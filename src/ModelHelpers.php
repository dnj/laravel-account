<?php

namespace dnj\Account;

use dnj\Currency\Models\Currency;

trait ModelHelpers
{
    protected function getUserModel(): ?string
    {
        return config('account.user_model');
    }

    protected function getUserTable(): ?string
    {
        $userModel = $this->getUserModel();

        $userTable = null;
        if ($userModel) {
            $userTable = (new $userModel())->getTable();
        }

        return $userTable;
    }

    protected function getCurrencyTable(): string
    {
        return (new Currency())->getTable();
    }

    protected function getFloatScale(): int
    {
        return config('currency.float_scale', 10);
    }
}
