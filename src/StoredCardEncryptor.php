<?php

namespace Caydeesoft\CardSdk;

use InvalidArgumentException;
use RuntimeException;

class StoredCardEncryptor
    {
        private const VERSION = 1;

        private const CIPHER = 'aes-256-gcm';

        private const KDF = 'pbkdf2-sha256';

        private const KEY_BYTES = 32;

        private const IV_BYTES = 12;

        private const SALT_BYTES = 16;

    /**
     * CVV/CVC values must not be stored, even when encrypted.
     */
        private const FORBIDDEN_KEYS
            = [
                'cvv',
                'cvc',
                'cvv2',
                'cid',
                'security_code',
            ];

        public function __construct(
            private readonly int    $iterations = 210000,
            private readonly string $pepper = '',
        )
            {
                if ($this->iterations < 100000)
                    {
                        throw new InvalidArgumentException('Password key derivation iterations must be at least 100000.');
                    }

                if (!in_array(self::CIPHER, openssl_get_cipher_methods(), true))
                    {
                        throw new RuntimeException('The aes-256-gcm cipher is not available in this PHP installation.');
                    }
            }

        public function encrypt(array $storedData, string $passwordOrPin): string
            {
                $this->guardSecret($passwordOrPin);
                $this->guardStoredData($storedData);

                $salt      = random_bytes(self::SALT_BYTES);
                $iv        = random_bytes(self::IV_BYTES);
                $key       = $this->deriveKey($passwordOrPin, $salt);
                $plainText = json_encode($storedData, JSON_THROW_ON_ERROR);
                $tag       = '';

                $cipherText = openssl_encrypt(
                    $plainText,
                    self::CIPHER,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag
                );

                if ($cipherText === false || $tag === '')
                    {
                        throw new RuntimeException('Unable to encrypt stored card data.');
                    }

                return json_encode([
                    'version'    => self::VERSION,
                    'cipher'     => self::CIPHER,
                    'kdf'        => self::KDF,
                    'iterations' => $this->iterations,
                    'salt'       => $this->encode($salt),
                    'iv'         => $this->encode($iv),
                    'tag'        => $this->encode($tag),
                    'value'      => $this->encode($cipherText),
                ], JSON_THROW_ON_ERROR);
            }

        public function decrypt(string $payload, string $passwordOrPin): array
            {
                $this->guardSecret($passwordOrPin);

                $encoded = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

                if (!is_array($encoded))
                    {
                        throw new InvalidArgumentException('Encrypted payload must be a JSON object.');
                    }

                $this->guardPayload($encoded);

                $salt       = $this->decode($encoded['salt']);
                $iv         = $this->decode($encoded['iv']);
                $tag        = $this->decode($encoded['tag']);
                $cipherText = $this->decode($encoded['value']);
                $key        = $this->deriveKey($passwordOrPin, $salt, (int)$encoded['iterations']);

                $plainText = openssl_decrypt(
                    $cipherText,
                    self::CIPHER,
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag
                );

                if ($plainText === false)
                    {
                        throw new InvalidArgumentException('Unable to decrypt stored card data with the provided password or PIN.');
                    }

                $storedData = json_decode($plainText, true, flags: JSON_THROW_ON_ERROR);

                if (!is_array($storedData))
                    {
                        throw new InvalidArgumentException('Decrypted stored card data must be a JSON object.');
                    }

                return $storedData;
            }

        private function deriveKey(string $passwordOrPin, string $salt, ?int $iterations = null): string
            {
                return hash_pbkdf2(
                    'sha256',
                    $passwordOrPin . $this->pepper,
                    $salt,
                    $iterations ?? $this->iterations,
                    self::KEY_BYTES,
                    true
                );
            }

        private function guardSecret(string $passwordOrPin): void
            {
                if ($passwordOrPin === '')
                    {
                        throw new InvalidArgumentException('A password or PIN is required to encrypt stored card data.');
                    }
            }

        private function guardStoredData(array $storedData): void
            {
                if ($storedData === [])
                    {
                        throw new InvalidArgumentException('Stored card data cannot be empty.');
                    }

                $this->rejectForbiddenKeys($storedData);
            }

        private function rejectForbiddenKeys(array $data): void
            {
                foreach ($data as $key => $value)
                    {
                        if (is_string($key) && in_array(strtolower($key), self::FORBIDDEN_KEYS, true))
                            {
                                throw new InvalidArgumentException('CVV/CVC values must not be stored, even when encrypted.');
                            }

                        if (is_array($value))
                            {
                                $this->rejectForbiddenKeys($value);
                            }
                    }
            }

        private function guardPayload(array $payload): void
            {
                foreach (['version', 'cipher', 'kdf', 'iterations', 'salt', 'iv', 'tag', 'value'] as $key)
                    {
                        if (!array_key_exists($key, $payload))
                            {
                                throw new InvalidArgumentException("Encrypted payload is missing [{$key}].");
                            }
                    }

                if ((int)$payload['version'] !== self::VERSION)
                    {
                        throw new InvalidArgumentException('Unsupported encrypted payload version.');
                    }

                if ($payload['cipher'] !== self::CIPHER || $payload['kdf'] !== self::KDF)
                    {
                        throw new InvalidArgumentException('Unsupported encrypted payload format.');
                    }

                if ((int)$payload['iterations'] < 100000)
                    {
                        throw new InvalidArgumentException('Encrypted payload uses an unsafe key derivation iteration count.');
                    }
            }

        private function encode(string $value): string
            {
                return base64_encode($value);
            }

        private function decode(string $value): string
            {
                $decoded = base64_decode($value, true);

                if ($decoded === false)
                    {
                        throw new InvalidArgumentException('Encrypted payload contains invalid base64 data.');
                    }

                return $decoded;
            }
    }
