<?php

namespace App\Http\Controllers\Admin;

use App\FuelLpo;
use App\Vehicle;
use Carbon\Carbon;
use App\WalletTran;
use App\Model\Route;
use App\Model\UserLog;
use App\SalesmanShift;
use App\Model\WaShift;
use App\DeliverySchedule;
use App\Model\WaCustomer;
use App\OffsiteShiftRequest;
use Illuminate\Http\Request;
use App\Models\PettyCashType;
use App\Enums\FuelEntryStatus;
use App\Model\WaRouteCustomer;
use App\Services\DeliveryService;
use App\Services\DispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Enums\FuelEntryParentTypes;
use App\SalesmanShiftStoreDispatch;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use App\Models\PettyCashTransaction;
use Illuminate\Support\Facades\Http;
use App\Models\TravelExpenseTransaction;
use Illuminate\Database\Query\JoinClause;
use App\WaInventoryLocationTransferItemReturn;
use App\NewFuelEntry;
use App\Models\SaleCenterSmallPacks;
use App\Models\SaleCenterSmallPackItems;
use App\Model\DeliveryCentres;


class ShiftController extends Controller
{
    public function open(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return $this->jsonify(['message' => getTokenHasNoUserMessage(), 'status' => false], 422);
            }

            return match ($user->role_id) {
                4 => $this->openSalesmanShift($user, $request->route_id),
                6 => $this->openDriverShift($user),
                default => $this->jsonify(['You are not eligible to open a shift. You are neither a salesman or  a driver.'], 422),
            };
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage(), 'status' => false], 422);
        }
    }

    public function close(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            if (!$request->id) {
                return $this->jsonify(['status' => false, 'message' => 'Shift ID is required'], 422);
            }

            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return $this->jsonify(['message' => getTokenHasNoUserMessage(), 'status' => false], 422);
            }
            return match ($user->role_id) {
                4 => $this->closeSalesmanShift($request, $user),
                6 => $this->closeDriverShift($user),
                default => $this->jsonify(['You are not eligible to open a shift. You are neither a salesman or  a driver.'], 422),
            };
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage(), 'status' => false], 422);
        }
    }

    private function openSalesmanShift($user, $route_id = null): JsonResponse
    {
        // Removed time restriction - allow shift opening at any time
        // $now = Carbon::now()->hour;
        // if ($now > 20) {
        //     return $this->jsonify(['message' => "Sorry, you can not open a shift after 6PM. Try again tomorrow."], 422);
        // }

        $route = Route::find($route_id);
        if (!$route) {
            return $this->jsonify(['message' => 'The provided route id is invalid'], 422);
        }

        //check if user belongs to route
        if (!in_array($route->id, $user->routes->pluck('id')->toArray())) {
            return $this->jsonify(['status' => false, 'message' => 'The salesman does not belong to this route'], 422);
        }

        $customer = WaCustomer::where('route_id', $route_id)->first();
        if ($customer->is_blocked == 1) {
            return response()->json(['result' => -1, 'message' => "Your account is blocked from making any orders. Please contact your accounts manager."], 422);
        }

        $salesmanHasOpenShift = SalesmanShift::where('salesman_id', $user->id)->where('status', 'open')->first();
        if ($salesmanHasOpenShift) {
            return $this->jsonify(['message' => 'Sorry, you already have an open shift.', 'status' => false], 422);
        }

        //check if it is order taking  day  for route
        $today = Carbon::now()->dayOfWeek;
        $orderTakingDays = explode(',', $route->order_taking_days);
        if (!in_array((string)$today, $orderTakingDays)) {
            return $this->jsonify(['status' => false, 'message' => "Sorry, today is not order taking day for $route->route_name"], 422);
        }

        // Check for pending returns
        $pendingReturns = DB::table('wa_inventory_location_transfer_item_returns')
            ->where('wa_inventory_location_transfer_item_returns.status', 'pending')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 1)
            ->join('wa_inventory_location_transfers', function (JoinClause $join) use ($route) {
                $join->on('wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
                    ->where('wa_inventory_location_transfers.route', $route->route_name);
            })
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_items.id', '=', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id')
            ->join('wa_inventory_location_uom', function ($join) {
                $join->on('wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_location_transfer_items.wa_inventory_item_id')
                    ->whereColumn('wa_inventory_location_uom.location_id', '=', 'wa_inventory_location_transfer_items.store_location_id');
            })
            ->join('wa_unit_of_measures', 'wa_unit_of_measures.id', '=', 'wa_inventory_location_uom.uom_id')
            ->select('wa_unit_of_measures.title as bin')
            ->get();

        if ($pendingReturns->count() > 0) {
            $bins = implode(', ', $pendingReturns->map(fn($return) => $return->bin)->unique()->toArray());
            return $this->jsonify(['status' => false, 'message' => "You cannot start a shift with unprocessed returns. Please clear with the store and try again. Bins: $bins"], 422);
        }

        $lastShift = SalesmanShift::latest()->where('salesman_id', $user->id)->where('route_id', $route->id)->whereDate('created_at', '=', Carbon::now()->toDateString())->first();
        $routeCustomers = WaRouteCustomer::where('route_id', $route->id)->where('status', 'approved')->get();
        if (!$lastShift) {
            $this->openNewShift($user, $route, $routeCustomers);
            DB::commit();
            return $this->jsonify(['status' => true, 'message' => 'Shift opened successfully'], 200);
        }

        if ($lastShift->status == 'not_started') {
            $lastShift->update([
                'status' => 'open',
                'start_time' => Carbon::now(),
            ]);

            foreach ($routeCustomers as $routeCustomer) {
                $lastShift->shiftCustomers()->create([
                    'route_customer_id' => $routeCustomer->id,
                ]);
            }

            UserLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'module' => 'order_taking',
                'activity' => "Started an onsite order-taking shift for $route->route_name",
                'entity_id' => $lastShift->id,
                'user_agent' => 'Bizwiz APP',
            ]);

            DB::commit();
            return $this->jsonify(['status' => true, 'message' => 'Shift opened successfully'], 200);
        }

        if ($lastShift->block_orders) {
            //check maximum shifts per day
            $routeShiftsForTheDay = SalesmanShift::where('salesman_id', $user->id)->where('route_id', $route->id)
                ->whereDate('created_at', '=', Carbon::now()->toDateString())->count();
            if ($routeShiftsForTheDay >= $route->maximum_allowed_shifts) {
                return $this->jsonify(['message' => "You have exceeded the maximum allowed shifts for $route->route_name today"], 422);
            }

            $this->openNewShift($user, $route, $routeCustomers);
            DB::commit();
            return $this->jsonify(['status' => true, 'message' => 'New shift opened successfully'], 200);
        }

        $lastShift->update([
            'status' => 'open'
        ]);

        UserLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'module' => 'order_taking',
            'activity' => "Reopened order-taking shift for $route->route_name as $lastShift->shift_type",
            'entity_id' => $lastShift->id,
            'user_agent' => 'Bizwiz APP',
        ]);

        DB::commit();
        return $this->jsonify(['status' => true, 'message' => 'Your previous shift has been re-opened successfully.'], 200);
    }

    private function openNewShift($user, $route, $routeCustomers): void
    {
        SalesmanShift::where('route_id', $route->id)->where('status', 'not_started')->whereDate('created_at', Carbon::now()->toDateString())->delete();
        $shift = SalesmanShift::create([
            'salesman_id' => $user->id,
            'route_id' => $route->id,
            'status' => 'open',
            'shift_type' => 'onsite',
            'start_time' => Carbon::now(),
        ]);

        // Create corresponding WaShift record for financial data linkage
        $waShift = new WaShift();
        $waShift->shift_id = 'SS-' . $shift->id . '-' . date('Ymd');
        $waShift->salesman_id = $user->id;
        $waShift->route = $route->route_name;
        $waShift->status = 'open';
        $waShift->shift_date = Carbon::now()->toDateString();
        $waShift->save();

        foreach ($routeCustomers as $routeCustomer) {
            $shift->shiftCustomers()->create([
                'route_customer_id' => $routeCustomer->id,
            ]);
        }

        UserLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'module' => 'order_taking',
            'activity' => "Started an onsite order-taking shift for $route->route_name",
            'entity_id' => $shift->id,
            'user_agent' => 'Bizwiz APP',
        ]);
    }

    private function closeSalesmanShift($request, $user): JsonResponse
    {
        $shift = SalesmanShift::find($request->id);
        
        // If not found in salesman_shifts, check wa_shifts
        if (!$shift) {
            $waShift = WaShift::find($request->id);
            if (!$waShift) {
                return $this->jsonify(['message' => 'The provided shift id is invalid'], 422);
            }
            
            // Close the wa_shift
            $waShift->update(['status' => 'close']);
            
            UserLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'module' => 'order_taking',
                'activity' => "Closed shift for {$waShift->route}",
                'entity_id' => $waShift->id,
                'user_agent' => 'Bizwiz APP',
            ]);
            
            DB::commit();
            return $this->jsonify(['message' => 'Shift closed successfully'], 200);
        }
        
        $route = Route::withCount('waRouteCustomer')->find($shift->route_id);
        if (!in_array($route->id, $user->routes->pluck('id')->toArray())) {
            return $this->jsonify(['status' => false, 'message' => 'The salesman does not belong to this route'], 422);
        }
        //check if all  centers have initiated dispatching
        // $saleCenterSmallPacks = SaleCenterSmallPacks::where('shift_id', $shift->id)->pluck('id')->toArray();
        // $saleCenterSmallPackItems = SaleCenterSmallPackItems::whereIn('sale_center_small_pack_id', $saleCenterSmallPacks)->pluck('requisition_no')->toArray();

        // $unInitiatedOrders = WaInternalRequisition::where('wa_shift_id', $shift->id)
        //     ->leftJoin('wa_internal_requisition_items', 'wa_internal_requisitions.id', 'wa_internal_requisition_items.wa_internal_requisition_id')
        //     ->leftJoin('wa_inventory_location_uom', function($query) use($user) {
        //         $query->on('wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_location_uom.inventory_id')
        //         ->where('wa_inventory_location_uom.location_id', $user->wa_locatioon_and_store_id);

        //     })
        //     ->leftJoin('wa_unit_of_measures', 'wa_unit_of_measures.id', 'wa_inventory_location_uom.uom_id')
        //     ->where('wa_unit_of_measures.is_display', 1)
        //     ->whereNotIn('requisition_no', $saleCenterSmallPackItems)->first();
        // if($unInitiatedOrders){
        //     $routeCustomer = WaRouteCustomer::find($unInitiatedOrders->wa_route_customer_id);
        //     $centerDetails = DeliveryCentres::find($routeCustomer->delivery_centres_id);
        //     return $this->jsonify(['status' => false, 'message' => 'Center '. $centerDetails->name.' have not been marked  complete. Please mark them as complete  to allow loading.'], 422);
        // }
        $inactiveCenter = DeliveryCentres::where('route_id', $shift->route_id)->where('is_active', 0)->first();
        if($inactiveCenter){
             return $this->jsonify(['status' => false, 'message' => 'Center '. $inactiveCenter->name.' has not been marked  complete. Please mark them as complete  to allow loading.'], 422);
        }

        $shift->update(['status' => 'close', 'closed_time' => Carbon::now()]);

        UserLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'module' => 'order_taking',
            'activity' => "Closed shift for $route->route_name",
            'entity_id' => $shift->id,
            'user_agent' => 'Bizwiz APP',
        ]);

        if ($offsiteRequest = OffsiteShiftRequest::latest()->where('shift_id', $shift->id)->where('status', 'pending')->first()) {
            $offsiteRequest->update(['status' => 'expired']);
        }

        $existingDispatchIds = SalesmanShiftStoreDispatch::where('shift_id', $shift->id)->pluck('id')->toArray();
        SalesmanShiftStoreDispatch::destroy($existingDispatchIds);


        $existingDeliveryIds = DeliverySchedule::where('shift_id', $shift->id)->pluck('id')->toArray();
        DeliverySchedule::destroy($existingDeliveryIds);

        if ($shiftHasOrders = WaInternalRequisition::where('wa_shift_id', $shift->id)->count() > 0) {
            DispatchService::prepareLoadingSheets($shift->id, $user);
            DeliveryService::createDeliverySchedule($shift->id, $route->id);

            try {
                $today = Carbon::now()->toDateString();

                $shiftdata = DB::table('salesman_shift_customers')
                    ->where('salesman_shift_id', $shift->id)
                    ->where('visited', 1)
                    ->orderBy('updated_at', 'asc')
                    ->first();

                if ($shiftdata) {
                    $travelExpenseIncentive = DB::table('travel_expense_transactions')->where('user_id', $user->id)
                        ->orderBy('created_at', 'DESC')
                        ->where('shift_id', $shift->id)
                        ->where('shift_type', 'order_taking')
                        ->first();

                    $orderShiftType = DB::table('salesman_shift_customers')
                        ->select('salesman_shift_type', DB::raw('count(*) as count'))
                        ->where('salesman_shift_id', $shift->id)
                        ->where('visited', 1)
                        ->groupBy('salesman_shift_type')
                        ->union(
                            DB::table('salesman_shift_customers')
                                ->select(DB::raw("'total' as salesman_shift_type"), DB::raw('count(*) as count'))
                                ->where('salesman_shift_id', $shift->id)
                                ->where('visited', 1)
                        )
                        ->pluck('count', 'salesman_shift_type');

                    $incentiveAmount = 0;
                    $total_incentive_amount = 0;
                    $routeoffsiteallowance = floatval($route->offsite_allowance) / floatval($route->wa_route_customer_count);
                    $routeonsiteallowance = floatval($route->travel_expense) / floatval($route->wa_route_customer_count);

                    $onsitecount = $orderShiftType->get('onsite', 0);
                    $offsitecount = $orderShiftType->get('offsite', 0);

                    if ($offsitecount > 0 && $onsitecount > 0) {
                        $total_incentive_amount = $route->offsite_allowance + $route->travel_expense;
                    } else if ($offsitecount <= 0 && $onsitecount > 0) {
                        $total_incentive_amount = $route->travel_expense;
                    } else if ($offsitecount > 0 && $onsitecount <= 0) {
                        $total_incentive_amount = $route->offsite_allowance;
                    }

                    if (!$travelExpenseIncentive) {
                        if ($shiftdata->salesman_shift_type == 'onsite') {

                            $documentNumber = getCodeWithNumberSeries('PETTY_CASH');
                            $walletType = PettyCashType::where('slug', 'travel-expense')->first();

                            $calculatedIncentiveAmount = 0;
                            $total_customers = $route->wa_route_customer_count;
                            $customers_visited = $total_customers - $onsitecount;

                            if (floatval($total_customers) > 0) {
                                $calculatedrouteonsiteallowance = floatval($route->travel_expense) / floatval($total_customers);
                            } else {
                                $calculatedrouteonsiteallowance = 0;
                            }

                            if (floatval($customers_visited) > 0) {
                                $calculatedrouteoffsiteallowance = floatval($route->offsite_allowance) / floatval($total_customers);
                            } else {
                                $calculatedrouteoffsiteallowance = 0;
                            }

                            $calculatedoffsiteamount = floatval($offsitecount * $calculatedrouteoffsiteallowance);
                            $calculatedonsiteamount = floatval($onsitecount * $calculatedrouteonsiteallowance);
                            $calculatedIncentiveAmount = ceil($calculatedonsiteamount + $calculatedoffsiteamount);

                            if (floatval($calculatedIncentiveAmount) < $total_incentive_amount) {
                                $incentive = TravelExpenseTransaction::create([
                                    'transaction_type' => 'incentive',
                                    'user_id' => $user->id,
                                    'route_id' => $route->id,
                                    'shift_id' => $shift->id,
                                    'shift_type' => 'order_taking',
                                    'amount' => $calculatedIncentiveAmount,
                                    'document_no' => $documentNumber,
                                    'wallet_type' => $walletType?->title,
                                    'wallet_type_id' => $walletType?->id,
                                    'reference' => "$user->name/$route->route_name/TRAVEL EXPENSE",
                                    'narrative' => "Salesman travel expense for route $route->route_name",
                                ]);

                                PettyCashTransaction::create([
                                    'user_id' => $incentive->user_id,
                                    'amount' => $incentive->amount,
                                    'document_no' => $incentive->document_no,
                                    'wallet_type' => $incentive->wallet_type,
                                    'wallet_type_id' => $incentive->wallet_type_id,
                                    'parent_id' => $incentive->id,
                                    'reference' => $incentive->reference,
                                    'narrative' => $incentive->narrative,
                                    'call_back_status' => 'complete',
                                ]);
                                updateUniqueNumberSeries('PETTY_CASH', $documentNumber);
                            } else {
                                $incentive = TravelExpenseTransaction::create([
                                    'transaction_type' => 'incentive',
                                    'user_id' => $user->id,
                                    'route_id' => $route->id,
                                    'shift_id' => $shift->id,
                                    'shift_type' => 'order_taking',
                                    'amount' => $total_incentive_amount,
                                    'document_no' => $documentNumber,
                                    'wallet_type' => $walletType?->title,
                                    'wallet_type_id' => $walletType?->id,
                                    'reference' => "$user->name/$route->route_name/TRAVEL EXPENSE",
                                    'narrative' => "Salesman travel expense for route $route->route_name",
                                ]);

                                PettyCashTransaction::create([
                                    'user_id' => $incentive->user_id,
                                    'amount' => $incentive->amount,
                                    'document_no' => $incentive->document_no,
                                    'wallet_type' => $incentive->wallet_type,
                                    'wallet_type_id' => $incentive->wallet_type_id,
                                    'parent_id' => $incentive->id,
                                    'reference' => $incentive->reference,
                                    'narrative' => $incentive->narrative,
                                    'call_back_status' => 'complete',
                                ]);
                                updateUniqueNumberSeries('PETTY_CASH', $documentNumber);
                            }
                        } else if ($shiftdata->salesman_shift_type == 'offsite') {

                            $documentNumber = getCodeWithNumberSeries('PETTY_CASH');
                            $walletType = PettyCashType::where('slug', 'travel-expense')->first();

                            $calculatedIncentiveAmount = 0;
                            $total_customers = $route->wa_route_customer_count;
                            $customers_visited = $total_customers - $offsitecount;

                            if (floatval($customers_visited) > 0) {
                                $calculatedrouteonsiteallowance = floatval($route->travel_expense) / floatval($total_customers);
                            } else {
                                $calculatedrouteonsiteallowance = 0;
                            }

                            if (floatval($total_customers) > 0) {
                                $calculatedrouteoffsiteallowance = floatval($route->offsite_allowance) / floatval($total_customers);
                            } else {
                                $calculatedrouteoffsiteallowance = 0;
                            }

                            $calculatedonsiteamount = floatval($onsitecount * $calculatedrouteonsiteallowance);
                            $calculatedoffsiteamount = floatval($offsitecount * $calculatedrouteoffsiteallowance);
                            $calculatedIncentiveAmount = ceil($calculatedoffsiteamount + $calculatedonsiteamount);

                            if (floatval($calculatedIncentiveAmount) < $total_incentive_amount) {
                                $incentive = TravelExpenseTransaction::create([
                                    'transaction_type' => 'incentive',
                                    'user_id' => $user->id,
                                    'route_id' => $route->id,
                                    'shift_id' => $shift->id,
                                    'shift_type' => 'order_taking',
                                    'amount' => $calculatedIncentiveAmount,
                                    'document_no' => $documentNumber,
                                    'wallet_type' => $walletType?->title,
                                    'wallet_type_id' => $walletType?->id,
                                    'reference' => "$user->name/$route->route_name/TRAVEL EXPENSE",
                                    'narrative' => "Salesman travel expense for route $route->route_name",
                                ]);

                                PettyCashTransaction::create([
                                    'user_id' => $incentive->user_id,
                                    'amount' => $incentive->amount,
                                    'document_no' => $incentive->document_no,
                                    'wallet_type' => $incentive->wallet_type,
                                    'wallet_type_id' => $incentive->wallet_type_id,
                                    'parent_id' => $incentive->id,
                                    'reference' => $incentive->reference,
                                    'narrative' => $incentive->narrative,
                                    'call_back_status' => 'complete',
                                ]);
                                updateUniqueNumberSeries('PETTY_CASH', $documentNumber);
                            } else {
                                $incentive = TravelExpenseTransaction::create([
                                    'transaction_type' => 'incentive',
                                    'user_id' => $user->id,
                                    'route_id' => $route->id,
                                    'shift_id' => $shift->id,
                                    'shift_type' => 'order_taking',
                                    'amount' => $total_incentive_amount,
                                    'document_no' => $documentNumber,
                                    'wallet_type' => $walletType?->title,
                                    'wallet_type_id' => $walletType?->id,
                                    'reference' => "$user->name/$route->route_name/TRAVEL EXPENSE",
                                    'narrative' => "Salesman travel expense for route $route->route_name",
                                ]);

                                PettyCashTransaction::create([
                                    'user_id' => $incentive->user_id,
                                    'amount' => $incentive->amount,
                                    'document_no' => $incentive->document_no,
                                    'wallet_type' => $incentive->wallet_type,
                                    'wallet_type_id' => $incentive->wallet_type_id,
                                    'parent_id' => $incentive->id,
                                    'reference' => $incentive->reference,
                                    'narrative' => $incentive->narrative,
                                    'call_back_status' => 'complete',
                                ]);
                                updateUniqueNumberSeries('PETTY_CASH', $documentNumber);
                            }
                        }
                    } else {
                        if ($shiftdata->salesman_shift_type == 'onsite') {
                            $calculatedIncentiveAmount = 0;
                            $total_customers = $route->wa_route_customer_count;
                            $customers_visited = $total_customers - $onsitecount;

                            if (floatval($total_customers) > 0) {
                                $calculatedrouteonsiteallowance = floatval($route->travel_expense) / floatval($total_customers);
                            } else {
                                $calculatedrouteonsiteallowance = 0;
                            }

                            if (floatval($customers_visited) > 0) {
                                $calculatedrouteoffsiteallowance = floatval($route->offsite_allowance) / floatval($total_customers);
                            } else {
                                $calculatedrouteoffsiteallowance = 0;
                            }

                            $calculatedoffsiteamount = floatval($offsitecount * $calculatedrouteoffsiteallowance);
                            $calculatedonsiteamount = floatval($onsitecount * $calculatedrouteonsiteallowance);
                            $calculatedIncentiveAmount = ceil($calculatedonsiteamount + $calculatedoffsiteamount);

                            if (floatval($calculatedIncentiveAmount) < $total_incentive_amount) {
                                $incentivedata = TravelExpenseTransaction::where('shift_id', $shift->id)->where('shift_type', 'order_taking')->first();
                                $incentivedata->amount = $calculatedIncentiveAmount;
                                $incentivedata->save();
                                $pettycashdata = PettyCashTransaction::where('parent_id', $incentivedata->id)->where('user_id', $incentivedata->user_id)->first();
                                $pettycashdata->amount = $incentivedata->amount;
                                $pettycashdata->save();
                            } else {
                                $incentivedata = TravelExpenseTransaction::where('shift_id', $shift->id)->where('shift_type', 'order_taking')->first();
                                $incentivedata->amount = $total_incentive_amount;
                                $incentivedata->save();
                                $pettycashdata = PettyCashTransaction::where('parent_id', $incentivedata->id)->where('user_id', $incentivedata->user_id)->first();
                                $pettycashdata->amount = $incentivedata->amount;
                                $pettycashdata->save();
                            }
                        } else if ($shiftdata->salesman_shift_type == 'offsite') {
                            $calculatedIncentiveAmount = 0;
                            $total_customers = $route->wa_route_customer_count;
                            $customers_visited = $total_customers - $offsitecount;

                            if (floatval($customers_visited) > 0) {
                                $calculatedrouteonsiteallowance = floatval($route->travel_expense) / floatval($total_customers);
                            } else {
                                $calculatedrouteonsiteallowance = 0;
                            }

                            if (floatval($total_customers) > 0) {
                                $calculatedrouteoffsiteallowance = floatval($route->offsite_allowance) / floatval($total_customers);
                            } else {
                                $calculatedrouteoffsiteallowance = 0;
                            }

                            $calculatedonsiteamount = floatval($onsitecount * $calculatedrouteonsiteallowance);
                            $calculatedoffsiteamount = floatval($offsitecount * $calculatedrouteoffsiteallowance);
                            $calculatedIncentiveAmount = ceil($calculatedoffsiteamount + $calculatedonsiteamount);

                            if (floatval($calculatedIncentiveAmount) < $total_incentive_amount) {
                                $incentivedata = TravelExpenseTransaction::where('shift_id', $shift->id)->where('shift_type', 'order_taking')->first();
                                $incentivedata->amount = $calculatedIncentiveAmount;
                                $incentivedata->save();
                                $pettycashdata = PettyCashTransaction::where('parent_id', $incentivedata->id)->where('user_id', $incentivedata->user_id)->first();
                                $pettycashdata->amount = $incentivedata->amount;
                                $pettycashdata->save();
                            } else {
                                $incentivedata = TravelExpenseTransaction::where('shift_id', $shift->id)->where('shift_type', 'order_taking')->first();
                                $incentivedata->amount = $total_incentive_amount;
                                $incentivedata->save();
                                $pettycashdata = PettyCashTransaction::where('parent_id', $incentivedata->id)->where('user_id', $incentivedata->user_id)->first();
                                $pettycashdata->amount = $incentivedata->amount;
                                $pettycashdata->save();
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("close salesman shift error" . $e->getMessage());
            }
        }

        DB::commit();
        return $this->jsonify(['message' => 'Shift closed successfully'], 200);
    }

    private function openDriverShift($user): JsonResponse
    {
        $schedule = DeliverySchedule::with('route')
            ->where('driver_id', $user->id)
            ->where('status', '!=', 'finished')
            ->first();

        if (!$schedule) {
            return $this->jsonify([
                'message' => 'You do not have an open shift.  Please contact your route supervisor',
            ], 422);
        }

        $shift = $schedule->shift;
        if ($schedule->status != 'loaded') {
            $nonDispatchedSheets = SalesmanShiftStoreDispatch::with('bin')->latest()->where('shift_id', $shift->id)->where('received', false)->get();
            if (count($nonDispatchedSheets) != 0) {

                $bins = $nonDispatchedSheets->map(function ($item) {
                    return $item->bin->title;
                });

                return $this->jsonify([
                    'message' => 'You cannot start shift without receiving and loading all delivery items',
                ], 422);
            }
        }
        
        if ($schedule->gate_pass_status == 'pending') {
            return $this->jsonify(['message' => 'Your gate pass has not been created. Please contact your route supervisor.'], 422);
        }

        if ($schedule->gate_pass_status == 'initiated') {
            return $this->jsonify(['message' => 'Your gate pass has not been verified. Please verify at the gate and try again.'], 422);
        }

        $schedule->update(['actual_delivery_date' => Carbon::now()]);
        $schedule->update(['status' => 'in_progress']);

        $today = Carbon::now()->toDateString();
        $vehicle = Vehicle::latest()->with('model')->where('driver_id', $user->id)->first();
        $travelExpenseIncentive = DB::table('travel_expense_transactions')->where('user_id', $user->id)->orderBy('created_at', 'DESC')
            ->whereDate('created_at', $today)->first();
        if (!$travelExpenseIncentive) {
            $documentNumber = getCodeWithNumberSeries('PETTY_CASH');
            $walletType = PettyCashType::where('slug', 'travel-expense')->first();
            $route = Route::select('id', 'route_name')->find($schedule->route_id);
            $incentive = TravelExpenseTransaction::create([
                'transaction_type' => 'incentive',
                'user_id' => $user->id,
                'route_id' => $route->id,
                'shift_id' => $schedule->id,
                'shift_type' => 'delivery',
                'amount' => $vehicle->model?->travel_expense ?? 0,
                'document_no' => $documentNumber,
                'wallet_type' => $walletType->title,
                'wallet_type_id' => $walletType->id,
                'reference' => "$user->name/$route->route_name/TRAVEL EXPENSE",
                'narrative' => "Driver travel expense for route $route->name",
            ]);

            PettyCashTransaction::create([
                'user_id' => $incentive->user_id,
                'amount' => $incentive->amount,
                'document_no' => $incentive->document_no,
                'wallet_type' => $incentive->wallet_type,
                'wallet_type_id' => $incentive->wallet_type_id,
                'parent_id' => $incentive->id,
                'reference' => $incentive->reference,
                'narrative' => $incentive->narrative,
            ]);

            updateUniqueNumberSeries('PETTY_CASH', $documentNumber);
        }
        //create fuel lpo
        try {
            $lpoNumber = getCodeWithNumberSeries('FUEL LPO');
            $now = Carbon::now()->todateTimeString();
            $tenMinutesAgo = Carbon::now()->subMinutes(10)->todateTimeString();
            $telematicsRecord = DB::connection('telematics')
                ->table('vehicle_telematics')
                ->where('device_number', $vehicle->license_plate_number)
                ->whereBetween('timestamp', [$tenMinutesAgo, $now])
                ->orderBy('timestamp', 'DESC')
                ->first();
            $fuelEntry = new NewFuelEntry();
            $fuelEntry->lpo_number = $lpoNumber;
            $fuelEntry->vehicle_id = $vehicle->id;
            $fuelEntry->shift_type = FuelEntryParentTypes::RouteDelivery->value;
            $fuelEntry->shift_id = $schedule->id;
            if ($telematicsRecord) {
                $fuelEntry->last_fuel_entry_mileage = $telematicsRecord->mileage;
            }
            $fuelEntry->save();

            updateUniqueNumberSeries('FUEL LPO', $lpoNumber);
        } catch (\Throwable $th) {
            // pass
        }

        DB::commit();
        return $this->jsonify(['message' => 'Shift started successfully'], 200);
    }

    private function closeDriverShift($user): JsonResponse
    {
        //        return $this->jsonify(['message' => 'Your shift will be automatically ended after you have fueled'], 422);

        $schedule = DeliverySchedule::with('customers')
            ->where('driver_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($schedule) {
            $allScheduleCustomers = $schedule->customers->count();
            $unvisitedCustomers = $schedule->customers->where('visited', false)->count();
            // if ($unvisitedCustomers > 0) {
            if ($unvisitedCustomers > (0.15 * $allScheduleCustomers)) {
                return $this->jsonify(['message' => 'You have pending deliveries.'], 422);
            }

            $driverHasNotFueled = DB::table('fuel_entries')
                ->where('vehicle_id', $schedule->driver_id)
                ->where('shift_id', $schedule->id)
                ->where('shift_type', FuelEntryParentTypes::RouteDelivery->value)
                ->where('entry_status', FuelEntryStatus::Pending->value)
                ->first();
            if ($driverHasNotFueled) {
                return $this->jsonify(['message' => 'Your shift will be automatically ended after you have fueled'], 422);
            }

            $schedule->update(['status' => 'finished', 'finish_time' => Carbon::now()]);

            DB::commit();
            return $this->jsonify(['message' => 'Shift closed successfully'], 200);
        } else {
            return $this->jsonify([
                'status' => false,
                'error' => 'Delivery schedule not available!'
            ], 400);
        }
    }
}
