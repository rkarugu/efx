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
                $model == 'utilities' ||
                $model == 'bulk_purchase_data' ||
                $model == 'returned-receive-purchase-order' ||
                $model == 'inventory_location_stock_summary' ||
                $model == 'inventory_location_as_at' ||
                $model == 'batch-price-change' ||
                $model == 'single-price-change' ||
                $model == 'price-change-history-list' ||
                $model == 'return-accepted-receive-order' ||
                $model == 'return-to-supplier-from-grn-create' ||
                $model == 'return-to-supplier-from-grn-pending' ||
                $model == 'return-to-supplier-from-grn-approve' ||
                $model == 'return-to-supplier-from-grn-approved' ||
                $model == 'processed-grns' ||
                $model == 'price-update-upload' ||
                $model == 'maintain-items-manual-cost-change' ||
                $model == 'inventory-reports' ||
                $model == 'items-data-purchases' ||
                $model == 'slow-moving-items-report' ||
                $model == 'pending-price-change-requests' ||
                $model == 'items_negetive_listing' ||
                $model == 'max-stock-report' ||
                $model == 'item-sales-route-performance-report' ||
                $model == 'average-sales-report' ||
                $model == 'missing-items-report' ||
                $model == 'overstock-report' ||
                $model == 'inactive-stock-report' ||
                $model == 'dead-stock-report' ||
                $model == 'slow-moving-items-report' ||
                $model == 'items-data-sales' ||
                $model == 'transfer-inwards-report' ||
                $model == 'price-timeline-report' ||
                $model == 'items-list-report' ||
                $model == 'sub-distributor-suppliers-report' ||
                $model == 'procurement-reported-shift-issues' ||
                $model == 'supplier-user-report' ||
                $model == 'missing-split-report' ||
                $model == 'CTN-without-children' ||
                $model == 'grn-summary-by-supplier-report' ||
                $model == 'out-of-stock-report' ||
                $model == 'discount-items-report' ||
                $model == 'promotion-items-report' ||
                $model == 'no-supplier-items-report' ||
                $model == 'return-to-supplier-from-store-create' ||
                $model == 'return-to-supplier-from-store-pending' ||
                $model == 'return-to-supplier-from-store-approve' ||
                $model == 'return-to-supplier-from-store-approved' ||
                $model == 'return-to-supplier-from-store-rejected' ||
                $model == 'pending-new-approval' ||
                $model == 'pending-edit-approval' ||
                $model == 'rejected-approval' ||
                $model == 'item-log' ||
                $model == 'supplier-user-management' ||
                $model == 'rejected-approval' ||
                $model == 'match-purchase-orders' ||
                $model == 'delivery-notes' ||
                $model == 'delivery-notes-invoices' ||
                $model == 'delivery-notes-schedules' ||
                $model == 'stock-break-completed' ||
                $model == 'item-sales-route-performance-report' ||
                $model == 'maintain-items-suggested-order-report' ||
                $model == 'detailed-stock-count-variance' ||
                $model == 'summary-stock-count-variance' ||
                $model == 'retired-items' ||
                $model == 'batch-retire-items' ||
                $model == 'utility' ||
                $model == 'stock-take-user-assignment' ||
                $model == 'download-branch-utilities' ||
                $model == 'recalculate-qoh' ||
                $model == 'utilities' ||
                $model == 'child-vs-mother-qoh-report' ||
                $model == 'stock-debtors' ||
                $model == 'stock-processing-sales' ||
                $model == 'stock-processing-return' ||
                $model == 'upload-new-items' ||
                $model == 'verify-stocks' ||
                $model == 'download-stocks' ||
                $model == 'update-item-code' ||
                $model == 'items-without-suppliers' ||
                $model == 'stock-count-blocked-users' ||
                $model == 'stock-count-blocked-users-exemption-schedules' ||
                $model == 'inventory-utility-logs' ||
                $model == 'update-item-prices' ||
                $model == 'update-item-standard-cost' ||
                $model == 'item-margins' ||
                $model == 'update-item-selling-price' ||
                $model == 'stock-breaking-summary' ||
                $model == 'display-split-requests' ||
                $model == 'update-selling-price-and-standard-cost' ||
                $model == 'reverse-splits' ||
                $model == 'competing-brands' ||
                $model == 'price-list-cost-change' ||
                $model == 'update-stock-qoh' ||
                $model == 'update-grn-utility' ||
                $model == 'item-has-count' ||
                $model == 'stock-uncompleted-sales' || 
                $model == 'stock-non-debtors')) active @endif">
        <a href="#"><i class="fa fa-fw fa-warehouse"></i><span>Inventory</span>
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
                            $model == 'pending-new-approval' ||
                            $model == 'pending-edit-approval' ||
                            $model == 'rejected-approval' ||
                            $model == 'stock-break-completed' ||
                            $model == 'item-log' ||
                            $model == 'stock-breaking-summary' ||
                            $model == 'display-split-requests' ||
                            $model == 'price-list-cost-change' ||
                            $model == 'rejected-approval')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Stock
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___view']))
                            <li class="@if (isset($model) && $model == 'maintain-items') active @endif">
                                <a href="{!! route('maintain-items.index') !!}"><i class="fa fa-circle"></i> Manage Stock</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___manage-standard-cost']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'single-price-change' ||
                                        $model == 'price-change-history-list' ||
                                        $model == 'pending-price-change-requests' ||
                                        $model == 'batch-price-change' ||
                                        $model == 'price-update-upload' ||
                                        $model == 'price-list-cost-change' ||
                                        $model == 'maintain-items-manual-cost-change')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Price Change
                                    <span class="pull-right-container"><i
                                            class="fa fa-angle-left pull-right"></i></span>
                                </a>
                                <ul class="treeview-menu">
                                    <li class="@if (isset($model) && $model == 'batch-price-change') active @endif">
                                        <a href="{!! route('price-change.batch-requests') !!}"><i class="fa fa-circle"></i>Price Change</a>
                                    </li>
                                    <li class="@if (isset($model) && ($model == 'maintain-items' || $model == 'price-change-history-list')) active @endif">
                                        <a href="{!! route('maintain-items.item_price_history_list') !!}">
                                            <i class="fa fa-circle"></i> Price Change History
                                        </a>
                                    </li>
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___manage-standard-cost-manual']))
                                        <li class="@if ((isset($model) && $model == 'maintain-items') || $model == 'maintain-items-manual-cost-change') active @endif"><a
                                                href="{!! route('maintain-items.manual-cost-change') !!}"><i class="fa fa-circle"></i>
                                                Manual Cost Change</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (
                $logged_user_info->role_id == 1 ||
                    isset($my_permissions['stock-breaking___view']) ||
                    isset($my_permissions['stock-auto-breaks___view']) ||
                    isset($my_permissions['stock-break-dispatched ___view']) ||
                    isset($my_permissions['stock-break-completed ___view']) ||
                    isset($my_permissions['stock-break-dispatch___view']))
                <li class="treeview @if (
                    (isset($model) && $model == 'stock-breaking') ||
                        $model == 'stock-auto-breaks' ||
                        $model == 'stock-break-dispatch' ||
                        $model == 'stock-breaking-summary' ||
                        $model == 'display-split-requests' ||
                        $model == 'reverse-splits' ||
                        $model == 'stock-break-completed') active @endif">
                    <a href="#">
                        <i class="fa fa-circle"></i> Stocks Break
                        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                    </a>

                    <ul class="treeview-menu">

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-breaking___view']))
                            <li class="@if (isset($model) && $model == 'stock-breaking') active @endif"><a
                                    href="{!! route('stock-breaking.index') !!}"><i class="fa fa-circle"></i> Stock
                                    Breaking</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['display-split-requests___view']))
                            <li class="@if (isset($model) && $model == 'display-split-requests') active @endif">
                                <a href="{!! route('stock-breaking.split-requests') !!}"><i class="fa fa-circle"></i>Split Requests</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___view']))
                            <li class="@if (isset($model) && $model == 'stock-auto-breaks') active @endif">
                                <a href="{!! route('stock-auto-breaks.index') !!}"><i class="fa fa-circle"></i> Auto Breaks</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['reverse-splits___view']))
                            <li class="@if (isset($model) && $model == 'reverse-splits') active @endif">
                                <a href="{!! route('reverse-splitting.index') !!}"><i class="fa fa-circle"></i>Reverse Splits</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___dispatch']))
                            <li class="@if (isset($model) && $model == 'stock-break-dispatch') active @endif">
                                <a href="{!! route('stock-auto-breaks.dispatch.list') !!}"><i class="fa fa-circle"></i> Pending
                                    Dispatches</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___dispatch']))
                            <li class="@if (isset($model) && $model == 'stock-break-dispatched') active @endif">
                                <a href="{!! route('stock-auto-breaks.dispatched.list') !!}"><i class="fa fa-circle"></i> Dispatched
                                    Breaks</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-auto-breaks___dispatch']))
                            <li class="@if (isset($model) && $model == 'stock-break-completed') active @endif">
                                <a href="{!! route('stock-auto-breaks.dispatch.completed') !!}"><i class="fa fa-circle"></i> Completed
                                    Breaks</a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-breaking___summary']))
                            <li class="@if (isset($model) && $model == 'stock-breaking-summary') active @endif">
                                <a href="{!! route('stock-breaking.summary') !!}"><i class="fa fa-circle"></i> Summary</a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['weighted-average-history___view']))
                <li class="@if (isset($model) && $model == 'weighted-average-history') active @endif"><a href="{!! route('weighted-average-history.index') !!}"><i
                            class="fa fa-circle"></i> Weighted Averages</a></li>
            @endif
            @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-items___item-approval']))
                <li class="treeview @if (
                    (isset($model) && $model == 'pending-new-approval') ||
                        $model == 'pending-edit-approval' ||
                        $model == 'rejected' ||
                        $model == 'item-log' ||
                        $model == 'rejected-approval') active @endif">
                    <a href="#">
                        <i class="fa fa-circle"></i> Item Approval
                        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                    </a>

                    <ul class="treeview-menu">
                        <li class="@if (isset($model) && $model == 'pending-new-approval') active @endif">
                            {{-- <a href="{!! route('item-approval', 'pending-new-approval') !!}"><i class="fa fa-circle"></i>Pending New Approval</a> --}}
                            <a href="{!! route('item-new-approval') !!}"><i class="fa fa-circle"></i>Pending New Approval</a>

                        </li>
                        <li class="@if (isset($model) && $model == 'pending-edit-approval') active @endif">
                            <a href="{!! route('item-approval', 'pending-edit-approval') !!}"><i class="fa fa-circle"></i>Pending Edit
                                Approval</a>
                        </li>
                        <li class="@if (isset($model) && $model == 'item-log') active @endif">
                            <a href="{!! route('admin.show.item.log') !!}"><i class="fa fa-circle"></i>Item History</a>
                        </li>
                        <li class="@if (isset($model) && $model == 'rejected-approval') active @endif">
                            <a href="{!! route('item-approval', 'rejected-approval') !!}"><i class="fa fa-circle"></i>Rejected Request</a>
                        </li>
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

                                {{-- @if ($logged_user_info->role_id == 1 || isset($my_permissions['process-receive-purchase-order___view']))
                                    <li class="@if (isset($model) && $model == 'process-receive-purchase-order') active @endif"><a
                                            href="{!! route('process-receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Approve GRN
                                            Request </a></li>
                                @endif --}}
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['confirmed-receive-purchase-order___view']))
                                    <li class="@if (isset($model) && $model == 'confirmed-receive-purchase-order') active @endif"><a
                                            href="{!! route('confirmed-receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Confirm
                                            GRN</a>
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

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['delivery-notes___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'match-purchase-orders' ||
                            $model == 'delivery-notes' ||
                            $model == 'delivery-notes-invoices' ||
                            $model == 'delivery-notes-schedules')) active @endif">
                    <a href="#">
                        <i class="fa fa-circle"></i> Delivery Note
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['match-purchase-orders___view']))
                            <li class="@if (isset($model) && $model == 'match-purchase-orders') active @endif">
                                <a href="{!! route('match-purchase-orders.index') !!}"><i class="fa fa-circle"></i> Match Purchase Order
                                </a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['delivery-notes___view']))
                            <li class="@if (isset($model) && $model == 'delivery-notes') active @endif">
                                <a href="{!! route('delivery-notes.index') !!}"><i class="fa fa-circle"></i> Post Delivery Note
                                </a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['delivery-notes-invoices___view']))
                            <li class="@if (isset($model) && $model == 'delivery-notes-invoices') active @endif">
                                <a href="{!! route('delivery-notes-invoices.index') !!}"><i class="fa fa-circle"></i> Post Delivery
                                    Invoice
                                </a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['delivery-notes-schedules___view']))
                            <li class="@if (isset($model) && $model == 'delivery-notes-schedules') active @endif">
                                <a href="{!! route('delivery-notes-schedules.index') !!}"><i class="fa fa-circle"></i> Delivery Note
                                    Schedule
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['goods-returns___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'return-to-supplier-from-grn-create' ||
                            $model == 'return-to-supplier-from-grn-pending' ||
                            $model == 'return-to-supplier-from-grn-approve' ||
                            $model == 'return-to-supplier-from-grn-approved' ||
                            $model == 'returned-receive-purchase-order' ||
                            $model == 'processed-grns' ||
                            $model == 'return-accepted-receive-order' ||
                            $model == 'return-to-supplier-from-store-create' ||
                            $model == 'return-to-supplier-from-store-pending' ||
                            $model == 'return-to-supplier-from-store-approve' ||
                            $model == 'return-to-supplier-from-store-approved' ||
                            $model == 'return-to-supplier-from-store-rejected'
                        )) active @endif">
                    <a href="#">
                        <i class="fa fa-circle"></i> Goods Returns
                        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                    </a>

                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-grn___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'return-to-supplier-from-grn-create' ||
                                        $model == 'return-to-supplier-from-grn-pending' ||
                                        $model == 'return-to-supplier-from-grn-approve' ||
                                        $model == 'return-to-supplier-from-grn-approved')) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i>
                                    <span class="pull-right-container"><i
                                            class="fa fa-angle-left pull-right"></i></span>
                                    Return From GRN
                                </a>

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-grn___create']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-grn-create') active @endif">
                                            <a href="{!! route('return-to-supplier.from-grn.create') !!}"><i class="fa fa-circle"></i> Create
                                                Return </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-grn___view-pending']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-grn-pending') active @endif">
                                            <a href="{{ route('return-to-supplier.from-grn.pending') }}"><i
                                                    class="fa fa-circle"></i> Pending Returns</a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-grn___view-approved']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-grn-approved') active @endif">
                                            <a href="{{ route('return-to-supplier.from-grn.approved') }}"><i
                                                    class="fa fa-circle"></i> Approved Returns </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'return-to-supplier-from-store-create' ||
                                        $model == 'return-to-supplier-from-store-pending' ||
                                        $model == 'return-to-supplier-from-store-approve' ||
                                        $model == 'return-to-supplier-from-store-approved' ||
                                        $model == 'return-to-supplier-from-store-rejected'
                                    )) active @endif">
                                <a href="#">
                                    <i class="fa fa-circle"></i> Return From Store
                                    <span class="pull-right-container"><i
                                            class="fa fa-angle-left pull-right"></i></span>
                                </a>

                                <ul class="treeview-menu">
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___create']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-create') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.create') !!}"><i class="fa fa-circle"></i> Create
                                                return </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view-pending']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-pending') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.pending') !!}"><i class="fa fa-circle"></i> Pending
                                                returns </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view-approved']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-approved') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.approved') !!}"><i class="fa fa-circle"></i> Approved
                                                returns </a>
                                        </li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-from-store___view-rejected']))
                                        <li class="@if (isset($model) && $model == 'return-to-supplier-from-store-rejected') active @endif">
                                            <a href="{!! route('return-to-supplier.from-store.rejected') !!}"><i class="fa fa-circle"></i> Rejected
                                                returns </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-to-supplier-processed___view']))
                            <li class="@if (isset($model) && $model == 'processed-grns') active @endif">
                                <a href="{!! route('return-to-supplier.processed-returns') !!}"><i class="fa fa-circle"></i> Processed Returns </a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['pending-returns-receive-purchase-order___view']))
                            <li class="@if (isset($model) && $model == 'returned-receive-purchase-order') active @endif">
                                <a href="{!! route('returned-receive-purchase-order.index') !!}"><i class="fa fa-circle"></i> Pending Portal Request
                                </a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-accepted-receive-order___view']))
                            <li class="@if (isset($model) && $model == 'return-accepted-receive-order') active @endif">
                                <a href="{!! route('return-accepted-receive-order.index') !!}"><i class="fa fa-circle"></i> Returned Credit Notes
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'confirmed-receive-purchase-order' ||
                            $model == 'n-transfers' ||
                            $model == 'stock-return' ||
                            $model == 'process-receive-purchase-order' ||
                            $model == 'receive-purchase-order')) active @endif">
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
                                    href="{!! route('n-transfers.indexProcessed') . getReportDefaultFilterForTrialBalance() !!}"><i class="fa fa-circle"></i> Processed
                                    Transfers</a>
                            </li>
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
                            $model == 'detailed-stock-count-variance' ||
                            $model == 'summary-stock-count-variance' ||
                            $model == 'stock-counts-compare' ||
                            $model == 'stock-take-user-assignment' ||
                            $model == 'deviation-report' ||
                            $model == 'stock-debtors' ||
                            $model == 'stock-processing-sales' ||
                            $model == 'stock-processing-return' ||
                            $model == 'stock-count-blocked-users' ||
                            $model == 'stock-count-blocked-users-exemption-schedules' ||
                            $model == 'stock-uncompleted-sales' || 
                            $model == 'stock-non-debtors')) active @endif">
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
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-take-user-assignment___view']))
                            <li class="@if (isset($model) && $model == 'stock-take-user-assignment') active @endif"><a
                                    href="{!! route('admin.stock-counts-users-assingment') !!}"><i class="fa fa-circle"></i>
                                    Assign Stock Count Users</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-counts___view']))
                            <li class="@if (isset($model) && $model == 'stock-counts') active @endif"><a
                                    href="{!! route('admin.stock-counts') !!}"><i class="fa fa-circle"></i> Enter
                                    Stock Counts</a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-count-variance___detailed-view']))
                            <li class="@if (isset($model) && $model == 'detailed-stock-count-variance') active @endif"><a
                                    href="{!! route('admin.stock-count-variance.index') !!}"><i class="fa fa-circle"></i>Detailed
                                    Variance Report</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-count-variance___summary-view']))
                            <li class="@if (isset($model) && $model == 'summary-stock-count-variance') active @endif"><a
                                    href="{!! route('admin.stock-count-variance.summary') !!}"><i class="fa fa-circle"></i>Summary
                                    Variance Report</a></li>
                        @endif

                        
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-debtors___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'stock-debtors' ||
                                        $model == 'stock-non-debtors')) active @endif">
                                <a href="#"><i class="fa fa-circle"></i> Debtors
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-debtors___view']))
                                        <li class="@if (isset($model) && $model == 'stock-debtors') active @endif"><a
                                                href="{!! route('stock-debtors.index') !!}"><i class="fa fa-circle"></i> Stock
                                                Debtors</a></li>
                                    @endif

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-non-debtors___view']))
                                        <li class="@if (isset($model) && $model == 'stock-non-debtors') active @endif"><a
                                                href="{!! route('stock-non-debtors.index') !!}"><i class="fa fa-circle"></i> Non Stock
                                                Debtors</a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-processing___view']))
                            <li class="treeview @if (isset($model) &&
                                    ($model == 'stock-processing-sales' ||
                                        $model == 'stock-processing-return' ||
                                        $model == 'stock-uncompleted-sales')) active @endif">
                                <a href="#"><i class="fa fa-circle"></i> Stock Processing
                                    <span class="pull-right-container">
                                        <i class="fa fa-angle-left pull-right"></i>
                                    </span>
                                </a>
                                <ul class="treeview-menu">

                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-processing-sales___view']))
                                        <li class="@if (isset($model) && $model == 'stock-processing-sales') active @endif"><a
                                                href="{!! route('stock-processing.sales') !!}"><i class="fa fa-circle"></i> Sale
                                                (Short)</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-processing-return___view']))
                                        <li class="@if (isset($model) && $model == 'stock-processing-return') active @endif"><a
                                                href="{!! route('stock-processing.return') !!}"><i class="fa fa-circle"></i>Return
                                                (Excess)
                                            </a></li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-uncompleted-sales___view']))
                                        <li class="@if (isset($model) && $model == 'stock-uncompleted-sales') active @endif"><a
                                                href="{!! route('stock-uncompleted-sales.index') !!}"><i
                                                    class="fa fa-circle"></i>Uncompleted
                                                Entries
                                            </a></li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-count-blocked-users___view']))
                            <li class="@if (isset($model) && $model == 'stock-count-blocked-users') active @endif"><a
                                    href="{!! route('admin.stock-count-blocked-users.index') !!}"><i class="fa fa-circle"></i>Blocked Users</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['stock-count-blocked-users-exemption-schedules___view']))
                            <li class="@if (isset($model) && $model == 'stock-count-blocked-users-exemption-schedules') active @endif"><a
                                    href="{!! route('admin.stock-count-blocked-users.exemption-schedules') !!}"><i class="fa fa-circle"></i>Exemption Schedules</a></li>
                        @endif
                    </ul>
                </li>
            @endif

              {{-- Opening Balances Stock Take --}}
      




        {{-- Opening Balances Stock  Take End --}}

            @if (
                $logged_user_info->role_id == 1 ||
                    isset($my_permissions['utility___view']) ||
                    isset($my_permissions['supplier-user-management___view']))
                <li class="treeview @if (
                    (isset($model) && $model == 'utilities') ||
                        $model == 'supplier-user-management' ||
                        $model == 'retired-items' ||
                        $model == 'utility' ||
                        $model == 'download-branch-utilities' ||
                        $model == 'recalculate-qoh' ||
                        $model == 'utilities' ||
                        $model == 'promotion-groups' ||
                        $model == 'bulk_purchase_data' ||
                        $model == 'promotion-types' ||
                        $model == 'active-promotions' ||
                        $model == 'upload-new-items' ||
                        $model == 'verify-stocks' ||
                        $model == 'download-stocks' ||
                        $model == 'update-item-code' ||
                        $model == 'items-without-suppliers' ||
                        $model == 'update-item-prices' ||
                        $model == 'update-item-standard-cost' ||
                        $model == 'item-margins' ||
                        $model == 'update-item-selling-price' ||
                        $model == 'update-selling-price-and-standard-cost' ||
                        $model == 'competing-brands' ||
                        $model == 'update-stock-qoh' ||
                        $model == 'update-grn-utility' ||
                        $model == 'item-has-count' ||
                        $model == 'batch-retire-items') active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Utility
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___view']))
                            <li class="@if (isset($model) && $model == 'utilities') active @endif"><a
                                    href="{!! route('admin.utility.update-max-stock-and-reorder-level') !!}"><i class="fa fa-circle"></i>
                                    Update Max Stock / Reorder Level</a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___bulk_purchase_data']))
                            <li class="@if (isset($model) && $model == 'bulk_purchase_data') active @endif"><a
                                    href="{!! route('maintain-items.bulk_purchase_data') !!}"><i class="fa fa-circle"></i> Batch Update Price
                                    List
                                </a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___view']))
                            <li class="@if (isset($model) && $model == 'retired-items') active @endif">
                                <a href="{!! route('admin.utility.retired.items') !!}"><i class="fa fa-circle"></i>
                                    Retired Items
                                </a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___view']))
                            <li class="@if (isset($model) && $model == 'batch-retire-items') active @endif">
                                <a href="{!! route('admin.utility.batch.retire.items') !!}"><i class="fa fa-circle"></i>
                                    Batch Retire Items
                                </a>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___recalculate_qoh']))
                            <li class="@if (isset($model) && $model == 'recalculate-qoh') active @endif"><a
                                    href="{!! route('admin.utility.recalculate-qoh') !!}"><i class="fa fa-circle"></i>
                                    Recalculate New QOH</a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___supplier-user-management']))
                            <li class="@if (isset($model) && $model == 'supplier-user-management') active @endif"><a
                                    href="{!! route('utility.supplier_user_management') !!}"><i class="fa fa-circle"></i>
                                    Supplier User Management</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___view']))
                            <li class="@if (isset($model) && $model == 'utility') active @endif"><a
                                    href="{!! route('utility.update_bin') !!}"><i class="fa fa-circle"></i>
                                    Update Bin</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___item-selling-price']))
                            <li class="@if (isset($model) && $model == 'update-item-selling-price') active @endif"><a
                                    href="{!! route('utility.update_item_prices') !!}"><i class="fa fa-circle"></i>
                                    Update Selling Price</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___item-standard-cost']))
                            <li class="@if (isset($model) && $model == 'update-item-standard-cost') active @endif"><a
                                    href="{!! route('utility.update_item_standard_cost') !!}"><i class="fa fa-circle"></i>
                                    Update Costs</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___item-selling-price-and-standard-cost']))
                            <li class="@if (isset($model) && $model == 'update-selling-price-and-standard-cost') active @endif"><a
                                    href="{!! route('utility.update_item_selling_price_standard_cost') !!}"><i class="fa fa-circle"></i>
                                    Update Branch Pricing</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___item-margins']))
                            <li class="@if (isset($model) && $model == 'item-margins') active @endif"><a
                                    href="{!! route('utility.item_margins') !!}"><i class="fa fa-circle"></i>
                                    Update Item Margins</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___branch-utilities']))
                            <li class="@if (isset($model) && $model == 'download-branch-utilities') active @endif"><a
                                    href="{!! route('utility.branch_utilities') !!}"><i class="fa fa-circle"></i>
                                    Branch Utilities</a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___upload-new-items']))
                            <li class="@if (isset($model) && $model == 'upload-new-items') active @endif"><a
                                    href="{!! route('utility.upload_new_items') !!}"><i class="fa fa-circle"></i>
                                    Upload New Items</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___verify-stocks']))
                            <li class="@if (isset($model) && $model == 'verify-stocks') active @endif"><a
                                    href="{!! route('utility.verify_stocks') !!}"><i class="fa fa-circle"></i>
                                    Verify Stocks</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___download-stocks']))
                            <li class="@if (isset($model) && $model == 'download-stocks') active @endif"><a
                                    href="{!! route('utility.download_stocks') !!}"><i class="fa fa-circle"></i>
                                    Download Stocks</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['update-item-code___view']))
                            <li class="@if (isset($model) && $model == 'update-item-code') active @endif"><a
                                    href="{!! route('utility.update_item_code') !!}"><i class="fa fa-circle"></i>
                                    Update Item Stock Code</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['items-without-suppliers___view']))
                            <li class="@if (isset($model) && $model == 'items-without-suppliers') active @endif"><a
                                    href="{!! route('utility.items_without_suppliers') !!}"><i class="fa fa-circle"></i>
                                    Items Without Suppliers</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['competing-brands___view']))
                            <li class="@if (isset($model) && $model == 'competing-brands') active @endif"><a
                                    href="{!! route('competing-brands.index') !!}"><i class="fa fa-circle"></i>
                                    Competing Brands</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['update-grn-utility___view']))
                            <li class="@if (isset($model) && $model == 'update-grn-utility') active @endif"><a
                                    href="{!! route('utilities.grn-update') !!}"><i class="fa fa-circle"></i>
                                    GRN Update</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['item-has-count___view']))
                            <li class="@if (isset($model) && $model == 'item-has-count') active @endif"><a
                                    href="{!! route('utility.item_has_count_utility') !!}"><i class="fa fa-circle"></i>
                                    Update Item Count</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-utility-logs___view']))
                <li class="treeview @if (isset($model) && $model == 'inventory-utility-logs') active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Utility Logs
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-utility-logs___view-inventory-utility-logs']))
                            <li class="@if (isset($model) && $model == 'utilities') active @endif"><a
                                    href="{!! route('utility.inventory-utility-logs.index') !!}"><i class="fa fa-circle"></i>
                                    Logs</a></li>
                        @endif
                    </ul>
                </li>
            @endif
            @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory-reports___view']))
                <li class="@if (isset($model) &&
                        ($model == 'inventory-reports' ||
                            $model == 'items_negetive_listing' ||
                            $model == 'inventory_location_stock_summary' ||
                            $model == 'inventory_location_as_at' ||
                            $model == 'maintain-items-suggested-order-report' ||
                            $model == 'inventory_sales_report' ||
                            $model == 'items-data-purchases' ||
                            $model == 'transfer-inwards-report' ||
                            $model == 'supplier-user-report' ||
                            $model == 'CTN-without-children' ||
                            $model == 'max-stock-report' ||
                            $model == 'average-sales-report' ||
                            $model == 'missing-items-report' ||
                            $model == 'overstock-report' ||
                            $model == 'inactive-stock-report' ||
                            $model == 'dead-stock-report' ||
                            $model == 'items-data-sales' ||
                            $model == 'price-timeline-report' ||
                            $model == 'items-list-report' ||
                            $model == 'missing-split-report' ||
                            $model == 'grn-summary-by-supplier-report' ||
                            $model == 'out-of-stock-report' ||
                            $model == 'discount-items-report' ||
                            $model == 'promotion-items-report' ||
                            $model == 'item-sales-route-performance-report' ||
                            $model == 'slow-moving-items-report' ||
                            $model == 'child-vs-mother-qoh-report' ||
                            $model == 'item-sales-route-performance-report' ||
                            $model == 'no-supplier-items-report')) active @endif">
                    <a href="{!! route('inventory-reports.index') !!}"><i class="fa fa-circle"></i>
                        Reports
                    </a>
                </li>
            @endif
        </ul>
    </li>
@endif
