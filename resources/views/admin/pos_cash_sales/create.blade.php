@extends('layouts.admin.admin')
@section('content')
    <style>
        /* ── POS Modern Layout ───────────────────────────── */
        .pos-wrap {
            padding: 12px 16px;
        }
        /* Top action bar */
        .pos-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 12px;
        }
        .pos-topbar-left { display: flex; align-items: center; gap: 10px; }
        .pos-topbar-right { display: flex; align-items: center; gap: 10px; }

        /* Customer + total card */
        .pos-customer-card {
            background: var(--dark-surface, #1a1d27);
            border: 1px solid var(--dark-border, #2d3148);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        .pos-customer-card .customer-field {
            flex: 1 1 320px;
            min-width: 0;
        }
        .pos-customer-card .customer-field label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--text-muted, #5c6290);
            margin-bottom: 6px;
            display: block;
        }
        .pos-customer-card .customer-select-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .pos-customer-card .customer-select-row select {
            flex: 1;
        }
        .pos-total-display {
            flex: 0 0 auto;
            text-align: right;
            white-space: nowrap;
        }
        .pos-total-display .total-label {
            font-size: 12px;
            color: var(--text-muted, #5c6290);
            text-transform: uppercase;
            letter-spacing: .8px;
            display: block;
            margin-bottom: 2px;
        }
        .pos-total-display .total-value {
            font-size: 38px;
            font-weight: 700;
            color: #00d4aa;
            line-height: 1;
        }

        /* Items card */
        .pos-items-card {
            background: var(--dark-surface, #1a1d27);
            border: 1px solid var(--dark-border, #2d3148);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .pos-items-card .pos-table-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--dark-border, #2d3148);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pos-items-card .pos-table-header h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary, #e8eaf6);
            letter-spacing: .3px;
        }

        /* Table */
        #mainItemTable {
            margin: 0 !important;
            border: none !important;
        }
        #mainItemTable thead tr th {
            background: var(--dark-surface2, #1f2235) !important;
            color: var(--text-muted, #5c6290) !important;
            font-size: 10px !important;
            font-weight: 700 !important;
            letter-spacing: .9px !important;
            text-transform: uppercase !important;
            border-color: var(--dark-border, #2d3148) !important;
            padding: 10px 10px !important;
            white-space: nowrap !important;
        }
        #mainItemTable thead tr th .hint {
            color: #00d4aa;
            font-weight: 600;
            text-transform: none;
            letter-spacing: 0;
        }
        #mainItemTable tbody tr td {
            border-color: var(--dark-border, #2d3148) !important;
            vertical-align: middle !important;
            padding: 8px 8px !important;
            background: transparent !important;
        }
        #mainItemTable tbody tr:hover td {
            background: rgba(0,212,170,0.04) !important;
        }
        #mainItemTable tfoot tr th,
        #mainItemTable tfoot tr td {
            border-color: var(--dark-border, #2d3148) !important;
            background: var(--dark-surface2, #1f2235) !important;
        }

        /* Summary rows in tfoot */
        .pos-summary-row th {
            color: var(--text-secondary, #9ca3c8) !important;
            font-weight: 500 !important;
            font-size: 12px !important;
        }
        .pos-summary-row td {
            color: var(--text-primary, #e8eaf6) !important;
            font-weight: 600 !important;
            font-size: 13px !important;
        }
        .pos-summary-row.total-row th,
        .pos-summary-row.total-row td {
            color: #00d4aa !important;
            font-size: 14px !important;
            font-weight: 700 !important;
        }

        /* Add row FAB */
        .pos-fab {
            position: fixed;
            bottom: 30%;
            left: 4%;
            width: 40px;
            height: 40px;
            border-radius: 50% !important;
            background: #00d4aa !important;
            border-color: #00d4aa !important;
            color: #000 !important;
            font-size: 18px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 16px rgba(0,212,170,0.4) !important;
            transition: transform .15s ease, box-shadow .15s ease !important;
            z-index: 900;
            padding: 0 !important;
        }
        .pos-fab:hover {
            transform: scale(1.1) !important;
            box-shadow: 0 6px 22px rgba(0,212,170,0.55) !important;
        }

        /* Continue to payment footer */
        .pos-footer {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-top: 1px solid var(--dark-border, #2d3148);
        }
        .btn-pos-pay {
            background: #00d4aa !important;
            border-color: #00d4aa !important;
            color: #000 !important;
            font-weight: 700 !important;
            padding: 10px 28px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            letter-spacing: .3px !important;
            transition: background .18s ease, transform .15s ease !important;
        }
        .btn-pos-pay:hover {
            background: #00b894 !important;
            border-color: #00b894 !important;
            transform: translateY(-1px) !important;
        }
        .btn-pos-pay:disabled {
            opacity: .5 !important;
            pointer-events: none !important;
        }

        /* Search autocomplete dropdown */
        .textData {
            width: 100%;
            position: relative;
            z-index: 99;
        }
        .textData table tr:hover, .SelectedLi {
            background: #00d4aa !important;
            color: #000 !important;
            cursor: pointer !important;
        }

        /* Payment modal */
        #modelId .modal-dialog { max-width: 520px; }
        .pay-method-table th {
            background: var(--dark-surface2, #1f2235) !important;
            color: var(--text-muted, #5c6290) !important;
            font-size: 11px !important;
            text-transform: uppercase !important;
            letter-spacing: .7px !important;
        }
        .pay-summary-due td   { font-size: 14px; font-weight: 600; color: var(--text-primary, #e8eaf6) !important; }
        .pay-summary-bal td   {
            font-size: 20px !important;
            font-weight: 700 !important;
            background: #00d4aa !important;
            color: #000 !important;
        }

        .total_total_sales { font-size: 16px; font-weight: 700; }
    </style>

    <form id="orderForm" method="POST" action="{{ route($model.'.store') }}" accept-charset="UTF-8" onsubmit="return false;" enctype="multipart/form-data">
        {{ csrf_field() }}
        <?php
            $getLoggeduserProfile = getLoggeduserProfile();
            $purchase_date = date('d-M-Y');
            $purchase_time = date('H:i:s');
        ?>

        <div class="pos-wrap">

            {{-- ── Top action bar ── --}}
            <div class="pos-topbar">
                <div class="pos-topbar-left">
                    <a href="{{ route($model.'.index') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                    <span style="color:var(--text-muted,#5c6290);font-size:12px;">{!! $title !!}</span>
                </div>
                <div class="pos-topbar-right">
                    <x-drop-component/>
                </div>
            </div>

            @include('message')

            {{-- ── Customer + Total card ── --}}
            <div class="pos-customer-card">
                <div class="customer-field">
                    <label>Customer</label>
                    <div class="customer-select-row">
                        <select name="route_customer" id="route_customer" class="route_customer"></select>
                        <button type="button" class="btn btn-primary btn-sm" onclick="load_customer()" title="Load Customer">
                            <i class="fa fa-address-book"></i>
                        </button>
                    </div>
                </div>
                <div class="pos-total-display">
                    <span class="total-label">Order Total</span>
                    <span class="total-value" id="top_total">0.00</span>
                </div>
            </div>

            {{-- ── Items table card ── --}}
            <div class="pos-items-card">
                <div class="pos-table-header">
                    <h4><i class="fa fa-list" style="color:#00d4aa;margin-right:8px;"></i> Order Items</h4>
                    <button type="button" class="btn btn-primary btn-sm addNewrow">
                        <i class="fa fa-plus"></i> Add Item
                    </button>
                </div>

                <div id="requisitionitemtable" name="item_id[0]" style="overflow-x:auto;">
                    <table class="table table-hover" id="mainItemTable" style="min-width:900px;">
                        <thead>
                        <tr>
                            <th>Selection <span class="hint">(min 3 chars)</span></th>
                            <th>Image</th>
                            <th>Description</th>
                            <th style="width:80px;">Bal Stock</th>
                            <th style="width:70px;">Unit</th>
                            <th style="width:80px;">QTY</th>
                            <th style="width:110px;">Selling Price</th>
                            <th style="width:110px;">VAT Type</th>
                            <th style="width:70px;">Disc%</th>
                            <th style="width:90px;">Discount</th>
                            <th style="width:70px;">VAT</th>
                            <th style="width:90px;">Total</th>
                            <th style="width:46px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <input type="text" autofocus placeholder="Search Atleast 3 Keyword"
                                       class="testIn form-control makemefocus">
                                <div class="textData" style="width:100%;position:relative;z-index:99;"></div>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <button type="button" class="btn btn-danger btn-xs deleteparent" title="Remove">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr class="pos-summary-row">
                            <th colspan="11" class="text-right">Total Price</th>
                            <td colspan="2">KES <span id="total_exclusive">0.00</span></td>
                        </tr>
                        <tr class="pos-summary-row">
                            <th colspan="11" class="text-right">Discount</th>
                            <td colspan="2">KES <span id="total_discount">0.00</span></td>
                        </tr>
                        <tr class="pos-summary-row">
                            <th colspan="11" class="text-right">Total VAT</th>
                            <td colspan="2">KES <span id="total_vat">0.00</span></td>
                        </tr>
                        <tr class="pos-summary-row total-row">
                            <th colspan="11" class="text-right">Total</th>
                            <td colspan="2">KES <span id="total_total">0.00</span></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="pos-footer">
                    <button type="button" class="btn btn-pos-pay" id="continuePayment" data-toggle="modal" data-target="#modelId">
                        <i class="fa fa-arrow-right"></i>&nbsp; Continue to Payment
                    </button>
                </div>
            </div>

        </div>{{-- /pos-wrap --}}

        {{-- FAB add row (fixed) --}}
        <button type="button" class="btn pos-fab addNewrow" title="Add Item">
            <i class="fa fa-plus"></i>
        </button>

        <input type="hidden" id="attached_sales" name="attached_sales" value="">

        {{-- ── Payment Modal ── --}}
        <div class="modal fade" id="modelId" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fa fa-credit-card" style="color:#00d4aa;margin-right:8px;"></i>Payments</h5>
                        <input class="form-control tenderAmount" name="tenderAmount" type="hidden" value="0"
                               onkeyup="checkBalance()" onchange="checkBalance()">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="padding:0;">
                        <table class="table table-hover pay-method-table" style="margin:0;">
                            <thead>
                            <tr>
                                <th>Method</th>
                                <th>Amount</th>
                                <th style="display:none;">Reference</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($paymentMethod as $method)
                                @if($method->is_mpesa)
                                    <tr>
                                        <td>{{ $method->title }}</td>
                                        <td>
                                            <input type="text" class="form-control mpesa" id="mpesa_number"
                                                   name="mpesa_number" value="" placeholder="Enter Customer Number">
                                            <span id="error-mpesa-number" class="error-message text-danger" style="display:none;"></span>
                                        </td>
                                        <td>
                                            <button id="mpesa_pay" class="btn btn-primary btn-sm mpesa_pay" value="{{ $method->id }}">Push STK</button>
                                        </td>
                                    </tr>
                                    @continue
                                @endif
                                <tr>
                                    <td>{{ $method->title }}</td>
                                    <td>
                                        <input type="text" class="form-control checkBalance dynamic-input-method dynamic-input amount"
                                               min="1"
                                               id="payment_method[{{$method->id}}]"
                                               name="payment_amount[{{$method->id}}]"
                                               onkeyup="checkBalance()" onchange="checkBalance()"
                                               data-method-title="{{$method->title}}"
                                               data-method-cash="{{$method->is_cash}}"
                                               value="" placeholder="Enter amount">
                                        <span id="error[{{$method->id}}]" class="error-message text-danger" style="display:none;">Please verify this payment</span>
                                        <span id="error-amount[{{$method->id}}]" class="error-message text-danger" style="display:none;">Please Enter Amount</span>
                                    </td>
                                    <td>
                                        <input type="hidden" class="form-control reference"
                                               id="payment_remarks[{{$method->id}}]"
                                               name="payment_remarks[{{$method->id}}]"
                                               value="" readonly placeholder="Reference">
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="pay-summary-due">
                                <td class="total_total_sales">Total Due</td>
                                <td colspan="2" class="text-right">
                                    <span class="total_total_sales total_total">0.00</span>
                                </td>
                            </tr>
                            <tr class="pay-summary-due">
                                <td class="total_total_sales">Total Tendered</td>
                                <td colspan="2" class="text-right">
                                    <span class="total_total_sales total_tendered">0.00</span>
                                </td>
                            </tr>
                            <tr class="pay-summary-bal">
                                <td style="font-weight:700;font-size:18px;">Balance</td>
                                <td colspan="2" class="cash_change text-right" style="font-size:18px;font-weight:700;">0.00</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Close
                        </button>
                        @if(isset($permission[$pmodule.'___save']) || $permission == 'superadmin')
                            <button type="submit" class="btn btn-primary btn-sm addExpense" value="save">
                                <i class="fa fa-save"></i> Save
                            </button>
                        @endif
                        @if(isset($permission[$pmodule.'___process']) || $permission == 'superadmin')
                            <button type="submit" class="btn btn-primary btn-sm addExpense processIt" id="process"
                                    value="send_request" disabled>
                                <i class="fa fa-check"></i> Process
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="transactionsModal" tabindex="-1" role="dialog" aria-labelledby="transactionsModalLabel">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="pull-right">
                            <img src="{{ asset('images/mpesa.png') }}" alt="mpesa" class="payment-logo">
                            <img src="{{ asset('images/equity.png') }}" alt="equity" class="payment-logo">
                            <img src="{{ asset('images/vooma.png') }}" alt="vooma" class="payment-logo">
                            <!-- Add more logos as needed -->
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <h4 class="modal-title" id="transactionsModalLabel">Transactions</h4>
                    </div>
                    <div class="modal-body">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active"><a href="#active" aria-controls="active" role="tab" data-toggle="tab">Active</a></li>
{{--                            <li role="presentation"><a href="#inactive" aria-controls="inactive" role="tab" data-toggle="tab">Inactive</a></li>--}}
{{--                            <li role="presentation"><a href="#utilized" aria-controls="utilized" role="tab" data-toggle="tab">Utilized</a></li>--}}
                        </ul>

                        <!-- Tab panes -->
                        <div class="tab-content">
                            <!-- Active Transactions Tab -->
                            <div role="tabpanel" class="tab-pane active" id="active">
                                <input type="text" id="searchActive" class="form-control" placeholder="Search Active Transactions">
                                <table class="table table-bordered" id="activeTable">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Time</th>
                                        <th>Name</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Action</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Active Transactions Data -->
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><strong>Total Selected:</strong></td>
                                        <td colspan="2" class="table-footer-total">0.00</td>
                                    </tr>
                                    </tfoot>
                                </table>
                                <div class="modal-footer">
                                    <button id="proceedButton" class="btn btn-primary"> <i class="fa fa-check"></i> Proceed</button>
                                </div>
                            </div>
                            <!-- Inactive Transactions Tab -->
                            <div role="tabpanel" class="tab-pane" id="inactive">
                                <input type="text" id="searchInactive" class="form-control" placeholder="Search Inactive Transactions">
                                <table class="table table-bordered" id="inactiveTable">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Time</th>
                                        <th>Method</th>
                                        <th>Customer</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Inactive Transactions Data -->
                                    </tbody>
                                </table>
                            </div>
                            <!-- Utilized Transactions Tab -->
                            <div role="tabpanel" class="tab-pane" id="utilized">
                                <input type="text" id="searchUtilized" class="form-control" placeholder="Search Utilized Transactions">
                                <table class="table table-bordered" id="utilizedTable">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Time</th>
                                        <th>Method</th>
                                        <th>Sales No</th>
                                        <th>Use Time</th>
                                        <th>Customer</th>
                                        <th>Cashier</th>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <!-- Utilized Transactions Data -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary pull-left" data-dismiss="modal"> <i class="fa fa-close"></i>Close</button>
                    </div>
                </div>
            </div>
        </div>

        {{--mpesa wating Modal--}}
        <div class="modal fade" id="loadingModal" tabindex="-1" role="dialog" aria-labelledby="loadingModalLabel" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="loadingModalLabel"></h4>
                    </div>
                    <div class="modal-body text-center">
                        <!-- Image above the loader -->
                        <img src="{{asset('images/mpesa.png')}}" alt="Loading Image" class="img-responsive center-block" style="max-width: 100px; margin-bottom: 20px;">

                        <!-- Spinner Loader -->
                        <div class="loader">
                            <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions  Modal -->
        <div id="searchModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Search Sales </h4>
                    </div>
                    <div class="modal-body">
                        <!-- Search Input -->
                        <input type="text" id="searchSaleInput" class="form-control" placeholder="Search Sale...">

                        <!-- Table for displaying results -->
                        <table class="table table-bordered table-hover" id="resultsTable" style="display: none;">
                            <thead>
                            <tr>

                                <th>Time</th>
                                <th>Sales No</th>
                                <th>Customer</th>
                                <th>Customer Phone</th>
                                <th>Total</th>
                                <th>Select</th>
                            </tr>
                            </thead>
                            <tbody id="resultsBody">
                            <!-- Results will be appended here -->
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Current CashSale:</strong></td>
                                <td colspan="2" class="thisSaleTotal text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total Selected:</strong></td>
                                <td colspan="2" class="totalBeforeAttachments text-right">0.00</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Cumulative Total:</strong></td>
                                <td colspan="2" class="cumulativeTotal text-right">0.00</td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="attachSalesBtn">Proceed To Payments</button>
                    </div>
                </div>
            </div>
        </div>



    </form>
@endsection

@section('uniquepagestyle')
    <link href="{{asset('assets/admin/bower_components/select2/dist/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('assets/admin/dist/datepicker.css')}}">

    <style type="text/css">
        .select2 {
            width: 100% !important;
        }

        #note {
            height: 60px !important;
        }

        .align_float_right {
            text-align: right;
        }

        .textData table tr:hover, .SelectedLi {
            background: #00d4aa !important;
            color: #000 !important;
            cursor: pointer !important;
        }


        /* ALL LOADERS */

        .loader {
            width: 100px;
            height: 100px;
            border-radius: 100%;
            position: relative;
            margin: 0 auto;
            top: 35%;
        }

        /* LOADER 1 */

        #loader-1:before, #loader-1:after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 100%;
            border: 10px solid transparent;
            border-top-color: #3498db;
        }

        #loader-1:before {
            z-index: 100;
            animation: spin 1s infinite;
        }

        #loader-1:after {
            border: 10px solid #ccc;
        }

        @keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }

            100% {
                -webkit-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
        }

    </style>
@endsection

@section('uniquepagescript')
    <div id="loader-on" style="
position: fixed;
top: 0;
text-align: center;
display: block;
z-index: 999999;
width: 100%;
height: 100%;
background: #000000b8;
display:none;
">
        <div class="loader" id="loader-1"></div>
    </div>
    <script src="{{asset('js/sweetalert.js')}}"></script>
    <script src="{{asset('js/form.js')}}"></script>
    <script src="{{asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/idb-keyval@6/dist/umd.js"></script>
    <script src="{{asset('assets/admin/dist/bootstrap-datepicker.js')}}"></script>

    @include('partials.shortcuts')

@endsection


