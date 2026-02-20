<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
// use Symfony\Component\Console\Input\Input as InputInput;

class WaAccountGroup extends Model
{

    use Sluggable;

    public function sluggable(): array
    {
        return ['slug' => [
            'source' => 'group_name',
            'onUpdate' => true
        ]];
    }

    protected $appends = ['amount'];

    public function getAccountSection()
    {
        return $this->belongsTo('App\Model\WaAccountSection', 'wa_account_section_id');
    }

    public function getParentAccountGroup()
    {
        return $this->belongsTo('App\Model\WaAccountGroup', 'parent_id');
    }

    public function accountSubSections()
    {
        return $this->hasMany('App\Model\WaSubAccountSection', 'wa_account_group_id');
    }


    public function getChartAccount()
    {
        $this_year = date('Y');
        $prev_year = date('Y') - 1;
        $startdate = request()->get('start-date');
        $enddate = request()->get('end-date');
        $restaurant = request()->get('restaurant');
        $date = '';
        $close_stock = '';
        $inventory_op_amount = '';
        $posdate = 'AND wa_gl_trans.transaction_type = "POS Sales"';

        if ($startdate && $enddate) {
            $getDate1 = date('Y-m-d', strtotime($startdate)) . ' 00:00:00';
            $getDate2 = date('Y-m-d', strtotime($enddate)) . ' 23:59:59';
            $date .= " AND (wa_gl_trans.created_at BETWEEN '$getDate1' AND '$getDate2')";
            $posdate .= " AND (wa_gl_trans.created_at BETWEEN '$getDate1' AND '$getDate2')";
            $inventory_op_amount .= " AND (wa_gl_trans.created_at < '$getDate1')";
            $close_stock .= " AND (wa_gl_trans.created_at <= '$getDate2')";
        }

        if ($restaurant) {
            if (is_array($restaurant)) {
                $res = "(" . implode(",", $restaurant) . ")";
                $date .= " AND (wa_gl_trans.tb_reporting_branch in $res)";
                $posdate .= " AND (wa_gl_trans.tb_reporting_branch in $res)";
                $close_stock .= " AND (wa_gl_trans.tb_reporting_branch in $res)";
                $inventory_op_amount .= " AND (wa_gl_trans.tb_reporting_branch in $res)";
            } else {
                $date .= " AND (wa_gl_trans.restaurant_id = $restaurant)";
                $posdate .= " AND (wa_gl_trans.restaurant_id = $restaurant)";
                $close_stock .= " AND (wa_gl_trans.restaurant_id = $restaurant)";
                $inventory_op_amount .= " AND (wa_gl_trans.restaurant_id = $restaurant)";
            }
        }
        return $this->hasMany('App\Model\WaChartsOfAccount', 'wa_account_group_id', 'id')
            ->select([
                '*',
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND YEAR(trans_date) = "' . $this_year . '") as this_year'),
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND YEAR(trans_date) = "' . $prev_year . '") as prev_year'),
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code ' . $date . ') as t_amount'),
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code ' . $date . ' AND amount > 0 AND (wa_gl_trans.transaction_type = "Goods Received Note" OR wa_gl_trans.transaction_type = "EXPENSE")) as purchase_amount'),
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code ' . $close_stock . ' AND amount > 0) as close_stock_amount'),
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code ' . $posdate . ') as pos_amount'),
                \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code ' . $inventory_op_amount . ' AND amount > 0) as inventory_op_amount'),
            ]);
    }

    public function getChartAccountBudget()
    {
        $year = request()->year;
        $date = '';
        if (request()->startdate && request()->enddate) {
            $getDate1 = date('d', strtotime(request()->startdate));
            $getDate2 = date('d', strtotime(request()->enddate));
            $date = " AND (DATE(trans_date) BETWEEN $getDate1 AND $getDate2)";
        }
        return $this->hasMany('App\Model\WaChartsOfAccount', 'wa_account_group_id', 'id')->select([
            'wa_charts_of_accounts.*',

            //Monthly
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 01 AND YEAR(trans_date) = ' . $year . $date . ') as January_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 02 AND YEAR(trans_date) = ' . $year . $date . ') as February_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 03 AND YEAR(trans_date) = ' . $year . $date . ') as March_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 04 AND YEAR(trans_date) = ' . $year . $date . ') as April_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 05 AND YEAR(trans_date) = ' . $year . $date . ') as May_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 06 AND YEAR(trans_date) = ' . $year . $date . ') as June_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 07 AND YEAR(trans_date) = ' . $year . $date . ') as July_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 08 AND YEAR(trans_date) = ' . $year . $date . ') as August_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 09 AND YEAR(trans_date) = ' . $year . $date . ') as September_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 10 AND YEAR(trans_date) = ' . $year . $date . ') as October_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 11 AND YEAR(trans_date) = ' . $year . $date . ') as November_amount'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND MONTH(trans_date) = 12 AND YEAR(trans_date) = ' . $year . $date . ') as December_amount'),

            //Quarterly
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND (MONTH(trans_date) BETWEEN 01 AND 03) AND (YEAR(trans_date) = ' . $year . $date . ')) as jan_mar'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND (MONTH(trans_date) BETWEEN 04 AND 06) AND (YEAR(trans_date) = ' . $year . $date . ')) as apr_june'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND (MONTH(trans_date) BETWEEN 07 AND 10) AND (YEAR(trans_date) = ' . $year . $date . ')) as july_sep'),
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND (MONTH(trans_date) BETWEEN 11 AND 13) AND (YEAR(trans_date) = ' . $year . $date . ')) as oct_dec'),

            //Yearly
            \DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND (YEAR(trans_date) = ' . $year . $date . ')) as jan_dec'),

        ]);
    }

    public function getAmountAttribute()
    {
        // $startdate = Input::get('start-date');
        // $enddate = Input::get('end-date');
        // $restaurant = Input::get('restaurant');
        $startdate = request()->get('start-date');
        $enddate = request()->get('end-date');
        $restaurant = request()->get('restaurant');

        // echo $enddate; die;
        $data = WaChartsOfAccount::where('wa_account_group_id', $this->id)
            ->join('wa_gl_trans', 'wa_gl_trans.account', '=', 'wa_charts_of_accounts.account_code');
        if ($restaurant) {
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
    public function getChartAccountMontly()
    {
        $startdate = date('Y-m-01', strtotime(request()->input('start-date') ?? date('Y-m-01')));
        $enddate   = date('Y-m-t', strtotime(request()->input('end-date') ?? date('Y-m-t')));
        $restaurant   = request()->input('restaurant');
        $select = ['*'];
        $branch = '';
        if ($restaurant) {
            if (is_array($restaurant)) {
                $res = "(" . implode(",", $restaurant) . ")";
                $branch .= " AND (wa_gl_trans.tb_reporting_branch in $res)";
            } else {
                $branch .= " AND (wa_gl_trans.tb_reporting_branch = $restaurant)";
            }
        }
        $select[] = DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND (DATE(trans_date) BETWEEN "' . $startdate . '" AND "' . $enddate . '") ' . $branch . ') as amount_total');
        $selectedMonthArr = getMonthsBetweenDates($startdate, $enddate);
        $monthRange = getMonthRangeBetweenDate($startdate, $enddate);
        if (isset($selectedMonthArr['m']) && !is_null($selectedMonthArr['m'])) {
            foreach ($selectedMonthArr['m'] as $key => $month) {
                $year = $selectedMonthArr['y'][$key];


                $select[] = DB::RAW('(SELECT SUM(amount) from wa_gl_trans where account = wa_charts_of_accounts.account_code AND YEAR(trans_date) = "' . $year . '" AND MONTH(trans_date) = "' . $month . '" ' . $branch . ') as amount_' . $month . '_' . $year);
            }
        }

        return $this->hasMany('App\Model\WaChartsOfAccount', 'wa_account_group_id', 'id')
            ->select($select);
    }
}
