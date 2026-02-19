<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Status\ApprovalStatus;
use App\Exports\MaxStockExport;
use App\Exports\BulkPurchaseDataExport;
use App\Exports\StockMovesExport;
use App\Exports\WaStockMovesExport;
use App\ItemSupplierDemand;
use App\Model\WaInventoryItemRawMaterial;
use App\Models\WaInventoryItemPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WaInventoryItem;
use App\Model\WaLocationAndStore;
use App\Model\StockAdjustment;
use App\Model\WaAccountingPeriod;
use App\Model\WaInventoryPriceHistory;
use App\Model\WaInventoryLocationStockStatus;
use App\Model\WaInventoryLocationUom;
use App\Model\WaStockMove;
use App\Model\WaStockMove2;
use App\Model\WaNumerSeriesCode;
use App\Model\WaGlTran;
use App\Model\WaCategory;
use App\Model\WaSupplier;
use Illuminate\Support\Facades\File;
use App\Model\WaCategoryItemPrice;
use App\Model\PackSize;
use App\Model\WaInventoryAssignedItems;
use App\Model\WaInventoryCategory;
use App\Model\WaInventoryItemSupplierData;
use App\Model\WaPurchaseOrderItem;
use App\Models\WaInventoryItemApprovalStatus;
use App\Services\ExcelDownloadService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\{DB, Session, Validator};
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use App\Interfaces\Inventory\ApprovalItemInterface;
use App\Model\User;
use App\Model\TaxManager;
use App\Model\WaInventoryItemSupplier;
use App\Models\UpdateNewItemInventoryUtilityLog;
use Illuminate\Support\Str;
use App\Imports\SupplierCostImport;
use App\Interfaces\SmsService;
use App\Model\WaUnitOfMeasure;
use Throwable;

class InventoryItemController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;
    private ApprovalItemInterface $approvalRepository;

    public function __construct(Request $request, ApprovalItemInterface $approvalRepository, protected SmsService $smsService)
    {
        $this->model = 'maintain-items';
        $this->title = 'Maintain items';
        $this->pmodule = 'maintain-items';
        $this->approvalRepository = $approvalRepository;
    }

    public function assignInventoryItems($itemid)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $data = WaInventoryItem::with(['destinated_items.destinated_item'])->where('id', $itemid)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.maintaininvetoryitems.assignInventoryItems', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'data'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function inventoryDropdown(Request $request)
    {
        // dd($request->all());
        $data = WaInventoryItem::select([
            'id',
            'wa_unit_of_measure_id',
            DB::RAW('CONCAT(title," - ",stock_id_code) as text')
        ])->where(function ($e) use ($request) {
            if ($request->q) {
                $e->orWhere('title', 'LIKE', '%' . $request->q . '%');
                $e->orWhere('stock_id_code', 'LIKE', '%' . $request->q . '%');
            }
        })->where(function ($e) use ($request) {
            if ($request->id) {
                $e->where('id', '!=', $request->id);
            }
        })
            // ->limit(20)
            ->get();
        return response()->json($data);
    }

    public function index()
    {
        if (!can('view', $this->model)) {
            return redirect()->back()->withErrors(['errors' => pageRestrictedMessage()]);
        }

        $user = getLoggeduserProfile();
        $permission = $this->mypermissionsforAModule();

        $qohQuery = "SELECT 
                        SUM(qauntity)
                    FROM
                        `wa_stock_moves`
                    WHERE
                        `wa_inventory_item_id` = `wa_inventory_items`.`id`";

        if ($user->role_id == 152) {
            $qohQuery .= " AND wa_location_and_store_id = $user->wa_location_and_store_id";
        }

        if ($user->role_id != 1 && !isset($permission['maintain-items___view-per-branch'])) {
            $qohQuery .= " AND wa_location_and_store_id = $user->wa_location_and_store_id";
        }

        $qooQuery = "SELECT 
                        SUM(quantity)
                    FROM
                        `wa_purchase_order_items`
                            JOIN
                        `wa_purchase_orders` ON `wa_purchase_order_items`.`wa_purchase_order_id` = `wa_purchase_orders`.`id`
                            LEFT JOIN
                        `wa_grns` ON `wa_purchase_orders`.`id` = `wa_grns`.`wa_purchase_order_id`
                    WHERE
                        `wa_purchase_order_items`.`wa_inventory_item_id` = `wa_inventory_items`.`id`
                            AND `status` = 'APPROVED'
                            AND `is_hide` <> 'YES'
                            AND `wa_grns`.id IS NULL";

        if ($user->role_id == 152) {
            $qooQuery .= " AND wa_location_and_store_id = $user->wa_location_and_store_id";
        }

        if ($user->role_id != 1 && !isset($permission['maintain-items___view-per-branch'])) {
            $qooQuery .= " AND wa_location_and_store_id = $user->wa_location_and_store_id";
        }

        $branchId = request()->branch;
        $binId = request()->bin;
        
        $query = WaInventoryItem::query()
            ->select([
                'wa_inventory_items.*',
                DB::raw("($qohQuery) as qty_on_hand"),
                DB::raw("($qooQuery) as qty_on_order"),
            ])
            ->with([
                'category',
                'packSize',
                'competingBrand'
            ])
            ->where('item_type', '1')
            ->where('status', 1);

        if ($user->role_id ==152 && (!$branchId || !$binId)) {
            $query->when($user->role_id == 152, function ($query) use ($user) {
                $query->whereHas('bin_locations', function ($query) use ($user) {
                    $query->where('wa_inventory_location_uom.location_id', $user->wa_location_and_store_id)
                        ->where('wa_inventory_location_uom.uom_id', $user->wa_unit_of_measures_id);
                });
            });

            $branchId = $user->wa_location_and_store_id;
            $binId = $user->wa_unit_of_measures_id;
        } else {
            $query->when($branchId, fn ($query) => $query->whereHas('bin_locations', fn ($binLocations) => $binLocations->where('location_id', $branchId)));
            $query->when($binId, fn ($query) => $query->whereHas('bin_locations', fn ($binLocations) => $binLocations->where('uom_id', $binId)));
        }

        $query->when(request()->filled('category'), function ($query) {
            $query->where('wa_inventory_items.wa_inventory_category_id', request()->category);
        })
            ->when(request()->filled('supplier'), function ($query) {
                $query->whereHas('suppliers', function ($query) {
                    $query->where('wa_suppliers.id', request()->supplier);
                });
            })
            ->when(request()->filled('productId'), function ($query) {
                $query->where('wa_inventory_items.id', request()->productId);
            });


        if (request()->wantsJson()) {
            return DataTables::eloquent($query)
                ->addColumn('image', function ($item) {
                    if ($item->image) {
                        return '<img src="' . asset_public('uploads/inventory_items/' . $item->image) . '" width="50" alt="Item Image"/>';
                    }

                    return '';
                })
                ->editColumn('standard_cost', function ($item) {
                    return manageAmountFormat($item->standard_cost);
                })
                ->editColumn('price_list_cost', function ($item) {
                    return manageAmountFormat($item->price_list_cost);
                })
                ->editColumn('price_list_cost', function ($item) {
                    return manageAmountFormat($item->price_list_cost);
                })
                ->editColumn('last_grn_cost', function ($item) {
                    return manageAmountFormat($item->last_grn_cost);
                })
                ->editColumn('weighted_average_cost', function ($item) {
                    return manageAmountFormat($item->weighted_average_cost);
                })
                ->editColumn('qty_on_hand', function ($item) {
                    if (can('view-stock-status', 'maintain-items')) {
                        return view('admin.maintain_items.qoh-link', compact('item'));
                    }
                    return manageAmountFormat($item->qty_on_hand);
                })
                ->editColumn('qty_on_order', function ($item) {
                    if ($item->qty_on_order > 0) {
                        return view('admin.maintain_items.link', [
                            'url' => route('receive-purchase-order.index', ['item' => $item->id]),
                            'text' => manageAmountFormat($item->qty_on_order)
                        ]);
                    }

                    return manageAmountFormat($item->qty_on_order);
                })
                ->addColumn('actions', function ($item) {
                    return view('admin.maintain_items.actions', compact('item'));
                })
                ->rawColumns(['image', 'qty_on_hand', 'qty_on_order', 'actions'])
                ->make(true);
        }

        $breadcum = [
            'Maintain Items' => route('maintain-items.index')
        ];

        $bins = WaUnitOfMeasure::with('location.locationStore')
            ->get()
            ->map(function ($bin) {
                return [
                    'id' => $bin->id,
                    'title' => $bin->title,
                    'branch_id' => $bin->location && $bin->location->locationStore ? $bin->location->locationStore->id : null,
                ];
            });

        return view('admin.maintain_items.index', [
            'model' => $this->model,
            'title' => $this->title,
            'breadcum' => $breadcum,
            'categories' => WaInventoryCategory::get(),
            'suppliers' => WaSupplier::get(),
            'branches' => WaLocationAndStore::all(),
            'bins' => $bins,
            'branchId' => $branchId,
            'binId' => $binId,
        ]);
    }

    public function itemStockStatus()
    {
        $item = WaInventoryItem::where('stock_id_code', request()->item_code)->firstOrFail();

        $qohSub = WaStockMove::query()
            ->select([
                'wa_location_and_store_id',
                DB::raw('SUM(qauntity) as quantity')
            ])
            ->where('wa_inventory_item_id', $item->id)
            ->groupBy('wa_location_and_store_id');

        $locations = WaLocationAndStore::query()
            ->select([
                'wa_location_and_stores.id',
                'wa_location_and_stores.location_name',
                DB::raw('IFNULL(quantities.quantity, 0) AS qty_on_hand'),
                DB::raw('IFNULL(stock_tatus.max_stock, 0) AS max_stock'),
                DB::raw('IFNULL(stock_tatus.re_order_level, 0) AS re_order_level'),
            ])
            ->leftJoinSub($qohSub, 'quantities', 'quantities.wa_location_and_store_id', 'wa_location_and_stores.id')
            ->leftJoin('wa_inventory_location_stock_status as stock_tatus', function ($join) use ($item) {
                $join->on('stock_tatus.wa_location_and_stores_id', 'wa_location_and_stores.id')
                    ->where('wa_inventory_item_id', $item->id);
            })
            ->get();

        return response()->json([
            'success', true,
            'locations' => $locations,
            'total_qty_on_hand' => $locations->sum('qty_on_hand')
        ]);
    }

    public function datatable(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $branches = $this->getRestaurantList();
        $columns = [
            'stock_id_code',
            'title',
            'uom',
            'standard_cost',
            'qauntity',
            'qty_on_order'
        ];

        $totalData = WaInventoryItem::where('item_type', '1')->count();
        if (isset($request->status)) {
            $totalData = WaInventoryItem::where([['item_type', '1'], ['status', $request->status]])->count();
        }

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $user = getLoggeduserProfile();
        //if user is storekeeper
        if ($user->role_id == 152) {
            $location_and_store_id = $user->wa_location_and_store_id;
            $data_query = WaInventoryItem::select(
                'wa_inventory_categories.category_description',
                'wa_inventory_items.*',
                DB::raw(' (select sum(qauntity) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id and wa_stock_moves.wa_location_and_store_id = ' . $location_and_store_id . ') as item_total_qunatity'),
                DB::RAW(' (select count(wa_stock_moves.id) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id=wa_inventory_items.id and wa_stock_moves.wa_location_and_store_id = ' . $location_and_store_id . ') as item_count'),
                DB::RAW(' (SELECT SUM(items.quantity) FROM wa_purchase_order_items AS items JOIN  wa_purchase_orders AS orders ON orders.id = items.wa_purchase_order_id LEFT JOIN wa_grns AS grns ON grns.wa_purchase_order_id = items.wa_purchase_order_id WHERE items.wa_inventory_item_id = wa_inventory_items.id AND orders.status = \'APPROVED\' AND orders.is_hide <> \'Yes\' AND grns.grn_number IS NULL and orders.wa_location_and_store_id = ' . $location_and_store_id . ') as qty_on_order')
            )->withCount('inventory_item_suppliers')->with('pack_size', 'approvalStatus.approvalBy')
                ->join('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')
                ->leftjoin('wa_inventory_location_uom', 'wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')->where('item_type', '1')
                ->where('wa_inventory_location_uom.uom_id', $user->wa_unit_of_measures_id);
        } else {
            $data_query = WaInventoryItem::with('locationPrices')
                ->with('locationPrices.location')
                ->select(
                    'wa_inventory_categories.category_description',
                    'wa_inventory_items.*',
                    DB::RAW(' (select sum(qauntity) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id=wa_inventory_items.id) as item_total_qunatity'),
                    DB::RAW(' (select count(wa_stock_moves.id) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id=wa_inventory_items.id) as item_count'),
                    DB::RAW(' (SELECT SUM(items.quantity) FROM wa_purchase_order_items AS items JOIN  wa_purchase_orders AS orders ON orders.id = items.wa_purchase_order_id LEFT JOIN wa_grns AS grns ON grns.wa_purchase_order_id = items.wa_purchase_order_id WHERE items.wa_inventory_item_id = wa_inventory_items.id AND orders.status = \'APPROVED\' AND orders.is_hide <> \'Yes\' AND grns.grn_number IS NULL) as qty_on_order')
                )
                ->withCount('inventory_item_suppliers')
                ->with('pack_size', 'approvalStatus.approvalBy')
                ->join('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')
                ->where('item_type', '1');
        }

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $data_query = $data_query->where(function ($data_query) use ($search) {
                $data_query->where('stock_id_code', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('standard_cost', 'LIKE', "%{$search}%");
            });
        }

        if ($request->category_id) {
            $data_query = $data_query->where('wa_inventory_items.wa_inventory_category_id', $request->category_id);
        }
        if ($request->supplier_id) {
            $supplierItemIds = DB::table('wa_inventory_item_suppliers')->where('wa_supplier_id', $request->supplier_id)->pluck('wa_inventory_item_id')->toArray();
            $data_query = $data_query->whereIn('wa_inventory_items.id', $supplierItemIds);
        }

        if (!isset($request->status)) {
            $data_query = $data_query->where('wa_inventory_items.status', 1);
        }
        if (isset($request->status)) {
            $data_query = $data_query->where('wa_inventory_items.status', $request->status);
        }

        $data_query_count = $data_query;
        $totalFiltered = $data_query_count->count();
        $data_query = $data_query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = array();
        if (!empty($data_query)) {
            foreach ($data_query as $key => $row) {
                $user_link = '';
                $nestedData['stock_id_code'] = $row->stock_id_code;
                $nestedData['item_category'] = $row->category_description;
                $nestedData['title'] = $row->title;
                if ($row->image) {
                    $imageUrl = 'https://efficentrix.co.ke/public/uploads/inventory_items/' . $row->image;
                    $nestedData['image'] = '<img src="' . $imageUrl . '" width="50" alt="Item Image"/>';
                } else {
                    $nestedData['image'] = '';
                }
                $nestedData['uom'] = @$row->pack_size->title;
                // $nestedData['vortex_cost'] = manageAmountFormat($row->vortex_cost);
                $nestedData['standard_cost'] = manageAmountFormat($row->standard_cost);
                $nestedData['qauntity'] = manageAmountFormat($row->item_total_qunatity);
                $nestedData['qty_on_order'] = $row->qty_on_order > 0 ? '<a href="' . route('receive-purchase-order.index', ['item' => $row->id]) . '" target="_blank">' . manageAmountFormat($row->qty_on_order) . '</a>' : manageAmountFormat($row->qty_on_order);
                // if ($user->role_id == 152) {
                //     $nestedData['qauntity'] = manageAmountFormat(WaStockMove::where('stock_id_code', $row->stock_id_code)->where('wa_location_and_store_id', $user->wa_location_and_store_id)->sum('qauntity'));
                // } else {
                //     $nestedData['qauntity'] = manageAmountFormat(WaStockMove::where('stock_id_code', $row->stock_id_code)->sum('qauntity'));
                // }
                // $nestedData['vortex_price'] = manageAmountFormat($row->vortex_price);
                $nestedData['selling_price'] = manageAmountFormat($row->selling_price);

                $action_text = ($row->slug != 'mpesa' && (isset($permission[$pmodule . '___edit']) || $permission == 'superadmin')) ? buttonHtmlCustom('edit', route($model . '.edit', $row->slug)) : '';
                $link_popup = route('admin.table.adjust-item-stock-form', $row->slug);
                $link_popup2 = route('admin.table.adjust-category-price-form', $row->slug);
                if (!isset($request->status)) {
                    $action_text .= buttonHtmlCustom('stock_movements', route($model . '.stock-movements', $row->stock_id_code));
                }
                // $action_text .= buttonHtmlCustom('stock_movements_2', route($model . '.stock-movements-2', $row->stock_id_code));
                if (isset($permission[$pmodule . '___view-stock-status']) || $permission == 'superadmin' &&  !isset($request->status)) {
                    $action_text .= buttonHtmlCustom('stock_status', route($model . '.stock-status', $row->stock_id_code));
                }
                if (isset($permission[$pmodule . '___manage-item-stock']) || $permission == 'superadmin' && !isset($request->status)) {
                    $action_text .= view('admin.maintaininvetoryitems.popup_link', [
                        'link_popup' => $link_popup,
                        'id' => $row->id,
                        'type' => '1'
                    ]);
                }
                if ($row->item_count == 0 || $row->item_count == null) {
                    $action_text .= (isset($permission[$pmodule . '___delete']) || $permission == 'superadmin' && !isset($request->status)) ? buttonHtmlCustom('delete', route($model . '.destroy', $row->slug)) : '';
                }
                if (isset($permission[$pmodule . '___manage-category-pricing']) || $permission == 'superadmin' && !isset($request->status)) {
                    $action_text .= view('admin.maintaininvetoryitems.popup_link', [
                        'link_popup2' => $link_popup2,
                        'id' => $row->id,
                        'data' => $row,
                        'type' => '2'
                    ]);
                }
                if (isset($permission[$pmodule . '___maintain-purchasing-data']) || $permission == 'superadmin' && !isset($request->status)) {
                    if ($row->inventory_item_suppliers_count > 0) {
                        $action_text .= buttonHtmlCustom('purchaseData', route($model . '.purchaseData', $row->id));
                    }
                }
                $nestedData['action'] = $action_text;
                if (isset($permission[$pmodule . '___assign-inventory-items']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<a href="' . route($model . '.assignInventoryItems', $row->id) . '" title="Assign Inventory Items"><i class="fa fa-share-alt" aria-hidden="true"></i></a>';
                }
                if (isset($permission[$pmodule . '___price-change-history']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<span class="span-action"><a href="' . route($model . '.item_price_history_list', ['item' => $row->id]) . '" title="Price Change History"><i class="fa fa-history" aria-hidden="true"></i></a></span>';
                }
                if (isset($permission[$pmodule . '___update-bin-location']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<span class="span-action"><a href="' . route($model . '.stock-bin-location', ['stockIdCode' => $row->stock_id_code]) . '" title="Bin Location"><i class="fa fa-location-arrow" aria-hidden="true"></i></a></span>';
                }
                if (isset($permission[$pmodule . '___manage-discount']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<span class="span-action"><a href="' . route('discounts.listing', ['itemId' => $row->id]) . '" title="Manage Discounts"><i class="fas fa-tags" aria-hidden="true"></i></a></span>';
                }
                if (isset($permission[$pmodule . '___manage-promotions']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<span class="span-action"><a href="' . route('promotions.listing', ['itemId' => $row->id]) . '" title="Promotions"><i class="fas fa-flag" aria-hidden="true"></i></a></span>';
                }
                if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<span class="span-action"><a href="' . route($model . '.show', $row->slug) . '" title="View"><i class="fas fa-eye" aria-hidden="true"></i></a></span>';
                }
                if (isset($permission[$pmodule . '___route-pricing']) || $permission == 'superadmin' && !isset($request->status)) {
                    $nestedData['action'] .= '<span class="span-action"><a href="' . route('route.pricing.listing', ['itemId' => $row->id]) . '" title="Route Pricing"><i class="fas fa-route" aria-hidden="true"></i></a></span>';
                }
                if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin' && !isset($request->status)) {
                    $rowData = htmlspecialchars(json_encode($row)); // Encode $row data to JSON
                    $nestedData['action'] .= '<span class="span-action"><a href="#" class="open-modal" data-target="#price_locations" data-toggle="modal" data-row="' . $rowData . '" title="Location Price"><i class="fas fa-map-marker" aria-hidden="true"></i></a></span>';
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
        return response()->json($json_data);
    }

    public function datatableApproval(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $columns = [
            'stock_id_code',
            'title',
            'uom',
            'standard_cost',
            'qauntity',
            'qty_on_order'
        ];

        $approvalStatus = $this->getApprovalStatusFromSlug($request->approval_status);
        $totalData = WaInventoryItem::where([['item_type', '1'], ['approval_status', $approvalStatus]])->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $user = getLoggeduserProfile();
        //if user is storekeeper
        if ($user->role_id == 152) {
            $location_and_store_id = $user->wa_location_and_store_id;
            $data_query = WaInventoryItem::select(
                'wa_inventory_categories.category_description',
                'wa_inventory_items.*',
                DB::raw(' (select sum(qauntity) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id and wa_stock_moves.wa_location_and_store_id = ' . $location_and_store_id . ') as item_total_qunatity'),
                DB::RAW(' (select count(wa_stock_moves.id) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id=wa_inventory_items.id and wa_stock_moves.wa_location_and_store_id = ' . $location_and_store_id . ') as item_count'),
                DB::RAW(' (SELECT SUM(items.quantity) FROM wa_purchase_order_items AS items JOIN  wa_purchase_orders AS orders ON orders.id = items.wa_purchase_order_id LEFT JOIN wa_grns AS grns ON grns.wa_purchase_order_id = items.wa_purchase_order_id WHERE items.wa_inventory_item_id = wa_inventory_items.id AND orders.status = \'APPROVED\' AND orders.is_hide <> \'Yes\' AND grns.grn_number IS NULL and orders.wa_location_and_store_id = ' . $location_and_store_id . ') as qty_on_order')
            )->withCount('inventory_item_suppliers')->with('pack_size', 'approvalStatus.approvalBy')
                ->leftjoin('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')
                ->leftjoin('wa_inventory_location_uom', 'wa_inventory_items.id', '=', 'wa_inventory_location_uom.inventory_id')->where('item_type', '1')
                ->where('wa_inventory_location_uom.uom_id', $user->wa_unit_of_measures_id);
        } else {
            $data_query = WaInventoryItem::select(
                'wa_inventory_categories.category_description',
                'wa_inventory_items.*',
                DB::RAW(' (select sum(qauntity) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id=wa_inventory_items.id) as item_total_qunatity'),
                DB::RAW(' (select count(wa_stock_moves.id) from wa_stock_moves where wa_stock_moves.wa_inventory_item_id=wa_inventory_items.id) as item_count'),
                DB::RAW(' (SELECT SUM(items.quantity) FROM wa_purchase_order_items AS items JOIN  wa_purchase_orders AS orders ON orders.id = items.wa_purchase_order_id LEFT JOIN wa_grns AS grns ON grns.wa_purchase_order_id = items.wa_purchase_order_id WHERE items.wa_inventory_item_id = wa_inventory_items.id AND orders.status = \'APPROVED\' AND orders.is_hide <> \'Yes\' AND grns.grn_number IS NULL) as qty_on_order')
            )->withCount('inventory_item_suppliers')->with('pack_size', 'approvalStatus.approvalBy')
                ->leftjoin('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')->where('item_type', '1');
        }

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $data_query = $data_query->where(function ($data_query) use ($search) {
                $data_query->where('stock_id_code', 'LIKE', "%{$search}%")
                    ->orWhere('title', 'LIKE', "%{$search}%")
                    ->orWhere('standard_cost', 'LIKE', "%{$search}%");
            });
        }

        if ($request->category_id) {
            $data_query = $data_query->where('wa_inventory_items.wa_inventory_category_id', $request->category_id);
        }
        if ($request->supplier_id) {
            $supplierItemIds = DB::table('wa_inventory_item_suppliers')->where('wa_supplier_id', $request->supplier_id)->pluck('wa_inventory_item_id')->toArray();
            $data_query = $data_query->whereIn('wa_inventory_items.id', $supplierItemIds);
        }

        $approvalStatus = $this->getApprovalStatusFromSlug($request->approval_status);
        $data_query = $data_query->where('wa_inventory_items.approval_status', $approvalStatus);

        $data_query_count = $data_query;
        $totalFiltered = $data_query_count->count();
        $data_query = $data_query->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = array();

        if (!empty($data_query)) {
            foreach ($data_query as $key => $row) {
                if ($request->item || $request->user) {
                    if ($request->item == $row->id) {
                        $user_link = '';
                        $nestedData['requested_by'] = $row->approvalStatus->first()?->approvalBy?->name;
                        $nestedData['requested_on'] = date('d M, Y H:m', strtotime($row->approvalStatus->first()?->created_at));
                        $nestedData['stock_id_code'] = $row->stock_id_code;
                        $nestedData['item_category'] = $row->category_description;
                        $nestedData['title'] = $row->title;
                        $nestedData['uom'] = @$row->pack_size->title;
                        $nestedData['standard_cost'] = manageAmountFormat($row->standard_cost);
                        $nestedData['qauntity'] = manageAmountFormat($row->item_total_qunatity);
                        $nestedData['qty_on_order'] = $row->qty_on_order > 0 ? '<a href="' . route('receive-purchase-order.index', ['item' => $row->id]) . '" target="_blank">' . manageAmountFormat($row->qty_on_order) . '</a>' : manageAmountFormat($row->qty_on_order);
                        $nestedData['selling_price'] = manageAmountFormat($row->selling_price);
                        $nestedData['action'] = '<span class="span-action"><a href="' . route('admin.show.approval', $row->slug) . '" title="View"><i class="fas fa-eye" aria-hidden="true"></i></a></span>';

                        $data[] = $nestedData;
                    }
                    if ($request->user == $row->approvalStatus->first()?->approvalBy?->id) {
                        $user_link = '';
                        $nestedData['requested_by'] = $row->approvalStatus->first()?->approvalBy?->name;
                        $nestedData['requested_on'] = date('d M, Y H:m', strtotime($row->approvalStatus->first()?->created_at));
                        $nestedData['stock_id_code'] = $row->stock_id_code;
                        $nestedData['item_category'] = $row->category_description;
                        $nestedData['title'] = $row->title;
                        $nestedData['uom'] = @$row->pack_size->title;
                        $nestedData['standard_cost'] = manageAmountFormat($row->standard_cost);
                        $nestedData['qauntity'] = manageAmountFormat($row->item_total_qunatity);
                        $nestedData['qty_on_order'] = $row->qty_on_order > 0 ? '<a href="' . route('receive-purchase-order.index', ['item' => $row->id]) . '" target="_blank">' . manageAmountFormat($row->qty_on_order) . '</a>' : manageAmountFormat($row->qty_on_order);
                        $nestedData['selling_price'] = manageAmountFormat($row->selling_price);
                        $nestedData['action'] = '<span class="span-action"><a href="' . route('admin.show.approval', $row->slug) . '" title="View"><i class="fas fa-eye" aria-hidden="true"></i></a></span>';

                        $data[] = $nestedData;
                    }
                } else {
                    $user_link = '';
                    $nestedData['requested_by'] = $row->approvalStatus->first()?->approvalBy?->name;
                    $nestedData['requested_on'] = date('d M, Y H:m', strtotime($row->approvalStatus->first()?->created_at));
                    $nestedData['stock_id_code'] = $row->stock_id_code;
                    $nestedData['item_category'] = $row->category_description;
                    $nestedData['title'] = $row->title;
                    $nestedData['uom'] = @$row->pack_size->title;
                    $nestedData['standard_cost'] = manageAmountFormat($row->standard_cost);
                    $nestedData['qauntity'] = manageAmountFormat($row->item_total_qunatity);
                    $nestedData['qty_on_order'] = $row->qty_on_order > 0 ? '<a href="' . route('receive-purchase-order.index', ['item' => $row->id]) . '" target="_blank">' . manageAmountFormat($row->qty_on_order) . '</a>' : manageAmountFormat($row->qty_on_order);
                    $nestedData['selling_price'] = manageAmountFormat($row->selling_price);
                    $nestedData['action'] = '<span class="span-action"><a href="' . route('admin.show.approval', $row->slug) . '" title="View"><i class="fas fa-eye" aria-hidden="true"></i></a></span>';

                    $data[] = $nestedData;
                }
            }
        }
        $json_data = array(
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        );
        return response()->json($json_data);
    }

    public function postassignInventoryItems(Request $request, $id)
    {
        $user = getLoggeduserProfile();
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:wa_inventory_items,id|in:' . $id,
            'destination_item.*' => 'required|exists:wa_inventory_items,id',
            'conversion_factor.*' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return $request->ajax() ? response()->json([
                'result' => 0,
                'errors' => $validator->errors()
            ]) : redirect()->back()->withErrors($validator->errors())->withInput();
        }
        WaInventoryAssignedItems::where('wa_inventory_item_id', $request->id)->delete();
        $destination_item = [];
        $motherItem = WaInventoryItem::find($request->id);
        if ($request->destination_item && count($request->destination_item) > 0) {
            foreach ($request->destination_item as $key => $value) {
                $destination_item[] = [
                    'wa_inventory_item_id' => $request->id,
                    'destination_item_id' => $value,
                    'conversion_factor' => ($request->conversion_factor[$key] ?? NULL),
                ];

                $childItem = WaInventoryItem::find($value);
                //save child item history
                $childHistory = new WaInventoryPriceHistory();
                $childHistory->wa_inventory_item_id = $childItem->id;
                $childHistory->old_standard_cost =  $motherItem->standard_cost / $request->conversion_factor[$key];
                $childHistory->standard_cost = $childItem->standard_cost ?? 0;
                $childHistory->old_selling_price = $childItem->selling_price  ?? 0;
                $childHistory->selling_price = $childItem->selling_price  ?? 0;
                $childHistory->initiated_by = $user->id;
                $childHistory->approved_by = $user->id;
                $childHistory->status = 'Approved';
                $childHistory->created_at = date('Y-m-d H:i:s');
                $childHistory->updated_at = date('Y-m-d H:i:s');
                $childHistory->block_this = False;
                $childHistory->save();

                $childItem->standard_cost = $motherItem->standard_cost / $request->conversion_factor[$key];
                $childItem->save();
            }
            if (count($destination_item) > 0) {
                WaInventoryAssignedItems::insert($destination_item);
            }
        }
        return $request->ajax() ? response()->json([
            'result' => 1,
            'message' => 'Items Assigned successfully',
            'location' => route('item-centre.show', $motherItem->id)
        ]) : redirect()->route('item-centre.show', $motherItem->id)
            ->with('success', 'Items Assigned Successfully');
    }

    public function create()
    {
        $permission = $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___add']) || $permission == 'superadmin') {
            $title = 'Add ' . $this->title;
            $model = $this->model;
            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
            $all_taxes = $this->getAllTaxFromTaxManagers();
            $suppliers = WaSupplier::pluck('name', 'id')->toArray();
            $locations = WaLocationAndStore::pluck('location_name', 'id')->toArray();
            $PackSize = PackSize::pluck('title', 'id')->toArray();
            return view('admin.maintaininvetoryitems.create', compact('title', 'model', 'breadcum', 'all_taxes', 'locations', 'PackSize', 'suppliers'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function validate_first_step(Request $request, $id = "")
    {
        $validator = Validator::make($request->all(), [
            'stock_id_code' => ($id == "" ? "required" : "nullable") . '|unique:wa_inventory_items,stock_id_code' . $id,
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'wa_inventory_category_id' => 'required',
            'item_sub_category_id' => 'required',
            'suppliers.*' => 'required|exists:wa_suppliers,id',
            // 'suppliers.*' => 'required|array',
            'standard_cost' => 'required|numeric',
            // 'minimum_order_quantity'=>'required|numeric',
            'selling_price' => 'required|numeric',
            // 'profit_margin' => 'required|numeric'
            'percentage_margin' => 'required|numeric',
        ], [], [
            'suppliers.*' => 'Supplier' // Custom attribute name for error messages
        ]);
        return !$validator->fails() ? false : $validator->errors();
    }

    public function validate_second_step(Request $request, $image = "nullable")
    {
        $validator = Validator::make($request->all(), [
            'tax_manager_id' => 'required',
            'pack_size_id' => 'required',
            // 'store_location_id'=>'nullable',
            'alt_code' => 'nullable',
            'packaged_volume' => 'nullable',
            'gross_weight' => 'nullable',
            'net_weight' => 'nullable',
            'hs_code' => 'nullable',
            'restocking_method' => 'nullable',
            'image' => $image . '|image|mimes:jpeg,png,jpg,gif,svg'
        ]);
        return !$validator->fails() ? false : $validator->errors();
    }

    public function validate_third_step(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destination_item.*' => 'required|exists:wa_inventory_items,id',
            'conversion_factor.*' => 'required|numeric',
            'block_this' => 'nullable'
        ]);
        return !$validator->fails() ? false : $validator->errors();
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['current_step' => "required|in:1,2,3"]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }
            if ($request->current_step == 1 && $st_first = $this->validate_first_step($request)) {
                return response()->json(['result' => 0, 'errors' => $st_first]);
            }
            if ($request->current_step == 2 && $st_sec = $this->validate_second_step($request, 'required')) {
                return response()->json(['result' => 0, 'errors' => $st_sec]);
            }
            if ($request->current_step == 3 && $st_third = $this->validate_third_step($request)) {
                return response()->json(['result' => 0, 'errors' => $st_third]);
            }
            if ($request->current_step != 3) {
                return response()->json(['result' => 1, 'next_step' => $request->current_step + 1]);
            }
            $data = $request->all();

            if ($request->hasFile('image')) {
                $uploadPath = base_path('../public_html/uploads/inventory_items');
                if (!file_exists($uploadPath)) {
                    \Illuminate\Support\Facades\File::makeDirectory($uploadPath, 0755, true, true);
                }
                $file = $request->file('image');
                $fileName = time() . rand(0000000000, 9999999999) . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $fileName);

                $data['image'] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $fileName
                ];
            }

            $jsonData = json_encode($data);
            // dd($jsonData);
            $pendingNewApproval = new WaInventoryItemApprovalStatus();
            $pendingNewApproval->approval_by = getLoggeduserProfile()->id;
            $pendingNewApproval->status  = "Pending New Approval";
            $pendingNewApproval->changes = "[]";
            $pendingNewApproval->new_data = $jsonData;
            $pendingNewApproval->save();

            // return response()->json(['result' => 1, 'message' => 'Record added. Waiting Approval.', 'location' => route($this->model . '.index')]);
            return $this->handleResponse($request, ['result' => 1, 'message' => 'Record added. Waiting Approval.', 'location' => route('maintain-items.index')]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }
    protected function handleResponse(Request $request, array $response)
    {
        if ($request->expectsJson()) {
            return response()->json($response);
        }

        if ($response['result'] === 1) {
            return redirect()->route('maintain-items.index')->with('success', $response['message']);
        } else {
            return redirect()->route('maintain-items.index')->with('error', $response['message']);
        }
    }

    public function updateImagesManually(): JsonResponse
    {
        try {
            foreach (DB::table('wa_inventory_items')->get() as $record) {
                $item = WaInventoryItem::find($record->id);
                if (\Illuminate\Support\Facades\File::exists(base_path("../public_html/uploads/inventory_items/{$item->stock_id_code}.jpg"))) {
                    $item->image = "$item->stock_id_code.jpg";
                } else if (\Illuminate\Support\Facades\File::exists(base_path("../public_html/uploads/inventory_items/{$item->stock_id_code}.JPG"))) {
                    $item->image = "$item->stock_id_code.JPG";
                } else {
                    $item->image = null;
                }

                $item->save();
            }

            return $this->jsonify(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(["message" => $e->getMessage()], 500);
        }
    }

    public function _save_item(WaInventoryItem $row, Request $request)
    {
        $approvalStatus = $row->approval_status;
        $changes = [];
        if ($approvalStatus != ApprovalStatus::PendingNewApproval->value) {
            $changes = $this->getFieldChanges($row, $request);
            if (isset($row->oldSuppliers)) {
                unset($row->oldSuppliers);
            }
        }

        $row->stock_id_code = strtoupper($request->stock_id_code);
        $row->title = $request->title;
        $row->description = $request->description;
        if ($request->file('image')) {
            $file = $request->file('image');
            $fileName = time() . rand(111111111, 9999999999) . '.' . $file->getClientOriginalExtension();
            $uploadPath = base_path('../public_html/public/uploads/inventory_items');
            if (!file_exists($uploadPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($uploadPath, 0777, true, true);
            }
            $file->move($uploadPath, $fileName);
            $row->image = $fileName;
        }
        $row->wa_inventory_category_id = $request->wa_inventory_category_id;
        $row->item_sub_category_id = $request->item_sub_category_id;

        $row->minimum_order_quantity = 0; //$request->minimum_order_quantity;
        $row->wa_unit_of_measure_id = $request->wa_unit_of_measure_id;
        $row->tax_manager_id = $request->tax_manager_id;
        $row->hs_code = $request->hs_code;
        $row->showroom_stock = isset($request->showroom_stock) ? '1' : '0';
        $row->new_stock = isset($request->new_stock) ? '1' : '0';


        $row->pack_size_id = $request->pack_size_id ?? NULL;
        // $row->profit_margin = $request->profit_margin ?? NULL;
        $row->percentage_margin = $request->percentage_margin ?? NULL;
        $row->actual_margin = $request->actual_margin ?? NULL;
        $row->max_order_quantity = $request->max_order_quantity ?? NULL;
        $row->store_location_id = NULL; //$request->store_location_id ?? NULL;
        $row->alt_code = $request->alt_code ?? NULL;
        $row->packaged_volume = $request->packaged_volume ?? NULL;
        $row->gross_weight = $request->gross_weight ?? NULL;
        $row->net_weight = $request->net_weight ?? NULL;
        $row->hs_code = $request->hs_code ?? NULL;
        $row->block_this = $request->block_this ?? false;
        $row->restocking_method = $request->restocking_method ?? '2';
        $row->status = $request->status ?? 1;
        $row->margin_type = $request->margin_type ?? 1;
        $row->item_count = $request->item_count ?? null;

        $row->save();

        $destination_item = [];
        if ($request->destination_item && count($request->destination_item) > 0) {
            foreach ($request->destination_item as $key => $value) {
                $destination_item[] = [
                    'wa_inventory_item_id' => $row->id,
                    'destination_item_id' => $value,
                    'conversion_factor' => ($request->conversion_factor[$key] ?? NULL),
                ];
            }
            if (count($destination_item) > 0) {
                WaInventoryAssignedItems::insert($destination_item);
            }
        }
        if (isset($request->suppliers) && count($request->suppliers) > 0) {
            $suppliers = [];
            foreach ($request->suppliers as $key => $value) {
                $suppliers[] = [
                    'wa_inventory_item_id' => $row->id,
                    'wa_supplier_id' => $value,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            if (count($suppliers) > 0) {
                DB::table('wa_inventory_item_suppliers')->insert($suppliers);
            }
        }

        $this->approvalStatus(['item' => $row->id, 'status' => $approvalStatus, 'changes' => $changes]);
        return true;
    }

    public function show($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___view']) || $permission == 'superadmin') {
                $pmodule = $this->pmodule;
                $row = WaInventoryItem::with('category', 'sub_category', 'pack_size', 'getTaxesOfItem', 'approvalStatus', 'destinated_items')->whereSlug($slug)->first();

                if ($row) {
                    $title = 'View ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;

                    return view('admin.maintaininvetoryitems.show_item', compact('title', 'model', 'breadcum', 'row', 'permission', 'pmodule'));
                } else {
                    Session::flash('warning', 'Invalid Request');
                    return redirect()->back();
                }
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

    public function showApproval($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___view']) || $permission == 'superadmin') {
                $pmodule = $this->pmodule;
                $row = WaInventoryItem::with('category', 'sub_category', 'pack_size', 'getTaxesOfItem', 'approvalStatus', 'destinated_items')->whereSlug($slug)->first();

                if ($row) {
                    $title = 'View ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;

                    return view('admin.maintaininvetoryitems.approval.show_approval', compact('title', 'model', 'breadcum', 'row', 'permission', 'pmodule'));
                } else {
                    Session::flash('warning', 'Invalid Request');
                    return redirect()->back();
                }
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
    public function item_new_approval_show($id)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___view']) || $permission == 'superadmin') {
                $pmodule = $this->pmodule;
                $all_taxes = $this->getAllTaxFromTaxManagers();
                $suppliers = WaSupplier::pluck('name', 'id')->toArray();
                $packSizes = PackSize::pluck('title', 'id')->toArray();
                $row = WaInventoryItemApprovalStatus::find($id);

                if ($row) {
                    $title = 'View ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;
                    $new_data = json_decode($row->new_data);

                    return view('admin.maintaininvetoryitems.approval.show_new_approval', compact('title', 'model', 'breadcum', 'row', 'permission', 'pmodule', 'new_data', 'all_taxes', 'packSizes', 'suppliers'));
                } else {
                    Session::flash('warning', 'Invalid Request');
                    return redirect()->back();
                }
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
    public function item_new_approval_reject($id)
    {
        try {
            $rejectedStatus = WaInventoryItemApprovalStatus::find($id);
            $rejectedStatus->status = 'Rejected';
            $rejectedStatus->save();
            Session::flash('success', 'Item successfully rejected');
            return redirect()->route('item-new-approval');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }
    public function item_new_approval_approve(Request $request, $id)
    {
        try {

            $validator = Validator::make($request->all(), [
                // 'stock_id_code' => ($request->stock_id_code == "" ? "required" : "nullable") . '|unique:wa_inventory_items,stock_id_code' . $request->stock_id_code,
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'wa_inventory_category_id' => 'required',
                'item_sub_category_id' => 'required',
                'suppliers.*' => 'required|exists:wa_suppliers,id',
                // 'suppliers.*' => 'required|array',
                'standard_cost' => 'required|numeric',
                'price_list_cost' => 'required|numeric',
                // 'minimum_order_quantity'=>'required|numeric',
                'selling_price' => 'required|numeric',
                // 'profit_margin' => 'required|numeric'
                'tax_manager_id' => 'required',
                'pack_size_id' => 'required',
                // 'store_location_id'=>'nullable',
                'alt_code' => 'nullable',
                'packaged_volume' => 'nullable',
                'gross_weight' => 'nullable',
                'net_weight' => 'nullable',
                'hs_code' => 'nullable',
                'restocking_method' => 'nullable',
                // 'image' => $image . '|image'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }
            DB::beginTransaction();
            //check stock id code 
            $existingItem = WaInventoryItem::where('stock_id_code', $request->stock_id_code)->count();
            if ($existingItem > 0) {
                Session::flash('success', 'Item successfully approved');
                return redirect()->route('item-new-approval');
            } else {
                $row = new WaInventoryItem();
                $row->standard_cost = $request->standard_cost;
                $row->price_list_cost = $request->price_list_cost;
                $row->selling_price = $request->selling_price;
                $row->stock_id_code = $request->stock_id_code;
                $row->title = $request->title;
                $row->description = $request->description;
                $row->wa_inventory_category_id = $request->wa_inventory_category_id;
                $row->tax_manager_id = $request->tax_manager_id;
                $row->pack_size_id = $request->pack_size_id;
                $row->margin_type = $request->margin_type ?? 1;
                $row->percentage_margin = $request->percentage_margin;
                $row->actual_margin  = $request->actual_margin ?? null;
                $row->max_order_quantity = $request->max_order_quantity ?? null;
                $row->alt_code = $request->alt_code ?? 0;
                $row->hs_code = $request->hs_code ?? 0;
                $row->net_weight = $request->net_weight ?? 0;
                $row->gross_weight = $request->gross_weight ?? 0;
                $row->item_sub_category_id = $request->item_sub_category_id;
                $row->item_sub_category_id = $request->item_sub_category_id;
                $row->approval_status = 'Approved';

                $newApprovalStatus = WaInventoryItemApprovalStatus::find($id);
                $newData = json_decode($newApprovalStatus->new_data);
                if (isset($newData->image) && isset($newData->image->path)) {
                    $row->image = $newData->image->path;
                }

                $row->save();

                if ($request->suppliers) {
                    foreach ($request->suppliers as $supplier) {
                        $supplierData = new WaInventoryItemSupplier();
                        $supplierData->wa_inventory_item_id = $row->id;
                        $supplierData->wa_supplier_id = $supplier;
                        $supplierData->save();
                    }
                }

                $newApprovalStatus->status = 'Approved';
                $newApprovalStatus->wa_inventory_items_id = $row->id;
                $newApprovalStatus->save();

                DB::commit();
                //notify salesmen
                $category = WaInventoryCategory::find($row->wa_inventory_category_id);
                $users  = User::where('status', 1)->whereIn('role_id', [4, 169, 170, 181])->get();
                $message  = "New Item Alert:\nCODE: $row->stock_id_code\nTITLE: $row->title\nSELLING PRICE: $row->selling_price\nCATEGORY:$category->category_description";
                foreach ($users as $user) {
                    $this->smsService->sendMessage($message, $user->phone_number);
                }

                Session::flash('success', 'Item successfully approved');
                return redirect()->route('item-new-approval');
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back();
        }
    }


    public function approve_bulk_items(Request $request)
    {
        try {
            $selectedItemIds = $request->input('selectedItems');

            foreach ($selectedItemIds as $itemCode) {
                $inventoryItem = WaInventoryItem::where('stock_id_code', $itemCode)->first();

                if ($inventoryItem) {
                    $inventoryItem->update([
                        'approval_status' => 'Approved'
                    ]);

                    $approvalStatus = WaInventoryItemApprovalStatus::where('wa_inventory_items_id', $inventoryItem->id)->first();

                    if ($approvalStatus) {
                        $approvalStatus->update([
                            'status' => 'Approved'
                        ]);
                    }

                    $log = UpdateNewItemInventoryUtilityLog::where('wa_inventory_item_id', $inventoryItem->id)->first();
                    if($log){
                        $log->approved_by = auth()->id();
                        $log->save();
                    }                    
                }
            }

            return redirect()->route('item-new-approval');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }





    public function edit($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row = WaInventoryItem::withSum('stockMoves as qoh', 'qauntity')
                    ->whereSlug($slug)
                    ->firstOrFail();

                $inventory_item_suppliers = $row->suppliers->pluck('id')->toArray();

                if ($row) {
                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;
                    $suppliers = WaSupplier::pluck('name', 'id')->toArray();
                    $locations = WaLocationAndStore::pluck('location_name', 'id')->toArray();
                    $PackSize = PackSize::pluck('title', 'id')->toArray();
                    $all_taxes = $this->getAllTaxFromTaxManagers()->filter(fn ($tax, $id) => $tax != 'VAT 8%');
                    return view('admin.maintaininvetoryitems.edit', compact('title', 'model', 'breadcum', 'row', 'all_taxes', 'locations', 'PackSize', 'suppliers'));
                } else {
                    Session::flash('warning', 'Invalid Request');
                    return redirect()->back();
                }
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


    public function update(Request $request, $slug)
    {
        $row = WaInventoryItem::whereSlug($slug)->first();
        $destination_item = [];
        if ($request->destination_item && count($request->destination_item) > 0) {
            WaInventoryAssignedItems::where('wa_inventory_item_id', $row->id)->delete();
            foreach ($request->destination_item as $key => $value) {
                $destination_item[] = [
                    'wa_inventory_item_id' => $row->id,
                    'destination_item_id' => $value,
                    'conversion_factor' => ($request->conversion_factor[$key] ?? NULL),
                ];
            }
            if (count($destination_item) > 0) {
                WaInventoryAssignedItems::insert($destination_item);
            }
        }

        try {
            
            if ($row->status && !request()->status) {
                $qoh = DB::table('wa_stock_moves')->where('wa_inventory_item_id', $row->id)->sum('qauntity');
                if ($qoh != 0) {
                    return response()->json(['result' => -1, 'message' => 'The item has a QOH of ' . $qoh . '. It cannot retire.']);
                }
            }

            $validator = Validator::make($request->all(), ['current_step' => "required|in:1,2,3"]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'errors' => $validator->errors()]);
            }
            if ($request->current_step == 1 && $st_first = $this->validate_first_step($request, ',' . $row->id)) {
                return response()->json(['result' => 0, 'errors' => $st_first]);
            }
            if ($request->current_step == 2 && $st_sec = $this->validate_second_step($request)) {
                return response()->json(['result' => 0, 'errors' => $st_sec]);
            }
            if ($request->current_step == 3 && $st_third = $this->validate_third_step($request)) {
                return response()->json(['result' => 0, 'errors' => $st_third]);
            }
            if ($request->current_step != 3) {
                return response()->json(['result' => 1, 'next_step' => $request->current_step + 1]);
            }


            $check = DB::transaction(function ($e) use ($request, $row) {
                $oldSuppliers = DB::table('wa_inventory_item_suppliers')->where('wa_inventory_item_id', $row->id)->get();
                $row->oldSuppliers = $oldSuppliers->pluck('wa_supplier_id')->toArray();

                $status = ApprovalStatus::PendingEditApproval->value;
                $row->approval_status = $status;
                $changes = $this->getFieldChanges($row, $request);


                //check changed items
                $percentageMarginChanged = false;
                $statusChanged = false;
                $categoryChanged = false;
                $subCategoryChanged = false;
                $taxManagerChanged = false;
                $packSizeChanged = false;
                $netWeightChanged = false;
                $grossWeightChanged = false;
                $itemCountChanged = false;
                $maxOrderQuantityCountChanged = false;
                $marginTypeChanged = false;
                $actualMarginChanged = false;


                foreach ($changes as $change) {
                    if (isset($change['Percentage Margin'])) {
                        $percentageMarginChanged = true;
                    }
                    if (isset($change['Actual Margin'])) {
                        $actualMarginChanged = true;
                    }
                    if (isset($change['Status'])) {
                        $statusChanged = true;
                    }
                    if (isset($change['Category'])) {
                        $categoryChanged = true;
                    }
                    if (isset($change['Subcategory'])) {
                        $subCategoryChanged = true;
                    }
                    if (isset($change['Tax Manager'])) {
                        $taxManagerChanged = true;
                    }
                    if (isset($change['Pack Size'])) {
                        $packSizeChanged = true;
                    }
                    if (isset($change['Net Weight'])) {
                        $netWeightChanged = true;
                    }
                    if (isset($change['Gross Weight'])) {
                        $grossWeightChanged = true;
                    }
                    if (isset($change['Item Count'])) {
                        $itemCountChanged = true;
                    }
                    if (isset($change['Max Order Quantity'])) {
                        $maxOrderQuantityCountChanged = true;
                    }
                    if (isset($change['Margin Type'])) {
                        $marginTypeChanged = true;
                    }
                }


                if ($request->file('image')) {
                    
                    $file = $request->file('image');
                    $fileName = time() . rand(111111111, 9999999999) . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/inventory_items/'), $fileName);
                    $row->image = $fileName;
                }
                
                if ($marginTypeChanged || $percentageMarginChanged || $actualMarginChanged || $statusChanged  || $categoryChanged || $subCategoryChanged || $taxManagerChanged || $packSizeChanged || $netWeightChanged || $grossWeightChanged || $itemCountChanged || $maxOrderQuantityCountChanged) {
                    $new_data = $request->all();
                    $this->approvalStatus(['item' => $row->id, 'status' => $status, 'changes' => $changes, 'new_data' => $new_data]);

                    if (isset($row->oldSuppliers)) {
                        unset($row->oldSuppliers);
                    }
                    //                    dd($row);
                    $row->save();
                    return [true, 'Record updated. Waiting Approval.'];
                } else {
                    $row->title = $request->title;
                    $row->description = $request->description;
                    $row->approval_status = 'Approved';
                    
                    // Update Suppliers
                    DB::table('wa_inventory_item_suppliers')->where('wa_inventory_item_id', $row->id)->delete();
                    if (isset($request->suppliers) && count($request->suppliers) > 0) {
                        $suppliers = [];
                        foreach ($request->suppliers as $key => $value) {
                            $suppliers[] = [
                                'wa_inventory_item_id' => $row->id,
                                'wa_supplier_id' => $value,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                        }
                        if (count($suppliers) > 0) {
                            DB::table('wa_inventory_item_suppliers')->insert($suppliers);
                        }
                    }


                    if (isset($row->oldSuppliers)) {
                        unset($row->oldSuppliers);
                    }
                    $row->save();
                    return [true, 'Record updated Successful.'];
                }
            });
            if (!$check[0]) {
                return response()->json(['result' => -1, 'message' => 'Something went wrong']);
            }
            return response()->json(['result' => 1, 'message' => $check[1], 'location' => route($this->model . '.index')]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }


    public function update_approved_items($data, $item)
    {
        try {
            $row = WaInventoryItem::find($item);
            $request = new Request();
            $request->merge($data);
            $check = DB::transaction(function ($e) use ($request, $row) {
                // WaInventoryAssignedItems::where('wa_inventory_item_id', $row->id)->delete();
                $oldSuppliers = DB::table('wa_inventory_item_suppliers')->where('wa_inventory_item_id', $row->id)->get();
                $row->oldSuppliers = $oldSuppliers->pluck('wa_supplier_id')->toArray();
                DB::table('wa_inventory_item_suppliers')->where('wa_inventory_item_id', $row->id)->delete();
                // Reasign The Current Stock Code;
                $request['stock_id_code'] = $row->stock_id_code;

                $row->approval_status = ApprovalStatus::Approved->value;
                $this->_save_item($row, $request);
                return true;
            });

            return response()->json(['result' => 1, 'message' => 'Record updated. Waiting Approval.', 'location' => route($this->model . '.index')]);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            return response()->json(['result' => -1, 'message' => $msg]);
        }
    }


    public function destroy($item)
    {
        $item = WaInventoryItem::findOrFail($item);

        try {

            if ($item->stockMoves()->count() != 0) {
                Session::flash('warning', 'Item can not be deleted');

                return redirect()->back();
            }
            $item->delete();
            Session::flash('success', 'Deleted successfully.');

            return redirect()->back();
        } catch (\Exception $e) {
            Session::flash('warning', 'Invalid Request');

            return redirect()->back();
        }
    }

    public function standardCost(Request $request)
    {

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = 'Standard Cost';
        // $model = $this->model;
        $model = 'single-price-change';
        $suppliers = DB::table('wa_suppliers')->select('id', 'name')->get();
        $inventoryItems = WaInventoryItem::all();

        if (isset($permission[$pmodule . '___manage-standard-cost']) || $permission == 'superadmin') {
            if (isset($_GET['block_all'])) {
                DB::table('wa_inventory_items')->update(['block_this' => 1]);
                Session::flash('success', 'All Inventory items are blocked successfully.');
                return redirect()->route($this->model . '.standard.cost');
            }

            if (isset($_GET['un_block_all'])) {
                DB::table('wa_inventory_items')->update(['block_this' => 0]);
                Session::flash('success', 'All Inventory items are blocked successfully.');
                return redirect()->route($this->model . '.standard.cost');
            }

            $lists = DB::table('wa_inventory_items')->limit(4000);
            if ($request->item) {
                $lists = $lists->where('id', $request->item);
            }
            if ($request->supplier) {
                $supplierItemIds = DB::table('wa_inventory_item_suppliers')->where('wa_supplier_id', $request->supplier)->pluck('wa_inventory_item_id')->toArray();
                $lists->whereIn('id', $supplierItemIds);
            }

            $lists = $lists->get();

            $breadcum = [$title => route('maintain-items.standard.cost'), 'Listing' => ''];
            return view('admin.maintaininvetoryitems.standardCost', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'suppliers', 'inventoryItems'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function editStandardCost($slug)
    {
        try {
            $permission = $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___manage-standard-cost']) || $permission == 'superadmin') {
                $row = WaInventoryItem::whereSlug($slug)->first();
                if ($row) {
                    $this->title = 'Standard Cost';
                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.standard.cost'), 'Edit' => ''];
                    $model = 'single-price-change';
                    return view('admin.maintaininvetoryitems.editStandardCost', compact('title', 'model', 'breadcum', 'row'));
                } else {
                    Session::flash('warning', 'Invalid Request');
                    return redirect()->back();
                }
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

    public function updateStandardCost(Request $request, $slug)
    {
        try {
            $row = WaInventoryItem::whereSlug($slug)->first();
            $validator = Validator::make($request->all(), [
                'stock_id_code' => 'required|unique:wa_inventory_items,stock_id_code,' . $row->id,
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {
                $pending_history = WaInventoryPriceHistory::where('wa_inventory_item_id', $row->id)->where('status', 'Pending')->first();
                if ($pending_history) {
                    throw new \Exception("Price change approval is already in queue!");
                }
                $history = new WaInventoryPriceHistory();
                $history->wa_inventory_item_id = $row->id;
                $history->old_standard_cost = $row->standard_cost;
                $history->standard_cost = $request->standard_cost;
                $history->old_selling_price = $row->selling_price;
                $history->selling_price = $request->selling_price ?? $row->selling_price;

                $history->initiated_by = getLoggeduserProfile()->id;
                $history->status = 'Pending';
                $history->created_at = date('Y-m-d H:i:s');
                $history->updated_at = date('Y-m-d H:i:s');
                $history->block_this = $request->block_this ? True : False;

                $history->save();

                Session::flash('success', 'Price change request added successfully.');
                return redirect()->route($this->model . '.standard.cost');
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }

    public function supplier_stock_movements($StockIdCode, Request $request)
    {
        $this->model = 'maintain-suppliers';
        return $this->stockMovements($StockIdCode, $request);
    }

    public function stockMovements($StockIdCode, Request $request)
    {
        // dd($request->all());
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $formurl = "stock-movements";
        $location = WaLocationAndStore::query();
        $user = getLoggeduserProfile();
        if ($user->role_id != 1) {
            $location = $location->where('wa_branch_id', $user->restaurant_id);
        }
        $location = $location->get();

        $inventoryItem = WaInventoryItem::where('stock_id_code', $StockIdCode)->first();

        $lists = WaStockMove::with(['getRelatedUser', 'getLocationOfStore', 'getInventoryItemDetail'])
            ->select([
                '*',
                \DB::RAW('
            (CASE WHEN grn_type_number = 4 THEN (SELECT wa_pos_cash_sales_items.selling_price FROM wa_pos_cash_sales_items where wa_pos_cash_sales_items.wa_pos_cash_sales_id = wa_stock_moves.wa_pos_cash_sales_id AND wa_stock_moves.wa_inventory_item_id = wa_pos_cash_sales_items.wa_inventory_item_id LIMIT 1)
            WHEN grn_type_number = 51 THEN (SELECT wa_internal_requisition_items.selling_price FROM wa_internal_requisition_items where wa_internal_requisition_items.wa_internal_requisition_id = wa_stock_moves.wa_internal_requisition_id AND wa_stock_moves.wa_inventory_item_id = wa_internal_requisition_items.wa_inventory_item_id LIMIT 1)
            ELSE selling_price END
            ) as selling_price
            ')
            ]);

        if ($request->from && $request->to) {
            $lists->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
        }
        if ($request->location) {
            $lists->where('wa_location_and_store_id', $request->location);
        }

        if ($request->move_type) {
            if ($request->move_type == 'adjustment') {
                $lists->whereNot('stock_adjustment_id', null);
            } else if ($request->move_type == 'cash-sales') {
                $lists->where('document_no', 'like', '%CS%');
            } else if ($request->move_type == 'delivery-note') {
                $lists->whereNot('wa_inventory_location_transfer_id', null);
            } else if ($request->move_type == 'ingredients-booking') {
                $lists->whereNot('ordered_item_id', null);
            } else if ($request->move_type == 'internal-requisition-store-c') {
                $lists->where('document_no', 'like', '%IRSC%');
            } else if ($request->move_type == 'purchase') {
                $lists->whereNot('wa_purchase_order_id', null);
            } else if ($request->move_type == 'recieve-stock-store-c') {
                $lists->where('document_no', 'like', '%RSSC%');
            } else if ($request->move_type == 'return-from-store') {
                $lists->where('document_no', 'like', '%RFS%');
            } else if ($request->move_type == 'return') {
                $lists->where('document_no', 'like', '%RTN%');
            } else if ($request->move_type == 'sales-invoice') {
                $lists->where(function ($query) {
                    $query->whereNot('wa_internal_requisition_id', null)
                        ->orWhere('document_no', 'like', '%INV%')
                        ->orWhere('document_no', 'like', '%CS%');
                });
            } else if ($request->move_type == 'stock-break') {
                $lists->where('document_no', 'like', '%STB%');
            } else if ($request->move_type == 'transfer') {
                $lists->where('document_no', 'like', '%MARCH24%');
            }
        }

        $lists = $lists->where('stock_id_code', $StockIdCode)->orderBy('id', 'asc')->get();

        // if ($request->type == 'filter') {
        // } else {
        //     $lists = $lists->where('stock_id_code', $StockIdCode)->orderBy('id', 'DESC')->limit(20)->get();
        //     $lists = $lists->sort();
        // }


        $row = WaInventoryItem::where('stock_id_code', $StockIdCode)->first();
        $breadcum = [$title => route($model . '.index'), 'Stock Movement' => '', $StockIdCode => ''];
        if ($request->type == 'pdf') {

            $firstQoh = WaStockMove::where(function ($w) use ($request) {
                $w->where('created_at', '<', $request->from . ' 00:00:00');
                if ($request->location) {
                    $w->where('wa_location_and_store_id', $request->location);
                }
            })
                ->where('stock_id_code', $StockIdCode)->orderBy('id', 'DESC')->first();
            $currentLocation = WaLocationAndStore::where(['id' => $request->location])->first();
            $pdf = \PDF::loadView('admin.maintaininvetoryitems.stockmovement_pdf', compact('firstQoh', 'currentLocation', 'request', 'row', 'location', 'title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'StockIdCode'));
            $report_name = 'Stock-Card-' . date('Y_m_d_H_i_A');
            // return $pdf->stream();
            return $pdf->download($report_name . '.pdf');
        }

        if ($request->type == 'print') {
            $firstQoh = WaStockMove::where(function ($w) use ($request) {
                $w->where('created_at', '<', $request->from . ' 00:00:00');
                if ($request->location) {
                    $w->where('wa_location_and_store_id', $request->location);
                }
            })
                ->where('stock_id_code', $StockIdCode)->orderBy('id', 'DESC')->first();
            $currentLocation = WaLocationAndStore::where(['id' => $request->location])->first();
            return view('admin.maintaininvetoryitems.stockmovement_pdf', compact('firstQoh', 'currentLocation', 'request', 'row', 'location', 'title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'StockIdCode'));
        }

        if ($request->type == 'Excel') {
            $data = [];
            foreach ($lists as $list) {
                $payload = [
                    'date' => date('d-m-Y H:i:s', strtotime(@$list->created_at)),
                    'user' => ucfirst(@$list->getRelatedUser->name),
                    'store-location' => isset($list->getLocationOfStore->location_name) ? ucfirst($list->getLocationOfStore->location_name) : '',
                    'quantity' => $list->qauntity,
                    'qty-in' => (($list->qauntity >= 0) ? +$list->qauntity : NULL),
                    'qty-out' => (($list->qauntity < 0) ? -$list->qauntity : NULL),
                    'new-qoh' => @$list->new_qoh,
                    'selling-price' => manageAmountFormat($list->selling_price),
                    'refrence' => @$list->refrence,
                    'document-no' => @$list->document_no,
                    'type' => getStockMoveType($list)
                ];
                $data[] = $payload;
            }
            // echo "<pre>";print_r($payload);die();
            $itemTitle = WaInventoryItem::where('stock_id_code', $StockIdCode)->first()->title ?? '-';
            $export = new StockMovesExport(collect($data));
            return Excel::download($export, "$StockIdCode-" . str($itemTitle)->slug() . "-stocmoves.xlsx");
        }
        if ($request->type == 'Stock Moves') {
            $query = "
            SELECT
                wa_stock_moves.created_at AS date,
                wa_stock_moves.stock_id_code,
                wa_inventory_items.title,
                document_no,
                refrence,
                qauntity,
                new_qoh,
                wa_stock_moves.standard_cost,
                wa_stock_moves.price,
                wa_stock_moves.selling_price,
                wa_stock_moves.total_cost,
                users.name
            FROM
                wa_stock_moves
            LEFT JOIN wa_inventory_items ON wa_stock_moves.wa_inventory_item_id = wa_inventory_items.id
            LEFT JOIN users ON wa_stock_moves.user_id = users.id
            ";

            $bindings = [];

            if ($request->from && $request->to) {
                $query .= " WHERE wa_stock_moves.created_at BETWEEN ? AND ?";
                $bindings[] = $request->from . ' 00:00:00';
                $bindings[] = $request->to . ' 23:59:59';
            }
            $exportData = [];
            $rows = DB::cursor($query, $bindings);
            $data = collect($rows)->map(function ($row) {
                $row->type = getStockMoveType($row);
                return $row;
            });

            $export = new WaStockMovesExport(collect($data->all()));
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', '300');
            return Excel::download($export, 'Stock-Movements.xlsx');
        }

        return view('admin.maintaininvetoryitems.stockmovement', compact('location', 'title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'StockIdCode', 'formurl', 'inventoryItem'));
    }


    public function stockMovements2($StockIdCode, Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $formurl = "stock-movements-2";
        $location = WaLocationAndStore::where(['wa_branch_id' => getLoggeduserProfile()->restaurant_id])->get();
        $lists = WaStockMove2::with(['getRelatedUser', 'getLocationOfStore'])->select([
            '*',
            \DB::RAW('
            (CASE WHEN grn_type_number = 4 THEN (SELECT wa_pos_cash_sales_items.selling_price FROM wa_pos_cash_sales_items where wa_pos_cash_sales_items.wa_pos_cash_sales_id = wa_stock_moves_2.wa_pos_cash_sales_id AND wa_stock_moves_2.wa_inventory_item_id = wa_pos_cash_sales_items.wa_inventory_item_id LIMIT 1)
            WHEN grn_type_number = 51 THEN (SELECT wa_internal_requisition_items.selling_price FROM wa_internal_requisition_items where wa_internal_requisition_items.wa_internal_requisition_id = wa_stock_moves_2.wa_internal_requisition_id AND wa_stock_moves_2.wa_inventory_item_id = wa_internal_requisition_items.wa_inventory_item_id LIMIT 1)
            ELSE selling_price END
            ) as selling_price
            ')
        ])->where(function ($w) use ($request) {
            if ($request->from && $request->to) {
                $w->whereBetween('created_at', [$request->from . ' 00:00:00', $request->to . ' 23:59:59']);
            }
            if ($request->location) {
                $w->where('wa_location_and_store_id', $request->location);
            }
        });
        if (($request->from && $request->to) || $request->location) {
            $lists = $lists->where('stock_id_code', $StockIdCode)->orderBy('id', 'asc')->get();
        } else {
            $lists = $lists->where('stock_id_code', $StockIdCode)->orderBy('id', 'DESC')->limit(20)->get();
            $lists = $lists->sort();
        }

        $row = WaInventoryItem::where('stock_id_code', $StockIdCode)->first();
        $breadcum = [$title => route($model . '.index'), 'Stock Movement' => '', $StockIdCode => ''];
        if ($request->type == 'pdf') {
            $firstQoh = WaStockMove2::where(function ($w) use ($request) {
                $w->where('created_at', '<', $request->from . ' 00:00:00');
                if ($request->location) {
                    $w->where('wa_location_and_store_id', $request->location);
                }
            })
                ->where('stock_id_code', $StockIdCode)->orderBy('id', 'DESC')->first();
            $currentLocation = WaLocationAndStore::where(['id' => $request->location])->first();
            $pdf = \PDF::loadView('admin.maintaininvetoryitems.stockmovement_pdf', compact('firstQoh', 'currentLocation', 'request', 'row', 'location', 'title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'StockIdCode'));
            $report_name = 'Stock-Card-' . date('Y_m_d_H_i_A');
            // return $pdf->stream();
            return $pdf->download($report_name . '.pdf');
        }
        if ($request->type == 'print') {
            $firstQoh = WaStockMove2::where(function ($w) use ($request) {
                $w->where('created_at', '<', $request->from . ' 00:00:00');
                if ($request->location) {
                    $w->where('wa_location_and_store_id', $request->location);
                }
            })
                ->where('stock_id_code', $StockIdCode)->orderBy('id', 'DESC')->first();
            $currentLocation = WaLocationAndStore::where(['id' => $request->location])->first();
            return view('admin.maintaininvetoryitems.stockmovement_pdf', compact('firstQoh', 'currentLocation', 'request', 'row', 'location', 'title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'StockIdCode'));
        }
        return view('admin.maintaininvetoryitems.stockmovement', compact('location', 'title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'StockIdCode', 'formurl'));
    }

    public function stockStatus($StockIdCode)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $user = getLoggeduserProfile();
        $inventory = WaInventoryItem::where('stock_id_code', $StockIdCode)->first();
        $location_list = WaLocationAndStore::select([
            'wa_location_and_stores.*',
            'wa_inventory_location_stock_status.max_stock',
            'wa_inventory_location_stock_status.re_order_level'
        ])->where('is_physical_store', '1')
            ->leftJoin('wa_inventory_location_stock_status', function ($e) use ($inventory) {
                $e->on('wa_location_and_stores.id', '=', 'wa_inventory_location_stock_status.wa_location_and_stores_id')
                    ->where('wa_inventory_location_stock_status.wa_inventory_item_id', $inventory->id);
            })->groupBy('wa_location_and_stores.id')->get();

        $lists = DB::table('wa_stock_moves')
            ->where('wa_stock_moves.stock_id_code', $StockIdCode)
            ->whereIn('wa_stock_moves.wa_location_and_store_id', $location_list->pluck('id')->toArray())
            ->select('wa_stock_moves.wa_location_and_store_id', DB::raw('SUM(`qauntity`) as total_quantity'))
            ->groupBy('wa_stock_moves.wa_location_and_store_id')
            ->get();

        $storeBiseQty = [];
        foreach ($lists as $list) {
            $storeBiseQty[$list->wa_location_and_store_id] = $list->total_quantity;
        }

        $lists = $location_list;
        $breadcum = [$title => route($model . '.index'), 'Stock Status' => '', $StockIdCode => ''];
        return view('admin.maintaininvetoryitems.stockstatus', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'storeBiseQty', 'inventory', 'user'));
    }

    public function stockBinLocation($StockIdCode)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $inventory = WaInventoryItem::where('stock_id_code', $StockIdCode)->first();
        $location_list = WaLocationAndStore::with(['bin_locations'])->select([
            'wa_location_and_stores.*',
            'wa_inventory_location_uom.uom_id',
        ])->where('is_physical_store', '1')
            ->leftJoin('wa_inventory_location_uom', function ($e) use ($inventory) {
                $e->on('wa_location_and_stores.id', '=', 'wa_inventory_location_uom.location_id')
                    ->where('wa_inventory_location_uom.inventory_id', $inventory->id);
            })->groupBy('wa_location_and_stores.id')->get();
        $lists = $location_list;
        $breadcum = [$title => route($model . '.index'), 'Bin Location' => '', $StockIdCode => ''];
        return view('admin.maintaininvetoryitems.stockBinLocation', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission', 'inventory'));
    }

    public function updateBinLocation(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        if (isset($permission[$pmodule . '___update-bin-location']) || $permission == 'superadmin') {
            $validator = Validator::make($request->all(), [
                'inventory_id' => 'required|exists:wa_inventory_items,id',
                'uom_id.*' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'message' => $validator->errors()]);
            }
            $check = DB::transaction(function () use ($request) {
                $location_list = WaLocationAndStore::with(['bin_locations'])->select([
                    'wa_location_and_stores.*',
                    'wa_inventory_location_uom.uom_id',
                ])->where('is_physical_store', '1')
                    ->leftJoin('wa_inventory_location_uom', function ($e) use ($request) {
                        $e->on('wa_location_and_stores.id', '=', 'wa_inventory_location_uom.location_id')
                            ->where('wa_inventory_location_uom.inventory_id', $request->inventory_id);
                    })->groupBy('wa_location_and_stores.id')->get();
                WaInventoryLocationUom::where('inventory_id', $request->inventory_id)->delete();
                foreach ($location_list as $key => $location) {
                    if (isset($request->uom_id[$location->id])) {
                        $row = new WaInventoryLocationUom();
                        $row->inventory_id = $request->inventory_id;
                        $row->location_id = $location->id;
                        $row->created_at = date('Y-m-d H:i:s');
                        $row->updated_at = date('Y-m-d H:i:s');
                        $row->uom_id = $request->uom_id[$location->id];
                        $row->save();
                    }
                }
                return true;
            });
            if ($check) {
                return response()->json([
                    'result' => 1,
                    'message' => 'Bin Location updated successfully',
                    'location' => route('item-centre.show', $request->inventory_id)
                ]);
            }
        }
        return response()->json(['result' => -1, 'message' => 'Something went wrong']);
    }

    public function updateStockStatus(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        if (isset($permission[$pmodule . '___item_price_pending_list']) || $permission == 'superadmin') {
            $validator = Validator::make($request->all(), [
                'inventory_id' => 'required|exists:wa_inventory_items,id',
                'max_stock.*' => 'required|numeric',
                're_order_level.*' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json(['result' => 0, 'message' => $validator->errors()]);
            }

            $check = DB::transaction(function () use ($request) {
                $location_list = WaLocationAndStore::all();
                foreach ($location_list as $key => $location) {
                    $row = WaInventoryLocationStockStatus::where('wa_inventory_item_id', $request->inventory_id)->where('wa_location_and_stores_id', $location->id)->first();

                    if ($row) {
                        $row->updated_at = date('Y-m-d H:i:s');
                        $row->max_stock = $request->max_stock[$location->id];
                        $row->re_order_level = $request->re_order_level[$location->id];
                        $row->save();
                    } else {
                        $row = new WaInventoryLocationStockStatus();
                        $row->wa_inventory_item_id = $request->inventory_id;
                        $row->wa_location_and_stores_id = $location->id;
                        $row->created_at = date('Y-m-d H:i:s');
                        $row->max_stock = $request->max_stock[$location->id];
                        $row->re_order_level = $request->re_order_level[$location->id];
                        $row->save();
                    }
                }
                return true;
            });
            if ($check) {
                return response()->json([
                    'result' => 1,
                    'message' => 'Stock details updated successfully',
                    'location' => route('item-centre.show', $request->inventory_id)
                ]);
            }
        }
        return response()->json(['result' => -1, 'message' => 'Something went wrong']);
    }

    public function adjustItemStockForm($slug = '')
    {
        $item_row = WaInventoryItem::with(['getUnitOfMeausureDetail'])->where('slug', $slug)->first();
        $locations = WaLocationAndStore::getLocationList();
        return view('admin.maintaininvetoryitems.adjust_item_form', compact('item_row', 'locations'));
    }


    public function adjustCategoryPriceForm($slug = '')
    {
        $item_row = WaInventoryItem::with(['getTaxesOfItem'])->where('slug', $slug)->first();
        $categories = WaCategory::get();
        return view('admin.maintaininvetoryitems.adjust_category_price_form', compact('item_row', 'categories'));
    }

    public function getAvailableQuantityAjax(Request $request)
    {
        if (isset($request->store_c)) {
            $available_quantity = getItemAvailableQuantity_C($request->stock_id_code, $request->location_id);
        } else {
            $available_quantity = getItemAvailableQuantity($request->stock_id_code, $request->location_id);
        }
        return json_encode(['available_quantity' => $available_quantity]);
    }

    public function manageCategoryPrice(Request $request)
    {
        //echo "<pre>"; print_r($request->all()); die;
        $itemid = $request->get('item_id');
        foreach ($request->get('category_id') as $key => $val) {
            $checkexisting = WaCategoryItemPrice::where('item_id', $itemid)->where('category_id', $val)->count();
            if ($checkexisting == 0) {
                $catprice = new WaCategoryItemPrice();
            } else {
                $catprice = WaCategoryItemPrice::where('item_id', $itemid)->where('category_id', $val)->first();
            }
            $catprice->item_id = $itemid;
            $catprice->price = $request->get('category_price')[$key];
            $catprice->category_id = $val;
            $catprice->save();
        }
        Session::flash('success', 'Category Item Price Saved Successfully');
        return redirect()->route($this->model . '.index');
    }

    public function manualManageStock(Request $request)
    {
        DB::beginTransaction();
        try {
            $items = DB::table('wa_inventory_items')->get();
            $logged_user_profile = getLoggeduserProfile();
            foreach ($items as $item) {
                $code = getCodeWithNumberSeries('ITEM ADJUSTMENT');
                $entity = new StockAdjustment();
                $entity->user_id = $logged_user_profile->id;
                $entity->item_id = $item->id;
                $entity->wa_location_and_store_id = 37;
                $entity->adjustment_quantity = 2500;
                $entity->comments = "Manual adjustment for stock taking";
                $entity->item_adjustment_code = $code;
                $entity->save();

                updateUniqueNumberSeries('ITEM ADJUSTMENT', $code);

                $series_module = WaNumerSeriesCode::where('module', 'ITEM ADJUSTMENT')->first();
                $stockMove = new WaStockMove();
                $stockMove->user_id = $logged_user_profile->id;
                $stockMove->stock_adjustment_id = $entity->id;
                $stockMove->restaurant_id = $logged_user_profile->restaurant_id;
                $stockMove->wa_location_and_store_id = $entity->wa_location_and_store_id;
                $stockMove->wa_inventory_item_id = $item->id;
                $stockMove->standard_cost = $item->standard_cost;
                $stockMove->qauntity = 2500;
                $stockMove->new_qoh = 2500;
                $stockMove->stock_id_code = $item->stock_id_code;
                $stockMove->grn_type_number = $series_module->type_number;
                $stockMove->document_no = $code;
                $stockMove->grn_last_nuber_used = $series_module->last_number_used;
                $stockMove->price = $item->standard_cost;
                $stockMove->refrence = $entity->comments;
                $stockMove->save();
            }

            DB::commit();
            return $this->jsonify(['message' => 'success'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->jsonify(['message' => $e->getMessage(), 'trace' => $e->getTrace()], 500);
        }
    }

    public function stockManage(Request $request)
    {
        $item_row = WaInventoryItem::where('id', $request->item_id)->first();
        $adjustment_quantity = $request->adjustment_quantity;
        $current_available_quantity = getItemAvailableQuantity($request->stock_id_code, $request->wa_location_and_store_id);

        $new_quantity = $current_available_quantity + $adjustment_quantity;


        if ($new_quantity < 0) {
            $error = "Item No $item_row->stock_id_code does not have enough stock.";
            Session::flash('warning', $error);
            return redirect()->back();
        }


        $logged_user_profile = getLoggeduserProfile();
        $entity = new StockAdjustment();
        $entity->user_id = $logged_user_profile->id;
        $entity->item_id = $request->item_id;
        $entity->wa_location_and_store_id = $request->wa_location_and_store_id;
        $entity->adjustment_quantity = $request->adjustment_quantity;
        $entity->comments = $request->comments;
        $entity->item_adjustment_code = $request->item_adjustment_code;
        $entity->save();

        // $series_module = WaNumerSeriesCode::where('module','GRN')->first();
        $series_module = $item_adj = WaNumerSeriesCode::where('module', 'ITEM ADJUSTMENT')->first();
        $WaAccountingPeriod = WaAccountingPeriod::where('is_current_period', '1')->first();
        // $grn_number = getCodeWithNumberSeries('GRN');
        $dateTime = date('Y-m-d H:i:s');

        $stockMove = new WaStockMove();
        $stockMove->user_id = $logged_user_profile->id;
        $stockMove->stock_adjustment_id = $entity->id;
        $stockMove->restaurant_id = $logged_user_profile->restaurant_id;
        $stockMove->wa_location_and_store_id = $entity->wa_location_and_store_id;
        $stockMove->wa_inventory_item_id = $item_row->id;
        $stockMove->standard_cost = $item_row->standard_cost;
        $stockMove->qauntity = $adjustment_quantity;
        $stockMove->new_qoh = ($item_row->getAllFromStockMoves->where('wa_location_and_store_id', @$entity->wa_location_and_store_id)->sum('qauntity') ?? 0) + $stockMove->qauntity;
        $stockMove->stock_id_code = $item_row->stock_id_code;
        $stockMove->grn_type_number = $series_module->type_number;
        $stockMove->document_no = $request->item_adjustment_code;
        $stockMove->grn_last_nuber_used = $series_module->last_number_used;
        $stockMove->price = $item_row->standard_cost;
        $stockMove->refrence = $entity->comments;
        $stockMove->save();

        /*$dr = new WaGlTran();
        $dr->stock_adjustment_id = $entity->id;
        $dr->grn_type_number = $series_module->type_number;
        $dr->transaction_type = $item_adj->description;
        $dr->transaction_no = $request->item_adjustment_code;
        $dr->grn_last_used_number = $series_module->last_number_used;
        $dr->trans_date = $dateTime;
        $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;
        $dr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;

        $dr->account = $item_row->getInventoryCategoryDetail->getStockGlDetail ? $item_row->getInventoryCategoryDetail->getStockGlDetail->account_code : null;
        $dr->amount = abs($item_row->standard_cost * $adjustment_quantity);
        if ($adjustment_quantity < '0') {
            //             $dr->amount = '-'.abs($item_row->standard_cost * $adjustment_quantity);
            $dr->account = $item_row->getInventoryCategoryDetail?->getusageGlDetail?->account_code;
        }
        $dr->narrative = $item_row->stock_id_code . '/' . $item_row->title . '/' . $item_row->standard_cost . '@' . $adjustment_quantity;
        $dr->save();

        $dr = new WaGlTran();
        $dr->stock_adjustment_id = $entity->id;
        $dr->grn_type_number = $series_module->type_number;
        $dr->grn_last_used_number = $series_module->last_number_used;
        $dr->transaction_type = $item_adj->description;
        $dr->transaction_no = $request->item_adjustment_code;
        $dr->trans_date = $dateTime;
        $dr->restaurant_id = getLoggeduserProfile()->restaurant_id;

        $dr->period_number = $WaAccountingPeriod ? $WaAccountingPeriod->period_no : null;
        $dr->account = $item_row->getInventoryCategoryDetail->getPricevarianceGlDetail ? $item_row->getInventoryCategoryDetail->getPricevarianceGlDetail->account_code : null;
        $tamount = $item_row->standard_cost * $adjustment_quantity;

        $dr->amount = '-' . abs($tamount);
        if ($adjustment_quantity < '0') {
            //           $dr->amount = abs($item_row->standard_cost * $adjustment_quantity);
            $dr->account = $item_row->getInventoryCategoryDetail?->getStockGlDetail?->account_code;
        }
        $dr->narrative = $item_row->stock_id_code . '/' . $item_row->title . '/' . $item_row->standard_cost . '@' . $adjustment_quantity;
        $dr->save();*/


        updateUniqueNumberSeries('ITEM ADJUSTMENT', $request->item_adjustment_code);
        // updateUniqueNumberSeries('GRN',$grn_number);
        Session::flash('success', 'Processed Successfully');
        return redirect()->route($this->model . '.index');
    }

    public function stockMovementGlEntries($stock_move_id, $stock_id_code)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $data = WaGlTran::where('stock_adjustment_id', $stock_move_id)->orderBy('id', 'desc')->get();
        //echo "<pre>"; print_r($data); die;
        $negativeAMount = WaGlTran::where('stock_adjustment_id', $stock_move_id)->where('amount', '<', '0')->sum('amount');
        $positiveAMount = WaGlTran::where('stock_adjustment_id', $stock_move_id)->where('amount', '>', '0')->sum('amount');


        $breadcum = [$title => route($model . '.index'), 'Stock Movement' => route($model . '.stock-movements', $stock_id_code), 'GL Entries' => ''];
        return view('admin.maintaininvetoryitems.gl_entries', compact('title', 'data', 'model', 'breadcum', 'stock_id_code', 'pmodule', 'permission', 'negativeAMount', 'positiveAMount'));
    }

    public function exportCategoryPrice(Request $request)
    {
        //   echo "dfs"; die;

        $data_query = WaInventoryItem::select('wa_inventory_items.id', 'wa_inventory_categories.id as cat_id', 'wa_inventory_categories.category_description', 'wa_inventory_items.*')->with('getUnitOfMeausureDetail', 'getAllFromStockMoves', 'location')
            ->join('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')
            ->where('wa_inventory_items.wa_inventory_category_id', $request->wa_inventory_category_id)
            //        ->limit(50)
            ->get();
        $filetype = "xlsx";
        $mixed_array = $data_query;
        $export_array = [];
        $file_name = 'Item Category Price';
        $counter = 1;

        $pricecat = WaCategory::get();
        $headings = [];
        foreach ($pricecat as $key => $val) {
            $headings[$key] = $val->title;
        }
        $data = array('ID', 'Stock ID', 'Description', 'Category', 'Standard Cost', 'Selling Price', 'VAT', 'Gross weight', 'Bin Location', 'Store Location', 'HS Code');
        $export_array = [];

        $final_amount = [];


        foreach ($mixed_array as $item) {

            $prices = [];
            foreach ($pricecat as $key => $val) {
                $prices[$key] = WaCategoryItemPrice::getitemcatprice($item->id, $val->id);
            }

            $final_amount[] = 0;

            $export_arrays = [
                $item->id,
                $item->stock_id_code,
                $item->description,
                $item->wa_inventory_category_id,
                // $item->vortex_cost,
                $item->standard_cost,
                // $item->vortex_price,
                $item->selling_price,
                number_format($item->standard_cost != 0 ? ((($item->selling_price - $item->standard_cost) / $item->standard_cost) * 100) : 0, 2),
                $item->tax_manager_id,
                $item->gross_weight,
                $item->wa_unit_of_measure_id,
                $item->store_location_id,
                $item->hs_code,
            ];

            $export_array[] = array_merge($export_arrays, $prices);


            $counter++;
        }
        return ExcelDownloadService::download($file_name, collect($export_array), ['ID', 'STOCK ID CODE', 'TITLE', 'CATEGORY ID', 'STANDARD COST', 'STANDARD PRICE', 'PERCENTAGE MARGIN', 'TAX MANAGER ID', 'GROSS WEIGHT', 'BIN', 'STORE LOCATION', 'HS CODE']);
    }

    public function downloadExcelFile($data, $type, $file_name)
    {


        // return \Excel::download(function ($excel) use ($data) {
        //     $excel->sheet('mySheet', function ($sheet) use ($data) {
        //         $sheet->fromArray($data);
        //         foreach ($data as $record) {
        //             $i = 'A';
        //             foreach ($record as $key => $records) {
        //                 Log::info("this is from the records array");
        //                 Log::info($records);
        //                 if ($key > 4) {
        //                     $sheet->cell($i . '2', function ($cell) {
        //                         $cell->setBackground('#FFFF00');
        //                     });
        //                 }
        //                 $sheet->getStyle($i . '2')->getFont()
        //                     ->setBold(true);
        //                 $i++;
        //             }
        //         }
        //     });
        // },$file_name.'.'.$type);


    }

    public function importexcelforitempriceupdate(Request $request)
    {
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle);
            $data = array_combine($header, $row);
        } else {
            return redirect()->back()->with('danger', 'Please provide an excel file.');
        }
    }

    public function oldimportexcelforitempriceupdate(Request $request)
    {

        if ($request->hasFile('excel_file')) {
            $path = $request->file('excel_file')->getRealPath();

            Excel::import($path, function ($reader) use (&$excel) {
                $objExcel = $reader->getExcel();
                $sheet = $objExcel->getSheet(0);
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $alphabet = range('A', 'Z');
                $highestColumnInNum = array_search($highestColumn, $alphabet);
                $excel = [];
                $rown = $highestColumnInNum - 5;
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = $sheet->rangeToArray(
                        'A' . $row . ':' . $highestColumn . $row,
                        NULL,
                        TRUE,
                        FALSE
                    );

                    $excel[] = $rowData[0];
                }
            });

            $allCategories = \App\Model\WaInventoryCategory::get();
            $data = [];
            $heading = [];
            foreach ($excel as $key => $val) {
                if ($key == 1) {
                    $heading = $val;
                }
                if ($key > 1) {
                    $data[$key]['item_id'] = $val[0];
                    $data[$key]['category'] = (int)$val[2];
                    $data[$key]['standard_cost'] = (int)@$val[3];
                    $data[$key]['selling_price'] = (int)@$val[4];
                    $data[$key]['tax_manager_id'] = (int)@$val[5];
                    $data[$key]['gross_weight'] = (int)@$val[6];
                    $data[$key]['wa_unit_of_measure_id'] = (int)@$val[7];
                    $data[$key]['store_location_id'] = (int)@$val[8];
                    $data[$key]['hs_code'] = @$val[9];
                    foreach ($val as $k => $vals)
                        if ($k > 8 && $heading[$k] != "") {
                            $data[$key][$heading[$k]] = ($vals != "") ? $vals : "";
                        }
                }
            }
            //        echo "<pre>"; print_r($data); die;

            $final = [];
            foreach ($data as $key => $vals) {
                $i = 0;
                $itemid = WaInventoryItem::where('stock_id_code', $vals['item_id'])->first();
                foreach ($vals as $k => $val) {
                    if ($i > 0) {
                        if ($val != "") {
                            $categoryid = WaCategory::where('title', $k)->first();
                            if ($itemid && $categoryid) {
                                $checkexisting = WaCategoryItemPrice::where('item_id', $itemid->id)->where('category_id', $categoryid->id)->first();
                                if (!$checkexisting) {
                                    $catprice = new WaCategoryItemPrice();
                                } else {
                                    $catprice = $checkexisting;
                                }
                                $catprice->item_id = $itemid;
                                $catprice->price = $val;
                                $catprice->category_id = $categoryid->id;
                                $catprice->save();
                                // $final[] = [$categoryid , $itemid];
                            }
                        }
                    }
                    $i++;
                }
                if ($itemid) {
                    // $final[] = ['' , $itemid];
                    $itemid->standard_cost = $vals['standard_cost'] ?? $itemid->standard_cost;
                    $itemid->selling_price = $vals['selling_price'] ?? $itemid->selling_price;
                    $itemid->tax_manager_id = $vals['tax_manager_id'] ?? $itemid->tax_manager_id;
                    $itemid->wa_unit_of_measure_id = $vals['wa_unit_of_measure_id'] ?? $itemid->wa_unit_of_measure_id;
                    $itemid->wa_inventory_category_id = $vals['category'] ?? $itemid->wa_inventory_category_id;
                    $itemid->store_location_id = $vals['store_location_id'] ?? $itemid->store_location_id;
                    $itemid->gross_weight = $vals['gross_weight'] ?? $itemid->gross_weight;
                    $itemid->hs_code = $vals['hs_code'] ?? $itemid->hs_code;
                    $itemid->save();
                }
            }

            Session::flash('success', 'Category Item Price Imported Successfully');
            return redirect()->route($this->model . '.index');
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->route($this->model . '.index');
        }
    }

    public function downloadInvetoryitems(Request $request)
    {
        try {
            $data_query = DB::table('wa_inventory_items')
                ->select(
                    'wa_inventory_items.title as title',
                    'wa_inventory_items.stock_id_code as stock_id_code',
                    'wa_inventory_categories.category_description as category',
                    'pack_sizes.title as pack_size',
                    'wa_inventory_items.price_list_cost',
                    'wa_inventory_items.last_grn_cost',
                    'wa_inventory_items.weighted_average_cost',
                    'wa_inventory_items.standard_cost',
                    'wa_inventory_items.selling_price',
                    'wa_inventory_items.actual_margin',
                    // DB::RAW(' (select sum(qauntity) from wa_stock_moves where wa_stock_moves.stock_id_code=wa_inventory_items.stock_id_code) as item_total_quantity'),
                    DB::RAW(' (SELECT SUM(qauntity) FROM wa_stock_moves WHERE wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code'
                        . ($request->branch ? ' AND wa_stock_moves.wa_location_and_store_id = ' . (int)$request->branch : '')
                        . ') as item_total_quantity'),
                    'tax_managers.title as tax_manager',
                    'tax_managers.tax_value as tax_value',
                    'wa_inventory_items.image',
                    'wa_inventory_items.status as retired',
                    DB::RAW("(SELECT GROUP_CONCAT(wa_suppliers.name SEPARATOR ', ') 
                        FROM wa_inventory_item_suppliers 
                        LEFT JOIN wa_suppliers ON wa_inventory_item_suppliers.wa_supplier_id = wa_suppliers.id 
                        WHERE wa_inventory_item_suppliers.wa_inventory_item_id = wa_inventory_items.id
                        ) AS suppliers"),
                    DB::RAW("(SELECT GROUP_CONCAT(users.name SEPARATOR ', ')
                        FROM wa_inventory_item_suppliers
                        LEFT JOIN wa_user_suppliers ON wa_inventory_item_suppliers.wa_supplier_id = wa_user_suppliers.wa_supplier_id
                        LEFT JOIN users ON  wa_user_suppliers.user_id = users.id
                        WHERE wa_inventory_item_suppliers.wa_inventory_item_id = wa_inventory_items.id
                        ) AS users"),

                )
                ->leftJoin('wa_inventory_categories', 'wa_inventory_items.wa_inventory_category_id', 'wa_inventory_categories.id')
                ->leftJoin('pack_sizes', 'pack_sizes.id', 'wa_inventory_items.pack_size_id')
                ->leftJoin('tax_managers', 'wa_inventory_items.tax_manager_id', 'tax_managers.id');
            if ($request->category) {
                $data_query = $data_query->where('wa_inventory_items.wa_inventory_category_id', $request->category);
            }
            if ($request->branch) {
                $data_query = $data_query->addSelect(
                    DB::RAW("(SELECT wa_unit_of_measures.title
                                  FROM wa_inventory_location_uom
                                  LEFT JOIN wa_unit_of_measures ON wa_unit_of_measures.id = wa_inventory_location_uom.uom_id
                                  WHERE wa_inventory_location_uom.inventory_id = wa_inventory_items.id
                                  AND wa_inventory_location_uom.location_id = $request->branch
                                  ) AS bin_location")
                );
            }

            if ($request->bin) {
                $data_query->leftJoin('wa_inventory_location_uom', 'wa_inventory_location_uom.inventory_id', 'wa_inventory_items.id')
                    ->where('wa_inventory_location_uom.uom_id', '=', $request->bin);
            }
            
            $data_query = $data_query->get();
            if (!empty($data_query)) {
                foreach ($data_query as $row) {
                    $arrays[] = [
                        'Stock Id Code' => (string)($row->stock_id_code),
                        'Title' => $row->title,
                        'Item Category' => $row->category ?? '',
                        'Quantity' => (string)(@$row->item_total_quantity ?? 0),
                    ];
                }
            }
            $headers = ['STOCK ID CODE', 'TITLE', 'CATEGORY', 'QUANTITY'];

            return ExcelDownloadService::download('inventory-items' . date('Y-m-d-H-i-s'), collect($arrays), $headers);
        } catch (\Exception $th) {
            $request->session()->flash('danger', $th->getMessage());
            return redirect()->back();
        }
    }

    public function showBomMaterials($id)
    {
        $pmodule = $this->pmodule;
        $model = $this->model;
        $title = $this->title;
        $breadcum = [$title => route($model . '.index'), 'BOM' => ''];

        $inventoryItem = WaInventoryItem::with('bom')->select(['id', 'title'])->find($id);
        if (!$inventoryItem) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        $title = "$title BOM";
        $bom = $inventoryItem->bom;
        $bomPayload = [];
        foreach ($bom as $bomItem) {
            $rawMaterial = WaInventoryItem::find($bomItem->raw_material_id);
            $bomPayload[] = [
                'id' => $bomItem->id,
                'title' => $rawMaterial->title,
                'quantity' => $bomItem->quantity,
                'unit_cost' => $rawMaterial->standard_cost,
                'stock_cost' => round($bomItem->quantity * $rawMaterial->standard_cost, 2),
                'notes' => $bomItem->notes
            ];
        }

        return view('admin.maintaininvetoryitems.bom.index', compact('pmodule', 'model', 'title', 'breadcum', 'bomPayload', 'inventoryItem'));
    }

    public function showAddBomItemForm($id)
    {
        $pmodule = $this->pmodule;
        $model = $this->model;
        $title = $this->title;

        $inventoryItem = WaInventoryItem::with('bom')->select(['id', 'title'])->find($id);
        if (!$inventoryItem) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        $breadcum = [$title => route($model . '.index'), 'BOM' => route($model . '.show-bom', $inventoryItem->id), 'Add Item' => ''];
        $currentBomIds = $inventoryItem->bom()->pluck('raw_material_id')->toArray();
        $rawMaterials = WaInventoryItem::select(['id', 'title'])->where('item_type', '2')->get()
            ->filter(function (WaInventoryItem $inventoryItem) use ($currentBomIds) {
                return !in_array($inventoryItem->id, $currentBomIds);
            });

        return view('admin.maintaininvetoryitems.bom.create', compact('pmodule', 'model', 'title', 'breadcum', 'inventoryItem', 'rawMaterials'));
    }

    public function storeBomItem(Request $request, int $id): RedirectResponse
    {
        $inventoryItem = WaInventoryItem::with('bom')->find($id);
        if (!$inventoryItem) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        $inventoryItem->bom()->create($request->all());
        Session::flash('success', 'Bom item added successfully');
        return redirect()->route("$this->model.show-bom", $inventoryItem->id);
    }

    public function removeBomItem(int $id): RedirectResponse
    {
        $bomItem = WaInventoryItemRawMaterial::find($id);
        if (!$bomItem) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        $bomItem->delete();
        return redirect()->back()->with('success', 'BOM item removed successfully');
    }

    public function item_price_pending_list(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        // $model = $this->model;
        $model = 'pending-price-change-requests';

        if (isset($permission[$pmodule . '___item_price_pending_list']) || $permission == 'superadmin') {
            $breadcum = [$title => route('maintain-items.index'), 'Listing' => ''];
            $list = WaInventoryPriceHistory::with(['item', 'creator'])->where('status', 'Pending')->orderBy('created_at', 'DESC');
            if ($request->item) {
                $list = $list->where('wa_inventory_item_id', $request->item);
            }

            if ($request->supplier) {
                $supplierItemIds = DB::table('wa_inventory_item_suppliers')->where('wa_supplier_id', $request->supplier)->pluck('wa_inventory_item_id')->toArray();
                $list = $list->whereIn('wa_inventory_item_id', $supplierItemIds);
            }


            $list = $list->get()->map(function ($item) {
                $qoh = DB::table('wa_stock_moves')->where('wa_inventory_item_id', $item->item->id)->where('wa_location_and_store_id', 46)->sum('qauntity');
                $item->qoh = $qoh;

                $suppliers = DB::table('wa_inventory_item_suppliers')->where('wa_inventory_item_id', $item->item->id)
                    ->leftJoin('wa_suppliers', 'wa_inventory_item_suppliers.wa_supplier_id', '=', 'wa_suppliers.id')
                    ->select('wa_suppliers.id', 'wa_suppliers.name')
                    ->get();
                $item->suppliers = $suppliers;
                $item->supplier_names = implode(',', $suppliers->pluck('name')->toArray());

                return $item;
            });

            $suppliers = DB::table('wa_suppliers')->select('id', 'name')->get();
            return view('admin.maintaininvetoryitems.price_change.list', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'list', 'suppliers'));
        } else {
            Session::flash('warning', 'Access Denied');
            return redirect()->back();
        }
    }

    public function item_price_history_list(Request $request)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = 'price-change-history-list';
        $suppliers = DB::table('wa_suppliers')->select('id', 'name')->get();

        if (isset($permission[$pmodule . '___item_price_history']) || $permission == 'superadmin') {
            $breadcum = [$title => route('maintain-items.index'), 'Listing' => ''];
            $list = WaInventoryPriceHistory::withWhereHas('item')->with(['creator', 'approver'])->where(function ($e) use ($request) {
                if ($request->item) {
                    $e->where('wa_inventory_item_id', $request->item);
                }
                if ($request->supplier) {
                    $supplierItemIds = DB::table('wa_inventory_item_suppliers')->where('wa_supplier_id', $request->supplier)->pluck('wa_inventory_item_id')->toArray();
                    $e->whereIn('wa_inventory_item_id', $supplierItemIds);
                }
            })->where('status', '!=', 'Pending')->orderBy('created_at', 'DESC')->paginate(100);
            return view('admin.maintaininvetoryitems.price_change.history', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'list', 'suppliers'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function item_price_pending_verify(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $permission = $this->mypermissionsforAModule();
            $pmodule = $this->pmodule;

            if (isset($permission[$pmodule . '___item_price_pending_list']) || $permission == 'superadmin') {
                $validator = Validator::make($request->all(), [
                    'status' => 'required|in:Approved,Rejected',
                    'id' => 'required|exists:wa_inventory_item_price_history,id,status,Pending',
                ]);

                if ($validator->fails()) {
                    return response()->json(['result' => 0, 'message' => $validator->errors()]);
                }

                $row = WaInventoryPriceHistory::with('item')->where('id', $id)->first();
                if (!$row || $row->status != 'Pending') {
                    return response()->json(['result' => 0, 'message' => ['id' => ['This request has already been reviewed']]]);
                }

                $row->status = $request->status;
                $row->approved_by = getLoggeduserProfile()->id;
                $row->updated_at = date('Y-m-d H:i:s');
                $row->old_standard_cost = $row->standard_cost;
                $row->old_selling_price = $row->selling_price;
                $row->item->prev_standard_cost = $row->item->standard_cost;
                $row->item->standard_cost = $row->standard_cost;
                $row->item->selling_price = $row->selling_price;
                $row->item->block_this = $row->block_this ? True : False;
                $row->item->save();
                $row->save();

                if ($request->status == 'Approved') {
                    //update inventory Item
                    $inventoryItem = WaInventoryItem::find($row->item->id);
                    $inventoryItem->standard_cost = $row->standard_cost;
                    $inventoryItem->selling_price = $row->selling_price;
                    $inventoryItem->save();

                    //update purchase data
                    $purchaseData = WaInventoryItemSupplierData::where('wa_supplier_id', $request->supplier_id)->where('wa_inventory_item_id', $row->item->id)->first();
                    $purchaseData->price = $row->standard_cost;
                    $purchaseData->save();
                }

                if (($request->standard_cost < $request->old_standard_cost) && ($request->status == 'Approved')) {
                    $qoh = DB::table('wa_stock_moves')->where('wa_inventory_item_id', $row->item->id)->where('wa_location_and_store_id', 46)
                        ->sum('qauntity');

                    $demand = ItemSupplierDemand::create([
                        'wa_inventory_item_id' => $row->item->id,
                        'wa_supplier_id' => $request->supplier_id,
                        'current_cost' => $request->old_standard_cost,
                        'new_cost' => $request->standard_cost,
                        'current_price' => $request->old_selling_price,
                        'new_price' => $request->selling_price,
                        'demand_quantity' => $qoh,
                    ]);
                }

                DB::commit();
                return response()->json(['result' => 1, 'message' => 'Item Price Updated Successfully', 'location' => route($this->model . '.item_price_pending_list')]);
            } else {
                return response()->json(['result' => -1, 'message' => 'Access Denied']);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['result' => -1, 'message' => $e->getMessage(), 'trace' => $e->getTrace()]);
        }
    }

    //Maintain Purchase Data
    public function purchaseData($stockid, Request $request)
    {
        try {
            $data['pmodule'] = $this->pmodule;
            $data['title'] = $this->title;
            $data['model'] = $this->model;
            $data['item_suppliers'] = \App\Model\WaInventoryItemSupplierData::where('wa_inventory_item_id', $stockid)->orderBy('id', 'DESC')->get();
            $item_suppliers = \App\Model\WaInventoryItemSupplier::where('wa_inventory_item_id', $stockid)->orderBy('id', 'DESC')->get();
            $data['inventoryItem'] = \App\Model\WaInventoryItem::findOrFail($stockid);
            $data['suppliers'] = \App\Model\WaSupplier::whereIn('id', $item_suppliers->pluck('wa_supplier_id')->toArray())->get();
            return view('admin.maintaininvetoryitems.purchaseData.purchaseData')->with($data);
        } catch (\Throwable $th) {
            Session::flash('warning', 'Something went wrong');
            return redirect()->route('maintain-items.index');
        }
    }

    public function purchaseDataAdd($stockid, Request $request)
    {
        try {
            $data['supplier'] = \App\Model\WaSupplier::where('supplier_code', $request->supplier_code)->firstOrFail();
            $supplier_item = \App\Model\WaInventoryItemSupplierData::where('wa_supplier_id', $data['supplier']->id)->where('wa_inventory_item_id', $stockid)->first();
            if ($supplier_item) {
                return redirect()->route('maintain-items.purchaseDataEdit', ['stockid' => encrypt($stockid), 'itemid' => encrypt($supplier_item->id)]);
            }
            $data['pmodule'] = $this->pmodule;
            $data['title'] = $this->title;
            $data['model'] = $this->model;
            $data['inventoryItem'] = \App\Model\WaInventoryItem::findOrFail($stockid);
            $data['currencys'] = \App\Model\WaCurrencyManager::get();
            $data['units'] = \App\Model\WaUnitOfMeasure::get();
            return view('admin.maintaininvetoryitems.purchaseData.purchaseDataAdd')->with($data);
        } catch (\Throwable $th) {
            Session::flash('warning', 'Something went wrong');
            return redirect()->route('maintain-items.index');
        }
    }

    public function purchaseDataEdit($stockid, $itemid, Request $request)
    {
        try {
            $data['pmodule'] = $this->pmodule;
            $data['title'] = $this->title;
            $data['model'] = $this->model;
            $itemid = decrypt($itemid);
            $stockid = decrypt($stockid);
            $data['inventoryItem'] = \App\Model\WaInventoryItem::findOrFail($stockid);
            $data['supplier_item'] = \App\Model\WaInventoryItemSupplierData::findOrFail($itemid);
            $data['supplier'] = \App\Model\WaSupplier::where('id', $data['supplier_item']->wa_supplier_id)->firstOrFail();
            // dd($data);
            $data['currencys'] = \App\Model\WaCurrencyManager::get();
            $data['units'] = \App\Model\WaUnitOfMeasure::get();
            return view('admin.maintaininvetoryitems.purchaseData.purchaseDataEdit')->with($data);
        } catch (\Throwable $th) {
            Session::flash('warning', 'Something went wrong');
            return redirect()->route('maintain-items.index');
        }
    }

    public function bulk_inventory_import_export(Request $request)
    {
        try {
            //code...
            $supplier = \App\Model\WaSupplier::where('id', $request->supplier)->firstOrFail();
            $assigned = \App\Model\WaInventoryItemSupplierData::where('wa_supplier_id', $supplier->id)->orderBy('id', 'DESC')->get();
            $items = \App\Model\WaInventoryItem::whereIn('id', $assigned->pluck('wa_inventory_item_id')->toArray())->get();
            if ($request->hasFile('excel')) {
                Excel::import(new SupplierCostImport($items, $supplier->id), request()->file('excel'));
                Session::flash('success', 'Data imported successfully!');
                return redirect()->route('maintain-items.index');
            }
            if (!$request->export) {
                throw new \Exception("Error Processing Request");
            }
            $export = new BulkPurchaseDataExport($items);
            return Excel::download($export, 'supplier_data_' . $supplier->name . '.xlsx');
        } catch (\Throwable $th) {
            Session::flash('warning', $th->getMessage());
            return redirect()->back();
        }
    }

    public function bulk_purchase_data()
    {
        try {
            $data['pmodule'] = $this->pmodule;
            $data['title'] = $this->title;
            $data['model'] = 'bulk_purchase_data';
            $data['suppliers'] = \App\Model\WaSupplier::get();
            return view('admin.maintaininvetoryitems.purchaseData.bulk_purchase_data')->with($data);
        } catch (\Throwable $th) {
            Session::flash('warning', 'Something went wrong');
            return redirect()->route('maintain-items.index');
        }
    }

    public function purchaseDataStore($stockid, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier' => 'required|exists:wa_suppliers,id',
            'stockid' => 'required|exists:wa_inventory_items,id',
            'currency' => 'required|exists:wa_currency_managers,ISO4217',
            'price' => 'required|numeric|max:2550000000|min:1',
            'price_effective_from' => 'required|date_format:Y-m-d|after:yesterday',
            // 'supplier_unit' => 'required|exists:wa_unit_of_measures,title',
            // 'conversion_factor' => 'nullable|string|min:1|max:255',
            'supplier_stock_code' => 'nullable|string|min:1|max:255',
            // 'min_order_qty' => 'required|numeric|digits_between:1,10|min:1',
            'supplier_stock' => 'required|string|max:255|min:1',
            // 'lead_time' => 'required|numeric|digits_between:1,10|min:1',
            'preferred_supplier' => 'required|in:No,Yes'
        ], [], []);
        if ($validator->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validator->errors()
            ]);
        }
        $suplier = DB::transaction(function () use ($request) {
            $suplier = new \App\Model\WaInventoryItemSupplierData;
            $suplier->wa_supplier_id = $request->supplier;
            $suplier->wa_inventory_item_id = $request->stockid;
            $suplier->currency = $request->currency;
            $suplier->price = $request->price;
            $suplier->price_effective_from = $request->price_effective_from;
            $suplier->our_unit_of_measure = $request->our_unit_of_measure;
            $suplier->supplier_unit_of_measure = $request->supplier_unit ?? NULL;
            $suplier->conversion_factor = $request->conversion_factor ?? NULL;
            $suplier->supplier_stock_code = $request->supplier_stock_code;
            $suplier->minimum_order_quantity = $request->min_order_qty ?? NULL;
            $suplier->supplier_stock_description = $request->supplier_stock;
            $suplier->lead_time_days = $request->lead_time ?? NULL;
            $suplier->preferred_supplier = $request->preferred_supplier;
            $suplier->save();
            $price = new \App\Model\WaInventoryItemSupplierPrices;
            $price->wa_inventory_item_supplier_id = $suplier->id;
            $price->price = $suplier->price;
            $price->status = 'Current';
            $price->save();
            return $suplier;
        });
        if ($suplier) {
            return response()->json([
                'result' => 1,
                'message' => 'Supplier Item added successfully',
                'location' => route('item-centre.show', $request->stockid),
            ]);
        }
        return response()->json([
            'result' => -1,
            'message' => 'Something went wrong',
        ]);
    }

    public function purchaseDataUpdate($stockid, $itemid, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|exists:wa_currency_managers,ISO4217',
            'price' => 'required|numeric|min:1|max:2550000000',
            'price_effective_from' => 'required|date_format:Y-m-d|after:yesterday',
            // 'supplier_unit' => 'required|exists:wa_unit_of_measures,title',
            // 'conversion_factor' => 'nullable|string|min:1|max:255',
            'supplier_stock_code' => 'nullable|string|min:1|max:255',
            // 'min_order_qty' => 'required|numeric|digits_between:1,10|min:1',
            'supplier_stock' => 'required|string|max:255|min:1',
            // 'lead_time' => 'required|numeric|digits_between:1,10|min:1',
            'preferred_supplier' => 'required|in:No,Yes'
        ], [], []);
        if ($validator->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validator->errors()
            ]);
        }
        $suplier = DB::transaction(function () use ($request, $itemid) {

            $itemid = decrypt($itemid);
            $suplier = \App\Model\WaInventoryItemSupplierData::findOrFail($itemid);
            // $suplier->wa_supplier_id = $request->supplier;
            // $suplier->wa_inventory_item_id = $request->stockid;
            $suplier->currency = $request->currency;
            $suplier->price = $request->price;
            $suplier->price_effective_from = $request->price_effective_from;
            $suplier->our_unit_of_measure = $request->our_unit_of_measure;
            $suplier->supplier_unit_of_measure = $request->supplier_unit ?? NULL;
            $suplier->conversion_factor = $request->conversion_factor ?? NULL;
            $suplier->supplier_stock_code = $request->supplier_stock_code;
            $suplier->minimum_order_quantity = $request->min_order_qty ?? NULL;
            $suplier->supplier_stock_description = $request->supplier_stock;
            $suplier->lead_time_days = $request->lead_time ?? NULL;
            $suplier->preferred_supplier = $request->preferred_supplier;
            $suplier->save();
            \App\Model\WaInventoryItemSupplierPrices::where('wa_inventory_item_supplier_id', $suplier->id)->update(['status' => 'Old']);
            $price = new \App\Model\WaInventoryItemSupplierPrices;
            $price->wa_inventory_item_supplier_id = $suplier->id;
            $price->price = $suplier->price;
            $price->status = 'Current';
            $price->save();
            return $suplier;
        });
        if ($suplier) {
            return response()->json([
                'result' => 1,
                'message' => 'Supplier Item updated successfully',
                'location' => route('item-centre.show', $request->stockid),
            ]);
        }
        return response()->json([
            'result' => -1,
            'message' => 'Something went wrong',
        ]);
    }

    public function purchaseDataDelete($stockid, $itemid, Request $request)
    {
        $itemid = decrypt($itemid);
        $stockid = decrypt($stockid);
        $suplier = \App\Model\WaInventoryItemSupplierData::findOrFail($itemid);
        $suplier->delete();
        Session::flash('success', 'Supplier Item deleted successfully.');
        return redirect()->route($this->model . '.purchaseData', ['stockid' => $stockid]);
    }

    public function getMaxStockData()
    {
        $items = DB::table('wa_inventory_items')->whereIn('pack_size_id', [8, 7, 3, 5, 2, 4, 13])->select('id', 'stock_id_code', 'title', 'wa_inventory_category_id')->get()->map(function ($item) {
            $item->category = DB::table('wa_inventory_categories')->select('id', 'category_description')->where('id', $item->wa_inventory_category_id)->first()->category_description;

            $item->bin = null;
            $binRecord = DB::table('wa_inventory_location_uom')->where('location_id', 46)->where('inventory_id', $item->id)->first();
            if ($binRecord) {
                $item->bin = DB::table('wa_unit_of_measures')->where('id', $binRecord->uom_id)->first()?->title;
            }

            $item->max_stock = DB::table('wa_inventory_location_stock_status')->where('wa_inventory_item_id', $item->id)->where('wa_location_and_stores_id', 46)->first()?->max_stock ?? 0;

            $item->qoh = DB::table('wa_stock_moves')->where('wa_inventory_item_id', $item->id)->where('wa_location_and_store_id', 46)->sum('qauntity');
            if (!$item->qoh) {
                $item->qoh = 0;
            }

            $suppliers = DB::table('wa_inventory_item_suppliers')->where('wa_inventory_item_id', $item->id)
                ->leftJoin('wa_suppliers', 'wa_inventory_item_suppliers.wa_supplier_id', '=', 'wa_suppliers.id')
                ->select('wa_suppliers.id', 'wa_suppliers.name')
                ->get();
            $supplierIds = $suppliers->pluck('id')->toArray();
            $users = DB::table('wa_user_suppliers')->whereIn('wa_supplier_id', $supplierIds)
                ->leftJoin('users', 'wa_user_suppliers.user_id', '=', 'users.id')
                ->select('users.name')
                ->get();

            $item->users = implode(',', $users->pluck('name')->toArray());
            $item->supplier_names = implode(',', $suppliers->pluck('name')->toArray());

            unset($item->wa_inventory_category_id);
            return $item;
        });

        $export = new MaxStockExport($items);
        return Excel::download($export, 'MAX STOCK DATA REPORT.xlsx');
    }

    public function approvalStatus($data)
    {
        $user = getLoggeduserProfile();
        $changes = NULL;
        $newData = NULL;
        if (isset($data['changes'])) {
            $changes = json_encode($data['changes']);
        }
        if (isset($data['new_data'])) {
            $newData = json_encode($data['new_data']);
        }
        $data['user'] = $user->id;
        $data['changes'] = $changes;
        $data['new_data'] = $newData;
        $this->approvalRepository->storeApprovalItem($data);
    }

    public function item_approval($status)
    {
        switch ($status) {
            case 'pending-new-approval':
                $title = $this->title . ' :: Pending New Approval';
                break;
            case 'pending-edit-approval':
                $title = $this->title . ' :: Pending Edit Approval';
                break;
            case 'rejected-approval':
                $title = $this->title . ' :: Rejected Request';
                break;

            default:
                $title = $this->title;
                break;
        }

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = $status;
        $users = User::where('role_id', 154)->orwhere('role_id', 157)->orwhere('role_id', 1)->get();
        //        $inventoryItems = WaInventoryItem::all();
        $pendingNewApprovalStatuses =   WaInventoryItemApprovalStatus::where('status', 'Pending Edit Approval')
            ->whereNotNull('new_data')
            ->latest()
            ->get();

        if (isset($permission[$pmodule . '___item-approval']) || $permission == 'superadmin') {
            $breadcum = [$title => route('maintain-items.index'), 'Listing' => ''];
            return view('admin.maintaininvetoryitems.approval.index', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'status', 'users', 'pendingNewApprovalStatuses'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }
    public function item_new_approval()
    {
        $title = "Pending New Approval";

        $permission = $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $model = 'pending-new-approval';
        $status  = 'pending-new-approval';
        $pendingNewApprovalStatuses = WaInventoryItemApprovalStatus::where('status', 'Pending New Approval')->whereNotNull('new_data')->get();
        // dd($pendingNewApprovalStatuses);
        if (isset($permission[$pmodule . '___item-approval']) || $permission == 'superadmin') {
            $breadcum = [$title => route('maintain-items.index'), 'Listing' => ''];
            return view('admin.maintaininvetoryitems.approval.new_item_approval', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'status', 'pendingNewApprovalStatuses'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function getApprovalStatusFromSlug($status)
    {
        $approvalStatus = '';
        if ($status) {
            switch ($status) {
                case 'pending-new-approval':
                    $approvalStatus = ApprovalStatus::PendingNewApproval->value;
                    break;
                case 'pending-edit-approval':
                    $approvalStatus = ApprovalStatus::PendingEditApproval->value;
                    break;
                case 'rejected-approval':
                    $approvalStatus = ApprovalStatus::Rejected->value;
                    break;

                default:
                    $approvalStatus = ApprovalStatus::Approved->value;
                    break;
            }
        }
        return $approvalStatus;
    }

    public function downloadInvetoryitemsApproval(Request $request, $status)
    {
        try {
            $approvalStatus = $this->getApprovalStatusFromSlug($status);
            $data_query = WaInventoryItem::select(
                'wa_inventory_categories.id as cat_id',
                'wa_inventory_categories.category_description',
                'wa_inventory_items.*',
                DB::RAW(' (select sum(qauntity) from wa_stock_moves where wa_stock_moves.stock_id_code=wa_inventory_items.stock_id_code) as item_total_qunatity')
            )->with(['pack_size', 'getAllFromStockMoves', 'getTaxesOfItem', 'location', 'unitofmeasures', 'bin_locations' => function ($e) {
                $e->where('location_id', 46);
            }])
                ->where('wa_inventory_items.approval_status', $approvalStatus)
                ->join('wa_inventory_categories', 'wa_inventory_categories.id', '=', 'wa_inventory_items.wa_inventory_category_id')->get(); //dd($data_query);
            $arrays = [];
            $sumH = 0;
            if (!empty($data_query)) {
                foreach ($data_query as $key => $row) {
                    $arrays[] = [
                        'Stock Id Code' => (string)($row->stock_id_code),
                        'Title' => $row->title,
                        'Item Category' => $row->category_description,
                        'Pack Size' => (string)($row->pack_size ? $row->pack_size->title : ''),
                        'Standard Cost' => (string)$row->standard_cost,
                        'Selling Price' => (string)$row->selling_price,
                        '% MARGIN' => number_format($row->standard_cost != 0 ? ((($row->selling_price - $row->standard_cost) / $row->standard_cost) * 100) : 0, 2),
                        'Quantity' => (string)(@$row->item_total_qunatity ?? 0),
                        'Tax Category' => (string)@$row->getTaxesOfItem->title,
                        // 'Default Store' => (string)@$row->location->location_name,
                        // 'Gross Weight' => (string)@$row->gross_weight,
                        // 'Bin Location(UOM)' => (string)@$row->unitofmeasures->title,
                        'Bin Location(Thika Store)' => (string)@$row->bin_locations->first()->uom->title,
                    ];

                    $sumH += ($row->getAllFromStockMoves ? $row->getAllFromStockMoves->sum('qauntity') : 0);
                }
            }

            return ExcelDownloadService::download('inventory-items-' . $status . '-' . date('Y-m-d-H-i-s'), collect($arrays), ['STOCK ID CODE', 'TITLE', 'CATEGORY', 'PACK SIZE', 'STANDARD COST', 'SELLING PRICE', 'PERCENTAGE  MARGIN', 'QUANTITY', 'TAX CATEGORY', 'BIN LOCATION']);
        } catch (\Exception $th) {
            $request->session()->flash('danger', $th->getMessage());
            return redirect()->back();
        }
    }

    public function update_approval($item, $status)
    {

        try {
            $getOldStatus = DB::table('wa_inventory_items')
                ->where('id', $item)
                ->first();

            if ($status == ApprovalStatus::Approved->value) {
                $approvalStatus = DB::table('wa_inventory_item_approval_statuses')
                    ->where('wa_inventory_items_id', $item)
                    ->orderBy('created_at', 'desc')
                    ->get()->first();
                if ($approvalStatus && $approvalStatus->status == ApprovalStatus::PendingEditApproval->value) {
                    $newData = (array)json_decode($approvalStatus->new_data);
                    if (count($newData)) {
                        $this->update_approved_items($newData, $item);
                    }
                }
            }

            DB::table('wa_inventory_items')
                ->where('id', $item)
                ->update(['approval_status' => $status]);

            Session::flash('success', 'Item Approval Updated Successfully.');
            if ($getOldStatus->approval_status == ApprovalStatus::PendingEditApproval->value) {
                $url = 'pending-edit-approval';
            } else if ($getOldStatus->approval_status == ApprovalStatus::PendingNewApproval->value) {
                $url = 'pending-new-approval';
            } else {
                $url = 'rejected-approval';
            }
            return redirect(route('item-approval', $url));
        } catch (\Exception $th) {
            Session::flash('danger', $th->getMessage());
            return redirect()->back();
        }
    }

    /*
    * Match The Updated Fields to their old Values
    */
    public function getFieldChanges($old, $new)
    {

        $changes = [];
        if ($old->title != $new->title) {
            array_push($changes, ['Title' => [$old->title, $new->title]]);
        }
        if ($old->status != $new->status) {
            array_push($changes, ['Status' => [$old->status ? 'Active' : 'Retired', $new->status ? 'Active' : 'Retired']]);
        }
        if ($old->margin_type != $new->margin_type) {
            array_push($changes, ['Margin Type' => [$old->margin_type ? 'Percentage' : 'Value', $new->margin_type ? 'Percentage' : 'Value']]);
        }
        if ($old->description != $new->description) {
            array_push($changes, ['Description' => [$old->description, $new->description]]);
        }
        if ($old->wa_inventory_category_id != $new->wa_inventory_category_id) {
            $oldCat = DB::table('wa_inventory_categories')->find($old->wa_inventory_category_id);
            $newCat = DB::table('wa_inventory_categories')->find($new->wa_inventory_category_id);
            if ($oldCat) {
                $oldCatTitle = $oldCat->category_description;
            } else {
                $oldCatTitle = '';
            }
            if ($newCat) {
                $newCatTitle = $newCat->category_description;
            } else {
                $newCatTitle = '';
            }
            array_push($changes, ['Category' => [$oldCatTitle, $newCatTitle]]);
        }
        if ($old->item_sub_category_id != $new->item_sub_category_id) {
            $oldCat = DB::table('wa_item_sub_categories')->find($old->item_sub_category_id);
            $newCat = DB::table('wa_item_sub_categories')->find($new->item_sub_category_id);
            if ($oldCat) {
                $oldCatTitle = $oldCat->title;
            } else {
                $oldCatTitle = '';
            }
            if ($newCat) {
                $newCatTitle = $newCat->title;
            } else {
                $newCatTitle = '';
            }
            array_push($changes, ['Subcategory' => [$oldCatTitle, $newCatTitle]]);
        }
        if ($old->tax_manager_id != $new->tax_manager_id) {
            $oldTax = DB::table('tax_managers')->find($old->tax_manager_id);
            $newTax = DB::table('tax_managers')->find($new->tax_manager_id);
            if ($oldTax) {
                $oldTaxTitle = $oldTax->title;
            } else {
                $oldTaxTitle = '';
            }
            if ($newTax) {
                $newTaxTitle = $newTax->title;
            } else {
                $newTaxTitle = '';
            }
            array_push($changes, ['Tax Manager' => [$oldTaxTitle, $newTaxTitle]]);
        }
        if (isset($new->hs_code) && $old->hs_code != $new->hs_code) {
            array_push($changes, ['HS Code' => [$old->hs_code, $new->hs_code]]);
        }
        if (isset($new->pack_size_id) && $old->pack_size_id != $new->pack_size_id) {
            $oldPack = DB::table('pack_sizes')->find($old->pack_size_id);
            $newPack = DB::table('pack_sizes')->find($new->pack_size_id);
            if ($oldPack) {
                $oldPackTitle = $oldPack->title;
            } else {
                $oldPackTitle = '';
            }
            if ($newPack) {
                $newPackTitle = $newPack->title;
            } else {
                $newPackTitle = '';
            }
            array_push($changes, ['Pack Size' => [$oldPackTitle, $newPackTitle]]);
        }
        if (isset($new->percentage_margin) && $old->percentage_margin != $new->percentage_margin) {
            array_push($changes, ['Percentage Margin' => [$old->percentage_margin, $new->percentage_margin]]);
        }
        if (isset($new->actual_margin) && $old->actual_margin != $new->actual_margin) {
            array_push($changes,  ['Actual Margin' =>  [$old->actual_margin, $new->actual_margin]]);
        }
        if (isset($new->max_order_quantity) && $old->max_order_quantity != $new->max_order_quantity) {
            array_push($changes, ['Max Order Quantity' => [$old->max_order_quantity, $new->max_order_quantity]]);
        }

        if (isset($new->alt_code) && $old->alt_code != $new->alt_code) {
            array_push($changes, ['Alt Code' => [$old->alt_code, $new->alt_code]]);
        }
        if (isset($new->packaged_volume) && $old->packaged_volume != $new->packaged_volume) {
            array_push($changes, ['Packaged Volume' => [$old->packaged_volume, $new->packaged_volume]]);
        }
        if (isset($new->gross_weight) && $old->gross_weight != $new->gross_weight) {
            array_push($changes, ['Gross Weight' => [$old->gross_weight, $new->gross_weight]]);
        }
        if (isset($new->net_weight) && $old->net_weight != $new->net_weight) {
            array_push($changes, ['Net Weight' => [$old->net_weight, $new->net_weight]]);
        }
        if (isset($new->block_this) && $old->block_this != $new->block_this) {
            array_push($changes, ['Block This' => [$old->block_this, $new->block_this]]);
        }
        if (isset($new->restocking_method) && $old->restocking_method != $new->restocking_method) {
            array_push($changes, ['Restocking Method' => [$old->restocking_method, $new->restocking_method]]);
        }
        if (isset($new->selling_price) && $old->selling_price != $new->selling_price) {
            array_push($changes, ['Selling Price' => [$old->selling_price, $new->selling_price]]);
        }
        if (isset($new->standard_cost) && $old->standard_cost != $new->standard_cost) {
            array_push($changes, ['Standard Cost' => [$old->standard_cost, $new->standard_cost]]);
        }

        if (isset($new->suppliers) && count($new->suppliers) > 0) {
            if ($old->oldSuppliers != $new->suppliers) {
                $oldSupp = DB::table('wa_suppliers')->whereIn('id', $old->oldSuppliers)->get();
                if ($oldSupp) {
                    $oldSuppName = implode(", ", $oldSupp->pluck('name')->toArray());
                } else {
                    $oldSuppName = '';
                }
                $newSupp = DB::table('wa_suppliers')->whereIn('id', $new->suppliers)->get();
                if ($newSupp) {
                    $newSuppName = implode(", ", $newSupp->pluck('name')->toArray());
                } else {
                    $newSuppName = '';
                }
                array_push($changes, ['Supplier' => [$oldSuppName, $newSuppName]]);
            }
        }
        if (isset($new->item_count) && $old->item_count != $new->item_count) {
            array_push($changes, ['Item Count' => [$old->item_count, $new->item_count]]);
        }
        if (isset($new->margin_type) && $old->margin_type != $new->margin_type) {
            array_push($changes, ['Margin Type' => [$old->margin_type, $new->margin_type]]);
        }
        return $changes;
    }

    public function showItemLog()
    {
        if (!can('item-approval', $this->pmodule)) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }
        $title = 'Item History Log';
        $model = 'item-log';
        $breadcum = [$this->title => route('maintain-items' . '.index'), 'Listing' => ''];
        return view('admin.maintaininvetoryitems.approval.item_history', compact('title', 'model', 'breadcum'));
    }

    public function showItemLogDatatable(Request $request)
    {
        $items = WaInventoryItemApprovalStatus::with('approvalBy', 'inventoryItem', 'inventoryItem.category')
            ->select('wa_inventory_item_approval_statuses.*')->orderBy('created_at', 'DESC');

        $items->when(request()->filled('start_date') && request()->filled('end_date'), function ($query) {
            return $query->whereBetween('created_at', [request()->start_date . ' 00:00:00', request()->end_date . ' 23:59:59']);
        });

        return DataTables::eloquent($items)
            ->addColumn('requested_on', function ($item) {
                return date('F j, Y, g:i a', strtotime($item->created_at));
            })
            ->addColumn('item_category', function ($item) {
                return $item->inventoryItem?->category ? $item->inventoryItem?->category->category_description : '';
            })
            ->editColumn('inventory_item.stock_id_code', function ($item) {
                return $item->inventoryItem?->stock_id_code ? $item->inventoryItem?->stock_id_code : '';
            })
            ->editColumn('inventory_item.title', function ($item) {
                return $item->inventoryItem?->title ? $item->inventoryItem?->title : '';
            })
            ->toJson();
    }

    public function updatepricePerLocation(Request $request, $id)
    {
        $item = WaInventoryItem::find($id);


        $validator = Validator::make($request->all(), [
            'price_data' => 'required|array',
            'price_data.*.store_id' => 'required|integer',
            //            'price_data.*.selling_price' => 'required|numeric|min:' . $item->standard_cost,
            //            'price_data.*.is_flash' => 'required|boolean',
        ]);

        //        dd($request->all());
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422); // Return validation errors
        }

        DB::beginTransaction();
        try {
            foreach ($request->price_data as $datum) {
                $ends_at = null;
                if ($datum['is_flash']) {
                    $ends_at = now()->endOfDay();
                }
                WaInventoryItemPrice::where('store_location_id', $datum['store_id'])
                    ->where('wa_inventory_item_id', $item->id)
                    ->update([
                        'selling_price' => $datum['selling_price'],
                        'user_id' => Auth::id(),
                        'is_flash' => $datum['is_flash'],
                        'ends_at' => $ends_at,
                    ]);
            }
            DB::commit();
            return response()->json([
                'status' => 1,
                'message' => 'Prices Updated Successfully'
            ]);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => -1,
                'message' => 'Something Went wrong. Update was not successfull'
            ]);
        }
    }

    public function showItemLogView($id)
    {
        if (!can('item-approval', $this->pmodule)) {
            Session::flash(pageRestrictedMessage());
            return redirect()->back();
        }
        $title = 'Item History Log View';
        $model = 'item-log';
        $breadcum = [$this->title => route('maintain-items' . '.index'), 'Listing' => ''];
        $item = WaInventoryItemApprovalStatus::with('approvalBy', 'inventoryItem', 'inventoryItem.category')->find($id);
        $changes = json_decode($item->changes);
        return view('admin.maintaininvetoryitems.approval.item_history_view', compact('title', 'model', 'breadcum', 'item', 'changes'));
    }
    public function cloneItem(Request $request)
    {
        $inventoryItem = WaInventoryItem::find($request->item_id);

        if (!$inventoryItem) {
            return response()->json(['result' => 0, 'message' => 'Item not found']);
        }

        $clonedData = $inventoryItem->toArray();
        $clonedData['stock_id_code'] = $request->new_stock_id_code;

        unset($clonedData['id'], $clonedData['created_at'], $clonedData['updated_at']);

        $clonedData['current_step'] = 3;

        $storeRequest = new Request($clonedData);

        return $this->store($storeRequest);
    }
}
