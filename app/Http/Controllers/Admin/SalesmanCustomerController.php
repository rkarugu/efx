<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessShopImage;
use App\Model\WaRouteCustomer;
use App\Model\WaCustomer;
use App\Model\Route;
use App\Model\DeliveryCentres;
use App\SalesmanShift;
use App\Interfaces\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SalesmanCustomerController extends Controller
{
    protected $model;
    protected $title;
    protected $pmodule;
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->model = 'salesman-customers';
        $this->title = 'Customer Management';
        $this->pmodule = 'salesman-customers';
        $this->smsService = $smsService;
    }

    /**
     * Display customer management interface
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            Session::flash('error', 'Access denied. This section is for salesmen only.');
            return redirect()->back();
        }

        $title = 'Customer Management';
        $model = $this->model;

        $routeCustomers = collect();
        $userRoute = $this->resolveUserRouteId($user);
        $routeInfo = $userRoute ? Route::find($userRoute) : null;

        if ($userRoute) {
            $routeCustomers = WaRouteCustomer::where('route_id', $userRoute)
                ->whereNull('deleted_at')
                ->whereIn('status', ['approved', 'verified', 'duplicate', 'unverified'])
                ->with(['center'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Get delivery centers for the route
        $deliveryCenters = DeliveryCentres::where('is_active', 1)
            ->orderBy('name')
            ->get();

        $breadcum = [$title => '', 'Customer Management' => ''];
        
        return view('admin.salesman_customers.index', compact(
            'title', 'model', 'breadcum', 'routeCustomers', 'deliveryCenters', 'user', 'routeInfo', 'userRoute'
        ));
    }

    /**
     * Store a new customer
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'phone_no' => 'required|numeric|digits_between:9,12',
            'business_name' => 'required|string|min:1|max:200',
            'town' => 'required|string|min:1|max:200',
            'contact_person' => 'nullable|string|min:1|max:200',
            'center_id' => 'required|exists:delivery_centres,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'kra_pin' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'customer_type' => 'nullable|string|max:50',
            'secondary_name' => 'nullable|string|max:200',
            'secondary_phone_no' => 'nullable|numeric|digits_between:9,12',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        $customerWithPhoneNumber = WaRouteCustomer::latest()
            ->where('phone', $request->phone_no)
            ->whereIn('status', ['verified', 'approved', 'duplicate'])
            ->first();

        $status = $customerWithPhoneNumber ? 'duplicate' : 'verified';

        DB::beginTransaction();
        try {
            $userRoute = $this->resolveUserRouteId($user);
            if (!$userRoute) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Route not found']);
            }
            
            $route = Route::find($userRoute);
            if (!$route) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Route not found']);
            }

            // Get or create WaCustomer for the route
            $customer = WaCustomer::where("route_id", $route->id)->first();
            if (!$customer) {
                $customer = new WaCustomer();
                $customer->customer_code = getCodeWithNumberSeries('CUSTOMERS');
                $customer->customer_name = $route->route_name;
                $customer->credit_limit = 0;
                $customer->route_id = $route->id;
                $customer->save();

                updateUniqueNumberSeries('CUSTOMERS', $customer->customer_code);
            }

            // Create route customer
            $routeCustomer = new WaRouteCustomer();
            $routeCustomer->created_by = $user->id;
            $routeCustomer->route_id = $userRoute;
            $routeCustomer->delivery_centres_id = $request->center_id;
            $routeCustomer->customer_id = $customer->id;
            $routeCustomer->kra_pin = $request->kra_pin;
            $routeCustomer->name = strtoupper($request->name);
            $routeCustomer->phone = $request->phone_no;
            $routeCustomer->bussiness_name = strtoupper($request->business_name);
            $routeCustomer->town = $request->town;
            $routeCustomer->contact_person = $request->contact_person;
            $routeCustomer->lat = $request->latitude;
            $routeCustomer->lng = $request->longitude;
            $routeCustomer->gender = $request->gender;
            $routeCustomer->comment = $request->comment;
            $routeCustomer->status = $status;
            $routeCustomer->secondary_name = $request->secondary_name;
            $routeCustomer->secondary_phone_no = $request->secondary_phone_no;
            $routeCustomer->customer_type = $request->customer_type;
            $routeCustomer->save();

            if ($request->file('photo')) {
                $file = $request->file('photo');
                $fileName = time() . rand(111111111, 9999999999) . '.' . $file->getClientOriginalExtension();
                $filePath = public_path('uploads/shops/') . $fileName;
                $file->move(public_path('uploads/shops/'), $fileName);
                ProcessShopImage::dispatch($filePath, $routeCustomer->id);
            }

            // Send welcome SMS
            try {
                $customerMessage = "Greetings! Your shop has been on-boarded with Kanini Haraka enterprise. We are looking forward to more business with you.";
                $this->smsService->sendMessage($customerMessage, $routeCustomer->phone);
            } catch (\Throwable $e) {
                // SMS sending failed, but don't fail the whole operation
            }

            DB::commit();

            return response()->json([
                'success' => true, 
                'message' => $status === 'duplicate' ? 'Customer created as duplicate and awaiting approval' : 'Customer added successfully',
                'customer_id' => $routeCustomer->id,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Error adding customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Update customer information
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $userRoute = $this->resolveUserRouteId($user);
        $customer = WaRouteCustomer::where('route_id', $userRoute)
            ->where('id', $id)
            ->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'phone_no' => 'required|numeric|digits_between:9,12',
            'business_name' => 'required|string|min:1|max:200',
            'town' => 'required|string|min:1|max:200',
            'contact_person' => 'nullable|string|min:1|max:200',
            'center_id' => 'required|exists:delivery_centres,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'kra_pin' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female',
            'customer_type' => 'nullable|string|max:50',
            'secondary_name' => 'nullable|string|max:200',
            'secondary_phone_no' => 'nullable|numeric|digits_between:9,12',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        // Check for duplicate phone number (excluding current customer)
        $duplicateCustomer = WaRouteCustomer::where('phone', $request->phone_no)
            ->where('id', '!=', $id)
            ->whereIn('status', ['verified', 'approved', 'duplicate'])
            ->first();

        if ($duplicateCustomer) {
            return response()->json([
                'success' => false, 
                'message' => 'Another customer with this phone number already exists'
            ]);
        }

        try {
            $customer->name = strtoupper($request->name);
            $customer->phone = $request->phone_no;
            $customer->bussiness_name = strtoupper($request->business_name);
            $customer->town = $request->town;
            $customer->contact_person = $request->contact_person;
            $customer->delivery_centres_id = $request->center_id;
            $customer->lat = $request->latitude;
            $customer->lng = $request->longitude;
            $customer->kra_pin = $request->kra_pin;
            $customer->gender = $request->gender;
            $customer->comment = $request->comment;
            $customer->secondary_name = $request->secondary_name;
            $customer->secondary_phone_no = $request->secondary_phone_no;
            $customer->customer_type = $request->customer_type;
            $customer->save();

            return response()->json([
                'success' => true, 
                'message' => 'Customer updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Get customer details for editing
     */
    public function show($id)
    {
        $user = Auth::user();
        
        if (!$this->isSalesman($user)) {
            return response()->json(['success' => false, 'message' => 'Access denied']);
        }

        $userRoute = $this->resolveUserRouteId($user);
        $customer = WaRouteCustomer::where('route_id', $userRoute)
            ->where('id', $id)
            ->with(['center'])
            ->first();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found']);
        }

        return response()->json([
            'success' => true, 
            'customer' => $customer
        ]);
    }

    /**
     * Get delivery centers for AJAX
     */
    public function getDeliveryCenters()
    {
        $centers = DeliveryCentres::where('is_active', 1)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json(['centers' => $centers]);
    }

    /**
     * Check if user is a salesman
     */
    private function isSalesman($user)
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

    private function resolveUserRouteId($user): ?int
    {
        if (!$user) {
            return null;
        }

        try {
            if (!empty($user->route)) {
                return (int) $user->route;
            }
        } catch (\Throwable $e) {
        }

        try {
            if (!empty($user->getroute)) {
                return (int) $user->getroute->id;
            }
        } catch (\Throwable $e) {
        }

        try {
            if (method_exists($user, 'routes') && $user->routes()->exists()) {
                return (int) $user->routes()->first()->id;
            }
        } catch (\Throwable $e) {
        }

        try {
            $recentShift = SalesmanShift::where('salesman_id', $user->id)
                ->latest()
                ->first();
            if ($recentShift && $recentShift->route_id) {
                return (int) $recentShift->route_id;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }
}
