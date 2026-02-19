<?php

namespace App\Http\Controllers\Admin;

use App\Model\WaInternalRequisition;
use App\Model\WaNumerSeriesCode;
use App\SalesmanShift;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use PDF;
use Session;
use Illuminate\Support\Facades\Validator;
use App\Model\WaSalesInvoice;
use App\Model\WaSalesInvoiceItem;
use App\Model\WaCashSales;
use App\Model\WaCashSalesItem;
use App\Model\WaBanktran;
use App\Model\WaGlTran;
use App\Model\WaShift;
use App\Model\User;
use App\Model\WaCustomer;
use App\Exports\CustomerStatementExport;
use App\Model\WaStockMove;
use App\Model\WaChartsOfAccount;
use App\Model\WaSalesCommissionBand;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\WaDebtorTran;
use App\Model\WaDebtorTran2;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as DomainPDF;



use Excel;

class SalesAndReceiablesReportsController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'sales-and-receivables-reports';
        $this->title = 'Reports';
        $this->pmodule = 'sales-and-receivables-reports';
        ini_set('memory_limit', '4096M');
        set_time_limit(30000000); // Extends to 5 minutes.
    }

    public function index(Request $request)
    {

        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();

        $start_date = $request->input('start-date') ? $request->input('start-date') : Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $end_date = $request->input('end-date') ? $request->input('end-date') : Carbon::today()->endOfDay()->format('Y-m-d H:i:s');


        if (isset($permission[$pmodule . '___customer_invoices']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaSalesInvoice::orderBy('id', 'desc');
            // if ($request->has('start-date')) {
            $all_item = $all_item->where('order_date', '>=', $start_date);
            // }
            // if ($request->has('end-date')) {
            $all_item = $all_item->where('order_date', '<=', $end_date);
            // }
            $all_item = $all_item->get();

            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }

            $breadcum = [$title => '', 'Customer Invoices' => ''];
            return view('admin.salesreceiablesreports.sales_invoice_reports', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function showroomSalesItem(Request $request)
    {

        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___showroom-sales-item']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaSalesInvoice::with('getRelatedCustomer')->select('*');
            if ($request->has('start-date')) {
                $all_item = $all_item->where('created_at', '>=', $request->input('start-date'));
            }
            if ($request->has('wa_customer_id')) {
                $all_item = $all_item->where('wa_customer_id', '>=', $request->input('wa_customer_id'));
            }

            if ($request->has('end-date')) {
                $all_item = $all_item->where('created_at', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();

            //  echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }

            $breadcum = [$title => '', 'Salesman Detailed Summary' => ''];
            return view('admin.salesreceiablesreports.showroom_sales_item', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function customerSalesSummary(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___customer_sales_summary']) || $permission == 'superadmin') {

            $date1 = $request->get('start-date');
            $date2 = $request->get('end-date');

            $lists = WaCashSales::orderBy('id', 'desc');
            if ($request->has('salesman_id')) {
                $lists = $lists->where('creater_id', $request->input('salesman_id'));
            }
            if ($request->has('start-date')) {
                $lists = $lists->whereDate('created_at', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $lists = $lists->whereDate('created_at', '<=', $request->input('end-date'));
            }

            $lists = $lists->get();

            if ($request->has('salesman_id') && $request->salesman_id) {
                $salesmanname = getSalesmanListById($request->salesman_id);
            } else {
                $salesmanname = "-";
            }

            if ($request->has('manage-request') && ($request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'pdf') {
                    $pdf = PDF::loadView('admin.salesreceiablesreports.customer_sales_summary_pdf', compact('title', 'date1', 'date2', 'salesmanname', 'lists'));
                    return $pdf->download('customer_sales_summary_report' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }
            $breadcum = [$title => "Customer Sales Summary", 'Listing' => ''];
            return view('admin.salesreceiablesreports.customer_sales_summary', compact('title', 'lists', 'model', 'salesmanname', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function PaymentReconcilliation_reverse_transactions(Request $request, $id)
    {
        $id = base64_decode($id);
        $item = WaGlTran::where('id', $id)->first();
        if (!$item) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
        DB::transaction(function () use ($item) {
            WaGlTran::where('transaction_no', $item->transaction_no)->delete();
            WaDebtorTran::where('document_no', $item->transaction_no)->delete();
        });
        Session::flash('success', 'Payment Reversed successfully');
        return redirect()->back();
    }

    public function PaymentReconcilliation(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = "payment-reconcilliation";
        $pmodule = "payment-reconcilliation";
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            //$detail = [];
            $expenseArr = WaChartsOfAccount::where('wa_account_group_id', 4)->pluck('account_code')->toArray();
            $all_item = WaGlTran::select('wa_gl_trans.*')->with(['getShiftDetail', 'getShiftDetail.getDeliveryNoteDetail', 'getShiftDetail.getSalesManDetail'])->whereIn('transaction_type', ['Receipt', 'Invoice'])->whereIn('account', $expenseArr);
            if ($request->has('start-date')) {
                $all_item = $all_item->whereDate('wa_gl_trans.created_at', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->whereDate('wa_gl_trans.created_at', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->leftjoin('wa_shifts', 'wa_shifts.id', '=', 'wa_gl_trans.shift_id')->where(function ($w) use ($request) {
                if ($request->salesman_id) {
                    $w->where('wa_shifts.salesman_id', $request->salesman_id);
                }
            })->orderBy('id', 'DESC');
            $all_item = $all_item->get();

            //				sort($detail);
            // echo "<pre>"; print_r($all_item); die;
            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }
            $lists = $all_item;
            $breadcum = [$title => '', 'Salesman Shift' => ''];
            return view('admin.payment-reconcilliation.index', compact('title', 'lists', 'all_item', 'model', 'breadcum', 'all_item'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function updateShiftID(Request $request, $id)
    {

        $update = WaGlTran::where('id', $id)->update(["shift_id" => $request->new_shift_id]);
        Session::flash('success', 'Shift Updated Successfully.');
        return redirect()->back();
    }


    public function updateShiftStatus($id, $status)
    {

        $update = WaShift::where('id', $id)->update(["status" => $status]);
        Session::flash('success', 'Shift ' . $status . ' successfully.');
        return redirect()->back();
    }

    public function salesmanDetailedSummary(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___salesman-detailed-summary']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaCashSales::with('getRelatedSalesman')->select('*');
            if ($request->has('salesman_id')) {
                $all_item = $all_item->where('creater_id', $request->input('salesman_id'));
            }
            if ($request->has('start-date')) {
                $all_item = $all_item->where('order_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->where('order_date', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();
            /*
                      foreach($all_item as $val){
                          echo $val->id."<br>";
                      }
                      die("ok");
      */
            //                echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }

            $breadcum = [$title => '', 'Salesman Detailed Summary' => ''];
            return view('admin.salesreceiablesreports.salesman_detailed_summary', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function customerDetailedSummary(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___customer-detailed-summary']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaCashSales::with('getRelatedSalesman')->select('*');
            if ($request->has('customer_id')) {
                $all_item = $all_item->where('wa_customer_id', $request->input('customer_id'));
            }
            if ($request->has('start-date')) {
                $all_item = $all_item->where('order_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->where('order_date', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();
            /*
                      foreach($all_item as $val){
                          echo $val->id."<br>";
                      }
                      die("ok");
      */
            //                echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }

            $breadcum = [$title => '', 'Customer Detailed Summary' => ''];
            return view('admin.salesreceiablesreports.customer_detailed_summary', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function salesmanSummary(Request $request)
    {

        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;

        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___salesman-summary']) || $permission == 'superadmin') {

            $detail = [];
            $all_cash_receipt = WaCashSales::select('id', 'creater_id', DB::raw('(select SUM(`unit_price`*`quantity`) as totalamnt from wa_cash_sales_items where wa_cash_sales_id = `wa_cash_sales`.`id`) as totalamount'));
            if ($request->has('start-date')) {
                $all_cash_receipt = $all_cash_receipt->where('order_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_cash_receipt = $all_cash_receipt->where('order_date', '<=', $request->input('end-date'));
            }
            $all_cash_receipt = $all_cash_receipt->get();

            foreach ($all_cash_receipt as $cash_receipt) {
                if (!isset($detail[$cash_receipt->creater_id])) {
                    $detail[$cash_receipt->creater_id]['salesman_name'] = $cash_receipt->getRelatedSalesman->name;
                    $detail[$cash_receipt->creater_id]['salesman_id'] = $cash_receipt->getRelatedSalesman->id;
                }

                $detail[$cash_receipt->creater_id]['amount'][] = $cash_receipt->totalamount ? $cash_receipt->totalamount : 0;
            }
            sort($detail);

            if ($request->has('manage-request') && ($request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'pdf') {
                    $date1 = $request->input('start-date');
                    $date2 = $request->input('end-date');

                    $pdf = PDF::loadView('admin.salesreceiablesreports.salesman_summary_pdf', compact('title', 'date1', 'date2', 'detail', 'model', 'breadcum'));
                    return $pdf->download('salesman_summary_report_' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }


            //echo "<pre>"; print_r($list); die;
            $breadcum = [$title => '', 'Salesman Summary' => ''];
            return view('admin.salesreceiablesreports.salesmanSummaryReports', compact('title', 'detail', 'model', 'breadcum'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function salesmanTripSummaryTest(Request $request)
    {

        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___salesman-trip-summary']) || $permission == 'superadmin') {
            $detail = [];
            if ($request->has('shift_id') && $request->shift_id) {
                $all_item = WaStockMove::select('*');

                if ($request->has('salesman_id') && $request->salesman_id) {
                    $all_item->where('wa_location_and_store_id', $request->salesman_id);
                }
                if ($request->has('shift_id') && $request->shift_id) {
                    $all_item->whereIn('shift_id', $request->shift_id);
                }

                $all_item = $all_item->orderBy('document_no', 'ASC')->get();
                // echo "<pre>"; print_r($all_item); die;
                foreach ($all_item as $key => $val) {
                    //	 	            $detail[$val->stock_id_code]['stock_id_code'] = $val->document_no;
                    $detail[$val->document_no][$val->stock_id_code]['stock_id_code'] = $val->stock_id_code;
                    $detail[$val->document_no][$val->stock_id_code]['standard_cost'] = $val->getInventoryItemDetail->standard_cost;
                    $key = $val->stock_id_code;

                    $itemtaken = WaStockMove::where('wa_internal_requisition_id', '!=', null)
                        ->where('wa_inventory_item_id', $val->wa_inventory_item_id)
                        ->where('qauntity', '>', 0);
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $itemtaken->where('wa_location_and_store_id', $request->salesman_id);
                    }
                    if ($request->has('shift_id') && $request->shift_id) {
                        $itemtaken->whereIn('shift_id', $request->shift_id);
                    }
                    $itemtaken = $itemtaken->sum('qauntity');

                    $detail[$val->document_no][$val->stock_id_code]['item_taken'] = $itemtaken;


                    $itemtaken = WaStockMove::where('wa_inventory_item_id', $val->wa_inventory_item_id)
                        ->where('qauntity', '<', 0);
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $itemtaken->whereIn('stock_adjustment_id', $request->shift_id)
                            ->where('wa_location_and_store_id', $request->salesman_id);
                    }
                    if ($request->has('shift_id') && $request->shift_id) {
                        $itemtaken->whereIn('shift_id', $request->shift_id);
                    }
                    $itemtaken = $itemtaken->sum('qauntity');

                    $detail[$val->document_no][$val->stock_id_code]['item_returned'] = $itemtaken;

                    $itemtaken = WaStockMove::where('stock_adjustment_id', null)
                        ->where('wa_inventory_item_id', $val->wa_inventory_item_id)
                        ->where('qauntity', '<', 0);
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $itemtaken->where('wa_location_and_store_id', $request->salesman_id);
                    }
                    if ($request->has('shift_id') && $request->shift_id) {
                        $itemtaken->whereIn('shift_id', $request->shift_id);
                    }
                    $itemtaken = $itemtaken->sum('qauntity');

                    $detail[$val->document_no][$val->stock_id_code]['item_sold'] = $itemtaken;

                    $avgamount = WaStockMove::where('stock_adjustment_id', null)
                        ->where('wa_inventory_item_id', $val->wa_inventory_item_id);
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $avgamount->where('wa_location_and_store_id', $request->salesman_id);
                    }
                    if ($request->has('shift_id') && $request->shift_id) {
                        $avgamount->whereIn('shift_id', $request->shift_id);
                    }
                    $avgamount->where('qauntity', '<', 0);
                    $avgamount = $avgamount->sum(DB::raw('price * qauntity'));

                    $detail[$val->document_no][$val->stock_id_code]['avg_price'] = (abs($itemtaken) > 0) ? ($avgamount / $itemtaken) : 0;
                }
            }
            $all_item = json_decode(json_encode($detail));
            /*
                      foreach($all_item as $key=> $val){
                          echo $key."<hr><br>";
                          foreach($val as $keys=> $vals){
                          echo $vals->stock_id_code."<br>";
                          }
                      }
                      die("ok");
                      echo "<pre>"; print_r($all_item); die;
      */

            if ($request->has('manage-request') && ($request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'pdf') {
                    if ($request->has('shift_id') && $request->shift_id) {
                        $shiftName = implode(",", getlShiftsByIds($request->shift_id));
                    } else {
                        $shiftName = "-";
                    }
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $salesmanname = getSalesmanListById($request->salesman_id);
                    } else {
                        $salesmanname = "-";
                    }
                    $expenseList = WaChartsOfAccount::select('wa_charts_of_accounts.id', 'wa_gl_trans.cheque_image', 'wa_gl_trans.reference', 'wa_gl_trans.narrative', 'wa_charts_of_accounts.account_name', 'wa_charts_of_accounts.account_code')
                        ->join('wa_account_groups', 'wa_account_groups.id', '=', 'wa_charts_of_accounts.wa_account_group_id')
                        ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code')
                        ->where('amount', '>', '0')
                        //->where('wa_account_groups.slug','salesman-expenses')
                        ->whereIn('wa_gl_trans.shift_id', $request->shift_id)
                        ->where('wa_charts_of_accounts.account_code', '12005')
                        ->groupBy('wa_charts_of_accounts.account_code')
                        ->get();

                    foreach ($expenseList as $key => $val) {
                        $expenseList[$key]->total_exp = WaGlTran::where('amount', '>', 0)->whereIn('shift_id', $request->shift_id)->where('account', $val->account_code)->sum('amount');
                    }


                    $pdf = PDF::loadView('admin.salesreceiablesreports.salesman_trip_summary_pdf', compact('all_item', 'shiftName', 'salesmanname', 'expenseList'));
                    return $pdf->download('salesman_trip_summary_report_' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }

            $breadcum = [$title => '', 'Salesman Trip Summary' => ''];
            return view('admin.salesreceiablesreports.salesman_trip_summary', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function salesmanTripSummary(Request $request)
    {

        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___salesman-trip-summary']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaStockMove::select('*')->groupBy('wa_inventory_item_id');

            if ($request->has('salesman_id') && $request->salesman_id) {
                $all_item->where('wa_location_and_store_id', $request->salesman_id);
            }
            if ($request->has('shift_id') && $request->shift_id) {
                $all_item->whereIn('shift_id', $request->shift_id);
            }

            $all_item = $all_item->orderBy('stock_id_code', 'ASC')->get();

            foreach ($all_item as $key => $val) {

                $itemtaken = WaStockMove::where('wa_internal_requisition_id', '!=', null)
                    ->where('wa_inventory_item_id', $val->wa_inventory_item_id)
                    ->where('qauntity', '>', 0);
                if ($request->has('salesman_id') && $request->salesman_id) {
                    $itemtaken->where('wa_location_and_store_id', $request->salesman_id);
                }
                if ($request->has('shift_id') && $request->shift_id) {
                    $itemtaken->whereIn('shift_id', $request->shift_id);
                }
                $itemtaken = $itemtaken->sum('qauntity');

                $all_item[$key]->item_taken = $itemtaken;


                $itemtaken = WaStockMove::where('wa_inventory_item_id', $val->wa_inventory_item_id)
                    ->where('qauntity', '<', 0);
                if ($request->has('salesman_id') && $request->salesman_id) {
                    $itemtaken->whereIn('stock_adjustment_id', $request->shift_id)
                        ->where('wa_location_and_store_id', $request->salesman_id);
                }
                if ($request->has('shift_id') && $request->shift_id) {
                    $itemtaken->whereIn('shift_id', $request->shift_id);
                }
                $itemtaken = $itemtaken->sum('qauntity');

                $all_item[$key]->item_returned = $itemtaken;

                $itemtaken = WaStockMove::where('stock_adjustment_id', null)
                    ->where('wa_inventory_item_id', $val->wa_inventory_item_id)
                    ->where('qauntity', '<', 0);
                if ($request->has('salesman_id') && $request->salesman_id) {
                    $itemtaken->where('wa_location_and_store_id', $request->salesman_id);
                }
                if ($request->has('shift_id') && $request->shift_id) {
                    $itemtaken->whereIn('shift_id', $request->shift_id);
                }
                $itemtaken = $itemtaken->sum('qauntity');

                $all_item[$key]->item_sold = $itemtaken;

                $avgamount = WaStockMove::where('stock_adjustment_id', null)
                    ->where('wa_inventory_item_id', $val->wa_inventory_item_id);
                if ($request->has('salesman_id') && $request->salesman_id) {
                    $avgamount->where('wa_location_and_store_id', $request->salesman_id);
                }
                if ($request->has('shift_id') && $request->shift_id) {
                    $avgamount->whereIn('shift_id', $request->shift_id);
                }
                $avgamount->where('qauntity', '<', 0);
                $avgamount = $avgamount->sum(DB::raw('price * qauntity'));

                $all_item[$key]->avg_price = (abs($itemtaken) > 0) ? ($avgamount / $itemtaken) : 0;
            }
            /*
                      foreach($all_item as $val){
                          echo $val->id."<br>";
                      }
                      die("ok");
      */
            //   echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request') && ($request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'pdf') {
                    if ($request->has('shift_id') && $request->shift_id) {
                        $shiftName = implode(",", getlShiftsByIds($request->shift_id));
                    } else {
                        $shiftName = "-";
                    }
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $salesmanname = getSalesmanListById($request->salesman_id);
                    } else {
                        $salesmanname = "-";
                    }
                    $expenseList = WaChartsOfAccount::select('wa_charts_of_accounts.id', 'wa_gl_trans.cheque_image', 'wa_gl_trans.reference', 'wa_gl_trans.narrative', 'wa_charts_of_accounts.account_name', 'wa_charts_of_accounts.account_code')
                        ->join('wa_account_groups', 'wa_account_groups.id', '=', 'wa_charts_of_accounts.wa_account_group_id')
                        ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code')
                        ->where('amount', '>', '0')
                        //->where('wa_account_groups.slug','salesman-expenses')
                        ->whereIn('wa_gl_trans.shift_id', $request->shift_id)
                        ->where('wa_charts_of_accounts.account_code', '12005')
                        ->groupBy('wa_charts_of_accounts.account_code')
                        ->get();

                    foreach ($expenseList as $key => $val) {
                        $expenseList[$key]->total_exp = WaGlTran::where('amount', '>', 0)->whereIn('shift_id', $request->shift_id)->where('account', $val->account_code)->sum('amount');
                    }


                    $pdf = PDF::loadView('admin.salesreceiablesreports.salesman_trip_summary_pdf', compact('all_item', 'shiftName', 'salesmanname', 'expenseList'));
                    return $pdf->download('salesman_trip_summary_report_' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }

            $breadcum = [$title => '', 'Salesman Trip Summary' => ''];
            return view('admin.salesreceiablesreports.salesman_trip_summary', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function showroomSalesSummary(Request $request)
    {

        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___showroom-sales-summary']) || $permission == 'superadmin') {
            $detail = [];
            $startdate = $request->input('start-date');
            $enddate = $request->input('end-date');
            $salesinvoiceidarr = WaSalesInvoice::where('wa_customer_id', $request->get('wa_customer_id'))->pluck('id');
            $all_item = WaSalesInvoiceItem::select('*');

            if (count($salesinvoiceidarr) > 0) {
                $all_item = $all_item->whereIn('wa_sales_invoice_id', $salesinvoiceidarr);
            }
            if ($request->has('start-date')) {
                $all_item = $all_item->where('created_at', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->where('created_at', '<=', $request->input('end-date'));
            }

            $all_item = $all_item->orderBy('item_no', 'ASC')->get();
            // echo "<pre>"; print_r($all_item); die;
            foreach ($all_item as $key => $val) {
                $detail[$val->item_no]['item_no'] = $val->item_no;
                $detail[$val->item_no]['standard_cost'] = $val->standard_cost;
                $detail[$val->item_no]['created_at'] = $val->created_at;
                $key = $val->item_no;

                $itemtaken = WaSalesInvoiceItem::where('item_no', $val->item_no)
                    ->where('quantity', '>', 0);

                if ($request->has('start-date')) {
                    $itemtaken = $itemtaken->where('created_at', '>=', $request->input('start-date'));
                }
                if ($request->has('end-date')) {
                    $itemtaken = $itemtaken->where('created_at', '<=', $request->input('end-date'));
                }

                $itemtaken = $itemtaken->sum('quantity');

                $detail[$val->item_no]['item_sold'] = $itemtaken;

                $avgamount = WaSalesInvoiceItem::where('item_no', $val->item_no);

                if ($request->has('start-date')) {
                    $avgamount = $avgamount->where('created_at', '>=', $request->input('start-date'));
                }
                if ($request->has('end-date')) {
                    $avgamount = $avgamount->where('created_at', '<=', $request->input('end-date'));
                }
                $avgamount->where('quantity', '>', 0);
                $avgamount = $avgamount->sum(DB::raw('unit_price * quantity'));

                $detail[$val->item_no]['avg_price'] = (abs($itemtaken) > 0) ? ($avgamount / $itemtaken) : 0;
            }
            $all_item = json_decode(json_encode($detail));

            if ($request->has('manage-request') && ($request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'pdf') {
                    if ($request->has('shift_id') && $request->shift_id) {
                        $shiftName = implode(",", getlShiftsByIds($request->shift_id));
                    } else {
                        $shiftName = "-";
                    }
                    if ($request->has('salesman_id') && $request->salesman_id) {
                        $salesmanname = getSalesmanListById($request->salesman_id);
                    } else {
                        $salesmanname = "-";
                    }
                    $expenseList = [];

                    $pdf = PDF::loadView('admin.salesreceiablesreports.showroom_sales_summary_pdf', compact('all_item', 'startdate', 'enddate', 'shiftName', 'salesmanname', 'expenseList'));
                    return $pdf->download('showroom_sales_summary_report_' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }
            //			echo "<pre>"; print_r($all_item); die;
            $breadcum = [$title => '', 'Showroom Sales Summary' => ''];
            return view('admin.salesreceiablesreports.showroom_sales_summary', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function getShiftBySalesman(Request $request)
    {
        if ($request->has('shift_summary')) {
            $salesmanid = $request->salesman_id;
        } else {
            $salesmanid = getUserIdBySalesmanId($request->salesman_id);
        }

        $shifts = WaShift::where('salesman_id', $salesmanid)
            ->where('status', 'close')
            ->where('parking_list_status', 'open')
            ->orderBy('id', 'DESC')->pluck('shift_id', 'id')->toArray();

        return json_encode($shifts);
    }

    public function dailygpreport(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___daily-gp-report']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaCashSales::with('getRelatedSalesman')->select('*');
            if ($request->has('salesman_id')) {
                $all_item = $all_item->where('creater_id', $request->input('salesman_id'));
            }
            if ($request->has('start-date')) {
                $all_item = $all_item->where('order_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->where('order_date', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();
            /*
                      foreach($all_item as $val){
                          echo $val->id."<br>";
                      }
                      die("ok");
      */
            //                echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }

            $breadcum = [$title => '', 'Daily GP Reports' => ''];
            return view('admin.salesreceiablesreports.daily_gp_report', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function monthlygpreport(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___monthly-gp-report']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaCashSales::select('*', DB::raw('(select SUM(`unit_price`*`quantity`) as totalamnt from wa_cash_sales_items where wa_cash_sales_id = `wa_cash_sales`.`id`) as totalamount'), DB::raw('(select SUM(`standard_cost`*`quantity`) as cost_amount from wa_cash_sales_items where wa_cash_sales_id = `wa_cash_sales`.`id`) as cost_amount'));
            if ($request->has('salesman_id')) {
                $all_item = $all_item->where('creater_id', $request->input('salesman_id'));
            }
            if ($request->has('start-date')) {
                $all_item = $all_item->where('order_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->where('order_date', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();


            foreach ($all_item as $cash_receipt) {
                if (!isset($detail[$cash_receipt->creater_id])) {
                    $detail[$cash_receipt->creater_id]['salesman_name'] = $cash_receipt->getRelatedSalesman->name;
                    $detail[$cash_receipt->creater_id]['salesman_id'] = $cash_receipt->getRelatedSalesman->id;
                }
                $detail[$cash_receipt->creater_id]['sales_amount'][] = $cash_receipt->totalamount ? $cash_receipt->totalamount : 0;
                $detail[$cash_receipt->creater_id]['cost_amount'][] = $cash_receipt->cost_amount ? $cash_receipt->cost_amount : 0;
                $detail[$cash_receipt->creater_id]['gross_profit'][] = $cash_receipt->totalamount - $cash_receipt->cost_amount;
            }
            sort($detail);
            //	   echo "<pre>"; print_r($detail); die;


            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdata('xls', $all_item, $request, 'Customer Invoice');
                }
            }

            $breadcum = [$title => '', 'Monthly GP Reports' => ''];
            return view('admin.salesreceiablesreports.monthly_gp_report', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function salescommissionreport(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___monthly-gp-report']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaCashSales::select('*', DB::raw('(select SUM(`unit_price`*`quantity`) as totalamnt from wa_cash_sales_items where wa_cash_sales_id = `wa_cash_sales`.`id`) as totalamount'), DB::raw('(select SUM(`standard_cost`*`quantity`) as cost_amount from wa_cash_sales_items where wa_cash_sales_id = `wa_cash_sales`.`id`) as cost_amount'));
            if ($request->has('salesman_id')) {
                $all_item = $all_item->where('creater_id', $request->input('salesman_id'));
            }
            if ($request->has('start-date')) {
                $all_item = $all_item->where('order_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->where('order_date', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();


            //			echo "<pre>"; print_r($all_item); die;

            foreach ($all_item as $cash_receipt) {
                if (!isset($detail[$cash_receipt->creater_id])) {
                    $detail[$cash_receipt->creater_id]['salesman_name'] = $cash_receipt->getRelatedSalesman->name;
                    $detail[$cash_receipt->creater_id]['salesman_id'] = $cash_receipt->getRelatedSalesman->id;
                }
                $detail[$cash_receipt->creater_id]['sales_amount'][] = $cash_receipt->totalamount ? $cash_receipt->totalamount : 0;
            }
            sort($detail);
            foreach ($detail as $key => $val) {
                $salesamount = array_sum($val['sales_amount']);
                $commissionamount = WaSalesCommissionBand::where('sales_to', '>=', $salesamount)->where('sales_from', '<=', $salesamount)->first();
                if ($commissionamount) {
                    $commissionamnt = $commissionamount->amount;
                } else {
                    $commissionamount = WaSalesCommissionBand::orderBy('amount', 'DESC')->first();
                    $commissionamnt = $commissionamount->amount;
                }
                $detail[$key]['commission_amount'] = $commissionamnt ? $commissionamnt : 0;
            }


            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->salescommissionexportdata('xls', $detail, $request, 'Sales Commission Report');
                }
                if ($request->input('manage-request') == 'pdf') {
                    $date1 = $request->get('start-date');
                    $date2 = $request->get('end-date');

                    $salesmanname = getSalesmanName($request->input('salesman_id'));
                    $pdf = PDF::loadView('admin.salesreceiablesreports.sales_commission_report_pdf', compact('title', 'date1', 'date2', 'salesmanname', 'detail'));
                    return $pdf->download('sales_commission_report' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }

            $breadcum = [$title => '', 'Sales Commission Reports' => ''];
            return view('admin.salesreceiablesreports.sales_commission_report', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function depoitelistProcess(Request $request)
    {
        if (!isset($request->posted_to_account) || count($request->posted_to_account) == 0) {
            Session::flash('warning', 'Select item to process');
            return redirect()->back();
        }

        $start_date = $request->input('start-date') ? $request->input('start-date') : Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $end_date = $request->input('end-date') ? $request->input('end-date') : Carbon::today()->endOfDay()->format('Y-m-d H:i:s');

        $depoitelist = DB::table('wa_merged_payments')->where('wa_merged_payments.is_posted_to_account', 0)
            ->whereIn('wa_merged_payments.id', $request->posted_to_account)
            ->where('amount', '>', 0)
            ->get();
        $val = 0;
        $logged_user_info = getLoggeduserProfile();
        $salesman = User::where('id', $request->salesman_id)->first();
        if (!$salesman) {
            Session::flash('warning', 'Salesman Not found');
            return redirect()->back();
        }
        $salesman_customer = WaCustomer::where('route_id', $salesman->route)->first();
        if (!$salesman_customer) {
            Session::flash('warning', 'Salesman don\'t have customer');
            return redirect()->back();
        }
        $i = 0;
        $equitys = DB::table('wa_merged_payments')->where('wa_merged_payments.is_posted_to_account', 0)
            ->where('payment_account', '12012')
            ->where('amount', '>', 0)
            ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
            ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
            ->sum('amount');
        foreach ($depoitelist as $key => $value) {
            if ($i == 1 && $value->payment_account == '12012') {
                continue;
            }
            if ($value->payment_account == '12012') {
                $i = 1;
                $val += $equitys;
            } else {
                $val += $value->amount;
            }
        }

        $grn = getCodeWithNumberSeries('RECEIPT');
        $module = WaNumerSeriesCode::where('module', 'RECEIPT')->first();
        $deb = new WaDebtorTran;
        $deb->salesman_id = @$salesman->wa_location_and_store_id;
        $deb->salesman_user_id = @$salesman->id;
        $deb->type_number = $module->type_number;
        $deb->wa_customer_id = $salesman_customer->id;
        $deb->customer_number = $salesman_customer->customer_code;
        $deb->trans_date = date('Y-m-d');
        $deb->amount = -$val;
        $deb->document_no = $grn;
        $deb->user_id = $logged_user_info->id;
        $deb->reference = 'Book Clearance';
        $deb->save();
        DB::table('wa_merged_payments')->where('wa_merged_payments.is_posted_to_account', 0)
            ->whereIn('wa_merged_payments.id', $request->posted_to_account)->update(['is_posted_to_account' => 1, 'wa_debtor_trans_id' => $deb->id]);
        DB::table('wa_merged_payments')->where('wa_merged_payments.is_posted_to_account', 0)
            ->where('payment_account', '12012')
            ->where('amount', '>', 0)
            ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
            ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
            ->update(['is_posted_to_account' => 1, 'wa_debtor_trans_id' => $deb->id]);
        Session::flash('success', 'Posted to Account successfully');
        return redirect()->back();
    }

    public function depoitelistProcess___(Request $request)
    {
        $depoitelist = DB::table('wa_merged_payments')->where('wa_merged_payments.is_posted_to_account', 0)
            ->whereIn('wa_merged_payments.id', $request->posted_to_account)
            ->where('amount', '>', 0)
            ->get();
        $val = 0;
        $logged_user_info = getLoggeduserProfile();
        $salesman = User::where('id', $request->salesman_id)->first();
        if (!$salesman) {
            Session::flash('warning', 'Salesman Not found');
            return redirect()->back();
        }
        $salesman_customer = WaCustomer::where('route_id', $salesman->route)->first();
        if (!$salesman_customer) {
            Session::flash('warning', 'Salesman don\'t have customer');
            return redirect()->back();
        }
        $val = $depoitelist->sum('amount');
        // foreach ($depoitelist as $key => $value) {
        // }
        $grn = getCodeWithNumberSeries('RECEIPT');
        $module = WaNumerSeriesCode::where('module', 'RECEIPT')->first();
        $deb = new WaDebtorTran;
        $deb->salesman_id = @$salesman->wa_location_and_store_id;
        $deb->salesman_user_id = @$salesman->id;
        $deb->type_number = $module->type_number;
        $deb->wa_customer_id = $salesman_customer->id;
        $deb->customer_number = $salesman_customer->customer_code;
        $deb->trans_date = date('Y-m-d');
        $deb->amount = -$val;
        $deb->document_no = $grn;
        $deb->user_id = $logged_user_info->id;
        $deb->reference = 'Book Clearance';
        $deb->save();
        DB::table('wa_merged_payments')->where('wa_merged_payments.is_posted_to_account', 0)
            ->whereIn('wa_merged_payments.id', $depoitelist->pluck('id')->toArray())->update(['is_posted_to_account' => 1, 'wa_debtor_trans_id' => $deb->id]);
        Session::flash('success', 'Posted to Account successfully');
        return redirect()->back();
    }

    public function shiftSummary(Request $request)
    {

        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___shift-summary']) || $permission == 'superadmin') {
            $detail = [];
            $breadcum = [];
            $salesmanname = '';
            if ($request->shift_id != null) {
                // Handle both SalesmanShift IDs and WaShift IDs
                $salesmanShiftIds = collect($request->shift_id);
                
                // Check if financial data exists for SalesmanShift IDs directly
                $hasFinancialData = DB::table('wa_merged_payments')
                    ->whereIn('shift_id', $salesmanShiftIds)
                    ->exists();
                
                if ($hasFinancialData) {
                    // Financial data is linked to SalesmanShift IDs directly
                    $shiftIds = $salesmanShiftIds->toArray();
                    $shiftData = SalesmanShift::whereIn('id', $shiftIds)
                        ->with(['salesman', 'salesman_route'])
                        ->get()
                        ->pluck('shift_id')
                        ->toArray();
                } else {
                    // Find corresponding WaShift records for SalesmanShift IDs
                    $waShiftIds = [];
                    foreach ($salesmanShiftIds as $shiftId) {
                        // Check if this is a SalesmanShift ID by looking for corresponding WaShift
                        $waShift = WaShift::where('shift_id', 'LIKE', 'SS-' . $shiftId . '-%')->first();
                        if ($waShift) {
                            $waShiftIds[] = $waShift->id;
                        } else {
                            // Assume it's already a WaShift ID
                            $waShiftIds[] = $shiftId;
                        }
                    }
                    $shiftIds = $waShiftIds;
                    $shiftData = WaShift::whereIn('id', $waShiftIds)->pluck('shift_id')->toArray();
                }
                
                // Update the request to use the correct shift IDs for all subsequent queries
                $request->merge(['shift_id' => $shiftIds]);

                $all_item = WaCashSales::with('getRelatedSalesman')->select('*');
                if ($request->has('salesman_id')) {
                    $all_item = $all_item->where('creater_id', $request->input('salesman_id'));
                }

                $all_item = $all_item->get();

                if ($request->has('salesman_id') && $request->salesman_id) {

                    $salesmanname = getSalesmanUserById($request->salesman_id);
                } else {
                    $salesmanname = "-";
                }

                $expenseArr = WaChartsOfAccount::where('wa_account_group_id', 4)->pluck('account_code')->toArray();

                $depoitelist = DB::table('wa_merged_payments')->select(
                    'wa_merged_payments.id',
                    'wa_merged_payments.payment_account as account',
                    'is_posted_to_account',
                    'wa_merged_payments.id',
                    'wa_merged_payments.description as reference',
                    'wa_merged_payments.check_image as cheque_image',
                    'wa_merged_payments.created_at as trans_date',
                    'wa_merged_payments.payment_account',
                    'wa_merged_payments.narration as narrative',
                    'wa_merged_payments.amount'
                )
                    ->where('wa_merged_payments.amount', '>', '0')
                    ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
                    ->whereIn('wa_merged_payments.payment_account', $expenseArr)
                    ->whereNotIn('payment_account', ['12005', '12003', '12012']) //expense,Equity,OtherPayments
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->get();
                $equitys = DB::table('wa_merged_payments')->select(
                    'wa_merged_payments.id',
                    'wa_merged_payments.payment_account as account',
                    'is_posted_to_account',
                    'wa_merged_payments.id',
                    'wa_merged_payments.description as reference',
                    'wa_merged_payments.check_image as cheque_image',
                    'wa_merged_payments.created_at as trans_date',
                    'wa_merged_payments.payment_account',
                    'wa_merged_payments.narration as narrative',
                    DB::RAW('SUM(wa_merged_payments.amount) as amount')
                )
                    ->where('wa_merged_payments.amount', '>', '0')
                    ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
                    // ->whereIn('wa_merged_payments.payment_account',$expenseArr)
                    ->where('payment_account', '12012') //expense
                    ->groupBy('is_posted_to_account')
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->get();
                if (count($equitys) > 0) {
                    foreach ($equitys as $key => $equity) {
                        $depoitelist[] = (object)[
                            'id' => $equity->id,
                            'account' => $equity->account,
                            'is_posted_to_account' => $equity->is_posted_to_account,
                            'reference' => $equity->reference,
                            'cheque_image' => $equity->cheque_image,
                            'trans_date' => $equity->trans_date,
                            'payment_account' => $equity->payment_account,
                            'narrative' => $equity->narrative,
                            'amount' => $equity->amount,
                        ];
                    }
                }
                $shiftdata = WaShift::where('salesman_id', $request->salesman_id)->first();

                $stotalAmnt = WaDebtorTran::whereIn('wa_debtor_trans.shift_id', $request->shift_id)
                    // ->whereIn('wa_debtor_trans.account',$expenseArr)
                    ->where('type_number', 51)
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_debtor_trans.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)->where('amount', '>', 0)
                    ->sum('wa_debtor_trans.amount');
                $rtotalAmnt = WaDebtorTran::whereIn('wa_debtor_trans.shift_id', $request->shift_id)
                    // ->whereIn('wa_debtor_trans.account',['13004'])
                    ->where('type_number', 109)
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_debtor_trans.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->sum('wa_debtor_trans.amount');

                // $returns = WaInventoryLocationTransferItem::with(['getInventoryItemDetail','getTransferLocation'])
                // ->whereHas('getTransferLocation',function($we) use ($request){
                //     $we->where('shift_id',$request->shift_id);
                // })
                // ->where('is_return',1)->orderBy('return_date','DESC')->get();

                $returns = WaStockMove::with(['getInventoryItemDetail'])->where('qauntity', '<', 0)->where('stock_adjustment_id', '!=', NULL)
                    ->whereIn('shift_id', $request->shift_id)->get();


                $totalAmnt = (float)$stotalAmnt + (float)$rtotalAmnt;
                // dd([(float)$stotalAmnt, (float)$rtotalAmnt,$totalAmnt]);
                $mydebtorlist = WaCustomer::select(
                    'customer_code',
                    'wa_salesman_trans.id as debtor_id',
                    'wa_salesman_trans.invoice_customer_name',
                    'wa_salesman_trans.document_no',
                    'wa_salesman_trans.reference',
                    'wa_salesman_trans.trans_date',
                    'wa_salesman_trans.amount',
                    'pesaflow_response.amount_paid',
                    DB::raw('SUM(wa_salesman_trans.amount) as balance')
                )
                    ->join('wa_salesman_trans', 'wa_salesman_trans.wa_customer_id', '=', 'wa_customers.id')
                    ->join('pesaflow_response', 'pesaflow_response.client_invoice_ref', '=', 'wa_salesman_trans.document_no', 'left')
                    ->whereIn('wa_salesman_trans.shift_id', $request->shift_id)
                    ->having('balance', '>', '0')
                    ->groupBy('wa_salesman_trans.document_no')
                    ->get();

                //echo "<pre>"; print_r($mydebtorlist); die;
                $expenseList = DB::table('wa_merged_payments')
                    ->select(
                        'wa_merged_payments.id',
                        'wa_merged_payments.payment_account as account',
                        'is_posted_to_account',
                        'wa_merged_payments.id',
                        'wa_merged_payments.description as reference',
                        'wa_merged_payments.check_image as cheque_image',
                        'wa_merged_payments.created_at as trans_date',
                        'wa_merged_payments.payment_account',
                        'wa_merged_payments.narration as narrative',
                        'wa_merged_payments.amount'
                    )
                    ->where('wa_merged_payments.amount', '>', '0')
                    ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
                    ->where(function ($e) {
                        $e->orWhere('payment_account', '12005'); //expense
                        $e->orWhere('payment_account', '12003'); //expense
                    })
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->get();


                //   WaChartsOfAccount::select('wa_charts_of_accounts.id',
                //   'wa_merged_payments.check_image as cheque_image','wa_merged_payments.description as reference',
                //   'wa_merged_payments.narration as narrative','wa_charts_of_accounts.account_name','wa_charts_of_accounts.account_code')
                // 		->join('wa_merged_payments','wa_merged_payments.payment_account','=','wa_charts_of_accounts.account_code')
                // 		->where('amount','>','0')
                // 		->whereIn('wa_merged_payments.shift_id',$request->shift_id)
                // 		->where('wa_charts_of_accounts.account_code','12005')
                // 		->groupBy('wa_charts_of_accounts.account_code')
                // 		->get();
                //         $WaGlTran = DB::table('wa_merged_payments')->where('amount','>',0)->whereIn('shift_id',$request->shift_id)->get();
                // 	//echo "<pre>"; print_r($expenseList); die;
                // 	foreach($expenseList as $key=> $val){
                //  		$expenseList[$key]->total_exp = $WaGlTran->where('payment_account',$val->account_code)->sum('amount');
                // 	}
                $unmerged_trans = DB::table('wa_salesman_trans')->select(
                    'id',
                    'reference',
                    DB::RAW('(select wa_shifts.shift_id from wa_shifts where wa_shifts.id = wa_salesman_trans.shift_id limit 1) as shift'),
                    'shift_id',
                    'cheque_image',
                    'trans_date',
                    'account',
                    'narrative',
                    'amount'
                )
                    ->where('amount', '<', '0')
                    ->whereIn('shift_id', $request->shift_id)
                    ->where('type_number', '12')
                    ->where('is_cheque_trans', 0)
                    ->where('is_settled', 0)
                    ->orderBy('trans_date', 'DESC')
                    ->get()->map(function ($item) {
                        $item->amount = abs($item->amount);
                        return $item;
                    });
            } else {
                $shiftData = [];
                $all_item = [];
                $totalAmnt = 0;
                $mydebtorlist = [];
                $expenseList = [];
                $depoitelist = [];
                $returns = [];
                $unmerged_trans = [];
            }
            /*
                      foreach($all_item as $val){
                          echo $val->id."<br>";
                      }
                      die("ok");
      */
            //                echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request')) {
                if ($request->input('manage-request') == 'pdf') {

                    $pdf = PDF::loadView('admin.salesreceiablesreports.shift_summary_pdf', compact('returns', 'title', 'all_item', 'depoitelist', 'mydebtorlist', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
                    //	echo $pdf; die;
                    return $pdf->download('shift_summary_report' . date('Y_m_d_h_i_s') . '.pdf');
                } elseif ($request->input('manage-request') == 'print') {
                    return view('admin.salesreceiablesreports.shift_summary_pdf', compact('returns', 'title', 'all_item', 'depoitelist', 'mydebtorlist', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
                }
            }

            $breadcum = [$title => '', 'Shift Summary' => ''];
            return view('admin.salesreceiablesreports.shift_summary', compact('unmerged_trans', 'returns', 'title', 'all_item', 'depoitelist', 'mydebtorlist', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail', 'salesmanname'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function shiftSummary_returns_reverse(Request $request)
    {
        $validations = Validator::make($request->all(), [
            'stock_move_id' => 'required|array',
            'stock_move_id.*' => 'required|exists:wa_stock_moves,id'
        ]);
        if ($validations->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'result' => 0,
                    'message' => $validations->errors()
                ]);
            }

            return redirect()->back()->withErrors(['errors' => $validations->errors()]);
        }
        $delete = WaStockMove::whereIn('id', $request->stock_move_id)->delete();
        if ($delete) {
            return response()->json([
                'result' => 1,
                'message' => 'Returns reversed successfully'
            ]);
        }
        return response()->json([
            'result' => -1,
            'message' => 'Something went wrong'
        ]);
    }

    public function shiftSummary__(Request $request)
    {

        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___shift-summary']) || $permission == 'superadmin') {
            $detail = [];
            if ($request->shift_id != null) {
                $shiftData = WaShift::whereIn('id', $request->shift_id)->pluck('shift_id')->toArray();

                $all_item = WaCashSales::with('getRelatedSalesman')->select('*');
                if ($request->has('salesman_id')) {
                    $all_item = $all_item->where('creater_id', $request->input('salesman_id'));
                }

                $all_item = $all_item->get();

                if ($request->has('salesman_id') && $request->salesman_id) {

                    $salesmanname = getSalesmanUserById($request->salesman_id);
                } else {
                    $salesmanname = "-";
                }

                $expenseArr = WaChartsOfAccount::where('wa_account_group_id', 4)->pluck('account_code')->toArray();

                $depoitelist = DB::table('wa_merged_payments')->select('wa_merged_payments.id', 'wa_merged_payments.payment_account as account', 'is_posted_to_account', 'wa_merged_payments.id', 'wa_merged_payments.description as reference', 'wa_merged_payments.check_image as cheque_image', 'wa_merged_payments.created_at as trans_date', 'wa_merged_payments.payment_account', 'wa_merged_payments.narration as narrative', 'wa_merged_payments.amount')
                    ->where('wa_merged_payments.amount', '>', '0')
                    ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
                    ->whereIn('wa_merged_payments.payment_account', $expenseArr)
                    ->where('payment_account', '!=', '12005') //expense
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->get();
                $shiftdata = WaShift::where('salesman_id', $request->salesman_id)->first();

                $stotalAmnt = WaDebtorTran::whereIn('wa_debtor_trans.shift_id', $request->shift_id)
                    // ->whereIn('wa_debtor_trans.account',$expenseArr)
                    ->where('type_number', 51)
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_debtor_trans.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)->where('amount', '>', 0)
                    ->sum('wa_debtor_trans.amount');
                $rtotalAmnt = WaDebtorTran::whereIn('wa_debtor_trans.shift_id', $request->shift_id)
                    // ->whereIn('wa_debtor_trans.account',['13004'])
                    ->where('type_number', 109)
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_debtor_trans.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->sum('wa_debtor_trans.amount');

                $returns = WaInventoryLocationTransferItem::with(['getInventoryItemDetail', 'getTransferLocation'])
                    ->whereHas('getTransferLocation', function ($we) use ($request) {
                        $we->where('shift_id', $request->shift_id);
                    })
                    ->where('is_return', 1)->orderBy('return_date', 'DESC')->get();

                $totalAmnt = (float)$stotalAmnt + (float)$rtotalAmnt;
                // dd([(float)$stotalAmnt, (float)$rtotalAmnt,$totalAmnt]);
                $mydebtorlist = WaCustomer::select(
                    'customer_code',
                    'wa_salesman_trans.id as debtor_id',
                    'customer_name',
                    'wa_salesman_trans.reference',
                    'wa_salesman_trans.trans_date',
                    DB::raw('SUM(amount) as balance')
                )
                    ->join('wa_salesman_trans', 'wa_salesman_trans.wa_customer_id', '=', 'wa_customers.id')
                    ->whereIn('wa_salesman_trans.shift_id', $request->shift_id)
                    ->having('balance', '>', '0')
                    ->groupBy('wa_salesman_trans.invoice_customer_name')
                    ->get();

                //echo "<pre>"; print_r($mydebtorlist); die;
                $expenseList = DB::table('wa_merged_payments')->select('wa_merged_payments.id', 'wa_merged_payments.payment_account as account', 'is_posted_to_account', 'wa_merged_payments.id', 'wa_merged_payments.description as reference', 'wa_merged_payments.check_image as cheque_image', 'wa_merged_payments.created_at as trans_date', 'wa_merged_payments.payment_account', 'wa_merged_payments.narration as narrative', 'wa_merged_payments.amount')
                    ->where('wa_merged_payments.amount', '>', '0')
                    ->whereIn('wa_merged_payments.shift_id', $request->shift_id)
                    ->where('payment_account', '12005') //expense
                    ->join('wa_shifts', 'wa_shifts.id', '=', 'wa_merged_payments.shift_id')->where('wa_shifts.salesman_id', $request->salesman_id)
                    ->get();
                //   WaChartsOfAccount::select('wa_charts_of_accounts.id',
                //   'wa_merged_payments.check_image as cheque_image','wa_merged_payments.description as reference',
                //   'wa_merged_payments.narration as narrative','wa_charts_of_accounts.account_name','wa_charts_of_accounts.account_code')
                // 		->join('wa_merged_payments','wa_merged_payments.payment_account','=','wa_charts_of_accounts.account_code')
                // 		->where('amount','>','0')
                // 		->whereIn('wa_merged_payments.shift_id',$request->shift_id)
                // 		->where('wa_charts_of_accounts.account_code','12005')
                // 		->groupBy('wa_charts_of_accounts.account_code')
                // 		->get();
                //         $WaGlTran = DB::table('wa_merged_payments')->where('amount','>',0)->whereIn('shift_id',$request->shift_id)->get();
                // 	//echo "<pre>"; print_r($expenseList); die;
                // 	foreach($expenseList as $key=> $val){
                //  		$expenseList[$key]->total_exp = $WaGlTran->where('payment_account',$val->account_code)->sum('amount');
                // 	}
                $unmerged_trans = DB::table('wa_salesman_trans')->select(
                    'id',
                    'reference',
                    DB::RAW('(select wa_shifts.shift_id from wa_shifts where wa_shifts.id = wa_salesman_trans.shift_id limit 1) as shift'),
                    'shift_id',
                    'cheque_image',
                    'trans_date',
                    'account',
                    'narrative',
                    'amount'
                )
                    ->where('amount', '<', '0')
                    ->whereIn('shift_id', $request->shift_id)
                    ->where('type_number', '12')
                    ->where('is_cheque_trans', 0)
                    ->where('is_settled', 0)
                    ->orderBy('trans_date', 'DESC')
                    ->get()->map(function ($item) {
                        $item->amount = abs($item->amount);
                        return $item;
                    });
            } else {
                $shiftData = [];
                $all_item = [];
                $totalAmnt = 0;
                $mydebtorlist = [];
                $expenseList = [];
                $depoitelist = [];
                $returns = [];
                $unmerged_trans = [];
            }
            /*
                      foreach($all_item as $val){
                          echo $val->id."<br>";
                      }
                      die("ok");
      */
            //                echo "<pre>"; print_r($all_item); die;

            if ($request->has('manage-request')) {
                if ($request->input('manage-request') == 'pdf') {

                    $pdf = PDF::loadView('admin.salesreceiablesreports.shift_summary_pdf', compact('returns', 'title', 'all_item', 'shiftdata', 'salesmanname', 'depoitelist', 'mydebtorlist', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
                    //	echo $pdf; die;
                    return $pdf->download('shift_summary_report' . date('Y_m_d_h_i_s') . '.pdf');
                } elseif ($request->input('manage-request') == 'print') {
                    return view('admin.salesreceiablesreports.shift_summary_pdf', compact('returns', 'title', 'all_item', 'shiftdata', 'salesmanname', 'depoitelist', 'mydebtorlist', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
                }
            }

            $breadcum = [$title => '', 'Shift Summary' => ''];
            return view('admin.salesreceiablesreports.shift_summary', compact('unmerged_trans', 'returns', 'title', 'all_item', 'shiftdata', 'depoitelist', 'mydebtorlist', 'salesmanname', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function showroomShiftSummary(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___showroom-shift-summary']) || $permission == 'superadmin') {
            $detail = [];
            $shiftData = [];

            $all_item = WaCashSales::with('getRelatedSalesman')->select('*');
            $all_item = $all_item->get();

            /*
                      if($request->has('salesman_id') && $request->salesman_id){
                          $salesmanname = getSalesmanListById($request->salesman_id);
                      }else{
                          $salesmanname = "-";
                      }
      */

            $expenseArr = [];
            $depoitelist = WaBanktran::with(['getPaymentMethod', 'getCashierDetail', 'debt_or_trans'])->where('wa_banktrans.type_number', '12')
                ->join('wa_debtor_trans', 'wa_debtor_trans.document_no', '=', 'wa_banktrans.document_no')
                ->orderBy('wa_banktrans.id', 'desc');
            if ($request->has('start-date')) {
                $depoitelist = $depoitelist->where('wa_banktrans.created_at', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $depoitelist = $depoitelist->where('wa_banktrans.created_at', '<=', $request->input('end-date'));
            }
            if ($request->has('customer_id')) {
                $customerid = getCustomerDataById($request->get('customer_id'));
                //echo $customerid;die;
                $depoitelist = $depoitelist->where('customer_number', $customerid);
            }
            $depoitelist = $depoitelist->get();

            $totalAmnt = 0;
            //				echo "<pre>"; print_r($depoitelist); die;
            /*
                      $mydebtorlist = WaCustomer::select('customer_code','wa_debtor_trans.id as debtor_id','customer_name','wa_debtor_trans.reference','wa_debtor_trans.trans_date',DB::raw('(select sum(amount) as totalamount from wa_debtor_trans where wa_customer_id=`wa_customers`.`id` group by `customer_code`) as balance'))
                      ->join('wa_debtor_trans','wa_debtor_trans.wa_customer_id','=','wa_customers.id')
                      ->having('balance','>','0')
                      ->groupBy('customer_code')
                      ->get();
      */

            $mydebtorlist = WaSalesInvoice::with(['getRelatedItem', 'getRelatedCustomerAllocatedAmnt', 'getRelatedCustomer'])->orderBy('id', 'desc');
            if ($request->has('start-date')) {
                $mydebtorlist = $mydebtorlist->where('created_at', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $mydebtorlist = $mydebtorlist->where('created_at', '<=', $request->input('end-date'));
            }
            if ($request->has('customer_id')) {
                $mydebtorlist = $mydebtorlist->where('wa_customer_id', $request->get('customer_id'));
            }
            $mydebtorlist = $mydebtorlist->get();

            /*
                         $expenseList = WaChartsOfAccount::select('wa_charts_of_accounts.id','wa_gl_trans.cheque_image','wa_gl_trans.reference','wa_gl_trans.narrative','wa_gl_trans.id','wa_gl_trans.transaction_no as document_no','wa_charts_of_accounts.account_name','wa_charts_of_accounts.account_code')
                               ->join('wa_account_groups','wa_account_groups.id','=','wa_charts_of_accounts.wa_account_group_id')
                               ->join('wa_gl_trans','wa_gl_trans.account','=','wa_charts_of_accounts.account_code')
                              //->where('amount','>','0')
                              ->where('wa_gl_trans.transaction_type','EXPENSE')
                              ->where('wa_charts_of_accounts.account_code','12009')
                              ->groupBy('wa_charts_of_accounts.account_code')
                              ->get();
                           //echo "<pre>"; print_r($expenseList); die;
                           foreach($expenseList as $key=> $val){
                               $expenseList[$key]->total_exp = WaGlTran::where('amount','>',0)->where('account',$val->account_code)->sum('amount');
                           }
      */
            $expenseList = WaBanktran::with(['getPaymentMethod'])->where('type_number', '2')->where('bank_gl_account_code', '12009');
            if ($request->input('start-date') != "" && $request->input('end-date') != "") {
                $expenseList->where('created_at', '>=', $request->input('start-date'))->where('created_at', '<=', $request->input('end-date'));
            }
            $expenseList->orderBy('trans_date', 'DESC');
            $expenseList = $expenseList->get();


            //echo "<pre>"; print_r($mydebtorlist); die;

            if ($request->has('manage-request') && ($request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'pdf') {
                    $date1 = $request->get('start-date');
                    $date2 = $request->get('end-date');
                    $pdf = PDF::loadView('admin.salesreceiablesreports.showroom_shift_summary_pdf', compact('title', 'date1', 'date2', 'all_item', 'shiftdata', 'salesmanname', 'depoitelist', 'mydebtorlist', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
                    //	echo $pdf; die;
                    return $pdf->download('shift_summary_report' . date('Y_m_d_h_i_s') . '.pdf');
                }
            }

            $breadcum = [$title => '', 'Shift Summary' => ''];
            return view('admin.salesreceiablesreports.showroom_shift_summary', compact('title', 'all_item', 'shiftdata', 'depoitelist', 'mydebtorlist', 'salesmanname', 'expenseList', 'shiftData', 'totalAmnt', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function salescommissionexportdata($filetype, $mixed_array, $request, $reportname)
    {
        $export_array = [];
        $file_name = 'salescommissionreport';
        $export_array[] = array($reportname); //heading;


        if ($request->has('start-date')) {
            $export_array[] = ['Start Date: ' . date('d/m/Y', strtotime($request->input('start-date')))];
        }
        if ($request->has('end-date')) {
            $export_array[] = ['End Date: ' . date('d/m/Y', strtotime($request->input('end-date')))];
        }


        $counter = 1;


        $export_array[] = array('Salesman', 'Sales Amount', 'Commission Amount');

        $final_amount = [];
        $commission_total_amount = [];
        //            echo "<pre>"; print_r($mixed_array); die;
        foreach ($mixed_array as $datas) {
            $total_amount = array_sum($datas['sales_amount']);
            $final_amount[] = $total_amount;
            $commission_total_amount[] = $datas['commission_amount'];


            $export_array[] = [
                ucfirst($datas['salesman_name']),
                manageAmountFormat(array_sum($datas['sales_amount'])),
                manageAmountFormat($datas['commission_amount'])
            ];


            $counter++;
        }
        $export_array[] = array();
        $export_array[] = array('Total', manageAmountFormat(array_sum($final_amount)), manageAmountFormat(array_sum($commission_total_amount))); //restro;


        $this->downloadExcelFile($export_array, $filetype, $file_name);
    }


    public function exportdata($filetype, $mixed_array, $request, $reportname)
    {
        $export_array = [];
        $file_name = 'customerinvoice';
        $export_array[] = array($reportname); //heading;


        if ($request->has('start-date')) {
            $export_array[] = ['Start Date: ' . date('d/m/Y', strtotime($request->input('start-date')))];
        }
        if ($request->has('end-date')) {
            $export_array[] = ['End Date: ' . date('d/m/Y', strtotime($request->input('end-date')))];
        }


        $counter = 1;


        $export_array[] = array('SN', 'Invoice Number', 'Customer Name', 'Date', 'Due Date', 'Invoice Total', 'Paid', 'Due');

        $final_amount = [];

        foreach ($mixed_array as $row) {
            $total_amount = $row->getRelatedItem->sum('total_cost_with_vat');
            $final_amount[] = $total_amount;


            $export_array[] = [
                $counter,
                $row->sales_invoice_number,
                ucfirst($row->getRelatedCustomer->customer_name),
                $row->order_date,
                $row->order_date,
                manageAmountFormat($total_amount),
                '0.00',
                '0.00'
            ];


            $counter++;
        }
        $export_array[] = array();
        $export_array[] = array('', 'Total', '', '', '', manageAmountFormat(array_sum($final_amount)), '0.00', '0.00'); //restro;


        $this->downloadExcelFile($export_array, $filetype, $file_name);
    }

    public function downloadExcelFile($data, $type, $file_name)
    {
        // refrence url http://www.maatwebsite.nl/laravel-excel/docs/blade
        //http://www.easylaravelbook.com/blog/2016/04/19/exporting-laravel-data-to-an-excel-spreadsheet/
        return Excel::create($file_name, function ($excel) use ($data) {
            $excel->sheet('mySheet', function ($sheet) use ($data) {
                $sheet->fromArray($data);
            });
        })->download($type);
    }


    public function dailyCashReceiptSummary(Request $request)
    {
        //$this->managetimeForallCron();
        $title = $this->title;
        $model = $this->model;
        $pmodule = $this->pmodule;
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$pmodule . '___daily-cash-receipt-summary']) || $permission == 'superadmin') {
            $detail = [];
            $all_item = WaBanktran::where('type_number', '12')->orderBy('id', 'desc');
            if ($request->has('start-date')) {
                $all_item = $all_item->whereDate('trans_date', '>=', $request->input('start-date'));
            }
            if ($request->has('end-date')) {
                $all_item = $all_item->whereDate('trans_date', '<=', $request->input('end-date'));
            }
            $all_item = $all_item->get();

            //dd($all_item);

            if ($request->has('manage-request') && ($request->input('manage-request') == 'xls' || $request->input('manage-request') == 'pdf')) {
                if ($request->input('manage-request') == 'xls') {
                    $this->exportdataReceipSummary('xls', $all_item, $request, 'Daily Cash Receipt Summary');
                }
            }

            $breadcum = [$title => '', 'Daily Cash Receipt Summary' => ''];
            return view('admin.salesreceiablesreports.daily_cash_receipt_summary', compact('title', 'all_item', 'model', 'breadcum', 'detail'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function exportdataReceipSummary($filetype, $mixed_array, $request, $reportname)
    {
        $export_array = [];
        $file_name = 'Daily Cash Receipt Summary';
        $export_array[] = array($reportname); //heading;
        if ($request->has('start-date')) {
            $export_array[] = ['Start Date: ' . date('d/m/Y', strtotime($request->input('start-date')))];
        }
        if ($request->has('end-date')) {
            $export_array[] = ['End Date: ' . date('d/m/Y', strtotime($request->input('end-date')))];
        }
        $counter = 1;


        $export_array[] = array('SN', 'Receipt No', 'Date', 'Customer Name', 'Payment Method', 'Cashier Name', 'Reference', 'Amount');
        $final_amount = [];

        foreach ($mixed_array as $item) {

            $final_amount[] = $item->amount;


            $export_array[] = [
                $counter,
                $item->document_no,
                date('Y-m-d', strtotime($item->trans_date)),
                getCustomerNameByDocumentNumber($item->document_no),
                $item->getPaymentMethod->title,
                $item->getCashierDetail ? $item->getCashierDetail->name : '',
                $item->reference,
                manageAmountFormat($item->amount)
            ];


            $counter++;
        }
        $export_array[] = array();
        $export_array[] = array('', '', '', '', '', '', 'Total', manageAmountFormat(array_sum($final_amount))); //restro;


        $this->downloadExcelFile($export_array, $filetype, $file_name);
    }


    public function customerStatement(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___customer-statement']) || $permission == 'superadmin') {
            $restroList = $this->getRestaurantList();
            $date1 = $request->get('from') ?? Carbon::now()->startOfDay();
            $date2 = $request->get('to') ?? Carbon::now()->endOfDay();

            $start = Carbon::parse($request->from)->startOfDay();
            $end = Carbon::parse($request->to)->endOfDay();
            $lists = WaDebtorTran::select(
                'wa_debtor_trans.*',
                'invoices.requisition_no as invoice'
            )
            ->where('wa_customer_id', $request->customer_id)
                ->leftJoin('wa_internal_requisitions as invoices', 'wa_debtor_trans.wa_sales_invoice_id', 'invoices.id')
                ->whereBetween('wa_debtor_trans.created_at', [$start, $end])
                ->orderBy('wa_debtor_trans.created_at')
                ->get()
                ->map(function($record) {
                    if ($record->invoice) {
                        $record->reference = "$record->reference / $record->invoice";
                    }

                    return $record;
                });

            $customerAccounts = WaCustomer::pluck('customer_name', 'id');
            $getOpeningBlance = WaDebtorTran::where('wa_customer_id', $request->customer_id)->where('created_at', '<', $date1)->sum('amount') ?? 0;
            $closingBalance = ($lists->sum('amount') ?? 0) + $getOpeningBlance;

            $supplier = WaDebtorTran::where('wa_customer_id', $request->customer_id)->first();
            $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();
            if ($request->get('manage-request') == "pdf" || $request->get('manage-request') == "print"  || $request->get('manage-request') == "excel") {

                if ($request->get('manage-request') == "print") {
                    return view('admin.salesreceiablesreports.customer_statement_pdf', compact('lists', 'supplier', 'getOpeningBlance', 'date1', 'date2', 'number_series_list'));
                }
                if ($request->get('manage-request') == "excel") {
                    return Excel::download(new CustomerStatementExport($lists, $number_series_list), 'customer_statement_' .  date('Y_m_d_h_i_s') . '.xlsx');
                }

                // $pdf = \PDF::loadView('admin.salesreceiablesreports.customer_statement_pdf', compact('lists', 'supplier', 'getOpeningBlance', 'date1', 'date2', 'number_series_list'));
                $pdf = DomainPDF::loadView('admin.salesreceiablesreports.customer_statement_pdf', compact('lists', 'supplier', 'getOpeningBlance', 'date1', 'date2', 'number_series_list'))->setPaper('a4', 'landscape');
                return $pdf->download('customer_statement_' . time() . '.pdf');
            }
            $breadcum = [$title => "Reports", 'Customer Statement' => ''];
            return view('admin.salesreceiablesreports.customer_statement', compact('supplier', 'title', 'customerAccounts', 'restroList', 'getOpeningBlance', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'number_series_list', 'closingBalance'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function customerStatement2(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___customer-statement']) || $permission == 'superadmin') {

            $restroList = $this->getRestaurantList();
            $date1 = $request->get('to');
            $date2 = $request->get('from');
            $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();
            $lists = [];

            if ($request->get('to') && $request->get('from')) {
                $lists = \App\Model\WaDebtorTran2::whereDate('trans_date', '>=', $date1)->whereDate('trans_date', '<=', $date2);
                if ($request->customer_code) {
                    $lists->where('customer_number', $request->customer_code);
                }
                $lists->orderBy('created_at', 'asc');
                // if($request->get('manage-request')=="pdf" || $request->get('manage-request')=="print"){
                // }else{
                // $lists->orderBy('id', 'desc');
                // }
                $lists = $lists->get();
            }
            $supplierList = WaCustomer::orderBy('created_at', 'asc')->pluck('customer_name', 'customer_code');
            $getOpeningBlance = \App\Model\WaDebtorTran2::with(['customerDetail'])->select('*');
            if ($request->customer_code) {
                $getOpeningBlance->where('customer_number', $request->customer_code);
            }
            if ($date1 != "") {
                $getOpeningBlance->whereDate('trans_date', '<', $date1);
            }
            $getOpeningBlance = $getOpeningBlance->sum('amount');
            if ($request->customer_code) {
                $supplier = \App\Model\WaDebtorTran2::where('customer_number', $request->customer_code)->first();
            } else {
                $supplier = [];
            }
            if ($request->get('manage-request') == "pdf" || $request->get('manage-request') == "print") {
                //  $lists = WaSuppTran::orderBy('trans_date','desc')->get();
                if ($request->get('manage-request') == "print") {
                    return view('admin.salesreceiablesreports.customer_statement_pdf', compact('lists', 'supplier', 'number_series_list', 'getOpeningBlance', 'date1', 'date2'));
                }
                $pdf = \PDF::loadView('admin.salesreceiablesreports.customer_statement_pdf', compact('lists', 'supplier', 'number_series_list', 'getOpeningBlance', 'date1', 'date2'));
                //    return $pdf->stream();

                return $pdf->download('customer_statement_' . time() . '.pdf');
            }

            $breadcum = [$title => "Reports", 'Customer Statement' => ''];
            return view('admin.salesreceiablesreports.customer_statement', compact('supplier', 'title', 'supplierList', 'restroList', 'getOpeningBlance', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'supplier_code', 'number_series_list'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    //shift summary
    public function print_returns_shift(Request $request)
    {
        $salesmanname = '';
        if ($request->salesman_id) {
            $salesmanname = getSalesmanUserById($request->salesman_id);
        }
        $shiftData = WaShift::whereIn('id', @$request->shift_id)->pluck('shift_id')->toArray();

        $returns = WaStockMove::with(['getInventoryItemDetail'])->where('qauntity', '<', 0)->where('stock_adjustment_id', '!=', NULL)
            ->whereIn('shift_id', @$request->shift_id)->get();
        return view('admin.salesreceiablesreports.print_returns_shift', compact('returns', 'salesmanname', 'shiftData'));
    }
}
