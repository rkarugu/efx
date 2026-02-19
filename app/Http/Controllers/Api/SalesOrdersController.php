<?php

namespace App\Http\Controllers\Api;

use App\DeliverySchedule;
use App\Interfaces\SmsService;
use App\ItemPromotion;
use App\Model\Notification;
use App\Model\PackSize;
use App\Model\Setting;
use App\Model\WaAccountingPeriod;
use App\Model\WaCashSales;
use App\Model\WaCategoryItemPrice;
use App\Model\WaCustomer;
use App\Model\WaDebtorTran;
use App\Model\WaEsdDetails;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\WaInventoryLocationUom;
use App\Model\WaShift;
use App\Model\WaStockMove;
use App\Models\RouteAutoBreak;
use App\OrderLocationLog;
use App\Pesaflow;
use App\SalesmanShift;
use App\Services\MappingService;
use Barryvdh\DomPDF\PDF;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JWTAuth;
use App\Model\User;
use Illuminate\Support\Facades\Validator;
use App\Model\WaSalesOrders;
use App\Model\WaSalesOrderItems;
use Illuminate\Support\Facades\Storage;
use App\Model\Route;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaInventoryAssignedItems;
use App\Model\WaInventoryItem;
use App\Model\WaNumerSeriesCode;
use App\Model\WaRouteCustomer;
use App\Model\WaStockBreaking;
use App\Model\WaStockBreakingItem;
use App\Models\RoutePricing;
use App\Model\Restaurant;

class SalesOrdersController extends Controller
{
    private $user;
    private $uploadsfolder;

    public function __construct(User $user, protected SmsService $smsService)
    {
        $this->user = $user;
        $this->uploadsfolder = asset('uploads/');
    }

    public function salesOrders(Request $request)
    {
        $validator = Validator::make($request->all(), ['user_id' => 'required|exists:users,id']);
        if ($validator->fails()) {
            $error = $this->validationHandle($validator->messages());
            return response()->json(['status' => false, 'message' => $error]);
        }
        $user = User::where('id', $request->user_id)->first();
        $route = @Route::where('id', @$user->route)->first()->route_name;
        $allItems = WaSalesOrders::with(['items'])->where('user_id', $request->user_id)->orderBy('id', 'DESC')->limit(10)->get();
        return response()->json(['status' => true, 'message' => 'Sales orders', 'data' => $allItems, 'route' => $route]);
    }

    public function getSalesManOrders(Request $request)
    {
        $user = JWTAuth::toUser($request->token);
        // $route = Route::where('id', @$user->route)->first()->route_name;
        $shift = SalesmanShift::latest()->where('salesman_id', $user->id)->first();
        $route = Route::find($shift->route_id)->route_name;
        $allItems = WaInternalRequisition::withCount('getRelatedItem as number_of_items')->where('user_id', $user->id)->orderBy('id', 'DESC')->get();

        $allItems = $allItems->map(function (WaInternalRequisition $order) {
            return $this->getBasicOrderInformation($order);
        });

        return response()->json(['status' => true, 'message' => 'Sales orders', 'data' => $allItems, 'route' => $route]);
    }


    private function getBasicOrderInformation(WaInternalRequisition $order): WaInternalRequisition
    {

        $relatedItems = $order->getRelatedItem;
        $items_received = false;
        $is_delivered = false;
        $totalSum = 0;
        foreach ($relatedItems as $relatedItem) {
            $totalSum += $relatedItem->getCostWithTotalReturns();
            $relatedItem->total_cost = $this->formatMoney($relatedItem->getCostWithTotalReturns());

            $relatedItem->returned_quantity = $relatedItem->returnedQuantity();
            $relatedItem->returned_total = $relatedItem->returnedTotal();

            $relatedItem->standard_cost = $this->formatMoney($relatedItem->selling_price);
            $relatedItem->item_name = $relatedItem->getInventoryItemDetail?->title;
            $relatedItem->quantityValue = floatval($relatedItem->quantity - $relatedItem->returnedQuantity());
        }

        if ($order->items_received == 1) {
            $items_received = true;
        }

        if ($order->is_delivered == 1) {
            $is_delivered = true;
        }

        $order->totalOrderAmount = $this->formatMoney($order->getTotalWithAllReturns());
        $order->totalOrderAmountValue = floatval($order->getTotalWithAllReturns());
        $order->items_received = $items_received;
        $order->is_delivered = $is_delivered;
        // $order->delivery_date = date('Y-m-d');

        $shop = WaRouteCustomer::find($order->wa_route_customer_id);
        $shopPayload = [
            'id' => $shop->id,
            'photo' => null
        ];

        if ($shop->image_url) {
            $appUrl = env('APP_URL');
            $shopPayload['photo'] = "$appUrl/uploads/shops/" . $shop->image_url;
        }

        $order->shop = $shopPayload;
        return $order;
    }


    public function getOrdersByRoute(Request $request)
    {
        $validator = Validator::make($request->all(), ['route_id' => 'required|exists:routes,id']);
        if ($validator->fails()) {
            $error = $this->validationHandle($validator->messages());
            return response()->json(['status' => false, 'message' => $error]);
        }
        $user = JWTAuth::toUser($request->token);
        $route = @Route::where('id', @$user->route)->first()->route_name;
        $allItems = WaInternalRequisition::withCount('getRelatedItem as number_of_items')->where('route_id', $request->route_id)->orderBy('id', 'DESC')->get();


        $allItems = $allItems->map(function (WaInternalRequisition $order) {
            return $this->getBasicOrderInformation($order);
        });


        // foreach ($allItems as $item) {
        //     // Access the 'getRelatedItem' relationship for each item
        //     $relatedItems = $item->getRelatedItem;

        //     // Calculate the total sum for this item
        //     $totalSum = 0;
        //     $deliveryDate = '';
        //     foreach ($relatedItems as $relatedItem) {

        //         $totalSum += $relatedItem->selling_price * $relatedItem->quantity;
        //         $relatedItem->total_cost = $this->formatMoney($relatedItem->selling_price * $relatedItem->quantity);
        //         $relatedItem->standard_cost = $this->formatMoney($relatedItem->selling_price);
        //         $relatedItem->item_name = $relatedItem->getInventoryItemDetail->title;
        //         $relatedItem->quantityValue = floatval($relatedItem->quantity);


        //     }

        //     $item->totalOrderAmount = $this->formatMoney($totalSum);
        //     $item->totalOrderAmountValue = floatval($totalSum);

        //     $item->delivery_date = date('Y-m-d');

        //     // Add the total sum to each relatedItem object
        //     // foreach ($relatedItems as $relatedItem) {

        //     // }
        // }

        return response()->json(['status' => true, 'message' => 'Sales orders', 'data' => $allItems, 'route' => $route]);
    }


    public function getDeliveryManDeliveries(Request $request)
    {
        $user = JWTAuth::toUser($request->token);
        // $route = @Route::where('id', @$user->route)->first()->route_name;
        $deliveryShift = DeliverySchedule::latest()->where('driver_id', $user->id)->first();
        $route = Route::find($deliveryShift->route_id);

        $allItems = WaInternalRequisition::withCount('getRelatedItem as number_of_items')
            ->with('getRelatedItem.getInventoryItemDetail')
            ->where('route_id', $route->id)
            ->where('status', "COMPLETED")
            ->orderBy('id', 'DESC')->get();


        // foreach ($allItems as $item) {
        //     // Access the 'getRelatedItem' relationship for each item
        //     $relatedItems = $item->getRelatedItem;
        //     // Calculate the total sum for this item
        //     $totalSum = 0;
        //     foreach ($relatedItems as $relatedItem) {

        //         $totalSum += $relatedItem->selling_price * $relatedItem->quantity;
        //         $relatedItem->total_cost = $this->formatMoney($relatedItem->selling_price * $relatedItem->quantity);
        //         $relatedItem->standard_cost = $this->formatMoney($relatedItem->selling_price);
        //         $relatedItem->item_name = $relatedItem->getInventoryItemDetail->title;
        //         $relatedItem->quantityValue = floatval($relatedItem->quantity);

        //     }

        //     $item->totalOrderAmount = $this->formatMoney($totalSum);
        //     $item->totalOrderAmountValue = floatval($totalSum);
        //     $item->delivery_date = date('Y-m-d');
        // }

        $allItems = $allItems->map(function (WaInternalRequisition $order) {
            return $this->getBasicOrderInformation($order);
        });


        return response()->json(['status' => true, 'message' => 'Deliveries', 'data' => $allItems]);
    }


    public function getShopOrders(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'shop_id' => 'required'
            ]);

            if ($validator->fails()) {
                $error = $this->validationHandle($validator->messages());
                return response()->json(['status' => false, 'message' => $error], 422);
            }

            $user = JWTAuth::toUser($request->token);
            $shiftId = null;
            if ($user->role_id == 4) {
                $shift = SalesmanShift::latest()->where('salesman_id', $user->id)->first();
                if (!$shift) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Sales orders',
                        'totalOrdersAmount' => $this->formatMoney(0),
                        'totalOrdersAmountValue' => 0,
                        'data' => []
                    ]);
                }

                $shiftId = $shift->id;
            }

            if ($user->role_id == 6) {
                $delivery = DeliverySchedule::latest()->started()->forDriver($user->id)->first();
                if (!$delivery) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Sales orders',
                        'totalOrdersAmount' => $this->formatMoney(0),
                        'totalOrdersAmountValue' => 0,
                        'data' => [],
                    ]);
                }

                $shiftId = $delivery->shift_id;
            }

            $invoices = WaInternalRequisition::withCount('getRelatedItem as number_of_items')
                ->where('wa_route_customer_id', $request->shop_id)->where('wa_shift_id', $shiftId);

            $invoices = $invoices->orderBy('id', 'DESC')->get();
            $totalPendingOrdersAmount = 0;
            $returns = 0;
            $invoices = $invoices->map(function (WaInternalRequisition $order) {
                return $this->getBasicOrderInformation($order);
            });

            foreach ($invoices as $invoice) {
                $totalPendingOrdersAmount += $invoice->getTotalWithAllReturns();
                $returns += $invoice->getTotalReturns();
            }

            return response()->json([
                'status' => true,
                'message' => "Sales orders. Shift id $shiftId",
                'totalOrdersAmount' => $this->formatMoney($totalPendingOrdersAmount),
                'totalOrdersAmountValue' => $totalPendingOrdersAmount,
                'returns' => $returns,
                'data' => $invoices
            ]);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }


    public function getSalesOrderDetails(Request $request)
    {


        $validator = Validator::make($request->all(), ['order_id' => 'required']);
        if ($validator->fails()) {
            $error = $this->validationHandle($validator->messages());
            return response()->json(['status' => false, 'message' => $error]);
        }

        $user = JWTAuth::toUser($request->token);
        $route = @Route::where('id', @$user->route)->first()->route_name;
        $allItems = WaInternalRequisition::withCount('getRelatedItem as number_of_items')
            ->with([
                'getRelatedItem' => function ($query) {
                    // Select specific columns for the 'getRelatedItem' relationship
                    // $query->select('related_column1', 'related_column2');
                },
                'getRelatedItem.getInventoryItemDetail' => function ($query) {
                    // Select specific columns for the 'getInventoryItemDetail' relationship
                    // $query->select('inventory_column1', 'inventory_column2');
                }
            ])
            ->with('getRelatedItem.getInventoryItemDetail')
            ->with("getRouteCustomer")
            ->where('user_id', $user->id)
            ->where('id', $request->order_id)->first();


        $relatedItems = $allItems->getRelatedItem;

        // Calculate the total sum for this item
        $totalSum = 0;
        foreach ($relatedItems as $relatedItem) {
            $totalSum += $relatedItem->selling_price * $relatedItem->quantity;
            $relatedItem->total_cost = $this->formatMoney($relatedItem->selling_price * $relatedItem->quantity);
            $relatedItem->standard_cost = $this->formatMoney($relatedItem->selling_price);
            $relatedItem->item_name = $relatedItem->getInventoryItemDetail->title;
            $relatedItem->quantityValue = floatval($relatedItem->quantity);
        }

        $is_delivered = false;

        if ($allItems->is_delivered == 1) {
            $is_delivered = true;
        }

        $allItems->totalOrderAmount = $this->formatMoney($totalSum);
        $allItems->totalOrderAmountValue = floatval($totalSum);
        $allItems->is_delivered = $is_delivered;
        $allItems->delivery_date = date('Y-m-d');


        return response()->json(['status' => true, 'message' => 'Sales orders', 'data' => $allItems, 'route' => $route]);
    }


    public function getSalesOrderReceipt(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['order_id' => 'required']);

            if ($validator->fails()) {
                $error = $this->validationHandle($validator->messages());
                return response()->json(['status' => false, 'message' => $error], 422);
            }

            $order = WaInternalRequisition::find($request->order_id);
            $esdData = WaEsdDetails::where('invoice_number', $order->requisition_no)->where('status', 1)->first();

            // Bypass signing check for now
            // if (!$esdData) {
            //     return response()->json([
            //         'msg' => "This invoice is currently in the process of signing. Please try again shortly."
            //     ], 422);
            // }

            $restaurant = Restaurant::find($order->restaurant_id);

            $vatAmount = DB::table('wa_internal_requisition_items')->where('wa_internal_requisition_id', $request->order_id)->sum('vat_amount');
            $items = WaInternalRequisitionItem::where('wa_internal_requisition_id', $request->order_id)->get()->map(function (WaInternalRequisitionItem $item) {
                $item->quantity = $item->quantity;
                $item->title = DB::table('wa_inventory_items')->where('id', $item->wa_inventory_item_id)->first()->title;
                $item->total_cost = $item->selling_price * $item->quantity;

                return $item;
            });

            $data = ['items' => $items];
            $data['order_number'] = $order->requisition_no;
            $data['gross_total'] = $order->getOrderTotalForEsd();
            $data['order_total'] = $order->getOrderTotalForReceipt();
            $data['order_discount'] = $order->getTotalDiscount();
            $data['order_returns'] = $order->getTotalReturns();

            $salesman = User::find($order->user_id);
            $customer = WaRouteCustomer::find($order->wa_route_customer_id);
            $data['salesman'] = $salesman->name;
            $data['customer_name'] = $customer->bussiness_name;
            $data['customer_number'] = $customer->phone;
            $data['kra_pin'] = $customer->kra_pin ?? '';
            $data['route'] = $order->route;

            $data['total_vat'] = $vatAmount;

            $data['net_amount'] = $data['gross_total'] - $data['total_vat'];

            $customerAccount = WaCustomer::find($order->customer_id);
            $data['equity_account'] = $customerAccount->equity_till;
            $data['kcb_account'] = $customerAccount->kcb_till;
            $payment_code = substr($order->requisition_no, 4);

            $pdf = \PDF::loadView('receipt', compact('data', 'esdData', 'restaurant', 'order','payment_code'));
            // Don't set paper size - let it use natural size for thermal printer
            return $pdf->stream();
        } catch (\Throwable $e) {
            return response()->json(['msg' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }

    public function generateRandomAlphanumeric($length = 10)
    {
        $bytes = random_bytes($length);
        return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
    }


    public function recordSalesOrders(Request $request): JsonResponse
    {
        $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();
        $lastNumberUsed = $series_module->last_number_used;
        $newNumber = (int)$lastNumberUsed + 1;
        $series_module->update(['last_number_used' => $newNumber]);

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'request_type' => 'required|in:save,send_request',
                'route' => 'required|exists:routes,id',
                'route_customer_id' => 'required|exists:wa_route_customers,id',
                'item_id' => 'array',
                'item_quantity' => 'array',
                'item_id.*' => 'required|exists:wa_inventory_items,id',
                'item_quantity.*' => 'required|min:1|numeric',
            ], [
                'item_quantity.*.min' => 'Quantity must be greater than or equal to 1',
            ], [
                'item_id.*' => 'Item',
                'item_quantity.*' => 'Quantity',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'result' => 0], 422);
            }

            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return response()->json(['errors' => 'A user matching the provided token was not found.'], 422);
            }

            $route = Route::find($request->route);
            $routeCustomer = WaRouteCustomer::find($request->route_customer_id);
            $customer = WaCustomer::find($routeCustomer->customer_id);
            if ($customer->is_blocked == 1) {
                return response()->json(['result' => -1, 'message' => "Your account is blocked from making any orders. Please contact your supervisor."], 422);
            }
            $currentShift = SalesmanShift::where('status', 'open')->where('route_id', $route->id)->first();
            if (!$currentShift) {
                return response()->json(['result' => -1, 'message' => "Route $route->route_name does not have an open shift"], 422);
            }

            // Logic to block duplicate orders
            $existingCustomerOrder = WaInternalRequisition::latest()->with('getRelatedItem')->where('wa_route_customer_id', $routeCustomer->id)->where('wa_shift_id', $currentShift->id)
                ->first();
            if ($existingCustomerOrder) {
                $itemIds = $existingCustomerOrder->getRelatedItem()->whereNot('selling_price', 0)->pluck('wa_inventory_item_id')->toArray();
                $itemQuantities = $existingCustomerOrder->getRelatedItem()->whereNot('selling_price', 0)->pluck('quantity')->toArray();
                $incomingIds = $request->item_id;
                $incomingQuantities = $request->item_quantity;
                if (($itemIds == $incomingIds) && ($itemQuantities == $incomingQuantities)) {
                    return $this->jsonify(['message' => 'A similar order exists for this customer'], 422);
                }
            }

            $locationLog = null;
            if ($currentShift->shift_type == 'onsite') {
                $salesmanLat = $request->latitude;
                $salesmanLng = $request->longitude;

                $distance = MappingService::getTheaterDistanceBetweenTwoPoints($salesmanLat, $salesmanLng, $routeCustomer->lat, $routeCustomer->lng);
                $locationLog = OrderLocationLog::create([
                    'salesman_id' => $user->id,
                    'shop_id' => $routeCustomer->id,
                    'shift_id' => $currentShift->id,
                    'salesman_lat' => $salesmanLat,
                    'salesman_lng' => $salesmanLng,
                    'shop_lat' => $routeCustomer->lat,
                    'shop_lng' => $routeCustomer->lng,
                    'proximity' => $route->salesman_proximity,
                    'distance' => $distance,
                    'status' => 'passed',
                ]);

                if ($distance > $route->salesman_proximity) {
                    $locationLog->update(['status' => 'failed']);
                    DB::commit();
                    return response()->json(['result' => -1,
                        'message' => "You are outside the allowed order taking distance from the shop ($distance)m"], 422);
                }
            }


            $orderItems = [];
            foreach ($request->item_id as $itemId) {
                $item = WaInventoryItem::with(['getAllFromStockMoves', 'getTaxesOfItem'])->find($itemId);

                //logic to check flash route  pricing
                $routePrice = RoutePricing::resolvePrice($itemId, $route->id, $route->restaurant_id ?? null);
                if ($routePrice !== null) {
                    $item->selling_price = $routePrice;
                }

                $item->quantity = $item->getAllFromStockMoves()->where('wa_location_and_store_id', '=', $user->wa_location_and_store_id)->sum('qauntity');
                $orderItems[] = $item;
            }

            $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();

//            $invoiceNumber = getCodeWithNumberSeries('INTERNAL REQUISITIONS');
//            updateUniqueNumberSeries('INTERNAL REQUISITIONS', $invoiceNumber);
            $lastNumberUsed = $series_module->last_number_used;
            $newNumber = (int)$lastNumberUsed + 1;
            $series_module->update(['last_number_used' => $newNumber]);

            $invoice = WaInternalRequisition::create([
                'requisition_no' => "INV-$newNumber",
                'wa_shift_id' => $currentShift->id,
                'shift_type' => $currentShift->shift_type,
                'user_id' => $user->id,
                'to_store_id' => $user->wa_location_and_store_id,
                'requisition_date' => Carbon::now(),
                'name' => $routeCustomer->name,
                'route_id' => $route->id,
                'route' => $route->route_name,
                'customer_id' => $customer->id,
                'wa_route_customer_id' => $routeCustomer->id,
                'status' => 'APPROVED',
            ]);

            if ($locationLog) {
                $locationLog->update(['order_id' => $invoice->id]);
            }

            $smsItems = [];
            $receiptTotal = 0;

            foreach ($orderItems as $index => $orderItem) {
                $settings = getAllSettings();
                if ($settings['ALLOW_OUT_OF_STOCK_ORDERING'] == 0) {
                    $orderQty = $request->item_quantity[$index];
                    if ($orderItem->quantity < $orderQty) {
                        $motherItemRelation = WaInventoryAssignedItems::where('destination_item_id', $orderItem->id)->first();
                        if (!$motherItemRelation) {
                            DB::rollBack();
                            return response()->json(['result' => -1, 'message' => "Item $orderItem->title does not have enough quantity"], 422);
                        }

                        $motherItem = WaInventoryItem::with(['getAllFromStockMoves'])->find($motherItemRelation->wa_inventory_item_id);
                        $motherItemQuantity = $motherItem->getAllFromStockMoves()->where('wa_location_and_store_id', $user->wa_location_and_store_id)->sum('qauntity');
                        $availableSplitQty = $motherItemQuantity * $motherItemRelation->conversion_factor;
                        $requiredQty = $orderQty - $orderItem->quantity;
                        if ($availableSplitQty < $requiredQty) {
                            DB::rollBack();
                            return response()->json(['result' => -1, 'message' => "Item $orderItem->title does not have enough quantity"], 422);
                        }

                        $motherQuantityToBreak = ceil($requiredQty / $motherItemRelation->conversion_factor);
                        $destinationQty = $motherQuantityToBreak * $motherItemRelation->conversion_factor;
                        $stock_break_number = getCodeWithNumberSeries('STOCKBREAKING');
                        $head = new WaStockBreaking();
                        $head->user_id = $user->id;
                        $head->date = \Carbon\Carbon::now()->toDateString();
                        $head->time = \Carbon\Carbon::now()->toTimeString();
                        $head->breaking_code = $stock_break_number;
                        $head->status = 'PROCESSED';
                        $head->save();

                        $line = new WaStockBreakingItem();
                        $line->wa_stock_breaking_id = $head->id;
                        $line->source_item_id = $motherItem->id;
                        $line->source_item_bal_stock = $motherItemQuantity;
                        $line->source_qty = $motherQuantityToBreak;
                        $line->destination_item_id = $orderItem->id;
                        $line->conversion_factor = $motherItemRelation->conversion_factor;
                        $line->destination_qty = $destinationQty;
                        $line->save();

                        //perform stock moves for mother and child
                        $parentStockMove = new WaStockMove();
                        $parentStockMove->user_id = $user->id;
                        $parentStockMove->restaurant_id = $user->restaurant_id;
                        $parentStockMove->wa_location_and_store_id = $user->wa_location_and_store_id;
                        $parentStockMove->wa_inventory_item_id = $motherItem->id;
                        $parentStockMove->standard_cost = $motherItem->standard_cost;
                        $parentStockMove->qauntity = $motherQuantityToBreak * -1;
                        $parentStockMove->new_qoh = $motherItemQuantity - $motherQuantityToBreak;
                        $parentStockMove->stock_id_code = $motherItem->stock_id_code;
                        $parentStockMove->price = $motherItem->selling_price * $motherQuantityToBreak;
                        $parentStockMove->document_no = $stock_break_number;
                        $parentStockMove->refrence = "$route->route_name/$stock_break_number/$invoice->requisition_no";
                        $parentStockMove->total_cost = $motherItem->selling_price * $motherQuantityToBreak;
                        $parentStockMove->selling_price = $motherItem->selling_price;
                        $parentStockMove->route_id = $route->id;
                        $parentStockMove->save();

                        $childStockMove = new WaStockMove();
                        $childStockMove->user_id = $user->id;
                        $childStockMove->restaurant_id = $user->restaurant_id;
                        $childStockMove->wa_location_and_store_id = $user->wa_location_and_store_id;
                        $childStockMove->wa_inventory_item_id = $orderItem->id;
                        $childStockMove->standard_cost = $orderItem->standard_cost;
                        $childStockMove->qauntity = $destinationQty;
                        $childStockMove->new_qoh = $orderItem->quantity + $destinationQty;
                        $childStockMove->stock_id_code = $orderItem->stock_id_code;
                        $childStockMove->price = $orderItem->selling_price * $destinationQty;
                        $childStockMove->total_cost = $orderItem->selling_price * $destinationQty;
                        $childStockMove->selling_price = $orderItem->selling_price;
                        $childStockMove->document_no = $stock_break_number;
                        $childStockMove->refrence = "$route->route_name/$stock_break_number/$invoice->requisition_no";
                        $childStockMove->save();

                        updateUniqueNumberSeries('STOCKBREAKING', $stock_break_number);

                        // Populate auto break list
                        $childBinId = WaInventoryLocationUom::where('inventory_id', $orderItem->id)->where('location_id', $user->wa_location_and_store_id)->first()?->uom_id;
                        $childPackSize = PackSize::find($orderItem->pack_size_id)->title;
                        $motherBinId = WaInventoryLocationUom::where('inventory_id', $motherItem->id)->where('location_id', $user->wa_location_and_store_id)->first()?->uom_id;
                        $motherPackSize = PackSize::find($motherItem->pack_size_id)->title;

                        RouteAutoBreak::create([
                            'stb_number' => $stock_break_number,
                            'child_item_id' => $orderItem->id,
                            'child_bin_id' => $childBinId,
                            'child_quantity' => $destinationQty,
                            'child_pack_size' => $childPackSize,
                            'mother_item_id' => $motherItem->id,
                            'mother_bin_id' => $motherBinId,
                            'mother_quantity' => $motherQuantityToBreak,
                            'mother_pack_size' => $motherPackSize,
                            'route_id' => $route->id,
                            'shift_id' => $currentShift->id,
                            'salesman_id' => $user->id,
                            'invoice_id' => $invoice->id,
                        ]);
                    }
                }

                $itemMargin = $orderItem->selling_price - $orderItem->standard_cost;
                if ($itemMargin < 1) {
                    return response()->json(['result' => -1, 'message' => "$orderItem->title is disabled due to negative margin."], 422);
                }

                // Cap Imported sugar @30 bags
                if ($orderItem->stock_id_code == 'HYD03B') {
                    if (($request->item_quantity[$index]) > 30) {
                        DB::rollBack();
                        return response()->json(['result' => -1, 'message' => "You cannot order more than 30 bags of $orderItem->title"], 422);
                    }
                }

                if ($orderItem->block_this == 1) {
                    DB::rollBack();
                    return response()->json(['result' => -1, 'message' => "Item $orderItem->title has been blocked from sale due to a change in standard cost"], 422);
                }

                $vat_rate = 0;
                $vat_amount = 0;

                // Handle multiple price categories
                $sellingPriceToUse = $orderItem->selling_price;
                if ($request->price_categories[$index] != null) {
                    $priceCategory = WaCategoryItemPrice::find($request->price_categories[$index]);
                    if ($priceCategory) {
                        $sellingPriceToUse = $priceCategory->price;
                    }
                }


                $itemTotal = $sellingPriceToUse * ($request->item_quantity[$index]);
                if ($orderItem->tax_manager_id && $orderItem->getTaxesOfItem) {
                    $vat_rate = $orderItem->getTaxesOfItem->tax_value;
                    $vat_amount = $itemTotal - (($itemTotal * 100) / ($vat_rate + 100));
                }

                $discount = 0;
                $discountDescription = null;
                $discountBand = DB::table('discount_bands')->where('inventory_item_id', $orderItem->id)
                    ->where('from_quantity', '<=', $request->item_quantity[$index])
                    ->where('to_quantity', '>=', $request->item_quantity[$index])
                    ->first();
                if ($discountBand) {
                    $discount = $discountBand->discount_amount * $request->item_quantity[$index];
                    $discountDescription = "$discountBand->discount_amount discount for quantity between $discountBand->from_quantity and $discountBand->to_quantity";
                }

                $itemTotal = $itemTotal - $discount;

                $orderQty = (float)$request->item_quantity[$index];
                WaInternalRequisitionItem::create([
                    'wa_internal_requisition_id' => $invoice->id,
                    'wa_inventory_item_id' => $orderItem->id,
                    'quantity' => $request->item_quantity[$index],
                    'standard_cost' => $orderItem->standard_cost,
                    'selling_price' => $sellingPriceToUse,
                    'total_cost' => $itemTotal,
                    'tax_manager_id' => $orderItem->tax_manager_id,
                    'vat_rate' => $vat_rate,
                    'vat_amount' => $vat_amount,
                    'total_cost_with_vat' => ($itemTotal),
                    'created_at' => Carbon::now(),
                    'store_location_id' => $invoice->to_store_id,
                    'updated_at' => Carbon::now(),
                    'hs_code' => $orderItem->hs_code,
                    'discount' => $discount,
                    'discount_description' => $discountDescription,
                ]);

                $receiptTotal += $itemTotal;
                $inventoryItem = WaInventoryItem::find($orderItem->id);
                $payload = [
                    'title' => $inventoryItem->title,
                    'Qty' => $request->item_quantity[$index],
                    'total_cost_with_vat' => $itemTotal,
                ];
                $smsItems [] = $payload;

                // Check promotion
                $promotion = ItemPromotion::where('inventory_item_id', $orderItem->id)->where('status', 'active')->first();
                if ($promotion) {
                    $promotionBatches = floor($orderQty / (float)$promotion->sale_quantity);
                    if ($promotionBatches > 0) {
                        $promotionQty = $promotionBatches * $promotion->promotion_quantity;
                        $promotionItem = WaInventoryItem::find($promotion->promotion_item_id);
                        $promotionItemQoh = WaStockMove::where('wa_inventory_item_id', $promotionItem->id)->where('wa_location_and_store_id', $invoice->to_store_id)->sum('qauntity');
                        if ($promotionItemQoh < $promotionQty) {


                            //auto break stock on promotion
                            $motherItemRelation = WaInventoryAssignedItems::where('destination_item_id', $promotionItem->id)->first();
                            if (!$motherItemRelation) {
                                DB::rollBack();
                                return response()->json(['result' => -1, 'message' => "Promotion item $promotionItem->title attached to $inventoryItem->title does not have enough quantity"], 422);
                            }

                            $motherItem = WaInventoryItem::with(['getAllFromStockMoves'])->find($motherItemRelation->wa_inventory_item_id);
                            $motherItemQuantity = $motherItem->getAllFromStockMoves()->where('wa_location_and_store_id', $user->wa_location_and_store_id)->sum('qauntity');
                            $availableSplitQty = $motherItemQuantity * $motherItemRelation->conversion_factor;
                            $requiredQty = $promotionQty - $promotionItemQoh;
                            if ($availableSplitQty < $requiredQty) {
                                DB::rollBack();
                                return response()->json(['result' => -1, 'message' => "Promotion Item $promotionItem->title does not have enough quantity"], 422);
                            }

                            $motherQuantityToBreak = ceil($requiredQty / $motherItemRelation->conversion_factor);
                            $destinationQty = $motherQuantityToBreak * $motherItemRelation->conversion_factor;
                            $stock_break_number = getCodeWithNumberSeries('STOCKBREAKING');
                            $head = new WaStockBreaking();
                            $head->user_id = $user->id;
                            $head->date = \Carbon\Carbon::now()->toDateString();
                            $head->time = \Carbon\Carbon::now()->toTimeString();
                            $head->breaking_code = $stock_break_number;
                            $head->status = 'PROCESSED';
                            $head->save();

                            $line = new WaStockBreakingItem();
                            $line->wa_stock_breaking_id = $head->id;
                            $line->source_item_id = $motherItem->id;
                            $line->source_item_bal_stock = $motherItemQuantity;
                            $line->source_qty = $motherQuantityToBreak;
                            $line->destination_item_id = $promotionItem->id;
                            $line->conversion_factor = $motherItemRelation->conversion_factor;
                            $line->destination_qty = $destinationQty;
                            $line->save();

                            //perform stock moves for mother and child
                            $parentStockMove = new WaStockMove();
                            $parentStockMove->user_id = $user->id;
                            $parentStockMove->restaurant_id = $user->restaurant_id;
                            $parentStockMove->wa_location_and_store_id = $user->wa_location_and_store_id;
                            $parentStockMove->wa_inventory_item_id = $motherItem->id;
                            $parentStockMove->standard_cost = $motherItem->standard_cost;
                            $parentStockMove->qauntity = $motherQuantityToBreak * -1;
                            $parentStockMove->new_qoh = $motherItemQuantity - $motherQuantityToBreak;
                            $parentStockMove->stock_id_code = $motherItem->stock_id_code;
                            $parentStockMove->price = $motherItem->selling_price * $motherQuantityToBreak;
                            $parentStockMove->document_no = $stock_break_number;
                            $parentStockMove->refrence = "$route->route_name/$stock_break_number/$invoice->requisition_no";
                            $parentStockMove->total_cost = $motherItem->selling_price * $motherQuantityToBreak;
                            $parentStockMove->selling_price = $motherItem->selling_price;
                            $parentStockMove->route_id = $route->id;
                            $parentStockMove->save();

                            $childStockMove = new WaStockMove();
                            $childStockMove->user_id = $user->id;
                            $childStockMove->restaurant_id = $user->restaurant_id;
                            $childStockMove->wa_location_and_store_id = $user->wa_location_and_store_id;
                            $childStockMove->wa_inventory_item_id = $promotionItem->id;
                            $childStockMove->standard_cost = $promotionItem->standard_cost;
                            $childStockMove->qauntity = $destinationQty;
                            $childStockMove->new_qoh = $promotionItemQoh + $destinationQty;
                            $childStockMove->stock_id_code = $promotionItem->stock_id_code;
                            $childStockMove->price = $promotionItem->selling_price * $destinationQty;
                            $childStockMove->total_cost = $promotionItem->selling_price * $destinationQty;
                            $childStockMove->selling_price = $promotionItem->selling_price;
                            $childStockMove->document_no = $stock_break_number;
                            $childStockMove->refrence = "$route->route_name/$stock_break_number/$invoice->requisition_no";
                            $childStockMove->save();

                            updateUniqueNumberSeries('STOCKBREAKING', $stock_break_number);

                            // Populate auto break list
                            $childBinId = WaInventoryLocationUom::where('inventory_id', $promotionItem->id)->where('location_id', $user->wa_location_and_store_id)->first()?->uom_id;
                            $childPackSize = PackSize::find($promotionItem->pack_size_id)->title;
                            $motherBinId = WaInventoryLocationUom::where('inventory_id', $motherItem->id)->where('location_id', $user->wa_location_and_store_id)->first()?->uom_id;
                            $motherPackSize = PackSize::find($motherItem->pack_size_id)->title;

                            RouteAutoBreak::create([
                                'stb_number' => $stock_break_number,
                                'child_item_id' => $promotionItem->id,
                                'child_bin_id' => $childBinId,
                                'child_quantity' => $destinationQty,
                                'child_pack_size' => $childPackSize,
                                'mother_item_id' => $motherItem->id,
                                'mother_bin_id' => $motherBinId,
                                'mother_quantity' => $motherQuantityToBreak,
                                'mother_pack_size' => $motherPackSize,
                                'route_id' => $route->id,
                                'shift_id' => $currentShift->id,
                                'salesman_id' => $user->id,
                                'invoice_id' => $invoice->id,
                            ]);


                        }
                        WaInternalRequisitionItem::create([
                            'wa_internal_requisition_id' => $invoice->id,
                            'wa_inventory_item_id' => $promotionItem->id,
                            'quantity' => $promotionQty,
                            'standard_cost' => $promotionItem->standard_cost,
                            'selling_price' => 0,
                            'total_cost' => 0,
                            'tax_manager_id' => $orderItem->tax_manager_id,
                            'vat_rate' => $vat_rate,
                            'vat_amount' => $vat_amount,
                            'total_cost_with_vat' => (0),
                            'created_at' => Carbon::now(),
                            'store_location_id' => $invoice->to_store_id,
                            'updated_at' => Carbon::now(),
                            'hs_code' => $promotionItem->hs_code,
                        ]);

                        $payload = [
                            'title' => $promotionItem->title,
                            'Qty' => $promotionQty,
                            'total_cost_with_vat' => 0,
                        ];
                        $smsItems [] = $payload;
                    }
                }
            }

            // Mark shop as visited
            $routeCustomerRecord = $currentShift->shiftCustomers()->where('route_customer_id', $routeCustomer->id)->first();
            if ($routeCustomerRecord) {
                $routeCustomerRecord->update(['visited' => 1, 'order_taken' => true, 'salesman_shift_type' => $currentShift->shift_type,]);
            }

            $notificationTitle = "New sales order";
            $notificationMessage = "Sales Order No: {$invoice->requisition_no} created for customer: {$routeCustomer->name}";
            Notification::sendNotification($user->id, $notificationTitle, $invoice->id, $notificationMessage);

            $totalSum = $invoice->getOrderTotal();

            $msgHeader = "Dear $routeCustomer->name , a Doc No: $invoice->requisition_no has been generated in your A/C, the value is:\n Ksh" . manageAmountFormat($receiptTotal) . "\n";
            foreach ($smsItems as $index => $orderItem) {
                $msgHeader .= ($index + 1) . ". " . $orderItem['title'] . " Qty: " . $orderItem['Qty'] . " Amount: " . manageAmountFormat($orderItem['total_cost_with_vat']) . "\n";
            }
            $customerMessageWithItems = $msgHeader .= "DO NOT PAY CASH, PAY USING: \n VOOMA: $customer->kcb_till \n EAZZYPAY: $customer->equity_till \nCOMPANY WILL NOT BE RESPONSIBLE FOR ANY CASH GIVEN  TO A SALESMAN \n Cell = 0723030848/0726765432";


            $customerMessage = "Hello $routeCustomer->name, we have received your order. Thank you for doing business with KHEL. We are looking forward to more business this year.";
            try {
                // $this->smsService->sendMessage($customerMessage, [$routeCustomer->phone]);
                $this->smsService->sendMessage($customerMessageWithItems, $routeCustomer->phone);

            } catch (\Throwable $e) {
                // pass
            }

            // Wa location transfer
            $this->recordInventoryLocationTransfer($invoice, $user, $currentShift, $route, $routeCustomer, $customer);

            // Stock Moves
            $this->recordStockMoves($invoice, $invoice->getRelatedItem, $user, $currentShift->id, $route);

            // Debtor Trans
            $this->recordDebtorTrans($invoice);

            // Sign
            $this->signInvoice($invoice);

            // TODO: GL Transactions

            DB::commit();

            $invoice->totalOrderAmountValue = $invoice->getOrderTotal();
            $invoice->totalOrderAmount = format_amount_with_currency($invoice->totalOrderAmountValue);

            foreach ($invoice->getRelatedItem as $item) {
                $item->item_name = $item->getInventoryItemDetail->title;
            }

            return response()->json(['result' => 1, 'message' => 'Order created successfully', "order" => $invoice]);
        } catch (\Throwable $e) {
            DB::rollBack();
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg, 'data' => $e->getTrace()], 500);
        }
    }

    private function signInvoice(WaInternalRequisition $invoice): void
    {
        try {
            $settings = getAllSettings();
            $clientPin = $settings['PIN_NO'];
            $esdUrl = $settings['ESD_URL'];
            $apiUrl = "$esdUrl/api/sign?invoice+1";
            $vatAmount = DB::table('wa_internal_requisition_items')->where('wa_internal_requisition_id', $invoice->id)->sum('vat_amount');
            $payload = [
                "invoice_date" => Carbon::parse($invoice->created_at)->format('d_m_Y'),
                "invoice_number" => $invoice->requisition_no,
                "invoice_pin" => $clientPin,
                "customer_pin" => "",
                "customer_exid" => "",
                "grand_total" => number_format($invoice->getOrderTotalForEsd(), 2),
                "net_subtotal" => number_format($invoice->getOrderTotalForEsd() - $vatAmount, 2),
                "tax_total" => number_format($vatAmount, 2),
                "net_discount_total" => "0",
                "sel_currency" => "KSH",
                "rel_doc_number" => "",
                "items_list" => []
            ];

            foreach ($invoice->getRelatedItem as $item) {
                $inventoryItem = DB::table('wa_inventory_items')->find($item->wa_inventory_item_id);
                $itemTotal = $item->total_cost_with_vat + $item->discount;
                $itemTotal = manageAmountFormat($itemTotal);
                $item->selling_price = manageAmountFormat($item->selling_price);
                $line = "$inventoryItem->title $item->quantity $item->selling_price $itemTotal";
                if ($inventoryItem->hs_code) {
                    $line = "$inventoryItem->hs_code " . $line;
                }
                $payload['items_list'][] = $line;
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ZxZoaZMUQbUJDljA7kTExQ==',
            ])->post($apiUrl, $payload);

            $responseData = json_decode($response->body(), true);
            $newEsd = new WaEsdDetails();

            if ($response->ok()) {
                $newEsd->invoice_number = $responseData['invoice_number'];
                $newEsd->cu_serial_number = $responseData['cu_serial_number'];
                $newEsd->cu_invoice_number = $responseData['cu_invoice_number'];
                $newEsd->verify_url = $responseData['verify_url'] ?? null;
                $newEsd->description = $responseData['description'] ?? null;
                $newEsd->status = 1;
                $newEsd->save();
            } else {
                $newEsd->invoice_number = $invoice->requisition_no;
                $newEsd->description = $response->body();
                $newEsd->status = 0;
                $newEsd->save();
            }
        } catch (\Throwable $e) {
            $newEsd = new WaEsdDetails();
            $newEsd->invoice_number = $invoice->requisition_no;
            $newEsd->description = $e->getMessage();
            $newEsd->status = 0;
            $newEsd->save();
        }
    }

    private function recordInventoryLocationTransfer($invoice, $user, $shift, $route, $routeCustomer, $customer)
    {
        $transfer = new WaInventoryLocationTransfer();
//        $transfer->transfer_no = getCodeWithNumberSeries('TRAN');
        $transfer->transfer_no = $invoice->requisition_no;
        $transfer->transfer_date = $invoice->requisition_date;
        $transfer->restaurant_id = $user->restaurant_id;
        $transfer->wa_department_id = $user->wa_department_id;
        $transfer->user_id = $user->id;
        $transfer->to_store_location_id = $invoice->to_store_id;
        $transfer->route = $route->route_name;
        $transfer->route_id = $route->id;
        $transfer->customer = $invoice->customer;
        $transfer->customer_id = $customer->id;
        $transfer->status = $invoice->status;
        $transfer->shift_id = $shift->id;
        $transfer->name = $routeCustomer->name;
        $transfer->customer_pin = $routeCustomer->kra_pin;
        $transfer->customer_phone_number = $routeCustomer->phone;
        $transfer->save();

        foreach ($invoice->getRelatedItem as $invoiceItem) {
            $item = new WaInventoryLocationTransferItem();
            $item->wa_inventory_location_transfer_id = $transfer->id;
            $item->wa_inventory_item_id = $invoiceItem->wa_inventory_item_id;
            $item->quantity = $invoiceItem->quantity;
            $item->wa_internal_requisition_item_id = $invoiceItem->id;
            $item->issued_quantity = $invoiceItem->quantity;
            $item->note = "";
            $item->standard_cost = $invoiceItem->standard_cost;
            $item->total_cost = $invoiceItem->total_cost;
            $item->vat_rate = $invoiceItem->vat_rate;
            $item->vat_amount = $invoiceItem->vat_amount;
            $item->total_cost_with_vat = $invoiceItem->total_cost_with_vat;
            $item->selling_price = $invoiceItem->selling_price;
            $item->discount_amount = $invoiceItem->discount;
            $item->store_location_id = $invoice->to_store_id;
            $item->save();
        }

        return $transfer;

//        updateUniqueNumberSeries('TRAN', $transfer->transfer_no);
    }

    private function recordStockMoves($invoice, $invoiceItems, $user, $shiftId, $route)
    {
        $routeCustomer = WaRouteCustomer::with('route')->find($invoice->wa_route_customer_id);
        foreach ($invoiceItems as $invoiceItem) {
            $inventoryItem = $invoiceItem->getInventoryItemDetail;

            $stockMove = new WaStockMove();
            $stockMove->user_id = $user->id;
            $stockMove->wa_internal_requisition_id = $invoice->id;
            $stockMove->restaurant_id = $user->restaurant_id;
            $stockMove->wa_location_and_store_id = $user->wa_location_and_store_id;
            $stockMove->wa_inventory_item_id = $inventoryItem->id;
            $stockMove->standard_cost = $inventoryItem->standard_cost;
            $stockMove->qauntity = $invoiceItem->quantity * -1;
            $stockMove->new_qoh = ($inventoryItem->getAllFromStockMoves->where('wa_location_and_store_id', $user->wa_location_and_store_id)->sum('qauntity') ?? 0) - $invoiceItem->quantity;
            $stockMove->stock_id_code = $inventoryItem->stock_id_code;
            $stockMove->document_no = $invoice->requisition_no;
            $stockMove->shift_id = $shiftId;
            $stockMove->refrence = "{$routeCustomer->route->route_name} - $routeCustomer->bussiness_name";
            $stockMove->price = $invoiceItem->total_cost;
            $stockMove->total_cost = $invoiceItem->total_cost;
            $stockMove->selling_price = $invoiceItem->selling_price;
            $stockMove->route_id = $route->id;

            $stockMove->save();
        }
    }

    private function recordDebtorTrans($invoice)
    {
        $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();
        $routeCustomer = WaRouteCustomer::with('route')->find($invoice->wa_route_customer_id);

        $debtorTran = new WaDebtorTran();
        $debtorTran->wa_sales_invoice_id = $invoice->id;
        $debtorTran->type_number = $series_module ? $series_module->type_number : '';
        $debtorTran->wa_customer_id = $invoice->customer_id;
        $debtorTran->salesman_id = $invoice->to_store_id;
        $debtorTran->customer_number = WaCustomer::find($invoice->customer_id)->customer_code;
        $debtorTran->trans_date = $invoice->requisition_date;
        $debtorTran->wa_accounting_period_id = $accountingPeriod ? $accountingPeriod->id : null;
        $debtorTran->amount = $invoice->getOrderTotal();
        $debtorTran->document_no = $invoice->requisition_no;
        $debtorTran->reference = "{$routeCustomer->route->route_name} - $routeCustomer->bussiness_name";
        $debtorTran->invoice_customer_name = "$routeCustomer->bussiness_name";
        $debtorTran->save();

        if ($invoice->getTotalDiscount() > 0) {
            $discountTran = new WaDebtorTran();
            $discountTran->wa_sales_invoice_id = $invoice->id;
            $discountTran->type_number = $series_module ? $series_module->type_number : '';
            $discountTran->wa_customer_id = $invoice->customer_id;
            $discountTran->salesman_id = $invoice->to_store_id;
            $discountTran->customer_number = WaCustomer::find($invoice->customer_id)->customer_code;
            $discountTran->trans_date = $invoice->requisition_date;
            $discountTran->wa_accounting_period_id = $accountingPeriod ? $accountingPeriod->id : null;
            $discountTran->amount = ($invoice->getTotalDiscount()) * -1;
            $discountTran->document_no = $invoice->requisition_no;
            $discountTran->reference = "{$routeCustomer->route->route_name} - $routeCustomer->bussiness_name Discount Allowed";
            $discountTran->invoice_customer_name = "$routeCustomer->bussiness_name";
            $discountTran->save();
        }
    }

    public function getOrderById(Request $request): JsonResponse
    {
        try {
            $user = JWTAuth::toUser($request->token);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'A user matching the provided token was not found.', 'data' => []], 422);
            }

            $order = WaInternalRequisition::find($request->order_id);
            if (!$order) {
                return response()->json(['status' => false, 'message' => 'An order  matching the provided Id was not found'], 404);
            }
            $relatedItems = $order->getRelatedItem;
            $items_received = false;
            $is_delivered = false;
            $totalSum = 0;
            foreach ($relatedItems as $relatedItem) {
                $totalSum += $relatedItem->getRealCost();
                $relatedItem->total_cost = $this->formatMoney($relatedItem->getRealCost());

                $relatedItem->returned_quantity = $relatedItem->returnedQuantity();
                $relatedItem->returned_total = $relatedItem->returnedTotal();

                $relatedItem->standard_cost = $this->formatMoney($relatedItem->selling_price);
                $relatedItem->item_name = $relatedItem->getInventoryItemDetail?->title;
                $relatedItem->quantityValue = floatval($relatedItem->quantity) - $relatedItem->returnedQuantity();
            }

            if ($order->items_received == 1) {
                $items_received = true;
            }

            if ($order->is_delivered == 1) {
                $is_delivered = true;
            }

            $order->totalOrderAmount = $this->formatMoney($order->getRealCost());
            $order->totalOrderAmountValue = floatval($order->getRealCost());
            $order->items_received = $items_received;
            $order->is_delivered = $is_delivered;
            // $order->delivery_date = date('Y-m-d');

            $shop = WaRouteCustomer::find($order->wa_route_customer_id);
            $shopPayload = [
                'id' => $shop->id,
                'photo' => null
            ];

            if ($shop->image_url) {
                $appUrl = env('APP_URL');
                $shopPayload['photo'] = "$appUrl/uploads/shops/" . $shop->image_url;
            }
            $order->shop = $shopPayload;
            return response()->json(['status' => true, 'order' => $order,]);
        } catch (\Throwable $e) {
            return $this->jsonify(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
