<?php

namespace dnj\Account\Tests;

use dnj\Account\Contracts\AccountStatus;
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
		$data = [
			'from_id' => '' ,
			'to_id' => '' ,
			'amount' => '' ,
		];
		$route = $this->getRoute('transaction/transfer');
		$this->postJson($route , $data)
			 ->assertStatus(422)
			 ->assertJson(fn( AssertableJson $json ) => $json->hasAll([
																		  'errors.from_id' ,
																		  'errors.to_id' ,
																		  //'errors.amount' ,
																	  ])
															 ->etc());
	}
	
	/**
	 * Testing transfer
	 */
	public function test_transfer () {
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD);
		$account2 = $this->createUSDAccount($USD);
		$data = [
			'from_id' => $account1->getID() ,
			'to_id' => $account2->getID() ,
			'amount' => 1.02 ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$route = $this->getRoute('transaction/transfer');
		$this->postJson($route , $data)
			 ->assertStatus(200)
			 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
				 $json->where('transaction.meta' , $data[ 'meta' ]);
			 });
	}
	/**
	 * Testing update transfer
	 */
	public function test_update_transfer () {
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD);
		$account2 = $this->createUSDAccount($USD);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , [ 'key1' => 'value1' ] , true ,);
		$data = [
			'meta' => [ 'transaction_key_1' => 'transaction_value_1' ] ,
		];
		$route = $this->getRoute("transaction/update/{$transaction->id}");
		$this->postJson($route , $data)
			 ->assertStatus(200)
			 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
				 $json->where('transaction.meta' , $data[ 'meta' ]);
			 });
	}
	/**
	 * Testing rollback
	 */
	public function test_rollback_transfer () {
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD);
		$account2 = $this->createUSDAccount($USD);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , null , true);
		$route = $this->getRoute("transaction/rollback/{$transaction->id}");
		$response = $this->postJson($route)
						 ->assertStatus(200);
		$this->assertSame($response[ 'transaction' ][ 'meta' ][ 'type' ] , 'rollback-transaction');
	}
	
	/**
	 * Testing rollback from account deactived
	 */
	public function test_rollback_from_account_deactived () {
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD);
		$account2 = $this->createUSDAccount($USD);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , null , true);
		$account1->status = AccountStatus::DEACTIVE;
		$account1->save();
		$this->expectException(DisabledAccountException::class);
		$route = $this->getRoute("transaction/rollback/{$transaction->id}");
		$this->postJson($route);
	}
	
	/**
	 * Testing rollback to account deactived
	 */
	public function test_rollback_to_account_deactived () {
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD);
		$account2 = $this->createUSDAccount($USD);
		$transaction = $this->getTransactionManager()
							->transfer($account1->getID() , $account2->getID() , Number::formString('1.02') , null , true);
		$account2->status = AccountStatus::DEACTIVE;
		$account2->save();
		$this->expectException(DisabledAccountException::class);
		$route = $this->getRoute("transaction/rollback/{$transaction->id}");
		$this->postJson($route);
	}
	
	/**
	 * Testing transfer without user authenticate
	 */
	public function tes_transfer_without_user_authenticate () {
		$USD = $this->createUSD();
		$account1 = $this->createUSDAccount($USD);
		$account2 = $this->createUSDAccount($USD);
		$data = [
			'from_id' => $account1->id ,
			'to_id' => $account2->id ,
			'amount' => Number::formString('1.02')
							  ->getValue() ,
			'meta' => [ 'transfer_key' => 'transfer_value' ] ,
			'force' => true ,
		];
		$this->postJson('/api/transaction/transfer' , $data)
			 ->assertStatus(401);
	}
	
	/**
	 * Testing update transfer without authenticated
	 */
	public function tes_update_transfer_without_authenticate () {
		$transaction = $this->createTransaction();
		$this->postJson('/api/transaction/update' , [
			'transaction_id' => $transaction->id ,
			'meta' => [ 'transaction_key_1' => 'transaction_value_1' ] ,
		])
			 ->assertStatus(401);
	}
	
	/**
	 * Testing rollback transfer without authenticated
	 */
	public function tes_rollback_transfer_without_authenticated () {
		$transaction = $this->createTransaction();
		$data = [
			'transaction_id' => $transaction->id ,
		];
		$this->postJson('/api/transaction/rollback' , $data)
			 ->assertStatus(401);
	}
}