@php
    $settings = getAllSettings();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Transfer Invoice Print</title>

    <style>
        @font-face {
            font-family: 'bitArray-A2';
            src: local('Courier New'), local('Courier'), monospace;
            font-weight: normal;
        }
        @font-face {
            font-family: 'bitArray-A2-bold';
            src: local('Courier New'), local('Courier'), monospace;
            font-weight: bold;
        }
        @font-face {
            font-family: 'bitArray-A2-narrow';
            src: local('Courier New'), local('Courier'), monospace;
            font-weight: normal;
            font-stretch: condensed;
        }
        body {
            font-family: "bitArray-A2", "Courier New", monospace;
            font-size: 18px;
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
            font-size: 18px;
        }

        .normal {
            display: block;
            font-size: 18px;
        }

        .bolder {
            display: block;
            font-size: 15px;
            font-family: "bitArray-A2-bold", "Courier New", monospace;
            font-weight: 700;
        }

        .customer-details .normal {
            font-size: 16px;
        }

        .table {
            width: 100%;
            font-size: 16px;
            border-collapse: collapse;
        }

        .table tr.heading td {
            border-bottom: 1px solid #000;
            font-family: "bitArray-A2-bold", "Courier New", monospace;
            font-weight: bold;
            padding: 5px 0;
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
        
        hr.new4 {
            border: none;
            border-bottom: 1px dotted #000;
            margin: 0;
        }
    </style>
</head>

<body>

<div id="receipt-main">
    <div id="receipt-header">
        <h3 style="margin: 10px; padding: 0; font-size: 15px; font-family: 'bitArray-A2-bold', 'Courier New', monospace;"> {{ $settings['COMPANY_NAME'] }}</h3>
        <span> {{ $settings['ADDRESS_2']}} {{ $settings['ADDRESS_3']}} </span>
        <span> Tel: {{ $settings['PHONE_NUMBER']}} </span>
        <span> Email: {{ $settings['EMAILS']}} </span>
        <span> Website: {{ $settings['WEBSITE']}} </span>
        <span> PIN No: {{ $settings['PIN_NO']}} </span>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <h3 style="margin: 0; padding: 0; font-size: 15px; font-family: 'bitArray-A2-bold', 'Courier New', monospace;"> CREDIT INVOICE</h3>
        <span class="bolder"> CIV No.: {{ $list->transfer_no ?? $list->id }} </span>
        @if ($list->print_count > 1)
            <span style="font-size:15px !important; font-family: 'bitArray-A2-bold', 'Courier New', monospace; font-weight: bold">REPRINT {{$list->print_count-1}}</span>
        @endif
        <br>
    </div>

    <div style="margin-top: 20px;" class="customer-details">
        @php
            $customer = $list->get_customer;
            $shift_id = \App\SalesmanShift::find($list->shift_id);
            
            // Calculate balances
            if ($list->print_count == 1 && is_null($list->printed_bf_balance)) {
                $currentInvoiceAmount = 0;
                foreach($list->getRelatedItem as $item) {
                    $currentInvoiceAmount += ($item->quantity * $item->selling_price);
                }
                $used_limit = \App\Model\WaDebtorTran::where('wa_customer_id', $customer->id ?? 0)->sum('amount');
                $currentBalance = $used_limit - $currentInvoiceAmount;
                $accountBalance = $used_limit;
                $list->printed_bf_balance = $currentBalance;
                $list->printed_account_balance = $accountBalance;
                $list->save();
            } else {
                $currentBalance = $list->printed_bf_balance ?? 0;
                $accountBalance = $list->printed_account_balance ?? 0;
                $used_limit = $accountBalance;
            }
        @endphp
        
        <span class="normal"> Customer Name: {{ $customer->customer_name ?? $list->name ?? 'N/A' }} </span>
        <span class="normal"> Customer Number: {{ $list->customer_phone_number ?? $customer->telephone ?? 'N/A' }}</span>
        @if($list->customer_pin ?? $customer->kra_pin ?? false)
            <span class="normal"> Customer KRA Pin: {{ $list->customer_pin ?? $customer->kra_pin }}</span>
        @endif
        <br>
        <span class="normal"> Prices are inclusive of tax where applicable. </span>
    </div>
    
    <table class="table">
        <tbody>
        <tr>
            <td colspan="4" style="border-top: 2px solid #000; padding: 0; margin: 0; height: 0; line-height: 0;"></td>
        </tr>
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
                <td colspan="4" style="text-align:left;">{{ $loop->iteration }}. {{strtoupper($item->getInventoryItemDetail->title)}}</td>
            </tr>
            <tr class="item">
                <td>{{$item->getInventoryItemDetail->pack_size->title ?? 'Pc(s)'}}</td>
                <td>{{number_format($item->quantity, 1)}}</td>
                <td>{{number_format($item->selling_price, 2)}}</td>
                <td style="text-align:right;">{{number_format($item->quantity * $item->selling_price, 2)}}</td>
            </tr>
            @php
                $TONNAGE += (($item->getInventoryItemDetail->net_weight ?? 1) * $item->quantity);
                $itemTotal = $item->quantity * $item->selling_price;
                $itemDiscount = $item->getDiscount();
                $gross_amount += $itemTotal;
                $net_amount += ($itemTotal - $itemDiscount);
                $total_discount += $itemDiscount;
                $count++;
                
                $vat = 0;
                if ($item->vat_rate > 0) {
                    $vat = ($item->vat_rate/(100 + $item->vat_rate)) * $item->total_cost_with_vat;
                }
                $vat_amount += $vat;
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
                <span style="border-bottom:1px dotted #000">&nbsp;</span>
            </td>
            <td colspan="2" style="text-align:right !important">
                <span style="border-bottom:1px dotted #000">VATABLE AMT</span>
            </td>
            <td colspan="1" style="text-align:right !important">
                <span style="border-bottom:1px dotted #000">VAT AMT</span>
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="1" style="text-align:left !important">
                &nbsp;
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
                You were served by: <b>{{getLoggeduserProfile()->name ?? 'N/A'}}</b>
                <br>
                Time: {{ $list->created_at->format('d/m/y h:i A') }}
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
        <span> CIV No: {{ $list->transfer_no ?? $list->id }} </span>
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
