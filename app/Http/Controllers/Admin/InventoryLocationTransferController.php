<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\PerformPostReturnActions;
use PDF;
use QrCode;
use Carbon\Carbon;
use App\Model\User;
use App\Model\Route;
use App\Model\WaEsd;
use App\ItemPromotion;
use App\Model\Setting;
use App\SalesmanShift;
use App\Model\WaGlTran;
use App\Model\Restaurant;
use App\Model\WaCustomer;
use App\Model\WaStockMove;
use App\Model\WaDebtorTran;
use App\Model\WaDepartment;
use App\Model\WaEsdDetails;
use App\Models\ReturnReason;
use Illuminate\Http\Request;
use App\Interfaces\SmsService;
use App\Model\WaInventoryItem;
use App\Model\WaRouteCustomer;
use App\Model\WaUnitOfMeasure;
use App\Model\WaNumerSeriesCode;
use App\Model\WaAccountingPeriod;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Model\WaExternalRequisition;
use App\Model\WaInternalRequisition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Model\WaExternalRequisitionItem;
use App\Model\WaInternalRequisitionItem;
use Illuminate\Support\Facades\Validator;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaInventoryLocationItemReturn;
use App\Model\WaInventoryLocationTransferItem;
use App\WaInventoryLocationTransferItemReturn;
use App\DiscountBand;

use App\Model\WaInventoryLocationUom;

class InventoryLocationTransferController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct(protected SmsService $smsService)
    {
        $this->model = 'transfers';
        $this->title = 'Transfers';
        $this->pmodule = 'transfers';
    }

    public function index(Request $request)
    {
        $title = $this->title;
        $model = $this->model;
        $user = getLoggeduserProfile();
        $permission = $this->mypermissionsforAModule();
        $user_permission = $this->myUserPermissionsforAModule();
        $pmodule = $this->pmodule;



        $authuser = Auth::user();
        $userwithrestaurants = $authuser->load('userRestaurent');
        $isAdmin = $authuser->role_id == 1;
        $permission = $this->mypermissionsforAModule();

        if ($isAdmin && !isset($permission['employees' . '___view_all_branches_data'])) {
            $branches = Restaurant::all();
        } else {
            $branches = Restaurant::where('id', $authuser->userRestaurent->id)->get();
        }

        if (isset($permission['print-invoice-delivery-note___view']) || $permission == 'superadmin') {
            $lists = WaInventoryLocationTransfer::latest();

            if ($request->has('start-date')) {
                $lists = $lists->whereDate('wa_inventory_location_transfers.created_at', '>=', Carbon::parse($request->input('start-date')));
            }

            if ($request->has('end-date')) {
                $lists = $lists->whereDate('wa_inventory_location_transfers.created_at', '<=', Carbon::parse($request->input('end-date')));
            }

            if ($request->has('route')) {
                $lists = $lists->where('wa_inventory_location_transfers.route_id', $request->route);
            }
            
            if ($request->has('salesman')) {
                $lists = $lists->where('wa_inventory_location_transfers.restaurant_id', $request->salesman);
            } else {

                if ($user->role_id == 4) {
                    $lists = $lists->where('wa_inventory_location_transfers.restaurant_id', $user->restaurant_id);
                }
            }

            $branchids = $branches->pluck('id')->toArray();
            $lists = $lists->select(
                'wa_inventory_location_transfers.*',
                'users.name as salesman',
                'wa_customers.customer_name as credit_customer',
                'wa_location_and_stores.location_name as store',
                'wa_route_customers.bussiness_name as name',
                'wa_internal_requisitions.status as  req_status',
                DB::RAW('(select wa_esd_details.description from wa_esd_details where wa_esd_details.invoice_number = wa_inventory_location_transfers.transfer_no ORDER BY id DESC limit 1) as esd_status'),
                DB::RAW('(select SUM(`total_cost_with_vat`) from wa_inventory_location_transfer_items where wa_inventory_location_transfer_items.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id) as transfer_total')
            )
                ->leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.requisition_no', '=', 'wa_inventory_location_transfers.transfer_no')
                ->leftJoin('wa_route_customers', 'wa_route_customers.id', '=', 'wa_internal_requisitions.wa_route_customer_id')
                ->leftJoin('wa_customers',  'wa_customers.id', '=', 'wa_internal_requisitions.customer_id')
                ->leftJoin('users', 'wa_inventory_location_transfers.user_id', '=', 'users.id')
                ->leftJoin('wa_location_and_stores', 'wa_inventory_location_transfers.to_store_location_id', '=', 'wa_location_and_stores.id')
                ->whereIn('wa_location_and_stores.wa_branch_id', $branchids);
            if($request->status && $request->status == 'paid'){
                $lists = $lists->where('wa_internal_requisitions.status', 'PAID');
            }
            if($request->status && $request->status == 'unpaid'){
                $lists = $lists->where('wa_internal_requisitions.status','<>', 'PAID');
            }
            $lists = $lists->orderBy('wa_inventory_location_transfers.created_at', 'desc')
                ->get();

            if ($isAdmin && !isset($permission['employees' . '___view_all_branches_data'])) {
                $routes = DB::table('routes')->select('id', 'route_name')->get();
            } else {
                $routes = DB::table('routes')->where('restaurant_id', $authuser->userRestaurent->id)->select('id', 'route_name')->get();
            }

            if ($request->get('manage-request') && $request->get('manage-request') == 'PDF') {
                $pdf = PDF::loadView('admin.inventorylocationtransfer.indexprint', compact('user', 'title', 'lists', 'model', 'pmodule', 'permission', 'request'));
                return $pdf->download('delivery_note_' . date('Y_m_d_h_i_s') . '.pdf');
            }
            if ($request->get('request') && $request->get('request') == 'PRINT') {
                return view('admin.inventorylocationtransfer.indexprint', compact('user', 'title', 'lists', 'model', 'pmodule', 'permission', 'request'));
            }

            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.inventorylocationtransfer.index', compact(
                'user',
                'title',
                'lists',
                'model',
                'breadcum',
                'pmodule',
                'permission',
                'user_permission',
                'routes',
                'user',
                'branches'
            ));
        } else {
            Session::flash('warning', pageRestrictedMessage());
            return redirect()->back();
        }
    }

    public function resign_esd($id)
    {
        $id = base64_decode($id);
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = 'sales-invoice';

        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Resign' => ''];
            $data = WaInventoryLocationTransfer::with(['getRelatedItem'])->where('id', $id)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            $esd_setting = Setting::whereSlug('esd-url')->first();
            $esd_url = $esd_setting->description;
            return view('admin.salesreceiablesreports.resign_esd', compact('data', 'title', 'model', 'breadcum', 'pmodule', 'permission', 'esd_url'));
        } else {
            Session::flash('warning', pageRestrictedMessage());
            return redirect()->back();
        }
    }

    public function invoiceResignEsd($id)
    {
        $id = base64_decode($id);
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Resign' => ''];
            $data = WaInternalRequisition::with(['getRelatedItem'])->where('id', $id)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            $esd_setting = Setting::whereSlug('esd-url')->first();
            $esd_url = $esd_setting->description;
            return view('admin.salesreceiablesreports.resign_esd', compact('data', 'title', 'model', 'breadcum', 'pmodule', 'permission', 'esd_url'));
        } else {
            Session::flash('warning', pageRestrictedMessage());
            return redirect()->back();
        }
    }

    public function resign_esd_post(Request $request, $id)
    {
        try {
            $invoice = WaInternalRequisition::with('getRelatedItem')->find($id);

            $settings = getAllSettings();
            $clientPin = $settings['PIN_NO'];
            $esdUrl = $settings['ESD_URL'];
            $apiUrl = "$esdUrl/api/sign?invoice+1";
            $vatAmount = 0;

            // $vatAmount = DB::table('wa_internal_requisition_items')->where('wa_internal_requisition_id', $invoice->id)->sum('vat_amount');
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

            $grandTotal = 0;
            $taxManagers = DB::table('tax_managers')->select('id', 'title', 'tax_value')->get();
            foreach ($invoice->getRelatedItem as $item) {
                $itemTotal = $item->selling_price * $item->quantity;
                $grandTotal += $itemTotal;

                $inventoryItem = DB::table('wa_inventory_items')->find($item->wa_inventory_item_id);
                $taxManager = $taxManagers->where('id', $inventoryItem->tax_manager_id)->first();
                if ($taxManager) {
                    $vatRate = (float)$taxManager->tax_value;
                    $vatAmount += ($vatRate / (100 + $vatRate)) * $itemTotal;
                }

                $itemTotal = manageAmountFormat($itemTotal);
                $item->selling_price = manageAmountFormat($item->selling_price);
                $line = "{$inventoryItem->title} {$item->quantity} {$item->selling_price} {$itemTotal}";
                if ($inventoryItem->hs_code) {
                    $line = "{$inventoryItem->hs_code} " . $line;
                }

                $payload['items_list'][] = $line;
            }

            $payload['tax_total'] = number_format($vatAmount, 2);
            $payload['grand_total'] = number_format($grandTotal, 2);
            $payload['net_subtotal'] = number_format($grandTotal - $vatAmount, 2);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ZxZoaZMUQbUJDljA7kTExQ==',
            ])->post($apiUrl, $payload);

            $responseData = json_decode($response->body(), true);
            if ($response->ok()) {
                $esdRecord = WaEsdDetails::where('invoice_number', $invoice->requisition_no)->first();
                $esdRecord->cu_serial_number = $responseData['cu_serial_number'];
                $esdRecord->cu_invoice_number = $responseData['cu_invoice_number'];
                $esdRecord->verify_url = $responseData['verify_url'] ?? null;
                $esdRecord->description = $responseData['description'] ?? null;
                $esdRecord->status = 1;
                $esdRecord->save();

                return redirect()->route('transfers.index')->with('success', 'Invoice re-signed successfully');
            } else {
                return redirect()->back()->withErrors(['error' => "Resigning failed with {$response->body()}. " . json_encode($payload)]);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function invoiceResignEsdPost(Request $request, $id)
    {
        try {
            $invoice = WaInternalRequisition::find($id);
            $settings = getAllSettings();
            $clientPin = $settings['PIN_NO'];
            $esdUrl = $settings['ESD_URL'];
            $apiUrl = "$esdUrl/api/sign?invoice+1";
            $vatAmount = 0;

            // $vatAmount = DB::table('wa_internal_requisition_items')->where('wa_internal_requisition_id', $invoice->id)->sum('vat_amount');
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

            $grandTotal = 0;
            $taxManagers = DB::table('tax_managers')->select('id', 'title', 'tax_value')->get();
            foreach ($invoice->getRelatedItem as $item) {
                $itemTotal = $item->selling_price * $item->quantity;
                $grandTotal += $itemTotal;

                $inventoryItem = DB::table('wa_inventory_items')->find($item->wa_inventory_item_id);
                $taxManager = $taxManagers->where('id', $inventoryItem->tax_manager_id)->first();
                if ($taxManager) {
                    $vatRate = (float)$taxManager->tax_value;
                    $vatAmount += ($vatRate / (100 + $vatRate)) * $itemTotal;
                }

                $itemTotal = manageAmountFormat($itemTotal);
                $item->selling_price = manageAmountFormat($item->selling_price);
                $line = "{$inventoryItem->slug} {$item->quantity} {$item->selling_price} {$itemTotal}";
                if ($inventoryItem->hs_code) {
                    $line = "{$inventoryItem->hs_code} " . $line;
                }

                $payload['items_list'][] = $line;
            }

            $payload['tax_total'] = number_format($vatAmount, 2);
            $payload['grand_total'] = number_format($grandTotal, 2);
            $payload['net_subtotal'] = number_format($grandTotal - $vatAmount, 2);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ZxZoaZMUQbUJDljA7kTExQ==',
            ])->post($apiUrl, $payload);

            $responseData = json_decode($response->body(), true);
            if ($response->ok()) {
                $esdRecord = WaEsdDetails::where('invoice_number', $invoice->requisition_no)->first();
                $esdRecord->cu_serial_number = $responseData['cu_serial_number'];
                $esdRecord->cu_invoice_number = $responseData['cu_invoice_number'];
                $esdRecord->verify_url = $responseData['verify_url'] ?? null;
                $esdRecord->description = $responseData['description'] ?? null;
                $esdRecord->status = 1;
                $esdRecord->save();

                Session::flash('success', 'Invoice resigned successfully');
                return redirect("admin/transfers" . getReportDefaultFilterForTrialBalance());
            } else {
                return redirect()->back()->withErrors(['error' => "Resigning failed with {$response->body()}. " . json_encode($payload)]);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function create()
    {
        if (getLoggeduserProfile()->wa_department_id && getLoggeduserProfile()->restaurant_id) {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___add']) || $permission == 'superadmin') {
                $title = 'Add ' . $this->title;
                $model = $this->model;
                $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
                return view('admin.inventorylocationtransfer.create', compact('title', 'model', 'breadcum'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } else {
            Session::flash('warning', 'Please update your branch and department');
            return redirect()->back();
        }
    }


    public function print(Request $request)
    {
        $list = WaInventoryLocationTransfer::with(['getRelatedItem', 'getRelatedItem.getInventoryItemDetail'])->where('transfer_no', $request->transfer_no)->first();
        $ref = $request->ref;
        $name = User::where('id', $list->user_id)->first();
        $prep_by = $name->name;
        if ($list) {
            $list->print_count++;
            $list->save();

            $is_print = 1;
            $esd_details = WaEsdDetails::where('invoice_number', $request->transfer_no)->first();
            return view('admin.inventorylocationtransfer.print', compact('list', 'prep_by', 'esd_details', 'is_print', 'ref'));
        }
        Session::flash('warning', 'Invalid request');
        return redirect()->back();
    }

    public function print_return(Request $request)
    {
        // $data =  WaInventoryLocationTransferItem::with(['getInventoryItemDetail','getTransferLocation','returned_by','getTransferLocation.toStoreDetail'])
        // ->where('is_return',1)->where('return_grn',$request->transfer_no)->get();
        // return view('admin.inventorylocationtransfer.print_return',compact('title','data','request'));       

        $data = \App\Model\WaInventoryLocationItemReturn::with(['item_parent', 'item_parent.getInventoryItemDetail', 'getTransferLocation', 'returned_by', 'getTransferLocation.toStoreDetail'])
            ->where('return_grn', $request->transfer_no)->get();
        \App\Model\WaInventoryLocationItemReturn::where('return_grn', $request->transfer_no)->update(['print_count' => DB::RAW("print_count + 1")]);
        return view('admin.inventorylocationtransfer.print_return', compact('title', 'data', 'request'));
    }

    public function refreshstockmoves()
    {

        $list = WaInventoryLocationTransfer::select('transfer_no', 'shift_id')->get();
        foreach ($list as $key => $val) {
            WaStockMove::where('document_no', $val->transfer_no)->update(['shift_id' => $val->shift_id]);
        }
        Session::flash('success', 'Refreshed successfully.');
        return redirect()->back();
    }

    public function store(Request $request)
    {
        //   echo "<pre>"; print_r($request->all()); die;
        try {
            $validator = Validator::make($request->all(), [

                'transfer_no' => 'required|unique:wa_inventory_location_transfers',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {
                $checkexist = WaInventoryLocationTransfer::where('transfer_no', $request->transfer_no)->count();
                if ($request->has('type')) {
                    $itemcode = array_filter($request->get('item_code'));
                    $checkitem = WaInventoryItem::whereIn('stock_id_code', $itemcode)->count();
                    if (count($itemcode) != $checkitem) {
                        Session::flash('warning', 'Please enter a valid item code.');
                        return redirect()->back();
                    }
                }
                if ($checkexist > 0) {
                    Session::flash('warning', 'Delivery Note No is already taken.');
                    return redirect()->back();
                }

                $row = new WaInventoryLocationTransfer();
                $row->transfer_no = $request->transfer_no;
                $row->transfer_date = $request->transfer_date;
                $row->restaurant_id = getLoggeduserProfile()->restaurant_id;
                $row->wa_department_id = getLoggeduserProfile()->wa_department_id;
                $row->user_id = getLoggeduserProfile()->id;
                $row->from_store_location_id = $request->from_store_location_id;
                $row->to_store_location_id = $request->to_store_location_id;
                $row->vehicle_register_no = $request->vehicle_reg_no;
                $row->route = $request->route;
                $row->customer = $request->customer;
                $row->save();

                foreach ($request->qty as $key => $val) {
                    if ($val > 0) {
                        if ($request->has('type')) {
                            $itemcode = $request->get('item_code');
                            $key = WaInventoryItem::where('stock_id_code', $itemcode[$key])->first()->id;
                        } else {
                            $key = $key;
                        }


                        $item = new WaInventoryLocationTransferItem();
                        $item->wa_inventory_location_transfer_id = $row->id;
                        $item->wa_inventory_item_id = $key;
                        $item->quantity = $val;
                        $item->note = "";
                        $item_detail = WaInventoryItem::where('id', $key)->first();
                        $item->standard_cost = $item_detail->standard_cost;
                        $item->total_cost = $item_detail->standard_cost * $val;


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
                    }
                }
                updateUniqueNumberSeries('TRAN', $request->transfer_no);
                Session::flash('success', 'Record added successfully.');
                return redirect()->route($this->model . '.edit', $row->slug);
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }

    public function checkQtyWithHandForAll($inventoryTransfer)
    {

        $item_withqty = [];
        foreach ($inventoryTransfer->getRelatedItem as $item_required) {

            if (isset($item_withqty[$item_required->wa_inventory_item_id])) {
                $item_withqty[$item_required->wa_inventory_item_id] = $item_withqty[$item_required->wa_inventory_item_id] + $item_required->quantity;
            } else {
                $item_withqty[$item_required->wa_inventory_item_id] = $item_required->quantity;
            }
        }


        $error = '';
        foreach ($item_withqty as $key => $value) {


            $item = WaInventoryItem::select('stock_id_code', 'id')->where('id', $key)->first();
            $qtyOnHand = WaStockMove::where('wa_location_and_store_id', $inventoryTransfer->from_store_location_id)
                ->where('stock_id_code', $item->stock_id_code)
                ->sum('qauntity');
            if ($value <= $qtyOnHand) {
            } else {
                if ($error == '') {
                    $error = $item->stock_id_code . ' have only ' . $qtyOnHand;
                } else {
                    $error .= ', ' . $item->stock_id_code . ' have only ' . $qtyOnHand;
                }
            }
        }
        if ($error == '') {
            return 'ok';
        } else {
            return 'ok';
            //            return $error;
        }
    }

    public function processTransfer($transfer_no)
    {
        try {
            $checkcount = WaInventoryLocationTransfer::where('status', 'PENDING')->where('transfer_no', $transfer_no)->count();

            if ($checkcount > 1) {
                Session::flash('warning', 'Already exist transfer no.');
                return redirect()->back();
            }

            $row = WaInventoryLocationTransfer::where('status', 'PENDING')->where('transfer_no', $transfer_no)->first();
            if ($row) {
                $qtyStatus = $this->checkQtyWithHandForAll($row);
                if ($qtyStatus == 'ok') {
                    $row->status = 'COMPLETED';
                    $row->save();
                    $internal_requisition_row = WaInventoryLocationTransfer::where('transfer_no', $transfer_no)->first();
                    $series_module = WaNumerSeriesCode::where('module', 'TRAN')->first();
                    $intr_smodule = WaNumerSeriesCode::where('module', 'TRAN')->first();
                    $WaAccountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
                    $dateTime = date('Y-m-d H:i:s');
                    foreach ($row->getRelatedItem as $item) {
                        $delivery_quantity = 'delivered_quantity_' . $item;
                        $from_entry = new WaStockMove();
                        $from_entry->user_id = getLoggeduserProfile()->id;
                        $from_entry->wa_inventory_location_transfer_id = $row->id;
                        $from_entry->restaurant_id = $row->restaurant_id;
                        $from_entry->wa_location_and_store_id = $row->from_store_location_id;
                        $from_entry->stock_id_code = $item->getInventoryItemDetail->stock_id_code;
                        $from_entry->wa_inventory_item_id = $item->item;
                        $from_entry->qauntity = '-' . $item->quantity;
                        $from_entry->standard_cost = $item->standard_cost;
                        $from_entry->price = $item->standard_cost;
                        $from_entry->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                        $from_entry->document_no = $transfer_no;
                        $from_entry->save();

                        $to_entry = new WaStockMove();
                        $to_entry->user_id = getLoggeduserProfile()->id;
                        $to_entry->wa_inventory_location_transfer_id = $row->id;
                        $to_entry->restaurant_id = $row->restaurant_id;
                        $to_entry->wa_location_and_store_id = $row->to_store_location_id;
                        $to_entry->stock_id_code = $item->getInventoryItemDetail->stock_id_code;
                        $to_entry->wa_inventory_item_id = $item->getInventoryItemDetail->id;
                        $to_entry->qauntity = $item->quantity;
                        $to_entry->standard_cost = $item->standard_cost;
                        $to_entry->price = $item->standard_cost;
                        $to_entry->document_no = $transfer_no;
                        $to_entry->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                        $to_entry->save();


                        // $dr =  new WaGlTran();
                        // //$dr->wa_internal_requisition_id = $internal_requisition_row->id;
                        // $dr->grn_type_number = $series_module->type_number;
                        // $dr->grn_last_used_number = $series_module->last_number_used;
                        // $dr->transaction_type = $intr_smodule->description;
                        // $dr->transaction_no =  $internal_requisition_row->transfer_no;
                        // $dr->trans_date = $dateTime;
                        // $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;
                        // $dr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                        // $dr->account = $item->getInventoryItemDetail->getInventoryCategoryDetail->getStockGlDetail->account_code;
                        // $dr->amount = '-' . ($item->standard_cost * $item->quantity);
                        // $dr->narrative = $internal_requisition_row->transfer_no . '/' . $item->getInventoryItemDetail->stock_id_code . '/' . $item->getInventoryItemDetail->title . '/' . $item->standard_cost . '@' . $item->quantity;
                        // $dr->save();


                        // $dr =  new WaGlTran();
                        // //$dr->wa_internal_requisition_id = $internal_requisition_row->id;
                        // $dr->grn_type_number = $series_module->type_number;
                        // $dr->grn_last_used_number = $series_module->last_number_used;
                        // $dr->transaction_type = $intr_smodule->description;
                        // $dr->transaction_no =  $internal_requisition_row->transfer_no;
                        // $dr->trans_date = $dateTime;
                        // $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;
                        // $dr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                        // $dr->account = $item->getInventoryItemDetail->getInventoryCategoryDetail->getIssueGlDetail->account_code;
                        // $camount = $item->standard_cost * $item->quantity;
                        // $dr->amount = $camount;
                        // $dr->narrative = $internal_requisition_row->transfer_no . '/' . $item->getInventoryItemDetail->stock_id_code . '/' . $item->getInventoryItemDetail->title . '/' . $item->standard_cost . '@' . $item->quantity;
                        // $dr->save();


                    }
                    Session::flash('success', 'Transfered successfully.');
                    return redirect()->route($this->model . '.index');
                } else {
                    Session::flash('warning', $qtyStatus);
                    return redirect()->back();
                }
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
        $row = WaInventoryLocationTransfer::with([
            'getRelatedItem',
            'getRelatedItem.getInventoryItemDetail',
            'getRelatedItem.getInventoryItemDetail.getInventoryCategoryDetail',
            'getRelatedItem.getInventoryItemDetail.getUnitOfMeausureDetail',
        ])->whereSlug($slug)->first();
        if ($row) {
            $title = 'View ' . $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Show' => ''];
            $model = $this->model;
            return view('admin.inventorylocationtransfer.show', compact('title', 'model', 'breadcum', 'row'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function edit($slug)
    {
        try {
            $row = WaInventoryLocationTransfer::whereSlug($slug)->first();
            if ($row) {
                $title = 'Edit ' . $this->title;
                $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                $model = $this->model;
                return view('admin.inventorylocationtransfer.edit', compact('title', 'model', 'breadcum', 'row'));
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
            $row = WaInventoryLocationTransfer::whereSlug($slug)->first();
            $validator = Validator::make($request->all(), [
                'transfer_no' => 'required|unique:wa_inventory_location_transfers,transfer_no,' . $row->id,
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {
                if ($request->has('type')) {
                    $itemcode = array_filter($request->get('item_code'));
                    $checkitem = WaInventoryItem::whereIn('stock_id_code', $itemcode)->count();
                    if (count($itemcode) != $checkitem) {
                        Session::flash('warning', 'Please enter a valid item code.');
                        return redirect()->back();
                    }
                }

                foreach ($request->qty as $key => $val) {
                    if ($val > 0) {

                        if ($request->has('type')) {
                            $itemcode = $request->get('item_code');
                            $key = WaInventoryItem::where('stock_id_code', $itemcode[$key])->first()->id;
                        } else {
                            $key = $key;
                        }

                        $item = new WaInventoryLocationTransferItem();
                        $item->wa_inventory_location_transfer_id = $row->id;
                        $item->wa_inventory_item_id = $key;
                        $item->quantity = $val;
                        $item->note = ""; //$request->note;
                        $item_detail = WaInventoryItem::where('id', $key)->first();
                        $item->standard_cost = $item_detail->standard_cost;
                        $item->total_cost = $item_detail->standard_cost * $val;
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
                    }
                }
                Session::flash('success', 'Record updated successfully.');
                return redirect()->route($this->model . '.edit', $row->slug);
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }


    public function destroy($slug)
    {
        try {
            WaInventoryLocationTransfer::whereSlug($slug)->delete();
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
        return json_encode(['stock_id_code' => $rows->stock_id_code, 'unit_of_measure' => $rows->wa_unit_of_measure_id ? $rows->wa_unit_of_measure_id : '', 'minimum_order_quantity' => $rows->minimum_order_quantity]);
    }

    public function deletingItemRelation($purchase_no, $id)
    {
        try {
            WaInventoryLocationTransferItem::whereId($id)->delete();
            Session::flash('success', 'Deleted successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function printToPdf($transfer_no)
    {
        $list = WaInventoryLocationTransfer::where('transfer_no', $transfer_no)->with(['get_customer', 'getRelatedItem.getInventoryItemDetail' => function ($query) {
            $query->orderBy('stock_id_code', 'DESC');
        }])->first();
        $name = User::where('id', $list->user_id)->first();
        $prep_by = $name->name;

        if (!$list) {
            Session::flash('warning', 'No Invoice Found');
            return redirect()->back();
        }
        $list->print_count++;
        $list->save();
        // $payment_code = WaInternalRequisition::where('requisition_no', $list->transfer_no)->first()->payment_code;
        $payment_code = substr($list->transfer_no, 4);
        $restaurant = Restaurant::find($list->restaurant_id);
        $itemsdata = WaInventoryLocationTransferItem::where('wa_inventory_location_transfer_id', $list->id)->with(['getInventoryItemDetail' => function ($query) {
            $query->orderBy('stock_id_code', 'DESC');
        }])->get();

        $customer_discount = ($list->customer_discount > 0) ? $list->customer_discount : 0;

        $esd_details = WaEsdDetails::where('invoice_number', $transfer_no)->first();
        $pdf = PDF::loadView('admin.inventorylocationtransfer.print', compact('list', 'itemsdata', 'prep_by', 'customer_discount', 'esd_details', 'payment_code', 'restaurant'));
        return $pdf->download('transfer_' . date('Y_m_d_h_i_s') . '.pdf');
        // return $pdf->stream('transfer_' . date('Y_m_d_h_i_s') . '.pdf');
    }

    public function printReturnToPdf($transfer_no)
    {
        $prep_by = getLoggeduserProfile()->name;
        $list = null;
        $returnRecords = [];
        $returnTotal = 0;
        $returnNumber = null;
        $returnsCount = DB::table('wa_inventory_location_transfer_item_returns')
            ->where('wa_inventory_location_transfer_item_returns.return_number', $transfer_no)->count();
        if ($returnsCount > 0) {
            $list = DB::table('wa_inventory_location_transfer_item_returns')
                ->where('wa_inventory_location_transfer_item_returns.return_number', $transfer_no)
                ->select(
                    'wa_inventory_items.title as item_name',
                    'wa_inventory_location_transfer_item_returns.created_at as return_date',
                    'wa_inventory_location_transfer_item_returns.updated_at as processing_date',
                    'wa_inventory_location_transfer_item_returns.return_number',
                    'wa_inventory_location_transfer_item_returns.return_quantity',
                    'wa_inventory_location_transfer_item_returns.received_quantity',
                    'users.name as initiator',
                    'wa_inventory_location_transfer_items.selling_price'
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->leftJoin('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->leftJoin('users', 'wa_inventory_location_transfer_item_returns.return_by', '=', 'users.id')
                ->get()
                ->map(function ($record) {
                    $record->total = $record->received_quantity * $record->selling_price;
                    return $record;
                });
        }
        if (!$list) {
            Session::flash('warning', 'No Invoice Found');
            return redirect()->back();
        }

        $pdf = PDF::loadView('admin.inventorylocationtransfer.print_return_back_end', compact('list', 'prep_by', 'transfer_no'));
        return $pdf->download('return_transfer_' . date('Y_m_d_h_i_s') . '.pdf');
    }


    public function editPurchaseItem($transfer_no, $id)
    {
        try {

            $row = WaInventoryLocationTransfer::where('transfer_no', $transfer_no)
                ->whereHas('getRelatedItem', function ($sql_query) use ($id) {
                    $sql_query->where('id', $id);
                })
                ->first();
            if ($row) {

                $title = 'Edit ' . $this->title;
                $breadcum = [$this->title => route($this->model . '.index'), $row->purchase_no => '', 'Edit' => ''];
                $model = $this->model;


                $form_url = [$model . '.updatePurchaseItem', $row->getRelatedItem->find($id)->id];
                return view('admin.inventorylocationtransfer.editItem', compact('title', 'model', 'breadcum', 'row', 'id', 'form_url'));
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


            $item = WaInventoryLocationTransferItem::where('id', $id)->first();

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
            return redirect()->route($this->model . '.edit', $item->getTransferLocation->slug);
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }


    public function checkQuantity(Request $request)
    {
        try {
            $qtyOnHand = WaStockMove::where('wa_location_and_store_id', $request->from_strore_location_id)->where('stock_id_code', $request->item_id)->sum('qauntity');
            // echo $qtyOnHand; die;
            $item = WaInventoryItem::select('stock_id_code', 'id')->where('stock_id_code', $request->item_id)->first();

            $item_id = $item->id;


            $myqty = $request->quantity;
            $qtyOnHand = $qtyOnHand;
            if ($myqty <= $qtyOnHand) {
                return '1';
            } else {

                return '1';
            }
        } catch (\Exception $e) {

            return '1';
        }
    }


    public function getManualItemsList(Request $request)
    {
        if ($request->has('type')) {
            $type = $request->get('type');
        } else {
            $type = '';
        }
        $view_data = view('admin.inventorylocationtransfer.manual_entry', compact('type'));
        return $view_data;
    }


    public function return_show($slug)
    {

        $user = getLoggeduserProfile();
        $permission = $this->mypermissionsforAModule();
        $returnReasons = ReturnReason::all();
        if (!isset($permission['print-invoice-delivery-note___return']) && $permission != 'superadmin') {
            Session::flash('warning', 'Restricted : You Don\'t have enough permissions');
            return redirect()->back();
        }

        $row = DB::table('wa_inventory_location_transfers')->where('wa_inventory_location_transfers.slug', $slug)
            ->select(
                'wa_inventory_location_transfers.id',
                'wa_inventory_location_transfers.slug',
                'wa_inventory_location_transfers.transfer_no',
                'wa_inventory_location_transfers.created_at',
                'wa_inventory_location_transfers.to_store_location_id',
                'wa_inventory_location_transfers.customer_id',
                'users.name as salesman',
                'users.id as salesman_id',
                'route_user.route_id',
            )
            ->leftJoin('users', 'wa_inventory_location_transfers.user_id', '=', 'users.id')
            ->leftJoin('route_user', 'route_user.user_id', '=', 'users.id')
            ->first();


        if ($row) {
            $invoiceItems = DB::table('wa_inventory_location_transfer_items')
                ->where('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', $row->id)
                ->whereNot('wa_inventory_location_transfer_items.selling_price', 0)
                ->select(
                    'wa_inventory_location_transfer_items.id',
                    'wa_inventory_location_transfer_items.quantity',
                    'wa_inventory_location_transfer_items.selling_price',
                    'wa_inventory_location_transfer_items.total_cost_with_vat',
                    'wa_inventory_items.id as inventory_item_id',
                    'wa_inventory_items.stock_id_code as item_code',
                    'wa_inventory_items.title as item_name',
                    'wa_unit_of_measures.title as bin',
                    'wa_internal_requisition_items.discount',


                )
                ->leftJoin('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->leftJoin('wa_inventory_location_uom', function ($join) use ($row) {
                    $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')->where('wa_inventory_location_uom.location_id', $row->to_store_location_id);
                })
                ->leftJoin('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
                ->leftJoin('wa_internal_requisition_items', 'wa_inventory_location_transfer_items.wa_internal_requisition_item_id', '=', 'wa_internal_requisition_items.id')
                ->distinct('wa_inventory_location_transfer_items.id')
                ->get()
                ->map(function ($record) {
                    $record->returned_quantity = $record->received_quantity = DB::table('wa_inventory_location_transfer_item_returns')->where('wa_inventory_location_transfer_item_id', $record->id)->sum('received_quantity');
                    $record->rejected_quantity = DB::table('wa_inventory_location_transfer_item_returns')->where('wa_inventory_location_transfer_item_id', $record->id)->sum('rejected_quantity');
                    $record->initiated_quantity = DB::table('wa_inventory_location_transfer_item_returns')
                        ->where('status', 'pending')
                        ->where('wa_inventory_location_transfer_item_id', $record->id)->sum('return_quantity');

                    return $record;
                });

            $title = 'Return ' . $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Return' => ''];
            $model = $this->model;
            return view('admin.inventorylocationtransfer.return_show', compact('title', 'model', 'breadcum', 'row', 'invoiceItems', 'returnReasons'));
        } else {
            Session::flash('warning', 'No Record found to Return');
            return redirect()->back();
        }
    }


    public function return_groups(Request $request)
    {
        $user = getLoggeduserProfile();
        $type = $request->type ?? 0;
        $d1 = $request->input('start_date') ?? \Carbon\Carbon::now()->toDateString();
        $d2 = $request->input('end_date') ?? \Carbon\Carbon::now()->toDateString();
        if ($request->type && $request->type == 3) {
            $returns = DB::table('wa_inventory_location_transfer_item_returns')
                ->whereBetween('wa_inventory_location_transfer_item_returns.created_at', [$d1 . ' 00:00:00', $d2 . ' 23:59:59'])
                ->where('wa_inventory_location_transfer_item_returns.status', '=', 'received')
                ->whereRaw('wa_inventory_location_transfer_item_returns.created_at > DATE_ADD(wa_inventory_location_transfer_items.created_at, INTERVAL 48 HOUR)')
                ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
                ->select(
                    'wa_inventory_location_transfer_item_returns.updated_at as invoice_date',
                    'wa_inventory_location_transfers.name as customer',
                    'wa_inventory_location_transfers.route as route',
                    'wa_inventory_location_transfers.route_id as route_id',
                    DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
                    DB::raw('DATE(wa_inventory_location_transfer_item_returns.created_at) as date')
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->join('wa_inventory_location_transfers', function ($join) use ($request, $d1, $d2) {
                    $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                    if ($request->route_id) {
                        $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                    }
                })
                ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                // ->groupBy('date')
                // ->groupBy('route')
                ->get();
        } else {

            $returns = DB::table('wa_inventory_location_transfer_item_returns')
                ->whereNot('wa_inventory_location_transfer_item_returns.status', '=', 'received')
                ->whereBetween('wa_inventory_location_transfer_item_returns.created_at', [$d1 . ' 00:00:00', $d2 . ' 23:59:59'])
                ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
                ->select(
                    'wa_inventory_location_transfer_item_returns.updated_at as invoice_date',
                    'wa_inventory_location_transfers.name as customer',
                    'wa_inventory_location_transfers.route as route',
                    'wa_inventory_location_transfers.route_id as route_id',
                    DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
                    DB::raw('DATE(wa_inventory_location_transfer_item_returns.created_at) as date')
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->join('wa_inventory_location_transfers', function ($join) use ($request, $d1, $d2) {
                    $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                    if ($request->route_id) {
                        $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                    }
                })
                ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->groupBy('date')
                ->groupBy('route');
            if ($request->type && $request->type == 1) {
                $returns = $returns->having(DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price)'), '>', 10000);
            } elseif ($request->type && $request->type == 2) {
                $returns = $returns->having(DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price)'), '>', 100000);
            } else {
                $returns = $returns;
            }
            $returns = $returns->get();
        }
        $title = 'Pending Returns Report';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];

        $model = 'return-confirm-report';

        $routes = DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.return_groups', compact('title', 'model', 'breadcum', 'returns', 'routes', 'd1', 'd2', 'type'));
    }

    public function over_limit_returns_details($d1, $d2, $route_id, $type)
    {

        switch ($type) {
            case 0:
                $type_name = 'All';
                break;
            case 1:
                $type_name = 'Over 10,000';
                break;
            case 2:
                $type_name = 'Over 100,000';
                break;
            case 3:
                $type_name = '48hrs Since Invoice';
                break;
            default:
                $type_name = '';
        }

        $route = Route::find($route_id);
        //TODO: check for type  being 0

        $user = getLoggeduserProfile();
        $d1 = $d1 ? \Carbon\Carbon::parse($d1)->toDateString() : \Carbon\Carbon::now()->toDateString();
        $d2 = $d2 ? \Carbon\Carbon::parse($d2)->toDateString() : \Carbon\Carbon::now()->toDateString();

        if ($type && $type == 3) {
            $returns = DB::table('wa_inventory_location_transfer_item_returns')
                ->whereBetween('wa_inventory_location_transfer_item_returns.created_at', [$d1 . ' 00:00:00', $d2 . ' 23:59:59'])
                ->where('wa_inventory_location_transfer_item_returns.status', '=', 'received')
                ->whereRaw('wa_inventory_location_transfer_item_returns.created_at > DATE_ADD(wa_inventory_location_transfer_items.created_at, INTERVAL 48 HOUR)')
                ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
                ->select(
                    'wa_inventory_location_transfer_item_returns.created_at as return_date',
                    'wa_inventory_location_transfers.created_at as invoice_date',
                    'wa_inventory_location_transfers.transfer_no as invoice_number',
                    'wa_inventory_location_transfer_item_returns.return_number as return_number',
                    'wa_inventory_location_transfers.name as customer',
                    'wa_inventory_location_transfers.route as route',
                    'wa_inventory_location_transfers.route_id as route_id',
                    DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
                    DB::raw('DATE(wa_inventory_location_transfer_item_returns.created_at) as date')
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->join('wa_inventory_location_transfers', function ($join) use ($d1, $d2, $route_id) {
                    $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                    if ($route_id) {
                        $query = $query->where('wa_inventory_location_transfers.route_id', $route_id);
                    }
                })
                ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->get();
        } else {

            $returns = DB::table('wa_inventory_location_transfer_item_returns')
                ->where('wa_inventory_location_transfer_item_returns.status', '=', 'received')
                ->whereBetween('wa_inventory_location_transfer_item_returns.created_at', [$d1 . ' 00:00:00', $d2 . ' 23:59:59'])
                ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
                ->select(
                    'wa_inventory_location_transfer_item_returns.created_at as return_date',
                    'wa_inventory_location_transfers.created_at as invoice_date',
                    'wa_inventory_location_transfers.transfer_no as invoice_number',
                    'wa_inventory_location_transfer_item_returns.return_number as return_number',
                    'wa_inventory_location_transfers.name as customer',
                    'wa_inventory_location_transfers.route as route',
                    'wa_inventory_location_transfers.route_id as route_id',
                    DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
                    DB::raw('DATE(wa_inventory_location_transfer_item_returns.created_at) as date')
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->join('wa_inventory_location_transfers', function ($join) use ($route_id, $d1, $d2) {
                    $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                    if ($route_id) {
                        $query = $query->where('wa_inventory_location_transfers.route_id', $route_id);
                    }
                })
                ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->groupBy('date')
                ->groupBy('route');
            if ($type && $type == 1) {
                $returns = $returns->having(DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price)'), '>', 10000);
            } elseif ($type && $type == 2) {
                $returns = $returns->having(DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price)'), '>', 100000);
            } else {
                $returns = $returns;
            }
            $returns = $returns->get();
        }
        $title = 'Pending Returns Report';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'return-confirm-report';
        $routes = DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.over_limit_return_details', compact('title', 'model', 'breadcum', 'returns', 'routes', 'd1', 'd2', 'type_name', 'route'));
    }

    public function return_list(Request $request)
    {
        $user = getLoggeduserProfile();
        if (!can('return', 'print-invoice-delivery-note')) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }

        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->where('wa_inventory_location_transfer_item_returns.status', 'pending')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 1)
            ->where('created_at', '>', '2024-04-17 23:59:59')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfer_item_returns.created_at',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfers.customer_id',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                DB::raw('sum(wa_inventory_location_transfer_items.quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }
            })
            //->groupBy('wa_inventory_location_transfer_item_returns.return_number')
            ->get();

        $title = 'Invoice Returns';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'return-transfers';

        $routes = DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.return_list', compact('title', 'model', 'breadcum', 'returns', 'routes'));
    }


    public function return_list_pending(Request $request)
    {
        $user = getLoggeduserProfile();

        if (!can('return', 'print-invoice-delivery-note')) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }
        //
        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                DB::raw('sum(wa_inventory_location_transfer_items.quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 0)
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number')
            ->get();
        $title = 'Pending Returns';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'return-confirm-approver-1';

        $routes = DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.return_pending_list', compact('title', 'model', 'breadcum', 'returns', 'routes'));
    }

    public function return_list_overlimit(Request $request)
    {
        $user = getLoggeduserProfile();

        if (!can('return', 'print-invoice-delivery-note')) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }

        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 2)
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number')
            ->get();

        $title = 'Pending Returns';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'return-confirm-approver-2';

        $routes = DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.return_pending_list_over', compact('title', 'model', 'breadcum', 'returns', 'routes'));
    }


    public function return_list_items($number)
    {
        $user = getLoggeduserProfile();
        $return = DB::table('wa_inventory_location_transfer_item_returns')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 1) //get  approved returns
            ->where('return_number', $number)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfers.to_store_location_id as store',
            )
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->first();

        $returnItems = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 1) // approved returns
            ->select(
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.description',
                'wa_unit_of_measures.title as bin',
                'wa_inventory_location_transfer_item_returns.id',
                'wa_inventory_location_transfer_item_returns.return_quantity',
                'wa_inventory_location_transfer_item_returns.received_quantity',
                'wa_inventory_location_uom.uom_id',
            )
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->join('wa_inventory_location_uom', function ($join) use ($user, $return) {
                $query = $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id');
                if ($user->role_id != 1) {
                    $query->where('wa_inventory_location_uom.uom_id', '=', $user->wa_unit_of_measures_id);
                } else {
                    $query->where('wa_inventory_location_uom.location_id', '=', $return->store);
                }
            })
            ->join('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->groupBy('wa_inventory_location_transfer_item_returns.id')
            ->get();

        $title = 'Process Invoice Return';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => route('transfers.return_list'), 'Process' => ''];
        $model = 'return-transfers';

        return view('admin.inventorylocationtransfer.return_list_items', compact('title', 'model', 'breadcum', 'return', 'returnItems'));
    }

    public function return_list_items_pending($number)
    {
        $user = getLoggeduserProfile();
        $return = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfers.to_store_location_id as store',
            )
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->first();

        $returnItems = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->whereIn('return_status', [0, 1])
            ->select(
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.description',
                'wa_unit_of_measures.title as bin',
                'wa_inventory_location_transfer_item_returns.id',
                'wa_inventory_location_transfer_item_returns.return_quantity',
                'wa_inventory_location_transfer_item_returns.received_quantity',
                'wa_inventory_location_transfer_items.selling_price as sp',
                'wa_inventory_location_transfer_item_returns.return_reason as return_reason',

                'wa_inventory_location_uom.uom_id',
            )
            ->leftJoin('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->leftJoin('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->leftJoin('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->leftJoin('wa_inventory_location_uom', function ($join) use ($user, $return) {
                $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')
                    ->where('wa_inventory_location_uom.location_id', $return->store);
            })
            ->leftJoin('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->get();

        $title = 'Process Invoice Return';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => route('transfers.return_list'), 'Process' => ''];
        $model = 'approver-1';

        return view('admin.inventorylocationtransfer.return_list_items_pending', compact('title', 'model', 'breadcum', 'return', 'returnItems'));
    }

    public function return_list_items_pending_approver_2($number)
    {
        $user = getLoggeduserProfile();
        $return = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfers.to_store_location_id as store',


            )
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->first();

        $returnItems = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            // ->where('return_status',  0)
            ->select(
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.description',
                'wa_unit_of_measures.title as bin',
                'wa_inventory_location_transfer_item_returns.id',
                'wa_inventory_location_transfer_item_returns.return_quantity',
                'wa_inventory_location_transfer_item_returns.received_quantity',
                'wa_inventory_location_transfer_items.selling_price as sp',
                'wa_inventory_location_transfer_item_returns.return_reason as return_reason',
                'wa_inventory_location_uom.uom_id',
            )
            ->leftJoin('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->leftJoin('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->leftJoin('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->leftJoin('wa_inventory_location_uom', function ($join) use ($user, $return) {
                $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')
                    ->where('wa_inventory_location_uom.location_id', $return->store);
            })
            ->leftJoin('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->get();

        $title = 'Process Invoice Return';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => route('transfers.return_list'), 'Process' => ''];
        $model = 'approver-2';

        return view('admin.inventorylocationtransfer.return_list_items_pending_approver_2', compact('title', 'model', 'breadcum', 'return', 'returnItems'));
    }

    public function return_list_items_pending_late($number)
    {
        $user = getLoggeduserProfile();
        $return = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfers.to_store_location_id as store',


            )
            ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->first();

        $returnItems = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $number)
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            // ->where('return_status',  0)
            ->select(
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.description',
                'wa_inventory_items.selling_price as current_price',
                'wa_unit_of_measures.title as bin',
                'wa_inventory_location_transfer_item_returns.id',
                'wa_inventory_location_transfer_item_returns.return_quantity',
                'wa_inventory_location_transfer_item_returns.received_quantity',
                'wa_inventory_location_transfer_items.selling_price as sp',
                'wa_inventory_location_transfer_item_returns.return_reason as return_reason',
                'wa_inventory_location_uom.uom_id',
            )
            ->leftJoin('wa_inventory_location_transfers', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id')
            ->leftJoin('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->leftJoin('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->leftJoin('wa_inventory_location_uom', function ($join) use ($user, $return) {
                $join->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')
                    ->where('wa_inventory_location_uom.location_id', $return->store);
            })
            ->leftJoin('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->get();

        $title = 'Process Invoice Return';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => route('transfers.return_list'), 'Process' => ''];
        $model = 'late-returns';

        return view('admin.inventorylocationtransfer.return_list_items_pending_late', compact('title', 'model', 'breadcum', 'return', 'returnItems'));
    }
    public function sendotp(Request $request)
    {
        $otps = DB::table('otp')->select('*')
            ->where('ref_no', '=', $request->invoice)
            ->where('status', '=', 1)
            ->first();

        if ($otps) {
            return json_encode(['status' => 0, 'message' => "A verification code already exists for {$request->invoice}"]);
        }

        $route = DB::table('routes')->where('route_name', $request->route)->first();
        if ($route) {
            $phoneNumber = DB::table('wa_customers')->where('route_id', $route->id)->first()->telephone;
            $otp = mt_rand(100000, 999999);
            $message = "Store {$request->bin} has requested to reject return {$request->invoice} for route {$route->route_name}. Use the code $otp to approve.";
            DB::table('otp')->insert([
                'phone_number' => $phoneNumber,
                'otp' => $otp,
                'status' => 1,
                'ref_no' => $request->invoice,
                'type' => 'RTN'
            ]);

            $this->smsService->sendMessage($message, $phoneNumber);

            return json_encode(['status' => 1, 'message' => "Verification code sent Successfully."]);
        } else {
            return json_encode(['status' => 0, 'message' => "Invalid route provided. "]);
        }
    }


    public function checkotp(Request $request)
    {

        $otps = DB::table('otp')->select('*')
            ->where('otp', '=', $request->otp)
            ->where('status', '=', 1)->first();
        if ($otps) {
            return json_encode(['status' => 1, 'message' => " OTP OK "]);
        } else {
            return json_encode(['status' => 0, 'message' => "OTP provided not valid for $request->invoice  "]);
        }
    }

    public function processPendingReturn(Request $request)
    {

        if ($request->return_number) {
            $user = getLoggeduserProfile();
            $result = DB::table('wa_inventory_location_transfer_item_returns')
                ->where('return_number', $request->return_number)
                ->whereIn('return_status', [1, 0])
                ->update(['return_status' => 1, 'comment' => $request->note[0], 'confirmed_by' => $user->name]);

            if ($result) {
                Session::flash('success', 'Return confirmed Successfully');
                return redirect()->route('transfers.return_list_groups');
            } else {
                Session::flash('warning', "Return Number - $request->return_number already confirmed.");
                return redirect()->route('transfers.return_list_groups');
            }
        } else {
            Session::flash('warning', 'No return number provided');
            return redirect()->route('transfers.return_list_groups');
        }
    }

    public function processPendingReturnApprover2(Request $request)
    {

        if ($request->return_number) {
            $user = getLoggeduserProfile();
            $result = DB::table('wa_inventory_location_transfer_item_returns')
                ->where('return_number', $request->return_number)
                ->whereIn('return_status', [1, 0, 2])
                ->update(['return_status' => 1, 'comment' => $request->note[0], 'confirmed_by' => $user->name]);

            if ($result) {
                Session::flash('success', 'Return confirmed Successfully');
                return redirect()->route('transfers.return_list_groups_2');
            } else {
                Session::flash('warning', "Return Number - $request->return_number already confirmed.");
                return redirect()->route('transfers.return_list_groups_2');
            }
        } else {
            Session::flash('warning', 'No return number provided');
            return redirect()->back();
        }
    }

    public function processPendingReturnLateReturn(Request $request)
    {

        if ($request->return_number) {
            $user = getLoggeduserProfile();
            $result = DB::table('wa_inventory_location_transfer_item_returns')
                ->where('return_number', $request->return_number)
                ->whereIn('return_status', [1, 0, 2, 3])
                ->update(['return_status' => 1, 'comment' => $request->note[0], 'confirmed_by' => $user->name]);

            if ($result) {
                Session::flash('success', 'Return confirmed Successfully');
                return redirect()->route('transfers.return_list_groups_late_returns');
            } else {
                Session::flash('warning', "Return Number - $request->return_number already confirmed.");
                return redirect()->route('transfers.return_list_groups_late_returns');
            }
        } else {
            Session::flash('warning', 'No return number provided');
            return redirect()->back();
        }
    }

    //
    public function processReturn2(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = getLoggeduserProfile();
            $transfer = null;
            $returnNumber = null;
            $received_count = 0;
            $returned = 0;
            $messages = [];
            $shouldSendSms = false;
            $msg = null;
            $binId = null;
            $msgBody = null;
            foreach ($request->item_ids as $index => $item_id) {
                $returnedTotal = 0;
                $returnedDiscount = 0;
                $recalculatedDiscount = 0;
                $returnItem = WaInventoryLocationTransferItemReturn::find($item_id);
                $allowedQty = (float)$returnItem->return_quantity - (float)$returnItem->received_quantity;
                $incomingQuantity = $request->received_quantity[$index];
                if ($request->get("reject-$item_id")) {
                    if ($incomingQuantity > $allowedQty) {
                        DB::rollBack();
                        return json_encode(['status' => 0, 'message' => 'Physical quantity cannot be more than return quantity']);
                    }

                    if ($incomingQuantity < 0) {
                        DB::rollBack();
                        return json_encode(['status' => 0, 'message' => 'Physical quantity cannot be less than 0']);
                    }

                    if (!$request->note[$index]) {
                        DB::rollBack();
                        return json_encode(['status' => 0, 'message' => 'Please provide a rejection reason for all rejected returns']);
                    }

                    $returnItem->update([
                        'rejected_quantity' => $allowedQty,
                        'physical_quantity' => $request->received_quantity[$index] ?? 0,
                        'note' => $request->note[$index] ?? null,
                        'status' => 'received'
                    ]);

                    DB::commit();

                    DB::table('otp')->where('ref_no', $request->invoice)->update(array(
                        'status' => 0,
                    ));

                    $returned++;
                    //return json_encode(['status'=>1,'message'=>'Returns processed Successfully']);
                } else {
                    if ($incomingQuantity > $allowedQty) {
                        DB::rollBack();
                        return json_encode(['status' => 0, 'message' => 'Total return quantity cant  be greater  than invoice quantity']);
                    }

                    $returnNumber = $returnItem->return_number;

                    $transfer = WaInventoryLocationTransfer::with(['get_requisition'])->find($returnItem->wa_inventory_location_transfer_id);
                    $transferItem = WaInventoryLocationTransferItem::with(['getInventoryItemDetail'])->find($returnItem->wa_inventory_location_transfer_item_id);

                    $returnedTotal += $transferItem->selling_price * $incomingQuantity;
                    $requisitionItem = WaInternalRequisitionItem::find($transferItem->wa_internal_requisition_item_id);
                    $inventoryItem = $transferItem->getInventoryItemDetail;
                    $shouldSendSms = true;
                    $msgBody = $msgBody . ' ' . $inventoryItem->title . '(' . $incomingQuantity . ')';
                    $binId = WaInventoryLocationUom::where('inventory_id', $inventoryItem->id)->where('location_id', $user->wa_location_and_store_id)->first()->uom_id;
                    $store = WaUnitOfMeasure::find($binId);
                    $route = Route::find($transfer->route_id);
                    $msg = "The following return items have been received in {$store->title} from {$route->route_name}: ";
                    $newOrderQty = $requisitionItem->quantity - $incomingQuantity;
                    if ($requisitionItem->discount > 0) {
                        $returnedDiscount += $requisitionItem->discount;
                        $newDiscount = 0;
                        $newDiscountDescription = null;
                        // $discountBand = DB::table('discount_bands')->where('inventory_item_id', $inventoryItem->id)
                        //     ->where('from_quantity', '<=', $newOrderQty)
                        //     ->where('to_quantity', '>=', $newOrderQty)
                        //     ->first();
                        $discountBand = DiscountBand::where('inventory_item_id', $inventoryItem->id)
                            ->where(function ($query) use ($newOrderQty, $inventoryItem) {
                                $query->where('from_quantity', '<=', $newOrderQty)
                                    ->where('to_quantity', '>=', $newOrderQty);
                            })
                            ->orWhere(function ($query) use ($newOrderQty, $inventoryItem) {
                                $query->where('inventory_item_id', $inventoryItem->id)
                                    ->where('to_quantity', '<', $newOrderQty);
                            })
                            ->orderBy('to_quantity', 'desc')
                            ->first();
                        if ($discountBand) {
                            $newDiscount = $discountBand->discount_amount * $newOrderQty;
                            $newDiscountDescription = "{$discountBand->discount_amount} discount for quantity between {$discountBand->from_quantity} and {$discountBand->to_quantity}";
                        }

                        $requisitionItem->update([
                            'discount' => $newDiscount,
                            'discount_description' => $newDiscountDescription,
                        ]);

                        $recalculatedDiscount += $newDiscount;
                    }

                    $returnItem->update([
                        'received_quantity' => (float)$returnItem->received_quantity + $incomingQuantity,
                        'received_by' => $user->id,
                        'status' => 'received',
                    ]);

                    $currentQoh = DB::table('wa_stock_moves')->where('wa_inventory_item_id', $inventoryItem->id)
                        ->where('wa_location_and_store_id', $transfer->to_store_location_id)
                        ->sum('qauntity');

                  $stockMove =   WaStockMove::create([
                        'user_id' => $user->id,
                        'restaurant_id' => $user->restaurant_id,
                        'wa_location_and_store_id' => $transfer->to_store_location_id,
                        'stock_id_code' => $inventoryItem->stock_id_code,
                        'wa_inventory_item_id' => $inventoryItem->id,
                        'price' => $transferItem->selling_price,
                        'refrence' => "{$transfer->route} {$transfer->transfer_no} RETURN",
                        'qauntity' => $incomingQuantity,
                        'new_qoh' => $currentQoh + $incomingQuantity,
                        'standard_cost' => $transferItem->standard_cost,
                        'document_no' => $returnNumber,
                        'selling_price' => $transferItem->selling_price,
                        'total_cost' => $transferItem->selling_price * $incomingQuantity,
                        'route_id' => $route->id
                    ]);

                    $invoice = $transfer->get_requisition;
                    $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
                    $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();
                    $routeCustomer = WaRouteCustomer::with('route')->find($invoice->wa_route_customer_id);
                    $customerName = '';
                    
                    if ($routeCustomer && is_object($routeCustomer)) {
                        $customerName = $routeCustomer->bussiness_name ?? $routeCustomer->name ?? '';
                    } else {
                        $waCustomer = WaCustomer::find($invoice->customer_id);
                        $customerName = $waCustomer?->customer_name ?? '';
                    }

                    $customer = WaCustomer::find($transfer->get_requisition->customer_id);
                    
                    WaDebtorTran::insert([
                        'salesman_id' => $transfer->to_store_location_id,
                        'salesman_user_id' => $transfer->user_id,
                        'type_number' => $series_module?->type_number,
                        'wa_customer_id' => $transfer->get_requisition->customer_id,
                        'customer_number' => $customer?->customer_code ?? '',
                        'trans_date' => Carbon::now()->toDateString(),
                        'input_date' => Carbon::now()->toDateTimeString(),
                        'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                        'shift_id' => $transfer->shift_id,
                        'invoice_customer_name' => $customerName,
                        'reference' => "{$transfer->route} {$transfer->transfer_no} RETURN",
                        'amount' => - ($returnedTotal),
                        'document_no' => $returnNumber,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'created_at' => date('Y-m-d H:i:s'),
                        'branch_id' => $transfer->restaurant_id,
                    ]);

                    if ($returnedDiscount > 0) {
                        WaDebtorTran::insert([
                            'salesman_id' => $transfer->to_store_location_id,
                            'salesman_user_id' => $transfer->user_id,
                            'type_number' => $series_module?->type_number,
                            'wa_customer_id' => $transfer->get_requisition->customer_id,
                            'customer_number' => $customer?->customer_code ?? '',
                            'trans_date' => Carbon::now()->toDateString(),
                            'input_date' => Carbon::now()->toDateTimeString(),
                            'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                            'shift_id' => $transfer->shift_id,
                            'invoice_customer_name' => $customerName,
                            'reference' => "{$transfer->route} {$transfer->transfer_no} DISCOUNT RETURN",
                            'amount' => ($returnedDiscount),
                            'document_no' => $returnNumber,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'branch_id' => $transfer->restaurant_id,

                        ]);
                    }

                    if ($recalculatedDiscount > 0) {
                        WaDebtorTran::insert([
                            'salesman_id' => $transfer->to_store_location_id,
                            'salesman_user_id' => $transfer->user_id,
                            'type_number' => $series_module?->type_number,
                            'wa_customer_id' => $transfer->get_requisition->customer_id,
                            'customer_number' => $customer?->customer_code ?? '',
                            'trans_date' => Carbon::now()->toDateString(),
                            'input_date' => Carbon::now()->toDateTimeString(),
                            'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                            'shift_id' => $transfer->shift_id,
                            'invoice_customer_name' => $customerName,
                            'reference' => "{$transfer->route} {$transfer->transfer_no} DISCOUNT ALLOWED",
                            'amount' => - ($recalculatedDiscount),
                            'document_no' => $transfer->transfer_no,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'created_at' => date('Y-m-d H:i:s'),
                            'branch_id' => $transfer->restaurant_id,

                        ]);
                    }

                    // Post to GL
                    SalesInvoiceReturnController::postReturn($returnItem->id);

                    $received_count++;

                    // DB::table('otp')->where('ref_no',$request->invoice)->update(array(
                    //                      'status'=>0,
                    //         ));
                    PerformPostReturnActions::dispatch($stockMove)->afterCommit();
                    DB::commit();
                }
            }
            //send notification 

            if ($shouldSendSms) {
                $msg = $msg . $msgBody . ' Received By: ' . $user->name;
                $store = WaUnitOfMeasure::find($binId);
                if ($store->chief_storekeeper != $user->id) {
                    $chiefStoreKeeper = User::find($store->chief_storekeeper);
                    if ($chiefStoreKeeper) {
                        $this->smsService->sendMessage($msg, $chiefStoreKeeper->phone_number);
                    }
                }
            }

            return json_encode(['status' => 1, 'message' => "Received - $received_count item(s), Returned -  $returned item(s) "]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Return Processing Error: ' . $e->getMessage() . ' at line ' . $e->getLine() . ' in file ' . $e->getFile());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return json_encode(['status' => 0, 'message' => $e->getMessage() . ' (Line: ' . $e->getLine() . ')']);
        }
    }

    public function processReturn(Request $request)
    {
        // dd('all');
        DB::beginTransaction();

        try {
            $user = getLoggeduserProfile();
            $transfer = null;
            $returnNumber = null;


            foreach ($request->item_ids as $index => $item_id) {
                $returnedTotal = 0;
                $returnedDiscount = 0;
                $recalculatedDiscount = 0;

                $returnItem = WaInventoryLocationTransferItemReturn::find($item_id);
                $allowedQty = (float)$returnItem->return_quantity - (float)$returnItem->received_quantity;
                $incomingQuantity = $request->received_quantity[$index];
                if ($request->get("reject-$item_id")) {
                    if ($incomingQuantity > $allowedQty) {
                        DB::rollBack();
                        Session::flash('warning', 'Physical quantity cannot be more than return quantity');
                        return redirect()->back();
                    }

                    if ($incomingQuantity < 0) {
                        DB::rollBack();
                        Session::flash('warning', 'Physical quantity cannot be less than 0');
                        return redirect()->back();
                    }

                    if (!$request->note[$index]) {
                        DB::rollBack();
                        Session::flash('warning', 'Please provide a rejection reason for all rejected returns');
                        return redirect()->back();
                    }

                    $returnItem->update([
                        'rejected_quantity' => $allowedQty,
                        'physical_quantity' => $request->received_quantity[$index] ?? 0,
                        'note' => $request->note[$index] ?? null,
                        'status' => 'received'
                    ]);

                    DB::commit();
                    Session::flash('success', 'Returns processed Successfully');
                    return redirect()->route('transfers.return_list');
                }

                $returnNumber = $returnItem->return_number;

                $transfer = WaInventoryLocationTransfer::with(['get_requisition'])->find($returnItem->wa_inventory_location_transfer_id);
                $transferItem = WaInventoryLocationTransferItem::with(['getInventoryItemDetail'])->find($returnItem->wa_inventory_location_transfer_item_id);

                $returnedTotal += $transferItem->selling_price * $incomingQuantity;
                $requisitionItem = WaInternalRequisitionItem::find($transferItem->wa_internal_requisition_item_id);
                $inventoryItem = $transferItem->getInventoryItemDetail;
                $newOrderQty = $requisitionItem->quantity - $incomingQuantity;
                if ($requisitionItem->discount > 0) {
                    $returnedDiscount += $requisitionItem->discount;
                    $newDiscount = 0;
                    $newDiscountDescription = null;
                    $discountBand = DB::table('discount_bands')->where('inventory_item_id', $inventoryItem->id)
                        ->where('from_quantity', '<=', $newOrderQty)
                        ->where('to_quantity', '>=', $newOrderQty)
                        ->first();
                    if ($discountBand) {
                        $newDiscount = $discountBand->discount_amount * $newOrderQty;
                        $newDiscountDescription = "{$discountBand->discount_amount} discount for quantity between {$discountBand->from_quantity} and {$discountBand->to_quantity}";
                    }

                    $requisitionItem->update([
                        'discount' => $newDiscount,
                        'discount_description' => $newDiscountDescription,
                    ]);

                    $recalculatedDiscount += $newDiscount;
                }

                $returnItem->update([
                    'received_quantity' => (float)$returnItem->received_quantity + $incomingQuantity,
                    'received_by' => $user->id,
                    'status' => 'received',
                ]);

                $currentQoh = DB::table('wa_stock_moves')->where('wa_inventory_item_id', $inventoryItem->id)
                    ->where('wa_location_and_store_id', $transfer->to_store_location_id)
                    ->sum('qauntity');

                WaStockMove::create([
                    'user_id' => $user->id,
                    'restaurant_id' => $user->restaurant_id,
                    'wa_location_and_store_id' => $transfer->to_store_location_id,
                    'stock_id_code' => $inventoryItem->stock_id_code,
                    'wa_inventory_item_id' => $inventoryItem->id,
                    'price' => $transferItem->selling_price,
                    'refrence' => "{$transfer->route} {$transfer->transfer_no} RETURN",
                    'qauntity' => $incomingQuantity,
                    'new_qoh' => $currentQoh + $incomingQuantity,
                    'standard_cost' => $transferItem->standard_cost,
                    'document_no' => $returnNumber,
                    'selling_price' => $transferItem->selling_price,
                    'total_cost' => $transferItem->selling_price * $incomingQuantity,
                    'route_id' => SalesmanShift::find($transfer->shift_id)->route_id,
                ]);
            }

            $invoice = $transfer->get_requisition;
            $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
            $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();
            $routeCustomer = WaRouteCustomer::with('route')->find($invoice->wa_route_customer_id);
            $customerName = '';
            
            if ($routeCustomer && is_object($routeCustomer)) {
                $customerName = $routeCustomer->bussiness_name ?? $routeCustomer->name ?? '';
            } else {
                $waCustomer = WaCustomer::find($invoice->customer_id);
                $customerName = $waCustomer?->customer_name ?? '';
            }

            $customer = WaCustomer::find($transfer->get_requisition->customer_id);
            
            WaDebtorTran::insert([
                'salesman_id' => $transfer->to_store_location_id,
                'salesman_user_id' => $transfer->user_id,
                'type_number' => $series_module?->type_number,
                'wa_customer_id' => $transfer->get_requisition->customer_id,
                'customer_number' => $customer?->customer_code ?? '',
                'trans_date' => Carbon::now()->toDateString(),
                'input_date' => Carbon::now()->toDateTimeString(),
                'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                'shift_id' => $transfer->shift_id,
                'invoice_customer_name' => $customerName,
                'reference' => "{$transfer->route} {$transfer->transfer_no} RETURN",
                'amount' => - ($returnedTotal),
                'document_no' => $returnNumber,
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($returnedDiscount > 0) {
                WaDebtorTran::insert([
                    'salesman_id' => $transfer->to_store_location_id,
                    'salesman_user_id' => $transfer->user_id,
                    'type_number' => $series_module?->type_number,
                    'wa_customer_id' => $transfer->get_requisition->customer_id,
                    'customer_number' => $customer?->customer_code ?? '',
                    'trans_date' => Carbon::now()->toDateString(),
                    'input_date' => Carbon::now()->toDateTimeString(),
                    'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                    'shift_id' => $transfer->shift_id,
                    'invoice_customer_name' => $customerName,
                    'reference' => "{$transfer->route} {$transfer->transfer_no} DISCOUNT RETURN",
                    'amount' => ($returnedDiscount),
                    'document_no' => $returnNumber,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            if ($recalculatedDiscount > 0) {
                WaDebtorTran::insert([
                    'salesman_id' => $transfer->to_store_location_id,
                    'salesman_user_id' => $transfer->user_id,
                    'type_number' => $series_module?->type_number,
                    'wa_customer_id' => $transfer->get_requisition->customer_id,
                    'customer_number' => $customer?->customer_code ?? '',
                    'trans_date' => Carbon::now()->toDateString(),
                    'input_date' => Carbon::now()->toDateTimeString(),
                    'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                    'shift_id' => $transfer->shift_id,
                    'invoice_customer_name' => $customerName,
                    'reference' => "{$transfer->route} {$transfer->transfer_no} DISCOUNT ALLOWED",
                    'amount' => - ($recalculatedDiscount),
                    'document_no' => $transfer->transfer_no,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }


            DB::table('otp')->where('ref_no', $request->invoice)->update(array(
                'status' => 0,
            ));

            DB::commit();
            return redirect()->route('transfers.return_list')->with('success', 'Items received successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }


    public function return_process($slug, Request $request)
    {
        $user = getLoggeduserProfile();
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;

        if (!isset($permission['print-invoice-delivery-note___return']) && $permission != 'superadmin') {
            return response()->json(['result' => -1, 'message' => "Restricted: You Don't have enough permissions"]);
        }

        if (!isset($request->item)) {
            Session::flash('warning', 'You did not select any return items');
            return redirect()->back();
        }


        DB::beginTransaction();
        try {
            $selectedItemIds = array_values($request->item);

            foreach ($selectedItemIds as $selectedItemId) {
                $incomingQty = (float)$request->quantity[$selectedItemId];
                $remainderQty = (float)$request->qty[$selectedItemId];

                $incomingItemCode = $request->item_code[$selectedItemId];
                if ($remainderQty >= $incomingQty) {
                } elseif ($remainderQty == 0) {
                    DB::rollBack();
                    Session::flash('warning', "$incomingItemCode Remaining quantity is $remainderQty.");
                    return redirect()->back()->withInput();
                } else {
                    DB::rollBack();
                    Session::flash('warning', "$incomingItemCode Exceeds its maximum allowed remaining quantity of $remainderQty.");
                    return redirect()->back()->withInput();
                }
                if ($incomingQty > 0) {
                    $incomingReason = $request->reason[$selectedItemId];
                    $incomingReason = ReturnReason::find($incomingReason)->reason;

                    if (!$incomingReason) {
                        DB::rollBack();
                        Session::flash('warning', "Please provide a return reason for $incomingItemCode");
                        return redirect()->back()->withInput();
                    }

                    $transferItem = WaInventoryLocationTransferItem::find((int)$selectedItemId);

                    $transfer = WaInventoryLocationTransfer::find((int)$transferItem->wa_inventory_location_transfer_id);
                    // if ($transfer) {
                    //     $existingReturn = WaInventoryLocationTransferItemReturn::latest()
                    //         ->where('wa_inventory_location_transfer_item_id', $transferItem->id)
                    //         ->where('wa_inventory_location_transfer_id', $transfer->id)
                    //         ->where('status', 'received')
                    //         ->whereNot('received_quantity', 0)
                    //         ->first();
                    //     if ($existingReturn) {
                    //         $item = WaInventoryItem::find($transferItem->wa_inventory_item_id);
                    //         DB::rollBack();
                    //         Session::flash('warning', 'Returns have already been done on invoice item.' . $item->title);
                    //         return redirect()->back();
                    //     }
                    // }

                    
                    $returns = DB::table('wa_inventory_location_transfer_item_returns')
                        ->select(DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) as total'),)
                        ->join('wa_inventory_location_transfers', 'wa_inventory_location_transfers.id', '=', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id')
                        ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                        ->where('wa_inventory_location_transfers.shift_id', $transfer->shift_id)
                        ->get();
                    $customer = DB::table('wa_customers')->select('id', 'return_limit')->where('id', $transfer->customer_id)->first();
                    if ($customer) {
                        $getDiffInHours = Carbon::now()->diffInMinutes($transfer->created_at) / 60;

                        if ($getDiffInHours > 48) {
                            $status = 3;
                        } elseif (((int)$returns->sum('total') + ((int)($transferItem->selling_price * $incomingQty))) > 100000) {
                            $status = 2;
                        } else {
                            if ((int)$returns->sum('total') < 100000) {

                                $rtn_amount = (int)($transferItem->selling_price * $incomingQty);
                                $remaining_bal = 10000 - (int)$returns->sum('total');
                                //check limits
                                if ((int)$remaining_bal > 0) {
                                    if ((int)$rtn_amount > (int)$remaining_bal) {
                                        $status = 0;
                                    }
                                    if (
                                        (int)$rtn_amount < (int)$remaining_bal ||
                                        (int)$rtn_amount == (int)$remaining_bal
                                    ) {
                                        $status = 1;
                                    }
                                } else {
                                    $status = 0;
                                }
                            } else {

                                $status = 2;
                            }
                        }
                    }

                    $returnRecords = WaInventoryLocationTransferItemReturn::where('wa_inventory_location_transfer_item_id', $transferItem->id)->get();

                    if (count($returnRecords) > 0) {
                        $allowedQty = $transferItem->quantity - $returnRecords->sum('received_quantity');
                        if ($incomingQty > $allowedQty) {
                            DB::rollBack();
                            Session::flash('warning', "$incomingItemCode exceeds its maximum allowed return quantity of $allowedQty.");
                            return redirect()->back()->withInput();
                        }
                    }

                    $returnNumber = WaInventoryLocationTransferItemReturn::latest()->where('wa_inventory_location_transfer_id', $transferItem->wa_inventory_location_transfer_id)->first()?->return_number;
                    $updateReturnSeries = false;
                    if (!$returnNumber) {
                        $returnNumber = getCodeWithNumberSeries('RETURN');
                        $updateReturnSeries = true;
                    }

                    $user = getLoggeduserProfile();
                    // $checked_item = WaInventoryLocationTransferItemReturn::where('wa_inventory_location_transfer_id', $transferItem->wa_inventory_location_transfer_id)
                    // ->where('return_status',0)->where('return_by',$user->id)->first();

                    // if($checked_item){
                    //     DB::rollBack();
                    //     Session::flash('warning', "$incomingItemCode already returned.With return number".$checked_item->return_number."  ");
                    //         return redirect()->back();
                    // }
                    //dd($status);
                    $return = WaInventoryLocationTransferItemReturn::create([
                        'return_number' => $returnNumber,
                        'wa_inventory_location_transfer_item_id' => $transferItem->id,
                        'wa_inventory_location_transfer_id' => $transferItem->wa_inventory_location_transfer_id,
                        'return_by' => $user->id,
                        'return_date' => Carbon::now(),
                        'return_quantity' => $incomingQty,
                        'return_reason' => $incomingReason,
                        'return_status' => $status,
                    ]);

                    if ($updateReturnSeries) {
                        updateUniqueNumberSeries('RETURN', $returnNumber);
                    }
                }
            }

            DB::commit();
            Session::flash('success', 'Return successful. Zero quantities have been ignored.');
            if ($status == 1) {
                return redirect()->route('transfers.return_list');
            } else {
                return redirect()->route('transfers.return_list_pending');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Session::flash('error', $e->getMessage());
            return redirect()->back();
        }
    }

    public function checkQuantity_return($locationid, $itemid, $qty)
    {
        try {
            $qtyOnHand = WaStockMove::where('wa_location_and_store_id', $locationid)->where('stock_id_code', $itemid)->sum('qauntity');
            if ($qty > $qtyOnHand) {
                return '0';
            } else {
                return '1';
            }
        } catch (\Exception $e) {
            return '1';
        }
    }

    public function return_list_groups(Request $request)
    {
        $user = getLoggeduserProfile();
        $model = $this->model;
        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(

                'wa_inventory_location_transfer_item_returns.created_at as invoice_date',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfers.route_id as route_id',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }
                if ($request->branch) {
                    $query = $query->where('wa_inventory_location_transfers.restaurant_id', $request->branch);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 0)
            ->groupBy('wa_inventory_location_transfers.route')
            ->groupBy(DB::raw('DATE(wa_inventory_location_transfer_item_returns.created_at)'))
            ->get();
        // dd($returns);


        $title = 'Pending Returns List';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];

        $model = 'approver-1';

        $routes = DB::table('routes')->select('id', 'route_name', 'restaurant_id')->get();
        $branches = Restaurant::all();

        return view('admin.inventorylocationtransfer.return_pending_groups', compact('title', 'model', 'breadcum', 'returns', 'routes', 'branches'));
    }

    public function return_list_groups_2(Request $request)
    {
        $user = getLoggeduserProfile();


        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfer_item_returns.created_at as invoice_date',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }

                if ($request->branch) {
                    $query = $query->where('wa_inventory_location_transfers.restaurant_id', $request->branch);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 2)
            ->groupBy('wa_inventory_location_transfers.route')
            ->groupBy(DB::raw('DATE(wa_inventory_location_transfer_item_returns.created_at)'))

            // ->groupBy('wa_inventory_location_transfer_item_returns.return_number')

            ->get();


        $title = 'Pending Returns List';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'approver-2';
        $routes = DB::table('routes')->select('id', 'route_name', 'restaurant_id')->get();
        $branches = Restaurant::all();
        return view('admin.inventorylocationtransfer.return_pending_groups_2', compact('title', 'model', 'breadcum', 'returns', 'routes', 'branches'));
    }

    public function return_list_groups_late_returns(Request $request)
    {

        $user = getLoggeduserProfile();
        $model = $this->model;
        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(
                // 'wa_inventory_location_transfer_item_returns.return_number',
                // 'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfer_item_returns.updated_at as invoice_date',
                // 'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }

                if ($request->branch) {
                    $query = $query->where('wa_inventory_location_transfers.restaurant_id', $request->branch);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 3)
            ->groupBy('wa_inventory_location_transfers.route')
            ->get();

        $title = 'Pending Returns List';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];

        $model = 'late-returns';

        $routes = DB::table('routes')->select('id', 'route_name', 'restaurant_id')->get();
        $branches = Restaurant::all();
        return view('admin.inventorylocationtransfer.return_pending_groups_late_returns', compact('title', 'model', 'breadcum', 'returns', 'routes', 'branches'));
    }

    public function return_list_route(Request $request)
    {
        $date = \Carbon\Carbon::parse($request->date)->toDateString();
        $start = Carbon::parse($request->date)->startOfDay();
        $end = Carbon::parse($request->date)->endOfDay();
        $user = getLoggeduserProfile();
        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            // ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->where('wa_inventory_location_transfer_item_returns.created_at', '>=', $start)
            ->where('wa_inventory_location_transfer_item_returns.created_at', '<=', $end)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'users.name as initiated_by',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfer_item_returns.return_status as return_status',
                'wa_inventory_location_transfer_item_returns.status as status',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->leftjoin('users', 'users.id', '=', 'wa_inventory_location_transfer_item_returns.return_by')
            ->whereIn('wa_inventory_location_transfer_item_returns.return_status', [0, 1])
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number', 'wa_inventory_location_transfer_item_returns.return_status')
            ->get();
        $title = 'Pending Returns List';
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'approver-1';
        $route = $request->route;
        $routes = []; // DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.return_pending_group', compact('title', 'model', 'breadcum', 'returns', 'routes', 'route'));
    }

    public function return_list_route_2(Request $request)
    {
        $date = \Carbon\Carbon::parse($request->date)->toDateString();
        $user = getLoggeduserProfile();
        $start = Carbon::parse($request->date)->startOfDay();
        $end = Carbon::parse($request->date)->endOfDay();

        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            // ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->where('wa_inventory_location_transfer_item_returns.created_at', '>=', $start)
            ->where('wa_inventory_location_transfer_item_returns.created_at', '<=', $end)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'users.name as initiated_by',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                'wa_inventory_location_transfer_item_returns.return_status as return_status',
                'wa_inventory_location_transfer_item_returns.status as status',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->leftJoin('users', 'users.id', '=', 'wa_inventory_location_transfer_item_returns.return_by')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->whereIn('wa_inventory_location_transfer_item_returns.return_status', [0, 1, 2])
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number', 'wa_inventory_location_transfer_item_returns.return_status')
            ->get();
        $title = 'Pending Returns List - ' . $request->route;
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'approver-2';
        $route = $request->route;
        $routes = [];
        return view('admin.inventorylocationtransfer.return_pending_group_2', compact('title', 'model', 'breadcum', 'returns', 'routes', 'route'));
    }

    public function return_list_route_late_returns(Request $request)
    {
        $user = getLoggeduserProfile();
        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfer_item_returns.created_at as return_date',
                'users.name as initiated_by',
                'wa_inventory_location_transfers.name as customer',
                'wa_inventory_location_transfers.route as route',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns'),
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_items.selling_price) AS current_price')

            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->leftjoin('users', 'users.id', '=', 'wa_inventory_location_transfer_item_returns.return_by')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route);
                }
            })
            ->join('wa_inventory_items', 'wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 3)
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number')
            ->get();
        $title = 'Pending Returns List - ' . $request->route;
        $breadcum = [$this->title => route($this->model . '.index'), 'Returns' => ''];
        $model = 'late-returns';
        $route = $request->route;
        $routes = []; // DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.inventorylocationtransfer.return_pending_group_late_returns_list', compact('title', 'model', 'breadcum', 'returns', 'routes', 'route'));
    }

    public function processgroupReturn(Request $request)
    {
        DB::beginTransaction();

        try {

            $count = 0;
            $checkboxes = isset($request->checkbox) ? $request->checkbox : array();
            if (count($checkboxes) > 0) {

                foreach ($checkboxes as $key => $val) {
                    if ($val) {
                        $user = getLoggeduserProfile();

                        $result = DB::table('wa_inventory_location_transfer_item_returns')->where('return_number', $val)
                            ->update([
                                'return_status' => 1,
                                'comment' => "Confirmed by - $user->name",
                                'confirmed_by' => $user->name,
                            ]);

                        if ($result) {
                        } else {
                            DB::rollBack();
                            Session::flash('warning', "Return Number - $val already confirmed.");
                            return redirect()->back();
                        }
                    } else {
                        DB::rollBack();
                        Session::flash('warning', 'No return number provided');
                        return redirect()->back();
                    }

                    $count++;
                }

                DB::commit();
                Session::flash('success', "$count - Returns processed Successfully");
                return redirect()->back(); //route('transfers.return_list_groups');
            } else {
                // DB::rollBack();
                Session::flash('warning', "No returns selected.");
                return redirect()->back();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Session::flash('error', "Something went wrong please check and try again");
            return redirect()->back();
        }
    }
}
