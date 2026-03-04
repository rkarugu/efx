<aside class="main-sidebar">
    @php
        $logged_user_info = getLoggeduserProfile();
        $my_permissions = $logged_user_info->permissions;
        $route_name = \Route::currentRouteName();
        $currentRoute = Route::currentRouteName() ?? '';
        $model = $model ?? null; // Initialize if not set
        $rmodel = $rmodel ?? null; // Initialize if not set
        
        // Determine active menu from query parameter or session
        $activeMenu = request()->query('menu', session('activeMenu', ''));
        
        // Auto-detect menu from current route if not set
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
            } elseif (str_contains($currentRoute, 'user') || str_contains($currentRoute, 'role')) {
                $activeMenu = 'admin';
            }
        }
        
        // Menu title mapping
        $menuTitles = [
            'revenue' => 'REVENUE MANAGEMENT',
            'logistics' => 'DELIVERY & LOGISTICS',
            'purchases' => 'PURCHASE PROCUREMENT',
            'supplier' => 'SUPPLIER PORTAL',
            'vendor' => 'VENDOR MANAGEMENT',
            'inventory' => 'INVENTORY',
            'ledger' => 'GENERAL LEDGER',
            'hr' => 'HR AND PAYROLL',
            'fleet' => 'FLEET MANAGEMENT',
            'helpdesk' => 'HELP DESK',
            'comms' => 'COMMUNICATIONS CENTRE',
            'admin' => 'PLATFORM ADMIN'
        ];
        
        $menuTitle = $menuTitles[$activeMenu] ?? 'MAIN NAVIGATION';
    @endphp
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
            <li class="header sidebar-menu-title">
                <span id="active-menu-title">{{ $menuTitle }}</span>
            </li>
            
            <!-- Dynamic sub-menu container (loaded by AJAX or server-side) -->
            <span id="sidebar-submenu-container">
                @if($activeMenu == 'revenue')
                    @include('admin.includes.sidebar_includes.sales_and_receivables')
                @elseif($activeMenu == 'logistics')
                    @include('admin.includes.sidebar_includes.logistics')
                @elseif($activeMenu == 'purchases')
                    @include('admin.includes.sidebar_includes.purchases')
                @elseif($activeMenu == 'supplier')
                    @include('admin.includes.sidebar_includes.supplier_portal')
                @elseif($activeMenu == 'vendor')
                    @include('admin.includes.sidebar_includes.accounts_payable')
                @elseif($activeMenu == 'inventory')
                    @include('admin.includes.sidebar_includes.inventory')
                @elseif($activeMenu == 'ledger')
                    @include('admin.includes.sidebar_includes.general_ledger')
                @elseif($activeMenu == 'hr')
                    @include('admin.includes.sidebar_includes.hr')
                @elseif($activeMenu == 'fleet')
                    @include('admin.includes.sidebar_includes.fleet')
                @elseif($activeMenu == 'helpdesk')
                    @include('admin.includes.sidebar_includes.help_desk')
                @elseif($activeMenu == 'comms')
                    @include('admin.includes.sidebar_includes.communication_centre')
                @elseif($activeMenu == 'admin')
                    @include('admin.includes.sidebar_includes.system_administration')
                @else
                    <!-- Default: Show first accessible menu -->
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['sales-and-receivables___view']))
                        @include('admin.includes.sidebar_includes.sales_and_receivables')
                    @elseif ($logged_user_info->role_id == 1 || isset($my_permissions['delivery_and_logistics___view']))
                        @include('admin.includes.sidebar_includes.logistics')
                    @elseif ($logged_user_info->role_id == 1 || isset($my_permissions['purchases___view']))
                        @include('admin.includes.sidebar_includes.purchases')
                    @endif
                @endif
            </span>
        </ul>
    </section>
</aside>
