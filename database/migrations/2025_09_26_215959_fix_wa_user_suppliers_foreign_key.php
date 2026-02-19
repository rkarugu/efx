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
        Schema::table('wa_user_suppliers', function (Blueprint $table) {
            // Drop the incorrect foreign key constraint
            $table->dropForeign('wa_user_suppliers_users_id_fk');
            
            // Add the correct foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_user_suppliers', function (Blueprint $table) {
            // Drop the correct foreign key constraint
            $table->dropForeign(['user_id']);
            
            // Note: We won't recreate the incorrect constraint in the down method
            // as it would cause the same issue
        });
    }
};
