<?php

namespace App\Model;
use App\Models\PosStockBreakRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaStockBreaking extends Model
{
    protected $table = 'wa_stock_breaking';

    protected $casts = [
        'dispatched' => 'boolean',
        'dispatched_date' => 'datetime'
    ];

    protected $guarded = [];

    public function items(){
        return $this->HasMany(WaStockBreakingItem::class,'wa_stock_breaking_id');
    }
    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    public function posRequest(): BelongsTo
    {
        return $this->belongsTo(PosStockBreakRequest::class,'pos_stock_break_request_id');
    }
}