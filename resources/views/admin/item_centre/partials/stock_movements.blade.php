<div style="padding:10px;">
    <div class="row">
        <form action="{{ route('maintain-items.stock-movements', $item->stock_id_code) }}" method="get">
            
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <input type="hidden" id="stockStartDate" name="from">
                            <input type="hidden" id="stockEndDate" name="to">
                            <label for="">Select Dates</label>
                            <div class="stockReportRange reportRange">
                                <i class="fa fa-calendar" style="padding:8px"></i>
                                <span class="flex-grow-1" style="padding:8px"></span>
                                <i class="fa fa-caret-down" style="padding:8px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label for="storeLocation">Location</label>
                        <select name="location" id="storeLocation" class="form-control" @if (!$isAdmin && !$hasPermission) disabled @endif>
                            <option value="">Select Option</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" 
                                    @selected($location->id == $authuserlocation)
                                    @if (!$isAdmin && !$hasPermission && $location->id != $authuserlocation) disabled @endif>
                                    {{ $location->location_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-4">
                        <label for="">Type</label>
                        <select name="move_type" id="moveType" class="form-control mlselec6t">
                            <option value="" selected>Select Option</option>
                            <option value="adjustment" {{ request()->move_type == 'adjustment' ? 'selected' : '' }}>
                                Adjustment
                            </option>
                            <option value="return" {{ request()->move_type == 'return' ? 'selected' : '' }}>
                                Return
                            </option>
                            <option value="delivery-note"
                                {{ request()->move_type == 'delivery-note' ? 'selected' : '' }}>
                                Delivery Note</option>
                            {{-- <option value="ingredients-booking"
                                {{ request()->move_type == 'ingredients-booking' ? 'selected' : '' }}>Ingredients
                                Booking
                            </option> --}}
                            {{-- <option value="internal-requisition-store-c"
                                {{ request()->move_type == 'internal-requisition-store-c' ? 'selected' : '' }}>Internal
                                Requisition Store C</option> --}}
                            <option value="purchase" {{ request()->move_type == 'purchase' ? 'selected' : '' }}
                                >Purchase
                            </option>
                            {{-- <option value="recieve-stock-store-c"
                                {{ request()->move_type == 'recieve-stock-store-c' ? 'selected' : '' }}>Recieve Stock
                                Store
                                C
                            </option> --}}
                            {{-- <option value="return-from-store"
                                {{ request()->move_type == 'return-from-store' ? 'selected' : '' }}>
                                Return From Store</option> --}}
                            <option value="sales-invoice"
                                {{ request()->move_type == 'sales-invoice' ? 'selected' : '' }}>
                                Sales Invoice</option>
                            <option value="stock-break" {{ request()->move_type == 'stock-break' ? 'selected' : '' }}>
                                Stock  Break
                            </option>
                            <option value="transfer" {{ request()->move_type == 'transfer' ? 'selected' : '' }}>
                                Transfer
                            </option>
                            <option value="cash-sales" {{ request()->move_type == 'cash-sales' ? 'selected' : '' }}>
                                Cash Sales
                            </option>
                            <option value="stock-charge" {{ request()->move_type == 'stock-charge' ? 'selected' : '' }}>
                                Stock Charge
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="text-right">
                    <label style="display:block">&nbsp;</label>
                    <button class="btn btn-primary" type="submit" name="type" value="Excel">
                        <i class="fa fa-file-excel"></i>
                        Excel
                    </button>
                    <button class="btn btn-primary" type="button" name="type" value="print"
                        onclick="printStockCard(this); return false;">
                        <i class="fa fa-print"></i>
                        Print
                    </button>
                    <button class="btn btn-primary" type="submit" name="type" value="pdf">
                        <i class="fa fa-list"></i>
                        Stock Card
                    </button>
                </div>
            </div>
        </form>
    </div>
    <table class="table table-bordered table-hover table-striped" id="stockMovementDataTable">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th>User Name</th>
                <th>Store Location</th>
                <th>Quantity</th>
                <th>Qty In</th>
                <th>Qty Out</th>
                <th>New QOH</th>
                <th>Selling Price</th>
                <th>Reference</th>
                <th>Document No</th>
                <th>Type</th>
            </tr>
        </thead>
    </table>
</div>
@push('scripts')
    <script>
        $(function() {
            // Use safe Select2 initialization
            if (typeof safeSelect2Init === 'function') {
                safeSelect2Init("#storeLocation");
                safeSelect2Init("#moveType");
            } else {
                $("#storeLocation, #moveType").select2();
            }

            $("#storeLocation, #moveType").change(function() {
                refreshTable($("#stockMovementDataTable"));
            })

            let start = moment().subtract(7, 'days');
            let end = moment();
            $('.stockReportRange span').html(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));

            $("#stockStartDate").val(start.format('YYYY-MM-DD'));
            $("#stockEndDate").val(end.format('YYYY-MM-DD'));

            $('.stockReportRange').daterangepicker({
                startDate: start,
                endDate: end,
                alwaysShowCalendars: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(7, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                        'month').endOf('month')]
                }
            });

            $('.stockReportRange').on('apply.daterangepicker', function(ev, picker) {
                $("#stockStartDate").val(picker.startDate.format('YYYY-MM-DD'));
                $("#stockEndDate").val(picker.endDate.format('YYYY-MM-DD'));

                $('.stockReportRange span').html(picker.startDate.format('MMM D, YYYY') + ' - ' + picker
                    .endDate.format('MMM D, YYYY'));

                refreshTable($("#stockMovementDataTable"));
            });

            $("#stockMovementDataTable").DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, "asc"]
                ],
                autoWidth: false,
                pageLength: '<?= Config::get('params.list_limit_admin') ?>',
                ajax: {
                    url: '{!! route('item-centre.stock-movements', $item->id) !!}',
                    data: function(data) {
                        data.from = $("#stockStartDate").val();
                        data.to = $("#stockEndDate").val();
                        data.location = $("#storeLocation").val();
                        data.move_type = $("#moveType").val();
                    }
                },
                columns: [{
                    data: 'created_at',
                    name: 'created_at'
                }, {
                    data: 'user_name',
                    name: 'users.name'
                }, {
                    data: 'location.location_name',
                    name: 'location.location_name'
                }, {
                    data: 'qauntity',
                    name: 'qauntity',
                    searchable: false,
                    orderable: false,
                }, {
                    data: 'qty_in',
                    name: 'qty_in',
                    searchable: false,
                    orderable: false,
                }, {
                    data: 'qty_out',
                    name: 'qty_out',
                    searchable: false,
                    orderable: false,
                }, {
                    data: 'new_qoh',
                    name: 'new_qoh'
                }, {
                    data: 'selling_price',
                    name: 'selling_price'
                }, {
                    data: 'refrence',
                    name: 'refrence'
                }, {
                    data: 'document_no',
                    name: 'document_no'
                }, {
                    data: 'type',
                    name: 'type',
                    searchable: false,
                    orderable: false,
                }, ],
            });
        })
    </script>
@endpush
