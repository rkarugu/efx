<html>
<title>Print</title>

<head>
    <style type="text/css">
        body {
            font-family: arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td, th {
            text-align: left;
            padding: 4px 8px;
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        hr {
            border: 1px solid #000;
            margin: 5px 0;
        }

        .customer-details {
            line-height: 1.4;
            padding: 10px 0;
        }
    </style>
</head>

<body>
    <?php $all_settings = getAllSettings(); ?>

    <table style="width: 100%;">
        <!-- Company Name & Address (centered) -->
        <tr>
            <td colspan="4" class="center">
                <b style="font-size: 18px;">*** TEMPLATE UPDATED *** {!! strtoupper($all_settings['COMPANY_NAME']) !!}</b>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="center">
                <b>INVOICE</b>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="center">
                <b>{!! $all_settings['ADDRESS_1'] !!}, {!! $all_settings['ADDRESS_2'] !!}</b>
            </td>
        </tr>
        <tr>
            <td colspan="4" class="center">
                <b>Mobile: {!! $all_settings['PHONE_NUMBER'] ?? '0740804489' !!}</b>
            </td>
        </tr>
        
        @if(isset($row->print_count) && $row->print_count > 1)
        <tr>
            <td colspan="4" class="center">
                <b>REPRINT INVOICE COUNT: {{$row->print_count - 1}}</b>
            </td>
        </tr>
        @endif
        
        <!-- Horizontal Line -->
        <tr>
            <td colspan="4"><hr></td>
        </tr>
        
        <!-- Customer Details Section -->
        <tr>
            <td colspan="4" style="text-align: left; padding: 10px 0; line-height: 1.4;">
                <b>Invoice No.: {{$row->requisition_no ?? $list->requisition_no}}</b><br>
                <b>Company PIN: {{$all_settings['PIN_NO'] ?? 'Https://testing.com'}}</b><br>
                <b>Customer PIN:</b><br>
                <b>Customer Name: {{$row->customer ?? $list->customer}}</b><br>
                <b>Date: {{date('d/m/Y H:i', strtotime($row->created_at ?? $list->requisition_date))}}</b><br>
                <b>Served By: {{getLoggeduserProfile()->name}}</b><br>
                <b>Salesman name: {{getLoggeduserProfile()->name}}</b><br>
                <b>Customer Account: TEST BUSINESS</b><br>
                <b>Mobile: 0700</b><br>
                <b>B/F: KSh 100.00</b>
            </td>
        </tr>
        
        <!-- Horizontal Line -->
        <tr>
            <td colspan="4"><hr></td>
        </tr>
    </table>
    <!-- Items Table -->
    <table style="width:100%; border-collapse: collapse;">
        <!-- Table Headers -->
        <tr style="border-bottom: 2px solid #000;">
            <td style="font-weight: bold; text-align: left; padding: 8px;"><b>Uom</b></td>
            <td style="font-weight: bold; text-align: left; padding: 8px;"><b>Qty</b></td>
            <td style="font-weight: bold; text-align: left; padding: 8px;"><b>Price</b></td>
            <td style="font-weight: bold; text-align: right; padding: 8px;"><b>Amount</b></td>
        </tr>
        
        <?php 
        $total_amount = [];
        $discount_amount = [];
        $qty = [];
        $totalItems = count($itemsdata ?? []);
        ?>
        
        @foreach ($itemsdata as $index => $item)
        <!-- Item Row -->
        <tr>
            <td style="font-weight: bold; text-align: left; padding: 8px; vertical-align: top;">
                <b>{{strtoupper($item->getInventoryItemDetail->title ?? 'SOLAI MMEAL 12X2KG BALE')}}</b><br>
                <b>Pc(s)</b>
            </td>
            <td style="font-weight: bold; text-align: left; padding: 8px; vertical-align: top;">
                <b>{{number_format($item->quantity ?? 1, 2)}}</b>
            </td>
            <td style="font-weight: bold; text-align: left; padding: 8px; vertical-align: top;">
                <b>x {{number_format($item->selling_price ?? 2000, 2)}}</b>
            </td>
            <td style="font-weight: bold; text-align: right; padding: 8px; vertical-align: top;">
                <b>{{number_format(($item->quantity ?? 1) * ($item->selling_price ?? 2000), 2)}}</b>
            </td>
        </tr>
        
        @if($index < $totalItems - 1)
        <!-- Horizontal Line between items -->
        <tr>
            <td colspan="4" style="padding: 0;"><hr style="border: 1px solid #000; margin: 5px 0;"></td>
        </tr>
        @endif
        
        <?php 
        $original_amount = ($item->quantity ?? 1) * ($item->selling_price ?? 2000);
        $discount_per_item = ($item->discount ?? 0) * ($item->quantity ?? 1);
        
        $total_amount[] = $original_amount;
        $discount_amount[] = $discount_per_item;
        $qty[] = $item->quantity ?? 1;
        ?>
        @endforeach
    </table>

    <hr>

    <!-- Summary Section -->
    <table style="width: 100%; border-collapse: collapse;">
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px; width: 70%;"><b>No of Items</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px; width: 30%;"><b>{{count($itemsdata ?? [])}}</b></td>
        </tr>
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px;"><b>Subtotal: [TEMPLATE UPDATED]</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px;"><b>KSh {{number_format(array_sum($total_amount), 2)}}</b></td>
        </tr>
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px;"><b>Disc</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px;"><b>KSh {{number_format(array_sum($discount_amount ?? []), 2)}} (Debug: {{json_encode($discount_amount ?? [])}})</b></td>
        </tr>
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px;"><b>VAT</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px;"><b>KSh {{number_format(0, 2)}}</b></td>
        </tr>
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px;"><b>TOTAL INVOICE AMNT:</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px;"><b>KSh {{number_format(array_sum($total_amount) - array_sum($discount_amount), 2)}}</b></td>
        </tr>
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px;"><b>CURBET DUE AMOUNT</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px;"><b>KSh {{number_format(array_sum($total_amount) - array_sum($discount_amount), 2)}}</b></td>
        </tr>
        <tr> 
            <td style="text-align: left; font-weight: bold; padding: 8px;"><b>ACCOUNT BALANCE</b></td>
            <td style="text-align: right; font-weight: bold; padding: 8px;"><b>KSh {{number_format(100, 2)}}</b></td>
        </tr>
    </table>

    <hr>

    <!-- CU INFORMATION Section -->
    <div style="width: 100%; text-align: center; margin-top: 20px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; font-size: 16px; padding: 10px;">
                    <b>CU INFORMATION</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px;">
                    <b>Date: {{date('d/m/Y')}} Time: {{date('H:i:s A')}}</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px;">
                    <b>SCU ID:</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px;">
                    <b>DIGITAX ID: sale_01K15DQMBB8RT9JTK9FP1K6J2H</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px;">
                    <b>SCU INVOICE NO: {{$row->requisition_no ?? $list->requisition_no ?? '210'}}</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px;">
                    <b>Internal Data:</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px;">
                    <b>Receipt Signature:</b>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; padding: 20px;">
                    <!-- QR Code placeholder -->
                    <div style="width: 100px; height: 100px; border: 2px solid #000; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                        <b>QR CODE</b>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; font-weight: bold; padding: 10px;">
                    <b>MPESA TILL NO: 166538 NO CASH PAYMENT ON DELIVERY!</b>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
