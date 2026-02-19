<?php

$backup = 'C:\laragon\www\kaninichapchap\resources\views\receipt.blade.php.backup';
$target = 'C:\laragon\www\kaninichapchap\resources\views\receipt.blade.php';

$content = file_get_contents($backup);

// Reduce all font sizes
$content = str_replace('font-size: 50px', 'font-size: 11px', $content);
$content = str_replace('font-size: 45px', 'font-size: 10px', $content);
$content = str_replace('font-size: 40px', 'font-size: 12px', $content);
$content = str_replace('font-size: 30px', 'font-size: 9px', $content);

// Reduce border thickness
$content = str_replace('border-top: 5px dashed', 'border-top: 1px dashed', $content);
$content = str_replace('border-bottom: 5px dashed', 'border-bottom: 1px dashed', $content);

// Reduce padding
$content = str_replace('padding: 4px 0', 'padding: 2px 0', $content);
$content = str_replace('margin: 10px', 'margin: 5px', $content);
$content = str_replace('margin-top: 20px', 'margin-top: 8px', $content);
$content = str_replace('margin-top: 10px', 'margin-top: 5px', $content);

file_put_contents($target, $content);

echo "Font sizes reduced successfully!\n";
echo "Original format maintained with smaller fonts.\n";
