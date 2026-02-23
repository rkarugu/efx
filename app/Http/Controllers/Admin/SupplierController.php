<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SupplierInvoice\Discount\UpdateDeliveryDistributionDiscount;
use App\Events\SupplierInvoice\SupplierInvoiceCreated;
use App\Exports\SupplierInvoiceExport;
use App\FinancialNote;
use App\Model\WaLocationAndStore;
use App\Model\WaPaymentTerm;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Model\WaSupplier;
use App\Model\WaSupplierLog;
use App\Model\WaSuppTran;
use App\Model\WaGrn;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\Session;
use App\Mail\SupplierNotification;
use Illuminate\Support\Facades\Validator;
use App\Model\Restaurant;
use App\Model\TaxManager;
use PDF;
use App\Model\WaAccountingPeriod;
use App\Model\WaNumerSeriesCode;
use App\Model\WaInventoryItemSupplierDataApprovals;
use App\Model\WaGlTran;
use App\Model\WaInventoryItemSupplierData;
use App\Model\WaCompanyPreference;
use App\Model\WaBankAccount;
use App\Model\WaUserSupplier;
use App\Model\WaStockMove;
use App\Model\WaBanktran;
use App\Model\User;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryItemSupplier;
use App\Models\WaSupplierDistributor;

use App\Model\WaPurchaseOrder;
use App\Models\AdvancePaymentAllocation;
use App\Rules\Invoice\GrnMatchValidator;
use App\WaSupplierInvoice;
use App\WaSupplierInvoiceItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Facades\Excel as FacadesExcel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;

class SupplierController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'maintain-suppliers';
        $this->title = 'Suppliers';
        $this->pmodule = 'maintain-suppliers';
    }

    public function index()
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $breadcum = [$this->title => route($this->model . '.index')];
        $suppliers = WaSupplier::query()
            ->select('id', 'name', 'supplier_code')
            ->whereHas('users', function ($query) {
                $user = getLoggeduserProfile();
                $query->where('user_id', $user->id);
            })->get();

        return view('admin.maintainsuppliers.index_server', [
            'model' => $this->model,
            'title' => $this->title,
            'pmodule' => $this->pmodule,
            'breadcum' => $breadcum,
            'suppliers' => $suppliers,
        ]);
    }

    public function supplier_unverified_list(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___approve-new-supplier']) || $permission == 'superadmin') {
            $lists = [];
            $breadcum = [$title => route($this->pmodule . '.supplier_unverified_list'), 'Listing' => ''];
            return view('admin.maintainsuppliers.supplier_unverified_list', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function supplier_unverified_edit_list(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___approve-edits-supplier']) || $permission == 'superadmin') {
            $lists = [];
            $breadcum = [$title => route($this->pmodule . '.supplier_unverified_edit_list'), 'Listing' => ''];
            $records = DB::table('wa_supplier_logs')->where('edit_status', 1)->join('users', 'wa_supplier_logs.user_id', '=', 'users.id')->select(
                'wa_supplier_logs.name',
                'wa_supplier_logs.supplier_code',
                'wa_supplier_logs.created_at',
                'wa_supplier_logs.address',
                'users.name as requester',
            )->get();

            return view('admin.maintainsuppliers.supplier_unverified_edit', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'records'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function supplier_unverified_show_list(Request $request, $id)
    {


        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___approve-edits-supplier']) || $permission == 'superadmin') {
            $lists = [];
            $breadcum = [$title => route($this->pmodule . '.supplier_unverified_edit_list'), 'Listing' => ''];
            $records = DB::table('wa_supplier_logs')->where('supplier_code', $id)

                /*->join('wa_suppliers', 'wa_supplier_logs.supplier_code','!=', 'wa_suppliers.supplier_code')*/
                ->where('edit_status', 1)
                ->leftjoin('wa_payment_terms', 'wa_supplier_logs.wa_payment_term_id', 'wa_payment_terms.id')
                ->leftjoin('wa_currency_managers', 'wa_supplier_logs.wa_currency_manager_id', 'wa_currency_managers.id')
                // ->leftJoin('wa_supplier_distributors','wa_supplier_logs.supplier_code','wa_supplier_distributors.supplier_id')

                ->first();



            $distributors = DB::table('wa_supplier_distributors')->leftjoin('wa_suppliers', 'wa_supplier_distributors.distributors', '=', 'wa_suppliers.id')->select('wa_suppliers.name as suppliername')->where('supplier_id', $id)->get();
            /*  dd($distributors );*/






            $originalrecords = DB::table('wa_suppliers')->where('supplier_code', $id)
                ->leftjoin('wa_currency_managers', 'wa_suppliers.wa_currency_manager_id', 'wa_currency_managers.id')
                ->leftjoin('wa_payment_terms', 'wa_suppliers.wa_payment_term_id', 'wa_payment_terms.id')
                ->first();

            $origindistributors = DB::table('wa_supplier_distributors')->leftjoin('wa_suppliers', 'wa_supplier_distributors.distributors', '=', 'wa_suppliers.id')->select('wa_suppliers.name as suppliername')->where('status', '1')->where('supplier_id', $id)->get();



            return view('admin.maintainsuppliers.show_edits', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'records', 'originalrecords', 'distributors', 'origindistributors'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function datatable(Request $request)
    {
        $transSub = WaSuppTran::query()
            ->select([
                'supplier_no',
                DB::raw('SUM(total_amount_inc_vat) as balance'),
            ])
            ->groupBy('supplier_no');

        $query = WaSupplier::query()
            ->select([
                'wa_suppliers.id',
                'wa_suppliers.supplier_code',
                'wa_suppliers.name',
                'wa_suppliers.address',
                'wa_suppliers.email',
                'wa_suppliers.tax_withhold',
                'wa_suppliers.transport',
                'wa_suppliers.is_verified',
                'transactions.balance',
            ])
            ->leftJoinSub($transSub, 'transactions', 'transactions.supplier_no', 'wa_suppliers.supplier_code')
            ->where('is_verified', $request->is_verified == 'no' ? false : true)
            ->when($request->filled('service'), function ($query) {
                $query->where('service_type', request()->service);
            });

        $user = getLoggeduserProfile();
        if (!can('can-view-all-suppliers', 'maintain-suppliers')) {
            $query->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        return DataTables::eloquent($query)
            ->editColumn('tax_withhold', function ($supplier) {
                return $supplier->tax_withhold ? 'Yes' : 'No';
            })
            ->editColumn('professional_withholding', function ($supplier) {
                return $supplier->professional_withholding ? 'Yes' : 'No';
            })
            ->editColumn('balance', function ($supplier) {
                return manageAmountFormat($supplier->balance);
            })
            ->addColumn('actions', function ($supplier) {
                return view('admin.maintainsuppliers.actions.supplier', ['supplier' => $supplier]);
            })
            ->with('total', function () use ($query) {
                return $query->sum('balance');
            })
            ->toJson();
    }

    public function notificationJoinSupplierPortal(Request $request)
    {
        try {
            $supplier = WaSupplier::findOrFail($request->supplier_id);

            // $api = new \App\Services\ApiService(env('SUPPLIER_PORTAL_URI'));
            // $a = $api->postRequest('/api/onboarding-check', [
            //     'supplier_code' => $supplier->supplier_code,
            //     'name' => $supplier->name,
            //     'email' => $supplier->email,
            //     'source' => env('SUPPLIER_SOURCE')
            // ]);
            $query = 'supplier_code=' . $supplier->supplier_code .
                '&name=' . $supplier->name .
                '&email=' . $supplier->email .
                '&source=' . env('SUPPLIER_SOURCE');

            $param = Crypt::encryptString($query);
            if (true) {
                // if (isset($a['result'])) {
                // if ($a['result'] == 1) {
                $mail = new SupplierNotification($supplier, env('SUPPLIER_PORTAL_URI') . '/onboarding/' . $param);
                // $mail = new SupplierNotification($supplier, $a['location']);
                $recipients = array_unique(array_merge(explode(',', $request->recipient), [$supplier->email]));
                $copy = $request->filled('cc') ? explode(',', $request->cc) : [];
                Mail::to($recipients)
                    ->cc($copy)
                    ->send($mail);
                Session::flash('success', 'Mail Sent Successfully');
                // }
                // if ($a['result'] == 0) {
                //     return redirect()->back()->withErrors($a['errors']);
                // }
                // if ($a['result'] == -1) {
                //     throw new \Exception($a['message']);
                // }
            } else {
                throw new \Exception("Error Processing Request");
            }
        } catch (\Throwable $th) {
            Session::flash('warning', $th->getMessage());
        }
        return redirect()->back();
    }

    public function tradeAgreementList($supplier_code)
    {
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___trade-agreement-view']) || $permission == 'superadmin') {
            $title = 'Trade Agreement ' . $this->title;
            $model = $this->model;
            $supplier = WaSupplier::where('supplier_code', $supplier_code)->first();
            $supplier_item = \App\Model\WaInventoryItemSupplierData::with(['inventory_item', 'inventory_item.getAllFromStockMoves'])
                ->where('wa_supplier_id', $supplier->id)
                ->get()->filter(function ($record) {
                    $packSize = DB::table('pack_sizes')->where('id', $record->inventory_item?->pack_size_id)->first();
                    return ($packSize?->can_order ?? 0) == 1;
                });

            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
            $rebateTypes = [
                [
                    'label' => 'Per Unit',
                    'value' => 'per_unit'
                ],
                [
                    'label' => '% Of Invoice Amount',
                    'value' => 'invoice_amount_per'
                ],
                [
                    'label' => 'Tonnage',
                    'value' => 'tonnage'
                ],
                [
                    'label' => 'Per Location',
                    'value' => 'per_location'
                ]
            ];

            $discounts = [
                [
                    'label' => 'Base Discount',
                    'key' => 'base_discount',
                    'type' => 'percentage',
                    'target' => 'Product',
                    'stage' => 'LPO Processing',
                    'period' => '-',
                    'value' => '1%',
                    'banded' => false,
                ],
                [
                    'label' => 'Invoice Discount',
                    'key' => 'invoice_discount',
                    'type' => 'percentage',
                    'target' => 'Invoice',
                    'stage' => 'LPO Processing',
                    'period' => '-',
                    'value' => '2%',
                    'banded' => false,
                ],
                [
                    'label' => 'Purchase Qty Offer',
                    'key' => 'purchase_qty_offer',
                    'type' => 'value',
                    'target' => 'Product',
                    'stage' => 'LPO Processing',
                    'period' => '-',
                    'value' => '20 Get 1',
                    'banded' => false,
                ],
                [
                    'label' => 'Payment Discount',
                    'key' => 'payment_discount',
                    'type' => 'percentage',
                    'target' => 'Invoice',
                    'stage' => 'Invoice Payment',
                    'period' => '-',
                    'banded' => true,
                ],
                [
                    'label' => 'Quantity Discount',
                    'key' => 'payment_discount',
                    'type' => 'value',
                    'target' => 'Product',
                    'stage' => 'Invoice Payment',
                    'period' => '-',
                    'banded' => false,
                    'value' => '60.00'
                ],
                [
                    'label' => 'End Month Discount',
                    'key' => 'end_month_discount',
                    'type' => 'percentage',
                    'target' => 'Invoice',
                    'stage' => 'End Month Routine',
                    'period' => 'Monthly',
                    'value' => '4%',
                    'banded' => false,
                ],
                [
                    'label' => 'Quarterly Discount',
                    'key' => 'quartely_discount',
                    'type' => 'percentage',
                    'target' => 'Invoice',
                    'stage' => 'Quarterly Routine',
                    'period' => 'Quarterly',
                    'value' => '2%',
                    'banded' => false,
                ],
                [
                    'label' => 'Target Discount On Qty',
                    'key' => 'target_discount_on_qty',
                    'type' => 'percentage',
                    'target' => 'Product',
                    'stage' => 'End Month Routine',
                    'period' => 'Monthly',
                    'banded' => true,
                ],
                [
                    'label' => 'Target Discount On Value',
                    'key' => 'target_discount_on_value',
                    'type' => 'percentage',
                    'target' => 'Product',
                    'stage' => 'End Month Routine',
                    'period' => 'Monthly',
                    'banded' => true,
                ],
                [
                    'label' => 'Target Discount On Total Value',
                    'key' => 'target_discount_on_total_value',
                    'type' => 'percentage',
                    'target' => 'Invoice',
                    'stage' => 'End Month Routine',
                    'period' => 'Monthly',
                    'banded' => true,
                ],
                [
                    'label' => 'Transport Rebate',
                    'key' => 'transport_rebate',
                    'type' => 'both',
                    'target' => 'Product/Invoice',
                    'stage' => 'GRN Processing',
                    'period' => '-',
                    'banded' => true,
                ],
            ];

            $stores = WaLocationAndStore::select('id', 'location_name')->get();
            $terms = WaPaymentTerm::all();
            return view('admin.maintainsuppliers.tradeAgreementList', compact('title', 'model', 'breadcum', 'supplier_item', 'supplier', 'rebateTypes', 'stores', 'discounts', 'terms'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function tradeAgreementChangeRequestList(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___trade-agreement-change-request-list']) || $permission == 'superadmin') {
            $assigned = WaUserSupplier::where('user_id', getLoggeduserProfile()->id)->pluck('wa_supplier_id');
            $supplierList = WaSupplier::orderBy('id', 'desc')->where(function ($e) use ($permission, $assigned) {
                if ($permission != "superadmin") {
                    $e->whereIn('id', $assigned);
                }
            })->get();
            $title = 'Trade Agreement ' . $this->title;
            $model = 'trade-agreement-change-request-list';
            $supplier_change_requests_items = WaInventoryItemSupplierDataApprovals::with(
                ['item_data.inventory_item', 'item_data.supplier', 'initiator', 'approver']
            )->whereHas('item_data.supplier', function ($e) use ($request) {
                if ($request->supplier) {
                    $e->where('id', $request->supplier);
                }
            })->where('status', ($request->status ?? 'Pending'))->get();
            $breadcum = [$this->title => route($this->model . '.index'), 'Request' => ''];
            return view('admin.maintainsuppliers.tradeAgreementChangeRequestList', compact('title', 'model', 'breadcum', 'supplier_change_requests_items', 'supplierList'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function supplierRequestDataApprove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_id' => 'required|exists:wa_inventory_item_supplier_data,id',
            'approval_check' => 'required|in:Approve,Reject'
        ]);
        if ($validator->fails()) {
            return response()->json(['result' => 0, 'errors' => $validator->errors()]);
        }
        try {
            $check = DB::transaction(function ($e) use ($request) {
                $status = ['Approve' => 'Approved', 'Reject' => 'Rejected'];
                $supplier_data = WaInventoryItemSupplierDataApprovals::with(['item_data.inventory_item'])->where('status', 'Pending')->findOrFail($request->data_id);
                $supplier_data->status = $status[$request->approval_check] ?? $status['Reject'];
                if ($supplier_data->status == 'Approved') {
                    $supplier_data->item_data->price = $supplier_data->price;
                    $supplier_data->item_data->discount_amount = $supplier_data->discount_amount;
                    $supplier_data->item_data->transport_rebate_amount = $supplier_data->transport_rebate_amount;
                    $supplier_data->item_data->save();
                    $supplier_data->item_data->inventory_item->prev_standard_cost = $supplier_data->item_data->inventory_item->standard_cost;
                    $supplier_data->item_data->inventory_item->standard_cost = $supplier_data->price;
                    $supplier_data->item_data->inventory_item->save();
                }
                $supplier_data->approved_by = getLoggeduserProfile()->id;
                $supplier_data->approved_at = date('Y-m-d H:i:s');
                $supplier_data->save();

                return $supplier_data;
            });
            return response()->json(['result' => 1, 'message' => 'Supplier data change request has been ' . $check->status, 'location' => route($this->model . '.tradeAgreementChangeRequestList')]);
        } catch (\Throwable $th) {
            return response()->json(['result' => -1, 'message' => $th->getMessage()]);
        }
    }

    public function supplierDataChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_id' => 'required|exists:wa_inventory_item_supplier_data,id',
            'cost' => 'required|numeric',
            'discount_amount' => 'required|numeric',
            'transport_rebate_amount' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['result' => 0, 'errors' => $validator->errors()]);
        }
        try {
            $supplier_data = WaInventoryItemSupplierData::findOrFail($request->data_id);
            $new = new WaInventoryItemSupplierDataApprovals();
            $new->price = $request->cost;
            $new->discount_amount = $request->discount_amount;
            $new->transport_rebate_amount = $request->transport_rebate_amount;
            $new->status = 'Pending';
            $new->initiated_by = getLoggeduserProfile()->id;
            $new->initiated_at = date('Y-m-d H:i:s');
            $new->wa_supplier_data_id = $request->data_id;
            $new->save();

            return response()->json(['result' => 1, 'message' => 'Supplier data change request sent for approval!', 'location' => route($this->model . '.tradeAgreementList', $supplier_data->supplier->supplier_code)]);
        } catch (\Throwable $th) {
            return response()->json(['result' => -1, 'message' => $th->getMessage()]);
        }
    }

    public function supplier_unverified_process($id)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        if (isset($permission[$pmodule . '___approve-new-supplier']) || $permission == 'superadmin') {
            $supplier = WaSupplier::where('id', $id)->first();
            $supplier->is_verified = true;
            $supplier->save();
            return response()->json(['result' => 1, 'message' => 'Account verified successfully.', 'location' => route($this->model . '.supplier_unverified_list')]);
        } else {
            return response()->json(['result' => -1, 'message' => 'Something went wrong']);
        }
    }

    public function create()
    {
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___add']) || $permission == 'superadmin') {
            $title = 'Add ' . $this->title;
            $model = $this->model;
            $branches = Restaurant::pluck('name', 'id')->toArray();
            $users = User::where('role_id', '154')->get();

            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
            $googleMapsApiKey = config('app.google_maps_api_key');
            return view('admin.maintainsuppliers.create', compact('title', 'model', 'breadcum', 'branches', 'users', 'googleMapsApiKey'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function enterSupplierPayment($slug)
    {

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title . ' Payment';
        $model = $this->model;
        if (isset($permission[$pmodule . '___enter-supplier-payment']) || $permission == 'superadmin') {
            $supplier = WaSupplier::where('slug', $slug)->first();
            $supptrans = WaSuppTran::where('supplier_no', $slug)->where('settled', '0')->where('total_amount_inc_vat', '>', 0)->get();
            //echo "<pre>"; print_r($supptrans); die;
            $breadcum = [$title => route($model . '.index'), 'Supplier Payments' => route($model . '.enter-supplier-payment', $slug), $supplier->name => ''];

            return view('admin.maintainsuppliers.enter_supplier_payment', compact('title', 'supptrans', 'supplier', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function postSupplierPayment(Request $request, $slug)
    {
        // echo "<pre>"; print_r($request->all()); die;

        // if (isset($request->to_be_allocated) && !empty($request->to_be_allocated)) {
        //     $allocated_amount = 0;
        //     foreach ($request->to_be_allocated as $key => $suppallocated) {
        //         $supplier = WaSuppTran::where('id', $key)->where('settled', '0')->first();
        //         $allocated_amount += $suppallocated;
        //     }
        //     if($allocated_amount >= $request->amount){
        //         Session::flash('danger', 'All "To Be Allocated Amount" sum should not be greater than Amount.');
        //         return redirect()->back();
        //     }
        // }
        $bank_account = WaBankAccount::where('id', $request->wa_bank_account_id)->first();
        $accountuingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        $supplier = WaSupplier::where('slug', $slug)->first();
        $series_module = WaNumerSeriesCode::where('module', 'CREDITORS_PAYMENT')->first();
        $newSupplierTrans = new WaSuppTran();
        $document_no = getCodeWithNumberSeries('CREDITORS_PAYMENT');
        $request->document_no = $document_no;
        $newSupplierTrans->document_no = $document_no;
        $newSupplierTrans->total_amount_inc_vat = '-' . $request->amount;
        $newSupplierTrans->trans_date = $request->date_paid;
        $newSupplierTrans->suppreference = $request->cheque_number ? $request->cheque_number : null;
        $newSupplierTrans->supplier_no = $supplier->supplier_code;
        $newSupplierTrans->grn_type_number = $series_module->type_number;
        $newSupplierTrans->save();


        /** To be allocated amount code start **/
        $suppliers = WaSuppTran::where('settled', '0')->get();
        $request->trans = [];
        $suptrans = [];
        $amount = [];
        if (isset($request->to_be_allocated) && !empty($request->to_be_allocated)) {
            foreach ($request->to_be_allocated as $key => $suppallocated) {
                $request->trans[$key] = $key;
                $suppliertra = $suppliers->find($key);
                $suptrans[$key] = $suppliertra;
                $amount[$key] = $suppallocated;
                $allocated_amount = $suppliertra->allocated_amount + $suppallocated;
                if ($suppliertra->total_amount_inc_vat == $allocated_amount) {
                    $settaled = 1;
                } else {
                    $settaled = 0;
                }
                WaSuppTran::where('id', $key)->update(['allocated_amount' => $allocated_amount, 'settled' => $settaled]);
            }
        }
        /** To be allocated amount code end **/


        $cr = new WaGlTran();
        $cr->period_number = $accountuingPeriod ? $accountuingPeriod->period_no : null;
        $cr->wa_supp_tran_id = $newSupplierTrans->id;
        $cr->grn_type_number = $series_module->type_number;
        $dateTime = date('Y-m-d H:i:s');
        $cr->trans_date = $dateTime;
        $cr->restaurant_id = getLoggeduserProfile()->restaurant_id;
        $cr->grn_last_used_number = $series_module->last_number_used;
        $cr->transaction_type = $series_module->description;
        $cr->transaction_no = $document_no;
        $cr->narrative = $request->narrative;
        $cr->account = $bank_account->getGlDetail->account_code;
        $cr->amount = '-' . $request->amount;
        $cr->save();


        $dr = new WaGlTran();
        $dr->period_number = $accountuingPeriod ? $accountuingPeriod->period_no : null;
        $dr->wa_supp_tran_id = $newSupplierTrans->id;
        $dr->grn_type_number = $series_module->type_number;
        $dr->trans_date = $dateTime;
        $dr->grn_last_used_number = $series_module->last_number_used;
        $dr->transaction_type = $series_module->description;
        $dr->transaction_no = $document_no;
        $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;
        $dr->narrative = $request->narrative;
        $companyPreference = WaCompanyPreference::where('id', '1')->first();
        $dr->account = $companyPreference->creditorControlGlAccount->account_code;
        $dr->amount = $request->amount;
        $dr->save();

        $btran = new WaBanktran();
        $btran->type_number = $series_module->type_number;
        $btran->document_no = $document_no;
        $btran->bank_gl_account_code = $bank_account->getGlDetail->account_code;
        $btran->reference = $request->narrative . $request->cheque_number ? " + " . $request->cheque_number : '';
        $btran->trans_date = $dateTime;
        $btran->wa_payment_method_id = $request->payment_type_id;
        $btran->amount = '-' . $request->amount;
        $btran->wa_curreny_id = $request->wa_currency_manager_id;
        $btran->save();


        updateUniqueNumberSeries('CREDITORS_PAYMENT', $document_no);
        // Session::flash('success', 'Amount Paid successfully.');
        // return redirect()->back();
        //  $suptrans[$key] = $suppliertra;
        //         $amount[$key] = $s
        $request->slug = $slug;
        $lists = WaSuppTran::with(['getNumberSystem'])->whereIn('id', $request->trans)->orderBy('trans_date', 'desc')->get();
        $supplier = WaSupplier::where('slug', $slug)->first();
        $pdf = PDF::loadView('admin.maintainsuppliers.supplier_payment_Remittance_Advice_slip', compact('lists', 'supplier', 'request', 'bank_account', 'suptrans', 'amount'));
        return $pdf->download('remitence-' . time() . '.pdf');
    }

    public function remittanceAdvice($slug)
    {

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___remittance-advice']) || $permission == 'superadmin') {
            $supplier = WaSupplier::where('slug', $slug)->first();
            $breadcum = [$title => route($model . '.index'), 'Remittance Advice' => route($model . '.remittance-advice', $slug), $supplier->name => ''];
            $number_series_list = \App\Model\WaNumerSeriesCode::getNumberSeriesTypeList();
            $lists = WaSuppTran::where('supplier_no', $supplier->supplier_code)
                ->where('total_amount_inc_vat', '>', 0)
                ->where(function ($e) {
                    $e->orWhere('document_no', 'LIKE', '%GRN%');
                    $e->orWhere('document_no', 'LIKE', '%JV%');
                })
                ->where('settled', '0')
                ->orderBy('trans_date', 'desc')->get();
            return view('admin.maintainsuppliers.remitanceadvice', compact('title', 'supplier', 'model', 'breadcum', 'pmodule', 'permission', 'lists', 'number_series_list'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function getpaymentsummaryByreceiptId($supplierTransID)
    {

        $row = WaSuppTran::where('id', $supplierTransID)->first();
        $supplier = WaSupplier::where('supplier_code', $row->supplier_no)->first();
        return view('admin.maintainsuppliers.spilt_popup', compact('supplierTransID', 'row', 'supplier'));
    }

    public function postSplittedAmount(Request $request, $supplierTransID)
    {
        $originalRow = WaSuppTran::where('id', $supplierTransID)->first();
        $supplier = WaSupplier::where('supplier_code', $originalRow->supplier_no)->first();
        if ($originalRow->total_amount_inc_vat == $request->amount) {
            Session::flash('warning', 'Do not have any changes');
            return redirect()->route('maintain-suppliers.remittance-advice', $supplier->slug);
        } else {
            $copyRow = new WaSuppTran();
            $copyRow->wa_purchase_order_id = $originalRow->wa_purchase_order_id;
            $copyRow->grn_type_number = $originalRow->grn_type_number;
            $copyRow->supplier_no = $originalRow->supplier_no;
            $copyRow->suppreference = $originalRow->suppreference;
            $copyRow->trans_date = $originalRow->trans_date;
            $copyRow->due_date = $originalRow->due_date;
            $copyRow->settled = $originalRow->settled;
            $copyRow->rate = $originalRow->rate;
            $copyRow->document_no = $originalRow->document_no;
            $copyRow->description = $request->description;
            $copyRow->total_amount_inc_vat = $request->amount;
            $originaVatAmount = $originalRow->vat_amount;
            $originaAmount = $originalRow->total_amount_inc_vat;
            $splitted_amount = $request->amount;
            if ($originaVatAmount > 0) {
                $splittedVat = ($originaVatAmount * $splitted_amount) / $originaAmount;
                $originalRow->vat_amount = $originalRow->vat_amount - $splittedVat;
                $copyRow->vat_amount = $splittedVat;
            }
            $originalRow->total_amount_inc_vat = $originalRow->total_amount_inc_vat - $request->amount;
            $originalRow->save();
            $copyRow->save();

            Session::flash('success', 'Amount solitted successfully.');
            return redirect()->route('maintain-suppliers.remittance-advice', $supplier->slug);
        }
    }

    public function printRemittanceAdvice(Request $request)
    {
        //dd($request);
        if (isset($request->trans) && count($request->trans) > 0) {
            $selected_fields = [];
            foreach ($request->trans as $id => $data) {
                if ($data == 'F') {
                    $selected_fields[] = $id;
                }
            }
            if (count($selected_fields) > 0) {
                $lists = WaSuppTran::whereIn('id', $selected_fields)->orderBy('trans_date', 'desc')->get();
                $supplier = WaSupplier::where('slug', $request->slug)->first();
                $pdf = PDF::loadView('admin.maintainsuppliers.remitencereport', compact('lists', 'supplier'));
                return $pdf->download('remitence.pdf');
            } else {
                Session::flash('warning', 'Please select some data');
                return redirect()->back();
            }
        } else {
            Session::flash('warning', 'Please select some data');
            return redirect()->back();
        }
    }

    public function validate_first_step(Request $request, $id = "")
    {
        $validator = Validator::make($request->all(), [
            'supplier_code' => 'required|unique:wa_suppliers,supplier_code' . $id,
            'name' => 'required|string|unique:wa_suppliers,name' . $id,
            'address' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:255',
            'facsimile' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:wa_suppliers,email' . $id,
            'url' => 'nullable|string|max:255'
        ]);
        return !$validator->fails() ? false : $validator->errors();
    }

    public function validate_second_step(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_type' => 'nullable|in:default,others',
            'supplier_since' => 'nullable|string',
            'bank_reference' => 'nullable|string|max:255',
            'wa_payment_term_id' => 'nullable',
            'wa_currency_manager_id' => 'nullable',
            'remittance_advice' => 'nullable|in:not required,required',
            'tax_group' => 'nullable|string|max:255'
        ]);
        return !$validator->fails() ? false : $validator->errors();
    }

    public function validate_third_step(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|in:goods,services',
            'tax_withhold' => 'nullable|in:1',
            'transport' => 'required|in:Own Collection,Delivery',
            'purchase_order_blocked' => 'nullable',
            'payments_blocked' => 'nullable',
            'blocked_note' => 'nullable',
            'bank_branch' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'bank_swift' => 'nullable|string|max:255',
            'bank_cheque_payee' => 'nullable|string|max:255'
        ]);
        return !$validator->fails() ? false : $validator->errors();
    }

    public function _save_supplier(WaSupplier $row, Request $request)
    {


        $row->supplier_code = $request->supplier_code;
        $row->name = $request->name;
        $row->address = $request->address;
        $row->country = $request->country;
        $row->telephone = $request->telephone;
        $row->facsimile = $request->facsimile;
        $row->email = $request->email;
        $row->url = $request->url;
        $row->supplier_type = $request->supplier_type;
        $row->supplier_since = $request->supplier_since;
        $row->bank_reference = $request->bank_reference;
        $row->wa_payment_term_id = $request->wa_payment_term_id;
        $row->wa_currency_manager_id = $request->wa_currency_manager_id;
        $row->remittance_advice = $request->remittance_advice;
        $row->tax_group = $request->tax_group;
        $row->service_type = $request->service_type;
        $row->tax_withhold = $request->tax_withhold ?? false;
        $row->kra_pin = $request->kra_pin;
        $row->transport = $request->transport;
        $row->purchase_order_blocked = $request->purchase_order_blocked ?? false;
        $row->payments_blocked = $request->payments_blocked ?? false;
        $row->blocked_note = $request->blocked_note;
        $row->bank_branch = $request->bank_branch;
        $row->bank_account_no = $request->bank_account_no;
        $row->bank_name = $request->bank_name;
        $row->bank_swift = $request->bank_swift;
        $row->bank_cheque_payee = $request->bank_cheque_payee;
        /*$row->edit_status = true;*/
        $row->procument_user = $request->procument_user;
        $row->credit_limit = $request->credit_limit ?? 0;
        $row->monthly_target = $request->monthly_target ?? 0;
        $row->quarterly_target = $request->quarterly_target ?? 0;
        $row->save();
        return true;
    }





    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['current_step' => "required|in:1,2,3"]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            } else {
                if ($request->current_step == 1 && $st_first = $this->validate_first_step($request)) {
                    return response()->json(['result' => 0, 'errors' => $st_first]);
                }
                if ($request->current_step == 2 && $st_sec = $this->validate_second_step($request)) {
                    return response()->json(['result' => 0, 'errors' => $st_sec]);
                }
                if ($request->current_step == 3 && $st_third = $this->validate_third_step($request)) {
                    return response()->json(['result' => 0, 'errors' => $st_third]);
                }
                if ($request->current_step != 3) {
                    return response()->json(['result' => 1, 'next_step' => $request->current_step + 1]);
                }
                $row = new WaSupplier();
                if (!$this->_save_supplier($row, $request)) {
                    return response()->json(['result' => -1, 'message' => 'Something went wrong']);
                }
                updateUniqueNumberSeries('SUPPLIER', $request->supplier_code);
                return response()->json(['result' => 1, 'message' => 'Record added successfully.', 'location' => route($this->model . '.index')]);
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }

    public function _save_editsupplier(WaSupplier $row, Request $request)
    {


        $row->supplier_code = $request->supplier_code;
        $row->name = $request->name;
        $row->address = $request->address;
        $row->country = $request->country;
        $row->telephone = $request->telephone;
        $row->facsimile = $request->facsimile;
        $row->email = $request->email;
        $row->url = $request->url;
        $row->supplier_type = $request->supplier_type;
        $row->supplier_since = $request->supplier_since;
        $row->bank_reference = $request->bank_reference;
        $row->wa_payment_term_id = $request->wa_payment_term_id;
        $row->wa_currency_manager_id = $request->wa_currency_manager_id;
        $row->remittance_advice = $request->remittance_advice;
        $row->tax_group = $request->tax_group;
        $row->service_type = $request->service_type;
        $row->tax_withhold = $request->tax_withhold ?? false;
        $row->kra_pin = $request->kra_pin;
        $row->transport = $request->transport;
        $row->purchase_order_blocked = $request->purchase_order_blocked ?? false;
        $row->payments_blocked = $request->payments_blocked ?? false;
        $row->blocked_note = $request->blocked_note;
        $row->bank_branch = $request->bank_branch;
        $row->bank_account_no = $request->bank_account_no;
        $row->bank_name = $request->bank_name;
        $row->bank_swift = $request->bank_swift;
        $row->bank_cheque_payee = $request->bank_cheque_payee;
        $row->is_verified = true;
        $row->procument_user = $request->procument_user;


        $row->save();
        return true;
    }


    public function saveunverifiedsupplier(WaSupplier $row2, Request $request)
    {




        $row = WaSupplier::where('supplier_code', $request->supplier_code)->first();

        $log = $request;

        if ($row) {

            $row->supplier_code = $log->supplier_code;
            $row->name = $log->name;
            $row->address = $log->address;
            $row->country = $log->country;
            $row->telephone = $log->telephone;
            $row->facsimile = $log->facsimile;
            $row->email = $log->email;
            $row->url = $log->url;
            $row->supplier_type = $log->supplier_type;
            $row->supplier_since = $log->supplier_since;
            $row->bank_reference = $log->bank_reference;
            $row->wa_payment_term_id = $log->wa_payment_term_id;
            $row->wa_currency_manager_id = $log->wa_currency_manager_id;
            $row->remittance_advice = $log->remittance_advice;
            $row->tax_group = $log->tax_group;
            $row->service_type = $log->service_type;
            $row->tax_withhold = $log->tax_withhold ?? false;
            $row->kra_pin = $log->kra_pin;
            $row->transport = $log->transport;
            $row->purchase_order_blocked = $log->purchase_order_blocked ?? false;
            $row->payments_blocked = $log->payments_blocked ?? false;
            $row->blocked_note = $log->blocked_note;
            $row->bank_branch = $log->bank_branch;
            $row->bank_account_no = $log->bank_account_no;
            $row->bank_name = $log->bank_name;
            $row->bank_swift = $log->bank_swift;
            $row->bank_cheque_payee = $log->bank_cheque_payee;
            $row->is_verified = false;
            $row->procument_user = $request->procument_user;
            $row->save();
            //redirect()->route('maintain-suppliers.supplier_unverified_edit_list');

            return response()->json(['result' => 1, 'message' => 'Record updated successfully.', 'location' => route($this->model . '.supplier_unverified_list')]);
        }
    }










    public function show($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row = WaSupplier::with(['branches'])->whereId($slug)->first();
                if ($row) {
                    $title = 'View ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $branches = Restaurant::pluck('name', 'id')->toArray();

                    $model = $this->model;
                    return view('admin.maintainsuppliers.show', compact('title', 'model', 'breadcum', 'row', 'branches'));
                } else {
                    Session::flash('warning', 'Invalid Request');
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


    public function edit($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row = WaSupplier::with(['branches'])->whereSlug($slug)->first();
                if ($row) {
                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $branches = Restaurant::pluck('name', 'id')->toArray();
                    $supplier_item = \App\Model\WaInventoryItemSupplierData::with(['inventory_item'])->where('wa_supplier_id', $row->id)->get();
                    $distributors = WaSupplier::get();
                    $users = User::where('role_id', '154')->get();
                    $selectedSuppliers = WaSupplier::pluck('id')->toArray();
                    $distributorsDisplay = DB::table('wa_suppliers')
                        ->join('wa_supplier_distributors', 'wa_suppliers.supplier_code', '=', 'wa_supplier_distributors.supplier_id')
                        ->join('wa_suppliers as sub', 'wa_supplier_distributors.distributors', '=', 'sub.id')
                        ->where('wa_supplier_distributors.supplier_id', $slug)
                        ->where('wa_supplier_distributors.status', '1')
                        ->select('wa_suppliers.name as mainsupplier', 'sub.name as subsupplier', 'wa_supplier_distributors.id as keyId')
                        ->get();

                    $model = $this->model;
                    $googleMapsApiKey = config('app.google_maps_api_key');
                    return view('admin.maintainsuppliers.edit', compact('title', 'model', 'breadcum', 'row', 'branches', 'supplier_item', 'distributors', 'selectedSuppliers', 'distributorsDisplay', 'users', 'googleMapsApiKey'));
                } else {
                    Session::flash('warning', 'Invalid Request');
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

    public function _saveEdit_supplier(WaSupplier $row2, Request $request,)
    {


        $userid = getLoggeduserProfile();


        $row = new WaSupplierLog();
        $row->supplier_code = $request->supplier_code;
        $row->name = $request->name;
        $row->address = $request->address;
        $row->country = $request->country;
        $row->telephone = $request->telephone;
        $row->facsimile = $request->facsimile;
        $row->email = $request->email;
        $row->url = $request->url;
        $row->supplier_type = $request->supplier_type;
        $row->supplier_since = $request->supplier_since;
        $row->bank_reference = $request->bank_reference;
        $row->wa_payment_term_id = $request->wa_payment_term_id;
        $row->wa_currency_manager_id = $request->wa_currency_manager_id;
        $row->remittance_advice = $request->remittance_advice;
        $row->tax_group = $request->tax_group;
        $row->service_type = $request->service_type;
        $row->tax_withhold = $request->tax_withhold ?? false;
        $row->kra_pin = $request->kra_pin;
        $row->transport = $request->transport;
        $row->purchase_order_blocked = $request->purchase_order_blocked ?? false;
        $row->payments_blocked = $request->payments_blocked ?? false;
        $row->blocked_note = $request->blocked_note;
        $row->bank_branch = $request->bank_branch;
        $row->bank_account_no = $request->bank_account_no;
        $row->bank_name = $request->bank_name;
        $row->bank_swift = $request->bank_swift;
        $row->bank_cheque_payee = $request->bank_cheque_payee;
        $row->edit_status = true;
        $row->user_id = $userid->id;
        $row->procument_user = $request->procurement_user;
        $row->credit_limit = $request->credit_limit ?? 0;
        $row->monthly_target = $request->monthly_target ?? 0;
        $row->quarterly_target = $request->quarterly_target ?? 0;
        $row->save();

        $distributors = $request->distributors;

        if (!is_null($distributors)) {
            foreach ($distributors as $distributor) {
                $rowd = new WaSupplierDistributor();
                $rowd->supplier_id = $request->supplier_code;
                $rowd->distributors = $distributor;
                $rowd->status = false;
                $rowd->save();
            }
        }


        return true;
    }



    public function updatesupplier(WaSupplier $row2, $id)
    {
        try {


            $log = WaSupplierLog::where('supplier_code', $id)->first();
            $logs = WaSupplierDistributor::where('supplier_id', $id)->get();



            if ($log) {
                $row = WaSupplier::where('supplier_code', $id)->first();

                $row->supplier_code = $log->supplier_code;
                $row->name = $log->name;
                $row->address = $log->address;
                $row->country = $log->country;
                $row->telephone = $log->telephone;
                $row->facsimile = $log->facsimile;
                $row->email = $log->email;
                $row->url = $log->url;
                $row->supplier_type = $log->supplier_type;
                $row->supplier_since = $log->supplier_since;
                $row->bank_reference = $log->bank_reference;
                $row->wa_payment_term_id = $log->wa_payment_term_id;
                $row->wa_currency_manager_id = $log->wa_currency_manager_id;
                $row->remittance_advice = $log->remittance_advice;
                $row->tax_group = $log->tax_group;
                $row->service_type = $log->service_type;
                $row->tax_withhold = $log->tax_withhold ?? false;
                $row->kra_pin = $log->kra_pin;
                $row->transport = $log->transport;
                $row->purchase_order_blocked = $log->purchase_order_blocked ?? false;
                $row->payments_blocked = $log->payments_blocked ?? false;
                $row->blocked_note = $log->blocked_note;
                $row->bank_branch = $log->bank_branch;
                $row->bank_account_no = $log->bank_account_no;
                $row->bank_name = $log->bank_name;
                $row->bank_swift = $log->bank_swift;
                $row->bank_cheque_payee = $log->bank_cheque_payee;/*
                $row->edit_status = true;*/
                $row->procument_user = $log->procument_user;
                $row->credit_limit = $log->credit_limit ?? 0;
                $row->monthly_target = $log->monthly_target ?? 0;
                $row->quarterly_target = $log->quarterly_target ?? 0;

                $distributors = $logs;

                if (!is_null($distributors)) {
                    foreach ($distributors as $key => $distributor) {

                        $existingRows = WaSupplierDistributor::where('supplier_id', $distributor['supplier_id'])->get();

                        if ($existingRows->isNotEmpty()) {

                            WaSupplierDistributor::where('supplier_id', $distributor['supplier_id'])->update([
                                'status' => true,
                            ]);
                        }
                    }
                }

                $row->save();

                $log->delete();
            }

            Session::flash('success', 'Suppliers records updated successfully');
            return redirect()->route('maintain-suppliers.supplier_unverified_edit_list');
        } catch (\Throwable $e) {
            DB::rollBack();
            Session::flash('danger', $e->getMessage());
            return redirect()->route('maintain-suppliers.supplier_unverified_edit_list');
        }
    }

    public function rejectSupplierLog($id)


    {


        try {
            $logs = WaSupplierLog::where('supplier_code', $id)->first();



            if ($logs) {
                $logs->delete();
            }

            Session::flash('success', 'Suppliers edit rejected successfully');
            return redirect()->route('maintain-suppliers.supplier_unverified_edit_list');
        } catch (\Throwable $e) {
            DB::rollBack();
            Session::flash('danger', $e->getMessage());
            return redirect()->route('maintain-suppliers.supplier_unverified_edit_list');
        }
    }



    public function update(Request $request, $slug)
    {
        try {
            $validator = Validator::make($request->all(), ['current_step' => "required|in:1,2,3,4"]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            } else {
                $row = WaSupplier::whereSlug($slug)->first();


                if ($request->current_step == 1 && $st_first = $this->validate_first_step($request, ',' . $row->id)) {
                    return response()->json(['result' => 0, 'errors' => $st_first]);
                }
                if ($request->current_step == 2 && $st_sec = $this->validate_second_step($request)) {
                    return response()->json(['result' => 0, 'errors' => $st_sec]);
                }
                if ($request->current_step == 3 && $st_third = $this->validate_third_step($request)) {
                    return response()->json(['result' => 0, 'errors' => $st_third]);
                }
                if ($request->current_step != 4) {
                    return response()->json(['result' => 1, 'next_step' => $request->current_step + 1]);
                }
                if (!$this->_saveEdit_supplier($row, $request)) {
                    return response()->json(['result' => -1, 'message' => 'Something went wrong']);
                }

                return response()->json(['result' => 1, 'message' => 'Record sent for approval successfully.', 'location' => route($this->model . '.index')]);
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }


    public function destroy($id)
    {
        if (!can('delete', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }
        DB::beginTransaction();

        try {
            $supplier = WaSupplier::findOrFail($id);

            if ($supplier->grns()->exists() || $supplier->suppTrans()->exists()) {
                throw new Exception('This supplier cannot be deleted');
            }

            $supplier->products()->delete();
            $supplier->users()->delete();
            $supplier->delete();

            DB::commit();

            Session::flash('success', 'Deleted successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();

            Session::flash('warning', $e->getMessage());

            return redirect()->back();
        }
    }

    public function accountInquiry($supplier_code, Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $number_series_list = \App\Model\WaNumerSeriesCode::getNumberSeriesTypeList();
        $lists = WaSuppTran::where(function ($sub) use ($request) {
            if ($request->from_date && $request->to_date) {
                $sub->whereBetween('trans_date', [$request->from_date, $request->to_date]);
            }
        })->where('supplier_no', $supplier_code)->orderBy('id', 'desc')->get();
        $breadcum = [$title => route($model . '.index'), 'Account Inquiry' => '', $supplier_code => ''];
        return view('admin.maintainsuppliers.accountinquiry', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'supplier_code', 'number_series_list'));
    }

    public function supplierStatement(Request $request)
    {
        $from = $request->filled('from') ? $request->from . ' 00:00:00' : now()->subDays(30)->format('Y-m-d 00:00:00');
        $to = $request->filled('to') ? $request->to . ' 23:59:59' : now()->format('Y-m-d 23:59:59');
        $code = $request->supplier_code;

        // If no supplier code provided, show selection form
        if (!$code) {
            $suppliers = WaSupplier::select('id', 'supplier_code', 'name')->orderBy('name')->get();
            return view('admin.maintainsuppliers.supplier_statement_form', [
                'title' => 'Supplier Statement',
                'model' => $this->model,
                'breadcum' => ['Suppliers' => route('maintain-suppliers.index'), 'Supplier Statement' => ''],
                'suppliers' => $suppliers,
                'from' => now()->subDays(30)->format('Y-m-d'),
                'to' => now()->format('Y-m-d')
            ]);
        }

        // Get opening balance first
        $openingBalance = WaSuppTran::query()
            ->where('supplier_no', $code)
            ->where('created_at', '<', $from)
            ->sum('total_amount_inc_vat');

        // Optimize query - remove slow correlated subquery
        $items = WaSuppTran::query()
            ->select(
                'id',
                'document_no',
                'trans_date',
                'suppreference',
                'description',
                'total_amount_inc_vat',
                'created_at'
            )
            ->selectRaw("CONCAT_WS('/', suppreference, description) as full_description")
            ->selectRaw("(CASE WHEN total_amount_inc_vat > 0 THEN total_amount_inc_vat ELSE 0 END) as debit")
            ->selectRaw("(CASE WHEN total_amount_inc_vat < 0 THEN ABS(total_amount_inc_vat) ELSE 0 END) as credit")
            ->where('supplier_no', $code)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance and memo in PHP (faster than N+1 queries)
        $runningBalance = $openingBalance;
        
        // Pre-load all related data to avoid N+1 queries
        $documentPrefixes = $items->pluck('document_no')->map(function($doc) {
            $pos = strrpos($doc, '-');
            return $pos !== false ? substr($doc, 0, $pos) : '';
        })->unique()->filter()->values();
        
        $modules = WaNumerSeriesCode::whereIn('code', $documentPrefixes)->get()->keyBy('code');
        $invoiceRefs = $items->pluck('suppreference')->filter()->unique()->values();
        $invoices = WaSupplierInvoice::whereIn('supplier_invoice_number', $invoiceRefs)->pluck('supplier_invoice_number');
        
        $items->transform(function ($item) use (&$runningBalance, $modules, $invoices) {
            // Calculate running balance
            $item->opening_balance = $runningBalance;
            $runningBalance += $item->total_amount_inc_vat;
            
            // Determine memo
            $pos = strrpos($item->document_no, '-');
            $prefix = $pos !== false ? substr($item->document_no, 0, $pos) : '';
            
            if ($invoices->contains($item->suppreference)) {
                $item->memo = 'INVOICE';
            } elseif ($prefix == 'FN') {
                $item->memo = 'FINANCIAL NOTE';
            } elseif (isset($modules[$prefix])) {
                $item->memo = strtoupper($modules[$prefix]->description);
            } else {
                $item->memo = '';
            }
            
            return $item;
        });

        $branch = Restaurant::find(10);
        $supplier = WaSupplier::where('supplier_code', $code)->first();

        $qr_code = QrCode::generate(
            $supplier->supplier_code . " - " . $supplier->name . " - "
        );

        // Increase timeout and memory for large PDFs
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');

        try {
            $pdf = PDF::loadView('admin.maintainsuppliers.supplier_statement_pdf', [
                'items' => $items,
                'openingBalance' => $openingBalance,
                'settings' => getAllSettings(),
                'supplier' => $supplier,
                'from' => Carbon::parse($from),
                'to' => Carbon::parse($to),
                'branch' => $branch,
                'qr_code' => $qr_code,
            ]);

            return $pdf->download('supplier_statement_' . $code . '_' . time() . '.pdf');
        } catch (\Exception $e) {
            \Log::error('Supplier Statement PDF Error', [
                'supplier' => $code,
                'error' => $e->getMessage(),
                'items_count' => $items->count()
            ]);
            
            return redirect()->back()->withErrors(['error' => 'Failed to generate PDF. Please try a shorter date range or contact support.']);
        }
    }


    public function supplierMovementGlEntries($purchase_order_id, $supplier_no)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $data = WaGlTran::where(function ($f) use ($purchase_order_id) {
            $f->orWhere('wa_purchase_order_id', $purchase_order_id);
            $f->orWhere('transaction_no', $purchase_order_id);
        })->orderBy('id', 'desc')->get();

        $negativeAMount = WaGlTran::where(function ($f) use ($purchase_order_id) {
            $f->orWhere('wa_purchase_order_id', $purchase_order_id);
            $f->orWhere('transaction_no', $purchase_order_id);
        })->where('amount', '<', '0')->sum('amount');
        $positiveAMount = WaGlTran::where(function ($f) use ($purchase_order_id) {
            $f->orWhere('wa_purchase_order_id', $purchase_order_id);
            $f->orWhere('transaction_no', $purchase_order_id);
        })->where('amount', '>', '0')->sum('amount');

        $breadcum = [$title => route($model . '.index'), 'Supplier Movement' => route($model . '.account-inquiry', $supplier_no), 'GL Entries' => ''];
        return view('admin.maintainsuppliers.gl_entries', compact('title', 'data', 'model', 'breadcum', 'pmodule', 'permission', 'negativeAMount', 'positiveAMount'));
    }


    public function supplier_invoiced_list(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        if (!isset($permission['suppliers-invoice___view']) && $permission != 'superadmin') {
            Session::flash('warning', 'Restricted: You dont have permissions');
            return redirect()->back();
        }
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $invoices = WaPurchaseOrder::with(['getrelatedEmployee', 'getBranch', 'getDepartment', 'getRelatedItem', 'getSupplier'])->orderBy('id', 'desc')->where('invoiced', 'Yes')->get();

        $breadcum = [$title => route($this->model . '.index'), 'Supplier Invoice' => ''];
        return view('admin.maintainsuppliers.supplier_invoiced_list', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'invoices'));
    }

    public function supplier_invoice(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        if (!isset($permission['suppliers-invoice___add']) && $permission != 'superadmin') {
            Session::flash('warning', 'Restricted: You dont have permissions');
            return redirect()->back();
        }

        $title = $this->title;
        $model = 'suppliers-invoice';
        $pmodule = $this->pmodule;

        $supplierList = DB::table('wa_suppliers')->get();
        $invoices = WaPurchaseOrder::select([
            'wa_purchase_orders.*',
            'wa_grns.grn_number',
            'wa_grns.delivery_date',
            'wa_grns.invoice_info',
            'wa_grns.supplier_invoice_no as supplier_invoice_no',
            'wa_grns.cu_invoice_number as cu_invoice_number',
            'wa_grns.is_printed as grn_is_printed'
        ])->with(['getrelatedEmployee', 'getBranch', 'getDepartment', 'getRelatedItem', 'getRelatedGrn', 'uom', 'getRelatedGlTran'])->where(function ($e) use ($request) {
            if ($request->supplier) {
                $e->where('wa_purchase_orders.wa_supplier_id', $request->supplier);
            }
            if ($request->store) {
                $e->where('wa_purchase_orders.wa_location_and_store_id', $request->store);
            }
        })->join('wa_grns', function ($e) {
            $e->on('wa_grns.wa_purchase_order_id', 'wa_purchase_orders.id');
        })->leftJoin('wa_supp_trans', function ($e) {
            $e->on('wa_grns.supplier_invoice_no', 'wa_supp_trans.suppreference');
        })->leftJoin('wa_supplier_invoices', function ($e) {
            $e->on('wa_supplier_invoices.cu_invoice_number', 'wa_supp_trans.cu_invoice_number');
        })->where('wa_purchase_orders.supplier_archived', 0)
            ->where('wa_purchase_orders.invoiced', 'No')
            ->whereNotNull('wa_grns.supplier_invoice_no')
            ->whereNull('wa_supp_trans.suppreference')
            ->whereNull('wa_supplier_invoices.grn_number')
            ->orderBy('wa_grns.id', 'desc')
            ->groupBy('wa_grns.grn_number')
            ->get();

        $grandTotal = 0;
        $breadcum = [$title => route($this->model . '.index'), 'Supplier Invoice' => ''];
        foreach ($invoices as $list) {
            $relatedGrns = WaGrn::where('wa_purchase_order_id', $list->id)->where('grn_number', $list->grn_number)->get();
            $total_amount = 0;
            $vat = 0;
            foreach ($relatedGrns as $grn) {
                // $total_amount += ($grn->qty_received * $grn->standart_cost_unit);
                $invoice_info = json_decode($grn->invoice_info);
                $total_amount += ((float)$invoice_info->order_price * (float)$invoice_info->qty);
                $vat += ((float)$invoice_info->order_price * (float)$invoice_info->qty) * ((float)$invoice_info->vat_rate / 100);
            }
            $list->total_amount = $total_amount;
            $list->vat = $vat;
            $grandTotal += $total_amount + $vat;
        }

        $invoices->grandTotal = $grandTotal;

        if ($request->download && $request->download == 'Excel') {
            $data = [];
            foreach ($invoices as $list) {
                $payload = [
                    'grn_number' => isset($list->grn_number) ? $list->grn_number : '',
                    'date_received' => (isset($list->delivery_date)) ? date('Y-m-d', strtotime($list->delivery_date)) : '---',
                    'order_no' => $list->purchase_no,
                    'received_by' => $list->getRelatedStockMoves->first()->getRelatedUser?->name ?? '-',
                    'supplier' => $list->getSupplier?->name,
                    'store_location' => $list->getStoreLocation?->location_name,
                    'bin_location' => $list->uom?->title,
                    'supplier_invoice_no' => $list->supplier_invoice_no,
                    'CU_invoice_no' => $list->cu_invoice_number,
                    'vat' => manageAmountFormat($list->vat),
                    'amount' => manageAmountFormat($list->total_amount),
                ];
                $data[] = $payload;
            }

            $export = new SupplierInvoiceExport(collect($data));
            $today = \Carbon\Carbon::now()->toDateString();
            return FacadesExcel::download($export, "pending_grns$today.xlsx");
        }

        return view('admin.maintainsuppliers.supplier_invoice', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'supplierList', 'invoices'));
    }


    public function supplier_invoice_order_details(Request $request)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (!isset($permission['suppliers-invoice___add']) && $permission != 'superadmin') {
                Session::flash('warning', 'Restricted: You dont have permissions');
                return redirect()->back();
            }
            $pmodule = $this->pmodule;
            $title = "Supplier Invoice";
            $model = $this->model;
            $supplierList = WaSupplier::where('id', $request->supplier)->firstOrFail();
            $invoices = WaPurchaseOrder::select([
                'wa_purchase_orders.*',
                'wa_grns.grn_number',
                'wa_grns.delivery_date',
                'wa_grns.invoice_info',
                'wa_grns.supplier_invoice_no as supplier_invoice_no',
                'wa_grns.cu_invoice_number as cu_invoice_number',
                'wa_grns.is_printed as grn_is_printed'
            ])->with([
                'getrelatedEmployee',
                'getBranch',
                'getDepartment',
                'getRelatedItem',
                'getRelatedItem.location',
                'getRelatedItem' => function ($query) use ($request) {
                    $query->join('wa_grns', 'wa_grns.item_code', '=', 'wa_purchase_order_items.item_no')
                        ->select('wa_purchase_order_items.*', 'wa_grns.qty_received', 'wa_grns.invoice_info')
                        ->where('wa_grns.grn_number', $request->grn);
                },
                'getRelatedItem.getInventoryItemDetail',
                'getStoreLocation'
            ])
                ->join('wa_grns', function ($e) {
                    $e->on('wa_grns.wa_purchase_order_id', 'wa_purchase_orders.id');
                })->where('wa_grns.grn_number', $request->grn)
                ->orderBy('wa_purchase_orders.id', 'desc')
                ->where('wa_purchase_orders.wa_supplier_id', $request->supplier)
                ->where('wa_purchase_orders.id', $request->order_id)
                ->firstOrFail();

            $wa_stock_moves = WaStockMove::with(['getRelatedUser', 'getLocationOfStore', 'getInventoryItemDetail'])->select([
                '*',
                \DB::RAW('
                (CASE WHEN grn_type_number = 4 THEN (SELECT wa_pos_cash_sales_items.selling_price FROM wa_pos_cash_sales_items where wa_pos_cash_sales_items.wa_pos_cash_sales_id = wa_stock_moves.wa_pos_cash_sales_id AND wa_stock_moves.wa_inventory_item_id = wa_pos_cash_sales_items.wa_inventory_item_id LIMIT 1)
                WHEN grn_type_number = 51 THEN (SELECT wa_internal_requisition_items.selling_price FROM wa_internal_requisition_items where wa_internal_requisition_items.wa_internal_requisition_id = wa_stock_moves.wa_internal_requisition_id AND wa_stock_moves.wa_inventory_item_id = wa_internal_requisition_items.wa_inventory_item_id LIMIT 1)
                ELSE selling_price END
                ) as selling_price
                ')
            ])->where(function ($w) use ($request) {
                $w->where('document_no', $request->grn);
            })->orderBy('id', 'asc')->get();

            $vatTaxes = TaxManager::query()
                ->select('id', 'tax_value')
                ->selectRaw('concat(title," (",tax_value,")") as text')
                ->get();

            $grn = WaGrn::select([
                DB::raw('SUM(IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.total_discount")) AS DECIMAL(18,2)), 0)) AS total_discount'),
                DB::raw('SUM((CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.order_price")) AS DECIMAL(18,2)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.qty")) AS DECIMAL(18,2)) - IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.total_discount")) AS DECIMAL(18,2)), 0)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.vat_rate")) AS DECIMAL(18,2)) / (100 + CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.vat_rate")) AS DECIMAL(18,2)))) AS vat_amount'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.order_price")) AS DECIMAL(18,2)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.qty")) AS DECIMAL(18,2)) - IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.total_discount")) AS DECIMAL(18,2)), 0)) AS total_amount'),
            ])
                ->where('grn_number', $request->grn)
                ->first();


            $breadcum = [$title => route($this->model . '.index'), 'Supplier Invoice' => route('pending-grns.index')];
            return view('admin.maintainsuppliers.supplier_invoice_order_details', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'supplierList', 'grn', 'invoices', 'wa_stock_moves', 'vatTaxes'));
        } catch (\Throwable $th) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function supplier_invoice_make_archive(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        if (!isset($permission['suppliers-invoice___add']) && $permission != 'superadmin') {
            return response()->json(['result' => -1, 'message' => 'Restricted: You dont have permissions']);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:wa_purchase_orders,id',
            'supplier' => 'required|exists:wa_suppliers,id'
        ], [], ['id' => 'Order ID']);
        if ($validator->fails()) {
            return response()->json(['result' => 0, 'errors' => $validator->errors()]);
        }
        $order = WaPurchaseOrder::where('id', $request->id)->where('supplier_archived', 0)->where('wa_supplier_id', $request->supplier)->where('invoiced', 'No')->first();
        if (!$order) {
            return response()->json(['result' => 0, 'errors' => ['supplier_archived_id_' . $request->id => ['Invalid Data']]]);
        }
        $order->supplier_archived = 1;
        $order->save();
        return response()->json(['result' => 1, 'message' => 'Invoice Archived successfully', 'location' => route('pending-grns.index', ['supplier' => $request->supplier])]);
    }

    public function supplier_invoice_process(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        if (!isset($permission['suppliers-invoice___add']) && $permission != 'superadmin') {
            return response()->json(['result' => -1, 'message' => 'Restricted: You dont have permissions']);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:wa_purchase_orders,id',
            'supplier' => 'required|exists:wa_suppliers,id',
            'supplier_invoice_number' => 'required|unique:wa_supplier_invoices,cu_invoice_number',
            'cu_invoice_number' => 'required|unique:wa_supplier_invoices,cu_invoice_number',
            'supplier_invoice_date' => 'required|date',
            'price.*' => 'required|numeric',
            'quantity.*' => 'required|numeric',
            //  'discount.*' => 'required',
            'grn_number' => ['required'] // ['required', new GrnMatchValidator],
        ], [], [
            'id' => 'Order ID',
            'price.*' => 'price',
            'quantity.*' => 'quantity',
            'discount.*' => 'discount',
        ]);
        if ($validator->fails()) {
            return $request->ajax() ? response()->json([
                'result' => 0,
                'errors' => $validator->errors()
            ]) : redirect()->back()->withInput()->withErrors($validator->errors());
        }
        $order = WaPurchaseOrder::with(['getRelatedItem', 'getSupplier', 'getSupplier.getPaymentTerm'])->where('id', $request->id)->where('wa_supplier_id', $request->supplier)->first();
        if (!$order) {
            return $request->ajax() ? response()->json([
                'result' => 0,
                'errors' => ['id' => ['Invalid Data']]
            ]) : redirect()->back()->with('danger', 'Invalid Data');
        }

        $invoice =  new WaSupplierInvoice();

        try {
            $check = DB::transaction(function () use ($order, $request, &$invoice) {
                $getLoggeduserProfile = getLoggeduserProfile();
                $grn_number = $request->supplier_invoice_number;
                $dateTime = $request->supplier_invoice_date;
                $WaAccountingPeriod = \App\Model\WaAccountingPeriod::where('is_current_period', '1')->first();
                $series_module = $SUPPLIER_INVOICE_NO_series_module = \App\Model\WaNumerSeriesCode::where('module', 'SUPPLIER_INVOICE_NO')->first();

                $total_cost_with_vat = 0;
                $vat_amount = 0;
                foreach ($order->getRelatedItem as $key => $value) {
                    if (!isset($request->price[$value->id])) {
                        continue;
                    }

                    $total = $request->total[$value->id];
                    $vat = $total - (($total * 100) / (isset($request->vat_rate[$value->id]) ? $request->vat_rate[$value->id] + 100 : $value->vat_rate + 100));
                    $vat_amount += $vat;
                    $total_cost_with_vat += $total;
                }
                $roundVat = 0; //fmod($total_cost_with_vat, 1); //0.25
                // if ($roundVat != 0) {
                //     if ($roundVat > '0.50') {
                //         $roundVat = '+' . round((1 - $roundVat), 2);
                //     } else {
                //         $roundVat = '-' . round($roundVat, 2);
                //     }
                //     $total_cost_with_vat += $roundVat;
                // }

                $grnDiscount = WaGrn::select([
                    DB::raw('SUM(IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.total_discount")) AS DECIMAL(18,2)), 0)) AS total_discount')
                ])
                    ->where('grn_number', $request->grn_number)
                    ->first();

                // NOTE: Amount being received are already discounted
                // $total_cost_with_vat -= $grnDiscount->total_discount;

                $suppTran = new \App\Model\WaSuppTran();
                $suppTran->grn_type_number = $SUPPLIER_INVOICE_NO_series_module->type_number;
                $suppTran->supplier_no = @$order->getSupplier->supplier_code;
                $suppTran->suppreference = $request->supplier_invoice_number;
                $suppTran->trans_date = $dateTime; //date('Y-m-d');
                $suppTran->document_no = $grn_number;
                $due_date_number = '1';

                if (isset($order->getSupplier) && $order->getSupplier->getPaymentTerm && $order->getSupplier->getPaymentTerm->due_after_given_month == '1') {
                    $due_date_number = @$order->getSupplier->getPaymentTerm->days_in_following_months;
                }
                $suppTran->due_date = date('Y-m-d', strtotime($suppTran->trans_date . ' + ' . $due_date_number . ' days'));
                $suppTran->settled = '0';
                $suppTran->rate = '1';
                $suppTran->round_off = $roundVat;
                $suppTran->total_amount_inc_vat = $total_cost_with_vat;
                $suppTran->vat_amount = $vat_amount;
                $suppTran->wa_purchase_order_id = $order->id;
                $suppTran->cu_invoice_number = $request->cu_invoice_number;
                $suppTran->prepared_by = $getLoggeduserProfile->id;
                $suppTran->save();

                $grn = WaGrn::where('grn_number', $request->grn_number)->firstOrFail();
                $grnItems = WaGrn::where('grn_number', $request->grn_number)->get();
                $grnItems->each->update([
                    'invoiced' => 1
                ]);

                $invoice = WaSupplierInvoice::create([
                    'wa_purchase_order_id' => $order->id,
                    'wa_supp_tran_id' => $suppTran->id,
                    'grn_number' => $grn->grn_number,
                    'grn_date' => $grn->created_at,
                    'supplier_invoice_date' => $dateTime,
                    'invoice_number' => getCodeWithNumberSeries('SUPPLIER_INVOICE_NO'),
                    'supplier_invoice_number' => $request->supplier_invoice_number,
                    'cu_invoice_number' => $suppTran->cu_invoice_number,
                    'supplier_id' => $order->getSupplier->id,
                    'prepared_by' => $suppTran->prepared_by,
                    'vat_amount' => $vat_amount,
                    'amount' => $total_cost_with_vat,
                ]);

                updateUniqueNumberSeries('SUPPLIER_INVOICE_NO', $invoice->invoice_number);

                foreach ($order->getRelatedItem as $key => $value) {
                    if (!isset($request->price[$value->id])) {
                        continue;
                    }

                    $total = $request->total[$value->id]; //($request->price[$value->id] * $request->quantity[$value->id]) - $request->discount[$value->id];
                    $vat = $total - (($total * 100) / ($request->vat_rate[$value->id] ? $request->vat_rate[$value->id] + 100 : $value->vat_rate + 100));

                    WaSupplierInvoiceItem::create([
                        'wa_supplier_invoice_id' => $invoice->id,
                        'code' => $value->getInventoryItemDetail->stock_id_code,
                        'description' => $value->getInventoryItemDetail->title,
                        'quantity' => $request->quantity[$value->id],
                        'standart_cost_unit' => $request->price[$value->id],
                        'vat_amount' => $vat,
                        'amount' => $total,
                    ]);
                }

                app(UpdateDeliveryDistributionDiscount::class)->update($invoice);

                $cr = new \App\Model\WaGlTran();
                // $cr->wa_supp_tran_id = $suppTran->id;
                $cr->grn_type_number = $series_module->type_number;
                $cr->transaction_type = $series_module->description;
                $cr->transaction_no = $grn_number;
                $cr->grn_last_used_number = $series_module->last_number_used;
                $cr->trans_date = $dateTime;
                $cr->restaurant_id = $getLoggeduserProfile->restaurant_id;
                $cr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                // $cr->supplier_account_number = @$order->getSupplier->supplier_code;
                $cr->account = @$order->getBranch->getAssociateCompany->good_receive->account_code;
                $cr->amount = $total_cost_with_vat;
                $cr->narrative = $order->purchase_no;
                $cr->wa_purchase_order_id = $order->id;
                $cr->reference = $request->supplier_invoice_number;
                $cr->save();
                $dr = new \App\Model\WaGlTran();
                $dr->wa_supp_tran_id = $suppTran->id;
                $dr->grn_type_number = $series_module->type_number;
                $dr->transaction_type = $series_module->description;
                $dr->transaction_no = $grn_number;
                $dr->grn_last_used_number = $series_module->last_number_used;
                $dr->trans_date = $dateTime;
                $dr->restaurant_id = $getLoggeduserProfile->restaurant_id;
                $dr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
                $dr->supplier_account_number = @$order->getSupplier->supplier_code;
                $dr->account = @$order->getBranch->getAssociateCompany->creditorControlGlAccount->account_code;
                $dr->amount = '-' . $total_cost_with_vat;
                $dr->narrative = $order->purchase_no;
                $dr->reference = $request->supplier_invoice_number;
                $dr->wa_purchase_order_id = $order->id;
                $dr->save();

                $balance = $order->getRelatedItem->sum('total_cost_with_vat') -
                    $order->grns->sum(function ($grn) {
                        $invoice_info = json_decode($grn->invoice_info);
                        return ((float)$invoice_info->order_price * (float)$invoice_info->qty) * ((float)$invoice_info->vat_rate / 100);
                    }) -
                    $order->invoices->sum('total_amount_inc_vat');

                $order->invoiced = $balance == 0 ? 'Yes' : 'No';
                $order->save();

                return true;
            });


            if ($check) {
                event(new SupplierInvoiceCreated($invoice));

                return $request->ajax() ? response()->json(['result' => 1, 'message' => 'Invoice Processed successfully', 'location' => route('pending-grns.index')]) : redirect()->back()->with('success', 'Invoice Processed successfully');
            }
            return $request->ajax() ? response()->json(['result' => -1, 'message' => 'Something went wrong']) : redirect()->back()->with('danger', 'Something went wrong');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            dd($e);
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }

    public function suppliersList()
    {
        $suppliers = WaSupplier::orderBy('name')->get();

        return response()->json($suppliers);
    }
}
