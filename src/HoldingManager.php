<?php

namespace dnj\Account;

use dnj\Account\Concerns\UpdatingAccount;
use dnj\Account\Contracts\IHoldingManager;
use dnj\Account\Exceptions\BalanceInsufficientException;
use dnj\Account\Exceptions\MultipleAccountOperationException;
use dnj\Account\Models\Account;
use dnj\Account\Models\Holding;
use dnj\Number\Contracts\INumber;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HoldingManager implements IHoldingManager
{
    use UpdatingAccount;

    public function getByID(int $id): Holding
    {
        return Holding::query()->findOrFail($id);
    }

    /**
     * @return Collection<Holding>
     */
    public function findByAccount(int $accountId): Collection
    {
        return Holding::query()
            ->where('account_id', $accountId)
            ->get();
    }

    public function acquire(int $accountId, INumber $amount, ?array $meta = null, bool $force = false): Holding
    {
        return DB::transaction(function () use ($accountId, $amount, $meta, $force) {
            $account = $this->getAccountForUpdate($accountId);
            $available = $account->getAvailableBalance();
            if (!$force and $available->lt($amount)) {
                throw new BalanceInsufficientException($account, $available, $amount);
            }

            $holding = new Holding();
            $holding->account_id = $accountId;
            $holding->amount = $amount;
            $holding->meta = $meta;
            $holding->save();

            $account->holding = $account->holding->add($amount);
            $account->save();

            return $holding;
        });
    }

    public function release(int $recordId): Account
    {
        return DB::transaction(function () use ($recordId) {
            $holding = $this->getHoldingForUpdate($recordId);
            $account = $this->getAccountForUpdate($holding->account_id);

            $account->holding = $account->holding->sub($holding->amount);
            $account->save();

            $holding->delete();

            return $account;
        });
    }

    public function update(int $recordId, array $changes): Holding
    {
        return DB::transaction(function () use ($recordId, $changes) {
            $holding = $this->getHoldingForUpdate($recordId);
            $diffHoldingAmount = null;
            if (isset($changes['amount'])) {
                if ($changes['amount']->lte(0)) {
                    throw new InvalidArgumentException('new amount of holding cannot be non-positive number');
                }
                $diffHoldingAmount = $changes['amount']->sub($holding->amount);
                if (!$diffHoldingAmount->isEqual(0)) {
                    $holding->amount = $changes['amount'];
                } else {
                    $diffHoldingAmount = null;
                }
            }
            if (array_key_exists('meta', $changes)) {
                $holding->meta = $changes['meta'];
            }
            $holding->save();

            if ($diffHoldingAmount) {
                $account = $this->getAccountForUpdate($holding->account_id);
                $account->holding = $account->holding->add($diffHoldingAmount);
                $account->save();
            }

            return $holding;
        });
    }

    /**
     * @param iterable<int> $recordIds
     */
    public function releaseMultiple(iterable $recordIds): Account
    {
        return DB::transaction(function () use ($recordIds) {
            $holdings = Holding::query()
                ->lockForUpdate()
                ->whereIn('id', $recordIds)
                ->get();

            $accountId = null;
            foreach ($holdings as $holding) {
                if (null === $accountId) {
                    $accountId = $holding->account_id;
                }
                if ($accountId !== $holding->account_id) {
                    throw new MultipleAccountOperationException("You cannot release holding from diffrent account's in one invoke");
                }
            }

            $holdingIds = $holdings->pluck('id');
            $missingIds = [];
            foreach ($recordIds as $recordId) {
                if (!$holdingIds->contains($recordId)) {
                    $missingIds[] = $recordId;
                }
            }
            if ($missingIds) {
                $e = new ModelNotFoundException();
                $e->setModel(Holding::class, $missingIds);
                throw new $e();
            }

            $totalHoldings = Number::fromInt(0);
            foreach ($holdings as $holding) {
                $totalHoldings = $totalHoldings->add($holding->amount);
            }

            $account = $this->getAccountForUpdate($accountId);
            $account->holding = $account->holding->sub($totalHoldings);
            $account->save();

            foreach ($holdings as $holding) {
                $holding->delete();
            }

            return $account;
        });
    }

    public function releaseAll(int $accountId): Account
    {
        return DB::transaction(function () use ($accountId) {
            $account = $this->getAccountForUpdate($accountId);
            $account->holding = Number::fromInt(0);
            $account->save();

            Holding::query()
                ->where('account_id', $accountId)
                ->delete();

            return $account;
        });
    }

    public function recalucateHoldingBalance(int $accountId): Account
    {
        return DB::transaction(function () use ($accountId) {
            $account = $this->getAccountForUpdate($accountId);
            $holding = DB::table('accounts_holdings')
                ->select(DB::raw('sum(amount) as holding'))
                ->where('account_id', $accountId)
                ->value('holding');
            $holding = Number::fromInput($holding);
            $account->holding = $holding;
            $account->save();

            return $account;
        });
    }

    protected function getHoldingForUpdate(int $recordId): Holding
    {
        return Holding::query()
            ->lockForUpdate()
            ->findOrFail($recordId);
    }
}
