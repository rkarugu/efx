<?php

use App\Http\Controllers\Admin\BinLocationController;
use App\Http\Controllers\Admin\ChartsOfAccountController;
use App\Http\Controllers\Admin\CustomerEquityPaymentController;
use App\Http\Controllers\Admin\CustomerKcbPaymentController;
use App\Http\Controllers\Admin\CustomerPaymentController;
use App\Http\Controllers\Admin\DeliveryCenterController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\InventoryCategoryController;
use App\Http\Controllers\Admin\InventoryItemController;
use App\Http\Controllers\Admin\InventoryItemSupplierDataController;
use App\Http\Controllers\Admin\InventorySubCategoryController;
use App\Http\Controllers\Admin\NewFuelEntryController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\PosCustomerController;
use App\Http\Controllers\Admin\PriceListController;
use App\Http\Controllers\Admin\PurchaseDiscountController;
use App\Http\Controllers\Admin\RestaurantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\SalesmanStatementController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\SupplierTradeAgreementController;
use App\Http\Controllers\Admin\TelematicsDeviceController;
use App\Http\Controllers\Admin\StockCountsController;
use App\Http\Controllers\Admin\TaxManagerController;
use App\Http\Controllers\Admin\UserFingerprintController;
use App\Http\Controllers\Admin\UserSupplierController;
use App\Http\Controllers\Admin\VehicleMakeController;
use App\Http\Controllers\Admin\VehicleTelematicsDataController;
use App\Http\Controllers\Admin\VehicleTypeController;
use App\Http\Controllers\Admin\VendorCentreController;
use App\Http\Controllers\Api\RoutesApiController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\CleanupController;
use App\Http\Controllers\HandleExistingDemandsController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\Shifts\SalesAndDeliveryShiftController;
use App\Http\Controllers\Shifts\ShiftTypeController;
use App\Http\Controllers\Shifts\WaShiftController;
use App\Http\Controllers\TelematicsDataController;
use App\Http\Controllers\UserPettyCashTransactionController;
use App\Model\WaPosCashSales;
use App\Http\Controllers\Admin\FuelStationController;
use App\Http\Controllers\Admin\NInventoryLocationTransferController;
use App\Http\Controllers\Admin\PettyCashRequestTypeController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PendingGrnController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DeliveryScheduleController;
use App\Http\Controllers\StoreKeeper\ParkingListController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\PettyCashRequestController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Shared\RouteCustomerController;
use App\Http\Controllers\Admin\ReturnToSupplierController;
use App\Http\Controllers\Admin\SupplierOverviewController;
use App\Http\Controllers\Shared\SalesManShiftController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\VehicleModelController;
use App\Http\Controllers\Admin\VehicleCentreContoller;
use App\Http\Controllers\Admin\DeviceCenterController;
use App\Http\Controllers\Admin\DeviceRepairController;

Route::group(['prefix' => 'api', 'middleware' => ['AdminLoggedIn', 'ip-blocker']], function () {
    Route::post('/delivery-schedules/assign-vehicle', [DeliveryScheduleController::class, 'assignVehicle']);

    Route::group(['prefix' => 'vehicle-makes'], function () {
        Route::get('/', [VehicleMakeController::class, 'getVehicleMakes']);
    });

    Route::group(['prefix' => 'trade-agreements'], function () {
        Route::get('/', [SupplierTradeAgreementController::class, 'getAgreements']);
        Route::post('/add', [SupplierTradeAgreementController::class, 'addAgreement']);

        Route::group(['prefix' => 'discounts'], function () {
            Route::get('/get-global-discounts', [PurchaseDiscountController::class, 'getAgreements']);
            Route::post('/add', [SupplierTradeAgreementController::class, 'addAgreement']);
        });
    });

    Route::group(['prefix' => 'user-suppliers'], function () {
        Route::get('/', [UserSupplierController::class, 'getUserSuppliers']);
        Route::post('/add', [UserSupplierController::class, 'addUserSupplier']);
        Route::post('/remove', [UserSupplierController::class, 'deallocateUserSupplier']);
    });

    Route::get('/handle-existing-demands', [HandleExistingDemandsController::class, 'handleDemands'])->name('handleDemands');

    Route::group(['prefix' => 'alerts'], function () {
        Route::get('/all', [AlertController::class, 'getAlerts']);
        Route::get('/saved', [AlertController::class, 'getSavedAlerts']);
        Route::post('/update', [AlertController::class, 'update']);
    });

    Route::get('/get-onboarding-report', [RouteCustomerController::class, 'getOnboardingReport']);
    Route::get('/update-item-images', [InventoryItemController::class, 'updateImagesManually']);
    Route::get('/remove-empty-sub-categories', [InventorySubCategoryController::class, 'removeEmptySubCategories']);
    Route::get('/export-phone-duplicates', [RouteCustomerController::class, 'exportPhoneDuplicates']);
    Route::get('/export-name-duplicates', [RouteCustomerController::class, 'exportNameDuplicates']);
    Route::get('/clean-item-suppliers', [InventoryItemSupplierDataController::class, 'removeMissingItems']);
    Route::get('/clean-up', [CleanupController::class, 'cleanup']);
    Route::get('/recalc-qoh', [CleanupController::class, 'recalcNewQoH']);
    Route::get('/vehicle-telematics', [CleanupController::class, 'vehicleTelematics']);
    Route::get('/location-report', [CleanupController::class, 'locationReport']);
    Route::get('/bulk-resign', [CleanupController::class, 'bulkResign']);
    Route::get('/misc', [CleanupController::class, 'misc']);
    Route::get('/route-numbers', [CleanupController::class, 'getRouteNumbers']);
    Route::get('/route-customer-export-all', [RouteCustomerController::class, 'exportAll'])->name('route-customers.export-all');
    Route::get('/route-customer-export-all-route-customers', [RouteCustomerController::class, 'exportAllRouteCustomers'])->name('route-customers.export-all-route-customers');

    Route::get('/route-customer-export-mapped', [RouteCustomerController::class, 'exportAllMapped']);
    Route::get('/geo-map', [RouteCustomerController::class, 'updateEstimates']);
    Route::get('/max-stock-report', [InventoryItemController::class, 'getMaxStockData'])->name('inventory-reports.max-stock-report');


    Route::get('/grns-list', [ReturnToSupplierController::class, 'grnsList']);
    Route::get('/grn-line-items', [ReturnToSupplierController::class, 'grnLineItems']);
    Route::get('location-and-stores/{branch_id}', [ReturnToSupplierController::class, 'locationAndStoresByBranch']);
    Route::get('location-store-uom/{locationId}', [ReturnToSupplierController::class, 'locationStoreUoM']);
    Route::get('suppliers-by-uom/{uomId}', [ReturnToSupplierController::class, 'suppliersByUoM']);
    Route::get('/supplier-items/{supplierId}', [ReturnToSupplierController::class, 'supplierItems']);

    Route::post('/process-return-from-grn', [ReturnToSupplierController::class, 'processReturnFromGrn']);
    Route::post('/approve-return-from-grn', [ReturnToSupplierController::class, 'approveReturnFromGrn']);
    Route::post('/reject-return-from-grn', [ReturnToSupplierController::class, 'rejectReturnFromGrn']);
    Route::post('/process-return-from-store', [ReturnToSupplierController::class, 'processReturnFromStore']);
    Route::post('/approve-return-from-store/{id}', [ReturnToSupplierController::class, 'approveReturnFromStore']);
    Route::post('/reject-return-from-store/{id}', [ReturnToSupplierController::class, 'rejectReturnFromStore']);

    Route::get('/supplier-return-demands/{id}', [ReturnToSupplierController::class, 'supplierReturnDemands']);
    Route::post('process-demand-from-grn', [ReturnToSupplierController::class, 'processDemandFromGrn']);
    Route::post('approve-demand-from-grn', [ReturnToSupplierController::class, 'approveDemandFromGrn']);
    Route::post('process-demand-from-store', [ReturnToSupplierController::class, 'processDemandFromStore']);
    Route::post('approve-demand-from-store', [ReturnToSupplierController::class, 'approveDemandFromStore']);

    Route::post('edit-return-demand/{returnDemand}', [ReturnToSupplierController::class, 'editReturnDemand']);
    Route::post('approve-return-demand/{returnDemand}', [ReturnToSupplierController::class, 'approveReturnDemand']);
    Route::post('edit-and-approve-return-demand/{returnDemand}', [ReturnToSupplierController::class, 'editAndApproveReturnDemand']);
    Route::post('convert-return-demand/{returnDemand}', [ReturnToSupplierController::class, 'convertReturnDemand']);

    Route::post('edit-price-demand/{priceDemand}', [ReturnToSupplierController::class, 'editPriceDemand']);
    Route::post('approve-price-demand/{priceDemand}', [ReturnToSupplierController::class, 'approvePriceDemand']);
    Route::post('edit-and-approve-price-demand/{priceDemand}', [ReturnToSupplierController::class, 'editAndApprovePriceDemand']);
    Route::post('convert-price-demand/{priceDemand}', [ReturnToSupplierController::class, 'convertPriceDemand']);

    Route::get('/supplier-price-demands/{id}', [ReturnToSupplierController::class, 'supplierPriceDemands']);
    Route::post('process-price-demand', [ReturnToSupplierController::class, 'processPriceDemand']);
    Route::post('approve-price-demand', [ReturnToSupplierController::class, 'approvePriceDemand']);

    Route::post('merge-price-demands', [ReturnToSupplierController::class, 'mergePriceDemands']);

    // Vendor Centre
    Route::get('turnover-purchases/{supplier_d}', [VendorCentreController::class, 'turnoverPurchases']);
    Route::get('turnover-sales/{supplier_d}', [VendorCentreController::class, 'turnoverSales']);

    // Supplier Overview
    Route::get('supplier-overview-sales/{user_d}', [SupplierOverviewController::class, 'userSuppliersSales']);
    Route::get('supplier-sales-by-category/{user_id}', [SupplierOverviewController::class, 'userSuppliersCategorySales']);

    // Order Taking
    // Delivery Schedules
    Route::group(['prefix' => 'order-taking-schedule'], function () {
        Route::get('/get-list', [SalesManShiftController::class, 'getScheduleList']);
        Route::get('/get-summary', [SalesManShiftController::class, 'getScheduleSummary']);
        Route::get('/get-targets-vs-actuals', [SalesManShiftController::class, 'getTargetsVsActuals']);
    });

    // Dispatch
    // Delivery Schedules
    Route::group(['prefix' => 'store-loading-sheets'], function () {
        Route::post('/dispatch', [ParkingListController::class, 'processDispatch']);
    });
 
    // Delivery Schedules
    Route::group(['prefix' => 'delivery-schedules'], function () {
        Route::get('/active', [DeliveryScheduleController::class, 'getActiveSchedules']);
        Route::get('/filter-active', [DeliveryScheduleController::class, 'getFilteredctiveSchedules']);
        Route::get('/available-vehicles', [DeliveryScheduleController::class, 'getAvailableVehicles']);
        Route::post('/delivery-schedules/assign-vehicle', [DeliveryScheduleController::class, 'assignVehicle']);
        Route::get('/unassign-vehicle/{id}', [DeliveryScheduleController::class, 'unassignVehicle'])->name('delivery-schedules.unassignvehicles');
        Route::post('/check-geo-fence', [DeliveryScheduleController::class, 'checkGeoFence']);
    });

    // Vehicles
    Route::group(['prefix' => 'vehicles'], function () {
        Route::get('/all', [VehicleController::class, 'getAllVehicles']);
        Route::get('/available', [VehicleController::class, 'getAvailableVehicles']);
        Route::get('/available-drivers', [VehicleController::class, 'getAvailableDrivers']);
        Route::get('/available-turnboys', [VehicleController::class, 'getAvailableTurnboys']);
        Route::post('/store', [VehicleController::class, 'store']);
        Route::post('/update', [VehicleController::class, 'update']);
        Route::post('/assign-driver', [VehicleController::class, 'assignDriver']);
        Route::post('/assign-turnboy', [VehicleController::class, 'assignTurnboy']);
        Route::post('/unassign-driver', [VehicleController::class, 'unAssignDriver']);
        Route::post('/unassign-turnboy', [VehicleController::class, 'unAssignTurnboy']);
        Route::post('/save-service-details', [VehicleController::class, 'saveServiceDetails']);
        Route::post('/save-insurance-details', [VehicleController::class, 'saveInsuranceDetails']);
        Route::post('/switch-off', [VehicleController::class, 'switchOff']);
        Route::post('/switch-on', [VehicleController::class, 'switchOn']);
    });

    Route::group(['prefix' => 'vehicle-types'], function () {
        Route::get('/', [VehicleTypeController::class, 'getVehicleTypes']);
    });

    Route::group(['prefix' => 'vehicle-models'], function () {
        Route::get('/', [VehicleModelController::class, 'getVehicleModels']);
    });

    // Branches
    Route::group(['prefix' => 'branches'], function () {
        Route::get('/', [RestaurantController::class, 'getBranches']);
    });

    // Telematics
    Route::group(['prefix' => 'telematics'], function () {
        Route::get('/get-devices', [TelematicsDeviceController::class, 'getDevices']);
    });

    // Users
    Route::group(['prefix' => 'users'], function () {
        Route::get('/dispatchers', [UserController::class, 'getDispatchers']);
    });

    Route::get('/data', [RouteController::class, 'data']);

        // Routes
    Route::group(['prefix' => 'routes'], function () {
        Route::get('/list-for-map-view', [RouteController::class, 'getRouteListForMapView']);
        Route::get('/map-view-stats', [RouteController::class, 'getMapViewRouteStats']);
        Route::get('/get-polylines', [RouteController::class, 'getRoutePolylines']);
        Route::get('/get-centers', [RouteController::class, 'getRouteCenters']);
        Route::get('/get-shops', [RouteController::class, 'getRouteShops']);
        Route::post('/store', [RouteController::class, 'store']);
        Route::post('/update', [RouteController::class, 'update']);
    });

    Route::group(['prefix' => 'delivery-centers'], function () {
        Route::post('/store', [DeliveryCenterController::class, 'storeFromApi']);
    });

    Route::group(['namespace' => 'Admin'], function () {
        Route::get('/location-stores', 'ProductionWorkOrderController@getLocationStores');
        Route::get('/producible-products', 'ProductionWorkOrderController@getProducibleProducts');

        // Telematics
        Route::get('/telematics/devices', 'VehicleController@getDevices');

        // Fuel station attendant apis
        //    Route::get('/list-fueling-vehicles', 'NewFuelEntryController@listVehicles');
        //    Route::get('/fuel-entries', 'NewFuelEntryController@listForApi');
        //    Route::post('/fuel-entries/add', 'NewFuelEntryController@storeFromApi');

        Route::get('/vehicles/live', 'VehicleController@getDevices');

        Route::get('/routes', [RouteController::class, 'getAllRoutes']);
        Route::get('/roles', [RoleController::class, 'getAllRoles']);
        Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'getAllUsers']);
        Route::get('/departments', [DepartmentController::class, 'getAllDepartments']);
    });

    Route::get('/route-get-users-branch/{branch}', [RouteController::class, 'fetchRouteusers']);

    Route::group(['prefix' => 'vehicle-center'], function () {
        Route::get('/get-fuel-history', [VehicleCentreContoller::class, 'getFuelHistory']);
    });

    Route::group(['prefix' => 'device-center'], function () {
        Route::get('/get-device-history/{id}', [DeviceCenterController::class, 'getDeviceHistory']);
        Route::get('/get-device-users/{id}', [DeviceCenterController::class, 'getDeviceUsers']);
        Route::post('/allocate-device', [DeviceCenterController::class, 'allocateDevice']);
        Route::post('/verify-device-allocate', [DeviceCenterController::class, 'verifyDeviceAllocate']);
        Route::post('/initiate-device-return/{id}', [DeviceCenterController::class, 'initiateDeviceReturn']);
        Route::get('/get-device-sims', [DeviceCenterController::class, 'getDeviceSims']);
        Route::get('/get-device-sim/{id}', [DeviceCenterController::class, 'getDeviceSim']);
        Route::post('/allocate-device-simcard', [DeviceCenterController::class, 'allocateDeviceSim']);
        Route::post('/remove-device-simcard', [DeviceCenterController::class, 'removeDeviceSim']);
        Route::get('/get-device-repair/{id}', [DeviceRepairController::class, 'getDeviceRepair']);
        Route::post('/repair-complete', [DeviceRepairController::class, 'repairComplete']);
        
    });
});
