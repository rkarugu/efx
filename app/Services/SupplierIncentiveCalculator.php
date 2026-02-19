<?php

namespace App\Services;

use App\Model\WaStockMove;
use App\Models\SalesmanSupplierIncentiveEarning;
use Illuminate\Http\Request;

class SupplierIncentiveCalculator
{
    public static function add(WaStockMove $stockMove)
    {
        /*get active incentive with product code*/
        $incentives = self::getIncentive($stockMove->stock_id_code);
        
        // Check if incentives is valid and is an array
        if (!$incentives || !is_array($incentives) || empty($incentives)) {
            return; // No incentives to process
        }

        if ($stockMove->qauntity > 0) {
            /*this is a return so incentive quantity should be negative*/
            $quantity = abs((float)$stockMove->qauntity);
        } else{
            /*this is a sale quantity should be positive*/
            $quantity = -abs((float)$stockMove->qauntity);
        }




        $data = [];
        foreach ($incentives as $incentive) {
            // Ensure $incentive is an array and has required keys
            if (!is_array($incentive) || !isset($incentive['stock_id_code'])) {
                continue; // Skip invalid incentive
            }

            /**/
            $data[]  = [
                'user_id' => $stockMove->user_id,
                'route_id' => $stockMove->route_id,
                'quantity' => $quantity,
                'wa_stock_move_id' => $stockMove->id,
                'stock_id_code' => $incentive['stock_id_code'] ?? null,
                'supplier_code' => $incentive['supplier']['supplier_code'] ?? null,
                'incentive_id' => $incentive['promotiondetail']['id'] ?? null,
                'incentive' => $incentive['promotiondetail']['offer_title'] ?? null,
                'target' => $incentive['trade_amount'] ?? null,
                'reward' => $incentive['offer_amount'] ?? null,
                'created_at'=> now(),
                'updated_at'=> now(),
            ];
        }
        
        if (!empty($data)) {
            SalesmanSupplierIncentiveEarning::insert($data);
        }

    }


    public static function getIncentive($code)
    {
        $data = [
            'stock_id_code' => $code,
        ];

        $api = new \App\Services\ApiService(env('SUPPLIER_PORTAL_URI'));
        $resp = $api->get_incentive_by_product(
            $data
        );
        return $resp;
    }
}