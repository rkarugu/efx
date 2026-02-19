<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");


use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\BinLocationController;
use App\Http\Controllers\Admin\ChairSalesReportController;
use App\Http\Controllers\Admin\ChartsOfAccountController;
use App\Http\Controllers\Admin\CustomerEquityPaymentController;
use App\Http\Controllers\Admin\CustomerKcbPaymentController;
use App\Http\Controllers\Admin\CustomerPaymentController;
use App\Http\Controllers\Admin\DeliveryCenterController;
use App\Http\Controllers\Admin\DeliveryScheduleController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\InventoryCategoryController;
use App\Http\Controllers\Admin\InventoryItemController;
use App\Http\Controllers\Admin\InventoryItemSupplierDataController;
use App\Http\Controllers\Admin\InventorySubCategoryController;
use App\Http\Controllers\Admin\MaintainWalletsController;
use App\Http\Controllers\Admin\NewFuelEntryController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PettyCashRequestController;
use App\Http\Controllers\Admin\PosCustomerController;
use App\Http\Controllers\Admin\PriceChangeController;
use App\Http\Controllers\Admin\PriceListController;
use App\Http\Controllers\Admin\PurchaseDiscountController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Admin\ReturnToSupplierController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesmanReportedIssueController;
use App\Http\Controllers\Admin\SalesmanStatementController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\SupplierTradeAgreementController;
use App\Http\Controllers\Admin\TelematicsDeviceController;
use App\Http\Controllers\Admin\SalesmanReportingReasonsController;
use App\Http\Controllers\Admin\StockCountsController;
use App\Http\Controllers\Admin\SupplierOverviewController;
use App\Http\Controllers\Admin\TaxManagerController;
use App\Http\Controllers\Admin\UserFingerprintController;
use App\Http\Controllers\Admin\UserSupplierController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\VehicleMakeController;
use App\Http\Controllers\Admin\VehicleModelController;
use App\Http\Controllers\Admin\VehicleTelematicsDataController;
use App\Http\Controllers\Admin\VehicleTypeController;
use App\Http\Controllers\Admin\VendorCentreController;
use App\Http\Controllers\Api\DeliveryDriverApiController;
use App\Http\Controllers\Api\RoutesApiController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\CleanupController;
use App\Http\Controllers\HandleExistingDemandsController;
use App\Http\Controllers\PesaflowDisbursementController;
use App\Http\Controllers\SalesInvoiceValidationController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\Shared\RouteCustomerController;
use App\Http\Controllers\Shared\SalesManShiftController;
use App\Http\Controllers\Shifts\SalesAndDeliveryShiftController;
use App\Http\Controllers\Shifts\ShiftTypeController;
use App\Http\Controllers\Shifts\WaShiftController;
use App\Http\Controllers\StoreKeeper\ParkingListController;
use App\Http\Controllers\TelematicsDataController;
use App\Http\Controllers\UserPettyCashTransactionController;
use Illuminate\Support\Facades\Route;
use App\Model\WaPosCashSales;
use App\Http\Controllers\Admin\FuelStationController;
use App\Http\Controllers\Admin\NInventoryLocationTransferController;
use App\Http\Controllers\Admin\PettyCashRequestTypeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PendingGrnController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\MobileInventoryManagementController;
use  App\Http\Controllers\Admin\ReportedMissingItemsController;
use App\Http\Controllers\Api\RequestNewSkuController;
use App\Http\Controllers\Api\SubscriptionBillingInvoiceController;
use App\Http\Controllers\Api\SupplierBillingController;
use App\Http\Controllers\DarajaDisbursementController;
use App\Http\Controllers\Shared\ReportNewItemController;
use App\Http\Controllers\Shared\ReportPriceConflict;
use App\Http\Controllers\Api\SmallPacksController;
use App\Http\Controllers\Api\SupplierVehicleTypeController;
use App\Http\Controllers\ChairmanDashboardController;
use App\Http\Controllers\NewMpesaIpnNotificationController;
use App\Models\NewMpesaIpnNotification;

// STORE SMS ACCOUNT
Route::post('store-sms-account', [SMSController::class, 'storeSMSAccount']);
// SMS DELIVERY STATUS CALLBACK
Route::post('sms-delivery-status', [SMSController::class, 'handleSMSDeliveryStatus']);

Route::post('reset-account-password', [\App\Http\Controllers\Api\AccountResetPasswordController::class, 'resetAccountPassword']);
Route::post('set-new-password', [\App\Http\Controllers\Api\AccountResetPasswordController::class, 'setNewPassword']);
Route::post('store-user-access-request', 'App\Http\Controllers\Api\ManageDeniedUsersAccessController@storeUserAccessRequest');
Route::get('invalidate-mobile-user-access', 'App\Http\Controllers\Api\ManageDeniedUsersAccessController@invalidateMobileSessions');

// DARAJA B2C CALLBACKS
Route::post('disbursements/daraja/{id}/callback', [DarajaDisbursementController::class, 'receiveResultCallback']);
Route::post('disbursements/daraja/{id}/timeout-callback', [DarajaDisbursementController::class, 'receiveTimeoutCallback']);

// PESAFLOW B2C CALLBACKS
Route::any('disbursements/pesaflow/{id}/callback', [PesaflowDisbursementController::class, 'receiveResultCallback']);

Route::group(['namespace' => 'Api', 'middleware' => ['isr_request']], function () {
    Route::POST('/purchase-orders/get-delivery-slots/{purchase_no}', 'PurchaseOrderController@get_delivery_slots')->name('purchase-orders.get_delivery_slots');
    Route::POST('/purchase-orders/add-sub-lpo/{purchase_no}', 'PurchaseOrderController@add_sub_lpo')->name('purchase-orders.add_sub_lpo');
    Route::POST('/purchase-orders/supplier-portal/receive-details-update/{slug}', 'PurchaseOrderController@receive_order_details_update')->name('purchase-orders.receive_order_details_update');
    Route::POST('/purchase-orders/supplier-portal/receive-lpo-for-approval', 'PurchaseOrderController@receive_lpo_for_approval')->name('purchase-orders.receive_lpo_for_approval');
    Route::POST('/purchase-orders/supplier-portal/update-lpo-return-status', 'PurchaseOrderController@lpo_return_accepted')->name('purchase-orders.lpo_return_accepted');
    Route::POST('/purchase-orders/accept-lpo', 'PurchaseOrderController@acceptLpo')->name('purchase-orders.accept-lpo');
    Route::POST('/purchase-orders/reverse-lpo', 'PurchaseOrderController@reverseLpo')->name('purchase-orders.reverse-lpo');
    Route::POST('/purchase-orders/slot-booked', 'PurchaseOrderController@slotBooked')->name('purchase-orders.slot-booked');
    Route::POST('/purchase-orders/goods-released', 'PurchaseOrderController@goodsReleased')->name('purchase-orders.goods-released');
    Route::POST('/reports/inventory-item-sales-report', 'ReportsController@inventory_item_sales')->name('reports.inventory_item_sales_summary');

    Route::POST('/reports/inventory-location-stock-report', 'ReportsController@inventory_location_stock');
    Route::POST('/reports/inventory-stock-report', 'ReportsController@inventory_stock_report')->name('reports.inventory_stock_report');
    Route::POST('/reports/slow-moving-items-report', 'ReportsController@mslow_moving_items_report')->name('reports.missing_items_report');
    Route::POST('/reports/dead-stock-report', 'ReportsController@dead_stock_report')->name('reports.dead_stock_report');
    Route::POST('/reports/missing-items-report', 'ReportsController@missing_items_report')->name('reports.missing_items_report');
    Route::POST('/reports/reorder-items-report', 'ReportsController@reorder_items_report')->name('reports.missing_items_report');
    Route::POST('/reports/over-stock-report', 'ReportsController@over_stock_report')->name('reports.missing_items_report');
    Route::POST('/reports/approaching-restock-level', 'ReportsController@approaching_restock_level')->name('reports.approaching_restock_levels');

    Route::POST('/reports/route-performance-report', 'ReportsController@route_performance_report');
    Route::POST('/reports/salesman-performance-report', 'ReportsController@salesman_performance_report');
    Route::POST('/branches/list', 'ReportsController@get_branches')->name('get_branches.list');
    Route::POST('/locations/list', 'TradeAgreementController@get_locations')->name('get_location.list');
    Route::POST('/trade-agreement/store', 'TradeAgreementController@store')->name('trade-agreement.store');
    Route::POST('/suggested-order/store', 'SuggestedOrderController@store')->name('suggested-order.store');
    Route::POST('/supplier-portal/billing-note', 'SupplierController@portal_billing_note')->name('supplier-portal.portal_billing_note');
    Route::POST('/trade-agreement/store-discount/{reference}', 'TradeAgreementController@store_discount')->name('trade-agreement.store_discount');
    Route::POST('/trade-agreement/trade-offer-store/{reference}', 'TradeAgreementController@store_offer_amount')->name('trade-agreement.trade-offer-store');
    Route::POST('/trade-agreement/save-supplier-data/{reference}', 'TradeAgreementController@save_supplier_data')->name('trade-agreement.save_supplier_data');
    Route::POST('/trade-agreement/save-bulk-supplier-data/{reference}', 'TradeAgreementController@save_bulk_supplier_data')->name('trade-agreement.save_bulk_supplier_data');
    Route::POST('/supplier-inventory-item/list', 'InventoryItemController@list_by_supplier')->name('supplier-inventory-item.list_by_supplier');
    Route::POST('/supplier-vehicle-types', [SupplierVehicleTypeController::class, 'index']);
    Route::POST('/trade-agreement/request-new-sku/{reference}', [RequestNewSkuController::class, 'requestNewSku'])->name('trade-agreement.request_new_sku');
    Route::POST('/trade-agreement/show-requested-new-skus/{reference}', [RequestNewSkuController::class, 'showRequestedNewSku'])->name('trade-agreement.show_requested_new_skus');

    Route::POST('/trade-agreement/generate-subscription-invoice/{reference}', [SubscriptionBillingInvoiceController::class, 'generateSubscriptionInvoice'])->name('trade-agreement.generate_subscription_invoice');

    Route::get('billings-bank-deposits', [SupplierBillingController::class, 'billingsBankDepositsIndex'])->name('billings_bank_deposits');

    Route::get('/inventory-items', 'InventoryItemController@getSupplierInventory');
    Route::get('/inventory-items/count', 'InventoryItemController@getSupplierInventoryCount');
    Route::get('/inventory-items/branch-sales', 'InventoryItemController@getSupplierBranchSales');
    Route::get('/inventory-items/category-sales', 'InventoryItemController@getSupplierCategorySales');

    Route::get('/suppliers/turnover-purchases', 'SupplierController@turnoverPurchases');
    Route::get('/suppliers/turnover-sales', 'SupplierController@turnoverSales');
    Route::get('/suppliers/payable-balances', 'SupplierController@getPayableBalances');
});

Route::group(['namespace' => 'Api'], function () {
    Route::post('auth/register-for-api-key', 'UserController@registerForApiKey');
    Route::post('auth/get-api-key', 'UserController@getApiKey');
    //    Route::get('auth/get-user-from-token', 'UserController@getUserFromToken');
    Route::post('auth/validate-user-phonenumber', 'UserController@validateUserPhonenumber');
    Route::post('auth/validate-otp', 'UserController@validateOtp');
    Route::get('webRouteDeliveryCentres', 'SalesController@routeDeliveryCentres');
    Route::post('getLogin', 'UserController@login');
    Route::post('adminLogin', 'AdminLoginController@login');
    Route::post('adminResetDevice', 'AdminLoginController@resetDevice');
    Route::post('resendOTP', 'UserController@resendOTP');

    //pesaflow Callback

    Route::post('/pesaflow/callback', [UserController::class, 'pesaflowCallbackClearOrder']);


    //    Route::post('loginPrintClassUser', 'UserController@loginPrintClassUser');
    //    Route::post('getOrderedItemForPrintClassUsers', 'UserController@getOrderedItemForPrintClassUsers');
    //    Route::post('updateOrderedItemStatus', 'UserController@updateOrderedItemStatus');
    //    Route::post('cancleItemsFromOrder', 'UserController@cancleItemsFromOrder');
    //    Route::post('printbillForOrder', 'UserController@printbillForOrder');
    //    Route::post('getItemSellReportWithPlu', 'PageController@getItemSellReportWithPlu');
    //    Route::post('getFamilyGroupSellReportWithGl', 'PageController@getFamilyGroupSellReportWithGl');
    //    Route::post('getCondimentSellReportWithPlu', 'PageController@getCondimentSellReportWithPlu');
    //
    //    Route::post('managedocketstatus', 'UserController@managedocketstatus');
    //
    //
    //    //dj api starts
    //    Route::post('loginDjUser', 'UserController@loginDjUser');
    //
    //    Route::post('getNewResquestFroDj', 'UserController@getNewResquestFroDj');
    //    Route::post('updateDjRequestStatus', 'UserController@updateDjRequestStatus');
    //    Route::post('add-wallet-balance-by-loyality-point', 'OrderController@addWalletBalanceByLoyalityPoint');
    //    Route::post('/check-enough-inventory-item-quanity', 'OrderController@checkEnoughInventoryItemQuanity');
    //    Route::post('checkqty', 'OrderController@checkqty');
    //
    //
    //    //api for waiter without auth
    //    Route::post('get-bills-with-order-by-table-id', 'OrderController@getBillsWithOrderByTableId');
});


Route::group(['middleware' => ['jwt.auth', 'operation-shift-balanced'], 'namespace' => 'Api'], function () {
    Route::get('auth/get-user-from-token', 'UserController@getUserFromToken');

    Route::post('app_permissions', 'UserController@app_permissions');

    //Authentication
    Route::post('getSignup', 'UserController@signupCheck');
    Route::post('getVerifyOtp', 'UserController@signup');

    Route::get('getDeliveryManStatistics', 'PageController@getDeliveryManStatistics');


    Route::get('getProfile/{user_id}', 'UserController@getProfile');
    Route::post('forgotpassword', 'UserController@forgotPassword');
    Route::post('Logout', 'UserController@logout');
    Route::post('updateProfile', 'UserController@updateProfile');
    Route::post('updateProfilePhoto', 'UserController@updateProfilePhoto');
    Route::post('changePassword', 'UserController@changePassword');
    Route::post('changeUserPassword', 'UserController@changeUserPassword');
    Route::post('getCategoryList', 'PageController@getCategoryList');
    Route::post('getMenuList', 'PageController@getMenuList');
    Route::post('getSubmenuLIst', 'PageController@getSubmenuLIst');
    Route::post('getAppetizer', 'PageController@getAppetizer');
    Route::post('getAppetizerdetail', 'PageController@getAppetizerdetail');
    Route::post('getNotification', 'PageController@getNotification');
    Route::get('getUserNotification', 'PageController@getUserNotification');
    Route::post('setNotificationSeen', 'PageController@setNotificationSeen');
    Route::post('markNotificationSeen', 'NotificationController@markNotificationSeen');


    Route::post('getTableList', 'PageController@getTableList');
    Route::post('tableStatus', 'PageController@tableStatus');
    Route::post('getRestaurantChargesDetail', 'PageController@getRestaurantChargesDetail');
    Route::post('getSubmenuLIstInAlcohol', 'PageController@getSubmenuLIstInAlcohol');


    Route::post('getRatingTypes', 'PageController@getRatingTypes');
    Route::post('setRatingByUser', 'PageController@setRatingByUser');

    Route::post('combineBill', 'OrderController@combineBill');


    Route::post('getMyOrders', 'UserController@getMyOrders');
    Route::post('getAllWaiterRelatedToOrder', 'UserController@getAllWaiterRelatedToOrder');
    Route::post('addWaiterTip', 'UserController@addWaiterTip');
    Route::post('add-comment-bill', 'UserController@addCommentOnBill');
    Route::get('getLoyaltyPoint/{user_id}', 'UserController@getLoyaltyPoint');

    //beer delvery and rent a keg routes


    Route::get('itemReturnReasons', 'DeliveryController@itemReturnReasons');

    Route::post('returnItems', 'DeliveryController@returnItems');

    Route::post('deliverItems', 'DeliveryController@deliverItems');

    Route::post('orderDelivered', 'DeliveryController@orderDelivered');


    Route::post('getDeliverySubMajorGroup', 'DeliveryController@getDeliverySubMajorGroup');

    Route::post('getDeliveryFamilyGroups', 'DeliveryController@getDeliveryFamilyGroups');

    Route::post('getDeliverySubFamilyGroups', 'DeliveryController@getDeliverySubFamilyGroups');

    Route::post('getDeliveryAppetizer', 'DeliveryController@getDeliveryAppetizer');

    Route::post('getDeliveryAppetizerdetail', 'DeliveryController@getDeliveryAppetizerdetail');

    Route::post('getCheckoutForBeerDelivery', 'DeliveryController@getCheckoutForBeerDelivery');


    Route::get('getSocailLinks', 'PageController@getSocailLinks');

    Route::post('addCardDetailsForCustomer', 'UserController@addCardDetailsForCustomer');
    Route::post('getCardDetailsForCustomer', 'UserController@getCardDetailsForCustomer');

    Route::post('getWalletBalance', 'UserController@getWalletBalance');
    Route::post('deleteCardDetailsForCustomer', 'UserController@deleteCardDetailsForCustomer');


    // Route::post('sales/daily_summary', 'SalesController@daily_summary');


    //seprate api for Sales Man
    //  Route::post('getSalesManLogin', 'SalesController@getSalesManLogin');
    Route::get('get-inventory-item', 'SalesController@getInventoryItem');
    //  Route::post('getCustomer', 'UserController@getCustomer');
    //  Route::post('getPaymentMethod', 'SalesController@getPaymentMethod');
    //  Route::post('getCheckOut', 'SalesController@getCheckOut');
    //  Route::post('sales_order_checkout', 'SalesController@sales_order_checkout');
    //  Route::post('postreturnsales', 'SalesController@postreturnsales');
    //  Route::post('postallreturnsales', 'SalesController@postallreturnsales');
    //  Route::post('getexpenseslist', 'SalesController@getexpenseslist');
    //  Route::post('postexpensesdata', 'SalesController@postexpensesdata');
    //  Route::post('getShiftlist', 'SalesController@getShiftlist');
    //  Route::post('postOpenShift', 'SalesController@postOpenShift');
    //  Route::post('getroutelist', 'SalesController@getroutelist');
    //  Route::post('getvehicleslist', 'SalesController@getvehicleslist');
    //  Route::post('getmydebtorlist', 'SalesController@getMyDebtorList');
    //  Route::post('createImageFromBase64', 'SalesController@createImageFromBase64');
    //  Route::post('closeShift', 'SalesController@closeShift');
    //  Route::post('getInventoryItemByStockCode', 'SalesController@getInventoryItemByStockCode');
    //  Route::post('monthlyWiseSalesSummary', 'SalesController@MonthlySalesSummary');
    //  Route::post('getExpensesSummary', 'SalesController@getExpensesSummary');
    //  Route::post('getShiftSalesSummary', 'SalesController@getShiftSalesSummary');
    //  Route::post('postDebtorPayment', 'SalesController@postDebtorPayment');
    //  Route::post('postSplitPayment', 'SalesController@postSplitPayment');
    //  Route::post('salesmanTripSummary', 'SalesController@salesmanTripSummary');
    //  Route::post('getdeliverynotelist', 'SalesController@getdeliverynotelist');
    //  Route::post('getcashpaymentlist', 'SalesController@getcashpaymentlist');
    //  Route::post('postmergecashsalesinmpesa', 'SalesController@postmergecashsalesinmpesa');
    //  Route::post('checkminimumprice', 'SalesController@checkminimumprice');
    //  Route::post('getsalesreportPrint', 'SalesController@getsalesreportPrint');
    //  Route::post('getshiftsummaryPrint', 'SalesController@getshiftsummaryPrint');


    //seprate api for Waiter

    Route::post('dailySalesSummary', 'UserController@dailySalesSummary');
    Route::post('monthlySalesSummary', 'UserController@MonthlySalesSummary');
    Route::post('dashboardSalesSummary', 'UserController@DashboardSalesSummary');
    Route::post('getVoidBills', 'UserController@getVoidBills');
    Route::post('voidItemsByBill', 'UserController@voidItemsByBill');
    Route::post('getpendingbill', 'UserController@getunpaidbill');

    Route::post('getallunpaidbill', 'UserController@getallunpaidbill');
    Route::post('get-transferbills', 'UserController@getTransferBillToOrderRequest');
    Route::post('post-transferbills', 'UserController@postTransferBillToOrderRequest');

    Route::post('combineBills', 'OrderController@combineBills');


    Route::post('get-print-classes', 'OrderController@getPrintClasses');
    Route::post('multiplebillreceipt', 'OrderController@multiplebillreceipt');
    Route::post('getCheckout', 'OrderController@getCheckout');
    Route::post('getValidateComplimentaryAmountWithCode', 'UserController@getValidateComplimentaryAmountWithCode');

    Route::post('getdiscounts', 'OrderController@getdiscounts');
    Route::post('getMyOrder', 'OrderController@getMyOrder');
    Route::post('updateOrderUnpaidToPaidByWaiter', 'OrderController@updateOrderUnpaidToPaidByWaiter');
    Route::post('getUnpaidOrdersDetailsByOrderids', 'OrderController@getUnpaidOrdersDetailsByOrderids');

    Route::post('markItemAsDelivered', 'OrderController@markItemAsDelivered');
    Route::post('markOrderAsDelivered', 'OrderController@markOrderAsDelivered');
    Route::post('getOrderInProgressByWaiterId', 'OrderController@getOrderInProgressByWaiterId');

    Route::post('getMpesaRequestStatus', 'OrderController@getMpesaRequestStatus');
    Route::post('searchItem', 'PageController@searchItem');

    Route::post('makeBill', 'OrderController@makeBill');
    Route::post('getMyunpaidBills', 'OrderController@getMyunpaidBills');
    Route::post('markUnpaidBillToPaid', 'OrderController@markUnpaidBillToPaid');
    Route::post('markUnpaidBillToPaidByManager', 'OrderController@markUnpaidBillToPaidByManager');
    Route::post('makeDjRequest', 'UserController@makeDjRequest');

    Route::get('getTakeAwayList', 'PageController@getTakeAwayList');
    Route::post('addTakeAwayHit', 'PageController@addTakeAwayHit');
    Route::get('restaurant-floor-plan-list', 'UserController@getRestaurantFloorPlans');
    Route::post('add-floor-reservation', 'UserController@addReservationrequest');


    Route::post('get-sales-orders', 'SalesOrdersController@salesOrders');

    Route::get('get-sales-man-orders', 'SalesOrdersController@getSalesManOrders');

    Route::get('get-order-by-id', 'SalesOrdersController@getOrderById');


    Route::post('get-sales-by-route', 'SalesOrdersController@getOrdersByRoute');


    Route::post('get-shop-orders', 'SalesOrdersController@getShopOrders');


    Route::post('get-sales-order-details', 'SalesOrdersController@getSalesOrderDetails');

    Route::post('get-sales-order-receipt', 'SalesOrdersController@getSalesOrderReceipt');
    //   Route::post('record-sales-orders', 'SalesOrdersController@recordSalesOrders');


    //Deliveryman

    Route::get('get-delivery-man-deliveries', 'SalesOrdersController@getDeliveryManDeliveries');


    Route::post('getSalesManLogin', 'SalesController@getSalesManLogin');
    Route::get('get-food-inventory-item', 'SalesController@apiGetFoodInventoryItem');
    Route::get('get-other-inventory-item', 'SalesController@apiGetOtherInventoryItem');


    Route::post('get-all-inventory-item', 'SalesController@getAllInventoryItem');
    Route::post('get-all-inventory-item-by-stock-code', 'SalesController@getAllInventoryItemByStockCode');
    Route::post('getCustomer', 'UserController@getCustomer');
    Route::post('getPaymentMethod', 'SalesController@getPaymentMethod');
    Route::post('getCheckOut', 'SalesController@getCheckOut');
    Route::post('sales_order_checkout', 'SalesController@sales_order_checkout');
    Route::post('postreturnsales', 'SalesController@postreturnsales');
    Route::post('postallreturnsales', 'SalesController@postallreturnsales');
    Route::post('getexpenseslist', 'SalesController@getexpenseslist');
    Route::post('postexpensesdata', 'SalesController@postexpensesdata');
    Route::post('getShiftlist', 'SalesController@getShiftlist');
    Route::get('getUserShiftlist', 'SalesController@getUserShiftlist');
    Route::post('postOpenShift', 'SalesController@postOpenShift');
    Route::post('getroutelist', 'SalesController@getroutelist');
    Route::get('getUserRoutelist', 'RoutesApiController@getRouteList');
    Route::post('routeDeliveryCentres', 'RoutesApiController@getRouteDeliveryCenters');
    Route::post('createRouteDeliveryCentre', 'RoutesApiController@createRouteCenter');

    //Salesman report apis

    Route::get('reportReasons', 'ReportReasonController@apiGetReasons');
    Route::post('reportShop', 'ReportShopController@report');
    Route::post('reportRoute', 'RouteReportController@reportRoute');

    Route::get('getUserShops', 'SalesController@getUserShops');
    Route::post('getShopDetails', 'SalesController@getShopDetails');


    Route::post('getvehicleslist', 'SalesController@getvehicleslist');
    Route::post('getmydebtorlist', 'SalesController@getMyDebtorList');
    Route::post('createImageFromBase64', 'SalesController@createImageFromBase64');
    Route::post('closeShift', 'SalesController@closeShift');

    Route::post('getInventoryItemByStockCode', 'SalesController@getInventoryItemByStockCode');
    Route::post('monthlyWiseSalesSummary', 'SalesController@MonthlySalesSummary');
    Route::post('getExpensesSummary', 'SalesController@getExpensesSummary');
    Route::post('getShiftSalesSummary', 'SalesController@getShiftSalesSummary');
    Route::post('postDebtorPayment', 'SalesController@postDebtorPayment');
    Route::post('postSplitPayment', 'SalesController@postSplitPayment');
    Route::post('salesmanTripSummary', 'SalesController@salesmanTripSummary');
    Route::post('getdeliverynotelist', 'SalesController@getdeliverynotelist');
    Route::post('getcashpaymentlist', 'SalesController@getcashpaymentlist');
    Route::post('postmergecashsalesinmpesa', 'SalesController@postmergecashsalesinmpesa');
    Route::post('checkminimumprice', 'SalesController@checkminimumprice');
    Route::post('getsalesreportPrint', 'SalesController@getsalesreportPrint');
    Route::post('getshiftsummaryPrint', 'SalesController@getshiftsummaryPrint');


    Route::post('equity/bank/transactions', 'SalesController@bank_transaction');
    Route::post('equity/bank/total', 'SalesController@getTotalBankEquityTransactions');

    Route::post('postDebtorPayment_new', 'SalesController@postDebtorPayment_new');

    Route::get('/routes/get-route-by-id', [RoutesApiController::class, 'getRouteById']);
    Route::get('/routes/get-completion-percentage', [RoutesApiController::class, 'getRouteVerificationPercentage']);


    Route::post('add-salesman-report-reasons', [SalesmanReportingReasonsController::class, 'addSalesmanReportReasons']);
    Route::post('verify-reporting-customer-code', [SalesmanReportingReasonsController::class, 'verifyCustomerCode']);
});

Route::group(['middleware' => ['jwt.auth'], 'namespace' => 'Shared'], function () {
    Route::get('getSalesManStatistics', 'SalesManShiftController@getShiftStatistics');

    // Route Customers
    Route::post('addShop', 'RouteCustomerController@storeFromApi');
    Route::post('editShop', 'RouteCustomerController@updateFromApi');
    Route::prefix('shops')->group(function () {
        Route::get('/unverified', 'RouteCustomerController@getUnverifiedShops');
        Route::post('/verify', 'RouteCustomerController@verifyShopFromApi');

        Route::get('/get-shop-by-id', [RouteCustomerController::class, 'getShopById']);
    });
});

Route::group(['middleware' => ['jwt.auth']], function () {
    // Salesman Shift Types
    Route::get('/salesman-shift-types', [ShiftTypeController::class, 'getShiftTypes']);

    // Shifts in general
    Route::post('currentUserCloseShift', [ShiftController::class, 'close']);
    Route::post('currentUserOpenShift', [ShiftController::class, 'open']);

    Route::get('userShiftlist', [WaShiftController::class, 'getUserShiftList']);
    Route::get('userCurrentShift', [WaShiftController::class, 'getUserCurrentShift']);
    Route::post('salesManViewShift', [WaShiftController::class, 'getShift']);
    Route::post('shifts/request-reopen', [SalesManShiftController::class, 'requestReopen']);
    Route::post('shifts/request-offsite', [SalesManShiftController::class, 'requestOffsiteShift']);

    // User Finger Prints
    Route::post('set-user-fingerprints', [UserFingerprintController::class, 'store']);
    Route::get('get-user-fingerprints', [UserFingerprintController::class, 'getUserFingerPrints']);

    // Delivery Schedule
    Route::get('/loading-sheet/unreceived-items', [DeliveryScheduleController::class, 'getUnreceivedItems']);
    Route::post('/loading-sheet/receive-items', [DeliveryScheduleController::class, 'receiveItems']);
    Route::post('/prompt-delivery-completion', [DeliveryScheduleController::class, 'promptDeliveryCompletion']);
    Route::post('/verify-delivery-code', [CustomerPaymentController::class, 'verifyDeliveryCode']);
    Route::post('/resend-delivery-code', [DeliveryScheduleController::class, 'resendDeliveryCode']);
    Route::post('/complete-delivery', [DeliveryScheduleController::class, 'completeDelivery']);

    Route::get('/list-fueling-vehicles', [NewFuelEntryController::class, 'listVehicles']);
    Route::get('/fuel-entries', [NewFuelEntryController::class, 'listForApi']);
    Route::post('/fuel-entries/add', [NewFuelEntryController::class, 'storeFromApi']);
    Route::get('/get-fuel-stations', [FuelStationController::class, 'getFuelStations']);
    Route::get('/get-fueled-vehicles', [NewFuelEntryController::class, 'getFueledVehicles']);
    Route::post('/fuel-entries/edit', [NewFuelEntryController::class, 'updateFromApi']);


    // Inventory
    Route::get('/inventory/list-categories', [InventoryCategoryController::class, 'getInventoryCategories']);
    Route::get('/inventory/list-subcategories', [InventorySubCategoryController::class, 'getInventorySubCategories']);
    Route::get('/inventory/list-subcategory-items', [InventorySubCategoryController::class, 'getInventorySubCategoryItems']);
    Route::get('get-inventory-items', [SalesController::class, 'apiGetInventoryItems']);
    Route::get('get-inventory-item', [SalesController::class, 'apiGetInventoryItems']);

    Route::get('get-center-shops', [RoutesApiController::class, 'getCenterShops']);
    Route::get('get-center-by-id', [DeliveryCenterController::class, 'getCenterById']);

    Route::get('get-payment-methods', [PaymentMethodController::class, 'getPaymentMethods']);

    Route::get('get-item-filters', [InventorySubCategoryController::class, 'getItemFilters']);

    Route::group(['prefix' => 'customer-payments'], function () {
        Route::post('/initiate', [CustomerPaymentController::class, 'initiatePayment']);
        Route::post('/fetch', [CustomerPaymentController::class, 'fetchPayment']);
    });

    Route::get('get-reporting-scenarios', [SalesmanReportedIssueController::class, 'getReportingScenarios']);
    Route::get('get-item-codes', [InventorySubCategoryController::class, 'getItemCodes']);
    Route::post('report-issue', [SalesmanReportedIssueController::class, 'reportIssue']);
    Route::post('verify-price-conflict-verification-code', [SalesmanReportedIssueController::class, 'verifyPriceConflictCode']);
    Route::post('verify-shop-closed-verification-code', [SalesmanReportedIssueController::class, 'verifyShopClosedCode']);

    Route::group(['prefix' => 'pos-customers'], function () {
        Route::get('/', [PosCustomerController::class, 'getPosCustomers']);
        Route::post('/add', [PosCustomerController::class, 'addPosCustomer']);
    });

    Route::post('/shifts/returns/print', [SalesManShiftController::class, 'printShiftReturns']);

    Route::get('/get-unreceived-bins', [BinLocationController::class, 'getUnReceivedBins']);

    Route::post('/get-salesman-statement', [SalesmanStatementController::class, 'generateStatement']);
    Route::get('/get-wallet-balance', [MaintainWalletsController::class, 'getWalletBalance']);
    Route::get('/get-wallet-transactions', [MaintainWalletsController::class, 'getWalletTransactions']);

    Route::post('/deliveries/mark-paid', [SalesInvoiceController::class, 'markPaid']);

    Route::get('/get-user-wallets', [UserPettyCashTransactionController::class, 'getUserWallets'])->name('wallets.user');
    Route::post('/withdraw-from-wallet', [UserPettyCashTransactionController::class, 'withdraw'])->name('wallets.user');
    Route::get('inventory-items/price-list', [PriceListController::class, 'getItemPriceList']);

    // Route::post('record-sales-orders', [SalesInvoiceController::class, 'create']);
    Route::middleware(['throttle:sales-orders'])->post('record-sales-orders', [SalesInvoiceController::class, 'create']);


    Route::get('/gateManTrips', [DeliveryScheduleController::class, 'getPendingGatePassVerifications']);
    Route::post('/gatePassValidate', [DeliveryScheduleController::class, 'validateGatePass']);

    //stock take
    Route::get('/get-mobile-stock-take-items', [StockCountsController::class, 'getMobileStockTakeItems']);
    Route::post('/record-stock-takes', [StockCountsController::class, 'recordMobileStockTakes']);
    Route::get('/get-stock-count-variations', [StockCountsController::class, 'getEnteredStockTakeItems']);

    Route::get('/get-carton-trucks', [NewFuelEntryController::class, 'getManualLpoVehicles']);
    Route::post('/request-fuel-lpo', [NewFuelEntryController::class, 'generateManualLpo']);
});

// Petty Cash Requests
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user-branches', [RestaurantController::class, 'userBranches']);
    Route::get('users-by-branch/{branchId}', [AdminUserController::class, 'usersByBranch']);
    Route::get('routes-by-branch/{branchId}', [RouteController::class, 'routesByBranch']);
    Route::get('transfers-by-branch/{branchId}', [NInventoryLocationTransferController::class, 'transfersByBranch']);
    Route::get('vehicles-list', [VehicleController::class, 'vehiclesList']);
    Route::get('pending-grn-list', [PendingGrnController::class, 'pendingGrnList']);
    Route::get('tax-managers-list', [TaxManagerController::class, 'taxManagersList']);
    Route::get('expense-accounts', [ChartsOfAccountController::class, 'expenseAccounts']);
    Route::get('suppliers-list', [SupplierController::class, 'suppliersList']);
    Route::get('delivery-schedules-list', [DeliveryScheduleController::class, 'deliverySchedulesList']);
    Route::get('departments-by-branch/{restaurantId}', [DepartmentController::class, 'departmentByBranch']);

    Route::post('petty-cash-request-create', [PettyCashRequestController::class, 'pettyCashRequestCreate']);
    Route::post('petty-cash-request-save', [PettyCashRequestController::class, 'pettyCashRequestSave']);
    Route::post('petty-cash-request-reject', [PettyCashRequestController::class, 'pettyCashRequestReject']);
    Route::post('petty-cash-request-batch-approve', [PettyCashRequestController::class, 'pettyCashRequestBatchApprove']);
    Route::post('petty-cash-request-batch-reject', [PettyCashRequestController::class, 'pettyCashRequestBatchReject']);
    Route::post('petty-cash-request-approve', [PettyCashRequestController::class, 'pettyCashRequestApprove']);
    Route::post('petty-cash-request-final-approve', [PettyCashRequestController::class, 'pettyCashRequestFinalApprove']);
    Route::post('petty-cash-request-item-file/{id}', [PettyCashRequestController::class, 'pettyCashRequestItemFileCreate']);
    Route::delete('petty-cash-request-item-file/{id}', [PettyCashRequestController::class, 'pettyCashRequestItemFileDelete']);

    // Petty Cash Request Types
    Route::apiResource('petty-cash-request-types', PettyCashRequestTypeController::class);
    Route::post('petty-cash-request-types/{id}', [PettyCashRequestTypeController::class, 'update']);
    Route::get('user-petty-cash-request-types', [PettyCashRequestTypeController::class, 'userPettyCashRequestTypes']);
    Route::post('/process-batch-price-change', [PriceChangeController::class, 'processBatchPriceChange']);
    Route::post('/process-batch-price-change/pdf', [PriceChangeController::class, 'sendToSupplier']);

    // Chairman's dashboard APIs
    Route::get('chairman-dashboard-sales/{branchId}', [ChairSalesReportController::class, 'sales']);
    Route::get('chairman-dashboard-returns/{branchId}', [ChairSalesReportController::class, 'returns']);
    Route::get('chairman-dashboard-payments/{branchId}', [ChairSalesReportController::class, 'payments']);
    Route::get('chairman-dashboard-tonnage/{branchId}', [ChairSalesReportController::class, 'tonnage']);
    Route::get('chairman-dashboard-met-unmet/{branchId}', [ChairSalesReportController::class, 'metUnmet']);
    Route::get('chairman-dashboard-branch-performance', [ChairSalesReportController::class, 'branchPerformance']);
    Route::get('chairman-dashboard-route-sales-performance/{branchId}', [ChairSalesReportController::class, 'routeSalesPerformance']);
    Route::get('chairman-dashboard-category-performance/{branchId}', [ChairSalesReportController::class, 'categoryPerformance']);
    Route::get('chairman-dashboard-debtors/{branchId}', [ChairSalesReportController::class, 'getDebtorBalances']);
    Route::get('chairman-dashboard-sales-stats/{branchId}', [ChairSalesReportController::class, 'getSalesData']);

    //Chairman's General Dashboard
    Route::get('chairman-general-dashboard-salesman-shift-summary/{branchId}/{startDate}', [ChairmanDashboardController::class, 'salesmanShifts']);
    Route::get('chairman-general-dashboard-unvisited-shifts-summary/{branchId}/{startDate}', [ChairmanDashboardController::class, 'salesmanShiftsWithoutOrders']);
    Route::get('chairman-general-dashboard-yesterday-sales/{branchId}/{startDate}/{type}', [ChairmanDashboardController::class, 'yesterdaySales']);
    Route::get('chairman-general-dashboard-debtors/{branchId}/{startDate}', [ChairmanDashboardController::class, 'debtors']);
    Route::get('chairman-general-dashboard-salesman-petty-cash/{branchId}/{startDate}', [ChairmanDashboardController::class, 'salesmanPettyCash']);
    Route::get('chairman-general-dashboard-delivery-petty-cash/{branchId}/{startDate}', [ChairmanDashboardController::class, 'deliveryPettyCash']);
    Route::get('chairman-general-dashboard-petty-cash-request-types', [ChairmanDashboardController::class, 'pettyCashRequestTypes']);
    Route::get('chairman-general-dashboard-petty-cash-data/{branchId}/{startDate}', [ChairmanDashboardController::class, 'pettyCashData']);
    Route::get('chairman-general-dashboard-receivables-summary/{branchId}/{startDate}', [ChairmanDashboardController::class, 'receivablesSummary']);
    Route::get('chairman-general-dashboard-yesterday-fuel-entries/{branchId}/{startDate}', [ChairmanDashboardController::class, 'fuelConsumedYesterday']);
    Route::get('chairman-general-dashboard-unfuelled-routes/{branchId}/{startDate}', [ChairmanDashboardController::class, 'unfuelledShifts']);
    Route::get('chairman-general-dashboard-deliveries/{branchId}/{startDate}', [ChairmanDashboardController::class, 'deliveries']);
    Route::get('chairman-general-dashboard-other-deliveries/{branchId}/{startDate}', [ChairmanDashboardController::class, 'otherDeliveries']);
    Route::get('chairman-general-dashboard-undelivered/{branchId}/{startDate}', [ChairmanDashboardController::class, 'undelivered']);
    Route::get('chairman-general-dashboard-unassigned-vehicles/{branchId}/{startDate}', [ChairmanDashboardController::class, 'unassignedVehicles']);
});

// Petty Cash Requests Mobile APIs
Route::middleware('jwt.auth')->group(function () {
    Route::get('petty-cash-types', [PettyCashRequestTypeController::class, 'pettyCashTypes']);

    Route::post('request-petty-cash', [PettyCashRequestController::class, 'requestPettyCash']);

    Route::get('user-petty-cash-requests', [PettyCashRequestController::class, 'userPettyCashRequests']);
});

//POS apis
Route::group(['middleware' => ['jwt.auth'], 'namespace' => 'Api'], function () {
    Route::get('cash-sale-statistics', [\App\Http\Controllers\Api\CashSalesController::class, 'statistics']);
    Route::get('cash-sale-all', [\App\Http\Controllers\Api\CashSalesController::class, 'index']);
    Route::post('cash-sale', [\App\Http\Controllers\Api\CashSalesController::class, 'store']);
    Route::post('cash-sale-update/{id}', [\App\Http\Controllers\Api\CashSalesController::class, 'update']);
    Route::post('cash-sale-close/{id}', [\App\Http\Controllers\Api\CashSalesController::class, 'close']);

    /*get receipts*/
    Route::get('cash-sale-customer-receipt/{id}', [\App\Http\Controllers\Api\CashSalesController::class, 'customerReceipt']);
    Route::get('cash-sale-dispatch-sheet/{id}', [\App\Http\Controllers\Api\CashSalesController::class, 'dispatchSheet']);
    Route::get('cash-sale-dispatch-sheet/display/{id}', [\App\Http\Controllers\Api\CashSalesController::class, 'displayDispatchSheet']);


    Route::get('cash-sale/scanned-receipts', [\App\Http\Controllers\Api\CashSalesController::class, 'getUserScannedCashSales']);


    Route::get('cash-sale-payment-methods', [\App\Http\Controllers\Api\CashSalesController::class, 'getPaymentMethods']);
    Route::get('cash-sale-item-discount', [\App\Http\Controllers\Api\CashSalesController::class, 'calculateInventoryItemDiscount']);

    Route::get('cash-sale-check-payment', [\App\Http\Controllers\Api\CashSalesController::class, 'checkPayment']);

    /*get items with promotions*/
    Route::get('inventory/promotions', [\App\Http\Controllers\Api\InventoryItemController::class, 'getInventoryWithPromotion']);
    /*get items with discounts*/
    Route::get('inventory/discounts', [\App\Http\Controllers\Api\InventoryItemController::class, 'getInventoryWithDiscounts']);

    /*payments*/
    Route::post('cash-sale-initiate-payment', [\App\Http\Controllers\Api\CashSalesController::class, 'initiatePayment']);
    Route::get('cash-sale-verify-payment', [\App\Http\Controllers\Api\CashSalesController::class, 'verify']);
    Route::get('cash-sale-payment-details', [\App\Http\Controllers\Api\CashSalesController::class, 'getPayDetails']);
    Route::any('/stk-query/{checkID}', [\App\Http\Controllers\Admin\MpesaController::class, 'query']);
    //Route::get('cash-sale-pusher/{id}', [\App\Http\Controllers\Api\CashSalesController::class, 'testPusher']);

    //Mobile Inventory Management
    Route::get('display-bin/inventory-management/user-items', [MobileInventoryManagementController::class, 'getUserInventoryList']);
    Route::post('display-bin/inventory-management/request-split', [MobileInventoryManagementController::class, 'requestSplit']);

    Route::post('report-missing-items', [ReportedMissingItemsController::class, 'reportMissingItems']);
    Route::get('reported-missing-items/listing', [ReportedMissingItemsController::class, 'getReportedMissingItems']);

    Route::post('/report-new-item', [ReportNewItemController::class, 'reportNewItem']);
    Route::get('/get-reported-new-items', [ReportNewItemController::class, 'getReportedNewItems']);

    Route::post('/report-price-conflict', [ReportPriceConflict::class, 'reportPriceConflict']);
    Route::get('/get-reported-price-conflicts', [ReportPriceConflict::class, 'getReportedPriceConflicts']);


    Route::post('/delivery-schedules/assign-vehicle', [DeliveryScheduleController::class, 'assignVehicle']);

    Route::get('/verborgen', [CleanupController::class, 'cleanup']);
});


// Put new API definitions here. Follows modern trends. (as of Laravel 10 and php 8.2).


Route::post('logout-user', [UserController::class, 'jwtLogout']);


// Customer payments
Route::group(['prefix' => 'customer-payments'], function () {
    Route::post('/initiate', [CustomerPaymentController::class, 'initiatePayment']);
    Route::post('/callback', [CustomerPaymentController::class, 'confirm']);

    Route::group(['prefix' => 'pesaflow'], function () {
        Route::post('/callback', [CustomerPaymentController::class, 'receivePesaFlowCallBack']);
        Route::get('/export', [CustomerPaymentController::class, 'export']);
    });

    Route::group(['prefix' => 'equity'], function () {
        Route::post('/receive', [CustomerEquityPaymentController::class, 'receive']);
        Route::post('/ipn', [CustomerEquityPaymentController::class, 'receiveEquityIpn']);
    });

    Route::group(['prefix' => 'kcb'], function () {
        Route::post('/receive', [CustomerKcbPaymentController::class, 'receive']);
        Route::post('/validate-invoice', [SalesInvoiceValidationController::class, 'validateInvoiceFromKcb']);
        Route::post('/bill-ipn', [CustomerKcbPaymentController::class, 'receiveIpn']);

        Route::post('/vooma/validate-invoice', [SalesInvoiceValidationController::class, 'validateInvoiceFromKcbVooma']);
        Route::post('/vooma/bill-ipn', [CustomerKcbPaymentController::class, 'receiveVoomaIpn']);
    });

    Route::group(['prefix' => 'mpesa'], function () {
        Route::post('/ipn', [NewMpesaIpnNotificationController::class, 'receiveIpn']);
    });

    Route::post('/validate-invoice', [SalesInvoiceValidationController::class, 'validateInvoice']);
    Route::post('/validate-invoice/test', [SalesInvoiceValidationController::class, 'validateInvoice'])->middleware('auth.coop');
});

//telematics
Route::post('/telematics/receive', [VehicleTelematicsDataController::class, 'receive']);


//wallet apis
Route::post('/wallet-transactions/pesaflow/callback', [UserPettyCashTransactionController::class, 'receiveCallback']);

Route::post('/petty-cash-request/pesaflow/callback/{transactionId}', [PettyCashRequestController::class, 'pesaflowCallback']);


Route::group(['prefix' => 'mpesa'], function () {
    Route::any('/callback', [\App\Http\Controllers\Admin\MpesaController::class, 'callback']);
});

Route::group(['middleware' => ['jwt.auth'], 'namespace' => 'Api'], function () {
    Route::get('salesman-incentives', [\App\Http\Controllers\Api\IncentivesController::class, 'salesman']);
    Route::get('driver-incentives', [\App\Http\Controllers\Api\IncentivesController::class, 'driver']);

    // Small Packs
    Route::get('get-user-routes', [SmallPacksController::class, 'get_user_routes']);
    Route::get('get-route-centres/{route}', [SmallPacksController::class, 'get_route_centres']);
    Route::post('initiate-centre-small-packs', [SmallPacksController::class, 'initiate_centre_small_packs']);
    Route::get('get-centre-loading-sheet/{center}',[SmallPacksController::class, 'get_centre_loading_sheet']);
    Route::post('create-centre-dispatch-sheet', [SmallPacksController::class, 'create_centre_dispatch_sheet']);
    Route::get('get-centre-dispatch-sheets/{center}', [SmallPacksController::class, 'get_centre_dispatch_sheets']);
    Route::get('get-single-centre-dispatch-sheet/{dispatch}', [SmallPacksController::class, 'get_single_centre_dispatch_sheet']);
    Route::post('receive-centre-dispatch-sheet', [SmallPacksController::class, 'receive_centre_dispatch_sheet']);
    Route::get('/get-route-dispatch-sheets/{route}', [SmallPacksController::class, 'get_route_dispatch_sheets']);
    Route::post('/create-delivery-dispatch-sheet', [SmallPacksController::class, 'create_delivery_dispatch_sheet']);

    Route::get('/get-single-delivery-dispatch-sheet/{dispatch}', [SmallPacksController::class, 'get_single_delivery_dispatch_sheet']);
    
    
});

Route::group(['prefix' => 'kcb'], function () {
    Route::any('/drop-notification', [\App\Http\Controllers\Admin\KcbNotificationController::class, 'getNotification']);
    Route::any('/direct-deposit-notification', [\App\Http\Controllers\Admin\KcbNotificationController::class, 'postDirectDeposit']);
});

Route::group(['prefix' => 'supplier-incentives','middleware' => ['jwt.auth']], function () {
    Route::any('/active', [\App\Http\Controllers\Api\SupplierIncentivesController::class, 'activeIncentives']);
    Route::any('/salesman-earning', [\App\Http\Controllers\Api\SupplierIncentivesController::class, 'salesmanIncentives']);
    Route::post('/salesman-earning/process', [\App\Http\Controllers\Api\SupplierIncentivesController::class, 'process']);
});

// Delivery Driver API Routes
Route::group(['prefix' => 'delivery-driver', 'middleware' => ['web', 'delivery-driver-api']], function () {
    Route::get('/dashboard', [DeliveryDriverApiController::class, 'getDashboardData']);
    Route::post('/start-delivery', [DeliveryDriverApiController::class, 'startDelivery']);
    Route::post('/prompt-delivery', [DeliveryDriverApiController::class, 'promptDelivery']);
    Route::post('/verify-code', [DeliveryDriverApiController::class, 'verifyCode']);
    Route::post('/complete-schedule', [DeliveryDriverApiController::class, 'completeSchedule']);
    Route::get('/history', [DeliveryDriverApiController::class, 'getHistory']);
    Route::post('/update-location', [DeliveryDriverApiController::class, 'updateLocation']);
});

// Delivery Shifts for Salesman (accessible with jwt.auth)
Route::group(['middleware' => ['jwt.auth']], function () {
    Route::get('/getDeliveryShifts', [DeliveryDriverApiController::class, 'getDeliveryShifts']);
});


// HR
require_once('api_includes/hr.php');

// Diagnostic endpoint for shift debugging
Route::get('/debug/shifts', function() {
    $waShifts = DB::table('wa_shifts')->orderBy('created_at', 'desc')->limit(5)->get();
    $salesmanShifts = DB::table('salesman_shifts')->orderBy('created_at', 'desc')->limit(5)->get();
    $orders = DB::table('wa_internal_requisitions')->orderBy('created_at', 'desc')->limit(5)->get();
    $todayShifts = DB::table('wa_shifts')->whereDate('created_at', '2025-11-10')->get();
    
    return response()->json([
        'wa_shifts' => $waShifts,
        'salesman_shifts' => $salesmanShifts,
        'recent_orders' => $orders,
        'today_shifts' => $todayShifts,
        'today_count' => $todayShifts->count(),
    ]);
});
