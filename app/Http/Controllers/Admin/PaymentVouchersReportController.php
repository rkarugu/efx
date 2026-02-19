<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CommonReportDataExport;
use App\Http\Controllers\Controller;
use App\Model\WaSupplier;
use App\PaymentVoucher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class PaymentVouchersReportController extends Controller
{
    protected $model = 'payment-vouchers-report';

    protected $title = 'Payment Vouchers Report';

    public function index()
    {
        if (!can('view', 'payment-vouchers-report')) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $from = request()->filled('from') ? request()->from . ' 00:00:00' : false;
        $to = request()->filled('to') ? request()->to . ' 23:59:59' : false;

        $query = DB::table('payment_vouchers as vouchers')
            ->select([
                'vouchers.id',
                'vouchers.number',
                DB::raw("DATE_FORMAT(vouchers.created_at, '%Y-%m-%d') AS created_at"),
                'suppliers.name AS supplier_name',
                'accounts.account_name',
                'payment_modes.mode AS payment_mode',
                'bank_files.file_no AS bank_file_no',
                DB::raw("DATE_FORMAT(bank_files.created_at, '%Y-%m-%d') AS bank_file_date"),
                'vouchers.amount',
            ])
            ->join('wa_suppliers as suppliers', 'suppliers.id', 'vouchers.wa_supplier_id')
            ->join('wa_bank_accounts as accounts', 'accounts.id', 'vouchers.wa_bank_account_id')
            ->join('wa_payment_modes as payment_modes', 'payment_modes.id', 'vouchers.wa_payment_mode_id')
            ->leftJoin('wa_bank_file_items as bank_file_items', 'bank_file_items.payment_voucher_id', 'vouchers.id')
            ->leftJoin('wa_bank_files as bank_files', 'bank_files.id', 'bank_file_items.wa_bank_file_id')
            ->when(request()->filled('supplier'), function ($query) {
                $query->where('suppliers.id', request()->supplier);
            })
            ->when(request()->filled('status'), function ($query) use ($from, $to) {
                if (request()->status == 'paid') {
                    return $query->where('vouchers.status', PaymentVoucher::PROCESSED);
                }

                if (request()->status == 'approved') {
                    return $query->where('vouchers.status', PaymentVoucher::APPROVED);
                }

                if (request()->status == 'pending') {
                    return $query->where('vouchers.status', PaymentVoucher::PENDING);
                }
            })
            ->when($from && $to, function ($query) use ($from, $to) {
                if (request()->status == 'paid') {
                    return $query->whereBetween('bank_files.created_at', [$from, $to]);
                }

                return $query->whereBetween('vouchers.created_at', [$from, $to]);
            });

        if (request()->wantsJson()) {
            return DataTables::of($query)
                ->editColumn('amount', function ($voucher) {
                    return manageAmountFormat($voucher->amount);
                })
                ->addColumn('actions', function ($voucher) {
                    return view('admin.payment_vouchers.partials.link', compact('voucher'));
                })
                ->with('total_amount', function () use ($query) {
                    return manageAmountFormat($query->sum('vouchers.amount'));
                })
                ->toJson();
        }

        if (request()->download == 'excel') {
            $view = view(
                'admin.payment_vouchers.exports.excel',
                [
                    'vouchers' => $query->get(),
                    "supplier" => request()->filled('supplier') ? WaSupplier::find(request()->supplier) : '',
                    'from' => $from ? Carbon::parse($from)->format('d/m/Y') : '',
                    'to' => $to ? Carbon::parse($to)->format('d/m/Y') : '',
                    'status' => request()->filled('status') ? Str::upper(request()->status) : false
                ]
            );

            return Excel::download(new CommonReportDataExport($view), 'payment_vouchers_report' . date('Ymdhis') . '.xlsx');
        }

        if (request()->download == 'pdf') {
            $pdf = Pdf::loadView(
                'admin.payment_vouchers.exports.pdf',
                [
                    'vouchers' => $query->get(),
                    "supplier" => request()->filled('supplier') ? WaSupplier::find(request()->supplier) : '',
                    'from' => $from ? Carbon::parse($from)->format('d/m/Y') : '',
                    'to' => $to ? Carbon::parse($to)->format('d/m/Y') : '',
                    'description' => request()->filled('status') ? Str::upper(request()->status) . ' PAYMENT VOUCHERS REPORT' : 'PAYMENT VOUCHERS REPORT'
                ]
            );

            return $pdf->setPaper('a4', 'landscape')
                ->setWarnings(false)
                ->download('payment_vouchers_report' . date('Ymdhis') . '.pdf');
        }

        return view('admin.payment_vouchers.report', [
            'model' => $this->model,
            'title' => $this->title,
            'breadcum' => [
                'Accounts Payables Reports' => route('account-payables-reports.index'),
                $this->title => ''
            ],
            'suppliers'   => WaSupplier::get(),
        ]);
    }

    public function approvedPendingReport()
    {
        if (!can('view', 'payment-vouchers-report')) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $from = request()->filled('from') ? request()->from . ' 00:00:00' : false;
        $to = request()->filled('to') ? request()->to . ' 23:59:59' : false;

        $query = DB::table('payment_vouchers as vouchers')
            ->select([
                'vouchers.id',
                'vouchers.number',
                DB::raw("DATE_FORMAT(vouchers.created_at, '%Y-%m-%d') AS created_at"),
                'suppliers.name AS supplier_name',
                'accounts.account_name',
                'payment_modes.mode AS payment_mode',
                'bank_files.file_no AS bank_file_no',
                DB::raw("DATE_FORMAT(bank_files.created_at, '%Y-%m-%d') AS bank_file_date"),
                'vouchers.amount',
                'vouchers.status',
            ])
            ->join('wa_suppliers as suppliers', 'suppliers.id', 'vouchers.wa_supplier_id')
            ->join('wa_bank_accounts as accounts', 'accounts.id', 'vouchers.wa_bank_account_id')
            ->join('wa_payment_modes as payment_modes', 'payment_modes.id', 'vouchers.wa_payment_mode_id')
            ->leftJoin('wa_bank_file_items as bank_file_items', 'bank_file_items.payment_voucher_id', 'vouchers.id')
            ->leftJoin('wa_bank_files as bank_files', 'bank_files.id', 'bank_file_items.wa_bank_file_id')
            ->whereIn('vouchers.status', [PaymentVoucher::APPROVED, PaymentVoucher::PENDING])
            ->when(request()->filled('supplier'), function ($query) {
                $query->where('suppliers.id', request()->supplier);
            })
            ->when($from && $to, function ($query) use ($from, $to) {
                return $query->whereBetween('vouchers.created_at', [$from, $to]);
            });

        if (request()->wantsJson()) {
            return DataTables::of($query)
                ->editColumn('amount', function ($voucher) {
                    return manageAmountFormat($voucher->amount);
                })
                ->editColumn('status', function ($voucher) {
                    if ($voucher->status == PaymentVoucher::APPROVED) {
                        return '<span class="badge badge-success">Approved</span>';
                    }
                    return '<span class="badge badge-warning">Pending</span>';
                })
                ->addColumn('actions', function ($voucher) {
                    return view('admin.payment_vouchers.partials.link', compact('voucher'));
                })
                ->with('total_amount', function () use ($query) {
                    return manageAmountFormat($query->sum('vouchers.amount'));
                })
                ->rawColumns(['status', 'actions'])
                ->toJson();
        }

        if (request()->download == 'excel') {
            $view = view(
                'admin.payment_vouchers.exports.excel',
                [
                    'vouchers' => $query->get(),
                    "supplier" => request()->filled('supplier') ? WaSupplier::find(request()->supplier) : '',
                    'from' => $from ? Carbon::parse($from)->format('d/m/Y') : '',
                    'to' => $to ? Carbon::parse($to)->format('d/m/Y') : '',
                    'status' => 'APPROVED & PENDING'
                ]
            );

            return Excel::download(new CommonReportDataExport($view), 'approved_pending_payment_vouchers_' . date('Ymdhis') . '.xlsx');
        }

        if (request()->download == 'pdf') {
            $pdf = Pdf::loadView(
                'admin.payment_vouchers.exports.pdf',
                [
                    'vouchers' => $query->get(),
                    "supplier" => request()->filled('supplier') ? WaSupplier::find(request()->supplier) : '',
                    'from' => $from ? Carbon::parse($from)->format('d/m/Y') : '',
                    'to' => $to ? Carbon::parse($to)->format('d/m/Y') : '',
                    'description' => 'APPROVED & PENDING PAYMENT VOUCHERS REPORT'
                ]
            );

            return $pdf->setPaper('a4', 'landscape')
                ->setWarnings(false)
                ->download('approved_pending_payment_vouchers_' . date('Ymdhis') . '.pdf');
        }

        return view('admin.payment_vouchers.report', [
            'model' => $this->model,
            'title' => 'Approved & Pending Payment Vouchers Report',
            'breadcum' => [
                'Accounts Payables Reports' => route('account-payables-reports.index'),
                'Approved & Pending Payment Vouchers' => ''
            ],
            'suppliers'   => WaSupplier::get(),
        ]);
    }
}
