# Salesman Web Login Setup Guide

## Overview
This system now allows salesmen to log into the web interface to perform order taking and customer management tasks that were previously only available through the mobile app.

## Configuration

### Environment Variables
Add the following to your `.env` file to enable salesman web login:

```env
# Allow salesmen to log into web interface (default: true)
ALLOW_SALESMAN_WEB_LOGIN=true

# Disable OTP for development/testing (optional)
USE_OTP=false
```

### Configuration File
The system uses `config/salesman.php` for configuration:

```php
'allow_web_login' => env('ALLOW_SALESMAN_WEB_LOGIN', true),
'sales_role_ids' => [169, 170], // Known sales role IDs
'sales_role_keywords' => ['sales', 'salesman', 'representative'], // Keywords in role names
```

## How It Works

### User Detection
The system identifies salesmen using multiple criteria:
1. **Route Assignment**: Users with an assigned route (`user->route`)
2. **Role IDs**: Users with role IDs 169, 170 (configurable)
3. **Role Names**: Users whose role name contains 'sales', 'salesman', or 'representative'

### Login Process
1. Salesman enters username/password at `/admin/login`
2. System checks if user is a salesman using the criteria above
3. If `ALLOW_SALESMAN_WEB_LOGIN=true`, salesman is allowed to log in
4. System creates proper session and redirects to dashboard

## Features Available to Salesmen

### 1. Order Taking Dashboard (`/admin/salesman-orders`)
- View shift status (active/inactive)
- Open/close shifts
- View today's orders and sales statistics
- Quick access to order creation and customer management

### 2. Order Creation (`/admin/salesman-orders/create`)
- Create orders for route customers
- Real-time inventory checking
- Dynamic item selection with pricing
- Order totaling with discount support

### 3. Customer Management (`/admin/salesman-customers`)
- Add new customers to route
- Edit existing customer information
- View customer details and history
- GPS coordinate support

### 4. Order Details (`/admin/salesman-orders/{id}`)
- View complete order information
- Print order receipts
- Track order status

## Navigation
Salesmen will see the following menu items in the sidebar under "Salesman Invoice":
- **Order Taking** - Main dashboard and order management
- **Customer Management** - Add and manage route customers

## Testing

### 1. Create Test Salesman User
```sql
-- Create a test salesman user
INSERT INTO users (name, email, phone_number, password, role_id, route, status, restaurant_id) 
VALUES ('Test Salesman', 'salesman@test.com', '0712345678', '$2y$10$hash', 169, 1, 1, 1);
```

### 2. Test Login
1. Go to `/admin/login`
2. Enter salesman credentials
3. Should successfully log in and see salesman dashboard

### 3. Test Salesman Detection
1. After logging in as a salesman, visit `/admin/salesman-test`
2. This will show JSON response with user detection details:
   - `is_salesman`: Should be `true`
   - `can_access_salesman_urls`: Should be `true`
   - `salesman_urls`: Shows the available URLs

### 4. Test Access to Salesman URLs
After successful login, test these URLs directly:
- **Dashboard**: `/admin/salesman-orders`
- **Create Order**: `/admin/salesman-orders/create`
- **Customer Management**: `/admin/salesman-customers`

### 5. Test Order Creation
1. Open a shift from the dashboard
2. Navigate to "Create New Order"
3. Select a customer and add items
4. Submit the order

## Troubleshooting

### Login Issues
- Check that `ALLOW_SALESMAN_WEB_LOGIN=true` in `.env`
- Verify user has route assignment or sales role
- Check user status is active (status = 1)

### Permission Issues
- Ensure user role is not in restricted list [4, 181]
- Verify route assignment exists
- Check role name contains sales keywords

### Navigation Issues
- Clear browser cache
- Check that routes are properly registered
- Verify middleware is not blocking access

## Security Notes
- Salesmen can only see their own route customers
- Orders are linked to active shifts
- All actions are logged for audit purposes
- Session timeout is configurable (default: 15 minutes)

## Database Tables Used
- `users` - User authentication and role information
- `salesman_shifts` - Shift management
- `wa_internal_requisitions` - Orders
- `wa_internal_requisition_items` - Order line items
- `wa_route_customers` - Customer information
- `routes` - Route assignments

## API Compatibility
The web interface uses the same underlying models and business logic as the mobile app, ensuring data consistency and compatibility.
