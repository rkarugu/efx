<?php

namespace App\Http\Controllers\Admin;

use App\DeliverySchedule;
use App\Http\Controllers\Controller;
use App\Model\User;
use App\Model\VehicleType;
use App\Vehicle;
use App\UserLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Model\Restaurant;
class VehicleController extends Controller
{
    protected $model;
    protected $base_route;
    protected $resource_folder;
    protected $base_title;

    public function __construct()
    {
        $this->model = 'vehicles';
        $this->base_route = 'vehicles';
        $this->resource_folder = 'admin.vehicles';
        $this->base_title = 'My Fleet';
    }
   

    public function index()
    {
        $title = $this->base_title;
        $model = $this->model;
        $pmodule = "vehicles-overview";
        $permission = $this->mypermissionsforAModule();
        $branches = Restaurant::all();

        if (isset($permission[$pmodule . '___listing']) || $permission == 'superadmin') {
            $breadcum = [$this->base_title => route("$this->base_route.index"), 'Listing' => ''];
            $base_route = $this->base_route;

            return view("$this->resource_folder.index", compact('title', 'model', 'breadcum', 'base_route', 'branches'));
        } else {
            return redirect()->back()->withErrors(['error' => pageRestrictedMessage()]);
        }
    }

    public function show($id)
    {
        $title = $this->base_title;
        $model = $this->model;
        $pmodule = "vehicles-overview";
        $permission = $this->mypermissionsforAModule();

        if (isset($permission[$pmodule . '___listing']) || $permission == 'superadmin') {
            $breadcum = [$this->base_title => route("$this->base_route.index"), 'Details' => ''];
            $base_route = $this->base_route;

            $vehicle = Vehicle::with(['type', 'driver', 'model'])->find($id);
            $vehicle->acquisition_date = Carbon::parse($vehicle->acquisition_date)->toFormattedDateString();
            $currentSchedule = DeliverySchedule::with('shift')->latest()->active()->forVehicle($vehicle->id)->first();
            $vehicle->current_schedule = $currentSchedule;
            $vehicle->display_travel_expense = format_amount_with_currency($vehicle->travel_expense);
            $vehicle->typeName = VehicleType::find($vehicle->model->vehicle_type_id)->value('name');

            $googleMapsApiKey = config('app.google_maps_api_key');
            return view("$this->resource_folder.show", compact('title', 'model', 'breadcum', 'base_route', 'vehicle', 'googleMapsApiKey'));
        } else {
            return redirect()->back()->withErrors(['error' => pageRestrictedMessage()]);
        }
    }

    public function getAvailableVehicles(Request $request): JsonResponse
    {
        try {
            $vehicles = Vehicle::whereHas('driver')
                ->where('branch_id', $request->user_restaurant_id)
                ->get()
                ->filter(function (Vehicle $vehicle) {
                    $activeSchedule = DeliverySchedule::latest()->active()->where('vehicle_id', $vehicle->id)->first();
                    return !$activeSchedule;
                });

            return $this->jsonify(['data' => $vehicles], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function getAllVehicles(Request $request): JsonResponse
    {
        try {
            $vehicles = Vehicle::with(['driver', 'model', 'turnboy'])->where('branch_id', $request->branch_id)
                ->get()
                ->map(function (Vehicle $vehicle) {
                    $vehicle->acquisition_date = Carbon::parse($vehicle->acquisition_date)->toFormattedDateString();
                    $vehicle->turnboyName = $vehicle->turnboy?->name;
                    return $vehicle;
                });
            return $this->jsonify(['data' => $vehicles], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function getAvailableDrivers(Request $request): JsonResponse
    {
        try {
            $query = User::select('id', 'name', 'role_id', 'restaurant_id')
                ->doesntHave('vehicle')
                ->where('role_id', 6);

            // Filter by branch if provided
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('restaurant_id', $request->branch_id);
            }

            $users = $query->get();

            return $this->jsonify(['data' => $users], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }
    public function getAvailableTurnboys(Request $request): JsonResponse
    {
        try {
            $query = User::select('id', 'name', 'role_id', 'restaurant_id')
                ->doesntHave('vehicle')
                ->where('role_id', 184);

            // Filter by branch if provided
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('restaurant_id', $request->branch_id);
            }

            $users = $query->get();

            return $this->jsonify(['data' => $users], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }

    }

    public function assignDriver(Request $request): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($request->vehicle_id);
            $vehicle->update(['driver_id' => $request->driver_id]);

            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }
    public function assignTurnboy(Request $request): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($request->vehicle_id);
            $vehicle->update(['turn_boy_id' => $request->driver_id]);

            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }

    }

    public function unAssignDriver(Request $request): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($request->vehicle_id);
            $activeSchedule = DeliverySchedule::latest()->active()->where('vehicle_id', $vehicle->id)->first();
//            if ($activeSchedule) {
//                return $this->jsonify(['message' => 'This driver is currently on a delivery shift.'], 422);
//            }

            $vehicle->update(['driver_id' => null]);
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }
    public function unAssignTurnboy(Request $request): JsonResponse
    {
        try {
            $vehicle = Vehicle::find($request->vehicle_id);
            $activeSchedule = DeliverySchedule::latest()->active()->where('vehicle_id', $vehicle->id)->first();
//            if ($activeSchedule) {
//                return $this->jsonify(['message' => 'This driver is currently on a delivery shift.'], 422);
//            }

            $vehicle->update(['turn_boy_id' => null]);
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }

    }


    public function create()
    {
        $title = "Add Vehicle";
        $model = $this->model;
        $pmodule = "vehicles-overview";
        $permission = $this->mypermissionsforAModule();

        if (isset($permission[$pmodule . '___add']) || $permission == 'superadmin') {
            $breadcum = [$this->base_title => route("$this->base_route.index"), $title => ''];
            $base_route = $this->base_route;

            return view("$this->resource_folder.create", compact('title', 'model', 'breadcum', 'base_route'));
        } else {
            return redirect()->back()->withErrors(['error' => pageRestrictedMessage()]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::create([
                'branch_id' => $request->branch_id,
                'license_plate_number' => $request->license_plate_number,
                'vin' => $request->vin,
                'vehicle_model_id' => $request->vehicle_type_id,
                'name' => $request->name,
                'color' => $request->color,
                'unladen_weight' => $request->unladen_weight,
                'max_load_capacity' => $request->max_load_capacity,
                'axle_count' => $request->axle_count,
                'tyre_count' => $request->tyre_count,
                'acquisition_date' => $request->acquisition_date,
                'acquisition_price' => $request->acquisition_price,
                'travel_expense' => $request->travel_expense,
                'primary_responsibility' => $request->primary_responsibility,
            ]);

            if ($request->device_name) {
                $adjustment = (float)$request->onboarding_mileage - $request->system_mileage;
                $vehicle->update([
                    'device_name' => $request->device_name,
                    'onboarding_mileage' => $request->onboarding_mileage,
                    'onboarding_mileage_date' => Carbon::now(),
                    'odometer_adjustment' => $adjustment,
                    'onboarding_fuel' => $request->onboarding_fuel,
                    'onboarding_fuel_date' => Carbon::now(),
                    'sim_card_number' => $request->sim_card_number,
                    'fuel_tank_capacity' => $request->fuel_tank_capacity,
                ]);
            }

            DB::commit();
            return $this->jsonify(['data' => $vehicle], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function saveServiceDetails(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $payload = json_decode($request->payload, true);
            $vehicle = Vehicle::find($payload['vehicle_id']);
            foreach ($payload['intervals'] as $interval) {
                $serviceInterval = $vehicle->serviceIntervals()->create([
                    'name' => $interval['name'],
                    'mileage' => $interval['mileage'],
                    'last_service_mileage' => 0, // redundant
                ]);

                if (isset($payload['last_service']) && ($serviceInterval->name == $payload['last_service']['name'])) {
                    $vehicle->update([
                        'last_service_interval_id' => $serviceInterval->id,
                        'last_service_mileage' => $payload['last_service']['mileage'],
                        'last_service_date' => $payload['last_service']['last_service_date']
                    ]);
                }
            }

            DB::commit();
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function saveInsuranceDetails(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::find($request->vehicle_id);
            $vehicle->insuranceRecords()->create([
                'insurer' => $request->insurer,
                'type' => $request->type,
                'insurance_amount' => $request->insurance_amount,
                'insurance_period' => $request->insurance_period,
                'insurance_date' => $request->insurance_date,
            ]);

            DB::commit();
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        if (!can('add', 'vehicles-overview')) {
            return redirect()->back()->withErrors(['error' => pageRestrictedMessage()]);
        }

        $vehicle = Vehicle::find($id);
        $vehicle->acquisition_date_string = \Carbon\Carbon::parse($vehicle->acquisition_date)->toDateString();
        $title = "$vehicle->license_plate_number | Edit Vehicle";
        $model = $this->model;
        $breadcum = [
            $this->base_title => route("$this->base_route.index"),
            $vehicle->license_plate_number => route("$this->base_route.show", $vehicle->id),
            'Edit' => ''
        ];

        $base_route = $this->base_route;
        return view("$this->resource_folder.edit", compact('title', 'model', 'breadcum', 'base_route', 'vehicle'));
    }

    public function update(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::find($request->id);
            $vehicle->update([
                'branch_id' => $request->branch_id,
                'license_plate_number' => $request->license_plate_number,
                'vin' => $request->vin,
                'vehicle_model_id' => $request->vehicle_type_id,
                'name' => $request->name,
                'color' => $request->color,
                'unladen_weight' => $request->unladen_weight,
                'max_load_capacity' => $request->max_load_capacity,
                'axle_count' => $request->axle_count,
                'tyre_count' => $request->tyre_count,
                'acquisition_date' => $request->acquisition_date,
                'acquisition_price' => $request->acquisition_price,
                'travel_expense' => $request->travel_expense,
                'primary_responsibility' => $request->primary_responsibility,

            ]);

            if ($request->device_name) {
                $adjustment = (float)$request->onboarding_mileage - $request->system_mileage;
                $vehicle->update([
                    'device_name' => $request->device_name,
                    'onboarding_mileage' => $request->onboarding_mileage,
                    'onboarding_mileage_date' => Carbon::now(),
                    'odometer_adjustment' => $adjustment,
                    'onboarding_fuel' => $request->onboarding_fuel,
                    'onboarding_fuel_date' => Carbon::now(),
                    'sim_card_number' => $request->sim_card_number,
                    'fuel_tank_capacity' => $request->fuel_tank_capacity,
                ]);
            }

            DB::commit();
            return $this->jsonify(['data' => $vehicle], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function switchOff(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::find($request->vehicle_id);

            //TODO: Switch off
            $vehicle->update([
                'switch_off_status' => 'off',
            ]);

            DB::commit();
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function switchOn(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $vehicle = Vehicle::find($request->vehicle_id);

            $vehicle->update([
                'switch_off_status' => 'on',
            ]);

            DB::commit();
            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function vehiclesList()
    {
        return response()->json(Vehicle::orderBy('license_plate_number')->select('id', 'license_plate_number')->get());
    }
}
