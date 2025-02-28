<?php
	
	namespace Caydeesoft\CardSdk;
	
	use GuzzleHttp\Client;
	
	class VisaClient implements CardInterface
		{
			private $client;
			private $apiKey;
			private $baseUrl;
			
			public function __construct()
				{
					$this->apiKey  = config('card.visa_api_key');
					$this->baseUrl = 'https://sandbox.api.visa.com';
					
					$this->client = new Client([
						                           'base_uri' => $this->baseUrl,
						                           'timeout'  => 10.0,
					                           ]);
				}
			
			public function authorizePayment(array $paymentData)
				{
					return $this->request('POST', '/visa/payments/authorize', $paymentData);
				}
			
			public function capturePayment(string $transactionId)
				{
					return $this->request('POST', "/visa/payments/capture/{$transactionId}");
				}
			
			public function refundPayment(string $transactionId)
				{
					return $this->request('POST', "/visa/payments/refund/{$transactionId}");
				}
			
			private function request($method, $endpoint, $data = [])
				{
					$response = $this->client->request($method, $endpoint, [
						'json'    => $data,
						'headers' => [
							'Authorization' => "Bearer {$this->apiKey}",
							'Content-Type'  => 'application/json',
						]
					]);
					return json_decode($response->getBody(), true);
				}
		}
