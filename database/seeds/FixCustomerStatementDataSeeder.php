<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixCustomerStatementDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder fixes existing customer statement data:
     * 1. Syncs existing invoice payments to wa_debtor_trans
     * 2. Adds invoice numbers to payment references
     * 3. Fixes invoice timestamps using order creation times
     */
    public function run(): void
    {
        $this->command->info('Starting customer statement data fixes...');
        
        // Step 1: Sync existing payments
        $this->syncExistingPayments();
        
        // Step 2: Fix payment records (add invoice numbers)
        $this->fixPaymentRecords();
        
        // Step 3: Fix invoice timestamps
        $this->fixInvoiceTimestamps();
        
        $this->command->info('All fixes completed successfully!');
    }
    
    /**
     * Sync existing invoice payments to wa_debtor_trans
     */
    private function syncExistingPayments()
    {
        $this->command->info('Step 1: Syncing existing payments...');
        
        $payments = DB::table('invoice_payments')
            ->whereNotNull('order_id')
            ->where('status', 'completed')
            ->get();
        
        $synced = 0;
        $skipped = 0;
        
        foreach ($payments as $payment) {
            $order = DB::table('wa_internal_requisitions')
                ->where('id', $payment->order_id)
                ->first();
            
            if (!$order || !$order->customer_id) {
                $skipped++;
                continue;
            }
            
            $exists = DB::table('wa_debtor_trans')
                ->where('document_no', $payment->payment_reference)
                ->exists();
            
            if ($exists) {
                $skipped++;
                continue;
            }
            
            $invoiceNo = $order->requisition_no ?? $order->order_no ?? 'N/A';
            
            DB::table('wa_debtor_trans')->insert([
                'wa_customer_id' => $order->customer_id,
                'wa_sales_invoice_id' => $payment->order_id,
                'trans_date' => Carbon::parse($payment->payment_date)->format('Y-m-d'),
                'input_date' => Carbon::parse($payment->payment_date)->format('Y-m-d H:i:s'),
                'amount' => -($payment->paid_amount),
                'reference' => 'PAYMENT - ' . ($payment->payment_gateway ?? 'Unknown') . ' - Invoice: ' . $invoiceNo,
                'document_no' => $payment->payment_reference,
                'created_at' => $payment->created_at ?? now(),
                'updated_at' => $payment->updated_at ?? now()
            ]);
            
            $synced++;
        }
        
        $this->command->info("  ✓ Synced: {$synced} payments");
        $this->command->info("  - Skipped: {$skipped} payments");
    }
    
    /**
     * Fix payment records to include invoice numbers
     */
    private function fixPaymentRecords()
    {
        $this->command->info('Step 2: Fixing payment records...');
        
        $payments = DB::table('wa_debtor_trans')
            ->where('reference', 'LIKE', 'PAYMENT -%')
            ->where('reference', 'NOT LIKE', '%Invoice:%')
            ->get();
        
        $fixed = 0;
        
        foreach ($payments as $payment) {
            $order = DB::table('wa_internal_requisitions')
                ->where('id', $payment->wa_sales_invoice_id)
                ->first();
            
            if (!$order) {
                continue;
            }
            
            $invoiceNo = $order->requisition_no ?? $order->order_no ?? 'N/A';
            $newReference = $payment->reference . ' - Invoice: ' . $invoiceNo;
            
            $transDate = $payment->trans_date;
            if (strlen($transDate) > 10) {
                $transDate = Carbon::parse($transDate)->format('Y-m-d');
            }
            
            $inputDate = $payment->input_date;
            if (strpos($inputDate, 'T') !== false || strlen($inputDate) > 19) {
                $inputDate = Carbon::parse($inputDate)->format('Y-m-d H:i:s');
            }
            
            DB::table('wa_debtor_trans')
                ->where('id', $payment->id)
                ->update([
                    'reference' => $newReference,
                    'trans_date' => $transDate,
                    'input_date' => $inputDate
                ]);
            
            $fixed++;
        }
        
        $this->command->info("  ✓ Fixed: {$fixed} payment records");
    }
    
    /**
     * Fix invoice timestamps using order creation times
     */
    private function fixInvoiceTimestamps()
    {
        $this->command->info('Step 3: Fixing invoice timestamps...');
        
        $invoices = DB::table('wa_debtor_trans')
            ->where(function($query) {
                $query->where('reference', 'LIKE', 'Roysambu%')
                      ->orWhere('reference', 'LIKE', 'Thika%')
                      ->orWhere('reference', 'LIKE', '%SO%');
            })
            ->where('reference', 'NOT LIKE', '%PAYMENT%')
            ->where('reference', 'NOT LIKE', '%RETURN%')
            ->get();
        
        $fixed = 0;
        $skipped = 0;
        
        foreach ($invoices as $invoice) {
            $order = DB::table('wa_internal_requisitions')
                ->where('id', $invoice->wa_sales_invoice_id)
                ->first();
            
            if (!$order) {
                $skipped++;
                continue;
            }
            
            $currentInputDate = Carbon::parse($invoice->input_date);
            $orderCreatedAt = Carbon::parse($order->created_at);
            
            if ($currentInputDate->format('Y-m-d H:i') == $orderCreatedAt->format('Y-m-d H:i')) {
                $skipped++;
                continue;
            }
            
            $newInputDate = $orderCreatedAt->format('Y-m-d H:i:s');
            
            DB::table('wa_debtor_trans')
                ->where('id', $invoice->id)
                ->update([
                    'input_date' => $newInputDate,
                    'updated_at' => now()
                ]);
            
            $fixed++;
        }
        
        $this->command->info("  ✓ Fixed: {$fixed} invoice timestamps");
        $this->command->info("  - Skipped: {$skipped} records");
    }
}
