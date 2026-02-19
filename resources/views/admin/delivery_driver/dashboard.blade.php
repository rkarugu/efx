@extends('admin.layouts.app')

@section('title', $title)

@section('uniquepagestyle')
<style>
    .delivery-card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .delivery-card:hover {
        transform: translateY(-2px);
    }
    .status-badge {
        font-size: 0.8rem;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
    }
    .status-consolidating { background-color: #ffc107; color: #000; }
    .status-consolidated { background-color: #17a2b8; color: #fff; }
    .status-loaded { background-color: #28a745; color: #fff; }
    .status-in_progress { background-color: #007bff; color: #fff; }
    .status-finished { background-color: #6c757d; color: #fff; }
    
    .customer-item {
        border-left: 4px solid #dee2e6;
        margin-bottom: 0.5rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 0 5px 5px 0;
    }
    .customer-delivered {
        border-left-color: #28a745;
        background: #d4edda;
    }
    .customer-pending {
        border-left-color: #ffc107;
        background: #fff3cd;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 1.5rem;
        text-align: center;
    }
    .stat-card.success {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .stat-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .stat-card.info {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-truck"></i> {{ $title }}
            <small>Welcome back, {{ Auth::user()->name }}!</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Delivery Dashboard</li>
        </ol>
    </section>

    <section class="content">
        <!-- Statistics Row -->
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="stat-card">
                    <h3>{{ $todayStats['completed_deliveries'] }}</h3>
                    <p>Completed Today</p>
                    <i class="fa fa-check-circle fa-2x" style="opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="stat-card success">
                    <h3>{{ $todayStats['active_schedules'] }}</h3>
                    <p>Active Schedules</p>
                    <i class="fa fa-truck fa-2x" style="opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="stat-card warning">
                    <h3>{{ $todayStats['delivered_customers'] }}/{{ $todayStats['total_customers'] }}</h3>
                    <p>Customers Delivered</p>
                    <i class="fa fa-users fa-2x" style="opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="stat-card info">
                    <h3>{{ $todayStats['pending_customers'] }}</h3>
                    <p>Pending Deliveries</p>
                    <i class="fa fa-clock-o fa-2x" style="opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Active Delivery Schedule -->
            <div class="col-md-8">
                <div class="box box-primary delivery-card">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-truck"></i> Active Delivery Schedule
                        </h3>
                        @if($activeSchedule)
                        <div class="box-tools pull-right">
                            <span class="status-badge status-{{ $activeSchedule->status }}">
                                {{ ucfirst(str_replace('_', ' ', $activeSchedule->status)) }}
                            </span>
                        </div>
                        @endif
                    </div>
                    <div class="box-body">
                        @if($activeSchedule)
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>{{ $activeSchedule->delivery_number }}</h4>
                                    <p><strong>Route:</strong> {{ $activeSchedule->route->route_name ?? 'N/A' }}</p>
                                    <p><strong>Vehicle:</strong> {{ $activeSchedule->vehicle->license_plate_number ?? 'Not assigned' }}</p>
                                    <p><strong>Expected Date:</strong> {{ $activeSchedule->expected_delivery_date ? \Carbon\Carbon::parse($activeSchedule->expected_delivery_date)->format('M d, Y') : 'N/A' }}</p>
                                    <p><strong>Duration:</strong> {{ $activeSchedule->duration }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h5>Customers ({{ $activeSchedule->customers->count() }})</h5>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        @foreach($activeSchedule->customers as $customer)
                                        <div class="customer-item {{ $customer->delivered_at ? 'customer-delivered' : 'customer-pending' }}">
                                            <strong>{{ $customer->routeCustomer->bussiness_name ?? 'Unknown Customer' }}</strong>
                                            @if($customer->delivered_at)
                                                <span class="pull-right text-success"><i class="fa fa-check"></i> Delivered</span>
                                            @else
                                                <span class="pull-right text-warning"><i class="fa fa-clock-o"></i> Pending</span>
                                            @endif
                                            <br>
                                            <small>{{ $customer->routeCustomer->phone ?? 'No phone' }}</small>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-12">
                                    <div class="btn-group pull-right">
                                        <a href="{{ route('delivery-driver.schedule.show', $activeSchedule->id) }}" 
                                           class="btn btn-info">
                                            <i class="fa fa-eye"></i> View Details
                                        </a>
                                        
                                        @if($activeSchedule->status == 'loaded')
                                        <button class="btn btn-success" onclick="startDelivery({{ $activeSchedule->id }})">
                                            <i class="fa fa-play"></i> Start Delivery
                                        </button>
                                        @endif
                                        
                                        @if($activeSchedule->status == 'in_progress')
                                        <button class="btn btn-warning" onclick="completeSchedule({{ $activeSchedule->id }})">
                                            <i class="fa fa-check"></i> Complete Schedule
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center" style="padding: 40px;">
                                <i class="fa fa-truck fa-3x text-muted"></i>
                                <h4 class="text-muted">No Active Delivery Schedule</h4>
                                <p class="text-muted">You don't have any active delivery schedules at the moment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Recent History -->
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="box box-success delivery-card">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-bolt"></i> Quick Actions
                        </h3>
                    </div>
                    <div class="box-body">
                        <a href="{{ route('delivery-driver.history') }}" class="btn btn-block btn-primary">
                            <i class="fa fa-history"></i> View Delivery History
                        </a>
                        @if($activeSchedule)
                        <a href="{{ route('delivery-driver.schedule.show', $activeSchedule->id) }}" class="btn btn-block btn-info">
                            <i class="fa fa-list"></i> Manage Current Schedule
                        </a>
                        @endif
                        <button class="btn btn-block btn-warning" onclick="refreshDashboard()">
                            <i class="fa fa-refresh"></i> Refresh Dashboard
                        </button>
                    </div>
                </div>

                <!-- Recent Deliveries -->
                <div class="box box-info delivery-card">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-clock-o"></i> Recent Deliveries
                        </h3>
                    </div>
                    <div class="box-body">
                        @if($recentDeliveries->count() > 0)
                            @foreach($recentDeliveries as $delivery)
                            <div class="customer-item customer-delivered">
                                <strong>{{ $delivery->delivery_number }}</strong>
                                <span class="pull-right">
                                    <small>{{ $delivery->updated_at->diffForHumans() }}</small>
                                </span>
                                <br>
                                <small>{{ $delivery->route->route_name ?? 'Unknown Route' }}</small>
                            </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center">No recent deliveries</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('uniquepagescript')
<script>
function startDelivery(scheduleId) {
    if (confirm('Are you sure you want to start this delivery?')) {
        $.ajax({
            url: `/admin/delivery-driver/schedule/${scheduleId}/start`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error starting delivery');
            }
        });
    }
}

function completeSchedule(scheduleId) {
    if (confirm('Are you sure you want to complete this delivery schedule? Make sure all customers have been delivered to.')) {
        $.ajax({
            url: `/admin/delivery-driver/schedule/${scheduleId}/complete`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error completing delivery schedule');
            }
        });
    }
}

function refreshDashboard() {
    location.reload();
}

// Auto-refresh every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>
@endsection
