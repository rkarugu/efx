<div style="padding:10px">
    <form action="{{ route('sales-and-receivables-reports.customer_statement') }}" method="get">
        <div class="row">
            <div class="col-sm-4">
                <input type="hidden" id="startDate" name="from">
                <input type="hidden" id="endDate" name="to">
                <input type="hidden" name="customer_code" value="{{ $customer->customer_code }}">
                <div class="row">
                    <label class="col-sm-4">Select Dates</label>
                    <div class="reportRange col-sm-8">
                        <i class="fa fa-calendar" style="padding:8px"></i>
                        <span class="flex-grow-1" style="padding:8px"></span>
                        <i class="fa fa-caret-down" style="padding:8px"></i>
                    </div>
                </div>
            </div>
            @if (can('print', 'customer-centre'))
                <div class="col-sm-4">
                    <button type="submit" class="btn btn-primary" name="manage-request" value="pdf">Print
                        Pdf</button>
                </div>
            @endif
        </div>
    </form>
</div>
<div style="padding: 10px">
    <table class="table table-striped table-bordered" id="statementDataTable">
        <thead>
            <tr>
                <th class="text-right" colspan="5">Opening Balance</th>
                <th id="openingBalance"
                    style="text-align: right; border-top: 1px solid #000;border-bottom: 1px solid #000;"></th>
            </tr>
            <tr>
                <th>Transaction Date</th>
                <th>Description</th>
                <th>Trans No</th>
                <th style="text-align: right;">Debit</th>
                <th style="text-align: right;">Credit</th>
                <th style="text-align: right;">Running Balance</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th style="text-align: right;" colspan="5">Closing Balance:</th>
                <th id="total" style="text-align: right; border-top: 1px solid #000;border-bottom: 1px solid #000;">
                </th>
            </tr>
        </tfoot>
    </table>
</div>
@push('styles')
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
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(document).ready(function() {
            let start = moment().subtract(30, 'days');
            let end = moment();

            $('.reportRange span').html(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
            $("#startDate").val(start.format('YYYY-MM-DD'));
            $("#endDate").val(end.format('YYYY-MM-DD'));

            $('.reportRange').daterangepicker({
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
            $('.reportRange').on('apply.daterangepicker', function(ev, picker) {
                $("#startDate").val(picker.startDate.format('YYYY-MM-DD'));
                $("#endDate").val(picker.endDate.format('YYYY-MM-DD'));

                $('.reportRange span').html(picker.startDate.format('MMM D, YYYY') + ' - ' + picker.endDate
                    .format('MMM D, YYYY'));

                refreshStatementTable();
            });

            $("#statementDataTable").DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, "asc"]
                ],
                autoWidth: false,
                pageLength: '<?= Config::get('params.list_limit_admin') ?>',
                ajax: {
                    url: '{!! route('customer-centre.statement', $customer->id) !!}',
                    data: function(data) {
                        data.from = $("#startDate").val();
                        data.to = $("#endDate").val();
                    }
                },
                columns: [{
                        data: 'trans_date',
                        name: 'trans_date',
                        orderable: false,
                    },
                    {
                        data: 'reference',
                        name: 'reference',
                        orderable: false,
                    },
                    {
                        data: 'document_no',
                        name: 'document_no',
                        orderable: false,
                    },
                    {
                        data: 'debit',
                        name: 'debit',
                        orderable: false,
                        searchable: false,
                        className: "text-right"
                    },
                    {
                        data: 'credit',
                        name: 'credit',
                        orderable: false,
                        searchable: false,
                        className: "text-right"
                    },
                    {
                        data: 'running_balance',
                        name: 'running_balance',
                        orderable: false,
                        searchable: false,
                        className: "text-right"
                    },
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    var json = api.ajax.json();

                    $("#total").html(json.total);
                    $("#openingBalance").html(json.opening_balance);
                }
            });
        });

        function refreshStatementTable() {
            $("#statementDataTable").DataTable().ajax.reload();
        }
    </script>
@endpush
