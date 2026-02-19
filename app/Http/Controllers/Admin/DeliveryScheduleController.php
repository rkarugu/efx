<?php

namespace App\Http\Controllers\Admin;

use PDF;
use Throwable;
use App\Vehicle;
use Carbon\Carbon;
use App\Model\User;
use App\Model\Route;
use App\Model\Setting;
use App\SalesmanShift;
use App\DeliverySchedule;
use App\Model\Restaurant;
use Illuminate\Support\Str;
use App\RouteDeviationAlert;
use Illuminate\Http\Request;
use App\DeliveryScheduleItem;
use App\LoadingSheetDispatch;
use App\Model\WaInventoryItem;
use App\Model\WaRouteCustomer;
use App\Model\WaUnitOfMeasure;
use App\Services\MappingService;
use App\DeliveryScheduleCustomer;
use App\LoadingSheetDispatchItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\SalesmanShiftStoreDispatch;
use Illuminate\Support\Facades\Log;
use App\Jobs\CreateDeliverySchedule;
use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use App\Model\WaInventoryLocationUom;
use App\Models\SaleCenterSmallPackDispatch;
use App\Models\SaleCenterSmallPackDispatchItems;
use App\Models\SaleCenterSmallPackItems;
use Illuminate\Http\RedirectResponse;
use App\SalesmanShiftStoreDispatchItem;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Validator;
use Illuminate\Console\Scheduling\Schedule;

class DeliveryScheduleController extends Controller
{
    protected $model;
    protected $base_route;
    protected $resource_folder;
    protected $base_title;

    public function __construct()
    {
        $this->model = 'delivery-schedules';
        $this->base_route = 'delivery-schedules';
        $this->resource_folder = 'admin.delivery_schedules';
        $this->base_title = 'Delivery Schedules';
    }

    public function index(Request $request)
    {
        $user = getLoggeduserProfile();
        $title = $this->base_title;
        $breadcum = [$title => route("$this->base_route.index"), 'Listing' => ''];
        $model = $this->model;
        $base_route = $this->base_route;
        $routes = Route::all();
        $branches = Restaurant::all();

        // Super Admin (role_id = 1) can see vehicles from ALL branches
        // Other users only see vehicles from their branch
        if ($user->role_id == 1) {
            $vehicles = Vehicle::whereHas('driver')->get();
        } else {
            $vehicles = Vehicle::whereHas('driver')
                ->where('branch_id', $user->restaurant_id)
                ->get();
        }
        
        foreach ($vehicles as $vehicle) {
            $activeSchedule = DeliverySchedule::latest()->active()->where('vehicle_id', $vehicle->id)->first();
            if ($activeSchedule) {
                $vehicle->isAvailable = 0;
            } else {
                $vehicle->isAvailable = 1;
            }
        }


        $sqlQuery = "
        SELECT 
            delivery_schedules.*,
            delivery_schedules.status AS delivery_status,
            delivery_schedules.id AS schedule_id,
            routes.*, 
            salesman_shifts.*,
            salesman_shifts.created_at AS shift_created_at,
            vehicles.*, 
            users.*, 
            (SELECT SUM(COALESCE(wii.net_weight * oi.quantity, 0) / 1000)
             FROM delivery_schedule_shifts dss
             LEFT JOIN wa_internal_requisitions AS wir ON dss.salesman_shift_id = wir.wa_shift_id
             LEFT JOIN wa_internal_requisition_items AS oi ON wir.id = oi.wa_internal_requisition_id
             LEFT JOIN wa_inventory_items AS wii ON oi.wa_inventory_item_id = wii.id
             WHERE dss.delivery_schedule_id = delivery_schedules.id
            ) AS shift_tonnage,
            (SELECT GROUP_CONCAT(
                CONCAT(r.route_name, '-', DATE_FORMAT(ss.start_time, '%Y/%m/%d'))
                SEPARATOR ', '
             )
             FROM delivery_schedule_shifts dss
             LEFT JOIN salesman_shifts ss ON dss.salesman_shift_id = ss.id
             LEFT JOIN routes r ON ss.route_id = r.id
             WHERE dss.delivery_schedule_id = delivery_schedules.id
            ) AS merged_shift_names,
            CONCAT('DS-', 
            LPAD(CAST(delivery_schedules.id AS CHAR), 6, '0')) AS delivery_number
        FROM 
            delivery_schedules
        LEFT JOIN 
            routes ON delivery_schedules.route_id = routes.id
        LEFT JOIN 
            salesman_shifts ON delivery_schedules.shift_id = salesman_shifts.id
        LEFT JOIN 
            vehicles ON delivery_schedules.vehicle_id = vehicles.id
        LEFT JOIN 
            users ON delivery_schedules.driver_id = users.id
       
        ";

        // $bindings = [];

        if ($request->route) {
            $sqlQuery .= " WHERE routes.id = ?";
            $bindings[] = $request->route;
        } else {
            $sqlQuery .= " WHERE 1 = 1";
            $bindings = [];
        }

        if ($request->from && $request->to) {
            $sqlQuery .= " AND salesman_shifts.created_at BETWEEN ? AND ?";
            $bindings[] = $request->from . " 00:00:00";
            $bindings[] = $request->to . " 23:59:59";
        } else {
            $sqlQuery .= " AND salesman_shifts.created_at BETWEEN ?  AND ?";
            $bindings[] = \Carbon\Carbon::now()->toDateString() . " 00:00:00";
            $bindings[] = \Carbon\Carbon::now()->toDateString() . " 23:59:59";
        }

        if ($request->branch) {
            $sqlQuery .= " AND routes.restaurant_id = ?";
            $bindings[] = $request->branch;
        }

        if ($user->role_id != 1) {
            $sqlQuery .= " AND  routes.restaurant_id = ?";
            $bindings[] = $user->restaurant_id;
        }


        $sqlQuery .= " GROUP BY delivery_schedules.id";
        $schedules = DB::select($sqlQuery, $bindings);

        return view("$this->resource_folder.index_new", compact('title', 'breadcum', 'base_route', 'model', 'routes', 'branches', 'vehicles', 'schedules', 'user'));
    }

    public function show($id)
    {
        $title = $this->base_title;
        $model = $this->model;
        $pmodule = "delivery-schedule";
        $permission = $this->mypermissionsforAModule();

        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$this->base_title => route("$this->base_route.index"), 'Details' => ''];
            $base_route = $this->base_route;

            $deliverySchedule = DeliverySchedule::with('shifts')->findOrFail($id);
            $shiftIds = $this->getScheduleShiftIds($deliverySchedule);

            $schedule = DB::table('delivery_schedules')
                ->select(
                    'delivery_schedules.id',
                    'delivery_schedules.actual_delivery_date',
                    'delivery_schedules.status',
                    'vehicles.license_plate_number as vehicle',
                    'drivers.name as driver',
                    DB::raw("(select GROUP_CONCAT(DISTINCT r.route_name SEPARATOR ', ') \n                        from salesman_shifts ss \n                        join routes r on ss.route_id = r.id \n                        where ss.id IN (" . implode(',', $shiftIds) . ")\n                    ) as route"),
                    'routes.start_lat',
                    'routes.start_lng',
                    DB::raw("(select GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') 
                        from salesman_shifts ss 
                        join users u on ss.salesman_id = u.id 
                        where ss.id IN (" . implode(',', $shiftIds) . ")
                    ) as salesman"),
                    DB::raw("(select coalesce(sum(items.net_weight * order_items.quantity),0) from wa_inventory_items as items 
                    join wa_internal_requisition_items as order_items on items.id = order_items.wa_inventory_item_id 
                    join wa_internal_requisitions as orders on order_items.wa_internal_requisition_id = orders.id 
                    where orders.wa_shift_id IN (" . implode(',', $shiftIds) . ")
                    ) as tonnage"),
                    DB::raw("(select coalesce(sum(order_items.total_cost_with_vat),0) from wa_internal_requisition_items as order_items 
                    join wa_internal_requisitions as orders on order_items.wa_internal_requisition_id = orders.id 
                    where orders.wa_shift_id IN (" . implode(',', $shiftIds) . ")
                    ) as sales"),
                    DB::raw("(select count(distinct(wa_route_customer_id)) from wa_internal_requisitions where wa_shift_id IN (" . implode(',', $shiftIds) . ")) as customers"),
                    DB::raw("(select count(distinct(wa_inventory_item_id)) from wa_internal_requisition_items as order_items 
                    join wa_internal_requisitions as orders on order_items.wa_internal_requisition_id = orders.id  
                    where wa_shift_id IN (" . implode(',', $shiftIds) . ")) as items"),
                )
                ->leftJoin('vehicles', 'vehicles.id', '=', 'delivery_schedules.vehicle_id')
                ->leftJoin('users as drivers', 'drivers.id', '=', 'delivery_schedules.driver_id')
                ->join('routes', 'routes.id', '=', 'delivery_schedules.route_id')
                ->where('delivery_schedules.id', $id)
                ->first();


            $schedule->delivery_number = DeliverySchedule::buildDeliveryNumber($schedule->id);
            $schedule->sales = manageAmountFormat($schedule->sales);
            $schedule->tonnage = round($schedule->tonnage / 1000, 1);
            $schedule->delivery_date = Carbon::parse($schedule->actual_delivery_date)->format('d-m-Y');
            $schedule->status = ucwords(str_replace('_', ' ', $schedule->status));
            $schedule->shift_count = count($shiftIds); // Add shift count for display

            $googleMapsApiKey = config('app.google_maps_api_key');
            return view("$this->resource_folder.show", compact('title', 'model', 'breadcum', 'base_route', 'googleMapsApiKey', 'schedule'));
        } else {
            return redirect()->back()->withErrors(['error' => pageRestrictedMessage()]);
        }
    }

    private function getScheduleShiftIds(DeliverySchedule $schedule): array
    {
        $pivotIds = $schedule->shifts()->pluck('salesman_shifts.id')->toArray();
        $ids = $pivotIds;
        if ($schedule->shift_id) {
            $ids[] = (int)$schedule->shift_id;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return [0];
        }

        return $ids;
    }


    public function getLoadingList(Request $request): JsonResponse
    {
        try {
            $schedule = DeliverySchedule::with('shifts')->find($request->id);

            $shiftIds = $this->getScheduleShiftIds($schedule);
            
            $list = DB::table('wa_internal_requisition_items as order_items')
                ->select(
                    'items.stock_id_code',
                    'items.title as item',
                    DB::raw("(SUM(order_items.quantity)) as quantity"),
                    DB::raw("(SUM(order_items.quantity * items.net_weight)) as tonnage"),
                    DB::raw("(CASE WHEN pack_sizes.id in (3) THEN 'CTN' WHEN pack_sizes.id in (6, 9, 17, 4, 10, 1) THEN 'SMALL PACK' ELSE 'BULK' END) as pack_size")
                )
                ->join('wa_internal_requisitions as orders', function ($join) use ($shiftIds) {
                    $join->on('orders.id', '=', 'order_items.wa_internal_requisition_id')
                        ->whereIn('orders.wa_shift_id', $shiftIds);
                })
                ->join('wa_inventory_items as items', 'items.id', '=', 'order_items.wa_inventory_item_id')
                ->join('pack_sizes', 'items.pack_size_id', '=', 'pack_sizes.id')
                ->groupBy('order_items.wa_inventory_item_id')
                ->orderBy('quantity', 'DESC')
                ->get()->map(function ($record) {
                    $record->tonnage = round($record->tonnage / 1000, 3);
                    return $record;
                });

            return $this->jsonify($list);
        } catch (\Throwable $th) {
            return $this->jsonify(['message' => $th->getMessage()], 500);
        }
    }

    public function getDeliveryReport(Request $request): JsonResponse
    {
        try {
            $schedule = DeliverySchedule::with('shifts')->find($request->id);

            $shiftIds = $this->getScheduleShiftIds($schedule);
            
            $list = DB::table('wa_internal_requisitions as orders')
                ->select(
                    'wa_route_customers.bussiness_name as customer',
                    'orders.requisition_no as order_no',
                    'orders.status as order_status',
                    'orders.delivery_date',
                    'delivery_schedule_customers.delivered_at',
                    DB::raw("(select SUM(total_cost_with_vat) from wa_internal_requisition_items where wa_internal_requisition_id = orders.id) as total"),
                )
                ->join('wa_route_customers', 'wa_route_customers.id', '=', 'orders.wa_route_customer_id')
                ->leftJoin('delivery_schedule_customers', function($join) use ($schedule) {
                    $join->on('delivery_schedule_customers.customer_id', '=', 'wa_route_customers.id')
                         ->where('delivery_schedule_customers.delivery_schedule_id', '=', $schedule->id);
                })
                ->orderBy('orders.delivery_date')
                ->whereIn('wa_shift_id', $shiftIds)
                ->get()->map(function ($record) {
                    $record->delivered = in_array($record->order_status, ['DELIVERED', 'COMPLETED']);
                    $record->total = manageAmountFormat($record->total);
                    $record->delivery_time = $record->delivered_at ? \Carbon\Carbon::parse($record->delivered_at)->format('H:i') : '-';

                    return $record;
                });

            return $this->jsonify($list);
        } catch (\Throwable $th) {
            return $this->jsonify(['message' => $th->getMessage()], 500);
        }
    }

    public function getPerformanceReport(Request $request): JsonResponse
    {
        try {
            $data = [];

            $deliverySchedule = DeliverySchedule::with('shifts')->find($request->id);
            $shiftIds = $this->getScheduleShiftIds($deliverySchedule);
            
            $schedule = DeliverySchedule::query()
                ->select(
                    'delivery_schedules.id',
                    'delivery_schedules.shift_id',
                    'routes.tonnage_target',
                    'routes.sales_target',
                    'routes.manual_distance_estimate',
                    'routes.manual_fuel_estimate',
                    'routes.ctn_target',
                    'routes.dzn_target',
                    DB::raw("(select count(*) from wa_route_customers where route_id = routes.id) as customers"),
                    DB::raw("(select count(distinct(wa_route_customer_id)) from wa_internal_requisitions where wa_shift_id IN (" . implode(',', $shiftIds) . ")) as met_with_order"),
                    DB::raw("(select count(distinct(customer_id)) from salesman_shift_issues where shift_id IN (" . implode(',', $shiftIds) . ") and status = 'verified') as met_without_order"),
                )
                ->join('routes', 'routes.id', '=', 'delivery_schedules.route_id')
                ->find($request->id);

            // Sales
            $grossSales = DB::table('wa_internal_requisition_items as order_items')
                ->join('wa_internal_requisitions as orders', function ($join) use ($shiftIds) {
                    $join->on('order_items.wa_internal_requisition_id', '=', 'orders.id')
                        ->whereIn('orders.wa_shift_id', $shiftIds);
                })->sum('total_cost_with_vat');

            // $returns = DB::table('wa_inventory_location_transfer_item_returns as returns')
            //     ->select(DB::raw("(SUM(received_quantity * order_items.selling_price)) as total"))
            //     ->join('wa_inventory_location_transfer_items as transfer_items', 'transfer_items.id', '=', 'returns.wa_inventory_location_transfer_item_id')
            //     ->join('wa_internal_requisition_items as order_items', 'transfer_items.wa_internal_requisition_item_id', '=', 'order_items.id')
            //     ->join('wa_internal_requisitions as orders', function ($join) use ($schedule) {
            //         $join->on('order_items.wa_internal_requisition_id', '=', 'orders.id')->where('orders.wa_shift_id', $schedule->shift_id);
            //     })
            //     ->get();

            $actualSales = $grossSales;
            $salesVariance =  $actualSales - $schedule->sales_target;
            $salesPerformance = round(($actualSales / $schedule->sales_target) * 100, 2);
            $data[] = [
                'parameter' => 'Sales',
                'target' => manageAmountFormat($schedule->sales_target),
                'actual' => manageAmountFormat($actualSales),
                'variance' => manageAmountFormat($salesVariance),
                'performance' => $salesPerformance,
            ];

            // Tonnage
            $actualTonnage =  DB::select("select coalesce(sum(items.net_weight * order_items.quantity),0) as tonnage from wa_inventory_items as items 
            join wa_internal_requisition_items as order_items on items.id = order_items.wa_inventory_item_id 
            join wa_internal_requisitions as orders on order_items.wa_internal_requisition_id = orders.id 
            where orders.wa_shift_id IN (" . implode(',', $shiftIds) . ")");
            $actualTonnage = round($actualTonnage[0]->tonnage / 1000, 1);
            $tonnageVariance =  round($actualTonnage - $schedule->tonnage_target, 1);
            $tonnagePerformance = round(($actualTonnage / $schedule->tonnage_target) * 100, 2);
            $data[] = [
                'parameter' => 'Tonnage',
                'target' => $schedule->tonnage_target,
                'actual' => $actualTonnage,
                'variance' => $tonnageVariance,
                'performance' => $tonnagePerformance,
            ];

            // CTNs
            $actualCtns =  DB::select("select count(*) as ctns from wa_inventory_items as items 
            join wa_internal_requisition_items as order_items on items.id = order_items.wa_inventory_item_id 
            join wa_internal_requisitions as orders on order_items.wa_internal_requisition_id = orders.id 
            join pack_sizes on items.pack_size_id = pack_sizes.id 
            where orders.wa_shift_id IN (" . implode(',', $shiftIds) . ") and pack_sizes.id = 3");
            $actualCtns = $actualCtns[0]->ctns;
            $ctnsVariance =  $actualCtns - $schedule->ctn_target;
            $ctnsPerformance = round(($actualCtns / $schedule->ctn_target) * 100, 2);
            $data[] = [
                'parameter' => 'CTNs',
                'target' => $schedule->ctn_target,
                'actual' => $actualCtns,
                'variance' => $ctnsVariance,
                'performance' => $ctnsPerformance,
            ];

            // DZNs
            $actualDzns =  DB::select("select count(*) as dzns from wa_inventory_items as items 
            join wa_internal_requisition_items as order_items on items.id = order_items.wa_inventory_item_id 
            join wa_internal_requisitions as orders on order_items.wa_internal_requisition_id = orders.id 
            join pack_sizes on items.pack_size_id = pack_sizes.id 
            where orders.wa_shift_id IN (" . implode(',', $shiftIds) . ") and pack_sizes.id in (6, 9, 17, 4, 10, 1)");
            $actualDzns = $actualDzns[0]->dzns;
            $dznsVariance =  $actualDzns - $schedule->dzn_target;
            $dznsPerformance = round(($actualDzns / $schedule->dzn_target) * 100, 2);
            $data[] = [
                'parameter' => 'DZNs',
                'target' => $schedule->dzn_target,
                'actual' => $actualDzns,
                'variance' => $dznsVariance,
                'performance' => $dznsPerformance,
            ];

            // Customers
            $actualCustomers = $schedule->met_with_order + $schedule->met_without_order;
            $data[] = [
                'parameter' => 'Customers',
                'target' => $schedule->customers,
                'actual' => $actualCustomers,
                'variance' => $actualCustomers - $schedule->customers,
                'performance' => round(($actualCustomers / $schedule->customers) * 100, 2)
            ];

            return $this->jsonify($data);
        } catch (\Throwable $th) {
            return $this->jsonify(['message' => $th->getMessage()], 500);
        }
    }

    public function getMainTripPolyline(Request $request): JsonResponse
    {
        try {
            $deliverySchedule = DeliverySchedule::with('shifts')->findOrFail($request->id);
            $shiftIds = $this->getScheduleShiftIds($deliverySchedule);

            $schedule = DB::table('delivery_schedules')
                ->select(
                    'delivery_schedules.actual_delivery_date',
                    'delivery_schedules.status',
                    'delivery_schedules.finish_time',
                    'delivery_schedules.shift_id',
                    'vehicles.license_plate_number as vehicle',
                    DB::raw("(select delivery_date from wa_internal_requisitions where wa_shift_id IN (" . implode(',', $shiftIds) . ") and status = 'DELIVERED' order by delivery_date desc limit 1) as last_customer_time"),
                    DB::raw("(select delivery_date from wa_internal_requisitions where wa_shift_id IN (" . implode(',', $shiftIds) . ") and status = 'DELIVERED' order by delivery_date limit 1) as first_customer_time"),
                )
                ->join('vehicles', 'delivery_schedules.vehicle_id', '=', 'vehicles.id')
                ->where('delivery_schedules.id', $request->id)
                ->first();

            if (!$schedule->finish_time) {
                $schedule->finish_time = Carbon::parse($schedule->actual_delivery_date)->toDateString() . ' 23:59:59';
            }

            $schedule->pre_trip_time = Carbon::parse($schedule->actual_delivery_date)->toDateString() . ' 05:00:00';

            $preTrip = DB::connection('telematics')
                ->table('vehicle_telematics')
                ->select('latitude as lat', 'longitude as lng')
                ->where('device_number', $schedule->vehicle)
                ->whereBetween('timestamp', [$schedule->pre_trip_time, $schedule->actual_delivery_date])
                ->get();

            $firstTrip = DB::connection('telematics')
                ->table('vehicle_telematics')
                ->select('latitude as lat', 'longitude as lng')
                ->where('device_number', $schedule->vehicle)
                ->whereBetween('timestamp', [$schedule->actual_delivery_date, $schedule->first_customer_time])
                ->get();

            $deliveryTrip = DB::connection('telematics')
                ->table('vehicle_telematics')
                ->select('latitude as lat', 'longitude as lng')
                ->where('device_number', $schedule->vehicle)
                ->whereBetween('timestamp', [$schedule->first_customer_time, $schedule->last_customer_time])
                ->get();

            // $returnTrip = DB::connection('telematics')
            //     ->table('vehicle_telematics')
            //     ->select('latitude as lat', 'longitude as lng')
            //     ->where('device_number', $schedule->vehicle)
            //     ->whereBetween('timestamp', [$schedule->last_customer_time, $schedule->finish_time])
            //     ->get();

            $schedule->pre_trip = $preTrip;
            $schedule->first_trip = $firstTrip;
            $schedule->delivery_trip = $deliveryTrip;
            // $schedule->return_trip = $returnTrip;

            return $this->jsonify($schedule);
        } catch (\Throwable $th) {
            return $this->jsonify(['message' => $th->getMessage()], 500);
        }
    }


    public function getFilteredctiveSchedules(Request $request): JsonResponse
    {
        try {

            $end_date = $request->end_date;
            $start_date = $request->start_date;

            $schedules = DeliverySchedule::with(['route', 'shift'])
                ->latest()
                ->orderByDesc('expected_delivery_date')
                //                ->whereNotIn('status', ['finished'])
                ->when($start_date, function ($query) use ($start_date) {
                    return $query->where('expected_delivery_date', '>=', $start_date);
                })
                ->when($end_date, function ($query) use ($end_date) {
                    return $query->where('expected_delivery_date', '<=', $end_date);
                })
                ->get()
                ->filter(function (DeliverySchedule $schedule) use ($request) {
                    if ($request->user_role_id != 1) {
                        return $schedule->route->restaurant_id == $request->user_restaurant_id;
                    }
                    return true;
                })
                ->map(function (DeliverySchedule $schedule) use ($request) {
                    $schedule->delivery_man = '-';
                    if ($schedule->vehicle_id) {
                        $vehicle = Vehicle::with('driver')->find($schedule->vehicle_id);
                        $schedule->delivery_man = "{$vehicle?->driver?->name} ($vehicle?->license_plate_number)";
                    }

                    $schedule->display_status = ucwords(str_replace('_', ' ', $schedule->status));
                    return $schedule;
                });
            return $this->jsonify(['data' => $schedules, 'check' => $request->all()], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function getActiveSchedules(Request $request): JsonResponse
    {
        try {
            $schedules = DeliverySchedule::with(['route', 'shift'])
                ->latest()
                ->orderByDesc('expected_delivery_date')
                ->get()
                ->filter(function (DeliverySchedule $schedule) use ($request) {
                    if ($request->user_role_id != 1) {
                        return $schedule->route->restaurant_id == $request->user_restaurant_id;
                    }
                    return true;
                })->map(function (DeliverySchedule $schedule) use ($request) {
                    $schedule->delivery_man = '-';
                    if ($schedule->vehicle_id) {
                        $vehicle = Vehicle::with('driver')->find($schedule->vehicle_id);
                        $schedule->delivery_man = "{$vehicle?->driver?->name} ($vehicle?->license_plate_number)";
                    }

                    $schedule->display_status = ucwords(str_replace('_', ' ', $schedule->status));
                    return $schedule;
                });


            return $this->jsonify(['data' => $schedules, 'check' => $request->all()], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function assignVehicle(Request $request): JsonResponse
    {
        try {
            $schedule = DeliverySchedule::find($request->schedule_id);
            $vehicle = Vehicle::with('driver')->find($request->vehicle_id);

            $schedule->update(['vehicle_id' => $vehicle->id, 'driver_id' => $vehicle->driver->id]);
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function unassignVehicle($id)
    {
        $schedule = DeliverySchedule::find($id);
        $schedule->vehicle_id = null;
        $schedule->driver_id = null;
        $schedule->save();
        Session::flash('success', 'Vehicle was unassigned successfully');
        return redirect()->route('delivery-schedules.index');
    }

    public function downloadDeliverySchedule($scheduleId)
    {


        $items = DeliveryScheduleItem::where('delivery_schedule_id', $scheduleId)->get();
        $company = Setting::where('slug', 'company-name')->first();
        $address = Setting::where('name', 'ADDRESS_2')->first();
        $location = Setting::where('slug', 'address-3')->first();
        $schedule = DeliverySchedule::with(['route' => function ($query) {
            return $query->select(['id', 'route_name']);
        }])->with('shift')->find($scheduleId);
        $title = "Delivery Schedule";
        $salesman = User::where('id', $schedule->shift->salesman_id)->first();
        $pdf = PDF::loadView('admin.delivery_schedules.delivery_loading_sheet', compact('title', 'items', 'company', 'address', 'location', 'schedule', 'salesman'));

        $report_name = 'delivery_schedules' . date('Y_m_d_H_i_A');
        //        return $pdf->stream();
        return $pdf->download($report_name . '.pdf');
    }

    public function getUnreceivedItems(Request $request): JsonResponse
    {
        $payload = [
            'status' => true,
            'message' => 'success',
            'data' => [],
        ];

        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'Token mismatch';
                return response()->json($payload, 422);
            }

            if (!$request->bin_location_id) {
                return $this->jsonify(['message' => 'Bin location ID field is required'], 422);
            }

            $schedule = DeliverySchedule::forDriver($user->id)->whereIn('status', ['consolidating', 'consolidated'])->first();
       

           
            // if($request->is_small_pack && $request->is_small_pack == "true"){

            //     $dispatch = DB::table('sale_center_small_pack_dispatches')->where('id',$request->bin_location_id)->first();
            //     $dispatchIds = DB::table('sale_center_small_pack_dispatches')->where('shift_id',$dispatch->shift_id)->get()->pluck('id');
            //     $payload['data'] = DB::table('sale_center_small_pack_dispatch_items')
            //         ->select(
            //         'sale_center_small_pack_dispatch_items.id',
            //         'wa_inventory_items.title as item_name',
            //         'sale_center_small_pack_dispatch_items.dispatched_quantity as item_count',
            //         'wa_unit_of_measures.title as bin',
            //         DB::raw('(sale_center_small_pack_dispatch_items.dispatched_quantity * wa_inventory_items.selling_price) as item_total')
            //         )
            //         ->leftJoin('sale_center_small_pack_dispatches', 'sale_center_small_pack_dispatches.id', 'sale_center_small_pack_dispatch_items.dispatch_id')
            //         ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', 'sale_center_small_pack_dispatch_items.wa_inventory_item_id')
            //         ->leftJoin('wa_unit_of_measures', 'sale_center_small_pack_dispatch_items.bin_id', '=', 'wa_unit_of_measures.id')
            //         // ->where('sale_center_small_pack_dispatches.id', $request->bin_location_id)
            //         ->whereIn('sale_center_small_pack_dispatches.id', $dispatchIds)
            //         ->get()
            //         ->map(function ($record) {
            //             if (!$record->bin) {
            //                 $record->bin = 'Unassigned';
            //             }
            //             $record->item_name = "($record->bin) $record->item_name ";
            //             $record->item_total = format_amount_with_currency($record->item_total);
            //             return $record;
            //         });

            // }else{
                $shiftIds = $schedule->shifts()->pluck('salesman_shifts.id')->toArray();
                if (empty($shiftIds) && $schedule->shift_id) {
                    $shiftIds = [(int)$schedule->shift_id];
                }

                $dispatch = SalesmanShiftStoreDispatch::where('bin_location_id', $request->bin_location_id)
                    ->whereIn('shift_id', $shiftIds)
                    ->orderByDesc('id')
                    ->first();

                $salesmanShift = $dispatch ? SalesmanShift::with('salesman')->find($dispatch->shift_id) : null;
                $salesman = $salesmanShift->salesman;

                if (!$dispatch) {
                    $payload['data'] = [];
                    return response()->json($payload);
                }

                $payload['data'] = DB::table('salesman_shift_store_dispatch_items')->where('dispatch_id', $dispatch->id)
                ->select(
                    'salesman_shift_store_dispatch_items.id',
                    'wa_inventory_items.title as item_name',
                    'salesman_shift_store_dispatch_items.dispatched_quantity as item_count',
                    'wa_unit_of_measures.title as bin',
                    DB::raw('(salesman_shift_store_dispatch_items.dispatched_quantity * wa_inventory_items.selling_price) as item_total')
                )
                ->leftJoin('wa_inventory_items', 'salesman_shift_store_dispatch_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->leftJoin('wa_inventory_location_uom', function ($join) use ($salesman) {
                    $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')->where('wa_inventory_location_uom.location_id', $salesman->wa_location_and_store_id);
                })
                ->leftJoin('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
                ->get()
                ->map(function ($record) {
                    if (!$record->bin) {
                        $record->bin = 'Unassigned';
                    }

                    $record->item_name = "($record->bin) $record->item_name ";
                    $record->item_total = format_amount_with_currency($record->item_total);
                    return $record;
                });

            // }
           
            return response()->json($payload);
        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function receiveItems(Request $request): JsonResponse
    {
        $payload = [
            'status' => true,
            'message' => 'success',
            'data' => [],
        ];

        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'Token mismatch';
                return response()->json($payload, 422);
            }

            $validator = Validator::make($request->all(), [
                'item_id' => 'array',
                // 'item_id.*' => 'required|exists:salesman_shift_store_dispatch_items,id',
                'item_id.*' => 'required',

            ]);

            if ($validator->fails()) {
                $error = $this->validationHandle($validator->messages());
                return response()->json(['status' => false, 'message' => $error], 422);
            }
            // if($request->is_small_pack && $request->is_small_pack == "true"){
            //     foreach($request->item_id as $item_id){
            //         $centerDispatchItem = SaleCenterSmallPackDispatchItems::find($item_id);
            //         $centerDispatchItem->is_received_by_driver  = true;
            //         $centerDispatchItem->save();
            //         $centerDispatch = SaleCenterSmallPackDispatch::find($centerDispatchItem->dispatch_id);
    
            //         $dispatch = SalesmanShiftStoreDispatch::where('shift_id', $centerDispatch->shift_id)->where('bin_location_id', $centerDispatchItem->bin_id)->first();
            //         $dispatchItem = SalesmanShiftStoreDispatchItem::where('dispatch_id', $dispatch->id)->where('wa_inventory_item_id', $centerDispatchItem->wa_inventory_item_id)->first();
            //         $dispatch->update(['received' => true]);

            //     }
               
            // }else{
                $dispatchItem = SalesmanShiftStoreDispatchItem::find($request->item_id[0]);
                $dispatch = SalesmanShiftStoreDispatch::find($dispatchItem->dispatch_id);
                $dispatch->update(['received' => true]);

            // }

            DB::commit();

            $undispatchedLoadingSheets = SalesmanShiftStoreDispatch::where('shift_id', $dispatch->shift_id)->where('received', false)->count();
            if ($undispatchedLoadingSheets == 0) {
                $delivery = DeliverySchedule::latest()->forShiftId((int)$dispatch->shift_id)->first();
                $delivery?->update(['status' => 'loaded']);
            }

            return response()->json($payload);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // TODO: Listen to payment confirmation
    public function promptDeliveryCompletion(Request $request): JsonResponse
    {
        $payload = [
            'message' => 'Prompt received successfully.',
            'order_status' => 'PROCESSING',
            'status' => true
        ];

        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'You have provided an invalid API token';
                return $this->jsonify($payload, 422);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
            ]);

            if ($validator->fails()) {
                $payload['status'] = false;
                $payload['message'] = $this->validationHandle($validator->messages());
                return $this->jsonify($payload, 422);
            }

            $customer = WaRouteCustomer::select('id', 'name', 'phone')->find($request->customer_id);
            $currentDeliveryShift = DeliverySchedule::with('customers')->latest()->active()->forDriver($user->id)->first();
            $customerDeliveryRecord = $currentDeliveryShift->customers()->where('customer_id', $customer->id)->first();
            $customerDeliveryRecord->update(['delivery_code_status' => 'sent']);

            $customerOrderIds = explode(',', $customerDeliveryRecord->order_id);
            $customerOrderIds = collect($customerOrderIds)->map(function (string $id) {
                return (int)$id;
            });

            WaInternalRequisition::whereIn('id', $customerOrderIds)->update(['status' => 'PROCESSING']);

            //            $customerMessage = "Hello $customer->name,\nThank you for shopping with us. Your delivery confirmation code is $customerDeliveryRecord->delivery_code";
            // sendMessage($customer->phone , $customerMessage);

            DB::commit();
            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // TODO: Check for payment success
    public function resendDeliveryCode(Request $request): JsonResponse
    {
        $payload = [
            'message' => 'Delivery code resent successfully.',
            'status' => true
        ];

        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'You have provided an invalid API token';
                return $this->jsonify($payload, 422);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
            ]);

            if ($validator->fails()) {
                $payload['status'] = false;
                $payload['message'] = $this->validationHandle($validator->messages());
                return $this->jsonify($payload, 422);
            }

            $customer = WaRouteCustomer::select('id', 'name', 'phone')->find($request->customer_id);
            $currentDeliveryShift = DeliverySchedule::with('customers')->latest()->active()->forDriver($user->id)->first();
            $customerDeliveryRecord = $currentDeliveryShift->customers()->where('customer_id', $customer->id)->first();

            $customerMessage = "Hello $customer->name,\nThank you for shopping with us. Your delivery confirmation code is $customerDeliveryRecord->delivery_code";
            // send_sms(substr($customer->phone, 1), $customerMessage);
            sendMessage([$customer->phone], $customerMessage);

            DB::commit();
            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'A server error was encountered.'], 500);
        }
    }

    public function verifyDeliveryCode(Request $request): JsonResponse
    {
        $payload = [
            'message' => 'Delivery code verified successfully.',
            'status' => true
        ];

        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'You have provided an invalid API token';
                return $this->jsonify($payload, 422);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'delivery_code' => 'required',
            ]);

            if ($validator->fails()) {
                $payload['status'] = false;
                $payload['message'] = $this->validationHandle($validator->messages());
                return $this->jsonify($payload, 422);
            }

            $customer = WaRouteCustomer::select('id', 'name', 'phone')->find($request->customer_id);
            $currentDeliveryShift = DeliverySchedule::with('customers')->latest()->active()->forDriver($user->id)->first();
            $customerDeliveryRecord = $currentDeliveryShift->customers()->where('customer_id', $customer->id)->first();
            if ($request->delivery_code != $customerDeliveryRecord->delivery_code) {
                $payload['status'] = false;
                $payload['message'] = 'The provided delivery code is incorrect.';
                return $this->jsonify($payload, 422);
            }

            $customerDeliveryRecord->update(['delivery_code_status' => 'approved']);

            $customerOrderIds = explode(',', $customerDeliveryRecord->order_id);
            $customerOrderIds = collect($customerOrderIds)->map(function (string $id) {
                return (int)$id;
            });

            WaInternalRequisition::whereIn('id', $customerOrderIds)->update(['status' => 'COMPLETED']);

            DB::commit();
            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'A server error was encountered.'], 500);
        }
    }

    public function completeDelivery(Request $request): JsonResponse
    {
        $payload = [
            'message' => 'Delivery completed successfully.',
            'order_status' => 'COMPLETED',
            'status' => true
        ];

        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'You have provided an invalid API token';
                return $this->jsonify($payload, 422);
            }

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required',
                'payment_reference' => 'required',
            ]);

            if ($validator->fails()) {
                $payload['status'] = false;
                $payload['message'] = $this->validationHandle($validator->messages());
                return $this->jsonify($payload, 422);
            }

            $customer = WaRouteCustomer::select('id', 'name', 'phone')->find($request->customer_id);
            $currentDeliveryShift = DeliverySchedule::with('customers')->latest()->active()->forDriver($user->id)->first();
            $customerDeliveryRecord = $currentDeliveryShift->customers()->where('customer_id', $customer->id)->first();
            $customerDeliveryRecord->update(['delivery_code_status' => 'approved']);

            $customerOrderIds = explode(',', $customerDeliveryRecord->order_id);
            $customerOrderIds = collect($customerOrderIds)->map(function (string $id) {
                return (int)$id;
            });

            WaInternalRequisition::whereIn('id', $customerOrderIds)->update(['status' => 'COMPLETED']);

            DB::commit();
            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
            //            return response()->json(['status' => false, 'message' => 'A server error was encountered.'], 500);
        }
    }

    public function checkGeoFence(Request $request): JsonResponse
    {
        try {
            $route = Route::with(['polylines'])->find($request->route_id);
            $latLngs = [];
            foreach ($route->polylines as $polyline) {
                $latLngs = [...$latLngs, ...json_decode($polyline->lat_lngs, true)];
            }

            $objectIsOutsideFence = true;
            $sendAlert = true;
            $alertDistance = 0;
            foreach ($latLngs as $latLngPair) {
                $distanceToObject = MappingService::getTheaterDistanceBetweenTwoPoints($latLngPair[0], $latLngPair[1], $request->lat, $request->lng);
                $alertDistance = $distanceToObject;
                if ($distanceToObject < 200) {
                    $objectIsOutsideFence = false;
                    break;
                }
            }

            if ($objectIsOutsideFence) {
                $vehicle = Vehicle::with('driver')->find($request->vehicle_id);
                $schedule = DeliverySchedule::find($request->schedule_id);

                /**
                 * Before switching off the vehicle, check whether it's been already switched off for this delivery.
                 * If not, check whether there has been ample time after switching it back on to get back to route
                 */
                $lastDeviationAlert = RouteDeviationAlert::latest()->where('delivery_id', $schedule->id)->where('vehicle_id', $vehicle->id)->first();
                if ($lastDeviationAlert) {
                    if ($lastDeviationAlert->status == 'switched_off') {
                        $sendAlert = false;
                    } else {
                        $timeSinceSwitchOn = Carbon::now()->diffInMinutes(Carbon::parse($lastDeviationAlert->switch_on_time));
                        if ($timeSinceSwitchOn < 10) {
                            $sendAlert = false;
                        }
                    }
                }

                if ($sendAlert && ($schedule->status == 'in_progress')) {
                    $switchOffMessage = "  setparam 11702:1";
                    //                    send_sms(substr('0769513434', 1), $switchOffMessage);
                    // sendMessage(['0769513434'] , $switchOffMessage);

                    RouteDeviationAlert::create([
                        'delivery_id' => $schedule->id,
                        'vehicle_id' => $vehicle->id,
                        'route_id' => $route->id,
                        'driver_id' => $vehicle->driver->id,
                        'status' => 'switched_off'
                    ]);

                    $message = "Hello Administrator, $vehicle->license_plate_number has deviated from its delivery route and has been switched off.";
                    $message .= "\nDriver: {$vehicle->driver->name}";
                    $message .= "\nRoute: $route->route_name";
                    $message .= "\nDelivery Schedule: $schedule->delivery_number";

                    // send_sms(substr('0790544563', 1), $message);
                    sendMessage($message, '0790544563',);
                }
            }

            return $this->jsonify(['message' => 'success', 'position' => $objectIsOutsideFence ? 'outside' : 'inside', 'alert' => $sendAlert ? 'yes' : 'no', 'distance' => $alertDistance], 200);
        } catch (\Throwable $e) {
            // send_sms(substr('0790544563', 1), "Deviation alert failed: {$e->getMessage()}");
            sendMessage("Deviation alert failed: {$e->getMessage()}", '0790544563');
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function downloadGatePass($scheduleId)
    {
        $items = DeliveryScheduleItem::where('delivery_schedule_id', $scheduleId)->get();
        $company = Setting::where('slug', 'company-name')->first();
        $address = Setting::where('name', 'ADDRESS_2')->first();
        $location = Setting::where('slug', 'address-3')->first();
        $schedule = DeliverySchedule::with(['route' => function ($query) {
            return $query->select(['id', 'route_name']);
        }])->with('shift', 'driver', 'vehicle')->find($scheduleId);
        $schedule->issued_gate_pass = true;
        $schedule->has_gatepass = true;
        $schedule->save();
        $title = "Delivery Schedule";
        $salesman = User::where('id', $schedule->shift->salesman_id)->first();
        $pdf = PDF::loadView('admin.delivery_schedules.gate_pass', compact('title', 'items', 'company', 'address', 'location', 'schedule', 'salesman'));
        $report_name = 'GATE PASS' . date('Y_m_d_H_i_A');
        return $pdf->download($report_name . '.pdf');
    }

    public function initiateGatePass($scheduleId)
    {
        $schedule = DeliverySchedule::with(['route' => function ($query) {
            return $query->select(['id', 'route_name']);
        }])->with('shift', 'driver', 'vehicle')->find($scheduleId);
        $schedule->issued_gate_pass = true;
        $schedule->has_gatepass = true;
        $schedule->save();
        Session::flash('success', 'Gate Pass Initiated Successfully');
        return redirect()->route('delivery-schedules.index');
    }

    public function createGatePass(Request $request): RedirectResponse
    {
        try {
            $delivery = DeliverySchedule::find($request->delivery_id);
            $delivery->update(['gate_pass_status' => 'initiated']);

            Session::flash('success', 'Gate pass created successfully');
            return redirect()->back();
        } catch (Throwable $e) {
            Session::flash('warning', $e->getMessage());
            return redirect()->back();
        }
    }
    public function endSchedule(Request $request): RedirectResponse
    {
        try {
            $delivery = DeliverySchedule::find($request->delivery_id);
            $delivery->update(['status' => 'finished']);

            Session::flash('success', 'Schedule Ended Successfully');
            return redirect()->back();
        } catch (Throwable $e) {
            Session::flash('warning', $e->getMessage());
            return redirect()->back();
        }
    }

    public function getPendingGatePassVerifications(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return $this->jsonify(['message' => getTokenHasNoUserMessage(), 'status' => false], 422);
            }

            $pendingVerifications = DB::table('delivery_schedules')
                ->where('delivery_schedules.status', 'loaded')
                ->where('delivery_schedules.gate_pass_status', 'initiated')
                ->select(
                    'delivery_schedules.shift_id as id',
                    'delivery_schedules.id as delivery_id',
                    'routes.route_name as route',
                    'vehicles.license_plate_number as vehicle',
                    'users.name as driver',
                )
                ->join('routes', 'delivery_schedules.route_id', '=', 'routes.id')
                ->join('vehicles', function (JoinClause $join) use ($request) {
                    $query = $join->on('delivery_schedules.vehicle_id', '=', 'vehicles.id');
                    if ($request->search_query) {
                        $query = $query->where('license_plate_number', 'like', "%$request->search_query%");
                    }
                })
                ->join('users', 'delivery_schedules.driver_id', '=', 'users.id')
                ->orderBy('delivery_schedules.id')
                ->cursorPaginate(14);

            return $this->jsonify($pendingVerifications, 200);
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function validateGatePass(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return $this->jsonify(['message' => getTokenHasNoUserMessage(), 'status' => false], 422);
            }

            if (!$request->gate_pass_code) {
                return $this->jsonify(['message' => 'Gate pass code is required'], 422);
            }

            if (!$request->delivery_id) {
                return $this->jsonify(['message' => 'Delivery ID is required'], 422);
            }

            $deliverySchedule = DeliverySchedule::with('route')->find($request->delivery_id);
            if (!$deliverySchedule) {
                return $this->jsonify(['message' => 'The provided delivery ID is invalid'], 422);
            }

            $gatePassCode = "$deliverySchedule->delivery_number-{$deliverySchedule->route->route_name}";
            if ($gatePassCode != $request->gate_pass_code) {
                return $this->jsonify(['message' => 'The provided gate pass code is invalid'], 422);
            }

            $deliverySchedule->update(['gate_pass_status' => 'verified']);

            return $this->jsonify(['message' => 'Gate pass validated successfully'], 200);
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function deliverySchedulesList()
    {
        // $deliverySchedules = DeliverySchedule::latest()->get();
        $deliverySchedules = DB::table('delivery_schedules')
            ->join('routes', 'route_id', '=', 'routes.id')
            ->select('delivery_schedules.id', 'routes.id as route_id', 'route_name', 'delivery_schedules.created_at')
            ->latest()
            ->get()
            ->map(function ($deliverSchedule) {
                $deliverSchedule->delivery_number = "DS-" . Str::padLeft($deliverSchedule->id, 6, '0');
                $deliverSchedule->created_at = Carbon::parse($deliverSchedule->created_at)->format('Y-m-d');

                return $deliverSchedule;
            });

        return $deliverySchedules;
    }

    /**
     * Add shifts to a delivery schedule
     */
    public function addShifts(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'shift_ids' => 'required|array',
                'shift_ids.*' => 'required|exists:salesman_shifts,id',
            ]);

            if ($validator->fails()) {
                return $this->jsonify(['errors' => $validator->errors()], 422);
            }

            $schedule = null;
            DB::transaction(function () use ($id, $request, &$schedule) {
                $schedule = DeliverySchedule::whereKey($id)->lockForUpdate()->firstOrFail();

                if (!in_array($schedule->status, ['consolidating', 'consolidated'])) {
                    throw new \RuntimeException('Cannot add shifts to a delivery schedule that is already in progress or completed');
                }

                $schedule->shifts()->syncWithoutDetaching($request->shift_ids);
                $this->recalculateDeliverySchedule($schedule);
            });

            return $this->jsonify([
                'message' => 'Shifts added successfully',
                'schedule' => $schedule->load('shifts')
            ], 200);
        } catch (\Throwable $e) {
            $status = $e instanceof \RuntimeException ? 422 : 500;
            return $this->jsonify(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * Remove a shift from a delivery schedule
     */
    public function removeShift(Request $request, $id, $shiftId): JsonResponse
    {
        try {
            $schedule = null;
            DB::transaction(function () use ($id, $shiftId, &$schedule) {
                $schedule = DeliverySchedule::whereKey($id)->lockForUpdate()->firstOrFail();

                if (!in_array($schedule->status, ['consolidating', 'consolidated'])) {
                    throw new \RuntimeException('Cannot remove shifts from a delivery schedule that is already in progress or completed');
                }

                if ($schedule->shifts()->count() <= 1) {
                    throw new \RuntimeException('Cannot remove the last shift from a delivery schedule');
                }

                $schedule->shifts()->detach($shiftId);

                if ((int)$schedule->shift_id === (int)$shiftId) {
                    $newPrimaryShift = $schedule->shifts()->first();
                    if (!$newPrimaryShift) {
                        throw new \RuntimeException('Cannot determine new primary shift');
                    }
                    $schedule->update(['shift_id' => $newPrimaryShift->id]);
                }

                $this->recalculateDeliverySchedule($schedule);
            });

            return $this->jsonify([
                'message' => 'Shift removed successfully',
                'schedule' => $schedule->load('shifts')
            ], 200);
        } catch (\Throwable $e) {
            $status = $e instanceof \RuntimeException ? 422 : 500;
            return $this->jsonify(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * Get all shifts in a delivery schedule
     */
    public function getShifts($id): JsonResponse
    {
        try {
            $schedule = DeliverySchedule::with(['shifts.salesman', 'shifts.orders'])->findOrFail($id);
            
            $shifts = $schedule->shifts->map(function ($shift) {
                // Format start_time - handle both string and Carbon object
                $startTime = 'N/A';
                if ($shift->start_time) {
                    try {
                        $startTime = $shift->start_time instanceof \Carbon\Carbon 
                            ? $shift->start_time->format('Y-m-d H:i') 
                            : \Carbon\Carbon::parse($shift->start_time)->format('Y-m-d H:i');
                    } catch (\Exception $e) {
                        $startTime = $shift->start_time;
                    }
                }
                
                return [
                    'id' => $shift->id,
                    'shift_name' => $shift->shift_id ?? "Shift {$shift->id}",
                    'salesman' => $shift->salesman->name ?? 'N/A',
                    'route' => $shift->route ?? 'N/A',
                    'start_time' => $startTime,
                    'status' => $shift->status,
                    'orders_count' => $shift->orders->count(),
                    'total' => $shift->shift_total ?? 0,
                ];
            });

            return $this->jsonify([
                'schedule_id' => $schedule->id,
                'delivery_number' => $schedule->delivery_number,
                'shifts' => $shifts
            ], 200);
        } catch (\Throwable $e) {
            Log::error("Error loading shifts for delivery schedule {$id}: " . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalculate delivery schedule items and customers based on assigned shifts
     */
    private function recalculateDeliverySchedule(DeliverySchedule $schedule): void
    {
        $shiftIds = $schedule->shifts()->pluck('salesman_shifts.id')->toArray();
        if ($schedule->shift_id) {
            $shiftIds[] = (int)$schedule->shift_id;
        }
        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));

        if (empty($shiftIds)) {
            return;
        }

        $customersWithOrders = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)
            ->whereNotNull('wa_route_customer_id')
            ->distinct()
            ->pluck('wa_route_customer_id');

        $existingCustomers = $schedule->customers()->get()->keyBy('customer_id');
        $customerIdsToKeep = [];

        foreach ($customersWithOrders as $customerId) {
            $customerIdsToKeep[] = (int)$customerId;

            $customerOrderIds = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)
                ->where('wa_route_customer_id', $customerId)
                ->pluck('id')
                ->toArray();

            $existing = $existingCustomers->get((int)$customerId);
            if ($existing) {
                $existing->order_id = implode(',', $customerOrderIds);
                $existing->save();
                continue;
            }

            $schedule->customers()->create([
                'customer_id' => $customerId,
                'delivery_code' => random_int(100000, 999999),
                'order_id' => implode(',', $customerOrderIds)
            ]);
        }

        $customerIdsToDelete = $existingCustomers->keys()->map(fn ($k) => (int)$k)->diff($customerIdsToKeep);
        if ($customerIdsToDelete->isNotEmpty()) {
            $schedule->customers()->whereIn('customer_id', $customerIdsToDelete->values()->all())->delete();
        }

        $shiftItems = DB::table('wa_internal_requisition_items')
            ->join('wa_internal_requisitions', function (JoinClause $join) use ($shiftIds) {
                $join->on('wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
                    ->whereIn('wa_internal_requisitions.wa_shift_id', $shiftIds);
            })
            ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->groupBy('wa_internal_requisition_items.wa_inventory_item_id')
            ->select(
                'wa_inventory_items.id as item_id',
                DB::raw('SUM(`quantity`) as total_quantity')
            )
            ->get();

        $existingItems = $schedule->items()->get()->keyBy('wa_inventory_item_id');
        $itemIdsToKeep = [];

        foreach ($shiftItems as $shiftItem) {
            $itemIdsToKeep[] = (int)$shiftItem->item_id;

            $existing = $existingItems->get((int)$shiftItem->item_id);
            if ($existing) {
                $existing->total_quantity = $shiftItem->total_quantity;
                if ($existing->received_quantity > $existing->total_quantity) {
                    $existing->received_quantity = $existing->total_quantity;
                }
                $existing->save();
                continue;
            }

            $schedule->items()->create([
                'wa_inventory_item_id' => $shiftItem->item_id,
                'total_quantity' => $shiftItem->total_quantity,
                'received_quantity' => 0,
            ]);
        }

        $itemIdsToDelete = $existingItems->keys()->map(fn ($k) => (int)$k)->diff($itemIdsToKeep);
        if ($itemIdsToDelete->isNotEmpty()) {
            $schedule->items()->whereIn('wa_inventory_item_id', $itemIdsToDelete->values()->all())->delete();
        }

        Log::info("Recalculated delivery schedule {$schedule->id} with " . count($shiftIds) . " shifts");
    }

    private function mergeScheduleCustomersAndItems(DeliverySchedule $targetSchedule, DeliverySchedule $sourceSchedule): void
    {
        static $customerColumns = null;
        static $itemColumns = null;

        if ($customerColumns === null) {
            $customerColumns = [];
            foreach ([
                'delivery_code_status',
                'delivered_at',
                'delivery_prompted_at',
                'collection_amount',
                'payment_method',
                'payment_skipped',
                'is_skipped',
                'skip_reason',
                'skipped_at',
                'delivery_notes',
                'delivery_location_lat',
                'delivery_location_lng',
                'visited',
            ] as $col) {
                if (Schema::hasColumn('delivery_schedule_customers', $col)) {
                    $customerColumns[] = $col;
                }
            }
        }

        if ($itemColumns === null) {
            $itemColumns = [];
            foreach (['received_quantity'] as $col) {
                if (Schema::hasColumn('delivery_schedule_items', $col)) {
                    $itemColumns[] = $col;
                }
            }
        }

        $statusRank = [
            'pending' => 0,
            'sent' => 1,
            'approved' => 2,
        ];

        $targetCustomers = $targetSchedule->customers()->get()->keyBy('customer_id');
        $sourceCustomers = $sourceSchedule->customers()->get();

        foreach ($sourceCustomers as $sourceCustomer) {
            $targetCustomer = $targetCustomers->get((int)$sourceCustomer->customer_id);
            if ($targetCustomer) {
                if (in_array('delivery_code_status', $customerColumns, true)) {
                    $sourceRank = $statusRank[$sourceCustomer->delivery_code_status] ?? 0;
                    $targetRank = $statusRank[$targetCustomer->delivery_code_status] ?? 0;
                    if ($sourceRank > $targetRank) {
                        $targetCustomer->delivery_code_status = $sourceCustomer->delivery_code_status;
                    }
                }

                if (in_array('delivered_at', $customerColumns, true) && !$targetCustomer->delivered_at && $sourceCustomer->delivered_at) {
                    $targetCustomer->delivered_at = $sourceCustomer->delivered_at;
                }
                if (in_array('delivery_prompted_at', $customerColumns, true) && !$targetCustomer->delivery_prompted_at && $sourceCustomer->delivery_prompted_at) {
                    $targetCustomer->delivery_prompted_at = $sourceCustomer->delivery_prompted_at;
                }

                foreach (['collection_amount', 'payment_method', 'skip_reason', 'skipped_at', 'delivery_notes', 'delivery_location_lat', 'delivery_location_lng'] as $field) {
                    if (!in_array($field, $customerColumns, true)) {
                        continue;
                    }
                    if (!isset($targetCustomer->{$field}) && isset($sourceCustomer->{$field})) {
                        $targetCustomer->{$field} = $sourceCustomer->{$field};
                    }
                }

                foreach (['visited', 'payment_skipped', 'is_skipped'] as $field) {
                    if (!in_array($field, $customerColumns, true)) {
                        continue;
                    }
                    $targetCustomer->{$field} = (bool)$targetCustomer->{$field} || (bool)$sourceCustomer->{$field};
                }

                $targetCustomer->save();
                $sourceCustomer->delete();
                continue;
            }

            $sourceCustomer->delivery_schedule_id = $targetSchedule->id;
            $sourceCustomer->save();
            $targetCustomers->put((int)$sourceCustomer->customer_id, $sourceCustomer);
        }

        $targetItems = $targetSchedule->items()->get()->keyBy('wa_inventory_item_id');
        $sourceItems = $sourceSchedule->items()->get();
        foreach ($sourceItems as $sourceItem) {
            $targetItem = $targetItems->get((int)$sourceItem->wa_inventory_item_id);
            if ($targetItem) {
                if (in_array('received_quantity', $itemColumns, true)) {
                    $targetItem->received_quantity = (float)$targetItem->received_quantity + (float)$sourceItem->received_quantity;
                }
                $targetItem->save();
                $sourceItem->delete();
                continue;
            }

            $sourceItem->delivery_schedule_id = $targetSchedule->id;
            $sourceItem->save();
            $targetItems->put((int)$sourceItem->wa_inventory_item_id, $sourceItem);
        }
    }

    /**
     * Merge another delivery schedule into this one
     */
    public function mergeDeliverySchedules(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'source_schedule_id' => 'required|exists:delivery_schedules,id',
            ]);

            if ($validator->fails()) {
                return $this->jsonify(['errors' => $validator->errors()], 422);
            }

            $sourceScheduleId = (int)$request->source_schedule_id;
            if ((int)$id === $sourceScheduleId) {
                return $this->jsonify(['message' => 'Source and target delivery schedules must be different'], 422);
            }

            $targetSchedule = null;
            $sourceSchedule = null;
            DB::transaction(function () use ($id, $sourceScheduleId, &$targetSchedule, &$sourceSchedule) {
                $targetSchedule = DeliverySchedule::whereKey($id)->lockForUpdate()->firstOrFail();
                $sourceSchedule = DeliverySchedule::whereKey($sourceScheduleId)->lockForUpdate()->firstOrFail();

                if (!in_array($targetSchedule->status, ['consolidating', 'consolidated'])) {
                    throw new \RuntimeException('Target delivery schedule must be in consolidating or consolidated status');
                }
                if (!in_array($sourceSchedule->status, ['consolidating', 'consolidated'])) {
                    throw new \RuntimeException('Source delivery schedule must be in consolidating or consolidated status');
                }

                $this->mergeScheduleCustomersAndItems($targetSchedule, $sourceSchedule);

                $sourceShiftIds = $sourceSchedule->shifts()->pluck('salesman_shifts.id')->toArray();
                if (empty($sourceShiftIds) && $sourceSchedule->shift_id) {
                    $sourceShiftIds = [(int)$sourceSchedule->shift_id];
                }

                $targetSchedule->shifts()->syncWithoutDetaching($sourceShiftIds);
                $this->recalculateDeliverySchedule($targetSchedule);

                $sourceSchedule->shifts()->detach();
                $sourceSchedule->delete();
            });

            Log::info("Merged delivery schedule {$sourceScheduleId} into {$targetSchedule->id}");

            return $this->jsonify([
                'message' => 'Delivery schedules merged successfully',
                'schedule' => $targetSchedule->load('shifts')
            ], 200);
        } catch (\Throwable $e) {
            $status = $e instanceof \RuntimeException ? 422 : 500;
            return $this->jsonify(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * Unmerge a shift from a delivery schedule (create separate delivery for it)
     */
    public function unmergeShift(Request $request, $id, $shiftId): JsonResponse
    {
        try {
            $schedule = null;
            $newSchedule = null;

            DB::transaction(function () use ($id, $shiftId, &$schedule, &$newSchedule) {
                $schedule = DeliverySchedule::whereKey($id)->lockForUpdate()->firstOrFail();
                $shift = SalesmanShift::findOrFail($shiftId);

                if (!in_array($schedule->status, ['consolidating', 'consolidated'])) {
                    throw new \RuntimeException('Cannot unmerge shifts from a delivery schedule that is already in progress or completed');
                }

                if (!$schedule->shifts()->where('salesman_shifts.id', $shiftId)->exists()) {
                    throw new \RuntimeException('This shift is not part of this delivery schedule');
                }

                if ($schedule->shifts()->count() <= 1) {
                    throw new \RuntimeException('Cannot unmerge the only shift. Delete the delivery schedule instead.');
                }

                $newSchedule = DeliverySchedule::create([
                    'shift_id' => $shift->id,
                    'route_id' => $shift->route_id,
                    'expected_delivery_date' => $schedule->expected_delivery_date,
                    'status' => 'consolidating',
                ]);
                $newSchedule->shifts()->attach([(int)$shift->id]);

                $customersToMove = $schedule->customers()->get()->filter(function ($customer) use ($shiftId) {
                    $orderIds = array_filter(array_map('trim', explode(',', (string)$customer->order_id)));
                    if (empty($orderIds)) {
                        return false;
                    }
                    $shiftIdsForOrders = WaInternalRequisition::whereIn('id', $orderIds)->pluck('wa_shift_id')->unique()->values();
                    return $shiftIdsForOrders->count() === 1 && (int)$shiftIdsForOrders->first() === (int)$shiftId;
                });

                if ($customersToMove->isNotEmpty()) {
                    $idsToMove = $customersToMove->pluck('id')->all();
                    $schedule->customers()->whereIn('id', $idsToMove)->update(['delivery_schedule_id' => $newSchedule->id]);
                }

                $schedule->shifts()->detach($shiftId);

                if ((int)$schedule->shift_id === (int)$shiftId) {
                    $newPrimaryShift = $schedule->shifts()->first();
                    if (!$newPrimaryShift) {
                        throw new \RuntimeException('Cannot determine new primary shift');
                    }
                    $schedule->update(['shift_id' => $newPrimaryShift->id]);
                }

                $this->recalculateDeliverySchedule($schedule);
                $this->recalculateDeliverySchedule($newSchedule);
            });

            Log::info("Unmerged shift {$shiftId} from delivery schedule {$schedule->id} into new schedule {$newSchedule?->id}");

            return $this->jsonify([
                'message' => 'Shift unmerged successfully. A new delivery schedule has been created.',
                'schedule' => $schedule->load('shifts')
            ], 200);
        } catch (\Throwable $e) {
            $status = $e instanceof \RuntimeException ? 422 : 500;
            return $this->jsonify(['message' => $e->getMessage()], $status);
        }
    }

    /**
     * Get available vehicles for assignment
     * A vehicle is available if it's not assigned to any active delivery schedule
     */
    public function getAvailableVehicles(Request $request)
    {
        try {
            $user = getLoggeduserProfile();
            
            if (!$user) {
                Log::error('getAvailableVehicles: User not found');
                return $this->jsonify(['message' => 'User not authenticated'], 401);
            }
            
            Log::info('getAvailableVehicles called by user: ' . $user->id . ' (' . $user->name . ') | Role: ' . $user->role_id);
            Log::info('User branch (restaurant_id): ' . $user->restaurant_id);
            
            // Super Admin (role_id = 1) can see vehicles from ALL branches
            // Other users only see vehicles from their branch
            $isSuperAdmin = ($user->role_id == 1);
            
            if ($isSuperAdmin) {
                Log::info('Super Admin detected - fetching vehicles from ALL branches');
                $vehicles = Vehicle::with(['driver'])
                    ->whereHas('driver')
                    ->get();
            } else {
                Log::info('Regular user - fetching vehicles from branch ' . $user->restaurant_id);
                $vehicles = Vehicle::with(['driver'])
                    ->whereHas('driver')
                    ->where('branch_id', $user->restaurant_id)
                    ->get();
            }
            
            Log::info('Total vehicles with drivers: ' . $vehicles->count());
            
            // Check availability for each vehicle
            $availableVehicles = [];
            foreach ($vehicles as $vehicle) {
                // Check if vehicle has an active delivery schedule (status != 'finished')
                // A vehicle is available if it has NO active schedules
                $activeSchedule = DeliverySchedule::where('vehicle_id', $vehicle->id)
                    ->where('status', '!=', 'finished')
                    ->first();
                
                $isAvailable = !$activeSchedule;
                
                Log::info('Vehicle ID: ' . $vehicle->id . 
                         ' | Plate: ' . $vehicle->license_plate_number . 
                         ' | Driver: ' . ($vehicle->driver ? $vehicle->driver->name : 'None') .
                         ' | Available: ' . ($isAvailable ? 'YES' : 'NO') .
                         ($activeSchedule ? ' (Active schedule status: ' . $activeSchedule->status . ')' : ''));
                
                if ($isAvailable) {
                    $availableVehicles[] = [
                        'id' => $vehicle->id,
                        'name' => $vehicle->name,
                        'license_plate_number' => $vehicle->license_plate_number,
                        'driver_name' => $vehicle->driver ? $vehicle->driver->name : 'No Driver',
                        'display_name' => $vehicle->name . ' ' . $vehicle->license_plate_number . ' (' . ($vehicle->driver ? $vehicle->driver->name : 'No Driver') . ')'
                    ];
                }
            }
            
            Log::info('Available vehicles count: ' . count($availableVehicles));
            
            return $this->jsonify(['data' => $availableVehicles], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching available vehicles: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }
}
