<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $data->sales_no }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .header {
            margin-bottom: 20px;
        }
        .customer-details, .invoice-details {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Invoice #{{ $data->sales_no }}</h2>
    </div>

    <div class="customer-details">
        <h4>Customer Details</h4>
        <p><strong>Name:</strong> {{ $data->customer }}</p>
        <p><strong>Phone:</strong> {{ $data->customer_phone_number }}</p>
        <p><strong>PIN:</strong> {{ $data->customer_pin }}</p>
    </div>

    <div class="invoice-details">
        <h4>Invoice Details</h4>
        <p><strong>Date:</strong> {{ $data->date }}</p>
        <p><strong>Time:</strong> {{ $data->time }}</p>
        <p><strong>Cashier:</strong> {{ $data->user->name }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data->items as $item)
                <tr>
                    <td>{{ $item->item->title }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format($item->selling_price, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Subtotal:</th>
                <th>{{ number_format($data->items->sum('total'), 2) }}</th>
            </tr>
            <tr>
                <th colspan="3" class="text-right">VAT:</th>
                <th>{{ number_format($data->items->sum('vat_amount'), 2) }}</th>
            </tr>
            <tr>
                <th colspan="3" class="text-right">Total:</th>
                <th>{{ number_format($data->items->sum('total') + $data->items->sum('vat_amount'), 2) }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="payment-details">
        <h4>Payment Details</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment['title'] }}</td>
                        <td>{{ number_format($payment['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($esd_details && $esd_details->verify_url)
        <div class="esd-details">
            <h4>ESD Details</h4>
            <p><strong>Status:</strong> {{ $esd_details->description }}</p>
            <p><strong>Verification URL:</strong> {{ $esd_details->verify_url }}</p>
        </div>
    @endif
</body>
</html>
@extends('layouts.admin.admin')