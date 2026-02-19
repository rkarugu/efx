<?php

namespace App\Http\Controllers\Admin;

use App\DeliverySchedule;
use App\DeliveryScheduleCustomer;
use App\DeliveryScheduleItem;
use App\Http\Controllers\Controller;
use App\Model\Route;
use App\Model\User;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\WaInventoryLocationTransferItemReturn;
use App\Model\WaRouteCustomer;
use App\Model\WaCustomer;
use App\Model\WaUnitOfMeasure;
use App\Model\WaDebtorTran;
use App\Model\PaymentMethod;
use App\InvoicePayment;
use App\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeliveryDriverController extends Controller
{
    /**
     * Delivery Driver Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return redirect()->route('admin.login')->with('error', 'Access denied. Delivery drivers only.');
        }

        $title = 'Delivery Dashboard';
        
        // Get active delivery schedule for this driver
        $activeSchedule = DeliverySchedule::with(['route', 'vehicle', 'customers.routeCustomer', 'items.inventoryItem'])
            ->where('driver_id', $user->id)
            ->whereNotIn('status', ['finished'])
            ->latest()
            ->first();

        // Get today's statistics
        $todayStats = $this->getTodayStats($user->id);
        
        // Get recent delivery history
        $recentDeliveries = DeliverySchedule::with(['route'])
            ->where('driver_id', $user->id)
            ->where('status', 'finished')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.delivery_driver.dashboard', compact(
            'title', 
            'activeSchedule', 
            'todayStats', 
            'recentDeliveries'
        ));
    }

    /**
     * Mobile App Interface
     */
    public function mobileApp()
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return redirect()->route('admin.login')->with('error', 'Access denied. Delivery drivers only.');
        }

        // Get dashboard data using the same logic as the API
        $dashboardData = $this->getDashboardDataForWeb($user->id);

        return view('admin.delivery_driver.mobile_app', compact('dashboardData'));
    }


    /**
     * Show delivery schedule details
     */
    public function showSchedule($id)
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return redirect()->route('admin.login')->with('error', 'Access denied.');
        }

        $schedule = DeliverySchedule::with([
            'route', 
            'vehicle', 
            'shift',
            'customers.routeCustomer', 
            'items.inventoryItem'
        ])
        ->where('driver_id', $user->id)
        ->findOrFail($id);

        $title = 'Delivery Schedule - ' . $schedule->delivery_number;

        return view('admin.delivery_driver.schedule_details', compact('title', 'schedule'));
    }

    /**
     * Start delivery schedule
     */
    public function startDelivery(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            DB::beginTransaction();

            $schedule = DeliverySchedule::where('driver_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($schedule->status !== 'loaded') {
                return response()->json([
                    'success' => false, 
                    'message' => 'Schedule must be in loaded status to start delivery'
                ]);
            }

            $schedule->update([
                'status' => 'in_progress',
                'loading_time' => Carbon::now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery started successfully',
                'schedule_status' => 'in_progress'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error starting delivery: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error starting delivery']);
        }
    }

    /**
     * Mark items as received by driver
     */
    public function receiveItems(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:delivery_schedules,id',
            'item_ids' => 'required|array',
            'item_ids.*' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            $schedule = DeliverySchedule::where('driver_id', $user->id)
                ->findOrFail($request->schedule_id);

            // Mark internal requisition items as received by driver
            $orderIds = $schedule->customers()->pluck('order_id')->toArray();
            $allOrderIds = [];
            
            foreach ($orderIds as $orderIdString) {
                $ids = explode(',', $orderIdString);
                $allOrderIds = array_merge($allOrderIds, $ids);
            }

            WaInternalRequisitionItem::whereIn('wa_internal_requisition_id', $allOrderIds)
                ->whereIn('wa_inventory_item_id', $request->item_ids)
                ->update(['driver_item_received' => 1]);

            // Update schedule status if not already in progress
            if ($schedule->status === 'consolidated') {
                $schedule->update(['status' => 'loaded']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items marked as received successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error receiving items: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error receiving items']);
        }
    }

    /**
     * Prompt delivery completion for customer
     */
    public function promptDeliveryCompletion(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:wa_route_customers,id',
            'delivery_items' => 'sometimes|array',
            'delivery_items.*.item_id' => 'required|integer',
            'delivery_items.*.delivery_quantity' => 'required|integer|min:0',
            'delivery_items.*.returned_quantity' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            // Find active schedule
            $activeSchedule = DeliverySchedule::where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();

            if (!$activeSchedule) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No active delivery in progress'
                ]);
            }

            // Find customer in schedule
            $customer = $activeSchedule->customers()
                ->where('customer_id', $request->customer_id)
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Customer not found in active schedule'
                ]);
            }

            // Generate delivery code
            $deliveryCode = mt_rand(100000, 999999);

            // Update customer with delivery code
            $customer->update([
                'delivery_code' => $deliveryCode,
                'delivery_code_status' => 'sent',
                'delivery_prompted_at' => Carbon::now()
            ]);
            
            // Process delivery items if provided
            if ($request->has('delivery_items') && is_array($request->delivery_items)) {
                // Get order IDs for this customer
                $orderIds = [];
                if (!empty($customer->order_id)) {
                    $orderIds = explode(',', $customer->order_id);
                }
                
                // Process each delivery item
                foreach ($request->delivery_items as $deliveryItem) {
                    $itemId = $deliveryItem['item_id'];
                    $deliveryQuantity = $deliveryItem['delivery_quantity'] ?? 0;
                    $returnedQuantity = $deliveryItem['returned_quantity'] ?? 0;
                    $isDelivered = $deliveryItem['is_delivered'] ?? ($deliveryQuantity > 0);
                    $isReturned = $deliveryItem['is_returned'] ?? ($returnedQuantity > 0);
                    
                    // Find the item in the customer's orders
                    $item = WaInternalRequisitionItem::find($itemId);
                    
                    if ($item && in_array($item->wa_internal_requisition_id, $orderIds)) {
                        // Update the item with delivery and return status
                        $item->update([
                            'delivered' => $isDelivered,
                            'is_returned' => $isReturned
                        ]);
                        
                        // Create return record if there are returns
                        if ($isReturned && $returnedQuantity > 0) {
                            // Find the transfer item
                            $transferItem = WaInventoryLocationTransferItem::where('wa_internal_requisition_item_id', $itemId)->first();
                            
                            if ($transferItem) {
                                // Create or update return record
                                WaInventoryLocationTransferItemReturn::updateOrCreate(
                                    [
                                        'wa_inventory_location_transfer_item_id' => $transferItem->id
                                    ],
                                    [
                                        'wa_inventory_location_transfer_id' => $transferItem->wa_inventory_location_transfer_id,
                                        'return_quantity' => $returnedQuantity,
                                        'received_quantity' => $returnedQuantity,
                                        'created_by' => $user->id,
                                        'updated_by' => $user->id
                                    ]
                                );
                            }
                        }
                        
                        // Log the delivery details
                        Log::info('Item delivery updated', [
                            'item_id' => $itemId,
                            'customer_id' => $request->customer_id,
                            'delivered' => $isDelivered,
                            'returned' => $isReturned,
                            'returned_quantity' => $returnedQuantity
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery prompted successfully',
                'data' => [
                    'delivery_code' => $deliveryCode
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error prompting delivery: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error prompting delivery: ' . $e->getMessage()]);
        }
    }

    /**
     * Verify delivery code
     */
    public function verifyDeliveryCode(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:wa_route_customers,id',
            'delivery_code' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        try {
            DB::beginTransaction();

            // Find active schedule
            $activeSchedule = DeliverySchedule::where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();

            if (!$activeSchedule) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No active delivery in progress'
                ]);
            }

            // Find customer in schedule
            $customer = $activeSchedule->customers()
                ->where('customer_id', $request->customer_id)
                ->first();

            if (!$customer) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Customer not found in active schedule'
                ]);
            }

            // Verify delivery code
            if ($customer->delivery_code != $request->delivery_code) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Invalid delivery code'
                ]);
            }

            // Mark customer as delivered
            $customer->update([
                'delivered_at' => Carbon::now(),
                'delivery_code_status' => 'approved'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery code verified successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error verifying delivery code: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error verifying delivery code']);
        }
    }

    /**
     * Complete delivery schedule
     */
    public function completeSchedule(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        try {
            DB::beginTransaction();

            $schedule = DeliverySchedule::with('customers')
                ->where('driver_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            if ($schedule->status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule must be in progress to complete'
                ]);
            }

            // Check if all customers have been delivered to
            $undeliveredCustomers = $schedule->customers()
                ->whereNull('delivered_at')
                ->count();

            if ($undeliveredCustomers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "You have {$undeliveredCustomers} undelivered customers remaining"
                ]);
            }

            $schedule->update([
                'status' => 'finished',
                'actual_delivery_date' => Carbon::now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery schedule completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing delivery schedule: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error completing delivery schedule']);
        }
    }

    /**
     * Get delivery history
     */
    public function history()
    {
        $user = Auth::user();
        
        if (!$this->isDeliveryDriver($user)) {
            return redirect()->route('admin.login')->with('error', 'Access denied.');
        }

        $title = 'Delivery History';
        
        $deliveries = DeliverySchedule::with(['route', 'vehicle'])
            ->where('driver_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.delivery_driver.history', compact('title', 'deliveries'));
    }

    /**
     * Get customer items for delivery
     */
    public function getCustomerItems(Request $request)
    {
        try {
            $customerId = $request->input('customer_id');
            
            // Find the customer in the active delivery schedule
            $user = auth()->user();
            $activeSchedule = DeliverySchedule::with([
                'customers.routeCustomer'
            ])
                ->where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();
                
            if (!$activeSchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active delivery schedule found'
                ]);
            }
            
            $customer = $activeSchedule->customers->firstWhere('customer_id', $customerId);
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found in active schedule'
                ]);
            }
            
            // Get customer items from orders
            $items = [];
            $totalAmount = 0;
            
            // Check if order_id exists and is not empty
            if (!empty($customer->order_id)) {
                $orderIds = explode(',', $customer->order_id);
                
                foreach ($orderIds as $orderId) {
                    if (empty($orderId)) continue;
                    
                    $order = WaInternalRequisition::find($orderId);
                    if (!$order) continue;
                    
                    $orderItems = WaInternalRequisitionItem::where('wa_internal_requisition_id', $orderId)->get();
                    
                    foreach ($orderItems as $item) {
                        $inventoryItem = $item->getInventoryItemDetail;
                        $itemName = $inventoryItem ? ($inventoryItem->item_name ?? $inventoryItem->title ?? 'Unknown Item') : 'Unknown Item';
                        $unit = 'Units';
                        
                        if ($inventoryItem && isset($inventoryItem->packSize)) {
                            $unit = $inventoryItem->packSize->pack_name ?? 'Units';
                        } elseif ($inventoryItem && isset($inventoryItem->pack_size)) {
                            $unit = $inventoryItem->pack_size->pack_name ?? 'Units';
                        }
                        
                        // Get returned quantity if any
                        $returnedQuantity = $item->returnedQuantity();
                        
                        // Log for debugging
                        \Log::info('Item returned quantity', [
                            'item_id' => $item->id,
                            'item_name' => $itemName,
                            'original_quantity' => $item->quantity,
                            'returned_quantity' => $returnedQuantity
                        ]);
                        
                        // Calculate item amount after returns
                        $itemOriginalAmount = $item->total_cost_with_vat ?? 0;
                        $itemReturnedAmount = 0;
                        
                        if ($returnedQuantity > 0 && $item->quantity > 0) {
                            // Calculate proportional return value
                            $itemReturnedAmount = ($returnedQuantity / $item->quantity) * $itemOriginalAmount;
                        }
                        
                        // Add net amount (original - returned) to total
                        $totalAmount += ($itemOriginalAmount - $itemReturnedAmount);
                        
                        $items[] = [
                            'id' => $item->id,
                            'item_name' => $itemName,
                            'quantity' => $item->quantity,
                            'unit' => $unit,
                            'returned_quantity' => $returnedQuantity,
                            'is_delivered' => (bool)$item->delivered,
                            'is_returned' => (bool)$item->is_returned
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'customer_id' => $customerId,
                    'customer_name' => $customer->routeCustomer->bussiness_name ?? 'Unknown Customer',
                    'items' => $items,
                    'total_amount' => number_format($totalAmount, 2, '.', '')
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting customer items: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting customer items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's statistics for driver
     */
    private function getTodayStats($driverId): array
    {
        $today = Carbon::today();
        
        $completedToday = DeliverySchedule::where('driver_id', $driverId)
            ->where('status', 'finished')
            ->whereDate('actual_delivery_date', $today)
            ->count();

        $activeSchedules = DeliverySchedule::where('driver_id', $driverId)
            ->whereNotIn('status', ['finished'])
            ->count();

        $totalCustomersToday = DeliveryScheduleCustomer::whereHas('deliverySchedule', function($query) use ($driverId, $today) {
            $query->where('driver_id', $driverId)
                ->whereDate('created_at', $today);
        })->count();

        $deliveredCustomersToday = DeliveryScheduleCustomer::whereHas('deliverySchedule', function($query) use ($driverId, $today) {
            $query->where('driver_id', $driverId)
                ->whereDate('created_at', $today);
        })->whereNotNull('delivered_at')->count();

        return [
            'completed_deliveries' => $completedToday,
            'active_schedules' => $activeSchedules,
            'total_customers' => $totalCustomersToday,
            'delivered_customers' => $deliveredCustomersToday,
            'pending_customers' => $totalCustomersToday - $deliveredCustomersToday
        ];
    }

    /**
     * Get dashboard data for web interface (mirrors API logic)
     */
    private function getDashboardDataForWeb($driverId): array
    {
        // Get active delivery schedule
        $activeSchedule = DeliverySchedule::with([
            'route', 
            'vehicle', 
            'customers.routeCustomer.route',
            'customers.routeCustomer.center',
            'items.inventoryItem.packSize',
            'items.inventoryItem.binLocation'
        ])
            ->where('driver_id', $driverId)
            ->whereNotIn('status', ['finished'])
            ->latest()
            ->first();

        // Get today's statistics
        $todayStats = $this->getTodayStats($driverId);

        // Get recent deliveries
        $recentDeliveries = DeliverySchedule::with(['route'])
            ->where('driver_id', $driverId)
            ->where('status', 'finished')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Calculate financial data for active schedule
        $totalAmount = 0;
        $collectedAmount = 0;
        $pendingAmount = 0;
        
        $shiftIds = [];
        $routeNameDisplay = null;
        $itemsGrouped = [];
        $itemsGroupedCount = 0;

        if ($activeSchedule) {
            $shiftIds = $activeSchedule->shifts()->pluck('salesman_shifts.id')->toArray();
            if ($activeSchedule->shift_id) {
                $shiftIds[] = (int)$activeSchedule->shift_id;
            }
            $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));

            $shifts = $activeSchedule->shifts()->with(['relatedRoute'])->get();
            if ($activeSchedule->shift_id && !$shifts->pluck('id')->contains((int)$activeSchedule->shift_id)) {
                $shift = \App\SalesmanShift::with(['relatedRoute'])->find($activeSchedule->shift_id);
                if ($shift) {
                    $shifts = $shifts->push($shift);
                }
            }
            if ($shifts->isEmpty() && $activeSchedule->shift_id) {
                $shift = \App\SalesmanShift::with(['relatedRoute'])->find($activeSchedule->shift_id);
                $shifts = $shift ? collect([$shift]) : collect();
            }

            $this->ensureScheduleCustomersAndItemsFromShifts($activeSchedule, $shiftIds);
            $activeSchedule->load([
                'customers.routeCustomer.route',
                'customers.routeCustomer.center',
                'items.inventoryItem.packSize',
                'items.inventoryItem.binLocation'
            ]);

            $routeNames = $shifts->map(function ($shift) {
                return $shift?->relatedRoute?->route_name;
            })->filter()->unique()->values();
            $routeNameDisplay = $routeNames->isNotEmpty()
                ? $routeNames->implode(', ')
                : ($activeSchedule->route->route_name ?? 'N/A');

            $routeGroups = [];
            foreach ($shifts as $shift) {
                if (!$shift) {
                    continue;
                }

                $routeKey = (string)($shift->relatedRoute?->id ?? 'unknown');
                if (!isset($routeGroups[$routeKey])) {
                    $routeGroups[$routeKey] = [
                        'route_id' => $shift->relatedRoute?->id,
                        'route_name' => $shift->relatedRoute?->route_name ?? 'Unknown Route',
                        'shift_ids' => [],
                        'shift_names' => [],
                        'items_by_id' => [],
                    ];
                }

                $routeGroups[$routeKey]['shift_ids'][] = (int)$shift->id;
                $routeGroups[$routeKey]['shift_names'][] = $shift->shift_id ?? "Shift {$shift->id}";

                $shiftItems = DB::table('wa_internal_requisition_items as order_items')
                    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                    ->where('orders.wa_shift_id', (int)$shift->id)
                    ->groupBy('order_items.wa_inventory_item_id')
                    ->select('order_items.wa_inventory_item_id as item_id', DB::raw('SUM(order_items.quantity) as total_quantity'))
                    ->get();

                $itemIds = $shiftItems->pluck('item_id')->filter()->unique()->values()->all();
                $inventoryItems = WaInventoryItem::with(['packSize', 'binLocation'])
                    ->whereIn('id', $itemIds)
                    ->get()
                    ->keyBy('id');

                foreach ($shiftItems as $shiftItem) {
                    $itemId = (int)$shiftItem->item_id;
                    $qty = (float)$shiftItem->total_quantity;

                    if (!isset($routeGroups[$routeKey]['items_by_id'][$itemId])) {
                        $inventoryItem = $inventoryItems->get($itemId);

                        $binLocation = 'N/A';
                        if ($inventoryItem && $inventoryItem->binLocation) {
                            $binUom = WaUnitOfMeasure::find($inventoryItem->binLocation->uom_id);
                            $binLocation = $binUom ? $binUom->title : 'N/A';
                        }

                        $unit = 'Units';
                        if ($inventoryItem && isset($inventoryItem->packSize)) {
                            $unit = $inventoryItem->packSize->pack_name ?? 'Units';
                        } elseif ($inventoryItem && isset($inventoryItem->pack_size)) {
                            $unit = $inventoryItem->pack_size->pack_name ?? 'Units';
                        }

                        $routeGroups[$routeKey]['items_by_id'][$itemId] = [
                            'inventory_item_id' => $itemId,
                            'item_name' => $inventoryItem?->item_name ?? $inventoryItem?->title ?? 'Unknown Item',
                            'quantity' => 0,
                            'unit' => $unit,
                            'bin_location' => $binLocation,
                        ];
                    }

                    $routeGroups[$routeKey]['items_by_id'][$itemId]['quantity'] += $qty;
                }
            }

            $itemsGrouped = collect($routeGroups)->map(function ($routeGroup) {
                return [
                    'route_id' => $routeGroup['route_id'],
                    'route_name' => $routeGroup['route_name'],
                    'shift_ids' => array_values(array_unique($routeGroup['shift_ids'])),
                    'shift_names' => array_values(array_unique($routeGroup['shift_names'])),
                    'items' => array_values($routeGroup['items_by_id']),
                ];
            })->values()->all();

            $itemsGroupedCount = collect($itemsGrouped)->sum(function ($group) {
                return is_array($group['items'] ?? null) ? count($group['items']) : 0;
            });
            
            // Calculate total amount from all orders in these shifts
            $totalAmount = DB::table('wa_internal_requisition_items as order_items')
                ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                ->whereIn('orders.wa_shift_id', $shiftIds)
                ->sum('order_items.total_cost_with_vat');
            
            // Subtract total returned items value
            $totalReturnedAmount = DB::table('sale_order_returns as returns')
                ->join('wa_internal_requisition_items as order_items', 'returns.wa_internal_requisition_item_id', '=', 'order_items.id')
                ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                ->whereIn('orders.wa_shift_id', $shiftIds)
                ->sum(DB::raw('(returns.quantity / order_items.quantity) * order_items.total_cost_with_vat'));
            
            $totalAmount = max(0, $totalAmount - $totalReturnedAmount);
            
            // Calculate collected amount from delivered customers (excluding payment skipped)
            $deliveredCustomerIds = $activeSchedule->customers()
                ->whereNotNull('delivered_at')
                ->where(function($query) {
                    $query->whereNull('payment_method')
                          ->orWhere('payment_method', '!=', 'skip');
                })
                ->pluck('customer_id')
                ->toArray();

            // Log for debugging
            $allDeliveredCustomers = $activeSchedule->customers()
                ->whereNotNull('delivered_at')
                ->get(['customer_id', 'payment_method', 'collection_amount', 'delivered_at']);
            
            Log::info('Collection calculation debug', [
                'schedule_id' => $activeSchedule->id,
                'all_delivered_customers' => $allDeliveredCustomers->toArray(),
                'customers_with_payment' => $deliveredCustomerIds,
                'total_delivered' => $allDeliveredCustomers->count(),
                'customers_for_collection' => count($deliveredCustomerIds)
            ]);

            if (!empty($deliveredCustomerIds)) {
                $collectedAmount = DB::table('wa_internal_requisition_items as order_items')
                    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                    ->whereIn('orders.wa_shift_id', $shiftIds)
                    ->whereIn('orders.wa_route_customer_id', $deliveredCustomerIds)
                    ->sum('order_items.total_cost_with_vat');
                
                // Subtract returned items value from collected amount
                $collectedReturnedAmount = DB::table('sale_order_returns as returns')
                    ->join('wa_internal_requisition_items as order_items', 'returns.wa_internal_requisition_item_id', '=', 'order_items.id')
                    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                    ->whereIn('orders.wa_shift_id', $shiftIds)
                    ->whereIn('orders.wa_route_customer_id', $deliveredCustomerIds)
                    ->sum(DB::raw('(returns.quantity / order_items.quantity) * order_items.total_cost_with_vat'));
                
                $collectedAmount = max(0, $collectedAmount - $collectedReturnedAmount);
            }
            
            $pendingAmount = $totalAmount - $collectedAmount;
        }

        return [
            'active_schedule' => $activeSchedule ? [
                'id' => $activeSchedule->id,
                'delivery_number' => $activeSchedule->delivery_number,
                'status' => $activeSchedule->status,
                'route_name' => $routeNameDisplay ?? ($activeSchedule->route->route_name ?? 'N/A'),
                'vehicle' => $activeSchedule->vehicle->license_plate_number ?? 'Not assigned',
                'expected_date' => $activeSchedule->expected_delivery_date,
                'duration' => $activeSchedule->duration,
                'customers_count' => $activeSchedule->customers->count(),
                'delivered_count' => $activeSchedule->customers->whereNotNull('delivered_at')->count(),
                'total_amount' => $totalAmount,
                'collected_amount' => $collectedAmount,
                'pending_amount' => $pendingAmount,
                'items_grouped' => $itemsGrouped,
                'items_count' => $itemsGroupedCount ?: $activeSchedule->items->count(),
                'items' => $activeSchedule->items->map(function ($item) {
                    $binLocation = 'N/A';
                    $itemName = 'Unknown Item';
                    $unit = 'Units';
                    $quantity = $item->total_quantity ?? $item->received_quantity ?? $item->quantity ?? 0;
                    
                    if ($item->inventoryItem) {
                        $itemName = $item->inventoryItem->item_name ?? $item->inventoryItem->title ?? 'Unknown Item';
                        
                        if ($item->inventoryItem->packSize) {
                            $unit = $item->inventoryItem->packSize->pack_name ?? 'Units';
                        } elseif ($item->inventoryItem->pack_size) {
                            $unit = $item->inventoryItem->pack_size->pack_name ?? 'Units';
                        }
                        
                        if ($item->inventoryItem->binLocation) {
                            $binData = $item->inventoryItem->binLocation;
                            $binUom = \App\Model\WaUnitOfMeasure::find($binData->uom_id);
                            $binLocation = $binUom ? $binUom->title : 'N/A';
                        }
                    }
                    
                    return [
                        'id' => $item->id,
                        'inventory_item_id' => $item->wa_inventory_item_id,
                        'item_name' => $itemName,
                        'quantity' => $quantity,
                        'tonnage' => $item->tonnage,
                        'unit' => $unit,
                        'bin_location' => $binLocation
                    ];
                }),
                'customers' => $activeSchedule->customers->map(function ($customer) {
                    $routeCustomer = $customer->routeCustomer;
                    $route = $routeCustomer ? $routeCustomer->route : null;
                    $center = $routeCustomer ? $routeCustomer->center : null;
                    
                    return [
                        'id' => $customer->customer_id,
                        'delivery_schedule_customer_id' => $customer->id,
                        'name' => $routeCustomer->bussiness_name ?? 'Unknown Customer',
                        'phone' => $routeCustomer->phone ?? 'No phone',
                        'location' => $routeCustomer->location ?? 'No location',
                        'route_id' => $route->id ?? null,
                        'route_name' => $route->route_name ?? 'Unknown Route',
                        'center_id' => $center->id ?? null,
                        'center_name' => $center->name ?? 'Unknown Center',
                        'delivered_at' => $customer->delivered_at,
                        'delivery_code' => $customer->delivery_code,
                        'delivery_code_status' => $customer->delivery_code_status,
                        'delivery_prompted_at' => $customer->delivery_prompted_at,
                        'status' => $customer->delivered_at ? 'delivered' : 
                                  ($customer->delivery_code_status == 'sent' ? 'in_progress' : 'pending')
                    ];
                }),
                'customers_grouped' => $activeSchedule->customers->groupBy(function ($customer) {
                    $routeCustomer = $customer->routeCustomer;
                    $route = $routeCustomer ? $routeCustomer->route : null;
                    return $route ? $route->route_name : 'Unknown Route';
                })->map(function ($routeCustomers, $routeName) use ($activeSchedule, $shiftIds) {
                    return [
                        'route_name' => $routeName,
                        'centers' => $routeCustomers->groupBy(function ($customer) {
                            $routeCustomer = $customer->routeCustomer;
                            $center = $routeCustomer ? $routeCustomer->center : null;
                            return $center ? $center->name : 'Unknown Center';
                        })->map(function ($centerCustomers, $centerName) use ($activeSchedule, $shiftIds) {
                            return [
                                'center_name' => $centerName,
                                'customers' => $centerCustomers->map(function ($customer) use ($activeSchedule, $shiftIds) {
                                    $routeCustomer = $customer->routeCustomer;
                                    
                                    // Calculate order amount for this customer
                                    $orderAmount = DB::table('wa_internal_requisition_items as order_items')
                                        ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                                        ->whereIn('orders.wa_shift_id', $shiftIds)
                                        ->where('orders.wa_route_customer_id', $customer->customer_id)
                                        ->sum('order_items.total_cost_with_vat');
                                    
                                    // Subtract returned items value
                                    $returnedAmount = DB::table('sale_order_returns as returns')
                                        ->join('wa_internal_requisition_items as order_items', 'returns.wa_internal_requisition_item_id', '=', 'order_items.id')
                                        ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
                                        ->whereIn('orders.wa_shift_id', $shiftIds)
                                        ->where('orders.wa_route_customer_id', $customer->customer_id)
                                        ->sum(DB::raw('(returns.quantity / order_items.quantity) * order_items.total_cost_with_vat'));
                                    
                                    $orderAmount = max(0, $orderAmount - $returnedAmount);
                                    
                                    return [
                                        'id' => $customer->customer_id,
                                        'delivery_schedule_customer_id' => $customer->id,
                                        'name' => $routeCustomer->bussiness_name ?? 'Unknown Customer',
                                        'phone' => $routeCustomer->phone ?? 'No phone',
                                        'location' => $routeCustomer->location ?? 'No location',
                                        'order_amount' => $orderAmount,
                                        'delivered_at' => $customer->delivered_at,
                                        'delivery_code' => $customer->delivery_code,
                                        'delivery_code_status' => $customer->delivery_code_status,
                                        'delivery_prompted_at' => $customer->delivery_prompted_at,
                                        'status' => $customer->delivered_at ? 'delivered' : 
                                                  ($customer->delivery_code_status == 'sent' ? 'in_progress' : 'pending')
                                    ];
                                })->values()
                            ];
                        })->values()
                    ];
                })->values()
            ] : null,
            'today_stats' => $todayStats,
            'recent_deliveries' => $recentDeliveries->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'delivery_number' => $delivery->delivery_number,
                    'route_name' => $delivery->route->route_name ?? 'N/A',
                    'status' => $delivery->status,
                    'updated_at' => $delivery->updated_at->diffForHumans()
                ];
            })
        ];
    }

    private function ensureScheduleCustomersAndItemsFromShifts(DeliverySchedule $schedule, array $shiftIds): void
    {
        if (empty($shiftIds)) {
            return;
        }

        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));
        if (empty($shiftIds)) {
            return;
        }

        $customersWithOrders = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)
            ->whereNotNull('wa_route_customer_id')
            ->distinct()
            ->pluck('wa_route_customer_id')
            ->map(fn($id) => (int)$id)
            ->values();

        $existingCustomers = $schedule->customers()->get()->keyBy(fn($c) => (int)$c->customer_id);
        foreach ($customersWithOrders as $customerId) {
            $customerOrderIds = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)
                ->where('wa_route_customer_id', $customerId)
                ->pluck('id')
                ->map(fn($id) => (int)$id)
                ->values()
                ->all();

            $existing = $existingCustomers->get($customerId);
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

        $shiftItems = DB::table('wa_internal_requisition_items as order_items')
            ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
            ->whereIn('orders.wa_shift_id', $shiftIds)
            ->groupBy('order_items.wa_inventory_item_id')
            ->select('order_items.wa_inventory_item_id as item_id', DB::raw('SUM(order_items.quantity) as total_quantity'))
            ->get();

        $existingItems = $schedule->items()->get()->keyBy(fn($i) => (int)$i->wa_inventory_item_id);
        foreach ($shiftItems as $shiftItem) {
            $itemId = (int)$shiftItem->item_id;
            $qty = (float)$shiftItem->total_quantity;

            $existing = $existingItems->get($itemId);
            if ($existing) {
                $existing->total_quantity = $qty;
                if (isset($existing->received_quantity) && $existing->received_quantity > $existing->total_quantity) {
                    $existing->received_quantity = $existing->total_quantity;
                }
                $existing->save();
                continue;
            }

            $schedule->items()->create([
                'wa_inventory_item_id' => $itemId,
                'total_quantity' => $qty,
                'received_quantity' => 0,
            ]);
        }
    }

    /**
     * Get delivery codes for active schedule
     */
    public function getDeliveryCodes(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }
            
            // Find active schedule
            $activeSchedule = DeliverySchedule::with(['customers.routeCustomer'])
                ->where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();
            
            if (!$activeSchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active delivery schedule found'
                ]);
            }
            
            // Get customers with delivery codes
            $customerCodes = $activeSchedule->customers
                ->filter(function($customer) {
                    return !empty($customer->delivery_code) && $customer->delivery_code_status === 'sent';
                })
                ->map(function($customer) {
                    return [
                        'customer_id' => $customer->customer_id,
                        'customer_name' => $customer->routeCustomer->bussiness_name ?? 'Unknown Customer',
                        'phone' => $customer->routeCustomer->phone ?? 'No phone',
                        'delivery_code' => $customer->delivery_code,
                        'delivery_code_status' => $customer->delivery_code_status,
                        'delivery_prompted_at' => $customer->delivery_prompted_at ? $customer->delivery_prompted_at->format('Y-m-d H:i:s') : null
                    ];
                })
                ->values();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'delivery_number' => $activeSchedule->delivery_number,
                    'customers' => $customerCodes
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting delivery codes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting delivery codes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Complete delivery directly without code verification
     */
    public function completeDeliveryDirect(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }
            
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:wa_route_customers,id'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ]);
            }
            
            DB::beginTransaction();
            
            // Find active schedule
            $activeSchedule = DeliverySchedule::where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();
            
            if (!$activeSchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active delivery in progress'
                ]);
            }
            
            // Find customer in schedule
            $customer = $activeSchedule->customers()
                ->where('customer_id', $request->customer_id)
                ->first();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found in active schedule'
                ]);
            }
            
            // Mark customer as delivered
            // Only set payment_skipped if explicitly requested (not when called after payment)
            $paymentSkipped = $request->input('payment_skipped', false);
            
            $customer->update([
                'delivered_at' => Carbon::now(),
                'delivery_code_status' => 'approved',
                'payment_skipped' => $paymentSkipped
            ]);
            
            // Update order status to COMPLETED
            if (!empty($customer->order_id)) {
                $orderIds = explode(',', $customer->order_id);
                WaInternalRequisition::whereIn('id', $orderIds)
                    ->update(['status' => 'COMPLETED']);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Delivery completed successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing delivery: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error completing delivery: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get available payment methods
     */
    public function getPaymentMethods(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }
            
            // Get payment methods for the user's branch
            $paymentMethods = PaymentMethod::join('wa_chart_of_accounts_branches as branches', 'payment_methods.gl_account_id', '=', 'branches.wa_chart_of_account_id')
                ->where('branches.restaurant_id', $user->restaurant_id)
                ->where('payment_methods.use_in_pos', true)
                ->select([
                    'payment_methods.id',
                    'payment_methods.title',
                    'payment_methods.is_cash'
                ])
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'payment_methods' => $paymentMethods
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting payment methods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting payment methods: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record payment for delivery
     */
    public function recordPayment(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ]);
            }
            
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:wa_route_customers,id',
                'payments' => 'required|array',
                'payments.*.method_id' => 'required|exists:payment_methods,id',
                'payments.*.amount' => 'required|numeric|min:0.01'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ]);
            }
            
            DB::beginTransaction();
            
            // Find active schedule
            $activeSchedule = DeliverySchedule::where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();
            
            if (!$activeSchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active delivery in progress'
                ]);
            }
            
            // Find customer in schedule
            $customer = $activeSchedule->customers()
                ->where('customer_id', $request->customer_id)
                ->first();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found in active schedule'
                ]);
            }
            
            // Get order IDs
            $orderIds = [];
            if (!empty($customer->order_id)) {
                $orderIds = explode(',', $customer->order_id);
            }
            
            // Get payment method details
            $paymentMethodsMap = [];
            foreach ($request->payments as $payment) {
                $method = PaymentMethod::find($payment['method_id']);
                if ($method) {
                    $paymentMethodsMap[$payment['method_id']] = $method->title;
                }
            }
            
            // Record payments for each order
            foreach ($orderIds as $orderId) {
                $order = WaInternalRequisition::with('getRouteCustomer')->find($orderId);
                if (!$order) continue;
                
                // Get wa_customer_id from route customer
                $waCustomerId = null;
                if ($order->getRouteCustomer && $order->getRouteCustomer->route_id) {
                    $waCustomer = \App\Model\WaCustomer::where('route_id', $order->getRouteCustomer->route_id)->first();
                    $waCustomerId = $waCustomer ? $waCustomer->id : null;
                }
                
                foreach ($request->payments as $payment) {
                    $methodName = $paymentMethodsMap[$payment['method_id']] ?? 'Unknown';
                    $paymentRef = 'DELIVERY-' . time() . '-' . $orderId;
                    
                    // Create invoice payment record
                    InvoicePayment::create([
                        'order_id' => $orderId,
                        'order_no' => $order->order_no ?? 'N/A',
                        'invoice_amount' => $order->total_cost_with_vat ?? '0',
                        'paid_amount' => $payment['amount'],
                        'payment_gateway' => $methodName,
                        'payment_channel' => $methodName,
                        'payment_reference' => $paymentRef,
                        'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
                        'status' => 'completed',
                        'delivery_code' => $customer->delivery_code ?? null
                    ]);
                    
                    // Create debtor transaction for customer statement
                    if ($waCustomerId) {
                        WaDebtorTran::create([
                            'wa_customer_id' => $waCustomerId,
                            'wa_sales_invoice_id' => $orderId,
                            'document_no' => $paymentRef,
                            'reference' => "PAYMENT - {$methodName} - Invoice: {$order->requisition_no}",
                            'amount' => -$payment['amount'], // Negative for payment/credit
                            'trans_date' => Carbon::now()->format('Y-m-d'),
                            'input_date' => Carbon::now()->format('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Payment recorded successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error recording payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error recording payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get return reasons
     */
    public function getReturnReasons()
    {
        try {
            $reasons = \App\Models\ReturnReason::select('id', 'reason')
                ->orderBy('reason', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'reasons' => $reasons
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching return reasons: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching return reasons'
            ], 500);
        }
    }
    
    /**
     * Process item returns
     */
    public function processReturns(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
            'items' => 'required|array',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.reason_id' => 'required|integer'
        ]);
        
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $customerId = $request->customer_id;
            
            // Log for debugging
            \Log::info('Processing returns', [
                'customer_id' => $customerId,
                'driver_id' => $user->id,
                'items' => $request->items
            ]);
            
            // Get customer from delivery schedule using customer_id (wa_route_customer_id)
            $customer = DeliveryScheduleCustomer::where('customer_id', $customerId)
                ->whereHas('deliverySchedule', function($query) use ($user) {
                    $query->where('driver_id', $user->id)
                          ->where('status', 'in_progress');
                })
                ->first();
            
            if (!$customer) {
                \Log::error('Customer not found in active delivery schedule', [
                    'customer_id' => $customerId,
                    'driver_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Customer record not found in your active delivery schedule. Please make sure you have prompted delivery for this customer first.'
                ]);
            }
            
            // Verify customer belongs to driver's schedule
            if ($customer->deliverySchedule && $customer->deliverySchedule->driver_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This customer is not in your delivery schedule'
                ]);
            }
            
            // Get order IDs
            $orderIds = [];
            if (!empty($customer->order_id)) {
                $orderIds = explode(',', $customer->order_id);
            }
            
            // Process each return item
            foreach ($request->items as $returnItem) {
                $requisitionItem = WaInternalRequisitionItem::find($returnItem['item_id']);
                
                if (!$requisitionItem) {
                    continue;
                }
                
                // Get the transfer item
                $transferItem = \App\Model\WaInventoryLocationTransferItem::where('wa_internal_requisition_item_id', $requisitionItem->id)
                    ->first();
                
                if (!$transferItem) {
                    continue;
                }
                
                // Check if return already exists for this item
                $existingReturn = \App\Model\WaInventoryLocationItemReturn::where('wa_inventory_location_transfer_item_id', $transferItem->id)
                    ->where('status', 'pending')
                    ->first();
                
                if ($existingReturn) {
                    \Log::info('Skipping return - already exists', [
                        'item_id' => $returnItem['item_id'],
                        'existing_return_id' => $existingReturn->id,
                        'existing_return_qty' => $existingReturn->return_quantity
                    ]);
                    continue; // Skip if already has pending return
                }
                
                // Generate return number if needed
                $returnNumber = \App\Model\WaInventoryLocationItemReturn::where('wa_inventory_location_transfer_id', $transferItem->wa_inventory_location_transfer_id)
                    ->latest()
                    ->first()?->return_number;
                
                if (!$returnNumber) {
                    $returnNumber = 'RTN-' . time() . '-' . $customerId;
                }
                
                // Get reason text for return_reason field
                $reason = \App\Models\ReturnReason::find($returnItem['reason_id']);
                $reasonText = $reason ? $reason->reason : 'Other';
                
                // Log what we're about to create
                \Log::info('Creating return record', [
                    'item_id' => $returnItem['item_id'],
                    'return_quantity' => $returnItem['quantity'],
                    'transfer_item_id' => $transferItem->id
                ]);
                
                // Create return record
                $createdReturn = \App\Model\WaInventoryLocationItemReturn::create([
                    'return_number' => $returnNumber,
                    'wa_inventory_location_transfer_item_id' => $transferItem->id,
                    'wa_inventory_location_transfer_id' => $transferItem->wa_inventory_location_transfer_id,
                    'return_by' => $user->id,
                    'return_date' => Carbon::now(),
                    'return_quantity' => $returnItem['quantity'],
                    'return_reason' => $reasonText,
                    'status' => 'pending',
                    'return_status' => 1,
                    'received_quantity' => 0
                ]);
                
                // Log what was actually saved
                \Log::info('Return record created', [
                    'return_id' => $createdReturn->id,
                    'saved_return_quantity' => $createdReturn->return_quantity
                ]);
                
                // Also create sale order return record
                \App\Model\SaleOrderReturns::create([
                    'wa_internal_requisition_item_id' => $requisitionItem->id,
                    'quantity' => $returnItem['quantity'],
                    'item_return_reason_id' => $returnItem['reason_id'],
                    'comment' => $reasonText,
                    'image' => 0
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Returns processed successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing returns: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing returns: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if user is a delivery driver
     */
    private function isDeliveryDriver($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check role_id = 6 (delivery driver)
        if ($user->role_id == 6) {
            return true;
        }

        // Check role name/slug contains 'delivery'
        $roleName = '';
        if (isset($user->userRole)) {
            $roleName = $user->userRole->name ?? $user->userRole->title ?? $user->userRole->slug ?? '';
        }

        return stripos($roleName, 'delivery') !== false;
    }
}
