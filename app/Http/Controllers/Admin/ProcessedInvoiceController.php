<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProcessedSupplierInvoicesExport;
use App\Http\Controllers\Controller;
use App\Model\WaGlTran;
use App\Model\WaLocationAndStore;
use App\Model\WaSupplier;
use App\Model\WaUserSupplier;
use App\WaSupplierInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProcessedInvoiceController extends Controller
{
    protected $pmodel;
    protected $model;
    protected $base_title;

    public function __construct()
    {
        $this->pmodel = 'maintain-suppliers';
        $this->model = 'processed-invoices';
        $this->base_title = 'Supplier Invoices';
    }

    public function  index()
    {
        if (!can('view', 'suppliers-invoice')) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $query = WaSupplierInvoice::query()
            ->select('wa_supplier_invoices.*')
            ->with([
                'supplier:id,name',
                'lpo:id,purchase_no,created_at',
                'user:id,name',
                'suppTrans'
            ])
            ->whereHas('lpo', function ($query) use (&$description) {
                if (request()->filled('location')) {
                    $description .= 'Location: ' . WaLocationAndStore::find(request()->location)->location_name;
                    $query->where('wa_location_and_store_id', request()->location);
                }
            })
            ->doesntHave('payments');

        if (request()->filled('from') && request()->filled('to')) {
            $start = request()->from . " 00:00:00";
            $end = request()->to . " 23:59:59";

            $description .= " Dates: $start - $end";

            $query->whereBetween('wa_supplier_invoices.created_at', [$start, $end]);
        }

        if (request()->filled('period')) {
            $query = $this->addAgingConstraint(request()->period, $query);
        }

        if (request()->filled('supplier')) {
            $description .= ' Supplier: ' . WaSupplier::find(request()->supplier)->name;
            $query->where('supplier_id', request()->supplier);
        }

        if (!can('can-view-all-suppliers', 'maintain-suppliers')) {
            $supplierIds = WaUserSupplier::where('user_id', auth()->user()->id)->get()
                ->pluck('wa_supplier_id')->toArray();
            $query->whereIn('supplier_id', $supplierIds);
        }

        if (request()->action == 'pdf') {
            $pdf = Pdf::loadView('admin.maintainsuppliers.processed_invoices.print', [
                'invoices' => $query->get(),
                'description' => $description,
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('processed_invoces_' . date('Y-m-d-H-i-s') . '.pdf');
        }

        $sumQuery = $query;
        $sumTotal = $sumQuery->sum('amount');
        if (request()->action == 'excel') {
            $data = $query->get();
            $excelData = [];
            foreach ($data as $invoice) {
                $payload = [
                    'date' => \Carbon\Carbon::parse($invoice->created_at)->toDateString(),
                    'lpo'   => $invoice->lpo->purchase_no,
                    'lpo-date' => \Carbon\Carbon::parse($invoice->created_at)->toDateString(),
                    'grn' => $invoice->grn_number,
                    'grn-date' => \Carbon\Carbon::parse($invoice->grn_date)->toDateString(),
                    'invoice-number' => $invoice->invoice_number,
                    'supplier-name' => $invoice->supplier->name,
                    'supplier-invoice' => $invoice->supplier_invoice_number,
                    'sup-invoice-date' => $invoice->supplier_invoice_date,
                    'cu-invoice-number' => $invoice->cu_invoice_number,
                    'user-name' => $invoice->user->name,
                    'vat' => manageAmountFormat($invoice->vat_amount),
                    'amount' => manageAmountFormat($invoice->amount),
                ];
                $excelData[] = $payload;
            }
            $excel = new ProcessedSupplierInvoicesExport(collect($excelData));
            $today = \Carbon\Carbon::now()->toDateString();
            return Excel::download($excel, "processed_supplier_invoce_$today.xlsx");
        }


        if (request()->wantsJson()) {

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->editColumn('lpo.created_at', function ($invoice) {
                    return $invoice->lpo->created_at->format('Y-m-d');
                })
                ->editColumn('grn_date', function ($invoice) {
                    return $invoice->grn_date->format('Y-m-d');
                })
                ->editColumn('supplier_invoice_date', function ($invoice) {
                    return $invoice->supplier_invoice_date?->format('Y-m-d');
                })
                ->editColumn('created_at', function ($invoice) {
                    return $invoice->created_at->format('Y-m-d');
                })
                ->editColumn('vat_amount', function ($invoice) {
                    return manageAmountFormat($invoice->vat_amount);
                })
                ->editColumn('amount', function ($invoice) {
                    return manageAmountFormat($invoice->amount);
                })
                ->addColumn('action', function ($invoice) {
                    return view('admin.maintainsuppliers.processed_invoices.action', ['invoice' => $invoice]);
                })
                ->toJson();
        }


        return view('admin.maintainsuppliers.processed_invoices.index', [
            'suppliers' => WaSupplier::get(),
            'locations' => WaLocationAndStore::all(),
            'title' => $this->base_title . " - Processed Invoices",
            'model' => $this->model,
            'sumTotal' => $sumTotal
        ]);
    }

    protected function addAgingConstraint($period, $query)
    {
        switch ($period) {
            case 'under30':
                return $query->where('supplier_invoice_date', '>', now()->subDays(30)->toDateString());
            case 'under60':
                return $query->whereBetween('supplier_invoice_date', [
                    now()->subDays(60)->toDateString(),
                    now()->subDays(30)->toDateString(),
                ]);
            case 'under90':
                return $query->whereBetween('supplier_invoice_date', [
                    now()->subDays(90)->toDateString(),
                    now()->subDays(60)->toDateString(),
                ]);
            case 'under120':
                return $query->whereBetween('supplier_invoice_date', [
                    now()->subDays(120)->toDateString(),
                    now()->subDays(90)->toDateString(),
                ]);
            case 'over120':
                return $query->where('supplier_invoice_date', '<', now()->subDays(120)->toDateString());
            default:
                return $query;
        }
    }

    public function show($id)
    {
        if (!can('view', 'suppliers-invoice')) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $invoice = WaSupplierInvoice::findOrFail($id);

        return view('admin.maintainsuppliers.processed_invoices.show', [
            'invoice' => $invoice,
            'title' => $this->base_title . " - Processed Invoice",
            'model' => $this->model
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!can('edit', 'suppliers-invoice')) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $invoice = WaSupplierInvoice::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'cu_invoice_number' => 'required|unique:wa_supplier_invoices,cu_invoice_number,' . $invoice->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => 0,
                'message' => $validator->errors(),
            ], 422);
        }

        try {
            $prevReference = $invoice->supplier_invoice_number;

            $invoice->update([
                'supplier_invoice_number' => $request->supplier_invoice_number,
                'supplier_invoice_date' => $request->supplier_invoice_date,
                'cu_invoice_number' => $request->cu_invoice_number,
            ]);

            $invoice->suppTrans->update([
                'suppreference' => $request->supplier_invoice_number,
                'document_no' => $request->supplier_invoice_number,
                'cu_invoice_number' => $request->cu_invoice_number,
                'trans_date' => $request->supplier_invoice_date,
            ]);

            $glTrans = WaGlTran::where('transaction_no', $prevReference)->get();
            foreach ($glTrans as $transaction) {
                $transaction->update([
                    'transaction_no' =>  $request->supplier_invoice_number,
                ]);
            }

            return response()->json([
                'result' => 1,
                'message' => 'Invoice details updated successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'result' => 0,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reverse(WaSupplierInvoice $invoice)
    {
        try {
            if ($invoice->suppTrans->payments()->exists()) {
                throw new Exception('The invoice cannot be reversed');
            }

            $invoice->lpo->update([
                'invoiced' => 'No'
            ]);

            WaGlTran::where('transaction_no', $invoice->supplier_invoice_number)->delete();

            $invoice->suppTrans()->delete();
            $invoice->items()->delete();
            $invoice->delete();

            Session::flash('success', 'Invoice reversed successfully');

            return redirect()->back();
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());

            return redirect()->back();
        }
    }
}
