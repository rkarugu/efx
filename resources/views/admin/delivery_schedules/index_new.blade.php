@extends('layouts.admin.admin')

@section('content')
    <?php
    $logged_user_info = getLoggeduserProfile();
    $my_permissions = $logged_user_info->permissions;
    ?>
            <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="box-header-flex">
                    <h3 class="box-title"> Delivery Schedules </h3>
                </div>
            </div>

            <div class="box-body">
                {!! Form::open(['route' => 'delivery-schedules.index', 'method' => 'get']) !!}
                <div class="row">
                    @if ($logged_user_info->role_id == 1 ||  $logged_user_info->role_id == 147)

                        <div class="col-md-3 form-group">
                            <select name="branch" id="branch" class="mlselect form-control">
                                <option value="" selected disabled>Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{$branch->id}}" {{ $branch->id == request()->branch ? 'selected' : '' }}>{{$branch->name}}</option>

                                @endforeach
                            </select>

                        </div>
                    @endif
                    <div class="col-md-3 form-group">
                        <select name="route" id="route" class="mlselect form-control">
                            <option value="" selected disabled>Select Route</option>
                            @foreach ($routes as $route )
                                <option value="{{$route->id}}" {{ $route->id == request()->route ? 'selected' : '' }}>{{$route->route_name}}</option>

                            @endforeach
                        </select>

                    </div>

                    <div class="col-md-2 form-group">
                        <input type="date" name="from" id="from" class="form-control" value="{{ request()->from ?? \Carbon\Carbon::now()->toDateString() }}">
                    </div>

                    <div class="col-md-2 form-group">
                        <input type="date" name="to" id="to" class="form-control" value="{{ request()->to ?? \Carbon\Carbon::now()->toDateString() }}">
                    </div>

                    <div class="col-md-2 form-group">
                        <button type="submit" class="btn btn-success" name="manage-request" value="filter">Filter</button>
                        <a class="btn btn-success ml-12" href="{!! route('delivery-schedules.index') !!}">Clear </a>
                    </div>
                </div>

                {!! Form::close(); !!}

                <hr>

                @include('message')


                <div class="col-md-12">
                    <table class="table table-bordered table-hover" id="create_datatable">
                        <thead>
                        <tr>
                            <th></th>
                            <th>Delivery Date</th>
                            <th>Shift Date</th>
                            <th>Delivery No.</th>
                            <th>Route</th>
                            <th>Tonnage</th>
                            <th>Status</th>
                            <th>Delivery man</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                            // Group schedules by route
                            $groupedSchedules = collect($schedules)->groupBy('route_name');
                            $counter = 1;
                        ?>
                        @foreach ($groupedSchedules as $routeName => $routeSchedules)
                            <tr class="table-info">
                                <td colspan="9" class="text-center font-weight-bold" style="background-color: #e9ecef; font-size: 14px; padding: 10px;">
                                    <i class="fa fa-map-marker"></i> {{ $routeName }}
                                </td>
                            </tr>
                            @foreach ($routeSchedules as $data)
                            <tr>
                                <td>{{ $counter++ }}</td>
                                <td>{{ \Carbon\Carbon::parse($data->expected_delivery_date)->format('Y-m-d')}}</td>
                                <td>{{ \Carbon\Carbon::parse($data->shift_created_at)->format('Y-m-d') }}</td>
                                <td>{{ $data->delivery_number }}</td>
                                <td>
                                    @if($data->merged_shift_names)
                                        <small class="text-muted">{{ $data->merged_shift_names }}</small>
                                    @else
                                        {{ $data->route_name }}
                                    @endif
                                </td>
                                <td>{{ number_format($data->shift_tonnage,  2) }}</td>
                                <td>{{ $data->delivery_status }}</td>
                                <td>{{ $data->name }} {{ $data->license_plate_number ? "($data->license_plate_number)" : '' }}</td>
                                <td>
                                    <div class="action-button-div">


                                        <a href="{{route('delivery-schedules.show', $data->schedule_id)}}" title="View Details">
                                            <i class="fa fa-eye text-primary fa-lg"></i>
                                        </a>
                                        <!--<a href="{{route('route.split-schedules', $data->schedule_id)}}" title="View Details">
                                            <i class="fa fa-columns text-primary fa-lg"></i>
                                        </a>-->

                                        @if ((!$data->vehicle_id))
                                            @if (($logged_user_info->role_id == 1 || isset($my_permissions['delivery-schedule___assign-vehicles'])) )
                                                <button class="assign-vehicle-btn" id="showVehicles" data-schedule-id="{{ $data->schedule_id }}" title="Assign Vehicle"
                                                    style="background: transparent; border:none; ">

                                                <i class="fa fa-truck text-primary fa-lg"></i>
                                                </button>
                                            @endif
                                        @endif

                                        @if (in_array($data->delivery_status, ['consolidating', 'consolidated']) && $data->vehicle_id)
                                            @if (($logged_user_info->role_id == 1 || isset($my_permissions['delivery-schedule___assign-vehicles'])) )
                                                <a href="{{ route('delivery-schedules.unassignvehicles', $data->schedule_id)}}" title="unassign vehicle">
                                                    <i class="fa fa-truck text-danger fa-lg"></i>
                                                </a>
                                            @endif
                                        @endif

                                        @if ($data->delivery_status === 'loaded')
                                            @if (($logged_user_info->role_id == 1 || isset($my_permissions['delivery-schedule___issue-gate-pass'])) && ($data->gate_pass_status == 'pending'))
                                                <a href="#" data-schedule-id="{{ $data->schedule_id }}" title="Create Gate Pass" class="initiate-gate-pass">
                                                    <i class="fa fa-ticket fa-lg text-success"></i>
                                                </a>
                                            @endif
                                        @endif
                                        @if (($user->role_id == 1 || isset($my_permissions['delivery-schedule___end-schedule'])) && $data->delivery_status != 'finished')
                                            <a href="#" data-schedule-id="{{ $data->schedule_id }}" title="End Schedule" class="end-schedule">
                                                <i class="fas fa-hourglass-end"></i>                                            </a>
                                        @endif

                                        @if (in_array($data->delivery_status, ['consolidating', 'consolidated']))
                                            @if (($logged_user_info->role_id == 1 || isset($my_permissions['delivery-schedule___assign-vehicles'])) )
                                                <button class="view-shifts-btn" data-schedule-id="{{ $data->schedule_id }}" data-schedule-number="{{ $data->delivery_number }}" title="View/Manage Shifts"
                                                    style="background: transparent; border:none; ">
                                                    <i class="fa fa-list text-info fa-lg"></i>
                                                </button>
                                                <button class="merge-schedule-btn" data-schedule-id="{{ $data->schedule_id }}" data-schedule-number="{{ $data->delivery_number }}" title="Merge with another delivery"
                                                    style="background: transparent; border:none; ">
                                                    <i class="fa fa-compress text-warning fa-lg"></i>
                                                </button>
                                            @endif
                                        @endif

                                    </div>

                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- vehicle selection --}}
        <div class="modal fade" id="vehicle-assignment-modal" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"> Assign Vehicle </h3>
                    </div>

                    <div class="box-body">
                        <div class="form-group">
                            <label for="vehicles" class="control-label"> Select Vehicle </label>
                            <select name="selected_vehicle" id="selected_vehicle"
                                    class="form-control mlselect">
                                <option value="" selected disabled> Loading vehicles...</option>
                            </select>
                            <small class="text-muted" id="vehicle-count"></small>
                        </div>
                    </div>

                    <div class="box-footer">
                        <div class="box-header-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" id="assign-btn" class="btn btn-primary">Assign</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="initiateGatePassModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="box-title"> Initiate Gate Pass</h3>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>

                    <div class="box-body">
                        Are you sure you want to create a gate pass for this delivery? This will act as an acknowledgement that you have confirmed that everything has been loaded correctly.
                    </div>

                    <div class="box-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>

                            <form action="{{ route('delivery-schedules.create-gate-pass') }}" method="post">
                                {{ @csrf_field() }}

                                <input type="hidden" name="delivery_id" id="gatepass_delivery_id">

                                <input type="submit" value="Yes, confirm" class="btn btn-primary">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="endSchedule" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="box-title"> End Delivery Schedule</h3>

                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>

                    <div class="box-body">
                        Are you sure you want to end this delivery? 
                    </div>

                    <div class="box-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>

                            <form action="{{ route('delivery-schedules.end-schedule') }}" method="post">
                                {{ @csrf_field() }}

                                <input type="hidden" name="delivery_id" id="end-schedule-id">

                                <input type="submit" value="Yes, confirm" class="btn btn-primary">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection


@section('uniquepagestyle')
    <link rel="stylesheet" href="{{ asset('assets/admin/dist/datepicker.css') }}">
    <link href="{{ asset('assets/admin/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet"/>
@endsection

@section('uniquepagescript')
    <script src="{{ asset('assets/admin/dist/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
    <script src="{{asset('js/sweetalert.js')}}"></script>
    <script src="{{asset('js/form.js')}}"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>

    <script type="text/javascript">
        $(function () {

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
                function promptVehicleAssignment(scheduleId) {
                    console.log("Prompting vehicle assignment for schedule ID: " + scheduleId);
                    $('#vehicle-assignment-modal').data('schedule-id', scheduleId);
                    
                    // Fetch available vehicles dynamically
                    fetchAvailableVehicles();
                    
                    $('#vehicle-assignment-modal').modal('show');
                }
                
                function fetchAvailableVehicles() {
                    console.log('Fetching available vehicles...');
                    const $select = $('#selected_vehicle');
                    
                    // Show loading state
                    $select.html('<option value="" selected disabled>Loading vehicles...</option>');
                    $('#vehicle-count').text('');
                    
                    $.ajax({
                        url: '/api/delivery-schedules/available-vehicles',
                        method: 'GET',
                        success: function(response) {
                            console.log('Available vehicles response:', response);
                            const vehicles = response.data || [];
                            
                            // Clear and populate dropdown
                            $select.html('<option value="" selected disabled>Select vehicle</option>');
                            
                            if (vehicles.length === 0) {
                                $select.append('<option value="" disabled>No available vehicles</option>');
                                $('#vehicle-count').text('No vehicles available. All vehicles may be assigned to active schedules.');
                            } else {
                                vehicles.forEach(function(vehicle) {
                                    $select.append(
                                        $('<option></option>')
                                            .attr('value', vehicle.id)
                                            .text(vehicle.display_name)
                                    );
                                });
                                $('#vehicle-count').text(vehicles.length + ' vehicle(s) available');
                            }
                            
                            // Reinitialize Select2 if it exists
                            if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
                                $select.select2('destroy');
                            }
                            if ($.fn.select2) {
                                $select.select2({
                                    placeholder: 'Select vehicle',
                                    allowClear: true,
                                    dropdownParent: $('#vehicle-assignment-modal')
                                });
                            }
                            
                            console.log('Loaded ' + vehicles.length + ' available vehicles');
                        },
                        error: function(xhr, status, error) {
                            console.error('Error fetching vehicles:', error);
                            $select.html('<option value="" selected disabled>Error loading vehicles</option>');
                            $('#vehicle-count').text('Failed to load vehicles. Please try again.');
                            
                            let form = new Form();
                            form.errorMessage('Failed to load available vehicles. Please refresh the page.');
                        }
                    });
                }

                function assignVehicle() {
                    var scheduleId = $('#vehicle-assignment-modal').data('schedule-id');
                    var vehicleId = $('#selected_vehicle').val();
                    let form = new Form();

                    $.ajax({
                        url: '/api/delivery-schedules/assign-vehicle',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')  
                        },
                        data: {
                            schedule_id: scheduleId,
                            vehicle_id: vehicleId,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            $('#vehicle-assignment-modal').modal('hide');
                            form.successMessage('Vehicle assigned successfully.');
                            // alert('Vehicle assigned successfully');
                            window.location.reload();

                        },
                        error: function (xhr, status, error) {
                            form.errorMessage('An error was encountered. Please try again.')
                        }
                    });
                }

                $(document).on('click', '.assign-vehicle-btn', function () {
                    var scheduleId = $(this).data('schedule-id');
                    promptVehicleAssignment(scheduleId);
                });

                $(document).on('click', '#assign-btn', function () {
                    assignVehicle();
                });
                $('.initiate-gate-pass').on('click', function (event) {
                    event.preventDefault();
                    let scheduleId = $(this).data('schedule-id');
                    $("#gatepass_delivery_id").val(scheduleId)
                    $('#initiateGatePassModal').modal('show');
                });
                $('.end-schedule').on('click', function (event) {
                    event.preventDefault();
                    let scheduleId = $(this).data('schedule-id');
                    $("#end-schedule-id").val(scheduleId)
                    $('#endSchedule').modal('show');
                });

                // Merge delivery schedules
                $('.merge-schedule-btn').on('click', function (event) {
                    event.preventDefault();
                    let targetScheduleId = $(this).data('schedule-id');
                    let targetScheduleNumber = $(this).data('schedule-number');
                    
                    // Populate the merge modal
                    $('#merge-target-schedule-id').val(targetScheduleId);
                    $('#merge-target-schedule-number').text(targetScheduleNumber);
                    
                    // Populate source schedule dropdown (exclude the target)
                    let sourceSelect = $('#merge-source-schedule-id');
                    sourceSelect.empty();
                    sourceSelect.append('<option value="">Loading available deliveries...</option>');
                    sourceSelect.prop('disabled', true);
                    
                    // Show modal first
                    $('#merge-schedules-modal').modal('show');
                    
                    // Get all consolidating schedules from the table and load their shifts
                    let schedulePromises = [];
                    let scheduleData = [];
                    
                    $('table tbody tr:not(.table-info)').each(function() {
                        let $row = $(this);
                        let rowScheduleId = $row.find('.merge-schedule-btn').data('schedule-id');
                        
                        if (rowScheduleId && rowScheduleId != targetScheduleId) {
                            let rowScheduleNumber = $row.find('td').eq(3).text().trim(); // Delivery No. column
                            let rowRouteName = $row.find('td').eq(4).text().trim(); // Route column
                            let rowStatus = $row.find('td').eq(6).text().trim(); // Status column
                            
                            if (rowStatus.includes('consolidating') || rowStatus.includes('consolidated')) {
                                scheduleData.push({
                                    id: rowScheduleId,
                                    number: rowScheduleNumber,
                                    route: rowRouteName
                                });
                            }
                        }
                    });
                    
                    // If no schedules found, show message
                    if (scheduleData.length === 0) {
                        sourceSelect.empty();
                        sourceSelect.append('<option value="">No other deliveries available to merge</option>');
                        sourceSelect.prop('disabled', false);
                        return;
                    }
                    
                    // Load shifts for each schedule
                    scheduleData.forEach(function(schedule) {
                        let promise = $.ajax({
                            url: `/admin/delivery-schedules/${schedule.id}/shifts`,
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')  
                            }
                        }).then(function(response) {
                            let salesmanNames = [];
                            if (response.shifts && response.shifts.length > 0) {
                                salesmanNames = response.shifts.map(s => s.salesman);
                            }
                            let salesmanText = salesmanNames.length > 0 ? salesmanNames.join(', ') : 'No salesman';
                            return {
                                id: schedule.id,
                                text: `${schedule.number} - ${schedule.route} (${salesmanText})`
                            };
                        }).catch(function(error) {
                            console.error('Error loading shifts for schedule ' + schedule.id, error);
                            return {
                                id: schedule.id,
                                text: `${schedule.number} - ${schedule.route}`
                            };
                        });
                        schedulePromises.push(promise);
                    });
                    
                    // Wait for all shift data to load, then populate dropdown
                    Promise.all(schedulePromises).then(function(schedules) {
                        sourceSelect.empty();
                        sourceSelect.append('<option value="">Select delivery schedule to merge</option>');
                        schedules.forEach(function(schedule) {
                            sourceSelect.append(`<option value="${schedule.id}">${schedule.text}</option>`);
                        });
                        sourceSelect.prop('disabled', false);
                    }).catch(function(error) {
                        console.error('Error loading schedules', error);
                        sourceSelect.empty();
                        sourceSelect.append('<option value="">Error loading deliveries. Please try again.</option>');
                        sourceSelect.prop('disabled', false);
                    });
                });

                $('#confirm-merge-btn').on('click', function() {
                    let targetScheduleId = $('#merge-target-schedule-id').val();
                    let sourceScheduleId = $('#merge-source-schedule-id').val();
                    
                    if (!sourceScheduleId) {
                        alert('Please select a delivery schedule to merge');
                        return;
                    }
                    
                    if (confirm('Are you sure you want to merge these delivery schedules? This action cannot be undone.')) {
                        $.ajax({
                            url: `/admin/delivery-schedules/${targetScheduleId}/merge`,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')  
                            },
                            data: {
                                source_schedule_id: sourceScheduleId,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                $('#merge-schedules-modal').modal('hide');
                                alert('Delivery schedules merged successfully!');
                                window.location.reload();
                            },
                            error: function (xhr, status, error) {
                                let errorMsg = 'An error occurred while merging delivery schedules.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                alert(errorMsg);
                            }
                        });
                    }
                });

                // View shifts in a delivery
                $('.view-shifts-btn').on('click', function (event) {
                    event.preventDefault();
                    let scheduleId = $(this).data('schedule-id');
                    let scheduleNumber = $(this).data('schedule-number');
                    
                    $('#view-shifts-schedule-number').text(scheduleNumber);
                    $('#view-shifts-schedule-id').val(scheduleId);
                    
                    // Load shifts via AJAX
                    $.ajax({
                        url: `/admin/delivery-schedules/${scheduleId}/shifts`,
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')  
                        },
                        success: function (response) {
                            let shiftsHtml = '';
                            if (response.shifts && response.shifts.length > 0) {
                                response.shifts.forEach(function(shift) {
                                    shiftsHtml += `
                                        <tr>
                                            <td>${shift.id}</td>
                                            <td><strong>${shift.shift_name}</strong></td>
                                            <td>${shift.salesman}</td>
                                            <td>${shift.start_time}</td>
                                            <td>${shift.orders_count}</td>
                                            <td>
                                                ${response.shifts.length > 1 ? `
                                                    <button class="btn btn-sm btn-danger unmerge-shift-btn" 
                                                            data-shift-id="${shift.id}" 
                                                            data-schedule-id="${scheduleId}">
                                                        <i class="fa fa-expand"></i> Unmerge
                                                    </button>
                                                ` : '<span class="text-muted">Only shift</span>'}
                                            </td>
                                        </tr>
                                    `;
                                });
                            } else {
                                shiftsHtml = '<tr><td colspan="6" class="text-center">No shifts found</td></tr>';
                            }
                            $('#shifts-table-body').html(shiftsHtml);
                            $('#view-shifts-modal').modal('show');
                        },
                        error: function (xhr, status, error) {
                            alert('Error loading shifts');
                        }
                    });
                });

                // Unmerge shift
                $(document).on('click', '.unmerge-shift-btn', function() {
                    let shiftId = $(this).data('shift-id');
                    let scheduleId = $(this).data('schedule-id');
                    
                    if (confirm('Are you sure you want to unmerge this shift? A new delivery schedule will be created for it.')) {
                        $.ajax({
                            url: `/admin/delivery-schedules/${scheduleId}/unmerge/${shiftId}`,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')  
                            },
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function (response) {
                                $('#view-shifts-modal').modal('hide');
                                alert('Shift unmerged successfully! A new delivery schedule has been created.');
                                window.location.reload();
                            },
                            error: function (xhr, status, error) {
                                let errorMsg = 'An error occurred while unmerging the shift.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                alert(errorMsg);
                            }
                        });
                    }
                });
            }
        );

    </script>

    {{-- View Shifts Modal --}}
    <div class="modal fade" id="view-shifts-modal" tabindex="-1" role="dialog" aria-labelledby="viewShiftsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content box box-primary">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewShiftsLabel">Shifts in Delivery Schedule</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="view-shifts-schedule-id">
                    
                    <div class="form-group">
                        <label>Delivery Schedule:</label>
                        <p class="form-control-static"><strong id="view-shifts-schedule-number"></strong></p>
                    </div>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Shift ID</th>
                                <th>Shift Name</th>
                                <th>Salesman</th>
                                <th>Start Time</th>
                                <th>Orders</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="shifts-table-body">
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Note:</strong> You can unmerge shifts to create separate delivery schedules. This is useful if you need to split a consolidated delivery.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Merge Delivery Schedules Modal --}}
    <div class="modal fade" id="merge-schedules-modal" tabindex="-1" role="dialog" aria-labelledby="mergeSchedulesLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content box box-primary">
                <div class="modal-header">
                    <h5 class="modal-title" id="mergeSchedulesLabel">Merge Delivery Schedules</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="merge-target-schedule-id">
                    
                    <div class="form-group">
                        <label>Target Delivery Schedule:</label>
                        <p class="form-control-static"><strong id="merge-target-schedule-number"></strong></p>
                        <small class="text-muted">This delivery schedule will receive all shifts from the source schedule.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="merge-source-schedule-id">Source Delivery Schedule to Merge:</label>
                        <select class="form-control" id="merge-source-schedule-id">
                            <option value="">Select delivery schedule to merge</option>
                        </select>
                        <small class="text-muted">This delivery schedule will be deleted after merging.</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>Note:</strong> This action will:
                        <ul>
                            <li>Move all shifts from the source to the target delivery</li>
                            <li>Recalculate items and customers</li>
                            <li>Delete the source delivery schedule</li>
                            <li>You can unmerge shifts later if needed</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirm-merge-btn">
                        <i class="fa fa-compress"></i> Merge Schedules
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
