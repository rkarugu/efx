<?php

namespace App\Http\Controllers\Admin;

use App\Alert;
use App\Interfaces\SmsService;
use App\Model\WaEsdDetails;
use App\Model\WaRouteCustomer;
use App\Models\WaAccountTransaction;
use App\Notifications\PosCashSales\StaleOrdersNotification;
use App\Services\InfoSkySmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaDepartment;
use App\Model\WaInventoryItem;
use App\Model\WaStockMove;
use App\Model\WaCustomer;
use App\Model\WaNumerSeriesCode;
use App\Model\WaLocationAndStore;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\Route;
use App\Model\User;
use App\Model\WaInventoryLocationUom;
use App\Model\WaUnitOfMeasure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\DiscountBand;
use App\ItemPromotion;
use PDF;
use Session;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use function tests\data;

class InternalRequisitionController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;
    public function __construct()
    {
        $this->model = 'sales-invoice';
        $this->title = 'Credit Sales';
        $this->pmodule = 'sales-invoice';
    }

    public function getcustomercredit(Request $request)
    {
        $customer = WaCustomer::where('id', $request->id)->where('is_blocked', 0)->first();
        if (!$customer) {
            return response()->json(['result' => -1, 'message' => 'Customer not available']);
        }
        $customer_detail = $customer->customer_name;
        $customer_limit = 0;
        $customer_limit = $customer->credit_limit;
        $used_limit = \App\Model\WaDebtorTran::where('wa_customer_id', @$customer->id)->sum('amount');
        $credit_limit = $customer_limit - $used_limit;
        return response()->json([
            'result' => 1,
            'customer_id' => @$customer->id,
            'credit_limit' => manageAmountFormat($customer_limit),
            'balance' => manageAmountFormat($credit_limit),
            'used_balance' => manageAmountFormat($used_limit),
            'customer' => $customer
        ]);
    }

    public function getsalesmanroute(Request $request)
    {
        //        dd($request->all());
        $salesman = WaLocationAndStore::where('id', $request->id)->first();
        //        dd($salesman);
        if (!$salesman || !$salesman->route_id) {
            return response()->json(['result' => -1, 'message' => 'Salesman route not available']);
        }
        $route = Route::where('id', $salesman->route_id)->first();
        if (!$route) {
            return response()->json(['result' => -1, 'message' => 'Route not available']);
        }
        // else{
        //     $customer = WaCustomer::where('route_id',@$route->id)->where('is_blocked',0)->first();
        // }
        if (isset($salesman->user->route) && $salesman->user->route != NULL) {
            $customer = WaCustomer::where('route_id', @$salesman->user->route)->where('is_blocked', 0)->first();
        }
        $customer_detail = NULL;
        if (isset($customer) && isset($salesman->user) && $salesman->user->role_id == 4) {
            $customer_detail = $customer->customer_name; //'<option value="'.$customer->id.'" selected>'.$customer->customer_name.'</option>';
        }
        $user_limit = $customer_limit = 0;

        if (isset($customer) && $customer) {
            $customer_limit = $customer->credit_limit;
            $used_limit = \App\Model\WaDebtorTran::where('wa_customer_id', @$customer->id)->sum('amount');
        } else {
            $used_limit = 0;
        };
        $shift_id = \App\Model\WaShift::where('salesman_id', @$salesman->user->id)->where('shift_date', date('Y-m-d'))->orderBy('id', 'DESC')->where('status', 'open')->first();
        $invoices = WaInventoryLocationTransfer::where('shift_id', @$shift_id->id)->pluck('id')->toArray();
        $invoicesItems = WaInventoryLocationTransferItem::whereIn('wa_inventory_location_transfer_id', $invoices)->sum('total_cost_with_vat');
        // $credit_limit = ($salesman->credit_limit ?? 0)-$used_limit;
        $credit_limit = $customer_limit - $used_limit;
        return response()->json([
            'result' => 1,
            'data' => $route,
            // 'credit_limit'=>manageAmountFormat($salesman->credit_limit ?? 0),
            'credit_limit' => manageAmountFormat($customer_limit),
            'balance' => manageAmountFormat($credit_limit),
            'used_balance' => manageAmountFormat($used_limit),
            'shift_id' => $shift_id->shift_id ?? NULL,
            'invoices' => count($invoices),
            'invoicesItems' => $invoicesItems,
            'customer' => $customer_detail
        ]);
    }

    public function index(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $user_permission = $this->myUserPermissionsforAModule();

        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        $startDate = $request->from ?? now()->startOfDay();
        $endDate = Carbon::parse($request->to)->endOfDay()  ?? now();

        if (isset($permission[$pmodule . '___invoices']) || $permission == 'superadmin') {
            $routes = WaCustomer::where('is_invoice_customer', true)->pluck('route_id')->toArray();

            $lists = WaInternalRequisition::with(['getRouteCustomer', 'getrelatedEmployee', 'esd_details'])
                ->whereIn('route_id', $routes)
                ->where('requisition_no', 'LIKE', 'INV-%')
                ->whereBetween('created_at', [$startDate, $endDate]);

            //            $esd_details = WaEsdDetails::where('invoice_number', $request->requisition_no)->first();

            if ($permission != 'superadmin') {
                $lists = $lists->where('user_id', getLoggeduserProfile()->id);
            }
            $lists = $lists->orderBy('id', 'desc')->get();
            //            dd($startDate, $endDate);

            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];

            return view('admin.internalrequisition.index', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'user_permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function create()
    {
        $getLoggeduserProfile = getLoggeduserProfile();

        if ( env("USE_OTP"))
        {
            if (!session()->has('credit_otp_verified')) {

                Session::flash('warning', 'OTP Not Verified');
                return redirect()->route('sales-invoice.index');
            }
            session()->forget('credit_otp_verified');
        }

        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___invoices-create']) || $permission == 'superadmin') {
            $title = 'Add ' . $this->title;
            $model = $this->model;
            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
            $salesmanroute = NULL;

            if ($getLoggeduserProfile->userRole->slug == 'sales-man') {
                $salesman = WaLocationAndStore::with(['user'])->where('id', $getLoggeduserProfile->wa_location_and_store_id)->first();
                if ($salesman && $salesman->route_id) {
                    if (isset($salesman->user->route) && $salesman->user->route != NULL) {
                        $salesmanroute = @$salesman->user->route;
                    }
                }
            }

            $customer_list = WaCustomer::where('is_invoice_customer', true)
                ->pluck('bussiness_name', 'id')
                ->toArray();
            return view('admin.internalrequisition.create', compact('title', 'model', 'customer_list', 'breadcum', 'getLoggeduserProfile', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function print(Request $request)
    {

        $slug = $request->slug;
        $title = 'Add ' . $this->title;
        $model = $this->model;
        $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
        $row = WaInternalRequisition::whereSlug($slug)->first();
        $list = $row;
        $itemsdata = WaInternalRequisitionItem::query()
            ->select(
                'wa_internal_requisition_items.*',
                'wa_unit_of_measures.title as bin'
            )
            ->join('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_internal_requisition_items.wa_inventory_item_id')
            ->join('wa_inventory_location_uom', function ($join) use ($list) {
                $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')
                    ->where('wa_inventory_location_uom.location_id', $list->to_store_id);
            })
            ->join('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->where('wa_internal_requisition_items.wa_internal_requisition_id', $list->id)
            ->orderBy('wa_inventory_items.stock_id_code', 'ASC')
            ->get();
        return view('admin.salesinvoice.print', compact('title', 'model', 'breadcum', 'row', 'list', 'itemsdata'));
    }

    public function exportToPdf($slug)
    {


        $title = 'Add ' . $this->title;
        $model = $this->model;
        $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
        $list = WaInternalRequisition::whereSlug($slug)->first();
        $itemsdata = WaInternalRequisitionItem::query()
            ->select(
                'wa_internal_requisition_items.*',
                'wa_unit_of_measures.title as bin'
            )
            ->join('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_internal_requisition_items.wa_inventory_item_id')
            ->join('wa_inventory_location_uom', function ($join) use ($list) {
                $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')
                    ->where('wa_inventory_location_uom.location_id', $list->to_store_id);
            })
            ->join('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->where('wa_internal_requisition_id', $list->id)
            ->orderBy('wa_inventory_items.stock_id_code', 'ASC')
            ->get();

        $pdf = PDF::loadView('admin.salesinvoice.print', compact('title', 'model', 'breadcum', 'list', 'itemsdata'));
        $report_name = 'internal_requisition_' . date('Y_m_d_H_i_A');
        return $pdf->download($report_name . '.pdf');
    }


    public function store(Request $request)
    {
        try {
            if (!$request->ajax()) {
                throw new Exception("Un-Authorized Request");
            }

            $validator = Validator::make($request->all(), [
                'requisition_date' => 'required|date',
                'request_type' => 'required|in:save,send_request',
                'customer' => 'required|string|min:1',
                'item_id' => 'array',
                'item_id.*' => 'required|exists:wa_inventory_items,id',
                'item_quantity.*' => 'required|numeric',
                'item_selling_price.*' => 'required|numeric'
            ], [
                'item_id.*' => 'Item',
                'item_quantity.*' => 'Quantity',
                'item_selling_price.*' => 'Selling Price',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'result' => 0]);
            }

            $inventory = WaInventoryItem::select([
                '*',
                DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.wa_location_and_store_id = wa_inventory_items.store_location_id AND wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
            ])->with(['getTaxesOfItem'])->whereIn('id', $request->item_id)->get();
            if (count($request->item_id) == 0 || count($inventory) == 0) {
                throw new \Exception("No inventory item selected");
            }
            $used_limit = 0;
            $credit_limit = 0;
            $customer = \App\Model\WaCustomer::where('id', $request->customer)->with('route')->first();

            if ($customer) {
                if ($customer->is_blocked == 1) {
                    return response()->json(['errors' => [
                        'customer' => ["Your account has been temporarily blocked from invoice processing, kindly contact the administrator"]
                    ], 'result' => 0]);
                }
                $credit_limit = $customer->credit_limit ?? 0;
                $used_limit = \App\Model\WaDebtorTran::where('wa_customer_id', @$customer->id)->sum('amount');
                if (!$customer->route_id) {
                    return response()->json(['errors' => [
                        'customer' => ["Account route not found"]
                    ], 'result' => 0]);
                }
            }

            if ($request->request_type == 'send_request') {
                $totalinventorycost = 0;
                foreach ($inventory as $key => $value) {
                    $qoh = WaStockMove::where('stock_id_code', $value->stock_id_code)->where('wa_location_and_store_id', getLoggeduserProfile()->wa_location_and_store_id)->sum('qauntity');

                    if ($qoh < $request->item_quantity[$value->id]) {
                        return response()->json(['errors' => [
                            'item_quantity.' . $value->id => ["Available quantity is not enough"]
                        ], 'result' => 0]);
                    }
                    if (!$request->item_selling_price[$value->id] || $value->standard_cost > $request->item_selling_price[$value->id]) {
                        return response()->json([
                            'result' => 0,
                            'errors' => ['item_selling_price.' . $value->id => ['Selling price must be greater than or equal to standard cost']]
                        ]);
                    }
                    if ($value->block_this == 1) {
                        return response()->json([
                            'result' => 0,
                            'errors' => ['item_id.' . $value->id => ['The product has been blocked from sale due to a change in standard cost']]
                        ]);
                    }
                    $totalinventorycost += @$request->item_selling_price[$value->id] * (@$request->item_quantity[$value->id]);
                }
                $available_limit = $credit_limit - $used_limit;
                if ($available_limit < $totalinventorycost) {
                    throw new \Exception("You cannot process the Invoice as it will exceed your allowed Credit Limit");
                }
            }

            // return response()->json(['result'=>-1,'message'=>'Something went wrong']);
            $check = DB::transaction(function () use ($request, $inventory, $customer) {


                $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();
                $lastNumberUsed = $series_module->last_number_used;
                $newNumber = (int)$lastNumberUsed + 1;
                $series_module->update(['last_number_used' => $newNumber]);


                $getLoggeduserProfile = getLoggeduserProfile();
                $row = new WaInternalRequisition();
                $row->requisition_no =  "INV-$newNumber";
                $row->slug =  strtolower("INV-$newNumber");
                $row->restaurant_id = $customer->route->restaurant_id;
                $row->wa_department_id = $getLoggeduserProfile->wa_department_id;
                $row->user_id = $getLoggeduserProfile->id;
                $row->invoice_type = "Backend";
                $row->to_store_id = $getLoggeduserProfile->wa_location_and_store_id;
                $row->wa_location_and_store_id = $getLoggeduserProfile->wa_location_and_store_id;
                $row->requisition_date = $request->requisition_date;
                $row->name = @$customer->customer_name;


                $row->route = $customer->customer_name;
                $row->route_id = $customer->route_id;

                $row->customer = $customer->customer_name;
                $row->customer_phone_number = $customer->telephone;
                $row->customer_pin = $customer->kra_pin;
                $row->customer_id = @$customer->id;
                $row->save();



                $childs = [];
                foreach ($inventory as $key => $value) {
                    $quantity = @$request->item_quantity[$value->id];
                    $originalPrice = @$request->item_selling_price[$value->id];
                    
                    // Check for discount bands
                    $discountBand = DiscountBand::where('inventory_item_id', $value->id)
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
                    
                    $discountAmount = 0;
                    $finalPrice = $originalPrice;
                    
                    if ($discountBand) {
                        $discountAmount = $discountBand->discount_amount * $quantity;
                        $finalPrice = max(0, $originalPrice - $discountBand->discount_amount);
                    }
                    
                    // Check for Buy X Get Y promotions
                    $promotion = ItemPromotion::where('inventory_item_id', $value->id)
                        ->where('status', 'active')
                        ->whereNotNull('promotion_item_id')
                        ->where(function ($query) {
                            $today = \Carbon\Carbon::today();
                            $query->where('from_date', '<=', $today)
                                ->where(function ($subQuery) use ($today) {
                                    $subQuery->where('to_date', '>=', $today)
                                             ->orWhereNull('to_date');
                                });
                        })
                        ->first();
                        
                    if ($promotion) {
                        $promotionBatches = floor($quantity / (float)$promotion->sale_quantity);
                        if ($promotionBatches > 0) {
                            $promotionQty = $promotionBatches * $promotion->promotion_quantity;
                            $promotionItem = WaInventoryItem::find($promotion->promotion_item_id);
                            
                            if ($promotionItem) {
                                // Add free item to childs array
                                $childs[] = [
                                    'wa_internal_requisition_id' => $row->id,
                                    'wa_inventory_item_id' => $promotionItem->id,
                                    'quantity' => $promotionQty,
                                    'standard_cost' => $promotionItem->standard_cost,
                                    'selling_price' => 0, // Free item
                                    'total_cost' => 0, // Free item
                                    'tax_manager_id' => $promotionItem->tax_manager_id,
                                    'vat_rate' => 0,
                                    'vat_amount' => 0,
                                    'total_cost_with_vat' => 0,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'store_location_id' => $getLoggeduserProfile->store_location_id,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                    'hs_code' => $promotionItem->hs_code,
                                    'discount' => 0,
                                ];
                                
                                \Log::info("Credit Invoice: Free item added", [
                                    'promotion_item_id' => $promotionItem->id,
                                    'promotion_qty' => $promotionQty,
                                    'customer_bought' => $quantity,
                                    'promotion_batches' => $promotionBatches
                                ]);
                            }
                        }
                    }
                    
                    $vat_rate = 0;
                    $vat_amount = 0;
                    $totalcost = $finalPrice * $quantity;
                    if ($value->tax_manager_id && $value->getTaxesOfItem) {
                        $vat_rate = $value->getTaxesOfItem->tax_value;
                        $vat_amount = $totalcost - (($totalcost * 100) / ($vat_rate + 100));
                    }
                    $childs[] = [
                        'wa_internal_requisition_id' => $row->id,
                        'wa_inventory_item_id' => $value->id,
                        'quantity' => $quantity,
                        'standard_cost' => $value->standard_cost,
                        'selling_price' => $originalPrice, // Store original price
                        'total_cost' => $totalcost,
                        'tax_manager_id' => $value->tax_manager_id,
                        'vat_rate' => $vat_rate,
                        'vat_amount' => $vat_amount,
                        'total_cost_with_vat' => $totalcost,
                        'created_at' => date('Y-m-d H:i:s'),
                        'store_location_id' => $getLoggeduserProfile->store_location_id,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'hs_code' => $value->hs_code,
                        'discount' => $discountAmount, // Per unit discount
                    ];
                }
                WaInternalRequisitionItem::insert($childs);
                $row->status = 'UNAPPROVED';
                $row->save();
                if ($request->request_type == 'send_request') {
                    addInternalRequisitionPermissions($row->id, $row->wa_department_id);
                }
                return $row;
            });
            if ($check) {
                $location = route('sales-invoice.index') . getReportDefaultFilterForTrialBalance();
                if ($request->request_type == 'send_request') {
                    $message = 'Request Sent Successfully.';
                } else {
                    $message = 'Sales Created Successfully';
                }
                return response()->json(['result' => 1, 'message' => $message, 'location' => $location]);
            }
            return response()->json(['result' => -1, 'message' => 'Something went wrong']);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }

    public function sendRequisitionRequest($requisition_no)
    {
        try {


            $row = WaInternalRequisition::with(['getRelatedItem', 'getRelatedItem.getInventoryItemDetail'])->where('status', 'UNAPPROVED')->where('requisition_no', $requisition_no)->first();
            if ($row) {
                foreach ($row->getRelatedItem as $key => $val) {
                    if ($this->checkQuantity($row->wa_location_and_store_id, $val->getInventoryItemDetail->stock_id_code, $val->quantity) == '1') {
                        //	return response()->json(['status'=>false,'message'=>'('.$val->stockcode.') out of stock.']);
                        Session::flash('warning', $val->getInventoryItemDetail->stock_id_code . ') out of stock.');
                        return redirect()->back();
                    }
                }
                $series_module = WaNumerSeriesCode::where('module', 'GRN')->first();
                $intr_smodule = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();
                $logged_user_profile = getLoggeduserProfile();

                foreach ($row->getRelatedItem as $key => $related_item_row) {
                    //negative entry
                    $stockMove = new WaStockMove();
                    $stockMove->user_id = $logged_user_profile->id;
                    $stockMove->wa_internal_requisition_id = $row->id;
                    $stockMove->document_no = $row->requisition_no;
                    $stockMove->restaurant_id = $logged_user_profile->restaurant_id;
                    $stockMove->wa_location_and_store_id = $row->wa_location_and_store_id;
                    $stockMove->wa_inventory_item_id = $related_item_row->wa_inventory_item_id;
                    $stockMove->standard_cost = $related_item_row->standard_cost;
                    $stockMove->qauntity = - ($related_item_row->quantity);
                    $stockMove->stock_id_code = $related_item_row->getInventoryItemDetail->stock_id_code;
                    $stockMove->wa_inventory_item_id = $related_item_row->getInventoryItemDetail->id;
                    $stockMove->grn_type_number = $series_module->type_number;
                    $stockMove->grn_last_nuber_used = $series_module->last_number_used;
                    $stockMove->price = $related_item_row->standard_cost;
                    $stockMove->save();
                }

                $row->status = 'PENDING';
                $row->save();
                addInternalRequisitionPermissions($row->id, $row->wa_department_id);
                //                updateUniqueNumberSeries('TRAN',$requisition_no);
                updateUniqueNumberSeries('INTERNAL REQUISITIONS', $requisition_no);
                Session::flash('success', 'Request sent successfully.');
                return redirect()->route($this->model . '.index');
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }


    public function show($slug)
    {

        $row = WaInternalRequisition::whereSlug($slug)->first();
        if ($row) {
            $title = 'View ' . $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Show' => ''];
            $model = $this->model;
            foreach ($row->getRelatedItem as $relatedItem) {
                $ItemBin = WaInventoryLocationUom::where('inventory_id', $relatedItem->wa_inventory_item_id)->where('location_id', $row->to_store_id)->get();
                if ($ItemBin->count() > 0 && $ItemBin[0]->uom_id) {
                    $itemBinLocationName = WaUnitOfMeasure::find($ItemBin[0]->uom_id)->title;
                    $relatedItem->uom = $itemBinLocationName;
                } else {
                    $relatedItem->uom = '-';
                }
            }
            return view('admin.internalrequisition.show', compact('title', 'model', 'breadcum', 'row'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function edit($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            $editPermission = '';
            $pmodule = $this->pmodule;
            if (!isset($permission[$pmodule . '___edit-values']) && $permission != 'superadmin') {
                $editPermission = 'readonly';
            }
            $row = WaInternalRequisition::with([
                'getRelatedItem',
                'getRelatedItem.getInventoryItemDetail',
                'getRelatedItem.getInventoryItemDetail.location',
                'getRelatedItem.getInventoryItemDetail.getAllFromStockMoves',
                'getRelatedItem.getInventoryItemDetail.pack_size'
            ])->whereSlug($slug)->first();
            if ($row && (isset($permission[$pmodule . '___edit-invoice']) || $permission == 'superadmin')) {
                $customer_list = WaCustomer::where('is_blocked', 0)->pluck('customer_name', 'customer_name')->toArray();
                $getLoggeduserProfile = getLoggeduserProfile();
                $title = 'Edit ' . $this->title;
                $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                $model = $this->model;
                return view('admin.internalrequisition.edit', compact('title', 'model', 'breadcum', 'row', 'getLoggeduserProfile', 'customer_list', 'editPermission', 'permission'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }


    public function update(Request $request, $slug)
    {
        try {
            if (!$request->ajax()) {
                throw new Exception("Un-Authorized Request");
            }

            $validator = Validator::make($request->all(), [
                'requisition_date' => 'required|date',
                'request_type' => 'required|in:save,send_request',
                'customer' => 'required|string|min:1',
                'item_id' => 'array',
                'item_id.*' => 'required|exists:wa_inventory_items,id',
                'item_selling_price.*' => 'required|numeric'
            ], [
                'item_id.*' => 'Item',
                'item_quantity.*' => 'Quantity',
                'item_selling_price.*' => 'Selling Price',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'result' => 0]);
            }

            $inventory = WaInventoryItem::select([
                '*',
                DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.wa_location_and_store_id = wa_inventory_items.store_location_id AND wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
            ])->with(['getTaxesOfItem'])->whereIn('id', $request->item_id)->get();
            if (count($request->item_id) == 0 || count($inventory) == 0) {
                throw new \Exception("No inventory item selected");
            }
            $used_limit = 0;
            $credit_limit = 0;
            $customer = \App\Model\WaCustomer::where('id', $request->customer)->first();

            if ($customer) {
                if ($customer->is_blocked == 1) {
                    return response()->json(['errors' => [
                        'customer' => ["Your account has been temporarily blocked from invoice processing, kindly contact the administrator"]
                    ], 'result' => 0]);
                }
                $credit_limit = $customer->credit_limit ?? 0;
                $used_limit = \App\Model\WaDebtorTran::where('wa_customer_id', @$customer->id)->sum('amount');
                if (!$customer->route_id) {
                    return response()->json(['errors' => [
                        'customer' => ["Account route not found"]
                    ], 'result' => 0]);
                }
            }

            if ($request->request_type == 'send_request') {
                $totalinventorycost = 0;
                foreach ($inventory as $key => $value) {
                    $qoh = WaStockMove::where('stock_id_code', $value->stock_id_code)->where('wa_location_and_store_id', getLoggeduserProfile()->wa_location_and_store_id)->sum('qauntity');

                    if ($qoh < $request->item_quantity[$value->id]) {
                        return response()->json(['errors' => [
                            'item_quantity.' . $value->id => ["Available quantity is not enough"]
                        ], 'result' => 0]);
                    }
                    if (!$request->item_selling_price[$value->id] || $value->standard_cost > $request->item_selling_price[$value->id]) {
                        return response()->json([
                            'result' => 0,
                            'errors' => ['item_selling_price.' . $value->id => ['Selling price must be greater than or equal to standard cost']]
                        ]);
                    }
                    if ($value->block_this == 1) {
                        return response()->json([
                            'result' => 0,
                            'errors' => ['item_id.' . $value->id => ['The product has been blocked from sale due to a change in standard cost']]
                        ]);
                    }
                    $totalinventorycost += @$request->item_selling_price[$value->id] * (@$request->item_quantity[$value->id]);
                }
                $available_limit = $credit_limit - $used_limit;
                if ($available_limit < $totalinventorycost) {
                    throw new \Exception("You cannot process the Invoice as it will exceed your allowed Credit Limit");
                }
            }

            $row = WaInternalRequisition::where('id', $request->id)->first();
            if (!$row || !in_array($row->status, ['UNAPPROVED', 'APPROVED'])) {
                throw new \Exception("Something went wrong!");
            }
            // return response()->json(['result'=>-1,'message'=>'Something went wrong']);
            $check = DB::transaction(function () use ($request, $inventory, $row, $customer) {
                // $series_module = WaNumerSeriesCode::where('module','INTERNAL REQUISITIONS')->first();
                $getLoggeduserProfile = getLoggeduserProfile();

                // $row->requisition_no= $request->requisition_no;
                $row->restaurant_id = $getLoggeduserProfile->restaurant_id;
                $row->wa_department_id = $getLoggeduserProfile->wa_department_id;
                $row->user_id = $getLoggeduserProfile->id;
                $row->to_store_id = getLoggeduserProfile()->wa_location_and_store_id;
                // $row->requisition_date = $request->requisition_date;
                $row->name = $request->name;
                $row->vehicle_register_no = NULL; //$request->vehicle_reg_no;
                //                $row->route_id = $request->route;
                $row->invoice_type = "Backend";
                //                $route = Route::where('id', $request->route)->first();
                //                $row->route = @$route->route_name;
                $row->customer = $request->customer;
                // $customer = WaCustomer::where('customer_name', $request->customer)->where('is_blocked',0)->first();
                $row->customer_id = @$customer->id;
                $row->save();
                $childs = [];
                WaInternalRequisitionItem::where('wa_internal_requisition_id', $row->id)->delete();
                foreach ($inventory as $key => $value) {
                    $quantity = @$request->item_quantity[$value->id];
                    $originalPrice = @$request->item_selling_price[$value->id];
                    
                    // Check for discount bands
                    $discountBand = DiscountBand::where('inventory_item_id', $value->id)
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
                    
                    $discountAmount = 0;
                    $finalPrice = $originalPrice;
                    
                    if ($discountBand) {
                        $discountAmount = $discountBand->discount_amount; // Per unit discount
                        $finalPrice = max(0, $originalPrice - $discountBand->discount_amount);
                    }
                    
                    // Check for Buy X Get Y promotions
                    $promotion = ItemPromotion::where('inventory_item_id', $value->id)
                        ->where('status', 'active')
                        ->whereNotNull('promotion_item_id')
                        ->where(function ($query) {
                            $today = \Carbon\Carbon::today();
                            $query->where('from_date', '<=', $today)
                                ->where(function ($subQuery) use ($today) {
                                    $subQuery->where('to_date', '>=', $today)
                                             ->orWhereNull('to_date');
                                });
                        })
                        ->first();
                        
                    if ($promotion) {
                        $promotionBatches = floor($quantity / (float)$promotion->sale_quantity);
                        if ($promotionBatches > 0) {
                            $promotionQty = $promotionBatches * $promotion->promotion_quantity;
                            $promotionItem = WaInventoryItem::find($promotion->promotion_item_id);
                            
                            if ($promotionItem) {
                                // Add free item to childs array
                                $childs[] = [
                                    'wa_internal_requisition_id' => $row->id,
                                    'wa_inventory_item_id' => $promotionItem->id,
                                    'quantity' => $promotionQty,
                                    'standard_cost' => $promotionItem->standard_cost,
                                    'selling_price' => 0, // Free item
                                    'total_cost' => 0, // Free item
                                    'tax_manager_id' => $promotionItem->tax_manager_id,
                                    'vat_rate' => 0,
                                    'vat_amount' => 0,
                                    'total_cost_with_vat' => 0,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'store_location_id' => $getLoggeduserProfile->store_location_id,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                    'hs_code' => $promotionItem->hs_code,
                                    'discount' => 0,
                                ];
                                
                                \Log::info("Credit Invoice Update: Free item added", [
                                    'promotion_item_id' => $promotionItem->id,
                                    'promotion_qty' => $promotionQty,
                                    'customer_bought' => $quantity,
                                    'promotion_batches' => $promotionBatches
                                ]);
                            }
                        }
                    }
                    
                    $vat_rate = 0;
                    $vat_amount = 0;
                    $totalcost = $finalPrice * $quantity;
                    if ($value->tax_manager_id && $value->getTaxesOfItem) {
                        $vat_rate = $value->getTaxesOfItem->tax_value;
                        $vat_amount = $totalcost - (($totalcost * 100) / ($vat_rate + 100));
                    }
                    $childs[] = [
                        'wa_internal_requisition_id' => $row->id,
                        'wa_inventory_item_id' => $value->id,
                        'quantity' => $quantity,
                        'standard_cost' => $value->standard_cost,
                        'selling_price' => $originalPrice, // Store original price
                        'total_cost' => $totalcost,
                        'vat_rate' => $vat_rate,
                        'tax_manager_id' => $value->tax_manager_id,
                        'vat_amount' => $vat_amount,
                        'total_cost_with_vat' => $totalcost,
                        'created_at' => date('Y-m-d H:i:s'),
                        'store_location_id' => $value->store_location_id,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'discount' => $discountAmount, // Per unit discount
                    ];
                }
                WaInternalRequisitionItem::insert($childs);
                $row->status = 'UNAPPROVED';
                $row->save();
                if ($request->request_type == 'send_request') {
                    addInternalRequisitionPermissions($row->id, $row->wa_department_id);
                }
                return $row;
            });
            if ($check) {
                if (!isset($request->from)) {
                    $location = route('sales-invoice.index') . getReportDefaultFilterForTrialBalance();
                } else {
                    $location = route('confirm-invoice.index') . getReportDefaultFilterForTrialBalance();
                }
                if ($request->request_type == 'send_request') {
                    $message = 'Request Sent Successfully.';
                } else {
                    // $location = route('sales-invoice.edit',$check->slug);
                    $message = 'Sales Updated Successfully';
                }
                return response()->json(['result' => 1, 'message' => $message, 'location' => $location]);
            }
            return response()->json(['result' => -1, 'message' => 'Something went wrong']);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }


    public function destroy($slug)
    {
        try {
            $a = DB::transaction(function () use ($slug) {
                $row = WaInternalRequisition::whereSlug($slug)->first();
                if ($row->id) {
                    WaInternalRequisitionItem::where('wa_internal_requisition_id', $row->id)->delete();
                    $row->delete();
                    return true;
                }
                return false;
            });
            if (!$a) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            Session::flash('success', 'Deleted successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function getDapartments(Request $request)
    {
        $rows = WaDepartment::where('restaurant_id', $request->branch_id)->orderBy('department_name', 'asc')->get();
        $data = '<option  value="">Please select department</option>';
        foreach ($rows as $row) {
            $data .= '<option  value="' . $row->id . '">' . $row->department_name . '</option>';
        }

        return $data;
    }

    public function getItems(Request $request)
    {
        $rows = WaInventoryItem::where('wa_inventory_category_id', $request->selected_inventory_category)->orderBy('title', 'asc')->get();
        $data = '<option  value="">Please select item</option>';
        foreach ($rows as $row) {
            $data .= '<option  value="' . $row->id . '">' . $row->title . '</option>';
        }

        return $data;
    }

    public function getItemDetail(Request $request)
    {
        $rows = WaInventoryItem::where('id', $request->selected_item_id)->first();

        if ($rows->minimum_order_quantity < 1) {
            $rows->minimum_order_quantity = 1;
        }


        return json_encode(['stock_id_code' => $rows->stock_id_code, 'unit_of_measure' => $rows->wa_unit_of_measure_id ? $rows->wa_unit_of_measure_id : '', 'minimum_order_quantity' => $rows->minimum_order_quantity]);
    }

    public function deletingItemRelation($purchase_no, $id)
    {
        try {
            WaInternalRequisitionItem::whereId($id)->delete();


            Session::flash('success', 'Deleted successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function downloadPrint($requisition_no)
    {

        $row = WaInternalRequisition::where('requisition_no', $requisition_no)->first();

        $pdf = PDF::loadView('admin.salesinvoice.print', compact('row'));
        return $pdf->download($row . '.pdf');
    }


    public function editPurchaseItem($requisition_no, $id)
    {
        try {

            $row = WaInternalRequisition::where('requisition_no', $requisition_no)
                ->whereHas('getRelatedItem', function ($sql_query) use ($id) {
                    $sql_query->where('id', $id);
                })
                ->first();
            if ($row) {

                $title = 'Edit ' . $this->title;
                $breadcum = [$this->title => route($this->model . '.index'), $row->purchase_no => '', 'Edit' => ''];
                $model = $this->model;


                $form_url = [$model . '.updatePurchaseItem', $row->getRelatedItem->find($id)->id];
                return view('admin.internalrequisition.editItem', compact('title', 'model', 'breadcum', 'row', 'id', 'form_url'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }

    public function updatePurchaseItem(Request $request, $id)
    {

        try {


            $item = WaInternalRequisitionItem::where('id', $id)->first();

            $item->wa_inventory_item_id = (string)$request->wa_inventory_item_id;
            $item->quantity = $request->quantity;
            $item->note = $request->note;
            $item_detail = WaInventoryItem::where('id', $request->wa_inventory_item_id)->first();
            $item->standard_cost = $item_detail->standard_cost;
            $item->total_cost = $item_detail->standard_cost * $request->quantity;
            $vat_rate = 0;
            $vat_amount = 0;
            if ($item_detail->tax_manager_id && $item_detail->getTaxesOfItem) {
                $vat_rate = $item_detail->getTaxesOfItem->tax_value;
                if ($item->total_cost > 0) {
                    $vat_amount = ($item_detail->getTaxesOfItem->tax_value * $item->total_cost) / 100;
                }
            }
            $item->vat_rate = $vat_rate;
            $item->vat_amount = $vat_amount;
            $item->total_cost_with_vat = $item->total_cost + $vat_amount;
            $item->save();
            Session::flash('success', 'Record updated successfully.');
            return redirect()->route($this->model . '.edit', $item->getInternalPurchaseId->slug);
        } catch (\Exception $e) {
            dd($e);
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }

    public function getItemQohAjax(Request $request)
    {
        $item_id = $request->item_id;
        $location_id = $request->location_id;
        $quantity = '';
        if (!empty($item_id) && !empty($location_id)) {
            $inventory_items_row = \App\Model\WaInventoryItem::where('id', $item_id)->first();
            if (!empty($inventory_items_row->stock_id_code)) {
                $quantity = getItemAvailableQuantity($inventory_items_row->stock_id_code, $location_id);
            }
        }
        echo json_encode(['quantity' => $quantity]);
        die;
    }


    public function checkQuantity($locationid, $itemid, $qty)
    {
        try {
            $qtyOnHand = WaStockMove::where('wa_location_and_store_id', $locationid)->where('stock_id_code', $itemid)->sum('qauntity');
            // echo $qtyOnHand; die;
            $item = WaInventoryItem::select('stock_id_code', 'id')->where('stock_id_code', $itemid)->first();

            $item_id = $item->id;


            $myqty = $qty;
            $qtyOnHand = $qtyOnHand;
            if ($myqty <= $qtyOnHand) {
                return '0';
            } else {

                return '1';
            }
        } catch (\Exception $e) {

            return '1';
        }
    }


    public function getInventryItemDetails(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $editPermission = '';
        if (!isset($permission[$pmodule . '___edit-values']) && $permission != 'superadmin') {
            $editPermission = 'readonly';
        }
        $data = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
        ])->with(['getTaxesOfItem', 'pack_size', 'location'])->where('id', $request->id)->first();
        $view = '';
        if ($data) {
            if ($data->quantity == NULL || $data->quantity <= 0) {
                return response()->json(['result' => -1, 'message' => 'This item quantity is less than 0, item not selectable']);
            }
            $view .= '<tr>                                      
            <td>
                <input type="hidden" name="item_id[' . $data->id . ']" class="itemid" value="' . $data->id . '">
                <input style="padding: 3px 3px;"  type="text" class="testIn form-control" value="' . $data->stock_id_code . '">
                <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
            </td>
            <td><input style="padding: 3px 3px;" readonly type="text" name="item_description[' . $data->id . ']" data-id="' . $data->id . '"  class="form-control" value="' . $data->description . '"></td>
            <td>' . ($data->quantity ?? 0) . '</td>
            <td><input style="padding: 3px 3px;" readonly type="text" name="item_unit[' . $data->id . ']" data-id="' . $data->id . '"  class="form-control" value="' . ($data->pack_size->title ?? NULL) . '" readonly></td>
            <td><input style="padding: 3px 3px;" onkeyup="getTotal(this)" onchange="getTotal(this)"  type="text" name="item_quantity[' . $data->id . ']" data-id="' . $data->id . '"  class="quantity form-control" value="" required>
            <input type="hidden" value="' . $data->quantity . '" name="item_old_quantity[' . $data->id . ']">
            </td>
            <td><input style="padding: 3px 3px;" ' . $editPermission . ' onchange="getTotal(this)" onkeyup="getTotal(this)" type="text" name="item_selling_price[' . $data->id . ']" data-id="' . $data->id . '"  class="selling_price form-control send_me_to_next_item" value="' . $data->selling_price . '" readonly></td>';

            $view .= '<td><select class="form-control vat_list send_me_to_next_item" name="item_vat[' . $data->id . ']" ' . $editPermission . '>';
            $per = 0;
            $vat = 0.00;
            if ($data->getTaxesOfItem) {
                $view .= '<option value="' . $data->getTaxesOfItem->id . '" selected>' . $data->getTaxesOfItem->title . '</option>';
                $per = $data->getTaxesOfItem->tax_value;
                $vat = round($data->selling_price - (($data->selling_price * 100) / ($per + 100)), 2) * 0;
            }
            $view .= '</select>
            <input type="hidden" class="vat_percentage" value="' . $per . '"  name="item_vat_percentage[' . $data->id . ']">
            </td>';
            $view .= '<td><input style="padding: 3px 3px;" readonly onchange="getTotal(this)" onkeyup="getTotal(this)" type="text" name="item_discount_per[' . $data->id . ']" data-id="' . $data->id . '" class="discount_per form-control" value="0.00"></td>
            <td><input style="padding: 3px 3px;" readonly type="text" name="item_discount[' . $data->id . ']" data-id="' . $data->id . '"  class="discount form-control" value="0.00"></td>
           
            <td><span class="vat">' . $vat . '</span></td>
            <td><span class="total">' . ($data->selling_price * 0) . '</span></td>
            <td>
            <button type="button" class="btn btn-primary btn-sm deleteparent"><i class="fas fa-trash" aria-hidden="true"></i></button>
            </td>
            </tr>';
        }
        return response()->json($view);
    }

    public function searchInventory(Request $request)
    {
        $data = WaInventoryItem::select([
            'wa_inventory_items.*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id AND wa_stock_moves.wa_location_and_store_id = ' . ($request->store_location_id ?? 'wa_inventory_items.store_location_id') . ') as quantity'),
        ])
            ->join('pack_sizes', 'wa_inventory_items.pack_size_id', '=', 'pack_sizes.id')
//            ->where([['pack_sizes.can_order', 1], ['status', 1]])
            ->where(function ($q) use ($request) {
                if ($request->search) {
                    $q->where('wa_inventory_items.title', 'LIKE', "%$request->search%");
                    $q->orWhere('stock_id_code', 'LIKE', "%$request->search%");
                }
            })->where(function ($e) use ($request) {
                if ($request->store_c) {
                    $e->where('store_c_deleted', 0);
                }
            })
            ->whereHas('suppliers', function ($query) use ($request) {
                if ($request->supplier_id) {
                    $query->where('wa_suppliers.id', $request->supplier_id);
                }
            })
            ->limit(15)->get();

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
        ">';
        $view .= "<thead>";
        $view .= '<tr>';
        $view .= '<th style="width:20%">Code</th>';
        $view .= '<th style="width:70%">Description</th>';
        $view .= '<th style="width:10%">QOH</th>';
        $view .= '</tr>';
        $view .= '</thead>';
        $view .= "<tbody>";
        foreach ($data as $key => $value) {
            $view .= '<tr onclick="fetchInventoryDetails(this)" ' . ($key == 0 ? 'class="SelectedLi"' : NULL) . ' data-id="' . $value->id . '" data-title="' . $value->title . '(' . $value->stock_id_code . ')">';
            $view .= '<td style="width:20%">' . $value->stock_id_code . '</td>';
            $view .= '<td style="width:70%">' . $value->title . '</td>';
            // $view .= '<td style="width:10%">' . ($value->quantity ?? 0) . '</td>';
            $view .= '<td style="width:10%">' . ($value->quantity ?? 0) . '</td>';

            $view .= '</tr>';
        }
        $view .= '</tbody>';
        $view .= '</table>';
        return response()->json($view);
    }

    public function sendOTP()
    {
        $otp = rand(100000, 999999);
        $otpKey = 'admin_otp_' . auth()->id();
        Cache::put($otpKey, $otp, now()->addMinutes(5));

        $alert = Alert::where('alert_name', 'credit-sales-otp')->first();

        if ($alert instanceof Alert) {
            $recipientType = $alert->recipient_type;
            $recipientId = $alert->recipients;
            if ($recipientType === 'user') {
                $ids = explode(',', $alert->recipients);
                $recipients = \App\User::whereIn('id', $ids)->get();
            } else if ($recipientType === 'role') {
                // Fetch users with the specified role
                $roleids = explode(',', $alert->recipients);
                $recipients = User::whereIn('role_id', $roleids)->get();
            }

            if ($recipients) {
                foreach ($recipients as $recipient) {
                    $sms_msg = "Otp to start credit Sale: " . $otp;
                    $smsService = app(SmsService::class);
                    $smsService->sendMessage($sms_msg,$recipient->phone_number);
//                    (new InfoSkySmsService())->sendMessage($sms_msg, $recipient->phone_number);
                }
            }
        }

        return response()->json(['message' => 'OTP sent to the admin.' . $otp]);
    }
    public function sendOTPOver()
    {
        $otp = rand(100000, 999999);
        $otpKey = 'admin_otp_' . auth()->id();
        Cache::put($otpKey, $otp, now()->addMinutes(5));

        $alert = Alert::where('alert_name', 'credit-sales-otp')->first();

        if ($alert instanceof Alert) {
            $recipientType = $alert->recipient_type;
            $recipientId = $alert->recipients;
            if ($recipientType === 'user') {
                $ids = explode(',', $alert->recipients);
                $recipients = \App\User::whereIn('id', $ids)->get();
            } else if ($recipientType === 'role') {
                // Fetch users with the specified role
                $roleids = explode(',', $alert->recipients);
                $recipients = User::whereIn('role_id', $roleids)->get();
            }

            if ($recipients) {
                foreach ($recipients as $recipient) {
                    $sms_msg = "Otp to Authorize sale to a customer with unpaid invoices: " . $otp;
                    $smsService = app(SmsService::class);
                    $smsService->sendMessage($sms_msg,$recipient->phone_number);
                }
            }
        }

        return response()->json(['message' => 'OTP sent to the admin.' . $otp]);
    }

    public function verifyOTP(Request $request)
    {
        $otp = $request->input('otp');
        $otpKey = 'admin_otp_' . auth()->id();
        if (Cache::has($otpKey) && Cache::get($otpKey) == $otp) {
            Cache::forget($otpKey);
            session(['credit_otp_verified' => true]);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid OTP.']);
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
}
