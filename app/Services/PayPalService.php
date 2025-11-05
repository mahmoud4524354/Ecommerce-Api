<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PayPalService
{
    /**
     * PayPal API base URL (sandbox or live)
     */
    private string $baseUrl;

    /**
     * PayPal client ID
     */
    private string $clientId;

    /**
     * PayPal client secret
     */
    private string $clientSecret;

    /**
     * Access token for API calls
     */
    private ?string $accessToken = null;

    public function __construct()
    {
        // Initialize PayPal configuration from environment
        $this->baseUrl = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
    }

    /**
     * Get access token from PayPal OAuth API
     *
     * @return string
     * @throws Exception
     */
    private function getAccessToken(): string
    {
        // Return cached token if available
        if ($this->accessToken) {
            return $this->accessToken;
        }

        try {
            // Make OAuth request to PayPal
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post($this->baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                throw new Exception('Failed to get PayPal access token: ' . $response->body());
            }

            $data = $response->json();
            $this->accessToken = $data['access_token'];

            return $this->accessToken;

        } catch (Exception $e) {
            Log::error('PayPal OAuth error: ' . $e->getMessage());
            throw new Exception('PayPal authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a PayPal order
     *
     * @param float $amount Order total amount
     * @param string $currency Currency code (default: USD)
     * @param array $metadata Additional order metadata
     * @return array PayPal order response
     * @throws Exception
     */
    public function createOrder(float $amount, string $currency = 'USD', array $metadata = []): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // Prepare order data for PayPal API
            $orderData = [
                'intent' => 'CAPTURE',                    // Capture payment immediately
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),  // Format to 2 decimal places
                        ],
                        'description' => $metadata['description'] ?? 'Order Payment',
                        'reference_id' => $metadata['order_number'] ?? uniqid('order_'),
                    ]
                ],
                'application_context' => [
                    'return_url' => $metadata['return_url'] ?? config('app.url') . '/payment/success',
                    'cancel_url' => $metadata['cancel_url'] ?? config('app.url') . '/payment/cancel',
                    'brand_name' => config('app.name'),
                    'landing_page' => 'NO_PREFERENCE',     // Let PayPal decide the best landing page
                    'user_action' => 'PAY_NOW',            // Show "Pay Now" instead of "Continue"
                ]
            ];

            // Make API call to create PayPal order
            $response = Http::withToken($accessToken)
                ->contentType('application/json')
                ->post($this->baseUrl . '/v2/checkout/orders', $orderData);

            if (!$response->successful()) {
                Log::error('PayPal create order error: ' . $response->body());
                throw new Exception('Failed to create PayPal order: ' . $response->body());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('PayPal create order exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Capture a PayPal order (complete the payment)
     *
     * @param string $orderId PayPal order ID
     * @return array PayPal capture response
     * @throws Exception
     */
    public function captureOrder(string $orderId): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // Make API call to capture the PayPal order
            $response = Http::withToken($accessToken)
                ->contentType('application/json')
                ->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/capture");

            if (!$response->successful()) {
                Log::error('PayPal capture order error: ' . $response->body());
                throw new Exception('Failed to capture PayPal order: ' . $response->body());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('PayPal capture order exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get details of a PayPal order
     *
     * @param string $orderId PayPal order ID
     * @return array PayPal order details
     * @throws Exception
     */
    public function getOrderDetails(string $orderId): array
    {
        try {
            $accessToken = $this->getAccessToken();

            // Make API call to get PayPal order details
            $response = Http::withToken($accessToken)
                ->get($this->baseUrl . "/v2/checkout/orders/{$orderId}");

            if (!$response->successful()) {
                Log::error('PayPal get order error: ' . $response->body());
                throw new Exception('Failed to get PayPal order details: ' . $response->body());
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('PayPal get order exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
