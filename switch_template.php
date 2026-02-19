<?php

$file = 'C:\laragon\www\kaninichapchap\app\Http\Controllers\Api\SalesOrdersController.php';
$content = file_get_contents($file);

// Switch back to original receipt template
$content = str_replace(
    "loadView('receipt_thermal'",
    "loadView('receipt'",
    $content
);

file_put_contents($file, $content);

echo "Switched back to original receipt template with reduced fonts!\n";
