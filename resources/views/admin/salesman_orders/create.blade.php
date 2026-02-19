@extends('layouts.admin.admin')

@section('content')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
/* Custom styling for Select2 customer results */
.select2-result-customer {
    padding: 5px 0;
}

.select2-result-customer__meta {
    margin-left: 0;
}

.select2-result-customer__title {
    font-weight: bold;
    color: #333;
    margin-bottom: 3px;
}

.select2-result-customer__description {
    font-size: 12px;
    color: #666;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] .select2-result-customer__title {
    color: #fff;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] .select2-result-customer__description {
    color: #f0f0f0;
}

/* Make Select2 dropdown wider for better readability */
.select2-container {
    width: 100% !important;
}

.select2-dropdown {
    min-width: 400px;
}
</style>

<section class="content">
    <div class="session-message-container">
        @include('message')
    </div>

    <form id="orderForm">
        @csrf
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-plus"></i> Create New Order
                </h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('salesman-orders.index') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="box-body">
                <!-- Order Header Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Salesman</label>
                            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="text" class="form-control" value="{{ date('Y-m-d') }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Route</label>
                            <input type="text" class="form-control" value="{{ $user->routes()->first()->route_name ?? 'Not Assigned' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Shift Type</label>
                            <input type="text" class="form-control" value="{{ ucfirst($activeShift->shift_type) }}" readonly>
                        </div>
                    </div>
                </div>

                <!-- Customer Selection -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Select Customer <span class="text-red">*</span></label>
                            <select name="wa_route_customer_id" id="customerSelect" class="form-control" required>
                                <option value="">Search for a customer...</option>
                            </select>
                            <small class="text-muted">Start typing to search by customer name, business name, or phone number</small>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Display -->
                <div id="customerDetails" class="row" style="display: none;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <strong>Customer Details:</strong>
                            <span id="customerInfo"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items Section -->
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-shopping-cart"></i> Order Items
                </h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-success btn-sm addNewrow">
                    </button>
                </div>
            </div>

            <div class="box-body">
                <button type="button" class="btn btn-success btn-sm Newrow" 
                        style="position: fixed;bottom: 30%;right:4%;">
                    <i class="fa fa-plus" aria-hidden="true"></i> Add Item
                </button>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="mainItemTable">
                        <thead>
                            <tr>
                                <th>Selection <span style="color: red;">(Search Atleast 3 Keywords)</span></th>
                                <th>Description</th>
                                <th style="width: 90px;">Bal Stock</th>
                                <th style="width: 90px;">Unit</th>
                                <th style="width: 90px;">QTY</th>
                                <th>Selling Price</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="text" autofocus placeholder="Search Atleast 3 Keywords"
                                           class="testIn form-control makemefocus">
                                    <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
                                </td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" style="text-align: right;">Total Price</th>
                                <th colspan="2">KES <span id="grandTotal">0.00</span></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="box box-info">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="fa fa-save"></i> Create Order
                        </button>
                        <a href="{{ route('salesman-orders.index') }}" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>

<script>
// Initialize vanilla JavaScript functionality for item search
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Setup vanilla JavaScript functionality
        initializeAllSearchInputs();
        setupCustomerHandler();
        setupTableHandlers();
    });
} else {
    // jQuery not available, use vanilla JavaScript
    console.log('jQuery not available, using vanilla JavaScript');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up vanilla JavaScript...');
        initializeAllSearchInputs();
        setupCustomerHandler();
        setupTableHandlers();
    });
}
    
// Customer selection change handler
function setupCustomerHandler() {
    const customerSelect = document.querySelector('#customerSelect');
    const customerDetails = document.querySelector('#customerDetails');
    const customerInfo = document.querySelector('#customerInfo');
    
    if (customerSelect && customerDetails && customerInfo) {
        customerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const phone = selectedOption.getAttribute('data-phone');
                const town = selectedOption.getAttribute('data-town');
                customerInfo.textContent = `Phone: ${phone}, Town: ${town}`;
                customerDetails.style.display = 'block';
            } else {
                customerDetails.style.display = 'none';
            }
        });
    }
}

// Setup table event handlers
function setupTableHandlers() {
    // Remove item handler
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item')) {
            e.target.closest('tr').remove();
            calculateGrandTotal();
        }
        
        // Add new row handler
        if (e.target.classList.contains('Newrow')) {
            addNewSearchRow();
        }
    });
    
    // Handle quantity and price changes
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity')) {
            const row = e.target.closest('tr');
            const itemId = row.querySelector('input[name*="wa_inventory_item_id"]').value;
            const quantity = parseFloat(e.target.value) || 0;
            
            if (quantity > 0) {
                // Check for discount bands
                checkDiscountBand(itemId, quantity, row);
            } else {
                calculateRowTotal(row);
                calculateGrandTotal();
            }
        } else if (e.target.classList.contains('selling_price')) {
            const row = e.target.closest('tr');
            calculateRowTotal(row);
            calculateGrandTotal();
        }
    });
    
    // Handle form submission
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitOrder();
        });
    }
}

// Add new search row function
function addNewSearchRow() {
    const tableBody = document.querySelector('#mainItemTable tbody');
    if (!tableBody) return;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <input type="text" autofocus placeholder="Search Atleast 3 Keywords" class="testIn form-control makemefocus">
            <div class="textData" style="width: 100%;position: relative;z-index: 99;"></div>
        </td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-item">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    `;
    
    tableBody.appendChild(newRow);
    
    // Setup search for the new input
    setupSearchForInput(newRow.querySelector('.testIn'));
}

// Setup search for a specific input
function setupSearchForInput(searchInput) {
    if (!searchInput) return;
    
    const searchResults = searchInput.nextElementSibling;
    
    searchInput.addEventListener('keyup', function(e) {
        const search = this.value;
        console.log('Search triggered with value:', search);
        
        if (search.length >= 3) {
            console.log('Making search request...');
            const searchUrl = "{{ route('salesman-orders.search-inventory') }}?" + new URLSearchParams({
                'search': search,
                'store_location_id': {{ Auth::user()->wa_location_and_store_id ?? 'null' }}
            });
            console.log('Search URL:', searchUrl);
            
            fetch(searchUrl, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response data:', data);
                if (data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        html += `<div class="search-item" onclick="addItemToCart('${item.id}', '${item.item_name}', '${item.unit_name}', '${item.available_stock}', '${item.selling_price}')" style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer; background: white;">
                            <strong>${item.item_name}</strong><br>
                            <small>Stock: ${item.available_stock} ${item.unit_name} | Price: ${item.selling_price}</small>
                        </div>`;
                    });
                    searchResults.innerHTML = html;
                    searchResults.style.display = 'block';
                } else {
                    searchResults.innerHTML = '<div style="padding: 8px; background: white;">No items found</div>';
                    searchResults.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Search failed:', error);
                searchResults.innerHTML = '<div style="padding: 8px; background: white; color: red;">Search failed: ' + error.message + '</div>';
                searchResults.style.display = 'block';
            });
        } else {
            searchResults.style.display = 'none';
        }
    });
}

// Submit order function
function submitOrder() {
    const customerSelect = document.getElementById('customerSelect');
    if (!customerSelect || !customerSelect.value) {
        alert('Please select a customer');
        return;
    }
    
    // Collect order items from table
    const items = [];
    const rows = document.querySelectorAll('#mainItemTable tbody tr');
    
    rows.forEach(row => {
        const itemIdInput = row.querySelector('input[name*="wa_inventory_item_id"]');
        const qtyInput = row.querySelector('.quantity');
        const priceInput = row.querySelector('.selling_price');
        const discountInput = row.querySelector('.discount');
        
        if (itemIdInput && qtyInput && parseFloat(qtyInput.value) > 0) {
            items.push({
                wa_inventory_item_id: itemIdInput.value,
                quantity: parseFloat(qtyInput.value),
                selling_price: parseFloat(priceInput.value),
                discount: parseFloat(discountInput ? discountInput.value : 0)
            });
        }
    });
    
    if (items.length === 0) {
        alert('Please add at least one item to the order');
        return;
    }
    
    const orderData = {
        wa_route_customer_id: customerSelect.value,
        items: items
    };
    
    // Show loading
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Creating Order...';
    }
    
    fetch('{{ route("salesman-orders.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order created successfully!');
            window.location.href = '{{ route("salesman-orders.index") }}';
        } else {
            alert(data.message || 'Error creating order');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-save"></i> Create Order';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error creating order');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-save"></i> Create Order';
        }
    });
}

// Initialize all search inputs on page load
function initializeAllSearchInputs() {
    const searchInputs = document.querySelectorAll('.testIn');
    searchInputs.forEach(input => {
        setupSearchForInput(input);
    });
    
    // Hide search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.testIn') && !e.target.closest('.textData')) {
            document.querySelectorAll('.textData').forEach(results => {
                results.style.display = 'none';
            });
        }
    });
}

// Add item to cart function
function addItemToCart(itemId, itemName, unitName, availableStock, sellingPrice) {
    console.log('Adding item to cart:', itemId, itemName);
    
    // Find the active search row (the one that was clicked)
    const activeSearchResults = document.querySelector('.textData[style*="block"]');
    let searchRow = null;
    
    if (activeSearchResults) {
        searchRow = activeSearchResults.closest('tr');
        // Hide search results
        activeSearchResults.style.display = 'none';
        
        // Clear search input
        const searchInput = searchRow.querySelector('.testIn');
        if (searchInput) {
            searchInput.value = '';
        }
    }
    
    // Create the item row HTML
    const itemRowHTML = `
        <td>
            <input type="hidden" name="items[${itemId}][wa_inventory_item_id]" value="${itemId}">
            <input type="hidden" class="discount" name="items[${itemId}][discount]" value="0">
            <strong>${itemName}</strong>
        </td>
        <td>${itemName}</td>
        <td class="available-stock">${availableStock}</td>
        <td>${unitName}</td>
        <td><input type="number" name="items[${itemId}][quantity]" class="form-control quantity" min="1" max="${availableStock}" value="1"></td>
        <td><input type="number" name="items[${itemId}][selling_price]" class="form-control selling_price" value="${sellingPrice}"></td>
        <td class="total-cost">0.00</td>
        <td><button type="button" class="btn btn-danger btn-sm remove-item">Remove</button></td>
    `;
    
    if (searchRow) {
        // Replace the search row with the item row
        searchRow.innerHTML = itemRowHTML;
    } else {
        // Fallback: add to table body
        const tableBody = document.querySelector('#mainItemTable tbody');
        if (!tableBody) {
            console.error('Table body not found!');
            return;
        }
        
        const newRow = document.createElement('tr');
        newRow.innerHTML = itemRowHTML;
        tableBody.appendChild(newRow);
        searchRow = newRow;
    }
    
    // Check for discount bands on initial quantity (1)
    if (searchRow) {
        const itemIdInput = searchRow.querySelector('input[name*="wa_inventory_item_id"]');
        const quantityInput = searchRow.querySelector('.quantity');
        
        if (itemIdInput && quantityInput) {
            const itemId = itemIdInput.value;
            const quantity = parseFloat(quantityInput.value) || 1;
            
            // Check discount band for initial quantity
            checkDiscountBand(itemId, quantity, searchRow);
        }
    } else {
        // Fallback calculation
        calculateRowTotal(searchRow);
        calculateGrandTotal();
    }
}

function calculateRowTotal(row) {
    const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
    const price = parseFloat(row.querySelector('.selling_price').value) || 0;
    
    const total = quantity * price;
    row.querySelector('.total-cost').textContent = total.toFixed(2);
}

function calculateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.total-cost').forEach(cell => {
        grandTotal += parseFloat(cell.textContent) || 0;
    });
    
    const totalElement = document.querySelector('#grandTotal');
    if (totalElement) {
        totalElement.textContent = grandTotal.toFixed(2);
    }
}

function checkDiscountBand(itemId, quantity, row) {
    console.log('Checking discount band for item:', itemId, 'quantity:', quantity);
    
    // Make AJAX call to check for discount bands
    fetch(`{{ route('salesman-orders.calculate-discount') }}?inventory_item_id=${itemId}&item_quantity=${quantity}`)
        .then(response => response.json())
        .then(data => {
            console.log('Discount response:', data);
            
            if (data.result === 1) {
                const priceInput = row.querySelector('.selling_price');
                const discountInput = row.querySelector('.discount');
                const originalPrice = data.original_price;
                const discountedPrice = data.discounted_price;
                const totalDiscount = data.discount || 0;
                
                // Update the price input with discounted price
                if (discountedPrice < originalPrice) {
                    priceInput.value = discountedPrice.toFixed(2);
                    priceInput.style.backgroundColor = '#e8f5e8'; // Light green to show discount applied
                    
                    // Update hidden discount field
                    if (discountInput) {
                        discountInput.value = totalDiscount;
                    }
                    
                    // Show discount information
                    let discountInfo = row.querySelector('.discount-info');
                    if (!discountInfo) {
                        discountInfo = document.createElement('small');
                        discountInfo.className = 'discount-info text-success';
                        priceInput.parentNode.appendChild(discountInfo);
                    }
                    discountInfo.innerHTML = `<br>Original: KSh ${originalPrice}<br>${data.discount_description}`;
                } else {
                    priceInput.value = originalPrice.toFixed(2);
                    priceInput.style.backgroundColor = '';
                    
                    // Reset discount field
                    if (discountInput) {
                        discountInput.value = 0;
                    }
                    
                    // Remove discount info if exists
                    const discountInfo = row.querySelector('.discount-info');
                    if (discountInfo) {
                        discountInfo.remove();
                    }
                }
                
                // Recalculate totals
                calculateRowTotal(row);
                calculateGrandTotal();
            } else {
                console.error('Error calculating discount:', data.errors || data.message);
            }
        })
        .catch(error => {
            console.error('Error checking discount band:', error);
            // Fallback to normal calculation
            calculateRowTotal(row);
            calculateGrandTotal();
        });
}
</script>

<style>
.textData {
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.textData table tr:hover, .SelectedLi {
    background: #000 !important;
    color: white !important;
    cursor: pointer !important;
}

.textData table {
    margin-bottom: 0;
}

.textData table td {
    padding: 8px;
    border-bottom: 1px solid #eee;
}

.qty-input {
    width: 80px;
}

#mainItemTable td {
    vertical-align: middle;
}

#mainItemTable input[type="number"] {
    width: 100%;
    padding: 3px;
}

.form-group {
    position: relative;
}
</style>

@endsection

@section('uniquepagescript')
<!-- Select2 JS - Load in script section -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
console.log('=== UNIQUE PAGE SCRIPT SECTION LOADED ===');
console.log('jQuery available:', typeof $ !== 'undefined');
console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');

// Wait for everything to be ready
$(document).ready(function() {
    console.log('=== SELECT2 INITIALIZATION DEBUG ===');
    console.log('Document ready - jQuery loaded:', typeof $);
    console.log('jQuery version:', $.fn.jquery);
    console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');
    console.log('Customer select element exists:', $('#customerSelect').length);
    console.log('Customer select element:', $('#customerSelect')[0]);
    
    // Check if Select2 is available
    if (typeof $.fn.select2 === 'undefined') {
        console.error('Select2 is not loaded!');
        alert('Select2 library is not loaded. Customer search will not work.');
        return;
    }
    
    // Check if customer select exists
    if ($('#customerSelect').length === 0) {
        console.error('Customer select element not found!');
        return;
    }
    
    console.log('Initializing Select2 on #customerSelect...');
    
    // Format customer display in dropdown - MUST BE DEFINED BEFORE SELECT2 INIT
    function formatCustomer(customer) {
        if (customer.loading) {
            return customer.text;
        }
        
        var $container = $(
            "<div class='select2-result-customer clearfix'>" +
                "<div class='select2-result-customer__meta'>" +
                    "<div class='select2-result-customer__title'></div>" +
                    "<div class='select2-result-customer__description'></div>" +
                "</div>" +
            "</div>"
        );
        
        $container.find(".select2-result-customer__title").text(customer.text);
        
        var description = '';
        if (customer.phone) {
            description += 'Phone: ' + customer.phone;
        }
        if (customer.town) {
            description += (description ? ' | ' : '') + 'Town: ' + customer.town;
        }
        
        if (description) {
            $container.find(".select2-result-customer__description").text(description);
        }
        
        return $container;
    }

    // Format selected customer - MUST BE DEFINED BEFORE SELECT2 INIT
    function formatCustomerSelection(customer) {
        return customer.text || customer.bussiness_name;
    }
    
    // Initialize Select2 with AJAX search for customer dropdown
    $('#customerSelect').select2({
        ajax: {
            url: '{{ route("salesman-orders.search-customers") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                console.log('Select2 AJAX - Search term:', params.term);
                return {
                    q: params.term, // search term
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                console.log('Select2 AJAX - Response data:', data);
                params.page = params.page || 1;
                return {
                    results: data.results,
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            error: function(xhr, status, error) {
                console.error('Select2 AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
            },
            cache: true
        },
        placeholder: 'Search for a customer...',
        minimumInputLength: 0,
        allowClear: true,
        templateResult: formatCustomer,
        templateSelection: formatCustomerSelection,
        language: {
            searching: function() {
                return "Searching customers...";
            },
            noResults: function() {
                return "No customers found";
            },
            errorLoading: function() {
                return "Error loading customers";
            }
        }
    });
    
    console.log('✓ Select2 successfully initialized on #customerSelect');
    console.log('Select2 instance:', $('#customerSelect').data('select2'));
    console.log('=== END SELECT2 INITIALIZATION ===');

    // Handle customer selection change
    $('#customerSelect').on('select2:select', function (e) {
        var data = e.params.data;
        var customerInfo = '';
        
        if (data.phone) {
            customerInfo += 'Phone: ' + data.phone;
        }
        if (data.town) {
            customerInfo += (customerInfo ? ', ' : '') + 'Town: ' + data.town;
        }
        
        if (customerInfo) {
            $('#customerInfo').text(customerInfo);
            $('#customerDetails').show();
        }
    });

    // Handle customer deselection
    $('#customerSelect').on('select2:clear', function (e) {
        $('#customerDetails').hide();
    });
});
</script>
@endsection
