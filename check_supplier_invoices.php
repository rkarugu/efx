<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$supplier = App\Model\WaSupplier::where('supplier_code', 'SUP-01353')->first();

if (!$supplier) {
    echo "Supplier SUP-01353 not found!\n";
    exit(1);
}

echo "Supplier ID: " . $supplier->id . "\n";
echo "Supplier Code: " . $supplier->supplier_code . "\n";
echo "Supplier Name: " . $supplier->name . "\n\n";

echo "=== wa_supp_trans counts ===\n";
echo "By supplier_id (1463): " . App\Model\WaSuppTran::where('supplier_no', $supplier->id)->count() . "\n";
echo "By supplier_code (SUP-01353): " . App\Model\WaSuppTran::where('supplier_no', $supplier->supplier_code)->count() . "\n\n";

echo "=== wa_supplier_invoices counts ===\n";
echo "By supplier_id: " . App\WaSupplierInvoice::where('supplier_id', $supplier->id)->count() . "\n\n";

echo "=== Sample wa_supp_trans records (if any) ===\n";
$sample = App\Model\WaSuppTran::where('supplier_no', $supplier->id)->first();
if ($sample) {
    echo "Found record ID: " . $sample->id . "\n";
    echo "GRN Number: " . ($sample->grn_number ?? 'N/A') . "\n";
    echo "Total Amount: " . $sample->total_amount_inc_vat . "\n";
    echo "Settled: " . $sample->settled . "\n";
    echo "Has invoice relation: " . ($sample->invoice ? 'YES' : 'NO') . "\n";
} else {
    echo "No wa_supp_trans records found for this supplier.\n";
}

echo "\n=== Sample wa_supplier_invoices records (if any) ===\n";
$invoiceSample = App\WaSupplierInvoice::where('supplier_id', $supplier->id)->first();
if ($invoiceSample) {
    echo "Found invoice ID: " . $invoiceSample->id . "\n";
    echo "GRN Number: " . ($invoiceSample->grn_number ?? 'N/A') . "\n";
    echo "Supplier Invoice Number: " . ($invoiceSample->supplier_invoice_number ?? 'N/A') . "\n";
    echo "wa_supp_tran_id: " . ($invoiceSample->wa_supp_tran_id ?? 'N/A') . "\n";
} else {
    echo "No wa_supplier_invoices records found for this supplier.\n";
}

echo "\nDone.\n";
