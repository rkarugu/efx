<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class WaInventoryLocationItemReturn extends Model
{
    protected $table = "wa_inventory_location_transfer_item_returns";
    
    protected $fillable = [
        'return_number',
        'wa_inventory_location_transfer_item_id',
        'wa_inventory_location_transfer_id',
        'return_reason',
        'return_quantity',
        'return_by',
        'return_date',
        'status',
        'return_status',
        'received_quantity',
        'physical_quantity',
        'note'
    ];

    public function item_parent() {
        return $this->belongsTo(WaInventoryLocationTransferItem::class, 'wa_inventory_location_transfer_item_id');
    }

     public function getTransferLocation() {
        return $this->belongsTo(WaInventoryLocationTransfer::class, 'wa_inventory_location_transfer_id');
    }

    public function returned_by()
    {
        return $this->belongsTo(User::class,'return_by');
    }

    public function getTotalReceived()
    {
        $selling_price = $this->item_parent ->selling_price;
        $qty = $this->received_quantity;
        return $selling_price * $qty;
    }
}