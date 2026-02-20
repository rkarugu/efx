@if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases___view']))
    <li
        class="treeview @if (isset($model) &&
                ($model == 'external-requisitions' ||
                    $model == 'purchases-reports' ||
                    $model == 'approve-external-requisitions' ||
                    $model == 'purchase-orders' ||
                    $model == 'approve-lpo' ||
                    $model == 'resolve-requisition-to-lpo' ||
                    $model == 'lpo-status-and-leadtime-report' ||
                    $model == 'pending-suppliers' ||
                    $model == 'suggested_order' ||
                    $model == 'archived-lpo' ||
                    $model == 'completed-lpo' ||
                    $model == 'purchase-order-status' ||
                    $model == 'suggested_order' ||
                    $model == 'order-delivery-slots')) active
                @else
                @if (isset($rmodel) &&
                        ($rmodel == 'purchases-by-store-location' ||
                            $rmodel == 'purchases-by-family-group' ||
                            $rmodel == 'purchases-by-supplier')) active @endif
                @endif">

        <a href="#"><i class="fa fa-fw fa-shopping-cart"></i><span>Purchase Procurement</span>
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
                    <a href="#"><i class="fa fa-share"></i> Branch Requisitions
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['external-requisitions___view']))
                            <li class="@if (isset($model) && $model == 'external-requisitions') active @endif">
                                <a href="{!! route('external-requisitions.index') !!}"><i class="fa fa-circle"></i>
                                    Initiate Requisitions</a>
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
                <li class="treeview @if (isset($model) &&
                        ($model == 'purchase-orders' ||
                            $model == 'archived-lpo' ||
                            $model == 'completed-lpo' ||
                            $model == 'purchase-order-status' ||
                            $model == 'approve-lpo')) active @endif">
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
                                    href="{!! route('approve-lpo.index') !!}"><i class="fa fa-circle"></i>
                                    Approve LPOs</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['archived-lpo___view']))
                            <li class="@if (isset($model) && $model == 'archived-lpo') active @endif"><a
                                    href="{!! route('purchase-orders.archived-lpo') !!}"><i class="fa fa-circle"></i>
                                    Archived LPOs</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['completed-lpo___view']))
                            <li class="@if (isset($model) && $model == 'completed-lpo') active @endif"><a
                                    href="{!! route('purchase-orders.completed-lpo') !!}"><i class="fa fa-circle"></i>
                                    Completed LPOs</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchase-order-status___view']))
                            <li class="@if (isset($model) && $model == 'purchase-order-status') active @endif"><a
                                    href="{!! route('purchase-orders.status_report') !!}"><i class="fa fa-circle"></i>
                                    Status Report</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases-reports___view']))
                <li
                    class="@if (isset($rmodel) &&
                            ($rmodel == 'purchases-by-store-location' ||
                                $rmodel == 'purchases-by-family-group' ||
                                $rmodel == 'purchases-by-supplier')) active @else
                                        @if (isset($model) && ($model == 'purchases-reports' || $model == 'lpo-status-and-leadtime-report')) active @endif @endif">
                    <a href="{!! route('purchases-reports.index') !!}"><i class="fa fa-circle"></i>
                        Reports
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif
