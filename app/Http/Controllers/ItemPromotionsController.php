<?php

namespace App\Http\Controllers;

use App\Enums\PromotionMatrix;
use App\Exports\GeneralExcelExport;
use App\Exports\ItemsWithPromotionsExport;
use App\ItemPromotion;
use App\Model\WaInventoryItem;
use App\Model\WaSupplier;
use App\Models\PromotionGroup;
use App\Models\PromotionType;
use App\WaDemand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class ItemPromotionsController extends Controller
{
    protected $model;
    protected $title;
    protected $pmodule;
    protected  $basePath;

    public function __construct(Request $request)
    {
        $this->model = 'maintain-items';
        $this->title = 'Maintain items';
        $this->pmodule = 'maintain-items';
        $this->basePath = 'admin.promotions';
    }
    public function index($itemId)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $inventoryItem = WaInventoryItem::find($itemId);
        $promotions = ItemPromotion::where('inventory_item_id', $itemId)->get();
        if(ItemPromotion::where('inventory_item_id', $itemId)->where('status', 'active')->count() > 0){
            $can_create =  false;
        }else{
            $can_create =  true;
        }

        if (isset($permission[$pmodule . '___manage-promotions']) || $permission == 'superadmin') {
           
            $breadcum = [$title => route('maintain-items.index'), 'Listing' => ''];
            return view('admin.promotions.index', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'inventoryItem', 'promotions', 'can_create'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($itemId)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $breadcum = [$title => route('item-centre.show', $itemId), 'Create' => ''];
        $inventoryItems = WaInventoryItem::all();
        $promotionTypes = PromotionType::all();
        $promotionGroups = PromotionGroup::where('active', true)->latest()->get();
        $inventoryItem = WaInventoryItem::with('suppliers')->find($itemId);
        if (isset($permission[$this->pmodule . '___manage-promotions']) || $permission == 'superadmin') {
            return view('admin.promotions.create', compact('title', 'model', 'breadcum', 'inventoryItem', 'inventoryItems','promotionGroups','promotionTypes'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $itemId)
    {

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $breadcum = [$title => route('item-centre.show', $itemId), 'Create' => ''];

        // Enhanced validation for foreign key constraints
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'promotion_type_id' => 'required|exists:promotion_types,id',
            'supplier_id' => 'required|exists:wa_suppliers,id',
            'promotion_group_id' => 'nullable|exists:promotion_groups,id',
            'inventory_item' => 'nullable|exists:wa_inventory_items,id'
        ]);

        try{
        $inventoryItem = WaInventoryItem::with('supplier_data')->find($itemId);
        
        // Validate that the inventory item exists
        if (!$inventoryItem) {
            return redirect()->back()->withErrors(['errors' => 'Inventory item not found']);
        }
        
        // Validate user exists
        $user = getLoggeduserProfile();
        if (!$user || !$user->id) {
            return redirect()->back()->withErrors(['errors' => 'User profile not found']);
        }


        /*create a demand*/
            $demandCode = getCodeWithNumberSeries('DELTA');
            $delta = new WaDemand();
            $delta->demand_no = $demandCode;
            $delta->created_by = $user->id; // Use validated user ID
            $delta->wa_supplier_id = $request->supplier_id;
            $delta->demand_amount = 0;
            $delta->edited_demand_amount = 0;
            $delta->save();
            updateUniqueNumberSeries('DELTA',$demandCode);

            /*create promotion*/
            $itemPromotion = new ItemPromotion();
            $itemPromotion->inventory_item_id = $inventoryItem->id;
            $itemPromotion->promotion_type_id = $request->promotion_type_id;
            $itemPromotion->promotion_group_id = $request->promotion_group_id;
            $itemPromotion->wa_demand_id = $delta->id;
            $itemPromotion->supplier_id = $request->supplier_id;
            $itemPromotion->apply_to_split = $request->apply_to_split ?? false;
            $itemPromotion->initiated_by = $user->id; // Use validated user ID
            $itemPromotion->from_date = \Carbon\Carbon::parse($request->from_date)->toDateString();
            if($request->to_date){
                $itemPromotion->to_date = \Carbon\Carbon::parse($request->to_date)->toDateString();
            }
            
            $type = PromotionType::find($request->promotion_type_id);
            if (!$type) {
                return redirect()->back()->withErrors(['errors' => 'Invalid promotion type']);
            }
            
            if ($type->description == PromotionMatrix::BSGY->value)
            {
                // Validate promotion item exists for BSGY type
                if ($request->inventory_item && !WaInventoryItem::find($request->inventory_item)) {
                    return redirect()->back()->withErrors(['errors' => 'Promotion item not found']);
                }
                
                $itemPromotion->sale_quantity = $request->item_quantity;
                $itemPromotion->promotion_item_id = $request->inventory_item;
                $itemPromotion->promotion_quantity = $request->promotion_quantity;
            }
            if ($type->description == PromotionMatrix::PD->value)
            {
                $itemPromotion->current_price = $inventoryItem->selling_price;
                $itemPromotion->promotion_price = $request->promotion_price;
            }

            $itemPromotion->save();

//            dd($itemPromotion);
        return redirect()->route("item-centre.show", $itemId)->with('success', 'Promotions Created successfully' );


        }catch(\Throwable $e){
            return redirect()->back()->withErrors(['errors' => $e->getMessage()]);

        }
    }

    /**
     * Display the specified resource.
     */
    public function show($promotionId)
    {
       
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($promotionId)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $promotion = ItemPromotion::find($promotionId);
        $inventoryItems = WaInventoryItem::all();
        $inventoryItem = WaInventoryItem::with('suppliers')->find($promotion->inventory_item_id);
        $promotionTypes = PromotionType::all();
        $promotionGroups = PromotionGroup::latest()->get();
        $breadcum = [$title => route('item-centre.show', $promotion->inventory_item_id), 'Create' => ''];

        if (isset($permission[$this->pmodule . '___manage-promotions']) || $permission == 'superadmin') {
            return view( 'admin.promotions.edit', compact('title', 'model', 'breadcum', 'promotion','inventoryItems','inventoryItem','promotionTypes', 'promotionGroups'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $promotionId)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $promotion = ItemPromotion::find($promotionId);
        $breadcum = [$title => route('item-centre.show', $promotion->inventory_item_id), 'Create' => ''];
        try{

            $promotion->from_date = \Carbon\Carbon::parse($request->from_date)->toDateString();
            $promotion->to_date = \Carbon\Carbon::parse($request->to_date)->toDateString();
            $promotion->initiated_by = getLoggeduserProfile()->id;
            $type = PromotionType::find($request->promotion_type_id);
            if ($type->description == PromotionMatrix::BSGY->value)
            {
                $promotion->sale_quantity = $request->item_quantity;
                $promotion->promotion_item_id = $request->inventory_item;
                $promotion->promotion_quantity = $request->promotion_quantity;
            }
            if ($type->description == PromotionMatrix::PD->value)
            {
                $promotion->promotion_price = $request->promotion_price;
            }
            $promotion->save();
            return redirect()->route("item-centre.show", $promotion->inventory_item_id)->with('success', 'Promotion Updated successfully' );


        }catch(\Throwable $e){
            return redirect()->back()->withErrors(['errors' => $e->getMessage()]);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $promotionId)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $promotion = ItemPromotion::find($promotionId);
        $itemId = $promotion->inventory_item_id;
        $breadcum = [$title => route('item-centre.show', $itemId), 'Create' => ''];
        try{
            $promotion->delete();
        
        return redirect()->route("item-centre.show", $itemId)->with('success', 'Promotion Deleted successfully' );


        }catch(\Throwable $e){
            return redirect()->back()->withErrors(['errors' => $e->getMessage()]);

        }
    }

    public function block($promotionId){
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $promotion = ItemPromotion::find($promotionId);
        $itemId = $promotion->inventory_item_id;
        $breadcum = [$title => route('item-centre.show', $itemId), 'Create' => ''];
        try{
            $promotion->status =  'blocked';
            $promotion->save();
            
        
        return redirect()->route("item-centre.show", $itemId)->with('success', 'Promotion blocked successfully' );


        }catch(\Throwable $e){
            return redirect()->back()->withErrors(['errors' => $e->getMessage()]);

        }

    }
    public function unblock($promotionId){
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $basePath = $this->basePath;
        $promotion = ItemPromotion::find($promotionId);
        $itemId = $promotion->inventory_item_id;
        $breadcum = [$title => route('item-centre.show', $itemId), 'Create' => ''];
        try{
            $promotion->status =  'active';
            $promotion->save();
            
        
        return redirect()->route("item-centre.show", $itemId)->with('success', 'Promotion unblocked successfully' );


        }catch(\Throwable $e){
            return redirect()->back()->withErrors(['errors' => $e->getMessage()]);

        }

    }
    public function itemsWithPromotionsReport(Request $request){
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = 'promotion-items-report';
        $basePath = $this->basePath;
        $breadcum = [$title => route('items-with-discounts-reports'), 'Listing' => ''];
        $inventoryItems = WaInventoryItem::all();
        $query = "
        SELECT
            item_promotions.from_date as start_date,
            item_promotions.to_date as end_date,
            parent_item.stock_id_code as parent_stock_id_code,
            parent_item.title as parent_title,
            parent_pack_size.title as parent_pack_size,
            parent_item.selling_price as parent_price,
            item_promotions.sale_quantity,
            promotion_item.stock_id_code as promotion_item_stock_id_code,
            promotion_item.title as promotion_item_title,
            promotion_pack_size.title as promotion_pack_size,
            promotion_item.selling_price as promotion_item_price,
            item_promotions.promotion_quantity,
            users.name as user_name
        FROM
            item_promotions
        LEFT JOIN
            wa_inventory_items parent_item ON item_promotions.inventory_item_id = parent_item.id
        LEFT JOIN 
            pack_sizes parent_pack_size ON parent_item.pack_size_id = parent_pack_size.id
        LEFT JOIN
            wa_inventory_items promotion_item ON item_promotions.promotion_item_id = promotion_item.id
        LEFT JOIN 
            pack_sizes promotion_pack_size ON promotion_item.pack_size_id = promotion_pack_size.id
        LEFT JOIN
            users ON item_promotions.initiated_by = users.id        
        ";
        $bindings = [];
        if($request->item){
            $query.= " WHERE item_promotions.inventory_item_id = ? ";
            $bindings[] = $request->item;
        }
        $data  = DB::select($query, $bindings);

        if($request->type && $request->type == 'Download'){
            $export = new ItemsWithPromotionsExport(collect($data));
            return Excel::download($export, 'ItemPromotions.xlsx');
        }

        return view('admin.promotions.items_with_promotions_report', compact('title', 'model', 'breadcum', 'data', 'inventoryItems'));
    }
    public function promotionSalesReport(Request $request){
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = 'promotion-sales-report';
        $basePath = $this->basePath;
        $breadcum = [$title => route('sales-and-receivables-reports.promotion-sales-report'), 'Listing' => ''];
        $inventoryItems = WaInventoryItem::all();
        $query = DB::table('item_promotions')
        ->select(
            'item_promotions.from_date as start_date',
            'item_promotions.to_date as end_date',
            'parent_item.stock_id_code as parent_stock_id_code',
            'parent_item.title as parent_title',
            'parent_pack_size.title as parent_pack_size',
            'parent_item.selling_price as parent_price',
            'item_promotions.sale_quantity',
            DB::raw('(SELECT SUM(quantity) FROM wa_inventory_location_transfer_items WHERE wa_inventory_location_transfer_items.total_cost > 0 AND wa_inventory_location_transfer_items.wa_inventory_item_id = parent_item.id AND wa_inventory_location_transfer_items.created_at >= start_date AND wa_inventory_location_transfer_items.created_at <= end_date  ) as parent_sold_quantity'),
            'promotion_item.stock_id_code as promotion_item_stock_id_code',
            'promotion_item.title as promotion_item_title',
            'promotion_pack_size.title as promotion_pack_size',
            'promotion_item.selling_price as promotion_item_price',
            'item_promotions.promotion_quantity',
            DB::raw('(SELECT SUM(quantity) FROM wa_inventory_location_transfer_items WHERE wa_inventory_location_transfer_items.total_cost = 0 AND wa_inventory_location_transfer_items.wa_inventory_item_id = promotion_item.id AND wa_inventory_location_transfer_items.created_at >= start_date AND wa_inventory_location_transfer_items.created_at <= end_date) as promotion_sold_quantity'),
        )
        ->leftJoin('wa_inventory_items as parent_item', 'item_promotions.inventory_item_id', '=', 'parent_item.id')
        ->leftJoin('wa_inventory_item_suppliers', 'wa_inventory_item_suppliers.wa_inventory_item_id', '=', 'parent_item.id')
        ->leftJoin('pack_sizes as parent_pack_size', 'parent_item.pack_size_id', '=', 'parent_pack_size.id')
        ->leftJoin('wa_inventory_items as promotion_item', 'item_promotions.promotion_item_id', '=', 'promotion_item.id')
        ->leftJoin('pack_sizes as promotion_pack_size', 'promotion_item.pack_size_id', '=', 'promotion_pack_size.id');
    
    if($request->item){
        $query->where('item_promotions.inventory_item_id', '=', $request->item);
    }
    if($request->supplier_id){
        $query->where('wa_inventory_item_suppliers.wa_supplier_id', '=', $request->supplier_id);

    }
    
    $data = $query->get();
        if($request->type && $request->type == 'Download'){
            $export = new GeneralExcelExport(collect($data), ['START  DATE', 'END DATE', 'ITEM CODE', 'TITLE', 'PACK SIZE', 'PRICE', 'SALE QUANTITY','SOLD QUANTITY', 
        'PROMO ITEM CODE', 'PROMO ITEM', 'PACK SIZE', 'PRICE', 'QUANTITY', 'ISSUED QUANTITY']);
            return Excel::download($export, 'promotions-sales-summary.xlsx');
        }
        return view('admin.promotions.items_with_promotions_sales_report', compact('title', 'model', 'breadcum', 'data', 'inventoryItems'));

    }
}
