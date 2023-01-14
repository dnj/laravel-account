<?php

namespace dnj\Account\Exceptions;

use dnj\Account\Contracts\IAccount;

class DisabledAccountException extends \Exception
{
    public function __construct(
        public readonly IAccount $account,
    ) {
    }
}
