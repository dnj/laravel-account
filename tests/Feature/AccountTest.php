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
		
		
		$this->postJson(route('accounts.store'))
			 ->assertStatus(401);
		$user = $this->createNewUser();
		$this->actingAs($user);
		$data = [
			'title' => '' ,
			'currency_id' => '' ,
			'meta' => 'test' ,
		];
		$response = $this->postJson(route('accounts.store') , $data);
		$response->assertStatus(422)
				 ->assertJson(fn( AssertableJson $json ) => $json->hasAll([
																			  "errors.title" ,
																			  "errors.currency_id" ,
																			  "errors.meta" ,
																		  ])
																 ->etc());
	}
	
	/**
	 * Testing create new account
	 *
	 * @return void
	 */
	public function test_create_new_account () {
		
		$this->postJson(route('accounts.store'))
			 ->assertStatus(401);
		$user = $this->createNewUser();
		$this->actingAs($user);
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
		$response = $this->postJson(route('accounts.store') , $data);
		$response->assertStatus(201)
				 ->assertJson(function ( AssertableJson $json ) use ( $data ) {
					 $json->where('account.title' , $data[ 'title' ]);
					 $json->where('account.can_send' , $data[ 'can_send' ]);
					 $json->where('account.can_receive' , $data[ 'can_receive' ]);
					 $json->where('account.currency_id' , $data[ 'currency_id' ]);
				 });
	}
	
	/**
	 * Testing  update account
	 *
	 * @return void
	 */
	public function test_update_account () {
		
		$user = $this->createNewUser();
		$account = $this->createAccount();
		$this->putJson(route('accounts.update' , [ 'account' => 2 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$this->putJson(route('accounts.update' , [ 'account' => 2 ]))
			 ->assertStatus(404);
		$this->putJson(route('accounts.update' , [ 'account' => 1 ]) , [
			'meta' => [
				[
					'key' => 'value' ,
				] ,
			] ,
		])
			 ->assertStatus(200)
			 ->assertJson(function ( AssertableJson $json ) use ( $account ) {
				 $json->where('account.title' , $account[ 0 ]->title);
				 $json->where('account.currency_id' , $account[ 0 ]->currency_id);
			 });
	}
	
	/**
	 * Testing delete account
	 *
	 * @return void
	 */
	public function test_delete_account () {
		$user = $this->createNewUser();
		$this->createAccount();
		$this->deleteJson(route('accounts.destroy' , [ 'account' => 2 ]))
			 ->assertStatus(401);
		$this->actingAs($user);
		$this->deleteJson(route('accounts.destroy' , [ 'account' => 2 ]))
			 ->assertStatus(404);
		$this->deleteJson(route('accounts.destroy' , [ 'account' => 1 ]))
			 ->assertStatus(200)
			 ->assertJson([
							  'message' => 'Account deleted successfully' ,
						  ]);
	}
	
	/**
	 * Testing filter account without authenticate
	 *
	 * @return void
	 */
	public function test_filter_account () {
		$user = $this->createNewUser();
		$this->createAccount(20);
		$this->getJson(route('accounts.index'))
			 ->assertStatus(401);
		$this->actingAs($user);
		$this->getJson(route('accounts.index'))
			 ->assertStatus(200)
			 ->assertJson(function ( AssertableJson $json ) {
				 $json->hasAll([ 'data.0.id' ]);
				 $json->hasAll([ 'data.0.title' ]);
				 $json->hasAll([ 'data.0.currency_id' ]);
			 });
	}
}
