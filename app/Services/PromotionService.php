<?php

namespace App\Services;

use App\ItemPromotion;
use App\Models\PromotionType;
use App\Enums\PromotionMatrix;
use Carbon\Carbon;

class PromotionService
{
    /**
     * Check for active promotions for an item
     * Handles both Price Discount and Buy X Get Y Free promotions
     */
    public static function checkPromotion($item_id, $quantity = 1)
    {
        $discount = 0;
        $freeItems = [];
        $today = Carbon::today();
        
        // Find active promotion for this item
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
            return [
                'discount' => 0,
                'free_items' => [],
                'promotion_type' => null,
                'promotion_details' => null
            ];
        }

        // Get promotion type
        $promotionType = $promotion->promotion_type_id 
            ? PromotionType::find($promotion->promotion_type_id)->description 
            : null;

        if (!$promotionType) {
            return [
                'discount' => 0,
                'free_items' => [],
                'promotion_type' => null,
                'promotion_details' => null
            ];
        }

        // Handle Price Discount promotions
        if ($promotionType == PromotionMatrix::PD->value) {
            $selling_price = $promotion->promotion_price;
            $current_price = $promotion->current_price;
            $discount = ($current_price - $selling_price) * $quantity;
            
            return [
                'discount' => $discount,
                'free_items' => [],
                'promotion_type' => 'price_discount',
                'promotion_details' => [
                    'original_price' => $current_price,
                    'promotion_price' => $selling_price,
                    'discount_per_item' => $current_price - $selling_price
                ]
            ];
        }

        // Handle Buy X Get Y Free promotions
        if ($promotionType == PromotionMatrix::BSGY->value) {
            $saleQuantity = $promotion->sale_quantity; // Quantity to buy
            $promotionQuantity = $promotion->promotion_quantity; // Quantity to get free
            $promotionItemId = $promotion->promotion_item_id; // Item to get free
            
            // Calculate how many free items customer gets
            $promotionBatches = floor($quantity / $saleQuantity);
            $totalFreeQty = $promotionBatches * $promotionQuantity;
            
            if ($totalFreeQty > 0 && $promotionItemId) {
                $freeItems[] = [
                    'item_id' => $promotionItemId,
                    'quantity' => $totalFreeQty,
                    'promotion_id' => $promotion->id
                ];
            }
            
            return [
                'discount' => 0, // No price discount for BSGY
                'free_items' => $freeItems,
                'promotion_type' => 'buy_x_get_y',
                'promotion_details' => [
                    'buy_quantity' => $saleQuantity,
                    'get_quantity' => $promotionQuantity,
                    'free_item_id' => $promotionItemId,
                    'batches_qualified' => $promotionBatches,
                    'total_free_qty' => $totalFreeQty
                ]
            ];
        }

        return [
            'discount' => 0,
            'free_items' => [],
            'promotion_type' => 'unknown',
            'promotion_details' => null
        ];
    }
    
    /**
     * Get promotion details for display
     */
    public static function getPromotionDescription($item_id)
    {
        $result = self::checkPromotion($item_id, 1);
        
        if ($result['promotion_type'] == 'price_discount') {
            $details = $result['promotion_details'];
            return "Special Price: KSh " . number_format($details['promotion_price'], 2) . 
                   " (Save KSh " . number_format($details['discount_per_item'], 2) . ")";
        }
        
        if ($result['promotion_type'] == 'buy_x_get_y') {
            $details = $result['promotion_details'];
            return "Buy {$details['buy_quantity']}, Get {$details['get_quantity']} Free!";
        }
        
        return null;
    }
}
