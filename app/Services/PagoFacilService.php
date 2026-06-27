<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
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
                Log::warning('PagoFacil generateQR: no access token, using local fallback');

                return $this->generateLocalQR($params);
            }

            $paymentNumber = now()->format('YmdHis').random_int(100, 999);

            $orderDetail = isset($params['orderDetail']) ? array_map(function ($item, $i) {
                return [
                    'serial' => $item['serial'] ?? ($i + 1),
                    'product' => $item['product'] ?? 'Producto',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                    'discount' => $item['discount'] ?? 0,
                    'total' => $item['total'] ?? 0,
                ];
            }, $params['orderDetail'], array_keys($params['orderDetail'])) : [
                [
                    'serial' => 1,
                    'product' => 'Detalle_Item',
                    'quantity' => 1,
                    'price' => 0.10,
                    'discount' => 0,
                    'total' => 0.10,
                ],
            ];

            $payload = [
                'paymentMethod' => $params['paymentMethod'] ?? 34,
                'clientName' => $params['clientName'] ?? 'Jhon Daniel',
                'documentType' => $params['documentType'] ?? 1,
                'documentId' => $params['documentId'] ?? '11317191',
                'phoneNumber' => $params['phoneNumber'] ?? '75540850',
                'email' => $params['email'] ?? 'mario.herbas@pagofacil.com.bo',
                'paymentNumber' => $paymentNumber,
                'amount' => $params['amount'] ?? 0.01,
                'currency' => $params['currency'] ?? 2,
                'clientCode' => $params['clientCode'] ?? '11001',
                'callbackUrl' => $params['callbackUrl'] ?? 'https://uncle-prideful-uncloak.ngrok-free.dev/api/callbacks/pagofacil',
                'orderDetail' => $orderDetail,
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

            Log::warning('PagoFacil generateQR failed via API, using local fallback', [
                'status' => $response->status(),
                'paymentNumber' => $payload['paymentNumber'],
            ]);

            return $this->generateLocalQR($params);
        } catch (\Exception $e) {
            Log::error('PagoFacil generateQR exception, using local fallback', ['message' => $e->getMessage()]);

            return $this->generateLocalQR($params);
        }
    }

    public function generateLocalQR(array $params): array
    {
        $amount = $params['amount'] ?? 0;
        $paymentNumber = now()->format('YmdHis').random_int(100, 999);

        $data = json_encode([
            'pago' => 'licorvintage',
            'monto' => $amount,
            'nro' => $paymentNumber,
        ]);

        $renderer = new ImageRenderer(new RendererStyle(300), new SvgImageBackEnd);
        $writer = new Writer($renderer);
        $svg = $writer->writeString($data);

        return [
            'qrBase64' => base64_encode($svg),
            'qrFormat' => 'svg',
            'transactionId' => 'local_'.$paymentNumber,
            'paymentNumber' => $paymentNumber,
        ];
    }

    public function logout(): void
    {
        Cache::forget('pagofacil_access_token');
    }
}
