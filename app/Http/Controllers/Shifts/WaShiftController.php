<?php

namespace App\Http\Controllers\Shifts;

use App\CustomerEquityPayment;
use App\CustomerKcbPayment;
use App\DeliveryManShift;
use App\DeliverySchedule;
use App\DeliveryScheduleCustomer;
use App\DeliveryScheduleItem;
use App\FuelLpo;
use App\Http\Controllers\Controller;
use App\Jobs\CreateDeliverySchedule;
use App\Jobs\PrepareStoreParkingList;
use App\Model\OdometerReadingHistory;
use App\Model\Route;
use App\Model\UserLog;
use App\Model\WaCustomer;
use App\Model\WaInternalRequisition;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\WaNumerSeriesCode;
use App\Model\WaRouteCustomer;
use App\Model\WaShift;
use App\NewVehicle;
use App\OffsiteShiftRequest;
use App\ParkingListItem;
use App\SalesmanShift;
use App\SalesmanShiftStoreDispatch;
use App\SalesmanShiftStoreDispatchItem;
use App\Services\DeliveryManService;
use App\Services\DeliveryService;
use App\Services\DispatchService;
use App\Vehicle;
use App\WaInventoryLocationTransferItemReturn;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class WaShiftController extends Controller
{
    public function __construct() {}

    public function open(Request $request): JsonResponse
    {

        try {
            DB::beginTransaction();

            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return $this->jsonify(['message' => getTokenHasNoUserMessage(), 'status' => false], 422);
            }

            $userRole = $user->userRole;
            if ($userRole) {
                if ($userRole->slug == 'sales-man') {
                    //                    $now = Carbon::now()->hour;
                    //                    if ($now > 16) {
                    //                        return $this->jsonify(['message' => "Sorry, you can not open a shift after 5PM."], 422);
                    //                    }

                    if (!$request->route_id) {
                        return $this->jsonify(['status' => false, 'message' => 'Salesman route is required'], 422);
                    }

                    $route = Route::with('waRouteCustomer')->find($request->route_id);
                    if (!$route) {
                        return $this->jsonify([
                            'status' => false,
                            'message' => "Please select a valid route!"
                        ], 422);
                    }

                    $today = Carbon::now()->dayOfWeek;
                    $orderTakingDays = explode(',', $route->order_taking_days);
                    if (!in_array((string)$today, $orderTakingDays)) {
                        return $this->jsonify(['status' => false, 'message' => "Sorry, today is not order taking day for route $route->route_name"], 422);
                    }

                    if (!$request->shift_type_id) {
                        return $this->jsonify(['status' => false, 'message' => 'Shift type is required'], 422);
                    }

                    if (!in_array($request->route_id, $user->routes->pluck('id')->toArray())) {
                        return $this->jsonify(['status' => false, 'message' => 'The salesman does not belong to this route'], 422);
                    }

                    // Auto-close shifts that are older than 24 hours or past 7 PM
                    $now = Carbon::now();
                    $yesterday = $now->copy()->subDay();
                    $sevenPmToday = $now->copy()->setTime(19, 0, 0); // 7 PM today
                    
                    // Get all open shifts for this user
                    $openShifts = $user->salesmanShifts()->where('status', 'open')->get();
                    
                    foreach ($openShifts as $openShift) {
                        $shiftStartTime = Carbon::parse($openShift->start_time);
                        $shouldAutoClose = false;
                        
                        // Close if shift is older than 24 hours
                        if ($shiftStartTime->lt($yesterday)) {
                            $shouldAutoClose = true;
                        }
                        // Close if shift started before 7 PM yesterday and it's now past 7 PM today
                        elseif ($shiftStartTime->lt($sevenPmToday->copy()->subDay()) && $now->gte($sevenPmToday)) {
                            $shouldAutoClose = true;
                        }
                        // Close if shift started today but it's now past 7 PM
                        elseif ($shiftStartTime->isToday() && $now->gte($sevenPmToday)) {
                            $shouldAutoClose = true;
                        }
                        
                        if ($shouldAutoClose) {
                            $openShift->status = 'close';
                            $openShift->closed_time = $now;
                            $openShift->save();
                            
                            \Log::info('Auto-closed shift', [
                                'shift_id' => $openShift->id,
                                'salesman_id' => $user->id,
                                'start_time' => $shiftStartTime,
                                'closed_time' => $now
                            ]);
                        }
                    }
                    
                    // Check again for open shifts after auto-close
                    $salesmanHasOpenShift = $user->salesmanShifts()->where('status', 'open')->first();
                    if ($salesmanHasOpenShift) {
                        return $this->jsonify(['message' => 'Sorry, you already have an open shift.', 'status' => false], 422);
                    }

                    // Salesman can only have one shift a day for a route.
                    $salesmanHadARouteShiftToday = $user->salesmanShifts()
                        ->where('id', '!=', $request->route_id)
                        ->where(function ($query) {
                            $query->where('status', 'open')
                                ->orWhere('status', 'close');
                        })
                        ->where('route_id', $request->route_id)
                        ->whereDate('created_at', '=', Carbon::now()->toDateString())
                        ->first();
                    if ($salesmanHadARouteShiftToday) {
                        $shiftType = $salesmanHadARouteShiftToday->shift_type == 'onsite' ? 'onsite' : 'offsite';
                        return $this->jsonify([
                            'message' => "Sorry, you already have an $shiftType shift for this route today. Please request for it to be re-opened.",
                            'status' => false,
                            'has_shift_for_today' => true
                        ], 422);
                    }

                    if ($request->shift_type_id == 2) {
                        if (!$request->reason) {
                            return $this->jsonify(['status' => false, 'message' => 'Please provide a reason'], 422);
                        }

                        $existingRequest = OffsiteShiftRequest::latest()->where('status', 'pending')->where('route_id', $route->id)->first();
                        if ($existingRequest) {
                            return $this->jsonify(['status' => false, 'message' => 'You already have a pending request for this route.'], 422);
                        }

                        OffsiteShiftRequest::create([
                            'route_id' => $route->id,
                            'salesman_id' => $user->id,
                            'reason' => $request->reason,
                        ]);

                        //                        $message = "Salesman $user->name ($user->phone_number) has requested an offsite shift for route $route->route_name, citing \"$request->reason\"";
                        //                        try {
                        //                            sendMessage($message, '0790544563');
                        //                        } catch (\Throwable $e) {
                        //                            // pass
                        //                        }

                        DB::commit();
                        return $this->jsonify(['status' => true, 'message' => 'Your request has been received and is being processed.'], 200);
                    }

                    $scheduledShift = $user->salesmanShifts()
                        ->where([
                            'route_id' => $request->route_id,
                            'status' => 'not_started'
                        ])
                        ->whereDate('created_at', '=', Carbon::now()->toDateString())
                        ->first();

                    if ($scheduledShift) {
                        $scheduledShift->update([
                            'status' => 'open',
                            'start_time' => Carbon::now()
                        ]);

                        foreach ($route->waRouteCustomer as $routeCustomer) {
                            if ($routeCustomer->status == 'approved') {
                                $scheduledShift->shiftCustomers()->create([
                                    'route_customer_id' => $routeCustomer->id,
                                    'salesman_shift_type' => $scheduledShift->shift_type,
                                ]);
                            }
                        }

                        UserLog::create([
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'module' => 'order_taking',
                            'activity' => "Started an order-taking shift for route $route->route_name",
                            'entity_id' => $scheduledShift->id,
                            'user_agent' => 'Bizwiz APP',
                        ]);
                    } else {
                        $shift = $user->salesmanShifts()->create([
                            'route_id' => $request->route_id,
                            'shift_type' => $request->shift_type_id == 1 ? 'onsite' : 'offsite',
                            'start_time' => Carbon::now()
                        ]);
                        foreach ($route->waRouteCustomer as $routeCustomer) {
                            if ($routeCustomer->status == 'approved') {
                                $shift->shiftCustomers()->create([
                                    'route_customer_id' => $routeCustomer->id,
                                    'salesman_shift_type' => $shift->shift_type,
                                ]);
                            }
                        }

                        UserLog::create([
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'module' => 'order_taking',
                            'activity' => "Started an order-taking shift for route $route->route_name",
                            'entity_id' => $shift->id,
                            'user_agent' => 'Bizwiz APP',
                        ]);
                    }
                } else if ($userRole->slug == 'delivery') {
                    $schedule = DeliverySchedule::with('route')
                        ->latest()
                        ->where('driver_id', $user->id)
                        ->where('status', '!=', 'finished')
                        ->first();
                    //check if all items for this delivery schedule have been consolidated

                    if ($schedule->status == 'consolidating') {
                        return $this->jsonify(['error' => 'Cannot start shift without receiving all delivery items'], 422);
                    } else {
                        $schedule->update(['actual_delivery_date' => Carbon::now(),]);
                        $preMileage = 0;
                        $preFuelLevel = 0;
                        $vehicle = Vehicle::find($schedule->vehicle_id);

                        // Get mileage and fuel
                        $response = Http::get('https://telematics.bizwizrp.com/api/devices/latest', [
                            'device_name' => $vehicle->device_name
                        ]);

                        if ($response->ok()) {
                            $responseData = json_decode($response->body(), true);
                            if ($responseData) {
                                if (isset($responseData['data']) && isset($responseData['data']['mileage'])) {
                                    $preMileage = $responseData['data']['mileage'] + $vehicle->odometer_adjustment;
                                }

                                if (isset($responseData['data']) && isset($responseData['data']['fuel_level'])) {
                                    $preFuelLevel = $responseData['data']['fuel_level'];
                                }
                            }
                        }

                        $lpo = FuelLpo::create([
                            'lpo_number' => $this->getOrCreateFuelLpoNumber(),
                            'branch_id' => $schedule->route->restaurant_id,
                            'deliveryman_id' => $user->id,
                            'route_id' => $schedule->route_id,
                            'vehicle_id' => $schedule->vehicle_id,
                            'pre_mileage' => $preMileage,
                            'pre_fuel' => $preFuelLevel,
                            'distance_estimate' => $schedule->route->sections->sum('distance_estimate'),
                            'fuel_estimate' => $schedule->route->manual_fuel_estimate
                        ]);

                        $schedule->update(['status' => 'in_progress']);
                        updateUniqueNumberSeries('FUEL LPO', $lpo->lpo_number);
                    }
                } else {
                    return $this->jsonify(['error' => 'The user must either be a salesman or a delivery man.'], 422);
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Shift Opened Successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            logger($e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function close(Request $request): JsonResponse
    {

        try {
            $user = JWTAuth::toUser($request->token);

            $userRole = $user->userRole;
            if ($userRole->slug == 'sales-man') {
                $shift = SalesmanShift::find($request->id);
                if ($shift) {
                    if ($shift->status == 'open') {
                        //                         $now = Carbon::now()->hour;
                        //                         if ($now < 15) {
                        //                             return $this->jsonify(['message' => "You can only close the shift after 3PM."], 422);
                        //                         }

                        $shift->update([
                            'status' => 'close',
                            'closed_time' => Carbon::now()
                        ]);

                        $route = Route::find($shift->route_id);
                        UserLog::create([
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'module' => 'order_taking',
                            'activity' => "Ended their order-taking shift for route $route->route_name",
                            'entity_id' => $shift->id,
                            'user_agent' => 'Bizwiz APP',
                        ]);

                        if (WaInternalRequisition::where('wa_shift_id', $shift->id)->count() > 0) {
                            $existingPackingList = SalesmanShiftStoreDispatch::latest()->where('shift_id', $shift->id)->first();
                            if ($existingPackingList) {
                                //remove line items
                                $items = SalesmanShiftStoreDispatchItem::where('dispatch_id', $existingPackingList->id)->get();
                                foreach ($items as $item) {
                                    $item->delete();
                                }

                                $existingPackingList->delete();
                            }
                            PrepareStoreParkingList::dispatch($shift)->afterCommit();

                            $existingDeliverySchedule = DeliverySchedule::latest()->where('shift_id', $shift->id)->first();
                            if ($existingDeliverySchedule) {
                                //remove  line items
                                $deliveryItems = DeliveryScheduleItem::where('delivery_schedule_id', $existingDeliverySchedule->id)->get();
                                foreach ($deliveryItems as $item) {
                                    $item->delete();
                                }
                                //customers
                                $customers = DeliveryScheduleCustomer::where('delivery_schedule_id', $existingDeliverySchedule->id)->get();
                                foreach ($customers as $customer) {
                                    $customer->delete();
                                }
                                $existingDeliverySchedule->delete();
                            }
                            CreateDeliverySchedule::dispatch($shift)->afterCommit();
                        }
                    } else {
                        return $this->jsonify([
                            'status' => false,
                            'error' => 'Shift not in open state!'
                        ], 400);
                    }
                } else {
                    return $this->jsonify([
                        'status' => false,
                        'error' => 'Shift not found!'
                    ], 404);
                }
            } elseif ($userRole->slug == 'delivery') {
                $schedule = DeliverySchedule::latest()->with('customers')
                    ->where('driver_id', $user->id)
                    ->where('status', '!=', 'finished')
                    ->first();

                if ($schedule) {
                    // check if schedule customers have been cleared
                    //                    $unvisitedCustomers = $schedule->customers->where('visited', false)->count();
                    //                    if ($unvisitedCustomers > 0) {
                    //                        return $this->jsonify(['message' => 'You have pending deliveries.'], 422);
                    //                    }

                    $schedule->update(['status' => 'finished', 'finish_time' => Carbon::now()]);
                } else {
                    return $this->jsonify([
                        'status' => false,
                        'error' => 'Delivery schedule not available!'
                    ], 400);
                }
            } else {
                return $this->jsonify([
                    'status' => false,
                    'error' => 'The user must either be a salesman or a delivery man.'
                ], 422);
            }

            DB::commit();

            return $this->jsonify([
                'status' => true,
                'message' => 'Shift closed successfully'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => $e->getTrace()
            ], 500);
        }
    }

    public function getUserCurrentShift(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'A user matching the provided token was not found.', 'data' => []], 422);
            }

            switch ($user->role_id) {
                case 4:
                    $shift = SalesmanShift::latest()->where('status', 'open')->where('salesman_id', $user->id)->first();
                    if (!$shift) {
                        return response()->json(['status' => false, 'message' => "This salesman does not have an active shift", 'data' => []], 422);
                    }

                    // $shiftData = $this->getShiftData($shift);
                    $shiftTotal = DB::table('wa_internal_requisition_items as items')
                        ->join('wa_internal_requisitions as orders', function ($join) use ($shift) {
                            $join->on('orders.id', '=', 'items.wa_internal_requisition_id')->where('wa_shift_id', $shift->id);
                        })
                        ->sum('total_cost_with_vat') ?? 0;

                    // $shiftDurationInMinutes = Carbon::now()->diffInMinutes(Carbon::parse($shift->start_time));

                    $shiftData = $data = [
                        'id' => $shift->id,
                        'shift_id' => "$shift->id",
                        'route_id' => $shift->route_id,
                        'shift_type' => $shift->shift_type,
                        'total_orders_amount' => manageAmountFormat($shiftTotal),
                        'start_time' => $shift->start_time,
                        'status' => 'open'
                        // 'shift_duration' => CarbonInterval::minutes($shiftDurationInMinutes)->cascade()->forHumans(),
                    ];

                    return response()->json(['status' => true, 'message' => 'Active Shift.', 'data' => $shiftData]);
                case 6:
                    $schedule = DeliverySchedule::with(['route', 'route.centers', 'route.waRouteCustomer'])->where('driver_id', $user->id)
                        ->where('status', 'in_progress')->first();
                    if (!$schedule) {
                        return response()->json(['status' => false, 'message' => "This user does not have an active shift", 'data' => []], 404);
                    }

                    $data = [
                        'id' => $schedule->id,
                        'route_id' => $schedule->route_id,
                        'shift_type' => 'onsite',
                        'route' => $schedule->route->route_name,
                        'status' => 'open',
                        'created_at' => $schedule->created_at,
                        'updated_at' => $schedule->updated_at,
                        'closed_time' => null,
                        'total_orders' => 0,
                        'total_orders_amount' => 0,
                        'shift_duration' => $schedule->shift_duration,
                        'target_amount' => 0,
                        'target_balance' => 0,
                        'shops_count' => $schedule->route->centers()->count() . " Shops",
                        'centres_count' => $schedule->route->waRouteCustomer()->count() . " Centers",
                    ];

                    $shiftOrders = WaInternalRequisition::where('wa_shift_id', $schedule->shift_id)->get();
                    $data['total_orders'] = count($shiftOrders);
                    foreach ($shiftOrders as $shiftOrder) {
                        $data['total_orders_amount'] += $shiftOrder->getOrderTotal();
                    }

                    $data['target_amount'] = $data['total_orders_amount'];

                    $data['total_orders_amount'] = format_amount_with_currency($data['total_orders_amount']);
                    $data['target_amount'] = format_amount_with_currency($data['target_amount']);
                    $data['target_balance'] = format_amount_with_currency($data['target_balance']);

                    return response()->json(['status' => true, 'message' => 'Active Shift.', 'data' => $data]);
                default:
                    return response()->json(['status' => false, 'message' => "The user must be a salesman or delivery man", 'data' => []], 422);
            }
        } catch (\Throwable $e) {
            return $this->jsonify(['status' => false, 'message' => 'A server error was encountered'], 500);
        }
    }

    public function getUserShiftList(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'A user matching the provided token was not found.', 'data' => []], 422);
            }

            $shifts = [];
            switch ($user->role_id) {
                case 4:
                    // Get today's day of week (0 = Sunday, 1 = Monday, etc.)
                    $today = Carbon::now()->dayOfWeek;
                    
                    // Get routes assigned to this salesman that have order-taking scheduled for today
                    $scheduledRoutes = $user->routes()
                        ->whereRaw("FIND_IN_SET(?, order_taking_days)", [$today])
                        ->get();
                    
                    $shiftsData = [];

                    // Always include any open shifts (even from previous days) so the app can present them for closure
                    $openShifts = SalesmanShift::where('salesman_id', $user->id)
                        ->where('status', 'open')
                        ->latest('id')
                        ->get();
                    $openRouteIds = $openShifts->pluck('route_id')->toArray();

                    foreach ($openShifts as $openShift) {
                        $openShift->setAppends([]);
                        $shiftsData[] = $this->getSalesmanShiftData($openShift);
                    }
                    
                    // For each scheduled route, check if a shift exists, otherwise show as scheduled
                    foreach ($scheduledRoutes as $route) {
                        if (in_array($route->id, $openRouteIds)) {
                            continue;
                        }
                        $existingShift = SalesmanShift::where('salesman_id', $user->id)
                            ->where('route_id', $route->id)
                            ->whereDate('created_at', Carbon::today())
                            ->latest('id')
                            ->first();
                        
                        if ($existingShift) {
                            // Shift already exists, return actual shift data
                            $existingShift->setAppends([]);
                            $shiftsData[] = $this->getSalesmanShiftData($existingShift);
                        } else {
                            // No shift created yet, return scheduled shift data
                            $customersCount = $route->waRouteCustomer()->count();
                            $shiftsData[] = [
                                'id' => null,
                                'shift_id' => "Scheduled - {$route->route_name}",
                                'route_id' => $route->id,
                                'route' => $route->route_name,
                                'route_name' => $route->route_name,
                                'shift_type' => 'scheduled',
                                'status' => 'scheduled',
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                                'closed_time' => null,
                                'start_time' => null,
                                'total_orders' => 0,
                                'total_orders_amount' => format_amount_with_currency(0),
                                'total_returns_amount' => format_amount_with_currency(0),
                                'total_discount_amount' => format_amount_with_currency(0),
                                'shift_duration' => '0 minutes',
                                'target_amount' => format_amount_with_currency($route->tonnage_target ?? 0),
                                'target_balance' => format_amount_with_currency(0),
                                'total_collections' => format_amount_with_currency(0),
                                'shops_count' => "$customersCount Shops",
                                'centres_count' => "0 Centers",
                                'distance' => "0 km",
                                'has_returns' => false,
                            ];
                        }
                    }
                    
                    // Return as paginated response format
                    $shifts = new \Illuminate\Support\Collection($shiftsData);
                    return response()->json(['status' => true, 'data' => $shifts], 200);
                    
                    break;
                case 6:
                    $shifts = DeliverySchedule::latest()->where('driver_id', $user->id)->whereIn('status', ['in_progress', 'finished']);
                    if ($request->search_date) {
                        $shifts = $shifts->whereDate('start_time', '=', Carbon::parse($request->search_date)->toDateString());
                    }

                    $shifts = $shifts->cursorPaginate(3)->through(function (DeliverySchedule $shift) {
                        return $this->getDriverShiftData($shift);
                    });

                    break;
                default:
                    break;
            }

            return $this->jsonify($shifts, 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['status' => false, 'message' => $e->getMessage(), 't' => $e->getTrace()], 500);
        }
    }

    public function getShift(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'A user matching the provided token was not found.', 'data' => []], 422);
            }

            $shiftData = match ($user->role_id) {
                4 => $this->getSalesmanShiftData(SalesmanShift::find($request->shift_id)),
                6 => $this->getDriverShiftData(DeliverySchedule::find($request->shift_id)),
                default => null
            };

            $shiftId = $request->shift_id;
            if ($user->role_id == 6) {
                $shiftId = DeliverySchedule::find($request->shift_id)?->shift_id;
            }

            $allItems = WaInternalRequisition::withCount('getRelatedItem as number_of_items')
                ->where('wa_shift_id', $shiftId)->orderBy('id', 'DESC')->get();

            foreach ($allItems as $item) {
                $relatedItems = $item->getRelatedItem;
                $totalSum = 0;
                $deliveryDate = '';
                foreach ($relatedItems as $relatedItem) {
                    $totalSum += $relatedItem->total_cost_with_vat;
                    $relatedItem->item_name = $relatedItem->getInventoryItemDetail->title;

                    $transferItem = WaInventoryLocationTransferItem::where('wa_internal_requisition_item_id', $relatedItem->id)->first();
                    $submittedReturns = WaInventoryLocationTransferItemReturn::where('wa_inventory_location_transfer_item_id', $transferItem?->id)->sum('return_quantity') ?? 0;
                    $receivedReturns = WaInventoryLocationTransferItemReturn::where('wa_inventory_location_transfer_item_id', $transferItem?->id)->sum('received_quantity') ?? 0;
                    $relatedItem->total_returns = (int)$submittedReturns;
                    $relatedItem->accepted_returns = $receivedReturns;
                    $relatedItem->formatted_returned_figures = "$receivedReturns/$submittedReturns";
                }

                $item->totalOrderAmount = $totalSum;
                $item->delivery_date = date('Y-m-d');

                $customer = WaRouteCustomer::select('id', 'name', 'bussiness_name')->find($item->wa_route_customer_id);
                if ($customer) {
                    $item->customer_name = $customer->name ?? ' ';
                    $item->business_name = $customer->bussiness_name ?? ' ';
                } else {
                    $item->customer_name = ' ';
                    $item->business_name = ' ';
                }
            }

            $payments = [];
            $shiftDelivery = DeliverySchedule::where('shift_id', (int)$shiftId)->first();
            if (($shiftDelivery?->status == 'in_progress') || ($shiftDelivery?->status == 'finished')) {
                $route = Route::select('id')->find($shiftDelivery->route_id);
                $customerAccount = WaCustomer::select('id', 'route_id')->where('route_id', $route->id)->first();
                $paymentDate = Carbon::parse($shiftDelivery->actual_delivery_date)->toDateString();
                $equityPayments = CustomerEquityPayment::where('matched_wa_customer_id', $customerAccount->id)->whereDate('created_at', '=', $paymentDate)->get()
                    ->map(function ($payment) {
                        return [
                            'created_at' => Carbon::parse($payment->created_at)->format('Y-m-d H:i A'),
                            'channel' => 'Eazzy',
                            'code' => $payment->narrative ?? $payment->transaction_reference,
                            'amount' => format_amount_with_currency($payment->paid_amount),
                            'customerName' => $payment->customer_name,
                            'raw_timestamp' => $payment->created_at
                        ];
                    });

                $kcbPayments = CustomerKcbPayment::where('matched_wa_customer_id', $customerAccount->id)->whereDate('created_at', '=', $paymentDate)->get()
                    ->map(function ($payment) {
                        return [
                            'created_at' => Carbon::parse($payment->created_at)->format('Y-m-d H:i A'),
                            'channel' => 'Vooma',
                            'code' => $payment->mpesa_reference,
                            'amount' => format_amount_with_currency($payment->paid_amount),
                            'customerName' => $payment->customer_name,
                            'raw_timestamp' => $payment->created_at
                        ];
                    });

                $payments = [...$payments, ...$equityPayments];
                $payments = [...$payments, ...$kcbPayments];

                $payments = collect($payments)->sortBy('raw_timestamp', descending: true)->values();
            }

            return response()->json(['status' => true, 'shift' => $shiftData, 'orders' => $allItems, 'payments' => $payments]);
        } catch (\Throwable $e) {
            return $this->jsonify(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // TODO: Check for usage, change accordingly
    private function getShiftData(SalesmanShift $shift): SalesmanShift
    {
        $route = Route::with('centers', 'waRouteCustomer')->find($shift->route_id);
        $shift->target_balance = $route->sales_target;
        $shift->target_amount = $route->sales_target;
        $shift->shops_count = "{$route->waRouteCustomer()->count()} Shops";
        $shift->centres_count = "{$route->centers()->count()} Centers";

        $shiftOrders = WaInternalRequisition::where('wa_shift_id', $shift->id)->get();
        $shift->total_orders = count($shiftOrders);
        $shift->total_orders_amount = 0;
        $shift->total_returns_amount = 0;
        $shift->total_discount_amount = 0;
        foreach ($shiftOrders as $shiftOrder) {
            $shift->total_orders_amount += $shiftOrder->getFinalTotal();
            $shift->total_returns_amount += $shiftOrder->getTotalReturns();
            $shift->total_discount_amount += $shiftOrder->getTotalDiscount();
        }

        if ($shift->total_orders_amount > 0) {
            $shift->target_balance = $shift->target_amount - $shift->total_orders_amount;
            if ($shift->target_balance < 0) {
                $shift->target_balance = 0;
            }
        }

        $shift->total_orders_amount = format_amount_with_currency($shift->total_orders_amount);
        $shift->total_returns_amount = format_amount_with_currency($shift->total_returns_amount);
        $shift->total_discount_amount = format_amount_with_currency($shift->total_discount_amount);
        $shift->target_balance = format_amount_with_currency($shift->target_balance);
        $shift->target_amount = format_amount_with_currency($shift->target_amount);

        if ($shift->start_time) {
            $shift->start_time = Carbon::parse($shift->start_time);
        }

        $transferIds = DB::table('wa_inventory_location_transfers')->join('wa_internal_requisitions', function ($join) use ($shift) {
            $join->on('wa_inventory_location_transfers.transfer_no', '=', 'wa_internal_requisitions.requisition_no')->where('wa_internal_requisitions.wa_shift_id', $shift->id);
        })->pluck('wa_inventory_location_transfers.id')->toArray();
        $returnsCount = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereIn('wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', $transferIds)->count();

        $shift->has_returns = $returnsCount > 0;

        return $shift;
    }

    private function getSalesmanShiftData(SalesmanShift $shift): SalesmanShift
    {
        $route = Route::with('sections')->find($shift->route_id);
        $shift->target_balance = $route->sales_target;
        $shift->target_amount = $route->sales_target;
        $shift->shops_count = "{$shift->shiftCustomers()->count()} Shops";
        $shopIds = $shift->shiftCustomers()->pluck('route_customer_id')->toArray();
        $centerIds = WaRouteCustomer::whereIn('id', $shopIds)->pluck('delivery_centres_id')->toArray();
        $centerIds = array_unique($centerIds);
        $shift->centres_count = count($centerIds) . " Centers";

        $shiftOrders = WaInternalRequisition::where('wa_shift_id', $shift->id)->get();
        $shift->total_orders = count($shiftOrders);
        $shift->total_orders_amount = 0;
        $shift->total_returns_amount = 0;
        $shift->total_discount_amount = 0;
        foreach ($shiftOrders as $shiftOrder) {
            $shift->total_orders_amount += $shiftOrder->getFinalTotal();
            $shift->total_returns_amount += $shiftOrder->getTotalReturns();
            $shift->total_discount_amount += $shiftOrder->getTotalDiscount();
        }

        $shift->total_collections = 0;
        $schedule = DeliverySchedule::where('shift_id', $shift->id)->first();
        if (($schedule?->status == 'in_progress') || ($schedule?->status == 'finished')) {
            $customerAccount = WaCustomer::select('id', 'route_id')->where('route_id', $shift->route_id)->first();
            $paymentDate = Carbon::parse($schedule->actual_delivery_date)->toDateString();
            $equityPayments = CustomerEquityPayment::where('matched_wa_customer_id', $customerAccount->id)->whereDate('created_at', '=', $paymentDate)->sum('paid_amount');
            $kcbPayments = CustomerKcbPayment::where('matched_wa_customer_id', $customerAccount->id)->whereDate('created_at', '=', $paymentDate)->sum('paid_amount');
            $shift->total_collections = $equityPayments + $kcbPayments;
        }

        if ($shift->total_orders_amount > 0) {
            $shift->target_balance = $shift->target_amount - $shift->total_orders_amount;
            if ($shift->target_balance < 0) {
                $shift->target_balance = 0;
            }
        }

        $shift->total_orders_amount = format_amount_with_currency($shift->total_orders_amount);
        $shift->total_returns_amount = format_amount_with_currency($shift->total_returns_amount);
        $shift->total_discount_amount = format_amount_with_currency($shift->total_discount_amount);
        $shift->target_balance = format_amount_with_currency($shift->target_balance);
        $shift->target_amount = format_amount_with_currency($shift->target_amount);
        $shift->total_collections = format_amount_with_currency($shift->total_collections);

        if ($shift->start_time) {
            $shift->start_time = Carbon::parse($shift->start_time);
        }

        $transferIds = DB::table('wa_inventory_location_transfers')->join('wa_internal_requisitions', function ($join) use ($shift) {
            $join->on('wa_inventory_location_transfers.transfer_no', '=', 'wa_internal_requisitions.requisition_no')->where('wa_internal_requisitions.wa_shift_id', $shift->id);
        })->pluck('wa_inventory_location_transfers.id')->toArray();
        $returnsCount = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereIn('wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', $transferIds)->count();

        $shift->has_returns = $returnsCount > 0;

        $distance = $route->sections->sum('distance_estimate');
        $shift->distance = round($distance / 1000, 2) . " km";
        $shift->route_name = $route->route_name;
        $shift->route = $route->route_name;

        return $shift;
    }

    private function getDriverShiftData(DeliverySchedule $shift): array
    {
        $shiftData = [];
        $route = Route::with('sections')->find($shift->route_id);
        $salesmanShift = SalesmanShift::find($shift->shift_id);
        $shiftData['target_balance'] = $salesmanShift->getPayableTotal();
        $shiftData['target_amount'] = $salesmanShift->getPayableTotal();
        $shiftData['shops_count'] = "{$shift->customers()->count()} Shops";
        $shopIds = $shift->customers()->pluck('customer_id')->toArray();
        $centerIds = WaRouteCustomer::whereIn('id', $shopIds)->pluck('delivery_centres_id')->toArray();
        $centerIds = array_unique($centerIds);
        $shiftData['centres_count'] = count($centerIds) . " Centers";

        $shiftOrders = WaInternalRequisition::where('wa_shift_id', $salesmanShift->id)->get();
        $shiftData['total_orders'] = count($shiftOrders);
        $shiftData['total_orders_amount'] = 0;
        $shiftData['total_returns_amount'] = 0;
        $shiftData['total_discount_amount'] = 0;
        foreach ($shiftOrders as $shiftOrder) {
            $shiftData['total_orders_amount'] += $shiftOrder->getFinalTotal();
            $shiftData['total_returns_amount'] += $shiftOrder->getTotalReturns();
            $shiftData['total_discount_amount'] += $shiftOrder->getTotalDiscount();
        }

        $customerAccount = WaCustomer::select('id', 'route_id')->where('route_id', $salesmanShift->route_id)->first();
        $paymentDate = Carbon::parse($shift->actual_delivery_date)->toDateString();
        $equityPayments = CustomerEquityPayment::where('matched_wa_customer_id', $customerAccount->id)->whereDate('created_at', '=', $paymentDate)->sum('paid_amount');
        $kcbPayments = CustomerKcbPayment::where('matched_wa_customer_id', $customerAccount->id)->whereDate('created_at', '=', $paymentDate)->sum('paid_amount');
        $shiftData['total_collections'] = $equityPayments + $kcbPayments;

        if ($shiftData['total_orders_amount'] > 0) {
            $shiftData['target_balance'] = $shiftData['target_amount'] - $shiftData['total_collections'];
            if ($shiftData['target_balance'] < 0) {
                $shiftData['target_balance'] = 0;
            }
        }

        $shiftData['total_orders_amount'] = format_amount_with_currency($shiftData['total_orders_amount']);
        $shiftData['total_returns_amount'] = format_amount_with_currency($shiftData['total_returns_amount']);
        $shiftData['total_discount_amount'] = format_amount_with_currency($shiftData['total_discount_amount']);
        $shiftData['target_balance'] = format_amount_with_currency($shiftData['target_balance']);
        $shiftData['target_amount'] = format_amount_with_currency($shiftData['target_amount']);
        $shiftData['total_collections'] = format_amount_with_currency($shiftData['total_collections']);

        if ($shift->start_time) {
            $shiftData['start_time'] = Carbon::parse($shift->start_time);
        }

        $transferIds = DB::table('wa_inventory_location_transfers')->join('wa_internal_requisitions', function ($join) use ($salesmanShift) {
            $join->on('wa_inventory_location_transfers.transfer_no', '=', 'wa_internal_requisitions.requisition_no')->where('wa_internal_requisitions.wa_shift_id', $salesmanShift->id);
        })->pluck('wa_inventory_location_transfers.id')->toArray();
        $returnsCount = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereIn('wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', $transferIds)->count();

        $shiftData['has_returns'] = $returnsCount > 0;

        // Meta
        $salesmanShiftDate = Carbon::parse($salesmanShift->created_at)->toDateString();
        $shiftData['shift_id'] = "$route->route_name - $salesmanShiftDate";
        $shiftData['id'] = $shift->id;
        $shiftData['route_id'] = $route->id;
        $shiftData['shift_type'] = 'onsite';
        $shiftData['route'] = $route->route_name;
        $shiftData['status'] = $shift->status;
        $shiftData['created_at'] = Carbon::parse($shift->created_at);
        $shiftData['updated_at'] = Carbon::parse($shift->updated_at);
        $shiftData['closed_time'] = Carbon::parse($shift->finish_time);
        $shiftData['shift_duration'] = '0 minutes';

        $distance = $route->sections->sum('distance_estimate');
        $shiftData['distance'] = round($distance / 1000, 2) . " km";

        if (isset($shiftData['start_time'])) {
            $closeTime = Carbon::now();
            if ($shift->status == 'finished') {
                $closeTime = Carbon::parse($shift->finish_time);
            }
            $duration = $closeTime->diffInMinutes($shiftData['start_time']);
            $shiftData['shift_duration'] = CarbonInterval::minutes(ceil($duration / 60))->cascade()->forHumans();
        }

        return $shiftData;
    }

    private function getOrCreateFuelLpoNumber(): string
    {
        $seriesCode = WaNumerSeriesCode::where('module', 'FUEL LPO')->first();
        if (!$seriesCode) {
            WaNumerSeriesCode::create([
                'code' => 'FPO',
                'module' => 'FUEL LPO',
                'description' => 'Fuel LPOs',
                'starting_number' => 0,
                'last_date_used' => Carbon::now()->toDateString(),
                'last_number_used' => 0,
                'type_number' => 1,
            ]);
        }

        return getCodeWithNumberSeries('FUEL LPO');
    }
}
