# Backend Performance Optimization Required

## Critical Issue: `/get-inventory-items` endpoint taking 15 seconds

### Current Performance:
- Routes API: 2-3 seconds
- Shops API: 3 seconds  
- **Products API: 15 seconds** ← CRITICAL
- Order Submission: 8.5 seconds

### Required Backend Optimizations:

#### 1. Optimize `apiGetInventoryItems` in `SalesController.php` (line ~526)

**Add these optimizations:**

```php
public function apiGetInventoryItems(Request $request)
{
    // Add caching (5 minutes)
    $cacheKey = 'inventory_items_' . $request->store_location_id;
    
    return Cache::remember($cacheKey, 300, function () use ($request) {
        // Limit query to only necessary fields
        $items = WaInventoryItem::select([
            'id', 'stock_id_code', 'title', 'selling_price', 
            'standard_cost', 'image', 'qoh', 'status'
        ])
        ->where('status', 1)
        ->where('store_location_id', $request->store_location_id)
        ->limit(500) // Limit initial load
        ->get();
        
        return response()->json([
            'status' => true,
            'data' => $items
        ]);
    });
}
```

#### 2. Add Database Indexes

Run these SQL commands in MySQL:

```sql
-- Index for faster product queries
ALTER TABLE wa_inventory_items 
ADD INDEX idx_status_store (status, store_location_id);

-- Index for stock moves (if qoh is calculated)
ALTER TABLE wa_stock_moves 
ADD INDEX idx_item_location (wa_inventory_item_id, store_location_id);
```

#### 3. Optimize Order Submission (8.5 seconds)

In `SalesOrdersController.php` `recordSalesOrders` method:
- Remove unnecessary notifications during order creation
- Move SMS/email notifications to queue/background job
- Reduce database queries by using eager loading

```php
// Use eager loading
$order = WaInternalRequisition::with(['items', 'customer'])->find($id);

// Queue notifications instead of sending immediately
dispatch(new SendOrderNotification($order))->afterResponse();
```

#### 4. Enable Query Caching in `.env`

```env
CACHE_DRIVER=file
QUEUE_CONNECTION=database
```

### Expected Results After Optimization:
- Products API: 15s → **2-3 seconds** (80% faster)
- Order Submission: 8.5s → **3-4 seconds** (60% faster)
- Total "New Order" load: 20s → **5-6 seconds** (75% faster)
