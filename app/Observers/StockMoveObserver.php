<?php

namespace App\Observers;

use App\Model\WaStockMove;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

use App\Jobs\ProcessStockUncompletedEntries;

class StockMoveObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the WaStockMove "created" event.
     */
    public function created(WaStockMove $waStockMove): void
    {
        // Skip processing for POS cash sales (CS- prefix) as they handle their own stock movements
        if ($waStockMove->document_no && str_starts_with($waStockMove->document_no, 'CS-')) {
            \Log::info('Skipping ProcessStockUncompletedEntries for POS cash sale', [
                'document_no' => $waStockMove->document_no,
                'stock_move_id' => $waStockMove->id
            ]);
            return;
        }
        
        if($waStockMove->qauntity >= 1){
            ProcessStockUncompletedEntries::dispatch($waStockMove->wa_inventory_item_id,$waStockMove->wa_location_and_store_id);
        }
    }

    /**
     * Handle the WaStockMove "updated" event.
     */
    public function updated(WaStockMove $waStockMove): void
    {
        //
    }

    /**
     * Handle the WaStockMove "deleted" event.
     */
    public function deleted(WaStockMove $waStockMove): void
    {
        //
    }

    /**
     * Handle the WaStockMove "restored" event.
     */
    public function restored(WaStockMove $waStockMove): void
    {
        //
    }

    /**
     * Handle the WaStockMove "force deleted" event.
     */
    public function forceDeleted(WaStockMove $waStockMove): void
    {
        //
    }
}
