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
        Schema::table('wa_inventory_location_transfers', function (Blueprint $table) {
            $table->decimal('printed_bf_balance', 15, 2)->nullable()->comment('B/F balance at time of first print');
            $table->decimal('printed_account_balance', 15, 2)->nullable()->comment('Account balance at time of first print');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_inventory_location_transfers', function (Blueprint $table) {
            $table->dropColumn(['printed_bf_balance', 'printed_account_balance']);
        });
    }
};
