@if ($logged_user_info->role_id == 1 || isset($my_permissions['financial-management___view']))
    <li class="treeview @if (isset($model) &&
            ($model == 'route-manager' ||
                $model == 'pack-size' ||
                $model == 'account-sections' ||
                $model == 'account-groups' ||
                $model == 'branches' ||
                $model == 'departments' ||
                $model == 'company-preferences' ||
                $model == 'settings' ||
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
                $model == 'scheduled-notifications' ||
                $model == 'teams' ||
                $model == 'manage-delivery-centers' ||
                $model == 'wallet-matrix' ||
                $model == 'petty-cash-type' ||
                $model == 'petty-cash-request-types' ||
                $model == 'payment-modes' ||
                $model == 'loaders' ||
                $model == 'gl_tags' ||
                $model == 'return-reasons' ||
                $model == 'log-in-activity' ||
                $model == 'promotion-groups' ||
                $model == 'promotion-types' ||
                $model == 'active-promotions' ||
                $model == 'hampers' ||
                $model == 'cheque-bank' ||
                $model == 'projects' ||
                $model == 'support-team' ||
                $model == 'ticket-category' ||
                $model == 'activity-logs')) active @endif">
        <a href="#"><i class="fa fa-fw fa-cogs"></i><span>Platform Admin</span>
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
                            $model == 'petty-cash-request-types' ||
                            $model == 'sub-account-sections' ||
                            $model == 'departments' ||
                            $model == 'gl_tags' ||
                            $model == 'projects')) active @endif">
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
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['projects___view']))
                                        <li class="@if (isset($model) && $model == 'projects') active @endif">
                                            <a href="{!! route('projects.index') !!}"><i class="fa fa-circle"></i>
                                                Projects</a>
                                        </li>
                                    @endif
                                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['gl_tags___view']))
                                        <li class="@if (isset($model) && $model == 'gl_tags') active @endif">
                                            <a href="{!! route('gl_tags.index') !!}"><i class="fa fa-circle"></i>
                                                Gl Tags</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['wallet-matrix___view']))
                            <li class="@if (isset($model) && $model == 'wallet-matrix') active @endif"><a
                                    href="{!! route('wallet-matrix.index') !!}"><i class="fa fa-circle"></i>Wallet
                                    Matrix</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash___view']))
                            <li class="@if (isset($model) && $model == 'petty-cash-type') active @endif"><a
                                    href="{!! route('petty-cash-types.index') !!}"><i class="fa fa-circle"></i>Wallet
                                    Types</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-request-types___view']))
                            <li class="@if (isset($model) && $model == 'petty-cash-request-types') active @endif">
                                <a href="{!! route('petty-cash-request.types') !!}">
                                    <i class="fa fa-circle"></i>
                                    Petty Cash Request Types
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($logged_user_info->role_id == 1 || isset($my_permissions['finanacial-system-setup___view']))
                <li class="treeview @if (isset($model) &&
                        ($model == 'company-preferences' ||
                            $model == 'settings' ||
                            $model == 'tax-manager' ||
                            $model == 'currency-managers' ||
                            $model == 'accounting-periods' ||
                            $model == 'number-series' ||
                            $model == 'roles' ||
                            $model == 'user-denied-accesses' ||
                            $model == 'teams' ||
                            $model == 'employees' ||
                            $model == 'return-reasons' ||
                            $model == 'log-in-activity' ||
                             $model == 'cheque-bank' ||
                            $model == 'loaders'||
                            $model == 'support-team' ||
                            $model == 'ticket-category' ||
                            $model == 'activity-logs')) active @endif">
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
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['settings___view']))
                            <li class="@if (isset($model) && $model == 'settings') active @endif">
                                <a href="{!! route('settings.index') !!}"><i class="fa fa-circle"></i>
                                    Settings
                                </a>
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
                                    <span>Roles and Permissions</span></a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['employees___view']))
                            <li class="@if (isset($model) && $model == 'employees') active @endif"><a
                                    href="{!! route('employees.index') !!}"><i class="fa fa-circle"></i>
                                    <span>Users</span></a>
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
                                <a href="{!! route('teams.index') !!}"><i class="fa fa-circle"></i><span>Teams</span></a>
                            </li>
                        @endif

                        @if ($logged_user_info->role_id == 1)
                            <li class="@if (isset($model) && $model == 'employees') active @endif"><a
                                    href="{!! route('userlog.index') !!}"><i class="fa fa-circle"></i>
                                    <span>User Logs</span></a></li>
                        @endif



                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['log-in-activity___view']))
                            <li class="@if (isset($model) && $model == 'log-in-activity') active @endif"><a
                                    href="{!! route('user-login-activity-report') !!}"><i class="fa fa-circle"></i>
                                    <span>Log in Activity</span></a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['activity-logs___view']))
                            <li class="@if (isset($model) && $model == 'activity-logs') active @endif"><a
                                    href="{!! route('activitylogs.index') !!}"><i class="fa fa-circle"></i>
                                    <span>Activity Logs</span></a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['return-reasons___view']))
                            <li class="@if (isset($model) && $model == 'return-reasons') active @endif"><a
                                    href="{!! route('return-reasons.index') !!}"><i class="fa fa-circle"></i>
                                    <span>Return Reasons</span></a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['cheque-bank___view']))
                            <li class="@if (isset($model) && $model == 'cheque-bank') active @endif"><a
                                    href="{!! route('cheque-banks') !!}"><i class="fa fa-circle"></i>
                                    <span>Banks </span></a></li>
                        @endif

                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['help-desk-setup___view']))
                        <li class="treeview @if (isset($model) && $model == 'support-team' || $model == 'ticket-category') active @endif">
                            <a href="#"><i class="fa fa-circle"></i> Help Desk Setup
                                <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['support-team___view']))
                                    <li class="@if (isset($model) && $model == 'support-team') active @endif"><a
                                            href="{!! route('support-team.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Support Team</span></a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['ticket-category___view']))
                                    <li class="@if (isset($model) && $model == 'ticket-category') active @endif"><a
                                            href="{!! route('ticket-category.index') !!}"><i class="fa fa-circle"></i>
                                            <span>Ticket Category</span></a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                            @if ($logged_user_info->role_id == 1 || isset($my_permissions['incentive-settings___view']))
                                <li class="@if (isset($model) && $model == 'incentive-settings') active @endif"><a
                                            href="{!! route('incentive-settings.index') !!}"><i class="fa fa-circle"></i>
                                        <span>Incentives</span></a></li>
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
                            $model == 'promotion-groups' ||
                            $model == 'promotion-types' ||
                            $model == 'active-promotions' ||
                            $model == 'hampers' ||
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
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___promotion-type-view']))
                                <li class="@if (isset($model) && $model == 'promotion-types') active @endif"><a
                                            href="{!! route('promotion-types') !!}"><i class="fa fa-circle"></i>
                                        Promotion Type</a></li>
                            @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___promotion-group-view']))
                            <li class="@if (isset($model) && $model == 'promotion-groups') active @endif"><a
                                    href="{!! route('promotion-group.index') !!}"><i class="fa fa-circle"></i>
                                    Promotion Groups</a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___active-promotions-view']))
                            <li class="@if (isset($model) && $model == 'active-promotions') active @endif"><a
                                    href="{!! route('active-promotions.index') !!}"><i class="fa fa-circle"></i>
                                    Active Promotion </a></li>
                        @endif
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['utility___hampers-view']))
                            <li class="@if (isset($model) && $model == 'hampers') active @endif"><a
                                    href="{!! route('hampers.index') !!}"><i class="fa fa-circle"></i>
                                    Hampers </a></li>
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

                        {{-- <li class="@if (isset($model) && $model == 'manage-routes') active @endif">
                            <a href="{!! route('manage-routes.listing') !!}">
                                <i class="fa fa-circle"></i> Route Listing
                            </a>
                        </li> --}}
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
                    <a href="#"><i class="fa fa-circle"></i> Payments
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
                <li class="treeview @if (isset($model) && ($model == 'alerts' || $model == 'scheduled-notifications')) active @endif">
                    <a href="#"><i class="fa fa-circle"></i> Alerts & Notifications
                        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
                    </a>

                    <ul class="treeview-menu">
                        @if ($logged_user_info->role_id == 1 || isset($my_permissions['alerts___view']))
                            <li class="@if (isset($model) && $model == 'alerts') active @endif">
                                <a href="{!! route('alerts.index') !!}"><i class="fa fa-circle"></i> Alerts
                                </a>
                            </li>
                        @endif
                        @if (can('view', 'scheduled-notifications'))
                            <li class="@if (isset($model) && $model == 'scheduled-notifications') active @endif">
                                <a href="{!! route('scheduled-notifications.index') !!}">
                                    <i class="fa fa-circle"></i>
                                    Scheduled Notifications
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
        </ul>
    </li>
@endif
