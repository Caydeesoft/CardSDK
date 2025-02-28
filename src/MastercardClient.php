<?php
	
	namespace Caydeesoft\CardSdk;
	
	use GuzzleHttp\Client;
	
	class MastercardClient implements CardInterface
		{
			private $client;
			private $consumerKey;
			private $privateKeyPath;
			private $baseUrl;
			
			public function __construct()
				{
					$this->consumerKey    = config('card.mastercard_consumer_key');
					$this->privateKeyPath = config('card.mastercard_private_key_path');
					$this->baseUrl        = 'https://sandbox.api.mastercard.com';
					
					$this->client = new Client([
						                           'base_uri' => $this->baseUrl,
						                           'timeout'  => 10.0,
					                           ]);
				}
			
			public function authorizePayment(array $paymentData)
				{
					return $this->request('POST', '/mastercard/payments/authorize', $paymentData);
				}
			
			public function capturePayment(string $transactionId)
				{
					return $this->request('POST', "/mastercard/payments/capture/{$transactionId}");
				}
			
			public function refundPayment(string $transactionId)
				{
					return $this->request('POST', "/mastercard/payments/refund/{$transactionId}");
				}
			
			private function request($method, $endpoint, $data = [])
				{
					$response = $this->client->request($method, $endpoint, [
						'json'    => $data,
						'headers' => [
							'Authorization' => "OAuth {$this->consumerKey}",
							'Content-Type'  => 'application/json',
						]
					]);
					return json_decode($response->getBody(), true);
				}
		}
