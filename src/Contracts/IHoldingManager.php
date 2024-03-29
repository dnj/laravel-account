<?php

namespace dnj\Account\Contracts;

use dnj\Number\Contracts\INumber;

interface IHoldingManager
{
    public function acquire(int $accountId, INumber $amount, ?array $meta = null, bool $force = false, bool $userActivityLog = false): IHoldingRecord;

    public function release(int $recordId): IAccount;

    /**
     * @param array{amount?:INumber,meta?:array|null} $changes
     */
    public function update(int $recordId, array $changes, bool $userActivityLog = false): IHoldingRecord;

    /**
     * @param iterable<int> $recordIds
     */
    public function releaseMultiple(iterable $recordIds, bool $userActivityLog = false): IAccount;

    public function releaseAll(int $accountId, bool $userActivityLog = false): IAccount;

    /**
     * @return iterable<IHoldingRecord>
     */
    public function findByAccount(int $accountId): iterable;

    public function getByID(int $recordId): IHoldingRecord;

    public function recalucateHoldingBalance(int $accountId): IAccount;
}
