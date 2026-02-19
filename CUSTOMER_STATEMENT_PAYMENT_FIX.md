# Customer Statement Payment Fix

## Issues Fixed

### 1. Payments Not Appearing in Customer Statement ✅
**Problem:** When delivery drivers recorded payments during delivery, the payments were saved in `invoice_payments` table but NOT in `wa_debtor_trans` table, so they didn't appear in the customer statement.

**Solution:** Added code to create `wa_debtor_trans` entries when payments are recorded during delivery.

### 2. Payment Amounts Showing as 0.00 in Credit Column ✅
**Problem:** Payment amounts were showing as 0.00 instead of the actual payment amount because negative values weren't being converted to positive for display.

**Solution:** Changed the credit calculation to use `ABS(amount)` to show positive values.

---

## Changes Made

### 1. DeliveryDriverController - Record Payment in wa_debtor_trans ✅
**File:** `app/Http/Controllers/Admin/DeliveryDriverController.php`
**Method:** `recordPayment()` (lines 1134-1143)

**Added:**
```php
// Create debtor transaction for customer statement
WaDebtorTran::create([
    'wa_customer_id' => $order->wa_customer_id,
    'wa_sales_invoice_id' => $orderId,
    'document_no' => $paymentRef,
    'reference' => "PAYMENT - {$methodName} - Invoice: {$order->requisition_no}",
    'amount' => -$payment['amount'], // Negative for payment/credit
    'trans_date' => Carbon::now()->format('Y-m-d'),
    'input_date' => Carbon::now()->format('Y-m-d H:i:s')
]);
```

**Import Added:** (line 17)
```php
use App\Model\WaDebtorTran;
```

### 2. CustomerCentreController - Fix Credit Display ✅
**File:** `app/Http/Controllers/Admin/CustomerCentreController.php`
**Method:** `statement()` (line 59)

**Changed:**
```php
// Before:
->selectRaw("(CASE WHEN amount < 0 THEN amount ELSE 0 END) as credit")

// After:
->selectRaw("(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as credit")
```

---

## How It Works Now

### Payment Flow:

1. **Delivery Driver Records Payment**
   - Driver selects payment method (MPESA/CASH)
   - Enters amount
   - Clicks "Confirm Payment"

2. **Two Records Created:**
   
   **A. Invoice Payment Record** (`invoice_payments` table)
   ```php
   - order_id
   - paid_amount
   - payment_gateway (MPESA/CASH)
   - payment_reference (DELIVERY-timestamp-orderid)
   - status: completed
   ```
   
   **B. Debtor Transaction** (`wa_debtor_trans` table) ← **NEW!**
   ```php
   - wa_customer_id
   - wa_sales_invoice_id
   - document_no: DELIVERY-timestamp-orderid
   - reference: "PAYMENT - MPESA - Invoice: SO202511030001"
   - amount: -35520.00 (negative for credit)
   - trans_date: 2025-11-03
   - input_date: 2025-11-03 14:48:00
   ```

3. **Customer Statement Display**
   - Reads from `wa_debtor_trans` table
   - Shows payment with:
     - Date & Time: 2025-11-03 14:48
     - Description: PAYMENT - MPESA - Invoice: SO202511030001
     - Debit: 0.00
     - Credit: 35,520.00 ← **Shows positive amount**
     - Running Balance: Updated correctly

---

## Example Transaction Flow

### Order Created:
```
Date: 2025-11-03 10:00
Invoice: SO202511030001
Amount: KES 35,520.00
Customer: COOLMART

wa_debtor_trans entry:
- amount: +35520.00 (positive = debit/invoice)
- Shows in Debit column
```

### Payment Received During Delivery:
```
Date: 2025-11-03 14:48
Payment Method: MPESA
Amount: KES 35,520.00

wa_debtor_trans entry (NEW):
- amount: -35520.00 (negative = credit/payment)
- Shows in Credit column as: 35,520.00
```

### Customer Statement Shows:
```
Date         Description                              Debit      Credit    Balance
2025-11-03   Roysambu - SO202511030001               35,520.00   0.00     35,520.00
2025-11-03   PAYMENT - MPESA - Invoice: SO2025...    0.00        35,520.00 0.00
             14:48
```

---

## Database Tables

### wa_debtor_trans (Customer Statement Source)
```
id
wa_customer_id          ← Links to customer
wa_sales_invoice_id     ← Links to order/invoice
document_no             ← Reference number
reference               ← Description
amount                  ← Positive=Debit, Negative=Credit
trans_date              ← Transaction date (Y-m-d)
input_date              ← Transaction datetime (Y-m-d H:i:s)
```

### invoice_payments (Payment Records)
```
id
order_id
paid_amount
payment_gateway
payment_reference
payment_date
status
```

---

## Benefits

### For Customers:
- ✅ See all payments immediately in statement
- ✅ Accurate balance tracking
- ✅ Clear payment history with timestamps

### For Accounting:
- ✅ Complete transaction history
- ✅ Payments linked to invoices
- ✅ Audit trail with timestamps
- ✅ Correct debit/credit display

### For Management:
- ✅ Real-time payment visibility
- ✅ Accurate customer balances
- ✅ Payment method tracking
- ✅ Delivery payment reconciliation

---

## Testing Checklist

- ✅ Payment recorded during delivery
- ✅ Payment appears in customer statement
- ✅ Payment shows in Credit column
- ✅ Amount displays as positive value
- ✅ Date and time shown correctly
- ✅ Reference includes payment method and invoice
- ✅ Running balance calculates correctly
- ✅ Customer balance updates immediately

---

## Before vs After

### Before Fix:
```
Customer Statement:
Date         Description                    Debit      Credit    Balance
2025-11-03   Roysambu - SO202511030001     35,520.00   0.00     35,520.00
(No payment entry - payment not visible!)
```

### After Fix:
```
Customer Statement:
Date         Description                              Debit      Credit    Balance
2025-11-03   Roysambu - SO202511030001               35,520.00   0.00     35,520.00
2025-11-03   PAYMENT - MPESA - Invoice: SO2025...    0.00        35,520.00 0.00
  14:48
```

---

## Important Notes

1. **Negative Amounts = Credits**
   - Payments stored as negative in database
   - Displayed as positive in Credit column
   - This is standard accounting practice

2. **Timestamp Tracking**
   - `trans_date`: Date only (for filtering)
   - `input_date`: Full datetime (for display)

3. **Payment Reference Format**
   - `DELIVERY-{timestamp}-{orderid}`
   - Example: `DELIVERY-1762029408-94178`

4. **Multiple Payments**
   - If multiple payment methods used
   - Creates separate entry for each
   - All linked to same invoice

---

**Status:** ✅ Complete and Ready

**Files Modified:**
1. `app/Http/Controllers/Admin/DeliveryDriverController.php`
2. `app/Http/Controllers/Admin/CustomerCentreController.php`

**Next Payment:** Will automatically appear in customer statement with correct amount and timestamp! 💰✅
