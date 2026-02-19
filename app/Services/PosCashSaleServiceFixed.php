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
use App\Model\WaInventoryItem;
use App\Model\WaLocationAndStore;
use App\Model\WaPosCashSales;
use App\Model\WaPosCashSalesItem;
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

class PosCashSaleServiceFixed
{
    /**
     * Fixed version of the POS Cash Sale Service with proper date filtering for promotions
     */
    public static function createSale($data)
    {
        // Get the original service content and fix the promotion query
        $originalService = new \App\Services\PosCashSaleService();
        
        // This is a demonstration of how the promotion query should be fixed
        // The actual fix needs to be applied to the original service
        
        return "This is a demonstration service. The actual fix needs to be applied to PosCashSaleService.php line 113";
    }
    
    /**
     * This is how the promotion query should look in PosCashSaleService.php line 113
     */
    public static function getFixedPromotionQuery($item_id)
    {
        $today = Carbon::today();
        
        $promotion = ItemPromotion::where('inventory_item_id', $item_id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->where('from_date', '<=', $today)
                    ->where(function ($subQuery) use ($today) {
                        $subQuery->where('to_date', '>=', $today)
                                 ->orWhereNull('to_date');
                    });
            })
            ->first();
            
        return $promotion;
    }
}
