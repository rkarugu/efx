@php
    $getLoggeduserProfile = getLoggeduserProfile();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cash Sales</title>
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
            margin-bottom: 0px;
            padding-bottom: 0px;
            color: #000;
        }

        body h3 {
            font-weight: 300;
            margin-top: 10px;
            margin-bottom: 20px;
            font-style: italic;
            color: #555;
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
        .invoice-box *{
            font-size: 12px;
        }

        .invoice-box table {
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
    </style>
</head>
<body>

    <div class="invoice-box">
        <table  style="text-align: center;">
            <tbody>
                <tr class="top">
                    <th colspan="3">
                        <h2 style="font-size:18px !important">{{getAllSettings()['COMPANY_NAME']}}</h2>
                    </th>
                </tr>
                <tr class="top">
                    <td colspan="3" style="    text-align: center;">{{getAllSettings()['ADDRESS_2']}}</td>
                </tr>
                <tr class="top">
                    <!-- <th  colspan="1" style="width: 50%">
                        VAT NO:
                    </th> -->
                    <th colspan="1" style="width: 33%;text-align:left">
                        
                    </th>
                    <th colspan="1"  style="width: 33%;text-align:center">CASH SALE NO: {{$data->sales_no}}</th>
                    <th colspan="1" style="width: 33%;text-align:right">DATE: {{date('d-M-Y',strtotime($data->date))}}</th>

                </tr>
                <tr class="top">
                    <th  colspan="3" style="width: 100%;text-align:left"> <b>Customer Name:</b> {{$data->customer}}</th>
                </tr>
                <tr class="top">
                    <th  colspan="3" style="width: 100%;text-align:left"> <b>Customer PIN:</b> {{$data->customer_pin}}</th>
                </tr>
                <tr class="top">
                    <th  colspan="3" style="width: 100%;text-align:left"> <b>Telephone No:</b> {{$data->customer_phone_number}}</th>
                </tr>
                @if ($data->print_count > 1)
                    <tr class="top">
                        <th  colspan="3" style="width: 100%;text-align:center">
                            <div style="display: flex">
                                <div style="width: 50%;text-align:center">
                                    <h2 style="font-size:18px !important">REPRINT {{$data->print_count-1}}</h2>
                                </div>
                                <div style="width: 50%;text-align:center">
                                    <h2 style="font-size:18px !important">REPRINT {{$data->print_count-1}}</h2>
                                </div>
                            </div>    
                        </th>
                    </tr>
                @endif
            </tbody>        
        </table>
        <table>
            <tbody>
                <tr class="heading">
					<td style="width: 40%;">Description</td>
					<td style="width: 7%;">Qty</td>
					<td style="width: 8%;">RTN-Qty</td>
					<td style="width: 9%;">Price</td>
					<td style="width: 10%;">Amount</td>
					<td style="width: 8%;text-align:right;padding-right:2px">Disc</td>
					<td style="width: 8%;text-align:right;padding-right:2px">Vat%</td>
					<td style="width: 10%;">Total</td>
				</tr>
                @php
                    $TONNAGE = $gross_amount = 0;
                @endphp
                @foreach ($data->items as $item)
                    <tr class="item">
                        <td>{{@$item->item->description}}</td>
                        <td>{{((int)$item->qty)}}</td>
                        <td>{{((int)$item->return_quantity)}}</td>
                        <td>{{number_format($item->selling_price, 0)}}</td>
                        <td>{{number_format($item->qty*$item->selling_price, 0)}}</td>
                        <td>{{number_format($item->discount_amount, 0)}}</td>
                        <td>{{number_format($item->vat_percentage, 0)}}</td>
                        <td>{{number_format($item->qty*$item->selling_price, 0)}}</td>
                    </tr>

                    @php
                        $TONNAGE += (($item->item->net_weight ?? 1) * $item->qty);
                        $gross_amount += (($item->qty*$item->selling_price) - $item->discount_amount);
                    @endphp
                @endforeach
                </tbody>
        </table>
        <table>
            <tbody>
                
                <tr >
					<td colspan="5"></td>
				</tr>

                <tr >
					<td colspan="3">{{count($data->items)}} Lines</td>
					<td style="text-align: right;" colspan="1">Gross Amount:</td>
					<td  colspan="1">{{manageAmountFormat($gross_amount)}}</td>
				</tr>
                <tr >
					<td colspan="2" ><div style="display:flex"><span style="width:65%">Prepared by: {{@$data->user->name}}</span> <span style="width:35%;float:right">Time: {{date('H:i A',strtotime($data->time))}}</span></div></td>
					
					<td colspan="1">Delivered By: </td>
					<td style="text-align: right;" colspan="1">Discount:</td>
					<td colspan="1">{{manageAmountFormat($data->items->sum('discount_amount') ?? 0.00)}}</td>
				</tr>
                <tr >
					<td colspan="3"></td>
					<td style="text-align: right;" colspan="1">Net Amount:</td>
					<td  colspan="1">{{manageAmountFormat($gross_amount - ($data->items->sum('vat_amount') ?? 0.00))}}</td>
				</tr>
                <tr >
					<td colspan="3"></td>
					<td style="text-align: right;" colspan="1">V.A.T:</td>
					<td  colspan="1">{{manageAmountFormat($data->items->sum('vat_amount') ?? 0.00)}}</td>
				</tr>
                <tr >
                    <td colspan="1">Received By: </td>
					<td colspan="1">Sign: </td>
					<td colspan="2">TONNAGE : {{manageAmountFormat($TONNAGE)}}</td>
					<td colspan="1" style="text-align: center;">
                        <hr style="border: 1px dashed #7b7b7b;">
                    </td>
				</tr>
                <tr >
					<td colspan="1"></td>
					<td colspan="1">RUBBER STAMP</td>
					<td colspan="1"></td>
					<td colspan="1" style="text-align: right;" >Total:  </td>
					<td colspan="1">
                        {{manageAmountFormat($gross_amount)}}
                        <hr style="border: 1px dashed #979797;">
                    </td>
				</tr>
                <tr >
					<td colspan="3">Amount in Words
                        <br>
                        {{strtoupper(getCurrencyInWords($gross_amount))}}
                    </td>
					<td colspan="1">A/C Balance : 0.00</td>
					<td colspan="1">Change: {{$data->change}}</td>
				</tr>
                <tr >
                    <td colspan="5"></td>
                </tr>
                <tr >
                    <td colspan="5"></td>
                </tr>
                <tr >
                    <td colspan="5"></td>
                </tr>
                @if ($getLoggeduserProfile->upload_data == 0)
                    <tr >
                        <td colspan="5" style="text-align:center">{{($data->upload_data)}}</td>					
                    </tr>                    
                @endif
            </tbody>
        </table>


        @if(!empty($esd_details))

            <div style="width:100%; padding: 10px; text-align:left;"> 
                <div style="width:20%;  float: left;">
                    @if($esd_details->verify_url!="")

                        @if(isset($is_print))
                            {{ QrCode::size(70)->generate($esd_details->verify_url) }}
                        @else
                            <img src="data:image/png;base64, {!! base64_encode(QrCode::size(70)->generate($esd_details->verify_url)) !!} ">
                        @endif

                       
                        
                    @endif
                </div>
                <div style="width:80%; text-align:left;  float: left;">
                    CU Serial No : {{ $esd_details->cu_serial_number }}<br>
                    <p> CU Invoice Number : {{ $esd_details->cu_invoice_number }} </p>
                </div>
            </div>
        @endif
    </div>   
</body>
</html>