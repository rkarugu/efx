/**
 * Supermarket POS System - JavaScript
 * Built with jQuery for AdminLTE compatibility
 * Version: 3.0 - Full Ecosystem (Promotions, Stock Moves, Cash Drops)
 */

console.log('🚀 Supermarket POS v3.0 loaded - Full Ecosystem Mode');

// Global Variables
let cart = [];
let currentCustomer = null;
let products = [];
let selectedCategory = 'all';
let cashierInfo = {};
let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
let productsCache = null;
let lastLoadTime = null;

// Initialize on document ready
$(document).ready(function() {
    loadCashierInfo();
    loadProducts();
    setupEventListeners();
    
    // Focus on search input
    $('#product-search').focus();
    
    // Check cash drop alerts every 5 minutes
    setInterval(checkCashDropAlert, 300000);
});

// Load Cashier Information
function loadCashierInfo() {
    console.log('Loading cashier information...');
    $.ajax({
        url: '/admin/pos-cash-sales/supermarket/cashier-info',
        method: 'GET',
        success: function(data) {
            if (data.success) {
                cashierInfo = data;
                console.log('✅ Cashier info loaded:', data);
                
                // Show cash drop alert if needed
                if (data.needs_drop) {
                    showCashDropAlert();
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading cashier info:', error);
        }
    });
}

// Check Cash Drop Alert
function checkCashDropAlert() {
    if (cashierInfo.needs_drop) {
        showCashDropAlert();
    }
}

// Show Cash Drop Alert
function showCashDropAlert() {
    if (!$('#cash-drop-alert').length) {
        const alertHtml = `
            <div id="cash-drop-alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 350px;">
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h4><i class="fa fa-exclamation-triangle"></i> Cash Drop Required!</h4>
                    <p>You have reached your drop limit. Please make a cash drop to continue.</p>
                    <button class="btn btn-sm btn-warning" onclick="showCashDropModal()">
                        <i class="fa fa-money-bill"></i> Make Cash Drop
                    </button>
                </div>
            </div>
        `;
        $('body').append(alertHtml);
    }
}

// Show Cash Drop Modal
function showCashDropModal() {
    const modalHtml = `
        <div class="modal fade" id="cash-drop-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Cash Drop</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Cash at Hand</label>
                            <input type="text" class="form-control" value="KES ${cashierInfo.cash_at_hand?.toFixed(2)}" disabled>
                        </div>
                        <div class="form-group">
                            <label>Drop Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="drop-amount" placeholder="Enter amount to drop" step="0.01" min="0.01">
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea class="form-control" id="drop-notes" rows="3" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning" onclick="submitCashDrop()">
                            <i class="fa fa-money-bill"></i> Submit Cash Drop
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#cash-drop-modal').remove();
    
    // Add and show modal
    $('body').append(modalHtml);
    $('#cash-drop-modal').modal('show');
}

// Submit Cash Drop
function submitCashDrop() {
    const amount = parseFloat($('#drop-amount').val());
    const notes = $('#drop-notes').val();
    
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    if (amount > cashierInfo.cash_at_hand) {
        alert('Drop amount cannot exceed cash at hand');
        return;
    }
    
    $.ajax({
        url: '/admin/pos-cash-sales/supermarket/cash-drop',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            amount: amount,
            notes: notes
        },
        success: function(response) {
            if (response.success) {
                alert('Cash drop recorded successfully!');
                $('#cash-drop-modal').modal('hide');
                $('#cash-drop-alert').remove();
                loadCashierInfo(); // Reload cashier info
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Cash drop error:', error);
            alert('Error submitting cash drop: ' + (xhr.responseJSON?.message || error));
        }
    });
}

// Load Products from API with caching
function loadProducts(forceReload = false) {
    // Check cache (5 minute validity)
    if (!forceReload && productsCache && lastLoadTime && (Date.now() - lastLoadTime) < 300000) {
        console.log('📦 Using cached products');
        products = productsCache;
        renderProducts();
        return;
    }
    
    console.log('Loading products from API...');
    $.ajax({
        url: '/admin/pos-cash-sales/supermarket/products',
        method: 'GET',
        cache: true,
        success: function(data) {
            console.log('API Response:', data);
            if (data && data.length > 0) {
                products = data;
                productsCache = data;
                lastLoadTime = Date.now();
                console.log('✅ Loaded ' + products.length + ' products from inventory');
                
                // Count promoted items
                const promotedCount = products.filter(p => p.has_promotion).length;
                console.log('🎁 ' + promotedCount + ' items on promotion');
            } else {
                console.warn('⚠️ API returned empty data');
                products = [];
            }
            renderProducts();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading products:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            showErrorMessage("Error loading products: " + (xhr.responseJSON?.message || error));
        }
    });
}

// Setup Event Listeners
function setupEventListeners() {
    // Product Search
    $('#product-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterProducts(searchTerm);
    });

    // Category Buttons
    $('.category-btn').on('click', function() {
        $('.category-btn').removeClass('active');
        $(this).addClass('active');
        selectedCategory = $(this).data('category');
        filterProducts($('#product-search').val().toLowerCase());
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // F1 - Focus search
        if (e.key === 'F1') {
            e.preventDefault();
            $('#product-search').focus();
        }
        // F2 - Select customer
        if (e.key === 'F2') {
            e.preventDefault();
            selectCustomer();
        }
        // F3 - Checkout
        if (e.key === 'F3') {
            e.preventDefault();
            if (cart.length > 0) {
                proceedToPayment();
            }
        }
        // F4 - Cash Drop
        if (e.key === 'F4') {
            e.preventDefault();
            showCashDropModal();
        }
    });
}

// Render Products
function renderProducts() {
    const grid = $('#products-grid');
    grid.empty();

    if (products.length === 0) {
        grid.html(`
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;">
                <i class="fa fa-box-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p style="font-size: 16px;">No products available</p>
            </div>
        `);
        return;
    }

    products.forEach(product => {
        const promotionBadge = product.has_promotion ? `
            <div style="position: absolute; top: 5px; left: 5px; background: #ff5722; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                ${product.promotion.type === 'price_discount' ? 'SALE' : 'PROMO'}
            </div>
        ` : '';
        
        const displayPrice = product.has_promotion && product.promotion.type === 'price_discount' 
            ? product.promotion.promotion_price 
            : product.price;
        
        const originalPrice = product.has_promotion && product.promotion.type === 'price_discount'
            ? `<div style="font-size: 12px; text-decoration: line-through; color: #999;">KES ${product.price.toFixed(2)}</div>`
            : '';
        
        const card = `
            <div class="product-card" onclick="addToCart(${product.id})">
                ${promotionBadge}
                <div class="product-add-icon">
                    <i class="fa fa-plus"></i>
                </div>
                <div class="product-image">
                    <img loading="lazy" src="${product.image}" alt="${product.name}" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<i class=\'fa fa-box\'></i>';">
                </div>
                <div class="product-name">${product.name}</div>
                ${originalPrice}
                <div class="product-price">KES ${displayPrice.toFixed(2)}</div>
                <div class="product-stock">Stock: ${product.stock}</div>
            </div>
        `;
        grid.append(card);
    });
}

// Filter Products
function filterProducts(searchTerm) {
    const grid = $('#products-grid');
    grid.empty();

    let filtered = products;

    // Filter by category
    if (selectedCategory !== 'all') {
        filtered = filtered.filter(p => p.category === selectedCategory);
    }

    // Filter by search term
    if (searchTerm) {
        filtered = filtered.filter(p => 
            p.name.toLowerCase().includes(searchTerm) || 
            p.barcode.includes(searchTerm)
        );
    }

    if (filtered.length === 0) {
        grid.html(`
            <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;">
                <i class="fa fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p style="font-size: 16px;">No products found</p>
            </div>
        `);
        return;
    }

    filtered.forEach(product => {
        const promotionBadge = product.has_promotion ? `
            <div style="position: absolute; top: 5px; left: 5px; background: #ff5722; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                ${product.promotion.type === 'price_discount' ? 'SALE' : 'PROMO'}
            </div>
        ` : '';
        
        const displayPrice = product.has_promotion && product.promotion.type === 'price_discount' 
            ? product.promotion.promotion_price 
            : product.price;
        
        const originalPrice = product.has_promotion && product.promotion.type === 'price_discount'
            ? `<div style="font-size: 12px; text-decoration: line-through; color: #999;">KES ${product.price.toFixed(2)}</div>`
            : '';
        
        const card = `
            <div class="product-card" onclick="addToCart(${product.id})">
                ${promotionBadge}
                <div class="product-add-icon">
                    <i class="fa fa-plus"></i>
                </div>
                <div class="product-image">
                    <img loading="lazy" src="${product.image}" alt="${product.name}" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<i class=\'fa fa-box\'></i>';">
                </div>
                <div class="product-name">${product.name}</div>
                ${originalPrice}
                <div class="product-price">KES ${displayPrice.toFixed(2)}</div>
                <div class="product-stock">Stock: ${product.stock}</div>
            </div>
        `;
        grid.append(card);
    });
}

// Add to Cart
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        // Apply promotion price if available
        let price = product.price;
        let autoDiscount = 0;
        
        if (product.has_promotion && product.promotion.type === 'price_discount') {
            price = product.promotion.promotion_price;
        }
        
        cart.push({
            id: product.id,
            name: product.name,
            price: price,
            original_price: product.price,
            quantity: 1,
            discount: autoDiscount,
            vat: product.vat,
            image: product.image,
            has_promotion: product.has_promotion,
            promotion: product.promotion
        });
    }

    renderCart();
    playSound('add');
}

// Render Cart
function renderCart() {
    const cartItems = $('#cart-items');
    const cartCount = $('#cart-count');
    const cartSummary = $('#cart-summary');

    cartCount.text(cart.length);

    if (cart.length === 0) {
        cartItems.html(`
            <div class="cart-empty">
                <i class="fa fa-shopping-cart"></i>
                <p style="font-size: 16px; font-weight: 500;">Cart is empty</p>
                <p style="font-size: 14px;">Add products to get started</p>
            </div>
        `);
        cartSummary.hide();
        return;
    }

    cartItems.empty();
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        const discountAmount = (itemTotal * item.discount) / 100;
        const finalPrice = itemTotal - discountAmount;
        
        const promotionBadge = item.has_promotion ? `
            <span style="background: #ff5722; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">
                ${item.promotion.type === 'price_discount' ? 'ON SALE' : 'PROMO'}
            </span>
        ` : '';
        
        // Show VAT badge if VAT-exempt
        const vatBadge = item.vat === 0 ? `
            <span style="background: #2196f3; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">
                VAT EXEMPT
            </span>
        ` : '';

        const cartItem = `
            <div class="cart-item">
                <div class="cart-item-header">
                    <div class="cart-item-image-wrapper">
                        <img src="${item.image}" alt="${item.name}" class="cart-item-image" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='<i class=\'fa fa-box\' style=\'font-size: 24px; color: #ccc;\'></i>';">
                    </div>
                    <div style="flex: 1;">
                        <div class="cart-item-name">${item.name} ${promotionBadge}${vatBadge}</div>
                        <div class="cart-item-price">KES ${item.price.toFixed(2)} each ${item.vat > 0 ? '(Inc. ' + item.vat.toFixed(0) + '% VAT)' : '(VAT Exempt)'}</div>
                    </div>
                    <div class="cart-item-remove" onclick="removeFromCart(${index})">
                        <i class="fa fa-trash"></i>
                    </div>
                </div>
                <div class="cart-item-controls">
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="updateQuantity(${index}, ${item.quantity - 1})">
                            <i class="fa fa-minus"></i>
                        </button>
                        <input type="number" class="qty-input" value="${item.quantity}" 
                               onchange="updateQuantity(${index}, this.value)" min="1">
                        <button class="qty-btn" onclick="updateQuantity(${index}, ${item.quantity + 1})">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                    <div class="cart-item-total">
                        KES ${finalPrice.toFixed(2)}
                        ${item.discount > 0 ? `<div style="font-size: 11px; color: #4caf50;">-${item.discount}% discount</div>` : ''}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa fa-percent" style="color: #999;"></i>
                    <input type="number" class="discount-input" placeholder="Additional Discount %" 
                           value="${item.discount || ''}" 
                           onchange="updateDiscount(${index}, this.value)" 
                           min="0" max="100">
                </div>
            </div>
        `;
        cartItems.append(cartItem);
    });

    updateCartSummary();
    cartSummary.show();
}

// Update Quantity
function updateQuantity(index, quantity) {
    quantity = parseInt(quantity);
    if (quantity <= 0) {
        removeFromCart(index);
        return;
    }
    cart[index].quantity = quantity;
    renderCart();
}

// Update Discount
function updateDiscount(index, discount) {
    discount = parseFloat(discount) || 0;
    if (discount < 0) discount = 0;
    if (discount > 100) discount = 100;
    cart[index].discount = discount;
    renderCart();
}

// Remove from Cart
function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
    playSound('remove');
}

// Clear Cart
function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm("Are you sure you want to remove all items from the cart?")) {
        cart = [];
        renderCart();
    }
}

// Update Cart Summary
function updateCartSummary() {
    let subtotal = 0;
    let totalDiscount = 0;
    let totalVat = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity; // Price is VAT inclusive
        const discountAmount = (itemTotal * item.discount) / 100;
        const totalAfterDiscount = itemTotal - discountAmount;
        
        // Extract VAT from VAT-inclusive price (item-specific VAT%)
        // VAT = Total × (VAT% / (100 + VAT%))
        let vatAmount = 0;
        if (item.vat > 0) {
            vatAmount = totalAfterDiscount * (item.vat / (100 + item.vat));
        }
        
        subtotal += itemTotal;
        totalDiscount += discountAmount;
        totalVat += vatAmount;
    });

    const grandTotal = subtotal - totalDiscount; // Grand total is same as subtotal after discount (VAT already included)

    $('#subtotal').text('KES ' + subtotal.toFixed(2));
    $('#vat').text('KES ' + totalVat.toFixed(2));
    $('#grand-total').text('KES ' + grandTotal.toFixed(2));

    if (totalDiscount > 0) {
        $('#discount-row').show();
        $('#total-discount').text('-KES ' + totalDiscount.toFixed(2));
    } else {
        $('#discount-row').hide();
    }
}

// Calculate Grand Total
function calculateGrandTotal() {
    let subtotal = 0;
    let totalDiscount = 0;

    cart.forEach(item => {
        const itemTotal = item.price * item.quantity; // Price is VAT inclusive
        const discountAmount = (itemTotal * item.discount) / 100;
        subtotal += itemTotal;
        totalDiscount += discountAmount;
    });

    // Grand total is subtotal minus discount (VAT already included in prices)
    return subtotal - totalDiscount;
}

// Proceed to Payment
function proceedToPayment() {
    if (cart.length === 0) {
        alert("Cart is empty!");
        return;
    }

    const total = calculateGrandTotal();
    $('#payment-total').text('KES ' + total.toFixed(2));
    $('#payment-due').text('KES ' + total.toFixed(2));
    
    loadPaymentMethods();
    $('#payment-modal').modal('show');
}

// Load Payment Methods
function loadPaymentMethods() {
    const paymentMethods = [
        { id: 'Cash', name: 'Cash', icon: 'fa-money-bill', isCash: true },
        { id: 'M-Pesa', name: 'M-Pesa', icon: 'fa-mobile-alt', isMpesa: true },
        { id: 'Card', name: 'Card', icon: 'fa-credit-card', isCard: true }
    ];

    const container = $('#payment-methods');
    container.empty();

    paymentMethods.forEach(method => {
        const methodHtml = `
            <div style="background: #f5f7fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="width: 40px; height: 40px; background: #e3f2fd; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa ${method.icon}" style="color: #1976d2; font-size: 18px;"></i>
                    </div>
                    <strong>${method.name}</strong>
                </div>
                ${method.isMpesa ? `
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="tel" class="form-control" id="mpesa-phone" placeholder="Phone Number (07XX...)" style="flex: 1;">
                        <button class="btn btn-primary" onclick="pushSTK()">Push STK</button>
                    </div>
                ` : ''}
                <div style="display: flex; gap: 10px;">
                    <input type="number" class="form-control payment-amount" data-method="${method.id}" 
                           placeholder="Amount" step="0.01" style="flex: 1;">
                    ${!method.isCash ? `
                        <input type="text" class="form-control payment-reference" data-method="${method.id}" 
                               placeholder="Reference" style="flex: 1;">
                    ` : ''}
                </div>
            </div>
        `;
        container.append(methodHtml);
    });

    // Setup payment amount listeners
    $('.payment-amount').on('input', updatePaymentBalance);
}

// Update Payment Balance
function updatePaymentBalance() {
    const total = calculateGrandTotal();
    let tendered = 0;

    $('.payment-amount').each(function() {
        const amount = parseFloat($(this).val()) || 0;
        tendered += amount;
    });

    const balance = tendered - total;

    $('#payment-tendered').text('KES ' + tendered.toFixed(2));
    $('#payment-balance').text('KES ' + Math.abs(balance).toFixed(2));

    if (balance < 0) {
        $('#balance-label').text('Balance Due');
        $('#payment-balance').css('color', '#f44336');
        $('#complete-sale-btn').prop('disabled', true);
    } else {
        $('#balance-label').text('Change');
        $('#payment-balance').css('color', '#4caf50');
        $('#complete-sale-btn').prop('disabled', false);
    }
}

// Push STK (M-Pesa)
function pushSTK() {
    const phone = $('#mpesa-phone').val();
    if (!phone) {
        alert("Please enter M-Pesa phone number");
        return;
    }
    alert(`STK Push sent to ${phone}`);
}

// Complete Sale
function completeSale() {
    const total = calculateGrandTotal();
    let payments = [];

    $('.payment-amount').each(function() {
        const amount = parseFloat($(this).val()) || 0;
        if (amount > 0) {
            const method = $(this).data('method');
            const reference = $(`.payment-reference[data-method="${method}"]`).val() || '';
            payments.push({ method, amount, reference });
        }
    });

    if (payments.length === 0) {
        alert("Please enter payment amount");
        return;
    }

    const saleData = {
        cart: cart.map(item => ({
            id: item.id,
            quantity: item.quantity,
            price: item.price,
            discount: item.discount || 0
        })),
        customer: currentCustomer || { name: 'Walk-in Customer' },
        payments: payments,
        total: total
    };

    console.log('Submitting sale:', saleData);

    // Show loading state
    $('#complete-sale-btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

    $.ajax({
        url: '/admin/pos-cash-sales/supermarket/store',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: saleData,
        success: function(response) {
            if (response.success) {
                // Close payment modal
                $('#payment-modal').modal('hide');
                
                // Open receipt in new window for printing
                const receiptUrl = `/admin/pos-cash-sales/supermarket/receipt/${response.sale_id}`;
                window.open(receiptUrl, 'receipt', 'width=800,height=600');
                
                // Show brief success message (will auto-close quickly)
                showSuccessMessage(response.sales_no, response.total, response.change);
                
                // Reset cart
                cart = [];
                currentCustomer = null;
                $('#customer-name').text('Select Customer');
                renderCart();
                loadCashierInfo(); // Reload cashier info after sale
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Sale error:', error);
            alert('Error completing sale: ' + (xhr.responseJSON?.message || error));
        },
        complete: function() {
            $('#complete-sale-btn').prop('disabled', false).html('<i class="fa fa-check"></i> Complete Sale');
        }
    });
}

// Helper Functions
function selectCustomer() {
    const inputValue = prompt("Enter customer name or phone:");
    if (inputValue && inputValue.trim() !== "") {
        currentCustomer = { name: inputValue };
        $('#customer-name').text(inputValue);
    }
}

function newSale() {
    if (cart.length > 0) {
        if (confirm("Current cart will be cleared. Start new sale?")) {
            cart = [];
            currentCustomer = null;
            $('#customer-name').text('Select Customer');
            renderCart();
        }
    }
}

function viewPending() {
    window.location.href = '/admin/pos-cash-sales?status=PENDING';
}

function reportIssue() {
    alert("Report Issue - Feature coming soon");
}

function playSound(type) {
    // Optional: Add sound effects
    // const audio = new Audio('/sounds/' + type + '.mp3');
    // audio.play();
}

// Show success message after sale
function showSuccessMessage(salesNo, total, change) {
    const messageId = 'success-message-' + Date.now();
    const overlayId = 'success-overlay-' + Date.now();
    
    const message = `
        <div id="${overlayId}" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                    background: rgba(0,0,0,0.5); z-index: 9999;"></div>
        <div id="${messageId}" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    z-index: 10000; background: #fff; padding: 30px; border-radius: 10px; 
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center; min-width: 400px;">
            <div style="color: #4caf50; font-size: 60px; margin-bottom: 20px;">
                <i class="fa fa-check-circle"></i>
            </div>
            <h3 style="margin: 0 0 20px 0; color: #333;">Sale Completed Successfully!</h3>
            <div style="background: #f5f7fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 5px 0; font-size: 14px;"><strong>Sale No:</strong> ${salesNo}</p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Total:</strong> KES ${total.toFixed(2)}</p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Change:</strong> KES ${change.toFixed(2)}</p>
            </div>
            <p style="font-size: 12px; color: #999; margin-top: 20px;">Receipt is printing...</p>
        </div>
    `;
    
    $('body').append(message);
    
    // Auto-remove after 2 seconds with fade effect
    setTimeout(function() {
        $('#' + messageId).fadeOut(400, function() {
            $(this).remove();
        });
        $('#' + overlayId).fadeOut(400, function() {
            $(this).remove();
        });
    }, 2000);
}

// Show Error Message
function showErrorMessage(message) {
    const messageId = 'error-message-' + Date.now();
    const overlayId = 'error-overlay-' + Date.now();
    
    const html = `
        <div id="${overlayId}" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                    background: rgba(0,0,0,0.5); z-index: 9999;"></div>
        <div id="${messageId}" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    z-index: 10000; background: #fff; padding: 30px; border-radius: 10px; 
                    box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center; min-width: 400px;">
            <div style="color: #f44336; font-size: 60px; margin-bottom: 20px;">
                <i class="fa fa-exclamation-circle"></i>
            </div>
            <h3 style="margin: 0 0 20px 0; color: #333;">Error</h3>
            <div style="background: #ffebee; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 14px; color: #c62828;">${message}</p>
            </div>
            <button onclick="$('#${messageId}, #${overlayId}').remove()" style="padding: 10px 30px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">OK</button>
        </div>
    `;
    
    $('body').append(html);
    
    // Auto-remove after 5 seconds with fade effect
    setTimeout(function() {
        $('#' + messageId).fadeOut(400, function() {
            $(this).remove();
        });
        $('#' + overlayId).fadeOut(400, function() {
            $(this).remove();
        });
    }, 5000);
}
