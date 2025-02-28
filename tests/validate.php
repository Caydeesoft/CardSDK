<?php
	use Caydeesoft\CardSdk\CardValidator;
	use Illuminate\Http\Request;
	
	
	/**
	 * @param Request $request
	 * @return mixed
	 */
	function validateCard(Request $request)
		{
			$cardNumber = $request->input('card_number');
			$expiry     = explode('/', $request->input('expiry_date'));
			$cvv        = $request->input('cvv');
			$cardType   = CardValidator::getCardType($cardNumber);
			if ($cardType === 'Unknown')
				{
					return response()->json(['error' => 'Invalid card type'], 422);
				}
			
			if (!CardValidator::isValidCardNumber($cardNumber))
				{
					return response()->json(['error' => 'Invalid card number'], 422);
				}
			
			if (!CardValidator::isValidExpiry($expiry[0], $expiry[1]))
				{
					return response()->json(['error' => 'Expired card'], 422);
				}
			
			if (!CardValidator::isValidCVV($cvv, $cardType))
				{
					return response()->json(['error' => 'Invalid CVV'], 422);
				}
			
			return response()->json([
				                        'message'   => 'Card is valid',
				                        'card_type' => $cardType
			                        ]);
		}
	
