@extends('admin.layouts.app')

@section('title', $title)

@section('uniquepagestyle')
<style>
    .delivery-card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        margin-bottom: 1rem;
    }
    .delivery-card:hover {
        transform: translateY(-2px);
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
    }
    .status-finished { background-color: #28a745; color: #fff; }
    .status-in_progress { background-color: #007bff; color: #fff; }
    .status-loaded { background-color: #17a2b8; color: #fff; }
    .status-consolidated { background-color: #ffc107; color: #000; }
    
    .history-item {
        border-left: 4px solid #28a745;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0 8px 8px 0;
        margin-bottom: 1rem;
    }
    
    .stats-row {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-history"></i> {{ $title }}
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('delivery-driver.dashboard') }}">Delivery Dashboard</a></li>
            <li class="active">History</li>
        </ol>
    </section>

    <section class="content">
        <!-- Statistics Summary -->
        <div class="stats-row">
            <div class="row">
                <div class="col-md-3 text-center">
                    <h3>{{ $deliveries->total() }}</h3>
                    <p>Total Deliveries</p>
                </div>
                <div class="col-md-3 text-center">
                    <h3>{{ $deliveries->where('status', 'finished')->count() }}</h3>
                    <p>Completed</p>
                </div>
                <div class="col-md-3 text-center">
                    <h3>{{ $deliveries->whereNotIn('status', ['finished'])->count() }}</h3>
                    <p>In Progress</p>
                </div>
                <div class="col-md-3 text-center">
                    <h3>{{ $deliveries->sum(function($d) { return $d->customers->count(); }) }}</h3>
                    <p>Total Customers</p>
                </div>
            </div>
        </div>

        <!-- Delivery History List -->
        <div class="box box-primary delivery-card">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-list"></i> Delivery History
                </h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('delivery-driver.dashboard') }}" class="btn btn-sm btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="box-body">
                @if($deliveries->count() > 0)
                    @foreach($deliveries as $delivery)
                    <div class="history-item">
                        <div class="row">
                            <div class="col-md-8">
                                <h4>
                                    {{ $delivery->delivery_number }}
                                    <span class="status-badge status-{{ $delivery->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $delivery->status)) }}
                                    </span>
                                </h4>
                                <p><i class="fa fa-route"></i> <strong>Route:</strong> {{ $delivery->route->route_name ?? 'N/A' }}</p>
                                <p><i class="fa fa-truck"></i> <strong>Vehicle:</strong> {{ $delivery->vehicle->license_plate_number ?? 'Not assigned' }}</p>
                                <p><i class="fa fa-users"></i> <strong>Customers:</strong> {{ $delivery->customers->count() }}</p>
                            </div>
                            <div class="col-md-4 text-right">
                                <p><strong>Created:</strong><br>{{ $delivery->created_at->format('M d, Y H:i') }}</p>
                                @if($delivery->actual_delivery_date)
                                <p><strong>Completed:</strong><br>{{ \Carbon\Carbon::parse($delivery->actual_delivery_date)->format('M d, Y H:i') }}</p>
                                @endif
                                @if($delivery->status == 'finished')
                                <p><strong>Duration:</strong><br>{{ $delivery->duration }}</p>
                                @endif
                                
                                @if($delivery->status != 'finished')
                                <a href="{{ route('delivery-driver.schedule.show', $delivery->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-eye"></i> View Details
                                </a>
                                @endif
                            </div>
                        </div>
                        
                        @if($delivery->status == 'finished')
                        <!-- Completed Delivery Summary -->
                        <div class="row" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #dee2e6;">
                            <div class="col-md-12">
                                <h5>Delivery Summary</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <small><strong>Total Customers:</strong> {{ $delivery->customers->count() }}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <small><strong>Delivered:</strong> {{ $delivery->customers->whereNotNull('delivered_at')->count() }}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <small><strong>Tonnage:</strong> {{ $delivery->tonnage }} tons</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                    
                    <!-- Pagination -->
                    <div class="text-center">
                        {{ $deliveries->links() }}
                    </div>
                @else
                    <div class="text-center" style="padding: 40px;">
                        <i class="fa fa-history fa-3x text-muted"></i>
                        <h4 class="text-muted">No Delivery History</h4>
                        <p class="text-muted">You haven't completed any deliveries yet.</p>
                        <a href="{{ route('delivery-driver.dashboard') }}" class="btn btn-primary">
                            <i class="fa fa-arrow-left"></i> Go to Dashboard
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

@section('uniquepagescript')
<script>
// Add any JavaScript functionality for history page if needed
</script>
@endsection
