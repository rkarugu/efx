<?php

namespace App;

use App\Interfaces\SmsService;
use App\Model\PaymentMethod;
use App\Model\Restaurant;
use App\Model\Route;
use App\Model\UserAppPermissions;
use App\Model\WaPosCashSalesItemReturns;
use App\Model\WaPosCashSalesPayments;
use App\Models\DropLimitAlert;
use App\Models\WaCloseBranchEndOfDay;
use App\Model\WaPosCashSales;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cash_at_hand',
        'drop_limit'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class);
    }

    public function salesmanShifts(): HasMany
    {
        return $this->hasMany(SalesmanShift::class, 'salesman_id', 'id');
    }

    public function app_permissions()
    {
        return $this->hasMany(UserAppPermissions::class, 'user_id');
    }
    public function userRole()
    {
        return $this->belongsTo('App\Model\Role', 'role_id');
    }

    public function userRestaurent()
    {
        return $this->belongsTo('App\Model\Restaurant', 'restaurant_id');
    }


    public function getAssignedTableForWaiter()
    {
        return $this->hasMany('App\Model\EmployeeTableAssignment', 'user_id');
    }

    public function userDepartment()
    {
        return $this->belongsTo('App\Model\WaDepartment', 'wa_department_id');
    }
    public function getroute()
    {
        return $this->belongsTo('App\Model\Route', 'route');
    }


    public function stock_moves()
    {
        return $this->hasMany('App\Model\WaStockMove', 'user_id');
    }
    public function debtor_tran()
    {
        return $this->hasMany('App\Model\WaDebtorTran', 'salesman_user_id');
    }

    public function vehicle(): HasOne
    {
        return $this->hasOne(NewVehicle::class, 'driver_id', 'id');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(UserFingerprint::class);
    }

    public function waclosebranchendofdays(): HasMany
    {
        return $this->hasMany(WaCloseBranchEndOfDay::class);
    }

    public function branch()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function posCashSale()
    {
        return $this->hasMany(WaPosCashSales::class);
    }

    public function salesmanshiftissues(): HasMany
    {
        return $this->hasMany(SalesmanShiftIssue::class);
    }

    // public function cashAtHand()
    // {
    //     $cashier = $this;
    //     $today = today();

    //     $cashDrops = DB::table('cash_drop_transactions')
    //         ->select('cashier_id', DB::raw('SUM(amount) as total_drops'))
    //         ->whereDate('created_at', $today)
    //         ->where('cashier_id', $cashier->id)
    //         ->sum('amount');

    //     $sales = DB::table('wa_pos_cash_sales_items as items')
    //         ->select(
    //             DB::raw("(coalesce(sum((items.selling_price * items.qty) - items.discount_amount), 0)) as total")
    //         )
    //         ->join('wa_pos_cash_sales as sales', function ($join) use ($today, $cashier) {
    //             $join->on('items.wa_pos_cash_sales_id', '=', 'sales.id')
    //                 ->whereDate('sales.created_at', $today)
    //                 ->where('sales.attending_cashier', $cashier->id)
    //                 ->where('sales.status', 'Completed');
    //         })
    //         ->get();
    //     $sales = $sales->sum('total');

    //     $returnsTotal = WaPosCashSalesItemReturns::query()
    //         ->whereDate('wa_pos_cash_sales_items_return.accepted_at', $today)
    //         ->where('wa_pos_cash_sales_items_return.accepted', true)
    //         ->with(['PosCashSale'])
    //         ->whereHas('PosCashSale', function ($query) use ($cashier) {
    //             $query->where('attending_cashier', $cashier->id);
    //         })
    //         ->join('wa_pos_cash_sales_items', 'wa_pos_cash_sales_items.id', '=', 'wa_pos_cash_sales_items_return.wa_pos_cash_sales_item_id')
    //         ->select(DB::raw('SUM(wa_pos_cash_sales_items_return.return_quantity * wa_pos_cash_sales_items.selling_price) as total_returns'))
    //         ->value('total_returns') ?? 0;

    //     $cashPaymentIds = PaymentMethod::where('use_in_pos', true)
    //         ->where('is_cash', true)
    //         ->pluck('id')
    //         ->toArray();

    //     $pdqPayments = DB::table('wa_pos_cash_sales_payments as csp')
    //         ->join('wa_pos_cash_sales as cs', function ($join) use ($today, $cashier) {
    //             $join->on('csp.wa_pos_cash_sales_id', '=', 'cs.id')
    //                 ->whereDate('cs.created_at', $today)
    //                 ->where('cs.attending_cashier', $cashier->id)
    //                 ->where('cs.status', 'Completed');
    //         })
    //         ->whereNotIn('csp.payment_method_id', $cashPaymentIds)
    //         ->sum('csp.amount');

    //     // $a = WaPosCashSalesPayments::with('PosCashSale')
    //     //     ->whereHas('PosCashSale', function ($q) use ($today, $cashier) {
    //     //         $q->where('attending_cashier', $cashier->id)
    //     //             ->whereDate('created_at', $today)
    //     //             ->where('status', 'Completed');
    //     //     })
    //     //     ->whereIn('payment_method_id', $paymentIds);
    //     // ->sum('amount');
    //     // ->pluck('PosCashSale')
    //     // ->unique();
    //     // $cashSales = $orders->sum('amount');

    //     $ec = $sales - $returnsTotal - $pdqPayments - $cashDrops;
    //     return ceil($ec);
    // }


    // public function cashAtHand()
    // {
    //     $cashier = $this;
    //     $today = today();

    //     $cashDrops = DB::table('cash_drop_transactions')
    //         ->select('cashier_id', DB::raw('SUM(amount) as total_drops'))
    //         ->whereDate('created_at', $today)
    //         ->where('cashier_id', $cashier->id)
    //         ->sum('amount');

    //     $orders = WaPosCashSalesItemReturns::whereDate('accepted_at', $today)
    //         ->with('PosCashSale')
    //         ->whereHas('PosCashSale', function ($q) use ($cashier) {
    //             $q->where('attending_cashier', $cashier->id);
    //         })
    //         ->get()
    //         ->pluck('PosCashSale')
    //         ->unique();
    //     $returnsTotal = $orders->sum->acceptedReturnsTotal;

    //     $paymentIds = PaymentMethod::where('use_in_pos', true)
    //         ->where('is_cash', true)
    //         ->pluck('id')
    //         ->toArray();

    //     $orders = WaPosCashSalesPayments::with('PosCashSale')
    //         ->whereHas('PosCashSale', function ($q) use ($today, $cashier) {
    //             $q->where('attending_cashier', $cashier->id)
    //                 ->whereDate('created_at', $today)
    //                 ->where('status', 'Completed');
    //         })
    //         ->whereIn('payment_method_id', $paymentIds);
    //         // ->sum('amount');
    //         // ->pluck('PosCashSale')
    //         // ->unique();
    //     $cashSales = $orders->sum('amount');



    //     return ceil($cashSales - $returnsTotal - $cashDrops);

    // }

    public function cashAtHand()
    {
        $cashier = $this;
        $today = today();

        // Optimized: Direct sum without unnecessary select
        $cashDrops = DB::table('cash_drop_transactions')
            ->whereDate('created_at', $today)
            ->where('cashier_id', $cashier->id)
            ->sum('amount');

        // Optimized: Direct calculation without eager loading
        $returnsTotal = DB::table("wa_pos_cash_sales_items_return")
            ->join('wa_pos_cash_sales_items', 'wa_pos_cash_sales_items.id', 'wa_pos_cash_sales_items_return.wa_pos_cash_sales_item_id')
            ->join('wa_pos_cash_sales', 'wa_pos_cash_sales.id', 'wa_pos_cash_sales_items_return.wa_pos_cash_sales_id')
            ->where('wa_pos_cash_sales.attending_cashier', $cashier->id)
            ->whereDate('accepted_at', $today)
            ->where('accepted', 1)
            ->where('wa_pos_cash_sales_items_return.branch_id', $cashier->restaurant_id)
            ->sum(DB::raw('wa_pos_cash_sales_items_return.return_quantity * wa_pos_cash_sales_items.selling_price'));

        // Optimized: Get payment IDs once and cache
        $paymentIds = \Cache::remember('pos_cash_payment_ids', 3600, function () {
            return PaymentMethod::where('use_in_pos', true)
                ->where('is_cash', true)
                ->pluck('id')
                ->toArray();
        });

        // Optimized: Direct sum with join instead of eager loading
        $cashSales = DB::table('wa_pos_cash_sales_payments')
            ->join('wa_pos_cash_sales', 'wa_pos_cash_sales.id', 'wa_pos_cash_sales_payments.wa_pos_cash_sales_id')
            ->where('wa_pos_cash_sales.attending_cashier', $cashier->id)
            ->whereDate('wa_pos_cash_sales.created_at', $today)
            ->where('wa_pos_cash_sales.status', 'Completed')
            ->whereIn('wa_pos_cash_sales_payments.payment_method_id', $paymentIds)
            ->sum('wa_pos_cash_sales_payments.amount');

        return ceil($cashSales - $returnsTotal - $cashDrops);
    }


    public function dropLimitAlert()
    {
        /*send aleet*/
        $last = DropLimitAlert::where('user_id', $this->id)->latest()->first();
        if ($last && !$last->used) {
            return null;
        }

        $alert = Alert::where('alert_name', 'drop-limit-alert')->first();
        if ($alert instanceof Alert) {
            $recipientType = $alert->recipient_type;
            $recipientId = $alert->recipients;
            if ($recipientType === 'user') {
                $ids = explode(',', $alert->recipients);
                $recipients = \App\User::whereIn('id', $ids)->get();
            } else if ($recipientType === 'role') {
                // Fetch users with the specified role
                $roleids = explode(',', $alert->recipients);
                $recipients = \App\Model\User::whereIn('role_id', $roleids)->get();
            }

            if ($recipients) {
                foreach ($recipients as $recipient) {
                    $sms_msg = "Cashier  " . $this->name . ' is approaching Drop Limit.';
                    $smsService = app(SmsService::class);
                    $smsService->sendMessage($sms_msg, $recipient->phone_number);
                }
            }
        }
    }
}
