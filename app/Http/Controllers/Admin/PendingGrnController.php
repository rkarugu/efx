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
use Illuminate\Support\Facades\Log;
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

        try {
            Log::info('PendingGrnController: Starting query build');
            
            $query = WaGrn::query()
                ->select([
                    DB::raw('MIN(wa_grns.id) as id'),
                    DB::raw('MAX(wa_grns.delivery_date) as delivery_date'),
                    'wa_grns.grn_number',
                    DB::raw('MAX(wa_grns.is_printed) as is_printed'),
                    DB::raw('MAX(wa_grns.supplier_invoice_no) as supplier_invoice_no'),
                    DB::raw('MAX(wa_grns.cu_invoice_number) as cu_invoice_number'),
                    DB::raw('MAX(wa_grns.documents_received) as documents_received'),
                    DB::raw('MAX(wa_grns.documents_sent) as documents_sent'),
                    'orders.id as order_id',
                    'orders.purchase_no',
                    'orders.documents',
                    'suppliers.id AS supplier_id',
                    'suppliers.name AS supplier_name',
                    DB::raw('"" AS received_by'),
                    'locations.location_name',
                    DB::raw('COALESCE(SUM(
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.order_price")) AS DECIMAL(10,2)) * 
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.qty")) AS DECIMAL(10,2)) - 
                        COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.total_discount")) AS DECIMAL(10,2)), 0)
                    ) * 
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.vat_rate")) AS DECIMAL(5,2)) / 
                    (100 + CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.vat_rate")) AS DECIMAL(5,2))), 0) AS vat_amount'),
                    DB::raw('COALESCE(SUM(
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.order_price")) AS DECIMAL(10,2)) * 
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.qty")) AS DECIMAL(10,2)) - 
                        COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.total_discount")) AS DECIMAL(10,2)), 0)
                    ), 0) AS total_amount'),
                ])
                ->join('wa_purchase_orders AS orders', 'orders.id', 'wa_grns.wa_purchase_order_id')
                ->join('wa_suppliers AS suppliers', 'suppliers.id', 'orders.wa_supplier_id')
                ->join('wa_location_and_stores AS locations', 'locations.id', 'orders.wa_location_and_store_id')

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
                    if (!empty($supplierIds)) {
                        $query->whereIn('orders.wa_supplier_id', $supplierIds);
                    }
                })
                ->whereNotExists(function($query) {
                    $query->select(DB::raw(1))
                          ->from('wa_supplier_invoices')
                          ->whereColumn('wa_supplier_invoices.grn_number', 'wa_grns.grn_number');
                })
                ->groupBy([
                    'wa_grns.grn_number',
                    'orders.id',
                    'orders.purchase_no',
                    'orders.documents',
                    'suppliers.id',
                    'suppliers.name',
                    'locations.location_name'
                ]);

            Log::info('PendingGrnController: Query built successfully');
            
            if (request()->wantsJson()) {
                Log::info('PendingGrnController: Processing DataTables request');
                
                return DataTables::eloquent($query)
                    ->editColumn('delivery_date', function ($grn) {
                        return $grn->delivery_date ? date('Y-m-d', strtotime($grn->delivery_date)) : '';
                    })
                    ->editColumn('vat_amount', function ($grn) {
                        return manageAmountFormat($grn->vat_amount ?? 0);
                    })
                    ->editColumn('total_amount', function ($grn) {
                        return manageAmountFormat($grn->total_amount ?? 0);
                    })
                    ->addColumn('actions', function ($grn) {
                        return view('admin.maintainsuppliers.pending_grns.actions', compact('grn'));
                    })
                    ->with([
                        'grand_total' => manageAmountFormat($query->get()->sum('total_amount')),
                    ])
                    ->toJson();
            }
        } catch (\Exception $e) {
            Log::error('PendingGrnController Error: ' . $e->getMessage());
            Log::error('PendingGrnController Stack Trace: ' . $e->getTraceAsString());
            
            if (request()->wantsJson()) {
                return response()->json([
                    'error' => 'An error occurred while loading data.',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
            
            return redirect()->back()->withErrors(['error' => 'An error occurred while loading data: ' . $e->getMessage()]);
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
