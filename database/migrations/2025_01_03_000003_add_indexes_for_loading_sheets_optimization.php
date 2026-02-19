<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for salesman_shift_store_dispatches
        $this->addIndexIfNotExists('salesman_shift_store_dispatches', 'shift_id', 'salesman_shift_store_dispatches_shift_id_index');
        $this->addIndexIfNotExists('salesman_shift_store_dispatches', ['dispatched', 'bin_location_id'], 'salesman_shift_store_dispatches_dispatched_bin_index');
        $this->addIndexIfNotExists('salesman_shift_store_dispatches', 'store_id', 'salesman_shift_store_dispatches_store_id_index');

        // Add indexes for salesman_shift_store_dispatch_items
        $this->addIndexIfNotExists('salesman_shift_store_dispatch_items', 'dispatch_id', 'salesman_shift_store_dispatch_items_dispatch_id_index');
        $this->addIndexIfNotExists('salesman_shift_store_dispatch_items', 'wa_inventory_item_id', 'salesman_shift_store_dispatch_items_item_id_index');

        // Add indexes for salesman_shifts
        $this->addIndexIfNotExists('salesman_shifts', ['salesman_id', 'status'], 'salesman_shifts_salesman_status_index');
        $this->addIndexIfNotExists('salesman_shifts', 'route_id', 'salesman_shifts_route_id_index');
        $this->addIndexIfNotExists('salesman_shifts', 'start_time', 'salesman_shifts_start_time_index');

        // Add indexes for wa_internal_requisitions
        $this->addIndexIfNotExists('wa_internal_requisitions', ['wa_shift_id', 'status'], 'wa_internal_requisitions_shift_status_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for salesman_shift_store_dispatches
        $this->dropIndexIfExists('salesman_shift_store_dispatches', 'salesman_shift_store_dispatches_shift_id_index');
        $this->dropIndexIfExists('salesman_shift_store_dispatches', 'salesman_shift_store_dispatches_dispatched_bin_index');
        $this->dropIndexIfExists('salesman_shift_store_dispatches', 'salesman_shift_store_dispatches_store_id_index');

        // Drop indexes for salesman_shift_store_dispatch_items
        $this->dropIndexIfExists('salesman_shift_store_dispatch_items', 'salesman_shift_store_dispatch_items_dispatch_id_index');
        $this->dropIndexIfExists('salesman_shift_store_dispatch_items', 'salesman_shift_store_dispatch_items_item_id_index');

        // Drop indexes for salesman_shifts
        $this->dropIndexIfExists('salesman_shifts', 'salesman_shifts_salesman_status_index');
        $this->dropIndexIfExists('salesman_shifts', 'salesman_shifts_route_id_index');
        $this->dropIndexIfExists('salesman_shifts', 'salesman_shifts_start_time_index');

        // Drop indexes for wa_internal_requisitions
        $this->dropIndexIfExists('wa_internal_requisitions', 'wa_internal_requisitions_shift_status_index');
    }

    /**
     * Add index if it doesn't exist
     */
    private function addIndexIfNotExists(string $table, $columns, string $indexName): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $columnList = implode(',', $columns);
        
        // Check if index exists using raw SQL to avoid ENUM issues
        $indexExists = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        
        if (empty($indexExists)) {
            \DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columnList})");
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $indexExists = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        
        if (!empty($indexExists)) {
            \DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};
