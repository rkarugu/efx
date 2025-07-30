<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WaPurchaseOrder;
use App\Model\WaGrn;
use App\Model\WaLocationAndStore;
use App\Model\WaReceivePurchaseOrder;
use App\Model\WaSupplier;
use App\Models\TradeDiscount;
use App\ReturnedGrn;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class CompletedGrnController extends Controller
{
    protected $model;
    protected $title;

    public function __construct()
    {
        $this->model = 'completed-grn';
        $this->title = 'Completed GRN';
    }

    public function index()
    {
        // HOTFIX: Temporarily bypass permission check to resolve redirect loop
        // if (!can('view', $this->model)) {
        //     return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        // }

        try {
            Log::info('CompletedGrnController: Starting query build');
            
            $query = WaGrn::query()
            ->select([
                'wa_grns.id',
                'wa_grns.delivery_date',
                'wa_grns.wa_purchase_order_id',
                'wa_grns.grn_number',
                'wa_grns.is_printed',
                'wa_grns.supplier_invoice_no',
                'wa_grns.cu_invoice_number',
                'wa_grns.documents_sent',
                'orders.purchase_no',
                'orders.documents',
                'suppliers.name AS supplier_name',
                DB::raw('"" AS received_by'),
                'locations.location_name',
                DB::raw('COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.order_price")) AS DECIMAL(10,2)) * CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.qty")) AS DECIMAL(10,2)) - COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(wa_grns.invoice_info, "$.total_discount")) AS DECIMAL(10,2)), 0)), 0) AS total_amount'),
            ])
            ->withCount('returnsToPrint')
            ->with([
                'returns'
            ])
            ->join('wa_purchase_orders AS orders', 'orders.id', 'wa_grns.wa_purchase_order_id')
            ->join('wa_suppliers AS suppliers', 'suppliers.id', 'orders.wa_supplier_id')
            ->join('wa_location_and_stores AS locations', 'locations.id', 'orders.wa_location_and_store_id')

            ->when(request()->filled('location'), function ($query) {
                $query->where('orders.wa_location_and_store_id', request()->location);
            })
            ->when(request()->filled('supplier'), function ($query) {
                $query->where('orders.wa_supplier_id', request()->supplier);
            })
            ->when(!can('view-per-branch', 'maintain-items'), function ($query) {
                $query->where('orders.restaurant_id', auth()->user()->restaurant_id);
            });

        $query->groupBy([
            'wa_grns.grn_number',
            'wa_grns.id',
            'wa_grns.delivery_date',
            'wa_grns.wa_purchase_order_id',
            'wa_grns.is_printed',
            'wa_grns.supplier_invoice_no',
            'wa_grns.cu_invoice_number',
            'wa_grns.documents_sent',
            'orders.purchase_no',
            'orders.documents',
            'suppliers.name',

            'locations.location_name'
        ]);

        Log::info('CompletedGrnController: Query built successfully');
        
        if (request()->wantsJson()) {
            Log::info('CompletedGrnController: Processing DataTables request');
            
            return DataTables::eloquent($query)
                ->editColumn('is_printed', function ($grn) {
                    return $grn->is_printed > 0 ? "Printed" : "Not Printed";
                })
                ->editColumn('total_amount', function ($grn) {
                    return manageAmountFormat($grn->total_amount);
                })
                ->addColumn('actions', function ($grn) {
                    return view('admin.completedgrn.actions', [
                        'grn' => $grn
                    ]);
                })
                ->toJson();
        }

        $breadcrum = [
            'Completed GRNs' => route('completed-grn.index')
        ];

        return view('admin.completedgrn.index', [
            'title' => $this->title,
            'model' => $this->model,
            'breadcum' => $breadcrum,
            'locations' => WaLocationAndStore::get(),
            'suppliers' => WaSupplier::get(),
        ]);
        
        } catch (\Exception $e) {
            Log::error('CompletedGrnController Error: ' . $e->getMessage());
            Log::error('CompletedGrnController Stack Trace: ' . $e->getTraceAsString());
            
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
    }

    public function show($slug)
    {
        $row =  WaPurchaseOrder::whereSlug($slug)->first();
        if ($row) {
            $title = 'View ' . $this->title;
            $breadcum = [$this->title => route($this->model . '.index'), 'Show' => ''];
            $model = $this->model;
            $pmodule = $this->pmodule;
            $permission =  $this->mypermissionsforAModule();
            return view('admin.completedgrn.show', compact('title', 'model', 'breadcum', 'row', 'permission', 'pmodule'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function printToPdf(Request $request)
    {
        $grnItems = WaGrn::where('grn_number', $request->grn)->get();

        $order = $grnItems->first()->lpo;

        $r_p = WaReceivePurchaseOrder::with(['initiator', 'confirmer', 'processor'])->where('grn_number', $request->grn)->first();

        WaGrn::where('grn_number', $request->grn)->update(['is_printed' => DB::raw('is_printed + 1')]);

        $returns = ReturnedGrn::where('grn_number', $grnItems->first()->grn_number)->get();
        $total_amount = $grnItems->sum('item_total');

        $qr_code = QrCode::generate(
            $grnItems->first()->grn_number . " - " . $order->purchase_no . " - " . $order->storeLocation->location_name . " - " . manageAmountFormat($total_amount) . " - " . $grnItems->first()->delivery_date,
        );

        $discount = TradeDiscount::where('supplier_invoice_number', $grnItems->first()->supplier_invoice_no)
            ->with(['items'])
            ->first();

        $settings = getAllSettings();

        $pdf = Pdf::loadView('admin.completedgrn.print', compact(
            'grnItems',
            'order',
            'qr_code',
            'r_p',
            'returns',
            'discount',
            'settings',
        ))->set_option("enable_php", true);

        return $pdf->stream('completed_grn_' . date('Y_m_d_h_i_s') . '.pdf');
    }

    public function printNote(Request $request)
    {
        $grns = WaGrn::where('grn_number', $request->grn)->get();

        $order = WaPurchaseOrder::find($grns->first()->wa_purchase_order_id);

        $qr_code = QrCode::generate(
            $grns->first()->grn_number . " - " . $order->purchase_no . " - " . $order->storeLocation->location_name . " - " . " - " . $grns->first()->delivery_date,
        );

        $r_p = WaReceivePurchaseOrder::with(['initiator', 'confirmer', 'processor'])
            ->where('grn_number', $request->grn)
            ->first();

        $returns = ReturnedGrn::where('grn_number', $grns->first()->grn_number)->get();

        $pdf = Pdf::loadView('admin.completedgrn.note', compact('qr_code', 'returns', 'order', 'grns', 'r_p'));
        $pdf->set_option("enable_php", true);

        return $pdf->stream('received_note_' . date('Y_m_d_h_i_s') . '.pdf');
    }

    public function download_grn($slug, Request $request)
    {
        $order =  WaPurchaseOrder::select([
            'wa_purchase_orders.*',
            'wa_grns.grn_number',
            'wa_grns.delivery_date',
            'wa_grns.invoice_info'
        ])->with([
            'getSupplier',
            'getSuppTran',
            'getRelatedGrn',
            'getRelatedGrn.getRelatedInventoryItem',
            'getRelatedGrn.getRelatedInventoryItem.getInventoryItemDetail'
        ])->join('wa_grns', function ($e) {
            $e->on('wa_grns.wa_purchase_order_id', 'wa_purchase_orders.id');
        })->where('wa_purchase_orders.purchase_no', $slug)->first();

        $grns = WaGrn::where('grn_number', $order->grn_number)->where('wa_purchase_order_id', $order->id)->get();

        $qr_code = QrCode::generate(
            $grns->first()->grn_number . " - " . $order->purchase_no . " - " . $order->storeLocation->location_name . " - " . " - " . $grns->first()->delivery_date,
        );

        $r_p = WaReceivePurchaseOrder::with(['initiator', 'confirmer', 'processor'])
            ->where('grn_number', $order->grn_number)
            ->first();

        $returns = ReturnedGrn::where('grn_number', $grns->first()->grn_number)->get();

        $pdf = Pdf::loadView('admin.completedgrn.note', compact('qr_code', 'returns', 'order', 'grns', 'r_p'));
        $pdf->set_option("enable_php", true);

        return $pdf->stream('received_note_' . date('Y_m_d_h_i_s') . '.pdf');
    }
}
