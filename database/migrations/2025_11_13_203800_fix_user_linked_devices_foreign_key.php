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
        // First, check if the table exists
        if (Schema::hasTable('user_linked_devices')) {
            // Get all foreign key constraints for this table
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'user_linked_devices' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            // Drop all existing foreign key constraints on user_id column
            foreach ($foreignKeys as $fk) {
                try {
                    DB::statement("ALTER TABLE user_linked_devices DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                } catch (Exception $e) {
                    // Continue if constraint doesn't exist
                }
            }
            
            // Clean up orphaned records - delete records where user_id doesn't exist in users table
            DB::statement('DELETE FROM user_linked_devices WHERE user_id NOT IN (SELECT id FROM users)');
            
            // Add the correct foreign key constraint pointing to the users table
            try {
                DB::statement('ALTER TABLE user_linked_devices ADD CONSTRAINT user_linked_devices_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
            } catch (Exception $e) {
                // If constraint already exists with correct reference, that's fine
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_linked_devices')) {
            try {
                DB::statement('ALTER TABLE user_linked_devices DROP FOREIGN KEY user_linked_devices_user_id_foreign');
            } catch (Exception $e) {
                // Continue if constraint doesn't exist
            }
        }
    }
};
