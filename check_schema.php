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

echo "=== wa_supplier_invoices counts ===\n";
echo "For supplier_id 1463: " . DB::table('wa_supplier_invoices')->where('supplier_id', $supplier->id)->count() . "\n";
echo "Total in table: " . DB::table('wa_supplier_invoices')->count() . "\n\n";

echo "=== Sample invoices for this supplier ===\n";
$invoices = DB::table('wa_supplier_invoices')->where('supplier_id', $supplier->id)->limit(3)->get();
if ($invoices->isEmpty()) {
    echo "No invoices found for this supplier.\n";
} else {
    foreach ($invoices as $inv) {
        echo "- ID: {$inv->id}, GRN: {$inv->grn_number}, Invoice: {$inv->supplier_invoice_number}, Amount: {$inv->amount}\n";
    }
}

echo "\nDone.\n";
