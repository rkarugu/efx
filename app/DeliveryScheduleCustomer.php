<?php

namespace App;

use App\Model\WaInternalRequisition;
use App\Model\WaRouteCustomer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryScheduleCustomer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $dates = [
        'delivered_at',
        'delivery_prompted_at',
    ];

    protected $appends = [
        'orders',
        'order_ids',
        'status',
        'order_delivery_statuses',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(WaRouteCustomer::class, 'customer_id', 'id');
    }

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class, 'delivery_schedule_id', 'id');
    }

    public function routeCustomer(): BelongsTo
    {
        return $this->belongsTo(WaRouteCustomer::class, 'customer_id', 'id');
    }

    public function orders(): Attribute
    {
        $orders = [];
        foreach (explode(',', $this->order_id) as $order_id) {
            $order = WaInternalRequisition::with(['getRelatedItem'])->select('requisition_no', 'id', 'status')->find($order_id);
            $orders[] = $order;
        }

        return Attribute::make(get: fn() => $orders);
    }

    public function orderIds(): Attribute
    {
        $ids = collect($this->orders)->pluck('requisition_no')->toArray();
        return Attribute::make(get: fn() => implode(",", $ids));
    }
    public function orderDeliveryStatuses(): Attribute
    {
        // $ids = collect($this->orders)->pluck('status')->toArray();
        // return Attribute::make(get: fn() => implode(",", $ids));
        return Attribute::make(
            get: function () {
                $ids = collect($this->orders)->pluck('status')->toArray();
                
                // Map "approved" status to "Not delivered"
                $statuses = array_map(function ($status) {
                    return $status === 'APPROVED' ? 'NOT DELIVERED' : $status;
                }, $ids);
                
                return implode(",", $statuses);
            }
        );
    }


    public function status(): Attribute
    {
        return Attribute::make(get: fn() => $this->delivery_code_status == 'approved' ? 'Delivered' : 'Not Delivered');
    }
}
