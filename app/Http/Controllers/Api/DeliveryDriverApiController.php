<?php

namespace App\Http\Controllers\Api;

use App\DeliverySchedule;
use App\DeliveryScheduleCustomer;
use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeliveryDriverApiController extends Controller
{
    /**
     * Get driver dashboard data
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            Log::info('Dashboard data requested', ['driver_id' => $user->id]);

            // Get active delivery schedule
            $activeSchedule = DeliverySchedule::with([
                'route', 
                'vehicle', 
                'customers.routeCustomer', 
                'items.inventoryItem.packSize',
                'items.inventoryItem.binLocation'
            ])
                ->where('driver_id', $user->id)
                ->whereNotIn('status', ['finished'])
                ->latest()
                ->first();

            // Get today's statistics
            $todayStats = $this->getTodayStats($user->id);

            // Get recent deliveries
            $recentDeliveries = DeliverySchedule::with(['route'])
                ->where('driver_id', $user->id)
                ->where('status', 'finished')
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'active_schedule' => $activeSchedule ? [
                        'id' => $activeSchedule->id,
                        'delivery_number' => $activeSchedule->delivery_number,
                        'status' => $activeSchedule->status === 'finished' ? 'completed' : 
                                   ($activeSchedule->status === 'in_progress' ? 'in_progress' : 
                                   ($activeSchedule->status === 'loaded' ? 'loaded' : 'pending')),
                        'route_name' => $activeSchedule->route->route_name ?? 'N/A',
                        'vehicle' => $activeSchedule->vehicle->license_plate_number ?? 'Not assigned',
                        'expected_date' => $activeSchedule->expected_delivery_date,
                        'duration' => $activeSchedule->duration,
                        'customers_count' => $activeSchedule->customers->count(),
                        'delivered_count' => $activeSchedule->customers->whereNotNull('delivered_at')->count(),
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
                            $status = $customer->delivered_at ? 'delivered' : 
                                    ($customer->is_skipped ? 'skipped' :
                                    ($customer->delivery_code_status == 'sent' ? 'in_progress' : 'pending'));
                            
                            Log::info('Customer status mapping', [
                                'customer_id' => $customer->customer_id,
                                'business_name' => $customer->routeCustomer->bussiness_name ?? 'Unknown',
                                'delivered_at' => $customer->delivered_at,
                                'delivery_code_status' => $customer->delivery_code_status,
                                'is_skipped' => $customer->is_skipped,
                                'calculated_status' => $status
                            ]);
                            
                            return [
                                'id' => $customer->customer_id,
                                'name' => $customer->routeCustomer->bussiness_name ?? 'Unknown Customer',
                                'phone' => $customer->routeCustomer->phone ?? 'No phone',
                                'location' => $customer->routeCustomer->location ?? 'No location',
                                'delivered_at' => $customer->delivered_at,
                                'delivery_code' => $customer->delivery_code,
                                'delivery_code_status' => $customer->delivery_code_status,
                                'delivery_prompted_at' => $customer->delivery_prompted_at,
                                'payment_method' => $customer->payment_method,
                                'is_skipped' => $customer->is_skipped ?? false,
                                'skip_reason' => $customer->skip_reason,
                                'skipped_at' => $customer->skipped_at,
                                'delivery_notes' => $customer->delivery_notes,
                                'status' => $status
                            ];
                        })
                    ] : null,
                    'today_stats' => $todayStats,
                    'recent_deliveries' => $recentDeliveries->map(function ($delivery) {
                        return [
                            'id' => $delivery->id,
                            'delivery_number' => $delivery->delivery_number,
                            'route_name' => $delivery->route->route_name ?? 'N/A',
                            'status' => $delivery->status === 'finished' ? 'completed' : 
                                       ($delivery->status === 'in_progress' ? 'in_progress' : 
                                       ($delivery->status === 'loaded' ? 'loaded' : 'pending')),
                            'updated_at' => $delivery->updated_at->diffForHumans()
                        ];
                    })
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting dashboard data: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading dashboard data'], 500);
        }
    }

    /**
     * Start delivery schedule
     */
    public function startDelivery(Request $request): JsonResponse
    {
        Log::info('Start delivery request received', [
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:delivery_schedules,id'
        ]);

        if ($validator->fails()) {
            Log::warning('Validation failed for start delivery', [
                'errors' => $validator->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                Log::warning('Non-delivery driver attempted to start delivery', ['user_id' => $user->id]);
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            DB::beginTransaction();

            // Check if schedule exists for this driver
            $schedule = DeliverySchedule::where('driver_id', $user->id)
                ->where('id', $request->schedule_id)
                ->first();

            if (!$schedule) {
                Log::warning('Schedule not found for driver', [
                    'driver_id' => $user->id,
                    'schedule_id' => $request->schedule_id
                ]);
                
                // Get all schedules for this driver for debugging
                $driverSchedules = DeliverySchedule::where('driver_id', $user->id)->get(['id', 'status']);
                Log::info('Available schedules for driver', [
                    'driver_id' => $user->id,
                    'schedules' => $driverSchedules->toArray()
                ]);
                
                return response()->json([
                    'success' => false, 
                    'message' => 'Schedule not found or not assigned to you'
                ], 404);
            }

            if ($schedule->status !== 'loaded') {
                Log::warning('Schedule not in loaded status', [
                    'schedule_id' => $schedule->id,
                    'current_status' => $schedule->status
                ]);
                return response()->json([
                    'success' => false, 
                    'message' => 'Schedule must be in loaded status to start delivery. Current status: ' . $schedule->status
                ], 400);
            }

            $schedule->update([
                'status' => 'in_progress',
                'loading_time' => Carbon::now()
            ]);

            DB::commit();

            Log::info('Delivery started successfully', [
                'schedule_id' => $schedule->id,
                'driver_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delivery started successfully',
                'data' => [
                    'schedule_id' => $schedule->id,
                    'status' => 'in_progress'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error starting delivery: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'schedule_id' => $request->schedule_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Error starting delivery: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Prompt delivery completion
     */
    public function promptDelivery(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:wa_route_customers,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            DB::beginTransaction();

            $activeSchedule = DeliverySchedule::with('customers')
                ->where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->firstOrFail();

            // Log for debugging
            Log::info('Prompt Delivery - Looking for customer', [
                'customer_id_received' => $request->customer_id,
                'schedule_id' => $activeSchedule->id,
                'schedule_customers_ids' => $activeSchedule->customers->pluck('id')->toArray(),
                'schedule_customers_customer_ids' => $activeSchedule->customers->pluck('customer_id')->toArray()
            ]);

            // customer_id is the wa_route_customer_id
            $customerDelivery = $activeSchedule->customers()
                ->where('customer_id', $request->customer_id)
                ->first();
                
            if (!$customerDelivery) {
                Log::error('Customer not found in schedule', [
                    'customer_id' => $request->customer_id,
                    'available_ids' => $activeSchedule->customers->pluck('id')->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found in active schedule. Available IDs: ' . 
                                 implode(', ', $activeSchedule->customers->pluck('id')->toArray())
                ], 404);
            }

            // Generate delivery code if not exists
            if (!$customerDelivery->delivery_code) {
                $customerDelivery->update([
                    'delivery_code' => random_int(100000, 999999)
                ]);
            }

            $customerDelivery->update([
                'delivery_code_status' => 'sent',
                'delivery_prompted_at' => Carbon::now()
            ]);

            // Update order status to PROCESSING
            $orderIds = explode(',', $customerDelivery->order_id);
            WaInternalRequisition::whereIn('id', $orderIds)
                ->update(['status' => 'PROCESSING']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery completion prompted successfully',
                'data' => [
                    'delivery_code' => $customerDelivery->delivery_code,
                    'customer_id' => $request->customer_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error prompting delivery: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error prompting delivery'], 500);
        }
    }

    /**
     * Verify delivery code
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:wa_route_customers,id',
            'delivery_code' => 'required|string|size:6',
            'collection_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,mpesa,card,bank_transfer,credit,skip',
            'delivery_notes' => 'nullable|string|max:1000',
            'delivery_location_lat' => 'nullable|string|max:20',
            'delivery_location_lng' => 'nullable|string|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            DB::beginTransaction();

            $activeSchedule = DeliverySchedule::with('customers')
                ->where('driver_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->firstOrFail();

            // customer_id is the wa_route_customer_id
            $customerDelivery = $activeSchedule->customers()
                ->where('customer_id', $request->customer_id)
                ->firstOrFail();

            if ($request->delivery_code != $customerDelivery->delivery_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid delivery code'
                ], 400);
            }

            $updateData = [
                'delivery_code_status' => 'verified',
                'delivered_at' => Carbon::now()
            ];

            // Add optional fields if provided
            if ($request->has('payment_method') && $request->payment_method !== null) {
                $updateData['payment_method'] = $request->payment_method;
                
                // If payment is skipped, set collection amount to 0
                if ($request->payment_method === 'skip') {
                    $updateData['collection_amount'] = 0;
                } elseif ($request->has('collection_amount') && $request->collection_amount !== null) {
                    $updateData['collection_amount'] = $request->collection_amount;
                }
            } elseif ($request->has('collection_amount') && $request->collection_amount !== null) {
                $updateData['collection_amount'] = $request->collection_amount;
            }
            if ($request->has('delivery_notes') && $request->delivery_notes !== null) {
                $updateData['delivery_notes'] = $request->delivery_notes;
            }
            if ($request->has('delivery_location_lat') && $request->delivery_location_lat !== null) {
                $updateData['delivery_location_lat'] = $request->delivery_location_lat;
            }
            if ($request->has('delivery_location_lng') && $request->delivery_location_lng !== null) {
                $updateData['delivery_location_lng'] = $request->delivery_location_lng;
            }

            $customerDelivery->update($updateData);

            // Log payment method for debugging
            Log::info('Delivery completed', [
                'customer_id' => $request->customer_id,
                'payment_method' => $updateData['payment_method'] ?? 'not_set',
                'collection_amount' => $updateData['collection_amount'] ?? 'not_set',
                'delivered_at' => $updateData['delivered_at']
            ]);

            // Update order status to COMPLETED
            $orderIds = explode(',', $customerDelivery->order_id);
            WaInternalRequisition::whereIn('id', $orderIds)
                ->update(['status' => 'COMPLETED']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery verified successfully',
                'data' => [
                    'customer_id' => $request->customer_id,
                    'delivered_at' => $customerDelivery->delivered_at,
                    'collection_amount' => $customerDelivery->collection_amount,
                    'payment_method' => $customerDelivery->payment_method,
                    'delivery_notes' => $customerDelivery->delivery_notes
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error verifying delivery: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error verifying delivery'], 500);
        }
    }

    /**
     * Complete delivery schedule
     */
    public function completeSchedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schedule_id' => 'required|exists:delivery_schedules,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            DB::beginTransaction();

            $schedule = DeliverySchedule::with('customers')
                ->where('driver_id', $user->id)
                ->where('id', $request->schedule_id)
                ->firstOrFail();

            if ($schedule->status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule must be in progress to complete'
                ], 400);
            }

            // Check if all customers have been delivered to
            $undeliveredCustomers = $schedule->customers()
                ->whereNull('delivered_at')
                ->count();

            if ($undeliveredCustomers > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "You have {$undeliveredCustomers} undelivered customers remaining"
                ], 400);
            }

            $schedule->update([
                'status' => 'finished',
                'actual_delivery_date' => Carbon::now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Delivery schedule completed successfully',
                'data' => [
                    'schedule_id' => $schedule->id,
                    'status' => 'finished',
                    'completed_at' => $schedule->actual_delivery_date
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completing schedule: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error completing schedule'], 500);
        }
    }

    /**
     * Get delivery history
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 20);

            $deliveries = DeliverySchedule::with(['route', 'vehicle', 'customers'])
                ->where('driver_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => [
                    'deliveries' => $deliveries->items(),
                    'pagination' => [
                        'current_page' => $deliveries->currentPage(),
                        'last_page' => $deliveries->lastPage(),
                        'per_page' => $deliveries->perPage(),
                        'total' => $deliveries->total()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting delivery history: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error loading history'], 500);
        }
    }

    /**
     * Update driver location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$this->isDeliveryDriver($user)) {
                return response()->json(['success' => false, 'message' => 'Access denied'], 403);
            }

            // Update user location (assuming you have location fields in users table)
            $user->update([
                'current_latitude' => $request->latitude,
                'current_longitude' => $request->longitude,
                'location_updated_at' => Carbon::now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating location: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating location'], 500);
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
     * Get delivery shifts for salesman
     */
    public function getDeliveryShifts(Request $request): JsonResponse
    {
        try {
            $salesmanId = $request->get('salesman_id');
            
            Log::info('getDeliveryShifts API called', [
                'salesman_id' => $salesmanId,
                'timestamp' => now()
            ]);
            
            if (!$salesmanId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Salesman ID is required'
                ], 400);
            }

            // Get salesman's routes
            $salesmanRoutes = DB::table('route_user')
                ->where('user_id', $salesmanId)
                ->pluck('route_id')
                ->toArray();

            if (empty($salesmanRoutes)) {
                return response()->json([
                    'status' => true,
                    'data' => []
                ]);
            }

            // Get delivery schedules for the salesman's routes
            $deliveryShifts = DeliverySchedule::with([
                'route',
                'vehicle',
                'driver',
                'customers' => function($query) {
                    $query->select('*'); // Ensure all fields including payment_method are selected
                },
                'customers.routeCustomer'
            ])
                ->whereIn('route_id', $salesmanRoutes)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($schedule) {
                    // Calculate collections and returns
                    $totalCollections = 0;
                    $totalReturns = 0;
                    $deliveryPoints = [];

                    foreach ($schedule->customers as $customer) {
                        // Log customer data to debug payment_method availability
                        Log::info('Processing customer', [
                            'customer_id' => $customer->customer_id,
                            'schedule_id' => $schedule->id,
                            'payment_method_from_model' => $customer->payment_method ?? 'null',
                            'collection_amount_from_model' => $customer->collection_amount ?? 'null',
                            'customer_attributes' => $customer->getAttributes()
                        ]);
                        
                        // Double-check by querying the database directly
                        $customerPaymentData = DB::table('delivery_schedule_customers')
                            ->select('payment_method', 'collection_amount', 'delivered_at')
                            ->where('id', $customer->id)
                            ->first();
                            
                        Log::info('Direct DB query for customer payment data', [
                            'customer_id' => $customer->customer_id,
                            'db_payment_method' => $customerPaymentData->payment_method ?? 'null',
                            'db_collection_amount' => $customerPaymentData->collection_amount ?? 'null',
                            'db_delivered_at' => $customerPaymentData->delivered_at ?? 'null'
                        ]);
                        
                        // Get orders for this customer
                        $orderIds = explode(',', $customer->order_id);
                        $orders = WaInternalRequisition::whereIn('id', $orderIds)->get();
                        
                        foreach ($orders as $order) {
                            // Get payment data from wa_debtor_trans (statement module - very accurate)
                            $paymentAmount = 0;
                            $paymentMethod = 'Cash';
                            
                            // Get actual return items with available details first
                            $returnItems = DB::table('wa_inventory_location_transfer_item_returns as returns')
                                ->join('wa_inventory_location_transfer_items as items', 'returns.wa_inventory_location_transfer_item_id', '=', 'items.id')
                                ->join('wa_inventory_items as products', 'items.wa_inventory_item_id', '=', 'products.id')
                                ->join('wa_inventory_location_transfers as transfers', 'returns.wa_inventory_location_transfer_id', '=', 'transfers.id')
                                ->join('wa_internal_requisition_items as req_items', 'items.wa_internal_requisition_item_id', '=', 'req_items.id')
                                ->where('req_items.wa_internal_requisition_id', $order->id)
                                ->select('products.title as item_name', 'returns.return_quantity', 'returns.return_date',
                                        DB::raw('returns.return_quantity * items.selling_price as return_value'))
                                ->get();
                            
                            $hasReturn = $returnItems->count() > 0;
                            $returnAmount = $returnItems->sum('return_value');
                            
                            // Check for actual payment records in wa_debtor_trans (like the statement does)
                            // Look across all customers since there might be customer mapping issues
                            $paymentRecord = DB::table('wa_debtor_trans')
                                ->select('amount', 'channel')
                                ->where('amount', '<', 0) // Negative amounts are payments
                                ->where('reference', 'LIKE', '%' . $order->requisition_no . '%')
                                ->first();
                            
                            if ($paymentRecord) {
                                // Payment was made - use the actual payment amount
                                $paymentAmount = abs($paymentRecord->amount); // Make positive
                                
                                // Log payment record details for debugging
                                Log::info('Payment Record Found', [
                                    'order_no' => $order->requisition_no,
                                    'payment_amount' => $paymentAmount,
                                    'debtor_trans_channel' => $paymentRecord->channel ?? 'null',
                                    'customer_payment_method' => $customer->payment_method ?? 'null',
                                    'customer_collection_amount' => $customer->collection_amount ?? 'null',
                                    'customer_id' => $customer->customer_id ?? 'null',
                                    'customer_delivered_at' => $customer->delivered_at ?? 'null',
                                    'customer_all_attributes' => $customer->getAttributes()
                                ]);
                                
                                // Get payment method from delivery_schedule_customers table first (most accurate)
                                // Use direct DB query result as primary source
                                $paymentMethod = $customerPaymentData->payment_method ?? $customer->payment_method ?? null;
                                
                                // If not found, check wa_debtor_trans channel field
                                if (!$paymentMethod && $paymentRecord->channel) {
                                    $paymentMethod = $paymentRecord->channel;
                                    Log::info('Using payment method from wa_debtor_trans.channel', [
                                        'order_no' => $order->requisition_no,
                                        'channel' => $paymentRecord->channel
                                    ]);
                                }
                                
                                // Default to Cash if no specific method found
                                if (!$paymentMethod) {
                                    $paymentMethod = 'Cash';
                                    Log::info('Defaulting to Cash - no payment method found', [
                                        'order_no' => $order->requisition_no
                                    ]);
                                }
                                
                                // Normalize payment method names
                                $originalMethod = $paymentMethod;
                                $paymentMethod = ucfirst(strtolower($paymentMethod));
                                if ($paymentMethod === 'Mpesa') {
                                    $paymentMethod = 'M-Pesa';
                                }
                                
                                Log::info('Payment Method Determined', [
                                    'order_no' => $order->requisition_no,
                                    'original_method' => $originalMethod,
                                    'final_method' => $paymentMethod,
                                    'amount' => $paymentAmount
                                ]);
                            } else {
                                // No payment record found - delivered without payment
                                $paymentAmount = 0;
                                $paymentMethod = 'No payment collected';
                                
                                Log::info('No Payment Record Found', [
                                    'order_no' => $order->requisition_no,
                                    'customer_id' => $customer->customer_id ?? 'null'
                                ]);
                            }
                            
                            // Add to total collections and returns
                            $totalCollections += $paymentAmount;
                            $totalReturns += $returnAmount;
                            
                            // Build delivery point
                            $deliveryPoints[] = [
                                'id' => $order->id,
                                'order_no' => $order->requisition_no,
                                'customer_name' => $customer->routeCustomer->bussiness_name ?? 'Unknown',
                                'customer_phone' => $customer->routeCustomer->phone ?? 'N/A',
                                'delivery_status' => $customer->delivered_at ? 'delivered' : 
                                                   ($customer->is_skipped ? 'skipped' : 'pending'),
                                'is_delivered' => $customer->delivered_at ? 1 : 0,
                                'is_skipped' => $customer->is_skipped ?? false,
                                'skip_reason' => $customer->skip_reason,
                                'collection_amount' => $paymentAmount > 0 ? 'KES ' . number_format($paymentAmount, 2) : 'KES 0.00',
                                'collection_method' => $paymentMethod,
                                'has_return' => $hasReturn,
                                'return_amount' => $hasReturn ? 'KES ' . number_format($returnAmount, 2) : null,
                                'return_items' => $returnItems->map(function($item) {
                                    return [
                                        'item_name' => $item->item_name,
                                        'quantity' => $item->return_quantity,
                                        'return_date' => $item->return_date,
                                        'value' => 'KES ' . number_format($item->return_value, 2)
                                    ];
                                })->toArray(),
                                'delivery_time' => $customer->delivered_at,
                                'skipped_at' => $customer->skipped_at,
                                'delivery_notes' => $customer->delivery_notes,
                            ];
                        }
                    }

                    return [
                        'id' => $schedule->id,
                        'shift_date' => $schedule->expected_delivery_date ?? $schedule->created_at->format('Y-m-d'),
                        'vehicle_register_no' => $schedule->vehicle->license_plate_number ?? 'N/A',
                        'driver_name' => $schedule->driver->name ?? 'N/A',
                        'route_name' => $schedule->route->route_name ?? 'N/A',
                        'status' => $schedule->status === 'finished' ? 'completed' : 
                                   ($schedule->status === 'in_progress' ? 'in_progress' : 
                                   ($schedule->status === 'loaded' ? 'loaded' : 'pending')),
                        'total_deliveries' => $schedule->customers->count(),
                        'completed_deliveries' => $schedule->customers->whereNotNull('delivered_at')->count(),
                        'pending_deliveries' => $schedule->customers->whereNull('delivered_at')->count(),
                        'total_collections' => 'KES ' . number_format($totalCollections, 2),
                        'total_returns' => 'KES ' . number_format($totalReturns, 2),
                        'delivery_points' => $deliveryPoints,
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $deliveryShifts
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting delivery shifts: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'status' => false,
                'message' => 'Error loading delivery shifts: ' . $e->getMessage()
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



