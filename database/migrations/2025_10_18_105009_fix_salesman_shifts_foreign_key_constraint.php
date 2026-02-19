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
        Schema::table('salesman_shifts', function (Blueprint $table) {
            // Drop the existing foreign key constraint that references 'usersss'
            $table->dropForeign('salesman_shifts_salesman_id_foreign');
            
            // Add the correct foreign key constraint that references 'users'
            $table->foreign('salesman_id')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salesman_shifts', function (Blueprint $table) {
            // Drop the corrected foreign key constraint
            $table->dropForeign(['salesman_id']);
            
            // Restore the original foreign key constraint that references 'usersss'
            $table->foreign('salesman_id')->references('id')->on('usersss')->onUpdate('cascade');
        });
    }
};
