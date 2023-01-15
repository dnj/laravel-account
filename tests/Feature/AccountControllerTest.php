<?php

namespace dnj\Account\Tests\Feature;

use Carbon\Carbon;
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

    public function testFilterByUser(): void
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

    public function testFilterByStatus(): void
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

    public function testFliterByTitle(): void
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

    public function testFliterByCurrency(): void
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

    public function testFliterByCanReceive(): void
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

    public function testFliterByCantSend(): void
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

    public function testFliterByBalance(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $account1 = Account::factory()
                           ->withBalance(100)
                           ->create();
        $account2 = Account::factory()
                           ->withBalance(80)
                           ->create();
        $account3 = Account::factory()
                           ->withBalance(60)
                           ->create();
        $account4 = Account::factory()
                           ->withBalance(50)
                           ->create();
        $account5 = Account::factory()
                           ->withBalance(79)
                           ->create();

        $accountIds = [
            $account1->getID(),
            $account2->getID(),
            $account3->getID(),
            $account4->getID(),
            $account5->getID(),
        ];

        $this->getJson(route('accounts.index', [
            'balance_from' => 50,
            'balance_to' => 70,
        ]))->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($accountIds) {
                 $json->has('data', 2);
                 $json->whereContains('data.0.id', $accountIds[2]);
                 $json->whereContains('data.1.id', $accountIds[3]);
                 $json->etc();
             });
    }

    public function testFliterByCreate(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $account1 = Account::factory()
                           ->create([
                                        'created_at' => Carbon::yesterday(),
                                    ]);
        $account2 = Account::factory()
                           ->create([
                                        'created_at' => Carbon::now()->subDays(6),
                                    ]);
        $account3 = Account::factory()
                           ->create([
                                        'created_at' => Carbon::now()->subDays(5),
                                    ]);
        $account4 = Account::factory()
                           ->create([
                                        'created_at' => Carbon::now()->subDays(4),
                                    ]);
        $account5 = Account::factory()
                           ->create([
                                        'created_at' => Carbon::now()->subDays(3),
                                    ]);

        $accountIds = [
            $account1->getID(),
            $account2->getID(),
            $account3->getID(),
            $account4->getID(),
            $account5->getID(),
        ];

        $this->getJson(route('accounts.index', [
            'created_from' => Carbon::now()->subDays(5)->toDateString(),
            'created_to' => Carbon::now()->toDateString(),
        ]))->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($accountIds) {
                 $json->has('data', 4);
                 $json->whereContains('data.0.id', $accountIds[0]);
                 $json->whereContains('data.1.id', $accountIds[2]);
                 $json->whereContains('data.2.id', $accountIds[3]);
                 $json->whereContains('data.3.id', $accountIds[4]);
                 $json->etc();
             });
    }
}
