<?php

namespace Caydeesoft\CardSdk;

class CardValidator
    {
        private static array $patterns = [
            'Visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/',
            'MasterCard' => '/^(5[1-5][0-9]{14}|2[2-7][0-9]{14})$/',
            'Amex' => '/^3[47][0-9]{13}$/',
            'Discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'Diners Club' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            'JCB' => '/^(?:2131|1800|35\d{3})\d{11}$/',
        ];

        public static function getCardType(string $number): string
            {
                $number = preg_replace('/\D/', '', $number) ?? '';

                foreach (self::$patterns as $type => $pattern) {
                    if (preg_match($pattern, $number) === 1) {
                        return $type;
                    }
                }

                return 'Unknown';
            }

        public static function isValidCardNumber(string $number): bool
            {
                $number = preg_replace('/\D/', '', $number) ?? '';

                if ($number === '') {
                    return false;
                }

                return self::luhnCheck($number);
            }

        public static function isValidExpiry(string|int $month, string|int $year): bool
            {
                $month = (int) $month;
                $year = (int) $year;

                if ($month < 1 || $month > 12) {
                    return false;
                }

                if ($year < 100) {
                    $year += 2000;
                }

                $expiry = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "{$year}-{$month}-1 23:59:59");

                if ($expiry === false) {
                    return false;
                }

                $expiry = $expiry->modify('last day of this month');
                $now = new \DateTimeImmutable('now');

                return $expiry >= $now;
            }

        public static function isValidCVV(string|int $cvv, string $cardType): bool
            {
                $cvv = (string) $cvv;

                if ($cardType === 'Amex') {
                    return preg_match('/^\d{4}$/', $cvv) === 1;
                }

                return preg_match('/^\d{3}$/', $cvv) === 1;
            }

        private static function luhnCheck(string $number): bool
            {
                $sum = 0;
                $alt = false;

                for ($i = strlen($number) - 1; $i >= 0; $i--) {
                    $n = (int) $number[$i];

                    if ($alt) {
                        $n *= 2;

                        if ($n > 9) {
                            $n -= 9;
                        }
                    }

                    $sum += $n;
                    $alt = ! $alt;
                }

                return $sum % 10 === 0;
            }
    }