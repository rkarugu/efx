@php
    $settings = getAllSettings();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Receipt - {{ $data->sales_no }}</title>

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

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body onload="window.print();">
<div id="receipt-main">
    <div id="receipt-header">
        <div style="width:100%; text-align:center;">
            @php
                $generator = new \Picqer\Barcode\BarcodeGeneratorHTML();
                $barcode = $generator->getBarcode($data->sales_no, $generator::TYPE_CODE_128);
            @endphp
            <div style="display: inline-block; height: 50px">
                <div style="transform: scale(1);">
                    {!! $barcode !!}
                </div>
            </div>
            <br>
        </div>

        <h3 style="margin: 10px; padding: 0; font-size: 15px;"> {{ $settings['COMPANY_NAME'] ?? 'Supermarket' }}</h3>
        <span> {{ $settings['ADDRESS_2'] ?? '' }} {{ $settings['ADDRESS_3'] ?? '' }} </span>
        <span> Tel: {{ $settings['PHONE_NUMBER'] ?? '' }} </span>
        <span> Email: {{ $settings['EMAILS'] ?? '' }} </span>
        <span> Website: {{ $settings['WEBSITE'] ?? '' }} </span>
        <span> PIN No: {{ $settings['PIN_NO'] ?? '' }} </span>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <h3 style="margin: 0; padding: 0; font-size: 15px;"> CASH SALE RECEIPT</h3>
        <span class="bolder"> Sale No.: {{ $data->sales_no }} </span>
        @if ($data->print_count > 1)
            <span style="font-size:15px !important; font-weight: bold">REPRINT {{$data->print_count-1}}</span>
        @endif
        <br>
    </div>

    <div style="margin-top: 20px;" class="customer-details">
        <span class="normal"> Time: {{ $data->created_at->format('d/m/y  H:i A') }} </span>
        <span class="normal"> Customer Name: {{ $data->customer ?? 'Walk-in Customer' }} </span>
        @if($data->customer_phone_number)
            <span class="normal"> Customer Number: {{ substr($data->customer_phone_number, 0, 2) . ' *****' . substr($data->customer_phone_number, -2) }}</span>
        @endif
        <br>
        <span class="normal"> Prices are inclusive of VAT where applicable. </span>
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
            $gross_amount = 0;
            $count = 0;
            $vat_amount = 0;
            $total_discount = 0;
            $net_amount = 0;
        @endphp
        @foreach($data->items as $item)
            <tr style="width:100%;">
                <td colspan="4" style="text-align:left;">{{ $loop->iteration }}.  {{ $item->item->title ?? $item->item->description ?? 'Product' }}</td>
            </tr>
            <tr class="item">
                <td>{{ $item->item->pack_size->title ?? 'Unit' }}</td>
                <td>{{ $item->qty }}</td>
                <td>{{ manageAmountFormat($item->selling_price) }}</td>
                <td style="text-align:right;">{{ manageAmountFormat($item->qty * $item->selling_price) }}</td>
            </tr>
            @if($item->discount_amount > 0)
                <tr>
                    <td colspan="3" style="text-align:right; font-size: 12px; color: #666;">
                        Discount ({{ $item->discount_percent }}%)
                    </td>
                    <td style="text-align:right; font-size: 12px; color: #666;">
                        -{{ manageAmountFormat($item->discount_amount) }}
                    </td>
                </tr>
            @endif
            @php
                $itemTotal = $item->qty * $item->selling_price;
                $gross_amount += $itemTotal;
                $net_amount += ($itemTotal - $item->discount_amount);
                $total_discount += $item->discount_amount;
                $count++;
                $vat_amount += $item->vat_amount;
            @endphp
        @endforeach

        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="3" style="text-align:left !important">
                Gross Totals <br>
                @if($total_discount > 0)
                    Discount <br>
                @endif
                Totals <br>
            </td>

            <td colspan="1" style="text-align:right !important">
                {{ manageAmountFormat($gross_amount) }} <br>
                @if($total_discount > 0)
                    -{{ manageAmountFormat($total_discount) }} <br>
                @endif
                {{ manageAmountFormat($net_amount) }}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr>
            <td colspan="4" style="text-align:left !important">
                {{strtoupper(getCurrencyInWords($net_amount))}}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>

        @php
            // Group items by VAT type for the VAT breakdown
            $vatBreakdown = [];
            foreach($data->items as $item) {
                $vatPercent = $item->vat_percentage ?? 16;
                $code = $vatPercent == 0 ? 'E' : 'S'; // E = Exempt, S = Standard
                
                if (!isset($vatBreakdown[$code])) {
                    $vatBreakdown[$code] = [
                        'vat_percent' => $vatPercent,
                        'net_amount' => 0,
                        'vat_amount' => 0
                    ];
                }
                
                $itemTotal = ($item->qty * $item->selling_price) - $item->discount_amount;
                $vatBreakdown[$code]['vat_amount'] += $item->vat_amount;
                $vatBreakdown[$code]['net_amount'] += $itemTotal - $item->vat_amount;
            }
        @endphp
        
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
        @foreach($vatBreakdown as $code => $breakdown)
            <tr style="width:100%;">
                <td colspan="1" style="text-align:left !important">
                    {{ $code }} @if($breakdown['vat_percent'] > 0)({{ $breakdown['vat_percent'] }}%)@else(Exempt)@endif
                </td>
                <td colspan="2" style="text-align:right !important">
                    {{ manageAmountFormat($breakdown['net_amount']) }}
                </td>
                <td colspan="1" style="text-align:right !important">
                    {{ manageAmountFormat($breakdown['vat_amount']) }}
                </td>
            </tr>
        @endforeach
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="3" style="text-align:left !important">
                @foreach ($payments as $payment)
                    {{ucfirst(strtolower($payment->title))}} <br>
                @endforeach
            </td>
            <td colspan="1" style="text-align:right !important">
                @php
                    $totalAmountPaid = 0;
                @endphp
                @foreach ($payments as $payment)
                    {{ manageAmountFormat($payment->amount) }}<br>
                    @php
                        $totalAmountPaid += $payment->amount;
                    @endphp
                @endforeach
            </td>
        </tr>
        <tr style="width: 100%;">
            <td colspan="3" style="text-align:left !important">
                Total Paid<br>
            </td>
            <td colspan="1" style="text-align:right !important">
                {{ manageAmountFormat($totalAmountPaid) }}<br>
            </td>
        </tr>
        <tr style="width: 100%;">
            <td colspan="3" style="text-align:left !important">
                Change<br>
            </td>
            <td colspan="1" style="text-align:right !important">
                {{ manageAmountFormat($data->change) }}<br>
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4" style="text-align:left !important">
                @if($data->attending_cashier && $data->attending_cashier != $data->user_id)
                    You were served by: <b>{{ $data->attendingCashier->name ?? 'Cashier' }}</b>
                    <br>
                    Sales Rep: <b>{{ $data->user->name ?? 'N/A' }}</b>
                @else
                    You were served by: <b>{{ $data->user->name ?? 'Cashier' }}</b>
                @endif
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4"><hr class="new4"></td>
        </tr>
        </tbody>
    </table>

    @if($esd_details)
        <div style="width:100%; text-align:center; margin-top: 20px !important;">
            @if($esd_details->verify_url!="")
                {{ QrCode::size(120)->generate($esd_details->verify_url) }}
            @endif
            <br>
            <br>
            <span class="normal"> {{ $esd_details->cu_serial_number }}</span>
            <span class="normal"> CU Invoice Number : {{ $esd_details->cu_invoice_number }}</span>
        </div>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 14px;">
        <span> Thank you for shopping with us. </span>
        <br>
        <span> &copy; {{ \Carbon\Carbon::now()->year }}. {{ $settings['COMPANY_NAME'] ?? 'Supermarket' }}. </span>
    </div>
</div>

<script>
    // Auto-close after printing (optional)
    window.onafterprint = function() {
        window.close();
    };
</script>
</body>
</html>

