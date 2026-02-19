@extends('admin.layouts.app')

@section('title', $title)

@section('uniquepagestyle')
<style>
    .schedule-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
    
    .delivery-card {
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }
    
    .status-badge {
        font-size: 0.9rem;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: bold;
    }
    .status-consolidating { background-color: #ffc107; color: #000; }
    .status-consolidated { background-color: #17a2b8; color: #fff; }
    .status-loaded { background-color: #28a745; color: #fff; }
    .status-in_progress { background-color: #007bff; color: #fff; }
    .status-finished { background-color: #6c757d; color: #fff; }
    
    .customer-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }
    .customer-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .customer-delivered {
        border-left: 5px solid #28a745;
        background: #f8fff9;
    }
    .customer-pending {
        border-left: 5px solid #ffc107;
        background: #fffef8;
    }
    .customer-in-progress {
        border-left: 5px solid #007bff;
        background: #f8fbff;
    }
    
    .item-row {
        padding: 0.5rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .item-row:last-child {
        border-bottom: none;
    }
    
    .action-buttons {
        position: sticky;
        bottom: 20px;
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        margin-top: 2rem;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-truck"></i> {{ $title }}
        </h1>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard') }}"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="{{ route('delivery-driver.dashboard') }}">Delivery Dashboard</a></li>
            <li class="active">Schedule Details</li>
        </ol>
    </section>

    <section class="content">
        <!-- Schedule Header -->
        <div class="schedule-header">
            <div class="row">
                <div class="col-md-8">
                    <h2>{{ $schedule->delivery_number }}</h2>
                    <p><i class="fa fa-route"></i> <strong>Route:</strong> {{ $schedule->route->route_name ?? 'N/A' }}</p>
                    <p><i class="fa fa-truck"></i> <strong>Vehicle:</strong> {{ $schedule->vehicle->license_plate_number ?? 'Not assigned' }}</p>
                </div>
                <div class="col-md-4 text-right">
                    <span class="status-badge status-{{ $schedule->status }}">
                        {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                    </span>
                    <br><br>
                    <p><strong>Expected:</strong> {{ $schedule->expected_delivery_date ? \Carbon\Carbon::parse($schedule->expected_delivery_date)->format('M d, Y H:i') : 'N/A' }}</p>
                    <p><strong>Duration:</strong> {{ $schedule->duration }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Customers Section -->
            <div class="col-md-8">
                <div class="box box-primary delivery-card">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-users"></i> Customers ({{ $schedule->customers->count() }})
                        </h3>
                        <div class="box-tools pull-right">
                            <span class="badge bg-green">{{ $schedule->customers->whereNotNull('delivered_at')->count() }} Delivered</span>
                            <span class="badge bg-yellow">{{ $schedule->customers->whereNull('delivered_at')->count() }} Pending</span>
                        </div>
                    </div>
                    <div class="box-body">
                        @foreach($schedule->customers as $customer)
                        <div class="customer-card {{ $customer->delivered_at ? 'customer-delivered' : ($customer->delivery_code_status == 'sent' ? 'customer-in-progress' : 'customer-pending') }}">
                            <div class="box-header">
                                <h4 class="box-title">
                                    {{ $customer->routeCustomer->bussiness_name ?? 'Unknown Customer' }}
                                    @if($customer->delivered_at)
                                        <span class="label label-success">Delivered</span>
                                    @elseif($customer->delivery_code_status == 'sent')
                                        <span class="label label-info">In Progress</span>
                                    @else
                                        <span class="label label-warning">Pending</span>
                                    @endif
                                </h4>
                                <div class="box-tools pull-right">
                                    @if(!$customer->delivered_at && $schedule->status == 'in_progress')
                                    <div class="btn-group">
                                        @if($customer->delivery_code_status != 'sent')
                                        <button class="btn btn-sm btn-info" onclick="promptDelivery({{ $customer->customer_id }})">
                                            <i class="fa fa-bell"></i> Prompt Delivery
                                        </button>
                                        @endif
                                        
                                        @if($customer->delivery_code_status == 'sent')
                                        <button class="btn btn-sm btn-success" onclick="showVerifyModal({{ $customer->customer_id }}, '{{ $customer->routeCustomer->bussiness_name ?? 'Customer' }}')">
                                            <i class="fa fa-check"></i> Verify Code
                                        </button>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><i class="fa fa-phone"></i> {{ $customer->routeCustomer->phone ?? 'No phone' }}</p>
                                        <p><i class="fa fa-map-marker"></i> {{ $customer->routeCustomer->location ?? 'No location' }}</p>
                                        @if($customer->delivery_code)
                                        <p><i class="fa fa-key"></i> <strong>Delivery Code:</strong> 
                                            <span class="text-primary">{{ $customer->delivery_code }}</span>
                                        </p>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        @if($customer->delivered_at)
                                        <p><i class="fa fa-check-circle text-success"></i> <strong>Delivered:</strong> {{ \Carbon\Carbon::parse($customer->delivered_at)->format('M d, Y H:i') }}</p>
                                        @endif
                                        @if($customer->delivery_prompted_at)
                                        <p><i class="fa fa-bell text-info"></i> <strong>Prompted:</strong> {{ \Carbon\Carbon::parse($customer->delivery_prompted_at)->format('M d, Y H:i') }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Customer Orders -->
                                @php
                                    $orderIds = explode(',', $customer->order_id);
                                    $orders = \App\Model\WaInternalRequisition::whereIn('id', $orderIds)->with('getRelatedItem.getInventoryItemDetail')->get();
                                @endphp
                                
                                @if($orders->count() > 0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Orders ({{ $orders->count() }})</h5>
                                        @foreach($orders as $order)
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <strong>{{ $order->requisition_no }}</strong>
                                                <span class="label label-{{ $order->status == 'COMPLETED' ? 'success' : ($order->status == 'PROCESSING' ? 'info' : 'default') }}">
                                                    {{ $order->status }}
                                                </span>
                                            </div>
                                            <div class="panel-body">
                                                @foreach($order->getRelatedItem as $item)
                                                <div class="item-row">
                                                    <strong>{{ $item->getInventoryItemDetail->title ?? 'Unknown Item' }}</strong>
                                                    <span class="pull-right">Qty: {{ $item->quantity }}</span>
                                                    <br>
                                                    <small>{{ $item->getInventoryItemDetail->stock_id_code ?? 'N/A' }}</small>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Items & Actions Section -->
            <div class="col-md-4">
                <!-- Items Summary -->
                <div class="box box-info delivery-card">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-cubes"></i> Items Summary
                        </h3>
                    </div>
                    <div class="box-body">
                        @if($schedule->items->count() > 0)
                            <div style="max-height: 300px; overflow-y: auto;">
                                @foreach($schedule->items as $item)
                                <div class="item-row">
                                    <strong>{{ $item->inventoryItem->title ?? 'Unknown Item' }}</strong>
                                    <span class="pull-right">{{ $item->quantity }}</span>
                                    <br>
                                    <small>{{ $item->inventoryItem->stock_id_code ?? 'N/A' }}</small>
                                </div>
                                @endforeach
                            </div>
                            <hr>
                            <p><strong>Total Items:</strong> {{ $schedule->items->count() }}</p>
                            <p><strong>Total Tonnage:</strong> {{ $schedule->tonnage }} tons</p>
                        @else
                            <p class="text-muted">No items found</p>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="box box-success delivery-card">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-cogs"></i> Actions
                        </h3>
                    </div>
                    <div class="box-body">
                        @if($schedule->status == 'consolidated')
                        <button class="btn btn-block btn-primary" onclick="receiveAllItems()">
                            <i class="fa fa-check"></i> Mark All Items Received
                        </button>
                        @endif
                        
                        @if($schedule->status == 'loaded')
                        <button class="btn btn-block btn-success" onclick="startDelivery()">
                            <i class="fa fa-play"></i> Start Delivery
                        </button>
                        @endif
                        
                        @if($schedule->status == 'in_progress')
                        <button class="btn btn-block btn-warning" onclick="completeSchedule()">
                            <i class="fa fa-check-circle"></i> Complete Schedule
                        </button>
                        @endif
                        
                        <a href="{{ route('delivery-driver.dashboard') }}" class="btn btn-block btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Verify Delivery Code Modal -->
<div class="modal fade" id="verifyCodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Verify Delivery Code</h4>
            </div>
            <div class="modal-body">
                <p>Enter the delivery code provided by <strong id="customerName"></strong>:</p>
                <div class="form-group">
                    <input type="text" id="deliveryCodeInput" class="form-control" placeholder="Enter 6-digit code" maxlength="6">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="verifyDeliveryCode()">Verify Code</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('uniquepagescript')
<script>
let currentCustomerId = null;

function promptDelivery(customerId) {
    if (confirm('Send delivery prompt to customer?')) {
        $.ajax({
            url: '{{ route("delivery-driver.prompt-delivery") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.delivery_code) {
                        toastr.info('Delivery code: ' + response.delivery_code);
                    }
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Error prompting delivery');
            }
        });
    }
}

function showVerifyModal(customerId, customerName) {
    currentCustomerId = customerId;
    $('#customerName').text(customerName);
    $('#deliveryCodeInput').val('');
    $('#verifyCodeModal').modal('show');
}

function verifyDeliveryCode() {
    const code = $('#deliveryCodeInput').val();
    if (!code || code.length !== 6) {
        toastr.error('Please enter a valid 6-digit code');
        return;
    }

    $.ajax({
        url: '{{ route("delivery-driver.verify-code") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            customer_id: currentCustomerId,
            delivery_code: code
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#verifyCodeModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Error verifying delivery code');
        }
    });
}

function receiveAllItems() {
    if (confirm('Mark all items as received?')) {
        const itemIds = @json($schedule->items->pluck('inventory_item_id')->toArray());
        
        $.ajax({
            url: '{{ route("delivery-driver.receive-items") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                schedule_id: {{ $schedule->id }},
                item_ids: itemIds
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
                toastr.error('Error receiving items');
            }
        });
    }
}

function startDelivery() {
    if (confirm('Start this delivery schedule?')) {
        $.ajax({
            url: `/admin/delivery-driver/schedule/{{ $schedule->id }}/start`,
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

function completeSchedule() {
    if (confirm('Complete this delivery schedule? Make sure all customers have been delivered to.')) {
        $.ajax({
            url: `/admin/delivery-driver/schedule/{{ $schedule->id }}/complete`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        window.location.href = '{{ route("delivery-driver.dashboard") }}';
                    }, 2000);
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

// Auto-refresh every 2 minutes for active schedules
@if($schedule->status == 'in_progress')
setInterval(function() {
    location.reload();
}, 120000);
@endif
</script>
@endsection
