# Vehicle Driver Dropdown Fix

## Issue
On the vehicle center page (`/admin/vehicles/vehicle-center/55`), the "Assign Driver" modal's dropdown was not working. The dropdown showed "Select driver" but clicking it did nothing - no options appeared.

## Root Cause
The issue was caused by Select2 initialization timing and configuration problems:

1. **Timing Issue**: Select2 was being initialized via `$nextTick` immediately after the API call, but the modal might not have been fully rendered yet
2. **Missing dropdownParent**: Select2 dropdowns inside Bootstrap modals need the `dropdownParent` option set to the modal element, otherwise the dropdown gets hidden behind the modal backdrop

## Solution

### 1. Added Delay for Select2 Initialization
Added a 300ms timeout to ensure the modal is fully shown before initializing Select2:

**File: `resources/views/admin/vehicles/vehicle_centre.blade.php` (Lines 422-428)**

```javascript
// Initialize Select2 after data is loaded and modal is shown
this.$nextTick(() => {
    // Wait for modal to be fully shown before initializing Select2
    setTimeout(() => {
        this.initializeSelect2();
    }, 300);
});
```

### 2. Added dropdownParent Configuration
Set the `dropdownParent` option to ensure the Select2 dropdown appears correctly within the modal:

**File: `resources/views/admin/vehicles/vehicle_centre.blade.php` (Lines 646-649)**

```javascript
$driverSelect.select2({
    placeholder: 'Select driver',
    allowClear: true,
    dropdownParent: $('#driver-assignment-modal')  // Critical fix
})
```

### 3. Added Comprehensive Logging
Added console logging to help debug issues:

```javascript
console.log('Initializing Select2 for driver dropdown');
console.log('Number of available drivers:', this.availableDrivers.length);
console.log('Driver selected:', e.target.value);
console.log('Select2 initialized successfully');
```

### 4. Added Error Handling
Added error handling for the API call:

```javascript
.catch(err => {
    console.error('Error fetching available drivers:', err);
    toastr.error('Failed to load available drivers');
})
```

### 5. Added Element Existence Check
Added check to ensure the select element exists before initializing:

```javascript
const $driverSelect = $('#driver_id');

if ($driverSelect.length === 0) {
    console.error('Driver select element not found');
    return;
}
```

## How It Works

### Flow:
1. User clicks "Assign Driver" button (user icon)
2. `promptAssignDriver(vehicle)` is called
3. `fetchAvailableDrivers()` fetches drivers from `/api/vehicles/available-drivers`
4. Modal is shown: `$('#driver-assignment-modal').modal('show')`
5. After 300ms delay, Select2 is initialized with `dropdownParent` set to the modal
6. User can now click the dropdown and see available drivers
7. Selecting a driver updates `selectedDriverId`
8. Clicking "Assign" button calls `assignDriver()` API

### API Endpoint:
- **URL**: `/api/vehicles/available-drivers`
- **Method**: GET
- **Parameters**: `branch_id` (current user's restaurant_id)
- **Returns**: Users with `role_id = 6` (drivers) who don't have a vehicle assigned

## Testing

### To verify the fix:
1. Open vehicle center page: `http://127.0.0.1:8000/admin/vehicles/vehicle-center/55`
2. Open browser console (F12)
3. Click the "Assign Driver" button (user icon) on a vehicle without a driver
4. Check console logs:
   - Should see: `"Fetching available drivers for branch: [ID]"`
   - Should see: `"Available drivers response: {...}"`
   - Should see: `"Number of available drivers: [N]"`
   - Should see: `"Initializing Select2 for driver dropdown"`
   - Should see: `"Select2 initialized successfully"`
5. Click the dropdown - it should now show available drivers
6. Select a driver and click "Assign"

### If No Drivers Appear:
Check the console log for "Number of available drivers". If it's 0, then:
- There are no users with `role_id = 6` (delivery driver role)
- Or all drivers already have vehicles assigned
- Or the drivers are in a different branch

## Files Changed

- `resources/views/admin/vehicles/vehicle_centre.blade.php`
  - Lines 411-432: Updated `fetchAvailableDrivers()` method
  - Lines 629-656: Updated `initializeSelect2()` method

## Related Code

### Backend Controller:
**File**: `app/Http/Controllers/Admin/VehicleController.php`

```php
public function getAvailableDrivers(Request $request): JsonResponse
{
    try {
        $query = User::select('id', 'name', 'role_id', 'restaurant_id')
            ->doesntHave('vehicle')
            ->where('role_id', 6);

        // Filter by branch if provided
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('restaurant_id', $request->branch_id);
        }

        $users = $query->get();

        return $this->jsonify(['data' => $users], 200);
    } catch (\Throwable $e) {
        return $this->jsonify(['message' => $e->getMessage()], 500);
    }
}
```

### Modal HTML:
```html
<select name="driver_id" id="driver_id" v-model="selectedDriverId" class="form-control">
    <option value="" selected disabled> Select driver</option>
    <option v-for="driver in availableDrivers" :key="driver.id" :value="driver.id">
        @{{ driver.name }}
    </option>
</select>
```

## Important Notes

1. **dropdownParent is Critical**: When using Select2 inside Bootstrap modals, always set `dropdownParent` to the modal element
2. **Timing Matters**: Ensure Select2 initializes after the modal is fully shown and the DOM is ready
3. **Console Logging**: The added logs will help diagnose future issues
4. **Role ID**: Drivers must have `role_id = 6` to appear in the dropdown
5. **Vehicle Assignment**: Only drivers without an assigned vehicle will appear

## Date
November 4, 2025
