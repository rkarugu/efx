@extends('layouts.admin.admin')
@section('content')
    @if(Request::url() != URL::previous())
        <a href="{!! URL::previous() !!}" class="btn btn-primary">Back</a>
    @endif
    <form method="POST" action="{{route($model.'.store')}}" accept-charset="UTF-8" enctype="multipart/form-data"
          novalidate="novalidate" onsubmit="return false;">
        <section class="content">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title"> {!! $title !!} </h3></div>
                @include('message')
                {{ csrf_field() }}
                <?php
                // $requisition_no = getCodeWithNumberSeries('INTERNAL REQUISITIONS');
                $default_branch_id = $getLoggeduserProfile->restaurant_id;
                $default_department_id = $getLoggeduserProfile->wa_department_id;
                $default_wa_location_and_store_id = null;
                $requisition_date = date('Y-m-d');


                ?>

                <div class="row">

                    <div class="col-sm-6">
                        {{-- <div class = "row">
                           <div class="box-body">
                       <div class="form-group">
                           <label for="inputEmail3" class="col-sm-5 control-label">Invoice No.</label>
                           <div class="col-sm-7">


                               {!! Form::text('requisition_no',  $requisition_no , ['maxlength'=>'255','placeholder' => '', 'required'=>true, 'class'=>'form-control','readonly'=>true]) !!}
                           </div>
                       </div>
                   </div>
                        </div> --}}

                        <div class="row">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Employee name</label>
                                    <div class="col-sm-7">
                                        {!! Form::text('emp_name', $getLoggeduserProfile->name, ['maxlength'=>'255','placeholder' => '', 'required'=>true, 'class'=>'form-control','readonly'=>true]) !!}
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Invoice Date</label>
                                    <div class="col-sm-7">
                                        {!! Form::text('requisition_date', $requisition_date, ['maxlength'=>'255','placeholder' => '', 'required'=>true, 'class'=>'form-control','readonly'=>true]) !!}
                                    </div>
                                </div>
                            </div>
                        </div>


                        {{-- <div class = "row">
                         <div class="box-body">
                            <div class="form-group">
                                <label for="inputEmail3" class="col-sm-5 control-label">Vehicle Registration No.</label>
                                <div class="col-sm-7">
                                     {!!Form::select('vehicle_reg_no',getVehicleRegList(), null, ['class' => 'form-control mlselec6t','id'=>'vehicle_reg_no' ,'placeholder'=>'Please select' ])!!}
                                 </div>
                            </div>
                        </div>
                        </div> --}}
                        {{--            <div class = "row">--}}
                        {{--              <div class="box-body">--}}
                        {{--                <div class="form-group">--}}
                        {{--                    <label for="inputEmail3" class="col-sm-5 control-label">Route</label>--}}
                        {{--                    <div class="col-sm-7">--}}
                        {{--                         {!!Form::hidden('route',null, ['class' => 'form-control','id'=>'route' ,'placeholder'=>'Please select' ])!!} --}}
                        {{--                          <span id = "error_msg_route"></span>--}}
                        {{--                    </div>--}}
                        {{--                </div>--}}
                        {{--              </div>--}}
                        {{--            </div>--}}
                        {{--         --}}

                        <div class="row">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Credit Limit</label>
                                    <div class="col-sm-7">
                                        <span id="credit_limit"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Current A/C Balance</label>
                                    <div class="col-sm-7">
                                        <span id="used_balance"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Available Limit</label>
                                    <div class="col-sm-7">
                                        <span id="ac_balance"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>


                    <div class="col-sm-6">

                        <input type="hidden" name="restaurant_id" value="{{$default_branch_id}}">
                        <input type="hidden" name="wa_department_id" value="{{$default_department_id}}">
                        <div class="row">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Account</label>
                                    <div class="col-sm-6">
                                        {!! Form::select('customer', $customer_list , null, ['maxlength'=>'255','placeholder' => 'Customer','id'=>'customer', 'required'=>true, 'class'=>'form-control mlselec6t','onchange'=>'getcustomercredit()']) !!}
                                        <span id="error_msg_customer"></span>

                                    </div>
                                </div>
                            </div>

                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Customer Pin</label>
                                    <div class="col-sm-7">
                                        <span id="kra_pin"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="box-body">
                                <div class="form-group">
                                    <label for="inputEmail3" class="col-sm-5 control-label">Customer Phone</label>
                                    <div class="col-sm-7">
                                        <span id="phone_number"></span>
                                    </div>
                                </div>
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


                    <div class="col-md-12 no-padding-h">
                        <h3 class="box-title"> Invoice Line</h3>

                        <div id="requisitionitemtable" name="item_id[0]">

                            <button type="button" class="btn btn-danger btn-sm addNewrow"
                                    style="position: fixed;bottom: 30%;left:4%;"><i class="fa fa-plus"
                                                                                    aria-hidden="true"></i></button>
                            <table class="table table-bordered table-hover" id="mainItemTable">
                                <thead>
                                <tr>
                                    <th>Selection <span style="color: red;">(Search Atleast 3 Keyword)</span></th>
                                    <th>Description</th>
                                    <th style="width: 90px;">QOH</th>
                                    <th style="width: 90px;">Unit</th>
                                    <th style="width: 90px;">QTY</th>
                                    <th>Selling Price</th>
                                    <th>VAT Type</th>
                                    <th style="width: 90px;">Disc%</th>
                                    <th style="width: 90px;">Discount</th>
                                    <th>VAT</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>
                                        <input type="text" placeholder="Search Atleast 3 Keyword"
                                               class="testIn form-control">
                                        <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm deleteparent"><i
                                                    class="fa fa-trash" aria-hidden="true"></i></button>
                                    </td>
                                </tr>


                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="11" style="text-align:right">
                                        Total Price
                                    </th>
                                    <td colspan="2">KES <span id="total_exclusive">0.00</span></td>
                                </tr>
                                <tr>
                                    <th colspan="11" style="text-align:right">
                                        Discount
                                    </th>
                                    <td colspan="2">KES <span id="total_discount">0.00</span></td>
                                </tr>
                                <tr>
                                    <th colspan="11" style="text-align:right">
                                        Total VAT
                                    </th>
                                    <td colspan="2">KES <span id="total_vat">0.00</span></td>
                                </tr>
                                <tr>
                                    <th colspan="11" style="text-align:right">
                                        Total
                                    </th>
                                    <td colspan="2">KES <span id="total_total">0.00</span></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <div class="col-md-6 request_type">

                            <button type="submit" class="btn btn-success btn-lg addExpense" value="save">Save</button>
                            <button type="submit" class="btn btn-success btn-lg addExpense processIt"
                                    value="send_request">Send Request
                            </button>

                        </div>
                        <div class="col-md-3"></div>
                        <div class="col-md-3"></div>
                    </div>


                </div>
            </div>


        </section>
    </form>


    <div class="modal fade" id="modelId" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
        <form action="{{route('maintain-customers.add_route_customer')}}" method="post" class="addSubCustomer">
            {{csrf_field()}}
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Customer</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="route_id" id="route_id" value="">
                        <input type="hidden" name="customer_id" id="customer_id" value="">
                        <div class="form-group">
                            <label for="">Name</label>
                            <input type="text" name="name" id="name" class="form-control" placeholder="Enter Name"
                                   aria-describedby="helpId">
                        </div>
                        <div class="form-group">
                            <label for="">Phone No</label>
                            <input type="text" name="phone_no" onchange="phoneFormat(this)" id="phone_no"
                                   class="form-control" placeholder="Enter Phone No" aria-describedby="helpId">
                        </div>
                        <div class="form-group">
                            <label for="">Business Name</label>
                            <input type="text" name="business_name" id="business_name" class="form-control"
                                   placeholder="Enter Business Name" aria-describedby="helpId">
                        </div>
                        <div class="form-group">
                            <label for="">Town</label>
                            <input type="text" name="town" id="town" class="form-control" placeholder="Enter Town"
                                   aria-describedby="helpId">
                        </div>
                        <div class="form-group">
                            <label for="">Contact Person</label>
                            <input type="text" name="contact_person" id="contact_person" class="form-control"
                                   placeholder="Enter Contact Person" aria-describedby="helpId">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div id="otpModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">OTP Verification</h5>
                    <button type="button" class="close" id="closeOtpModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Please enter the OTP sent to the admin:</p>
                    <input type="text" id="otpInput" class="form-control" placeholder="Enter OTP">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="verifyOtpBtn">Verify OTP</button>
                    <button type="button" class="btn btn-secondary" id="closeModalBtn">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('uniquepagestyle')

    <link href="{{asset('assets/admin/bower_components/select2/dist/css/select2.min.css')}}" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('assets/admin/dist/datepicker.css')}}">

    <style type="text/css">
        .select2 {
            width: 100% !important;
        }

        #note {
            height: 60px !important;
        }

        .align_float_right {
            text-align: right;
        }

        .textData table tr:hover, .SelectedLi {
            background: #000 !important;
            color: white !important;
            cursor: pointer !important;
        }


        /* ALL LOADERS */

        .loader {
            width: 100px;
            height: 100px;
            border-radius: 100%;
            position: relative;
            margin: 0 auto;
            top: 35%;
        }

        /* LOADER 1 */

        #loader-1:before, #loader-1:after {
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

        #loader-1:before {
            z-index: 100;
            animation: spin 1s infinite;
        }

        #loader-1:after {
            border: 10px solid #ccc;
        }

        @keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
                -ms-transform: rotate(0deg);
                -o-transform: rotate(0deg);
                transform: rotate(0deg);
            }

            100% {
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
    <script type="text/javascript">
        var form = new Form();
        $(document).on('keypress', ".start_process", function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                $(".processIt").click();
            }
        });

        function makemefocus() {
            if ($(".makemefocus")[0]) {
                $(".makemefocus")[0].focus();
            }
        }

        // Close button logic
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            // Redirect to the previous page
            window.location.href = "{{ URL::previous() }}";
        });
        $(document).on('click', '.addNewrow', function () {
            $('#mainItemTable tbody').prepend('<tr>' +
                '<td>'
                +'<input type="text" class="testIn form-control makemefocus">'
                +'<div class="textData" style="width: 100%;position: relative;z-index: 99;">'
                +'</div>'
                +'</td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td></td>'
                + '<td><button class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button></td>'
                + '</tr>');
            $('#mainItemTable tbody tr:first-child td:first-child input').focus();
            makemefocus();
        });
        // Optional: Close with the "X" button in the header
        document.getElementById('closeOtpModal').addEventListener('click', function() {
            // Redirect to the previous page
            window.location.href = "{{ URL::previous() }}";
        });
        $(document).on('keypress change', '.send_me_to_next_item', function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                makemefocus();
            }
        });
        $(document).on('click', '.addExpense', function (e) {
            e.preventDefault();
            $('#loader-on').show();
            var postData = new FormData($(this).parents('form')[0]);
            var url = $(this).parents('form').attr('action');
            postData.append('_token', $(document).find('input[name="_token"]').val());
            postData.append('request_type', $(this).val());
            $.ajax({
                url: url,
                data: postData,
                contentType: false,
                cache: false,
                processData: false,
                method: 'POST',
                success: function (out) {
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

                error: function (err) {
                    $('#loader-on').hide();
                    $(".remove_error").remove();
                    form.errorMessage('Something went wrong');
                }
            });
        });
        $(document).on('keypress', ".quantity", function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                $(".addNewrow").click();
            }
        });
        $(document).on('change', '.quantity', function (e) {
            var qty = $(this).val();
            var inputName = $(this).attr('name');
            var item_id = inputName.substring(inputName.indexOf("[") + 1, inputName.indexOf("]"));
            
            console.log('Quantity changed for item:', item_id, 'quantity:', qty);
            console.log('Input name:', inputName);
            var counts = $(this).data('counts');
            var $inputElement = $(this);
            $inputElement.next('.error-message').remove();

            console.log('count == ' + counts);
            console.log('qty is decimal ' + isDecimal(qty));

            // Validate if the quantity is a decimal
            if (isDecimal(qty)) {
                if (counts !== null && counts !== '') {
                    console.log('has count');
                    if (!checkSplit(qty)) {
                        $inputElement.after('<span class="error-message" style="color: red;">Item cannot be split into given quantity</span>');
                    }
                } else {
                    console.log('No count');
                    $inputElement.after('<span class="error-message" style="color: red;">Item cannot be sold into halves</span>');
                }
            }

            // Note: Discount bands are now calculated in the backend during form submission
            // This ensures consistent discount application without frontend AJAX issues
            totalofAllTotal();
        });
        $(document).on('click', '.deleteparent', function () {
            $(this).parents('tr').remove();
            totalofAllTotal()
        });
        $(document).on('change', '.vat_list', function () {
            var vat = $(this).val();
            var $this = $(this);
            $.ajax({
                type: "GET",
                url: "{{route('expense.vat_find')}}",
                data: {
                    'id': vat
                },
                success: function (response) {
                    $this.parents('tr').find('.vat_percentage').val(response.tax_value);
                    getTotal($this);
                }
            });

        });
        var valueTest = null;
        $(document).on('keyup keypress click', '.testIn', function (e) {
            var vale = $(this).val();
            $(this).parent().find(".textData").show();
            var objCurrentLi, obj = $(this).parent().find(".textData tbody tr.SelectedLi"),
                objUl = $(this).parent().find('.textData tbody'),
                code = (e.keyCode ? e.keyCode : e.which);
            if (code == 40) { //Up Arrow

                //if object not available or at the last tr item this will roll that back to first tr item
                if ((obj.length === 0) || (objUl.find('tr:last').hasClass('SelectedLi') === true)) {
                    objCurrentLi = objUl.find('tr:first').addClass('SelectedLi').addClass('industryli');
                }
                //This will add class to next tr item
                else {
                    objCurrentLi = obj.next().addClass('SelectedLi').addClass('industryli');
                }

                //this will remove the class from current item
                obj.removeClass('SelectedLi');

                var listItem = $(this).parent().find('.SelectedLi.industryli');
                var selectedLi = $(this).parent().find(".textData tbody tr").index(listItem);

                var len = $(this).parent().find('.textData tbody tr').length;


                if (selectedLi > 1) {
                    var scroll = selectedLi + 1;
                    $(this).parent().find('.textData table').scrollTop($(this).parent().find('.textData table').scrollTop() + obj.next().height());
                }
                if (selectedLi == 0) {
                    $(this).parent().find('.textData table').scrollTop($(this).parent().find('.textData table tr:first').position().top);
                }

                return false;
            }
            else if (code == 38) {//Down Arrow
                if ((obj.length === 0) || (objUl.find('tr:first').hasClass('SelectedLi') === true)) {
                    objCurrentLi = objUl.find('tr:last').addClass('SelectedLi').addClass('industryli');
                } else {
                    objCurrentLi = obj.prev().addClass('SelectedLi').addClass('industryli');
                }
                obj.removeClass('SelectedLi');

                var listItem = $(this).parent().find('.SelectedLi.industryli');
                var selectedLi = $(this).parent().find(".textData tbody tr").index(listItem);

                var len = $(this).parent().find('.textData tbody tr').length;


                if (selectedLi > 1) {
                    var scroll = selectedLi - 1;
                    $(this).parent().find('.textData table').scrollTop(
                        $(this).parent().find('.textData table tr:nth-child(' + scroll + ')').position().top -
                        $(this).parent().find('.textData table tr:first').position().top);
                }
                return false;
            } else if (code == 13) {
                obj.click();
                return false;
            }
            else if (valueTest != vale && (e.type == 'keyup' || e.type == 'click') && code != 13 && code != 38 && code != 40 && vale != '') {
                var $this = $(this);

                if (vale.length >= 3) {
                    $.ajax({
                        type: "GET",
                        url: "{{route('pos-cash-sales.search-inventory')}}",
                        data: {
                            'search': vale,
                            'store_location_id': {{ getLoggeduserProfile()->wa_location_and_store_id }},

                        },
                        success: function (response) {
                            $this.parent().find('.textData').html(response.view);
                            console.log(response.results)
                        }
                    });
                    valueTest = vale;
                }

                return true;
            }


        });

        $(document).click(function (e) {
            var container = $(".textData");
            // if the target of the click isn't the container nor a descendant of the container
            if (!container.is(e.target) && container.has(e.target).length === 0) {
                container.hide();
            }
        });
        function fetchInventoryDetails(varia) {
            var $this = $(varia);
            var itemids = $('.itemid');
            var furtherCall = true;
            $.each(itemids, function (indexInArray, valueOfElement) {
                if ($this.data('id') == $(valueOfElement).val()) {
                    form.errorMessage('This Item is already added in list');
                    furtherCall = false;
                    return true;
                }
            });
            if (furtherCall == true) {
                $.ajax({
                    type: "GET",
                    url: "{{route('sales-invoice.getInventryItemDetails')}}",
                    data: {
                        'id': $this.data('id')
                    },
                    success: function (response) {
                        if (response.result) {
                            form.errorMessage(response.message);
                            return true;
                        }
                        $(".vat_list").select2('destroy');
                        $this.parents('tr').replaceWith(response);
                        vat_list();
                        totalofAllTotal();
                        
                        // Discount bands will be calculated automatically in the backend when the form is submitted
                    }
                });
            }
        }
        function isDecimal(num) {
            return num % 1 !== 0;
        }
        function checkSplit(number) {
            // Get the decimal part of the number using modulo operation
            var decimalPart = number % 1;

            // Check if the decimal part is one of the allowed values
            return [0.25, 0.5, 0.75].includes(decimalPart);
        }
        function getcustomercredit() {
            var customer = $(document).find('#customer').val();
            if (customer == '' || customer == 'Customer') {
                return false;
            }
            $.ajax({
                type: "GET",
                url: "{{route('sales-invoice.getcustomercredit')}}",
                data: {
                    'id': customer
                },
                success: function (response) {
                    if (response.result) {
                        if (response.result === -1) {
                            form.errorMessage(response.message);
                            $('#credit_limit').html('').removeClass('form-control');
                            $('#ac_balance').html('').removeClass('form-control');
                            $('#used_balance').html('').removeClass('form-control');
                        } else {

                            @if(env("USE_OTP"))
                            if (parseFloat(response.used_balance).toFixed(2) !== '0.00') {
                                Swal.fire({
                                    title: 'Unpaid Invoices',
                                    text: 'Customer has unpaid invoices.',
                                    icon: 'warning',
                                    confirmButtonText: 'Close',
                                    cancelButtonText: 'Request OTP',
                                    showCancelButton: true,
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Action for the "Close" button
                                        window.location.href = "{{ URL::previous() }}";
                                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                                        requestOTP();
                                    }
                                });
                            }
                            @endif


                            $('#customer_id').val(response.customer_id);

                            $('#credit_limit').html(response.credit_limit).addClass('form-control');
                            $('#ac_balance').html(response.balance).addClass('form-control');
                            $('#used_balance').html(response.used_balance).addClass('form-control');
                            $('#phone_number').html(response.customer.telephone).addClass('form-control');
                            $('#kra_pin').html(response.customer.kra_pin).addClass('form-control');
                        }
                    }
                }
            });
        }
        function getTotal(vara) {
            var price = $(vara).parents('tr').find('.selling_price').val();
            if (price < 0) {
                $(vara).parents('tr').find('.selling_price').val(0);
                price = 0;
            }
            var quantity = $(vara).parents('tr').find('.quantity').val();
            if (quantity <= 0) {
                $(vara).parents('tr').find('.quantity').val('');
                quantity = 0;
            }
            var discount_per = $(vara).parents('tr').find('.discount_per').val();
            if (discount_per < 0) {
                $(vara).parents('tr').find('.discount_per').val(0);
                discount_per = 0;
            }
            var vat_percentage = $(vara).parents('tr').find('.vat_percentage').val();
            if (vat_percentage < 0) {
                $(vara).parents('tr').find('.vat_percentage').val(0);
                vat_percentage = 0;
            }
            var discount = ((parseFloat(price) * parseFloat(quantity)) * parseFloat(discount_per)) / 100;
            var exclusive = ((parseFloat(price) * parseFloat(quantity)) - parseFloat(discount));
            var vat = parseFloat(exclusive) - parseFloat((parseFloat(exclusive) * 100) / (parseFloat(vat_percentage) + 100));
            var total = parseFloat(exclusive);
            $(vara).parents('tr').find('.discount').val((discount).toFixed(2));
            $(vara).parents('tr').find('.vat').html((vat).toFixed(2));
            $(vara).parents('tr').find('.total').html((total).toFixed(2));

            totalofAllTotal();
        }
        function totalofAllTotal() {

            var allv = $(document).find('.vat');
            var allt = $(document).find('.total');
            var alle = $(document).find('.selling_price');
            var exclusive = 0;
            var vat = 0;
            var total = 0;
            var alld = $(document).find('.discount');
            var discount = 0;

            $.each(alld, function (indexInArray, valueOfElement) {
                console.log('Current Value:', $(valueOfElement).val());
                let value = parseFloat($(valueOfElement).val()) || 0;
                discount += value;
            });

            $.each(alle, function (indexInArray, valueOfElement) {
                exclusive = parseFloat(exclusive) + parseFloat($(valueOfElement).val());
            });
            $.each(allv, function (indexInArray, valueOfElement) {
                vat = parseFloat(vat) + parseFloat($(valueOfElement).text());
            });
            $.each(allt, function (indexInArray, valueOfElement) {
                total = parseFloat(total) + parseFloat($(valueOfElement).text());
            });
            $('#total_vat').html((vat).toFixed(2));
            $('#total_total').html((total).toFixed(2));
            $('#total_exclusive').html((parseFloat(total) - parseFloat(vat)).toFixed(2));
            $('#total_discount').html((discount).toFixed(2));

        }

        var vat_list = function () {
            $(".vat_list").select2(
                {
                    placeholder: 'Select Vat',
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


        function requestOTP() {
            $('#otpModal').modal('show');
            $.post("{{ route('credit.sales.otp-over') }}", {
                _token: '{{ csrf_token() }}'
            }, function(response) {
                if (response.message) {

                }else {
                    $('#otpModal').modal('hide');
                }
            });
        }
        $('#verifyOtpBtn').click(function() {
            const otp = $('#otpInput').val();

            $.post("{{ route('credit.sales.verify.otp') }}", {
                _token: '{{ csrf_token() }}',
                otp: otp
            }, function(response) {
                if (response.success) {
                    $('#otpModal').modal('hide');
                } else {
                    alert(response.message);
                }
            });
        });
        function verifyOtp() {
            $.post("{{ route('credit.sales.verify.otp') }}", {
                _token: '{{ csrf_token() }}',
                otp: otp
            }, function(response) {
                if (response.success) {
                    window.location.href = "{{ route($model.'.create') }}";
                } else {
                    alert(response.message);
                }
            });
        }

        $(document).ready(function () {
            $('#to_store_location_id').change(function (e) {
                e.preventDefault();
                var myval = $(this).val();
                $.ajax({
                    type: "get",
                    url: "{{route('sales-invoice.getsalesmanroute')}}",
                    data: {
                        'id': myval
                    },
                    success: function (response) {
                        if (response.result) {
                            if (response.result === -1) {
                                form.errorMessage(response.message);
                                $('#error_msg_route').html('').removeClass('form-control');
                                $('#route').val('');
                                $('#route_id').val('');
                                $('#credit_limit').html('').removeClass('form-control');
                                $('#ac_balance').html('').removeClass('form-control');
                                $('#shift_id').html('').removeClass('form-control');
                                $('#used_balance').html('').removeClass('form-control');

                            } else {
                                $('#error_msg_route').html(response.data.route_name).addClass('form-control');
                                $('#route').val(response.data.id);
                                $('#route_id').val(response.data.id);
                                $('#credit_limit').html(response.credit_limit).addClass('form-control');
                                $('#ac_balance').html(response.balance).addClass('form-control');
                                $('#shift_id').html(response.shift_id).addClass('form-control');
                                $('#used_balance').html(response.used_balance).addClass('form-control');

                                $(".mlselec6t").select2('destroy');
                                $('#customer option').each(function (key, val) {
                                    if ($(val).val() == response.customer) {
                                        $('#customer option').removeAttr('selected');
                                        $(val).attr('selected', true);
                                        $(".mlselec6t").select2();

                                        return true;
                                    }
                                });
                                $(".mlselec6t").select2();
                            }
                        }
                        getcustomercredit();
                    }
                });
            });
            $('body').addClass('sidebar-collapse');
            // payment_method();
        });
        $(function () {
            $(".mlselec6t").select2();
        });
        $(function () {
            $(".mlselec6t_modal").select2({dropdownParent: $('.modal')});
        });


    </script>
    <script src="{{asset('assets/admin/dist/bootstrap-datepicker.js')}}"></script>
    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>

@endsection


