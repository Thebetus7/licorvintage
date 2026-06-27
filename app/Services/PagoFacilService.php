<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagoFacilService
{
    protected string $baseUrl;

    protected string $tokenService;

    protected string $tokenSecret;

    public function __construct()
    {
        $this->baseUrl = config('pagofacil.base_url');
        $this->tokenService = config('pagofacil.token_service');
        $this->tokenSecret = config('pagofacil.token_secret');
    }

    public function login(): ?string
    {
        try {
            $response = Http::withHeaders([
                'tcTokenService' => $this->tokenService,
                'tcTokenSecret' => $this->tokenSecret,
            ])->post("{$this->baseUrl}/login");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['values']['accessToken'])) {
                    $accessToken = $data['values']['accessToken'];
                    $expiresInMinutes = $data['values']['expiresInMinutes'] ?? 800;

                    Cache::put('pagofacil_access_token', $accessToken, now()->addMinutes((int) $expiresInMinutes));

                    Log::info('PagoFacil login successful');

                    return $accessToken;
                }
            }

            Log::error('PagoFacil login failed', ['response' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::error('PagoFacil login exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    public function getAccessToken(): ?string
    {
        if (Cache::has('pagofacil_access_token')) {
            return Cache::get('pagofacil_access_token');
        }

        return $this->login();
    }

    public function generateQR(array $params): ?array
    {
        try {
            $token = $this->getAccessToken();
            if (! $token) {
                Log::error('PagoFacil generateQR failed: no access token');

                return null;
            }

            $payload = [
                'paymentMethod' => $params['paymentMethod'] ?? 34,
                'clientName' => $params['clientName'] ?? 'Jhon Daniel',
                'documentType' => $params['documentType'] ?? 1,
                'documentId' => $params['documentId'] ?? '11317191',
                'phoneNumber' => $params['phoneNumber'] ?? '75540850',
                'email' => $params['email'] ?? 'mario.herbas@pagofacil.com.bo',
                'paymentNumber' => '2026050210643',
                'amount' => 0.01,
                'currency' => $params['currency'] ?? 2,
                'clientCode' => $params['clientCode'] ?? '11001',
                'callbackUrl' => 'https://uncle-prideful-uncloak.ngrok-free.dev/api/callbacks/pagofacil',

                'orderDetail' => [
                    [
                        'serial' => 1,
                        'product' => 'Detalle_Item',
                        'quantity' => 1,
                        'price' => 0.10,
                        'discount' => 0,
                        'total' => 0.10,
                    ],
                ],
            ];

            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/generate-qr", $payload);

            Log::debug('PagoFacil generateQR raw response', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 1000),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['values']['qrBase64'])) {
                    Log::info('PagoFacil QR generated successfully', [
                        'transactionId' => $data['values']['transactionId'] ?? null,
                        'paymentNumber' => $payload['paymentNumber'],
                    ]);

                    return $data['values'];
                }
            }

            Log::error('PagoFacil generateQR failed', [
                'status' => $response->status(),
                'response' => substr($response->body(), 0, 1000),
                'payload' => $payload,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('PagoFacil generateQR exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    public function logout(): void
    {
        Cache::forget('pagofacil_access_token');
    }
}
