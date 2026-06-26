<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('stripe.secret');
    }

    public function chargeWithCard(float $amount, array $cardDetails, string $description = 'Pago Licor Vintage'): array
    {
        try {
            // Map the card number to a Stripe standard test token to avoid PCI compliance / raw card data errors.
            $cardNumber = str_replace(' ', '', $cardDetails['number'] ?? '');
            
            // Default token is tok_visa (Visa)
            $sourceToken = 'tok_visa';

            if (empty($cardNumber)) {
                return [
                    'success' => false,
                    'message' => 'El número de tarjeta es obligatorio.',
                ];
            }

            // Let the developer test different scenarios based on the input card number
            if (str_contains($cardNumber, '34') || str_contains($cardNumber, '37')) {
                $sourceToken = 'tok_amex'; // American Express
            } elseif (str_contains($cardNumber, '51') || str_contains($cardNumber, '55')) {
                $sourceToken = 'tok_mastercard'; // Mastercard
            } elseif (str_contains($cardNumber, '9999') || str_contains($cardNumber, 'declined') || str_contains($cardNumber, '0002')) {
                $sourceToken = 'tok_chargeDeclined'; // Card declined
            } elseif (str_contains($cardNumber, '0003')) {
                $sourceToken = 'tok_chargeDeclinedInsufficientFunds'; // Insufficient funds
            } elseif (str_contains($cardNumber, '0004')) {
                $sourceToken = 'tok_chargeDeclinedExpiredCard'; // Expired card
            } elseif (str_contains($cardNumber, '0005')) {
                $sourceToken = 'tok_chargeDeclinedIncorrectCvc'; // Incorrect CVC
            }

            // Create the charge directly using the mapped test token
            $amountInCents = (int)round($amount * 100);

            $chargeResponse = Http::withoutVerifying()
                ->withToken($this->secretKey)
                ->asForm()
                ->post('https://api.stripe.com/v1/charges', [
                    'amount' => $amountInCents,
                    'currency' => 'usd', // Stripe test mode universal currency
                    'source' => $sourceToken,
                    'description' => $description,
                ]);

            if ($chargeResponse->successful()) {
                Log::info('Stripe charge successful', ['charge_id' => $chargeResponse->json()['id']]);
                return [
                    'success' => true,
                    'charge_id' => $chargeResponse->json()['id'],
                    'receipt_url' => $chargeResponse->json()['receipt_url'] ?? null,
                ];
            }

            $error = $chargeResponse->json()['error']['message'] ?? 'Error al procesar el cargo con Stripe.';
            Log::error('Stripe charge failed', ['response' => $chargeResponse->json()]);
            return [
                'success' => false,
                'message' => $error,
            ];

        } catch (\Exception $e) {
            Log::error('Stripe payment exception', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Ocurrió un error inesperado al comunicarse con el procesador de pagos.',
            ];
        }
    }
}
