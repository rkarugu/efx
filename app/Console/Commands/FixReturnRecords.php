<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixReturnRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'returns:fix {return_number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all records for a specific return number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $returnNumber = $this->argument('return_number');
        
        $this->info("Fixing return records for: {$returnNumber}");
        
        // Get count before deletion
        $returnItemsCount = DB::table('wa_inventory_location_transfer_item_returns')
            ->where('return_number', $returnNumber)
            ->count();
            
        $saleOrderReturnsCount = DB::table('sale_order_returns')
            ->whereIn('wa_internal_requisition_item_id', function($query) use ($returnNumber) {
                $query->select('wa_inventory_location_transfer_items.wa_internal_requisition_item_id')
                    ->from('wa_inventory_location_transfer_item_returns')
                    ->join('wa_inventory_location_transfer_items', 
                        'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', 
                        '=', 
                        'wa_inventory_location_transfer_items.id')
                    ->where('wa_inventory_location_transfer_item_returns.return_number', $returnNumber);
            })
            ->count();
        
        $this->warn("Found {$returnItemsCount} return item records");
        $this->warn("Found {$saleOrderReturnsCount} sale order return records");
        
        if ($this->confirm('Do you want to delete these records?')) {
            DB::beginTransaction();
            
            try {
                // Delete from sale_order_returns first
                $deletedSaleOrders = DB::table('sale_order_returns')
                    ->whereIn('wa_internal_requisition_item_id', function($query) use ($returnNumber) {
                        $query->select('wa_inventory_location_transfer_items.wa_internal_requisition_item_id')
                            ->from('wa_inventory_location_transfer_item_returns')
                            ->join('wa_inventory_location_transfer_items', 
                                'wa_inventory_location_transfer_item_returns.wa_inventory_location_transfer_item_id', 
                                '=', 
                                'wa_inventory_location_transfer_items.id')
                            ->where('wa_inventory_location_transfer_item_returns.return_number', $returnNumber);
                    })
                    ->delete();
                
                // Delete from wa_inventory_location_transfer_item_returns
                $deletedReturns = DB::table('wa_inventory_location_transfer_item_returns')
                    ->where('return_number', $returnNumber)
                    ->delete();
                
                DB::commit();
                
                $this->info("Successfully deleted {$deletedReturns} return item records");
                $this->info("Successfully deleted {$deletedSaleOrders} sale order return records");
                $this->info("Return records cleaned up successfully!");
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error deleting records: " . $e->getMessage());
                return 1;
            }
        } else {
            $this->info("Operation cancelled");
        }
        
        return 0;
    }
}
