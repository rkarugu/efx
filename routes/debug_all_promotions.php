<?php

use App\ItemPromotion;
use App\Models\PromotionType;
use App\Model\WaInventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

// Debug route to check all active promotions
Route::get('/debug-all-promotions', function() {
    $today = Carbon::today();
    
    echo "<h2>All Active Promotions in System</h2>";
    echo "<p>Today's date: " . $today->toDateString() . "</p>";
    
    // Get all promotions with status 'active'
    $allActivePromotions = ItemPromotion::where('status', 'active')
        ->with(['inventoryItem', 'promotionType'])
        ->get();
    
    echo "<h3>Found " . $allActivePromotions->count() . " active promotions:</h3>";
    
    if ($allActivePromotions->count() == 0) {
        echo "<p style='color: red;'>❌ No active promotions found in the system!</p>";
        
        // Check if there are any promotions at all
        $anyPromotions = ItemPromotion::all();
        echo "<h3>All promotions (any status): " . $anyPromotions->count() . "</h3>";
        
        foreach($anyPromotions as $promo) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>Promotion ID:</strong> {$promo->id}<br>";
            echo "<strong>Item ID:</strong> {$promo->inventory_item_id}<br>";
            echo "<strong>Status:</strong> {$promo->status}<br>";
            echo "<strong>From Date:</strong> {$promo->from_date}<br>";
            echo "<strong>To Date:</strong> " . ($promo->to_date ?? 'NULL') . "<br>";
            
            if ($promo->inventoryItem) {
                echo "<strong>Item Name:</strong> {$promo->inventoryItem->title}<br>";
            }
            
            echo "</div>";
        }
    } else {
        foreach($allActivePromotions as $promo) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>Promotion ID:</strong> {$promo->id}<br>";
            echo "<strong>Item ID:</strong> {$promo->inventory_item_id}<br>";
            echo "<strong>Status:</strong> {$promo->status}<br>";
            echo "<strong>From Date:</strong> {$promo->from_date}<br>";
            echo "<strong>To Date:</strong> " . ($promo->to_date ?? 'NULL') . "<br>";
            echo "<strong>Current Price:</strong> {$promo->current_price}<br>";
            echo "<strong>Promotion Price:</strong> {$promo->promotion_price}<br>";
            
            if ($promo->inventoryItem) {
                echo "<strong>Item Name:</strong> {$promo->inventoryItem->title}<br>";
                echo "<strong>Item Code:</strong> {$promo->inventoryItem->stock_id_code}<br>";
            }
            
            if ($promo->promotionType) {
                echo "<strong>Promotion Type:</strong> {$promo->promotionType->description}<br>";
            }
            
            // Check if this promotion should be active today
            $isDateValid = $promo->from_date <= $today && ($promo->to_date >= $today || is_null($promo->to_date));
            echo "<strong>Valid for today:</strong> " . ($isDateValid ? 'YES' : 'NO') . "<br>";
            
            echo "<p><a href='/debug-promotion/{$promo->inventory_item_id}' target='_blank'>🔍 Debug this item</a></p>";
            echo "</div>";
        }
    }
    
    // Also check the specific promotion ID from the URL
    echo "<h3>Check Promotion ID 4 (from your URL):</h3>";
    $specificPromo = ItemPromotion::find(4);
    if ($specificPromo) {
        echo "<div style='border: 2px solid #007cba; padding: 10px; margin: 5px;'>";
        echo "<strong>Promotion ID:</strong> {$specificPromo->id}<br>";
        echo "<strong>Item ID:</strong> {$specificPromo->inventory_item_id}<br>";
        echo "<strong>Status:</strong> {$specificPromo->status}<br>";
        echo "<strong>From Date:</strong> {$specificPromo->from_date}<br>";
        echo "<strong>To Date:</strong> " . ($specificPromo->to_date ?? 'NULL') . "<br>";
        
        if ($specificPromo->inventoryItem) {
            echo "<strong>Item Name:</strong> {$specificPromo->inventoryItem->title}<br>";
            echo "<strong>Item Code:</strong> {$specificPromo->inventoryItem->stock_id_code}<br>";
        }
        
        echo "<p><a href='/debug-promotion/{$specificPromo->inventory_item_id}' target='_blank'>🔍 Debug this specific item</a></p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Promotion ID 4 not found!</p>";
    }
});
