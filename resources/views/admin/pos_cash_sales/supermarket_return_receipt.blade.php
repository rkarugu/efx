@php
    $settings = getAllSettings();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Return Receipt - {{ $returnGrn }}</title>

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

        .return-notice {
            background: #f39c12;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            margin: 20px 0;
            border-radius: 5px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .return-notice {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
                $barcode = $generator->getBarcode($returnGrn, $generator::TYPE_CODE_128);
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

    <div class="return-notice">
        ⚠ RETURN / CREDIT NOTE ⚠
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <h3 style="margin: 0; padding: 0; font-size: 15px;"> RETURN RECEIPT</h3>
        <span class="bolder"> Return GRN: {{ $returnGrn }} </span>
        <span class="normal"> Original Sale: {{ $sale->sales_no }} </span>
        <br>
    </div>

    <div style="margin-top: 20px;" class="customer-details">
        <span class="normal"> Return Date: {{ $returnDate->format('d/m/y  H:i A') }} </span>
        <span class="normal"> Original Sale Date: {{ $sale->created_at->format('d/m/y  H:i A') }} </span>
        <span class="normal"> Customer Name: {{ $sale->customer ?? 'Walk-in Customer' }} </span>
        @if($sale->customer_phone_number)
            <span class="normal"> Customer Number: {{ substr($sale->customer_phone_number, 0, 2) . ' *****' . substr($sale->customer_phone_number, -2) }}</span>
        @endif
        <br>
        <span class="normal"> Items returned and credited to customer. </span>
    </div>
    <br>

    <table class="table">
        <tbody>
        <tr class="heading">
            <td>Item</td>
            <td>Qty</td>
            <td>Price</td>
            <td>Reason</td>
            <td style="text-align:right">Amount</td>
        </tr>
        @php
            $total_return_amount = 0;
            $count = 0;
        @endphp
        @foreach($returnItems as $returnItem)
            @php
                $saleItem = $returnItem['sale_item'];
                $item = $saleItem->item;
                $returnAmount = $returnItem['quantity'] * $saleItem->selling_price;
                $total_return_amount += $returnAmount;
                $count++;
            @endphp
            <tr style="width:100%;">
                <td colspan="5" style="text-align:left;">{{ $loop->iteration }}.  {{ $item->title ?? 'Product' }}</td>
            </tr>
            <tr class="item">
                <td>{{ $item->stock_id_code ?? 'N/A' }}</td>
                <td>{{ $returnItem['quantity'] }}</td>
                <td>{{ manageAmountFormat($saleItem->selling_price) }}</td>
                <td>{{ $returnItem['reason']->reason ?? 'N/A' }}</td>
                <td style="text-align:right;">{{ manageAmountFormat($returnAmount) }}</td>
            </tr>
        @endforeach

        <tr style="width:100%;">
            <td colspan="5"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="4" style="text-align:left !important; font-weight: bold;">
                Total Return Amount
            </td>
            <td colspan="1" style="text-align:right !important; font-weight: bold;">
                {{ manageAmountFormat($total_return_amount) }}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="5"><hr class="new4"></td>
        </tr>
        <tr>
            <td colspan="5" style="text-align:left !important">
                {{strtoupper(getCurrencyInWords($total_return_amount))}}
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="5"><hr class="new4"></td>
        </tr>
        <tr style="width:100%;">
            <td colspan="5" style="text-align:left !important">
                Processed by: <b>{{ $processedBy->name ?? 'Staff' }}</b>
            </td>
        </tr>
        <tr style="width:100%;">
            <td colspan="5"><hr class="new4"></td>
        </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; padding: 10px; border: 2px solid #000; text-align: center;">
        <span class="normal" style="font-weight: bold;">REFUND INFORMATION</span><br>
        <span class="normal">Refund Amount: {{ manageAmountFormat($total_return_amount) }}</span><br>
        <span class="normal">Refund Method: Cash / Original Payment Method</span><br>
        <span class="normal" style="font-size: 12px; margin-top: 10px;">Please present this receipt to claim your refund</span>
    </div>

    <div style="margin-top: 40px; text-align: center; font-size: 14px;">
        <span> Thank you for your understanding. </span>
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
