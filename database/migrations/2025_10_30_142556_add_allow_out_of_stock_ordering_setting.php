<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Model\Setting;

class AddAllowOutOfStockOrderingSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add the ALLOW_OUT_OF_STOCK_ORDERING setting
        $setting = new Setting();
        $setting->name = 'ALLOW_OUT_OF_STOCK_ORDERING';
        $setting->slug = 'allow-out-of-stock-ordering';
        $setting->description = '0'; // 0 = Don't allow, 1 = Allow
        $setting->parameter_type = 'boolean';
        $setting->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('name', 'ALLOW_OUT_OF_STOCK_ORDERING')->delete();
    }
}