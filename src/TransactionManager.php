<?php

namespace dnj\Account;

use dnj\Account\Concerns\UpdatingAccount;
use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Exceptions\BalanceInsufficientException;
use dnj\Account\Exceptions\CurrencyMismatchException;
use dnj\Account\Exceptions\DisabledAccountException;
use dnj\Account\Exceptions\InvalidAccountOperationException;
use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Number\Contracts\INumber;
use dnj\UserLogger\Contracts\ILogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionManager implements ITransactionManager
{
    use UpdatingAccount;

    public function __construct(protected ILogger $userLogger)
    {
    }

    public function getByID(int $id): Transaction
    {
        return Transaction::query()->findOrFail($id);
    }

    /**
     * @return Collection<Transaction>
     */
    public function findByAccount(int $accountId): Collection
    {
        return Transaction::query()
            ->where('from_id', $accountId)
            ->orWhere('to_id', $accountId)
            ->get();
    }

    public function transfer(
        int $fromAccountId,
        int $toAccountId,
        INumber $amount,
        ?array $meta = null,
        bool $force = false,
        bool $userActivityLog = false,
    ): Transaction {
        return DB::transaction(function () use ($fromAccountId, $toAccountId, $amount, $meta, $force, $userActivityLog) {
            $fromAccount = $this->getAccountForUpdate($fromAccountId);
            $toAccount = $this->getAccountForUpdate($toAccountId);

            if (AccountStatus::ACTIVE !== $fromAccount->status) {
                throw new DisabledAccountException($fromAccount);
            }

            if (AccountStatus::ACTIVE !== $toAccount->status) {
                throw new DisabledAccountException($toAccount);
            }

            if (!$fromAccount->getCanSend()) {
                throw new InvalidAccountOperationException($fromAccount, 'send');
            }

            if (!$toAccount->getCanReceive()) {
                throw new InvalidAccountOperationException($toAccount, 'receive');
            }

            $available = $fromAccount->getAvailableBalance();
            if (!$force and $available->lt($amount)) {
                throw new BalanceInsufficientException($fromAccount, $available, $amount);
            }

            $transaction = $this->createTransaction($fromAccount, $toAccount, $amount, $meta, $userActivityLog);

            return $transaction;
        });
    }

    public function rollback(int $transactionId, bool $force = false, bool $userActivityLog = false): Transaction
    {
        return DB::transaction(function () use ($transactionId, $force, $userActivityLog) {
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transactionId);

            $fromAccount = $this->getAccountForUpdate($transaction->getFromAccountID());
            $toAccount = $this->getAccountForUpdate($transaction->getToAccountID());

            if (AccountStatus::ACTIVE !== $fromAccount->status) {
                throw new DisabledAccountException($fromAccount);
            }

            if (AccountStatus::ACTIVE !== $toAccount->status) {
                throw new DisabledAccountException($toAccount);
            }

            $amount = $transaction->getAmount();

            $available = $toAccount->getAvailableBalance();
            if (!$force and $available->lt($amount)) {
                throw new BalanceInsufficientException($toAccount, $available, $amount);
            }

            $rollbackTransaction = $this->createTransaction($toAccount, $fromAccount, $amount, [
                'type' => 'rollback-transaction',
                'original-transaction' => $transactionId,
            ], $userActivityLog);

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($transaction)
                    ->withProperties([
                        'rollback-transaction' => $rollbackTransaction->getID(),
                    ])
                    ->log('rollbacked');
            }

            return $rollbackTransaction;
        });
    }

    public function update(int $transactionId, ?array $meta = null, bool $userActivityLog = false): Transaction
    {
        return DB::transaction(function () use ($transactionId, $meta, $userActivityLog) {
            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail($transactionId);
            $transaction->meta = $meta;
            $changes = $transaction->changesForLog();
            $transaction->save();

            if ($userActivityLog) {
                $this->userLogger
                    ->withRequest(request())
                    ->performedOn($transaction)
                    ->withProperties($changes)
                    ->log('updated');
            }

            return $transaction;
        });
    }

    protected function createTransaction(Account $fromAccount, Account $toAccount, INumber $amount, ?array $meta = null, bool $userActivityLog = false): Transaction
    {
        if ($fromAccount->getCurrencyID() !== $toAccount->getCurrencyID()) {
            throw new CurrencyMismatchException($fromAccount->getCurrency(), $toAccount->getCurrency());
        }

        $transaction = new Transaction();
        $transaction->from_id = $fromAccount->getID();
        $transaction->to_id = $toAccount->getID();
        $transaction->amount = $amount;
        $transaction->meta = $meta;
        $changes = $transaction->changesForLog();
        $transaction->save();

        if ($userActivityLog) {
            $this->userLogger
                ->withRequest(request())
                ->performedOn($transaction)
                ->withProperties($changes)
                ->log('created');
        }

        $fromAccount->balance = $fromAccount->getBalance()->sub($amount);
        $fromAccount->save();

        $toAccount->balance = $toAccount->getBalance()->add($amount);
        $toAccount->save();

        return $transaction;
    }
}
