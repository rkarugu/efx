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
        // Drop the incorrect foreign key constraint that references 'usersss'
        DB::statement('ALTER TABLE discount_bands DROP FOREIGN KEY discount_bands_initiated_by_foreign');
        
        // Add the correct foreign key constraint that references 'users'
        Schema::table('discount_bands', function (Blueprint $table) {
            $table->foreign('initiated_by')->references('id')->on('users')->onDelete('cascade');
        });
        
        // Also fix the approved_by foreign key if it exists and is incorrect
        try {
            DB::statement('ALTER TABLE discount_bands DROP FOREIGN KEY discount_bands_approved_by_foreign');
            Schema::table('discount_bands', function (Blueprint $table) {
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key might not exist or already be correct
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the correct foreign key constraints
        Schema::table('discount_bands', function (Blueprint $table) {
            $table->dropForeign(['initiated_by']);
            $table->dropForeign(['approved_by']);
        });
        
        // Note: We won't recreate the incorrect 'usersss' constraint in rollback
        // as that would be wrong. Manual intervention would be needed if rollback is required.
    }
};
