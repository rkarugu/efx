<?php

namespace App\Http\Controllers\Admin;

use App\DeliverySchedule;
use App\DeliveryScheduleCustomer;
use App\Exports\CustomerPaymentExport;
use App\Http\Controllers\Controller;
use App\Interfaces\MpesaPaymentInterface;
use App\InvoicePayment;
use App\Model\PaymentMethod;
use App\Model\WaInternalRequisition;
use App\Model\WaRouteCustomer;
use App\OrderLocationLog;
use App\Services\InfoSkySmsService;
use App\Services\MappingService;
use App\Services\PosPaymentService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class CustomerPaymentController extends Controller
{
    // public function __construct(protected MpesaPaymentInterface $mpesaPaymentService, protected InfoSkySmsService $smsService)
    // {
    // }

    public function confirm(Request $request): JsonResponse
    {
        try {
            Log::info('Customer Payment received');
            Log::info(json_encode($request->all()));

            $header = $request->header;
            $additionalData = $request->additionalData;
            $notificationData = Arr::get($additionalData, 'notificationData');
            $payload = [
                "header" => [
                    "messageID" => Arr::get($header, 'messageID'),
                    "originatorConversationID" => Arr::get($header, 'originatorConversationID'),
                    "statusCode" => "0",
                    "statusMessage" => "Notification received"
                ],
                "responsePayload" => [
                    "transactionInfo" => [
                        "transactionId" => Arr::get($notificationData, 'transactionID'),
                    ]
                ]

            ];

            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            Log::info('Customer Payment failed');
            Log::error($e->getMessage(), $e->getTrace());

            return $this->jsonify([], 500);
        }
    }

    public function receivePesaFlowCallBack(Request $request): void
    {
        Log::info("Pesaflow callback");
        Log::info(json_encode($request->all()));

        try {
            $payload = json_decode(json_encode($request->all()), true);
            $paymentRecord = InvoicePayment::latest()->where('order_no', $payload['client_invoice_ref'])->first();
            if ($paymentRecord->payable_type == 'App\Model\WaPosCashSales')
            {

                $PosService = new PosPaymentService();
                $PosService->paymentCallback($payload, $paymentRecord);

            }
            else{
                $paymentRecord->update([
                    'payment_invoice_no' => $payload['invoice_number'],
                    'payment_channel' => $payload['payment_channel'],
                    'payment_reference' => $payload['payment_reference'][0]['payment_reference'],
                    'paid_amount' => $payload['payment_reference'][0]['amount'],
                    'paying_number' => $payload['phone_number'],
                    'payment_date' => $payload['payment_reference'][0]['inserted_at'],
                    'status' => 'settled',
                ]);
            }

        } catch (\Throwable $e) {
            Log::info("Pesaflow callback update failed");
            Log::error($e->getMessage(), $e->getTrace());
        }
    }

    public function initiatePayment(Request $request): JsonResponse
    {
        try {
            $validations = Validator::make($request->all(), [
                'order_id' => 'required',
                'payment_method_id' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);

            if ($validations->fails()) {
                return $this->jsonify(['message' => $validations->errors(), 'payment_already_initiated' => false], 422);
            }

            $order = WaInternalRequisition::find($request->order_id);
            $customer = WaRouteCustomer::with('route')->find($order->wa_route_customer_id);
            $paymentMethod = PaymentMethod::with('provider')->find($request->payment_method_id);

            $locationLog = OrderLocationLog::where('shift_id', $order->wa_shift_id)->where('shop_id', $customer->id)->first();
            if ($locationLog) {
                $distance = MappingService::getTheaterDistanceBetweenTwoPoints($request->latitude, $request->longitude, $customer->lat, $customer->lng);
                $schedule = DeliverySchedule::where('shift_id', $order->wa_shift_id)->first();
                $locationLog->update([
                    'driver_id' => $schedule->driver_id,
                    'driver_lat' => $request->latitude,
                    'driver_lng' => $request->longitude,
                    'driver_distance' => $distance,
                    'driver_proximity' => $customer->route->salesman_proximity,
                    'delivery_id' => $schedule->id
                ]);

                if ($distance > 300) {
                    if ($distance <= 100) {
                        $customer->update([
                            'lat' => $request->latitude,
                            'lng' => $request->longitude,
                        ]);
                        $locationLog->update(['driver_status' => 'failed_with_update']);
                    } else {
                        $locationLog->update(['driver_status' => 'failed']);
                        return response()->json(['status' => false, 'message' => "You are outside the allowed delivery distance ($distance) from the shop."], 422);
                    }
                } else {
                    $locationLog->update(['driver_status' => 'passed']);
                }
            }

            if ($paymentMethod->slug == 'pay-later') {
                $order->update([
                    'status' => 'Delivered',
                    'delivery_date' => Carbon::now()
                ]);

                $deliveryCustomers = DeliveryScheduleCustomer::latest()->where('customer_id', $order->wa_route_customer_id)->get();
                foreach ($deliveryCustomers as $deliveryCustomer) {
                    $deliveryCustomer->update(['visited' => true]);
                }

                return $this->jsonify(['message' => 'Pay later request received successfully'], 200);
            }

            $existingPayment = InvoicePayment::latest()->where('order_id', $order->id)->first();
            if ($existingPayment) {
                return $this->jsonify(['message' => 'Payment already initiated', 'payment_already_initiated' => true], 422);
            }

            $payment = InvoicePayment::create([
                'order_id' => $order->id,
                'order_no' => $order->requisition_no,
                'truncated_order_no' => str_replace('INV-', '', $order->requisition_no),
                'payment_gateway' => $paymentMethod->provider->slug,
                'initiating_number' => $request->phone_number ?? $customer->phone,
                'invoice_amount' => $order->getRealCost(),
                'delivery_code' => random_int(100000, 999999),
                'status' => 'pending',
                'payable_id' => $order->id,
                'payable_type' => get_class($order)
            ]);

            $statusCode = 200;
            $message = 'Payment initiated successfully.';
            $pesaflowResponse = null;

            if ($paymentMethod->slug != 'pay-later') {
                $response = match ($paymentMethod->provider->slug) {
                    'mpesa' => $this->mpesaPaymentService->initiatePayment($order, $request->phone_number),
                    default => ['success' => false, 'message' => 'The selected payment method does not have a service provider.'],
                };

                $pesaflowResponse = $response['message'];
                if (!$response['success']) {
                    $statusCode = 422;
                    $message = $response['message'];
                } else {
                    $actualResponse = json_decode($response['message'], true);
                    $payment->update(['ps_invoice_number' => $actualResponse['invoice_number']]);
                }
            }

            return $this->jsonify(['message' => $message, 'ps' => $pesaflowResponse], $statusCode);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage(), 'data' => $e->getTrace(), 'payment_already_initiated' => false], 500);
        }
    }

    public function fetchPayment(Request $request): JsonResponse
    {
        try {
            $validations = Validator::make($request->all(), [
                'order_id' => 'required',
            ]);

            if ($validations->fails()) {
                return $this->jsonify(['message' => $validations->errors()], 422);
            }

            $order = WaInternalRequisition::with('payments')->find($request->order_id);
            if (!$order) {
                return $this->jsonify(['message' => 'An order matching the provided ID was not found'], 422);
            }

            $payment = $order->payments()->latest()->first();
            if (!$payment) {
                return $this->jsonify(['message' => 'Payment was not initiated.'], 422);
            }

            $payment = $order->payments()->latest()->first();
            if ($payment->status == 'pay_later') {
                $responsePayload = [
                    'total_amount' => $this->formatMoney(0),
                    'paybill_number' => '1002001', // Replace with route paybill
                    'order_number' => $payment->order_no,
                    'payment_reference' => 'PAY LATER',
                    'client_phone_number' => substr($payment->initiating_number, 0, 4) . str_repeat('*', (strlen($payment->initiating_number) - 5)) . substr($payment->initiating_number, -2),
                ];

                $message = "Your delivery code is $payment->delivery_code.";
                sendMessage($message, $payment->initiating_number);

                return $this->jsonify(['data' => $responsePayload], 200);
            }

            if ($payment->status == 'pending') {
                if (!$request->payment_reference) {
                    return $this->jsonify(['message' => 'Payment not found. Verification not received from PesaFlow'], 422);
                }

                $response = match ($payment->payment_gateway) {
                    'mpesa' => $this->mpesaPaymentService->fetchPayment($order, $payment, $request->payment_reference),
                    default => ['success' => false, 'message' => 'The selected payment method does not have a service provider.'],
                };

                if (!$response['success']) {
                    return $this->jsonify(['message' => $response['message'], 'payload' => $response['payload']], 422);
                }

                sleep(5);
                $newPayment = $order->payments()->latest()->where('status', 'settled')->first();
                if ($newPayment) {
                    $payment = $newPayment;
                } else {
                    return $this->jsonify(['message' => 'Payment not found. Verification not received from PesaFlow', 'payload' => $response['payload']], 422);
                }
            }

            $paymentAmount = $this->formatMoney($payment->paid_amount);
            $responsePayload = [
                'total_amount' => $paymentAmount,
                'paybill_number' => '1002001', // Replace with route paybill
                'order_number' => $payment->order_no,
                'payment_reference' => $payment->payment_reference,
                'client_phone_number' => substr($payment->initiating_number, 0, 4) . str_repeat('*', (strlen($payment->initiating_number) - 5)) . substr($payment->initiating_number, -2),
            ];

            $message = "Your payment of $paymentAmount for order $payment->order_no has been received. Your delivery code is $payment->delivery_code";
            sendMessage($message, $payment->initiating_number);

            return $this->jsonify(['data' => $responsePayload], 200);
        } catch (\Throwable $e) {
            return $this->jsonify(['message' => $e->getMessage()], 500);
        }
    }

    public function verifyDeliveryCode(Request $request): JsonResponse
    {
        $payload = [
            'message' => 'Delivery code verified successfully.',
            'status' => true
        ];

        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'order_id' => 'required',
                'delivery_code' => 'required',
            ]);

            if ($validator->fails()) {
                $payload['status'] = false;
                $payload['message'] = $this->validationHandle($validator->messages());
                return $this->jsonify($payload, 422);
            }

            $order = WaInternalRequisition::with('payments')->find($request->order_id);
            $payment = $order->payments()->latest()->whereIn('status', ['settled', 'pay_later'])->first();
            if ($request->delivery_code != $payment->delivery_code) {
                $payload['status'] = false;
                $payload['message'] = 'The provided delivery code is incorrect.';
                return $this->jsonify($payload, 422);
            }

            $payment->update(['status' => 'verified']);
            $order->update(['status' => 'COMPLETED']);

            $deliveryCustomers = DeliveryScheduleCustomer::latest()->where('customer_id', $order->wa_route_customer_id)->get();
            foreach ($deliveryCustomers as $deliveryCustomer) {
                $deliveryCustomer->update(['visited' => true]);
            }

            DB::commit();
            return $this->jsonify($payload, 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $customerPayments = InvoicePayment::whereDate('created_at', '>', '2024-03-07')->where('payment_gateway', 'mpesa')->whereIn('status', ['settled', 'verified'])
            ->whereNotNull('payment_reference')->get()->map(function (InvoicePayment $payment) {
                $invoice = WaInternalRequisition::find($payment->order_id);
                return [
                    'date' => Carbon::parse($payment->created_at)->format('Y-m-d'),
                    'invoice_no' => $payment->truncated_order_no,
                    'route' => $invoice->route,
                    'ps_code' => $payment->ps_invoice_number,
                    'amount_due' => $payment->invoice_amount,
                    'amount_paid' => $payment->paid_amount,
                    'reference' => $payment->payment_reference,
                ];
            });


        $export = new CustomerPaymentExport(collect($customerPayments));
        return Excel::download($export, 'Customer Payments 08032024 - 11032024.xlsx');
    }
}
