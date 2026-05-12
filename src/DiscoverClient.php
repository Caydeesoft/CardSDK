<?php

namespace Caydeesoft\CardSdk;

use Caydeesoft\CardSdk\Contracts\CardInterface;
use GuzzleHttp\Client;

class DiscoverClient implements CardInterface
    {
        private Client $client;

        private ?string $apiKey;

        private string $baseUrl;

        public function __construct()
            {
                $this->apiKey  = config('card.discover_api_key');
                $this->baseUrl = config('card.discover_base_url', 'https://api.discover.com');

                $this->client = new Client([
                    'base_uri' => $this->baseUrl,
                    'timeout'  => 10.0,
                ]);
            }

        public function authorizePayment(array $paymentData): array
            {
                return $this->request('POST', '/discover/payments/authorize', $paymentData);
            }

        public function capturePayment(string $transactionId): array
            {
                return $this->request('POST', "/discover/payments/capture/{$transactionId}");
            }

        public function refundPayment(string $transactionId): array
            {
                return $this->request('POST', "/discover/payments/refund/{$transactionId}");
            }

        private function request(string $method, string $endpoint, array $data = []): array
            {
                $response = $this->client->request($method, $endpoint, [
                    'json'    => $data,
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiKey}",
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ],
                ]);

                return json_decode((string)$response->getBody(), true) ?? [];
            }
    }