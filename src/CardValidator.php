<?php
	
	namespace Caydeesoft\CardSdk;
	
	class CardValidator
		{
			private static $patterns
				= [
					'Visa'        => '/^4[0-9]{12}(?:[0-9]{3})?$/',
					'MasterCard'  => '/^(5[1-5][0-9]{14}|2[2-7][0-9]{14})$/',
					'Amex'        => '/^3[47][0-9]{13}$/',
					'Discover'    => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
					'Diners Club' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
					'JCB'         => '/^(?:2131|1800|35\d{3})\d{11}$/'
				];
			
			public static function getCardType($number)
				{
					foreach (self::$patterns as $type => $pattern)
						{
							if (preg_match($pattern, $number))
								{
									return $type;
								}
						}
					return 'Unknown';
				}
			
			public static function isValidCardNumber($number)
				{
					$number = preg_replace('/\D/', '', $number);
					return self::luhnCheck($number);
				}
			
			public static function isValidExpiry($month, $year)
				{
					$currentYear  = date('y');
					$currentMonth = date('m');
					return ($year > $currentYear || ($year == $currentYear && $month >= $currentMonth));
				}
			
			public static function isValidCVV($cvv, $cardType)
				{
					if ($cardType === 'Amex')
						{
							return preg_match('/^\d{4}$/', $cvv);
						}
					return preg_match('/^\d{3}$/', $cvv);
				}
			
			private static function luhnCheck($number)
				{
					$sum = 0;
					$alt = false;
					for ($i = strlen($number) - 1; $i >= 0; $i--)
						{
							$n = (int)$number[$i];
							if ($alt)
								{
									$n *= 2;
									if ($n > 9)
										{
											$n -= 9;
										}
								}
							$sum += $n;
							$alt = !$alt;
						}
					return ($sum % 10 === 0);
				}
		}