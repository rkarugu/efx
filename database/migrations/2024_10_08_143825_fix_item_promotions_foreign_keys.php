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
        Schema::table('item_promotions', function (Blueprint $table) {
            // First, modify column types to match referenced tables if needed
            
            // Ensure promotion_type_id is the correct type (string to match promotion_types.id)
            if (Schema::hasColumn('item_promotions', 'promotion_type_id')) {
                $table->string('promotion_type_id')->nullable()->change();
            }
            
            // Ensure promotion_group_id is unsigned integer
            if (Schema::hasColumn('item_promotions', 'promotion_group_id')) {
                $table->unsignedBigInteger('promotion_group_id')->nullable()->change();
            }
            
            // Ensure wa_demand_id is unsigned integer
            if (Schema::hasColumn('item_promotions', 'wa_demand_id')) {
                $table->unsignedBigInteger('wa_demand_id')->nullable()->change();
            }
            
            // Ensure supplier_id is string to match wa_suppliers.id
            if (Schema::hasColumn('item_promotions', 'supplier_id')) {
                $table->string('supplier_id')->nullable()->change();
            }
            
            // Add status column if it doesn't exist
            if (!Schema::hasColumn('item_promotions', 'status')) {
                $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            }
        });
        
        // Add foreign key constraints in a separate schema call after column modifications
        Schema::table('item_promotions', function (Blueprint $table) {
            // Add foreign key constraints that might be missing
            
            // Check if promotion_group_id foreign key exists, if not add it
            if (!$this->foreignKeyExists('item_promotions', 'item_promotions_promotion_group_id_foreign')) {
                $table->foreign('promotion_group_id')->references('id')->on('promotion_groups')->onDelete('set null');
            }
            
            // Check if wa_demand_id foreign key exists, if not add it
            if (!$this->foreignKeyExists('item_promotions', 'item_promotions_wa_demand_id_foreign')) {
                $table->foreign('wa_demand_id')->references('id')->on('wa_demands')->onDelete('cascade');
            }
            
            // Note: Skipping promotion_type_id and supplier_id foreign keys as they may have string IDs
            // These will be handled by validation in the controller instead
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_promotions', function (Blueprint $table) {
            // Drop foreign keys if they exist
            if ($this->foreignKeyExists('item_promotions', 'item_promotions_promotion_group_id_foreign')) {
                $table->dropForeign(['promotion_group_id']);
            }
            
            if ($this->foreignKeyExists('item_promotions', 'item_promotions_wa_demand_id_foreign')) {
                $table->dropForeign(['wa_demand_id']);
            }
            
            // Drop status column if it exists
            if (Schema::hasColumn('item_promotions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
    
    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists($table, $foreignKey)
    {
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys($table);
            
        foreach ($foreignKeys as $key) {
            if ($key->getName() === $foreignKey) {
                return true;
            }
        }
        
        return false;
    }
};
