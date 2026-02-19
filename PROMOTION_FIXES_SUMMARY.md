# Promotion System Database Integrity Fixes

## Issue Description
Database integrity constraint violation (SQLSTATE[23000]: Integrity constraint violation: 1452) when creating promotions in production environment.

## Root Cause Analysis
The error occurred due to foreign key constraint violations in the `item_promotions` table where one or more foreign key references were pointing to non-existent records.

## Fixes Applied

### 1. Enhanced Validation in ItemPromotionsController
**File:** `app/Http/Controllers/ItemPromotionsController.php`

#### Changes Made:
- **Enhanced request validation** to check existence of foreign key references:
  ```php
  $request->validate([
      'from_date' => 'required|date',
      'to_date' => 'nullable|date|after_or_equal:from_date',
      'promotion_type_id' => 'required|exists:promotion_types,id',
      'supplier_id' => 'required|exists:wa_suppliers,id',
      'promotion_group_id' => 'nullable|exists:promotion_groups,id',
      'inventory_item' => 'nullable|exists:wa_inventory_items,id'
  ]);
  ```

- **Added existence checks** for inventory item and user:
  ```php
  if (!$inventoryItem) {
      return redirect()->back()->withErrors(['errors' => 'Inventory item not found']);
  }
  
  $user = getLoggeduserProfile();
  if (!$user || !$user->id) {
      return redirect()->back()->withErrors(['errors' => 'User profile not found']);
  }
  ```

- **Fixed user ID assignment** to use validated user:
  ```php
  $delta->created_by = $user->id; // Instead of $request->user_id
  $itemPromotion->initiated_by = $user->id; // Instead of getLoggeduserProfile()->id
  ```

- **Added promotion type validation**:
  ```php
  $type = PromotionType::find($request->promotion_type_id);
  if (!$type) {
      return redirect()->back()->withErrors(['errors' => 'Invalid promotion type']);
  }
  ```

- **Added promotion item validation** for BSGY promotions:
  ```php
  if ($request->inventory_item && !WaInventoryItem::find($request->inventory_item)) {
      return redirect()->back()->withErrors(['errors' => 'Promotion item not found']);
  }
  ```

- **Fixed permission check inconsistency**:
  ```php
  // Changed from 'manage-discount' to 'manage-promotions'
  if (isset($permission[$this->pmodule . '___manage-promotions']) || $permission == 'superadmin')
  ```

### 2. Updated ItemPromotion Model
**File:** `app/ItemPromotion.php`

#### Changes Made:
- **Added fillable property** to allow mass assignment of all promotion fields:
  ```php
  protected $fillable = [
      'inventory_item_id', 'promotion_type_id', 'promotion_group_id',
      'wa_demand_id', 'supplier_id', 'apply_to_split', 'initiated_by',
      'from_date', 'to_date', 'sale_quantity', 'promotion_item_id',
      'promotion_quantity', 'current_price', 'promotion_price',
      'discount_percentage', 'discount_amount', 'status'
  ];
  ```

### 3. Database Migration for Foreign Key Constraints
**File:** `database/migrations/2024_10_08_143825_fix_item_promotions_foreign_keys.php`

#### Purpose:
- Ensures all required foreign key constraints exist
- Adds missing `status` column if not present
- Provides rollback functionality

#### Foreign Keys Added:
- `promotion_type_id` → `promotion_types.id`
- `promotion_group_id` → `promotion_groups.id`
- `wa_demand_id` → `wa_demands.id`
- `supplier_id` → `wa_suppliers.id`

## Testing Instructions

### 1. Run the Migration
```bash
php artisan migrate
```

### 2. Test Promotion Creation
1. Navigate to an inventory item
2. Click "Create Promotion"
3. Fill in all required fields:
   - From Date (required)
   - To Date (optional)
   - Promotion Type (required)
   - Supplier (required)
   - Promotion Group (optional)
4. Submit the form

### 3. Verify Database Integrity
- Check that all foreign key references exist in their respective tables
- Verify that the `initiated_by` field contains valid user IDs
- Confirm that `status` field defaults to 'active'

## Expected Outcomes
- ✅ No more foreign key constraint violations
- ✅ Proper validation messages for invalid data
- ✅ Correct permission checks for promotion management
- ✅ Consistent user ID assignment throughout the system

## Rollback Plan
If issues arise, the migration can be rolled back:
```bash
php artisan migrate:rollback --step=1
```

## Related Issues Addressed
Based on previous analysis, this fix also addresses:
- Permission check inconsistency (Item #8 from promotion system issues)
- Missing validation in promotion creation (Item #6)
- Database migration structural problems (Item #10)
