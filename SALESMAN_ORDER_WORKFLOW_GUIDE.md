# Salesman Order to Delivery Workflow - Complete Guide

## Overview
This document outlines the complete workflow from salesman order creation to delivery, including loading sheet generation.

## Workflow Steps

### 1. Shift Management

#### Opening a Shift
- **Route**: `POST /admin/salesman-orders/shift/open`
- **Controller**: `SalesmanOrderController@openShift`
- **Process**:
  1. Validates user is a salesman
  2. Checks for existing open shifts
  3. Creates `SalesmanShift` record with status 'open'
  4. Creates corresponding `WaShift` record for financial linkage
  5. Records start time

#### Closing a Shift
- **Route**: `POST /admin/salesman-orders/shift/close`
- **Controller**: `SalesmanOrderController@closeShift`
- **Process**:
  1. Finds active shift for salesman
  2. Sets status to 'close' and records closed_time
  3. **Dispatches `PrepareStoreParkingList` job** (generates loading sheets)
  4. **Dispatches `CreateDeliverySchedule` job** (generates delivery schedule)

### 2. Order Creation

#### Creating Orders
- **Route**: `POST /admin/salesman-orders/store`
- **Controller**: `SalesmanOrderController@store`
- **Process**:
  1. Validates salesman has active shift
  2. Validates customer and items
  3. Checks stock availability (if setting enabled)
  4. Generates requisition number (SO prefix)
  5. Creates `WaInternalRequisition` record with:
     - `wa_shift_id` linking to SalesmanShift
     - Customer details
     - Route information
     - Status: 'APPROVED'
  6. Creates `WaInternalRequisitionItem` records for each item
  7. Dispatches `PerformPostSaleActions` job (stock moves, transfers)

### 3. Loading Sheet Generation

#### Job: PrepareStoreParkingList
- **File**: `app/Jobs/PrepareStoreParkingList.php`
- **Trigger**: Automatically when shift is closed
- **Process**:
  1. **Deletes existing loading sheets** for the shift (prevents duplicates)
  2. Validates shift has salesman and location
  3. Queries all items from orders in the shift
  4. Groups items by bin location
  5. Creates `SalesmanShiftStoreDispatch` records (one per bin location)
  6. Creates `SalesmanShiftStoreDispatchItem` records in bulk
  7. Logs all operations for debugging

#### Key Features (FIXED):
- ✅ **Idempotent**: Can be run multiple times without creating duplicates
- ✅ **Bulk Insert**: Better performance for large orders
- ✅ **Proper Logging**: Tracks all operations
- ✅ **Error Handling**: Logs errors and re-throws for job retry
- ✅ **Validation**: Checks for salesman and location before processing

### 4. Loading Sheet Management

#### Viewing Loading Sheets
- **Route**: `GET /admin/store-loading-sheets`
- **Controller**: `ParkingListController@index`
- **Views**:
  - Undispatched sheets (for store keepers)
  - Dispatched sheets (historical)
  - Filtered by bin location and date

#### Dispatching Loading Sheets
- **Route**: `GET /admin/store-loading-sheets/dispatch/{id}`
- **Controller**: `ParkingListController@dispatchLoadingSheet`
- **Process**:
  1. Store keeper reviews items
  2. Marks sheet as dispatched
  3. Records dispatcher_id and dispatch time

### 5. Delivery Schedule

#### Job: CreateDeliverySchedule
- **Trigger**: Automatically when shift is closed
- **Process**:
  1. Creates delivery schedule for the shift
  2. Groups orders by customer/route
  3. Optimizes delivery sequence

## Database Schema

### Key Tables

#### salesman_shifts
- `id`: Primary key
- `salesman_id`: FK to users
- `route_id`: FK to routes
- `shift_type`: regular/offsite/etc
- `start_time`: Shift start timestamp
- `closed_time`: Shift close timestamp
- `status`: open/close

#### salesman_shift_store_dispatches
- `id`: Primary key
- `shift_id`: FK to salesman_shifts
- `store_id`: FK to wa_location_and_stores
- `bin_location_id`: FK to wa_unit_of_measures
- `dispatched`: boolean (false = pending, true = dispatched)
- `dispatcher_id`: FK to users (who dispatched)
- `created_at`, `updated_at`

#### salesman_shift_store_dispatch_items
- `id`: Primary key
- `dispatch_id`: FK to salesman_shift_store_dispatches
- `wa_inventory_item_id`: FK to wa_inventory_items
- `total_quantity`: Aggregated quantity needed
- `created_at`, `updated_at`

#### wa_internal_requisitions (Orders)
- `id`: Primary key
- `requisition_no`: Order number (SO prefix)
- `wa_shift_id`: FK to salesman_shifts
- `user_id`: Salesman ID
- `wa_route_customer_id`: FK to customer
- `status`: APPROVED/etc
- `created_at`, `updated_at`

## Common Issues & Solutions

### Issue 1: Duplicate Loading Sheets
**Problem**: Multiple loading sheets created when regenerating
**Solution**: ✅ FIXED - Job now deletes existing sheets before creating new ones

### Issue 2: Missing Loading Sheets
**Problem**: Shift closed but no loading sheets generated
**Causes**:
- Shift has no orders
- Job failed silently
- Salesman has no location assigned

**Solution**: Check logs at `storage/logs/laravel.log` for:
```
Loading Sheet skipped: Shift X has no items
Loading Sheet failed for Shift X: [error message]
```

### Issue 3: Items Not Grouped Correctly
**Problem**: Items split across multiple sheets incorrectly
**Solution**: ✅ FIXED - Items now properly grouped by bin location

## Testing & Debugging

### Manual Loading Sheet Generation
```
GET /admin/salesman-orders/generate-loading-sheets/{shiftId}
```
- Regenerates loading sheets for a specific shift
- Useful for fixing missing sheets

### Debug Endpoints
1. **Debug Loading Sheets**: `/admin/salesman-orders/debug-loading-sheets`
   - Shows recent shifts and their loading sheets
   
2. **Test Mobile Shift Closing**: `/admin/salesman-orders/test-mobile-shift-closing/{shiftId}`
   - Tests the mobile API shift closing logic

3. **Debug Entire Journey**: `/admin/salesman-orders/debug-entire-journey`
   - Shows complete workflow for recent shifts

## Permissions Required

### Salesman
- `salesman-orders___view`: View orders
- `order-taking___view`: Create orders

### Store Keeper
- `store-loading-sheet___view`: View loading sheets
- `store-loading-sheet___view-undispatched`: View pending sheets
- `store-loading-sheet___dispatch`: Mark sheets as dispatched

### Admin
- `dispatched-loading-sheets___view`: View dispatched sheets
- `dispatched-loading-sheets___view-all`: View all branches

## Performance Optimizations

### Loading Sheet Generation
- ✅ Bulk insert for dispatch items
- ✅ Single query to get all shift items
- ✅ Efficient grouping by bin location
- ✅ Proper indexing on shift_id and item_id

### Recommendations
1. Add index on `salesman_shift_store_dispatches.shift_id`
2. Add index on `salesman_shift_store_dispatch_items.dispatch_id`
3. Add composite index on `wa_internal_requisitions(wa_shift_id, status)`

## Monitoring

### Key Metrics to Monitor
1. **Loading Sheet Generation Time**: Should be < 5 seconds per shift
2. **Duplicate Sheets**: Should be 0 (now fixed)
3. **Failed Jobs**: Check queue:failed table
4. **Missing Sheets**: Shifts with status='close' but no dispatches

### Log Monitoring
Search logs for:
- `Loading Sheet generation completed`
- `Loading Sheet failed`
- `Loading Sheet skipped`

## Future Enhancements

1. **Real-time Notifications**: Alert store keepers when new loading sheets are ready
2. **Mobile App Integration**: Allow salesmen to view their loading sheets
3. **Barcode Scanning**: Scan items during dispatch for accuracy
4. **Stock Reconciliation**: Compare dispatched vs actual delivery
5. **Route Optimization**: Suggest optimal delivery sequence

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Run debug endpoints
3. Check database for orphaned records
4. Verify permissions and settings

---

**Last Updated**: 2025-01-03
**Version**: 2.0 (Fixed duplicate loading sheets issue)
