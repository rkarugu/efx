@extends('layouts.admin.admin')
@section('content')
<section class="content">    
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="box-title"> {!! $title !!} </h3>
                <div>
                    <a href="{{ route('pos-cash-sales.index') }}" class="btn btn-success btn-sm"> <i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
         @include('message')
            <div class = "row">
                <div class = "col-sm-4">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="">Date</label>
                                <span class="form-control">{{$data->date}}</span>
                            </div>
                        </div>
                </div>

                <div class = "col-sm-4">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Time</label>
                            <span class="form-control">{{$data->time}}</span>
                        </div>
                    </div>
                </div>

                <div class = "col-sm-4">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">User</label>
                            <span class="form-control">{{@$data->user->name}}</span>
                        </div>
                    </div>
                </div>
            </div>       
            <div class = "row">
                <div class = "col-sm-3">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Customer</label>
                            <span class="form-control">{{$data->customer}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Payment Method</label>                                  
                            <span class="form-control">{{@$data->payment->title}}</span>
                        </div>
                    </div>
                </div>
                
                <div class = "col-sm-3">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Cash</label>
                            <span class="form-control">{{$data->cash}}</span>
                        </div>
                    </div>
                </div>

                <div class = "col-sm-3">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Change</label>
                            <span class="form-control">{{$data->change}}</span>
                        </div>
                    </div>
                </div>
            </div>  
    </div>
</section>
       <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="box box-primary">
                        <div class="box-header with-border no-padding-h-b">
                             
                          
                            <div class="col-md-12 no-padding-h ">
                           <h3 class="box-title"> Cash Sales</h3>

                            <form action="{{route('pos-cash-sales.return_items_post',base64_encode($data->id))}}" method="POST" class = "addExpense">
                                {{ csrf_field() }}
                                <input type="hidden" name="id" value="{{$data->id}}">                               
                                <table class="table table-bordered table-hover" id="mainItemTable">
                                    <thead>
                                    <tr>
                                      <th>Item</th>
                                      <th>Description</th>
                                      <th style="width: 90px;">Unit</th>
                                      <th style="width: 90px;">QTY</th>
                                      <th>Selling Price</th>
                                      <th>Location</th>
                                      <th style="width: 90px;">Discount</th>
                                      <th>VAT</th>
                                      <th>Total</th>
                                      <th>Dispatch Details</th>
                                      <th>Return Reason</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $totalprice = $discount = $totalvat = $total =0;
                                    @endphp
                                    @foreach ($data->items as $item)
                                        @if ($item->selling_price == 0)
                                            @continue
                                        @endif

                                    @php
                                        $totalprice += $item->selling_price;
                                        $discount += $item->discount_amount;
                                        $totalvat += $item->vat_amount;
                                        $total +=$item->total;
                                        $pending_rtn = $item->returnItems ->where('accepted', true)->sum('return_quantity');
                                        $accepted_rtn = $item->returnItems ->where('accepted', true)->sum('return_quantity');
                                        $gross_rtn = $pending_rtn + $accepted_rtn;
                                        $returnable_qty = $item->qty - $gross_rtn + $accepted_rtn;
                                    @endphp

                                        @if($returnable_qty == 0)
                                            @continue
                                        @endif
                                       <tr>
                                           <td>
                                                <div class="checkbox" style="margin-top: 0;margin-bottom: 0;">
                                                    <label>
                                                        <input class="checked_return_items" type="checkbox" name="item[{{$item->id}}]" value="{{$item->id}}" onchange="enableQuantity(this,'#quantity-{{$item->id}}')">{{@$item->item->stock_id_code}}
                                                    </label>
                                                </div>
                                            </td>
                                           <td>{{@$item->item->description}}</td>
                                           <td>{{@$item->item->pack_size->title}}</td>
                                           <td>
                                            <div class="form-group">

                                              <input type="text" name="quantity[{{$item->id}}]" max="{{$returnable_qty}}" readonly id="quantity-{{$item->id}}" class="form-control" value="{{$returnable_qty}}">
                                            </div>   
                                            </td>
                                           <td>{{$item->selling_price}}</td>
                                           <td>{{@$item->item->getBinData(getLoggeduserProfile()->wa_location_and_store_id)->title}}</td>
                                           <td>{{$item->discount_amount}}</td>
                                           <td>{{$item->vat_amount}}</td>
                                           <td>{{$item->total}}</td>
                                           <td>
                                            @if (@$item->dispatch -> status  == 'dispatched')
                                                <span>Dispatched By: {{@$item->dispatch->dispatch_by->name}}</span><br>
                                                <span>Date: {{date('d-M-Y',strtotime($item ->dispatch->dispatched_time))}}</span><br>
                                                <span>Disp No: {{$item ->dispatch ->desp_no}}</span>
                                            @endif
                                           </td>
                                           <td>
                                               <select class="form-control mlselect2" name="reason[{{ $item ->id}}]">
                                                   <option value="">Select reason</option>
                                                   @foreach($reasons as $reason)
                                                       <option value="{{ $reason->id }}">{{ $reason->reason }}</option>
                                                   @endforeach
                                               </select>
                                           </td>
                                       </tr>
                                    @endforeach


                                    </tbody>
                                    <tfoot>
                                      <tr>
                                        <th colspan="9" style="text-align:right">
                                        Total Price
                                        </th>
                                        <td colspan="2">KES <span id="total_exclusive">{{$totalprice}}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="9" style="text-align:right">
                                        Discount
                                        </th>
                                        <td colspan="2">KES <span id="total_discount">{{$discount}}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="9" style="text-align:right">
                                        Total VAT		
                                        </th>
                                        <td colspan="2">KES <span id="total_vat">{{$totalvat}}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="9" style="text-align:right">
                                        Total
                                        </th>
                                        <td colspan="2">KES <span id="total_total">{{ number_format($total,2) }}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="11" style="text-align:left">
                                            <button type="submit" class="btn return_btn  btn-success btn-sm">Return</button>
                                        </th>
                                      </tr>
                                    </tfoot>
                                </table>
                            </form>
                            </div>
                       


                        </div>
                    </div>
    </section>
@endsection


@section('uniquepagestyle')
<link href="{{asset('assets/admin/bower_components/select2/dist/css/select2.min.css')}}" rel="stylesheet" />
<link rel="stylesheet" href="{{asset('assets/admin/dist/datepicker.css')}}">

<style type="text/css">
   .select2{
     width: 100% !important;
    }
    #note{
      height: 60px !important;
    }
    .align_float_right
    {
      text-align:  right;
    }
    .textData table tr:hover{
      background:#000 !important;
      color:white !important;
      cursor: pointer !important;
    }


/* ALL LOADERS */

.loader{
  width: 100px;
  height: 100px;
  border-radius: 100%;
  position: relative;
  margin: 0 auto;
  top: 35%;
}

/* LOADER 1 */

#loader-1:before, #loader-1:after{
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 100%;
  border: 10px solid transparent;
  border-top-color: #3498db;
}

#loader-1:before{
  z-index: 100;
  animation: spin 1s infinite;
}

#loader-1:after{
  border: 10px solid #ccc;
}

@keyframes spin{
  0%{
    -webkit-transform: rotate(0deg);
    -ms-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }

  100%{
    -webkit-transform: rotate(360deg);
    -ms-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}

    </style>
@endsection

@section('uniquepagescript')
<div id="loader-on" style="
position: absolute;
top: 0;
text-align: center;
display: block;
z-index: 999999;
width: 100%;
height: 100%;
background: #000000b8;
display:none;
">
  <div class="loader" id="loader-1"></div>
</div>
<script src="{{asset('js/sweetalert.js')}}"></script>
<script src="{{asset('js/form.js')}}"></script>
<script src="{{asset('assets/admin/bower_components/select2/dist/js/select2.full.min.js')}}"></script>
<script type="text/javascript">
    $(function () {

        $(".mlselect2").select2();
    });
</script>
  <script type="text/javascript">
  
function enableQuantity(pro,param) {
    if($(pro).is(':checked')){
        $(param).removeAttr('readonly');
    }else{
        $(param).attr('readonly',true);
    }
  }
var form = new Form();
$(document).on('submit','.addExpense',function(e){
    e.preventDefault();
    var is_checked = 0;
    $('.checked_return_items').each(function () {
        if($(this).prop('checked')==true){
          is_checked=1;  
        }
    });
    
    if(is_checked==true){

        $('.return_btn').attr('disabled',true);

        $('#loader-on').show();
        var postData = new FormData($(this)[0]);
        var url = $(this).attr('action');
        postData.append('_token',$(document).find('input[name="_token"]').val());
        $.ajax({
            url:url,
            data:postData,
            contentType: false,
            cache: false,
            processData: false,
            method:'POST',
            success:function (out) {
                $('#loader-on').hide();

                $(".remove_error").remove();
                if (out.result == 0) {
                    for (let i in out.errors) {
                        var id = i.split(".");
                        if (id && id[1]) {
                            $("[name='" + id[0] + "[" + id[1] + "]']").parent().append('<label class="error d-block remove_error w-100" id="' + i + '_error">' + out.errors[i][0] + '</label>');
                        } else {
                            $("[name='" + i + "']").parent().append('<label class="error d-block remove_error w-100" id="' + i + '_error">' + out.errors[i][0] + '</label>');
                            $("." + i).parent().append('<label class="error d-block remove_error w-100" id="' + i + '_error">' + out.errors[i][0] + '</label>');
                        }
                    }
                }
                if (out.result === 1) {
                    form.successMessage(out.message);
                    
                    // Only print if there's a receipt URL
                    if (out.receipt_url) {
                        var url = out.receipt_url;
                        print_this(url);
                    }

                    if (out.location) {
                        setTimeout(() => {
                            location.href = out.location;
                        }, 1000);
                    }
                }
                if (out.result === -1) {
                    form.errorMessage(out.message);
                }
            },
            
            error:function(err)
            {
              $('#loader-on').hide();
                $(".remove_error").remove();
                form.errorMessage('Something went wrong');                          
            }
        });
    }else{
        $('.return_btn').attr('disabled',false);
        alert('Please select any item first!')
    }


    function printReceipt(out) {
        // console.log(slug)


        jQuery.ajax({
            url: out.receipt_url,
            type: 'GET',
            async: false,   //NOTE THIS
            headers: {
                'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                var divContents = response;
                var printWindow = window.open('', '', 'width=600');
                printWindow.document.write(divContents);
                printWindow.document.close();
                printWindow.print();
                printWindow.close();
                location.href = out.location;

            },
            error: function (err){
                location.href = out.location;
            }
        });

    }
});



</script>
<script src="{{asset('assets/admin/dist/bootstrap-datepicker.js')}}"></script>
    <script>
        $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
        });
    </script>
@endsection


