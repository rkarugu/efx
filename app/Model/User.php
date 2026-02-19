<?php

namespace App\Model;

use App\Alert;
use App\Interfaces\SmsService;
use App\Models\DropLimitAlert;
use App\Models\StockDebtor;
use App\Models\UpdateBinInventoryUtilityLog;
use App\Models\UpdateItemBin;
use App\Models\UpdateItemPriceUtilityLog;
use App\Models\UpdateNewItemInventoryUtilityLog;
use App\Models\UserGeneralLedgerAccount;
use App\NewVehicle;
use App\UserAccessRequest;
use App\UserFingerprint;
use App\UserLinkedDevice;
use App\Vehicle;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Sluggable, Notifiable;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guarded = [];
    protected $appends = ['is_hq_user'];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
                'onUpdate' => true
            ]
        ];
    }
    public function branches()
    {
        return $this->belongsToMany(Restaurant::class, 'user_branches', 'user_id', 'restaurant_id');
    }

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class);
    }

    public function getroute()
    {
        return $this->belongsTo('App\Model\Route', 'route');
    }

    public function app_permissions()
    {
        return $this->hasMany(UserAppPermissions::class, 'user_id');
    }

    public function userRole()
    {
        return $this->belongsTo('App\Model\Role', 'role_id');
    }

    public function isHqUser(): Attribute
    {
        return Attribute::make(get: fn() => $this->userRole?->is_hq_role ?? false);
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
        return $this->hasOne(Vehicle::class, 'driver_id', 'id');
    }

    public function usergeneralledgeraccounts(): HasMany
    {
        return $this->hasMany(UserGeneralLedgerAccount::class);
    }

    public function location_stores()
    {
        return $this->belongsTo(WaLocationAndStore::class, 'wa_location_and_store_id');
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

    public function linkedDevice(): HasOne
    {
        return $this->hasOne(UserLinkedDevice::class);
    }

    public function accessRequests(): HasMany
    {
        return $this->hasMany(UserAccessRequest::class);
    }

    public function fingerprints(): HasMany
    {
        return $this->hasMany(UserFingerprint::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(WaSupplier::class, 'wa_user_suppliers', 'user_id', 'wa_supplier_id');
    }

    public function rolePermissions()
    {
        return $this->hasMany(UserPermission::class, 'role_id', 'role_id');
    }

    public function userPermissions()
    {
        return $this->rolePermissions()->where('user_id', Auth::user()->id);
    }

    public function isAdministrator()
    {
        return $this->role_id == 1;
    }

    public function uom()
    {
        return $this->belongsTo(WaUnitOfMeasure::class, 'wa_unit_of_measures_id');
    }

    public function stockDebtor()
    {
        return $this->hasOne(StockDebtor::class, 'employee_id');
    }


    // Utility relationships start

    public function inventorybins(): HasMany
    {
        return $this->hasMany(UpdateBinInventoryUtilityLog::class, 'id', 'initiated_by');
    }

    public function inventoryitemprices(): HasMany
    {
        return $this->hasMany(UpdateItemPriceUtilityLog::class, 'id', 'initiated_by');
    }

    public function newiteminventoryutilitylogs(): HasMany
    {
        return $this->hasMany(UpdateNewItemInventoryUtilityLog::class, 'id', 'initiated_by');
    }

    public function updateitembins(): HasMany
    {
        return $this->hasMany(UpdateItemBin::class, 'id', 'user_id');
    }

    // Utility relationships end

    public static function withPermission(string $permissionModule, string $action)
    {
        return self::whereHas('rolePermissions', function ($query) use ($permissionModule, $action) {
            $query->where('module_name', $permissionModule)
                ->where('module_action', $action);
        });
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


    public function cashAtHand()
    {
        $cashier = $this;
        $today = today();

        $cashDrops = DB::table('cash_drop_transactions')
            ->select('cashier_id', DB::raw('SUM(amount) as total_drops'))
            ->whereDate('created_at', $today)
            ->where('cashier_id', $cashier->id)
            ->sum('amount');

        $orders = WaPosCashSalesItemReturns::whereDate('accepted_at', $today)
            ->with('PosCashSale')
            ->whereHas('PosCashSale', function ($q) use ($cashier) {
                $q->where('attending_cashier', $cashier->id);
            })
            ->get()
            ->pluck('PosCashSale')
            ->unique();

        $returnsTotal  = DB::table("wa_pos_cash_sales_items_return")
            ->select(
                DB::raw("SUM(wa_pos_cash_sales_items_return.return_quantity * wa_pos_cash_sales_items.selling_price) as amount")
            )
            ->join('wa_pos_cash_sales_items', 'wa_pos_cash_sales_items.id', 'wa_pos_cash_sales_items_return.wa_pos_cash_sales_item_id')
            ->join('wa_pos_cash_sales', 'wa_pos_cash_sales.id', 'wa_pos_cash_sales_items_return.wa_pos_cash_sales_id')
            ->where('wa_pos_cash_sales.attending_cashier', $cashier->id)
            ->whereDate('accepted_at', $today)
            ->where('accepted', 1)
            ->where('wa_pos_cash_sales_items_return.branch_id', $cashier->restaurant_id)
            ->value('amount');

        // $returnsTotal = $orders->sum->acceptedReturnsTotal;

        $paymentIds = PaymentMethod::where('use_in_pos', true)
            ->where('is_cash', true)
            ->pluck('id')
            ->toArray();

        $orders = WaPosCashSalesPayments::with('PosCashSale')
            ->whereHas('PosCashSale', function ($q) use ($today, $cashier) {
                $q->where('attending_cashier', $cashier->id)
                    ->whereDate('created_at', $today)
                    ->where('status', 'Completed');
            })
            ->whereIn('payment_method_id', $paymentIds);
        // ->sum('amount');
        // ->pluck('PosCashSale')
        // ->unique();
        $cashSales = $orders->sum('amount');



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
                $recipients = User::whereIn('role_id', $roleids)->get();
            }

            if ($recipients) {
                foreach ($recipients as $recipient) {
                    $sms_msg = "Cashier  " . $this->name . ' is approaching Drop Limit. Do a drop for them ';
                    $smsService = app(SmsService::class);
                    $smsService->sendMessage($sms_msg, $recipient->phone_number);
                }
            }
        }
    }
}
