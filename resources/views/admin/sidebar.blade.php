<aside class="main-sidebar">
    <?php
    $logged_user_info = getLoggeduserProfile();
    $my_permissions = $logged_user_info->permissions;
    $route_name = \Route::currentRouteName();
    //dd($my_permissions);
    ?>
    <section class="sidebar">

        <div class="user-panel">
            <div class="pull-left image">
                @if ($logged_user_info->image && file_exists('uploads/users/thumb/' . $logged_user_info->image))
                    <img src="{{ asset('uploads/users/thumb/' . $logged_user_info->image) }}" class="img-circle"
                         alt="User Image">
                @else
                    <img src="{{ asset('assets/userdefault.jpg') }}" alt="User" class="img-circle">
                @endif
            </div>

            <div class="pull-left info">
                <p>{!! ucfirst($logged_user_info->name) !!}</p>
            </div>
        </div>

        <ul class="sidebar-menu" data-widget="tree">
            <li class="header">MAIN NAVIGATION</li>
            <li><a href="{!! route('admin.dashboard') !!}"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
            </li>

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'route-customers' ||
                            $model == 'end-of-the-day-routine' ||
                            $model == 'cheque-report' ||
                            $model == 'bounced-cheque' ||
                            $model == 'cleared-cheque' ||
                            $model == 'deposit-cheque' ||
                            $model == 'register-cheque' ||
                            $model == 'merged-payments' ||
                            $model == 'equity-bank-deposits' ||
                            $model == 'pos-cash-sales-new' ||
                            $model == 'total-vat-report' ||
                            $model == 'esd-vat-report' ||
                            $model == 'new-kra-signed-invoices' ||
                            $model == 'dispatch-invoice-report' ||
                            $model == 'dispatch-and-close-loading-sheet' ||
                            $model == 'dispatched-loading-sheets' ||
                            $model == 'return-transfers' ||
                            $model == 'approver-1' ||
                            $model == 'approver-2' ||
                            $model == 'rejected-returns' ||
                            $model == 'processed-returns' ||
                            $model == 'return-confirm-report' ||
                            $model == 'transfers' ||
                            $model == 'sales-invoice' ||
                            $model == 'authorise-requisitions' ||
                            $model == 'confirm-invoice' ||
                            $model == 'confirm-invoice-test' ||
                            $model == 'summary_report' ||
                            $model == 'petty-cash' ||
                            $model == 'return-list' ||
                            $model == 'route-profitibility-report' ||
                            $model == 'dispatched_items_report' ||
                            $model == 'detailed_sales_report' ||
                            $model == 'sales_by_date_report' ||
                            $model == 'dispatch' ||
                            $model == 'pos-cash-sales' ||
                            $model == 'cash-sales' ||
                            $model == 'credit-note' ||
                            $model == 'maintain-customers' ||
                            $model == 'payment-reconcilliation' ||
                            $model == 'order-taking-schedules' ||
                            $model == 'proforma-invoice' ||
                            $model == 'customer-aging-analysis' ||
                            $model == 'sales-commission-bands' ||
                            $model == 'sales-invoices' ||
                            $model == 'parking-lists' ||
                            $model == 'salesman-shifts' ||
                            $model == 'delivery-schedules' ||
                            $model == 'delivery-schedules-live' ||
                            $model == 'route-daily-sales-report' ||
                            $model == 'reported-shift-issues' ||
                            $model == 'field-visits' ||
                            $model == 'sales-and-receivables-reports' ||
                            $model == 'shift-reopen-request' ||
                            $model == 'route-weekly-sales-report' ||
                            $model == 'loading-schedule-vs-sales-report' ||
                            $model == 'delivery-schedule-report' ||
                            $model == 'customer-balances-report' ||
                            $model == 'shift-delivery-report' ||
                            $model == 'till-direct-banking-report' ||
                            $model == 'route-performance-report' ||
                            $model == 'promotion-sales-report' ||
                            $model == 'discount-sales-report' ||
                            $model == 'sales-and-receivables-dashboard' ||
                            $model == 'sales-per-supplier-per-route' ||
                            $model == 'sales-analysis-report' ||
                            $model == 'bank-reconciliation' ||
                            $model == 'archived_orders_report' ||
                            $model == 'archived_orders' ||
                            $model == 'salesman-offsite-requests')) active @endif">
                    <a href="#"><i class="fa fa-fw fa-hotel"></i> <span> Sales & Receivables</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>

                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['module-dashboards__sales-and-receivables']))
                            <li class="@if (isset($model) && $model == 'sales-and-receivables-dashboard') active @endif"><a
                                        href="{!! route('sales-and-receivables-dashboard') !!}"><i class="fa fa-dashboard"></i>
                                    <span>Dashboard</span></a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-customers___view']))
                            <li class="@if (isset($model) && $model == 'maintain-customers') active @endif">
                                <a href="{!! route('maintain-customers.index') !!}"><i class="fa fa-circle"></i>Maintain Customers</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['reconciliation___view']))
                            <li class="treeview @if (isset($model) && ($model == 'bank-reconciliation')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Reconciliation
                                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['reconciliation___reconcile']))
                                        <li class="@if (isset($model) && $model == 'bank-reconciliation') active @endif">
                                            <a href="{!! route('bank-reconciliation.index') !!}"><i class="fa fa-circle"></i> Recon Tool</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['reconciliation___upload']))
                                        <li class="@if (isset($model) && $model == 'bank-reconciliation') active @endif">
                                            <a href="{!! route('maintain-customers.real_recon.index') !!}"><i class="fa fa-circle"></i> Manual Uploads</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['reconciliation___suspend']))
                                        <li class="@if (isset($model) && $model == 'bank-reconciliation') active @endif">
                                            <a href="{!! route('suspended-transactions.index') !!}"><i class="fa fa-circle"></i> Suspended Transactions</a>
                                        </li>

                                        <li class="@if (isset($model) && $model == 'bank-reconciliation') active @endif">
                                            <a href="{!! route('suspended-transactions.expunged') !!}"><i class="fa fa-circle"></i> Expunged Transactions</a>
                                        </li>

                                        <li class="@if (isset($model) && $model == 'bank-reconciliation') active @endif">
                                            <a href="{!! route('suspended-transactions.restored') !!}"><i class="fa fa-circle"></i> Restored Transactions</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-customers___view']))
                            <li class="treeview @if (isset($model) && ($model == 'route-customers' || $model == 'field-visits')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Route Customers
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-customers___overview']))
                                        <li class="@if (isset($model) && $model == 'route-customers') active @endif">
                                            <a href="{!! route('route-customers.overview') !!}">
                                                <i class="fa fa-circle"></i> Overview
                                            </a>
                                        </li>

                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-customers___listing']))
                                        <li class="@if (isset($model) && $model == 'route-customers') active @endif">
                                            <a href="{!! route('route-customers.index') !!}">
                                                <i class="fa fa-circle"></i> Listing
                                            </a>
                                        </li>

                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-customers___onboarding-requests']))
                                        <li class="@if (isset($model) && $model == 'route-customers') active @endif">
                                            <a href="{!! route('route-customers.unverified') !!}">
                                                <i class="fa fa-circle"></i> Onboarding Requests
                                            </a>
                                        </li>

                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-customers___approval-requests']))

                                        <li class="@if (isset($model) && $model == 'route-customers') active @endif">
                                            <a href="{!! route('route-customers.approval-requests') !!}">
                                                <i class="fa fa-circle"></i> Approval Requests
                                            </a>
                                        </li>

                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-customers___field-visits']))

                                        @if ($logged_user_info->role_id == 1)
                                            <li class="@if (isset($model) && $model == 'field-visits') active @endif">
                                                <a href="{{ route('field-visits.index') }}">
                                                    <i class="fa fa-circle"></i> Field Visits
                                                </a>
                                            </li>
                                        @endif

                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if (
                            $logged_user_info->role_id == 1 ||
                                isset($my_permissions['pos-cash-sales-new___view']) ||
                                isset($my_permissions['pos-cash-sales___view']) ||
                                isset($my_permissions['petty-cash___view']) ||
                                isset($my_permissions['pos-cash-sales___return-list']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'pos-cash-sales-new' ||
                                        $model == 'pos-cash-sales' ||
                                        $model == 'petty-cash' ||
                                        $model == 'pos-return-list')) active @endif">
                                <a href="#"><i class="fa fa-circle"></i> Cash Sales
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['pos-cash-sales___view']))
                                        <li class="@if (isset($model) && $model == 'pos-cash-sales') active @endif"><a
                                                    href="{!! route('pos-cash-sales.index') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i>POS Cash
                                                Sales</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['pos-cash-sales-new___view']))
                                        <li class="@if (isset($model) && $model == 'pos-cash-sales-new') active @endif">
                                            <a href="{!! route('pos-cash-sales-new.index') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i>POS Cash
                                                Sales - II</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash___view']))
                                        <li class="@if (isset($model) && $model == 'petty-cash') active @endif"><a
                                                    href="{!! route('petty-cash.index') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i>Petty
                                                Cash</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['pos-cash-sales___return-list']))
                                        <li class="@if (isset($model) && $model == 'pos-return-list') active @endif"><a
                                                    href="{!! route('pos-cash-sales.returned_cash_sales_list') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i>Cash Sales
                                                Return</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if (
                            $logged_user_info->role_id == 1 ||
                                isset($my_permissions['sales-invoice___view']) ||
                                isset($my_permissions['confirm-invoice___view']) ||
                                isset($my_permissions['print-invoice-delivery-note___view']) ||
                                isset($my_permissions['print-invoice-delivery-note___return']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'return-transfers' ||
                                        $model == 'transfers' ||
                                        $model == 'sales-invoice' ||
                                        $model == 'authorise-requisitions' ||
                                        $model == 'confirm-invoice' ||
                                        $model == 'confirm-invoice-test' ||
                                        $model == 'salesman-shift' ||
                                        $model == 'salesman-orders' ||
                                        $model == 'salesman-customers' ||

                                        $model == 'processed-returns' ||
                                        $model == 'approver-1' ||
                                        $model == 'approver-2' ||
                                        $model == 'rejected-returns' ||

                                        $model == 'return-confirm-report' ||
                                        $model == 'rejected-returns' ||
                                        $model == 'print-invoice-delivery-note')) active @endif">
                                <a href="#"><i class="fa fa-circle"></i> Salesman Invoice
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    {{--                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-invoice___view']))--}}
                                    {{--                                        <li class="@if (isset($model) && $model == 'sales-invoice') active @endif">--}}
                                    {{--                                            <a href="{!! route('sales-invoice.index') !!}"><i class="fa fa-circle"></i>Invoice</a>--}}
                                    {{--                                        </li>--}}
                                    {{--                                    @endif--}}

                                    {{--                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['confirm-invoice___view']))--}}
                                    {{--                                        <li class="@if (isset($model) && $model == 'confirm-invoice') active @endif">--}}
                                    {{--                                            <a href="{!! route('confirm-invoice.index') !!}"><i class="fa fa-circle"></i> Confirm Invoice</a>--}}
                                    {{--                                        </li>--}}
                                    {{--                                    @endif--}}

                                    {{--                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['confirm-invoice-r___view']))--}}
                                    {{--                                        <li class="@if (isset($model) && $model == 'confirm-invoice-test') active @endif">--}}
                                    {{--                                            <a href="{!! route('confirm-invoice-test.index') !!}"><i class="fa fa-circle"></i> Confirm Invoice R</a>--}}
                                    {{--                                        </li>--}}
                                    {{--                                    @endif--}}

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['print-invoice-delivery-note___view']))
                                        <li class="@if (isset($model) && $model == 'transfers') active @endif">
                                            <a href="{!! route('transfers.index') . getReportDefaultFilterForTrialBalance() !!}">
                                                <i class="fa fa-circle"></i> Print Invoice/Delivery Note
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['print-invoice-delivery-note___return']))
                                        <li class="@if (isset($model) && $model == 'return-transfers') active @endif">
                                            <a href="{!! route('transfers.return_list') . getReportDefaultFilterForTrialBalance() !!}">
                                                <i class="fa fa-circle"></i> Sales Invoice Returns
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Salesman Web Order Taking --}}
                                    @php
                                        $isSalesman = false;
                                        if ($logged_user_info) {
                                            $salesRoleIds = config('salesman.sales_role_ids', [169, 170]);
                                            $salesKeywords = config('salesman.sales_role_keywords', ['sales', 'salesman', 'representative']);
                                            $roleName = $logged_user_info->userRole->name ?? $logged_user_info->userRole->title ?? '';
                                            
                                            $isSalesman = !empty($logged_user_info->route) || 
                                                         in_array((int) $logged_user_info->role_id, $salesRoleIds) ||
                                                         collect($salesKeywords)->some(fn($keyword) => stripos($roleName, $keyword) !== false);
                                        }
                                    @endphp

                                    @if ($isSalesman || $logged_user_info->role_id == 1)
                                        <li class="@if (isset($model) && $model == 'salesman-orders') active @endif">
                                            <a href="{!! route('salesman-orders.index') !!}">
                                                <i class="fa fa-shopping-cart"></i> Order Taking
                                            </a>
                                        </li>
                                        
                                        <li class="@if (isset($model) && $model == 'salesman-customers') active @endif">
                                            <a href="{!! route('salesman-customers.index') !!}">
                                                <i class="fa fa-users"></i> Customer Management
                                            </a>
                                        </li>
                                    @endif

                                    <li class="treeview @if (isset($model) &&
                                    ($model == 'approver-1' || $model == 'approver-2' || $model == 'return-confirm-report' )) active @endif">
                                        <a href="#">
                                            <i class="fa fa-circle"></i> Over limit Returns
                                            <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                                        </a>
                                        <ul class="treeview-menu">
                                            @if ($logged_user_info->role_id == 1  ||
                                             isset($my_permissions['approver-limit-returns___approver-1']))
                                                <li class="@if (isset($model) && $model == 'approver-1') active @endif">
                                                    <a href="{!! route('transfers.return_list_groups') . getReportDefaultFilterForTrialBalance() !!}">
                                                        <i class="fa fa-circle"></i> Approver 1
                                                    </a>
                                                </li>

                                            @endif


                                            @if ($logged_user_info->role_id == 1  ||
                                           isset($my_permissions['approver-limit-returns___approver-2']))

                                                <li class="@if (isset($model) && $model == 'approver-2') active @endif">
                                                    <a href="{!! route('transfers.return_list_groups_2') . getReportDefaultFilterForTrialBalance() !!}">
                                                        <i class="fa fa-circle"></i> Approver 2
                                                    </a>
                                                </li>

                                            @endif

                                            @if ($logged_user_info->role_id == 1  ||
                                          isset($my_permissions['approver-limit-returns___return-confirm-report']))

                                                <li class="@if (isset($model) && $model == 'return-confirm-report') active @endif">
                                                    <a href="{!! route('transfers.return_groups') . getReportDefaultFilterForTrialBalance() !!}">
                                                        <i class="fa fa-circle"></i> Over Limit Returns
                                                    </a>
                                                </li>

                                            @endif

                                        </ul>
                                    </li>


                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['processed-returns___view']))
                                        <li class="@if (isset($model) && $model == 'processed-returns') active @endif">
                                            <a href="{!! route('transfers.processed-returns') . getReportDefaultFilterForTrialBalance() !!}">
                                                <i class="fa fa-circle"></i> Processed Returns
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['rejected-returns___view']))
                                        <li class="@if (isset($model) && $model == 'rejected-returns') active @endif">
                                            <a href="{!! route('transfers.rejected-returns') . getReportDefaultFilterForTrialBalance() !!}">
                                                <i class="fa fa-circle"></i> Rejected Returns
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-taking___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'order-taking-schedules' ||
                                        $model == 'reported-shift-issues' ||
                                        $model == 'salesman-shifts' ||
                                        $model == 'shift-reopen-request' ||
                                        $model == 'salesman-offsite-requests')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Order Taking
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    {{--                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-taking-overview___view']))--}}
                                    {{--                                        <li class="@if (isset($model) && $model == 'order-taking-schedules') active @endif">--}}
                                    {{--                                            <a href="{!! route('order-taking-schedules.overview') !!}">--}}
                                    {{--                                                <i class="fa fa-circle"></i>Overview--}}
                                    {{--                                            </a>--}}
                                    {{--                                        </li>--}}
                                    {{--                                    @endif--}}

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-taking-schedules___view']))
                                        <li class="@if (isset($model) && $model == 'salesman-shifts') active @endif">
                                            <a href="{!! route('salesman-shifts.index') !!}"><i class="fa fa-circle"></i>
                                                Salesman Shifts
                                            </a>
                                        </li>
                                    @endif

                                    {{--                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-taking-schedules___reopen-requests']))--}}
                                    {{--                                        <li class="@if (isset($model) && $model == 'shift-reopen-request') active @endif">--}}
                                    {{--                                            <a href="{!! route('salesman-shift.reopen-requests') !!}"><i class="fa fa-circle"></i>Shift--}}
                                    {{--                                                Reopen Requests</a>--}}
                                    {{--                                        </li>--}}
                                    {{--                                    @endif--}}

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-taking-schedules___offsite-requests']))
                                        <li class="@if (isset($model) && $model == 'salesman-offsite-requests') active @endif">
                                            <a href="{!! route('salesman-shift.offsite-requests') !!}">
                                                <i class="fa fa-circle"></i>Offsite Shift Requests
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['reported-shift-issues___view']))
                                        <li class="@if (isset($model) && $model == 'reported-shift-issues') active @endif">
                                            <a href="{!! route('reported-shift-issues.index') !!}">
                                                <i class="fa fa-circle"></i>Reported Issues
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['dispatch-and-delivery___view']))
                            <li class="treeview @if (isset($model) && ($model == 'parking-lists' || $model == 'delivery-schedules' || $model == 'shift-delivery-report')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Dispatch & Delivery
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['store-loading-sheet___view']))
                                        <li class="@if (isset($model) && $model == 'parking-lists') active @endif">
                                            <a href="{!! route('store-loading-sheets.index') !!}">
                                                <i class="fa fa-circle"></i>Dispatch Loading Sheet
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['dispatched-loading-sheets___view']))
                                        <li class="@if (isset($model) && $model == 'parking-lists') active @endif">
                                            <a href="{!! route('store-loading-sheets.dispatched') !!}">
                                                <i class="fa fa-circle"></i>Dispatched Loading Sheets
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['delivery-schedule___view']))
                                        <li class="@if (isset($model) && $model == 'delivery-schedules') active @endif">
                                            <a href="{!! route('delivery-schedules.index') !!}"><i class="fa fa-circle"></i>Deliveries</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['shift_delivery_report___view']))
                                        <li class="@if (isset($model) && $model == 'shift-delivery-report') active @endif">
                                            <a href="{!! route('dispatch-reports.shift-delivery-report') !!}"><i class="fa fa-circle"></i>Shift
                                                Delivery Report</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif


                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'dashboard_report' ||
                                        $model == 'total-vat-report' ||
                                        $model == 'esd-vat-report' ||
                                        $model == 'new-kra-signed-invoices' ||
                                        $model == 'sales-and-receivables-reports' ||
                                        $model == 'route-profitibility-report' ||
                                        $model == 'summary_report' ||
                                        $model == 'sales_by_date_report' ||
                                        $model == 'detailed_sales_report' ||
                                        $model == 'route-daily-sales-report' ||
                                        $model == 'route-weekly-sales-report' ||
                                        $model == 'loading-schedule-vs-sales-report' ||
                                        $model == 'delivery-schedule-report' ||
                                        $model == 'customer-balances-report' ||
                                        $model == 'till-direct-banking-report' ||
                                        $model == 'route-performance-report' ||
                                        $model == 'promotion-sales-report' ||
                                        $model == 'discount-sales-report' ||
                                        $model == 'sales-per-supplier-per-route' ||
                                        $model == 'sales-analysis-report' ||
                                        $model == 'archived_orders_report' ||
                                        $model == 'archived_orders' ||
                                        $model == 'dispatched_items_report')) active @endif">
                                <a href="#"><i class="fa fa-share"></i><span> Reports</span>
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['summary-report___detailed']))
                                        <li class="@if (isset($model) && $model == 'summary_report') active @endif"><a
                                                    href="{!! route('summary_report.index') !!}"><i class="fa fa-circle"></i> EOD
                                                Detailed Report
                                            </a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['summary-report___summary']))
                                        <li class="@if (isset($model) && $model == 'summary_report') active @endif"><a
                                                    href="{!! route('summary_report.summaryindex') !!}"><i class="fa fa-circle"></i> EOD
                                                Summary
                                                Report </a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['summary-report___sales_summary']))
                                        <li class="@if (isset($model) && $model == 'summary_report') active @endif"><a
                                                    href="{!! route('summary_report.sales_summary') !!}"><i class="fa fa-circle"></i> Sales Summary Report
                                            </a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['summary-report___detailed_sales_report']))
                                        <li class="@if (isset($model) && $model == 'detailed_sales_report') active @endif"><a
                                                    href="{!! route('summary_report.detailed_sales_report') !!}"><i class="fa fa-circle"></i>Sales of
                                                Product by Date</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['summary-report___sales_by_date_report']))
                                        <li class="@if (isset($model) && $model == 'sales_by_date_report') active @endif"><a
                                                    href="{!! route('summary_report.sales_by_date_report') !!}"><i class="fa fa-circle"></i>Sales by
                                                Date</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___shift-summary']))
                                        <li><a href="{!! route('sales-and-receivables-reports.shift-summary') !!}"><i class="fa fa-circle"></i>
                                                Shift Summary </a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___salesman-trip-summary']))
                                        <li>
                                            <a href="{!! route('sales-and-receivables-reports.salesman-trip-summary') !!}"><i class="fa fa-circle"></i> Salesman
                                                Trip Summary </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___gross-profit']))
                                        <li class="@if (isset($model) && $model == 'dispatched_items_report') active @endif">
                                            <a href="{!! route('gross-profit.inventory-valuation-report') !!}"><i class="fa fa-circle"></i>
                                                Gross Profit Summary Report </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['gross-profit___view']))
                                        <li class=""><a href="{!! route('gross-profit.inventory-valuation-detailed-report') !!}"><i
                                                        class="fa fa-circle"></i> Gross Profit Detailed Report </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-profitibility-report___view']))
                                        <li class="@if (isset($model) && $model == 'route-profitibility-report') active @endif">
                                            <a href="{!! route('gross-profit.route-profitibility-report') !!}"><i class="fa fa-circle"></i> Route
                                                Profitibility Report </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___dashboard-report']))
                                        <li class="@if (isset($model) && $model == 'dashboard_report') active @endif"><a
                                                    href="{!! route('dashboard_report.index', ['show_dashboard' => 1]) !!}"><i class="fa fa-dashboard"></i>
                                                <span>Dashboard Report</span></a></li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['dispatch-pos-invoice-sales___dispatch-report']))
                                        <li class="@if (isset($model) && $model == 'dispatched_items_report') active @endif">
                                            <a href="{!! route('dispatched_items.report') !!}"><i class="fa fa-circle"></i>Dispatch
                                                Items Report</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['summary-report___inventory_sales_report']))
                                        <li class="@if (isset($model) && $model == 'dispatched_items_report') active @endif"><a
                                                    href="{!! route('summary_report.inventory_sales_report') !!}"><i
                                                        class="fa fa-circle"></i>Inventory Valuation Report</a></li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___customer_invoices']))
                                        <li>
                                            <a href="{!! route('sales-and-receivables-reports.customer_invoices') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i> Customer
                                                Invoices </a>
                                        </li>
                                    @endif

                                    @if (
                                        $logged_user_info->role_id == 1 ||
                                            isset($my_permissions['sales-and-receivables-reports___daily-cash-receipt-summary']))
                                        <li>
                                            <a href="{!! route('sales-and-receivables-reports.daily-cash-receipt-summary') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i>
                                                Daily
                                                Cash Receipt Summary </a>
                                        </li>
                                    @endif

                                    @if (
                                        $logged_user_info->role_id == 1 ||
                                            isset($my_permissions['sales-and-receivables-reports___customer-aging-analysis']))
                                        <li class="@if (isset($model) && $model == 'customer-aging-analysis') active @endif">
                                            <a href="{!! route('customer-aging-analysis.index') . '?start-date=' . date('Y-m-d') !!}"><i class="fa fa-circle"></i>
                                                Customer Aging Analysis </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___customer-statement']))
                                        <li class="@if (isset($model) && $model == 'customer-statement') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.customer_statement') !!}"><i class="fa fa-circle"></i>
                                                Customer Statement </a>
                                        </li>
                                    @endif
                                    {{--
                                                                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___customer-statement']))
                                                                            <li class="@if (isset($model) && $model == 'customer-statement') active @endif">
                                                                                <a href="{!! route('sales-and-receivables-reports.customer_statement2') !!}"><i class="fa fa-circle"></i>
                                                                                    Customer Statement 2</a>
                                                                            </li>
                                                                        @endif --}}

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___vat-report']))
                                        <li class="@if (isset($model) && $model == 'total-vat-report') active @endif"><a
                                                    href="{!! route('customer-aging-analysis.vatReport') !!}"><i class="fa fa-circle"></i> Vat
                                                Report </a></li>

                                        <li class="@if (isset($model) && $model == 'esd-vat-report') active @endif"><a
                                                    href="{!! route('customer-aging-analysis.esdVatReport') !!}"><i class="fa fa-circle"></i>ESD Vat
                                                Report</a></li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-reports___weekly-sales-report']))
                                        <li class="@if (isset($model) && $model == 'route-daily-sales-report') active @endif">
                                            <a href="{!! route('route-reports.daily-sales') !!}">
                                                <i class="fa fa-circle"></i> Daily Sales Report
                                            </a>
                                        </li>
                                    @endif

                                    {{-- <li>
                                        <a href="{!! route('sales-and-receivables-reports.sales-commission-report') . getReportDefaultFilterForTrialBalance() !!}">
                                            <i class="fa fa-circle"></i> Sales Commission Report
                                        </a>
                                    </li> --}}

                                    @if (
                                        $logged_user_info->role_id == 1 ||
                                            isset($my_permissions['sales-and-receivables-reports___loading-schedule-vs-stock-report']))
                                        <li class="@if (isset($model) && $model == 'loading-schedule-vs-sales-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.loading-schedule-vs-sales-report') !!}">
                                                <i class="fa fa-circle"></i> Loading Schedule vs Stocks
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        $logged_user_info->role_id == 1 ||
                                            isset($my_permissions['sales-and-receivables-reports___delivery-schedule-report']))
                                        <li class="@if (isset($model) && $model == 'delivery-schedule-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.delivery-schedule-report') !!}">
                                                <i class="fa fa-circle"></i> Delivery Schedule Report
                                            </a>
                                        </li>
                                    @endif

                                    @if (
                                        $logged_user_info->role_id == 1 ||
                                            isset($my_permissions['sales-and-receivables-reports___customer-balances-report']))
                                        <li class="@if (isset($model) && $model == 'customer-balances-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.customer-balances-report') !!}">
                                                <i class="fa fa-circle"></i> Customer Balances Report
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___till-direct-banking-report']))
                                        <li class="@if (isset($model) && $model == 'till-direct-banking-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.till-direct-banking-report') !!}">
                                                <i class="fa fa-circle"></i> Till Direct Banking Report
                                            </a>
                                        </li>
                                    @endif
                                    @if (
                                        $logged_user_info->role_id == 1
                                            )
                                        <li class="@if (isset($model) && $model == 'till-direct-banking-report') active @endif">
                                            <a href="{!! route('summary_report.sales_vs_stocks_ledger') !!}">
                                                <i class="fa fa-circle"></i> Sales Vs Stocks Ledger
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___route-performance-report']))
                                        <li class="@if (isset($model) && $model == 'route-performance-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.route-performance-report') !!}">
                                                <i class="fa fa-circle"></i> Route Performance Report
                                            </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___route-performance-report']))
                                        <li class="@if (isset($model) && $model == 'group-performance-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.group-performance-report') !!}">
                                                <i class="fa fa-circle"></i> Group Performance Report
                                            </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___promotion-sales']))
                                        <li class="@if (isset($model) && $model == 'promotion-sales-report') active @endif">
                                            <a href="{!! route('sales-and-receivables-reports.promotion-sales-report') !!}">
                                                <i class="fa fa-circle"></i> Promotion Sales Report
                                            </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___discount-sales']))
                                        <li class="@if (isset($model) && $model == 'discount-sales-report') active @endif">
                                            <a href="{!! route('discount-sales-report') !!}">
                                                <i class="fa fa-circle"></i> Discount Sales Report
                                            </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___sales-per-supplier-per-route']))
                                        <li class="@if (isset($model) && $model == 'sales-per-supplier-per-route') active @endif">
                                            <a href="{!! route('sales-per-supplier-per-route') !!}">
                                                <i class="fa fa-circle"></i> Sales Per Supplier Per Route
                                            </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables-reports___sales-analysis']))
                                        <li class="@if (isset($model) && $model == 'sales-analysis-report') active @endif">
                                            <a href="{!! route('sales-analysis-report') !!}">
                                                <i class="fa fa-circle"></i> Sales Analysis
                                            </a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['archived_orders___archived_orders_report']))
                                        <li class="@if (isset($model) && $model == 'archived_orders_report') active @endif">
                                            <a href="{!! route('pos-cash-sales.archive-report') !!}">
                                                <i class="fa fa-circle"></i> Archived Orders
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases___view']))
                <li
                        class="treeview @if (isset($model) &&
                            ($model == 'external-requisitions' ||
                                $model == 'approve-external-requisitions' ||
                                $model == 'purchase-orders' ||
                                $model == 'approve-lpo' ||
                                $model == 'suggested-orders' ||
                                $model == 'lpo-portal-req-approval' ||
                                $model == 'resolve-requisition-to-lpo' || $model == 'lpo-status-and-leadtime-report' || $model == 'pending-suppliers' || $model == 'order-delivery-slots')) active
        @else
          @if (isset($rmodel) &&
                  ($rmodel == 'purchases-by-store-location' ||
                      $rmodel == 'purchases-by-family-group' ||
                      $rmodel == 'purchases-by-supplier' ||
                      $model == 'lpo-status-and-leadtime-report')) active @endif
        @endif">
                    <a href="#"><i class="fa fa-fw fa-hourglass"></i><span>Purchases</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchase_requisitions___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'external-requisitions' ||
                                        $model == 'approve-external-requisitions' ||
                                        $model == 'suggested-orders' ||
                                        $model == 'resolve-requisition-to-lpo')) active @endif">
                                <a href="#"><i class="fa fa-share"></i> Branch Purchase Requisitions
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['external-requisitions___view']))
                                        <li class="@if (isset($model) && $model == 'external-requisitions') active @endif">
                                            <a href="{!! route('external-requisitions.index') !!}"><i class="fa fa-circle"></i>
                                                Branch Requisitions</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['external-requisitions___archived-requisition']))
                                        <li class="@if (isset($model) && $model == 'external-requisitions') active @endif"><a
                                                    href="{!! route('external-requisitions.archivedRequisition') !!}"><i class="fa fa-circle"></i>
                                                Archived Branch Requisitions</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['approve-external-requisitions___view']))
                                        <li class="@if (isset($model) && $model == 'approve-external-requisitions') active @endif">
                                            <a href="{!! route('approve-external-requisitions.index') !!}"><i class="fa fa-circle"></i> Approve
                                                Branch Requisition</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['resolve-requisition-to-lpo___view']))
                                        <li class="@if (isset($model) && $model == 'resolve-requisition-to-lpo') active @endif">
                                            <a href="{!! route('resolve-requisition-to-lpo.index') !!}">
                                                <i class="fa fa-circle"></i> Resolve Requisition to LPO
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['suggested-orders___view']))
                                        <li class="@if (isset($model) && $model == 'suggested-orders') active @endif">
                                            <a href="{!! route('branch-requisitions.suggested-orders') !!}">
                                                <i class="fa fa-circle"></i> Suggested Orders
                                            </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['external-requisitions___external-requisition-report']))
                                        <li class="@if (isset($model) && $model == 'external-requisition-report') active @endif">
                                            <a href="{!! route('externalRequisitionReport') !!}"><i class="fa fa-circle"></i> Status
                                                Report </a>
                                        </li>
                                    @endif


                                </ul>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchase_orders_module___view']))
                            <li class="treeview @if (isset($model) && ($model == 'lpo-portal-req-approval' || $model == 'purchase-orders' || $model == 'approve-lpo' || $model == 'pending-suppliers' || $model == 'order-delivery-slots')) active @endif">
                                <a href="#"><i class="fa fa-share"></i> Purchase Orders
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchase-orders___view']))
                                        <li class="@if (isset($model) && $model == 'purchase-orders') active @endif"><a
                                                    href="{!! route('purchase-orders.index') !!}"><i class="fa fa-circle"></i> New
                                                Purchase Order</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['approve-lpo___view']))
                                        <li class="@if (isset($model) && $model == 'approve-lpo') active @endif"><a
                                                    href="{!! route('approve-lpo.index') !!}"><i class="fa fa-circle"></i> Approve
                                                LPO</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['archived-lpo___view']))
                                        <li class="@if (isset($models) && $models == 'archived-lpo') active @endif"><a
                                                    href="{!! route('purchase-orders.archived-lpo') !!}"><i class="fa fa-circle"></i>
                                                Archived LPO's</a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchase-order-status___view']))
                                        <li class="@if (isset($models) && $models == 'archived-lpo') active @endif"><a
                                                    href="{!! route('purchase-orders.status_report') !!}"><i class="fa fa-circle"></i> Status
                                                Report</a></li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['supplier-portal___view']))
                                        <li class="treeview @if (isset($model) && ($model == 'lpo-portal-req-approval' ||$model == 'pending-suppliers' || $model == 'order-delivery-slots')) active @endif">
                                            <a href="#">
                                                <i class="fa fa-circle"></i> Supplier Portal
                                                <span class="pull-right-container"><i
                                                            class="fa fa-angle-left pull-right"></i></span>
                                            </a>

                                            <ul class="treeview-menu">
                                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['pending-suppliers___view']))
                                                    <li class="@if (isset($model) && $model == 'pending-suppliers') active @endif">
                                                        <a href="{!! route('supplier-portal.pending-suppliers') !!}">
                                                            <i class="fa fa-circle"></i> Pending Invites
                                                        </a>
                                                    </li>
                                                @endif

                                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-delivery-slots___view']))
                                                    <li class="@if (isset($model) && $model == 'order-delivery-slots') active @endif">
                                                        <a href="{!! route('order-delivery-slots.index') !!}">
                                                            <i class="fa fa-circle"></i> LPO Delivery Slots
                                                        </a>
                                                    </li>
                                                @endif

                                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['order-delivery-slots___show']))
                                                    <li class="@if (isset($model) && $model == 'order-delivery-slots') active @endif">
                                                        <a href="{!! route('order-delivery-slots.show_booked_slots') !!}">
                                                            <i class="fa fa-circle"></i> LPO Booked Slots
                                                        </a>
                                                    </li>
                                                @endif
                                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['lpo-portal-req-approval___view']))
                                                    <li class="@if (isset($model) && $model == 'lpo-portal-req-approval') active @endif">
                                                        <a href="{!! route('lpo-portal-req-approval.index') !!}"><i class="fa fa-circle"></i>
                                                            Approve LPO Changes </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases-reports___view']))
                            <li class="treeview @if (
                                (isset($rmodel) &&
                                    ($rmodel == 'purchases-by-store-location' ||
                                        $rmodel == 'purchases-by-family-group' ||
                                        $rmodel == 'purchases-by-supplier')) ||
                                    (isset($model) && $model == 'lpo-status-and-leadtime-report')) active @endif">
                                <a href="#"><i class="fa fa-share"></i> Reports
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    {{-- @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases-reports___purchases-by-store-location']))
                                        <li class="@if (isset($rmodel) && $rmodel == 'purchases-by-store-location') active @endif">
                                            <a href="{!! route('purchases-by-store-location') !!}"><i class="fa fa-circle"></i>
                                                Purchases by
                                                Store Location</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases-reports___purchases-by-family-group']))
                                        <li class="@if (isset($rmodel) && $rmodel == 'purchases-by-family-group') active @endif">
                                            <a href="{!! route('purchases-by-family-group') !!}"><i class="fa fa-circle"></i>
                                                Purchases by Family
                                                Group </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases-reports___purchases-by-supplier']))
                                        <li class="@if (isset($rmodel) && $rmodel == 'purchases-by-supplier') active @endif">
                                            <a href="{!! route('purchases-by-supplier') !!}"><i class="fa fa-circle"></i>
                                                Purchases by Supplier </a>
                                        </li>
                                    @endif --}}

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases-reports___lpo-status-and-leadtime']))
                                        <li class="@if (isset($model) && $model == 'lpo-status-and-leadtime-report') active @endif">
                                            <a href="{!! route('lpo-status-and-leatime-reports') !!}"><i class="fa fa-circle"></i>
                                                LPO Status & Leadtime </a>
                                        </li>
                                    @endif


                                </ul>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif


            @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-payables___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'maintain-suppliers' ||
                            $model == 'vat-report' ||
                            $model == 'trade-agreement-change-request-list' ||
                            $model == 'supplier-aging-analysis' ||
                            $model == 'supplier-listing' ||
                            $model == 'supplier-bank-listing' ||
                            $model == 'suppliers-invoice' ||
                            $model == 'payment-vouchers' ||
                            $model == 'item-demands' ||
                            $model == 'return-demands' ||
                            $model == 'credit-debit-notes' ||
                            $model == 'pending-suppliers' ||
                            $model == 'processed-invoices' ||
                            $model == 'bank-files' || $model == 'trade-agreement'||
                            $model == 'withholding-files' )) active @endif">
                    <a href="#"><i class="fa fa-fw fa-tree"></i><span>Accounts Payables</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>

                    <ul class="treeview-menu">
                        <li class="treeview @if (isset($model) &&
                        ($model == 'maintain-suppliers' ||
                            $model == 'trade-agreement' )) active @endif">
                            <a href="#"><i class="fa fa-fw fa-users"></i><span>Maintain
                                Suppliers</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>

                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-suppliers___view']))
                                    <li class="@if (isset($model) && $model == 'maintain-suppliers') active @endif">
                                        <a href="{!! route('maintain-suppliers.index') !!}"><i class="fa fa-circle"></i>Suppliers</a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['trade-agreement___view']))
                                    <li class="@if (isset($model) && $model == 'trade-agreement') active @endif">
                                        <a href="{!! route('trade-agreement.index') !!}"><i class="fa fa-circle"></i>Trade Agreements</a>
                                    </li>
                                @endif
                            </ul>
                        </li>

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['suppliers-invoice___view']))
                            <li class="@if (isset($model) && $model == 'suppliers-invoice') active @endif">
                                <a href="{!! route('pending-grns.index') !!}"><i class="fa fa-circle"></i>Pending GRN's</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['suppliers-invoice___view']))
                            <li class="@if (isset($model) && $model == 'processed-invoices') active @endif">
                                <a href="{!! route('maintain-suppliers.processed_invoices.index') !!}"><i class="fa fa-circle"></i>Processed
                                    Invoices</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['payment-vouchers___view']))
                            <li class="@if (isset($model) && $model == 'payment-vouchers') active @endif">
                                <a href="{!! route('payment-vouchers.index') !!}"><i class="fa fa-circle"></i>Payment Vouchers</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-files___view']))
                            <li class="@if (isset($model) && $model == 'bank-files') active @endif">
                                <a href="{!! route('bank-files.index') !!}"><i class="fa fa-circle"></i>Generate Bank File</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['withholding-files___view']))
                            <li class="@if (isset($model) && $model == 'withholding-files') active @endif">
                                <a href="{!! route('withholding-files.index') !!}"><i class="fa fa-circle"></i>Process Withholding Tax</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['credit-debit-notes___view']))
                            <li class="@if (isset($model) && $model == 'credit-debit-notes') active @endif">
                                <a href="{!! route('credit-debit-notes.index') !!}"><i class="fa fa-circle"></i>Credit/Debit
                                    Notes</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || 
                        isset($my_permissions['maintain-suppliers___view']))
                            <li class="treeview @if (request()->routeIs('maintain-suppliers.supplier_unverified_list' , 'maintain-suppliers.supplier_unverified_edit_list')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Approve  Supplier
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>
                                

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-suppliers___approve-new-supplier']))
                                        <li class="@if (request()->routeIs('maintain-suppliers.supplier_unverified_list')) active @endif">
                                            <a href="{!! route('maintain-suppliers.supplier_unverified_list') !!}">
                                                <i class="fa fa-circle"></i> New  Requests
                                            </a>
                                        </li>
                                    @endif

                                      @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-suppliers___approve-edits-supplier']))
                                        <li class="@if (request()->routeIs('maintain-suppliers.supplier_unverified_edit_list')) active @endif">
                                            <a href="{!! route('maintain-suppliers.supplier_unverified_edit_list') !!}">
                                                <i class="fa fa-circle"></i> Edit  Requests
                                            </a>
                                        </li>
                                    @endif


                                </ul>
                            </li>
                        @endif

                        @if (
                            $logged_user_info->role_id == 1 ||
                                isset($my_permissions['maintain-suppliers___trade-agreement-change-request-list']))
                            <li class="treeview @if (isset($model) && $model == 'trade-agreement-change-request-list') active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Approve Price Change
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    @if (
                                        $logged_user_info->role_id == 1 ||
                                            isset($my_permissions['maintain-suppliers___trade-agreement-change-request-list']))
                                        <li class="@if (isset($model) && $model == 'trade-agreement-change-request-list') active @endif">
                                            <a href="{!! route('maintain-suppliers.tradeAgreementChangeRequestList') !!}">
                                                <i class="fa fa-circle"></i> Pending Requests
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['item-demands___view']))
                            <li class="@if (isset($model) && $model == 'item-demands') active @endif">
                                {{-- <a href="{!! route('item-demands.index') !!}"><i class="fa fa-circle"></i>Demands</a> --}}
                                <a href="{!! route('demands.item-demands.new') !!}"><i class="fa fa-circle"></i>Price Demands</a>

                            </li>
                        @endif

                        {{-- Check permissions --}}
                        <li class="@if (isset($model) && $model == 'return-demands') active @endif">
                            <a href="{!! route('return-demands.index') !!}"><i class="fa fa-circle"></i>Return Demands</a>

                        </li>

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-suppliers___view']))
                            <li class="treeview @if (isset($model) && ($model == 'supplier-aging-analysis' || 
                            $model == 'vat-report' || 
                            $model == 'supplier-listing' ||
                            $model == 'supplier-bank-listing')) active 
                        @endif">
                                <a href="#"><i class="fa fa-share"></i><span> Reports</span>
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    <li class="@if (isset($model) && $model == 'supplier-aging-analysis') active @endif">
                                        <a href="{!! route('supplier-aging-analysis.index') . '?start-date=' . date('Y-m-d') !!}">
                                            <i class="fa fa-circle"></i> Supplier Aging Analysis
                                        </a>
                                    </li>

                                    <li class="@if (isset($model) && $model == 'vat-report') active @endif">
                                        <a href="{!! route('vat-report.index') !!}"><i class="fa fa-circle"></i>Vat
                                            Report</a>
                                    </li>

                                    <li class="@if (isset($model) && $model == 'supplier-listing') active @endif">
                                        <a href="{!! route('supplier-listing.index') !!}"><i class="fa fa-circle"></i> Supplier
                                            Listing</a>
                                    </li>

                                    <li class="@if (isset($model) && $model == 'supplier-bank-listing') active @endif">
                                        <a href="{!! route('supplier-bank-listing.index') !!}"><i class="fa fa-circle"></i> Supplier
                                            Bank List</a>
                                    </li>

                                    <li class="@if (isset($model) && $model == 'supplier-statement') active @endif">
                                        <a href="{!! route('maintain-suppliers.supplier-statement') !!}"><i class="fa fa-circle"></i> Supplier
                                            Statement</a>
                                    </li>
                                </ul>
                            </li>

                        @endif


                    </ul>
                </li>
            @endif


            @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'store-c-issue-un-inventoryItems' ||
                            $model == 'inventory-item-adjustment' ||
                            $model == 'store-c-stock-take' ||
                            $model == 'store-c-issue-inventoryItems' ||
                            $model == 'stock-return' ||
                            $model == 'store-c-issue-processed' ||
                            $model == 'store-c-issue' ||
                            $model == 'store-c-requisitions' ||
                            $model == 'store-c-receive' ||
                            $model == 'supreme-store-issue-un-inventoryItems' ||
                            $model == 'inventory-item-adjustment' ||
                            $model == 'supreme-store-stock-take' ||
                            $model == 'supreme-store-issue-inventoryItems' ||
                            $model == 'stock-return' ||
                            $model == 'n-transfers' ||
                            $model == 'supreme-store-issue-processed' ||
                            $model == 'stock-breaking' ||
                            $model == 'stock-auto-breaks' || 
                            $model == 'stock-break-dispatch' ||
                            $model == 'stock-break-dispatched' ||
                            $model == 'supreme-store-issue' ||
                            $model == 'supreme-store-requisitions' ||
                            $model == 'supreme-store-receive' ||
                            $model == 'stock-count-process' ||
                            $model == 'maintain-items' ||
                            $model == 'inventory_sales_report' ||
                            $model == 'maintain-raw-material-items' ||
                            $model == 'confirmed-receive-purchase-order' ||
                            $model == 'receive-purchase-order' ||
                            $model == 'process-receive-purchase-order' ||
                            $model == 'completed-grn' ||
                            $model == 'authorise-requisitions' ||
                            $model == 'stock-takes' ||
                            $model == 'stock-counts' ||
                            $model == 'stock-counts-compare' ||
                            $model == 'weighted-average-history' ||
                            $model == 'n-processed-requisition' ||
                            $model == 'n-internal-requisitions' ||
                            $model == 'n-authorise-requisitions' ||
                            $model == 'processed-requisition' ||
                            $model == 'n-issue-fullfill-requisition' ||
                            $model == 'deviation-report' || 
                            $model == 'returned-receive-purchase-order' ||
                            $model == 'inventory_location_stock_summary' ||
                            $model == 'batch-price-change' ||
                            $model == 'single-price-change' ||
                            $model == 'price-change-history-list' ||
                            $model == 'return-accepted-receive-order' ||
                            $model == 'return-to-supplier-from-grn' ||
                            $model == 'processed-grns' ||
                            $model == 'price-update-upload' ||
                            $model == 'maintain-items-manual-cost-change' ||
                            $model == 'inventory-reports' ||
                            $model == 'items-data-purchases' ||
                            $model == 'pending-price-change-requests' ||
                            $model == 'items_negetive_listing' ||
                            $model == 'max-stock-report' ||
                            $model == 'item-sales-route-performance-report' ||
                            $model == 'average-sales-report' ||
                            $model == 'grn-summary-by-supplier-report' ||
                            $model == 'out-of-stock-report' ||
                            $model == 'discount-items-report' ||
                            $model == 'promotion-items-report' ||
                            $model == 'no-supplier-items-report' ||
                            $model == 'return-to-supplier-from-store-create' ||
                            $model == 'return-to-supplier-from-store-pending' ||
                            $model == 'return-to-supplier-from-store-approve' ||
                            $model == 'return-to-supplier-from-store-approved' ||
                            $model == 'pending-new-approval') ||
                            $model == 'pending-edit-approval' ||
                            $model == 'disaproved-approval'
                        )) active @endif">
                    <a href="#"><i class="fa fa-fw fa-building"></i><span>Inventory</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-item___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'stock-breaking' ||
                                        $model == 'single-price-change' ||
                                        $model == 'maintain-items' ||
                                        $model == 'price-change-history-list' ||
                                        $model == 'maintain-raw-material-items' ||
                                        $model == 'weighted-average-history' ||
                                        $model == 'batch-price-change' ||
                                        $model == 'price-update-upload' ||
                                        $model == 'stock-auto-breaks' ||
                                        $model == 'stock-break-dispatch' ||
                                        $model == 'maintain-items-manual-cost-change' ||
                                        $model == 'pending-new-approval') ||
                                        $model == 'pending-edit-approval' ||
                                        $model == 'disaproved-approval')) active @endif">
                                <a href="#"><i class="fa fa-circle"></i> Maintain Item
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___view']))
                                        <li class="@if (isset($model) && $model == 'maintain-items') active @endif">
                                            <a href="{!! route('maintain-items.index') !!}"><i class="fa fa-circle"></i> Manage
                                                Items</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-item-adjustment___view']))
                                        <li class="@if (isset($model) && $model == 'inventory-item-adjustment') active @endif">
                                            <a href="{!! route('inventory-item-adjustment.index') !!}"><i class="fa fa-circle"></i>
                                                Inventory
                                                Adjustment</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___manage-standard-cost']))
                                        <li class="treeview @if (
                                            (isset($model) && $model == 'single-price-change') ||
                                                $model == 'price-change-history-list' ||
                                                $model == 'pending-price-change-requests' ||
                                                $model == 'batch-price-change' ||
                                                $model == 'price-update-upload' ||
                                                $model == 'maintain-items-manual-cost-change') active @endif">
                                            <a href="#">
                                                <i class="fa fa-circle"></i> Price Change
                                                <span class="pull-right-container"><i
                                                            class="fa fa-angle-left pull-right"></i></span>
                                            </a>

                                            <ul class="treeview-menu">
                                                {{-- <li class="@if (isset($model) && $model == 'single-price-change') active @endif">
                                                    <a href="{!! route('maintain-items.standard.cost') !!}"><i class="fa fa-circle"></i>Single Price Change</a>
                                                </li> --}}

                                                <li class="@if (isset($model) && $model == 'batch-price-change') active @endif">
                                                    <a href="{!! route('price-change.batch-requests') !!}"><i
                                                                class="fa fa-circle"></i>Price Change</a>
                                                </li>

                                                {{-- <li class="@if (isset($model) && $model == 'pending-price-change-requests') active @endif">
                                                    <a href="{!! route('maintain-items.item_price_pending_list') !!}">
                                                        <i class="fa fa-circle"></i> Pending Requests
                                                    </a>
                                                </li> --}}

                                                <li class="@if (isset($model) && ($model == 'maintain-items' || $model == 'price-change-history-list')) active @endif">
                                                    <a href="{!! route('maintain-items.item_price_history_list') !!}">
                                                        <i class="fa fa-circle"></i> Price Change History
                                                    </a>
                                                </li>

                                                <li>
                                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___manage-standard-cost-manual']))
                                                    <li class="@if ((isset($model) && $model == 'maintain-items') || $model == 'maintain-items-manual-cost-change') active @endif"><a
                                                                href="{!! route('maintain-items.manual-cost-change') !!}"><i class="fa fa-circle"></i>
                                                            Manual Cost Change</a></li>
                                            @endif
                                        </li>


                                        {{--                                                <li class="@if (isset($model) && $model == 'price-update-upload') active @endif"> --}}
                                        {{--                                                    <a href="{!! route('price-update.upload-page') !!}"> --}}
                                        {{--                                                        <i class="fa fa-circle"></i> Upload Prices --}}
                                        {{--                                                    </a> --}}
                                        {{--                                                </li> --}}
                                </ul>
                            </li>
                        @endif


                        @if ($logged_user_info->role_id == 1 ||  isset($my_permissions['stock-breaking___view'] )|| isset($my_permissions['stock-auto-breaks___view']) ||
                        isset($my_permissions['stock-break-dispatched ___view']) ||
                        isset($my_permissions['stock-break-completed ___view']) ||
                        isset($my_permissions['stock-break-dispatch___view'] ))
                            <li class="treeview @if (
                                            (isset($model) && 
                                                $model == 'stock-breaking') ||
                                                $model == 'stock-auto-breaks' ||
                                                $model == 'stock-break-dispatch') 
                                                active 
                                                @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Stocks Break
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-breaking___view']))
                                        <li class="@if (isset($model) && $model == 'stock-breaking') active @endif"><a href="{!! route('stock-breaking.index') !!}"><i
                                                        class="fa fa-circle"></i> Stock
                                                Breaking</a></li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___view']))
                                        <li class="@if (isset($model) && $model == 'stock-auto-breaks') active @endif">
                                            <a href="{!! route('stock-auto-breaks.index') !!}"><i class="fa fa-circle"></i> Auto Breaks</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___dispatch']))
                                        <li class="@if (isset($model) && $model == 'stock-break-dispatch') active @endif">
                                            <a href="{!! route('stock-auto-breaks.dispatch.list') !!}"><i class="fa fa-circle"></i> Pending Dispatches</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___dispatch']))
                                        <li class="@if (isset($model) && $model == 'stock-break-dispatched') active @endif">
                                            <a href="{!! route('stock-auto-breaks.dispatched.list') !!}"><i class="fa fa-circle"></i> Dispatched Breaks</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___dispatch']))
                                        <li class="@if (isset($model) && $model == 'stock-break-completed') active @endif">
                                            <a href="{!! route('stock-auto-breaks.dispatch.completed') !!}"><i class="fa fa-circle"></i> Processed Breaks</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif





                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['weighted-average-history___view']))
                            <li class="@if (isset($model) && $model == 'weighted-average-history') active @endif"><a href="{!! route('weighted-average-history.index') !!}"><i
                                            class="fa fa-circle"></i> Weighted Averages</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___manage-approval']))
                            <li class="treeview @if (
                                (isset($model) && $model == 'pending-new-approval') ||
                                    $model == 'pending-edit-approval' ||
                                    $model == 'disaproved-approval') active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Item Approval
                                    <span class="pull-right-container"><i
                                                class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    <li class="@if (isset($model) && $model == 'pending-new-approval') active @endif">
                                        <a href="{!! route('item-approval','pending-new-approval') !!}"><i
                                                    class="fa fa-circle"></i>Pending New Approval</a>
                                    </li>
                                    <li class="@if (isset($model) && $model == 'pending-edit-approval') active @endif">
                                        <a href="{!! route('item-approval','pending-edit-approval') !!}"><i
                                                    class="fa fa-circle"></i>Pending Edit Approval</a>
                                    </li>
                                    <li class="@if (isset($model) && $model == 'disaproved-approval') active @endif">
                                        <a href="{!! route('item-approval','disaproved-approval') !!}"><i
                                                    class="fa fa-circle"></i>Disaproved Approval</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-purchase-orders___view']))
                <li class="treeview @if (isset($model) &&
                    ($model == 'confirmed-receive-purchase-order' ||
                        $model == 'process-receive-purchase-order' ||
                        $model == 'receive-purchase-order' ||
                        $model == 'completed-grn')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Purchase Orders
                        <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">


                        <li class="treeview @if (isset($model) &&
                            ($model == 'confirmed-receive-purchase-order' ||
                                $model == 'process-receive-purchase-order' ||
                                
                                $model == 'receive-purchase-order')) active @endif">
                            <a href="#"><i class="fa fa-circle"></i> Receive Purchases
                                <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                            </a>
                            <ul class="treeview-menu">

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['receive-purchase-order___view']))
                                    <li class="@if (isset($model) && $model == 'receive-purchase-order') active @endif">
                                        <a href="{!! route('receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Initiate GRN
                                        </a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['process-receive-purchase-order___view']))
                                    <li class="@if (isset($model) && $model == 'process-receive-purchase-order') active @endif"><a
                                                href="{!! route('process-receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Approve GRN
                                            Request </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['confirmed-receive-purchase-order___view']))
                                    <li class="@if (isset($model) && $model == 'confirmed-receive-purchase-order') active @endif"><a
                                                href="{!! route('confirmed-receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Confirm GRN</a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['completed-grn___view']))
                            <li class="@if (isset($model) && $model == 'completed-grn') active @endif"><a
                                        href="{!! route('completed-grn.index') !!}"><i class="fa fa-circle"></i>
                                    Completed GRN</a></li>
                        @endif
                    </ul>
                </li>
            @endif


            {{--                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-return___view'])) --}}
            {{--                            <li class="@if (isset($model) && $model == 'stock-return') active @endif"> --}}
            {{--                                <a href="{!! route('stock-return.index') !!}"><i class="fa fa-circle"></i> Return to Supplier </a> --}}
            {{--                            </li> --}}
            {{--                        @endif --}}

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier___view']))
                <li class="treeview @if (isset($model) && (
                    $model == 'return-to-supplier-from-grn' ||
                    $model == 'returned-receive-purchase-order' ||
                    $model == 'processed-grns' ||
                    $model == 'return-accepted-receive-order' ||
                    $model == 'return-to-supplier-from-store-create' ||
                    $model == 'return-to-supplier-from-store-pending' ||
                    $model == 'return-to-supplier-from-store-approve' ||
                    $model == 'return-to-supplier-from-store-approved'
                )) active @endif">
                    <a href="#">
                        <i class="fa fa-circle"></i> Return To Supplier
                        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                    </a>

                    <ul class="treeview-menu">
                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-grn') active @endif">
                            <a href="{!! route('return-to-supplier.from-grn.show') !!}"><i class="fa fa-circle"></i> Return From GRN </a>
                        </li>

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view']))
                            <li class="treeview @if (isset($model) && (
                                $model == 'return-to-supplier-from-store-create' ||
                                $model == 'return-to-supplier-from-store-pending' ||
                                $model == 'return-to-supplier-from-store-approve' ||
                                $model == 'return-to-supplier-from-store-approved'
                            )) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Return From Store
                                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___create']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-create') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.create') !!}"><i class="fa fa-circle"></i> Create return </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view-pending']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-pending') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.pending') !!}"><i class="fa fa-circle"></i> Pending returns </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view-approved']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-approved') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.approved') !!}"><i class="fa fa-circle"></i> Approved returns </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        <li class="@if (isset($model) && $model == 'processed-grns') active @endif">
                            <a href="{!! route('return-to-supplier.processed-returns') !!}"><i class="fa fa-circle"></i> Processed Returns </a>
                        </li>

                        <li class="@if (isset($model) && $model == 'returned-receive-purchase-order') active @endif">
                            <a href="{!! route('returned-receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Pending Portal Request </a>
                        </li>
                        <li class="@if (isset($model) && $model == 'return-accepted-receive-order') active @endif">
                            <a href="{!! route('return-accepted-receive-order.index') !!}"><i class="fa fa-circle"></i> Returned Credit Notes </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                <li class="treeview @if (isset($model) &&
                    ($model == 'confirmed-receive-purchase-order' ||
                        $model == 'n-transfers' ||
                        $model == 'stock-return' ||
                        $model == 'process-receive-purchase-order' ||
                        $model == 'receive-purchase-order' ||
                        $model == 'completed-grn')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i>Inter-branch Transfers
                        <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">


                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                            <li class="@if (isset($model) && $model == 'n-transfers') active @endif"><a
                                        href="{!! route('n-transfers.index') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i> Initiate Transfer</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                            <li class="@if (isset($model) && $model == 'n-transfers') active @endif"><a
                                        href="{!! route('n-transfers.indexReceive') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i> Receive Transfer</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                            <li class="@if (isset($model) && $model == 'n-transfers') active @endif"><a
                                        href="{!! route('n-transfers.indexProcessed') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i> Processed Transfers</a>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['internal-requisitions___view']))
                <li class="treeview @if (isset($model) &&
                    ($model == 'processed-requisition' ||
                        $model == 'n-internal-requisitions' ||
                        $model == 'n-authorise-requisitions' ||
                        $model == 'n-issue-fullfill-requisition')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Internal Requisition
                        <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['internal-requisitions___view']))
                            <li class="@if (isset($model) && $model == 'n-internal-requisitions') active @endif"><a
                                        href="{!! route('n-internal-requisitions.index') !!}"><i class="fa fa-circle"></i> New Stock Request</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['n-authorise-requisitions___view']))
                            <li class="@if (isset($model) && $model == 'authorise-requisitions') active @endif"><a
                                        href="{!! route('n-authorise-requisitions.index') !!}"><i class="fa fa-circle"></i>Authorise
                                    Requisition</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['issue-fullfill-requisition___view']))
                            <li class="@if (isset($model) && $model == 'n-issue-fullfill-requisition') active @endif"><a
                                        href="{!! route('n-issue-fullfill-requisition.index') !!}"><i class="fa fa-circle"></i> Issue/Fullfill
                                    Requisition</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['processed-requisition___view']))
                            <li class="@if (isset($model) && $model == 'processed-requisition') active @endif"><a
                                        href="{!! route('processed-requisition.index') !!}"><i class="fa fa-circle"></i>Processed
                                    Requisition</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            @if (
                $logged_user_info->role_id == 1 ||
                    isset($my_permissions['stock-take___view']) ||
                    isset($my_permissions['stock-counts___view']))
                <li class="treeview @if (isset($model) &&
                    ($model == 'stock-count-process' ||
                        $model == 'stock-variance' ||
                        $model == 'stock-takes' ||
                        $model == 'stock-counts' ||
                        $model == 'stock-counts-compare' ||
                        $model == 'deviation-report')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Stock Take
                        <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-take___view']))
                            <li class="@if (isset($model) && $model == 'stock-takes') active @endif"><a
                                        href="{!! route('admin.stock-takes.create-stock-take-sheet') !!}"><i class="fa fa-circle"></i>
                                    Create Stock Take Sheet</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-counts___view']))
                            <li class="@if (isset($model) && $model == 'stock-counts') active @endif"><a
                                        href="{!! route('admin.stock-counts') !!}"><i class="fa fa-circle"></i> Enter
                                    Stock Counts</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['compare-counts-vs-stock-check___view']))
                            <li class="@if (isset($model) && $model == 'stock-counts-compare') active @endif">
                                <a href="{!! route('admin.stock-counts.compare-counts-vs-stock-check') !!}"><i class="fa fa-circle"></i> Compare
                                    Counts Vs Stock Check Data</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-count-process___view']))
                            <li class="@if (isset($model) && $model == 'stock-count-process') active @endif">
                                <a href="{!! route('admin.stock-counts.stock-count-process') !!}"><i class="fa fa-circle"></i> Stock
                                    Count Process</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['deviation-report___view']))
                            <li class="@if (isset($model) && $model == 'deviation-report') active @endif"><a
                                        href="{!! route('admin.stock-counts.deviation-report') !!}"><i class="fa fa-circle"></i>
                                    Deviation Reports</a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-variance___view']))
                            <li class="@if (isset($model) && $model == 'stock-variance') active @endif"><a
                                        href="{!! route('admin.stock-variance.index') !!}"><i class="fa fa-circle"></i> Stock
                                    Variance</a></li>
                        @endif
                    </ul>
                </li>
            @endif



            {{-- Utility Module Start --}}


            @if (
                $logged_user_info->role_id == 1 ||
                isset($my_permissions['utility___view']))
                <li class="treeview @if (isset($model) &&
                    ($model == 'utilities')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Utility
                        <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___view']))
                            <li class="@if (isset($model) && $model == 'sutilities') active @endif"><a
                                        href="{!! route('admin.utility.update-max-stock-and-reorder-level') !!}"><i class="fa fa-circle"></i>
                                    Update Max Stock / Reorder Level</a></li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- Utility Module End --}}


            @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___view']))
                <li class="treeview @if (isset($model) &&
                    ($model == 'inventory-reports' ||
                        $model == 'items_negetive_listing' ||
                        $model == 'inventory_location_stock_summary' ||
                        $model == 'maintain-items' ||
                        $model == 'inventory_sales_report' ||
                        $model == 'items-data-purchases' ||
                        $model == 'max-stock-report' ||
                        $model == 'average-sales-report' ||
                        $model == 'grn-summary-by-supplier-report' ||
                        $model == 'out-of-stock-report' ||
                        $model == 'discount-items-report' ||
                        $model == 'promotion-items-report' ||
                        $model == 'item-sales-route-performance-report' ||
                        $model == 'no-supplier-items-report')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i>Reports
                        <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___inventory-valuation-report']))
                            <li class="@if (isset($model) && $model == 'inventory_sales_report') active @endif"><a
                                        href="{!! route('summary_report.inventory_sales_report') !!}"><i class="fa fa-circle"></i> Valuation Report</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___suggested_order_report']))
                            <li class="@if (isset($model) && $model == 'maintain-items') active @endif"><a
                                        href="{!! route('reports.suggested_order_report') !!}"><i class="fa fa-circle"></i>Suggested Order
                                    Report</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___negetive_stock_report']))
                            <li class="@if (isset($model) && $model == 'items_negetive_listing') active @endif"><a
                                        href="{!! route('reports.items_negetive_listing') !!}"><i class="fa fa-circle"></i>Inventory -Ve Stock
                                    Report</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___inventory-location-stock-report']))
                            <li class="@if (isset($model) && $model == 'inventory_location_stock_summary') active @endif"><a
                                        href="{!! route('reports.inventory_location_stock_summary') !!}"><i class="fa fa-circle"></i>Location Stock
                                    Report</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___supplier-product-reports']))
                            <li class=""><a href="{!! route('inventory-reports.supplier-product-reports') !!}"><i class="fa fa-circle"></i>
                                    Supplier Product Reports</a></li>
                        @endif

                        {{-- @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___supplier-product-reports']))
                            <li class=""><a href="{!! route('inventory-reports.supplier-product-reports2') !!}"><i class="fa fa-circle"></i>
                                    Supplier Product Reports 2</a></li>
                        @endif --}}

                        {{-- @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___grn-reports']))
                                <li class=""><a href="{!! route('inventory-reports.grn-reports') !!}"><i
                                                class="fa fa-circle"></i> Deliveries
                                        by Suppliers</a></li>
                            @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___grn-summary']))
                            <li class=""><a href="{!! route('inventory-reports.grn-summary') !!}"><i class="fa fa-circle"></i> GRN
                                    Summary</a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___export-transfer-general']))
                            <li class=""><a href="{!! route('inventory-reports.export-transfer-general') !!}"><i class="fa fa-circle"></i>
                                    Internal Transfers</a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___export-internal-requisitions']))
                            <li class=""><a href="{!! route('inventory-reports.export-internal-requisitions') !!}"><i class="fa fa-circle"></i>
                                    Internal Issues</a></li>

                        @endif


                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___location-wise-movement']))
                            <li class=""><a href="{!! route('inventory-reports.location-wise-movement') !!}"><i class="fa fa-circle"></i>
                                    Location wise Product Movement</a></li>
                        @endif


                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___inventory-valuation-report']))
                            <li class=""><a href="{!! route('inventory-reports.inventory-valuation-report') !!}"><i class="fa fa-circle"></i>
                                    Inventory Valuation - SOH </a></li>
                        @endif


                        <li class=""><a href="{!! route('inventory-reports.inventory-moment-reports') !!}"><i class="fa fa-circle"></i>
                                Inventory Movement Report </a></li>

                            <li class=""><a
                                        href="{!! route('inventory-reports.delivery-note-reports') . getReportDefaultFilterForTrialBalance() !!}"><i
                                            class="fa fa-circle"></i> Delivery Note Report </a></li> --}}


                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___max-stock-report']))
                            <li class="@if (isset($model) && $model == 'max-stock-report') active @endif">
                                <a href="{!! route('inventory-reports.max-stock-report.index') !!}">
                                    <i class="fa fa-circle"></i>Max Stock Report</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___average-sales-report']))
                            <li class="@if (isset($model) && $model == 'average-sales-report') active @endif">
                                <a href="{!! route('inventory-reports.average-sales-report.index') !!}">
                                    <i class="fa fa-circle"></i>Average Sales Vs Max Stock</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___grn-summary-by-supplier-report']))
                            <li class="@if (isset($model) && $model == 'grn-summary-by-supplier-report') active @endif">
                                <a href="{!! route('inventory-reports.grn-summary-by-supplier-report.index') !!}">
                                    <i class="fa fa-circle"></i>GRN Summary by Supplier</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___out-of-stock-report']))
                            <li class="@if (isset($model) && $model == 'out-of-stock-report') active @endif">
                                <a href="{!! route('inventory-reports.out-of-stock-report') !!}">
                                    <i class="fa fa-circle"></i>Out of Stock Report</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___no-supplier-items-report']))
                            <li class="@if (isset($model) && $model == 'no-supplier-items-report') active @endif">
                                {{-- <a href="{!! route('inventory-reports.no-supplier-items-report') !!}"> --}}
                                <a href="#">
                                    <i class="fa fa-circle"></i>No Supplier Items Report</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___child-vs-mother-qoh']))
                            <li class="@if (isset($model) && $model == 'maintain-items') active @endif">
                                <a href="{!! route('child-vs-mother-qoh') !!}">
                                    <i class="fa fa-circle"></i>Child Vs Mother Qoh</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___discount-items']))
                            <li class="@if (isset($model) && $model == 'discount-items-report') active @endif">
                                <a href="{!! route('items-with-discounts-reports') !!}">
                                    <i class="fa fa-circle"></i>Discount Items</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___promotion-items']))
                            <li class="@if (isset($model) && $model == 'promotion-items-report') active @endif">
                                <a href="{!! route('items-with-promotions-reports') !!}">
                                    <i class="fa fa-circle"></i>Promotion Items</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___items-data-sales']))
                           <li class="@if (isset($model) && $model == 'items-data-sales') active @endif">
                                <a href="{!! route('reports.items-data-sales') !!}">
                                    <i class="fa fa-circle"></i>Items Data Sales Report</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___items-data-purchases']))
                           <li class="@if (isset($model) && $model == 'items-data-purchases') active @endif">
                                <a href="{!! route('reports.items_data_purchase_report') !!}">
                                    <i class="fa fa-circle"></i>Items Data Purchases Report</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___item-sales-route-performance-report']))
                           <li class="@if (isset($model) && $model == 'item-sales-route-performance-report') active @endif">
                                <a href="{!! route('reports.route_performance_report') !!}">
                                    <i class="fa fa-circle"></i>Item Sales Route Performance</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        </ul>
        </li>
        @endif
        {{--
        <li class="treeview {{ Request::is('admin/gender') || Request::is('admin/gender/*') || Request::is('admin/employment-type/*') || Request::is('admin/employment-type') || Request::is('admin/employment-status') || Request::is('admin/jobGrade') || Request::is('admin/jobGrade/*') || Request::is('admin/employment-status/*') || Request::is('admin/jobGroup/*') || Request::is('admin/jobGroup') || Request::is('admin/jobTitles/*') || Request::is('admin/jobTitles') || Request::is('admin/salutation/*') || Request::is('admin/salutation') || Request::is('admin/nationality/*') || Request::is('admin/nationality') || Request::is('admin/marital-status/*') || Request::is('admin/marital-status') || Request::is('admin/termination-types/*') || Request::is('admin/termination-types') || Request::is('admin/indiscipline-category/*') || Request::is('admin/indiscipline-category') || Request::is('admin/indiscipline-action/*') || Request::is('admin/indiscipline-action')|| Request::is('admin/education-level/*') || Request::is('admin/education-level') || Request::is('admin/nhif') || Request::is('admin/nhif/*') || Request::is('admin/paye') || Request::is('admin/paye/*') || Request::is('admin/allowance') || Request::is('admin/allowance/*') || Request::is('admin/commission') || Request::is('admin/commission/*') || Request::is('admin/sacco') || Request::is('admin/sacco/*') || Request::is('admin/loan-type') || Request::is('admin/loan-type/*') || Request::is('admin/pension') || Request::is('admin/pension/*') || Request::is('admin/non-cash-benefit') || Request::is('admin/non-cash-benefit/*') || Request::is('admin/relief') || Request::is('admin/relief/*') || Request::is('admin/custom-parameter') || Request::is('admin/custom-parameter/*') || Request::is('admin/bank') || Request::is('admin/bank/*') || Request::is('admin/branch') || Request::is('admin/branch/*') || Request::is('admin/payment-modes') || Request::is('admin/payment-modes/*') || Request::is('admin/payment-frequency') || Request::is('admin/payment-frequency/*') || Request::is('admin/emp-list') || Request::is('admin/emp-list/*') || Request::is('admin/employee-manage') || Request::is('admin/employee-manage/*') || Request::is('admin/emp/contract') || Request::is('admin/emp/contract/*') || Request::is('admin/emp/indisciplineCategory') || Request::is('admin/emp/indisciplineCategory/*')  || Request::is('admin/emp/separation') || Request::is('admin/emp/separation/*')  || Request::is('admin/emp/separation-termnation') || Request::is('admin/emp/separation-termnation/*') || Request::is('admin/emp/ApproveTermination') || Request::is('admin/emp/ApproveTermination/*')  || Request::is('admin/emp/TerminatedStaff') || Request::is('admin/emp/TerminatedStaff/*') || Request::is('admin/emp/PayrollMaster') || Request::is('admin/emp/Payroll/*') || Request::is('admin/emp/PayrollAbsend') || Request::is('admin/emp/PayrollAbsend/*')|| Request::is('admin/emp/OvertimeHours') || Request::is('admin/emp/OvertimeHours/*')|| Request::is('admin/emp/loan-Entries') || Request::is('admin/emp/loan-Entries/*') || Request::is('admin/emp/salaryReview') || Request::is('admin/emp/salaryReview/*') || Request::is('admin/emp/leaveConfig') || Request::is('admin/emp/leaveConfig/*')  || Request::is('admin/emp/AssignLeaveIndex') || Request::is('admin/emp/AssignLeaveIndex/*') || Request::is('admin/emp/entitlements/index') || Request::is('admin/emp/entitlements/index/*') || Request::is('admin/emp/HrApproval') || Request::is('admin/emp/HrApproval/*') || Request::is('admin/emp/ManagerApproval/index') || Request::is('admin/emp/ManagerApproval/manage/*')  || Request::is('admin/emp/leave_status/index') || Request::is('admin/emp/leave_status/index*')  ||  Request::is('admin/emp/LeaveRecalls') || Request::is('admin/emp/LeaveRecalls/*') || Request::is('admin/emp/LeaveReversal') || Request::is('admin/emp/LeaveReversal/*') || Request::is('admin/emp/leaveHistory') || Request::is('admin/emp/leaveHistory/*') || Request::is('admin/emp/LeaveBalances') || Request::is('admin/emp/LeaveBalances/*') || Request::is('admin/emp/LeaveRecallsReport') || Request::is('admin/emp/LeaveRecallsReport/*') || Request::is('admin/emp/ProcessPayroll/index') || Request::is('admin/emp/ProcessPayroll/index/*') || Request::is('admin/emp/ProcessPayroll/paysliproll') || Request::is('admin/emp/ProcessPayroll/paysliproll/*')  || Request::is('admin/emp/staffpayslips') || Request::is('admin/emp/staffpayslips/*')
                || Request::is('admin/emp/ReversalReport') || Request::is('admin/emp/ReversalReport/*') || Request::is('admin/emp/payrollreport') || Request::is('admin/emp/payrollreport/*') ? 'active' : '' }}" >
                    <a href="#"><i class="fa fa-shopping-basket"></i><span>HR And Payroll </span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                     <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/gender') || Request::is('admin/gender/*') ||  Request::is('admin/employment-type') || Request::is('admin/employment-type/*') || Request::is('admin/employment-status') || Request::is('admin/jobGrade/*') || Request::is('admin/jobGrade') || Request::is('admin/employment-status/*') || Request::is('admin/jobGroup/*') || Request::is('admin/jobGroup') || Request::is('admin/jobTitles/*') || Request::is('admin/jobTitles') || Request::is('admin/salutation/*') || Request::is('admin/salutation') || Request::is('admin/nationality/*') || Request::is('admin/nationality') || Request::is('admin/marital-status/*') || Request::is('admin/marital-status') || Request::is('admin/termination-types/*') || Request::is('admin/termination-types') || Request::is('admin/indiscipline-category/*') || Request::is('admin/indiscipline-category') || Request::is('admin/indiscipline-action/*') || Request::is('admin/indiscipline-action') || Request::is('admin/education-level/*') || Request::is('admin/education-level')
                          || Request::is('admin/nhif') || Request::is('admin/nhif/*') || Request::is('admin/paye') || Request::is('admin/paye/*') || Request::is('admin/allowance') || Request::is('admin/allowance/*') || Request::is('admin/commission') || Request::is('admin/commission/*') || Request::is('admin/sacco') || Request::is('admin/sacco/*') || Request::is('admin/loan-type') || Request::is('admin/loan-type/*') || Request::is('admin/pension') || Request::is('admin/pension/*') || Request::is('admin/non-cash-benefit') || Request::is('admin/non-cash-benefit/*') || Request::is('admin/relief') || Request::is('admin/relief/*') || Request::is('admin/custom-parameter') || Request::is('admin/custom-parameter/*') || Request::is('admin/bank') || Request::is('admin/bank/*') || Request::is('admin/branch') || Request::is('admin/branch/*') || Request::is('admin/payment-modes') || Request::is('admin/payment-modes/*') || Request::is('admin/payment-frequency') || Request::is('admin/payment-frequency/*') ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Configurations</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/gender') || Request::is('admin/gender/*') || Request::is('admin/employment-type') || Request::is('admin/employment-type/*') || Request::is('admin/employment-status') || Request::is('admin/jobGrade/*') || Request::is('admin/jobGrade') || Request::is('admin/employment-status/*') || Request::is('admin/jobGroup/*') || Request::is('admin/jobGroup') || Request::is('admin/jobTitles/*') || Request::is('admin/jobTitles') || Request::is('admin/salutation/*') || Request::is('admin/salutation') || Request::is('admin/nationality/*') || Request::is('admin/nationality') || Request::is('admin/marital-status/*') || Request::is('admin/marital-status')  || Request::is('admin/emp/LeaveBalances') || Request::is('admin/emp/LeaveBalances/*') ? 'active' : '' }} ">
                            <a href="#"><i class="fa fa-adjust"></i><span>HR Configurations</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>

                            <ul class="treeview-menu">
                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/gender') || Request::is('admin/gender/*') ? 'active' : '' }}"><a href="{!! route('gender.index')!!}"><i class="fa fa-circle"></i>Gender</a></li>

                                <li class= "{{ Request::is('admin/employment-type') || Request::is('admin/employment-type/*') ? 'active' : '' }}"><a href="{!! route('employment-type.index')!!}"><i class="fa fa-circle"></i>Employment Type</a></li>

                                <li class= "{{ Request::is('admin/employment-status') || Request::is('admin/employment-status/*') ? 'active' : '' }}"><a href="{!! route('employment-status.index')!!}"><i class="fa fa-circle"></i>Employment Status</a></li>

                                <li class= "{{ Request::is('admin/jobGrade') || Request::is('admin/jobGrade/*') ? 'active' : '' }}"><a href="{!! route('JobGrade.index')!!}"><i class="fa fa-circle"></i>Job Grade</a></li>

                                <li class= "{{ Request::is('admin/jobGroup/*') || Request::is('admin/jobGroup') ? 'active' : '' }}"><a href="{!! route('jobGroup.index')!!}"><i class="fa fa-circle"></i>Job Group</a></li>

                                <li class= "{{ Request::is('admin/jobTitles/*') || Request::is('admin/jobTitles') ? 'active' : '' }}"><a href="{!! route('JobTitles.index')!!}"><i class="fa fa-circle"></i>Job Title</a></li>

                                <li class= "{{ Request::is('admin/salutation/*') || Request::is('admin/salutation') ? 'active' : '' }}"><a href="{!! route('Salutation.index')!!}"><i class="fa fa-circle"></i>Salutation</a></li>
                                <li class= "{{ Request::is('admin/nationality/*') || Request::is('admin/nationality') ? 'active' : '' }}"><a href="{!! route('Nationality.index')!!}"><i class="fa fa-circle"></i>Nationality</a></li>

                                <li class= "{{ Request::is('admin/marital-status/*') || Request::is('admin/marital-status') ? 'active' : '' }}"><a href="{!! route('marital-status.index')!!}"><i class="fa fa-circle"></i>Marital Status</a></li>
                              @endif

                            </ul>
                          </li>
                          @endif

                          @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/termination-types') || Request::is('admin/termination-types/*') || Request::is('admin/indiscipline-category/*') || Request::is('admin/indiscipline-category') || Request::is('admin/indiscipline-action/*') || Request::is('admin/indiscipline-action') || Request::is('admin/education-level/*') || Request::is('admin/education-level')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Other Settings</span>
                                  <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu {{ Request::is('admin/termination-types') || Request::is('admin/termination-types/*') || Request::is('admin/indiscipline-category/*') || Request::is('admin/indiscipline-category') || Request::is('admin/indiscipline-action/*') || Request::is('admin/indiscipline-action') || Request::is('admin/education-level/*') || Request::is('admin/education-level') ? 'active' : '' }}  ">
                                 @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                                <li class= "{{ Request::is('admin/termination-types') || Request::is('admin/termination-types/*') ? 'active' : '' }}"><a href="{!! route('termination-types.index')!!}"><i class="fa fa-circle"></i>Termination Types</a></li>
                                <li class= "{{ Request::is('admin/indiscipline-category') || Request::is('admin/indiscipline-category/*') ? 'active' : '' }}"><a href="{!! route('indiscipline-category.index')!!}"><i class="fa fa-circle"></i>Indiscipline Category</a></li>
                                <li class= "{{ Request::is('admin/indiscipline-action') || Request::is('admin/indiscipline-action/*') ? 'active' : '' }}"><a href="{!! route('indiscipline-action.index')!!}"><i class="fa fa-circle"></i>Indiscipline Action</a></li>
                                <li class= "{{ Request::is('admin/education-level') || Request::is('admin/education-level/*') ? 'active' : '' }}"><a href="{!! route('education-level.index')!!}"><i class="fa fa-circle"></i>Education Level</a></li>
                                 @endif
                            </ul>
                          </li>
                           @endif

                           @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/nhif') || Request::is('admin/nhif/*') || Request::is('admin/paye') || Request::is('admin/paye/*') || Request::is('admin/allowance') || Request::is('admin/allowance/*') || Request::is('admin/commission') || Request::is('admin/commission/*') || Request::is('admin/sacco') || Request::is('admin/sacco/*') || Request::is('admin/loan-type') || Request::is('admin/loan-type/*') || Request::is('admin/pension') || Request::is('admin/pension/*') || Request::is('admin/non-cash-benefit') || Request::is('admin/non-cash-benefit/*') || Request::is('admin/relief') || Request::is('admin/relief/*') || Request::is('admin/custom-parameter') || Request::is('admin/custom-parameter/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Payroll Settings</span>
                                  <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu {{  Request::is('admin/nhif') || Request::is('admin/nhif/*') || Request::is('admin/paye') || Request::is('admin/paye/*') || Request::is('admin/allowance') || Request::is('admin/allowance/*') || Request::is('admin/commission') || Request::is('admin/commission/*') || Request::is('admin/sacco') || Request::is('admin/sacco/*') || Request::is('admin/loan-type') || Request::is('admin/loan-type/*') || Request::is('admin/pension') || Request::is('admin/pension/*') || Request::is('admin/non-cash-benefit') || Request::is('admin/non-cash-benefit/*') || Request::is('admin/relief') || Request::is('admin/relief/*') || Request::is('admin/custom-parameter') || Request::is('admin/custom-parameter/*') || Request::is('admin/payment-modes') || Request::is('admin/payment-modes/*') ? 'active' : '' }}  ">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                                <li class= "{{ Request::is('admin/nhif') || Request::is('admin/nhif/*') ? 'active' : '' }}"><a href="{!! route('Nhif.index')!!}"><i class="fa fa-circle"></i>NHIF</a></li>

                                <li class= "{{ Request::is('admin/paye') || Request::is('admin/paye/*') ? 'active' : '' }}"><a href="{!! route('Paye.index')!!}"><i class="fa fa-circle"></i>PAYE</a></li>

                                <li class= "{{ Request::is('admin/allowance') || Request::is('admin/allowance/*') ? 'active' : '' }}"><a href="{!! route('Allowance.index')!!}"><i class="fa fa-circle"></i>Allowance</a></li>

                                <li class= "{{ Request::is('admin/commission') || Request::is('admin/commission/*') ? 'active' : '' }}"><a href="{!! route('Commission.index')!!}"><i class="fa fa-circle"></i>Commission</a></li>

                                <li class= "{{ Request::is('admin/sacco') || Request::is('admin/sacco/*') ? 'active' : '' }}"><a href="{!! route('Sacco.index')!!}"><i class="fa fa-circle"></i>Sacco</a></li>

                                <li class= "{{ Request::is('admin/loan-type') || Request::is('admin/loan-type/*') ? 'active' : '' }}"><a href="{!! route('loan-type.index')!!}"><i class="fa fa-circle"></i>Loan Type</a></li>

                                <li class= "{{ Request::is('admin/pension') || Request::is('admin/pension/*') ? 'active' : '' }}"><a href="{!! route('pension.index')!!}"><i class="fa fa-circle"></i>Pension</a></li>

                                <li class= "{{ Request::is('admin/custom-parameter') || Request::is('admin/custom-parameter/*') ? 'active' : '' }}"><a href="{!! route('custom-parameter.index')!!}"><i class="fa fa-circle"></i>Custom Parameter</a></li>

                                <li class= "{{ Request::is('admin/non-cash-benefit') || Request::is('admin/non-cash-benefit/*') ? 'active' : '' }}"><a href="{!! route('non-cash-benfit.index')!!}"><i class="fa fa-circle"></i>Non Cash Benefit</a></li>

                                <li class= "{{ Request::is('admin/relief') || Request::is('admin/relief/*') ? 'active' : '' }}"><a href="{!! route('relief.index')!!}"><i class="fa fa-circle"></i>Relief</a></li>
                               @endif
                            </ul>
                          </li>
                           @endif
                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/bank') || Request::is('admin/bank/*') || Request::is('admin/branch') || Request::is('admin/branch/*') || Request::is('admin/payment-modes') || Request::is('admin/payment-modes/*') || Request::is('admin/payment-frequency') || Request::is('admin/payment-frequency/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Banking Info</span>
                                  <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu {{  Request::is('admin/bank') || Request::is('admin/bank/*')  ? 'active' : '' }}  ">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                                <li class= "{{ Request::is('admin/bank') || Request::is('admin/bank/*') ? 'active' : '' }}"><a href="{!! route('bank.index')!!}"><i class="fa fa-circle"></i>Bank</a></li>

                                <li class= "{{ Request::is('admin/branch') || Request::is('admin/branch/*') ? 'active' : '' }}"><a href="{!! route('branch.index')!!}"><i class="fa fa-circle"></i>Branch</a></li>

                                <li class= "{{ Request::is('admin/payment-modes') || Request::is('admin/payment-modes/*') ? 'active' : '' }}"><a href="{!! route('payment-modes.index')!!}"><i class="fa fa-circle"></i>Payment Modes</a></li>

                                <li class= "{{ Request::is('admin/payment-frequency') || Request::is('admin/payment-frequency/*') ? 'active' : '' }}"><a href="{!! route('payment-frequency.index')!!}"><i class="fa fa-circle"></i>Payment Frequency</a></li>

                               @endif
                            </ul>
                          </li>
                           @endif
                          </ul>

                          </li>
                          @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/emp-list') || Request::is('admin/emp-list/*') || Request::is('admin/employee-manage') || Request::is('admin/employee-manage/*')  || Request::is('admin/emp/contract') || Request::is('admin/emp/contract/*')  || Request::is('admin/emp/indisciplineCategory') || Request::is('admin/emp/indisciplineCategory/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>HR Management</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/emp-list') || Request::is('admin/emp-list/*') ? 'active' : '' }} ">
                             @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/emp-list') || Request::is('admin/emp-list/*')  || Request::is('admin/employee-manage') || Request::is('admin/employee-manage/*') || Request::is('admin/emp/contract') || Request::is('admin/emp/contract/*') || Request::is('admin/emp/indisciplineCategory') || Request::is('admin/emp/indisciplineCategory/*')  ? 'active' : '' }}"><a href="{!! route('employee.index')!!}"><i class="fa fa-circle"></i>Employee</a></li>
                              @endif
                          </li>
                          @endif
                          </ul>
                          </li>@endif

                          @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{  Request::is('admin/emp/separation') || Request::is('admin/emp/separation/*') || Request::is('admin/emp/separation-termnation') || Request::is('admin/emp/separation-termnation/*') || Request::is('admin/emp/ApproveTermination') || Request::is('admin/emp/ApproveTermination/*') || Request::is('admin/emp/TerminatedStaff') || Request::is('admin/emp/TerminatedStaff/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Separation Center</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/emp-list') || Request::is('admin/emp-list/*') ? 'active' : '' }} ">
                             @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/emp/separation') || Request::is('admin/emp/separation/*') || Request::is('admin/emp/separation-termnation') || Request::is('admin/emp/separation-termnation/*') ? 'active' : '' }}"><a href="{!! route('separation.index')!!}"><i class="fa fa-circle"></i>Schedule Termination</a></li>
                               <li class= "{{ Request::is('admin/emp/ApproveTermination') || Request::is('admin/emp/ApproveTermination/*')   ? 'active' : '' }}"><a href="{!! route('ApproveTermination.index')!!}"><i class="fa fa-circle"></i>Approve Termination</a></li>
                               <li class= "{{ Request::is('admin/emp/TerminatedStaff') || Request::is('admin/emp/TerminatedStaff/*')   ? 'active' : '' }}"><a href="{!! route('TerminatedStaff.index')!!}"><i class="fa fa-circle"></i>Terminated Staff</a></li>
                              @endif
                          </li>
                          @endif
                          </ul>
                          </li>@endif

                          @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{  Request::is('admin/emp/PayrollMaster') || Request::is('admin/emp/Payroll/*') || Request::is('admin/emp/PayrollAbsend') || Request::is('admin/emp/PayrollAbsend/*') || Request::is('admin/emp/OvertimeHours') || Request::is('admin/emp/OvertimeHours/*') || Request::is('admin/emp/loan-Entries') || Request::is('admin/emp/loan-Entries/*') || Request::is('admin/emp/salaryReview') || Request::is('admin/emp/salaryReview/*')|| Request::is('admin/emp/ProcessPayroll/index') || Request::is('admin/emp/ProcessPayroll/index/*') || Request::is('admin/emp/ProcessPayroll/paysliproll') || Request::is('admin/emp/ProcessPayroll/paysliproll/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Payroll Master</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/PayrollMaster') || Request::is('admin/Payroll/*') ? 'active' : '' }} ">
                             @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/emp/PayrollMaster') || Request::is('admin/emp/Payroll/*') ? 'active' : '' }}"><a href="{!! route('PayrollMaster.index')!!}"><i class="fa fa-circle"></i>Payroll Details</a></li>

                               <li class= "{{ Request::is('admin/emp/PayrollAbsend') || Request::is('admin/emp/PayrollAbsend/*') ? 'active' : '' }}"><a href="{!! route('PayrollAbsend.index')!!}"><i class="fa fa-circle"></i>Absents Details</a></li>
                               <li class= "{{ Request::is('admin/emp/OvertimeHours') || Request::is('admin/emp/OvertimeHours/*') ? 'active' : '' }}"><a href="{!! route('OvertimeHours.index')!!}"><i class="fa fa-circle"></i>Overtime Hours</a></li>
                               <li class= "{{ Request::is('admin/emp/loan-Entries') || Request::is('admin/emp/loan-Entries/*') ? 'active' : '' }}"><a href="{!! route('LoanEntries.index')!!}"><i class="fa fa-circle"></i>Loan Enteries</a></li>
                               <li class= "{{ Request::is('admin/emp/salaryReview') || Request::is('admin/emp/salaryReview/*') ? 'active' : '' }}"><a href="{!! route('SalaryReview.index')!!}"><i class="fa fa-circle"></i>Salary Review</a></li>

                               <li class= "{{ Request::is('admin/emp/ProcessPayroll/index') || Request::is('admin/emp/ProcessPayroll/index/*') || Request::is('admin/emp/ProcessPayroll/paysliproll') || Request::is('admin/emp/ProcessPayroll/paysliproll/*')  ? 'active' : '' }}"><a href="{!! route('ProcessPayroll.index')!!}"><i class="fa fa-circle"></i>Process Payroll</a></li>
                              @endif
                          </li>
                          @endif
                          </ul>
                          </li>@endif

                          @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{   Request::is('admin/emp/staffpayslips') || Request::is('admin/emp/staffpayslips/*')  || Request::is('admin/emp/payrollreport') || Request::is('admin/emp/payrollreport/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Payroll Report</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{  Request::is('admin/emp/staffpayslips') || Request::is('admin/emp/staffpayslips/*') ? 'active' : '' }} ">
                             @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/emp/staffpayslips') || Request::is('admin/emp/staffpayslips/*') ? 'active' : '' }}"><a href="{!! route('staffpayslips.index')!!}"><i class="fa fa-circle"></i>Staff Payslips</a></li>

                               <li class= "{{ Request::is('admin/emp/payrollreport') || Request::is('admin/emp/payrollreport/*') ? 'active' : '' }}"><a href="{!! route('payrollreprt.index')!!}"><i class="fa fa-circle"></i>Payroll Report</a></li>
                              @endif
                          </li>
                          @endif
                          </ul>
                          </li>@endif
                          @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{   Request::is('admin/emp/leaveConfig') || Request::is('admin/emp/leaveConfig/*') ||  Request::is('admin/emp/AssignLeaveIndex') || Request::is('admin/emp/AssignLeaveIndex/*')||  Request::is('admin/emp/entitlements/index') || Request::is('admin/emp/entitlements/index/*') ||  Request::is('admin/emp/HrApproval') || Request::is('admin/emp/HrApproval/*') ||  Request::is('admin/emp/ManagerApproval/index') || Request::is('admin/emp/ManagerApproval/manage/*')||  Request::is('admin/emp/leave_status/index') || Request::is('admin/emp/leave_status/index/*') ||  Request::is('admin/emp/LeaveRecalls') || Request::is('admin/emp/LeaveRecalls/*') || Request::is('admin/emp/LeaveReversal') || Request::is('admin/emp/LeaveReversal/*') || Request::is('admin/emp/leaveHistory') || Request::is('admin/emp/leaveHistory/*') || Request::is('admin/emp/LeaveBalances') || Request::is('admin/emp/LeaveBalances/*') || Request::is('admin/emp/LeaveRecallsReport') || Request::is('admin/emp/LeaveRecallsReport/*') || Request::is('admin/emp/ReversalReport') || Request::is('admin/emp/ReversalReport/*')   ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Manage Leaves</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/PayrollMaster') || Request::is('admin/Payroll/*') ? 'active' : '' }} ">
                             @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/emp/leaveConfig/manage') || Request::is('admin/emp/leaveConfig/manage/*') ? 'active' : '' }}"><a href="{!! route('LeaveConfig.manage')!!}"><i class="fa fa-circle"></i>Leave Config</a></li>

                               <li class= "{{ Request::is('admin/emp/entitlements/index') || Request::is('admin/emp/entitlements/index/*') ? 'active' : '' }}"><a href="{!! route('Entitlements.index')!!}"><i class="fa fa-circle"></i>Entitlements</a></li>

                                <li class= "{{ Request::is('admin/emp/AssignLeaveIndex') || Request::is('admin/emp/AssignLeaveIndex/*') ? 'active' : '' }}"><a href="{!! route('Assign.index')!!}"><i class="fa fa-circle"></i>Assign Leave</a></li>

                               <li class= "{{ Request::is('admin/emp/HrApproval') || Request::is('admin/emp/HrApproval/*') ? 'active' : '' }}"><a href="{!! route('HrApproval.index')!!}"><i class="fa fa-circle"></i>HR Approval</a></li>

                               <li class= "{{ Request::is('admin/emp/ManagerApproval/index') || Request::is('admin/emp/ManagerApproval/manage/*') ? 'active' : '' }}"><a href="{!! route('ManagerApproval.index')!!}"><i class="fa fa-circle"></i> Manager Approval</a></li>

                               <li class= "{{ Request::is('admin/emp/leave_status/index') || Request::is('admin/emp/leave_status/index/*') ? 'active' : '' }}"><a href="{!! route('LeaveStatus.index')!!}"><i class="fa fa-circle"></i> Leave Status</a></li>

                               <li class= "{{ Request::is('admin/emp/LeaveRecalls') || Request::is('admin/emp/LeaveRecalls/*') ? 'active' : '' }}"><a href="{!! route('LeaveRecalls.Index')!!}"><i class="fa fa-circle"></i>Leave Recalls</a></li>

                               <li class= "{{ Request::is('admin/emp/LeaveReversal') || Request::is('admin/emp/LeaveReversal/*') ? 'active' : '' }}"><a href="{!! route('LeaveReversal.Index')!!}"><i class="fa fa-circle"></i>Leave Reversal</a></li>

                                 @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{  Request::is('admin/emp/leaveHistory') || Request::is('admin/emp/leaveHistory/*') || Request::is('admin/emp/LeaveBalances') || Request::is('admin/emp/LeaveBalances/*') || Request::is('admin/emp/LeaveRecallsReport') || Request::is('admin/emp/LeaveRecallsReport/*') || Request::is('admin/emp/ReversalReport') || Request::is('admin/emp/ReversalReport/*')  ? 'active' : '' }}">
                            <a href="#"><i class="fa fa-adjust"></i><span>Report</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">

                              @if ($logged_user_info->role_id == 1 || isset($my_permissions['front-desk___view']))
                          <li class="treeview {{ Request::is('admin/leaveHistory') || Request::is('admin/leaveHistory/*') ? 'active' : '' }} ">
                             @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                               <li class= "{{ Request::is('admin/emp/leaveHistory') || Request::is('admin/emp/leaveHistory/*') ? 'active' : '' }}"><a href="{!! route('leaveHistory.Index')!!}"><i class="fa fa-circle"></i>Leave History Report</a></li>

                               <li class= "{{ Request::is('admin/emp/LeaveBalances') || Request::is('admin/emp/LeaveBalances/*') ? 'active' : '' }}"><a href="{!! route('LeaveBalances.Index')!!}"><i class="fa fa-circle"></i>Leave Balances Report</a></li>

                               <li class= "{{ Request::is('admin/emp/LeaveRecallsReport') || Request::is('admin/emp/LeaveRecallsReport/*') ? 'active' : '' }}"><a href="{!! route('emp.LeaveRecallsReport')!!}"><i class="fa fa-circle"></i>Leave Recalls Report</a></li>

                               <li class= "{{ Request::is('admin/emp/ReversalReport') || Request::is('admin/emp/ReversalReport/*') ? 'active' : '' }}"><a href="{!! route('emp.ReversalReport')!!}"><i class="fa fa-circle"></i>Leave Reversal Report</a></li>

                              @endif
                          </li>
                          @endif
                          </ul>
                              @endif
                          </li>
                          @endif
                          </ul>
                          </li>@endif


                          </li>@endif
                      </ul>
                   </li>
                   @endif


        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-production___view']))
            <li class="treeview @if (isset($model) && $model == 'work-orders') active @endif">
                <a href="#">
                    <i class="fa fa-fw fa-industry"></i><span>Production</span>
                    <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                </a>

                <ul class="treeview-menu">
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['work-orders___view'])) @endif
                    <li class="@if (isset($model) && $model == 'work-orders') active @endif">
                        <a href="{!! route('work-orders.index') !!}">
                            <i class="fa fa-circle"></i> Work Orders
                        </a>
                    </li>
                </ul>
            </li>
        @endif
        --}}

        @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                <?php
                $active_class = isset($model) && in_array($model, ['recipes', 'sales', 'sales-sales_deduction', 'sales-sales_with_no_recipe_link']) ? 'active' : '';
                ?>
            <li class="treeview <?= $active_class ?>" style="display: none;">
                <a href="#"><i class="fa fa-ge"></i><span>Recipes and Menu Costing</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                            <?php $active_class = isset($model) && in_array($model, ['recipes']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('recipes.index') !!}"><i
                                        class="fa fa-circle"></i> Recipes </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                            <?php $active_class = isset($model) && in_array($model, ['sales', 'sales-sales_deduction', 'sales-sales_with_no_recipe_link']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>">
                            <a href="#"><i class="fa fa-cube"></i><span>Ingredients Booking</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>

                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['sales-sales_deduction']) ? 'active' : ''; ?>
                                    <li class="<?= $active_class ?>"><a href="{!! route('sales.sales-deductions') !!}"><i
                                                    class="fa fa-circle"></i>Sales Deductions </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['sales']) ? 'active' : ''; ?>
                                    <li class="<?= $active_class ?>"><a href="{!! route('sales.sales-with-less-quantity') !!}"><i
                                                    class="fa fa-circle"></i>Sales with less quantity </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['recipes___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['sales-sales_with_no_recipe_link']) ? 'active' : ''; ?>
                                    <li class="<?= $active_class ?>"><a href="{!! route('sales.sales-with-no-recipe-link') !!}"><i
                                                    class="fa fa-circle"></i>Sales with No Recipes link </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                </ul>
            </li>
        @endif


        @if ($logged_user_info->role_id == 1 || isset($my_permissions['genralledger___view']))
                <?php $active_class = isset($model) && in_array($model, ['account-inquiry', 'petty-cash-approvals', 'genralLedger-bank-deposite', 'genralLedger-bank-transfer', 'bank-accounts', 'genralLedger-cheque', 'genralLedger-bills', 'expenses', 'genralLedger-gl_entries', 'profit-and-loss', 'balance-sheet', 'chart-of-accounts', 'bank-accounts', 'trial-balances', 'journal-entries', 'maintain-wallet', 'gl-transaction-report-summary', 'profit-and-loss', 'detailed-transaction-summary', 'edit-ledger','journal-inquiry']) ? 'active' : ''; ?>
            <li class="treeview <?= $active_class ?>">
                <a href="#"><i class="fa fa-globe"></i><span>General Ledger</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-wallet___view']))
                        <li class="@if (isset($model) && $model == 'maintain-wallet') active @endif"><a
                                    href="{!! route('maintain-wallet.index') !!}"><i class="fa fa-circle"></i>
                                Maintain Wallets
                            </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['chart-of-accounts___view']))
                        <li class="@if (isset($model) && $model == 'chart-of-accounts') active @endif"><a
                                    href="{!! route('chart-of-accounts.index') !!}"><i class="fa fa-circle"></i>
                                Chart Of Accounts
                            </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-inquiry___view']))
                        <li class="@if (isset($model) && $model == 'account-inquiry') active @endif"><a
                                    href="{!! route('admin.account-inquiry.index') !!}"><i class="fa fa-circle"></i> Account Inquiry
                            </a></li>
                    @endif
                    @if($logged_user_info->role_id == 1 || isset($my_permissions['journal-inquiry___view']))
                      <li  class="@if(isset($model) && $model == 'journal-inquiry') active @endif"><a href="{!! route('admin.journal-inquiry.index')!!}"><i class="fa fa-circle"></i> GL Journal Inquiry </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                            <?php $active_class = isset($model) && in_array($model, ['genralLedger-gl_entries']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('general-ledgers.gl-entries') . '?to=' . date('Y-m-d') . '&from=' . date('Y-m-d') !!}"><i
                                        class="fa fa-circle"></i> GL Transactions </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash___pending-approval']))
                            <?php $active_class = isset($model) && in_array($model, ['petty-cash-approvals']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Petty Cash</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash___pending-approval']))
                                        <?php $active_class = isset($model) && in_array($model, ['petty-cash-approvals']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('petty-cash.pending_approvals') }}"><i
                                                    class="fa fa-circle"></i> Pending Approval </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash___completed_approvals']))
                                        <?php $active_class = isset($model) && in_array($model, ['petty-cash-approvals']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('petty-cash.completed_approvals') }}"><i
                                                    class="fa fa-circle"></i> Completed Requests </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___view']))
                            <?php $active_class = isset($model) && in_array($model, ['genralLedger-cheque', 'genralLedger-bills', 'expenses']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Expenses</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___bill']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-bills']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a href="{{ route('bills.list') }}"><i
                                                    class="fa fa-circle"></i> Bills </a></li>
                                @endif
                                {{-- @if ($logged_user_info->role_id == 1)
                                  <li  class=""><a href="#"><i class="fa fa-circle"></i> Pay Bills </a></li>
                                @endif --}}
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___expense']))
                                        <?php $active_class = isset($model) && in_array($model, ['expenses']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a href="{{ route('expense.list') }}"><i
                                                    class="fa fa-circle"></i> Expense </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___cheque']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-cheque']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a href="{{ route('cheques.list') }}"><i
                                                    class="fa fa-circle"></i> Cheque </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___view']))
                            <?php $active_class = isset($model) && in_array($model, ['genralLedger-bank-deposite', 'genralLedger-bank-transfer', 'bank-accounts']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Banking</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['bank-accounts']) ? 'active' : ''; ?>
                                    <li class="<?= $active_class ?>"><a href="{!! route('bank-accounts.index') !!}"><i
                                                    class="fa fa-circle"></i>Bank
                                            Accounts </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___bankdeposit']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-bank-deposite']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('banking.deposite.list') }}"><i
                                                    class="fa fa-circle"></i> Bank
                                            Deposit </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___banktransfer']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-bank-transfer']) ? 'active' : ''; ?>

                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('banking.transfer.list') }}"><i
                                                    class="fa fa-circle"></i>
                                            Transfer </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['journal-entries___view']))
                            <?php $active_class = isset($model) && in_array($model, ['journal-entries']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('journal-entries.index') !!}"><i
                                        class="fa fa-circle"></i>Journal
                                Entry </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['journal-entries___processed']))
                            <?php $active_class = isset($model) && in_array($model, ['journal-entries']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('journal-entries.processed_index') !!}"><i
                                        class="fa fa-circle"></i>Processed
                                JV </a></li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['edit-ledger___processed']))
                            <?php $active_class = isset($model) && in_array($model, ['edit-ledger']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>">
                            <a href="{!! route('edit-ledger.index') !!}"><i class="fa fa-circle"></i> View Ledger </a>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger-reports___view']))
                        <li class="treeview @if (isset($model) && ($model == 'trial-balances' 
                        || $model == 'profit-and-loss' ||
                        $model == 'gl-transaction-report-summary' ||
                        $model == 'detailed-transaction-summary' ||
                         $model == 'balance-sheet')) active @endif">
                            <a href="#"><i class="fa fa-circle"></i> Reports
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['trial-balances___view']))
                                    <li class="@if (isset($model) && $model == 'trial-balances') active @endif">
                                        <a href="{!! route('trial-balances.index') . getReportDefaultFilterForTrialBalance() !!}">
                                            <i class="fa fa-circle"></i> Trial Balance
                                        </a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['tgeneral-ledger-reports___detailed-trial-balance']))
                                    <li class="@if (isset($model) && $model == 'trial-balances') active @endif">
                                        <a href="{!! route('trial-balances.detailed') . getReportDefaultFilterForTrialBalance() !!}">
                                            <i class="fa fa-circle"></i> Detailed Trial Balance
                                        </a>
                                    </li>
                                @endif

                                <li class="@if (isset($model) && $model == 'profit-and-loss') active @endif"><a
                                            href="{!! route('profit-and-loss.index') !!}"><i class="fa fa-circle"></i> Profit &
                                        Loss </a></li>
                                <li class="@if (isset($model) && $model == 'profit-and-loss') active @endif"><a
                                            href="{!! route('profit-and-loss.detailsAll', ['start-date' => date('Y-m-d'), 'end-date' => date('Y-m-d')]) !!}"><i class="fa fa-circle"></i> Profit &
                                        Loss Details </a></li>
                                <li class="@if (isset($model) && $model == 'balance-sheet') active @endif"><a
                                            href="{!! route('balance-sheet.index') !!}"><i class="fa fa-circle"></i> Balance
                                        Sheet</a></li>

                                <li class="@if (isset($model) && $model == 'balance-sheet') active @endif"><a
                                            href="{!! route('statement-financical-position.detailsAll') !!}"><i class="fa fa-circle"></i>
                                        Detailed Balance Sheet</a></li>

                                @if($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger-reports___transaction-summary']))
                                    <li class="@if(isset($model) && $model == 'gl-transaction-report-summary') active @endif"><a href="{!! route('general-ledger.gl_transaction_summary')!!}"><i
                                                    class="fa fa-circle"></i> Transaction Summary </a></li>
                                @endif

                                @if($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger-reports___detailed-transaction-summary']))
                                    <li class="@if(isset($model) && $model == 'detailed-transaction-summary') active @endif">
                                        <a href="{!! route('gl-reports.detailed-transaction-summary')!!}"><i class="fa fa-circle"></i> Detailed Transaction Summary </a>
                                    </li>
                                @endif

                                @if($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger-reports___p-l-monthly-report']))
                                    <li class="@if(isset($model) && $model == 'profit-and-loss') active @endif"><a href="{!! route('profit-and-loss.monthlyProfitSummary')!!}"><i
                                                    class="fa fa-circle"></i> Profit & Loss Monthly Report </a></li>
                                @endif

                            </ul>
                        </li>
                    @endif
                </ul>
            </li>
        @endif



        <!-- Fleet-Management -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['fleet-management-module___view']))
            <li class="treeview @if (isset($model) &&
                    ($model == 'vehicles' ||
                        $model == 'fuel-history' ||
                        $model == 'fuel-stations' ||
                        $model == 'fuel-lpos' ||
                        $model == 'vehicle-suppliers' ||
                        $model == 'vehicle-models')) active @endif">
                <a href="#"><i class="fa fa-truck"></i><span>Fleet Management </span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>

                <ul class="treeview-menu">
                    <li class="treeview @if (isset($model) && ($model == 'vehicle-suppliers' || $model == 'vehicle-models')) active @endif">
                        <a href="#"><i class="fas fa-sliders-h"></i><span> Setup </span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['vehicle-suppliers___view']))
                                <li class="@if (isset($model) && $model == 'vehicle-suppliers') active @endif">
                                    <a href="{!! route('vehicle-suppliers.index') !!}"><i class="fa fa-circle"
                                                                                          aria-hidden="true"></i>
                                        Vehicle Suppliers
                                    </a>
                                </li>
                            @endif
                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['vehicle-suppliers___view']))
                                <li class="@if (isset($model) && $model == 'vehicle-models') active @endif">
                                    <a href="{!! route('vehicle-models.index') !!}"><i class="fa fa-circle"
                                                                                       aria-hidden="true"></i>
                                        Vehicle Models
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    {{-- fleet management --}}
                    <li class="treeview @if (isset($model) && ($model == 'vehicle-suppliers' || $model == 'vehicles')) active @endif">
                        <a href="#"><i class="fa fa-car"></i><span> My Fleet </span>
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['vehicle-suppliers___view']))
                                <li class="@if (isset($model) && $model == 'vehicle-suppliers') active @endif">
                                    <a href="#"><i class="fa fa-circle" aria-hidden="true"></i>
                                        Overview
                                    </a>
                                </li>
                            @endif
                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['vehicle-suppliers___view']))
                                <li class="@if (isset($model) && $model == 'vehicles') active @endif">
                                    <a href="{!! route('vehicles.index') !!}"><i class="fa fa-circle"
                                                                                 aria-hidden="true"></i>
                                        Vehicles
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>


                    {{-- @if ($logged_user_info->role_id == 1 || isset($my_permissions['vehicles___view']))
                        <li class="@if (isset($model) && $model == 'vehicles') active @endif">
                            <a href="{!! route('vehicles.index') !!}"><i class="fa fa-car" aria-hidden="true"></i>
                                My Fleet
                            </a>
                        </li>
                    @endif --}}

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['fuelentry___view']))
                        <li class="@if (isset($model) && $model == 'fuelentry') active @endif"><a
                                    href="{!! route('fuel-entries.index') !!}"><i class="fa fa-filter" aria-hidden="true"></i>
                                Fuel History</a></li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['fuel-stations___view']))
                        <li class="@if (isset($model) && $model == 'fuel-stations') active @endif">
                            <a href="{!! route('fuel-stations.index') !!}"><i class="fas fa-gas-pump" aria-hidden="true"></i>
                                Fuel Stations
                            </a>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['fuel-lpos___view']))
                        <li class="@if (isset($model) && $model == 'fuel-lpos') active @endif">
                            <a href="{!! route('fuel-lpos.index') !!}"><i class="fas fa-credit-card" aria-hidden="true"></i>
                                Fuel LPOs
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif
        <!-- End Fleet Management       -->

        @if ($logged_user_info->role_id == 1 || isset($my_permissions['financial-management___view']))
            <li class="treeview @if (isset($model) &&
                    ($model == 'route-manager' ||
                        $model == 'pack-size' ||
                        $model == 'account-sections' ||
                        $model == 'account-groups' ||
                        $model == 'branches' ||
                        $model == 'departments' ||
                        $model == 'company-preferences' ||
                        $model == 'tax-manager' ||
                        $model == 'currency-managers' ||
                        $model == 'accounting-periods' ||
                        $model == 'stock-type-categories' ||
                        $model == 'stock-family-groups' ||
                        $model == 'inventory-categories' ||
                        $model == 'category' ||
                        $model == 'unit-of-measures' ||
                        $model == 'payment-terms' ||
                        $model == 'item-sub-categories' ||
                        $model == 'location-and-stores' ||
                        $model == 'priority-level' ||
                        $model == 'payment-methods' ||
                        $model == 'payment-providers' ||
                        $model == 'number-series' ||
                        $model == 'roles' ||
                        $model == 'employees' ||
                        $model == 'user-denied-accesses' ||
                        $model == 'processes' ||
                        $model == 'manage-routes' ||
                        $model == 'sub-account-sections' ||
                        $model == 'alerts' ||
                        $model == 'teams' ||
                        $model == 'manage-delivery-centers' ||
                        $model == 'wallet-matrix' ||
                        $model == 'petty-cash-type' ||
                        $model == 'payment-modes' ||
                        $model == 'loaders' ||
                        $model == 'gl_tags' ||
                        $model ==  'projects')) active @endif">
                <a href="#"><i class="fa fa-fw fa-server"></i><span>System Administration</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger___view']))
                        <li class="treeview @if (isset($model) &&
                                ($model == 'account-sections' ||
                                    $model == 'account-groups' ||
                                    $model == 'chart-of-accounts' ||
                                    $model == 'branches' ||
                                    $model == 'wallet-matrix' ||
                                    $model == 'petty-cash-type' ||
                                    $model == 'sub-account-sections' ||
                                    $model == 'departments' ||
                                    $model == 'gl_tags' ||
                                    $model ==  'projects')) active @endif">
                            <a href="#"><i class="fa fa-circle"></i> General Ledger
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-sections___view']))
                                    <li class="@if (isset($model) && $model == 'account-sections') active @endif"><a
                                                href="{!! route('account-sections.index') !!}"><i class="fa fa-circle"></i>
                                            Account Groups </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-groups___view']))
                                    <li class="@if (isset($model) && $model == 'account-groups') active @endif"><a
                                                href="{!! route('account-groups.index') !!}"><i class="fa fa-circle"></i>
                                            Account Sections </a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-groups___view']))
                                    <li class="@if (isset($model) && $model == 'sub-account-sections') active @endif"><a
                                                href="{!! route('sub-account-sections.index') !!}"><i class="fa fa-circle"></i>Account Sub
                                            Sections</a></li>
                                @endif


                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['dimensions___view']))
                                    <li class="treeview @if (isset($model) && ($model == 'branches' || $model == 'departments' || $model == 'projects' || $model == 'gl_tags')) active @endif">
                                        <a href="#"><i class="fa fa-share"></i> Dimensions<span
                                                    class="pull-right-container">
                                                <i class="fa fa-angle-left pull-right"></i>
                                            </span>
                                        </a>
                                        <ul class="treeview-menu">
                                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['branches___view']))
                                                <li class="@if (isset($model) && $model == 'branches') active @endif">
                                                    <a href="{!! route('branches.index') !!}"><i class="fa fa-circle"></i>
                                                        <span>Branches</span></a>
                                                </li>
                                            @endif
                                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['departments___view']))
                                                <li class="@if (isset($model) && $model == 'departments') active @endif">
                                                    <a href="{!! route('departments.index') !!}"><i class="fa fa-circle"></i>
                                                        Departments</a>
                                                </li>
                                            @endif
                                            @if($logged_user_info->role_id == 1 || isset($my_permissions['projects___view']))
                                                <li class="@if(isset($model) && $model == 'projects') active @endif">
                                                    <a href="{!! route('projects.index')!!}"><i class="fa fa-circle"></i>
                                                        Projects</a>
                                                </li>
                                            @endif
                                            @if($logged_user_info->role_id == 1 || isset($my_permissions['gl_tags___view']))
                                                <li class="@if(isset($model) && $model == 'gl_tags') active @endif">
                                                    <a href="{!! route('gl_tags.index')!!}"><i class="fa fa-circle"></i>
                                                        Gl Tags</a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['wallet-matrix___view']))
                                    <li class="@if (isset($model) && $model == 'wallet-matrix') active @endif"><a
                                                href="{!! route('wallet-matrix.index') !!}"><i class="fa fa-circle"></i>Wallet Matrix</a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-type') active @endif"><a
                                                href="{!! route('petty-cash-types.index') !!}"><i class="fa fa-circle"></i>Petty Cash Types</a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['finanacial-system-setup___view']))
                        <li class="treeview @if (isset($model) &&
                                ($model == 'company-preferences' ||
                                    $model == 'tax-manager' ||
                                    $model == 'currency-managers' ||
                                    $model == 'accounting-periods' ||
                                    $model == 'number-series' ||
                                    $model == 'roles' ||
                                    $model == 'user-denied-accesses' ||
                                    $model == 'teams' ||
                                    $model == 'employees' ||
                                    $model == 'loaders')) active @endif">
                            <a href="#">
                                <i class="fa fa-circle"></i> System Setup<span class="pull-right-container"><i
                                            class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-sections___view']))
                                    <li class="@if (isset($model) && $model == 'company-preferences') active @endif">
                                        <a href="{!! route('company-preferences.index') !!}"><i class="fa fa-circle"></i>
                                            Company
                                            Preferences </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['tax-manager___view']))
                                    <li class="@if (isset($model) && $model == 'tax-manager') active @endif"><a
                                                href="{!! route('tax-manager.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Tax Manager</span></a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['currency-managers___view']))
                                    <li class="@if (isset($model) && $model == 'currency-managers') active @endif">
                                        <a href="{!! route('currency-managers.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Currency Managers</span></a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['accounting-periods___view']))
                                    <li class="@if (isset($model) && $model == 'accounting-periods') active @endif">
                                        <a href="{!! route('accounting-periods.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Accounting Periods</span></a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['number-series___view']))
                                    <li class="@if (isset($model) && $model == 'number-series') active @endif"><a
                                                href="{!! route('number-series.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Number Series</span></a></li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['roles___view']))
                                    <li class="@if (isset($model) && $model == 'roles') active @endif"><a
                                                href="{!! route('roles.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Roles</span></a></li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['employees___view']))
                                    <li class="@if (isset($model) && $model == 'employees') active @endif"><a
                                                href="{!! route('employees.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Employees</span></a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['employees___view']))
                                    <li class="@if (isset($model) && $model == 'loaders') active @endif"><a
                                                href="{!! route('loaders.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Loaders</span></a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['teams___view']))
                                    <li class="@if (isset($model) && $model == 'teams') active @endif">
                                        <a href="{!! route('teams.index') !!}"><i
                                                    class="fa fa-circle"></i><span>Teams</span></a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['access-denied___edit']))
                                    <li class="@if (isset($model) && $model == 'user-denied-accesses') active @endif">
                                        <a href="{!! route('admin.manage.users-access-denied') !!}"><i class="fa fa-circle"></i>
                                            <span>Users Access Requests</span></a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1)
                                    <li class="@if (isset($model) && $model == 'employees') active @endif"><a
                                                href="{!! route('userlogs.index') !!}"><i class="fa fa-circle"></i>
                                            <span>User Logs</span></a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['finanacial-inventory___view']))
                        <li class="treeview @if (isset($model) &&
                                ($model == 'pack-size' ||
                                    $model == 'stock-type-categories' ||
                                    $model == 'stock-family-groups' ||
                                    $model == 'item-sub-categories' ||
                                    $model == 'priority-level' ||
                                    $model == 'location-and-stores' ||
                                    $model == 'inventory-categories' ||
                                    $model == 'unit-of-measures' ||
                                    $model == 'category')) active @endif">
                            <a href="#"><i class="fa fa-circle"></i> Inventory
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['location-and-stores___view']))
                                    <li class="@if (isset($model) && $model == 'location-and-stores') active @endif"><a
                                                href="{!! route('location-and-stores.index') !!}"><i class="fa fa-circle"></i>
                                            Location and Stores</a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-type-categories___view']))
                                    <li class="@if (isset($model) && $model == 'stock-type-categories') active @endif">
                                        <a href="{!! route('stock-type-categories.index') !!}"><i class="fa fa-circle"></i> Stock
                                            Type
                                            Categories </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-family-groups___view']))
                                    <li class="@if (isset($model) && $model == 'stock-family-groups') active @endif">
                                        <a href="{!! route('stock-family-groups.index') !!}"><i class="fa fa-circle"></i> Stock
                                            Family
                                            Groups </a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-categories___view']))
                                    <li class="@if (isset($model) && $model == 'inventory-categories') active @endif">
                                        <a href="{!! route('inventory-categories.index') !!}"><i class="fa fa-circle"></i>
                                            Inventory Categories</a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['item-sub-categories___view']))
                                    <li class="@if (isset($model) && $model == 'item-sub-categories') active @endif"><a
                                                href="{!! route('item-sub-categories.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Item Sub Categories</span></a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['priority-level___view']))
                                    <li class="@if (isset($model) && $model == 'priority-level') active @endif"><a
                                                href="{!! route('priority-level.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Priority Level</span></a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['unit-of-measures___view']))
                                    <li class="@if (isset($model) && $model == 'unit-of-measures') active @endif"><a
                                                href="{!! route('unit-of-measures.index') !!}"><i class="fa fa-circle"></i> Bin
                                            Location</a>
                                    </li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['category___view']))
                                    <li class="@if (isset($model) && $model == 'category') active @endif"><a
                                                href="{!! route('category.index') !!}"><i class="fa fa-circle"></i>
                                            Category Price</a></li>
                                @endif

                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['pack-size___view']))
                                    <li class="@if (isset($model) && $model == 'pack-size') active @endif"><a
                                                href="{!! route('pack-size.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Pack size</span></a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['route-manager___view']))
                        <li class="treeview @if (isset($model) && ($model == 'manage-routes' || $model == 'manage-delivery-centers')) active @endif">
                            <a href="#">
                                <i class="fa fa-circle"></i> Route Manager
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>

                            <ul class="treeview-menu">
                                <li class="@if (isset($model) && $model == 'manage-routes') active @endif">
                                    <a href="{!! route('manage-routes.index') !!}">
                                        <i class="fa fa-circle"></i> Route Mapping
                                    </a>
                                </li>

                                <li class="@if (isset($model) && $model == 'manage-routes') active @endif">
                                    <a href="{!! route('manage-routes.listing') !!}">
                                        <i class="fa fa-circle"></i> Route Listing
                                    </a>
                                </li>
                                <li class="@if (isset($model) && $model == 'manage-routes') active @endif">
                                    <a href="{!! route('manage-routes.route-tonnage-summary') !!}">
                                        <i class="fa fa-circle"></i> Route Targets Summary
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['finanacial-receivables-payables___view']))
                        <li class="treeview @if (isset($model) &&
                                ($model == 'payment-terms' ||
                                    $model == 'payment-methods' ||
                                    $model == 'payment-modes' ||
                                    $model == 'payment-providers')) active @endif">
                            <a href="#"><i class="fa fa-circle"></i> Receivables/Payables
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['payment-terms___view']))
                                    <li class="@if (isset($model) && $model == 'payment-terms') active @endif">
                                        <a href="{!! route('payment-terms.index') !!}">
                                            <i class="fa fa-circle"></i>
                                            Payment Terms
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['payment-providers___view']))
                                    <li class="@if (isset($model) && $model == 'payment-providers') active @endif">
                                        <a href="{!! route('payment-providers.index') !!}">
                                            <i class="fa fa-circle"></i><span>Payment Providers</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['payment-methods___view']))
                                    <li class="@if (isset($model) && $model == 'payment-methods') active @endif">
                                        <a href="{!! route('payment-methods.index') !!}">
                                            <i class="fa fa-circle"></i><span>Payment Methods</span>
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['payment-modes___view']))
                                    <li class="@if (isset($model) && $model == 'payment-modes') active @endif">
                                        <a href="{!! route('payment-modes.index') !!}">
                                            <i class="fa fa-circle"></i><span>Payment Modes</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['financial-production___view']))
                        <li class="treeview @if (isset($model) && $model == 'processes') active @endif">
                            <a href="#"><i class="fa fa-circle"></i> Production
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['processes___view']))
                                    <li class="@if (isset($model) && $model == 'processes') active @endif">
                                        <a href="{!! route('processes.index') !!}"><i class="fa fa-circle"></i>
                                            Processes </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['alerts-and-notifications___view']))
                        <li class="treeview @if (isset($model) && $model == 'alerts') active @endif">
                            <a href="#"><i class="fas fa-bell"></i> Alerts & Notifications
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>

                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['alerts___view']))
                                    <li class="@if (isset($model) && $model == 'alerts') active @endif">
                                        <a href="{!! route('alerts.index') !!}"><i class="fa fa-circle"></i> Alerts </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                </ul>
            </li>
            @endif
            </ul>
    </section>
</aside>
