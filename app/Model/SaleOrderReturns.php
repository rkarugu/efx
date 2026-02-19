<?php

namespace App\Model;
use App\Model\WaInternalRequisitionItem;
use Illuminate\Database\Eloquent\Model;

class SaleOrderReturns extends Model
{
    protected $table = 'sale_order_returns';
    
    protected $fillable = [
        'wa_internal_requisition_item_id',
        'quantity',
        'item_return_reason_id',
        'comment',
        'image'
    ];

    public function wa_internal_requisition_item(){
        return $this->belongsTo(WaInternalRequisitionItem::class, 'wa_internal_requisition_item_id');
    }


    public function reason(){
        return $this->belongsTo(\ItemReturnReasons::class, 'wa_internal_requisition_item_id');
    }
}
