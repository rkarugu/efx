<?php

namespace App\Http\Controllers\Admin;

use App\FinancialNote;
use App\Http\Controllers\Controller;
use App\Model\Restaurant;
use App\Model\WaBankAccount;
use App\Model\WaGrn;
use App\Model\WaSupplier;
use App\Model\WaSuppTran;
use App\Models\WaPaymentMode;
use App\PaymentVoucher;
use App\PaymentVoucherCheque;
use App\PaymentVoucherItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryLocationStockStatus;
use App\Model\WaLocationAndStore;
use App\Model\WaPurchaseOrderItem;
use App\Model\WaStockMove;
use App\Model\WaUserSupplier;
use App\Models\AdvancePayment;
use App\Models\SupplierBill;
use App\Models\TradeDiscount;
use App\Models\WaReturnDemand;
use App\WaDemand;
use Illuminate\Support\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentVoucherController extends Controller
{
    protected $model;
    protected $base_route;
    protected $resource_folder;
    protected $base_title;
    protected $title;

    public function __construct()
    {
        $this->model = 'payment-vouchers';
        $this->base_route = 'payment-vouchers';
        $this->resource_folder = 'admin.payment_vouchers';
        $this->base_title = 'Payment Vouchers';
    }

    public function index()
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $model = $this->model;
        $title = 'Payment Vouchers';
        $breadcum = [
            'Payment Vouchers' => route($this->model . '.index')
        ];

        $query = PaymentVoucher::query()
            ->select([
                'payment_vouchers.*',
                'bank_files.file_no'
            ])
            ->with('supplier', 'account', 'paymentMode', 'voucherItems.payable')
            ->withCount('voucherItems')
            ->leftJoin('wa_bank_file_items as bank_file_items', 'bank_file_items.payment_voucher_id', 'payment_vouchers.id')
            ->leftJoin('wa_bank_files as bank_files', 'bank_files.id', 'bank_file_items.wa_bank_file_id')
            ->when(request()->status, function ($query) {
                if (request()->status == 'approved') {
                    return $query->approved();
                }

                if (request()->status == 'paid') {
                    return $query->processed();
                }

                return $query->pending();
            })
            ->when(!can('can-view-all-suppliers', 'maintain-suppliers'), function ($query) {
                $supplierIds = WaUserSupplier::where('user_id', auth()->user()->id)->get()
                    ->pluck('wa_supplier_id')->toArray();
                $query->whereIn('wa_grns.wa_supplier_id', $supplierIds);
            });

        if (request()->filled('supplier')) {
            $query->where('wa_supplier_id', request()->supplier);
        }

        if (request()->filled('start_date') && request()->filled('end_date')) {
            $query->whereBetween('created_at', [request()->start_date . ' 00:00:00', request()->end_date . ' 23:59:59']);
        }

        if (request()->ajax()) {
            return DataTables::eloquent($query)
                ->addColumn('actions', function ($voucher) {
                    return view('admin.payment_vouchers.action', ['voucher' => $voucher]);
                })
                ->editColumn('amount', function ($voucher) {
                    return manageAmountFormat($voucher->amount);
                })
                ->editColumn('created_at', function ($voucher) {
                    return $voucher->created_at->format('Y-m-d');
                })
                ->with('total_amount', function () use ($query) {
                    return manageAmountFormat($query->sum('payment_vouchers.amount'));
                })
                ->rawColumns(['action'])
                ->toJson();
        }

        $suppliers = WaSupplier::get();

        return view('admin.payment_vouchers.index', compact('model', 'title', 'breadcum', 'suppliers'));
    }

    public function create($code)
    {
        if (!can('add', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $model = $this->model;
        $supplier = WaSupplier::where('supplier_code', $code)->firstOrFail();

        $title = $this->base_title . " - $supplier->supplier_code";
        $breadcum = [
            'Maintain Suppliers' => route($this->model . '.index'),
            'Vendor Centre' => route('maintain-suppliers.vendor_centre', $supplier->supplier_code),
            $this->base_title => route('maintain-suppliers.payment_vouchers.create', $supplier->supplier_code)
        ];

        $paymentVouchers = PaymentVoucher::where('wa_supplier_id', $supplier->id)->latest()->get();
        $accounts = WaBankAccount::query()
            ->with('getGlDetail')
            ->whereHas('paymentMethod', function ($query) {
                $query->where('use_for_payments', 1);
            })->get();
        $paymentModes = WaPaymentMode::query()->get();
        $chqSeries =  getCodeWithNumberSeries('CHEQUES');

        if (request()->type == 'advance') {
            return $this->loadAdvancePayments($breadcum, $title, $supplier, $paymentVouchers, $accounts, $paymentModes, $chqSeries);
        }

        if (request()->type == 'bill') {
            return $this->loadBillPayments($breadcum, $title, $supplier, $paymentVouchers, $accounts, $paymentModes, $chqSeries);
        }

        $seriesCode = \App\Model\WaNumerSeriesCode::where('module', 'SUPPLIER_INVOICE_NO')->first();
        $invoices = WaSuppTran::query()
            ->with('invoice.lpo', 'notes')
            ->where('total_amount_inc_vat', '>', 0)
            ->where('supplier_no', $supplier->supplier_code)
            ->where('settled', 0)
            ->where('grn_type_number', $seriesCode->type_number)
            ->whereDoesntHave('payments')
            ->whereDoesntHave('allocation')
            ->get();

        $invoices->each(function ($item) use ($supplier) {
            $withholding = $supplier->tax_withhold ? ceil($item->vat_amount * (2 / 16)) : 0;
            $professional_withholding = $supplier->professional_withholding ? ceil($item->vat_amount * 0.05) : 0;
            $item->setAttribute('withholding_tax',   $withholding);
            $item->setAttribute('paid',   $item->payments()->sum('amount'));
            $item->setAttribute('professional_withholding',   $professional_withholding);
        });

        return view('admin.payment_vouchers.create.invoice', compact('title', 'breadcum', 'supplier', 'model', 'invoices', 'accounts', 'paymentModes', 'chqSeries', 'paymentVouchers'));
    }

    protected function loadBillPayments($breadcum, $title, $supplier, $paymentVouchers, $accounts, $paymentModes, $chqSeries)
    {
        $bills = SupplierBill::query()
            ->where('wa_supplier_id', $supplier->id)
            ->doesntHave('payment')
            ->get();

        $bills->each(function ($item) use ($supplier) {
            $withholding = $supplier->tax_withhold ? ceil($item->vat_amount * (2 / 16)) : 0;
            $item->setAttribute('withholding_tax',   $withholding);
        });

        return view('admin.payment_vouchers.create.bill', [
            'title' => $title,
            'model' => $this->model,
            'breadcum' => $breadcum,
            'paymentVouchers' => $paymentVouchers,
            'accounts' => $accounts,
            'paymentModes' => $paymentModes,
            'chqSeries' => $chqSeries,
            'bills' => $bills,
            'supplier' => $supplier,
        ]);
    }

    protected function loadAdvancePayments($breadcum, $title, $supplier, $paymentVouchers, $accounts, $paymentModes, $chqSeries)
    {
        $payments = AdvancePayment::query()
            ->with('lpo')
            ->where('supplier_id', $supplier->id)
            ->pending()
            ->doesntHave('payment')
            ->get();

        $payments->each(function ($item) use ($supplier) {
            $withholding = $supplier->tax_withhold ? ceil($item->vat_amount * (2 / 16)) : 0;
            $item->setAttribute('withholding_tax',   $withholding);
        });

        return view('admin.payment_vouchers.create.advance', [
            'title' => $title,
            'model' => $this->model,
            'breadcum' => $breadcum,
            'paymentVouchers' => $paymentVouchers,
            'accounts' => $accounts,
            'paymentModes' => $paymentModes,
            'chqSeries' => $chqSeries,
            'payments' => $payments,
            'supplier' => $supplier,
        ]);
    }

    public function edit($code)
    {
        if (!can('edit', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $model = $this->model;
        $voucher = PaymentVoucher::with([
            'voucherItems.payable.notes',
            'voucherItems.payable.invoice.lpo',
        ])->where('number', $code)
            ->firstOrFail();

        if ($voucher->isProcessed()) {
            return redirect()->route('payment-vouchers.index')
                ->withErrors('A processed payment voucher cannot be edited');
        }

        $supplier = $voucher->supplier;

        $title = $this->base_title . " - $supplier->supplier_code";
        $breadcum = [
            'Maintain Suppliers' => route($this->model . '.index'),
            'Payment Vouchers' => route('payment-vouchers.index'),
            'Edit Voucher' => route('payment-vouchers.edit', $voucher)
        ];

        $seriesCode = \App\Model\WaNumerSeriesCode::where('module', 'SUPPLIER_INVOICE_NO')->first();
        $invoices = WaSuppTran::query()
            ->with('invoice.lpo', 'notes')
            ->where('total_amount_inc_vat', '>', 0)
            ->where('supplier_no', $supplier->supplier_code)
            ->where('settled', 0)
            ->where('grn_type_number', $seriesCode->type_number)
            ->whereDoesntHave('payments')
            ->whereDoesntHave('allocation')
            ->get();

        $invoices->each(function ($item) use ($supplier) {
            $withholding = $supplier->tax_withhold ? ceil($item->vat_amount * (2 / 16)) : 0;
            $professional_withholding = $supplier->professional_withholding ? ceil($item->vat_amount * 0.05) : 0;
            $item->setAttribute('withholding_tax',   $withholding);
            $item->setAttribute('paid',   $item->payments()->sum('amount'));
            $item->setAttribute('professional_withholding',   $professional_withholding);
        });

        $selectedInvoices = $voucher->voucherItems;
        $selectedInvoices->each(function ($item) use ($supplier) {
            $withholding = $supplier->tax_withhold ? ceil($item->vat_amount * (2 / 16)) : 0;
            $professional_withholding = $supplier->professional_withholding ? ceil($item->payable->vat_amount * 0.05) : 0;
            $item->setAttribute('withholding_tax',   $withholding);
            $item->setAttribute('paid',   $item->payable->payments()->sum('amount'));
            $item->setAttribute('professional_withholding',   $professional_withholding);
        });

        $paymentVouchers = PaymentVoucher::where('wa_supplier_id', $supplier->id)->latest()->get();
        $accounts = WaBankAccount::query()
            ->with('getGlDetail')
            ->whereHas('paymentMethod', function ($query) {
                $query->where('use_for_payments', 1);
            })->get();
        $paymentModes = WaPaymentMode::query()->get();
        $cheques = [];
        $voucher->cheques->map(function ($cheque) use (&$cheques) {
            $cheques[] = [
                'cheq_number' => $cheque->number,
                'cheq_date' => $cheque->date,
                'cheq_amount' => $cheque->amount,
            ];
        });
        $chqSeries =  getCodeWithNumberSeries('CHEQUES');

        return view('admin.payment_vouchers.edit', compact('title', 'breadcum', 'supplier', 'model', 'accounts', 'invoices', 'selectedInvoices', 'paymentModes', 'chqSeries', 'cheques', 'voucher', 'paymentVouchers'));
    }

    public function store(Request $request, $code)
    {
        if (!can('add', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $this->validate($request, [
            'account' => 'required',
            'payment_mode' => 'required',
            'transactions' => 'required',
            'cheques' => 'required',
        ], [
            'items' => 'Please select at least one item',
            'cheques' => 'At least on check is required'
        ]);


        $user = getLoggeduserProfile();
        $supplier = WaSupplier::where('supplier_code', $code)->firstOrFail();

        DB::beginTransaction();

        try {
            $cheques = PaymentVoucherCheque::wherein('number', collect(json_decode($request->cheques))->pluck('cheq_number'))->get();

            $voucher = PaymentVoucher::create([
                'wa_supplier_id' => $supplier->id,
                'wa_bank_account_id' => $request->account,
                'number' => getCodeWithNumberSeries('PAYMENT_VOUCHERS'),
                'wa_payment_mode_id' => $request->payment_mode,
                'narration' => $request->narration,
                'amount' => $amountPaid = $cheques->sum('amount'),
                "prepared_by" => $user->id,
            ]);

            updateUniqueNumberSeries('PAYMENT_VOUCHERS', $voucher->number);

            foreach ($cheques as $cheque) {
                $cheque->update([
                    'payment_voucher_id' => $voucher->id,
                    'wa_supplier_code' => $supplier->supplier_code,
                ]);
            }

            switch (request()->type) {
                case 'advance':
                    $this->storeAdvanceItems($request, $supplier, $voucher, $amountPaid);
                    break;

                case 'bill':
                    $this->storeBillItems($request, $supplier, $voucher, $amountPaid);
                    break;

                default:
                    $this->storeInvoiceItems($request, $supplier, $voucher, $amountPaid);
                    break;
            }

            DB::commit();

            if ($request->input('action') == 'print') {
                return redirect()->route('payment-vouchers.print_pdf', $voucher->id);
            }

            Session::flash('success', 'Voucher created successfully.');

            return redirect()->route('maintain-suppliers.vendor_centre', $supplier->supplier_code);
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->route('maintain-suppliers.payment_vouchers.create', $supplier->supplier_code)
                ->withErrors('An error occurred: ' . $e->getMessage());
        }
    }

    protected function storeAdvanceItems($request, $supplier, $voucher, $amountPaid)
    {
        // NB: Pass amount paid by reference
        $transactions = AdvancePayment::wherein('id', collect(json_decode($request->transactions))->pluck('id'))->get();
        $transactions->each(function ($transaction) use ($supplier, $voucher, &$amountPaid, $request) {
            $withholding_amount = $supplier->tax_withhold ? ceil($transaction->vat_amount * (2 / 16)) : 0;
            $professional_withholding = $supplier->professional_withholding ? ceil($transaction->total_amount_inc_vat * 0.05) : 0;

            $toPay = collect(json_decode($request->transactions))->where('id', $transaction->id)->first()->amount;

            $transaction->update([
                'withholding_amount' => $withholding_amount,
            ]);

            PaymentVoucherItem::create([
                'payment_voucher_id' => $voucher->id,
                'payable_type' => 'advance',
                'payable_id' => $transaction->id,
                'amount' => $toPay
            ]);
        });
    }

    protected function storeBillItems($request, $supplier, $voucher, $amountPaid)
    {
        // NB: Pass amount paid by reference
        $transactions = SupplierBill::wherein('id', collect(json_decode($request->transactions))->pluck('id'))->get();
        $transactions->each(function ($transaction) use ($supplier, $voucher, &$amountPaid, $request) {
            $withholding_amount = $supplier->tax_withhold ? ceil($transaction->vat_amount * (2 / 16)) : 0;
            $professional_withholding = $supplier->professional_withholding ? ceil($transaction->total_amount_inc_vat * 0.05) : 0;

            $toPay = collect(json_decode($request->transactions))->where('id', $transaction->id)->first()->amount;

            $transaction->update([
                'withholding_amount' => $withholding_amount,
            ]);

            PaymentVoucherItem::create([
                'payment_voucher_id' => $voucher->id,
                'payable_type' => 'bill',
                'payable_id' => $transaction->id,
                'amount' => $toPay
            ]);
        });
    }

    protected function storeInvoiceItems($request, $supplier, $voucher, $amountPaid)
    {
        // NB: Pass amount paid by reference
        $transactions = WaSuppTran::wherein('id', collect(json_decode($request->transactions))->pluck('id'))->get();
        $transactions->each(function ($transaction) use ($supplier, $voucher, &$amountPaid, $request) {
            $withholding_amount = $supplier->tax_withhold ? ceil($transaction->vat_amount * (2 / 16)) : 0;
            $professional_withholding = $supplier->professional_withholding ? ceil($transaction->total_amount_inc_vat * 0.05) : 0;

            $toPay = collect(json_decode($request->transactions))->where('id', $transaction->id)->first()->amount;

            $balance = $amountPaid - $toPay;
            if ($balance == 0) {
                $transaction->update([
                    'allocated_amount' =>  $transaction->allocated_amount - $transaction->voucherPaymentsTotal($voucher->id) + $toPay,
                ]);
            } else if ($balance > 0) {
                $transaction->update([
                    'allocated_amount' =>  $transaction->allocated_amount - $transaction->voucherPaymentsTotal($voucher->id) +  $toPay,
                ]);

                $amountPaid -= $toPay;
            } else if ($amountPaid > 0 && $balance < 0) {
                $toPay = $amountPaid;
                $transaction->update([
                    'allocated_amount' =>  $transaction->allocated_amount - $transaction->voucherPaymentsTotal($voucher->id) +  $toPay,
                ]);
            }

            $transaction->update([
                'withholding_amount' => $withholding_amount,
                'professional_withholding' => $professional_withholding,
            ]);

            PaymentVoucherItem::create([
                'payment_voucher_id' => $voucher->id,
                'payable_type' => 'invoice',
                'payable_id' => $transaction->id,
                'amount' => $toPay
            ]);
        });
    }

    public function show(PaymentVoucher $voucher)
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        if ($voucher->isAdvancePayment() || $voucher->isBillPayment()) {
            return view('admin.payment_vouchers.show', [
                'title' => 'Voucher Details',
                'model' => 'payment-vouchers',
                'breadcum' => [
                    'Payment Vouchers' => route('payment-vouchers.index'),
                    $voucher->number => '',
                ],
                'voucher' => $voucher,
                'locations' => WaLocationAndStore::get()
            ]);
        }

        $items = [];
        $grns = [];
        $invoices = [];

        foreach ($voucher->voucherItems as $voucherItem) {
            $invoices[] = $voucherItem->invoice->id;
            $grns[] = $voucherItem->invoice->grn_number;
            $items[] = $voucherItem->invoice->items->pluck('code')->toArray();
        }


        return view('admin.payment_vouchers.show', [
            'title' => 'Voucher Details',
            'model' => 'payment-vouchers',
            'breadcum' => [
                'Payment Vouchers' => route('payment-vouchers.index'),
                $voucher->number => '',
            ],
            'voucher' => $voucher,
            'items' => collect($items)->unique()->flatten(),
            'grns' => collect($grns)->unique(),
            'invoices' => collect($invoices)->unique(),
            'locations' => WaLocationAndStore::get()
        ]);
    }

    public function supplierOverStocks()
    {
        $from = now()->subDays(30)->format('Y-m-d 00:00:00');
        $to = now()->format('Y-m-d 23:59:59');

        $salesSub = WaStockMove::query()
            ->select([
                DB::raw('IFNULL(ABS(SUM(qauntity)),0)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where(function ($query) {
                $query->where('document_no', 'like', 'INV-%')
                    ->orWhere('document_no', 'like', 'RTN-%');
            })
            ->whereBetween('created_at', [$from, $to])
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qohSub = WaStockMove::query()
            ->select([
                DB::raw('SUM(qauntity)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qooSub = WaPurchaseOrderItem::query()
            ->select([
                DB::raw("IFNULL(SUM(quantity),0)")
            ])
            ->whereHas('getPurchaseOrder', function ($query) {
                $query->where('status', 'APPROVED')
                    ->where('is_hide', '<>', 'Yes')
                    ->doesntHave('grns');

                $query->where('wa_location_and_store_id', request()->location);
            })
            ->whereColumn('wa_purchase_order_items.wa_inventory_item_id', 'wa_inventory_items.id');

        $lastLpoDateSub = WaPurchaseOrderItem::query()
            ->select([
                DB::raw("MAX(created_at)")
            ])
            ->whereHas('getPurchaseOrder', function ($query) {
                $query->where(function ($query) {
                    $query->where('status', 'APPROVED')
                        ->orWhere('status', 'COMPLETED');
                })
                    ->where('is_hide', '<>', 'Yes')
                    ->where('wa_location_and_store_id', request()->location);
            })
            ->whereColumn('wa_purchase_order_items.wa_inventory_item_id', 'wa_inventory_items.id');

        $lastGrnDateSub = WaStockMove::query()
            ->select([
                DB::raw("MAX(created_at)")
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where('document_no', 'like', 'GRN-%')
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');


        $query = WaInventoryItem::query()
            ->select([
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.title',
                'category_description',
                DB::raw("IFNULL(stock_status.max_stock, 0) as max_stock"),
                DB::raw("IFNULL(stock_status.re_order_level, 0) as re_order_level"),
            ])
            ->selectSub($salesSub, 'total_sales')
            ->selectSub($qohSub, 'qty_on_hand')
            ->selectSub($qooSub, 'qty_on_order')
            ->selectSub($lastLpoDateSub, 'last_lpo_date')
            ->selectSub($lastGrnDateSub, 'last_grn_date')
            ->leftJoin('wa_inventory_categories as categories', 'categories.id', 'wa_inventory_items.wa_inventory_category_id')
            ->leftJoin('wa_inventory_location_stock_status as stock_status', function ($query) {
                $query->on('stock_status.wa_inventory_item_id', 'wa_inventory_items.id')
                    ->where('wa_location_and_stores_id', request()->location);
            })
            ->whereIn('wa_inventory_items.stock_id_code', request()->items)
            ->where('wa_inventory_items.status', 1)
            ->havingRaw('IFNULL(qty_on_hand, 0) >  IFNULL(max_stock, 0)');

        return DataTables::eloquent($query)
            ->editColumn('qty_on_hand', function ($inventory) {
                return $inventory->qty_on_hand == 0 ? "<b class='text-danger'>$inventory->qty_on_hand</b>" : manageAmountFormat($inventory->qty_on_hand);
            })
            ->addColumn('variance', function ($inventory) {
                $balance = $inventory->qty_on_hand - $inventory->max_stock;
                $formatted = manageAmountFormat($balance);
                return $balance > 0 ? "<b class='text-info'>$formatted</b>" : $formatted;
            })
            ->editColumn('last_lpo_date', function ($inventory) {
                return is_null($inventory->last_lpo_date) ? '' : Carbon::parse($inventory->last_lpo_date)->format('d/m/Y');
            })
            ->editColumn('last_grn_date', function ($inventory) {
                return is_null($inventory->last_grn_date) ? '' : Carbon::parse($inventory->last_grn_date)->format('d/m/Y');
            })
            ->rawColumns(['variance', 'qty_on_hand'])
            ->toJson();
    }

    public function supplierMissingStocks()
    {
        $from = now()->subDays(30)->format('Y-m-d 00:00:00');
        $to = now()->format('Y-m-d 23:59:59');

        $salesSub = WaStockMove::query()
            ->select([
                DB::raw('IFNULL(ABS(SUM(qauntity)),0)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where(function ($query) {
                $query->where('document_no', 'like', 'INV-%')
                    ->orWhere('document_no', 'like', 'RTN-%');
            })
            ->whereBetween('created_at', [$from, $to])
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qohSub = WaStockMove::query()
            ->select([
                DB::raw('SUM(qauntity)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qooSub = WaPurchaseOrderItem::query()
            ->select([
                DB::raw("IFNULL(SUM(quantity),0)")
            ])
            ->whereHas('getPurchaseOrder', function ($query) {
                $query->where('status', 'APPROVED')
                    ->where('is_hide', '<>', 'Yes')
                    ->doesntHave('grns');

                $query->where('wa_location_and_store_id', request()->location);
            })
            ->whereColumn('wa_purchase_order_items.wa_inventory_item_id', 'wa_inventory_items.id');

        $lastLpoDateSub = WaPurchaseOrderItem::query()
            ->select([
                DB::raw("MAX(created_at)")
            ])
            ->whereHas('getPurchaseOrder', function ($query) {
                $query->where(function ($query) {
                    $query->where('status', 'APPROVED')
                        ->orWhere('status', 'COMPLETED');
                })
                    ->where('is_hide', '<>', 'Yes')
                    ->where('wa_location_and_store_id', request()->location);
            })
            ->whereColumn('wa_purchase_order_items.wa_inventory_item_id', 'wa_inventory_items.id');

        $lastGrnDateSub = WaStockMove::query()
            ->select([
                DB::raw("MAX(created_at)")
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where('document_no', 'like', 'GRN-%')
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');


        $query = WaInventoryItem::query()
            ->select([
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.title',
                'category_description',
                DB::raw("IFNULL(stock_status.max_stock, 0) as max_stock"),
                DB::raw("IFNULL(stock_status.re_order_level, 0) as re_order_level"),
            ])
            ->selectSub($salesSub, 'total_sales')
            ->selectSub($qohSub, 'qty_on_hand')
            ->selectSub($qooSub, 'qty_on_order')
            ->selectSub($lastLpoDateSub, 'last_lpo_date')
            ->selectSub($lastGrnDateSub, 'last_grn_date')
            ->leftJoin('wa_inventory_categories as categories', 'categories.id', 'wa_inventory_items.wa_inventory_category_id')
            ->leftJoin('wa_inventory_location_stock_status as stock_status', function ($query) {
                $query->on('stock_status.wa_inventory_item_id', 'wa_inventory_items.id')
                    ->where('wa_location_and_stores_id', request()->location);
            })
            ->whereHas('suppliers', function ($query) {
                $query->where('wa_inventory_item_suppliers.wa_supplier_id', request()->supplier);
            })
            ->where('wa_inventory_items.status', 1)
            ->havingRaw('IFNULL(qty_on_hand, 0) = 0')
            ->havingRaw('IFNULL(total_sales, 0) > 0');

        return DataTables::eloquent($query)
            ->editColumn('qty_on_hand', function ($inventory) {
                return $inventory->qty_on_hand == 0 ? "<b class='text-danger'>$inventory->qty_on_hand</b>" : manageAmountFormat($inventory->qty_on_hand);
            })
            ->addColumn('variance', function ($inventory) {
                $balance = $inventory->qty_on_hand - $inventory->max_stock;
                $formatted = manageAmountFormat($balance);
                return $balance > 0 ? "<b class='text-info'>$formatted</b>" : $formatted;
            })
            ->editColumn('last_lpo_date', function ($inventory) {
                return is_null($inventory->last_lpo_date) ? '' : Carbon::parse($inventory->last_lpo_date)->format('d/m/Y');
            })
            ->editColumn('last_grn_date', function ($inventory) {
                return is_null($inventory->last_grn_date) ? '' : Carbon::parse($inventory->last_grn_date)->format('d/m/Y');
            })
            ->rawColumns(['variance', 'qty_on_hand'])
            ->toJson();
    }

    public function supplierSlowStocks()
    {
        $from = now()->subDays(30)->format('Y-m-d 00:00:00');
        $to = now()->format('Y-m-d 23:59:59');

        $salesSub = WaStockMove::query()
            ->select([
                DB::raw('IFNULL(ABS(SUM(qauntity)),0)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where(function ($query) {
                $query->where('document_no', 'like', 'INV-%')
                    ->orWhere('document_no', 'like', 'RTN-%');
            })
            ->whereBetween('created_at', [$from, $to])
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qohSub = WaStockMove::query()
            ->select([
                DB::raw('IFNULL(SUM(qauntity),0)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $lastSaleDateSub = WaStockMove::query()
            ->select([
                DB::raw("MAX(created_at) as last_sale_date")
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where('document_no', 'like', 'INV-%')
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');


        $query = WaInventoryItem::query()
            ->select([
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.title',
                'category_description',
                DB::raw("IFNULL(stock_status.max_stock, 0) as max_stock"),
                DB::raw("IFNULL(stock_status.re_order_level, 0) as re_order_level"),
            ])
            ->selectSub($salesSub, 'total_sales')
            ->selectSub($qohSub, 'qty_on_hand')
            ->selectSub($lastSaleDateSub, 'last_sale_date')
            ->leftJoin('wa_inventory_categories as categories', 'categories.id', 'wa_inventory_items.wa_inventory_category_id')
            ->leftJoin('wa_inventory_location_stock_status as stock_status', function ($query) {
                $query->on('stock_status.wa_inventory_item_id', 'wa_inventory_items.id')
                    ->where('wa_location_and_stores_id', request()->location);
            })
            ->whereIn('wa_inventory_items.stock_id_code', request()->items)
            ->where('wa_inventory_items.status', 1)
            ->havingRaw('IFNULL(qty_on_hand, 0) > 0')
            ->havingRaw('IFNULL(total_sales, 0) <= 5');

        return DataTables::eloquent($query)
            ->editColumn('last_sale_date', function ($inventory) {
                return is_null($inventory->last_sale_date) ? '' : Carbon::parse($inventory->last_sale_date)->format('d/m/Y');
            })
            ->addColumn('variance', function ($inventory) {
                $balance = $inventory->qty_on_hand - $inventory->max_stock;
                $formatted = manageAmountFormat($balance);
                return $balance > 0 ? "<b class='text-info'>$formatted</b>" : $formatted;
            })
            ->rawColumns(['variance', 'qty_on_hand'])
            ->toJson();
    }

    public function supplierDeadStocks()
    {
        $from = now()->subDays(30)->format('Y-m-d 00:00:00');
        $to = now()->format('Y-m-d 23:59:59');

        $salesSub = WaStockMove::query()
            ->select([
                DB::raw('IFNULL(ABS(SUM(qauntity)),0)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where(function ($query) {
                $query->where('document_no', 'like', 'INV-%')
                    ->orWhere('document_no', 'like', 'RTN-%');
            })
            ->whereBetween('created_at', [$from, $to])
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qohSub = WaStockMove::query()
            ->select([
                DB::raw('IFNULL(SUM(qauntity),0)')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $lastSaleDateSub = WaStockMove::query()
            ->select([
                DB::raw("MAX(created_at) as last_sale_date")
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where('document_no', 'like', 'INV-%')
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');


        $query = WaInventoryItem::query()
            ->select([
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.title',
                'category_description',
                DB::raw("IFNULL(stock_status.max_stock, 0) as max_stock"),
                DB::raw("IFNULL(stock_status.re_order_level, 0) as re_order_level"),
            ])
            ->selectSub($salesSub, 'total_sales')
            ->selectSub($qohSub, 'qty_on_hand')
            ->selectSub($lastSaleDateSub, 'last_sale_date')
            ->leftJoin('wa_inventory_categories as categories', 'categories.id', 'wa_inventory_items.wa_inventory_category_id')
            ->leftJoin('wa_inventory_location_stock_status as stock_status', function ($query) {
                $query->on('stock_status.wa_inventory_item_id', 'wa_inventory_items.id')
                    ->where('wa_location_and_stores_id', request()->location);
            })
            ->whereIn('wa_inventory_items.stock_id_code', request()->items)
            ->where('wa_inventory_items.status', 1)
            ->havingRaw('IFNULL(qty_on_hand, 0) > 0')
            ->havingRaw('IFNULL(total_sales, 0) = 0');

        return DataTables::eloquent($query)
            ->editColumn('last_sale_date', function ($inventory) {
                return is_null($inventory->last_sale_date) ? '' : Carbon::parse($inventory->last_sale_date)->format('d/m/Y');
            })
            ->addColumn('variance', function ($inventory) {
                $balance = $inventory->qty_on_hand - $inventory->max_stock;
                $formatted = manageAmountFormat($balance);
                return $balance > 0 ? "<b class='text-info'>$formatted</b>" : $formatted;
            })
            ->rawColumns(['variance', 'qty_on_hand'])
            ->toJson();
    }

    public function supplierSales()
    {
        $now = now();
        $start7Days = $now->copy()->subDays(7)->format('Y-m-d 00:00:00');
        $start30Days = $now->copy()->subDays(30)->format('Y-m-d 00:00:00');
        $start60Days = $now->copy()->subDays(60)->format('Y-m-d 00:00:00');
        $start90Days = $now->copy()->subDays(90)->format('Y-m-d 00:00:00');
        $to = $now->format('Y-m-d 23:59:59');

        $salesSub = WaStockMove::query()
            ->select([
                DB::raw('ABS(SUM(qauntity))')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->where(function ($query) {
                $query->where('document_no', 'like', 'INV-%')
                    ->orWhere('document_no', 'like', 'RTN-%');
            })
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $smallPacksSub = WaStockMove::query()
            ->select([
                DB::raw('ABS(SUM(qauntity)) / conversion_factor')
            ])
            ->leftJoin('wa_inventory_assigned_items as items', 'items.destination_item_id', '=', 'wa_stock_moves.wa_inventory_item_id')
            ->where('wa_location_and_store_id', request()->location)
            ->where(function ($query) {
                $query->where('document_no', 'like', 'INV-%')
                    ->orWhere('document_no', 'like', 'RTN-%');
            })
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $qohSub = WaStockMove::query()
            ->select([
                DB::raw('SUM(qauntity) as quantity')
            ])
            ->where('wa_location_and_store_id', request()->location)
            ->whereColumn('wa_stock_moves.wa_inventory_item_id', 'wa_inventory_items.id');

        $query = WaInventoryItem::query()
            ->select([
                'wa_inventory_items.id',
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.title',
                'wa_inventory_items.description',
                'wa_inventory_items.wa_inventory_category_id',
            ])
            ->selectSub($qohSub, 'qoh')
            ->selectSub((clone $salesSub)->whereBetween('created_at', [$start7Days, $to]), 'sales_7_days')
            ->selectSub((clone $smallPacksSub)->whereBetween('wa_stock_moves.created_at', [$start7Days, $to]), 'small_7_days')
            ->selectSub((clone $salesSub)->whereBetween('created_at', [$start30Days, $to]), 'sales_30_days')
            ->selectSub((clone $smallPacksSub)->whereBetween('wa_stock_moves.created_at', [$start30Days, $to]), 'small_30_days')
            ->selectSub((clone $salesSub)->whereBetween('created_at', [$start60Days, $to]), 'sales_60_days')
            ->selectSub((clone $smallPacksSub)->whereBetween('wa_stock_moves.created_at', [$start60Days, $to]), 'small_60_days')
            ->selectSub((clone $salesSub)->whereBetween('created_at', [$start90Days, $to]), 'sales_90_days')
            ->selectSub((clone $smallPacksSub)->whereBetween('wa_stock_moves.created_at', [$start90Days, $to]), 'small_90_days')
            ->with([
                'category'
            ])
            ->whereIn('wa_inventory_items.stock_id_code', request()->items)
            ->where('wa_inventory_items.status', 1)
            ->whereHas('suppliers', function ($query) {
                $query->where('wa_suppliers.id', request()->supplier);
            });

        return DataTables::eloquent($query)
            ->editColumn('qoh', function ($inventory) {
                return $inventory->qoh == 0 ? "<b class='text-danger'>" . manageAmountFormat($inventory->qoh) . "</b>" : manageAmountFormat($inventory->qoh);
            })
            ->editColumn('sales_7_days', function ($sale) {
                return manageAmountFormat($sale->sales_7_days + $sale->small_7_days);
            })
            ->editColumn('sales_30_days', function ($sale) {
                return manageAmountFormat($sale->sales_30_days + $sale->small_30_days);
            })
            ->editColumn('sales_60_days', function ($sale) {
                return manageAmountFormat($sale->sales_60_days + $sale->small_60_days);
            })
            ->editColumn('sales_90_days', function ($sale) {
                return manageAmountFormat($sale->sales_90_days + $sale->small_90_days);
            })
            ->rawColumns(['qoh'])
            ->toJson();
    }

    public function supplierReturns()
    {
        $items = WaInventoryItem::whereIn('stock_id_code', request()->items)->pluck('id')->toArray();

        $query = WaReturnDemand::withCount('returnDemandItems')
            ->where('wa_supplier_id', request()->supplier)
            ->whereHas('returnDemandItems', function ($query) use ($items) {
                $query->where('wa_inventory_item_id', $items);
            });

        return  DataTables::eloquent($query)
            ->editColumn('created_at', function ($return) {
                return $return->created_at->format('d/m/Y');
            })
            ->editColumn('demand_amount', function ($return) {
                return number_format($return->demand_amount, 2);
            })
            ->editColumn('vat_amount', function ($return) {
                return number_format($return->vat_amount, 2);
            })
            ->editColumn('edited_demand_amount', function ($return) {
                return number_format($return->edited_demand_amount, 2);
            })
            ->addColumn('action', function ($return) {
                return '<a href="' . route('return-demands.details', $return->id) . '" target="_blank">
                    <i class="fas fa-eye text-primary fa-lg" style="color: #337ab7;" title="View"></i></a>';
            })
            ->with('total_amount', function () use ($query) {
                return manageAmountFormat($query->sum('wa_return_demands.demand_amount'));
            })
            ->with('total_edited_amount', function () use ($query) {
                return manageAmountFormat($query->sum('wa_return_demands.edited_demand_amount'));
            })
            ->toJson();
    }

    public function supplierPricedrops()
    {
        $query = WaDemand::with('user.userRestaurent')
            ->withCount('demandItems')
            ->where('wa_supplier_id', request()->supplier)
            ->where('merged', false);

        return  DataTables::eloquent($query)
            ->editColumn('created_at', function ($return) {
                return $return->created_at->format('d/m/Y');
            })
            ->editColumn('vat_amount', function ($demand) {
                return number_format($demand->vat_amount, 2);
            })
            ->editColumn('demand_amount', function ($demand) {
                return number_format($demand->demand_amount, 2);
            })
            ->editColumn('edited_demand_amount', function ($demand) {
                return number_format($demand->edited_demand_amount, 2);
            })
            ->editColumn('approved', function ($demand) {
                return $demand->status ? 'Yes' : 'No';
            })
            ->addColumn('action', function ($demand) {
                return '<a href="' . route('demands.item-demands.details.new', $demand) . '" target="_blank" title="View Details" data-toggle="tooltip">
                    <i class="fa fa-eye"></i>
                </a>';
            })
            ->with('total_amount', function () use ($query) {
                return manageAmountFormat($query->sum('demand_amount'));
            })
            ->with('total_edited_amount', function () use ($query) {
                return manageAmountFormat($query->sum('edited_demand_amount'));
            })
            ->toJson();
    }

    public function supplierDiscounts()
    {
        $query = TradeDiscount::query()
            ->select([
                'trade_discounts.*',
                'agreements.discount_type',
                'demand_no'
            ])
            ->with([
                'preparedBy'
            ])
            ->join('trade_agreement_discounts as agreements', 'agreements.id', 'trade_discounts.trade_agreement_discount_id')
            ->leftJoin('trade_discount_demand_items as items', 'items.trade_discount_id', 'trade_discounts.id')
            ->leftJoin('trade_discount_demands as demands', 'demands.id', 'items.trade_discount_demand_id')
            ->where('trade_discounts.supplier_id', request()->supplier)
            ->whereIn('invoice_id', request()->invoices);

        return  DataTables::eloquent($query)
            ->editColumn('invoice_amount', function ($discount) {
                return number_format($discount->invoice_amount, 2);
            })
            ->editColumn('amount', function ($discount) {
                return number_format($discount->amount, 2);
            })
            ->editColumn('approved_amount', function ($discount) {
                return number_format($discount->approved_amount, 2);
            })
            ->editColumn('status', function ($discount) {
                return $discount->status ? 'Yes' : 'No';
            })
            ->addColumn('action', function ($discount) {
                return '<a href="' . route('trade-discounts.show', $discount) . '" target="_blank" title="View Details" data-toggle="tooltip">
                    <i class="fa fa-eye"></i>
                </a>';
            })
            ->with('total_amount', function () use ($query) {
                return manageAmountFormat($query->sum('trade_discounts.amount'));
            })
            ->with('total_invoice_amount', function () use ($query) {
                return manageAmountFormat($query->sum('trade_discounts.invoice_amount'));
            })
            ->with('total_approved_amount', function () use ($query) {
                return manageAmountFormat($query->sum('approved_amount'));
            })
            ->toJson();
    }

    public function supplierInvoiceVariance()
    {
        $grnSub = WaGrn::query()
            ->select([
                'grn_number',
                'delivery_date',
                DB::raw('SUM((invoice_info->"$.order_price" * invoice_info->"$.qty" - IFNULL(invoice_info->"$.total_discount", 0)) * invoice_info->"$.vat_rate" / (100 + invoice_info->"$.vat_rate")) AS vat_amount'),
                DB::raw('SUM(invoice_info->"$.order_price" * invoice_info->"$.qty"- IFNULL(invoice_info->"$.total_discount", 0)) AS total_amount'),
            ])
            ->groupBy('wa_grns.grn_number');


        $query = DB::table('wa_supplier_invoices as invoices')
            ->select([
                'invoices.grn_number',
                'invoices.supplier_invoice_number',
                'grns.delivery_date',
                DB::raw('ROUND(grns.vat_amount,2) AS grn_vat_amount'),
                'grns.total_amount AS grn_total_amount',
                DB::raw("DATE_FORMAT(invoices.supplier_invoice_date, '%Y-%m-%d') AS supplier_invoice_date"),
                DB::raw('ROUND(invoices.vat_amount,2) AS vat_amount'),
                'invoices.amount',
                DB::raw('grns.total_amount - invoices.amount as variance'),
            ])
            ->joinSub($grnSub, 'grns', 'grns.grn_number', 'invoices.grn_number')
            ->whereIn('invoices.id', request()->items);

        return DataTables::of($query)
            ->editColumn('grn_vat_amount', function ($invoice) {
                return manageAmountFormat($invoice->grn_vat_amount);
            })
            ->editColumn('grn_total_amount', function ($invoice) {
                return manageAmountFormat($invoice->grn_total_amount);
            })
            ->editColumn('vat_amount', function ($invoice) {
                return manageAmountFormat($invoice->vat_amount);
            })
            ->editColumn('amount', function ($invoice) {
                return manageAmountFormat($invoice->amount);
            })
            ->editColumn('variance', function ($invoice) {
                return manageAmountFormat($invoice->variance);
            })
            ->toJson();
    }

    public function supplierInvoiceAging()
    {
        $creditNoteSub = FinancialNote::query()
            ->select([
                'wa_supp_tran_id',
                DB::raw("ROUND(SUM(CASE WHEN type = 'credit' THEN -amount + withholding_amount ELSE amount END),2) As note_amount"),
            ])
            ->whereNotNull('wa_supp_tran_id')
            ->groupBy('wa_supp_tran_id');

        $query = DB::table('wa_supplier_invoices as invoices')
            ->select([
                'invoices.supplier_invoice_date',
                'invoices.grn_number',
                'invoices.supplier_invoice_number',
                'invoices.cu_invoice_number',
                'trans.vat_amount',
                'trans.total_amount_inc_vat',
                DB::raw('DATEDIFF(CURDATE(), supplier_invoice_date) AS days_pending'),
                'notes.note_amount',
                'trans.withholding_amount',
                DB::raw('trans.total_amount_inc_vat - IFNULL(notes.note_amount,0) - trans.withholding_amount as payable_amount'),
            ])
            ->leftJoin('wa_supp_trans as trans', 'trans.id', 'invoices.wa_supp_tran_id')
            ->leftJoin('payment_voucher_items as payment', function ($query) {
                $query->on('payment.payable_id', 'invoices.wa_supp_tran_id')
                    ->where('payable_type', 'invoice');
            })
            ->leftJoinSub($creditNoteSub, 'notes', 'notes.wa_supp_tran_id', 'invoices.wa_supp_tran_id')
            ->where('invoices.supplier_id', request()->supplier)
            ->whereNull('payment.payable_id');

        return DataTables::of($query)
            ->editColumn('vat_amount', function ($invoice) {
                return manageAmountFormat($invoice->vat_amount);
            })
            ->editColumn('withholding_amount', function ($invoice) {
                return manageAmountFormat($invoice->withholding_amount);
            })
            ->editColumn('total_amount_inc_vat', function ($invoice) {
                return manageAmountFormat($invoice->total_amount_inc_vat);
            })
            ->editColumn('note_amount', function ($invoice) {
                return manageAmountFormat($invoice->note_amount);
            })
            ->editColumn('payable_amount', function ($invoice) {
                return manageAmountFormat($invoice->payable_amount);
            })
            ->with('total_payable', function () use ($query) {
                return manageAmountFormat($query->get()->sum('payable_amount'));
            })
            ->toJson();
    }

    public function stockMovements()
    {
        $query = DB::table('wa_stock_moves as moves')
            ->select([
                'moves.stock_id_code',
                'moves.created_at',
                'moves.qauntity',
                'moves.new_qoh',
                'moves.document_no',
                'moves.refrence',
                'items.title',
                'users.name as user_name',
                'locations.location_name',
            ])
            ->join('users', 'users.id', 'moves.user_id')
            ->join('wa_inventory_items as items', 'items.id', 'moves.wa_inventory_item_id')
            ->join('wa_location_and_stores as locations', 'locations.id', 'moves.wa_location_and_store_id')
            ->whereIn('document_no', request()->items);

        return DataTables::of($query)
            ->toJson();
    }

    public function update(Request $request, $code)
    {
        if (!can('edit', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $this->validate($request, [
            'account' => 'required',
            'payment_mode' => 'required',
            'transactions' => 'required',
            'cheques' => 'required',
        ], [
            'items' => 'Please select at least one item',
            'cheques' => 'At least on check is required'
        ]);

        $voucher = PaymentVoucher::where('number', $code)->firstOrFail();
        if ($voucher->isProcessed()) {
            return redirect()->route('payment-vouchers.index')
                ->withErrors('A processed payment voucher cannot be edited');
        }

        $user = getLoggeduserProfile();
        $supplier = $voucher->supplier;

        DB::beginTransaction();

        try {
            $transactions = WaSuppTran::wherein('id', collect(json_decode($request->transactions))->pluck('id'))->get();
            $cheques = PaymentVoucherCheque::wherein('number', collect(json_decode($request->cheques))->pluck('cheq_number'))->get();

            $voucher->update([
                'wa_bank_account_id' => $request->account,
                'wa_payment_mode_id' => $request->payment_mode,
                'narration' => $request->narration,
                'amount' => $amountPaid = $cheques->sum('amount'),
                "prepared_by" => $user->id,
            ]);

            if ($voucher->isPending()) {
                foreach ($cheques as $cheque) {
                    $cheque->update([
                        'payment_voucher_id' => $voucher->id,
                        'wa_supplier_code' => $supplier->supplier_code,
                    ]);
                }

                $voucher->voucherItems()->delete();

                // NB: Pass amount paid by reference
                $transactions->each(function ($transaction) use ($supplier, $voucher, &$amountPaid, $request) {
                    $withholding_amount = $supplier->tax_withhold ? ceil($transaction->vat_amount * (2 / 16)) : 0;
                    $professional_withholding = $supplier->professional_withholding ? ceil($transaction->total_amount_inc_vat * 0.05) : 0;
                    $toPay = collect(json_decode($request->transactions))->where('id', $transaction->id)->first()->amount;

                    $balance = $amountPaid - $toPay;
                    if ($balance == 0) {
                        $transaction->update([
                            'allocated_amount' =>  $transaction->allocated_amount + $toPay,
                            'settled' => true
                        ]);
                    } else if ($balance > 0) {
                        $transaction->update([
                            'allocated_amount' =>  $transaction->allocated_amount +  $toPay,
                            'settled' => true
                        ]);

                        $amountPaid -= $toPay;
                    } else if ($amountPaid > 0 && $balance < 0) {
                        $toPay = $amountPaid;
                        $transaction->update([
                            'allocated_amount' =>  $transaction->allocated_amount +  $toPay,
                        ]);
                    }

                    $transaction->update([
                        'withholding_amount' => $withholding_amount,
                        'professional_withholding' => $professional_withholding,
                    ]);

                    PaymentVoucherItem::create([
                        'payment_voucher_id' => $voucher->id,
                        'payable_type' => 'invoice',
                        'payable_id' => $transaction->id,
                        'amount' => $toPay
                    ]);
                });
            }

            DB::commit();

            if ($request->input('action') == 'print') {
                return redirect()->route('payment-vouchers.print_pdf', $voucher->id);
            }

            Session::flash('success', 'Voucher updated successfully.');

            return redirect()->route('maintain-suppliers.vendor_centre', $supplier->supplier_code);
        } catch (Exception $e) {
            DB::rollback();

            return redirect()->route('maintain-suppliers.payment_vouchers.create', $supplier->supplier_code)
                ->withErrors('An error occurred: ' . $e->getMessage());
        }
    }

    public function approve(PaymentVoucher $voucher)
    {
        if (!can('edit', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $document_no = getCodeWithNumberSeries('CREDITORS_PAYMENT');

        $voucher->update([
            'document_number' => $document_no,
            'status' => PaymentVoucher::APPROVED
        ]);

        updateUniqueNumberSeries('CREDITORS_PAYMENT', $document_no);

        Session::flash('success', 'Voucher approved successfully.');

        return redirect()->route('payment-vouchers.index');
    }

    public function decline(PaymentVoucher $voucher)
    {
        if (!can('edit', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        if ($voucher->isProcessed()) {
            return redirect()->route('payment-vouchers.index')
                ->withErrors('A processed payment voucher cannot be declined');
        }

        DB::beginTransaction();

        try {
            foreach ($voucher->voucherItems as $item) {
                $item->payable->update([
                    'allocated_amount' => $item->payable->allocated_amount - $item->amount,
                    'settled' => false
                ]);
            }

            $voucher->voucherItems()->delete();

            $voucher->cheques()->delete();

            $voucher->delete();

            DB::commit();

            Session::flash('success', 'Voucher declined successfully.');

            return redirect()->route('payment-vouchers.index');
        } catch (Exception $e) {
            DB::rollback();

            Session::flash('error', 'An error occurred');

            return redirect()->route('payment-vouchers.index');
        }
    }

    public function printPdf($voucherId)
    {
        $voucher = PaymentVoucher::query()
            ->with([
                'supplier',
                'account',
                'voucherItems',
                'cheques'
            ])
            ->findOrFail($voucherId);

        $settings = getAllSettings();

        $branch = Restaurant::find(10);

        $qr_code = QrCode::generate(
            $voucher->number . " - " . $voucher->supplier->name . " - " . manageAmountFormat($voucher->amount) . " - " . $voucher->created_at->format('d/m/Y H:i'),
        );

        $pdf = Pdf::loadView('admin.payment_vouchers.print', compact('voucher', 'settings', 'branch', 'qr_code'));

        return $pdf->stream('payment_voucher_' . date('Y-m-d-H-i-s') . '.pdf');
    }

    public function printRemittance($voucherId)
    {
        $voucher = PaymentVoucher::query()
            ->with('supplier', 'account', 'voucherItems.transaction.purchaseOrder', 'cheques')
            ->findOrFail($voucherId);

        $pdf = Pdf::loadView('admin.maintainsuppliers.supplier_payment_Remittance_Advice_slip', compact('voucher'));

        return $pdf->stream('payment_remittance_' . date('Y-m-d-H-i-s') . '.pdf');
    }

    public function confirm(PaymentVoucher $voucher)
    {
        if (!can('confirm-details', $this->model) && !can('approve-confirmation', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        if (is_null($voucher->confirmed_by) && can('confirm-details', $this->model)) {
            $voucher->update([
                'confirmed_by' => auth()->user()->id,
                'confirmed_at' => now(),
            ]);

            Session::flash('success', 'Voucher confirmed successfully.');

            return redirect()->route('payment-vouchers.show', $voucher);
        }

        if (is_null($voucher->confirmation_approval_by) && can('approve-confirmation', $this->model)) {
            $voucher->update([
                'confirmation_approval_by' => auth()->user()->id,
                'confirmation_approval_at' => now(),
            ]);

            Session::flash('success', 'Voucher confirmation approved successfully.');

            return redirect()->route('payment-vouchers.show', $voucher);
        }

        return redirect()->route('payment-vouchers.show', $voucher);
    }
}
