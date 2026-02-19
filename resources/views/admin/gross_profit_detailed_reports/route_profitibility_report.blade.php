@extends('layouts.admin.admin')

@section('content')
    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="box box-primary">
            
            <div class="box-header with-border">
                <div class="d-flex justify-content-between">
                    <h3 class="box-title">Route Profitibility Report</h3>
                    {{-- <a href="{{ url()->previous() }}" class="btn btn-primary" role="button">
                        << Back to Sales and Receivables Reports </a> --}}
                </div>
            </div>

            <div class="box-header with-border no-padding-h-b">
                <div style="height: 150px ! important;">
                    <div class="card-header">
                        <i class="fa fa-filter"></i> Filter
                    </div>
                    <br>

                    @php
                        $selectedSalesman = request('salesman_id');
                        $selectedShifts = (array) request('shift_id', []);
                    @endphp
                    {!! Form::open(['route' => 'gross-profit.route-profitibility-report', 'method' => 'POST']) !!}
                    {{ csrf_field() }}
                    <div>
                        <div class="col-md-12 no-padding-h">

                            <div class="col-sm-3">
                                <div class="form-group">
                                    {!! Form::select('salesman_id', getAllsalesmanList(), $selectedSalesman, [
                                        'placeholder' => 'Select Salesman',
                                        'class' => 'form-control mlselect getshiftdata',
                                        'required' => true,
                                    ]) !!}
                                    <small class="text-muted">Choose a salesman to load their closed shifts.</small>
                                </div>
                            </div>

                            <div class="col-sm-5">
                                <div class="form-group">
                                    <select name="shift_id[]" class="form-control mlselec6t shiftList" multiple
                                        required data-placeholder="Select Shift(s)" data-selected='@json($selectedShifts)'
                                        @if (!$selectedSalesman) disabled @endif>
                                        <option value="">{{ $selectedSalesman ? 'Loading shifts...' : 'Select a salesman first' }}
                                        </option>
                                    </select>
                                    <small class="text-muted">Hold Ctrl/Cmd to select multiple shifts.</small>
                                </div>
                            </div>

                        </div>

                        <div class="col-md-12 no-padding-h">
                            <div class="col-sm-1">
                                <button type="submit" class="btn btn-warning" name="manage" value="pdf"><i
                                        class="fa fa-file-pdf"></i></button>
                            </div>
                            <div class="col-sm-1">
                                <button type="submit" class="btn btn-danger" name="manage" value="Filter">Filter</button>
                            </div>

                        </div>
                    </div>

                    </form>

                </div>

            </div>
        </div>

    </section>
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="box box-primary">
            <div class="box-header with-border">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th colspan="6"></th>
                        </tr>

                        <tr>
                            <th>Particular</th>
                            <th>Total Quantity</th>
                            <th>Total Selling Price</th>
                            <th>Total Cost</th>
                            <th>Gross Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $grandtotal = 0;
                            $totalQuantity = 0;
                            $grandprice = 0;
                            $grandprofit = 0;
                        @endphp
                        @foreach ($data as $key => $val)
                            <tr>
                                <td>{{ $val->title }}</td>
                                @php
                                    $total_cost = $val->standard_cost_sum;
                                    $totalQuantity += abs($val->total_quantity);
                                    $grandtotal += $total_cost;
                                    $grandprice += $val->price_sum;
                                    $grandprofit += $val->price_sum + $total_cost;
                                @endphp
                                <td>{{ manageAmountFormat(abs($val->total_quantity)) }}</td>
                                <td>{{ manageAmountFormat($val->price_sum) }}</td>
                                <td>{{ manageAmountFormat(abs($total_cost)) }}</td>
                                <td>{{ manageAmountFormat($val->price_sum + $total_cost) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <th>Total</th>

                            <th>{{ manageAmountFormat($totalQuantity) }}</th>
                            <th>{{ manageAmountFormat($grandprice) }}</th>
                            <th>{{ manageAmountFormat(abs($grandtotal)) }}</th>
                            <th>{{ manageAmountFormat($grandprofit) }}</th>
                        </tr>

                    </tbody>

                </table>
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
    <script type="text/javascript">
        $(function() {
            $(".mlselect").select2({ width: '100%' });
        });

        $(document).ready(function() {
            const $salesman = $('.getshiftdata');
            const $shiftList = $('.shiftList');
            const initiallySelected = $shiftList.data('selected') || [];
            const select2Config = { closeOnSelect: false, width: '100%' };

            function initialiseSelect2() {
                if ($shiftList.hasClass('select2-hidden-accessible')) {
                    $shiftList.select2('destroy');
                }
                $shiftList.select2(select2Config);
            }

            function renderOptions(options, selectedValues = []) {
                $shiftList.empty();

                if (!options.length) {
                    $shiftList.append('<option value="">No closed shifts found for this salesman</option>');
                    $shiftList.prop('disabled', true);
                } else {
                    options.forEach(function(option) {
                        const isSelected = selectedValues.includes(option.value);
                        $shiftList.append(`<option value="${option.value}" ${isSelected ? 'selected' : ''}>${option.label}</option>`);
                    });
                    $shiftList.prop('disabled', false);
                }

                initialiseSelect2();
            }

            function fetchShifts(salesmanId, selectedValues = []) {
                if (!salesmanId) {
                    $shiftList.prop('disabled', true)
                        .html('<option value="">Select a salesman first</option>');
                    initialiseSelect2();
                    return;
                }

                $.ajax({
                    url: "{{ route('gross-profit.get-salesman-shifts') }}",
                    type: "GET",
                    dataType: "json",
                    data: {
                        '_token': "{{ csrf_token() }}",
                        salesman_id: salesmanId
                    },
                    beforeSend: function() {
                        $shiftList.prop('disabled', true)
                            .html('<option value="">Loading shifts...</option>');
                        initialiseSelect2();
                    },
                    success: function(result) {
                        const options = Object.entries(result).map(function([value, label]) {
                            return { value, label };
                        });
                        renderOptions(options, selectedValues);
                    },
                    error: function() {
                        $shiftList.prop('disabled', true)
                            .html('<option value="">Error loading shifts</option>');
                        initialiseSelect2();
                    }
                });
            }

            $salesman.on('change', function() {
                const salesmanId = $(this).val();
                fetchShifts(salesmanId, []);
            });

            initialiseSelect2();

            const preselectedSalesman = $salesman.val();
            if (preselectedSalesman) {
                fetchShifts(preselectedSalesman, initiallySelected);
            }
        });
    </script>

    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>
@endsection
