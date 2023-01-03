<?php

namespace dnj\Account\Tests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Number\Number;
use Illuminate\Testing\Fluent\AssertableJson;

class AccountTest extends TestCase {
	
	/**
	 * Testing validation for creating new account
	 *
	 * @return void
	 */
	public function test_validation_for_createing_new_account () {
		
		$data = [
			'title' => '' ,
			'currency_id' => '' ,
			'meta' => 'test' ,
		];
		$response = $this->postJson('/api/create' , $data);
		$response->assertStatus(422)
				 ->assertJson(fn( AssertableJson $json ) => $json->hasAll([
																			  "errors.title" ,
																			  "errors.currency_id" ,
																			  "errors.meta" ,
																		  ])
																 ->etc());
	}
	
	/**
	 * Testing create new account without authenticate
	 *
	 * @return void
	 */
	public function test_create_new_account_without_authenticate () {
		$USD = $this->createUSD();
		$data = [
			'title' => 'account1' ,
			'balance' => Number::fromInt(1) ,
			'can_send' => false ,
			'can_receive' => false ,
			'currency_id' => $USD->getID() ,
			'user_id' ,
			'status' => AccountStatus::ACTIVE ,
			'meta' => [
				[
					"name" => "john" ,
					"age" => 30 ,
					"cat" => null ,
				] ,
			] ,
		];
		$response = $this->postJson('/api/create' , $data);
		$response->assertStatus(200)
				 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
					 $json->where('account.title' , $data[ 'title' ]);
					 $json->where('account.can_send' , $data[ 'can_send' ]);
					 $json->where('account.can_receive' , $data[ 'can_receive' ]);
					 $json->where('account.currency_id' , $data[ 'currency_id' ]);
				 });
	}
	
	/**
	 * Testing validation for update account
	 *
	 * @return void
	 */
	public function tes_validation_for_update_account () {
		$data = [
			'account_id' => '' ,
		];
		$response = $this->postJson('/api/update' , $data);
		$response->assertStatus(422)
				 ->assertJson(fn( AssertableJson $json ) => $json->hasAll([
																			  'errors.account_id' ,
																		  ])
																 ->etc());
	}
	/**
	 * Testing update account without authenticate
	 *
	 * @return void
	 */
	public function tes_update_account_without_authenticate () {
		$USD = $this->createUSD();
		$account = $this->createUSDAccount($USD);
		$data = [
			'title' => 'USD Reserve 2' ,
			'balance' => Number::fromInt(1) ,
			'canSend' => 1 ,
			'canReceive' => 0 ,
			'currency_id' => $account->currency_id ,
			'status' => AccountStatus::DEACTIVE->value ,
			'account_id' => $account->id ,
		];
		$response = $this->postJson('/api/update' , $data);
		$response->assertStatus(200)
				 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
					 $json->where('account.title' , $data[ 'title' ]);
					 $json->where('account.can_send' , $data[ 'canSend' ]);
					 $json->where('account.can_receive' , $data[ 'canReceive' ]);
					 $json->where('account.currency_id' , $data[ 'currency_id' ]);
				 });
		$response = $this->postJson('/api/update' , [
			'account_id' => 985 ,
		]);
		$response->assertStatus(404);
	}
	
	/**
	 * Testing validation delete account
	 *
	 * @return void
	 */
	public function tes_validation_delete_account () {
		$data = [
			'account_id' => '' ,
		];
		$response = $this->postJson('/api/destroy' , $data);
		$response->assertStatus(422)
				 ->assertJson(fn( AssertableJson $json ) => $json->hasAll([
																			  "errors.account_id" ,
																		  ])
																 ->etc());
	}
	
	/**
	 * Testing delete account
	 *
	 * @return void
	 */
	public function tes_delete_account () {
		$USD = $this->createUSD();
		$account = $this->createUSDAccount($USD);
		$data = [
			'account_id' => $account->getID() ,
		];
		$response = $this->postJson('/api/destroy' , $data);
		$response->assertStatus(200);
		$this->postJson('/api/destroy' , [
			'account_id' => 985 ,
		])
			 ->assertStatus(404);
	}
	
	/**
	 * Testing filter account without authenticate
	 *
	 * @return void
	 */
	public function tes_filter_account_without_authenticate () {
		$response = $this->postJson('/api/filter');
		$response->assertStatus(200)
				 ->assertJson(function ( AssertableJson $json ) {
					 $json->hasAll([ 'accounts.0.id' ]);
					 $json->hasAll([ 'accounts.0.title' ]);
					 $json->hasAll([ 'accounts.0.currency_id' ]);
				 });
	}
}
