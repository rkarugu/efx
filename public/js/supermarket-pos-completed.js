/**
 * Completed Sales Management
 * Handles viewing, reprinting, and returns for completed sales
 */

// Show New Sale Tab
function showNewSale() {
    $('#products-section').show();
    $('#completed-section').hide();
    $('#btn-new-sale').removeClass('btn-modern-secondary').addClass('btn-modern-primary');
    $('#btn-completed').removeClass('btn-modern-primary').addClass('btn-modern-secondary');
}

// Show Completed Sales Tab
function showCompletedSales() {
    $('#products-section').hide();
    $('#completed-section').show();
    $('#btn-new-sale').removeClass('btn-modern-primary').addClass('btn-modern-secondary');
    $('#btn-completed').removeClass('btn-modern-secondary').addClass('btn-modern-primary');
    
    // Set default dates (today)
    const today = new Date().toISOString().split('T')[0];
    $('#completed-date-from').val(today);
    $('#completed-date-to').val(today);
    
    // Load completed sales
    loadCompletedSales();
}

// Load Completed Sales
function loadCompletedSales() {
    console.log('Loading completed sales...');
    
    const dateFrom = $('#completed-date-from').val();
    const dateTo = $('#completed-date-to').val();
    
    $.ajax({
        url: '/admin/pos-cash-sales/supermarket/completed',
        method: 'GET',
        data: {
            date_from: dateFrom,
            date_to: dateTo
        },
        success: function(response) {
            if (response.success) {
                console.log('✅ Loaded ' + response.sales.length + ' completed sales');
                renderCompletedSales(response.sales);
            } else {
                console.error('Error loading completed sales:', response.message);
                showErrorMessage('Error loading completed sales: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading completed sales:', error);
            console.error('Response:', xhr.responseText);
            showErrorMessage('Error loading completed sales. Please try again.');
        }
    });
}

// Render Completed Sales
function renderCompletedSales(sales) {
    const container = $('#completed-sales-list');
    container.empty();
    
    if (sales.length === 0) {
        container.html(`
            <div style="text-align: center; padding: 60px 20px; color: #999;">
                <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                <p style="font-size: 16px;">No completed sales found</p>
                <p style="font-size: 14px;">Try selecting a different date range</p>
            </div>
        `);
        return;
    }
    
    sales.forEach(sale => {
        const paymentMethodsText = sale.payment_methods.map(p => 
            `${p.method}: KES ${p.amount.toFixed(2)}`
        ).join(', ');
        
        const returnButton = sale.can_return ? `
            <button class="btn-action btn-action-return" onclick="initiateSaleReturn(${sale.id})">
                <i class="fa fa-undo"></i> Return
            </button>
        ` : '';
        
        const card = `
            <div class="completed-sale-card">
                <div class="completed-sale-header">
                    <div class="completed-sale-info">
                        <div class="completed-sale-number">#${sale.sales_no}</div>
                        <div class="completed-sale-meta">
                            <span><i class="fa fa-calendar"></i> ${sale.date} at ${sale.time}</span>
                            <span><i class="fa fa-user"></i> ${sale.customer_name}</span>
                            ${sale.customer_phone ? `<span><i class="fa fa-phone"></i> ${sale.customer_phone}</span>` : ''}
                            <span><i class="fa fa-user-circle"></i> Cashier: ${sale.cashier}</span>
                        </div>
                        <div class="completed-sale-items">
                            <i class="fa fa-shopping-bag"></i> ${sale.items_count} item(s) | Payment: ${paymentMethodsText}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="completed-sale-total">KES ${sale.total_amount.toFixed(2)}</div>
                        <div class="completed-sale-actions" style="margin-top: 10px;">
                            <button class="btn-action btn-action-print" onclick="reprintReceipt(${sale.id})">
                                <i class="fa fa-print"></i> Reprint
                            </button>
                            ${returnButton}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.append(card);
    });
}

// Reprint Receipt
function reprintReceipt(saleId) {
    console.log('Reprinting receipt for sale:', saleId);
    
    // Open receipt in new window
    const receiptUrl = `/admin/pos-cash-sales/supermarket/receipt/${saleId}`;
    window.open(receiptUrl, '_blank', 'width=800,height=600');
}

// Initiate Sale Return
function initiateSaleReturn(saleId) {
    console.log('Initiating return for sale:', saleId);
    
    // Load sale details for return
    $.ajax({
        url: `/admin/pos-cash-sales/supermarket/return/${saleId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                console.log('✅ Loaded sale for return');
                showReturnModal(response);
            } else {
                showErrorMessage(response.message || 'Error loading sale for return');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading sale for return:', error);
            const message = xhr.responseJSON?.message || 'Error loading sale for return';
            showErrorMessage(message);
        }
    });
}

// Show Return Modal
function showReturnModal(data) {
    const sale = data.sale;
    const items = data.items;
    const reasons = data.reasons;
    
    // Build items HTML
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
            <tr data-item-id="${item.id}">
                <td>
                    <strong>${item.item_name}</strong><br>
                    <small>${item.stock_id_code}</small>
                </td>
                <td class="text-right">${item.qty}</td>
                <td class="text-right">KES ${parseFloat(item.selling_price).toFixed(2)}</td>
                <td>
                    <input type="number" 
                           class="form-control return-qty" 
                           min="0" 
                           max="${item.qty}" 
                           step="0.01"
                           value="0"
                           data-item-id="${item.id}"
                           data-price="${item.selling_price}">
                </td>
                <td>
                    <select class="form-control return-reason" data-item-id="${item.id}">
                        <option value="">Select reason</option>
                        ${reasons.map(r => `<option value="${r.id}">${r.reason}</option>`).join('')}
                    </select>
                </td>
                <td class="text-right return-amount" data-item-id="${item.id}">KES 0.00</td>
            </tr>
        `;
    });
    
    // Build reasons dropdown
    let reasonsOptions = '<option value="">Select reason</option>';
    reasons.forEach(reason => {
        reasonsOptions += `<option value="${reason.id}">${reason.reason}</option>`;
    });
    
    const modalHtml = `
        <div class="modal fade" id="returnModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background: #f39c12; color: white;">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            <i class="fa fa-undo"></i> Process Return - ${sale.sales_no}
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 
                            Enter the quantity to return for each item and select a reason.
                        </div>
                        
                        <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            <strong>Sale Date:</strong> ${sale.date} ${sale.time} | 
                            <strong>Total Amount:</strong> KES ${parseFloat(sale.total_amount).toFixed(2)}
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr style="background: #27AE60; color: white;">
                                        <th>Item</th>
                                        <th class="text-right">Sold Qty</th>
                                        <th class="text-right">Price</th>
                                        <th>Return Qty</th>
                                        <th>Reason</th>
                                        <th class="text-right">Return Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="return-items-tbody">
                                    ${itemsHtml}
                                </tbody>
                                <tfoot>
                                    <tr style="background: #f8f9fa; font-weight: bold;">
                                        <td colspan="5" class="text-right">Total Return Amount:</td>
                                        <td class="text-right" id="total-return-amount">KES 0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-warning" onclick="processReturn(${sale.id})">
                            <i class="fa fa-check"></i> Process Return
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#returnModal').remove();
    
    // Add modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#returnModal').modal('show');
    
    // Add event listeners for quantity changes
    $('.return-qty').on('input', function() {
        updateReturnAmounts();
    });
}

// Update Return Amounts
function updateReturnAmounts() {
    let totalReturn = 0;
    
    $('.return-qty').each(function() {
        const qty = parseFloat($(this).val()) || 0;
        const price = parseFloat($(this).data('price'));
        const itemId = $(this).data('item-id');
        const amount = qty * price;
        
        totalReturn += amount;
        
        // Update individual item return amount
        $(`.return-amount[data-item-id="${itemId}"]`).text('KES ' + amount.toFixed(2));
    });
    
    // Update total
    $('#total-return-amount').text('KES ' + totalReturn.toFixed(2));
}

// Process Return
function processReturn(saleId) {
    console.log('Processing return for sale:', saleId);
    
    // Collect return items
    const returnItems = [];
    let hasErrors = false;
    let errorMessage = '';
    
    $('.return-qty').each(function() {
        const qty = parseFloat($(this).val()) || 0;
        
        if (qty > 0) {
            const itemId = $(this).data('item-id');
            const reasonId = $(`.return-reason[data-item-id="${itemId}"]`).val();
            
            if (!reasonId) {
                hasErrors = true;
                errorMessage = 'Please select a return reason for all items being returned';
                $(`.return-reason[data-item-id="${itemId}"]`).css('border-color', 'red');
                return false; // Break loop
            }
            
            returnItems.push({
                item_id: itemId,
                quantity: qty,
                reason_id: reasonId
            });
        }
    });
    
    if (hasErrors) {
        showErrorMessage(errorMessage);
        return;
    }
    
    if (returnItems.length === 0) {
        showErrorMessage('Please enter at least one item to return');
        return;
    }
    
    // Confirm return
    if (!confirm(`Are you sure you want to process return for ${returnItems.length} item(s)?`)) {
        return;
    }
    
    // Show loading
    const $btn = $('button[onclick="processReturn(' + saleId + ')"]');
    const originalText = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
    
    // Process the return
    $.ajax({
        url: `/admin/pos-cash-sales/supermarket/return/${saleId}`,
        method: 'POST',
        data: {
            items: returnItems,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                console.log('✅ Return processed successfully');
                console.log('Return GRN:', response.return_grn);
                console.log('Return Amount:', response.return_amount);
                
                // Close modal
                $('#returnModal').modal('hide');
                
                // Show success message
                showSuccessMessage(`Return processed successfully! Return GRN: ${response.return_grn}<br>Return Amount: KES ${response.return_amount.toFixed(2)}`);
                
                // Open return receipt in new window
                const returnReceiptUrl = `/admin/pos-cash-sales/supermarket/return-receipt/${response.return_grn}`;
                console.log('Opening return receipt at:', returnReceiptUrl);
                const receiptWindow = window.open(returnReceiptUrl, '_blank', 'width=800,height=600');
                
                if (!receiptWindow) {
                    console.error('❌ Failed to open receipt window - popup may be blocked');
                    showErrorMessage('Could not open return receipt. Please check if popups are blocked.');
                } else {
                    console.log('✅ Receipt window opened successfully');
                }
                
                // Reload completed sales
                loadCompletedSales();
            } else {
                showErrorMessage(response.message || 'Error processing return');
                $btn.html(originalText).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error processing return:', error);
            const message = xhr.responseJSON?.message || 'Error processing return';
            showErrorMessage(message);
            $btn.html(originalText).prop('disabled', false);
        }
    });
}

// Show success message
function showSuccessMessage(message) {
    const successHtml = `
        <div style="position: fixed; top: 20px; right: 20px; z-index: 99999; max-width: 400px;">
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                <h4><i class="fa fa-check-circle"></i> Success</h4>
                <p>${message}</p>
            </div>
        </div>
    `;
    $('body').append(successHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        $('.alert-success').fadeOut(function() { $(this).parent().remove(); });
    }, 5000);
}

// Show error message without using alert
function showErrorMessage(message) {
    const errorHtml = `
        <div style="position: fixed; top: 20px; right: 20px; z-index: 99999; max-width: 400px;">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                <h4><i class="fa fa-exclamation-circle"></i> Error</h4>
                <p>${message}</p>
            </div>
        </div>
    `;
    $('body').append(errorHtml);
    
    // Auto-remove after 5 seconds
    setTimeout(function() {
        $('.alert-danger').fadeOut(function() { $(this).parent().remove(); });
    }, 5000);
}

// Show info message
function showInfoMessage(message) {
    const infoHtml = `
        <div style="position: fixed; top: 20px; right: 20px; z-index: 99999; max-width: 400px;">
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" onclick="this.parentElement.parentElement.remove()">&times;</button>
                <h4><i class="fa fa-info-circle"></i> Information</h4>
                <p>${message}</p>
            </div>
        </div>
    `;
    $('body').append(infoHtml);
    
    // Auto-remove after 3 seconds
    setTimeout(function() {
        $('.alert-info').fadeOut(function() { $(this).parent().remove(); });
    }, 3000);
}

