<?php

namespace dnj\Account\Exceptions;

use dnj\Currency\Contracts\ICurrency;

class CurrencyMismatchException extends \Exception
{
    public function __construct(
        public readonly ICurrency $sourceCurrency,
        public readonly ICurrency $destCurrency,
    ) {
    }
}
