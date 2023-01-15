<?php

namespace dnj\Account\Tests\Feature;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Models\Account;
use dnj\Account\Tests\Models\User;
use dnj\Account\Tests\TestCase;
use dnj\Currency\Models\Currency;
use Illuminate\Testing\Fluent\AssertableJson;

class AccountControllerTest extends TestCase
{
    public function testStore(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $secondUser = User::factory()
                          ->create();
        $USD = Currency::factory()
                       ->asUSD()
                       ->create();
        $data = [
            'title' => 'account1',
            'can_send' => false,
            'can_receive' => false,
            'currency_id' => $USD->getID(),
            'user_id' => $secondUser->id,
            'meta' => [
                'name' => 'john',
                'age' => 30,
                'cat' => null,
            ],
        ];
        $this->postJson(route('accounts.store'), $data)
             ->assertStatus(201)
             ->assertJson(fn (AssertableJson $json) => $json->has('data.id')
                                                             ->etc())
             ->assertJson([
                              'data' => $data,
                          ]);
    }

    public function testUpdate(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $account = Account::factory()
                          ->create();
        $this->putJson(route('accounts.update', ['account' => $account->id]), [
            'meta' => [
                'key' => 'value',
            ],
        ])
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($account) {
                 $json->where('data.title', $account->title);
                 $json->where('data.currency_id', $account->currency_id);
             });
    }

    public function testDestroy(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $account = Account::factory()
                          ->create();
        $this->deleteJson(route('accounts.destroy', ['account' => $account->id]))
             ->assertStatus(204);
    }

    public function testIndexFilterUser(): void
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
               ->create();
        $this->getJson(route('accounts.index', [
            'user_id' => $secondUser->id,
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($secondUser) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.user_id', $secondUser->id);
                 $json->etc();
             });
    }

    public function testIndexFilterStatus(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        Account::factory(5)
               ->create();
        $account = Account::factory()
                          ->withStatus(AccountStatus::DEACTIVE)
                          ->create();
        $this->getJson(route('accounts.index', [
            'status' => AccountStatus::DEACTIVE->value,
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($account) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.status', $account->status->value);
                 $json->etc();
             });
    }

    public function testIndexFliterByTitle(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        Account::factory(5)
               ->create();
        $account = Account::factory()
                          ->withTitle('this is a test')
                          ->create();
        $this->getJson(route('accounts.index', [
            'title' => 'this is a test',
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($account) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.title', $account->title);
                 $json->etc();
             });
    }

    public function testIndexFliterByCurrency(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $USD = Currency::factory()
                       ->asUSD()
                       ->create();
        Account::factory(5)
               ->create();
        $account = Account::factory()
                          ->withCurrency($USD)
                          ->create();
        $this->getJson(route('accounts.index', [
            'currency_id' => $USD->getID(),
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($account) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.currency_id', $account->currency_id);
                 $json->etc();
             });
    }

    public function testIndexFliterByCanReceive(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        Account::factory(5)
               ->create();
        Account::factory()
                          ->cantReceive(false)
                          ->create();
        $this->getJson(route('accounts.index', [
            'can_receive' => false,
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.can_receive', 0);
                 $json->etc();
             });
    }

    public function testIndexFliterByCantSend(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        Account::factory()
               ->cantSend(false)
               ->create();
        $this->getJson(route('accounts.index', [
            'can_send' => false,
        ]))
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) {
                 $json->has('data', 1);
                 $json->whereContains('data.0.can_send', 0);
                 $json->etc();
             });
    }
}
