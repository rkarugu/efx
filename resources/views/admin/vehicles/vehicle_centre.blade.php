@extends('layouts.admin.admin')

@section('content')
    @php
        $user = getLoggeduserProfile();
    @endphp
    <script>
        window.user = {!! $user !!}
        window.vehicle = {!! $vehicle !!}
    </script>

    <section class="content" id="vehicle-centre-page">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="box-header" style="border-bottom: 1px solid #eee">
                    @include('message')
                    <div class="d-flex justify-content-between">
                        <h4>Vehicle Information </h4>
                        <div class="text-right" style="margin: 5px 0">
                            

                            <a href="javascript:void(0);" title="Assign Driver" v-if="!vehicle.driver" @click="promptAssignDriver(vehicle)" class="btn btn-primary" style="margin-left:5px;">
                                <i class="fas fa-user-tie"></i>
                            </a>

                            <a href="javascript:void(0);" title="Unassign Driver" v-if="vehicle.driver" @click="promptUnAssignDriver(vehicle)" class="btn btn-primary" style="margin-left:5px;">
                                <i class="fas fa-user-slash"></i>
                            </a>
                            <a href="javascript:void(0);" title="Assign Turn Boy" v-if="!vehicle.turnboy" @click="promptAssignTurnboy(vehicle)" class="btn btn-primary" style="margin-left:5px;">
                                <i class="fas fa-user-tag"></i>
                            </a>

                            <a href="javascript:void(0);" title="Unassign Turn Boy" v-if="vehicle.turnboy" @click="promptUnAssignTurnboy(vehicle)" class="btn btn-primary" style="margin-left:5px;">
                                <i class="fas fa-user-times"></i>
                            </a>

                            <a href="javascript:void(0);" title="Switch Off" 
                            v-if="(vehicle.switch_off_status === 'on') && (currentUser.role_id === 1)" 
                            @click="promptSwitchOffVehicle(vehicle)" class="btn btn-primary" style="margin-left:5px;">
                                <i class="fas fa-power-off"></i>
                            </a>

                            <a href="javascript:void(0);" title="Switch On" 
                            v-if="(vehicle.switch_off_status === 'off') && (currentUser.role_id === 1)" 
                            @click="promptSwitchOnVehicle(vehicle)" class="btn btn-primary" style="margin-left:5px; border-color: #038105 !important;background-color: #038105 !important;">
                                <i class="fas fa-power-off"></i>
                            </a>
                            <a href={{ route('vehicles.index') }} class="btn btn-primary" style="margin-left:5px;">
                                <i class="fas fa-long-arrow-alt-left"></i>
                                Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Vehicle Plate</th>
                                    <td><span v-cloak> @{{ vehicle.license_plate_number }}</span></td>
                                </tr>
                                <tr>
                                    <th>Vehicle Type</th>
                                    <td><span v-cloak> @{{ vehicle.model?.name }} </span></td>
                                </tr>
                                <tr>
                                    <th>Supplier</th>
                                    <td><span v-cloak>@{{ vehicle.model?.supplier?.name ?? '-' }}</span></td>
                                </tr>
                                <tr>
                                    <th>Primary Responsibility</th>
                                    <td><span v-cloak>@{{ vehicle.primary_responsibility ?? '-'}}</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-sm-6">
                            <table class="table table-bordered"> 
                                <tr>
                                    <th> Max Load Capacity</th>
                                    <td v-cloak> @{{ vehicle.model?.ma_load_capacity }} <span v-if="vehicle.max_load_capacity">T</span></td>
                                </tr>
                                <tr>
                                    <th> Fuel Tank Capacity</th>
                                    <td v-cloak> @{{ vehicle.model?.fuel_tank_capacity }} <span v-if="vehicle.fuel_tank_capacity">L</span></td>
                                </tr>   
                                <tr>
                                    <th>Driver</th>
                                    <td><span v-cloak>@{{ vehicle.driver?.name ?? '-' }}</span></td>
                                </tr>
                                <tr>
                                    <th>Turnboy</th>
                                    <td><span v-cloak>@{{ vehicle.turnboy?.name ?? '-' }}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box box-primary">
            <ul class="nav nav-tabs" id="vehicleCentreTabs">
                <li class="active"><a href="#liveLocation" data-toggle="tab">Live Location</a></li>
                <li><a href="#fuelHistory" data-toggle="tab">Fuel History</a></li>
                {{-- <li><a href="#serviceHistory" data-toggle="tab">Service History</a></li>
                <li><a href="#tyre" data-toggle="tab">Tyre</a></li> --}}
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="liveLocation">
                    @include('admin.vehicles.partials.live_location')
                    
                </div>
                <div class="tab-pane" id="fuelHistory" style="padding: 10px;">
                    @include('admin.vehicles.partials.fuel_history')
                </div>
                <div class="tab-pane" id="serviceHistory">
                    @include('admin.vehicles.partials.service_history')
                </div>
                <div class="tab-pane" id="tyre">
                    @include('admin.vehicles.partials.tyres')
                </div>
            </div>
        </div>

        <!-- Driver Assignment -->
        <div class="modal fade" id="driver-assignment-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"> Assign Driver </h3>
                    </div>

                    <div class="box-body">
                        <div class="form-group">
                            <label for="driver_id" class="control-label"> Select Driver </label>
                            <select name="driver_id" id="driver_id" v-model="selectedDriverId" class="form-control">
                                <option value="" selected disabled> Select driver</option>
                                <option v-for="driver in availableDrivers" :key="driver.id" :value="driver.id">@{{ driver.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="box-footer">
                        <div class="box-header-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" @click="assignDriver">Assign</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Driver Unassignment -->
        <div class="modal fade" id="driver-unassignment-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"> Un-assign Driver </h3>
                    </div>

                    <div class="box-body">
                        Are you sure you want to unassign driver @{{ activeVehicle?.driver?.name }} from @{{ activeVehicle?.license_plate_number }}?
                    </div>

                    <div class="box-footer">
                        <div class="box-header-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">No, cancel</button>
                            <button type="button" class="btn btn-primary" @click="unAssignDriver">Yes, Proceed</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
              <!-- Turn Boy Assignment -->
              <div class="modal fade" id="turnboy-assignment-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title"> Assign Turn Boy </h3>
                        </div>
    
                        <div class="box-body">
                            <div class="form-group">
                                <label for="turnboy_id" class="control-label"> Select Turn Boy </label>
                                <select name="turnboy_id" id="turnboy_id" v-model="selectedTurnboyId" class="form-control">
                                    <option value="" selected disabled> Select turn boy</option>
                                    <option v-for="turnboy in availableTurnboys" :key="turnboy.id" :value="turnboy.id">@{{ turnboy.name }}</option>
                                </select>
                            </div>
                        </div>
    
                        <div class="box-footer">
                            <div class="box-header-flex">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" @click="assignTurnboy">Assign</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
             <!-- Turn Boy Unassignment -->
        <div class="modal fade" id="turnboy-unassignment-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"> Un-assign Turn boy </h3>
                    </div>

                    <div class="box-body">
                        Are you sure you want to unassign turn boy @{{ activeVehicle?.turnboy?.name }} from @{{ activeVehicle?.license_plate_number }}?
                    </div>

                    <div class="box-footer">
                        <div class="box-header-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">No, cancel</button>
                            <button type="button" class="btn btn-primary" @click="unAssignTurnboy">Yes, Proceed</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Switch Off -->
        <div class="modal fade" id="vehicle-switch-off-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content box">
                    <div class="box-header with-border">
                        <h3 class="box-title"> Confirm Vehicle Switch Off </h3>
                    </div>

                    <div class="box-body">
                        Are you sure you want to switch off @{{ activeVehicle?.license_plate_number }}?
                        <br>
                        <br>
                        <strong>Please note that this is a critical operation and assumes you've considered all safety pre-cautions.</strong>
                    </div>

                    <div class="box-footer">
                        <div class="box-header-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">No, cancel</button>
                            <button type="button" class="btn btn-primary" @click="switchOffVehicle">Yes, Switch Off</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vehicle Switch On -->
        <div class="modal fade" id="vehicle-switch-on-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content box">
                    <div class="box-header with-border">
                        <h3 class="box-title"> Confirm Vehicle Switch On </h3>
                    </div>

                    <div class="box-body">
                        Are you sure you want to switch on @{{ activeVehicle?.license_plate_number }}?
                    </div>

                    <div class="box-footer">
                        <div class="box-header-flex">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">No, cancel</button>
                            <button type="button" class="btn btn-primary" @click="switchOnVehicle">Yes, Switch On</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('uniquepagestyle')
    <link href="{{ asset('assets/admin/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet"/>
    <style>
        #map-view {
            height: 700px;
        }
    </style>
@endsection

@section('uniquepagescript')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
    <script src="{{asset('js/sweetalert.js')}}"></script>
    <script src="{{asset('js/form.js')}}"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script type="importmap">
        {
          "imports": {
            "vue": "https://unpkg.com/vue@3/dist/vue.esm-browser.js"
          }
        }
    </script>
    <script>
        (g => {
            let h, a, k, p = "The Google Maps JavaScript API", c = "google", l = "importLibrary", q = "__ib__", m = document, b = window;
            b = b[c] || (b[c] = {});
            let d = b.maps || (b.maps = {}), r = new Set, e = new URLSearchParams, u = () => h || (h = new Promise(async (f, n) => {
                await (a = m.createElement("script"));
                e.set("libraries", [...r] + "");
                for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                e.set("callback", c + ".maps." + q);
                a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                d[q] = f;
                a.onerror = () => h = n(Error(p + " could not load."));
                a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                m.head.append(a)
            }));
            d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() => d[l](f, ...n))
        })({
            key: "{{ $googleMapsApiKey }}",
            v: "weekly",
        });
    </script>
    <script type="module">
        import {createApp} from 'vue';

        const app = createApp({
            data() {
                return {
                    loading: true,
                    availableDrivers: [],
                    availableTurnboys: [],

                    selectedDriverId: null,
                    selectedTurnboyId: null,
                    fuelHistory: [],
                    fuelHistoryTotal: 0,
                }
            },

            created() {
                this.fetchFuelHistory()
            },

            mounted() {
                $('body').addClass('sidebar-collapse');
                // this.initMap();
                
            },

            computed: {
                currentUser() {
                    return window.user
                },
                vehicle() {
                    return window.vehicle
                },

                toaster() {
                    return new Form();
                },
            },

            methods: {
                async initMap() {
                    const {Map} = await google.maps.importLibrary("maps");
                    await google.maps.importLibrary("geometry");

                    this.map = new Map(document.getElementById("map-view"), {
                        center: {lat: -1.28333, lng: 36.81667},
                        zoom: 13,
                        mapId: "7c9bd9e078617725",
                    });
                },

                initPusher() {
                    // Enable pusher logging - don't include this in production
                    // Pusher.logToConsole = true;

                    let pusher = new Pusher('b2012a3c72f2a36ad705', {
                        cluster: 'ap2'
                    });

                    let channel = pusher.subscribe('telematics');
                    channel.bind('data.received', function (data) {
                        console.log(data)
                    });
                },

                resetMap() {
                    $("#map-view").css("display", "none");
                    setTimeout(async () => {
                        $("#map-view").css("display", "block");
                        await this.initMap();
                    }, 100)
                },

                async drawVehicleOnMap(lat, lng) {
                    const {AdvancedMarkerElement, PinElement} = await google.maps.importLibrary("marker");

                    const icon = document.createElement("div");
                    icon.className = "price-tag";
                    icon.innerHTML = '<i class="fas fa-truck-moving truck-icon"></i>';

                    if (!this.marker) {
                        this.marker = new AdvancedMarkerElement({
                            position: new google.maps.LatLng(lat, lng),
                            content: icon,
                            title: `${this.vehicle.name} ${this.vehicle.license_plate_number}`,
                        });

                        this.marker.map = this.map;
                    } else {
                        this.marker.position = new google.maps.LatLng(lat, lng)
                    }
                },

                fetchAvailableDrivers() {
                    console.log('Fetching available drivers for branch:', this.currentUser.restaurant_id);
                    axios.get('/api/vehicles/available-drivers', {
                        params: {
                            branch_id: this.currentUser.restaurant_id
                        }
                    }).then(res => {
                        console.log('Available drivers response:', res.data);
                        this.availableDrivers = res.data.data
                        console.log('Available drivers set to:', this.availableDrivers);
                        console.log('Number of available drivers:', this.availableDrivers.length);
                        // Initialize Select2 after data is loaded and modal is shown
                        this.$nextTick(() => {
                            // Wait for modal to be fully shown before initializing Select2
                            setTimeout(() => {
                                this.initializeSelect2();
                            }, 300);
                        });
                    }).catch(err => {
                        console.error('Error fetching available drivers:', err);
                        toastr.error('Failed to load available drivers');
                    })
                },

                fetchAvailableTurnboys() {
                    axios.get('/api/vehicles/available-turnboys', {
                        params: {
                            branch_id: this.currentUser.restaurant_id
                        }
                    }).then(res => {
                        this.availableTurnboys = res.data.data
                        // Initialize Select2 after data is loaded
                        this.$nextTick(() => {
                            this.initializeTurnboySelect2();
                        });
                    }).catch(err => {

                    })
                },
                assignDriver() {
                    if (!this.selectedDriverId) {
                        return this.toaster.errorMessage('Please select a driver.')
                    }

                    axios.post('/api/vehicles/assign-driver', {
                        driver_id: this.selectedDriverId,
                        vehicle_id: this.activeVehicle.id,
                    }).then(res => {
                        $('#driver-assignment-modal').modal('hide');
                        this.toaster.successMessage('Driver assigned successfully.')
                        location.reload();
                    }).catch(error => {
                        this.toaster.errorMessage('An error was encountered. Please try again.')
                    })
                },
                assignTurnboy() {
                    if (!this.selectedTurnboyId) {
                        return this.toaster.errorMessage('Please select a turn boy.')
                    }

                    axios.post('/api/vehicles/assign-turnboy', {
                        driver_id: this.selectedTurnboyId,
                        vehicle_id: this.activeVehicle.id,
                    }).then(res => {
                        $('#turnboy-assignment-modal').modal('hide');
                        this.toaster.successMessage('Turn boy assigned successfully.')
                        location.reload();
                    }).catch(error => {
                        this.toaster.errorMessage('An error was encountered. Please try again.')
                    })
                },

                promptAssignDriver(vehicle) {
                    this.fetchAvailableDrivers()

                    this.activeVehicle = vehicle
                    $('#driver-assignment-modal').modal({
                        keyboard: false,
                        backdrop: 'static'
                    });

                    $('#driver-assignment-modal').modal('show');
                },
                promptAssignTurnboy(vehicle) {
                    this.fetchAvailableTurnboys()

                    this.activeVehicle = vehicle
                    $('#turnboy-assignment-modal').modal({
                        keyboard: false,
                        backdrop: 'static'
                    });

                    $('#turnboy-assignment-modal').modal('show');
                },
                promptUnAssignDriver(vehicle) {
                    this.activeVehicle = vehicle
                    $('#driver-unassignment-modal').modal({
                        keyboard: false,
                        backdrop: 'static'
                    });

                    $('#driver-unassignment-modal').modal('show');
                },
                promptUnAssignTurnboy(vehicle) {
                    this.activeVehicle = vehicle
                    $('#turnboy-unassignment-modal').modal({
                        keyboard: false,
                        backdrop: 'static'
                    });

                    $('#turnboy-unassignment-modal').modal('show');
                },

                unAssignDriver() {
                    axios.post('/api/vehicles/unassign-driver', {
                        vehicle_id: this.activeVehicle.id,
                    }).then(res => {
                        $('#driver-unassignment-modal').modal('hide');
                        this.toaster.successMessage('Driver unassigned successfully.')
                        location.reload();
                    }).catch(error => {
                        this.toaster.errorMessage(error.response.data?.message)
                    })
                },
                unAssignTurnboy() {
                    axios.post('/api/vehicles/unassign-turnboy', {
                        vehicle_id: this.activeVehicle.id,
                    }).then(res => {
                        $('#turnboy-unassignment-modal').modal('hide');
                        this.toaster.successMessage('Turn boy unassigned successfully.')
                        location.reload();
                    }).catch(error => {
                        this.toaster.errorMessage(error.response.data?.message)
                    })
                },

                promptSwitchOffVehicle(vehicle) {
                    this.activeVehicle = vehicle
                    $('#vehicle-switch-off-modal').modal({
                        keyboard: false,
                        backdrop: 'static'
                    });

                    $('#vehicle-switch-off-modal').modal('show');
                },

                switchOffVehicle() {
                    axios.post('/api/vehicles/switch-off', {
                        vehicle_id: this.activeVehicle.id,
                    }).then(res => {
                        $('#vehicle-switch-off-modal').modal('hide');
                        this.toaster.successMessage('Vehicle switch off successfully.')
                        location.reload();
                    }).catch(error => {
                        this.toaster.errorMessage(error.response.data?.message)
                    })
                },

                promptSwitchOnVehicle(vehicle) {
                    this.activeVehicle = vehicle
                    $('#vehicle-switch-on-modal').modal({
                        keyboard: false,
                        backdrop: 'static'
                    });

                    $('#vehicle-switch-on-modal').modal('show');
                },

                switchOnVehicle() {
                    axios.post('/api/vehicles/switch-on', {
                        vehicle_id: this.activeVehicle.id,
                    }).then(res => {
                        $('#vehicle-switch-on-modal').modal('hide');
                        this.toaster.successMessage('Vehicle switch on successfully.')
                        location.reload();
                    }).catch(error => {
                        this.toaster.errorMessage(error.response.data?.message)
                    })
                },

                fetchFuelHistory() {
                    axios.get('/api/vehicle-center/get-fuel-history', {
                        params: {
                            vehicle_id: this.vehicle.id
                        }
                    }).then(res => {
                        this.fuelHistory = res.data.data
                        this.fuelHistoryTotal = res.data.totals.fuelHistoryTotal
                        
                        if (this.table) {
                            this.table.destroy();
                        }
                        setTimeout(() => {
                           this.table = $('#fuel-history-table').DataTable({
                                'paging': true,
                                'lengthChange': true,
                                'searching': true,
                                'ordering': true,
                                'info': true,
                                'autoWidth': false,
                                'pageLength': 100,
                                'initComplete': function (settings, json) {
                                    let info = this.api().page.info();
                                    let total_record = info.recordsTotal;
                                },
                                'aoColumnDefs': [{
                                    'bSortable': false,
                                    'aTargets': 'noneedtoshort'
                                }],
                                //"aaSorting": [ [0,'desc'] ]

                            });
                        }, 100)
                    }).catch(() => {
                    })
                },

                // Select2 initialization methods
                initializeSelect2() {
                    console.log('Initializing Select2 for driver dropdown');
                    const $driverSelect = $('#driver_id');
                    
                    if ($driverSelect.length === 0) {
                        console.error('Driver select element not found');
                        return;
                    }
                    
                    // Safely destroy existing Select2 instance if it exists
                    if ($driverSelect.hasClass('select2-hidden-accessible')) {
                        console.log('Destroying existing Select2 instance');
                        $driverSelect.select2('destroy');
                    }
                    
                    // Initialize Select2
                    console.log('Creating new Select2 instance');
                    $driverSelect.select2({
                        placeholder: 'Select driver',
                        allowClear: true,
                        dropdownParent: $('#driver-assignment-modal')
                    }).on('change', (e) => {
                        console.log('Driver selected:', e.target.value);
                        this.selectedDriverId = e.target.value;
                    });
                    
                    console.log('Select2 initialized successfully');
                },

                initializeTurnboySelect2() {
                    // Safely destroy existing Select2 instance if it exists
                    if ($('#turnboy_id').hasClass('select2-hidden-accessible')) {
                        $('#turnboy_id').select2('destroy');
                    }
                    
                    // Initialize Select2
                    $('#turnboy_id').select2({
                        placeholder: 'Select turn boy',
                        allowClear: true
                    }).on('change', (e) => {
                        this.selectedTurnboyId = e.target.value;
                    });
                },
            },
        })

        app.mount('#vehicle-centre-page')
    </script>
    <script>
        (g => {
            let h, a, k, p = "The Google Maps JavaScript API",
                c = "google",
                l = "importLibrary",
                q = "__ib__",
                m = document,
                b = window;
            b = b[c] || (b[c] = {});
            let d = b.maps || (b.maps = {}),
                r = new Set,
                e = new URLSearchParams,
                u = () => h || (h = new Promise(async (f, n) => {
                    await (a = m.createElement("script"));
                    e.set("libraries", [...r] + "");
                    for (k in g) e.set(k.replace(/[A-Z]/g, t => "_" + t[0].toLowerCase()), g[k]);
                    e.set("callback", c + ".maps." + q);
                    a.src = `https://maps.${c}apis.com/maps/api/js?` + e;
                    d[q] = f;
                    a.onerror = () => h = n(Error(p + " could not load."));
                    a.nonce = m.querySelector("script[nonce]")?.nonce || "";
                    m.head.append(a)
                }));
            d[l] ? console.warn(p + " only loads once. Ignoring:", g) : d[l] = (f, ...n) => r.add(f) && u().then(() =>
                d[l](f, ...n))
        })({
            key: "{{ $googleMapsApiKey }}",
            v: "weekly",
        });
    </script>
    <script type="text/javascript">
        $(function() {
            $('body').addClass('sidebar-collapse');
            let map;
            let markers = [];
            let polylinePath = [];
            let polyline;

            //attempt playback implementation
            let playbackInterval;
            let playbackIndex = 0;
            let isPlaying = false;
            let vehicleMarker;


            function startPlayback() {
                
                console.log("startPlayback");
                if (!isPlaying) {
                    isPlaying = true;
                    //set  map center
                    map.setCenter({
                        lat: polylinePath[polylinePath.length-1].lat,
                        lng: polylinePath[polylinePath.length-1].lng,
                    });

                    playbackIndex = polylinePath.length - 1;
                    playbackInterval = setInterval(moveVehicle, 200); // Adjust interval as needed
                }
            }

            function pausePlayback() {
                clearInterval(playbackInterval);
                isPlaying = false;
            }

            function resumePlayback() {
                startPlayback();
            }

            function moveVehicle() {

                if (playbackIndex > 0) {
                    const newPosition = polylinePath[playbackIndex];
                    const nextPosition = polylinePath[playbackIndex - 1];

                    // moveMarker(newPosition);
                    // playbackIndex++;
                    if (nextPosition) {
                            const heading = computeHeading(newPosition, nextPosition);
                            moveMarker(newPosition, heading);
                        } else {
                            moveMarker(newPosition);
                        }

                        playbackIndex--;
                } else {
                    pausePlayback();
                }
            }

            function moveMarker(position, heading =  0) {
                console.log(position);
                if (vehicleMarker) {
                        vehicleMarker.position = new google.maps.LatLng(position.lat, position.lng);
                        vehicleMarker.content.style.transform = `rotate(${heading}deg)`;

                    }
            }
            function computeHeading(from, to) {
                    const fromLatLng = new google.maps.LatLng(from.lat, from.lng);
                    const toLatLng = new google.maps.LatLng(to.lat, to.lng);
                    return google.maps.geometry.spherical.computeHeading(fromLatLng, toLatLng);
                }

            // Event listeners for playback controls
            $('#play').on('click', startPlayback);
            $('#pause').on('click', pausePlayback);
            $('#resume').on('click', resumePlayback);




            function fetchLocations() {
                let date = $('#date').val();
                let to_date = $('#to_date').val();
                console.log(date);
                return $.ajax({
                    url: `/admin/vehicle-movement/get-movement/{{ $vehicle->device_name }}`,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        date: date,
                        to_date: to_date,
                    },
                    success: function(response) {
                        markers.forEach(marker => marker.setMap(null));
                        markers = [];
                        console.log(markers);
                        polylinePath = [];
                        if (polyline) {
                            polyline.setMap(null);
                        }
                        response.vehicleMovement.forEach((location, index) => {
                            let vehicle_name = location.device;

                            polylinePath.push({
                                lat: location.latitude,
                                lng: location.longitude,
                            });

                            if (index === 0) {
                                map.setCenter({
                                    lat: location.latitude,
                                    lng:location.longitude,
                                });
                            }
                            // if (index === response.vehicleMovement.length - 1) {
                                if (index === 0) {
                                    const vehicleName = location.device;
                                    const speed = location.speed;
                                    const ignition = location.ignition_status;
                                    const time = location.time;
                                    const fuel_level = location.fuel_level;
                                    const mileage = location.mileage;
                                    $('#vehicle-name').text(vehicleName);
                                    $('#speed').text(speed);
                                    $('#ignition').text(ignition);
                                    $('#time').text(time);
                                    $('#fuel').text(fuel_level);
                                    $('#mileage').text(mileage);

                                const icon = document.createElement("div");
                                icon.className = "price-tag";
                                // icon.innerHTML = `<img src="{{ asset('assets/admin/images/car-removebg-preview.png') }}" height="35px" width="30px "/>`;
                                icon.innerHTML = `<img src="{{ asset('assets/admin/images/lorry.png') }}" height="37px" width="20px" />`;
                                // icon.style.position = "absolute"; 
                                // icon.style.transform = "translate(-50%, -50%)"; 
                                

                                // const rotation = response.currentVehicleStatus.direction - 90;
                                const rotation = location.direction;

                                icon.style.transform = `rotate(${rotation}deg)`;


                                if (location.movement && location.speed > 8 && location.speed <=
                                    65) {
                                    icon.classList.add("blue-vehicle");
                                } else if (location.movement && location.speed > 65) {
                                    icon.classList.add("red-vehicle");
                                } else if (location.speed <= 8 && location.ignition_status ===
                                    'ON') {
                                    icon.classList.add("olive-vehicle");
                                } else if (location.ignition_status ===
                                    'OFF') {
                                    icon.classList.add("green-vehicle");
                                }
                                
                                vehicleMarker = new google.maps.marker.AdvancedMarkerElement({
                                    map: map,
                                    position: {
                                        lat: location.latitude,
                                        lng: location.longitude,
                                    },
                                    title: location.device,
                                    content: icon,
                                });
                                markers.push(vehicleMarker);

                            }
                            if (index === response.vehicleMovement.length - 1) {
                                const pin = new google.maps.marker.PinElement({
                                    background: "#027148",
                                    glyphColor: "white",
                                    borderColor: "#000000",

                                    });
                                const startMarker = new google.maps.marker.AdvancedMarkerElement({
                                    map: map,
                                    position: {
                                        lat: location.latitude,
                                        lng: location.longitude,
                                    },
                                    title: 'START',
                                    content: pin.element    ,
                                });
                                markers.push(startMarker);
                            }
                            drawCircle(location, map);


                        });
                        polyline = new google.maps.Polyline({
                            path: polylinePath,
                            geodesic: true,
                            strokeColor: '#FF0000',
                            strokeOpacity: 1.0,
                            strokeWeight: 2,
                        });

                        polyline.setMap(map);
                    }
                });
            }

            function drawCircle(location, map) {
                if (location.movement && location.speed > 8 && location.speed <=
                    65) {
                        //moving
                        strokeColor = '#0000ff';
                        fillColor = '#0000ff';
                } else if (location.movement && location.speed > 65) {
                    //overspeeding
                        strokeColor = '#ff0000';
                        fillColor = '#ff0000';
                } else if (location.speed <= 8 && location.ignition_status ===
                    'ON') {
                        //idling
                        strokeColor = '#808000';
                        fillColor = '#808000';
                } else if (location.ignition_status === 'OFF') {
                    //stationery
                        strokeColor = '#006400';
                        fillColor = '#006400';     
                }
                const icon = document.createElement("div");
                    icon.className = "price-tag";
                    icon.innerHTML = `<i class="fas fa-circle" style="color:${strokeColor}; font-size:10px;"></i>`;
                    icon.style.position = "absolute"; 
                    icon.style.transform = "translate(-50%, -50%)"; 

                    const marker = new google.maps.marker.AdvancedMarkerElement({
                        map: map,
                        position: {
                            lat: location.latitude,
                            lng: location.longitude,
                        },
                        title: `time:${location.time} speed:${location.speed}km/hr fuel:${location.fuel_level}lts mileage:${location.mileage}`,
                        content: icon,
                    });
                    // show tooltip on hover
                    google.maps.event.addListener(marker, 'click', function() {
                        console.log(marker)
                        });
                    marker.content.addEventListener('mouseout', function() {
                        console.log('removed');
                    });

                    markers.push(marker);   
                    

                // const drawncircle = new google.maps.Circle({
                //     strokeColor: strokeColor,
                //     strokeOpacity: 0.8,
                //     strokeWeight: 2,
                //     fillColor: fillColor,
                //     fillOpacity: 0.35,
                //     map,
                //     center: {
                //         lat: location.latitude,
                //         lng: location.longitude
                //     },
                //     radius: 10,
                // });
                // //show details on hovering over the circle
                // google.maps.event.addListener( drawncircle, 'mouseover', function(event) { 

                //   } );

            }

        // function showTooltip(position, title) {
        //     const tooltip = document.getElementById('tooltip');
        //     tooltip.style.display = 'block';
        //     tooltip.style.left = position.latLng.lng() + 'px';
        //     tooltip.style.top = position.latLng.lat() + 'px';
        //     tooltip.innerHTML = title;
        // }

        // function hideTooltip() {
        //     const tooltip = document.getElementById('tooltip');
        //     tooltip.style.display = 'none';
        // }
        function downloadReport() {
                let date = $('#date').val();
                let to_date = $('#to_date').val();
                console.log(date);
                return $.ajax({
                    url: `/admin/vehicle-movement/download-report/{{ $vehicle->device_name }}`,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        date: date,
                        to_date: to_date,
                    },
                    success: function(response) {
                        toaster = new Form();
                        console.log(response);
                        window.location.href = response.file; 
                        toaster.successMessage('Report Downloaded Successfully');
                    }
                });
            }

        $('#date').change(function() {  
            pausePlayback()
            fetchLocations();
         });
        $('#to_date').change(function() {  
            pausePlayback()
            fetchLocations();
        });
        $('#download-report-btn').click(function() {  
            event.preventDefault();
            let date = $('#date').val();
            let to_date = $('#to_date').val();
            link = `/admin/vehicle-movement/download-report/{{$vehicle->device_name}}/${date}/${to_date}`
            // console.log(link);
            $('#download-report').attr('href', link);
            console.log($('#download-report').attr('href'));
            setTimeout(function(){
                $('#download-report')[0].click();
            },1);

            console.log('clicked');
        });

            async function initMap() {
                const position = {
                    lat: -1.034444,
                    lng: 37.076805
                };
                const {
                    Map
                } = await google.maps.importLibrary("maps");
                const {
                    AdvancedMarkerElement, PinElement
                } = await google.maps.importLibrary("marker");

                map = new Map(document.getElementById("map-view"), {
                    zoom: 15,
                    center: position,
                    // mapId: "DEMO_MAP_ID",
                    mapId: "7c9bd9e078617725",
                    gestureHandling: "greedy",

                });
                const markers = new AdvancedMarkerElement({
                    map: map,
                    position: position,
                    title: "HQ",

                });
                fetchLocations();
                setInterval(fetchLocations, 50000);
            }

            initMap();

        });
       
    </script>
@endsection