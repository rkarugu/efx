<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the incorrect foreign key constraint if it exists
        Schema::table('item_promotions', function (Blueprint $table) {
            // Check if there's an existing foreign key and drop it
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'item_promotions' 
                AND COLUMN_NAME = 'initiated_by'
                AND CONSTRAINT_NAME LIKE '%foreign%'
            ");
            
            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE item_promotions DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            }
        });
        
        // Add the correct foreign key constraint referencing 'users' table
        Schema::table('item_promotions', function (Blueprint $table) {
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_promotions', function (Blueprint $table) {
            $table->dropForeign(['initiated_by']);
        });
    }
};
