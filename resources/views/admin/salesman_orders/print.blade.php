@php
    $settings = getAllSettings();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sales Order Print</title>

    <style>
        body {
            font-family: "Helvetica Neue", sans-serif;
            font-size: 14px;
            color: #000000;
        }
        #receipt-main {
            padding: 0;
            margin: 0;
            width: 100%;
        }

        #receipt-header {
            position: relative;
            width: 100%;
            text-align: center;
        }

        #receipt-header span {
            display: block;
            font-size: 14px;
        }

        .normal {
            display: block;
            font-size: 14px;
        }

        .bolder {
            display: block;
            font-size: 15px;
            font-weight: 700;
        }

        .customer-details .normal {
            font-size: 14px;
        }

        .table {
            width: 100%;
            font-size: 14px;
            border-collapse: collapse;
        }

        .table tr.heading td {
            border-bottom: 2px dotted #000;
            border-top: 2px dotted #000;
            font-weight: bold;
            padding: 10px 0;
        }

        .table tr.item td {
            padding: 8px 0;
            border-bottom: 1px dotted #000;
        }

        .table tr hr {
            border: none;
            border-bottom: 1px dotted #000;
            margin: 0;
        }
    </style>
</head>

<body>

@php
    // Custom formatting function for quantities and prices
    function formatNumber($number) {
        // If the number has a decimal part of .5, show 1 decimal place
        if (fmod($number, 1) == 0.5) {
            return number_format($number, 1);
        }
        // Otherwise, show no decimal places
        return number_format($number, 0);
    }
@endphp

<div id="receipt-main">
    <div id="receipt-header">
        <div style="width:100%; text-align:center;">
            @php
                $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
                $barcode = $generator->getBarcode($list->requisition_no ?? $list->id, $generator::TYPE_CODE_128);
            @endphp
            <div style="display: inline-block; height: 50px">
                <div style="transform: scale(1);">
                    {!! $barcode !!}
                </div>
            </div>
            <br>
        </div>

        <h3 style="margin: 10px; padding: 0; font-size: 15px;"> {{ $settings['COMPANY_NAME'] }}</h3>
        <span> {{ $settings['ADDRESS_2']}} {{ $settings['ADDRESS_3']}} </span>
        <span> Tel: {{ $settings['PHONE_NUMBER']}} </span>
        <span> Email: {{ $settings['EMAILS']}} </span>
        <span> Website: {{ $settings['WEBSITE']}} </span>
        <span> PIN No: {{ $settings['PIN_NO']}} </span>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <h3 style="margin: 0; padding: 0; font-size: 15px;"> SALES ORDER</h3>
        <span class="bolder"> Order No.: {{ $list->requisition_no ?? $list->id }} </span>
        @if ($list->print_count > 1)
            <span style="font-size:15px !important; font-weight: bold">REPRINT {{$list->print_count-1}}</span>
        @endif
        <br>
    </div>

    <div style="margin-top: 20px;" class="customer-details">
        @php
            $customer = $list->getRouteCustomer;
            $shift = $list->shift;
        @endphp
        
        <span class="normal"> Time: {{ $list->created_at->format('d/m/y  H:i A') }} </span>
        <span class="normal"> Customer Name: {{ $customer->bussiness_name ?? $customer->name ?? 'N/A' }} </span>
        <span class="normal"> Customer Number: {{ $customer->phone ?? 'N/A' }}</span>
        @if($customer->kra_pin)
            <span class="normal"> Customer KRA Pin: {{ $customer->kra_pin }}</span>
        @endif
        <span class="normal"> Served By: {{ $list->getrelatedEmployee->name ?? 'N/A' }} </span>
        <br>
        <span class="normal"> Prices are inclusive of tax where applicable. </span>
    </div>
    <br>

    <table class="table">
        <tbody>
        <tr class="heading">
            <td>Item</td>
            <td>Qty</td>
            <td>Price</td>
            <td style="text-align:right">Amount</td>
        </tr>
        @php
            $TONNAGE = 0;
            $gross_amount = 0;
            $count = 0;
            $vat_amount = 0;
            $total_discount = 0;
            $net_amount = 0;
        @endphp
        
        @foreach($list->getRelatedItem as $item)
            <tr style="width:100%;">
                <td colspan="4" style="text-align:left;">{{ $loop->iteration }}. {{strtoupper($item->getInventoryItemDetail->title)}}
                @if($item->selling_price == 0)
                    <span style="color: #008000;"> - FREE ITEM</span>
                @endif
                </td>
            </tr>
            <tr class="item">
                <td>{{$item->getInventoryItemDetail->pack_size->title ?? 'Pc(s)'}}</td>
                <td>{{formatNumber($item->quantity)}}</td>
                <td>
                    @if($item->selling_price == 0)
                        <span style="color: #008000;">FREE</span>
                    @else
                        {{number_format($item->selling_price, 2)}}
                    @endif
                </td>
                <td style="text-align:right;">
                    @if($item->selling_price == 0)
                        <span style="color: #008000;">FREE</span>
                    @else
                        {{number_format($item->total_cost_with_vat, 2)}}
                    @endif
                </td>
            </tr>
            @php
                $TONNAGE += (($item->getInventoryItemDetail->net_weight ?? 1) * $item->quantity);
                $gross_amount += $item->total_cost_with_vat;
                $net_amount += (($item->total_cost_with_vat) - ($item->discount ?? 0));
                $total_discount += $item->discount ?? 0;
                $count++;
                
                // Calculate VAT properly
                $vat = 0;
                if ($item->getInventoryItemDetail->taxManager && $item->getInventoryItemDetail->taxManager->tax_value > 0) {
                    $taxRate = (float)$item->getInventoryItemDetail->taxManager->tax_value;
                    if ($item->getInventoryItemDetail->taxManager->tax_format === 'PERCENTAGE') {
                        $itemTotal = ($item->selling_price * $item->quantity) - ($item->discount ?? 0);
                        $vat = ($taxRate / (100 + $taxRate)) * $itemTotal;
                    }
                }
                $actualVat = $item->vat_amount ?? $vat;
                $vat_amount += $actualVat;
            @endphp
        @endforeach

        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="3" style="text-align:left !important">
                Gross Totals <br>
                Discount <br>
                Totals <br>
            </td>
            <td colspan="1" style="text-align:right !important">
                {{ number_format($gross_amount, 2) }} <br>
                {{ number_format($total_discount, 2) }} <br>
                {{ number_format($net_amount, 2) }}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:left !important">
                {{strtoupper(getCurrencyInWords($gross_amount))}}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>

        <tr style="width:100%;">
            <td colspan="1" style="text-align:left !important">
                <span style="border-bottom:1px dashed">CODE</span>
            </td>
            <td colspan="2" style="text-align:right !important">
                <span style="border-bottom:1px dashed">VATABLE AMT</span>
            </td>
            <td colspan="1" style="text-align:right !important">
                <span style="border-bottom:1px dashed">VAT AMT</span>
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="1" style="text-align:left !important">
                S
            </td>
            <td colspan="2" style="text-align:right !important">
                {{number_format($gross_amount, 2)}}
            </td>
            <td colspan="1" style="text-align:right !important">
                {{number_format($vat_amount, 2)}}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="3" style="text-align:left !important">
                Tonnage<br>
            </td>
            <td colspan="1" style="text-align:right !important">
                {{number_format($TONNAGE, 2)}} KG<br>
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4" style="text-align:left !important">
                You were served by: <b>{{$list->getrelatedEmployee->name ?? 'N/A'}}</b>
                @if($shift)
                    <br>Shift: <b>{{$shift->shift_name ?? 'N/A'}}</b>
                @endif
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: center; font-size: 14px;">
        <span> Thank you for your business. </span>
        <br>
        <span> Order ID: {{ $list->requisition_no ?? $list->id }} </span>
        <br>
        <span> &copy; {{ \Carbon\Carbon::now()->year }}. Effecentrix POS. </span>
    </div>
</div>

@if(!isset($is_pdf))
<script type="text/javascript">
    window.print();
</script>
@endif

</body>
</html>
