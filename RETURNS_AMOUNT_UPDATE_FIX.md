# Returns Amount Update Fix

## Issue
When a delivery driver processed item returns through the mobile interface, the return was recorded in the database but the displayed order amount on the customer card did not change. The amount shown (e.g., KES 321,600.00) remained the same even after returning items.

## Root Cause
The order amount calculations were only summing the original order items' `total_cost_with_vat` without accounting for returned items stored in the `sale_order_returns` table.

Four calculations were affected:
1. **Payment Modal Amount** - Amount shown in the payment modal (via `getCustomerItems` method)
2. **Total Amount** - Total value of all orders in the shift
3. **Collected Amount** - Value collected from delivered customers
4. **Individual Customer Order Amount** - Amount shown on each customer card

## Solution
Updated all four calculations to subtract the proportional value of returned items:

### Formula
```
Returned Item Value = (Returned Quantity / Original Quantity) × Original Item Total Cost
```

This ensures that if a customer returns part of an item (e.g., 10 out of 50 units), only the proportional cost is deducted.

## Changes Made

### File: `app/Http/Controllers/Admin/DeliveryDriverController.php`

#### 1. Payment Modal Total Amount (Lines 583-593)
**Issue:** Payment modal was showing original order amount without accounting for returns.

**Before:**
```php
// Add to total amount
$totalAmount += $item->total_cost_with_vat ?? 0;
```

**After:**
```php
// Calculate item amount after returns
$itemOriginalAmount = $item->total_cost_with_vat ?? 0;
$itemReturnedAmount = 0;

if ($returnedQuantity > 0 && $item->quantity > 0) {
    // Calculate proportional return value
    $itemReturnedAmount = ($returnedQuantity / $item->quantity) * $itemOriginalAmount;
}

// Add net amount (original - returned) to total
$totalAmount += ($itemOriginalAmount - $itemReturnedAmount);
```

This ensures the payment modal shows the correct amount to collect after returns.

#### 2. Total Amount Calculation (Lines 703-710)
**Before:**
```php
$totalAmount = DB::table('wa_internal_requisition_items as order_items')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->sum('order_items.total_cost_with_vat');
```

**After:**
```php
$totalAmount = DB::table('wa_internal_requisition_items as order_items')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->sum('order_items.total_cost_with_vat');

// Subtract total returned items value
$totalReturnedAmount = DB::table('sale_order_returns as returns')
    ->join('wa_internal_requisition_items as order_items', 'returns.wa_internal_requisition_item_id', '=', 'order_items.id')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->sum(DB::raw('(returns.quantity / order_items.quantity) * order_items.total_cost_with_vat'));

$totalAmount = $totalAmount - $totalReturnedAmount;
```

#### 2. Collected Amount Calculation (Lines 717-725)
**Before:**
```php
$collectedAmount = DB::table('wa_internal_requisition_items as order_items')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->whereIn('orders.wa_route_customer_id', $deliveredCustomerIds)
    ->sum('order_items.total_cost_with_vat');
```

**After:**
```php
$collectedAmount = DB::table('wa_internal_requisition_items as order_items')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->whereIn('orders.wa_route_customer_id', $deliveredCustomerIds)
    ->sum('order_items.total_cost_with_vat');

// Subtract returned items value from collected amount
$collectedReturnedAmount = DB::table('sale_order_returns as returns')
    ->join('wa_internal_requisition_items as order_items', 'returns.wa_internal_requisition_item_id', '=', 'order_items.id')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->whereIn('orders.wa_route_customer_id', $deliveredCustomerIds)
    ->sum(DB::raw('(returns.quantity / order_items.quantity) * order_items.total_cost_with_vat'));

$collectedAmount = $collectedAmount - $collectedReturnedAmount;
```

#### 3. Individual Customer Order Amount (Lines 820-828)
**Before:**
```php
$orderAmount = DB::table('wa_internal_requisition_items as order_items')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->where('orders.wa_route_customer_id', $customer->customer_id)
    ->sum('order_items.total_cost_with_vat');
```

**After:**
```php
$orderAmount = DB::table('wa_internal_requisition_items as order_items')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->where('orders.wa_route_customer_id', $customer->customer_id)
    ->sum('order_items.total_cost_with_vat');

// Subtract returned items value
$returnedAmount = DB::table('sale_order_returns as returns')
    ->join('wa_internal_requisition_items as order_items', 'returns.wa_internal_requisition_item_id', '=', 'order_items.id')
    ->join('wa_internal_requisitions as orders', 'order_items.wa_internal_requisition_id', '=', 'orders.id')
    ->whereIn('orders.wa_shift_id', $shiftIds)
    ->where('orders.wa_route_customer_id', $customer->customer_id)
    ->sum(DB::raw('(returns.quantity / order_items.quantity) * order_items.total_cost_with_vat'));

$orderAmount = $orderAmount - $returnedAmount;
```

## How It Works

1. **Original Order Value**: Calculated from `wa_internal_requisition_items.total_cost_with_vat`
2. **Returns Query**: Joins `sale_order_returns` → `wa_internal_requisition_items` → `wa_internal_requisitions`
3. **Proportional Calculation**: `(returned_qty / original_qty) × original_cost`
4. **Final Amount**: Original value - Returned value

### Example:
- Original order: 50 units @ KES 100/unit = KES 5,000
- Customer returns: 10 units
- Returned value: (10 / 50) × 5,000 = KES 1,000
- **New order amount: KES 4,000**

## Benefits

✅ **Accurate amounts** - Customer card shows correct amount after returns  
✅ **Real-time updates** - Amount updates immediately after processing returns  
✅ **Correct collection tracking** - Progress bar reflects actual collectible amount  
✅ **Proportional calculation** - Handles partial returns correctly  
✅ **Consistent across all views** - Total, collected, and individual amounts all updated  

## Testing

To verify the fix:

1. Open delivery driver mobile page: `http://127.0.0.1:8000/admin/delivery-driver/mobile`
2. Note the customer's order amount (e.g., KES 321,600.00)
3. Click "Prompt Delivery" and process some returns
4. After returns are processed, refresh the page
5. The customer's order amount should now be reduced by the value of returned items
6. The collection summary should also reflect the updated amounts

## Related Tables

- `wa_internal_requisition_items` - Original order items with quantities and costs
- `sale_order_returns` - Return records with returned quantities
- `wa_internal_requisitions` - Orders linking items to customers and shifts
- `delivery_schedule_customers` - Customer delivery status

## Date
November 4, 2025
