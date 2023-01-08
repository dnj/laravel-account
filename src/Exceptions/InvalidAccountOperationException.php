<?php

namespace dnj\Account\Exceptions;

use dnj\Account\Contracts\IAccount;

class InvalidAccountOperationException extends \Exception
{
    public function __construct(
        public readonly IAccount $account,
        public readonly string $operation,
    ) {
    }
}
