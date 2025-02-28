<?php
	return [
		'payment_provider'            => env('CARD_PROVIDER', 'visa'),
		'visa_api_key'                => env('VISA_API_KEY'),
		'mastercard_consumer_key'     => env('MASTERCARD_CONSUMER_KEY'),
		'mastercard_private_key_path' => env('MASTERCARD_PRIVATE_KEY_PATH'),
		'amex_api_key'                => env('AMEX_API_KEY'),
	];

