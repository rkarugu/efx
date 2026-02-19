@php use Carbon\Carbon; @endphp
@extends('layouts.admin.admin')

@section('content')
    <script>
        window.schedule = {!! json_encode($schedule) !!};
    </script>

    <section class="content" id="vue-mount">
        <div class="box box-primary">
            <div class="box-header with-border">
                <div class="box-header-flex">
                    <h3 class="box-title" v-cloak> Delivery Center - @{{ schedule.delivery_number }} | @{{ schedule.route }} |
                        @{{ schedule.delivery_date }} </h3>
                    <a href="{{ url()->previous() }}" class="btn btn-success"> <i class="fas fa-arrow-left btn-icon"></i> Back
                    </a>
                </div>
            </div>

            <div class="box-body">
                <div class="row" v-cloak>
                    <div class="col-md-5">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th> <i class="fas fa-truck btn-icon"></i> Vehicle </th>
                                    <td> @{{ schedule.vehicle ?? 'Not Assigned' }} </td>
                                </tr>

                                <tr>
                                    <th> <i class="fas fa-user-secret btn-icon"></i> Driver </th>
                                    <td> @{{ schedule.driver ?? 'Not Assigned' }} </td>
                                </tr>

                                <tr>
                                    <th> <i class="fas fa-user-tie btn-icon"></i> Salesman </th>
                                    <td> @{{ schedule.salesman }} </td>
                                </tr>

                                <tr>
                                    <th> <i class="fas fa-spinner btn-icon"></i> Status </th>
                                    <td> @{{ schedule.status }} </td>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <div class="col-md-4">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th> <i class="fas fa-cash-register btn-icon"></i> Total Sales </th>
                                    <td> @{{ schedule.sales }} </td>
                                </tr>

                                <tr>
                                    <th> <i class="fas fa-boxes btn-icon"></i> Items </th>
                                    <td> @{{ schedule.items }} </td>
                                </tr>

                                <tr>
                                    <th> <i class="fas fa-weight-scale btn-icon"></i> Tonnage </th>
                                    <td> @{{ schedule.tonnage }}T </td>
                                </tr>

                                <tr>
                                    <th> <i class="fas fa-users btn-icon"></i> Customers </th>
                                    <td> @{{ schedule.customers }} </td>
                                </tr>
                            </thead>
                        </table>
                    </div>

                    <div class="col-md-3 d-flex align-items-center flex-column">
                        <span style="font-weight: bold; font-size: 20px;"> OVERALL PERFORMANCE </span>
                        <span v-if="performanceReport.length > 0" style="font-weight: bold; font-size: 50px;">
                            @{{ getPerformanceAverage() }}% </span>
                        <span v-else style="font-weight: bold; font-size: 50px;"> 0% </span>
                    </div>
                </div>


                <ul class="nav nav-tabs" id="data-tabs">
                    <li class="active">
                        <a href="#map-view" data-toggle="tab"> <i class="fas btn-icon fa-route"></i> Telematics
                        </a>
                    </li>

                    <li>
                        <a href="#loading-list" data-toggle="tab"> <i class="fas btn-icon fa-boxes"></i> Loading List </a>
                    </li>

                    <li>
                        <a href="#delivery-report" data-toggle="tab"> <i class="fas btn-icon fa-school-circle-check"></i>
                            Delivery Report
                        </a>
                    </li>

                    <li>
                        <a href="#performance-report" data-toggle="tab"> <i class="fas btn-icon fa-graduation-cap"></i>
                            Route Performance
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="map-view" v-cloak>
                        <div id="map" style="width: 100%; height: 700px;"></div>
                    </div>

                    <div class="tab-pane" id="loading-list" v-cloak>
                        <div class="box-body">
                            <table class="table table-bordered data-tables" id="loading-list-table">
                                <thead>
                                    <tr>
                                        <th style="width: 3%;">#</th>
                                        <th> Item Code </th>
                                        <th> Item Name </th>
                                        <th> Pack Size </th>
                                        <th style="text-align: right;"> Quantity </th>
                                        <th style="text-align: right;"> Tonnage </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(item, index) in loadingList" :key="index">
                                        <th style="width: 3%;">@{{ index + 1 }}</th>
                                        <td> @{{ item.stock_id_code }} </td>
                                        <td> @{{ item.item }} </td>
                                        <td> @{{ item.pack_size }} </td>
                                        <td style="text-align: right;"> @{{ item.quantity }} </td>
                                        <td style="text-align: right;"> @{{ item.tonnage }}T </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="delivery-report" v-cloak>
                        <div class="box-body">
                            <table class="table table-bordered data-tables" id="delivery-report-table">
                                <thead>
                                    <tr>
                                        <th style="width: 3%;">#</th>
                                        <th> Customer Name </th>
                                        <th> Order Number </th>
                                        <th> Order Status </th>
                                        <th> Delivery Time </th>
                                        <th style="text-align: right;"> Order Total </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(item, index) in deliveryReport" :key="index">
                                        <th style="width: 3%;">@{{ index + 1 }}</th>
                                        <td> @{{ item.customer }} </td>
                                        <td> @{{ item.order_no }} </td>
                                        <td> @{{ item.delivered ? 'Delivered' : 'Pending' }} </td>
                                        <td> @{{ item.delivery_time || '-' }} </td>
                                        <td style="text-align: right;"> @{{ item.total }} </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane" id="performance-report" v-cloak>
                        <div class="box-body">
                            <table class="table table-bordered data-tables" id="performance-report-table">
                                <thead>
                                    <tr>
                                        <th style="width: 3%;">#</th>
                                        <th> Parameter </th>
                                        <th style="text-align: right;"> Target </th>
                                        <th style="text-align: right;"> Actual </th>
                                        <th style="text-align: right;"> Variance </th>
                                        <th style="text-align: right;"> Performance </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(item, index) in performanceReport" :key="index"
                                        :style="{ backgroundColor: item.performance < 100 ? '#d0abab' : '#b2e9cb' }">
                                        <th style="width: 3%;">@{{ index + 1 }}</th>
                                        <td> @{{ item.parameter }} </td>
                                        <td style="text-align: right;"> @{{ item.target }} </td>
                                        <td style="text-align: right;"> @{{ item.actual }} </td>
                                        <td style="text-align: right;"> @{{ item.variance }} </td>
                                        <td style="text-align: right;"> @{{ item.performance }}% </td>
                                    </tr>
                                </tbody>

                                <tfoot>
                                    <tr style="border-top: 2px solid black; border-bottom: 2px solid black;">
                                        <th colspan="5" style="text-align: center;"> OVERALL PERFORMANCE </th>
                                        <th style="text-align: right;"> @{{ getPerformanceAverage() }}% </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <span class="btn-loader" style="display:none;">
        <img src="<?= asset('/assets/admin/images/loader.gif') ?>" alt="Loader" />
    </span>
@endsection

@section('uniquepagestyle')
    {{-- <link href="{{ asset('assets/admin/bower_components/select2/dist/css/select2.min.css') }}" rel="stylesheet"/> --}}
@endsection

@section('uniquepagescript')
    <script src="{{ asset('js/axios.min.js') }}"></script>
    {{-- <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script> --}}
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
    <script type="importmap">
        {
          "imports": {
            "vue": "/js/vue.esm-browser.js"
          }
        }
    </script>

    <script type="module">
        import {
            createApp
        } from 'vue';

        const app = createApp({
            data() {
                return {
                    loadingList: [],
                    loadingLoading: false,
                    deliveryReport: [],
                    performanceReport: [],
                    routeHistory: [],
                }
            },

            mounted() {
                // $(".mlselect").select2();

                $("body").addClass('sidebar-collapse');

                this.initMap();
            },

            computed: {
                schedule() {
                    return window.schedule
                },
            },

            created() {
                this.fetchLoadingList();
                this.fetchDeliveryReport();
                this.fetchPerformanceReport();
                this.fetchRouteHistory();
            },

            methods: {
                async initMap() {
                    const {
                        Map
                    } = await google.maps.importLibrary("maps");
                    await google.maps.importLibrary("geometry");
                    const {
                        AdvancedMarkerElement,
                        PinElement
                    } = await google.maps.importLibrary("marker");
                    const {
                        InfoWindow
                    } = await google.maps.importLibrary("maps");

                    this.map = new Map(document.getElementById("map"), {
                        center: {
                            lat: this.schedule.start_lat,
                            lng: this.schedule.start_lng
                        },
                        zoom: 13,
                        mapId: "355bd45b5b2fb544",
                        gestureHandling: "greedy",
                    });

                    const icon = document.createElement("div");
                    icon.innerHTML = '<i class="fas fa-warehouse fa-success"></i>';
                    const pinScaled = new PinElement({
                        scale: 1.5,
                        background: "#fff",
                        borderColor: "#137333",
                        glyph: icon,
                    });

                    const marker = new AdvancedMarkerElement({
                        position: new google.maps.LatLng(this.schedule.start_lat, this.schedule
                            .start_lng),
                        content: pinScaled.element,
                        title: 'Loading Location',
                    });
                    marker.map = this.map;

                    // const infoWindow = new InfoWindow();
                    // infoWindow.setContent('Home');
                    // infoWindow.open(marker.map, marker);
                },

                fetchLoadingList() {
                    this.loadingLoading = true;
                    axios.get('{{ route('delivery-center.loading-list') }}', {
                        params: {
                            id: this.schedule.id
                        }
                    }).then(response => {
                        this.loadingLoading = false;
                        this.loadingList = response.data;

                        setTimeout(() => {
                            $('#loading-list-table').DataTable({
                                'paging': true,
                                'lengthChange': true,
                                'searching': true,
                                'ordering': true,
                                'info': true,
                                'autoWidth': false,
                                'pageLength': 100,
                                'initComplete': function(settings, json) {
                                    // let info = this.api().page.info();
                                    // let total_record = info.recordsTotal;
                                    // if (total_record < 101) {
                                    //     $('.dataTables_paginate').hide();
                                    // }
                                },
                                'aoColumnDefs': [{
                                    'bSortable': false,
                                    'aTargets': 'noneedtoshort'
                                }],
                            });
                        }, 500)
                    }).catch(error => {
                        this.loadingLoading = false;
                        // this.toaster.errorMessage(error.response?.data?.message ?? error.response?.data);
                    });
                },

                fetchDeliveryReport() {
                    axios.get('{{ route('delivery-center.delivery-report') }}', {
                        params: {
                            id: this.schedule.id
                        }
                    }).then(response => {
                        this.deliveryReport = response.data;

                        setTimeout(() => {
                            $('#delivery-report-table').DataTable({
                                'paging': true,
                                'lengthChange': true,
                                'searching': true,
                                'ordering': true,
                                'info': true,
                                'autoWidth': false,
                                'pageLength': 100,
                                'initComplete': function(settings, json) {
                                    // let info = this.api().page.info();
                                    // let total_record = info.recordsTotal;
                                    // if (total_record < 101) {
                                    //     $('.dataTables_paginate').hide();
                                    // }
                                },
                                'aoColumnDefs': [{
                                    'bSortable': false,
                                    'aTargets': 'noneedtoshort'
                                }],
                            });
                        }, 500)
                    }).catch(error => {
                        // this.toaster.errorMessage(error.response?.data?.message ?? error.response?.data);
                    });
                },


                getColumnTotal(column) {
                    return this.records.reduce((partialSum, record) => partialSum + record[column], 0);
                },

                fetchPerformanceReport() {
                    axios.get('{{ route('delivery-center.performance') }}', {
                        params: {
                            id: this.schedule.id
                        }
                    }).then(response => {
                        this.performanceReport = response.data;
                    }).catch(error => {
                        // this.toaster.errorMessage(error.response?.data?.message ?? error.response?.data);
                    });
                },

                getPerformanceAverage() {
                    let total = this.performanceReport.reduce((partialSum, record) => partialSum + record
                        .performance, 0);
                    return (total / this.performanceReport.length).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                },

                fetchRouteHistory() {
                    axios.get('{{ route('delivery-center.polylines') }}', {
                        params: {
                            id: this.schedule.id
                        }
                    }).then(response => {
                        this.routeHistory = response.data;
                        this.drawRoute();
                    }).catch(error => {
                        // this.toaster.errorMessage(error.response?.data?.message ?? error.response?.data);
                    });
                },

                drawRoute() {
                    const preTrip = new google.maps.Polyline({
                        path: this.routeHistory.pre_trip,
                        geodesic: true,
                        strokeColor: "#FF0000",
                        strokeOpacity: 1.0,
                        strokeWeight: 2,
                    });

                    const firstTrip = new google.maps.Polyline({
                        path: this.routeHistory.first_trip,
                        geodesic: true,
                        strokeColor: "#0000FF",
                        strokeOpacity: 1.0,
                        strokeWeight: 2,
                    });

                    const deliveryTrip = new google.maps.Polyline({
                        path: this.routeHistory.delivery_trip,
                        geodesic: true,
                        strokeColor: "#088F8F",
                        strokeOpacity: 1.0,
                        strokeWeight: 2,
                    });

                    const returnTrip = new google.maps.Polyline({
                        path: this.routeHistory.return_trip,
                        geodesic: true,
                        strokeColor: "#000000",
                        strokeOpacity: 1.0,
                        strokeWeight: 2,
                    });

                    preTrip.setMap(this.map);
                    firstTrip.setMap(this.map);
                    deliveryTrip.setMap(this.map);
                    returnTrip.setMap(this.map);
                }
            },
        })

        app.mount('#vue-mount')
    </script>
@endsection
