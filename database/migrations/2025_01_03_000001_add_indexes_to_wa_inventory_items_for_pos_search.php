<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToWaInventoryItemsForPosSearch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wa_inventory_items', function (Blueprint $table) {
            // Add index on title for faster LIKE searches
            $table->index('title', 'idx_wa_inventory_items_title');
            
            // Add index on stock_id_code for faster LIKE searches
            $table->index('stock_id_code', 'idx_wa_inventory_items_stock_id_code');
            
            // Add index on status for faster filtering
            $table->index('status', 'idx_wa_inventory_items_status');
            
            // Add composite index for common query pattern
            $table->index(['status', 'pack_size_id'], 'idx_wa_inventory_items_status_pack_size');
        });
        
        Schema::table('wa_stock_moves', function (Blueprint $table) {
            // Add composite index for stock quantity calculation
            $table->index(['wa_inventory_item_id', 'wa_location_and_store_id'], 'idx_wa_stock_moves_item_location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wa_inventory_items', function (Blueprint $table) {
            $table->dropIndex('idx_wa_inventory_items_title');
            $table->dropIndex('idx_wa_inventory_items_stock_id_code');
            $table->dropIndex('idx_wa_inventory_items_status');
            $table->dropIndex('idx_wa_inventory_items_status_pack_size');
        });
        
        Schema::table('wa_stock_moves', function (Blueprint $table) {
            $table->dropIndex('idx_wa_stock_moves_item_location');
        });
    }
}
