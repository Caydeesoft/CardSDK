<?php

namespace Caydeesoft\CardSdk\Test;

use Caydeesoft\CardSdk\Contracts\CardInterface;

function processPayment(CardInterface $paymentGateway)
    {
        $response = $paymentGateway->authorizePayment([
            'amount'      => 1000,
            'currency'    => 'USD',
            'card_number' => '4111111111111111',
            'expiry'      => '12/26',
            'cvv'         => '123'
        ]);

        return response()->json($response);
    }
