# Mobile App: Display Discount Information in Review Order Screen

## Problem
The Review Order modal doesn't show discount and promotion information for items, even when discounts are applied.

## Required Changes

### 1. Backend API Enhancement (if needed)
The `apiGetInventoryItems` endpoint should include discount/promotion data:

```php
// Add to the select statement in apiGetInventoryItems method
DB::raw("(SELECT discount_percentage FROM wa_item_discounts WHERE item_id = wa_inventory_items.id AND is_active = 1 LIMIT 1) as discount_percentage"),
DB::raw("(SELECT discount_amount FROM wa_item_discounts WHERE item_id = wa_inventory_items.id AND is_active = 1 LIMIT 1) as discount_amount"),
DB::raw("(SELECT promotion_name FROM wa_promotions WHERE id IN (SELECT promotion_id FROM wa_item_promotions WHERE item_id = wa_inventory_items.id AND is_active = 1) LIMIT 1) as promotion_name")
```

### 2. Mobile App Changes

#### File: `src/screens/OrderTaking/ReviewOrderModal.tsx` (or similar)

**A. Update Item Display to Show Discount:**

```typescript
// For each item in the order
{items.map((item, index) => (
  <View key={index} style={styles.itemContainer}>
    <Text style={styles.itemName}>{item.title}</Text>
    <View style={styles.itemDetails}>
      <Text style={styles.itemQuantity}>
        {item.quantity} × KES {item.price.toFixed(2)}
      </Text>
      <Text style={styles.itemTotal}>
        KES {item.total.toFixed(2)}
      </Text>
    </View>
    
    {/* ADD THIS: Show discount if exists */}
    {item.discount > 0 && (
      <View style={styles.discountRow}>
        <Text style={styles.discountLabel}>
          Discount {item.promotion_name ? `(${item.promotion_name})` : ''}
        </Text>
        <Text style={styles.discountAmount}>
          -KES {item.discount.toFixed(2)}
        </Text>
      </View>
    )}
  </View>
))}
```

**B. Update Order Summary to Show Total Discount:**

```typescript
<View style={styles.summarySection}>
  <Text style={styles.summaryTitle}>Order Summary</Text>
  
  {/* ADD THIS: Show subtotal before discount */}
  {totalDiscount > 0 && (
    <View style={styles.summaryRow}>
      <Text style={styles.summaryLabel}>Subtotal</Text>
      <Text style={styles.summaryValue}>
        KES {(netAmount + totalDiscount).toFixed(2)}
      </Text>
    </View>
  )}
  
  {/* ADD THIS: Show total discount */}
  {totalDiscount > 0 && (
    <View style={styles.summaryRow}>
      <Text style={styles.discountLabel}>Discount</Text>
      <Text style={styles.discountAmount}>
        -KES {totalDiscount.toFixed(2)}
      </Text>
    </View>
  )}
  
  <View style={styles.summaryRow}>
    <Text style={styles.summaryLabel}>Net Amount (excl. VAT)</Text>
    <Text style={styles.summaryValue}>
      KES {netAmount.toFixed(2)}
    </Text>
  </View>
  
  <View style={styles.summaryRow}>
    <Text style={styles.summaryLabel}>VAT (16%)</Text>
    <Text style={styles.summaryValue}>
      KES {vat.toFixed(2)}
    </Text>
  </View>
  
  <View style={[styles.summaryRow, styles.totalRow]}>
    <Text style={styles.totalLabel}>Total Amount</Text>
    <Text style={styles.totalValue}>
      KES {totalAmount.toFixed(2)}
    </Text>
  </View>
</View>
```

**C. Calculate Total Discount:**

```typescript
// Add this calculation
const totalDiscount = items.reduce((sum, item) => sum + (item.discount || 0), 0);
```

**D. Add Styles:**

```typescript
const styles = StyleSheet.create({
  // ... existing styles ...
  
  discountRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingLeft: 20,
    marginTop: 4,
  },
  discountLabel: {
    fontSize: 14,
    color: '#666',
    fontStyle: 'italic',
  },
  discountAmount: {
    fontSize: 14,
    color: '#e74c3c', // Red color for discount
    fontWeight: '600',
  },
});
```

### 3. Data Structure Expected

Each item in the cart should have:
```typescript
interface CartItem {
  id: number;
  title: string;
  quantity: number;
  price: number;
  total: number;
  discount?: number;  // Discount amount
  discount_percentage?: number;  // Discount percentage
  promotion_name?: string;  // Name of promotion if applicable
}
```

### 4. Testing

1. Add an item with a discount to the cart
2. Open Review Order modal
3. Verify discount is shown under the item
4. Verify Order Summary shows:
   - Subtotal (if discount exists)
   - Total Discount
   - Net Amount
   - VAT
   - Total Amount

## Implementation Steps

1. Update backend API to include discount data (if not already present)
2. Update mobile app to display discount per item
3. Update Order Summary to show total discount
4. Test with various scenarios:
   - Items with no discount
   - Items with percentage discount
   - Items with fixed amount discount
   - Items with promotions
   - Mixed cart (some with discount, some without)
