<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#667eea">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Delivery Driver App</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('delivery-app-manifest.json') }}">
    
    <!-- Icons -->
    <link rel="apple-touch-icon" href="{{ asset('assets/images/delivery-icon-192.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/delivery-icon-32.png') }}">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/bower_components/bootstrap/dist/css/bootstrap.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fontawesome/css/solid.css') }}">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            overflow-x: hidden;
        }
        
        .app-container {
            max-width: 100%;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .app-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            text-align: center;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .app-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .app-header .subtitle {
            margin-top: 5px;
            opacity: 0.8;
            font-size: 14px;
        }
        
        .app-content {
            padding: 20px;
            min-height: calc(100vh - 120px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .app-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .app-card h3 {
            margin: 0 0 15px 0;
            color: var(--dark-color);
            font-size: 18px;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            width: 100%;
            margin: 10px 0;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: var(--success-color);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            width: 100%;
            margin: 10px 0;
            transition: all 0.3s ease;
        }
        
        .btn-success:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-warning {
            background: var(--warning-color);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            width: 100%;
            margin: 10px 0;
        }
        
        .item-list {
            max-height: 400px;
            overflow-y: auto;
            margin: 15px 0;
        }
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .item-row.checked {
            background: #d4edda;
            border-left-color: var(--success-color);
        }
        
        .item-checkbox {
            width: 24px;
            height: 24px;
            margin-right: 15px;
            cursor: pointer;
            accent-color: var(--success-color);
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .item-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
            flex-wrap: wrap;
        }
        
        .item-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .item-meta-item i {
            color: var(--primary-color);
        }
        
        .item-quantity {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 18px;
            min-width: 60px;
            text-align: right;
        }
        
        .customer-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .route-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .route-header:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .route-header i.fa-map-marked-alt {
            font-size: 20px;
        }
        
        .center-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 10px 0 10px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            border-left: 4px solid var(--info-color);
            transition: all 0.3s ease;
        }
        
        .center-header:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .center-header i.fa-building {
            color: var(--info-color);
            font-size: 18px;
        }
        
        .expand-icon {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        
        .expand-icon.expanded {
            transform: rotate(180deg);
        }
        
        .route-content {
            margin-left: 10px;
        }
        
        .center-content {
            margin-left: 30px;
        }
        
        .customer-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-color);
        }
        
        .customer-card.delivered {
            border-left-color: var(--success-color);
            background: #d4edda;
        }
        
        .customer-card.in-progress {
            border-left-color: var(--warning-color);
            background: #fff3cd;
        }
        
        .customer-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .customer-details {
            font-size: 12px;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: #e9ecef;
            color: #495057;
        }
        
        .status-in-progress {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .no-schedule {
            text-align: center;
            padding: 40px 20px;
            color: white;
        }
        
        .no-schedule i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .delivery-code-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 18px;
            text-align: center;
            letter-spacing: 2px;
            margin: 10px 0;
        }
        
        .delivery-code-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        /* Toast animation */
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <div class="app-header">
            <h1><i class="fa fa-truck"></i> Delivery Driver</h1>
            <div class="subtitle">Welcome back, {{ Auth::user()->name }}!</div>
        </div>
        
        <!-- Main Content -->
        <div class="app-content">
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">{{ $dashboardData['today_stats']['completed_deliveries'] ?? 0 }}</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $dashboardData['today_stats']['active_schedules'] ?? 0 }}</div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $dashboardData['today_stats']['delivered_customers'] ?? 0 }}</div>
                    <div class="stat-label">Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">{{ $dashboardData['today_stats']['pending_customers'] ?? 0 }}</div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            
            @if($dashboardData['active_schedule'])
                @php $schedule = $dashboardData['active_schedule']; @endphp
                
                @if($schedule['status'] === 'consolidated')
                    <!-- Item Verification Screen -->
                    <div class="app-card">
                        <h3><i class="fa fa-clipboard-check"></i> Verify Items Before Starting</h3>
                        <p>Please check each item below to confirm you have received them:</p>
                        
                        <div class="item-list" id="itemsList">
                            @if(!empty($schedule['items_grouped']))
                                @foreach($schedule['items_grouped'] as $itemRouteIndex => $itemRoute)
                                    <div class="route-header" onclick="toggleRoute({{ $itemRouteIndex }})">
                                        <i class="fa fa-map-marked-alt"></i>
                                        <div style="flex: 1;">
                                            <strong>{{ $itemRoute['route_name'] }}</strong>
                                            @if(!empty($itemRoute['shift_names']))
                                                <div style="font-size: 12px; opacity: 0.9; margin-top: 3px;">
                                                    <i class="fa fa-user"></i>
                                                    {{ implode(', ', $itemRoute['shift_names']) }}
                                                </div>
                                            @endif
                                        </div>
                                        <span class="expand-icon" id="route-icon-{{ $itemRouteIndex }}">
                                            <i class="fa fa-chevron-down"></i>
                                        </span>
                                    </div>

                                    <div class="route-content" id="route-{{ $itemRouteIndex }}" style="display: none;">
                                        @foreach(($itemRoute['items'] ?? []) as $item)
                                            <div class="item-row" data-item-id="{{ $item['inventory_item_id'] }}">
                                                <input type="checkbox" class="item-checkbox" onchange="toggleItemCheck(this)">
                                                <div class="item-details">
                                                    <div class="item-name">{{ $item['item_name'] }}</div>
                                                    <div class="item-meta">
                                                        <div class="item-meta-item">
                                                            <i class="fa fa-map-marker-alt"></i>
                                                            <span>Bin: {{ $item['bin_location'] }}</span>
                                                        </div>
                                                        <div class="item-meta-item">
                                                            <i class="fa fa-box"></i>
                                                            <span>{{ $item['unit'] }}</span>
                                                        </div>
                                                        <div class="item-meta-item">
                                                            <i class="fa fa-cubes"></i>
                                                            <span><strong>Qty: {{ $item['quantity'] }}</strong></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @else
                                @foreach($schedule['items'] as $item)
                                    <div class="item-row" data-item-id="{{ $item['inventory_item_id'] }}">
                                        <input type="checkbox" class="item-checkbox" onchange="toggleItemCheck(this)">
                                        <div class="item-details">
                                            <div class="item-name">{{ $item['item_name'] }}</div>
                                            <div class="item-meta">
                                                <div class="item-meta-item">
                                                    <i class="fa fa-map-marker-alt"></i>
                                                    <span>Bin: {{ $item['bin_location'] }}</span>
                                                </div>
                                                <div class="item-meta-item">
                                                    <i class="fa fa-box"></i>
                                                    <span>{{ $item['unit'] }}</span>
                                                </div>
                                                <div class="item-meta-item">
                                                    <i class="fa fa-cubes"></i>
                                                    <span><strong>Qty: {{ $item['quantity'] }}</strong></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        
                        <button class="btn btn-success" id="acceptItemsBtn" onclick="acceptItems({{ $schedule['id'] }})" disabled>
                            <i class="fa fa-check"></i> Accept Items & Start Shift
                        </button>
                        <p style="text-align: center; color: #666; font-size: 13px; margin-top: 10px;">
                            <span id="checkedCount">0</span> of {{ $schedule['items_count'] ?? count($schedule['items']) }} items verified
                        </p>
                    </div>
                    
                @elseif($schedule['status'] === 'loaded')
                    <!-- Ready to Start Delivery -->
                    <div class="app-card">
                        <h3><i class="fa fa-play-circle"></i> Ready to Start Delivery</h3>
                        <p><strong>{{ $schedule['delivery_number'] }}</strong></p>
                        <p><i class="fa fa-route"></i> {{ $schedule['route_name'] }}</p>
                        <p><i class="fa fa-truck"></i> {{ $schedule['vehicle'] }}</p>
                        <p><i class="fa fa-users"></i> {{ $schedule['customers_count'] }} customers</p>
                        
                        <!-- Financial Summary -->
                        <div style="background: #f8f9fa; border-radius: 10px; padding: 15px; margin: 15px 0;">
                            <h4 style="margin: 0 0 10px 0; font-size: 16px; color: #495057;">
                                <i class="fa fa-money-bill-wave"></i> Collection Summary
                            </h4>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="color: #666;">Total to Collect:</span>
                                <strong style="color: #495057; font-size: 18px;">KES {{ number_format($schedule['total_amount'], 2) }}</strong>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary" onclick="startDelivery({{ $schedule['id'] }})">
                            <i class="fa fa-play"></i> Start Delivery
                        </button>
                    </div>
                    
                @elseif($schedule['status'] === 'in_progress')
                    <!-- Delivery in Progress -->
                    <div class="app-card">
                        <h3><i class="fa fa-truck-moving"></i> Delivery in Progress</h3>
                        <p><strong>{{ $schedule['delivery_number'] }}</strong></p>
                        <p>Progress: {{ $schedule['delivered_count'] }}/{{ $schedule['customers_count'] }} customers</p>
                        
                        <!-- Financial Summary -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; padding: 20px; margin: 15px 0; color: white;">
                            <h4 style="margin: 0 0 15px 0; font-size: 16px; opacity: 0.9;">
                                <i class="fa fa-money-bill-wave"></i> Collection Summary
                            </h4>
                            
                            <div style="background: rgba(255,255,255,0.15); border-radius: 10px; padding: 12px; margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="opacity: 0.9;">Total Amount:</span>
                                    <strong style="font-size: 18px;">KES {{ number_format($schedule['total_amount'], 2) }}</strong>
                                </div>
                            </div>
                            
                            <div style="background: rgba(40, 167, 69, 0.3); border-radius: 10px; padding: 12px; margin-bottom: 10px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="opacity: 0.9;"><i class="fa fa-check-circle"></i> Collected:</span>
                                    <strong style="font-size: 18px; color: #90EE90;">KES {{ number_format($schedule['collected_amount'], 2) }}</strong>
                                </div>
                            </div>
                            
                            <div style="background: rgba(255, 193, 7, 0.3); border-radius: 10px; padding: 12px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="opacity: 0.9;"><i class="fa fa-clock"></i> Pending:</span>
                                    <strong style="font-size: 18px; color: #FFD700;">KES {{ number_format($schedule['pending_amount'], 2) }}</strong>
                                </div>
                            </div>
                            
                            @php
                                $collectionPercentage = $schedule['total_amount'] > 0 ? ($schedule['collected_amount'] / $schedule['total_amount']) * 100 : 0;
                            @endphp
                            <div style="margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px;">
                                    <span>Collection Progress</span>
                                    <span>{{ number_format($collectionPercentage, 1) }}%</span>
                                </div>
                                <div style="background: rgba(255,255,255,0.2); height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div style="background: #90EE90; height: 100%; width: {{ $collectionPercentage }}%; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="customer-list">
                            @foreach($schedule['customers_grouped'] as $routeIndex => $route)
                                @php
                                    // Calculate route-level totals
                                    $routeTotalShops = 0;
                                    $routeDeliveredShops = 0;
                                    foreach($route['centers'] as $center) {
                                        $routeTotalShops += count($center['customers']);
                                        $routeDeliveredShops += collect($center['customers'])->where('status', 'delivered')->count();
                                    }
                                @endphp
                                <!-- Route Header -->
                                <div class="route-header" onclick="toggleRoute({{ $routeIndex }})">
                                    <i class="fa fa-map-marked-alt"></i>
                                    <div style="flex: 1;">
                                        <strong>{{ $route['route_name'] }}</strong>
                                        <div style="font-size: 12px; opacity: 0.9; margin-top: 3px;">
                                            <i class="fa fa-store"></i> {{ $routeDeliveredShops }}/{{ $routeTotalShops }} shops delivered
                                        </div>
                                    </div>
                                    <span class="expand-icon" id="route-icon-{{ $routeIndex }}">
                                        <i class="fa fa-chevron-down"></i>
                                    </span>
                                </div>
                                
                                <!-- Centers in Route -->
                                <div class="route-content" id="route-{{ $routeIndex }}" style="display: none;">
                                    @foreach($route['centers'] as $centerIndex => $center)
                                        @php
                                            $totalShops = count($center['customers']);
                                            $deliveredShops = collect($center['customers'])->where('status', 'delivered')->count();
                                        @endphp
                                        <!-- Center Header -->
                                        <div class="center-header" onclick="toggleCenter({{ $routeIndex }}, {{ $centerIndex }})">
                                            <i class="fa fa-building"></i>
                                            <div style="flex: 1;">
                                                <div><strong>{{ $center['center_name'] }}</strong></div>
                                                <div style="font-size: 12px; color: #666; margin-top: 3px;">
                                                    <i class="fa fa-store"></i> {{ $deliveredShops }}/{{ $totalShops }} shops delivered
                                                </div>
                                            </div>
                                            <span class="expand-icon" id="center-icon-{{ $routeIndex }}-{{ $centerIndex }}">
                                                <i class="fa fa-chevron-down"></i>
                                            </span>
                                        </div>
                                        
                                        <!-- Customers in Center -->
                                        <div class="center-content" id="center-{{ $routeIndex }}-{{ $centerIndex }}" style="display: none;">
                                            @foreach($center['customers'] as $customer)
                                                <div class="customer-card {{ $customer['status'] }}">
                                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                                        <div class="customer-name">{{ $customer['name'] }}</div>
                                                        <div style="background: #667eea; color: white; padding: 4px 10px; border-radius: 12px; font-weight: 600; font-size: 14px;">
                                                            KES {{ number_format($customer['order_amount'], 2) }}
                                                        </div>
                                                    </div>
                                                    <div class="customer-details">
                                                        <i class="fa fa-phone"></i> {{ $customer['phone'] }}<br>
                                                        <i class="fa fa-map-marker-alt"></i> {{ $customer['location'] }}
                                                    </div>
                                                    <div style="margin-top: 10px;">
                                                        <span class="status-badge status-{{ $customer['status'] }}">{{ ucfirst($customer['status']) }}</span>
                                                        
                                                        @if($customer['status'] === 'pending' || $customer['status'] === 'in_progress')
                                                            <button class="btn btn-warning btn-sm" onclick="promptDelivery({{ $customer['id'] }})">
                                                                <i class="fa fa-bell"></i> Prompt Delivery
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                        
                        @if($schedule['delivered_count'] === $schedule['customers_count'])
                            <button class="btn btn-success" onclick="completeSchedule({{ $schedule['id'] }})">
                                <i class="fa fa-flag-checkered"></i> Complete Delivery Schedule
                            </button>
                        @endif
                    </div>
                @endif
                
            @else
                <!-- No Active Schedule -->
                <div class="no-schedule">
                    <i class="fa fa-calendar-times"></i>
                    <h3>No Active Schedule</h3>
                    <p>You don't have any active delivery schedules at the moment.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/admin/bower_components/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/bower_components/bootstrap/dist/js/bootstrap.min.js') }}"></script>
    
    <script>
        // CSRF Token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Toggle Route Expand/Collapse
        function toggleRoute(routeIndex) {
            const routeContent = $('#route-' + routeIndex);
            const routeIcon = $('#route-icon-' + routeIndex);
            
            if (routeContent.is(':visible')) {
                routeContent.slideUp(300);
                routeIcon.removeClass('expanded');
            } else {
                routeContent.slideDown(300);
                routeIcon.addClass('expanded');
            }
        }
        
        // Toggle Center Expand/Collapse
        function toggleCenter(routeIndex, centerIndex) {
            const centerContent = $('#center-' + routeIndex + '-' + centerIndex);
            const centerIcon = $('#center-icon-' + routeIndex + '-' + centerIndex);
            
            if (centerContent.is(':visible')) {
                centerContent.slideUp(300);
                centerIcon.removeClass('expanded');
            } else {
                centerContent.slideDown(300);
                centerIcon.addClass('expanded');
            }
        }
        
        // Toggle item checkbox
        function toggleItemCheck(checkbox) {
            const itemRow = $(checkbox).closest('.item-row');
            if (checkbox.checked) {
                itemRow.addClass('checked');
            } else {
                itemRow.removeClass('checked');
            }
            updateItemCount();
        }

        // Update checked item count and enable/disable button
        function updateItemCount() {
            const totalItems = $('.item-checkbox').length;
            const checkedItems = $('.item-checkbox:checked').length;
            
            $('#checkedCount').text(checkedItems);
            
            // Enable button only if all items are checked
            if (checkedItems === totalItems && totalItems > 0) {
                $('#acceptItemsBtn').prop('disabled', false);
            } else {
                $('#acceptItemsBtn').prop('disabled', true);
            }
        }

        // Accept Items - collect all item IDs and mark as received
        function acceptItems(scheduleId) {
            // Collect all item IDs from the checkboxes
            const itemIds = [];
            $('.item-row').each(function() {
                const itemId = $(this).data('item-id');
                if (itemId) {
                    itemIds.push(itemId);
                }
            });

            $.ajax({
                url: '/admin/delivery-driver/receive-items',
                method: 'POST',
                data: {
                    schedule_id: scheduleId,
                    item_ids: itemIds
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Items accepted successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showError(response.message || 'Error accepting items');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error accepting items';
                    showError(message);
                }
            });
        }

        // Start Delivery (uses existing API)
        function startDelivery(scheduleId) {
            $.ajax({
                url: '/api/delivery-driver/start-delivery',
                method: 'POST',
                data: {
                    schedule_id: scheduleId
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Delivery started successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showError(response.message || 'Error starting delivery');
                    }
                },
                error: function() {
                    showError('Error starting delivery');
                }
            });
        }

        // Global variable to store order total
        let currentOrderTotal = 0;
        
        // Show customer items before prompting delivery
        function promptDelivery(customerId) {
            // First, fetch customer items
            $.ajax({
                url: '/admin/delivery-driver/get-customer-items',
                method: 'GET',
                data: {
                    customer_id: customerId
                },
                success: function(response) {
                    if (response.success) {
                        // Store the total amount
                        currentOrderTotal = parseFloat(response.data.total_amount || 0);
                        showCustomerItemsModal(response.data, customerId);
                    } else {
                        showError(response.message || 'Error loading customer items');
                    }
                },
                error: function() {
                    showError('Error fetching customer items');
                }
            });
        }
        
        // Show customer items in a modal
        function showCustomerItemsModal(data, customerId) {
            // Create modal HTML
            let modalHtml = `
                <div class="modal fade" id="customerItemsModal" tabindex="-1" role="dialog" aria-labelledby="customerItemsModalLabel">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="customerItemsModalLabel">Items to Deliver - ${data.customer_name}</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Item</th>
                                                <th>Quantity Ordered</th>
                                                <th>Deliver</th>
                                            </tr>
                                        </thead>
                                        <tbody>`;
            
            // Add items to table
            if (data.items && data.items.length > 0) {
                data.items.forEach((item, index) => {
                    // Calculate returned quantity if any
                    const returnedQty = parseFloat(item.returned_quantity) || 0;
                    const maxDeliverQty = parseFloat(item.quantity) - returnedQty;
                    
                    modalHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${item.item_name}${returnedQty > 0 ? ' <span style="color: red; font-size: 0.85em;">(Returned: ' + returnedQty.toFixed(2) + ')</span>' : ''}</td>
                            <td>${parseFloat(item.quantity).toFixed(2)}</td>
                            <td>
                                <input type="number" class="form-control delivery-quantity" 
                                    data-item-id="${item.id}" 
                                    value="${maxDeliverQty.toFixed(2)}" 
                                    min="0" max="${maxDeliverQty}" 
                                    step="0.01"
                                    style="width: 80px;">
                            </td>
                        </tr>`;
                });
            } else {
                modalHtml += `
                    <tr>
                        <td colspan="4" class="text-center">No items found for this customer</td>
                    </tr>`;
            }
            
            modalHtml += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" onclick="showReturnModal(${customerId})">
                                    <i class="fa fa-undo"></i> Return
                                </button>
                                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-warning" onclick="confirmPromptDelivery(${customerId})">
                                    <i class="fa fa-bell"></i> Prompt Delivery
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            
            // Add modal to body and show it
            $('body').append(modalHtml);
            $('#customerItemsModal').modal('show');
            
            // Remove modal from DOM when hidden
            $('#customerItemsModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }
        
        // Confirm and proceed with prompting delivery
        function confirmPromptDelivery(customerId) {
            // Collect delivery quantities
            const deliveryItems = [];
            $('.delivery-quantity').each(function() {
                const itemId = $(this).data('item-id');
                const deliveryQuantity = parseFloat($(this).val()) || 0;
                const maxQuantity = parseFloat($(this).attr('max')) || 0;
                
                // Calculate returned quantity
                const returnedQuantity = maxQuantity - deliveryQuantity;
                
                // Only include items with changes
                if (deliveryQuantity < maxQuantity) {
                    deliveryItems.push({
                        item_id: itemId,
                        delivery_quantity: deliveryQuantity,
                        returned_quantity: returnedQuantity,
                        is_delivered: deliveryQuantity > 0,
                        is_returned: returnedQuantity > 0
                    });
                }
            });
            
            // Close the modal
            $('#customerItemsModal').modal('hide');
            
            // Call the API to prompt delivery with quantities
            $.ajax({
                url: '/api/delivery-driver/prompt-delivery',
                method: 'POST',
                data: {
                    customer_id: customerId,
                    delivery_items: deliveryItems
                },
                success: function(response) {
                    if (response.success) {
                        // Show payment options modal immediately with total
                        showPaymentModal(customerId, response.data, currentOrderTotal);
                    } else {
                        showError(response.message || 'Error prompting delivery');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error prompting delivery';
                    showError(message);
                }
            });
        }

        // Verify Code (uses existing API)
        function verifyCode(customerId) {
            const code = $('#code-' + customerId).val();
            if (!code || code.length !== 6) {
                showError('Please enter a valid 6-digit code');
                return;
            }

            $.ajax({
                url: '/api/delivery-driver/verify-code',
                method: 'POST',
                data: {
                    customer_id: customerId,
                    delivery_code: code
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Delivery verified successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showError(response.message || 'Invalid delivery code');
                    }
                },
                error: function() {
                    showError('Error verifying code');
                }
            });
        }

        // Complete Schedule (uses existing API)
        function completeSchedule(scheduleId) {
            if (!confirm('Are you sure you want to complete this delivery schedule?')) {
                return;
            }

            $.ajax({
                url: '/api/delivery-driver/complete-schedule',
                method: 'POST',
                data: {
                    schedule_id: scheduleId
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Delivery schedule completed successfully!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showError(response.message || 'Error completing schedule');
                    }
                },
                error: function() {
                    showError('Error completing schedule');
                }
            });
        }

        // Show success message with toast
        function showSuccess(message) {
            showToast(message, 'success');
        }

        // Show error message with toast
        function showError(message) {
            showToast(message, 'error');
        }
        
        // Toast notification function
        function showToast(message, type) {
            const bgColor = type === 'success' ? '#28a745' : '#dc3545';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            const toast = $(`
                <div class="custom-toast" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${bgColor};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 5px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
                    z-index: 99999;
                    min-width: 250px;
                    animation: slideIn 0.3s ease-out;
                ">
                    <i class="fa ${icon}" style="margin-right: 10px;"></i>
                    ${message}
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // Show payment modal
        function showPaymentModal(customerId, deliveryData, totalAmount) {
            // Fetch payment methods
            $.ajax({
                url: '/admin/delivery-driver/get-payment-methods',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data.payment_methods) {
                        displayPaymentModal(customerId, deliveryData, response.data.payment_methods, totalAmount);
                    } else {
                        showError('No payment methods available');
                        // Complete delivery without payment
                        completeDeliveryWithoutPayment(customerId);
                    }
                },
                error: function() {
                    showError('Error loading payment methods');
                    // Complete delivery without payment
                    completeDeliveryWithoutPayment(customerId);
                }
            });
        }
        
        // Display payment modal with payment methods
        function displayPaymentModal(customerId, deliveryData, paymentMethods, totalAmount) {
            let modalHtml = `
                <div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title" id="paymentModalLabel">Payment Options</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Delivery Code:</strong> ${deliveryData.delivery_code}</p>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                                    <h4 style="margin: 0 0 5px 0; color: #333;">Total Amount to Pay</h4>
                                    <h2 style="margin: 0; color: #667eea; font-weight: bold;">KES ${parseFloat(totalAmount || 0).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</h2>
                                </div>
                                <p>Please select payment method(s):</p>
                                <div class="payment-methods">`;
            
            paymentMethods.forEach(method => {
                modalHtml += `
                    <div class="payment-method-item" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" class="payment-method-checkbox" 
                                data-method-id="${method.id}" 
                                data-method-name="${method.title}" 
                                style="margin-right: 10px;">
                            <span>${method.title}</span>
                        </label>
                        <input type="number" class="form-control payment-amount" 
                            data-method-id="${method.id}" 
                            placeholder="Amount" 
                            min="0" 
                            step="0.01" 
                            style="margin-top: 5px; display: none;">
                    </div>`;
            });
            
            modalHtml += `
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-warning" onclick="skipPaymentAndComplete(${customerId})">
                                    <i class="fa fa-forward"></i> Skip Payment & Complete
                                </button>
                                <button type="button" class="btn btn-success" onclick="submitPayment(${customerId})">
                                    <i class="fa fa-check"></i> Confirm Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            
            // Add modal to body and show it
            $('body').append(modalHtml);
            // Show modal with backdrop:'static' to prevent closing by clicking outside
            $('#paymentModal').modal({
                backdrop: 'static',
                keyboard: false,
                show: true
            });
            
            // Show/hide amount input when checkbox is toggled
            $('.payment-method-checkbox').on('change', function() {
                const methodId = $(this).data('method-id');
                const amountInput = $(`.payment-amount[data-method-id="${methodId}"]`);
                if ($(this).is(':checked')) {
                    amountInput.show();
                } else {
                    amountInput.hide().val('');
                }
            });
            
            // Remove modal from DOM when hidden (but don't auto-reload)
            $('#paymentModal').on('hidden.bs.modal', function() {
                $(this).remove();
            });
        }
        
        // Skip payment and complete delivery
        function skipPaymentAndComplete(customerId) {
            console.log('Skip Payment clicked for customer:', customerId);
            $('#paymentModal').modal('hide');
            completeDeliveryWithoutPayment(customerId, true); // Pass true to indicate payment was skipped
        }
        
        // Complete delivery without code verification
        function completeDeliveryWithoutPayment(customerId, paymentSkipped = false) {
            console.log('Completing delivery - Customer:', customerId, 'Payment Skipped:', paymentSkipped);
            $.ajax({
                url: '/admin/delivery-driver/complete-delivery-direct',
                method: 'POST',
                data: {
                    customer_id: customerId,
                    payment_skipped: paymentSkipped
                },
                success: function(response) {
                    if (response.success) {
                        $('#paymentModal').modal('hide');
                        showSuccess('✓ Delivery completed successfully!');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showError(response.message || 'Error completing delivery');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error completing delivery';
                    showError(message);
                }
            });
        }
        
        // Submit payment
        function submitPayment(customerId) {
            const payments = [];
            $('.payment-method-checkbox:checked').each(function() {
                const methodId = $(this).data('method-id');
                const amount = parseFloat($(`.payment-amount[data-method-id="${methodId}"]`).val()) || 0;
                
                if (amount > 0) {
                    payments.push({
                        method_id: methodId,
                        amount: amount
                    });
                }
            });
            
            if (payments.length === 0) {
                showError('Please select at least one payment method and enter an amount');
                return;
            }
            
            // Submit payment to backend
            $.ajax({
                url: '/admin/delivery-driver/record-payment',
                method: 'POST',
                data: {
                    customer_id: customerId,
                    payments: payments
                },
                success: function(response) {
                    if (response.success) {
                        // Complete the delivery after payment
                        completeDeliveryWithoutPayment(customerId);
                    } else {
                        showError(response.message || 'Error recording payment');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error recording payment';
                    showError(message);
                }
            });
        }

        // Global variables for return process
        let currentReturnCustomerId = null;
        let currentReturnItems = [];
        let returnReasons = [];
        
        // Show return modal
        function showReturnModal(customerId) {
            currentReturnCustomerId = customerId;
            
            // Close the items modal first
            $('#customerItemsModal').modal('hide');
            
            // Fetch return reasons
            $.ajax({
                url: '/admin/delivery-driver/get-return-reasons',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        returnReasons = response.reasons;
                        displayReturnModal(customerId);
                    } else {
                        showError('Error loading return reasons');
                    }
                },
                error: function() {
                    showError('Error loading return reasons');
                }
            });
        }
        
        // Display return modal with items
        function displayReturnModal(customerId) {
            // Get customer items again
            $.ajax({
                url: '/admin/delivery-driver/get-customer-items?customer_id=' + customerId,
                method: 'GET',
                success: function(response) {
                    const data = response.data || response;
                    currentReturnItems = data.items;
                    
                    let modalHtml = `
                        <div class="modal fade" id="returnModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h4 class="modal-title">
                                            <i class="fa fa-undo"></i> Return Items - ${data.customer_name}
                                        </h4>
                                        <button type="button" class="close text-white" data-dismiss="modal">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> Select items to return and specify the reason
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th width="50">
                                                            <input type="checkbox" id="selectAllReturns" onchange="toggleAllReturns(this)">
                                                        </th>
                                                        <th>Item</th>
                                                        <th width="100">Qty Ordered</th>
                                                        <th width="120">Return Qty</th>
                                                        <th width="200">Return Reason</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;
                    
                    if (data.items && data.items.length > 0) {
                        data.items.forEach((item, index) => {
                            modalHtml += `
                                <tr class="return-item-row">
                                    <td class="text-center">
                                        <input type="checkbox" class="return-item-checkbox" 
                                            data-item-id="${item.id}" 
                                            onchange="toggleReturnItem(this)">
                                    </td>
                                    <td>${item.item_name}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm return-quantity" 
                                            data-item-id="${item.id}" 
                                            value="0" 
                                            min="0" 
                                            max="${item.quantity}" 
                                            disabled>
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm return-reason" 
                                            data-item-id="${item.id}" 
                                            disabled>
                                            <option value="">Select reason...</option>`;
                            
                            returnReasons.forEach(reason => {
                                modalHtml += `<option value="${reason.id}">${reason.reason}</option>`;
                            });
                            
                            modalHtml += `
                                        </select>
                                    </td>
                                </tr>`;
                        });
                    } else {
                        modalHtml += `
                            <tr>
                                <td colspan="5" class="text-center">No items available for return</td>
                            </tr>`;
                    }
                    
                    modalHtml += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                        <button type="button" class="btn btn-danger" onclick="submitReturns()">
                                            <i class="fa fa-check"></i> Confirm Returns
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    
                    // Add modal to body and show it
                    $('body').append(modalHtml);
                    $('#returnModal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    
                    // Remove modal from DOM when hidden
                    $('#returnModal').on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                },
                error: function() {
                    showError('Error loading customer items');
                }
            });
        }
        
        // Toggle all return items
        function toggleAllReturns(checkbox) {
            $('.return-item-checkbox').prop('checked', checkbox.checked).each(function() {
                toggleReturnItem(this);
            });
        }
        
        // Toggle individual return item
        function toggleReturnItem(checkbox) {
            const itemId = $(checkbox).data('item-id');
            const isChecked = $(checkbox).is(':checked');
            const $row = $(checkbox).closest('tr');
            
            // Enable/disable quantity and reason inputs
            $row.find('.return-quantity').prop('disabled', !isChecked);
            $row.find('.return-reason').prop('disabled', !isChecked);
            
            if (isChecked) {
                // Set default return quantity to max
                const maxQty = $row.find('.return-quantity').attr('max');
                $row.find('.return-quantity').val(maxQty);
                $row.addClass('table-warning');
            } else {
                // Reset values
                $row.find('.return-quantity').val(0);
                $row.find('.return-reason').val('');
                $row.removeClass('table-warning');
            }
        }
        
        // Submit returns
        function submitReturns() {
            const returnItems = [];
            
            $('.return-item-checkbox:checked').each(function() {
                const itemId = $(this).data('item-id');
                const quantity = parseFloat($(`.return-quantity[data-item-id="${itemId}"]`).val()) || 0;
                const reasonId = $(`.return-reason[data-item-id="${itemId}"]`).val();
                
                if (quantity > 0 && reasonId) {
                    returnItems.push({
                        item_id: itemId,
                        quantity: quantity,
                        reason_id: reasonId
                    });
                }
            });
            
            if (returnItems.length === 0) {
                showError('Please select at least one item with quantity and reason');
                return;
            }
            
            // Validate all selected items have reasons
            let hasError = false;
            $('.return-item-checkbox:checked').each(function() {
                const itemId = $(this).data('item-id');
                const quantity = parseFloat($(`.return-quantity[data-item-id="${itemId}"]`).val()) || 0;
                const reasonId = $(`.return-reason[data-item-id="${itemId}"]`).val();
                
                if (quantity > 0 && !reasonId) {
                    showError('Please select a return reason for all items');
                    hasError = true;
                    return false;
                }
            });
            
            if (hasError) return;
            
            // Submit returns to backend
            $.ajax({
                url: '/admin/delivery-driver/process-returns',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    customer_id: currentReturnCustomerId,
                    items: returnItems
                },
                success: function(response) {
                    if (response.success) {
                        $('#returnModal').modal('hide');
                        showSuccess('✓ Returns processed successfully!');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showError(response.message || 'Error processing returns');
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Error processing returns';
                    showError(message);
                }
            });
        }

        // Auto-refresh only when no modal is open
        setInterval(() => {
            // Don't reload if any modal is open (including Bootstrap modals and custom modals)
            const anyModalOpen = $('.modal.show').length > 0 || $('.modal.in').length > 0;
            const anyModalBackdrop = $('.modal-backdrop').length > 0;
            
            // Additional check for specific modals
            const paymentModalOpen = $('#paymentModal').is(':visible');
            const itemsModalOpen = $('#customerItemsModal').is(':visible');
            const returnModalOpen = $('#returnModal').is(':visible');
            
            // Check if user is actively interacting (any input focused)
            const inputFocused = $('input:focus, textarea:focus, select:focus').length > 0;
            
            const shouldPreventRefresh = anyModalOpen || anyModalBackdrop || paymentModalOpen || itemsModalOpen || returnModalOpen || inputFocused;
            
            if (!shouldPreventRefresh) {
                console.log('Auto-refreshing page...');
                location.reload();
            } else {
                console.log('Auto-refresh skipped - modal or input active');
            }
        }, 30000); // Refresh every 30 seconds
    </script>
</body>
</html>