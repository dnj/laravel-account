<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\Http\Requests\CreateNewTransactionRequest;
use dnj\Account\Http\Requests\TransactionRequest;
use dnj\Account\Http\Resources\TransactionResource;
use dnj\Account\Models\Transaction;
use dnj\Account\TransactionManager;
use dnj\Number\Number;

class TransactionController extends Controller {
	protected TransactionManager $transactionManager;
	
	public function __construct ( TransactionManager $transactionManager ) {
		$this->transactionManager = $transactionManager;
	}
	
	/**
	 * Transfer
	 *
	 * @param \dnj\Account\Http\Requests\CreateNewTransactionRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store ( CreateNewTransactionRequest $request ) {
		$from_id = $request->get('from_id');
		$to_id = $request->get('to_id');
		$amount = Number::formString($request->get('amount'));
		$mate = $request->get('meta');
		$force = $request->get('force');
		$transaction = $this->transactionManager->transfer($from_id , $to_id , $amount , $mate , $force);
		
		return TransactionResource::make($transaction);
	}
	
	/**
	 * @param                                               $transactionId
	 * @param \dnj\Account\Http\Requests\TransactionRequest $request
	 * @return \dnj\Account\Http\Resources\TransactionResource
	 */
	public function update ( Transaction $transaction , TransactionRequest $request ) {
		$meta = $request->get('meta');
		$transaction = $this->transactionManager->update($transaction->id , $meta);
		
		return TransactionResource::make($transaction);
	}
	
	/**
	 * @param $transactionId
	 * @return \dnj\Account\Http\Resources\TransactionResource
	 */
	public function transactionRollBack ( Transaction $transaction ) {
		$transaction = $this->transactionManager->rollback($transaction->id);
		
		return TransactionResource::make($transaction);
	}
}