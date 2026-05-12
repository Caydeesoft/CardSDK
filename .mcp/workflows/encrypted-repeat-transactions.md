# MCP Workflow: Encrypted Repeat Transactions

Use this workflow when changing stored payment data, repeat transaction behavior, or the user password/PIN unlock flow.

## Goal

Keep stored payment metadata encrypted at rest while allowing a repeat transaction only after the user provides the same password or PIN used when the payment method was stored.

## Safety Rules

- Do not store CVV, CVC, CVV2, CID, or security code values.
- Prefer storing provider tokens or card references instead of raw card numbers.
- Treat user passwords and PINs as transient input. Do not log them, persist them, or return them in responses.
- Keep `CARD_ENCRYPTION_PEPPER` in server-side environment configuration only.
- Preserve the per-record salt, IV, tag, and KDF metadata in the encrypted payload.
- Rate-limit repeat transaction unlock attempts when a PIN is allowed.

## Implementation Flow

1. Validate card data with `Caydeesoft\CardSdk\CardValidator`.
2. Send the initial authorization/tokenization request through the configured `CardInterface` provider.
3. Store provider-returned token data with `Caydeesoft\CardSdk\StoredCardEncryptor::encrypt`.
4. Save only the encrypted payload and non-sensitive display metadata such as brand and last four digits.
5. During repeat payment, ask for the password or PIN again.
6. Decrypt with `StoredCardEncryptor::decrypt`.
7. Submit the provider token or card reference through the configured `CardInterface`.
8. If decryption fails, return a generic invalid unlock response.

## Verification

Run these checks after any workflow change:

```bash
composer validate --strict --no-check-publish
find src config tests -name '*.php' -print0 | xargs -0 -n1 php -l
php -r 'require "vendor/autoload.php"; use Caydeesoft\CardSdk\StoredCardEncryptor; $e = new StoredCardEncryptor(100000, "server-pepper"); $p = $e->encrypt(["card_token" => "tok_123"], "123456"); if ($e->decrypt($p, "123456")["card_token"] !== "tok_123") { throw new RuntimeException("Decrypt check failed"); }'
```

## Expected Failure Cases

- Wrong password or PIN cannot decrypt the payload.
- Empty password or PIN is rejected.
- Payloads containing CVV/CVC-like keys are rejected before encryption.
- Payloads with unsupported cipher, KDF, version, or weak iteration count are rejected.
