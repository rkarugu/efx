<?php

namespace App\Http\Controllers\Admin;

use App\Models\TradeAgreement;
use App\Models\TradeAgreementDiscount;
use App\Models\WaSupplierDistributor;
use App\User;
use App\WaDemand;
use App\Model\WaGrn;
use App\ReturnedGrn;
use App\Model\WaSupplier;
use App\Model\WaSuppTran;
use App\ItemSupplierDemand;
use App\FinancialNote;
use Illuminate\Http\Request;
use App\Models\WaReturnDemand;
use App\Model\WaLocationAndStore;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\Interfaces\LocationStoreInterface;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryCategory;
use App\Model\WaNumerSeriesCode;
use App\PaymentVoucher;
use App\WaSupplierInvoice;
use App\Model\WaStockMove;
use App\Models\AdvancePaymentAllocation;
use App\PaymentVoucherItem;
use App\Services\Inventory\TurnoverPurchases;
use App\Services\Inventory\TurnoverSales;

class VendorCentreController extends Controller
{
    protected $model;
    protected $base_route;
    protected $resource_folder;
    protected $base_title;
    protected $title;
    private LocationStoreInterface $locationRepository;

    public function __construct(LocationStoreInterface $locationRepository)
    {
        $this->model = 'maintain-suppliers';
        $this->base_route = 'maintain-suppliers';
        $this->resource_folder = 'admin.vendor_center';
        $this->base_title = 'Vendor Centre';
        $this->locationRepository = $locationRepository;
    }

    public function show(Request $request, $code)
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $model = $this->model;

        $title = 'Vendor Centre';
        $supplier = WaSupplier::where('supplier_code', $code)->firstOrFail();
        $supplier->setAttribute('balance', $supplier->getAllTrans->sum('total_amount_inc_vat'));
        $categories = \App\Model\WaInventoryCategory::get();
        $locations = WaLocationAndStore::where('is_physical_store', '1')
            ->where('location_name', '<>', 'THIKA')->get();
        $this->title =  $supplier->supplier_code;

        $breadcum = ['Maintain Suppliers' => route('maintain-suppliers.index'), $this->title => route($model . '.index')];

        return view('admin.vendor_centre.show', compact('title', 'supplier', 'model', 'breadcum', 'categories', 'locations'));
    }

    public function payables($code)
    {
        $supplier = WaSupplier::where('supplier_code', $code)->firstOrFail();
        $seriesCode = \App\Model\WaNumerSeriesCode::where('module', 'SUPPLIER_INVOICE_NO')->first();

        $creditNoteSub = FinancialNote::query()
            ->select([
                'wa_supp_tran_id',
                DB::raw("ROUND(SUM(CASE WHEN type = 'credit' THEN -amount + withholding_amount ELSE amount END),2) As note_amount"),
            ])
            ->whereNotNull('wa_supp_tran_id')
            ->groupBy('wa_supp_tran_id');

        $payments = PaymentVoucherItem::query()
            ->select([
                'payable_id AS wa_supp_trans_id',
                DB::raw('ROUND(SUM(amount),2) As paid_amount'),
            ])
            ->where('payable_type', 'invoice')
            ->groupBy('payable_id');

        $payables = WaSuppTran::query()
            ->select([
                'wa_supp_trans.*',
                'notes.note_amount',
                'payments.paid_amount',
                DB::raw('(total_amount_inc_vat - IFNULL(notes.note_amount,0)) as payable_amount'),
            ])
            ->with([
                'user',
                'invoice',
                'purchaseOrder',
            ])
            ->leftJoinSub($creditNoteSub, 'notes', 'notes.wa_supp_tran_id', 'wa_supp_trans.id')
            ->leftJoinSub($payments, 'payments', 'payments.wa_supp_trans_id', 'wa_supp_trans.id')
            ->whereHas('invoice')
            ->where(function ($q) use ($supplier, $code) {
                $q->where('wa_supp_trans.supplier_no', $supplier->id)
                    ->orWhere('wa_supp_trans.supplier_no', $code);
            })
            ->when(request()->status == 'pending', function ($payables) {
                $payables->whereDoesntHave('payments')
                    ->whereDoesntHave('allocation');
            })
            ->when(request()->status == 'processing', function ($payables) {
                $payables->whereHas('payments', function ($query) {
                    $query->whereHas('voucher', function ($query) {
                        $query->processing();
                    });
                });
            })
            ->when(request()->status == 'completed', function ($payables) {
                $payables->where(function ($q) {
                    $q->whereHas('payments', function ($query) {
                        $query->whereHas('voucher', function ($query) {
                            $query->processed();
                        });
                    })->orWhereHas('allocation')
                    ->orWhere('wa_supp_trans.settled', 1);
                });
            })
            ->when(request()->filled('from') && request()->filled('to'), function ($payables) {
                $payables->whereBetween('wa_supp_trans.created_at', [request()->from . ' 00:00:00', request()->to . ' 23:59:59']);
            })
            ->groupBy('wa_supp_trans.id');

        return DataTables::eloquent($payables)
            ->editColumn('trans_date', function ($payable) {
                return $payable->trans_date?->format('Y-m-d');
            })
            ->editColumn('invoice.grn_number', function ($payable) {
                return view('admin.vendor_centre.link', [
                    'url' => route('completed-grn.printToPdf', $payable->invoice->grn_number),
                    'text' => $payable->invoice->grn_number,
                ]);
            })
            ->editColumn('user.name', function ($payable) {
                return is_null($payable->user->name) ? '' : $payable->user->name;
            })
            ->editColumn('vat_amount', function ($payable) {
                return manageAmountFormat($payable->vat_amount);
            })
            ->editColumn('withholding_amount', function ($payable) {
                return manageAmountFormat($payable->withholding_amount);
            })
            ->editColumn('total_amount_inc_vat', function ($payable) {
                return manageAmountFormat($payable->total_amount_inc_vat);
            })
            ->editColumn('note_amount', function ($payable) {
                return manageAmountFormat($payable->note_amount);
            })
            ->editColumn('payable_amount', function ($payable) {
                return manageAmountFormat($payable->payable_amount);
            })
            ->editColumn('paid_amount', function ($payable) {
                return manageAmountFormat($payable->paid_amount);
            })
            ->with('total_payable', function () use ($payables) {
                return manageAmountFormat($payables->get()->sum('payable_amount'));
            })
            ->with('total_paid', function () use ($payables) {
                return manageAmountFormat($payables->get()->sum('paid_amount'));
            })
            ->toJson();
    }

    public function grn($id)
    {
        $grns = WaGrn::query()
            ->select([
                'wa_grns.*',
                DB::raw('SUM((CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.order_price")) AS DECIMAL(18,2)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.qty")) AS DECIMAL(18,2)) - IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.total_discount")) AS DECIMAL(18,2)), 0)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.vat_rate")) AS DECIMAL(18,2)) / (100 + CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.vat_rate")) AS DECIMAL(18,2)))) AS vat_amount'),
                DB::raw('SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.order_price")) AS DECIMAL(18,2)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.qty")) AS DECIMAL(18,2)) - IFNULL(CAST(JSON_UNQUOTE(JSON_EXTRACT(invoice_info, "$.total_discount")) AS DECIMAL(18,2)), 0)) AS total_amount'),
            ])
            ->with([
                'purchaseOrder',
                'purchaseOrder.getrelatedEmployee',
                'purchaseOrder.getBranch',
                'purchaseOrder.getStoreLocation',
                'purchaseOrder.getDepartment',
                'purchaseOrder.uom',
                'purchaseOrder.getRelatedGlTran'
            ])
            ->join('wa_purchase_orders', function ($e) {
                $e->on('wa_purchase_orders.id', 'wa_grns.wa_purchase_order_id');
            })
            ->where('wa_grns.wa_supplier_id', $id)
            ->where('wa_purchase_orders.supplier_archived', 0)
            ->whereNot('wa_grns.return_status', 'Returned')
            ->when(request()->filled('from') && request()->filled('to'), function ($query) {
                $query->whereBetween('delivery_date', [request()->from . ' 00:00:00', request()->to . ' 23:59:59']);
            })
            ->when(request()->status == 'pending', function ($query) {
                $query->whereDoesntHave('invoice');
            })
            ->when(request()->status == 'completed', function ($query) {
                $query->whereHas('invoice');
            })->groupBy('wa_grns.grn_number');

        return DataTables::eloquent($grns)
            ->editColumn('grn_number', function ($grn) {
                return view('admin.vendor_centre.link', [
                    'url' => route('completed-grn.printToPdf', $grn->grn_number),
                    'text' => $grn->grn_number,
                ]);
            })
            ->editColumn('purchase_order.purchase_no', function ($grn) {
                return view('admin.vendor_centre.link', [
                    'url' => route('purchase-orders.exportToPdf', ['slug' => $grn->purchaseOrder->purchase_no]),
                    'text' => $grn->purchaseOrder->purchase_no,
                ]);
            })
            ->editColumn('total_amount', function ($grn) {
                return manageAmountFormat($grn->total_amount);
            })
            ->with('total', function () use ($grns) {
                $total_amount = $grns->get()->sum('total_amount');

                return manageAmountFormat($total_amount);
            })
            ->toJson();
    }

    public function payments($code)
    {
        $supplier = WaSupplier::where('supplier_code', $code)->first();
        $payments = PaymentVoucher::query()
            ->select([
                'payment_vouchers.id',
                'payment_vouchers.wa_payment_mode_id',
                'payment_vouchers.wa_supplier_id',
                'payment_vouchers.number',
                'payment_vouchers.updated_at',
                'payment_vouchers.amount',
                'files.file_no',
                'files.id as bank_file_id',
                'accounts.account_name',
            ])
            ->with([
                'paymentMode',
                'supplier'
            ])
            ->join('wa_bank_file_items AS items', 'items.payment_voucher_id', 'payment_vouchers.id')
            ->join('wa_bank_files AS files', 'files.id', 'items.wa_bank_file_id')
            ->join('wa_bank_accounts AS accounts', 'accounts.id', 'files.wa_bank_account_id')
            ->when(request()->filled('from') && request()->filled('to'), function ($payments) {
                $payments->whereBetween('files.created_at', [request()->from . ' 00:00:00', request()->to . ' 23:59:59']);
            })
            ->where('wa_supplier_id', $supplier->id);

        return DataTables::eloquent($payments)
            ->editColumn('updated_at', function ($payment) {
                return $payment->updated_at->format('Y-m-d');
            })
            ->editColumn('number', function ($payment) {
                return view('admin.vendor_centre.link', [
                    'url' => route('payment-vouchers.print_pdf', ['voucher' => $payment->id]),
                    'text' => $payment->number
                ]);
            })
            ->editColumn('file_no', function ($payment) {
                return view('admin.vendor_centre.link', [
                    'url' => route('bank-files.download', ['file' => $payment->bank_file_id]),
                    'text' => $payment->file_no
                ]);
            })
            ->editColumn('amount', function ($payment) {
                return manageAmountFormat($payment->amount);
            })
            ->toJson();
    }

    public function statement($code)
    {
        $supplier = WaSupplier::where('supplier_code', $code)->firstOrFail();
        $from = request()->filled('from') ? request()->from . ' 00:00:00' : now()->subDays(30)->format('Y-m-d 00:00:00');
        $to = request()->filled('to') ? request()->to . ' 23:59:59' : now()->format('Y-m-d 23:59:59');

        $query = WaSuppTran::query()
            ->select('*')
            ->selectRaw("CONCAT_WS('/', suppreference, description) as description")
            ->selectRaw("(CASE WHEN total_amount_inc_vat > 0 THEN total_amount_inc_vat ELSE 0 END) as debit")
            ->selectRaw("(CASE WHEN total_amount_inc_vat < 0 THEN total_amount_inc_vat ELSE 0 END) as credit")
            ->selectRaw("(SELECT SUM(prev.total_amount_inc_vat) FROM wa_supp_trans as prev where (prev.supplier_no = '{$supplier->id}' OR prev.supplier_no = '$code') AND prev.id  < wa_supp_trans.id) AS opening_balance")
            ->where(function ($q) use ($supplier, $code) {
                $q->where('supplier_no', $supplier->id)
                    ->orWhere('supplier_no', $code);
            })
            ->whereBetween('created_at', [$from, $to]);

        $openingBalance = WaSuppTran::query()
            ->where(function ($q) use ($supplier, $code) {
                $q->where('supplier_no', $supplier->id)
                    ->orWhere('supplier_no', $code);
            })
            ->where('created_at', '<', $from)
            ->sum('total_amount_inc_vat');

        return DataTables::eloquent($query)
            ->editColumn('created_at', function ($record) {
                return $record->created_at->format('Y-m-d');
            })
            ->addColumn('memo', function ($record) {
                $pos = strrpos($record->document_no, '-');
                $prefix = substr($record->document_no, 0, $pos);
                $module = WaNumerSeriesCode::where('code', $prefix)->first();

                $invoice = WaSupplierInvoice::where('supplier_invoice_number', $record->suppreference)->first();
                if (!is_null($invoice)) {
                    return 'INVOICE';
                }

                if (is_null($module)) {
                    return '';
                }

                if ($prefix == 'FN') {
                    return FinancialNote::where('note_no', $record->document_no)->first()->type . ' NOTE';
                }

                return strtoupper($module->description);
            })
            ->editColumn('document_no', function ($record) {
                $invoice = WaSupplierInvoice::where('supplier_invoice_number', $record->suppreference)->first();
                if (!is_null($invoice)) {
                    return $invoice->invoice_number;
                }

                return $record->document_no;
            })
            ->editColumn('suppreference', function ($record) {
                $prefix = substr($record->document_no, 0, 3);
                if ($prefix == 'PMV') {
                    $voucher = PaymentVoucher::where('number', $record->document_no)->first();
                    return $voucher->number."/".$voucher->account->account_name;
                }
                return $record->suppreference;
            })
            ->editColumn('debit', function ($record) {
                return manageAmountFormat($record->debit);
            })
            ->editColumn('credit', function ($record) {
                return manageAmountFormat($record->credit);
            })
            ->addColumn('running_balance', function ($record) {
                return manageAmountFormat($record->opening_balance + $record->total_amount_inc_vat);
            })
            ->with('total', function () use ($query, $openingBalance) {
                return  manageAmountFormat($openingBalance + $query->sum('total_amount_inc_vat'));
            })
            ->with('opening_balance', function () use ($openingBalance) {
                return manageAmountFormat($openingBalance);
            })
            ->toJson();
    }

    public function priceList($id)
    {
        $items = WaInventoryItem::query()
            ->with([
                'packSize'
            ])
            ->whereHas('suppliers', function ($query) {
                $query->where('wa_supplier_id', request()->id);
            });

        return DataTables::eloquent($items)
            ->addIndexColumn()
            ->editColumn('price_list_cost', function ($item) {
                return manageAmountFormat($item->price_list_cost);
            })
            ->editColumn('margin_type', function ($item) {
                return $item->margin_type ? 'Percentage' : 'Value';
            })
            ->editColumn('standard_cost', function ($item) {
                return manageAmountFormat($item->standard_cost);
            })
            ->editColumn('selling_price', function ($item) {
                return manageAmountFormat($item->selling_price);
            })
            ->editColumn('percentage_margin', function ($item) {
                return manageAmountFormat($item->percentage_margin);
            })
            ->toJson();
    }

    public function demands($id)
    {
        $items = ItemSupplierDemand::query()
            ->join('wa_inventory_items as t', 't.id', '=', 'item_supplier_demands.wa_inventory_item_id')
            // ->selectRaw('CONCAT_WS(t.title, t.stock_id_code) as description')
            ->select([
                'item_supplier_demands.*',
                't.title',
                't.stock_id_code',
                'item_supplier_demands.current_cost',
                'item_supplier_demands.demand_quantity'


            ])
            ->where('wa_supplier_id', $id);

        return DataTables::eloquent($items)
            ->addIndexColumn()
            ->editColumn('created_at', function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->editColumn('valuation_before', function ($item) {
                return format_amount_with_currency($item->current_cost * $item->demand_quantity);
            })
            ->editColumn('current_cost', function ($item) {
                return format_amount_with_currency($item->current_cost);
            })
            ->editColumn('new_cost', function ($item) {
                return format_amount_with_currency($item->new_cost);
            })
            ->editColumn('valuation_after', function ($item) {
                return format_amount_with_currency($item->new_cost * $item->demand_quantity);
            })
            ->editColumn('demand', function ($item) {
                return format_amount_with_currency(($item->current_cost * $item->demand_quantity) - ($item->new_cost * $item->demand_quantity));
            })
            ->toJson();
    }
    public function refactoredDemands($id)
    {
        $items = WaDemand::query()
            ->with('getDemandItem', 'getSupplier', 'getUser')
            ->where('wa_supplier_id', $id);


        return DataTables::eloquent($items)
            ->addIndexColumn()
            ->editColumn('created_at', function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->editColumn('quantity', function ($item) {
                return count($item->getDemandItem);
            })
            ->editColumn('created_by', function ($item) {
                return $item->getUser->name;
            })
            ->editColumn('demand_amount', function ($item) {
                return format_amount_with_currency($item->demand_amount);
            })
            ->toJson();
    }

    public function returns($id)
    {
        $items = ReturnedGrn::query()
            ->with('user', 'supplier', 'grn')
            ->where('wa_supplier_id', $id);

        return DataTables::eloquent($items)
            ->addIndexColumn()
            ->editColumn('created_at', function ($item) {
                return \Carbon\Carbon::parse($item->created_at)->toFormattedDayDateString();
            })
            ->toJson();
    }

    public function stock_balances()
    {
        $items = $this->locationRepository->getStockBalance();
        if (request()->has('print')) {
            $description = '';
            $title = "Vendor Center :: Stock Balances";
            $type = request()->input('type');
            $model = 'inventory_location_stock_summary';
            $pmodule = $this->model;
            $locations = WaLocationAndStore::where('is_physical_store', '1')
                ->where('location_name', '<>', 'THIKA')->get();

            if (request()->category) {
                $description .= "Category: " . WaInventoryCategory::find(request()->category)->name;
            }
            if (request()->supplier) {
                $description .= "Supplier: " . WaSupplier::find(request()->supplier)->name;
            }

            $items = $items->get();

            $pdf = \Pdf::loadView('admin.maintaininvetoryitems.reports.full', compact('items', 'title', 'type', 'description', 'model', 'pmodule', 'locations'));

            return $pdf->setPaper('a4', 'landscape')
                ->setWarnings(false)
                ->download('inventory_location_stock_summary_' . date('d_m_Y_H_i_s') . '.pdf');
        }
        $json_data = array(
            "draw" => intval(request()->input('draw')),
            "recordsTotal" => intval($items['totalData']),
            "recordsFiltered" => intval($items['totalFiltered']),
            "data" => $items['data'],
            "totals" => $items['totals']
        );
        $data = $items['data'];
        $totals = $items['totals'];
        return DataTables::of($data)
            ->with('totals', function () use ($totals) {
                return $totals;
            })

            ->toJson();
        return response()->json($json_data);

        return DataTables::eloquent($items)
            ->addIndexColumn()
            ->editColumn('created_at', function ($item) {
                return \Carbon\Carbon::parse($item->created_at)->toFormattedDayDateString();
            })
            ->toJson();
    }

    public function turnoverPurchases($supplier_id)
    {
        $data = app(TurnoverPurchases::class)->purchases($supplier_id);

        return response()->json($data);
    }

    public function turnoverSales($supplier_id)
    {
        $data = app(TurnoverSales::class)->sales($supplier_id);

        return response()->json($data);
    }

    public function monthly_demands($supplier_id)
    {

        $trade = TradeAgreement::where('wa_supplier_id', $supplier_id)->where('status', 'Approved')->firstOrFail();
        $discounts = TradeAgreementDiscount::where('trade_agreements_id', $trade->id)->where('discount_type', 'End month Discount')->first();
        $inventory_ids = [];
        $options = json_decode($discounts->other_options, true);
        if ($options) {
            foreach ($options as $key => $option) {
                $inventory_ids[] = $key;
            }
        }
        $parent = WaSupplierDistributor::where('distributors', $supplier_id)->first()?->supplier_id;

        $stockMovesData = WaStockMove::select([
            'wa_inventory_item_id',
            DB::RAW('SUM(qauntity) as tq'),
            DB::RAW('SUM(price * qauntity) as tc')
        ])->whereHas('inventoryItem', function ($query) use ($supplier_id, $parent) {
            $query->whereHas('suppliers', function ($query) use ($supplier_id, $parent) {
                $query->whereIn('wa_suppliers.id', [$supplier_id, $parent]);
            })->whereIn('wa_inventory_items.pack_size_id', [8, 7, 3, 5, 2, 4, 13]);
        })
            ->where(function ($e) {
                $e->where('document_no', 'like', '%GRN%')->orWhere('document_no', 'like', '%RFS%');
            })
            ->whereIn('wa_inventory_item_id', $inventory_ids)
            ->where('qauntity', '>', 0)
            ->whereYear('created_at', now()->year)
            // ->whereMonth('created_at', '04')
            ->groupBy('wa_inventory_item_id')
            ->get();
        $make_collection = [];
        foreach ($stockMovesData as $key => $data) {
            $discount = $options[$data->wa_inventory_item_id];
            if ($discount) {
                $makediscount = 0;
                if ($discounts->discount_value_type == 'Value') {
                    $makediscount = (float)$discount['discount'] * $data->tq;
                } else {
                    $makediscount = $data->tc * (float)$discount['discount'] / 100;
                }
                $make_collection[$data->wa_inventory_item_id] = [
                    'type' => $discounts->discount_value_type,
                    'discount_value' => (float)$discount['discount'],
                    'discount' => $makediscount,
                    'total_quantity' => $data->tq
                ];
            }
        }
        dd($make_collection);
    }
}
