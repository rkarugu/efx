<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\SmsService;
use App\Model\Route;
use App\Model\TaxManager;
use App\Model\WaAccountingPeriod;
use App\Model\WaCompanyPreference;
use App\Model\WaCustomer;
use App\Model\WaGlTran;
use App\Model\WaNumerSeriesCode;
use App\Services\AirTouchSmsService;
use App\Services\ExcelDownloadService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesInvoiceReturnController extends Controller
{
    public function showInitialReturnsList(Request $request)
    {
        $user = Auth::user();
        if (!can('return', 'print-invoice-delivery-note')) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }

        $authuser = Auth::user();
        $userwithrestaurants = $authuser->load('userRestaurent');
        $isAdmin = $authuser->role_id == 1;
        $permission = $this->mypermissionsforAModule();

        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->whereNot('wa_inventory_location_transfer_item_returns.status', 'received')
            ->where('wa_inventory_location_transfer_item_returns.return_status', 1)
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.transfer_no as invoice_number',
                'wa_inventory_location_transfers.created_at as invoice_date',
                'wa_inventory_location_transfers.customer_id',
                'wa_inventory_location_transfers.name as name',
                'wa_route_customers.bussiness_name as customer',
                'wa_customers.customer_name as credit_customer',
                'wa_inventory_location_transfers.route as route',
                'wa_unit_of_measures.title as bin',
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_uom', function (JoinClause $join) use ($user) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_item_id', '=', 'wa_inventory_location_uom.inventory_id')
                    ->whereColumn('wa_inventory_location_uom.location_id', '=', 'wa_inventory_location_transfer_items.store_location_id');
                if ($user->role_id == 152) {
                    $query = $query->where('wa_inventory_location_uom.uom_id', $user->wa_unit_of_measures_id);
                }
            })
            ->join('wa_unit_of_measures', 'wa_inventory_location_uom.uom_id', '=', 'wa_unit_of_measures.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }
            })
            ->leftJoin('wa_internal_requisitions', 'wa_internal_requisitions.requisition_no', '=', 'wa_inventory_location_transfers.transfer_no')
            ->leftJoin('wa_route_customers', 'wa_internal_requisitions.wa_route_customer_id', '=', 'wa_route_customers.id')
            ->leftJoin('wa_customers',  'wa_customers.id', '=', 'wa_internal_requisitions.customer_id')
            ->when(!$isAdmin && !isset($permission['employees' . '___view_all_branches_data']), function ($query) use ($authuser) {
                return $query->where('wa_inventory_location_transfers.restaurant_id', $authuser->userRestaurent->id);
            })
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number')
            ->orderBy('wa_inventory_location_transfer_item_returns.created_at', 'DESC')
            ->get();

        $title = 'Invoice Returns';
        $breadcum = [$title => '', 'Pending' => ''];
        $model = 'return-transfers';

        // $routes = DB::table('routes')->select('id', 'route_name')->get();
        if ($isAdmin && !isset($permission['employees' . '___view_all_branches_data'])) {
            $routes = DB::table('routes')->select('id', 'route_name')->get();
        } else {
            $routes = DB::table('routes')->where('restaurant_id', $authuser->userRestaurent->id)->select('id', 'route_name')->get();
        }
        return view('admin.inventorylocationtransfer.return_list', compact('title', 'model', 'breadcum', 'returns', 'routes'));
    }

    public function showAbnormalReturnsPage(Request $request): View|RedirectResponse|BinaryFileResponse
    {
        $user = getLoggeduserProfile();
        if (!can('view', 'abnormal-returns')) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }

        $start = Carbon::now()->startOfDay()->format('Y-m-d H:i:s');
        $end = Carbon::now()->format('Y-m-d H:i:s');
        if ($request->start) {
            $start = Carbon::parse($request->start)->format('Y-m-d H:i:s');
        }

        if ($request->end) {
            $end = Carbon::parse($request->end)->format('Y-m-d H:i:s');
        }

        $returns = DB::table('wa_inventory_location_transfer_item_returns')
            ->where(function ($query) {
                $query->whereRaw("HOUR(wa_inventory_location_transfer_item_returns.updated_at) between 20 and 24")
                    ->orWhereRaw("HOUR(wa_inventory_location_transfer_item_returns.updated_at) between 0 and 4");
            })
            ->whereBetween('wa_inventory_location_transfer_item_returns.updated_at', [$start, $end])
            ->select(
                'wa_inventory_location_transfer_item_returns.return_number',
                'wa_inventory_location_transfers.route as route',
                's.name as salesman',
                'wa_inventory_location_transfer_item_returns.created_at as return_time',
                'wa_inventory_location_transfer_item_returns.updated_at as receive_time',
                'storekeepers.name as receiver',
                DB::raw('count(wa_inventory_location_transfer_item_returns.return_number) as count'),
                DB::raw('sum(wa_inventory_location_transfer_item_returns.return_quantity * wa_inventory_location_transfer_items.selling_price) AS total_returns')
            )
            ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
            ->join('wa_inventory_location_transfers', function ($join) use ($request) {
                $query = $join->on('wa_inventory_location_transfer_items.wa_inventory_location_transfer_id', '=', 'wa_inventory_location_transfers.id');
                if ($request->route_id) {
                    $query = $query->where('wa_inventory_location_transfers.route', $request->route_id);
                }
            })
            ->join('users as s', 'wa_inventory_location_transfers.user_id', '=', 's.id')
            ->join('users as storekeepers', 'wa_inventory_location_transfer_item_returns.received_by', '=', 'storekeepers.id')
            ->groupBy('wa_inventory_location_transfer_item_returns.return_number')
            ->orderBy('wa_inventory_location_transfer_item_returns.updated_at')
            ->get();

        if ($request->intent == 'Excel') {
            $headings = ['Return Number', 'Route', 'salesman', 'Return Time', 'Processed Time', 'Processed By', 'Item Count', 'Total Returns'];
            return ExcelDownloadService::download('abnormal-returns', $returns, $headings);
        }

        $title = 'Abnormal Returns';
        $breadcum = ['Sales Invoice' => '', 'Returns' => '', 'Abnormal' => ''];
        $model = 'abnormal-returns';

        $routes = DB::table('routes')->select('id', 'route_name')->get();
        return view('admin.sales_invoice.returns.abnormal', compact('title', 'model', 'breadcum', 'returns', 'routes'));
    }

    static public function postReturn($returnId)
    {
        try {
            $returns = DB::table('wa_inventory_location_transfer_item_returns')
                ->select(
                    'wa_inventory_location_transfer_item_returns.*',
                    'wa_internal_requisition_items.selling_price',
                    'wa_internal_requisition_items.vat_rate',
                    'wa_inventory_location_transfers.route_id',
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->join('wa_internal_requisition_items', 'wa_inventory_location_transfer_items.wa_internal_requisition_item_id', '=', 'wa_internal_requisition_items.id')
                ->join('wa_inventory_location_transfers', function ($join) {
                    $join->on('wa_inventory_location_transfers.id', '=', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id');
                })
                ->where('wa_inventory_location_transfer_item_returns.id', $returnId)
                ->where('wa_inventory_location_transfer_item_returns.status', 'received')
                ->get()
                ->map(function ($record) {
                    $returnTotal = $record->selling_price * $record->received_quantity;
                    $record->total_cost_with_vat = $returnTotal;
                    $record->vat = ($record->vat_rate / ($record->vat_rate + 100)) * $returnTotal;

                    return $record;
                });

            foreach ($returns as $return) {
                $route = Route::find($return->route_id);
                
                if (!$route) {
                    continue; // Skip if route not found
                }
                
                $totalSalesInclusive = $return->total_cost_with_vat;
                $vatAmount = $return->vat;
                $totalSalesExclusive = $totalSalesInclusive - $vatAmount;

                $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
                $series_module = WaNumerSeriesCode::where('module', 'RETURN')->first();

                $documentNo = $return->return_number;
                $routeName = $route->route_name ?? 'Unknown Route';
                
                // Get customer for this route
                $customer = WaCustomer::where('route_id', $route->id)->first();
                if (!$customer) {
                    continue; // Skip if customer not found
                }

                $salesAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('account_code', '56002-003')->first();
                if (!$salesAccount) {
                    continue; // Skip if sales account not found
                }
                
                $salesCredit = new WaGlTran();
                $salesCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                $salesCredit->grn_type_number = $series_module?->type_number ?? 109;
                $salesCredit->trans_date = Carbon::parse($return->updated_at);
                $salesCredit->restaurant_id = 10;
                $salesCredit->tb_reporting_branch = 10;
                $salesCredit->grn_last_used_number = $series_module?->last_number_used;
                $salesCredit->transaction_type = $series_module?->description ?? 'Return';
                $salesCredit->transaction_no = $documentNo;
                $salesCredit->narrative = "{$routeName} - $documentNo - Returns Exc";
                $salesCredit->account = $salesAccount->account_code;
                $salesCredit->amount = $totalSalesExclusive;
                $salesCredit->customer_id = $customer->id;
                $salesCredit->save();

                $taxManager = TaxManager::find(1);
                if (!$taxManager) {
                    continue; // Skip if tax manager not found
                }
                
                $vatControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $taxManager->output_tax_gl_account)->first();
                if (!$vatControlAccount) {
                    continue; // Skip if VAT control account not found
                }
                $vatCredit = new WaGlTran();
                $vatCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                $vatCredit->grn_type_number = $series_module?->type_number ?? 109;
                $vatCredit->trans_date = Carbon::parse($return->updated_at);
                $vatCredit->restaurant_id = 10;
                $vatCredit->tb_reporting_branch = 10;
                $vatCredit->grn_last_used_number = $series_module?->last_number_used;
                $vatCredit->transaction_type = $series_module?->description ?? 'Return';
                $vatCredit->transaction_no = $documentNo;
                $vatCredit->narrative = "{$routeName} - {$documentNo} - VAT Return";
                $vatCredit->account = $vatControlAccount->account_code;
                $vatCredit->amount = $vatAmount;
                $vatCredit->customer_id = $customer->id;
                $vatCredit->save();

                $companyPreferences = WaCompanyPreference::find(1);
                if (!$companyPreferences) {
                    continue; // Skip if company preferences not found
                }
                
                $debtorsControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $companyPreferences->debtors_control_gl_account)->first();
                if (!$debtorsControlAccount) {
                    continue; // Skip if debtors control account not found
                }
                $debtorsDebit = new WaGlTran();
                $debtorsDebit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                $debtorsDebit->grn_type_number = $series_module?->type_number ?? 109;
                $debtorsDebit->trans_date = Carbon::parse($return->updated_at);
                $debtorsDebit->restaurant_id = 10;
                $debtorsDebit->tb_reporting_branch = 10;
                $debtorsDebit->grn_last_used_number = $series_module?->last_number_used;
                $debtorsDebit->transaction_type = $series_module?->description ?? 'Return';
                $debtorsDebit->transaction_no = $documentNo;
                $debtorsDebit->narrative = "{$routeName} - {$documentNo} - Debtors Return";
                $debtorsDebit->account = $debtorsControlAccount->account_code;
                $debtorsDebit->amount = $totalSalesInclusive * -1;
                $debtorsDebit->customer_id = $customer->id;
                $debtorsDebit->save();
            }
        } catch (\Throwable $e) {
            (new AirTouchSmsService())->sendMessage("Return $returnId failed to post to GL citing {$e->getMessage()}", '254790544563');
        }
    }
}
