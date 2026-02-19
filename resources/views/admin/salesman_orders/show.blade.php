@extends('layouts.admin.admin')

@section('content')
<section class="content">
    <div class="session-message-container">
        @include('message')
    </div>

    <!-- Order Header -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-file-text"></i> Order Details - {{ $order->requisition_no }}
            </h3>
            <div class="box-tools pull-right">
                <a href="{{ route('salesman-orders.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="{{ route('salesman-orders.print', $order->id) }}" class="btn btn-info btn-sm" target="_blank">
                    <i class="fa fa-print"></i> Print Order
                </a>
                <a href="{{ route('salesman-orders.download', $order->id) }}" class="btn btn-success btn-sm">
                    <i class="fa fa-download"></i> Download Invoice
                </a>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Order Number:</strong></td>
                            <td>{{ $order->requisition_no }}</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>{{ $order->created_at->format('d/m/Y H:i A') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Salesman:</strong></td>
                            <td>{{ $order->getrelatedEmployee->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <span class="label label-{{ $order->status == 'PENDING' ? 'warning' : ($order->status == 'COMPLETED' ? 'success' : 'info') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Customer:</strong></td>
                            <td>{{ $order->getRouteCustomer->bussiness_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Contact Person:</strong></td>
                            <td>{{ $order->getRouteCustomer->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>{{ $order->getRouteCustomer->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Location:</strong></td>
                            <td>{{ $order->getRouteCustomer->location ?? $order->getRouteCustomer->town ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-shopping-cart"></i> Order Items
            </h3>
        </div>

        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Discount</th>
                            <th>VAT Amount</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                        @php $counter = 1; @endphp
                        @foreach($order->getRelatedItem as $item)
                        <tr>
                            <td>{{ $counter++ }}</td>
                            <td>{{ $item->getInventoryItemDetail->title ?? 'N/A' }}</td>
                            <td>{{ $item->getInventoryItemDetail->pack_size->title ?? 'N/A' }}</td>
                            <td>{{ number_format($item->quantity, 2) }}</td>
                            <td>KSh {{ number_format($item->selling_price, 2) }}</td>
                            <td>KSh {{ number_format($item->discount ?? 0, 2) }}</td>
                            @php
                                $itemTotal = ($item->selling_price * $item->quantity) - ($item->discount ?? 0);
                                $itemVat = 0;
                                if ($item->getInventoryItemDetail && $item->getInventoryItemDetail->taxManager) {
                                    $taxRate = (float)$item->getInventoryItemDetail->taxManager->tax_value;
                                    // VAT is already included in the selling price, so extract it
                                    $itemVat = ($taxRate / (100 + $taxRate)) * $itemTotal;
                                }
                                // Use stored VAT amount if available, otherwise calculate it
                                $displayVat = $item->vat_amount ?? $itemVat;
                            @endphp
                            <td>KSh {{ number_format($displayVat, 2) }}</td>
                            <td>KSh {{ number_format($item->total_cost_with_vat, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @php
                            $subtotalWithoutVat = 0;
                            $totalVat = 0;
                            foreach($order->getRelatedItem as $item) {
                                // Calculate item total (with VAT included)
                                $itemTotal = ($item->selling_price * $item->quantity) - ($item->discount ?? 0);
                                
                                // Calculate VAT for each item using the same formula as SalesInvoiceController
                                $itemVat = 0;
                                if ($item->getInventoryItemDetail && $item->getInventoryItemDetail->taxManager) {
                                    $taxRate = (float)$item->getInventoryItemDetail->taxManager->tax_value;
                                    // VAT is already included in the selling price, so extract it
                                    $itemVat = ($taxRate / (100 + $taxRate)) * $itemTotal;
                                }
                                
                                // Use stored VAT amount if available, otherwise use calculated
                                $actualVat = $item->vat_amount ?? $itemVat;
                                $totalVat += $actualVat;
                                
                                // Subtotal without VAT = Total - VAT
                                $subtotalWithoutVat += ($itemTotal - $actualVat);
                            }
                        @endphp
                        <tr class="bg-light-blue">
                            <th colspan="7" class="text-right">Sub Total (Before VAT):</th>
                            <th>KSh {{ number_format($subtotalWithoutVat, 2) }}</th>
                        </tr>
                        <tr class="bg-light-blue">
                            <th colspan="7" class="text-right">VAT Amount:</th>
                            <th>KSh {{ number_format($totalVat, 2) }}</th>
                        </tr>
                        <tr class="bg-light-blue">
                            <th colspan="7" class="text-right">Sub Total (With VAT):</th>
                            <th>KSh {{ number_format($order->getOrderTotalWithoutDiscount(), 2) }}</th>
                        </tr>
                        <tr class="bg-light-blue">
                            <th colspan="7" class="text-right">Total Discount:</th>
                            <th>KSh {{ number_format($order->getTotalDiscount(), 2) }}</th>
                        </tr>
                        <tr class="bg-green">
                            <th colspan="7" class="text-right">Grand Total:</th>
                            <th>KSh {{ number_format($order->getOrderTotal(), 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Summary -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Order Summary</h3>
                </div>
                <div class="box-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Total Items:</strong></td>
                            <td>{{ $order->getRelatedItem->count() }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Quantity:</strong></td>
                            <td>{{ number_format($order->getRelatedItem->sum('quantity'), 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Order Value:</strong></td>
                            <td>KSh {{ number_format($order->getOrderTotal(), 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Payment Status:</strong></td>
                            <td>
                                @if($order->payments->count() > 0)
                                    <span class="label label-success">Paid</span>
                                @else
                                    <span class="label label-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Shift Information</h3>
                </div>
                <div class="box-body">
                    @if($order->shift)
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Shift Type:</strong></td>
                            <td>{{ ucfirst($order->shift_type ?? 'N/A') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Shift Start:</strong></td>
                            <td>{{ $order->shift->start_time ? \Carbon\Carbon::parse($order->shift->start_time)->format('H:i A') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Shift Status:</strong></td>
                            <td>
                                <span class="label label-{{ $order->shift->status == 'open' ? 'success' : 'default' }}">
                                    {{ ucfirst($order->shift->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Route:</strong></td>
                            <td>{{ $order->route->route_name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                    @else
                    <p class="text-muted">No shift information available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Information -->
    @if($order->payments->count() > 0)
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-money"></i> Payment Information
            </h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at->format('d/m/Y H:i A') }}</td>
                            <td>KSh {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                            <td>{{ $payment->reference ?? 'N/A' }}</td>
                            <td>
                                <span class="label label-success">Completed</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Actions -->
    <div class="box box-default">
        <div class="box-body text-center">
            <a href="{{ route('salesman-orders.index') }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
            
            @if($order->status == 'PENDING')
            <button class="btn btn-warning" onclick="updateOrderStatus('PROCESSING')">
                <i class="fa fa-cog"></i> Mark as Processing
            </button>
            @endif
            
            @if($order->status == 'PROCESSING')
            <button class="btn btn-success" onclick="updateOrderStatus('COMPLETED')">
                <i class="fa fa-check"></i> Mark as Completed
            </button>
            @endif
            
            <button class="btn btn-info" onclick="window.print()">
                <i class="fa fa-print"></i> Print Order
            </button>
        </div>
    </div>
</section>

<style>
@media print {
    .box-tools, .btn, .box-footer {
        display: none !important;
    }
    
    .content-wrapper, .main-footer {
        margin-left: 0 !important;
    }
    
    .main-header, .main-sidebar {
        display: none !important;
    }
}

.table-borderless td {
    border: none !important;
    padding: 5px 10px;
}
</style>

<script>
function updateOrderStatus(status) {
    if (confirm(`Are you sure you want to mark this order as ${status.toLowerCase()}?`)) {
        // This would typically make an AJAX call to update the order status
        // For now, we'll just show an alert
        alert('Order status update functionality would be implemented here');
    }
}
</script>
@endsection
