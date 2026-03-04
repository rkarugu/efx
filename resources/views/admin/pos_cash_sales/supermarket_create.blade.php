@extends('layouts.admin.admin')
@section('content')
<style>
    /* Supermarket POS Styles */
    .pos-container {
        display: flex;
        height: calc(100vh - 100px);
        background: #f5f7fa;
        margin: -15px;
    }

    .pos-left {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-right: 1px solid #e0e6ed;
    }

    .pos-right {
        width: 450px;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    /* Header Section */
    .pos-header {
        padding: 20px;
        border-bottom: 1px solid #e0e6ed;
        background: #fff;
    }

    .pos-header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .customer-badge {
        background: #e3f2fd;
        color: #1976d2;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Search Section */
    .pos-search {
        padding: 20px;
        border-bottom: 1px solid #e0e6ed;
    }

    .search-wrapper {
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 12px 45px 12px 45px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s;
    }

    .search-input:focus {
        border-color: #03db1cac;
        outline: none;
        box-shadow: 0 0 0 3px rgba(3, 219, 28, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }

    .barcode-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: #03db1cac;
        color: #fff;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
    }

    /* Categories */
    .pos-categories {
        padding: 15px 20px;
        border-bottom: 1px solid #e0e6ed;
        display: flex;
        gap: 10px;
        overflow-x: auto;
        white-space: nowrap;
    }

    .category-btn {
        padding: 8px 16px;
        border: 1px solid #e0e6ed;
        background: #fff;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }

    .category-btn:hover {
        background: #f5f7fa;
    }

    .category-btn.active {
        background: #03db1cac;
        color: #fff;
        border-color: #03db1cac;
    }

    /* Products Grid */
    .pos-products {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        -webkit-overflow-scrolling: touch;
        contain: layout style;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
        contain: layout style;
    }

    .product-card {
        background: #fff;
        border: 1px solid #e0e6ed;
        border-radius: 10px;
        padding: 15px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        position: relative;
        contain: layout style paint;
        will-change: transform;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-color: #03db1cac;
    }

    .product-image {
        width: 100%;
        height: 120px;
        background: #f5f7fa;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        overflow: hidden;
        contain: strict;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .product-image i {
        font-size: 40px;
        color: #ccc;
    }

    .product-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 5px;
        color: #333;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .product-price {
        font-size: 18px;
        font-weight: 700;
        color: #03db1cac;
        margin-bottom: 5px;
    }

    .product-stock {
        font-size: 12px;
        color: #999;
    }

    .product-add-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        background: #03db1cac;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .product-card:hover .product-add-icon {
        opacity: 1;
    }

    /* Cart Section */
    .cart-header {
        padding: 20px;
        border-bottom: 1px solid #e0e6ed;
    }

    .cart-title {
        font-size: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .cart-item {
        background: #f5f7fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .cart-item-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 10px;
        gap: 12px;
    }

    .cart-item-image-wrapper {
        width: 60px;
        height: 60px;
        background: #f5f7fa;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .cart-item-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .cart-item-name {
        font-weight: 600;
        color: #333;
        flex: 1;
    }

    .cart-item-price {
        font-size: 12px;
        color: #999;
    }

    .cart-item-remove {
        color: #f44336;
        cursor: pointer;
        padding: 4px;
    }

    .cart-item-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .qty-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #e0e6ed;
        background: #fff;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .qty-btn:hover {
        background: #f5f7fa;
    }

    .qty-input {
        width: 60px;
        text-align: center;
        border: 1px solid #e0e6ed;
        border-radius: 6px;
        padding: 6px;
        font-weight: 600;
    }

    .cart-item-total {
        font-weight: 700;
        color: #333;
    }

    .discount-input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #e0e6ed;
        border-radius: 6px;
        font-size: 13px;
    }

    .cart-empty {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .cart-empty i {
        font-size: 60px;
        margin-bottom: 15px;
        opacity: 0.3;
    }

    /* Cart Summary */
    .cart-summary {
        padding: 20px;
        border-top: 1px solid #e0e6ed;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
    }

    .summary-row.total {
        font-size: 18px;
        font-weight: 700;
        padding-top: 10px;
        border-top: 2px solid #e0e6ed;
        color: #03db1cac;
    }

    .checkout-btn {
        width: 100%;
        padding: 16px;
        background: #03db1cac;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 15px;
        transition: all 0.3s;
    }

    .checkout-btn:hover {
        background: #02b817;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(3, 219, 28, 0.3);
    }

    .checkout-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    /* Modern Button Styles */
    .btn-modern {
        padding: 10px 20px;
        border-radius: 6px;
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-modern-primary {
        background: #03db1cac;
        color: #fff;
    }

    .btn-modern-secondary {
        background: #f5f7fa;
        color: #333;
        border: 1px solid #e0e6ed;
    }

    /* Completed Sales & Returns Sections */
    #completed-section,
    #returns-section {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    #completed-section > div,
    #returns-section > div {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    #completed-sales-list,
    #returns-list {
        flex: 1;
        overflow-y: auto;
    }

    /* Completed Sales Styles */
    .completed-sale-card {
        background: #fff;
        border: 1px solid #e0e6ed;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }

    .completed-sale-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: #03db1cac;
    }

    .completed-sale-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 12px;
    }

    .completed-sale-info {
        flex: 1;
    }

    .completed-sale-number {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .completed-sale-meta {
        font-size: 13px;
        color: #666;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .completed-sale-actions {
        display: flex;
        gap: 8px;
    }

    .completed-sale-total {
        font-size: 20px;
        font-weight: 700;
        color: #03db1cac;
        text-align: right;
    }

    .completed-sale-items {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
        font-size: 13px;
        color: #666;
    }

    .btn-action {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-action-print {
        background: #2196f3;
        color: #fff;
    }

    .btn-action-print:hover {
        background: #1976d2;
    }

    .btn-action-return {
        background: #ff9800;
        color: #fff;
    }

    .btn-action-return:hover {
        background: #f57c00;
    }

    /* Scrollbar Styles */
    .pos-products::-webkit-scrollbar,
    .cart-items::-webkit-scrollbar {
        width: 6px;
    }

    .pos-products::-webkit-scrollbar-track,
    .cart-items::-webkit-scrollbar-track {
        background: #f5f7fa;
    }

    .pos-products::-webkit-scrollbar-thumb,
    .cart-items::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .pos-right {
            width: 380px;
        }
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .pos-container {
            flex-direction: column;
        }
        .pos-right {
            width: 100%;
            max-height: 50vh;
        }
    }
</style>

<div class="pos-container">
    <!-- Left Side - Products -->
    <div class="pos-left">
        <!-- Header -->
        <div class="pos-header">
            <div class="pos-header-actions">
                <button class="btn-modern btn-modern-secondary" onclick="selectCustomer()">
                    <i class="fa fa-user"></i> <span id="customer-name">Select Customer</span>
                </button>
                <button class="btn-modern btn-modern-primary" id="btn-new-sale" onclick="showNewSale()">
                    <i class="fa fa-shopping-cart"></i> New Sale
                </button>
                <button class="btn-modern btn-modern-secondary" id="btn-completed" onclick="showCompletedSales()">
                    <i class="fa fa-check-circle"></i> Completed
                </button>
                <button class="btn-modern btn-modern-secondary" id="btn-returns" onclick="showReturns()">
                    <i class="fa fa-undo"></i> Returns
                </button>
                <button class="btn-modern btn-modern-secondary" onclick="viewPending()">
                    <i class="fa fa-clock"></i> Pending
                </button>
                <button class="btn-modern btn-modern-secondary" onclick="reportIssue()">
                    <i class="fa fa-exclamation-circle"></i> Report Issue
                </button>
            </div>
        </div>

        <!-- Search -->
        <div class="pos-search">
            <div class="search-wrapper">
                <i class="fa fa-search search-icon"></i>
                <input type="text" class="search-input" id="product-search" 
                       placeholder="Search products by name or scan barcode..." 
                       autocomplete="off">
                <button class="barcode-btn">
                    <i class="fa fa-barcode"></i>
                </button>
            </div>
        </div>

        <!-- Categories -->
        <div class="pos-categories">
            <button class="category-btn active" data-category="all">All Products</button>
            <button class="category-btn" data-category="beverages">Beverages</button>
            <button class="category-btn" data-category="dairy">Dairy</button>
            <button class="category-btn" data-category="snacks">Snacks</button>
            <button class="category-btn" data-category="household">Household</button>
            <button class="category-btn" data-category="grains">Grains</button>
        </div>

        <!-- Products Grid -->
        <div class="pos-products" id="products-section">
            <div class="products-grid" id="products-grid">
                <!-- Products will be loaded here dynamically -->
            </div>
        </div>

        <!-- Completed Sales Section (Hidden by default) -->
        <div class="pos-products" id="completed-section" style="display: none;">
            <div style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;"><i class="fa fa-check-circle"></i> Completed Sales</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="date" id="completed-date-from" class="form-control" style="display: inline-block; width: auto;" placeholder="From Date">
                        <input type="date" id="completed-date-to" class="form-control" style="display: inline-block; width: auto;" placeholder="To Date">
                        <button class="btn btn-primary" onclick="loadCompletedSales()">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="completed-sales-list">
                    <!-- Completed sales will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Returns/Credit Notes Section (Hidden by default) -->
        <div class="pos-products" id="returns-section" style="display: none;">
            <div style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;"><i class="fa fa-undo"></i> Returns / Credit Notes</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="date" id="returns-date-from" class="form-control" style="display: inline-block; width: auto;" placeholder="From Date">
                        <input type="date" id="returns-date-to" class="form-control" style="display: inline-block; width: auto;" placeholder="To Date">
                        <button class="btn btn-primary" onclick="loadReturns()">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="returns-list">
                    <!-- Returns will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side - Cart -->
    <div class="pos-right">
        <!-- Cart Header -->
        <div class="cart-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="cart-title">
                    <i class="fa fa-shopping-cart"></i>
                    Cart (<span id="cart-count">0</span>)
                </div>
                <button class="btn-modern-secondary" onclick="clearCart()" style="padding: 6px 12px; font-size: 13px;">
                    Clear All
                </button>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="cart-items" id="cart-items">
            <div class="cart-empty">
                <i class="fa fa-shopping-cart"></i>
                <p style="font-size: 16px; font-weight: 500;">Cart is empty</p>
                <p style="font-size: 14px;">Add products to get started</p>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="cart-summary" id="cart-summary" style="display: none;">
            <div class="summary-row">
                <span>Subtotal (Inc. VAT)</span>
                <span id="subtotal">KES 0.00</span>
            </div>
            <div class="summary-row" id="discount-row" style="display: none; color: #4caf50;">
                <span>Discount</span>
                <span id="total-discount">-KES 0.00</span>
            </div>
            <div class="summary-row" style="font-size: 12px; color: #999;">
                <span>VAT (Inclusive)</span>
                <span id="vat">KES 0.00</span>
            </div>
            <div class="summary-row total">
                <span>Total Payable</span>
                <span id="grand-total">KES 0.00</span>
            </div>
            <button class="checkout-btn" onclick="proceedToPayment()">
                Proceed to Payment
            </button>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="payment-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 10px;">
            <div class="modal-header" style="border-bottom: 2px solid #e0e6ed;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="font-weight: 700;">Payment</h4>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <!-- Total Due -->
                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; text-align: center; margin-bottom: 30px;">
                    <p style="margin: 0; color: #666; font-size: 14px;">Total Amount Due</p>
                    <p style="margin: 10px 0 0 0; font-size: 36px; font-weight: 700; color: #1976d2;" id="payment-total">KES 0.00</p>
                </div>

                <!-- Payment Methods -->
                <h5 style="font-weight: 600; margin-bottom: 20px;">Payment Methods</h5>
                
                <div id="payment-methods">
                    <!-- Payment methods will be loaded here -->
                </div>

                <!-- Payment Summary -->
                <div style="background: #f5f7fa; padding: 20px; border-radius: 10px; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Total Due</span>
                        <strong id="payment-due">KES 0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Total Tendered</span>
                        <strong id="payment-tendered">KES 0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 2px solid #e0e6ed; font-size: 18px; font-weight: 700;">
                        <span id="balance-label">Balance</span>
                        <span id="payment-balance" style="color: #f44336;">KES 0.00</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 2px solid #e0e6ed;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="complete-sale-btn" onclick="completeSale()" disabled>
                    <i class="fa fa-check"></i> Complete Sale
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('uniquepagescript')
<script src="{{asset('js/sweetalert.js')}}"></script>
<script src="{{asset('js/supermarket-pos.js')}}?v={{time()}}"></script>
<script src="{{asset('js/supermarket-pos-completed.js')}}?v={{time()}}"></script>
<script src="{{asset('js/supermarket-pos-returns.js')}}?v={{time()}}"></script>
@endsection
