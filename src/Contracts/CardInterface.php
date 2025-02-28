<?php
	
	namespace Caydeesoft\CardSdk;
	
	interface CardInterface
		{
			public function authorizePayment(array $paymentData);
			
			public function capturePayment(string $transactionId);
			
			public function refundPayment(string $transactionId);
		}
