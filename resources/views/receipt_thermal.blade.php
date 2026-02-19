@php
    $settings = getAllSettings();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
            width: 80mm;
            padding: 5px;
        }
        
        .center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .company-info {
            font-size: 9px;
            line-height: 1.4;
        }
        
        .invoice-title {
            font-size: 12px;
            font-weight: bold;
            margin: 8px 0;
        }
        
        .customer-details {
            font-size: 9px;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        
        .customer-details div {
            margin: 2px 0;
        }
        
        .items-table {
            width: 100%;
            font-size: 9px;
            margin-bottom: 10px;
        }
        
        .item-row {
            margin: 5px 0;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 3px;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }
        
        .totals {
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 10px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .totals-row.grand-total {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            font-size: 9px;
        }
        
        .payment-info {
            margin: 10px 0;
            padding: 5px;
            background: #f0f0f0;
            border: 1px solid #000;
            text-align: center;
        }
        
        .payment-code {
            font-size: 12px;
            font-weight: bold;
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $settings['COMPANY_NAME'] ?? 'COMPANY NAME' }}</div>
        <div class="company-info">
            {{ $settings['ADDRESS_2'] ?? '' }} {{ $settings['ADDRESS_3'] ?? '' }}<br>
            Tel: {{ $settings['PHONE_NUMBER'] ?? '' }}<br>
            Email: {{ $settings['EMAILS'] ?? '' }}<br>
            PIN: {{ $settings['PIN_NO'] ?? '' }}
        </div>
    </div>

    <!-- Invoice Title -->
    <div class="center invoice-title">SALES INVOICE</div>

    <!-- Customer Details -->
    <div class="customer-details">
        <div><strong>Invoice:</strong> {{ $data['order_number'] }}</div>
        <div><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</div>
        <div><strong>Sales Rep:</strong> {{ $data['salesman'] }}</div>
        <div><strong>Customer:</strong> {{ $data['customer_name'] }}</div>
        <div><strong>Phone:</strong> {{ $data['customer_number'] }}</div>
        @if(!empty($data['kra_pin']))
        <div><strong>PIN:</strong> {{ $data['kra_pin'] }}</div>
        @endif
        <div><strong>Route:</strong> {{ $data['route'] }}</div>
    </div>

    <!-- Items -->
    <div class="items-table">
        @foreach($data['items'] as $index => $item)
        <div class="item-row">
            <div class="item-name">{{ $index + 1 }}. {{ ucwords(strtolower($item->title)) }}</div>
            <div class="item-details">
                <span>{{ number_format($item->selling_price, 2) }} x {{ round($item->quantity) }}</span>
                <span><strong>{{ number_format($item->total_cost, 2) }}</strong></span>
            </div>
            @if($item->discount > 0)
            <div class="item-details">
                <span>Discount</span>
                <span>-{{ number_format($item->discount, 2) }}</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-row">
            <span>Gross Amount:</span>
            <span>{{ number_format($data['gross_total'], 2) }}</span>
        </div>
        @if($data['order_discount'] > 0)
        <div class="totals-row">
            <span>Discount:</span>
            <span>-{{ number_format($data['order_discount'], 2) }}</span>
        </div>
        @endif
        @if($data['order_returns'] > 0)
        <div class="totals-row">
            <span>Returns:</span>
            <span>-{{ number_format($data['order_returns'], 2) }}</span>
        </div>
        @endif
        <div class="totals-row">
            <span>Net Amount:</span>
            <span>{{ number_format($data['net_amount'], 2) }}</span>
        </div>
        <div class="totals-row">
            <span>VAT (16%):</span>
            <span>{{ number_format($data['total_vat'], 2) }}</span>
        </div>
        <div class="totals-row grand-total">
            <span>TOTAL:</span>
            <span>KES {{ number_format($data['order_total'], 2) }}</span>
        </div>
    </div>

    <!-- Payment Info -->
    @if(isset($payment_code))
    <div class="payment-info">
        <div>MPESA PAYBILL</div>
        <div class="payment-code">{{ $payment_code }}</div>
        <div style="font-size: 8px;">Use this code for payment</div>
    </div>
    @endif

    <!-- Bank Details -->
    @if(!empty($data['equity_account']) || !empty($data['kcb_account']))
    <div style="font-size: 8px; margin: 10px 0; text-align: center;">
        <div><strong>BANK DETAILS</strong></div>
        @if(!empty($data['equity_account']))
        <div>Equity: {{ $data['equity_account'] }}</div>
        @endif
        @if(!empty($data['kcb_account']))
        <div>KCB: {{ $data['kcb_account'] }}</div>
        @endif
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div>Prices are inclusive of tax where applicable</div>
        <div style="margin-top: 5px;">Thank you for your business!</div>
        <div style="margin-top: 5px; font-size: 8px;">{{ $settings['WEBSITE'] ?? '' }}</div>
    </div>

    @if(isset($esdData) && $esdData)
    <div style="margin-top: 10px; font-size: 8px; text-align: center;">
        <div>ESD Verified</div>
        <div>CU Serial: {{ $esdData->cu_serial_number ?? '' }}</div>
        <div>Receipt No: {{ $esdData->receipt_number ?? '' }}</div>
    </div>
    @endif
</body>
</html>
