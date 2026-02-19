<?php

$file = 'C:\laragon\www\kaninichapchap\app\Http\Controllers\Api\SalesOrdersController.php';
$content = file_get_contents($file);

// Replace the old template with thermal template
$content = str_replace(
    "loadView('receipt'",
    "loadView('receipt_thermal'",
    $content
);

file_put_contents($file, $content);

echo "Receipt template updated successfully!\n";
