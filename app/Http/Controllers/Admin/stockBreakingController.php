<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Model\WaInventoryItem;
use App\Model\WaStockBreaking;
use App\Model\WaNumerSeriesCode;
use App\Model\WaStockBreakingItem;
use App\Model\WaStockMove;
use App\Models\StockBreakDispatch;
use App\Models\StockBreakDispatchItem;
use App\Models\PosStockBreakRequest;
use App\Models\WaInventoryItemApprovalStatus;
use App\Models\WaStoreReturnItem;
use App\User;
use Exception;
use Illuminate\Support\Facades\Validator;

class stockBreakingController extends BaseController
{

    protected $model;
    protected $title;
    protected $pmodule;

    public function __construct()
    {
        $this->model = 'stock-breaking';
        $this->title = 'Stock Breaking';
        $this->pmodule = 'stock-breaking';
    }

    public function mypermissionsforAModule()
    {
        $logged_user_info = getLoggeduserProfile();

        if (!$logged_user_info) {
            return [];
        }

        if ($logged_user_info->role_id == 1) {
            return 'superadmin';
        } else {
            return $logged_user_info->permissions ?? [];
        }
    }


    public function index(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
            $pmodule = $this->pmodule;
            $title = $this->title;
            $model = $this->model;
            $user = getLoggeduserProfile();

            if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            if ($request->ajax()) {
                try {
                $sortable_columns = [
                    'wa_stock_breaking.id',
                    'users.name',
                    'wa_stock_breaking.breaking_code',
                    'wa_stock_breaking.user_id',
                    'wa_stock_breaking.user_id'
                ];
                // Safely read DataTables parameters with sensible defaults
                $limit          = (int) $request->input('length', 10);
                $start          = (int) $request->input('start', 0);
                $search         = (string) $request->input('search.value', '');
                $orderbyIndex   = (int) $request->input('order.0.column', 0);
                $order          = strtoupper((string) $request->input('order.0.dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
                $draw           = (int) $request->input('draw', 0);

                // Validate orderBy index to avoid undefined offset
                if (!isset($sortable_columns[$orderbyIndex])) {
                    $orderbyIndex = 0; // default to first sortable column
                }
                $data = WaStockBreaking::select([
                    'wa_stock_breaking.*',
                    'users.name as user_name',
                    'requesters.name as requester_name',
                    'requesters.id as requester_id',
                    //fetch first bin of the child
                ])
                    ->leftjoin('users', function ($join) {
                        $join->on('users.id', '=', 'wa_stock_breaking.user_id');
                    })
                    ->leftjoin('pos_stock_break_requests as break_requests', function ($join) {
                        $join->on('break_requests.id', '=', 'wa_stock_breaking.pos_stock_break_request_id');
                    })
                    ->leftjoin('users as requesters', function ($join) {
                        $join->on('requesters.id', '=', 'break_requests.requested_by');
                    })
                    ->where(function ($w) use ($user, $request) {
                        if ($request->input('from') && $request->input('to')) {
                            $w->whereBetween('date', [$request->input('from'), $request->input('to')]);
                        }
                        if ($user->role_id != 1 && $user->wa_unit_of_measures_id) {
                            $w->whereHas('items.destination_item.binLocation', fn($query) => $query->where('uom_id', $user->wa_unit_of_measures_id));
                        }
                    })->where(function ($w) use ($search) {
                        if ($search) {
                            $w->orWhere('wa_stock_breaking.breaking_code', 'LIKE', "%$search%");
                            $w->orWhere('users.name', 'LIKE', "%$search%");
                        }
                    });
                if ($user->role_id != 1) {
                    $data = $data->where('users.wa_location_and_store_id', $user->wa_location_and_store_id);
                }
                // Compute total count BEFORE ordering/pagination to avoid SQL issues
                $totalCms       = (clone $data)->count('wa_stock_breaking.id');
                // Apply ordering after count
                $data = $data->orderBy($sortable_columns[$orderbyIndex], $order);
                $response       = $data->limit($limit)->offset($start)->get()->map(function ($item) use ($permission, $user) {
                    $item->user_name = @$item->user_name;
                    $item->date_time = $item->date . ' / ' . $item->time;
                    $item->payment_title = @$item->payment->title;
                    $item->dispatch_status = $item->dispatched ? 'DISPATCHED' : 'NOT DISPATCHED';
                    $item->dispatch_date = $item->dispatched_date ? $item->dispatched_date->format('Y-m-d / H:i:s') : 'N/A';
                    $item->links = '';
                    if ($item->status == 'PENDING') {
                        $item->links .= '<a style="margin: 2px;" class="btn btn-warning btn-sm" href="' . route('stock-breaking.edit', base64_encode($item->id)) . '" title="Details"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
                    }
                    $item->links .= '<a style="margin: 2px;"  class="btn btn-danger btn-sm" href="' . route('stock-breaking.show', base64_encode($item->id)) . '" title="Details"><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    if ($item->status == 'PROCESSED' && $item->dispatched) {
                        $item->links .= '<a style="margin: 2px;"  class="btn btn-primary btn-sm printBill" onclick="printBill(this); return false;" href="' . route('stock-breaking.invoice_print', base64_encode($item->id)) . '" title="Print"><i class="fa fa-print" aria-hidden="true"></i></a>
                                <a style="margin: 2px;"  class="btn btn-warning btn-sm" href="' . route('stock-breaking.exportToPdf', base64_encode($item->id)) . '" target="_blank" title="PDF">
                                    <i class="fa fa-file-pdf" aria-hidden="true"></i>
                                </a>';
                    }
                    if ($item->status != 'PENDING' && !$item->dispatched && $user->uom && $user->uom->isDisplay()) {
                        $createDispatchRoute = route('stock-breaking.create-dispatch', $item->id);
                        $csrf = csrf_field();
                        $item->links .= <<<TEXT
                            <form method="post" action="$createDispatchRoute" style="display: inline-block;">
                                $csrf
                                <input type="hidden" name="id" value="$item->id">
                                <button type="submit" class="btn btn-info btn-sm" title="Create Dispatch" style="margin: 2px">
                                    <i class="fa fa-arrow-right"></i>
                                </button>
                            </form>
                        TEXT;
                    }
                    return $item;
                });

                $return = [
                    "draw"              =>  intval($draw),
                    "recordsFiltered"   =>  intval($totalCms),
                    "recordsTotal"      =>  intval($totalCms),
                    "data"              =>  $response
                ];

                return response()->json($return);
                } catch (\Throwable $e) {
                    \Log::error('StockBreaking index AJAX error', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    return response()->json([
                        'message' => 'Server error loading stock breaking data',
                        'error' => $e->getMessage(),
                    ], 500);
                }
            }

            return view('admin.stock_breaking.index', compact('user', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            // If this is an AJAX/DataTables request and the user lacks permission, return 403 JSON
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'You do not have permission to view Stock Breaking.'
                ], 403);
            }
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function show($id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            $data = WaStockBreaking::with(['items', 'user', 'items.source_item', 'items.destination_item'])->where('id', $id)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            return view('admin.stock_breaking.view', compact('data', 'title', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }
    public function edit($id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___edit']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            $getLoggeduserProfile = getLoggeduserProfile();
            $data = WaStockBreaking::with(['items', 'user', 'items.source_item', 'items.destination_item', 'items.source_item.getAllFromStockMoves'])->where('id', $id)->first();
            if (!$data) {
                Session::flash('warning', 'Invalid Request');
                return redirect()->back();
            }
            return view('admin.stock_breaking.edit', compact('title', 'model', 'breadcum', 'pmodule', 'permission', 'data'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }
    public function create(Request $request)
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___add']) || $permission == 'superadmin') {
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.stock_breaking.create', compact('title', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function getInventryItemDetails(Request $request)
    {
        $user = Auth::user();
        $data = WaInventoryItem::select([
            '*',
            // DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
            DB::RAW("(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code AND wa_stock_moves.wa_location_and_store_id = '$user->wa_location_and_store_id') as quantity"),

        ])->where('id', $request->id)->first();
        $view = '';
        if ($data) {
            $view .= '<tr>                                      
            <td>
                <input type="hidden" name="item_id[' . $data->id . ']" class="itemid" value="' . $data->id . '">
                <input style="padding: 3px 3px;"  type="text" class="testIn form-control" value="' . $data->stock_id_code . '">
                <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
            </td>
            <td class="item_description">' . $data->description . '</td>
            <td>' . ($data->quantity ?? 0) . '</td>
            <td>
                <input type="hidden" name="alternateid[' . $data->id . ']" class="alternateid" value="">
                <input style="padding: 3px 3px;"  type="text" class="alternateIn form-control" value="">
                <div class="alternateid_data" style="width: 100%;position: relative;z-index: 99;"></div>
            </td>
            <td class="alternate_desc"></td>
            <td><input style="padding: 3px 3px;"  type="number" name="source_qty[' . $data->id . ']" class="form-control quantity_cal" value="" onchange="quantity_packsize_cal(this)" onkeyup="quantity_packsize_cal(this)"></td>
            <td><input style="padding: 3px 3px;"  type="number" readonly name="conversion_factor[' . $data->id . ']" class="form-control packsize_cal conversion_factor" value="" onchange="quantity_packsize_cal(this)" onkeyup="quantity_packsize_cal(this)"></td>
            <td class="quantity_packsize_cal"></td>
            <td>
            <button type="button" class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
            </tr>';
        }
        return response()->json($view);
    }

    public function inventoryItems(Request $request)
    {
        $user = Auth::user();
        if ($request->type == 'alternate') {

            $arr = [];
            $selectedItem = WaInventoryItem::with('destinated_items')->where('id', $request->item)->first();
            if ($selectedItem && count($selectedItem->destinated_items) > 0) {
                $arr = $selectedItem->destinated_items->pluck('destination_item_id')->toArray();
            }

            $data = WaInventoryItem::select([
                '*',
                DB::RAW("(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code AND wa_stock_moves.wa_location_and_store_id = '$user->wa_location_and_store_id') as quantity"),
            ])->where(function ($q) use ($request) {
                if ($request->search) {
                    $q->where('title', 'LIKE', "%$request->search%");
                    $q->orWhere('stock_id_code', 'LIKE', "%$request->search%");
                }
            })->with(['getAssignedItem' => function ($e) use ($request) {
                $e->where('wa_inventory_item_id', $request->item);
            }])->whereIn('id', $arr)->limit(20)->get();
            $view = '<table class="table table-bordered table-hover" id="stock_inventory" style="
            display: block;
            right: auto !important;
            position: absolute;
            min-width: 400px;
            left: 0 !important;
            max-height: 350px;
            margin-top: 4px!important;
            overflow: auto;
            padding: 0;
            background:#fff;
            ">';
            $view .= "<thead>";
            $view .= '<tr>';
            $view .= '<th style="width:20%">Code</th>';
            $view .= '<th style="width:70%">Description</th>';
            $view .= '<th style="width:10%">QOH</th>';
            $view .= '</tr>';
            $view .= '</thead>';
            $view .= "<tbody>";
            foreach ($data as $key => $value) {
                $view .= '<tr onclick="fetchInventoryDetails(this)" data-conversion_factor="' . $value->getAssignedItem->conversion_factor . '" data-type="' . $request->type . '" data-id="' . $value->id . '" data-stock_id_code="' . $value->stock_id_code . '" data-description="' . $value->description . '">';
                $view .= '<td style="width:20%">' . $value->stock_id_code . '</td>';
                $view .= '<td style="width:70%">' . $value->title . '</td>';
                $view .= '<td style="width:10%">' . ($value->quantity ?? 0) . '</td>';
                $view .= '</tr>';
            }
            $view .= '</tbody>';
            $view .= '</table>';
            return response()->json($view);
        }


        $data = WaInventoryItem::select([
            '*',
            DB::RAW("(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code AND wa_stock_moves.wa_location_and_store_id = '$user->wa_location_and_store_id') as quantity"),
            // DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
        ])->where(function ($q) use ($request) {
            if ($request->search) {
                $q->where('title', 'LIKE', "%$request->search%");
                $q->orWhere('stock_id_code', 'LIKE', "%$request->search%");
            }
        })->limit(20)->get();
        $view = '<table class="table table-bordered table-hover" id="stock_inventory" style="
        display: block;
        right: auto !important;
        position: absolute;
        min-width: 400px;
        left: 0 !important;
        max-height: 350px;
        margin-top: 4px!important;
        overflow: auto;
        padding: 0;
        background:#fff;
        ">';
        $view .= "<thead>";
        $view .= '<tr>';
        $view .= '<th style="width:20%">Code</th>';
        $view .= '<th style="width:70%">Description</th>';
        $view .= '<th style="width:10%">QOH</th>';
        $view .= '</tr>';
        $view .= '</thead>';
        $view .= "<tbody>";
        foreach ($data as $key => $value) {
            $view .= '<tr onclick="fetchInventoryDetails(this)" data-type="' . $request->type . '" data-id="' . $value->id . '" data-stock_id_code="' . $value->stock_id_code . '" data-description="' . $value->description . '">';
            $view .= '<td style="width:20%">' . $value->stock_id_code . '</td>';
            $view .= '<td style="width:70%">' . $value->title . '</td>';
            $view .= '<td style="width:10%">' . ($value->quantity ?? 0) . '</td>';
            $view .= '</tr>';
        }
        $view .= '</tbody>';
        $view .= '</table>';
        return response()->json($view);
    }

    public function processAutomaticStockBreaking($destinationItemId, $sourceQty)
    {
        try {
            DB::beginTransaction();
            $destinationItem = WaInventoryItem::where('id', $destinationItemId)->firstOrFail();

            // Get source item through assigned items relationship
            $assignedItem = $destinationItem->getAssignedItem;
            if (!$assignedItem) {
                throw new Exception("No assigned source item found for destination item ID: $destinationItemId");
            }
            $sourceItemId = $assignedItem->wa_inventory_item_id;

            $sourceItem = WaInventoryItem::where('id', $sourceItemId)->firstOrFail();
            if ($sourceItem->quantity < $sourceQty) {
                throw new Exception("Insufficient source item quantity for automatic stock breaking.");
            }

            $conversionFactor = $assignedItem->conversion_factor ?? 1;
            $destinationQty = $sourceQty * $conversionFactor;
            $sourceItem->quantity -= $sourceQty;
            $sourceItem->save();

            $sourceStockMove = [
                'user_id' => getLoggeduserProfile()->id,
                'restaurant_id' => getLoggeduserProfile()->restaurant_id,
                'wa_location_and_store_id' => getLoggeduserProfile()->wa_location_and_store_id,
                'wa_inventory_item_id' => $sourceItemId,
                'standard_cost' => $sourceItem->standard_cost,
                'qauntity' => -$sourceQty,
                'new_qoh' => $sourceItem->quantity,
                'stock_id_code' => $sourceItem->stock_id_code,
                'grn_type_number' => getCodeWithNumberSeries('STOCKBREAKING'),
                'price' => - ($sourceItem->standard_cost * $sourceQty),
                'document_no' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            WaStockMove::insert($sourceStockMove);

            $destinationItem->quantity += $destinationQty;
            $destinationItem->save();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            logMessage('error', 'Automatic stock breaking failed: ' . $e->getMessage());
            throw $e;
        }
    }


    public function store(Request $request)
    {
        $user = Auth::user();

        $validations = Validator::make($request->all(), [
            'time' => 'required',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:wa_inventory_items,id',
            'alternateid.*' => 'required|exists:wa_inventory_items,id',
            'source_qty.*' => 'required|numeric|min:1',
            'conversion_factor.*' => 'required|numeric|min:1',
        ], [], [
            'item_id.*' => 'Source Item',
            'alternateid.*' => 'Destination Item',
            'source_qty.*' => 'Source Qty',
            'conversion_factor.*' => 'Conversion Factor',
        ]);
        if ($validations->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validations->errors()
            ]);
        }
        $item_id = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code and wa_stock_moves.wa_location_and_store_id =' . $user->wa_location_and_store_id . ' ) as quantity'),
        ])->whereIn('id', $request->item_id)->get();
        $alternateitems = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code and wa_stock_moves.wa_location_and_store_id = ' . $user->wa_location_and_store_id . ') as quantity'),
        ])->whereIn('id', $request->alternateid)->get();
        $error = [];
        foreach ($item_id as $key => $value) {
            if (!isset($request->alternateid[$value->id]) || $request->alternateid[$value->id] == '') {
                $error['alternateid.' . $value->id] = ['Destination Item is required'];
            } elseif ($value->id == @$request->alternateid[$value->id]) {
                $error['alternateid.' . $value->id] = ['Source Item and Destination Item needs to be different'];
            }
            if ($value->quantity < 0) {
                $error['item_id.' . $value->id] = ['Source Item don\'t have enough quantity'];
            }
            if (!isset($request->source_qty[$value->id]) || $request->source_qty[$value->id] == '' || $value->quantity < $request->source_qty[$value->id]) {
                $error['source_qty.' . $value->id] = ['Source Item don\'t have enough quantity'];
            }
            if (!isset($request->conversion_factor[$value->id]) || $request->conversion_factor[$value->id] == '') {
                $error['conversion_factor.' . $value->id] = ['Conversion factor is required'];
            }
        }
        if (count($error) > 0) {
            return response()->json([
                'result' => 0,
                'errors' => $error
            ]);
        }
        $check = DB::transaction(function () use ($item_id, $alternateitems, $request) {
            $items = [];
            $WaStockMove = [];
            $date = date('Y-m-d');
            $time = date('H:i:s');
            $user = getLoggeduserProfile();
            $series_module = WaNumerSeriesCode::where('module', 'STOCKBREAKING')->first();
            $grn_number = getCodeWithNumberSeries('STOCKBREAKING');
            $parent = new WaStockBreaking;
            $parent->user_id = $user->id;
            $parent->date = $date;
            $parent->time = $request->time;
            $parent->breaking_code = $grn_number;
            $parent->status = 'PENDING';
            $parent->save();

            foreach ($item_id as $key => $value) {
                $totalQty = ($request->conversion_factor[$value->id] * $request->source_qty[$value->id]);
                $items[] = [
                    'wa_stock_breaking_id' => $parent->id,
                    'source_item_id' => $value->id,
                    'source_item_bal_stock' => $value->quantity,
                    'source_qty' => $request->source_qty[$value->id],
                    'destination_item_id' => $request->alternateid[$value->id],
                    'conversion_factor' => $request->conversion_factor[$value->id],
                    'destination_qty' => $totalQty,
                    'created_at' => $date . ' ' . $time,
                    'updated_at' => $date . ' ' . $time,
                ];
                if ($request->request_type != 'save') {
                    $pvstock_qoh = @$value->quantity;
                    $pvstock_qoh -= $request->source_qty[$value->id];

                    $WaStockMove[] = [
                        'user_id' => $user->id,
                        'restaurant_id' => $user->restaurant_id,
                        'wa_location_and_store_id' => $user->wa_location_and_store_id,
                        // 'wa_location_and_store_id'=>46,
                        'wa_inventory_item_id' => $value->id,
                        'standard_cost' => $value->standard_cost,
                        'qauntity' => - ($request->source_qty[$value->id]),
                        'new_qoh' => $pvstock_qoh,
                        'stock_id_code' => $value->stock_id_code,
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => $series_module->last_number_used,
                        'price' => - ($value->standard_cost * ($request->source_qty[$value->id])),
                        'document_no' => $grn_number,
                        'selling_price' => $value->selling_price,
                        'refrence' => $grn_number . '/Manual-Stock-Break',
                        'updated_at' => $date . ' ' . $time,
                        'created_at' => $date . ' ' . $time,
                    ];
                    $alteritem = @$alternateitems->where('id', $request->alternateid[$value->id])->first();
                    $pvstock_qoh = @$alteritem->quantity;
                    $pvstock_qoh += $totalQty;
                    $WaStockMove[] = [
                        'user_id' => $user->id,
                        'restaurant_id' => $user->restaurant_id,
                        'wa_location_and_store_id' => $user->wa_location_and_store_id,
                        // 'wa_location_and_store_id'=>46,
                        'wa_inventory_item_id' => @$alteritem->id,
                        'standard_cost' => @$alteritem->standard_cost,
                        'qauntity' => ($totalQty),
                        'new_qoh' => $pvstock_qoh,
                        'stock_id_code' => @$alteritem->stock_id_code,
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => $series_module->last_number_used,
                        'price' => (@$alteritem->standard_cost * ($totalQty)),
                        'document_no' => $grn_number,
                        'selling_price' => @$alteritem->selling_price,
                        'refrence' => $grn_number . '/Manual-Stock-Break',
                        'updated_at' => $date . ' ' . $time,
                        'created_at' => $date . ' ' . $time,
                    ];
                }
            }
            if (count($WaStockMove) > 0) {
                \App\Model\WaStockMove::insert($WaStockMove);
            }
            if (count($items) > 0) {
                WaStockBreakingItem::insert($items);
            }
            if ($request->request_type != 'save') {
                $parent->status = 'PROCESSED';
                $parent->save();
            }
            updateUniqueNumberSeries('STOCKBREAKING', $grn_number);
            return true;
        });
        if ($check) {
            $location = route($this->model . '.index');
            if ($request->request_type == 'save') {
                $message = 'Saved Successfully';
            } else {
                $message = 'Processed Successfully';
            }
            return response()->json([
                'result' => 1,
                'message' => $message,
                'location' => $location
            ]);
        }
    }
    public function invoice_print($id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $data = WaStockBreaking::with(['items', 'user', 'items.source_item', 'items.destination_item', 'posRequest'])->where('id', $id)->first();
        if (!$data) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
        $settings = getAllSettings();

        return view('admin.stock_breaking.print', compact('settings','data', 'title', 'model', 'pmodule', 'permission'));
    }

    public function exportToPdf($id)
    {
        $id = base64_decode($id);
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        $data = WaStockBreaking::with(['items', 'user', 'items.source_item', 'items.destination_item', 'posRequest'])->where('id', $id)->first();
        if (!$data) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
        $data->print_count++;
        $data->save();
        $settings = getAllSettings();
        $pdf = \PDF::loadView('admin.stock_breaking.print', compact('settings','data', 'title', 'model', 'pmodule', 'permission'));
        $report_name = 'stock_breaking_' . date('Y_m_d_H_i_A');
        return $pdf->stream($report_name . '.pdf');
    }
    public function update(Request $request, $id)
    {
        $id = base64_decode($id);
        $user = Auth::user();
        $validations = Validator::make($request->all(), [
            'time' => 'required',
            'id' => 'required|exists:wa_stock_breaking,id',
            'item_id' => 'required|array',
            'item_id.*' => 'required|exists:wa_inventory_items,id',
            'alternateid.*' => 'required|exists:wa_inventory_items,id',
            'source_qty.*' => 'required|numeric|min:1',
            'conversion_factor.*' => 'required|numeric|min:1',
        ], [], [
            'item_id.*' => 'Source Item',
            'alternateid.*' => 'Destination Item',
            'source_qty.*' => 'Source Qty',
            'conversion_factor.*' => 'Conversion Factor',
        ]);
        if ($validations->fails()) {
            return response()->json([
                'result' => 0,
                'errors' => $validations->errors()
            ]);
        }
        $item_id = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
        ])->whereIn('id', $request->item_id)->get();
        $alternateitems = WaInventoryItem::select([
            '*',
            DB::RAW('(select SUM(wa_stock_moves.qauntity) FROM wa_stock_moves where wa_stock_moves.stock_id_code = wa_inventory_items.stock_id_code) as quantity'),
        ])->whereIn('id', $request->alternateid)->get();

        $error = [];
        foreach ($item_id as $key => $value) {
            if (!isset($request->alternateid[$value->id]) || $request->alternateid[$value->id] == '') {
                $error['alternateid.' . $value->id] = ['Destination Item is required'];
            } elseif ($value->id == @$request->alternateid[$value->id]) {
                $error['alternateid.' . $value->id] = ['Source Item and Destination Item needs to be different'];
            }
            if ($value->quantity < 0) {
                $error['item_id.' . $value->id] = ['Source Item don\'t have enough quantity'];
            }
            if (!isset($request->source_qty[$value->id]) || $request->source_qty[$value->id] == '' || $value->quantity < $request->source_qty[$value->id]) {
                $error['source_qty.' . $value->id] = ['Source Item don\'t have enough quantity'];
            }
            if (!isset($request->conversion_factor[$value->id]) || $request->conversion_factor[$value->id] == '') {
                $error['conversion_factor.' . $value->id] = ['Conversion factor is required'];
            }
        }
        if (count($error) > 0) {
            return response()->json([
                'result' => 0,
                'errors' => $error
            ]);
        }
        $parent = WaStockBreaking::find($id);
        if (!$parent || $parent->status != 'PENDING') {
            return response()->json([
                'result' => -1,
                'message' => 'Something went wrong | Refresh the page'
            ]);
        }
        $check = DB::transaction(function () use ($item_id, $alternateitems, $request, $parent) {
            $items = [];
            $WaStockMove = [];
            $date = date('Y-m-d');
            $time = date('H:i:s');
            $user = getLoggeduserProfile();
            $series_module = WaNumerSeriesCode::where('module', 'STOCKBREAKING')->first();
            $grn_number = $parent->breaking_code;
            WaStockBreakingItem::where('wa_stock_breaking_id', $parent->id)->delete();

            foreach ($item_id as $key => $value) {
                $totalQty = ($request->conversion_factor[$value->id] * $request->source_qty[$value->id]);
                $items[] = [
                    'wa_stock_breaking_id' => $parent->id,
                    'source_item_id' => $value->id,
                    'source_item_bal_stock' => $value->quantity,
                    'source_qty' => $request->source_qty[$value->id],
                    'destination_item_id' => $request->alternateid[$value->id],
                    'conversion_factor' => $request->conversion_factor[$value->id],
                    'destination_qty' => $totalQty,
                    'created_at' => $date . ' ' . $time,
                    'updated_at' => $date . ' ' . $time,
                ];
                if ($request->request_type != 'save') {
                    $pvstock_qoh = @$value->quantity;
                    $pvstock_qoh -= $request->source_qty[$value->id];
                    $WaStockMove[] = [
                        'user_id' => $user->id,
                        'restaurant_id' => $user->restaurant_id,
                        // 'wa_location_and_store_id'=>$value->store_location_id,
                        'wa_location_and_store_id' => $user->wa_location_and_store_id,
                        'wa_inventory_item_id' => $value->id,
                        'standard_cost' => $value->standard_cost,
                        'qauntity' => - ($request->source_qty[$value->id]),
                        'new_qoh' => $pvstock_qoh,
                        'stock_id_code' => $value->stock_id_code,
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => $series_module->last_number_used,
                        'price' => - ($value->standard_cost * ($request->source_qty[$value->id])),
                        'document_no' => $grn_number,
                        'selling_price' => $value->selling_price,
                        'refrence' => $grn_number . '/Manual-Stock-Break',
                        'updated_at' => $date . ' ' . $time,
                        'created_at' => $date . ' ' . $time,
                    ];
                    $alteritem = @$alternateitems->where('id', $request->alternateid[$value->id])->first();
                    $pvstock_qoh = @$alteritem->quantity;
                    $pvstock_qoh += $totalQty;
                    $WaStockMove[] = [
                        'user_id' => $user->id,
                        'restaurant_id' => $user->restaurant_id,
                        // 'wa_location_and_store_id'=>@$alteritem->store_location_id,
                        'wa_location_and_store_id' => $user->wa_location_and_store_id,
                        'wa_inventory_item_id' => @$alteritem->id,
                        'standard_cost' => @$alteritem->standard_cost,
                        'qauntity' => ($totalQty),
                        'new_qoh' => $pvstock_qoh,
                        'stock_id_code' => @$alteritem->stock_id_code,
                        'grn_type_number' => $series_module->type_number,
                        'grn_last_nuber_used' => $series_module->last_number_used,
                        'price' => (@$alteritem->standard_cost * ($totalQty)),
                        'document_no' => $grn_number,
                        'selling_price' => @$alteritem->selling_price,
                        'refrence' => $grn_number . '/Manual-Stock-Break',
                        'updated_at' => $date . ' ' . $time,
                        'created_at' => $date . ' ' . $time,
                    ];
                }
            }
            if (count($WaStockMove) > 0) {
                \App\Model\WaStockMove::insert($WaStockMove);
            }
            if (count($items) > 0) {
                WaStockBreakingItem::insert($items);
            }
            if ($request->request_type != 'save') {
                $parent->status = 'PROCESSED';
                $parent->save();
            }
            // updateUniqueNumberSeries('STOCKBREAKING',$grn_number);
            return true;
        });
        if ($check) {
            $location = route($this->model . '.index');
            if ($request->request_type == 'save') {
                $message = 'Saved Successfully';
            } else {
                $message = 'Processed Successfully';
            }
            return response()->json([
                'result' => 1,
                'message' => $message,
                'location' => $location
            ]);
        }
    }

    public function createDispatch(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:wa_stock_breaking,id'
        ]);

        $stockBreak = WaStockBreaking::with('user')
            ->where('id', $request->id)
            ->where('status', '!=', 'PENDING')
            ->where('dispatched', false)
            ->first();

        if (!$stockBreak) {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }

        // Check if user has required relationships - DISABLED
        // User profile validation disabled as per user request
        /*
        if (!$stockBreak->user || !$stockBreak->user->wa_unit_of_measures_id) {
            Session::flash('danger', 'User profile is incomplete. Please contact administrator.');
            return redirect()->back();
        }
        */

        $locationId = $stockBreak->user && $stockBreak->user->wa_location_and_store_id 
            ? $stockBreak->user->wa_location_and_store_id 
            : null;
            
        $stockBreakItemGroups = $stockBreak->items()
            ->with([
                'source_item.binLocation' => fn($query) => $locationId ? $query->where('location_id', $locationId) : $query,
                'source_item.pack_size',
                'destination_item.pack_size'
            ])
            ->get()
            ->groupBy(function ($stockBreakItem) {
                // Ensure relationships are loaded
                if (!$stockBreakItem->source_item || !$stockBreakItem->source_item->binLocation) {
                    return null;
                }
                return $stockBreakItem->source_item->binLocation->uom_id;
            });

        // Filter out null groups
        $stockBreakItemGroups = $stockBreakItemGroups->filter(function ($group, $key) {
            return $key !== null && $group->count() > 0;
        });

        // Check if we have any valid items to process
        if ($stockBreakItemGroups->isEmpty()) {
            Session::flash('warning', 'No valid items found for dispatch. Items may be missing bin location data.');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            foreach ($stockBreakItemGroups as $binId => $stockBreakItemGroup) {
                // Skip if binId is null
                if ($binId === null) {
                    continue;
                }

                // Ensure we have a valid child_bin_id
                $childBinId = $stockBreak->user && $stockBreak->user->wa_unit_of_measures_id 
                    ? $stockBreak->user->wa_unit_of_measures_id 
                    : $binId; // Use mother_bin_id as fallback
                
                $dispatch = StockBreakDispatch::create([
                    'child_bin_id' => $childBinId,
                    'mother_bin_id' => $binId,
                    'initiated_by' => $stockBreak->user ? $stockBreak->user->id : auth()->id()
                ]);

                foreach ($stockBreakItemGroup as $stockBreakItem) {
                    // Ensure all required relationships exist
                    if (!$stockBreakItem->destination_item || !$stockBreakItem->source_item) {
                        \Log::error('Missing item relationships', [
                            'stock_break_item_id' => $stockBreakItem->id,
                            'destination_item' => $stockBreakItem->destination_item_id,
                            'source_item' => $stockBreakItem->source_item_id,
                        ]);
                        continue;
                    }

                    $dispatch->items()->create([
                        'child_item_id' => $stockBreakItem->destination_item_id,
                        'child_quantity' => $stockBreakItem->destination_qty,
                        'child_pack_size' => $stockBreakItem->destination_item->pack_size ? $stockBreakItem->destination_item->pack_size->title : 'N/A',
                        'mother_item_id' => $stockBreakItem->source_item_id,
                        'mother_quantity' => $stockBreakItem->source_qty,
                        'mother_pack_size' => $stockBreakItem->source_item->pack_size ? $stockBreakItem->source_item->pack_size->title : 'N/A',
                    ]);
                }

                $stockBreak->update([
                    'dispatched' => true,
                    'dispatched_date' => now(),
                ]);
            }

            DB::commit();

            $dispatchCount = $stockBreakItemGroups->count();
            $totalItems = $stockBreak->items()->count();
            $processedItems = $stockBreakItemGroups->sum(fn($group) => $group->count());
            $skippedCount = $totalItems - $processedItems;

            $message = 'Stock break initiated successfully. ';
            if ($skippedCount > 0) {
                $message .= "Note: $skippedCount item(s) were skipped due to missing bin location data. ";
            }
            $message .= 'The items are ready for dispatch at the mother bin.';

            Session::flash('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Stock break dispatch creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'stock_break_id' => $stockBreak->id,
                'user_id' => $stockBreak->user_id,
            ]);

            Session::flash('danger', 'Error creating dispatch: ' . $e->getMessage());
        }

        return redirect()->route('stock-breaking.index');
    }
}
