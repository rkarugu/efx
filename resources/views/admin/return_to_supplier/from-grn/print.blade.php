<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $return['return_no'] }}</title>

    <style>
        .header p,
        .order-info p,
        tbody p {
            margin: 0
        }

        .order-info {
            display: flex;
            flex-direction: row
        }

        .shipping-info table,
        .shipping-info th,
        .shipping-info td {
            border: 1px solid black;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table {
            cellpa
        }

        th, td {
            padding: 5px;
            font-size: 12px
        }

        th {
            text-align: left;
            font-weight: bold;
        }

        th.text-right, td.text-right {
            text-align: right
        }

        .item-info {
            margin-top: 10px
        }
        
        .item-info tr {
            border-bottom: 1px solid black
        }
        
        .signature-section > p {
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    @php
        $settings = getAllSettings();
    @endphp
    <div class="header" style="text-align: center">
        <p>RETURN FROM GRN</p>
        <p>{{ $settings['COMPANY_NAME'] ?? '' }}</p>
        <p>{{ trim(($settings['ADDRESS_2'] ?? '') . ' ' . ($settings['ADDRESS_3'] ?? '')) }}</p>
        <p>Tel: {{ $settings['PHONE_NUMBER'] ?? '' }} Fax: {{ $settings['FAX_NUMBER'] ?? 'N/A' }}</p>
        <p>{{ $settings['EMAILS'] ?? '' }}</p>
    </div>

    <hr>

    <div class="order-info">
        <table>
            <tbody>
                <tr>
                    <td><strong>Date Created:</strong> {{ date_format(date_create($return['created_at']), 'd/m/Y') }}</td>
                    <td class="text-right"><strong>Delivery Date:</strong> {{ date_format(date_create($return['grn']->delivery_data), 'd/m/Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Return No:</strong> {{ $return['return_no'] }}</td>
                    <td class="text-right"><strong>Supplier Invoice No.</strong> {{ $return['grn']->supplier_invoice_no }}</td>
                </tr>                
                <tr>
                    <td><strong>Date Placed:</strong> {{ date_format(date_create($return['approved_date']), 'd/m/Y') }}</td>
                    <td class="text-right"><strong>CU Invoice No.</strong> {{ $return['grn']->cu_invoice_number }} </td>
                </tr>
                <tr>
                    <td><strong>Placed By:</strong> <span style="text-transform: uppercase">{{ $return['user']->name }}</span></td>
                    <td class="text-right"><strong>LPO No.</strong> {{ $return['grn']->purchaseOrder->purchase_no }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="text-right"><strong>GRN No.</strong> {{ $return['grn']->grn_number }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="text-right"><strong>Delivery Note</strong> {{ $return['grn']->purchaseOrder->receive_note_doc_no }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px">
        <div class="shipping-info">
            <table style="width: 100%">
                <thead>
                    <tr>
                        <th>FROM</th>
                        <th>SHIP TO</th>
                    </tr>
                </thead>
                <tbody style="vertical-align: baseline">
                    <tr>
                        <td>
                            <p>{{ $settings['COMPANY_NAME'] ?? '' }}</p>
                            <p>{{ $return['store_location'] }}</p>
                        </td>
                        <td>
                            <p>{{ $return['supplier']->name }}</p>
                            <p>{{ $return['supplier']->address ?? 'N/A' }}</p>
                            <p>{{ $return['supplier']->telephone ?? 'N/A' }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="item-info">
            <table>
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Weight (Kg)</th>
                        <th>Cost</th>
                        <th>Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalCost = 0;
                    @endphp
                    
                    @foreach ($return['returns'] as $returnItem)
                        <tr>
                            <td>{{ $returnItem->inventoryItem->stock_id_code }}</td>
                            <td>{{ $returnItem->inventoryItem->description }}</td>
                            <td>{{ $returnItem->returned_quantity }}</td>
                            <td>{{ $returnItem->inventoryItem->net_weight }}</td>
                            <td>{{ number_format($returnItem->grn->invoice_info->order_price, 2) }}</td>
                            <td style="text-align: right">{{ number_format((float)$returnItem->grn->invoice_info->order_price * (float)$returnItem->returned_quantity, 2) }}</td>
                        </tr>

                        @php
                            $totalCost += (float)$returnItem->grn->invoice_info->order_price * (float)$returnItem->returned_quantity;
                        @endphp
                    @endforeach
                    <tr></tr>
                    <tr>
                        <th colspan="3" style="text-align: right">Totals</th>
                        {{-- <td>{{ number_format(123456789, 2) }} (Kg)</td> --}}
                        <td>{{ number_format($return['returns']->sum('inventoryItem.net_weight'), 2) }} (Kg)</td>
                        <td></td>
                        <td style="text-align: right">KES {{ number_format($totalCost, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <p>Total Items: {{ $return['returns']->count() }}</p>
        </div>

        <div style="margin-bottom: 30px">
            <p>REMARKS</p>
            <P style="border-bottom: 1px solid black; padding-bottom: 20px"></P>
        </div>

        <div class="signature-section">
            <p>PREPARED BY:</p>
            <table style="table-layout: fixed">
                <tr style="text-align: left">
                    <td style="width:10%;">NAME:</td>
                    <td style="border-bottom:2px dashed #848484; width:30%;">{{ $return['user']->name }}</td>
                    <td style="width:5%;">SIGN: </td>
                    <td style="border-bottom:2px dashed #848484; width:25%;"></td>
                    <td style="width:5%;">DATE: </td>
                    <td style="border-bottom:2px dashed #848484; width:25%; padding-left:15px">{{ date_format(date_create($return['created_at']), 'd/m/Y') }}</td>
                </tr>
            </table>

            <p>APPROVED BY:</p>
            <table style="table-layout: fixed">
                <tr style="text-align: left">
                    <td style="width:10%;">NAME:</td>
                    <td style="border-bottom:2px dashed #848484; width:30%;">{{ $return['approvedBy']->name }}</td>
                    <td style="width:5%;">SIGN: </td>
                    <td style="border-bottom:2px dashed #848484; width:25%;"></td>
                    <td style="width:5%;">DATE: </td>
                    <td style="border-bottom:2px dashed #848484; width:25%; padding-left:15px">{{ date_format(date_create($return['approved_date']), 'd/m/Y') }}</td>
                </tr>
            </table>

            <p>AUTHORISED BY:</p>
            <table style="table-layout: fixed">
                <tr style="text-align: left">
                    <td style="width:10%;">NAME:</td>
                    <td style="border-bottom:2px dashed #848484; width:30%;"></td>
                    <td style="width:5%;">SIGN: </td>
                    <td style="border-bottom:2px dashed #848484; width:25%;"></td>
                    <td style="width:5%;">DATE: </td>
                    <td style="border-bottom:2px dashed #848484; width:25%;"></td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>