<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Model\WaInventoryItem;
use App\Model\User;


class DiscountBand extends Model
{
    use HasFactory;
    public function inventoryItem()
    {
        return $this->belongsTo(WaInventoryItem::class);
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
