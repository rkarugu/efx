@php
    $logged_user_info = getLoggeduserProfile();
    $my_permissions = $logged_user_info->permissions;
    $currentRoute = Route::currentRouteName() ?? '';
    
    // Determine active menu based on current route or query parameter
    $activeMenu = request()->query('menu', session('activeMenu', ''));
    
    // If no menu query param, detect from current route
    if (empty($activeMenu) && !empty($currentRoute)) {
        if (str_contains($currentRoute, 'sales') || str_contains($currentRoute, 'pos') || str_contains($currentRoute, 'dispatch') || str_contains($currentRoute, 'return')) {
            $activeMenu = 'revenue';
        } elseif (str_contains($currentRoute, 'delivery') || str_contains($currentRoute, 'logistics') || str_contains($currentRoute, 'loading')) {
            $activeMenu = 'logistics';
        } elseif (str_contains($currentRoute, 'purchase') || str_contains($currentRoute, 'lpo') || str_contains($currentRoute, 'requisition')) {
            $activeMenu = 'purchases';
        } elseif (str_contains($currentRoute, 'supplier')) {
            $activeMenu = 'supplier';
        } elseif (str_contains($currentRoute, 'vendor') || str_contains($currentRoute, 'payable')) {
            $activeMenu = 'vendor';
        } elseif (str_contains($currentRoute, 'inventory') || str_contains($currentRoute, 'stock')) {
            $activeMenu = 'inventory';
        } elseif (str_contains($currentRoute, 'ledger') || str_contains($currentRoute, 'journal') || str_contains($currentRoute, 'account')) {
            $activeMenu = 'ledger';
        } elseif (str_contains($currentRoute, 'hr') || str_contains($currentRoute, 'employee') || str_contains($currentRoute, 'payroll')) {
            $activeMenu = 'hr';
        } elseif (str_contains($currentRoute, 'fleet') || str_contains($currentRoute, 'vehicle') || str_contains($currentRoute, 'fuel')) {
            $activeMenu = 'fleet';
        } elseif (str_contains($currentRoute, 'helpdesk') || str_contains($currentRoute, 'ticket')) {
            $activeMenu = 'helpdesk';
        } elseif (str_contains($currentRoute, 'communication') || str_contains($currentRoute, 'message')) {
            $activeMenu = 'comms';
        } elseif (str_contains($currentRoute, 'admin') || str_contains($currentRoute, 'user') || str_contains($currentRoute, 'role')) {
            $activeMenu = 'admin';
        }
    }
@endphp

<div class="header-main-menu">
    <!-- Mobile menu toggle -->
    <button class="header-menu-toggle" id="mobile-menu-toggle">
        <i class="fa fa-bars"></i>
        <span>Main Menu</span>
    </button>

    <!-- Desktop horizontal menu -->
    <ul class="header-menu-desktop">
        <!-- Executive Summary - Direct Link -->
        <li class="@if($currentRoute == 'admin.dashboard') active @endif">
            <a href="{!! route('admin.dashboard') !!}">
                <i class="fa fa-tachometer-alt"></i>
                <span>Executive Summary</span>
            </a>
        </li>

        <!-- Business Insights - Direct Link -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['management-dashboard___view']))
            <li class="@if($currentRoute == 'admin.chairman-dashboard') active @endif">
                <a href="{!! route('admin.chairman-dashboard') !!}">
                    <i class="fa fa-chart-bar"></i>
                    <span>Business Insights</span>
                </a>
            </li>
        @endif

        <!-- Revenue Management -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables___view']))
            <li class="@if($activeMenu == 'revenue') active @endif">
                <a href="#" data-menu="revenue" class="menu-trigger">
                    <i class="fa fa-chart-line"></i>
                    <span>Revenue Management</span>
                </a>
            </li>
        @endif

        <!-- Delivery & Logistics -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['delivery_and_logistics___view']))
            <li class="@if($activeMenu == 'logistics') active @endif">
                <a href="#" data-menu="logistics" class="menu-trigger">
                    <i class="fa fa-truck"></i>
                    <span>Delivery & Logistics</span>
                </a>
            </li>
        @endif

        <!-- Purchase Procurement -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['purchases___view']))
            <li class="@if($activeMenu == 'purchases') active @endif">
                <a href="#" data-menu="purchases" class="menu-trigger">
                    <i class="fa fa-shopping-cart"></i>
                    <span>Purchase Procurement</span>
                </a>
            </li>
        @endif

        <!-- Supplier Portal -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['supplier-portal___view']))
            <li class="@if($activeMenu == 'supplier') active @endif">
                <a href="#" data-menu="supplier" class="menu-trigger">
                    <i class="fa fa-handshake"></i>
                    <span>Supplier Portal</span>
                </a>
            </li>
        @endif

        <!-- Vendor Management -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-payables___view']))
            <li class="@if($activeMenu == 'vendor') active @endif">
                <a href="#" data-menu="vendor" class="menu-trigger">
                    <i class="fa fa-file-invoice-dollar"></i>
                    <span>Vendor Management</span>
                </a>
            </li>
        @endif

        <!-- Inventory -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['inventory___view']))
            <li class="@if($activeMenu == 'inventory') active @endif">
                <a href="#" data-menu="inventory" class="menu-trigger">
                    <i class="fa fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
        @endif

        <!-- General Ledger -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['genralledger___view']))
            <li class="@if($activeMenu == 'ledger') active @endif">
                <a href="#" data-menu="ledger" class="menu-trigger">
                    <i class="fa fa-book"></i>
                    <span>General Ledger</span>
                </a>
            </li>
        @endif

        <!-- HR And Payroll -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['hr-and-payroll___view']))
            <li class="@if($activeMenu == 'hr') active @endif">
                <a href="#" data-menu="hr" class="menu-trigger">
                    <i class="fa fa-users"></i>
                    <span>HR And Payroll</span>
                </a>
            </li>
        @endif

        <!-- Fleet Management -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['fleet-management-module___view']))
            <li class="@if($activeMenu == 'fleet') active @endif">
                <a href="#" data-menu="fleet" class="menu-trigger">
                    <i class="fa fa-car"></i>
                    <span>Fleet Management</span>
                </a>
            </li>
        @endif

        <!-- Help Desk -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['help-desk___view']))
            <li class="@if($activeMenu == 'helpdesk') active @endif">
                <a href="#" data-menu="helpdesk" class="menu-trigger">
                    <i class="fa fa-question-circle"></i>
                    <span>Help Desk</span>
                </a>
            </li>
        @endif

        <!-- Communications Centre -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['communication-center___view']))
            <li class="@if($activeMenu == 'comms') active @endif">
                <a href="#" data-menu="comms" class="menu-trigger">
                    <i class="fa fa-comments"></i>
                    <span>Communications Centre</span>
                </a>
            </li>
        @endif

        <!-- Platform Admin -->
        @if ($logged_user_info->role_id == 1 || isset($my_permissions['financial-management___view']))
            <li class="@if($activeMenu == 'admin') active @endif">
                <a href="#" data-menu="admin" class="menu-trigger">
                    <i class="fa fa-cog"></i>
                    <span>Platform Admin</span>
                </a>
            </li>
        @endif
    </ul>

    <!-- Mobile dropdown menu -->
    <div class="header-menu-mobile-dropdown" id="mobile-menu-dropdown">
        <ul class="mobile-menu-list">
            <!-- Same items as desktop, will be populated by JavaScript -->
        </ul>
    </div>
</div>

<script>
    // Store active menu in session storage
    document.addEventListener('DOMContentLoaded', function() {
        var activeMenu = '{{ $activeMenu }}';
        if (activeMenu) {
            sessionStorage.setItem('activeMenu', activeMenu);
        }
    });
</script>
