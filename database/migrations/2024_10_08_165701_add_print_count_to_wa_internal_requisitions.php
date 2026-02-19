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
        Schema::table('wa_internal_requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_internal_requisitions', 'print_count')) {
                $table->integer('print_count')->default(0)->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_internal_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('wa_internal_requisitions', 'print_count')) {
                $table->dropColumn('print_count');
            }
        });
    }
};
