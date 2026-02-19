<?php

namespace App\Model;

use App\Models\UserGeneralLedgerAccount;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Support\Facades\Request;
// use Illuminate\Support\Facades\Input;
use App\Models\WaPettyCashRequest;

class WaChartsOfAccount extends Model
{
    use Sluggable;

    public function sluggable(): array
    {
        return ['slug' => [
            'source' => 'account_name',
            'onUpdate' => false
        ]];
    }
    protected $appends = ['amount','this_month_amount','this_year_amount','previous_year_amount','two_year_back_amount'];

    public function getRelatedGroup()
    {
        return $this->belongsTo('App\Model\WaAccountGroup', 'wa_account_group_id');
    }

    public function getSubAccountSection()
    {
        return $this->belongsTo('App\Model\WaSubAccountSection', 'wa_account_sub_section_id');
    }
    public function branches()
    {
        return $this->belongsToMany(Restaurant::class, 'wa_chart_of_accounts_branches', 'wa_chart_of_account_id', 'restaurant_id');
    }
    public function parent_account()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
    public function child_accounts()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    public function getGlTrans()
    {
        return $this->belongsTo('App\Model\WaGlTran', 'account_code', 'account')->select('account', 'id');
    }
    public function getAllGlTrans()
    {
        return $this->hasMany('App\Model\WaGlTran', 'account', 'account_code');
    }
    public function usergeneralledgeraccounts()
    {
        return $this->hasMany(UserGeneralLedgerAccount::class, 'account_id');
    }

    public function getAmountAttribute()
    {
        $startdate = request()->get('start-date');
        $enddate   = request()->get('end-date');
        $restaurant   = request()->get('restaurant');
        // echo $enddate; die;
        $data = WaChartsOfAccount::where('wa_charts_of_accounts.id', $this->id)
            ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code');
        if ($restaurant != null) {
            $data->where('wa_gl_trans.restaurant_id', $restaurant);
        }
        if ($startdate != "") {
            $data->whereDate('wa_gl_trans.trans_date', '>=', $startdate);
            //            $data->whereDate('wa_gl_trans.trans_date', '<=', $enddate);
        }
        if ($enddate != "") {
            //          $data->whereDate('wa_gl_trans.trans_date', '>=', $startdate);
            $data->whereDate('wa_gl_trans.trans_date', '<=', $enddate);
        }

        $data = $data->sum('amount');

        return $data;
    }
    //new functions and appends
    public function getThisMonthAmountAttribute()
    {
        // echo $enddate; die;
        $data = WaChartsOfAccount::where('wa_charts_of_accounts.id', $this->id)
            ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code');
        $data->whereMonth('wa_gl_trans.trans_date', date('m'));
        $data = $data->sum('amount');
        return $data;
    }
    public function getThisYearAmountAttribute()
    {
        // echo $enddate; die; this_year_amount
        $data = WaChartsOfAccount::where('wa_charts_of_accounts.id', $this->id)
            ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code');
        $data->whereYear('wa_gl_trans.trans_date', date('Y'));
        $data = $data->sum('amount');
        return $data;
    }
    public function getPreviousYearAmountAttribute()
    {
        $year = date('Y') - 1;
        $data = WaChartsOfAccount::where('wa_charts_of_accounts.id', $this->id)
            ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code');
        $data->whereYear('wa_gl_trans.trans_date', $year);
        $data = $data->sum('amount');
        return $data;
    }
    public function getTwoYearBackAmountAttribute()
    {
        $year = date('Y') - 2;
        $data = WaChartsOfAccount::where('wa_charts_of_accounts.id', $this->id)
            ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code');
        $data->whereYear('wa_gl_trans.trans_date', $year);
        $data = $data->sum('amount');
        return $data;
    }

    public function getMonthlyBudget()
    {
        $id = Request::get('id');
        // return  $id;
        return $this->hasOne(WaBudgetMonthly::class, 'chart_of_account_id', 'id')->where('wa_budget_id', $id);
    }

    public function getQuarterlyBudget()
    {
        $id = Request::get('id');
        // return  $id;
        return $this->hasOne(WaBudgetQuarterly::class, 'chart_of_account_id', 'id')->where('wa_budget_id', $id);
    }
    public function getYearlyBudget()
    {
        $id = Request::get('id');
        // return  $id;
        return $this->hasOne(WaBudgetYearly::class, 'chart_of_account_id', 'id')->where('wa_budget_id', $id);
    }

    public function paymentMethod()
    {
        return $this->hasOne(PaymentMethod::class, 'gl_account_id');
    }

    public function pettyCashRequest()
    {
        return $this->hasOne(WaPettyCashRequest::class,'wa_charts_of_account_id');
    }
}
