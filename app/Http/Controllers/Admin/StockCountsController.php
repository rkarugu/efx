<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use PDF;
use Session;
use Illuminate\Support\Facades\Validator;
use App\Model\WaStockCheckFreeze;
use App\Model\WaStockCheckFreezeItem;
use App\Model\WaInventoryItem;
use App\Model\WaStockCount;
use App\Model\StockAdjustment;
use App\Model\WaNumerSeriesCode;
use App\Model\WaAccountingPeriod;
use App\Model\WaStockMove;
use App\Model\WaGlTran;
use App\Model\WaInventoryCategory;
use App\Model\WaLocationAndStore;
use App\Model\WaStockCountDeviation;
use App\Model\WaStockCountProcess;
use App\Model\WaUnitOfMeasure;
use App\Models\StockTakeUserAssignment;
use App\Models\StockTakeUserAssignmentAssignee;
use App\Models\WaStockCountVariation;
use Carbon\Carbon;
use Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\DisplayBinUserItemAllocation;

class StockCountsController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'stock-counts';
        $this->title = 'Stock Counts';
        $this->pmodule = 'stock-counts';
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000000);
        set_time_limit(30000000); // Extends to 5 minutes.
    }

    public function index()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $user = Auth::user();
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            if (isset($user->role_id) && $user->role_id == 152) {
                $lists = WaStockCount::where('uom', $user->wa_unit_of_measures_id)->with([
                        'getAssociateItemDetail' => function ($query) {
                            $query->where('status', 1);
                        },
                        'getAssociateLocationDetail',
                        'category'
                    ])->get();
            } else {
                $lists = WaStockCount::with([
                    'getAssociateItemDetail' => function ($query) {
                        $query->where('status', 1);
                    },
                    'getAssociateLocationDetail',
                    'category'
                ])->get();
            }
            $breadcum = [$title => route('admin.stock-counts'), 'Listing' => ''];
            return view('admin.stock_counts.index', compact('lists', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    /**
     * Mobile Web Interface for Stock Take Testing
     * Allows testing decimal quantity support without mobile app
     */
    public function mobileWeb()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = 'Mobile Stock Take - Web';
        $model = $this->model;
        $user = Auth::user();
        
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            // Check if user is a Store Keeper (role_id 152, 169, 170, 181)
            $isStoreKeeper = in_array($user->role_id, [152, 169, 170, 181]);
            
            // Pre-select values for Store Keepers
            $preSelectedStore = null;
            $preSelectedBin = null;
            
            if ($isStoreKeeper) {
                // Store Keeper: Only show their store and bin
                $stores = WaLocationAndStore::select('id', 'location_name')
                    ->where('id', $user->wa_location_and_store_id)
                    ->get();
                $bins = WaUnitOfMeasure::select('id', 'title')
                    ->where('id', $user->wa_unit_of_measures_id)
                    ->get();
                $preSelectedStore = $user->wa_location_and_store_id;
                $preSelectedBin = $user->wa_unit_of_measures_id;
            } else {
                // Admin: Show all stores and bins
                $stores = WaLocationAndStore::select('id', 'location_name')->get();
                $bins = WaUnitOfMeasure::select('id', 'title')->get();
            }
            
            // All users can see all categories
            $categories = WaInventoryCategory::select('id', 'category_description')->get();
            
            $breadcum = ['Stock Counts' => route('admin.stock-counts'), 'Mobile Web' => ''];
            return view('admin.stock_counts.mobile_web', compact(
                'title', 'model', 'breadcum', 'pmodule', 'permission', 
                'stores', 'bins', 'categories', 
                'preSelectedStore', 'preSelectedBin', 'isStoreKeeper'
            ));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    /**
     * Get stock take items for mobile web (session-based, no API token needed)
     * Implements role-based access: Store Keepers and Assigned Users
     */
    public function getMobileWebItems(Request $request)
    {
        $user = Auth::user();
        $store = $request->store;
        $bin = $request->bin;
        
        if (!$store || !$bin) {
            return response()->json(['status' => false, 'message' => 'Store and Bin are required']);
        }

        // Check if there's a frozen stock take for this store
        $freezeExists = WaStockCheckFreeze::where('wa_location_and_store_id', $store)->exists();
        if (!$freezeExists) {
            return response()->json([
                'status' => false, 
                'message' => 'No stock take has been frozen for this store. Please create a stock take first at /admin/stock-takes/create-stock-take-sheet'
            ]);
        }

        // Get already counted items to exclude
        $counts = WaStockCount::where('wa_location_and_store_id', $store)->pluck('wa_inventory_item_id')->toArray();
        $dd = array_unique($counts);
        
        \Log::info("Stock Take Load Request", [
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'store' => $store,
            'bin' => $bin,
            'already_counted' => count($dd)
        ]);

        // Check if user is a Store Keeper with item allocation (role_id 169, 170, 181)
        if (in_array($user->role_id, [169, 170, 181])) {
            // Store Keeper with allocation: Only show items allocated to them
            $userItems = DisplayBinUserItemAllocation::where('user_id', $user->id)->pluck('wa_inventory_item_id')->toArray();
            
            if (empty($userItems)) {
                return response()->json(['status' => false, 'message' => 'No items allocated to you. Please contact admin.']);
            }

            $items = WaStockCheckFreezeItem::select(
                    'wa_inventory_items.id as id',
                    'wa_inventory_items.stock_id_code as stock_id_code',
                    'wa_inventory_items.title as title',
                    'wa_inventory_items.wa_inventory_category_id as category_id',
                    'wa_stock_check_freeze_items.quantity_on_hand as system_qoh',
                    'wa_stock_check_freeze_items.id as freeze_id'
                )
                ->join('wa_inventory_items', 'wa_inventory_items.id', 'wa_stock_check_freeze_items.wa_inventory_item_id')
                ->where('wa_stock_check_freeze_items.wa_location_and_store_id', $store)
                ->where('wa_stock_check_freeze_items.wa_unit_of_measure', $bin)
                ->whereIn('wa_inventory_items.id', $userItems) // Only allocated items
                ->whereNotIn('wa_inventory_items.id', $dd)
                ->orderBy('wa_stock_check_freeze_items.quantity_on_hand', 'desc')
                ->get()
                ->unique('id'); // Remove duplicates by item id

            return response()->json([
                'status' => true, 
                'items' => $items, 
                'count' => $items->count(),
                'user_type' => 'Store Keeper (Allocated)',
                'message' => 'Showing only your allocated items'
            ]);
        }
        
        // Check if user is a regular Store Keeper (role_id 152) - sees all items in their store/bin
        if ($user->role_id == 152) {
            $items = WaStockCheckFreezeItem::select(
                    'wa_inventory_items.id as id',
                    'wa_inventory_items.stock_id_code as stock_id_code',
                    'wa_inventory_items.title as title',
                    'wa_inventory_items.wa_inventory_category_id as category_id',
                    'wa_stock_check_freeze_items.quantity_on_hand as system_qoh',
                    'wa_stock_check_freeze_items.id as freeze_id'
                )
                ->join('wa_inventory_items', 'wa_inventory_items.id', 'wa_stock_check_freeze_items.wa_inventory_item_id')
                ->where('wa_stock_check_freeze_items.wa_location_and_store_id', $store)
                ->where('wa_stock_check_freeze_items.wa_unit_of_measure', $bin)
                ->whereNotIn('wa_inventory_items.id', $dd)
                ->orderBy('wa_inventory_items.title', 'asc')
                ->get()
                ->unique('id'); // Remove duplicates by item id

            return response()->json([
                'status' => true, 
                'items' => $items, 
                'count' => $items->count(),
                'user_type' => 'Store Keeper',
                'message' => 'Showing all items in your store/bin'
            ]);
        }

        // Check if user has assignment for today
        $today = Carbon::now()->toDateString();
        $assignment = StockTakeUserAssignment::latest()
            ->whereHas('assistant', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereDate('stock_take_date', $today)->first();

        if ($assignment) {
            // Assigned User: Show items based on assignment
            $categoryIds = explode(',', $assignment->category_ids);
            
            $items = WaStockCheckFreezeItem::select(
                    'wa_inventory_items.id as id',
                    'wa_inventory_items.stock_id_code as stock_id_code',
                    'wa_inventory_items.title as title',
                    'wa_inventory_items.wa_inventory_category_id as category_id',
                    'wa_stock_check_freeze_items.quantity_on_hand as system_qoh',
                    'wa_stock_check_freeze_items.id as freeze_id'
                )
                ->join('wa_inventory_items', 'wa_inventory_items.id', 'wa_stock_check_freeze_items.wa_inventory_item_id')
                ->where('wa_stock_check_freeze_items.wa_location_and_store_id', $store)
                ->where('wa_stock_check_freeze_items.wa_unit_of_measure', $bin)
                ->whereIn('wa_inventory_items.wa_inventory_category_id', $categoryIds) // Assignment categories
                ->whereNotIn('wa_inventory_items.id', $dd)
                ->orderBy('wa_inventory_items.title', 'asc')
                ->get()
                ->unique('id'); // Remove duplicates by item id

            return response()->json([
                'status' => true, 
                'items' => $items, 
                'count' => $items->count(),
                'user_type' => 'Assigned User',
                'assignment_date' => $assignment->stock_take_date,
                'message' => 'Showing items from your assignment'
            ]);
        }

        // Super Admin or users without restrictions: Show all items
        $items = WaStockCheckFreezeItem::select(
                'wa_inventory_items.id as id',
                'wa_inventory_items.stock_id_code as stock_id_code',
                'wa_inventory_items.title as title',
                'wa_inventory_items.wa_inventory_category_id as category_id',
                'wa_stock_check_freeze_items.quantity_on_hand as system_qoh',
                'wa_stock_check_freeze_items.id as freeze_id'
            )
            ->join('wa_inventory_items', 'wa_inventory_items.id', 'wa_stock_check_freeze_items.wa_inventory_item_id')
            ->where('wa_stock_check_freeze_items.wa_location_and_store_id', $store)
            ->where('wa_stock_check_freeze_items.wa_unit_of_measure', $bin)
            ->whereNotIn('wa_inventory_items.id', $dd)
            ->orderBy('wa_inventory_items.title', 'asc')
            ->get()
            ->unique('id'); // Remove duplicates by item id

        \Log::info("Super Admin Items Loaded", [
            'count' => $items->count(),
            'store' => $store,
            'bin' => $bin
        ]);

        if ($items->count() == 0) {
            return response()->json([
                'status' => false, 
                'message' => 'No items found for this store and bin combination. Either all items have been counted or there are no items in this bin.'
            ]);
        }

        return response()->json([
            'status' => true, 
            'items' => $items, 
            'count' => $items->count(),
            'user_type' => 'Admin/Unrestricted',
            'message' => 'Showing all items'
        ]);
    }

    /**
     * Submit stock counts from mobile web (session-based, no API token needed)
     */
    public function submitMobileWebCounts(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'store' => 'required',
            'bin' => 'required',
            'item_id' => 'array|required',
            'item_quantity' => 'array|required',
            'item_reference' => 'array|required',
            'item_id.*' => 'required|exists:wa_inventory_items,id',
            'item_quantity.*' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/', // Support decimals
            'item_reference.*' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        DB::beginTransaction();
        try {
            foreach ($request->item_id as $index => $itemId) {
                $inventoryItem = WaInventoryItem::find($itemId);
                $frozenQuantity = WaStockCheckFreezeItem::latest()
                    ->where('wa_location_and_store_id', $request->store)
                    ->where('wa_inventory_item_id', $itemId)->first();
                
                $existingCount = WaStockCount::latest()
                    ->where('wa_location_and_store_id', $request->store)
                    ->where('wa_inventory_item_id', $itemId)->first();
                
                if ($existingCount) {
                    DB::rollback();
                    return response()->json(['status' => false, 'message' => "$inventoryItem->title has already been counted."]);
                }

                if ($frozenQuantity) {
                    $entity = new WaStockCount();
                    $entity->wa_location_and_store_id = $request->store;
                    $entity->wa_inventory_item_id = $itemId;
                    $entity->user_id = $user->id;
                    $entity->item_name = $inventoryItem->title;
                    $entity->category_id = $inventoryItem->wa_inventory_category_id;
                    $entity->uom = $request->bin;
                    $entity->quantity = floatval($request->item_quantity[$index]); // Decimal precision
                    $entity->reference = $request->item_reference[$index] ?? '';
                    $entity->save();

                    $variance = new WaStockCountVariation();
                    $variance->user_id = $user->id;
                    $variance->wa_location_and_store_id = $request->store;
                    $variance->wa_inventory_item_id = $itemId;
                    $variance->category_id = $inventoryItem->wa_inventory_category_id;
                    $variance->quantity_recorded = floatval($request->item_quantity[$index]);
                    $variance->current_qoh = floatval($frozenQuantity->quantity_on_hand);
                    
                    if ($frozenQuantity->quantity_on_hand < 0) {
                        $variance->variation = $variance->quantity_recorded + $variance->current_qoh;
                    } else {
                        $variance->variation = $variance->quantity_recorded - $variance->current_qoh;
                    }

                    $variance->uom_id = $request->bin;
                    $variance->reference = $request->item_reference[$index] ?? '';
                    $variance->save();
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Stock counts submitted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Mobile web stock count submission failed: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to submit: ' . $e->getMessage()]);
        }
    }


    public function enterStockCounts()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        if (isset($permission[$pmodule . '___add']) || $permission == 'superadmin') {
            $user = Auth::user();
            //check if user is a store keeper
            if (isset($user->role_id) && $user->role_id == 152) {
                $location_ids = WaStockCheckFreeze::distinct()
                    ->where('wa_location_and_store_id', $user->wa_location_and_store_id)->pluck('wa_location_and_store_id');
            } else {
                $location_ids = WaStockCheckFreeze::distinct()->pluck('wa_location_and_store_id');
            }
            $location_list = \App\Model\WaLocationAndStore::getLocationListByIds($location_ids);
            $category_list = getInventoryCategoryList();
            $breadcum = [$title => route('admin.stock-counts'), 'Enter Stock Counts' => ''];
            return view('admin.stock_counts.enter_stock_counts', compact('title', 'category_list', 'location_list', 'model', 'breadcum', 'pmodule', 'permission', 'user'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function stockCountFormListAjax(Request $request)
    {
        $location_id = $request->location_id;
        $category_id = $request->category_id;
        $wa_unit_of_measure_id = $request->wa_unit_of_measure_id;
        $items = [];
        if (!empty($location_id) && !empty($wa_unit_of_measure_id)) {
            if ($category_id == 0) {
                $counts = WaStockCount::where('wa_location_and_store_id', $location_id)->pluck('wa_inventory_item_id')->toArray();
                $dd = array_unique($counts);
                $items = WaStockCheckFreezeItem::select(
                    'wa_inventory_items.id as inventory_id',
                    'wa_inventory_items.stock_id_code as stock_id_code',
                    'wa_inventory_items.title as title',
                    'pack_sizes.title as pack_size',
                )

                    ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_stock_check_freeze_items.wa_inventory_item_id')
                    ->leftJoin('pack_sizes', 'pack_sizes.id', '=', 'wa_inventory_items.pack_size_id')
                    ->leftJoin('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_items.id')
                    ->where('wa_inventory_location_uom.uom_id', $wa_unit_of_measure_id)
                    ->whereNotIn('wa_inventory_items.id', $dd)->get();
            } else {
                $counts = WaStockCount::where('wa_location_and_store_id', $location_id)->where('category_id', $category_id)->pluck('wa_inventory_item_id')->toArray();
                $dd = array_unique($counts);
                $items = WaStockCheckFreezeItem::select(
                    'wa_inventory_items.id as inventory_id',
                    'wa_inventory_items.stock_id_code as stock_id_code',
                    'wa_inventory_items.title as title',
                    'pack_sizes.title as pack_size',
                )
                    ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_stock_check_freeze_items.wa_inventory_item_id')
                    ->leftJoin('pack_sizes', 'pack_sizes.id', '=', 'wa_inventory_items.pack_size_id')
                    ->leftJoin('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_items.id')
                    ->where('wa_inventory_location_uom.uom_id', $wa_unit_of_measure_id)
                    ->whereNotIn('wa_inventory_items.id', $dd)->where('wa_inventory_category_id', $category_id)->get();
            }
        }
        return view('admin.stock_counts.stock_count_form', compact('items'));
    }

    public function stockCountFormList(Request $request)
    {
        $location_id = $request->location_id;
        $category_id = $request->category_id;
        $wa_unit_of_measure_id = $request->wa_unit_of_measure_id;
        $items = [];
        if (!empty($location_id) && !empty($category_id)) {
            $counts = WaStockCount::where('wa_location_and_store_id', $location_id)->where('category_id', $category_id)->pluck('wa_inventory_item_id')->toArray();
            $dd = array_unique($counts);
            $items = WaInventoryItem::with('pack_size')->where('store_location_id', $location_id)->whereNotIn('id', $dd)->where('wa_unit_of_measure_id', $wa_unit_of_measure_id)->where('wa_inventory_category_id', $category_id)->get();
        }
        return view('admin.stock_counts.stock_count_form', compact('items'));
    }

    public function updateStockCounts(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'wa_location_and_store_id' => 'required',
                'wa_inventory_category_id' => 'required',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            }
            $logged_user_profile = getLoggeduserProfile();
            $location_id = $request->wa_location_and_store_id;
            $category_id = $request->wa_inventory_category_id;
            $wa_unit_of_measure_id = $request->wa_unit_of_measure_id;
            if ($category_id == 0) {

                $items = WaInventoryItem::with('getUnitOfMeausureDetail')
                    ->leftJoin('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_items.id')
                    ->where('wa_inventory_location_uom.uom_id', $request->wa_unit_of_measure_id)
                    // ->where('wa_inventory_category_id', $category_id)
                    ->get();
            } else {
                $items = WaInventoryItem::with('getUnitOfMeausureDetail')
                    ->leftJoin('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_items.id')
                    ->where('wa_inventory_location_uom.uom_id', $request->wa_unit_of_measure_id)
                    ->where('wa_inventory_category_id', $category_id)->get();
            }
            //TODO: use items coming from the request
            foreach ($items as $key => $row) {
                $frozenQuantity = WaStockCheckFreezeItem::latest()->where('wa_location_and_store_id', $location_id)
                    ->where('wa_inventory_item_id', $row->inventory_id)->first();

                if ($frozenQuantity) {
                    $entity = WaStockCount::firstOrNew(
                        [
                            'wa_location_and_store_id' => $location_id,
                            'wa_inventory_item_id' => $row->inventory_id,
                        ]
                    );
                    $entity->user_id = $logged_user_profile->id;
                    $entity->item_name = $row->title;
                    $entity->category_id = $row->wa_inventory_category_id;
                    $entity->uom = $wa_unit_of_measure_id;
                    $entity->quantity = isset($request['quantity_' . $row->inventory_id]) ? $request['quantity_' . $row->inventory_id] : 0;
                    $entity->reference = isset($request['reference_' . $row->inventory_id]) ? $request['reference_' . $row->inventory_id] : '';
                    $entity->save();

                    $variance = new WaStockCountVariation();
                    $variance->user_id = $logged_user_profile->id;
                    $variance->wa_location_and_store_id = $location_id;
                    $variance->wa_inventory_item_id = $row->inventory_id;
                    $variance->category_id = $row->wa_inventory_category_id;
                    $variance->quantity_recorded = !empty($request['quantity_' . $row->inventory_id]) ? $request['quantity_' . $row->inventory_id] : null;
                    // $variance->current_qoh = getItemAvailableQuantity($row->stock_id_code, $location_id);
                    $variance->current_qoh = $frozenQuantity->quantity_on_hand;
                    if ($frozenQuantity->quantity_on_hand < 0) {
                        $variance->variation = isset($request['quantity_' . $row->inventory_id]) ? ($variance->quantity_recorded + $variance->current_qoh) : null;
                    } else {
                        $variance->variation = isset($request['quantity_' . $row->inventory_id]) ? ($variance->quantity_recorded - $variance->current_qoh) : null;
                    }
                    $variance->uom_id =  $wa_unit_of_measure_id;
                    $variance->reference =  isset($request['reference_' . $row->inventory_id]) ? $request['reference_' . $row->inventory_id] : '';
                    $variance->save();
                }
            }
            DB::commit();

            Session::flash('success', 'Stock Counts has been saved successfully.');
            return redirect()->route('admin.stock-counts');
        } catch (\Throwable $e) {
            DB::rollback();
            Session::flash('warning', $e->getMessage());
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        WaStockCount::destroy($id);
        Session::flash('success', 'Record has been deleted successfully.');
        return redirect()->back();
    }

    public function updateStockRow(Request $request)
    {
        if ($request->quantity < 0) {
            Session::flash('warning', 'Quantity should be greater than 0.');
            return redirect()->back();
        }
        $permission = $this->mypermissionsforAModule();
        if (!isset($permission['stock-counts___edit']) && $permission != 'superadmin') {
            Session::flash('warning', 'Restriced: You dont have permission to perform this operation');
            return redirect()->back();
        }
        DB::beginTransaction();
        try {
            $entity = new WaStockCount();
            $entity->exists = true;
            $entity->id = $request->row_id;
            $entity->quantity = $request->quantity;
            $entity->save();
            $newEntity = WaStockCount::find($entity->id);
            $stockCountVariationEntity = WaStockCountVariation::latest()
                ->where('wa_inventory_item_id', $newEntity->wa_inventory_item_id)
                ->where('uom_id', $newEntity->uom)
                ->where('category_id', $newEntity->category_id)
                ->first();
            if ($stockCountVariationEntity->is_processed != 0) {
                DB::rollback();
                Session::flash('warning', 'Cannot update quantity for processed items');
                return redirect()->back();
            }
            $stockCountVariationEntity->quantity_recorded = $request->quantity;
            $stockCountVariationEntity->variation = $request->quantity - $stockCountVariationEntity->current_qoh;
            $stockCountVariationEntity->save();
            DB::commit();

            Session::flash('success', 'Quantity has been updated successfully.');
            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollback();
            Session::flash('warning', $e->getMessage());
            return redirect()->back();
        }
    }

    public function compareCountsVsStockCheck()
    {
        $locationAndStores  = getStoreLocationDropdown();
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = 'Compare Counts Vs Stock Check Data';
        $model = $this->model . '-compare';
        if (isset($permission['compare-counts-vs-stock-check___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route('admin.stock-counts'), $title => ''];
            return view('admin.stock_counts.compare_counts_vs_stock_check', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'locationAndStores'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function compareCountsVsStockCheckUpdate(Request $request)
    {
        $action_type = $request->action_type;
        $data = WaStockCount::with('getAssociateItemDetail.getInventoryCategoryDetail', 'getAssociateLocationDetail')
            ->where('wa_location_and_store_id', $request->wa_location_and_store_id)
            ->get();
        $items_by_location_category = $category_list = [];
        $logged_user_profile = getLoggeduserProfile();
        $batch_date =  date('Y-m-d H:i:s');
        $deviation_data = [];

        $StockAdjustmentdata = [];
        $stockMovedata = [];
        $drgltrans = [];
        $crgltrans = [];

        foreach ($data as $key => $row) {
            $stock_id_code =  $row->getAssociateItemDetail->stock_id_code;
            $wa_location_and_store_id = $row->wa_location_and_store_id;
            $item_available_quantity = getItemAvailableQuantity($stock_id_code, $wa_location_and_store_id);

            $adjustment = $row->quantity - $item_available_quantity;
            if ($action_type == 2) {
                $item_available_quantity = $row->quantity;
            }

            $category_id = $row->getAssociateItemDetail->getInventoryCategoryDetail->id;
            $category_list[$category_id] = [
                'category_description' => $row->getAssociateItemDetail->getInventoryCategoryDetail->category_description,
                'category_code' => $row->getAssociateItemDetail->getInventoryCategoryDetail->category_code
            ];

            $row->quantity_on_hand = $item_available_quantity;
            $row->adjustment = $adjustment;
            $items_by_location_category[$wa_location_and_store_id][$category_id][] = $row;
        }
        if ($action_type == 2) {
            $process = WaStockCountProcess::where('wa_location_and_store_id', $request->wa_location_and_store_id)->count();
            if ($process > 0) {
                $process = WaStockCountProcess::where('wa_location_and_store_id', $request->wa_location_and_store_id)->first();
            } else {
                $process = new WaStockCountProcess();
            }
            $process->wa_location_and_store_id = $request->wa_location_and_store_id;
            $process->selected_type = 2;
            $process->save();
        }
        $location_list = getStoreLocationDropdown();
        $pdf = PDF::loadView('admin.stock_counts.compare_counts_print', compact('items_by_location_category', 'location_list', 'category_list', 'data'));
        return $pdf->download('compare_counts_vs_stock_check_data_' . date('Y_m_d_h_i_s') . '.pdf');
    }

    public function deviationReport()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = 'deviation-report';
        if (isset($permission['deviation-report___view']) || $permission == 'superadmin') {
            $data = WaStockCountDeviation::select('batch_date', DB::raw('COUNT(batch_date) as batch_date_count'))->orderBy('batch_date')->groupBy('batch_date')->get();
            $breadcum = [$title => route('admin.stock-counts'), 'Listing' => ''];
            return view('admin.stock_counts.deviation_report', compact('data', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function stockcountprocess()
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = 'stock-count-process';
        $title = $this->title;
        $model = 'stock-count-process';
        $data = WaStockCountProcess::get();
        $breadcum = [$title => route('admin.stock-counts'), 'Listing' => ''];
        return view('admin.stock_counts.stock_count_process', compact('data', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
    }
    public function compareCountsVsStockCheckProcess(Request $request, $wa_location_and_store_id)
    {

        $action_type = 2;
        $data = WaStockCount::with('getAssociateItemDetail', 'getAssociateItemDetail.getAllFromStockMoves', 'getAssociateItemDetail.getInventoryCategoryDetail', 'getAssociateItemDetail.getInventoryCategoryDetail.getusageGlDetail', 'getAssociateLocationDetail')
            ->where('wa_location_and_store_id', $wa_location_and_store_id)
            ->get();
        //   echo "<pre>"; print_r($data); die;
        $items_by_location_category = $category_list = [];
        $logged_user_profile = getLoggeduserProfile();
        $batch_date =  date('Y-m-d H:i:s');
        $deviation_data = [];

        $StockAdjustmentdata = [];
        $stockMovedata = [];
        $drgltrans = [];
        $crgltrans = [];

        $series_module = WaNumerSeriesCode::where('module', 'GRN')->first();
        $adj_series_module = WaNumerSeriesCode::where('module', 'ITEM ADJUSTMENT')->first();
        $WaAccountingPeriod =  WaAccountingPeriod::where('is_current_period', '1')->first();
        $grn_number = getCodeWithNumberSeriesBillClose('GRN');
        $dateTime = date('Y-m-d H:i:s');
        foreach ($data as $key => $row) {

            $stock_id_code =  $row->getAssociateItemDetail->stock_id_code;
            $wa_location_and_store_id = $row->wa_location_and_store_id;
            $item_available_quantity = getItemAvailableQuantity($stock_id_code, $wa_location_and_store_id);

            $adjustment = $row->quantity - $item_available_quantity;
            if ($action_type == 2) {

                if ($adjustment != 0) {
                    /*
                    $entity = new StockAdjustment();
                    $entity->user_id = $logged_user_profile->id;
                    $entity->item_id = $row->wa_inventory_item_id;
                    $entity->wa_location_and_store_id = $row->wa_location_and_store_id;
                    $entity->adjustment_quantity = $adjustment;
                    $entity->comments = '';
                    $entity->item_adjustment_code = getCodeWithNumberSeries('ITEM ADJUSTMENT');
                    $entity->save();	               
*/


                    $stockMovedata[] = [
                        'user_id' => $logged_user_profile->id,
                        'stock_adjustment_id' => "2",
                        'restaurant_id' => $logged_user_profile->restaurant_id,
                        'standard_cost' => $row->getAssociateItemDetail->standard_cost,
                        'qauntity' => $adjustment,
                        'stock_id_code' => $row->getAssociateItemDetail->stock_id_code,
                        'wa_location_and_store_id' => $row->wa_location_and_store_id,
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => $series_module->last_number_used,
                        'price' => $row->getAssociateItemDetail->selling_price,
                        'created_at' => $batch_date,
                        'updated_at' => $batch_date,
                        'refrence' => '',
                        'new_qoh' => ($row->getAssociateItemDetail->getAllFromStockMoves->where('wa_location_and_store_id', @$row->wa_location_and_store_id)->sum('qauntity') ?? 0) + $adjustment,
                        'wa_inventory_item_id' => $row->getAssociateItemDetail->id
                    ];


                    if ($adjustment < '0') {
                        $draccount = $row->getAssociateItemDetail->getInventoryCategoryDetail->getusageGlDetail->account_code;
                    } else {
                        $draccount = $row->getAssociateItemDetail->getInventoryCategoryDetail->getStockGlDetail->account_code;
                    }


                    $drgltrans[] = [
                        'grn_type_number' => $series_module->type_number,
                        'stock_adjustment_id' => "2",
                        'transaction_type' => $adj_series_module->description,
                        'grn_last_used_number' => $series_module->last_number_used,
                        'trans_date' => $dateTime,
                        'restaurant_id' => getLoggeduserProfile()->restaurant_id,
                        'period_number' => $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null,
                        'amount' => abs($row->getAssociateItemDetail->standard_cost * $adjustment),
                        'account' => $draccount,
                        'created_at' => $batch_date,
                        'updated_at' => $batch_date,
                        'narrative' => $row->getAssociateItemDetail->stock_id_code . '/' . $row->getAssociateItemDetail->title . '/' . $row->getAssociateItemDetail->standard_cost . '@' . $adjustment,
                    ];



                    if ($adjustment < '0') {
                        $craccount = $row->getAssociateItemDetail->getInventoryCategoryDetail->getStockGlDetail->account_code;
                    } else {
                        $craccount = $row->getAssociateItemDetail->getInventoryCategoryDetail->getPricevarianceGlDetail->account_code;
                    }

                    $crgltrans[] = [
                        'grn_type_number' => $series_module->type_number,
                        'stock_adjustment_id' => "2",
                        'transaction_type' => $adj_series_module->description,
                        'grn_last_used_number' => $series_module->last_number_used,
                        'trans_date' => $dateTime,
                        'restaurant_id' => getLoggeduserProfile()->restaurant_id,
                        'period_number' => $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null,
                        'amount' => abs($row->getAssociateItemDetail->standard_cost * $adjustment),
                        'account' => $craccount,
                        'created_at' => $batch_date,
                        'updated_at' => $batch_date,
                        'narrative' => $row->getAssociateItemDetail->stock_id_code . '/' . $row->getAssociateItemDetail->title . '/' . $row->getAssociateItemDetail->standard_cost . '@' . $adjustment,
                    ];


                    //  updateUniqueNumberSeries('GRN',$grn_number);
                }



                $deviation_data[] = [
                    'batch_date' => $batch_date,
                    'wa_inventory_item_id' => $row->wa_inventory_item_id,
                    'wa_location_and_store_id' => $row->wa_location_and_store_id,
                    'uom' => $row->uom,
                    'quantity' => $row->quantity,
                    'quantity_on_hand' => $item_available_quantity,
                    'created_at' => $batch_date,
                    'updated_at' => $batch_date,
                ];

                /**/
                $item_available_quantity = $row->quantity;
            }
        }
        if ($action_type == 2) {
            /*
			echo "<pre>"; print_r($stockMovedata);
			echo "<pre>"; print_r($drgltrans);
			echo "<pre>"; print_r($crgltrans);
			echo "<pre>"; print_r($deviation_data);
			die();
*/
            WaStockMove::insert($stockMovedata);
            WaGlTran::insert($drgltrans);
            WaGlTran::insert($crgltrans);


            WaStockCountDeviation::insert($deviation_data);


            DB::table('wa_stock_counts')->where('wa_location_and_store_id', $wa_location_and_store_id)->delete();
            DB::table('wa_stock_count_process')->where('wa_location_and_store_id', $wa_location_and_store_id)->delete();
        }
        Session::flash('success', 'Processed Successfully.');
        return redirect()->back();
    }
    public function deviationReportPdf($date)
    {
        $category_list = $items_by_location_category = [];
        $data = WaStockCountDeviation::where('batch_date', $date)->with('getAssociateItemDetail', 'getAssociateItemDetail.getInventoryCategoryDetail')->get();
        foreach ($data as $key => $row) {
            $wa_location_and_store_id = $row->wa_location_and_store_id;
            $item_available_quantity = $row->quantity_on_hand;
            $adjustment = $row->quantity - $item_available_quantity;

            $category_id = @$row->getAssociateItemDetail->getInventoryCategoryDetail->id;
            $category_list[$category_id] = [
                'category_description' => @$row->getAssociateItemDetail->getInventoryCategoryDetail->category_description,
                'category_code' => @$row->getAssociateItemDetail->getInventoryCategoryDetail->category_code
            ];

            $row->quantity_on_hand = $item_available_quantity;
            $row->adjustment = $adjustment;
            $items_by_location_category[$wa_location_and_store_id][$category_id][] = $row;
        }
        $location_list = getStoreLocationDropdown();
        return view('admin.stock_counts.compare_counts_print', compact('items_by_location_category', 'location_list', 'category_list', 'data'));
        return $pdf->download('compare_counts_vs_stock_check_data_' . date('Y_m_d_h_i_s') . '.pdf');
    }

    public function deviationReportExcel($date)
    {

        $data = WaStockCountDeviation::where('batch_date', $date)->with('getAssociateItemDetail', 'getAssociateItemDetail.getInventoryCategoryDetail', 'getAssociateLocationDetail')->get();
        $data_excel = [];
        foreach ($data as $key => $row) {
            $item_available_quantity = $row->quantity_on_hand;
            $adjustment = $row->quantity - $item_available_quantity;
            $deviationvalue = $adjustment * @$row->getAssociateItemDetail->standard_cost;
            $data_excel[] = [
                'loccode' => @$row->getAssociateLocationDetail->location_code,
                'stockid' => @$row->getAssociateItemDetail->stock_id_code,
                'description' => @$row->getAssociateItemDetail->title,
                'categoryid' => @$row->getAssociateItemDetail->getInventoryCategoryDetail->category_code,
                'categorydescription' => @$row->getAssociateItemDetail->getInventoryCategoryDetail->category_description,
                'uom' => $row->uom,
                'counted' => $row->quantity,
                'system' => $row->quantity_on_hand,
                'cost' => @$row->getAssociateItemDetail->standard_cost,
                'deviation' => $adjustment,
                'deviationvalue' => $deviationvalue,
            ];
        }

        return Excel::create('deviation_reports', function ($excel) use ($data_excel) {
            $excel->sheet('mySheet', function ($sheet) use ($data_excel) {
                $sheet->fromArray($data_excel);
            });
        })->download('xls');
    }

    public function getCetegoryListForStore(Request $request)
    {

        $lists =  getUnserializeList($request->wa_location_and_store_id);
        $html = '<option value="">Please select</option>';
        foreach ($lists as $key => $title) {
            $html .=  '<option value="' . $key . '">' . $title . '</option>';
        }
        return $html;
    }
    public function getMobileStockTakeItems(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'token' => 'required'
        // ]);
        // if ($validator->fails()) {
        //     $error = $this->validationHandle($validator->messages());
        //     return response()->json(['status' => false, 'message' => $error]);
        // }
        $getUserData = JWTAuth::toUser($request->token);
        if (!$getUserData) {
            return $this->jsonify(['errors' => 'Invalid token provided.'], 422);
        }
        $today = Carbon::now()->toDateString();
        $stores = WaLocationAndStore::select('id', 'location_code', 'location_name')->where('id', $getUserData->wa_location_and_store_id)->get();
        $counts = WaStockCount::where('wa_location_and_store_id', $getUserData->wa_location_and_store_id)->pluck('wa_inventory_item_id')->toArray();
        $dd = array_unique($counts);
        if ($getUserData->role_id == 169 || $getUserData->role_id == 170 || $getUserData->role_id == 181) {
            $assignedBins = WaUnitOfMeasure::select(
                'wa_unit_of_measures.id as id',
                'wa_unit_of_measures.title as title',
                'wa_unit_of_measures.slug as slug',
                'wa_location_store_uom.location_id as store_id'
            )
                ->leftJoin('wa_location_store_uom', 'wa_location_store_uom.uom_id', 'wa_unit_of_measures.id')
                ->where('wa_unit_of_measures.id', $getUserData->wa_unit_of_measures_id)->get();
            $userItems = DisplayBinUserItemAllocation::where('user_id', $getUserData->id)->pluck('wa_inventory_item_id')->toArray();
            $categoryIds = WaInventoryItem::whereIn('id', $userItems)->pluck('wa_inventory_category_id')->toArray();
            $categories = WaInventoryCategory::select(
                'wa_inventory_categories.id',
                'wa_inventory_categories.category_description',
                'wa_inventory_categories.slug'
            )
                ->whereIn('wa_inventory_categories.id', $categoryIds)
                ->get();
            $items = WaStockCheckFreezeItem::select(
                'wa_inventory_items.id as id',
                'wa_inventory_items.stock_id_code as stock_id_code',
                'wa_inventory_items.title as title',
                'wa_inventory_items.wa_inventory_category_id as category_id',
                'wa_inventory_location_uom.uom_id as bin_id',
                'wa_inventory_location_uom.location_id as store_id',
                'wa_stock_check_freeze_items.quantity_on_hand as system_qoh'
            )
                ->join('wa_inventory_items', 'wa_inventory_items.id', 'wa_stock_check_freeze_items.wa_inventory_item_id')
                ->join('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_items.id')
                ->where('wa_stock_check_freeze_items.wa_location_and_store_id', $getUserData->wa_location_and_store_id)
                ->where('wa_inventory_location_uom.location_id', $getUserData->wa_location_and_store_id)
                ->whereIn('wa_inventory_items.id', $userItems)
                ->whereNotIn('wa_inventory_items.id', $dd)
                ->distinct('wa_inventory_items.id') 
                ->orderBy('wa_stock_check_freeze_items.quantity_on_hand', 'desc')
                ->get();
            if ($items->count() == 0) {
                return response()->json(['status' => false, 'message' => 'You have  no items pending  count']);
            }

            return response()->json(['status' => true, 'stores' => $stores, 'bins' => $assignedBins, 'categories' => $categories, 'items' => $items, 'show_quantity' => false]);
        }

        $assignment = StockTakeUserAssignment::latest()
            ->whereHas('assistant', function ($q) use ($getUserData) {
                $q->where('user_id', $getUserData->id);
            })
            ->whereDate('stock_take_date', $today)->first();
        if (!$assignment) {
            return response()->json(['status' => false, 'message' => 'No Stock Take Assigned']);
        }
        $assignedBins = WaUnitOfMeasure::select(
            'wa_unit_of_measures.id as id',
            'wa_unit_of_measures.title as title',
            'wa_unit_of_measures.slug as slug',
            'wa_location_store_uom.location_id as store_id'
        )
            ->leftJoin('wa_location_store_uom', 'wa_location_store_uom.uom_id', 'wa_unit_of_measures.id')
            ->where('wa_unit_of_measures.id', $assignment->uom_id)->get();
        $categoryIds = explode(',', $assignment->category_ids);
        $categories = WaInventoryCategory::select('id', 'category_description', 'slug')->whereIn('id', $categoryIds)->get();
        if (!$assignment) {
            return response()->json(['status' => false, 'message' => 'No Stock Take  Categories Assigned']);
        }
        $items = WaStockCheckFreezeItem::select(
            'wa_inventory_items.id as id',
            'wa_inventory_items.stock_id_code as stock_id_code',
            'wa_inventory_items.title as title',
            'wa_inventory_items.wa_inventory_category_id as category_id',
            'wa_inventory_location_uom.uom_id as bin_id',
            'wa_inventory_location_uom.location_id as store_id',
            'wa_stock_check_freeze_items.quantity_on_hand as system_qoh'
        )
            ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', 'wa_stock_check_freeze_items.wa_inventory_item_id')
            ->leftJoin('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', '=', 'wa_inventory_items.id')
            ->where('wa_stock_check_freeze_items.wa_location_and_store_id', $getUserData->wa_location_and_store_id)
            ->whereIn('wa_inventory_items.wa_inventory_category_id', $categoryIds)
            ->whereIn('wa_inventory_location_uom.uom_id', $assignedBins->pluck('id'))
            ->whereNotIn('wa_inventory_items.id', $dd)
            ->distinct('wa_inventory_items.id') 
            ->orderBy('wa_inventory_items.title', 'asc')
            ->get();

        if ($items->count() == 0) {
            return response()->json(['status' => false, 'message' => 'You have  no items pending  count']);
        }

        return response()->json(['status' => true, 'stores' => $stores, 'bins' => $assignedBins, 'categories' => $categories, 'items' => $items, 'show_quantity' => false]);
    }
    //record  mobile counts
    public function recordMobileStockTakes(Request $request)
    {
        $user = JWTAuth::toUser($request->token);
        if (!$user) {
            return $this->jsonify(['errors' => 'Invalid token provided.'], 422);
        }
        DB::beginTransaction();
        try {
            $itemIds = $request->input('item_id');
            $itemQuantities = $request->input('item_quantity');
            $itemReferences = $request->input('item_reference');

            if (!(count($itemIds) === count($itemQuantities) && count($itemQuantities) === count($itemReferences))) {
                return response()->json(['status' => false, 'message' => 'The item_id, item_quantity, and item_reference arrays must have the same length.']);
            }

            $validator = Validator::make($request->all(), [
                'store' => 'required',
                'bin' => 'required',
                // 'token' => 'required',
                'item_id' => 'array|required',
                'item_quantity' => 'array|required',
                'item_reference' => 'array|required',
                'item_id.*' => 'required|exists:wa_inventory_items,id',
                'item_quantity.*' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/', // Support decimals up to 2 places
                'item_reference.*' => 'nullable',


            ]);
            if ($validator->fails()) {
                $error = $this->validationHandle($validator->messages());
                return response()->json(['status' => false, 'message' => $error]);
            }
            //process each item
            foreach ($request->item_id as $index => $itemId) {
                $inventoryItem = WaInventoryItem::find($itemId);
                $frozenQuantity = WaStockCheckFreezeItem::latest()->where('wa_location_and_store_id', $request->store)
                    ->where('wa_inventory_item_id', $itemId)->first();
                $existingCount = WaStockCount::latest()->where('wa_location_and_store_id', $request->store)
                    ->where('wa_inventory_item_id', $itemId)->first();
                if ($existingCount) {
                    DB::rollback();
                    return response()->json(['status' => false, 'message' => "$inventoryItem->title has already been counted."]);
                }

                if ($frozenQuantity) {
                    $entity = WaStockCount::firstOrNew(
                        [
                            'wa_location_and_store_id' => $request->store,
                            'wa_inventory_item_id' => $itemId,
                        ]
                    );
                    $entity->user_id = $user->id;
                    $entity->item_name = $inventoryItem->title;
                    $entity->category_id = $inventoryItem->wa_inventory_category_id;
                    $entity->uom = $request->bin;
                    $entity->quantity = isset($request->item_quantity[$index]) ? floatval($request->item_quantity[$index]) : 0; // Ensure decimal precision
                    $entity->reference = isset($request->item_reference[$index]) ? $request->item_reference[$index] : '';
                    $entity->counted_at = now(); // Audit trail
                    $entity->save();

                    $variance = new WaStockCountVariation();
                    $variance->user_id = $user->id;
                    $variance->wa_location_and_store_id = $request->store;
                    $variance->wa_inventory_item_id = $itemId;
                    $variance->category_id = $inventoryItem->wa_inventory_category_id;
                    $variance->quantity_recorded = isset($request->item_quantity[$index]) ? floatval($request->item_quantity[$index]) : 0; // Decimal precision
                    // $variance->current_qoh = getItemAvailableQuantity($row->stock_id_code, $request->store);
                    $variance->current_qoh = floatval($frozenQuantity->quantity_on_hand); // Decimal precision
                    if ($frozenQuantity->quantity_on_hand < 0) {
                        $variance->variation = isset($request->item_quantity[$index]) ? ($variance->quantity_recorded + $variance->current_qoh) : null;
                    } else {
                        $variance->variation = isset($request->item_quantity[$index]) ? ($variance->quantity_recorded - $variance->current_qoh) : null;
                    }

                    $variance->uom_id =  $request->bin;
                    $variance->reference =  isset($request->item_reference[$index]) ? $request->item_reference[$index] : '';
                    $variance->save();
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'stock takes processed successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['result' => -1, 'message' => $e->getMessage()], 500);
        }
    }
    public function getEnteredStockTakeItems(Request $request)
    {
        $getUserData = JWTAuth::toUser($request->token);
        if (!$getUserData) {
            return $this->jsonify(['errors' => 'Invalid token provided.'], 422);
        }
        $today = Carbon::now()->toDateString();
        $items = WaStockCountVariation::select(
            'wa_inventory_items.id as id',
            'wa_inventory_items.stock_id_code as stock_id_code',
            'wa_inventory_items.title as title',
            'wa_inventory_categories.category_description as category',
            'wa_unit_of_measures.title as bin',
            'wa_stock_count_variation.quantity_recorded as physical_qoh',
            'wa_stock_count_variation.current_qoh as system_qoh',
            'wa_stock_count_variation.variation as variation',
            'wa_stock_count_variation.reference as reference'
        )
            ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_stock_count_variation.wa_inventory_item_id')
            ->leftJoin('wa_inventory_categories', 'wa_inventory_items.wa_inventory_category_id', '=', 'wa_inventory_categories.id')
            ->leftJoin('wa_unit_of_measures', 'wa_unit_of_measures.id', '=', 'wa_stock_count_variation.uom_id')
            ->whereBetween('wa_stock_count_variation.created_at', [$today . ' 00:00:00', $today . ' 23:59:59']);
        if ($getUserData->role_id != 152) {
            $items = $items->where('wa_stock_count_variation.user_id', $getUserData->id);
        }
        if ($getUserData->role_id = 152) {
            $items = $items->where('wa_stock_count_variation.uom_id', $getUserData->wa_unit_of_measures_id);
        }
        if($request->search){
            $items = $items->where('wa_inventory_items.stock_id_code',  '%'.$request->search.'%')
                ->orWhere('wa_inventory_items.title',  '%'.$request->search.'%');
        }
        $items = $items->orderBy('wa_stock_count_variation.current_qoh', 'desc')->get();
        return response()->json(['status' => true, 'items' => $items, 'show_quantity' => true]);
    }
}
