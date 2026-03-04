<?php
namespace App\Model;
use App\Enums\PromotionMatrix;
use App\ItemPromotion;
use App\Models\PromotionType;
use App\Models\ReturnReason;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaPosCashSalesItemReturns extends Model
{
    protected $table = "wa_pos_cash_sales_items_return";

    protected $guarded = [];

    protected $casts= [
        'accepted_at'=> 'datetime'
    ];


    public function PosCashSale(): BelongsTo
    {
        return $this->belongsTo(WaPosCashSales::class,'wa_pos_cash_sales_id');
    }

    public function saleItem():BelongsTo
    {
        return $this->belongsTo(WaPosCashSalesItems::class,'wa_pos_cash_sales_item_id');
    }

    public function item()
    {
        return $this->hasOneThrough(WaInventoryItem::class,WaPosCashSalesItems::class ,'wa_inventory_item_id','id');
    }

    public function receiver(): BelongsTo
    {
        return  $this->belongsTo(User::class,'accepted_by')->withDefault([
            'name'=> ''
        ]);
    }

    public function returner(): BelongsTo
    {
        return  $this->belongsTo(User::class,'return_by');
    }

    public function reasons()
    {
        return $this->belongsTo(ReturnReason::class,'reason_id');
    }

    public function returnReason()
    {
        return $this->belongsTo(ReturnReason::class,'reason_id');
    }

    public function location()
    {
        return $this->belongsTo(WaUnitOfMeasure::class,'bin_location_id');
    }

    public function total()
    {
        $discount = $this->calculateInventoryItemDiscount();
        $amount = $this->return_quantity * $this->saleItem->selling_price;
        return $amount-$discount;
    }
    public function calculateInventoryItemDiscount()
    {
        $discount = 0;
        $discountDescription = null;
        $item_id = $this->saleItem->wa_inventory_item_id;
        $qty = $this->return_quantity;
        $discountBand = DB::table('discount_bands')->where('inventory_item_id', $item_id)
            ->where('from_quantity', '<=', $qty)
            ->where('to_quantity', '>=', $qty)
            ->first();
        if ($discountBand) {
            $discount = $discountBand->discount_amount * $qty;
            $discountDescription = "$discountBand->discount_amount discount for quantity between $discountBand->from_quantity and $discountBand->to_quantity";
        }else{
            /*check for discount price promotion*/
            $discount = $this->checkPromotion($item_id);
        }
        return $discount;
    }
    public function checkPromotion($item_id)
    {
        $discount = 0;
        $today = Carbon::today();
        $promotion = ItemPromotion::where('inventory_item_id', $item_id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->where('from_date', '<=', $today)
                    ->where('to_date', '>=', $today);
            })
            ->first();

        if ($promotion) {
            /*get promotion type*/
            $promotionType = $promotion->promotion_type_id ? PromotionType::find($promotion->promotion_type_id)->description : null;

            if ($promotionType)
            {

                /*Price Discount*/
                if ($promotionType == PromotionMatrix::PD->value)
                {
                    /*chenge selling price*/
                    $selling_price = $promotion->promotion_price;
                    $current_price = $promotion->current_price;
                    $discount = $current_price - $selling_price;

                }

            }

        }

        return $discount;
    }
}