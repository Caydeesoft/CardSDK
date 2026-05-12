# MCP Workflow: Package CI

Use this workflow when preparing changes for this package or debugging GitHub Actions failures.

## Local Checks

Run package-level checks before proposing or merging changes:

```bash
composer validate --strict --no-check-publish
composer install --prefer-dist --no-interaction --no-progress
find src config tests -name '*.php' -print0 | xargs -0 -n1 php -l
```

Run focused behavior checks for card validation and stored-data encryption:

```bash
php -r 'require "vendor/autoload.php"; use Caydeesoft\CardSdk\CardValidator; if (CardValidator::getCardType("4111111111111111") !== "Visa") { throw new RuntimeException("Visa detection failed"); } if (CardValidator::getCardType("2221000000000009") !== "MasterCard") { throw new RuntimeException("Mastercard detection failed"); } if (CardValidator::getCardType("2721000000000000") !== "Unknown") { throw new RuntimeException("Invalid range accepted"); }'
php -r 'require "vendor/autoload.php"; use Caydeesoft\CardSdk\StoredCardEncryptor; $e = new StoredCardEncryptor(100000, "server-pepper"); $p = $e->encrypt(["card_token" => "tok_123", "last_four" => "1111"], "123456"); if ($e->decrypt($p, "123456")["card_token"] !== "tok_123") { throw new RuntimeException("Decrypt check failed"); }'
```

## GitHub Actions

The repository includes:

- `.github/workflows/ci.yml` for Composer validation, dependency installation, PHP linting, validator assertions, and encryption assertions across PHP 8.2, 8.3, and 8.4.
- `.github/workflows/security.yml` for CodeQL analysis and dependency review.

## Review Checklist

- New code is covered by at least a focused assertion or lint check.
- Stored payment examples do not include CVV persistence.
- README examples match real class names and configuration keys.
- Workflows do not require live provider API credentials.
- CI commands lint only package files, not `vendor`.
