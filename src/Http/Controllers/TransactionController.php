<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\Http\Requests\CreateNewTransactionRequest;
use dnj\Account\Http\Requests\TransactionRequest;
use dnj\Account\Http\Resources\TransactionResource;
use dnj\Account\TransactionManager;
use dnj\Number\Number;

class TransactionController extends Controller
{
    public TransactionManager $transaction_manager;

    public function __construct(TransactionManager $transaction_manager)
    {
        $this->transaction_manager = $transaction_manager;
    }

    /**
     * Transfer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transfer(CreateNewTransactionRequest $request)
    {
        $from_id = $request->get('from_id');
        $to_id = $request->get('to_id');
        $amount = Number::formString($request->get('amount'));
        $mate = $request->get('meta');
        $force = $request->get('force');
        $transaction = $this->transaction_manager->transfer($from_id, $to_id, $amount, $mate, $force);

        return response()->json([
                                    'transaction' => TransactionResource::make($transaction),
                                ]);
    }

    /**
     * Updating Transaction.
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function update(TransactionRequest $request)
    {
        $transaction_id = $request->get('transaction_id');
        $meta = $request->get('meta');
        $transaction = $this->transaction_manager->update($transaction_id, $meta);

        return response()->json([
                                    'transaction' => TransactionResource::make($transaction),
                                ]);
    }

    /**
     * Transaction Rollback.
     *
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function transactionRollBack(TransactionRequest $request)
    {
        $transaction_id = $request->get('transaction_id');
        $transaction = $this->transaction_manager->rollback($transaction_id);

        return response()->json([
                                    'transaction' => TransactionResource::make($transaction),
                                ]);
    }
}
