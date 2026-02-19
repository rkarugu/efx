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
        // First, drop the incorrect foreign key constraint
        Schema::table('wa_user_suppliers', function (Blueprint $table) {
            $table->dropForeign('wa_user_suppliers_users_id_fk');
        });
        
        // Clean up orphaned records - delete records where user_id doesn't exist in users table
        DB::statement('DELETE FROM wa_user_suppliers WHERE user_id NOT IN (SELECT id FROM users)');
        
        // Also clean up records where wa_supplier_id doesn't exist in wa_suppliers table
        DB::statement('DELETE FROM wa_user_suppliers WHERE wa_supplier_id NOT IN (SELECT id FROM wa_suppliers)');
        
        // Add the correct foreign key constraints
        Schema::table('wa_user_suppliers', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
