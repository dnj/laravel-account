<?php

namespace dnj\Account\Http\Controllers;

use dnj\Account\Http\Requests\TransactionSearchRequest;
use dnj\Account\Http\Requests\TransactionStoreRequest;
use dnj\Account\Http\Requests\TransactionUpdateRequest;
use dnj\Account\Http\Resources\TransactionResource;
use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Account\TransactionManager;
use dnj\Number\Number;

class TransactionController extends Controller
{

	public function __construct(protected TransactionManager $transactionManager)
	{
	}

	public function index(Account $account, TransactionSearchRequest $request)
	{
		$data = $request->validated();

		$q = Transaction::query()
			->where('from_id', $account->id)
			->orWhere('to_id', $account->id)
			->orderBy("id", "DESC");
		if (isset($data['created_from'])) {
			$q->where("created_at", ">=", $data['created_from']);
		}
		if (isset($data['created_to'])) {
			$q->where("created_at", "<", $data['created_to']);
		}
		if (isset($data['amount_from'])) {
			$q->where("amount", ">=", $data['amount_from']);
		}
		if (isset($data['amount_to'])) {
			$q->where("amount", "<", $data['amount_to']);
		}

		return new TransactionResource($q->cursorPaginate());
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
		);

		return new TransactionResource($transaction);
	}

	public function update(Transaction $transaction, TransactionUpdateRequest $request)
	{
		$data = $request->validated();
		$transaction = $this->transactionManager->update($transaction->id, $data['meta']);

		return new TransactionResource($transaction);
	}

	public function destroy(Transaction $transaction)
	{
		$rollback = $this->transactionManager->rollback($transaction->id);

		return new TransactionResource($rollback);
	}
}
