<?php

namespace App\Jobs;

use App\Interfaces\SmsService;
use App\Model\TaxManager;
use App\Model\WaAccountingPeriod;
use App\Model\WaCompanyPreference;
use App\Model\WaCustomer;
use App\Model\WaDebtorTran;
use App\Model\WaEsdDetails;
use App\Model\WaGlTran;
use App\Model\WaInternalRequisition;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryLocationTransfer;
use App\Model\WaInventoryLocationTransferItem;
use App\Model\WaNumerSeriesCode;
use App\Model\WaRouteCustomer;
use App\Model\WaStockMove;
use App\SalesmanShift;
use App\Services\InfoSkySmsService;
use App\Services\SupplierIncentiveCalculator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Model\Restaurant;

class PerformPostSaleActions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public function __construct(public WaInternalRequisition $internalRequisition) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /**
         * 1. Transfer stock (with moves)
         * 2. mark customer as visited
         * 3. Debtors control
         * 4. Sign
         * 5. Post GL
         * 6. Notify customer
         */

        try {

            $this->recordStockTransfer();
            $this->recordStockMoves();
            $this->recordDebtorTrans();
            $this->postGlTrans();

            if (env('APP_URL') == 'https://bizwizkaniniharaka.com') {
                if (!str_starts_with($this->internalRequisition->slug, 'civ')) {
                    $this->signInvoice();
                }
            }

            if (!str_starts_with($this->internalRequisition->slug, 'civ')) {
                if ($this->internalRequisition->invoice_type != 'Backend') {
                    $this->markCustomerAsVisited();
                    $this->notifyCustomer();
                }
            }

            $this->recordSupplierIncentives();
        } catch (\Throwable $e) {
            Log::info("Failed after sales");
            Log::error($e->getMessage(), $e->getTrace());

            // try {
            //     $invoice_number = $this->internalRequisition->requisition_no;
            //     (new InfoSkySmsService())->sendMessage("Failed post sales for $invoice_number, saying {$e->getMessage()}", '0790544563');
            // } catch (\Throwable $e) {
            //     // pass
            // }
        }
    }

    private function recordStockTransfer(): void
    {
        $transfer = new WaInventoryLocationTransfer();
        $transfer->transfer_no = $this->internalRequisition->requisition_no;
        $transfer->transfer_date = $this->internalRequisition->requisition_date;
        $transfer->restaurant_id = $this->internalRequisition->restaurant_id;
        $transfer->wa_department_id = 0;
        $transfer->user_id = $this->internalRequisition->user_id;
        $transfer->to_store_location_id = $this->internalRequisition->to_store_id;
        $transfer->route = $this->internalRequisition->route;
        $transfer->route_id = $this->internalRequisition->route_id;
        $transfer->customer = $this->internalRequisition->customer;
        $transfer->customer_id = $this->internalRequisition->customer_id;
        $transfer->status = $this->internalRequisition->status;
        $transfer->shift_id = $this->internalRequisition->wa_shift_id;
        $transfer->name = $this->internalRequisition->name;
        $transfer->customer_pin = $this->internalRequisition->customer_pin;
        $transfer->customer_phone_number = $this->internalRequisition->customer_phone_number;
        $transfer->wa_internal_requisitions_id = $this->internalRequisition->id;
        $transfer->created_at = $this->internalRequisition->created_at;
        $transfer->save();

        foreach ($this->internalRequisition->getRelatedItem as $invoiceItem) {
            $item = new WaInventoryLocationTransferItem();
            $item->wa_inventory_location_transfer_id = $transfer->id;
            $item->wa_inventory_item_id = $invoiceItem->wa_inventory_item_id;
            $item->quantity = $invoiceItem->quantity;
            $item->wa_internal_requisition_item_id = $invoiceItem->id;
            $item->issued_quantity = $invoiceItem->quantity;
            $item->note = "";
            $item->standard_cost = $invoiceItem->standard_cost;
            $item->total_cost = $invoiceItem->total_cost;
            $item->vat_rate = $invoiceItem->vat_rate;
            $item->vat_amount = $invoiceItem->vat_amount;
            $item->total_cost_with_vat = $invoiceItem->total_cost_with_vat;
            $item->selling_price = $invoiceItem->selling_price;
            $item->discount_amount = $invoiceItem->discount;
            $item->store_location_id = $this->internalRequisition->to_store_id;
            $item->created_at = $this->internalRequisition->created_at;
            $item->save();
        }
    }

    private function recordStockMoves(): void
    {
        foreach ($this->internalRequisition->getRelatedItem as $invoiceItem) {
            $inventoryItem = $invoiceItem->getInventoryItemDetail;

            $stockMove = new WaStockMove();
            $stockMove->user_id = $this->internalRequisition->user_id;
            $stockMove->wa_internal_requisition_id = $this->internalRequisition->id;
            $stockMove->restaurant_id = $this->internalRequisition->restaurant_id;
            $stockMove->wa_location_and_store_id = $this->internalRequisition->to_store_id;
            $stockMove->wa_inventory_item_id = $inventoryItem->id;
            $stockMove->standard_cost = $inventoryItem->standard_cost;
            $stockMove->qauntity = $invoiceItem->quantity * -1;
            $stockMove->new_qoh = ($inventoryItem->getAllFromStockMoves->where('wa_location_and_store_id', $this->internalRequisition->to_store_id)->sum('qauntity') ?? 0) - $invoiceItem->quantity;
            $stockMove->stock_id_code = $inventoryItem->stock_id_code;
            $stockMove->document_no = $this->internalRequisition->requisition_no;
            $stockMove->shift_id = $this->internalRequisition->wa_shift_id;
            $stockMove->refrence = "{$this->internalRequisition->route} - {$this->internalRequisition->requisition_no}";
            $stockMove->price = $invoiceItem->total_cost;
            $stockMove->total_cost = $invoiceItem->total_cost;
            $stockMove->selling_price = $invoiceItem->selling_price;
            $stockMove->route_id = $this->internalRequisition->route_id;
            $stockMove->created_at = $this->internalRequisition->created_at;


            $stockMove->save();
        }
    }

    private function recordDebtorTrans(): void
    {
        $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITIONS')->first();

        $debtorTran = new WaDebtorTran();
        $debtorTran->wa_sales_invoice_id = $this->internalRequisition->id;
        $debtorTran->type_number = $series_module ? $series_module->type_number : '';
        $debtorTran->wa_customer_id = $this->internalRequisition->customer_id;
        $debtorTran->salesman_id = $this->internalRequisition->to_store_id;
        $debtorTran->customer_number = WaCustomer::find($this->internalRequisition->customer_id)->customer_code;
        $debtorTran->trans_date = $this->internalRequisition->requisition_date;
        $debtorTran->input_date = now()->format('Y-m-d H:i:s');
        $debtorTran->wa_accounting_period_id = $accountingPeriod ? $accountingPeriod->id : null;
        $debtorTran->amount = $this->internalRequisition->getOrderTotalWithoutDiscount();
        $debtorTran->document_no = $this->internalRequisition->requisition_no;
        $debtorTran->reference = "{$this->internalRequisition->route} - {$this->internalRequisition->requisition_no}";
        $debtorTran->invoice_customer_name = "{$this->internalRequisition->customer}";
        $debtorTran->branch_id = $this->internalRequisition->restaurant_id;

        $debtorTran->save();

        if ($this->internalRequisition->getTotalDiscount() > 0) {
            $discountTran = new WaDebtorTran();
            $discountTran->wa_sales_invoice_id = $this->internalRequisition->id;
            $discountTran->type_number = $series_module ? $series_module->type_number : '';
            $discountTran->wa_customer_id = $this->internalRequisition->customer_id;
            $discountTran->salesman_id = $this->internalRequisition->to_store_id;
            $discountTran->customer_number = WaCustomer::find($this->internalRequisition->customer_id)->customer_code;
            $discountTran->trans_date = $this->internalRequisition->requisition_date;
            $discountTran->input_date = now()->format('Y-m-d H:i:s');
            $discountTran->wa_accounting_period_id = $accountingPeriod ? $accountingPeriod->id : null;
            $discountTran->amount = ($this->internalRequisition->getTotalDiscount()) * -1;
            $discountTran->document_no = $this->internalRequisition->requisition_no;
            $discountTran->reference = "{$this->internalRequisition->route} - {$this->internalRequisition->requisition_no} Discount Allowed";
            $discountTran->invoice_customer_name = "{$this->internalRequisition->customer}";
            $debtorTran->branch_id = $this->internalRequisition->restaurant_id;

            $discountTran->save();
        }
    }

    private function postGlTrans(): void
    {
        try {
            $accountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
            $series_module = WaNumerSeriesCode::where('module', 'INTERNAL REQUISITION')->first();

            $totalSalesInclusive = $this->internalRequisition->getRelatedItem()->sum('total_cost_with_vat');
            $vatAmount = $this->internalRequisition->getRelatedItem()->sum('vat_amount');
            $totalSalesExclusive = $totalSalesInclusive - $vatAmount;

            $salesAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('account_code', '56002-003')->first();
            $salesCredit = new WaGlTran();
            $salesCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
            $salesCredit->grn_type_number = $series_module?->type_number ?? 51;
            $salesCredit->trans_date = $this->internalRequisition->created_at;
            $salesCredit->restaurant_id = $this->internalRequisition->restaurant_id;
            $salesCredit->tb_reporting_branch = $this->internalRequisition->restaurant_id;
            $salesCredit->grn_last_used_number = $series_module?->last_number_used;
            $salesCredit->transaction_type = $series_module?->description ?? 'Invoice';
            $salesCredit->transaction_no = $this->internalRequisition->requisition_no;
            $salesCredit->narrative = "{$this->internalRequisition->route} - {$this->internalRequisition->requisition_no} - Sales Exc";
            $salesCredit->account = $salesAccount->account_code;
            $salesCredit->amount = $totalSalesExclusive * -1;
            $salesCredit->customer_id = $this->internalRequisition->customer_id;
            $salesCredit->save();

            $taxManager = TaxManager::find(1);
            $vatControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $taxManager->output_tax_gl_account)->first();
            $vatCredit = new WaGlTran();
            $vatCredit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
            $vatCredit->grn_type_number = $series_module?->type_number ?? 51;
            $vatCredit->trans_date = $this->internalRequisition->created_at;
            $vatCredit->restaurant_id = $this->internalRequisition->restaurant_id;
            $vatCredit->tb_reporting_branch = $this->internalRequisition->restaurant_id;
            $vatCredit->grn_last_used_number = $series_module?->last_number_used;
            $vatCredit->transaction_type = $series_module?->description ?? 'Invoice';
            $vatCredit->transaction_no = $this->internalRequisition->requisition_no;
            $vatCredit->narrative = "{$this->internalRequisition->route} - {$this->internalRequisition->requisition_no} - VAT Amount";
            $vatCredit->account = $vatControlAccount->account_code;
            $vatCredit->amount = $vatAmount * -1;
            $vatCredit->customer_id = $this->internalRequisition->customer_id;
            $vatCredit->save();

            $companyPreferences = WaCompanyPreference::find(1);
            $debtorsControlAccount = DB::table('wa_charts_of_accounts')->select('id', 'account_code')->where('id', $companyPreferences->debtors_control_gl_account)->first();
            $cashAccountCode = '54008-000';

            $debtorsDebit = new WaGlTran();
            $debtorsDebit->period_number = $accountingPeriod ? $accountingPeriod->period_no : null;
            $debtorsDebit->grn_type_number = $series_module?->type_number ?? 51;
            $debtorsDebit->trans_date = $this->internalRequisition->created_at;
            $debtorsDebit->restaurant_id = $this->internalRequisition->restaurant_id;
            $debtorsDebit->tb_reporting_branch = $this->internalRequisition->restaurant_id;
            $debtorsDebit->grn_last_used_number = $series_module?->last_number_used;
            $debtorsDebit->transaction_type = $series_module?->description ?? 'Invoice';
            $debtorsDebit->transaction_no = $this->internalRequisition->requisition_no;
            $debtorsDebit->narrative = "{$this->internalRequisition->route} - {$this->internalRequisition->requisition_no} - " . str_starts_with($this->internalRequisition->slug, 'civ') ? 'Cash Account' : 'Debtors Control';
            $debtorsDebit->account = str_starts_with($this->internalRequisition->slug, 'civ') ? $cashAccountCode : $debtorsControlAccount->account_code;
            $debtorsDebit->amount = $totalSalesInclusive;
            $debtorsDebit->customer_id = $this->internalRequisition->customer_id;
            $debtorsDebit->save();
        } catch (Throwable $e) {
            //do nothing
        }
    }

    private function signInvoice(): void
    {
        $invoice = $this->internalRequisition;
        $invoiceSigned = true;
        $message = null;
        try {
            $settings = getAllSettings();
            $clientPin = $settings['PIN_NO'];
            $esdUrl = $settings['ESD_URL'];
            $apiUrl = "$esdUrl/api/sign?invoice+1";
            $vatAmount = 0;

            // $vatAmount = DB::table('wa_internal_requisition_items')->where('wa_internal_requisition_id', $invoice->id)->sum('vat_amount');
            $payload = [
                "invoice_date" => Carbon::parse($invoice->created_at)->format('d_m_Y'),
                "invoice_number" => $invoice->requisition_no,
                "invoice_pin" => $clientPin,
                "customer_pin" => $invoice->customer->kra_pin ?? "",
                "customer_exid" => "",
                "grand_total" => number_format($invoice->getOrderTotalForEsd(), 2),
                "net_subtotal" => number_format($invoice->getOrderTotalForEsd() - $vatAmount, 2),
                "tax_total" => number_format($vatAmount, 2),
                "net_discount_total" => "0",
                "sel_currency" => "KSH",
                "rel_doc_number" => "",
                "items_list" => []
            ];

            $grandTotal = 0;
            $taxManagers = DB::table('tax_managers')->select('id', 'title', 'tax_value')->get();
            foreach ($invoice->getRelatedItem as $item) {
                $itemTotal = $item->selling_price * $item->quantity;
                $grandTotal += $itemTotal;

                $inventoryItem = DB::table('wa_inventory_items')->find($item->wa_inventory_item_id);
                $taxManager = $taxManagers->where('id', $inventoryItem->tax_manager_id)->first();
                if ($taxManager) {
                    $vatRate = (float)$taxManager->tax_value;
                    $vatAmount += ($vatRate / (100 + $vatRate)) * $itemTotal;
                }

                $itemTotal = manageAmountFormat($itemTotal);
                $item->selling_price = manageAmountFormat($item->selling_price);
                $line = "$inventoryItem->slug $item->quantity $item->selling_price $itemTotal";
                if ($inventoryItem->hs_code) {
                    $line = "$inventoryItem->hs_code " . $line;
                }

                $payload['items_list'][] = $line;
            }

            $payload['tax_total'] = number_format($vatAmount, 2);
            $payload['grand_total'] = number_format($grandTotal, 2);
            $payload['net_subtotal'] = number_format($grandTotal - $vatAmount, 2);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ZxZoaZMUQbUJDljA7kTExQ==',
            ])->post($apiUrl, $payload);

            $responseData = json_decode($response->body(), true);
            $newEsd = new WaEsdDetails();

            if ($response->ok()) {
                $newEsd->invoice_number = $responseData['invoice_number'];
                $newEsd->cu_serial_number = $responseData['cu_serial_number'];
                $newEsd->cu_invoice_number = $responseData['cu_invoice_number'];
                $newEsd->verify_url = $responseData['verify_url'] ?? null;
                $newEsd->description = $responseData['description'] ?? null;
                $newEsd->status = 1;
                $newEsd->save();
            } else {
                $newEsd->invoice_number = $invoice->requisition_no;
                $newEsd->description = $response->body();
                $newEsd->status = 0;
                $newEsd->save();

                $invoiceSigned = false;
                $message = $response->body();
            }
        } catch (\Throwable $e) {
            $newEsd = new WaEsdDetails();
            $newEsd->invoice_number = $invoice->requisition_no;
            $newEsd->description = $e->getMessage();
            $newEsd->status = 0;
            $newEsd->save();

            $invoiceSigned = false;
            $message = $e->getMessage();
        }

        try {
            if (!$invoiceSigned) {
                (new InfoSkySmsService())->sendMessage(
                    "$newEsd->invoice_number failed to sign citing: $message",
                    "0790544563" // Isaac's number
                );
            }
        } catch (\Throwable $e) {
            // 
        }
    }

    private function markCustomerAsVisited(): void
    {
        $shift = SalesmanShift::with('shiftCustomers')->find($this->internalRequisition->wa_shift_id);
        $routeCustomerRecord = $shift->shiftCustomers()->where('route_customer_id', $this->internalRequisition->wa_route_customer_id)->first();
        if ($routeCustomerRecord) {
            $routeCustomerRecord->update(['visited' => 1, 'order_taken' => true, 'salesman_shift_type' => $shift->shift_type]);
        }
    }

    private function notifyCustomer(): void
    {
        $invoice = $this->internalRequisition;
        $restaurant = Restaurant::find($invoice->restaurant_id);
        $routeCustomer = WaRouteCustomer::find($invoice->wa_route_customer_id);
        $customer = WaCustomer::find($invoice->customer_id);
        $smsItems = [];
        $receiptTotal = 0;
        foreach ($invoice->getRelatedItem as $item) {
            $inventoryItem = DB::table('wa_inventory_items')->find($item->wa_inventory_item_id);
            $receiptTotal += $item->total_cost_with_vat;
            $payload = [
                'title' => $inventoryItem->title,
                'Qty' => $item->quantity,
                'total_cost_with_vat' => $item->total_cost_with_vat,
            ];
            $smsItems[] = $payload;
        }
        $paybills = [$restaurant->kcb_mpesa_paybill];
        // shuffle($paybills);
        $payment_code = substr($invoice->requisition_no, 4);


        $msgHeader = "Dear $routeCustomer->name , a Doc No: $invoice->requisition_no has been generated in your A/C, the value is:\n Ksh" . manageAmountFormat($receiptTotal) . "\n";
        foreach ($smsItems as $index => $orderItem) {
            $msgHeader .= ($index + 1) . ". " . $orderItem['title'] . " Qty: " . $orderItem['Qty'] . " Amount: " . manageAmountFormat($orderItem['total_cost_with_vat']) . "\n";
        }
        // $customerMessageWithItems = $msgHeader .= "DO NOT PAY CASH, PAY USING: \n VOOMA: $customer->kcb_till \n EAZZYPAY: $customer->equity_till \nCOMPANY WILL NOT BE RESPONSIBLE FOR ANY CASH GIVEN  TO A STAFF \n Cell = 0723030848/0726765432";
        $customerMessageWithItems = $msgHeader .= "DO NOT PAY CASH, PAY USING: \nMPESA Paybill: $paybills[0] \nAccount No: $payment_code \nVooma Paybill: $restaurant->kcb_vooma_paybill \nAccount No: $payment_code \nEquity Biller No: $restaurant->equity_paybill \nAccount No: $payment_code \nCOMPANY WILL NOT BE RESPONSIBLE FOR ANY CASH GIVEN  TO A STAFF \nCell = 0723030848/0726765432";

        try {
            (new InfoSkySmsService())->sendMessage($customerMessageWithItems, $routeCustomer->phone);
        } catch (\Throwable $e) {
            // pass
        }
    }

    public function recordSupplierIncentives(): void
    {
        try {
            $stockmoves  = WaStockMove::where('wa_internal_requisition_id', $this->internalRequisition->id)->get();
            foreach ($stockmoves as $stockmove) {
                SupplierIncentiveCalculator::add($stockmove);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
