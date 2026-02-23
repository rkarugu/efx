<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Grns\ApproveGrnReturn;
use App\Actions\Grns\CreateGrnReturn;
use App\Model\WaInventoryItemSupplierData;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Interfaces\SmsService;
use App\Model\WaPurchaseOrder;
use App\Model\WaPurchaseOrderItem;
use App\Model\WaDepartment;
use App\Model\WaInventoryItem;
use App\Model\WaReceivePurchaseOrder;
use App\Model\WaReceivePurchaseOrderItem;
use App\Model\WaGrn;
use App\Model\WaStockMove;
use App\Model\WaNumerSeriesCode;
use App\Model\WeightedAverageHistory;
use App\Model\WaAccountingPeriod;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Model\WaGlTran;
use App\Models\PriceTimeline;
use App\Model\TaxManager;
use App\Model\User;
use App\Model\WaInventoryPriceHistory;
use App\Model\WaLocationAndStore;
use App\Model\WaSuppTran;
use App\WaSupplierInvoice;
use App\WaSupplierInvoiceItem;
use DB;
use Illuminate\Support\Facades\Validator;
use App\ReturnedGrn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Session;

class ConfirmedReceiveOrderController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;
    private $path = "admin.receivepurchaseorders.confirmed_orders.";
    private $consts = [
        'Confirm' => 'Confirmed',
        'Process' => 'Processed',
        'Reject' => 'Rejected',
        'Pending' => 'Pending'
    ];
    public function __construct(protected SmsService $smsService)
    {
        $this->model = 'confirmed-receive-purchase-order';
        $this->title = 'Confirmed Receive Purchase Order';
        $this->pmodule = 'confirmed-receive-purchase-order';
    }

    public function index(Request $request)
    {
        try {
            $status = $this->consts[$request->status];
        } catch (\Throwable $th) {
            $status = $this->consts['Confirm'];
        }
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $user = getLoggeduserProfile();
        $user_location = $user->wa_location_and_store_id;
        $user_restaurant_id = WaLocationAndStore::where('id', $user_location)->pluck('wa_branch_id')->first();
        $preselect_location = null;
        $disable_select = false;
        if ($user->role_id != 1 && !isset($permission['maintain-items___view-per-branch'])) {
            $preselect_location = $user_location;
            $disable_select = true;
        }

        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $lists = WaReceivePurchaseOrder::query()
                ->select('wa_receive_purchase_orders.*')
                ->where('status', $status)
                ->whereHas('parent', function ($e) use ($request, $permission, $user, $user_restaurant_id) {
                    $e->where('is_hide', 'No');
                    if ($request->supplier) {
                        $e = $e->where('wa_supplier_id', $request->supplier);
                    }
                    // if ($permission != 'superadmin') {
                    //     $e = $e->where('restaurant_id', getLoggeduserProfile()->restaurant_id);
                    // }
                    if ($user->role_id != 1 && !isset($permission['maintain-items___view-per-branch'])) {
                        $e = $e->where('restaurant_id', $user_restaurant_id);
                    }
                })->with([
                    'parent.getrelatedEmployee',
                    'uom',
                    'getStoreLocation',
                    'parent.getSupplier',
                    'child_items'
                ]);

            if ($request->store) {
                $lists = $lists->where('wa_location_and_store_id', $request->store);
            }
            $lists = $lists->orderBy('id', 'desc')->get();

            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view($this->path . 'index', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'preselect_location', 'disable_select'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function show($id)
    {
        $row =  WaReceivePurchaseOrder::with([
            'child_items.parent.getInventoryItemDetail.getInventoryCategoryDetail',
            'child_items.parent.getInventoryItemDetail.getUnitOfMeausureDetail',
            'child_items.parent.getInventoryItemDetail.location',
            'parent.getRelatedAuthorizationPermissions.getExternalAuthorizerProfile'
        ])->whereId($id)->first();
        if ($row) {
            $title = 'View ' . $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Show' => ''];
            $model = $this->model;
            $pmodule = $this->pmodule;
            $permission =  $this->mypermissionsforAModule();
            return view('admin.receivepurchaseorders.confirmed_orders.show', compact('title', 'model', 'breadcum', 'row', 'permission', 'pmodule'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function create()
    {
        return QrCode::generate(
            'Hello, World!',
        );
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'approval_status' => 'required|in:Process,Reject',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }
            $receiveOrder = WaReceivePurchaseOrder::with(['child_items'])->whereId($id)->first();
            $receiveOrderItems = WaReceivePurchaseOrderItem::where('wa_receive_purchase_order_id', $receiveOrder->id)->with([
                'parent.getInventoryItemDetail.getAllFromStockMoves',
                'parent.getInventoryItemDetail.getInventoryCategoryDetail.getStockGlDetail'
            ])->get();
            foreach ($receiveOrderItems as $key => $value) {
                if ($value->parent->getInventoryItemDetail->block_this == 1) {
                    // Session::flash('warning', $value->parent->getInventoryItemDetail->stock_id_code.': The product has been blocked from sale due to a change in standard cost');
                    // return redirect()->back()->withInput();
                    $message = $value->parent->getInventoryItemDetail->stock_id_code . ': The product has been blocked from sale due to a change in standard cost';

                    return response()->json([
                        'error' => $message,
                    ], 400);
                }
            }
            $returns = [];
            $check = DB::transaction(function () use ($request, $id, $receiveOrderItems, $receiveOrder, &$returns) {
                $getLoggeduserProfile = getLoggeduserProfile();
                $purchaseOrder =  WaPurchaseOrder::with([
                    'getSupplier.getPaymentTerm',
                    'getBranch.getAssociateCompany.good_receive'
                ])->whereId($receiveOrder->wa_purchase_order_id)->first();
                $purchaseOrder->wa_location_and_store_id = $receiveOrder->wa_location_and_store_id;
                $purchaseOrder->wa_unit_of_measures_id = $receiveOrder->wa_unit_of_measures_id;
                $purchaseOrder->invoice_control_amount = $receiveOrder->invoice_control_amount;
                $purchaseOrder->note = $receiveOrder->note;
                $series_module = WaNumerSeriesCode::where('module', 'GRN')->first();
                $WaAccountingPeriod =  WaAccountingPeriod::where('is_current_period', '1')->first();
                $grn_number = getCodeWithNumberSeries('GRN');
                $dateTime = date('Y-m-d H:i:s');
                $vat_amount_arr = [];
                $cr_amount = [];
                $discounts = [];
                $allGood = true;
                $lineItems = [];

                if (count($receiveOrderItems) > 0) {
                    foreach ($receiveOrderItems as $purchaseOrderItem) {
                        $order_pirce = $purchaseOrderItem->order_price;
                        $supplier_discount = $purchaseOrderItem->supplier_discount;
                        $delivered_quantity = $purchaseOrderItem->delivered_quantity;
                        $invoice_discount = 0;
                        $distribution_discount = 0;
                        $transport_rebate = 0;

                        if ($purchaseOrderItem->return_quantity > 0) {

                            $vat = 0;
                            if ($purchaseOrderItem->parent->vat_rate && $purchaseOrderItem->parent->vat_rate > 0) {
                                $totalAmount = $purchaseOrderItem->return_quantity * $purchaseOrderItem->order_price;
                                $vat = getVatAmount($totalAmount, $purchaseOrderItem->parent->vat_rate);
                            }

                            $returns[] = [
                                'order_item_id' => $purchaseOrderItem->parent->id,
                                'item_code' => $purchaseOrderItem->parent->getInventoryItemDetail->stock_id_code,
                                'return_quantity' => $purchaseOrderItem->return_quantity,
                                'return_reason' => $purchaseOrderItem->return_reason,
                                'return_doc' => $purchaseOrderItem->return_doc,
                                'vat' => $vat,
                            ];
                        }

                        $purchaseOrderItem->parent->already_received = $purchaseOrderItem->parent->already_received + $delivered_quantity;
                        if ($purchaseOrderItem->parent->already_received < $purchaseOrderItem->parent->supplier_quantity) {
                            $allGood = false;
                        }
                        $purchaseOrderItem->parent->save();
                        $accountno = '';
                        $store_location_id = NULL;
                        $receivePurchaseOrder = WaReceivePurchaseOrder::find($purchaseOrderItem->wa_receive_purchase_order_id);
                        $accountno = @$purchaseOrderItem->parent->getInventoryItemDetail->getInventoryCategoryDetail->getStockGlDetail->account_code;
                        $stock_qoh = @$purchaseOrderItem->parent->getInventoryItemDetail->getAllFromStockMoves->where('wa_location_and_store_id', $receivePurchaseOrder->wa_location_and_store_id)->sum('qauntity') ?? 0;
                        if ($stock_qoh <=  0) {
                            $send_restock_notification = true;
                        } else {
                            $send_restock_notification = false;
                        }
                        $grn = new WaGrn();
                        $grn->wa_receive_purchase_order_item_id = $purchaseOrderItem->id;
                        $grn->wa_purchase_order_item_id = $purchaseOrderItem->parent->id;
                        $grn->wa_purchase_order_id = $purchaseOrder->id;
                        $grn->wa_supplier_id = $purchaseOrder->wa_supplier_id;
                        $grn->grn_number =  $grn_number;
                        $grn->item_code = $purchaseOrderItem->parent->item_no;
                        $grn->supplier_invoice_no = $receivePurchaseOrder->supplier_invoice_no;
                        $grn->cu_invoice_number = $receivePurchaseOrder->cu_invoice_number;
                        $grn->delivery_date = $dateTime; //date('Y-m-d');
                        $grn->item_description = @$purchaseOrderItem->parent->getInventoryItemDetail->title;
                        //~ $grn->qty_received = $purchaseOrderItem->parent->quantity;
                        $grn->qty_received = $delivered_quantity;
                        $grn->qty_invoiced = $purchaseOrderItem->parent->supplier_quantity;
                        $grn->standart_cost_unit = $purchaseOrderItem->parent->standard_cost;

                        $t = ($purchaseOrderItem->parent->order_price * $purchaseOrderItem->parent->supplier_quantity) - $purchaseOrderItem->parent->discount_amount;
                        $settings = json_decode($purchaseOrderItem->parent->discount_settings);
                        if ($settings) {
                            $inv_per = (float) (isset($settings->invoice_percentage) ? $settings->invoice_percentage : 0);
                            $invoice_discount += ($t * $inv_per) / 100;
                            $transport_rebate_per_unit = (float) isset($settings->transport_rebate_per_unit) ? $settings->transport_rebate_per_unit : 0;
                            $transport_rebate_percentage = (float) isset($settings->transport_rebate_percentage) ? $settings->transport_rebate_percentage : 0;
                            $transport_rebate_per_tonnage = (float) isset($settings->transport_rebate_per_tonnage) ? $settings->transport_rebate_per_tonnage : 0;
                            $distribution_discount = (float) isset($settings->distribution_discount) ? $settings->distribution_discount * $purchaseOrderItem->parent->quantity : 0;
                            if ($transport_rebate_per_unit > 0) {
                                $transport_rebate += $transport_rebate_per_unit * $purchaseOrderItem->parent->quantity;
                            } elseif ($transport_rebate_percentage > 0) {
                                $transport_rebate += ($t * $transport_rebate_percentage) / 100;
                            } elseif ($transport_rebate_per_tonnage > 0) {
                                $transport_rebate += $transport_rebate_per_tonnage * $purchaseOrderItem->parent->measure;
                            }
                        }

                        $totalDiscount = $purchaseOrderItem->parent->discount_amount + $purchaseOrderItem->parent->other_discounts_total;
                        $discounts[] = $totalDiscount;
                        $invoice_calculation = ['order_price' => $order_pirce, 'vat_rate' => $purchaseOrderItem->parent->vat_rate, 'qty' => $grn->qty_received, 'unit' => @$purchaseOrderItem->parent->getSupplierUomDetail->title];                      
                        $invoice_calculation['total_discount'] = $totalDiscount;
                        $grn->invoice_info = json_encode($invoice_calculation);
                        $grn->save();

                        $wainventoryitem = WaInventoryItem::where('stock_id_code', $purchaseOrderItem->parent->getInventoryItemDetail->stock_id_code)->first();
                        // $wainventoryitem->prev_standard_cost = $wainventoryitem->standard_cost;
                        // $wainventoryitem->standard_cost	= $grn->standart_cost_unit;
                        // $wainventoryitem->cost_update_time = date('d-m-Y H:i:s');
                        // $wainventoryitem->save();

                        $current_qoh = WaStockMove::where('stock_id_code', $wainventoryitem->stock_id_code)->sum('qauntity');
                        if (($current_qoh + $grn->qty_received) > 0) {
                            $itemId = $wainventoryitem->id;

                            //calculate base, invoice and promo item discounts
                            $baseDiscountRecord = DB::table('trade_agreements')
                                ->leftJoin('trade_agreement_discounts', function ($query) use ($itemId) {
                                    $query->on('trade_agreements.id', '=', 'trade_agreement_discounts.trade_agreements_id')
                                        ->where('trade_agreement_discounts.discount_type', 'Base Discount')
                                        ->whereRaw("JSON_CONTAINS_PATH(trade_agreement_discounts.other_options, 'one', '$.\"$itemId\"')");
                                })
                                ->where('wa_supplier_id', $purchaseOrder->wa_supplier_id)
                                ->first();
                            if ($baseDiscountRecord  && isset($baseDiscountRecord->other_options)) {
                                $otherOptions = json_decode($baseDiscountRecord->other_options, true);

                                if (isset($otherOptions[$itemId])) {
                                    $discountData = $otherOptions[$itemId];
                                    if (isset($discountData['type'])) {
                                        $type = $discountData['type'];
                                    } else {
                                        $type = '';
                                    }
                                    $discount = $discountData['discount'];
                                    $stockId = $discountData['stock_id'];
                                    if ($type == 'Percentage') {
                                        $baseDiscountValue = ($order_pirce * $discount) / 100;
                                    } else if ($type == 'Value') {
                                        $baseDiscountValue = $discount;
                                    } else {
                                        $baseDiscountValue = 0;
                                    }
                                }
                            }
                            $invoiceDiscountRecord = DB::table('trade_agreements')
                                ->leftJoin('trade_agreement_discounts', function ($query) use ($itemId) {
                                    $query->on('trade_agreements.id', '=', 'trade_agreement_discounts.trade_agreements_id')
                                        ->where('trade_agreement_discounts.discount_type', 'Invoice Discount')
                                        ->whereRaw("JSON_CONTAINS_PATH(trade_agreement_discounts.other_options, 'one', '$.\"$itemId\"')");
                                })
                                ->where('wa_supplier_id', $purchaseOrder->wa_supplier_id)
                                ->first();
                            if ($invoiceDiscountRecord  && isset($invoiceDiscountRecord->other_options)) {
                                $otherOptions = json_decode($invoiceDiscountRecord->other_options, true);

                                if (isset($otherOptions[$itemId])) {
                                    $discountData = $otherOptions[$itemId];
                                    $discountPercent = $discountData['discount'];
                                    $invoiceDiscountValue = (($order_pirce - ($baseDiscountValue ?? 0)) * $discountPercent) / 100;
                                }
                            }
                            $basePrice = $delivered_quantity * ($order_pirce - ($baseDiscountValue ?? 0));
                            $invoiceDiscount = $delivered_quantity * ($invoiceDiscountValue ?? 0);
                            $freeStockQty = ($purchaseOrderItem->parent->free_qualified_stock ?? 0) * ($purchaseOrderItem->parent->unit_conversion ?? 0);

                            $totalCostQty = $delivered_quantity + $freeStockQty;
                            if ($totalCostQty > 0) {
                                $last_grn_cost = (($basePrice) - ($invoiceDiscount)) / ($totalCostQty);
                            } else {
                                $last_grn_cost = $wainventoryitem->last_grn_cost;
                            }
                            if (($current_qoh + $totalCostQty) > 0) {
                                $weighted_cost = (($current_qoh * $wainventoryitem->weighted_average_cost) + ($totalCostQty * $last_grn_cost)) / ($current_qoh + $totalCostQty);
                            } else {
                                $weighted_cost = $wainventoryitem->weighted_average_cost;
                            }
                            $loggedUser = Auth::user();

                            //save history
                            $history = new WaInventoryPriceHistory();
                            $history->wa_inventory_item_id = $wainventoryitem->id;
                            $history->old_standard_cost = $wainventoryitem->standard_cost;
                            $history->standard_cost = $wainventoryitem->standard_cost;
                            $history->old_price_list_cost = $wainventoryitem->price_list_cost;
                            $history->price_list_cost = $wainventoryitem->price_list_cost;
                            $history->weighted_cost = $weighted_cost;
                            $history->old_weighted_cost = $wainventoryitem->weighted_average_cost;
                            $history->old_selling_price = $wainventoryitem->selling_price;
                            $history->selling_price = $wainventoryitem->selling_price;
                            $history->initiated_by = $loggedUser->id;
                            $history->approved_by = $loggedUser->user_id;
                            $history->status = 'Approved';
                            $history->created_at = date('Y-m-d H:i:s');
                            $history->updated_at = date('Y-m-d H:i:s');
                            $history->block_this = False;
                            $history->save();

                            $wainventoryitem->last_grn_cost = $last_grn_cost;
                            $wainventoryitem->weighted_average_cost = $weighted_cost;
                            $wainventoryitem->save();
                        }

                        $supplierData = WaInventoryItemSupplierData::where('wa_inventory_item_id', $wainventoryitem->id)->where('wa_supplier_id', $purchaseOrder->wa_supplier_id)->first();
                        $supplierData?->update([
                            'price' => $grn->standart_cost_unit
                        ]);

                        //move to price timelines start
                        $pt = new PriceTimeline();
                        $pt->user_id = $getLoggeduserProfile->id;
                        $pt->restaurant_id = $purchaseOrder->restaurant_id;
                        $pt->wa_location_and_store_id = @$purchaseOrder->wa_location_and_store_id;
                        $pt->wa_inventory_item_id = @$purchaseOrderItem->parent->getInventoryItemDetail->id;
                        $pt->stock_id_code = $purchaseOrderItem->parent->getInventoryItemDetail->stock_id_code;
                        $pt->transcation_type = 'GRN';
                        $pt->wa_supplier_id = $purchaseOrder->wa_supplier_id;
                        //$pt->delivery_date = $dateTime;//date('Y-m-d');   

                        $pt->standart_cost_unit = $purchaseOrderItem->parent->standard_cost;
                        $pt->qty_received = $delivered_quantity;


                        $pt->save();

                        //move to stock moves start
                        $stockMove = new WaStockMove();
                        $stockMove->user_id = $getLoggeduserProfile->id;
                        $stockMove->wa_purchase_order_id = $purchaseOrder->id;
                        $stockMove->restaurant_id = $purchaseOrder->restaurant_id;
                        $stockMove->wa_location_and_store_id = @$purchaseOrder->wa_location_and_store_id;
                        $stockMove->wa_inventory_item_id = @$purchaseOrderItem->parent->getInventoryItemDetail->id;
                        $stockMove->stock_id_code = $purchaseOrderItem->parent->getInventoryItemDetail->stock_id_code;
                        $stockMove->grn_type_number = $series_module->type_number;
                        $stockMove->document_no = $grn_number;
                        $stockMove->grn_last_nuber_used = $series_module->last_number_used;
                        $price = $order_pirce;
                        if ($supplier_discount && $supplier_discount > 0) {
                            $discount_type = $settings->base_discount_type;
                            $discount_amount = ($discount_type == 'Value' ? ($supplier_discount) : (($supplier_discount * $order_pirce) / 100));

                            $price = $price - $discount_amount;
                            $stockMove->discount_percent = $supplier_discount;
                        }
                        $stockMove->price = $price;
                        $stockMove->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                        $stockMove->refrence = (@$purchaseOrder->getSupplier->supplier_code) . '/' . (@$purchaseOrder->getSupplier->name) . '/' . $purchaseOrder->purchase_no;
                        $stockMove->qauntity =  $delivered_quantity * $purchaseOrderItem->parent->unit_conversion;
                        $stockMove->standard_cost = $purchaseOrderItem->parent->standard_cost;
                        $stock_qoh += $stockMove->qauntity;
                        $stockMove->new_qoh = $stock_qoh;
                        $stockMove->save();

                        if ($purchaseOrderItem->parent->free_qualified_stock > 0) {
                            $freeStock = new WaStockMove();
                            $freeStock->user_id = $getLoggeduserProfile->id;
                            $freeStock->wa_purchase_order_id = $purchaseOrder->id;
                            $freeStock->restaurant_id = $purchaseOrder->restaurant_id;
                            $freeStock->wa_location_and_store_id = @$purchaseOrder->wa_location_and_store_id;
                            $freeStock->wa_inventory_item_id = @$purchaseOrderItem->parent->getInventoryItemDetail->id;
                            $freeStock->stock_id_code = $purchaseOrderItem->parent->getInventoryItemDetail->stock_id_code;
                            $freeStock->grn_type_number = $series_module->type_number;
                            $freeStock->document_no = $grn_number;
                            $freeStock->grn_last_nuber_used = $series_module->last_number_used;
                            $freeStock->price = 0;
                            $freeStock->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                            $freeStock->refrence = (@$purchaseOrder->getSupplier->supplier_code) . '/' . (@$purchaseOrder->getSupplier->name) . '/' . $purchaseOrder->purchase_no . '/free-stock';
                            $freeStock->qauntity =  $purchaseOrderItem->parent->free_qualified_stock * $purchaseOrderItem->parent->unit_conversion;
                            $freeStock->standard_cost = 0;
                            $freeStock->new_qoh = $stockMove->new_qoh + $stockMove->qauntity;
                            $freeStock->save();
                        }

                        $dr =  new WaGlTran();
                        $dr->grn_type_number = $series_module->type_number;
                        $dr->grn_last_used_number = $series_module->last_number_used;


                        $dr->transaction_type = $series_module->description;
                        $dr->transaction_no = $grn_number;
                        $dr->trans_date = $dateTime;
                        $dr->restaurant_id = $getLoggeduserProfile->restaurant_id;

                        $dr->wa_purchase_order_id = $purchaseOrder->id;
                        $dr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                        $dr->supplier_account_number = null;
                        $dr->account = $accountno;
                        //$dr->amount = $price*$delivered_quantity;

                        //managae dr accounts end/
                        $vat_amm = 0;
                        $total_price = $order_pirce * $delivered_quantity - $totalDiscount;
                        $cr_amount[] = $total_price;
                        if ($purchaseOrderItem->parent->vat_rate && $purchaseOrderItem->parent->vat_rate > 0) {
                            $vat_amm = $total_price - (($total_price * 100) / ($purchaseOrderItem->parent->vat_rate + 100));
                            $vat_amount_arr[] = $vat_amm;
                        }
                        $dr->amount = $total_price - $vat_amm;
                        $dr->narrative = $purchaseOrder->purchase_no . '/' . (@$purchaseOrder->getSupplier->supplier_code) . '/' . $purchaseOrderItem->parent->item_no . '/' . $purchaseOrderItem->parent->getInventoryItemDetail->title . '/' . $purchaseOrderItem->parent->quantity . '@' . $price;
                        $dr->save();

                        //send restock sms
                        if ($send_restock_notification) {
                            $users  = User::where('status', 1)->whereIn('role_id', [4, 169, 170, 181])->where('wa_location_and_store_id', @$purchaseOrder->wa_location_and_store_id)->get();
                            $message  = "Item Restock Alert:\nCODE: $wainventoryitem->stock_id_code\nTITLE: $wainventoryitem->title\nSELLING PRICE: $wainventoryitem->selling_price";
                            foreach ($users as $user) {
                                $this->smsService->sendMessage($message, $user->phone_number);
                            }
                        }
                    }
                }
                //vat entry start
                $taxVat = TaxManager::with(['getOutputGlAccount'])->where('slug', 'vat')->first();
                if ($taxVat && $taxVat->getOutputGlAccount && count($vat_amount_arr) > 0) {
                    $vat = new WaGlTran();
                    $vat->grn_type_number = $series_module->type_number;
                    $vat->transaction_type = $series_module->description;
                    $vat->transaction_no = $grn_number;
                    $vat->grn_last_used_number = $series_module->last_number_used;
                    $vat->trans_date = $dateTime;
                    $vat->restaurant_id = $getLoggeduserProfile->restaurant_id;
                    $vat->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                    $vat->supplier_account_number = null;
                    $vat->account = $taxVat->getOutputGlAccount->account_code;
                    $vat->amount = array_sum($vat_amount_arr);
                    $vat->narrative = null;
                    $vat->wa_purchase_order_id = $purchaseOrder->id;
                    $vat->save();
                }
                // vat entry end 
                // cr entry start
                $cr = new WaGlTran();
                $cr->grn_type_number = $series_module->type_number;
                $cr->transaction_type = $series_module->description;
                $cr->transaction_no = $grn_number;
                $cr->grn_last_used_number = $series_module->last_number_used;
                $cr->trans_date = $dateTime;
                $cr->restaurant_id = $getLoggeduserProfile->restaurant_id;
                $cr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                $cr->supplier_account_number = null;
                $cr->account =  @$purchaseOrder->getBranch->getAssociateCompany->good_receive->account_code;
                $cr->amount = round(array_sum($cr_amount), 2) * -1;
                $cr->narrative = null;
                $cr->wa_purchase_order_id = $purchaseOrder->id;
                $cr->save();

                $total_cost_with_vat = array_sum($cr_amount);
                $roundOff = fmod($total_cost_with_vat, 1); //0.25
                if ($roundOff != 0) {
                    if ($roundOff > '0.50') {
                        $roundOff = round((1 - $roundOff), 2);
                        $crdrAmnt = '+' . $roundOff;
                    } else {
                        $roundOff = '-' . round($roundOff, 2);
                        $crdrAmnt = $roundOff;
                    }
                    $cr = new WaGlTran();
                    $cr->grn_type_number = $series_module->type_number;
                    $cr->transaction_type = $series_module->description;
                    $cr->transaction_no = $grn_number;
                    $cr->grn_last_used_number = $series_module->last_number_used;
                    $cr->trans_date = $dateTime;
                    $cr->restaurant_id = $getLoggeduserProfile->restaurant_id;
                    $cr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                    $cr->supplier_account_number = null;
                    $cr->account =  "202021";
                    $cr->amount = $crdrAmnt;
                    $cr->narrative = null;
                    $cr->wa_purchase_order_id = $purchaseOrder->id;
                    $cr->save();
                    //cr enter end
                }

                //  supp trans entry start
                // Create supplier transaction for invoice generation
                $suppTransSeries = WaNumerSeriesCode::where('module', 'SUPPLIER_INVOICE_NO')->first();
                $supplierTrans = new WaSuppTran();
                $supplierTrans->document_no = $grn_number;
                $supplierTrans->trans_date = $dateTime;
                $supplierTrans->suppreference = $receivePurchaseOrder->supplier_invoice_no ?? $grn_number;
                $supplierTrans->cu_invoice_number = $receivePurchaseOrder->cu_invoice_number;
                $supplierTrans->supplier_no = $purchaseOrder->wa_supplier_id;
                $supplierTrans->grn_type_number = $suppTransSeries->type_number ?? $series_module->type_number;
                $supplierTrans->vat_amount = array_sum($vat_amount_arr);
                $supplierTrans->total_amount_inc_vat = $total_cost_with_vat;
                $supplierTrans->wa_purchase_order_id = $purchaseOrder->id;
                $supplierTrans->prepared_by = $getLoggeduserProfile->id;
                $supplierTrans->settled = 0;
                $supplierTrans->save();

                $invoiceNumber = getCodeWithNumberSeries('SUPPLIER_INVOICE_NO');
                $supplierInvoice = WaSupplierInvoice::create([
                    'wa_purchase_order_id' => $purchaseOrder->id,
                    'wa_supp_tran_id' => $supplierTrans->id,
                    'grn_number' => $grn_number,
                    'grn_date' => $dateTime,
                    'supplier_invoice_date' => $dateTime,
                    'invoice_number' => $invoiceNumber,
                    'supplier_invoice_number' => $receivePurchaseOrder->supplier_invoice_no ?? $invoiceNumber,
                    'cu_invoice_number' => $receivePurchaseOrder->cu_invoice_number,
                    'supplier_id' => $purchaseOrder->wa_supplier_id,
                    'prepared_by' => $getLoggeduserProfile->id,
                    'vat_amount' => array_sum($vat_amount_arr),
                    'amount' => $total_cost_with_vat,
                ]);

                $grnLines = WaGrn::where('grn_number', $grn_number)->get();
                foreach ($grnLines as $grnLine) {
                    $invoiceDetails = json_decode($grnLine->invoice_info);
                    $qty = (float) ($invoiceDetails->qty ?? $grnLine->qty_received ?? 0);
                    $orderPrice = (float) ($invoiceDetails->order_price ?? 0);
                    $totalDiscount = (float) ($invoiceDetails->total_discount ?? 0);
                    $vatRate = (float) ($invoiceDetails->vat_rate ?? 0);

                    $lineTotal = ($orderPrice * $qty) - $totalDiscount;
                    $vatAmount = $vatRate > 0 ? ($lineTotal - (($lineTotal * 100) / ($vatRate + 100))) : 0;

                    WaSupplierInvoiceItem::create([
                        'wa_supplier_invoice_id' => $supplierInvoice->id,
                        'code' => $grnLine->item_code,
                        'description' => $grnLine->item_description,
                        'quantity' => $qty,
                        'standart_cost_unit' => $orderPrice,
                        'discount_amount' => $totalDiscount,
                        'vat_amount' => $vatAmount,
                        'amount' => $lineTotal,
                    ]);
                }

                updateUniqueNumberSeries('SUPPLIER_INVOICE_NO', $invoiceNumber);
                //  supp trans entry end    
                if ($allGood) {
                    $orderStatus = 'Received';
                    $purchaseOrder->status = 'COMPLETED';
                } else {
                    $orderStatus = 'Partial Received';
                }
                // Try to update supplier portal, but don't fail if it's not available
                if ($purchaseOrder->getSupplier->locked_trade) {
                    try {
                        \Illuminate\Support\Facades\Log::info('Attempting to update supplier portal for LPO: ' . $purchaseOrder->purchase_no);
                        
                        // Check if SUPPLIER_PORTAL_URI is set
                        $supplierPortalUri = env('SUPPLIER_PORTAL_URI');
                        if (empty($supplierPortalUri)) {
                            \Illuminate\Support\Facades\Log::warning('SUPPLIER_PORTAL_URI not set, skipping supplier portal update');
                        } else {
                            $api = new \App\Services\ApiService($supplierPortalUri);
                            $response = $api->postRequest('/api/update-delivery-received-status', [
                                'lpo_number' => $purchaseOrder->purchase_no,
                                'status' => $orderStatus,
                                'order_from' => env('SUPPLIER_SOURCE')
                            ]);
                            
                            // Log the response
                            if (isset($response['error'])) {
                                \Illuminate\Support\Facades\Log::warning('Supplier portal update failed: ' . json_encode($response));
                            } else {
                                \Illuminate\Support\Facades\Log::info('Supplier portal updated successfully');
                            }
                        }
                    } catch (\Exception $e) {
                        // Log the error but continue processing
                        \Illuminate\Support\Facades\Log::error('Error updating supplier portal: ' . $e->getMessage());
                        // Don't throw the exception - continue processing GRN
                    }
                }

                if (count($returns) > 0) {
                    $lineItems = collect($returns)->map(function ($return) use ($purchaseOrder, $grn) {
                        return [
                            'id' => $grn->id,
                            'supplier_id' => $purchaseOrder->wa_supplier_id,
                            'item_code' => $return['item_code'],
                            'grn_number' => $grn->grn_number,
                            'quantity' => $return['return_quantity'],
                            'reason' => $return['return_reason'],
                            'vat' => $return['vat'],
                        ];
                    });

                    $returnNumber = app(CreateGrnReturn::class)->create($lineItems->toArray(), auth()->user());
                    if ($purchaseOrder->getSupplier->locked_trade) {
                        $postData = [
                            'lpo_number' => $purchaseOrder->purchase_no,
                            'order_from' => env('SUPPLIER_SOURCE'),
                            'return_no' => $returnNumber
                        ];

                        foreach ($returns as $key => $return) {
                            $postData['order_item_id'][] = $return['order_item_id'];
                            $postData['item_code'][] = $return['item_code'];
                            $postData['return_quantity'][] = $return['return_quantity'];
                            $postData['return_reason'][] = $return['return_reason'];
                            $postData['return_doc'][] = $return['return_doc'];
                        }

                        $api = new \App\Services\ApiService(env('SUPPLIER_PORTAL_URI'));
                        $api->postRequest('/api/lpo/update-order-returns', $postData);
                    }
                    $receiveOrder->returned_by = $getLoggeduserProfile->id;
                    $receiveOrder->return_no = $returnNumber;
                    $receiveOrder->returned_at = date('Y-m-d H:i:s');
                }

                $purchaseOrder->vehicle_reg_no = $receiveOrder->vehicle_reg_no;
                $purchaseOrder->receive_note_doc_no = $receiveOrder->receive_note_doc_no;
                $purchaseOrder->save();
                $receiveOrder->processed_by = $getLoggeduserProfile->id;
                $receiveOrder->grn_number = $grn_number;
                $receiveOrder->processed_at = date('Y-m-d H:i:s');
                $receiveOrder->status = $this->consts[$request->approval_status];
                $receiveOrder->save();

                updateUniqueNumberSeries('GRN', $grn_number);

                return true;
            });

            // auto approve any returns
            if (count($returns) > 0) {
                $lineItems = collect($returns)->map(function ($item) use ($receiveOrder) {
                    return [
                        'id' => ReturnedGrn::where([
                            'return_number' => $receiveOrder->return_no,
                            'item_code' => $item['item_code']
                        ])->first()->id,
                        'quantity' => $item['return_quantity'],
                        'reason' => $item['return_reason'],
                        'vat' => $item['vat']
                    ];
                });

                app(ApproveGrnReturn::class)->approve($lineItems->toArray(), auth()->user());
            }

            if (!$check) {
                throw new \Exception("Something went wrong");
            }

            return response()->json([
                'message' => 'GRN Processed Successfully',
                'redirect_url' => route($this->model . '.index')
            ], 200);
        } catch (\Throwable $th) {

            return response()->json([
                'error' => 'Invalid Request: ' . $th->getTraceAsString()
            ], 500);
        }
    }

    public function destroy($id)
    {
        if (!can('delete', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $receiveOrder = WaReceivePurchaseOrder::findOrFail($id);
        $receiveOrder->child_items()->delete();
        $receiveOrder->delete();

        Session::flash('success', 'Record deleted successfully');

        return redirect()->route('confirmed-receive-purchase-order.index');
    }
}
