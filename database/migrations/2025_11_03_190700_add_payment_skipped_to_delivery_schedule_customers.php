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
            $table->boolean('payment_skipped')->default(false)->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_schedule_customers', function (Blueprint $table) {
            $table->dropColumn('payment_skipped');
        });
    }
};
