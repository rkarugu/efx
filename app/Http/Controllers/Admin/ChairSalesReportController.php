<?php

namespace App\Http\Controllers\Admin;

use App\Model\Restaurant;
use App\Model\Route;
use Carbon\Carbon;
use App\Model\WaCustomer;
use Illuminate\Http\Request;
use App\Enums\PaymentChannel;
use App\Model\WaRouteCustomer;
use App\SalesmanShiftCustomer;
use App\Model\WaInventoryCategory;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Model\WaDebtorTran;
use App\Model\WaInternalRequisition;
use Illuminate\Support\Facades\Session;
use App\Model\WaInternalRequisitionItem;
use App\WaInventoryLocationTransferItemReturn;
use App\WaTenderEntry;
use Illuminate\Support\Facades\Auth;

class ChairSalesReportController extends Controller
{

    protected $title;
    protected $model;
    protected $pmodel;

    public function __construct()
    {
        $this->title = 'Sales Reports';
        $this->model = 'chair-sales-report';
        $this->pmodel = 'chair-sales-report';
    }

    public function chairManDashboard()
    {
        if (can('view', 'management-dashboard')) {
            $model = 'management-dashboard';
            $title = 'Management Dashboard';

            $user = request()->user();
            
            return view('admin.page.dashboards.chair_dashboard', compact('model', 'title', 'user'));

        } else {
            return returnAccessDeniedPage();
        }
    }

    public function index1()
    {

        ini_set('memory_limit', '10000M');
        ini_set('max_execution_time', '0');

        $title = $this->title;
        $model = $this->model;
        $pmodel = $this->pmodel;

        $startDate = Carbon::now()->startOfYear();
        $currentDate = Carbon::now()->endOfDay();

        $currentyear = request()->input('year', now()->year);
        $currentmonth = request()->input('month', now()->month);

        $currentMonthStart = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');
        $currentMonthEnd = Carbon::now()->endOfMonth()->format('Y-m-d 23:59:59');

        $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d 00:00:00');
        $lastMonthEnd = Carbon::now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d 23:59:59');

        $previousMonthStart = Carbon::now()->subMonthsNoOverflow(2)->startOfMonth()->format('Y-m-d 00:00:00');
        $previousMonthEnd = Carbon::now()->subMonthsNoOverflow(2)->endOfMonth()->format('Y-m-d 23:59:59');

        $vooma = PaymentChannel::Vooma->value;
        $kcb = PaymentChannel::KCB->value;
        $equity = PaymentChannel::Equity->value;
        $eazzy = PaymentChannel::Eazzy->value;
        $mpesa = PaymentChannel::Mpesa->value;

        $totaldebtorbalances = 0;
        $categories = [];
        $tonnage = [];
        $monthly_met_unmet_data = [];
        $routes = [];
        $routeperfomance = [];
        $monthlysales = [];
        $monthlypayments = [];
        $invoices = [];
        $currentmonthnetsales = 0;
        $lastmonthnetsales = 0;
        $previousmonthnetsales = 0;

        // Months sales start
        $inventoryTransfers = DB::table('wa_internal_requisitions')
            ->leftJoin('wa_inventory_location_transfers', 'wa_inventory_location_transfers.transfer_no', '=', 'wa_internal_requisitions.requisition_no')
            ->leftJoin('wa_internal_requisition_items', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->select(
                DB::raw('SUM(CASE WHEN wa_internal_requisitions.created_at BETWEEN "' . $currentMonthStart . '" AND "' . $currentMonthEnd . '" THEN wa_internal_requisition_items.total_cost_with_vat ELSE 0 END) as current_month_vcs'),
                DB::raw('SUM(CASE WHEN wa_internal_requisitions.created_at BETWEEN "' . $lastMonthStart . '" AND "' . $lastMonthEnd . '" THEN wa_internal_requisition_items.total_cost_with_vat ELSE 0 END) as last_month_vcs'),
                DB::raw('SUM(CASE WHEN wa_internal_requisitions.created_at BETWEEN "' . $previousMonthStart . '" AND "' . $previousMonthEnd . '" THEN wa_internal_requisition_items.total_cost_with_vat ELSE 0 END) as previous_month_vcs'),
                DB::raw('(SELECT SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price)
            FROM wa_inventory_location_transfers 
            LEFT JOIN wa_inventory_location_transfer_items ON 
            wa_inventory_location_transfer_items.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
            LEFT JOIN wa_inventory_location_transfer_item_returns ON 
            wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            WHERE wa_inventory_location_transfer_item_returns.status = "received" 
            AND wa_inventory_location_transfer_item_returns.return_status = "1" 
            AND wa_inventory_location_transfer_item_returns.return_date BETWEEN "' . $currentMonthStart . '" AND "' . $currentMonthEnd . '") as current_month_returns'),
                DB::raw('(SELECT SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price)
            FROM wa_inventory_location_transfers 
            LEFT JOIN wa_inventory_location_transfer_items ON 
            wa_inventory_location_transfer_items.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
            LEFT JOIN wa_inventory_location_transfer_item_returns ON 
            wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            WHERE wa_inventory_location_transfer_item_returns.status = "received" 
            AND wa_inventory_location_transfer_item_returns.return_status = "1" 
            AND wa_inventory_location_transfer_item_returns.return_date BETWEEN "' . $lastMonthStart . '" AND "' . $lastMonthEnd . '") as last_month_returns'),
                DB::raw('(SELECT SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price)
            FROM wa_inventory_location_transfers 
            LEFT JOIN wa_inventory_location_transfer_items ON 
            wa_inventory_location_transfer_items.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
            LEFT JOIN wa_inventory_location_transfer_item_returns ON 
            wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            WHERE wa_inventory_location_transfer_item_returns.status = "received" 
            AND wa_inventory_location_transfer_item_returns.return_status = "1" 
            AND wa_inventory_location_transfer_item_returns.return_date BETWEEN "' . $previousMonthStart . '" AND "' . $previousMonthEnd . '") as previous_month_returns')
            )
            ->first();

        $currentMonthVcs = $inventoryTransfers->current_month_vcs;
        $currentMonthReturns = $inventoryTransfers->current_month_returns;
        $currentmonthnetsales = $currentMonthVcs - $currentMonthReturns;

        $lastMonthVcs = $inventoryTransfers->last_month_vcs;
        $lastMonthReturns = $inventoryTransfers->last_month_returns;
        $lastmonthnetsales = $lastMonthVcs - $lastMonthReturns;

        $previousMonthVcs = $inventoryTransfers->previous_month_vcs;
        $previousMonthReturns = $inventoryTransfers->previous_month_returns;
        $previousmonthnetsales = $previousMonthVcs - $previousMonthReturns;
        // Months sales end


        // Debtor balances start
        $totaldebtorbalances = WaCustomer::query()
            ->select([
                DB::raw("(SELECT SUM(amount) FROM wa_debtor_trans WHERE wa_debtor_trans.wa_customer_id = wa_customers.id) AS balance")
            ])
            ->get()
            ->sum('balance');
        // Debtor balances end

        // Monthly sales start
        $monthlysales = DB::table('wa_internal_requisitions')
            ->join('wa_internal_requisition_items', 'wa_internal_requisitions.id', '=', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->select(
                DB::raw("DATE_FORMAT(wa_internal_requisitions.requisition_date, '%Y-%m') as month"),
                DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as total_selling_price'),
                DB::raw(
                    '
        (
            SELECT SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price)
            FROM wa_inventory_location_transfer_item_returns
            LEFT JOIN wa_inventory_location_transfer_items ON 
            wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id = wa_inventory_location_transfer_items.id
            WHERE wa_inventory_location_transfer_item_returns.status = "received" 
            AND wa_inventory_location_transfer_item_returns.return_status = "1" 
            AND DATE_FORMAT(wa_inventory_location_transfer_item_returns.return_date, "%Y-%m") = DATE_FORMAT(wa_internal_requisitions.requisition_date, "%Y-%m")
        ) as returns'
                )
            )
            ->groupBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total_selling_price' => $item->total_selling_price,
                    'returns' => $item->returns,
                ];
            });
        // Monthly sales end

        // Monthly payments start
        $monthlypayments = DB::table('wa_tender_entries')
            ->select(
                DB::raw("DATE_FORMAT(wa_tender_entries.trans_date, '%Y-%m') as month"),
                DB::raw('SUM(CASE WHEN wa_tender_entries.channel = "' . $eazzy . '" OR wa_tender_entries.channel = "' . $equity . '" THEN wa_tender_entries.amount ELSE 0 END) as Eazzy'),
                DB::raw('SUM(CASE WHEN wa_tender_entries.channel = "' . $vooma . '" OR wa_tender_entries.channel = "' . $kcb . '" THEN wa_tender_entries.amount ELSE 0 END) as Vooma'),
                DB::raw('SUM(CASE WHEN wa_tender_entries.channel = "' . $mpesa . '" THEN wa_tender_entries.amount ELSE 0 END) as Mpesa'),
                DB::raw('SUM(CASE WHEN wa_tender_entries.channel IN ("' . $eazzy . '", "' . $equity . '", "' . $vooma . '", "' . $kcb . '", "' . $mpesa . '") THEN wa_tender_entries.amount ELSE 0 END) as total_payments')
            )
            ->groupBy(DB::raw("DATE_FORMAT(wa_tender_entries.trans_date, '%Y-%m')"))
            ->get();
        // Monthly payments end

        // Monthly tonnage start
        $tonnage = DB::table('wa_internal_requisition_items')
            ->select(
                DB::raw('SUM(COALESCE(wa_inventory_items.net_weight * wa_internal_requisition_items.quantity, 0) / 1000) as tonnage'),
                DB::raw('DATE_FORMAT(wa_internal_requisition_items.created_at, "%Y-%m") as month')
            )
            ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('tonnage', 'month')
            ->toArray();
        // Monthly tonnage end

        // Monthly met start
        $monthly_met_unmet_data = DB::table('salesman_shift_customers')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as shops_count'),
                DB::raw('SUM(CASE WHEN visited = 1 THEN 1 ELSE 0 END) as total_met'),
                DB::raw('SUM(CASE WHEN visited = 0 THEN 1 ELSE 0 END) as total_unmet'),
                DB::raw('SUM(CASE WHEN salesman_shift_type = "onsite" THEN 1 ELSE 0 END) as onsite'),
                DB::raw('SUM(CASE WHEN salesman_shift_type = "offsite" THEN 1 ELSE 0 END) as offsite'),
                DB::raw('SUM(CASE WHEN visited = 1 AND order_taken = 0 THEN 1 ELSE 0 END) as met_without_orders'),
            )
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();
        // Monthly met end

        // Monthly departmental start
        $categories = WaInternalRequisitionItem::select(
            'wa_inventory_items.wa_inventory_category_id',
            'wa_inventory_categories.category_description',
            DB::raw('SUM(total_cost_with_vat) as total_cost')
        )
            ->join('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_internal_requisition_items.wa_inventory_item_id')
            ->leftJoin('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')
            ->whereYear('wa_internal_requisition_items.created_at', $currentyear)
            ->whereMonth('wa_internal_requisition_items.created_at', $currentmonth)
            ->groupBy('wa_inventory_items.wa_inventory_category_id', 'wa_inventory_categories.category_description')
            ->get();
        // Monthly departmental end

        // Route perfomance start

        $sales_subquery = DB::table('wa_internal_requisitions')
            ->leftJoin('wa_internal_requisition_items', 'wa_internal_requisitions.id', '=', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->select(
                'wa_internal_requisitions.route',
                DB::raw("DATE_FORMAT(wa_internal_requisitions.requisition_date, '%Y-%m') as month"),
                DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as total_selling_price')
            )
            ->groupBy('wa_internal_requisitions.route', 'month')
            ->get();

        $returns_subquery = DB::table('wa_inventory_location_transfer_item_returns')
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->join('wa_internal_requisitions', 'wa_internal_requisitions.requisition_no', '=', 'wa_inventory_location_transfers.transfer_no')
            ->select(
                'wa_internal_requisitions.route',
                DB::raw("DATE_FORMAT(wa_inventory_location_transfer_item_returns.return_date, '%Y-%m') as month"),
                DB::raw('SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price) as total_returns')
            )
            ->where('wa_inventory_location_transfer_item_returns.status', '=', 'received')
            ->where('wa_inventory_location_transfer_item_returns.return_status', '=', '1')
            ->groupBy('wa_internal_requisitions.route', 'month')
            ->get();

        $salesCollection = collect($sales_subquery);
        $returnsCollection = collect($returns_subquery);

        $groupedSales = $salesCollection->groupBy('route')->map(function ($sales) {
            return $sales->groupBy('month');
        });

        $groupedReturns = $returnsCollection->groupBy('route')->map(function ($returns) {
            return $returns->groupBy('month');
        });

        $routeperfomance = $groupedSales->map(function ($sales, $route) use ($groupedReturns) {
            return $sales->map(function ($sale, $month) use ($groupedReturns, $route) {
                $totalSellingPrice = $sale->sum('total_selling_price');
                $totalReturns = $groupedReturns->get($route, collect())->get($month, collect())->sum('total_returns');
                $netAmount = $totalSellingPrice - $totalReturns;
                return number_format($netAmount, 2, '.', '');
            });
        });

        // Route perfomance end

        return view('admin.page.dashboards.sales_reports.index', compact(
            'title',
            'model',
            'pmodel',
            'totaldebtorbalances',
            'categories',
            'tonnage',
            'routeperfomance',
            'monthly_met_unmet_data',
            'monthlysales',
            'monthlypayments',
            'invoices',
            'currentmonthnetsales',
            'lastmonthnetsales',
            'previousmonthnetsales',
        ));
    }

    public function index(Request $request)
    {
        if (can('view', 'chairmans-dashboard')) {
            $title = $this->title;
            $model = $this->model;

            $branches = Restaurant::get();
            $branchId = $request->query('branch_id', $request->user()->restaurant_id);

            // $monthlySales = $this->getBranchSalesData($branchId, now()->subMonths(2)->startOfMonth())
            //     ->values();

            // $debtorBalances = WaDebtorTran::sum('amount');

            $pageRoute = $request->route()->uri;
            $detailedBranchPerformanceRoute = route('hq-dashboard.order-taking-summary');
            $detailedRouteSalesPerformanceRoute = route('salesman-performance-report', ['branch_id' => $branchId]);
    
            return view('admin.page.dashboards.sales_reports.index', compact(
                'title',
                'model',
                'branches',
                'branchId',
                // 'monthlySales',
                // 'debtorBalances',
                'pageRoute',
                'detailedBranchPerformanceRoute',
                'detailedRouteSalesPerformanceRoute'
            ));
        } else {
            return returnAccessDeniedPage();
        }
        
    }

    // API
    public function sales($branchId)
    {
        return response()->json($this->getBranchSalesData($branchId));
    }
    
    public function payments($branchId)
    {
        $payments = WaTenderEntry::query()
            ->where('trans_date', '>=', now()->startOfYear())
            ->where(function ($tenderEntries) use ($branchId) {
                $tenderEntries->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            })
            ->get()
            ->groupBy(fn ($tenderEntry) => $tenderEntry->trans_date->format('y-m'))
            ->map(fn ($monthTenderEntries) => round($monthTenderEntries->sum('amount'), 2));

        return response()->json($payments);
    }

    public function tonnage($branchId)
    {        
        $tonnagePerMonth = WaInternalRequisitionItem::query()
            ->whereHas('internalRequisition', fn ($internalRequisition) => $internalRequisition
            // ->whereIn('route_id', $this->getRouteIdsByBranch($branchId))
            ->where('restaurant_id', $branchId)
            )
            ->join('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->select(
                DB::raw('SUM((wa_internal_requisition_items.quantity * wa_inventory_items.net_weight) / 1000) as total_tonnage'),
                DB::raw('DATE_FORMAT(wa_internal_requisition_items.created_at, "%Y-%m") as month')
            )
            ->where('wa_internal_requisition_items.created_at', '>=', now()->startOfYear())
            ->latest('wa_internal_requisition_items.created_at', 'desc')
            ->groupBy('month')
            ->get();

        $data = [];

        foreach($tonnagePerMonth as $tonnage) {
            $data[$tonnage->month] = round($tonnage->total_tonnage, 2);
        }

        return response()->json($data);
    }

    public function returns($branchId)
    {
        $returnsPerMonth = DB::table('wa_inventory_location_transfer_item_returns')
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->join('wa_internal_requisitions', 'wa_internal_requisitions.requisition_no', '=', 'wa_inventory_location_transfers.transfer_no')
            ->whereIn('wa_internal_requisitions.route_id', $this->getRouteIdsByBranch($branchId))
            ->where('wa_inventory_location_transfer_item_returns.status', 'received')
            ->where('return_status', '1')
            ->where('wa_inventory_location_transfer_item_returns.created_at', '>=', now()->startOfYear())
            ->select(
                DB::raw("DATE_FORMAT(wa_inventory_location_transfer_item_returns.return_date, '%Y-%m') as month"),
                DB::raw('SUM(wa_inventory_location_transfer_item_returns.received_quantity * wa_inventory_location_transfer_items.selling_price) as total_returns')
            )
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at')
            ->groupBy('month')
            ->get();
        
        $data = [];

        foreach ($returnsPerMonth as $return) {
            $data[$return->month] = round($return->total_returns, 2);
        }

        return response()->json($data);
    }

    public function metUnmet($branchId)
    {
        $data = DB::table('salesman_shift_customers')
            ->join('salesman_shifts', 'salesman_shifts.id', '=', 'salesman_shift_customers.salesman_shift_id')
            ->select(
                DB::raw("DATE_FORMAT(salesman_shift_customers.created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as shops_count'),
                DB::raw('SUM(CASE WHEN salesman_shift_customers.visited = 1 THEN 1 ELSE 0 END) as total_met'),
                DB::raw('SUM(CASE WHEN salesman_shift_customers.visited = 0 THEN 1 ELSE 0 END) as total_unmet'),
                DB::raw('SUM(CASE WHEN salesman_shift_customers.salesman_shift_type = "onsite" THEN 1 ELSE 0 END) as onsite'),
                DB::raw('SUM(CASE WHEN salesman_shift_customers.salesman_shift_type = "offsite" THEN 1 ELSE 0 END) as offsite'),
                DB::raw('SUM(CASE WHEN salesman_shift_customers.visited = 1 AND order_taken = 0 THEN 1 ELSE 0 END) as met_without_orders'),
            )
            ->groupBy('month')
            ->whereIn('salesman_shifts.route_id', $this->getRouteIdsByBranch($branchId))
            ->get();

        return response()->json($data);
    }

    public function branchPerformance()
    {
        return response()->json($this->getBranchPerformanceData());
    }

    public function routeSalesPerformance($branchId)
    {
        $data = WaInternalRequisition::query()
            ->withSum('getRelatedItem as total_sales', 'total_cost_with_vat')
            // ->whereIn('route_id', $this->getRouteIdsByBranch($branchId))
            ->where('restaurant_id', $branchId)
            ->whereDate('created_at', '>=', now()->startOfYear())
            ->get()
            ->groupBy(fn ($requisition) => $requisition->route)
            ->map(function ($routeGroup) {
                return $routeGroup->groupBy(fn ($requisition) => $requisition->created_at->format('Y-m'))
                    ->map(fn ($monthRequisitions) => round($monthRequisitions->sum('total_sales'), 2));
            })
            ->sortKeys();

        return response()->json($data);
    }

    public function categoryPerformance(Request $request, $branchId)
    {
        $data = DB::table('wa_internal_requisition_items')
            ->join('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_internal_requisition_items.wa_inventory_item_id')
            ->leftJoin('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
            ->select(
                'wa_inventory_items.wa_inventory_category_id',
                'wa_inventory_categories.category_description',
                DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as total_cost'),
                DB::raw('SUM((wa_internal_requisition_items.quantity * wa_inventory_items.net_weight) / 1000) as total_tonnage')
            )
            ->whereBetween('wa_internal_requisition_items.created_at', [Carbon::parse("$request->year-$request->month")->startOfMonth(), Carbon::parse("$request->year-$request->month")->endOfMonth()->endOfDay()])
            ->groupBy(
                'wa_inventory_items.wa_inventory_category_id',
                'wa_inventory_categories.category_description'
            )
            ->whereIn('wa_internal_requisitions.route_id', $this->getRouteIdsByBranch($branchId))
            ->get();

        return response()->json($data);
    }
    public function getDebtorBalances($branchId){
        $data = WaDebtorTran::sum('amount');
        return response()->json($data);
    }

    public function getBranchSalesData($branchId, $fromDate = null)
    {
        $fromDate =  $fromDate ?: now()->startOfYear();

        return WaInternalRequisition::query()
            ->withSum('getRelatedItem as total_sales', 'total_cost_with_vat')
            // ->whereIn('route_id', $this->getRouteIdsByBranch($branchId))
            ->where('restaurant_id', $branchId)
            ->whereDate('created_at', '>=', $fromDate)
            ->get()
            ->groupBy(fn ($requisition) => $requisition->created_at->format('Y-m'))
            ->map(fn ($monthRequisitions) => round($monthRequisitions->sum('total_sales'), 2));
    }
    
    public function getSalesData($branchId){

        $monthlySales = $this->getBranchSalesData($branchId, now()->subMonths(2)->startOfMonth())
        ->values();
        return response()->json($monthlySales);

    }

    public function getRouteIdsByBranch($branchId)
    {
        return Route::where('restaurant_id', $branchId)->pluck('id');
    }

    public function getBranchPerformanceData()
    {
        $from = Carbon::now()->startOfMonth()->toDateString();
        $to =  Carbon::now()->toDateString();

        $route_sales_subquery = DB::table('wa_internal_requisition_items')
            ->select(
                DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as sub_query_route_sales'),
                'wa_internal_requisitions.restaurant_id',
            )
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->where('wa_internal_requisitions.requisition_no', 'like', 'INV%')
            ->whereBetween('wa_internal_requisitions.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('wa_internal_requisitions.restaurant_id');

        $pos_sales_subquery = DB::table('wa_internal_requisition_items')
            ->select(
                DB::raw('SUM(wa_internal_requisition_items.total_cost_with_vat) as sub_query_pos_sales'),
                'wa_internal_requisitions.restaurant_id',
            )
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->where('wa_internal_requisitions.requisition_no', 'like', 'CS%')
            ->whereBetween('wa_internal_requisitions.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('wa_internal_requisitions.restaurant_id');

        $tonnage_subquery = DB::table('wa_internal_requisition_items')
            ->select(
                DB::raw('SUM((wa_internal_requisition_items.quantity * wa_inventory_items.net_weight)/1000) as sub_query_tonnage'),
                'wa_internal_requisitions.restaurant_id',
            )
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', 'wa_internal_requisition_items.wa_inventory_item_id')
            ->whereBetween('wa_internal_requisitions.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('wa_internal_requisitions.restaurant_id');

        $routes_subquery = DB::table('wa_internal_requisitions')
            ->select(
                DB::raw('COUNT(DISTINCT wa_internal_requisitions.route_id) as sub_query_routes'),
                'wa_internal_requisitions.restaurant_id',
            )
            ->leftJoin('routes', 'routes.id', 'wa_internal_requisitions.route_id')
            ->where('routes.is_physical_route', 1)
            ->where('wa_internal_requisitions.requisition_no', 'like', 'INV%')
            ->whereBetween('wa_internal_requisitions.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('wa_internal_requisitions.restaurant_id');

        $customers_subquery = DB::table('salesman_shifts')
            ->select(
                DB::raw('COUNT(DISTINCT wa_route_customers.id) as sub_query_route_customers'),
                'routes.restaurant_id',
            )
            ->leftJoin('routes', 'routes.id', 'salesman_shifts.route_id')
            ->leftJoin('wa_route_customers', 'routes.id', 'wa_route_customers.route_id')
            ->where('routes.is_physical_route', 1)
            ->whereNull('wa_route_customers.deleted_at')
            ->whereBetween('salesman_shifts.created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->groupBy('routes.restaurant_id');

        $data = DB::table('restaurants')
            ->select(
                'restaurants.name',
                'route_sales_subquery.sub_query_route_sales as route_sales',
                'pos_sales_subquery.sub_query_pos_sales as pos_sales',
                'tonnage_subquery.sub_query_tonnage as tonnage',
                'routes_subquery.sub_query_routes as routes_with_orders',
                'customers_subquery.sub_query_route_customers as branch_customers',

                DB::raw("(SELECT COUNT(routes.id)
                        FROM routes 
                        WHERE routes.restaurant_id = restaurants.id
                        AND routes.is_physical_route = '1'
                    ) as total_routes"),
                DB::raw("(SELECT COUNT(delivery_centres.id)
                        FROM delivery_centres
                        LEFT JOIN routes ON routes.id = delivery_centres.route_id 
                        WHERE routes.restaurant_id = restaurants.id
                        AND routes.is_physical_route = '1'
                    ) as centers"),

            )
            ->leftJoinSub($route_sales_subquery, 'route_sales_subquery', 'route_sales_subquery.restaurant_id', '=', 'restaurants.id')
            ->leftJoinSub($pos_sales_subquery, 'pos_sales_subquery', 'pos_sales_subquery.restaurant_id', '=', 'restaurants.id')
            ->leftJoinSub($tonnage_subquery, 'tonnage_subquery', 'tonnage_subquery.restaurant_id', '=', 'restaurants.id')
            ->leftJoinSub($routes_subquery, 'routes_subquery', 'routes_subquery.restaurant_id', '=', 'restaurants.id')
            ->leftJoinSub($customers_subquery, 'customers_subquery', 'customers_subquery.restaurant_id', '=', 'restaurants.id')
            ->get()->map(function ($record){
                $record->total_sales = $record->route_sales + $record->pos_sales;
                return $record;
            })->sortByDesc('total_sales');
        return $data;
    }

    // API methods for Vue component
    public function getMonthlyReviews()
    {
        // Placeholder implementation
        return response()->json([]);
    }

    public function getTotalDebtorBalances()
    {
        $total = WaDebtorTran::sum('amount');
        return response()->json($total);
    }

    public function getMonthlySales()
    {
        $branchId = request()->query('branch_id', auth()->user()->restaurant_id);
        $sales = $this->getBranchSalesData($branchId, now()->subMonths(2)->startOfMonth())->values();
        return response()->json($sales);
    }

    public function getMonthlyPayments()
    {
        $branchId = request()->query('branch_id', auth()->user()->restaurant_id);
        $payments = $this->payments($branchId)->getData(true);
        return response()->json($payments);
    }

    public function getMonthlyTonnage()
    {
        $branchId = request()->query('branch_id', auth()->user()->restaurant_id);
        $tonnage = $this->tonnage($branchId)->getData(true);
        return response()->json($tonnage);
    }

    public function getMonthlyMetUnmetData()
    {
        $branchId = request()->query('branch_id', auth()->user()->restaurant_id);
        $data = $this->metUnmet($branchId)->getData(true);
        return response()->json($data);
    }

    public function getCategories()
    {
        $categories = WaInventoryCategory::all();
        return response()->json($categories);
    }

    public function getRoutePerformance()
    {
        $branchId = request()->query('branch_id', auth()->user()->restaurant_id);
        $data = $this->routeSalesPerformance($branchId)->getData(true);
        return response()->json($data);
    }

    // Additional API endpoints needed by Vue component
    public function getSalesStats($branchId)
    {
        $sales = $this->getBranchSalesData($branchId, now()->subMonths(2)->startOfMonth())->values();
        return response()->json($sales);
    }

}
