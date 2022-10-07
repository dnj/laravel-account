<?php

namespace dnj\Account\Contracts;

use dnj\Number\Contracts\INumber;

interface ITransactionManager
{
    public function getById(int $transactionId): ITransaction;

    /**
     * @return iterable<ITransaction>
     */
    public function findByAccount(int $accountId): iterable;

    public function transfer(
        int $fromAccountId,
        int $toAccountId,
        INumber $amount,
        ?array $meta = null,
        bool $force = false,
    ): ITransaction;

    public function rollback(int $transactionId, bool $force = false): ITransaction;

    public function update(int $transactionId, ?array $meta = null): ITransaction;
}
