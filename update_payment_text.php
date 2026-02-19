<?php

$file = 'C:\laragon\www\kaninichapchap\resources\views\receipt.blade.php';
$content = file_get_contents($file);

// Update the payment channels section
$content = str_replace(
    '<h2>NEW PAYMENT CHANNELS</h2>',
    '<h2>PAYMENT CHANNELS</h2>',
    $content
);

$content = str_replace(
    '<h3>(PLEASE DO NOT MAKE CASH PAYMENTS TO OUR STAFF. THE COMPANY WILL NOT BE LIABLE FOR ANY LOSS RESULTING FROM CASH
TRANSACTIONS.)</h3>',
    '<h3>(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS)</h3>',
    $content
);

// Also try the single-line version in case it's formatted differently
$content = str_replace(
    '(PLEASE DO NOT MAKE CASH PAYMENTS TO OUR STAFF. THE COMPANY WILL NOT BE LIABLE FOR ANY LOSS RESULTING FROM CASH TRANSACTIONS.)',
    '(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS)',
    $content
);

file_put_contents($file, $content);

echo "Payment channels text updated successfully!\n";
