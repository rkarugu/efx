<?php

namespace App\Http\Controllers\Admin;

use App\Alert;
use App\Enums\PromotionMatrix;
use App\Enums\Status\CashSaleDispatchStatus;
use App\Interfaces\SmsService;
use App\ItemPromotion;
use App\Jobs\PerformPostReturnActions;
use App\Model\Setting;
use App\Model\WaChartsOfAccount;
use App\Model\WaInternalRequisition;
use App\Model\WaInventoryLocationUom;
use App\Model\WaLocationAndStore;
use App\Model\WaNumerSeriesCode;
use App\Model\WaPosCashSalesItemReturns;
use App\Model\WaUnitOfMeasure;
use App\Models\CashDropTransaction;
use App\Models\CashSaleEntry;
use App\Models\HamperItem;
use App\Models\PromotionType;
use App\Models\ReturnReason;
use App\Models\WaAccountTransaction;
use App\Models\WaBranch;

use App\Services\InfoSkySmsService;
use App\Services\PosCashSaleService;
use App\Services\PosPaymentService;
use App\Services\SupplierIncentiveCalculator;
use App\User;
use App\WaTenderEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WaInventoryItem;
use App\Model\WaPosCashSales;
use App\Model\WaPosCashSalesItems;
use App\Model\WaRouteCustomer;
use App\Model\PaymentMethod;
use App\Model\WaGlTran;
use App\Model\WaLogs;
use App\Model\WaStockMove;
use App\Model\WaPosCashSalesDispatch;
use App\Model\WaInternalRequisitionDispatch;
use App\Model\WaPosCashSalesPayments;
use App\Model\WaEsdDetails;
use App\Model\DispatchLoadedProducts;
use App\Model\Restaurant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Session;
use Yajra\DataTables\Facades\DataTables;

class PosCashSalesController extends Controller
{
    protected $model;
    protected $title;
    protected $pmodule;
    protected $smsService;


    public function __construct(SmsService $smsService)
    {
        $this->model = 'pos-cash-sales';
        $this->title = 'POS Cash Sales';
        $this->pmodule = 'pos-cash-sales';
        $this->smsService = $smsService;
    }

    public function index(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $user = getLoggeduserProfile();


        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            if ($request->ajax()) {
                $sortable_columns = [
                    'wa_pos_cash_sales.id',
                    'wa_pos_cash_sales.date',
                    'wa_pos_cash_sales.time',
                    'creating_user.name',
                    'attending_user.name',
                    'wa_pos_cash_sales.sales_no',
                    'wa_pos_cash_sales.customer',
                    'wa_pos_cash_sales.cash',
                    'wa_pos_cash_sales.change',
                ];
                $limit          = $request->input('length');
                $start          = $request->input('start');
                $search         = $request['search']['value'];
                $orderby        = $request['order']['0']['column'] ?? 1;
                $order          = $request['order']['0']['dir'] ?? "DESC";
                $draw           = $request['draw'];



                $data = WaPosCashSales::with(['items', 'payment'])
                    ->select([
                        'wa_pos_cash_sales.*',
                        'creating_user.name as tablet_cashier_name',
                        'attending_user.name as counter_cashier_name',
                        DB::RAW('(select wa_esd_details.description from wa_esd_details where wa_esd_details.invoice_number = wa_pos_cash_sales.sales_no ORDER BY id DESC limit 1) as esd_status')
                    ])
                    ->leftJoin('users as creating_user', 'creating_user.id', '=', 'wa_pos_cash_sales.user_id')
                    ->leftJoin('users as attending_user', 'attending_user.id', '=', 'wa_pos_cash_sales.attending_cashier')
                    ->where(function ($w) use ($permission, $user, $request, $search, $pmodule) {
                        if ($request->input('from') && $request->input('to')) {
                            $w->whereBetween('date', [$request->input('from'), $request->input('to')]);
                        }
                        if ($permission === 'superadmin') {
                            $branch_id = $request->restaurant_id ?? Auth::user()->restaurant_id;
                            $w->where('branch_id', $branch_id);
                        } else {
                            if (isset($permission[$pmodule . '___view-all'])) {
                                $w->where('branch_id', Auth::user()->restaurant_id);
                            } else {
                                $w->where('branch_id', Auth::user()->restaurant_id)
                                    // ->where('wa_pos_cash_sales.attending_cashier', Auth::id())
                                    ->where(function ($query) {
                                        $query->where('wa_pos_cash_sales.attending_cashier', Auth::id())
                                            ->orWhere('wa_pos_cash_sales.is_tablet_sale', true);
                                    });
                            }
                        }
                    })
                    ->when($search, function ($query) use ($search) {
                        return $query->where(function ($w) use ($search) {
                            $w->where('wa_pos_cash_sales.sales_no', 'LIKE', "%$search%")
                                ->orWhere('wa_pos_cash_sales.customer', 'LIKE', "%$search%")
                                ->orWhere('wa_pos_cash_sales.cash', 'LIKE', "%$search%")
                                ->orWhere('wa_pos_cash_sales.change', 'LIKE', "%$search%")
                                ->orWhere('creating_user.name', 'LIKE', "%$search%")
                                ->orWhere('attending_user.name', 'LIKE', "%$search%");
                        });
                    })
                    ->where('wa_pos_cash_sales.status', '!=', 'Archived')
                    ->where('wa_pos_cash_sales.status', ($request->status ?? 'PENDING'));

               if($request->status && $request->status == 'completed' && !can('view-all','pos-cash-sales')){
                   $data->where('wa_pos_cash_sales.attending_cashier', Auth::id());
               }

                $data = $data->orderBy($sortable_columns[$orderby], $order);

                $allData = $data->get();

                $grandTotal = $allData->sum(function ($order) {
                    return $order->items->sum(function ($item) {
                        return ($item->qty * $item->selling_price) - $item->discount_amount;
                    });
                });

                $totalCms = $allData->count();

                $response = $allData->slice($start, $limit)->map(function ($item) use ($permission, $user) {
                    if ($item->is_tablet_sale) {
                        $item->tablet_cashier = $item->tablet_cashier_name ?? 'N/A';
                        $item->counter_cashier = ($item->user_id == $item->attending_cashier) ? 'N/A' : ($item->counter_cashier_name ?? 'N/A');
                    } else {
                        $item->counter_cashier = $item->counter_cashier_name ?? 'N/A';
                        $item->tablet_cashier = 'N/A';
                    }

                    $item->date_time = $item->date . ' / ' . $item->time;
                    $item->payment_title = @$item->payment->title;

                    // Calculate total from items
                    $tot = $item->items->sum(function ($child) {
                        return ($child->qty * $child->selling_price) - $child->discount_amount;
                    });

                    $item->total = $tot;
                    $item->links = '';

                    if ($item->status == 'PENDING') {
                        $item->links .= '<a style="margin: 2px;" class="btn btn-secondary btn-sm" href="' . route('pos-cash-sales.edit', base64_encode($item->id)) . '" title="Edit"><i class="fa fa-edit" aria-hidden="true"></i></a>';
                        $item->links .= '<a onclick="return confirm(\'Are you sure for archive this item?\')" style="margin: 2px;" class="btn btn-secondary btn-sm archive_btn" href="' . route('pos-cash-sales.archive', base64_encode($item->id)) . '" title="Archive"><i class="fa fa-trash-0 " aria-hidden="true"></i></a>';
                    }

                    $item->links .= '<a style="margin: 2px;" class="btn btn-danger btn-sm" href="' . route('pos-cash-sales.show', base64_encode($item->id)) . '" title="Details"><i class="fa fa-eye" aria-hidden="true"></i></a>';

                    if ($item->status == 'Completed') {
                        if ($permission == 'superadmin' || (isset($permission['pos-cash-sales___print']) && isset($permission['pos-cash-sales___re-print']))) {
                            $item->links .= '<a style="margin: 2px;" class="btn btn-primary btn-sm printBill" onclick="printBill(\'' . route('pos-cash-sales.invoice_print', base64_encode($item->id)) . '\'); return false;" href="#" title="Print Invoice"><i class="fa fa-print" aria-hidden="true"></i></a>';
                        }
                        if ($permission == 'superadmin' || (isset($permission['pos-cash-sales___pdf']) && isset($permission['pos-cash-sales___re-print']))) {
                            $item->links .= '<a style="margin: 2px;" class="btn btn-warning btn-sm" href="' . route('pos-cash-sales.exportToPdf', base64_encode($item->id)) . '" title="Download Invoice as PDF"><i class="fa fa-file-pdf" aria-hidden="true"></i></a>';
                        }
                        if ($permission == 'superadmin' || isset($permission['pos-cash-sales-r___return'])) {
                            $item->links .= '<a style="margin: 2px;" class="btn btn-success btn-sm" href="' . route('pos-cash-sales.return_items', base64_encode($item->id)) . '" title="Return"><i class="fa fa-retweet" aria-hidden="true"></i></a>';
                        }
                        if ($permission == 'superadmin' || isset($permission['pos-cash-sales___dispatch-slip'])) {
                            $item->links .= '<a style="margin: 2px;" class="btn btn-success btn-sm" onclick="printLoadings(' . $item->id . ')" href="#" title="Dispatch sheet"><i class="fa fa-truck" aria-hidden="true"></i></a>';
                        }
                    }

                    if ($item->status == 'PENDING') {
                        if ($permission == 'superadmin' || isset($permission['pos-cash-sales___archive'])) {
                            $item->links .= '<input type="checkbox" class="archive_checkbox" style="margin: 2px;" value="' . $item->id . '" title="Archive" name="archive_checkbox[]">';
                        }
                    }

                    return $item;
                });

                $pageTotal = 0;
                if (isset($permission[$pmodule . '___show-total']) || $permission == 'superadmin') {
                    $pageTotal = $grandTotal;
                }

                $return = [
                    "draw" => intval($draw),
                    "recordsFiltered" => intval($totalCms),
                    "recordsTotal" => intval($totalCms),
                    "data" => $response,
                    'total' => manageAmountFormat($grandTotal)
                ];

                return $return;

            }

            $branches = Restaurant::pluck('name','id');

            return view('admin.pos_cash_sales.index', compact('user', 'title', 'model', 'breadcum', 'pmodule', 'permission','branches'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function create(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $user = getLoggeduserProfile();

        $cashier = User::find(Auth::id());
        $cashDropsQuery = DB::table('cash_drop_transactions')
            ->select(
                DB::raw('SUM(amount) as total_drops'),
                DB::raw('COUNT(CASE WHEN cash_drop_transactions.bank_receipt_number IS NULL THEN 1 END) as unbanked')
            )
            ->whereDate('cash_drop_transactions.created_at', today()->toDateString())
            ->where('cashier_id', $cashier->id)
            ->first();



        $dropLimitAlertPercentage = random_int(5, 20);
        $drop_limit = $user->drop_limit ?? 100000;
        $cash_at_hand = $user->cashAtHand();
        if ($drop_limit > 0) {
            $percentage = ($cash_at_hand / $drop_limit) * 100;
        } else {
            $percentage = 0;
        }
        $difference = 100 - $percentage;

        try {
            if ($difference <= $dropLimitAlertPercentage) {
                Session::flash('success', 'You are Approaching your Drop Limit. Request Chief cashier to make a drop early.');
                $user->dropLimitAlert();
            }
        } catch (\Exception $e) {

        }
        /*
         * Send Notification to Chief cashier to Come drop
         * */

        $selling_allowance =  $drop_limit - $cash_at_hand;


        /*check if all drops have been banked*/

        if (getLoggeduserProfile()->role_id  != 1 && $drop_limit <= $cash_at_hand) {
            Session::flash('warning', 'Drop cash to proceed.');
            return redirect()->back();
        }
//        if (getLoggeduserProfile()->role_id  != 1 && $cashDropsQuery->unbanked != 0) {
//            Session::flash('warning', 'Your have been blocked from selling, till your last Drop is banked.');
//            return redirect()->back();
//        }

        if (isset($permission[$pmodule . '___add']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];

            $paymentMethod = PaymentMethod::join('wa_chart_of_accounts_branches as branches', 'payment_methods.gl_account_id', '=', 'branches.wa_chart_of_account_id')
                ->where('branches.restaurant_id', Auth::user()->restaurant_id)
                ->where('payment_methods.use_in_pos', true)
                ->select(['payment_methods.*'])
                ->get();
            //            $paymentMethod = PaymentMethod::where('use_for_receipts', true)
            //                ->select(['id','title'])->get();
            return view('admin.pos_cash_sales.create', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'paymentMethod', 'selling_allowance'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function getInventryItemDetails(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $editPermission = '';
        $pmodule = $this->pmodule;
        if (!isset($permission[$pmodule . '___edit-values']) && $permission != 'superadmin') {
            $editPermission = 'readonly';
        }

        $item = WaInventoryItem::select([
            '*',
            DB::raw('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id AND wa_stock_moves.wa_location_and_store_id = ' . (getLoggeduserProfile()->wa_location_and_store_id) . ') as quantity'),
        ])
            ->with(['getTaxesOfItem', 'pack_size'])
            ->where('id', $request->id)
            ->first();

        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $per = 0;
        if ($item->getTaxesOfItem) {
            $per = $item->getTaxesOfItem->tax_value;
        }

        $data = [
            'id' => $item->id,
            'stock_id_code' => $item->stock_id_code,
            'image_url' => $item->image ? asset_public('uploads/inventory_items/' . $item->image) : asset('assets/images/users/0.jpg'),
            'description' => $item->description,
            'quantity_in_stock' => $item->quantity ?? 0,
            'unit' => $item->pack_size->title ?? null,
            'item_count' => $item->item_count,
            'selling_price' => $item->selling_price,
            'tax' => $item->getTaxesOfItem ? ['id' => $item->getTaxesOfItem->id, 'title' => $item->getTaxesOfItem->title] : null,
            'tax_percentage' => $per,
            'edit_permission' => $editPermission,
        ];

        return response()->json($data);
    }

    public function calculateInventoryItemDiscount(Request $request)
    {
        $discount = 0;
        $discountDescription = null;
        $discountBand = DB::table('discount_bands')->where('inventory_item_id', $request->item_id)
            ->where('from_quantity', '<=', $request->item_quantity)
            ->where('to_quantity', '>=', $request->item_quantity)
            ->first();
        if ($discountBand) {

            $discount = $discountBand->discount_amount * $request->item_quantity;
           $discountDescription = "$discountBand->discount_amount discount for quantity between $discountBand->from_quantity and $discountBand->to_quantity";
        } else {
            /*check for discount price promotion*/
            $discount = $this->checkPromotion($request->item_id);
        }
        $data = [
            'discount' => $discount,
            'item_id' => $request->item_id
        ];

        return response()->json($data);
    }

    public function checkPromotion($item_id)
    {
        $discount = 0;
        $today = Carbon::today();
        $promotion = ItemPromotion::where('inventory_item_id', $item_id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->where('from_date', '<=', $today)
                    ->where('to_date', '>=', $today);
            })
            ->first();

        if ($promotion) {
            /*get promotion type*/
            $promotionType = $promotion->promotion_type_id ? PromotionType::find($promotion->promotion_type_id)->description : null;

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


    public function store(Request $request)
    {
        if (!$request->ajax()) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        try {
            $permission =  $this->mypermissionsforAModule();
            $pmodule = $this->pmodule;
            if (!isset($permission[$pmodule . '___add']) && $permission != 'superadmin') {
                return response()->json([
                    'result' => -1,
                    'message' => 'Restricted:You Dont have permissions'
                ]);
            }
            $validation = \Validator::make(
                $request->all(),
                [
                    'item_id' => 'array',
                    'item_id.*' => 'required|exists:wa_inventory_items,id',
                    'item_quantity.*' => 'required|min:0|numeric',
                    'item_selling_price.*' => 'required|min:1|numeric',
                    'item_vat.*' => 'required|exists:tax_managers,id',
                    'item_discount_per.*' => 'nullable|min:0|numeric',
                    'route_customer' => 'required|exists:wa_route_customers,id',
                    'request_type' => 'required|in:send_request,save,mpesa',
                    'payment_amount' => 'required|array',
                ],
                [
                    'item_discount_per.*.min' => 'Discount must be greater than or equal to 0',
                    'item_quantity.*.min' => 'Quantity must be greater than or equal to 1',
                    'item_selling_price.*.min' => 'Selling Price must be greater than or equal to 1',
                ],
                [
                    'item_id.*' => 'Item',
                    'item_quantity.*' => 'Quantity',
                    'item_selling_price.*' => 'Price',
                    'item_discount_per.*' => 'Discount',
                    'item_vat.*' => 'Vat',
                ]
            );
            if ($validation->fails()) {
                return response()->json([
                    'result' => 0,
                    'errors' => $validation->errors()
                ]);
            }
            $errors = [];

            if ($request->request_type == 'send_request') {
                $errorpp = 0;
                foreach ($request->payment_amount as $key => $value) {
                    if ($value != NULL || $value != '') {
                        $value = (float)$value;
                        if ($value <= 0) {
                            $errors['payment_amount' . $key][] = 'Invalid Payment';
                        }

                        $errorpp = 1;
                    }
                }
                if ($errorpp == 0) {
                    $errors['payment_amount.1'][] = 'Invalid Payment';
                }
            }
            if (count($errors) > 0) {
                return response()->json([
                    'result' => 0,
                    'errors' => $errors
                ]);
            }
            $allInventroy = WaInventoryItem::select([
                '*',
                DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
            ])->with(['getAllFromStockMoves', 'getInventoryCategoryDetail', 'getInventoryCategoryDetail.getWIPGlDetail', 'getInventoryCategoryDetail.getStockGlDetail', 'getInventoryCategoryDetail.getIssueGlDetail'])
                ->whereIn('id', $request->item_id)
                ->get();


            if ($allInventroy->count() == 0) {
                return response()->json([
                    'result' => -1,
                    'message' => 'Inventroy Items is required'
                ]);
            }
            $total = 0;


            foreach ($allInventroy as $key => $value) {
                if (!$request->item_selling_price[$value->id] || $value->standard_cost > $request->item_selling_price[$value->id]) {
                    return response()->json([
                        'result' => 0,
                        'errors' => ['item_selling_price.' . $value->id => ['Selling price must be greater than or equal to standard cost']]
                    ]);
                }

                $itemBin = @$value->getBinData(getLoggeduserProfile()->wa_location_and_store_id)->id;
                if ($itemBin == null) {
                    return response()->json([
                        'result' => 0,
                        'errors' => ['item_id.' . $value->id => ['This Product is not assigned to a bin']]
                    ]);
                }

                $qoh = WaStockMove::where('stock_id_code', $value->stock_id_code)->where('wa_location_and_store_id', getLoggeduserProfile()->wa_location_and_store_id)->sum('qauntity');

                if ($qoh < $request->item_quantity[$value->id]) {

                    return response()->json([
                        'result' => 0,
                        'errors' => ['item_quantity.' . $value->id => ['Quantity cannot be greater than balance stock']]
                    ]);
                }
                if ($value->block_this == 1) {
                    return response()->json([
                        'result' => 0,
                        'errors' => ['item_id.' . $value->id => ['The product has been blocked from sale due to a change in standard cost']]
                    ]);
                }

                $number = $request->item_quantity[$value->id];
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

                $sum = ($request->item_selling_price[$value->id] * $request->item_quantity[$value->id]);
                // $disAmount = ($sum * @$request->item_discount_per[$value->id]) / 100;
                $disAmount = $request->item_discount[$value->id];
                $total += ($sum - $disAmount);
            }

            $new_sales_no = self::generateNewSalesNumber();
            $check = DB::transaction(function () use ($allInventroy, $new_sales_no, $request) {

                $items = [];
                foreach ($request->item_id as $id => $itemId) {
                    $items[] = [
                        "item_id" => (int) $itemId,
                        "item_quantity" => (float) $request->item_quantity[$id],
                        "item_discount_amount" => (float) $request->item_discount[$id],
                    ];
                }
                $payment_methods = [];
                foreach ($request->payment_amount as $id => $amount) {
                    if (!is_null($amount)) {
                        $payment_remarks = isset($request->payment_remarks[$id]) ? $request->payment_remarks[$id] : null;
                        
                        // If payment_remarks is JSON, decode it
                        if ($payment_remarks && is_string($payment_remarks) && json_decode($payment_remarks)) {
                            $entries = json_decode($payment_remarks, true);
                            foreach ($entries as $entry) {
                                if (isset($entry['tender_entry_id']) && isset($entry['amount'])) {
                                    $payment_methods[] = [
                                        "method_id" => $id,
                                        "amount" => (float) $entry['amount'],
                                        "tender_entry_id" => (int) $entry['tender_entry_id'],
                                    ];
                                }
                            }
                        } 
                        // If payment_remarks is in the old format (comma-separated with hyphens)
                        else if ($payment_remarks) {
                            $entries = explode(',', $payment_remarks);
                            foreach ($entries as $entry) {
                                if (str_contains($entry, '-')) {
                                    list($tender_entry_id, $amount) = explode('-', $entry);
                                    if (is_numeric($tender_entry_id) && is_numeric($amount)) {
                                        $payment_methods[] = [
                                            "method_id" => $id,
                                            "amount" => (float) $amount,
                                            "tender_entry_id" => (int) $tender_entry_id,
                                        ];
                                    }
                                }
                            }
                        }
                        // If no payment_remarks, just use the amount
                        else {
                            $payment_methods[] = [
                                "method_id" => $id,
                                "amount" => (float) $amount,
                                "tender_entry_id" => null,
                            ];
                        }
                    }
                }
                $route_customer = $request->route_customer;
                $paid  = $request->request_type === 'send_request';
                $products = $allInventroy;
                $attending_cashier = Auth::id();


                //            $attached_sales_ids = $request->attached_sales;
                //            if($attached_sales_ids){
                //                $sales_ids =   explode(',', $attached_sales_ids);
                //                $sales = WaPosCashSales::whereIn('id', $sales_ids)->get();
                //            }
//                if (isset($permission['pos-cash-sales___process-bank-overpayment'])) {
//                    $attending_cashier = $parent->user_id;
//                }

                return PosCashSaleService::recordSale($items, Auth::id(), $products,$new_sales_no, $route_customer, $payment_methods, $paid, $attending_cashier);
            });
            if ($check) {
                $user = Auth::user();
                $waiting = null;
                $displayDispatch = null;
                $dispatch = null;
                if ($request->request_type == 'mpesa') {
                    /*Initiste STK*/
                    $payment_method = $request->mpesa_method_id;
                    $service  = new PosPaymentService();
                    $response = $service->initiatePayment($check, $check->total, $payment_method, $request->mpesa_number);
                    $data = $response->getData(true);
                    if ($data['results'] != 1) {
                        $message = 'Error sending STK push, Try Again Later.';
                        return response()->json(['result' => -2, 'message' => $message]);
                    } else {
                        $message = 'STK Push Sent Successfully.';
                        $location = route('pos-cash-sales.invoice_print', base64_encode($check->id));
                        $dispatch = route('pos-cash-sales.dispatch-slip', $check->id);
                        $urls = [
                            'receipt' => $location,
                            'dispatch' => $dispatch
                        ];
                        return response()->json([
                            'result' => 2,
                            'message' => $message,
                            'sales_id' => $check->id,
                            'urls' => $urls,
                        ]);
                    }
                }
                if ($request->request_type == 'send_request') {
                    $message = 'Sales processed successfully.';
                    if (isset($permission[$pmodule . '___print']) || $permission == 'superadmin') {
                        $requestty = 'send_request';
                        $location = route('pos-cash-sales.invoice_print', base64_encode($check->id));
                        $waiting = route('pos-cash-sales.waiting-slip', $check->id);
                        $data = WaPosCashSales::with([
                            'items' => function ($query) {
                                $query->where('qty', '>', 0);
                            },
                            'user',
                            'items.item',
                            'items.item.pack_size',
                            'items.location',
                            'items.dispatch_by',
                            'items.item.unitofmeasures'
                        ])->where('id', $check->id)
                            ->first();
                        $groupedItems = $data->items->filter(function ($item) use ($user) {
                            $binData = $item->item->getBinData($user->wa_location_and_store_id);
                            return $binData && $binData->is_display == 0;
                        })->groupBy(function ($item) use ($user) {
                            return $item->item->getBinData($user->wa_location_and_store_id)->title;
                        });
                        $data->setRelation('items', $groupedItems);
                        if ($data->items->count() > 0) {
                            $dispatch = route('pos-cash-sales.dispatch-slip', $check->id);
                        }
                        $data = WaPosCashSales::with([
                            'items' => function ($query) {
                                $query->where('qty', '>', 0);
                            },
                            'user',
                            'items.item',
                            'items.item.pack_size',
                            'items.location',
                            'items.dispatch_by',
                            'items.item.unitofmeasures'
                        ])->where('id', $check->id)
                            ->first();
                        $groupedItems = $data->items->filter(function ($item) use ($user) {
                            $binData = $item->item->getBinData($user->wa_location_and_store_id);
                            return $binData && $binData->is_display == 1;
                        })->groupBy(function ($item) use ($user) {
                            return $item->item->getBinData($user->wa_location_and_store_id)->title;
                        });
                        $data->setRelation('items', $groupedItems);
                        if ($data->items->count() > 0) {
                            $displayDispatch = route('pos-cash-sales.display.dispatch-slip', $check->id);
                        }
                    } else {
                        $requestty = 'save';
                        $location = route('pos-cash-sales.index');
                    }
                } else {
                    $message = 'Sales Saved successfully.';
                    $requestty = 'save';
                    $location = route('pos-cash-sales.invoice_print', base64_encode($check->id));
                }
                return response()->json([
                    'result' => 1,
                    'dispatch' => $dispatch,
                    'displayDispatch' => $displayDispatch,
                    'waiting' => $waiting,
                    'message' => $message,
                    'location' => $location,
                    'requestty' => $requestty,
                    'order_id'=>$check->id
                ]);
            }
            return response()->json(['result' => -1, 'message' => 'Something went wrong']);
        } catch (\Exception $e) {
            // Catch any unexpected exceptions
            return response()->json([
                'result' => -1,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    private static function generateNewSalesNumber(): string
    {
        DB::beginTransaction();
        try {
            $series_module = WaNumerSeriesCode::where('module', 'CASH_SALES')
                ->lockForUpdate()
                ->first();

            $lastNumberUsed = $series_module->last_number_used;
            $newNumber = (int)$lastNumberUsed + 1;
            $sales_no = $series_module->code . '-' . str_pad($newNumber, 5, "0", STR_PAD_LEFT);

            $series_module->update(['last_number_used' => $newNumber]);
            DB::commit();
            return $sales_no;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    public function edit($id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        $cashier = User::find(Auth::id());
        $cashDropsQuery = DB::table('cash_drop_transactions')
            ->select(
                DB::raw('SUM(amount) as total_drops'),
                DB::raw('COUNT(CASE WHEN cash_drop_transactions.bank_receipt_number IS NULL THEN 1 END) as unbanked')
            )
            ->whereDate('cash_drop_transactions.created_at', today()->toDateString())
            ->where('cashier_id', $cashier->id)
            ->first();

        $cashier_sales_id = WaPosCashSales::where('status', 'Completed')
            ->where('attending_cashier', $cashier->id)
            ->pluck('id')->toArray();
        $cash_sale =  DB::table('wa_pos_cash_sales_payments')
            ->join('payment_methods', 'wa_pos_cash_sales_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereIn('wa_pos_cash_sales_payments.wa_pos_cash_sales_id', $cashier_sales_id)
            ->where('payment_methods.is_cash', true)
            ->whereDate('wa_pos_cash_sales_payments.created_at', today()->toDateString())
            ->select(DB::raw('SUM(wa_pos_cash_sales_payments.amount) as cash_total'))
            ->first();
        $cash_at_hand = $cash_sale->cash_total - $cashDropsQuery->total_drops;
        $selling_allowance =  getLoggeduserProfile()->drop_limit - $cash_at_hand;
        if (getLoggeduserProfile()->role_id  != 1 && getLoggeduserProfile()->drop_limit <= $cash_at_hand) {
            Session::flash('warning', 'Drop cash to proceed.');
            return redirect()->back();
        }
//        if (getLoggeduserProfile()->role_id  != 1 && $cashDropsQuery->unbanked != 0) {
//            Session::flash('warning', 'Your have been blocked from selling, till your last Drop is banked.');
//            return redirect()->back();
//        }

        if (isset($permission[$pmodule . '___edit']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            $getLoggeduserProfile = getLoggeduserProfile();
            $data = WaPosCashSales::with(['items', 'items.tax_manager', 'user', 'items.item', 'items.item.getAllFromStockMoves' => function ($w) use ($getLoggeduserProfile) {
                $w->where('wa_location_and_store_id', $getLoggeduserProfile->wa_location_and_store_id);
            }, 'items.item.pack_size', 'items.location'])->where('status', 'PENDING')->where('id', $id)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }

            $can_override = can('super-edit', 'pos-cash-sales');

//            if ($data->attending_cashier != null && $permission != 'superadmin' && Auth::id() != $data->attending_cashier && !$can_override) {
//                Session::flash('warning', 'Order is being attended by another cashier.');
//                return redirect()->back();
//            }

            $editPermission = '';
            if (!isset($permission[$pmodule . '___edit-values']) && $permission != 'superadmin') {
                $editPermission = 'readonly';
            }
            $paymentMethod = PaymentMethod::join('wa_chart_of_accounts_branches as branches', 'payment_methods.gl_account_id', '=', 'branches.wa_chart_of_account_id')
                ->where('branches.restaurant_id', Auth::user()->restaurant_id)
                ->where('payment_methods.use_in_pos', true)
                ->select(['payment_methods.*'])
                ->get();
            //            if (getLoggeduserProfile()->role_id  !=1)

            return view('admin.pos_cash_sales.edit', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'data', 'editPermission', 'paymentMethod', 'selling_allowance'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }
    public function update(Request $request, $id)
    {
        if (!$request->ajax()) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        if (!isset($permission[$pmodule . '___edit']) && $permission != 'superadmin') {
            return response()->json([
                'result' => -1,
                'message' => 'Restricted:You Dont have permissions'
            ]);
        }
        $validation = \Validator::make($request->all(), [
            'item_id' => 'array',
            'item_id.*' => 'required|exists:wa_inventory_items,id',
            'item_quantity.*' => 'required|min:0|numeric',
            'item_selling_price.*' => 'required|min:1|numeric',
            'item_vat.*' => 'required|exists:tax_managers,id',
            'item_discount_per.*' => 'nullable|min:0|numeric',
            //            'time'=>'required',
            'id' => 'required|exists:wa_pos_cash_sales,id',
            //            'route_customer'=>'required|exists:wa_route_customers,id',
            'payment_amount' => 'required|array',
            'request_type' => 'required|in:send_request,save,mpesa'
        ], [
            'item_discount_per.*.min' => 'Discount must be greater than or equal to 0',
            'item_quantity.*.min' => 'Quantity must be greater than or equal to 1',
            'item_selling_price.*.min' => 'Selling Price must be greater than or equal to 1',
        ], [
            'item_id.*' => 'Item',
            'item_quantity.*' => 'Quantity',
            'item_selling_price.*' => 'Price',
            'item_discount_per.*' => 'Discount',
            'item_vat.*' => 'Vat',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ]);
        }
        $errors = [];
        if ($request->request_type == 'send_request') {
            $errorpp = 0;
            foreach ($request->payment_amount as $key => $value) {
                if ($value != NULL || $value != '') {
                    $value = (float)$value;
                    if ($value <= 0) {
                        $errors['payment_amount' . $key][] = 'Invalid Payment';
                    }
                    $errorpp = 1;
                }
            }
            if ($errorpp == 0) {
                $errors['payment_amount.1'][] = 'Invalid Payment';
            }
        }
        if (count($errors) > 0) {
            return response()->json([
                'result' => 0,
                'errors' => $errors
            ]);
        }
        $allInventroy = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
        ])->with(['getAllFromStockMoves', 'getInventoryCategoryDetail', 'getInventoryCategoryDetail.getWIPGlDetail', 'getInventoryCategoryDetail.getStockGlDetail', 'getInventoryCategoryDetail.getIssueGlDetail'])->whereIn('id', $request->item_id)->get();
        if (count($allInventroy) == 0) {
            return response()->json([
                'result' => -1,
                'message' => 'Inventroy Items is required'
            ]);
        }
        $total = 0;

        foreach ($allInventroy as $key => $value) {

            if (!$request->item_selling_price[$value->id] || $value->standard_cost > $request->item_selling_price[$value->id]) {
                return response()->json([
                    'result' => 0,
                    'errors' => ['item_selling_price.' . $value->id => ['Selling price must be greater than or equal to standard cost']]
                ]);
            }


            $itemBin = @$value->getBinData(getLoggeduserProfile()->wa_location_and_store_id)->id;
            if ($itemBin == null) {
                return response()->json([
                    'result' => 0,
                    'errors' => ['item_id.' . $value->id => ['This Product is not assigned to a bin']]
                ]);
            }
            $qoh = WaStockMove::where('stock_id_code', $value->stock_id_code)->where('wa_location_and_store_id', getLoggeduserProfile()->wa_location_and_store_id)->sum('qauntity');
            if ($qoh < $request->item_quantity[$value->id]) {

                return response()->json([
                    'result' => 0,
                    'errors' => ['item_quantity.' . $value->id => ['Quantity cannot be greater than balance stock']]
                ]);
            }
            if ($value->block_this == 1) {
                return response()->json([
                    'result' => 0,
                    'errors' => ['item_id.' . $value->id => ['The product has been blocked from sale due to a change in standard cost']]
                ]);
            }
            $number = $request->item_quantity[$value->id];
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

            $sum = ($request->item_selling_price[$value->id] * $request->item_quantity[$value->id]);
            // $disAmount = ($sum * @$request->item_discount_per[$value->id]) / 100;
            $disAmount = $request->item_discount[$value->id];
            $total += ($sum - $disAmount);
        }
        $parent = WaPosCashSales::where('id', $request->id)->first();
        if (!$parent || $parent->status != 'PENDING') {
            return response()->json(['result' => -1, 'message' => 'Order Not Found In Pending Orders!']);
        }
        $check = DB::transaction(function () use ($allInventroy, $request, $parent) {
            $items = [];
            foreach ($request->item_id as $id => $itemId) {
                $items[] = [
                    "item_id" => (int) $itemId,
                    "item_quantity" => (float) $request->item_quantity[$id],
                    "item_discount_amount" => (float) $request->item_discount[$id],
                ];
            }
            $payment_methods = [];
            foreach ($request->payment_amount as $id => $amount) {
                if (!is_null($amount)) {
                    $payment_remarks = isset($request->payment_remarks[$id]) ? $request->payment_remarks[$id] : null;
                    if ($payment_remarks) {
                        // Split the payment_remarks by commas to separate each tender entry
                        $entries = explode(',', $payment_remarks);

                        // Loop through each entry and split by hyphen to get the tender_entry_id and amount
                        foreach ($entries as $entry) {
                            // Ensure there is a hyphen before trying to split
                            if (str_contains($entry, '-')) {
                                list($tender_entry_id, $amount) = explode('-', $entry);

                                $payment_methods[] = [
                                    "method_id" => $id,
                                    "amount" => (float) $amount,
                                    "tender_entry_id" => (int) $tender_entry_id,
                                ];
                            }
                        }
                    } else {
                        $payment_methods[] = [
                            "method_id" => $id,
                            "amount" => (float) $amount,
                            "tender_entry_id" => isset($request->payment_remarks[$id]) ? (int) $request->payment_remarks[$id] : null,
                        ];
                    }
                }
            }
            $route_customer = $parent->wa_route_customer_id;
            $paid  = $request->request_type === 'send_request';
            $products = $allInventroy;
            $attending_cashier = Auth::id();
            if (isset($permission['pos-cash-sales___process-bank-overpayment'])) {
                $attending_cashier = $parent->user_id;
            }

            $cashier = $parent->user_id;
            return PosCashSaleService::recordSale($items, $cashier, $products,'', $route_customer, $payment_methods, $paid, $attending_cashier, $request->id);
        });
        if ($check) {
            $waiting = null;
            $dispatch = null;
            if ($request->request_type == 'mpesa') {
                /*Initiste STK*/
                $payment_method = $request->mpesa_method_id;
                $service  = new PosPaymentService();
                $response = $service->initiatePayment($check, $check->total, $payment_method, $request->mpesa_number);
                $data = $response->getData(true);
                if ($data['results'] != 1) {
                    $message = 'Error sending STK push, Try Again Later.';
                    return response()->json(['result' => -2, 'message' => $message]);
                } else {
                    $message = 'STK Push Sent Successfully.';
                    $location = route('pos-cash-sales.invoice_print', base64_encode($check->id));
                    $dispatch = route('pos-cash-sales.dispatch-slip', $check->id);
                    $urls = [
                        'receipt' => $location,
                        'dispatch' => $dispatch
                    ];
                    return response()->json([
                        'result' => 2,
                        'message' => $message,
                        'sales_id' => $check->id,
                        'urls' => $urls,
                    ]);
                }
            }
            if ($request->request_type == 'send_request') {
                $message = 'Sales processed successfully.';
                if (isset($permission[$pmodule . '___print']) || $permission == 'superadmin') {
                    $requestty = 'send_request';
                    $location = route('pos-cash-sales.invoice_print', base64_encode($check->id));
                    $waiting = route('pos-cash-sales.waiting-slip', $check->id);
                    $dispatch = route('pos-cash-sales.dispatch-slip', $check->id);
                } else {
                    $requestty = 'save';
                    $location = route('pos-cash-sales.index');
                }
            } else {
                $message = 'Sales Saved successfully.';
                $requestty = 'save';
                $location = route('pos-cash-sales.invoice_print', base64_encode($check->id));
            }

            return response()->json([
                'result' => 1,
                'dispatch' => $dispatch,
                'message' => $message,
                'location' => $location,
                'requestty' => $requestty,
                'waiting' => $waiting,
                'order_id'=>$check->id
            ]);
        }
        return response()->json(['result' => -1, 'message' => 'Something went wrong']);
    }
    public function dispatch_pos(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = 'dispatch';
        $user = getLoggeduserProfile();
        $title = 'Dispatch Bin';
        $breadcum = [$title => ''];
        $bins = WaUnitOfMeasure::pluck('title', 'id');
        $status = 'dispatching';

        if ($user->role_id == 1) {
            $bin = (int) $request->bin_id;
        } else {
            $bin = $user->wa_unit_of_measures_id;
        }

        if (request()->wantsJson()) {

            $startDate = $request->from ?? now()->startOfDay();
            $endDate = Carbon::parse($request->to)->endOfDay()  ?? now();
            $query = WaPosCashSales::with(['items' => function ($query) use ($bin, $status) {
                $query->with('item')
                    ->whereHas('item', function ($query) use ($bin) {
                        $query->whereHas('bin_locations',  function ($t) use ($bin) {
                            $t->where('uom_id',  $bin);
                        });
                    });
            }])
                ->where('status', '=', 'completed')
                ->whereHas('items', function ($q) use ($bin) {
                    $q->whereHas('item', function ($k) use ($bin) {
                        $k->whereHas('bin_locations',  function ($t) use ($bin) {
                            $t->where('uom_id',  $bin);
                        });
                    });
                })
                ->whereHas('items', function ($q) use ($bin, $status) {
                    $q->whereHas('dispatch', function ($k) use ($bin, $status) {
                        $k->where('wa_unit_of_measure_id',  $bin)
                            ->where('status', '=', $status);
                    });
                })
                ->whereBetween('created_at', [$startDate, $endDate])
                ->withCount(['items' => function ($q) use ($bin) {
                    $q->whereHas('item', function ($k) use ($bin) {
                        $k->whereHas('bin_locations',  function ($t) use ($bin) {
                            $t->where('uom_id',  $bin);
                        });
                    });
                }])
                ->latest();
            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->format('d/m/Y, H:i:s');
                })
                ->toJson();
        }

        return view('admin.pos_cash_sales.dispatch', compact('user', 'bins', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
    }

    public function process_dispatch(Request $request, $id)
    {


        if (!$request->ajax()) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        $request->validate([
            'itemQuantities' => 'required|array',
            'itemQuantities.*.itemId' => 'required',
        ]);
        DB::transaction(function () use ($id, $request) {
            $poscashSale = WaPosCashSales::with('items')->find($id);
            $ids = [];
            foreach ($request->itemQuantities as $itemQuantity) {
                $ids[] = $itemQuantity['itemId'];
            }
            $dispatchs = WaPosCashSalesDispatch::whereIn('pos_sales_item_id', $ids)->get();

            $count = 0;

            foreach ($dispatchs as $dispatch) {
                $d = $poscashSale->items->where('id', $dispatch->pos_sales_item_id)->first();
                $quantity = $d->qty;
                $dispatch->update([
                    'dispatched_time' => now(),
                    'dispatched_by' => getLoggeduserProfile()->id,
                    'dispatch_quantity' => $quantity,
                    'status' => CashSaleDispatchStatus::dispatched,
                ]);
                $count++;
            }
            /*check if all bins have dispatched*/

            $remaining = WaPosCashSalesDispatch::where('pos_sales_id', $poscashSale->id)
                ->where('status', 'dispatching')->count();

            //            if ($remaining == 0){
            //                $poscashSale->update([
            //                    'status'=>'Ready'
            //                ]);
            //            }


        });

        return response()->json([
            'status' => true,
            'message' => 'Dispatched Successfully',
        ], 200);
    }

    public function customerView(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = 'dispatch-progress';
        $user = User::find(getLoggeduserProfile()->id);
        $title = 'Dispatch Progress ';
        $breadcum = [$title => ''];
        $branches = Restaurant::all();

        if ($user->role_id != 1) {
            $branchIds = DB::table('user_branches')
                ->where('user_id', $user->id)
                ->pluck('restaurant_id')
                ->toArray();
            $branches = Restaurant::where('id', Auth::user()->restaurant_id)->get();
        }

        if (request()->wantsJson()) {

            $startDate = $request->from ?? now()->startOfDay();
            $endDate = Carbon::parse($request->to)->endOfDay()  ?? now();

            $lastDispatchTimeSub = WaPosCashSalesDispatch::query()
                ->select('pos_sales_id')
                ->selectRaw('MAX(dispatched_time) as last_dispatch_time')
                ->where('status', '=', 'dispatched')
                ->groupBy('pos_sales_id');

            $query = WaPosCashSales::query()
                ->select([
                    'wa_pos_cash_sales.*',
                    'last_dispatch.last_dispatch_time',
                    \DB::raw('(SELECT count(DISTINCT wa_unit_of_measure_id) 
                    FROM wa_pos_cash_sales_dispatch 
                    WHERE wa_pos_cash_sales_dispatch.pos_sales_id = wa_pos_cash_sales.id) as bins_count'),

                    \DB::raw('(SELECT count(DISTINCT wa_unit_of_measure_id)
                    FROM wa_pos_cash_sales_dispatch
                    WHERE wa_pos_cash_sales_dispatch.pos_sales_id = wa_pos_cash_sales.id
                    AND wa_pos_cash_sales_dispatch.status = "dispatched") as bins_count_dispatched'),

                    \DB::raw('(SELECT count(DISTINCT wa_unit_of_measure_id)
                    FROM wa_pos_cash_sales_dispatch
                    WHERE wa_pos_cash_sales_dispatch.pos_sales_id = wa_pos_cash_sales.id
                    AND wa_pos_cash_sales_dispatch.status = "collected") as bins_count_collected'),

                    //                    \DB::raw('(SELECT count(DISTINCT wa_unit_of_measure_id)
                    //                       FROM wa_pos_cash_sales_dispatch
                    //                       WHERE wa_pos_cash_sales_dispatch.pos_sales_id = wa_pos_cash_sales.id
                    //                       AND wa_pos_cash_sales_dispatch.status IN ("dispatched", "collected")) as bins_count_dispatched'),


                    \DB::raw('(SELECT GROUP_CONCAT(DISTINCT wa_unit_of_measures.title SEPARATOR ", ") 
                    FROM wa_pos_cash_sales_dispatch 
                    JOIN wa_unit_of_measures 
                    ON wa_pos_cash_sales_dispatch.wa_unit_of_measure_id = wa_unit_of_measures.id 
                    WHERE wa_pos_cash_sales_dispatch.pos_sales_id = wa_pos_cash_sales.id 
                    AND wa_pos_cash_sales_dispatch.status = "dispatching") as pending_bins'),
                    \DB::raw("(SELECT users.name 
                        FROM wa_pos_cash_sales as cash_sales
                        LEFT JOIN users ON cash_sales.dipatched_by = users.id
                        WHERE cash_sales.id = wa_pos_cash_sales.id
                    ) AS dispatcher")
                ])
                ->leftJoinSub($lastDispatchTimeSub, 'last_dispatch', 'last_dispatch.pos_sales_id', 'wa_pos_cash_sales.id')
                ->with([
                    'items' => function ($query) {
                        $query->with('item');
                    }
                ])
                ->when($request->status, function ($q) use ($request) {
                    $q->whereHas('items', function ($query) use ($request) {
                        $query->whereHas('dispatch', function ($subQuery) use ($request) {
                            $subQuery->where('status', $request->status);
                        });
                    });
                })
                ->where('wa_pos_cash_sales.status', 'Completed');

            if ($request->branch) {
                $query = $query->where('wa_pos_cash_sales.branch_id', $request->branch);
            } else {
                if ($user->role_id != 1) {
                    $query = $query->whereIn('wa_pos_cash_sales.branch_id', $branchIds);
                }
            }
            $query =  $query->whereBetween('wa_pos_cash_sales.created_at', [$startDate, $endDate])
                ->withCount('items')
                ->groupBy('wa_pos_cash_sales.id')
                ->latest();
            $dataTable = DataTables::eloquent($query)
                ->addIndexColumn();

            if ($request->status && $request->status == 'collected') {
                $dataTable = $dataTable->addColumn('state', 'Collected');
            } elseif ($request->status && $request->status == 'dispatched') {
                $dataTable = $dataTable->addColumn('state', 'Dispatched');
            } else {
                $dataTable = $dataTable->addColumn('state', 'Dispatching');
            }

            $dataTable = $dataTable->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y, H:i:s');
            })->addColumn('action', function ($row) use ($request) {
                return ($row->is_suspended != 1)
                    ? '<a href="#" class="remove-from-screen" data-id="' . $row->id . '"><i class="fas fa-tv" title="remove from dispatch screen"></i></a>'
                    : '';
            })
                ->addColumn('age', function ($row) {
                    if ($row->last_dispatch_time != null) {
                        $lastDispatch = Carbon::parse($row->last_dispatch_time);
                        $paidAt = Carbon::parse($row->paid_at);

                        $diffInMinutes = $paidAt->diffInMinutes($lastDispatch);

                        // Calculate hours and minutes
                        $hours = floor($diffInMinutes / 60);
                        $minutes = $diffInMinutes % 60;

                        // Format the difference
                        $formattedDifference = ($hours > 0 ? "{$hours} hrs " : '') . "{$minutes} Mins";

                        return $formattedDifference;
                    }


                    return Carbon::parse($row->paid_at)->diffForHumans(['parts' => 2, 'short' => true]) . ' ';
                })
                ->toJson();
            return $dataTable;
        }
        return view('admin.pos_cash_sales.customer-view', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'branches'));
    }
    public function removeFromScreen($id)
    {
        $sale = WaPosCashSales::find($id);
        $sale->is_suspended = 1;
        $sale->save();
        return response()->json(['success' => 'removed successfully'], 200);
    }
    public function customerViewUnguarded(Request $request)
    {
        /*wat Time*/
        $delay_time = optional(Setting::where('name', 'DISPATCH_CALLOUT_DELAY_TIME')->first())->description ?? 60;


        $startDate = $request->from ?? now()->startOfDay();
        $endDate = Carbon::parse($request->to)->endOfDay()  ?? now();
        $branch = $request->restaurant_id;
        $binSub = WaPosCashSalesDispatch::query()
            ->select('pos_sales_id')
            ->selectRaw('count(DISTINCT wa_unit_of_measure_id) as bins_count')
            ->groupBy('pos_sales_id');
        $binSub2 = WaPosCashSalesDispatch::query()
            ->select('pos_sales_id', 'dispatched_time')
            ->where('status', '=', 'dispatched')
            ->selectRaw('count(DISTINCT wa_unit_of_measure_id) as bins_count_dispatched')
            ->groupBy('pos_sales_id');
        $binsPending = WaPosCashSalesDispatch::query()
            ->select('pos_sales_id', \DB::raw("GROUP_CONCAT(DISTINCT wa_unit_of_measures.title ORDER BY wa_unit_of_measures.title SEPARATOR ', ') as pending_bin_titles"))
            ->leftJoin('wa_unit_of_measures', 'wa_pos_cash_sales_dispatch.wa_unit_of_measure_id', '=', 'wa_unit_of_measures.id') // Join with bins table
            ->where('status', '!=', 'dispatched')
            ->groupBy('pos_sales_id');


        $lastDispatchTimeSub = WaPosCashSalesDispatch::query()
            ->select('pos_sales_id')
            ->selectRaw('MAX(dispatched_time) as last_dispatch_time')
            ->where('status', '=', 'dispatched')
            ->groupBy('pos_sales_id');

        $query = WaPosCashSales::select([
            'wa_pos_cash_sales.*',
            'bins.bins_count',
            'disp.bins_count_dispatched',
            'last_dispatch.last_dispatch_time',
            'pending_bins.pending_bin_titles',
            \DB::raw("CASE 
    WHEN bins.bins_count = disp.bins_count_dispatched THEN 'Ready' 
    ELSE 'Dispatching' 
    END as order_status")
        ])
            ->with(['items' => function ($query) {
                $query->with('item');
            }])
            ->leftJoinSub($binSub, 'bins', 'bins.pos_sales_id', 'wa_pos_cash_sales.id')
            ->leftJoinSub($binSub2, 'disp', 'disp.pos_sales_id', 'wa_pos_cash_sales.id')
            ->leftJoinSub($lastDispatchTimeSub, 'last_dispatch', 'last_dispatch.pos_sales_id', 'wa_pos_cash_sales.id')
            ->leftJoinSub($binsPending, 'pending_bins', 'pending_bins.pos_sales_id', 'wa_pos_cash_sales.id')
            ->where('status', '=', 'Completed')
            ->where('branch_id', $branch)
            ->whereHas('items', function ($q) {
                $q->whereHas('dispatch', function ($k) {
                    $k->where('status', '!=', 'collected');
                });
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('wa_pos_cash_sales.is_suspended', 0)
            ->whereRaw('
    (last_dispatch.last_dispatch_time IS NOT NULL 
     AND TIMESTAMPDIFF(MINUTE, last_dispatch.last_dispatch_time, NOW()) <= 5)
')

            ->withCount([
                'items',
            ])
            ->groupBy('wa_pos_cash_sales.id')
            ->orderByRaw("
                CASE 
                    WHEN bins.bins_count = disp.bins_count_dispatched THEN 1 
                    ELSE 2 
                END");

        return DataTables::eloquent($query)
            ->addColumn('order_status', function ($row) use ($delay_time) {
                $state = 'Dispatching';
                $class = 'btn btn-warning'; // Default class for 'Dispatching' state

                if ($row->bins_count == $row->bins_count_dispatched) {
                    if (Carbon::parse($row->last_dispatch_time)->lt(Carbon::now()->subSeconds($delay_time))) {
                        $state = 'Ready';
                        $class = 'btn btn-success';
                    }
                }
                return $state;
            })
            ->addColumn('state', function ($row) {
                $state = 'Dispatching';
                $class = 'btn btn-warning'; // Default class for 'Dispatching' state

                if ($row->bins_count == $row->bins_count_dispatched) {
                    if (Carbon::parse($row->last_dispatch_time)->lt(Carbon::now()->subMinutes(1))) {
                        $state = 'Ready';
                        $class = 'btn btn-success';
                    }
                }
                return new HtmlString("<button type=\"button\" class=\"$class\">$state</button>");
            })
            ->addColumn('time', function ($row) {
                return Carbon::parse($row->paid_at)->format('H:i A');
            })
            ->editColumn('bins_count_dispatched', function ($row) {
                $count = $row->bins_count_dispatched;
                $bins = $row->bins_count;

                if ($count < 1) {
                    $count = 0;
                }
                return new HtmlString("<div class='dispatching-bins'><span>$count / $bins</span> </div>");
            })
            ->editColumn('customer', function ($row) {
                return Str::upper($row->customer);
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d/m/Y, H:i:s');
            })
            ->toJson();
    }

    public function dispatch_log(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = 'dispatch-logs';
        $user = getLoggeduserProfile();
        $title = 'Dispatch Bin Logs';
        $breadcum = [$title => ''];
        $bins = WaUnitOfMeasure::pluck('title', 'id');
        $status = 'dispatching';

        $bin = $user->wa_unit_of_measures_id;
        if (request()->wantsJson()) {

            $startDate = $request->from ?? now()->startOfDay();
            $endDate = Carbon::parse($request->to)->endOfDay()  ?? now();
            $query = WaPosCashSalesDispatch::query()
                ->with('dispatch_user')
                ->with('bin')
                ->with('cashSaleItem', function ($q) {
                    $q->with('item', 'parent');
                })
                ->when($user->role_id != 1, function ($q) use ($bin) {
                    return $q->where('wa_unit_of_measure_id', $bin);
                })
                ->when($request->bin_id, function ($q) use ($request) {
                    return $q->where('wa_unit_of_measure_id', $request->bin_id);
                })
                ->where('status', '!=', 'dispatching')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('wa_pos_cash_sales_dispatch.pos_sales_id',  'wa_pos_cash_sales_dispatch.wa_unit_of_measure_id')
                ->latest();

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->dispatched_time)->format('d/m/Y, H:i:s');
                })
                ->addColumn('action', function ($row) {
                    $url = route('pos-cash-sales.dispatch-logs.details', [
                        'pos_sales_id' => $row->pos_sales_id,
                        'wa_unit_of_measures_id' => $row->wa_unit_of_measure_id
                    ]);
                    return '<a href="' . $url . '" title="View" target="_blank"><i class="fa fa-eye"></i></a>';
                })
                ->rawColumns(['action'])
                ->toJson();
        }
        return view('admin.pos_cash_sales.dispatch-log', compact('user', 'bins', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
    }
    public function dispatch_log_details($pos_sales_id, $wa_unit_of_measures_id)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = 'dispatch-logs';
        $title = 'Dispatch Bin Logs Details';
        $breadcum = [$title => ''];
        $sale = WaPosCashSales::with('buyer')->find($pos_sales_id);
        $bin = WaUnitOfMeasure::find($wa_unit_of_measures_id);


            $items = WaPosCashSalesDispatch::query()
                ->with('dispatch_user')
                ->with('bin')
                ->with('cashSaleItem', function ($q) {
                    $q->with('item', 'parent');
                })
                ->where('wa_pos_cash_sales_dispatch.pos_sales_id', $pos_sales_id)
                ->where('wa_pos_cash_sales_dispatch.wa_unit_of_measure_id', $wa_unit_of_measures_id)
                ->whereNotNull('dispatched_time')
                ->latest()->get();
        
        return view('admin.pos_cash_sales.dispatch-log-details', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'sale', 'bin', 'items'));
    }


    public function archive(Request $request, $id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = 'archive';
        //if (isset($permission[$pmodule.'___archive']) || $permission == 'superadmin') {
        $cashSale = WaPosCashSales::findOrFail($id);
        $cashSale->status = "Archived";
        $cashSale->save();
        Session::flash('success', 'Item Archived successfully.');
        return redirect()->back();

        /*} else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }*/
    }

    public function get_sales_list(Request $request)
    {

        $getLoggeduserProfile = getLoggeduserProfile();
        if ($request->type == "Cash Sales") {
            $data = WaPosCashSales::select(['id', 'sales_no as text'])->where(function ($w) use ($request) {
                if ($request->q) {
                    $w->where('sales_no', 'LIKE', '%' . $request->q . '%');
                }
            })->whereHas('items', function ($r) use ($getLoggeduserProfile) {
                $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id);
            })->where('status', 'Completed')->whereRaw(' DATE(created_at) =  "' . date('Y-m-d') . '"')
                ->orderBy('id', 'DESC')->get();
        } else {
            DB::enableQueryLog();
            $data = \App\Model\WaInternalRequisition::select(['id', 'requisition_no as text'])->where(function ($w) use ($request) {
                if ($request->q) {
                    $w->where('requisition_no', 'LIKE', '%' . $request->q . '%');
                }
                // $r->where('to_store_id',$getLoggeduserProfile->wa_location_and_store_id);
            })
                ->whereHas('getRelatedItem', function ($r) use ($getLoggeduserProfile) {
                    $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id);
                })->where('status', 'COMPLETED')
                ->where('route', 'sales invoice')
                ->whereRaw('DATE(created_at) =  "' . date('Y-m-d') . '"')
                ->orderBy('id', 'DESC')->get();
        }
        return response()->json($data);
    }

    public function cash_sales_data($request)
    {
        // echo '<pre>';
        // print_r($request->all());
        // echo '***********************';

        $getLoggeduserProfile = getLoggeduserProfile();
        $data = WaPosCashSales::with(['items', 'user', 'items.item', 'items.item.pack_size', 'items.dispatch_details'])->where('id', $request->id)->whereHas('items', function ($r) use ($getLoggeduserProfile) {
            $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id);
        })->where('status', 'Completed')->first();

        // pre($data);
        $reponse['data']['customer'] = '';
        $reponse['data']['sold_by'] = '';
        $reponse['data']['amount'] = '';
        $reponse['data']['quantity'] = '';
        $reponse['result'] = 0;
        if (!$data) {
            $reponse['message'] = 'Receipt Not found';
            return $reponse;
        }
        if ($data->items->where('is_dispatched', 0)->count() == 0) {
            $reponse['message'] = 'All Items already dispatched';
            return $reponse;
        }
        $reponse['result'] = 1;
        $reponse['items'] = view('admin.pos_cash_sales.dispatchitem')->with(['data' => $data, 'getLoggeduserProfile' => $getLoggeduserProfile])->render();
        $reponse['data']['customer'] = $data->customer;
        $reponse['data']['sold_by'] = @$data->user->name;
        $reponse['data']['sold_by_id'] = @$data->user->id;
        $reponse['data']['amount'] = @$data->items->sum('total');
        $reponse['data']['quantity'] = @$data->items->count();
        return $reponse;
    }


    public function sales_invoice_data($request)
    {
        $getLoggeduserProfile = getLoggeduserProfile();
        $data = \App\Model\WaInternalRequisition::with(['getRelatedItem', 'getRelatedItem.dispatch_details', 'getrelatedEmployee', 'getRelatedItem.getInventoryItemDetail', 'getRelatedItem.getInventoryItemDetail.pack_size'])->where('id', $request->id)
            ->whereHas('getRelatedItem', function ($r) use ($getLoggeduserProfile) {
                $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id);
            })->where('status', 'COMPLETED')
            ->first();
        $reponse['data']['customer'] = '';
        $reponse['data']['sold_by'] = '';
        $reponse['data']['amount'] = '';
        $reponse['data']['quantity'] = '';
        $reponse['result'] = 0;
        if (!$data) {
            $reponse['message'] = 'Receipt Not found';
            return $reponse;
        }
        if ($data->getRelatedItem->where('is_dispatched', 0)->count() == 0) {
            $reponse['message'] = 'All Items already dispatched';
            return $reponse;
        }
        $reponse['result'] = 1;
        $reponse['items'] = view('admin.pos_cash_sales.sales_invoice_data')->with(['data' => $data, 'getLoggeduserProfile' => $getLoggeduserProfile])->render();
        $reponse['data']['customer'] = $data->customer;
        $reponse['data']['sold_by'] = @$data->getrelatedEmployee->name;
        $reponse['data']['amount'] = @$data->getRelatedItem->sum('total_cost_with_vat');
        $reponse['data']['quantity'] = @$data->getRelatedItem->count();
        return $reponse;
    }

    public function get_sales_list_details(Request $request)
    {
        // pre($request->all());
        if ($request->type == "Cash Sales") {
            $reponse = $this->cash_sales_data($request);
        } else {
            $reponse = $this->sales_invoice_data($request);
        }
        return response()->json($reponse);
    }


    public function post_dispatch(Request $request)
    {


        if (!$request->ajax()) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        if (!isset($permission['dispatch-pos-invoice-sales___dispatch']) && $permission != 'superadmin') {
            return response()->json([
                'result' => -1,
                'message' => 'Restricted:You Don\'t have permissions'
            ]);
        }
        $validation = \Validator::make($request->all(), [
            'type' => 'required|in:Cash Sales,Sales Invoice',
            'store_qty_loaded' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ]);
        }

        // echo "test"; die;
        $item_arr = $request->inventory_item_id;
        $item_qty_arr = $request->item_qty;
        $getLoggeduserProfile = getLoggeduserProfile();


        // foreach($shift_data as $shift){
        foreach ($request->store_qty_loaded as $key => $loaded_qty) {

            $loaded_qty = ($loaded_qty != "") ? $loaded_qty : 0;
            $dispatchItem = new DispatchLoadedProducts();
            $dispatchItem->user_id = $getLoggeduserProfile->id;
            $dispatchItem->salesman_id = @$request->sold_by_id;
            $dispatchItem->document_no = $request->receipt_no;
            $dispatchItem->store_location_id = $getLoggeduserProfile->wa_location_and_store_id;
            $dispatchItem->inventory_item_id = @$item_arr[$key];
            $dispatchItem->total_qty = @$item_qty_arr[$key];
            $dispatchItem->qty_loaded = @$loaded_qty;
            $dispatchItem->balance_qty = @($dispatchItem->total_qty - $dispatchItem->qty_loaded);
            $dispatchItem->save();
        }


        if ($request->type == 'Cash Sales') {
            return $this->cash_sales_dispatch($request);
        }
        if ($request->type == 'Sales Invoice') {
            return $this->sales_invoice_dispatch($request);
        }
        return response()->json([
            'result' => -1,
            'message' => 'Something went wrong',
        ]);
    }
    public function sales_invoice_dispatch($request)
    {
        // pre($request->all());

        $validation = \Validator::make($request->all(), [
            'receipt_no' => 'required|exists:wa_internal_requisitions,id',
            'time' => 'required',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:wa_internal_requisition_items,id',
            'store_qty_loaded.*' => 'required',
            'item_qty.*' => 'required|numeric',
            // 'disp_no'=>'required|unique:wa_internal_requisition_dispatch,desp_no'
        ], [
            // 'disp_no.unique'=>'This Disp No is in use, Refresh to get new one'
        ], ['item_id.*' => 'Item', 'item_qty.*' => 'Qty']);
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ]);
        }
        $getLoggeduserProfile = getLoggeduserProfile();
        $data = \App\Model\WaInternalRequisition::with(['getRelatedItem' => function ($r) use ($getLoggeduserProfile, $request) {
            $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id)->whereIn('id', $request->item_id);
        }, 'getRelatedItem.dispatch_details'])->where('id', $request->receipt_no)->whereHas('getRelatedItem', function ($r) use ($getLoggeduserProfile) {
            $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id);
        })->where('status', 'COMPLETED')->first();
        if (!$data) {
            return response()->json(['result' => 0, 'errors' => ['receipt_no' => ['Receipt Not found']]]);
        }
        if ($data->getRelatedItem->where('is_dispatched', 0)->count() == 0) {
            return response()->json(['result' => 0, 'errors' => ['receipt_no' => ['All Items already dispatched']]]);
        }
        $dispatchError = [];
        foreach ($data->getRelatedItem->where('is_dispatched', 0) as $item) {
            $quantity = $item->quantity - @$item->dispatch_details->sum('dispatch_quantity');
            if (!isset($request->item_qty[$item->id]) || $quantity < @$request->item_qty[$item->id] || @$request->item_qty[$item->id] <= 0) {
                $dispatchError['item_qty.' . $item->id] = ['Invalid quantity'];
            }
        }
        if (count($dispatchError) > 0) {
            return response()->json(['result' => 0, 'errors' => $dispatchError]);
        }
        $check = DB::transaction(function () use ($request, $data, $getLoggeduserProfile) {
            // $accountuingPeriod =  \App\Model\WaAccountingPeriod::where('is_current_period', '1')->first();
            $series_module = \App\Model\WaNumerSeriesCode::where('module', 'DISPATCH-CASH-SALES')->first();
            $sale_invoiceno = $request->disp_no = getCodeWithNumberSeries('DISPATCH-CASH-SALES');
            $dispatch = [];
            $ids = [];
            foreach ($data->getRelatedItem->where('is_dispatched', 0) as $positem) {
                $dispatch[] = [
                    'desp_no' => $sale_invoiceno,
                    'wa_internal_requisition_id' => $data->id,
                    'wa_internal_requisition_item_id' => $positem->id,
                    'dispatched_time' => date('Y-m-d') . ' ' . date('H:i:s', strtotime($request->time)),
                    'dispatched_by' => $getLoggeduserProfile->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'dispatch_quantity' => @$request->item_qty[$positem->id]
                ];
                $quantity = $positem->quantity - @$positem->dispatch_details->sum('dispatch_quantity');
                if ($quantity >= @$request->item_qty[$positem->id]) {
                    $ids[] = $positem->id;
                }
            }
            if (count($ids) > 0) {
                \App\Model\WaInternalRequisitionItem::whereIn('id', $ids)->update([
                    'is_dispatched' => 1,
                    'dispatched_by' => $getLoggeduserProfile->id,
                    'dispatched_time' => date('Y-m-d') . ' ' . date('H:i:s', strtotime($request->time)),
                    'dispatch_no' => $sale_invoiceno
                ]);
            }
            WaInternalRequisitionDispatch::insert($dispatch);
            updateUniqueNumberSeries('DISPATCH-CASH-SALES', $sale_invoiceno);
            return true;
        });
        if ($check) {
            return response()->json(['result' => 1, 'message' => 'Dispatch processed successfully.', 'location' => route('pos-cash-sales.dispatch')]);
        }
        return response()->json(['result' => -1, 'message' => 'Something went wrong']);
    }
    public function cash_sales_dispatch($request)
    {
        // pre($request->all());
        $validation = \Validator::make($request->all(), [
            'receipt_no' => 'required|exists:wa_pos_cash_sales,id',
            'time' => 'required',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:wa_pos_cash_sales_items,id',
            'item_qty.*' => 'required|numeric',
            'store_qty_loaded.*' => 'required',
            // 'disp_no'=>'required|unique:wa_pos_cash_sales_dispatch,desp_no'
        ], ['disp_no.unique' => 'This Disp No is in use, Refresh to get new one']);
        if ($validation->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validation->errors()
            ]);
        }
        $getLoggeduserProfile = getLoggeduserProfile();
        $data = WaPosCashSales::with(['items' => function ($r) use ($getLoggeduserProfile, $request) {
                    $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id)->whereIn('id', $request->item_id);
                }, 'user', 'items.item', 'items.item.pack_size', 'items.dispatch_details'])
                ->where('id', $request->receipt_no)
                ->whereHas('items', function ($r) use ($getLoggeduserProfile) {
                    $r->where('store_location_id', $getLoggeduserProfile->wa_location_and_store_id);
                })->where('status', 'Completed')
                ->first();
        if (!$data) {
            return response()->json(['result' => 0, 'errors' => ['receipt_no' => ['Receipt Not found']]]);
        }
        if ($data->items->where('is_dispatched', 0)->count() == 0) {
            return response()->json(['result' => 0, 'errors' => ['receipt_no' => ['All Items already dispatched']]]);
        }
        $dispatchError = [];
        foreach ($data->items->where('is_dispatched', 0) as $item) {
            $qty = $item->qty - @$item->dispatch_details->sum('dispatch_quantity');
            if (!isset($request->item_qty[$item->id]) || $qty < @$request->item_qty[$item->id] || @$request->item_qty[$item->id] <= 0) {
                $dispatchError['item_qty.' . $item->id] = ['Invalid quantity'];
            }
        }
        if (count($dispatchError) > 0) {
            return response()->json(['result' => 0, 'errors' => $dispatchError]);
        }
        $check = DB::transaction(function () use ($request, $data, $getLoggeduserProfile) {
            // $accountuingPeriod =  \App\Model\WaAccountingPeriod::where('is_current_period', '1')->first();
            $series_module = \App\Model\WaNumerSeriesCode::where('module', 'DISPATCH-CASH-SALES')->first();
            $sale_invoiceno = $request->disp_no = getCodeWithNumberSeries('DISPATCH-CASH-SALES');
            $dispatch = [];
            $ids = [];
            foreach ($data->items->where('is_dispatched', 0) as $positem) {
                $dispatch[] = [
                    'desp_no' => $sale_invoiceno,
                    'pos_sales_id' => $data->id,
                    'pos_sales_item_id' => $positem->id,
                    'dispatched_time' => date('Y-m-d') . ' ' . date('H:i:s', strtotime($request->time)),
                    'dispatched_by' => $getLoggeduserProfile->id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'dispatch_quantity' => @$request->item_qty[$positem->id]
                ];
                $qty = $positem->qty - @$positem->dispatch_details->sum('dispatch_quantity');
                if ($qty >= @$request->item_qty[$positem->id]) {
                    $ids[] = $positem->id;
                }
            }
            if (count($ids) > 0) {
                WaPosCashSalesItems::whereIn('id', $ids)->update([
                    'is_dispatched' => 1,
                    'dispatched_by' => $getLoggeduserProfile->id,
                    'dispatched_time' => date('Y-m-d') . ' ' . date('H:i:s', strtotime($request->time)),
                    'dispatch_no' => $sale_invoiceno
                ]);
            }
            WaPosCashSalesDispatch::insert($dispatch);
            updateUniqueNumberSeries('DISPATCH-CASH-SALES', $sale_invoiceno);
            return true;
        });
        if ($check) {
            return response()->json(['result' => 1, 'message' => 'Dispatch processed successfully.', 'location' => route('pos-cash-sales.dispatch')]);
        }
        return response()->json(['result' => -1, 'message' => 'Something went wrong']);
    }
    public function show($id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            $data = WaPosCashSales::with([
                'items' => function ($query) {
                    $query->where('qty', '>', 0);
                },
                'user',
                'items.item',
                'items.item.pack_size',
                'items.location',
                'items.dispatch_by'
            ])->where('id', $id)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            return view('admin.pos_cash_sales.show', compact('data', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function invoice_print($id)
    {
        \Log::info('invoice_print called', ['id' => $id]);
        try {
            $decodedId = base64_decode($id);
            \Log::info('Decoded ID', ['decodedId' => $decodedId]);
            $data = WaPosCashSales::with([
                'user',
                'items.item',
                'payment'
            ])->find($decodedId);
            if (!$data) {
                \Log::error('No sale found for decodedId', ['decodedId' => $decodedId]);
                return back()->with('error', 'Sale not found.');
            }
            // Get payment details
            $payments = \DB::table('wa_pos_cash_sales_payments')
                ->select(
                    'wa_pos_cash_sales_payments.*',
                    'payment_methods.title',
                    'payment_methods.is_cash',
                    'payment_providers.slug as payment_slug'
                )
                ->leftJoin('payment_methods', 'payment_methods.id', '=', 'wa_pos_cash_sales_payments.payment_method_id')
                ->leftJoin('payment_providers', 'payment_providers.id', '=', 'payment_methods.payment_provider_id')
                ->where('wa_pos_cash_sales_id', $data->id)
                ->get();
            return view('admin.pos_cash_sales_new.print', [
                'data' => $data,
                'payments' => $payments,
                'esd_details' => null,
                'title' => 'POS Cash Sales Invoice',
                'model' => 'pos-cash-sales',
            ]);
        } catch (\Exception $e) {
            \Log::error('Invoice Print Error: ' . $e->getMessage() . ' - Sale ID: ' . $id);
            \Log::error($e->getTraceAsString());
            return back()->with('error', 'Failed to generate invoice: ' . $e->getMessage());
        }
    }
    
    /**
     * Search for inventory items
     *
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

            $view = '<table class="table table-bordered table-hover" id="stock_inventory" style="
            display: block;
            right: auto !important;
            position: absolute;
            min-width: 400px;
            left: 0 !important;
            max-height: 350px;
            margin-top: 4px!important;
            overflow: auto;
            padding: 0;
            background:#fff;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none;  /* Internet Explorer 10+ */
            ">';
            $view .= "<thead>";
            $view .= '<tr>';
            $view .= '<th style="width:20%">Code</th>';
            $view .= '<th style="width:70%">Description</th>';
            $view .= '<th style="width:10%">QOH</th>';
            $view .= '<th style="width:10%">PRICE</th>';
            $view .= '</tr>';
            $view .= '</thead>';
            $view .= "<tbody>";
            foreach ($data as $key => $value) {
                $qoh = WaStockMove::where('wa_inventory_item_id', $value->id)
                    ->where('wa_location_and_store_id', $request->store_location_id)
                    ->sum('qauntity');
                $view .= '<tr onclick="fetchInventoryDetails(this)" ' . ($key == 0 ? 'class="SelectedLi"' : NULL) . ' data-id="' . $value->id . '" data-title="' . $value->title . '(' . $value->stock_id_code . ')">';
                $view .= '<td style="width:20%">' . $value->stock_id_code . '</td>';
                $view .= '<td style="width:70%">' . $value->title . '</td>';
                $view .= '<td style="width:10%">' . ($qoh ?? 0) . '</td>';
                $view .= '<td style="width:10%">' . (number_format($value->selling_price, 2)) . '</td>';

                $view .= '</tr>';
            }
            $view .= '</tbody>';
            $view .= '</table>';
            
            return response()->json([
                'view' => $view,
                'results' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in searchInventory: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'error' => 'An error occurred while searching inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportToPdf($id)
    {
        $decodedId = base64_decode($id);
        $data = WaPosCashSales::with([
            'user',
            'items.item',
            'payment'
        ])->find($decodedId);

        if (!$data) {
            return back()->with('error', 'Sale not found.');
        }

        $payments = \DB::table('wa_pos_cash_sales_payments')
            ->select(
                'wa_pos_cash_sales_payments.*',
                'payment_methods.title',
                'payment_methods.is_cash',
                'payment_providers.slug as payment_slug'
            )
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'wa_pos_cash_sales_payments.payment_method_id')
            ->leftJoin('payment_providers', 'payment_providers.id', '=', 'payment_methods.payment_provider_id')
            ->where('wa_pos_cash_sales_id', $data->id)
            ->get();

        $pdf = Pdf::loadView('admin.pos_cash_sales_test.print', [
            'data' => $data,
            'payments' => $payments,
            'esd_details' => null,
            'title' => 'POS Cash Sales Invoice',
            'model' => 'pos-cash-sales',
        ]);

        return $pdf->download('POS_Cash_Sale_Invoice_' . $data->sales_no . '.pdf');
    }

    /**
     * Display a listing of returned cash sales items.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function returned_cash_sales_list(Request $request)
    {
        $user = getLoggeduserProfile();
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = "Cash Sales Return";
        $model = 'pos-return-list';
        
        if (isset($permission[$pmodule . '___return-list']) || $permission == 'superadmin') {
            $breadcum = [$title => route($pmodule . '.index'), 'Listing' => ''];
            
            $data = WaPosCashSalesItems::select([
                '*',
                DB::RAW('SUM(return_quantity) as rtn_qty'),
                DB::RAW('SUM(return_quantity * selling_price) as rtn_total')
            ])
            ->with(['item', 'parent', 'parent.user', 'returned_by'])
            ->where('is_return', 1)
            ->where(function($w) use ($request, $permission, $user) {
                if ($request->input('start-date') && $request->input('end-date')) {
                    $w->whereBetween('return_date', [
                        $request->input('start-date') . ' 00:00:00',
                        $request->input('end-date') . " 23:59:59"
                    ]);
                }
                // Apply location filter if not superadmin
                if ($permission != 'superadmin' && isset($user->wa_location_and_store_id)) {
                    $w->where('store_location_id', $user->wa_location_and_store_id);
                }
            })
            ->orderBy('return_date', 'DESC')
            ->groupBy('return_grn')
            ->paginate(100);

            $esd_details = null;
            if ($data->isNotEmpty() && isset($data->first()->parent->sales_no)) {
                $esd_details = WaEsdDetails::where('invoice_number', $data->first()->parent->sales_no)->first();
            }

            return view('admin.pos_cash_sales.returned_cash_sales_list', compact(
                'data',
                'title',
                'model',
                'breadcum',
                'pmodule',
                'permission',
                'esd_details'
            ));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }
}
