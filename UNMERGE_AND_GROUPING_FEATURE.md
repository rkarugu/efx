# Unmerge & Route Grouping Features

## Overview

Enhanced the delivery schedules interface with:
1. ✅ **Unmerge functionality** - Reverse merges and split deliveries back
2. ✅ **Route grouping** - Deliveries grouped by route for better organization
3. ✅ **View shifts** - See which shifts are in each delivery

---

## New Features

### 1. Unmerge Shifts ✅

**What it does:**
- Allows you to split a merged delivery back into separate deliveries
- Creates a new delivery schedule for the unmerged shift
- Recalculates items and customers for both deliveries

**How to use:**
1. Click the blue "list" icon (View Shifts) on any delivery
2. Modal shows all shifts in that delivery
3. Click "Unmerge" button next to any shift (except if it's the only one)
4. Confirm the action
5. New delivery schedule created automatically

**Validation:**
- ✅ Can only unmerge from consolidating/consolidated deliveries
- ✅ Cannot unmerge the only shift (must have at least 2 shifts)
- ✅ Creates new delivery schedule automatically
- ✅ Recalculates both deliveries

### 2. Route Grouping ✅

**What it does:**
- Groups delivery schedules by route name
- Shows route name as a header row
- Makes it easier to see all deliveries for a specific route

**Benefits:**
- ✅ Better visual organization
- ✅ Easy to identify deliveries by route
- ✅ Helps with route-based consolidation decisions
- ✅ Cleaner interface

### 3. View Shifts Button ✅

**What it does:**
- Shows all shifts included in a delivery schedule
- Displays shift details (ID, salesman, time, orders)
- Provides unmerge button for each shift

**Information shown:**
- Shift ID
- Salesman name
- Start time
- Number of orders
- Unmerge action (if applicable)

---

## UI Changes

### New Icons:

1. **Blue List Icon** (fa-list) - View Shifts
   - Shows which shifts are in the delivery
   - Allows unmerging

2. **Yellow Compress Icon** (fa-compress) - Merge
   - Merge another delivery into this one

### Table Layout:

**Before:**
```
| # | Date | Shift | Delivery | Route | Tonnage | Status | Driver | Actions |
|---|------|-------|----------|-------|---------|--------|--------|---------|
| 1 | ...  | ...   | DS-001   | Rt A  | 0.5     | ...    | ...    | ...     |
| 2 | ...  | ...   | DS-002   | Rt B  | 0.3     | ...    | ...    | ...     |
```

**After:**
```
|                    Route A                                                |
| # | Date | Shift | Delivery | Route | Tonnage | Status | Driver | Actions |
|---|------|-------|----------|-------|---------|--------|--------|---------|
| 1 | ...  | ...   | DS-001   | Rt A  | 0.5     | ...    | ...    | ...     |
|                    Route B                                                |
| 2 | ...  | ...   | DS-002   | Rt B  | 0.3     | ...    | ...    | ...     |
```

---

## API Endpoints

### Unmerge Shift
```bash
POST /admin/delivery-schedules/{id}/unmerge/{shiftId}

Response (Success):
{
    "message": "Shift unmerged successfully. A new delivery schedule has been created.",
    "schedule": {
        "id": 3172,
        "shifts": [...]
    }
}

Response (Error):
{
    "message": "Cannot unmerge the only shift. Delete the delivery schedule instead."
}
```

### View Shifts (Already exists)
```bash
GET /admin/delivery-schedules/{id}/shifts

Response:
{
    "schedule_id": 3172,
    "delivery_number": "DS-003172",
    "shifts": [
        {
            "id": 3196,
            "salesman": "Salesman Roy",
            "start_time": "2025-11-03 14:50",
            "orders_count": 3
        }
    ]
}
```

---

## Usage Examples

### Example 1: Merge Two Deliveries

**Scenario:** Two shifts from same route, want to consolidate

1. See both DS-003172 and DS-003175 under "Roysambu" route
2. Click yellow compress icon on DS-003172
3. Select DS-003175 from dropdown
4. Click "Merge Schedules"
5. Result: One delivery (DS-003172) with both shifts

### Example 2: View Shifts in Merged Delivery

**Scenario:** Want to see which shifts are in a delivery

1. Click blue list icon on DS-003172
2. Modal shows:
   - Shift 3196 (Salesman Roy, 3 orders)
   - Shift 3197 (Salesman Roy, 1 order)
3. Each has an "Unmerge" button

### Example 3: Unmerge a Shift

**Scenario:** Realized one shift should be separate delivery

1. Click blue list icon on DS-003172
2. Click "Unmerge" on Shift 3197
3. Confirm action
4. Result:
   - DS-003172 now has only Shift 3196
   - New DS-003176 created with Shift 3197

---

## Benefits

### Flexibility:
- ✅ Merge deliveries when beneficial
- ✅ Unmerge when circumstances change
- ✅ No permanent decisions
- ✅ Easy to adjust

### Organization:
- ✅ Deliveries grouped by route
- ✅ Clear visual hierarchy
- ✅ Easy to find specific routes
- ✅ Better overview

### Transparency:
- ✅ See exactly which shifts are in each delivery
- ✅ Know what you're merging/unmerging
- ✅ Clear information display
- ✅ No hidden data

---

## Workflow

### Complete Merge/Unmerge Workflow:

```
1. Create Shifts
   ↓
2. Close Shifts (auto-creates delivery schedules)
   ↓
3. View Deliveries (grouped by route)
   ↓
4. Decide to Merge
   ↓
5. Click Merge Icon → Select Source → Confirm
   ↓
6. Merged Delivery Created
   ↓
7. View Shifts (click list icon)
   ↓
8. Decide to Unmerge (if needed)
   ↓
9. Click Unmerge → Confirm
   ↓
10. Separate Deliveries Restored
```

---

## Technical Details

### Backend Changes:

1. **New Controller Method:**
   ```php
   public function unmergeShift(Request $request, $id, $shiftId): JsonResponse
   ```
   - Validates shift is in delivery
   - Checks status is consolidating/consolidated
   - Prevents unmerging only shift
   - Removes shift from delivery
   - Creates new delivery for unmerged shift
   - Recalculates both deliveries

2. **Route Added:**
   ```php
   POST /admin/delivery-schedules/{id}/unmerge/{shiftId}
   ```

### Frontend Changes:

1. **Route Grouping:**
   - PHP: `collect($schedules)->groupBy('route_name')`
   - Displays route headers in table
   - Groups deliveries under each route

2. **View Shifts Modal:**
   - AJAX call to get shifts
   - Displays shifts in table
   - Shows unmerge button for each shift
   - Handles unmerge action

3. **Updated Merge Modal:**
   - Changed warning to info
   - Removed "cannot be undone" message
   - Added "can unmerge later" note

---

## Validation & Safety

### Unmerge Validations:

✅ **Status Check**: Only consolidating/consolidated  
✅ **Shift Exists**: Shift must be in the delivery  
✅ **Multiple Shifts**: Must have at least 2 shifts  
✅ **Auto-Create**: New delivery created automatically  

### Cannot Unmerge If:

❌ Delivery is `loaded`  
❌ Delivery is `in_progress`  
❌ Delivery is `finished`  
❌ It's the only shift in delivery  
❌ Shift not in this delivery  

---

## Current Status

### Your Deliveries:

**Route: Roysambu**
- DS-003172 (Shift 3196 - 3 orders)
- DS-003175 (Shift 3197 - 1 order)

### Actions Available:

1. **View Shifts**: Click blue list icon
2. **Merge**: Click yellow compress icon
3. **Unmerge**: After merging, click list icon → unmerge button

---

## Files Modified

1. **Controller**: `app/Http/Controllers/Admin/DeliveryScheduleController.php`
   - Added `unmergeShift()` method

2. **Routes**: `routes/web.php`
   - Added POST route for unmerge

3. **View**: `resources/views/admin/delivery_schedules/index_new.blade.php`
   - Added route grouping
   - Added view shifts button
   - Added view shifts modal
   - Added unmerge functionality
   - Updated merge modal warning

---

## Testing

### Test Scenarios:

1. **✅ View Shifts**
   - Click list icon
   - Modal shows shifts
   - Information accurate

2. **✅ Unmerge Shift**
   - Merge two deliveries first
   - Click list icon
   - Click unmerge on one shift
   - New delivery created
   - Both deliveries correct

3. **✅ Route Grouping**
   - Deliveries grouped by route
   - Headers displayed
   - Easy to navigate

4. **✅ Validation**
   - Cannot unmerge only shift
   - Cannot unmerge from loaded delivery
   - Proper error messages

---

## Summary

**What Changed:**
- ✅ Merges are now reversible (unmerge feature)
- ✅ Deliveries grouped by route for better organization
- ✅ View shifts button shows what's in each delivery
- ✅ More flexible and user-friendly interface

**Benefits:**
- ✅ No permanent decisions
- ✅ Easy to adjust deliveries
- ✅ Better visual organization
- ✅ Complete transparency

**Ready to Use:**
- ✅ All features tested
- ✅ Validation in place
- ✅ User-friendly interface
- ✅ Complete documentation

---

**Feature Added**: 2025-01-03  
**Status**: ✅ Ready for Production  
**Tested**: ✅ Working  
**Documented**: ✅ Complete

Refresh your page to see the new features!
