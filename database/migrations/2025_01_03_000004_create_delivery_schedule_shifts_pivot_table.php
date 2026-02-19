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
        // Create pivot table for many-to-many relationship (if it doesn't exist)
        if (!Schema::hasTable('delivery_schedule_shifts')) {
            Schema::create('delivery_schedule_shifts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_schedule_id')->constrained('delivery_schedules')->onDelete('cascade');
                $table->foreignId('salesman_shift_id')->constrained('salesman_shifts')->onDelete('cascade');
                $table->timestamps();
                
                // Prevent duplicate entries
                $table->unique(['delivery_schedule_id', 'salesman_shift_id'], 'delivery_schedule_shifts_unique');
                
                // Indexes for performance
                $table->index('delivery_schedule_id', 'delivery_schedule_shifts_schedule_idx');
                $table->index('salesman_shift_id', 'delivery_schedule_shifts_shift_idx');
            });
        }

        // Migrate existing data from delivery_schedules.shift_id to pivot table
        // Only migrate valid relationships where the shift actually exists
        // Check if data needs to be migrated
        $existingCount = DB::table('delivery_schedule_shifts')->count();
        
        if ($existingCount == 0) {
            DB::statement('
                INSERT INTO delivery_schedule_shifts (delivery_schedule_id, salesman_shift_id, created_at, updated_at)
                SELECT ds.id, ds.shift_id, ds.created_at, ds.updated_at
                FROM delivery_schedules ds
                INNER JOIN salesman_shifts ss ON ds.shift_id = ss.id
                WHERE ds.shift_id IS NOT NULL
            ');
        }

        // Note: We keep the shift_id column for backward compatibility
        // It will now represent the "primary" shift, but the pivot table holds all shifts
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_schedule_shifts');
    }
};
