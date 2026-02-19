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
            font-size: 13px;
            line-height: 20px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }
        td{
            font-size: 12px !important;
        }
        th{
            font-size:14px !important;
        }

        .invoice-box * {
            font-size: 13px;
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
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            border-bottom: 1px solid #ddd;
            /* font-weight: bold; */
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
            font-size: 14px;
            font-weight: 400;
            text-align: left;
            color: #555;
        }

        .bordered-div span {
            display: block;
            margin-bottom: 5px;
        }

        #customers-table {
            width: 100%;
        }

        #customers-table tr.heading {
            font-weight: bold;
            text-align: left;
            color: #555;
        }
        td{
            text-align: left;
            color: #555;
        }
        

        .bordered-table td, .bordered-table th {
        border: 1px solid #ddd;
        padding: 8px;
        }

        .bordered-table tr:nth-child(even){background-color: #f2f2f2;}

        .bordered-table tr:hover {background-color: #ddd;}

        
    </style>
</head>
@foreach ($bins as $bin)
<body>
<div class="invoice-box">
    <table style="text-align: right;">
        <tbody>
        <tr class="top">
            <th colspan="1" style="font-size:18px !important;font-weight: bold; text-align:left !important;">
                {{ $settings['COMPANY_NAME'] }}
            </th>
            <th colspan="1" style="font-size:10px !important; text-align:right !important;">
                <p>Printed On: {{$now}}</p>
            </th>
        </tr>
        <tr class="top">
            <th colspan="1" style="font-size:16px !important; text-align:left !important;">
                {{$branch}}
            </th>
            <th colspan="1" style="font-size:16px !important; text-align:right !important;">
                <p>
                    @if ($shift->loading_sheet_print_count == 1)
                    @else
                    Re-print {{$shift->loading_sheet_print_count - 1}}
                        
                    @endif
                </p>
            </th>
            
        </tr>

        <tr class="top">
            <th colspan="1" style="font-size:15px !important; text-align:left !important;">
                LOADING SHEET
            </th>
            <th colspan="1" style="font-size:15px !important; text-align:right !important; color:black !important">
                {{ $shift->date }}
            </th>
        </tr>
        </tbody>
    </table>
</div>

<div class="bordered-div">
    <div style="float: left;">
        <span> Delivery No: {{ $schedule?->delivery_number ?? '' }} </span>
        <span> Route: {{ $shift->relatedRoute->route_name }} </span>
        <span> Salesman: {{ $shift->salesman->name }} </span>

    </div>

    <div style="float: left; margin-left: 30px;">
        <span> Vehicle: {{ $schedule?->vehicle?->license_plate_number }} </span>
        <span> Driver: {{ $schedule?->driver?->name }} </span>
    </div>

    <div style="float: left; margin-left: 30px;">
        <span> Dispatcher: - </span>
        <span> Total Weight: {{ $shift->shift_tonnage }}T </span>
    </div>

    <div style="clear:both;"></div>
</div>

<div class="bordered-div">
    <h3 style="margin: 0 0 10px 0!important; font-weight: 500; color: #555;"> INVOICES </h3>
    <span> {{ $shift?->invoices }} </span>
</div>
<h5 style="text-align: left;">{{$bin->title}}<h5>
    @if ($bin->is_display == '1')
    <table id="customers-table" style="padding-top: 1px !important; margin-top:0 !important;"  class="bordered-table">
        <thead>
        <tr class="heading">
            <th style="width: 3%; text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important;">#</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> STOCK ID</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> TITLE</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> QTY</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> TONS</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> CTNS</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> DZNS</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> OUTS</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> PCS/PKTS</th>
        </tr>
        </thead>
    
        <tbody>
            <?php $rowNumber = 0;
            $totalQuantity = 0;
            $totalTonnage = 0;
            $totalCtns = 0;
            $totalDzns = 0;
            $totalOuts = 0;
            $totalPcs = 0;
             ?>
    
        @foreach($newdata as $index => $order) 
        @if ($bin->id == $order['bin'])
        <tr>
            <th scope="row" style="width: 3%; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>{{ ++$rowNumber }}.</span> </th>
            <td style="width:15%; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>{{ $order['stock_id'] }}</span></td>
            <td style="width:60%; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>{{ $order['title'] }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important; font-size:16px !important; font-weight:bold; font-family:'Times New Roman', Times, serif"> <span>{{ $order['quantity'] }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"><span> {{ $order['tonnage'] }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important; font-size:16px !important; font-weight:bold; font-family:'Times New Roman', Times, serif "> <span>{{ $order['CTNS'] ?? '-'}}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important; font-size:16px !important; font-weight:bold; font-family:'Times New Roman', Times, serif" ><span> {{ $order['DZNS'] ??  '-' }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important; font-size:16px !important; font-weight:bold; font-family:'Times New Roman', Times, serif"> <span>{{ $order['OUTERS'] ?? '-'}}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important; font-size:16px !important; font-weight:bold; font-family:'Times New Roman', Times, serif"><span> {{ $order['PCS'] ?? '-'}} </span></td>
        </tr>
        <?php
        $totalQuantity += $order['quantity'];
        $totalTonnage += $order['tonnage'];
        $totalCtns += $order['CTNS'];
        $totalDzns += $order['DZNS'];
        $totalOuts += $order['OUTERS'];
        $totalPcs += $order['PCS'];

        
        ?>
            
        @endif
      
        @endforeach
        <tr>
            <th colspan="3" style=" border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>TOTAL</span> </th>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> <span>{{ $totalQuantity }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"><span> {{ $totalTonnage }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> <span>{{ $totalCtns }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"><span> {{ $totalDzns }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> <span>{{ $totalOuts }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"><span> {{ $totalPcs }}</span></td>
    
        </tr>
        </tbody>
    </table>












        
    @else
    <table id="customers-table" style="padding-top: 1px !important; margin-top:0 !important;"  class="bordered-table">
        <thead>
        <tr class="heading">
            <th style="width: 3%; text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important;">#</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> STOCK ID</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> TITLE</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> QTY</th>
            <th style="text-align: left !important; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> TONNAGE</th>
        </tr>
        </thead>
    
        <tbody>
            <?php $rowNumber = 0;
            $totalQuantity = 0;
            $totalTonnage = 0;
             ?>
    
        @foreach($data as $index => $order) 
        @if ($bin->id == $order['bin'])
        <tr>
            <th scope="row" style="width: 3%; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>{{ ++$rowNumber }}.</span> </th>
            <td style="width:15%; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>{{ $order['stock_id'] }}</span></td>
            <td style="width:60%; border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>{{ $order['title'] }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important; font-size:16px !important; font-weight:bold;  font-family:'Times New Roman', Times, serif"> <span>{{ $order['quantity'] }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"><span> {{ $order['tonnage'] }}</span></td>
        </tr>
        <?php
        $totalQuantity += $order['quantity'];
        $totalTonnage += $order['tonnage'];
        
        ?>
            
        @endif
      
        @endforeach
        <tr>
            <th colspan="3" style=" border-top: 2px solid black !important; border-bottom: 2px solid black !important;"> <span>TOTAL</span> </th>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"> <span>{{ $totalQuantity }}</span></td>
            <td style="width:10%; border-top: 2px solid black !important; border-bottom: 2px solid black !important; border-right: 2px solid black !important;  border-left: 2px solid black !important;"><span> {{ $totalTonnage }}</span></td>
    
        </tr>
        </tbody>
    </table>
    @endif

    
{{-- @endforeach --}}
</body>
@endforeach

</html>
