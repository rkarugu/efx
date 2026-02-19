<?php

namespace App\Http\Controllers\StoreKeeper;

use Carbon\Carbon;
use App\Model\User;
use App\SalesmanShift;
use App\ParkingListItem;
use App\DeliverySchedule;
use App\Model\Restaurant;
use Illuminate\Http\Request;
use App\Model\WaInventoryItem;
use App\Model\WaUnitOfMeasure;
use App\ParkingListItemDispatch;
use App\Model\WaLocationAndStore;
use Illuminate\Http\JsonResponse;
use App\Models\StockBreakDispatch;
use Illuminate\Support\Facades\DB;
use App\SalesmanShiftStoreDispatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Model\WaInternalRequisition;
use Illuminate\Http\RedirectResponse;
use App\SalesmanShiftStoreDispatchItem;
use Illuminate\Support\Facades\Session;
use App\Model\WaInternalRequisitionItem;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Validator;

class ParkingListController extends Controller
{
    public function index()
    {

        $title = "Store Loading Sheets";
        $model = "parking-lists";
        $permission = $this->mypermissionsforAModule();

        $user = getLoggeduserProfile();
       
        if (isset($permission['store-loading-sheet___view-undispatched']) || $user->role_id == 1 ) {
            $shifts = SalesmanShift::latest()->withWhereHas('dispatches', function ($query) {
                $query->where('dispatched', false);
            })->with(['salesman', 'salesman_route'])->get()->map(function (SalesmanShift $shift) {
                $shift->date = Carbon::parse($shift->created_at)->format('Y-m-d');
                $shift->branch = Restaurant::find($shift->salesman_route?->restaurant_id)?->name;

                return $shift;
            });

            return view('admin.store_loading_sheets.admin_index', compact('title', 'model', 'shifts'));
        }

        if (isset($permission['store-loading-sheet___view']) || $permission == 'superadmin') {
            $loadingSheets = SalesmanShiftStoreDispatch::with(['items', 'shift', 'shift.salesman', 'shift.salesman_route'])->where('dispatched', false);
            if ($user->role_id != 1 ) {
                if (!$user->wa_unit_of_measures_id) {
                    return redirect()->back()->withErrors(['message' => 'You have not been allocated a Bin Location']);
                }

                $loadingSheets = $loadingSheets->where('bin_location_id', $user->wa_unit_of_measures_id);
            }

            $loadingSheets = $loadingSheets->orderBy('created_at', 'DESC')->get()->map(function (SalesmanShiftStoreDispatch $sheet) {
                $sheet->bin_location_name = (DB::table('wa_unit_of_measures')->select('id', 'title')->where('id', $sheet->bin_location_id)->first())?->title;
                $sheet->branch = (DB::table('restaurants')->select('id', 'name')->where('id', $sheet->shift?->salesman_route?->restaurant_id)->first())?->name;

                $sheet->items = $sheet->items->map(function ($item) {
                    $item->item_name = (DB::table('wa_inventory_items')->where('id', $item->wa_inventory_item_id)->first())?->title;
                    return $item;
                });

                return $sheet;
            });

            $bin = DB::table('wa_unit_of_measures')->select('id', 'title')->where('id', $user->wa_unit_of_measures_id)->first();
            return view('admin.store_loading_sheets.index', compact('title', 'model', 'loadingSheets', 'bin'));
        } 
        else {
            Session::flash('warning', 'Access Denied');
            return redirect()->back();
        }
    }

    public function dispatchLoadingSheet($id)
    {
        $title = "Store Loading Sheets";
        $model = "parking-lists";
        $permission = $this->mypermissionsforAModule();

        if (isset($permission['store-loading-sheet___view']) || $permission == 'superadmin') {
            $user = getLoggeduserProfile();
            $loadingSheets = SalesmanShiftStoreDispatch::with(['items', 'shift', 'shift.salesman', 'shift.salesman_route'])->where('dispatched', false);
            if ($user->role_id != 1) {
                if (!$user->wa_unit_of_measures_id) {
                    return redirect()->back()->withErrors(['message' => 'You have not been allocated a Bin Location']);
                }

                $loadingSheets = $loadingSheets->where('bin_location_id', $user->wa_unit_of_measures_id);
            }
            $activeLoadingSheet = $loadingSheets->where('id', $id)->get()->map(function (SalesmanShiftStoreDispatch $sheet) {
                $sheet->bin_location_name = (DB::table('wa_unit_of_measures')->select('id', 'title')->where('id', $sheet->bin_location_id)->first())?->title;
                $sheet->branch = (DB::table('restaurants')->select('id', 'name')->where('id', $sheet->shift?->salesman_route?->restaurant_id)->first())?->name;

                $sheet->items = $sheet->items->map(function ($item) {
                    $item->item_name = (DB::table('wa_inventory_items')->where('id', $item->wa_inventory_item_id)->first())?->title;
                    return $item;
                });

                return $sheet;
            });

            $loadingSheets = $loadingSheets->orderBy('created_at', 'DESC')->get()->map(function (SalesmanShiftStoreDispatch $sheet) {
                $sheet->bin_location_name = (DB::table('wa_unit_of_measures')->select('id', 'title')->where('id', $sheet->bin_location_id)->first())?->title;
                $sheet->branch = (DB::table('restaurants')->select('id', 'name')->where('id', $sheet->shift?->salesman_route?->restaurant_id)->first())?->name;

                $sheet->items = $sheet->items->map(function ($item) {
                    $item->item_name = (DB::table('wa_inventory_items')->where('id', $item->wa_inventory_item_id)->first())?->title;
                    return $item;
                });

                return $sheet;
            });


            $bin = DB::table('wa_unit_of_measures')->select('id', 'title')->where('id', $user->wa_unit_of_measures_id)->first();
            return view('admin.store_loading_sheets.process_loading_sheet', compact('title', 'model', 'loadingSheets', 'bin', 'id', 'activeLoadingSheet'));
        } else {
            Session::flash('warning', 'Access Denied');
            return redirect()->back();
        }
    }


    public function processDispatch(Request $request): JsonResponse
    {
        // dd($request->all());
        $user = User::find($request->user_id);

        $uom = $user->uom;

        if ($uom->is_display) {
            $autoBreaks = DB::table('route_auto_breaks')
                ->where('route_auto_breaks.status', 'pending')
                ->whereDate('route_auto_breaks.created_at', '<', Carbon::today());
            
            if ($user->role_id == 152) {
                $autoBreaks = $autoBreaks->where('route_auto_breaks.child_bin_id', $user->wa_unit_of_measures_id);
            }
    
            $autoBreaks = $autoBreaks->join('wa_inventory_items as child', 'route_auto_breaks.child_item_id', '=', 'child.id')
                ->join('wa_inventory_items as mother', 'route_auto_breaks.mother_item_id', '=', 'mother.id')
                ->join('wa_unit_of_measures as child_bin', 'route_auto_breaks.child_bin_id', '=', 'child_bin.id')
                ->join('wa_unit_of_measures as mother_bin', 'route_auto_breaks.mother_bin_id', '=', 'mother_bin.id')
                ->groupBy('route_auto_breaks.child_item_id')
                ->orderBy('route_auto_breaks.child_bin_id')
                ->count();
    
            if ($autoBreaks) {
                return response()->json([
                    'message' => 'Cannot process when there are pending auto breaks'
                ], 422);
            }

            $unreceivedDispatches = StockBreakDispatch::where('stock_break_dispatches.dispatched', true)
                ->where('received', false)
                ->whereDate('stock_break_dispatches.created_at', '<', Carbon::today());

            if ($user->role_id == 152) {
                $unreceivedDispatches = $unreceivedDispatches->where(function ($query) use ($user) {
                    $query->where('stock_break_dispatches.child_bin_id', $user->wa_unit_of_measures_id)->orWhere('stock_break_dispatches.dispatched_by', $user->id);
                });
            }

            $unreceivedDispatches = $unreceivedDispatches->join('wa_unit_of_measures as child_bin', 'stock_break_dispatches.child_bin_id', '=', 'child_bin.id')
                ->join('wa_unit_of_measures as mother_bin', 'stock_break_dispatches.mother_bin_id', '=', 'mother_bin.id')
                ->join('users as initiator', 'stock_break_dispatches.initiated_by', '=', 'initiator.id')
                ->orderBy('stock_break_dispatches.created_at', 'DESC')
                ->count();

            if ($unreceivedDispatches) {
                return response()->json([
                    'message' => 'Cannot process when there are unreceived dispatches'
                ], 422);
            }

        } else {
            $pendingDispatches = StockBreakDispatch::where('stock_break_dispatches.dispatched', false)
                ->whereDate('stock_break_dispatches.created_at', '<', Carbon::today());
            
            if ($user->role_id == 152) {
                $pendingDispatches = $pendingDispatches->where(function ($query) use ($user) {
                    $query->where('stock_break_dispatches.mother_bin_id', $user->wa_unit_of_measures_id)->orWhere('stock_break_dispatches.initiated_by', $user->id);
                });
            }
    
            $pendingDispatches = $pendingDispatches->join('wa_unit_of_measures as child_bin', 'stock_break_dispatches.child_bin_id', '=', 'child_bin.id')
                ->join('wa_unit_of_measures as mother_bin', 'stock_break_dispatches.mother_bin_id', '=', 'mother_bin.id')
                ->join('users as initiator', 'stock_break_dispatches.initiated_by', '=', 'initiator.id')
                ->orderBy('stock_break_dispatches.created_at', 'DESC')
                ->count();
    
            if ($pendingDispatches) {
                return response()->json([
                    'message' => 'Cannot process when there are pending dispatches'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $payload = json_decode($request->payload, true);
            $dispatch = SalesmanShiftStoreDispatch::latest()->with('items')->where('shift_id', $payload['shift_id'])->where('store_id', $payload['store_id'])
                ->where('bin_location_id', $payload['bin_location_id'])->where('id', $payload['id'])
                ->first();

            $dispatch->update([
                'dispatched' => true,
                'dispatch_time' => Carbon::now(),
                'dispatcher_id' => $request->user_id
            ]);

            foreach ($payload['items'] as $item) {
                $dispatchItem = SalesmanShiftStoreDispatchItem::find($item['id']);
                $dispatchItem->update(['dispatched_quantity' => $item['qty_received']]);
            }

            DB::commit();

            $nonDispatchedSheets = SalesmanShiftStoreDispatch::latest()->where('shift_id', $payload['shift_id'])->where('dispatched', false)->get();
            if (count($nonDispatchedSheets) == 0) {
                $schedule = DeliverySchedule::latest()->forShiftId((int)$payload['shift_id'])->first();
                $schedule?->update(['status' => 'consolidated']);
            }

            return $this->jsonify([], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function getDispatched(Request $request): View|RedirectResponse
    {
        $title = "Dispatched Loading Sheets";
        $model = "parking-lists";
        $permission = $this->mypermissionsforAModule();
        $bins = WaUnitOfMeasure::all();
        $selectedBinId = $request->bin;


        if (isset($permission['dispatched-loading-sheets___view']) || $permission == 'superadmin') {
            $user = getLoggeduserProfile();
            $loadingSheets = SalesmanShiftStoreDispatch::with(['items', 'shift', 'shift.salesman', 'shift.salesman_route', 'bin', 'dispatcher'])->where('dispatched', true);
            if ($user->role_id != 1) {
                // TODO: Filter by branch
            }

            //filter by dates
            if($request->bin){
                $loadingSheets = $loadingSheets->where('bin_location_id', $request->bin);  
            }
            if($request->date){
                $date = \Carbon\Carbon::parse($request->date)->toDateString();
                $loadingSheets = $loadingSheets->whereDate('created_at', $date);
            }else{
                $todaysDate = \Carbon\Carbon::now()->toDateString();
                $loadingSheets = $loadingSheets->whereDate('created_at', $todaysDate);
             }
            
            if($user->role_id != 1 && !isset($permission['dispatched-loading-sheets___view-all'])){
                $loadingSheets = $loadingSheets->where('bin_location_id', $user->wa_unit_of_measures_id);
            }
            
            $loadingSheets = $loadingSheets->orderBy('created_at', 'DESC')->get()->map(function (SalesmanShiftStoreDispatch $sheet) {
                // $sheet->bin_location_name = (DB::table('wa_unit_of_measures')->select('id', 'title')->where('id', $sheet->bin_location_id)->first())?->title;
                $sheet->branch = (DB::table('restaurants')->select('id', 'name')->where('id', $sheet->shift?->salesman_route?->restaurant_id)->first())?->name;
                $sheet->items = $sheet->items->map(function ($item) {
                    $item->item_name = (DB::table('wa_inventory_items')->where('id', $item->wa_inventory_item_id)->first())?->title;
                    return $item;
                });

                $sheet->unfulfilled_items = $sheet->items->filter(function ($item) {
                    return $item->dispatched_quantity < $item->total_quantity;
                });

                return $sheet;
            });

            return view('admin.store_loading_sheets.dispatched', compact('title', 'model', 'loadingSheets', 'selectedBinId', 'bins', 'user'));
        } else {
            Session::flash('warning', 'Access Denied');
            return redirect()->back();
        }
    }

    public function getDispatchedItems($id): View|RedirectResponse
    {
        $title = "Dispatched Loading Sheets";
        $model = "parking-lists";
        $permission = $this->mypermissionsforAModule();

        if (isset($permission['dispatched-loading-sheets___view']) || $permission == 'superadmin') {
            $user = getLoggeduserProfile();
            $loadingSheet = SalesmanShiftStoreDispatch::find($id);
          
            $dispatchItems = SalesmanShiftStoreDispatchItem::with(['item'])->where('dispatch_id', $id)->get();


            return view('admin.store_loading_sheets.dispatched_details', compact('title', 'model', 'loadingSheet','dispatchItems'));
        } else {
            Session::flash('warning', 'Access Denied');
            return redirect()->back();
        }
    }





    public function shiftLoadingSheets($id)
    {
        $title = "Store Loading Sheets";
        $model = "parking-lists";

        $shift = SalesmanShift::with(['dispatches' => function ($query) {
            $query->where('dispatched', false);
        }, 'dispatches.items', 'salesman_route'])->find($id);
        $shift->date = Carbon::parse($shift->created_at)->format('Y-m-d');
        $shift->dispatches = $shift->dispatches->map(function ($dispatch) {
            $dispatch->bin = WaUnitOfMeasure::find($dispatch->bin_location_id)->title;
            $dispatch->bin_manager = User::where('wa_unit_of_measures_id', $dispatch->bin_location_id)->where('role_id', 152)->first()?->name ?? 'Unassigned';

            return $dispatch;
        });

        return view('admin.store_loading_sheets.admin_shift_dispatches', compact('title', 'model', 'shift'));
    }

    public function loadingSheetItems($id)
    {
        $title = "Store Loading Sheet Items";
        $model = "parking-lists";

        $dispatch = SalesmanShiftStoreDispatch::with('items')->find($id);
        $shift = SalesmanShift::with('salesman_route')->find($dispatch->shift_id);
        $shift->date = Carbon::parse($shift->created_at)->format('Y-m-d');
        $dispatch->dispatch_items = $dispatch->items->map(function ($item) {
            $item->inventory_item = WaInventoryItem::select('id', 'stock_id_code', 'title')->find($item->wa_inventory_item_id);

            return $item;
        });

        return view('admin.store_loading_sheets.dispatch_items', compact('title', 'model', 'shift', 'dispatch'));
    }
}
