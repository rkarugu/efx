# Delivery Schedule Multi-Shift Feature

## Overview

The delivery schedule system has been enhanced to support **multiple salesman shifts per delivery schedule**. This allows you to consolidate orders from multiple salesmen/routes into a single delivery run, optimizing logistics and reducing costs.

---

## Key Changes

### 1. Database Schema

**New Pivot Table**: `delivery_schedule_shifts`
```sql
CREATE TABLE delivery_schedule_shifts (
    id BIGINT PRIMARY KEY,
    delivery_schedule_id BIGINT,
    salesman_shift_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (delivery_schedule_id, salesman_shift_id)
);
```

**Existing Tables**: No breaking changes
- `delivery_schedules.shift_id` is kept for backward compatibility (represents the "primary" shift)
- All shifts are tracked in the pivot table

### 2. Model Relationships

**DeliverySchedule Model**:
```php
// Old: Single shift (still works)
$schedule->shift; // Returns BelongsTo relationship

// New: Multiple shifts
$schedule->shifts; // Returns BelongsToMany relationship
```

**SalesmanShift Model**:
```php
// New: Get all delivery schedules for a shift
$shift->deliverySchedules; // Returns BelongsToMany relationship
```

### 3. Job Updates

**CreateDeliverySchedule Job**:
- Now accepts a single shift OR an array of shifts
- Automatically aggregates items and customers from all shifts
- Creates entries in the pivot table

```php
// Single shift (backward compatible)
CreateDeliverySchedule::dispatch($shift);

// Multiple shifts (new feature)
CreateDeliverySchedule::dispatch([$shift1, $shift2, $shift3]);
```

---

## API Endpoints

### Get Shifts in a Delivery Schedule
```
GET /admin/delivery-schedules/{id}/shifts
```

**Response**:
```json
{
    "schedule_id": 123,
    "delivery_number": "DS-000123",
    "shifts": [
        {
            "id": 456,
            "salesman": "John Doe",
            "route": "Route A",
            "start_time": "2025-01-03 08:00",
            "status": "close",
            "orders_count": 15,
            "total": 125000
        },
        {
            "id": 457,
            "salesman": "Jane Smith",
            "route": "Route B",
            "start_time": "2025-01-03 08:30",
            "status": "close",
            "orders_count": 12,
            "total": 98000
        }
    ]
}
```

### Add Shifts to a Delivery Schedule
```
POST /admin/delivery-schedules/{id}/shifts
Content-Type: application/json

{
    "shift_ids": [458, 459, 460]
}
```

**Response**:
```json
{
    "message": "Shifts added successfully",
    "schedule": {
        "id": 123,
        "shifts": [...]
    }
}
```

**Validation**:
- Schedule must be in `consolidating` or `consolidated` status
- Shift IDs must exist
- Duplicates are automatically skipped

**Side Effects**:
- Items and customers are recalculated
- Loading list is updated
- Tonnage is recalculated

### Remove a Shift from a Delivery Schedule
```
DELETE /admin/delivery-schedules/{id}/shifts/{shiftId}
```

**Response**:
```json
{
    "message": "Shift removed successfully",
    "schedule": {
        "id": 123,
        "shifts": [...]
    }
}
```

**Validation**:
- Schedule must be in `consolidating` or `consolidated` status
- Cannot remove the last shift
- If removing the primary shift, a new primary is automatically assigned

**Side Effects**:
- Items and customers are recalculated
- Loading list is updated
- Tonnage is recalculated

---

## Usage Examples

### Example 1: Create Delivery Schedule with Multiple Shifts

```php
use App\Jobs\CreateDeliverySchedule;
use App\SalesmanShift;

// Get closed shifts for today from the same route
$shifts = SalesmanShift::where('status', 'close')
    ->where('route_id', 5)
    ->whereDate('start_time', today())
    ->get();

// Create delivery schedule with all shifts
CreateDeliverySchedule::dispatch($shifts->toArray());
```

### Example 2: Add Shifts to Existing Schedule

```php
use App\DeliverySchedule;

$schedule = DeliverySchedule::find(123);

// Add more shifts
$schedule->shifts()->attach([458, 459]);

// Or use the controller method via API
// POST /admin/delivery-schedules/123/shifts
// {"shift_ids": [458, 459]}
```

### Example 3: Get All Orders from a Delivery Schedule

```php
use App\DeliverySchedule;
use App\Model\WaInternalRequisition;

$schedule = DeliverySchedule::with('shifts')->find(123);

// Get all shift IDs
$shiftIds = $schedule->shifts->pluck('id');

// Get all orders from all shifts
$orders = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)->get();
```

### Example 4: Check if a Shift is in a Delivery Schedule

```php
use App\SalesmanShift;

$shift = SalesmanShift::find(456);

// Check if shift has any delivery schedules
if ($shift->deliverySchedules()->exists()) {
    $schedules = $shift->deliverySchedules;
    echo "Shift is in " . $schedules->count() . " delivery schedule(s)";
}
```

---

## Migration Guide

### For Existing Code

**No changes required!** The system is backward compatible:

```php
// This still works (single shift)
$schedule->shift; // Returns the primary shift

// New feature (multiple shifts)
$schedule->shifts; // Returns all shifts
```

### For New Features

To use multiple shifts:

1. **When creating delivery schedules**:
   ```php
   // Old way (still works)
   CreateDeliverySchedule::dispatch($shift);
   
   // New way (multiple shifts)
   CreateDeliverySchedule::dispatch([$shift1, $shift2]);
   ```

2. **When querying orders**:
   ```php
   // Old way (single shift)
   $orders = WaInternalRequisition::where('wa_shift_id', $schedule->shift_id)->get();
   
   // New way (multiple shifts)
   $shiftIds = $schedule->shifts->pluck('id');
   $orders = WaInternalRequisition::whereIn('wa_shift_id', $shiftIds)->get();
   ```

3. **When calculating totals**:
   ```php
   // Old way (single shift)
   $total = $schedule->shift->shift_total;
   
   // New way (multiple shifts)
   $total = $schedule->shifts->sum('shift_total');
   ```

---

## Business Benefits

### 1. Route Consolidation
- Combine multiple salesmen from the same route into one delivery
- Reduce number of delivery trips
- Lower fuel costs

### 2. Cross-Route Optimization
- Consolidate nearby routes
- Optimize vehicle capacity utilization
- Reduce delivery time

### 3. Flexible Scheduling
- Add/remove shifts dynamically
- Adjust delivery schedules based on vehicle availability
- Handle last-minute changes

### 4. Better Reporting
- Track which shifts are in which delivery
- Analyze delivery efficiency per shift
- Identify optimization opportunities

---

## Technical Details

### Automatic Recalculation

When shifts are added or removed, the system automatically:

1. **Recalculates Items**:
   - Aggregates quantities from all shifts
   - Groups by inventory item
   - Updates `delivery_schedule_items` table

2. **Recalculates Customers**:
   - Gets unique customers from all shifts
   - Combines order IDs per customer
   - Updates `delivery_schedule_customers` table

3. **Updates Tonnage**:
   - Sums net weight from all items
   - Updates delivery schedule tonnage attribute

### Status Restrictions

You can only add/remove shifts when the delivery schedule is in:
- `consolidating` - Initial state
- `consolidated` - After consolidation, before loading

You **cannot** modify shifts when status is:
- `loaded` - Already loaded on vehicle
- `in_progress` - Delivery in progress
- `finished` - Delivery completed

### Data Integrity

- **Unique Constraint**: Prevents duplicate shift assignments
- **Foreign Keys**: Cascade deletes if shift or schedule is deleted
- **Indexes**: Optimized for fast lookups
- **Validation**: Ensures at least one shift per schedule

---

## Testing

### Test Scenarios

1. **Create with Single Shift**:
   ```php
   $shift = SalesmanShift::factory()->create();
   CreateDeliverySchedule::dispatch($shift);
   $this->assertDatabaseHas('delivery_schedule_shifts', [
       'salesman_shift_id' => $shift->id
   ]);
   ```

2. **Create with Multiple Shifts**:
   ```php
   $shifts = SalesmanShift::factory()->count(3)->create();
   CreateDeliverySchedule::dispatch($shifts->toArray());
   $this->assertEquals(3, DeliverySchedule::latest()->first()->shifts->count());
   ```

3. **Add Shifts**:
   ```php
   $schedule = DeliverySchedule::factory()->create();
   $newShifts = SalesmanShift::factory()->count(2)->create();
   $schedule->shifts()->attach($newShifts->pluck('id'));
   $this->assertEquals(3, $schedule->fresh()->shifts->count());
   ```

4. **Remove Shift**:
   ```php
   $schedule = DeliverySchedule::factory()->create();
   $schedule->shifts()->attach([1, 2, 3]);
   $schedule->shifts()->detach(2);
   $this->assertEquals(2, $schedule->fresh()->shifts->count());
   ```

---

## Troubleshooting

### Issue: "Cannot add shifts to a delivery schedule that is already in progress"

**Cause**: Trying to modify a schedule that's already loaded or in progress

**Solution**: Only modify schedules in `consolidating` or `consolidated` status

### Issue: "Cannot remove the last shift from a delivery schedule"

**Cause**: Trying to remove the only shift in a schedule

**Solution**: Delete the entire delivery schedule instead, or add another shift first

### Issue: Items/customers not updating after adding shifts

**Cause**: Recalculation not triggered

**Solution**: Use the API endpoints which automatically trigger recalculation, or manually call:
```php
$controller = new DeliveryScheduleController();
$controller->recalculateDeliverySchedule($schedule);
```

---

## Future Enhancements

### Planned Features

1. **UI for Shift Management**:
   - Drag-and-drop interface to add/remove shifts
   - Visual representation of delivery schedule composition
   - Real-time tonnage and capacity calculations

2. **Smart Consolidation**:
   - AI-powered suggestions for shift consolidation
   - Route optimization algorithms
   - Capacity-based recommendations

3. **Split Delivery Schedules**:
   - Split a multi-shift schedule into multiple deliveries
   - Rebalance loads across vehicles
   - Handle vehicle breakdowns

4. **Performance Analytics**:
   - Compare single-shift vs multi-shift deliveries
   - Cost savings reports
   - Efficiency metrics

---

## Summary

The multi-shift delivery schedule feature provides:

✅ **Backward Compatibility**: Existing code works without changes  
✅ **Flexibility**: Add/remove shifts dynamically  
✅ **Automation**: Automatic recalculation of items and customers  
✅ **Validation**: Prevents invalid operations  
✅ **Performance**: Optimized queries and indexes  
✅ **Logging**: Complete audit trail  

**Use Cases**:
- Route consolidation
- Cross-route optimization
- Last-minute schedule adjustments
- Vehicle capacity optimization
- Cost reduction

---

**Last Updated**: 2025-01-03  
**Version**: 1.0  
**Status**: Production Ready
