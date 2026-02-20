<html>

<head>
    <title>Payment Voucher</title>
    <style type="text/css">
        body {
            font-family: 'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-weight: 400;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            line-height: 1.6;
        }

        h1 {
            font-size: 18px
        }

        h2 {
            font-size: 16px
        }

        h3 {
            font-size: 14px
        }

        h4 {
            font-size: 12px
        }

        h5 {
            font-size: 10px
        }

        a {
            color: #06f;
        }

        /* Base styles */
        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 10px;
            background-color: transparent;
            border-collapse: collapse;
        }

        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
            line-height: 1.42857143;
            vertical-align: top;
            border-top: 1px solid #aaa;
            text-align: left;
        }

        /* Table header */
        .table>thead>tr>th {
            vertical-align: bottom;
            border-bottom: 2px solid #aaa;
            text-align: left;
        }

        /* Striped tables */
        .table-striped>tbody>tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        /* Bordered tables */
        .table-bordered {
            border: 1px solid #aaa;
        }

        .table-bordered>thead>tr>th,
        .table-bordered>tbody>tr>th,
        .table-bordered>tfoot>tr>th,
        .table-bordered>thead>tr>td,
        .table-bordered>tbody>tr>td,
        .table-bordered>tfoot>tr>td {
            border: 1px solid #aaa;
        }

        /* Condensed tables */
        .table-condensed>thead>tr>th,
        .table-condensed>tbody>tr>th,
        .table-condensed>tfoot>tr>th,
        .table-condensed>thead>tr>td,
        .table-condensed>tbody>tr>td,
        .table-condensed>tfoot>tr>td {
            padding: 5px;
        }

        .table.no-border,
        .table.no-border td,
        .table.no-border th {
            border: 0;
            /* padding: 5px 0 0 */
        }

        /* Margin utility classes */
        .m-0 {
            margin: 0 !important;
        }

        .mt-0 {
            margin-top: 0 !important;
        }

        .mr-0 {
            margin-right: 0 !important;
        }

        .mb-0 {
            margin-bottom: 0 !important;
        }

        .ml-0 {
            margin-left: 0 !important;
        }

        .mx-0 {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .my-0 {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }

        /* Padding utility classes */
        .p-0 {
            padding: 0 !important;
        }

        .pt-0 {
            padding-top: 0 !important;
        }

        .pr-0 {
            padding-right: 0 !important;
        }

        .pb-0 {
            padding-bottom: 0 !important;
        }

        .pl-0 {
            padding-left: 0 !important;
        }

        .px-0 {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .py-0 {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        .table.items>thead>tr>th {
            font-size: 9px;
            text-transform: uppercase
        }

        .table.items>tbody>tr>th,
        .table.items>tbody>tr>td {
            padding: 3px;
            font-size: 11px
        }

        .text-center {
            text-align: center !important;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .underline {
            border-bottom: 1px solid #aaa !important;
        }

        .stamp {
            margin-top: 35px;
        }

        .stamp img {
            width: 230px;
        }
    </style>
</head>

<body>
    <table class="table no-border mb-0 mt-0">
        <tr>
            <td colspan="2">
                <h1>{{ $settings['COMPANY_NAME'] }}</h1>
            </td>
            <th rowspan="2">
                <img src="{{ asset('uploads/restaurants/' . $branch->image) }}" class="img-circle" alt=""
                    style="float:right;width: 115px; margin-bottom: 23px;">
            </th>
        </tr>
        <tr>
            <td>
                {{ $settings['ADDRESS_1'] }}<br>
                {{ $settings['ADDRESS_2'] }}<br>
                {{ $settings['ADDRESS_3'] }}<br>
                Tel: {{ $settings['PHONE_NUMBER'] }}<br>
                {{ $settings['EMAILS'] }}<br>
                {{ $settings['WEBSITE'] }}<br>
                Pin No: {{ $settings['PIN_NO'] }}<br>
            </td>
            <th class="text-center" style="width: 30%;">
                <img src="data:image/png;base64,{{ base64_encode($qr_code) }}" alt="QR Code">
            </th>
        </tr>
    </table>
    <h3 style="text-align:center;border-bottom:2px solid #aaa; padding-bottom:5px; position:relative">
        PAYMENT VOUCHER
        <span style="position: absolute; right:0; font-size:13px">Date:
            {{ $voucher->created_at->format('d/m/Y H:i') }}</span>
    </h3>
    <table class="table no-border">
        <tr>
            <th class="text-left">
                <span style="margin-right: 10px"> {{ $voucher->supplier->supplier_code }} </span>
                <span style="margin-right: 10px">{{ $voucher->supplier->name }}</span>
            </th>
            <th class="text-right">
                <span>VOUCHER NO : {{ $voucher->number }}</span> <br>
            </th>
        </tr>
    </table>
    <table class="table table-bordered items" style="width:70%">
        <tr>
            <th class="text-left">Account</th>
            <td>{{ $voucher->account->account_number }}</td>
            <td>{{ $voucher->account->account_name }}</td>
        </tr>
        <tr>
            <th class="text-left">Details</th>
            <th class="text-left">Date</th>
            <th class="text-right">Amount</th>
        </tr>
        @foreach ($voucher->cheques as $cheque)
            <tr>
                <td>{{ $cheque->number }}</td>
                <td>{{ $cheque->created_at->format('d/m/Y') }}</td>
                <td class="text-right">{{ manageAmountFormat($cheque->amount) }}</td>
            </tr>
        @endforeach
        <tfoot>
            <tr>
                <td colspan="2"></td>
                <th class="text-right">{{ manageAmountFormat($voucher->cheques->sum('amount')) }}</th>
            </tr>
        </tfoot>
    </table>
    <table class="table table-bordered items">
        <thead>
            <tr>
                <th>Date</th>
                <th>Ref</th>
                <th>Memo</th>
                <th>CU Invoice No.</th>
                <th>LPO No</th>
                <th>GRN No</th>
                <th>Amount</th>
                <th>W/Hold Tax</th>
                <th class="text-right">Paid</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = $withHoldingTotal = $paidTototal =0; @endphp
            @foreach ($voucher->voucherItems as $item)
                @php
                    if ($item->payable->notes) {
                        $noteAmountTotal = 0;
                        $noteAmountWithholding = 0;
                        $notePaidTototal = 0;
                        foreach ($item->payable->notes as $note) {
                            $noteAmountTotal += $note->type == 'CREDIT' ? '-' . $note->amount : $note->amount;
                            $noteAmountWithholding +=
                                $note->type == 'CREDIT' ? '-' . $note->withholding_amount : $note->withholding_amount;
                            $notePaidTototal += $noteAmountTotal - $noteAmountWithholding;
                        }
                        $totalAmount += $item->payable->total_amount_inc_vat + $noteAmountTotal;
                        $withHoldingTotal += $item->payable->withholding_amount + $noteAmountWithholding;
                        $paidTototal +=
                            $item->payable->total_amount_inc_vat -
                            $item->payable->withholding_amount +
                            $noteAmountTotal;
                    } elseif ($item->payable_type == 'advance' || $item->payable_type == 'bill') {
                        $totalAmount += $item->payable->amount;
                        $withHoldingTotal += $item->payable->withholding_amount;
                        $paidTototal += $item->payable->amount - $item->payable->withholding_amount;
                    } else {
                        $totalAmount += $item->payable->total_amount_inc_vat;
                        $withHoldingTotal += $item->payable->withholding_amount;
                        $paidTototal += $item->payable->total_amount_inc_vat - $item->payable->withholding_amount;
                    }
                @endphp
                @if ($item->payable_type == 'advance')
                    <tr>
                        <td>{{ $item->payable->created_at->format('Y-m-d') }}</td>
                        <td>{{ $item->payable->id }}</td>
                        <td>ADVANCE</td>
                        <td></td>
                        <td>{{ $item->payable->purchaseOrder?->purchase_no }}</td>
                        <td></td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->amount) }}
                        </td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->withholding_amount) }}
                        </td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->amount - $item->payable->withholding_amount) }}
                        </td>
                    </tr>
                @elseif($item->payable_type == 'bill')
                    <tr>
                        <td>{{ $item->payable->created_at?->format('Y-m-d') }}</td>
                        <td>{{ $item->payable->supplier_invoice_number }}</td>
                        <td>BILL</td>
                        <td>{{ $item->payable->cu_invoice_number }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->amount) }}
                        </td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->withholding_amount) }}
                        </td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->amount - $item->payable->withholding_amount) }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $item->payable->trans_date->format('Y-m-d') }}</td>
                        <td>{{ $item->payable->suppreference }}</td>
                        <td>INVOICE</td>
                        <td>{{ $item->payable->cu_invoice_number }}</td>
                        <td>{{ $item->payable->purchaseOrder?->purchase_no }}</td>
                        <td>{{ $item->payable->invoice?->grn_number }}</td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->total_amount_inc_vat) }}
                        </td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->withholding_amount + $item->professional_withholding) }}
                        </td>
                        <td class="text-right">
                            {{ manageAmountFormat($item->payable->total_amount_inc_vat - $item->payable->withholding_amount) }}
                        </td>
                    </tr>
                    @if ($item->payable->notes)
                        @foreach ($item->payable->notes as $note)
                            <tr>
                                <td>{{ $note->note_date }}</td>
                                <td>{{ $note->supplier_invoice_number }}</td>
                                <td>{{ $type = $note->type }}</td>
                                <td>{{ $note->cu_invoice_number }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-right">
                                    {{ manageAmountFormat($type == 'CREDIT' ? '-' . $note->amount : $note->amount) }}
                                </td>
                                <td class="text-right">
                                    {{ manageAmountFormat($type == 'CREDIT' ? '-' . $note->withholding_amount : $note->withholding_amount) }}
                                </td>
                                <td class="text-right">
                                    {{ manageAmountFormat($type == 'CREDIT' ? '-' . ($note->amount - $note->withholding_amount) : $note->amount - $note->withholding_amount) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">Total</th>
                <th class="text-right">
                    {{ manageAmountFormat($totalAmount) }}
                </th>
                <th class="text-right">{{ manageAmountFormat($withHoldingTotal) }}</th>
                <th class="text-right">{{ manageAmountFormat($totalAmount - $withHoldingTotal) }}</th>
            </tr>
        </tfoot>
    </table>
    {{-- @if ($item->payable_type == 'advance')
        @foreach ($voucher->voucherItems as $item)
            @foreach ($item->payable->purchaseOrder->getRelatedItem as $item)
                <table class="table no-border" style="margin-top: 25px">
                    <tr style="border-top: 1px dashed #111; border-bottom: 1px dashed #111">
                        <th class="text-left">Date: {{ $item->created_at->format('d/m/Y') }}</th>
                        <th class="text-left">Item: {{ $item->inventoryItem->title }}</th>
                        <th>Qty: {{ number_format($item->quantity) }}</th>
                        <th>Price: {{ number_format($item->order_price) }}</th>
                        <th>Total: {{ manageAmountFormat($item->total_cost_with_vat) }}</th>
                    </tr>
                </table>
            @endforeach
        @endforeach
    @endif --}}
    <table class="table no-border" style="margin-top: 50px">
        <tbody>
            <tr>
                <th width="15%" class="text-left">Prepared By:</th>
                <td width="30%" class="underline"><strong>{{ strtoupper($voucher->preparedBy?->name) }}</strong>
                </td>
                <th width="5%" class="text-right">Date:</th>
                <td class="underline">{{ $voucher->created_at->format('d/m/Y') }}</td>
                <th width="5%" class="text-right">Sign:</th>
                <td colspan="3" class="underline">&nbsp;</td>
            </tr>
            <tr>
                <th width="15%" class="text-left">Approved By:</th>
                <td width="30%" class="underline">
                    @if ($voucher->isProcessed())
                        <strong>{{ strtoupper($voucher->bankFileItem->bankFile->preparedBy?->name) }}
                    @endif
                </td>
                <th width="5%" class="text-right">Date:</th>
                <td class="underline">
                    @if ($voucher->isProcessed())
                        {{ $voucher->bankFileItem->created_at->format('d/m/Y') }}
                    @endif
                </td>
                <th width="5%" class="text-right">Sign:</th>
                <td colspan="3" class="underline">&nbsp;</td>
            </tr>
            <tr>
                <th width="15%" class="text-left">Collected By:</th>
                <td width="30%" class="underline"></td>
                <th width="5%" class="text-right">Date:</th>
                <td class="underline"></td>
                <th width="5%" class="text-right">Sign:</th>
                <td class="underline">&nbsp;</td>
                <th class="text-right" width="3%">ID: </th>
                <td class="underline">&nbsp;</td>
            </tr>
        </tbody>
    </table>
    @if ($voucher->isProcessed())
        <div class="stamp">
            <img src="data:image/jpeg;base64,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"
                alt="kanini stamp and signature">
        </div>
    @endif
</body>

</html>
