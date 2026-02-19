<?php

namespace App\Http\Controllers;

use App\Enums\VehicleResponsibilityTypes;
use App\Jobs\PerformPostSaleActions;
use App\Model\DeliveryCentres;
use App\Model\StockAdjustment;
use App\Model\WaDebtorTran;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\WaRouteCustomer;
use App\Models\FuelVerificationRecord;
use App\Vehicle;
use Carbon\Carbon;
use App\Model\Route;
use App\NewFuelEntry;
use App\Model\WaGlTran;
use App\DeliverySchedule;
use App\Model\TaxManager;
use App\Model\WaCustomer;
use App\Model\WaStockMove;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Model\WaNumerSeriesCode;
use App\Model\WaAccountingPeriod;
use App\Model\WaCompanyPreference;
use Illuminate\Support\Facades\DB;
use App\Enums\FuelEntryParentTypes;
use App\Model\User;
use App\Model\WaGrn;
use App\Model\WaPurchaseOrder;
use App\Model\WaReceivePurchaseOrder;
use App\Model\WaReceivePurchaseOrderItem;
use App\Services\ExcelDownloadService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Model\PaymentMethod;
use App\Model\WaChartsOfAccount;
use App\Models\RouteRepresentatives;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CleanupController extends Controller
{

    public function cleanup()
    {
        // $notsQ = DB::table('mpesa_ipn_notifications');
        // $ids = $notsQ->clone()->pluck('id')->toArray();
        // $nots = $notsQ->clone()->get();

        // $debtors = DB::table('wa_debtor_trans')
        //     ->select('id', 'reference', 'notification_id')
        //     ->whereIn('notification_id', $ids)
        //     ->where('verification_status', 'pending')
        //     ->where('channel', 'MPESA MAKONGENI')
        //     ->get();

        // foreach ($nots as $not) {
        //     try {
        //         $data = json_decode($not->payment_details, true);
        //         $reference = $data['ref'];

        //         $debtor = $debtors->where('notification_id', $not->id)->first();
        //         WaDebtorTran::find($debtor->id)->update(['reference' => $reference]);
        //     } catch (\Throwable $th) {
        //         Log::info("Failed to update debtor reference: " . $th->getMessage());
        //     }
        // }

        // return $this->jsonify(['message' => 'success']);

        $debtors = WaDebtorTran::whereBetween('trans_date', ['2024-11-28 00:00:00', '2024-12-01 23:59:59'])
        ->where('channel', 'VOOMA MAKONGENI')
        ->where('verification_status', 'pending')
        ->where('reference', 'like', '00%')
        ->get();
        $changed = 0;

        foreach ($debtors as $key => $value) {
            $value->reference = str_replace('-92', '', $value->reference);
            $value->reference = ltrim($value->reference, '00');
            $value->save();

            $changed++;            
        }

        return $this->jsonify(['message' => "$changed success"]);
    }

    public function calculateInventoryItemDiscount(Request $request)
    {
        $discount = 0;
        $discountDescription = null;
        $discountBand = DB::table('discount_bands')->where('inventory_item_id', $request->item_id)
            ->where('from_quantity', '<=', $request->item_quantity)
            ->where('to_quantity', '>=', $request->item_quantity)
            ->first();
        if ($discountBand) {

            $discount = $discountBand->discount_amount * $request->item_quantity;
            $discountDescription = "$discountBand->discount_amount discount for quantity between $discountBand->from_quantity and $discountBand->to_quantity";
        } else {
            /*check for discount price promotion*/
            $discount = $this->checkPromotion($request->item_id);
        }
        $data = [
            'discount' => $discount,
            'item_id' => $request->item_id
        ];

        return response()->json($data);
    }

    private function recordDebtorTrans($internalRequisition): void
    {
        $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();

        $debtorTran = new WaDebtorTran();
        $debtorTran->wa_sales_invoice_id = $internalRequisition->id;
        $debtorTran->type_number = $series_module ? $series_module->type_number : '';
        $debtorTran->wa_customer_id = $internalRequisition->customer_id;
        $debtorTran->salesman_id = $internalRequisition->to_store_id;
        $debtorTran->customer_number = WaCustomer::find($internalRequisition->customer_id)->customer_code;
        $debtorTran->trans_date = $internalRequisition->requisition_date;
        $debtorTran->input_date = now()->format('Y-m-d H:i:s');
        $debtorTran->wa_accounting_period_id = $accountingPeriod ? $accountingPeriod->id : null;
        $debtorTran->amount = $internalRequisition->getOrderTotalWithoutDiscount();
        $debtorTran->document_no = $internalRequisition->requisition_no;
        $debtorTran->reference = "{$internalRequisition->route} - {$internalRequisition->requisition_no}";
        $debtorTran->invoice_customer_name = "{$internalRequisition->customer}";
        $debtorTran->branch_id = $internalRequisition->restaurant_id;

        $debtorTran->save();

        if ($internalRequisition->getTotalDiscount() > 0) {
            $discountTran = new WaDebtorTran();
            $discountTran->wa_sales_invoice_id = $internalRequisition->id;
            $discountTran->type_number = $series_module ? $series_module->type_number : '';
            $discountTran->wa_customer_id = $internalRequisition->customer_id;
            $discountTran->salesman_id = $internalRequisition->to_store_id;
            $discountTran->customer_number = WaCustomer::find($internalRequisition->customer_id)->customer_code;
            $discountTran->trans_date = $internalRequisition->requisition_date;
            $discountTran->input_date = now()->format('Y-m-d H:i:s');
            $discountTran->wa_accounting_period_id = $accountingPeriod ? $accountingPeriod->id : null;
            $discountTran->amount = ($internalRequisition->getTotalDiscount()) * -1;
            $discountTran->document_no = $internalRequisition->requisition_no;
            $discountTran->reference = "{$internalRequisition->route} - {$internalRequisition->requisition_no} Discount Allowed";
            $discountTran->invoice_customer_name = "{$internalRequisition->customer}";
            $debtorTran->branch_id = $internalRequisition->restaurant_id;

            $discountTran->save();
        }
    }

    private function postGlTrans($internalRequisition): void
    {
        try {
            $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
            $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITION')->first();

            $totalSalesInclusive = $internalRequisition->getRelatedItem()->sum('total_cost_with_vat');
            $vatAmount = $internalRequisition->getRelatedItem()->sum('vat_amount');
            $totalSalesExclusive = $totalSalesInclusive - $vatAmount;

            $salesAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('account_code', '56002-003')->first();
            $salesCredit = new WaGlTran();
            $salesCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
            $salesCredit->grn_type_number = $series_module?->type_number ?? 51;
            $salesCredit->trans_date = $internalRequisition->created_at;
            $salesCredit->restaurant_id = $internalRequisition->restaurant_id;
            $salesCredit->tb_reporting_branch = $internalRequisition->restaurant_id;
            $salesCredit->grn_last_used_number = $series_module?->last_number_used;
            $salesCredit->transaction_type = $series_module?->description ?? 'Invoice';
            $salesCredit->transaction_no = $internalRequisition->requisition_no;
            $salesCredit->narrative = "{$internalRequisition->route} - {$internalRequisition->requisition_no} - Sales Exc";
            $salesCredit->account = $salesAccount->account_code;
            $salesCredit->amount = $totalSalesExclusive * -1;
            $salesCredit->customer_id = $internalRequisition->customer_id;
            $salesCredit->save();

            $taxManager = TaxManager::find(1);
            $vatControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $taxManager->output_tax_gl_account)->first();
            $vatCredit = new WaGlTran();
            $vatCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
            $vatCredit->grn_type_number = $series_module?->type_number ?? 51;
            $vatCredit->trans_date = $internalRequisition->created_at;
            $vatCredit->restaurant_id = $internalRequisition->restaurant_id;
            $vatCredit->tb_reporting_branch = $internalRequisition->restaurant_id;
            $vatCredit->grn_last_used_number = $series_module?->last_number_used;
            $vatCredit->transaction_type = $series_module?->description ?? 'Invoice';
            $vatCredit->transaction_no = $internalRequisition->requisition_no;
            $vatCredit->narrative = "{$internalRequisition->route} - {$internalRequisition->requisition_no} - VAT Amount";
            $vatCredit->account = $vatControlAccount->account_code;
            $vatCredit->amount = $vatAmount * -1;
            $vatCredit->customer_id = $internalRequisition->customer_id;
            $vatCredit->save();

            $companyPreferences = WaCompanyPreference::find(1);
            $debtorsControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $companyPreferences->debtors_control_gl_account)->first();
            $cashAccountCode = '54008-000';

            $debtorsDebit = new WaGlTran();
            $debtorsDebit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
            $debtorsDebit->grn_type_number = $series_module?->type_number ?? 51;
            $debtorsDebit->trans_date = $internalRequisition->created_at;
            $debtorsDebit->restaurant_id = $internalRequisition->restaurant_id;
            $debtorsDebit->tb_reporting_branch = $internalRequisition->restaurant_id;
            $debtorsDebit->grn_last_used_number = $series_module?->last_number_used;
            $debtorsDebit->transaction_type = $series_module?->description ?? 'Invoice';
            $debtorsDebit->transaction_no = $internalRequisition->requisition_no;
            $debtorsDebit->narrative = "{$internalRequisition->route} - {$internalRequisition->requisition_no} - " . str_starts_with($internalRequisition->slug, 'civ') ? 'Cash Account' : 'Debtors Control';
            $debtorsDebit->account = str_starts_with($internalRequisition->slug, 'civ') ? $cashAccountCode : $debtorsControlAccount->account_code;
            $debtorsDebit->amount = $totalSalesInclusive;
            $debtorsDebit->customer_id = $internalRequisition->customer_id;
            $debtorsDebit->save();
        } catch (Throwable $e) {
            //do nothing
        }
    }

    public function cleanup2()
    {
        DB::beginTransaction();

        try {
            $mpesaReader = new Xlsx();
            $mpesaReader->setReadDataOnly(true);
            $fileName = public_path('thika_users.xlsx');
            $spreadsheet = $mpesaReader->load($fileName);
            $data = $spreadsheet->getActiveSheet()->toArray();

            array_shift($data);
            foreach ($data as $row) {
                User::create([
                    'name' => $row[3],
                    'phone_number' => $row[0],
                    'id_number' => $row[0],
                    'role_id' => $row[4],
                    'restaurant_id' => 1,
                    'wa_department_id' => 2,
                    'status' => '1',
                    'password' => Hash::make('Bizwiz@100'),
                    'email' => $row[0],
                ]);
            }

            DB::commit();
            return $this->jsonify(['message' => 'success']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->jsonify(['message' => $th->getMessage()], 500);
        }
    }

    public function cleanup1()
    {
        ini_set('max_execution_time', 800);

        // Roolback GL
        DB::table('gl_reconcile_interest_expenses')->truncate();
        DB::table('gl_recon_statements')->truncate();
        DB::table('gl_reconciles')->truncate();

        DB::table('payment_verification_banks')->whereNotNull('gl_reconcile_id')
            ->update([
                'gl_reconcile_id' => NULL,
                'gl_recon_statement_id' => NULL,
            ]);
        DB::table('payment_vouchers')->whereNotNull('gl_reconcile_id')
            ->update([
                'gl_reconcile_id' => NULL,
                'gl_recon_statement_id' => NULL,
            ]);
        DB::table('payment_voucher_cheques')->whereNotNull('gl_reconcile_id')
            ->update([
                'gl_reconcile_id' => NULL,
                'gl_recon_statement_id' => NULL,
            ]);
        DB::table('wa_debtor_trans')->whereNotNull('gl_reconcile_id')
            ->update([
                'gl_reconcile_id' => NULL,
                'gl_recon_statement_id' => NULL,
            ]);
        DB::table('wa_gl_trans')->whereNotNull('gl_reconcile_id')
            ->update([
                'gl_reconcile_id' => NULL,
                'gl_recon_statement_id' => NULL,
            ]);

        DB::beginTransaction();

        try {
            $trans = DB::table('wa_debtor_trans')->where('verification_status', 'Approved')->get();
            $account_codes =  getChartOfAccountsList();
            $records = [];
            foreach ($trans as $tran) {
                $gls = DB::table('wa_gl_trans')->where('wa_debtor_tran_id', $tran->id)->where('account', '!=', '55001-001')->get();
                foreach ($gls as $gl) {

                    $account = $account_codes[$gl->account];
                    $suggested_account = '';
                    if ($tran->channel == 'EQUITY MAKONGENI' && $gl->account != '54004-007') {
                        $suggested_account = $account_codes['54004-007'];
                    } elseif ($tran->channel == 'VOOMA MAKONGENI' && $gl->account != '54007-007') {
                        $suggested_account = $account_codes['54007-007'];
                    } elseif ($tran->channel == 'KENYA COMMERCIAL BANK' && $gl->account != '54002-000') {
                        $suggested_account = $account_codes['54002-000'];
                    } elseif ($tran->channel == 'EQUITY BANK' && $gl->account != '54004-000') {
                        $suggested_account = $account_codes['54004-000'];
                    }
                    $trans_date = date('Y-m-d', strtotime($gl->trans_date));
                    $suggested_trans_date = '';
                    if ($trans_date != $tran->trans_date) {
                        $suggested_trans_date = $tran->trans_date;
                    }

                    $narrative = $gl->narrative;
                    $suggested_narrative = '';
                    if ($gl->narrative != $tran->reference . ' / ' . $tran->document_no . ' / ' . $tran->customer_number) {
                        $suggested_narrative = $tran->reference . ' / ' . $tran->document_no . ' / ' . $tran->customer_number;
                    }
                    if ($suggested_account || $suggested_narrative || $suggested_trans_date) {
                        $records[] = [
                            'account' => $account,
                            'suggested_account' => $suggested_account,
                            'narrative' => $narrative,
                            'suggested_narrative' => $suggested_narrative,
                            'trans_date' => $trans_date,
                            'suggested_trans_date' => $suggested_trans_date
                        ];
                    }
                }
            }
            $records = collect($records);
            $columns = ['ACCOUNT', 'SUGGESTED ACCOUNT', 'NARRATIVE', 'SUGGESTED NARRATIVE', 'TRANS DATE', 'SUGGESTED TRANS DATE'];
            return ExcelDownloadService::download('Gl_suggested_data', $records, $columns);

            DB::commit();
            return $this->jsonify(['message' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()]);
        }
    }

    public function lpos(Request $request)
    {
        DB::beginTransaction();
        try {
            $deliveries = DeliverySchedule::latest()->whereDate('actual_delivery_date', '2024-08-08')
                ->whereIn('status', ['finished', 'in_progress'])
                ->get();
            foreach ($deliveries as $delivery) {
                $lpoNumber = getCodeWithNumberSeries('FUEL LPO');
                $vehicle = Vehicle::find($delivery->vehicle_id);

                $telematicsRecords = DB::connection('telematics')
                    ->table('vehicle_telematics')
                    ->where('device_number', $vehicle->license_plate_number)
                    ->whereBetween('timestamp', [Carbon::parse('2024-08-08 06:00:00'), Carbon::parse('2024-08-08 06:00:00')->addMinutes(2)])
                    ->orderBy('timestamp', 'DESC');

                $fuelEntry = NewFuelEntry::create([
                    'lpo_number' => $lpoNumber,
                    'vehicle_id' => $vehicle->id,
                    'shift_type' => FuelEntryParentTypes::RouteDelivery->value,
                    'shift_id' => $delivery->id,
                    'last_fuel_entry_mileage' => ceil($telematicsRecords->clone()->avg('mileage')),
                    'created_at' => $delivery->actual_delivery_date
                ]);

                updateUniqueNumberSeries('FUEL LPO', $lpoNumber);
            }

            DB::commit();
            return $this->jsonify(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function routeSplitting()
    {
        DB::beginTransaction();
        try {
            $centerIds = [5686];
            $oldCenterData = DeliveryCentres::whereIn('id', $centerIds)->get()->map(function ($center) {
                $center->setAppends([]);
                $center->route_id = 441;
                $center->created_at = Carbon::now();
                $center->updated_at = Carbon::now();

                return $center;
            });

            $newShopInserts = [];
            foreach ($oldCenterData as $oldCenter) {
                $oldCenterId = $oldCenter->id;
                unset($oldCenter->id);
                $newCenter = DeliveryCentres::create($oldCenter->toArray());
                $oldShopData = WaRouteCustomer::where('delivery_centres_id', $oldCenterId)->get()->map(function ($shop) use ($newCenter, $newShopInserts) {
                    $shop->setAppends([]);
                    $shop->route_id = 441;
                    $shop->customer_id = 5481;
                    $shop->created_at = Carbon::now();
                    $shop->updated_at = Carbon::now();
                    $shop->created_by = 1;
                    $shop->delivery_centres_id = $newCenter->id;
                    unset($shop->id);
                    return $shop;
                })->toArray();

                $newShopInserts = array_merge($newShopInserts, $oldShopData);
            }

            $oldCenterUpdateQuery = "update delivery_centres set name = concat(name, ' - old') where id in (5686)";
            DB::raw($oldCenterUpdateQuery);

            $oldShopUpdateQuery = "update wa_route_customers set phone = concat(phone, ' - old'),status = 'dormant' where wa_route_customers.delivery_centres_id in (5686)";
            DB::raw($oldShopUpdateQuery);

            WaRouteCustomer::insert($newShopInserts);

            DB::commit();
            return $this->jsonify(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function returnSigning(Request $request)
    {
        DB::beginTransaction();
        try {
            $returns = DB::table('wa_inventory_location_transfer_item_returns')
                ->select(
                    'wa_inventory_location_transfer_item_returns.*',
                    'wa_internal_requisition_items.selling_price',
                    'wa_internal_requisition_items.vat_rate',
                    'wa_inventory_location_transfers.route_id',
                    'return_number',
                    'wa_inventory_location_transfer_item_returns.updated_at'
                )
                ->join('wa_inventory_location_transfer_items', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', '=', 'wa_inventory_location_transfer_items.id')
                ->join('wa_internal_requisition_items', 'wa_inventory_location_transfer_items.wa_internal_requisition_item_id', '=', 'wa_internal_requisition_items.id')
                ->join('wa_inventory_location_transfers', function ($join) {
                    $join->on('wa_inventory_location_transfers.id', '=', 'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_id');
                })
                ->whereBetween('wa_inventory_location_transfer_item_returns.updated_at', [Carbon::parse($request->start)->startOfDay(), Carbon::parse($request->end)->endOfDay()])
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
                $totalSalesInclusive = $return->total_cost_with_vat;
                $vatAmount = $return->vat;
                $totalSalesExclusive = $totalSalesInclusive - $vatAmount;

                $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
                $series_module = WaNumerSeriesCode::where('module', 'RETURN')->first();

                $documentNo = $return->return_number;

                $salesAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('account_code', '56002-003')->first();
                $salesCredit = new WaGlTran();
                $salesCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                $salesCredit->grn_type_number = $series_module?->type_number ?? 109;
                $salesCredit->trans_date = Carbon::parse($return->updated_at);
                $salesCredit->restaurant_id = 10;
                $salesCredit->tb_reporting_branch = 10;
                $salesCredit->grn_last_used_number = $series_module?->last_number_used;
                $salesCredit->transaction_type = $series_module?->description ?? 'Return';
                $salesCredit->transaction_no = $documentNo;
                $salesCredit->narrative = "{$route->route_name} - $documentNo - Returns Exc";
                $salesCredit->account = $salesAccount->account_code;
                $salesCredit->amount = $totalSalesExclusive;
                $salesCredit->customer_id = WaCustomer::where('route_id', $route->id)->first()->id;
                $salesCredit->save();

                $taxManager = TaxManager::find(1);
                $vatControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $taxManager->output_tax_gl_account)->first();
                $vatCredit = new WaGlTran();
                $vatCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                $vatCredit->grn_type_number = $series_module?->type_number ?? 109;
                $vatCredit->trans_date = Carbon::parse($return->updated_at);
                $vatCredit->restaurant_id = 10;
                $vatCredit->tb_reporting_branch = 10;
                $vatCredit->grn_last_used_number = $series_module?->last_number_used;
                $vatCredit->transaction_type = $series_module?->description ?? 'Return';
                $vatCredit->transaction_no = $documentNo;
                $vatCredit->narrative = "$route->route_name - {$documentNo} - VAT Return";
                $vatCredit->account = $vatControlAccount->account_code;
                $vatCredit->amount = $vatAmount;
                $vatCredit->customer_id = WaCustomer::where('route_id', $route->id)->first()->id;
                $vatCredit->save();

                $companyPreferences = WaCompanyPreference::find(1);
                $debtorsControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $companyPreferences->debtors_control_gl_account)->first();
                $debtorsDebit = new WaGlTran();
                $debtorsDebit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
                $debtorsDebit->grn_type_number = $series_module?->type_number ?? 109;
                $debtorsDebit->trans_date = Carbon::parse($return->updated_at);
                $debtorsDebit->restaurant_id = 10;
                $debtorsDebit->tb_reporting_branch = 10;
                $debtorsDebit->grn_last_used_number = $series_module?->last_number_used;
                $debtorsDebit->transaction_type = $series_module?->description ?? 'Return';
                $debtorsDebit->transaction_no = $documentNo;
                $debtorsDebit->narrative = "$route->route_name - {$documentNo} - Debtors Return";
                $debtorsDebit->account = $debtorsControlAccount->account_code;
                $debtorsDebit->amount = $totalSalesInclusive * -1;
                $debtorsDebit->customer_id = WaCustomer::where('route_id', $route->id)->first()->id;
                $debtorsDebit->save();
            }

            DB::commit();
            return $this->jsonify([
                'status' => 'success',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function locationReport(Request $request)
    {
        $customers = DB::table('wa_route_customers')
            ->where('wa_route_customers.bussiness_name', 'like', "%$request->customer%")
            ->select(
                'wa_route_customers.id',
                'wa_route_customers.bussiness_name as shop',
                'wa_route_customers.name as owner',
                'routes.route_name as route',
                'wa_route_customers.lat as current_lat',
                'wa_route_customers.lng as current_lng',
                DB::raw("(select count(*) from wa_internal_requisitions as orders where orders.wa_route_customer_id = wa_route_customers.id and date(orders.created_at) > '2024-07-07') as orders_since_monday"),
                DB::raw("(select status from order_location_logs as logs where logs.shop_id = wa_route_customers.id and status in ('passed', 'failed_with_update') order by created_at desc limit 1) as last_salesman_status"),
                DB::raw("(select distance from order_location_logs as logs where logs.shop_id = wa_route_customers.id and status in ('passed', 'failed_with_update') order by created_at desc limit 1) as last_salesman_distance"),
                DB::raw("(select salesman_lat from order_location_logs as logs where logs.shop_id = wa_route_customers.id and status in ('passed', 'failed_with_update') order by created_at desc limit 1) as last_salesman_lat"),
                DB::raw("(select salesman_lng from order_location_logs as logs where logs.shop_id = wa_route_customers.id and status in ('passed', 'failed_with_update') order by created_at desc limit 1) as last_salesman_lng"),
                DB::raw("(select created_at from order_location_logs as logs where logs.shop_id = wa_route_customers.id and status in ('passed', 'failed_with_update') order by created_at desc limit 1) as last_salesman_ts"),
                DB::raw("(select driver_status from order_location_logs as logs where logs.shop_id = wa_route_customers.id and driver_status in ('passed', 'failed_with_update') order by updated_at desc limit 1) as last_driver_status"),
                DB::raw("(select driver_distance from order_location_logs as logs where logs.shop_id = wa_route_customers.id and driver_status in ('passed', 'failed_with_update') order by updated_at desc limit 1) as last_driver_distance"),
                DB::raw("(select driver_lat from order_location_logs as logs where logs.shop_id = wa_route_customers.id and driver_status in ('passed', 'failed_with_update') order by updated_at desc limit 1) as last_driver_lat"),
                DB::raw("(select driver_lng from order_location_logs as logs where logs.shop_id = wa_route_customers.id and driver_status in ('passed', 'failed_with_update') order by updated_at desc limit 1) as last_driver_lng"),
                DB::raw("(select updated_at from order_location_logs as logs where logs.shop_id = wa_route_customers.id and driver_status in ('passed', 'failed_with_update') order by updated_at desc limit 1) as last_driver_ts"),
            )
            ->join('routes', function ($join) {
                $join->on('wa_route_customers.route_id', '=', 'routes.id')->where('routes.restaurant_id', 10);
            })
            ->skip(0)
            ->take(500)
            ->get();

        return ExcelDownloadService::download(
            'location_report',
            $customers,
            [
                'ID',
                'shop',
                'owner',
                'route',
                'current_lat',
                'current_lng',
                'orders_since_monday',
                'last_salesman_status',
                'last_salesman_distance',
                'last_salesman_lat',
                'last_salesman_lng',
                'last_salesman_ts',
                'last_driver_status',
                'last_driver_distance',
                'last_driver_lat',
                'last_driver_lng',
                'last_driver_ts'
            ]
        );
    }

    public function vehicleTelematics(Request $request)
    {
        $data = DB::connection('telematics')->table('vehicle_telematics')
            ->where('device_number', $request->vehicle)
            ->whereBetween('created_at', [$request->start, $request->end])
            ->get();

        $vehicleRecords = [];
        foreach ($data as $record) {
            $vehicleRecords = array_merge($vehicleRecords, json_decode($record->data, true));
        }

        return $vehicleRecords;
    }

    public function recalcNewQoH(Request $request)
    {
        try {
            ini_set('max_execution_time', 600);

            if (!$request->code) {
                return $this->jsonify(['message' => 'No code supplied'], 500);
            }

            if (!$request->store) {
                return $this->jsonify(['message' => 'No store supplied'], 500);
            }

            $records = DB::table('wa_stock_moves')
                ->select('id', 'qauntity', 'new_qoh')
                ->where('stock_id_code', $request->code)
                ->where('wa_location_and_store_id', $request->store)
                ->get();

            $prevQoH = 0;
            foreach ($records as $record) {
                $newQoH = $record->qauntity + $prevQoH;
                $prevQoH = $newQoH;
                WaStockMove::find($record->id)->update(['new_qoh' => $newQoH]);
            }

            return $this->jsonify('success', 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function misc()
    {
        return $this->jsonify(DB::table('otp')->where('status', 1)->orderBy('id', 'DESC')->get(), 200);
    }
}
