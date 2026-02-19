@php
    $settings = getAllSettings();
@endphp

        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
{{-- 
    <style>
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
            font-size: 11px;
        }

        .normal {
            display: block;
            font-size: 9px;
        }

        div.dashed {
            border-top: 1px dashed #000 !important;
            border-bottom: 1px dashed #000 !important;
            padding: 2px 0;
        }
        div.dashed2 {
            /* border-top: 1px dashed #000 !important; */
            border-bottom: 1px dashed #000 !important;
            padding: 2px 0;
        }

        .order-item {
            font-size: 11px;
            width: 50%;
            float: left;
        }

        .customer-details .normal {
            font-size: 10px;
        }
    </style> --}}

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
            font-size: 28px;
        }

        .normal {
            display: block;
            font-size: 28px;
        }

        div.dashed {
            border-top: 1px dotted #000 !important;
            border-bottom: 1px dotted #000 !important;
            padding: 4px 0;
        }

        .order-item {
            font-size: 28px;
            width: 50%;
            float: left;
        }

        .customer-details .normal {
            font-size: 24px;
        }
        
        hr.solid {
            border: none;
            border-top: 2px solid #000;
            margin: 5px 0;
        }
        
        hr.dotted {
            border: none;
            border-top: 1px dotted #000;
            margin: 5px 0;
        }
        
        .table-header {
            font-family: 'bitArray-A2-bold', 'Courier New', monospace;
            font-weight: bold;
            font-size: 28px;
            padding: 5px 0;
        }
    </style>
</head>

<body>
<div id="receipt-main">
    <div id="receipt-header">
        <h3 style="margin: 5px; padding: 0; font-size: 32px; font-weight: bold; font-family: 'bitArray-A2-bold', 'Courier New', monospace;"> {{ $settings['COMPANY_NAME'] }} </h3>

        <span> {{ $settings['ADDRESS_2']}} {{ $settings['ADDRESS_3']}} </span>
        <span> Tel: {{ $settings['PHONE_NUMBER']}} </span>
        <span> Email: {{ $settings['EMAILS']}} </span>
        <span> Website: {{ $settings['WEBSITE']}} </span>
        <span> PIN No: {{ $settings['PIN_NO']}} </span>
    </div>

    <div style="margin-top: 8px; text-align: center;">
        <h3 style="margin: 0; padding: 0; font-size: 30px; font-weight: bold; font-family: 'bitArray-A2-bold', 'Courier New', monospace;"> INVOICE </h3>
    </div>

    <div style="margin-top: 8px; text-align: center;" class="customer-details">
        <span class="normal" style="font-weight: bold;"> Order No.: {{ $data['order_number'] }} </span>
    </div>
    
    <div style="margin-top: 8px;" class="customer-details">
        <span class="normal"> Customer Name: {{ $data['customer_name'] }} </span>
        <span class="normal"> Customer Number: {{ $data['customer_number'] }} </span>
        <span class="normal"> Customer Pin: {{ $data['kra_pin'] }}</span>
        <br>
        <span class="normal"> Prices are inclusive of tax where applicable. </span>
    </div>

    <hr class="solid">
    <div class="table-header" style="position: relative; width: 100%; height: 35px;">
        <span style="position: absolute; left: 0; width: 40%;">Item</span>
        <span style="position: absolute; left: 40%; width: 15%; text-align: center;">Qty</span>
        <span style="position: absolute; left: 55%; width: 20%; text-align: center;">Price</span>
        <span style="position: absolute; left: 75%; width: 25%; text-align: right;">Amount</span>
    </div>
    <hr class="solid">
    
    <div style="padding: 5px 0;">
        @foreach($data['items'] as $index => $item)
            <div style="position: relative; width: 100%; margin-top: 5px; border-bottom: 1px dotted black; padding-bottom: 5px;" class="order-item-main">
                <div style="position: relative; width: 100%;" class="normal"> {{ $index + 1 }}. {{ ucwords(strtolower($item->title)) }} @if($item->selling_price == 0) <span style="font-weight: bold;">(PROMOTION)</span> @endif </div>
                <div style="position: relative; width: 100%; height: 30px; font-size: 28px;">
                    <span style="position: absolute; left: 40%; width: 15%; text-align: center;">{{ number_format($item->quantity, 2) }}</span>
                    <span style="position: absolute; left: 55%; width: 20%; text-align: center;">{{ number_format($item->selling_price, 2) }}</span>
                    <span style="position: absolute; left: 75%; width: 25%; text-align: right;">{{ number_format($item->total_cost, 2) }}</span>
                </div>
                @if($item->discount > 0)
                    <div style="position: relative; width: 100%; height: 30px; font-size: 28px;">
                        <span style="position: absolute; left: 0; width: 40%; padding-left: 20px;">Discount</span>
                        <span style="position: absolute; left: 75%; width: 25%; text-align: right;">{{ number_format($item->discount * -1, 2) }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="normal" style="margin-top: 5px; position: relative; width: 100%;">
        <div style="margin-top: 5px;">
            <div style="position: relative; width: 50%; float: left;"> Gross Amount</div>
            <div style="position: relative; width: 50%; float: left; text-align: right;"> {{ number_format($data['gross_total'], 2) }} </div>
            <div style="clear: both;"></div>
        </div>

        <div style="margin-top: 5px;">
            <div style="position: relative; width: 50%; float: left;"> Discount</div>
            <div style="position: relative; width: 50%; float: left; text-align: right;"> {{ number_format($data['order_discount'], 2) }} </div>
            <div style="clear: both;"></div>
        </div>

        <div style="margin-top: 5px;">
            <div style="position: relative; width: 50%; float: left;"> Net Amount</div>
            <div style="position: relative; width: 50%; float: left; text-align: right;"> {{ number_format($data['net_amount'], 2) }} </div>
            <div style="clear: both;"></div>
        </div>

        <div>
            <div style="position: relative; width: 50%; float: left;"> VAT</div>
            <div style="position: relative; width: 50%; float: left; text-align: right;"> {{ number_format($data['total_vat'], 2) }} </div>
            <div style="clear: both;"></div>
        </div>

        <div style="margin-top: 5px; font-weight: bold;" class="dashed">
            <div style="position: relative; width: 50%; float: left;"> Total</div>
            <div style="position: relative; width: 50%; float: left; text-align: right;"> {{ number_format($data['order_total'], 2) }} </div>
            <div style="clear: both;"></div>
        </div>
    </div>
  
    <hr class="dotted">
    
    <div style="margin-top: 10px;" class="customer-details">
        <span class="normal"> Time: {{ \Carbon\Carbon::now()->format('d/m/Y h:i A') }} </span>
        <span class="normal"> Sales Rep: {{ $data['salesman'] }} </span>
        <span class="normal"> Route: {{ $data['route'] }} </span>
    </div>
    
    <br>
    
    <div class="dashed"  style="text-align: center">
        <h2 style="font-size: 28px; margin: 3px 0; font-weight: bold;">PAYMENT CHANNELS</h2>
        <h3 style="font-size: 22px; margin: 2px 0; font-weight: normal;">(PLEASE MAKE PAYMENTS THROUGH OUR AUTHORIZED CHANNELS)</h3>
        <br>
    </div>
   
    <br>
    <span class="normal"> {{ $settings['INVOICE_NOTE'] ?? '' }}</span>

    <br>

    @if($esdData)
        <div style="width:100%; text-align:center;">
            <img src="data:image/png;base64, {!! base64_encode(QrCode::size(200)->generate($esdData->verify_url)) !!} " alt="">
            <br>
            <span class="normal"> {{ $esdData->cu_serial_number }}</span>
            <span class="normal"> CU Invoice Number : {{ $esdData->cu_invoice_number }}</span>
        </div>
    @endif

    <div style="margin-top: 40px; text-align: center; font-size: 24px;">
        <span> Thank you for shopping with us. </span>
        <br>
        <span> &copy; {{ \Carbon\Carbon:: now()->year }}. Powered by Altrom Technologies. </span>
    </div>
</div>
</body>

</html>
