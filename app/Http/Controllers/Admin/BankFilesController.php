<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PaymentVoucher\ProcessVoucher;
use App\Actions\PaymentVoucher\ReverseVoucher;
use App\Actions\SupplierInvoice\Discount\CreatePaymentDiscount;
use App\Exports\BankFileExport;
use App\Exports\ExportViewToExcel;
use App\Http\Controllers\Controller;
use App\Model\WaBankAccount;
use App\Models\WaBankFile;
use App\Models\WaBankFileItem;
use App\Models\WaPaymentMode;
use App\PaymentVoucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class BankFilesController extends Controller
{
    protected $model = 'bank-files';

    protected $title = 'Bank Files';

    public function index()
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $query = WaBankFile::query()
            ->select([
                'wa_bank_files.*',
                'users.name as prepared_by'
            ])
            ->with([
                'account',
                'items'
            ])
            ->withCount('items')
            ->leftJoin('users', 'users.id', 'wa_bank_files.prepared_by');

        if (request()->from && request()->to) {
            $query->whereBetween('wa_bank_files.created_at', [request()->from . ' 00:00:00', request()->to . '23:59:59']);
        }

        if (request()->account) {
            $query->where('wa_bank_account_id', request()->account);
        }

        if (request()->wantsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('amount', function ($file) {
                    return manageAmountFormat($file->amount);
                })
                ->editColumn('created_at', function ($file) {
                    return $file->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('actions', function ($file) {
                    return view('admin.bank_files.action', ['file' => $file]);
                })
                ->toJson();
        }

        $breadcum = [
            'Bank Files' => route($this->model . '.index')
        ];

        return view('admin.bank_files.index', [
            'title' => $this->title,
            'model' => $this->model,
            'breadcum' => $breadcum,
            'accounts' => WaBankAccount::query()
                ->with('getGlDetail')
                ->whereHas('paymentMethod', function ($query) {
                    $query->where('use_for_payments', 1);
                })->get(),
        ]);
    }

    public function fileItems()
    {
        if (!can('view', $this->model)) {
            return response()->json([
                'message' => 'Permission denied'
            ], 403);
        }

        $items = WaBankFileItem::with([
            'voucher.preparedBy',
            'voucher.paymentMode',
            'voucher.supplier'
        ])->where('wa_bank_file_id', request()->file)
            ->get();

        return response()->json([
            'items' => $items
        ]);
    }

    public function create()
    {
        if (!can('add', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $breadcum = [
            'Bank Files' => route($this->model . '.index'),
            'Create' => route($this->model . '.create'),
        ];

        return view('admin.bank_files.create', [
            'title' => $this->title,
            'model' => $this->model,
            'breadcum' => $breadcum,
            'accounts' => WaBankAccount::query()
                ->with('getGlDetail')
                ->whereHas('paymentMethod', function ($query) {
                    $query->where('use_for_payments', 1);
                })->get(),
        ]);
    }

    public function show()
    {
        if (!can('view', $this->model)) {
            return response()->json([
                'message' => 'Permission denied'
            ], 403);
        }

        $vouchers = PaymentVoucher::query()
            ->with([
                'supplier:id,name',
                'preparedBy:id,name',
                'paymentMode:id,mode',
            ])
            ->where('wa_bank_account_id', request()->account)
            ->where('status', PaymentVoucher::APPROVED)
            ->when(request()->payment_method == 'cheque', function ($query) {
                $query->where('wa_payment_mode_id', WaPaymentMode::cheque()->id);
            })
            ->when(request()->payment_method == 'transfer', function ($query) {
                $query->where('wa_payment_mode_id', '<>', WaPaymentMode::cheque()->id);
            })
            ->get();

        $vouchers->map(function ($voucher) {
            $voucher->setAttribute('printUrl', route('payment-vouchers.print_pdf', $voucher));
        });

        return response()->json([
            'success' => true,
            'vouchers' => $vouchers
        ]);
    }

    public function store(Request $request)
    {
        if (!can('add', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $this->validate($request, [
            'account' => 'required',
            'vouchers' => 'required'
        ]);

        $user = getLoggeduserProfile();
        $vouchers = PaymentVoucher::whereIn('id', $request->vouchers)->get();

        DB::beginTransaction();

        try {
            $bankFile = WaBankFile::create([
                'file_no' => getCodeWithNumberSeries('BANK_FILES'),
                'wa_bank_account_id' => $request->account,
                'prepared_by' => $user->id,
                'amount' => $vouchers->sum('amount'),
            ]);

            updateUniqueNumberSeries('BANK_FILES', $bankFile->number);

            foreach ($vouchers as $voucher) {
                WaBankFileItem::create([
                    'wa_bank_file_id' =>  $bankFile->id,
                    'payment_voucher_id' =>  $voucher->id,
                    'amount' =>  $voucher->amount,
                ]);

                $voucher->update(['status' => PaymentVoucher::PROCESSED]);

                app(ProcessVoucher::class)->process($voucher);

                app(CreatePaymentDiscount::class)->create($voucher);
            }

            DB::commit();

            Session::flash('success', 'Bank file created successfully.');

            return redirect()->route('bank-files.index');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->route('bank-files.create')->withErrors($e->getMessage());
        }
    }

    public function edit($id)
    {
        if (!can('edit', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $file = WaBankFile::with('items')->find($id);
        $breadcum = [
            'Bank Files' => route($this->model . '.index'),
            'Edit' => route($this->model . '.create', $file),
        ];

        return view('admin.bank_files.edit', [
            'title' => $this->title,
            'model' => $this->model,
            'breadcum' => $breadcum,
            'accounts' => WaBankAccount::query()
                ->with('getGlDetail')
                ->whereHas('paymentMethod', function ($query) {
                    $query->where('use_for_payments', 1);
                })->get(),
            'bankFile' => $file
        ]);
    }

    public function update(Request $request, WaBankFile $file)
    {
        if (!can('edit', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $this->validate($request, [
            'account' => 'required',
            'vouchers' => 'required'
        ]);

        $vouchers = PaymentVoucher::whereIn('id', $request->vouchers)->get();

        DB::beginTransaction();

        try {
            foreach ($vouchers as $voucher) {

                $item = WaBankFileItem::where('payment_voucher_id', $voucher->id)->first();

                if (is_null($item)) {
                    continue;
                }

                $item->delete();

                app(ReverseVoucher::class)->reverse($voucher);
            }

            $file->update([
                'wa_bank_account_id' => $request->account,
                'amount' => $file->items()->sum('amount'),
            ]);

            if ($file->items->fresh()->count() == 0) {
                $file->delete();
            }

            DB::commit();

            Session::flash('success', 'Bank file created successfully.');

            return redirect()->route('bank-files.index');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->route('bank-files.create')->withErrors($e->getMessage());
        }
    }

    public function supportingDocument($fileId)
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $file = WaBankFile::query()
            ->with([
                'items',
            ])
            ->findOrFail($fileId);

        $pdf = Pdf::loadView('admin.bank_files.supporting_document', compact('file'));

        return $pdf->stream('supporting_' . $file->file_no . '_' . date('Y-m-d-H-i-s') . '.pdf');
    }

    public function download(WaBankFile $file)
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        switch ($file->account->account_name) {
            case 'EQUITY BANK':
                $view = view('admin.bank_files.exports.equity', compact('file'));

                return Excel::download(new ExportViewToExcel($view), $file->file_no . '_' . date('Y-m-d-H-i-s') . '.csv');

            default:
                $view = view('admin.bank_files.exports.kcb', compact('file'));

                return Excel::download(new ExportViewToExcel($view), $file->file_no . '_' . date('Y-m-d-H-i-s') . '.xls');
        }
    }
}
