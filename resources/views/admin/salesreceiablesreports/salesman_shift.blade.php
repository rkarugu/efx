@extends('layouts.admin.admin')

@section('content')
    <?php
    $logged_user_info = getLoggeduserProfile();
    $my_permissions = $logged_user_info->permissions;
    ?>
            <!-- Main content -->
    <section class="content">
        <div class="modal fade" id="confirmDownloadModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="box-title"> Download Loading Sheet</h3>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>

                    <div class="box-body">
                        Downloading the loading sheet will block this shift from taking more orders. Are you sure you want to download?
                    </div>

                    <div class="box-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <a id="confirmDownloadBtn" href="#" class="btn btn-primary">Confirm</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="confirmShiftReopenModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="box-title"> Reopen Shift</h3>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">
                        Reopening the shift will allow salesman to take orders. Are you sure you want to re-open?
                    </div>
                    <div class="box-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <a id="confirmShiftReopenBtn" href="#" class="btn btn-primary">Confirm</a>

                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Small boxes (Stat box) -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="box-header-flex">
                    <h3 class="box-title"> Salesman Shifts </h3>
                </div>
            </div>

            <div class="box-body">
                {!! Form::open(['route' => 'salesman-shifts.index', 'method' => 'get']) !!}
                <div class="row">
                    {{-- @if ($logged_user_info->role_id == 1 ||  $logged_user_info->role_id == 147) --}}

                        <div class="col-md-2 form-group">
                            <select name="branch" id="branch" class="form-control mlselect"  data-url="{{ route('admin.get-branch-routes') }}">
                                <option value="" selected disabled>Select branch</option>
                                @foreach ($branches as $branch)
                                    {{-- <option value="{{$branch->id}}" {{ $branch->id == request()->branch ? 'selected' : '' }}>{{$branch->name}}</option> --}}
                                    <option value="{{$branch->id}}" 
                                        {{ request()->has('branch') ? ($branch->id == request()->branch ? 'selected' : '') : ($branch->id == $authuser->restaurant_id ? 'selected' : '') }}>
                                        {{$branch->name}}
                                    </option>

                                @endforeach
                            </select>

                        </div>
                    {{-- @endif --}}
                    <div class="col-md-2 form-group">
                        <select name="route" id="route" class="mlselect form-control">
                            <option value="" selected disabled>Select Route</option>
                            @foreach ($routes as $route )
                                <option value="{{$route->id}}" {{ $route->id == request()->route ? 'selected' : '' }}>{{$route->route_name}}</option>

                            @endforeach
                        </select>

                    </div>

                    <div class="col-md-2 form-group">
                        <input type="date" name="start-date" id="from" class="form-control" value="{{ request()->get('start-date') ?? \Carbon\Carbon::now()->toDateString() }}">
                    </div>

                    <div class="col-md-2 form-group">
                        <input type="date" name="end-date" id="to" class="form-control" value="{{ request()->get('end-date') ?? \Carbon\Carbon::now()->toDateString() }}">
                    </div>

                    <div class="col-md-3 form-group">
                        <button type="submit" class="btn btn-success" name="manage-request" value="filter">Filter</button>
                        <input type="submit" class="btn btn-success" name="type" value="Download">
                        <a class="btn btn-success" href="{!! route('salesman-shifts.index') !!}">Clear </a>
                    </div>
                </div>

                {!! Form::close(); !!}

                <hr>

                @include('message')


                <div class="col-md-12 table-responsive">
                    <table class="table table-bordered table-hover" id="create_datatable">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Route</th>
                            <th>Shift Type</th>
                            <th>Opened At</th>
                            <th>Status</th>
                            <th>Closed At</th>
                            <th>Sales Man</th>
                            <th>Customer Count</th>
                            <th>Tonnage</th>
                            <th>Shift Total</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $totalTonnage = 0;
                        $totalAmount = 0;
                        $totalMet = 0;
                        $allTotalCustomers = 0;
                        $totalExpectedTonnage = 0;
                        ?>
                        @foreach ($all_item as $data)
                        @php
                            $timestamp = \Carbon\Carbon::parse($data->created_at);
                            $isMoreThan20HoursOld = $timestamp->diffInHours(now()) > 19;
                            $currentTime = \Carbon\Carbon::now();
                            $isPast8PM = $currentTime->hour >= 20;
                            $visitedCustomers = $data->met_customers_with_orders;
                            $totalCustomers = $data->total_customers; 
                            $tonnagePercentage = ($data->tonnage_target > 0) ? ($data->shift_tonnage / $data->tonnage_target) * 100 : 0;
                            $customerPercentage = ($totalCustomers > 0) ? ($visitedCustomers / $totalCustomers) * 100 : 0;


                        @endphp
                            <tr>
                                <td>{{$loop->index+1}}</td>
                                <td>{{ \Carbon\Carbon::parse($data->created_at)->toDateString() }}</td>
                                <td>{{ $data->route_name }}</td>
                                <td>{{ $data->shift_type }}</td>
                                <td>{{$data->shift_start_time ? \Carbon\Carbon::parse($data->shift_start_time)->toTimeString() : '-'}}</td>
                                <td>{{ ucfirst($data->status) }}</td>
                                <td>{{$data->shift_close_time ?  ($data->status == 'close' ? \Carbon\Carbon::parse($data->shift_close_time)->toTimeString() : '-') :  '-'}}</td>
                                <td>{{ $data->salesman_name }}</td>
                                <td>
                                
                                    <div class="progress mt-1" style="flex-grow: 1; margin-right: 10px; background-color: grey; position: relative;">
                                        <div class="progress-bar progress-bar-success " role="progressbar" style="width: {{ $customerPercentage }}%;" aria-valuenow="{{ $customerPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                        <span style="position: absolute; width: 100%; text-align: center; top: 0; left: 0; color:white;">
                                            {!! $visitedCustomers .' / '. $totalCustomers !!}
                                        </span>
                                    </div>
                                </td>
                                <td>

                                    <div class="progress mt-1" style="flex-grow: 1; margin-right: 10px; background-color: grey; position: relative;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $tonnagePercentage }}%;" aria-valuenow="{{ $tonnagePercentage }}" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                        <span style="position: absolute; width: 100%; text-align: center; top: 0; left: 0; color:white;">
                                            {{ number_format($data->shift_tonnage, 2) . ' / ' . $data->tonnage_target }}
                                        </span>
                                    </div>
                                    
                                 
                                </td>
                                <td style="text-align: right;">{!! manageAmountFormat($data->shift_total ) !!}</td>
                                <td>
                                    <div class="action-button-div">
                                        <a href="{{ route('salesman-shift-details', $data->id) }}" class="text-primary" title="Summary"><i class='fa fa-eye text-primary fa-lg'></i></a>

                                        @if ($data->status == 'close')
                                            @if (!$data->block_orders && (!$isMoreThan20HoursOld || !$isPast8PM))
                                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['salesman-shift___reopen-from-backend']))
                                                    <a href="#" data-shift-id="{{ $data->id }}" title="Reopen Shift" class="shift-reopen">
                                                        <i class="fas fa-door-open"></i>
                                                    </a>
                                                @endif
                                            @else
                                            @if ($logged_user_info->role_id == 1)
                                                <a href="#" data-shift-id="{{ $data->id }}" title="Reopen Shift" class="shift-reopen">
                                                    <i class="fas fa-door-open"></i>
                                                </a>
                                            @endif

                                            @endif

                                            <a href="{{ route('salesman-shifts.delivery-report', $data->id) }}" title="Delivery Report"><i class='fa fa-file-pdf fa-lg'></i></a>
                                            <a href="{{ route('salesman-shifts.delivery-sheet', $data->id) }}" title="Delivery Sheet"><i class='fa fa-file-pdf fa-lg text-danger ' style="background-color: transparent;"></i></a>
                                            <a href="{{ route('salesman-shifts.loading-sheet', $data->id) }}" title="Loading Sheet"><i class='fa fa-file-pdf fa-lg'></i></a>
                                        @endif


                                    </div>
                                </td>
                            </tr>
                                <?php
                                $totalTonnage = $totalTonnage + $data->shift_tonnage;
                                $totalAmount = $totalAmount + $data->shift_total;
                                $totalMet += $visitedCustomers;
                                $totalExpectedTonnage += $data->tonnage_target;
                                $allTotalCustomers += $totalCustomers;
                                ?>

                        @endforeach

                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="8">Total</th>
                            <th style="text-align: center;">{{ $totalMet. ' / ' . $allTotalCustomers}}</th>
                            <th style="text-align: center;">{{ number_format($totalTonnage, 2) . ' / ' . $totalExpectedTonnage}}</th>
                            <th style="text-align:right;">{{ manageAmountFormat($totalAmount) }}</th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection


@section('uniquepagestyle')
    <link rel="stylesheet" href="{{ asset('assets/admin/dist/datepicker.css') }}">
    <link href="{{ asset('assets/admin/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet"/>
    <style>
        .table{
            overflow-x: auto;
        }
    </style>
@endsection

@section('uniquepagescript')
    <script src="{{ asset('assets/admin/dist/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
    <script type="text/javascript">
        $(function () {
            $('body').addClass('sidebar-collapse');

            $(".mlselect").select2();
        });
    </script>

    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>



    <script type="text/javascript">
        $(document).ready(function () {
            $('.download-link').on('click', function (event) {
                event.preventDefault();
                var shiftId = $(this).data('shift-id');
                $('#confirmDownloadBtn').attr('href', "{{ url('admin/salesman-shifts') }}/" + shiftId + "/loading-sheet");
                $('#confirmDownloadModal').modal('show');
            });

            //close modal
            $('#confirmDownloadBtn').on('click', function () {
                var downloadLink = $(this).attr('href');
                $('#confirmDownloadModal').modal('hide');
            });

            //shift reopen
            $('.shift-reopen').on('click', function (event) {
                event.preventDefault();
                var shiftId = $(this).data('shift-id');
                $('#confirmShiftReopenBtn').attr('href', "{{ url('admin/salesman-shifts') }}/" + shiftId + "/reopen-from-back-end");
                $('#confirmShiftReopenModal').modal('show');
            });

            //close modal
            $('#confirmShiftReopenBtn').on('click', function () {
                var downloadLink = $(this).attr('href');
                $('#confirmShiftReopenModal').modal('hide');

            });
            $('#branch').change(function() {
                    var branchId = $(this).val();
                    var url = $(this).data('url');
        
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: { branch_id: branchId },
                        success: function(data) {
                            console.log(data);
                            $('#route').empty();
                            $('#route').append('<option value="" selected disabled>Select Route</option>');
        
                            $.each(data.routes, function(key, value) {
                                $('#route').append('<option value="' + value.id + '">' + value.route_name + '</option>');
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                        }
                    });
                });
        });
    </script>
@endsection
