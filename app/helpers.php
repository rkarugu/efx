<?php

use App\LoadingSheetDispatch;
use App\VehicleAssignment;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Input;
use App\Model\Category;
use App\Model\EmployeeTableAssignment;
use App\Model\User;
use App\Model\Order;
use App\Model\UserPermission;
use App\Model\PrintClassUser;
use App\Model\PrintClass;
use App\Model\OrderReceiptRelation;
use App\Model\OrderBookedTable;
use App\Model\OrderedItem;
use App\Model\FoodItem;
use App\Model\BeerKegCategory;
use App\Model\DeliveryOrderSaleRepRelation;
use App\Model\DeliveryOrder;
use App\Model\WaAccountSection;
use App\Model\WaAccountGroup;
use App\Model\WaBranch;
use App\Model\WaChartsOfAccount;
use App\Model\Restaurant;
use App\Model\WaShift;
use App\Model\WaCompanyPreference;
use App\Services\InfoSkySmsService;
use App\Model\Country;
use App\Model\WaCashSales;
use App\Model\Vehicle;
use App\Model\Route;
use App\Model\Role;
use App\Model\WaStockFamilyGroup;
use App\Model\WaStockTypeCategory;
use App\Model\WaPaymentTerm;
use App\Model\WaCurrencyManager;
use App\Model\WaInventoryCategory;
use App\Model\WaUnitOfMeasure;
use App\Model\WaNumerSeriesCode;
use App\Model\WaInventoryItem;
use App\Model\WaSupplier;
use App\Model\WaDepartment;
use App\Model\WaExternalRequisition;
use App\Model\WaExternalReqPermission;
use App\Model\WaDepartmentExternalAuthorization;
use App\Model\WaPurchaseOrder;
use App\Model\WaLocationAndStore;
use App\Model\WaPurchaseOrderPermission;
use App\Model\Setting;
use App\Model\WaGlTran;
use App\Model\WaGrn;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaPurchaseOrderItem;
use App\Model\WaInternalRequisition;
use App\Model\StockAdjustment;
use App\Model\WaDepartmentsAuthorizationRelations;
use App\Model\WaInternalReqPermission;
use App\Model\WaRecipe;
use App\Model\WaStockCheckFreeze;
use App\Model\PaymentCredit;
use App\Model\PaymentDebit;
use App\Model\PaymentMethod;
use App\Model\WaBankAccount;
use App\Model\WaSuppTran;
use App\Model\WaCustomer;
use App\Model\WaSalesOrderQuotation;
use App\Model\WaSalesInvoice;
use App\Model\WaDebtorTran;
use App\Model\WaJournalEntry;
use App\Model\WaBanktran;
use App\Model\WaPurchaseOrderAuthorization;
use App\Model\Bill;
use App\Model\BillOrderRelation;
use App\Model\ItemSubCategories;
use App\Model\OrdersDiscountsForGlTran;
use App\Model\TyreInventory;
use App\Model\WaTyrePurchaseOrderPermission;
use App\Model\WaTyrePurchaseOrderAuthorization;
use App\Model\WaTyrePurchaseOrder;
use App\Model\TyrePosition;
use App\Model\Meterhistory;
use App\Model\OdometerReadingHistory;
use App\Model\PackSize;
use App\Model\VehicleType;
use App\Model\WaInventoryLocationItemReturn;
use App\Model\WaRouteCustomer;
use App\Model\WaStockMove;
use App\VehicleSupplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use App\SalesmanShiftCustomer;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\LpoApproved;
use App\Models\EmailTemplate;


function getArrCalc($dataArr = [])
{
    $temp_var = [];
    foreach ($dataArr as $key => $firstArr) {
        $temp_var[] = array_sum(array_column($dataArr, $key));
    }
    return $temp_var;
}

function saveOdometerHistory($vehicle_id = "", $odometer = "", $entry_type = "")
{
    $loggedUserData = getLoggeduserProfile();
    $checkHistory = Meterhistory::where('vehicle', $vehicle_id)->first();
    if ($checkHistory) {
        $history_id = $checkHistory->id;
        $checkHistory->primary_meter = $odometer;
        $checkHistory->date = date('Y-m-d');
        $checkHistory->save();
    } else {
        $meterHistory = new Meterhistory();
        $meterHistory->vehicle = $vehicle_id;
        $meterHistory->primary_meter = $odometer;
        $meterHistory->entry_type = $entry_type;
        $meterHistory->date = date('Y-m-d');
        $meterHistory->save();
        $history_id = $meterHistory->id;
    }
    $new = new OdometerReadingHistory();
    $new->meter_history_id = $history_id;
    $new->vehicle_id = $vehicle_id;
    $new->date = date('Y-m-d H:i:s');
    $new->odometer_reading = $odometer;
    $new->entry_type = $entry_type;
    $new->user_id = $loggedUserData->id;
    $new->save();
}


if (!function_exists('getTotalSales')) {
    function getTotalSales($storelocationid = 18, $date_from = "2022-09-01", $date_to = "2022-09-13")
    {

        $lists = WaInventoryLocationTransfer::with(['getRelatedItem_ForReturn' => function ($w) {
            $w->where('quantity', '>', DB::RAW('wa_inventory_location_transfer_items.return_quantity'));
        }, 'getRelatedItem', 'getBranch', 'getDepartment', 'fromStoreDetail', 'toStoreDetail', 'getrelatedEmployee'])->where('status', '!=', 'UNCOMPLETED');
        $lists = $lists->where('to_store_location_id', $storelocationid);
        if ($date_from) {
            $lists = $lists->whereDate('created_at', '>=', $date_from);
        }
        if ($date_to) {
            $lists = $lists->whereDate('created_at', '<=', $date_to);
        }
        $lists = $lists->orderBy('id', 'desc')->get();

        $total_sales = 0;
        if ($lists->count() > 0) {
            foreach ($lists as $key => $list) {
                $total_sales += @$list->getRelatedItem->sum('total_cost_with_vat');
            }
        }
        return $total_sales;
    }
}

function addPurchaseOrder_non_stock_Permissions($wa_purchase_order_id, $wa_department_id)
{
    $rowmain = WaPurchaseOrder::where('id', $wa_purchase_order_id)->first();
    $authorizers_users = WaPurchaseOrderAuthorization::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();
    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::whereIn('id', $authorizers_users)
        ->orderBy('purchase_order_authorization_level', 'asc')
        ->get();
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new WaPurchaseOrderPermission();
            $row->user_id = $users->id;
            $row->wa_purchase_order_id = $wa_purchase_order_id;
            $row->approve_level = $users->purchase_order_authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();
            if ($i == 0) {
                $emp = $users;
                // sendMailForPurchaseOrder($emp, $row->wa_purchase_order_id, $row->approve_level);
                $phone_number = User::select('phone_number')->where('id', $users->id)->first();
                $phone = $phone_number->phone_number;
                $u = @$rowmain->getrelatedEmployee;
                $html = '';
                foreach ($rowmain->getRelatedItem as $key => $items) {
                    $html .= $key + 1 . '  ' . @$items->getNonStockItemDetail->item_description . '  ' . 'QTY ' . $items->supplier_quantity . "\n";
                }
                $message = 'You have an Purchase Order for non-stock No ' . $rowmain->purchase_no . ' from ' . @$u->name . ' of Branch: ' . @$u->userRestaurent->name . ' and Department: ' . @$u->userDepartment->department_name . ' that requires your approval.  The items are: ' . "\n" . $html;
                // send_sms($phone, $message);
                // sendMessage($message, $phone);
            }
            $i++;
        }
    } else {
        $u = @$rowmain->getrelatedEmployee;
        $phone = $u->phone_number;
        $message = 'Your Purchase order for non non-stock No ' . $rowmain->purchase_no . ' is Approved';
        // send_sms($phone, $message);
        // sendMessage($message, $phone);
        $rowmain->status = 'APPROVED';
        $rowmain->save();
    }
}

function getVehicleListWithAll()
{
    $new_arr = array('all' => 'All Vehicles');
    $list = Vehicle::pluck('license_plate', 'id')->toArray();
    $list = array_merge($new_arr, $list);
    return count($list) > 0 ? $list : [];
}

function getItemQoh($id)
{
    return WaStockMove::where('wa_inventory_item_id', $id)->sum('qauntity');
}


function getVendorList()
{
    $new_arr = array('all' => 'All Vendors');
    $list = WaSupplier::pluck('name', 'id')->toArray();
    $list = array_merge($new_arr, $list);
    return count($list) > 0 ? $list : [];
}


if (!function_exists('getTotalReturn')) {
    function getTotalReturn($storelocationid = 18, $date_from = "2022-09-01", $date_to = "2022-09-13")
    {
        $data = \App\Model\WaInventoryLocationItemReturn::select([
            '*',
            DB::RAW('SUM(return_quantity) as rtn_qty'),
            DB::RAW('SUM(return_quantity * (SELECT selling_price FROM wa_inventory_location_transfer_items where wa_inventory_location_transfer_items.id = wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id limit 1)) as rtn_total')
        ])
            ->with(['item_parent.getInventoryItemDetail', 'getTransferLocation', 'returned_by', 'getTransferLocation.toStoreDetail'])
            ->where(function ($w) use ($date_from, $date_to) {
                if ($date_from && $date_to) {
                    $w->whereBetween('return_date', [$date_from . ' 00:00:00', $date_to . " 23:59:59"]);
                }
            })
            ->whereHas('item_parent', function ($e) use ($storelocationid) {
                $e->where('to_store_location_id', $storelocationid);
            })->orderBy('return_date', 'DESC')->groupBy('return_grn')->paginate(100);
        $total_return = 0;
        if ($data->count() > 0) {
            foreach ($data as $key => $list) {
                $total_return += @$list->rtn_total;
            }
        }
        return $total_return;
    }
}

function checkEnforceMeterReading($vehicle_id = "", $entry_type = "")
{
    $loggedUserId = getLoggeduserProfile()->id;
    $checkOdometer = OdometerReadingHistory::where('user_id', $loggedUserId)->where('vehicle_id', $vehicle_id)->where('entry_type', $entry_type)->orderBy('id', 'DESC')->first();
    $odometer_reading = 0;
    if ($checkOdometer) {
        $odometer_reading = $checkOdometer->odometer_reading;
    }
    return (int)$odometer_reading;
}


function getVehicleList()
{
    $new_arr = array('All' => 'All Vehicles');
    $list = Vehicle::pluck('license_plate', 'id')->toArray();
    return count($list) > 0 ? $list : [];
}

function getTyrePositionList()
{
    $list = TyrePosition::pluck('title', 'id')->toArray();
    return count($list) > 0 ? $list : [];
}

function getTyreList()
{
    $list = TyreInventory::pluck('title', 'id')->toArray();
    return count($list) > 0 ? $list : [];
}

function addInternalRequisitionPermissions_N($wa_internal_requisition_id, $wa_department_id)
{
    $authorizers_users = WaDepartmentsAuthorizationRelations::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();
    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::select('id', 'authorization_level', 'email')->whereIn('id', $authorizers_users)
        ->orderBy('authorization_level', 'asc')
        ->get();
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new \App\Model\NWaInternalReqPermission();
            $row->user_id = $users->id;
            $row->wa_internal_requisition_id = $wa_internal_requisition_id;
            $row->approve_level = $users->authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();
            if ($i == 0) {
                $emp = User::select('email')->where('id', $users->id)->first();
                sendMailForInternalRequisition($emp->email, $row->wa_internal_requisition_id, $row->approve_level);
            }
            $i++;
        }
    } else {
        $row = \App\Model\NWaInternalRequisition::where('id', $wa_internal_requisition_id)->first();
        if ($row) {
            $row->status = 'APPROVED';
            $row->save();
        }
    }
}

function addTyrePurchaseOrderPermissions($wa_tyre_purchase_order_id, $wa_department_id)
{
    $authorizers_users = WaTyrePurchaseOrderAuthorization::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();
    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::select('id', 'purchase_order_authorization_level', 'email')->whereIn('id', $authorizers_users)
        ->orderBy('purchase_order_authorization_level', 'asc')
        ->get();
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new WaTyrePurchaseOrderPermission();
            $row->user_id = $users->id;
            $row->wa_tyre_purchase_order_id = $wa_tyre_purchase_order_id;
            $row->approve_level = $users->purchase_order_authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();
            if ($i == 0) {
                $emp = User::select('email')->where('id', $users->id)->first();
                sendMailForTyrePurchaseOrder($emp, $row->wa_tyre_purchase_order_id, $row->approve_level);
            }
            $i++;
        }
    } else {
        $row = WaTyrePurchaseOrder::where('id', $wa_tyre_purchase_order_id)->first();
        $row->status = 'APPROVED';
        $row->save();
    }
}


function sendMailForTyrePurchaseOrder($user, $wa_purchase_order_id, $approve_level)
{
    try {
        $row = WaTyrePurchaseOrder::with(['getrelatedEmployee', 'getStoreLocation'])->where('id', $wa_purchase_order_id)->first();
        $receiver = $user;
        $data = ['name' => $receiver->name, 'email' => $receiver->email, 'row' => $row];
        Mail::send('emails.sendpurchaseorder', ['data' => $data], function ($message) use ($data, $row) {
            $message->from('roy.karugu@gmail.com', 'Efficentrix');
            $message->to($data['email'])->subject('Purchase Order Approval Request - ' . date('Y-m-d') . ' ' . $row->purchase_no . ' / ' . @$row->getStoreLocation->location_name);
        });
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function getStoreLocationDropdownLimitByIds(array $data)
{
    $rows = WaLocationAndStore::select('location_code', 'location_name', 'id')->whereIn('id', $data)->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->location_name . ' (' . $row->location_code . ')';
    }
    return $data;
}

function getBankAccountBalanceByCode($bank_gl_code)
{
    $amount = 0;
    if ($bank_gl_code != "") {
        $amount = WaBanktran::where('bank_gl_account_code', $bank_gl_code)->sum('amount');
    }

    return $amount;
}

function userAppPermissions()
{
    return array(
        'sales' => 'Sales',
        'stocks' => 'Stocks',
        'stock_returns' => 'Stock Returns',
        'reports' => 'Reports',
        'manage_shift' => 'Manage Shift',
        'my_debtors' => 'My Debtors',
        'merge_cash_payments' => 'Merge Cash Payments',
        'bank_deposits' => 'Bank Deposits',
        'customer_order' => 'Customer Order',
        'expenses' => 'Expenses',
    );
}

if (!function_exists('getStatusAI')) {

    function getStatusAI($status)
    {
        $getStatusArray = getStatusArray();
        if (isset($getStatusArray[$status])) {
            return $getStatusArray[$status];
        }

        return '';
    }
}


if (!function_exists('getDeliveryFamilyAndSubfamilyGroup')) {

    function getDeliveryFamilyAndSubfamilyGroup()
    {
        //beer delivery sub family group
        $beerDeliverySubFamilyGroup = BeerKegCategory::select('id', 'name')->where('level', '3')->get();

        //rent a keg family group
        $RentAKegFamilyGroup = BeerKegCategory::select('id', 'name')->where('level', '2')->where('is_have_another_layout', '0')->get();

        $all_categories = [];
        foreach ($beerDeliverySubFamilyGroup as $cat) {
            $all_categories[$cat->id] = ucfirst($cat->name);
        }

        foreach ($RentAKegFamilyGroup as $cat) {
            $all_categories[$cat->id] = ucfirst($cat->name);
        }

        return $all_categories;
    }
}

if (!function_exists('getStatusArray')) {

    function getStatusArray()
    {
        $return = ['1' => 'Active', '0' => 'Inactive'];
        return $return;
    }
}

if (!function_exists('displayButton')) {

    function displayButton($buttonName = array())
    {
        $return = [];
        if (is_array($buttonName) && count($buttonName) > 0) {
            foreach ($buttonName as $key => $value) {
                $route = $value[0];
                $routeKey = isset($value[1]) ? $value[1] : [];
                //if(displayButtonPermission($route))
                $return[$key] = buttonHtml($key, route($route, $routeKey));
            }
        }
        return $return;
    }
}

/*
 * * Button With Html
 */
if (!function_exists('buttonHtml')) {

    function buttonHtml($key, $link)
    {
        $array = [
            "edit" => "<span class='f-left margin-r-5'><a data-toggle='tooltip'  class='btn btn-primary small-btn' title='Edit' href='" . $link . "'><i class='fa fa-pencil-square' aria-hidden='true'></i></a></span>",
            "Active" => '<span class="f-left margin-r-5"> <a data-toggle="tooltip" class="btn btn-success small-btn" title="Active" href="' . $link . '"><i class="fa fa-check" aria-hidden="true"></i></a></span>',
            "Inactive" => '<span class="f-left margin-r-5"> <a data-toggle="tooltip" class="btn btn-warning small-btn" title="Inactive" href="' . $link . '"><i class="fa fa-times" aria-hidden="true"></i></a></span>',
            "add" => '<a href="' . $link . '" class="btn col-md-11  btn-primary">Add</a>',
            "delete" => '<form method="POST" action="' . $link . '" accept-charset="UTF-8" style="display:inline"><input name="_method" value="DELETE" type="hidden">
' . csrf_field() . '<span><button data-toggle="tooltip" title="Delete" type="submit" class="btn btn-danger small-btn"><i class="fa fa-trash" aria-hidden="true"></i></button></span></form>',
            "view" => '<span class="f-left margin-r-5"><a data-toggle="tooltip"  class="btn btn-info small-btn" title="View" href="' . $link . '"><i class="fa fa-eye" aria-hidden="true"></i></a></span>',
            "purchaseData" => "<span class='span-action'> <a title='Maintain Purchasing Data' href='" . $link . "' ><i class='fa fa-shopping-cart' aria-hidden='true' style='font-size: 16px;'></i></a></span>",

        ];

        if (isset($array[$key])) {
            return $array[$key];
        }
        return '';
    }
}

if (!function_exists('buttonHtmlCustom')) {

    function buttonHtmlCustom($key, $link)
    {
        $array = [
            "edit" => "<span class='span-action'> <a title='Edit' href='" . $link . "' ><img src='" . asset('assets/admin/images/edit.png') . "'></a></span>",
            "show" => "<span class='span-action'> <a title='Show' href='" . $link . "' ><i class='fa fa-eye'></i></a></span>",
            "stock_movements" => "<span class='span-action'><a style='font-size: 16px;'  href='" . $link . "' ><i class='fa fa-list' title= 'Stock Movements'></i></a></span>",
            "stock_movements_2" => "<span class='span-action'><a style='font-size: 16px;'  href='" . $link . "' ><i class='fa fa-list' title= 'Stock Movements 2'></i></a></span>",
            "account_inquiry" => "<span class='span-action'><a style='font-size: 16px;'  href='" . $link . "' ><i class='fa fa-list' title= 'Account Inquiry'></i></a></span>",
            "stock_status" => "<span class='span-action'><a style='font-size: 16px;'  href='" . $link . "' ><i class='fa fa-book' title= 'Stock Status'></i></a></span>",
            "delete" => "<span class='span-action'><form title='Trash' action='" . $link . "' method='POST'><input type='hidden' name='_method' value='DELETE'><input type='hidden' name='_token' value='" . csrf_token() . "'><button><i class='fa fa-trash' aria-hidden='true' style='font-size: 16px;'></i></button></form></span>",
            "delete_right" => "<span><form title='Trash' action='" . $link . "' method='POST'><input type='hidden' name='_method' value='DELETE'><input type='hidden' name='_token' value='{{ csrf_token() }}'><button  style='float:left'><i class='fa fa-trash' aria-hidden='true'></i></button></form></span>",
            "remittance_advice" => "<span class='span-action'> <a title='Remittance Advice' href='" . $link . "' ><i class='fa fa-question' aria-hidden='true' style='font-size: 16px;'></i></a></span>",

            "enter_supplier_payment" => "<span class='span-action'> <a title='Enter Supplier Payment' href='" . $link . "' ><i class='fa fa-money' aria-hidden='true' style='font-size: 16px;'></i></a></span>",
            "purchaseData" => "<span class='span-action'> <a title='Maintain Purchasing Data' href='" . $link . "' ><i class='fa fa-shopping-cart' aria-hidden='true' style='font-size: 16px;'></i></a></span>",

            "edit_process" => "<span class='span-action mr-12'><a style='font-size: 16px;'  href='" . $link . "' ><i class='fa fa-pencil-square text-primary' title= 'Edit Process'></i></a></span>",
            "remove_process" => "<span class='span-action'><form title='Remove Process' action='" . $link . "' method='POST' style='display: inline-block;'><input type='hidden' name='_method' value='DELETE'><input type='hidden' name='_token' value='" . csrf_token() . "'><button><i class='fa fa-trash text-danger' title= 'Remove Process' style='font-size: 16px; cursor:pointer;'></i></button></form></span>",
        ];

        if (isset($array[$key])) {
            return $array[$key];
        }
        return '';
    }
}

if (!function_exists('keyExist')) {
}

if (!function_exists('getadminurl')) {

    function getadminurl($moduleName)
    {
        return url('admin/' . $moduleName);
    }
}

/*
** File Upload
*/
if (!function_exists('uploadwithresize')) {
    function uploadwithresize($file, $path, $h = null)
    {
        $h = 200;
        $w = 200;
        $fileName = time() . rand(111111111, 9999999999) . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path("uploads/$path/");
        //        $destinationPath = 'public/uploads/' . $path . '/';
        // upload new image
        Image::make($file->getRealPath())
            // original
            ->save($destinationPath . $fileName)
            // thumbnail
            ->resize($w, $h)
            ->save($destinationPath . 'thumb/' . $fileName)
            ->destroy();
        return $fileName;
    }
}


if (!function_exists('getgeaolocation')) {
    function getgeaolocation($address = null)
    {
        /********************************************************************************************
         * function for get geolocation by address
         *********************************************************************************************/
        $address = str_replace(' ', '+', $address);
        $url = "http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response);
        $main_array = array();
        if ($response_a->status == "OK") {
            if (isset($response_a->results[0]->geometry->location->lat) && isset($response_a->results[0]->geometry->location->lng)) {
                $main_array['latitude'] = $response_a->results[0]->geometry->location->lat;
                $main_array['longitude'] = $response_a->results[0]->geometry->location->lng;
            }
        }
        return $main_array;
    }
}

// remove file from folder
if (!function_exists('unlinkfile')) {
    function unlinkfile($path, $file_name)
    {
        $file1 = 'public/uploads/' . $path . '/' . $file_name;
        $file2 = 'public/uploads/' . $path . '/thumb/' . $file_name;
        File::delete($file1, $file2);
    }
}


// remove file from folder
if (!function_exists('getCategoryNameById')) {
    function getCategoryNameById($category_id)
    {
        $category_data = Category::whereId($category_id)->first();
        return $category_data->name;
    }
}
if (!function_exists('getlocationRowById')) {
    function getlocationRowById($id)
    {
        return WaLocationAndStore::select('location_code', 'location_name', 'id')->where('id', $id)->first();
    }
}


// remove file from folder
if (!function_exists('getDeliveryCategoryNameById')) {
    function getDeliveryCategoryNameById($category_id)
    {
        $category_data = BeerKegCategory::whereId($category_id)->first();
        return $category_data->name;
    }
}

if (!function_exists('mailSend')) {
    function mailSend($data)
    {
        try {
            Mail::send('emails.all', ['data' => $data], function ($message) use ($data) {
                $message->from(env('PROCUREMENT_EMAIL'), 'Brew Bistro');
                $message->to($data['email'])->subject("Forgot Password");
            });
            return true;
        } catch (Exception $ex) {
            //dd($ex->getMessage());
            return false;
        }
    }
}


if (!function_exists('reservationEmailSend')) {
    function reservationEmailSend($template, $data)
    {
        try {
            Mail::send($template, ['data' => $data], function ($message) use ($data) {
                $message->from(env('PROCUREMENT_EMAIL'), 'Brew Bistro');
                $message->to($data['send_to'])->subject($data['subject']);
            });
            return true;
        } catch (Exception $ex) {

            return false;
        }
    }
}


function send_sms($mobile, $sms)
{

    $post = [
        'issn' => 'KSFCHURCH',
        'msisdn' => '254' . (int)$mobile,
        'text' => $sms,
        'username' => 'ksfchurchnks',
        'password' => '55cae103d5394115b4181176c4d4530e'
    ];
    $url = "https://client.airtouch.co.ke:9012/sms/api/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $res = curl_exec($ch);

    return $res;
}

//change sms provider 
function sendMessageOld(array $phoneNumber, string $msg): void
{
    try {
        $payload = [
            "acc_no" => env("KANINI_SMS_ACC_NO"),
            "api_key" => env("KANINI_SMS_API_KEY"),
            "sender_id" => env("KANINI_SMS_SENDER_ID"),
            "message" => $msg,
            "msisdn" => $phoneNumber,
            "dlr_url" => "",
            "linkID" => ""
        ];

        $apiResponse = Http::post("https://isms.infosky.co.ke/sms2/api/v1/send-sms", $payload);
        if (!$apiResponse->ok()) {
            Log::info($apiResponse->body());
        }
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
}

function sendMessage(string $msg, string $phoneNumber): void
{
    try {
        $infoskyService = new InfoSkySmsService();
        $infoskyService->sendMessage($msg, $phoneNumber);
        // $payload = [
        //     "sender" => env("KANINI_SMS_SENDER_ID"),
        //     "message" => $msg,
        //     "phone" => $phoneNumber
        // ];
        // $apiResponse = Http::withHeaders([
        //     'Content-Type' => 'application/json',
        //     'Accept' => 'application/json',
        //     'Authorization' => env('KANINI_SMS_TOKEN'),
        // ])->post('https://bulk.infosky.co.ke/api/v1/send-sms', $payload);

        // if (!$apiResponse->ok()) {
        //     Log::info($apiResponse->body());
        // }
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
}

function sendOtp(string $msg, string $phoneNumber): void
{
    try {
        $infoskyService = new InfoSkySmsService();
        $infoskyService->sendOtp($msg, $phoneNumber);
        // $payload = [
        //     "sender" => env("KANINI_SMS_SENDER_ID"),
        //     "message" => $msg,
        //     "phone" => $phoneNumber
        // ];
        // $apiResponse = Http::withHeaders([
        //     'Content-Type' => 'application/json',
        //     'Accept' => 'application/json',
        //     'Authorization' => env('KANINI_SMS_TOKEN'),
        // ])->post('https://bulk.infosky.co.ke/api/v1/send-sms', $payload);

        // if (!$apiResponse->ok()) {
        //     Log::info($apiResponse->body());
        // }
    } catch (\Throwable $th) {
        Log::info($th->getMessage());
    }
}

// remove file from folder
if (!function_exists('convertDMYtoYMD')) {
    function convertDMYtoYMD($date)
    {
        $date = str_replace('/', '-', $date);
        return date('Y-m-d', strtotime($date));
    }
}

if (!function_exists('manageAmountFormat')) {
    function manageAmountFormat($amount)
    {
        return number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('manageOrderidWithPad')) {
    function manageOrderidWithPad($order_id)
    {
        $order_id = str_pad($order_id, 5, "0", STR_PAD_LEFT);
        return $order_id;
    }
}

if (!function_exists('getAssociateTableWithOrder')) {
    function getAssociateTableWithOrder($order)
    {
        $table_arr = [];
        foreach ($order->getAssociateTableWithOrder as $data) {
            $table_arr[] = $data->getRelativeTableData->name;
        }
        return implode(' ,', $table_arr);
    }
}


if (!function_exists('getAssociateWaiteWithOrder')) {
    function getAssociateWaiteWithOrder($order)
    {
        $table_arr = [];
        $waiter_arr = [];
        foreach ($order->getAssociateTableWithOrder as $data) {
            $table_arr[] = $data->getRelativeTableData->id;
        }
        if (count($table_arr) > 0) {
            $all_waiter_ids_arr = EmployeeTableAssignment::whereIn('table_manager_id', $table_arr)->pluck('user_id')->toArray();
            if (count($all_waiter_ids_arr) > 0) {
                $waiter_arr = User::whereIn('id', $all_waiter_ids_arr)->pluck('name')->toArray();
            }
        }
        return implode(' ,', $waiter_arr);
    }
}

if (!function_exists('getAssociateWaiterIdsWithOrder')) {
    function getAssociateWaiterIdsWithOrder($order)
    {
        $table_arr = [];
        $all_waiter_ids_arr = [];
        foreach ($order->getAssociateTableWithOrder as $data) {
            $table_arr[] = $data->getRelativeTableData->id;
        }
        if (count($table_arr) > 0) {
            $all_waiter_ids_arr = EmployeeTableAssignment::whereIn('table_manager_id', $table_arr)->pluck('user_id')->toArray();
        }
        return $all_waiter_ids_arr;
    }
}

if (!function_exists('getAssociateWaiteWithOrderWithBadge')) {
    function getAssociateWaiteWithOrderWithBadge($order)
    {
        $table_arr = [];
        $waiter_arr = [];
        $new_waiter_array = [];
        foreach ($order->getAssociateTableWithOrder as $data) {
            $table_arr[] = $data->getRelativeTableData->id;
        }
        if (count($table_arr) > 0) {
            $all_waiter_ids_arr = EmployeeTableAssignment::whereIn('table_manager_id', $table_arr)->pluck('user_id')->toArray();
            if (count($all_waiter_ids_arr) > 0) {
                $waiter_arr = User::select('name', 'badge_number')->whereIn('id', $all_waiter_ids_arr)->get();

                foreach ($waiter_arr as $wat) {
                    $new_waiter_array[] = ucfirst($wat->name);
                }
            }
        }
        return implode(' ,', $new_waiter_array);
    }
}

if (!function_exists('getLoggeduserProfile')) {
    function getLoggeduserProfile($user = null)
    {
        if (!$user) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if (!$user) {
                return null;
            }
        }

        $my_permissions = getPreviousPermissionsArray($user);
        $my_permissions_user_wise = getPreviousPermissionsByUserArray($user);

        $user->setAttribute('permissions', $my_permissions);
        $user->setAttribute('user_permissions', $my_permissions_user_wise);
        return $user;
    }
}


if (!function_exists('printBill')) {
    function printBill($user_id, $order_id, $print_type, $user_type)
    {
        if ($user_type == "P") {
            $user_detail = PrintClassUser::whereId($user_id)->first();
        } else {
            $user_detail = User::whereId($user_id)->first();
        }
        $order_detail = Order::whereId($order_id)->first();
        return view('admin.orders.receipt', compact('user_detail', 'order_detail', 'print_type', 'user_type'));
    }
}

if (!function_exists('printMultipleBillWithSingleHeader')) {
    function printMultipleBillWithSingleHeader($user_id, $order_id, $print_type, $user_type)
    {
        if ($user_type == "P") {
            $user_detail = PrintClassUser::whereId($user_id)->first();
        } else {
            $user_detail = User::whereId($user_id)->first();
        }
        $order_detail = Order::whereId($order_id)->first();
        return view('admin.orders.billwithoutheaderreceipt', compact('user_detail', 'order_detail', 'print_type', 'user_type'));
    }
}

if (!function_exists('getBillHeader')) {
    function getBillHeader($user_id, $order_id, $print_type, $user_type)
    {
        if ($user_type == "P") {
            $user_detail = PrintClassUser::whereId($user_id)->first();
        } else {
            $user_detail = User::whereId($user_id)->first();
        }
        $order_detail = Order::whereId($order_id)->first();
        return view('admin.orders.billheader', compact('user_detail', 'order_detail', 'print_type', 'user_type'));
    }
}


function setUpPermissions()
{
    $permissions = [
        'dashboard' => ['view' => 'view'],
        'module-dashboards' => ['sales-and-receivables' => 'sales-and-receivables'],

        'account-receivables' => ['view' => 'view'],

        'sales-and-receivables' => ['view' => 'view'],
        'reconciliation' => ['view' => 'view', 'reconcile' => 'reconcile', 'suspend' => 'suspend', 'upload' => 'upload', 'verification' => 'verification', 'approval' => 'approval', 'debtor-trans' => 'debtor-trans', 'bank-statement-upload' => 'bank-statement-upload'],
        'dispatch-and-close-loading-sheet' => ['view' => 'view'],
        'dispatched-loading-sheets' => ['view' => 'view', 'view-all' => 'view-all',],


        'proforma-invoice' => ['view' => 'view', 'edit' => 'edit', 'delete' => 'delete', 'add' => 'add', 'view-all' => 'view-all'],


        'maintain-customers' => [
            'view' => 'view',
            'edit' => 'edit',
            'delete' => 'delete',
            'add' => 'add',
            'enter-customer-payment' => 'enter-customer-payment',
            'print-receipts' => 'print-receipts',
            'allocate-receipts' => 'allocate-receipts'
        ],
        'customer-centre' => ['view' => 'view', 'customer-statement' => 'customer-statement', 'route-customers' => 'route-customers'],
        'location-and-stores' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        // 'sales-invoices' => ['view' => 'view', 'edit' => 'edit', 'delete' => 'delete', 'add' => 'add', 'view-all' => 'view-all', 'sales-item-reserve-transaction' => 'sales-item-reserve-transaction', 'draw-stock-from' => 'draw-stock-from','posted-receipt'=>'posted-receipt'],

        'sales-invoice' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'edit-values' => 'edit-values',
            'route-customer' => 'route-customer',
            'confirm-invoice' => 'confirm-invoice',
            'confirm-invoice-r' => 'confirm-invoice-r'
        ],
        'confirm-invoice' => ['view' => 'view'],
        'confirm-invoice-r' => ['view' => 'view'],
        'print-invoice-delivery-note' => ['view' => 'view', 'return' => 'return', 'print' => 'print', 'pdf' => 'pdf'],
        'route-customers-overview' => ['view' => 'view'],
        'processed-returns' => ['view' => 'view'],
        'rejected-returns' => ['view' => 'view'],
        'abnormal-returns' => ['view' => 'view'],
        'confirm-returns' => ['view' => 'view', 'confirm' => 'confirm'],

        //approver level
        'approver-limit-returns' => ['view' => 'view', 'approver-1' => 'approver-1', 'approver-2' => 'approver-2', 'late-returns' => 'late-returns'],

        //Fleet Management Permission
        'fleet-management-module' => ['view' => 'view'], // main
        'vehicle-suppliers' => ['view' => 'view', 'add' => 'add'],
        'vehicles' => ['view' => 'view', 'edit' => 'edit'],
        'fuel-stations' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'fuel-lpos' => ['view' => 'view', 'add' => 'add', 'archive' => 'archive'],
        //End Fleet Management Permission

        'credit-note' => ['view' => 'view', 'edit' => 'edit', 'delete' => 'delete', 'add' => 'add', 'view-all' => 'view-all'],

        'pos-cash-sales' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'show' => 'show',
            'edit-values' => 'edit-values',
            'return' => 'return',
            'return-list' => 'return-list',
            'print' => 'print',
            'pdf' => 'pdf',
            're-print' => 're-print',
            'resign-esd' => 'resign-esd',
            'save' => 'save',
            'process' => 'process',
            'dispatch' => 'dispatch',
            'dispatch-progress' => 'dispatch-progress',
            'archive' => 'archive',
            'show-total' => 'show-total',
            'dispatch-slip' => 'dispatch-slip',
            'delayed-orders' => 'delayed-orders',
        ],

        'pos-cash-sales-r' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'show' => 'show', 'edit-values' => 'edit-values', 'return' => 'return', 'return-list' => 'return-list', 'print' => 'print', 'pdf' => 'pdf', 're-print' => 're-print'],

        'pos-cash-sales-new' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'show' => 'show',
            'edit-values' => 'edit-values',
            'return' => 'return',
            'return-list' => 'return-list',
            'print' => 'print',
            'pdf' => 'pdf'
        ],

        'petty-cash' => ['view' => 'view', 'add' => 'add', 'print' => 'print', 'pdf' => 'pdf', 're-print' => 're-print', 'edit' => 'edit', 'destroy' => 'destroy'],
        'dispatch-pos-invoice-sales' => ['dispatch' => 'dispatch', 'dispatch-report' => 'dispatch-report'], // report
        'invoice-dispatch-report' => ['detailed' => 'detailed', 'summary' => 'summary'],

        // reports
        'summary-report' => ['detailed' => 'detailed', 'summary' => 'summary', 'inventory_sales_report' => 'inventory_sales_report', 'detailed_sales_report' => 'detailed_sales_report', 'sales_by_date_report' => 'sales_by_date_report', 'sales_summary' => 'sales_summary'],
        'merged-payments' => ['view' => 'view', 'reverse-transaction' => 'reverse-transaction'],
        'cash-sales' => ['view' => 'view', 'reserve-transaction' => 'reserve-transaction'],
        'salesman-shift' => ['view' => 'view', 'reopen-from-backend' => 'reopen-from-backend'],
        'end-of-day-utility' => ['detailed' => 'detailed'],

        'sales-commission-bands' => ['view' => 'view'],

        'payment-reconcilliation' => ['view' => 'view'],

        'route-profitibility-report' => ['view' => 'view'],

        'sales-and-receivables-reports' => [
            'view' => 'view',
            'customer_invoices' => 'customer_invoices',
            'daily-cash-receipt-summary' => 'daily-cash-receipt-summary',
            'customer-aging-analysis' => 'customer-aging-analysis',
            'customer-detailed-summary' => 'customer-detailed-summary',
            'salesman-detailed-summary' => 'salesman-detailed-summary',
            'daily-gp-report' => 'daily-gp-report',
            'monthly-gp-report' => 'monthly-gp-report',
            'shift-summary' => 'shift-summary',
            'salesman-trip-summary' => 'salesman-trip-summary',
            'customer_sales_summary' => 'customer_sales_summary',
            'salesman-summary' => 'salesman-summary',
            'showroom-shift-summary' => 'showroom-shift-summary',
            'showroom-sales-summary' => 'showroom-sales-summary',
            'showroom-sales-item' => 'showroom-sales-item',
            'customer-statement' => 'customer-statement',
            'vat-report' => 'vat-report',
            'vat-report-dropdown-type' => 'vat-report-dropdown-type',
            'gross-profit' => 'gross-profit',
            'dashboard-report' => 'dashboard-report',
            'loading-schedule-vs-stock-report' => 'loading-schedule-vs-stock-report',
            'delivery-schedule-report' => 'delivery-schedule-report',
            'customer-balances-report' => 'customer-balances-report',
            'till-direct-banking-report' => 'till-direct-banking-report',
            'route-performance-report' => 'route-performance-report',
            'group-performance-report' => 'group-performance-report',
            'invoice-balancing-report' => 'invoice-balancing-report',
            'promotion-sales' => 'promotion-sales',
            'sales-per-supplier-per-route' => 'sales-per-supplier-per-route',
            'sales-analysis' => 'sales-analysis',
            'archived-orders' => 'archived_orders_report',
            'discount-sales' => 'discount-sales',
            'onsite-vs-offsite-shifts-report' => 'onsite-vs-offsite-shifts-report',
            'unbalanced_invoices_report' => 'unbalanced_invoices_report',
        ],

        'route-reports' => ['weekly-sales-report' => 'weekly-sales-report'],

        'route-manager' => ['view' => 'view'],

        'route-customers' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'remove' => 'remove',
            'verify' => 'verify',
            'approve' => 'approve',
            'overview' => 'overview',
            'listing' => 'listing',
            'onboarding-requests' => 'onboarding-requests',
            'approval-requests' => 'approval-requests',
            'field-visits' => 'field-visits',


        ],

        'routes' => [
            'add' => 'add',
            'edit' => 'edit',
            'remove' => 'remove',
        ],


        /* permission for beer and keg start from here */
        // 'beer-delivery-and-keg-setup' => ['view' => 'view'],
        // 'beer-and-keg-sub-major-group' => ['view' => 'view', 'edit' => 'edit'],
        // 'delivery-family-groups' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        // 'delivery-sub-family-groups' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        // 'delivery-items' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        /* permission for beer and keg end from here */

        /* permission for web accounting start from here */

        'genralLedger' => ['view' => 'view'], // report

        'financial-management' => ['view' => 'view'],
        'general-ledger' => ['view' => 'view'],
        'edit-ledger' => ['view' => 'view'],
        'maintain-wallet' => ['view' => 'view'],
        // 'bank-accounts' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'bank-accounts' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'account-inquiry' => 'account-inquiry', 'bankdeposit' => 'bank deposit', 'banktransfer' => 'bank transfer'],

        'expenses' => ['view' => 'view', 'expense' => 'expense', 'bill' => 'bill', 'cheque' => 'cheque'],
        'journal-entries' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'processed' => 'processed'],
        'journal-inquiry' => ['view' => 'view'],
        'general-ledger-reports' => [
            'view' => 'view',
            'transaction-summary' => 'transaction-summary',
            'detailed-transaction-summary' => 'detailed-transaction-summary',
            'detailed-trial-balance' => 'detailed-trial-balance',
            'p-l-monthly-report' => 'p-l-monthly-report'
        ],
        'trial-balances' => ['view' => 'view'],

        'account-sections' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'account-groups' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'chart-of-accounts' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'dimensions' => ['view' => 'view'],
        'wallet-matrix' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'branches' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'departments' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'finanacial-system-setup' => ['view' => 'view'],
        'company-preferences' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'tax-manager' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'currency-managers' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'accounting-periods' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'roles' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'employees' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'change_password' => 'Change Password'],
        'finanacial-inventory' => ['view' => 'view'],
        'stock-type-categories' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'stock-family-groups' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'inventory-categories' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],

        'unit-of-measures' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'finanacial-receivables-payables' => ['view' => 'view'],
        'payment-terms' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'payment-methods' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'payment-modes' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'payment-providers' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'account-payables' => ['view' => 'view', 'approved-pending-payments-voucher-report' => 'approved-pending-payments-voucher-report'], // last permission is a report
        'maintain-suppliers' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'delete' => 'delete',
            'trade-agreement-view' => 'trade-agreement-view',
            'approve-new-supplier' => 'approve-new-supplier',
            'approve-edits-supplier' => 'approve-edits-supplier',
            'supplier-portail-joining-email' => 'supplier-portail-joining-email',
            'remittance-advice' => 'remittance-advice',
            'enter-supplier-payment' => 'enter-supplier-payment',
            'trade-agreement-change-request-list' => 'trade-agreement-change-request-list',
            'vendor-centre' => 'vendor-centre',

            'can-view-all-suppliers' => 'can-view-all-suppliers'
        ],
        'trade-agreement' => ['view' => 'view', 'lock' => 'lock', 'view-all' => 'view-all'],
        'suppliers-overview' => ['view' => 'view'],
        'suppliers-invoice' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],

        'advance-payments' => ['view' => 'view', 'add' => 'add', 'delete' => 'delete'],

        'payment-vouchers' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],

        'processed-invoices' => ['view' => 'view', 'edit' => 'edit', 'reverse' => 'reverse'],

        'bank-files' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],

        'withholding-files' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],

        'credit-debit-notes' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],

        'inventory' => ['view' => 'view'],


        'maintain-item' => ['view' => 'view'],
        'account-payables-reports' => ['view' => 'view'],
        'maintain-items' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'delete' => 'delete',
            'manage-item-stock' => 'manage-item-stock',
            'manage-category-pricing' => 'manage-category-pricing',
            'maintain-purchasing-data' => 'maintain-purchasing-data',
            'assign-inventory-items' => 'assign-inventory-items',
            'price-change-history' => 'price-change-history',
            'suggested_order_report' => 'suggested_order_report',
            'manage-standard-cost' => 'manage-standard-cost',
            'item_price_pending_list' => 'item_price_pending_list',
            'item_price_history' => 'item_price_history',

            'negetive_stock_report' => 'negetive_stock_report',
            'inventory-location-stock-report' => 'inventory-location-stock-report',
            'inventory-location-as-at' => 'inventory-location-as-at',
            'price-update-upload' => 'price-update-upload',
            'manage-discount' => 'manage-discount',
            'approve-discount' => 'approve-discount',
            'manage-standard-cost-manual' => 'manage-standard-cost-manual',
            'update-bin-location' => 'update-bin-location',
            'view-stock-status' => 'view-stock-status',
            'edit-max-stock' => 'edit-max-stock',
            'edit-reorder-level' => 'edit-reorder-level',
            'manage-promotions' => 'manage-promotions',
            'item-approval' => 'item-approval',
            'route-pricing' => 'route-pricing',
        ],
        'inventory-item-adjustment' => ['view' => 'view', 'add' => 'add'],
        'stock-breaking' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],
        'stock-auto-breaks' => ['view' => 'view', 'dispatch' => 'dispatch'],
        'weighted-average-history' => ['view' => 'view'],
        'inventory-purchase-orders' => ['view' => 'view'],
        'receive-purchase-order' => ['view' => 'view', 'edit-price' => 'edit-price'],
        'process-receive-purchase-order' => ['view' => 'view'],
        'confirmed-receive-purchase-order' => ['view' => 'view'],
        'pending-returns-receive-purchase-order' => ['view' => 'view'],
        'return-accepted-receive-order' => ['view' => 'view'],
        'completed-grn' => ['view' => 'view'],
        'transfers' => ['view' => 'view', 'add' => 'add', 'return-list' => 'return-list', 'resign-esd' => 'resign-esd'],

        'return-to-supplier' => ['view' => 'view'],
        'return-to-supplier-from-grn' => [
            'view' => 'view',
            'create' => 'create',
            'approve' => 'approve',
            'view-pending' => 'view-pending',
            'view-approved' => 'view-approved',
            'print' => 'print',
        ],
        'return-to-supplier-from-store' => [
            'view' => 'view',
            'create' => 'create',
            'approve' => 'approve',
            'view-pending' => 'view-pending',
            'view-pending-details' => 'view-pending-details',
            'view-approved' => 'view-approved',
            'print' => 'print',
        ],

        'internal-requisitions' => ['view' => 'view', 'add' => 'add'],
        'issue-fullfill-requisition' => ['view' => 'view'],
        'processed-requisition' => ['view' => 'view'],
        'authorise-requisitions' => ['view' => 'view'],

        'match-purchase-orders' => ['view' => 'view', 'add' => 'add'],
        'delivery-notes' => ['view' => 'view', 'add' => 'add'],
        'delivery-notes-invoices' => ['view' => 'view', 'add' => 'add'],
        'delivery-notes-schedules' => ['view' => 'view'],

        'inventory-reports' => [
            'view' => 'view',
            'supplier-product-reports' => 'supplier-product-reports',
            'grn-reports' => 'grn-reports',
            'export-transfer-general' => 'export-transfer-general',
            'export-internal-requisitions' => 'export-internal-requisitions',
            'location-wise-movement' => 'location-wise-movement',
            'grn-summary' => 'grn-summary',
            'inventory-valuation-report' => 'inventory-valuation-report',
            'max-stock-report' => 'max-stock-report',
            'no-supplier-items-report' => 'no-supplier-items-report',
            'inventory-location-stock-report' => 'inventory-location-stock-report',
            'child-vs-mother-qoh' => 'child-vs-mother-qoh',
            'out-of-stock-report' => 'out-of-stock-report',
            'average-sales-report' => 'average-sales-report',
            'missing-items-report' => 'missing-items-report',
            'items-data-sales' => 'items-data-sales',
            'grn-summary-by-supplier-report' => 'grn-summary-by-supplier-report',
            'items-data-sales' => 'items-data-sales',
            'suggested_order_report' => 'suggested_order_report',
            'discount-items' => 'discount-items',
            'items-data-purchases' => 'items-data-purchases',
            'promotion-items' => 'promotion-items',
            'price-timeline-report' => 'price-timeline-report',
            'item-sales-route-performance-report' => 'item-sales-route-performance-report',
            'overstock-report' => 'overstock-report',
            'sub-distributor-suppliers-report' => 'sub-distributor-suppliers-report',
            'slow-moving-items-report' => 'slow-moving-items-report',
            'inactive-stock-report' => 'inactive-stock-report',
            'dead-stock-report' => 'dead-stock-report',
            'inventory-location-as-at' => 'inventory-location-as-at',
            'transfer-inwards-report' => 'transfer-inwards-report',
            'CTN-without-children' => 'CTN-without-children',
            'slow-moving-items-report' => 'slow-moving-items-report',
            'price-timeline-report' => 'price-timeline-report',
            'missing-split-report' => 'missing-split-report',
            'item-sales-route-performance-report' => 'item-sales-route-performance-report',
            'supplier-user-report' => 'supplier-user-report',
            'items-list-report' => 'items-list-report',
            // 'procurement-salesman-reported-issues' => 'procurement-salesman-reported-issues'

        ],

        'utility' => [
            'view' => 'view',
            'update' => 'update',
            'supplier-user-management' => 'supplier-user-management',
            'recalculate_qoh' => 'recalculate_qoh'
        ],


        'purchases' => ['view' => 'view'],


        'store-c' => ['view' => 'view'],
        'store-c-inventory' => ['view' => 'view', 'delete' => 'delete', 'adjustment' => 'adjustment'],
        'store-c-receive' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'edit-values' => 'edit-values'],

        'store-c-requisitions' => ['view' => 'view', 'add' => 'add', 'add-2' => 'add-2', 'edit' => 'edit', 'delete' => 'delete', 'edit-values' => 'edit-values'],

        'store-c-issue' => ['view' => 'view', 'edit' => 'edit', 'processed' => 'processed'],
        'store-c-stock-take' => ['view' => 'view', 'freeze' => 'freeze', 'add' => 'add'],


        'supreme-store' => ['view' => 'view'],
        'supreme-store-inventory' => ['view' => 'view', 'delete' => 'delete', 'adjustment' => 'adjustment'],
        'supreme-store-receive' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'edit-values' => 'edit-values'],
        'supreme-store-requisitions' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'edit-values' => 'edit-values'],
        'supreme-store-issue' => ['view' => 'view', 'edit' => 'edit', 'processed' => 'processed'],
        'supreme-store-stock-take' => ['view' => 'view', 'freeze' => 'freeze', 'add' => 'add'],

        'purchase_requisitions' => ['view' => 'view'],

        'external-requisitions' => ['view' => 'view', 'add' => 'add', 'external-requisition-report' => 'external-requisition-report'],
        'approve-external-requisitions' => ['view' => 'view'],
        'resolve-requisition-to-lpo' => ['view' => 'view', 'add' => 'add'],
        'suggested-orders' => ['view' => 'view'],
        'purchase_orders_module' => ['view' => 'view'],
        'purchase-orders' => ['view' => 'view', 'add' => 'add', 'view-all' => 'view-all', 'hide' => 'hide'],
        'approve-lpo' => ['view' => 'view'],
        'archived-lpo' => ['view' => 'view'],

        'purchase-order-status' => ['view' => 'view'],

        'purchases-reports' => [
            'view' => 'view',
            'purchases-by-store-location' => 'purchases-by-store-location',
            'purchases-by-family-group' => 'purchases-by-family-group',
            'purchases-by-supplier' => 'purchases-by-supplier',
            'lpo-status-and-leadtime' => 'lpo-status-and-leadtime',
        ], // reports


        'stock-take' => ['view' => 'view', 'add' => 'add', 'freeze' => 'freeze'],
        'stock-counts' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'compare-counts-vs-stock-check' => ['view' => 'view'],
        'deviation-report' => ['view' => 'view'],
        'stock-count-process' => ['view' => 'view'],
        'stock-count-variance' => ['view' => 'view'],
        'recipes' => [
            'view' => 'view',
            'add' => 'add',
            'edit' => 'edit',
            'delete' => 'delete',
            'ingredient-view' => 'ingredient-view',
            'ingredient-add' => 'ingredient-add',
            'ingredient-edit' => 'ingredient-edit',
            'ingredient-delete' => 'ingredient-delete'
        ],
        'pack-size' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'item-sub-categories' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'priority-level' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],

        /* permissions for production processes */
        'financial-production' => ['view' => 'view'],
        'processes' => ['view' => 'view', 'edit' => 'edit', 'delete' => 'delete'],

        /* permissions for production work orders */
        'inventory-production' => ['view' => 'view'], // not in code
        'work-orders' => ['view' => 'view', 'edit' => 'edit', 'delete' => 'delete'], // removed from code
        /* permission to manage users denied system access */
        'access-denied' => ['edit' => 'edit'],
        // login activity permissions
        'log-in-activity' => ['view' => 'view'],

        'order-taking' => ['view' => 'view'],
        'order-taking-overview' => ['view' => 'view'], // not in code
        'order-taking-schedules' => ['view' => 'view', 'reopen-requests' => 'reopen-requests', 'offsite-requests' => 'offsite-requests'],
        'reported-shift-issues' => ['view' => 'view'],
        'dispatch-and-delivery' => ['view' => 'view'],
        'store-loading-sheet' => ['view' => 'view', 'view-undispatched' => 'view-undispatched'],
        'dispatched-loading-sheets' => ['view' => 'view'],
        'delivery-schedule' => ['view' => 'view', 'issue-gate-pass' => 'issue-gate-pass',],
        'shift_delivery_report' => ['view' => 'view'],
        'pos-dispatch' => ['view' => 'view'],

        'alerts-and-notifications' => ['view' => 'view'],
        'alerts' => ['view' => 'view'],
        'scheduled-alerts' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],

        'supplier-portal' => ['view' => 'view'],

        'pending-suppliers' => ['view' => 'view', 'invite' => 'invite'],
        'order-delivery-slots' => ['view' => 'view', 'show' => 'show', 'add' => 'add', 'edit' => 'edit', 'delete' => 'delete'],
        'lpo-portal-req-approval' => ['view' => 'view'],
        'teams' => ['view' => 'view', 'add' => 'add', 'edit' => 'edit'],
        'item-demands' => ['view' => 'view', 'convert' => 'convert', 'edit-demand-quantity' => 'edit-demand-quantity'],
        'return-demands' => ['view' => 'view', 'convert' => 'convert'],
        'general_ledger_reports' => [''],
        'account-payables-reports' => ['view' => 'view'],
        'reports-category' => ['create-category' => 'create-category', 'create-report' => 'create-report', 'sort-report' => 'sort-report'],
        'tgeneral-ledger-reports' => ['detailed-trial-balance' => 'detailed-trial-balance'],
        'route-reports' => ['weekly-sales-report' => 'weekly-sales-report'],
        'route-profitibility-report' => ['view' => 'view'],
        'cashier-management' => ['view' => 'view', 'transaction' => 'transactions'],
        'tender-entry' => [
            'view' => 'view',
            'transaction' => 'transactions',
            'summery' => 'summery',
        ]
    ];

    return $permissions;
}

require_once __DIR__ . '/Permissions/ManagementDashboard.php';
require_once __DIR__ . '/Permissions/SalesAndReceivables.php';
require_once __DIR__ . '/Permissions/DeliveryAndLogistics.php';
require_once __DIR__ . '/Permissions/Purchases.php';
require_once __DIR__ . '/Permissions/SupplierPortal.php';
require_once __DIR__ . '/Permissions/AccountPayables.php';
require_once __DIR__ . '/Permissions/Inventory.php';
require_once __DIR__ . '/Permissions/HrAndPayroll.php';
require_once __DIR__ . '/Permissions/GeneralLedger.php';
require_once __DIR__ . '/Permissions/FleetManagement.php';
require_once __DIR__ . '/Permissions/AssetManagement.php';
require_once __DIR__ . '/Permissions/HelpDesk.php';
require_once __DIR__ . '/Permissions/SystemAdministration.php';
require_once __DIR__ . '/Permissions/CommunicationCenter.php';

function getPreviousPermissionsArray($user)
{
    if (!$user) {
        return [];
    }
    
    $list = $user->rolePermissions ?? collect([]);
    $previous_data = array();
    foreach ($list as $data) {
        $previous_data[$data->module_name . '___' . $data->module_action] = $data->module_action;
    }
    return $previous_data;
}

function getPreviousPermissionsByUserArray($user)
{
    if (!$user) {
        return [];
    }
    
    $list = $user->userPermissions ?? collect([]);
    $previous_data = array();
    foreach ($list as $data) {
        $previous_data[$data->module_name . '___' . $data->module_action] = $data->module_action;
    }
    return $previous_data;
}

function getwaiterNameForReceipt($receipt_id)
{
    $waiter_name = '-';
    $row = OrderReceiptRelation::select('order_id')->where('order_receipt_id', $receipt_id)->first();
    if ($row) {
        $order = Order::select('id')->where('id', $row->order_id)->first();
        $waiter_name = ucfirst(getAssociateWaiteWithOrder($order));
    }
    return $waiter_name;
}

function getTableBlockSection()
{

    return [
        'Gazebo' => 'Gazebo',
        'Gazebo_small' => 'Gazebo Small',
        'Pond' => 'Pond',
        'Terrace' => 'Terrace',
        'Boma' => 'Boma',
        'Grill_Gazebo' => 'Grill Gazebo',
        'Peacock' => 'Peacock',
        'Picnic' => 'Picnic',
        'Glass_Tent' => 'Glass Tent',
        'Impala_Gardens' => 'Impala Gardens',
        'Crane_Gardens' => 'Crane Gardens',
        'Hyrax_Gardens' => 'Hyrax Gardens',
        'Hyrax Extension' => 'Hyrax Extension',
    ];
}

function getAllsalesmanList()
{
    $user_data = User::with(['userRole'])
        ->get()
        ->filter(function (User $user) {
            if ($user->userRole) {
                return $user->userRole->slug == 'sales-man';
            } else {
                return;
            }
        })->pluck('name', 'id');
    return $user_data;
}


function getAllStoreKeepers()
{
    return User::with(['userRole'])->get()->filter(function (User $user) {
        $userRole = $user->userRole->slug;
        return ($userRole == 'store-keeper') || ($userRole == 'dispatcher') || ($userRole == 'storekeeper') || ($userRole == 'dispatch');
    })->pluck('name', 'id');
}

function pageRestrictedMessage(): string
{
    return 'The page you tried to access is restricted. Please contact your system administrator.';
}

function returnAccessDeniedPage(): \Illuminate\Contracts\View\View
{
    return view('utility_files.access_denied');
}

function getSalesmanName($id)
{
    $user_data = User::where('role_id', '4')->where('id', $id)
        ->first();
    if ($user_data) {
        $user_data = $user_data->name;
    } else {
        $user_data = "--";
    }
    return $user_data;
}

function getRouteSalesmen($id)
{
    $route = Route::find($id);
    if ($route->users) {
        return $route->users->filter(function ($user) {
            return $user->userRole?->slug == 'sales-man';
        })->first();
    }
}

function getAllsalesmanLists()
{
    $user_data = User::where('role_id', '4')
        ->pluck('name', 'wa_location_and_store_id');
    return $user_data;
}

function getAllShiftList()
{
    $user_data = \App\SalesmanShift::with(['salesman', 'salesman_route'])
        ->orderBy('id', 'DESC')
        ->get()
        ->mapWithKeys(function ($shift) {
            $routeName = $shift->salesman_route ? $shift->salesman_route->route_name : 'Unknown Route';
            $salesmanName = $shift->salesman ? $shift->salesman->name : 'Unknown Salesman';
            $shiftDate = $shift->created_at ? $shift->created_at->format('Y-m-d') : 'Unknown Date';
            $shiftType = ucfirst($shift->shift_type ?? 'Unknown');
            $status = ucfirst($shift->status ?? 'Unknown');
            
            $label = "{$salesmanName} - {$routeName} ({$shiftDate}) - {$shiftType} - {$status}";
            return [$shift->id => $label];
        });
    return $user_data;
}

function getLoadingShiftList()
{
    $shifts = WaShift::orderBy('id', 'DESC')
        ->where('status', 'close')
        ->where('parking_list_status', 'open')
        ->pluck('shift_id', 'id');

    return $shifts;
}

function getAllShiftLists()
{
    $user_data = \App\SalesmanShift::with(['salesman', 'salesman_route'])
        ->orderBy('id', 'DESC')
        ->get()
        ->mapWithKeys(function ($shift) {
            $routeName = $shift->salesman_route ? $shift->salesman_route->route_name : 'Unknown Route';
            $salesmanName = $shift->salesman ? $shift->salesman->name : 'Unknown Salesman';
            $shiftDate = $shift->created_at ? $shift->created_at->format('Y-m-d') : 'Unknown Date';
            $shiftType = ucfirst($shift->shift_type ?? 'Unknown');
            $status = ucfirst($shift->status ?? 'Unknown');
            
            $label = "{$salesmanName} - {$routeName} ({$shiftDate}) - {$shiftType} - {$status}";
            return [$shift->id => $label];
        });
    return $user_data;
}

function getSalesmanListById($id)
{
    $user_data = User::where('role_id', '4')->where('wa_location_and_store_id', $id)->orWhere('id', $id)
        ->first()->name;
    return $user_data;
}

function getSalesmanUserById($id)
{

    $user_data = User::find($id);
    if (!$user_data) {
        return '';
    }

    return $user_data->name;
}

function getUserIdBySalesmanId($id)
{
    $user_data = User::where('role_id', '4')->where('wa_location_and_store_id', $id)
        ->first()->id;
    return $user_data;
}

function getUserData($id)
{
    return User::find($id);
}

function getlShiftsByIds($id)
{
    $user_data = WaShift::whereIn('id', $id)->pluck('shift_id')->toArray();
    return $user_data;
}


function getAllRelatedReceiptsBywaiterName($search_keyword)
{
    $all_related_tables = [0];
    $user_data = User::where('role_id', '4')
        ->where('name', 'LIKE', "%{$search_keyword}%")
        ->get();
    //  echo '<pre>';
    foreach ($user_data as $data) {
        $all_related_table = $data->getAssignedTableForWaiter->pluck('table_manager_id')->toArray();
        $all_related_tables = array_merge($all_related_tables, $all_related_table);
    }
    $all_order = OrderBookedTable::whereIn('table_id', $all_related_tables)->pluck('order_id')->toArray();
    $all_related_receipt = [0];
    if (count($all_order) > 0) {
        $all_related_receipt = OrderReceiptRelation::whereIn('order_id', $all_order)->pluck('order_receipt_id')->toArray();
        $all_related_receipt = array_values(array_unique($all_related_receipt));
    }


    return $all_related_receipt;
}

function getFamilyGroupsListByOrderId($order_id)
{
    $famil_froup_string = '';
    $all_food_item_id = OrderedItem::select('food_item_id')->where('order_id', $order_id)->pluck('food_item_id')->toArray();
    if (count($all_food_item_id) > 0) {
        $all_items = FoodItem::select('id')->whereIn('id', $all_food_item_id)->get();

        $family_groups = [];
        foreach ($all_items as $item) {
            if (!isset($family_groups[$item->getItemCategoryRelation->category_id])) {
                $family_groups[$item->getItemCategoryRelation->category_id] = strtoupper(getCategoryNameById($item->getItemCategoryRelation->category_id));
            }
        }
        $famil_froup_string = implode(', ', $family_groups);
    }
    return $famil_froup_string;
}


function getSaleRepresentative()
{
    return User::where('role_id', 105)->orderBy('name', 'asc')->pluck('name', 'id')->toArray();
}

function getRepresentativeDeleteStatus($representative_id)
{
    $all_related_order = DeliveryOrderSaleRepRelation::where('representative_id', $representative_id)->pluck('delivery_order_id')->toArray();

    $status = true;

    if (count($all_related_order) > 0) {
        $is_exist = DeliveryOrder::whereIn('id', $all_related_order)->whereIN('status', ['CONFIRMED', 'PAID'])->first();

        if ($is_exist) {
            $status = false;
        }
    }

    return $status;
}

function getSubAccountSectionDropdown()
{
    return \App\Model\WaSubAccountSection::pluck('section_name', 'id');
}


//web accounting helpers start


function getUserRoleStockParameter($user_id)
{
    $is_exist = User::select('role_id')->where('id', $user_id)->first();
    if ($is_exist && $is_exist->role_id == '4') {
        return 'show_to_waiter';
    } else {
        return 'show_to_customer';
    }
}


function getAccountSectionDropdown()
{
    $all_sections = WaAccountSection::orderBy('section_name')->get();
    $arr = [];
    foreach ($all_sections as $section) {
        $arr[$section->id] = $section->section_name . ' (' . $section->section_number . ')';
    }
    return $arr;
}

function getParentAccountGroupsDropdown($leave_id = null)
{
    $all_sections = WaAccountGroup::where('is_parent', '1')->orderBy('group_name')->get();
    $arr = [];
    foreach ($all_sections as $section) {

        $arr[$section->id] = $section->group_name;
    }

    if ($leave_id) {
        unset($arr[$leave_id]);
    }
    return $arr;
}

function getAccountGroupsDropdown()
{
    $all_sections = WaAccountGroup::orderBy('group_name')->get();
    $arr = [];
    foreach ($all_sections as $section) {

        $arr[$section->id] = $section->group_name;
    }


    return $arr;
}


function getMonthsNameToNumber($monthNum = 0)
{
    $monthNum = (int)$monthNum;
    $dateObj = DateTime::createFromFormat('!m', $monthNum);
    $monthName = $dateObj->format('M'); // March
    return $monthName;
}

function getMonthsBetweenDates($date1 = "", $date2 = "")
{
    $begin = new DateTime($date1);
    $end = new DateTime($date2);

    $selectedMonthArr = [];
    while ($begin <= $end) {
        $selectedMonthArr['m'][] = $begin->format('m');
        $selectedMonthArr['y'][] = $begin->format('Y');
        $begin->modify('first day of next month');
    }
    return $selectedMonthArr;
}

function getMonthRangeBetweenDate($date1 = "", $date2 = "")
{
    $d1 = new DateTime($date2);
    $d2 = new DateTime($date1);
    $Months = $d2->diff($d1);
    $howeverManyMonths = (($Months->y) * 12) + ($Months->m);
    return $howeverManyMonths;
}

function getSuppliers()
{
    return WaSupplier::orderBy('name')->where('name', "!=", "")->pluck('name', 'id')->toArray();
}

function getSuppliersForReorderWithBin($bin_id)
{
    $user = getLoggeduserProfile();
    $assigned = \App\Model\WaUserSupplier::where('user_id', $user->id)->pluck('wa_supplier_id');
    $items = WaInventoryItem::with(['getUnitOfMeausureDetail'])->select(
        [
            'wa_inventory_items.*',
            'wa_location_and_stores.location_name',
            'wa_location_and_stores.id as location_id',
            'wa_inventory_location_stock_status.re_order_level',
            'wa_inventory_location_stock_status.max_stock as max_stock_f',
            'wa_suppliers.id as supplier_id',
            'wa_suppliers.name as supplier',
            'wa_unit_of_measures.title as bin_location',
            DB::RAW('(select COALESCE(SUM(wa_stock_moves.qauntity), 0) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id and wa_stock_moves.wa_location_and_store_id = wa_inventory_location_stock_status.wa_location_and_stores_id) as qty_inhand')
        ]
    )
        ->join('wa_inventory_location_uom', function ($e) use ($bin_id) {
            $e->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')->where('uom_id', $bin_id);
        })
        ->join('wa_inventory_location_stock_status', function ($e) {
            $e->on('wa_inventory_location_stock_status.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->whereRaw('wa_inventory_location_stock_status.wa_location_and_stores_id = wa_inventory_location_uom.location_id')
                ->where('wa_inventory_location_stock_status.re_order_level', '>', 0);
        })
        ->join('wa_inventory_item_suppliers', function ($e) {
            $e->on('wa_inventory_items.id', '=', 'wa_inventory_item_suppliers.wa_inventory_item_id');
        })
        ->leftJoin('wa_location_and_stores', function ($e) {
            $e->on('wa_inventory_location_uom.location_id', '=', 'wa_location_and_stores.id');
        })
        ->leftJoin('wa_suppliers', function ($e) {
            $e->on('wa_inventory_item_suppliers.wa_supplier_id', '=', 'wa_suppliers.id');
        })
        ->leftJoin('wa_unit_of_measures', function ($e) {
            $e->on('wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id');
        })
        ->orderBy('wa_inventory_items.id')->where(function ($e) use ($user, $assigned) {
            if ($user->role_id != 1) {
                $e->whereIn('wa_suppliers.id', $assigned);
            }
        })
        ->having('qty_inhand', '<=', DB::RAW('wa_inventory_location_stock_status.re_order_level'))
        ->get();

    $suppliers = [];
    foreach ($items as $item) {
        // $supplierExists = collect($suppliers)->where('id', $item->supplier_id)->first();
        if (!in_array($item->supplier_id, $suppliers)) {
            $suppliers[$item->supplier_id] = ['id' => $item->supplier_id, 'name' => $item->supplier];
        }
    }

    return $suppliers;
}

function getBranchesDropdown()
{
    $all_sections = Restaurant::orderBy('name')->get();
    $arr = [];
    foreach ($all_sections as $section) {
        $arr[$section->id] = $section->name;
    }
    return $arr;
}

function getCompanyDropdownFromPreferences()
{
    $all_sections = WaCompanyPreference::orderBy('name')->get();
    $arr = [];
    foreach ($all_sections as $section) {

        $arr[$section->id] = $section->name;
    }


    return $arr;
}


function getChartOfAccountsDropdown()
{
    $all_sections = WaChartsOfAccount::orderBy('account_name')->get();
    $arr = [];
    foreach ($all_sections as $section) {

        $arr[$section->id] = $section->account_name . '(' . $section->account_code . ')';
    }


    return $arr;
}

function getChartOfAccountsList()
{
    $all_sections = WaChartsOfAccount::orderBy('account_name')->get();
    $arr = [];
    foreach ($all_sections as $section) {

        $arr[$section->account_code] = $section->account_name;
    }


    return $arr;
}


function getAuthorizerEmployee()
{
    $users = User::whereNotNull('authorization_level')->get();
    $arr = [];
    foreach ($users as $section) {

        $arr[$section->id] = $section->name . ' (' . getAuthorizerLevels()[$section->authorization_level] . ')';
    }


    return $arr;
}

function getExternalAuthorizerEmployee()
{
    $users = User::whereNotNull('external_authorization_level')->get();
    $arr = [];
    foreach ($users as $section) {

        $arr[$section->id] = $section->name . ' (' . getAuthorizerLevels()[$section->external_authorization_level] . ')';
    }


    return $arr;
}

function getPurchaseAuthorizerEmployee()
{
    $users = User::whereNotNull('purchase_order_authorization_level')->get();
    $arr = [];
    foreach ($users as $section) {

        $arr[$section->id] = $section->name . ' (' . getAuthorizerLevels()[$section->purchase_order_authorization_level] . ')';
    }


    return $arr;
}


function getstockFamilyGroup()
{
    $users = WaStockFamilyGroup::whereNotNull('title')->get();
    $arr = [];
    foreach ($users as $section) {

        $arr[$section->id] = $section->title;
    }


    return $arr;
}

function getstockTypeCategory()
{
    $users = WaStockTypeCategory::whereNotNull('title')->get();
    $arr = [];
    foreach ($users as $section) {

        $arr[$section->id] = $section->title;
    }


    return $arr;
}


function getAuthorizerLevels()
{
    return ['1' => 'Level-1', '2' => 'Level-2', '3' => 'Level-3', '4' => 'Level-4', '5' => 'Level-5'];
}

function getCompanyPreferencesCurrency()
{
    return ['AUD' => 'Australian dollar', 'CHF' => 'Swiss franc', 'EUR' => 'European Union euro', 'GBP' => 'United Kingdom pound sterling', 'KES' => 'Kenyan shilling', 'USD' => 'United States dollar'];
}

function getAllCurrenciesList()
{
    $CurrencyName = array();
    // BEGIN: AlphabeticCode and CurrencyName data.
    $CurrencyName['AED'] = _('United Arab Emirates dirham');
    $CurrencyName['AFN'] = _('Afghan afghani');
    $CurrencyName['ALL'] = _('Albanian lek');
    $CurrencyName['AMD'] = _('Armenian dram');
    $CurrencyName['ANG'] = _('Netherlands Antillean guilder');
    $CurrencyName['AOA'] = _('Angolan kwanza');
    $CurrencyName['ARS'] = _('Argentine peso');
    $CurrencyName['AUD'] = _('Australian dollar');
    $CurrencyName['AWG'] = _('Aruban florin');
    $CurrencyName['AZN'] = _('Azerbaijani manat');
    $CurrencyName['BAM'] = _('Bosnia and Herzegovina convertible mark');
    $CurrencyName['BBD'] = _('Barbados dollar');
    $CurrencyName['BDT'] = _('Bangladeshi taka');
    $CurrencyName['BGN'] = _('Bulgarian lev');
    $CurrencyName['BHD'] = _('Bahraini dinar');
    $CurrencyName['BIF'] = _('Burundian franc');
    $CurrencyName['BMD'] = _('Bermudian dollar');
    $CurrencyName['BND'] = _('Brunei dollar');
    $CurrencyName['BOB'] = _('Bolivian Boliviano');
    $CurrencyName['BOV'] = _('Bolivian Mvdol (funds code)');
    $CurrencyName['BRL'] = _('Brazilian real');
    $CurrencyName['BSD'] = _('Bahamian dollar');
    $CurrencyName['BTN'] = _('Bhutanese ngultrum');
    $CurrencyName['BWP'] = _('Botswana pula');
    $CurrencyName['BYR'] = _('Belarusian ruble');
    $CurrencyName['BZD'] = _('Belize dollar');
    $CurrencyName['CAD'] = _('Canadian dollar');
    $CurrencyName['CDF'] = _('Congolese franc');
    $CurrencyName['CHE'] = _('Swiss WIR Euro (complementary currency)');
    $CurrencyName['CHF'] = _('Swiss franc');
    $CurrencyName['CHW'] = _('Swiss WIR Franc (complementary currency)');
    $CurrencyName['CLF'] = _('Chilean Unidad de Fomento (funds code)');
    $CurrencyName['CLP'] = _('Chilean peso');
    $CurrencyName['CNY'] = _('Chinese yuan');
    $CurrencyName['COP'] = _('Colombian peso');
    $CurrencyName['COU'] = _('Colombian Unidad de Valor Real');
    $CurrencyName['CRC'] = _('Costa Rican colon');
    $CurrencyName['CUC'] = _('Cuban peso convertible');
    $CurrencyName['CUP'] = _('Cuban peso');
    $CurrencyName['CVE'] = _('Cape Verde escudo');
    $CurrencyName['CZK'] = _('Czech koruna');
    $CurrencyName['DJF'] = _('Djiboutian franc');
    $CurrencyName['DKK'] = _('Danish krone');
    $CurrencyName['DOP'] = _('Dominican peso');
    $CurrencyName['DZD'] = _('Algerian dinar');
    $CurrencyName['EGP'] = _('Egyptian pound');
    $CurrencyName['ERN'] = _('Eritrean nakfa');
    $CurrencyName['ETB'] = _('Ethiopian birr');
    $CurrencyName['EUR'] = _('European Union euro');
    $CurrencyName['FJD'] = _('Fiji dollar');
    $CurrencyName['FKP'] = _('Falkland Islands pound');
    $CurrencyName['GBP'] = _('United Kingdom pound sterling');
    $CurrencyName['GEL'] = _('Georgian lari');
    $CurrencyName['GHS'] = _('Ghanaian cedi');
    $CurrencyName['GIP'] = _('Gibraltar pound');
    $CurrencyName['GMD'] = _('Gambian dalasi');
    $CurrencyName['GNF'] = _('Guinean franc');
    $CurrencyName['GTQ'] = _('Guatemalan quetzal');
    $CurrencyName['GYD'] = _('Guyanese dollar');
    $CurrencyName['HKD'] = _('Hong Kong dollar');
    $CurrencyName['HNL'] = _('Honduran lempira');
    $CurrencyName['HRK'] = _('Croatian kuna');
    $CurrencyName['HTG'] = _('Haitian gourde');
    $CurrencyName['HUF'] = _('Hungarian forint');
    $CurrencyName['IDR'] = _('Indonesian rupiah');
    $CurrencyName['ILS'] = _('Israeli new shekel');
    $CurrencyName['INR'] = _('Indian rupee');
    $CurrencyName['IQD'] = _('Iraqi dinar');
    $CurrencyName['IRR'] = _('Iranian rial');
    $CurrencyName['ISK'] = _('Icelandic króna');
    $CurrencyName['JMD'] = _('Jamaican dollar');
    $CurrencyName['JOD'] = _('Jordanian dinar');
    $CurrencyName['JPY'] = _('Japanese yen');
    $CurrencyName['KES'] = _('Kenyan shilling');
    $CurrencyName['KGS'] = _('Kyrgyzstani som');
    $CurrencyName['KHR'] = _('Cambodian riel');
    $CurrencyName['KMF'] = _('Comoro franc');
    $CurrencyName['KPW'] = _('North Korean won');
    $CurrencyName['KRW'] = _('South Korean won');
    $CurrencyName['KWD'] = _('Kuwaiti dinar');
    $CurrencyName['KYD'] = _('Cayman Islands dollar');
    $CurrencyName['KZT'] = _('Kazakhstani tenge');
    $CurrencyName['LAK'] = _('Lao kip');
    $CurrencyName['LBP'] = _('Lebanese pound');
    $CurrencyName['LKR'] = _('Sri Lankan rupee');
    $CurrencyName['LRD'] = _('Liberian dollar');
    $CurrencyName['LSL'] = _('Lesotho loti');
    $CurrencyName['LTL'] = _('Lithuanian litas');
    $CurrencyName['LVL'] = _('Latvian lats');
    $CurrencyName['LYD'] = _('Libyan dinar');
    $CurrencyName['MAD'] = _('Moroccan dirham');
    $CurrencyName['MDL'] = _('Moldovan leu');
    $CurrencyName['MGA'] = _('Malagasy ariary');
    $CurrencyName['MKD'] = _('Macedonian denar');
    $CurrencyName['MMK'] = _('Myanmar kyat');
    $CurrencyName['MNT'] = _('Mongolian tugrik');
    $CurrencyName['MOP'] = _('Macanese pataca');
    $CurrencyName['MRO'] = _('Mauritanian ouguiya');
    $CurrencyName['MUR'] = _('Mauritian rupee');
    $CurrencyName['MVR'] = _('Maldivian rufiyaa');
    $CurrencyName['MWK'] = _('Malawian kwacha');
    $CurrencyName['MXN'] = _('Mexican peso');
    $CurrencyName['MXV'] = _('Mexican Unidad de Inversion (funds code)');
    $CurrencyName['MYR'] = _('Malaysian ringgit');
    $CurrencyName['MZN'] = _('Mozambican metical');
    $CurrencyName['NAD'] = _('Namibian dollar');
    $CurrencyName['NGN'] = _('Nigerian naira');
    $CurrencyName['NIO'] = _('Nicaraguan córdoba');
    $CurrencyName['NOK'] = _('Norwegian krone');
    $CurrencyName['NPR'] = _('Nepalese rupee');
    $CurrencyName['NZD'] = _('New Zealand dollar');
    $CurrencyName['OMR'] = _('Omani rial');
    $CurrencyName['PAB'] = _('Panamanian balboa');
    $CurrencyName['PEN'] = _('Peruvian nuevo sol');
    $CurrencyName['PGK'] = _('Papua New Guinean kina');
    $CurrencyName['PHP'] = _('Philippine peso');
    $CurrencyName['PKR'] = _('Pakistani rupee');
    $CurrencyName['PLN'] = _('Polish złoty');
    $CurrencyName['PYG'] = _('Paraguayan guaraní');
    $CurrencyName['QAR'] = _('Qatari riyal');
    $CurrencyName['RON'] = _('Romanian new leu');
    $CurrencyName['RSD'] = _('Serbian dinar');
    $CurrencyName['RUB'] = _('Russian rouble');
    $CurrencyName['RWF'] = _('Rwandan franc');
    $CurrencyName['SAR'] = _('Saudi riyal');
    $CurrencyName['SBD'] = _('Solomon Islands dollar');
    $CurrencyName['SCR'] = _('Seychelles rupee');
    $CurrencyName['SDG'] = _('Sudanese pound');
    $CurrencyName['SEK'] = _('Swedish krona');
    $CurrencyName['SGD'] = _('Singapore dollar');
    $CurrencyName['SHP'] = _('Saint Helena pound');
    $CurrencyName['SLL'] = _('Sierra Leonean leone');
    $CurrencyName['SOS'] = _('Somali shilling');
    $CurrencyName['SRD'] = _('Surinamese dollar');
    $CurrencyName['SSP'] = _('South Sudanese pound');
    $CurrencyName['STD'] = _('São Tomé and Príncipe dobra');
    $CurrencyName['SYP'] = _('Syrian pound');
    $CurrencyName['SZL'] = _('Swazi lilangeni');
    $CurrencyName['THB'] = _('Thai baht');
    $CurrencyName['TJS'] = _('Tajikistani somoni');
    $CurrencyName['TMT'] = _('Turkmenistani manat');
    $CurrencyName['TND'] = _('Tunisian dinar');
    $CurrencyName['TOP'] = _('Tongan paʻanga');
    $CurrencyName['TRY'] = _('Turkish lira');
    $CurrencyName['TTD'] = _('Trinidad and Tobago dollar');
    $CurrencyName['TWD'] = _('Taiwan new dollar');
    $CurrencyName['TZS'] = _('Tanzanian shilling');
    $CurrencyName['UAH'] = _('Ukrainian hryvnia');
    $CurrencyName['UGX'] = _('Ugandan shilling');
    $CurrencyName['USD'] = _('United States dollar');
    $CurrencyName['USN'] = _('United States dollar next day (funds code)');
    $CurrencyName['USS'] = _('United States dollar same day (funds code)');
    $CurrencyName['UYI'] = _('Uruguayan unidad indexada (funds code)');
    $CurrencyName['UYU'] = _('Uruguayan peso');
    $CurrencyName['UZS'] = _('Uzbekistan som');
    $CurrencyName['VEF'] = _('Venezuelan bolívar fuerte');
    $CurrencyName['VND'] = _('Vietnamese dong');
    $CurrencyName['VUV'] = _('Vanuatu vatu');
    $CurrencyName['WST'] = _('Samoan tala');
    $CurrencyName['XAF'] = _('CFA franc BEAC');
    $CurrencyName['XAG'] = _('Silver (one troy ounce)');
    $CurrencyName['XAU'] = _('Gold (one troy ounce)');
    $CurrencyName['XBA'] = _('European Composite Unit');
    $CurrencyName['XBB'] = _('European Monetary Unit');
    $CurrencyName['XBC'] = _('European Unit of Account 9');
    $CurrencyName['XBD'] = _('European Unit of Account 17');
    $CurrencyName['XCD'] = _('East Caribbean dollar');
    $CurrencyName['XDR'] = _('Special drawing rights');
    $CurrencyName['XFU'] = _('UIC franc (special settlement currency)');
    $CurrencyName['XOF'] = _('CFA franc BCEAO');
    $CurrencyName['XPD'] = _('Palladium (one troy ounce)');
    $CurrencyName['XPF'] = _('CFP franc');
    $CurrencyName['XPT'] = _('Platinum (one troy ounce)');
    $CurrencyName['XTS'] = _('Code reserved for testing purposes');
    $CurrencyName['XXX'] = _('No currency');
    $CurrencyName['YER'] = _('Yemeni rial');
    $CurrencyName['ZAR'] = _('South African rand');
    $CurrencyName['ZMW'] = _('Zambian kwacha');
    asort($CurrencyName);
    return $CurrencyName;
}


function getCurrencyDropdown()
{
    $list = getAllCurrenciesList();
    $arr = [];
    foreach ($list as $iso => $data) {
        $arr[$iso] = $iso . ' - ' . $data;
    }
    return $arr;
}


function canDeleteParentData()
{
    return false;
}

function getCountryList()
{
    return Country::pluck('name', 'name')->toArray();
}

function getInventoryCategoryList()
{
    return WaInventoryCategory::pluck('category_description', 'id')->toArray();
}

function getInventorySubCategoryList()
{
    return ItemSubCategories::pluck('title', 'id')->toArray();
}

function getInventoryItemsSuppliers()
{
    return WaSupplier::pluck('name', 'id')->toArray();
}

function getVehicleTypes()
{
    return VehicleType::pluck('name', 'id')->toArray();
}

function getRouteListWithId()
{

    return Route::pluck('route_name', 'id')->toArray();
}

function getRouteCustomersCount($routeId)
{
    return WaRouteCustomer::where('route_id', $routeId)->count();
}
function getShiftVisitedCustomers($shiftId)
{
    return SalesmanShiftCustomer::where('salesman_shift_id', $shiftId)->where('visited', 1)->count();;
}

function getRouteCustomerDetails($id)
{
    return WaRouteCustomer::find($id);
}

function getRouteCustomerOrderDetails($shiftId, $customerId)
{
    return WaInternalRequisition::where('wa_shift_id', $shiftId)->where('wa_route_customer_id', $customerId)->latest()->first();
}

function getBranchListWithId()
{
    return Restaurant::pluck('name', 'id')->toArray();
}

function getRoles()
{
    return Role::pluck('title', 'id')->toArray();
}

function getUnitOfMeasureList()
{
    return WaUnitOfMeasure::pluck('title', 'id')->toArray();
}

function paymentTermsList()
{
    return WaPaymentTerm::pluck('term_description', 'id')->toArray();
}

function getAssociatedCurrenyList()
{
    return WaCurrencyManager::pluck('ISO4217', 'id')->toArray();
}

function getDepartmentDropdown($restaurant_id)
{
    return WaDepartment::where('restaurant_id', $restaurant_id)->pluck('department_name', 'id')->toArray();
}

function getDepartmentsDropdown()
{
    $rows = WaDepartment::select('department_name', 'id')->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->department_name;
    }
    return $data;
}

function getRestaurantNameById($restaurant_id = null)
{
    if ($restaurant_id != null) {
        $data = Restaurant::where('id', $restaurant_id)->first();
        if ($data) {
            return $data->name;
        } else {
            return '--';
        }
    } else {
        return '--';
    }
}

function getRestaurants()
{
    return Restaurant::pluck('name', 'id')->toArray();
}


function getCodeWithNumberSeries($module_name)
{
    $series_module = WaNumerSeriesCode::where('module', $module_name)->first();
    //echo $series_module->starting_number; die;
    $newcode = checkUniqueCodeWithSeries($series_module->code, $series_module->last_number_used + 1, $module_name);

    return $newcode;
}

function getCodeWithNumberSeriesPOS($module_name)
{
    $series_module = WaNumerSeriesCode::where('module', $module_name)->first();
    if (!$series_module) {
        WaNumerSeriesCode::create([
            'code'
        ]);
    }
    //echo $series_module->starting_number; die;
    $newcode = checkUniqueCodeWithSeries($series_module->code, $series_module->last_number_used + 1, $module_name);

    return $newcode;
}

function getCodeWithNumberSeriesRCT($module_name)
{
    $series_module = WaNumerSeriesCode::where('module', $module_name)->first();
    //echo $series_module->starting_number; die;
    $newcode = checkUniqueCodeWithSeries($series_module->code, $series_module->last_number_used + 1, $module_name);

    return $newcode;
}


function getCodeWithNumberSeriesBillClose($module_name)
{
    $series_module = WaNumerSeriesCode::where('module', $module_name)->first();
    //checkUniqueCodeWithSeries($series_module->code,$series_module->starting_number,$module_name);
    WaNumerSeriesCode::where('module', $module_name)->update(["last_number_used" => $series_module->last_number_used + 1]);
    $newcode = $series_module->code . '-' . manageOrderidWithPad($series_module->last_number_used);

    return $newcode;
}


function inventoryUItemDropDown()
{
    $rows = WaInventoryItem::orderBy('title', 'asc')->pluck('title', 'id')->toArray();
    return $rows;
}

function checkUniqueCodeWithSeries($series_code, $series_starting_number, $module_name)
{
    //$series_module->code,$series_module->last_number_used+1,$module_name
    $newcode = $series_code . '-' . manageOrderidWithPad($series_starting_number);
    // echo $newcode; die;


    if ($module_name == 'INVENTORY ITEM') {
        $row = WaInventoryItem::where('stock_id_code', $newcode)->first();
    }
    if ($module_name == 'SUPPLIER') {
        $row = WaSupplier::where('supplier_code', $newcode)->first();
    }
    if ($module_name == 'EXTERNAL REQUISITIONS') {
        $row = WaExternalRequisition::where('purchase_no', $newcode)->first();
    }

    if ($module_name == 'INTERNAL REQUISITIONS') {
        $row = WaInternalRequisition::where('requisition_no', $newcode)->first();
    }

    if ($module_name == 'PURCHASE ORDERS') {
        $row = WaPurchaseOrder::where('purchase_no', $newcode)->first();
    }
    if ($module_name == 'GRN') {
        $row = WaGrn::where('grn_number', $newcode)->first();
    }

    if ($module_name == 'TRAN') {
        $row = WaInventoryLocationTransfer::where('transfer_no', $newcode)->first();
    }
    if ($module_name == 'ITEM ADJUSTMENT') {
        $row = StockAdjustment::where('item_adjustment_code', $newcode)->first();
    }
    if ($module_name == 'RECIPE') {
        $row = WaRecipe::where('recipe_number', $newcode)->first();
    }

    if ($module_name == 'POS SALES') {
        $row = PaymentCredit::where('transaction_no', $newcode)->first();
    }

    if ($module_name == 'CREDITORS_PAYMENT') {
        $row = WaSuppTran::where('document_no', $newcode)->first();
    }

    if ($module_name == 'CUSTOMERS') {
        $row = WaCustomer::where('customer_code', $newcode)->first();
    }


    if ($module_name == 'SALES_ORDER') {
        $row = WaSalesOrderQuotation::where('sales_order_number', $newcode)->first();
    }

    if ($module_name == 'SALES_INVOICE') {
        $row = WaSalesInvoice::where('sales_invoice_number', $newcode)->first();
    }

    if ($module_name == 'RECEIPT') {

        $row = WaDebtorTran::where('document_no', $newcode)->first();
    }

    if ($module_name == 'JOURNAL_ENTRY') {

        $row = WaJournalEntry::where('journal_entry_no', $newcode)->first();
    }
    if ($module_name == 'SE') {
        $row = WaCashSales::where('cash_sales_number', $newcode)->first();
    }
    if ($module_name == 'POS') {
        $row = WaGlTran::where('transaction_no', $newcode)->first();
    }
    if ($module_name == 'CASH_SALES') {
        $row = \App\Model\WaPosCashSales::where('sales_no', $newcode)->first();
    }
    if ($module_name == 'Receive_Stock_storeC') {
        $row = \App\Model\WaStoreCReceive::where('receive_code', $newcode)->first();
    }
    //echo "<pre>"; print_r($row); die;


    if ($module_name == 'DISPATCH-CASH-SALES') {
        $row = \App\Model\WaPosCashSalesDispatch::where('desp_no', $newcode)->first();
    }

    if ($module_name == 'RETURN') {
        $row = \App\Model\WaPosCashSalesItems::where('return_grn', $newcode)->first();
    }

    if ($module_name == 'CREDIT_NOTE') {
        $row = \App\Model\WaCreditNote::where('credit_note_number', $newcode)->first();
    }

    if ($module_name == 'BANK_FILES') {
        $row = \App\Models\WaBankFile::where('file_no', $newcode)->first();
    }

    if ($module_name == 'PETTY_CASH') {
        $row = \App\Model\WaPettyCash::where('petty_cash_no', $newcode)->first();
    }

    if (isset($row)) {
        $newcode = checkUniqueCodeWithSeries($series_code, $series_starting_number + 1, $module_name);
    }
    return $newcode;
}

function tax_amount_type()
{
    $data['Exclusive'] = 'Exclusive of Tax';
    $data['Inclusive'] = 'Inclusive of Tax';
    $data['Out Of Scope'] = 'Out Of Scope of Tax';
    return $data;
}


function companyPrefFromRes($res)
{
    $data = '';
    $a = \App\Model\Restaurant::where('id', $res)->with('getAssociateCompany')->first();
    $data = $a ? ($a->getAssociateCompany ?
        ($a->getAssociateCompany->creditorControlGlAccount ? $a->getAssociateCompany->creditorControlGlAccount->account_code : NULL) : NULL
    ) : NULL;
    return $data;
}

function receivedType()
{
    $data['Customer'] = 'Customer';
    // $data['Supplier'] = 'Supplier';
    return $data;
}

function getSupplierDropdown()
{
    $user = getLoggeduserProfile();
    $assigned = \App\Model\WaUserSupplier::where('user_id', $user->id)->pluck('wa_supplier_id');
    $rows = WaSupplier::select('supplier_code', 'name', 'id')->where(function ($e) use ($user, $assigned) {
        if ($user->role_id != 1) {
            $e->whereIn('id', $assigned);
        }
    })->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->name . ' (' . $row->supplier_code . ')';
    }
    return $data;
}

function getItemCategory($id)
{
    $category = WaInventoryCategory::find($id);
    return $category;
}

function getItemPackSize($id)
{
    $packSize = PackSize::find($id);
    return $packSize;
}

function __getSupplierDropdown()
{
    $rows = WaSupplier::select('supplier_code', 'name', 'id')->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->name . ' (' . $row->supplier_code . ')';
    }
    return $data;
}

function getStoreLocationDropdown()
{
    $rows = WaLocationAndStore::select('location_code', 'location_name', 'id')->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->location_name . ' (' . $row->location_code . ')';
    }
    return $data;
}

function getAllStores()
{
    return getStoreLocationDropdown();
}

function getStoreLocationDropdownByBranch($branch_id)
{
    $rows = WaLocationAndStore::select('location_code', 'location_name', 'id')->where('wa_branch_id', $branch_id)->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->location_name . ' (' . $row->location_code . ')';
    }
    return $data;
}

function getStoreLocationDropdownByBranch_with_user($branch_id)
{
    $getLoggeduserProfile = getLoggeduserProfile();
    $rows = WaLocationAndStore::select('location_code', 'location_name', 'id')->whereHas('user')->where(function ($w) use ($getLoggeduserProfile, $branch_id) {
        $w->where('wa_branch_id', $branch_id);
        if ($getLoggeduserProfile->role_id == 4) {
            $w->where('id', $getLoggeduserProfile->wa_location_and_store_id);
        }
    })->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->id] = $row->location_name . ' (' . $row->location_code . ')';
    }
    return $data;
}

function getVehicleRegList()
{
    $rows = Vehicle::select('vehicle_reg')->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->vehicle_reg] = $row->vehicle_reg;
    }
    return $data;
}

function getRouteList()
{
    $rows = Route::select('route_name')->get();
    $data = [];
    foreach ($rows as $row) {
        $data[$row->route_name] = $row->route_name;
    }
    return $data;
}


function updateUniqueNumberSeries($module_name, $uniqueCode)
{
    $series_module = WaNumerSeriesCode::where('module', $module_name)->first();
    $last_number_used = str_replace($series_module->code . '-', '', $uniqueCode);
    $series_module->last_number_used = intval($last_number_used);
    $series_module->last_date_used = date('Y-m-d');
    $series_module->save();
}

function addExternalRequisition_nonstock_Permissions($wa_external_requisition_id, $wa_department_id)
{
    $rowmain = WaExternalRequisition::where('id', $wa_external_requisition_id)->first();

    $authorizers_users = WaDepartmentExternalAuthorization::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();


    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::whereIn('id', $authorizers_users)
        ->orderBy('external_authorization_level', 'asc')
        ->get();

    $html = '';
    foreach ($rowmain->getRelatedItem as $key => $items) {

        $html .= $key + 1 . '  ' . @$items->getInventoryItemDetail->title . '  ' . 'QTY ' . $items->supplier_quantity . "\n";
    }

    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new WaExternalReqPermission();
            $row->user_id = $users->id;
            $row->wa_external_requisition_id = $wa_external_requisition_id;
            $row->approve_level = $users->external_authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();


            if ($i == 0) {
                $emp = $users;
                sendMailForExternalRequisition($emp->email, $row->wa_external_requisition_id, $row->approve_level);
                $phone = $emp->phone_number;
                // if(strlen($phone) > 7){
                $u = @$rowmain->getrelatedEmployee;
                $html = '';

                foreach ($rowmain->getRelatedItem as $key => $items) {

                    $html .= $key + 1 . '  ' . @$items->item_no . '  ' . 'QTY ' . $items->quantity . "\n";
                }
                $message = 'You have an External Requistion non-stock No ' . $rowmain->purchase_no . ' from ' . @$u->name . ' of Branch: ' . @$u->userRestaurent->name . ' and Department: ' . @$u->userDepartment->department_name . ' that requires your approval. Status - ' . $rowmain->project_level . "\n" . $html;

                // send_sms($phone, $message);
                sendMessage($message, $phone);
                // }
            }
            $i++;
        }
    } else {

        $u = @$rowmain->getrelatedEmployee;
        $phone = $u->phone_number;
        $message = 'Your External Requistion non-stock No ' . $rowmain->purchase_no . ' is Approved and items is ' . "\n" . $html;


        sendMessage($message, $phone);
        $rowmain->status = 'APPROVED';
        $rowmain->save();
    }
}

function addPurchaseOrderPermissions($wa_purchase_order_id, $wa_department_id)
{
    $rowmain = WaPurchaseOrder::with(['getSupplier', 'getStoreLocation', 'getRelatedItem.getInventoryItemDetail', 'getrelatedEmployee'])->where('id', $wa_purchase_order_id)->first();

    $authorizers_users = WaPurchaseOrderAuthorization::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();


    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::whereIn('id', $authorizers_users)
        ->orderBy('purchase_order_authorization_level', 'asc')
        ->get();
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new WaPurchaseOrderPermission();
            $row->user_id = $users->id;
            $row->wa_purchase_order_id = $wa_purchase_order_id;
            $row->approve_level = $users->purchase_order_authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();


            if ($i == 0) {
                $emp = $users;
                sendMailForPurchaseOrder($emp, $row->wa_purchase_order_id, $row->approve_level);
                $phone_number = User::select('phone_number')->where('id', $users->id)->first();
                $phone = $phone_number->phone_number;

                $u = @$rowmain->getrelatedEmployee;

                $html = '';
                foreach ($rowmain->getRelatedItem as $key => $items) {

                    $html .= $key + 1 . '  ' . @$items->getInventoryItemDetail->title . '  ' . 'QTY ' . $items->supplier_quantity . "\n";
                }


                $message = 'You have an Purchase Order No ' . $rowmain->purchase_no . ' from ' . @$u->name . ' of Branch: ' . @$u->userRestaurent->name . ' and Department: ' . @$u->userDepartment->department_name . ' that requires your approval.  The items are: ' . "\n" . $html;

                // send_sms($phone, $message);
                sendMessage($message, $phone);

                $rowmain->status = 'PENDING';
                $rowmain->save();
            }
            $i++;
        }
    } else {
        $u = @$rowmain->getrelatedEmployee;
        $phone = $u->phone_number;
        $message = 'Your Purchase order No ' . $rowmain->purchase_no . ' is Approved';
        // send_sms($phone, $message);
        sendMessage($message, $phone);

        $rowmain->status = 'APPROVED';
        $rowmain->save();
        send_supplier_lpo($rowmain);
    }
}

function send_supplier_lpo($row)
{
    $apiLog = new \App\Models\ApiCallLog();
    $apiLog->module = 'Send LPO to Supplier';
    $apiLog->lpo_number = $row->purchase_no;
    $apiLog->module_id = $row->id;

    try {
        // $trade = \App\Models\TradeAgreement::where('wa_supplier_id',$row->wa_supplier_id)->where('is_locked',1)->first();

        $items = [];
        foreach ($row->getRelatedItem as $key => $item) {
            $items['code'][$item->id] = $item->getInventoryItemDetail->stock_id_code;
            $items['title'][$item->id] = $item->getInventoryItemDetail->title;
            $items['measure'][$item->id] = ($item->getInventoryItemDetail->net_weight ?? 1);
            $items['quantity'][$item->id] = $item->supplier_quantity;
            $items['free_qualified_stock'][$item->id] = $item->free_qualified_stock;
            $items['unit_price'][$item->id] = $item->order_price;
            $items['vat_percentage'][$item->id] = $item->vat_rate;
            $items['discount_amount'][$item->id] = $item->discount_amount;
            $items['discount_percentage'][$item->id] = $item->discount_percentage;
            $items['vat_amount'][$item->id] = $item->vat_amount;
            $items['net_amount'][$item->id] = $item->total_cost;
            $items['total_amount'][$item->id] = $item->total_cost_with_vat;
            $items['is_exclusive_vat'][$item->id] = $item->is_exclusive_vat;
            $items['discount_settings'][$item->id] = $item->discount_settings;
        }
        $items['lpo_number'] = $row->purchase_no;
        $items['supplier_own'] = $row->supplier_own;
        $items['invoice_discount_per'] = $row->invoice_discount_per;
        $items['invoice_discount'] = $row->invoice_discount;
        $items['lpo_type'] = $row->lpo_type;
        $items['order_date'] = $row->purchase_date;
        $items['supplier_code'] = $row->getSupplier->supplier_code;
        $items['supplier_email'] = $row->getSupplier->email;
        $items['supplier_name'] = $row->getSupplier->name;
        $items['supplier_address'] = $row->getSupplier->address;
        $items['license_plate_number'] = @$row->vehicle->license_plate_number;
        $items['driver_name'] = @$row->employee->name;
        $items['driver_phone'] = @$row->employee->phone_number;
        $items['ship_to'] = getAllSettings()['COMPANY_NAME'];
        $items['order_from'] = env('SUPPLIER_SOURCE');

        $items['transport_rebate_discount'] = $row->transport_rebate_discount;
        $items['transport_rebate_discount_value'] = $row->transport_rebate_discount_value;
        $items['transport_rebate_discount_type'] = $row->transport_rebate_discount_type;
        $items['ship_to_location'] = @$row->getStoreLocation->location_name;
        $apiLog->request_data = json_encode($items);
        $apiLog->save();
        if (!$row->getSupplier->locked_trade) {
            throw new \Exception("No Trade Agreement Found");
        }
        $api = new \App\Services\ApiService(env('SUPPLIER_PORTAL_URI'));
        $a = $api->postRequest(env('SUPPLIER_PORTAL_LPO_API', '/api/add_lpo'), $items);
        Log::info(json_encode([env('SUPPLIER_PORTAL_URI'), $a, $items]));

        $apiLog->response_data = json_encode($a);
        $apiLog->status = 'success';
        $apiLog->save();

        $cc = [];
        if ($a && isset($a['data']) && isset($a['data']['subscribers'])) {
            $cc = array_map('trim', explode(',', $a['data']['subscribers']));
        }
        $numbers = [];
        if ($a && isset($a['data']) && isset($a['data']['phone_subscribers'])) {
            $numbers = array_map('trim', explode(',', $a['data']['phone_subscribers']));
        }

        lpo_sent_to_phone($row, $numbers, $row->getStoreLocation->location_name . ' (' . $row->getStoreLocation->location_code . ')');

        lpo_sent_to_supplier_email($row, $cc, $row->getStoreLocation->location_name . ' (' . $row->getStoreLocation->location_code . ')');

        // $mail = new \App\Mail\LpoSentToSupplier($row->getSupplier, $row);
        // Mail::to($row->getSupplier->email)->cc($cc)->send($mail);
        return $a;
    } catch (\Throwable $th) {
        Log::error($th->getMessage());
        $apiLog->status = 'failed';
        $apiLog->error_message = $th->getMessage();
        $apiLog->save();
        return $th->getMessage();
    }
}

function lpo_sent_to_phone($row, $phones, $to_location){
    $message = 'Greetings! Please supply the following Purchase order ' . $row->purchase_no . '. Please note the delivery location is '.$to_location;
   
    foreach($phones as $phone){
        sendMessage($message, $phone);
    }
}

function lpo_sent_to_supplier_email($row, $cc, $to_location)
{
    $supplier = $row->getSupplier;
    throw_if(!$supplier->email, "This supplier does not have a valid email address.");

    $lpo = $row;

    $qr_code = QrCode::generate(
        $lpo->purchase_date . " - " . $lpo->purchase_no . " - " . $lpo->getBranch->name . " - " . manageAmountFormat($lpo->getRelatedItem->sum('total_cost_with_vat')),
    );
    $settings = getAllSettings();
    $pdf_d = true;
    $row = $lpo;
    $pdf = PDF::loadView('admin.purchaseorders.print', compact('row', 'qr_code', 'pdf_d', 'settings'))->set_option("enable_php", true);

    $email_template = EmailTemplate::templateList()['place_lpo'];
    $template = EmailTemplate::where('name', $email_template->name)->first();
    $makesubject = @$template->subject ?? $email_template->subject;
    $subject = str_replace(['${purchase_no}', '${branch}', '${$branch}'], [$lpo?->purchase_no, $lpo?->getBranch?->name, $lpo?->getBranch?->name], $makesubject);

    $email_message = @$template->body ?? $email_template->template;
    $email_message = str_replace(['${location}', '${user}'], [$to_location, @$lpo->getrelatedEmployee->name], $email_message);

    $mail = new LpoApproved($lpo, $supplier,  $email_message, null, $subject);

    Mail::to($supplier->email)
        ->cc($cc)
        ->send($mail);

    $lpo->update([
        'sent_to_supplier' => true
    ]);
}

function addInternalRequisitionPermissions($wa_internal_requisition_id, $wa_department_id)
{

    $authorizers_users = WaDepartmentsAuthorizationRelations::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();
    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::select('id', 'authorization_level', 'email')->whereIn('id', $authorizers_users)
        ->orderBy('authorization_level', 'asc')
        ->get();
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new WaInternalReqPermission();
            $row->user_id = $users->id;
            $row->wa_internal_requisition_id = $wa_internal_requisition_id;
            $row->approve_level = $users->authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();


            if ($i == 0) {
                $emp = User::select('email')->where('id', $users->id)->first();
                sendMailForInternalRequisition($emp->email, $row->wa_internal_requisition_id, $row->approve_level);
            }
            $i++;
        }
    } else {
        $row = WaInternalRequisition::where('id', $wa_internal_requisition_id)->first();
        $row->status = 'APPROVED';
        $row->save();
    }
}

function addstore_c_RequisitionPermissions($wa_internal_requisition_id, $wa_department_id)
{

    $authorizers_users = WaDepartmentsAuthorizationRelations::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();
    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::select('id', 'authorization_level', 'email')->whereIn('id', $authorizers_users)
        ->orderBy('authorization_level', 'asc')
        ->get();
    $data = [];
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $datas = [
                'user_id' => $users->id,
                'wa_store_c_requisition_id' => $wa_internal_requisition_id,
                'approve_level' => $users->authorization_level,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($i == 0) {
                $datas['status'] = 'NEW';
            }
            $data[] = $datas;

            if ($i == 0 && $users->email) {
                sendMailForInternalRequisition($users->email, $row->wa_internal_requisition_id, $row->approve_level);
            }
            $i++;
        }
        if (count($data) > 0) {
            \App\Model\WaStoreCReqPermission::insert($data);
        }
    } else {
        $row = \App\Model\WaStoreCRequisition::where('id', $wa_internal_requisition_id)->first();
        $row->status = 'APPROVED';
        $row->save();
    }
}


function addsupreme_store_RequisitionPermissions($wa_internal_requisition_id, $wa_department_id)
{

    $authorizers_users = WaDepartmentsAuthorizationRelations::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();
    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::select('id', 'authorization_level', 'email')->whereIn('id', $authorizers_users)
        ->orderBy('authorization_level', 'asc')
        ->get();
    $data = [];
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $datas = [
                'user_id' => $users->id,
                'wa_store_c_requisition_id' => $wa_internal_requisition_id,
                'approve_level' => $users->authorization_level,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($i == 0) {
                $datas['status'] = 'NEW';
            }
            $data[] = $datas;

            if ($i == 0 && $users->email) {
                sendMailForInternalRequisition($users->email, $row->wa_internal_requisition_id, $row->approve_level);
            }
            $i++;
        }
        if (count($data) > 0) {
            \App\Model\WaStoreCReqPermission::insert($data);
        }
    } else {
        $row = \App\Model\WaSupremeStoreRequisition::where('id', $wa_internal_requisition_id)->first();
        $row->status = 'APPROVED';
        $row->save();
    }
}

function addExternalRequisitionPermissions($wa_external_requisition_id, $wa_department_id)
{
    $rowmain = WaExternalRequisition::where('id', $wa_external_requisition_id)->first();

    $authorizers_users = WaDepartmentExternalAuthorization::select('user_id')->where('wa_department_id', $wa_department_id)->pluck('user_id')->toArray();

    $authorizers_users = count($authorizers_users) > 0 ? $authorizers_users : [0];
    $user_with_authorization = User::whereIn('id', $authorizers_users)
        ->orderBy('external_authorization_level', 'asc')
        ->get();
    $html = '';

    foreach ($rowmain->getRelatedItem as $key => $items) {

        $html .= $key + 1 . '  ' . @$items->getInventoryItemDetail->title . '  ' . 'QTY ' . $items->supplier_quantity . "\n";
    }
    if (count($user_with_authorization) > 0) {
        $i = 0;
        foreach ($user_with_authorization as $users) {
            $row = new WaExternalReqPermission();
            $row->user_id = $users->id;
            $row->wa_external_requisition_id = $wa_external_requisition_id;
            $row->approve_level = $users->external_authorization_level;
            if ($i == 0) {
                $row->status = 'NEW';
            }
            $row->save();


            if ($i == 0) {
                $emp = $users;
                sendMailForExternalRequisition($emp->email, $row->wa_external_requisition_id, $row->approve_level);
                $phone = $emp->phone_number;
                $u = @$rowmain->getrelatedEmployee;
                $html = '';
                foreach ($rowmain->getRelatedItem as $key => $items) {

                    $html .= $key + 1 . '  ' . @$items->getInventoryItemDetail->title . '  ' . 'QTY ' . $items->quantity . "\n";
                }
                $message = 'You have an External Requistion No ' . $rowmain->purchase_no . ' from ' . @$u->name . ' of Branch: ' . @$u->userRestaurent->name . ' and Department: ' . @$u->userDepartment->department_name . ' that requires your approval. Status - ' . $rowmain->project_level . "\n" . $html;

                // send_sms($phone, $message);
                sendMessage($message, $phone);
            }
            $i++;
        }
    } else {

        $u = @$rowmain->getrelatedEmployee;
        $phone = $u->phone_number;
        $message = 'Your External Requistion No ' . $rowmain->purchase_no . ' is Approved and items is ' . "\n" . $html;

        // send_sms($phone, $message);
        sendMessage($message, $phone);
        $rowmain->status = 'APPROVED';
        $rowmain->save();
    }
}

function isHaveAnyPendingExternalRequisition($user_id)
{
    $rows = WaExternalReqPermission::whereIn('status', ['NEW', 'HOLD'])->where('user_id', $user_id)->get();

    if (count($rows)) {
        return true;
    } else {
        return false;
    }
}

function isHaveAnyPendingPurchaseOrderPermission($user_id)
{
    $rows = WaPurchaseOrderPermission::whereIn('status', ['NEW', 'HOLD'])->where('user_id', $user_id)->get();
    if (count($rows)) {
        return true;
    } else {
        return false;
    }
}


function empExtranalAuthorityIsChanged($user_id)
{
    WaDepartmentExternalAuthorization::where('user_id', $user_id)->delete();
}


function sendMailForExternalRequisition($email, $wa_external_requisition_id, $approve_level)
{
    try {
        $receiver = User::where('email', $email)->first();
        $data = ['name' => $receiver->name, 'email' => $email];
        Mail::send('emails.external_requisition_approval', ['data' => $data], function ($message) use ($data) {
            $message->from(env('PROCUREMENT_EMAIL'), env('PROCUREMENT_NAME'));
            $message->to($data['email'])->subject('New External Requisition Approval Request');
        });
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function sendMailForInternalRequisition($email, $wa_internal_requisition_id, $approve_level)
{
    try {
        $receiver = User::where('email', $email)->first();
        $data = ['name' => $receiver->name, 'email' => $email];
        Mail::send('emails.external_requisition_approval', ['data' => $data], function ($message) use ($data) {
            $message->from(env('PROCUREMENT_EMAIL'), env('PROCUREMENT_NAME'));
            $message->to($data['email'])->subject('New Internal Requisition Approval Request');
        });
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function sendMailForPurchaseOrder($email, $wa_purchase_order_id, $approve_level)
{
    try {
        $receiver = User::where('email', $email)->first();
        $data = ['name' => $receiver->name, 'email' => $email];
        Mail::send('emails.external_requisition_approval', ['data' => $data], function ($message) use ($data) {
            $message->from(env('PROCUREMENT_EMAIL'), env('PROCUREMENT_NAME'));
            $message->to($data['email'])->subject('New Purchase Order Approval Request');
        });
        return true;
    } catch (Exception $ex) {
        return false;
    }
}

function getAllSettings()
{
    // Cache settings for 1 hour to improve performance
    return \Cache::remember('all_settings', 3600, function () {
        return Setting::pluck('description', 'name')->toArray();
    });
}

function getAllReusitionUsers()
{
    $userArr = [];
    $userHaveApprovedReq = WaExternalRequisition::select('user_id')->where('status', 'APPROVED')->pluck('user_id')->toArray();
    if (count($userHaveApprovedReq) > 0) {
        $userArr = User::whereIn('id', $userHaveApprovedReq)->pluck('name', 'id')->toArray();
    }
    return $userArr;
}

function getQtyOnOrder($item_id)
{
    $all_approved_status_id = WaPurchaseOrder::select('id')->where('status', 'APPROVED')->pluck('id')->toArray();
    $all_approved_status_id = count($all_approved_status_id) > 0 ? $all_approved_status_id : [0];

    return WaPurchaseOrderItem::whereIn('wa_purchase_order_id', $all_approved_status_id)->where('wa_inventory_item_id', $item_id)->sum('quantity');
}

function getItemAvailableQuantity($stock_id_code, $wa_location_and_store_id)
{
    $lists = DB::table('wa_stock_moves')
        ->where('stock_id_code', $stock_id_code)
        ->where('wa_location_and_store_id', $wa_location_and_store_id)
        ->select(DB::raw('SUM(`qauntity`) as total_quantity'))
        ->get();
    //    $lists = DB::select(DB::raw("SELECT SUM(`qauntity`) as total_quantity from wa_stock_moves where stock_id_code = '" . $stock_id_code . "' AND wa_location_and_store_id='" . $wa_location_and_store_id . "'"));

    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }

    return $available_quantity;
}

function getItemAvailableQuantity_C($stock_id_code, $wa_location_and_store_id)
{
    $lists = DB::table('wa_stock_moves_C')
        ->select(DB::raw('SUM(qauntity) as total_quantity'))
        ->where('stock_id_code', $stock_id_code)
        ->where('wa_location_and_store_id', $wa_location_and_store_id)
        ->get();


    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}

/**
 * Batch fetch available quantities for multiple items
 * Optimized version to avoid N+1 query problem
 * 
 * @param array $stock_id_codes Array of stock ID codes
 * @param int $wa_location_and_store_id Location ID
 * @return array Associative array [stock_id_code => quantity]
 */
function getItemsAvailableQuantities($stock_id_codes, $wa_location_and_store_id)
{
    if (empty($stock_id_codes)) {
        return [];
    }

    $results = DB::table('wa_stock_moves')
        ->whereIn('stock_id_code', $stock_id_codes)
        ->where('wa_location_and_store_id', $wa_location_and_store_id)
        ->select('stock_id_code', DB::raw('SUM(`qauntity`) as total_quantity'))
        ->groupBy('stock_id_code')
        ->get();

    $quantities = [];
    foreach ($results as $result) {
        $quantities[$result->stock_id_code] = $result->total_quantity ?? 0;
    }

    // Fill in zeros for items not found
    foreach ($stock_id_codes as $code) {
        if (!isset($quantities[$code])) {
            $quantities[$code] = 0;
        }
    }

    return $quantities;
}

function getStockMoveType($row)
{
    $type = '';
    if (!empty($row->document_no)) {
        $inv = explode('-', $row->document_no);
    } else {
        $inv = [];
    }
    switch ($row) {
        case (!empty($row->wa_purchase_order_id)):
            $type = "Purchase";
            break;
        case (!empty($row->document_no) && in_array("TRANS", $inv)):
            $type = "Transfer";
            break;
        case (!empty($row->wa_internal_requisition_id) && !in_array("CIV", $inv)):
            $type = "Sales Invoice";
            break;
        case (!empty($row->stock_adjustment_id)):
            $type = "Adjustment";
            break;
        case (!empty($row->wa_inventory_location_transfer_id)):
            $type = "Delivery Note";
            break;
        case (!empty($row->wa_inventory_location_transfer_id)):
            $type = "Delivery Note";
            break;
        case (!empty($row->ordered_item_id)):
            $type = "Ingredients booking";
            break;

        case (!empty($row->document_no) && in_array("INV", $inv)):
            $type = "Sales Invoice";
            break;
        case (!empty($row->document_no) && in_array("CIV", $inv)):
            $type = "Cash Sales";
            break;
        case (!empty($row->document_no) && in_array("RTN", $inv)):
            $type = "Return";
            break;
        case (!empty($row->document_no) && in_array("RSSC", $inv)):
            $type = "Receive Stock store-C";
            break;
        case (!empty($row->document_no) && in_array("IRSC", $inv)):
            $type = "Internal Requisition Store-C";
            break;
        case (!empty($row->document_no) && in_array("STB", $inv)):
            $type = "Stock Break";
            break;
        case (!empty($row->document_no) && in_array("RSTB", $inv)):
            $type = "Stock Break";
            break;
        case (!empty($row->document_no) && in_array("MARCH24", $inv)):
            $type = "Transfer";
            break;
        case (!empty($row->document_no) && in_array("RFS", $inv)):
            $type = "Return From Store";
            break;
        case (!empty($row->document_no) && in_array("SAS", $inv)):
            $type = "Stock Adjustment Sales";
            break;
        case (!empty($row->document_no) && in_array("SAR", $inv)):
            $type = "Stock Adjustment Return";
            break;
        case (!empty($row->document_no) && in_array("SOB", $inv)):
            $type = "Stock Opening Balance";
            break;
        default:
            $type = "";
    }

    return $type;
}

function getDateFormatted($date)
{
    $date_formatted = '';
    if (!empty($date)) {
        $date_formatted = date('Y-m-d', strtotime($date));
    }
    return $date_formatted;
}

function getDateTimeFormatted($date)
{
    $date_formatted = '';
    if (!empty($date)) {
        $date_formatted = date('Y-m-d h:i:s A', strtotime($date));
    }
    return $date_formatted;
}

function getinventoryItemDeductedQuantity($ordered_item_id, $inventory_item_id)
{
    $quantity = App\Model\WaStockMove::where([['ordered_item_id', $ordered_item_id], ['wa_inventory_item_id', $inventory_item_id]])->pluck('qauntity')->first();

    $quantity = !empty($quantity) ? $quantity : 0;
    $quantity = abs($quantity);
    return $quantity;
}

function getUnserializeList($wa_location_and_store_id)
{
    $lists = WaStockCheckFreeze::where('wa_location_and_store_id', $wa_location_and_store_id)->pluck('wa_inventory_category_ids')->toArray();
    $category_ids = [];
    foreach ($lists as $list) {

        foreach (unserialize($list) as $id) {
            $category_ids[] = $id;
        }
    }
    $category_ids = count($category_ids) > 0 ? $category_ids : [0];
    $data = WaInventoryCategory::whereIn('id', $category_ids)->pluck('category_description', 'id')->toArray();
    return $data;
}

function getNumberSeriesRow($module)
{
    $series_module = WaNumerSeriesCode::where('module', $module)->first();
    return $series_module;
}


function getAmountSumOfDate($date, $type)
{
    $data = \App\Model\PaymentDebit::select(DB::raw("SUM(amount) as amount_sum"))
        ->where('date', $date)
        ->where('type', $type)
        ->groupBy('date')
        ->first();
    $sum = isset($data->amount_sum) ? $data->amount_sum : 0;
    return $sum;
}

function getItemOpeningStock($item_id, $date)
{
    $condition = '';
    if (!empty($date)) {
        $condition = " AND created_at < '" . $date . "'";
    }
    $lists = DB::select(DB::raw("SELECT SUM(`qauntity`) as total_quantity from wa_stock_moves where wa_inventory_item_id = '" . $item_id . "'" . $condition));
    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}

function getItemTotalPurchase($item_code, $start_date, $end_date)
{
    $condition = '';
    if (!empty($start_date)) {
        $condition = " AND delivery_date >= '" . $start_date . "'";
    }
    if (!empty($end_date)) {
        $condition = " AND delivery_date <= '" . $end_date . "'";
    }
    $lists = DB::select(DB::raw("SELECT SUM(`qty_received`) as total_quantity from wa_grns where item_code = '" . $item_code . "'" . $condition));
    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}

function getItemTotalTransfer($item_id, $start_date, $end_date)
{
    $condition = '';
    if (!empty($start_date)) {
        $condition = " AND created_at >= '" . $start_date . "'";
    }
    if (!empty($end_date)) {
        $condition = " AND created_at <= '" . $end_date . "'";
    }
    $lists = DB::select(DB::raw("SELECT SUM(`qauntity`) as total_quantity from wa_stock_moves where wa_inventory_location_transfer_id IS NOT NULL AND wa_inventory_item_id = '" . $item_id . "'" . $condition));
    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}

function getItemTotalIssues($item_id, $start_date, $end_date)
{
    $condition = '';
    if (!empty($start_date)) {
        $condition = " AND created_at >= '" . $start_date . "'";
    }
    if (!empty($end_date)) {
        $condition = " AND created_at <= '" . $end_date . "'";
    }
    $lists = DB::select(DB::raw("SELECT SUM(`qauntity`) as total_quantity from wa_stock_moves where wa_internal_requisition_id IS NOT NULL AND wa_inventory_item_id = '" . $item_id . "'" . $condition));
    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}

function getItemTotalSales($item_id, $start_date, $end_date)
{
    $condition = '';
    if (!empty($start_date)) {
        $condition = " AND created_at >= '" . $start_date . "'";
    }
    if (!empty($end_date)) {
        $condition = " AND created_at <= '" . $end_date . "'";
    }
    $lists = DB::select(DB::raw("SELECT SUM(`qauntity`) as total_quantity from wa_stock_moves where ordered_item_id IS NOT NULL AND wa_inventory_item_id = '" . $item_id . "'" . $condition));
    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}

function getItemTotalQuantity($item_id, $start_date, $end_date)
{
    $condition = '';
    if (!empty($start_date)) {
        $condition = " AND created_at >= '" . $start_date . "'";
    }
    if (!empty($end_date)) {
        $condition = " AND created_at <= '" . $end_date . "'";
    }
    $lists = DB::select(DB::raw("SELECT SUM(`qauntity`) as total_quantity from wa_stock_moves where wa_inventory_item_id = '" . $item_id . "'" . $condition));
    $available_quantity = 0;
    if (!empty($lists[0]) && isset($lists[0]->total_quantity)) {
        $available_quantity = $lists[0]->total_quantity;
    }
    return $available_quantity;
}


function getAccountDetailsFromGlCode($gl_code)
{
    $account_code = WaChartsOfAccount::where('id', $gl_code)->pluck('account_code')->first();
    return $account_code;
}

function getReportDefaultFilter()
{
    return '?start-date=' . date('Y-m-d') . ' 00:00:00&end-date=' . date('Y-m-d') . ' 23:59:00&manage-request=filter';
}

function getReportDefaultFilterForTrialBalance()
{
    return '?start-date=' . date('Y-m-d') . '&end-date=' . date('Y-m-d') . '&manage-request=filter';
}

function getChartsOfaccountarrayWithID()
{
    $account_code = WaChartsOfAccount::pluck('account_code', 'id')->toArray();
    return $account_code;
}

function getBankAccountDropdowns()
{
    $account_numbers = WaBankAccount::select('account_name', 'account_number', 'id')->get();

    $all_accounts = [];

    foreach ($account_numbers as $number) {
        $all_accounts[$number->id] = $number->account_name . ' (' . $number->account_number . ')';
    }
    return $all_accounts;
}

function getBankAccountDropdownsJonralEntry()
{
    $account_numbers = WaBankAccount::select('account_name', 'bank_account_gl_code_id', 'account_number', 'id')->get();

    $all_accounts = [];

    foreach ($account_numbers as $number) {
        $all_accounts[$number->bank_account_gl_code_id] = $number->account_name . ' (' . $number->account_number . ')';
    }
    return $all_accounts;
}

function getPaymentmeList()
{
    $account_numbers = PaymentMethod::pluck('title', 'id')->toArray();

    return count($account_numbers) > 0 ? $account_numbers : [];
}


function getCustomersList()
{
    $customerList = WaCustomer::pluck('customer_name', 'id')->toArray();

    return count($customerList) > 0 ? $customerList : [];
}

function getCustomerDataById($id)
{
    $customerList = WaCustomer::where('id', $id)->first()->customer_code;

    return $customerList;
}

function getCustomersTwoList()
{
    $customerList = WaCustomer::whereIn('customer_code', ["CUST-01946", "CUST-00467"])->pluck('customer_name', 'id')->toArray();

    return count($customerList) > 0 ? $customerList : [];
}


function getCustomerDropdowns()
{
    $customerList = WaCustomer::get();

    $all_accounts = [];

    foreach ($customerList as $number) {
        $all_accounts[$number->id] = $number->customer_name . ' ( ' . $number->customer_code . ' )';
    }
    return $all_accounts;
}

function getCustomerTotalDebt($customer_id)
{
    // echo $customer_id.'prem';die;
    return WaDebtorTran::where('wa_customer_id', $customer_id)->sum('amount');
}

function getFamilyGroupById($id)
{
    $row = WaStockFamilyGroup::where('id', $id)->first();
    return $row;
}


function associatePostpaidOrderByBillUsineOrderIdAndTableId($order_id, $table_id, $user_id)
{
    $tableAlreadyHaveABill = OrderBookedTable::where('table_id', $table_id)
        ->whereHas('getRelativeOrderData', function ($query) {
            $query->where('order_type', 'POSTPAID')
                ->whereHas('getAssociateBillRelation', function ($qq) {
                    $qq->whereHas('getAssociateBill', function ($qqq) {
                        $qqq->where('status', 'PENDING');
                    });
                });
        })->first();
    if ($tableAlreadyHaveABill) {
        $bill_id = $tableAlreadyHaveABill->getRelativeOrderData->getAssociateBillRelation->bill_id;
    } else {
        $new_bill = new Bill();
        $new_bill->user_id = $user_id;
        $new_bill->slug = rand(1111, 99999) . strtotime(date('Y-m-d h:i:s'));
        $new_bill->save();
        $bill_id = $new_bill->id;
    }
    BillOrderRelation::updateOrCreate(
        ['bill_id' => $bill_id, 'order_id' => $order_id]
    );
    return $bill_id;
}

function getUnbilledAmountByTableId($table_id)
{

    $unbilledAmountArr = OrderBookedTable::where('table_id', $table_id)
        ->whereHas('getRelativeOrderData', function ($query) {
            $query->where('order_type', 'POSTPAID')
                ->whereIn('status', ['NEW_ORDER', 'DELIVERED', 'COMPLETED'])
                ->whereHas('getAssociateBillRelation', function ($qq) {
                    $qq->whereHas('getAssociateBill', function ($qqq) {
                        $qqq->where('status', 'PENDING');
                    });
                });
        })->get();
    $total_amount = [];
    foreach ($unbilledAmountArr as $unbilledAmount) {
        $total_amount[] = $unbilledAmount->getRelativeOrderData->order_final_price;
    }

    return (float)array_sum($total_amount);
}


function getBillCommentByTableId($table_id)
{

    $unbilledAmountArr = OrderBookedTable::with('getRelativeOrderData')->where('table_id', $table_id)->orderBy('id', 'DESC')->first();


    return (isset($unbilledAmountArr->getRelativeOrderData->getAssociateBillRelation->getAssociateBill->bill_narration)) ? $unbilledAmountArr->getRelativeOrderData->getAssociateBillRelation->getAssociateBill->bill_narration : "";
}


function getTableByBillid($bill_id)
{
    $order = BillOrderRelation::where('bill_id', $bill_id)->first();
    $tabel = OrderBookedTable::where('order_id', @$order->order_id)->first();
    return @$tabel->table_id;
}

function getTableNumberByBillid($bill_id)
{
    $order = BillOrderRelation::where('bill_id', $bill_id)->first();
    $tabel = OrderBookedTable::with('getRelativeTableData')->where('order_id', @$order->order_id)->first();
    return @$tabel->getRelativeTableData->name;
}

function manageOrdersDiscountsForGlTrans($order_ids_arr)
{
    $chart_of_account = WaChartsOfAccount::where('slug', 'discount-allowed')->first();
    if ($chart_of_account) {
        $orders = Order::select('id', 'order_discounts')->whereIn('id', $order_ids_arr)->whereNotNull('order_discounts')->get();
        foreach ($orders as $order) {
            $order_discount = json_decode($order->order_discounts);
            if (isset($order_discount[0]->discount_amount) && $order_discount[0]->discount_amount != "") {
                $amount = $order_discount[0]->discount_amount;
                $newD = new OrdersDiscountsForGlTran();
                $newD->order_id = $order->id;
                $newD->gl_code_id = $chart_of_account->id;
                $newD->discount_amount = $amount;
                $newD->sale_date = checkAndGetDate(date('Y-m-d H:i:s'));
                $newD->save();
            }
        }
    }
}


function checkAndGetDate($date)
{
    $date = strtotime($date);
    $start = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' 00:00:00')));
    $end = strtotime(date('Y-m-d H:i:s', strtotime(date('Y-m-d') . ' 05:59:59')));
    $date_return = date('Y-m-d');
    if ($date >= $start && $date <= $end) {
        $date_return = date('Y-m-d', strtotime('-1 day', $date));
    }
    return $date_return;
}


function getBillIdByOrderId($order_id)
{
    $bill = BillOrderRelation::where('order_id', $order_id)->first();
    if ($bill) {
        return $bill->bill_id;
    } else {
        return false;
    }
}

function getCurrencyInWords(float $number)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(
        0 => '',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety'
    );
    // $digits = array('', 'hundred','thousand','lakh', 'crore');
    $digits = array('', 'hundred', 'thousand', 'hundred', 'crore');
    while ($i < $digits_length) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural . ' ' . $hundred : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' KSH' : '';
    return ($Rupees ? $Rupees . 'KSH ' : '') . $paise;
}

function getCustomerNameByDocumentNumber($document_no)
{
    $row = WaDebtorTran::with('customerDetail')->where('document_no', $document_no)->first();

    if ($row) {

        return $row->customerDetail->customer_name;
    } else {
        return '';
    }
}

function pre($value)
{
    echo '<pre>';
    print_r($value);
}

function format_amount_with_currency($amount): string
{
    $currencyPrefix = "KES. ";
    try {
        return $currencyPrefix . number_format((float)$amount, 2);
    } catch (Throwable $e) {
        return $currencyPrefix . number_format(0, 2);
    }
}

function getVehicleSupplier($id)
{
    return VehicleSupplier::find($id);
}

function mapDayOfWeekValueToName($value): string
{
    switch ((int)$value) {
        case 1:
            return 'Monday';
        case 2:
            return 'Tuesday';
        case 3:
            return 'Wednesday';
        case 4:
            return 'Thursday';
        case 5:
            return 'Friday';
        case 6:
            return 'Saturday';
        case 7:
            return 'Sunday';
        default:
            return '';
    }
}

function getTokenHasNoUserMessage(): string
{
    return 'A user matching the provided token was not found.';
}

function can(string $action, string $module, User $user= null): bool
{
    $permissions = getUserPermissions();
    if ($user)
    {
        $permissions = getUserPermissions($user);
    }

    return (isset($permissions[$module . "___$action"])) || ($permissions == 'superadmin');
}

function getUserPermissions($user = null)
{
    $logged_user_info = getLoggeduserProfile();
    if ($user)
    {
        $logged_user_info = getLoggeduserProfile($user);
    }

    if ($logged_user_info->role_id == 1) {
        return 'superadmin';
    } else {
        return $logged_user_info->permissions;
    }
}

if (!function_exists('toSql')) {
    function toSql(Builder $builder)
    {
        return vsprintf(str_replace(array('?'), array('\'%s\''), $builder->toSql()), $builder->getBindings());
    }
}

if (!function_exists('checkSplit')) {
    function checkSplit($number)
    {
        $decimalPart = fmod($number, 1);
        return in_array($decimalPart, [0.25, 0.5, 0.75]);
    }
}
if (!function_exists('hasHalfs')) {
    function hasHalfs($number)
    {
        $decimalPart = fmod($number, 1);
        return in_array($decimalPart, [0.5]);
    }
}
if (!function_exists('hasQuotas')) {
    function hasQuotas($number)
    {
        $decimalPart = fmod($number, 1);
        return in_array($decimalPart, [0.25, 0.75]);
    }
}

if (!function_exists('getVatAmount')) {
    function getVatAmount(float $inclusiveAmount, float $rate = 16)
    {
        return ($inclusiveAmount * $rate) / (100 + $rate);
    }
}

if (!function_exists('getExclusiveAmount')) {
    function getExclusiveAmount(float $inclusiveAmount, float $rate = 16)
    {
        return $inclusiveAmount / (1 + $rate / 100);
    }
}
