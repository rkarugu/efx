<?php

namespace App;

use App\Model\Route;
use App\Model\WaInternalRequisition;
use App\Model\WaInventoryLocationTransfer;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Model\PackSize;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesmanShift extends Model
{
    protected $guarded = [];

    protected $casts = [
        'closed_time' => 'datetime'
    ];

    protected $appends = [
        'route',
        'shift_id',
        'shift_duration',
        'shift_total',
        'shift_tonnage',
        'shift_ctns',
        'shift_dzns',
    ];
    protected  $with = [
        'orders',
        'orders.getRelatedItem',
         'orders.getRelatedItem.getInventoryItemDetail'
    ];

    public function getRoute(): Route|null
    {
        return Route::find($this->route_id);
    }

    public function reopenRequests(): HasMany
    {
        return $this->hasMany(SalesmanShiftReopenRequest::class, 'shift_id', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(WaInternalRequisition::class, 'wa_shift_id', 'id');
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesman_id', 'id');
    }

    public function relatedRoute(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function salesman_route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function route(): Attribute
    {
//        $route = Route::select('route_name')->find($this->route_id);
        return Attribute::make(
            get: fn() => $this->salesman_route?->route_name
        );
    }

    public function shiftTotal(): Attribute
    {
        $total = 0;
        foreach ($this->orders as $order) {
            // $total += $order->getOrderTotalForReceipt();
            // $total += $order->getOrderTotalWithDiscount();
            $total += $order->getOrderTotal();

        }
        return Attribute::make(
            get: fn() => $total
        );
    }

    public function getPayableTotal(): float
    {
        $total = 0;
        foreach ($this->orders as $order) {
            $total += $order->getFinalTotal();
        }
        return $total;
    }

    public function shiftTonnage(): Attribute
    {
        $tonnage = 0;
        foreach ($this->orders as $order) {
            foreach ($order->getRelatedItem as $orderItem) {
                $tonnage += (($orderItem->getInventoryItemDetail?->net_weight ?? 0) * $orderItem->quantity)/1000;
            }
        }

        return Attribute::make(
            get: fn() => $tonnage
        );
    }

    public function shiftCtns(): Attribute
    {
        // $ctns = 0;
        // foreach ($this->orders as $order) {
        //     foreach ($order->getRelatedItem as $orderItem) {
        //         $ctnPackSize = PackSize::where('title', 'CTN')->first();
        //         if ($orderItem->getInventoryItemDetail?->pack_size_id == $ctnPackSize?->id) {
        //             //count distinct

        //             $ctns += $orderItem->quantity;
        //         }
        //     }
        // }
        $ctnsCount  = WaInternalRequisition::select('wa_inventory_items.id')
        ->leftjoin('wa_internal_requisition_items', 'wa_internal_requisitions.id', '=', 'wa_internal_requisition_items.wa_internal_requisition_id')
        ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=' ,'wa_inventory_items.id')
        ->whereIn('wa_inventory_items.pack_size_id', [3])
        ->where('wa_internal_requisitions.wa_shift_id', $this->id)
        ->distinct('wa_inventory_items.id')
        ->count();

        return Attribute::make(
            get: fn() => $ctnsCount
        );
    }

    public function shiftDzns(): Attribute
    {
        // $dzns = 0;
        // foreach ($this->orders as $order) {
        //     foreach ($order->getRelatedItem as $orderItem) {
        //         $ctnPackSize = PackSize::where('title', 'DZN')->first();
        //             if (in_array($orderItem->getInventoryItemDetail?->pack_size_id, [6,9,17,4,10,1]) ) {

        //             $dzns += $orderItem->quantity;
        //         }
        //     }
        // }
        $dznsCount  = WaInternalRequisition::select('wa_inventory_items.id')
        ->leftjoin('wa_internal_requisition_items', 'wa_internal_requisitions.id', '=','wa_internal_requisition_items.wa_internal_requisition_id')
        ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id','=', 'wa_inventory_items.id')
        ->whereIn('wa_inventory_items.pack_size_id', [6,9,17,4,10,1])
        ->where('wa_internal_requisitions.wa_shift_id', $this->id)
        ->distinct('wa_inventory_items.id')
        ->count();

        return Attribute::make(
            get: fn() => $dznsCount  
        );
    }

    public function shiftId(): Attribute
    {
        $shiftDate = Carbon::parse($this->created_at)->format('Y/m/d');
        return Attribute::make(
            get: fn() => "{$this->route}-$shiftDate"
        );
    }

    public function shiftDuration(): Attribute
    {
        $shiftClosingTime = Carbon::now();
        if ($this->status == 'close') {
            $shiftClosingTime = Carbon::parse($this->closed_time);
        }

        $shiftDurationInMins = $shiftClosingTime->diffInMinutes(Carbon::parse($this->start_time));
        return Attribute::make(
            get: fn() => CarbonInterval::minutes($shiftDurationInMins)->cascade()->forHumans(),
        );
    }

    public function shiftCustomers(): HasMany
    {
        return $this->hasMany(SalesmanShiftCustomer::class, 'salesman_shift_id', 'id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(SalesmanShiftStoreDispatch::class, 'shift_id', 'id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(WaInventoryLocationTransfer::class,'shift_id');
    }

    public function firstOrder(): HasOne
    {
        return $this->hasOne(WaInternalRequisition::class, 'wa_shift_id', 'id')->oldest();
    }

    /**
     * Get all delivery schedules this shift is assigned to (many-to-many)
     */
    public function deliverySchedules(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\DeliverySchedule::class,
            'delivery_schedule_shifts',
            'salesman_shift_id',
            'delivery_schedule_id'
        )->withTimestamps();
    }
}
