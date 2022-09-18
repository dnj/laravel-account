<?php

namespace dnj\Account\Exceptions;

use dnj\Account\Contracts\IAccount;
use dnj\Number\Contracts\INumber;
use Exception;

class BalanceInsufficientException extends Exception
{
    public function __construct(
        public readonly IAccount $account,
        public readonly INumber $currentBalance,
        public readonly INumber $wantedAmount,
    ) {
    }
}
