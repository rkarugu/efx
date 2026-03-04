<?php

// Include debug promotion routes
require __DIR__.'/debug_promotion.php';
require __DIR__.'/debug_all_promotions.php';
require __DIR__.'/test_promotion_service.php';
require __DIR__.'/debug_promotion_logic.php';

/*
  |--------------------------------------------------------------------------
  | Web Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register web routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */

use App\Http\Controllers\Admin\VerifyStocksController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\SuggestedOrderController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\ChildVsMotherQoh;
use App\Http\Controllers\Admin\GlTagsController;
use App\Http\Controllers\Admin\CustomerBalancesReportController;
use App\Http\Controllers\Admin\CustomerCentreController;
use App\Http\Controllers\Admin\DeliveryScheduleController;
use App\Http\Controllers\Admin\DetailedTransactionSummaryController;
use App\Http\Controllers\Admin\DetailedTrialBalanceController;
use App\Http\Controllers\Admin\FieldVisitController;
use App\Http\Controllers\Admin\SupplierPortalController;
use App\Http\Controllers\Admin\GeneralExcelFileImportController;
use App\Http\Controllers\Admin\ItemSupplierDemandController;
use App\Http\Controllers\Admin\LoaderController;
use App\Http\Controllers\ReturnDemandController;
use App\Http\Controllers\Admin\BulkSmsController;
use App\Http\Controllers\Admin\FuelLPOController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\UtilityController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ProjectsController;
use App\Http\Controllers\ItemPromotionsController;
use App\Http\Controllers\Admin\WaBankingController;
use App\Http\Controllers\Admin\ItemCentreController;
use App\Http\Controllers\Admin\SubDistributorReport;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\PaymentModeController;
use App\Http\Controllers\Admin\PriceChangeController;
use App\Http\Controllers\Admin\ItemDiscountController;
use App\Http\Controllers\Admin\ManualUploadController;
use App\Http\Controllers\Admin\ReturnReasonController;
use App\Http\Controllers\Admin\RoutePricingController;
use App\Http\Controllers\Admin\StockDebtorsController;
use App\Http\Controllers\Admin\VehicleModelController;
use App\Http\Controllers\Admin\WalletMatrixController;
use App\Http\Controllers\Admin\BranchUtilityController;
use App\Http\Controllers\Admin\DeliverySplitController;
use App\Http\Controllers\Admin\GlobalMethodsController;
use App\Http\Controllers\Admin\LogInActivityController;
use App\Http\Controllers\Admin\NotificationsController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\ChairPettyCashController;
use App\Http\Controllers\Admin\DeliveryReportController;
use App\Http\Controllers\Admin\ItemListReportController;
use App\Http\Controllers\Admin\RecalculateQohController;
use App\Http\Controllers\Admin\RouteAutoBreakController;
use App\Http\Controllers\Shared\RouteCustomerController;
use App\Http\Controllers\Shared\SalesManShiftController;
use App\Http\Controllers\Admin\EndOfDayUtilityController;
use App\Http\Controllers\Admin\MaintainWalletsController;
use App\Http\Controllers\Admin\OverStockReportController;
use App\Http\Controllers\Admin\PaymentProviderController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\VehicleOverviewController;
use App\Http\Controllers\Admin\VehicleSupplierController;
use App\Http\Controllers\ScheduledNotificationController;
use App\Http\Controllers\Admin\ChairSalesReportController;
use App\Http\Controllers\Admin\CompletedReturnsController;
use App\Http\Controllers\Admin\PettyCashRequestController;
use App\Http\Controllers\Admin\PurchasesReportsController;
use App\Http\Controllers\Admin\ReturnToSupplierController;
use App\Http\Controllers\Admin\SupplierOverviewController;
use App\Http\Controllers\Admin\UpdateGLCustomerController;
use App\Http\Controllers\Admin\AssignAccountUserController;
use App\Http\Controllers\Admin\PettyCashApprovalController;
use App\Http\Controllers\Admin\PriceUpdateUploadController;
use App\Http\Controllers\Admin\UpdateBinLocationController;
use App\Http\Controllers\Admin\WeeklySalesReportController;
use App\Http\Controllers\StoreKeeper\ParkingListController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\ResolveSalesmanReportedIssue;
use App\Http\Controllers\Admin\SalesInvoiceReturnController;
use App\Http\Controllers\Admin\BankStatementUploadController;
use App\Http\Controllers\Admin\Finance\DebtorTransController;
use App\Http\Controllers\Admin\GeomappingSchedulesController;
use App\Http\Controllers\Admin\InactiveStockReportController;
use App\Http\Controllers\Admin\SalesAnalysisReportController;
use App\Http\Controllers\Admin\StockPendingEntriesController;
use App\Http\Controllers\Admin\GeneralLedgerReportsController;
use App\Http\Controllers\Admin\InventoryMainReportsController;
use App\Http\Controllers\Admin\SupplierLedgerReportController;
use App\Http\Controllers\Admin\SuspendedTransactionController;
use App\Http\Controllers\Admin\NoSupplierItemsReportController;
use App\Http\Controllers\Admin\RouteDailySalesReportController;
use App\Http\Controllers\Admin\SalesmanReportedIssueController;
use App\Http\Controllers\Admin\AccountPayablesReportsController;
use App\Http\Controllers\Admin\ApprovePriceListChange;
use App\Http\Controllers\Admin\ApprovePriceListChangeController;
use App\Http\Controllers\Admin\DeliveryScheduleReportController;
use App\Http\Controllers\Admin\DownloadStocksController;
use App\Http\Controllers\Admin\DownloadTradeAgreementFiles;
use App\Http\Controllers\Admin\RoutePerformanceReportController;
use App\Http\Controllers\Admin\Reports\CustomerPerformanceReport;
use App\Http\Controllers\Admin\ResolveRequisitionToLpoController;

use App\Http\Controllers\Admin\StockTakeUserAssignmentController;
use App\Http\Controllers\Admin\TillDirectBankingReportController;
use App\Http\Controllers\Admin\InventoryItem\RetireItemController;
use App\Http\Controllers\Admin\SalesmanReportingReasonsController;
use App\Http\Controllers\Admin\StockCountVarianceReportController;
use App\Http\Controllers\Admin\TransactionSummaryReportController;
use App\Http\Controllers\Admin\DuplicateCustomerRequestsController;
use App\Http\Controllers\Admin\Reports\UnassignedInvoiceController;
use App\Http\Controllers\Admin\RouteReturnSummarryReportController;
use App\Http\Controllers\Admin\Finance\WaGLJournalInquiryController;
use App\Http\Controllers\Admin\LpoStatusAndLeadtimeReportController;
use App\Http\Controllers\Admin\OnsiteVsOffsiteShiftReportController;
use App\Http\Controllers\Admin\ProfitAndLossMonthlyReportController;
use App\Http\Controllers\Admin\StockUncompletedProcessingController;
use App\Http\Controllers\Admin\TransactionsWithoutAccountController;
use App\Http\Controllers\Admin\LoadingScheduleVSalesReportController;
use App\Http\Controllers\Admin\Reports\SalesByCenterReportController;
use App\Http\Controllers\Admin\TransactionsWithoutBranchesController;
use App\Http\Controllers\Admin\SalesAndReceivablesDashboardController;
use App\Http\Controllers\Admin\Finance\PaymentReconciliationController;
use App\Http\Controllers\Admin\InventoryUtilityLogsController;
use App\Http\Controllers\Admin\Reports\GroupPerfomanceReportController;
use App\Http\Controllers\Admin\SalesAndReceivablesMainReportsController;
use App\Http\Controllers\Admin\SalesPerSupplierPerRouteReportController;
use App\Http\Controllers\Admin\UploadNewItemsController;
use App\Http\Controllers\Admin\ProcurementSalesmanReportedIssueController;
use App\Http\Controllers\Admin\Reports\GroupRouteItemReportDataController;
use App\Http\Controllers\Admin\UpdateItemPriceController;
use App\Http\Controllers\Admin\VehicleCommandContoller;
use App\Http\Controllers\Admin\StockCountBlockedUsersController;
use App\Http\Controllers\Admin\UpdateItemSellingPriceStandardCostPerBranch;
use App\Http\Controllers\Admin\UpdateStandardCostController;
use App\Http\Controllers\GrnsAgainstInvoicesReportController;
use App\Http\Controllers\DetailedSalesSummaryReportController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\SalesmanOrderController;
use App\Http\Controllers\Admin\SalesmanCustomerController;
use App\Http\Controllers\Admin\TicketCategoryController;
use App\Http\Controllers\Admin\HelpDeskSupportController;
use App\Http\Controllers\Admin\ItemMarginProcessingController;
use App\Http\Controllers\Admin\ItemsWithoutSupplierController;
use App\Http\Controllers\Admin\UndisbursedPettyCashController;
use App\Http\Controllers\Admin\MissingItemssalesReportController;
use App\Http\Controllers\Admin\Utility\UpdateItemStockCodeController;
use App\Http\Controllers\Admin\MobileInventoryManagementController;
use App\Http\Controllers\Admin\RouteSplittingController;
use App\Http\Controllers\Admin\ReportedMissingItemsController;
use App\Http\Controllers\Admin\stockBreakingController;
use App\Http\Controllers\Admin\ReverseSplitsController;
use App\Http\Controllers\Shared\ReportNewItemController;
use App\Http\Controllers\Shared\ReportPriceConflict;
use App\Http\Controllers\Admin\CompetingBrandsController;
use App\Http\Controllers\Admin\GrnUpdateUtilityController;
use App\Http\Controllers\Admin\UpdateStockQohController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Api\RequestNewSkuController;
use App\Http\Controllers\Admin\ApiCallLogController;
use App\Http\Controllers\Admin\CompetingBrandsReportController;
use App\Http\Controllers\Admin\DeadStockReportController;
use App\Http\Controllers\Admin\PriceListCostReportController;
use App\Http\Controllers\Admin\ItemsWithMultipleSuppliersController;
use App\Http\Controllers\Admin\OpeningBalancesStockTakeController;

use App\Http\Controllers\Admin\ItemHasCountController;
use App\Http\Controllers\Admin\EodReportController;
use App\Http\Controllers\Admin\SupplierBillingController;
use App\Http\Controllers\Admin\SupplierImpersonationController;
use App\Http\Controllers\ProcurementDashboardController;
use App\Http\Controllers\Admin\SupplierVehicleTypeController;
use App\Http\Controllers\Api\WalletSupplierDocumentProcessController;
use App\Http\Controllers\Admin\DailySalesAndMovesSummaryController;
use App\Http\Controllers\Admin\CashPaymentController;

include_once('modules/fleet.blade.php');
include_once('modules/logistics.blade.php');
include_once('modules/api.blade.php');
include_once('modules/sales_and_receivables.php');
include_once('modules/general_ledger.php');
include_once('modules/management_dashboard.blade.php');

include_once __DIR__ . "/modules/accounts_payables.php";


Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('/dispatch-screen', function () {
    return view('customer-dispatch-screen');
})->middleware('auth.check');

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['AdminBeforeLoggedIn', 'ip-blocker']], function () {
    Route::get('/login', 'UserController@login')->name('admin.login');
    Route::post('/login', 'UserController@makelogin')->name('admin.make.login');
    //    Route::any('/mpesa/callback', 'UserController@mpesacallback')->name('mpesa.callback');
});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['ip-blocker']], function () {
    Route::get('purchase-orders/pdf/{slug}', 'PurchaseOrderController@exportToPdf')->name('purchase-orders.exportToPdf');
    Route::GET('trade-agreement/download-trade-agreement/{reference}', 'TradeAgreementController@get_document_trade_reference');
    Route::get('grn-download/{slug}', 'CompletedGrnController@download_grn')->name('completed-grn.download_grn');
});

// Public route for mobile app invoice download
Route::get('/api/get-sales-order-receipt', [\App\Http\Controllers\Api\SalesOrdersController::class, 'getSalesOrderReceipt'])->name('api.invoice.download');

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['AdminLoggedIn', 'ip-blocker']], function () {


    Route::get('dashboard', 'PagesController@dashboard')->name('admin.dashboard');
    Route::get('/', 'PagesController@dashboard')->name('admin.dashboard');

    Route::get('/logout', 'UserController@logout')->name('admin.logout');
    Route::get('/my-profile', "UserController@myProfile")->name('admin.profile');
    Route::PATCH('/my-profile/{slug}', "UserController@updateMyProfile")->name('admin.update.profile');

    Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('admin.email_templates.index');
    Route::get('email-templates/{id}/edit', [EmailTemplateController::class, 'edit'])->name('admin.email_templates.edit');
    Route::put('email-templates/{id}', [EmailTemplateController::class, 'update'])->name('admin.email_templates.update');

    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [NotificationsController::class, 'update'])->name('notifications.update');

    Route::group(['middleware' => 'set-session-lifetime'], function () {
        Route::get('/chair/dashboard', [ChairSalesReportController::class, 'chairManDashboard'])->name('admin.chairman-dashboard');
        Route::get('/chair/sales-reports', [ChairSalesReportController::class, 'index'])->name('chair-sales-reports.index');
        Route::get('/chair/monthly-reviews', [ChairSalesReportController::class, 'getMonthlyReviews']);
        Route::get('/chair/total-debtor-balances', [ChairSalesReportController::class, 'getTotalDebtorBalances']);
        Route::get('/chair/monthly-sales', [ChairSalesReportController::class, 'getMonthlySales']);
        Route::get('/chair/monthly-payments', [ChairSalesReportController::class, 'getMonthlyPayments']);
        Route::get('/chair/monthly-tonnage', [ChairSalesReportController::class, 'getMonthlyTonnage']);
        Route::get('/chair/monthly-met-unmet-data', [ChairSalesReportController::class, 'getMonthlyMetUnmetData']);
        Route::get('/chair/categories', [ChairSalesReportController::class, 'getCategories']);
        Route::get('/chair/route-performance', [ChairSalesReportController::class, 'getRoutePerformance']);

        Route::get('/chair/petty-cash-reports', [ChairPettyCashController::class, 'index'])->name('chair-petty-cash-reports.index');
    });

    // Account payables reports start
    Route::get('account-payables-reports', [AccountPayablesReportsController::class, 'index'])->name('account-payables-reports.index');
    // Account payables reports end

    // Account payables reports start
    Route::get('general-ledger-reports', [GeneralLedgerReportsController::class, 'index'])->name('general-ledger-reports.index');
    // Account payables reports end

    // Account payables reports start
    Route::get('inventory-reports-load', [InventoryMainReportsController::class, 'index'])->name('inventory-reports.index');
    // Account payables reports end

    // Account payables reports start
    Route::get('sales-and-receivables-reports-load', [SalesAndReceivablesMainReportsController::class, 'index'])->name('sales-and-receivables-reports.index');
    // Account payables reports end

    Route::get('get-report-details', [PurchasesReportsController::class, 'getReportDetails'])->name('get-report-details');
    
    // Dynamic sidebar menu loading
    Route::get('load-sidebar-menu', 'PagesController@loadSidebarMenu')->name('admin.load-sidebar-menu');
});

Route::group(['namespace' => 'Admin', 'middleware' => ['OtpVerified', 'ip-blocker']], function () {
    Route::get('/otp-verify', 'UserController@user_otp')->name('admin.user_otp');
    Route::get('/otp-resend', 'UserController@user_resend_otp')->name('admin.user_resend_otp');
    Route::post('/otp-verify', 'UserController@user_otp_verify')->name('admin.user_otp');
});

Route::group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['AdminLoggedIn', 'ip-blocker', 'operation-shift-balanced']], function () {

    Route::resource('trade-agreement', 'TradeAgreementController');
    Route::put('trade-agreement/summary-update/{id}', 'TradeAgreementController@summary_update')->name('trade-agreement.summary_update');
    Route::put('trade-agreement/lock/{id}', 'TradeAgreementController@lock_agreement')->name('trade-agreement.lock_agreement');
    Route::GET('trade-agreement/discount-items/{id}/show', 'TradeAgreementController@get_discount')->name('trade-agreement.get_discount');
    Route::GET('trade-agreement/{id}/document', 'TradeAgreementController@get_document')->name('trade-agreement.get_document');
    Route::POST('trade-agreement/store-all-offer-amount/{id}', 'TradeAgreementController@store_all_offer_amount')->name('trade-agreement.store_all_offer_amount');
    Route::POST('trade-agreement/store-offer-amount/{id}', 'TradeAgreementController@store_offer_amount')->name('trade-agreement.store_offer_amount');
    Route::POST('trade-agreement/save-discount/{id}', 'TradeAgreementController@store_discount')->name('trade-agreement.store_discount');
    Route::DELETE('trade-agreement/delete-discount/{id}', 'TradeAgreementController@delete_discount')->name('trade-agreement.delete_discount');
    Route::GET('trade-agreement/download-trade-agreement/{reference}', 'TradeAgreementController@get_document_trade_reference');
    Route::post('trade-agreement/retire/{id}', 'TradeAgreementController@retireItem')->name('trade_agreement_item.retire');
    Route::post('update-trade-agreement-item-cost', 'TradeAgreementController@updateItemCost')->name('update_trade_agreement.item_cost');
    Route::post('download-trade-agreement-items', [DownloadTradeAgreementFiles::class, 'downloadItems'])->name('download_trade_agreement_items');
    Route::post('download-trade-agreements', [DownloadTradeAgreementFiles::class, 'downloadTradeAgreements'])->name('download_trade_agreements');
    Route::GET('trade-agreement/email-subscribers/{id}', 'TradeAgreementController@email_subscribers')->name('trade-agreement.email_subscribers');
    Route::GET('trade-agreement/subscription_charges/{id}', 'TradeAgreementController@subscription_charges')->name('trade-agreement.subscription_charges');
    Route::POST('trade-agreement/subscription_charges/{id}', 'TradeAgreementController@store_subscription_charges');

    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [NotificationsController::class, 'update'])->name('notifications.update');
    Route::get('/api-call-logs', [ApiCallLogController::class, 'index'])->name('api_call_logs.index');

    Route::get('n-refresh-stockmoves', 'NInventoryLocationTransferController@refreshstockmoves')->name('n-transfers.refreshstockmoves');
    Route::post('n-get-manual-entry', 'NInventoryLocationTransferController@getManualItemsList')->name('n-transfers.getManualItemsList');
    Route::get('n-transfers/{transfer_no}/{id}/edit', 'NInventoryLocationTransferController@editPurchaseItem')->name('n-transfers.editPurchaseItem');
    Route::post('n-transfers/{id}/edit', 'NInventoryLocationTransferController@updatePurchaseItem')->name('n-transfers.updatePurchaseItem');
    Route::get('n-transfers/delete-item/{transfer_no}/{item_id}', 'NInventoryLocationTransferController@deletingItemRelation')->name('n-transfers.items.delete');
    Route::get('n-transfers/process-transfer/{transfer_no}', 'NInventoryLocationTransferController@processTransfer')->name('n-transfers.processTransfer');
    Route::get('n-transfers/{transfer_no}/print-to-pdf', 'NInventoryLocationTransferController@printToPdf')->name('n-transfers.printToPdf');
    Route::get('n-transfers/{transfer_no}/printpdf', 'NInventoryLocationTransferController@printPdf')->name('n-transfers.printPdf');

    Route::post('n-transfers/print', 'NInventoryLocationTransferController@print')->name('n-transfers.print');
    Route::post('n-transfers/check-quantity', 'NInventoryLocationTransferController@checkQuantity')->name('n-transfers.checkQuantity');
    Route::get('n-transfers/inventoryItems/getInventryItemDetails', 'NInventoryLocationTransferController@getInventryItemDetails')->name('n-transfers.getInventryItemDetails');
    Route::resource('n-transfers', 'NInventoryLocationTransferController');
    Route::resource('lpo-portal-req-approval', 'LpoPortalReqApprovalController');
    Route::get('n-transfers/receive-transfers/requests', 'NInventoryLocationTransferController@indexReceive')->name('n-transfers.indexReceive');
    Route::get('n-transfers/receive-transfers/processed', 'NInventoryLocationTransferController@indexProcessed')->name('n-transfers.indexProcessed');

    Route::get('n-transfers/receive-transfer/{id}', 'NInventoryLocationTransferController@receiveInterBranchTransfer')->name('n-transfers.receiveInterBranchTransfer');
    Route::get('n-transfers/receive-transfer-view/{id}', 'NInventoryLocationTransferController@receiveInterBranchTransfer2')->name('n-transfers.receiveInterBranchTransferview');
    Route::post('n-transfers/updat-uom/{id}', 'NInventoryLocationTransferController@updateUnitOfMeasure')->name('n-transfers.updateUnitOfMeasure');
    Route::get('n-transfers/receive-transfer-processed/{id}', 'NInventoryLocationTransferController@receiveInterBranchTransferProcessed')->name('n-transfers.receiveInterBranchTransferProcessed');


    Route::get('n-transfers/receive-transfer-process/{transferId}', 'NInventoryLocationTransferController@processReceiveInterBranchTransfer')->name('n-transfers.processReceiveInterBranchTransfer');
    Route::POST('order-delivery-slots/book-lpo-slot', 'LpoPortalSlotsController@book_lpo_slot')->name('order-delivery-slots.book_lpo_slot');
    //all routes for restaurant module
    Route::resource('branches', 'RestaurantController');
    Route::get('delivery-branches', 'LpoPortalSlotsController@delivery_branches')->name('order-delivery-slots.delivery_branches');
    Route::get('order-delivery-slots/show-delivery-booked-slots', 'LpoPortalSlotsController@show_booked_slots')->name('order-delivery-slots.show_booked_slots');
    Route::group(['prefix' => 'delivery-branches/{id}/'], function () {
        Route::resource('order-delivery-slots', 'LpoPortalSlotsController');
    });
    Route::resource('pack-size', 'PackSizeController');
    Route::GET('item-sub-categories/search', 'ItemSubCategoriesController@dropdown_search')->name('item-sub-categories.dropdown_search');
    Route::GET('inventory-category-sub-items/search', 'InventoryCategoryController@search_sub_categories')->name('inventory-categories.search_sub_categories');
    Route::GET('uom/search', 'UnitOfMeasureController@dropdown_search')->name('uom.dropdown_search');
    Route::GET('uom/search_by_item_location', 'UnitOfMeasureController@search_by_item_location')->name('uom.search_by_item_location');
    Route::resource('item-sub-categories', 'ItemSubCategoriesController');
    Route::GET('priority-level/search', 'WaPriorityLevelController@dropdown_search')->name('priority-level.dropdown_search');
    Route::resource('priority-level', 'WaPriorityLevelController');

    //all routes for employee module

    //all routes for restaurant module
    Route::get('sales-commission-bands/deleteRec/{id}', 'SalesCommissionBrandController@destroy')->name('sales-commission-bands.deleteRec');
    Route::resource('sales-commission-bands', 'SalesCommissionBrandController');

    Route::resource('dashboard_report', 'DashboardReportController');

    Route::get('users/get_user_suppliers', 'UserController@get_user_suppliers')->name('admin.users.get_user_suppliers');
    Route::POST('users/assign_user_suppliers', 'UserController@assign_user_suppliers')->name('admin.users.assign_user_suppliers');
    Route::get('users/assign_branches', 'UserController@assign_branches')->name('admin.users.assign_branches');
    Route::POST('users/assign_branches_post', 'UserController@assign_branches_post')->name('admin.users.assign_branches_post');
    Route::post('employees-table-assignment-with-user-section', 'UserController@getFreetablesForAssignmentByUserAndSection')->name('admin.table.assignment.with.user.section');

    Route::post('assign-or-remove-table-from-waiter', 'UserController@assignOrRemoveTableFromWaiter')->name('assign-or-remove-table-from-waiter');

    Route::get('clear-all-tables-from-waiters', 'UserController@clearAllTableFromWaiter')->name('clear.all.tables.from.waiters');


    Route::get('emp/entitlements/index', 'EntitlementsController@index')->name('Entitlements.index');

    Route::get('emp/Leave/Get', 'EntitlementsController@LeaveTypeGet')->name('LeaveType.Get');
    Route::get('emp/Assign/Get', 'AssignLeaveController@AssignLeaveGet')->name('AssignLeaveGet.Get');
    Route::get('emp/Year/Get', 'EntitlementsController@YearCalcution')->name('YearCalcution.Get');
    Route::post('emp/Leave/CreateStore', 'EntitlementsController@CreateStore')->name('Entitlements.CreateStore');
    Route::get('emp/entitlements/delete/{id}', 'EntitlementsController@EntitlementsDelete')->name('Entitlements.Delete');
    Route::get('emp/entitlements/HolidaysDelete/{id}', 'EntitlementsController@HolidaysDelete')->name('Holidays.Delete');
    Route::post('emp/entitlements/updtae/{id}', 'EntitlementsController@EntitlementsUpdate')->name('Entitlements.update');

    Route::post('emp/entitlements/departmentsCreate', 'EntitlementsController@DepartmentsCreate')->name('Entitlements.DepartmentsCreate');

    Route::post('emp/entitlements/departmentsCreate', 'EntitlementsController@DepartmentsCreate')->name('Entitlements.DepartmentsCreate');


    // ||||| Payroll Report Start ||||||

    Route::resource('/payment-modes', PaymentModeController::class);

    // Route::get('/payment-modes', 'PaymentModesController@index')->name('payment-modes.index');
    // Route::get('/payment-modes/create', 'PaymentModesController@create')->name('payment-modes.create');
    // Route::post('/payment-modes/store', 'PaymentModesController@store')->name('payment-modes.store');
    // Route::post('/payment-modes/Datatables', 'PaymentModesController@Datatables')->name('payment-modes.Datatables');
    // Route::get('/payment-modes/edit/{id}', 'PaymentModesController@edit')->name('payment-modes.edit');
    // Route::get('/payment-modes/delete/{id}', 'PaymentModesController@delete')->name('payment-modes.delete');
    // Route::post('/payment-modes/update/{id}', 'PaymentModesController@update')->name('payment-modes.update');


    Route::get('/emp-list', 'EmployeeController@index')->name('employee.index');
    Route::get('/emp-list/create', 'EmployeeController@create')->name('employee.create');
    Route::post('/emp-list/datatables', 'EmployeeController@Datatables')->name('employee.Datatables');
    Route::post('/emp-list/store', 'EmployeeController@store')->name('employee.store');
    Route::get('/emp-list/edit/{id}', 'EmployeeController@edit')->name('employee.edit');
    Route::post('/emp-list/update/{id}', 'EmployeeController@update')->name('employee.update');
    Route::get('/emp-list/delete/{id}', 'EmployeeController@delete')->name('employee.delete');
    Route::get('/employee-manage/{id}', 'EmployeeController@EmployeeManagee')->name('employee.manage');
    Route::post('/employee-manage/Emp-Bio-Data/{id}', 'EmployeeController@BioDataUpdate')->name('Emp-Bio-Data.Update');


    Route::post('/emp-experienceStore', 'EmployeeController@EmpExperienceStore')->name('employee.EmpExperienceStore');
    Route::post('/emp-bankStore', 'EmployeeController@EmpBankStore')->name('employee.EmpBankStore');
    Route::post('/emp-bankStore/update/{id}', 'EmployeeController@EmpBankUpdate')->name('emp.update');
    Route::post('/emp-dependents/store', 'EmployeeController@DependentsStore')->name('Dependents.store');
    Route::get('/emp-dependents/delete/{id}', 'EmployeeController@DependentsDelete')->name('Dependents.Delete');
    Route::get('/emp/contract/{id}', 'EmployeeController@Contract')->name('emp.contract');

    Route::post('/emp/contract/store', 'EmployeeController@ContractStore')->name('contract.store');
    Route::get('/emp/contract/delete/{id}', 'EmployeeController@ContractDelete')->name('contract.delete');
    Route::get('/emp/indisciplineCategory/{EmpID}', 'EmployeeController@IndisciplineCategory')->name('emp.indisciplineCategory');

    Route::post('/emp/NextKinStore', 'EmployeeController@NextKinStore')->name('next-kin.store');
    Route::get('/emp/NextKinDelete/{id}', 'EmployeeController@NextKinDelete')->name('NextKin.Delete');

    Route::post('/emp/IndisciplineCreate', 'EmployeeController@IndisciplineCreate')->name('emp.IndisciplineCreate');
    Route::get('/emp/IndisciplineDelete/{id}', 'EmployeeController@IndisciplineDelete')->name('emp.IndisciplineDelete');

    Route::get('/emp/EmpExperience/{id}', 'EmployeeController@EmpExperience')->name('EmpExperience.Delete');

    Route::post('/emp/BankInformation', 'EmployeeController@BankInformation')->name('employee.BankInformation');
    Route::post('/emp/BankInformationUpdate/{id}', 'EmployeeController@BankInformationUpdate')->name('employee.BankInformationUpdate');
    Route::post('/emp/EductionCreate', 'EmployeeController@EductionCreate')->name('employee.EductionCreate');

    Route::get('/emp/EductionDelete/{id}', 'EmployeeController@EductionDelete')->name('employee.EductionDelete');

    Route::post('/emp/DocumentCreate', 'EmployeeController@EductionDocumentCreate')->name('employee.EductionDocumentCreate');
    Route::get('/emp/DocumentDelete/{id}', 'EmployeeController@DocusDelete')->name('employee.DocusDelete');

    Route::post('/emp/ContactsCreate', 'EmployeeController@ContactsCreate')->name('employee.ContactsCreate');

    Route::post('/emp/EmpRefereesCreate', 'EmployeeController@EmpRefereesCreate')->name('Emp.Referees');
    Route::get('/emp/RefereesDelete/{id}', 'EmployeeController@RefereesDelete')->name('employee.RefereesDelete');

    Route::get('/emp/ContactsDelete/{id}', 'EmployeeController@ContactsDelete')->name('employee.ContactsDelete');


    Route::get('/emp/separation', 'SeparationController@index')->name('separation.index');
    Route::post('/emp/separationDatatables', 'SeparationController@Datatables')->name('separation.Datatables');
    Route::get('/emp/separation-termnation/{id}', 'SeparationController@SeparationTermnation')->name('separation.SeparationTermnation');
    Route::post('/emp/separation-termnation/create', 'SeparationController@Create')->name('separation.create');
    Route::get('/emp/separation-termnation/delete/{id}', 'SeparationController@delete')->name('separation.delete');


    Route::get('/emp/ApproveTermination', 'ApproveTerminationController@index')->name('ApproveTermination.index');
    Route::post('/emp/ApproveTermination', 'ApproveTerminationController@Datatables')->name('ApproveTermination.Datatables');

    Route::get('/emp/ApproveTermination/Create/{id}', 'ApproveTerminationController@ApporveTermnationCreate')->name('ApproveTermination.Create');
    Route::post('/emp/ApproveTermination/store', 'ApproveTerminationController@ApporveTermnationStore')->name('ApproveTermination.store');
    Route::get('/emp/ApproveTermination/{id}', 'ApproveTerminationController@Delete2')->name('ApproveTermination.delete');


    Route::get('/emp/TerminatedStaff', 'TerminatedStaffController@index')->name('TerminatedStaff.index');
    Route::post('/emp/TerminatedStaff', 'TerminatedStaffController@Datatables')->name('TerminatedStaff.Datatables');

    Route::post('/emp/PayrollMasterStore', 'PayrollMasterController@PayrollMasterCreate')->name('PayrollMaster.EmpPaymentStore');
    Route::post('/emp/PayrollMasterUpdate/{id}', 'PayrollMasterController@PayrollMasterUpdate')->name('PayrollMaster.EmpPaymentUpdate');
    Route::post('/emp/AllowancesCreate', 'PayrollMasterController@AllowancesCreate')->name('PayrollMaster.AllowancesCreate');
    Route::post('/emp/LoansCreate', 'PayrollMasterController@LoansCreate')->name('PayrollMaster.LoanCreate');
    Route::post('/emp/CommissionCreate', 'PayrollMasterController@CommissionCreate')->name('PayrollMaster.CommissionCreate');

    Route::post('/emp/PayrollPensionCreate', 'PayrollMasterController@PensionCreate')->name('Pension.create');
    Route::post('/emp/PayrollReliefCreate', 'PayrollMasterController@PayrollReliefCreate')->name('Relief.create');
    Route::post('/emp/PayrollReliefCreate', 'PayrollMasterController@PayrollReliefCreate')->name('Relief.create');

    Route::post('/emp/PayrollSaccoCreate', 'PayrollMasterController@PayrollSaccoCreate')->name('Sacco.create');

    Route::post('/emp/PayrollCustomParametersCreate', 'PayrollMasterController@PayrollCustomParametersCreate')->name('CustomParameters.create');


    Route::post('/emp/Non-Cash-Benfit.store', 'PayrollMasterController@NonCashBenfitStore')->name('Non-Cash-Benfit.store');


    // ||||| Payroll Report End ||||||


    // ???????Parroll Master Startd ????????????
    Route::get('/emp/PayrollMaster', 'PayrollMasterController@index')->name('PayrollMaster.index');
    Route::get('/emp/OvertimeHours', 'OvertimeHoursController@index')->name('OvertimeHours.index');
    Route::get('/emp/OvertimeHours/Manage/{id}', 'OvertimeHoursController@Manage')->name('OvertimeHour.manage');

    Route::post('/emp/OvertimeHours/Datatables', 'OvertimeHoursController@Datatables')->name('OvertimeHours.Datatables');
    Route::post('/emp/PayrollMaster/Datatables', 'PayrollMasterController@Datatables')->name('PayrollMaster.Datatables');
    Route::get('/emp/Payroll/{id}', 'PayrollMasterController@Payroll')->name('payroll.manage');


    Route::get('/emp/PayrollAbsend', 'AbsentController@index')->name('PayrollAbsend.index');
    Route::get('/emp/PayrollAbsendCreate/{id}', 'AbsentController@PayrollAbsend')->name('PayrollAbsend.Create');
    Route::post('/emp/PayrollAbsendEdit/{id}', 'AbsentController@PayrollAbsendEdit')->name('PayrollAbsend.edit');
    Route::get('/emp/PayrollAbsendDelete/{id}', 'AbsentController@PayrollAbsendDelete')->name('PayrollAbsend.delete');
    Route::post('/emp/PayrollDatatables', 'AbsentController@Datatables')->name('PayrollAbsend.Datatables');


    Route::post('/emp/CreateAbsent', 'AbsentController@CreateAbsent')->name('PayrollAbsend.CreateAbsent');

    Route::post('/emp/OvertimeHoursCreate', 'OvertimeHoursController@OvertimeHoursCreate')->name('OvertimeHours.Create');
    Route::get('/emp/OvertimeHoursDelete/{id}', 'OvertimeHoursController@OvertimeHoursDelete')->name('OvertimeHours.delete');


    Route::get('/emp/loan-Entries', 'LoanEntriesController@index')->name('LoanEntries.index');
    Route::post('/emp/loan-Entries/Datatables', 'LoanEntriesController@Datatables')->name('LoanEntries.Datatables');
    Route::get('/emp/loan-Entries/addLoan/{id}', 'LoanEntriesController@AddLoan')->name('LoanEntries.addLoan');
    Route::post('/emp/loan-Entries/create', 'LoanEntriesController@CreateLoan')->name('LoanEntries.create');
    Route::get('/emp/loan-Entries/delete/{id}', 'LoanEntriesController@delete')->name('LoanEntries.Delete');


    Route::get('/emp/salaryReview', 'SalaryReviewController@index')->name('SalaryReview.index');
    Route::post('/emp/salaryReview/Datatables', 'SalaryReviewController@Datatables')->name('SalaryReview.Datatables');
    Route::get('/emp/salaryReview/Create/{id}', 'SalaryReviewController@SalaryReviewCreate')->name('SalaryReview.Create');
    Route::post('/emp/salaryReview/Store', 'SalaryReviewController@SalaryStore')->name('SalaryReview.Store');
    Route::get('/emp/ProcessPayroll/paysliproll/{id}', 'SalaryReviewController@PayrolllPayslip')->name('ProcessPayroll.Payslip');
    Route::get('/emp/ProcessPayroll/index', 'SalaryReviewController@ProcessPayroll')->name('ProcessPayroll.index');
    Route::get('/emp/ProcessPayroll/pdf/{id}', 'SalaryReviewController@ProcessPayrollPdf')->name('ProcessPayroll.pdf');


    Route::get('/emp/staffpayslips', 'StaffPayslipController@index')->name('staffpayslips.index');
    Route::get('/emp/payrollreport', 'StaffPayslipController@PayrollProcessReport')->name('payrollreprt.index');
    Route::get('/emp/payrollreport/pdf', 'StaffPayslipController@PayrollProcessPdf')->name('payrollreprt.Pdf');
    Route::get('/emp/payrollreport/export', 'StaffPayslipController@PayrollProcessExport')->name('payrollreprt.export');


    // Route::get('/emp/leaveConfig','ManageConfigController@index')->name('LeaveConfig.index');
    Route::get('/emp/leaveConfig/manage', 'ManageConfigController@index')->name('LeaveConfig.manage');
    Route::post('/emp/leaveConfig/Store', 'ManageConfigController@Store')->name('LeaveConfig.Store');
    Route::get('/emp/leaveConfig/Delete/{id}', 'ManageConfigController@Delete')->name('Leave.Delete');
    Route::post('/emp/leaveConfig/update/{id}', 'ManageConfigController@Update')->name('LeaveConfig.Update');

    Route::post('/emp/leaveHolidayCreate', 'ManageConfigController@HoliDayCreate')->name('LeaveConfig.HoliDayCreate');
    Route::get('/emp/leaveHolidayDelete/{id}', 'ManageConfigController@HoliDayDelete')->name('HoliDay.Delete');

    Route::get('emp/AssignLeaveIndex', 'AssignLeaveController@index')->name('Assign.index');
    Route::post('emp/AssignLeaveStore', 'AssignLeaveController@CreateStore')->name('Assign.Store');
    Route::get('emp/AssignLeaveDelete/{id}', 'AssignLeaveController@AssignLeaveDelete')->name('AssignLeave.Delete');
    Route::post('emp/AssignLeaveUpdate/{id}', 'AssignLeaveController@AssignLeaveUpdate')->name('AssignLeave.Update');


    Route::get('emp/HrApproval', 'HrApprovalController@index')->name('HrApproval.index');
    Route::post('emp/HrApproval/Datatables', 'HrApprovalController@Datatables')->name('HrApproval.Datatables');
    Route::post('emp/HrApproval/ApprovalHr', 'HrApprovalController@ApprovalHr')
        ->name('ApprovalHr.Leave');

    Route::get('emp/HrApproval/manage/{id}', 'HrApprovalController@ManageIndex')->name('HrApproval.manage');
    Route::get('emp/HrApproval/LeaveLetterPdf', 'HrApprovalController@LeaveLetterPdf')->name('HrApproval.LeaveLetterPdf');
    Route::get('emp/HrApproval/calcuction', 'HrApprovalController@Calcuction')->name('HrApproval.calcuction');


    Route::get('emp/ManagerApproval/index', 'ManagerApprovalController@index')->name('ManagerApproval.index');
    Route::get('emp/ManagerApproval/manage/{id}', 'ManagerApprovalController@Manage')->name('ManagerApproval.manage');
    Route::post('emp/ManagerApproval/Datatables', 'ManagerApprovalController@Datatables')->name('ManagerApproval.Datatables');
    Route::post('emp/HrApproval/ManagerApproval', 'ManagerApprovalController@ManagerApproval')
        ->name('ManagerApproval.Leave');


    Route::get('emp/leave_status/index', 'LeaveStatusController@index')->name('LeaveStatus.index');
    Route::get('emp/leave_status/PdfLetter', 'LeaveStatusController@PdfDownloadEmployeeLeaveon')->name('LeaveStatus.PdfDownloadEmployeeLeaveon');
    Route::get('emp/leave_status/PdfDownloadScheduledLeaves', 'LeaveStatusController@PdfDownloadScheduledLeaves')->name('LeaveStatus.PdfDownloadScheduledLeaves');
    Route::get('emp/leave_status/PdfDownloadCompletedLeaves', 'LeaveStatusController@PdfDownloadCompletedLeaves')->name('LeaveStatus.PdfDownloadCompletedLeaves');
    Route::get('emp/leave_status/PdfDownloadDeclinedLeaves', 'LeaveStatusController@PdfDownloadDeclinedLeaves')->name('LeaveStatus.PdfDownloadDeclinedLeaves');
    Route::get('emp/leave_status/export', 'LeaveStatusController@export')->name('LeaveStatus.export');
    Route::get('emp/LeaveRecalls', 'LeaveRecallsController@Index')->name('LeaveRecalls.Index');
    Route::get('emp/LeaveRecalls/manage/{id}', 'LeaveRecallsController@manage')->name('LeaveRecalls.manage');
    Route::post('emp/LeaveRecalls/Recalls', 'LeaveRecallsController@RecallsLeave')->name('Leave.Recalls');


    Route::get('emp/LeaveReversal', 'LeaveReversalController@LeaveReversal')->name('LeaveReversal.Index');

    Route::get('emp/LeaveReversal/manage/{id}', 'LeaveReversalController@manage')->name('LeaveReversal.manage');

    Route::post('emp/LeaveReversal/create', 'LeaveReversalController@LeaveReversalCreate')->name('Leave.Reversal');


    Route::get('emp/leaveHistory', 'ManageReportController@leaveHistory')->name('leaveHistory.Index');
    Route::get('emp/LeaveRecallsReport', 'ManageReportController@LeaveRecallsReport')->name('emp.LeaveRecallsReport');
    Route::get('emp/ReversalReport', 'ManageReportController@ReversalReport')->name('emp.ReversalReport');
    Route::get('emp/LeaveBalances', 'ManageReportController@LeaveBalances')->name('LeaveBalances.Index');
    Route::get('emp/leaveHistorypdf', 'ManageReportController@leaveHistoryPdf')->name('leaveHistory.Pdf');
    Route::get('emp/ReversalReportPdf', 'ManageReportController@ReversalReportPdf')->name('ReversalReport.Pdf');
    Route::get('emp/RecallReportPdf', 'ManageReportController@RecallReportPdf')->name('RecallReportPdf.Pdf');

    Route::get('emp/LeaveBlancePdf', 'ManageReportController@LeaveBlancePdf')->name('LeaveBlancePdf.Pdf');


    // ???????Parroll Master End ????????????


    //For user Logs
    Route::resource('userlog', 'UserLogController');


    Route::get('employees-table-assignment/{slug}', 'UserController@tableAssignment')->name('admin.table.assignment');

    Route::get('employees-authorization-level-assignment/{slug}', 'UserController@authorizationAssignment')->name('admin.authorization.assignment');
    Route::post('employees-authorization-level-assignment-post', 'UserController@authorizationAssignmentPost')->name('assign-authorization-for-employee');

    Route::get('employees-external-authorization-level-assignment/{slug}', 'UserController@externalauthorizationAssignment')->name('admin.external.authorization.assignment');

    Route::post('employees-external-authorization-level-assignment', 'UserController@externalauthorizationAssignmentPost')->name('admin.external.authorization.assignment.post');


    Route::get('employees-purchase-order-authorization-level-assignment/{slug}', 'UserController@purchaseOrderauthorizationAssignment')->name('admin.purchase.order.authorization.assignment');

    Route::post('employees-purchae-order-authorization-level-assignment', 'UserController@purchaseOrderauthorizationAssignmentPost')->name('admin.purchase.order.authorization.assignment.post');


    Route::post('employees-table-assignment/{slug}', 'UserController@tableAssignmentUpdate')->name('admin.update.table.assignment');


    Route::get('employees/change-status/{slug}/{any}', 'UserController@changeStatus')->name('employees.status');

    Route::get('users', 'UserController@usersIndex')->name('users.index');
    Route::get('users/{slug}', 'UserController@usersShow')->name('users.show');
    Route::get('users/add-amount-to-wallet/{slug}', 'UserController@getAddWalletAmountFrom')->name('users.get.add.amount.to.wallet');

    Route::post('users/add-amount-to-wallet/{slug}', 'UserController@setAmountToWallet')->name('users.post.add.amount.to.wallet');


    Route::post('users/datatables-app-user', 'UserController@datatablesGetUsers')->name('users.show.datatables');

    Route::get('role-permissions/{slug}', 'RoleController@getPermissions')->name('users.permissions.form');


    Route::PATCH('role-permissions/{slug}', 'RoleController@setPermissions')->name('users.permissions.updateform');
    Route::POST('role-permissions', 'RoleController@setPermissionsByUser')->name('users.permissions.invoice_r');

    Route::get('employees-settings/change-password/{slug}', 'UserController@changePassword')->name('employees.change_password');


    Route::PATCH('employees-settings/employee-change-password/{slug}', 'UserController@postchangePassword')->name('employees.store.change_password');

    Route::get('user/change-user-profile-password', 'UserController@userGetChangePassword')->name('users.get.change.profile.password');

    Route::PATCH('user/change-user-profile-password', 'UserController@userPostChangePassword')->name('users.post.change.profile.password');


    Route::POST('user/assign-app-permission', 'UserController@assignUserPermission')->name('users.appAssignUserPermission');


    Route::resource('employees', 'UserController');
    Route::POST('employees/filter', 'UserController@filterUsers')->name('employees.filter');
    Route::resource('roles', 'RoleController');

    //all routes for restro tables manager
    Route::get('table-managers/status/{slug}/{any}', 'TableManagerController@changeStatus')->name('table-managers.status');
    Route::resource('table-managers', 'TableManagerController');

    //all routes for major groups manager
    Route::resource('major-group-managers', 'MajorGroupManagerController');

    //all routes for sub major groups manager
    Route::resource('sub-major-groups', 'SubMajorGroupController');

    //all routes for menu items groups manager
    Route::resource('menu-item-groups', 'MenuItemGroupController');
    Route::resource('offers', 'OfferController');

    //all routes for family groups manager
    Route::resource('family-groups', 'FamilyGroupController');

    //all routes for alcoholic family groups manager
    Route::resource('alcoholic-family-groups', 'FamilyGroupForAnotherLayoutController');

    //all routes for food items or menu items manager
    Route::post('getfamilygroupormenuitemgroup', 'FoodItemController@getfamilygroupormenuitemgroup')->name('admin.get.familygroupormenuitemgroup.data');
    Route::get('menu-items/offer-themes-nights', 'FoodItemController@themeIndex')->name('menu-items.themeindex');

    Route::get('sales-and-receivables-reports/customer-statement', 'SalesAndReceiablesReportsController@customerStatement')->name('sales-and-receivables-reports.customer_statement');

    Route::get('sales-and-receivables-reports/customer-statement2', 'SalesAndReceiablesReportsController@customerStatement2')->name('sales-and-receivables-reports.customer_statement2');


    Route::get('menu-items/add-offer-themes-nights', 'FoodItemController@themeCreate')->name('menu-items.themecreate');

    Route::post('menu-items/add-offer-themes-nights', 'FoodItemController@themeStore')->name('menu-items.themestore');

    Route::get('menu-items/{slug}/edit-offer-themes-nights', 'FoodItemController@themeEdit')->name('menu-items.themeedit');

    Route::patch('menu-items/edit-offer-themes-nights/{slug}', 'FoodItemController@themeUpdate')->name('menu-items.themeupdate');

    Route::get('menu-items/exlce/witoutplu', 'FoodItemController@foodItemNotRelatedtoplu')->name('admin.getmenuitemwithoutplu');
    Route::get('menu-items/exlce/pricelist', 'FoodItemController@priceListExport')->name('admin.priceListExport');

    Route::resource('menu-items', 'FoodItemController');

    Route::post('menu-items/auto-fill-reciept', 'RecipesController@autofillrecieptAmnt')->name('admin.autofillrecieptAmnt');

    Route::get('delivery-items/exlce/witoutplu', 'DeliveryItemController@foodItemNotRelatedtoplu')->name('admin.getdeliveryitemwithoutplu');

    Route::resource('delivery-items', 'DeliveryItemController');


    Route::get('{slug}/take-away-hits-list', 'AwayTakeController@getHitsList')->name('admin.take-away-hits-list');
    Route::resource('take-away', 'AwayTakeController');
    Route::resource('print-classes', 'PrintClassController');
    Route::resource('payment-methods', 'PaymentMethodController');
    Route::resource('ratingtypes', 'RatingTypeController');
    Route::resource('advertisements', 'AdvertisementController');

    Route::get('maintain-customers/debtors-inquiry/{slug}', 'CustomerController@debtorTransDetail')->name('maintain-customers.debtors-inquiry');
    Route::get('maintain-customers/debtors-inquiry-lines/{document_number}', 'CustomerController@debtorTransDetailLines')->name('maintain-customers.debtors-inquiry-lines');
    Route::get('maintain-customers/debtors-inquiry-2/{slug}', 'CustomerController@debtorTransDetail2')->name('maintain-customers.debtors-inquiry-2');
    Route::POST('maintain-customers/debtors-inquiry/{slug}/posted/{document_no}', 'SalesInvoiceController@reverse_receipt')->name('maintain-customers.debtors-inquiry.reverse_receipt');
    Route::get('maintain-customers/enter-customer-payment/{slug}', 'CustomerController@enterCustomerPayment')->name('maintain-customers.enter-customer-payment');
    Route::post('maintain-customers/post-customer-payment/{slug}', 'CustomerController@postCustomerPayment')->name('maintain-customers.post-customer-payment');

    Route::get('maintain-customers/download-crc-receipt/{id}', 'CustomerController@downloadDropReceipt')->name('maintain-customers.download-crc-receipt');

    Route::get('maintain-customers/enter-customer-payment-upload/{slug}', 'CustomerController@enterCustomerPayments')->name('maintain-customers.enter-customer-payment-uploads');
    Route::post('maintain-customers/post-customer-payment-upload/{slug}', 'CustomerController@postCustomerPayments')->name('maintain-customers.post-customer-payment-uploads');
    Route::get('maintain-customers/print-receipts/{slug}', 'CustomerController@printReceipts')->name('maintain-customers.print-receipts');
    Route::get('maintain-customers/allocate-receipts/{slug}', 'CustomerController@allocateReceipts')->name('maintain-customers.allocate-receipts');
    Route::post('maintain-customers/print-receipt-by-id', 'CustomerController@printReceiptByid')->name('maintain-customers.print-receipt-by-id');
    Route::GET('maintain-customers/route-customer/dropdown', 'CustomerController@route_customer_dropdown')->name('maintain-customers.route_customer_dropdown');
    Route::GET('pos/route-customer/dropdown', 'RouteCustomerController@dropdown')->name('pos.route_customer.dropdown');
    Route::GET('pos/route-customer/create', 'RouteCustomerController@create')->name('pos.route_customer.create');
    Route::POST('pos/route-customer/store', 'RouteCustomerController@store')->name('pos.route_customer.store');
    Route::POST('maintain-customers/route-customer/add-from-sales', 'CustomerController@add_route_customer')->name('maintain-customers.add_route_customer');

    Route::GET('get-route-centers-list/{routeId}', 'RouteController@getEditRouteCentersList')->name('route-delivery-centers-list');

    Route::GET('update-route-centers-list/{centerId}', 'RouteController@editRouteDeliveryCenter')->name('update-delivery-center-details');

    Route::GET('delete-route-centers-list/{centerId}', 'RouteController@deleteRouteDeliveryCenter')->name('delete-delivery-center-details');

    Route::GET('route-linked-centers-list/{routeId}', 'RouteController@manageRouteLinkedCentersList')->name('manage-route-linked-centers-list');

    Route::GET('route-linked-centers-list/edit/{routeId}', 'RouteController@manageRouteEditLinkedCenters')->name('edit-route-centres');

    Route::GET('get-center-update-details/{centerId}', 'RouteController@getCenterUpdateDetails')->name('get-center-update-details');

    Route::POST('get-center-update-details', 'DeliveryCenterController@updateRouteCenter')->name('update-get-center-update-details');

    Route::POST('admin-upload-delivery-center', 'DeliveryCenterController@adminuploaddeliverycenter')->name('admin-upload-delivery-center');

    Route::POST('delete-selected-route-center-details', 'DeliveryCenterController@deleteSelectedRouteDetails')->name('delete-selected-route-center-details');

    //    Route::GET('route-linked-centers-list/{routeId}', 'RouteController@manageRouteLinkedCentersList')->name('manage-route-linked-centers-list');
    //    Route::GET('route-customers', 'RouteCustomerReportController@index')->name('route-customers.index');
    //    Route::GET('route-customers/sales', 'RouteCustomerReportController@sales')->name('route-customers.sales');

    Route::get('/reports/items-data-purchase-report', [ReportsController::class, 'items_data_purchases'])->name('reports.items_data_purchase_report');


    Route::resource('maintain-customers', 'CustomerController');

    Route::get('/maintain-customers-recon', [CustomerController::class, 'showDebtorTransReconPage'])->name('maintain-customers.real_recon.index');
    Route::post('/maintain-customers-recon', [CustomerController::class, 'processUpload'])->name('maintain-customers.real_recon.upload');
    Route::post('/maintain-customers-recon/upload', [CustomerController::class, 'confirmUpload'])->name('maintain-customers.real_recon.confirm');
    Route::post('/maintain-customers-recon/download-rejected', [CustomerController::class, 'downloadRejected'])->name('maintain-customers.real_recon.download-rejected');

    Route::GET('maintain-customers/route-customer/{id}', 'CustomerController@route_customer_list')->name('maintain-customers.route_customer_list');
    Route::GET('maintain-customers/route-customers-by-route/{route_id}', 'CustomerController@routeCustomersByRouteId')->name('maintain-customers.route_customer_by_route_id');

    Route::any('maintain-customers/route-customer/date-filter', 'CustomerController@filterData')->name('maintain-customers.route_customer_list.date_time');

    Route::post('exportRouteCustomer/{id}', 'CustomerController@exportroutecustomer')->name('admin.table.exportRouteCustomer');


    Route::post('importexcelforroutecustomer', 'CustomerController@importexcelforroutecustomer')->name('admin.table.importexcelforroutecustomer');


    Route::GET('maintain-customers/route-customer/{id}/add', 'CustomerController@route_customer_add')->name('maintain-customers.route_customer_add');


    Route::GET('maintain-customers/route-customer/{id}/edit', 'CustomerController@route_customer_edit')->name('maintain-customers.route_customer_edit');


    Route::GET('maintain-customers/route-customer/{id}/approve', 'CustomerController@approveRouteCustomer')->name('maintain-customers.route_customer_approve');


    Route::POST('maintain-customers/route-customer/{id}/update', 'CustomerController@route_customer_update')->name('maintain-customers.route_customer_update');

    Route::POST('maintain-customers/route-customer/{id}/store', 'CustomerController@route_customer_store')->name('maintain-customers.route_customer_store');
    Route::POST('maintain-customers/route-customer/{id}/delete', 'CustomerController@route_customer_delete')->name('maintain-customers.route_customer_delete');

    Route::get('customer-centre/{customer}/statement', [CustomerCentreController::class, 'statement'])->name('customer-centre.statement');
    Route::get('customer-centre/{customer}/route-customers', [CustomerCentreController::class, 'routeCustomers'])->name('customer-centre.route-customers');
    Route::get('customer-centre/{customer}', [CustomerCentreController::class, 'show'])->name('customer-centre.show');

    Route::post('proforma-invoice/getCustomer-detail', 'SalesOrderQuotationController@getCustomerDetail')->name('proforma-invoice.get.customer-detail');
    Route::post('proforma-invoice/addMore/{slug}', 'SalesOrderQuotationController@addMore')->name('proforma-invoice.addMore');
    Route::post('proforma-invoice/process/{slug}', 'SalesOrderQuotationController@process')->name('proforma-invoice.process');
    Route::post('proforma-invoice/getItems', 'SalesOrderQuotationController@getItems')->name('proforma-invoice.items');
    Route::post('proforma-invoice/getItems-detail', 'SalesOrderQuotationController@getItemDetail')->name('proforma-invoice.items.detail');
    Route::post('proforma-invoice/print', 'SalesOrderQuotationController@print')->name('proforma-invoice.print');


    Route::get('proforma-invoice/pdf/{slug}', 'SalesOrderQuotationController@exportToPdf')->name('proforma-invoice.exportToPdf');


    Route::get('proforma-invoice/transfer-to-sales-invoice/{slug}', 'SalesOrderQuotationController@transferToSalesInvoice')->name('proforma-invoice.transfer-to-sales-invoice');
    Route::resource('proforma-invoice', 'SalesOrderQuotationController');


    Route::post('sales-invoices/getCustomer-detail', 'SalesInvoiceController@getCustomerDetail')->name('sales-invoices.get.customer-detail');
    Route::post('sales-invoices/addMore/{slug}', 'SalesInvoiceController@addMore')->name('sales-invoices.addMore');

    Route::post('sales-invoices/addMoreManual/{slug}', 'SalesInvoiceController@addMoreManual')->name('sales-invoices.addMoreManual');

    Route::post('sales-invoices/process/{slug}', 'SalesInvoiceController@process')->name('sales-invoices.process');
    Route::post('sales-invoices/getItems', 'SalesInvoiceController@getItems')->name('sales-invoices.items');
    Route::post('sales-invoices/getItemcode', 'SalesInvoiceController@getItemCode')->name('sales-invoices.item-code');
    Route::post('sales-invoices/getItems-detail', 'SalesInvoiceController@getItemDetail')->name('sales-invoices.items.detail');
    Route::post('sales-invoices/print', 'SalesInvoiceController@print')->name('sales-invoices.print');
    Route::get('sales-invoices/pdf/{slug}', 'SalesInvoiceController@exportToPdf')->name('sales-invoices.exportToPdf');


    Route::post('sales-invoices/manualstore', 'SalesInvoiceController@manualstore')->name('sales-invoices.manualstore');

    Route::get('reverse-sales-item/{id}', 'SalesInvoiceController@reverseItem')->name('sales-invoices.reserve-transaction');
    Route::resource('sales-invoices', 'SalesInvoiceController');

    Route::post('cash-sales/getCustomer-detail', 'CashSalesController@getCustomerDetail')->name('cash-sales.get.customer-detail');
    Route::post('cash-sales/addMore/{slug}', 'CashSalesController@addMore')->name('cash-sales.addMore');
    Route::post('cash-sales/process/{slug}', 'CashSalesController@process')->name('cash-sales.process');
    Route::post('cash-sales/getItems', 'CashSalesController@getItems')->name('cash-sales.items');
    Route::post('cash-sales/getItems-detail', 'CashSalesController@getItemDetail')->name('cash-sales.items.detail');
    Route::post('cash-sales/print', 'CashSalesController@print')->name('cash-sales.print');
    Route::get('cash-sales/pdf/{slug}', 'CashSalesController@exportToPdf')->name('cash-sales.exportToPdf');
    Route::get('reverse-item/{id}', 'CashSalesController@reverseItem')->name('cash-sales.reserve-transaction');
    Route::resource('cash-sales', 'CashSalesController');
    Route::get('cash-sales/show/{slug}', 'CashSalesController@show')->name('cash-sales.show');


    Route::get('pos-cash-sales/inventoryItems/getInventryItemDetails', 'PosCashSalesController@getInventryItemDetails')->name('pos-cash-sales.getInventryItemDetails');

    Route::get('pos-cash-sales/dispatch', 'PosCashSalesController@dispatch_pos')->name('pos-cash-sales.dispatch');
    Route::get('pos-cash-sales/dispatch_logs', 'PosCashSalesController@dispatch_log')->name('pos-cash-sales.dispatch-logs');
    Route::get('pos-cash-sales/dispatch_logs/details/{pos_sales_id}/{wa_unit_of_measures_id}', 'PosCashSalesController@dispatch_log_details')->name('pos-cash-sales.dispatch-logs.details');
    Route::get('pos-cash-sales/dispatch-progress', 'PosCashSalesController@customerView')->name('pos-cash-sales.customer-view');
    Route::get('pos-cash-sales/remove-from-screen/{id}', 'PosCashSalesController@removeFromScreen')->name('pos-cash-sales.removeFromScreen');
    Route::post('pos-cash-sales/process_dispatch/{id}', 'PosCashSalesController@process_dispatch')->name('pos-cash-sales.process_dispatch');

    Route::get('pos-cash-sales/items-list', 'PosCashSalesController@get_sales_list')->name('pos-cash-sales.get_sales_list');
    Route::get('pos-cash-sales/items-list-details', 'PosCashSalesController@get_sales_list_details')->name('pos-cash-sales.get_sales_list_details');
    Route::get('pos-cash-sales/items-discounts', 'PosCashSalesController@calculateInventoryItemDiscount')->name('pos-cash-sales.cal_discount');
    Route::POST('pos-cash-sales/post_dispatch', 'PosCashSalesController@post_dispatch')->name('pos-cash-sales.post_dispatch');
    Route::POST('pos-cash-sales/accept-return/{id}', 'PosCashSalesController@acceptReturn')->name('pos-cash-sales.accept_return');

Route::any('/admin/pos/route-customer/store', [CustomerController::class, 'store']);

    Route::get('pos-cash-sales/invoice/print/{id}', 'PosCashSalesController@invoice_print')->name('pos-cash-sales.invoice_print');
    Route::get('pos-cash-sales/download-receipt/{id}', 'PosCashSalesController@downloadReceipt')->name('pos-cash-sales.download_receipt');
    Route::get('pos-cash-sales/invoice/pdf/{id}', 'PosCashSalesController@exportToPdf')->name('pos-cash-sales.exportToPdf');
    Route::get('pos-cash-sales/invoice/return/{id}', 'PosCashSalesController@return_items')->name('pos-cash-sales.return_items');
    Route::POST('pos-cash-sales/invoice/return/{id}', 'PosCashSalesController@return_items_post')->name('pos-cash-sales.return_items_post');
    Route::GET('pos-cash-sales/invoice/return-list', 'PosCashSalesController@returned_cash_sales_list')->name('pos-cash-sales.returned_cash_sales_list');
    Route::GET('pos-cash-sales/invoice/return-list_late', 'PosCashSalesController@returned_cash_sales_list_late')->name('pos-cash-sales.returned_cash_sales_list_late');
    Route::get('pos-cash-sales/invoice/approve_late_return_show/{id}', 'PosCashSalesController@approve_late_return_show')->name('pos-cash-sales.approve_late_return_show');
    Route::POST('pos-cash-sales/invoice/approve_late_return', 'PosCashSalesController@approve_late_return')->name('pos-cash-sales.approve_late_return');
    Route::GET('pos-cash-sales/invoice/return-list_dispatcher', 'PosCashSalesController@returned_cash_sales_list_dispatcher')->name('pos-cash-sales.returned_cash_sales_list_dispatcher');
    Route::GET('pos-cash-sales/invoice/return-list_dispatcher_show/{id}', 'PosCashSalesController@returned_cash_sales_list_dispatcher_show')->name('pos-cash-sales.returned_cash_sales_list_dispatcher_show');
    Route::GET('pos-cash-sales/invoice/return-print/{id}', 'PosCashSalesController@returned_cash_sales_print')->name('pos-cash-sales.returned_cash_sales_print');
    Route::POST('pos-cash-sales/esd_upload', 'PosCashSalesController@esd_upload')->name('pos-cash-sales.esd_upload');
    Route::POST('pos-cash-sales/verify/return/otp', 'PosCashSalesController@verifyOTP')->name('pos-cash-sales.verify-return.otp');

    // ── POS Supermarket Routes (must be before resource route) ─────
    Route::get('pos-cash-sales/supermarket', 'PosCashSalesController@supermarketCreate')->name('pos-cash-sales.supermarket');
    Route::get('pos-cash-sales/supermarket/products', 'PosCashSalesController@getSupermarketProducts')->name('pos-cash-sales.supermarket.products');
    Route::post('pos-cash-sales/supermarket/store', 'PosCashSalesController@storeSupermarketSale')->name('pos-cash-sales.supermarket.store');
    Route::post('pos-cash-sales/supermarket/cash-drop', 'PosCashSalesController@storeCashDrop')->name('pos-cash-sales.supermarket.cash-drop');
    Route::get('pos-cash-sales/supermarket/cashier-info', 'PosCashSalesController@getCashierInfo')->name('pos-cash-sales.supermarket.cashier-info');
    Route::get('pos-cash-sales/supermarket/receipt/{id}', 'PosCashSalesController@printSupermarketReceipt')->name('pos-cash-sales.supermarket.receipt');
    Route::get('pos-cash-sales/supermarket/completed', 'PosCashSalesController@getCompletedSales')->name('pos-cash-sales.supermarket.completed');
    Route::get('pos-cash-sales/supermarket/returns', 'PosCashSalesController@getReturns')->name('pos-cash-sales.supermarket.returns');
    Route::get('pos-cash-sales/supermarket/return/{id}', 'PosCashSalesController@supermarketReturnItems')->name('pos-cash-sales.supermarket.return');
    Route::post('pos-cash-sales/supermarket/return/{id}', 'PosCashSalesController@supermarketReturnItemsPost')->name('pos-cash-sales.supermarket.return.post');
    Route::get('pos-cash-sales/supermarket/return-receipt/{returnGrn}', 'PosCashSalesController@printSupermarketReturnReceipt')->name('pos-cash-sales.supermarket.return.receipt');

    Route::resource('pos-cash-sales', 'PosCashSalesController')->middleware('branch-close');


    // Start
    Route::post('n-internal-requisitions/getItems-detail', 'NInternalRequisitionController@getItemDetail')->name('n-internal-requisitions.items.detail');

    Route::post('n-internal-requisitions/getItems', 'NInternalRequisitionController@getItems')->name('n-internal-requisitions.items');


    Route::get('n-internal-requisitions/{requisition_no_no}/{id}/edit', 'NInternalRequisitionController@editPurchaseItem')->name('n-internal-requisitions.editPurchaseItem');
    Route::post('n-internal-requisitions/{id}/edit', 'NInternalRequisitionController@updatePurchaseItem')->name('n-internal-requisitions.updatePurchaseItem');
    Route::get('n-internal-requisitions/delete-item/{requisition_no_no}/{item_id}', 'NInternalRequisitionController@deletingItemRelation')->name('n-internal-requisitions.items.delete');
    Route::get('n-internal-requisitions/send-request/{requisition_no_no}', 'NInternalRequisitionController@sendRequisitionRequest')->name('n-internal-requisitions.sendRequisitionRequest');
    Route::get('n-internal-requisitions/pdf/{slug}', 'NInternalRequisitionController@exportToPdf')->name('n-internal-requisitions.exportToPdf');
    Route::post('n-internal-requisitions/print', 'NInternalRequisitionController@print')->name('n-internal-requisitions.print');
    Route::post('n-internal-requisitions/get-item-qoh-ajax', 'NInternalRequisitionController@getItemQohAjax')->name('n-internal-requisitions.get-item-qoh-ajax');

    Route::get('purchase-orders/inventoryItems/search-list-ninternalrequistion', 'NInternalRequisitionController@inventoryItems')->name('purchase-orders.inventoryItems-ninternal');


    Route::get('purchase-orders/inventoryItems/getInventryItemDetails/ninternalrequistion', 'NInternalRequisitionController@getInventryItemDetailsnInternal')->name('purchase-orders.getInventryItemDetails-ninternalrequistion');

    Route::resource('n-internal-requisitions', 'NInternalRequisitionController');


    Route::get('n-authorise-requisitions/delete-item/{purchase_no}/{item_id}', 'NApproveInternalRequisitionController@deletingItemRelation')->name('n-authorise-requisitions.items.delete');
    Route::get('n-authorise-requisitions/{purchase_no}/{id}/edit', 'NApproveInternalRequisitionController@editPurchaseItem')->name('n-authorise-requisitions.editPurchaseItem');
    Route::post('n-authorise-requisitions/{id}/edit', 'NApproveInternalRequisitionController@updatePurchaseItem')->name('n-authorise-requisitions.updatePurchaseItem');
    Route::resource('n-authorise-requisitions', 'NApproveInternalRequisitionController');


    Route::get('n-issue-fullfill-requisition/pdf/{slug}', 'NIssueFullfillRequisitionController@exportToPdf')->name('n-issue-fullfill-requisition.exportToPdf');
    Route::post('n-issue-fullfill-requisition/print', 'NIssueFullfillRequisitionController@printPage')->name('n-issue-fullfill-requisition.print');
    Route::resource('n-issue-fullfill-requisition', 'NIssueFullfillRequisitionController');


    // End


    ######## Start - Dispatched Loading Sheets Routes -
    Route::any('pdf/download/{storelocationId}/{shiftId}/{type}', 'DispatchedLoadingSheetsController@downloadpdf')->name('downloadpdf');


    Route::resource('dispatched-loading-sheets', 'DispatchedLoadingSheetsController');

    ######## End - Dispatched Loading Sheets Routes -


    ######## Pos Cash sales Esd Routes -


    Route::get('pos-cash-sales-test/inventoryItems/getInventryItemDetails', 'PosCashSalesTestController@getInventryItemDetails')->name('pos-cash-sales-test.getInventryItemDetails');

    Route::get('pos-cash-sales-test/dispatch', 'PosCashSalesTestController@dispatch_pos')->name('pos-cash-sales-test.dispatch');
    Route::get('pos-cash-sales-test/items-list', 'PosCashSalesTestController@get_sales_list')->name('pos-cash-sales-test.get_sales_list');
    Route::get('pos-cash-sales-test/items-list-details', 'PosCashSalesTestController@get_sales_list_details')->name('pos-cash-sales-test.get_sales_list_details');
    Route::POST('pos-cash-sales-test/post_dispatch', 'PosCashSalesTestController@post_dispatch')->name('pos-cash-sales-test.post_dispatch');


    Route::get('pos-cash-sales-test/invoice/print/{id}', 'PosCashSalesTestController@invoice_print')->name('pos-cash-sales-test.invoice_print');
    Route::get('pos-cash-sales-test/invoice/pdf/{id}', 'PosCashSalesTestController@exportToPdf')->name('pos-cash-sales-test.exportToPdf');
    Route::get('pos-cash-sales-test/invoice/return/{id}', 'PosCashSalesTestController@return_items')->name('pos-cash-sales-test.return_items');
    Route::POST('pos-cash-sales-test/invoice/return/{id}', 'PosCashSalesTestController@return_items_post')->name('pos-cash-sales-test.return_items_post');
    Route::GET('pos-cash-sales-test/invoice/return-list', 'PosCashSalesTestController@returned_cash_sales_list')->name('pos-cash-sales-test.returned_cash_sales_list');
    Route::GET('pos-cash-sales-test/invoice/return-print/{id}', 'PosCashSalesTestController@returned_cash_sales_print')->name('pos-cash-sales-test.returned_cash_sales_print');
    Route::POST('pos-cash-sales-test/esd_upload', 'PosCashSalesTestController@esd_upload')->name('pos-cash-sales-test.esd_upload');

    Route::get('pos-cash-sales-test/resign-esd/{id}', 'PosCashSalesTestController@resign_esd')->name('pos-cash-sales-test.resign_esd');
    Route::PATCH('pos-cash-sales-test/resign-esd-post/{id}', 'PosCashSalesTestController@resign_esd_post')->name('pos-cash-sales-test.resign_esd_post');

    Route::any('pos-cash-sales-test/archive/{id}', 'PosCashSalesTestController@archive')->name('pos-cash-sales-test.archive');
    Route::any('pos-cash-sales/archive/{id}', 'PosCashSalesController@archive')->name('pos-cash-sales.archive');
    Route::resource('pos-cash-sales-test', 'PosCashSalesTestController');


    ########### End Pos Cash sales route


    Route::get('pos-cash-sales-new/inventoryItems/getInventryItemDetails', 'PosCashSales_newController@getInventryItemDetails')->name('pos-cash-sales-new.getInventryItemDetails');

    Route::get('pos-cash-sales-new/dispatch', 'PosCashSales_newController@dispatch_pos')->name('pos-cash-sales-new.dispatch');
    Route::get('pos-cash-sales-new/items-list', 'PosCashSales_newController@get_sales_list')->name('pos-cash-sales-new.get_sales_list');
    Route::get('pos-cash-sales-new/items-list-details', 'PosCashSales_newController@get_sales_list_details')->name('pos-cash-sales-new.get_sales_list_details');
    Route::POST('pos-cash-sales-new/post_dispatch', 'PosCashSales_newController@post_dispatch')->name('pos-cash-sales-new.post_dispatch');


    Route::get('pos-cash-sales-new/invoice/print/{id}', 'PosCashSales_newController@invoice_print')->name('pos-cash-sales-new.invoice_print');
    Route::get('pos-cash-sales-new/invoice/pdf/{id}', 'PosCashSales_newController@exportToPdf')->name('pos-cash-sales-new.exportToPdf');
    Route::get('pos-cash-sales-new/invoice/return/{id}', 'PosCashSales_newController@return_items')->name('pos-cash-sales-new.return_items');
    Route::POST('pos-cash-sales-new/invoice/return/{id}', 'PosCashSales_newController@return_items_post')->name('pos-cash-sales-new.return_items_post');
    Route::GET('pos-cash-sales-new/invoice/return-list', 'PosCashSales_newController@returned_cash_sales_list')->name('pos-cash-sales-new.returned_cash_sales_list');
    Route::GET('pos-cash-sales-new/invoice/return-print/{id}', 'PosCashSales_newController@returned_cash_sales_print')->name('pos-cash-sales-new.returned_cash_sales_print');
    Route::POST('pos-cash-sales-new/esd_upload', 'PosCashSales_newController@esd_upload')->name('pos-cash-sales-new.esd_upload');

    Route::resource('pos-cash-sales-new', 'PosCashSales_newController');

    Route::resource('pos-cash-payments', 'CashPaymentController');
    Route::get('/pos-cash-payments/get-payees/payees', [CashPaymentController::class, 'getUsers'])->name('pos-cash-payments.get-all-users');
    Route::POST('/pos-cash-payments/approve-request', [CashPaymentController::class, 'approve'])->name('pos-cash-payments.confirm-approval');
    Route::POST('/pos-cash-payments/reject-request', [CashPaymentController::class, 'reject'])->name('pos-cash-payments.reject');
    Route::POST('/pos-cash-payments/disburse-payment', [CashPaymentController::class, 'disburse'])->name('pos-cash-payments.disburse');
    Route::get('/pos-cash-payments/print-disbursement/{id}', [CashPaymentController::class, 'downloadDisbursementReceipt'])->name('pos-cash-payments.downloadDisbursementReceipt');
    Route::get('/pos-cash-payments/get-charts-of-accounts/expenses', [CashPaymentController::class, 'getChartsOfAccounts'])->name('pos-cash-payments.get-charts-of-accounts-pcp');



    Route::get('stock-breaking/invoice/print/{id}', 'stockBreakingController@invoice_print')->name('stock-breaking.invoice_print');
    Route::get('stock-breaking/invoice/pdf/{id}', 'stockBreakingController@exportToPdf')->name('stock-breaking.exportToPdf');
    Route::get('stock-breaking/inventoryItems/search-list', 'stockBreakingController@inventoryItems')->name('stock-breaking.inventoryItems');
    Route::get('stock-breaking/inventoryItems/getInventryItemDetails', 'stockBreakingController@getInventryItemDetails')->name('stock-breaking.getInventryItemDetails');
    Route::resource('stock-breaking', 'stockBreakingController');
    Route::resource('reverse-splitting', 'ReverseSplitsController');
    Route::get('/reverse-splitting/approve/{id}', [ReverseSplitsController::class, 'approve']);
    Route::get('/reverse-splitting/reject/{id}', [ReverseSplitsController::class, 'reject']);
    Route::post('stock-breaking/create-dispatch', [stockBreakingController::class, 'createDispatch'])->name('stock-breaking.create-dispatch');



    Route::get('store-c-receive/invoice/print/{id}', 'StoreCReceiveController@invoice_print')->name('store-c-receive.invoice_print');
    Route::get('store-c-receive/inventoryItems/getInventryItemDetails', 'StoreCReceiveController@getInventryItemDetails')->name('store-c-receive.getInventryItemDetails');
    Route::resource('store-c-receive', 'StoreCReceiveController');

    Route::get('store-c-requisitions/inventoryItems/search-list', 'StoreCRequisitionController@inventoryItems')->name('store-c-requisitions.inventoryItems');


    Route::get('store-c-requisitions/inventoryItems/getInventryItemDetails', 'StoreCRequisitionController@getInventryItemDetails')->name('store-c-requisitions.getInventryItemDetails');

    Route::any('store-c-requisitions/inventoryItems/getInventryItemDetails2', 'StoreCRequisitionController@getInventryItemDetails2')->name('store-c-requisitions.getInventryItemDetails2');


    Route::get('store-c-requisitions/print/', 'StoreCRequisitionController@print')->name('store-c-requisitions.print');


    Route::any('store-c-requisitions2/create2/', 'StoreCRequisitionController@create2')->name('store-c-requisitions.create2');

    Route::any('store-c-requisitions2/store2/', 'StoreCRequisitionController@store2')->name('store-c-requisitions.store2');

    Route::resource('store-c-requisitions', 'StoreCRequisitionController');
    Route::get('store-c-issue/completed/', 'StoreCIssueController@completedIndex')->name('store-c-requisitions.completedIndex');
    Route::match(['get', 'post'], 'store-c/inventoryItems/', 'StoreCIssueController@inventoryItems')->name('store-c-issue.inventoryItems');
    Route::match(['get', 'post'], 'store-c/archivedinventoryItems/', 'StoreCIssueController@archivedinventoryItems')->name('store-c-issue.archivedinventoryItems');
    Route::get('store-c/inventoryItems/stock-movements/{stockIdCode}', 'StoreCIssueController@stockMovements')->name('store-c-issue.stock-movements');
    Route::post('store-c/inventoryItems/delete/{id}', 'StoreCIssueController@inventoryItem_delete')->name('store-c-issue.inventory-item-delete');
    Route::post('store-c/archivedinventoryItems/un-archive/{id}', 'StoreCIssueController@archived_inventoryItem_delete')->name('store-c-issue.inventory-item-un-archive');
    Route::post('store-c/inventoryItems/manage-stock/', 'StoreCIssueController@inventoryItem_manage_stock')->name('store-c-issue.inventory-manage-stock');


    Route::get('store-c-stock-takes/getCategories', 'StoreCIssueController@getCategories')->name('admin.store-c-stock-takes.getCategories');
    Route::get('store-c-stock-takes/create-stock-take-sheet', 'StoreCIssueController@stock_take_index')->name('admin.store-c-stock-takes.create-stock-take-sheet');
    Route::get('store-c-stock-takes/freeze-table', 'StoreCIssueController@freezeTable')->name('admin.store-c-stock-takes.freeze-table');
    Route::post('store-c-stock-takes/add-stock-check-file', 'StoreCIssueController@addStockCheckFile')->name('admin.store-c-stock-takes.add-stock-check-file');
    Route::get('store-c-stock-takes/print-to-pdf/{id}', 'StoreCIssueController@printToPdf')->name('admin.store-c-stock-takes.print-to-pdf');
    Route::post('store-c-stock-takes/print', 'StoreCIssueController@printPage')->name('admin.store-c-stock-takes.print');

    Route::resource('store-c-issue', 'StoreCIssueController');


    ## Start Supreme Store Routes  ##


    Route::get('supreme-store-receive/invoice/print/{id}', 'SupremeStoreReceiveController@invoice_print')->name('supreme-store-receive.invoice_print');
    Route::get('supreme-store-receive/inventoryItems/getInventryItemDetails', 'SupremeStoreReceiveController@getInventryItemDetails')->name('supreme-store-receive.getInventryItemDetails');
    Route::resource('supreme-store-receive', 'SupremeStoreReceiveController');

    Route::get('supreme-store-requisitions/inventoryItems/search-list', 'SupremeStoreRequisitionController@inventoryItems')->name('supreme-store-requisitions.inventoryItems');
    Route::get('supreme-store-requisitions/inventoryItems/getInventryItemDetails', 'SupremeStoreRequisitionController@getInventryItemDetails')->name('supreme-store-requisitions.getInventryItemDetails');
    Route::get('supreme-store-requisitions/print/', 'SupremeStoreRequisitionController@print')->name('supreme-store-requisitions.print');

    Route::resource('supreme-store-requisitions', 'SupremeStoreRequisitionController');
    Route::get('supreme-store-issue/completed/', 'SupremeStoreIssueController@completedIndex')->name('supreme-store-requisitions.completedIndex');
    Route::match(['get', 'post'], 'supreme-store/inventoryItems/', 'SupremeStoreIssueController@inventoryItems')->name('supreme-store-issue.inventoryItems');
    Route::match(['get', 'post'], 'supreme-store/archivedinventoryItems/', 'SupremeStoreIssueController@archivedinventoryItems')->name('supreme-store-issue.archivedinventoryItems');
    Route::get('supreme-store/inventoryItems/stock-movements/{stockIdCode}', 'SupremeStoreIssueController@stockMovements')->name('supreme-store-issue.stock-movements');
    Route::post('supreme-store/inventoryItems/delete/{id}', 'SupremeStoreIssueController@inventoryItem_delete')->name('supreme-store-issue.inventory-item-delete');
    Route::post('supreme-store/archivedinventoryItems/un-archive/{id}', 'SupremeStoreIssueController@archived_inventoryItem_delete')->name('supreme-store-issue.inventory-item-un-archive');
    Route::post('supreme-store/inventoryItems/manage-stock/', 'SupremeStoreIssueController@inventoryItem_manage_stock')->name('supreme-store-issue.inventory-manage-stock');


    Route::get('supreme-store-stock-takes/getCategories', 'SupremeStoreIssueController@getCategories')->name('admin.supreme-store-stock-takes.getCategories');
    Route::get('supreme-store-stock-takes/create-stock-take-sheet', 'SupremeStoreIssueController@stock_take_index')->name('admin.supreme-store-stock-takes.create-stock-take-sheet');
    Route::get('supreme-store-stock-takes/freeze-table', 'SupremeStoreIssueController@freezeTable')->name('admin.supreme-store-stock-takes.freeze-table');
    Route::post('supreme-store-stock-takes/add-stock-check-file', 'SupremeStoreIssueController@addStockCheckFile')->name('admin.supreme-store-stock-takes.add-stock-check-file');
    Route::get('supreme-store-stock-takes/print-to-pdf/{id}', 'SupremeStoreIssueController@printToPdf')->name('admin.supreme-store-stock-takes.print-to-pdf');
    Route::post('supreme-store-stock-takes/print', 'SupremeStoreIssueController@printPage')->name('admin.supreme-store-stock-takes.print');

    Route::resource('supreme-store-issue', 'SupremeStoreIssueController');


    /* End Supreme Store Routes  */


    Route::GET('dispatch-report', 'PosCashSalesController@dispatched_items_report')->name('dispatched_items.report');
    Route::GET('inventory-item-list', 'PosCashSalesController@inventory_item_list')->name('inventory_item_list');


    Route::get('petty-cash/category_list', 'PettyCashController@category_list')->name('petty-cash.category_list');
    Route::get('petty-cash/print/{id}', 'PettyCashController@print')->name('petty-cash.print');
    Route::get('petty-cash/pdf/{id}', 'PettyCashController@exportpdf')->name('petty-cash.exportpdf');
    Route::get('petty-cash/bank-accounts', 'PettyCashController@bank_accounts')->name('petty-cash.bank_accounts');
    Route::get('petty-cash/approvals/pending/{id}', 'PettyCashController@pending_approval_show')->name('petty-cash.pending_approval_show');
    Route::PUT('petty-cash/approvals/pending/{id}', 'PettyCashController@pending_approval_update')->name('petty-cash.pending_approval_update');
    Route::get('petty-cash/approvals/pending-list', 'PettyCashController@pending_approvals')->name('petty-cash.pending_approvals');
    Route::get('petty-cash/approvals/completed-list', 'PettyCashController@completed_approvals')->name('petty-cash.completed_approvals');
    Route::resource('petty-cash', 'PettyCashController');
    Route::resource('petty-cash-types', 'PettyCashTypesController');
    Route::resource('equity-bank-deposits', 'EquityBankController');

    Route::get('petty-cash-requests/petty-cash-types', [PettyCashRequestController::class, 'showTypesPage'])->name('petty-cash-request.types');
    Route::get('petty-cash-requests/create', [PettyCashRequestController::class, 'showCreatePage'])->name('petty-cash-request.create');
    Route::get('petty-cash-requests/initial-approval', [PettyCashRequestController::class, 'showInitialApprovalPage'])->name('petty-cash-request.initial-approval');
    Route::get('petty-cash-requests/initial-approval-approve/{id}', [PettyCashRequestController::class, 'showInitialApprovalApprovePage'])->name('petty-cash-request.initial-approval-approve');
    Route::get('petty-cash-requests/final-approval', [PettyCashRequestController::class, 'showFinalApprovalPage'])->name('petty-cash-request.final-approval');
    Route::get('petty-cash-requests/final-approval-approve/{id}', [PettyCashRequestController::class, 'showFinalApprovalApprovePage'])->name('petty-cash-request.final-approval-approve');
    Route::get('petty-cash-requests/processed', [PettyCashRequestController::class, 'showProcessedRequestsPage'])->name('petty-cash-request.processed');
    Route::get('petty-cash-requests/processed-details/{pettyCashNo}', [PettyCashRequestController::class, 'showProcessedRequestDetailsPage'])->name('petty-cash-request.processed-details');
    Route::get('petty-cash-requests/failed', [PettyCashRequestController::class, 'showFailedRequestsPage'])->name('petty-cash-request.failed');
    Route::post('petty-cash-requests/failed-batch-action', [PettyCashRequestController::class, 'failedRequestsBatchAction'])->name('petty-cash-request.failed-batch-action')->middleware('throttle:1,3');
    Route::get('petty-cash-requests/rejected', [PettyCashRequestController::class, 'showRequestRejectedPage'])->name('petty-cash-request.rejected');
    Route::get('petty-cash-requests/rejected-details/{pettyCashNo}', [PettyCashRequestController::class, 'showRejectedRequestDetailsPage'])->name('petty-cash-request.rejected-details');
    Route::get('petty-cash-requests/expunged', [PettyCashRequestController::class, 'showRequestExpungedPage'])->name('petty-cash-request.expunged');
    Route::get('petty-cash-requests/logs', [PettyCashRequestController::class, 'showRequestLogsPage'])->name('petty-cash-request.logs');
    Route::get('petty-cash-requests/logs/{id}/transactions', [PettyCashRequestController::class, 'showRequestLogTransactionsPage'])->name('petty-cash-request.log-transactions');

    Route::get('eod-detailed-report', 'SummaryReportController@index')->name('summary_report.index');
    Route::get('report/inventory-sales-report', 'SummaryReportController@inventory_sales_report')->name('summary_report.inventory_sales_report');
    Route::get('report/{category}/category-items-sales/{date}', 'SummaryReportController@category_items_sales')->name('summary_report.category_items_sales');
    Route::get('eod-detailed-report/report', 'SummaryReportController@report')->name('summary_report.report');
    Route::get('report/detailed-sales-report', 'SummaryReportController@detailed_sales_report')->name('summary_report.detailed_sales_report');
    Route::get('report/sales-by-date-report', 'SummaryReportController@sales_by_date_report')->name('summary_report.sales_by_date_report');
    Route::get('eod-detailed-report/report-r', 'SummaryReportController@report_r')->name('summary_report.report_r');
    Route::get('eod-summary-report', 'SummaryReportController@summaryindex')->name('summary_report.summaryindex');
    Route::get('sales-summary-report', 'SummaryReportController@sales_summary_index')->name('summary_report.sales_summary');
    Route::get('eod-summary-report/report', 'SummaryReportController@summaryreport')->name('summary_report.summaryreport');
    Route::get('sales-vs-stocks-ledger', 'SummaryReportController@salesLedgerVsStocksLedger')->name('summary_report.sales_vs_stocks_ledger');
    Route::get('external-requisition-report', 'ApproveExternalRequisitionController@externalRequisitionReport')->name('externalRequisitionReport');
    Route::get('detailed-sales-summary-report', [DetailedSalesSummaryReportController::class, 'index'])->name('detailed-sales-summary-report');
    Route::get('detailed-sales-summary-report/download', [DetailedSalesSummaryReportController::class, 'excel'])->name('detailed-sales-summary-report.excel-download');
    Route::get('detailed-sales-summary-report/stock-sales', [DetailedSalesSummaryReportController::class, 'stockSalesIndex'])->name('detailed-sales-summary-report.stock-sales');
    Route::get('detailed-stock-sales-summary-report/download', [DetailedSalesSummaryReportController::class, 'stockSalesExcel'])->name('detailed-sales-summary-report.stock-sales-excel-download');

    Route::get('eod-report', 'EodReportController@index')->name('eod_report.index');
    Route::get('eod-report/report', 'EodReportController@report')->name('eod_report.report');




    Route::get('dashboard/salesperson_report', 'PagesController@salesperson_report')->name('dashboard.salesperson_report');
    Route::get('dashboard/selling_report', 'PagesController@selling_report')->name('dashboard.selling_report');


    Route::resource('condiments', 'CondimentController');
    Route::resource('condiment-groups', 'CondimentGroupController');
    Route::resource('tax-manager', 'TaxManagerController');


    Route::resource('alcoholic-sub-family-groups', 'SubFamilyGroupController');

    Route::post('orders/receipt', 'OrderController@receipt')->name('admin.orders.receipt');
    Route::get('orders/myreceipt', 'OrderController@myreceipt')->name('admin.orders.myreceipt');


    Route::get('orders/postpad/edit/{slug}', 'OrderController@editPostpadOrders')->name('admin.edit.postpad.orders');
    Route::patch('orders/postpad/edit/{slug}', 'OrderController@updatePostpadOrders')->name('admin.update.postpad.orders');


    Route::get('orders/postpad', 'OrderController@postpadOrders')->name('admin.postpad.orders');

    Route::post('orders/postpad/delete-with-reason', 'OrderController@postpadOrdersDeleteWithReason')->name('admin.postpad.orders.delete_with_reason');

    Route::get('orders/completed', 'OrderController@completeOrders')->name('admin.completed.orders');

    Route::post('orders/datatables-completed-orders', 'OrderController@datatablesGetCompletedOrders')->name('admin.completed.orders.datatables');


    Route::get('orders/prepaid', 'OrderController@prepaidUnCompletedOrders')->name('admin.prepaidUnCompletedOrders');

    Route::get('orders/closed-orders', 'OrderController@getOrderUnderReceipts')->name('admin.order-receipts');

    Route::post('orders/closed-orders', 'OrderController@multiplereceipt')->name('admin.orders.multiplebillreceipt');

    Route::post('orders/datatables-closed-orders', 'OrderController@datatablesGetClosedOrders')->name('admin.datatables.closed.orders');


    Route::get('orders/closed-orders-payment', 'OrderController@getOrderUnderReceiptsForAll')->name('admin.closed-orders-payment');

    Route::post('orders/datatables-closed-orders-payments', 'OrderController@datatablesForgetClosedOrderPaypanets')->name('admin.datatables.closed.orders.payments.with.datatables');


    Route::get('orders/get-payment-summary/{receipt_id}', 'OrderController@getpaymentsummaryByreceiptId')->name('admin.get.payment.summary');


    Route::get('orders/generate-bills', 'OrderController@getGenerateBills')->name('admin.generate-bills');
    Route::post('orders/generate-bills/{waiter_id}', 'OrderController@postGenerateBills')->name('admin.post.generate-bills');

    Route::get('orders/master-bills', 'OrderController@getMasterBills')->name('admin.master-bills');

    Route::get('orders/master-bill-orders/{orderid}', 'OrderController@getMasterBillsOrders')->name('admin.master-bills-orders');


    Route::get('orders/{slug}/transfer-bill-to-order', 'OrderController@getTransferBillToOrder')->name('admin.get.transfer-bill-to-order');

    Route::get('orders/{slug}/void-items-from-bill', 'OrderController@voidItemsFromBill')->name('admin.get.void-items-from-bill');

    Route::post('orders/{slug}/void-items-from-bill', 'OrderController@postvoidItemsFromBill')->name('admin.post.void-items-from-bill');


    Route::get('orders/{slug}/request-transfer-bill-to-order', 'OrderController@getTransferBillToOrderRequest')->name('admin.request-transfer-bill-to-order');


    Route::post('orders/{slug}/request-transfer-bill-to-order', 'OrderController@postTransferBillToOrderRequest')->name('admin.post-request-transfer-bill-to-order');


    Route::post('orders/print-master-bills', 'OrderController@multiplebillreceipt')->name('admin.orders.printmultiplebillreceipt');


    Route::get('orders/{slug}/cash-receipt', 'OrderController@getMarkBillCashReceipt')->name('admin.get.cash.receipt');
    Route::patch('orders/{slug}/cash-receipt', 'OrderController@postMarkBillCashReceipt')->name('admin.post.cash.receipt');


    Route::get('orders/{slug}/cancle-order', 'OrderController@postCancleOrderRequest')->name('admin.cancle-order');


    Route::get('orders/{slug}/transfer-order', 'OrderController@getTransferOrder')->name('admin.get.transfer-order');

    Route::get('orders/{slug}/request-transfer-order-process', 'OrderController@makeRequestTransferOrder')->name('admin.request.transfer-order');

    Route::post('orders/{slug}/approve-transfer-order-process', 'OrderController@approveTransferOrderRequest')->name('admin.approve.transfer-order');

    Route::get('orders/{bill_id}/delete-bill-by-admin', 'OrderController@cancleBill')->name('admin.delete.bill.request');


    Route::get('orders/{slug}/bill-discount', 'OrderController@getRequestForaddDiscountAtBill')->name('admin.get.bills.discount.request');

    Route::patch('orders/{slug}/bill-discount', 'OrderController@setRequestForaddDiscountAtBill')->name('admin.set.bills.discount.request');


    Route::resource('orders', 'OrderController');


    Route::get('delivery-orders/new-delivery-orders', 'DeliveryOrderController@index')->name('admin.delivery-orders.index');

    Route::get('delivery-orders/{slug}/cancle', 'DeliveryOrderController@cancleOrder')->name('admin.delivery-orders.cancel');

    Route::get('delivery-orders/{slug}/assign', 'DeliveryOrderController@getAssignOrder')->name('admin.delivery-orders.get.assign');

    Route::post('delivery-orders/{slug}/assign', 'DeliveryOrderController@postAssignOrder')->name('admin.delivery-orders.post.assign');

    Route::get('delivery-orders/{slug}/confirm', 'DeliveryOrderController@confirmOrder')->name('admin.delivery-orders.confirm');

    Route::get('delivery-orders/open-delivery-orders', 'DeliveryOrderController@openDeliveryOrders')->name('admin.delivery-orders.open-orders');


    Route::get('delivery-orders/open-delivery-orders/generate-bills', 'DeliveryOrderController@generateBillsDeliveryOrders')->name('admin.delivery-orders.open-orders.generateBills');

    Route::post('delivery-orders/open-delivery-orders/generate-bills', 'DeliveryOrderController@postgenerateBillsDeliveryOrders')->name('admin.delivery-orders.open-orders.generateBills.post');

    Route::get('delivery-orders/open-delivery-orders/master-bills', 'DeliveryOrderController@getmasterBillsDeliveryOrders')->name('admin.delivery-orders.open-orders.masterbills');


    Route::get('delivery-orders/open-delivery-orders/{bill_id}/delete-bill-by-admin', 'DeliveryOrderController@deleteBills')->name('admin.opend-delivery-orders.delete.bill.request');


    Route::get('delivery-orders/open-delivery-orders/{slug}/cash-receipt', 'DeliveryOrderController@getMarkBillCashReceipt')->name('admin.pend-delivery-orders.get.cash.receipt');

    Route::patch('delivery-orders/open-delivery-orders/{slug}/cash-receipt', 'DeliveryOrderController@postMarkBillCashReceipt')->name('admin.pend-delivery-orders.post.cash.receipt');


    Route::get('print-class-users/change-status/{slug}/{any}', 'PrintClassUserController@changeStatus')->name('print-class-users.status');


    Route::get('print-class-users/change-password/{slug}', 'PrintClassUserController@changePassword')->name('print-class-users.change_password');


    Route::PATCH('print-class-users/employee-change-password/{slug}', 'PrintClassUserController@postchangePassword')->name('print-class-users.store.change_password');


    Route::resource('print-class-users', 'PrintClassUserController');
    Route::resource('settings', 'SettingsController');


    Route::get('reports/payment-sales-summary', 'ReportController@paymentSalesSummary')->name('reports.payment-sales-summary');


    Route::get('sales-and-receivables-reports/salesman_shift_report', [\App\Http\Controllers\Admin\Reports\SalesmanShiftReport::class, 'index'])->name('reports.salesman_shift_report');

    Route::get('reports/salesman-summary', 'ReportController@salesmanSummary')->name('reports.salesman-summary');

    Route::get('reports/menu-item-general-sales', 'ReportController@menuItemGeneralSales')->name('reports.menu-item-general-sales');

    Route::get('reports/percentage-profit-report', 'ReportController@percentageProfitReport')->name('reports.percentage-profit-report');

    Route::get('reports/family-group-sales', 'ReportController@familyGroupSales')->name('reports.family-group-sales');

    Route::get('reports/family-group-sales-with-gl', 'ReportController@familyGroupSalesWithGl')->name('reports.family-group-sales-with-gl');

    Route::get('reports/menu-item-group-sales', 'ReportController@menuItemGroupSales')->name('reports.menu-item-group-sales');
    Route::get('reports/major-group-sales', 'ReportController@majorGroupSales')->name('reports.major-group-sales');

    Route::get('reports/waiter-with-family-groups', 'ReportController@waiterWithFamilyGroupSales')->name('reports.waiter-with-family-groups');

    Route::get('reports/menu-item-general-sales-with-plu', 'ReportController@menuItemGeneralSalesWithPlu')->name('reports.menu-item-general-sales-with-plu');

    Route::get('reports/menu-item-general-sales-without-plu', 'ReportController@menuItemGeneralSalesWithoutPlu')->name('reports.menu-item-general-sales-without-plu');

    Route::get('sales-and-receivables-reports/salesman-summary', 'SalesAndReceiablesReportsController@salesmanSummary')->name('sales-and-receivables-reports.salesman-summary');

    Route::get('sales-and-receivables-reports/showroom-sales-item', 'SalesAndReceiablesReportsController@showroomSalesItem')->name('sales-and-receivables-reports.showroom-sales-item');

    Route::get('payment-reconcilliation', 'SalesAndReceiablesReportsController@PaymentReconcilliation')->name('payment-reconcilliation.index');
    Route::match(['get', 'POST'], 'merged-payments', 'SummaryReportController@merge_payment_report')->name('merge_payment_report.index');
    Route::post('merged-payments/reverse_transactions/{id}', 'SummaryReportController@merged_reverse_transactions')->name('merge_payment_report.reverse_transactions');

    Route::post('update-shift-id/{id}', 'SalesAndReceiablesReportsController@updateShiftID')->name('payment-reconcilliation.update-shift-id');
    Route::post('payment-reconcilliation/reverse_transactions/{id}', 'SalesAndReceiablesReportsController@PaymentReconcilliation_reverse_transactions')->name('payment-reconcilliation.reverse_transactions');

    Route::get('sales-and-receivables-reports/customer-invoices', 'SalesAndReceiablesReportsController@index')->name('sales-and-receivables-reports.customer_invoices');

    Route::get('sales-and-receivables-reports/customer-sales-summary', 'SalesAndReceiablesReportsController@customerSalesSummary')->name('sales-and-receivables-reports.customer_sales_summary');

    Route::get('sales-and-receivables-reports/get-shift-by-salesman', 'SalesAndReceiablesReportsController@getShiftBySalesman')->name('sales-and-receivables-reports.getShiftBySalesman');

    Route::get('sales-and-receivables-reports/invoice-balancing-report', 'InvoiceBalancingReportController@index')->name('sales-and-receivables-reports.invoice-balancing-report');

    Route::get('salesman-shifts', [SalesManShiftController::class, 'salesmanShift'])->name('salesman-shifts.index');
    Route::get('salesman-shift/{id}', [SalesManShiftController::class, 'salesmanShiftDetails'])->name('salesman-shift-details');
    Route::get('salesman-shifts/{id}/delivery-report', [SalesManShiftController::class, 'deliveryReport'])->name('salesman-shifts.delivery-report');
    Route::get('salesman-shifts/{id}/delivery-sheet', [SalesManShiftController::class, 'deliverySheet'])->name('salesman-shifts.delivery-sheet');
    Route::get('salesman-shifts/{id}/loading-sheet', [SalesManShiftController::class, 'loadingSheet'])->name('salesman-shifts.loading-sheet');
    Route::get('salesman-shifts/{id}/reopen-from-back-end', [SalesManShiftController::class, 'reopenShiftFromBackend'])->name('salesman-shifts.reopen-from-back-end');
    
    // List all routes to find Thika CBD
    Route::get('list-all-routes', function() {
        $user = Auth::user();
        
        echo "<h3>All Routes in System</h3>";
        echo "<p>Current User: {$user->name} (Restaurant: {$user->restaurant_id})</p>";
        
        // Get all routes
        $allRoutes = \App\Model\Route::with('restaurant')->get();
        
        echo "<h4>All Routes ({$allRoutes->count()}):</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Route Name</th><th>Restaurant ID</th><th>Restaurant Name</th><th>Order Taking Days</th><th>Status</th></tr>";
        
        foreach($allRoutes as $route) {
            $restaurantName = $route->restaurant ? $route->restaurant->name : 'N/A';
            echo "<tr>";
            echo "<td>{$route->id}</td>";
            echo "<td>{$route->route_name}</td>";
            echo "<td>{$route->restaurant_id}</td>";
            echo "<td>{$restaurantName}</td>";
            echo "<td>{$route->order_taking_days}</td>";
            echo "<td>" . ($route->status ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check today's day
        $today = \Carbon\Carbon::now();
        $dayOfWeek = $today->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.
        $dayName = $today->format('l'); // Full day name
        
        echo "<h4>Today's Information:</h4>";
        echo "<p>Today is: {$dayName} (Day {$dayOfWeek})</p>";
        
        // Find routes that should be active today
        echo "<h4>Routes Active Today:</h4>";
        $activeToday = \App\Model\Route::where('order_taking_days', 'LIKE', "%{$dayOfWeek}%")
            ->orWhere('order_taking_days', 'LIKE', "%{$dayName}%")
            ->with('restaurant')
            ->get();
            
        if($activeToday->count() > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Route Name</th><th>Restaurant</th><th>Order Taking Days</th></tr>";
            foreach($activeToday as $route) {
                $restaurantName = $route->restaurant ? $route->restaurant->name : 'N/A';
                echo "<tr>";
                echo "<td>{$route->id}</td>";
                echo "<td>{$route->route_name}</td>";
                echo "<td>{$restaurantName}</td>";
                echo "<td>{$route->order_taking_days}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No routes found for today.</p>";
        }
        
        return '';
    });
    
    // Check Thika CBD schedule
    Route::get('check-thika-schedule', function() {
        $today = \Carbon\Carbon::now()->toDateString();
        $user = Auth::user();
        
        echo "<h3>Thika CBD Schedule Check - Today: $today</h3>";
        echo "<p>Current User: {$user->name} (ID: {$user->id}, Restaurant: {$user->restaurant_id})</p>";
        
        // Check user permissions
        $isAdmin = $user->role_id == 1;
        $branchIds = DB::table('user_branches')->where('user_id', $user->id)->pluck('restaurant_id')->toArray();
        
        echo "<p>Is Admin: " . ($isAdmin ? 'Yes' : 'No') . "</p>";
        echo "<p>Role ID: {$user->role_id}</p>";
        echo "<p>Branch Access: " . implode(', ', $branchIds) . "</p>";
        
        // Test the same logic as the controller
        $hasGlobalAccess = $isAdmin || 
                          $user->role_id == 1 || 
                          strtolower($user->name) == 'demo admin' ||
                          !empty($branchIds);
        echo "<p>Has Global Access: " . ($hasGlobalAccess ? 'YES' : 'NO') . "</p>";
        
        // Find Thika CBD route
        $thikaRoute = \App\Model\Route::where('route_name', 'LIKE', '%thika%')
            ->orWhere('route_name', 'LIKE', '%CBD%')
            ->get();
            
        echo "<h4>Routes matching 'Thika' or 'CBD':</h4>";
        foreach($thikaRoute as $route) {
            echo "<p>Route ID: {$route->id}, Name: {$route->route_name}, Restaurant: {$route->restaurant_id}</p>";
        }
        
        // Check if there are shifts for these routes today
        if($thikaRoute->count() > 0) {
            $routeIds = $thikaRoute->pluck('id')->toArray();
            $todayShifts = \App\SalesmanShift::whereIn('route_id', $routeIds)
                ->whereDate('created_at', $today)
                ->with('relatedRoute', 'salesman')
                ->get();
                
            echo "<h4>Shifts for Thika/CBD routes today:</h4>";
            if($todayShifts->count() > 0) {
                foreach($todayShifts as $shift) {
                    $routeName = $shift->relatedRoute ? $shift->relatedRoute->route_name : 'N/A';
                    $salesmanName = $shift->salesman ? $shift->salesman->name : 'N/A';
                    echo "<p>Shift ID: {$shift->id}, Route: {$routeName}, Salesman: {$salesmanName}, Status: {$shift->status}, Created: {$shift->created_at}</p>";
                }
            } else {
                echo "<p>No shifts found for Thika/CBD routes today.</p>";
            }
        }
        
        // Check all shifts today regardless of route
        $allTodayShifts = \App\SalesmanShift::whereDate('created_at', $today)
            ->with('relatedRoute', 'salesman')
            ->get();
            
        echo "<h4>All shifts today ({$allTodayShifts->count()}):</h4>";
        foreach($allTodayShifts as $shift) {
            $routeName = $shift->relatedRoute ? $shift->relatedRoute->route_name : 'N/A';
            $salesmanName = $shift->salesman ? $shift->salesman->name : 'N/A';
            echo "<p>Shift ID: {$shift->id}, Route: {$routeName}, Salesman: {$salesmanName}, Status: {$shift->status}, Created: {$shift->created_at}</p>";
        }
        
        return '';
    });
    
    // Fix Test Salesman route assignment
    Route::get('fix-test-salesman-route', function() {
        echo "<h3>Fix Test Salesman Route Assignment</h3>";
        
        // Find Test Salesman user
        $user = \App\Model\User::where('name', 'Test Salesman')->first();
        if (!$user) {
            echo "<p>Test Salesman user not found!</p>";
            return '';
        }
        
        echo "<p>Found user: {$user->name} (ID: {$user->id})</p>";
        
        // Find Thika Town CBD route
        $route = \App\Model\Route::where('route_name', 'Thika Town CBD')->first();
        if (!$route) {
            echo "<p>Thika Town CBD route not found!</p>";
            return '';
        }
        
        echo "<p>Found route: {$route->route_name} (ID: {$route->id})</p>";
        
        // Check if already assigned
        $isAssigned = $user->routes()->where('route_id', $route->id)->exists();
        if ($isAssigned) {
            echo "<p>✅ Route is already assigned to user!</p>";
        } else {
            // Assign the route
            $user->routes()->attach($route->id);
            echo "<p>✅ Route has been assigned to Test Salesman!</p>";
        }
        
        // Also update the user's direct route field if it exists
        if (isset($user->route)) {
            $user->route = $route->id;
            $user->save();
            echo "<p>✅ Updated user's direct route field!</p>";
        }
        
        return '';
    });
    
    // Debug order item relationships
    Route::get('debug-order-item/{orderId}', function($orderId) {
        echo "<h3>Debug Order Item Relationships - Order ID: {$orderId}</h3>";
        
        $order = \App\Model\WaInternalRequisition::with([
            'getRelatedItem.getInventoryItemDetail.unitofmeasures'
        ])->find($orderId);
        
        if (!$order) {
            echo "<p>Order not found!</p>";
            return '';
        }
        
        echo "<p>Order: {$order->requisition_no}</p>";
        echo "<p>Items count: {$order->getRelatedItem->count()}</p>";
        
        foreach ($order->getRelatedItem as $index => $item) {
            echo "<h4>Item " . ($index + 1) . ":</h4>";
            echo "<p>Item ID: {$item->id}</p>";
            echo "<p>Inventory Item ID: {$item->wa_inventory_item_id}</p>";
            echo "<p>Quantity: {$item->quantity}</p>";
            echo "<p>Selling Price: {$item->selling_price}</p>";
            echo "<p>Discount: {$item->discount}</p>";
            echo "<p>Total Cost with VAT: {$item->total_cost_with_vat}</p>";
            
            if ($item->getInventoryItemDetail) {
                echo "<p>✅ Inventory Item Found: {$item->getInventoryItemDetail->title}</p>";
                echo "<p>Unit of Measure ID: {$item->getInventoryItemDetail->wa_unit_of_measure_id}</p>";
                
                if ($item->getInventoryItemDetail->unitofmeasures) {
                    echo "<p>✅ Unit of Measure Found: {$item->getInventoryItemDetail->unitofmeasures->title}</p>";
                } else {
                    echo "<p>❌ Unit of Measure NOT FOUND</p>";
                    
                    // Try to find the unit directly
                    $unit = \App\Model\WaUnitOfMeasure::find($item->getInventoryItemDetail->wa_unit_of_measure_id);
                    if ($unit) {
                        echo "<p>🔍 Direct Unit Query Found: {$unit->title}</p>";
                    } else {
                        echo "<p>🔍 Direct Unit Query: NOT FOUND</p>";
                    }
                }
            } else {
                echo "<p>❌ Inventory Item NOT FOUND</p>";
            }
            
            echo "<hr>";
        }
        
        return '';
    });
    
    // Debug routes by branch
    Route::get('debug-routes-by-branch/{branchId}', function($branchId) {
        echo "<h3>Debug Routes for Branch ID: {$branchId}</h3>";
        
        // Get branch info
        $branch = \App\Model\Restaurant::find($branchId);
        if (!$branch) {
            echo "<p>Branch not found!</p>";
            return '';
        }
        
        echo "<p>Branch: {$branch->restaurant_name} (ID: {$branch->id})</p>";
        
        // Get all routes for this branch
        $routes = \App\Model\Route::where('restaurant_id', $branchId)->get();
        
        echo "<h4>All Routes for this Branch ({$routes->count()}):</h4>";
        if ($routes->count() > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Route ID</th><th>Route Name</th><th>Physical Route</th><th>Has Salesman</th><th>Has Route Manager</th><th>Status</th></tr>";
            
            foreach ($routes as $route) {
                echo "<tr>";
                echo "<td>{$route->id}</td>";
                echo "<td>{$route->route_name}</td>";
                echo "<td>" . ($route->is_physical_route ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($route->has_salesman ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($route->has_route_manager ? 'Yes' : 'No') . "</td>";
                echo "<td>" . ($route->status ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Check which routes are available for salesman role (role_id = 4)
            echo "<h4>Routes Available for Salesman Role:</h4>";
            $availableRoutes = $routes->filter(function($route) {
                return $route->is_physical_route && !$route->has_salesman;
            });
            
            if ($availableRoutes->count() > 0) {
                echo "<ul>";
                foreach ($availableRoutes as $route) {
                    echo "<li>{$route->route_name} (ID: {$route->id})</li>";
                }
                echo "</ul>";
            } else {
                echo "<p><strong>No routes available for new salesman assignment.</strong></p>";
                echo "<p>Reasons a route might not be available:</p>";
                echo "<ul>";
                echo "<li>Route already has a salesman assigned (has_salesman = 1)</li>";
                echo "<li>Route is not marked as physical route (is_physical_route = 0)</li>";
                echo "<li>Route is inactive</li>";
                echo "</ul>";
            }
        } else {
            echo "<p>No routes found for this branch.</p>";
        }
        
        return '';
    });
    
    // Debug shift orders
    Route::get('debug-shift-orders/{shiftId}', function($shiftId) {
        echo "<h3>Debug Shift Orders - Shift ID: {$shiftId}</h3>";
        
        // Get shift details
        $shift = \App\SalesmanShift::with(['salesman', 'relatedRoute'])->find($shiftId);
        if (!$shift) {
            echo "<p>Shift not found!</p>";
            return '';
        }
        
        echo "<p>Shift: {$shift->id}, Route: {$shift->relatedRoute->route_name}, Salesman: {$shift->salesman->name}</p>";
        echo "<p>Status: {$shift->status}, Created: {$shift->created_at}</p>";
        
        // Get orders for this shift
        $orders = \App\Model\WaInternalRequisition::with(['getRelatedItem', 'getRouteCustomer'])
            ->where('wa_shift_id', $shiftId)
            ->get();
            
        echo "<h4>Orders for this shift ({$orders->count()}):</h4>";
        if ($orders->count() > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Order ID</th><th>Requisition No</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Created</th></tr>";
            
            foreach ($orders as $order) {
                $itemCount = $order->getRelatedItem->count();
                $total = $order->getOrderTotal();
                $customerName = $order->getRouteCustomer ? $order->getRouteCustomer->name : 'N/A';
                
                echo "<tr>";
                echo "<td>{$order->id}</td>";
                echo "<td>{$order->requisition_no}</td>";
                echo "<td>{$customerName}</td>";
                echo "<td>{$itemCount}</td>";
                echo "<td>" . number_format($total, 2) . "</td>";
                echo "<td>{$order->status}</td>";
                echo "<td>{$order->created_at}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Show order items details
            echo "<h4>Order Items Details:</h4>";
            foreach ($orders as $order) {
                echo "<h5>Order: {$order->requisition_no}</h5>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Item</th><th>Quantity</th><th>Price</th><th>Total</th></tr>";
                
                foreach ($order->getRelatedItem as $item) {
                    $inventoryItem = \App\Model\WaInventoryItem::find($item->wa_inventory_item_id);
                    $itemName = $inventoryItem ? $inventoryItem->title : 'Unknown Item';
                    
                    echo "<tr>";
                    echo "<td>{$itemName}</td>";
                    echo "<td>{$item->quantity}</td>";
                    echo "<td>" . number_format($item->selling_price, 2) . "</td>";
                    echo "<td>" . number_format($item->total_cost_with_vat, 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table><br>";
            }
        } else {
            echo "<p>No orders found for this shift.</p>";
        }
        
        return '';
    });
    
    // Debug route for salesman shifts
    Route::get('debug-salesman-shifts', function() {
        $today = \Carbon\Carbon::now()->toDateString();
        $user = Auth::user();
        
        echo "<h3>Debug Salesman Shifts - Today: $today</h3>";
        echo "<p>Current User: {$user->name} (ID: {$user->id}, Restaurant: {$user->restaurant_id})</p>";
        
        // Check total shifts
        $totalShifts = \App\SalesmanShift::count();
        echo "<p>Total shifts in database: $totalShifts</p>";
        
        // Check shifts today
        $todayShifts = \App\SalesmanShift::whereDate('created_at', $today)->count();
        echo "<p>Shifts created today: $todayShifts</p>";
        
        // Check shifts for user's restaurant
        $restaurantShifts = \App\SalesmanShift::join('routes', 'salesman_shifts.route_id', '=', 'routes.id')
            ->where('routes.restaurant_id', $user->restaurant_id)
            ->whereDate('salesman_shifts.created_at', $today)
            ->count();
        echo "<p>Shifts for user's restaurant today: $restaurantShifts</p>";
        
        // Show recent shifts
        $recentShifts = \App\SalesmanShift::with('relatedRoute')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        echo "<h4>Recent Shifts:</h4>";
        foreach($recentShifts as $shift) {
            $routeName = $shift->relatedRoute ? $shift->relatedRoute->route_name : 'N/A';
            $restaurantId = $shift->relatedRoute ? $shift->relatedRoute->restaurant_id : 'N/A';
            echo "<p>ID: {$shift->id}, Route: {$routeName} (Restaurant: {$restaurantId}), Status: {$shift->status}, Created: {$shift->created_at}</p>";
        }
        
        return '';
    });
    Route::get('salesman-shifts/{id}/delivery-report', [SalesManShiftController::class, 'downloadDeliveryReport'])->name('salesman-shifts.delivery-report');
    Route::get('salesman-shifts/{id}/delivery-sheet', [SalesManShiftController::class, 'downloadDeliverySheet'])->name('salesman-shifts.delivery-sheet');
    Route::get('salesman-shifts/{id}/loading-sheet', [SalesManShiftController::class, 'downloadLoadingSheet'])->name('salesman-shifts.loading-sheet');
    Route::get('salesman-shifts/{id}/debug-balance', [SalesManShiftController::class, 'debugInvoiceBalance'])->name('salesman-shifts.debug-balance');
    Route::post('salesman-shifts/{id}/fix-balance', [SalesManShiftController::class, 'fixInvoiceBalance'])->name('salesman-shifts.fix-balance');
    Route::get('salesman-shifts/{id}/reopen-from-back-end', [SalesManShiftController::class, 'reopenShiftBe'])->name('salesman-shifts.reopen-shift-from-be');


    Route::get('salesman-shift/reported-issues/{issueId}', [SalesManShiftController::class, 'shiftReportedIssues'])->name('salesman-shift.reported-issue');

    Route::get('salesman-shift/reported-issue-details/{shiftId}', [SalesManShiftController::class, 'shiftReportedIssueDetails'])->name('salesman-shift.reported-issue-details');

    // Route::get('salesman-shift/{shiftslug}', [SalesManShiftController::class, 'salesmanShiftDetails'])->name('salesman-shift-details');
    Route::get('salesman-shift/{id}', [SalesManShiftController::class, 'salesmanShiftDetails'])->name('salesman-shift-details');
    Route::get('salesman-shift/order-details/{slug}', [SalesManShiftController::class, 'getShopOrderDetails'])->name('get-shop-order-details');
    Route::get('salesman-shift/download-report/{id}', [SalesManShiftController::class, 'downloadSalesmanShiftDetailsReport'])->name('salesman-shift-details.download');


    Route::get('salesman-shift-pdf/{shiftslug}', [SalesManShiftController::class, 'salesmanShiftDetailsPdf'])->name('salesman-shift-details-pdf');

    Route::get('get-order-item-details/{orderSlug}', [SalesManShiftController::class, 'salesmanShiftOrderItems']);

    Route::get('update-shift-status/{id}/{status}', 'SalesAndReceiablesReportsController@updateShiftStatus')->name('sales-and-receivables-reports.updateShiftStatus');

    Route::get('sales-and-receivables-reports/salesman-detailed-summary', 'SalesAndReceiablesReportsController@salesmanDetailedSummary')->name('sales-and-receivables-reports.salesman-detailed-summary');

    Route::get('sales-and-receivables-reports/customer-detailed-summary', 'SalesAndReceiablesReportsController@customerDetailedSummary')->name('sales-and-receivables-reports.customer-detailed-summary');

    Route::get('sales-and-receivables-reports/salesman-trip-summary', 'SalesAndReceiablesReportsController@salesmanTripSummary')->name('sales-and-receivables-reports.salesman-trip-summary');

    Route::get('sales-and-receivables-reports/showroom-sales-summary', 'SalesAndReceiablesReportsController@showroomSalesSummary')->name('sales-and-receivables-reports.showroom-sales-summary');

    Route::get('sales-and-receivables-reports/daily-gp-report', 'SalesAndReceiablesReportsController@dailygpreport')->name('sales-and-receivables-reports.daily-gp-report');

    Route::get('sales-and-receivables-reports/monthly-gp-report', 'SalesAndReceiablesReportsController@monthlygpreport')->name('sales-and-receivables-reports.monthly-gp-report');

    Route::get('sales-and-receivables-reports/sales-commission-report', 'SalesAndReceiablesReportsController@salescommissionreport')->name('sales-and-receivables-reports.sales-commission-report');

    Route::match(['get', 'post'], 'sales-and-receivables-reports/shift-summary', 'SalesAndReceiablesReportsController@shiftSummary')->name('sales-and-receivables-reports.shift-summary');
    Route::post('sales-and-receivables-reports/shift-summary-returns-reverse', 'SalesAndReceiablesReportsController@shiftSummary_returns_reverse')->name('sales-and-receivables-reports.shiftSummary_returns_reverse');
    Route::get('sales-and-receivables-reports/print_returns_shift', 'SalesAndReceiablesReportsController@print_returns_shift')->name('sales-and-receivables-reports.print_returns_shift');

    Route::post('sales-and-receivables-reports/depoite-list-process', 'SalesAndReceiablesReportsController@depoitelistProcess')->name('sales-and-receivables-reports.depoitelistProcess');

    Route::get('sales-and-receivables-reports/showroom-shift-summary', 'SalesAndReceiablesReportsController@showroomShiftSummary')->name('sales-and-receivables-reports.showroom-shift-summary');

    Route::get('sales-and-receivables-reports/daily-cash-receipt-summary', 'SalesAndReceiablesReportsController@dailyCashReceiptSummary')->name('sales-and-receivables-reports.daily-cash-receipt-summary');
    Route::get('sales-and-receivables-reports/unbalanced-invoices-report', [\App\Http\Controllers\Admin\Reports\UnbalancedReportController::class, 'index'])->name('sales-and-receivables-reports.unbalanced-invoices-report');
    Route::get('sales-and-receivables-reports/unbalanced-invoices/process/{id}', [\App\Http\Controllers\Admin\Reports\UnbalancedReportController::class, 'processInvoice'])->name('sales-and-receivables-reports.unbalanced-invoices-process');


    Route::get('reports/condiment-sales-report-with-plu', 'ReportController@condimentSalesReportWithPlu')->name('reports.condiment-sales-report-with-plu');

    Route::get('reports/get-discounts-reports', 'ReportController@getdiscountsReports')->name('reports.get-discounts-reports');

    Route::get('reports/cashier-reports', 'ReportController@cashierReport')->name('reports.get-cashier-reports');

    Route::get('reports/discount-reports-with-orders', 'ReportController@getdiscountsReportsWithOrders')->name('reports.get-discount-reports-with-orders');

    Route::get('reports/complementary-reports-with-orders', 'ReportController@getcomplementaryReportsWithOrders')->name('reports.get-complementary-reports-with-orders');

    Route::get('reports/get-void-orders-reports', 'ReportController@getCancelledOrdersReports')->name('reports.get-void-orders-reports');

    Route::get('reports/wallet-ledger-entries', 'ReportController@userWalletSummary')->name('reports.wallet-ledger-entries');

    Route::get('reports/payment-sales-summary-data', 'ReportController@paymentSalesSummaryData')->name('reports.payment-sales-summary-data');

    Route::get('reports/get-cashier-detailed-reports', 'ReportController@cashierDetailedReport')->name('reports.get-cashier-detailed-reports');

    Route::get('reports/waiter-summary-reports', 'ReportController@waiterSummaryReports')->name('reports.waiter-summary-reports');


    Route::get('reports/detailed-payment-methods-reports', 'ReportController@detailedPaymentMethodReports')->name('reports.detailed-payment-methods-reports');


    Route::get('feedback/orders', 'FeedbackController@order')->name('feedback.order');
    Route::get('feedback/restaurant', 'FeedbackController@restro')->name('feedback.restro');


    Route::resource('app-custom-pages', 'AppCustomPageController');

    Route::post('reservations/datatables-reservation', 'ReservationController@datatablesReservation')->name('admin.reservations.datatables');

    Route::get('reservations/delete/{id}', 'ReservationController@deletereservation')->name('admin.delete.reservation.request');
    Route::get('reservations/update/{id}', 'ReservationController@updatereservation')->name('admin.edit.reservation.request');

    Route::get('maintain-items/purchaseData/bulk_purchase_data', 'InventoryItemController@bulk_purchase_data')->name('maintain-items.bulk_purchase_data');
    Route::POST('maintain-items/purchaseData/bulk-import-export-inventory', 'InventoryItemController@bulk_inventory_import_export')->name('maintain-items.bulk_inventory_import_export');
    Route::get('maintain-items/purchaseData/{stockid}', 'InventoryItemController@purchaseData')->name('maintain-items.purchaseData');
    Route::POST('maintain-items/purchaseData/{stockid}', 'InventoryItemController@purchaseData')->name('maintain-items.purchaseDataFetch');
    Route::POST('maintain-items/purchaseData/{stockid}/store', 'InventoryItemController@purchaseDataStore')->name('maintain-items.purchaseDataStore');
    Route::GET('maintain-items/purchaseData/{stockid}/add', 'InventoryItemController@purchaseDataAdd')->name('maintain-items.purchaseDataAdd');
    Route::GET('maintain-items/purchaseData/{stockid}/edit/{itemid}', 'InventoryItemController@purchaseDataEdit')->name('maintain-items.purchaseDataEdit');
    Route::POST('maintain-items/purchaseData/{stockid}/update/{itemid}', 'InventoryItemController@purchaseDataUpdate')->name('maintain-items.purchaseDataUpdate');
    Route::DELETE('maintain-items/purchaseData/{stockid}/delete/{itemid}', 'InventoryItemController@purchaseDataDelete')->name('maintain-items.purchaseDataDelete');

    Route::post('maintain-items/update-stock-status', 'InventoryItemController@updateStockStatus')->name('maintain-items.update-stock-status');
    Route::post('maintain-items/update-bin-location', 'InventoryItemController@updateBinLocation')->name('maintain-items.update-bin-location');
    Route::any('maintain-standard-cost', 'InventoryItemController@standardCost')->name('maintain-items.standard.cost');
    Route::GET('maintain-items/assign-inventory-items/{id}', 'InventoryItemController@assignInventoryItems')->name('maintain-items.assignInventoryItems');
    Route::POST('maintain-items/assign-inventory-items/{id}', 'InventoryItemController@postassignInventoryItems')->name('maintain-items.postassignInventoryItems');

    Route::get('maintain-items/item-dropdown', 'InventoryItemController@inventoryDropdown')->name('maintain-items.inventoryDropdown');
    Route::get('edit-maintain-standard-cost/{slug}', 'InventoryItemController@editStandardCost')->name('maintain-items.edit.standard.cost');
    Route::any('update-maintain-standard-cost/{slug}', 'InventoryItemController@updateStandardCost')->name('maintain-items.update.standard.cost');
    // Route::any('process-batch-price-change', 'PriceChangeController@updateStandardCost')->name('maintain-items.process.batch.price-change');

    //manual cost change
    Route::get('manual-price-change', 'ManualPriceChangeController@standardCost')->name('maintain-items.manual-cost-change');
    Route::get('manual-price-change/{slug}', 'ManualPriceChangeController@editStandardCost')->name('maintain-items.manual-cost-change.editStandardCost');
    Route::any('manual-price-change/update/{slug}', 'ManualPriceChangeController@updateStandardCost')->name('maintain-items.manual-cost-change.updateStandardCost');

    Route::get('approve-price-list-change', [ApprovePriceListChangeController::class, 'index'])->name('maintain-items.approve-price-list-change');
    Route::post('approve-price-list-change-confirm', [ApprovePriceListChangeController::class, 'confirmPriceChange'])->name('maintain-items.approve-price-list-change-confirm');

    Route::any('maintain-raw-material-standard-cost', 'MaintainRawMaterialController@standardCost')->name('maintain-raw-material-items.standard.cost');
    Route::GET('maintain-raw-material-items/assign-inventory-items/{id}', 'MaintainRawMaterialController@assignInventoryItems')->name('maintain-raw-material-items.assignInventoryItems');
    Route::POST('maintain-raw-material-items/assign-inventory-items/{id}', 'MaintainRawMaterialController@postassignInventoryItems')->name('maintain-raw-material-items.postassignInventoryItems');

    Route::get('maintain-raw-material-items/item-dropdown', 'MaintainRawMaterialController@inventoryDropdown')->name('maintain-raw-material-items.inventoryDropdown');
    Route::get('edit-maintain-raw-material-standard-cost/{slug}', 'MaintainRawMaterialController@editStandardCost')->name('maintain-raw-material-items.edit.standard.cost');
    Route::any('update-maintain-raw-material-standard-cost/{slug}', 'MaintainRawMaterialController@updateStandardCost')->name('maintain-raw-material-items.update.standard.cost');

    Route::resource('reservations', 'ReservationController');

    Route::resource('social-links', 'SocialLinkController');

    Route::resource('reservation-emails', 'ReservationEmailController');


    //all routes for Beer and Keg manager
    Route::resource('beer-and-keg-sub-major-group', 'BeerAndKegSubMajorGroupController');
    Route::resource('delivery-family-groups', 'DeliveryFamilyGroupController');
    Route::resource('delivery-sub-family-groups', 'DeliverySubFamilyGroupController');


    //all routes for webaccounting
    Route::resource('account-sections', 'AccountSectionController');
    Route::resource('account-groups', 'AccountGroupController');

    Route::resource('sub-account-sections', 'SubAccountSectionController');
    Route::get('sub-account-sections-details/account-sections', 'SubAccountSectionController@account_section_search')->name('sub-account-sections.account-sections');
    Route::get('sub-account-sections-details/account-detail', 'SubAccountSectionController@get_account_detail')->name('sub-account-sections.account-detail');

    Route::get('chart-of-accounts-new', 'ChartsOfAccountController@newindex')->name('chart-of-accounts.newindex');
    Route::resource('chart-of-accounts', 'ChartsOfAccountController');
    Route::get('chart-of-accounts/gl-trans/{account_code}', 'ChartsOfAccountController@glEntriesByAccountcode')->name('chart-of-accounts.gl-trans');

    Route::get('trading-profit-and-loss', 'TradingProfitAndLossController@index')->name('trading-profit-and-loss.index');
    Route::get('trading-profit-and-loss/download', 'TradingProfitAndLossController@download')->name('trading-profit-and-loss.download');
    Route::resource('profit-and-loss', 'ProfitAndLossController');
    Route::get('profit-and-loss/gl-entries/{account_code}', 'ProfitAndLossController@glEntriesByAccountcode')->name('profit-and-loss.gl-entries');

    Route::resource('balance-sheet', 'BalanceSheetController');
    Route::get('balance-sheet/gl-entries/{account_code}', 'BalanceSheetController@glEntriesByAccountcode')->name('balance-sheet.gl-entries');

    Route::resource('departments', 'DepartmentController');
    Route::resource('company-preferences', 'CompanyPreferenceController');
    Route::get('bank-accounts/assignUsers/{id}', 'BankAccountController@assignUsers')->name('bank-accounts.assignUsers');
    Route::POST('bank-accounts/assignUsers/{id}', 'BankAccountController@postAssignUsers')->name('bank-accounts.assignUsers');
    Route::get('bank-accounts/account/inquiry', 'BankAccountController@accountInquiry')->name('bank-accounts.account-inquiry');

    // Route::get('bank-accounts/account-inquiry/{slug}', 'BankAccountController@accountInquiry')->name('bank-accounts.account-inquiry');
    Route::resource('bank-accounts', 'BankAccountController');
    Route::resource('currency-managers', 'CurrencyManagerController');

    Route::resource('accounting-periods', 'AccountingPeriodController');


    Route::resource('end-of-the-day-routine', 'EndOfTheDayRoutineController');

    Route::resource('stock-type-categories', 'StockTypeCategoryController');
    Route::resource('stock-family-groups', 'StockFamilyGroupController');
    Route::resource('inventory-categories', 'InventoryCategoryController');

    Route::resource('location-and-stores', 'LocationAndStoreController');

    Route::resource('unit-of-measures', 'UnitOfMeasureController');

    Route::resource('category', 'CategoryController');


    Route::resource('payment-terms', 'PaymentTermController');


    /** Vechile Routes Start */


    // Route::resource('vehicle', 'VehicleController');
    Route::resource('vehicle', 'VehicleController');
    Route::resource('profit-ability-summary', 'ProfitAbilitySummaryController');
    Route::GET('vehicle/show/overview/{id}', 'VehicleController@overview')->name('vehicle.show.overview');
    Route::GET('vehicle/show/location/{id}', 'VehicleController@show')->name('vehicle.show.location');
    Route::get('vehicle/tyres/pdf/{id}', 'VehicleController@exportToPdf')->name('vehicle.exportToPdf');
    Route::GET('vehicle/show/fuelentries/{id}', 'VehicleController@fuelentries')->name('vehicle.show.fuelentries');
    Route::GET('vehicle/show/expensehistory/{id}', 'VehicleController@expensehistory')->name('vehicle.show.expensehistory');
    Route::GET('vehicle/show/servicehistory/{id}', 'VehicleController@servicehistory')->name('vehicle.show.servicehistory');
    Route::GET('vehicle/show/issues/{id}', 'VehicleController@issues')->name('vehicle.show.issues');
    Route::GET('vehicle/show/inspection_history/{id}', 'VehicleController@inspection_history')->name('vehicle.show.inspection_history');
    Route::GET('vehicle/show/service_remainder/{id}', 'VehicleController@service_remainder')->name('vehicle.show.service_remainder');
    Route::GET('vehicle/show/meter_history/{id}', 'VehicleController@meter_history')->name('vehicle.show.meter_history');

    Route::GET('vehicle/show/financial/{id}', 'VehicleController@financial')->name('vehicle.show.financial');
    Route::any('vehicle-dropdown', 'VehicleController@vehicle_dropdown')->name('vehicle_dropdown');

    Route::resource('vehicletype', 'VehicleTypeController');
    Route::resource('make', 'MakeController');
    // Route::resource('model', 'ModelController'); // Commented out - ModelController does not exist
    Route::resource('bodytype', 'BodyTypeController');
    Route::resource('expensetype', 'ExpenseTypeController');
    // Route::resource('vehicleassignment', 'VehicleAssignmentController'); // Commented out - VehicleAssignmentController does not exist

    Route::resource('tyre_fitting', 'TyreFittingController');
    Route::any('tyre_fitting/search', 'TyreFittingController@tyre_search')->name('tyre_fitting.search');


    Route::resource('tyre_removal', 'TyreRemovalController');
    Route::resource('tyre_transfer', 'TyreTransferController');
    Route::resource('tyre_retreading', 'TyreRetreadingController');
    Route::any('receive_retread_tyres', 'TyreRetreadingController@receive_retread_tyres')->name('tyre_retreading.receive_retread_tyres');
    Route::any('receive_retread_tyre_store', 'TyreRetreadingController@receive_retread_tyre_store')->name('tyre_retreading.receive_retread_tyre_store');


    Route::resource('fuelentry', 'FuelEntryController');
    Route::resource('expensehistory', 'ExpenseHistoryController');
    // Route::resource('meterhistory', 'MeterHistoryController'); // Commented out - MeterHistoryController does not exist
    Route::resource('issues', 'IssuesController');

    // Route::any('odometer_reading_history/{id}', 'MeterHistoryController@odometer_reading_history')->name('meterhistory.odometer_reading_history'); // Commented out - MeterHistoryController does not exist

    Route::GET('servicehistory/issues', 'ServiceHistoryController@getissues')->name('servicehistory.issues');
    Route::GET('servicehistory/servicetask', 'ServiceHistoryController@Addtask')->name('servicehistory.servicetask');
    Route::GET('service-task', 'ServiceHistoryController@service_task')->name('service.list');
    Route::GET('service-work', 'ServiceHistoryController@service_work')->name('service.work');


    Route::resource('servicehistory', 'ServiceHistoryController');
    Route::resource('servicetask', 'ServiceTasksController');
    Route::GET('servicetask/destroy/{id}', 'ServiceTasksController@destroy')->name('servicetask.destroy');

    Route::resource('service_remainder', 'ServiceRemainderController');

    Route::any('service_remainder/destroy/{id}', 'ServiceRemainderController@destroy')->name('service_remainder.destroy');


    /*inspection*/

    Route::resource('inspection_history', 'InspectionHistoryController');
    Route::any('inspection_history/create/{id}', 'InspectionHistoryController@create')->name('inspection_history.create');


    Route::resource('inspection_forms', 'InspectionFormsController');
    Route::any('inspection_forms/archive/{id}', 'InspectionFormsController@destroy')->name('inspection_forms.archive');

    Route::any('inspection_forms/edit/items/{id}', 'InspectionFormsController@edit_items')->name('inspection_forms.edit.items');
    Route::any('inspection_forms/edit/vehicle_schedule/{id}', 'InspectionFormsController@vehicle_schedule')->name('inspection_forms.edit.vehicle_schedule');
    Route::any('inspection_forms/store/items/', 'InspectionFormsController@store_items')->name('inspection_forms.store_items');

    // /maintaininvetoryitems
    Route::resource('tyre_inventories', 'TyreInventoriesController');

    Route::get('tyre-adjust-item-stock-form/{slug}', 'TyreInventoriesController@adjustItemStockForm')->name('admin.tyre.adjust-item-stock-form');

    Route::POST('tyre_inventories/export-pdf', 'TyreInventoriesController@exportPdf')->name('tyre.exportPdf');

    Route::get('tyre-maintain-inventory/serial-nos/{id}', 'TyreInventoriesController@stockMovesSerials')->name('tyre.stockMovesSerials');

    Route::GET('tyres-list', 'TyreInventoriesController@tyres_list')->name('tyres.list');
    Route::resource('tyreposition', 'TyrePositionController');

    Route::GET('tyre_inventories/serial-history/{id}/{inventory_item_id}', 'TyreInventoriesController@stockMovesSerialsHistory')->name('tyre_inventories.serial_history');

    Route::GET('vehicle-list', 'FuelEntryController@vehicle_list')->name('vehicle.list');
    Route::GET('vendor-list', 'FuelEntryController@WaSupplier')->name('vendor.list');

    Route::GET('previous-odometer', 'FuelEntryController@getPreviousOdometer')->name('fuelentry.get_previous_odometer');

    Route::GET('vehicle-lists', 'ExpenseHistoryController@vehicle_list')->name('vehicle.lists');
    Route::GET('vendor-lists', 'ExpenseHistoryController@WaSupplier')->name('vendor.lists');

    // Route::GET('vehicle-type', 'MeterHistoryController@vehicle_list')->name('vehicle.types'); // Commented out - MeterHistoryController does not exist

    Route::GET('vehicle-lists', 'IssuesController@vehicle_list')->name('vehicle.lists');
    Route::GET('user-list', 'IssuesController@User')->name('user.list');

    Route::GET('vehicle-list', 'ServiceHistoryController@vehicle_list')->name('vehicle.list');
    Route::GET('vendor-list', 'ServiceHistoryController@WaSupplier')->name('vendor.list');


    Route::get('exportpdf/', 'ExpenseHistoryController@createPDF')->name('exportpdf');
    Route::get('/pdf', 'ExpenseHistoryController@pdfview');

    Route::GET('/resolve', 'IssuesController@resolve')->name('resolve.user');
    Route::GET('/service', 'IssuesController@service')->name('service.user');

    Route::GET('subtype-lists', 'ServiceTasksController@subtype_list')->name('subtype.lists');
    Route::GET('servicetask-list', 'ServiceTasksController@servicetasks_list')->name('servicetask.list');

    Route::get('stock-takes/getCategories', 'ServiceHistoryController@getCategories')->name('admin.servicehistory.create');
    // Route::get('vehicle-listing/sheet', 'VehicleListingController@index')->name('vehicle-listing.index');
    // Route::resource('vehiclelisting', 'VehicleListingController'); // Commented out - VehicleListingController does not exist
    // Route::get('exportpdflisting/', 'VehicleListingController@createPDF')->name('exportpdflisting'); // Commented out - VehicleListingController does not exist
    // Route::get('/pdf', 'VehicleListingController@pdfview'); // Commented out - VehicleListingController does not exist

    Route::resource('operatingcostsummary', 'OperatingCostController');
    Route::get('exportpdfoperatingassigemet/', 'OperatingCostController@createPDF')->name('exportpdfoperatingassigemet');
    Route::get('/pdf', 'OperatingCostController@pdfview');
    /** Vechile Routes End */

    Route::resource('totalcosttrends', 'TotalCostTrendsController');

    // Service Report
    Route::resource('servicehistoryreport', 'ServiceSummaryReportController');
    Route::get('exportpdfoperatingassigemet/', 'ServiceSummaryReportController@createPDF')->name('exportpdfoperatingassigemet');
    Route::get('/pdf', 'ServiceSummaryReportController@pdfview');


    // Fuel Summary Report
    Route::resource('fuelsummary', 'FuelSummaryReportController');
    Route::get('exportpdfoperatingassigemet/', 'FuelSummaryReportController@createPDF')->name('exportpdfoperatingassigemet');
    Route::get('/pdf', 'FuelSummaryReportController@pdfview');


    // Issue Summary Report
    Route::resource('issuesummary', 'IssueSummaryReportController');
    //    Route::get('exportpdfoperatingassigemet/', 'IssueSummaryReportController@createPDF')->name('exportpdfoperatingassigemet');
    //    Route::get('/pdf', 'IssueSummaryReportController@pdfview');


    // Tyre Purchase Orders
    Route::resource('tyre-purchase-orders', 'TyrePurchaseOrderController');
    Route::get('tyre-purchase-orders/delete-item/{purchase_no}/{item_id}', 'TyrePurchaseOrderController@deletingItemRelation')->name('tyre-purchase-orders.items.delete');

    Route::get('tyre-purchase-orders/{purchase_no}/{id}/edit', 'TyrePurchaseOrderController@editPurchaseItem')->name('tyre-purchase-orders.editPurchaseItem');

    Route::post('tyre-purchase-orders/{id}/edit', 'TyrePurchaseOrderController@updatePurchaseItem')->name('tyre-purchase-orders.updatePurchaseItem');

    Route::get('tyre-purchase-orders/send-request/{purchase_no}', 'TyrePurchaseOrderController@sendRequisitionRequest')->name('tyre-purchase-orders.sendRequisitionRequest');

    Route::get('tyre-purchase-orders/archived-lpos', 'TyrePurchaseOrderController@archivedLPOs')->name('tyre-purchase-orders.archived-lpo');

    Route::post('tyre-purchase-orders/print', 'TyrePurchaseOrderController@print')->name('tyre-purchase-orders.print');

    Route::get('tyre-purchase-orders/pdf/{slug}', 'TyrePurchaseOrderController@exportToPdf')->name('tyre-purchase-orders.exportToPdf');
    Route::any('tyre-purchase-orders/hidepurchaseorder/{slug}', 'TyrePurchaseOrderController@hidepurchaseorder')->name('tyre-purchase-orders.hidepurchaseorder');
    Route::get('tyre-purchase-orders/inventoryItems/search-list', 'TyrePurchaseOrderController@inventoryItems')->name('tyre-purchase-orders.inventoryItems');
    Route::get('tyre-purchase-orders/inventoryItems/getInventryItemDetails', 'TyrePurchaseOrderController@getInventryItemDetails')->name('tyre-purchase-orders.getInventryItemDetails');

    Route::post('tyre-purchase-orders/getItemsList', 'TyrePurchaseOrderController@getItemsList')->name('tyre-purchase-orders.itemsList');
    Route::post('tyre-purchase-orders/getItems-detail', 'TyrePurchaseOrderController@getItemDetail')->name('tyre-purchase-orders.items.detail');

    Route::post('tyre-purchase-orders/getItems', 'TyrePurchaseOrderController@getItems')->name('tyre-purchase-orders.items');

    Route::get('tyre-purchase-orders/send-request/{purchase_no}', 'TyrePurchaseOrderController@sendRequisitionRequest')->name('tyre-purchase-orders.sendRequisitionRequest');

    Route::post('tyre-purchase-orders/view-last-purchases-price', 'TyrePurchaseOrderController@viewLastPurchasesPrice')->name('admin.tyre-purchase-orders.view-last-purchases-price');

    // Tyre Receivce Purchase Order

    Route::resource('receive-tyre-purchase-order', 'ReceiveTyrePurchasedOrderController');

    Route::get('receive-tyre-purchase/enter-serial-no/{id}', 'ReceiveTyrePurchasedOrderController@EnterSerialNo')->name('tyre-receive.EnterSerialNo');

    Route::POST('receive-tyre-purchase/save-enter-serial-no', 'ReceiveTyrePurchasedOrderController@saveEnterSerialNo')->name('tyre-receive.EnterSerialNo.save');

    Route::POST('receive-tyre-purchase/delete-enter-serial-no/{id}/{controlled_id}', 'ReceiveTyrePurchasedOrderController@deleteEnterSerialNo')->name('tyre-receive.EnterSerialNo.delete');

    Route::POST('receive-tyre-purchase/update-enter-serial-no/{id}', 'ReceiveTyrePurchasedOrderController@updateEnterSerialNo')->name('tyre-receive.EnterSerialNo.update');

    Route::get('tyre-stock-serial-new', 'ReceiveTyrePurchasedOrderController@downloadSerials')->name('tyre-purchase.downloadSerials');
    Route::POST('tyre-stock-serial-import-new', 'ReceiveTyrePurchasedOrderController@importSerials')->name('tyre-purchase.importSerials');

    // Tyre Approve LPO
    Route::get('tyre-approve-lpo/{purchase_no}/{id}/edit', 'TyreApproveLpoController@editPurchaseItem')->name('tyre-approve-lpo.editPurchaseItem');


    Route::post('tyre-approve-lpo/{id}/edit', 'TyreApproveLpoController@updatePurchaseItem')->name('tyre-approve-lpo.updatePurchaseItem');

    Route::get('tyre-approve-lpo/delete-item/{purchase_no}/{item_id}', 'ApproveLpoController@deletingItemRelation')->name('tyre-approve-lpo.items.delete');
    Route::resource('tyre-approve-lpo', 'TyreApproveLpoController');

    Route::post('add-asset-locations', 'NonStockPurchaseOrderController@addLocationId')->name('addAssetLocation');
    Route::post('add-asset-categories', 'NonStockPurchaseOrderController@addAssetCategory')->name('addAssetCategory');
    Route::post('add-asset', 'NonStockPurchaseOrderController@addAsset')->name('addAsset');
    Route::post('add-non-stock-item', 'NonStockPurchaseOrderController@addNonStockItem')->name('addNonStockItem');
    Route::resource('non-stock-purchase-orders', 'NonStockPurchaseOrderController');

    /* End Fleet Module */


    Route::get('maintain-suppliers/supplier-movement/gl-entries/{purchase_order_id}/{supplier_no}', 'SupplierController@supplierMovementGlEntries')->name('maintain-suppliers.supplier-movement-gl-entries');
    Route::post('maintain-suppliers/notification-join-supplier-portal', 'SupplierController@notificationJoinSupplierPortal')->name('maintain-suppliers.notificationJoinSupplierPortal');


    Route::get('maintain-suppliers/get-split-pop-up/{suppTransId}', 'SupplierController@getpaymentsummaryByreceiptId')->name('maintain-suppliers.supplier-popup');

    Route::get('maintain-suppliers/remittance-advice/{slug}', 'SupplierController@remittanceAdvice')->name('maintain-suppliers.remittance-advice');
    Route::get('maintain-suppliers/trade-agreement/{supplier_code}', 'SupplierController@tradeAgreementList')->name('maintain-suppliers.tradeAgreementList');


    Route::get('maintain-suppliers/enter-supplier-payment/{slug}', 'SupplierController@enterSupplierPayment')->name('maintain-suppliers.enter-supplier-payment');
    Route::post('maintain-suppliers/post-supplier-payment/{slug}', 'SupplierController@postSupplierPayment')->name('maintain-suppliers.post-supplier-payment');


    Route::post('maintain-suppliers/print-remittance-advice', 'SupplierController@printRemittanceAdvice')->name('maintain-suppliers.print-remittance-advice');
    Route::post('maintain-suppliers/supplierDataChange', 'SupplierController@supplierDataChange')->name('maintain-suppliers.supplierDataChange');
    Route::GET('maintain-suppliers/tradeAgreementChangeRequestList', 'SupplierController@tradeAgreementChangeRequestList')->name('maintain-suppliers.tradeAgreementChangeRequestList');
    Route::post('maintain-suppliers/supplierRequestDataApprove', 'SupplierController@supplierRequestDataApprove')->name('maintain-suppliers.supplierRequestDataApprove');

    Route::post('maintain-suppliers/post-splitted-amount/{suppTransId}', 'SupplierController@postSplittedAmount')->name('maintain-suppliers.post-splitted-amount');


    Route::get('maintain-suppliers/{supplier_code}/account-inquiry', 'SupplierController@accountInquiry')->name('maintain-suppliers.account-inquiry');
    Route::get('maintain-suppliers/supplier-statement', 'SupplierController@supplierStatement')->name('maintain-suppliers.supplier-statement');
    Route::get('maintain-suppliers/supplier-ledger-report', [SupplierLedgerReportController::class, 'index'])->name('maintain-suppliers.supplier-ledger-report');

    // Route::get('maintain-suppliers/pending-grns', 'SupplierController@supplier_invoice')->name('pending-grns.index');
    Route::get('pending-grns', 'PendingGrnController@index')->name('pending-grns.index');

    Route::get('maintain-suppliers/supplier-invoiced-list', 'SupplierController@supplier_invoiced_list')->name('maintain-suppliers.supplier_invoiced_list');
    Route::get('maintain-suppliers/pending-grns/order-details', 'SupplierController@supplier_invoice_order_details')->name('maintain-suppliers.supplier_invoice_order_details');
    Route::POST('maintain-suppliers/supplier-invoices/process', 'SupplierController@supplier_invoice_process')->name('maintain-suppliers.supplier_invoice_process');
    Route::POST('maintain-suppliers/supplier-invoices/make-archive', 'SupplierController@supplier_invoice_make_archive')->name('maintain-suppliers.supplier_invoice_make_archive');

    Route::get('maintain-suppliers/processed-invoices', 'ProcessedInvoiceController@index')->name('maintain-suppliers.processed_invoices.index');
    Route::get('maintain-suppliers/processed-invoices/{invoice}', 'ProcessedInvoiceController@show')->name('maintain-suppliers.processed_invoices.show');
    Route::post('maintain-suppliers/processed-invoices/{invoice}', 'ProcessedInvoiceController@update')->name('maintain-suppliers.processed_invoices.update');
    Route::post('maintain-suppliers/processed-invoices/{invoice}/reverse', 'ProcessedInvoiceController@reverse')->name('maintain-suppliers.processed_invoices.reverse');

    Route::get('maintain-suppliers/unverified-list', 'SupplierController@supplier_unverified_list')->name('maintain-suppliers.supplier_unverified_list');
    Route::get('maintain-suppliers/unverified-edit-list', 'SupplierController@supplier_unverified_edit_list')->name('maintain-suppliers.supplier_unverified_edit_list');

    Route::post('maintain-suppliers/unverified-update-unverified', 'SupplierController@saveunverifiedsupplier')->name('maintain-suppliers.updateunverified');


    Route::post('maintain-suppliers/rejectSupplierLog/{id}', 'SupplierController@rejectSupplierLog')->name('maintain-suppliers.rejectSupplierLog');

    Route::get('maintain-suppliers/unverified-show-list/{supplier_code}', 'SupplierController@supplier_unverified_show_list')->name('maintain-suppliers.supplier_unverified_show_list');

    Route::get('maintain-suppliers/unverified-update-list/{id}', 'SupplierController@updatesupplier')->name('maintain-suppliers.updatesupplier');


    Route::POST('maintain-suppliers/verify/{id}', 'SupplierController@supplier_unverified_process')->name('maintain-suppliers.supplier_unverified_process');

    Route::get('advance-payments', 'AdvancePaymentController@index')->name('advance-payments.index');
    Route::get('advance-payments/orders', 'AdvancePaymentController@orders')->name('advance-payments.orders');
    Route::post('advance-payments', 'AdvancePaymentController@store')->name('advance-payments.store');
    Route::delete('advance-payments/{payment}', 'AdvancePaymentController@destroy')->name('advance-payments.destroy');

    Route::get('match-purchase-orders', 'MatchPurchaseOrderController@index')->name('match-purchase-orders.index');
    Route::get('match-purchase-orders/orders', 'MatchPurchaseOrderController@orders')->name('match-purchase-orders.orders');
    Route::get('match-purchase-orders/children', 'MatchPurchaseOrderController@children')->name('match-purchase-orders.children');
    Route::post('match-purchase-orders', 'MatchPurchaseOrderController@store')->name('match-purchase-orders.store');

    Route::get('delivery-notes', 'DeliveryNoteController@index')->name('delivery-notes.index');
    Route::get('delivery-notes/create', 'DeliveryNoteController@create')->name('delivery-notes.create');
    Route::post('delivery-notes', 'DeliveryNoteController@store')->name('delivery-notes.store');

    Route::get('delivery-notes-invoices', 'DeliveryNoteInvoiceController@index')->name('delivery-notes-invoices.index');
    Route::get('delivery-notes-invoices/create', 'DeliveryNoteInvoiceController@create')->name('delivery-notes-invoices.create');
    Route::post('delivery-notes-invoices', 'DeliveryNoteInvoiceController@store')->name('delivery-notes-invoices.store');

    Route::get('delivery-notes-schedules', 'DeliveryNoteScheduleController@index')->name('delivery-notes-schedules.index');
    Route::get('delivery-notes-schedules/{lpo}/print', 'DeliveryNoteScheduleController@print')->name('delivery-notes-schedules.print');

    Route::post('maintain-suppliers/payment-vouchers/cheques/{code}', 'VoucherChequeController@store')->name('maintain-suppliers.payment_vouchers.cheques.store');
    Route::delete('maintain-suppliers/payment-vouchers/cheques/{code}', 'VoucherChequeController@destroy')->name('maintain-suppliers.payment_vouchers.cheques.destroy');

    Route::get('payment-vouchers', 'PaymentVoucherController@index')->name('payment-vouchers.index');
    Route::get('payment-vouchers/supplier-over-stocks', 'PaymentVoucherController@supplierOverStocks')->name('payment-vouchers.supplier-overstocks');
    Route::get('payment-vouchers/supplier-missing-stocks', 'PaymentVoucherController@supplierMissingStocks')->name('payment-vouchers.supplier-missingstocks');
    Route::get('payment-vouchers/supplier-slow-stocks', 'PaymentVoucherController@supplierSlowStocks')->name('payment-vouchers.supplier-slowstocks');
    Route::get('payment-vouchers/supplier-dead-stocks', 'PaymentVoucherController@supplierDeadStocks')->name('payment-vouchers.supplier-deadstocks');
    Route::get('payment-vouchers/supplier-sales', 'PaymentVoucherController@supplierSales')->name('payment-vouchers.supplier-sales');
    Route::get('payment-vouchers/supplier-returns', 'PaymentVoucherController@supplierReturns')->name('payment-vouchers.supplier-returns');
    Route::get('payment-vouchers/supplier-pricedrops', 'PaymentVoucherController@supplierPricedrops')->name('payment-vouchers.supplier-pricedrops');
    Route::get('payment-vouchers/supplier-discounts', 'PaymentVoucherController@supplierDiscounts')->name('payment-vouchers.supplier-discounts');
    Route::get('payment-vouchers/supplier-invoice-variance', 'PaymentVoucherController@supplierInvoiceVariance')->name('payment-vouchers.supplier-invoice-variance');
    Route::get('payment-vouchers/supplier-invoice-aging', 'PaymentVoucherController@supplierInvoiceAging')->name('payment-vouchers.supplier-invoice-aging');
    Route::get('payment-vouchers/stock-movements', 'PaymentVoucherController@stockMovements')->name('payment-vouchers.stock-movements');
    Route::get('payment-vouchers/{voucher}/show', 'PaymentVoucherController@show')->name('payment-vouchers.show');
    Route::get('payment-vouchers/{voucher}/edit', 'PaymentVoucherController@edit')->name('payment-vouchers.edit');
    Route::post('payment-vouchers/{voucher}/update', 'PaymentVoucherController@update')->name('payment-vouchers.update');
    Route::get('payment-vouchers/{voucher}/print-pdf', 'PaymentVoucherController@printPdf')->name('payment-vouchers.print_pdf');
    Route::get('payment-vouchers/{voucher}/print-remittance', 'PaymentVoucherController@printRemittance')->name('payment-vouchers.print_remittance');
    Route::post('payment-vouchers/{voucher}/approve', 'PaymentVoucherController@approve')->name('payment-vouchers.approve');
    Route::post('payment-vouchers/{voucher}/decline', 'PaymentVoucherController@decline')->name('payment-vouchers.decline');
    Route::post('payment-vouchers/{voucher}/confirm', 'PaymentVoucherController@confirm')->name('payment-vouchers.confirm');
    Route::get('payment-vouchers-report', 'PaymentVouchersReportController@index')->name('payment-vouchers-report.index');
    Route::get('payment-vouchers-approved-pending-report', 'PaymentVouchersReportController@approvedPendingReport')->name('payment-vouchers.approved.pending.report');

    Route::get('payment-vouchers/create/{code}', 'PaymentVoucherController@create')->name('maintain-suppliers.payment_vouchers.create');
    Route::post('payment-vouchers/{code}', 'PaymentVoucherController@store')->name('maintain-suppliers.payment_vouchers.store');

    Route::get('bank-files', 'BankFilesController@index')->name('bank-files.index');
    Route::get('bank-files/create', 'BankFilesController@create')->name('bank-files.create');
    Route::get('bank-files/items', 'BankFilesController@fileItems')->name('bank-files.items');
    Route::get('bank-files/show', 'BankFilesController@show')->name('bank-files.show');
    Route::get('bank-files/{file}/download', 'BankFilesController@download')->name('bank-files.download');
    Route::get('bank-files/{file}/edit', 'BankFilesController@edit')->name('bank-files.edit');
    Route::get('bank-files/{file}/supporting-document', 'BankFilesController@supportingDocument')->name('bank-files.supporting-document');
    Route::post('bank-files', 'BankFilesController@store')->name('bank-files.store');
    Route::put('bank-files/{file}', 'BankFilesController@update')->name('bank-files.update');

    Route::get('bank-payments-report', 'BankPaymentsReportController@index')->name('bank-payments-report.index');

    Route::get('withholding-files', 'WithholdingFilesController@index')->name('withholding-files.index');
    Route::get('withholding-files/create', 'WithholdingFilesController@create')->name('withholding-files.create');
    Route::get('withholding-files/show', 'WithholdingFilesController@show')->name('withholding-files.show');
    Route::get('withholding-files/{file}/download', 'WithholdingFilesController@download')->name('withholding-files.download');
    Route::post('withholding-files', 'WithholdingFilesController@store')->name('withholding-files.store');
    Route::delete('withholding-files/{file}', 'WithholdingFilesController@destroy')->name('withholding-files.destroy');

    Route::get('withholding-tax-payments', 'WithholdingTaxPaymentController@index')->name('withholding-tax-payments.index');
    Route::get('withholding-tax-payments/create', 'WithholdingTaxPaymentController@create')->name('withholding-tax-payments.create');
    Route::post('withholding-tax-payments', 'WithholdingTaxPaymentController@store')->name('withholding-tax-payments.store');
    Route::post('withholding-tax-payments/{voucher}/approve', 'WithholdingTaxPaymentController@approve')->name('withholding-tax-payments.approve');
    Route::get('withholding-tax-payments/{voucher}/edit', 'WithholdingTaxPaymentController@edit')->name('withholding-tax-payments.edit');
    Route::get('withholding-tax-payments/{voucher}/print', 'WithholdingTaxPaymentController@print')->name('withholding-tax-payments.print');
    Route::put('withholding-tax-payments/{voucher}', 'WithholdingTaxPaymentController@update')->name('withholding-tax-payments.update');
    Route::delete('withholding-tax-payments/{voucher}', 'WithholdingTaxPaymentController@destroy')->name('withholding-tax-payments.destroy');
    Route::get('withholding-tax-payments-report', 'WithholdingTaxPaymentsReportController@index')->name('withholding-tax-payments-report.index');

    Route::get('credit-debit-notes', 'FinancialNoteController@index')->name('credit-debit-notes.index');
    Route::get('credit-debit-notes/create', 'FinancialNoteController@create')->name('credit-debit-notes.create');
    Route::post('credit-debit-notes', 'FinancialNoteController@store')->name('credit-debit-notes.store');
    Route::get('credit-debit-notes/supplier-invoices', 'FinancialNoteController@supplierInvoices')->name('credit-debit-notes.supplier-invoices');
    Route::put('credit-debit-notes/allocate', 'FinancialNoteController@allocate')->name('credit-debit-notes.allocate');
    Route::put('credit-debit-notes/{note}', 'FinancialNoteController@update')->name('credit-debit-notes.update');
    Route::get('credit-debit-notes/{note}/edit', 'FinancialNoteController@edit')->name('credit-debit-notes.edit');
    Route::put('credit-debit-notes/{note}/deallocate', 'FinancialNoteController@deallocate')->name('credit-debit-notes.deallocate');
    Route::delete('credit-debit-notes/{note}', 'FinancialNoteController@destroy')->name('credit-debit-notes.destroy');

    Route::get('maintain-suppliers/vendor-centre/payment-vouchers/{id}', 'VendorCentreController@payables')->name('maintain-suppliers.vendor_centre.payables');
    Route::get('maintain-suppliers/vendor-centre/payables/{id}', 'VendorCentreController@payables')->name('maintain-suppliers.vendor_centre.payables');
    Route::get('maintain-suppliers/vendor-centre/grn/{id}', 'VendorCentreController@grn')->name('maintain-suppliers.vendor_centre.grn');
    Route::get('maintain-suppliers/vendor-centre/statement/{id}', 'VendorCentreController@statement')->name('maintain-suppliers.vendor_centre.statement');
    Route::get('maintain-suppliers/vendor-centre/payments/{id}', 'VendorCentreController@payments')->name('maintain-suppliers.vendor_centre.payments');
    Route::get('maintain-suppliers/vendor-centre/price-list/{id}', 'VendorCentreController@priceList')->name('maintain-suppliers.vendor_centre.price_list');
    // Route::get('maintain-suppliers/vendor-centre/demands/{id}', 'VendorCentreController@demands')->name('maintain-suppliers.vendor_centre.demands');
    Route::get('maintain-suppliers/vendor-centre/monthly-demands/{id}', 'VendorCentreController@monthly_demands')->name('maintain-suppliers.vendor_centre.monthly-demands');
    Route::get('maintain-suppliers/vendor-centre/demands/{id}', 'VendorCentreController@refactoredDemands')->name('maintain-suppliers.vendor_centre.demands');
    Route::get('maintain-suppliers/vendor-centre/returns/{id}', 'VendorCentreController@returns')->name('maintain-suppliers.vendor_centre.returns');
    Route::get('maintain-suppliers/vendor-centre/stock-balances', 'VendorCentreController@stock_balances')->name('maintain-suppliers.vendor_centre.stock_balances');
    Route::get('maintain-suppliers/vendor-centre/{code}', 'VendorCentreController@show')->name('maintain-suppliers.vendor_centre');

    Route::get('trade-discounts', 'TradeDiscountController@index')->name('trade-discounts.index');
    Route::post('trade-discounts', 'TradeDiscountController@store')->name('trade-discounts.store');
    Route::get('trade-discounts/{discount}', 'TradeDiscountController@show')->name('trade-discounts.show');
    Route::put('trade-discounts/{discount}', 'TradeDiscountController@update')->name('trade-discounts.update');
    Route::delete('trade-discounts/{discount}', 'TradeDiscountController@destroy')->name('trade-discounts.destroy');

    Route::get('trade-discounts-report', 'TradeDiscountReportController@index')->name('trade-discounts-report.index');

    Route::get('trade-discount-demands', 'TradeDiscountDemandController@index')->name('trade-discount-demands.index');
    Route::post('trade-discount-demands', 'TradeDiscountDemandController@store')->name('trade-discount-demands.store');
    Route::get('trade-discount-demands/{demand}', 'TradeDiscountDemandController@show')->name('trade-discount-demands.show');
    Route::get('trade-discount-demands/{demand}/edit', 'TradeDiscountDemandController@edit')->name('trade-discount-demands.edit');
    Route::put('trade-discount-demands/{demand}', 'TradeDiscountDemandController@update')->name('trade-discount-demands.update');
    Route::delete('trade-discount-demands/{demand}', 'TradeDiscountDemandController@destroy')->name('trade-discount-demands.destroy');

    Route::get('trade-discount-demands-report', 'TradeDiscountDemandReportController@index')->name('trade-discount-demands-report.index');

    Route::resource('maintain-suppliers', 'SupplierController');
    Route::get('maintain-suppliers-datatable', 'SupplierController@datatable')->name('maintain-suppliers.datatable');

    Route::get('grns-against-invoices-report', [GrnsAgainstInvoicesReportController::class, 'index'])->name('grns-against-invoices.index');
    Route::prefix('suppliers-utilities')->name('suppliers-utilities.')->group(function () {
        Route::get('supplier-montly-demand', 'SupplierDiscountDemandController@monthly_demand_index')->name('supplier-montly-demand.index');
        Route::get('supplier-montly-demand/generate', 'SupplierDiscountDemandController@monthly_demand_generate')->name('supplier-montly-demand.generate');
    });
    Route::prefix('suppliers-overview')->group(function () {
        Route::get('', [SupplierOverviewController::class, 'index'])->name('suppliers-overview.index');
        Route::get('suppliers', [SupplierOverviewController::class, 'suppliersList'])->name('suppliers-overview.suppliers-list');
        Route::get('suppliers/print', [SupplierOverviewController::class, 'printSuppliers'])->name('suppliers-overview.suppliers-print');
        Route::get('suppliers-sales-by-category', [SupplierOverviewController::class, 'suppliersSalesByCategory'])->name('suppliers-overview.suppliers-sales-by-category');
        Route::get('suppliers-sales-by-category/print', [SupplierOverviewController::class, 'printSuppliersSalesByCategory'])->name('suppliers-overview.suppliers-sales-by-category-print');
        Route::get('lpos-without-grn', [SupplierOverviewController::class, 'lposWithoutGrn'])->name('suppliers-overview.lpos-without-grn');
        Route::get('unprocessed-demands', [SupplierOverviewController::class, 'unprocessedDemands'])->name('suppliers-overview.unprocessed-demands');
        Route::get('pending-good-returns', [SupplierOverviewController::class, 'pendingGoodReturns'])->name('suppliers-overview.pending-good-returns');
        Route::get('departmental-performace', [SupplierOverviewController::class, 'departmentalPerformance'])->name('suppliers-overview.departmental-performance');
        Route::get('branch-requisitions', [SupplierOverviewController::class, 'branchRequisitions'])->name('suppliers-overview.branch-requisitions');
    });

    ///////////////////////////////////////////////////////////////////////

    Route::get('maintain-raw-material-items/stock-movements/{stockIdCode}', 'MaintainRawMaterialController@stockMovements')->name('maintain-raw-material-items.stock-movements');
    Route::get('maintain-raw-material-items/stock-movements-2/{stockIdCode}', 'MaintainRawMaterialController@stockMovements2')->name('maintain-raw-material-items.stock-movements-2');
    Route::get('maintain-raw-material-items/stock-status/{stockIdCode}', 'MaintainRawMaterialController@stockStatus')->name('maintain-raw-material-items.stock-status');
    Route::resource('maintain-raw-material-items', 'MaintainRawMaterialController');
    Route::post('maintain-raw-material-items/item-datatable', 'MaintainRawMaterialController@datatable')->name('admin.maintain-raw-material-items-datatable');
    ////////////////////////////////////////////////////////////////////////


    Route::get('maintain-items/stock-movements/{stockIdCode}', 'InventoryItemController@stockMovements')->name('maintain-items.stock-movements');
    Route::get('maintain-items/stock-movements/supplier/{stockIdCode}', 'InventoryItemController@supplier_stock_movements')->name('maintain-items.supplier-stock-movements');
    Route::get('maintain-items/stock-movements-2/{stockIdCode}', 'InventoryItemController@stockMovements2')->name('maintain-items.stock-movements-2');
    Route::get('maintain-items/item-stock-status', 'InventoryItemController@itemStockStatus')->name('maintain-items.item-stock-status');
    Route::get('maintain-items/stock-status/{stockIdCode}', 'InventoryItemController@stockStatus')->name('maintain-items.stock-status');
    Route::get('maintain-items/stock-bin-location/{stockIdCode}', 'InventoryItemController@stockBinLocation')->name('maintain-items.stock-bin-location');
    Route::get('maintain-items/price-change/pending-list', 'InventoryItemController@item_price_pending_list')->name('maintain-items.item_price_pending_list');
    Route::get('maintain-items/price-change/history-list', 'InventoryItemController@item_price_history_list')->name('maintain-items.item_price_history_list');
    Route::POST('maintain-items/price-change/verify/{id}', 'InventoryItemController@item_price_pending_verify')->name('maintain-items.item_price_pending_verify');

    /*
    * ITEM APPROVAL START
    */
    Route::get('item-approval/{status}', 'InventoryItemController@item_approval')->name('item-approval');
    Route::get('item-approvals/item-pending-new-approval', 'InventoryItemController@item_new_approval')->name('item-new-approval');
    Route::get('item-approvals/item-pending-new-approval/{id}', 'InventoryItemController@item_new_approval_show')->name('item-new-approval-show');
    Route::get('item-approvals/item-new-approval-reject/{id}', 'InventoryItemController@item_new_approval_reject')->name('item-new-approval-reject');
    Route::post('item-approvals/item-new-approval-approve/{id}', 'InventoryItemController@item_new_approval_approve')->name('item-new-approval-approve');
    Route::get('item-approval-update/{item}/{status}', 'InventoryItemController@update_approval')->name('maintain-items.update_approval');
    Route::get('item-approval/download/excel/{status}', 'InventoryItemController@downloadInvetoryitemsApproval')->name('admin.downloadExcel.approval');
    Route::get('maintain-items/approval/{slug}', 'InventoryItemController@showApproval')->name('admin.show.approval');
    Route::get('maintain-items/show-log', 'InventoryItemController@showItemLog')->name('admin.show.item.log');
    Route::get('maintain-items/show-log/datatable', 'InventoryItemController@showItemLogDatatable')->name('admin.show.item.log.datatable');
    Route::get('maintain-items/show-log/view/{id}', 'InventoryItemController@showItemLogView')->name('admin.show_item_log.view');
    Route::post('item-approvals/approve-bulk-items', 'InventoryItemController@approve_bulk_items')->name('approve_bulk_items');
    Route::post('maintain-items/clone', 'InventoryItemController@cloneItem')->name('maintain-items.clone');



    /*
    * ITEM APPROVAL END
    */

    Route::get('item-centre/{item}', [ItemCentreController::class, 'show'])->name('item-centre.show');
    Route::get('item-centre/{item}/stock-movements', [ItemCentreController::class, 'stockMovements'])->name('item-centre.stock-movements');
    Route::get('item-centre/{item}/price-change-history', [ItemCentreController::class, 'priceChangeHistory'])->name('item-centre.price-change-history');

    Route::resource('maintain-items', 'InventoryItemController');
    Route::post('maintain-items/item-datatable', 'InventoryItemController@datatable')->name('admin.maintain-items-datatable');
    Route::post('maintain-items/item-datatable-approval', 'InventoryItemController@datatableApproval')->name('admin.maintain-items-datatable-approval');

    Route::resource('number-series', 'NumerSeriesCodeController');


    Route::post('external-requisitions/getDapartments', 'ExternalRequisitionController@getDapartments')->name('external-requisitions.get-departments');
    Route::GET('external-requisitions/getOutOfStockItems', 'ExternalRequisitionController@getOutOfStockItems')->name('external-requisitions.getOutOfStockItems');
    Route::get('external-requisitions/inventoryItems/getInventryItemDetails', 'ExternalRequisitionController@getInventryItemDetails')->name('external-requisitions.getInventryItemDetails');
    Route::get('external-requisitions/create-non-stock', 'ExternalRequisitionController@create_non_stock')->name('external-requisitions.create_non_stock');
    Route::POST('external-requisitions/store-non-stock', 'ExternalRequisitionController@store_non_stock')->name('external-requisitions.store_non_stock');
    Route::get('external-requisitions/unit-of-measures', 'ExternalRequisitionController@get_WaUnitOfMeasure')->name('external-requisitions.get_WaUnitOfMeasure');

    Route::get('external-requisitions/archived-requisition', 'ExternalRequisitionController@archivedRequisition')->name('external-requisitions.archivedRequisition');
    Route::post('external-requisitions/getItems', 'ExternalRequisitionController@getItems')->name('external-requisitions.items');
    Route::post('external-requisitions/getItems-detail', 'ExternalRequisitionController@getItemDetail')->name('external-requisitions.items.detail');
    Route::get('external-requisitions/delete-item/{purchase_no}/{item_id}', 'ExternalRequisitionController@deletingItemRelation')->name('external-requisitions.items.delete');
    Route::get('external-requisitions/print/{purchase_no}', 'ExternalRequisitionController@downloadPrint')->name('external-requisitions.purchase.print');
    Route::get('external-requisitions/{purchase_no}/{id}/edit', 'ExternalRequisitionController@editPurchaseItem')->name('external-requisitions.editPurchaseItem');
    Route::post('external-requisitions/{id}/edit', 'ExternalRequisitionController@updatePurchaseItem')->name('external-requisitions.updatePurchaseItem');
    Route::get('external-requisitions/send-request/{purchase_no}', 'ExternalRequisitionController@sendRequisitionRequest')->name('external-requisitions.sendRequisitionRequest');
    Route::post('external-requisitions/print', 'ExternalRequisitionController@print')->name('external-requisitions.print');
    Route::get('external-requisitions/pdf/{slug}', 'ExternalRequisitionController@exportToPdf')->name('external-requisitions.exportToPdf');
    Route::get('external-requisitions/hideexternalquisition/{slug}', 'ExternalRequisitionController@hideexternalquisition')->name('external-requisitions.hideexternalquisition');
    Route::resource('external-requisitions', 'ExternalRequisitionController');


    Route::get('approve-external-requisitions/{purchase_no}/{id}/edit', 'ApproveExternalRequisitionController@editPurchaseItem')->name('approve-external-requisitions.editPurchaseItem');
    Route::post('approve-external-requisitions/{id}/edit', 'ApproveExternalRequisitionController@updatePurchaseItem')->name('approve-external-requisitions.updatePurchaseItem');
    Route::get('approve-external-requisitions/delete-item/{purchase_no}/{item_id}', 'ApproveExternalRequisitionController@deletingItemRelation')->name('approve-external-requisitions.items.delete');
    Route::resource('approve-external-requisitions', 'ApproveExternalRequisitionController');


    Route::get('route-delivery-centers/{route_id}', 'DeliveryCentresController@routeDeliveryCenters')->name('route-delivery-centers');
    Route::get('route-plan/{route_id}', 'RoutePlanController@viewRoutePlan')->name('route-plan');
    Route::get('create-route-plan/{route_id}', 'RoutePlanController@create')->name('create-route-plan');
    Route::post('create-route-plan', 'RoutePlanController@store')->name('create-route-plan.store');
    Route::get('edit-route-plan/{routePlan}', 'RoutePlanController@edit')->name('edit-route-plan');
    Route::post('edit-route-plan/{routePlan}', 'RoutePlanController@update')->name('edit-route-plan.edit');

    Route::resource('delivery-center', 'DeliveryCentresController');
    Route::get('create-route-delivery-center/{route_id}', 'DeliveryCentresController@createRouteCenter')->name('create-route-delivery-center');

    Route::get('route-delivery-centers/{route_id}', 'DeliveryCentresController@routeDeliveryCenters')->name('route-delivery-centers');
    Route::resource('delivery-center', 'DeliveryCentresController');
    Route::get('create-route-delivery-center/{route_id}', 'DeliveryCentresController@createRouteCenter')->name('create-route-delivery-center');

    Route::prefix('routes')->name('admin.routes.')->group(function () {
        Route::get('/{id}/plan', 'RouteController@showPlan')->name('plan');
        Route::post('/{id}/plan/create', 'RouteMasterPlanController@store')->name('plans.store');
        Route::post('/{id}/plan/update', 'RouteMasterPlanController@update')->name('plans.update');
    });


    Route::get('confirm-invoice/pdf/{slug}', 'IssueFullfillRequisitionController@exportToPdf')->name('confirm-invoice.exportToPdf');
    Route::any('confirm-invoice/invoice_dispatch_report', 'IssueFullfillRequisitionController@invoice_dispatch_report')->name('confirm-invoice.invoice_dispatch_report');
    Route::any('confirm-invoice/invoice_dispatch_report_profit', 'IssueFullfillRequisitionController@invoice_dispatch_report_profit')->name('confirm-invoice.invoice_dispatch_report_profit');

    Route::any('confirm-invoice/dispatch_and_close_loading_sheet', 'IssueFullfillRequisitionController@dispatch_and_close_loading_sheet')->name('confirm-invoice.dispatch_and_close_loading_sheet');
    Route::get('confirm-invoice/assign_loading_sheet', 'DeliveryLoadingSheetController@showAssignForm')->name('confirm-invoice.assign-form');
    Route::post('confirm-invoice/assign_loading_sheet', 'DeliveryLoadingSheetController@assign')->name('confirm-invoice.assign');

    Route::any('confirm-invoice/dispatch_and_close_loading_sheet_post', 'IssueFullfillRequisitionController@dispatch_and_close_loading_sheet_post')->name('confirm-invoice.dispatch_and_close_loading_sheet_post');


    Route::get('confirm-invoice-test/pdf/{slug}', 'IssueFullfillRequisitionTestController@exportToPdf')->name('confirm-invoice-test.exportToPdf');
    Route::any('confirm-invoice-test/invoice_dispatch_report', 'IssueFullfillRequisitionTestController@invoice_dispatch_report')->name('confirm-invoice-test.invoice_dispatch_report');
    Route::post('confirm-invoice-test/print', 'IssueFullfillRequisitionTestController@printPage')->name('confirm-invoice-test.print');
    Route::resource('confirm-invoice-test', 'IssueFullfillRequisitionTestController');

    Route::get('processed-requisition/pdf/{slug}', 'ProcessedRequisitionController@exportToPdf')->name('processed-requisition.exportToPdf');
    Route::post('processed-requisition/print', 'ProcessedRequisitionController@printPage')->name('processed-requisition.print');
    Route::resource('processed-requisition', 'ProcessedRequisitionController');


    Route::get('authorise-requisitions/delete-item/{purchase_no}/{item_id}', 'ApproveInternalRequisitionController@deletingItemRelation')->name('authorise-requisitions.items.delete');


    Route::get('authorise-requisitions/{purchase_no}/{id}/edit', 'ApproveInternalRequisitionController@editPurchaseItem')->name('authorise-requisitions.editPurchaseItem');
    Route::post('authorise-requisitions/{id}/edit', 'ApproveInternalRequisitionController@updatePurchaseItem')->name('authorise-requisitions.updatePurchaseItem');
    Route::resource('authorise-requisitions', 'ApproveInternalRequisitionController');


    Route::get('refresh-stockmoves', 'InventoryLocationTransferController@refreshstockmoves')->name('transfers.refreshstockmoves');

    Route::post('get-manual-entry', 'InventoryLocationTransferController@getManualItemsList')->name('transfers.getManualItemsList');

    Route::get('transfers/{transfer_no}/{id}/edit', 'InventoryLocationTransferController@editPurchaseItem')->name('transfers.editPurchaseItem');

    Route::post('transfers/{id}/edit', 'InventoryLocationTransferController@updatePurchaseItem')->name('transfers.updatePurchaseItem');

    Route::get('transfers/delete-item/{transfer_no}/{item_id}', 'InventoryLocationTransferController@deletingItemRelation')->name('transfers.items.delete');


    Route::get('transfers/process-transfer/{transfer_no}', 'InventoryLocationTransferController@processTransfer')->name('transfers.processTransfer');


    Route::get('transfers/{transfer_no}/print-to-pdf', 'InventoryLocationTransferController@printToPdf')->name('transfers.printToPdf');
    Route::get('transfers/{transfer_no}/print-return-to-pdf', 'InventoryLocationTransferController@printReturnToPdf')->name('transfers.printReturnToPdf');


    Route::post('transfers/print', 'InventoryLocationTransferController@print')->name('transfers.print');
    Route::post('transfers/print-return', 'InventoryLocationTransferController@print_return')->name('transfers.print-return');


    Route::post('transfers/check-quantity', 'InventoryLocationTransferController@checkQuantity')->name('transfers.checkQuantity');

    Route::get('transfers/return/{slug}', 'InventoryLocationTransferController@return_show')->name('transfers.return_show');
    Route::post('transfers/return/{slug}', 'InventoryLocationTransferController@return_process')->name('transfers.return_process');

    Route::get('transfers/return-list-pending', 'InventoryLocationTransferController@return_list_pending')->name('transfers.return_list_pending');
    Route::get('transfers/return-list-pending-over', 'InventoryLocationTransferController@return_list_overlimit')->name('transfers.return_list_pending_over');

    Route::get('transfers/return-list-pending/{route}/{date}', 'InventoryLocationTransferController@return_list_route')->name('transfers.return_list_route');
    Route::get('transfers/return-list-pending_2/{route}/{date}', 'InventoryLocationTransferController@return_list_route_2')->name('transfers.return_list_route_2');
    Route::get('transfers/return-list-pending_late_returns/{route}', 'InventoryLocationTransferController@return_list_route_late_returns')->name('transfers.return_list_route_late_returns');
    Route::get('transfers/return-list-groups', 'InventoryLocationTransferController@return_list_groups')->name('transfers.return_list_groups');
    Route::get('transfers/return-list-pending-over', 'InventoryLocationTransferController@return_list_overlimit')->name('transfers.return_list_pending_over');
    Route::get('transfers/return-list-groups-2', 'InventoryLocationTransferController@return_list_groups_2')->name('transfers.return_list_groups_2');
    Route::get('transfers/return-list-groups-late-returns', 'InventoryLocationTransferController@return_list_groups_late_returns')->name('transfers.return_list_groups_late_returns');
    Route::get('transfers/return-groups', 'InventoryLocationTransferController@return_groups')->name('transfers.return_groups');
    Route::get('transfers/return-groups/details/{start_date}/{end_date}/{route}/{type}', 'InventoryLocationTransferController@over_limit_returns_details')->name('transfers.return_groups.over_limit_returns_details');


    Route::post('transfers/returns/process-group-returns', 'InventoryLocationTransferController@processgroupReturn')->name('transfers.returns.process_group_return');

    Route::get('transfers/returns/{number}/items', 'InventoryLocationTransferController@return_list_items')->name('transfers.return_list_items');
    Route::get('transfers/returns/{number}/items-pending', 'InventoryLocationTransferController@return_list_items_pending')->name('transfers.return_list_items_pending');
    Route::get('transfers/returns/{number}/items-pending/approver2', 'InventoryLocationTransferController@return_list_items_pending_approver_2')->name('transfers.return_list_items_pending_approver2');
    Route::get('transfers/returns/{number}/items-pending/late', 'InventoryLocationTransferController@return_list_items_pending_late')->name('transfers.return_list_items_pending_late');


    Route::post('transfers/returns/{number}/process', 'InventoryLocationTransferController@processReturn')->name('transfers.returns.process');
    Route::post('transfers/returns/process', 'InventoryLocationTransferController@processReturn2')->name('transfers.returns.process_return');
    Route::post('transfers/returns/process-pending', 'InventoryLocationTransferController@processPendingReturn')->name('transfers.returns.process_return_pending');
    Route::post('transfers/returns/process-pending/approver-2', 'InventoryLocationTransferController@processPendingReturnApprover2')->name('transfers.returns.process_return_pending_approver_2');
    Route::post('transfers/returns/process-pending/late-return', 'InventoryLocationTransferController@processPendingReturnLateReturn')->name('transfers.returns.process_return_pending_late_return');
    Route::get('transfers/returns/completed', [CompletedReturnsController::class, 'index'])->name('completed_returns.index');
    Route::get('completed/details/{return}/{date}', [CompletedReturnsController::class, 'completedReturnsDetails'])->name('completedReturnsDetails');
    Route::get('transfers/returns/detailed-completed-returns', [CompletedReturnsController::class, 'detailedCompletedReturns'])->name('detailedCompletedReturns');


    Route::post('transfers/returns/send-otp', 'InventoryLocationTransferController@sendOtp')->name('transfers.returns.otp');
    Route::post('transfers/returns/check-otp', 'InventoryLocationTransferController@checkOtp')->name('transfers.returns.check');
    Route::get('transfers/returns/processed', 'InvoiceReturnController@showProcessedReturnsPage')->name('transfers.processed-returns');
    Route::get('transfers/returns/processed/datatable', 'InvoiceReturnController@showProcessedReturnsPageDatatable')->name('transfers.processed-returns-datatable');
    Route::get('transfers/returns/rejected', 'InvoiceReturnController@showRejectedReturnsPage')->name('transfers.rejected-returns');
    Route::get('transfers/returns/rejected/datatable', 'InvoiceReturnController@showRejectedReturnsPageDatatable')->name('transfers.rejected-returns-datatable');

    Route::get('transfers/resign-esd/{id}', 'InventoryLocationTransferController@resign_esd')->name('transfers.resign_esd');
    Route::PATCH('transfers/resign-esd-post/{id}', 'InventoryLocationTransferController@resign_esd_post')->name('transfers.resign_esd_post');

    Route::resource('transfers', 'InventoryLocationTransferController');
    Route::get('transfers/invoice-resign-esd/{id}', 'InventoryLocationTransferController@invoiceResignEsd')->name('transfers.invoice-resign-esd');
    Route::PATCH('transfers/invoice-resign-esd-post/{id}', 'InventoryLocationTransferController@invoiceResignEsdPost')->name('transfers.invoice-resign-esd-post');

    Route::POST('my-route-customers/{id}/update', 'RouteUserController@route_customer_update')->name('my-route-customers.route_customer_update');


    Route::POST('maintain-customers/route-customer/{id}/update', 'CustomerController@route_customer_update')->name('maintain-customers.route_customer_update');

    Route::resource('my-route-customers', 'RouteUserController');
    Route::resource('my-route-customers', 'RouteUserController');


    Route::resource('new-kra-signed-invoices', 'NewKRASignedInvoiceController');


    Route::post('purchase-orders/getItems-detail', 'PurchaseOrderController@getItemDetail')->name('purchase-orders.items.detail');
    Route::get('purchase-orders/distributor', 'PurchaseOrderController@checkSupplierType')->name('purchase-orders.distributor');
    Route::post('purchase-orders/getItems', 'PurchaseOrderController@getItems')->name('purchase-orders.items');
    Route::post('purchase-orders/get-supplier-discounts', 'PurchaseDiscountController@get_supplier_discounts')->name('purchase-orders.get-supplier-discounts');
    Route::post('purchase-orders/getItemsList', 'PurchaseOrderController@getItemsList')->name('purchase-orders.itemsList');

    Route::get('purchase-orders/archived-lpos', 'PurchaseOrderController@archivedLPOs')->name('purchase-orders.archived-lpo');
    Route::get('purchase-orders/completed-lpos', 'PurchaseOrderController@completedLPOs')->name('purchase-orders.completed-lpo');
    Route::get('purchase-orders/unarchieved-lpo/{slug}', 'PurchaseOrderController@unarchive_lpo')->name('purchase-orders.unarchive_lpo');

    Route::get('purchase-orders/delete-item/{purchase_no}/{item_id}', 'PurchaseOrderController@deletingItemRelation')->name('purchase-orders.items.delete');

    Route::get('purchase-orders/{purchase_no}/{id}/edit', 'PurchaseOrderController@editPurchaseItem')->name('purchase-orders.editPurchaseItem');

    Route::post('purchase-orders/{id}/edit', 'PurchaseOrderController@updatePurchaseItem')->name('purchase-orders.updatePurchaseItem');

    Route::get('purchase-orders/send-request/{purchase_no}', 'PurchaseOrderController@sendRequisitionRequest')->name('purchase-orders.sendRequisitionRequest');

    Route::post('purchase-orders/print', 'PurchaseOrderController@print')->name('purchase-orders.print');
    Route::get('purchase-orders/status-report', 'PurchaseOrderController@status_report')->name('purchase-orders.status_report');

    Route::get('purchase-orders/hidepurchaseorder/{slug}', 'PurchaseOrderController@hidepurchaseorder')->name('purchase-orders.hidepurchaseorder');
    Route::get('purchase-orders/inventoryItems/search-list', 'PurchaseOrderController@inventoryItems')->name('purchase-orders.inventoryItems');
    Route::get('purchase-orders/inventoryItemsTransfers/search-list', 'PurchaseOrderController@inventoryItemsTransfers')->name('purchase-orders.inventoryItemsTransfers');
    Route::get('purchase-orders/inventoryItems/getInventryItemDetails', 'PurchaseOrderController@getInventryItemDetails')->name('purchase-orders.getInventryItemDetails');
    Route::get('purchase-orders/inventoryItems/getInventryItemDetailsExtension', 'PurchaseOrderController@getInventryItemDetailsExtension')->name('purchase-orders.getInventryItemDetailsExtension');

    Route::get('purchase-orders/inventoryItems/getInventryItemDetailsRow', 'PurchaseOrderController@getInventryItemDetailsRow')->name('purchase-orders.getInventryItemDetailsRow');
    
    // Add missing routes for print and PDF export - no middleware
    Route::get('purchase-orders/print', 'PurchaseOrderController@print')->name('purchase-orders.print');
    Route::get('purchase-orders/exportToPdf/{slug}', 'PurchaseOrderController@exportToPdf')->name('purchase-orders.exportToPdf');


    Route::get('purchase-orders/orders', 'PurchaseOrderController@orders')->name('purchase-orders.orders');
    Route::resource('purchase-orders', 'PurchaseOrderController');


    Route::post('receive-purchase-order/{order}/complete', 'ReceivePurchasedOrderController@complete')->name('receive-purchase-orders.complete');
    Route::resource('receive-purchase-order', 'ReceivePurchasedOrderController');
    Route::resource('confirmed-receive-purchase-order', 'ConfirmedReceiveOrderController');
    Route::resource('returned-receive-purchase-order', 'ReturnedReceiveOrderController');
    Route::resource('return-accepted-receive-order', 'ReturnAcceptedReceiveOrderController');
    Route::resource('process-receive-purchase-order', 'ProcessReceiveOrderController');
    Route::resource('weighted-average-history', 'WeightedAverageHistoryController');

    Route::get('print-testing', 'PrintTestController@printTesting')->name('printTesting');
    Route::get('print-testing-form', 'PrintTestController@printform')->name('completed-grn.printform');

    Route::get('completed-grn/{grn}/print-to-pdf', 'CompletedGrnController@printToPdf')->name('completed-grn.printToPdf');
    Route::get('completed-grn/{grn}/print-note', 'CompletedGrnController@printNote')->name('completed-grn.printNote');
    Route::resource('completed-grn', 'CompletedGrnController');


    Route::get('approve-lpo/{purchase_no}/{id}/edit', 'ApproveLpoController@editPurchaseItem')->name('approve-lpo.editPurchaseItem');


    Route::post('approve-lpo/{id}/edit', 'ApproveLpoController@updatePurchaseItem')->name('approve-lpo.updatePurchaseItem');

    Route::get('approve-lpo/delete-item/{purchase_no}/{item_id}', 'ApproveLpoController@deletingItemRelation')->name('approve-lpo.items.delete');
    Route::resource('approve-lpo', 'ApproveLpoController');


    Route::get('purchases-by-store-location', 'PurchaseReportsController@purchasesByStoreLocation')->name('purchases-by-store-location');
    Route::get('purchases-by-family-group', 'PurchaseReportsController@purchasesByFamilyGroup')->name('purchases-by-family-group');
    Route::get('purchases-by-supplier', 'PurchaseReportsController@purchasesBySupplier')->name('purchases-by-supplier');
    Route::get('reports/suggested-order-report', 'ReportsController@suggested_order_report')->name('reports.suggested_order_report');
    Route::get('branch-requisitions/suggested-orders', 'ReportsController@suggested_order_report_for_purchases')->name('branch-requisitions.suggested-orders');
    Route::get('reports/inventory-negetive-report', 'ReportsController@items_negetive_listing')->name('reports.items_negetive_listing');
    Route::get('reports/inventory-location-stock-report', 'ReportsController@inventory_location_stock_summary')->name('reports.inventory_location_stock_summary');

    Route::get('reports/inventory-location-as-at-report', 'ReportsController@inventory_location_as_at')->name('reports.inventory_location_as_at');

    Route::get('reports/items-data-sales-report', 'ReportsController@items_data_sales')->name('reports.items-data-sales');
    Route::get('reports/route-performance-report', 'ReportsController@route_performance_report')->name('reports.route_performance_report');

    Route::get('purchases-status-and-leadtime-report', [LpoStatusAndLeadtimeReportController::class, 'newIndex'])->name('lpo-status-and-leatime-reports');


    Route::post('resolve-requisition-to-lpo/userDetail', 'ResolveRequisitionToLpoController@userDetail')->name('resolve-requisition-to-lpo.get.userDetail');

    Route::resource('resolve-requisition-to-lpo', 'ResolveRequisitionToLpoController');
    Route::get('/resolve-requisition-to-lpo/merge/{lpoId}', [ResolveRequisitionToLpoController::class, 'showAvailableLposMerge'])->name('resolve-requisition-to-lpo.merge');
    Route::post('/merge/lpo', [ResolveRequisitionToLpoController::class, 'mergeLpos'])->name('merge-lpo');
    Route::get('/resolve-requisition-to-lpo/edit-item/{slug}', [ResolveRequisitionToLpoController::class, 'edititem'])->name('resolve-requisition-to-lpo.edititem');
    Route::post('/resolve-requisition-to-lpo/update-item/', [ResolveRequisitionToLpoController::class, 'updateItem'])->name('resolve-requisition.edit-item');


    Route::post('resolve-requisition-to-lpo/items/remove', 'ResolveRequisitionToLpoController@removeItem')->name('resolve-requisition-to-lpo.remove-item');

    Route::post('locations/get-locations-by-brach', 'LocationAndStoreController@getLocationsByBrach')->name('locations.get-location-by_branch');
    Route::post('locations/get-bins-by-location', 'LocationAndStoreController@getBinsByLocation')->name('bins.get-bins-by-location');


    Route::post('maintain-items/manage-stock', 'InventoryItemController@stockManage')->name('maintain-items.manage-stock');
    Route::post('maintain-items/manage-category-price', 'InventoryItemController@manageCategoryPrice')->name('maintain-items.manage-category-price');
    /////////////

    Route::post('maintain-raw-material-items/manage-stock', 'MaintainRawMaterialController@stockManage')->name('maintain-raw-material-items.manage-stock');
    Route::post('maintain-raw-material-items/manage-category-price', 'MaintainRawMaterialController@manageCategoryPrice')->name('maintain-raw-material-items.manage-category-price');

    /////////////
    Route::get('adjust-item-stock-form/{slug}', 'InventoryItemController@adjustItemStockForm')->name('admin.table.adjust-item-stock-form');
    Route::get('adjust-items-manually', 'InventoryItemController@manualManageStock');


    Route::post('exportCategoryPrice', 'InventoryItemController@exportCategoryPrice')->name('admin.table.exportCategoryPrice');
    Route::post('importexcelforitempriceupdate', 'InventoryItemController@importexcelforitempriceupdate')->name('admin.table.importexcelforitempriceupdate');


    Route::get('adjust-category-price-form/{slug}', 'InventoryItemController@adjustCategoryPriceForm')->name('admin.table.adjust-category-price-form');

    Route::post('maintain-items/get-available-quantity-ajax', 'InventoryItemController@getAvailableQuantityAjax')->name('maintain-items.get-available-quantity-ajax');
    Route::post('maintain-items/update-price-per-location/{id}', 'InventoryItemController@updatepricePerLocation')->name('maintain-items.update-price-per-location');

    ////////////////////////

    Route::post('maintain-raw-material-items/get-available-quantity-ajax', 'InventoryItemController@getAvailableQuantityAjax')->name('maintain-raw-material-items.get-available-quantity-ajax');

    ////////////////////////
    Route::get('stock-takes/getCategories', 'StockTakesController@getCategories')->name('admin.stock-takes.getCategories');
    Route::get('stock-takes/create-stock-take-sheet', 'StockTakesController@index')->name('admin.stock-takes.create-stock-take-sheet');
    Route::get('stock-takes/freeze-table', 'StockTakesController@freezeTable')->name('admin.stock-takes.freeze-table');
    Route::post('stock-takes/add-stock-check-file', 'StockTakesController@addStockCheckFile')->name('admin.stock-takes.add-stock-check-file');
    Route::get('stock-takes/print-to-pdf/{id}', 'StockTakesController@printToPdf')->name('admin.stock-takes.print-to-pdf');
    Route::post('stock-takes/print', 'StockTakesController@printPage')->name('admin.stock-takes.print');

    Route::get('stock-counts', 'StockCountsController@index')->name('admin.stock-counts');
    Route::get('stock-counts/mobile-web', 'StockCountsController@mobileWeb')->name('admin.stock-counts.mobile-web');
    Route::get('stock-counts/mobile-web/get-items', 'StockCountsController@getMobileWebItems')->name('admin.stock-counts.mobile-web.get-items');
    Route::post('stock-counts/mobile-web/submit', 'StockCountsController@submitMobileWebCounts')->name('admin.stock-counts.mobile-web.submit');
    Route::delete('stock-counts/destroy/{id}', 'StockCountsController@destroy')->name('admin.stock-counts.destroy');
    Route::get('stock-counts/enter-stock-counts', 'StockCountsController@enterStockCounts')->name('admin.stock-counts.enter-stock-counts');
    Route::post('stock-counts/enter-stock-counts-form-list', 'StockCountsController@stockCountFormListAjax')->name('admin.stock-counts.enter-stock-counts-form-list');
    Route::post('stock-counts/update-stock-row', 'StockCountsController@updateStockRow')->name('admin.stock-counts.update-stock-row');
    Route::post('stock-counts/enter-update-stock-counts', 'StockCountsController@updateStockCounts')->name('admin.stock-counts.enter-update-stock-counts');
    Route::get('stock-counts/stock-count-variance', [StockCountVarianceReportController::class, 'index'])->name('admin.stock-count-variance.index');
    Route::get('stock-counts/stock-count-variance/summary', [StockCountVarianceReportController::class, 'summary'])->name('admin.stock-count-variance.summary');
    Route::get('stock-counts/stock-count-variance/print', [StockCountVarianceReportController::class, 'print'])->name('admin.stock-count-variance.print');


    Route::get('stock-counts/users', [StockTakeUserAssignmentController::class, 'index'])->name('admin.stock-counts-users-assingment');
    Route::get('stock-counts/users/create', [StockTakeUserAssignmentController::class, 'create'])->name('admin.stock-counts-users-assingment.create');
    Route::post('stock-counts/users/store', [StockTakeUserAssignmentController::class, 'store'])->name('admin.stock-counts-users-assingment.store');
    Route::get('stock-counts/users/edit/{id}', [StockTakeUserAssignmentController::class, 'edit'])->name('admin.stock-counts-users-assingment.edit');
    Route::post('stock-counts/users/update/{id}', [StockTakeUserAssignmentController::class, 'update'])->name('admin.stock-counts-users-assingment.update');
    Route::get('stock-count-blocked-users', [StockCountBlockedUsersController::class, 'index'])->name('admin.stock-count-blocked-users.index');
    Route::get('stock-count-blocked-users/unblock-all', [StockCountBlockedUsersController::class, 'unblockAll'])->name('admin.stock-count-blocked-users.unblockAll');
    Route::post('stock-count-blocked-users/unblock-selected', [StockCountBlockedUsersController::class, 'unblockSelected'])->name('admin.stock-count-blocked-users.selected');

    Route::get('stock-count-blocked-users/exemption-schedules', [StockCountBlockedUsersController::class, 'blockUserExemptionSchedules'])->name('admin.stock-count-blocked-users.exemption-schedules');
    Route::POST('stock-count-blocked-users/exemption-schedules', [StockCountBlockedUsersController::class, 'storeExemptionScheduleUsers'])->name('admin.stock-count-blocked-users.exemption-schedules.add-users');
    Route::delete('/stock-count-blocked-users/exemption-schedules/delete-user',  [StockCountBlockedUsersController::class, 'deleteUser'])->name('admin.stock-count-blocked-users.exemption-schedules.delete-user');



    Route::get('stock-counts/user-items-upload', [StockTakeUserAssignmentController::class, 'uploadItemsIndex'])->name('admin.stock-counts.user-items-upload');
    Route::get('stock-counts/batch-upload', [StockTakeUserAssignmentController::class, 'batchUploadItemsIndex'])->name('admin.stock-counts.batch-upload');
    Route::post('stock-counts/user-item-allocations', [StockTakeUserAssignmentController::class, 'uploadUserItemAssignments'])->name('admin.stock-counts.user-item-allocations.upload');
    Route::post('stock-counts/batch-upload/save', [StockTakeUserAssignmentController::class, 'batchUploadUserItemAssignments'])->name('admin.stock-counts.user-item-allocations.batch-upload');
    Route::get('stock-counts/user-item-assignments/all', [StockTakeUserAssignmentController::class, 'displayBinStockAssignments'])->name('admin.stock-count.user-item-assignments.all');
    Route::get('stock-counts/user-item-assignments/delete/{id}', [StockTakeUserAssignmentController::class, 'destroyAllocation'])->name('admin.stock-count.user-item-assignments.destroy');
    Route::get('stock-counts/user-item-assignments/add-allocation', [StockTakeUserAssignmentController::class, 'addAllocation'])->name('admin.stock-count.user-item-assignments.add-allocation');
    Route::post('stock-counts/user-item-assignments/save-allocation', [StockTakeUserAssignmentController::class, 'storeAllocation'])->name('admin.stock-count.user-item-assignments.store-allocation');
    Route::post('stock-counts/user-item-assignments/transfer-allocation', [StockTakeUserAssignmentController::class, 'transferAllocation'])->name('admin.stock-count.user-item-assignments.transfer-allocation');


    Route::get('stock-counts/compare-counts-vs-stock-check', 'StockCountsController@compareCountsVsStockCheck')->name('admin.stock-counts.compare-counts-vs-stock-check');
    Route::post('stock-counts/compare-counts-vs-stock-check-update', 'StockCountsController@compareCountsVsStockCheckUpdate')->name('admin.stock-counts.compare-counts-vs-stock-check-update');
    Route::get('stock-counts/deviation-report', 'StockCountsController@deviationReport')->name('admin.stock-counts.deviation-report');
    Route::get('stock-counts/deviation-report-pdf/{date}', 'StockCountsController@deviationReportPdf')->name('admin.stock-counts.deviation-report-pdf');
    Route::get('stock-counts/deviation-report-ecxel/{date}', 'StockCountsController@deviationReportExcel')->name('admin.stock-counts.deviation-report-excel');

    Route::post('stock-counts/get-category-list-for-store', 'StockCountsController@getCetegoryListForStore')->name('admin.stock-counts.get-category-list-for-store');

    Route::get('stock-counts/stock-count-process', 'StockCountsController@stockcountprocess')->name('admin.stock-counts.stock-count-process');

    Route::get('stock-counts/compare-counts-vs-stock-process/{id}', 'StockCountsController@compareCountsVsStockCheckProcess')->name('admin.stock-counts.compare-counts-vs-stock-process');

    // Opening Balances Stock Take
    Route::get('opening-balances/stock-takes/create-stock-take-sheet', [OpeningBalancesStockTakeController::class, 'index'])->name('admin.opening-balance.stock-takes.create-stock-take-sheet');
    Route::get('opening-balances/stock-takes/freeze-table', [OpeningBalancesStockTakeController::class, 'freezeTable'])->name('admin.opening-balances.stock-takes.freeze-table');
    Route::post('opening-balances/stock-takes/add-stock-check-file', [OpeningBalancesStockTakeController::class, 'addStockCheckFile'])->name('admin.opening-balances.stock-takes.add-stock-check-file');
    Route::get('opening-balances/stock-takes/print-to-pdf/{id}', [OpeningBalancesStockTakeController::class, 'printToPdf'])->name('admin.opening-balances.stock-takes.print-to-pdf');

    Route::get('opening-balances/stock-counts', [OpeningBalancesStockTakeController::class, 'stockCountsIndex'])->name('admin.opening-balances-stock-counts');
    Route::get('opening-balances/stock-counts/enter-stock-counts', [OpeningBalancesStockTakeController::class, 'enterStockCounts'])->name('admin.opening-balances.stock-counts.enter-stock-counts');
    Route::post('opening-balances/stock-counts/enter-update-stock-counts', [OpeningBalancesStockTakeController::class, 'updateStockCounts'])->name('admin.opening-balances.stock-counts.enter-update-stock-counts');
    Route::post('opening-balances/stock-counts/enter-stock-counts-form-list', [OpeningBalancesStockTakeController::class, 'stockCountFormListAjax'])->name('admin.opening-balances.stock-counts.enter-stock-counts-form-list');
    Route::delete('opening-balances/stock-counts/destroy/{id}', [OpeningBalancesStockTakeController::class, 'destroy'])->name('admin.opening-balances.stock-counts.destroy');
    Route::post('opening-balances/stock-counts/update-stock-row', [OpeningBalancesStockTakeController::class, 'updateStockRow'])->name('admin.opening-balances.stock-counts.update-stock-row');
    Route::get('opening-balances/stock-counts/compare-counts-vs-stock-check', [OpeningBalancesStockTakeController::class, 'compareCountsVsStockCheck'])->name('admin.opening-balances.stock-counts.compare-counts-vs-stock-check');
    Route::post('opening-balances/stock-counts/compare-counts-vs-stock-check-update', [OpeningBalancesStockTakeController::class, 'compareCountsVsStockCheckUpdate'])->name('admin.opening-balances.stock-counts.compare-counts-vs-stock-check-update');
    Route::get('opening-balances/stock-counts/stock-count-process', [OpeningBalancesStockTakeController::class, 'stockcountprocess'])->name('admin.opening-balances.stock-counts.stock-count-process');
    Route::get('opening-balances/stock-counts/compare-counts-vs-stock-process/{id}', [OpeningBalancesStockTakeController::class, 'compareCountsVsStockCheckProcess'])->name('admin.opening-balances.stock-counts.compare-counts-vs-stock-process');
    Route::get('opening-balances/stock-counts/deviation-report', [OpeningBalancesStocktakeController::class, 'deviationReport'])->name('admin.opening-balances.stock-counts.deviation-report');
    Route::get('opening-balances/stock-counts/deviation-report-pdf/{date}', [OpeningBalancesStocktakeController::class, 'deviationReportPdf'])->name('admin.opening-balances.stock-counts.deviation-report-pdf');
    Route::get('opening-balances/stock-counts/deviation-report-ecxel/{date}', [OpeningBalancesStocktakeController::class, 'deviationReportExcel'])->name('admin.opening-balances.stock-counts.deviation-report-excel');










    // INTERNAL DEBTORS
    Route::get('stock-debtors', [StockDebtorsController::class, 'index'])->name('stock-debtors.index');
    Route::get('stock-debtors/create', [StockDebtorsController::class, 'create'])->name('stock-debtors.add');
    Route::post('stock-debtors/store', [StockDebtorsController::class, 'store'])->name('stock-debtors.store');
    Route::get('stock-debtors/view/{id}', [StockDebtorsController::class, 'show'])->name('stock-debtors.view');
    Route::get('stock-debtors/balance/{id}', [StockDebtorsController::class, 'get_balance'])->name('stock-debtors.balance');
    Route::post('stock-debtors/split', [StockDebtorsController::class, 'split'])->name('stock-debtors.split');
    Route::get('stock-debtors/split/users/{id}/{bin}', [StockDebtorsController::class, 'split_users'])->name('stock-debtors.split.users');
    Route::get('stock-debtors/split/users/', [StockDebtorsController::class, 'split_users_non_debtors'])->name('stock-debtors.split.users.non_debtors');
    Route::post('stock-debtors/split/non-debtor', [StockDebtorsController::class, 'split_non_debtors'])->name('stock-debtors.split.non_debtor');

    Route::get('stock-non-debtors', [StockDebtorsController::class, 'stock_non_debtors'])->name('stock-non-debtors.index');
    Route::get('stock-non-debtors/view/{id}', [StockDebtorsController::class, 'stock_non_debtor_view'])->name('stock-non-debtors.view');

    Route::get('stock-processing/sales', [StockAdjustmentController::class, 'stock_processing_sales'])->name('stock-processing.sales');
    Route::get('stock-processing/sales/show/{id}', [StockAdjustmentController::class, 'stock_processing_sales_show'])->name('stock-processing.sales.show');
    Route::get('stock-processing/sales/create', [StockAdjustmentController::class, 'stock_processing_sales_add'])->name('stock-processing.sales.add');
    Route::get('stock-processing/sales/edit/{id}', [StockAdjustmentController::class, 'stock_processing_sales_edit'])->name('stock-processing.sales.edit');
    Route::post('stock-processing/sales/store', [StockAdjustmentController::class, 'stock_processing_sales_store'])->name('stock-processing.sales.store');
    Route::get('stock-processing/sales/file/{format}/{id}', [StockAdjustmentController::class, 'stock_processing_sales_file'])->name('stock-processing.sales.file');
    Route::post('stock-processing/sales/resign-esd/{id}', [StockAdjustmentController::class, 'resign_esd'])->name('stock-processing.sales.resign_esd');

    Route::get('stock-processing/return', [StockAdjustmentController::class, 'stock_processing_return'])->name('stock-processing.return');
    Route::get('stock-processing/return/show/{id}', [StockAdjustmentController::class, 'stock_processing_return_show'])->name('stock-processing.return.show');
    Route::get('stock-processing/return/create', [StockAdjustmentController::class, 'stock_processing_return_add'])->name('stock-processing.return.add');
    Route::post('stock-processing/return/store', [StockAdjustmentController::class, 'stock_processing_return_store'])->name('stock-processing.return.store');
    Route::get('stock-processing/return/file/{format}/{id}', [StockAdjustmentController::class, 'stock_processing_return_file'])->name('stock-processing.return.file');
    Route::post('stock-processing/return/resign-esd-return/{id}', [StockAdjustmentController::class, 'resign_esd_return'])->name('stock-processing.return.resign_esd');

    Route::get('stock-dates/{id}', [StockAdjustmentController::class, 'get_stock_dates'])->name('stock-dates');
    Route::post('stock-date-data', [StockAdjustmentController::class, 'get_stock_date_data'])->name('stock-dates-data');

    Route::get('stock-pending-entries', [StockPendingEntriesController::class, 'index'])->name('stock-pending-entries.index');
    Route::post('stock-pending-entries/restore', [StockPendingEntriesController::class, 'restore'])->name('stock-pending-entries.restore');
    Route::post('stock-pending-entries/expunge', [StockPendingEntriesController::class, 'expunge'])->name('stock-pending-entries.expunge');

    Route::get('stock-expunged-entries', [StockPendingEntriesController::class, 'expunged_entries'])->name('stock-expunged-entries.index');

    Route::get('stock-uncompleted-sales', [StockUncompletedProcessingController::class, 'stock_uncompleted_sales'])->name('stock-uncompleted-sales.index');
    Route::get('stock-uncompleted-sales/show/{id}', [StockUncompletedProcessingController::class, 'stock_uncompleted_sales_show'])->name('stock-uncompleted-sales.show');
    Route::post('stock-uncompleted/process/', [StockUncompletedProcessingController::class, 'process'])->name('stock-uncompleted.process');

    Route::get('inventory-reports/inventory-moment-reports', 'InventoryReportController@inventoryMomentReport')->name('inventory-reports.inventory-moment-reports');
    Route::get('inventory-reports/delivery-note-reports', 'InventoryReportController@getDeliveryNoteReport')->name('inventory-reports.delivery-note-reports');

    Route::get('inventory-reports/grn-reports', 'InventoryReportController@grnReports')->name('inventory-reports.grn-reports');
    Route::get('inventory-reports/supplier-product-reports', 'InventoryReportController@supplierProductReports')->name('inventory-reports.supplier-product-reports');

    Route::get('inventory-reports/out-of-stock', 'OutOfStockReportController@index')->name('inventory-reports.out-of-stock-report');

    // Supplier Product Reports 2
    Route::get('inventory-reports/supplier-product-reports2', 'InventoryReportController@supplierProductReports2')->name('inventory-reports.supplier-product-reports2');

    // End


    Route::get('inventory-reports/export-transfer-general', 'InventoryReportController@exportTransferGeneral')->name('inventory-reports.export-transfer-general');
    Route::post('inventory-reports/export-transfer-general', 'InventoryReportController@exportTransferGeneral')->name('inventory-reports.export-transfer-general');

    Route::get('inventory-reports/export-internal-requisitions', 'InventoryReportController@exportInternalRequisitions')->name('inventory-reports.export-internal-requisitions');
    Route::post('inventory-reports/export-internal-requisitions', 'InventoryReportController@exportInternalRequisitions')->name('inventory-reports.export-internal-requisitions');
    Route::get('inventory-reports/location-wise-movement', 'InventoryReportController@locationWiseMovement')->name('inventory-reports.location-wise-movement');
    Route::post('inventory-reports/location-wise-movement', 'InventoryReportController@locationWiseMovement')->name('inventory-reports.location-wise-movement');

    Route::get('inventory-reports/grn-summary', 'InventoryReportController@grnSummary')->name('inventory-reports.grn-summary');
    Route::post('inventory-reports/grn-summary', 'InventoryReportController@grnSummary')->name('inventory-reports.grn-summary');


    Route::get('inventory-reports/inventory-valuation-report', 'InventoryReportController@inventoryValuationReport')->name('inventory-reports.inventory-valuation-report');
    Route::post('inventory-reports/inventory-valuation-report', 'InventoryReportController@inventoryValuationReport')->name('inventory-reports.inventory-valuation-report');

    Route::get('inventory-reports/max-stock-report', 'MaxStockReportController@index')->name('inventory-reports.max-stock-report.index');
    Route::get('inventory-reports/average-sales-report', 'AverageSalesReportController@index')->name('inventory-reports.average-sales-report.index');
    Route::get('inventory-reports/missing-items-report', 'MissingItemsReportController@index')->name('inventory-reports.missing-items-report.index');
    Route::get('inventory-reports/reorder-items-report', 'ReorderItemsReportController@index')->name('inventory-reports.reorder-items-report.index');
    Route::get('inventory-reports/slow-moving-items-report', 'SlowMovingItemsReportController@index')->name('inventory-reports.slow-moving-items-report.index');
    Route::get('inventory-reports/grn-summary-by-supplier-report', 'GrnSummaryBySupplierReportController@index')->name('inventory-reports.grn-summary-by-supplier-report.index');

    //    Route::get('inventory-reports/suggested-order-report', 'InventoryReportController@suggestedOrderReport')->name('inventory-reports.suggested-order-report');
    //    Route::post('inventory-reports/suggested-order-report', 'InventoryReportController@suggestedOrderReport')->name('inventory-reports.suggested-order-report');


    ///////////////////////// Gross Profit Report

    Route::get('gross-profit/inventory-valuation-report', 'GrossProfitReportController@inventoryValuationReport')->name('gross-profit.inventory-valuation-report');
    Route::post('gross-profit/inventory-valuation-report', 'GrossProfitReportController@inventoryValuationReport')->name('gross-profit.inventory-valuation-report');

    /*Detailed Gross Profit Report*/
    Route::get('gross-profit/inventory-valuation-detailed-report', 'GrossProfitReportController@inventoryValuationDetailedReport')->name('gross-profit.inventory-valuation-detailed-report');
    Route::post('gross-profit/inventory-valuation-detailed-report', 'GrossProfitReportController@inventoryValuationDetailedReport')->name('gross-profit.inventory-valuation-detailed-report');
    Route::get('gross-profit/route-profitibility-report', 'GrossProfitReportController@routeProfitibilityReport')->name('gross-profit.route-profitibility-report');
    Route::post('gross-profit/route-profitibility-report', 'GrossProfitReportController@routeProfitibilityReport')->name('gross-profit.route-profitibility-report');
    Route::get('gross-profit/get-salesman-shifts', 'GrossProfitReportController@getSalesmanShifts')->name('gross-profit.get-salesman-shifts');

    // Delivery Driver Routes
Route::group(['prefix' => 'delivery-driver', 'as' => 'delivery-driver.', 'middleware' => 'delivery-driver'], function () {
    Route::get('dashboard', 'DeliveryDriverController@dashboard')->name('dashboard');
    Route::get('mobile', 'DeliveryDriverController@mobileApp')->name('mobile');
    Route::get('schedule/{id}', 'DeliveryDriverController@showSchedule')->name('schedule.show');
    Route::post('schedule/{id}/start', 'DeliveryDriverController@startDelivery')->name('schedule.start');
    Route::post('schedule/{id}/complete', 'DeliveryDriverController@completeSchedule')->name('schedule.complete');
    Route::post('receive-items', 'DeliveryDriverController@receiveItems')->name('receive-items');
    Route::get('get-customer-items', 'DeliveryDriverController@getCustomerItems')->name('get-customer-items');
    Route::post('prompt-delivery', 'DeliveryDriverController@promptDeliveryCompletion')->name('prompt-delivery');
    Route::post('verify-code', 'DeliveryDriverController@verifyDeliveryCode')->name('verify-code');
    Route::get('get-delivery-codes', 'DeliveryDriverController@getDeliveryCodes')->name('get-delivery-codes');
    Route::post('complete-delivery-direct', 'DeliveryDriverController@completeDeliveryDirect')->name('complete-delivery-direct');
    Route::get('get-payment-methods', 'DeliveryDriverController@getPaymentMethods')->name('get-payment-methods');
    Route::post('record-payment', 'DeliveryDriverController@recordPayment')->name('record-payment');
    Route::get('get-return-reasons', 'DeliveryDriverController@getReturnReasons')->name('get-return-reasons');
    Route::post('process-returns', 'DeliveryDriverController@processReturns')->name('process-returns');
    Route::get('history', 'DeliveryDriverController@history')->name('history');
});


    Route::get('recipes/reports-summary', 'RecipesController@recipesSummary')->name('admin.recipes.report_summary');
    Route::resource('recipes', 'RecipesController');
    Route::post('recipes/add-new-recipe-ajax', 'RecipesController@addOrUpdate')->name('admin.recipes.add');
    Route::post('recipes/edit-recipe-from-ajax', 'RecipesController@editRecipeFormAjax')->name('admin.recipes.editForm');
    Route::post('recipes/recipe-ingredient-save/{slug}', 'RecipesController@recipeIngredientSave')->name('admin.recipes.recipe-ingredient-save');
    Route::delete('recipes/recipe-ingredient-delete/{id}', 'RecipesController@recipeIngredientDelete')->name('admin.recipes.recipe-ingredient-delete');
    Route::get('recipes/recipe-ingredient-edit/{id}', 'RecipesController@recipeIngredientEdit')->name('admin.recipes.recipe-ingredient-edit');
    Route::post('recipes/recipe-ingredient-update/{id}', 'RecipesController@recipeIngredientUpdate')->name('admin.recipes.recipe-ingredient-update');
    Route::get('import-inventory-category', 'ImportsController@importInventoryCategory')->name('import-inventory-category');
    Route::get('import-inventory-items', 'ImportsController@importInventoryItems')->name('import-inventory-items');
    Route::get('import-recipe', 'ImportsController@importRecipe')->name('import-recipe');
    Route::get('import-recipe-ingredient', 'ImportsController@importRecipeIngredient')->name('import-recipe-ingredient');
    Route::get('import-suppliers', 'ImportsController@importSuppliers')->name('import-suppliers');

    Route::post('purchase-orders/view-last-purchases-price', 'PurchaseOrderController@viewLastPurchasesPrice')->name('admin.purchase-orders.view-last-purchases-price');
    Route::get('sales/sales-deductions', 'SalesController@salesDeductions')->name('sales.sales-deductions');


    Route::get('sales/sales-with-less-quantity', 'SalesController@salesWithLessQuantity')->name('sales.sales-with-less-quantity');


    Route::get('sales/sales-with-no-recipe-link', 'SalesController@salesWithNoRecipeLink')->name('sales.sales-with-no-recipe-link');
    Route::get('maintain-items/stock-movements/gl-entries/{stockMoveId}/{stockIdCode}', 'InventoryItemController@stockMovementGlEntries')->name('maintain-items.stock-movements.gl-entries');
    Route::get('general-ledgers/gl-entries', 'GeneralLedgersController@glEntries')->name('general-ledgers.gl-entries');
    Route::get('general-ledger/transaction-summary', [TransactionSummaryReportController::class, 'gl_transaction_summary'])->name('general-ledger.gl_transaction_summary');
    Route::get('general-ledger/monthly-profit-and-loss', [ProfitAndLossMonthlyReportController::class, 'monthlyProfitSummary'])->name('profit-and-loss.monthlyProfitSummary');
    Route::post('general-ledger/monthly-profit-and-loss', [ProfitAndLossMonthlyReportController::class, 'monthlyProfitSummary'])->name('profit-and-loss.monthlyProfitSummary');
    //Account Inquiry.
    Route::get('gl-journal-inquiry', [WaGLJournalInquiryController::class, 'index'])->name('admin.journal-inquiry.index');
    Route::get('gl-journal-inquiry/search', [WaGLJournalInquiryController::class, 'search'])->name('admin.journal-inquiry.search');


    Route::get('sales-booking-to-gl/daily-sales', 'SalesBookingToGlController@dailySales')->name('sales-booking-to-gl.daily-sales');
    Route::get('sales-booking-to-gl/daily-payment', 'SalesBookingToGlController@dailyPayments')->name('sales-booking-to-gl.daily-payment');
    Route::get('sales-booking-to-gl/posted-sales', 'SalesBookingToGlController@postedSales')->name('sales-booking-to-gl.posted-sales');

    Route::get('sales-booking-to-gl/post-sales-to-general-ledger', 'SalesBookingToGlController@postSalesToGeneralLedger')->name('sales-booking-to-gl.post-sales-to-general-ledger');


    Route::resource('sales-booking-to-gl', 'SalesBookingToGlController');
    Route::get('trial-balances/sheet', 'TrailBalanceController@index')->name('trial-balances.index');
    Route::get('trial-balances/detailed', [DetailedTrialBalanceController::class, 'index'])->name('trial-balances.detailed');
    Route::get('trial-balances/detailed-sheet/account-details', 'TrailBalanceController@accountPayablesDetails')->name('trial-balances.accountPayablesDetails');
    Route::get('trial-balances/account/{account}', 'TrailBalanceController@account_data')->name('trial-balance.account');
    Route::get('trial-balances/account/search/{account}', 'TrailBalanceController@account_data_search')->name('trial-balance.account.search');
    Route::get('trial-balances/account/export/{account}', 'TrailBalanceController@export_data')->name('trial-balance.account.excel');
    Route::get('trial-balances/account/group-transaction/{account}', 'TrailBalanceController@export_group_transaction')->name('trial-balance.account.group-transaction');


    Route::get('customer-aging-analysis/sheet', 'CustomerAgingAnalysisController@index')->name('customer-aging-analysis.index');
    Route::get('report/vat-report', 'CustomerAgingAnalysisController@newvatreport')->name('customer-aging-analysis.vatReport');
    Route::get('report/esd-vat-report', 'CustomerAgingAnalysisController@esdVatReport')->name('customer-aging-analysis.esdVatReport');

    Route::get('report/vat-report-2', 'CustomerAgingAnalysis2Controller@vatReport')->name('customer-aging-analysis.vatReport2');


    Route::get('supplier-aging-analysis/sheet', 'SupplierAgingAnalysisController@index')->name('supplier-aging-analysis.index');

    Route::get('vat-report/sheet', 'VatReportController@index')->name('vat-report.index');

    Route::get('vat-report/processed-vat', 'VatReportController@vatreport')->name('vat-report.processed.index');

    Route::get('supplier-listing/sheet', 'SupplierListingController@index')->name('supplier-listing.index');
    Route::get('supplier-bank-listing/sheet', 'SupplierBankListingController@index')->name('supplier-bank-listing.index');
    Route::post('supplier-bank-listing/update', 'SupplierBankListingController@update')->name('supplier-bank-listing.update');

    Route::get('journal-entries/{slug}/process', 'JournalEntryController@process')->name('journal-entries.process');
    Route::get('journal-entries/{slug}/{item}/delete', 'JournalEntryController@deleteItem')->name('journal-entries.deleteItem');


    Route::get('journal-entries/get-account-no-list', 'JournalEntryController@getAccountNo')->name('journal-entries.getAccountNo');
    Route::get('journal-entries/processed-index', 'JournalEntryController@processed_index')->name('journal-entries.processed_index');

    Route::resource('journal-entries', 'JournalEntryController');


    Route::post('credit-note/getCustomer-detail', 'CreditNoteController@getCustomerDetail')->name('credit-note.get.customer-detail');
    Route::post('credit-note/addMore/{slug}', 'CreditNoteController@addMore')->name('credit-note.addMore');
    Route::post('credit-note/process/{slug}', 'CreditNoteController@process')->name('credit-note.process');
    Route::post('credit-note/getItems', 'CreditNoteController@getItems')->name('credit-note.items');
    Route::post('credit-note/getItems-detail', 'CreditNoteController@getItemDetail')->name('credit-note.items.detail');
    Route::post('credit-note/print', 'CreditNoteController@print')->name('credit-note.print');
    Route::get('credit-note/pdf/{slug}', 'CreditNoteController@exportToPdf')->name('credit-note.exportToPdf');
    Route::resource('credit-note', 'CreditNoteController');


    Route::get('inventory-item-adjustment/location_list', 'InventoryItemAdjustmentController@location_list')->name('inventory-item-adjustment.location_list');
    Route::get('inventory-item-adjustment/inventoryItems/search-list', 'InventoryItemAdjustmentController@inventoryItems')->name('inventory-item-adjustment.inventoryItems');
    Route::get('inventory-item-adjustment/inventoryItems/getInventryItemDetails', 'InventoryItemAdjustmentController@getInventryItemDetails')->name('inventory-item-adjustment.getInventryItemDetails');
    Route::resource('inventory-item-adjustment', 'InventoryItemAdjustmentController');


    // UTILITY ROUTES START
    Route::get('max-stock-reorder-level', [UtilityController::class, 'index'])->name('admin.utility.update-max-stock-and-reorder-level');
    Route::post('update-max-stock-reorder-level', [UtilityController::class, 'update'])->name('update-max-stock-reorder-level');
    Route::post('update-max-stock-reorder-level-data', [UtilityController::class, 'updateData'])->name('update-max-stock-reorder-level-data');
    Route::post('update-max-stock-reorder-level-main', [UtilityController::class, 'updateMainData'])->name('update-max-stock-reorder-level-main');
    Route::get('utility/supplier-user-management', [UtilityController::class, 'supplierUserManagement'])->name('utility.supplier_user_management');
    Route::get('utility/supplier-user-management-edit/{id}', [UtilityController::class, 'supplierUserManagementEdit'])->name('utility.supplier_user_management_edit');
    Route::get('utility/supplier-user-management-download/{id}', [UtilityController::class, 'supplierUserManagementDownload'])->name('utility.supplier_user_management_download');
    Route::post('utility/supplier-user-management-update', [UtilityController::class, 'supplierUserManagementUpdate'])->name('utility.supplier_user_management_update');

    Route::get('utility/items-without-suppliers', [ItemsWithoutSupplierController::class, 'index'])->name('utility.items_without_suppliers');
    Route::post('utility/download-items-without-suppliers', [ItemsWithoutSupplierController::class, 'downloadItemsWithoutSuppliers'])->name('utility.download_items_without_suppliers');

    Route::get('utility/stock-qoh', [UpdateStockQohController::class, 'index'])->name('utility.stock_qoh');
    Route::post('utility/update-stock-qoh', [UpdateStockQohController::class, 'updateStocks'])->name('utility.update_stock_qoh');

    Route::get('utility/item-has-count-utility', [ItemHasCountController::class, 'index'])->name('utility.item_has_count_utility');
    Route::post('utility/process-has-count', [ItemHasCountController::class, 'processItemHasCount'])->name('utility.process_item_has_count');

    Route::post('utility/generate-sample-excel', [UtilityController::class, 'generateSampleExcel'])->name('utility.generate_sample_excel');
    Route::get('utility/verify-stocks', [VerifyStocksController::class, 'index'])->name('utility.verify_stocks');
    Route::post('utility/process-verify-stocks', [VerifyStocksController::class, 'processVerifyStocks'])->name('process_verify_stocks');

    Route::get('utility/download-stocks', [DownloadStocksController::class, 'index'])->name('utility.download_stocks');
    Route::post('utility/process-download-stocks', [DownloadStocksController::class, 'processDownloadStocks'])->name('process_download_stocks');

    Route::get('utility/update-item-stock-code', [UpdateItemStockCodeController::class, 'index'])->name('utility.update_item_code');
    Route::post('utility/process-item-stock-code', [UpdateItemStockCodeController::class, 'processItemStockCode'])->name('utility.process_update_item_code');
    Route::post('utility/process-single-item-stock-code', [UpdateItemStockCodeController::class, 'processSingleItemStockCode'])->name('utility.single_update_item_code');

    Route::get('utility/update-bin', [UpdateBinLocationController::class, 'index'])->name('utility.update_bin');
    Route::post('utility/update-bin-location-excel', [UpdateBinLocationController::class, 'updateBinLocation'])->name('utility.update_bin_location_excel');
    Route::post('utility/approve-update-bin-location', [UpdateBinLocationController::class, 'approveUpdateBinLocation'])->name('utility.approve_update_bin_location');
    Route::post('utility/update-item-bin-location', [UpdateBinLocationController::class, 'updateItemBinLocation'])->name('utility.update_item_bin_location');

    Route::post('utility/supplier-user/download-users-suppliers-documents', [UtilityController::class, 'downloadUsersSuppliersDocuments'])->name('utility.download_users_suppliers_documents');
    Route::delete('utility/supplier-user/{userId}/supplier/{supplierId}', [UtilityController::class, 'supplierUserManagementDelete'])->name('utility.supplier_user_management_delete');

    Route::get('branch-utility', [BranchUtilityController::class, 'index'])->name('utility.branch_utilities');
    Route::post('download-branch-utility', [BranchUtilityController::class, 'downloadExcels'])->name('utility.download_branch_utilities');
    Route::get('upload-new-items', [UploadNewItemsController::class, 'index'])->name('utility.upload_new_items');
    Route::post('process-upload-new-items', [UploadNewItemsController::class, 'uploadNewItems'])->name('process_upload_new_items');

    Route::get('selling-price-utility', [UpdateItemPriceController::class, 'index'])->name('utility.update_item_prices');
    Route::post('update-selling-price-function', [UpdateItemPriceController::class, 'updateItemPrices'])->name('utility.update_item_prices_function');

    Route::get('standard-cost-utility', [UpdateStandardCostController::class, 'index'])->name('utility.update_item_standard_cost');
    Route::post('update-standard-cost-function', [UpdateStandardCostController::class, 'updateItemStandardCost'])->name('utility.update_item_standard_cost_function');

    Route::get('item-selling-pricestandard-cost-utility', [UpdateItemSellingPriceStandardCostPerBranch::class, 'index'])->name('utility.update_item_selling_price_standard_cost');
    Route::post('update-item-selling-pricestandard-cost-function', [UpdateItemSellingPriceStandardCostPerBranch::class, 'updateItemSellingPriceStandardCost'])->name('utility.update_item_selling_price_standard_cost_function');

    Route::get('item-margin', [ItemMarginProcessingController::class, 'index'])->name('utility.item_margins');
    Route::post('download-item-margin', [ItemMarginProcessingController::class, 'downloadItemMargins'])->name('utility.download_item_margins');

    Route::get('retired-items', [RetireItemController::class, 'retired_items'])->name('admin.utility.retired.items');
    Route::get('download-retired-items', [RetireItemController::class, 'downloadInvetoryRetiredItems'])->name('admin.utility.download_retired_items');
    Route::get('batch-retire-items', [RetireItemController::class, 'batch_retire_items'])->name('admin.utility.batch.retire.items');
    Route::post('batch-retire-items', [RetireItemController::class, 'batch_retire_items_upload'])->name('admin.utility.batch.retire.items.upload');
    Route::post('batch-retire-items/store', [RetireItemController::class, 'batch_retire_items_store'])->name('admin.utility.batch.retire.items.store');
    Route::get('recalculate-qoh', [RecalculateQohController::class, 'recalculateQoh'])->name('admin.utility.recalculate-qoh');
    Route::get('process-item-stock-moves-data/{stockidcode}/{locationid}', [RecalculateQohController::class, 'processItemStockMovesData'])->name('process-item-stock-moves-data');
    Route::post('recalculate-new-qoh-data/{stockidcode}', [RecalculateQohController::class, 'recalculateNewQoh'])->name('recalculate-new-qoh-data');

    Route::get('utilities/grn-update', [GrnUpdateUtilityController::class, 'edit'])->name('utilities.grn-update');

    // End of Day Utility Start
    Route::get('get-end-of-day-veiw', [EndOfDayUtilityController::class, 'index'])->name('end_of_day_utility.index');
    Route::get('get-end-of-day-process', [EndOfDayUtilityController::class, 'loadEndOfDayPage'])->name('end_of_day_process.process');
    Route::post('process-branch-details', [EndOfDayUtilityController::class, 'processBranchData'])->name('process-branch-details');
    Route::post('process-branch-accounts-details', [EndOfDayUtilityController::class, 'processBranchAccountsDetails'])->name('process-branch-accounts-details');
    Route::post('process-inter-branch-transfer-details', [EndOfDayUtilityController::class, 'processPendingInterBranchTransferDetails'])->name('process-inter-branch-transfer-details');
    Route::post('process-incomplete-transactions-branch-details', [EndOfDayUtilityController::class, 'processIncompleteBranchTransactionsDetails'])->name('process-incomplete-transactions-branch-details');
    Route::post('process-sales-vs-stock-movement', [EndOfDayUtilityController::class, 'processSalesVsStockMovement'])->name('process-sales-vs-stock-movement');
    Route::post('close-branch', [EndOfDayUtilityController::class, 'closeBranch'])->name('close-branch');
    // End of Day Utility End

    // Utility Logs Start

    Route::get('inventory-utility-logs', [InventoryUtilityLogsController::class, 'index'])->name('utility.inventory-utility-logs.index');

    // Utility Logs End

    // Route Splitting Utility Start

    Route::get('route-split', [RouteSplittingController::class, 'index'])->name('route-split.index');
    Route::post('process-route-split', [RouteSplittingController::class, 'processRouteSplitting'])->name('route-split.process');

    // Route Splitting Utility End

    // Assign Account to User Start

    Route::get('assign-account-view', [AssignAccountUserController::class, 'assignAccountView'])->name('assign_account_view.index');
    Route::post('create-account-user', [AssignAccountUserController::class, 'createAccountsAndUsers'])->name('create_account_user');
    Route::post('update-account-user/{userid}', [AssignAccountUserController::class, 'updateAccountsAndUsers'])->name('update_account_user');

    // Assign Account to User End

    Route::resource('transactions-without-branches', TransactionsWithoutBranchesController::class);
    Route::resource('transactions-without-account', TransactionsWithoutAccountController::class);

    Route::resource('update-customer-to-gl', UpdateGLCustomerController::class);
    Route::post('update-customer-to-gl/process', [UpdateGLCustomerController::class, 'process'])->name('update-customer-to-gl.process');

    // UTILITY ROUTES END
    Route::resource('supplier-vehicle-type', SupplierVehicleTypeController::class);
    // REQUEST SKU ROUTES START

    Route::GET('/trade-agreement/request-new-sku/list', [RequestNewSkuController::class, 'index'])->name('request-new-sku.index');
    Route::POST('/trade-agreement/request-new-sku/approve', [RequestNewSkuController::class, 'approve'])->name('request-new-sku.approve');
    Route::POST('/trade-agreement/request-new-sku/reject', [RequestNewSkuController::class, 'reject'])->name('request-new-sku.reject');

    // REQUEST SKU ROUTES END

    Route::get('report/transfers-inwards-report', [ReportsController::class, 'transferInwardsReport'])->name('reports.transfer_inwards_report');
    Route::get('report/transfer-inward-download/{transfer_no}', [ReportsController::class, 'transferInwardDownload'])->name('transfer_inward_download');

    Route::get('approve-bank-deposits', [WalletSupplierDocumentProcessController::class, 'index'])->name('supplier_bank_deposits_initial_approval.index');
    Route::post('update-wallet-slip-status', [WalletSupplierDocumentProcessController::class, 'updateWalletSlipStatus'])->name('update_wallet_slip_status.update');

    Route::get('approve-bank-deposits-final', [WalletSupplierDocumentProcessController::class, 'index_final'])->name('supplier_bank_deposits_final_approval.index');
    Route::post('update-wallet-slip-status-final', [WalletSupplierDocumentProcessController::class, 'updateWalletSlipStatusFinal'])->name('update_wallet_slip_status_final.update');

    Route::get('billings-submitted', [SupplierBillingController::class, 'billing_submitted_index'])->name('billings_submitted');
    Route::post('update-billing-slip-status', [SupplierBillingController::class, 'updateBillingSlipStatusInitial'])->name('update_billing_slip_status.update');

    Route::get('billings-submitted-final', [SupplierBillingController::class, 'billing_submitted_index_final'])->name('billings_submitted_final');
    Route::post('update-billing-slip-status-final', [SupplierBillingController::class, 'updateBillinglipStatusFinal'])->name('update_billing_slip_status_final.update');

    // billings_submitted_final

    // GROUP PERFOMANCE REPORT START
    Route::get('/sales-and-receivables/reports/group-performance-report', [GroupPerfomanceReportController::class, 'index'])
        ->name('sales-and-receivables-reports.group-performance-report');
    // GROUP PERFOMANCE REPORT END

    // GROUP PERFOMANCE REPORT START
    Route::get('/sales-and-receivables/reports/group-filter-route-item-report', [GroupRouteItemReportDataController::class, 'index'])
        ->name('sales-and-receivables-reports.group-filter-route-item-report');

    Route::get('/sales-and-receivables/reports/group-filter-route-item-unment-report', [GroupRouteItemReportDataController::class, 'getUnmetShops2'])
        ->name('sales-and-receivables-reports.group-filter-route-item-unment-report');

    Route::get('/sales-and-receivables/reports/group-data-filter-route-item-report', [GroupRouteItemReportDataController::class, 'getGroupedItems'])
        ->name('sales-and-receivables-reports.group-data-filter-route-item-report');
    // GROUP PERFOMANCE REPORT END

    // OVERSTOCK REPORT START
    Route::get('inventory-reports/overstock-report', [OverStockReportController::class, 'index'])->name('inventory-reports.overstock-report.index');
    // OVERSTOCK REPORT END


    /* REPORTS ROUTES START */

    // Purchases reports start
    Route::get('purchases-reports', [PurchasesReportsController::class, 'index'])->name('purchases-reports.index');
    Route::post('create-purchases-reports-category', [PurchasesReportsController::class, 'createPurchasesReportsCategory'])->name('create-purchaes-reports-category');
    Route::post('create-purchases-reports', [PurchasesReportsController::class, 'createPurchasesReports'])->name('create-purchases-reports');
    Route::post('update-purchaes-reports-position', [PurchasesReportsController::class, 'updatePurchasesReportsPosition'])->name('update-purchaes-reports-position');
    Route::post('update-purchaes-reports', [PurchasesReportsController::class, 'updatePurchasesReports'])->name('update-purchaes-reports');
    Route::delete('delete-report-details', [PurchasesReportsController::class, 'deletePurchasesReports'])->name('delete-report-details');
    // Purchases reports end


    /* REPORTS ROUTES END */


    Route::get('inventory-reports/inactive-stock-report', [InactiveStockReportController::class, 'index'])->name('inventory-reports.inactive-stock-report.index');

    //daed stock
    Route::get('inventory-reports/dead-stock-report', [DeadStockReportController::class, 'index'])->name('inventory-reports.dead-stock-report.index');

    //
    Route::get('sales-and-receivables-reports/unassigned-invoices', [UnassignedInvoiceController::class, 'index'])->name('sales-and-receivables-reports.unassigned_invoices');
    Route::get('/sales-and-receivables-reports.filter-unassigned-invoices-items', [UnassignedInvoiceController::class, 'getRelatedItems'])
        ->name('sales-and-receivables-reports.filter-unassigned-invoices-items');


    //Expenses
    Route::prefix('expense')->name('expense.')->group(function () {
        Route::get('/', 'WaExpenseController@list')->name('list');
        Route::get('/departments', 'WaExpenseController@departments')->name('departments');
        Route::get('/new', 'WaExpenseController@new')->name('new');
        Route::POST('/pdf_report', 'WaExpenseController@report_download')->name('pdf');
        Route::get('/payee_list', 'WaExpenseController@payee_list')->name('payee_list');
        Route::get('/paymentAccount', 'WaExpenseController@paymentAccount')->name('paymentAccount');
        Route::get('/payment_method', 'WaExpenseController@payment_method')->name('payment_method');
        Route::get('/category_list', 'WaExpenseController@category_list')->name('category_list');
        Route::get('/vat_list', 'WaExpenseController@vat_list')->name('vat_list');
        Route::get('/branches', 'WaExpenseController@branches')->name('branches');
        Route::get('/vat_find', 'WaExpenseController@vat_find')->name('vat_find');
        Route::POST('/new-expense', 'WaExpenseController@store')->name('store');
        Route::get('/edit/{id}', 'WaExpenseController@edit')->name('edit');
        Route::get('/show/{id}', 'WaExpenseController@show')->name('show');
        Route::POST('/update', 'WaExpenseController@update')->name('update');
        Route::POST('/processExpense', 'WaExpenseController@processExpense')->name('processExpense');
    });
    Route::prefix('bills')->name('bills.')->group(function () {
        Route::get('/', 'WaBillsController@list')->name('list');
        Route::get('/new', 'WaBillsController@new')->name('new');
        Route::POST('/pdf_report', 'WaBillsController@report_download')->name('pdf');
        Route::POST('/new-bill', 'WaBillsController@store')->name('store');
        Route::get('/edit/{id}', 'WaBillsController@edit')->name('edit');
        Route::POST('/update', 'WaBillsController@update')->name('update');
        Route::POST('/process', 'WaBillsController@process')->name('process');
        Route::get('/payment_terms', 'WaBillsController@payment_terms')->name('wa_payment_terms');
        Route::get('/payment_terms_find', 'WaBillsController@payment_terms_find')->name('payment_terms_find');

        Route::get('/bill_payment/{id}', 'WaBillsController@bill_payment')->name('bill_payment');
        Route::POST('/bill_payment/{id}', 'WaBillsController@bill_payment_process')->name('bill_payment_process');
        Route::get('/bill_payment_list/{id}', 'WaBillsController@bill_payment_list')->name('bill_payment_list');
    });

    Route::prefix('cheques')->name('cheques.')->group(function () {
        Route::get('/', 'WaChequeController@list')->name('list');
        Route::get('/new', 'WaChequeController@new')->name('new');
        Route::POST('/new-cheque', 'WaChequeController@store')->name('store');
        Route::get('/bank-account', 'WaChequeController@bank_accounts')->name('bank_accounts');
        Route::get('/edit/{id}', 'WaChequeController@edit')->name('edit');
        Route::get('/show/{id}', 'WaChequeController@show')->name('show');
        Route::POST('/update-cheque/{id}', 'WaChequeController@update')->name('update');
        Route::POST('/process-cheque/{id}', 'WaChequeController@processCheque')->name('processCheque');
        Route::POST('/report_download', 'WaChequeController@report_download')->name('pdf');
    });
    Route::prefix('banking')->name('banking.')->group(function () {
        Route::get('/transfer', 'WaBankingController@transferList')->name('transfer.list');
        Route::get('/transfer/new', 'WaBankingController@transferNew')->name('transfer.new');
        Route::POST('/transfer/new', 'WaBankingController@transferStore')->name('transfer.store');
        Route::POST('/transfer/report_download', 'WaBankingController@transfer_report_download')->name('transfer.pdf');

        Route::POST('/transfer-bank-fetch', 'WaBankingController@transferAccountGet')->name('transfer.fetch');


        Route::get('/deposit', 'WaBankingController@depositeList')->name('deposite.list');
        Route::get('/deposit/new', 'WaBankingController@depositeNew')->name('deposite.new');
        Route::POST('/deposit/new', 'WaBankingController@deposit_save')->name('deposite.save');
        Route::get('/deposit/edit/{id}', 'WaBankingController@deposit_edit')->name('deposite.edit');
        Route::get('/deposit/show/{id}', 'WaBankingController@deposit_show')->name('deposite.show');
        Route::POST('/deposit/update/{id}', 'WaBankingController@deposit_update')->name('deposite.update');
        Route::POST('/deposit/process/{id}', 'WaBankingController@deposit_process')->name('deposite.process');
        Route::POST('/deposit/report_download', 'WaBankingController@deposite_report_download')->name('deposite.pdf');

        Route::get('/reconcile-daily-transactions', [WaBankingController::class, 'reconcile_daily_transactions'])->name('reconcile.daily.transactions');
        Route::post('reconcile-daily-transactions-datatable', [WaBankingController::class, 'reconcile_daily_transactions_datatable'])->name('reconcile.daily.transactions.datatable');
        Route::post('/reconcile-daily-transactions-save', [WaBankingController::class, 'reconcile_daily_transactions_store'])->name('reconcile.daily.transactions.store');
        Route::post('/reconcile-daily-transactions-upload', [WaBankingController::class, 'reconcile_daily_transactions_upload'])->name('reconcile.daily.transactions.upload');
        Route::post('/reconcile-daily-transactions-approve', [WaBankingController::class, 'reconcile_daily_transactions_approve'])->name('reconcile.daily.transactions.approve');
        Route::post('/reconcile-daily-transactions-download', [WaBankingController::class, 'reconcile_daily_transactions_download'])->name('reconcile.daily.transactions.download');
    });
    /** Update to all instance */
    Route::get('chart-of-accounts/download/excel', 'ChartsOfAccountController@downloadCoaitems')->name('chart-of-accounts.downloadCoaitems');
    Route::GET('maintain-items/download/excel', 'InventoryItemController@downloadInvetoryitems')->name('admin.downloadExcel');
    Route::get('/processed-return', 'StockReturnController@returned_index')->name('stock-return.returned_index');

    Route::resource('stock-return', 'StockReturnController');

    Route::get('profit-and-loss/details/all', 'ProfitLossDetailController@index')->name('profit-and-loss.detailsAll');
    Route::post('profit-and-loss/details/all', 'ProfitLossDetailController@index')->name('profit-and-loss.detailsAll');


    Route::get('profit-and-loss/details/all/excel', 'ProfitLossDetailController@excel')->name('profit-and-loss.detailsAll.excel');
    Route::get('statement-financical-position', 'SFPController@index')->name('statement-financical-position.detailsAll');
    Route::get('statement-financical-position/excel', 'SFPController@excel')->name('statement-financical-position.excel');

    /** Update to all instance */

    /** New Updates by Mohit */
    //  Route::get();
    Route::get('stock-variance/report', 'StockVarienceController@index')->name('admin.stock-variance.index');
    Route::get('stock-variance/add', 'StockVarienceController@addNew')->name('admin.stock-variance.add');
    Route::post('stock-variance/add', 'StockVarienceController@addNew');
    Route::post('stock-variance/create', 'StockVarienceController@create')->name('admin.stock-variance.create');
    Route::get('stock-variance/report-pdf/{date}', 'StockVarienceController@ReportPdf')->name('admin.stock-variance.report-pdf');
    Route::get('stock-variance/report-ecxel/{date}', 'StockVarienceController@ReportExcel')->name('admin.stock-variance.report-excel');

    /** New Updates by Mohit */

    //Account Inquiry.
    Route::get('account-inquiry', 'WaAccountInquiryController@index')->name('admin.account-inquiry.index');
    Route::GET('account-inquiry/search', 'WaAccountInquiryController@search')->name('admin.account-inquiry.search');
    Route::GET('account-inquiry/details/{transaction}', 'WaAccountInquiryController@details')->name('admin.account-inquiry.details');
    Route::GET('account-inquiry/details/edit/{transaction}', 'WaAccountInquiryController@edit')->name('admin.account-inquiry.edit');
    Route::POST('account-inquiry/details/update/', 'WaAccountInquiryController@update')->name('admin.account-inquiry.update');
    Route::get('account-inquiry/update-report', 'WaAccountInquiryController@update_report')->name('admin.account-inquiry.update_report');
    //Account Inquiry.

    // Production Processes
    Route::resource('processes', 'ProductionProcessController');
    Route::get('/maintain-processes/datatable', 'ProductionProcessController@datatable')->name('processes.datatable');

    // Inventory Items BOM
    Route::get('maintain-items/{id}/bom', 'InventoryItemController@showBomMaterials')->name('maintain-items.show-bom');
    Route::get('maintain-items/{id}/bom/add-item', 'InventoryItemController@showAddBomItemForm')->name('maintain-items.add-bom-item');
    Route::post('maintain-items/{id}/bom/add-item', 'InventoryItemController@storeBomItem')->name('maintain-items.store-bom-item');
    Route::post('maintain-items/bom/{id}/remove-item', 'InventoryItemController@removeBomItem')->name('maintain-items.remove-bom-item');

    // Production Processes
    Route::resource('processes', 'ProductionProcessController');
    Route::get('/maintain-processes/datatable', 'ProductionProcessController@datatable')->name('processes.datatable');
    // Production Processes
    Route::resource('processes', 'ProductionProcessController');
    Route::get('/maintain-processes/datatable', 'ProductionProcessController@datatable')->name('processes.datatable');

    Route::get('maintain-items/{itemId}/operation-steps', 'InventoryItemProcessController@index')->name('maintain-items.operation-steps.index');
    Route::get('maintain-items/{itemId}/operation-steps/create', 'InventoryItemProcessController@create')->name('maintain-items.operation-steps.create');
    Route::post('maintain-items/{itemId}/operation-steps/create', 'InventoryItemProcessController@store')->name('maintain-items.operation-steps.store');
    Route::get('maintain-items/{itemId}/operation-steps/{processId}/edit', 'InventoryItemProcessController@edit')->name('maintain-items.operation-steps.edit');
    Route::post('maintain-items/{itemId}/operation-steps/{processId}/update', 'InventoryItemProcessController@update')->name('maintain-items.operation-steps.update');
    Route::post('maintain-items/{itemId}/operation-steps/{stepId}/destroy', 'InventoryItemProcessController@delete')->name('maintain-items.operation-steps.destroy');

    // Route Manager routes
    Route::resource('manage-routes', 'RouteController');

    Route::get('/routes-datatable', 'RouteController@datatable')->name('manage-routes.datatable');
    Route::get('/routes-list', 'RouteController@listing')->name('manage-routes.listing');
    Route::get('/routes-targets-report', 'RouteController@routeTonnageSummary')->name('manage-routes.route-tonnage-summary');
    Route::post('/manage-routes/{route}/update-sections', 'RouteController@updateSections')->name('manage-routes.sections.update');
    Route::resource('manage-delivery-centers', 'DeliveryCenterController');

    // Production Work Orders
    Route::resource('work-orders', 'ProductionWorkOrderController');
    Route::get('/work-orders-datatable', 'ProductionWorkOrderController@datatable')->name('work-orders.datatable');
    Route::post('/work-orders/{id}/start', 'ProductionWorkOrderController@start')->name('work-orders.start');
    Route::post('/work-orders/{id}/pause', 'ProductionWorkOrderController@pause')->name('work-orders.pause');
    Route::post('/work-orders/{id}/resume', 'ProductionWorkOrderController@resume')->name('work-orders.resume');
    Route::post('/work-orders/{id}/complete', 'ProductionWorkOrderController@complete')->name('work-orders.complete');
    Route::post('/work-orders/{id}/void', 'ProductionWorkOrderController@void')->name('work-orders.void');

    //Reported Shops
    Route::resource('reported-shops', 'AdminReportShopController');
    Route::resource('reported-routes', 'AdminReportRouteController');
    Route::resource('report_reasons', 'AdminReportReasonController');

    // Delivery Loading Sheet
    Route::post('/loading-sheets/generate-for-dispatch', 'DeliveryLoadingSheetController@generateForDispatch')->name('delivery-loading-sheets.generate-for-dispatch');
    Route::get('/loading-sheets/generate-for-dispatch', function () {
        return redirect()->route('confirm-invoice.dispatch_and_close_loading_sheet');
    });


    Route::resource('edit-ledger', 'EditLedgerController');
    Route::get('edit-ledger/bank-trans/{id}', 'EditLedgerController@bankTransedit')->name('edit-ledger.bank-trans.edit');
    Route::put('edit-ledger/bank-trans/update/{id}', 'EditLedgerController@bankTransUpdate')->name('edit-ledger.bank-trans.update');

    Route::get('edit-ledger/debtor-trans/{id}', 'EditLedgerController@debtorTransedit')->name('edit-ledger.debtor-trans.edit');
    Route::put('edit-ledger/debtor-trans/update/{id}', 'EditLedgerController@debtorTransUpdate')->name('edit-ledger.debtor-trans.update');


    Route::get('edit-ledger/supplier-trans/{id}', 'EditLedgerController@supplierTransedit')->name('edit-ledger.supplier-trans.edit');
    Route::put('edit-ledger/supplier-trans/update/{id}', 'EditLedgerController@supplierTransUpdate')->name('edit-ledger.supplier-trans.update');
    Route::get('reports/items-data-purchase-report', 'ReportsController@items_data_purchases')->name('reports.items_data_purchase_report');
    Route::get('reports/items-data-sales-report', 'ReportsController@items_data_sales')->name('reports.items-data-sales');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activitylogs.index');
    Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show'])->name('activitylogs.show');
    Route::get('/activity-logs-datatable', [ActivityLogController::class, 'datatable'])->name('activitylogs.datatable');
    Route::delete('/activity-logs/{id}', [ActivityLogController::class, 'destroy'])->name('activitylogs.destroy');
    Route::get('/activity-logs/user/{id}', [ActivityLogController::class, 'user_activity'])->name('activitylogs.user_activity');

    // BULKSMS

    Route::prefix('bulk-sms')->name('bulk-sms.')->group(function () {
        Route::get('test-message', [BulkSmsController::class, 'test_message'])->name('test-message');
        Route::post('test-message-save', [BulkSmsController::class, 'test_message_save'])->name('test-message-save');
        Route::get('message-log', [BulkSmsController::class, 'message_log'])->name('message-log');
        Route::get('message-log/{id}', [BulkSmsController::class, 'message_log_view'])->name('message-log.view');
        Route::get('create', [BulkSmsController::class, 'create_bulk_message'])->name('create');
        Route::post('save', [BulkSmsController::class, 'save_bulk_message'])->name('save');

        Route::get('get-employee', [BulkSmsController::class, 'employee_info'])->name('employee_info');
        Route::get('get-customer', [BulkSmsController::class, 'customer_info'])->name('customer_info');
        Route::get('get-supplier/{branch}', [BulkSmsController::class, 'supplier_info'])->name('supplier_info');
        Route::get('get-routes/{branch}', [BulkSmsController::class, 'routes'])->name('routes');
    });

    // HELP DESK
    Route::prefix('help-desk')->name('help-desk.')->group(function () {
        Route::post('tickets-assign', [TicketController::class, 'assign'])->name('tickets.assign');
        Route::post('tickets-respond', [TicketController::class, 'respond'])->name('tickets.respond');
        Route::post('tickets-status', [TicketController::class, 'status'])->name('tickets.status');
        Route::get('my-tickets', [TicketController::class, 'my_tickets'])->name('my.tickets');
        Route::resource('tickets', TicketController::class);
    });

    Route::resource('ticket-category', TicketCategoryController::class);
    Route::get('support-team/delete/{id}', [HelpDeskSupportController::class, 'delete'])->name('support-team.delete');
    Route::resource('support-team', HelpDeskSupportController::class);
    //Missing  Items Sales
    Route::get('missing-items-sales', [MissingItemssalesReportController::class, 'index'])->name('missing-items-sales.index');
    Route::get('reported-missing-items', [ReportedMissingItemsController::class, 'reportedMissingItemsReport'])->name('reported-missing-items.index');
    Route::post('report-missing-items/web', [ReportedMissingItemsController::class, 'reportMissingItemsWeb'])->name('report-missing-items.report-from-web');

    Route::get('reported-new-items', [ReportNewItemController::class, 'index'])->name('reported-new-items.index');
    Route::post('report-new-items/web', [ReportNewItemController::class, 'reportNewItemsWeb'])->name('report-new-items.report-from-web');

    Route::get('reported-price-conflicts', [ReportPriceConflict::class, 'index'])->name('reported-price-conflicts.index');
    Route::post('report-price-conflicts/web', [ReportPriceConflict::class, 'reportPriceConflictsWeb'])->name('report-price-conflicts.report-from-web');

    Route::get('price-list-cost-report', [PriceListCostReportController::class, 'index'])->name('price-list-costs-reports.index');
    Route::get('items-with-multiple-suppliers', [ItemsWithMultipleSuppliersController::class, 'index'])->name('items-with-multiple-suppliers.index');
});

// Routes in controllers in shared folder => shared by both web and api requests
Route::group(['prefix' => 'admin', 'namespace' => 'Shared', 'middleware' => ['AdminLoggedIn', 'ip-blocker']], function () {
    // Route customers

    Route::get('route-customers/routes', 'RouteCustomerController@routes')->name('route-customers.routes');
    Route::get('route-customers/centers', 'RouteCustomerController@centers')->name('route-customers.centers');
    Route::resource('route-customers', 'RouteCustomerController');
    Route::get('/route-customers/custom-show/{id}/{model}', 'RouteCustomerController@customShow')->name('route-customers.show-custom');
    Route::get('/route-customers-datatable', 'RouteCustomerController@datatable')->name('route-customers.datatable');
    Route::get('/route-customers-overview', 'RouteCustomerController@overview')->name('route-customers.overview');
    Route::get('/route-customers-onboarding-requests', 'RouteCustomerController@unverifiedIndex')->name('route-customers.unverified');
    Route::post('/route-customers/{id}/verify', 'RouteCustomerController@verifyShopFromWeb')->name('route-customers.verify');
    Route::get('/route-customers/{id}/verify-show', 'RouteCustomerController@verifyShopFromWebShow')->name('route-customers.verify-show');
    Route::post('/route-customers-verify-all', 'RouteCustomerController@verifyAll')->name('route-customers.verify-all');
    Route::post('/route-customers/{id}/reject', 'RouteCustomerController@rejectShopFromWeb')->name('route-customers.verification-reject');
    Route::get('/route-customers/{id}/reject-show/{model}', 'RouteCustomerController@rejectShopFromWebShow')->name('route-customers.verification-reject-show');
    Route::post('/route-customers/{id}/reject-show/{model}', 'RouteCustomerController@rejectShopFromWebShow')->name('route-customers.verification-reject-show');
    Route::get('/route-customers/rejected/listing', 'RouteCustomerController@rejectedCustomers')->name('rejected-customers');


    Route::get('/route-customers-approval-requests', 'RouteCustomerController@approvalRequestsView')->name('route-customers.approval-requests');
    Route::post('/route-customers/{id}/approve', 'RouteCustomerController@approve')->name('route-customers.approve');
    Route::get('/route-customers/{id}/approve-show', 'RouteCustomerController@approveShow')->name('route-customers.approve-show');
    Route::post('/route-customers-approve-all', 'RouteCustomerController@approveAll')->name('route-customers.approve-all');
    Route::get('/route-customers-comments', 'RouteCustomerController@customerComments')->name('route-customers.comments');
    Route::get('/route-customers-get-routes/{id}', 'RouteCustomerController@getRoutes')->name('route-customers.get-routes');
    Route::get('/route-customers-download-customers', 'RouteCustomerController@downloadComments')->name('route-customers.download-comments');

    // Route::get('/download-route-customers','RouteCustomerController@downloadRouteCustomers')->name('route-customers.download');

    // ASSETS
    Route::prefix('assets')->group(function () {
        Route::get('/', [AssetController::class, 'index'])->name('assets.index');
        Route::get('/add', [AssetController::class, 'add'])->name('assets.add');
        Route::get('/getData/{id}', [AssetController::class, 'getData'])->name('assets.getData');

        Route::get('/addJournal/{id}', [AssetController::class, 'addJournal'])->name('assets.addJournal');
        Route::POST('/postJournal', [AssetController::class, 'postJournal'])->name('assets.postJournal');

        Route::POST('/edit', [AssetController::class, 'update'])->name('assets.edit');
        Route::POST('/delete', [AssetController::class, 'delete'])->name('assets.delete');
    });


    Route::prefix('assets-location')->group(function () {
        Route::get('/', [AssetController::class, 'location_index'])->name('assets.location.index');
        Route::get('/add', [AssetController::class, 'location_add'])->name('assets.location.add');
        Route::post('/save', [AssetController::class, 'location_save'])->name('assets.location.save');
        Route::get('/edit/{id}', [AssetController::class, 'location_edit'])->name('assets.location.edit');
        Route::POST('/update', [AssetController::class, 'location_update'])->name('assets.location.update');
        Route::POST('/delete', [AssetController::class, 'location_delete'])->name('assets.location.delete');
    });

    Route::prefix('assets-category')->group(function () {
        Route::get('/', [AssetController::class, 'category_index'])->name('assets.category.index');
        Route::get('/add', [AssetController::class, 'category_add'])->name('assets.category.add');
        Route::post('/save', [AssetController::class, 'category_save'])->name('assets.category.save');
        Route::get('/edit/{id}', [AssetController::class, 'category_edit'])->name('assets.category.edit');
        Route::POST('/update', [AssetController::class, 'category_update'])->name('assets.category.update');
        Route::POST('/delete', [AssetController::class, 'category_delete'])->name('assets.category.delete');
    });

    Route::GET('asset-change-location', [AssetController::class, 'changeAssetLocation'])->name('changeAssetLocation');
    Route::POST('asset-change-location', [AssetController::class, 'changeAssetLocationUpdate'])->name('changeAssetLocationUpdate');

    Route::prefix('fixed-asset')->group(function () {
        Route::GET('maintenance-task-list', [AssetController::class, 'maintenance_task_list'])->name('fixed_asset.maintenance_task_list');
        Route::GET('maintenance-task-add', [AssetController::class, 'maintenance_task_add'])->name('fixed_asset.maintenance_task_add');
        Route::POST('maintenance-task-add', [AssetController::class, 'maintenance_task_create']);
        Route::POST('maintenance-task-delete', [AssetController::class, 'maintenance_task_delete'])->name('fixed_asset.maintenance_task_delete');
        Route::GET('maintenance-task-report', [AssetController::class, 'maintenance_report_download'])->name('fixed_asset.maintenance_report_download');

        Route::GET('maintenance-task-edit/{id}', [AssetController::class, 'maintenance_task_edit'])->name('fixed_asset.maintenance_task_edit');
        Route::POST('maintenance-task-edit', [AssetController::class, 'maintenance_task_update'])->name('fixed_asset.maintenance_task_update');
    });
});
// discounts


// Non-namespaced routes
Route::group(['prefix' => 'admin', 'middleware' => ['AdminLoggedIn', 'ip-blocker']], function () {
    // Shifts
    Route::get('/salesman-shifts/reopen-requests', [SalesManShiftController::class, 'getReopenRequests'])->name('salesman-shift.reopen-requests');
    Route::post('/salesman-shifts/reopen-requests/{id}/approve', [SalesManShiftController::class, 'approveReopenRequest'])->name('salesman-shift.reopen-requests.approve');
    Route::post('/salesman-shifts/reopen-requests/{id}/decline', [SalesManShiftController::class, 'declineReopenRequest'])->name('salesman-shift.reopen-requests.decline');

    Route::get('/salesman-shifts/offsite-requests', [SalesManShiftController::class, 'getOffsiteRequests'])->name('salesman-shift.offsite-requests');
    Route::post('/salesman-shifts/offsite-requests/{id}/approve', [SalesManShiftController::class, 'approveOffsiteRequest'])->name('salesman-shift.offsite-requests.approve');
    Route::post('/salesman-shifts/offsite-requests/{id}/decline', [SalesManShiftController::class, 'declineOffsiteRequest'])->name('salesman-shift.offsite-requests.decline');

    // Delivery
    Route::resource('store-loading-sheets', ParkingListController::class);
    Route::get('/dispatch-loading-sheet/{id}', [ParkingListController::class, 'dispatchLoadingSheet'])->name('store-loading-sheets.dispatch');
    Route::get('/dispatched-loading-sheets', [ParkingListController::class, 'getDispatched'])->name('store-loading-sheets.dispatched');
    Route::get('/dispatched-loading-sheets/details/{id}', [ParkingListController::class, 'getDispatchedItems'])->name('store-loading-sheets.dispatched-details');
    Route::get('/salesman-shifts/{id}/loading-sheets', [ParkingListController::class, 'shiftLoadingSheets'])->name('salesman-shifts.loading-sheets');
    Route::get('/store-loading-sheets/{id}/items', [ParkingListController::class, 'loadingSheetItems'])->name('store-loading-sheets.items');
    Route::resource('delivery-schedules', DeliveryScheduleController::class);
    Route::get('delivery-schedules-pdf/{scheduleId}', [DeliveryScheduleController::class, 'downloadDeliverySchedule'])->name('delivery-schedules.downloadPdf');
    Route::get('gate-pass/{scheduleId}', [DeliveryScheduleController::class, 'downloadGatePass'])->name('gate-pass.downloadPdf');
    Route::get('initiate-gate-pass/{scheduleId}', [DeliveryScheduleController::class, 'initiateGatePass'])->name('gate-pass.downloadPdf');
    Route::get('split-schedule/{scheduleId}', [DeliverySplitController::class, 'splitSchedules'])->name('route.split-schedules');
    Route::post('split-schedule-insert', [DeliverySplitController::class, 'insertDeliverySplit'])->name('route.split-schedules-insert');
    
    // Multiple shifts management for delivery schedules
    Route::get('delivery-schedules/{id}/shifts', [DeliveryScheduleController::class, 'getShifts'])->name('delivery-schedules.get-shifts');
    Route::post('delivery-schedules/{id}/shifts', [DeliveryScheduleController::class, 'addShifts'])->name('delivery-schedules.add-shifts');
    Route::delete('delivery-schedules/{id}/shifts/{shiftId}', [DeliveryScheduleController::class, 'removeShift'])->name('delivery-schedules.remove-shift');
    Route::post('delivery-schedules/{id}/merge', [DeliveryScheduleController::class, 'mergeDeliverySchedules'])->name('delivery-schedules.merge');
    Route::post('delivery-schedules/{id}/unmerge/{shiftId}', [DeliveryScheduleController::class, 'unmergeShift'])->name('delivery-schedules.unmerge');



    // Fleet
    Route::resource('vehicles', VehicleController::class);
    Route::get('/vehicles/overview/all', [VehicleOverviewController::class, 'overview'])->name('vehicle-overview-all');
    Route::get('/get-vehicle-locations', [VehicleOverviewController::class, 'getLocations'])->name('vehicle-overview-get-locations');
    Route::get('/vehicle-movements/{deviceName}', [VehicleOverviewController::class, 'liveVehicleMovement'])->name('live-vehicle-movement');
    Route::get('/vehicle-movement/get-movement/{deviceName}', [VehicleOverviewController::class, 'getVehicleMovement'])->name('get-live-vehicle-movement');
    Route::get('/vehicle-details/info-window-detals/{deviceName}', [VehicleOverviewController::class, 'getVehicleInfoWindowDetails'])->name('get-vehicle-info-window-details');
    Route::post('/vehicles/toggle-ignition', [VehicleOverviewController::class, 'toggleVehicleIgnition'])->name('switch-off-vehicle-via-web');
    Route::get('/vehicle-movement/download-report/{deviceName}/{startDate}/{endDate}', [VehicleOverviewController::class, 'downloadVehicleTelematicsReport'])->name('telematics-report-download');


    Route::resource('vehicle-suppliers', VehicleSupplierController::class);
    Route::resource('vehicle-models', VehicleModelController::class);

    Route::get('/vehicles/command-center/index', [VehicleCommandContoller::class, 'index'])->name('vehicle-command-center');
    Route::post('/command-center/send-command', [VehicleCommandContoller::class, 'controlAction'])->name('admin.command-center.send-command');
    Route::get('/exemption-schedules', [VehicleCommandContoller::class, 'exemptionSchedules'])->name('exemption-schedules');
    Route::get('/exemption-schedules/edit/{id}', [VehicleCommandContoller::class, 'editExemptionScheduleVehicles'])->name('exemption-schedules-edit');
    Route::post('/exemption-schedules/update/{id}', [VehicleCommandContoller::class, 'updateExemptionScheduleVehicles'])->name('exemption-schedules-update');
    Route::get('/custom-schedules', [VehicleCommandContoller::class, 'customSchedules'])->name('custom-schedules');
    Route::get('/custom-schedules/create', [VehicleCommandContoller::class, 'createCustomSchedules'])->name('custom-schedules.create');
    Route::post('/custom-schedules/store/', [VehicleCommandContoller::class, 'storeCustomSchedules'])->name('custom-schedules.store');
    Route::get('/custom-schedules/edit/{id}', [VehicleCommandContoller::class, 'editCustomSchedules'])->name('custom-schedules.edit');
    Route::post('/custom-schedules/update/{id}', [VehicleCommandContoller::class, 'updateCustomSchedules'])->name('custom-schedules.update');

    // Routes Export
    Route::get('/routes/export', [RouteController::class, 'export'])->name('manage-routes.export');
    Route::get('/route-customer-export', [RouteCustomerController::class, 'export'])->name('route-customers.export');
    Route::post('/export-new-customers', [RouteCustomerController::class, 'exportNewCustomers'])->name('route-customers.export-new-customers');


    // Order Taking Schedules
    Route::group(['prefix' => 'order-taking-schedules'], function () {
        Route::get('/overview', [SalesManShiftController::class, 'overview'])->name('order-taking-schedules.overview');
    });

    Route::get('/suggested-order', [SuggestedOrderController::class, 'index'])->name('suggested-order.index');
    Route::get('/suggested-order/{id}', [SuggestedOrderController::class, 'show'])->name('suggested-order.show');
    Route::PUT('/suggested-order/{id}', [SuggestedOrderController::class, 'update'])->name('suggested-order.update');

    // Salesman Order Taking Routes
    Route::group(['prefix' => 'salesman-orders', 'as' => 'salesman-orders.'], function () {
        Route::get('/', [SalesmanOrderController::class, 'index'])->name('index');
        Route::get('/create', [SalesmanOrderController::class, 'create'])->name('create');
        Route::post('/store', [SalesmanOrderController::class, 'store'])->name('store');
        Route::get('/ajax/route-customers', [SalesmanOrderController::class, 'getRouteCustomers'])->name('ajax.route-customers');
        Route::get('/ajax/search-customers', [SalesmanOrderController::class, 'searchCustomers'])->name('search-customers');
        Route::get('/ajax/item-details', [SalesmanOrderController::class, 'getItemDetails'])->name('ajax.item-details');
        Route::get('/search-inventory', [SalesmanOrderController::class, 'searchInventory'])->name('search-inventory');
        Route::get('/get-item-details', [SalesmanOrderController::class, 'getItemDetails'])->name('get-item-details');
        Route::get('/calculate-discount', [SalesmanOrderController::class, 'calculateItemDiscount'])->name('calculate-discount');
        Route::get('/test-discount/{itemId}/{quantity}', [SalesmanOrderController::class, 'testDiscount'])->name('test-discount');
        Route::get('/test-search', [SalesmanOrderController::class, 'testSearch'])->name('test-search');
        Route::get('/debug-tax/{orderId}', [SalesmanOrderController::class, 'debugTax'])->name('debug-tax');
        Route::get('/fix-vat/{orderId}', [SalesmanOrderController::class, 'fixVatForOrder'])->name('fix-vat');
        Route::get('/debug-loading-sheets', [SalesmanOrderController::class, 'debugLoadingSheets'])->name('debug-loading-sheets');
        Route::get('/generate-loading-sheets/{shiftId?}', [SalesmanOrderController::class, 'generateLoadingSheets'])->name('generate-loading-sheets');
        Route::get('/test-mobile-shift-closing/{shiftId}', [SalesmanOrderController::class, 'testMobileShiftClosing'])->name('test-mobile-shift-closing');
        Route::get('/debug-entire-journey', [SalesmanOrderController::class, 'debugEntireJourney'])->name('debug-entire-journey');
        Route::get('/debug-pos-customer', [SalesmanOrderController::class, 'debugPosCustomer'])->name('debug-pos-customer');
        Route::get('/create-default-pos-customer', [SalesmanOrderController::class, 'createDefaultPosCustomer'])->name('create-default-pos-customer');
        Route::post('/shift/open', [SalesmanOrderController::class, 'openShift'])->name('shift.open');
        Route::post('/shift/close', [SalesmanOrderController::class, 'closeShift'])->name('shift.close');
        Route::get('/{id}', [SalesmanOrderController::class, 'show'])->name('show');
        Route::get('/{id}/print', [SalesmanOrderController::class, 'printOrder'])->name('print');
        Route::get('/{id}/download', [SalesmanOrderController::class, 'downloadInvoice'])->name('download');
    });


    // Salesman Customer Management Routes
    Route::group(['prefix' => 'salesman-customers', 'as' => 'salesman-customers.'], function () {
        Route::get('/', [SalesmanCustomerController::class, 'index'])->name('index');
        Route::post('/store', [SalesmanCustomerController::class, 'store'])->name('store');
        Route::get('/{id}', [SalesmanCustomerController::class, 'show'])->name('show');
        Route::put('/{id}', [SalesmanCustomerController::class, 'update'])->name('update');
        Route::get('/ajax/delivery-centers', [SalesmanCustomerController::class, 'getDeliveryCenters'])->name('ajax.delivery-centers');
    });

    // Salesman Access Test Route
    Route::get('/salesman-test', function () {
        $user = Auth::user();
        $salesRoleIds = config('salesman.sales_role_ids', [169, 170]);
        $salesKeywords = config('salesman.sales_role_keywords', ['sales', 'salesman', 'representative']);
        $roleName = $user->userRole->name ?? $user->userRole->title ?? '';
        
        $hasRoute = !empty($user->route);
        $isSalesRoleId = in_array((int) $user->role_id, $salesRoleIds);
        $roleLooksSales = collect($salesKeywords)->some(fn($keyword) => stripos($roleName, $keyword) !== false);
        
        return response()->json([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role_id' => $user->role_id,
            'role_name' => $roleName,
            'route' => $user->route,
            'has_route' => $hasRoute,
            'is_sales_role_id' => $isSalesRoleId,
            'role_looks_sales' => $roleLooksSales,
            'is_salesman' => ($hasRoute || $roleLooksSales || $isSalesRoleId),
            'can_access_salesman_urls' => true,
            'salesman_urls' => [
                'dashboard' => route('salesman-orders.index'),
                'create_order' => route('salesman-orders.create'),
                'customer_management' => route('salesman-customers.index')
            ]
        ]);
    })->name('salesman-test');


    Route::get('/routes/reports/daily-sales-report', [RouteDailySalesReportController::class, 'generate'])->name('route-reports.daily-sales');
    Route::get('/routes/reports/daily-sales-report/download', [RouteDailySalesReportController::class, 'download'])->name('route-reports.daily-sales.download');

    Route::get('/routes/reports/weekly-sales-report', [WeeklySalesReportController::class, 'generate'])->name('route-reports.weekly-sales');
    Route::get('routes/reports/weekly-sales-report/download', [WeeklySalesReportController::class, 'download'])->name('route-reports.weekly-sales.download');

    Route::resource('payment-providers', PaymentProviderController::class);

    Route::resource('alerts', AlertController::class);
    Route::resource('scheduled-notifications', ScheduledNotificationController::class);

    Route::resource('reported-shift-issues', SalesmanReportedIssueController::class);
    Route::get('procurement-reported-shift-issues', [ProcurementSalesmanReportedIssueController::class, 'index'])->name('procurement-reported-shift-issues.index');
    Route::post('resolve-salesman-reported-issue', [ResolveSalesmanReportedIssue::class, 'resolveSalesmanReportedIssue'])->name('resolve.salesman.reported.issue');

    Route::resource('field-visits', FieldVisitController::class);

    Route::resource('geomapping-schedules', GeomappingSchedulesController::class);
    Route::get('/route-customers-served-time/{id}', [GeomappingSchedulesController::class, 'customerServeTime'])->name('schedules-geomapping.customer-serve-time');
    Route::get('/geomapping-summary', [GeomappingSchedulesController::class, 'summary'])->name('geomapping-summary');
    Route::get('/geomapping-summary/show/{branchId}', [GeomappingSchedulesController::class, 'summaryShow'])->name('geomapping-summary.show');
    Route::post('/geomapping-summary/mark-complete/{id}', [GeomappingSchedulesController::class, 'markScheduleAsComplete'])->name('mark-geomapping-schedule-as-complete');
    Route::get('/geomapping-summary/mark-HQ-approve/{id}', [GeomappingSchedulesController::class, 'markScheduleAsHQApproved'])->name('mark-geomapping-schedule-as-Hq-approved');


    Route::group(['prefix' => 'supplier-portal', 'as' => 'supplier-portal.'], function () {
        Route::get('/pending-suppliers', [SupplierPortalController::class, 'getPendingSuppliers'])->name('pending-suppliers');
        Route::get('/approve-supplier-portal-user/{id}', [SupplierPortalController::class, 'approveSupplierPortalUser'])->name('approve.supplier.portal.user');
        Route::get('/decline-supplier-portal-user/{id}', [SupplierPortalController::class, 'declineSupplierPortalUser'])->name('decline.supplier.portal.user');
        Route::get('/supplier-portal/{id}', [SupplierPortalController::class, 'get_supplier_details'])->name('supplier-details');
        Route::post('/supplier-portal/staff/{id}', [SupplierPortalController::class, 'update_supplier_staff'])->name('update_supplier_staff');
        Route::get('/suppliers-from-portal', [SupplierPortalController::class, 'get_all_supplier_from_portal'])->name('get_all_supplier_from_portal');
        Route::get('/impersonate/{supplier}', [SupplierImpersonationController::class, 'show'])->name('impersonate');
        Route::post('/suspend-supplier', [SupplierPortalController::class, 'suspend_supplier'])->name('suspend-supplier');
        Route::post('/invite-supplier', [SupplierPortalController::class, 'inviteSupplier'])->name('invite-supplier');
        Route::get('/billing-description', [SupplierPortalController::class, 'billing_description'])->name('billing-description');
        Route::post('/billing-description', [SupplierPortalController::class, 'update_billing_description'])->name('billing-description');
    });
    
    // Debug route for LPO approval issues
    Route::get('/debug-lpo', function() {
        // Find all pending LPOs
        $pendingLPOs = \App\Model\WaPurchaseOrder::whereIn('status', ['PENDING', 'pending', 'UNAPPROVED', 'unapproved'])->get();
        
        // Find the logged in user
        $user = getLoggeduserProfile();
        $isProcurementOfficer = ($user->userRole && $user->userRole->slug == 'procurement-officer');
        
        // Create permissions for all pending LPOs for this procurement officer
        foreach($pendingLPOs as $lpo) {
            // Create a permission record if it doesn't exist
            $permission = \App\Model\WaPurchaseOrderPermission::firstOrCreate(
                ['wa_purchase_order_id' => $lpo->id, 'user_id' => $user->id],
                [
                    'approve_level' => $user->purchase_order_authorization_level ?? 1,
                    'status' => 'NEW',
                    'note' => 'Auto-created by debug route'
                ]
            );
            
            // If the permission status was already set, update it to NEW
            if ($permission->status != 'NEW') {
                $permission->status = 'NEW';
                $permission->save();
            }
        }
        
        return redirect()->route('admin.approve-lpo.index')->with('success', 'Debug complete. LPO permissions have been refreshed.');
    });

    Route::post('/purchase-order/send-to-supplier', [PurchaseOrderController::class, 'sendToSupplier'])->name('purchase-orders.send-to-supplier');
    Route::get('/general-file-import', [GeneralExcelFileImportController::class, 'import']);

    Route::resource('teams', TeamController::class);

    Route::resource('item-demands', ItemSupplierDemandController::class);
    Route::get('/item-demands/details/{id}', [ItemSupplierDemandController::class, 'demandItems'])->name('demands.item-demands');
    Route::get('/n-item-demands', [ItemSupplierDemandController::class, 'newIndex'])->name('demands.item-demands.new');
    Route::get('/n-item-demands/details/{id}', [ItemSupplierDemandController::class, 'newDemandItems'])->name('demands.item-demands.details.new');
    Route::get('/n-item-demands/download/{id}', [ItemSupplierDemandController::class, 'downloadDemand'])->name('demands.item-demands.download');
    Route::get('/n-item-demands/convert/{id}', [ItemSupplierDemandController::class, 'itemDemandConvert'])->name('demands.item-demands.convert');
    Route::get('/n-item-demands/approve/{id}', [ItemSupplierDemandController::class, 'itemDemandApprove'])->name('demands.item-demands.approve');

    // Return demands
    Route::get('/return-demands', [ReturnDemandController::class, 'returnDemandIndex'])->name('return-demands.index');
    Route::get('/return-demands/details/{id}', [ReturnDemandController::class, 'returnDemandDetails'])->name('return-demands.details');
    Route::get('/return-demands/print/{id}', [ReturnDemandController::class, 'returnDemandPrint'])->name('return-demands.print');
    Route::get('/return-demands/convert/{id}', [ReturnDemandController::class, 'returnDemandConvert'])->name('return-demands.convert');
    Route::get('/return-demands/approve/{id}', [ReturnDemandController::class, 'returnDemandApprove'])->name('return-demands.approve');

    Route::get('/update-shop-estimates', [RouteCustomerController::class, 'updateEstimates']);
    Route::post('/remove-route-customer', [CustomerController::class, 'removeCustomer'])->name('route-customers.remove');

    Route::get('/batch-price-change', [PriceChangeController::class, 'showBatchChangePage'])->name('price-change.batch-requests');
    Route::get('/price-timeline-report', [PriceChangeController::class, 'priceTimeline'])->name('reports.price_timeline_report');

    // RETURN TO SUPPLIER
    // Return From GRN
    Route::get('/return-to-supplier/from-grn/create', [ReturnToSupplierController::class, 'showReturnFromGrnCreatePage'])->name('return-to-supplier.from-grn.create');
    Route::get('/return-to-supplier/from-grn/pending', [ReturnToSupplierController::class, 'showReturnFromGrnPendingPage'])->name('return-to-supplier.from-grn.pending');
    Route::get('/return-to-supplier/from-grn/approved', [ReturnToSupplierController::class, 'showReturnFromGrnApprovedPage'])->name('return-to-supplier.from-grn.approved');
    Route::get('/return-to-supplier/from-grn/approve/{return_no}', [ReturnToSupplierController::class, 'showReturnFromGrnApprovePage'])->name('return-to-supplier.from-grn.approve');
    Route::get('/return-to-supplier/from-grn/print/{return_no}', [ReturnToSupplierController::class, 'printReturnFromGrn'])->name('return-to-supplier.from-grn.print');

    Route::get('/return-to-supplier/processed', [ReturnToSupplierController::class, 'showProcessedReturnsPage'])->name('return-to-supplier.processed-returns');
    Route::get('/return-to-supplier/pending', [ReturnToSupplierController::class, 'showPendingReturnsPage'])->name('return-to-supplier.pending-returns');

    // Return From Store

    Route::get('/return-to-supplier/from-grn', [ReturnToSupplierController::class, 'showReturnFromGrnPage'])->name('return-to-supplier.from-grn.show');
    // RETURN TO SUPPLIER
    // Return From GRN
    Route::get('/return-to-supplier/from-grn/create', [ReturnToSupplierController::class, 'showReturnFromGrnCreatePage'])->name('return-to-supplier.from-grn.create');
    Route::get('/return-to-supplier/from-grn/pending', [ReturnToSupplierController::class, 'showReturnFromGrnPendingPage'])->name('return-to-supplier.from-grn.pending');
    Route::get('/return-to-supplier/from-grn/approved', [ReturnToSupplierController::class, 'showReturnFromGrnApprovedPage'])->name('return-to-supplier.from-grn.approved');
    Route::get('/return-to-supplier/from-grn/approve/{return_no}', [ReturnToSupplierController::class, 'showReturnFromGrnApprovePage'])->name('return-to-supplier.from-grn.approve');
    Route::get('/return-to-supplier/from-grn/print/{return_no}', [ReturnToSupplierController::class, 'printReturnFromGrn'])->name('return-to-supplier.from-grn.print');

    Route::get('/return-to-supplier/processed', [ReturnToSupplierController::class, 'showProcessedReturnsPage'])->name('return-to-supplier.processed-returns');
    Route::get('/return-to-supplier/pending', [ReturnToSupplierController::class, 'showPendingReturnsPage'])->name('return-to-supplier.pending-returns');

    // Return From Store
    Route::get('/return-to-supplier/from-store/create', [ReturnToSupplierController::class, 'showReturnFromStoreCreatePage'])->name('return-to-supplier.from-store.create');
    Route::get('/return-to-supplier/from-store/pending', [ReturnToSupplierController::class, 'showReturnFromStorePendingPage'])->name('return-to-supplier.from-store.pending');
    Route::get('/return-to-supplier/from-store/approved', [ReturnToSupplierController::class, 'showReturnFromStoreApprovedPage'])->name('return-to-supplier.from-store.approved');
    Route::get('/return-to-supplier/from-store/approve/{id}', [ReturnToSupplierController::class, 'showReturnFromStoreApprovePage'])->name('return-to-supplier.from-store.approve');
    Route::get('/return-to-supplier/from-store/print/{id}', [ReturnToSupplierController::class, 'printReturnFromStore'])->name('return-to-supplier.from-store.print');
    Route::get('/return-to-supplier/from-store/rejected', [ReturnToSupplierController::class, 'showReturnFromStoreRejectedPage'])->name('return-to-supplier.from-store.rejected');
    Route::get('/return-to-supplier/from-store/rejected/{id}/details', [ReturnToSupplierController::class, 'showReturnFromStoreRejectedDetailsPage'])->name('return-to-supplier.from-store.rejected-details');

    Route::get('/price-update-upload', [PriceUpdateUploadController::class, 'showUploadPage'])->name('price-update.upload-page');
    Route::get('/price-update-upload-download', [PriceUpdateUploadController::class, 'download'])->name('price-update.download');
    Route::post('/price-update-upload-upload', [PriceUpdateUploadController::class, 'upload'])->name('price-update.upload');

    Route::get('/loading-schedule-vs-sales-report', [LoadingScheduleVSalesReportController::class, 'generate'])->name('sales-and-receivables-reports.loading-schedule-vs-sales-report');
    Route::get('/delivery-schedule-report', [DeliveryScheduleReportController::class, 'generate'])->name('sales-and-receivables-reports.delivery-schedule-report');

    Route::get('/customer-balances-reportport', [CustomerBalancesReportController::class, 'generate'])->name('sales-and-receivables-reports.customer-balances-report');
    Route::get('/till-direct-banking-report', [TillDirectBankingReportController::class, 'index'])->name('sales-and-receivables-reports.till-direct-banking-report');

    Route::get('/sales-and-receivables/reports/route-performance-report', [RoutePerformanceReportController::class, 'index'])
        ->name('sales-and-receivables-reports.route-performance-report');

    Route::get('/sales-and-receivables/reports/route-performance-sales-per-route', [RoutePerformanceReportController::class, 'salesPerRoute'])
        ->name('sales-and-receivables-reports.sales-per-route');
    Route::get('/sales-and-receivables/reports/route-performance-unmet-customers', [RoutePerformanceReportController::class, 'unmetCustomers'])
        ->name('sales-and-receivables-reports.unmet-customers');
    Route::get('/sales-and-receivables/reports/route-performance-shift', [RoutePerformanceReportController::class, 'routeShifts'])
        ->name('sales-and-receivables-reports.route-shifts');
    Route::get('/sales-and-receivables/reports/route-performance-tonnage', [RoutePerformanceReportController::class, 'tonnage'])
        ->name('sales-and-receivables-reports.route-tonnage');
    Route::get('/sales-and-receivables/reports/route-performance-cartons', [RoutePerformanceReportController::class, 'cartons'])
        ->name('sales-and-receivables-reports.route-cartons');
    Route::get('/sales-and-receivables/reports/route-performance-dozens', [RoutePerformanceReportController::class, 'dozens'])
        ->name('sales-and-receivables-reports.route-dozens');
    Route::get('/sales-and-receivables/reports/route-performance-returns', [RoutePerformanceReportController::class, 'returns'])
        ->name('sales-and-receivables-reports.route-returns');

    /*sales by centers Reports*/
    Route::get('/sales-and-receivables/reports/sales-by-center-summary', [SalesByCenterReportController::class, 'summary'])->name('sales-and-receivables-reports.sales-by-center-summary');
    Route::get('/sales-and-receivables/reports/sales-by-center-top-centers', [SalesByCenterReportController::class, 'topCenters'])->name('sales-and-receivables-reports.sales-by-center-top-centers');
    Route::get('/sales-and-receivables/reports/sales-by-center-top-customers', [SalesByCenterReportController::class, 'topCustomers'])->name('sales-and-receivables-reports.sales-by-center-top-customers');
    Route::get('/sales-and-receivables/reports/sales-by-center-dormant_customers', [SalesByCenterReportController::class, 'dormantCustomers'])->name('sales-and-receivables-reports.sales-by-center-dormant_customers');
    Route::get('/sales-and-receivables/reports/sales-by-center-global-sales', [SalesByCenterReportController::class, 'globalSales'])->name('sales-and-receivables-reports.sales-by-center-global-sales');
    Route::get('/sales-and-receivables/reports/sales-by-center-global-sales-summary', [SalesByCenterReportController::class, 'globalSalesSummary'])->name('sales-and-receivables-reports.sales-by-center-global-sales-summary');

    /*Customer Perfomence Reports*/
    Route::get('/sales-and-receivables/reports/customers-performance-report', [CustomerPerformanceReport::class, 'index'])
        ->name('sales-and-receivables-reports.route-customers-reports');
    Route::get('/sales-and-receivables/reports/customer-performance-report', [CustomerPerformanceReport::class, 'customer'])
        ->name('sales-and-receivables-reports.route-customer-reports');
    Route::get('/sales-and-receivables/reports/sales-vs-payments', [CustomerPerformanceReport::class, 'salesVsPayments'])
        ->name('sales-and-receivables-reports.sales-vs-payments');

    Route::get('/general-ledger/reports/detailed-transaction-summary', [DetailedTransactionSummaryController::class, 'index'])
        ->name('gl-reports.detailed-transaction-summary');

    Route::get('/stock-auto-breaks', [RouteAutoBreakController::class, 'index'])->name('stock-auto-breaks.index');
    Route::get('/stock-auto-breaks/{child_code}/lines', [RouteAutoBreakController::class, 'showAutoBreakLines'])->name('stock-auto-breaks.lines');
    Route::post('/stock-auto-breaks/dispatch/create', [RouteAutoBreakController::class, 'initiateDispatch'])->name('stock-auto-breaks.dispatch.create');
    Route::get('/stock-auto-breaks/dispatch/pending', [RouteAutoBreakController::class, 'showPendingDispatches'])->name('stock-auto-breaks.dispatch.list');
    Route::get('/stock-auto-breaks/dispatch/pending/{id}', [RouteAutoBreakController::class, 'showPendingDispatchLines'])->name('stock-auto-breaks.dispatch.pending.lines');
    Route::post('/stock-auto-breaks/dispatch/process', [RouteAutoBreakController::class, 'processDispatch'])->name('stock-auto-breaks.dispatch.process');
    Route::get('/stock-auto-breaks/dispatched/dispatched', [RouteAutoBreakController::class, 'showDispatchedDispatches'])->name('stock-auto-breaks.dispatched.list');
    Route::get('/stock-auto-breaks/dispatched/list/{id}', [RouteAutoBreakController::class, 'showDispatchedLines'])->name('stock-auto-breaks.dispatch.dispatched.lines');
    Route::post('/stock-auto-breaks/dispatched/receive', [RouteAutoBreakController::class, 'receive'])->name('stock-auto-breaks.dispatch.receive');

    Route::get('/stock-auto-breaks/dispatched/completed', [RouteAutoBreakController::class, 'completed'])->name('stock-auto-breaks.dispatch.completed');
    Route::get('/stock-auto-breaks/dispatched/print/{id}', [RouteAutoBreakController::class, 'printToPdf'])->name('stock-auto-breaks.dispatch.print');
    Route::get('/stock-breaking/summary/all', [RouteAutoBreakController::class, 'summary'])->name('stock-breaking.summary');

    //pos split requests
    Route::get('/stock-breaking/split-requests/all', [MobileInventoryManagementController::class, 'splitRequestsIndex'])->name('stock-breaking.split-requests');
    Route::post('/stock-breaking/split-requests/all/approve', [MobileInventoryManagementController::class, 'approveSplitRequests'])->name('admin.approve-stock-split-requests');
    Route::post('/stock-breaking/split-requests/all/reject', [MobileInventoryManagementController::class, 'rejectSplitRequests'])->name('admin.reject-stock-split-requests');
    Route::post('/stock-breaking/split-requests/report-from-web', [MobileInventoryManagementController::class, 'requestSplitsWeb'])->name('request-splits-from-web');

    Route::get('/bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::post('/bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
    Route::post('/bank-reconciliation/unreconciled', [BankReconciliationController::class, 'unreconciled'])->name('bank-reconciliation.unreconciled');
    Route::post('/bank-reconciliation/system_unreconciled', [BankReconciliationController::class, 'systemUnreconciled'])->name('bank-reconciliation.system_unreconciled');
    Route::post('/bank-reconciliation/reconciled', [BankReconciliationController::class, 'reconciled'])->name('bank-reconciliation.reconciled');
    Route::post('/bank-reconciliation/doubles', [BankReconciliationController::class, 'doubles'])->name('bank-reconciliation.doubles');

    Route::get('/payment-reconciliation-verification', [PaymentReconciliationController::class, 'verification'])->name('payment-reconciliation.verification');
    Route::get('/payment-reconciliation-verification-matching-datatable/{verification}', [PaymentReconciliationController::class, 'verificationMatchingDatatable'])->name('payment-reconciliation.verification.matching.datatable');
    Route::get('/payment-reconciliation-verification-create', [PaymentReconciliationController::class, 'verification_create'])->name('payment-reconciliation.verification.create');
    Route::post('/payment-reconciliation-verification', [PaymentReconciliationController::class, 'verification_upload'])->name('payment-reconciliation.verification.upload');
    Route::post('/payment-reconciliation-verification-process', [PaymentReconciliationController::class, 'verification_process'])->name('payment-reconciliation.verification.process');
    Route::post('/payment-reconciliation-verification-store/{verification}', [PaymentReconciliationController::class, 'verification_store'])->name('payment-reconciliation.verification.store');
    Route::post('/payment-reconciliation-verification-revert/{verification}/{id}', [PaymentReconciliationController::class, 'verification_revert'])->name('payment-reconciliation.verification.revert');
    Route::post('/payment-reconciliation-verification-suspend/{verification}/{id}', [PaymentReconciliationController::class, 'verification_suspend'])->name('payment-reconciliation.verification.suspend');
    Route::post('/payment-reconciliation-verification-discard/{verification}/{id}', [PaymentReconciliationController::class, 'verification_discard'])->name('payment-reconciliation.verification.discard');
    Route::get('/payment-reconciliation-verification-discard-date-range/{verification}', [PaymentReconciliationController::class, 'verification_discard_date_range'])->name('payment-reconciliation.verification.discard_range');
    Route::post('/payment-reconciliation-verification-edit-reference/{id}', [PaymentReconciliationController::class, 'verification_edit_reference'])->name('payment-reconciliation.verification.edit-reference');
    Route::post('/payment-reconciliation-verification-update/{type}/{id}', [PaymentReconciliationController::class, 'verification_update'])->name('payment-reconciliation.verification.update');
    Route::get('/payment-reconciliation-verification-list/{verification}', [PaymentReconciliationController::class, 'verification_list'])->name('payment-reconciliation.verification.list');
    Route::get('/payment-reconciliation-approval', [PaymentReconciliationController::class, 'approval'])->name('payment-reconciliation.approval');
    Route::post('/payment-reconciliation-approval-store', [PaymentReconciliationController::class, 'approval_store'])->name('payment-reconciliation.approval.store');
    Route::get('/payment-reconciliation-verify-all', [PaymentReconciliationController::class, 'verification_all'])->name('payment-reconciliation.verify-all');
    Route::get('/payment-reconciliation-approval/excel/{id}', [PaymentReconciliationController::class, 'approval_excel'])->name('payment-reconciliation.approval.excel');


    Route::get('/bank-post-log', [PaymentReconciliationController::class, 'bank_post_logs'])->name('bank-posting-logs');
    Route::get('/bank-post-log/excel/{id}', [PaymentReconciliationController::class, 'bank_post_logs_excel'])->name('bank-posting-logs.excel');

    Route::get('/debtor-trans', [DebtorTransController::class, 'index'])->name('debtor-trans');
    Route::get('/debtor-trans/datatable', [DebtorTransController::class, 'datatable'])->name('debtor-trans.datatable');

    Route::get('/bank-statements-upload', [BankStatementUploadController::class, 'index'])->name('bank-statements');
    Route::get('/bank-statements-upload/top-up', [BankStatementUploadController::class, 'top_up'])->name('bank-statements.top-up');
    Route::post('/bank-statements-upload/top-up', [BankStatementUploadController::class, 'upload'])->name('bank-statements.upload');
    Route::post('/bank-statements-upload/store', [BankStatementUploadController::class, 'top_up_store'])->name('bank-statements.store');
    Route::post('bank-statements-edit', [BankStatementUploadController::class, 'edit_channel'])->name('bank-edit-channel');
    Route::post('bank-statements-allocate-status', [BankStatementUploadController::class, 'allocate_status'])->name('bank-allocate-status');
    Route::post('bank-statements-export-duplicate', [BankStatementUploadController::class, 'export_duplicate'])->name('bank-statements-export-duplicate');
    Route::post('bank-statements-bank-error', [BankStatementUploadController::class, 'bank_error_flag'])->name('bank-statement-error-flag');
    Route::get('bank-error-logs', [BankStatementUploadController::class, 'bank_error_logs'])->name('bank-error-logs');

    Route::get('/bank-statements-upload/top-up-debit', [BankStatementUploadController::class, 'top_up_debit'])->name('bank-statements.top-up-debit');
    Route::post('/bank-statements-upload/top-up-debit', [BankStatementUploadController::class, 'upload_debit'])->name('bank-statements.upload-debit');
    Route::post('/bank-statements-upload/store-debit', [BankStatementUploadController::class, 'top_up_store_debit'])->name('bank-statements.store-debit');

    Route::get('/bank-statements-upload/top-up-debit-mpesa', [BankStatementUploadController::class, 'top_up_debit_mpesa'])->name('bank-statements.top-up-debit-mpesa');
    Route::post('/bank-statements-upload/top-up-debit-mpesa', [BankStatementUploadController::class, 'upload_debit_mpesa'])->name('bank-statements.upload-debit-mpesa');
    Route::post('/bank-statements-upload/store-debit-mpesa', [BankStatementUploadController::class, 'top_up_store_debit_mpesa'])->name('bank-statements.store-debit-mpesa');


    Route::post('manual-upload-transaction', [ManualUploadController::class, 'manual_upload_transaction'])->name('manual-upload-transaction');
    Route::post('manual-upload-transaction-stock-debtor', [ManualUploadController::class, 'manual_upload_transaction_stock_debtor'])->name('manual-upload-transaction-stock-debtor');
    Route::get('manual-upload-list', [ManualUploadController::class, 'manual_upload_list'])->name('manual-upload-list');
    Route::post('manual-update-status', [ManualUploadController::class, 'manual_update_status'])->name('manual-update-status');


    Route::get('/reconciliation/suspended-transactions', [SuspendedTransactionController::class, 'index'])->name('suspended-transactions.index');
    Route::get('/reconciliation/suspended-transactions/create', [SuspendedTransactionController::class, 'create'])->name('suspended-transactions.create');
    // Route::post('/reconciliation/suspended-transactions/create', [SuspendedTransactionController::class, 'upload'])->name('suspended-transactions.upload');
    Route::post('/reconciliation/suspended-transactions/create', [SuspendedTransactionController::class, 'fetch_transaction'])->name('suspended-transactions.fetch_transaction');
    Route::post('/reconciliation/suspended-transactions/store', [SuspendedTransactionController::class, 'store'])->name('suspended-transactions.store');
    Route::post('/reconciliation/suspended-transactions/{document_no}/expunge', [SuspendedTransactionController::class, 'expunge'])->name('suspended-transactions.expunge');
    Route::post('/reconciliation/suspended-transactions/{document_no}/restore', [SuspendedTransactionController::class, 'restore'])->name('suspended-transactions.restore');

    Route::get('/reconciliation/suspended-transactions/expunged', [SuspendedTransactionController::class, 'expunged'])->name('suspended-transactions.expunged');
    Route::get('/reconciliation/suspended-transactions/restored', [SuspendedTransactionController::class, 'restored'])->name('suspended-transactions.restored');


    Route::post('/delivery-shifts/gate-pass/create', [DeliveryScheduleController::class, 'createGatePass'])->name('delivery-schedules.create-gate-pass');
    Route::post('/delivery-shifts/end-schedule', [DeliveryScheduleController::class, 'endSchedule'])->name('delivery-schedules.end-schedule');


    // Projects
    Route::get('projects/monthly-summary', [ProjectsController::class, 'monthlyProjectSummary'])->name('projects.monthlyProjectSummary');
    Route::get('gl/transaction-summary', [ProjectsController::class, 'gl_transaction_report'])->name('gl.gl_transaction_report');
    Route::get('projects/list', [ProjectsController::class, 'list'])->name('projects.list');
    Route::resource('projects', ProjectsController::class);

    //GL Tags
    Route::get('gl_tags/list', [GlTagsController::class, 'list'])->name('gl_tags.list');
    Route::resource('gl_tags', GlTagsController::class);

    Route::group(['prefix' => 'sales-invoice'], function () {
        Route::group(['prefix' => 'returns'], function () {
            Route::get('/abnormal', [SalesInvoiceReturnController::class, 'showAbnormalReturnsPage'])->name('sales-invoice.returns.abnormal');
        });
    });

    Route::post('/delivery-shifts/gate-pass/create', [DeliveryScheduleController::class, 'createGatePass'])->name('delivery-schedules.create-gate-pass');

    Route::get('petty-cash-approvals/initial', [PettyCashApprovalController::class, 'initialApprovals'])->name('petty-cash-approvals.initial');
    Route::post('petty-cash-approvals/initial/approve', [PettyCashApprovalController::class, 'approveInitial'])->name('petty-cash-approvals.initial-approve');

    Route::get('petty-cash-approvals/final', [PettyCashApprovalController::class, 'showFinalApprovals'])->name('petty-cash-approvals.final');
    Route::get('petty-cash-approvals/final/lines', [PettyCashApprovalController::class, 'showFinalApprovalLines'])->name('petty-cash-approvals.final.lines');
    Route::post('petty-cash-approvals/final/approve', [PettyCashApprovalController::class, 'approveFinal'])->name('petty-cash-approvals.final-approve');

    Route::get('petty-cash-approvals/undisbursed-petty-cash', [UndisbursedPettyCashController::class, 'undisbursedPettyCash'])->name('petty-cash-approvals.undisbursed-petty-cash');
    Route::post('petty-cash-approvals/approve-undisbursed-petty-cash', [UndisbursedPettyCashController::class, 'approveUndisbursedPettyCash'])->name('petty-cash-approvals.approve-undisbursed-petty-cash');
    Route::post('petty-cash-approvals/reject-undisbursed-petty-cash', [UndisbursedPettyCashController::class, 'rejectUndisbursedPettyCash'])->name('petty-cash-approvals.reject-undisbursed-petty-cash');

    Route::get('petty-cash-approvals/successful-allocations', [PettyCashApprovalController::class, 'showSuccessfulAllocations'])->name('petty-cash-approvals.successful-allocations');
    Route::get('petty-cash-approvals/successful-allocations/export', [PettyCashApprovalController::class, 'exportSuccessfulAllocations'])->name('petty-cash-approvals.export-successful-allocations');
    Route::get('petty-cash-approvals/failed-deposits', [PettyCashApprovalController::class, 'showFailedDeposits'])->name('petty-cash-approvals.failed-deposits');
    Route::post('petty-cash-approvals/failed-deposits/resend', [PettyCashApprovalController::class, 'resendFailedDeposits'])->name('petty-cash-approvals.resend-failed-deposits')->middleware('throttle:1,3');
    Route::get('petty-cash-approvals/rejected-deposits', [PettyCashApprovalController::class, 'showRejectedDeposits'])->name('petty-cash-approvals.rejected-deposits');
    Route::get('petty-cash-approvals/expunged-deposits', [PettyCashApprovalController::class, 'showExpungedDeposits'])->name('petty-cash-approvals.expunged-deposits');

    Route::get('petty-cash-approvals/summary-log', [PettyCashApprovalController::class, 'showSummaryLog'])->name('petty-cash-approvals.summary-log');
    Route::get('petty-cash-approvals/detailed-log', [PettyCashApprovalController::class, 'showDetailedLog'])->name('petty-cash-approvals.detailed-log');
    Route::get('petty-cash-approvals/summary-log/print', [PettyCashApprovalController::class, 'printSummaryLog'])->name('petty-cash-approvals.summary-log-print');
    Route::get('petty-cash-approvals/summary-log/transactions', [PettyCashApprovalController::class, 'showSummaryLogTransactions'])->name('petty-cash-approvals.summary-log-transactions');
    Route::get('petty-cash-approvals/detailed-log/{id}/transactions', [PettyCashApprovalController::class, 'showDetailedLogTransactions'])->name('petty-cash-approvals.log-transactions');

    Route::post('petty-cash-approvals-recalculate', [PettyCashApprovalController::class, 'recalculateIncetives'])->name('petty-cash-approvals.recalculate');

    // Route::get('/fuel-purchase-orders/pending', [FuelLPOController::class, 'showPending'])->name('fuel-lpos.pending');

    Route::get('/competing-brands', [CompetingBrandsController::class, 'index'])->name('competing-brands.index');
    Route::get('/competing-brands/create', [CompetingBrandsController::class, 'create'])->name('competing-brands.create');
    Route::post('/competing-brands/store', [CompetingBrandsController::class, 'store'])->name('competing-brands.store');
    Route::get('/competing-brands/edit/{id}', [CompetingBrandsController::class, 'edit'])->name('competing-brands.edit');
    Route::post('/competing-brands/update/{id}', [CompetingBrandsController::class, 'update'])->name('competing-brands.update');
    Route::get('/fetch-competing-brands/{id}', [CompetingBrandsController::class, 'fetchCompetingBrands'])->name('fetch-competing-brands');
    Route::get('competing-brands/items/{competingBrandsId}', [CompetingBrandsController::class, 'completedBrandsItems'])->name('completedBrandsItems');

    Route::get('/competing-brands/listing', [CompetingBrandsReportController::class, 'listing'])->name('competing-brands.listing');
    Route::get('/competing-brands/details/{id}', [CompetingBrandsReportController::class, 'details'])->name('competing-brands.details');
    Route::get('/competing-brands/{brandId}/table-data', [CompetingBrandsReportController::class, 'tableData']);
    Route::get('/competing-brands/{brandId}/sales-data', [CompetingBrandsReportController::class, 'piechart']);
    Route::get('/competing-brands/{itemId}/table-data-details', [CompetingBrandsReportController::class, 'tableDataDetails']);
    Route::post('/competing-brands/upload-excel', [CompetingBrandsController::class, 'uploadExcel'])->name('upload-excel');

    //sales vs moves
    Route::get('/sales-vs-moves-report', [DailySalesAndMovesSummaryController::class, 'index'])->name('salesVsMovesReport.index');



    // Schema
    Route::get('/get-my-schema', [\App\Http\Controllers\Admin\SchemaController::class, 'index']);
    Route::get('/fetch-my-schema', [\App\Http\Controllers\Admin\SchemaController::class, 'fetch_schema'])->name('get-my-schema.fetch_data');
    // Route::get('/fetch-my-schema/{name}/{branch}/{from}/{to}', [\App\Http\Controllers\Admin\SchemaController::class, 'fetch_schema'])->name('get-my-schema.fetch_data');

    Route::get('/procurement-dashboard', [ProcurementDashboardController::class, 'index'])->name('procurement-dashboard.index');
    Route::get('/procurement-dashboard/lpo-stats', [ProcurementDashboardController::class, 'lpoStats'])->name('procurement-dashboard.lpo-stats');
    Route::get('/procurement-dashboard/stock-stats', [ProcurementDashboardController::class, 'stockStats'])->name('procurement-dashboard.stock-stats');
    Route::get('/procurement-dashboard/supplier-stats', [ProcurementDashboardController::class, 'supplierStats'])->name('procurement-dashboard.supplier-stats');
    Route::get('/procurement-dashboard/purchases-vs-sales', [ProcurementDashboardController::class, 'purchasesVsSales'])->name('procurement-dashboard.purchases-vs-sales');
    Route::get('/procurement-dashboard/stock-value', [ProcurementDashboardController::class, 'stockValue'])->name('procurement-dashboard.stock-value');
    Route::get('/procurement-dashboard/delivery-schedule', [ProcurementDashboardController::class, 'deliverySchedule'])->name('procurement-dashboard.delivery-schedule');
    Route::get('/procurement-dashboard/supplier-balances', [ProcurementDashboardController::class, 'supplierBalances'])->name('procurement-dashboard.supplier-balances');
    Route::get('/procurement-dashboard/supplier-information', [ProcurementDashboardController::class, 'supplierInformation'])->name('procurement-dashboard.supplier-information');
    Route::get('/procurement-dashboard/discounts', [ProcurementDashboardController::class, 'discounts'])->name('procurement-dashboard.discounts');
    Route::get('/procurement-dashboard/returns', [ProcurementDashboardController::class, 'returns'])->name('procurement-dashboard.returns');

    Route::get('/procurement-dashboard/turnover-purchases', [ProcurementDashboardController::class, 'turnoverPurchases'])->name('procurement-dashboard.turnover-purchases');
    Route::get('/procurement-dashboard/turnover-sales', [ProcurementDashboardController::class, 'turnoverSales'])->name('procurement-dashboard.turnover-sales');
});

Route::prefix('parking-lists')->name('storekeeper.')->middleware('AdminLoggedIn')->group(function () {
    Route::get('parking-list', [ParkingListController::class, 'index'])->name('parking.list');
    Route::get('parking-list-item/{itemId}', [ParkingListController::class, 'parkingListItem']);
    Route::get('parking-list-item/{listItem}/{storeid}', [ParkingListController::class, 'parkingListItemStore']);
    Route::get('parking-list-shift-details/{shiftId}/{itemId}', [ParkingListController::class, 'parkingListShiftDetails']);
    Route::post('dispatch-item', [ParkingListController::class, 'storeDispatchedItem'])->name('store-dispatch-item');
});

//route return summary report
Route::get('admin/route-reeturn-summary-report', [RouteReturnSummarryReportController::class, 'index'])->name('route-returns-summary-report');

// discounts
Route::group(['prefix' => 'admin'], function () {
    Route::get('/discounts-list/{itemId}', [ItemDiscountController::class, 'index'])->name('discounts.listing');
    Route::get('/discount-bands/create/{itemId}', [ItemDiscountController::class, 'create'])->name('discount-bands.create');
    Route::post('/discount-bands/create/{itemId}', [ItemDiscountController::class, 'store'])->name('discount-bands.store');
    Route::get('/discount-bands/edit/{discountBandId}', [ItemDiscountController::class, 'edit'])->name('discount-bands.edit');
    Route::post('/discount-bands/edit/{discountBandId}', [ItemDiscountController::class, 'update'])->name('discount-bands.update');
    Route::post('/discount-bands/approve/{discountBandId}', [ItemDiscountController::class, 'approve'])->name('discount-bands.approve');
    Route::delete('discount-bands/delete/{discountBandId}', [ItemDiscountController::class, 'delete'])->name('discount-bands.delete');
    Route::get('/items-with-discounts', [ItemDiscountController::class, 'itemsWithDiscountsReport'])->name('items-with-discounts-reports');
    Route::get('/discount-sales-report', [ItemDiscountController::class, 'discountSalesReport'])->name('discount-sales-report');
});

Route::group(['prefix' => 'admin'], function () {
    Route::get('/promotions-list/{itemId}', [ItemPromotionsController::class, 'index'])->name('promotions.listing');
    Route::get('/promotions-bands/create/{itemId}', [ItemPromotionsController::class, 'create'])->name('promotions-bands.create');
    Route::post('/promotions-bands/create/{itemId}', [ItemPromotionsController::class, 'store'])->name('promotions-bands.store');
    Route::get('/promotions-bands/edit/{promotionId}', [ItemPromotionsController::class, 'edit'])->name('promotions-bands.edit');
    Route::post('/promotions-bands/edit/{promotionId}', [ItemPromotionsController::class, 'update'])->name('promotions-bands.update');
    // Route::post('/promotions-bands/approve/{promotionsBandId}', [ItemPromotionsController::class, 'approve'])->name('promotions-bands.approve');
    Route::delete('/promotions-bands/delete/{promotionId}', [ItemPromotionsController::class, 'destroy'])->name('promotions-bands.delete');
    Route::get('/promotions-bands/block/{promotionId}', [ItemPromotionsController::class, 'block'])->name('promotions-bands.block');
    Route::get('/promotions-bands/unblock/{promotionId}', [ItemPromotionsController::class, 'unblock'])->name('promotions-bands.unblock');
    Route::get('/items-with-promotions', [ItemPromotionsController::class, 'itemsWithPromotionsReport'])->name('items-with-promotions-reports');
    Route::get('/promotion-sales-report', [ItemPromotionsController::class, 'promotionSalesReport'])->name('sales-and-receivables-reports.promotion-sales-report');
});
//Route Pricing
Route::group(['prefixx' => 'admin'], function () {
    Route::get('/route-pricing-list/{itemId}', [RoutePricingController::class, 'index'])->name('route.pricing.listing');
    Route::get('/route-pricing/create/{itemId}', [RoutePricingController::class, 'create'])->name('route.pricing.create');
    Route::post('/route-pricing/create/{itemId}', [RoutePricingController::class, 'store'])->name('route.pricing.store');
    Route::get('/get-routes-by-branch', [RoutePricingController::class, 'getRoutesByBranch'])->name('get.routes.by.branch');
    Route::get('/route-pricing/edit/{itemId}/{pricingId}', [RoutePricingController::class, 'edit'])->name('route.pricing.edit');
    Route::post('/route-pricing/edit/{pricingId}', [RoutePricingController::class, 'update'])->name('route.pricing.update');
});
//return reasons
Route::group(['prefixx' => 'admin'], function () {
    Route::resource('/return-reasons', ReturnReasonController::class);
});

// loaders
Route::resource('/loaders', LoaderController::class);
Route::get('reports/slow-moving-items-report', [ReportsController::class, 'slowMovingItems'])->name('reports.slow_moving_items_report');

//duplicate route customers onboarding
Route::get('/duplicate-route-customers', [DuplicateCustomerRequestsController::class, 'index'])->name('duplicate-route-customers');
Route::get('/duplicate-route-customers/{id}', [DuplicateCustomerRequestsController::class, 'show'])->name('duplicate-route-customers.show');
Route::get('/duplicate-route-customers/approve/{id}', [DuplicateCustomerRequestsController::class, 'approve'])->name('duplicate-route-customers.approve');
Route::get('/duplicate-route-customers/reject/{id}', [DuplicateCustomerRequestsController::class, 'reject'])->name('duplicate-route-customers.reject');


//delivery  reports
Route::get('dispatch-reports/delivery-report', [DeliveryReportController::class, 'index'])->name('dispatch-reports.shift-delivery-report');

//child vs mother qoh
Route::get('/child-vs-mother-qoh', [ChildVsMotherQoh::class, 'generate'])->name('child-vs-mother-qoh');
Route::get('/child-vs-mother/download', [ChildVsMotherQoh::class, 'downloadChildVsMotherQoh'])->name('child-vs-mother-download');
Route::get('report/missing-split', [ChildVsMotherQoh::class, 'missingSplit'])->name('report.missingsplit-report');

Route::get('report/ctn-no-child', [ChildVsMotherQoh::class, 'motherNoChildReport'])->name('report.ctn-no-child');

//wallet
Route::resource('/wallet-matrix', WalletMatrixController::class);
Route::get('/maintain-wallet', [MaintainWalletsController::class, 'index'])->name('maintain-wallet.index');
Route::get('/maintain-wallet/transactions/{employeeId}', [MaintainWalletsController::class, 'viewWalletTransactions'])->name('maintain-wallet.viewWalletTransactions');

//module dashboards
Route::get('/dashboad/sales-and-receivables', [SalesAndReceivablesDashboardController::class, 'index'])->name('sales-and-receivables-dashboard');
Route::get('/get-sales-data/monthly', [SalesAndReceivablesDashboardController::class, 'getSalesTransactions'])->name('get-sales-data-monthly');

// sales per supplier
Route::get('/sales-per-supplier-report', [SalesPerSupplierPerRouteReportController::class, 'index'])->name('sales-per-supplier-per-route');
Route::get('/sales-analysis', [SalesAnalysisReportController::class, 'index'])->name('sales-analysis-report');
Route::get('/daily-sales-margin', [SalesAnalysisReportController::class, 'dailySalesMargin'])->name('daily-sales-margin');
Route::get('/daily-sales-margin/download', [SalesAnalysisReportController::class, 'dailySalesMarginDownload'])->name('daily-sales-margin-download');
Route::get('/suppliers-user-report', [ReportsController::class, 'supplierUserReport'])->name('reports.supplier_user_report');

//itemlist report 

Route::get('report/items-list-report', [ItemListReportController::class, 'index'])->name('reports.items_list_report');
//subdistributor report

Route::get('report/sub-distributor-suppliers-report', [SubDistributorReport::class, 'index'])->name('reports.sub_distributor_report');
Route::post('/subsupplier/{keyId}', [SubDistributorReport::class, 'destroy'])->name('removeSubsupplier');
//no supplier items report
Route::get('report/no-supplier-items-report', [NoSupplierItemsReportController::class, 'index'])->name('reports.no_supplier_items_report');

//log in activity
Route::get('admin/user-login-activity-report', [LogInActivityController::class, 'index'])->name('user-login-activity-report');

//log in activity
Route::get('admin/user-login-activity-report', [LogInActivityController::class, 'index'])->name('user-login-activity-report');

//onsite vs offsite  shift requests
Route::get('admin/onsite-vs-offsite-shift-report', [OnsiteVsOffsiteShiftReportController::class, 'index'])->name('onsite-vs-offsite-shifts-report');

//Global methods
Route::get('/admin/get-branch-uoms', [GlobalMethodsController::class, 'getBranchUoms'])->name('admin.get-branch-uoms');
Route::get('/admin/get-branch-routes', [GlobalMethodsController::class, 'getBranchRoutes'])->name('admin.get-branch-routes');
Route::get('/admin/get-branch-vehicles', [GlobalMethodsController::class, 'getBranchVehicles'])->name('admin.get-branch-vehicles');
Route::post('/admin/get-branch-vehicles/command', [GlobalMethodsController::class, 'controlAction'])->name('admin.get-branch-vehicles.control');
// });


/*operation shift*/
Route::group(['prefix' => 'admin', 'middleware' => 'AdminLoggedIn'], function () {
    Route::get('/end_of_day_operation', [\App\Http\Controllers\Admin\OperationShiftController::class, 'index'])->name('operation_shifts.index');
    Route::get('/end_of_day_operation/{id}', [\App\Http\Controllers\Admin\OperationShiftController::class, 'show'])->name('operation_shifts.show');
    Route::post('/end_of_day_operation/{id}/override', [\App\Http\Controllers\Admin\OperationShiftController::class, 'override'])->name('operation_shifts.override');
    Route::post('/end_of_day_operation/rerun/{id}', [\App\Http\Controllers\Admin\OperationShiftController::class, 'rerun'])->name('operation_shifts.rerun');
});

/*Number Seriews Report */
Route::group(['prefix' => 'admin', 'middleware' => 'AdminLoggedIn'], function () {
    Route::get('/missing-invoice-series-numbers', [\App\Http\Controllers\Admin\Reports\NumberSeriesReportController::class, 'missingInvoices'])->name('number-series-report.invoices-missing');
});


//Cashier Management
Route::group(['prefix' => 'admin', 'middleware' => ['AdminLoggedIn', 'operation-shift-balanced']], function () {
    Route::get('/cashiers', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'index'])->middleware('branch-close')->name('cashier-management.index');
    Route::get('/cashiers/all', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'allcashiers'])->name('cashier-management.all');
    Route::post('/cashiers/update/drop-limit', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'updateDropLimit'])->name('cashier-management.updateDropLimit');
    Route::get('/show-cashier-drops/{user}', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'showCashier'])->name('cashier-management.cashier');
    Route::post('/drop-cash', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'dropCash'])->name('cashier-management.drop');
    Route::get('/drop-cash-pdf/{id}', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'downloadDropReceipt'])->name('cashier-management.downloadDropReceipt');
    Route::get('/drop-cash-transactions', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'allTransactions'])->name('cashier-management.transactions');
    Route::get('/cashier-tender-transactions/{id}', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'cashierTransaction'])->name('cashier-management.tender-transactions');
    Route::post('/cashier-declaration/{id}', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'declare'])->name('cashier-management.cashier-declare');
    Route::get('/cashier-sales', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'cashierSales'])->name('cashier-management.cashier-sales');
    Route::get('/cashier-returns', [\App\Http\Controllers\Admin\CasheirManagementController::class, 'cashierReturns'])->name('cashier-management.cashier-returns');

    Route::get('/chief-cashier-cash-pdf/{id}', [\App\Http\Controllers\Admin\EndOfDayUtilityController::class, 'downloadCashReceipt'])->name('cashier-management.downloadCashReceipt');

    Route::get('/double-transactions', [\App\Http\Controllers\Admin\DoubleEntryController::class, 'index'])->name('tender-entry.index');
    Route::get('/double-transactions-by-channels', [\App\Http\Controllers\Admin\DoubleEntryController::class, 'byChannel'])->name('tender-entry.transactions-by-channel');
    Route::get('/channels-summery', [\App\Http\Controllers\Admin\DoubleEntryController::class, 'sumery'])->name('tender-entry.channels-summery');
    Route::get('/pos-cash-sale/waiting-slip/{id}', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'waitingSlip'])->name('pos-cash-sales.waiting-slip');
    Route::get('/pos-cash-sale/check-payment', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'checkPayment'])->name('pos-cash-sales.check-payment');
    Route::get('/pos-cash-sale/utilized-payments', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'utilisedPayment'])->name('pos-cash-sales.utilised-payment');
    Route::get('/pos-cash-sale/expired-payments', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'expiredPayment'])->name('pos-cash-sales.expired-payment');
    Route::get('/pos-cash-sale/dispatch-slip/{id}', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'dispatchSlip'])->name('pos-cash-sales.dispatch-slip');
    Route::get('/pos-cash-sale/dispatch-slip/display/{id}', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'displayDispatchSlip'])->name('pos-cash-sales.display.dispatch-slip');
    Route::post('/pos-cash-sale/archive-pending-orders', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'archivePending'])->name('pos-cash-sales.archive-pending');

    Route::get('pos-cash-sale/search-inventory', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'searchInventory'])->name('pos-cash-sales.search-inventory');
    Route::get('pos-cash-sale/search-sale', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'searchSales'])->name('pos-cash-sales.search-sale');

    /*Archived orders report*/
    Route::get('/pos-cash-sale/archive-pending-orders-report', [\App\Http\Controllers\Admin\ArchivedOrdersReportController::class, 'index'])->name('pos-cash-sales.archive-report');
    Route::get('/pos-cash-sale/archive-pending-orders-report/show/{id}', [\App\Http\Controllers\Admin\ArchivedOrdersReportController::class, 'show'])->name('pos-cash-sales.archive-report.show');

    Route::get('/pos-cash-sale/stale-orders', [\App\Http\Controllers\Admin\CashSaleOrderController::class, 'index'])->name('pos-cash-sales.stale-orders');

    /*reports*/
    Route::get('/sales-and-receivables/reports/pos-overview', [\App\Http\Controllers\Admin\pos\PosOverviewReportController::class, 'index'])->name('pos-cash-sales.overview');
    Route::get('/sales-and-receivables/reports/pos-overview/sales/{startDate}/{endDate}/{branch}', [\App\Http\Controllers\Admin\pos\PosOverviewReportController::class, 'posSales'])->name('pos-cash-sales.overview.posSales');
    Route::get('/sales-and-receivables/reports/pos-overview/returns/{startDate}/{endDate}/{branch}', [\App\Http\Controllers\Admin\pos\PosOverviewReportController::class, 'posReturns'])->name('pos-cash-sales.overview.posReturns');
    Route::get('/sales-and-receivables/pos-payments', [\App\Http\Controllers\Admin\PosPaymentsConsumptionController::class, 'index'])->name('cashier-management.pos-payments-consumption');
    Route::get('/sales-and-receivables/pos-payments/manual-allocation', [\App\Http\Controllers\Admin\AllocatePaymentsController::class, 'index'])->name('manually-allocate-pos-payments');
    Route::get('/sales-and-receivables/pos-payments/fetch-sales', [\App\Http\Controllers\Admin\AllocatePaymentsController::class, 'getCashSales'])->name('manually-allocate-pos-payments.fetch-sales');
    Route::post('/sales-and-receivables/pos-payments/manual-allocation/process', [\App\Http\Controllers\Admin\AllocatePaymentsController::class, 'processManualAllocation'])->name('manually-allocate-pos-payments.process');
});

Route::get('pos-cash-sales/customer-view/unguarded', [\App\Http\Controllers\Admin\PosCashSalesController::class, 'customerViewUnguarded'])->name('pos-cash-sales.customer-view.unguarded');

Route::group(['prefix' => 'admin', 'middleware' => ['AdminLoggedIn']], function () {
    Route::get('/promotion-types', [\App\Http\Controllers\Admin\Inventory\PromotionTypeController::class, 'index'])->name('promotion-types');
    Route::post('/promotion-types', [\App\Http\Controllers\Admin\Inventory\PromotionTypeController::class, 'store'])->name('promotion-types.store');
    Route::put('/promotion-types/{id}', [\App\Http\Controllers\Admin\Inventory\PromotionTypeController::class, 'update'])->name('promotion-types.update');
    Route::delete('/promotion-types/{id}', [\App\Http\Controllers\Admin\Inventory\PromotionTypeController::class, 'destroy'])->name('promotion-types.delete');

    Route::resource('/promotion-group', \App\Http\Controllers\Admin\Inventory\PromotionGroupController::class);
    Route::resource('/active-promotions', \App\Http\Controllers\Admin\Inventory\ActivePromotionsController::class);

    /*show active promotions and discosunts*/
    Route::get('/active-promotion-discounts', [\App\Http\Controllers\Admin\pos\ActiveDiscountsPromotionsController::class, 'index'])->name('active-discounts-promotions');

    Route::resource('/hampers', \App\Http\Controllers\Admin\Inventory\HamperPromotionsController::class);
    Route::get('/hampers/search/inventoryDropdown', [\App\Http\Controllers\Admin\Inventory\HamperPromotionsController::class, 'inventoryDropdown'])->name('hampers.inventoryDropdown.search');
    Route::get('/hampers/search/suppliers', [\App\Http\Controllers\Admin\Inventory\HamperPromotionsController::class, 'suppliers'])->name('hampers.suppliers.search');
});

require_once('modules/hr.php');

require __DIR__ . '/creditSales.php';



//Route::get('/getdata', function () {
//    ini_set('memory_limit', '-1');

//    $DbName = env('DB_DATABASE');
//    $get_all_table_query = "SHOW TABLES ";
//    $result = DB::select(DB::raw($get_all_table_query));
//
//    $prep = "Tables_in_$DbName";
//    foreach ($result as $res) {
//        $tables[] = $res->$prep;
//    }
//
//
//    $connect = DB::connection()->getPdo();
//
//    $get_all_table_query = "SHOW TABLES";
//    $statement = $connect->prepare($get_all_table_query);
//    $statement->execute();
//    $result = $statement->fetchAll();
//
//
//    $output = '';
//    $tables = ["wa_stock_moves", 'wa_
//    grns', 'wa_gl_trans', 'wa_customers', "wa_inventory_items", "users"];
//    //	echo "<pre>"; print_r($tables); die;
//    foreach ($tables as $table) {
//        $show_table_query = "SHOW CREATE TABLE " . $table . "";
//        $statement = $connect->prepare($show_table_query);
//        $statement->execute();
//        $show_table_result = $statement->fetchAll();
//        //	echo "<pre>"; print_r($show_table_result); die;
//        foreach ($show_table_result as $show_table_row) {
//            if (isset($show_table_row["Create Table"])) {
//                $output .= "\n\n" . $show_table_row["Create Table"] . ";\n\n";
//            }
//        }
//        $select_query = "SELECT * FROM " . $table . "";
//        $statement = $connect->prepare($select_query);
//        $statement->execute();
//        $total_row = $statement->rowCount();
//
//        for ($count = 0; $count < $total_row; $count++) {
//            $single_result = $statement->fetch(\PDO::FETCH_ASSOC);
//            $table_column_array = array_keys($single_result);
//            $table_value_array = array_values($single_result);
//            $output .= "\nINSERT INTO $table (";
//            $output .= "" . implode(", ", $table_column_array) . ") VALUES (";
//            $output .= "'" . implode("','", $table_value_array) . "');\n";
//        }
//    }
//    $file_name = 'public/uploads/database_backup_on_' . date('y-m-d') . '.sql';
//    $file_handle = fopen($file_name, 'w+');
//    fwrite($file_handle, $output);
//    fclose($file_handle);
//    header('Content-Description: File Transfer');
//    header('Content-Type: application/octet-stream');
//    header('Content-Disposition: attachment; filename=' . basename($file_name));
//    header('Content-Transfer-Encoding: binary');
//    header('Expires: 0');
//    header('Cache-Control: must-revalidate');
//    header('Pragma: public');
//    header('Content-Length: ' . filesize($file_name));
//    ob_clean();
//    flush();
//    readfile($file_name);
//    unlink($file_name);
//
//});
//
