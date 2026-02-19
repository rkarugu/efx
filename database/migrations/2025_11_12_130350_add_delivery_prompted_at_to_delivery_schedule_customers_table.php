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
            $table->timestamp('delivery_prompted_at')->nullable()->after('delivery_code_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_schedule_customers', function (Blueprint $table) {
            $table->dropColumn('delivery_prompted_at');
        });
    }
};
