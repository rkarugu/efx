@extends('layouts.admin.admin')
@section('content')
    <script type="text/javascript">
        window.employee = JSON.parse('{!! $row !!}')
        selected_bin = JSON.parse('{!! $selected_bin !!}')
    </script>

    <section class="content" id="edit-user-page">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="box-header-flex">
                    <h3 class="box-title"> {!! $title !!} </h3>

                    <a href="{{ route("$model.index") }}" class="btn btn-success btn-sm"><i class="fas fa-arrow-left"></i> Back </a>
                </div>
            </div>

            <div class="box-body">
                <div class="session-message-container">
                    @include('message')
                </div>

                {!! Form::model($row, [
                    'method' => 'PATCH',
                    'route' => [$model . '.update', $row->slug],
                    'class' => 'validate',
                    'enctype' => 'multipart/form-data',
                ]) !!}
                {{ csrf_field() }}
                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Name</label>
                        <div class="col-sm-10">
                            {!! Form::text('name', null, [
                                'maxlength' => '255',
                                'placeholder' => 'Name',
                                'required' => true,
                                'class' => 'form-control',
                            ]) !!}
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Branch</label>
                        <div class="col-sm-10">
                            {!! Form::select('restaurant_id', $restroList, null, [
                                'placeholder' => 'Select Branch ',
                                'class' => 'form-control mlselec6t',
                                'required' => true,
                                'title' => 'Please select Branch',
                                'id' => 'branch',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Department</label>
                        <div class="col-sm-10">
                            {!! Form::select('wa_department_id', getDepartmentDropdown($row->restaurant_id), $row->wa_department_id, [
                                'class' => 'form-control mlselec6t',
                                'placeholder' => 'Please select department',
                                'id' => 'department',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Email</label>
                        <div class="col-sm-10">
                            {!! Form::text('email', null, [
                                'maxlength' => '255',
                                'placeholder' => 'Email',
                                'required' => true,
                                'class' => 'form-control',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Phone Number</label>
                        <div class="col-sm-10">
                            {!! Form::text('phone_number', null, [
                                'maxlength' => '255',
                                'placeholder' => 'Phone Number',
                                'required' => true,
                                'class' => 'form-control',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">ID Number</label>
                        <div class="col-sm-10">
                            {!! Form::text('id_number', null, [
                                'maxlength' => '255',
                                'placeholder' => 'Id Number',
                                'required' => true,
                                'class' => 'form-control',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="role_id" class="col-sm-2 control-label">Role</label>
                        <div class="col-sm-10">
                            <select name="role_id" id="role-id" class="form-control" required v-model="selectedRoleId">
                                <option v-for="role in roles" :value="role.id" :key="role.id">
                                    @{{ role.title }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Store Location</label>
                        <div class="col-sm-10">

                            {!! Form::select('wa_location_and_store_id', getStoreLocationDropdownByBranch($row->restaurant_id), null, [
                                'class' => 'form-control mlselec6t store_location_id',
                                'required' => true,
                                'placeholder' => 'Please select store location',
                                'id' => 'wa_location_and_store_id',
                            ]) !!}
                        </div>
                    </div>
                </div>

                {{-- <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Bin Location </label>
                        <div class="col-sm-10">
                            {!! Form::select('wa_unit_of_measures_id', getUnitOfMeasureList(), $row->wa_unit_of_measures_id, [
                                'class' => 'form-control wa_unit_of_measures_id',
                                'placeholder' => 'Please select bin location',
                                'id' => 'wa_unit_of_measures_id',
                            ]) !!}
                        </div>
                    </div>
                </div> --}}

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">Bin Location </label>
                        <div class="col-sm-10">
                            <select name="wa_unit_of_measures_id" id="wa_unit_of_measures_id"
                                class="form-control wa_unit_of_measures_id" placeholder="Please select bin location">
                                <option selected="selected" value="">Please select bin location</option>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="box-body">
                    <div class="form-group">
                        <label for="route-id" class="col-sm-2 control-label">Route</label>
                        <div class="col-sm-10">
                            <select name="route[]" id="route-id" class="form-control" multiple v-model="userRouteIds">
                                <option v-for="route in filteredRoutes" :value="route.id" :key="route.id"
                                    v-if="selectedRoleId" :selected="userRouteIds.includes(route.id)">
                                    @{{ route.route_name }}</option>
                                <option v-else value="null" disabled> Please select a role first</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="drop-limit" class="col-sm-2 control-label">Drop Limit</label>
                        <div class="col-sm-10">
                            {!! Form::number('drop_limit', null, ['class' => 'form-control', 'required' => false, 'id' => 'drop_limit']) !!}
                            <small id="helperText" class="form-text text-muted">This is Required if role is POS
                                Cashier.</small>
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="form-group">
                        <label for="inputEmail3" class="col-sm-2 control-label">E-Sign Image</label>
                        <div class="col-sm-10">
                            <input type="file" name="e_sign_image" title="Please select image" accept="image/*">
                            <img width="100px" height="100px;" src="{{ asset('uploads/users/' . $row->e_sign_image) }}">
                        </div>
                    </div>
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-success btn-sm">Update</button>
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </section>
@endsection

@section('uniquepagestyle')
    <link rel="stylesheet" href="{{ asset('assets/admin/dist/datepicker.css') }}">
    <link href="{{ asset('assets/admin/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
@endsection

@section('uniquepagescript')
    <script src="{{ asset('assets/admin/dist/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script>

    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script type="module">
        import {
            createApp
        } from 'https://unpkg.com/vue@3/dist/vue.esm-browser.js'

        window.vueApp = createApp({
            data() {
                return {
                    selectedRoleId: null,
                    roles: [],
                    routes: [],
                    filteredRoutes: [],
                    userRouteIds: [],
                }
            },

            computed: {
                employee() {
                    return window.employee
                },
            },

            created() {
                this.fetchRoles()
                this.fetchRoutes()
            },

            mounted() {
                // Set user role
                this.selectedRoleId = this.employee.role_id
                this.userRouteIds = this.employee.route_ids

                $("#route-id").select2();
                $("#role-id").select2();
                $("#select-category_id").select2();
                $("#branch").select2();
                $("#department").select2();
                $("#wa_location_and_store_id").select2();
                $("#role_id").select2();
                $(".mlselec7t").select2();

                $('.datepicker').datepicker({
                    format: 'yyyy-mm-dd'
                });

                $("#branch").change(function() {
                    let selected_branch_id = $("#branch").val();
                    // managedepartment(selected_branch_id);
                    manageStoreLocation(selected_branch_id);
                });



                $("#role-id").change(() => {
                    this.userRouteIds = []
                    let roleId = parseInt($("#role-id").val());
                    this.selectedRoleId = roleId
                    this.filterRoutes(roleId);
                });

                // let selected_location_id = $("#wa_location_and_store_id").val();
            },

            methods: {

                fetchRoles() {
                    axios.get('/api/roles').then(response => {
                        this.roles = response.data.data
                    }).catch(error => {
                        // pass for now
                        // TODO: Handle exception
                    })
                },

                fetchRoutes() {
                    axios.get('/api/routes').then(response => {
                        this.routes = response.data.data
                        this.filterRoutes(this.selectedRoleId)
                        // $("#route-id").val(this.employee.route_ids)
                    }).catch(error => {
                        // pass for now
                        // TODO: Handle exception
                    })
                },

                filterRoutes(value) {
                    this.filteredRoutes = this.routes;

                    // Salesman
                    if (value === 4) {
                        this.filteredRoutes = this.filteredRoutes.filter(route => {
                            if (this.userRouteIds.includes(route.id)) {
                                return true
                            }

                            return (route.is_physical_route && !route.has_salesman);
                        });
                    }

                    // Route manager
                    if (value === 5) {
                        this.filteredRoutes = this.filteredRoutes.filter(route => {
                            if (this.userRouteIds.includes(route.id)) {
                                return true
                            }

                            return (route.is_physical_route && !route.has_route_manager) || this
                                .userRouteIds.includes(route.id);
                        });
                    }
                },
                
            },
        })

        window.vueAppInstance = window.vueApp.mount('#edit-user-page')
    </script>

    <script>
        $(document).ready(function() {
            $("#role-id").change(function() {
                if ($(this).val() === "170") {
                    $("#drop_limit").prop('required', true);
                } else {
                    $("#drop_limit").prop('required', true);
                }
            });

            $('.wa_unit_of_measures_id').select2()

            let selected_location_id = $("#wa_location_and_store_id").val();
            getLocationBins(selected_location_id);

            var selectedBinId = {!! json_encode($selected_bin) !!};

            // function preselectBin(binId) {
            //     // $("#wa_unit_of_measures_id").val(binId).change();
            //     $("#wa_unit_of_measures_id").val(binId).trigger('change');
            // }


            // if (selectedBinId) {
            //     preselectBin(selectedBinId);
            // }

            $("#wa_location_and_store_id").change(function() {
                let selected_location_id = $("#wa_location_and_store_id").val();
                getLocationBins(selected_location_id);
            });

        });

        function managedepartment(branch_id) {
            if (branch_id != "") {
                jQuery.ajax({
                    url: '{{ route('external-requisitions.get-departments') }}',
                    type: 'POST',
                    data: {
                        branch_id: branch_id
                    },
                    headers: {
                        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $("#department").val('');
                        $("#department").html(response);

                        //manageTotalCost();

                    }
                });
            } else {
                $("#department").val('');
                $("#department").html('<option selected="selected" value="">Please select department</option>');
            }
        }

        function manageStoreLocation(branch_id) {
            if (branch_id != "") {
                jQuery.ajax({
                    url: '{{ route('locations.get-location-by_branch') }}',
                    type: 'POST',
                    data: {
                        branch_id: branch_id
                    },
                    headers: {
                        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log(response);
                        $("#wa_location_and_store_id").val('');
                        $("#wa_location_and_store_id").html(response);

                        // $("#wa_unit_of_measures_id").html('');
                        // $(".wa_unit_of_measures_id").append('<option value="' + value.id + '">' +
                        //     value.title + '</option>');
                        //manageTotalCost();

                    }
                });
            } else {
                $("#wa_location_and_store_id").val('');
                $("#wa_location_and_store_id").html(
                    '<option selected="selected" value="">Please select store location</option>');
            }
        }

        function getLocationBins(location_id) {

            var selectedBinId = {!! json_encode($selected_bin) !!};

            if (location_id != "") {
                jQuery.ajax({
                    url: '{{ route('bins.get-bins-by-location') }}',
                    type: 'POST',
                    data: {
                        location_id: location_id
                    },
                    headers: {
                        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // $("#wa_unit_of_measures_id").html('');
                        // $.each(response, function(index, value) {
                        //     $(".wa_unit_of_measures_id").append('<option value="' + value.id + '">' +
                        //         value.title + '</option>');
                        // });

                        // if (selectedBinId) {
                        //     $("#wa_unit_of_measures_id").val(selectedBinId).trigger('change');
                        // }

                        $("#wa_unit_of_measures_id").html(
                            '<option selected="selected" value="">Please select bin location</option>');
                        if (response.length > 0) {
                            $.each(response, function(index, value) {
                                $(".wa_unit_of_measures_id").append('<option value="' + value.id +
                                    '">' + value.title + '</option>');
                            });

                            if (selectedBinId) {
                                $("#wa_unit_of_measures_id").val(selectedBinId).trigger('change');
                            }

                            $("#wa_unit_of_measures_id").prop('disabled', false);
                        } else {
                            $("#wa_unit_of_measures_id").prop('disabled', true);
                        }
                    }
                });
            } else {
                // $("#wa_unit_of_measures_id").html(
                //     '<option selected="selected" value="">Please select bin location</option>');
                $("#wa_unit_of_measures_id").html(
                    '<option selected="selected" value="">Please select bin location</option>');
                $("#wa_unit_of_measures_id").prop('disabled', true);
            }
        }
    </script>
@endsection
