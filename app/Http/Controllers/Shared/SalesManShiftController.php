<?php

namespace App\Http\Controllers\Shared;

use PDF;
use Session;
use App\User;
use Throwable;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Route;
use JWTAuthException;
use App\Model\Setting;
use App\Model\UserLog;
use App\Model\WaShift;
use App\SalesmanShift;
use App\Model\PackSize;
use App\DeliverySchedule;
use App\Model\Restaurant;
use App\SalesmanShiftIssue;
use App\OffsiteShiftRequest;
use Illuminate\Http\Request;
use App\Interfaces\SmsService;
use App\Model\WaInventoryItem;
use App\Model\WaRouteCustomer;
use App\Model\WaUnitOfMeasure;
use App\SalesmanShiftCustomer;
use App\SalesmanReasonsResponse;
use App\SalesmanReportingReason;
use App\Services\MappingService;
use App\Model\WaLocationAndStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\SalesmanShiftReopenRequest;
use Illuminate\Contracts\View\View;
use App\Exports\SalesManShiftExport;
use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Model\WaInventoryLocationUom;
use Illuminate\Http\RedirectResponse;
use App\SalesmanReasonsResponseOption;

use App\SalesmanReportingReasonOption;
use App\Model\WaInventoryAssignedItems;
use App\Model\WaInternalRequisitionItem;
use App\Models\SaleCenterSmallPacks;
use Illuminate\Database\Query\JoinClause;
use App\Model\WaStockMove;
use App\Model\WaDebtorTran;

class SalesManShiftController extends Controller
{
    // protected $model;
    protected $base_route;
    protected $resource_folder;

    public function __construct(protected SmsService $smsService)
    {
        // $this->model = "order-taking-schedules";
        $this->base_route = "order-taking-schedules";
        $this->resource_folder = "admin.order_taking_schedules";
    }

    public function index()
    {
        $module = "order-taking-schedules";
        $title = "Order Taking Schedules";
        $model = "order-taking-schedules";
        $base_route = $this->base_route;

        if (!can('view', $module)) {
            return redirect()->back()->withErrors(['message' => 'You don\'t have sufficient permissions to access the requested page.']);
        }

        $breadcum = [$title => "", 'Listing' => ''];
        return view("$this->resource_folder.index", compact('model', 'title', 'base_route', 'breadcum'));
    }

    public function salesmanShift(Request $request)
    {
        $module = "salesmanShift";
        $title = "Salesman Shift";
        $model = "salesman-shifts";
        $date1 = $request->get('start-date') ? \Carbon\Carbon::parse($request->get('start-date'))->toDateString() . " 00:00:00" : null;
        $date2 = $request->get('end-date') ? \Carbon\Carbon::parse($request->get('end-date'))->toDateString() . " 23:59:59" : null;
        $todaysDate = \Carbon\Carbon::now()->toDateString();
        $authuser = Auth::user();
        $userwithrestaurants = $authuser->load('userRestaurent');
        $isAdmin = $authuser->role_id == 1;
        $permission = $this->mypermissionsforAModule();
        $branchIds = DB::table('user_branches')
            ->where('user_id', $authuser->id)
            ->pluck('restaurant_id')
            ->toArray();

        if ($isAdmin || isset($permission['employees' . '___view_all_branches_data'])) {
            $branches = Restaurant::all();
            $routes = Route::all();
        } else {
            $branches = Restaurant::whereIn('id', $branchIds)->get();
            $routes = Route::where('restaurant_id', $authuser->userRestaurent->id)->get();
        }

        if ($request->branch) {
            $routes = Route::where('restaurant_id', $request->branch)->get();
        }

        $query = "
            SELECT
                ss.id AS id,
                COALESCE(
                    (
                        SELECT GROUP_CONCAT(DISTINCT w.shift_type, ' ')
                        FROM wa_internal_requisitions w
                        WHERE w.id IN (
                            SELECT w1.id
                            FROM wa_internal_requisitions w1
                            INNER JOIN wa_shifts ws1 ON w1.wa_shift_id = ws1.id
                            WHERE CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(ws1.shift_id, '-', 2), '-', -1) AS UNSIGNED) = ss.id
                            UNION
                            SELECT w2.id
                            FROM wa_internal_requisitions w2
                            WHERE w2.wa_shift_id = ss.id
                        )
                        AND w.shift_type IS NOT NULL
                    ),
                    'onsite'
                ) AS shift_type,
                ss.status AS status,
                0 As block_orders,
                ss.created_at AS shift_start_time,
                ss.closed_time AS shift_close_time,
                ss.created_at AS created_at,
                SUM(COALESCE(pid.net_weight * oi.quantity, 0) / 1000) AS shift_tonnage,
                (
                  SELECT SUM(COALESCE(wiri.total_cost_with_vat, 0))
                  FROM wa_internal_requisition_items wiri
                  WHERE wiri.wa_internal_requisition_id IN (
                    SELECT w1.id
                    FROM wa_internal_requisitions w1
                    INNER JOIN wa_shifts ws1 ON w1.wa_shift_id = ws1.id
                    WHERE CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(ws1.shift_id, '-', 2), '-', -1) AS UNSIGNED) = ss.id
                    UNION
                    SELECT w2.id
                    FROM wa_internal_requisitions w2
                    WHERE w2.wa_shift_id = ss.id
                  )
                ) AS shift_total,
                SUM(CASE WHEN UPPER(p.title) IN ('CTN','CTNS','CARTON','CARTONS') THEN oi.quantity ELSE 0 END) AS shift_ctns,
                SUM(CASE WHEN UPPER(p.title) IN ('DZN','DOZ','DOZEN','DOZENS') THEN oi.quantity ELSE 0 END) AS shift_dzns,
                ss.salesman_id AS salesman_id,
                u.name AS salesman_name,
                r.route_name AS route_name,
                ss.route_id AS route_id,
                r.tonnage_target,
                (SELECT COUNT(*) FROM wa_route_customers wrc WHERE wrc.route_id = ss.route_id AND wrc.deleted_at IS NULL AND wrc.status = 'approved') AS total_customers,
                (SELECT COUNT(DISTINCT COALESCE(w.wa_route_customer_id, w.customer_id))
                 FROM wa_internal_requisitions w
                 WHERE w.id IN (
                    SELECT w1.id
                    FROM wa_internal_requisitions w1
                    INNER JOIN wa_shifts ws1 ON w1.wa_shift_id = ws1.id
                    WHERE CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(ws1.shift_id, '-', 2), '-', -1) AS UNSIGNED) = ss.id
                    UNION
                    SELECT w2.id
                    FROM wa_internal_requisitions w2
                    WHERE w2.wa_shift_id = ss.id
                 )
                 AND (w.wa_route_customer_id IS NOT NULL OR w.customer_id IS NOT NULL)) AS met_customers_with_orders,
                0 AS met_customers_without_orders,
                0 AS totally_unmet_customers
            FROM
                salesman_shifts ss
            LEFT JOIN
                (
                    SELECT w.id, CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(ws.shift_id, '-', 2), '-', -1) AS UNSIGNED) AS ss_id
                    FROM wa_internal_requisitions w
                    INNER JOIN wa_shifts ws ON w.wa_shift_id = ws.id
                    UNION
                    SELECT w.id, w.wa_shift_id AS ss_id
                    FROM wa_internal_requisitions w
                ) AS wir_all ON wir_all.ss_id = ss.id
            LEFT JOIN
                wa_internal_requisitions wir ON wir.id = wir_all.id
            LEFT JOIN
                wa_internal_requisition_items oi ON wir.id = oi.wa_internal_requisition_id
            LEFT JOIN
                wa_inventory_items pid ON oi.wa_inventory_item_id = pid.id
            LEFT JOIN
                pack_sizes p ON pid.pack_size_id = p.id
            LEFT JOIN
                users u ON ss.salesman_id = u.id
            LEFT JOIN
                routes r ON r.id = ss.route_id
        ";



        // Check admin access first
        $hasGlobalAccess = $isAdmin || 
                          isset($permission['employees' . '___view_all_branches_data']) ||
                          $authuser->role_id == 1 || 
                          strtolower($authuser->name) == 'demo admin' ||
                          !empty($branchIds);
        
        \Log::info('Admin Access Check', [
            'user_name' => $authuser->name,
            'user_role_id' => $authuser->role_id,
            'is_admin' => $isAdmin,
            'has_global_access' => $hasGlobalAccess,
            'branch_ids' => $branchIds,
            'requested_branch' => $request->branch
        ]);
        
        $query .= " WHERE 1=1";
        $bindings = [];
        
        // Only filter by branch if route was successfully joined (r.id IS NOT NULL)
        if ($request->branch && $hasGlobalAccess) {
            $query .= " AND (r.restaurant_id = ? OR r.id IS NULL)";
            $bindings[] = $request->branch;
        } elseif ($request->branch && !$hasGlobalAccess) {
            if (in_array($request->branch, $branchIds) || $request->branch == $authuser->restaurant_id) {
                $query .= " AND (r.restaurant_id = ? OR r.id IS NULL)";
                $bindings[] = $request->branch;
            } else {
                $query .= " AND (r.restaurant_id = ? OR r.id IS NULL)";
                $bindings[] = $authuser->restaurant_id;
            }
        } elseif (!$hasGlobalAccess) {
            if (!empty($branchIds)) {
                $placeholders = str_repeat('?,', count($branchIds) - 1) . '?';
                $query .= " AND (r.restaurant_id IN ($placeholders) OR r.id IS NULL)";
                $bindings = array_merge($bindings, $branchIds);
            } else {
                $query .= " AND (r.restaurant_id = ? OR r.id IS NULL)";
                $bindings[] = $authuser->restaurant_id;
            }
        }

        if ($request->route) {
            $query .= " AND r.id = ?";
            $bindings[] = $request->route;
        }

        if ($date1 && $date2) {
            $query .= " AND ss.created_at >= ? AND ss.created_at <= ?";
            $bindings[] = $date1;
            $bindings[] = $date2;
        } else {
            $query .= " AND DATE(ss.created_at) = ?";
            $bindings[] = $todaysDate;
        }

        $query .= " GROUP BY ss.id, ss.created_at ORDER BY shift_total DESC";

        // Debug: Log the query and bindings
        \Log::info('Salesman Shifts Query Debug', [
            'query' => $query,
            'bindings' => $bindings,
            'date1' => $date1,
            'date2' => $date2,
            'todaysDate' => $todaysDate,
            'request_start_date' => $request->get('start-date'),
            'request_end_date' => $request->get('end-date'),
            'request_branch' => $request->branch,
            'user_restaurant_id' => $authuser->restaurant_id,
            'user_name' => $authuser->name,
            'user_role_id' => $authuser->role_id,
            'is_admin' => $isAdmin,
            'has_global_access' => $hasGlobalAccess ?? 'not_set',
            'branch_ids' => $branchIds,
            'date_range' => [$date1, $date2],
            'today' => $todaysDate
        ]);
        
        $all_item = DB::select($query, $bindings);
        
        \Log::info('Salesman Shifts Query Results', [
            'result_count' => count($all_item),
            'results' => $all_item
        ]);

        if ($request->type && $request->type == "Download") {
            $data = [];
            foreach ($all_item as $row) {
                $payload = [
                    "date" => \Carbon\Carbon::parse($row->created_at)->toDateString(),
                    "route" => $row->route_name,
                    "shift_type" => $row->shift_type,
                    "opened_at" => \Carbon\Carbon::parse($row->shift_start_time)->toTimeString(),
                    "status" => $row->status,
                    "closed_at" => $row->shift_close_time ?  ($row->status == 'close' ? \Carbon\Carbon::parse($row->shift_close_time)->toTimeString() : '-') :  '-',
                    "sales-man" => $row->salesman_name,
                    "customer_count" => $row->met_customers_with_orders . ' / ' . $row->total_customers,
                    "tonnage" => manageAmountFormat($row->shift_tonnage) . ' / ' . $row->tonnage_target,
                    "shift_total" => manageAmountFormat($row->shift_total),
                ];
                $data[] = $payload;
            }
            $export = new SalesManShiftExport(collect($data));
            return Excel::download($export, "salesman_shifts.$date1._.$date2.xlsx");
        }

        $breadcum = [$title => "", 'Listing' => ''];
        return view("admin.salesreceiablesreports.salesman_shift", compact('all_item', 'model', 'title', 'breadcum', 'routes', 'branches', 'authuser'));
    }



    // public function salesmanShiftDetails($id)
    // {

    //     $module = "salesmanShift";
    //     $title = "Salesman Shift Details";
    //     $model = "salesman-shifts";
    //     $shift = SalesmanShift::with(['salesman', 'relatedRoute', 'shiftCustomers', 'orders'])->find($id);
    //     $route = Route::find($shift->route_id);
    //     $routeCustomers = WaRouteCustomer::where('route_id', $shift->route_id)->count();
    //     $data = [];
    //     $shiftTonnage = 0;
    //     $met_count = 0;
    //     $met_without_orders_count = 0;
    //     $totally_unmet_count = 0;
    //     foreach ($shift->shiftCustomers as $shiftCustomer) {

    //         $payload = [];
    //         $customerTonnage = 0;
    //         $customer = WaRouteCustomer::find($shiftCustomer->route_customer_id);
    //         if ($customer) {

    //             $payload['customer_name'] = $customer->bussiness_name ?? '';
    //             $payload['customer_phone_no'] = $customer->phone ?? '';
    //             $payload['customer_town'] = $customer->center->name ?? '';

    //             $shiftCustomerMet = SalesmanShiftCustomer::latest()
    //                 ->where('salesman_shift_id', $shift->id)
    //                 ->where('route_customer_id', $customer->id)
    //                 ->first();

    //             $payload['is_met'] = $shiftCustomerMet?->order_taken ?? null;
    //             $payload['is_met_updated_at'] = $shiftCustomerMet?->updated_at ?? null; 

    //             if ($payload['is_met'] == 1) {
    //                 $met_count++;
    //             }

    //             $payload['met_without_orders'] = SalesmanShiftCustomer::latest()
    //                 ->where('salesman_shift_id', $shift->id)
    //                 ->where('route_customer_id', $customer->id)
    //                 ->where('order_taken', 0)
    //                 ->whereExists(function ($query) use ($shift, $customer) {
    //                     $query->select(DB::raw(1))
    //                         ->from('salesman_shift_issues')
    //                         ->where('shift_id', $shift->id);

    //                     $created_at = $shift?->created_at->toDateTimeString();
    //                     if (isset($created_at) && $created_at > '2024-06-11 23:59:59') {
    //                         $query->where('status', 'verified');
    //                     }

    //                     $query->where('customer_id', $customer->id)
    //                         ->whereIn('scenario', ['no_order', 'shop_closed']);
    //                 })
    //                 ->exists();

    //             if ($payload['met_without_orders']) {
    //                 $met_without_orders_count++;
    //             }

    //             $payload['totally_unmet_customers'] = SalesmanShiftCustomer::latest()
    //                 ->where('salesman_shift_id', $shift->id)
    //                 ->where(function ($query) use ($customer) {
    //                     $query->where('route_customer_id', $customer->id)
    //                         ->where('visited', 0);
    //                 })
    //                 ->orWhere(function ($query) use ($shift, $customer) {
    //                     $query->where('route_customer_id', $customer->id)
    //                         ->where('visited', 1)
    //                         ->where('order_taken', 0)
    //                         ->whereExists(function ($query) use ($shift, $customer) {
    //                             $query->select(DB::raw(1))
    //                                 ->from('salesman_shift_issues')
    //                                 ->where('shift_id', $shift->id)
    //                                 ->where('customer_id', $customer->id);
    //                             $created_at = $shift?->created_at->toDateTimeString();
    //                             if (isset($created_at) && $created_at > '2024-06-11 23:59:59') {
    //                                 $query->where('status', '!=', 'verified');
    //                             }
    //                         });
    //                 })
    //                 ->exists();

    //             if ($payload['totally_unmet_customers']) {
    //                 $totally_unmet_count++;
    //             }

    //             $shiftIssue = SalesmanShiftIssue::latest()
    //                 ->where('shift_id', $shift->id)
    //                 ->where('customer_id', $customer->id)
    //                 ->first();

    //             $payload['reported_issue'] = $shiftIssue?->scenario ?? null;
    //             $payload['reported_issue_created_at'] = $shiftIssue?->created_at ?? null;

    //             $orders = WaInternalRequisition::with('getRelatedItem')->where('wa_route_customer_id', $customer->id)->where('wa_shift_id', $shift->id)->get();
    //             if ($orders->isEmpty()) {
    //                 $payload['order_slug'] = null;
    //                 $payload['order_no'] = null;
    //                 $payload['order_id'] = null;
    //                 $payload['order_total'] = null;
    //                 $payload['customer_tonnage'] = 0;
    //                 $data[] = $payload;
    //             } else {

    //                 foreach ($orders as $order) {
    //                     $payload['order_slug'] = $order->slug;
    //                     $payload['order_no'] = $order->requisition_no;
    //                     $payload['order_id'] = $order->requisition_no;
    //                     $payload['order_total'] = $order->getOrderTotal();

    //                     $orderItems = $order->getRelatedItem;
    //                     $customerTonnage = 0;

    //                     foreach ($orderItems as $item) {
    //                         $orderedItemQuantity = $item->quantity;
    //                         $orderedItemWeight = (WaInventoryItem::find($item->wa_inventory_item_id)->net_weight) / 1000;
    //                         $orderedItemTonnage = $orderedItemQuantity * $orderedItemWeight;
    //                         $customerTonnage = $customerTonnage + $orderedItemTonnage;
    //                     }
    //                     $payload['customer_tonnage'] = $customerTonnage;
    //                     $data[] = $payload;
    //                 }
    //             }
    //         }
    //     }

    //     $dataCollection = new Collection($data);
    //     $visitedCustomers = SalesmanShiftCustomer::where('salesman_shift_id', $shift->id)->where('visited', 1)->count();
    //     $breadcum = [$title => "", 'Listing' => ''];
    //     return view("admin.salesreceiablesreports.salesman_shift_summary", compact(
    //         'data',
    //         'model',
    //         'route',
    //         'routeCustomers',
    //         'visitedCustomers',
    //         'title',
    //         'breadcum',
    //         'shift',
    //         'met_count',
    //         'met_without_orders_count',
    //         'totally_unmet_count'
    //     ));
    // }

    public function salesmanShiftDetails($id)
    {

        $module = "salesmanShift";
        $title = "Salesman Shift Details";
        $model = "salesman-shifts";
        $shift = SalesmanShift::with(['salesman', 'relatedRoute', 'shiftCustomers', 'orders'])->find($id);
        
        // Check if shift exists
        if (!$shift) {
            return redirect()->route('salesman-shifts.index')->with('error', 'Shift not found');
        }
        
        $route = Route::find($shift->route_id);
        $routeCustomers = WaRouteCustomer::where('route_id', $shift->route_id)->whereNull('deleted_at')->where('status', 'approved')->count();
        $data = [];
        $shiftTonnage = 0;
        $met_count = 0;
        $met_without_orders_count = 0;
        $totally_unmet_count = 0;
        
        $waShiftIds = WaShift::whereRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(shift_id, '-', 2), '-', -1) AS UNSIGNED) = ?", [$shift->id])
            ->pluck('id')
            ->toArray();
        // Always include salesman_shifts.id for legacy orders that reference it directly
        if (!in_array($shift->id, $waShiftIds)) {
            $waShiftIds[] = $shift->id;
        }
        \Log::info('SalesmanShiftDetails Debug: waShiftIds', [
            'salesman_shift_id' => $shift->id,
            'wa_shift_ids' => $waShiftIds,
        ]);

        $ordersForShift = WaInternalRequisition::with('getRelatedItem')
            ->whereIn('wa_shift_id', $waShiftIds)
            ->get();
        \Log::info('SalesmanShiftDetails Debug: orders snapshot', [
            'salesman_shift_id' => $shift->id,
            'orders_count' => $ordersForShift->count(),
            'sample' => $ordersForShift->take(5)->map(function($o){
                return [
                    'id' => $o->id,
                    'wa_shift_id' => $o->wa_shift_id,
                    'wa_route_customer_id' => $o->wa_route_customer_id,
                    'customer_id' => $o->customer_id,
                    'customer_name' => $o->customer_name ?? null,
                    'customer_phone' => $o->customer_phone ?? null,
                    'requisition_no' => $o->requisition_no,
                    'total' => method_exists($o, 'getOrderTotal') ? $o->getOrderTotal() : null,
                ];
            }),
        ]);

        // Accumulators for shift header metrics
        $shiftTotalSales = 0;
        $shiftTotalTonnage = 0;
        $shiftTotalCtns = 0;
        $shiftTotalDzns = 0;

        $grouped = $ordersForShift->groupBy(function($order) {
            if (!is_null($order->wa_route_customer_id)) {
                return 'route:' . $order->wa_route_customer_id;
            }
            return 'mobile:' . $order->customer_id;
        });

        // met customers equals number of unique customer groups
        $met_count = $grouped->count();
        
        \Log::info('SalesmanShiftDetails Debug: grouped customers', [
            'salesman_shift_id' => $shift->id,
            'grouped_count' => $grouped->count(),
            'keys' => $grouped->keys()->toArray(),
            'order_counts_per_group' => $grouped->map(fn($orders) => $orders->count())->toArray(),
        ]);
        
        foreach ($grouped as $key => $orders) {
            $payload = [];
            $customerTonnage = 0;
            $first = $orders->first();

            $routeCustomerId = $first->wa_route_customer_id;
            $resolved = false;
            if (!is_null($routeCustomerId)) {
                $customer = WaRouteCustomer::find($routeCustomerId);
                if ($customer) {
                    $payload['customer_name'] = $customer->bussiness_name ?? '';
                    $payload['customer_phone_no'] = $customer->phone ?? '';
                    $payload['customer_town'] = $customer->center->name ?? '';
                    $resolved = true;
                }
            }

            if (!$resolved) {
                $payload['customer_name'] = $first->customer_name ?? ('Customer #' . ($first->customer_id ?? ''));
                $payload['customer_phone_no'] = $first->customer_phone ?? '';
                $payload['customer_town'] = '';
            }

            $payload['is_met'] = 1;
            $payload['is_met_updated_at'] = $first->created_at ?? now();
            $payload['met_without_orders'] = false;
            $payload['totally_unmet_customers'] = false;

            $shiftIssue = SalesmanShiftIssue::latest()
                ->where('shift_id', $shift->id)
                ->when(!is_null($routeCustomerId), function($q) use ($routeCustomerId) {
                    $q->where('customer_id', $routeCustomerId);
                })
                ->first();

            $payload['reported_issue'] = $shiftIssue?->scenario ?? null;
            $payload['reported_issue_created_at'] = $shiftIssue?->created_at ?? null;

            foreach ($orders as $order) {
                $payload['order_slug'] = $order->slug;
                $payload['order_no'] = $order->requisition_no;
                $payload['order_id'] = $order->requisition_no;
                $computedTotal = null;
                if (method_exists($order, 'getOrderTotal')) {
                    $computedTotal = $order->getOrderTotal();
                }
                $payload['order_total'] = $computedTotal ?? $order->total_cost_with_vat ?? 0;
                $shiftTotalSales += (float) ($payload['order_total'] ?? 0);

                $orderItems = $order->getRelatedItem;
                $customerTonnage = 0;
                $encountered = [];
                foreach ($orderItems as $item) {
                    $orderedItemQuantity = $item->quantity;
                    $inventoryItem = WaInventoryItem::find($item->wa_inventory_item_id);
                    $orderedItemWeight = $inventoryItem ? ($inventoryItem->net_weight / 1000) : 0;
                    $orderedItemTonnage = $orderedItemQuantity * $orderedItemWeight;
                    $customerTonnage = $customerTonnage + $orderedItemTonnage;

                    // CTNs and DZN counters
                    $packTitle = '';
                    if ($inventoryItem && method_exists($inventoryItem, 'packSize')) {
                        $ps = $inventoryItem->packSize;
                        $packTitle = strtoupper(trim($ps->title ?? ''));
                    } else {
                        // fallback: quick lookup of pack size if relation name differs
                        if ($inventoryItem && property_exists($inventoryItem, 'pack_size_id')) {
                            $psModel = \DB::table('pack_sizes')->where('id', $inventoryItem->pack_size_id)->value('title');
                            $packTitle = strtoupper(trim($psModel ?? ''));
                        }
                    }
                    $unitTitle = strtoupper(trim($item->unit ?? ''));
                    $encountered[] = ['pack' => $packTitle, 'unit' => $unitTitle, 'qty' => $orderedItemQuantity];
                    if (in_array($packTitle, ['CTN','CTNS','CARTON','CARTONS']) || in_array($unitTitle, ['CTN','CTNS','CARTON','CARTONS'])) {
                        $shiftTotalCtns += (float) $orderedItemQuantity;
                    }
                    if (in_array($packTitle, ['DZN','DOZ','DOZEN','DOZENS']) || in_array($unitTitle, ['DZN','DOZ','DOZEN','DOZENS'])) {
                        $shiftTotalDzns += (float) $orderedItemQuantity;
                    }
                }
                $payload['customer_tonnage'] = $customerTonnage;
                $shiftTotalTonnage += $customerTonnage;
                $data[] = $payload;
                \Log::info('SalesmanShiftDetails Debug: item units/packs encountered', [
                    'order_id' => $order->id,
                    'requisition_no' => $order->requisition_no,
                    'encountered' => $encountered,
                ]);
            }
        }

        // Set computed shift header metrics used by the Blade
        $shift->shift_total = $shiftTotalSales;
        $shift->shift_tonnage = $shiftTotalTonnage;
        $shift->shift_ctns = $shiftTotalCtns;
        $shift->shift_dzns = $shiftTotalDzns;

        $dataCollection = new Collection($data);
        $visitedCustomers = $met_count;
        $breadcum = [$title => "", 'Listing' => ''];
        return view("admin.salesreceiablesreports.salesman_shift_summary", compact(
            'data',
            'model',
            'route',
            'routeCustomers',
            'visitedCustomers',
            'title',
            'breadcum',
            'shift',
            'met_count',
            'met_without_orders_count',
            'totally_unmet_count'
        ));
    }


    public function getShopOrderDetails($slug)
    {
        $module = "salesmanShift";
        $title = "Order Details";
        $model = "salesman-shifts";
        $breadcum = [$title => "", 'Listing' => ''];

        $data = [];
        $orderItems = WaInternalRequisition::withCount('getRelatedItem as number_of_items')
            ->with([
                'getRelatedItem' => function ($query) {},
                'getRelatedItem.getInventoryItemDetail' => function ($query) {}
            ])
            ->with('getRelatedItem.getInventoryItemDetail')
            ->with("getRouteCustomer")
            ->where('slug', $slug)->first();

        $order = $orderItems->getRelatedItem;

        foreach ($order as $orderItem) {
            $payload = [];

            $inventoryItem = WaInventoryItem::find($orderItem->wa_inventory_item_id);
            if ($inventoryItem) {
                $payload['name'] = $inventoryItem->title;
            } else {
                $payload['name'] = 'Deleted Item';
            }

            $payload['quantity'] = $orderItem->quantity;
            $payload['item_price'] = $orderItem->selling_price;
            $payload['total_price'] = $orderItem->total_cost;
            $payload['tonnage'] = ($inventoryItem->net_weight * $orderItem->quantity) / 1000;

            $data[] = $payload;
        }
        // dd($order);


        return view("admin.salesreceiablesreports.shop_order_details", compact('data', 'model', 'orderItems', 'title', 'module', 'breadcum'));
    }

    public function getSalesmanShifts(): View|RedirectResponse
    {
        $title = "Salesman Shifts";
        $model = "salesman-shifts";
        $base_route = $this->base_route;

        //        if (!can('view', $module)) {
        //            return redirect()->back()->withErrors(['message' => 'You don\'t have sufficient permissions to access the requested page.']);
        //        }

        $breadcum = [$title => "", 'Listing' => ''];
        return view("$this->resource_folder.index", compact('model', 'title', 'base_route', 'breadcum'));
    }

    public function overview()
    {
        $module = "order-taking-schedules";
        $title = "Order Taking Schedules";
        $model = "order-taking-schedules";
        $base_route = $this->base_route;

        if (!can('view', $module)) {
            return redirect()->back()->withErrors(['message' => 'You don\'t have sufficient permissions to access the requested page.']);
        }

        $data = [];

        $user = getLoggeduserProfile();
        $shifts = SalesmanShift::withWhereHas('relatedRoute', function ($query) use ($user) {
            if ($user->role_id != 1) {
                $query->where('restaurant_id', $user->restaurant_id);
            }
        })->whereDate('created_at', '=', Carbon::today())->get();

        $data['summary']['scheduled_shifts'] = count($shifts);

        $statusCounts = $shifts->countBy(function (SalesmanShift $shift) {
            return $shift->status;
        });

        $data['summary']['active_shifts'] = $statusCounts['open'] ?? 0;
        $data['summary']['pending_shifts'] = $statusCounts['not_started'] ?? 0;
        $data['summary']['closed_shifts'] = $statusCounts['close'] ?? 0;

        $breadcum = [$title => "", 'Listing' => ''];
        return view("$this->resource_folder.overview", compact('model', 'title', 'base_route', 'breadcum', 'data'));
    }

    public function getScheduleSummary(Request $request): JsonResponse
    {
        try {
            $shifts = SalesmanShift::withWhereHas('relatedRoute', function ($query) use ($request) {
                if ($request->branch_id != 0) {
                    $query->where('restaurant_id', $request->branch_id);
                }
            })->whereDate('created_at', '=', Carbon::today())->get();

            $response['scheduled_shifts'] = count($shifts);

            $statusCounts = $shifts->countBy(function (SalesmanShift $shift) {
                return $shift->status;
            });

            $response['active_shifts'] = $statusCounts['open'] ?? 0;
            $response['pending_shifts'] = $statusCounts['not_started'] ?? 0;
            $response['closed_shifts'] = $statusCounts['close'] ?? 0;

            return $this->jsonify(['data' => $response], 200);
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function getTargetsVsActuals(Request $request): JsonResponse
    {
        try {
            $response = [];
            $salesTargetData = [
                'type' => 'Total Sales',
                'value' => 0,
                'actual' => 0,
                'variance' => 0,
                'performance' => 0,
            ];

            $tonnageTargetData = [
                'type' => 'Tonnage',
                'value' => 0,
                'actual' => 0,
                'variance' => 0,
                'performance' => 0,
            ];

            $ctnTargetData = [
                'type' => 'CTNs',
                'value' => 0,
                'actual' => 0,
                'variance' => 0,
                'performance' => 0,
            ];

            $dznTargetData = [
                'type' => 'DZNs',
                'value' => 0,
                'actual' => 0,
                'variance' => 0,
                'performance' => 0,
            ];
            // get target data

            $currentDay = Carbon::now()->dayOfWeek;

            $routes = Route::whereRaw("FIND_IN_SET(?, order_taking_days)", [$currentDay])
                ->get();

            $totalTonnage = $routes->sum('tonnage_target');
            $totalCtns = $routes->sum('ctn_target');
            $totalDzn = $routes->sum('dzn_target');
            $totalSales = $routes->sum('sales_target');
            $salesTargetData['value'] = $totalSales;
            $tonnageTargetData['value'] = $totalTonnage;
            $ctnTargetData['value'] = $totalCtns;
            $dznTargetData['value'] = $totalDzn;


            $shifts = SalesmanShift::withWhereHas('relatedRoute', function ($query) use ($request) {
                if ($request->branch_id != 0) {
                    $query->where('restaurant_id', $request->branch_id);
                }
            })->whereDate('created_at', '=', Carbon::today())->get();


            foreach ($shifts as $shift) {
                $salesTargetData['actual'] += $shift->shift_total;
                $tonnageTargetData['actual'] += $shift->shift_tonnage;
                $ctnTargetData['actual'] += $shift->shift_ctns;
                $dznTargetData['actual'] += $shift->shift_dzns;
            }
            $salesVariance = $salesTargetData['actual'] - $salesTargetData['value'];
            $salesTargetData['variance'] = $salesVariance;
            if ($salesTargetData['value'] != 0) {
                $salesTargetData['performance'] = round((($salesTargetData['actual'] / $salesTargetData['value']) * 100), 2);
            }
            $tonnageVariance = $tonnageTargetData['actual'] - $tonnageTargetData['value'];
            $tonnageTargetData['variance'] = $tonnageVariance;
            if ($tonnageTargetData['value'] != 0) {
                $tonnageTargetData['performance'] = round((($tonnageTargetData['actual'] / $tonnageTargetData['value']) * 100), 2);
            }

            $ctnVariance = $ctnTargetData['actual'] - $ctnTargetData['value'];
            $ctnTargetData['variance'] = $ctnVariance;
            if ($ctnTargetData['value'] != 0) {
                $ctnTargetData['performance'] = round((($ctnTargetData['actual'] / $ctnTargetData['value']) * 100), 2);
            }
            $dznVariance = $dznTargetData['actual'] - $dznTargetData['value'];
            $dznTargetData['variance'] = $dznVariance;
            if ($dznTargetData['value'] != 0) {
                $dznTargetData['performance'] = round((($dznTargetData['actual'] / $dznTargetData['value']) * 100), 2);
            }

            $salesTargetData['value'] = format_amount_with_currency($salesTargetData['value']);
            $salesTargetData['actual'] = format_amount_with_currency($salesTargetData['actual']);
            $salesTargetData['variance'] = format_amount_with_currency($salesTargetData['variance']);

            $tonnageTargetData['value'] = "{$tonnageTargetData['value']}T";
            $tonnageTargetData['actual'] = "{$tonnageTargetData['actual']}T";
            $tonnageTargetData['variance'] = "{$tonnageTargetData['variance']}T";

            $response[] = $salesTargetData;
            $response[] = $tonnageTargetData;
            $response[] = $ctnTargetData;
            $response[] = $dznTargetData;
            return $this->jsonify(['data' => $response], 200);
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function getScheduleList(Request $request): JsonResponse
    {
        try {
            $shifts = SalesmanShift::withWhereHas('relatedRoute', function ($query) use ($request) {
                if ($request->branch_id != 0) {
                    $query->where('restaurant_id', $request->branch_id);
                }
            })
                ->with(['salesman'])->withCount('shiftCustomers')
                ->whereDate('created_at', '=', Carbon::today())
                ->get()
                ->map(function (SalesmanShift $shift) {
                    $shift->route_salesman = "{$shift->relatedRoute->route_name} ({$shift->salesman->name})";
                    if ($shift->status == 'not_started') {
                        $shift->shift_customers_count = WaRouteCustomer::where('route_id', $shift->relatedRoute->id)->whereNull('deleted_at')->where('status', 'approved')->count();
                    }

                    $shift->display_status = ucwords(str_replace('_', ' ', $shift->status));

                    $shift->starting_time = 'N/A';
                    $shift->punctuality = 'N/A';
                    $shift->ctime = 'N/A';

                    if ($shift->status != 'not_started') {
                        $shift->starting_time = Carbon::parse($shift->start_time)->format('g:i A');
                        $shift->punctuality = 'On Time';
                    }

                    $punctualityDifference = Carbon::now()->setTime(7, 0)->diffInMinutes(Carbon::parse($shift->start_time));
                    if ($punctualityDifference > 0) {
                        $shift->punctuality = 'Late';
                    }

                    $shift->formatted_shift_total = format_amount_with_currency($shift->shift_total);

                    if ($shift->status == 'close') {
                        $shift->ctime = Carbon::parse($shift->closed_time)->format('g:i A');
                        $shift->punctuality = 'On Time';
                    }

                    return $shift;
                });

            return $this->jsonify(['data' => $shifts], 200);
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function getScheduleLogs(Request $request): JsonResponse
    {
        try {
            $shifts = SalesmanShift::withWhereHas('relatedRoute', function ($query) use ($request) {
                if ($request->branch_id != 0) {
                    $query->where('restaurant_id', $request->branch_id);
                }
            })->whereDate('created_at', '=', Carbon::now()->toDateString())->pluck('id');

            $logs = UserLog::latest()->whereIn('entity_id', $logs);

            return $this->jsonify(['data' => $response], 200);
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function getShiftStatistics(Request $request): JsonResponse
    {
        $payload = [
            'status' => true,
            'message' => 'success',
            'total_shops' => 0,
            'total_unvisited_shops' => 0,
            'totalOrdersAmount' => 0,
            'sales_target' => 'N/A',
            'tonnage_target' => 'N/A',
            'ctns' => 'N/A',
            'dzns' => 'N/A',
        ];

        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $payload['status'] = false;
                $payload['message'] = 'A user matching the provided token was not found.';
                return $this->jsonify($payload, 422);
            }

            $openShift = SalesmanShift::latest()->with(['shiftCustomers'])
                ->where('status', 'open')->where('salesman_id', $user->id)
                ->first();

            if (!$openShift) {
                $payload['totalOrdersAmount'] = format_amount_with_currency($payload['totalOrdersAmount']);
                return $this->jsonify($payload, 200);
            }

            $payload['total_shops'] = WaRouteCustomer::approved()->where('route_id', $openShift->route_id)->count();
            $payload['total_unvisited_shops'] = $openShift->shiftCustomers()->where('visited', 0)->count();

            $soldTonnage = 0;
            $soldCtns = 0;
            $soldDzns = 0;

            $orders = WaInternalRequisition::with(['getRelatedItem', 'getRelatedItem.getInventoryItemDetail'])
                ->where('wa_shift_id', $openShift->id)
                ->get();

            foreach ($orders as $order) {
                $payload['totalOrdersAmount'] += $order->getOrderTotal();
                foreach ($order->getRelatedItem as $orderItem) {
                    // $itemTonnage = $orderItem->getInventoryItemDetail?->net_weight ?? 0;
                    // if ($itemTonnage && $itemTonnage != 0) {
                    //     $soldTonnage += (float)number_format($itemTonnage / 1000, 4);
                    // }

                    $itemPackSize = PackSize::find($orderItem->getInventoryItemDetail?->pack_size_id);
                    if ($itemPackSize?->title == 'CTN') {
                        $soldCtns += $orderItem->quantity;
                    }

                    if ($itemPackSize?->title == 'DZN') {
                        $soldDzns += $orderItem->quantity;
                    }
                }
            }
            $soldTonnage = (float)number_format($openShift->shift_tonnage, 4);

            $route = Route::find($openShift->route_id);
            $payload['sales_target'] = 'KES. ' . $this->formatShiftSalesTarget($route->sales_target);
            $payload['tonnage_target'] = "{$soldTonnage}T/{$route->tonnage_target}T";
            $payload['ctns'] = "$soldCtns/$route->ctn_target";
            $payload['dzns'] = "$soldDzns/$route->dzn_target";

            $payload['totalOrdersAmount'] = format_amount_with_currency($payload['totalOrdersAmount']);
            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            $payload['status'] = false;
            $payload['message'] = $e->getMessage();

            return $this->jsonify($payload, 500);
        }
    }

    private function formatShiftSalesTarget($amount): string
    {
        $amount = (float)$amount;
        if ($amount < 1000) {
            return $amount;
        }

        if ($amount < 1000000) {
            return ((float)number_format($amount / 1000, 1)) . 'K';
        }

        return ((float)number_format($amount / 1000000, 1)) . 'M';
    }

    public function requestReopen(Request $request): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => "Your request has been received and is being processed."
        ];

        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $response['status'] = false;
                $response['message'] = 'A user matching the provided token was not found.';
                return $this->jsonify($response, 422);
            }

            if (!$request->route_id) {
                $response['status'] = false;
                $response['message'] = 'Route id is required';
                return $this->jsonify($response, 422);
            }

            if (!$request->reason) {
                $response['status'] = false;
                $response['message'] = 'Please provide a reason';
                return $this->jsonify($response, 422);
            }

            $route = Route::find($request->route_id);
            $todaysDate = Carbon::now()->toDateString();
            $routeShift = SalesmanShift::with(['salesman'])->latest()->where('route_id', $route->id)
                ->whereDate('created_at', '=', $todaysDate)
                ->where('status', 'close')
                ->first();
            if (!$routeShift) {
                $response['status'] = false;
                $response['message'] = "Route $route->route_name does not have a shift for the day.";
                return $this->jsonify($response, 422);
            }

            $existingRequest = $routeShift->reopenRequests()->where('status', 'pending')->first();
            if ($existingRequest) {
                $response['status'] = false;
                $response['message'] = "You already have a pending request for this shift.";
                return $this->jsonify($response, 422);
            }

            $routeShift->reopenRequests()->create(['reason' => $request->reason]);
            $salesman = $routeShift->salesman;
            $approverMessage = "Salesman $salesman->name ($salesman->phone_number) has requested re-opening of their shift for route $route->route_name, citing \"$request->reason\"";
            try {
                $this->smsService->sendMessage($approverMessage, '0740489494');
                $this->smsService->sendMessage($approverMessage, '0728600363');
            } catch (\Throwable $e) {
                // pass
            }

            return $this->jsonify($response, 200);
        } catch (\Throwable $e) {
            $response['status'] = false;
            $response['message'] = $e->getMessage();
            return $this->jsonify($response, 500);
        }
    }

    public function getReopenRequests()
    {
        $title = "Salesman Shift Reopen requests";
        $breadcum = [$title => '', 'Listing' => ''];
        $model = "shift-reopen-request";
        $base_route = $this->base_route;

        $requests = SalesmanShiftReopenRequest::latest()->with(['getShift', 'getShift.salesman'])->where('status', 'pending')->get();
        return view("$this->resource_folder.reopen_requests", compact('title', 'breadcum', 'base_route', 'model', 'requests'));
    }

    public function approveReopenRequest($id): RedirectResponse
    {


        DB::beginTransaction();
        try {
            $reopenRequest = SalesmanShiftReopenRequest::with(['getShift', 'getShift.salesman'])->find($id);
            $salesman = $reopenRequest->getShift->salesman;
            // check if request is expired
            if ($reopenRequest->status == 'expired') {
                $message = "Your shift re-opening request for route {$reopenRequest->getShift->route} is expired and can\'t be re-opened.";
                $adminMessage = "Shift re-opening request for route {$reopenRequest->getShift->route} is expired and can\'t be re-opened.";
            }
            // check existing open shifts
            $openShifts = SalesmanShift::where('salesman_id', $reopenRequest->getShift->salesman_id)->where('status', 'open')->get();
            if ($openShifts->count() >= 1) {
                $message = "Your re-opening request for route {$reopenRequest->getShift->route}  has been denied because you have an open shift for route {$openShifts[0]->salesman_route->route_name} .";
                $adminMessage = "Re-opening request for route {$reopenRequest->getShift->route}  has been denied because  salesman has an open shift for route {$openShifts[0]->salesman_route->route_name} .";
            } else {

                $reopenRequest->update(['status' => 'approved']);

                $reopenRequest->getShift()->update(['status' => 'open']);

                $message = "Your shift re-opening request for route {$reopenRequest->getShift->route} has been approved.";
                $adminMessage = "Shift re-opening request for route {$reopenRequest->getShift->route} has been approved.";
            }
            try {
                $this->smsService->sendMessage($message, $salesman->phone_number);
            } catch (\Throwable $e) {
                // pass
            }

            DB::commit();
            return redirect()->route('salesman-shift.reopen-requests')->with('success', $adminMessage);
        } catch (Throwable $e) {
            DB::rollBack();
            return redirect()->route('salesman-shift.reopen-requests')->withErrors(['error' => 'An error occurred while approving request. Please try again.']);
        }
    }

    public function declineReopenRequest($id): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $reopenRequest = SalesmanShiftReopenRequest::with(['getShift', 'getShift.salesman'])->find($id);
            $reopenRequest->update(['status' => 'declined']);

            $salesman = $reopenRequest->getShift->salesman;
            $message = "Your shift re-opening request for route {$reopenRequest->getShift->route} has been declined.";

            DB::commit();
            return redirect()->route('salesman-shift.reopen-requests')->with('success', 'Request declined successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            return redirect()->route('salesman-shift.reopen-requests')->withErrors(['error' => 'An error occurred while declining request. Please try again.']);
        }
    }

    public function requestOffsiteShift(Request $request): JsonResponse
    {
        $response = [
            'status' => true,
            'message' => 'Your request has been received and is being processed.'
        ];

        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                $response['status'] = false;
                $response['message'] = 'A user matching the provided token was not found.';
                return $this->jsonify($response, 422);
            }

            if (!$request->shift_id) {
                $response['status'] = false;
                $response['message'] = 'Shift id is required';
                return $this->jsonify($response, 422);
            }

            if (!$request->reason) {
                $response['status'] = false;
                $response['message'] = 'Please provide a reason';
                return $this->jsonify($response, 422);
            }

            $shift = SalesmanShift::with(['salesman'])->find($request->shift_id);
            $user = User::find($shift->salesman_id);
            //
            $existingRequest = OffsiteShiftRequest::latest()->where('status', 'pending')->where('shift_id', $shift->id)->first();
            if ($existingRequest) {
                $response['status'] = false;
                $response['message'] = "You already have a pending request for this shift.";
                return $this->jsonify($response, 422);
            }

            // $branch = Restaurant::find($user->restaurant_id);

            // $distance = MappingService::getTheaterDistanceBetweenTwoPoints($request->latitude, $request->longitude, $branch->latitude, $branch->longitude);

            // if ($distance > 200) {
            //     $response['status'] = false;
            //     $response['message'] = "You are outside the allowed distance from the branch. Please move within 200m of the branch";
            //     return $this->jsonify($response, 422);
            // }

            OffsiteShiftRequest::create([
                'shift_id' => $request->shift_id,
                'route_id' => $shift->route_id,
                'salesman_id' => $shift->salesman_id,
                'reason' => $request->reason,
            ]);

            $salesman = $user; // $shift->salesman;
            $message = "Salesman $salesman->name ($salesman->phone_number) has requested to switch to an off-site shift for route  $shift->route, citing \"$request->reason\"";

            // get offsite_shift_requests 
            $alert = DB::table('alerts')->where('alert_name', 'offsite_shift_requests')->first();
            // check if alerts are already set or found
            if ($alert) {
                // Check if type is user &  get where recipients is not 0
                if ($alert->recipient_type == 'user') {

                    if ($alert->recipients != 0) {
                        //create array of ids from receipients and get user with ids
                        $ids = explode(',', $alert->recipients);
                        $recipients = User::whereIn('id', $ids)->get();

                        foreach ($recipients as $key => $value) {
                            // send alerts
                            try {
                                $this->smsService->sendMessage($message, $value->phone_number);
                            } catch (\Throwable $e) {
                                // pass
                            }
                        }
                    }
                }

                // Check if type is role &  get where recipients is not 0

                if ($alert->recipient_type == 'role') {
                    if ($alert->recipients != 0) {
                        //create array of ids from receipients and get user with role_ids
                        $roleids = explode(',', $alert->recipients);
                        $recipients = User::whereIn('role_id', $roleids)->get();

                        foreach ($recipients as $key => $value) {

                            try {
                                $this->smsService->sendMessage($message, $value->phone_number);
                            } catch (\Throwable $e) {
                                // pass
                            }
                        }
                    }
                }
            }


            $route = Route::select('id', 'route_name')->find($shift->route_id);
            UserLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'module' => 'order_taking',
                'activity' => "Requested to switch onsite shift to offsite for $route?->route_name",
                'entity_id' => $shift->id,
                'user_agent' => 'Bizwiz APP',
            ]);

            DB::commit();
            return $this->jsonify($response, 200);
        } catch (Throwable $e) {
            DB::rollBack();
            $response['status'] = false;
            $response['message'] = $e->getMessage();
            return $this->jsonify($response, 500);
        }
    }


    public function getOffsiteRequests(Request $request): View
    {
        $title = "Salesman Offsite Shift requests";
        $breadcum = [$title => '', 'Listing' => ''];
        $model = "salesman-offsite-requests";
        $base_route = $this->base_route;
        $user = Auth::user();
        $start = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfDay();
        $end = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        $branchIds = DB::table('user_branches')
            ->where('user_id', $user->id)
            ->pluck('restaurant_id')
            ->toArray();
        $branches = Restaurant::whereIn('id', $branchIds)->get();
        if ($user->role_id == 1) {
            $branches = Restaurant::all();
        }
        if ($request->branch) {
            $branch = $request->branch;
        } else {
            $branch = $user->restaurant_id;
        }

        $requests = DB::table('offsite_shift_requests')->select(
            'offsite_shift_requests.id',
            'offsite_shift_requests.status',
            'offsite_shift_requests.reason',
            'offsite_shift_requests.created_at',
            'routes.route_name as route',
            'users.name as salesman_name',
            'users.phone_number as salesman_number',
        )
            ->join('routes', 'offsite_shift_requests.route_id', '=', 'routes.id')
            ->join('users', 'offsite_shift_requests.salesman_id', '=', 'users.id')
            ->whereBetween('offsite_shift_requests.created_at', [$start, $end])
            ->where('routes.restaurant_id', $branch)
            ->orderBy('offsite_shift_requests.created_at', 'DESC')
            ->get();

        return view("$this->resource_folder.offsite_requests", compact('title', 'breadcum', 'base_route', 'model', 'requests', 'user',  'branches'));
    }

    public function approveOffsiteRequest($id): RedirectResponse
    {
        DB::beginTransaction();
        try {

            $offsiteRequest = OffsiteShiftRequest::find($id);
            $salesman = User::find($offsiteRequest->salesman_id);
            $route = Route::find($offsiteRequest->route_id);

            $user = getLoggeduserProfile();
            $offsiteRequest->update(['status' => 'approved', 'reviewed_by' => $user->id]);

            $shift = SalesmanShift::find($offsiteRequest->shift_id);
            $shift->update(['shift_type' => 'offsite']);

            UserLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'module' => 'order_taking',
                'activity' => "Approved offsite shift for $route?->route_name",
                'entity_id' => $shift->id,
                'user_agent' => 'Bizwiz APP',
            ]);

            $message = "Your off-site shift request for $route->route_name has been approved.";
            $adminMessage = "Request approved successfully";


            try {
                $this->smsService->sendMessage($message, $salesman->phone_number);
            } catch (\Throwable $e) {
                // pass
            }

            DB::commit();
            return redirect()->route('salesman-shift.offsite-requests')->with('success', $adminMessage);
        } catch (Throwable $e) {
            DB::rollBack();
            return redirect()->route('salesman-shift.offsite-requests')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function declineOffsiteRequest($id): RedirectResponse
    {
        DB::beginTransaction();
        try {

            $offsiteRequest = OffsiteShiftRequest::find($id);
            $salesman = User::find($offsiteRequest->salesman_id);
            $route = Route::find($offsiteRequest->route_id);

            $user = getLoggeduserProfile();
            $offsiteRequest->update(['status' => 'declined', 'reviewed_by' => $user->id]);

            $shift = SalesmanShift::find($offsiteRequest->shift_id);
            UserLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'module' => 'order_taking',
                'activity' => "Declined offsite shift for $route?->route_name",
                'entity_id' => $shift->id,
                'user_agent' => 'Bizwiz APP',
            ]);

            $message = "Your off-site shift request for $route->route_name has been declined.";
            $adminMessage = "Request declined successfully";


            try {
                $this->smsService->sendMessage($message, $salesman->phone_number);
            } catch (\Throwable $e) {
                // pass
            }

            DB::commit();
            return redirect()->route('salesman-shift.offsite-requests')->with('success', $adminMessage);
        } catch (Throwable $e) {
            DB::rollBack();
            return redirect()->route('salesman-shift.offsite-requests')->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function downloadDeliveryReport($id)
    {
        $shift = SalesmanShift::with(['orders', 'salesman', 'relatedRoute'])->find($id);
        $schedule = DeliverySchedule::latest()->with(['vehicle', 'driver'])->forShiftId((int)$shift->id)->first();
        $branch = Restaurant::find($shift->relatedRoute->restaurant_id)->name;

        $shift->date = Carbon::parse($shift->created_at)->toFormattedDateString();
        $shift->invoices = implode(',', DB::table('wa_internal_requisitions')->where('wa_shift_id', $shift->id)->pluck('requisition_no')->toArray());
        $actual_orders = WaInternalRequisition::latest()->where('wa_shift_id', $shift->id);
        $shift->actual_orders = WaInternalRequisition::latest()->where('wa_shift_id', $shift->id)->get()->map(function ($order) {
            $routeCustomer = WaRouteCustomer::withTrashed()->with(['center'])->find($order->wa_route_customer_id);
            $orderItems = WaInternalRequisitionItem::where('wa_internal_requisition_id', $order->id)->get();
            $orderTonnage = 0;
            foreach ($orderItems as $item) {
                $orderedItemQuantity = $item->quantity;
                $orderedItemWeight = (WaInventoryItem::find($item->wa_inventory_item_id)->net_weight) / 1000;
                $orderedItemTonnage = $orderedItemQuantity * $orderedItemWeight;
                $orderTonnage = $orderTonnage + $orderedItemTonnage;
            }
            return [
                'invoice_id' => $order->requisition_no,
                // 'customer_account' => $routeCustomer->account_number,
                'customer_name' => $routeCustomer->bussiness_name,
                'location' => $routeCustomer->center?->name,
                'balance' => 0,
                'tonnage' => $orderTonnage,
                // 'total' => $order->getOrderTotalWithDiscount(),
                'total' => $order->getOrderTotal()
            ];
        })->sortBy('customer_name');

        $report_name = "{$shift->relatedRoute->route_name}-Delivery-Report";
        $pdf = PDF::loadView('admin.delivery_schedules.delivery_report', compact('report_name', 'schedule', 'shift', 'branch'));
        return $pdf->download($report_name . '.pdf');
    }

    public function downloadDeliverySheet($id)
    {
        $shift = SalesmanShift::with(['orders', 'salesman', 'relatedRoute'])->find($id);
        $schedule = DeliverySchedule::latest()->with(['vehicle', 'driver'])->forShiftId((int)$shift->id)->first();
        $branch = Restaurant::find($shift->relatedRoute->restaurant_id)->name;

        $shift->date = Carbon::parse($shift->created_at)->toFormattedDateString();

        $shift->invoices = implode(',', DB::table('wa_internal_requisitions')->where('wa_shift_id', $shift->id)->pluck('requisition_no')->toArray());

        $actual_orders = WaInternalRequisition::latest()->where('wa_shift_id', $shift->id);

        $shift->actual_orders = WaInternalRequisition::latest()->where('wa_shift_id', $shift->id)->get()->map(function ($order) {
            $routeCustomer = WaRouteCustomer::with(['center'])->find($order->wa_route_customer_id);
            $orderItems = WaInternalRequisitionItem::where('wa_internal_requisition_id', $order->id)->get();
            $orderTonnage = 0;
            foreach ($orderItems as $item) {
                $orderedItemQuantity = $item->quantity;
                $orderedItemWeight = (WaInventoryItem::find($item->wa_inventory_item_id)->net_weight ?? 0) / 1000;
                $orderedItemTonnage = $orderedItemQuantity * $orderedItemWeight;
                $orderTonnage = $orderTonnage + $orderedItemTonnage;
            }
            return [
                'invoice_id' => $order->requisition_no,
                'invoice_date' => $order->requisition_date,
                'customer_account' => $routeCustomer->account_number,
                'customer_name' => $routeCustomer->bussiness_name,
                'location' => $routeCustomer->center?->name,
                'balance' => 0,
                'tonnage' => $orderTonnage,
                // 'total' => $order->getOrderTotalWithDiscount(),
                'total' => $order->getOrderTotal()
            ];
        })->sortBy('customer_name');

        $report_name = "{$shift->relatedRoute->route_name}-Delivery-Sheet";
        $pdf = PDF::loadView('admin.delivery_schedules.delivery_sheet', compact('report_name', 'schedule', 'shift', 'branch'));
        return $pdf->download($report_name . '.pdf');
    }


    // public function downloadLoadingSheet($id)
    // {
    //     $now = Carbon::now();
    //     $shift = SalesmanShift::with(['orders', 'salesman', 'relatedRoute'])->find($id);
    //     $shift->block_orders = true;
    //     $shift->loading_sheet_print_count += 1;
    //     $shift->save();
    //     $salesmanLocation = User::find($shift->salesman_id)->wa_location_and_store_id;
    //     $schedule = DeliverySchedule::latest()->with(['vehicle', 'driver'])->where('shift_id', $shift->id)->first();
    //     $shift->date = Carbon::parse($shift->created_at)->toFormattedDateString();
    //     $shift->invoices = implode(',', DB::table('wa_internal_requisitions')->where('wa_shift_id', $shift->id)->pluck('requisition_no')->toArray());
    //     $actual_orders = WaInternalRequisition::latest()->where('wa_shift_id', $shift->id)->get();
    //     $branch = Restaurant::find($shift->relatedRoute->restaurant_id)->name;

    //     $data = [];
    //     $binIds = [];
    //     $smallPacks = [];
    //     $packs = $actual_orders->pluck('center_small_pack_id')
    //         ->filter() // Filter out null values
    //         ->unique() // Get distinct values
    //         ->values(); // Reindex the collection
    //     $packItems = DB::table('sale_center_small_pack_items')->whereIn('sale_center_small_pack_id',$packs)->get();
    //     $packItemsIds=[];
    //     if (count($packItems)) {;
    //         $packItemsIds = $packItems->pluck('wa_inventory_item_id')->toArray();
    //         $groupRep = SaleCenterSmallPacks::find($packItems[0]->sale_center_small_pack_id);
    //         // $smallPacks['user']=$groupRep->createdBy;
    //         $route = Route::with(['currentRepresentative', 'currentRepresentative.user'])->where('id', $shift->route_id)->first();
    //         $smallPacks['user']=$route->currentRepresentative?->user;

    //     }
        
    //     foreach ($actual_orders as $order) {
            
    //         $loadingSheetItems = WaInternalRequisitionItem::where('wa_internal_requisition_id', $order->id)->get();

    //         foreach ($loadingSheetItems as $item) {
    //             $inventoryItem = WaInventoryItem::find($item->wa_inventory_item_id);

    //             $existingItemKey = array_search($inventoryItem->stock_id_code, array_column($data, 'stock_id'));

    //             if ($existingItemKey !== false) {
    //                 $data[$existingItemKey]['quantity'] += $item->quantity;
    //                 $data[$existingItemKey]['tonnage'] += ($item->quantity * $inventoryItem->net_weight) / 1000;
    //             } else {
    //                 $smallPackStatus =0;
    //                 if (in_array($item->wa_inventory_item_id,$packItemsIds)) {
    //                     $smallPackStatus =1;
    //                 }
    //                 $payload = [
    //                     'item_id' => $inventoryItem->id,
    //                     'stock_id' => $inventoryItem->stock_id_code,
    //                     'title' => $inventoryItem->title,
    //                     'quantity' => $item->quantity,
    //                     'tonnage' => ($item->quantity * $inventoryItem->net_weight) / 1000,
    //                     'bin' => WaInventoryLocationUom::where('inventory_id', $inventoryItem->id)
    //                         ->where('location_id', $salesmanLocation)
    //                         ->first()
    //                         ->uom_id ?? 15,
    //                     'CTNS' => null,
    //                     'DZNS' => null,
    //                     'OUTERS' => null,
    //                     'PCS' => null,
    //                     'small_pack' => $smallPackStatus,
    //                 ];
                    
    //                     $data[] = $payload;
                    
                    
                    
    //             }

    //             $binIds[] = $payload['bin'];
    //         }
    //     }
    //     $data = collect($data)->sortBy('title');
    //     $newdata = [];
    //     foreach ($data as $item) {
    //         $motherChildRelationship = WaInventoryAssignedItems::where('destination_item_id', $item['item_id'])->first();
    //         if ($motherChildRelationship && ((int)$item['quantity'] > $motherChildRelationship->conversion_factor)) {
    //             $motherItemQuantity = floor($item['quantity'] / $motherChildRelationship->conversion_factor);
    //             $childQuantity = $item['quantity'] % $motherChildRelationship->conversion_factor;
    //             $motherItemPackSize = WaInventoryItem::find($motherChildRelationship->wa_inventory_item_id)->pack_size_id;
    //             $childPackSize = WaInventoryItem::find($item['item_id'])->pack_size_id;
    //             //update mother pack size
    //             if (in_array($motherItemPackSize, [3, 5])) {
    //                 $item['CTNS'] = $motherItemQuantity;
    //             } elseif ($motherItemPackSize == 6) {
    //                 $item['DZNS'] = $motherItemQuantity;
    //             }
    //             //update child
    //             if ($childPackSize == 9) {
    //                 $item['OUTERS'] = $childQuantity;
    //             } elseif ($childPackSize == 6) {
    //                 $item['DZNS'] = $childQuantity;
    //             } else {
    //                 $item['PCS'] = $childQuantity;
    //             }
    //             $newPayload = $item;
    //         } else {
    //             $itemPackSize = WaInventoryItem::find($item['item_id'])->pack_size_id;
    //             if (in_array($itemPackSize, [3, 5])) {
    //                 $item['CTNS'] = $item['quantity'];
    //             } elseif ($itemPackSize == 6) {
    //                 $item['DZNS'] = $item['quantity'];
    //             } elseif ($itemPackSize == 9) {
    //                 $item['OUTERS'] = $item['quantity'];
    //             } else {
    //                 $item['PCS'] = $item['quantity'];
    //             }


    //             $newPayload = $item;
    //         }
    //         $newdata[] = $newPayload;
    //     }

    //     $bins = WaUnitOfMeasure::whereIn('id', $binIds)->where('is_display', 0)->get();
    //     $report_name = "{$shift->relatedRoute->route_name}-Loading-Sheet"; 
    //     $pdf = PDF::loadView('admin.delivery_schedules.loading_sheet', compact('report_name', 'schedule', 'shift', 'data', 'bins', 'branch', 'newdata', 'now','smallPacks'));
    //     // return $pdf->stream();
    //     return $pdf->download($report_name . '.pdf');
    // }
    private function resolveWaShiftIds(int $salesmanShiftId): array
    {
        $waShiftIds = WaShift::whereRaw(
            "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(shift_id, '-', 2), '-', -1) AS UNSIGNED) = ?",
            [$salesmanShiftId]
        )
            ->pluck('id')
            ->toArray();

        if (!in_array($salesmanShiftId, $waShiftIds, true)) {
            $waShiftIds[] = $salesmanShiftId;
        }

        return $waShiftIds;
    }

    private function checkInvoicesBalance($shiftId)
{
    $shift = SalesmanShift::find($shiftId);
    
    if (!$shift) {
        return false;
    }

    $waShiftIds = $this->resolveWaShiftIds((int) $shiftId);

    //get invoices total
    $invoicesValue = WaInternalRequisitionItem::Join('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
        ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
        ->sum('wa_internal_requisition_items.total_cost_with_vat');

    //get moves total
    $stockMovesValue = WaStockMove::whereIn('shift_id', $waShiftIds)->sum('total_cost');

    
    //get debtors  total
    $debtorsValue = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
        ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
        ->sum('wa_debtor_trans.amount');
    
    // Enhanced debugging with detailed breakdown
    $this->logInvoiceBalanceDetails($shiftId, $invoicesValue, $stockMovesValue, $debtorsValue, $waShiftIds);
    
    //check if they balance with tolerance for floating point precision
    $tolerance = 0.01; // Allow 1 cent difference for floating point precision
    $invoicesStockDiff = abs($invoicesValue - $stockMovesValue);
    $stockDebtorsDiff = abs($stockMovesValue - $debtorsValue);
    
    if($invoicesStockDiff <= $tolerance && $stockDebtorsDiff <= $tolerance){
        return true;
    }else{
        // Check if we can auto-fix missing debtor transactions
        if ($invoicesValue > 0 && $stockMovesValue > 0 && ($debtorsValue + $tolerance) < $stockMovesValue) {
            \Log::info("Attempting to auto-fix missing debtor transactions for shift {$shiftId}");
            
            if ($this->createMissingDebtorTransactions($shiftId, $invoicesValue)) {
                \Log::info("Successfully created missing debtor transactions for shift {$shiftId}");
                $debtorsValueAfter = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
                    ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
                    ->sum('wa_debtor_trans.amount');

                $stockDebtorsDiffAfter = abs($stockMovesValue - $debtorsValueAfter);
                if ($invoicesStockDiff <= $tolerance && $stockDebtorsDiffAfter <= $tolerance) {
                    return true;
                }
            }
        }
        
        $message = null;
        $route = Route::find($shift->route_id);
        $date = Carbon::parse($shift->created_at)->toDateString();
        $message = "The shift for {$route->route_name} on {$date} has unbalanced invoices. Invoices: {$invoicesValue}, Stock Moves: {$stockMovesValue}, Debtors: {$debtorsValue}. Please resolve to allow loading.";
        
        //notify management
        $users = User::leftJoin('roles', 'roles.id', 'users.role_id')
            ->whereIn('roles.slug', ['super-admin', 'general-manager'])->get();
        // foreach ($users as $user) {
        //     $this->smsService->sendMessage($message, $user->phone_number);
        // }
        return false;
    }       

}

    private function logInvoiceBalanceDetails($shiftId, $invoicesValue, $stockMovesValue, $debtorsValue, array $waShiftIds)
    {
        // Get detailed breakdown for debugging
        $invoiceItems = WaInternalRequisitionItem::Join('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->select('wa_internal_requisition_items.*', 'wa_internal_requisitions.requisition_no')
            ->get();

        $stockMoves = WaStockMove::whereIn('shift_id', $waShiftIds)->get();

        $debtorTrans = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->select('wa_debtor_trans.*', 'wa_internal_requisitions.requisition_no')
            ->get();

        \Log::info("Detailed Invoice Balance Check for Shift {$shiftId}:", [
            'summary' => [
                'wa_shift_ids' => $waShiftIds,
                'invoices_value' => $invoicesValue,
                'stock_moves_value' => $stockMovesValue,
                'debtors_value' => $debtorsValue,
                'invoice_items_count' => $invoiceItems->count(),
                'stock_moves_count' => $stockMoves->count(),
                'debtor_trans_count' => $debtorTrans->count(),
            ],
            'invoice_items' => $invoiceItems->map(function($item) {
                return [
                    'requisition_no' => $item->requisition_no,
                    'total_cost_with_vat' => $item->total_cost_with_vat,
                    'quantity' => $item->quantity,
                    'selling_price' => $item->selling_price
                ];
            })->toArray(),
            'stock_moves' => $stockMoves->map(function($move) {
                return [
                    'total_cost' => $move->total_cost,
                    'quantity' => $move->quantity ?? 'N/A',
                    'unit_cost' => $move->unit_cost ?? 'N/A'
                ];
            })->toArray(),
            'debtor_trans' => $debtorTrans->map(function($trans) {
                return [
                    'requisition_no' => $trans->requisition_no,
                    'document_no' => $trans->document_no,
                    'amount' => $trans->amount
                ];
            })->toArray()
        ]);
    }

    private function createMissingDebtorTransactions($shiftId, $totalAmount)
    {
        try {
            // Get all internal requisitions for this shift
            $waShiftIds = $this->resolveWaShiftIds((int) $shiftId);
            $requisitions = WaInternalRequisition::whereIn('wa_shift_id', $waShiftIds)->get();
            
            if ($requisitions->isEmpty()) {
                \Log::warning("No requisitions found for shift {$shiftId}");
                return false;
            }

            DB::beginTransaction();

            foreach ($requisitions as $requisition) {
                // Check if debtor transaction already exists for this requisition
                $existingDebtor = WaDebtorTran::where('wa_sales_invoice_id', $requisition->id)->first();

                if (!$existingDebtor) {
                    // Calculate total for this requisition
                    $requisitionTotal = WaInternalRequisitionItem::where('wa_internal_requisition_id', $requisition->id)
                        ->sum('total_cost_with_vat');

                    if ($requisitionTotal > 0) {
                        // Create debtor transaction
                        $debtorTran = new WaDebtorTran();
                        $debtorTran->wa_sales_invoice_id = $requisition->id;
                        $debtorTran->document_no = $requisition->requisition_no;
                        $debtorTran->amount = $requisitionTotal;
                        $debtorTran->wa_customer_id = $requisition->wa_route_customer_id;
                        $debtorTran->wa_route_customer_id = $requisition->wa_route_customer_id;
                        $debtorTran->trans_date = $requisition->created_at->toDateString();
                        $debtorTran->input_date = $requisition->created_at;
                        $debtorTran->shift_id = $shiftId;
                        $debtorTran->route_id = $requisition->route_id ?? null;
                        $debtorTran->reference = $requisition->requisition_no;
                        $debtorTran->is_printed = '0';
                        $debtorTran->is_settled = 0;
                        $debtorTran->allocated_amount = 0.00;
                        $debtorTran->created_at = $requisition->created_at;
                        $debtorTran->updated_at = now();
                        
                        $debtorTran->save();

                        \Log::info("Created debtor transaction for requisition {$requisition->requisition_no}, amount: {$requisitionTotal}");
                    }
                }
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to create missing debtor transactions for shift {$shiftId}: " . $e->getMessage());
            return false;
        }
    }

    public function debugInvoiceBalance($id)
    {
        $shift = SalesmanShift::find($id);
        
        if (!$shift) {
            return response()->json(['error' => 'Shift not found'], 404);
        }

        // Get all the data
        $waShiftIds = $this->resolveWaShiftIds((int) $id);
        $invoicesValue = WaInternalRequisitionItem::Join('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->sum('wa_internal_requisition_items.total_cost_with_vat');

        $stockMovesValue = WaStockMove::whereIn('shift_id', $waShiftIds)->sum('total_cost');

        $debtorsValue = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->sum('wa_debtor_trans.amount');

        // Get detailed records
        $invoiceItems = WaInternalRequisitionItem::Join('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->select('wa_internal_requisition_items.*', 'wa_internal_requisitions.requisition_no')
            ->get();

        $stockMoves = WaStockMove::whereIn('shift_id', $waShiftIds)->get();

        $debtorTrans = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->select('wa_debtor_trans.*', 'wa_internal_requisitions.requisition_no')
            ->get();

        // Check if missing records in any table
        $missingStockMoves = $invoiceItems->count() > 0 && $stockMoves->count() == 0;
        $missingDebtorTrans = $invoiceItems->count() > 0 && $debtorTrans->count() == 0;

        return response()->json([
            'shift_id' => $id,
            'shift_info' => [
                'route' => $shift->relatedRoute->route_name ?? 'N/A',
                'status' => $shift->status,
                'created_at' => $shift->created_at,
            ],
            'balance_summary' => [
                'invoices_value' => $invoicesValue,
                'stock_moves_value' => $stockMovesValue,
                'debtors_value' => $debtorsValue,
                'differences' => [
                    'invoices_vs_stock' => $invoicesValue - $stockMovesValue,
                    'stock_vs_debtors' => $stockMovesValue - $debtorsValue,
                    'invoices_vs_debtors' => $invoicesValue - $debtorsValue,
                ]
            ],
            'record_counts' => [
                'invoice_items' => $invoiceItems->count(),
                'stock_moves' => $stockMoves->count(),
                'debtor_transactions' => $debtorTrans->count(),
            ],
            'potential_issues' => [
                'missing_stock_moves' => $missingStockMoves,
                'missing_debtor_transactions' => $missingDebtorTrans,
                'has_orders_but_no_stock_moves' => $missingStockMoves,
                'has_orders_but_no_accounting' => $missingDebtorTrans,
            ],
            'detailed_data' => [
                'invoice_items' => $invoiceItems->map(function($item) {
                    return [
                        'requisition_no' => $item->requisition_no,
                        'total_cost_with_vat' => $item->total_cost_with_vat,
                        'quantity' => $item->quantity,
                        'selling_price' => $item->selling_price,
                        'item_id' => $item->wa_inventory_item_id
                    ];
                }),
                'stock_moves' => $stockMoves->map(function($move) {
                    return [
                        'total_cost' => $move->total_cost,
                        'quantity' => $move->quantity ?? 'N/A',
                        'unit_cost' => $move->unit_cost ?? 'N/A',
                        'item_id' => $move->wa_inventory_item_id ?? 'N/A'
                    ];
                }),
                'debtor_transactions' => $debtorTrans->map(function($trans) {
                    return [
                        'requisition_no' => $trans->requisition_no,
                        'document_no' => $trans->document_no,
                        'amount' => $trans->amount
                    ];
                })
            ]
        ], 200, [], JSON_PRETTY_PRINT);
    }

    public function fixInvoiceBalance($id)
    {
        $shift = SalesmanShift::find($id);
        
        if (!$shift) {
            return response()->json(['error' => 'Shift not found'], 404);
        }

        // Get current balance status
        $waShiftIds = $this->resolveWaShiftIds((int) $id);
        $invoicesValue = WaInternalRequisitionItem::Join('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->sum('wa_internal_requisition_items.total_cost_with_vat');

        $debtorsValue = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
            ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
            ->sum('wa_debtor_trans.amount');

        $tolerance = 0.01;
        if ($invoicesValue > 0 && ($debtorsValue + $tolerance) < $invoicesValue) {
            // Attempt to create missing debtor transactions
            if ($this->createMissingDebtorTransactions($id, $invoicesValue)) {
                $debtorsValueAfter = WaDebtorTran::leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.id', 'wa_debtor_trans.wa_sales_invoice_id')
                    ->whereIn('wa_internal_requisitions.wa_shift_id', $waShiftIds)
                    ->sum('wa_debtor_trans.amount');

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully created missing debtor transactions',
                    'invoices_value' => $invoicesValue,
                    'debtors_value' => $debtorsValueAfter
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create debtor transactions. Check logs for details.'
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No fix needed or different type of imbalance',
                'invoices_value' => $invoicesValue,
                'debtors_value' => $debtorsValue
            ]);
        }
    }

    public function downloadLoadingSheet($id)
    {
        $now = Carbon::now();
        $shift = SalesmanShift::with(['orders', 'salesman', 'relatedRoute'])->find($id);
        
        // Check if invoice balance check should be bypassed (for testing/debugging)
        $bypassBalanceCheck = env('BYPASS_INVOICE_BALANCE_CHECK', false);
        
        if (!$bypassBalanceCheck && !$this->checkInvoicesBalance($id)) {
            // Return error if invoices don't balance
            return redirect()->back()->with('warning', 'This Shift  has  Unbalanced Invoices. Please Contact Administration for Assistance.');
        }

        $shift->block_orders = true;
        $shift->loading_sheet_print_count += 1;
        $shift->save();
        $salesmanLocation = User::find($shift->salesman_id)->wa_location_and_store_id;
        $schedule = DeliverySchedule::latest()->with(['vehicle', 'driver'])->where('shift_id', $shift->id)->first();
        $shift->date = Carbon::parse($shift->created_at)->toFormattedDateString();
        $shift->invoices = implode(',', DB::table('wa_internal_requisitions')->where('wa_shift_id', $shift->id)->pluck('requisition_no')->toArray());
        $actual_orders = WaInternalRequisition::latest()->where('wa_shift_id', $shift->id)->get();
        $branch = Restaurant::find($shift->relatedRoute->restaurant_id)->name;

        $data = [];
        $binIds = [];
        foreach ($actual_orders as $order) {
            $loadingSheetItems = WaInternalRequisitionItem::where('wa_internal_requisition_id', $order->id)->get();

            foreach ($loadingSheetItems as $item) {
                $inventoryItem = WaInventoryItem::find($item->wa_inventory_item_id);

                $existingItemKey = array_search($inventoryItem->stock_id_code, array_column($data, 'stock_id'));

                if ($existingItemKey !== false) {
                    $data[$existingItemKey]['quantity'] += $item->quantity;
                    $data[$existingItemKey]['tonnage'] += ($item->quantity * $inventoryItem->net_weight) / 1000;
                } else {

                    $payload = [
                        'item_id' => $inventoryItem->id,
                        'stock_id' => $inventoryItem->stock_id_code,
                        'title' => $inventoryItem->title,
                        'quantity' => $item->quantity,
                        'tonnage' => ($item->quantity * $inventoryItem->net_weight) / 1000,
                        'bin' => WaInventoryLocationUom::where('inventory_id', $inventoryItem->id)
                            ->where('location_id', $salesmanLocation)
                            ->first()
                            ->uom_id ?? 15,
                        'CTNS' => null,
                        'DZNS' => null,
                        'OUTERS' => null,
                        'PCS' => null,
                    ];


                    $data[] = $payload;
                }

                $binIds[] = $payload['bin'];
            }
        }
        $data = collect($data)->sortBy('title');
        $newdata = [];
        foreach ($data as $item) {
            $motherChildRelationship = WaInventoryAssignedItems::where('destination_item_id', $item['item_id'])->first();
            if ($motherChildRelationship && ((int)$item['quantity'] > $motherChildRelationship->conversion_factor)) {
                $motherItemQuantity = floor($item['quantity'] / $motherChildRelationship->conversion_factor);
                $childQuantity = $item['quantity'] % $motherChildRelationship->conversion_factor;
                $motherItemPackSize = WaInventoryItem::find($motherChildRelationship->wa_inventory_item_id)->pack_size_id;
                $childPackSize = WaInventoryItem::find($item['item_id'])->pack_size_id;
                //update mother pack size
                if (in_array($motherItemPackSize, [3, 5])) {
                    $item['CTNS'] = $motherItemQuantity;
                } elseif ($motherItemPackSize == 6) {
                    $item['DZNS'] = $motherItemQuantity;
                }
                //update child
                if ($childPackSize == 9) {
                    $item['OUTERS'] = $childQuantity;
                } elseif ($childPackSize == 6) {
                    $item['DZNS'] = $childQuantity;
                } else {
                    $item['PCS'] = $childQuantity;
                }
                $newPayload = $item;
            } else {
                $itemPackSize = WaInventoryItem::find($item['item_id'])->pack_size_id;
                if (in_array($itemPackSize, [3, 5])) {
                    $item['CTNS'] = $item['quantity'];
                } elseif ($itemPackSize == 6) {
                    $item['DZNS'] = $item['quantity'];
                } elseif ($itemPackSize == 9) {
                    $item['OUTERS'] = $item['quantity'];
                } else {
                    $item['PCS'] = $item['quantity'];
                }


                $newPayload = $item;
            }
            $newdata[] = $newPayload;
        }


        $bins = WaUnitOfMeasure::whereIn('id', $binIds)->get();
        $report_name = "{$shift->relatedRoute->route_name}-Loading-Sheet";
        $pdf = PDF::loadView('admin.delivery_schedules.loading_sheet_2', compact('report_name', 'schedule', 'shift', 'data', 'bins', 'branch', 'newdata', 'now'));
        return $pdf->download($report_name . '.pdf');
    }
    public function downloadSalesmanShiftDetailsReport($id)
    {

        $shift = SalesmanShift::with(['salesman', 'relatedRoute', 'shiftCustomers', 'orders'])->find($id);
        $route = Route::find($shift->route_id);
        $routeCustomers = WaRouteCustomer::where('route_id', $shift->route_id)->whereNull('deleted_at')->where('status', 'approved')->count();
        // $routeCustomers = $shift->shiftCustomers->count();
        $data = [];
        $shiftTonnage = 0;
        foreach ($shift->shiftCustomers as $shiftCustomer) {

            $payload = [];
            $customerTonnage = 0;
            $customer = WaRouteCustomer::find($shiftCustomer->route_customer_id);
            if ($customer) {

                $payload['customer_name'] = $customer->bussiness_name ?? '';
                $payload['customer_phone_no'] = $customer->phone ?? '';
                $payload['customer_town'] = $customer->center->name ?? '';
                $payload['is_met'] = SalesmanShiftCustomer::latest()->where('salesman_shift_id', $shift->id)->where('route_customer_id', $customer->id)->value('order_taken');
                $payload['reported_issue'] = SalesmanShiftIssue::latest()->where('shift_id', $shift->id)->where('customer_id', $customer->id)->value('scenario');
                $orders = WaInternalRequisition::with('getRelatedItem')->where('wa_route_customer_id', $customer->id)->where('wa_shift_id', $shift->id)->get();
                if ($orders->isEmpty()) {
                    $payload['order_slug'] = null;
                    $payload['order_no'] = null;
                    $payload['order_id'] = null;
                    $payload['order_total'] = null;
                    $payload['customer_tonnage'] = 0;
                    $data[] = $payload;
                } else {

                    foreach ($orders as $order) {
                        $payload['order_slug'] = $order->slug;
                        $payload['order_no'] = $order->requisition_no;
                        $payload['order_id'] = $order->requisition_no;
                        $payload['order_total'] = $order->getOrderTotal();

                        $orderItems = $order->getRelatedItem;
                        $customerTonnage = 0;

                        foreach ($orderItems as $item) {
                            $orderedItemQuantity = $item->quantity;
                            $orderedItemWeight = (WaInventoryItem::find($item->wa_inventory_item_id)->net_weight) / 1000;
                            $orderedItemTonnage = $orderedItemQuantity * $orderedItemWeight;
                            $customerTonnage = $customerTonnage + $orderedItemTonnage;
                        }
                        $payload['customer_tonnage'] = $customerTonnage;
                        $data[] = $payload;
                    }
                }
            }
        }

        $dataCollection = new Collection($data);
        // $visitedCustomers = $dataCollection->where('is_met', 1)->count();
        $visitedCustomers = WaInternalRequisition::where('wa_shift_id', $shift->id)
            ->whereNotNull('wa_route_customer_id')
            ->distinct('wa_route_customer_id')
            ->count();

        $report_name = "{$shift->shiftId}-Shift-Report";
        $pdf = PDF::loadView('admin.salesreceiablesreports.print_shift_details', compact('report_name', 'data', 'route', 'routeCustomers', 'visitedCustomers', 'shiftTonnage', 'shift'));
        return $pdf->download($report_name . '.pdf');
    }

    public function reopenShiftBe(Request $request, $id)
    {
        $shift = SalesmanShift::find($id);
        $user = getLoggeduserProfile();
        // if($user->role_id == 1){
        //     $shift->status = 'open';
        //     $shift->save();
        //     return redirect()->route('salesman-shifts.index')->with('success', 'Shift Reopened Successfully');
        // }

        if ($shift->block_orders) {
            return redirect()->back()->withErrors(['error' => "This shift has been blocked from taking any more orders because it's loading sheet has been downloaded."]);
        }
        $timestamp = Carbon::parse($shift->created_at);
        $isMoreThan20HoursOld = $timestamp->diffInHours(now()) > 19;
        $currentTime = Carbon::now();
        $isPast8PM = $currentTime->hour >= 20;
        if ($isMoreThan20HoursOld) {
            return redirect()->back()->withErrors(['error' => "Cannot reopen a shift that is older than 20 hours old"]);
        }
        if ($isPast8PM) {
            return redirect()->back()->withErrors(['error' => "Cannot reopen a shift past 8pm"]);
        }

        $shift->status = 'open';
        $shift->save();

        //save logs
        $row = new UserLog();
        $row->user_id = $user->id;
        $row->user_name = $user->name;
        $row->module = 'order_taking';
        $row->activity = "Re-opened order taking shift for route $shift->route from the backend";
        $row->entity_id = $shift->id;
        $row->user_agent = $request->header('User-Agent');
        $row->user_ip = $request->ip();
        $row->save();

        return redirect()->route('salesman-shifts.index')->with('success', 'Shift Reopened Successfully');
    }

    public function printShiftReturns(Request $request)
    {
        try {
            if (!$request->shift_id) {
                return $this->jsonify(['message' => 'Shift ID is required'], 422);
            }

            $shift = SalesmanShift::with(['salesman'])->find($request->shift_id);
            if (!$shift) {
                return $this->jsonify(['message' => 'Invalid shift ID'], 422);
            }

            $shift->shift_date = Carbon::parse($shift->created_at)->format('Y-m-d');

            $transferIds = DB::table('wa_inventory_location_transfers')->join('wa_internal_requisitions', function ($join) use ($shift) {
                $join->on('wa_inventory_location_transfers.transfer_no', '=', 'wa_internal_requisitions.requisition_no')->where('wa_internal_requisitions.wa_shift_id', $shift->id);
            })->pluck('wa_inventory_location_transfers.id')->toArray();

            $returnRecords = [];
            $returnTotal = 0;
            $returnNumber = null;
            $returnsCount = DB::table('wa_inventory_location_transfer_item_returns')
                ->whereIn('wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', $transferIds)->count();
            if ($returnsCount > 0) {
                $returnRecords = DB::table('wa_inventory_location_transfer_item_returns')
                    ->whereIn('wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', $transferIds)
                    ->select(
                        'wa_inventory_items.title as item_name',
                        'wa_inventory_location_transfer_item_returns.created_at as return_date',
                        'wa_inventory_location_transfer_item_returns.return_number',
                        'wa_inventory_location_transfer_item_returns.return_quantity',
                        'wa_inventory_location_transfer_item_returns.received_quantity',
                        'users.name as initiator',
                        'wa_inventory_location_transfer_items.selling_price'
                    )
                    ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                    ->leftJoin('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                    ->leftJoin('users', 'wa_inventory_location_transfer_item_returns.return_by', '=', 'users.id')
                    ->get()
                    ->map(function ($record) {
                        $record->total = $record->received_quantity * $record->selling_price;
                        return $record;
                    });

                $returnNumber = $returnRecords->first()->return_number;
                $returnTotal = $returnRecords->sum('total');
            }

            $esdData = null;
            $pdf = \PDF::loadView('admin.invoice_returns.salesman_print', compact('shift', 'returnTotal', 'returnRecords', 'esdData', 'returnNumber'));
            return $pdf->stream();
        } catch (Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }
}
