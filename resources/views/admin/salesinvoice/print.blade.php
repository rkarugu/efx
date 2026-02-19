@php
    $settings = getAllSettings();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sales Invoice Print</title>

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
            font-family: "bitArray-A2-bold", "Courier New", monospace;
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
        <div style="width:100%; text-align:center;">
            <div style="display: inline-block; height: 50px; border: 2px solid #000; padding: 10px; margin: 10px;">
                <strong>Invoice: {{ $row->sales_invoice_number ?? $row->id }}</strong>
            </div>
            <br>
        </div>

        <h3 style="margin: 10px; padding: 0; font-size: 15px; font-family: 'bitArray-A2-bold', 'Courier New', monospace;"> {{ $settings['COMPANY_NAME'] }}</h3>
        <span> {{ $settings['ADDRESS_2']}} {{ $settings['ADDRESS_3']}} </span>
        <span> Tel: {{ $settings['PHONE_NUMBER']}} </span>
        <span> Email: {{ $settings['EMAILS']}} </span>
        <span> Website: {{ $settings['WEBSITE']}} </span>
        <span> PIN No: {{ $settings['PIN_NO']}} </span>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <h3 style="margin: 0; padding: 0; font-size: 15px; font-family: 'bitArray-A2-bold', 'Courier New', monospace;"> SALES INVOICE</h3>
        <span class="bolder"> Invoice No.: {{ $row->sales_invoice_number ?? $row->id }} </span>
        @if ($row->print_count > 1)
            <span style="font-size:15px !important; font-family: 'bitArray-A2-bold', 'Courier New', monospace; font-weight: bold">REPRINT {{$row->print_count-1}}</span>
        @endif
        <br>
    </div>

    <div style="margin-top: 20px;" class="customer-details">
        <span class="normal"> Customer Name: {{ $row->getRelatedCustomer->customer_name ?? 'N/A' }} </span>
        <span class="normal"> Customer Number: {{ $row->getRelatedCustomer->phone_number ?? 'N/A' }}</span>
        @if($row->getRelatedCustomer->kra_pin ?? false)
            <span class="normal"> Customer KRA Pin: {{ $row->getRelatedCustomer->kra_pin }}</span>
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
            $vat_amount = 0;
            $gross_amount = 0;
            $count = 0;
            $total_discount = 0;
            $net_amount = 0;
            $items = $itemsdata ?? $row->getRelatedItem;
        @endphp
        
        @foreach($items as $item)
            <tr style="width:100%;">
                <td colspan="4" style="text-align:left;">{{ $loop->iteration }}. {{strtoupper($item->item_name ?? $item->getInventoryItemDetail->title ?? 'N/A')}}</td>
            </tr>
            <tr class="item">
                <td>{{$item->getInventoryItemDetail->getUnitOfMeausureDetail->title ?? $item->bin ?? 'Pc(s)'}}</td>
                <td>{{number_format($item->quantity ?? 1, 1)}}</td>
                <td>{{number_format($item->selling_price ?? 0, 2)}}</td>
                <td style="text-align:right;">{{number_format(($item->quantity ?? 1) * ($item->selling_price ?? 0), 2)}}</td>
            </tr>
            @php
                $itemTotal = ($item->quantity ?? 1) * ($item->selling_price ?? 0);
                $itemDiscount = ($item->discount ?? 0) * ($item->quantity ?? 1);
                $itemVat = $item->vat_amount ?? 0;
                
                $gross_amount += $itemTotal;
                $total_discount += $itemDiscount;
                $vat_amount += $itemVat;
                $net_amount += ($itemTotal - $itemDiscount);
                $count++;
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
                Invoice Status<br>
            </td>
            <td colspan="1" style="text-align:right !important">
                {{strtoupper($row->status ?? 'ACTIVE')}}<br>
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4" style="text-align:left !important">
                You were served by: <b>{{$row->getrelatedEmployee->name ?? 'N/A'}}</b>
                <br>
                Time: {{ $row->created_at->format('d/m/y h:i A') }}
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
        <span> Invoice No: {{ $row->sales_invoice_number ?? $row->id }} </span>
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
