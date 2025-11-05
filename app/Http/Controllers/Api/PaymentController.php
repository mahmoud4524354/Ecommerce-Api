<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    // ... existing Stripe methods remain the same

    /**
     * Create a PayPal payment for an order
     *
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createPayPalPayment(Order $order)
    {
        try {
            $paypalService = new PayPalService();

            // Create a payment record first
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'provider' => PaymentProvider::PAYPAL,
                'amount' => $order->total,
                'currency' => 'USD',                    // Hardcoded for this example
                'status' => PaymentStatus::PENDING,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'created_at' => now()->toIso8601String(),
                ]
            ]);

            // Prepare metadata for PayPal order creation
            $metadata = [
                'description' => "Payment for Order #{$order->order_number}",
                'order_number' => $order->order_number,
                'return_url' => config('app.url') . "/api/payments/paypal/success?payment_id={$payment->id}",
                'cancel_url' => config('app.url') . "/api/payments/paypal/cancel?payment_id={$payment->id}",
            ];

            // Create PayPal order using our service
            $paypalOrder = $paypalService->createOrder($order->total, 'USD', $metadata);

            // Update payment record with PayPal order ID
            $payment->update([
                'paypal_order_id' => $paypalOrder['id'],
                'metadata' => array_merge($payment->metadata ?? [], [
                    'paypal_order' => $paypalOrder
                ]),
            ]);

            // Extract approval URL from PayPal response
            $approvalUrl = null;
            foreach ($paypalOrder['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            // Return payment details to frontend
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'paypal_order_id' => $paypalOrder['id'],
                'approval_url' => $approvalUrl,
                'status' => $paypalOrder['status'],
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal payment error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'PayPal payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the main createPayment method to handle PayPal
     */
    public function createPayment(Request $request, Order $order)
    {
        // ... existing validation code

        // Route to correct payment provider handler
        $provider = PaymentProvider::from($request->provider);

        if ($provider === PaymentProvider::STRIPE) {
            return $this->createStripePayment($order);
        } elseif ($provider === PaymentProvider::PAYPAL) {
            return $this->createPayPalPayment($order);
        } else {
            return response()->json([
                'message' => 'Payment provider not supported.'
            ], 501);
        }
    }

    /**
     * Handle PayPal payment success callback
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paypalSuccess(Request $request)
    {
        try {
            $paymentId = $request->query('payment_id');
            $paypalOrderId = $request->query('token');       // PayPal returns order ID as 'token'

            if (!$paymentId || !$paypalOrderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ], 400);
            }

            // Find our payment record
            $payment = Payment::findOrFail($paymentId);

            if ($payment->status === PaymentStatus::COMPLETED) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment already completed',
                    'payment' => $payment
                ]);
            }

            // Capture the PayPal payment
            $paypalService = new PayPalService();
            $captureResult = $paypalService->captureOrder($paypalOrderId);

            // Check if capture was successful
            if ($captureResult['status'] === 'COMPLETED') {
                // Extract capture ID from PayPal response
                $captureId = $captureResult['purchase_units'][0]['payments']['captures'][0]['id'];

                // Mark payment as completed
                $payment->markAsCompletedPayPal($captureId, [
                    'paypal_capture_result' => $captureResult,
                    'completed_at' => now()->toIso8601String(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment completed successfully',
                    'payment' => $payment->fresh(),
                    'order' => $payment->order
                ]);
            } else {
                $payment->markAsFailed([
                    'paypal_error' => 'Capture failed',
                    'paypal_response' => $captureResult
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment capture failed'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('PayPal success callback error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle PayPal payment cancellation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function paypalCancel(Request $request)
    {
        try {
            $paymentId = $request->query('payment_id');

            if (!$paymentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing payment ID'
                ], 400);
            }

            // Find our payment record and mark as failed
            $payment = Payment::findOrFail($paymentId);
            $payment->markAsFailed([
                'reason' => 'User cancelled payment',
                'cancelled_at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment was cancelled by user',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal cancel callback error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error processing cancellation'
            ], 500);
        }
    }


    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook.secret');

        try {
            // Verify the webhook signature
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );

            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handleSuccessfulPayment($event->data->object);

                case 'payment_intent.payment_failed':
                    return $this->handleFailedPayment($event->data->object);

                default:
                    Log::info('Unhandled Stripe webhook: ' . $event->type);
                    return response()->json(['status' => 'ignored']);
            }

        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Invalid webhook payload: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Invalid webhook signature: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }


    protected function handleSuccessfulPayment($paymentIntent)
    {
        // Find payment by payment intent ID
        $payment = Payment::where('payment_intent_id', $paymentIntent->id)->first();

        // If no payment found, try to find it in metadata
        if (!$payment && isset($paymentIntent->metadata->payment_id)) {
            $payment = Payment::find($paymentIntent->metadata->payment_id);
        }

        if (!$payment) {
            Log::error("Payment not found for intent: " . $paymentIntent->id);
            return response()->json(['status' => 'payment-not-found']);
        }

        // Only process if payment is not already completed
        if ($payment->status !== PaymentStatus::COMPLETED) {
            // Mark payment as completed
            $payment->markAsCompleted($paymentIntent->id, [
                'stripe_data' => [
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'payment_method' => $paymentIntent->payment_method,
                    'status' => $paymentIntent->status,
                    'completed_at' => now()->toIso8601String(),
                ],
            ]);

            Log::info("Payment {$payment->id} marked as completed via webhook");
        }

        return response()->json(['status' => 'success']);
    }
}
