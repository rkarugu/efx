<?php

namespace App\Models;

use App\Model\Restaurant;
use App\Model\Route;
use App\Model\User;
use App\Model\WaInventoryItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoutePricing extends Model
{
    use HasFactory;
    protected $table = 'route_pricing';

    public static function resolvePrice(int $itemId, int $routeId, ?int $restaurantId = null): ?float
    {
        $query = static::query()
            ->latest()
            ->where('wa_inventory_item_id', $itemId)
            ->where('status', 0)
            ->whereRaw('FIND_IN_SET( ? , route_id)', [$routeId]);

        if ($restaurantId !== null) {
            $query->where('restaurant_id', $restaurantId);
        }

        $pricing = $query->first();

        return $pricing?->price;
    }

    public function getRoutesAttribute()
    {
        $routeIds = explode(',', $this->route_id);
        return Route::whereIn('id', $routeIds)->get();
    }
    public function restaurant(){
        return $this->belongsTo(Restaurant::class, 'restaurant_id'); 
    }
    public function createdBy(){
        return $this->belongsTo(User::class, 'created_by'); 
    }
    public function getInventoryItemDetails(){
        return $this->belongsTo(WaInventoryItem::class, 'wa_inventory_item_id');
    }
}
