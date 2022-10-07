<?php

namespace dnj\Account\Contracts;

use dnj\Number\Contracts\INumber;

interface IHoldingManager
{
    public function acquire(int $accountId, INumber $amount, ?array $meta = null, bool $force = false): IHoldingRecord;

    public function release(int $recordId): IAccount;

    public function update(int $recordId, ?array $meta = null): IHoldingRecord;

    /**
     * @param iterable<int> $recordIds
     */
    public function releaseMultiple(iterable $recordIds): IAccount;

    public function releaseAll(int $accountId): IAccount;

    /**
     * @return iterable<IHoldingRecord>
     */
    public function findByAccount(int $accountId): iterable;

    public function getByID(int $recordId): IHoldingRecord;

    public function recalucateHoldingBalance(int $accountId): IAccount;
}
