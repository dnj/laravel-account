<?php

namespace dnj\Account\Tests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Models\Account;
use dnj\Account\Tests\Models\User;
use dnj\Currency\Models\Currency;
use Illuminate\Testing\Fluent\AssertableJson;

class AccountControllerTest extends TestCase
{
    /**
     * Testing validation  create new account.
     *
     * @return void
     */
    public function testValidationCreateNewAccount()
    {
        $user = User::factory()
                    ->create();
        $this->postJson(route('accounts.store'))
             ->assertStatus(401);
        $this->actingAs($user);
        $data = [
            'title' => '',
            'currency_id' => '',
            'meta' => 'test',
        ];
        $response = $this->postJson(route('accounts.store'), $data);
        $response->assertStatus(422)
                 ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                                                                              'errors.title',
                                                                              'errors.currency_id',
                                                                              'errors.meta',
                                                                          ])
                                                                 ->etc());
    }

    /**
     * Testing create new account.
     */
    public function testStore(): void
    {
        $USD = Currency::factory()
                       ->asUSD()
                       ->create();
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $data = [
            'title' => 'account1',
            'can_send' => false,
            'can_receive' => false,
            'currency_id' => $USD->getID(),
            'user_id' => $user->id,
            'meta' => [
                'name' => 'john',
                'age' => 30,
                'cat' => null,
            ],
        ];
        $response = $this->postJson(route('accounts.store'), $data);
        $response->assertStatus(201)
                 ->assertJson(fn (AssertableJson $json) => $json->has('data.id')
                                                                 ->etc())
                 ->assertJson(compact('data'));
    }

    /**
     * Testing  update account.
     *
     * @return void
     */
    public function testUpdate()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $account = Account::factory()
                          ->create();
        $this->putJson(route('accounts.update', ['account' => $account->id]), [
            'meta' => [
                [
                    'key' => 'value',
                ],
            ],
            'title' => 'this is a first account',
        ])
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($account) {
                 $json->where('data.title', $account->getTitle());
                 $json->where('data.currency_id', $account->getCurrencyID());
             });
    }

    /**
     * Testing delete account.
     *
     * @return void
     */
    public function testDestroy()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $account = Account::factory()
                          ->create();
        $this->deleteJson(route('accounts.destroy', ['account' => $account->id]))
             ->assertStatus(204);
    }

    /**
     * Testing filter account without authenticate.
     *
     * @return void
     */
    public function testIndex()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        Account::factory(5)
               ->create();
        $secondUser = User::factory()
                          ->create();
        $account = Account::factory()
                          ->withUserId($secondUser->id)
                          ->create();
        $this->getJson(route('accounts.index', [
            'user_id' => $secondUser->id,
            'title' => $account->title,
            'currency_id' => $account->currency_id,
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($secondUser) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.user_id', $secondUser->id);
                 $json->etc();
             });
    }

    public function testIndexAccountDeactive()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        Account::factory(5)
               ->create();
        $secondUser = User::factory()
                          ->create();
        Account::factory()
               ->withUserId($secondUser->id)
               ->withStatus(AccountStatus::DEACTIVE)
               ->create();
        $this->getJson(route('accounts.index', [
            'user_id' => $secondUser->id,
            'status' => AccountStatus::DEACTIVE,
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($secondUser) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.user_id', $secondUser->id);
                 $json->etc();
             });
    }
}
