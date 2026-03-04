<?php

namespace App\Http\Controllers\Admin;

use App\SalesmanShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Restaurant;
use App\Model\User;
use App\Model\Order;
use Illuminate\Support\Facades\Validator;
use File;
use DB;
use App\Model\ReceiptSummaryPayment;
use App\Model\WalletTransaction;
use App\Model\Bill;
use App\Model\OrderReceipt;
use App\Model\WaCashSales;
use App\Model\WaCashSalesItem;
use App\Model\WaStockMove;
use App\Model\WaSalesInvoice;
use App\Model\WaSalesInvoiceItem;




class PagesController extends Controller
{
    public function getMasterBillsPendingAmount()
   {
        $lists = Bill::where('status','PENDING')->with(['getAssociateOrdersWithBill','getAssociateOrdersWithBill.getAssociateOrderForBill'])->orderBy('id', 'desc')->get();
        $total_bill = [];
        foreach($lists as $list)
        {
            foreach($list->getAssociateOrdersWithBill as $single_order)
            {
                $total_bill[] = @$single_order->getAssociateOrderForBill->order_final_price;
            }
        }
        return array_sum($total_bill);
   }

     public function getOpenordersAmount()
   {
     $lists = Order::where('order_type','POSTPAID')
                ->whereNotIn('status',['CANCLED','PENDING'])
                ->doesnthave('getAssociateBillRelation')
                ->orderBy('id', 'desc')->sum('order_final_price');
          return $lists;
   }

     public function  getClosedOrderAmount()
   {
      $posts = OrderReceipt::with(['getAssociateOrdersWithReceipt','getAssociateOrdersWithReceipt.getAssociateOrderForReceipt'])->where('is_printed','0')
                        ->whereDate('created_at','>=',date('Y-m-d'))    
                         ->get();
        $total_bill = [];
            foreach ($posts as $list)
            {

                foreach($list->getAssociateOrdersWithReceipt as $single_order)
                {
                    $total_bill[] = $single_order->getAssociateOrderForReceipt->order_final_price;
                }
            }






        $closedOrderPayments = OrderReceipt::with(['getAssociateOrdersWithReceipt','getAssociateOrdersWithReceipt.getAssociateOrderForReceipt'])->whereDate('created_at','>=',date('Y-m-d'))   
                              ->get();
       foreach ($closedOrderPayments as $listing)
       {
         foreach($listing->getAssociateOrdersWithReceipt as $single_order)
         {
           $total_bill[] = $single_order->getAssociateOrderForReceipt->order_final_price;

         }
       }


        return array_sum($total_bill);
   }

    public function gethighestsellingsalesman(){
	    $lists = \App\Model\WaInventoryLocationTransfer::with(['getrelatedEmployee'])->select('id','user_id',
      DB::raw('(select SUM(wa_inventory_location_transfer_items.total_cost_with_vat) as totalamnt from wa_inventory_location_transfer_items where wa_inventory_location_transfer_id = `wa_inventory_location_transfers`.`id`) as totalamount'))
	  	->whereMonth('created_at', date('m'))
	    ->orderBy('totalamount', 'desc')
	    ->groupBy('user_id')
	    ->limit(5)
	    ->get();	    
		return $lists;
    }

    public function gethighestsellingproducts(){
	    $lists = \App\Model\WaInventoryLocationTransferItem::select(DB::raw('COUNT(wa_inventory_location_transfer_items.id) as cnt'), 'wa_inventory_items.stock_id_code as item_no','wa_inventory_items.title as item_name')
	    ->groupBy('wa_inventory_item_id')
      ->join('wa_inventory_items',function($w){
        $w->on('wa_inventory_items.id','=','wa_inventory_location_transfer_items.wa_inventory_item_id');
      })
	    ->whereMonth('wa_inventory_location_transfer_items.created_at', date('m'))
	    ->orderBy('cnt', 'desc')
	    ->limit(5)
	    ->get();	  
	    return $lists;  
		//echo "<pre>"; print_r($lists); die;
    }
    
	public function dashboard(Request $request)
	{
        $title = 'Admin';

        $restro_count= $users_count =$earningStats = $start_date = $end_date = $getUsersRegistrStats = $sale_transaction_month = $sale_transaction_year = $sales_transaction_stats = $getMasterBillsPendingAmount = $wallet_balance_cr =  $wallet_balance_dr =  $wallet_balance = $wallet_balance_sale_today = $getOpenordersAmount  = $getClosedOrderAmount = $highestsellingsalesman = $highestsellingproducts = NULL;
  //       $restro_count= Restaurant::count();
  //       $users_count = User::whereIn('role_id',['11'])->count();
  //       $earningStats = $this->getTotalEarningStats();
  //       //get user registarion stats
  //       $start_date = date('Y-m-d');
  //       $end_date =  date('Y-m-d',strtotime("-15 days", strtotime(date('Y-m-d'))));
  //       $getUsersRegistrStats = $this->getUsersRegistrStats($start_date,$end_date);


  //       //get sales transaction stats

  //       $sale_transaction_month = date('m');
  //       $sale_transaction_year = date('Y');

  //       if ($request->has('sale_transaction_month'))
  //       {
  //           $sale_transaction_month = $request->input('sale_transaction_month');
  //       }
        
  //       if ($request->has('sale_transaction_year'))
  //       {
  //           $sale_transaction_year = $request->input('sale_transaction_year');
  //       }

  //       $sales_transaction_stats = $this->getSalesTransactionDetails($sale_transaction_month,$sale_transaction_year);

  //        $getMasterBillsPendingAmount = $this->getMasterBillsPendingAmount();



  //      $wallet_balance_cr =  WalletTransaction::where('transaction_type','CR')->sum('amount');
  //      $wallet_balance_dr =  WalletTransaction::where('transaction_type','DR')->sum('amount');
  //      $wallet_balance = $wallet_balance_cr-$wallet_balance_dr;


  //      $wallet_balance_sale_today =  WalletTransaction::whereDate('created_at',date('Y-m-d'))->where('transaction_type','DR')->sum('amount');
  //        $getOpenordersAmount  = $this->getOpenordersAmount();
  //         $getClosedOrderAmount = $this->getClosedOrderAmount();
		
		//  $highestsellingsalesman = $this->gethighestsellingsalesman();
		// $highestsellingproducts = $this->gethighestsellingproducts();


        $model="dashboard";
        return view('admin.page.dashboard',compact('title','restro_count','users_count','getUsersRegistrStats','end_date','earningStats','highestsellingproducts','sales_transaction_stats','wallet_balance','wallet_balance_sale_today','getMasterBillsPendingAmount','getOpenordersAmount','highestsellingsalesman','getClosedOrderAmount','model'));

         // return view('admin.page.dashboard',compact());
	}

	public function getTotalEarningStats()
   	{
        //current week date range
        $monday = strtotime("last monday");
        $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
        //previous week date range
        $previous_week = strtotime("-1 week");
        $start_week = strtotime("last sunday",$previous_week);
        $end_week = strtotime("next saturday",$start_week);

        $borrwed_amountbyDateArray=[
            'this_week'=>manageAmountFormat($this->getTotalEarning(date("Y-m-d",$monday),date("Y-m-d",$sunday))),
            'last_week'=>manageAmountFormat($this->getTotalEarning(date("Y-m-d",$start_week),date("Y-m-d",$end_week))),
            'this_month'=>manageAmountFormat($this->getTotalEarning(date('Y-m-01'),date('Y-m-t'))),
            'last_month'=>manageAmountFormat($this->getTotalEarning(date('Y-m-01',strtotime('last month')),date('Y-m-t',strtotime('last month')))),
            'till_date'=>manageAmountFormat($this->getTotalEarning(null,date('Y-m-d'))),
            'last_year'=>manageAmountFormat($this->getTotalEarning(null,date('Y-m-d',strtotime("-1 years"))))
        ];
      //  echo "<pre>"; print_r($borrwed_amountbyDateArray); die;
        return $borrwed_amountbyDateArray; 
   	}

   public function getTotalEarning($start_date=null,$end_date=null)
   {
        if(!$start_date)
        {
            // $total_amount = WaCashSales::whereDate('order_date','<=' ,$end_date)
            // ->join('wa_cash_sales_items','wa_cash_sales_items.wa_cash_sales_id','=','wa_cash_sales.id')
            // ->sum(DB::raw('wa_cash_sales_items.unit_price * wa_cash_sales_items.quantity'));

            // $total_sales_invoice = WaSalesInvoice::where('order_date','<=',$end_date)
						// 		   ->join('wa_sales_invoice_items','wa_sales_invoice_items.wa_sales_invoice_id','=','wa_sales_invoices.id')
						// 		   ->sum(DB::raw('wa_sales_invoice_items.unit_price * wa_sales_invoice_items.quantity'));
								   
            $poscashtotal = \App\Model\WaPosCashSalesItems::whereDate('created_at','<=',$end_date)
                        ->sum(DB::raw('wa_pos_cash_sales_items.selling_price * wa_pos_cash_sales_items.qty'));
          
            $salesinvoicereal = \App\Model\WaInventoryLocationTransferItem::whereDate('created_at','<=',$end_date)
                        ->sum(DB::raw('total_cost_with_vat'));
            $salesreturn = \App\Model\WaInventoryLocationTransferItem::whereDate('return_date','<=',$end_date)
                        ->sum(DB::raw('selling_price * return_quantity'));
            $salestotal = $salesinvoicereal - $salesreturn;

        }
        else
        {
            $start_date = $start_date;
            $end_date = $end_date;
            // $total_amount = WaCashSales::whereBetween('order_date', [$start_date, $end_date])
            // ->join('wa_cash_sales_items','wa_cash_sales_items.wa_cash_sales_id','=','wa_cash_sales.id')
            // ->sum(DB::raw('wa_cash_sales_items.unit_price * wa_cash_sales_items.quantity'));

            // $total_sales_invoice = WaSalesInvoice::whereBetween('order_date', [$start_date, $end_date])
						// 		   ->join('wa_sales_invoice_items','wa_sales_invoice_items.wa_sales_invoice_id','=','wa_sales_invoices.id')
						// 		   ->sum(DB::raw('wa_sales_invoice_items.unit_price * wa_sales_invoice_items.quantity'));
            $poscashtotal = \App\Model\WaPosCashSalesItems::whereBetween('created_at', [$start_date.' 00:00:00', $end_date.' 23:59:59'])
                   ->sum(DB::raw('wa_pos_cash_sales_items.selling_price * wa_pos_cash_sales_items.qty'));
         
            $salesinvoicereal = \App\Model\WaInventoryLocationTransferItem::whereBetween('created_at', [$start_date.' 00:00:00', $end_date.' 23:59:59'])
            ->sum(DB::raw('total_cost_with_vat'));
              $salesreturn = \App\Model\WaInventoryLocationTransferItem::whereBetween('return_date', [$start_date.' 00:00:00', $end_date.' 23:59:59'])
                          ->sum(DB::raw('selling_price * return_quantity'));
            $salestotal = $salesinvoicereal - $salesreturn;
        }
        return ($poscashtotal+$salestotal);

   }

   public function getSalesTransactionDetails($month,$year)
   {
		    $final_data=[];
            $rows = DB::select( DB::raw("
                SELECT DAY(created_at) as created_day,id,DATE(created_at) as createdate FROM `wa_cash_sales` WHERE  created_at like '".$year."-".$month."%' GROUP BY DAY(created_at), MONTH(created_at),Year(created_at)") );

            foreach($rows as $key=> $row)
            {   
                $inner_array = [$row->created_day,round($this->getTotalsalesAmount($row->createdate),2)]; 
                $final_data[]=$inner_array;
            }
			//die("ddd");
        return $final_data;
   }
   public function getTotalsalesAmount($date){
      $poscashtotal = \App\Model\WaPosCashSalesItems::whereDate('created_at',$date)
                        ->sum(DB::raw('wa_pos_cash_sales_items.selling_price * wa_pos_cash_sales_items.qty'));
      $salesinvoicereal = \App\Model\WaInventoryLocationTransferItem::whereDate('created_at',$date)
                  ->sum(DB::raw('total_cost_with_vat'));
      $salesreturn = \App\Model\WaInventoryLocationTransferItem::whereDate('return_date',$date)
                  ->sum(DB::raw('selling_price * return_quantity'));
      $salestotal = $salesinvoicereal - $salesreturn;
	   return ($salestotal+$poscashtotal);
   }

	public function getUsersRegistrStats($start_date,$end_date)
	{
		

      
        
        $final_data=[];

			$date_from = $start_date;   
			$date_from = strtotime($date_from); // Convert date to a UNIX timestamp  

			// Specify the end date. This date can be any English textual format  
			$date_to = $end_date;  
			$date_to = strtotime($date_to); // Convert date to a UNIX timestamp  

			$register_user_by_date = DB::select( DB::raw("
         	SELECT 
         	date(created_at) as register_date,count('*') as total_user  FROM `users` where date(created_at) >= '".$end_date."' and role_id=11 group by date(created_at)") ); 

			$user_data_arr = [];
         	foreach($register_user_by_date as $user_data)
         	{
         		$user_data_arr[date('d',strtotime($user_data->register_date))] = $user_data->total_user;
         	}
         	

			// Loop from the start date to end date and output all dates inbetween  
			for ($i=$date_to; $i<=$date_from; $i+=86400) 
			{  
				$my_string =  date("d", $i); 
				$my_value = 0;
				
				if(isset($user_data_arr[$my_string]))
				{
					$my_value = $user_data_arr[$my_string];
				}
				//echo $my_string.'<br>';
				$inner_array = [$my_string,$my_value
				]; 
				$final_data[]=$inner_array;

			} 
			return $final_data;

        
      

         
        

         
	}
        
        
  public function salesperson_report(Request $request)
    {
        // $pmodule = $this->pmodule;
        // $permission =  $this->mypermissionsforAModule();
        // if(!$request->date && (!isset($permission[$pmodule.'___detailed']) && $permission == 'superadmin')){
        //     Session::flash('warning', 'Invalid Request');
        //     return back();
        // }
        $user = getLoggeduserProfile();
        // AND wa_pos_cash_sales_items.is_return = 0
        $data = User::select([
            'users.name',
            DB::RAW('(SELECT SUM(wa_pos_cash_sales_items.selling_price * wa_pos_cash_sales_items.qty)
             from wa_pos_cash_sales 
             LEFT JOIN wa_pos_cash_sales_items ON wa_pos_cash_sales_items.wa_pos_cash_sales_id = wa_pos_cash_sales.id
             WHERE wa_pos_cash_sales.user_id = users.id  AND (DATE(wa_pos_cash_sales_items.created_at) BETWEEN "'.$request->date.'" AND "'.$request->todate.'")) as cash_sales'),
            
             DB::RAW('(SELECT SUM(wa_pos_cash_sales_items.selling_price * wa_pos_cash_sales_items.return_quantity) 
             from wa_pos_cash_sales 
             LEFT JOIN wa_pos_cash_sales_items ON wa_pos_cash_sales_items.wa_pos_cash_sales_id = wa_pos_cash_sales.id
             WHERE wa_pos_cash_sales.user_id = users.id AND is_return = 1 AND (DATE(wa_pos_cash_sales_items.created_at)  BETWEEN "'.$request->date.'" AND "'.$request->todate.'")) as pos_cash_sales_returns'),     
                   
            
             DB::RAW('(SELECT SUM(wa_pos_cash_sales_items.selling_price * wa_pos_cash_sales_items.return_quantity) 
             from wa_pos_cash_sales 
             LEFT JOIN wa_pos_cash_sales_items ON wa_pos_cash_sales_items.wa_pos_cash_sales_id = wa_pos_cash_sales.id
             WHERE wa_pos_cash_sales.user_id = users.id AND is_return = 1 AND (DATE(wa_pos_cash_sales_items.return_date)  BETWEEN "'.$request->date.'" AND "'.$request->todate.'")) as cash_sales_returns'),     
                      
             DB::RAW('(SELECT SUM(wa_inventory_location_transfer_items.total_cost_with_vat)
             from wa_inventory_location_transfers 
             LEFT JOIN wa_inventory_location_transfer_items ON wa_inventory_location_transfer_items.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
             WHERE wa_inventory_location_transfers.user_id = users.id AND (DATE(wa_inventory_location_transfers.transfer_date)  BETWEEN "'.$request->date.'" AND "'.$request->todate.'")) as invoices'),
             
             
             DB::RAW('(SELECT SUM(wa_inventory_location_transfer_items.return_quantity * wa_inventory_location_transfer_items.selling_price)
             from wa_inventory_location_transfers
             LEFT JOIN wa_inventory_location_transfer_items ON wa_inventory_location_transfer_items.wa_inventory_location_transfer_id = wa_inventory_location_transfers.id
             WHERE wa_inventory_location_transfers.user_id = users.id AND wa_inventory_location_transfer_items.is_return = 1 AND (DATE(wa_inventory_location_transfer_items.return_date)  BETWEEN "'.$request->date.'" AND "'.$request->todate.'")) as invoices_return'),
             
            
        ])->orderBy('cash_sales','DESC')->get();
     
        $finalData = [];
        foreach ($data as $key => $item) {
          $total = (($item->cash_sales ?? 0.00) + ($item->cash_sales_returns ?? 0.00)) + ($item->invoices ?? 0.00) - ($item->cash_sales_returns ?? 0.00) - ($item->invoices_return ?? 0.00);
          
          $finalData[(string)$total] = [
            'name'=>$item->name,
            'total'=>$total,
          ];
        }
      
        krsort($finalData);

        if($request->request_type){
            return view('admin.page.salesperson_report',compact('user','data','finalData'));
        }
        $pdf = \PDF::loadView('admin.page.salesperson_report',compact('user','data','finalData'));
        // return $pdf;
        // return $pdf->stream();
        return $pdf->download('SalesPerson-Report-'.$request->date.'-'.$request->todate.'.pdf');
    }

    public function selling_report(Request $request)
    {
        // $pmodule = $this->pmodule;
        // $permission =  $this->mypermissionsforAModule();
        // if(!$request->date && (!isset($permission[$pmodule.'___detailed']) && $permission == 'superadmin')){
        //     Session::flash('warning', 'Invalid Request');
        //     return back();
        // }
        $user = getLoggeduserProfile();
        // AND wa_pos_cash_sales_items.is_return = 0
        $data = WaStockMove::select([
           'stock_id_code',
          \DB::RAW('Abs(SUM(qauntity)) as total_quantity'),
          \DB::RAW('SUM(price) as sold_value'),
        ])->where(function($e){
          $e->orWhere('wa_pos_cash_sales_id','!=',NULL)->orWhere('wa_internal_requisition_id','!=',NULL);
        })->where('qauntity','<',0)->orderBy('total_quantity','DESC')->groupBy('stock_id_code')->limit(50)->get();
    //  dd( $data );
    $fdata = [];
        foreach ($data as $key => $item) {
          $d = (Object)[];
          $d->stock_id_code = $item->stock_id_code;
          $d->total_quantity = $item->total_quantity;
          $d->sold_value = $item->sold_value;
          $fdata[$item->total_quantity] = $d;
        }
        krsort($fdata);
        if($request->request_type){
            return view('admin.page.selling_report',compact('user','fdata'));
        }
        $pdf = \PDF::loadView('admin.page.selling_report',compact('user','fdata'));
        // return $pdf;
        // return $pdf->stream();
        return $pdf->download('selling-Report-'.$request->date.'-'.$request->todate.'.pdf');
    }
    
    /**
     * Load sidebar menu dynamically via AJAX
     */
    public function loadSidebarMenu(Request $request)
    {
        $menuType = $request->query('menu', 'revenue');
        $logged_user_info = getLoggeduserProfile();
        $my_permissions = $logged_user_info->permissions;
        $route_name = \Route::currentRouteName();
        $model = null; // Initialize model variable for blade templates
        $rmodel = null; // Initialize rmodel variable for blade templates
        
        // Menu mapping - permission keys must match those used in sidebar include files
        $menuMap = [
            'revenue' => [
                'view' => 'admin.includes.sidebar_includes.sales_and_receivables',
                'title' => 'REVENUE MANAGEMENT',
                'permission' => 'sales-and-receivables___view'
            ],
            'logistics' => [
                'view' => 'admin.includes.sidebar_includes.logistics',
                'title' => 'DELIVERY & LOGISTICS',
                'permission' => 'delivery_and_logistics___view'
            ],
            'purchases' => [
                'view' => 'admin.includes.sidebar_includes.purchases',
                'title' => 'PURCHASE PROCUREMENT',
                'permission' => 'purchases___view'
            ],
            'supplier' => [
                'view' => 'admin.includes.sidebar_includes.supplier_portal',
                'title' => 'SUPPLIER PORTAL',
                'permission' => 'supplier-portal___view'
            ],
            'vendor' => [
                'view' => 'admin.includes.sidebar_includes.accounts_payable',
                'title' => 'VENDOR MANAGEMENT',
                'permission' => 'account-payables___view'
            ],
            'inventory' => [
                'view' => 'admin.includes.sidebar_includes.inventory',
                'title' => 'INVENTORY',
                'permission' => 'inventory___view'
            ],
            'ledger' => [
                'view' => 'admin.includes.sidebar_includes.general_ledger',
                'title' => 'GENERAL LEDGER',
                'permission' => 'genralledger___view'
            ],
            'hr' => [
                'view' => 'admin.includes.sidebar_includes.hr',
                'title' => 'HR AND PAYROLL',
                'permission' => 'hr-and-payroll___view'
            ],
            'fleet' => [
                'view' => 'admin.includes.sidebar_includes.fleet',
                'title' => 'FLEET MANAGEMENT',
                'permission' => 'fleet-management-module___view'
            ],
            'helpdesk' => [
                'view' => 'admin.includes.sidebar_includes.help_desk',
                'title' => 'HELP DESK',
                'permission' => 'help-desk___view'
            ],
            'comms' => [
                'view' => 'admin.includes.sidebar_includes.communication_centre',
                'title' => 'COMMUNICATIONS CENTRE',
                'permission' => 'communication-center___view'
            ],
            'admin' => [
                'view' => 'admin.includes.sidebar_includes.system_administration',
                'title' => 'PLATFORM ADMIN',
                'permission' => 'financial-management___view'
            ]
        ];
        
        // Validate menu type
        if (!isset($menuMap[$menuType])) {
            return response()->json(['error' => 'Invalid menu type'], 400);
        }
        
        $menu = $menuMap[$menuType];
        
        // Check permissions
        if ($logged_user_info->role_id != 1 && !isset($my_permissions[$menu['permission']])) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        // Store active menu in session
        session(['activeMenu' => $menuType]);
        
        // Render the menu view
        try {
            $html = view($menu['view'], compact('logged_user_info', 'my_permissions', 'route_name', 'model', 'rmodel'))->render();
            
            return response()->json([
                'html' => $html,
                'title' => $menu['title']
            ]);
        } catch (\Exception $e) {
            \Log::error('Sidebar menu loading error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error loading menu: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
