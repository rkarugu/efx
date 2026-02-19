# Kaninichapchap Mobile API Documentation

## Base URL
```
Production: https://kaninichapchap.efficentrix.co.ke/api
Development: http://127.0.0.1:8000/api
```

## Authentication
All authenticated endpoints require JWT token in the Authorization header:
```
Authorization: Bearer {token}
```

---

## 1. Authentication APIs

### 1.1 Login
**Endpoint:** `POST /getLogin`

**Request Body:**
```json
{
  "phone_number": "0712345678",
  "password": "password123",
  "device_id": "unique-device-id"
}
```

**Response:**
```json
{
  "status": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "phone_number": "0712345678",
    "role_id": 5,
    "role": {
      "id": 5,
      "title": "Salesman"
    }
  }
}
```

### 1.2 Validate Phone Number
**Endpoint:** `POST /auth/validate-user-phonenumber`

**Request Body:**
```json
{
  "phone_number": "0712345678"
}
```

**Response:**
```json
{
  "status": true,
  "message": "OTP sent successfully",
  "otp_id": "12345"
}
```

### 1.3 Validate OTP
**Endpoint:** `POST /auth/validate-otp`

**Request Body:**
```json
{
  "phone_number": "0712345678",
  "otp": "123456",
  "otp_id": "12345"
}
```

**Response:**
```json
{
  "status": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {...}
}
```

### 1.4 Reset Password
**Endpoint:** `POST /reset-account-password`

**Request Body:**
```json
{
  "phone_number": "0712345678"
}
```

---

## 2. Salesman APIs

### 2.1 Get Inventory Items
**Endpoint:** `GET /get-inventory-item`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `search` (optional): Search term
- `category_id` (optional): Filter by category
- `page` (optional): Page number for pagination

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "id": 1,
      "stock_id_code": "ITM001",
      "title": "Coca Cola 500ml",
      "category": "Beverages",
      "price": 50.00,
      "quantity_on_hand": 100,
      "image_url": "https://..."
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 100
  }
}
```

### 2.2 Get Customers by Route
**Endpoint:** `POST /getCustomer`

**Request Body:**
```json
{
  "token": "jwt-token",
  "route_id": 5
}
```

**Response:**
```json
{
  "status": true,
  "customers": [
    {
      "id": 1,
      "name": "ABC Store",
      "phone": "0722123456",
      "location": "Nairobi",
      "credit_limit": 50000,
      "outstanding_balance": 15000,
      "latitude": -1.286389,
      "longitude": 36.817223
    }
  ]
}
```

### 2.3 Get Payment Methods
**Endpoint:** `POST /getPaymentMethod`

**Request Body:**
```json
{
  "token": "jwt-token"
}
```

**Response:**
```json
{
  "status": true,
  "payment_methods": [
    {
      "id": 1,
      "name": "Cash",
      "code": "CASH"
    },
    {
      "id": 2,
      "name": "M-Pesa",
      "code": "MPESA"
    },
    {
      "id": 3,
      "name": "Credit",
      "code": "CREDIT"
    }
  ]
}
```

### 2.4 Create Sales Order
**Endpoint:** `POST /sales_order_checkout`

**Request Body:**
```json
{
  "token": "jwt-token",
  "customer_id": 123,
  "payment_method_id": 1,
  "items": [
    {
      "inventory_item_id": 1,
      "quantity": 10,
      "price": 50.00,
      "discount": 0
    }
  ],
  "total_amount": 500.00,
  "payment_reference": "MPESA123456",
  "notes": "Deliver by 3pm"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Order created successfully",
  "order": {
    "id": 456,
    "order_number": "SO-2025-001",
    "total_amount": 500.00,
    "created_at": "2025-11-08 20:15:00"
  }
}
```

### 2.5 Get Route List
**Endpoint:** `POST /getroutelist`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123
}
```

**Response:**
```json
{
  "status": true,
  "routes": [
    {
      "id": 1,
      "name": "Route A - Nairobi CBD",
      "customer_count": 25,
      "total_value": 150000
    }
  ]
}
```

### 2.6 Get Shift List
**Endpoint:** `POST /getShiftlist`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123
}
```

**Response:**
```json
{
  "status": true,
  "shifts": [
    {
      "id": 1,
      "shift_date": "2025-11-08",
      "start_time": "08:00:00",
      "end_time": "17:00:00",
      "status": "open"
    }
  ]
}
```

### 2.7 Open Shift
**Endpoint:** `POST /postOpenShift`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123,
  "vehicle_id": 5,
  "route_id": 3,
  "opening_cash": 5000.00,
  "opening_mileage": 12500
}
```

**Response:**
```json
{
  "status": true,
  "message": "Shift opened successfully",
  "shift": {
    "id": 789,
    "shift_number": "SH-2025-001",
    "opened_at": "2025-11-08 08:00:00"
  }
}
```

### 2.8 Close Shift
**Endpoint:** `POST /closeShift`

**Request Body:**
```json
{
  "token": "jwt-token",
  "shift_id": 789,
  "closing_cash": 15000.00,
  "closing_mileage": 12650,
  "expenses": [
    {
      "expense_type_id": 1,
      "amount": 500.00,
      "description": "Fuel"
    }
  ]
}
```

**Response:**
```json
{
  "status": true,
  "message": "Shift closed successfully",
  "summary": {
    "total_sales": 50000.00,
    "total_cash": 15000.00,
    "total_mpesa": 25000.00,
    "total_credit": 10000.00,
    "total_expenses": 500.00
  }
}
```

### 2.9 Post Return Sales
**Endpoint:** `POST /postreturnsales`

**Request Body:**
```json
{
  "token": "jwt-token",
  "customer_id": 123,
  "items": [
    {
      "inventory_item_id": 1,
      "quantity": 2,
      "reason": "Damaged",
      "price": 50.00
    }
  ],
  "total_amount": 100.00
}
```

**Response:**
```json
{
  "status": true,
  "message": "Return processed successfully",
  "return_number": "RET-2025-001"
}
```

### 2.10 Get Debtor List
**Endpoint:** `POST /getmydebtorlist`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123
}
```

**Response:**
```json
{
  "status": true,
  "debtors": [
    {
      "customer_id": 1,
      "customer_name": "ABC Store",
      "outstanding_balance": 15000.00,
      "overdue_amount": 5000.00,
      "last_payment_date": "2025-10-15"
    }
  ]
}
```

### 2.11 Post Debtor Payment
**Endpoint:** `POST /postDebtorPayment`

**Request Body:**
```json
{
  "token": "jwt-token",
  "customer_id": 123,
  "amount": 5000.00,
  "payment_method_id": 2,
  "payment_reference": "MPESA789456",
  "notes": "Partial payment"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Payment recorded successfully",
  "receipt_number": "RCP-2025-001",
  "new_balance": 10000.00
}
```

### 2.12 Get Expenses List
**Endpoint:** `POST /getexpenseslist`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123
}
```

**Response:**
```json
{
  "status": true,
  "expense_types": [
    {
      "id": 1,
      "name": "Fuel",
      "requires_receipt": true
    },
    {
      "id": 2,
      "name": "Parking",
      "requires_receipt": false
    }
  ]
}
```

### 2.13 Post Expense
**Endpoint:** `POST /postexpensesdata`

**Request Body:**
```json
{
  "token": "jwt-token",
  "shift_id": 789,
  "expense_type_id": 1,
  "amount": 500.00,
  "description": "Fuel for delivery",
  "receipt_image": "base64-encoded-image"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Expense recorded successfully",
  "expense_id": 456
}
```

### 2.14 Get Sales Summary
**Endpoint:** `POST /getShiftSalesSummary`

**Request Body:**
```json
{
  "token": "jwt-token",
  "shift_id": 789
}
```

**Response:**
```json
{
  "status": true,
  "summary": {
    "total_orders": 25,
    "total_sales": 50000.00,
    "cash_sales": 15000.00,
    "mpesa_sales": 25000.00,
    "credit_sales": 10000.00,
    "returns": 500.00,
    "expenses": 500.00,
    "net_sales": 49500.00
  }
}
```

---

## 3. Delivery APIs

### 3.1 Get Delivery Schedule
**Endpoint:** `GET /webRouteDeliveryCentres`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "status": true,
  "deliveries": [
    {
      "id": 1,
      "order_number": "SO-2025-001",
      "customer_name": "ABC Store",
      "customer_phone": "0722123456",
      "delivery_address": "123 Main St, Nairobi",
      "latitude": -1.286389,
      "longitude": 36.817223,
      "total_amount": 5000.00,
      "status": "pending",
      "items": [
        {
          "id": 1,
          "name": "Coca Cola 500ml",
          "quantity": 10,
          "delivered_quantity": 0
        }
      ]
    }
  ]
}
```

### 3.2 Deliver Items
**Endpoint:** `POST /deliverItems`

**Request Body:**
```json
{
  "token": "jwt-token",
  "order_id": 1,
  "items": [
    {
      "item_id": 1,
      "delivered_quantity": 10
    }
  ],
  "signature": "base64-encoded-signature",
  "photo": "base64-encoded-photo",
  "notes": "Delivered successfully"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Items delivered successfully"
}
```

### 3.3 Return Items
**Endpoint:** `POST /returnItems`

**Request Body:**
```json
{
  "token": "jwt-token",
  "order_id": 1,
  "items": [
    {
      "item_id": 1,
      "return_quantity": 2,
      "reason_id": 3,
      "notes": "Damaged packaging"
    }
  ],
  "photo": "base64-encoded-photo"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Return recorded successfully",
  "return_number": "RET-2025-001"
}
```

### 3.4 Get Return Reasons
**Endpoint:** `GET /itemReturnReasons`

**Response:**
```json
{
  "status": true,
  "reasons": [
    {
      "id": 1,
      "reason": "Damaged"
    },
    {
      "id": 2,
      "reason": "Expired"
    },
    {
      "id": 3,
      "reason": "Customer Refused"
    }
  ]
}
```

### 3.5 Mark Order as Delivered
**Endpoint:** `POST /orderDelivered`

**Request Body:**
```json
{
  "token": "jwt-token",
  "order_id": 1,
  "delivered_at": "2025-11-08 14:30:00",
  "latitude": -1.286389,
  "longitude": 36.817223
}
```

**Response:**
```json
{
  "status": true,
  "message": "Order marked as delivered"
}
```

---

## 4. Reports APIs

### 4.1 Daily Sales Summary
**Endpoint:** `POST /dailySalesSummary`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123,
  "date": "2025-11-08"
}
```

**Response:**
```json
{
  "status": true,
  "summary": {
    "date": "2025-11-08",
    "total_sales": 50000.00,
    "total_orders": 25,
    "cash_sales": 15000.00,
    "mpesa_sales": 25000.00,
    "credit_sales": 10000.00
  }
}
```

### 4.2 Monthly Sales Summary
**Endpoint:** `POST /monthlySalesSummary`

**Request Body:**
```json
{
  "token": "jwt-token",
  "user_id": 123,
  "month": "11",
  "year": "2025"
}
```

**Response:**
```json
{
  "status": true,
  "summary": {
    "month": "November 2025",
    "total_sales": 500000.00,
    "total_orders": 250,
    "average_order_value": 2000.00,
    "daily_breakdown": [...]
  }
}
```

### 4.3 Salesman Performance Report
**Endpoint:** `POST /reports/salesman-performance-report`

**Request Body:**
```json
{
  "from_date": "2025-11-01",
  "to_date": "2025-11-08",
  "salesman_id": 123
}
```

**Response:**
```json
{
  "status": true,
  "performance": {
    "total_sales": 500000.00,
    "total_orders": 250,
    "customers_visited": 50,
    "average_order_value": 2000.00,
    "target_achievement": 85.5
  }
}
```

---

## 5. Common Response Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 401 | Unauthorized - Invalid or expired token |
| 403 | Forbidden - Insufficient permissions |
| 404 | Not Found - Resource doesn't exist |
| 422 | Validation Error |
| 500 | Server Error |

---

## 6. Error Response Format

```json
{
  "status": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## 7. Image Upload Format

All image uploads should be base64 encoded strings:

```json
{
  "image": "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD..."
}
```

---

## 8. Pagination

Paginated endpoints return:

```json
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total_pages": 10,
    "total_items": 200,
    "has_more": true
  }
}
```

---

## 9. Testing

**Test Credentials:**
```
Phone: 0712345678
Password: test123
```

**Postman Collection:** Available at `/docs/postman_collection.json`

---

## 10. Rate Limiting

- **Rate Limit:** 100 requests per minute per user
- **Header:** `X-RateLimit-Remaining: 95`

---

## Support

For API support, contact: dev@efficentrix.com
