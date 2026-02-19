<?php

namespace App\Actions\SupplierInvoice;

use App\Models\TradeAgreement;
use App\Models\TradeAgreementDiscount;
use App\Models\TradeDiscount;
use App\WaSupplierInvoice;
use Illuminate\Support\Carbon;

class CreateTradeDiscount
{
    public function create(WaSupplierInvoice $invoice)
    {
        $tradeAgreement = TradeAgreement::where([
            'wa_supplier_id' => $invoice->supplier_id,
            'is_locked' => 1
        ])->first();

        if (is_null($tradeAgreement)) {
            return;
        }

        $monthlyDiscount = TradeAgreementDiscount::where([
            'discount_type' => 'End month Discount',
            'trade_agreements_id' => $tradeAgreement->id,
        ])->first();

        if (is_null($monthlyDiscount)) {
            return;
        }

        $options = json_decode($monthlyDiscount->other_options);

        $discounts = [];
        $totalDiscount = 0;

        foreach ($invoice->items as $item) {
            $itemId = $item->inventoryItem->id;

            if (!isset($options->$itemId)) {
                continue;
            }

            $option = $options->$itemId;

            $discountAmount = $this->calculateDiscount($item, $option);

            if ($discountAmount == 0) {
                continue;
            }

            $discounts[] = [
                'invoice_item_id' => $item->id,
                'item_code' => $option->stock_id,
                'discount_type' => $option->type,
                'discount_value' => $option->discount,
                'item_cost' => $item->standart_cost_unit,
                'item_quantity' => $item->quantity,
                'amount' => $discountAmount
            ];

            $totalDiscount += $discountAmount;
        }

        if ($totalDiscount == 0) {
            return;
        }

        $tradeDiscount = TradeDiscount::create([
            'supplier_id' => $invoice->supplier_id,
            'invoice_id' => $invoice->id,
            'trade_agreement_discount_id' => $monthlyDiscount->id,
            'supplier_invoice_number' => $invoice->supplier_invoice_number,
            'invoice_date' => $invoice->supplier_invoice_date,
            'invoice_amount' => $invoice->amount,
            'description' => Carbon::parse($invoice->supplier_invoice_date)->format('F Y') . ' DISCOUNT',
            'amount' => $totalDiscount,
            'prepared_by' => $invoice->prepared_by,
        ]);

        foreach ($discounts as $discount) {
            $tradeDiscount->items()->create($discount);
        }

        return $tradeDiscount;
    }

    protected function calculateDiscount($item, $option)
    {
        if (!$item || !$option || !isset($item->amount) || $item->amount <= 0) {
            return 0;
        }
        
        if ($option->type == 'Value') {
            return round($item->quantity * $option->discount, 2);
        }

        return round(($item->amount * $option->discount) / 100, 2);
    }
}
