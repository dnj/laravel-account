<?php

namespace dnj\Account\Tests\Feature;

use dnj\Account\Models\Account;
use dnj\Account\Tests\Models\User;
use dnj\Account\Tests\TestCase;
use dnj\Currency\Models\Currency;
use dnj\Number\Number;
use Illuminate\Testing\Fluent\AssertableJson;

class TransactionControllerTest extends TestCase {

	public function testStore() {
		$user = User::factory()->create();
		$this->actingAs($user);

		$USD = Currency::factory()->asUSD()->create();
		$account1 = Account::factory()->withCurrency($USD)->create();
		$account2 = Account::factory()->withCurrency($USD)->create();

		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => "1.02",
			'meta' => [ 'transfer_key' => 'transfer_value' ],
			'force' => true,
		];
		$this->postJson(route('transactions.store') , $data)
			 ->assertStatus(201)
			 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
				 $json->where('data.meta' , $data[ 'meta' ]);
			 });
	}
	

	public function testUpdate () {
		$user = User::factory()->create();
		$this->actingAs($user);

		$USD = Currency::factory()->asUSD()->create();
		$account1 = Account::factory()->withCurrency($USD)->create();
		$account2 = Account::factory()->withCurrency($USD)->create();
	
		$transaction = $this->getTransactionManager()->transfer(
			$account1->getID(),
			$account2->getID(),
			Number::formString('1.02') ,
			[ 'key1' => 'value1' ],
			true
		);
		$data = [
			'meta' => [ 'transaction_key_1' => 'transaction_value_1' ] ,
		];
		$this->putJson(route("transactions.update", array('transaction' => $transaction->getID())), $data)
			 ->assertStatus(200)
			 ->assertJson(array(
				'data' => $data
			 ));
	}
	
	public function testDestroy () {
		$user = User::factory()->create();
		$this->actingAs($user);
	
		$USD = Currency::factory()->asUSD()->create();
		[$account1, $account2] = Account::factory(2)->withCurrency($USD)->create();

		$transaction = $this->getTransactionManager()->transfer(
			$account1->getID(),
			$account2->getID(),
			Number::formString('1.02'),
			null,
			true,
		);

		$this->deleteJson(route("transactions.destroy", ['transaction' => $transaction->getID()]))
			 ->assertStatus(201)
			 ->assertJson(array(
				'data' => array(
					'from_id' => $account2->getID(),
					'to_id' => $account1->getID(),
					'amount' => $transaction->getAmount()->__toString(),
					'meta' => array(
						'type' => 'rollback-transaction',
						'original-transaction' => $transaction->getID(),
					)
				)
			 ));
	}
}