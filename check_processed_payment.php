<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$s = App\Model\WaSupplier::where('supplier_code', 'SUP-01353')->first();
if (!$s) {
    echo "Supplier SUP-01353 not found\n";
    exit;
}
echo "Supplier ID: {$s->id}\n";

$vouchers = App\PaymentVoucher::where('wa_supplier_id', $s->id)->orderBy('id', 'desc')->take(2)->get();
foreach ($vouchers as $voucher) {
    echo "Voucher: {$voucher->number} (ID: {$voucher->id})\n";
    echo "  Status: {$voucher->status}\n";
    
    $bfi = DB::table('wa_bank_file_items')->where('payment_voucher_id', $voucher->id)->first();
    echo $bfi ? "  Bank File ID: {$bfi->wa_bank_file_id}\n" : "  NO Bank File linked\n";
    
    $st = App\Model\WaSuppTran::where('document_no', $voucher->number)->first();
    echo $st ? "  Supp Tran ID: {$st->id}, Amt: {$st->total_amount_inc_vat}, Supplier No: {$st->supplier_no}\n" : "  NO Supp Tran found\n";
    
    $items = App\PaymentVoucherItem::where('payment_voucher_id', $voucher->id)->get();
    echo "  Voucher Items: " . $items->count() . "\n";
    foreach ($items as $item) {
        $payable = $item->payable;
        echo "    - Payable Type: {$item->payable_type}, ID: {$item->payable_id}, Amt: {$item->amount}\n";
        if ($payable) {
            echo "      Settled Status: " . ($payable->settled ? 'YES' : 'NO') . "\n";
        }
    }
}

echo "\nLatest Bank Files:\n";
$bfs = App\Models\WaBankFile::orderBy('id', 'desc')->take(2)->get();
foreach ($bfs as $bf) {
    echo "Bank File: {$bf->file_no} (ID: {$bf->id}), Created At: {$bf->created_at}\n";
}
