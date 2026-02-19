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
        Schema::table('delivery_schedule_customers', function (Blueprint $table) {
            // Money collection tracking
            $table->decimal('collection_amount', 10, 2)->nullable()->after('delivery_prompted_at');
            $table->enum('payment_method', ['cash', 'mpesa', 'card', 'bank_transfer', 'credit'])->nullable()->after('collection_amount');
            
            // Skip delivery tracking
            $table->boolean('is_skipped')->default(false)->after('payment_method');
            $table->text('skip_reason')->nullable()->after('is_skipped');
            $table->timestamp('skipped_at')->nullable()->after('skip_reason');
            
            // Additional delivery tracking
            $table->text('delivery_notes')->nullable()->after('skipped_at');
            $table->string('delivery_location_lat', 20)->nullable()->after('delivery_notes');
            $table->string('delivery_location_lng', 20)->nullable()->after('delivery_location_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_schedule_customers', function (Blueprint $table) {
            $table->dropColumn([
                'collection_amount',
                'payment_method', 
                'is_skipped',
                'skip_reason',
                'skipped_at',
                'delivery_notes',
                'delivery_location_lat',
                'delivery_location_lng'
            ]);
        });
    }
};
