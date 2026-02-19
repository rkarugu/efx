# Salesman APIs & Endpoints - Complete List

## Authentication
- **POST** `/api/getSalesManLogin` - SalesController@getSalesManLogin

## Shift Management
- **GET** `/api/salesman-shift-types` - ShiftTypeController@getShiftTypes (jwt.auth)
- **GET** `/api/userShiftlist` - WaShiftController@getUserShiftList (jwt.auth)
- **GET** `/api/userCurrentShift` - WaShiftController@getUserCurrentShift (jwt.auth)
- **POST** `/api/salesManViewShift` - WaShiftController@getShift (jwt.auth)
- **POST** `/api/currentUserOpenShift` - ShiftController@open (jwt.auth)
- **POST** `/api/currentUserCloseShift` - ShiftController@close (jwt.auth)
- **POST** `/api/shifts/request-reopen` - SalesManShiftController@requestReopen (jwt.auth)
- **POST** `/api/shifts/request-offsite` - SalesManShiftController@requestOffsiteShift (jwt.auth)
- **GET** `/api/getSalesManStatistics` - SalesManShiftController@getShiftStatistics (jwt.auth)
- **POST** `/api/shifts/returns/print` - SalesManShiftController@printShiftReturns (jwt.auth)

## Routes & Locations
- **GET** `/api/getUserRoutelist` - RoutesApiController@getRouteList
- **POST** `/api/getroutelist` - SalesController@getroutelist
- **GET** `/api/get-route-by-id` - RoutesApiController@getRouteById
- **GET** `/api/routes/get-completion-percentage` - RoutesApiController@getRouteVerificationPercentage
- **POST** `/api/routeDeliveryCentres` - RoutesApiController@getRouteDeliveryCenters
- **POST** `/api/createRouteDeliveryCentre` - RoutesApiController@createRouteCenter
- **GET** `/api/get-center-shops` - RoutesApiController@getCenterShops (jwt.auth)
- **GET** `/api/get-center-by-id` - DeliveryCenterController@getCenterById (jwt.auth)

## Shops/Customers
- **GET** `/api/getUserShops` - SalesController@getUserShops
- **POST** `/api/getShopDetails` - SalesController@getShopDetails
- **POST** `/api/addShop` - RouteCustomerController@storeFromApi (jwt.auth)
- **POST** `/api/editShop` - RouteCustomerController@updateFromApi (jwt.auth)
- **GET** `/api/shops/unverified` - RouteCustomerController@getUnverifiedShops (jwt.auth)
- **POST** `/api/shops/verify` - RouteCustomerController@verifyShopFromApi (jwt.auth)
- **GET** `/api/shops/get-shop-by-id` - RouteCustomerController@getShopById (jwt.auth)

## Inventory Management
- **GET** `/api/get-inventory-item` - SalesController@getInventoryItem
- **GET** `/api/get-food-inventory-item` - SalesController@apiGetFoodInventoryItem
- **GET** `/api/get-other-inventory-item` - SalesController@apiGetOtherInventoryItem
- **POST** `/api/get-all-inventory-item` - SalesController@getAllInventoryItem
- **POST** `/api/get-all-inventory-item-by-stock-code` - SalesController@getAllInventoryItemByStockCode
- **POST** `/api/getInventoryItemByStockCode` - SalesController@getInventoryItemByStockCode
- **GET** `/api/inventory/list-categories` - InventoryCategoryController@getInventoryCategories (jwt.auth)
- **GET** `/api/inventory/list-subcategories` - InventorySubCategoryController@getInventorySubCategories (jwt.auth)
- **GET** `/api/inventory/list-subcategory-items` - InventorySubCategoryController@getInventorySubCategoryItems (jwt.auth)
- **GET** `/api/get-inventory-items` - SalesController@apiGetInventoryItems (jwt.auth)
- **GET** `/api/get-item-filters` - InventorySubCategoryController@getItemFilters (jwt.auth)
- **GET** `/api/get-item-codes` - InventorySubCategoryController@getItemCodes (jwt.auth)
- **GET** `/api/inventory/promotions` - InventoryItemController@getInventoryWithPromotion (jwt.auth)
- **GET** `/api/inventory/discounts` - InventoryItemController@getInventoryWithDiscounts (jwt.auth)
- **GET** `/api/inventory-items/price-list` - PriceListController@getItemPriceList (jwt.auth)

## Sales Orders
- **POST** `/api/get-sales-orders` - SalesOrdersController@salesOrders
- **GET** `/api/get-sales-man-orders` - SalesOrdersController@getSalesManOrders
- **GET** `/api/get-order-by-id` - SalesOrdersController@getOrderById
- **POST** `/api/get-sales-by-route` - SalesOrdersController@getOrdersByRoute
- **POST** `/api/get-shop-orders` - SalesOrdersController@getShopOrders
- **POST** `/api/get-sales-order-details` - SalesOrdersController@getSalesOrderDetails
- **POST** `/api/get-sales-order-receipt` - SalesOrdersController@getSalesOrderReceipt
- **POST** `/api/record-sales-orders` - SalesInvoiceController@create (jwt.auth, throttle:sales-orders)

## Sales Operations
- **POST** `/api/getCustomer` - UserController@getCustomer
- **POST** `/api/getPaymentMethod` - SalesController@getPaymentMethod
- **POST** `/api/getCheckOut` - SalesController@getCheckOut
- **POST** `/api/sales_order_checkout` - SalesController@sales_order_checkout
- **POST** `/api/postreturnsales` - SalesController@postreturnsales
- **POST** `/api/postallreturnsales` - SalesController@postallreturnsales
- **POST** `/api/getexpenseslist` - SalesController@getexpenseslist
- **POST** `/api/postexpensesdata` - SalesController@postexpensesdata
- **POST** `/api/getShiftlist` - SalesController@getShiftlist
- **GET** `/api/getUserShiftlist` - SalesController@getUserShiftlist
- **POST** `/api/postOpenShift` - SalesController@postOpenShift
- **POST** `/api/getvehicleslist` - SalesController@getvehicleslist
- **POST** `/api/getmydebtorlist` - SalesController@getMyDebtorList
- **POST** `/api/createImageFromBase64` - SalesController@createImageFromBase64
- **POST** `/api/closeShift` - SalesController@closeShift

## Sales Summaries
- **POST** `/api/getShiftSalesSummary` - SalesController@getShiftSalesSummary
- **POST** `/api/monthlyWiseSalesSummary` - SalesController@MonthlySalesSummary
- **POST** `/api/getExpensesSummary` - SalesController@getExpensesSummary
- **POST** `/api/salesmanTripSummary` - SalesController@salesmanTripSummary
- **POST** `/api/getsalesreportPrint` - SalesController@getsalesreportPrint
- **POST** `/api/getshiftsummaryPrint` - SalesController@getshiftsummaryPrint

## Payments & Debtor Management
- **POST** `/api/postDebtorPayment` - SalesController@postDebtorPayment
- **POST** `/api/postDebtorPayment_new` - SalesController@postDebtorPayment_new
- **POST** `/api/postSplitPayment` - SalesController@postSplitPayment
- **POST** `/api/getdeliverynotelist` - SalesController@getdeliverynotelist
- **POST** `/api/getcashpaymentlist` - SalesController@getcashpaymentlist
- **POST** `/api/postmergecashsalesinmpesa` - SalesController@postmergecashsalesinmpesa
- **POST** `/api/checkminimumprice` - SalesController@checkminimumprice
- **POST** `/api/customer-payments/initiate` - CustomerPaymentController@initiatePayment (jwt.auth)
- **POST** `/api/customer-payments/fetch` - CustomerPaymentController@fetchPayment (jwt.auth)
- **POST** `/api/deliveries/mark-paid` - SalesInvoiceController@markPaid (jwt.auth)

## Issue Reporting
- **GET** `/api/reportReasons` - ReportReasonController@apiGetReasons
- **POST** `/api/reportShop` - ReportShopController@report
- **POST** `/api/reportRoute` - RouteReportController@reportRoute
- **GET** `/api/get-reporting-scenarios` - SalesmanReportedIssueController@getReportingScenarios (jwt.auth)
- **POST** `/api/report-issue` - SalesmanReportedIssueController@reportIssue (jwt.auth)
- **POST** `/api/verify-price-conflict-verification-code` - SalesmanReportedIssueController@verifyPriceConflictCode (jwt.auth)
- **POST** `/api/verify-shop-closed-verification-code` - SalesmanReportedIssueController@verifyShopClosedCode (jwt.auth)
- **POST** `/api/add-salesman-report-reasons` - SalesmanReportingReasonsController@addSalesmanReportReasons
- **POST** `/api/verify-reporting-customer-code` - SalesmanReportingReasonsController@verifyCustomerCode

## Delivery Management
- **GET** `/api/loading-sheet/unreceived-items` - DeliveryScheduleController@getUnreceivedItems (jwt.auth)
- **POST** `/api/loading-sheet/receive-items` - DeliveryScheduleController@receiveItems (jwt.auth)
- **POST** `/api/prompt-delivery-completion` - DeliveryScheduleController@promptDeliveryCompletion (jwt.auth)
- **POST** `/api/verify-delivery-code` - CustomerPaymentController@verifyDeliveryCode (jwt.auth)
- **POST** `/api/resend-delivery-code` - DeliveryScheduleController@resendDeliveryCode (jwt.auth)
- **POST** `/api/complete-delivery` - DeliveryScheduleController@completeDelivery (jwt.auth)
- **GET** `/api/get-delivery-man-deliveries` - SalesOrdersController@getDeliveryManDeliveries

## Wallet & Petty Cash
- **GET** `/api/get-wallet-balance` - MaintainWalletsController@getWalletBalance (jwt.auth)
- **GET** `/api/get-wallet-transactions` - MaintainWalletsController@getWalletTransactions (jwt.auth)
- **GET** `/api/get-user-wallets` - UserPettyCashTransactionController@getUserWallets (jwt.auth)
- **POST** `/api/withdraw-from-wallet` - UserPettyCashTransactionController@withdraw (jwt.auth)
- **GET** `/api/petty-cash-types` - PettyCashRequestTypeController@pettyCashTypes (jwt.auth)
- **POST** `/api/request-petty-cash` - PettyCashRequestController@requestPettyCash (jwt.auth)
- **GET** `/api/user-petty-cash-requests` - PettyCashRequestController@userPettyCashRequests (jwt.auth)
- **POST** `/api/get-salesman-statement` - SalesmanStatementController@generateStatement (jwt.auth)

## Fingerprint & Security
- **POST** `/api/set-user-fingerprints` - UserFingerprintController@store (jwt.auth)
- **GET** `/api/get-user-fingerprints` - UserFingerprintController@getUserFingerPrints (jwt.auth)

## Stock Management
- **GET** `/api/get-mobile-stock-take-items` - StockCountsController@getMobileStockTakeItems (jwt.auth)
- **POST** `/api/record-stock-takes` - StockCountsController@recordMobileStockTakes (jwt.auth)
- **GET** `/api/get-stock-count-variations` - StockCountsController@getEnteredStockTakeItems (jwt.auth)
- **GET** `/api/get-unreceived-bins` - BinLocationController@getUnReceivedBins (jwt.auth)

## Fuel Management
- **GET** `/api/list-fueling-vehicles` - NewFuelEntryController@listVehicles (jwt.auth)
- **GET** `/api/fuel-entries` - NewFuelEntryController@listForApi (jwt.auth)
- **POST** `/api/fuel-entries/add` - NewFuelEntryController@storeFromApi (jwt.auth)
- **GET** `/api/get-fuel-stations` - FuelStationController@getFuelStations (jwt.auth)
- **GET** `/api/get-fueled-vehicles` - NewFuelEntryController@getFueledVehicles (jwt.auth)
- **POST** `/api/fuel-entries/edit` - NewFuelEntryController@updateFromApi (jwt.auth)
- **GET** `/api/get-carton-trucks` - NewFuelEntryController@getManualLpoVehicles (jwt.auth)
- **POST** `/api/request-fuel-lpo` - NewFuelEntryController@generateManualLpo (jwt.auth)

## POS Operations
- **GET** `/api/cash-sale-statistics` - CashSalesController@statistics (jwt.auth)
- **GET** `/api/cash-sale-all` - CashSalesController@index (jwt.auth)
- **POST** `/api/cash-sale` - CashSalesController@store (jwt.auth)
- **POST** `/api/cash-sale-update/{id}` - CashSalesController@update (jwt.auth)
- **POST** `/api/cash-sale-close/{id}` - CashSalesController@close (jwt.auth)
- **GET** `/api/cash-sale-customer-receipt/{id}` - CashSalesController@customerReceipt (jwt.auth)
- **GET** `/api/cash-sale-dispatch-sheet/{id}` - CashSalesController@dispatchSheet (jwt.auth)
- **GET** `/api/cash-sale-dispatch-sheet/display/{id}` - CashSalesController@displayDispatchSheet (jwt.auth)
- **GET** `/api/cash-sale/scanned-receipts` - CashSalesController@getUserScannedCashSales (jwt.auth)
- **GET** `/api/cash-sale-payment-methods` - CashSalesController@getPaymentMethods (jwt.auth)
- **GET** `/api/cash-sale-item-discount` - CashSalesController@calculateInventoryItemDiscount (jwt.auth)
- **GET** `/api/cash-sale-check-payment` - CashSalesController@checkPayment (jwt.auth)
- **POST** `/api/cash-sale-initiate-payment` - CashSalesController@initiatePayment (jwt.auth)
- **GET** `/api/cash-sale-verify-payment` - CashSalesController@verify (jwt.auth)
- **GET** `/api/cash-sale-payment-details` - CashSalesController@getPayDetails (jwt.auth)

## Inventory Management (Mobile)
- **GET** `/api/display-bin/inventory-management/user-items` - MobileInventoryManagementController@getUserInventoryList (jwt.auth)
- **POST** `/api/display-bin/inventory-management/request-split` - MobileInventoryManagementController@requestSplit (jwt.auth)

## Missing Items Reporting
- **POST** `/api/report-missing-items` - ReportedMissingItemsController@reportMissingItems (jwt.auth)
- **GET** `/api/reported-missing-items/listing` - ReportedMissingItemsController@getReportedMissingItems (jwt.auth)

## New Item Reporting
- **POST** `/api/report-new-item` - ReportNewItemController@reportNewItem (jwt.auth)
- **GET** `/api/get-reported-new-items` - ReportNewItemController@getReportedNewItems (jwt.auth)

## Price Conflict Reporting
- **POST** `/api/report-price-conflict` - ReportPriceConflict@reportPriceConflict (jwt.auth)
- **GET** `/api/get-reported-price-conflicts` - ReportPriceConflict@getReportedPriceConflicts (jwt.auth)

## POS Customers
- **GET** `/api/pos-customers` - PosCustomerController@getPosCustomers (jwt.auth)
- **POST** `/api/pos-customers/add` - PosCustomerController@addPosCustomer (jwt.auth)

## Bank Transactions
- **POST** `/api/equity/bank/transactions` - SalesController@bank_transaction
- **POST** `/api/equity/bank/total` - SalesController@getTotalBankEquityTransactions

## Web Routes (Non-API)
- **GET** `/salesman-shifts` - SalesManShiftController@salesmanShift
- **GET** `/salesman-shift/{id}` - SalesManShiftController@salesmanShiftDetails
- **GET** `/salesman-shifts/{id}/delivery-report` - SalesManShiftController@deliveryReport
- **GET** `/salesman-shifts/{id}/delivery-sheet` - SalesManShiftController@deliverySheet
- **GET** `/salesman-shifts/{id}/loading-sheet` - SalesManShiftController@loadingSheet
- **GET** `/salesman-shifts/{id}/reopen-from-back-end` - SalesManShiftController@reopenShiftFromBackend

## Reports
- **POST** `/api/reports/salesman-performance-report` - ReportsController@salesman_performance_report
- **GET** `/sales-and-receivables-reports/salesman_shift_report` - SalesmanShiftReport@index
- **GET** `/reports/salesman-summary` - ReportController@salesmanSummary
- **GET** `/sales-and-receivables-reports/salesman-summary` - SalesAndReceiablesReportsController@salesmanSummary
- **GET** `/sales-and-receivables-reports/get-shift-by-salesman` - SalesAndReceiablesReportsController@getShiftBySalesman

## Total Endpoints: 150+

**Note**: 
- Endpoints with `jwt.auth` middleware require JWT authentication token
- Endpoints without middleware are public
- All endpoints use base URL: `http://your-domain/api/` for API routes
- Web routes use: `http://your-domain/` prefix
