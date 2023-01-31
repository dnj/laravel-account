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
use dnj\UserLogger\Contracts\ILogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class HoldingManager implements IHoldingManager
{
    use UpdatingAccount;

    public function __construct(protected ILogger $userLogger)
    {
    }

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

    public function acquire(int $accountId, INumber $amount, ?array $meta = null, bool $force = false, bool $userActivityLog = false): Holding
    {
        return DB::transaction(function () use ($accountId, $amount, $meta, $force, $userActivityLog) {
            $account = $this->getAccountForUpdate($accountId);
            $available = $account->getAvailableBalance();
            if (!$force and $available->lt($amount)) {
                throw new BalanceInsufficientException($account, $available, $amount);
            }

            $holding = new Holding();
            $holding->account_id = $accountId;
            $holding->amount = $amount;
            $holding->meta = $meta;
            $changes = $holding->changesForLog();
            $holding->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($holding)
                    ->withProperties($changes)
                    ->log('created');
            }

            $account->holding = $account->holding->add($amount);
            $changes = $account->changesForLog();
            $account->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($account)
                    ->withProperties($changes)
                    ->log('held-amount');
            }

            return $holding;
        });
    }

    public function release(int $recordId, bool $userActivityLog = false): Account
    {
        return DB::transaction(function () use ($recordId, $userActivityLog) {
            $holding = $this->getHoldingForUpdate($recordId);
            $account = $this->getAccountForUpdate($holding->account_id);

            $account->holding = $account->holding->sub($holding->amount);
            $changes = $account->changesForLog();
            $account->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($account)
                    ->withProperties($changes)
                    ->log('released-amount');
            }

            $holding->delete();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($holding)
                    ->withProperties($holding->toArray())
                    ->log('destroyed');
            }

            return $account;
        });
    }

    public function update(int $recordId, array $changes, bool $userActivityLog = false): Holding
    {
        return DB::transaction(function () use ($recordId, $changes, $userActivityLog) {
            $holding = $this->getHoldingForUpdate($recordId);
            $diffHoldingAmount = null;
            if (isset($changes['amount'])) {
                if ($changes['amount']->lte(0)) {
                    throw new \InvalidArgumentException('new amount of holding cannot be non-positive number');
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
            $changes = $holding->changesForLog();
            $holding->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($holding)
                    ->withProperties($changes)
                    ->log('updated');
            }

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
    public function releaseMultiple(iterable $recordIds, bool $userActivityLog = false): Account
    {
        return DB::transaction(function () use ($recordIds, $userActivityLog) {
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
            $changes = $account->changesForLog();
            $account->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($account)
                    ->withProperties($changes)
                    ->log('released-amount');
            }

            foreach ($holdings as $holding) {
                $holding->delete();

                if ($userActivityLog) {
                    $this->userLogger
                        ->withRequest(request())
                        ->performedOn($holding)
                        ->withProperties($holding->toArray())
                        ->log('destroyed');
                }
            }

            return $account;
        });
    }

    public function releaseAll(int $accountId, bool $userActivityLog = false): Account
    {
        return DB::transaction(function () use ($accountId, $userActivityLog) {
            $account = $this->getAccountForUpdate($accountId);
            $account->holding = Number::fromInt(0);
            $changes = $account->changesForLog();
            $account->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($account)
                    ->withProperties($changes)
                    ->log('released-amount');
            }

            $holdings = Holding::query()
                ->lockForUpdate()
                ->where('account_id', $accountId)
                ->get();

            foreach ($holdings as $holding) {
                $holding->delete();

                if ($userActivityLog) {
                    $this->userLogger
                        ->withRequest(request())
                        ->performedOn($holding)
                        ->withProperties($holding->toArray())
                        ->log('destroyed');
                }
            }

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
