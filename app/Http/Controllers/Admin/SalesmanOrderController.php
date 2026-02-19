<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaInventoryItem;
use App\Model\WaRouteCustomer;
use App\Model\WaCustomer;
use App\Model\Route;
use App\Model\WaLocationAndStore;
use App\Model\WaInventoryLocationUom;
use App\Model\WaUnitOfMeasure;
use App\Model\DeliveryCentres;
use App\Model\WaStockMove;
use App\SalesmanShift;
use App\Model\WaShift;
use App\Model\WaNumerSeriesCode;
use App\DiscountBand;
use App\ItemPromotion;
use App\Enums\PromotionMatrix;
use App\Jobs\PerformPostSaleActions;
use App\Jobs\PrepareStoreParkingList;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Jobs\CreateDeliverySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SalesmanOrderController extends Controller
{
    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'salesman-orders';
        $this->title = 'Salesman Orders';
    }

    /**
     * Display salesman dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Load the routes relationship
        $user->load('routes');

        // Check if user is a salesman (you may need to adjust role checking based on your system)
        if (!$this->isSalesman($user)) {
            Session::flash('error', 'Access denied. This section is for salesmen only.');
            return redirect()->back();
        }

        $title = 'Salesman Dashboard';
        $model = $this->model;

        // Get filter date from request or default to today
        $filterDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        $filterDateCarbon = Carbon::parse($filterDate);

        // Get current active shift
        $activeShift = SalesmanShift::where('salesman_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // Get shifts for the filtered date
        $shifts = SalesmanShift::where('salesman_id', $user->id)
            ->whereDate('start_time', $filterDateCarbon)
            ->with(['orders' => function($query) {
                $query->with(['getRouteCustomer', 'getRelatedItem']);
            }])
            ->orderBy('start_time', 'desc')
            ->get();

        // Get today's orders (for backward compatibility)
        $todaysOrders = WaInternalRequisition::where('user_id', $user->id)
            ->where('requisition_no', 'LIKE', 'SO%')
            ->whereDate('created_at', Carbon::today())
            ->with(['getRouteCustomer', 'getRelatedItem'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get route customers for the salesman's route
        $routeCustomers = collect();
        $userRoute = null;
        $routeInfo = null;
        
        // Try multiple ways to get the route
        if ($user->route) {
            $userRoute = $user->route;
            $routeInfo = Route::find($userRoute);
        } elseif ($user->getroute) {
            $userRoute = $user->getroute->id;
            $routeInfo = $user->getroute;
        } elseif ($user->routes()->exists()) {
            $firstRoute = $user->routes()->first();
            $userRoute = $firstRoute->id;
            $routeInfo = $firstRoute;
        } else {
            // Try to get route from the most recent shift
            $recentShift = SalesmanShift::where('salesman_id', $user->id)
                ->latest()
                ->first();
            if ($recentShift && $recentShift->route_id) {
                $userRoute = $recentShift->route_id;
                $routeInfo = Route::find($userRoute);
            }
        }
        
        if ($userRoute) {
            $routeCustomers = WaRouteCustomer::where('route_id', $userRoute)
                ->whereNull('deleted_at')
                ->where('status', 'approved')
                ->orderBy('bussiness_name')
                ->get();
        }

        $breadcum = [$title => '', 'Dashboard' => ''];
        
        return view('admin.salesman_orders.index', compact(
            'title', 'model', 'breadcum', 'activeShift', 'todaysOrders', 'routeCustomers', 
            'user', 'routeInfo', 'userRoute', 'shifts', 'filterDate'
        ));
    }

    /**
     * Show order creation form
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        
        // Load the routes relationship
        $user->load('routes');
        
        // Check if user is a salesman
        if (!$this->isSalesman($user)) {
            Session::flash('error', 'Access denied. This section is for salesmen only.');
            return redirect()->back();
        }

        // Check if salesman has an active shift
        $activeShift = SalesmanShift::where('salesman_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$activeShift) {
            Session::flash('error', 'You need to have an active shift to create orders. Please contact your supervisor.');
            return redirect()->route('salesman-orders.index');
        }

        $title = 'Create New Order';
        $model = $this->model;

        // Get route customers
        $userRoute = $user->routes()->first(); // Get first assigned route
        $routeCustomers = collect();
        if ($userRoute) {
            $routeCustomers = WaRouteCustomer::where('route_id', $userRoute->id)
                ->where('status', 'approved')
                ->orderBy('bussiness_name')
                ->get();
        }

        $breadcum = [$this->title => route($model.'.index'), 'Create Order' => ''];
        
        // Variables needed for partials.shortcuts
        $selling_allowance = $user->drop_limit ?? 50000; // Default allowance for salesmen
        $permission = $user->permissions ?? []; // User permissions array
        $data = (object)['customer_phone_number' => '']; // Default data object
        
        return view('admin.salesman_orders.create', compact(
            'title', 'model', 'breadcum', 'routeCustomers', 'activeShift', 'user',
            'selling_allowance', 'permission', 'data'
        ));
    }

    /**
     * Store a new order
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'wa_route_customer_id' => 'required|exists:wa_route_customers,id',
            'items' => 'required|array|min:1',
            'items.*.wa_inventory_item_id' => 'required|exists:wa_inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.selling_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        // Get active shift
        $activeShift = SalesmanShift::where('salesman_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$activeShift) {
            return response()->json(['success' => false, 'message' => 'No active shift found']);
        }

        // Get customer details
        $customer = WaRouteCustomer::with('route')->find($request->wa_route_customer_id);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found']);
        }

        DB::beginTransaction();
        try {
            // Check if out of stock ordering is allowed
            $settings = getAllSettings();
            $allowOutOfStockOrdering = isset($settings['ALLOW_OUT_OF_STOCK_ORDERING']) ? $settings['ALLOW_OUT_OF_STOCK_ORDERING'] : '0';
            
            // Validate stock availability for all items before creating the order (if setting is disabled)
            if ($allowOutOfStockOrdering == '0') {
                foreach ($request->items as $item) {
                    $inventoryItem = WaInventoryItem::with('getAllFromStockMoves')->find($item['wa_inventory_item_id']);
                    if (!$inventoryItem) {
                        return response()->json(['success' => false, 'message' => 'Item not found']);
                    }
                    
                    // Calculate current stock quantity for this location
                    $currentStock = $inventoryItem->getAllFromStockMoves()
                        ->where('wa_location_and_store_id', $user->wa_location_and_store_id)
                        ->sum('qauntity');
                    
                    $requestedQuantity = $item['quantity'];
                    
                    // Check if there's sufficient stock
                    if ($currentStock < $requestedQuantity) {
                        return response()->json([
                            'success' => false, 
                            'message' => "Insufficient stock for {$inventoryItem->title}. Available: {$currentStock}, Requested: {$requestedQuantity}"
                        ]);
                    }
                }
            }
            
            // Generate requisition number
            $requisitionNo = $this->generateRequisitionNumber();
            
            // Create internal requisition (invoice)
            $requisition = WaInternalRequisition::create([
                'requisition_no' => $requisitionNo,
                'slug' => strtolower($requisitionNo),
                'user_id' => $user->id,
                'restaurant_id' => $user->restaurant_id,
                'to_store_id' => $user->wa_location_and_store_id,
                'wa_location_and_store_id' => $user->wa_location_and_store_id,
                'requisition_date' => Carbon::now(),
                'name' => $customer->name,
                'route_id' => $customer->route_id,
                'route' => $customer->route->route_name ?? '',
                'customer_id' => $customer->customer_id,
                'wa_route_customer_id' => $customer->id,
                'customer' => $customer->name,
                'customer_phone_number' => $customer->phone,
                'customer_pin' => $customer->kra_pin,
                'status' => 'APPROVED',
                'wa_shift_id' => $activeShift->id,
                'shift_type' => $activeShift->shift_type ?? 'regular',
                'invoice_type' => 'Backend',
            ]);

            // Add order items
            $dateTime = Carbon::now();
            $items = [];
            foreach ($request->items as $item) {
                $inventoryItem = WaInventoryItem::with('taxManager')->find($item['wa_inventory_item_id']);
                
                // Calculate VAT using the same formula as SalesInvoiceController
                $itemTotal = $item['quantity'] * $item['selling_price'];
                $vatRate = 0;
                $vatAmount = 0;
                
                if ($inventoryItem->taxManager) {
                    $vatRate = (float)$inventoryItem->taxManager->tax_value;
                    // VAT is already included in the selling price, so extract it
                    $vatAmount = ($vatRate / (100 + $vatRate)) * $itemTotal;
                }
                
                $totalWithVat = $itemTotal;
                
                $items[] = [
                    'wa_internal_requisition_id' => $requisition->id,
                    'wa_inventory_item_id' => $item['wa_inventory_item_id'],
                    'quantity' => $item['quantity'],
                    'standard_cost' => $inventoryItem->standard_cost,
                    'selling_price' => $item['selling_price'],
                    'total_cost' => $itemTotal,
                    'tax_manager_id' => $inventoryItem->tax_manager_id,
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount,
                    'total_cost_with_vat' => $totalWithVat,
                    'store_location_id' => $user->wa_location_and_store_id,
                    'hs_code' => $inventoryItem->hs_code,
                    'discount' => $item['discount'] ?? 0,
                    'discount_description' => '',
                    'created_at' => $dateTime,
                    'updated_at' => $dateTime,
                ];
            }
            
            WaInternalRequisitionItem::insert($items);

            // Trigger post-sale actions (stock moves, transfers, etc.)
            PerformPostSaleActions::dispatch($requisition)->afterCommit();

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => 'Order created successfully',
                'order_id' => $requisition->id,
                'requisition_no' => $requisition->requisition_no
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Salesman order creation failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'customer_id' => $request->wa_route_customer_id,
                'items' => $request->items
            ]);
            return response()->json(['success' => false, 'message' => 'Error creating order: ' . $e->getMessage()]);
        }
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            Session::flash('error', 'Access denied.');
            return redirect()->back();
        }

        $order = WaInternalRequisition::with([
                'getRouteCustomer', 
                'getRelatedItem.getInventoryItemDetail.pack_size',
                'getRelatedItem.getInventoryItemDetail.taxManager',
                'getrelatedEmployee',
                'shift',
                'route'
            ])
            ->where('user_id', $user->id)
            ->where('requisition_no', 'LIKE', 'SO%')
            ->findOrFail($id);

        $title = 'Order Details';
        $model = $this->model;
        $breadcum = [$this->title => route($model.'.index'), 'Order Details' => ''];

        return view('admin.salesman_orders.show', compact('title', 'model', 'breadcum', 'order'));
    }

    /**
     * Print order invoice
     */
    public function printOrder($id)
    {
        $user = Auth::user();
        
        // Allow access for admin users and salesmen
        if (!($user->role_id == 1 || $this->isSalesman($user))) {
            Session::flash('error', 'Access denied.');
            return redirect()->back();
        }

        $list = WaInternalRequisition::with([
                'getRouteCustomer', 
                'getRelatedItem.getInventoryItemDetail.pack_size',
                'getRelatedItem.getInventoryItemDetail.taxManager',
                'getrelatedEmployee',
                'shift',
                'route'
            ])
            ->where('requisition_no', 'LIKE', 'SO%')
            ->findOrFail($id);
            
        // Apply promotions to add free items
        $this->applyPromotionsToSalesmanOrder($list);
        
        // Increment print count for reprint tracking
        $list->increment('print_count');

        // Get all settings for the print template
        $all_settings = getAllSettings();

        return view('admin.salesman_orders.print', compact('list', 'all_settings'));
    }

    /**
     * Download order invoice as PDF
     */
    public function downloadInvoice($id)
    {
        $user = Auth::user();
        
        // Allow access for admin users and salesmen
        if (!($user->role_id == 1 || $this->isSalesman($user))) {
            Session::flash('error', 'Access denied.');
            return redirect()->back();
        }

        $list = WaInternalRequisition::with([
                'getRouteCustomer', 
                'getRelatedItem.getInventoryItemDetail.pack_size',
                'getRelatedItem.getInventoryItemDetail.taxManager',
                'getrelatedEmployee',
                'shift',
                'route'
            ])
            ->where('requisition_no', 'LIKE', 'SO%')
            ->findOrFail($id);

        // Increment print count for reprint tracking
        $list->increment('print_count');

        // Get all settings for the print template
        $all_settings = getAllSettings();
        
        // Set flag to indicate this is for PDF generation
        $is_pdf = true;

        // Generate PDF
        $pdf = Pdf::loadView('admin.salesman_orders.print', compact('list', 'all_settings', 'is_pdf'))
            ->setPaper('A4', 'portrait');
        
        // Generate filename
        $filename = 'Sales_Order_' . $list->requisition_no . '_' . date('Y-m-d') . '.pdf';
        
        // Download the PDF
        return $pdf->download($filename);
    }

    /**
     * Get available inventory items for salesman (using stock moves like SalesInvoiceController)
     */
    private function getAvailableInventoryItemsForUser($user)
    {
        // Get items that have bin locations first (more efficient than checking each item)
        $itemsWithBins = DB::table('wa_inventory_items as items')
            ->join('wa_inventory_location_uom as bins', 'items.id', '=', 'bins.inventory_id')
            ->leftJoin('wa_unit_of_measures as units', 'items.wa_unit_of_measure_id', '=', 'units.id')
            ->where('bins.location_id', $user->wa_location_and_store_id)
            ->where(function($query) {
                $query->whereNull('items.block_this')
                      ->orWhere('items.block_this', '!=', 1);
            })
            ->select([
                'items.id',
                'items.title',
                'items.standard_cost',
                'items.selling_price',
                'units.title as unit_name'
            ])
            ->orderBy('items.title')
            ->limit(100) // Limit to prevent timeout
            ->get();

        // Calculate stock quantities using stock moves (same as SalesInvoiceController)
        $availableItems = collect();
        
        foreach ($itemsWithBins as $item) {
            $itemQoh = WaStockMove::where('wa_location_and_store_id', $user->wa_location_and_store_id)
                ->where('wa_inventory_item_id', $item->id)
                ->sum('qauntity');

            // Only include items with positive quantity
            if ($itemQoh > 0) {
                $item->available_quantity = $itemQoh;
                $availableItems->push($item);
            }
        }

        return $availableItems;
    }

    /**
     * AJAX endpoint for inventory search (used by partials.shortcuts)
     */
    public function getAvailableInventoryItems(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $search = $request->get('search', '');
        $storeLocationId = $request->get('store_location_id', $user->wa_location_and_store_id);
        
        // Get items that match search and have bin locations
        $itemsQuery = DB::table('wa_inventory_items as items')
            ->join('wa_inventory_location_uom as bins', 'items.id', '=', 'bins.inventory_id')
            ->leftJoin('wa_unit_of_measures as units', 'items.wa_unit_of_measure_id', '=', 'units.id')
            ->where('bins.location_id', $storeLocationId)
            ->where(function($query) {
                $query->whereNull('items.block_this')
                      ->orWhere('items.block_this', '!=', 1);
            });

        // Add search filter if provided
        if (!empty($search)) {
            $itemsQuery->where('items.title', 'LIKE', '%' . $search . '%');
        }

        $items = $itemsQuery->select([
                'items.id',
                'items.title',
                'items.standard_cost',
                'items.selling_price',
                'units.title as unit_name'
            ])
            ->orderBy('items.title')
            ->limit(50)
            ->get();

        // Build HTML response like POS system expects
        $html = '';
        foreach ($items as $item) {
            // Calculate stock quantity
            $itemQoh = WaStockMove::where('wa_location_and_store_id', $storeLocationId)
                ->where('wa_inventory_item_id', $item->id)
                ->sum('qauntity');

            if ($itemQoh > 0) {
                $html .= '<div class="textDataItem" data-id="' . $item->id . '" style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer;">';
                $html .= '<strong>' . $item->title . '</strong><br>';
                $html .= 'Stock: ' . number_format($itemQoh, 2) . ' ' . ($item->unit_name ?? 'Units');
                $html .= '<br>Price: KSh ' . number_format($item->selling_price, 2);
                $html .= '</div>';
            }
        }

        if (empty($html)) {
            $html = '<div style="padding: 8px; color: #999;">No items found</div>';
        }

        return response()->json([
            'view' => $html,
            'results' => $items->count()
        ]);
    }

    /**
     * Check if user is a salesman
     */
    private function isSalesman($user)
    {
        if (!$user) {
            return false;
        }

        $roleName = '';
        if (isset($user->userRole)) {
            // Some installations use 'name', others 'title'
            $roleName = $user->userRole->name ?? $user->userRole->title ?? '';
        }

        $hasRoute = false;
        try {
            // Check if user has routes assigned via many-to-many relationship
            $hasRoute = method_exists($user, 'routes') && $user->routes()->exists();
        } catch (\Throwable $e) {
            $hasRoute = !empty($user->route);
        }

        $salesRoleIds = config('salesman.sales_role_ids', [169, 170]);
        $salesKeywords = config('salesman.sales_role_keywords', ['sales', 'salesman', 'representative']);
        
        $isSalesRoleId = in_array((int) $user->role_id, $salesRoleIds);
        
        $roleLooksSales = false;
        foreach ($salesKeywords as $keyword) {
            if (stripos($roleName, $keyword) !== false) {
                $roleLooksSales = true;
                break;
            }
        }

        return ($hasRoute || $roleLooksSales || $isSalesRoleId);
    }

    /**
     * Generate requisition number
     */
    private function generateRequisitionNumber()
    {
        try {
            $code = getCodeWithNumberSeries('Sales Order');
            if ($code) {
                updateUniqueNumberSeries('Sales Order', $code);
                return $code;
            }
        } catch (\Exception $e) {
            \Log::warning('Number series not found for Sales Order: ' . $e->getMessage());
        }
        
        // Fallback: Generate a simple requisition number
        $prefix = 'SO';
        $date = Carbon::now()->format('Ymd');
        $lastOrder = WaInternalRequisition::whereDate('created_at', Carbon::today())
            ->where('requisition_no', 'LIKE', $prefix . $date . '%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder->requisition_no, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $date . $newNumber;
    }

    /**
     * Get route customers for AJAX
     */
    public function getRouteCustomers(Request $request)
    {
        $user = Auth::user();
        
        $userRoute = $user->routes()->first();
        if (!$userRoute) {
            return response()->json(['customers' => []]);
        }

        $customers = WaRouteCustomer::where('route_id', $userRoute->id)
            ->where('status', 'approved')
            ->select('id', 'bussiness_name', 'name', 'phone')
            ->orderBy('bussiness_name')
            ->get();

        return response()->json(['customers' => $customers]);
    }

    /**
     * Search customers for Select2 AJAX
     */
    public function searchCustomers(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 30;
        
        // Get user's route
        $userRoute = $user->routes()->first();
        if (!$userRoute) {
            return response()->json([
                'results' => [],
                'pagination' => ['more' => false]
            ]);
        }

        // Build query
        $query = WaRouteCustomer::where('route_id', $userRoute->id)
            ->where('status', 'approved')
            ->whereNull('deleted_at');

        // Apply search filter
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('bussiness_name', 'LIKE', '%' . $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('phone', 'LIKE', '%' . $search . '%');
            });
        }

        // Get total count for pagination
        $total = $query->count();
        
        // Get paginated results
        $customers = $query->select('id', 'bussiness_name', 'name', 'phone', 'town')
            ->orderBy('bussiness_name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format results for Select2
        $results = $customers->map(function($customer) {
            return [
                'id' => $customer->id,
                'text' => $customer->bussiness_name . ' - ' . $customer->name,
                'phone' => $customer->phone,
                'town' => $customer->town
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Search inventory items for AJAX (POS-style)
     */
    public function searchItems(Request $request)
    {
        $user = Auth::user();
        $query = $request->get('search', '');

        if (strlen($query) < 3) {
            return '<table class="table table-bordered"><tr><td>Type at least 3 characters</td></tr></table>';
        }

        try {
            // Get items with bin locations
            $items = DB::table('wa_inventory_items as items')
                ->join('wa_inventory_location_uom as bins', 'items.id', '=', 'bins.inventory_id')
                ->leftJoin('wa_unit_of_measures as units', 'items.wa_unit_of_measure_id', '=', 'units.id')
                ->where('bins.location_id', $user->wa_location_and_store_id)
                ->where('items.title', 'LIKE', '%' . $query . '%')
                ->where(function($q) {
                    $q->whereNull('items.block_this')
                      ->orWhere('items.block_this', '!=', 1);
                })
                ->select([
                    'items.id',
                    'items.title',
                    'items.standard_cost',
                    'items.selling_price',
                    'units.title as unit_name'
                ])
                ->limit(20)
                ->get();

            if ($items->isEmpty()) {
                return '<table class="table table-bordered"><tr><td>No items found</td></tr></table>';
            }

            // Build HTML response like POS system
            $html = '<table class="table table-bordered">';
            
            foreach ($items as $item) {
                // Calculate stock for this item
                $itemQoh = WaStockMove::where('wa_location_and_store_id', $user->wa_location_and_store_id)
                    ->where('wa_inventory_item_id', $item->id)
                    ->sum('qauntity');

                if ($itemQoh > 0) {
                    $html .= '<tr class="SelectedLi" onclick="selectItem(' . $item->id . ', \'' . addslashes($item->title) . '\', \'' . addslashes($item->unit_name ?: '') . '\', ' . $item->selling_price . ', ' . $itemQoh . ')">';
                    $html .= '<td>';
                    $html .= '<strong>' . htmlspecialchars($item->title) . '</strong><br>';
                    $html .= '<small>Unit: ' . htmlspecialchars($item->unit_name ?: 'N/A') . ' | Price: KSh ' . number_format($item->selling_price, 2) . ' | Available: ' . $itemQoh . '</small>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
            }
            
            $html .= '</table>';
            
            return $html;

        } catch (\Exception $e) {
            return '<table class="table table-bordered"><tr><td>Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr></table>';
        }
    }

    /**
     * Get inventory item details for AJAX
     */
    public function getItemDetails(Request $request)
    {
        $user = Auth::user();
        $itemId = $request->get('id'); // Changed from item_id to id to match our AJAX call

        // Get item details with bin location check
        $itemDetails = DB::table('wa_inventory_items as items')
            ->join('wa_inventory_location_uom as bins', 'items.id', '=', 'bins.inventory_id')
            ->leftJoin('wa_unit_of_measures as units', 'items.wa_unit_of_measure_id', '=', 'units.id')
            ->where('items.id', $itemId)
            ->where('bins.location_id', $user->wa_location_and_store_id)
            ->select([
                'items.id',
                'items.title',
                'items.standard_cost',
                'items.selling_price',
                'units.title as unit_name'
            ])
            ->first();

        if (!$itemDetails) {
            return response()->json(['error' => 'Item not found or not available at this location']);
        }

        // Calculate stock quantity using stock moves (same as SalesInvoiceController)
        $itemQoh = WaStockMove::where('wa_location_and_store_id', $user->wa_location_and_store_id)
            ->where('wa_inventory_item_id', $itemId)
            ->sum('qauntity');

        // Return format expected by our JavaScript
        return response()->json([
            'id' => $itemDetails->id,
            'title' => $itemDetails->title,
            'selling_price' => $itemDetails->selling_price ?? 0,
            'unit_name' => $itemDetails->unit_name ?? 'Units',
            'available_quantity' => $itemQoh,
        ]);
    }

    /**
     * Open shift for salesman
     */
    public function openShift(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        // Check if there's already an open shift
        $existingShift = SalesmanShift::where('salesman_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existingShift) {
            return response()->json(['success' => false, 'message' => 'You already have an open shift']);
        }

        $validator = Validator::make($request->all(), [
            'shift_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            $shift = new SalesmanShift();
            $shift->salesman_id = $user->id;
            $userRoute = $user->routes()->first();
            $shift->route_id = $userRoute ? $userRoute->id : null;
            $shift->shift_type = $request->shift_type;
            $shift->start_time = Carbon::now();
            $shift->status = 'open';
            $shift->save();

            // Create corresponding WaShift record for financial data linkage
            $waShift = new WaShift();
            $waShift->shift_id = 'SS-' . $shift->id . '-' . date('Ymd');
            $waShift->salesman_id = $user->id;
            $waShift->route = $userRoute ? $userRoute->route_name : null;
            $waShift->status = 'open';
            $waShift->shift_date = Carbon::now()->toDateString();
            $waShift->save();

            return response()->json([
                'success' => true, 
                'message' => 'Shift opened successfully',
                'shift_id' => $shift->id
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error opening shift: ' . $e->getMessage()]);
        }
    }

    /**
     * Close shift for salesman
     */
    public function closeShift(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $activeShift = SalesmanShift::where('salesman_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (!$activeShift) {
            return response()->json(['success' => false, 'message' => 'No active shift found']);
        }

        try {
            $activeShift->status = 'close';
            $activeShift->closed_time = Carbon::now();
            $activeShift->save();

            // Dispatch jobs to create loading schedule and delivery schedule
            PrepareStoreParkingList::dispatch($activeShift);
            CreateDeliverySchedule::dispatch($activeShift);

            return response()->json([
                'success' => true, 
                'message' => 'Shift closed successfully. Loading sheets and delivery schedules are being generated.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error closing shift: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate discount for inventory item based on quantity
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateItemDiscount(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'inventory_item_id' => 'required|exists:wa_inventory_items,id',
            'item_quantity' => 'required|numeric|min:1',
        ]);
        
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ], 403);
        }

        $inventory_item_id = $request->inventory_item_id;
        $quantity = $request->item_quantity;
        
        $discount = 0;
        $discountDescription = null;
        $discountedPrice = null;
        $originalPrice = null;
        
        // Get the item
        $item = WaInventoryItem::find($inventory_item_id);
        if (!$item) {
            return response()->json([
                'result' => 0,
                'message' => 'Item not found'
            ], 404);
        }
        
        $originalPrice = $item->selling_price;
        
        // Find applicable discount band
        $discountBand = DiscountBand::where('inventory_item_id', $inventory_item_id)
            ->where('status', 'APPROVED')
            ->where(function ($query) use ($quantity) {
                $query->where('from_quantity', '<=', $quantity)
                    ->where(function($q) use ($quantity) {
                        $q->where('to_quantity', '>=', $quantity)
                          ->orWhereNull('to_quantity');
                    });
            })
            ->orderBy('from_quantity', 'desc')
            ->first();
            
        if ($discountBand) {
            $discount = $discountBand->discount_amount * $quantity;
            $discountedPrice = max(0, $item->selling_price - $discountBand->discount_amount);
            $discountDescription = "Discount of KSh {$discountBand->discount_amount} per unit for quantity {$discountBand->from_quantity}" . 
                                 ($discountBand->to_quantity ? " to {$discountBand->to_quantity}" : "+");
        } else {
            $discountedPrice = $originalPrice;
        }
        
        return response()->json([
            'result' => 1,
            'discount' => $discount,
            'discount_description' => $discountDescription,
            'discounted_price' => $discountedPrice,
            'original_price' => $originalPrice,
            'item_id' => $inventory_item_id,
            'quantity' => $quantity
        ]);
    }

    /**
     * Search inventory items for order creation
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchInventory(Request $request)
    {
        try {
            $data = WaInventoryItem::select([
                'wa_inventory_items.*',
                DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id AND wa_stock_moves.wa_location_and_store_id = ' . ($request->store_location_id ?? 'wa_inventory_items.store_location_id') . ') as quantity'),
            ])
                ->join('pack_sizes', 'wa_inventory_items.pack_size_id', '=', 'pack_sizes.id')
                ->where('status', 1)
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('wa_inventory_items.title', 'LIKE', "%$request->search%");
                        $q->orWhere('stock_id_code', 'LIKE', "%$request->search%");
                    }
                })->where(function ($e) use ($request) {
                    if ($request->store_c) {
                        $e->where('store_c_deleted', 0);
                    }
                })->limit(30)->get();

            // Format data for salesman order taking (simpler format)
            $results = [];
            foreach ($data as $item) {
                $qoh = WaStockMove::where('wa_inventory_item_id', $item->id)
                    ->where('wa_location_and_store_id', $request->store_location_id)
                    ->sum('qauntity');
                
                // Check for discount bands
                $discountBands = DiscountBand::where('inventory_item_id', $item->id)
                    ->where('status', 'APPROVED')
                    ->orderBy('from_quantity', 'asc')
                    ->get();
                
                $results[] = [
                    'id' => $item->id,
                    'item_name' => $item->title,
                    'stock_code' => $item->stock_id_code,
                    'unit_name' => $item->pack_size->title ?? 'Unit',
                    'available_stock' => $qoh ?? 0,
                    'selling_price' => $item->selling_price,
                    'discount_bands' => $discountBands->map(function($band) {
                        return [
                            'from_quantity' => $band->from_quantity,
                            'to_quantity' => $band->to_quantity,
                            'discount_amount' => $band->discount_amount,
                            'discounted_price' => max(0, $band->inventoryItem->selling_price - $band->discount_amount)
                        ];
                    })
                ];
            }
            
            return response()->json($results);
            
        } catch (\Exception $e) {
            \Log::error('Error in searchInventory: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'An error occurred while searching inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test search route for debugging
     */
    public function testSearch(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Search route is working!',
            'timestamp' => now()
        ]);
    }

    /**
     * Debug tax calculation for an order
     */
    public function debugTax($orderId)
    {
        $order = WaInternalRequisition::with(['getRelatedItem.getInventoryItemDetail.taxManager'])->find($orderId);
        if (!$order) {
            return "Order not found";
        }
        
        $output = "<h2>Order: " . $order->requisition_no . "</h2><br>";
        
        foreach($order->getRelatedItem as $item) {
            $output .= "<strong>Item:</strong> " . $item->getInventoryItemDetail->title . "<br>";
            $output .= "<strong>Quantity:</strong> " . $item->quantity . "<br>";
            $output .= "<strong>Selling Price:</strong> " . $item->selling_price . "<br>";
            $output .= "<strong>Tax Manager ID:</strong> " . ($item->getInventoryItemDetail->tax_manager_id ?? 'NULL') . "<br>";
            
            if($item->getInventoryItemDetail->taxManager) {
                $output .= "<strong>Tax Manager Title:</strong> " . $item->getInventoryItemDetail->taxManager->title . "<br>";
                $output .= "<strong>Tax Value:</strong> " . $item->getInventoryItemDetail->taxManager->tax_value . "<br>";
                
                // Calculate VAT using SalesInvoiceController formula
                $itemTotal = $item->quantity * $item->selling_price;
                $vatRate = (float)$item->getInventoryItemDetail->taxManager->tax_value;
                $vatAmount = ($vatRate / (100 + $vatRate)) * $itemTotal;
                
                $output .= "<strong>Item Total:</strong> " . $itemTotal . "<br>";
                $output .= "<strong>VAT Rate:</strong> " . $vatRate . "<br>";
                $output .= "<strong>Calculated VAT:</strong> " . number_format($vatAmount, 2) . "<br>";
            } else {
                $output .= "<strong>No Tax Manager assigned</strong><br>";
            }
            
            $output .= "<strong>Stored VAT Amount:</strong> " . ($item->vat_amount ?? 'NULL') . "<br>";
            $output .= "<hr>";
        }
        
        return $output;
    }

    /**
     * Fix VAT calculation for existing orders
     */
    public function fixVatForOrder($orderId)
    {
        $order = WaInternalRequisition::with(['getRelatedItem.getInventoryItemDetail.taxManager'])->find($orderId);
        if (!$order) {
            return "Order not found";
        }
        
        $output = "<h2>Fixing VAT for Order: " . $order->requisition_no . "</h2><br>";
        $updated = 0;
        
        foreach($order->getRelatedItem as $item) {
            if($item->getInventoryItemDetail->taxManager) {
                // Calculate VAT using SalesInvoiceController formula
                $itemTotal = $item->quantity * $item->selling_price;
                $vatRate = (float)$item->getInventoryItemDetail->taxManager->tax_value;
                $vatAmount = ($vatRate / (100 + $vatRate)) * $itemTotal;
                
                // Update the item with correct VAT
                WaInternalRequisitionItem::where('id', $item->id)->update([
                    'vat_rate' => $vatRate,
                    'vat_amount' => $vatAmount
                ]);
                
                $output .= "<strong>Updated:</strong> " . $item->getInventoryItemDetail->title . " - VAT: " . number_format($vatAmount, 2) . "<br>";
                $updated++;
            }
        }
        
        $output .= "<br><strong>Updated {$updated} items with correct VAT calculations.</strong><br>";
        $output .= "<a href='" . route('salesman-orders.show', $orderId) . "'>View Updated Order</a>";
        
        return $output;
    }

    /**
     * Debug loading sheets data
     */
    public function debugLoadingSheets()
    {
        $output = "<h2>Loading Sheets Debug</h2><br>";
        
        // Check recent shifts
        $shifts = \App\SalesmanShift::latest()->limit(5)->get();
        $output .= "<h3>Recent Salesman Shifts:</h3>";
        
        foreach($shifts as $shift) {
            $output .= "<strong>Shift ID:</strong> {$shift->id}<br>";
            $output .= "<strong>Status:</strong> {$shift->status}<br>";
            $output .= "<strong>Date:</strong> {$shift->created_at}<br>";
            
            // Check dispatches
            $dispatches = \App\SalesmanShiftStoreDispatch::where('shift_id', $shift->id)->get();
            $output .= "<strong>Dispatches:</strong> " . $dispatches->count() . "<br>";
            
            foreach($dispatches as $dispatch) {
                $output .= "&nbsp;&nbsp;- Dispatch ID: {$dispatch->id}, Dispatched: " . ($dispatch->dispatched ? 'Yes' : 'No') . "<br>";
            }
            
            // Check orders
            $orders = \App\Model\WaInternalRequisition::where('wa_shift_id', $shift->id)->count();
            $output .= "<strong>Orders:</strong> {$orders}<br>";
            
            $output .= "<hr>";
        }
        
        // Check all dispatches
        $allDispatches = \App\SalesmanShiftStoreDispatch::where('dispatched', false)->count();
        $output .= "<h3>Total Undispatched Loading Sheets:</h3>";
        $output .= "<strong>Count:</strong> {$allDispatches}<br><br>";
        
        if($allDispatches > 0) {
            $output .= "<strong>Undispatched Sheets:</strong><br>";
            $undispatched = \App\SalesmanShiftStoreDispatch::where('dispatched', false)->with('shift')->get();
            foreach($undispatched as $dispatch) {
                $output .= "- Shift ID: {$dispatch->shift_id}, Dispatch ID: {$dispatch->id}, Store ID: {$dispatch->store_id}<br>";
            }
        }
        
        return $output;
    }

    /**
     * Generate loading sheets for existing closed shifts
     */
    public function generateLoadingSheets($shiftId = null)
    {
        $output = "<h2>Generate Loading Sheets</h2><br>";
        
        if ($shiftId) {
            // Generate for specific shift
            $shift = SalesmanShift::find($shiftId);
            if (!$shift) {
                return "Shift not found";
            }
            
            $shifts = collect([$shift]);
            $output .= "<h3>Generating for Shift ID: {$shiftId}</h3>";
        } else {
            // Generate for recent closed shifts without dispatches
            $shifts = SalesmanShift::where('status', 'close')
                ->whereDoesntHave('dispatches')
                ->latest()
                ->limit(10)
                ->get();
            
            $output .= "<h3>Generating for {$shifts->count()} recent closed shifts without loading sheets:</h3>";
        }
        
        $generated = 0;
        
        foreach($shifts as $shift) {
            try {
                // Check if shift has orders
                $orderCount = WaInternalRequisition::where('wa_shift_id', $shift->id)->count();
                
                if ($orderCount > 0) {
                    // Dispatch the job
                    PrepareStoreParkingList::dispatch($shift);
                    CreateDeliverySchedule::dispatch($shift);
                    
                    $output .= "<strong>✓ Generated:</strong> Shift ID {$shift->id} ({$orderCount} orders)<br>";
                    $generated++;
                } else {
                    $output .= "<strong>⚠ Skipped:</strong> Shift ID {$shift->id} (no orders)<br>";
                }
            } catch (\Exception $e) {
                $output .= "<strong>✗ Error:</strong> Shift ID {$shift->id} - {$e->getMessage()}<br>";
            }
        }
        
        $output .= "<br><strong>Generated loading sheets for {$generated} shifts.</strong><br>";
        $output .= "<br><a href='/admin/store-loading-sheets'>View Loading Sheets</a>";
        
        return $output;
    }

    /**
     * Test the mobile API shift closing logic for existing shifts
     */
    public function testMobileShiftClosing($shiftId)
    {
        $output = "<h2>Test Mobile API Shift Closing Logic</h2><br>";
        
        // Find the WaShift record (mobile API uses WaShift table)
        $waShift = \App\Model\WaShift::find($shiftId);
        if (!$waShift) {
            return "WaShift not found for ID: {$shiftId}";
        }
        
        $output .= "<strong>WaShift Found:</strong> ID {$waShift->id}, Status: {$waShift->status}<br>";
        $output .= "<strong>Salesman ID:</strong> {$waShift->salesman_id}<br>";
        
        // Check the relationship to SalesmanShift
        $shift = $waShift->salesmanShift;
        if (!$shift) {
            $output .= "<strong>❌ Issue Found:</strong> No SalesmanShift record found for WaShift ID {$shiftId}<br>";
            $output .= "<strong>This explains why loading sheets weren't generated!</strong><br><br>";
            
            // Check if there's a SalesmanShift with matching salesman_id and date
            $possibleShifts = \App\SalesmanShift::where('salesman_id', $waShift->salesman_id)
                ->whereDate('created_at', $waShift->created_at->toDateString())
                ->get();
                
            if ($possibleShifts->count() > 0) {
                $output .= "<strong>Possible SalesmanShift matches found:</strong><br>";
                foreach($possibleShifts as $possibleShift) {
                    $output .= "- SalesmanShift ID: {$possibleShift->id}, Status: {$possibleShift->status}<br>";
                }
            }
        } else {
            $output .= "<strong>✅ SalesmanShift Found:</strong> ID {$shift->id}, Status: {$shift->status}<br>";
            
            // Test dispatching the jobs
            try {
                PrepareStoreParkingList::dispatch($shift);
                CreateDeliverySchedule::dispatch($shift);
                $output .= "<strong>✅ Jobs dispatched successfully!</strong><br>";
            } catch (\Exception $e) {
                $output .= "<strong>❌ Error dispatching jobs:</strong> {$e->getMessage()}<br>";
            }
        }
        
        return $output;
    }

    /**
     * Debug delivery schedules and the entire shift closing journey
     */
    public function debugEntireJourney()
    {
        $output = "<h2>Complete Shift Closing Journey Debug</h2><br>";
        
        // Check recent shifts
        $shifts = \App\SalesmanShift::latest()->limit(5)->get();
        $output .= "<h3>Recent Salesman Shifts:</h3>";
        
        foreach($shifts as $shift) {
            $output .= "<strong>Shift ID:</strong> {$shift->id}<br>";
            $output .= "<strong>Status:</strong> {$shift->status}<br>";
            $output .= "<strong>Date:</strong> {$shift->created_at}<br>";
            $output .= "<strong>Salesman ID:</strong> {$shift->salesman_id}<br>";
            
            // Check orders
            $orders = \App\Model\WaInternalRequisition::where('wa_shift_id', $shift->id)->count();
            $output .= "<strong>Orders:</strong> {$orders}<br>";
            
            // Check dispatches (loading sheets)
            $dispatches = \App\SalesmanShiftStoreDispatch::where('shift_id', $shift->id)->get();
            $output .= "<strong>Loading Sheets (Dispatches):</strong> " . $dispatches->count() . "<br>";
            
            // Check delivery schedules
            $deliverySchedules = \App\DeliverySchedule::where('shift_id', $shift->id)->get();
            $output .= "<strong>Delivery Schedules:</strong> " . $deliverySchedules->count() . "<br>";
            
            if ($deliverySchedules->count() > 0) {
                foreach($deliverySchedules as $schedule) {
                    $output .= "&nbsp;&nbsp;- Schedule ID: {$schedule->id}, Status: {$schedule->status}<br>";
                }
            }
            
            // Check if salesman has store location
            if ($shift->salesman) {
                $output .= "<strong>Salesman Store ID:</strong> " . ($shift->salesman->wa_location_and_store_id ?? 'NULL') . "<br>";
            }
            
            $output .= "<hr>";
        }
        
        // Check all delivery schedules
        $allDeliverySchedules = \App\DeliverySchedule::latest()->limit(10)->get();
        $output .= "<h3>Recent Delivery Schedules (All):</h3>";
        
        foreach($allDeliverySchedules as $schedule) {
            $output .= "<strong>Schedule ID:</strong> {$schedule->id}<br>";
            $output .= "<strong>Shift ID:</strong> {$schedule->shift_id}<br>";
            $output .= "<strong>Status:</strong> {$schedule->status}<br>";
            $output .= "<strong>Date:</strong> {$schedule->created_at}<br>";
            $output .= "<hr>";
        }
        
        // Check queue jobs
        $output .= "<h3>Queue Status:</h3>";
        try {
            $failedJobs = \DB::table('failed_jobs')->count();
            $output .= "<strong>Failed Jobs:</strong> {$failedJobs}<br>";
            
            if ($failedJobs > 0) {
                $recentFailures = \DB::table('failed_jobs')->latest()->limit(5)->get();
                $output .= "<strong>Recent Failed Jobs:</strong><br>";
                foreach($recentFailures as $job) {
                    $output .= "- {$job->payload} (Failed: {$job->failed_at})<br>";
                }
            }
        } catch (\Exception $e) {
            $output .= "<strong>Queue table not accessible:</strong> {$e->getMessage()}<br>";
        }
        
        return $output;
    }

    /**
     * Debug POS customer creation issue
     */
    public function debugPosCustomer()
    {
        $output = "<h2>POS Customer Creation Debug</h2><br>";
        
        // Check if default customer exists
        $defaultCustomer = \App\Model\WaCustomer::where('customer_code', 'CUST-00001')->first();
        
        if ($defaultCustomer) {
            $output .= "<strong>✅ Default Customer Found:</strong><br>";
            $output .= "- Customer Code: {$defaultCustomer->customer_code}<br>";
            $output .= "- Customer Name: {$defaultCustomer->customer_name}<br>";
            $output .= "- ID: {$defaultCustomer->id}<br>";
        } else {
            $output .= "<strong>❌ Default Customer NOT Found:</strong> CUST-00001<br>";
            $output .= "<strong>This is why POS customer creation is failing!</strong><br><br>";
            
            // Check what customers exist
            $customers = \App\Model\WaCustomer::limit(10)->get();
            $output .= "<strong>Existing Customers (first 10):</strong><br>";
            foreach($customers as $customer) {
                $output .= "- {$customer->customer_code}: {$customer->customer_name}<br>";
            }
            
            // Suggest creating the default customer
            $output .= "<br><strong>Solution:</strong> Create default customer CUST-00001<br>";
            $output .= "<a href='/admin/salesman-orders/create-default-pos-customer' style='background: green; color: white; padding: 10px; text-decoration: none;'>Create Default Customer</a>";
        }
        
        // Check POS routes
        $user = auth()->user();
        $posRoute = \App\Model\Route::where('is_pos_route', true)
            ->where('restaurant_id', $user->restaurant_id ?? 1)
            ->first();
            
        $output .= "<br><br><strong>POS Route Check:</strong><br>";
        if ($posRoute) {
            $output .= "✅ POS Route Found: {$posRoute->route_name} (ID: {$posRoute->id})<br>";
        } else {
            $output .= "❌ No POS Route found for restaurant ID: " . ($user->restaurant_id ?? 1) . "<br>";
        }
        
        return $output;
    }

    /**
     * Create default POS customer
     */
    public function createDefaultPosCustomer()
    {
        try {
            // Check if already exists
            $existing = \App\Model\WaCustomer::where('customer_code', 'CUST-00001')->first();
            if ($existing) {
                return "Default customer CUST-00001 already exists!";
            }
            
            // Create default customer
            $customer = new \App\Model\WaCustomer();
            $customer->customer_code = 'CUST-00001';
            $customer->customer_name = 'Default POS Customer';
            $customer->telephone = '0700000000';
            $customer->email = 'pos@default.com';
            $customer->town = 'Default Town';
            $customer->address = 'Default Location';
            $customer->contact_person = 'POS Admin';
            $customer->country = 'Kenya';
            $customer->customer_since = now()->toDateString();
            $customer->credit_limit = 0.00;
            $customer->is_blocked = false;
            $customer->delivery_centres_id = 1; // Default delivery center
            $customer->save();
            
            return "<h2>✅ Success!</h2><br>Default POS customer CUST-00001 created successfully!<br><br><a href='/admin/pos-cash-sales/create'>Test POS Customer Creation</a>";
            
        } catch (\Exception $e) {
            return "<h2>❌ Error!</h2><br>Failed to create default customer: " . $e->getMessage();
        }
    }

    /**
     * Test discount calculation for debugging
     * @param int $itemId
     * @param int $quantity
     * @return \Illuminate\Http\JsonResponse
     */
    public function testDiscount($itemId, $quantity)
    {
        $item = WaInventoryItem::find($itemId);
        if (!$item) {
            return response()->json(['error' => 'Item not found']);
        }

        $discountBands = DiscountBand::where('inventory_item_id', $itemId)
            ->where('status', 'APPROVED')
            ->orderBy('from_quantity', 'asc')
            ->get();

        $applicableDiscount = DiscountBand::where('inventory_item_id', $itemId)
            ->where('status', 'APPROVED')
            ->where(function ($query) use ($quantity) {
                $query->where('from_quantity', '<=', $quantity)
                    ->where(function($q) use ($quantity) {
                        $q->where('to_quantity', '>=', $quantity)
                          ->orWhereNull('to_quantity');
                    });
            })
            ->orderBy('from_quantity', 'desc')
            ->first();

        return response()->json([
            'item' => $item->title,
            'quantity' => $quantity,
            'original_price' => $item->selling_price,
            'all_discount_bands' => $discountBands,
            'applicable_discount' => $applicableDiscount,
            'discounted_price' => $applicableDiscount ? 
                max(0, $item->selling_price - $applicableDiscount->discount_amount) : 
                $item->selling_price
        ]);
    }
    /**
 * Apply promotions to salesman order
 */
private function applyPromotionsToSalesmanOrder($list)
{
    $today = \Carbon\Carbon::today();
    
    // Get all items in the order
    $orderItems = $list->getRelatedItem;
    
    foreach ($orderItems as $orderItem) {
        $inventoryItem = $orderItem->getInventoryItemDetail;
        
        // Check for active "Buy X Get Y Free" promotions for this item
        $promotion = ItemPromotion::where('inventory_item_id', $inventoryItem->id)
            ->where('status', 'active')
            ->whereNotNull('promotion_item_id')
            ->where(function ($query) use ($today) {
                $query->where('from_date', '<=', $today)
                    ->where(function ($subQuery) use ($today) {
                        $subQuery->where('to_date', '>=', $today)
                                 ->orWhereNull('to_date');
                    });
            })
            ->with(['promotionType', 'promotionItem'])
            ->first();
        
        if ($promotion && $promotion->promotionType && 
            $promotion->promotionType->description == PromotionMatrix::BSGY->value) {
            
            // Calculate how many free items the customer should get
            $saleQuantity = $promotion->sale_quantity;
            $promotionQuantity = $promotion->promotion_quantity;
            $orderedQuantity = $orderItem->quantity;
            
            if ($orderedQuantity >= $saleQuantity) {
                $freeItemsEarned = floor($orderedQuantity / $saleQuantity) * $promotionQuantity;
                
                if ($freeItemsEarned > 0) {
                    // Check if free item already exists in the order
                    $existingFreeItem = $orderItems->where('wa_inventory_item_id', $promotion->promotion_item_id)
                                                 ->where('selling_price', 0)
                                                 ->first();
                    
                    if (!$existingFreeItem) {
                        // Create new free item entry
                        $freeItem = new \App\Model\WaInternalRequisitionItem();
                        $freeItem->wa_internal_requisition_id = $list->id;
                        $freeItem->wa_inventory_item_id = $promotion->promotion_item_id;
                        $freeItem->quantity = $freeItemsEarned;
                        $freeItem->selling_price = 0;
                        $freeItem->total_cost = 0;
                        $freeItem->total_cost_with_vat = 0;
                        $freeItem->vat_amount = 0;
                        $freeItem->discount = 0;
                        $freeItem->save();
                        
                        // Add to the collection for immediate display
                        $list->getRelatedItem->push($freeItem);
                    } else {
                        // Update existing free item quantity
                        $existingFreeItem->quantity = $freeItemsEarned;
                        $existingFreeItem->save();
                    }
                    
                    \Log::info("Promotion applied to salesman order {$list->id}: {$freeItemsEarned} free items added for promotion {$promotion->id}");
                }
            }
        }
    }
}
}
