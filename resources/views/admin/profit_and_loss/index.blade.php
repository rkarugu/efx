
@extends('layouts.admin.admin')

@section('content')
<style>
.buttons-excel{
    background-color: #f39c12 !important;
    border-color: #e08e0b !important;
    border-radius: 3px !important;
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
    border: 1px solid transparent !important;
    color: #fff !important;
    display: inline-block !important;
    padding: 6px 12px !important;
    margin-bottom: 0 !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    line-height: 1.42857143 !important;
    text-align: center !important;
    white-space: nowrap !important;
    vertical-align: middle !important;
}
.dt-buttons{
width: 10% !important;
    position: relative !important;
    left: 90px !important;
    top: -42px !important;
}
.dataTables_wrapper table th, td {
    border-right:none !important;
}
</style>

<!-- Main content -->
<section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="box box-primary">
        <div class="box-header with-border no-padding-h-b">
            <div  style="height: 150px ! important;"> 
                <div class="card-header">
                </div>

                <div>
                    <div class="col-md-12 no-padding-h">
                        <div  style="height: 150px ! important;"> 
                            <div class="card-header">
                            <i class="fa fa-filter"></i> Filter
                            </div><br>
                        {!! Form::open(['route' => 'profit-and-loss.index','method'=>'get']) !!}

                            <div>
                            <div class="col-md-12 no-padding-h">
                            <div class="col-sm-3">
                            <div class="form-group">
                            {!! Form::text('start-date', null, [
                            'class'=>'datepicker form-control',
                            'placeholder'=>'Start Date' ,'readonly'=>true]) !!}
                            </div>
                            </div>

                            <div class="col-sm-3">
                            <div class="form-group">
                            {!! Form::text('end-date', null, [
                            'class'=>'datepicker form-control',
                            'placeholder'=>'End Date','readonly'=>true]) !!}
                            </div>
                            </div>

                            <div class="col-sm-3">
                            <div class="form-group">
                            {!! Form::select('restaurant',$restroList, null,['placeholder'=>"Select Branch",'class'=>'form-control']) !!}
                            </div>
                            </div>
                           
                            </div>

                            <div class="col-md-12 no-padding-h">
                                 <div class="col-sm-1"><button type="submit" class="btn btn-success" name="manage-request" value="filter"  >Filter</button></div>
                                {{-- <div class="col-sm-1">
                                <button title="Export In Excel" type="submit" class="btn btn-warning" name="manage-request" value="xls"  ><i class="fa fa-file-excel" aria-hidden="true"></i>
                                </button>
                                </div>

                                <div class="col-sm-1">
                                <button title="Export In PDF" type="submit" class="btn btn-warning" name="manage-request" value="pdf"  ><i class="fa fa-file-pdf" aria-hidden="true"></i>
                                </button>
                                </div>

                                <div class="col-sm-1">
                                <a class="btn btn-info" href="{!! route('trial-balances.index').getReportDefaultFilterForTrialBalance() !!}"  >Clear</a>
                                </div> --}}
                                

                                
                            </div>
                            </div>

                            </form>
        
                        
                            </div>


<table class="table table-responsive" style="border:1px solid #ddd; margin-top:40px;" id="create_datatable1">

@if(count($lists) > 0)
    <thead>
        <tr>
            <th>{{@$restuarantname}}</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th>Profit & Loss Report</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>
     <tbody>
             @php 
                $grandtotalcost = 0;
			 $a = 0;
             $b = 0;
             $c = 0;
             $d = 0;
             @endphp
            @foreach($lists as $key => $val)
            @if(count($val['get_wa_account_group']) > 0)
            <tr>
                <th width="15%">{{$val['section_name']}}</th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
             @php 
             $totalqty = 0;
             $totalcost = 0;
             @endphp

			@foreach($val['get_wa_account_group'] as $key => $groupacount)
            
				@foreach($groupacount['get_chart_account'] as $key => $value)
					@php 
					$totalqty += 0;
					$totalcost += abs($value['amount']);
					@endphp
					@if(abs($value['amount']) > 0)
					<tr>
						<th width="2%"></th>
						<td ><a target="_blank" href="{{route($model.'.gl-entries', [$value['account_code']])}}?to={{request()->get('start-date')}}&from={{request()->get('end-date')}}">{{$value['account_name']}}</a></td>
						<th></th>
						<th></th>
						<th></th>
						<td>{{ number_format(abs(abs($value['amount'])),2) }}</td>
					</tr>
					@endif
				@endforeach
             @endforeach
                
			@if($val['section_name']=="INCOME")
             @php 
             $a += $totalcost;
             @endphp
			@endif
			
			@if($val['section_name']=="COST OF SALES")
             @php 
             $b += $totalcost;
             @endphp
			@endif
			
			@if($val['section_name']=="OVERHEADS")
             @php 
             $d += $totalcost;
             @endphp
			@endif
             @php 
             $grandtotalcost += $totalcost;
             @endphp
			<tr>
				<th width="2%"></th>
                <th>Total {{$val['section_name']}} </th>
                <th></th>
                <th></th>
                <th></th>
                <th>{{number_format(abs($totalcost),2)}}</th>
                <th></th>
            </tr>
                @php
                $c = $a-$b;
                @endphp
                
			@if($val['section_name']=="COST OF SALES")
			<tr style="background:#ddd;">
                <th>GROSS PROFIT </th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>{{number_format($c,2)}}</th>
                <th></th>
            </tr>
            @endif
			@if($val['section_name']=="OVERHEADS")
			@php
				$e = $c-$d;
			@endphp
			<tr style="background:#ddd;">
                <th>NET PROFIT </th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th>{{number_format($e,2)}}</th>
                <th></th>
            </tr>
            @endif
                <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                </tr>
                @endif
            @endforeach
            <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>            

      </tbody>
        

    @endif
    </table>       

                            
                        


                    </div>


                </div>
                </div>

                </form>
            </div>

            <br>



        </div>
    </div>
</section>



@endsection


@section('uniquepagestyle')
 <link rel="stylesheet" href="{{asset('assets/admin/dist/datepicker.css')}}">
<link href="{{asset('assets/admin/bower_components/select2/dist/css/select2.min.css')}}" rel="stylesheet" />
@endsection

@section('uniquepagescript')
<script src="{{asset('assets/admin/dist/bootstrap-datepicker.js')}}"></script>
<script src="{{asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js')}}"></script>
<script type="text/javascript">
    $(function () {
        $(".mlselec6t").select2();
    });

                 $('.datepicker').datepicker({
                  format: 'yyyy-mm-dd',
                  minuteStep:1,
                 });
</script>


	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.flash.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.print.min.js"></script>
	<script type="text/javascript" class="init">
	


$(document).ready(function() {
	$('#create_datatable1').DataTable( {
        searching: false,
        "paging":   false,
        "ordering": false,
        "info":     false,
		dom: 'Bfrtip',
		buttons: [
			{ extend: 'excelHtml5', text: '<i class="fa fa-file-excel" aria-hidden="true">', footer: true },
		]
	} );
} );
            </script>
@endsection
