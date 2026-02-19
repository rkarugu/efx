<?php

namespace App;

use App\Models\PromotionGroup;
use App\Models\PromotionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\WaInventoryItem;
use App\Model\User;


class ItemPromotion extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'inventory_item_id',
        'promotion_type_id',
        'promotion_group_id',
        'wa_demand_id',
        'supplier_id',
        'apply_to_split',
        'initiated_by',
        'from_date',
        'to_date',
        'sale_quantity',
        'promotion_item_id',
        'promotion_quantity',
        'current_price',
        'promotion_price',
        'discount_percentage',
        'discount_amount',
        'status'
    ];
    
    public function inventoryItem()
    {
        return $this->belongsTo(WaInventoryItem::class, 'inventory_item_id');
    }
    public function promotionItem()
    {
        return $this->belongsTo(WaInventoryItem::class, 'promotion_item_id');
    }
    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function promotionType()
    {
        return $this->belongsTo(PromotionType::class,'promotion_type_id')->withDefault([
            'name'=>'N/A'
        ]);
    }

    public function promotionGroup()
    {
        return $this->belongsTo(PromotionGroup::class,'promotion_group_id')->withDefault([
            'name'=>'N/A'
        ]);
    }
}
