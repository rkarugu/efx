<?php

use App\ItemPromotion;
use App\Models\PromotionType;
use App\Enums\PromotionMatrix;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

// Debug route to check promotion issues
Route::get('/debug-promotion/{item_id}', function($item_id) {
    $today = Carbon::today();
    
    echo "<h2>Promotion Debug for Item ID: $item_id</h2>";
    echo "<p>Today's date: " . $today->toDateString() . "</p>";
    
    // Check all promotions for this item
    $allPromotions = ItemPromotion::where('inventory_item_id', $item_id)->get();
    echo "<h3>All Promotions for this item:</h3>";
    foreach($allPromotions as $promo) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
        echo "<strong>ID:</strong> {$promo->id}<br>";
        echo "<strong>Status:</strong> {$promo->status}<br>";
        echo "<strong>From Date:</strong> {$promo->from_date}<br>";
        echo "<strong>To Date:</strong> " . ($promo->to_date ?? 'NULL') . "<br>";
        echo "<strong>Promotion Type ID:</strong> {$promo->promotion_type_id}<br>";
        echo "<strong>Current Price:</strong> {$promo->current_price}<br>";
        echo "<strong>Promotion Price:</strong> {$promo->promotion_price}<br>";
        
        // Check if this promotion should be active
        $isActive = $promo->status == 'active';
        $isDateValid = $promo->from_date <= $today && ($promo->to_date >= $today || is_null($promo->to_date));
        
        echo "<strong>Should be active:</strong> " . ($isActive && $isDateValid ? 'YES' : 'NO') . "<br>";
        echo "<strong>Status check:</strong> " . ($isActive ? 'PASS' : 'FAIL') . "<br>";
        echo "<strong>Date check:</strong> " . ($isDateValid ? 'PASS' : 'FAIL') . "<br>";
        
        if ($promo->promotion_type_id) {
            $promotionType = PromotionType::find($promo->promotion_type_id);
            echo "<strong>Promotion Type:</strong> " . ($promotionType ? $promotionType->description : 'NOT FOUND') . "<br>";
        }
        
        echo "</div>";
    }
    
    // Test the current query logic
    echo "<h3>Current Query Result:</h3>";
    $currentPromotion = ItemPromotion::where('inventory_item_id', $item_id)
        ->where('status', 'active')
        ->where(function ($query) use ($today) {
            $query->where('from_date', '<=', $today)
                ->where('to_date', '>=', $today);
        })
        ->first();
        
    if ($currentPromotion) {
        echo "<p style='color: green;'>✅ Current query finds promotion ID: {$currentPromotion->id}</p>";
    } else {
        echo "<p style='color: red;'>❌ Current query finds NO promotion</p>";
    }
    
    // Test improved query logic
    echo "<h3>Improved Query Result:</h3>";
    $improvedPromotion = ItemPromotion::where('inventory_item_id', $item_id)
        ->where('status', 'active')
        ->where(function ($query) use ($today) {
            $query->where('from_date', '<=', $today)
                ->where(function ($subQuery) use ($today) {
                    $subQuery->where('to_date', '>=', $today)
                             ->orWhereNull('to_date');
                });
        })
        ->first();
        
    if ($improvedPromotion) {
        echo "<p style='color: green;'>✅ Improved query finds promotion ID: {$improvedPromotion->id}</p>";
        
        // Calculate discount
        if ($improvedPromotion->promotion_type_id) {
            $promotionType = PromotionType::find($improvedPromotion->promotion_type_id);
            if ($promotionType && $promotionType->description == PromotionMatrix::PD->value) {
                $discount = $improvedPromotion->current_price - $improvedPromotion->promotion_price;
                echo "<p style='color: blue;'>💰 Calculated discount: KSh {$discount}</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Improved query finds NO promotion</p>";
    }
});
