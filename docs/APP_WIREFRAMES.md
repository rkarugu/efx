# Kaninichapchap Mobile App - Wireframes

## Screen Flow Diagram

```
Login → Dashboard → [Shift Management]
                  → [Customer List] → Customer Details → Create Order → Order Confirmation
                  → [Product Catalog] → Product Details → Add to Cart
                  → [Collect Payment] → Payment Receipt
                  → [Process Return] → Return Note
                  → [Deliveries] → Delivery Details → Confirm Delivery
                  → [Expenses] → Record Expense
                  → [Reports] → Sales Report
                  → [Profile] → Settings
```

---

## 1. Login Screen

```
┌─────────────────────────────┐
│                             │
│      [APP LOGO]             │
│                             │
│   Welcome Back!             │
│   Sign in to continue       │
│                             │
│   ┌─────────────────────┐   │
│   │ 📱 Phone Number     │   │
│   └─────────────────────┘   │
│                             │
│   ┌─────────────────────┐   │
│   │ 🔒 Password         │   │
│   └─────────────────────┘   │
│                             │
│   ☐ Remember Me             │
│                             │
│   ┌─────────────────────┐   │
│   │    LOGIN            │   │
│   └─────────────────────┘   │
│                             │
│   Forgot Password?          │
│                             │
│   ┌─────────────────────┐   │
│   │ 🔐 Use Biometric    │   │
│   └─────────────────────┘   │
│                             │
└─────────────────────────────┘
```

---

## 2. Dashboard (Salesman)

```
┌─────────────────────────────┐
│ ☰  Dashboard        🔔 👤  │
├─────────────────────────────┤
│                             │
│ ┌─────────────────────────┐ │
│ │ 🟢 Shift: OPEN          │ │
│ │ Started: 08:00 AM       │ │
│ │ Vehicle: KBZ 123A       │ │
│ │ [Close Shift]           │ │
│ └─────────────────────────┘ │
│                             │
│ Today's Summary             │
│ ┌──────┐ ┌──────┐ ┌──────┐ │
│ │Sales │ │Orders│ │Coll. │ │
│ │50K   │ │  25  │ │ 15K  │ │
│ └──────┘ └──────┘ └──────┘ │
│                             │
│ Quick Actions               │
│ ┌──────┐ ┌──────┐          │
│ │ 🛒   │ │ 💰   │          │
│ │ New  │ │Collect│          │
│ │Order │ │Payment│          │
│ └──────┘ └──────┘          │
│ ┌──────┐ ┌──────┐          │
│ │ 🗺️   │ │ ↩️   │          │
│ │View  │ │Return│          │
│ │Route │ │Items │          │
│ └──────┘ └──────┘          │
│                             │
│ Recent Activity             │
│ • Order #001 - ABC Store    │
│   KES 5,000 | 10:30 AM      │
│ • Payment - XYZ Shop        │
│   KES 3,000 | 11:15 AM      │
│                             │
└─────────────────────────────┘
│ [🏠][👥][📦][📊][👤]        │
└─────────────────────────────┘
```

---

## 3. Customer List (Route View)

```
┌─────────────────────────────┐
│ ← Route: Nairobi CBD        │
├─────────────────────────────┤
│ 🔍 Search customers...      │
│                             │
│ [List] [Map]  Filter: All ▼ │
│                             │
│ ┌─────────────────────────┐ │
│ │ ABC Store          🟢   │ │
│ │ 📍 123 Main St, 2.5km   │ │
│ │ 💰 Balance: KES 15,000  │ │
│ │ Last Visit: 2 days ago  │ │
│ │ [📞] [🗺️] [Visit]       │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ XYZ Shop           🟢   │ │
│ │ 📍 456 Oak Ave, 3.1km   │ │
│ │ 💰 Balance: KES 0       │ │
│ │ Last Visit: Today       │ │
│ │ [📞] [🗺️] [Visit]       │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ DEF Mart           🔴   │ │
│ │ 📍 789 Elm St, 5.2km    │ │
│ │ 💰 Balance: KES 8,500⚠️ │ │
│ │ Overdue: 15 days        │ │
│ │ [📞] [🗺️] [Visit]       │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
│ [🏠][👥][📦][📊][👤]        │
└─────────────────────────────┘
```

---

## 4. Product Catalog

```
┌─────────────────────────────┐
│ ← Products            🛒 (3) │
├─────────────────────────────┤
│ 🔍 Search products...  [📷] │
│                             │
│ [Grid] [List]  All ▼   [⚙️] │
│                             │
│ ┌──────────┐ ┌──────────┐  │
│ │ [IMAGE]  │ │ [IMAGE]  │  │
│ │          │ │          │  │
│ │ Coca Cola│ │ Fanta    │  │
│ │ 500ml    │ │ 500ml    │  │
│ │ KES 50   │ │ KES 45   │  │
│ │ Stock:100│ │ Stock:50 │  │
│ │   [+]    │ │   [+]    │  │
│ └──────────┘ └──────────┘  │
│                             │
│ ┌──────────┐ ┌──────────┐  │
│ │ [IMAGE]  │ │ [IMAGE]  │  │
│ │          │ │          │  │
│ │ Sprite   │ │ Pepsi    │  │
│ │ 500ml    │ │ 500ml    │  │
│ │ KES 45   │ │ KES 50   │  │
│ │ Stock:75 │ │ Stock:0⚠️│  │
│ │   [+]    │ │   [-]    │  │
│ └──────────┘ └──────────┘  │
│                             │
│ ┌─────────────────────────┐ │
│ │ 🛒 Cart: 3 items        │ │
│ │ Total: KES 150  [View]  │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
│ [🏠][👥][📦][📊][👤]        │
└─────────────────────────────┘
```

---

## 5. Create Order (Cart)

```
┌─────────────────────────────┐
│ ← New Order                 │
├─────────────────────────────┤
│ Customer: ABC Store    [✎]  │
│ 📍 123 Main St              │
│                             │
│ Cart Items (3)              │
│ ┌─────────────────────────┐ │
│ │ Coca Cola 500ml         │ │
│ │ KES 50 x 10 = 500   [×] │ │
│ │ [-] 10 [+]              │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ Fanta 500ml             │ │
│ │ KES 45 x 5 = 225    [×] │ │
│ │ [-] 5 [+]               │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ Sprite 500ml            │ │
│ │ KES 45 x 2 = 90     [×] │ │
│ │ [-] 2 [+]               │ │
│ └─────────────────────────┘ │
│                             │
│ [+ Add More Items]          │
│                             │
│ ─────────────────────────   │
│ Subtotal:        KES 815    │
│ Discount (10%):  KES 81.50  │
│ ─────────────────────────   │
│ Total:           KES 733.50 │
│                             │
│ Payment Method              │
│ ○ Cash  ● M-Pesa  ○ Credit │
│                             │
│ M-Pesa Ref: QA12BC34        │
│                             │
│ Notes (Optional)            │
│ ┌─────────────────────────┐ │
│ │ Deliver by 3pm          │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │    PLACE ORDER          │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
```

---

## 6. Collect Payment

```
┌─────────────────────────────┐
│ ← Collect Payment           │
├─────────────────────────────┤
│ Customer: ABC Store    [✎]  │
│ 📍 123 Main St              │
│                             │
│ Outstanding Balance         │
│ ┌─────────────────────────┐ │
│ │   KES 15,000            │ │
│ │   Overdue: KES 5,000    │ │
│ └─────────────────────────┘ │
│                             │
│ Payment Amount *            │
│ ┌─────────────────────────┐ │
│ │ 5,000                   │ │
│ └─────────────────────────┘ │
│ [Pay Full] [Pay Overdue]    │
│                             │
│ Payment Method *            │
│ ○ Cash                      │
│ ● M-Pesa                    │
│ ○ Bank Transfer             │
│ ○ Cheque                    │
│                             │
│ M-Pesa Reference *          │
│ ┌─────────────────────────┐ │
│ │ QA12BC34                │ │
│ └─────────────────────────┘ │
│                             │
│ Notes (Optional)            │
│ ┌─────────────────────────┐ │
│ │ Partial payment         │ │
│ └─────────────────────────┘ │
│                             │
│ ☑ Send SMS Receipt          │
│                             │
│ ┌─────────────────────────┐ │
│ │   RECORD PAYMENT        │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
```

---

## 7. Delivery List

```
┌─────────────────────────────┐
│ ☰ Deliveries        🗺️ 🔔  │
├─────────────────────────────┤
│ [Pending] [In Progress]     │
│ [Completed]                 │
│                             │
│ Today: 8 deliveries         │
│ Completed: 3 | Pending: 5   │
│                             │
│ ┌─────────────────────────┐ │
│ │ #SO-001 - ABC Store  🔴 │ │
│ │ 📍 2.5 km away          │ │
│ │ 📦 10 items             │ │
│ │ 💰 KES 5,000            │ │
│ │ ⏰ ETA: 15 mins         │ │
│ │ [Navigate] [Details]    │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ #SO-002 - XYZ Shop   🟡 │ │
│ │ 📍 5.1 km away          │ │
│ │ 📦 5 items              │ │
│ │ 💰 KES 2,500            │ │
│ │ ⏰ ETA: 25 mins         │ │
│ │ [Navigate] [Details]    │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ #SO-003 - DEF Mart   🟢 │ │
│ │ ✓ Delivered 10:30 AM    │ │
│ │ 📦 8 items              │ │
│ │ 💰 KES 4,000            │ │
│ │ [View Receipt]          │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
│ [🏠][👥][📦][📊][👤]        │
└─────────────────────────────┘
```

---

## 8. Confirm Delivery

```
┌─────────────────────────────┐
│ ← Confirm Delivery          │
├─────────────────────────────┤
│ Order: #SO-001              │
│ Customer: ABC Store         │
│ 📞 0722123456               │
│                             │
│ Items to Deliver            │
│ ┌─────────────────────────┐ │
│ │ ☑ Coca Cola 500ml       │ │
│ │   Ordered: 10           │ │
│ │   Delivered: [10]   [✓] │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ ☑ Fanta 500ml           │ │
│ │   Ordered: 5            │ │
│ │   Delivered: [5]    [✓] │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ ☐ Sprite 500ml          │ │
│ │   Ordered: 2            │ │
│ │   Delivered: [0]    [×] │ │
│ │   Reason: Out of stock  │ │
│ └─────────────────────────┘ │
│                             │
│ Customer Signature *        │
│ ┌─────────────────────────┐ │
│ │                         │ │
│ │   [Signature Area]      │ │
│ │                         │ │
│ │ [Clear]                 │ │
│ └─────────────────────────┘ │
│                             │
│ Delivery Photo              │
│ [📷 Take Photo]             │
│                             │
│ Notes                       │
│ ┌─────────────────────────┐ │
│ │ Customer satisfied      │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │   CONFIRM DELIVERY      │ │
│ └─────────────────────────┘ │
│                             │
└─────────────────────────────┘
```

---

## 9. Sales Report

```
┌─────────────────────────────┐
│ ← Sales Report              │
├─────────────────────────────┤
│ Period: This Week      ▼    │
│ Nov 4 - Nov 8, 2025         │
│                             │
│ ┌─────────────────────────┐ │
│ │ Total Sales             │ │
│ │ KES 250,000             │ │
│ │ ▲ 15% vs last week      │ │
│ └─────────────────────────┘ │
│                             │
│ ┌──────┐ ┌──────┐ ┌──────┐ │
│ │Orders│ │Avg   │ │Cust. │ │
│ │ 125  │ │2,000 │ │  45  │ │
│ └──────┘ └──────┘ └──────┘ │
│                             │
│ Payment Methods             │
│ ┌─────────────────────────┐ │
│ │ Cash:    40% (100K)     │ │
│ │ ████████                │ │
│ │ M-Pesa:  45% (112.5K)   │ │
│ │ █████████               │ │
│ │ Credit:  15% (37.5K)    │ │
│ │ ███                     │ │
│ └─────────────────────────┘ │
│                             │
│ Top Products                │
│ 1. Coca Cola 500ml - 500 u  │
│ 2. Fanta 500ml - 350 u      │
│ 3. Sprite 500ml - 300 u     │
│                             │
│ [📊 View Charts]            │
│ [📄 Export Report]          │
│                             │
└─────────────────────────────┘
│ [🏠][👥][📦][📊][👤]        │
└─────────────────────────────┘
```

---

## 10. Profile & Settings

```
┌─────────────────────────────┐
│ ← Profile                   │
├─────────────────────────────┤
│      [PROFILE PHOTO]        │
│                             │
│      John Doe               │
│      Salesman               │
│      📞 0712345678          │
│                             │
│ ┌─────────────────────────┐ │
│ │ 📝 Edit Profile         │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ 🔒 Change Password      │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ 🔐 Biometric Settings   │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ 🔔 Notifications        │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ 🌐 Language             │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ ❓ Help & Support       │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ ℹ️ About                │ │
│ └─────────────────────────┘ │
│                             │
│ ┌─────────────────────────┐ │
│ │ 🚪 Logout               │ │
│ └─────────────────────────┘ │
│                             │
│ Version 1.0.0               │
│                             │
└─────────────────────────────┘
│ [🏠][👥][📦][📊][👤]        │
└─────────────────────────────┘
```

---

## Navigation Structure

### Bottom Tab Navigation
1. **Home** (🏠) - Dashboard
2. **Customers** (👥) - Customer list & routes
3. **Orders** (📦) - Orders & deliveries
4. **Reports** (📊) - Sales reports & analytics
5. **Profile** (👤) - Settings & profile

### Key Interactions
- **Swipe**: Refresh lists
- **Long Press**: Quick actions
- **Pull Down**: Refresh data
- **Tap**: Navigate to details
- **Double Tap**: Quick add to cart

---

## Color Scheme
- **Primary**: #007AFF (Blue)
- **Success**: #34C759 (Green)
- **Warning**: #FF9500 (Orange)
- **Danger**: #FF3B30 (Red)
- **Background**: #F2F2F7
- **Text**: #000000
