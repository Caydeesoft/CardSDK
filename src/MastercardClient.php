<?php

namespace Caydeesoft\CardSdk;

use Caydeesoft\CardSdk\Contracts\CardInterface;
use GuzzleHttp\Client;

class MastercardClient implements CardInterface
    {
        private Client $client;

        private ?string $consumerKey;

        private ?string $privateKeyPath;

        private string $baseUrl;

        public function __construct()
            {
                $this->consumerKey = config('card.mastercard_consumer_key');
                $this->privateKeyPath = config('card.mastercard_private_key_path');
                $this->baseUrl = config('card.mastercard_base_url', 'https://sandbox.api.mastercard.com');

                $this->client = new Client([
                    'base_uri' => $this->baseUrl,
                    'timeout' => 10.0,
                ]);
            }

        public function authorizePayment(array $paymentData): array
            {
                return $this->request('POST', '/mastercard/payments/authorize', $paymentData);
            }

        public function capturePayment(string $transactionId): array
            {
                return $this->request('POST', "/mastercard/payments/capture/{$transactionId}");
            }

        public function refundPayment(string $transactionId): array
            {
                return $this->request('POST', "/mastercard/payments/refund/{$transactionId}");
            }

        private function request(string $method, string $endpoint, array $data = []): array
            {
                $response = $this->client->request($method, $endpoint, [
                    'json' => $data,
                    'headers' => [
                        'Authorization' => "OAuth {$this->consumerKey}",
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                ]);

                return json_decode((string) $response->getBody(), true) ?? [];
            }
    }