<?php

namespace dnj\Account\Concerns;

use dnj\Account\Models\Account;

trait UpdatingAccount
{
    protected function getAccountForUpdate(int $accountId): Account
    {
        return Account::query()
            ->lockForUpdate()
            ->findOrFail($accountId);
    }
}
