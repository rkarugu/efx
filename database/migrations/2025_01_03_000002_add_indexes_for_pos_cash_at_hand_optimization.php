<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesForPosCashAtHandOptimization extends Migration
{
    /**
     * Run the migrations to optimize cashAtHand() queries
     *
     * @return void
     */
    public function up()
    {
        // Optimize cash_drop_transactions query
        Schema::table('cash_drop_transactions', function (Blueprint $table) {
            $table->index(['cashier_id', 'created_at'], 'idx_cash_drops_cashier_date');
        });

        // Optimize wa_pos_cash_sales_items_return query
        Schema::table('wa_pos_cash_sales_items_return', function (Blueprint $table) {
            $table->index(['accepted_at', 'accepted', 'branch_id'], 'idx_returns_accepted_date_branch');
        });

        // Optimize wa_pos_cash_sales query
        Schema::table('wa_pos_cash_sales', function (Blueprint $table) {
            $table->index(['attending_cashier', 'created_at', 'status'], 'idx_sales_cashier_date_status');
        });

        // Optimize wa_pos_cash_sales_payments query
        Schema::table('wa_pos_cash_sales_payments', function (Blueprint $table) {
            $table->index(['wa_pos_cash_sales_id', 'payment_method_id'], 'idx_payments_sale_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cash_drop_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_cash_drops_cashier_date');
        });

        Schema::table('wa_pos_cash_sales_items_return', function (Blueprint $table) {
            $table->dropIndex('idx_returns_accepted_date_branch');
        });

        Schema::table('wa_pos_cash_sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_cashier_date_status');
        });

        Schema::table('wa_pos_cash_sales_payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_sale_method');
        });
    }
}
