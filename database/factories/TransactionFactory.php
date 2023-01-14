<?php

namespace dnj\Account\Database\Factories;

use dnj\Account\Models\Account;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory {
	public function definition () {
		// TODO: Implement definition() method.
		return [
			'from_id' => Account::factory() ,
			'to_id' => Account::factory() ,
			'amount' => Number::fromInt(0) ,
			'meta' => null ,
		];
	}
	
	public function withFromAccount ( Account|AccountFactory $account ) {
		return $this->state(fn() => [
			'from_id' => $account ,
		]);
	}
	
	public function withToAccount ( Account|AccountFactory $account ) {
		return $this->state(fn() => [
			'to_id' => $account ,
		]);
	}
	
	public function withAmount ( string|int|float|INumber $amount ) {
		return $this->state(fn() => [
			'amount' => $amount ,
		]);
	}
	
	public function withMeta ( array $meta ) {
		return $this->state(fn() => [
			'meta' => $meta,
		]);
	}
}