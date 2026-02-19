<?php

namespace App\Model;
use Illuminate\Database\Eloquent\Model;
class WaStockBreakingItem extends Model
{
    protected $table = 'wa_stock_breaking_items';

    public function source_item()
    {
        return $this->belongsTo(WaInventoryItem::class,'source_item_id');
    }

    public function destination_item()
    {
        return $this->belongsTo(WaInventoryItem::class,'destination_item_id');
    }
}