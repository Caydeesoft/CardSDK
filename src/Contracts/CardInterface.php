<?php
	
	namespace Caydeesoft\CardSdk\Contracts;
	
	interface CardInterface
		{
            public function authorizePayment(array $paymentData): array;

            public function capturePayment(string $transactionId): array;

            public function refundPayment(string $transactionId): array;
		}
