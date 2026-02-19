<?php

namespace App\Http\Controllers\Api;

use Ably\Log;
use App\Enums\PromotionMatrix;
use App\Events\PaymentReceived;
use App\Http\Controllers\Controller;
use App\Interfaces\SmsService;
use App\InvoicePayment;
use App\ItemPromotion;
use App\Model\PaymentMethod;
use App\Model\TaxManager;
use App\Model\WaChartsOfAccount;
use App\Model\WaEsdDetails;
use App\Model\WaGlTran;
use App\Model\WaInventoryItem;
use App\Model\WaNumerSeriesCode;
use App\Model\WaPosCashSales;
use App\Model\WaPosCashSalesDispatch;
use App\Model\WaPosCashSalesItems;
use App\Model\WaPosCashSalesPayments;
use App\Model\WaRouteCustomer;
use App\Model\WaStockMove;
use App\Models\HamperItem;
use App\Models\MpesaOperation;
use App\Models\PromotionType;
use App\Models\WaAccountTransaction;
use App\Services\MpesaService;
use App\Services\PosCashSaleService;
use App\Services\PosPaymentService;
use App\User;
use App\WaTenderEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Validator;
use Mockery\Matcher\Not;
use App\DiscountBand;


class CashSalesController extends Controller
{
    public function __construct(protected SmsService $smsService) {}

    public function index(Request $request)
    {
        $user = JWTAuth::toUser($request->token);
        $location = $user->wa_location_and_store_id;
        $search = $request->search ?? '';
        $startDate = now()->startOfDay();
        $endDate = now();
        $posCashSales = WaPosCashSales::withCount('items')
            ->withSum('items', 'total')
            ->where(function ($query) use ($search) {
                $query->where('customer', 'like', '%' . $search . '%')
                    ->orWhere('sales_no', 'like', '%' . $search . '%')
                    ->orWhere('customer_phone_number', 'like', '%' . $search . '%');
            })
            ->whereHas('items', function ($query) use ($search, $location) {
                $query->whereHas('item', function ($subQuery) use ($search, $location) {
                    $subQuery->orWhere('title', 'like', '%' . $search . '%');
                });
            })
            ->with([
                'items' => function ($query) {
                    $query->with('item');
                },
                'payment'
            ])
            ->where('user_id', $user->id)
            ->where('wa_pos_cash_sales.status', '!=', 'Archived')
            ->whereBetween('created_at', [$startDate, $endDate])
            //            ->where('wa_pos_cash_sales.status', ($request->status ?? 'PENDING'))
            ->latest()
            ->cursorPaginate(15);


        $appUrl = env('APP_URL');
        $posCashSales->each(function ($sale) use ($appUrl, $location) {
            $sale->items->each(function ($item) use ($appUrl, $location) {
                if ($item->item && $item->item->image) {
                    $item->photo = "$appUrl/uploads/inventory_items/" . $item->item->image;
                } else {
                    $item->photo = null;
                }
                $item->name = $item->item->title;
                $item->display_qty = $item->item->getstockmoves()->where('wa_location_and_store_id', $location)->sum('qauntity');
                unset($item->item);
            });
        });
        return response()->json($posCashSales, 200);
    }

    public function store(Request $request)
    {
        $user = JWTAuth::toUser($request->token);

        $validation = \Validator::make($request->all(), [
            'paid' => 'required|boolean',
            'payment_methods' => function ($attribute, $value, $fail) use ($request) {
                if ($request->input('paid') === true && empty($value)) {
                    $fail('Payment methods are required when paid is true.');
                }
            },
            'payment_methods.*.method_id' => 'required_if:paid,true|exists:payment_methods,id',
            'payment_methods.*.amount' => 'required_if:paid,true',
            //            'payment_methods.*.tender_entry_id' => 'required_if:paid,true',
            'items' => 'array|required',
            'items.*.item_id' => 'required|exists:wa_inventory_items,id',
            'items.*.item_quantity' => 'required|numeric',
            'time' => 'required',
            'customer_id' => 'required|exists:wa_route_customers,id',
        ]);

        $paid_amount = collect($request->payment_methods)->sum('amount');
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ], 403);
        }


        $itemIds = [];
        foreach ($request->items as $item) {
            $itemIds[] = $item['item_id'];
        }

        $products = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
        ])
            ->whereIn('id', $itemIds)
            ->get();

        foreach ($request->items as $item) {
            $name =  @$products->firstWhere('id', $item['item_id'])->title;


            $qoh = WaStockMove::where('stock_id_code', $products->firstWhere('id', $item['item_id'])
                ->stock_id_code)->where('wa_location_and_store_id', $user->wa_location_and_store_id)
                ->sum('qauntity');
            if ($qoh < $item['item_quantity']) {

                return response()->json([
                    'status' => false,
                    'message' => ['item_id.' . $item['item_id'] => [$name .'Quantity cannot be greater than balance stock']]
                ]);
            }
            $itemBin = @$products->firstWhere('id', $item['item_id'])->getBinData(getLoggeduserProfile()->wa_location_and_store_id)->id;
            if ($itemBin == null) {
                // $name =  @$products->firstWhere('id', $item['item_id'])->title;
                return response()->json([
                    'result' => 0,
                    'errors' => ['item_id.' . $item['item_id'] => [$name . ' is not assigned to a bin']]
                ], 419);
            }
        }

        foreach ($request->items as $item) {
            $value =  $products->firstWhere('id', $item['item_id']);
            $number = $item['item_quantity'];


            if (is_numeric($number) && floor($number) != $number) {

                if ($value->item_count != null) {
                    if (!checkSplit($number)) {
                        return response()->json([
                            'result' => 0,
                            'errors' => ['item_id.' . $value->id => ['The product cannot be broken into provided quantity']]
                        ]);
                    }
                } else {
                    return response()->json([
                        'result' => 0,
                        'errors' => ['item_id.' . $value->id => ['The product cannot be broken into half']]
                    ]);
                }
            }
        }

        try {
            $new_sales_no = self::generateNewSalesNumber();
            $check = DB::transaction(function () use ($products, $request, $user, $paid_amount, $new_sales_no) {
                $items = $request->items;
                $payment_methods = $request->payment_methods;
                $route_customer =  $request->customer_id;
                $paid  = $request->paid;

                return PosCashSaleService::recordSale($items, $user->id, $products,$new_sales_no, $route_customer, $payment_methods, $paid, $user->id, null, true);
            });

            if ($check) {

                $message = 'Order Placed successfully.';
                return response()->json(['status' => true, 'message' => $message, 'order' => $check]);
            }
            return response()->json(['status' => false, 'message' => 'Something went wrong']);
        } catch (\Exception $exception) {
            return response()->json(['status' => -1, 'message' => $exception->getMessage()], 400);
        }
    }
    private static function generateNewSalesNumber(): string
    {
        $maxAttempts = 10;
        $attempt = 1;

        while ($attempt <= $maxAttempts) {
            DB::beginTransaction();
            try {
                $series_module = WaNumerSeriesCode::where('module', 'CASH_SALES')
                    ->lockForUpdate()
                    ->first();

                if (!$series_module) {
                    throw new \RuntimeException('CASH_SALES number series not found');
                }

                $lastNumberUsed = $series_module->last_number_used;
                $newNumber = (int)$lastNumberUsed + 1;
                $sales_no = $series_module->code . '-' . str_pad($newNumber, 5, "0", STR_PAD_LEFT);

                // Check for uniqueness in BOTH tables to prevent conflicts
                $existsInCashSales = WaPosCashSales::where('sales_no', $sales_no)->exists();
                $existsInRequisition = \App\Model\WaInternalRequisition::where('requisition_no', $sales_no)->exists();

                if (!$existsInCashSales && !$existsInRequisition) {
                    $series_module->update(['last_number_used' => $newNumber]);
                    DB::commit();
                    
                    // Log successful generation for debugging
                    \Log::info('API: Generated unique sales number', [
                        'sales_no' => $sales_no,
                        'attempt' => $attempt
                    ]);
                    
                    return $sales_no;
                }
                
                DB::rollBack();
                
                // Log collision for debugging
                \Log::warning('API: Sales number collision detected', [
                    'sales_no' => $sales_no,
                    'attempt' => $attempt,
                    'exists_in_cash_sales' => $existsInCashSales,
                    'exists_in_requisition' => $existsInRequisition
                ]);
                
                $attempt++;
                
                // Add small delay to reduce race conditions
                usleep(rand(10000, 50000)); // 10-50ms random delay

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('API: Error generating sales number', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        throw new \RuntimeException("Unable to generate unique sales number after {$maxAttempts} attempts");
    }
    public function update($id, Request $request)
    {
        $user = JWTAuth::toUser($request->token);

        $validation = \Validator::make($request->all(), [
            'paid' => 'required|boolean',
            'payment_methods' => function ($attribute, $value, $fail) use ($request) {
                if ($request->input('paid') === true && empty($value)) {
                    $fail('Payment methods are required when paid is true.');
                }
            },
            'payment_methods.*.method_id' => 'required_if:paid,true|exists:payment_methods,id',
            'payment_methods.*.amount' => 'required_if:paid,true',
            //            'payment_methods.*.tender_entry_id' => 'required_if:paid,true',
            'items' => 'array|required',
            'items.*.item_id' => 'required|exists:wa_inventory_items,id',
            'items.*.item_quantity' => 'required|numeric',
            'time' => 'required',
        ]);
        $paid_amount = collect($request->payment_methods)->sum('amount');
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ]);
        }


        $cashSales = WaPosCashSales::with('items')->find($id);
        $user = JWTAuth::toUser($request->token);
        /*check if Sale is pending*/
        if ($cashSales->status == 'PENDING') {
            $itemIds = [];
            foreach ($request->items as $item) {
                $itemIds[] = $item['item_id'];
            }
            $products = WaInventoryItem::select([
                '*',
                DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
            ])->with(['getAllFromStockMoves', 'getInventoryCategoryDetail', 'getInventoryCategoryDetail.getWIPGlDetail', 'getInventoryCategoryDetail.getStockGlDetail', 'getInventoryCategoryDetail.getIssueGlDetail'])
                ->whereIn('id', $itemIds)
                ->get();

            foreach ($request->items as $item) {
                $name =  @$products->firstWhere('id', $item['item_id'])->title;

                $qoh = WaStockMove::where('stock_id_code', $products->firstWhere('id', $item['item_id'])->stock_id_code)->where('wa_location_and_store_id', $user->wa_location_and_store_id)->sum('qauntity');
                if ($item['item_quantity'] > $qoh) {
                    return response()->json([
                        'status' => false,
                        'message' => ['item_id.' . $item['item_id'] => [$name.' Quantity cannot be greater than balance stock']]
                    ]);
                }
                $itemBin = @$products->firstWhere('id', $item['item_id'])->getBinData(getLoggeduserProfile()->wa_location_and_store_id)->id;
                if ($itemBin == null) {
                    return response()->json([
                        'result' => 0,
                        'errors' => ['item_id.' . $item['item_id'] => [$name. ' This Product is not assigned to a bin']]
                    ]);
                }
            }

            foreach ($request->items as $item) {
                $value =  $products->firstWhere('id', $item['item_id']);
                $number = $item['item_quantity'];
                if (is_numeric($number) && floor($number) != $number) {

                    if ($value->item_count != null) {
                        if (!checkSplit($number)) {
                            return response()->json([
                                'result' => 0,
                                'errors' => ['item_id.' . $value->id => ['The product cannot be broken into provided quantity']]
                            ]);
                        }
                    } else {
                        return response()->json([
                            'result' => 0,
                            'errors' => ['item_id.' . $value->id => ['The product cannot be broken into half']]
                        ]);
                    }
                }
            }

            try {
                $check = DB::transaction(function () use ($products, $request, $cashSales, $user) {
                    $items = $request->items;
                    $payment_methods = $request->payment_methods;
                    $route_customer = $request->customer_id;
                    $paid  = $request->paid;
                    $sale_id  = $cashSales->id;
                    return PosCashSaleService::recordSale($items, $user->id, $products,'', $route_customer, $payment_methods, $paid, $user->id, $sale_id,true);
                });

                if ($check) {
                    $check->refresh();
                    $message = 'Order Placed successfully.';
                    return response()->json(['status' => true, 'message' => $message, 'order' => $check]);
                }
                return response()->json(['status' => false, 'message' => 'Something went wrong']);
            } catch (\Exception $exception) {
                return response()->json(['status' => -1, 'message' => $exception->getMessage()], 400);
            }
        }
        return response()->json(['status' => -1, 'message' => 'You can Not Edit a complete Order'], 400);
    }

    public function statistics(Request $request)
    {
        $user = JWTAuth::toUser($request->token);

        $startDate = now()->startOfDay();
        $endDate = now();

        $packSizes = [6, 9, 17, 4, 10, 1];

        $startDate =  Carbon::parse($request->from)->startOfDay() ?? now()->startOfDay();
        $endDate = Carbon::parse($request->to)->endOfDay() ?? now();

        $dozen = WaPosCashSalesItems::whereHas('item.packSize', function ($query) use ($packSizes) {
            $query->whereIn('id', $packSizes);
        })
            ->whereHas('parent', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 'Completed');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('qty');


        $totalCartons = WaPosCashSalesItems::whereHas('item.packSize', function ($query) use ($packSizes) {
            $query->where('title', 'CTN');
        })
            ->whereHas('parent', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 'Completed');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('qty');


        $orders = WaPosCashSales::where('user_id', $user->id)
            ->where('status', 'Completed')
            ->join('wa_pos_cash_sales_items', 'wa_pos_cash_sales.id', '=', 'wa_pos_cash_sales_items.wa_pos_cash_sales_id')
            ->select(
                DB::raw('SUM(wa_pos_cash_sales_items.total) as total_amount'),
                DB::raw("(select count(*) 
            from wa_pos_cash_sales 
            where wa_pos_cash_sales.user_id = $user->id 
            and wa_pos_cash_sales.status = 'completed'
            and wa_pos_cash_sales.created_at >= '$startDate' 
            and wa_pos_cash_sales.created_at <= '$endDate') as sales_count"),
                DB::raw("(select count(*) 
          from wa_pos_cash_sales 
          where wa_pos_cash_sales.user_id = $user->id 
          and wa_pos_cash_sales.status = 'archived' 
          and wa_pos_cash_sales.created_at >= '$startDate' 
          and wa_pos_cash_sales.created_at <= '$endDate') as archived_orders"),
                //                DB::raw("(select sum(wa_pos_cash_sales_items.total)
                //          from wa_pos_cash_sales
                //          inner join wa_pos_cash_sales_items on wa_pos_cash_sales.id = wa_pos_cash_sales_items.wa_pos_cash_sales_id
                //          where wa_pos_cash_sales.user_id = $user->id
                //          and wa_pos_cash_sales.status = 'archived'
                //          and wa_pos_cash_sales.created_at >= '$startDate'
                //          and wa_pos_cash_sales.created_at <= '$endDate') as archived_sales")
                DB::raw("(select sum(wa_pos_cash_sales_items.total) - sum(wa_pos_cash_sales_items.discount_amount) 
          from wa_pos_cash_sales 
          inner join wa_pos_cash_sales_items on wa_pos_cash_sales.id = wa_pos_cash_sales_items.wa_pos_cash_sales_id
          where wa_pos_cash_sales.user_id = $user->id 
          and wa_pos_cash_sales.status = 'archived' 
          and wa_pos_cash_sales.created_at >= '$startDate' 
          and wa_pos_cash_sales.created_at <= '$endDate') as archived_sales")

            )
            ->whereBetween('wa_pos_cash_sales_items.created_at', [$startDate, $endDate])
            ->orderByDesc('wa_pos_cash_sales.created_at')
            ->first();

        $total_orders = $orders->sales_count ?? 0;
        $total_amount_sold = $orders->total_amount ?? 0;
        $cartons = (int) $totalCartons;
        $dozens = (int) $dozen;
        $archived_orders = (int) $orders->archived_orders;
        $archived_sales = (int) $orders->archived_sales;

        $resp = [
            'dozens' => $dozens,
            'cartons' => $cartons,
            'orders' => $total_orders,
            'sales' => $total_amount_sold,
            'archived_orders' => $archived_orders,
            'archived_sales_amount' => $archived_sales,
        ];

        return response()->json($resp);
    }

    public function close(Request $request, $id)
    {
        // $validator = Validator::make($request->all(), [
        //     'token' => 'required'
        // ]);
        // if ($validator->fails()) {
        //     $error = $this->validationHandle($validator->messages());
        //     return response()->json(['status' => false, 'message' => $error]);
        // }
        $getUserData = JWTAuth::toUser($request->token);

        $cashSale = WaPosCashSales::where('sales_no', $id)
            ->with('dispatch')
            ->where('branch_id', $getUserData->restaurant_id)
            ->withCount(['dispatch' => function ($query) {
                $query->where('status', 'dispatching');
            }])
            ->first();

        if (!$cashSale) {
            return response()->json([
                'result' => -1,
                'message' => 'No order Found with that sale number in dispatching state'
            ], 401);
        }
        if ($cashSale->status != 'Completed') {
            return response()->json([
                'result' => -1,
                'message' => 'Order not Completed yet'
            ], 401);
        }
        if ($cashSale->dispatch_count != 0) {
            return response()->json([
                'result' => -1,
                'message' => 'Order is still Dispatching'
            ], 401);
        }
        $cashSale->dipatched_by = $getUserData->id;
        $cashSale->dispatched_at = Carbon::now()->toDateTimeString();
        $cashSale->is_suspended = 1;
        $cashSale->save();

//        if ($cashSale->customer_phone_number) {
//            $message = "Your order #{$cashSale->sales_no} has been closed. Thank you for shopping with us.";
//            $this->smsService->sendMessage($message, $cashSale->customer_phone_number);
//        }

        foreach ($cashSale->dispatch as $item) {
            $item->update([
                'status' => 'collected'
            ]);
        }

        return response()->json([
            'result' => 1,
            'message' => 'Order Closed Successfully'
        ]);
    }

    public function getPaymentMethods(Request $request)
    {
        //        $paymentMethods = PaymentMethod::where('use_for_receipts', true)
        //            ->where('title','!=', 'CASH')
        //            ->select(['id','title'])->get();
        $user = JWTAuth::toUser($request->token);
        $paymentMethods =  PaymentMethod::join('wa_chart_of_accounts_branches as branches', 'payment_methods.gl_account_id', '=', 'branches.wa_chart_of_account_id')
            ->where('branches.restaurant_id', $user->restaurant_id)
            ->where('payment_methods.use_in_pos', true)
            ->select([
                'payment_methods.id',
                'payment_methods.title',
            ])
            ->get();
        return response()->json([
            'status' => 1,
            'methods' => $paymentMethods
        ]);
    }

    public function calculateInventoryItemDiscount(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'inventory_item_id' => 'required|exists:wa_inventory_items,id',
            'item_quantity' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ], 403);
        }


        $discount = 0;
        $discountDescription = null;
        // $discountBand = DB::table('discount_bands')
        //     ->where('inventory_item_id', $request->inventory_item_id)
        //     ->where('from_quantity', '<=', $request->item_quantity)
        //     ->where('to_quantity', '>=', $request->item_quantity)
        //     ->first();
        $discountBand = DiscountBand::where('inventory_item_id', $request->inventory_item_id)
            ->where(function ($query) use ($request) {
                $query->where('from_quantity', '<=', $request->item_quantity)
                    ->where('to_quantity', '>=', $request->item_quantity);
            })
            ->orWhere(function ($query) use ($request) {
                $query->where('inventory_item_id', $request->inventory_item_id)
                    ->where('to_quantity', '<', $request->item_quantity);
            })
            ->orderBy('to_quantity', 'desc')
            ->first();
        if ($discountBand) {
            $discount = $discountBand->discount_amount * $request->item_quantity;
            $discountDescription = "$discountBand->discount_amount discount for quantity between $discountBand->from_quantity and $discountBand->to_quantity";
        } else {
            /*check for discount price promotion*/
            $discount = $this->checkPromotion($request->inventory_item_id);
        }



        $data = [
            'discount' => $discount,
            'item_id' => $request->inventory_item_id
        ];
        return response()->json($data);
    }

    public function checkPromotion($item_id)
    {
        $today = Carbon::today();
        $discount = 0;
        $promotion = ItemPromotion::where('inventory_item_id', $item_id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->where('from_date', '<=', $today)
                    ->where('to_date', '>=', $today);
            })
            ->first();

        if ($promotion) {
            /*get promotion type*/
            $promotionTypeModel = $promotion->promotion_type_id ? PromotionType::find($promotion->promotion_type_id) : null;
            $promotionType = $promotionTypeModel ? $promotionTypeModel->description : null;

            if ($promotionType) {

                /*Price Discount*/
                if ($promotionType == PromotionMatrix::PD->value) {
                    /*chenge selling price*/
                    $selling_price = $promotion->promotion_price;
                    $current_price = $promotion->current_price;
                    $discount = $current_price - $selling_price;
                }
            }
        }

        return $discount;
    }

    public function checkPayment(Request $request)
    {
        $request->validate([
            'paymentMethod' => 'required',
            'amount' => 'required',
        ]);
        $paymentMethod = $request->paymentMethod;
        $amount =  $request->amount;
        $tenderEntries = WaTenderEntry::where('wa_payment_method_id', $paymentMethod)
            ->select('id', 'paid_by', 'additional_info', 'reference', 'created_at')
            ->where('amount', $amount)
            ->where('created_at', '>=', Carbon::now()->subMinutes(10))
            ->get();

        $filteredEntries = [];

        if ($tenderEntries->isNotEmpty()) {
            foreach ($tenderEntries as $tenderEntry) {
                $existsInPayments = WaPosCashSalesPayments::where('remarks', 'like', '%' . $tenderEntry->id . '%')->exists();
                if (!$existsInPayments) {
                    $filteredEntries[] = $tenderEntry;
                }
            }
        }
        $fake = [
            [
                'id' => 40820,
                'paid_by' => 'Test Payer',
                'additional_info' => 'FT24102CTMJ8',
                'reference' => 'SDB2UN9B5Y',
                'created_at' => now(),
            ]
        ];
        return response()->json([
            'result' => 1,
            'items' => $fake
        ]);
    }

    public function customerReceipt($id)
    {
        try {
            $data = WaPosCashSales::with(['items.item.pack_size', 'buyer', 'user'])->findOrFail($id);
            
            // Get payment details
            $payments = DB::table('wa_pos_cash_sales_payments')
                ->select(
                    'wa_pos_cash_sales_payments.*',
                    'payment_methods.title',
                    'payment_methods.is_cash',
                    'payment_providers.slug as payment_slug'
                )
                ->leftjoin('payment_methods', 'payment_methods.id', '=', 'wa_pos_cash_sales_payments.payment_method_id')
                ->leftjoin('payment_providers', 'payment_providers.id', '=', 'payment_methods.payment_provider_id')
                ->where('wa_pos_cash_sales_id', $data->id)
                ->get();

            if (!$data) {
                return response()->json([
                    'result' => 0,
                    'message' => 'Invalid Request'
                ], 401);
            }

            // Increment print count
            $data->print_count++;
            $data->save();

            // Configure PDF options
            $pdf = PDF\Pdf::loadView('admin.pos_cash_sales.pdq_print', compact('data', 'payments'))
                ->setPaper('A4')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true)
                ->setOption('isPhpEnabled', true)
                ->setOption('chroot', public_path());

            // Return PDF with proper headers
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="receipt-' . $data->sales_no . '.pdf"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            \Log::error('Error generating customer receipt: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate receipt'], 500);
        }
    }

    public function dispatchSheet(Request $request, $id)
    {
        $user = JWTAuth::toUser($request->token);

        $data = WaPosCashSales::with(['items', 'payments', 'user', 'items.item', 'items.item.pack_size', 'items.location', 'items.dispatch_by', 'items.item.bin_locations'])
            ->where('id', $id)
            ->first();
        $groupedItems = $data->items->filter(function ($item) use ($user) {
            $binData = $item->item->getBinData($user->wa_location_and_store_id);
            return $binData && $binData->is_display == 0;
        })->groupBy(function ($item) use ($user) {
            return $item->item->getBinData($user->wa_location_and_store_id)->title;
        });

        $data->setRelation('items', $groupedItems);
        if (!$data) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        if ($data->items->count() == 0) {
            return response()->json([
                'result' => 0,
                'message' => 'No items to dispatch'
            ], 401);
        }
        $data->dispatch_print_count++;
        $data->save();

        $pdf = PDF\Pdf::loadView('admin.pos_cash_sales.pdq_dispatch-slip', compact('data'));
        return $pdf->stream();
    }
    public function displayDispatchSheet(Request $request, $id)
    {
        $user = JWTAuth::toUser($request->token);

        $data = WaPosCashSales::with(['items', 'payments', 'user', 'items.item', 'items.item.pack_size', 'items.location', 'items.dispatch_by', 'items.item.bin_locations'])
            ->where('id', $id)
            ->first();
        $groupedItems = $data->items->filter(function ($item) use ($user) {
            $binData = $item->item->getBinData($user->wa_location_and_store_id);
            return $binData && $binData->is_display == 1;
        })->groupBy(function ($item) use ($user) {
            return $item->item->getBinData($user->wa_location_and_store_id)->title;
        });

        $data->setRelation('items', $groupedItems);
        if (!$data) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        if ($data->items->count() == 0) {
            return response()->json([
                'result' => 0,
                'message' => 'No items to dispatch'
            ], 401);
        }
        $data->print_count++;
        $data->save();

        $pdf = PDF\Pdf::loadView('admin.pos_cash_sales.pdq_dispatch-slip', compact('data'));
        return $pdf->stream();
    }

    public function initiatePayment(Request $request)
    {
        $request->validate([
            'sales_id' => 'required|exists:wa_pos_cash_sales,id',
            'payment_method' => 'required|exists:payment_methods,id',
            'phone_number' => 'required|numeric',
            'amount' => 'required|numeric',
        ]);
        $service  = new PosPaymentService();

        $order = WaPosCashSales::with('items')
            ->where('id', $request->sales_id)
            ->first();
        if (!$order) {
            return  response()->json([
                'result' => -1,
                'message' => 'Order with provided ID not found'
            ], 404);
        }
        if ($order->status == 'Completed') {
            return  response()->json([
                'result' => 0,
                'message' => 'Order Already Paid'
            ]);
        }

        $response = $service->initiatePayment($order, $request->amount, $request->payment_method, $request->phone_number);
        return $response;
    }

    public function getPayDetails(Request $request)
    {
        $request->validate([
            'sales_id' => 'required|exists:wa_pos_cash_sales,id',
        ]);
        $order = WaPosCashSales::with('items')
            ->where('id', $request->sales_id)
            ->first();
        if (!$order) {
            return  response()->json([
                'result' => -1,
                'message' => 'Order with provided ID not found'
            ], 404);
        }
        /*check for Mpesa Payment*/
        $paymentInvoice = InvoicePayment::where('order_no', $order->sales_no)->first();
        if ($paymentInvoice) {
            /*find Mpesa Transaction*/
            $mpesaTrans = MpesaOperation::where('invoice_payment_id', $paymentInvoice->id)->first();
            if ($mpesaTrans) {
                /*check Status*/
                return response()->json([
                    'result' => 1,
                    'message' => $mpesaTrans->result_description ?? 'STK Failed',
                    'checkout_request_id' => $mpesaTrans->checkout_request_id ?? '',
                    'merchant_request_id' => $mpesaTrans->merchant_request_id ?? '',
                    'mpesa_receipt_number' => $mpesaTrans->mpesa_receipt_number ?? '',
                    'phone_number' => $mpesaTrans->phone_number ?? '',
                    'callback_url' => env('MPESA_STK_CALLBACK_URL') . '/api/mpesa/callback',
                ]);
            }
        }
        return response()->json([
            'result' => 0,
            'message' => 'Sale Does not have an Mpesa Payment Transaction',
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'checkout_request_id' => 'required',
        ]);
        $mpesaService = new MpesaService();
        return $mpesaService->stkQuery($request->checkout_request_id);
    }

    public function testPusher($id)
    {
        $payload = [
            'sales_id' => $id,
            'paid' => true,
            'details' => "Payment Success",
        ];
        event(new PaymentReceived($payload));
    }

    public function getUserScannedCashSales(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'token' => 'required'
        // ]);
        // if ($validator->fails()) {
        //     $error = $this->validationHandle($validator->messages());
        //     return response()->json(['status' => false, 'message' => $error]);
        // }
        $getUserData = JWTAuth::toUser($request->token);
        $today = Carbon::now()->toDateString();
        $scannedReceipts = DB::table('wa_pos_cash_sales')
            ->select(
                'wa_pos_cash_sales.*'
            )
            ->whereDate('dispatched_at', $today)
            ->where('dipatched_by', $getUserData->id);
        if ($request->search) {
            $scannedReceipts->where('sales_no', 'LIKE', '%' . $request->search . '%');
        }
        $scannedReceipts = $scannedReceipts->orderBy('id')
            ->cursorPaginate(20);
        if ($scannedReceipts->count() == 0) {
            return response()->json(['status' => false, 'message' => 'You have  not closed any orders ']);
        }
        return response()->json(['status' => true, 'scannedReceipts' => $scannedReceipts]);
    }
}
