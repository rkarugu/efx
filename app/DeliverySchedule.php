<?php

namespace App;

use App\Model\Route;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Model\PackSize;


class DeliverySchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = [
        'delivery_number',
        'shift_duration',
        'duration',
        'tonnage',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(DeliveryScheduleCustomer::class, 'delivery_schedule_id', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryScheduleItem::class, 'delivery_schedule_id', 'id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(\App\Model\WaInventoryItem::class, 'inventory_item_id', 'id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }


    public function shift(): BelongsTo
    {
        return $this->belongsTo(SalesmanShift::class, 'shift_id', 'id');
    }

    /**
     * Get all shifts assigned to this delivery schedule (many-to-many)
     */
    public function shifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            SalesmanShift::class,
            'delivery_schedule_shifts',
            'delivery_schedule_id',
            'salesman_shift_id'
        )->withTimestamps();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id', 'id');
    }

    public function deliveryNumber(): Attribute
    {
        return Attribute::make(get: fn() => "DS-" . $this->getDeliveryNumber() . "$this->id");
    }

    public function duration(): Attribute
    {
        if (in_array($this->status, ['consolidating', 'consolidated', 'loaded'])) {
            return Attribute::make(
                get: fn() => 'N/A'
            );
        }

        $shiftClosingTime = Carbon::now();
        if ($this->status == 'finished') {
            $shiftClosingTime = Carbon::parse($this->updated_at);
        }

        $shiftDurationInMinutes = $shiftClosingTime->diffInMinutes($this->created_at);
        return Attribute::make(
            get: fn() => CarbonInterval::minutes($shiftDurationInMinutes)->cascade()->forHumans(),
        );
    }

    public function tonnage(): Attribute
    {
        $tonnage = round(($this->items->sum('tonnage') / 1000), 1);
        return Attribute::make(get: fn() => $tonnage);
    }


    private function getDeliveryNumber(): string
    {
        return match (true) {
            $this->id < 10 => '00000',
            ($this->id >= 10) && ($this->id < 100) => '0000',
            ($this->id >= 100) && ($this->id < 1000) => '000',
            ($this->id >= 1000) && ($this->id < 10000) => '00',
            ($this->id >= 10000) && ($this->id < 100000) => '0',
            default => '',
        };
    }

    static public function buildDeliveryNumber($id): string
    {
        $prefix = match (true) {
            $id < 10 => '00000',
            ($id >= 10) && ($id < 100) => '0000',
            ($id >= 100) && ($id < 1000) => '000',
            ($id >= 1000) && ($id < 10000) => '00',
            ($id >= 10000) && ($id < 100000) => '0',
            default => '',
        };

        return "DS-$prefix$id";
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', 'finished');
    }

    public function scopeStarted(Builder $query): void
    {
        $query->where('status', '=', 'in_progress');
    }

    public function scopeForDriver(Builder $query, int $driverId): void
    {
        $query->where('driver_id', $driverId);
    }

    public function scopeForVehicle(Builder $query, int $vehicleId): void
    {
        $query->where('vehicle_id', $vehicleId);
    }

    public function scopeForShiftId(Builder $query, int $shiftId): Builder
    {
        return $query->where(function (Builder $q) use ($shiftId) {
            $q->where('shift_id', $shiftId)
                ->orWhereHas('shifts', function (Builder $q2) use ($shiftId) {
                    $q2->where('salesman_shifts.id', $shiftId);
                });
        });
    }

    public function shiftDuration(): Attribute
    {
        $shiftClosingTime = Carbon::parse($this->updated_at);
        if (!$this->status != 'finished') {
            $shiftClosingTime = Carbon::now();
        }

        $shiftDurationInSecs = $shiftClosingTime->diffInMinutes($this->created_at);
        return Attribute::make(
            get: fn() => CarbonInterval::minutes($shiftDurationInSecs)->cascade()->forHumans(),
        );
    }
}
