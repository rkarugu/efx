@php
    $settings = getAllSettings();
@endphp

        <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> {{ $report_name }} </title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            text-align: center;
            color: #777;
            margin: 0;
            padding: 0;
        }

        body h1 {
            font-weight: 300;
            margin-bottom: 0;
            padding-bottom: 0;
            color: #000;
        }

        body a {
            color: #06f;
        }

        .invoice-box {
            margin: auto;
            font-size: 11px;
            line-height: 20px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        .invoice-box * {
            font-size: 11px;
        }

        .invoice-box table, table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table td {
            padding: 3px;
            vertical-align: top;
        }

        .invoice-box table tr td:last-child {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 11px;
            /*line-height: 45px;*/
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item:last-child {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        .bordered-div {
            width: 100%;
            border: 1px solid;
            padding: 8px;
            margin-bottom: 20px;
            font-size: 11px;
            font-weight: 400;
            text-align: left;
            color: #555;
        }

        .bordered-div span {
            display: block;
            margin-bottom: 5px;
        }

       

        #customers-table tr.heading td {
            font-weight: 400;
            text-align: left;
            color: #555;
        }
         #customers-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px !important;
            font-weight: 400;
        }
        #customers-table, #customers-table th, #customers-table td {
            border: 1px solid #555;
        }
        #customers-table th, #customers-table td {
            padding: 10px;
        }
        #customers-table th {
            text-align: left !important;
        }
        #customers-table td ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        #customers-table td {
            vertical-align: top;
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <table style="text-align: left;">
        <tbody>
        <tr class="top">
            <th colspan="2"  style="font-size:18px !important;font-weight: bold; text-align:left !important;">
                {{ $settings['COMPANY_NAME'] }}
            </th>
        </tr>
        <tr class="top">
            <th colspan="2"  style="font-size:16px !important;font-weight: bold; text-align:left !important; margin:3px !important;">
                {{ $branch }}
            </th>
        </tr>
        <tr class="top">
            <th colspan="1"  style="font-size:15px !important;font-weight: bold; text-align:left !important;">
                DELIVERY SHEET
            </th>
            <th colspan="1"  style="font-size:15px !important;font-weight: bold; text-align:right !important; margin:3px !important;">
                {{ $shift->date }}
            </th>
        </tr>

       
        </tbody>
    </table>
</div>

<div class="bordered-div" style="font-size: 13px !important">
    <div style="float: left;">
        <span> Delivery No: {{ $schedule?->delivery_number ?? '' }} </span>
        <span> Route: {{ $shift->relatedRoute->route_name }} </span>
        <span> Salesman: {{ $shift->salesman->name }} </span>

    </div>

    <div style="float: left; margin-left: 30px;">
        <span> Vehicle: {{ $schedule?->vehicle?->license_plate_number }} </span>
        <span> Driver: {{ $schedule?->driver?->name }} </span>
        <span> Loader: - </span>

    </div>

    <div style="float: left; margin-left: 30px;">
        <span> Total Weight: {{ $shift->shift_tonnage }}T </span>
        <span> Total Amount: {{ manageAmountFormat($shift->shift_total) }} </span>
    </div>

    <div style="clear:both;"></div>
</div>
@php
$groupedOrders = $shift->actual_orders->groupBy('customer_name');
$totalAmount = 0;
@endphp
<table id="customers-table">
        <thead>
            <tr class="heading">
                <th style="width: 3%;">#</th>
                <th>Date</th>
                <th>A/C Name</th>
                <th>Invoice</th>
                <th style="text-align: right;">Amount</th>
                <th style="text-align: center;">Payment Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groupedOrders as $customerName => $customerOrders)
            <tr >
                <th scope="row">{{ $loop->index + 1 }}</th>
                <td style="width:12%">
                    <ul>
                        @foreach ($customerOrders as $order)
                        <li>{{ $order['invoice_date'] }}</li>
                        @endforeach
                    </ul>
                </td>
                <td>{{ $order['customer_name'] }}</td>
                <td style="width:13%">
                    <ul>
                        @foreach ($customerOrders as $order)
                        <li>{{ $order['invoice_id'] }}</li>
                        @endforeach
                    </ul>
                </td>
                <td style="text-align: right;width:12%">
                    <ul>
                        @foreach ($customerOrders as $order)
                        <li>{{ manageAmountFormat($order['total'], 2) }}</li>
                        <?php $totalAmount += $order['total']; ?>
                        @endforeach
                    </ul>
                </td>
                <td style="text-align: center;width:50%;"> </td>
            </tr>
            @endforeach
            
            <tr>
                <th colspan="4">Grand Total</th>
                <th colspan="2" style="text-align: right;">{{ manageAmountFormat($totalAmount) }}</th>
            </tr>
            
        </tbody>
    </table>
    <p>Goods have been loaded as per the above delivery sheet and we should ensure that customers shall pay for the goods to the company(Kanini Chapchap) till number</p>
    <p>We confirm that we shall be fully liable in case of any loss of money due to omission or negligence.</p>
    <table style="font-size: 12px; margin-bottom: 10px;">
        <tbody>
            <tr style="padding-top: 20px;">
                <td style="text-align: left;">DRIVER: </td>
                <td width="25%" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">ID NO: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">SIGN: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
            </tr>
        </tbody>
    </table>
    <table style="font-size: 12px; margin-bottom: 10px;">
        <tbody>
           
            <tr style="padding-top: 20px;">
                <td style="text-align: left;">TURN BOY: </td>
                <td width="25%" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">ID NO: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">SIGN: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
            </tr>
          
        </tbody>
    </table>
    <table style="font-size: 12px; margin-bottom: 10px;">
        <tbody>
            <tr style="padding-top: 20px;">
                <td style="text-align: left;">SALESMAN: </td>
                <td width="25%" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">ID NO: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">SIGN: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
            </tr>
        </tbody>
    </table>
    
    <br>
    
    <table style="font-size: 12px; margin-bottom: 20px;">
        <tbody>
            <tr style="padding-top: 20px;">
                <td style="text-align: left;">Confirmed By: </td>
            </tr>
        </tbody>
    </table>
    
    <br>
    
    <table style="font-size: 12px;">
        <tbody>
            <tr style="padding-top: 20px;">
                <td style="text-align: left;">ROUTE MANAGER: </td>
                <td width="25%" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">ID NO: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
                <td style="text-align: left;">SIGN: </td>
                <td colspan="3" style="border-bottom: 1px solid #000;"></td>
            </tr>
        </tbody>
    </table>
    

</body>
</html>