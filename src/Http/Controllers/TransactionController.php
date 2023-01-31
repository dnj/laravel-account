<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Http\Requests\TransactionSearchRequest;
use dnj\Account\Http\Requests\TransactionStoreRequest;
use dnj\Account\Http\Requests\TransactionUpdateRequest;
use dnj\Account\Http\Resources\TransactionResource;
use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Number\Number;
use dnj\UserLogger\Contracts\ILogger;

class TransactionController extends Controller
{
    public function __construct(
        protected ITransactionManager $transactionManager,
        protected ILogger $userLogger
    ) {
    }

    public function index(Account $account, TransactionSearchRequest $request)
    {
        $data = $request->validated();
        $q = Transaction::query()
            ->where('from_id', $account->id)
            ->orWhere('to_id', $account->id)
            ->orderBy('id', 'DESC');
        if (isset($data['created_from'])) {
            $q->where('created_at', '>=', $data['created_from']);
        }
        if (isset($data['created_to'])) {
            $q->where('created_at', '<', $data['created_to']);
        }
        if (isset($data['amount_from'])) {
            $q->where('amount', '>=', $data['amount_from']);
        }
        if (isset($data['amount_to'])) {
            $q->where('amount', '<', $data['amount_to']);
        }
        $q = $q->cursorPaginate();

        return TransactionResource::collection($q);
    }

    public function store(TransactionStoreRequest $request)
    {
        $data = $request->validated();
        $data['amount'] = Number::formString($data['amount']);
        $transaction = $this->transactionManager->transfer(
            $data['from_id'],
            $data['to_id'],
            $data['amount'],
            $data['meta'] ?? null,
            $data['force'] ?? false,
            true,
        );

        return TransactionResource::make($transaction);
    }

    public function update(Transaction $transaction, TransactionUpdateRequest $request)
    {
        $data = $request->validated();
        $transaction = $this->transactionManager->update($transaction->id, $data['meta'], true);

        return TransactionResource::make($transaction);
    }

    public function destroy(Transaction $transaction)
    {
        $rollback = $this->transactionManager->rollback($transaction->id, false, true);

        return TransactionResource::make($rollback);
    }
}
