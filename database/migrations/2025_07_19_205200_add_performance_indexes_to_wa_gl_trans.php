<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerformanceIndexesToWaGlTrans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('wa_gl_trans', function (Blueprint $table) {
            // Add composite indexes for profit and loss queries
            $table->index(['account', 'trans_date'], 'idx_account_trans_date');
            $table->index(['account', 'created_at'], 'idx_account_created_at');
            $table->index(['account', 'transaction_type'], 'idx_account_transaction_type');
            $table->index(['account', 'amount'], 'idx_account_amount');
            
            // Add composite index for date range queries
            $table->index(['account', 'created_at', 'transaction_type'], 'idx_account_created_transaction');
            
            // Add index for trans_date year queries
            $table->index(['trans_date'], 'idx_trans_date');
            $table->index(['created_at'], 'idx_created_at');
            
            // Add index for restaurant filtering
            $table->index(['account', 'restaurant_id'], 'idx_account_restaurant');
            $table->index(['account', 'tb_reporting_branch'], 'idx_account_tb_branch');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('wa_gl_trans', function (Blueprint $table) {
            $table->dropIndex('idx_account_trans_date');
            $table->dropIndex('idx_account_created_at');
            $table->dropIndex('idx_account_transaction_type');
            $table->dropIndex('idx_account_amount');
            $table->dropIndex('idx_account_created_transaction');
            $table->dropIndex('idx_trans_date');
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_account_restaurant');
            $table->dropIndex('idx_account_tb_branch');
        });
    }
}
