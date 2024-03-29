<?php

namespace dnj\Account\Contracts;

interface IAccountManager
{
    public function getByID(int $id): IAccount;

    /**
     * @return iterable<IAccount>
     */
    public function findByUser(?int $userId): iterable;

    /**
     * @return iterable<IAccount>
     */
    public function findAll(): iterable;

    public function create(
        string $title,
        int $userId,
        int $currencyId,
        AccountStatus $status = AccountStatus::ACTIVE,
        bool $canSend = true,
        bool $canReceive = true,
        ?array $meta = null,
        bool $userActivityLog = false,
    ): IAccount;

    /**
     * @param array{title?:string, userId?:int|null, status?:AccountStatus, canSend?: bool, canReceive?:bool, meta?:array|null} $changes
     */
    public function update(
        int $accountId,
        array $changes,
        bool $userActivityLog = false,
    ): IAccount;

    public function recalucateBalance(int $accountId): IAccount;

    public function delete(int $accountId, bool $userActivityLog = false): void;
}
