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
        Schema::create('delivery_schedule_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_schedule_id');
            $table->unsignedBigInteger('salesman_shift_id');
            $table->timestamps();
            
            // Add foreign key constraints
            $table->foreign('delivery_schedule_id')
                  ->references('id')
                  ->on('delivery_schedules')
                  ->onDelete('cascade');
                  
            $table->foreign('salesman_shift_id')
                  ->references('id')
                  ->on('salesman_shifts')
                  ->onDelete('cascade');
                  
            // Add unique constraint to prevent duplicate entries
            $table->unique(['delivery_schedule_id', 'salesman_shift_id'], 'ds_shifts_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_schedule_shifts');
    }
};
