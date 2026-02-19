<?php

namespace App\Http\Controllers\Admin;

use App\Model\Route;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WaRouteCustomer;
use App\Model\WaCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;
use Maatwebsite\Excel\Facades\Excel;

class RouteCustomerController extends Controller

{
    protected $pmodule;
    protected $model;
    protected $title;

    public function __construct()
    {
        $this->default_customer = "CUST-00001";
        $this->model = 'wa-route-customer';
        $this->title = 'Route Customers';
        $this->pmodule = 'wa-route-customer';

    }

    /**
     * @throws Exception
     */
    public function get_parent()
    {
        $parent = WaCustomer::where('customer_code', $this->default_customer)->first();
        if (!$parent) {
            throw new Exception("Customer Account Not Found! Please contact administration department");
        }
        return $parent;
    }

    public function dropdown(Request $request)
    {
        try {
            $parent = $this->get_parent();
            $searchTerm = $request->q;
            $page = $request->page ?? 1;
            $perPage = 30; // Show 30 customers per page
            
            $route = Route::where('is_pos_route', true)->where('restaurant_id', Auth::user()->restaurant_id)->first();

            $query = WaRouteCustomer::where('customer_id', $parent->id)
                ->where('status', '!=', 'duplicate')
                ->where('route_id', $route->id);
            
            // Apply search filter if search term provided
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%$searchTerm%")
                      ->orWhere('phone', 'like', "%$searchTerm%");
                });
            }
            
            // Get total count for pagination
            $totalCount = $query->count();
            
            // Apply pagination
            $results = $query->select(
                    'id',
                    DB::raw('CONCAT(name, " --- ", phone) AS title'),
                    'phone'
                )
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->orderBy('name', 'asc')
                ->get();
            
            return response()->json([
                'results' => $results,
                'total_count' => $totalCount
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'result' => -1,
                'message' => $th->getMessage(),
                'trace' => $th->getTrace(),
            ]);
        }

    }

    public function create()
    {
        try {
            $this->get_parent();
            return response()->json([
                'result' => 1,
                'message' => "Rendered",
                'data' => view('admin.route_customers.modal_create')->render()
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'result' => -1,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $parent = $this->get_parent();
            $validation = \Validator::make($request->all(), [
                'telephone' => ['required', 'regex:/^((\+254|254|0)[71]\d{8})$/'],
                'f_name' => 'required|string|max:200',
                'l_name' => 'required|string|max:200',
                'pin' => [
                    'sometimes',
                    'nullable',
                    'regex:/^[AP]\d{9}[A-Z]$/',
                ],
            ],[
                'f_name.required' => 'First Name is required',
                'l_name.required' => 'Last Name is required',
                'telephone.regex' => 'This Must be a Valid Phone Number',
            ]);
            if ($validation->fails()) {
                return response()->json([
                    'result' => 0,
                    'errors' => $validation->errors()
                ]);
            }

            /*check if Phone number Exists*/

            $message  = "Received and Inserted";
            if (!$request->duplicate)
            {
                $cust = WaRouteCustomer::where('phone', $request->telephone)->first();
                if ($cust)
                {
                    return response()->json([
                        'result' => 3,
                        'message' => 'Customer With same Phone Number Exits.'
                    ]);
                }

            }

            $getLoggeduserProfile = getLoggeduserProfile();

            $route = Route::where('is_pos_route', true)->where('restaurant_id', Auth::user()->restaurant_id)->first();
// dd($route);
            $route_c = new WaRouteCustomer();
            $route_c->created_by = $getLoggeduserProfile->id;
            $route_c->route_id = $route? $route->id : null;
            $route_c->customer_id = $parent->id;
            $route_c->name = $request->f_name .' '. $request->l_name;
            $route_c->phone = $request->telephone;
            $route_c->bussiness_name = $request->f_name .' '. $request->l_name;
            $route_c->town = $parent->town;
            $route_c->kra_pin = $request->pin ?? null;
            $route_c->status = $request->duplicate? 'duplicate': 'approved';
            $route_c->save();
            return response()->json([
                'result' => 1,
                'message' => $request->duplicate ? 'Customer Created Awaiting Approval':'"Received and Inserted"',
                'data' => $route_c
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'result' => -1,
                'message' => $th->getMessage()
            ]);
        }
    }


}