<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\Http\Requests\TransactionStoreRequest;
use dnj\Account\Http\Requests\TransactionUpdateRequest;
use dnj\Account\Http\Resources\TransactionResource;
use dnj\Account\Models\Transaction;
use dnj\Account\TransactionManager;
use dnj\Number\Number;

class TransactionController extends Controller
{
    public TransactionManager $transactionManager;

    public function __construct(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    /**
     * Transfer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(TransactionStoreRequest $request)
    {
        $data = $request->validated();
        $data['amount'] = Number::formString($data['amount']);
        $transaction = $this->transactionManager->transfer($data['from_id'], $data['to_id'], $data['amount'], $data['meta'] ?? null, $data['force'] ?? false);

        return TransactionResource::make($transaction);
    }

    /**
     * Updating Transaction.
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function update(Transaction $transaction, TransactionUpdateRequest $request)
    {
        $data = $request->validated();
        $transaction = $this->transactionManager->update($transaction->id, $data['meta']);

        return TransactionResource::make($transaction);
    }

    /**
     * Transaction Rollback.
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function destroy(Transaction $transaction)
    {
        $rollback = $this->transactionManager->rollback($transaction->id);

        return new TransactionResource($rollback);
    }
}
