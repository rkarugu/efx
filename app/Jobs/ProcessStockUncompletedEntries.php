<?php

namespace App\Jobs;

use Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Model\WaStockMove;
use App\Models\StockDebtor;
use App\Model\WaInventoryItem;
use App\Models\StockDebtorTran;
use App\Models\StockDebtorTranItem;
use App\Model\WaNumerSeriesCode;
use Illuminate\Database\Eloquent\Builder;

class ProcessStockUncompletedEntries
{ 
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventoryItemId;
    protected $locationStoreId;
    
    /**
     * Create a new job instance.
     */
    public function __construct($inventoryItemId,$locationStoreId)
    {
        $this->inventoryItemId = $inventoryItemId;
        $this->locationStoreId = $locationStoreId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $debtor = StockDebtorTranItem::with('debtorTran')->where('document_no','like','SAS%')
            ->where('is_processed',0)->where('inventory_item_id',$this->inventoryItemId)->first(); 
            if ($debtor) {
                $inventoryItem = WaInventoryItem::find($debtor->inventory_item_id);
                $stockDebtor = StockDebtor::with('employee')->where('id',$debtor->stock_debtors_id)->first();

                $stockQuery = DB::table('wa_stock_moves')
                            ->where('wa_inventory_item_id',$inventoryItem->id)
                            ->where('wa_location_and_store_id', $this->locationStoreId);
                $stockMoveInfo = $stockQuery->first();
                $current_qoh = $stockQuery->sum('qauntity');

                $quantity = abs($debtor->quantity_pending);
                $documentExpoded = explode('-',$debtor->document_no);
                    
                $series_module = WaNumerSeriesCode::where('code', $documentExpoded[0])->first();
                $sellingPrice = $debtor->price;
                
                $quantityPending = $quantity;
                if ($current_qoh >= $quantity) {
                    $quantityPending = 0;
                    WaStockMove::create([
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => (int)$documentExpoded[1],
                        'qauntity' => -($quantity),
                        'new_qoh' => $current_qoh - $quantity,
                        'standard_cost' => $inventoryItem->standard_cost,
                        'selling_price' => $sellingPrice,
                        'price' => $sellingPrice,
                        'stock_id_code' => $inventoryItem->stock_id_code,
                        'wa_inventory_item_id' => $inventoryItem->id,
                        'wa_location_and_store_id' => $stockMoveInfo->wa_location_and_store_id,
                        'restaurant_id' => $stockMoveInfo->restaurant_id,
                        'document_no' => $debtor->document_no,
                        'refrence' => $stockDebtor->employee->name .'/'.$debtor->document_no .'/'.$debtor->created_at->toDateString(),
                        'total_cost' => $debtor->total,
                        'user_id'=> $debtor->debtorTran->created_by,
                    ]);

                    StockDebtorTranItem::where('id', $debtor->id)
                        ->update(['is_processed'=>1,'quantity_pending' => $quantityPending]);
                        
                } elseif($current_qoh < $quantity && $current_qoh !=0){
                    $quantityPending = $quantity - $current_qoh;
                    $quantity = $current_qoh;
                    
                    WaStockMove::create([
                        'user_id'=> $debtor->debtorTran->created_by,
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => (int)$documentExpoded[1],
                        'qauntity' => -($quantity),
                        'new_qoh' => 0,
                        'standard_cost' => $inventoryItem->standard_cost,
                        'selling_price' => $sellingPrice,
                        'price' => $sellingPrice,
                        'stock_id_code' => $inventoryItem->stock_id_code,
                        'wa_inventory_item_id' => $inventoryItem->id,
                        'wa_location_and_store_id' => $stockMoveInfo->wa_location_and_store_id,
                        'restaurant_id' => $stockMoveInfo->restaurant_id,
                        'document_no' => $debtor->document_no,
                        'refrence' => $stockDebtor->employee->name .'/'.$debtor->document_no.'/'.$debtor->created_at->toDateString(),
                        'total_cost' => $debtor->total,
                    ]);

                    $isProcessed=0;
                    if($quantityPending==0){
                        $isProcessed=1;
                    }
                    StockDebtorTranItem::where('id', $debtor->id)
                        ->update(['is_processed'=>$isProcessed,'quantity_pending' => $quantityPending]);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
}
