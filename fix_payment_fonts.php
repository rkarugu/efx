<?php

$file = 'C:\laragon\www\kaninichapchap\resources\views\receipt.blade.php';
$content = file_get_contents($file);

// Find and replace the payment channels section with smaller fonts
$oldSection = '<div class="dashed" style="text-align: center">
        <h2>PAYMENT CHANNELS</h2>
        <h3>(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS)</h3>';

$newSection = '<div class="dashed" style="text-align: center">
        <h2 style="font-size: 12px; margin: 3px 0;">PAYMENT CHANNELS</h2>
        <h3 style="font-size: 9px; margin: 2px 0; font-weight: normal;">(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS)</h3>';

$content = str_replace($oldSection, $newSection, $content);

// Also add inline styles to any h2/h3 in payment section if they exist elsewhere
$content = preg_replace(
    '/<h2>PAYMENT CHANNELS<\/h2>/',
    '<h2 style="font-size: 12px; margin: 3px 0;">PAYMENT CHANNELS</h2>',
    $content
);

$content = preg_replace(
    '/<h3>\(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS\)<\/h3>/',
    '<h3 style="font-size: 9px; margin: 2px 0; font-weight: normal;">(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS)</h3>',
    $content
);

file_put_contents($file, $content);

echo "Payment channels font sizes reduced!\n";
echo "H2 (PAYMENT CHANNELS): 12px\n";
echo "H3 (subtitle): 9px\n";
