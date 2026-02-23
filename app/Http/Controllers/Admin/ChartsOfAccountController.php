<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WaChartsOfAccount;
use App\Model\WaAccountSection;
use App\Model\WaGlTran;
use App\Model\Restaurant;
use DB;
use Session;
use Illuminate\Support\Facades\Validator;
use App\Services\ExcelDownloadService;

class ChartsOfAccountController extends Controller
{

    protected $model;
    protected $title;
    protected $pmodule;
    public function __construct()
    {
        $this->model = 'chart-of-accounts';
        $this->title = 'Chart Of Accounts';
        $this->pmodule = 'chart-of-accounts';
    }

    public function glEntriesByAccountcode(Request $request, $accountcode)
    {
        $permission = $this->mypermissionsforAModule();
        $pmodule = "genralLedger";
        $title = 'GL Entries';
        $model = 'genralLedger-gl_entries';

        $date1 = $request->get('to');
        $date2 = $request->get('from');

        $restroList = $this->getRestaurantList();

        $data = WaGlTran::orderBy('id', 'desc')->with('restaurant');
        $data->where('account', $accountcode);

        if ($request->restaurant) {
            $data->where('restaurant_id', $request->restaurant);
        }
        $data->whereDate('trans_date', '>=', $date1)->whereDate('trans_date', '<=', $date2);

        $data = $data->get();

        $dataarr = [];
        foreach ($data as $key => $val) {
            $data = WaGlTran::orderBy('id', 'desc')->with('restaurant');
            $data->where('account', $accountcode);
            $data->orWhere('transaction_no', $val->transaction_no);
            if ($request->restaurant) {
                $data->where('restaurant_id', $request->restaurant);
            }
            $data->whereDate('trans_date', '>=', $date1)->whereDate('trans_date', '<=', $date2);

            $data = $data->get();

            $dataarr[$key] = $data;

            $negativeAMount =  WaGlTran::where('amount', '<=', '0');
            $negativeAMount->where('account', $accountcode);
            $negativeAMount->orWhere('transaction_no', $val->transaction_no);
            $negativeAMount = $negativeAMount->sum('amount');

            $positiveAMount =  WaGlTran::where('amount', '>=', '0');
            $positiveAMount->where('account', $accountcode);
            $positiveAMount->orWhere('transaction_no', $val->transaction_no);
            $positiveAMount = $positiveAMount->sum('amount');
        }


        $breadcum = [$title => ''];
        $data = $dataarr;
        //echo "<pre>"; print_r($data); die;
        return view('admin.chartofaccount.gl_entries', compact('data', 'title', 'restroList', 'model', 'breadcum', 'pmodule', 'permission', 'negativeAMount', 'positiveAMount'));
    }

    public function index()
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            // $lists = WaChartsOfAccount::with(['child_accounts.getRelatedGroup.getAccountSection','getRelatedGroup.getAccountSection'])->get();
            $lists = WaAccountSection::with([
                'getWaAccountGroup',
                'getWaAccountGroup.accountSubSections',
                'getWaAccountGroup.accountSubSections.accounts'
            ])->get();
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.chartofaccount.index', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }

    public function newindex()
    {
        $permission =  $this->mypermissionsforAModule();
        $pmodule = $this->pmodule;
        $title = $this->title;
        $model = $this->model;
        if (isset($permission[$pmodule . '___view']) || $permission == 'superadmin') {
            $lists = WaChartsOfAccount::with(['child_accounts.getRelatedGroup.getAccountSection', 'getRelatedGroup.getAccountSection'])->orderBy('account_code')->get();
            $breadcum = [$title => route($model . '.index'), 'Listing' => ''];
            return view('admin.chartofaccount.newlist', compact('title', 'lists', 'model', 'breadcum', 'pmodule', 'permission'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function create()
    {
        // return 'here';
        $permission =  $this->mypermissionsforAModule();
        if (isset($permission[$this->pmodule . '___add']) || $permission == 'superadmin') {
            $title = 'Add ' . $this->title;
            $model = $this->model;
            $branches = Restaurant::pluck('name', 'id')->toArray();

            $lists = WaChartsOfAccount::where('is_parent', 1)->orderBy('id', 'DESC')->pluck('account_name', 'id')->toArray();
            $breadcum = [$this->title => route($model . '.index'), 'Add' => ''];
            return view('admin.chartofaccount.create', compact('title', 'model', 'breadcum', 'lists', 'branches'));
        } else {
            Session::flash('warning', 'Invalid Request');
            return redirect()->back();
        }
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_name' => 'required|max:255',
                'account_code' => 'required|unique:wa_charts_of_accounts',
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {

                $row = new WaChartsOfAccount();
                $row->account_name = $request->account_name;
                $row->account_code = $request->account_code;
                $row->wa_account_sub_section_id = $request->wa_sub_account_section_id;
                $row->wa_account_group_id = $request->wa_account_group_id;
                // $row->pl_or_bs= $request->pl_or_bs;
                // $row->is_parent = (isset($request->parent_group) && $request->parent_group == 1) ? 1 : 0;
                // $row->parent_id = (isset($request->parent_id) && $request->parent_id != 0) ? $request->parent_id : NULL;
                $row->save();
                $branches = [];
                // DB::table('wa_chart_of_accounts_branches')->where('wa_supplier_id',$row->id)->delete();
                if (isset($request->branches) && count($request->branches)) {
                    foreach ($request->branches as $key => $value) {
                        $branches[] = [
                            'wa_chart_of_account_id' => $row->id,
                            'restaurant_id' => $value,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ];
                    }
                    if (count($branches) > 0) {
                        DB::table('wa_chart_of_accounts_branches')->insert($branches);
                    }
                }
                Session::flash('success', 'Record added successfully.');
                return redirect()->route($this->model . '.index');
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }


    public function show($account_code)
    {
        try {
            $permission =  $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row =  WaChartsOfAccount::with(['branches'])->where('account_code', $account_code)->first();
                if ($row) {
                    $branches = Restaurant::pluck('name', 'id')->toArray();

                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;
                    $lists = WaChartsOfAccount::where('is_parent', 1)->where('id', '!=', $row->id)->orderBy('id', 'DESC')->pluck('account_name', 'id')->toArray();
                    return view('admin.chartofaccount.edit', compact('title', 'model', 'breadcum', 'row', 'lists', 'branches'));
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


    public function edit($slug)
    {
        try {
            $permission =  $this->mypermissionsforAModule();
            if (isset($permission[$this->pmodule . '___edit']) || $permission == 'superadmin') {
                $row =  WaChartsOfAccount::with(['branches'])->whereSlug($slug)->first();
                if ($row) {
                    $branches = Restaurant::pluck('name', 'id')->toArray();

                    $title = 'Edit ' . $this->title;
                    $breadcum = [$this->title => route($this->model . '.index'), 'Edit' => ''];
                    $model = $this->model;
                    $lists = WaChartsOfAccount::where('is_parent', 1)->where('id', '!=', $row->id)->orderBy('id', 'DESC')->pluck('account_name', 'id')->toArray();
                    return view('admin.chartofaccount.edit', compact('title', 'model', 'breadcum', 'row', 'lists', 'branches'));
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
        try {

            $row =  WaChartsOfAccount::whereSlug($slug)->first();
            $validator = Validator::make($request->all(), [
                'account_name' => 'required|max:255',
                'account_code' => 'required|unique:wa_charts_of_accounts,account_code,' . $row->id,

            ]);
            if ($validator->fails()) {
                return redirect()->back()->withInput()->withErrors($validator->errors());
            } else {

                $row->account_name = $request->account_name;
                // $row->account_code= $request->account_code;
                $row->wa_account_group_id = $request->wa_account_group_id;
                $row->wa_account_sub_section_id = $request->wa_account_sub_section_id;
                // $row->pl_or_bs= $request->pl_or_bs;
                // $row->is_parent = (isset($request->parent_group) && $request->parent_group == 1) ? 1 : 0;
                // $row->parent_id = (isset($request->parent_id) && $request->parent_id != 0 && $request->parent_id != $row->id) ? $request->parent_id : NULL;
                $row->save();
                $branches = [];
                DB::table('wa_chart_of_accounts_branches')->where('wa_chart_of_account_id', $row->id)->delete();
                if (isset($request->branches) && count($request->branches)) {
                    foreach ($request->branches as $key => $value) {
                        $branches[] = [
                            'wa_chart_of_account_id' => $row->id,
                            'restaurant_id' => $value,
                            'created_at' => $row->created_at,
                            'updated_at' => $row->updated_at,
                        ];
                    }
                    if (count($branches) > 0) {
                        DB::table('wa_chart_of_accounts_branches')->insert($branches);
                    }
                }
                Session::flash('success', 'Record updated successfully.');
                return redirect()->route($this->model . '.index');
            }
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            Session::flash('warning', $msg);
            return redirect()->back()->withInput();
        }
    }


    public function destroy($slug)
    {
        try {

            $deleted = WaChartsOfAccount::whereSlug($slug)->delete();
            if ($deleted) {
                Session::flash('success', 'Deleted successfully.');
            } else {
                Session::flash('warning', 'Delete failed: record not found.');
            }
            return redirect()->back();
        } catch (\Throwable $e) {
            Session::flash('warning', 'Delete failed: ' . $e->getMessage());
            return redirect()->back();
        }
    }
    public function downloadCoaitems(Request $request)
    {

        try {
            $lists = WaChartsOfAccount::orderBy('id', 'DESC')->get();
            $arrays = [];
            if (!empty($lists)) {
                foreach ($lists as $key => $row) {
                    $arrays[] = [
                        'S.No.' => (string)($key + 1),
                        'Account Code' => $row->account_code,
                        'Account Name' => $row->account_name,
                        'Account Group' => (string)($row->getRelatedGroup ? $row->getRelatedGroup->group_name : ''),
                        'P/L Or B/S' => (string)($row->pl_or_bs),
                    ];
                }
            }

            $filename = 'chart-of-accounts-' . date('Y-m-d-H-i-s');
            $headings = ['S.No.', 'Account Code', 'Account Name', 'Account Group', 'P/L Or B/S'];
            return ExcelDownloadService::download($filename, collect($arrays), $headings);
        } catch (\Exception $th) {
            $request->session()->flash('danger', 'Something went wrong');
            return redirect()->back();
        }
    }

    // API
    public function expenseAccounts(Request $request)
    {
        $user = $request->user();
        
        $accounts = WaChartsOfAccount::select('id', 'account_name', 'account_code')
            ->unless($user->role_id == '1', function ($query) use ($user) {
                $query->whereHas('usergeneralledgeraccounts', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                });
            })
            ->whereHas('getSubAccountSection.getParentAccountGroup.getAccountSection', fn ($query) => $query->whereIn('section_name', ['EXPENSES']))
            ->orderBy('account_name')
            ->get();

        return response()->json($accounts);
    }
}
