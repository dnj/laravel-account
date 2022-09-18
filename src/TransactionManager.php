<?php

namespace dnj\Account;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Exceptions\CurrencyMismatchException;
use dnj\Account\Exceptions\DisabledAccountException;
use dnj\Account\Exceptions\InvalidAccountOperationException;
use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Number\Contracts\INumber;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TransactionManager implements ITransactionManager
{
    public function getByID(int $id): Transaction
    {
        return Transaction::query()->findOrFail($id);
    }

    /**
     * @return Collection<ITransaction>
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
    ): Transaction {
        DB::beginTransaction();
        try {
            /**
             * @var Account
             */
            $fromAccount = Account::query()
                ->lockForUpdate()
                ->findOrFail($fromAccountId);

            /**
             * @var Account
             */
            $toAccount = Account::query()
                ->lockForUpdate()
                ->findOrFail($toAccountId);

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

            $transaction = $this->createTransaction($fromAccount, $toAccount, $amount, $meta);

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function rollback(int $transactionId): Transaction
    {
        DB::beginTransaction();
        try {
            $transaction = $this->getByID($transactionId);

            $fromAccount = Account::query()
                ->lockForUpdate()
                ->findOrFail($transaction->getFromAccountID());

            $toAccount = Account::query()
                ->lockForUpdate()
                ->findOrFail($transaction->getToAccountID());

            if (AccountStatus::ACTIVE !== $fromAccount->status) {
                throw new DisabledAccountException($fromAccount);
            }

            if (AccountStatus::ACTIVE !== $toAccount->status) {
                throw new DisabledAccountException($toAccount);
            }

            $transaction = $this->createTransaction($toAccount, $fromAccount, $transaction->getAmount(), [
                'type' => 'rollback-transaction',
                'original-transaction' => $transactionId,
            ]);

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(int $transactionId, ?array $meta = null): Transaction
    {
        $transaction = $this->getByID($transactionId);
        $transaction->meta = $meta;
        $transaction->save();

        return $transaction;
    }

    protected function createTransaction(Account $fromAccount, Account $toAccount, INumber $amount, ?array $meta = null): Transaction
    {
        if ($fromAccount->getCurrencyID() !== $toAccount->getCurrencyID()) {
            throw new CurrencyMismatchException($fromAccount->getCurrency(), $toAccount->getCurrency());
        }

        $transaction = new Transaction();
        $transaction->from_id = $fromAccount->getID();
        $transaction->to_id = $toAccount->getID();
        $transaction->amount = $amount;
        $transaction->meta = $meta;
        $transaction->save();

        $fromAccount->balance = $fromAccount->getBalance()->sub($amount);
        $fromAccount->save();

        $toAccount->balance = $toAccount->getBalance()->add($amount);
        $toAccount->save();

        return $transaction;
    }
}
