<?php

namespace App\Http\Controllers\Admin;

use App\Model\PaymentMethod;
use App\Models\CashDropTransaction;
use App\Models\CrcRecord;
use PDF;
use Carbon\Carbon;
use App\Model\Route;
use App\WaTenderEntry;
use App\Model\WaGlTran;
use App\Model\WaBanktran;
use App\Model\WaCustomer;
use App\Model\WaDebtorTran;
use Illuminate\Support\Str;
use App\Model\WaBankAccount;
use Illuminate\Http\Request;
use App\Model\DeliveryCentres;
use App\Model\WaRouteCustomer;
use Illuminate\Validation\Rule;
use App\Model\WaNumerSeriesCode;
use App\Model\WaAccountingPeriod;
use App\Model\WaLocationAndStore;

use App\Models\WaDebtorTranRecon;
use App\Jobs\GetShopRouteSections;
use App\Model\WaCompanyPreference;
use Illuminate\Support\Facades\DB;
use App\Jobs\GetShopRoutePolylines;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Queue;
use App\Imports\CustomerPaymentImport;
use App\Jobs\GetShopDistanceEstimates;
use App\Services\ExcelDownloadService;
use Illuminate\Support\Facades\Session;
use App\Exports\MaintainCustomersExport;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Model\Restaurant;

class CustomerController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'maintain-customers';
        $this->title = 'Customers';
        $this->pmodule = 'maintain-customers';
    }

    public function index(Request $request)
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        if ($request->disable_all) {
            DB::table('wa_customers')->update(['is_blocked' => '1']);
            Session::flash('success', 'All Customers are disabled successfully');

            return redirect()->route($this->model . '.index');
        }

        if ($request->enable_all) {
            DB::table('wa_customers')->update(['is_blocked' => '0']);
            Session::flash('success', 'All Customers are enabled successfully');

            return redirect()->route($this->model . '.index');
        }

        $query = WaCustomer::query()
            ->select([
                'wa_customers.*',
                'routes.route_name',
                DB::raw("(SELECT SUM(amount) FROM wa_debtor_trans AS trans WHERE trans.wa_customer_id = wa_customers.id) AS balance")
            ])
            ->join('routes', 'routes.id', 'wa_customers.route_id')
            ->when($request->filled('branch'), function ($query) use ($request) {
                $query->where('restaurant_id', $request->branch);
            });


        if ($request->download) {
            foreach ($query->orderBy('customer_code')->get() as $row) {
                $telephone = $row->telephone ? Str::startsWith($row->telephone, '+') ? '0' . Str::substr($row->telephone, 4) : $row->telephone : null;
                $arrays[] = [
                    'customer-code' => $row->customer_code,
                    'customer-name' => ucwords($row->customer_name),
                    'route' => $row->getRoute() ? $row->getRoute()->route_name : '-',
                    'telephone' => $telephone,
                    'equity-till' => $row->equity_till,
                    'kcb-till' => $row->kcb_till,
                    'is-blocked' => $row->is_blocked == 1 ? "Yes" : "No",
                    'amount' => $row->balance,
                ];
            }
            $export = new MaintainCustomersExport(collect($arrays));

            return Excel::download($export, 'customer-accounts' . date('Y-m-d-H-i-s') . '.xls');
        }

        if ($request->wantsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('is_blocked', function ($customer) {
                    return $customer->is_blocked ? 'Yes' : 'No';
                })
                ->editColumn('balance', function ($customer) {
                    return manageAmountFormat($customer->balance);
                })
                ->addColumn('actions', function ($customer) {
                    return view('admin.customers.actions', [
                        'customer' => $customer
                    ]);
                })
                ->with('total', function () use ($query) {
                    return $query->get()->sum('balance');
                })
                ->toJson();
        }

        $breadcum = [
            $this->title => route($this->model . '.index')
        ];

        return view('admin.customers.index', [
            'title' => $this->title,
            'model' => $this->model,
            'breadcum' => $breadcum,
            'branches' => Restaurant::get(),
            'user' => auth()->user(),
        ]);
    }

    public function create()
    {
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___add']) || $permission == 'superadmin') {
            $title = 'Add ' . $this->title;
            $model = $this->model;
            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];

            $routes = Route::select('id', 'slug', 'route_name')->get()->filter(function (Route $route) {
                if (!$route->is_physical_route) {
                    return true;
                }

                if (($route->slug == 'cash-sales') || ($route->slug == 'sales-invoice' || $route->slug == 'credit')) {
                    return true;
                }

                return !($route->getAssignedCustomerAccount());
            });
            $delivery_routes = Route::where('is_physical_route', true)->pluck('route_name', 'id');
            $paymentMethods = PaymentMethod::all();

            return view('admin.receiablescustomers.create', compact('title', 'model', 'breadcum', 'routes', 'paymentMethods', 'delivery_routes'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function store(Request $request)
    {

        try {
            $row = new WaCustomer();
            $row->customer_code = $request->customer_code;
            $row->customer_name = $request->customer_name;
            $row->address = $request->address;
            $row->country = $request->country;
            $row->route_id = $request->route_id ?? NULL;

            $row->is_invoice_customer = $request->is_invoice_customer ?? false;
            if ($row->is_invoice_customer ==  false) {
                $row->delivery_route_id = null;
                $row->kra_pin = null;
            } else {
                $row->delivery_route_id = $request->delivery_route_id ?? null;
                $row->kra_pin = $request->kra_pin ?? null;
            }
            $row->bussiness_name = $request->customer_name;

            $row->telephone = $request->telephone;
            $row->email = $request->email;
            $row->customer_since = $request->customer_since;
            $row->credit_limit = $request->credit_limit ?? 0;
            $row->return_limit = $request->return_limit ?? 0;
            $row->payment_term_id = $request->payment_term_id;
            $row->biller_number = $request->biller_number ?? NULL;
            $row->account_number = $request->account_number ?? NULL;
            $row->equity_till = $request->equity_till ?? NULL;
            $row->equity_payment_method_id = $request->equity_payment_method_id ?? NULL;
            $row->kcb_till = $request->kcb_till ?? NULL;
            $row->kcb_payment_method_id = $request->kcb_payment_method_id ?? NULL;
            $row->is_blocked = 0;
            $row->save();

            updateUniqueNumberSeries('CUSTOMERS', $request->customer_code);
            Session::flash('success', 'Record added successfully.');

            if ($row->is_dependent ==  true) {
                $new = new WaRouteCustomer;
                $new->created_by = Auth::id();
                $new->route_id = $request->delivery_route_id;
                $new->delivery_centres_id = $request->center_id;
                $new->customer_id = $row->id;
                $new->name = $request->customer_name;
                $new->phone = $request->telephone;
                $new->kra_pin = $request->kra_pin;
                $new->bussiness_name = $request->customer_name;
                $new->town = $request->location_name;
                $new->contact_person = $request->contact_person;
                $new->lat = $request->lat;
                $new->lng = $request->lng;
                $new->location_name = $request->location_name;
                $new->credit_customer_id = $row->id;
                $new->save();
            }
            return redirect()->route($this->model . '.index');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }

    public function saveRousteCustomer() {}


    public function show($id) {}


    public function edit($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row = WaCustomer::with('associatedRouteCustomer')->whereSlug($slug)->first();
                if ($row) {
                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;

                    $route = Route::select('id', 'route_name')->get()->filter(function (Route $route) use ($row) {
                        return (!$route->getAssignedCustomerAccount()) || ($route->slug == 'cash-sales') || ($route->slug == 'sales-invoice') || ($route->id == $row->route_id);
                    });
                    $delivery_routes = Route::where('is_physical_route', true)->pluck('route_name', 'id');
                    $paymentMethods = PaymentMethod::all();

                    return view('admin.receiablescustomers.edit', compact('title', 'model', 'breadcum', 'row', 'route', 'paymentMethods', 'delivery_routes'));
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


    public function update(Request $request, $slug)
    {
        try {
            $row = WaCustomer::whereSlug($slug)->first();
            $row->customer_name = $request->customer_name;
            $row->address = $request->address;
            $row->country = $request->country;
            $row->telephone = $request->telephone;
            $row->route_id = $request->route_id ?? NULL;
            $row->email = $request->email;
            // $row->delivery_centre_id= $request->center_id;
            $row->customer_since = $request->customer_since;
            $row->credit_limit = $request->credit_limit;
            $row->return_limit = $request->return_limit ?? 0;
            $row->payment_term_id = $request->payment_term_id;
            $row->is_blocked = $request->is_blocked ? 1 : 0;
            // $row->biller_number = $request->biller_number;
            // $row->account_number = $request->account_number;
            $row->equity_till = $request->equity_till;
            $row->equity_payment_method_id = $request->equity_payment_method_id ?? NULL;
            $row->kcb_payment_method_id = $request->kcb_payment_method_id ?? NULL;
            $row->kcb_till = $request->kcb_till;

            $row->is_invoice_customer = $request->is_invoice_customer ?? false;
            if ($row->is_invoice_customer ==  false) {
                $row->delivery_route_id = null;
                $row->kra_pin = null;
            } else {
                $row->kra_pin = $request->kra_pin ?? null;
            }
            $row->bussiness_name = $request->customer_name;

            $row->save();
            Session::flash('success', 'Record updated successfully.');
            return redirect()->route($this->model . '.index');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }

    public function printReceipts($slug)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $is_admin = 'yes';
        if (isset($permission[$pmodule . '___print-receipts']) || $permission == 'superadmin') {
            $customer = WaCustomer::where('slug', $slug)->first();
            $breadcum = [$title => route($model . '.index'), 'Print Receipts List' => route($model . '.print-receipts', $slug), $customer->customer_name => ''];
            $lists = WaDebtorTran::where('wa_customer_id', $customer->id)->where('type_number', '12');
            if ($permission != 'superadmin') {
                $lists = $lists->where('is_printed', '0');
                $is_admin = 'no';
            }

            $lists = $lists->orderBy('id', 'desc')->get();
            return view('admin.receiablescustomers.receiptlist', compact('title', 'customer', 'model', 'breadcum', 'pmodule', 'permission', 'lists', 'is_admin'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function allocateReceipts(Request $request, $slug)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        if (isset($permission[$pmodule . '___allocate-receipts']) || $permission == 'superadmin') {
            $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();
            $row = WaCustomer::whereSlug($slug)->first();
            if ($row) {
                $data = [];


                $allocation_type_arr = ['only-not-allocated' => '0', 'allocated-only' => '1'];

                if ($request->has('allocation-type')) {
                    $allocation_type = $request->input('allocation-type');
                    $data = WaDebtorTran::where('wa_customer_id', $row->id);

                    if (isset($allocation_type_arr[$allocation_type])) {
                        $data = $data->where('is_settled', $allocation_type_arr[$allocation_type]);
                    }
                    $data = $data->orderBy('type_number', 'desc')->get();
                }
                $title = $this->title;
                $breadcum = [$this->title => route($this->model . '.index'), 'Allocate Receipts' => ''];
                $model = $this->model;
                return view('admin.receiablescustomers.allocatereceipts', compact('title', 'model', 'breadcum', 'row', 'number_series_list', 'data', 'slug'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function printReceiptByid(Request $request)
    {
        $id = $request->receipt_id;
        $row = WaDebtorTran::where('id', $id)->first();
        $row->is_printed = '1';
        $row->save();
        $bank_tran = WaBanktran::where('document_no', $row->document_no)->first();
        $heading = 'Payment Receipt'; //heading;
        $printed_time = date('d/m/Y h:i A');
        $report_name = 'customer_receipts';
        return view('admin.receiablescustomers.reportinpdf', compact('heading', 'printed_time', 'row', 'bank_tran'));
    }


    public function enterCustomerPayment($slug)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title . ' Payment';
        $model = $this->model;
        if (isset($permission[$pmodule . '___enter-customer-payment']) || $permission == 'superadmin') {
            $customer = WaCustomer::where('slug', $slug)->first();
            $customertrans = WaDebtorTran::where('customer_number', $customer->customer_code)
                ->where('is_settled', '0')
                // ->where('document_no','LIKE', '%S-INV%')
                ->where('amount', '>', 0)
                ->orderBy('created_at', 'DESC')
                ->get();
            //            dd(PaymentMethod::all());
            $cash_methods = PaymentMethod::where('is_cash', true)->pluck('title', 'id')->toArray();
            //echo $customer->customer_code."<pre>"; print_r($customertrans); die;
            $breadcum = [$title => route($model . '.index'), 'Customer Payments' => route($model . '.enter-customer-payment', $slug), $customer->name => ''];
            return view('admin.receiablescustomers.enter_customer_payment', compact('title', 'customertrans', 'customer', 'model', 'breadcum', 'pmodule', 'permission', 'cash_methods'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function enterCustomerPayments($slug)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title . ' Payment';
        $model = $this->model;
        if (isset($permission[$pmodule . '___enter-customer-payment']) || $permission == 'superadmin') {
            $customer = WaCustomer::where('slug', $slug)->first();
            $breadcum = [$title => route($model . '.index'), 'Customer Payments' => route($model . '.enter-customer-payment-uploads', $slug), $customer->name => ''];
            return view('admin.receiablescustomers.enterCustomerPayments', compact('title', 'customer', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function postCustomerPayments(Request $request, $slug)
    {
        $request->validate([
            'upload_file' => 'required|mimes:xlsx,xls',
        ]);
        //dd($request);
        $bank_account = WaBankAccount::with(['getGlDetail'])->where('id', $request->wa_bank_account_id)->first();
        $customer = WaCustomer::whereSlug($slug)->first();
        $companyPreference = WaCompanyPreference::where('id', '1')->first();
        $accountuingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        $series_module = WaNumerSeriesCode::where('module', 'RECEIPT')->first();
        $document_no = getCodeWithNumberSeriesPOS('RECEIPT');
        $user = \App\Model\User::where('route', $customer->route_id)->first();
        $u = getLoggeduserProfile();
        Excel::import(new CustomerPaymentImport($bank_account, $customer, $companyPreference, $accountuingPeriod, $series_module, $document_no, $user, $request, $u), $request->file('upload_file'));
        updateUniqueNumberSeries('RECEIPT', $document_no);
        Session::flash('success', 'Payment received successfully');
        // Session::flash('debtrpayment', $debtorTran->id);
        return redirect()->back();
    }

    public function postCustomerPayment(Request $request, $slug)
    {
        // Validate required fields
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_type_id' => 'required|integer',
            'reference' => 'nullable|string|max:255',
            'narrative' => 'nullable|string|max:500',
            'paid_by' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $customer = WaCustomer::whereSlug($slug)->first();
            
            if (!$customer) {
                throw new \Exception('Customer not found.');
            }
            
            // Check if payment method exists
            $paymentMethod = PaymentMethod::find($request->payment_type_id);
            if (!$paymentMethod) {
                throw new \Exception('Invalid payment method selected.');
            }
            
            $accountuingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();

            // Try to find number series, first RECEIPT, then CHEQUE_REPLACE_BY_CASH as fallback
            $series_module = WaNumerSeriesCode::where('module', 'RECEIPT')->first();
            
            if (!$series_module) {
                $series_module = WaNumerSeriesCode::where('module', 'CHEQUE_REPLACE_BY_CASH')->first();
            }
            
            if (!$series_module) {
                // If no specific module found, try to get any available number series
                $series_module = WaNumerSeriesCode::first();
            }
            
            if (!$series_module) {
                throw new \Exception('No number series configuration found. Please contact administrator to set up number series.');
            }
            
            $lastNumberUsed = $series_module->last_number_used ?? 0;
            $newNumber = (int)$lastNumberUsed + 1;
            $series_module->update(['last_number_used' => $newNumber]);

            //        $series_module = WaNumerSeriesCode::where('module', 'RECEIPT')->first();
            $code = $series_module->code ?? 'RCP';
            $document_no = $code . '-' . str_pad($newNumber, 5, "0", STR_PAD_LEFT);



            $user = \App\Model\User::where('route', $customer->route_id)->first();
            $route = Route::find($customer->route_id);
            
            if (!$route) {
                throw new \Exception('Customer route not found. Please contact administrator.');
            }

            $debtorTran = new WaDebtorTran();
            $debtorTran->type_number = $series_module ? $series_module->type_number : '';
            $debtorTran->wa_customer_id = $customer->id;
            $debtorTran->customer_number = $customer->customer_code;
            $debtorTran->trans_date = date('Y-m-d');
            $debtorTran->input_date = date('Y-m-d H:i:s');
            $debtorTran->wa_accounting_period_id = $accountuingPeriod ? $accountuingPeriod->id : null;
            $debtorTran->amount = '-' . $request->amount;
            $debtorTran->document_no = $document_no;
            $debtorTran->wa_payment_method_id = $request->payment_type_id;
            $debtorTran->paid_by = $request->paid_by ?? getLoggeduserProfile()->name;
            $debtorTran->user_id = getLoggeduserProfile()->id;
            $debtorTran->reference = $request->reference . ' ' . $request->narrative;
            $debtorTran->salesman_id = @$user->wa_location_and_store_id;
            $debtorTran->salesman_user_id = @$user->id;
            $debtorTran->branch_id = $route->restaurant_id;
            $debtorTran->channel = 'CASH';
            $debtorTran->save();

            /*save crc record*/

            $crc = CrcRecord::create([
                'amount' => $request->amount,
                'reference' => $document_no,
                'user_id' => getLoggeduserProfile()->id,
                'branch_id' => $route->restaurant_id,
            ]);
            DB::commit();

            Session::flash('success', 'Payment received successfully');
            $crc_url = \route('maintain-customers.download-crc-receipt', $crc);
            return redirect()->route('customer-centre.show', $customer)->with('crc', $crc_url);
        } catch (\Throwable $exception) {
            DB::rollBack();
            Session::flash('warning', $exception->getMessage());
            return redirect()->back();
        }
    }

    public function downloadDropReceipt(Request $request, $id)
    {

        $crc = CrcRecord::with('user')->find($id);
        if ($request->ajax()) {
            return view('admin.cashierManagement.crc-pdf', compact('crc'));
        }

        $pdf = \PDF::loadView('admin.cashierManagement.crc-pdf', compact('crc'));
        return $pdf->download('CRC.pdf');
    }


    public function destroy($slug)
    {
        try {
            $row = WaCustomer::whereSlug($slug)->first();
            if (!$row || count($row->getAllDebtorsTrans) > 0) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            $row->delete();
            // WaCustomer::whereSlug($slug)->delete();

            Session::flash('success', 'Deleted successfully.');
            return redirect()->back();
        } catch (\Exception $e) {

            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function debtorTransDetail($slug, Request $request)
    {
        try {
            $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();
            $row = WaCustomer::whereSlug($slug)
                ->with(['getAllDebtorsTrans'])
                ->first();

            $row->getAllDebtorsTrans = $row->getAllDebtorsTrans()
                ->select([
                    'wa_debtor_trans.*',
                    DB::raw("COUNT(*) as count"),
                    DB::raw("SUM(wa_debtor_trans.amount) as total"),
                    DB::raw("(select channel from wa_tender_entries where wa_tender_entries.document_no = wa_debtor_trans.document_no limit 1) as channel")
                ])
                ->where('wa_debtor_trans.document_no', 'like', '%RCT%');
            if ($request && $request->posted) {
                $row->getAllDebtorsTrans = $row->getAllDebtorsTrans->where('wa_debtor_trans.type_number', '12');
            }

            $row->getAllDebtorsTrans = $row->getAllDebtorsTrans->where(function ($where1) use ($request) {
                if ($request && $request->date_from && $request->date_to) {
                    $where1->whereBetween('wa_debtor_trans.trans_date', [$request->date_from, $request->date_to]);
                } else {
                    $where1->where('wa_debtor_trans.trans_date', date('Y-m-d'));
                }
            });

            $row->getAllDebtorsTrans = $row->getAllDebtorsTrans->groupBy('wa_debtor_trans.document_no')->get();

            if ($request->intent == 'EXCEL') {
                $data = $row->getAllDebtorsTrans->map(function (WaDebtorTran $list) {
                    return [
                        'type' => $number_series_list[$list->type_number] ?? 'Receipt',
                        'date' => \Carbon\Carbon::parse($list->trans_date)->toDateString(),
                        'document_no' => $list->document_no,
                        'reference' => $list->reference,
                        'channel' => $list->channel,
                        'user' => \App\User::find($list->user_id)?->name ?? 'System',
                        'count' => $list->count,
                        'total' => $list->total,
                    ];
                });

                $headings = ['TRANSACTION TYPE', 'DATE', 'DOCUMENT_NO', 'REFERENCE', 'CHANNEL', 'USER', 'COUNT', 'TOTAL'];
                $filename = "Debtor_Trans_$slug";
                return ExcelDownloadService::download($filename, $data, $headings);
            }


            $title = $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Debtors Trans Inquiry' => ''];
            $model = $this->model;
            return view('admin.receiablescustomers.accountinquiry', compact('title', 'model', 'breadcum', 'row', 'number_series_list'));
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }

    public function debtorTransDetail2($slug, Request $request)
    {
        try {
            $permission = $this->mypermissionsforAModule();

            if (($request && $request->posted && isset($permission['sales-invoices___posted-receipt'])) || $permission == 'superadmin') {
                $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();
                $row = WaCustomer::whereSlug($slug)->with([
                    'getAllDebtorsTrans' => function ($query) use ($request) {
                        //                        if ($request && $request->posted) {
                        //                            $query->where('type_number', '12');
                        //                        }
                        //
                        //                        $startDate = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : Carbon::now()->startOfDay();
                        //                        $endDate = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : Carbon::now()->endOfDay();
                        //                        $query->whereBetween('trans_date', [$startDate, $endDate]);
                    }
                ])->first();

                if ($row) {
                    $title = $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Debtors Trans Inquiry 2' => ''];
                    $model = $this->model;
                    return view('admin.receiablescustomers.accountinquiry2', compact('title', 'model', 'breadcum', 'row', 'number_series_list'));
                }
            }

            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();
                $row = WaCustomer::whereSlug($slug)->with([
                    'getAllDebtorsTrans' => function ($where) use ($request) {
                        $where->where(function ($where1) use ($request) {
                            if ($request && $request->date_from && $request->date_to) {
                                $where1->whereBetween('trans_date', [$request->date_from, $request->date_to]);
                            } else {
                                $where1->where('trans_date', date('Y-m-d'));
                            }
                        });
                    }
                ])->first();
                // echo "<pre>";
                // print_r($row);
                // die;
                if ($row) {
                    $title = $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Debtors Trans Inquiry 2' => ''];
                    $model = $this->model;
                    return view('admin.receiablescustomers.accountinquiry2', compact('title', 'model', 'breadcum', 'row', 'number_series_list'));
                }
            }
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }

    public function debtorTransDetailLines($document_no, Request $request)
    {
        try {
            $number_series_list = WaNumerSeriesCode::getNumberSeriesTypeList();

            //////
            $lists = WaDebtorTran::where('document_no', $document_no)->get();

            if ($request->intent == 'EXCEL') {
                $data = $lists->map(function (WaDebtorTran $list) {
                    return [
                        'type' => $number_series_list[$list->type_number] ?? 'Receipt',
                        'date' => \Carbon\Carbon::parse($list->trans_date)->toDateString(),
                        'document_no' => $list->document_no,
                        'reference' => $list->reference,
                        'Amount' => $list->amount,
                    ];
                });

                $headings = ['TRANSACTION TYPE', 'DATE', 'DOCUMENT_NO', 'REFERENCE', 'AMOUNT'];
                $filename = "Debtor_Trans_Lines_$document_no";
                return ExcelDownloadService::download($filename, $data, $headings);
            }

            $title = $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Debtors Trans Inquiry Lines' => ''];
            $model = $this->model;
            return view('admin.receiablescustomers.debtor_trans_lines', compact('title', 'model', 'breadcum', 'lists', 'number_series_list'));
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }

    public function route_customer_list(Request $request, $id)
    {
        $startdate = Carbon::now()->format('Y-m-d');
        $enddate = Carbon::now()->format('Y-m-d');

        if (!empty($request->input('start-date'))) {
            $startdate = $request->input('start-date');
        } else if (!empty($request->input('end-date'))) {
            $enddate = $request->input('end-date');
        }


        $data['customer'] = WaCustomer::where('id', $id)->first();
        $data['title'] = 'Route Customers';
        $data['lists'] = WaRouteCustomer::with('center')->select(
            '*',
            DB::RAW(' (Select SUM(wa_debtor_trans.amount) from wa_debtor_trans where wa_debtor_trans.wa_route_customer_id=wa_route_customers.id AND (trans_date BETWEEN "' . $startdate . '" AND "' . $enddate . '")  group by wa_debtor_trans.wa_route_customer_id) as total_sales  ')
        )->where('route_id', $data['customer']->route_id);
        // if($startdate && $enddate){
        //     $data['lists']->where('created_at',">=",$startdate);
        //     $data['lists']->where('created_at',"<=",$enddate);
        // }
        $data['lists'] = $data['lists']->orderBy('phone')
            ->get()->map(function (WaRouteCustomer $routeCustomer) {
                $routeCustomer->display_status = ucfirst($routeCustomer->status);
                return $routeCustomer;
            });


        $data['model'] = $this->model;
        $data['customer_id'] = $id;
        $data['route'] = DB::table('routes')->where('id', $data['customer']->route_id)->select('id', 'route_name')->first();
        return view('admin.receiablescustomers.route_customer_list')->with($data);
    }

    public function routeCustomersByRouteId(Request $request, $route_id)
    {
        $startdate = $request->get('start-date');
        $enddate = $request->get('end-date');


        // $data['customer'] =  WaCustomer::where('id',$id)->first();
        $data['title'] = 'Route Customers';
        $data['lists'] = WaRouteCustomer::select(
            '*',
            DB::RAW(' (Select SUM(wa_debtor_trans.amount) from wa_debtor_trans where wa_debtor_trans.wa_route_customer_id=wa_route_customers.id AND (trans_date BETWEEN "' . $startdate . '" AND "' . $enddate . '")  group by wa_debtor_trans.wa_route_customer_id) as total_sales  ')
        )->where('route_id', $route_id);
        // if($startdate && $enddate){
        //     $data['lists']->where('created_at',">=",$startdate);
        //     $data['lists']->where('created_at',"<=",$enddate);
        // }


        $data['lists'] = $data['lists']->orderBy('id', 'DESC')
            ->get();
        $data['model'] = $this->model;
        $data['route_id'] = $route_id;
        return view('admin.receiablescustomers.route_customer_list')->with($data);
    }

    public function route_customer_add(Request $request, $id)
    {
        $data['customer'] = WaCustomer::where('id', $id)->first();
        $data['title'] = 'Add Route Customers';
        $data['model'] = $this->model;
        $centers = DeliveryCentres::where('route_id', $data['customer']->route_id)->get();


        return view('admin.receiablescustomers.route_customer_add')->with($data)->with('centers', $centers)
            ->with('google_maps_api_key', config('app.google_maps_api_key'));
    }


    public function route_customer_edit(Request $request, $id)
    {
        $data['customer'] = WaRouteCustomer::where('id', $id)->first();
        $data['title'] = 'Edit Route Customers';
        $data['model'] = $this->model;
        $centers = DeliveryCentres::where('route_id', $data['customer']->route_id)->get();
        return view('admin.receiablescustomers.route_customer_edit')->with($data)->with('centers', $centers)
            ->with('google_maps_api_key', config('app.google_maps_api_key'));
    }


    public function route_customer_delete(Request $request, $id)
    {
        $validations = Validator::make($request->all(), [
            'id' => 'required|exists:wa_route_customers,id',
        ]);
        if ($validations->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validations->errors()
            ]);
        }
        $new = WaRouteCustomer::where('id', $request->id)->first();
        $new->delete();
        return response()->json([
            'result' => 1,
            'message' => 'Customer Deleted Successfully',
            'location' => route('maintain-customers.route_customer_list', $id)
        ]);
    }

    public function approveRouteCustomer(Request $request, $id)
    {
        // $validations = Validator::make($request->all(), [
        //     'id' => 'required|exists:wa_route_customers,id',
        // ]);
        // if ($validations->fails()) {
        //     return response()->json([
        //         'result' => 0,
        //         'errors' => $validations->errors()
        //     ]);
        // }
        $new = WaRouteCustomer::where('id', $id)->first();
        $new->is_verified = true;
        $new->save();
        return $request->ajax() ? response()->json([
            'result' => 1,
            'message' => 'Customer approved Successfully',
            'location' => route('maintain-customers.route_customer_list', $id)
        ]) : redirect()->back()->with('success', 'Customer approved Successfully');
    }

    public function route_customer_store(Request $request, $id)
    {

        $validations = Validator::make($request->all(), [
            'route_id' => 'required|exists:wa_customers,route_id',
            'customer_id' => 'required|exists:wa_customers,id',
            'name' => 'required|string|min:1|max:200|unique:wa_route_customers,name,' . $request->route_id,
            'phone_no' => 'required|numeric|digits_between:9,12',
            'business_name' => 'required|string|min:1|max:200',
            //            'is_credit_customer' => 'boolean',
            'center_id' => 'required',
            //            'credit_limit' => 'required_if:is_credit_customer,1',
            //            'return_limit' => 'required_if:is_credit_customer,1',
            //            'payment_term_id' => 'required_if:is_credit_customer,1',
            'lat' => 'required',
            'lng' => 'required',
            'location_name' => 'required',
        ]);

        if ($validations->fails()) {
            return $request->ajax() ? response()->json([
                'result' => 0,
                'errors' => $validations->errors()
            ]) : redirect()->back()->withErrors($validations->errors());
        }
        $check = DB::transaction(function () use ($request, $id) {
            $user = getLoggeduserProfile();
            $new = new WaRouteCustomer;
            $new->created_by = 0;
            $new->route_id = $request->route_id;
            $new->delivery_centres_id = $request->center_id;
            $new->customer_id = $request->customer_id;
            $new->name = $request->name;
            $new->phone = $request->phone_no;
            $new->kra_pin = $request->kra_pin;
            $new->bussiness_name = $request->business_name;
            $new->town = $request->town;
            $new->contact_person = $request->contact_person;
            $new->lat = $request->lat;
            $new->lng = $request->lng;
            $new->location_name = $request->location_name;

            //            $new->is_credit_customer = $request->is_credit_customer;
            //            $new->credit_limit = $request->credit_limit;
            //            $new->return_limit = $request->return_limit;
            //            $new->payment_term_id = $request->payment_term_id;
            $new->save();

            //            GetShopDistanceEstimates::dispatch($new)->afterCommit();
            //            GetShopRouteSections::dispatch($new)->afterCommit();
            //            GetShopRoutePolylines::dispatch($new)->afterCommit();
            return true;
        });
        //


        if ($check) {
            return $request->ajax() ? response()->json([
                'result' => 1,
                'message' => 'Customer Added Successfully',
                'location' => route('customer-centre.show', $request->customer_id)
            ]) : redirect()->route('customer-centre.show', $request->customer_id)->with('success', 'Customer updated successfully');
        }
        return $request->ajax() ? response()->json([
            'result' => -1,
            'message' => 'Something went wrong',
        ]) : redirect()->back()->withErrors(['error', 'Something went wrong. Please try again']);
    }

    public function route_customer_update(Request $request, $id)
    {
        $validations = Validator::make($request->all(), [
            'route_id' => 'required|exists:wa_customers,route_id',
            'customer_id' => 'required|exists:wa_customers,id',
            'name' => 'required|string|min:1|max:200',
            'phone_no' => 'required|numeric|digits_between:9,12',
            'business_name' => 'required|string|min:1|max:200',
            //            'is_credit_customer' => 'boolean',
            // 'center_id' => 'required',
            //            'credit_limit' => 'required_if:is_credit_customer,1',
            //            'return_limit' => 'required_if:is_credit_customer,1',
            //            'payment_term_id' => 'required_if:is_credit_customer,1',
//            'lat' => 'required',
//            'lng' => 'required',
//            'location_name' => 'required',
        ]);
        if ($validations->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validations->errors()
            ]);
        }
//        if ($request->lat == 0 || $request->lng == 0) {
//            return response()->json([
//                'result' => 0,
//                'errors' => "Please check the shop location details."
//            ]);
//        }

        // $id = $request->customer_id;
        $check = DB::transaction(function () use ($request, $id) {
            $user = getLoggeduserProfile();

            $data = WaRouteCustomer::where('id', $id)->first();
            $data->created_by = $user->id;
            $data->route_id = $request->route_id;
            $data->delivery_centres_id = $request->center_id;
            $data->customer_id = $request->customer_id;
            $data->name = $request->name;
            $data->phone = $request->phone_no;
            $data->kra_pin = $request->kra_pin;
            $data->bussiness_name = $request->business_name;
            $data->town = $request->town;
            $data->contact_person = $request->contact_person;
            if ($data->lat != $request->lat || $data->lng == $request->lng) {
                //                GetShopDistanceEstimates::dispatch($data)->afterCommit();
                //                GetShopRouteSections::dispatch($data)->afterCommit();
                //                GetShopRoutePolylines::dispatch($data)->afterCommit();
            }
            $data->lat = $request->lat;
            $data->lng = $request->lng;
            $data->location_name = $request->location_name;
            $data->save();
            return true;
        });
        if ($check) {
            return $request->ajax() ? response()->json([
                'result' => 1,
                'message' => 'Customer updated successfully',
                'location' => route('customer-centre.show', $request->customer_id)
            ]) : redirect()->route('customer-centre.show', $request->customer_id)->with('success', 'Customer updated successfully');
        }

        return $request->ajax() ? response()->json([
            'result' => -1,
            'message' => 'Something went wrong',
        ]) : redirect()->back()->withErrors(['error', 'Something went wrong. Please try again']);
    }

    public function route_customer_dropdown(Request $request)
    {
        $data = WaRouteCustomer::SELECT(['id', DB::RAW('CONCAT(name," : ",bussiness_name," : ",phone) as text')])->where(function ($e) use ($request) {
            if ($request->search) {
                $e->orWhere('name', 'LIKE', $request->search . "%");
                $e->orWhere('phone', 'LIKE', $request->search . "%");
                $e->orWhere('bussiness_name', 'LIKE', $request->search . "%");
                $e->orWhere('contact_person', 'LIKE', $request->search . "%");
                $e->orWhere('town', 'LIKE', $request->search . "%");
            }
        })->where(function ($e) use ($request) {
            // if($request->role_id && $request->role_id == 4){
            $e->where('route_id', $request->route);
            // }
        })->limit(20)->get();
        return response()->json($data);
    }

    public function add_route_customer(Request $request)
    {
        $validations = Validator::make($request->all(), [
            'route_id' => 'required|exists:wa_customers,route_id',
            // 'customer_id'=>'required|exists:wa_customers,id',
            'name' => 'required|string|min:1|max:200|unique:wa_route_customers,name,NULL,id,route_id,' . $request->route_id,
            'phone_no' => 'required|unique:wa_route_customers,phone|min:10',
            'business_name' => 'required|unique:wa_route_customers,bussiness_name',
            'town' => 'required|string|min:1|max:200',
            'contact_person' => 'required|string|min:1|max:200',
        ]);
        if ($validations->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validations->errors()
            ]);
        }
        $check = DB::transaction(function () use ($request) {
            $user = getLoggeduserProfile();
            $new = new WaRouteCustomer;
            $new->created_by = $user->id;
            $new->route_id = $request->route_id ?? NULL;
            $new->customer_id = $request->customer_id ?? NULL;
            $new->name = $request->name;
            $new->phone = $request->phone_no;
            $new->bussiness_name = $request->business_name;
            $new->town = $request->town;
            $new->contact_person = $request->contact_person;
            $new->save();
            return $new;
        });
        if ($check) {
            return response()->json([
                'result' => 1,
                'message' => 'Customer Added Successfully',
                'data' => $check,
                //'location'=> route('admin.sales-invoices.index')
            ]);
        }
        return response()->json([
            'result' => -1,
            'message' => 'Something went wrong',
        ]);
    }

    public function downloadExcelFile($data, $type, $file_name)
    {
        return Excel::create($file_name, function ($excel) use ($data) {
            $excel->sheet('mySheet', function ($sheet) use ($data) {
                $sheet->fromArray($data);
                foreach ($data as $record) {
                    $i = 'A';
                    foreach ($record as $key => $records) {
                        if ($key > 4) {
                            $sheet->cell($i . '2', function ($cell) {
                                $cell->setBackground('#FFFF00');
                            });
                        }
                        $sheet->getStyle($i . '2')->getFont()
                            ->setBold(true);
                        $i++;
                    }
                }
            });
        })->download($type);
    }

    public function exportroutecustomer(Request $request, $id)
    {

        $data = WaCustomer::where('id', $id)->first();
        $data_query = WaRouteCustomer::where('route_id', $data->route_id)->orderBy('id', 'DESC')->get();
        //   echo "dfs"; die;

        //         $data_query = WaInventoryItem::select('wa_inventory_categories.id as cat_id','wa_inventory_categories.category_description','wa_inventory_items.*')->with('getUnitOfMeausureDetail', 'getAllFromStockMoves','location')
        //         ->join('wa_inventory_categories','wa_inventory_categories.id','=','wa_inventory_items.wa_inventory_category_id')
        //         ->where('wa_inventory_items.wa_inventory_category_id',$request->wa_inventory_category_id)
        // //        ->limit(50)
        //         ->get();
        $filetype = "xlsx";
        $mixed_array = $data_query;
        $export_array = [];
        $file_name = 'Route Customer';
        $counter = 1;

        //         $pricecat = WaCategory::get();
        $headings = [];
        //     //  $prices = [];
        //         foreach($pricecat as $key=> $val){
        //             $headings[$key] = $val->title;
        // //          $prices[$key] = "";
        //         }


        $export_arrays = array('Dated', 'Name', 'Phone', 'Bussiness', 'Town', 'Contact Person');

        //$export_array[] = array_merge($export_arrays,$headings);
        $export_array[] = $export_arrays;
        $final_amount = [];

        foreach ($mixed_array as $item) {

            $prices = [];
            /*foreach($pricecat as $key=> $val){
                $prices[$key] = WaCategoryItemPrice::getitemcatprice($item->id,$val->id);
            }*/

            $final_amount[] = 0;

            $export_arrays = [
                date('d/M/Y', strtotime($item->created_at)),
                $item->name,
                $item->phone,
                $item->bussiness_name,
                $item->town,
                $item->contact_person
            ];

            //$export_array[] = array_merge($export_arrays,$prices);
            $export_array[] = $export_arrays;


            $counter++;
        }


        //        echo "<pre>"; print_r($export_array); die;
        $this->downloadExcelFile($export_array, $filetype, $file_name);
    }


    public function importexcelforroutecustomer(Request $request)
    {


        if ($request->hasFile('excel_file')) {

            $path = $request->file('excel_file')->getRealPath();
            Excel::load($path, function ($reader) use (&$excel) {
                $objExcel = $reader->getExcel();
                $sheet = $objExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $alphabet = range('A', 'Z');
                $highestColumnInNum = array_search($highestColumn, $alphabet);
                $excel = [];
                $rown = $highestColumnInNum - 5;
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray(
                        'A' . $row . ':' . $highestColumn . $row,
                        NULL,
                        TRUE,
                        FALSE
                    );

                    $excel[] = $rowData[0];
                }
            });


            $data = [];
            $heading = [];
            foreach ($excel as $key => $val) {
                if ($key == 0) {
                    $heading = $val;
                }
                if ($key > 0) {
                    $data[$key]['name'] = $val[1];
                    $data[$key]['phone'] = $val[2];
                    $data[$key]['bussiness_name'] = @$val[3];
                    $data[$key]['town'] = @$val[4];
                    $data[$key]['contact_person'] = @$val[5];
                    /*foreach($val as $k=>$vals){
                        if($k > 8 && $heading[$k]!=""){
                            $data[$key][$heading[$k]] = ($vals!="")?$vals:"" ;
                        }
                    }*/
                }
                //        echo "<pre>"; print_r($data); die;
                // dd($data);
            }

            $final = [];


            foreach ($data as $key => $vals) {

                $routeCus = new WaRouteCustomer();
                if ($vals['name'] != "") {
                    $customer = WaCustomer::findOrFail($request->customer_id);


                    $user = getLoggeduserProfile();
                    $routeCus->created_by = @$user->id;
                    $routeCus->customer_id = @$request->customer_id;
                    $routeCus->route_id = @$customer->route_id;
                    $routeCus->name = $vals['name'];
                    $routeCus->phone = $vals['phone'];
                    $routeCus->bussiness_name = $vals['bussiness_name'];
                    $routeCus->town = $vals['town'];
                    $routeCus->contact_person = $vals['contact_person'];
                    $routeCus->save();
                }
            }


            Session::flash('success', 'Route Customer Imported Successfully');
            return redirect()->route($this->model . '.index');
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->route($this->model . '.index');
        }
    }

    public function filterData(Request $request)
    {
        die("sd");
        pre($request->route_id);
    }


    public function removeCustomer(Request $request)
    {
        try {
            $customer = WaRouteCustomer::find($request->shop_id);
            $customer->delete();

            return redirect()->back()->with('success', 'Customer removed successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors(['message' => $th->getMessage()]);
        }
    }

    public function showDebtorTransReconPage($uploadedData = false, $matchingTrans = [], $rejectedTrans = [])
    {
        $title = 'Debtor Trans Upload Centre';
        $model = 'bank-reconciliation';
        $breadcum = [$title => route($model . '.index'), 'Upload' => ''];
        return view('admin.receiablescustomers.recon.new_upload', compact('title', 'breadcum', 'model', 'uploadedData', 'matchingTrans', 'rejectedTrans'));
    }

    public function processUpload(Request $request)
    {
        try {
            $manualFileReader = new Xlsx();
            $manualFileReader->setReadDataOnly(false);
            $manualFile = $request->file('upload_file');
            $manualSpreadSheet = $manualFileReader->load($manualFile);
            $manualData = $manualSpreadSheet->getActiveSheet()->toArray();

            $bankFileReader = new Xlsx();
            $bankFileReader->setReadDataOnly(false);
            $bankFile = $request->file('bank_upload_file');
            $bankSpreadSheet = $bankFileReader->load($bankFile);
            $bankData = $bankSpreadSheet->getActiveSheet()->toArray();

            $extractedBankData = [];
            foreach ($bankData as $index => $record) {
                if ($index != 0) {
                    if ($request->bank == 'EQUITY BANK') {
                        $extractedBankData[] = [
                            'amount' => (float)(str_replace(',', '', $record[2])) ?? 0,
                            'raw_ref' => $record[1],
                            'alt_ref_1' => $record[3],
                            'alt_ref_2' => $record[4],
                            'alt_ref_3' => $record[5],
                        ];
                    } else {
                        $extractedBankData[] = [
                            'amount' => (float)(str_replace(',', '', $record[4])),
                            'raw_ref' => $record[1],
                            'alt_ref_1' => null,
                            'alt_ref_2' => null,
                            'alt_ref_3' => null,
                        ];
                    }
                }
            }

            $matchingTrans = [];
            $rejectedTrans = [];
            foreach ($manualData as $index => $record) {
                if ($index != 0) {
                    $record = array_slice($record, 0, 7);
                    $manualReference = $record[6];
                    //                    $manualDate = Carbon::parse($record[2])->toDateString();
                    $manualAmount = (float)(str_replace(',', '', $record[3]));

                    $recordMatch = collect($extractedBankData)->filter(function ($extractedBankDatum) use ($manualReference, $manualAmount) {
                        return str_contains($extractedBankDatum['raw_ref'], $manualReference) && ($extractedBankDatum['amount'] == $manualAmount);
                    })->first();

                    //                    if (!$recordMatch) {
                    //                        $recordMatch = collect($extractedBankData)->where('alt_ref_1', 'like', "%$manualReference")->where('amount', $manualAmount)->first();
                    //                    }
                    //
                    //                    if (!$recordMatch) {
                    //                        $recordMatch = collect($extractedBankData)->where('alt_ref_2', 'like', "%$manualReference%")->where('amount', $manualAmount)->first();
                    //                    }
                    //
                    //                    if (!$recordMatch) {
                    //                        $recordMatch = collect($extractedBankData)->where('alt_ref_3', 'like', "%$manualReference%")->where('amount', $manualAmount)->first();
                    //                    }

                    if (!$recordMatch) {
                        $record[] = 'Missing match';
                        $rejectedTrans[] = $record;
                    } else {
                        $record[] = $recordMatch['raw_ref'];
                        $matchingTrans[] = $record;
                    }
                }
            }

            return $this->showDebtorTransReconPage(true, $matchingTrans, $rejectedTrans);
        } catch (\Throwable $e) {
            Session::flash('warning', $e->getMessage());
            return redirect()->route('maintain-customers.real_recon.index');
        }
    }

    public function confirmUpload(Request $request)
    {
        DB::beginTransaction();
        try {
            $records = json_decode($request->records, true);
            $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
            $series_module = WaNumerSeriesCode::where('module', 'RECEIPT')->first();
            $waCustomers = DB::table('wa_customers')->get();
            $routes = Route::all();
            foreach ($records as $index => $row) {
                if ($existingRecord = WaDebtorTran::where('reference', 'like', "%$row[6]%")->where('document_no', 'like', '%RCT%')->where('amount', ($row[3]) * -1)->first()) {
                    DB::rollBack();
                    Session::flash('warning', "Reference $row[6] already exists at record id $existingRecord->document_no");
                    return redirect()->route('maintain-customers.real_recon.index');
                }

                $matchedWaCustomer = $waCustomers->where('customer_code', $row[0])->first();
                if (!$matchedWaCustomer) {
                    DB::rollBack();
                    Session::flash('warning', "Customer code $row[0] at index $index is invalid and could not be processed.");
                    return redirect()->route('maintain-customers.real_recon.index');
                }

                $documentNo = getCodeWithNumberSeries('RECEIPT');
                $route = $routes->where('id', $matchedWaCustomer->route_id)->first();
                $date = Carbon::parse($row[2]);
                $amount = $row[3];
                $channel = $row[5];
                $user = \App\Model\User::where('route', $matchedWaCustomer->route_id)->first();
                $debtorTrans = WaDebtorTran::create([
                    'salesman_id' => 46, // Find a way to make this dynamic
                    'salesman_user_id' => $route->salesman()?->id,
                    'type_number' => $series_module?->type_number,
                    'wa_customer_id' => $matchedWaCustomer->id,
                    'customer_number' => $matchedWaCustomer->customer_code,
                    'trans_date' => $date,
                    'input_date' => $date,
                    'wa_accounting_period_id' => $accountingPeriod ? $accountingPeriod->id : null,
                    'shift_id' => null,
                    'invoice_customer_name' => $row[4],
                    'reference' => $row[6],
                    'amount' => - ($amount),
                    'document_no' => $documentNo,
                    'updated_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                    'user_id' => 0,
                    'paid_by' => 0,
                    'route_id' => $route->id,
                    'branch_id' => $route->restaurant_id,
                    'channel' => $channel,
                    // 'reconciled' => true,
                    // 'bank_ref' => $row[7] ?? $row[8],
                ]);

                $bank_account = $channel == 'Eazzy' ? WaBankAccount::find(4) : WaBankAccount::find(2);
                // $btran = new WaBanktran();
                // $btran->type_number = $series_module->type_number;
                // $btran->document_no = $documentNo;
                // $btran->bank_gl_account_code = $bank_account->getGlDetail?->account_code;
                // $btran->reference = $debtorTrans->reference;
                // $btran->trans_date = $date;
                // $btran->wa_payment_method_id = $channel == 'Eazzy' ? 7 : 8;
                // $btran->amount = $amount;
                // $btran->wa_curreny_id = null;
                // $btran->cashier_id = 1;
                // $btran->created_at = Carbon::now();
                // $btran->updated_at = Carbon::now();
                // $btran->save();

                // $cr = new WaGlTran();
                // $cr->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                // $cr->wa_debtor_tran_id = $debtorTrans->id;
                // $cr->grn_type_number = $series_module->type_number;
                // $cr->trans_date = $date;
                // $cr->restaurant_id = 10; // MAKONGENI;
                // $cr->grn_last_used_number = $series_module->last_number_used;
                // $cr->transaction_type = $series_module->description;
                // $cr->transaction_no = $documentNo;
                // $cr->narrative = $debtorTrans->reference;
                // $cr->account = $bank_account->getGlDetail->account_code;
                // $cr->amount = $amount;
                // $cr->created_at = Carbon::now();
                // $cr->updated_at = Carbon::now();
                // $cr->save();


                // $dr = new WaGlTran();
                // $dr->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                // $dr->wa_debtor_tran_id = $debtorTrans->id;
                // $dr->grn_type_number = $series_module->type_number;
                // $dr->trans_date = $date;
                // $dr->restaurant_id = 10;
                // $dr->grn_last_used_number = $series_module->last_number_used;
                // $dr->transaction_type = $series_module->description;
                // $dr->transaction_no = $documentNo;
                // $dr->narrative = $debtorTrans->reference;
                // $dr->created_at = Carbon::now();
                // $dr->updated_at = Carbon::now();

                // $companyPreference = WaCompanyPreference::find(1);
                // $dr->account = $companyPreference->debtorsControlGlAccount?->account_code;
                // $dr->amount = '-' . $amount;
                // $dr->save();

                $tenderEntry = new WaTenderEntry();
                $tenderEntry->document_no = $documentNo;
                $tenderEntry->channel = $channel;
                $tenderEntry->account_code = $bank_account->getGlDetail?->account_code;
                $tenderEntry->reference = $debtorTrans->reference;
                $tenderEntry->additional_info = $row[4];
                $tenderEntry->customer_id = $matchedWaCustomer->id;
                $tenderEntry->trans_date = $date;
                $tenderEntry->wa_payment_method_id = $channel == 'Eazzy' ? 7 : 8;
                $tenderEntry->amount = $amount;
                $tenderEntry->paid_by = $row[4];
                $tenderEntry->cashier_id = 1;
                $tenderEntry->branch_id = $route->restaurant_id;
                $tenderEntry->created_at = Carbon::now();
                $tenderEntry->updated_at = Carbon::now();
                $tenderEntry->save();

                updateUniqueNumberSeries('RECEIPT', $documentNo);
            }

            DB::commit();
            Session::flash('success', 'Records uploaded successfully');
            return redirect()->route('maintain-customers.real_recon.index');
        } catch (\Throwable $e) {
            DB::rollBack();
            Session::flash('warning', $e->getMessage() . ": " . $e->getTraceAsString());
            return redirect()->route('maintain-customers.real_recon.index');
        }
    }

    public function downloadRejected(Request $request)
    {
        $records = json_decode($request->records, true);
        $headings = ['Route Number', 'Route Name', 'Date', 'Amount', 'Bank', 'Channel', 'Reference', 'Remarks'];
        return ExcelDownloadService::download('unmatched_uploads', collect($records), $headings);
    }
}
