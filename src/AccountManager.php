<?php

namespace dnj\Account;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Models\Account;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Collection;

class AccountManager implements IAccountManager
{
    public function getByID(int $id): Account
    {
        return Account::query()->findOrFail($id);
    }

    /**
     * @return Collection<Account>
     */
    public function findByUser(?int $userId): Collection
    {
        $q = Account::query();
        if (null === $userId) {
            $q->whereNull('user_id');
        } else {
            $q->where('user_id', $userId);
        }

        return $q->get();
    }

    /**
     * @return Collection<Account>
     */
    public function findAll(): Collection
    {
        return Account::query()->get();
    }

    public function create(
        string $title,
        int $currencyId,
        ?int $userId = null,
        AccountStatus $status = AccountStatus::ACTIVE,
        bool $canSend = true,
        bool $canReceive = true,
        ?array $meta = null,
    ): Account {
        $account = new Account();
        $account->title = $title;
        $account->user_id = $userId;
        $account->currency_id = $currencyId;
        $account->status = $status;
        $account->balance = Number::fromInt(0);
        $account->can_send = $canSend;
        $account->can_receive = $canReceive;
        $account->meta = $meta;
        $account->save();

        return $account;
    }

    public function update(
        int $accountId,
        array $changes,
    ): Account {
        $account = $this->getByID($accountId);
        if (isset($changes['title'])) {
            $account->title = $changes['title'];
        }
        if (array_key_exists('userId', $changes)) {
            $account->user_id = $changes['userId'];
        }
        if (isset($changes['status'])) {
            $account->status = $changes['status'];
        }
        if (isset($changes['canSend'])) {
            $account->can_send = $changes['canSend'];
        }
        if (isset($changes['canReceive'])) {
            $account->can_receive = $changes['canReceive'];
        }
        if (array_key_exists('meta', $changes)) {
            $account->meta = $changes['meta'];
        }
        $account->save();

        return $account;
    }

    public function delete(int $accountId): void
    {
        $account = $this->getByID($accountId);
        $account->delete();
    }
}
