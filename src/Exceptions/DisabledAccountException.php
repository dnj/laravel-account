<?php

namespace dnj\Account\Exceptions;

use dnj\Account\Contracts\IAccount;
use Exception;

class DisabledAccountException extends Exception
{
    public function __construct(
        public readonly IAccount $account,
    ) {
    }
}
