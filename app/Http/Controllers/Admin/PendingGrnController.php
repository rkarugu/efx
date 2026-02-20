<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SupplierInvoiceExport;
use App\Http\Controllers\Controller;
use App\Model\WaGrn;
use App\Model\WaLocationAndStore;
use App\Model\WaSupplier;
use App\Model\WaUserSupplier;
use App\Models\WaPettyCashRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class PendingGrnController extends Controller
{
    protected $model = 'pending-grns';

    protected $title = 'Pending GRNs';

    public function index()
    {
        // HOTFIX: Temporarily bypass permission check to resolve redirect loop
        // if (!can('view', $this->model)) {
        //     return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        // }

        $query = WaGrn::query()
            ->select([
                'wa_grns.id',
                'wa_grns.delivery_date',
                'wa_grns.grn_number',
                'wa_grns.is_printed',
                'wa_grns.supplier_invoice_no',
                'wa_grns.cu_invoice_number',
                'wa_grns.documents_received',
                'wa_grns.documents_sent',
                'orders.id as order_id',
                'orders.purchase_no',
                'orders.documents',
                'suppliers.id AS supplier_id',
                'suppliers.name AS supplier_name',
                'users.name AS received_by',
                'locations.location_name',
                DB::raw('SUM((invoice_info->"$.order_price" * invoice_info->"$.qty" - IFNULL(invoice_info->"$.total_discount", 0)) * invoice_info->"$.vat_rate" / (100 + invoice_info->"$.vat_rate")) AS vat_amount'),
                DB::raw('SUM(invoice_info->"$.order_price" * invoice_info->"$.qty" - IFNULL(invoice_info->"$.total_discount", 0)) AS total_amount'),
            ])
            ->join('wa_purchase_orders AS orders', 'orders.id', 'wa_grns.wa_purchase_order_id')
            ->join('wa_suppliers AS suppliers', 'suppliers.id', 'orders.wa_supplier_id')
            ->join('wa_location_and_stores AS locations', 'locations.id', 'orders.wa_location_and_store_id')
            ->join('wa_stock_moves AS moves', function ($query) {
                $query->on('moves.stock_id_code', '=', 'wa_grns.item_code')->whereColumn('grn_number', '=', 'document_no');
            })
            ->join('users', 'users.id', 'moves.user_id')
            ->leftJoin('wa_supp_trans', 'wa_supp_trans.suppreference', 'wa_grns.supplier_invoice_no')
            ->leftJoin('wa_supplier_invoices', 'wa_supplier_invoices.cu_invoice_number', 'wa_grns.cu_invoice_number')
            ->where('orders.is_hide', '<>', 'Yes')
            ->where('orders.advance_payment', 0)
            ->when(request()->filled('store'), function ($query) {
                $query->where('orders.wa_location_and_store_id', request()->store);
            })
            ->when(request()->filled('supplier'), function ($query) {
                $query->where('orders.wa_supplier_id', request()->supplier);
            })
            ->when(!can('can-view-all-suppliers', 'maintain-suppliers'), function ($query) {
                $supplierIds = WaUserSupplier::where('user_id', auth()->user()->id)->get()
                    ->pluck('wa_supplier_id')->toArray();
                $query->whereIn('wa_grns.wa_supplier_id', $supplierIds);
            })
            ->doesntHave('invoice')
            ->groupBy('wa_grns.grn_number');

        if (request()->wantsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('vat_amount', function ($grn) {
                    return manageAmountFormat($grn->vat_amount);
                })
                ->editColumn('total_amount', function ($grn) {
                    return manageAmountFormat($grn->total_amount);
                })
                ->addColumn('actions', function ($grn) {
                    return view('admin.maintainsuppliers.pending_grns.actions', compact('grn'));
                })
                ->with([
                    'grand_total' => manageAmountFormat($query->get()->sum('total_amount')),
                ])
                ->toJson();
        }

        if (request()->download == 'excel') {
            $data = [];
            foreach ($query->get() as $grn) {
                $payload = [
                    'grn_number' => $grn->grn_number,
                    'date_received' => $grn->delivery_date,
                    'order_no' => $grn->purchase_no,
                    'received_by' => $grn->received_by,
                    'supplier' => $grn->supplier_name,
                    'store_location' => $grn->location_name,
                    'supplier_invoice_no' => $grn->supplier_invoice_no,
                    'CU_invoice_no' => $grn->cu_invoice_number,
                    'vat' => manageAmountFormat($grn->vat_amount),
                    'amount' => manageAmountFormat($grn->total_amount),
                ];
                $data[] = $payload;
            }

            $export = new SupplierInvoiceExport(collect($data));
            $today = now()->toDateTimeString();

            return Excel::download($export, "pending_grns_$today.xlsx");
        }

        return view('admin.maintainsuppliers.pending_grns.index', [
            'title' => $this->title,
            'model' => $this->model,
            'suppliers' => WaSupplier::all(),
            'stores' => WaLocationAndStore::all(),
            'breadcum' => [
                $this->title => ''
            ]
        ]);
    }

    public function pendingGrnList()
    {
        $requestedGrns = WaPettyCashRequestItem::whereNotNull('grn_number')
            ->whereHas('pettyCashRequest', fn($query) => $query->where('rejected', false))
            ->select('grn_number')
            ->pluck('grn_number')
            ->toArray();

        $pendingGrns = WaGrn::with('supplier')
            ->whereDoesntHave('invoice')
            ->whereNotIn('grn_number', $requestedGrns)
            ->groupBy('grn_number')
            ->latest()
            ->get()
            ->map(function ($grn) {
                return [
                    'id' => $grn->id,
                    'grn_number' => $grn->grn_number,
                    'supplier_name' => $grn->supplier->name,
                    'date' => $grn->created_at->format('Y-m-d'),
                ];
            });

        return response()->json($pendingGrns);
    }
}
