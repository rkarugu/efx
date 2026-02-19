<?php

namespace App\Services;

use App\Enums\PromotionMatrix;
use App\ItemPromotion;
use App\Jobs\PerformPostSaleActions;
use App\Model\PaymentMethod;
use App\Model\Route;
use App\Model\TaxManager;
use App\Model\WaChartsOfAccount;
use App\Model\WaCustomer;
use App\Model\WaEsdDetails;
use App\Model\WaGlTran;
use App\Model\WaInternalRequisition;
use App\Model\WaInternalRequisitionItem;
use App\Model\WaInventoryItem;
use App\Model\WaNumerSeriesCode;
use App\Model\WaPosCashSales;
use App\Model\WaPosCashSalesDispatch;
use App\Model\WaPosCashSalesItems;
use App\Model\WaPosCashSalesPayments;
use App\Model\WaRouteCustomer;
use App\Model\WaStockMove;
use App\Models\HamperItem;
use App\Models\PromotionType;
use App\Models\WaAccountTransaction;
use App\User;
use App\WaDemandItem;
use App\WaTenderEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use App\Models\Setting;

class PosCashSaleService
{

    public static function recordSale(array $items,  $cashier_id, Collection $products,string $sales_no, int $route_customer, array $payment_methods, bool $paid, $attending_cashier=null, int $sale_id = null, $is_tablet_sale = false)
    {
        /*prerequisites*/;
        $user = User::find($cashier_id);
        $companyPreference =  \App\Model\WaCompanyPreference::where('id', '1')->first();
        $customer = WaRouteCustomer::where('id',$route_customer)->first();
        $getLoggeduserProfile = getLoggeduserProfile();
        $dateTime = date('Y-m-d H:i:s');

        $cashBal = WaAccountTransaction::where('account_id',$companyPreference -> cash_sales_control_account)->latest()->first();
        $saleBal = WaAccountTransaction::where('account_id',$companyPreference -> sales_control_account)->latest()->first();
        $vatBal = WaAccountTransaction::where('account_id',$companyPreference -> vat_control_account)->latest()->first();

        /*create sale*/
        if ($sale_id)
        {
            $parent = WaPosCashSales::find($sale_id);
        }else{

            $parent = new WaPosCashSales;
            $parent->sales_no = $sales_no;
        }


//        if ($sale_id)
//        {
//            $parent = WaPosCashSales::find($sale_id);
//        }else{
//
//            $new_sales_no = self::generateNewSalesNumber();
//
//            $parent = new WaPosCashSales;
//            $parent->sales_no = $new_sales_no;
//        }

        $parent->date =date('Y-m-d');
        $parent->time = date('H:i:s');
        $parent->user_id = $user->id;
        $parent->branch_id = $getLoggeduserProfile->restaurant_id;
        $parent->customer = $customer->name;
        $parent->wa_route_customer_id = $customer->id;
        $parent->customer_phone_number = $customer->phone;
        $parent->customer_pin = $customer->kra_pin;
        if (isset($attending_cashier)) {
            $parent->attending_cashier = $attending_cashier;
        } elseif ($paid === true) {
            $parent->attending_cashier = $user->id;
        }

//        if ($user->id != $attending_cashier)
//        {
//            $is_tablet_sale =  false;
//        }

        if ($is_tablet_sale)
        {
            $parent->is_tablet_sale = $is_tablet_sale;
        }

        $parent->save();

        /*add Items*/
        $glTrans = [];
        $total = 0;
        $total_vat_amount = 0;
        $childs = [];
        WaPosCashSalesItems::where('wa_pos_cash_sales_id',$parent->id)->delete();
        foreach ($items as $item) {

            $selling_price = $products->firstWhere('id', $item['item_id'])->selling_price;
            $promotion = ItemPromotion::where('inventory_item_id', $item['item_id'])->where('status', 'active')->first();


            if ($promotion) {
                /*get promotion type*/
                $promotionType = $promotion->promotion_type_id ? PromotionType::find($promotion->promotion_type_id)->description : null;

                if ($promotionType)
                {
                    /*hamper*/
                    if ($promotionType == PromotionMatrix::HAMPER->value)
                    {
                        /*raise demand for each item*/
                        $invItem =  $products->firstWhere('id', $item['item_id']);
                        /*get hamper Items*/
                        $hamper_items = HamperItem::where('hamper_id', $invItem->id)->get();
                        foreach ($hamper_items as $hamper_item)
                        {
                            $inventory_item = WaInventoryItem::find($hamper_item->wa_inventory_item_id);
                            $data = [
                                'wa_inventory_item_id'=> $invItem->id,
                                'current_cost'=> $inventory_item->standard_cost,
                                'current_price'=>$inventory_item -> selling_price,
                                'new_cost'=> $hamper_item-> standard_cost,
                                'new_price'=>$hamper_item ->selling_price,
                                'wa_demand_id'=>$hamper_item->demand_id,
                                'demand_quantity'=>$item['item_quantity'],
                            ];
                            self::raiseDemand($data);
                        }

                    }

                    /*Price Discount*/
                    if ($promotionType == PromotionMatrix::PD->value)
                    {
                        /*chenge selling price*/
                        $invItem =  $products->firstWhere('id', $item['item_id']);
                        $data = [
                            'wa_inventory_item_id'=> $invItem->id,
                            'current_cost'=> $invItem->standard_cost,
                            'current_price'=>$invItem -> selling_price,
                            'new_cost'=> $invItem-> standard_cost,
                            'new_price'=> $promotion ->promotion_price,
                            'wa_demand_id'=>$promotion->wa_demand_id,
                            'demand_quantity'=>$item['item_quantity'],
                        ];
                        self::raiseDemand($data);

                    }

                }else
                {
                    $orderQty = $item['item_quantity'];
                    $promotionBatches = floor($orderQty / (float)$promotion->sale_quantity);

                    if ($promotionBatches > 0) {
                        $promotionQty = $promotionBatches * $promotion->promotion_quantity;
                        $promotionItem = WaInventoryItem::find($promotion->promotion_item_id);
                        $promotionItemQoh = WaStockMove::where('wa_inventory_item_id', $promotionItem->id)->where('wa_location_and_store_id', $user->wa_location_and_store_id)->sum('qauntity');
                        if ($promotionQty  < $promotionItemQoh) {
                            $childs[] = [
                                'wa_pos_cash_sales_id'=> $parent->id,
                                'wa_inventory_item_id'=> $promotionItem->id,
                                'store_location_id'=> $user->wa_location_and_store_id,
                                'qty' => $promotionQty,
                                'selling_price' => 0,
                                'discount_percent' => 0,
                                'total' => 0,
                                'discount_amount' => 0,
                                'vat_percentage' => 0,
                                'vat_amount' => 0,
                                'tax_manager_id' => $promotionItem->tax_manager_id,
                                'created_at' => $dateTime,
                                'updated_at' => $dateTime,
                                'standard_cost' => $promotionItem->standard_cost,
                            ];
                        }
                    }
                }
              
            }

            $data = [];
            $data['wa_pos_cash_sales_id'] = $parent->id;
            $data['wa_inventory_item_id'] = $item['item_id'];
            $data['store_location_id'] = $getLoggeduserProfile->wa_location_and_store_id;
            $data['qty'] = $item['item_quantity'];
            $data['selling_price'] = $selling_price;
            $data['total'] = ceil($selling_price * $item['item_quantity']);


            $data['discount_percent'] = ($item['item_discount_amount']/ $data['total']) * 100;
            $data['discount_amount'] = $item['item_discount_amount'];
            $data['vat_percentage'] =TaxManager::find($products->firstWhere('id', $item['item_id'])->tax_manager_id)->tax_value;
            $data['vat_amount'] = ($data['total'] - $data['discount_amount']) - ((($data['total'] - $data['discount_amount'])*100) / ($data['vat_percentage']+100));

            $data['tax_manager_id'] = $products->firstWhere('id', $item['item_id'])->tax_manager_id;
            $data['created_at'] = $dateTime;
            $data['updated_at'] = $dateTime;
            $data['standard_cost'] = $products->firstWhere('id', $item['item_id'])->standard_cost;


            if($data['vat_percentage']){
                $products->firstWhere('id', $item['item_id'])->standard_cost = (($products->firstWhere('id', $item['item_id'])->standard_cost*100)/($data['vat_percentage']+100));
            }

            $total_invoice_amount = ($data['total'] - $data['discount_amount']);
            if ($data['vat_amount'] > 0) {
                $total_vat_amount += $data['vat_amount'];
            }
            $childs[] = $data;

        }
        /*associate items to cashsale*/
        WaPosCashSalesItems::insert($childs);

        $parent->change =  ($parent->cash > 0) ? $parent->cash - $total : 0.00;
        $parent->status = $paid ? 'Completed': 'PENDING';
        $parent->save();


        if ($paid){
            self::postPay($parent, $payment_methods);
        }

        /**/
        $parent->refresh();
        return $parent;
    }

    private static function generateNewSalesNumber(): string
    {
        $maxAttempts = 10;
        $attempt = 1;

        while ($attempt <= $maxAttempts) {
            DB::beginTransaction();
            try {
                $series_module = WaNumerSeriesCode::where('module', 'CASH_SALES')
                    ->lockForUpdate()
                    ->first();

                if (!$series_module) {
                    throw new \RuntimeException('CASH_SALES number series not found');
                }

                $lastNumberUsed = $series_module->last_number_used;
                $newNumber = (int)$lastNumberUsed + 1;
                $sales_no = $series_module->code . '-' . str_pad($newNumber, 5, "0", STR_PAD_LEFT);

                // Check for uniqueness in BOTH tables to prevent conflicts
                $existsInCashSales = WaPosCashSales::where('sales_no', $sales_no)->exists();
                $existsInRequisition = WaInternalRequisition::where('requisition_no', $sales_no)->exists();

                if (!$existsInCashSales && !$existsInRequisition) {
                    $series_module->update(['last_number_used' => $newNumber]);
                    DB::commit();
                    
                    // Log successful generation for debugging
                    \Log::info('Generated unique sales number', [
                        'sales_no' => $sales_no,
                        'attempt' => $attempt
                    ]);
                    
                    return $sales_no;
                }
                
                DB::rollBack();
                
                // Log collision for debugging
                \Log::warning('Sales number collision detected', [
                    'sales_no' => $sales_no,
                    'attempt' => $attempt,
                    'exists_in_cash_sales' => $existsInCashSales,
                    'exists_in_requisition' => $existsInRequisition
                ]);
                
                $attempt++;
                
                // Add small delay to reduce race conditions
                usleep(rand(10000, 50000)); // 10-50ms random delay

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error generating sales number', [
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        }

        throw new \RuntimeException("Unable to generate unique sales number after {$maxAttempts} attempts");
    }
    public static function esdSign(WaPosCashSales $cashSales): void
    {
        $invoice = $cashSales;
        $invoiceSigned = true;
        $message = null;
        try {
            $settings = getAllSettings();
            $clientPin = $settings['PIN_NO'];
            $esdUrl = $settings['ESD_URL'];
            $apiUrl = "$esdUrl/api/sign?invoice+1";
            $vatAmount = 0;
            $payload = [
                "invoice_date" => Carbon::parse($invoice->created_at)->format('d_m_Y'),
                "invoice_number" => $invoice->sale_no,
                "invoice_pin" => $clientPin,
                "customer_pin" => "",
                "customer_exid" => "",
                "grand_total" => number_format($invoice->getOrderTotalForEsd(), 2),
                "net_subtotal" => number_format($invoice->getOrderTotalForEsd() - $vatAmount, 2),
                "tax_total" => number_format($vatAmount, 2),
                "net_discount_total" => "0",
                "sel_currency" => "KSH",
                "rel_doc_number" => "",
                "items_list" => []
            ];

            $grandTotal = 0;
            $taxManagers = DB::table('tax_managers')->select('id', 'title', 'tax_value')->get();
            foreach ($invoice->items as $item) {
                $itemTotal = $item->selling_price * $item->quantity;
                $grandTotal += $itemTotal;

                $inventoryItem = DB::table('wa_inventory_items')->find($item->wa_inventory_item_id);
                $taxManager = $taxManagers->where('id', $inventoryItem->tax_manager_id)->first();
                if ($taxManager) {
                    $vatRate = (float)$taxManager->tax_value;
                    $vatAmount += ($vatRate / (100 + $vatRate)) * $itemTotal;
                }

                $itemTotal = manageAmountFormat($itemTotal);
                $item->selling_price = manageAmountFormat($item->selling_price);
                $line = "$inventoryItem->title $item->quantity $item->selling_price $itemTotal";
                if ($inventoryItem->hs_code) {
                    $line = "$inventoryItem->hs_code " . $line;
                }

                $payload['items_list'][] = $line;
            }

            $payload['tax_total'] = number_format($vatAmount, 2);
            $payload['grand_total'] = number_format($grandTotal, 2);
            $payload['net_subtotal'] = number_format($grandTotal - $vatAmount, 2);


            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ZxZoaZMUQbUJDljA7kTExQ==',
            ])->post($apiUrl, $payload);

            $responseData = json_decode($response->body(), true);


            $responseData = [
                'invoice_number'=> $cashSales -> sales_no,
                'cu_serial_number'=> 'KRAMW004202207080760 18.03.2024 16:51:48',
                'cu_invoice_number'=> '0040807600000000016',
                'description'=> 'signed successfully',
                'verify_url'=> 'https://itax.kra.go.ke/KRA-Portal/invoiceChk.htm?actionCode=loadPage&invoiceNo=0040807600000000017',
            ];

            $newEsd = new WaEsdDetails();

            if ($response->ok()) {
                $newEsd->invoice_number = $responseData['invoice_number'];
                $newEsd->cu_serial_number = $responseData['cu_serial_number'];
                $newEsd->cu_invoice_number = $responseData['cu_invoice_number'];
                $newEsd->verify_url = $responseData['verify_url'] ?? null;
                $newEsd->description = $responseData['description'] ?? null;
                $newEsd->status = 1;
                $newEsd->save();
            } else {
                $newEsd->invoice_number = $invoice->sale_no;
                $newEsd->description = $response->body();
                $newEsd->status = 0;
                $newEsd->save();

                $invoiceSigned = false;
                $message = $response->body();
            }
        } catch (\Throwable $e) {
            $newEsd = new WaEsdDetails();
            $newEsd->invoice_number = $invoice->sale_no;
            $newEsd->description = $e->getMessage();
            $newEsd->status = 0;
            $newEsd->save();

            $invoiceSigned = false;
            $message = $e->getMessage();
        }

        try {
//            if (!$invoiceSigned) {
//                (new InfoSkySmsService())->sendMessage(
//                    "$newEsd->invoice_number failed to sign citing: $message",
//                    "0790544563" // Isaac's number
//                );
//            }
        } catch (\Throwable $e) {
            //
        }
    }

    public static  function raiseDemand($data)
    {

        $demandItem = WaDemandItem::where('wa_demand_id', $data['wa_demand_id'])
            ->where('wa_inventory_item_id', $data['wa_inventory_item_id'])
            ->first();


        if ($demandItem) {
            // If record exists, increment the quantity
            $demandItem->demand_quantity += $data['demand_quantity'];
            $demandItem->save();
        } else {


          $demandItem =  WaDemandItem::create($data);
        }

    }

    public static function stockMove(WaPosCashSales $cashSales)
    {
        $companyPreference =  \App\Model\WaCompanyPreference::where('id', '1')->first();
        $user = User::find($cashSales->user_id);

        $accountuingPeriod =  \App\Model\WaAccountingPeriod::where('is_current_period', '1')->first();
        $series_module = \App\Model\WaNumerSeriesCode::where('module', 'CASH_SALES')->first();
        $paymentMethod = PaymentMethod::with(['paymentGlAccount'])->first();
        $customer = WaRouteCustomer::where('id',$cashSales->wa_route_customer_id)->first();
        $getLoggeduserProfile = $user;
        $dateTime = date('Y-m-d H:i:s');

        $cashBal = WaAccountTransaction::where('account_id',$companyPreference -> cash_sales_control_account)->latest()->first();
        $saleBal = WaAccountTransaction::where('account_id',$companyPreference -> sales_control_account)->latest()->first();
        $vatBal = WaAccountTransaction::where('account_id',$companyPreference -> vat_control_account)->latest()->first();

        $cashBal = $cashBal ? $cashBal->balance : 0;
        $saleBal = $saleBal ? $saleBal->balance : 0;
        $vatBal = $vatBal ? $vatBal->balance : 0;

        $items  = WaPosCashSalesItems::where('wa_pos_cash_sales_id', $cashSales->id)->get();


        foreach ($items as $item) {
            $total =  $item->total;
            $product = WaInventoryItem::find($item->wa_inventory_item_id);

            $stock_qoh = WaStockMove::where('wa_inventory_item_id', $item->wa_inventory_item_id)
                ->where('wa_location_and_store_id',$getLoggeduserProfile->wa_location_and_store_id)
                ->sum('qauntity') ?? 0;

            $stock_qoh -= $item->qty;

            $stockMove = new WaStockMove();
            $stockMove->user_id = $getLoggeduserProfile->id;
            $stockMove->wa_pos_cash_sales_id = $cashSales->id;
            $stockMove->restaurant_id = $getLoggeduserProfile->restaurant_id;
            $stockMove->wa_location_and_store_id = $getLoggeduserProfile->wa_location_and_store_id;
            $stockMove->stock_id_code = $product->stock_id_code;
            $stockMove->wa_inventory_item_id = $item->wa_inventory_item_id;
            $stockMove->document_no =   $cashSales->sales_no;
            $stockMove->price = $item->total;
            $stockMove->grn_type_number = $series_module->type_number;
            $stockMove->grn_last_nuber_used = $series_module->last_number_used;
            $stockMove->refrence = $user->name;
            $stockMove->qauntity = - $item->qty;
            $stockMove->new_qoh = $stock_qoh;
            $stockMove->standard_cost = $product->standard_cost;
            $stockMove->save();

//            $description = $product->title;
//            $accno = $product ->getInventoryCategoryDetail->getWIPGlDetail->account_code;
//
//            //cr entries start
//            $glTrans[] = [
//                'period_number'=>$accountuingPeriod ? $accountuingPeriod->period_no : null,
//                'wa_pos_cash_sales_id'=>$parent->id,
//                'grn_type_number'=>$series_module->type_number,
//                'trans_date' => $dateTime,
//                'restaurant_id' => $getLoggeduserProfile->restaurant_id,
//                'grn_last_used_number' => $series_module->last_number_used,
//                'transaction_type' => $series_module->description,
//                'transaction_no' => $parent->sales_no,
//                'narrative' => $description,
//                'account' => $accno,
//                'amount' => '-' . (($data['total'] - $data['discount_amount']) - $data['vat_amount']),
//                'created_at'=>$dateTime,
//                'updated_at'=>$dateTime,
//            ];

        }
    }

    public static function updateGl(WaPosCashSales $cashSales)
    {
        $companyPreference =  \App\Model\WaCompanyPreference::where('id', '1')->first();
        $user = User::find($cashSales->user_id);
        $accountuingPeriod =  \App\Model\WaAccountingPeriod::where('is_current_period', '1')->first();
        $series_module = \App\Model\WaNumerSeriesCode::where('module', 'CASH_SALES')->first();
        $dateTime = date('Y-m-d H:i:s');

        $cashBal = WaAccountTransaction::where('account_id',$companyPreference -> cash_sales_control_account)->latest()->first();
        $saleBal = WaAccountTransaction::where('account_id',$companyPreference -> sales_control_account)->latest()->first();
        $vatBal = WaAccountTransaction::where('account_id',$companyPreference -> vat_control_account)->latest()->first();

        $cashBal = $cashBal ? $cashBal->balance : 0;
        $saleBal = $saleBal ? $saleBal->balance : 0;
        $vatBal = $vatBal ? $vatBal->balance : 0;

        $items  = WaPosCashSalesItems::where('wa_pos_cash_sales_id', $cashSales->id)->get();
        $total_vat_amount = $items->sum('vat_amount');
        $total = $items->sum('total');

        if ($total_vat_amount > 0) {
            $taxVat = \App\Model\TaxManager::where('slug', 'vat')->first();
            $glTrans[] = [
                'period_number'=>$accountuingPeriod ? $accountuingPeriod->period_no : null,
                'wa_pos_cash_sales_id'=>$cashSales->id,
                'grn_type_number'=>$series_module->type_number,
                'trans_date' => $dateTime,
                'restaurant_id' => $user->restaurant_id,
                'grn_last_used_number' => $series_module->last_number_used,
                'transaction_type' => $series_module->description,
                'transaction_no' => $cashSales->sales_no,
                'narrative' => "VAT",
                'account' => $taxVat->getInputGlAccount->account_code,
                'amount' => '-' . $total_vat_amount,
                'created_at'=>$dateTime,
                'updated_at'=>$dateTime,
            ];
        }

        $cash_acc = WaChartsOfAccount::find($companyPreference -> cash_sales_control_account);
        $glTrans[] = [
            'period_number'=>$accountuingPeriod ? $accountuingPeriod->period_no : null,
            'wa_pos_cash_sales_id'=>$cashSales->id,
            'grn_type_number'=>$series_module->type_number,
            'trans_date' => $dateTime,
            'restaurant_id' => $user->restaurant_id,
            'grn_last_used_number' => $series_module->last_number_used,
            'transaction_type' => $series_module->description,
            'transaction_no' => $cashSales->sales_no,
            'narrative' => $cashSales->customer.' : '.$companyPreference -> cash_sales_control_account,
            'account' => $cash_acc->account_code,
            'amount' => $total,
            'created_at'=>$dateTime,
            'updated_at'=>$dateTime,
        ];
        if(count($glTrans)>0){
            WaGlTran::insert($glTrans);
        }


        /* debit on Cash Control*/
        $taxVat = \App\Model\TaxManager::where('slug', 'vat')->first();
        $transactions = [
            [
                /*Debit Cash sales*/
                'wa_pos_cash_sale_id'=>$cashSales->id,
                'amount'=> - round($total, 2),
                'balance'=> - round($total, 2) + $cashBal,
                'account_id'=>$companyPreference -> cash_sales_control_account,
                'user_id'=>$user->id,
                'restaurant_id'=>$user->restaurant_id,
                'period_number'=> $accountuingPeriod ? $accountuingPeriod->period_no : null,
                'transaction_time'=> $dateTime,
                'created_at'=> $dateTime,
                'updated_at'=> $dateTime,
            ],
            [
                /*credit sales Acc*/
                'wa_pos_cash_sale_id'=>$cashSales->id,
                'amount'=> round($total - $total_vat_amount, 2),
                'balance'=> round($total - $total_vat_amount, 2) + $saleBal,
                'account_id'=>$companyPreference -> sales_control_account,
                'user_id'=>$user->id,
                'restaurant_id'=>$user->restaurant_id,
                'period_number'=> $accountuingPeriod ? $accountuingPeriod->period_no : null,
                'transaction_time'=> $dateTime,
                'created_at'=> $dateTime,
                'updated_at'=> $dateTime,
            ],
            [
                /*credit VAT Acc*/
                'wa_pos_cash_sale_id'=>$cashSales->id,
                'amount'=>  round($total_vat_amount, 2),
                'balance'=>  round($total_vat_amount, 2) + $vatBal,
                'account_id'=> $taxVat->getInputGlAccount->id,
                'user_id'=>$user->id,
                'restaurant_id'=>$user->restaurant_id,
                'period_number'=> $accountuingPeriod ? $accountuingPeriod->period_no : null,
                'transaction_time'=> $dateTime,
                'created_at'=> $dateTime,
                'updated_at'=> $dateTime,
            ]
        ];
        $trans = WaAccountTransaction::insert($transactions);

    }

    public static function payments(WaPosCashSales $cashSales, array $payment_methods)
    {
        $companyPreference =  \App\Model\WaCompanyPreference::where('id', '1')->first();
        $user = User::find($cashSales->attending_cashier ?? $cashSales->user_id);

        $dateTime =now();
        WaPosCashSalesPayments::where('wa_pos_cash_sales_id',$cashSales->id)->where('transaction_type','SALES')->delete();
        $cash_saleTotal = $cashSales->total;
        $WaPosCashSalesPayments = [];
        $payments_total = 0;
        $cash_tendered = 0;
        foreach ($payment_methods as $meth) {
            $value = (float)$meth['amount'];

            $method = PaymentMethod::with(['paymentGlAccount'])->where('id', $meth['method_id'])->first();

            if ($value > 0) {
                $WaPosCashSalesPayments[] = [
                    'method' => $method,
                    'amount' => $value,
                    'remarks' => $meth['tender_entry_id'] ?? '',
                    'wa_tender_entry_id' => $meth['tender_entry_id'] ?? null,
                ];

                $payments_total += $value;
            }
        }

        $change = $payments_total - $cash_saleTotal;

        foreach ($WaPosCashSalesPayments as &$payment) {
            if ($payment['method']->is_cash) {
                $cash_tendered = $payment['amount'];
                $payment['amount'] -= $change;
            }
            $reference = null;
            if (isset($payment['wa_tender_entry_id'])) {
                $tender_entry = WaTenderEntry::find($payment['wa_tender_entry_id']);
                $reference = $tender_entry->reference;
               $tender_entry->update([
                    'consumed' => true
                ]);
            }

            $WaPosCashSalesPaymentsData[] = [
                'wa_pos_cash_sales_id' => $cashSales->id,
                'payment_method_id' => $payment['method']->id,
                'gl_account_id' => $payment['method']->paymentGlAccount->id,
                'gl_account_name' => $payment['method']->paymentGlAccount->account_code,
                'balancing_account_id' => $companyPreference->cash_sales_control_account,
                'amount' => $payment['amount'],
                'remarks' => $payment['remarks'],
                'wa_tender_entry_id' => $payment['wa_tender_entry_id'],
                'payment_reference'=>$reference,
                'cashier_id' => $user->id,
                'branch_id' => $user->restaurant_id,
                'created_at' => $dateTime,
                'updated_at' => $dateTime,
                'transaction_type' => 'SALES',
            ];


        }

        if (!empty($WaPosCashSalesPaymentsData)) {
            WaPosCashSalesPayments::insert($WaPosCashSalesPaymentsData);
        }

        $cashSales->update([
            'cash'=>$cash_tendered,
            'change'=>$change,
        ]);
    }

    public static function dispatch(WaPosCashSales $cashSales)
    {
        $user = User::find($cashSales->user_id);
        $disp=[];
        foreach ($cashSales ->items as $item){
            $disp[]= [
                'pos_sales_id'=> $cashSales->id,
                'pos_sales_item_id'=> $item->id,
                'wa_unit_of_measure_id'=> $item->item->getBinData($user->wa_location_and_store_id)->id,
                'created_at'=> now(),
                'updated_at'=> now(),
            ];
        };
        WaPosCashSalesDispatch::where('pos_sales_id',$cashSales->id)->delete();
        WaPosCashSalesDispatch::insert($disp);
    }

    public static function postPay(WaPosCashSales $cashSales, array $payment_methods)
    {
        try {
            $user = User::find($cashSales->user_id);

            /*record payments*/
            self::payments($cashSales, $payment_methods);

            /*save to dispatch table*/
            self::dispatch($cashSales);

            $cashSales->update([
                'status'=>'Completed',
                'paid_at'=>now(),
                'attending_cashier' => $cashSales->attending_cashier ?? $user->id,
            ]);

            /*create internal req*/
            $cashSales->refresh();
            $customer = WaRouteCustomer::with('route','parent')->find($cashSales->wa_route_customer_id);

            $internalRequisition = WaInternalRequisition::create([
                'requisition_no' => $cashSales -> sales_no,
                'slug' => strtolower( $cashSales -> sales_no),
                'user_id' => $user->id,
                'restaurant_id' => $user->restaurant_id,
                'to_store_id' => $user->wa_location_and_store_id,
                'wa_location_and_store_id' => $user->wa_location_and_store_id,
                'requisition_date' => Carbon::now(),
                'name' => $cashSales->customer,
                'route_id' => $customer->route_id,
                'route' => $customer->route->route_name,
                'customer_id' => $customer->customer_id,
                'wa_route_customer_id' => $cashSales->wa_route_customer_id,
                'customer' => $cashSales->customer,
                'customer_phone_number' => $cashSales->customer_phone_number,
                'customer_pin' => $customer->kra_pin?? null,
                'status' => 'APPROVED',
            ]);

            $items  = WaPosCashSalesItems::where('wa_pos_cash_sales_id', $cashSales->id)->get();
            $dateTime = now();
            $vitu = [];
            foreach ($items as $item){
                $inventoryItem = WaInventoryItem::find($item->wa_inventory_item_id);
                $vitu[]= [
                    'wa_internal_requisition_id' => $internalRequisition->id,
                    'wa_inventory_item_id' => $item->wa_inventory_item_id,
                    'quantity' => $item->qty,
                    'standard_cost' => $item->standard_cost,
                    'selling_price' => $item->selling_price,
                    'total_cost' => $item->total,
                    'tax_manager_id' => $item->tax_manager_id,
                    'vat_rate' => $item->vat_percentage,
                    'vat_amount' => $item->vat_amount,
                    'total_cost_with_vat' => $item->total,
                    'store_location_id' => $internalRequisition->to_store_id,
                    'hs_code' => $inventoryItem->hs_code,
                    'discount' => $item->discount_amount,
                    'discount_description' => '',
                    'created_at'=>$dateTime,
                    'updated_at'=>$dateTime,
                ];
            }
            WaInternalRequisitionItem::insert($vitu);

            /*dispatch post sale event*/
            PerformPostSaleActions::dispatch($internalRequisition)->afterCommit();

            try {
                /*sign invoice - but don't throw error if it fails*/
                self::signInvoice($internalRequisition);
            } catch (\Exception $e) {
                \Log::info('Invoice signing skipped - ESD disabled', [
                    'sales_no' => $cashSales->sales_no
                ]);
            }

            // Stock moves are now handled by PerformPostSaleActions job
            // self::stockMove($cashSales); // REMOVED - causes duplication

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function postPay1(WaPosCashSales $cashSales, array $payment_methods)
    {
        try {
            $user = User::find($cashSales->user_id);

            // Handle payments and dispatch
            self::payments($cashSales, $payment_methods);
            self::dispatch($cashSales);

            $cashSales->update([
                'status' => 'Completed',
                'paid_at' => now(),
                'attending_cashier' => $cashSales->attending_cashier ?? $user->id,
            ]);

            // Refresh and get customer data
            $cashSales->refresh();
            $customer = WaRouteCustomer::with('route', 'parent')->find($cashSales->wa_route_customer_id);

            // Try to create internal requisition with original sales number
            try {
                $internalRequisition = WaInternalRequisition::create([
                    'requisition_no' => $cashSales->sales_no,
                    'slug' => strtolower($cashSales->sales_no),
                    'user_id' => $user->id,
                    'restaurant_id' => $user->restaurant_id,
                    'to_store_id' => $user->wa_location_and_store_id,
                    'wa_location_and_store_id' => $user->wa_location_and_store_id,
                    'requisition_date' => Carbon::now(),
                    'name' => $cashSales->customer,
                    'route_id' => $customer->route_id,
                    'route' => $customer->route->route_name,
                    'customer_id' => $customer->customer_id,
                    'wa_route_customer_id' => $cashSales->wa_route_customer_id,
                    'customer' => $cashSales->customer,
                    'customer_phone_number' => $cashSales->customer_phone_number,
                    'customer_pin' => $customer->kra_pin ?? null,
                    'status' => 'APPROVED',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    $new_sales_no = self::generateNewSalesNumber();
                    $cashSales->update(['sales_no' => $new_sales_no]);

                    $internalRequisition = WaInternalRequisition::create([
                        'requisition_no' => $new_sales_no,
                        'slug' => strtolower($new_sales_no),
                        'user_id' => $user->id,
                        'restaurant_id' => $user->restaurant_id,
                        'to_store_id' => $user->wa_location_and_store_id,
                        'wa_location_and_store_id' => $user->wa_location_and_store_id,
                        'requisition_date' => Carbon::now(),
                        'name' => $cashSales->customer,
                        'route_id' => $customer->route_id,
                        'route' => $customer->route->route_name,
                        'customer_id' => $customer->customer_id,
                        'wa_route_customer_id' => $cashSales->wa_route_customer_id,
                        'customer' => $cashSales->customer,
                        'customer_phone_number' => $cashSales->customer_phone_number,
                        'customer_pin' => $customer->kra_pin ?? null,
                        'status' => 'APPROVED',
                    ]);
                } else {
                    throw $e;
                }
            }

            // Process items
            $items = WaPosCashSalesItems::where('wa_pos_cash_sales_id', $cashSales->id)->get();
            $dateTime = now();
            $vitu = [];

            foreach ($items as $item) {
                $inventoryItem = WaInventoryItem::find($item->wa_inventory_item_id);
                $vitu[] = [
                    'wa_internal_requisition_id' => $internalRequisition->id,
                    'wa_inventory_item_id' => $item->wa_inventory_item_id,
                    'quantity' => $item->qty,
                    'standard_cost' => $item->standard_cost,
                    'selling_price' => $item->selling_price,
                    'total_cost' => $item->total,
                    'tax_manager_id' => $item->tax_manager_id,
                    'vat_rate' => $item->vat_percentage,
                    'vat_amount' => $item->vat_amount,
                    'total_cost_with_vat' => $item->total,
                    'store_location_id' => $internalRequisition->to_store_id,
                    'hs_code' => $inventoryItem->hs_code,
                    'discount' => $item->discount_amount,
                    'discount_description' => '',
                    'created_at' => $dateTime,
                    'updated_at' => $dateTime,
                ];
            }

            WaInternalRequisitionItem::insert($vitu);

            // Dispatch event after commit
            PerformPostSaleActions::dispatch($internalRequisition)->afterCommit();

            try {
                /*sign invoice - but don't throw error if it fails*/
                self::signInvoice($internalRequisition);
            } catch (\Exception $e) {
                \Log::info('Invoice signing skipped - ESD disabled', [
                    'sales_no' => $cashSales->sales_no
                ]);
            }

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function signInvoice($internalRequisition): void
    {
        // Method disabled - returning immediately
        return;
        
        // Original implementation commented out below
        /*
        $invoice = $internalRequisition;
        try {
            // Get ESD configuration from settings
            $pinNo = Setting::where('key', 'PIN_NO')->value('value');
            $esdUrl = Setting::where('key', 'ESD_URL')->value('value');

            if (!$pinNo || !$esdUrl) {
                throw new \Exception("ESD configuration missing. PIN_NO or ESD_URL not set in settings.");
            }

            // Prepare invoice data for signing
            $items = $invoice->items;
            $totalAmount = $items->sum('total_cost_with_vat');
            
            // Log signing attempt
            \Log::info('Starting invoice signing process', [
                'invoice_no' => $invoice->requisition_no,
                'has_pin' => !empty($pinNo),
                'has_url' => !empty($esdUrl),
                'total_amount' => $totalAmount
            ]);

            // Make API call to ESD service
            $response = Http::post($esdUrl, [
                'pin' => $pinNo,
                'invoice_number' => $invoice->requisition_no,
                'amount' => $totalAmount,
                'items' => $items->map(function($item) {
                    return [
                        'name' => $item->inventoryItem->title,
                        'quantity' => $item->quantity,
                        'price' => $item->selling_price,
                        'vat' => $item->vat_amount
                    ];
                })->toArray()
            ]);

            if (!$response->successful()) {
                throw new \Exception("ESD signing failed: " . $response->body());
            }

            $esdData = $response->json();

            // Save ESD details
            $esdDetails = new WaEsdDetails();
            $esdDetails->invoice_number = $invoice->requisition_no;
            $esdDetails->cu_serial_number = $esdData['serial_number'];
            $esdDetails->cu_invoice_number = $esdData['invoice_number'];
            $esdDetails->verify_url = $esdData['verify_url'];
            $esdDetails->description = $esdData['status'];
            $esdDetails->status = 1;
            $esdDetails->save();

            \Log::info('Invoice signed successfully', [
                'invoice_no' => $invoice->requisition_no,
                'esd_serial' => $esdData['serial_number']
            ]);

        } catch (\Throwable $e) {
            \Log::error('ESD Signing Exception', [
                'invoice_number' => $invoice->requisition_no,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        */
    }
}