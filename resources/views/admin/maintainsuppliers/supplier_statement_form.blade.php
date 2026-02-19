@extends('layouts.admin.admin')

@section('content')
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
        </div>
        @include('message')
        <div class="box-body">
            <form action="{{ route('maintain-suppliers.supplier-statement') }}" method="GET">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_code" class="form-control select2" required>
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->supplier_code }}">
                                        {{ $supplier->supplier_code }} - {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="from" class="form-control" value="{{ $from }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="to" class="form-control" value="{{ $to }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-file-pdf-o"></i> Generate Statement
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@section('uniquepagescript')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Select Supplier',
            allowClear: true
        });
    });
</script>
@endsection
