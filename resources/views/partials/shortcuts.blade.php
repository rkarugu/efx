<style>
    .payment-logo {
        height: 40px; /* Adjust the height as needed */
        margin-left: 10px; /* Space between logos */
    }

</style>
<input type="hidden" id="customer_fon" value="{{ @$data->customer_phone_number }}">
<div id="pos_route_customer_create"></div>

<script src="https://cdn.jsdelivr.net/npm/pusher-js@7.0.3/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.2/dist/echo.iife.js"></script>


<script type="text/javascript">
    let total_tendered = 0;
    let allowance = 0;

    // Wait for jQuery and DOM to be ready
    $(document).ready(function() {
        var total = $('#total_total').html()
        if ((parseFloat(total)) === 0 ) {
            $('#continuePayment').attr('disabled', true);
        }
        
        // Attach event handler
        $('#route_customer').on('change', function() {
            checkButtonState();
        });
    });

    function load_customer() {
        $('#loader-on').show();
        url = "{{route('pos.route_customer.create')}}";
        $.ajax({
            type: "GET",
            url: url,
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                $('#loader-on').hide();
                if (response.result === -1) {
                    form.errorMessage(response.message);
                } else {
                    // form.successMessage(response.message);
                    $("#pos_route_customer_create").html(response.data)
                    checkButtonState()
                }
            }
        });

    }

    function checkButtonState() {
        var total = $('#total_total').html();
        var customerSelected = $('#route_customer').val();
        if ((parseFloat(total)) === 0 || customerSelected === null || customerSelected === '') {
            $('#continuePayment').attr('disabled', true);
        } else {
            console.log('should unblock')
            $('#continuePayment').attr('disabled', false);
        }
    }

    function checkBalance() {
        var checkBalance = $('.checkBalance');
        var total = $('#total_total').html()
        $('.thisSaleTotal').text(total);

        var payment_amount = 0;
        $.each(checkBalance, function (indexInArray, valueOfElement) {
            var thisval = $(valueOfElement).val();
            if (thisval != '' && !isNaN(thisval)) {
                payment_amount = parseFloat(payment_amount) + parseFloat(thisval);
            }
        });
        var balance = parseFloat(total.replace(/,/g, '')) - parseFloat(payment_amount);
        $('.total_total').text((total.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })));
        $('.cash_change').text((balance.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })));
        $('.processIt').attr('disabled', false);
        if ((parseFloat(balance)) > 0 ) {
            $('.processIt').attr('disabled', true);
        }
        $('.total_tendered').html(parseFloat(payment_amount));
        total_tendered = parseFloat(payment_amount);
        var customerSelected = $('#route_customer').val();

        checkButtonState();
        // if ((parseFloat(total)) === 0 || !customerSelected) {
        //     $('#continuePayment').attr('disabled', true);
        // }else {
        //     $('#continuePayment').attr('disabled', false);
        // }

        var $amountInputs = $('.amount');
        let no_cash = true;
        let non_cah_amount = 0;
        $amountInputs.each(function(index) {
            var $amountInput = $(this);
            var methodTitle = $amountInput.data('method-title');
            var is_cash = $amountInput.data('method-cash');
            if (!is_cash && $amountInput.val() === '') {
                var inputName =$amountInput.attr('name');
                var item_id = inputName.substring(inputName.indexOf("[") + 1, inputName.indexOf("]"));
                $('#payment_remarks\\[' + item_id + '\\]').val('');
            }

            if (!is_cash && $amountInput.val() !== '') {
               non_cah_amount += $amountInput.val()

            }

        });
        let amountNumber = parseFloat(total.replace(/,/g, ''));
        if (non_cah_amount > (parseFloat(amountNumber))   )
        {
            /*get permision  process-bank-overpayment*/
            @if(isset($permission['pos-cash-sales___process-bank-overpayment']))

            @else
            $('.processIt').attr('disabled', true);
            @endif

        }
        return balance;

    }

    var form; // Declare form variable at top level
    
    $(document).ready(function () {
       allowance =  @json($selling_allowance);
        let amount = $('#total_total').text();
        $('#top_total').text(amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        // Initialize form object
        form = new Form();
        
        // Attach all event handlers after DOM is ready
        $(document).on('keypress', ".quantity", function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                $(".Newrow").click();
            }
        });
        
        $(document).on('keypress', ".start_process", function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                $(".processIt").click();
            }
        });
        
        $(document).on('keypress', '.customer_name_enter', function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                makemefocus();
            }
        });
        
        $(document).on('keypress change', '.send_me_to_next_item', function (event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                makemefocus();
            }
        });
        
        $(document).on('click', '.btnUploadData', function (e) {
        e.preventDefault();
        $('#loader-on').show();
        var postData = new FormData();

        var url = $(this).parents('form').attr('action');
        postData.append('_token', $(document).find('input[name="_token"]').val());
        $.each($('#upload_data')[0].files, function (indexInArray, valueOfElement) {
            postData.append('upload_data[' + indexInArray + ']', $('#upload_data')[0].files[indexInArray]);
        });
        $.ajax({
            type: "POST",
            url: "{{route('pos-cash-sales.esd_upload')}}",
            data: postData,
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                $('#loader-on').hide();
                $('#upload_data').replaceWith('<input type="file" style="width: 80%" name="upload_data[]" id="upload_data" class="form-control" multiple accept="text/plain">');
                if (response.result === -1) {
                    form.errorMessage(response.message);
                } else {
                    form.successMessage(response.message);
                }
            }
        });
        });
        
        $(document).on('click', '.addExpense', function (e) {
            e.preventDefault();

            var errorDisplayed = false;
        var $amountInputs = $('.amount');
        var $referenceInputs = $('.reference');
        $amountInputs.each(function(index) {
            var $amountInput = $(this);
            var methodTitle = $amountInput.data('method-title');
            var is_cash = $amountInput.data('method-cash');
            var inputName =$amountInput.attr('name');
            var item_id = inputName.substring(inputName.indexOf("[") + 1, inputName.indexOf("]"));
            var remark = "payment_remarks"+'['+item_id+']';
            var error = "error"+'['+item_id+']';
            var $referenceInput = document.getElementById(remark);
            var $errorMessage = document.getElementById(error);

            if (!is_cash  && $amountInput.val() !== '' && $referenceInput.value === '') {
                e.preventDefault();
                errorDisplayed = true;
                $errorMessage.style.display = 'block';

            }
        });

        if (errorDisplayed) {
            $('#loader-on').hide(); // Hide loader if stopping request
            return; // Stop execution
        }

        const request_type = e.target.value;
        processSale(request_type)

        });
        
    }); // End of $(document).ready()
    
    function makemefocus() {
        // console.log($(".makemefocus"),'lll')
        if ($(".makemefocus")[0]) {
            $(".makemefocus")[0].focus();
        }
    }

    function processSale(request_type) {
        var $button = $('#process');
        var originalText = $button.html();
        var postData = new FormData($('#orderForm')[0]);
        var url = $('#orderForm').attr('action');
        var bal = checkBalance()
        if (request_type ==='mpesa')
        {
           let method_id =   $('#mpesa_pay').val()
            postData.append('mpesa_method_id', method_id);
        }
        postData.append('_token', $(document).find('input[name="_token"]').val());
        postData.append('tenderAmount', total_tendered);
        postData.append('balance', bal);
        postData.append('request_type', request_type);

        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        $.ajax({
            url: url,
            data: postData,
            contentType: false,
            cache: false,
            processData: false,
            method: 'POST',
            success: function (out) {
                $('#loader-on').hide();

                console.log(out)

                $(".remove_error").remove();
                if (out.result == 0) {
                    // Re-enable button on validation errors
                    $button.prop('disabled', false).html(originalText);
                    
                    for (let i in out.errors) {
                        var id = i.split(".");
                        if (id && id[1]) {
                            $("[name='" + id[0] + "[" + id[1] + "]']").parent().append('<label class="error d-block remove_error w-100" id="' + i + '_error">' + out.errors[i][0] + '</label>');
                        } else {
                            $("[name='" + i + "']").parent().append('<label class="error d-block remove_error w-100" id="' + i + '_error">' + out.errors[i][0] + '</label>');
                            $("." + i).parent().append('<label class="error d-block remove_error w-100" id="' + i + '_error">' + out.errors[i][0] + '</label>');
                        }
                    }
                    $('#modelId').modal('hide');
                }
                if (out.result === 1) {
                    form.successMessage(out.message);
                    // Disable all form buttons to prevent duplicate submissions
                    $('.addExpense').prop('disabled', true);
                    
                    if (out.location) {
                        // Only auto-print for "send_request" (process), not for "save"
                        if (out.requestty === 'send_request') {
                            console.log('Print URL:', out.location);
                            console.log('Request type:', out.requestty);
                            
                            // Close any open modals first
                            $('.modal').modal('hide');
                            $('body').removeClass('modal-open');
                            $('.modal-backdrop').remove();
                            
                            // Open receipt in new window - the print page will handle auto-print
                            var printWindow = window.open(out.location, 'PrintWindow', 'width=900,height=650,scrollbars=yes');
                            
                            if (printWindow) {
                                console.log('Print window opened successfully');
                                printWindow.focus();
                                // The print page will handle auto-printing and closing
                                
                                // Redirect main window to POS index after a short delay
                                setTimeout(function() {
                                    location.href = '{{ route($model.'.index') }}';
                                }, 1000);
                            } else {
                                console.error('Failed to open print window - popup may be blocked');
                                alert('Please allow popups for this site to print receipts. Redirecting to receipt page...');
                                // Fallback: open in same window
                                window.location.href = out.location;
                            }
                            return;
                        } else {
                            // For "save" action, redirect immediately without delay
                            location.href = out.location;
                            return;
                        }
                    }
                    sales_id = out.sales_id
                    connectToPusher()

                    /*open Listening Modal*/
                    $('#modelId').modal('hide');
                    $('#loadingModal').modal('show')
                }
                if (out.result === -1) {
                    // Re-enable button on server errors
                    $button.prop('disabled', false).html(originalText);
                    form.errorMessage(out.message);
                }
                if (out.result === -2) {
                    // Re-enable button on payment errors
                    $button.prop('disabled', false).html(originalText);
                    form.errorMessage(out.message);
                }
                if (out.result === 2) {
                    /*close payemnt modal*/
                    form.successMessage(out.message);
                    sales_id = out.sales_id
                    connectToPusher()

                    /*open Listening Modal*/
                    $('#modelId').modal('hide');
                    $('#loadingModal').modal('show')
                }
            },

            error: function (err) {
                $('#loader-on').hide();
                $(".remove_error").remove();
                // Re-enable button on AJAX errors
                $button.prop('disabled', false).html(originalText);
                form.errorMessage('Something went wrong');
            }
        });
    }

    let sales_id = null
    let urls = []
    function connectToPusher() {
        const pusher = new Pusher('{{ env("PUSHER_APP_KEY_POS") }}', {
            cluster:'{{ env("PUSHER_APP_CLUSTER_POS") }}',
            encrypted: true
        });

        const channel = pusher.subscribe('payments');
        console.log('Connecting to Channel: listing to order', sales_id);

        channel.bind('payments', function(data) {
            console.log('New order received:', data.paymentDetails.sales_id);
            console.log('Expected sales id:', sales_id);
            if (data.paymentDetails.sales_id == sales_id) {
                console.log('Expected order received. Closing connection.');
                pusher.unsubscribe('payments');
                pusher.disconnect();

                /*close modal and print receipt*/
                $('#loadingModal').modal('hide');


                // Print receipt
                const invoicePrintUrl = '{{ url('admin/pos-cash-sales/invoice/print') }}'+'/'+ btoa(sales_id);
                const disptachUrl = '{{ url('admin/pos-cash-sale/dispatch-slip') }}'+'/'+ sales_id;
                const displayUrl = '{{ url('admin/pos-cash-sale/dispatch-slip/display') }}'+'/'+ sales_id
                printBill(invoicePrintUrl);
                printDispatch(disptachUrl);
                printDispatch(displayUrl);
                location.href = '{{ route($model.'.index') }}';
            }

        });
    }

    $(document).ready(function () {
        $('#modelId').on('shown.bs.modal', function () {
            $(this).find('input, textarea, select').val('');
            $('.dynamic-input').eq(0).focus();
            let tot = $('#total_total').text();
            $('.cash_change').html(tot);
            $('.total_tendered').html('')
           let phon  =  $('#customer_fon').val();
            $('#mpesa_number').val(phon);
        });
        $('body').addClass('sidebar-collapse');
        $(".mlselec6t").select2();
        $(".mlselec6t_modal").select2({dropdownParent: $('.modal')});
        route_customer();

    });


    function printBill(slug) {
        jQuery.ajax({
            url: slug,
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
                // location.reload();
                {{--location.href = '{{ route($model.'.index') }}';--}}

            },
            error: function (xhr, status, error) {
                alert("Invoice Not signed. Try again Later");
            }
        });

    }
    function printWaiting(slug) {
        if (slug != null){
            jQuery.ajax({
                url: slug,
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
                    // location.reload();
                    location.href = '{{ route($model.'.index') }}';

                }
            });
        }


    }
    function printLoadings(id) {
        let dispatch = "{{ url('admin/pos-cash-sale/dispatch-slip') }}"+'/' +id
        let display = "{{ url('admin/pos-cash-sale/dispatch-slip/display') }}"+'/'+id


        printDispatch(dispatch)
        printDispatch(display)
    }
    function printDispatch(slug) {
        if (slug != null){
            jQuery.ajax({
                url: slug,
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


                }
            });
        }
    }

    // handle Payment pricess

    var ajaxInProgress = false;
    let active_method = null;
    let amount = 0;
    let totalAmount = 0;


    $(document).ready(function() {

        if ((parseFloat(totalAmount)) === 0 ) {
            $('#proceedButton').attr('disabled', true);
        }

        $(document).keydown(function(e) {
            if (e.which === 13) { // Check for Enter key
                e.preventDefault();
                const focusedElement = document.activeElement; // Get the currently focused element
                if(focusedElement && focusedElement.matches('.dynamic-input-method')){

                    var element =  $(document.activeElement);
                    var inputName =$(document.activeElement).attr('name');
                    var item_id = inputName.substring(inputName.indexOf("[") + 1, inputName.indexOf("]"));
                    var remark = "payment_remarks"+'['+item_id+']';
                    amount = $(document.activeElement).val();
                    var error = "error-amount"+'['+item_id+']';
                    var $errorMessage = document.getElementById(error);
                    active_method =item_id;


                    // var $referenceInput = document.getElementById(remark);
                    // var $errorMessage = document.getElementById(error);

                    /*show transactions  modal*/

                    if (/^\d+$/.test(amount) && parseInt(amount) > 0) {

                        $('#transactionsModal').modal('show');
                        $('#searchActive').val(amount);
                        loadData('active', amount);
                        fetchUsed();
                        fetchExpired();
                    } else {
                        // If invalid, show error message
                        var error = "error-amount"+'['+item_id+']';
                        var $errorMessage = document.getElementById(error);
                        errorDisplayed = true;
                        $errorMessage.style.display = 'block';

                    }



                }
            }
        })

        $('#proceedButton').on('click', function() {
            proceed();
        });

        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        var selectedTransactions = [];

        function loadData(tab, search = '') {
            url = "{{route('pos-cash-sales.check-payment')}}";

            $.ajax({
                url: url,
                method: 'GET',
                data: {
                    '_token': csrfToken,
                    'paymentMethod': active_method,
                    'amount': search
                },
                success: function(data) {
                    var tableBody = $('#activeTable tbody');
                    tableBody.empty();

                    // // Merge selected transactions with new data, giving priority to selected transactions
                    var mergedTransactions = selectedTransactions.concat(
                        data.items.filter(function(transaction) {
                            return !selectedTransactions.some(function(selected) {
                                return selected.id === transaction.id;
                            });
                        })
                    );

                    // Render the transactions
                    $.each(mergedTransactions, function(index, transaction) {
                        var isChecked = selectedTransactions.some(function(selected) {
                            return selected.id === transaction.id;
                        }) ? 'checked' : '';

                        var formattedDate = formatDate(transaction.created_at);

                        tableBody.append(
                            '<tr>' +
                            '<td>' + (index + 1) + '</td>' +  // Numbering the transactions
                            '<td>' + formattedDate + '</td>' +
                            '<td>' + transaction.paid_by + '</td>' +
                            '<td>' + transaction.reference + '</td>' +
                            '<td>' + transaction.amount.toFixed(2) + '</td>' +
                            "<td><input type='checkbox' class='form-check-input checkbox-item btn-view-details' data-id='" + transaction.id + "' " + isChecked + "></td>" +
                            '</tr>'
                        );
                    });

                    updateTotalAmount();
                    // Reattach the checkbox event handler after updating the table
                    attachCheckboxHandler();
                }
            });
        }

        function fetchUsed(){
            url = "{{route('pos-cash-sales.utilised-payment')}}";
            let uts = $('#searchUtilized').val()
            $.ajax({
                url: url,
                method: 'GET',
                data: {
                    '_token': csrfToken,
                    'search': uts
                },
                success: function(data) {
                    var tableBody = $('#utilizedTable tbody');
                    tableBody.empty();
                    // console.log(data.items)


                    $.each(data, function(index, transaction) {

                        var formattedDate = formatDate(transaction.use_time);
                        var paid_time = formatDate(transaction.created_at);
                        // console.log(transaction)
                        tableBody.append(
                            '<tr>' +
                            '<td>' + (index + 1) + '</td>' +  // Numbering the transactions
                            '<td>' + paid_time + '</td>' +
                            '<td>' + transaction.payment_method + '</td>' +
                            '<td>' + transaction.sales_no + '</td>' +
                            '<td>' + formattedDate + '</td>' +
                            '<td>' + transaction.paid_by + '</td>' +
                            '<td>' + transaction.cashier_name + '</td>' +
                            '<td>' + transaction.reference + '</td>' +
                            '<td>' + transaction.amount + '</td>' +
                            '</tr>'
                        );
                    });
                }
            });
        }
        function fetchExpired(){
            url = "{{route('pos-cash-sales.expired-payment')}}";
            let uts = $('#searchInactive').val()
            $.ajax({
                url: url,
                method: 'GET',
                data: {
                    '_token': csrfToken,
                    'search': uts
                },
                success: function(data) {
                    var tableBody = $('#inactiveTable tbody');
                    tableBody.empty();
                    // console.log(data.items)


                    $.each(data, function(index, transaction) {

                        console.log(transaction)

                        var formattedDate = formatDate(transaction.use_time);
                        var paid_time = formatDate(transaction.created_at);
                        // console.log(transaction)
                        tableBody.append(
                            '<tr>' +
                            '<td>' + (index + 1) + '</td>' +  // Numbering the transactions
                            '<td>' + paid_time + '</td>' +
                            '<td>' + transaction.channel + '</td>' +
                            '<td>' + transaction.paid_by + '</td>' +
                            '<td>' + transaction.reference + '</td>' +
                            '<td>' + transaction.amount + '</td>' +
                            '</tr>'
                        );
                    });
                }
            });
        }

       function attachCheckboxHandler() {
            $('.checkbox-item').off('change').on('change', function() {
                var transactionId = $(this).data('id');
                var transaction = selectedTransactions.find(function(txn) {
                    return txn.id === transactionId;
                });

                if ($(this).is(':checked')) {
                    if (!transaction) {
                        // If not already selected, add the transaction to the array
                        var row = $(this).closest('tr');
                        var newTransaction = {
                            id: transactionId,
                            created_at: row.find('td:eq(1)').text(),
                            paid_by: row.find('td:eq(2)').text(),
                            reference: row.find('td:eq(3)').text(),
                            amount: parseFloat(row.find('td:eq(4)').text())
                        };
                        selectedTransactions.push(newTransaction);
                    }
                } else {
                    // Remove the transaction from the array if unchecked
                    selectedTransactions = selectedTransactions.filter(function(txn) {
                        return txn.id !== transactionId;
                    });
                }

                updateTotalAmount();
            });
        }

       function updateTotalAmount() {
            totalAmount = selectedTransactions.reduce(function(sum, txn) {
                return sum + txn.amount;
            }, 0);

            $('.table-footer-total').text(totalAmount.toFixed(2));
            if ((parseFloat(totalAmount)) > 0 ) {
                $('#proceedButton').attr('disabled', false);
            }else {
                $('#proceedButton').attr('disabled', true);
            }
        }

       function formatDate(dateString) {
            var date = new Date(dateString);
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day = ('0' + date.getDate()).slice(-2);
            var hours = ('0' + date.getHours()).slice(-2);
            var minutes = ('0' + date.getMinutes()).slice(-2);
            var seconds = ('0' + date.getSeconds()).slice(-2);
            return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
        }
       function proceed() {
            // Calculate the total of selected transactions
            totalAmount = selectedTransactions.reduce(function(sum, txn) {
                return sum + txn.amount;
            }, 0);


            // Get the IDs of the selected transactions
            var selectedIds = selectedTransactions.map(function(txn) {
                return txn.id+'-'+txn.amount;
            }).join(',');


            // Assign total to the input with dynamic ID based on activeMethodId
            $('#payment_method\\[' + active_method + '\\]').val(totalAmount.toFixed(2)).trigger('change');

            // Assign selected IDs to the hidden input with dynamic ID based on activeMethodId
            $('#payment_remarks\\[' + active_method + '\\]').val(selectedIds);


            // Clear selected transactions array
            selectedTransactions = [];

            clearTable()


            // Update the total amount in the footer
            updateTotalAmount();
            $('#transactionsModal').modal('hide');
        }

       function clearTable(){
            // Clear the search field
            $('#searchActive').val('');
            $('#searchInactive').val('');
            $('#searchUtilized').val('');

            // Clear the table
            $('#activeTable tbody').empty();
            $('#inactiveTable tbody').empty();
            $('#utilizedTable tbody').empty();


        }

       $('#transactionsModal').on('hidden.bs.modal', function () {
            clearTable(); // Call the clear method when the modal is closed
        });

        // Load data on tab show
       $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var tab = $(e.target).attr('aria-controls');
            loadData(tab);
        });


       $('#searchActive').on('keyup', function(event) {
            if (event.keyCode === 13 ) {
                loadData('active', $(this).val());
            }
        });

       $('#searchInactive').on('keyup', function() {
            if (event.keyCode === 13 ) {
                fetchExpired()
            }
        });

       $('#searchUtilized').on('keyup', function() {
            if (event.keyCode === 13 ) {
                fetchUsed()
            }

        });


        let combined_sales_ids = []; // Array to store selected IDs
        let total_selected = 0;      // Variable to track the total of selected items
        let selected_items = [];
        $('#searchSaleInput').on('keyup', function(e) {
            if (e.which === 13) { // Enter key pressed
                let query = $(this).val();
                if (query.length > 3) {
                    // Make AJAX request
                    $.ajax({
                        url: '{{ route('pos-cash-sales.search-sale') }}', // replace with your route
                        method: 'GET',
                        data: { search: query },
                        success: function(response) {
                            // Display the results table
                            $('#resultsTable').show();
                            $('#resultsBody').empty();

                            // Merge selected items with the new response
                            var mergedSales = selected_items.concat(
                                response.filter(function(transaction) {
                                    return !selected_items.some(function(selected) {
                                        return selected.id === transaction.id;
                                    });
                                })
                            );

                            // Loop through results and append to the table
                            mergedSales.forEach(function(result) {
                                let isChecked = combined_sales_ids.includes(result.id) ? 'checked' : '';
                                var formattedDate = formatDate(result.created_at);
                                $('#resultsBody').append(`
                                <tr>
                                    <td>${formattedDate}</td>
                                    <td>${result.sales_no}</td>
                                    <td>${result.customer}</td>
                                    <td>${result.customer_phone_number}</td>
                                    <td>${ (result.total.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                })) }</td>
                                    <td><input type="checkbox" class="result-checkbox" data-sale='${JSON.stringify(result)}' data-id="${result.id}" data-total="${result.total}" ${isChecked}></td>
                                </tr>
                            `);
                            });
                        }
                    });
                }
            }
        });

        $(document).on('change', '.result-checkbox', function() {
            let resultId = $(this).data('id')
            const total = $('#total_total').html();
            let total_before_attachments =  parseFloat(total.replace(/,/g, ''));
            let resultTotal = parseFloat($(this).data('total'));
            let newTransaction = JSON.parse($(this).attr('data-sale'));


            if ($(this).is(':checked')) {
                if (!combined_sales_ids.includes(resultId)) {
                    combined_sales_ids.push(resultId);
                    total_selected += resultTotal;
                    selected_items.push(newTransaction);
                }
            } else {
                combined_sales_ids = combined_sales_ids.filter(id => id !== resultId);
                total_selected -= resultTotal;
                selected_items = selected_items.filter(item => item.id !== resultId);
            }


            let cumi = (parseFloat(total_before_attachments) + parseFloat(total_selected));
            $('.totalBeforeAttachments').text((total_selected.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })));
            $('.cumulativeTotal').text((cumi.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })));

        });

        $('#attachSalesBtn').on('click', function() {
            $('#attached_sales').val(combined_sales_ids.join(','));
            /*update the Total*/
            var total = $('.cumulativeTotal').html()
            $('#top_total').html((total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })));
            $('#total_total').html((total.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })));
            checkBalance();
            $('#searchModal').modal('hide')
        });
    });

</script>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
    var valueTest = null;
    var searchTimeout = null; // Debounce timer
    
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
        } else if (code == 38) {//Down Arrow
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
        } else if (valueTest != vale && (e.type == 'keyup' || e.type == 'click') && code != 13 && code != 38 && code != 40 && vale != '') {
            var $this = $(this);

            if (vale.length >= 3) {
                // Clear previous timeout to implement debouncing
                clearTimeout(searchTimeout);
                
                // Set new timeout - only execute after 300ms of no typing
                searchTimeout = setTimeout(function() {
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
                        },
                        error: function(xhr, status, error) {
                            console.error('Search error:', error);
                        }
                    });
                    valueTest = vale;
                }, 300); // 300ms debounce delay
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

    if (itemids.length >= 80) {
        form.errorMessage('Maximum Order Items Reached. Please Checkout');
        furtherCall = false;
        return;
    }

    $.each(itemids, function (indexInArray, valueOfElement) {
        if ($this.data('id') == $(valueOfElement).val()) {
            form.errorMessage('This Item is already added in list');
            furtherCall = false;
            return false; // Break the loop
        }
    });

    if (furtherCall) {
        $.ajax({
            type: "GET",
            url: "{{ route('pos-cash-sales.getInventryItemDetails') }}",
            data: {
                'id': $this.data('id'),
                'route_customer': ($('#route_customer').val() || $('.route_customer').val() || null)
            },
            success: function (data) {
                var taxOption = data.tax ? `<option value="${data.tax.id}" selected>${data.tax.title}</option>` : '';
                var newRow = `
                    <tr>
                        <td>
                            <input type="hidden" name="item_id[${data.id}]" class="itemid" value="${data.id}">
                            <input style="padding: 3px 3px;" type="text" class="testIn form-control" value="${data.stock_id_code}">
                            <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
                        </td>
                        <td><img src="${data.image_url}" style="width:50px"></td>
                        <td><input style="padding: 3px 3px;" readonly type="text" name="item_description[${data.id}]" class="form-control" value="${data.description}"></td>
                        <td>${data.quantity_in_stock}</td>
                        <td><input style="padding: 3px 3px;" readonly type="text" name="item_unit[${data.id}]" class="form-control" value="${data.unit || ''}" readonly></td>
                        <td><input style="padding: 3px 3px;" autofocus onkeyup="getTotal(this)" onchange="getTotal(this)" type="text" name="item_quantity[${data.id}]" data-counts="${data.item_count}" class="quantity form-control" value=""></td>
                        <td><input style="padding: 3px 3px;" ${data.edit_permission} onchange="getTotal(this)" onkeyup="getTotal(this)" readonly type="text" name="item_selling_price[${data.id}]" class="selling_price form-control send_me_to_next_item" value="${data.selling_price}"></td>
                        <td>
                            <select readonly class="form-control vat_list send_me_to_next_item" name="item_vat[${data.id}]" ${data.edit_permission}>${taxOption}</select>
                            <input type="hidden" class="vat_percentage" value="${data.tax_percentage}" name="item_vat_percentage[${data.id}]">
                        </td>
                        <td><input style="padding: 3px 3px;" ${data.edit_permission} onchange="getTotal(this)" onkeyup="getTotal(this)" type="text" name="item_discount_per[${data.id}]" class="discount_per form-control send_me_to_next_item" value="0.00" readonly></td>
                        <td><input style="padding: 3px 3px;" ${data.edit_permission} type="text" name="item_discount[${data.id}]" class="discount form-control send_me_to_next_item" value="0.00" readonly></td>
                        <td><span class="vat">0.00</span></td>
                        <td><span class="total">0.00</span></td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button>
                        </td>
                    </tr>
                `;

                $this.parents('tr').replaceWith(newRow);
                $('#mainItemTable tbody tr:first-child td:nth-child(6) input').focus();
                vat_list();
                totalofAllTotal();
            },
            error: function (xhr, status, error) {
                form.errorMessage('Failed to fetch item details. Please try again.');
                console.error('AJAX Error:', status, error);
            }
        });
    }
}

    $(document).on('click', '.deleteparent', function () {
        $(this).parents('tr').remove();
        totalofAllTotal()
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
            + '<td></td>'
            + '<td><button class="btn btn-danger btn-sm deleteparent"><i class="fa fa-trash" aria-hidden="true"></i></button></td>'
            + '</tr>');
        $('#mainItemTable tbody tr:first-child td:first-child input').focus();
        // makemefocus();
    });

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

    function getTotal(vara) {
        console.log(vara)
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

    $(document).on('keyup', '.discount', function (e) {
        var discount = $(this).val();
        if (discount < 0) {
            $(this).parents('tr').find('.discount').val(0);
            discount = 0;
        }
        var price = $(this).parents('tr').find('.selling_price').val();
        if (price < 0) {
            $(this).parents('tr').find('.selling_price').val(0);
            price = 0;
        }
        var quantity = $(this).parents('tr').find('.quantity').val();
        if (quantity <= 0) {
            $(this).parents('tr').find('.quantity').val('');
            quantity = 0;
        }
        var vat_percentage = $(this).parents('tr').find('.vat_percentage').val();
        if (vat_percentage < 0) {
            $(this).parents('tr').find('.vat_percentage').val(0);
            vat_percentage = 0;
        }
        var totalPriceBeforeDiscount = parseFloat(price) * parseFloat(quantity);
        var discount_per = (discount / totalPriceBeforeDiscount) * 100;
        var exclusive = ((parseFloat(price) * parseFloat(quantity)) - parseFloat(discount));
        var vat = parseFloat(exclusive) - parseFloat((parseFloat(exclusive) * 100) / (parseFloat(vat_percentage) + 100));
        var total = parseFloat(exclusive);
        $(this).parents('tr').find('.discount_per').val((discount_per).toFixed(2));
        $(this).parents('tr').find('.vat').html((vat).toFixed(2));
        $(this).parents('tr').find('.total').html((total).toFixed(2));
        totalofAllTotal();
    });

    $(document).on('change', '.quantity', function (e) {
        var qty = $(this).val();
        var inputName = $(this).attr('name');
        var item_id = inputName.substring(inputName.indexOf("[") + 1, inputName.indexOf("]"));
        var counts = $(this).data('counts');
        var $inputElement = $(this);
        $inputElement.next('.error-message').remove();

        console.log('count == '+counts)

        console.log('qty is decimal '+isDecimal(qty));
        if (isDecimal(qty))
        {
            if (counts !== null && counts !== '') {
                console.log('has count')
                if (!checkSplit(qty)) {
                    $inputElement.after('<span class="error-message" style="color: red;">Item cannot be split into given quantity</span>');
                }
            }else {
                console.log('No count')
                $inputElement.after('<span class="error-message" style="color: red;">Item cannot be sold into halves</span>');
            }
        }


        $.ajax({
            type: "GET",
            url: "{{route('pos-cash-sales.cal_discount')}}",
            data: {
                'item_id': item_id,
                'item_quantity': qty
            },
            success: function (response) {
                var discountName = 'item_discount['+response.item_id+']';
                var discId = $('input[name="' + discountName + '"]'); // Assuming `discId` is the selector for the input field where you want to set the discount value
                // $(discId).val(response.discount);
                $(discId).val(response.discount).trigger('keyup');
            }
        });
    });

    function isDecimal(num) {
        return num % 1 !== 0;
    }

    function checkSplit(number) {
        // Get the decimal part of the number using modulo operation
        var decimalPart = number % 1;

        // Check if the decimal part is one of the allowed values
        return [0.25, 0.5, 0.75].includes(decimalPart);
    }


    function totalofAllTotal() {
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
            total =  Math.ceil(parseFloat(total) + parseFloat($(valueOfElement).text()));
        });
        var total_exc = (parseFloat(total) - parseFloat(vat));
        $('#total_exclusive').html(total_exc.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $('#total_vat').html((vat.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})));
        $('#total_total').html((total.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })));
        $('#total_discount').html((discount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })));
        $('#top_total').text(total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        checkBalance();
    }

    var payment_method = function () {
        $("#payment_method").select2(
            {
                placeholder: 'Select Payment Method',
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
    var route_customer = function () {
        $(".route_customer").select2(
            {
                placeholder: 'Select Customer',
                minimumInputLength: 0, // Show customers immediately on dropdown open
                ajax: {
                    url: '{{route("pos.route_customer.dropdown")}}',
                    dataType: 'json',
                    type: "GET",
                    delay: 250, // Reduced delay for better responsiveness
                    cache: true, // Enable caching of results
                    data: function (params) {
                        return {
                            q: params.term || '', // Send empty string if no search term
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        
                        // Handle both old format (array) and new format (object with results)
                        var items = data.results || data;
                        
                        var res = items.map(function (item) {
                            return {
                                id: item.id,
                                text: item.title,
                                phone: item.phone
                            };
                        });
                        
                        return {
                            results: res,
                            pagination: {
                                more: (params.page * 30) < (data.total_count || 0)
                            }
                        };
                    }
                },
            });
    };
    $(".route_customer").on('change', function (e) {
        var selectedData = $(".route_customer").select2('data')[0]; // Get the selected item
        if (selectedData) {
            $('#customer_fon').val(selectedData.phone);
        }
    });
</script>


<script>
    $(document).ready(function () {
        /*shortcut to proceed to pay*/
        $(document).on('keydown', function (e) {
            if (e.which === 116) { //F5
                e.preventDefault();
                var total = $('#total_total').html(); // Assuming you have an input field with id 'total'
                var customerSelected = $('#route_customer').val();
                if ((parseFloat(total)) === 0 || customerSelected === null || customerSelected === '') {
                    $('#continuePayment').attr('disabled', true);
                } else {
                    $('#modelId').modal('show');
                }

            }
        });

        $(document).keydown(function(event) {
            if (event.which === 112) { // Check for F1 key (keyCode: 112)
                event.preventDefault();
                $('.route_customer').select2('open'); // Open the Select2 dropdown
            }
        });

        /*shortcut to focus on input for quantity*/
        $(document).on('keydown', function (e) {
            if (e.which === 32 && !$('input:focus').length) { //Space bar
                e.preventDefault()
                $('#mainItemTable tbody tr:first-child td:nth-child(6) input').focus()

            }
        });

        var ajaxInProgress = false;
        $(document).keydown(function(e) {
            if (e.which === 13) { // Check for Enter key
                e.preventDefault();
                const focusedElement = document.activeElement; // Get the currently focused element

                if (focusedElement && focusedElement.matches('#mainItemTable tbody tr:first-child td:nth-child(6) input')) {

                    $('.addNewrow').trigger('click'); // Trigger click on 'addNewrow' element
                }

            }
        });


    });
</script>



<script>

    $(document).on('click', '.mpesa_pay', function (e) {
        e.preventDefault();

        if ( validateMpesaNumber())
        {
            $('#mpesa_pay').attr('disabled', true);

            /*get payment method_id*/

            /*get the order Total, save it pending, then Complete the STK Push */
            processSale('mpesa')
        }

    });
    function validateMpesaNumber() {
        var number = $('#mpesa_number').val()
        // Remove any spaces, hyphens, or plus signs from the number
        const cleanedNumber = number.replace(/[\s+-]/g, '');

        // Regular expression to match Safaricom numbers only
        // const safaricomRegex = /^(?:254|0)?(7(0[0-9]|1[0-9]|2[0-9]|9[0-9]|4[0-9])|11[01])\d{6}$/;
        const safaricomRegex = /^(?:254|0)?(7(0[0-9]|1[0-9]|2[0-9]|4[0-3]|45|46|48|57|58|59|68|69|9[0-9])|11[0-5])\d{6}$/;


        // Test the cleaned number against the regex
        let result = safaricomRegex.test(cleanedNumber);
        if (!result)
        {
            $('#error-mpesa-number').html('Enter a valid Mpesa Number')
            document.getElementById('error-mpesa-number').style.display = 'block';

        }else {
            document.getElementById('error-mpesa-number').style.display = 'none';

        }
        return result;
    }

    $('#loadingModal').on('hidden.bs.modal', function () {
        $('#mpesa_pay').attr('disabled', false);
    });
    $('#modelId').on('hidden.bs.modal', function () {
        $('#mpesa_pay').attr('disabled', false);
    });
</script>

