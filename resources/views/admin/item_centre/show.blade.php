@extends('layouts.admin.admin')

@php
    $authuser = Auth::user();
    $authuserlocation = $authuser->wa_location_and_store_id;
    $isAdmin = $authuser->role_id == 1;
    $hasPermission = isset($permission['maintain-items___view-all-stocks']);
@endphp

@section('content')
    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                @include('message')
                <div class="box-header-flex">
                    <h3 class="box-title">{{ $item->title }} - ({{ $item->stock_id_code }})</h3>
                    <div>
                        <a href="{{ route('maintain-items.index') }}" class="btn btn-success">Back</a>

                        @if (can('edit', 'maintain-items'))
                            <a href = "{!! route('maintain-items.edit', $item->slug) !!}" class = "btn btn-success">
                                <i class="fa fa-edit"></i>
                            </a>
                        @endif
                        @if (can('manage-item-stock', 'maintain-items'))
                            <a href ="#" data-toggle="modal" data-target="#manage-stock-model"
                                class = "btn btn-success">
                                <i class="fa fa-bolt"></i>
                            </a>
                        @endif
                        @if (can('manage-category-pricing', 'maintain-items'))
                            <a href="#" data-toggle="modal" data-target="#manage-category-model"
                                class = "btn btn-success">
                                <i class="fa fa-money"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-2">
                        <label for="">Pack Size</label>
                        <p>
                            @if ($item->pack_size)
                                {{ $item->pack_size->title }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-md-2">
                        <label for="">Price List Cost</label>
                        <p>{{ manageAmountFormat($item->price_list_cost) }}</p>
                    </div>
                    <div class="col-md-2">
                        <label for="">Standard Cost</label>
                        <p>{{ manageAmountFormat($item->standard_cost) }}</p>
                    </div>
                    <div class="col-md-2">
                        <label for="">Last GRN Cost</label>
                        <p>{{ manageAmountFormat($item->last_grn_cost) }}</p>
                    </div>
                    <div class="col-md-2">
                        <label for="">Weighted Average Cost</label>
                        <p>{{ manageAmountFormat($item->weighted_average_cost) }}</p>
                    </div>
                    <div class="col-md-2">
                        <label for="">Selling Price</label>
                        <p>{{ manageAmountFormat($item->selling_price) }}</p>
                    </div>
                    <div class="col-md-2">
                        <label for="">Margin</label>
                        @if ($item->margin_type == 1)
                            <p>{{ $item->percentage_margin }} %</p>
                        @else
                            <p>Kes {{ $item->percentage_margin }}</p>
                        @endif
                    </div>
                    <div class="col-md-2">
                        <label for="">Tax</label>
                        <p>
                            @if ($item->getTaxesOfItem)
                                {{ $item->getTaxesOfItem->title }}
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <label for="">Supplier(s)</label>
                        <p>
                            {{ implode(',', $item->suppliers->pluck('name')->toArray()) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-primary">
            <ul class="nav nav-tabs">
                @if (can('view-stock-movements', 'maintain-items'))
                    <li class="active"><a href="#stock_movements" data-toggle="tab">Stock Movements</a></li>
                @endif
                @if (can('view-stock-status', 'maintain-items'))
                    <li><a href="#stock_status" data-toggle="tab">Stock Status</a></li>
                @endif
                @if (can('maintain-purchasing-data', 'maintain-items'))
                    <li><a href="#purchase_data" data-toggle="tab">Purchase Data</a></li>
                @endif
                @if (can('assign-inventory-items', 'maintain-items'))
                    <li><a href="#inventory_items" data-toggle="tab">Small Packs</a></li>
                @endif
                @if (can('price-change-history', 'maintain-items'))
                    <li><a href="#price_change_history" data-toggle="tab">Price Change History</a></li>
                @endif
                @if (can('update-bin-location', 'maintain-items'))
                    <li><a href="#bin_location" data-toggle="tab">Bin Location</a></li>
                @endif
                @if (can('manage-discount', 'maintain-items'))
                    <li><a href="#discounts" data-toggle="tab">Discounts</a></li>
                @endif
                @if (can('manage-promotions', 'maintain-items'))
                    <li><a href="#promotions" data-toggle="tab">Promotions</a></li>
                @endif
                @if (can('route-pricing', 'maintain-items'))
                    <li><a href="#route_pricing" data-toggle="tab">Route Pricing</a></li>
                @endif
                @if (can('view-shop-pricing', 'maintain-items'))
                    <li><a href="#location_prices" data-toggle="tab">Branch Pricing</a></li>
                @endif
            </ul>

            <div class="tab-content">
                @if (can('view-stock-movements', 'maintain-items'))
                    <div class="tab-pane active" id="stock_movements">
                        @include('admin.item_centre.partials.stock_movements')
                    </div>
                @endif
                @if (can('view-shop-pricing', 'maintain-items'))
                    <div class="tab-pane" id="location_prices">
                        <x-item-centre.shop-prices item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('view-stock-status', 'maintain-items'))
                    <div class="tab-pane" id="stock_status">
                        @include('admin.item_centre.partials.stock_status')
                    </div>
                @endif
                @if (can('maintain-purchasing-data', 'maintain-items'))
                    <div class="tab-pane" id="purchase_data">
                        <x-item-centre.purchase-data item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('assign-inventory-items', 'maintain-items'))
                    <div class="tab-pane" id="inventory_items">
                        <x-item-centre.inventory-items item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('price-change-history ', 'maintain-items'))
                    <div class="tab-pane" id="price_change_history">
                        <x-item-centre.price-change-history item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('update-bin-location', 'maintain-items'))
                    <div class="tab-pane" id="bin_location">
                        <x-item-centre.bin-location item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('manage-discount', 'maintain-items'))
                    <div class="tab-pane" id="discounts">
                        <x-item-centre.discounts item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('manage-promotions', 'maintain-items'))
                    <div class="tab-pane" id="promotions">
                        <x-item-centre.promotions item-id="{{ $item->id }}" />
                    </div>
                @endif
                @if (can('route-pricing', 'maintain-items'))
                    <div class="tab-pane" id="route_pricing">
                        <x-item-centre.route-pricing-component item-id="{{ $item->id }}" />
                    </div>
                @endif

            </div>
        </div>
    </section>
    @include('admin.item_centre.modals.adjust_item_stock')
    @include('admin.item_centre.modals.adjust_category_price')
@endsection
@push('styles')
    <link href="{{ asset('assets/admin/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet" />
    <style>
        .reportRange {
            display: flex;
            align-content: center;
            justify-content: stretch;
            border: 1px solid #eee;
            cursor: pointer;
            height: 35px;
        }
    </style>
@endpush
@push('scripts')
    <div id="loader-on"
        style="position: fixed; top: 0; text-align: center; z-index: 999999;
                width: 100%;  height: 100%; background: #000000b8; display:none;"
        class="loder">
        <div class="loader" id="loader-1"></div>
    </div>
    <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
    <script>
        // Immediately patch Select2 after it loads
        (function() {
            if (typeof $.fn.select2 !== 'undefined') {
                var originalSelect2 = $.fn.select2;
                
                $.fn.select2 = function(options) {
                    if (options === 'destroy') {
                        return this.each(function() {
                            var $this = $(this);
                            try {
                                if ($this.hasClass('select2-hidden-accessible')) {
                                    originalSelect2.call($this, 'destroy');
                                }
                            } catch (e) {
                                // Silently handle destroy errors
                            }
                        });
                    }
                    
                    return this.each(function() {
                        var $this = $(this);
                        try {
                            return originalSelect2.call($this, options);
                        } catch (e) {
                            console.warn('Select2 operation failed:', e);
                            return $this;
                        }
                    });
                };
                
                // Copy over any static methods/properties
                for (var prop in originalSelect2) {
                    if (originalSelect2.hasOwnProperty(prop)) {
                        $.fn.select2[prop] = originalSelect2[prop];
                    }
                }
            }

            // Global error handler for uncaught JavaScript errors
            window.addEventListener('error', function(e) {
                if (e.message && e.message.includes('select2') && 
                    (e.message.includes('destroy') || e.message.includes('not using Select2'))) {
                    e.preventDefault();
                    console.warn('Select2 error suppressed:', e.message);
                    return false;
                }
            });

            // Handle unhandled promise rejections related to Select2
            window.addEventListener('unhandledrejection', function(e) {
                if (e.reason && e.reason.toString().includes('select2')) {
                    e.preventDefault();
                    console.warn('Select2 promise rejection suppressed:', e.reason);
                }
            });
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="{{ asset('js/sweetalert.js') }}"></script>
    {{-- <script src="{{ asset('js/form.js') }}"></script> --}}
    <script>
        // Global error handler for Select2 issues
        $(document).ready(function() {

            // Override console.error to catch Select2 errors
            var originalError = console.error;
            console.error = function() {
                var args = Array.prototype.slice.call(arguments);
                var message = args.join(' ');
                
                // Suppress specific Select2 errors that are not critical
                if (message.includes('select2') && 
                    (message.includes('destroy') || message.includes('not using Select2'))) {
                    console.warn('Select2 warning (suppressed):', message);
                    return;
                }
                
                // Call original error function for other errors
                originalError.apply(console, args);
            };

            // Add safe Select2 methods to jQuery prototype
            $.fn.safeSelect2 = function(options) {
                return this.each(function() {
                    var $this = $(this);
                    try {
                        if ($this.hasClass('select2-hidden-accessible')) {
                            $this.select2('destroy');
                        }
                        $this.select2(options || {});
                    } catch (e) {
                        console.warn('Safe Select2 failed for element:', this, e);
                    }
                });
            };

            $.fn.safeSelect2Destroy = function() {
                return this.each(function() {
                    var $this = $(this);
                    try {
                        if ($this.hasClass('select2-hidden-accessible')) {
                            $this.select2('destroy');
                        }
                    } catch (e) {
                        console.warn('Safe Select2 destroy failed for element:', this, e);
                    }
                });
            };
        });

        // Safe Select2 destroy function
        function safeSelect2Destroy(selector) {
            try {
                var $element = $(selector);
                if ($element.length && $element.hasClass('select2-hidden-accessible')) {
                    $element.select2('destroy');
                }
            } catch (e) {
                console.warn('Select2 destroy failed for selector: ' + selector, e);
            }
        }

        // Safe Select2 initialization
        function safeSelect2Init(selector, options) {
            try {
                // Validate selector is not empty
                if (!selector || selector.trim() === '') {
                    console.warn('Empty selector passed to safeSelect2Init');
                    return;
                }
                
                var $element = $(selector);
                if ($element.length) {
                    // Destroy existing Select2 if present
                    if ($element.hasClass('select2-hidden-accessible')) {
                        $element.select2('destroy');
                    }
                    // Initialize Select2
                    $element.select2(options || {});
                }
            } catch (e) {
                console.warn('Select2 initialization failed for selector: ' + selector, e);
            }
        }

        function refreshTable(table) {
            table.DataTable().ajax.reload();
        }

        function printStockCard(input) {
            var url = "{{ route('maintain-items.stock-movements', ['stockIdCode' => $item->stock_id_code]) }}?" + $(input)
                .parents(
                    'form').serialize() + '&type=print';
            print_this(url);
        }

        // Initialize Select2 for location dropdown in modal when modal is shown
        $('#manage-stock-model').on('shown.bs.modal', function () {
            // Small delay to ensure DOM is ready
            setTimeout(function() {
                safeSelect2Init('#location-input', {
                    placeholder: 'Please select',
                    allowClear: true
                });
            }, 100);
        });

        // Clean up Select2 when modal is hidden
        $('#manage-stock-model').on('hidden.bs.modal', function () {
            safeSelect2Destroy('#location-input');
            // Clear form values to prevent issues
            $('#current_qty_available').val('');
            $('#quantity-input').val('');
        });

        // Handle tab switching to reinitialize Select2 if needed
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            if (target === '#stock_movements') {
                // Reinitialize Select2 for stock movements tab
                setTimeout(function() {
                    if (typeof safeSelect2Init === 'function') {
                        safeSelect2Init("#storeLocation");
                        safeSelect2Init("#moveType");
                    }
                }, 100);
            }
        });
    </script>
@endpush
