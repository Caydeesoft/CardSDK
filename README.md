# Caydeesoft Card SDK

A Laravel package for working with card payment providers and validating card details. The package ships with clients for Visa, Mastercard, American Express, and Discover, plus a lightweight card validator for card type detection, Luhn checks, expiry dates, and CVV length validation.

## Requirements

- PHP 8.2 or higher
- Laravel 9, 10, 11, 12, or 13
- Guzzle 7

## Installation

Install the package with Composer:

```bash
composer require caydeesoft/card-sdk
```

Laravel discovers the service provider automatically. If package discovery is disabled, add the provider manually:

```php
'providers' => [
    Caydeesoft\CardSdk\CardServiceProvider::class,
],
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=card-config
```

This creates `config/card.php`.

## Configuration

Set the active payment provider and credentials in your `.env` file:

```dotenv
CARD_PROVIDER=visa

VISA_API_KEY=
VISA_BASE_URL=https://sandbox.api.visa.com

MASTERCARD_CONSUMER_KEY=
MASTERCARD_PRIVATE_KEY_PATH=
MASTERCARD_BASE_URL=https://sandbox.api.mastercard.com

AMEX_API_KEY=
AMEX_BASE_URL=https://api.americanexpress.com

DISCOVER_API_KEY=
DISCOVER_BASE_URL=https://api.discover.com

CARD_ENCRYPTION_ITERATIONS=210000
CARD_ENCRYPTION_PEPPER=
```

Supported `CARD_PROVIDER` values are:

- `visa`
- `mastercard`
- `amex`
- `discover`

## Payment Usage

Resolve the configured payment client from Laravel's container by type-hinting `CardInterface`:

```php
use Caydeesoft\CardSdk\Contracts\CardInterface;

class PaymentController
{
    public function store(CardInterface $cards)
    {
        $response = $cards->authorizePayment([
            'amount' => 1000,
            'currency' => 'USD',
            'card_number' => '4111111111111111',
            'expiry' => '12/26',
            'cvv' => '123',
        ]);

        return response()->json($response);
    }
}
```

The concrete client is selected from `config('card.payment_provider')`.

### Available Payment Methods

All provider clients implement `Caydeesoft\CardSdk\Contracts\CardInterface`:

```php
authorizePayment(array $paymentData): array;
capturePayment(string $transactionId): array;
refundPayment(string $transactionId): array;
```

Example capture:

```php
$response = $cards->capturePayment($transactionId);
```

Example refund:

```php
$response = $cards->refundPayment($transactionId);
```

## Card Validation

Use `CardValidator` when you need local card checks before sending data to a payment provider:

```php
use Caydeesoft\CardSdk\CardValidator;

$cardType = CardValidator::getCardType('4111111111111111');

if (! CardValidator::isValidCardNumber('4111111111111111')) {
    return response()->json(['error' => 'Invalid card number'], 422);
}

if (! CardValidator::isValidExpiry('12', '2026')) {
    return response()->json(['error' => 'Expired card'], 422);
}

if (! CardValidator::isValidCVV('123', $cardType)) {
    return response()->json(['error' => 'Invalid CVV'], 422);
}
```

### Validator Methods

- `CardValidator::getCardType(string $number): string`
- `CardValidator::isValidCardNumber(string $number): bool`
- `CardValidator::isValidExpiry(string|int $month, string|int $year): bool`
- `CardValidator::isValidCVV(string|int $cvv, string $cardType): bool`

Detected card types include `Visa`, `MasterCard`, `Amex`, `Discover`, `Diners Club`, `JCB`, and `Unknown`.

## Encrypted Stored Data

Use `StoredCardEncryptor` to encrypt stored payment metadata with a password or PIN supplied by the user. The encryptor derives a key with PBKDF2-SHA256, a random per-record salt, and the optional `CARD_ENCRYPTION_PEPPER`, then encrypts the data with AES-256-GCM.

Store only the encrypted payload. Do not store CVV/CVC values, even encrypted.

```php
use Caydeesoft\CardSdk\StoredCardEncryptor;

class StoredPaymentMethodController
{
    public function store(StoredCardEncryptor $encryptor)
    {
        $encrypted = $encryptor->encrypt([
            'provider' => 'visa',
            'card_token' => 'tok_provider_generated_value',
            'last_four' => '1111',
            'card_type' => 'Visa',
            'expiry_month' => '12',
            'expiry_year' => '2028',
        ], request('password_or_pin'));

        auth()->user()->paymentMethods()->create([
            'encrypted_payload' => $encrypted,
        ]);
    }
}
```

### Repeat Transactions

For a repeat transaction, ask the user for the same password or PIN, decrypt the stored payload, and use the decrypted provider token or stored card reference in the payment request:

```php
use Caydeesoft\CardSdk\Contracts\CardInterface;
use Caydeesoft\CardSdk\StoredCardEncryptor;

class RepeatPaymentController
{
    public function store(CardInterface $cards, StoredCardEncryptor $encryptor)
    {
        $paymentMethod = auth()->user()->paymentMethods()->findOrFail(request('payment_method_id'));

        $stored = $encryptor->decrypt(
            $paymentMethod->encrypted_payload,
            request('password_or_pin')
        );

        $response = $cards->authorizePayment([
            'amount' => 1000,
            'currency' => 'USD',
            'card_token' => $stored['card_token'],
        ]);

        return response()->json($response);
    }
}
```

Password and PIN guidance:

- A password is safer than a short PIN because encrypted payloads can be attacked offline if the database leaks.
- If you allow a PIN, set a strong random `CARD_ENCRYPTION_PEPPER` in the server environment and rate-limit unlock attempts.
- If the user forgets the password or PIN, the package cannot decrypt the stored payload. Create a new stored payment method instead.
- Prefer storing provider tokens or card references. Storing raw card numbers can bring your application into PCI DSS scope.

## Notes

- Card number validation uses the Luhn algorithm.
- Amex CVVs must be four digits. Other supported card types use three digits.
- The included provider clients send JSON requests with authorization headers based on the configured credentials.
- Store live API credentials only in environment variables or a secrets manager.

## Troubleshooting

### Service Provider Is Not Discovered

Refresh Composer's autoloader and Laravel's package discovery cache:

```bash
composer dump-autoload
php artisan package:discover
```

If auto-discovery is disabled, register `Caydeesoft\CardSdk\CardServiceProvider::class` manually in your Laravel configuration.

## License

This package is open-sourced software licensed under the MIT license.
