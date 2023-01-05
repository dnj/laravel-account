<?php

namespace dnj\Account\Tests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Exceptions\BalanceInsufficientException;
use dnj\Account\Exceptions\CurrencyMismatchException;
use dnj\Account\Exceptions\DisabledAccountException;
use dnj\Number\Number;
use Illuminate\Testing\Fluent\AssertableJson;

class TransactionTest extends TestCase {
	/**
	 * Testing validation for transfer
	 *
	 * @return  void
	 */
	public function test_validation_for_transfer () {
		$user = $this->createNewUser();
		$this->postJson(route('transaction.store'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$this->postJson(route('transaction.store'))
			 ->assertStatus(422)
			 ->assertJson(fn( AssertableJson $json ) => $json->hasAll([
																		  'errors.from_id' ,
																		  'errors.to_id' ,
																	  ])
															 ->etc());
	}
	
	/**
	 * Testing transfer without equal currency
	 */
	public function test_transfer_without_equal_currency () {
		$user = $this->createNewUser();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($EUR , $user->id);
		$this->postJson(route('transaction.store'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 1.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->expectException(CurrencyMismatchException::class);
		$this->postJson(route('transaction.store') , $data)
			 ->assertStatus(500);
	}
	
	/**
	 * Testing transfer without equal currency
	 */
	public function test_transfer_from_account_deactive () {
		$user = $this->createNewUser();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($EUR , $user->id);
		$this->postJson(route('transaction.store'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$account1->status = AccountStatus::DEACTIVE;
		$account1->save();
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 1.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->expectException(DisabledAccountException::class);
		$this->postJson(route('transaction.store') , $data)
			 ->assertStatus(500);
	}
	
	/**
	 * Testing transfer without equal currency
	 */
	public function tes_transfer_to_account_deactive () {
		$user = $this->createNewUser();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($EUR , $user->id);
		$this->postJson(route('transaction.store'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$account2->status = AccountStatus::DEACTIVE;
		$account2->save();
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 1.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->expectException(DisabledAccountException::class);
		$this->postJson(route('transaction.store') , $data)
			 ->assertStatus(500);
	}
	
	/**
	 * Testing transfer balance Insufficient
	 */
	public function test_transfer_balance_insufficient () {
		$user = $this->createNewUser();
		$USD = $this->createUSD();
		$EUR = $this->createEUR();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($EUR , $user->id);
		$this->postJson(route('transaction.store'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$account1->balance = 1125.02;
		$account1->holding = 125.02;
		$account1->save();
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 111.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->expectException(BalanceInsufficientException::class);
		$this->postJson(route('transaction.store') , $data)
			 ->assertStatus(500);
	}
	
	/**
	 * Testing transfer_with equal currency
	 */
	public function test_transfer () {
		$user = $this->createNewUser();
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($USD , $user->id);
		$this->postJson(route('transaction.store'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 1.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->postJson(route('transaction.store') , $data)
			 ->assertStatus(201)
			 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
				 $json->where('transaction.meta' , $data[ 'meta' ]);
			 });
	}
	
	/**
	 * Testing update transfer
	 */
	public function test_update_transfer () {
		$user = $this->createNewUser();
		$this->putJson(route('transaction.update' , [ 'transaction' => 1 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($USD , $user->id);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , [ 'key1' => 'value1' ] , true ,);
		$this->putJson(route('transaction.update' , [ 'transaction' => 2 ]))
			 ->assertStatus(404);
		$data = [
			'meta' => [ 'transaction_key_1' => 'transaction_value_1' ] ,
		];
		$this->putJson(route('transaction.update' , [ 'transaction' => $transaction->id ]) , $data)
			 ->assertStatus(200)
			 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
				 $json->where('transaction.meta' , $data[ 'meta' ]);
			 });
	}
	
	/**
	 * Testing transfer rollback balance Insufficient
	 */
	public function test_transfer_rollback_balance_insufficient () {
		$user = $this->createNewUser();
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 1 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($USD , $user->id);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , [ 'key1' => 'value1' ] , true ,);
		$this->actingAs($user);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 2 ]))
			 ->assertStatus(404);
		$account2->balance = 1125.02;
		$account2->holding = 125.02;
		$account2->save();
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 111.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->expectException(BalanceInsufficientException::class);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => $transaction->id ]) , $data);
	}
	
	/**
	 * Testing rollback
	 */
	public function test_rollback_transfer () {
		$user = $this->createNewUser();
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 1 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($USD , $user->id);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , [ 'key1' => 'value1' ] , true ,);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 2 ]))
			 ->assertStatus(404);
		$response = $this->postJson(route('transaction.rollback' , [ 'transaction' => $transaction->id ]))
						 ->assertStatus(201);
		$this->assertSame($response[ 'transaction' ][ 'meta' ][ 'type' ] , 'rollback-transaction');
	}
	
	/**
	 * Testing rollback from account deactived
	 */
	public function test_rollback_from_account_deactived () {
		$user = $this->createNewUser();
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 1 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($USD , $user->id);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , [ 'key1' => 'value1' ] , true ,);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 2 ]))
			 ->assertStatus(404);
		$account1->status = AccountStatus::DEACTIVE;
		$account1->save();
		$this->expectException(DisabledAccountException::class);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => $transaction->id ]))
			 ->assertStatus(500);
	}
	
	/**
	 * Testing rollback to account deactived
	 */
	public function test_rollback_to_account_deactived () {
		$user = $this->createNewUser();
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 1 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD , $user->id);
		$account2 = $this->createUSDAccount($USD , $user->id);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , [ 'key1' => 'value1' ] , true ,);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => 2 ]))
			 ->assertStatus(404);
		$account2->status = AccountStatus::DEACTIVE;
		$account2->save();
		$this->expectException(DisabledAccountException::class);
		$this->postJson(route('transaction.rollback' , [ 'transaction' => $transaction->id ]))
			 ->assertStatus(500);
	}
}