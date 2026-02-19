<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Restaurant;
use App\Model\Route;
use App\Model\WaInternalRequisition;
use App\Models\WaAccountTransaction;
use App\SalesmanShift;
use App\Services\ExcelDownloadService;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class RoutePerformanceReportController extends Controller
{
    protected $model;
    protected $base_route;
    protected $resource_folder;
    protected $base_title;
    protected $permissions_module;

    public function __construct()
    {
        $this->model = 'route-performance-report';
        $this->base_route = 'route-performance-report';
        $this->resource_folder = 'admin.salesreceiablesreports';
        $this->base_title = 'Route Performance Report';
        $this->permissions_module = 'sales-and-receivables-reports';
    }

    public function index(Request $request)
    {


        $title = $this->base_title;
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];
        $branches = Restaurant::all();
        $branchIds = $branches->pluck('id')->toArray();
        $routes = Route::whereIn('restaurant_id', $branchIds)
            ->select('id', 'route_name')
            ->get();

        if (!can('route-performance-report', $this->permissions_module)) {
            Session::flash('warning', pageRestrictedMessage());
            return redirect()->back();
        }
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;

        if(!$request->datePicker){
            $startDate = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::now()->endOfDay()->format('Y-m-d H:i:s');
            return view("$this->resource_folder.route_performance", compact('title', 'model', 'breadcum', 'routes',   'branches'));
        }else{
              if ($request->selectionType  == 'single'){
            $startDate = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

        }else{
            $dates = explode(' to ', $request->datePicker);
            $startDate = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
            $endDate = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
        }

        }

        $start_date = Carbon::parse($startDate)->toDateString();
        $end_date = Carbon::parse($endDate)->toDateString();

        // Optimized: Get base route data without heavy subqueries
        $query = DB::table('routes')
            ->leftJoin('salesman_shifts', 'salesman_shifts.route_id', '=', 'routes.id')
            ->leftJoin('users', 'users.id', '=', 'salesman_shifts.salesman_id')
            ->whereNot('salesman_shifts.status', 'not_started')
            ->whereBetween('salesman_shifts.created_at', [$startDate, $endDate]);
        
        if ($request->branch) {
            $query->where('routes.restaurant_id', $request->branch);
        } else {
            $query->whereIn('routes.restaurant_id', $branchIds);
        }
        if ($request->route) {
            $query->where('routes.id', $request->route);
        }

        $data = $query->select([
            'routes.id as route_id',
            'routes.route_name as route',
            'routes.group as group',
            'routes.order_frequency',
            'users.name as salesman',
            'routes.order_taking_days',
            'routes.tonnage_target',
            'routes.sales_target',
            'routes.ctn_target',
            'routes.dzn_target',
            'salesman_shifts.id as salesman_shifts_id',
            'salesman_shifts.start_time as start_time',
            'salesman_shifts.closed_time as closed_time',
        ])
        ->distinct()
        ->get();

        // Fetch aggregated data separately for better performance
        $aggregatedData = $this->getAggregatedRouteData($branchIds, $startDate, $endDate, $request);

        $data = $data->map(function ($record) use ($aggregatedData, $filterfrequency, $request, $startDate, $endDate) {
            $routeId = $record->route_id;
            $agg = $aggregatedData[$routeId] ?? null;

            if (!$agg) {
                return null;
            }

            // Calculate time spent onsite and offsite
            $offsiteRequest = DB::table('offsite_shift_requests')
                ->where('status', 'approved')
                ->where('shift_id', $record->salesman_shifts_id)
                ->orderBy('id', 'desc')
                ->first();

            if($offsiteRequest){
                $onsiteDuration = Carbon::parse($offsiteRequest->updated_at)->diffInMinutes(Carbon::parse($record->start_time));
                $onsiteDuration = CarbonInterval::minutes($onsiteDuration)->totalHours;
                $offsiteDuration = Carbon::parse($record->closed_time)->diffInMinutes(Carbon::parse($offsiteRequest->updated_at));
                $offsiteDuration = CarbonInterval::minutes($offsiteDuration)->totalHours;
            } else {
                $onsiteDuration = Carbon::parse($record->closed_time)->diffInMinutes(Carbon::parse($record->start_time));
                $onsiteDuration = CarbonInterval::minutes($onsiteDuration)->totalHours;
                $offsiteDuration = 0;
            }

            $record->total_onsite_hours = $onsiteDuration;
            $record->total_offsite_hours = $offsiteDuration;

            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $number_of_days = $end->diffInDays($start) + 1;
            $multiplier = ($number_of_days == 1) ? 1 : number_format((($number_of_days / 7) * $record->order_frequency), 2);
            $record->multiplier = $multiplier;

            // Assign aggregated data
            $record->centre_count = $agg['centre_count'] ?? 0;
            $record->shop_count = $agg['shop_count'] ?? 0;
            $record->met_shops = $agg['met_shops'] ?? 0;
            $record->total_onsite_shops = $agg['total_onsite_shops'] ?? 0;
            $record->total_offsite_shops = $agg['total_offsite_shops'] ?? 0;
            $record->met_with_no_orders = $agg['met_with_no_orders'] ?? 0;
            $record->gross_sales = $agg['gross_sales'] ?? 0;
            $record->returns = $agg['returns'] ?? 0;
            $record->tonnage = $agg['tonnage'] ?? 0;
            $record->ctns = $agg['ctns'] ?? 0;
            $record->dzns = $agg['dzns'] ?? 0;
            $record->frequency = $agg['frequency'] ?? 0;

            $record->net_sales = $record->gross_sales - $record->returns;
            $record->unmet = $record->shop_count - $record->met_shops;
            $record->total_order_taking_days = $multiplier;
            $record->freq = $record->frequency;
            $record->met_customers_percentage = $record->shop_count != 0 ? ($record->met_shops / $record->shop_count) * 100 : 0;

            $netsales = $record->net_sales;
            $tonnagedata = $record->tonnage;
            $ctnsdata = $record->ctns;
            $dznsdata = $record->dzns;

            $record->sales_percentage = ((($record->sales_target) != 0) && (($record->total_order_taking_days) != 0)) ? (($netsales / ($record->sales_target * $record->total_order_taking_days)) * 100) : 0;
            $record->tonnage_percentage = ((($record->tonnage_target ) != 0) && (($record->total_order_taking_days) != 0)) ? (($tonnagedata / ($record->tonnage_target * $record->total_order_taking_days)) * 100) : 0;
            $record->ctns_percentage = ((($record->ctn_target ) != 0 ) && (($record->total_order_taking_days) != 0)) ? (($ctnsdata / ($record->ctn_target * $record->total_order_taking_days)) * 100) : 0;
            $record->dzns_percentage = ((($record->dzn_target ) != 0 ) && (($record->total_order_taking_days) != 0)) ? (($dznsdata / ($record->dzn_target * $record->total_order_taking_days)) * 100) : 0;

            $record->avg_percentage = ($record->met_customers_percentage + $record->sales_percentage + $record->tonnage_percentage + $record->ctns_percentage + $record->dzns_percentage) / 5;

            $record->met_customers_percentage = round($record->met_customers_percentage, 2);
            $record->sales_percentage = round($record->sales_percentage, 2);
            $record->tonnage_percentage = round($record->tonnage_percentage, 2);
            $record->ctns_percentage = round($record->ctns_percentage, 2);
            $record->dzns_percentage = round($record->dzns_percentage, 2);
            $record->avg_percentage = round($record->avg_percentage, 2);

            return $record;
        })->filter();
        if ($request->group) {
            $data = $data->where('group', $request->group)->all();
        }

        $data = collect($data);

        if ($request->filter && $request->filter == 'sales') {
            $data = $data->sortBy('net_sales', descending: true);
        }
        if ($request->filter && $request->filter == 'tonnage') {
            $data = $data->sortBy('tonnage', descending: true);
        }
        if ($request->filter && $request->filter == 'ctns') {
            $data = $data->sortBy('ctns', descending: true);
        }
        if ($request->filter && $request->filter == 'dzns') {
            $data = $data->sortBy('dzns', descending: true);
        }
        if ($request->filter && $request->filter == 'unmet') {
            $data = $data->sortBy('unmet', descending: false);
        }

        $totalGrossSales = $data->sum('gross_sales');
        $totalReturns = $data->sum('returns');
        $totalNetSales = $data->sum('net_sales');
        $totalTonnage = $data->sum('tonnage');
        $totalCtns = $data->sum('ctns');
        $totalDzns = $data->sum('dzns');

    //    dd($data);

        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'CENTRE COUNT', 'SHOP COUNT', 'MET SHOPS', 'MET ONSITE','MET OFFSITE','MET WITH NO ORDERS','UNMET','TIME ONSITE', 'TIME OFFSITE', 'MET CUSTOMERS %', 'TARGET TONNAGE', 'TONNAGE', 'TONNAGE %', 'TARGET CTNS', 'CTNS', 'CTNS %', 'TARGET DZNS', 'DZNS', 'DZNS %', 'TARGET SALES', 'GROSS SALES', 'RETURNS', 'NET SALES', 'SALES %', 'OVERALL AVG % PERFORMANCE'];
            $filename = "ROUTE PERFORMANCE REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'centre-count' => $row->centre_count,
                    'Shop-count' => $row->shop_count,
                    'met-shops' => $row->met_shops,
                    'met-onsite' => max(0, $row->total_onsite_shops ?? 0),
                    'met-offsite' => $row->met_shops - ($row->total_onsite_shops ?? 0) - ($row->met_with_no_orders ?? 0),
                    'met-with-no-orders' => $row->met_with_no_orders,
                    'unmet' => $row->shop_count - ($row->met_shops ?? 0),
                    'hours-onsite' => number_format($row->total_onsite_hours, 2),
                    'hours-offsite' => number_format($row->total_offsite_hours, 2),
                    'met-customer-percentage' => number_format($row->met_customers_percentage, 2),
                    'target-tonnage' => number_format(($row->tonnage_target * $row->total_order_taking_days), 2),
                    'tonnage' => number_format(($row->tonnage), 2),
                    'tonnage-percentage' => number_format($row->tonnage_percentage, 2),
                    'target-ctns' => number_format(($row->ctn_target * $row->total_order_taking_days), 2),
                    'ctns' => number_format(($row->ctns), 2),
                    'ctns-percentage' => number_format($row->ctns_percentage, 2),
                    'target-dzns' =>  number_format(($row->dzn_target * $row->total_order_taking_days), 2),
                    'dzns' => number_format(($row->dzns), 2),
                    'dzns-percentage' => number_format($row->dzns_percentage, 2),
                    'target-sales' => number_format(($row->sales_target * $row->total_order_taking_days), 2),
                    'gross-sales' => number_format(($row->gross_sales), 2),
                    'returns' => number_format(($row->returns), 2),
                    'net-sales' => number_format(($row->net_sales ), 2),
                    'sales-percentage' => number_format($row->sales_percentage, 2),
                    'overall-avg-percentage' => number_format($row->avg_percentage, 2),
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("$this->resource_folder.route_performance", compact('title', 'model', 'breadcum', 'routes', 'data', 'totalGrossSales', 'filterfrequency', 'totalReturns', 'totalNetSales', 'totalTonnage', 'totalCtns', 'totalDzns', 'start_date', 'end_date', 'branches'));

    }

    public function salesPerRoute(Request $request)
    {

        $title = 'Sales Performance report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];

        $branches = Restaurant::all();
        $branchIds = $branches->pluck('id')->toArray();
        $routes = Route::select('id', 'route_name')->get();
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;
        if(!$request->datePicker){
            return view("admin.sales_and_receivables_reports.route_performance", compact('title', 'model', 'breadcum', 'routes', 'branches'));


        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }


        $startDate = $start_date;
        $endDate = $end_date;
        $data = $this->q2($request, $startDate, $endDate, $branchIds);

        if ($request->group) {
            $data = $data->where('group', $request->group)->all();
        }

        $data = collect($data);

        if ($request->filter && $request->filter == 'sales') {
            $data = $data->sortBy('net_sales', descending: true);
        }
        if ($request->filter && $request->filter == 'tonnage') {
            $data = $data->sortBy('tonnage', descending: true);
        }
        if ($request->filter && $request->filter == 'ctns') {
            $data = $data->sortBy('ctns', descending: true);
        }
        if ($request->filter && $request->filter == 'dzns') {
            $data = $data->sortBy('dzns', descending: true);
        }
        if ($request->filter && $request->filter == 'unmet') {
            $data = $data->sortBy('unmet', descending: false);
        }

        $totalGrossSales = $data->sum('gross_sales');
        $totalReturns = $data->sum('returns');
        $totalNetSales = $data->sum('net_sales');
        $totalTonnage = $data->sum('tonnage');
        $totalCtns = $data->sum('ctns');
        $totalDzns = $data->sum('dzns');

        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'CENTRE COUNT', 'SHOP COUNT', 'TARGET SALES', 'GROSS SALES', 'RETURNS', 'NET SALES', 'SALES %'];
            $filename = "ROUTE SALES PERFORMANCE REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'centre-count' => $row->centre_count,
                    'Shop-count' => $row->shop_count,
                    'target-sales' => number_format(($row->sales_target * $row->total_order_taking_days), 2),
                    'gross-sales' => number_format(($row->gross_sales), 2),
                    'returns' => number_format(($row->returns), 2),
                    'net-sales' => number_format(($row->net_sales ), 2),
                    'sales-percentage' => number_format($row->sales_percentage, 2),
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }

        return view("admin.sales_and_receivables_reports.route_performance", compact('title', 'model', 'breadcum', 'routes', 'data', 'totalGrossSales', 'filterfrequency', 'totalReturns', 'totalNetSales', 'totalTonnage', 'totalCtns', 'totalDzns', 'start_date', 'end_date', 'branches'));

    }


    public function q2(Request $request, $startDate, $endDate, $branchIds)
    {

        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;

        $query = DB::table('routes');
        if ($request->branch) {
            $query->where('routes.restaurant_id', $request->branch);
        } else {
            $query->whereIn('routes.restaurant_id', $branchIds);
        }
        if ($request->route) {
            $query = $query->where('routes.id', $request->route);
        }
        $data = $query->select([
            'routes.id as route_id',
            'routes.route_name as route',
            'routes.group as group',
            'routes.order_frequency',
            'users.name as salesman',
            'routes.order_taking_days',
            'routes.tonnage_target',
            'routes.sales_target',
            'routes.ctn_target',
            'routes.dzn_target',
            DB::raw("(select count(*) from delivery_centres where delivery_centres.route_id = routes.id and delivery_centres.deleted_at is null) as centre_count"),
            DB::raw("(select count(*) from wa_route_customers where wa_route_customers.route_id = routes.id and wa_route_customers.deleted_at is null) as shop_count"),
            // DB::raw("(select count(distinct wa_route_customer_id) from wa_internal_requisitions where wa_internal_requisitions.route_id = routes.id and wa_internal_requisitions.created_at between '$startDate' and '$endDate') as met_shops"),
            DB::raw("(select count(distinct salesman_shift_customers.route_customer_id) 
                from wa_internal_requisitions 
                join salesman_shift_customers on wa_internal_requisitions.wa_shift_id = salesman_shift_customers.salesman_shift_id
                where salesman_shift_customers.visited = 1 and wa_internal_requisitions.route_id = routes.id and wa_internal_requisitions.created_at between '$startDate' and '$endDate') as met_shops"),

            DB::raw("(select sum(wa_internal_requisition_items.total_cost_with_vat) from wa_internal_requisition_items
            join wa_internal_requisitions on wa_internal_requisition_items.wa_internal_requisition_id = wa_internal_requisitions.id
            where wa_internal_requisitions.route_id = routes.id and wa_internal_requisition_items.created_at between '$startDate' and '$endDate')
            as gross_sales"),
            DB::raw("(select sum(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price)
            from wa_inventory_location_transfer_item_returns
            join wa_inventory_location_transfers on wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
            join wa_inventory_location_transfer_items on wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            where wa_inventory_location_transfers.route = routes.route_name and wa_inventory_location_transfers.created_at between '$startDate' and '$endDate')
            as returns"),
            DB::raw("(select sum(COALESCE(wa_inventory_items.net_weight * wa_internal_requisition_items.quantity, 0) / 1000) from wa_internal_requisition_items
            left join wa_inventory_items on wa_internal_requisition_items.wa_inventory_item_id = wa_inventory_items.id
            left join wa_internal_requisitions on wa_internal_requisition_items.wa_internal_requisition_id = wa_internal_requisitions.id
            where wa_internal_requisitions.route_id = routes.id and wa_internal_requisition_items.created_at between '$startDate' and '$endDate')
            as tonnage"),
            // DB::raw("(select sum(CASE WHEN pack_sizes.title = 'CTN' THEN wa_internal_requisition_items.quantity ELSE 0 END) from wa_internal_requisition_items
            // left join wa_inventory_items on wa_internal_requisition_items.wa_inventory_item_id = wa_inventory_items.id
            // left  join pack_sizes on wa_inventory_items.pack_size_id = pack_sizes.id
            // left join wa_internal_requisitions on wa_internal_requisition_items.wa_internal_requisition_id = wa_internal_requisitions.id
            // where wa_internal_requisitions.route_id = routes.id and wa_internal_requisition_items.created_at between '$startDate' and '$endDate')
            // as ctns"),
            DB::raw("(select count(distinct concat(wa_inventory_items.id ,date(wa_internal_requisition_items.created_at)) ) from wa_internal_requisition_items
            left join wa_inventory_items on wa_internal_requisition_items.wa_inventory_item_id = wa_inventory_items.id
            left join wa_internal_requisitions on wa_internal_requisition_items.wa_internal_requisition_id = wa_internal_requisitions.id
            where wa_inventory_items.pack_size_id = 3 and wa_internal_requisitions.route_id = routes.id and wa_internal_requisition_items.created_at between '$startDate' and '$endDate')
            as ctns"),

            DB::raw("(select sum(CASE WHEN pack_sizes.title = 'CTN' THEN wa_inventory_location_transfer_item_returns.received_quantity ELSE 0 END) from wa_inventory_location_transfer_item_returns
            join wa_inventory_location_transfer_items on wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            join wa_inventory_items on wa_inventory_location_transfer_items.wa_inventory_item_id = wa_inventory_items.id
            join pack_sizes on wa_inventory_items.pack_size_id = pack_sizes.id
            join wa_inventory_location_transfers on wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
            where wa_inventory_location_transfers.route = routes.route_name and wa_inventory_location_transfers.created_at between '$startDate' and '$endDate')
            as returned_ctns"),

            // DB::raw("(select sum(CASE WHEN pack_sizes.id in (6,9,17,4,10,1) THEN wa_internal_requisition_items.quantity ELSE 0 END) from wa_internal_requisition_items
            // left join wa_inventory_items on wa_internal_requisition_items.wa_inventory_item_id = wa_inventory_items.id
            // left  join pack_sizes on wa_inventory_items.pack_size_id = pack_sizes.id
            // left join wa_internal_requisitions on wa_internal_requisition_items.wa_internal_requisition_id = wa_internal_requisitions.id
            // where wa_internal_requisitions.route_id = routes.id and wa_internal_requisition_items.created_at between '$startDate' and '$endDate')
            // as dzns"),

            DB::raw("(select count(distinct concat(wa_inventory_items.id, date(wa_internal_requisition_items.created_at))) from wa_internal_requisition_items
            left join wa_inventory_items on wa_internal_requisition_items.wa_inventory_item_id = wa_inventory_items.id
            left join wa_internal_requisitions on wa_internal_requisition_items.wa_internal_requisition_id = wa_internal_requisitions.id
            where wa_inventory_items.pack_size_id in (6,9,17,4,10,1) and wa_internal_requisitions.route_id = routes.id and wa_internal_requisition_items.created_at between '$startDate' and '$endDate')
            as dzns"),

            DB::raw("(select sum(CASE WHEN pack_sizes.id in (6,9,17,4,10,1) THEN wa_inventory_location_transfer_item_returns.received_quantity ELSE 0 END) from wa_inventory_location_transfer_item_returns
            join wa_inventory_location_transfer_items on wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            join wa_inventory_items on wa_inventory_location_transfer_items.wa_inventory_item_id = wa_inventory_items.id
            join pack_sizes on wa_inventory_items.pack_size_id = pack_sizes.id
            join wa_inventory_location_transfers on wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
            where wa_inventory_location_transfers.route = routes.route_name and wa_inventory_location_transfers.created_at between '$startDate' and '$endDate')
            as returned_dzns"),

            DB::raw("(select count(distinct(requisition_date)) from wa_internal_requisitions where route_id = routes.id and created_at between '$startDate' and '$endDate') as frequency")
        ])
            ->join('route_user', 'routes.id', '=', 'route_user.route_id')
            ->join('users', function ($join) {
                $join->on('route_user.user_id', '=', 'users.id')->where('users.role_id', 4);
            })
//            ->take(30)
            ->get()
            ->map(function ($record) use ($filterfrequency, $request, $startDate, $endDate) {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);
                $multiplier = 1;
                if($start and $end) {
                    $number_of_days = $end->diffInDays($start) + 1;
                    if($number_of_days == 1) {
                        $multiplier = 1;
                    }else{
                        $multiplier = number_format((($number_of_days / 7) * $record->order_frequency), 2);
                    }


                }
                $record->multiplier  = $multiplier;



                $orderTakingDays = $record->frequency;
                $record->net_sales = $record->gross_sales - $record->returns;
                $record->unmet = $record->shop_count - $record->met_shops;
                // $record->total_order_taking_days = $orderTakingDays;
                $record->total_order_taking_days = $multiplier;

                $record->freq = $orderTakingDays;
                $record->met_customers_percentage = $record->shop_count != 0 ? ($record->met_shops / $record->shop_count) * 100 : 0;

                $netsales = $record->net_sales;
                $tonnagedata = $record->tonnage;
                $ctnsdata = $record->ctns;
                $dznsdata = $record->dzns;

                $record->sales_percentage = ((($record->sales_target) != 0) && (($record->total_order_taking_days) != 0)) ? (($netsales / ($record->sales_target * $record->total_order_taking_days)) * 100) : 0;
                $record->tonnage_percentage = ((($record->tonnage_target ) != 0) && (($record->total_order_taking_days) != 0)) ? (($tonnagedata / ($record->tonnage_target * $record->total_order_taking_days)) * 100) : 0;
                $record->ctns_percentage = ((($record->ctn_target ) != 0 ) && (($record->total_order_taking_days) != 0)) ? (($ctnsdata / ($record->ctn_target * $record->total_order_taking_days)) * 100) : 0;
                $record->dzns_percentage = ((($record->dzn_target ) != 0 ) && (($record->total_order_taking_days) != 0)) ? (($dznsdata / ($record->dzn_target * $record->total_order_taking_days)) * 100) : 0;

                $record->avg_percentage = ($record->met_customers_percentage + $record->sales_percentage + $record->tonnage_percentage + $record->ctns_percentage + $record->dzns_percentage) / 5;

                $record->met_customers_percentage = round($record->met_customers_percentage, 2);
                $record->sales_percentage = round($record->sales_percentage, 2);
                $record->tonnage_percentage = round($record->tonnage_percentage, 2);
                $record->ctns_percentage = round($record->ctns_percentage, 2);
                $record->dzns_percentage = round($record->dzns_percentage, 2);
                $record->avg_percentage = round($record->avg_percentage, 2);

                return $record;
            });
        return $data;
    }


    public function unmetCustomers(Request $request)
    {
        $title = 'Unmet Customers Report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];

        $branches = Restaurant::all();
        $branchIds = $branches->pluck('id')->toArray();

        $routes = Route::select('id', 'route_name')->get();
        if(!$request->datePicker){
            return view("admin.sales_and_receivables_reports.unmet-customers", compact('title', 'model', 'breadcum', 'routes', 'branches'));

        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }

        $startDate = $start_date;
        $endDate = $end_date;
        $data = $this->q2($request, $startDate, $endDate, $branchIds);
        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'CENTRE COUNT', 'SHOP COUNT', 'MET SHOPS', 'UNMET', 'MET CUSTOMERS %'];
            $filename = "ROUTE MEET/UNMET REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'centre-count' => $row->centre_count,
                    'Shop-count' => $row->shop_count,
                    'met-shops' => $row->met_shops,
                    'unmet-shops' => max($row->unmet, 0),
                    'met-customer-percentage' => number_format(min($row->met_customers_percentage, 100), 2),
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("admin.sales_and_receivables_reports.unmet-customers", compact('title', 'model', 'breadcum', 'routes', 'data',  'start_date', 'end_date', 'branches'));
    }

    public function tonnage(Request $request)
    {
        $title = 'Tonnage report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];
        $routes = Route::select('id', 'route_name')->get();
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;

        $branches = Restaurant::all();
        $branchIds = $branches->pluck('id')->toArray();

        if(!$request->datePicker){
            return view("admin.sales_and_receivables_reports.route_performance_tonnage", compact('title', 'model', 'breadcum', 'routes', 'branches'));

        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }

        $startDate = $start_date;
        $endDate = $end_date;

        $data = $this->q2($request, $startDate, $endDate, $branchIds);

        if ($request->group) {
            $data = $data->where('group', $request->group)->all();
        }
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;
        $data = collect($data);

        if ($request->filter && $request->filter == 'tonnage') {
            $data = $data->sortBy('tonnage', descending: true);
        }
        if ($request->filter && $request->filter == 'ctns') {
            $data = $data->sortBy('ctns', descending: true);
        }
        if ($request->filter && $request->filter == 'dzns') {
            $data = $data->sortBy('dzns', descending: true);
        }
        $totalTonnage = $data->sum('tonnage');
        $totalCtns = $data->sum('ctns');
        $totalDzns = $data->sum('dzns');

        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'CENTERS','TONNAGE','CTN','DZNS'];
            $filename = "ROUTE TONNAGE REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'centers' => $row->centre_count,
                    'target-tonnage' => number_format(($row->tonnage_target * $row->total_order_taking_days), 2),
                    'tonnage' => number_format(($row->tonnage), 2),
                    'tonnage-percentage' => number_format($row->tonnage_percentage, 2),
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("admin.sales_and_receivables_reports.route_performance_tonnage", compact('title', 'model', 'breadcum', 'routes', 'data', 'filterfrequency', 'totalTonnage', 'totalCtns', 'totalDzns', 'start_date', 'end_date', 'branches'));

    }
    public function cartons(Request $request)
    {
        $title = 'Cartons report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];
        $routes = Route::select('id', 'route_name')->get();
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;

        $branches = Restaurant::where('name','like','%thika%')->get();
        $branchIds = $branches->pluck('id')->toArray();

        if(!$request->datePicker){
            return view("admin.sales_and_receivables_reports.route_performance_cartons", compact('title', 'model', 'breadcum', 'routes', 'branches'));

        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }

        $startDate = $start_date;
        $endDate = $end_date;

        $data = $this->q2($request, $startDate, $endDate, $branchIds);

        if ($request->group) {
            $data = $data->where('group', $request->group)->all();
        }
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;
        $data = collect($data);

        if ($request->filter && $request->filter == 'tonnage') {
            $data = $data->sortBy('tonnage', descending: true);
        }
        if ($request->filter && $request->filter == 'ctns') {
            $data = $data->sortBy('ctns', descending: true);
        }
        if ($request->filter && $request->filter == 'dzns') {
            $data = $data->sortBy('dzns', descending: true);
        }
        $totalTonnage = $data->sum('tonnage');
        $totalCtns = $data->sum('ctns');
        $totalDzns = $data->sum('dzns');

        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'CTN TARGET','CTN','CTN %'];
            $filename = "ROUTE CARTON REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'target-ctns' => number_format(($row->ctn_target * $row->total_order_taking_days), 2),
                    'ctns' => number_format(($row->ctns), 2),
                    'ctns-percentage' => number_format($row->ctns_percentage, 2),
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("admin.sales_and_receivables_reports.route_performance_cartons", compact('title', 'model', 'breadcum', 'routes', 'data', 'filterfrequency', 'totalTonnage', 'totalCtns', 'totalDzns', 'start_date', 'end_date', 'branches'));

    }
    public function dozens(Request $request)
    {
        $title = 'Dozens report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];
        $routes = Route::select('id', 'route_name')->get();
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;

        $branches = Restaurant::where('name','like','%thika%')->get();
        $branchIds = $branches->pluck('id')->toArray();

        if(!$request->datePicker){
            return view("admin.sales_and_receivables_reports.route_performance_dozens", compact('title', 'model', 'breadcum', 'routes', 'branches'));

        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }

        $startDate = $start_date;
        $endDate = $end_date;

        $data = $this->q2($request, $startDate, $endDate, $branchIds);

        if ($request->group) {
            $data = $data->where('group', $request->group)->all();
        }
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;
        $data = collect($data);

        if ($request->filter && $request->filter == 'tonnage') {
            $data = $data->sortBy('tonnage', descending: true);
        }
        if ($request->filter && $request->filter == 'ctns') {
            $data = $data->sortBy('ctns', descending: true);
        }
        if ($request->filter && $request->filter == 'dzns') {
            $data = $data->sortBy('dzns', descending: true);
        }
        $totalTonnage = $data->sum('tonnage');
        $totalCtns = $data->sum('ctns');
        $totalDzns = $data->sum('dzns');

        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'TONNAGE','TARGET DZNS TARGET','DZNS','DZNS %'];
            $filename = "ROUTE DOZEN REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'dzn_target' => number_format(($row->dzn_target * $row->total_order_taking_days), 2),
                    'dzns' => $row->dzns,
                    'dzns_percentage' => $row->dzns_percentage,
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("admin.sales_and_receivables_reports.route_performance_dozens", compact('title', 'model', 'breadcum', 'routes', 'data', 'filterfrequency', 'totalTonnage', 'totalCtns', 'totalDzns', 'start_date', 'end_date', 'branches'));

    }
    public function returns(Request $request)
    {
        $title = 'Returns report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];
        $routes = Route::select('id', 'route_name')->get();
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;

        $branches = Restaurant::where('name','like','%thika%')->get();
        $branchIds = $branches->pluck('id')->toArray();

        if(!$request->datePicker){
            return view("admin.sales_and_receivables_reports.route_performance_returns", compact('title', 'model', 'breadcum', 'routes', 'branches'));

        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }

        $startDate = $start_date;
        $endDate = $end_date;

        $data = $this->q2($request, $startDate, $endDate, $branchIds);

        if ($request->group) {
            $data = $data->where('group', $request->group)->all();
        }
        $filterfrequency = $request->has('frequency_filter') ? intval($request->frequency_filter) : null;
        $data = collect($data);

        if ($request->filter && $request->filter == 'tonnage') {
            $data = $data->sortBy('tonnage', descending: true);
        }
        if ($request->filter && $request->filter == 'ctns') {
            $data = $data->sortBy('ctns', descending: true);
        }
        if ($request->filter && $request->filter == 'dzns') {
            $data = $data->sortBy('dzns', descending: true);
        }
        $totalTonnage = $data->sum('tonnage');
        $totalCtns = $data->sum('ctns');
        $totalDzns = $data->sum('dzns');
        $totalReturns = $data->sum('returns');

        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'GROUP', 'SALESMAN', 'FREQ', 'RETURNS'];
            $filename = "ROUTE RETURNS REPORT $startDate - $endDate";
            $excelData = [];
            foreach ($data as $row) {

                $payload = [
                    'route' => $row->route,
                    'group' => $row->group,
                    'salesman' => $row->salesman,
                    'freq' => $row->multiplier,
                    'returns' => $row -> returns ?? 0,
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("admin.sales_and_receivables_reports.route_performance_returns", compact('title', 'totalReturns','model', 'breadcum', 'routes', 'data', 'filterfrequency', 'totalTonnage', 'totalCtns', 'totalDzns', 'start_date', 'end_date', 'branches'));

    }

    public function routeShifts(Request $request)
    {
        $title = 'Route Shifts Report';
        $model = $this->model;
        $breadcum = ['Sales & Receivables' => '', 'Reports' => '', 'Route Performance Report' => ''];

        $branches = Restaurant::where('name','like','%thika%')->get();
        $branchIds = $branches->pluck('id')->toArray();

        if(!$request->datePicker){
            $start_date = Carbon::now()->startOfDecade()->format('Y-m-d H:i:s');
            $end_date = Carbon::now()->format('Y-m-d H:i:s');
            return view("admin.sales_and_receivables_reports.shifts_report", compact('title', 'model', 'breadcum','branches'));

        }else{
            if ($request->selectionType  == 'single'){
                $start_date = Carbon::parse($request->datePicker)->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($request->datePicker)->endOfDay()->format('Y-m-d H:i:s');

            }else{
                $dates = explode(' to ', $request->datePicker);
                $start_date = Carbon::parse($dates[0])->startOfDay()->format('Y-m-d H:i:s');
                $end_date = Carbon::parse($dates[1])->endOfDay()->format('Y-m-d H:i:s');
            }

        }
        $startDate = $start_date;
        $endDate = $end_date;
        $routes = Route::withCount([
            'shifts as total_shifts' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            },
            'shifts as onsite_shifts_count' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('firstorder', function ($query) use ($startDate, $endDate) {
                    $query->where('shift_type', 'onsite')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->orderBy('created_at', 'asc');
                });
            },
            'shifts as offsite_shifts_count' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('firstorder', function ($query) use ($startDate, $endDate) {
                    $query->where('shift_type', 'offsite')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->orderBy('created_at', 'asc');
                });
            },
        ])->when($request->branch, function ($q) use ($request){
            $q->where('restaurant_id', $request->branch);
        })->get();
        if ($request->intent == 'EXCEL') {
            $headings = ['ROUTE', 'TOTAL SHIFTS', 'ONSITE SHIFTS', 'OFF SITE SHIFTS', 'OFF SITE SHIFTS %'];
            $filename = "ROUTE PERFORMANCE REPORT ON SHIFTS $startDate - $endDate";
            $excelData = [];
            foreach ($routes as $route) {
                $total_shifts = $route->onsite_shifts_count + $route->offsite_shifts_count;
                $payload = [
                    'route' => $route->route_name,
                    'total_shifts' => $total_shifts,
                    'onsite_shits' => $route->onsite_shifts_count,
                    'off_site_shits' => $route->offsite_shifts_count,
                    'off_site_shits_%' => $total_shifts != 0 ? number_format(($route->offsite_shifts_count / $total_shifts) * 100, 2) : number_format(0, 2) ,
                ];
                $excelData[] = $payload;
            }
            return ExcelDownloadService::download($filename, collect($excelData), $headings);
        }
        return view("admin.sales_and_receivables_reports.shifts_report", compact('title', 'model', 'breadcum', 'routes','branches'));

    }

    /**
     * Optimized method to fetch aggregated route data
     * Reduces N+1 queries by fetching all data at once
     */
    private function getAggregatedRouteData($branchIds, $startDate, $endDate, $request)
    {
        $routeIds = DB::table('routes')
            ->whereIn('restaurant_id', $branchIds)
            ->when($request->branch, fn($q) => $q->where('restaurant_id', $request->branch))
            ->when($request->route, fn($q) => $q->where('id', $request->route))
            ->pluck('id');

        $aggregated = [];

        // Get all data in one query per metric
        $centreData = DB::table('delivery_centres')
            ->whereIn('route_id', $routeIds)
            ->whereNull('deleted_at')
            ->select('route_id', DB::raw('COUNT(*) as count'))
            ->groupBy('route_id')
            ->pluck('count', 'route_id');

        $shopData = DB::table('wa_route_customers')
            ->whereIn('route_id', $routeIds)
            ->whereNull('deleted_at')
            ->select('route_id', DB::raw('COUNT(*) as count'))
            ->groupBy('route_id')
            ->pluck('count', 'route_id');

        $metShopsData = DB::table('wa_internal_requisitions')
            ->join('salesman_shift_customers', 'wa_internal_requisitions.wa_shift_id', '=', 'salesman_shift_customers.salesman_shift_id')
            ->whereIn('wa_internal_requisitions.route_id', $routeIds)
            ->whereBetween('wa_internal_requisitions.created_at', [$startDate, $endDate])
            ->where('salesman_shift_customers.visited', 1)
            ->select('wa_internal_requisitions.route_id', DB::raw('COUNT(DISTINCT salesman_shift_customers.route_customer_id) as count'))
            ->groupBy('wa_internal_requisitions.route_id')
            ->pluck('count', 'route_id');

        $onsiteShopsData = DB::table('wa_internal_requisitions')
            ->whereIn('route_id', $routeIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('shift_type', 'onsite')
            ->select('route_id', DB::raw('COUNT(DISTINCT wa_route_customer_id) as count'))
            ->groupBy('route_id')
            ->pluck('count', 'route_id');

        $offsiteShopsData = DB::table('wa_internal_requisitions')
            ->whereIn('route_id', $routeIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('shift_type', 'offsite')
            ->select('route_id', DB::raw('COUNT(DISTINCT wa_route_customer_id) as count'))
            ->groupBy('route_id')
            ->pluck('count', 'route_id');

        $metNoOrdersData = DB::table('salesman_shift_customers')
            ->join('salesman_shifts', 'salesman_shift_customers.salesman_shift_id', '=', 'salesman_shifts.id')
            ->whereIn('salesman_shifts.route_id', $routeIds)
            ->whereBetween('salesman_shifts.created_at', [$startDate, $endDate])
            ->where('salesman_shift_customers.visited', 1)
            ->where('salesman_shift_customers.order_taken', 0)
            ->select('salesman_shifts.route_id', DB::raw('COUNT(DISTINCT salesman_shift_customers.route_customer_id, salesman_shift_customers.salesman_shift_id) as count'))
            ->groupBy('salesman_shifts.route_id')
            ->pluck('count', 'route_id');

        $salesData = DB::table('wa_internal_requisition_items')
            ->join('wa_internal_requisitions', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->whereIn('wa_internal_requisitions.route_id', $routeIds)
            ->whereBetween('wa_internal_requisition_items.created_at', [$startDate, $endDate])
            ->select('wa_internal_requisitions.route_id', DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as total'))
            ->groupBy('wa_internal_requisitions.route_id')
            ->pluck('total', 'route_id');

        $returnsData = DB::table('wa_inventory_location_transfer_item_returns')
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('routes', function($join) {
                $join->on(DB::raw('wa_inventory_location_transfers.route'), '=', 'routes.route_name');
            })
            ->whereIn('routes.id', $routeIds)
            ->whereBetween('wa_inventory_location_transfers.created_at', [$startDate, $endDate])
            ->select('routes.id as route_id', DB::raw('SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price) as total'))
            ->groupBy('routes.id')
            ->pluck('total', 'route_id');

        $tonnageData = DB::table('wa_internal_requisition_items')
            ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->join('wa_internal_requisitions', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->whereIn('wa_internal_requisitions.route_id', $routeIds)
            ->whereBetween('wa_internal_requisition_items.created_at', [$startDate, $endDate])
            ->select('wa_internal_requisitions.route_id', DB::raw('SUM(COALESCE(wa_inventory_items.net_weight * wa_internal_requisition_items.quantity, 0) / 1000) as total'))
            ->groupBy('wa_internal_requisitions.route_id')
            ->pluck('total', 'route_id');

        $ctnsData = DB::table('wa_internal_requisition_items')
            ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->join('wa_internal_requisitions', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->whereIn('wa_internal_requisitions.route_id', $routeIds)
            ->whereBetween('wa_internal_requisition_items.created_at', [$startDate, $endDate])
            ->where('wa_inventory_items.pack_size_id', 3)
            ->select('wa_internal_requisitions.route_id', DB::raw('COUNT(DISTINCT CONCAT(wa_inventory_items.id, DATE(wa_internal_requisition_items.created_at))) as total'))
            ->groupBy('wa_internal_requisitions.route_id')
            ->pluck('total', 'route_id');

        $dznsData = DB::table('wa_internal_requisition_items')
            ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->join('wa_internal_requisitions', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->whereIn('wa_internal_requisitions.route_id', $routeIds)
            ->whereBetween('wa_internal_requisition_items.created_at', [$startDate, $endDate])
            ->whereIn('wa_inventory_items.pack_size_id', [6,9,17,4,10,1])
            ->select('wa_internal_requisitions.route_id', DB::raw('COUNT(DISTINCT CONCAT(wa_inventory_items.id, DATE(wa_internal_requisition_items.created_at))) as total'))
            ->groupBy('wa_internal_requisitions.route_id')
            ->pluck('total', 'route_id');

        $frequencyData = DB::table('wa_internal_requisitions')
            ->whereIn('route_id', $routeIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('route_id', DB::raw('COUNT(DISTINCT requisition_date) as total'))
            ->groupBy('route_id')
            ->pluck('total', 'route_id');

        // Build aggregated array
        foreach ($routeIds as $routeId) {
            $aggregated[$routeId] = [
                'centre_count' => $centreData[$routeId] ?? 0,
                'shop_count' => $shopData[$routeId] ?? 0,
                'met_shops' => $metShopsData[$routeId] ?? 0,
                'total_onsite_shops' => $onsiteShopsData[$routeId] ?? 0,
                'total_offsite_shops' => $offsiteShopsData[$routeId] ?? 0,
                'met_with_no_orders' => $metNoOrdersData[$routeId] ?? 0,
                'gross_sales' => $salesData[$routeId] ?? 0,
                'returns' => $returnsData[$routeId] ?? 0,
                'tonnage' => $tonnageData[$routeId] ?? 0,
                'ctns' => $ctnsData[$routeId] ?? 0,
                'dzns' => $dznsData[$routeId] ?? 0,
                'frequency' => $frequencyData[$routeId] ?? 0,
            ];
        }

        return $aggregated;
    }
}
