<?php

namespace App\Models;

use App\Model\User;
use App\Model\WaSupplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TradeDiscount extends Model
{
    use HasFactory;

    const PENDING = 0;

    const APPROVED = 1;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(TradeDiscountItem::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function supplier()
    {
        return $this->belongsTo(WaSupplier::class, 'supplier_id');
    }

    public function demand()
    {
        return $this->hasOne(TradeDiscountDemandItem::class, 'trade_discount_id');
    }

    public function tradeAgreementDiscount()
    {
        return $this->hasOne(TradeAgreementDiscount::class, 'trade_agreement_discount_id');
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', self::PENDING);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', self::APPROVED);
    }

    public function isPending()
    {
        return $this->status == self::PENDING;
    }

    public function isApproved()
    {
        return $this->status == self::APPROVED;
    }
}
