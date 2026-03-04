// Supermarket POS Returns Management

// Show returns section
function showReturns() {
    console.log('Showing returns section');
    
    // Hide other sections
    document.getElementById('products-section').style.display = 'none';
    document.getElementById('completed-section').style.display = 'none';
    document.getElementById('returns-section').style.display = 'block';
    
    // Update button states
    document.getElementById('btn-new-sale').classList.remove('btn-modern-primary');
    document.getElementById('btn-new-sale').classList.add('btn-modern-secondary');
    document.getElementById('btn-completed').classList.remove('btn-modern-primary');
    document.getElementById('btn-completed').classList.add('btn-modern-secondary');
    document.getElementById('btn-returns').classList.remove('btn-modern-secondary');
    document.getElementById('btn-returns').classList.add('btn-modern-primary');
    
    // Set default dates (last 30 days)
    const today = new Date().toISOString().split('T')[0];
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    document.getElementById('returns-date-from').value = thirtyDaysAgo;
    document.getElementById('returns-date-to').value = today;
    
    // Load returns
    loadReturns();
}

// Load returns data
function loadReturns() {
    console.log('Loading returns...');
    
    const dateFrom = document.getElementById('returns-date-from').value;
    const dateTo = document.getElementById('returns-date-to').value;
    
    $.ajax({
        url: '/admin/pos-cash-sales/supermarket/returns',
        method: 'GET',
        data: {
            date_from: dateFrom,
            date_to: dateTo
        },
        success: function(response) {
            if (response.success) {
                console.log(`✅ Loaded ${response.returns.length} returns`);
                displayReturns(response.returns);
            } else {
                showErrorMessage(response.message || 'Error loading returns');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading returns:', error);
            showErrorMessage('Error loading returns. Please try again.');
        }
    });
}

// Display returns in the UI
function displayReturns(returns) {
    const returnsList = document.getElementById('returns-list');
    
    if (returns.length === 0) {
        returnsList.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p style="font-size: 16px;">No returns found for the selected date range</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    returns.forEach(returnItem => {
        html += `
            <div class="completed-sale-card" style="margin-bottom: 15px; border: 1px solid #e0e6ed; border-radius: 8px; padding: 15px; background: #fff;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                    <div>
                        <div style="font-weight: bold; font-size: 16px; color: #f39c12;">
                            <i class="fa fa-undo"></i> ${returnItem.return_grn}
                        </div>
                        <div style="font-size: 13px; color: #666; margin-top: 5px;">
                            Original Sale: <strong>${returnItem.sale_no}</strong>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <i class="fa fa-calendar"></i> ${returnItem.return_date} ${returnItem.return_time}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 20px; font-weight: bold; color: #e74c3c;">
                            -KES ${parseFloat(returnItem.total_return_amount).toFixed(2)}
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            ${returnItem.items_count} item(s)
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid #f0f0f0;">
                    <div style="font-size: 13px;">
                        <i class="fa fa-user"></i> Customer: <strong>${returnItem.customer_name}</strong><br>
                        <i class="fa fa-user-circle"></i> Processed by: <strong>${returnItem.returned_by}</strong>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="btn btn-sm btn-info" onclick="viewReturnDetails('${returnItem.return_grn}', ${JSON.stringify(returnItem.items).replace(/"/g, '&quot;')})">
                            <i class="fa fa-eye"></i> View Details
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="reprintReturnReceipt('${returnItem.return_grn}')">
                            <i class="fa fa-print"></i> Reprint
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    returnsList.innerHTML = html;
}

// View return details
function viewReturnDetails(returnGrn, items) {
    console.log('Viewing return details for:', returnGrn);
    
    let itemsHtml = '';
    items.forEach((item, index) => {
        itemsHtml += `
            <tr>
                <td>${index + 1}</td>
                <td>${item.item_name}</td>
                <td class="text-center">${item.quantity}</td>
                <td>${item.reason}</td>
            </tr>
        `;
    });
    
    const modalHtml = `
        <div class="modal fade" id="returnDetailsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background: #f39c12; color: white;">
                        <h4 class="modal-title">
                            <i class="fa fa-undo"></i> Return Details - ${returnGrn}
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" style="color: white;">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th width="50">#</th>
                                    <th>Item</th>
                                    <th width="100" class="text-center">Quantity</th>
                                    <th width="200">Return Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${itemsHtml}
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="reprintReturnReceipt('${returnGrn}')">
                            <i class="fa fa-print"></i> Print Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#returnDetailsModal').remove();
    
    // Add and show modal
    $('body').append(modalHtml);
    $('#returnDetailsModal').modal('show');
}

// Reprint return receipt
function reprintReturnReceipt(returnGrn) {
    console.log('Reprinting return receipt for:', returnGrn);
    
    const receiptUrl = `/admin/pos-cash-sales/supermarket/return-receipt/${returnGrn}`;
    const receiptWindow = window.open(receiptUrl, '_blank', 'width=800,height=600');
    
    if (!receiptWindow) {
        showErrorMessage('Could not open receipt window. Please check if popups are blocked.');
    }
}
