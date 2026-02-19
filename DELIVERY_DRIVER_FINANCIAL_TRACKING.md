# Delivery Driver Financial Tracking Feature

## Overview

Added real-time financial tracking to the delivery driver mobile app to show:
- **Total Amount to Collect** - Total value of all orders in the delivery
- **Amount Collected** - Money collected from completed deliveries
- **Amount Pending** - Money yet to be collected from pending deliveries
- **Collection Progress** - Visual progress bar showing collection percentage

---

## Features Added

### 1. Financial Calculation in Controller ✅

**Location:** `app/Http/Controllers/Admin/DeliveryDriverController.php`

**What it does:**
- Calculates total amount from all orders in the delivery schedule's shifts
- Tracks collected amount from delivered customers
- Calculates pending amount (total - collected)
- Supports multi-shift merged deliveries

**Code Logic:**
```php
// Get all shift IDs (supports merged deliveries)
$shiftIds = $activeSchedule->shifts()->pluck('salesman_shifts.id')->toArray();

// Calculate total from all orders
$totalAmount = DB::table('wa_internal_requisition_items')
    ->join('wa_internal_requisitions', ...)
    ->whereIn('wa_shift_id', $shiftIds)
    ->sum('total_cost_with_vat');

// Calculate collected from delivered customers
$deliveredCustomerIds = $activeSchedule->customers()
    ->whereNotNull('delivered_at')
    ->pluck('customer_id');
    
$collectedAmount = // Sum from delivered customers only
$pendingAmount = $totalAmount - $collectedAmount;
```

### 2. "Ready to Start" Screen Display ✅

**Status:** `loaded`

**Shows:**
- Total Amount to Collect
- Simple, clean display before delivery starts

**UI:**
```
┌─────────────────────────────────────┐
│ 💰 Collection Summary               │
├─────────────────────────────────────┤
│ Total to Collect: KES 10,500.00     │
└─────────────────────────────────────┘
```

### 3. "Delivery in Progress" Screen Display ✅

**Status:** `in_progress`

**Shows:**
- Total Amount (white background)
- Collected Amount (green background with checkmark)
- Pending Amount (yellow background with clock icon)
- Collection Progress Bar

**UI:**
```
┌─────────────────────────────────────┐
│ 💰 Collection Summary               │
├─────────────────────────────────────┤
│ Total Amount:    KES 10,500.00      │
│ ✓ Collected:     KES  7,200.00      │
│ ⏰ Pending:       KES  3,300.00      │
│                                     │
│ Collection Progress        68.6%    │
│ ████████████░░░░░░░                 │
└─────────────────────────────────────┘
```

---

## Visual Design

### Color Coding:
- **Total Amount**: White/Light background
- **Collected Amount**: Green background (#90EE90) with check icon
- **Pending Amount**: Yellow background (#FFD700) with clock icon
- **Progress Bar**: Green fill on semi-transparent background

### Gradient Card:
- Background: Purple gradient (matches app theme)
- Text: White for good contrast
- Rounded corners: 15px
- Padding: 20px

---

## How It Works

### Calculation Flow:

1. **Driver accepts items** → Status changes to `loaded`
   - Shows total amount to collect

2. **Driver starts delivery** → Status changes to `in_progress`
   - Shows full financial breakdown
   - Updates in real-time as deliveries are completed

3. **Customer delivery completed** → `delivered_at` timestamp set
   - Collected amount increases
   - Pending amount decreases
   - Progress bar updates

4. **All customers delivered** → Can complete schedule
   - Collection should be 100% (or show shortfall)

---

## Data Sources

### Total Amount:
```sql
SELECT SUM(total_cost_with_vat)
FROM wa_internal_requisition_items
JOIN wa_internal_requisitions ON ...
WHERE wa_shift_id IN (shift_ids_from_delivery)
```

### Collected Amount:
```sql
SELECT SUM(total_cost_with_vat)
FROM wa_internal_requisition_items
JOIN wa_internal_requisitions ON ...
WHERE wa_shift_id IN (shift_ids)
  AND wa_route_customer_id IN (delivered_customer_ids)
```

### Pending Amount:
```
pending = total - collected
```

---

## Multi-Shift Support

✅ **Fully supports merged deliveries:**
- Calculates total from ALL shifts in the delivery
- Tracks collections across all shifts
- Accurate financial reporting for consolidated routes

**Example:**
- Shift 3196: 3 orders, KES 7,500
- Shift 3197: 1 order, KES 3,000
- **Merged Total**: KES 10,500

---

## Benefits

### For Drivers:
- ✅ Know exactly how much money to collect
- ✅ Track progress in real-time
- ✅ See what's pending at a glance
- ✅ Visual progress bar for motivation

### For Management:
- ✅ Real-time collection tracking
- ✅ Identify collection issues early
- ✅ Better cash flow visibility
- ✅ Accountability for drivers

### For Operations:
- ✅ Accurate financial reporting
- ✅ Supports multi-shift deliveries
- ✅ Automatic calculations
- ✅ No manual tracking needed

---

## Screen States

### 1. Consolidated (Item Verification)
- No financial display yet
- Driver checking items

### 2. Loaded (Ready to Start)
- Shows: **Total Amount Only**
- Simple display
- Driver knows target collection

### 3. In Progress (Active Delivery)
- Shows: **Full Financial Breakdown**
- Total, Collected, Pending
- Progress bar
- Updates as deliveries complete

### 4. Finished
- Final totals recorded
- Can review collection performance

---

## Technical Details

### Controller Method:
`getDashboardDataForWeb($driverId)`

### Added Fields:
```php
'total_amount' => $totalAmount,
'collected_amount' => $collectedAmount,
'pending_amount' => $pendingAmount,
```

### View File:
`resources/views/admin/delivery_driver/mobile_app.blade.php`

### Sections Modified:
- Line 426-435: "Ready to Start" financial display
- Line 449-488: "In Progress" financial display with progress bar

---

## Example Scenarios

### Scenario 1: Start of Day
```
Status: loaded
Total to Collect: KES 10,500.00
```

### Scenario 2: Mid-Delivery (3 of 6 customers)
```
Status: in_progress
Total Amount:    KES 10,500.00
✓ Collected:     KES  7,200.00 (68.6%)
⏰ Pending:       KES  3,300.00
Progress: ████████████░░░░░░░ 68.6%
```

### Scenario 3: Almost Complete (5 of 6 customers)
```
Status: in_progress
Total Amount:    KES 10,500.00
✓ Collected:     KES  9,800.00 (93.3%)
⏰ Pending:       KES    700.00
Progress: ██████████████████░ 93.3%
```

---

## Future Enhancements (Optional)

1. **Payment Method Tracking**
   - Cash vs Mobile Money
   - Payment receipts

2. **Shortfall Alerts**
   - If collected < expected
   - Missing payments

3. **Daily Summary**
   - Total collected today
   - Multiple deliveries

4. **Collection History**
   - Past deliveries
   - Performance metrics

---

## Testing Checklist

- ✅ Total amount calculates correctly
- ✅ Collected amount updates when customer delivered
- ✅ Pending amount = Total - Collected
- ✅ Progress bar shows correct percentage
- ✅ Works with single-shift deliveries
- ✅ Works with multi-shift merged deliveries
- ✅ Display on "loaded" status
- ✅ Display on "in_progress" status
- ✅ Mobile responsive design
- ✅ Color coding clear and visible

---

## Files Modified

1. **Controller**: `app/Http/Controllers/Admin/DeliveryDriverController.php`
   - Added financial calculations to `getDashboardDataForWeb()`

2. **View**: `resources/views/admin/delivery_driver/mobile_app.blade.php`
   - Added financial display to "loaded" section
   - Added comprehensive financial display to "in_progress" section

---

**Feature Status**: ✅ Complete and Ready to Use

**URL**: `http://127.0.0.1:8000/admin/delivery-driver/mobile`

**Refresh the page to see the financial tracking!** 🎯💰
