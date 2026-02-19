<?php

use App\ItemPromotion;
use App\Models\PromotionType;
use App\Enums\PromotionMatrix;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

// Debug the exact promotion logic from PosCashSaleService
Route::get('/debug-promotion-logic/{item_id}/{quantity}', function($item_id, $quantity) {
    echo "<h2>Debug Promotion Logic for Item ID: $item_id (Qty: $quantity)</h2>";
    
    $today = Carbon::today();
    echo "<p><strong>Today:</strong> " . $today->toDateString() . "</p>";
    
    // Step 1: Check if promotion is found with the new query
    $promotion = ItemPromotion::where('inventory_item_id', $item_id)
        ->where('status', 'active')
        ->where(function ($query) use ($today) {
            $query->where('from_date', '<=', $today)
                ->where(function ($subQuery) use ($today) {
                    $subQuery->where('to_date', '>=', $today)
                             ->orWhereNull('to_date');
                });
        })
        ->first();
    
    if (!$promotion) {
        echo "<p style='color: red;'>❌ No promotion found with date filtering</p>";
        return;
    }
    
    echo "<p style='color: green;'>✅ Promotion found: ID {$promotion->id}</p>";
    echo "<div style='border: 1px solid #ccc; padding: 10px;'>";
    echo "<strong>Promotion Details:</strong><br>";
    echo "ID: {$promotion->id}<br>";
    echo "Status: {$promotion->status}<br>";
    echo "From Date: {$promotion->from_date}<br>";
    echo "To Date: " . ($promotion->to_date ?? 'NULL') . "<br>";
    echo "Sale Quantity: {$promotion->sale_quantity}<br>";
    echo "Promotion Quantity: {$promotion->promotion_quantity}<br>";
    echo "Promotion Item ID: {$promotion->promotion_item_id}<br>";
    echo "Promotion Type ID: {$promotion->promotion_type_id}<br>";
    echo "</div>";
    
    // Step 2: Check promotion type
    $promotionType = $promotion->promotion_type_id ? PromotionType::find($promotion->promotion_type_id)->description : null;
    echo "<p><strong>Promotion Type:</strong> $promotionType</p>";
    
    if (!$promotionType) {
        echo "<p style='color: red;'>❌ No promotion type found</p>";
        return;
    }
    
    // Step 3: Check if it's the right type (BSGY)
    echo "<p><strong>Checking promotion type...</strong></p>";
    echo "<p>PromotionMatrix::HAMPER->value = " . PromotionMatrix::HAMPER->value . "</p>";
    echo "<p>PromotionMatrix::PD->value = " . PromotionMatrix::PD->value . "</p>";
    echo "<p>PromotionMatrix::BSGY->value = " . PromotionMatrix::BSGY->value . "</p>";
    echo "<p>Current promotion type: $promotionType</p>";
    
    if ($promotionType == PromotionMatrix::HAMPER->value) {
        echo "<p style='color: blue;'>📦 This is a HAMPER promotion</p>";
    } elseif ($promotionType == PromotionMatrix::PD->value) {
        echo "<p style='color: blue;'>💰 This is a PRICE DISCOUNT promotion</p>";
    } elseif ($promotionType == PromotionMatrix::BSGY->value) {
        echo "<p style='color: green;'>🎁 This is a BUY X GET Y FREE promotion</p>";
        
        // Step 4: Calculate promotion batches
        $orderQty = $quantity;
        $saleQuantity = (float)$promotion->sale_quantity;
        $promotionBatches = floor($orderQty / $saleQuantity);
        
        echo "<p><strong>Calculation:</strong></p>";
        echo "<p>Order Quantity: $orderQty</p>";
        echo "<p>Sale Quantity (Buy): $saleQuantity</p>";
        echo "<p>Promotion Batches: floor($orderQty / $saleQuantity) = $promotionBatches</p>";
        
        if ($promotionBatches > 0) {
            $promotionQty = $promotionBatches * $promotion->promotion_quantity;
            echo "<p style='color: green;'>✅ Customer qualifies for $promotionQty free items!</p>";
            
            // Check if promotion item exists
            if ($promotion->promotion_item_id) {
                $promotionItem = \App\Model\WaInventoryItem::find($promotion->promotion_item_id);
                if ($promotionItem) {
                    echo "<p><strong>Free Item:</strong> {$promotionItem->title} (ID: {$promotionItem->id})</p>";
                } else {
                    echo "<p style='color: red;'>❌ Promotion item not found in inventory!</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ No promotion_item_id set!</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Customer doesn't qualify (needs to buy at least $saleQuantity)</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Unknown promotion type: $promotionType</p>";
    }
});
