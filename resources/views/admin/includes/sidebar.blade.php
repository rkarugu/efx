<aside class="main-sidebar">
    @php
        $logged_user_info = getLoggeduserProfile();
        $my_permissions = $logged_user_info->permissions;
        $route_name = \Route::currentRouteName();
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
            <li class="header">MAIN NAVIGATION</li>
            <li><a href="{!! route('admin.dashboard') !!}"><i class="fa fa-tachometer-alt"></i> <span>Executive Summary</span></a></li>
            
            @if ($logged_user_info->role_id == 1 || isset($my_permissions['management-dashboard___view']))
                <li class="@if (isset($model) && $model == 'management-dashboard') active @endif">
                    <a href="{!! route('admin.chairman-dashboard') !!}">
                        <i class="fa fa-chart-bar"></i>
                        <span>Business Insights</span>
                    </a>
                </li>
            @endif

            @include('admin.includes.sidebar_includes.sales_and_receivables')

            @include('admin.includes.sidebar_includes.logistics')

            @include('admin.includes.sidebar_includes.purchases')

            @include('admin.includes.sidebar_includes.supplier_portal')

            @include('admin.includes.sidebar_includes.accounts_payable')

            @include('admin.includes.sidebar_includes.inventory')

            @include('admin.includes.sidebar_includes.general_ledger')

            @include('admin.includes.sidebar_includes.hr')

            @include('admin.includes.sidebar_includes.fleet')

            @include('admin.includes.sidebar_includes.help_desk')

            @include('admin.includes.sidebar_includes.communication_centre')

            @include('admin.includes.sidebar_includes.system_administration')
        </ul>
    </section>
</aside>
