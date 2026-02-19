<?php

namespace App\Services\Inventory;

use App\Enums\PromotionMatrix;
use App\ItemPromotion;
use App\Models\PromotionType;
use App\WaDemand;
use Illuminate\Support\Facades\Auth;

class PromotionService
{
    public function create($inventoryItem, $supplier_id, $promotion_type_id, $promotion_group_id, $from_date, $to_date)
    {
        try {
            $promotion = new ItemPromotion();
            $promotion->inventory_item_id = $inventoryItem->id;
            $promotion->supplier_id = $supplier_id;
            $promotion->promotion_type_id = $promotion_type_id;
            $promotion->promotion_group_id = $promotion_group_id;
            $promotion->from_date = $from_date;
            $promotion->to_date = $to_date;
            $promotion->initiated_by = Auth::id();
            $promotion->status = 'active';
            $promotion->save();

            return $promotion;
        } catch (\Exception $e) {
            \Log::error('Error creating promotion: ' . $e->getMessage());
            return false;
        }
    }
}