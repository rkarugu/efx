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
                        <h3 class="box-title">Detailed Stock Count Variance Report  </h3>
                    </div>
                </div>

                <div class="box-body">
                    {!! Form::open(['route' => 'admin.stock-count-variance.index', 'method' => 'get']) !!}
                    <div class="row">
                        @if ($logged_user_info->role_id == 1 ||  $logged_user_info->role_id == 147 || $logged_user_info->role_id == 176 )

                            <div class="col-md-3 form-group">
                                <select name="branch" id="branch" class="mlselect form-control" data-url="{{ route('admin.get-branch-uoms') }}">
                                    <option value="" selected disabled>Select branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{$branch->id}}" {{ $branch->id == request()->branch ? 'selected' : '' }}>{{$branch->location_name}}</option>

                                    @endforeach
                                </select>

                            </div>
                        @endif
                    @if(isset($user->role_id) && $user->role_id != 152)

                        <div class="col-md-2 form-group">
                            <select name="uom" id="uom" class="mlselect form-control">
                                <option value="" selected disabled>Select Bin</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{$uom->id}}" {{ $uom->id == request()->uom ? 'selected' : '' }}>{{$uom->title}}</option>

                                @endforeach
                            </select>

                        </div>
                        @endif
                    
                        <div class="col-md-2 form-group">
                            <input type="date" name="start_date" id="from" class="form-control" value="{{ request()->get('start_date') ?? \Carbon\Carbon::now()->toDateString() }}">
                        </div>

                        <div class="col-md-3 form-group">
                            <button type="submit" class="btn btn-success" name="manage-request" value="filter">Filter</button>
                            <input type="submit" class = "btn btn-success" name="type" value="Download">
                            <button onClick="printgrn()" class="btn btn-success">Print</button>
                            <a class="btn btn-success" href="{!! route('admin.stock-count-variance.index') !!}">Clear </a>
                        </div>
                    </div>

                    {!! Form::close(); !!}

                    <hr>

                    @include('message')


                    <div class="col-md-12">
                            <table class="table table-bordered table-hover" id="create_datatable">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Stock Id Code</th>
                                    <th>Title</th>
                                    <th>Bin</th>
                                    <th>EFX</th>
                                    <th>Physical</th>
                                    <th>Excess</th>
                                    <th>Excess value</th>
                                    <th>Missing </th>
                                    <th>Missing Value</th>
                                    <th>Reference</th>
                                    <th>Action</th>
                                
                                </tr>
                        
                                </thead>
                                <tbody>
                                    @php
                                        $excessTotal = $missingTotal = 0;
                                    @endphp
                                    @foreach ($data as $row)
                                    <tr>
                                        <th>{{$loop->index+1}}</th>
                                        <td>{{$row?->getInventoryItemDetail?->stock_id_code}}</td>
                                        <td>{{$row?->getInventoryItemDetail?->title}}</td>
                                        <td>{{$row->getUomDetail->title}}</td>
                                        <td style="text-align: center;">{{$row->current_qoh}}</td>
                                        <td style="text-align: center;">{{$row->quantity_recorded ?? 'NCE'}}</td>
                                        @if (isset($row->variation) && $row->variation >= 0)
                                        <td style="text-align: center;">{{$row->variation}}</td>
                                        @else
                                        <td style="text-align: center;">-</td>
                                        @endif
                                        @if (isset($row->variation) && $row->variation >= 0)
                                        <td style="text-align: right;">{{manageAmountFormat($row->variation * $row->getInventoryItemDetail?->selling_price)}}</td>
                                        @else
                                        <td style="text-align: center;">-</td>
                                        @endif     
                                        @if (isset($row->variation) && $row->variation <= 0)
                                        <td style="text-align: center;">{{$row->variation}}</td>
                                        @else
                                        <td style="text-align: center;">-</td>
                                        @endif
                                        @if (isset($row->variation) && $row->variation <= 0)
                                        <td style="text-align: right;">{{manageAmountFormat($row->variation * $row->getInventoryItemDetail?->selling_price * -1)}}</td>
                                        @else
                                        <td style="text-align: center;">-</td>
                                        @endif 
                                        <td>{{$row->reference}}</td> 
                                        @if ($row->stockDebtorItem)
                                        @php
                                            $documentNo = $row->stockDebtorItem?->document_no;
                                            $debtorId = $row->stockDebtorItem?->stock_debtor_trans_id;
                                        @endphp
                                    
                                        @if (strpos($documentNo, "SAS") !== false)
                                            @php
                                                $link = route('stock-processing.sales.show', $debtorId);
                                            @endphp
                                        @elseif (strpos($documentNo, "SAR") !== false)
                                            @php
                                                $link = route('stock-processing.return.show', $debtorId);
                                            @endphp
                                        @endif
                                    
                                        <td>
                                            <a href="{{ $link }}" title="view" target="_blank">{{ $documentNo }}</a>
                                        </td>
                                    @else
                                        <td>Not Charged</td>
                                    @endif

                                    </tr>
                                    @if (isset($row->variation) && $row->variation >= 0)
                                        @php
                                            $excessTotal += ($row->variation * $row->getInventoryItemDetail?->selling_price)
                                        @endphp
                                    @endif
                                    @if (isset($row->variation) && $row->variation <= 0)
                                    @php
                                        $missingTotal += ($row->variation * $row->getInventoryItemDetail?->selling_price * -1)

                                    @endphp
                                @endif
                                        
                                    @endforeach
                                
                                </tbody> 
                            
                                <tfoot>
                                    <tr>
                                        <th colspan="7" style="text-align: left;">Totals</th>
                                        <th style="text-align: right;">{{manageAmountFormat($excessTotal)}}</th>
                                        <th></th>
                                        <th style="text-align: right;">{{manageAmountFormat($missingTotal)}}</th>
                                        <th></th>
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
    @endsection

    @section('uniquepagescript')
        <script src="{{ asset('assets/admin/dist/bootstrap-datepicker.js') }}"></script>
        <script src="{{ asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js') }}"></script>
    
        <script type="text/javascript">
            function printgrn() {
                    var confirm_text = 'report';
                    var isconfirmed = confirm("Do you want to print " + confirm_text + "?");
                let branch = $('#branch').val();
                let uom = $('#uom').val();
                let start_date = $('#from').val();
                if (isconfirmed) {
                    jQuery.ajax({
                        url: '{{ route('admin.stock-count-variance.print') }}',
                        // async: true, //NOTE THIS
                        type: 'GET',
                        data: {
                            branch: branch,
                            uom: uom,
                            start_date: start_date,
                        },
                        headers: {
                            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('response');
                            var divContents = response;
                            var printWindow = window.open('', '', 'width=600');
                            printWindow.document.write(divContents);
                            printWindow.document.close();
                            printWindow.print();
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', status, error);
                        }
                    
                    });
                }
            }
        </script>
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
        <script>
            $(document).ready(function() {
                $('#branch').change(function() {
                    var branchId = $(this).val();
                    var url = $(this).data('url');
        
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: { branch_id: branchId },
                        success: function(data) {
                            console.log(data);
                            $('#uom').empty();
                            $('#uom').append('<option value="" selected disabled>Select Bin</option>');
        
                            $.each(data.uoms, function(key, value) {
                                $('#uom').append('<option value="' + value.id + '">' + value.title + '</option>');
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
