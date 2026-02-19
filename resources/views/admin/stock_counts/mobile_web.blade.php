@extends('layouts.admin.admin')

@section('uniquepagestyle')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endsection

@section('content')
<style>
    .stock-item-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stock-item-card.counted {
        background: #e8f5e9;
        border-color: #4caf50;
    }
    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .item-code {
        font-weight: bold;
        color: #1976d2;
        font-size: 14px;
    }
    .item-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    .item-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
    }
    .system-qty {
        color: #666;
    }
    .count-input-group {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .count-input {
        flex: 1;
        padding: 10px;
        font-size: 16px;
        border: 2px solid #ddd;
        border-radius: 4px;
    }
    .count-input:focus {
        border-color: #1976d2;
        outline: none;
    }
    .btn-count {
        padding: 10px 20px;
        background: #4caf50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
    }
    .btn-count:hover {
        background: #45a049;
    }
    .btn-count:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .filter-section {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stats-bar {
        display: flex;
        justify-content: space-around;
        background: #1976d2;
        color: white;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .stat-item {
        text-align: center;
    }
    .stat-value {
        font-size: 24px;
        font-weight: bold;
    }
    .stat-label {
        font-size: 12px;
        opacity: 0.9;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    .badge-success {
        background: #4caf50;
        color: white;
    }
    .badge-warning {
        background: #ff9800;
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h3>Count Stocks</h3>
        </div>
    </div>

    <!-- User Info Bar -->
    <div class="alert alert-info" id="userInfoBar" style="display:none;">
        <strong id="userType"></strong>: <span id="userMessage"></span>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-value" id="totalItems">0</div>
            <div class="stat-label">Total Items</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" id="countedItems">0</div>
            <div class="stat-label">Counted</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" id="pendingItems">0</div>
            <div class="stat-label">Pending</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-3">
            <label>Store/Location</label>
            <select class="form-control" id="storeFilter" {{ $isStoreKeeper ? 'disabled' : '' }}>
                <option value="">Select Store</option>
            </select>
            @if($isStoreKeeper)
                <small class="text-muted">Your assigned store</small>
            @endif
        </div>
        <div class="col-md-3">
            <label>Bin/UOM</label>
            <select class="form-control" id="binFilter" {{ $isStoreKeeper ? 'disabled' : '' }}>
                <option value="">Select Bin</option>
            </select>
            @if($isStoreKeeper)
                <small class="text-muted">Your assigned bin</small>
            @endif
        </div>
        <div class="col-md-3">
            <label>Category</label>
            <select class="form-control" id="categoryFilter">
                <option value="">All Categories</option>
            </select>
        </div>
        <div class="col-md-3">
            <label>&nbsp;</label><br>
            <button class="btn btn-primary btn-block" id="loadItemsBtn">
                <i class="fa fa-refresh"></i> Load Items
            </button>
        </div>
    </div>

    <!-- Items List -->
    <div id="itemsList"></div>

    <!-- Submit All Button -->
    <div class="text-center" style="margin: 30px 0;">
        <button class="btn btn-success btn-lg" id="submitAllBtn" style="display:none;">
            <i class="fa fa-check"></i> Submit All Counts
        </button>
    </div>
</div>
@endsection

@section('uniquepagescript')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
console.log('Script loaded');

// Configure toastr
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "3000"
};

let items = [];
let countedItems = new Set();
let itemCounts = {};

// Check if jQuery is loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded!');
    alert('jQuery is not loaded. Please refresh the page.');
}

$(document).ready(function() {
    console.log('Document ready');
    
    try {
        loadInitialData();
    } catch(e) {
        console.error('Error loading initial data:', e);
        alert('Error loading data: ' + e.message);
    }
    
    $('#loadItemsBtn').click(function() {
        loadStockTakeItems();
    });

    $('#submitAllBtn').click(function() {
        submitAllCounts();
    });

    // Filter by category
    $('#categoryFilter').change(function() {
        renderItems();
    });
});

function loadInitialData() {
    console.log('loadInitialData called');
    
    const isStoreKeeper = {{ $isStoreKeeper ? 'true' : 'false' }};
    const preSelectedStore = {{ $preSelectedStore ?? 'null' }};
    const preSelectedBin = {{ $preSelectedBin ?? 'null' }};
    
    // Load all stores
    const stores = @json($stores ?? []);
    console.log('Stores data:', stores);
    
    if (stores && stores.length > 0) {
        stores.forEach(store => {
            $('#storeFilter').append(`<option value="${store.id}">${store.location_name}</option>`);
        });
        console.log('Added', stores.length, 'stores to dropdown');
    } else {
        console.error('No stores data available');
    }
    
    // Load all bins/UOMs
    const bins = @json($bins ?? []);
    console.log('Bins data:', bins);
    
    if (bins && bins.length > 0) {
        bins.forEach(bin => {
            $('#binFilter').append(`<option value="${bin.id}">${bin.title}</option>`);
        });
        console.log('Added', bins.length, 'bins to dropdown');
    } else {
        console.error('No bins data available');
    }
    
    // Load all categories
    const categories = @json($categories ?? []);
    console.log('Categories data:', categories);
    
    if (categories && categories.length > 0) {
        categories.forEach(cat => {
            $('#categoryFilter').append(`<option value="${cat.id}">${cat.category_description}</option>`);
        });
        console.log('Added', categories.length, 'categories to dropdown');
    } else {
        console.error('No categories data available');
    }
    
    console.log('Loaded:', stores.length, 'stores,', bins.length, 'bins,', categories.length, 'categories');
    
    // Pre-select for Store Keepers
    if (isStoreKeeper && preSelectedStore && preSelectedBin) {
        $('#storeFilter').val(preSelectedStore);
        $('#binFilter').val(preSelectedBin);
        
        // Auto-load items for store keepers
        toastr.info('Auto-loading items for your store and bin...');
        setTimeout(function() {
            loadStockTakeItems();
        }, 500);
    }
}

function loadStockTakeItems() {
    const store = $('#storeFilter').val();
    const bin = $('#binFilter').val();
    
    if (!store || !bin) {
        toastr.warning('Please select Store and Bin');
        return;
    }

    $('#loadItemsBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

    $.get('{{ route("admin.stock-counts.mobile-web.get-items") }}', {
        store: store,
        bin: bin
    }).done(function(response) {
        if (response.status) {
            items = response.items || [];
            
            // Show user type and access info
            if (response.user_type) {
                $('#userType').text(response.user_type);
                $('#userMessage').text(response.message || '');
                $('#userInfoBar').show();
            }
            
            renderItems();
            toastr.success(`Loaded ${items.length} items`);
        } else {
            toastr.error(response.message || 'Failed to load items');
        }
    }).fail(function(xhr) {
        toastr.error('Failed to load items: ' + (xhr.responseJSON?.message || 'Server error'));
    }).always(function() {
        $('#loadItemsBtn').prop('disabled', false).html('<i class="fa fa-refresh"></i> Load Items');
    });
}

function renderItems() {
    const categoryFilter = $('#categoryFilter').val();
    let filteredItems = items;
    
    if (categoryFilter) {
        filteredItems = items.filter(item => item.category_id == categoryFilter);
    }

    let html = '';
    filteredItems.forEach((item, index) => {
        const isCounted = countedItems.has(item.id);
        const countValue = itemCounts[item.id] || '';
        
        html += `
            <div class="stock-item-card ${isCounted ? 'counted' : ''}" data-item-id="${item.id}">
                <div class="item-header">
                    <span class="item-code">${item.stock_id_code}</span>
                    ${isCounted ? '<span class="badge badge-success">✓ Counted</span>' : '<span class="badge badge-warning">Pending</span>'}
                </div>
                <div class="item-title">${item.title}</div>
                <div class="item-info">
                    <span class="system-qty">System QOH: <strong>${parseFloat(item.system_qoh || 0).toFixed(2)}</strong></span>
                </div>
                <div class="count-input-group">
                    <input type="number" 
                           step="0.01" 
                           min="0" 
                           class="count-input" 
                           id="count_${item.id}" 
                           placeholder="Enter count (decimals allowed: 5.5, 10.25)"
                           value="${countValue}"
                           ${isCounted ? 'disabled' : ''}>
                    <input type="text" 
                           class="count-input" 
                           id="ref_${item.id}" 
                           placeholder="Reference (optional)"
                           ${isCounted ? 'disabled' : ''}>
                    <button class="btn-count" 
                            onclick="countItem(${item.id})"
                            ${isCounted ? 'disabled' : ''}>
                        ${isCounted ? 'Counted' : 'Count'}
                    </button>
                </div>
            </div>
        `;
    });

    $('#itemsList').html(html || '<p class="text-center">No items to count</p>');
    updateStats();
    
    if (countedItems.size > 0) {
        $('#submitAllBtn').show();
    }
}

function countItem(itemId) {
    const countInput = $(`#count_${itemId}`);
    const refInput = $(`#ref_${itemId}`);
    const count = parseFloat(countInput.val());
    
    if (isNaN(count) || count < 0) {
        toastr.error('Please enter a valid quantity (decimals allowed)');
        return;
    }

    // Store the count
    itemCounts[itemId] = count;
    countedItems.add(itemId);
    
    // Show variance
    const item = items.find(i => i.id == itemId);
    if (item) {
        const systemQty = parseFloat(item.system_qoh || 0);
        const variance = count - systemQty;
        const varianceText = variance >= 0 ? `+${variance.toFixed(2)}` : variance.toFixed(2);
        
        toastr.success(`Counted: ${count.toFixed(2)} | System: ${systemQty.toFixed(2)} | Variance: ${varianceText}`);
    }
    
    renderItems();
}

function updateStats() {
    $('#totalItems').text(items.length);
    $('#countedItems').text(countedItems.size);
    $('#pendingItems').text(items.length - countedItems.size);
}

function submitAllCounts() {
    if (countedItems.size === 0) {
        toastr.warning('No items counted yet');
        return;
    }

    const store = $('#storeFilter').val();
    const bin = $('#binFilter').val();
    
    if (!store || !bin) {
        toastr.error('Please select Store and Bin');
        return;
    }

    // Prepare data
    const itemIds = [];
    const quantities = [];
    const references = [];
    
    countedItems.forEach(itemId => {
        itemIds.push(itemId);
        quantities.push(itemCounts[itemId] || 0);
        references.push($(`#ref_${itemId}`).val() || '');
    });

    const data = {
        token: '{{ session("api_token") ?? "test" }}',
        store: store,
        bin: bin,
        item_id: itemIds,
        item_quantity: quantities,
        item_reference: references
    };

    $('#submitAllBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');

    $.ajax({
        url: '{{ route("admin.stock-counts.mobile-web.submit") }}',
        method: 'POST',
        data: data,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.status) {
                toastr.success('Stock counts submitted successfully!');
                // Reset
                countedItems.clear();
                itemCounts = {};
                loadStockTakeItems();
            } else {
                toastr.error(response.message || 'Failed to submit');
            }
        },
        error: function(xhr) {
            const error = xhr.responseJSON?.message || 'Failed to submit counts';
            toastr.error(error);
        },
        complete: function() {
            $('#submitAllBtn').prop('disabled', false).html('<i class="fa fa-check"></i> Submit All Counts');
        }
    });
}
</script>
@endsection
