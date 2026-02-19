@extends('layouts.admin.admin')
@section('content')
    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"> {!! $title !!} </h3>
            </div>
            @include('message')
            <div class="box-body" style="padding-bottom:15px">
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="">From</label>
                                <input type="date" name="start-date" id="start-date" class="form-control"
                                    value="{{ request()->input('start-date') ?? date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="">To</label>
                                <input type="date" name="end-date" id="end-date" class="form-control"
                                    value="{{ request()->input('end-date') ?? date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <button type="submit" id="filter" class="btn btn-primary btn-sm"
                                    style="margin-top: 25px;">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-invert" id="dataTable" style="width: 100%">
                        <thead>
                            <tr>
                                <th style="width:8%">Sr. No.</th>
                                <th>Requested By</th>
                                <th>Break No</th>
                                <th>Date/Time</th>
                                <th>Approved By</th>
                                <th>Dispatch Status</th>
                                <th>Dispatch Date</th>
                                <th style="width: 180px">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
    </section>
@endsection
@section('uniquepagescript')
    <script>
        function printBill(slug) {
            jQuery.ajax({
                url: $(slug).attr('href'),
                type: 'GET',
                async: false, //NOTE THIS
                headers: {
                    'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    var divContents = response;
                    var printWindow = window.open('', '', 'width=600');
                    printWindow.document.write(divContents);
                    printWindow.document.close();
                    printWindow.print();
                    printWindow.close();

                }
            });

        }
        $(function() {
            var table = $('#dataTable').DataTable({
                processing: true,
                serverSide: true,
                order: [
                    [0, "desc"]
                ],
                pageLength: '<?= Config::get('params.list_limit_admin') ?>',
                "ajax": {
                    "url": '{!! route($model . '.index') !!}',
                    "dataType": "json",
                    "type": "GET",
                    "headers": {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: function(data) {
                        var from = $('#start-date').val();
                        var to = $('#end-date').val();
                        data.from = from;
                        data.to = to;
                    },
                    "dataSrc": function(suc) {
                        // if(suc.total){
                        //     $('#getTotal').html(suc.total);
                        // }
                        return suc.data;
                    },
                    "error": function(xhr, error, thrown) {
                        console.log('AJAX Error Details:');
                        console.log('Status:', xhr.status);
                        console.log('Status Text:', xhr.statusText);
                        console.log('Response Text:', xhr.responseText);
                        console.log('Error:', error);
                        console.log('Thrown:', thrown);
                        
                        // Show user-friendly error
                        alert('Error loading data. Please check the console for details and refresh the page.');
                    }
                },
                'fnDrawCallback': function(oSettings) {
                    $('.dataTables_filter').each(function() {
                        $('.remove-btn').remove();
                        $(this).append(
                            '<a class="btn btn-danger remove-btn mr-xs pull-right ml-2 btn-sm" style="margin-left:5px" href="{{ route($model . '.create') }}">Add {{ $title }}</a>'
                            );
                    });
                },
                columns: [{
                        mData: 'id',
                        orderable: true,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'requester_name',
                        name: 'requesters_name',
                        orderable: true
                    },
                    {
                        data: 'breaking_code',
                        name: 'breaking_code',
                        orderable: true
                    },
                    {
                        data: 'date_time',
                        name: 'date_time',
                        orderable: false
                    },
                    {
                        data: 'user_name',
                        name: 'user_name',
                        orderable: true
                    },
                    {
                        data: 'dispatch_status',
                        name: 'dispatch_status',
                        orderable: false
                    },
                    {
                        data: 'dispatch_date',
                        name: 'dispatch_date',
                        orderable: false
                    },
                    {
                        data: 'links',
                        name: 'links',
                        orderable: false
                    },
                ],
                "columnDefs": [{
                    "searchable": false,
                    "targets": 0
                }, ],
                language: {
                    searchPlaceholder: "Search"
                },
            });
            $('#filter').click(function(e) {
                e.preventDefault();
                table.draw();
                $('#modelId').modal('hide');
            });
        });
    </script>
@endsection
