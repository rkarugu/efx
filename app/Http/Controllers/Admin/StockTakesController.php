<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SalesItemsDataExport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
// use PDF;
use Session;
use Illuminate\Support\Facades\Validator;
use App\Model\WaStockCheckFreeze;
use App\Model\WaStockCheckFreezeItem;
use App\Model\WaInventoryItem;
use App\Model\WaInventoryCategory;
use App\Model\WaInventoryLocationUom;
use App\Model\WaLocationAndStore;
use App\Model\WaUnitOfMeasure;
use App\Models\WaLocationStoreUom;
use App\Services\ExcelDownloadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class StockTakesController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'stock-takes';
        $this->title = 'Stock Takes';
        $this->pmodule = 'stock-take';
    }

    public function index()
    {
        // dd('dsfds');
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;

        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $user = Auth::user();
            if (isset($user->role_id) && $user->role_id == 152) {
                $lists = WaStockCheckFreeze::with(['getAssociateLocationDetail', 'getAssociateUserDetail', 'unit_of_measure', 'getAssociateItems'])
                    ->where('wa_location_and_store_id', $user->wa_location_and_store_id)
                    ->withCount(['getAssociateItems' => function ($query) {
                        $query->whereHas('getAssociateItemDetail', function ($query) {
                            $query->where('status', 1);
                        });
                    }])
                    ->orderBy('id', 'desc')->get();
            } else {
                $lists = WaStockCheckFreeze::with(['getAssociateLocationDetail', 'getAssociateUserDetail', 'unit_of_measure'])
                    ->withCount(['getAssociateItems' => function ($query) {
                        $query->whereHas('getAssociateItemDetail', function ($query) {
                            $query->where('status', 1);
                        });
                    }])
                    ->orderBy('id', 'desc')->get();
            }

            $breadcum = [$title => route('admin.stock-takes.create-stock-take-sheet'), 'Listing' => ''];
            return view('admin.stock_takes.index', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function freezeTable()
    {
        $permission = $this->mypermissionsforAModule();
        $branch_id = Auth::user()->wa_location_and_store_id;
        if($permission == 'superadmin') {
            DB::table('wa_stock_check_freezes_c')->delete();
            DB::table('wa_stock_counts_c')->delete();
            DB::table('wa_stock_check_freeze_c_items')->delete();
        }else{
            DB::table('wa_stock_check_freezes_c')->where('wa_location_and_store_id', $branch_id)->delete();
            DB::table('wa_stock_counts_c')->where('wa_location_and_store_id', $branch_id)->delete();
            DB::table('wa_stock_check_freeze_c_items')->where('wa_location_and_store_id', $branch_id)->delete();
        }

        Session::flash('success', 'Table has been truncated successfully.');
        return redirect()->back();
    }

    /*
    public function addStockCheckFile(Request $request){
        $wa_inventory_category_ids = array_filter($request->wa_inventory_category_id);
        if(empty($wa_inventory_category_ids)){
            Session::flash('warning', 'Please select Inventory Categorios.');
            return redirect()->back();
        }
        $logged_user_profile = getLoggeduserProfile();
        
        if($request->action_add_or_update == 1){
            WaStockCheckFreezeItem::whereIn('item_category_id', $wa_inventory_category_ids)->delete();
            //DB::table('wa_stock_check_freeze_items')->delete();
        }
        
        $entity_stock_check = new WaStockCheckFreeze();
        $entity_stock_check->wa_location_and_store_id = $request->wa_location_and_store_id;
        $entity_stock_check->user_id = $logged_user_profile->id;
        $entity_stock_check->wa_inventory_category_ids = serialize($wa_inventory_category_ids);
        $entity_stock_check->save();
        
        $items = WaInventoryItem::with('getUnitOfMeausureDetail')->whereIn('wa_inventory_category_id', $wa_inventory_category_ids)->get();
        
        foreach($items as $key => $item_row){
            $available_quantity = getItemAvailableQuantity($item_row->stock_id_code, $request->wa_location_and_store_id);
            if(!empty($request->quantities_zero) && empty($available_quantity)){
                continue;
            }
            if($request->action_add_or_update == 2){
                $entity = WaStockCheckFreezeItem::firstOrNew(
                    ['wa_inventory_item_id'=>$item_row->id]
                );
            }
            else{
                $entity = new WaStockCheckFreezeItem();
            }
            $entity->wa_stock_check_freeze_id = $entity_stock_check->id;
            $entity->wa_inventory_item_id = $item_row->id;
            $entity->item_category_id = $item_row->wa_inventory_category_id;
            $entity->quantity_on_hand = $available_quantity;
            $entity->wa_unit_of_measure = $item_row->getUnitOfMeausureDetail->title;
            $entity->save();
        }
        
        Session::flash('success', 'Processed successfully.');
        return redirect()->back();
    }*/

    public function addStockCheckFile(Request $request)
    {
        $wa_inventory_category_ids = $request->wa_inventory_category_id ?? WaInventoryCategory::pluck('id')->toArray();
        $logged_user_profile = getLoggeduserProfile();
        $stock_check_row_created = 0;
        $unit = WaUnitOfMeasure::with('get_uom_location')->where('id', $request->wa_unit_of_measure_id)->first();

        // Wrap in transaction for data integrity
        DB::beginTransaction();
        try {
            foreach ($wa_inventory_category_ids as $category_id) {
                $items = WaInventoryItem::where('wa_inventory_location_uom.uom_id', $request->wa_unit_of_measure_id)
                    ->where('wa_location_and_stores.id', $request->wa_location_and_store_id)
                    ->where('wa_inventory_items.wa_inventory_category_id', $category_id)
                    ->where('wa_inventory_items.status', 1)
                    ->join('wa_inventory_location_uom', function ($e) {
                        $e->on('wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id');
                    })
                    ->leftJoin('wa_location_and_stores', function ($e) {
                        $e->on('wa_inventory_location_uom.location_id', '=', 'wa_location_and_stores.id');
                    })
                    ->groupBy('wa_inventory_items.id')
                    ->select('wa_inventory_items.*')
                    ->get();

                // PERFORMANCE OPTIMIZATION: Batch fetch all quantities at once
                $stock_id_codes = $items->pluck('stock_id_code')->toArray();
                $available_quantities = getItemsAvailableQuantities($stock_id_codes, $request->wa_location_and_store_id);

                $exits_row = WaStockCheckFreezeItem::where([['wa_unit_of_measure', @$unit->id], ['wa_location_and_store_id', $request->wa_location_and_store_id], ['item_category_id', $category_id]])->first();
                
                if ($exits_row) {
                    // Update existing freeze
                    $stock_check_id = $exits_row->wa_stock_check_freeze_id;
                    foreach ($items as $key => $item_row) {
                        $available_quantity = $available_quantities[$item_row->stock_id_code] ?? 0;
                        if (!empty($request->quantities_zero) && empty($available_quantity)) {
                            continue;
                        }
                        
                        $entity = WaStockCheckFreezeItem::firstOrNew(
                            [
                                'wa_inventory_item_id' => $item_row->id,
                                'wa_stock_check_freeze_id' => $stock_check_id,
                                'wa_location_and_store_id' => $request->wa_location_and_store_id,
                                'item_category_id' => $category_id,
                                'wa_unit_of_measure' => @$unit->id,
                            ]
                        );

                        $entity->quantity_on_hand = $available_quantity;
                        $entity->save();
                    }
                } else {
                    // Create new freeze
                    if ($stock_check_row_created == 0) {
                        $entity_stock_check = new WaStockCheckFreeze();
                        $entity_stock_check->wa_location_and_store_id = $request->wa_location_and_store_id;
                        $entity_stock_check->user_id = $logged_user_profile->id;
                        $entity_stock_check->wa_unit_of_measure_id = $request->wa_unit_of_measure_id;
                        $entity_stock_check->wa_inventory_category_ids = serialize($wa_inventory_category_ids);
                        $entity_stock_check->save();
                        $stock_check_row_created = 1;
                    }
                    
                    foreach ($items as $key => $item_row) {
                        $available_quantity = $available_quantities[$item_row->stock_id_code] ?? 0;
                        if (!empty($request->quantities_zero) && empty($available_quantity)) {
                            continue;
                        }
                        
                        $entity = new WaStockCheckFreezeItem();
                        $entity->wa_stock_check_freeze_id = $entity_stock_check->id;
                        $entity->wa_inventory_item_id = $item_row->id;
                        $entity->wa_location_and_store_id = $request->wa_location_and_store_id;
                        $entity->item_category_id = $category_id;
                        $entity->quantity_on_hand = $available_quantity;
                        $entity->wa_unit_of_measure = @$unit->id;
                        $entity->save();
                    }
                }
            }
            
            DB::commit();
            Session::flash('success', 'Processed successfully.');
            return redirect()->back();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Stock take freeze creation failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to create stock take: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function printToPdf($id)
    {
        // Increase limits for large datasets
        set_time_limit(300); // 5 minutes
        ini_set("memory_limit", "512M");
        ini_set('max_execution_time', 300);

        $categories = WaInventoryCategory::all();
        $freeze = WaStockCheckFreeze::find($id);
        
        if (!$freeze) {
            Session::flash('error', 'Stock take freeze not found');
            return redirect()->back();
        }

        $user = Auth::user();
        if (isset($user->role_id) && $user->role_id == 152) {
            $bins = WaUnitOfMeasure::where('wa_unit_of_measures.id', $user->wa_unit_of_measures_id)
                ->where('wa_location_store_uom.location_id', $freeze->wa_location_and_store_id)
                ->leftJoin('wa_location_store_uom', function ($e) {
                    $e->on('wa_location_store_uom.uom_id', '=', 'wa_unit_of_measures.id');
                })->get();
        } else {
            $bins = WaUnitOfMeasure::where('wa_location_store_uom.location_id', $freeze->wa_location_and_store_id)
                ->leftJoin('wa_location_store_uom', function ($e) {
                    $e->on('wa_location_store_uom.uom_id', '=', 'wa_unit_of_measures.id');
                })->get();
        }

        // Optimize query - only select needed columns
        $freezeItems = DB::table('wa_stock_check_freeze_items')
            ->leftJoin('wa_inventory_items', 'wa_inventory_items.id', '=', 'wa_stock_check_freeze_items.wa_inventory_item_id')
            ->select(
                'wa_stock_check_freeze_items.id',
                'wa_stock_check_freeze_items.quantity_on_hand',
                'wa_stock_check_freeze_items.wa_unit_of_measure',
                'wa_stock_check_freeze_items.item_category_id',
                'wa_inventory_items.stock_id_code',
                'wa_inventory_items.title'
            )
            ->where('wa_stock_check_freeze_items.wa_location_and_store_id', '=', $freeze->wa_location_and_store_id)
            ->where('wa_inventory_items.status', 1)
            ->orderBy('wa_inventory_items.title', 'asc')
            ->get();

        \Log::info("Generating PDF for freeze ID {$id} with {$freezeItems->count()} items");

        // PERFORMANCE OPTIMIZATION: Pre-group items by bin and category to avoid nested loops in view
        $itemsByBinAndCategory = [];
        foreach ($freezeItems as $item) {
            $binId = $item->wa_unit_of_measure ?? 0;
            $categoryId = $item->item_category_id ?? 0;
            
            if (!isset($itemsByBinAndCategory[$binId])) {
                $itemsByBinAndCategory[$binId] = [];
            }
            if (!isset($itemsByBinAndCategory[$binId][$categoryId])) {
                $itemsByBinAndCategory[$binId][$categoryId] = [];
            }
            
            $itemsByBinAndCategory[$binId][$categoryId][] = $item;
        }

        try {
            $pdf = Pdf::loadView('admin.stock_takes.print', compact('categories', 'freeze', 'freezeItems', 'bins', 'itemsByBinAndCategory'));
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('enable-local-file-access', true);
            
            return $pdf->download('stock_check_' . date('Y_m_d_H_i_s') . '.pdf');
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            Session::flash('error', 'Failed to generate PDF: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    public function printPage(Request $request)
    {

        $id = $request->id;
        $title = $this->title;
        $model = $this->model;
        $breadcum = [$this->title => route('admin.stock-takes.add-stock-check-file'), 'Add' => ''];
        $data = WaStockCheckFreeze::with('getAssociateItems.getAssociateItemDetail.pack_size', 'getAssociateItems.getAssociateItemDetail.getInventoryCategoryDetail')->where('id', $id)->first();

        $freeze_items = $data->getAssociateItems;
        $items_by_category = $category_list = [];
        foreach ($freeze_items as $key => $row) {
            $category_id = $row->getAssociateItemDetail->getInventoryCategoryDetail->id;
            $category_list[$category_id] = [
                'category_description' => $row->getAssociateItemDetail->getInventoryCategoryDetail->category_description,
                'category_code' => $row->getAssociateItemDetail->getInventoryCategoryDetail->category_code
            ];
            $items_by_category[$category_id][] = $row;
        }
        $print_page = 1;
        return view('admin.stock_takes.print', compact('print_page', 'items_by_category', 'category_list', 'data', 'title', 'model', 'breadcum'));
    }

    public function getCategories(Request $request)
    {
        $selectedCategory = [];
        if ($request->selectedCategory) {
            $selectedCategory = explode(',', $request->selectedCategory);
        }
        $selectedUNit = '';
        if ($request->selectedUNit) {
            $selectedUNit = $request->selectedUNit;
        }
        if ($request->wa_location_and_store_id) {
            if ($request->wa_unit_of_measure_id) {
                //get categories for items in that bin only
                $itemsInBin = WaInventoryLocationUom::where('location_id', $request->wa_location_and_store_id)
                    ->where('uom_id', $request->wa_unit_of_measure_id)->pluck('inventory_id')->toArray();
                $itemCategoryIds = WaInventoryItem::whereIn('id', $itemsInBin)->pluck('wa_inventory_category_id')->toArray();
                $data = WaInventoryCategory::whereIn('id', $itemCategoryIds)->orderBy('id', 'DESC')->get();
                $rec = '<option value="0">All</option>';
                foreach ($data as $key => $value) {
                    $selectedC = '';
                    if (in_array($value->id, $selectedCategory)) {
                        $selectedC = 'selected';
                    }
                    $rec .= '<option value="' . $value->id . '" ' . $selectedC . '>' . $value->category_description . '</option>';
                }
            } else {
                $data = WaInventoryCategory::whereHas('getinventoryitems', function ($w) use ($request) {
                    // $w->where('store_location_id', $request->wa_location_and_store_id);
                })->orderBy('id', 'DESC')->get();
                $rec = '<option value="0">All</option>';
                foreach ($data as $key => $value) {
                    $selectedC = '';
                    if (in_array($value->id, $selectedCategory)) {
                        $selectedC = 'selected';
                    }
                    $rec .= '<option value="' . $value->id . '" ' . $selectedC . '>' . $value->category_description . '</option>';
                }
            }


            $loggedUser = Auth::user();
            //check if user is store keeper
            if ($loggedUser->role_id == 152) {
                $unit = WaUnitOfMeasure::where('id', $loggedUser->wa_unit_of_measures_id)->orderBy('id', 'DESC')->get();
            } else {
                $unit = WaUnitOfMeasure::orderBy('id', 'DESC')->get();
            }


            $rec1 = '';
            foreach ($unit as $valueS) {
                $selectedU = '';
                if ($valueS->id == $selectedUNit) {
                    $selectedU = 'selected';
                }
                $rec1 .= '<option value="' . $valueS->id . '" ' . $selectedU . '>' . $valueS->title . '</option>';
            }
            return response()->json(['result' => 1, 'data' => $rec, 'unit' => $rec1]);
        } else {
            if ($request->wa_unit_of_measure_id) {
                //get categories for items in that bin only
                $itemsInBin = WaInventoryLocationUom::where('location_id', $request->wa_location_and_store_id)
                    ->where('uom_id', $request->wa_unit_of_measure_id)->pluck('inventory_id')->toArray();
                $itemCategoryIds = WaInventoryItem::whereIn('id', $itemsInBin)->pluck('wa_inventory_category_id')->toArray();
                $data = WaInventoryCategory::whereIn('id', $itemCategoryIds)->orderBy('id', 'DESC')->get();
                $rec = '<option value="0">All</option>';
                foreach ($data as $key => $value) {
                    $selectedC = '';
                    if (in_array($value->id, $selectedCategory)) {
                        $selectedC = 'selected';
                    }
                    $rec .= '<option value="' . $value->id . '" ' . $selectedC . '>' . $value->category_description . '</option>';
                }
            } else {
                $data = WaInventoryCategory::orderBy('id', 'DESC')->get();
                $rec = '<option value="0">All</option>';
                foreach ($data as $key => $value) {
                    $selectedC = '';
                    if (in_array($value->id, $selectedCategory)) {
                        $selectedC = 'selected';
                    }
                    $rec .= '<option value="' . $value->id . '" ' . $selectedC . '>' . $value->category_description . '</option>';
                }
            }

            $unit = WaUnitOfMeasure::orderBy('id', 'DESC')->get();
            $rec1 = '';
            foreach ($unit as $valueS) {
                $selectedU = '';
                if ($valueS->id == $selectedUNit) {
                    $selectedU = 'selected';
                }
                $rec1 .= '<option value="' . $valueS->id . '" ' . $selectedU . '>' . $valueS->title . '</option>';
            }
            return response()->json(['result' => 1, 'data' => $rec, 'unit' => $rec1]);
        }
    }
}
