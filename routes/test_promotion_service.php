<?php

use App\Services\PromotionService;
use Illuminate\Support\Facades\Route;

// Test the new promotion service
Route::get('/test-promotion-service/{item_id}/{quantity?}', function($item_id, $quantity = 1) {
    echo "<h2>Testing Promotion Service</h2>";
    echo "<p><strong>Item ID:</strong> $item_id</p>";
    echo "<p><strong>Quantity:</strong> $quantity</p>";
    
    $result = PromotionService::checkPromotion($item_id, $quantity);
    
    echo "<h3>Promotion Result:</h3>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
    
    $description = PromotionService::getPromotionDescription($item_id);
    if ($description) {
        echo "<h3>Promotion Description:</h3>";
        echo "<p style='color: green; font-weight: bold;'>$description</p>";
    } else {
        echo "<p style='color: red;'>No promotion description available</p>";
    }
    
    // Test different quantities for Buy X Get Y
    if ($result['promotion_type'] == 'buy_x_get_y') {
        echo "<h3>Testing Different Quantities:</h3>";
        for ($testQty = 1; $testQty <= 10; $testQty++) {
            $testResult = PromotionService::checkPromotion($item_id, $testQty);
            $freeQty = 0;
            if (!empty($testResult['free_items'])) {
                $freeQty = $testResult['free_items'][0]['quantity'];
            }
            echo "<p>Buy $testQty → Get $freeQty free</p>";
        }
    }
});
