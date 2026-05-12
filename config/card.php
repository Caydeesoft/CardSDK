<?php

return [
    'payment_provider' => env('CARD_PROVIDER', 'visa'),

    'visa_api_key' => env('VISA_API_KEY'),
    'visa_base_url' => env('VISA_BASE_URL', 'https://sandbox.api.visa.com'),

    'mastercard_consumer_key' => env('MASTERCARD_CONSUMER_KEY'),
    'mastercard_private_key_path' => env('MASTERCARD_PRIVATE_KEY_PATH'),
    'mastercard_base_url' => env('MASTERCARD_BASE_URL', 'https://sandbox.api.mastercard.com'),

    'amex_api_key' => env('AMEX_API_KEY'),
    'amex_base_url' => env('AMEX_BASE_URL', 'https://api.americanexpress.com'),

    'discover_api_key' => env('DISCOVER_API_KEY'),
    'discover_base_url' => env('DISCOVER_BASE_URL', 'https://api.discover.com'),
];