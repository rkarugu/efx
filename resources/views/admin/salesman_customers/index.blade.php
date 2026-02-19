@extends('layouts.admin.admin')

@section('content')
<section class="content">
    <div class="session-message-container">
        @include('message')
    </div>

    <!-- Customer Management Header -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="box-title">
                    <i class="fa fa-users"></i> Customer Management - {{ $user->name }}
                </h3>
                <div>
                    <button class="btn btn-success" onclick="showAddCustomerModal()">
                        <i class="fa fa-plus"></i> Add New Customer
                    </button>
                    <a href="{{ route('salesman-orders.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="box-body">
            <!-- Route Information -->
            <div class="alert alert-info">
                <strong>Route:</strong> {{ $routeInfo->route_name ?? 'Not Assigned' }} | 
                <strong>Total Customers:</strong> {{ $routeCustomers->count() }}
            </div>
        </div>
    </div>

    <!-- Customers List -->
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-list"></i> Route Customers
            </h3>
        </div>

        <div class="box-body">
            @if($routeCustomers->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="customersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Business Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Town</th>
                            <th>Delivery Center</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $counter = 1; @endphp
                        @foreach($routeCustomers as $customer)
                        <tr>
                            <td>{{ $counter++ }}</td>
                            <td>{{ $customer->bussiness_name }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->town }}</td>
                            <td>{{ $customer->center->name ?? 'N/A' }}</td>
                            <td>
                                <span class="label label-{{ $customer->status == 'approved' ? 'success' : ($customer->status == 'verified' ? 'info' : 'warning') }}">
                                    {{ ucfirst($customer->status) }}
                                </span>
                            </td>
                            <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewCustomer({{ $customer->id }})">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editCustomer({{ $customer->id }})">
                                    <i class="fa fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="alert alert-warning text-center">
                <i class="fa fa-info-circle"></i>
                No customers found for your route. Click "Add New Customer" to start building your customer base.
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Add Customer Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add New Customer</h4>
            </div>
            <div class="modal-body">
                <form id="addCustomerForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business Name <span class="text-red">*</span></label>
                                <input type="text" name="business_name" class="form-control" required maxlength="200">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Person <span class="text-red">*</span></label>
                                <input type="text" name="name" class="form-control" required maxlength="200">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone Number <span class="text-red">*</span></label>
                                <input type="tel" name="phone_no" class="form-control" required minlength="9" maxlength="12">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Town <span class="text-red">*</span></label>
                                <input type="text" name="town" class="form-control" required maxlength="200">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Delivery Center <span class="text-red">*</span></label>
                                <select name="center_id" class="form-control select2" required>
                                    <option value="">Select Delivery Center</option>
                                    @foreach($deliveryCenters as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>KRA PIN</label>
                                <input type="text" name="kra_pin" class="form-control" maxlength="20">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Customer Type</label>
                                <input type="text" name="customer_type" class="form-control" maxlength="50" placeholder="e.g., Retail, Wholesale">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" name="latitude" class="form-control" step="any" placeholder="GPS Latitude">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" name="longitude" class="form-control" step="any" placeholder="GPS Longitude">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="button" class="btn btn-default" onclick="fillCurrentLocation('addCustomerForm')">
                                    <i class="fa fa-map-marker"></i> Use Current Location
                                </button>
                                <span id="addLocationStatus" style="margin-left: 10px;"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Secondary Contact Name</label>
                                <input type="text" name="secondary_name" class="form-control" maxlength="200">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Secondary Phone Number</label>
                                <input type="tel" name="secondary_phone_no" class="form-control" minlength="9" maxlength="12">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Comments</label>
                                <textarea name="comment" class="form-control" rows="3" placeholder="Additional notes about the customer"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Shop Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <small class="text-muted">Optional. Helps with mapping and verification.</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveCustomer()">Add Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Edit Customer</h4>
            </div>
            <div class="modal-body">
                <form id="editCustomerForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editCustomerId" name="customer_id">
                    
                    <!-- Same form fields as add customer modal -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Business Name <span class="text-red">*</span></label>
                                <input type="text" name="business_name" id="editBusinessName" class="form-control" required maxlength="200">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Person <span class="text-red">*</span></label>
                                <input type="text" name="name" id="editName" class="form-control" required maxlength="200">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Phone Number <span class="text-red">*</span></label>
                                <input type="tel" name="phone_no" id="editPhone" class="form-control" required minlength="9" maxlength="12">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Town <span class="text-red">*</span></label>
                                <input type="text" name="town" id="editTown" class="form-control" required maxlength="200">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Delivery Center <span class="text-red">*</span></label>
                                <select name="center_id" id="editCenterId" class="form-control select2" required>
                                    <option value="">Select Delivery Center</option>
                                    @foreach($deliveryCenters as $center)
                                        <option value="{{ $center->id }}">{{ $center->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>KRA PIN</label>
                                <input type="text" name="kra_pin" id="editKraPin" class="form-control" maxlength="20">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" id="editGender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Customer Type</label>
                                <input type="text" name="customer_type" id="editCustomerType" class="form-control" maxlength="50">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="number" name="latitude" id="editLatitude" class="form-control" step="any">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="number" name="longitude" id="editLongitude" class="form-control" step="any">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Secondary Contact Name</label>
                                <input type="text" name="secondary_name" id="editSecondaryName" class="form-control" maxlength="200">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Secondary Phone Number</label>
                                <input type="tel" name="secondary_phone_no" id="editSecondaryPhone" class="form-control" minlength="9" maxlength="12">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Comments</label>
                                <textarea name="comment" id="editComment" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="updateCustomer()">Update Customer</button>
            </div>
        </div>
    </div>
</div>

<!-- View Customer Modal -->
<div class="modal fade" id="viewCustomerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Customer Details</h4>
            </div>
            <div class="modal-body" id="customerDetailsContent">
                <!-- Customer details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#customersTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[7, 'desc']] // Sort by date added
    });

    // Initialize Select2
    $('.select2').select2();
});

function showAddCustomerModal() {
    $('#addCustomerForm')[0].reset();
    $('#addCustomerModal').modal('show');
}

function saveCustomer() {
    const form = document.getElementById('addCustomerForm');
    const formData = new FormData(form);

    fetch('{{ route("salesman-customers.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.status === 'duplicate') {
                alert('Customer saved as DUPLICATE and is awaiting approval.');
            } else {
                alert('Customer added successfully!');
            }
            location.reload();
        } else {
            if (data.errors) {
                let errorMsg = 'Please fix the following errors:\n';
                Object.keys(data.errors).forEach(key => {
                    errorMsg += `- ${data.errors[key][0]}\n`;
                });
                alert(errorMsg);
            } else {
                alert(data.message || 'Error adding customer');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding customer');
    });
}

function editCustomer(customerId) {
    fetch(`{{ route("salesman-customers.show", ":id") }}`.replace(':id', customerId))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const customer = data.customer;
            
            // Populate edit form
            document.getElementById('editCustomerId').value = customer.id;
            document.getElementById('editBusinessName').value = customer.bussiness_name;
            document.getElementById('editName').value = customer.name;
            document.getElementById('editPhone').value = customer.phone;
            document.getElementById('editTown').value = customer.town;
            document.getElementById('editCenterId').value = customer.delivery_centres_id;
            document.getElementById('editKraPin').value = customer.kra_pin || '';
            document.getElementById('editGender').value = customer.gender || '';
            document.getElementById('editCustomerType').value = customer.customer_type || '';
            document.getElementById('editLatitude').value = customer.lat || '';
            document.getElementById('editLongitude').value = customer.lng || '';
            document.getElementById('editSecondaryName').value = customer.secondary_name || '';
            document.getElementById('editSecondaryPhone').value = customer.secondary_phone_no || '';
            document.getElementById('editComment').value = customer.comment || '';
            
            $('#editCustomerModal').modal('show');
        } else {
            alert(data.message || 'Error loading customer details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading customer details');
    });
}

function updateCustomer() {
    const customerId = document.getElementById('editCustomerId').value;
    const form = document.getElementById('editCustomerForm');

    const formData = new FormData(form);
    
    // Convert FormData to JSON
    const data = {};
    formData.forEach((value, key) => {
        if (key !== 'customer_id' && key !== '_method') {
            data[key] = value;
        }
    });

    fetch(`{{ route("salesman-customers.update", ":id") }}`.replace(':id', customerId), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Customer updated successfully!');
            location.reload();
        } else {
            if (data.errors) {
                let errorMsg = 'Please fix the following errors:\n';
                Object.keys(data.errors).forEach(key => {
                    errorMsg += `- ${data.errors[key][0]}\n`;
                });
                alert(errorMsg);
            } else {
                alert(data.message || 'Error updating customer');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating customer');
    });
}

function viewCustomer(customerId) {
    fetch(`{{ route("salesman-customers.show", ":id") }}`.replace(':id', customerId))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const customer = data.customer;
            
            const detailsHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td><strong>Business Name:</strong></td><td>${customer.bussiness_name}</td></tr>
                            <tr><td><strong>Contact Person:</strong></td><td>${customer.name}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>${customer.phone}</td></tr>
                            <tr><td><strong>Town:</strong></td><td>${customer.town}</td></tr>
                            <tr><td><strong>KRA PIN:</strong></td><td>${customer.kra_pin || 'N/A'}</td></tr>
                            <tr><td><strong>Gender:</strong></td><td>${customer.gender || 'N/A'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr><td><strong>Delivery Center:</strong></td><td>${customer.center ? customer.center.name : 'N/A'}</td></tr>
                            <tr><td><strong>Customer Type:</strong></td><td>${customer.customer_type || 'N/A'}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="label label-success">${customer.status}</span></td></tr>
                            <tr><td><strong>Date Added:</strong></td><td>${new Date(customer.created_at).toLocaleDateString()}</td></tr>
                            <tr><td><strong>Secondary Contact:</strong></td><td>${customer.secondary_name || 'N/A'}</td></tr>
                            <tr><td><strong>Secondary Phone:</strong></td><td>${customer.secondary_phone_no || 'N/A'}</td></tr>
                        </table>
                    </div>
                </div>
                ${customer.comment ? `<div class="row"><div class="col-md-12"><strong>Comments:</strong><br>${customer.comment}</div></div>` : ''}
                ${customer.lat && customer.lng ? `<div class="row"><div class="col-md-12"><strong>GPS Coordinates:</strong> ${customer.lat}, ${customer.lng}</div></div>` : ''}
            `;
            
            document.getElementById('customerDetailsContent').innerHTML = detailsHtml;
            $('#viewCustomerModal').modal('show');
        } else {
            alert(data.message || 'Error loading customer details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading customer details');
    });
}

function fillCurrentLocation(formId) {
    const form = document.getElementById(formId);
    const statusEl = document.getElementById('addLocationStatus');
    if (!navigator.geolocation) {
        if (statusEl) statusEl.innerText = 'Geolocation not supported by this browser.';
        return;
    }

    if (statusEl) statusEl.innerText = 'Getting location...';

    navigator.geolocation.getCurrentPosition(
        function (pos) {
            const latInput = form.querySelector('input[name="latitude"]');
            const lngInput = form.querySelector('input[name="longitude"]');
            if (latInput) latInput.value = pos.coords.latitude;
            if (lngInput) lngInput.value = pos.coords.longitude;
            if (statusEl) statusEl.innerText = 'Location captured.';
        },
        function (err) {
            if (statusEl) statusEl.innerText = 'Failed to get location: ' + (err && err.message ? err.message : 'Unknown error');
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}
</script>

<style>
.table-borderless td {
    border: none !important;
    padding: 5px 10px;
}
</style>
@endsection
