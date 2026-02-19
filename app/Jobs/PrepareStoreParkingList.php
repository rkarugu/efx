<?php

namespace App\Jobs;

use App\Model\WaLocationAndStore;
use App\SalesmanShift;
use App\SalesmanShiftStoreDispatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrepareStoreParkingList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public SalesmanShift $shift)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validate shift has a salesman with location
            if (!$this->shift->salesman || !$this->shift->salesman->wa_location_and_store_id) {
                Log::warning("Loading Sheet skipped: Shift {$this->shift->id} has no salesman or location");
                return;
            }

            $storeId = $this->shift->salesman->wa_location_and_store_id;
            
            // Delete existing loading sheets for this shift to prevent duplicates
            // This makes the job idempotent - can be run multiple times safely
            $existingDispatches = SalesmanShiftStoreDispatch::where('shift_id', $this->shift->id)->get();
            foreach ($existingDispatches as $existingDispatch) {
                // Delete items first
                $existingDispatch->items()->delete();
                // Then delete the dispatch
                $existingDispatch->delete();
            }
            
            Log::info("Generating loading sheet for Shift {$this->shift->id}");
            
            // Get all items from orders in this shift, grouped by item
            $shiftItems = DB::table('wa_internal_requisition_items')
                ->join('wa_internal_requisitions', function (JoinClause $join) {
                    $join->on('wa_internal_requisition_items.wa_internal_requisition_id', '=', 'wa_internal_requisitions.id')
                        ->where('wa_internal_requisitions.wa_shift_id', $this->shift->id);
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
                Log::info("Loading Sheet skipped: Shift {$this->shift->id} has no items");
                return;
            }

            // Group items by bin location
            $itemsByBin = [];
            
            foreach ($shiftItems as $shiftItem) {
                // Get the bin location for this item at this store
                $inventoryLocationUom = DB::table('wa_inventory_location_uom')
                    ->where('inventory_id', $shiftItem->item_id)
                    ->where('location_id', $storeId)
                    ->first();
                
                // Use bin location if found, otherwise use default bin (15)
                $binLocationId = $inventoryLocationUom?->uom_id ?? 15;
                
                // Group items by bin location
                if (!isset($itemsByBin[$binLocationId])) {
                    $itemsByBin[$binLocationId] = [];
                }
                
                $itemsByBin[$binLocationId][] = [
                    'item_id' => $shiftItem->item_id,
                    'item_title' => $shiftItem->item_title,
                    'total_quantity' => $shiftItem->total_quantity
                ];
            }

            // Create dispatch records for each bin location
            foreach ($itemsByBin as $binLocationId => $items) {
                // Create the dispatch header
                $dispatch = SalesmanShiftStoreDispatch::create([
                    'shift_id' => $this->shift->id,
                    'store_id' => $storeId,
                    'bin_location_id' => $binLocationId,
                    'dispatched' => false,
                ]);

                // Create dispatch items in bulk
                $dispatchItems = [];
                foreach ($items as $item) {
                    $dispatchItems[] = [
                        'dispatch_id' => $dispatch->id,
                        'wa_inventory_item_id' => $item['item_id'],
                        'total_quantity' => $item['total_quantity'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                // Bulk insert for better performance
                DB::table('salesman_shift_store_dispatch_items')->insert($dispatchItems);
                
                Log::info("Created loading sheet {$dispatch->id} for Shift {$this->shift->id}, Bin {$binLocationId}, Items: " . count($items));
            }
            
            Log::info("Loading Sheet generation completed for Shift {$this->shift->id}");
            
        } catch (\Throwable $e) {
            Log::error("Loading Sheet failed for Shift {$this->shift->id}: " . $e->getMessage(), [
                'shift_id' => $this->shift->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
}
