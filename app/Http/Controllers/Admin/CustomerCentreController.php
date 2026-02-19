<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\WaCustomer;
use App\Model\WaDebtorTran;
use App\Model\WaRouteCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerCentreController extends Controller
{
    protected $model;
    protected $title;

    public function __construct()
    {
        $this->model = 'maintain-customers';
        $this->title = 'Customer Centre';
    }

    public function show(WaCustomer $customer)
    {
        if (!can('view', 'customer-centre')) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $customer->setAttribute('balance', $customer->getAllDebtorsTrans->sum('amount'));

        $this->title .=  $customer->supplier_code;

        $breadcum = [
            'Maintain Customers' => route('maintain-customers.index'),
            $this->title => route('customer-centre.show', $customer)
        ];

        return view('admin.customer_centre.show', [
            'title' => $this->title,
            'model' => $this->model,
            'customer' => $customer,
            'breadcum' => $breadcum,
        ]);
    }

    public function statement(WaCustomer $customer)
    {
        $from = request()->filled('from') ? request()->from . ' 00:00:00' : now()->format('Y-m-d 00:00:00');
        $to = request()->filled('to') ? request()->to . ' 23:59:59' : now()->format('Y-m-d 23:59:59');

        $query = WaDebtorTran::query()
            ->select([
                'wa_debtor_trans.*',
                'invoices.requisition_no as invoice',
                DB::raw("(SELECT SUM(prev.amount) FROM wa_debtor_trans as prev where wa_customer_id = $customer->id AND prev.id  < wa_debtor_trans.id) AS opening_balance"),
            ])
            ->selectRaw("(CASE WHEN amount > 0 THEN amount ELSE 0 END) as debit")
            ->selectRaw("(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as credit")
            ->leftJoin('wa_internal_requisitions as invoices', 'wa_debtor_trans.wa_sales_invoice_id', 'invoices.id')
            ->where('wa_customer_id', $customer->id)
            ->whereBetween('trans_date', [$from, $to]);

        $openingBalance = WaDebtorTran::query()
            ->where('wa_customer_id', $customer->id)
            ->where('trans_date', '<', $from)
            ->sum('amount');

        return DataTables::eloquent($query)
            ->editColumn('trans_date', function ($query) {
                // Use input_date if available (has time), otherwise use trans_date
                $dateTime = $query->input_date ?? $query->trans_date;
                return \Carbon\Carbon::parse($dateTime)->format('Y-m-d H:i');
            })
            ->editColumn('reference', function ($query) {
                $reference = $query->reference;
                if ($query->invoice && (substr($query->document_no, 0, 3) == 'RCT')) {
                    $reference = "$reference / $query->invoice";
                }
                return $reference;
            })
            ->editColumn('debit', function ($query) {
                return manageAmountFormat($query->debit);
            })
            ->editColumn('credit', function ($query) {
                return manageAmountFormat($query->credit);
            })
            ->addColumn('running_balance', function ($record) {
                return manageAmountFormat($record->opening_balance + $record->amount);
            })
            ->with('total', function () use ($query, $openingBalance) {
                return  manageAmountFormat($openingBalance + $query->sum('amount'));
            })
            ->with('opening_balance', function () use ($openingBalance) {
                return manageAmountFormat($openingBalance);
            })
            ->toJson();
    }

    public function routeCustomers(WaCustomer $customer)
    {
        $query = WaRouteCustomer::with('center')
            ->select('wa_route_customers.*')
            ->where('wa_route_customers.route_id', $customer->route_id);

        return DataTables::eloquent($query)
            ->editColumn('image_url', function ($customer) {
                return $customer->image_url ? 'Yes' : 'No';
            })
            ->addColumn('actions', function ($customer) {
                return view('admin.customer_centre.actions.customers', compact('customer'));
            })
            ->addColumn('center', function ($customer) {
                return $customer->center->name ?? '';
            })
            ->toJson();
    }
}
