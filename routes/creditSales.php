<?php


use App\Http\Controllers\Admin\CreditSales\ChequeBankController;
use App\Http\Controllers\Admin\CreditSales\CreditSalesController;
use App\Http\Controllers\Admin\InternalRequisitionController;
use App\Http\Controllers\Admin\IssueFullfillRequisitionController;
use App\Http\Controllers\Admin\RegisterChequeController;
use App\Http\Controllers\Admin\SalesInvoiceReturnController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin','namespace' => 'Admin', 'middleware' => 'AdminLoggedIn'], function (){
    Route::get('sales-invoice/inventoryItems/getInventryItemDetails', 'InternalRequisitionController@getInventryItemDetails')->name('sales-invoice.getInventryItemDetails');
    Route::post('sales-invoice/getItems-detail', 'InternalRequisitionController@getItemDetail')->name('sales-invoice.items.detail');
    Route::post('sales-invoice/getItems', 'InternalRequisitionController@getItems')->name('sales-invoice.items');
    Route::get('sales-invoice/{requisition_no_no}/{id}/edit', 'InternalRequisitionController@editPurchaseItem')->name('sales-invoice.editPurchaseItem');
    Route::post('sales-invoice/{id}/edit', 'InternalRequisitionController@updatePurchaseItem')->name('sales-invoice.updatePurchaseItem');
    Route::get('sales-invoice/delete-item/{requisition_no_no}/{item_id}', 'InternalRequisitionController@deletingItemRelation')->name('sales-invoice.items.delete');
    Route::get('sales-invoice/send-request/{requisition_no_no}', 'InternalRequisitionController@sendRequisitionRequest')->name('sales-invoice.sendRequisitionRequest');
    Route::get('sales-invoice/pdf/{slug}', 'InternalRequisitionController@exportToPdf')->name('sales-invoice.exportToPdf');
    Route::post('sales-invoice/print', 'InternalRequisitionController@print')->name('sales-invoice.print');
    Route::post('sales-invoice/get-item-qoh-ajax', 'InternalRequisitionController@getItemQohAjax')->name('sales-invoice.get-item-qoh-ajax');

    Route::get('sales-invoice/get-salesman-route', 'InternalRequisitionController@getsalesmanroute')->name('sales-invoice.getsalesmanroute');
    Route::get('sales-invoice/get-customer-credit', 'InternalRequisitionController@getcustomercredit')->name('sales-invoice.getcustomercredit');
    Route::resource('sales-invoice', InternalRequisitionController::class);
    Route::get('sales-invoice/returns/pending', [SalesInvoiceReturnController::class, 'showInitialReturnsList'])->name('transfers.return_list');
    Route::get('sales-invoice/search/items', [\App\Http\Controllers\Admin\InternalRequisitionController::class, 'searchInventory'])->name('sales-invoice.search');
    Route::get('sales-invoice/calculate-discount', [\App\Http\Controllers\Admin\InternalRequisitionController::class, 'calculateItemDiscount'])->name('sales-invoice.calculate-discount');
    Route::get('sales-invoice/test-discount/{itemId}/{quantity}', [\App\Http\Controllers\Admin\InternalRequisitionController::class, 'testDiscount'])->name('sales-invoice.test-discount');
    /*manage Cheque Banks*/
    Route::get('cheque-banks', [ChequeBankController::class, 'index'])->name('cheque-banks');
    Route::post('cheque-banks', [ChequeBankController::class, 'store'])->name('cheque-banks.store');
    Route::put('cheque-banks/{id}', [ChequeBankController::class, 'update'])->name('cheque-banks.update');
    Route::delete('cheque-banks/{id}', [ChequeBankController::class, 'destroy'])->name('cheque-banks.delete');

    /*Manage Cheques*/
    Route::GET('register-cheque/deposit-cheque/{id}', 'RegisterChequeController@deposit_cheque')->name('register-cheque.deposit_cheque');
    Route::GET('register-cheque/report', 'RegisterChequeController@report')->name('register-cheque.report');
    Route::PUT('register-cheque/deposit-cheque/{id}', 'RegisterChequeController@deposit_cheque_update')->name('register-cheque.deposit_cheque_update');
    Route::PUT('register-cheque/deposit-cheque/update-status/{id}', 'RegisterChequeController@deposit_cheque_update_status')->name('register-cheque.deposit_cheque_update_status');
    Route::PUT('register-cheque/bounced-cheque/update-transfer/{id}', 'RegisterChequeController@bounced_cheque_transfer')->name('register-cheque.bounced_cheque_transfer');
    Route::resource('register-cheque', RegisterChequeController::class);
    Route::resource('deposit-cheque', RegisterChequeController::class);
    Route::resource('cleared-cheque', RegisterChequeController::class);
    Route::resource('bounced-cheque', RegisterChequeController::class);

    Route::resource('cheque-management', RegisterChequeController::class);
    Route::GET('cheque-management/deposit-cheque/{id}', [RegisterChequeController::class,'deposit_cheque'])->name('cheque-management.deposit_cheque');
    Route::PUT('cheque-management/deposit-cheque/{id}', [RegisterChequeController::class,'deposit_cheque_update'])->name('cheque-management.deposit_cheque_update');

    Route::post('/send-otp', [InternalRequisitionController::class, 'sendOtp'])->name('credit.sales.otp');
    Route::post('/send-otp-over', [InternalRequisitionController::class, 'sendOtpOver'])->name('credit.sales.otp-over');
    Route::post('/verify-otp', [InternalRequisitionController::class, 'verifyOtp'])->name('credit.sales.verify.otp');

    Route::post('confirm-invoice/print', 'IssueFullfillRequisitionController@printPage')->name('confirm-invoice.print');
    Route::resource('confirm-invoice', IssueFullfillRequisitionController::class);

    Route::post('confirm-invoice/save-esd', 'IssueFullfillRequisitionController@save_esd')->name('confirm-invoice.save_esd');

    /*dispatch screen*/
    Route::get('store-man-dispatch-screen',[CreditSalesController::class,'dispatchScreen'])->name('credit-sales-dispatch');
    Route::post('store-man-dispatch-screen-one/{id}',[CreditSalesController::class,'process'])->name('credit-sales.dispatcher.dispatch');

});

