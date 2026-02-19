<?php

namespace App\Model;

use App\SaleOrderReturns;
use App\WaInventoryLocationTransferItemReturn;
use Illuminate\Database\Eloquent\Model;


class WaInternalRequisitionItem extends Model
{
    protected $guarded = [];

    public function getInventoryItemDetail()
    {
        return $this->belongsTo('App\Model\WaInventoryItem', 'wa_inventory_item_id');
    }

    public function orderReturns()
    {
        return $this->hasMany(SaleOrderReturns::class);
    }

    public function getInternalPurchaseId()
    {
        return $this->belongsTo('App\Model\WaInternalRequisition', 'wa_internal_requisition_id');
    }

    public function dispatch_details()
    {
        return $this->hasMany('App\Model\WaInternalRequisitionDispatch', 'wa_internal_requisition_item_id');
    }

    public function internalRequisition()
    {
        return $this->belongsTo(WaInternalRequisition::class, 'wa_internal_requisition_id');
    }

    public function returnedQuantity()
    {
        $transferItem = WaInventoryLocationTransferItem::where('wa_internal_requisition_item_id', $this->id)->first();
        
        if (!$transferItem) {
            return 0;
        }
        
        $returnedQty = WaInventoryLocationTransferItemReturn::where('wa_inventory_location_transfer_item_id', $transferItem->id)
            ->sum('return_quantity') ?? 0;
            
        return $returnedQty;
    }

    public function returnedTotal()
    {
       return $this->returnedQuantity() * $this->selling_price;
    }

    public function discountTotal()
    {
        return $this->discount;
    }

    public function getRealCost()
    {
        return (float)$this->total_cost_with_vat;
    }

    public function getCostWithTotalReturns()
    {
        return $this->getRealCost() - $this->returnedTotal();
    }
}


