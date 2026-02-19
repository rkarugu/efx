<?php

namespace App\Http\Controllers\Admin;

use App\EncryptionHelper;
use App\Exports\RouteCustomerExport;
use App\Exports\UsersExport;
use App\Model\PesaflowResponse;
use App\Model\Role;
use App\Model\WaDebtorTran;
use App\Model\WaInternalRequisition;
use App\Model\WaMergedPayments;
use App\Model\Restaurant;
use App\Model\WaRouteCustomer;
use App\Model\WaShift;
use App\Pesaflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\User;
use App\Model\WaUserSupplier;
use App\Model\UserOtp;
use App\Model\UserLog;
use App\Http\Requests\Admin\UserAddRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use DB;
use App\Model\TableManager;
use App\Model\EmployeeTableAssignment;
use App\Model\UserPermission;
use App\Model\Order;
use App\Model\WaiterTip;
use App\Model\Route;
use App\Model\OrderReceipt;
use App\Model\ReceiptSummaryPayment;
use App\Model\WaCategory;
use Carbon\Carbon;
use Firebase;
use Illuminate\Support\Facades\Input;
use App\Model\MpesaTransactionDetail;
use App\Model\BankEquityTransaction;
use App\Model\WalletTransaction;
use App\Interfaces\SmsService;

use App\Model\OrderBookedTable;
use App\Model\WaCustomer;
use App\SalesmanShift;
use DateTime;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

//use Session;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use File;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class UserController extends Controller
{

    public function equity_callback(Request $request)
    {
        $checkprevious = BankEquityTransaction::where('bankreference', $request->bankreference)->first();
        if ($checkprevious) {
            return response()->json(['status' => false, 'message' => 'Equity Tranasaction Already Here']);
        }
        $userid = 1;
        $location = \App\Model\WaLocationAndStore::where('account_no', $request->debitaccount)->first();
        if ($location) {
            $user = \App\Model\User::where('wa_location_and_store_id', $location->id)->first();
            if ($user) {
                $userid = $user->id;
            }
            $item = new BankEquityTransaction;
            $item->user_id = $userid;
            $item->billNumber = $request->billNumber;
            $item->billAmount = $request->billAmount;
            $item->CustomerRefNumber = $request->CustomerRefNumber;
            $item->bankreference = $request->bankreference;
            $item->tranParticular = $request->tranParticular;
            $item->paymentMode = $request->paymentMode;
            $item->transactionDate = $request->transactionDate ? date('Y-m-d H:i:s', strtotime($request->transactionDate)) : date('Y-m-d H:i:s');
            $item->phonenumber = $request->phonenumber;
            $item->debitaccount = $request->debitaccount;
            $item->debitcustname = $request->debitcustname;
            $item->transaction_type = 'Credit';
            $item->save();
            return response()->json(['status' => true, 'message' => 'Equity Tranasaction Added Successfully']);
        }
        return response()->json(['status' => false, 'message' => "Account Does't Exist"]);
    }

    public function pesaflow_callback(Request $request, $invoice_no_hash)
    {
        Log::info("PESAFLOW_CALLBACK", $request->all());

        $requestData = $request->all();

        $invoice_no = EncryptionHelper::decrypt($invoice_no_hash);

        PesaflowResponse::firstOrCreate(
            ['invoice_number' => $requestData['invoice_number']],
            [
                'payment_reference' => $requestData['payment_reference'][0]['payment_reference'],
                'currency' => $requestData['currency'],
                'amount' => $requestData['amount_paid'],
                'payment_date' => $requestData['payment_date'],
                'payment_channel' => $requestData['payment_channel'],
                'invoice_amount' => $requestData['amount_paid'],
                'client_invoice_ref' => $invoice_no,
                'amount_paid' => $requestData['amount_paid'],
                'status' => $requestData['status'],
                'response' => json_encode($requestData),
            ]
        );

        $wa_debtor_trans = WaDebtorTran::where('document_no', $invoice_no)->first();

        $wa_shift = WaShift::where('id', $wa_debtor_trans->shift_id)->first();

        WaMergedPayments::create(
            [
                'shift' => $wa_shift->shift_id,
                'salesman_user_id' => $wa_debtor_trans->salesman_user_id,
                'amount' => $requestData['amount_paid'],
                'payment_account' => '12012', //should be the pesaflow kcb account
                'transaction_no' => $invoice_no,
                'description' => $invoice_no,
                'narration' => $requestData['invoice_number'],
                'shift_id' => $wa_shift->id,
                'salesman_id' => $wa_debtor_trans->salesman_id,
                'is_cheque_trans' => '0',
                'trans_date' => $requestData['payment_date'],
                'is_posted_to_account' => '0',
                'wa_debtor_trans_id' => $wa_debtor_trans->id,
            ]
        );

        return response()->json(['status' => 'ok', 'message' => 'Payment request handled successfully']);
    }


    protected $model;
    protected $title;
    protected $pmodule;
    protected $top_up_type;

    public function __construct(protected SmsService $smsService)
    {
        $this->model = 'employees';
        $this->title = 'Employee';
        $this->pmodule = 'employees';
        $this->top_up_type = ['Mpesa Top Up' => 'Mpesa Top Up', 'Card Top Up' => 'Card Top Up', 'Loyalty Top Up' => 'Loyalty Top Up', 'Cash Top Up' => 'Cash Top Up'];
    }


    public function index(Request $request)
    {
        $authuser = Auth::user();
        if (!$authuser) {
            return redirect()->route('admin.login')->with('warning', 'Please login to continue');
        }

        $permission = $this->mypermissionsforAModule();
        if (!$permission) {
            return redirect()->back()->with('warning', 'You do not have permission to access this module');
        }

        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        
        $userwithrestaurants = $authuser->load('userRestaurent');
        $roleFilter = $request->role_id;
        $restaurantFilter = $request->restaurant_id;
        $canviewall = false;

        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $app_permissions = userAppPermissions();
            $restaurantid = request()->input('restaurant_id', 
                $authuser->userRestaurent ? $authuser->userRestaurent->id : null
            );
            $isAdmin = $authuser->role_id == 1;

            $usersQuery = User::select([
                '*',
                DB::raw("(SELECT COALESCE(GROUP_CONCAT(DISTINCT routes.route_name), '') 
                FROM route_user
                LEFT JOIN routes ON routes.id = route_user.route_id
                WHERE route_user.user_id = users.id) as routes"),
                DB::RAW('(select COUNT(*) from user_permissions where user_permissions.user_id=users.id) as invoice_r_permission_count'),
                DB::RAW('(select COUNT(*) from wa_stock_moves where wa_stock_moves.user_id=users.id) as stock_count'),
                DB::RAW('(select COUNT(*) from wa_debtor_trans where wa_debtor_trans.salesman_user_id=users.id) as debtor_count'),
            ])->with(['app_permissions', 'userRestaurent', 'routes', 'userRole'])
                ->whereNot('role_id', 1);

            if (!$isAdmin && !isset($permission['employees' . '___view_all_branches_data'])) {
                $usersQuery->whereHas('userRestaurent', function ($query) use ($restaurantid) {
                    $query->where('id', $restaurantid);
                });
            }

            // filter
            if ($roleFilter !== null) {
                $usersQuery->whereHas('userRole', function ($query) use ($roleFilter) {
                    if ($roleFilter) {
                        $query->where('id', $roleFilter);
                    }
                });
            }
            if ($restaurantFilter !== null) {
                $usersQuery->WhereHas('userRestaurent', function ($query) use ($restaurantFilter) {
                    if ($restaurantFilter) {
                        $query->where('id', $restaurantFilter);
                    }
                });
            }

            $users = $usersQuery->orderBy('id')->get()
            // ->map(function (User $user) {
            //     if ((count($user->routes) == 0) && $user->route) {
            //         $user->routes()->attach($user->route);
            //     }
            //     $user->routes = implode(",", $user->routes()->pluck('route_name')->toArray());
            //     return $user;
            // })
            ;

            if ($isAdmin || isset($permission['employees' . '___view_all_branches_data'])) {
                $canviewall = true;
            }

            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            if ($request->get('manage-request') && $request->get('manage-request') == 'excel') {

                $data = $users->map(function ($user) {
                    $payload = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'id_number' => $user->id_number,
                        'email' => $user->email,
                        'phone' => $user->phone_number,
                        'role' => @$user->userRole->title,
                        'branch' => ucfirst(@$user->userRestaurent->name),

                    ];

                    return $payload;
                });
                $export = new UsersExport($data);

                return Excel::download($export, "USERS ALL.xlsx");
            }
            return view('admin.users.index', compact('title', 'users', 'roleFilter', 'restaurantid', 'canviewall', 'authuser', 'model', 'breadcum', 'pmodule', 'permission', 'app_permissions'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    /**
     * Determine if the given user should be treated as a salesman and allowed web login.
     * We consider a user a salesman if:
     *  - They have an assigned route (user->route or routes relation), or
     *  - Their role name/title contains 'sales' (case-insensitive), or
     *  - They match known sales role IDs (e.g., 169, 170)
     */
    private function canSalesmanUseWeb($user): bool
    {
        if (!$user) {
            return false;
        }

        $roleName = '';
        if (isset($user->userRole)) {
            // Some installations use 'name', others 'title'
            $roleName = $user->userRole->name ?? $user->userRole->title ?? '';
        }

        $hasRoute = false;
        try {
            // Some schemas store a direct 'route' FK, others via many-to-many
            $hasRoute = !empty($user->route) || method_exists($user, 'routes') && $user->routes()->exists();
        } catch (\Throwable $e) {
            $hasRoute = !empty($user->route);
        }

        $salesRoleIds = config('salesman.sales_role_ids', [169, 170]);
        $salesKeywords = config('salesman.sales_role_keywords', ['sales', 'salesman', 'representative']);
        
        $isSalesRoleId = in_array((int) $user->role_id, $salesRoleIds);
        
        $roleLooksSales = false;
        foreach ($salesKeywords as $keyword) {
            if (stripos($roleName, $keyword) !== false) {
                $roleLooksSales = true;
                break;
            }
        }

        return ($hasRoute || $roleLooksSales || $isSalesRoleId);
    }


    public function filterUsers(Request $request)
    {

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        // filters
        $authuser = Auth::user();
        $roleFilter = $request->role_id;
        $restaurantFilter = $request->restaurant_id;
        $userwithrestaurants = $authuser->load('userRestaurent');
        $canviewall = false;


        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $app_permissions = userAppPermissions();
            $restaurantid = $restaurantFilter ?? 
                ($authuser->userRestaurent ? $authuser->userRestaurent->id : null);
            // $restaurantid = request()->input('restaurant_id', $authuser->userRestaurent->id);

            $isAdmin = $authuser->role_id == 1;
            $usersQuery = User::select([
                '*',
                DB::RAW('(select COUNT(*) from user_permissions where user_permissions.user_id=users.id) as invoice_r_permission_count'),
                DB::RAW('(select COUNT(*) from wa_stock_moves where wa_stock_moves.user_id=users.id) as stock_count'),
                DB::RAW('(select COUNT(*) from wa_debtor_trans where wa_debtor_trans.salesman_user_id=users.id) as debtor_count'),
            ])->with(['app_permissions', 'userRestaurent', 'routes', 'userRole'])
                ->whereNot('role_id', 1);

            if (!$isAdmin && !isset($permission['employees' . '___view_all_branches_data'])) {
                $usersQuery->whereHas('userRestaurent', function ($query) use ($restaurantid) {
                    $query->where('id', $restaurantid);
                });
            }

            // filter
            if ($roleFilter !== null) {
                $usersQuery->whereHas('userRole', function ($query) use ($roleFilter) {
                    if ($roleFilter) {
                        $query->where('id', $roleFilter);
                    }
                });
            }
            if ($restaurantFilter !== null) {
                $usersQuery->WhereHas('userRestaurent', function ($query) use ($restaurantFilter) {
                    if ($restaurantFilter) {
                        $query->where('id', $restaurantFilter);
                    }
                });
            }
            $users = $usersQuery->orderBy('id')
                ->get()
                ->map(function (User $user) {
                    if ((count($user->routes) == 0) && $user->route) {
                        $user->routes()->attach($user->route);
                    }

                    $user->routes = implode(",", $user->routes()->pluck('route_name')->toArray());
                    return $user;
                });

            if ($isAdmin || isset($permission['employees' . '___view_all_branches_data'])) {
                $canviewall = true;
            }
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.users.index', compact('title', 'users', 'restaurantid', 'role_id', 'canviewall', 'authuser', 'model', 'breadcum', 'pmodule', 'permission', 'app_permissions'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function userGetChangePassword()
    {

        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        $user_id = \Session::get('admin_userid');
        $row = User::whereId($user_id)->first();
        $title = 'Change Password';
        $breadcum = ['Change Password' => ''];
        return view('admin.users.change_profile_password', compact('title', 'breadcum', 'row', 'model', 'title', 'pmodule'));
    }

    public function userPostChangePassword(Request $request)
    {
        $rules = array(
            'old_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        } else {
            $user_id = \Session::get('admin_userid');
            $user = User::whereId($user_id)->first();
            if (Hash::check($request->get('old_password'), $user->password)) {
                $user->password = Hash::make($request->get('new_password'));
                $user->save();
                Session::flash('success', 'Password updated successfully.');
                return redirect()->back();
            } else {
                Session::flash('warning', 'Wrong old password');
                return redirect()->back();
            }
        }
    }


    public function changePassword($slug)
    {

        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___change_password']) || $permission == 'superadmin') {
            $row = User::whereSlug($slug)->first();
            if ($row) {
                $model = $this->model;
                $title = 'Change Password ' . $this->title;
                $breadcum = [$this->title => route($model . '.index'), 'Change Password' => ''];

                //echo 'here';
                return view('admin.users.change_password', compact('title', 'model', 'breadcum', 'row'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } else {


            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function postchangePassword(Request $request, $slug)
    {
        $rules = array(
            'new_password' => 'required|min:8|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
            'confirm_password' => 'required|same:new_password',
        );
        $validator = Validator::make($request->all(), $rules, [
            'new_password.regex' => 'Password must have Atleast 1 Capital Letter, Atleast 1 Number, Atleast 1 Special Character'
        ]);
        if ($validator->fails()) {

            Session::flash('warning', 'Password must be atleast 8 characters long, contain a mix of lower and upper case letters, numbers and special characters');
            return redirect()->back();
        } else {
            $model = $this->model;
            $row = User::whereSlug($slug)->first();
            $row->password = Hash::make($request->get('new_password'));
            $row->save();
            Session::flash('success', 'Password updated successfully.');
            return redirect()->route($model . '.index');
        }
    }


    public function login()
    {
        $title = 'Login';
        Session::forget('otp_session');
        Session::forget('otp_verification_user_id');
        Session::put('previous_url', url()->previous());
        return view('admin.users.login', compact('title'));
    }

    public function makelogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            }
            
            $username = $request->username;
            // Make sure we load the userRole relationship for role name checks
            $user = User::with('userRole')
                ->where(function ($e) use ($username) {
                    $e->where('email', $username)->orWhere('phone_number', $username);
                })
                ->where('status', '1')
                ->first();

            $restrictedUsers = [4, 181];
            // Allow salesmen to log in via web if explicitly permitted by config
            $allowSalesmanWebLogin = config('salesman.allow_web_login', true);

            $isRestrictedRole = in_array($user?->role_id, $restrictedUsers);
            $isSalesmanAllowed = $allowSalesmanWebLogin && $this->canSalesmanUseWeb($user);

            if (!$user || !Hash::check($request->password, $user->password) || ($isRestrictedRole && !$isSalesmanAllowed)) {
                Session::flash('danger', 'Invalid username or password');
                return redirect()->back()->withInput(['username' => $request->username]);
            }

            // Check if user is blocked
            if ($user->role_id == 152 && $user->is_blocked == 1) {
                Session::flash('danger', $user->block_reason);
                return redirect()->back()->withInput(['username' => $request->username]);
            }

            // Check for POS Cashier role to bypass EOD check
            $isPOSCashier = false;
            
            // Add detailed logging about the user's role
            \Illuminate\Support\Facades\Log::info(
                'User login attempt role info', 
                [
                    'user_id' => $user->id,
                    'username' => $request->username,
                    'role_id' => $user->role_id,
                    'role_name' => $user->userRole ? $user->userRole->name : 'No role',
                    'sales_role' => ($user->role_id == 169 || $user->role_id == 170) ? 'Yes' : 'No'
                ]
            );
            
            // Consider user a POS Cashier if role_id is 2 (common POS Cashier role) or the name contains relevant terms
            if ($user->userRole) {
                // Check if role is POS Cashier (flexible detection)
                $isPOSCashier = $user->role_id == 2 || // Common POS Cashier role ID
                             stripos($user->userRole->name, 'cashier') !== false || 
                             stripos($user->userRole->name, 'pos') !== false ||
                             (isset($user->userRole->is_pos_cashier) && $user->userRole->is_pos_cashier == 1);
                             
                // Log the detection result
                \Illuminate\Support\Facades\Log::info(
                    'POS Cashier detection result', 
                    ['is_pos_cashier' => $isPOSCashier ? 'Yes' : 'No']
                );
            }
            
            // Global flag to bypass all EOD checks - change to true to completely disable EOD checks
            $bypassAllEodChecks = true;
            
            // Apply EOD check for sales roles, but skip for POS Cashiers or if global bypass is enabled
            if (($user->role_id == 169 || $user->role_id == 170) && !$isPOSCashier && !$bypassAllEodChecks) {
                //yesterday record for eod routine
                $yesterday = Carbon::now()->subDay()->toDateString();
                $eodRecord = DB::table('end_of_day_routines')
                    ->where('branch_id', $user->restaurant_id)
                    ->where('day', $yesterday)
                    ->first();
                    
                if (!$eodRecord) {
                    // Log that we're blocking access due to missing EOD record
                    \Illuminate\Support\Facades\Log::warning(
                        'Login blocked due to missing EOD record', 
                        ['user_id' => $user->id, 'username' => $request->username, 'role_id' => $user->role_id]
                    );
                    Session::flash('danger', 'No EOD record found for yesterday. Please contact Admin.');
                    return redirect()->back()->withInput(['username' => $request->username]);
                }
                
                if ($eodRecord->lock_users == 1) {
                    Session::flash('danger', 'Access Blocked');
                    return redirect()->back()->withInput(['username' => $request->username]);
                }
            }
            
            // If user is a POS Cashier and would normally have EOD check, log that we're bypassing it
            if ($isPOSCashier && ($user->role_id == 169 || $user->role_id == 170)) {
                \Illuminate\Support\Facades\Log::info(
                    'Bypassing EOD check for POS Cashier', 
                    ['user_id' => $user->id, 'username' => $request->username, 'role_id' => $user->role_id]
                );
            }
            
            // Handle login without OTP in development or when OTP is disabled
            if ((!app()->environment('production')) || !env("USE_OTP")) {
                Session::put('admin_userid', $user->id);
                Session::put('userdata', $user);
                Session::put('activity_time', date('Y-m-d H:i:s'));
                Session::put('AdminLoggedIn', ['user_id' => $user->id, 'role' => $user->role_id]);
                Session::save();

                Auth::login($user);

                $this->logUserActivity($user, $request);

                if ($user->role_id == 10003) {
                    return redirect()->intended(route('admin.chairmain-dashboard'));
                } elseif ($user->role_id == 6) {
                    // Redirect delivery drivers to their mobile app interface
                    return redirect()->intended(route('delivery-driver.mobile'));
                } elseif (Session::has('previous_url')) {
                    return redirect()->intended(Session::get('previous_url'));
                }
                
                return redirect()->intended('/admin/dashboard');
            }

            // Special handling for Chairman role
            if ($user->role_id == 10003) {
                Session::put('admin_userid', $user->id);
                Session::put('userdata', $user);
                Session::put('activity_time', date('Y-m-d H:i:s'));
                Session::put('AdminLoggedIn', ['user_id' => $user->id, 'role' => $user->role_id]);
                Session::save();

                Auth::login($user);

                $this->logUserActivity($user, $request);

                return redirect()->intended(route('admin.chairmain-dashboard'));
            } else {
                // Regular users need OTP verification
                $otp_msg = $this->send_otp($user);
                Session::flash('success', 'Otp Sent Successfully');
                Session::put('access_denied_user_id', $user->id);
                Session::put('otp_verification_user_id', $user->id);
                Session::save();
                return redirect()->route('admin.user_otp');
            }
        } catch (\Exception $e) {
            Session::flash('danger', $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
    
    /**
     * Helper method to log user login activity
     */
    private function logUserActivity($user, $request)
    {
        $user_agent = $request->header('User-Agent');
        $row = new UserLog();
        $row->user_id = $user->id;
        $row->user_name = $user->name;
        $row->user_ip = $request->ip();
        $row->user_agent = $user_agent;
        $row->activity = "Logged into the system.";
        $row->save();

        activity()
            ->causedBy(AUTH::user())
            ->log('USER Login');
    }

    public function send_otp($user)
    {
        $otp = UserOtp::where('user_id', $user->id)->orderBy('id', 'DESC')->where('is_used', 0)->first();
        if (!$otp) {
            $otp = new UserOtp();
            $otp->user_id = $user->id;
        }
        $six_digit_random_number = random_int(100000, 999999);
        $otp->otp = Hash::make($six_digit_random_number);
        $otp->phone_number = $user->phone_number;
        $otp->is_used = 0;
        $otp->expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));
        $otp->save();
        $sms_msg = "Otp for two factor authentication: " . $six_digit_random_number;
        // $infoskyService = new InfoSkySmsService();

        $this->smsService->sendMessage($sms_msg, $user->phone_number);
        //        sendOtp($sms_msg, $user->phone_number);
        return $sms_msg;
    }

    public function user_resend_otp()
    {
        try {
            $user_id = Session::get('otp_verification_user_id');
            $user = User::whereId($user_id)->first();
            $sent_msg = $this->send_otp($user);
            Session::flash('success', 'Otp Sent Successfully ');
            return redirect()->back();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('danger', $msg);
            return redirect()->route('admin.login');
        }
    }

    public function user_otp_verify(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'otp' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {
                $otp = $request->otp;
                $user_id = Session::get('otp_verification_user_id');
                $user = User::whereId($user_id)->first();
                $user_otp = UserOtp::where('user_id', $user->id)->orderBy('id', 'DESC')->where('is_used', 0)->first();
                if ($user && $user_otp) {
                    if (strtotime($user_otp->expiry) < strtotime(date("Y-m-d H:i:s"))) {
                        Session::flash('danger', 'OTP is expired!');
                        return redirect()->route('admin.user_otp');
                    }
                    if (Hash::check($otp, $user_otp->otp)) {
                        Session::put('otp_session', Hash::make($user->id));
                        Session::put('admin_userid', $user->id);
                        Session::put('userdata', $user);
                        Session::put('activity_time', date('Y-m-d H:i:s'));
                        Session::put('AdminLoggedIn', ['user_id' => $user->id, 'role' => $user->role_id]);
                        Session::save();
                        Auth::login($user);

                        $user_agent = $request->header('User-Agent');
                        $row = new UserLog();
                        $row->user_id = $user->id;
                        $row->user_name = $user->name;
                        $row->user_ip = $request->ip();
                        $row->user_agent = $user_agent;
                        $row->activity = "Logged into the system.";
                        $row->save();

                        $user_agent = $request->header('User-Agent');
                        if (strpos(strtolower($user_agent), 'mobile') !== false || strpos(strtolower($user_agent), 'android') !== false) {
                            $sms_msg = $user->name . " has logged into a mobile device outside the network at " . date("Y-m-d H:i:s");
                            $infoskyService = new InfoSkySmsService();
                            $infoskyService->sendMessage($sms_msg, "0720314757");
                            $infoskyService->sendMessage($sms_msg, "0740489494");
                            $infoskyService->sendMessage($sms_msg, "0724635051");
                            $infoskyService->sendMessage($sms_msg, "0710481557");
                        }
                        $user_otp->is_used = 1;
                        $user_otp->save();
                        
                        // Redirect delivery drivers to their mobile app interface
                        if ($user->role_id == 6) {
                            return redirect()->route('delivery-driver.mobile');
                        }
                        
                        return redirect()->route('admin.dashboard');
                    } else {
                        Session::flash('danger', 'Invalid OTP!');
                        return redirect()->route('admin.user_otp');
                    }
                } else {
                    Session::flash('danger', 'Invalid username or password');
                    Session::forget('otp_session');
                    Session::forget('otp_verification_user_id');
                    return redirect()->route('admin.login');
                }
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('danger', $msg);
            return redirect()->back()->withInput();
        }
    }

    public function user_otp()
    {
        if (Session::has('otp_session')) {
            return redirect()->route('admin.dashboard');
        }
        try {
            $user_id = Session::get('otp_verification_user_id');
            $row = User::whereId($user_id)->first();
            $title = 'Otp Verify';
            $breadcum = ['Otp Verify' => ''];
            return view('admin.users.user_otp', compact('title', 'row', 'breadcum'));
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('danger', $msg);
            return redirect()->route('admin.login');
        }
    }

    public function logout()
    {
        $userId = Session::get('admin_userid');
        $logged_user = User::whereId($userId)->first();
        if ($logged_user) {
            $logged_user->online_status = 'offline';
            $logged_user->device_id = null;
            $logged_user->user_agent = null;
            $logged_user->device_category = null;
            $logged_user->time_logged_in = null;
            $logged_user->save();
        }

        activity('logout')
            ->causedBy(AUTH::user())
            ->log('USER Logout');

        Auth::logout();
        Session::forget('otp_session');
        Session::forget('otp_verification_user_id');
        Session::forget('AdminLoggedIn');
        Session::forget('userdata');
        Session::forget('activity_time');
        Session::forget('admin_userid');
        return redirect()->route('admin.login');
    }


    public function myProfile()
    {
        $user_id = Session::get('admin_userid');
        $row = User::whereId($user_id)->first();
        $title = 'My-Profile';
        $breadcum = ['My Profile' => ''];
        $restroList = $this->getRestaurantList();
        $model = 'profile';
        return view('admin.users.myProfile', compact('title', 'row', 'breadcum', 'restroList', 'model'));
    }

    public function updateMyProfile(Request $request, $slug)
    {
        $row = User::whereSlug($slug)->first();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'phone_number' => 'required|numeric|unique:users,phone_number,' . $row->id,
                'image_update' => 'mimes:jpeg,jpg,png',
                'email' => 'required|email|unique:users,email,' . $row->id,
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {
                $previous_row = $row;
                $row->name = $request->name;
                $row->phone_number = $request->phone_number;
                $row->email = $request->email;
                $row->restaurant_id = $request->restaurant_id;
                $row->wa_department_id = $request->wa_department_id;
                $row->wa_location_and_store_id = $request->wa_location_and_store_id;


                if (isset($request->max_discount_percent)) {
                    $row->max_discount_percent = $request->max_discount_percent;
                }


                if ($request->file('image_update')) {
                    // Define correct absolute paths using base_path(), which points to public_html
                    $main_dir = base_path('uploads/users');
                    $thumb_dir = base_path('uploads/users/thumb');

                    // Create directories if they don't exist
                    if (!\Illuminate\Support\Facades\File::isDirectory($main_dir)) {
                        \Illuminate\Support\Facades\File::makeDirectory($main_dir, 0775, true, true);
                    }
                    if (!\Illuminate\Support\Facades\File::isDirectory($thumb_dir)) {
                        \Illuminate\Support\Facades\File::makeDirectory($thumb_dir, 0775, true, true);
                    }

                    $file = $request->file('image_update');
                    $image_name = time() . rand(111111111, 999999999) . '.' . $file->getClientOriginalExtension();

                    // Create and save a resized thumbnail
                    $thumb_image = \Intervention\Image\Facades\Image::make($file->getRealPath())->resize(341, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    // Move the original file and save the thumbnail
                    $file->move($main_dir, $image_name);
                    $thumb_image->save($thumb_dir . '/' . $image_name);

                    // Delete old image if it exists, using the correct absolute paths
                    if ($previous_row->image) {
                        if (\Illuminate\Support\Facades\File::exists($main_dir . '/' . $previous_row->image)) {
                            \Illuminate\Support\Facades\File::delete($main_dir . '/' . $previous_row->image);
                        }
                        if (\Illuminate\Support\Facades\File::exists($thumb_dir . '/' . $previous_row->image)) {
                            \Illuminate\Support\Facades\File::delete($thumb_dir . '/' . $previous_row->image);
                        }
                    }
                    $row->image = $image_name;
                }
                $row->save();
                Session::flash('success', 'Record updated successfully.');
                return redirect()->route('admin.profile');
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }


    public function create()
    {
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___add']) || $permission == 'superadmin') {
            $title = 'Add ' . $this->title;
            $model = $this->model;
            $restroList = $this->getRestaurantList();
            $app_permissions = userAppPermissions();
            $categoryList = WaCategory::pluck('title', 'id');

            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
            return view('admin.users.create', compact('title', 'model', 'breadcum', 'restroList', 'categoryList', 'app_permissions'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function authorizationAssignment($slug)
    {
        $row = User::whereSlug($slug)->first();
        return view('admin.users.authorization_level_popup', compact('row', 'slug'));
    }

    public function externalauthorizationAssignment($slug)
    {
        $row = User::whereSlug($slug)->first();
        return view('admin.users.external_authorization_level_popup', compact('row', 'slug'));
    }

    public function purchaseOrderauthorizationAssignment($slug)
    {
        $row = User::whereSlug($slug)->first();
        return view('admin.users.purchase_order_authorization_level_popup', compact('row', 'slug'));
    }


    public function purchaseOrderauthorizationAssignmentPost(Request $request)
    {
        $emp_level = $request->emp_level;
        $user_slug = $request->user_slug;
        $user = User::whereSlug($request->user_slug)->first();
        $user->purchase_order_authorization_level = $emp_level > '0' ? (string)$emp_level : null;
        $user->save();
        return '1';
    }

    public function authorizationAssignmentPost(Request $request)
    {

        $emp_level = $request->emp_level;
        $user_slug = $request->user_slug;

        $user = User::whereSlug($request->user_slug)->first();

        $user->authorization_level = $emp_level > '0' ? (string)$emp_level : null;
        $user->save();
        return '1';
    }

    public function externalauthorizationAssignmentPost(Request $request)
    {

        $emp_level = $request->emp_level;
        $user_slug = $request->user_slug;
        $user = User::whereSlug($request->user_slug)->first();
        $user->external_authorization_level = $emp_level > '0' ? (string)$emp_level : null;
        //emp authority level hasbeen changed now it will be manage
        empExtranalAuthorityIsChanged($user->id);
        $user->save();
        return '1';
    }


    /*user table management with section start*/

    public function tableAssignment($slug)
    {
        $row = User::whereSlug($slug)->first();
        $already_assigned = EmployeeTableAssignment::where('user_id', $row->id)->pluck('table_manager_id')->toArray();
        $all_tables = [];
        if (count($already_assigned) > 0) {
            $all_tables = TableManager::where('restaurant_id', $row->restaurant_id)
                ->whereIn('id', $already_assigned)
                ->get();
        }
        return view('admin.users.table_assignment_popup', compact('row', 'all_tables', 'already_assigned', 'slug'));
    }


    public function clearAllTableFromWaiter()
    {


        $all_assigned_table = EmployeeTableAssignment::pluck('table_manager_id')->toArray();
        $countOfAssignedTable = count($all_assigned_table);
        $all_assigned_table = array_combine($all_assigned_table, $all_assigned_table);
        $all_assigned_table = count($all_assigned_table) > 0 ? $all_assigned_table : [0];

        //total tables ids which have orders

        $orderedTable = OrderBookedTable::whereIn('table_id', $all_assigned_table)
            ->whereHas('getRelativeOrderData', function ($query) {
                $query->whereNotIn('status', ['COMPLETED', 'CANCLED']);
            })
            ->pluck('table_id')->toArray();

        $pendingOrdersTablesCount = count($orderedTable);

        foreach ($orderedTable as $BusyTable_id) {
            unset($all_assigned_table[$BusyTable_id]);
        }

        EmployeeTableAssignment::whereIn('table_manager_id', $all_assigned_table)->delete();

        if ($pendingOrdersTablesCount > 0) {
            Session::flash('warning', 'Some waiter cannot be released due to some uncompleted order');
        } else {
            Session::flash('success', 'Table cleared successfully');
        }


        return redirect()->back();
    }


    public function assignOrRemoveTableFromWaiter(Request $request)
    {

        $request_type = $request->type;
        $user_slug = $request->user_slug;
        $table_id = $request->table_id;
        $return_data = 1;
        $user = User::whereSlug($request->user_slug)->first();

        if ($request_type == 'removeall') {
            $all_assigned_table = EmployeeTableAssignment::where('user_id', $user->id)->pluck('table_manager_id')->toArray();
            $all_assigned_table = count($all_assigned_table) > 0 ? $all_assigned_table : [0];

            $orderedTable = OrderBookedTable::whereIn('table_id', $all_assigned_table)
                ->whereHas('getRelativeOrderData', function ($query) {
                    $query->whereNotIn('status', ['COMPLETED', 'CANCLED']);
                })
                ->pluck('table_id')->toArray();

            if (count($orderedTable) > 0) {
                return 'CANNOTREMOVE';
            } else {
                EmployeeTableAssignment::where('user_id', $user->id)->delete();
            }
        }
        if ($request_type == 'remove') {
            $orderedTable = OrderBookedTable::where('table_id', $table_id)
                ->whereHas('getRelativeOrderData', function ($query) {
                    $query->whereNotIn('status', ['COMPLETED', 'CANCLED']);
                })
                ->pluck('table_id')->first();

            if ($orderedTable) {
                return 'CANNOTREMOVE';
            } else {
                EmployeeTableAssignment::where('user_id', $user->id)->where('table_manager_id', $table_id)->delete();
            }
        }

        if ($request_type == 'assign') {
            $table_info = EmployeeTableAssignment::where('table_manager_id', $table_id)->first();
            if ($table_info) {
                $return_data = $table_info->user_id == $user->id ? 1 : 2;
            } else {
                EmployeeTableAssignment::updateOrCreate(
                    ['user_id' => $user->id, 'table_manager_id' => $table_id]
                );
            }
        }
        return $return_data;
    }


    public function getFreetablesForAssignmentByUserAndSection(Request $request)
    {
        $user = User::whereSlug($request->user_slug)->first();
        $all_tables = TableManager::select(['id', 'name', 'block_section'])->where('restaurant_id', $user->restaurant_id)
            ->where('block_section', $request->section)
            ->doesnthave('tableAssignmentForrelatedTable')
            ->get();

        $all_empty_tables = [];
        $i = 0;
        foreach ($all_tables as $table) {
            $all_empty_tables[$i]['table_id'] = $table->id;
            $all_empty_tables[$i]['table_name'] = $table->name;
            $all_empty_tables[$i]['block_section'] = $table->block_section;
            $i++;
        }
        return json_encode($all_empty_tables);
    }

    public function getFreetablesForAssignment($user)
    {
        $all_tables = TableManager::select(['id'])->where('restaurant_id', $user->restaurant_id)->get();
        $all_empty_table = [];

        if (count($all_tables) > 0) {
            $all_booked_tables = EmployeeTableAssignment::whereIn('table_manager_id', $all_tables)->pluck('user_id', 'table_manager_id')->toArray();

            foreach ($all_tables as $table) {
                if (isset($all_booked_tables[$table->id])) {
                    if ($all_booked_tables[$table->id] == $user->id) {
                        $all_empty_table[] = $table->id;
                    }
                } else {
                    $all_empty_table[] = $table->id;
                }
            }
        }
        return $all_empty_table;
    }

    /*user table management with section end*/

    public function tableAssignmentUpdate(Request $request, $slug)
    {
        $row = User::whereSlug($slug)->first();
        EmployeeTableAssignment::where('user_id', $row->id)->delete();
        if ($request->tableids && count($request->tableids) > 0) {
            $all_free_tables = $this->getFreetablesForAssignment($row);
            foreach ($request->tableids as $table_id => $ke) {

                if (in_array($table_id, $all_free_tables)) {
                    EmployeeTableAssignment::updateOrCreate(
                        ['user_id' => $row->id, 'table_manager_id' => $table_id]
                    );
                }
            }
        }
        Session::flash('success', 'Table assignment completed successfully');
        return redirect()->back();
    }

    public function store(Request $request)
    {
        try {
            $rules = array(
                'phone_number' => 'required|unique:users,phone_number',
                'password' => 'required|min:8|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/',
                'restaurant_id' => 'required|exists:restaurants,id',
                'name' => 'required'
            );
            $validator = Validator::make($request->all(), $rules, [
                'password.regex' => 'Password must have Atleast 1 Capital Letter, Atleast 1 Number, Atleast 1 Special Character',
                'phone_number.unique' => 'user with same phone number already exists',
                'restaurant_id.required' => 'Branch/Restaurant is required',
                'restaurant_id.exists' => 'Selected Branch/Restaurant does not exist'
            ]);
            if ($validator->fails()) {

                // session()->flash('warning', 'Credentials does not match with new password');
                session()->forget('success');
                session()->forget('warning');

                return redirect()->back()->withInput()->withErrors($validator);
            }
            $row = new User();
            $row->name = $request->name;
            $row->role_id = $request->role_id;
            $row->restaurant_id = $request->restaurant_id;
            $row->wa_department_id = $request->wa_department_id;


            $role  = Role::with('permissions')->find($request->role_id);
            $list = $role->permissions;
            $role_modules = [];
            foreach ($list as $data) {
                $role_modules[$data->module_name] = $data->module_name;
            }


            $row->wa_unit_of_measures_id = $request->wa_unit_of_measures_id;
            $row->wa_location_and_store_id = $request->wa_location_and_store_id;
            $row->phone_number = $request->phone_number;
            $row->badge_number = $request->badge_number;
            $row->id_number = $request->id_number;
            $row->email = $request->email;
            $row->complementary_number = $request->complementary_number;
            $row->complementary_amount = $request->complementary_amount ? $request->complementary_amount : 0;
            $row->max_discount_percent = $request->max_discount_percent ? $request->max_discount_percent : 0;
            $row->password = bcrypt($request->password);
            $row->status = '1';
            $row->date_employeed = $request->date_employeed;
            $row->category_id = @$request->category_id;
            $row->upload_data = @$request->upload_data ? 1 : 0;
            $row->drop_limit = $request->drop_limit?? 50000;

            if ($request->file('image')) {
                // Define correct absolute paths using base_path(), which points to public_html
                $main_dir = base_path('uploads/users');
                $thumb_dir = base_path('uploads/users/thumb');

                // Create directories if they don't exist
                if (!\Illuminate\Support\Facades\File::isDirectory($main_dir)) {
                    \Illuminate\Support\Facades\File::makeDirectory($main_dir, 0775, true, true);
                }
                if (!\Illuminate\Support\Facades\File::isDirectory($thumb_dir)) {
                    \Illuminate\Support\Facades\File::makeDirectory($thumb_dir, 0775, true, true);
                }

                $file = $request->file('image');
                $image_name = time() . rand(111111111, 999999999) . '.' . $file->getClientOriginalExtension();

                // Create and save a resized thumbnail
                $thumb_image = \Intervention\Image\Facades\Image::make($file->getRealPath())->resize(341, null, function ($constraint) {
                    $constraint->aspectRatio();
                });

                // Move the original file and save the thumbnail
                $file->move($main_dir, $image_name);
                $thumb_image->save($thumb_dir . '/' . $image_name);

                $row->image = $image_name;
            }
            if ($request->file('e_sign_image')) {
                $file = $request->file('e_sign_image');
                $e_sign_image = uploadwithresize($file, 'users', '341');
                $row->e_sign_image = $e_sign_image;
            }

            $row->save();

            if (isset($request->permission) && count($request->permission) > 0) {
                foreach ($request->permission as $key => $value) {
                    $array[] = [
                        'user_id' => $row->id,
                        'module' => $value,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                if (count($array) > 0) {
                    \App\Model\UserAppPermissions::insert($array);
                }
            }

            foreach (($request->route ?? []) as $routeId) {
                //check if Route has active shift
                $openShift = SalesmanShift::where([
                    'status' => 'open',
                    'route_id' => $routeId
                ])->first();

                if (!$openShift) {
                    $row->routes()->attach($routeId);
                }
            }

            $customerMessage = "Dear " . $request->name . ",you have been created as a user in KHEL Bizwiz platform. Your login credentials are: Username/Email:" . $request->email . " Password: " . $request->password . " link: https://bizwizkaniniharaka.com";
            try {
                session()->forget('warning');
                session()->forget('success');
                $this->smsService->sendMessage($customerMessage, $request->phone_number);
            } catch (\Throwable $e) {
            }

            session()->forget('warning');
            session()->forget('success');
            session()->flash('success', 'User  Created  successfully.');
            return redirect()->route($this->model . '.index');
        } catch (\Exception $e) {
            $msg = $e->getMessage();

            return redirect()->back()->withInput()->withErrors(['errors' => $msg]);
        }
    }


    public function show($id)
    {
    }


    public function edit($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row = User::whereSlug($slug)->first();
                $selected_bin = $row->wa_unit_of_measures_id;
                if ($row) {
                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;
                    $restroList = $this->getRestaurantList();
                    $categoryList = WaCategory::pluck('title', 'id');

                    $row->route_ids = $row->routes->pluck('id');
                    return view('admin.users.edit', compact('title', 'model', 'breadcum', 'row', 'restroList', 'categoryList', 'selected_bin'));
                } else {
                    session()->forget('success');
                    session()->forget('warning');
                    session()->flash('warning', 'Invalid Request');
                    return redirect()->back();
                }
            } else {
                session()->forget('success');
                session()->forget('warning');
                session()->flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            session()->forget('success');
            session()->forget('warning');
            session()->flash('warning', $msg);
            return redirect()->back();
        }
    }


    public function update(Request $request, $slug)
    {
        $row = User::whereSlug($slug)->first();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:255',
                'role_id' => 'required',
                'restaurant_id' => 'required|exists:restaurants,id',
                'phone_number' => ['required', 'digits:10'],
                'id_number' => 'required',
                'image_update' => 'mimes:jpeg,jpg,png',
                'e_sign_image' => 'mimes:jpeg,jpg,png',
            ], [], [
                'restaurant_id.required' => 'Branch/Restaurant is required',
                'restaurant_id.exists' => 'Selected Branch/Restaurant does not exist'
            ]);
            if ($validator->fails()) {
                session()->forget('success');
                session()->forget('warning');
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {

                 $role  = Role::with('permissions')->find($request->role_id);
                 $list = $role->permissions;
                 $role_modules = [];
                 foreach ($list as $data) {
                     $role_modules[$data->module_name] = $data->module_name;
                 }
                 if (array_key_exists("pos-cash-sales", $role_modules)) {
                     $is_cashier = true;
                 } else {
                     $is_cashier = false;
                 }

                 if ($is_cashier && !$request->drop_limit && $role->id != 169)
                 {
                     return redirect()->back()->withInput()->withErrors([
                         'Role has Pos Cashier Capabilities. Needs  drop Limit Set'
                     ]);
                 }

                $previous_row = $row;
                $row->name = $request->name;
                $row->role_id = $request->role_id;
                $row->restaurant_id = $request->restaurant_id;
                $row->phone_number = $request->phone_number;
                $row->wa_unit_of_measures_id = $request->wa_unit_of_measures_id;
                // $row->badge_number=$request->badge_number;
                $row->id_number = $request->id_number;
                $row->email = $request->email;
                $row->date_employeed = $request->date_employeed;
                $row->wa_department_id = $request->wa_department_id;
                $row->wa_location_and_store_id = $request->wa_location_and_store_id;

                $row->route = @$request->route;
                $row->category_id = @$request->category_id;
                $row->upload_data = @$request->upload_data ? 1 : 0;
                $row->drop_limit = $request->drop_limit;

                //   $row->max_discount_percent=$request->max_discount_percent;
                if ($request->file('image_update')) {
                    // Define correct absolute paths using base_path(), which points to public_html
                    $main_dir = base_path('uploads/users');
                    $thumb_dir = base_path('uploads/users/thumb');

                    // Create directories if they don't exist
                    if (!\Illuminate\Support\Facades\File::isDirectory($main_dir)) {
                        \Illuminate\Support\Facades\File::makeDirectory($main_dir, 0775, true, true);
                    }
                    if (!\Illuminate\Support\Facades\File::isDirectory($thumb_dir)) {
                        \Illuminate\Support\Facades\File::makeDirectory($thumb_dir, 0775, true, true);
                    }

                    $file = $request->file('image_update');
                    $image_name = time() . rand(111111111, 999999999) . '.' . $file->getClientOriginalExtension();

                    // Create and save a resized thumbnail
                    $thumb_image = \Intervention\Image\Facades\Image::make($file->getRealPath())->resize(341, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });

                    // Move the original file and save the thumbnail
                    $file->move($main_dir, $image_name);
                    $thumb_image->save($thumb_dir . '/' . $image_name);

                    // Delete old image if it exists, using the correct absolute paths
                    if ($previous_row->image) {
                        if (\Illuminate\Support\Facades\File::exists($main_dir . '/' . $previous_row->image)) {
                            \Illuminate\Support\Facades\File::delete($main_dir . '/' . $previous_row->image);
                        }
                        if (\Illuminate\Support\Facades\File::exists($thumb_dir . '/' . $previous_row->image)) {
                            \Illuminate\Support\Facades\File::delete($thumb_dir . '/' . $previous_row->image);
                        }
                    }
                    $row->image = $image_name;
                }
                if ($request->file('e_sign_image')) {
                    $file = $request->file('e_sign_image');
                    $e_sign_image = uploadwithresize($file, 'users', '341');
                    if ($row->e_sign_image) {
                        unlinkfile('users', $row->e_sign_image);
                    }
                    $row->e_sign_image = $e_sign_image;
                }
                $row->save();

                $userRouteIds = $row->routes->pluck('id');
                foreach ($userRouteIds as $userRouteId) {
                    // Check if user has active shift for route
                    $userOpenShift = SalesmanShift::where([
                        'status' => 'open',
                        'route_id' => $userRouteId,
                        'salesman_id' => $row->id
                    ])->first();
                    if (!$userOpenShift) {
                        $row->routes()->detach($userRouteId);
                    }
                }

                if ($request->route) {
                    foreach ($request->route as $routeId) {
                        //check if Route has active shift
                        $openShift = SalesmanShift::where([
                            'status' => 'open',
                            'route_id' => $routeId
                        ])->first();

                        // attach routes to user if they do not have active shifts
                        if (!$openShift) {
                            $row->routes()->attach($routeId);
                        }
                    }
                }
                session()->forget('success');
                session()->forget('warning');
                session()->flash('success', 'Record updated successfully.');
                return redirect()->route($this->model . '.index');
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            session()->forget('success');
            session()->forget('warning');
            session()->flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }


    public function destroy($slug)
    {

        try {
            $row = User::whereSlug($slug)->first();
            if (count($row->stock_moves) > 0 || count($row->debtor_tran) > 0) {
                session()->forget('success');
                session()->forget('warning');
                session()->flash('warning', 'Restricted : User cannott be deleted');
                return redirect()->back();
            }
            $can_delete = true;
            if ($row->role_id == '105') {
                $can_delete = getRepresentativeDeleteStatus($row->id);
            }
            if ($can_delete == true) {

                $havependingexternalReq = isHaveAnyPendingExternalRequisition($row->id);
                $havependingepurchaseorderpermission = isHaveAnyPendingPurchaseOrderPermission($row->id);


                if ($havependingexternalReq == false) {

                    if ($havependingepurchaseorderpermission == false) {
                        User::whereSlug($slug)->delete();
                        if ($row->image) {
                            unlinkfile('users', $row->image);
                        }

                        session()->forget('warning');
                        session()->forget('success');
                        session()->flash('success', 'Deleted successfully.');
                        return redirect()->back();
                    } else {
                        session()->forget('success');
                        session()->forget('warning');
                        session()->flash('warning', 'Can not delete due to some pending purchase order approval');
                        return redirect()->back();
                    }
                } else {
                    session()->forget('success');
                    session()->forget('warning');
                    session()->flash('warning', 'Can not delete due to some pending external requisition approval');
                    return redirect()->back();
                }
            } else {
                Session::flash('warning', 'Representative have some pending deliveries.');
                return redirect()->back();
            }
        } catch (\Exception $e) {
            dd($e);

            session()->forget('success');
            session()->forget('warning');
            session()->flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function changeStatus($slug, $status)
    {
        try {
            $row = User::whereSlug($slug)->first();
            if ($row) {
                $row->status = $status == '1' ? '0' : '1';
                $row->save();
                session()->forget('success');
                session()->forget('warning');
                session()->flash('success', 'Status update successfully');
                return redirect()->route($this->model . '.index');
            } else {
                session()->forget('success');
                session()->forget('warning');
                session()->flash('warning', 'Invalid request');
                return redirect()->route($this->model . '.index');
            }
        } catch (Exception $ex) {
            session()->forget('success');
            session()->forget('warning');
            session()->flash('warning', 'Invalid request');
            return redirect()->route($this->model . '.index');
        }
    }


    public function datatablesGetUsers(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = 'app-users';

        $columns = array(
            0 => 'id',
            1 => 'name',
            2 => 'phone_number',
            3 => 'nationality',
            4 => 'created_at',
            5 => 'wallet_balance',
            6 => 'action',

        );

        $totalData = User::whereIn('role_id', ['11'])->count();
        $totalFiltered = $totalData;
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if (empty($request->input('search.value'))) {
            $posts = User::whereIn('role_id', ['11'])
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');
            $posts = User::whereIn('role_id', ['11'])
                ->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%{$search}%")
                        ->orWhere('created_at', 'LIKE', "%{$search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$search}%")
                        ->orWhere('nationality', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                })
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
            $totalFiltered = User::whereIn('role_id', ['11'])
                ->where(function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%{$search}%")
                        ->orWhere('created_at', 'LIKE', "%{$search}%")
                        ->orWhere('phone_number', 'LIKE', "%{$search}%")
                        ->orWhere('nationality', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                })
                ->count();
        }


        $data = array();
        if (!empty($posts)) {
            foreach ($posts as $list) {

                $nestedData['id'] = $list->id;
                $nestedData['created_at'] = date('Y-m-d H:i', strtotime($list->created_at));
                $nestedData['name'] = ucfirst($list->name);
                $nestedData['phone_number'] = $list->phone_number;
                $nestedData['nationality'] = strtoupper($list->nationality);
                $nestedData['wallet_balance'] = $this->getWalletBalanceByPhoneNumber($list->phone_number);
                $nestedData['action'] = '<span><a title="Show Detail" href="' . route('users.show', $list->slug) . '"><i class="fa fa-eye" aria-hidden="true"></i>
                                                    </a>
                                                    </span>';


                if (isset($permission[$pmodule . '___addAmountToWallet']) || $permission == 'superadmin') {
                    $nestedData['action'] .= '<span><a title="Add Amount To Wallet" href="' . route('users.get.add.amount.to.wallet', $list->slug) . '"><i class="fa fa-plus" aria-hidden="true"></i>
                        </a>
                        </span>';
                }


                $data[] = $nestedData;
            }
        }

        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );
        echo json_encode($json_data);
    }

    public function getAddWalletAmountFrom($slug)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = 'app-users';
        if (isset($permission[$pmodule . '___addAmountToWallet']) || $permission == 'superadmin') {

            $row = User::whereSlug($slug)->first();
            if ($row) {
                $title = 'Add Amount To Wallet';
                $model = 'users';
                $payment_mode = $this->top_up_type;
                $breadcum = [$title => route($model . '.index'), 'Add Amount To wallet' => '', $row->phone_number => ''];
                return view('admin.customers.walletAddAmount', compact('title', 'model', 'breadcum', 'row', 'payment_mode'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function setAmountToWallet(Request $request, $slug)
    {
        try {
            $user = User::whereSlug($slug)->first();
            if ($user) {
                $row = new WalletTransaction();
                $row->phone_number = $user->phone_number;
                $row->entry_type = $request->entry_type;
                $row->amount = $request->amount;
                $row->user_id = $user->id;
                $row->refrence_description = $request->refrence_description ? $request->refrence_description : null;
                $row->save();
                $this->updateWalletAmount($user->phone_number);
                Session::flash('success', 'Amount added successfully.');
                return redirect()->route('users.index');
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }


    public function usersIndex()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = 'app-users';
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $title = 'Users';
            $model = 'users';
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.customers.index', compact('title', 'model', 'breadcum'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function usersShow($slug)
    {
        try {
            $row = User::whereSlug($slug)->first();
            if ($row) {
                $model = 'users';
                $title = 'View User';
                $breadcum = ['Users' => route('users.index'), 'Show' => ''];
                return view('admin.customers.show', compact('title', 'breadcum', 'row', 'model'));
            } else {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }


    public function registeredResponse(Request $request)
    {
        $item = MpesaTransactionDetail::where('TransID', $request->TransID)->first();
        if (!$item) {
            return response()->json(['status' => false, 'message' => "No item found"]);
            return false;
        }
        $item->TransactionType = @$request->TransactionType;
        $item->TransTime = @$request->TransTime;
        $item->TransAmount = @$request->TransAmount;
        $item->BusinessShortCode = @$request->BusinessShortCode;
        $item->BillRefNumber = @$request->BillRefNumber;
        $item->InvoiceNumber = @$request->InvoiceNumber;
        $item->OrgAccountBalance = @$request->OrgAccountBalance;
        $item->ThirdPartyTransID = @$request->ThirdPartyTransID;
        $item->MSISDN = @$request->MSISDN;
        $item->FirstName = @$request->FirstName;
        $item->MiddleName = @$request->MiddleName;
        $item->LastName = @$request->LastName;
        $item->save();
    }


    public function mpesaCallBackold()
    {


        $response_xml = trim(file_get_contents('php://input'));


        $this->save_txt($response_xml, 'callback_' . date('YmdHis'));

        $rawInput = json_decode($response_xml, true);
        $ResultCode = $rawInput['Body']['stkCallback']['ResultCode'];
        $description = $rawInput['Body']['stkCallback']['ResultDesc'];
        $merchantTransId = $rawInput['Body']['stkCallback']['MerchantRequestID'];

        if ($ResultCode == 0) {

            //$this->save_txt( $response_xml, 'EasyFI_response_json_success'.date('YmdHis') );

            foreach ($rawInput['Body']['stkCallback']['CallbackMetadata']['Item'] as $key => $value) {

                if ($value['Name'] == 'Amount') {
                    $TransAmount = $value['Value'];
                }
                if ($value['Name'] == 'MpesaReceiptNumber') {

                    $TransID = $value['Value'];
                }
                if ($value['Name'] == 'TransactionDate') {
                    $TransTime = $value['Value'];
                }
                if ($value['Name'] == 'PhoneNumber') {
                    $MSISDN = $value['Value'];
                }
                //	var_dump( $value );
            }

            $date = date("Y-m-d H:i:s", strtotime($TransTime));
            //return $date;
            $mpesa = Models\MpesaTransaction::where('merchant_transaction_id', $merchantTransId)->first();

            $is_old_mpesa = $mpesa->TransID ? true : false;

            \Log::debug('Confirm - is_old_mpesa ' . ($is_old_mpesa ? 'yes' : 'no'));


            $mpesa->TransID = $TransID;
            $mpesa->TransTime = $date;
            $mpesa->datetime = $date;
            $mpesa->TransAmount = $TransAmount;
            $mpesa->responsecode = $ResultCode;
            $mpesa->MSISDN = $MSISDN;
            $mpesa->description = $description;
            $mpesa->active = 1;
            $mpesa->save();


            // add to wallet
            $user = Models\User::where('phone', 'like', '%' . substr($mpesa->MSISDN, -9))
                ->orWhere('phone', 'like', '%' . $mpesa->UserRequestNumber)
                ->first();


            \Log::debug('Confirm User ' . ($user ? $user->phone : 'no'));

            if (!$is_old_mpesa && $user) {
                $user->load('wallet');

                $user->wallet->add($mpesa->TransAmount, 'payment', $mpesa->id);
            }
        } else {
            $this->save_txt($response_xml, 'Fail_Easy_' . date('YmdHis'));
            $mpesa = Models\MpesaTransaction::where('merchant_transaction_id', $merchantTransId)->first();

            $mpesa->responsecode = $ResultCode;
            $mpesa->description = $description;

            $mpesa->save();
        }
    }

    public function mpesacallback()
    {

        //return response()->json(['status'=>true,'message'=>'Your Order is Accepted.']);

        //my code start from here
        try {
            $rawPostData = file_get_contents('php://input');
            $rawInput = json_decode($rawPostData, true);
            //echo "<pre>"; print_r($rawInput); die;
            $ResultCode = $rawInput['Body']['stkCallback']['ResultCode'];
            $description = $rawInput['Body']['stkCallback']['ResultDesc'];
            $merchantTransId = $rawInput['Body']['stkCallback']['MerchantRequestID'];
            $CheckoutRequestID = $rawInput['Body']['stkCallback']['CheckoutRequestID'];
            if ($ResultCode == 0) {

                //$this->save_txt( $response_xml, 'EasyFI_response_json_success'.date('YmdHis') );

                foreach ($rawInput['Body']['stkCallback']['CallbackMetadata']['Item'] as $key => $value) {

                    if ($value['Name'] == 'Amount') {
                        $TransAmount = $value['Value'];
                    }
                    if ($value['Name'] == 'MpesaReceiptNumber') {

                        $TransID = $value['Value'];
                    }
                    if ($value['Name'] == 'TransactionDate') {
                        $TransTime = $value['Value'];
                    }
                    if ($value['Name'] == 'PhoneNumber') {
                        $MSISDN = $value['Value'];
                    }
                    //	var_dump( $value );
                }
                $date = date("Y-m-d H:i:s", strtotime($TransTime));
                //return $date;
                $mpesa = new MpesaTransactionDetail();
                $mpesa->details = $rawPostData;
                $mpesa->merchant_transaction_id = $merchantTransId;
                $mpesa->mpesa_request_id = $CheckoutRequestID;
                $mpesa->TransID = $TransID;
                $mpesa->TransTime = $date;
                $mpesa->TransAmount = $TransAmount;
                $mpesa->responsecode = $ResultCode;
                $mpesa->MSISDN = $MSISDN;
                $mpesa->is_done = '1';

                $mpesa->TransactionType = @$rawInput['Body']['stkCallback']['TransactionType'];
                $mpesa->BusinessShortCode = @$rawInput['Body']['stkCallback']['BusinessShortCode'];
                $mpesa->BillRefNumber = @$rawInput['Body']['stkCallback']['BillRefNumber'];
                $mpesa->InvoiceNumber = @$rawInput['Body']['stkCallback']['InvoiceNumber'];

                $mpesa->save();
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            return $msg;
        }

        //my code ended here

    }

    public function cardPeymentCallback(Request $request)
    {
        // my code start from here for adding order by credit or debit card
        try {
            $jsonData = $request;


            if ($jsonData->has('result')) {
                $path = 'resources/views/checkoutjson.blade.php';
                $bytes_written = File::put($path, $jsonData->input('result'));
            }


            if ($jsonData->has('result') && $jsonData->has('ECOM_CONSUMERORDERID')) {
                $merchant_refrence = $jsonData->input('ECOM_CONSUMERORDERID');
                $jsondataFromfirbase = Firebase::get('card_payment_' . $merchant_refrence);
                $data = json_decode($jsondataFromfirbase);
                if ($data) {
                    $request = $data;
                    if (strtoupper($jsonData->input('result')) == 'SUCCESS') {
                        $order_default_status = 'NEW_ORDER';
                        $new_order = new Order();
                        $new_order->user_id = $request->user_id;
                        $new_order->restaurant_id = $request->restaurant_id;
                        $new_order->final_comment = isset($request->final_comment) ? $request->final_comment : '';
                        $new_order->order_final_price = '0';
                        if (isset($request->total_price)) {
                            $new_order->order_final_price = $request->total_price;
                        }
                        if (isset($request->Total_price)) {
                            $new_order->order_final_price = $request->Total_price;
                        }

                        $new_order->slug = rand(99, 999) . strtotime(date('Y-m-d h:i:s'));
                        $new_order->order_type = 'PREPAID';
                        $new_order->payment_mode = 'CARD';
                        $new_order->transaction_id = $merchant_refrence;
                        $new_order->complimentry_code = null;
                        $new_order->save();
                        $order_id = $new_order->id;
                        $json = json_decode($request->checkout_json);
                        //book the table for related order start
                        if (isset($json->table_info) && isset($json->table_info->Table_id) && isset($json->table_info->total_guests)) {
                            $this->bookedTheTableForRelatedOrder($json->table_info->Table_id, $json->table_info->total_guests, $order_id, $order_default_status);
                            $new_order->total_guests = $json->table_info->total_guests;
                            $new_order->save();
                        }

                        //store extra charges for related order start
                        if (isset($json->order_charges)) {
                            $new_order->order_charges = json_encode($json->order_charges);
                            $new_order->save();
                        }
                        //store extra charges for related order end

                        //store discounts for related order start
                        if (isset($json->order_discounts)) {
                            $new_order->order_discounts = json_encode($json->order_discounts);
                            $new_order->save();
                        }
                        //store discounts for related order end

                        //store general items  start

                        if (isset($json->Appetizerdata) && count($json->Appetizerdata) > 0) {
                            $this->storeGeneralItemForOrder($json->Appetizerdata, $order_id, $new_order->restaurant_id, $order_default_status);
                        }
                        //store general item end

                        //store offer items  start

                        if (isset($json->offerdata) && count($json->offerdata) > 0) {
                            $this->storeOfferItemForOrder($json->offerdata, $order_id, $new_order->restaurant_id);
                        }
                        $this->manageLoyalityPoint($new_order, $order_default_status);
                        //store offer item end

                        //manage multiple payment records
                        $receipt_id = $this->makeReceipt([$order_id], $request->user_id);
                        $this->managepaymentSummary($receipt_id, $new_order);

                        $my_receipt = OrderReceipt::whereId($receipt_id)->first();
                        $this->managetimeForall($receipt_id, $my_receipt->created_at);
                        Firebase::update('card_payment_' . $merchant_refrence, [
                            'order_default_status' => 'CONFIRMED',
                            'order_id' => $new_order->id,
                            'pending_order_count' => $this->getPendingorderCount($request->user_id),
                            'loyalty_points' => $this->getLoyaltyPointsByUserId($request->user_id),
                            'receipt_id' => $receipt_id

                        ]);
                    } else {
                        //not success
                        Firebase::update('card_payment_' . $merchant_refrence, ['order_default_status' => 'CANCLED']);
                    }
                }


                $jsondataFromfirbaseForWallet = Firebase::get('card_wallet_' . $merchant_refrence);
                $wallet_data = json_decode($jsondataFromfirbaseForWallet);
                if ($wallet_data) {
                    $request = $wallet_data;
                    if (strtoupper($jsonData->input('result')) == 'SUCCESS') {

                        $walletTransaction = new WalletTransaction();
                        $walletTransaction->refrence_description = $merchant_refrence;
                        $walletTransaction->phone_number = $request->phone_number;
                        $walletTransaction->entry_type = 'Card Top Up';
                        $walletTransaction->amount = $request->amount;
                        $walletTransaction->user_id = $request->user_id;
                        $walletTransaction->save();
                        $this->updateWalletAmount($request->phone_number);
                        Firebase::update('card_wallet_' . $merchant_refrence, ['status' => 'CONFIRMED']);
                    } else {
                        //not success
                        Firebase::update('card_wallet_' . $merchant_refrence, ['status' => 'CANCLED']);
                    }
                }
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $path = 'resources/views/checkoutjson.blade.php';
            $bytes_written = File::put($path, $msg);
        }
    }

    public function assign_branches(Request $request)
    {
        $user = User::with(['branches'])->where('id', $request->id)->first();
        $assigned = $user->branches->pluck('id')->toArray();
        $branches = Restaurant::get();
        return view('admin.users.assign_branches', compact('user', 'branches', 'assigned'));
    }

    public function assign_branches_post(Request $request)
    {
        $rules = array(
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|array',
            'branch_id.*' => 'required|exists:restaurants,id',
        );
        $validator = Validator::make($request->all(), $rules, [], [
            'branch_id' => 'Branch',
            'branch_id.*' => 'Branch'
        ]);
        if ($validator->fails()) {
            return response()->json(['result' => 0, 'errors' => $validator->errors()]);
        }
        $user = User::where('id', $request->user_id)->first();
        $branches = [];
        DB::table('user_branches')->where('user_id', $user->id)->delete();
        if (isset($request->branch_id) && count($request->branch_id)) {
            foreach ($request->branch_id as $key => $value) {
                $branches[] = [
                    'user_id' => $user->id,
                    'restaurant_id' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            if (count($branches) > 0) {
                DB::table('user_branches')->insert($branches);
                return response()->json(['result' => 1, 'message' => 'Branches Assigned Successfully']);
            }
        }

        return response()->json(['result' => -1, 'message' => 'Something went wrong']);
    }

    public function assignUserPermission(Request $request)
    {
        $permission = userAppPermissions();
        $checkpermission = implode(',', $permission);
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'permission' => 'nullable|array',
            'permission.*' => 'in:' . $checkpermission
        ], [], [
            'permission.*' => 'Permission'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validator->errors()
            ]);
        }
        $check = DB::transaction(function () use ($permission, $request) {
            $user = User::where('id', $request->user_id)->first();
            \App\Model\UserAppPermissions::where('user_id', $user->id)->delete();
            $array = [];
            foreach ($request->permission as $key => $value) {
                $array[] = [
                    'user_id' => $user->id,
                    'module' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
            if (count($array) > 0) {
                \App\Model\UserAppPermissions::insert($array);
            }
            return true;
        });
        if ($check) {
            return $request->ajax() ? response()->json([
                'result' => 1,
                'message' => 'Permissions Saved Successfully',
                'location' => route('employees.index')
            ]) : redirect()->back()->with('success', 'Permissions Saved Successfully');
        }
        return $request->ajax() ? response()->json([
            'result' => -1,
            'messsege' => 'Something went wrong'
        ]) : redirect()->back()->with('success', 'something went wrong');
    }

    public function getAllUsers(): JsonResponse
    {
        try {
            $users = User::where('role_id', '!=', 1)->get();
            return $this->jsonify(['data' => $users], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function get_user_suppliers(Request $request)
    {
        try {
            $data = WaUserSupplier::with(['supplier'])->where('user_id', $request->id)->get();
            return $this->jsonify(['data' => $data], 200);
        } catch (\Throwable $th) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function assign_user_suppliers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'supplier' => 'nullable|array',
            'supplier.*' => 'sometimes|nullable|exists:wa_suppliers,id'
        ], [], [
            'supplier.*' => 'Supplier'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validator->errors()
            ]);
        }
        try {
            $check = DB::transaction(function () use ($request) {
                $user = User::where('id', $request->user_id)->first();
                WaUserSupplier::where('user_id', $request->user_id)->delete();
                if (isset($request->supplier)) {
                    $array = [];
                    foreach ($request->supplier as $key => $value) {
                        $array[] = [
                            'user_id' => $user->id,
                            'wa_supplier_id' => $value,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                    if (count($array) > 0) {
                        WaUserSupplier::insert($array);
                    }
                }
                return true;
            });
            if ($check) {
                return $request->ajax() ? response()->json([
                    'result' => 1,
                    'message' => 'Suppliers added Successfully',
                    'location' => route('employees.index')
                ]) : redirect()->back()->with('success', 'Suppliers added Successfully');
            }
            return $request->ajax() ? response()->json([
                'result' => -1,
                'messsege' => 'Something went wrong'
            ]) : redirect()->back()->with('success', 'something went wrong');
        } catch (\Throwable $e) {
            return $request->ajax() ? response()->json([
                'result' => -1,
                'messsege' => $e->getMessage()
            ]) : redirect()->back()->with('success', $e->getMessage());
        }
    }

    public function usersByBranch($branchId)
    {
        $users = User::where('restaurant_id', $branchId)->where('status', '1')->get();
        
        return response()->json($users);
    }
}
