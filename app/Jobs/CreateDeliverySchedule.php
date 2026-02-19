<?php

namespace App\Jobs;

use App\DeliverySchedule;
use App\Model\WaInternalRequisition;
use App\SalesmanShift;
use App\SalesmanShiftCustomer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateDeliverySchedule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     * Can accept a single shift or array of shifts
     */
    public function __construct(public SalesmanShift|array $shift)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Normalize to array of shifts
            $shifts = is_array($this->shift) ? $this->shift : [$this->shift];
            
            // Validate all shifts
            $shiftIds = [];
            $routeId = null;
            
            foreach ($shifts as $shift) {
                if (!$shift instanceof SalesmanShift) {
                    Log::warning("Invalid shift provided to CreateDeliverySchedule");
                    continue;
                }
                
                $shiftIds[] = $shift->id;
                
                // Use the first shift's route as the primary route
                if (!$routeId) {
                    $routeId = $shift->route_id;
                }
            }
            
            if (empty($shiftIds)) {
                Log::warning("No valid shifts provided to CreateDeliverySchedule");
                return;
            }
            
            Log::info("Creating delivery schedule for shifts: " . implode(', ', $shiftIds));
            
            // Get customers who have orders in these shifts
            $customersWithOrders = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)
                ->whereNotNull('wa_route_customer_id')
                ->distinct()
                ->pluck('wa_route_customer_id');

            // Create the delivery schedule with the first shift as primary
            $schedule = DeliverySchedule::create([
                'shift_id' => $shiftIds[0], // Primary shift for backward compatibility
                'route_id' => $routeId,
                'expected_delivery_date' => Carbon::tomorrow(),
                'status' => 'consolidating'
            ]);
            
            // Attach all shifts to the delivery schedule via pivot table
            $schedule->shifts()->attach($shiftIds);
            
            Log::info("Created delivery schedule {$schedule->id} with " . count($shiftIds) . " shifts");

            // Create customer entries with orders from all shifts
            foreach ($customersWithOrders as $customerId) {
                $customerOrderIds = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)
                    ->where('wa_route_customer_id', $customerId)
                    ->pluck('id')
                    ->toArray();

                $schedule->customers()->create([
                    'customer_id' => $customerId,
                    'delivery_code' => random_int(100000, 999999),
                    'order_id' => implode(',', $customerOrderIds)
                ]);
            }

            // Aggregate items from all shifts
            $shiftItems = DB::table('wa_internal_requisition_items')
                ->join('wa_internal_requisitions', function (JoinClause $join) use ($shiftIds) {
                    $join->on('wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
                        ->whereIn('wa_internal_requisitions.wa_shift_id', $shiftIds);
                })
                ->leftJoin('wa_inventory_items', 'wa_internal_requisition_items.wa_inventory_item_id', '=', 'wa_inventory_items.id')
                ->groupBy('wa_internal_requisition_items.wa_inventory_item_id')
                ->select(
                    'wa_inventory_items.id as item_id',
                    'wa_inventory_items.title as item_title',
                    DB::raw('SUM(`quantity`) as total_quantity')
                )
                ->get();

            if ($shiftItems->isEmpty()) {
                Log::warning("Delivery schedule {$schedule->id} has no items");
            }

            // Create delivery schedule items
            foreach ($shiftItems as $shiftItem) {
                $schedule->items()->create([
                    'wa_inventory_item_id' => $shiftItem->item_id,
                    'total_quantity' => $shiftItem->total_quantity
                ]);
            }
            
            Log::info("Delivery schedule {$schedule->id} created successfully with " . $shiftItems->count() . " items and " . $customersWithOrders->count() . " customers");
            
        } catch (\Throwable $e) {
            Log::error("Failed to create delivery schedule: " . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
}