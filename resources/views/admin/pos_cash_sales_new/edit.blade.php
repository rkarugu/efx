@extends('layouts.admin.admin')
@section('content')
<form method="POST" action="{{ route($model.'.update',$data->id) }}" accept-charset="UTF-8" class="" onsubmit="return false;" enctype="multipart/form-data" >
  <a href="{{ route($model.'.index') }}" class="btn btn-primary">Back</a>
  <br>
<section class="content">    
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"> {!! $title !!} </h3></div>
         @include('message')
     
            {{ csrf_field() }}
            {{method_field('PUT')}}
            <input type="hidden" name="id" value="{{$data->id}}">
             <?php 
             $getLoggeduserProfile = getLoggeduserProfile();
               
                    $purchase_date = date('d-M-Y',strtotime($data->date));
                    $purchase_time = date('H:i:s',strtotime($data->time));


                    ?>

            <div class = "row">
                <div class = "col-sm-4">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="">Date</label>
                                <input value="{{$purchase_date}}" readonly name="date" class="form-control">
                            </div>
                        </div>
                </div>

                <div class = "col-sm-4">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Time</label>
                            <input value="{{$purchase_time}}" readonly name="time" class="form-control">
                        </div>
                    </div>
                </div>

                <div class = "col-sm-4">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">User</label>
                            <span class="form-control">{{$getLoggeduserProfile->name}}</span>
                        </div>
                    </div>
                </div>
            </div>       
            <div class = "row">
                <div class = "col-sm-3">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Name</label>
                            <input class="form-control customer_name customer_name_enter" name="customer_name" type="text" value="{{$data->customer}}">
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="">Payment Method</label>                                  
                            <select class="form-control" name="payment_method" id="payment_method">
                            @if($data->payment)
                              <option selected value="{{$data->payment->id}}">{{$data->payment->title}}</option>
                            @else
                            <option selected value="2">CASH</option>
                            @endif
                            </select>
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
                           <button type="button" class="btn btn-danger btn-sm addNewrow" style="position: fixed;bottom: 30%;left:4%;"><i class="fa fa-plus" aria-hidden="true"></i></button>
                            <div id = "requisitionitemtable">
                                <table class="table table-bordered table-hover" id="mainItemTable">
                                    <thead>
                                    <tr>
                                      <th>Selection</th>
                                      <th>Description</th>
                                      <th style="width: 90px;">Bal Stock</th>
                                      <th style="width: 90px;">Unit</th>
                                      <th style="width: 90px;">QTY</th>
                                      <th>Selling Price</th>
                                      <th>Location</th>
                                      <th>VAT Type</th>
                                      <th style="width: 90px;">Disc%</th>
                                      <th style="width: 90px;">Discount</th>
                                      <th>VAT</th>
                                      <th>Total</th>
                                      <th>
                                      
                                      </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $discount = $totalvat = $total =0;
                                    @endphp
                                    @foreach ($data->items as $item)
                                    @php
                                        $discount += $item->discount_amount;
                                        $totalvat += $item->vat_amount;
                                        $total +=$item->selling_price*$item->qty;
                                    @endphp
                                     <tr>                                      
                                        <td>
                                            <input type="hidden" name="item_id[{{@$item->wa_inventory_item_id}}]" class="itemid" value="{{@$item->wa_inventory_item_id}}">
                                            <input style="padding: 3px 3px;" type="text" class="testIn form-control" value="{{@$item->item->stock_id_code}}">
                                            <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
                                        </td>
                                        <td><input style="padding: 3px 3px;" readonly="" type="text" name="item_description[{{@$item->wa_inventory_item_id}}]" data-id="{{@$item->wa_inventory_item_id}}" class="form-control" value="{{@$item->item->title}}"></td>
                                        <td>{{@$item->item->getAllFromStockMoves->sum('qauntity')}}</td>
                                        <td><input style="padding: 3px 3px;" readonly="" type="text" name="item_unit[{{@$item->wa_inventory_item_id}}]" data-id="{{@$item->wa_inventory_item_id}}" class="form-control" value="{{@$item->item->pack_size->title}}"></td>
                                        <td><input style="padding: 3px 3px;" onkeyup="getTotal(this)" onchange="getTotal(this)" type="text" name="item_quantity[{{@$item->wa_inventory_item_id}}]" data-id="{{@$item->wa_inventory_item_id}}" class="quantity form-control" value="{{$item->qty}}"></td>
                                        <td><input style="padding: 3px 3px;" {{$editPermission}} onchange="getTotal(this)" onkeyup="getTotal(this)" type="text" name="item_selling_price[{{@$item->wa_inventory_item_id}}]" data-id="{{@$item->wa_inventory_item_id}}" class="selling_price form-control send_me_to_next_item" value="{{$item->selling_price}}"></td>
                                        <td><input type="hidden" name="store_location_id[{{@$item->wa_inventory_item_id}}]">{{@$item->location->location_name}}</td>
                                        <td>
                                            <select class="form-control vat_list send_me_to_next_item" name="item_vat[{{@$item->wa_inventory_item_id}}]" {{$editPermission}}>
                                            @if($item->tax_manager)
                                                <option value="{{@$item->tax_manager->id}}" selected>{{@$item->tax_manager->title}}</option>                                            
                                            @endif
                                            </select>
                                            <input type="hidden" class="vat_percentage" value="{{$item->vat_percentage}}" name="item_vat_percentage[{{@$item->wa_inventory_item_id}}]">
                                        </td>
                                        <td><input style="padding: 3px 3px;" {{$editPermission}} onchange="getTotal(this)" onkeyup="getTotal(this)" type="text" name="item_discount_per[{{@$item->wa_inventory_item_id}}]" data-id="{{@$item->wa_inventory_item_id}}" class="discount_per form-control send_me_to_next_item" value="{{$item->discount_percent}}"></td>
                                        <td><input style="padding: 3px 3px;" {{$editPermission}} type="text" name="item_discount[{{@$item->wa_inventory_item_id}}]" data-id="{{@$item->wa_inventory_item_id}}" class="discount form-control send_me_to_next_item" value="{{$item->discount_amount}}"></td>
                                       
                                        <td><span class="vat">{{round($item->selling_price - (($item->selling_price*100) / ($item->vat_percentage+100)),2)}}</span></td>
                                        <td><span class="total">{{$item->selling_price*$item->qty}}</span></td>
                                        <td>
                                        <button type="button" class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                        </td>
                                        </tr>
                                        @endforeach


                                    </tbody>
                                    <tfoot>
                                      <tr>
                                        <th colspan="11" style="text-align:right">
                                        Total Price
                                        </th>
                                        <td colspan="2">KES <span id="total_exclusive">{{$total - $totalvat}}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="11" style="text-align:right">
                                        Discount
                                        </th>
                                        <td colspan="2">KES <span id="total_discount">{{$discount}}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="11" style="text-align:right">
                                        Total VAT		
                                        </th>
                                        <td colspan="2">KES <span id="total_vat">{{$totalvat}}</span></td>
                                      </tr>
                                      <tr>
                                        <th colspan="11" style="text-align:right">
                                        Total
                                        </th>
                                        <td colspan="2">KES <span id="total_total">{{$total-$discount}}</span></td>
                                      </tr>
                                    </tfoot>
                                </table>
                              </div>
                            </div>
                       


                            <div class="col-md-12">
                              <div class="col-md-6">  
                                <div class="box-body">
                                  <div class="form-group">
                                    @if($getLoggeduserProfile->upload_data == 1 || $permission == 'superadmin')
                                    <input type="hidden" name="esd" id="display" />
                                      @else
                                      <label for="test">&nbsp;</label>
                                      @endif
                                        <button type="submit" class="btn btn-primary btn-sm addExpense" value="save">Save</button>
                                        <button type="submit" class="btn btn-primary btn-sm addExpense processIt" value="send_request">Process</button>
                                  </div>
                                </div>
                              </div>
                              <div class = "col-sm-3">
                                <div class="box-body">
                                    <div class="form-group">
                                        <label for="">Cash</label>
                                        <input style="padding: 0px 0px 0px 7px;font-size: 18px;" readonly class="form-control entered_cash start_process" name="cash" value="{{$data->cash}}" type="number" onkeyup="entered_cash()" onchange="entered_cash()">
                                    </div>
                                </div>
                              </div>

                              <div class = "col-sm-3">
                                <div class="box-body">
                                    <div class="form-group">
                                        <label for="">Change</label>
                                        <span class="form-control cash_change" style="background-color: red;color: white;font-size: 18px;padding: 3px 0px 0px 7px;">{{$data->change}}</span>
                                    </div>
                                </div>
                              </div>
                            </div>


                               
                        </div>
                    </div>


    </section>
  </form>

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
position: fixed;
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
<script src="https://cdn.jsdelivr.net/npm/idb-keyval@6/dist/umd.js"></script>
  <script type="text/javascript">
let directoryHandle;
async function verifyPermission(fileHandle) {
	  if (await fileHandle.queryPermission() === 'granted') {
	    return true;
	  }
	  if (await fileHandle.requestPermission() === 'granted') {
	    return true;
	  }
	  return false;
	}
  async function getData(directoryHandle) {
		var displayContainer = document.getElementById("display");
		entries = directoryHandle.entries();
		try {
			files = [];
			for await (const results of entries) {
				if(results[1].kind == 'file') {
					let file = await results[1].getFile()
					let content = await file.text()
					if(file.name.endsWith('b.txt')) {
						files.push( {
							name: file.name,
							size: file.size,
							lastModified: file.lastModified,
							lastModifiedDate: file.lastModifiedDate,
							content: content,
							dataLenght: content.length,
						})
					}
				}
			}
		} catch( err ) {
			alert('Error: ' + err);
		}
		files = files.sort((a, b) => {return a.lastModified-b.lastModified});
		var lastFile = files[files.length - 1];
    if(lastFile){
      $('#display').val(lastFile.content);
      $(".processIt").click();
    }
	}
  async function readFile () {
	  directoryHandle = await window.showDirectoryPicker();
		idbKeyval.set('directoryHandlePromise', directoryHandle);
		var directoryHandlePromise = idbKeyval.get('directoryHandlePromise');
		directoryHandlePromise.then(directoryHandle => {
			verifyPermission(directoryHandle)
					.then(() => {
						getData(directoryHandle);
					})
		});
	};

 $(document).on('keypress',".quantity",function(event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      $(".addNewrow").click();
    }
  });
  $(document).on('keypress',".start_process",function(event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      $(".processIt").click();
    }
  });
  function makemefocus(){
    if($(".makemefocus")[0]){
        $(".makemefocus")[0].focus();
    }
  }
  $(document).on('keypress','.customer_name_enter',function(event){
    if (event.keyCode === 13) {
      event.preventDefault();
      makemefocus();
    }
  });
  $(document).on('keypress change','.send_me_to_next_item',function(event){
    if (event.keyCode === 13) {
      event.preventDefault();
      makemefocus();
    }
  });
  $('#payment_method').change(function(e){
    if($(this).val() == '2'){
      $('.entered_cash').removeAttr('readonly');
    }else
    {
      $('.entered_cash').attr('readonly',true);
      $('.entered_cash').val('');
      entered_cash();
    }
  });
  $(document).ready(function () {
    if($('#payment_method').val() == '2'){
      $('.entered_cash').removeAttr('readonly');
    }else
    {
      $('.entered_cash').attr('readonly',true);
      $('.entered_cash').val('');
      entered_cash();
    }
  })
var form = new Form();
$(document).on('click','.addExpense',function(e){
    e.preventDefault();
    $('#loader-on').show();
    var dd = $('#display');
    if(dd.val() == '' && $(this).val() == 'send_request'){
      readFile();
      return false;
    }
    var postData = new FormData($(this).parents('form')[0]);
    var url = $(this).parents('form').attr('action');
    postData.append('_token',$(document).find('input[name="_token"]').val());
    postData.append('request_type',$(this).val());

    $.ajax({
        url:url,
        data:postData,
        contentType: false,
        cache: false,
        processData: false,
        method:'POST',
        success:function(out){
    $('#loader-on').hide();

            $(".remove_error").remove();
            if(out.result == 0) {
                for(let i in out.errors) {
                    var id = i.split(".");
                    if(id && id[1]){
                        $("[name='"+id[0]+"["+id[1]+"]']").parent().append('<label class="error d-block remove_error w-100" id="'+i+'_error">'+out.errors[i][0]+'</label>');
                    }else
                    {
                        $("[name='"+i+"']").parent().append('<label class="error d-block remove_error w-100" id="'+i+'_error">'+out.errors[i][0]+'</label>');
                        $("."+i).parent().append('<label class="error d-block remove_error w-100" id="'+i+'_error">'+out.errors[i][0]+'</label>');
                    }
                }
            }
            if(out.result === 1) {
                form.successMessage(out.message);
                if(out.location)
                {
                    // setTimeout(() => {
                      $('#mainItemTable tbody').html('');
                      $('#mainItemTable tbody').append('<tr><td><input type="text" class="testIn form-control makemefocus"><div class="textData" style="width: 100%;position: relative;z-index: 99;"></div></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td><button class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button></td>'
                                    +'</tr>');
                      $('[name="customer_name"]').val('');
                      $('.entered_cash').val('');
                      $('.cash_change').html('');
                      $('#display').val('');
                      $('#total_exclusive').html('0.00');
                      $('#total_discount').html('0.00');
                      $('#total_vat').html('0.00');
                      $('#total_total').html('0.00');
                      if(out.requestty == 'save'){
                        location.href = out.location;
                      }else{
                        printBill(out.location);
                      }
                    // }, 1000);
                }
            }
            if(out.result === -1) {
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
});

  $(document).ready(function(){
    $('body').addClass('sidebar-collapse');
    payment_method();
  });
    $(function () {
        $(".mlselec6t").select2();
    });
    $(function () {
        $(".mlselec6t_modal").select2({dropdownParent: $('.modal')});
    });

    function printBill(slug)
    {
        jQuery.ajax({
            url: slug,
            type: 'GET',
            async:false,   //NOTE THIS
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
            // location.reload();

            }
        });
              
    }

</script>
<script src="{{asset('assets/admin/dist/bootstrap-datepicker.js')}}"></script>
    <script>
        $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
        });
    </script>
    
    <script>
      
  $(document).on('keyup','.testIn',function(e){ var vale = $(this).val();
      $(this).parent().find(".textData").show();
      var $this = $(this);
      $.ajax({
        type: "GET",
        url: "{{route('purchase-orders.inventoryItems')}}",
        data: {
          'search':vale
        },
        success: function (response) {
          $this.parent().find('.textData').html(response);
        }
      });
  });

  $(document).click(function(e){
    var container = $(".textData");
    // if the target of the click isn't the container nor a descendant of the container
    if (!container.is(e.target) && container.has(e.target).length === 0) 
    {
        container.hide();
    }
  });
  $(".vat_list").select2();
    function fetchInventoryDetails(varia){
      var $this = $(varia);
      var itemids = $('.itemid');
      var furtherCall = true;
      $.each(itemids, function (indexInArray, valueOfElement) { 
         if($this.data('id') == $(valueOfElement).val()){
          form.errorMessage('This Item is already added in list');
          furtherCall = false;
          return true;
         }
      });
      if(furtherCall == true){
        $.ajax({
          type: "GET",
          url: "{{route('pos-cash-sales.getInventryItemDetails')}}",
          data: {
            'id':$this.data('id'),
            'route_customer': ($('#route_customer').val() || $('.route_customer').val() || null)
          },
          success: function (response) {
            $(".vat_list").select2('destroy');
            $this.parents('tr').replaceWith(response);
            vat_list();
            totalofAllTotal();
          }
        });
      }
    }
    $(document).on('click','.deleteparent',function(){
      $(this).parents('tr').remove();
      totalofAllTotal()
    });
    $(document).on('click','.addNewrow',function(){
      $('#mainItemTable tbody').append('<tr><td><input type="text" class="testIn form-control makemefocus"><div class="textData" style="width: 100%;position: relative;z-index: 99;"></div></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td></td>'
                                    +'<td><button class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button></td>'
                                    +'</tr>');
      makemefocus();
    });

    var vat_list = function(){
            $(".vat_list").select2(
            {
                placeholder:'Select Vat',
                ajax: {
                    url: '{{route("expense.vat_list")}}',
                    dataType: 'json',
                    type: "GET",
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                            var res = data.map(function (item) {
                                return {id: item.id, text: item.text};
                            });
                        return {
                            results: res
                        };
                    }
                },
            });
        };
        $(document).on('change','.vat_list',function(){
            var vat = $(this).val();
            var $this = $(this);
            $.ajax({
                type: "GET",
                url: "{{route('expense.vat_find')}}",
                data: {
                    'id':vat
                },
                success: function (response) {
                    $this.parents('tr').find('.vat_percentage').val(response.tax_value);
                    getTotal($this);
                }
            });
            
        });

        function getTotal(vara){
            var price = $(vara).parents('tr').find('.selling_price').val();
            if(price < 0){
                $(vara).parents('tr').find('.selling_price').val(0);
                price = 0;
            }
            var quantity = $(vara).parents('tr').find('.quantity').val();
            if(quantity <= 0){
                $(vara).parents('tr').find('.quantity').val('');
                quantity = 0;
            }
            var discount_per = $(vara).parents('tr').find('.discount_per').val();
            if(discount_per < 0){
                $(vara).parents('tr').find('.discount_per').val(0);
                discount_per = 0;
            }
            var vat_percentage = $(vara).parents('tr').find('.vat_percentage').val();
            if(vat_percentage < 0){
                $(vara).parents('tr').find('.vat_percentage').val(0);
                vat_percentage = 0;
            }
            var discount = ((parseFloat(price)*parseFloat(quantity))*parseFloat(discount_per))/100;
            var exclusive = ((parseFloat(price)*parseFloat(quantity))-parseFloat(discount));
            var vat = parseFloat(exclusive) - parseFloat((parseFloat(exclusive)*100) / (parseFloat(vat_percentage)+100));
            var total = parseFloat(exclusive);
            $(vara).parents('tr').find('.discount').val((discount).toFixed(2));
            $(vara).parents('tr').find('.vat').html((vat).toFixed(2));
            $(vara).parents('tr').find('.total').html((total).toFixed(2));

            totalofAllTotal();
        }
        $(document).on('keyup','.discount',function(e){
          var discount = $(this).val();
          if(discount < 0){
                $(this).parents('tr').find('.discount').val(0);
                discount = 0;
            }
          var price = $(this).parents('tr').find('.selling_price').val();
          if(price < 0){
                $(this).parents('tr').find('.selling_price').val(0);
                price = 0;
            }
          var quantity = $(this).parents('tr').find('.quantity').val();
          if(quantity <= 0){
                $(this).parents('tr').find('.quantity').val('');
                quantity = 0;
            }
          var vat_percentage = $(this).parents('tr').find('.vat_percentage').val();  
          if(vat_percentage < 0){
                $(this).parents('tr').find('.vat_percentage').val(0);
                vat_percentage = 0;
            }        
          var discount_per = (discount/parseFloat(price)*parseFloat(quantity))*100;
          var exclusive = ((parseFloat(price)*parseFloat(quantity))-parseFloat(discount));
          var vat = parseFloat(exclusive) - parseFloat((parseFloat(exclusive)*100) / (parseFloat(vat_percentage)+100));
          var total = parseFloat(exclusive);
          $(this).parents('tr').find('.discount_per').val((discount_per).toFixed(2));
          $(this).parents('tr').find('.vat').html((vat).toFixed(2));
          $(this).parents('tr').find('.total').html((total).toFixed(2));
          totalofAllTotal();
        });
        function totalofAllTotal(){
          var alld = $(document).find('.discount');
          var allv = $(document).find('.vat');
          var allt = $(document).find('.total');
          // var alle = $(document).find('.selling_price');
          // var exclusive = 0;
          var vat = 0;
          var total = 0;
          var discount = 0;
          $.each(alld, function (indexInArray, valueOfElement) { 
            discount = parseFloat(discount) + parseFloat($(valueOfElement).val());
          });
          // $.each(alle, function (indexInArray, valueOfElement) { 
          //   exclusive = parseFloat(exclusive) + parseFloat($(valueOfElement).val());
          // });
          $.each(allv, function (indexInArray, valueOfElement) { 
            vat = parseFloat(vat) + parseFloat($(valueOfElement).text());
          });
          $.each(allt, function (indexInArray, valueOfElement) { 
            total = parseFloat(total) + parseFloat($(valueOfElement).text());
          });
          $('#total_exclusive').html((parseFloat(total) - parseFloat(vat)).toFixed(2));
          $('#total_vat').html((vat).toFixed(2));
          $('#total_total').html((total).toFixed(2));
          $('#total_discount').html((discount).toFixed(2));
          entered_cash();
        }

        function entered_cash(){
            var entered_cash = $('.entered_cash').val();
            if(entered_cash == '' || entered_cash < 0){
                $('.entered_cash').val('');
                entered_cash = 0;
            }   
            var allt = $(document).find('.total');
            var total = 0;
            $.each(allt, function (indexInArray, valueOfElement) { 
                total = parseFloat(total) + parseFloat($(valueOfElement).text());
            });
            var cash_balance = parseFloat(entered_cash) - parseFloat(total) ;
            $('.cash_change').text(cash_balance);
        }
        var payment_method = function(){
            $("#payment_method").select2(
            {
                placeholder:'Select Payment Method',
                ajax: {
                    url: '{{route("expense.payment_method")}}',
                    dataType: 'json',
                    type: "GET",
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    },
                    processResults: function (data) {
                            var res = data.map(function (item) {
                                return {id: item.id, text: item.text};
                            });
                        return {
                            results: res
                        };
                    }
                },
            });
        };
    </script>
@endsection


