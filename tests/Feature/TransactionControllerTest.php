<?php

namespace dnj\Account\Tests\Feature;

use dnj\Account\Models\Account;
use dnj\Account\Models\Transaction;
use dnj\Account\Tests\Models\User;
use dnj\Account\Tests\TestCase;
use dnj\Currency\Models\Currency;
use dnj\Number\Number;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;

class TransactionControllerTest extends TestCase
{
    public function testStore()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $data = [
            'from_id' => $account1->getID(),
            'to_id' => $account2->getID(),
            'amount' => '1.02',
            'meta' => ['transfer_key' => 'transfer_value'],
            'force' => true,
        ];
        $this->postJson(route('transactions.store'), $data)
             ->assertStatus(201)
             ->assertJson(function (AssertableJson $json) use ($data) {
                 $json->where('data.meta', $data['meta']);
             });
    }

    public function testUpdate()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            ['key1' => 'value1'],
            true
        );
        $data = [
            'meta' => ['transaction_key_1' => 'transaction_value_1'],
        ];
        $this->putJson(route('transactions.update', ['transaction' => $transaction->getID()]), $data)
             ->assertStatus(200)
             ->assertJson([
                'data' => $data,
             ]);
    }

    public function testDestroy()
    {
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

        $this->deleteJson(route('transactions.destroy', ['transaction' => $transaction->getID()]))
             ->assertStatus(201)
             ->assertJson([
                'data' => [
                    'from_id' => $account2->getID(),
                    'to_id' => $account1->getID(),
                    'amount' => $transaction->getAmount()->__toString(),
                    'meta' => [
                        'type' => 'rollback-transaction',
                        'original-transaction' => $transaction->getID(),
                    ],
                ],
             ]);
    }

    public function testFilterByAmount()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $USD = Currency::factory()->asUSD()->create();
        [$account1, $account2, $account3] = Account::factory(3)->withCurrency($USD)->create();
        $transaction1 = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('76'),
            null,
            true,
        );
        $transaction2 = $this->getTransactionManager()->transfer(
            $account2->getID(),
            $account1->getID(),
            Number::formString('100'),
            null,
            true,
        );
        $transaction3 = $this->getTransactionManager()->transfer(
            $account2->getID(),
            $account3->getID(),
            Number::formString('80'),
            null,
            true,
        );
        $transaction4 = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account3->getID(),
            Number::formString('90'),
            null,
            true,
        );
        $transactionIds = [
            $transaction1->getID(),
            $transaction2->getID(),
            $transaction3->getID(),
            $transaction4->getID(),
        ];
        $this->getJson(route('transactions.index', [
            'account' => $account1,
            'amount_from' => 80,
            'amount_to' => 100,
        ]))->assertJson(function (AssertableJson $json) use ($transactionIds) {
            $json->has('data', 2);
            $json->whereContains('data.0.amount', '90');
            $json->whereContains('data.1.amount', '76');
            $json->whereContains('data.0.id', $transactionIds[3]);
            $json->whereContains('data.1.id', $transactionIds[0]);
            $json->etc();
        });
    }

    public function testFilterByCreatedAt()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $USD = Currency::factory()->asUSD()->create();
        [$account1, $account2, $account3] = Account::factory(3)->withCurrency($USD)->create();
        $transaction1 = Transaction::factory()
            ->withFromAccount($account1)
            ->withToAccount($account2)
            ->create([
                'created_at' => Carbon::now(),
                     ]);
        $transaction2 = Transaction::factory()
                   ->withFromAccount($account1)
                   ->withToAccount($account2)
                   ->create([
                                'created_at' => Carbon::now()->subDays(4),
                            ]);
        $transaction3 = Transaction::factory()
                   ->withFromAccount($account2)
                   ->withToAccount($account1)
                   ->create([
                                'created_at' => Carbon::now()->subDays(3),
                            ]);
        $transaction4 = Transaction::factory()
                                   ->withFromAccount($account3)
                                   ->withToAccount($account1)
                                   ->create([
                                                'created_at' => Carbon::now()->subDays(2),
                                            ]);
        $transactionIds = [
            $transaction1->getID(),
            $transaction2->getID(),
            $transaction3->getID(),
            $transaction4->getID(),
        ];

        $this->getJson(route('transactions.index', [
            'account' => $account1,
            'created_from' => Carbon::now()->subDays(4)->toDateString(),
            'created_to' => Carbon::now()->subDays(2)->toDateString(),
        ]))->assertJson(function (AssertableJson $json) use ($transactionIds) {
            $json->has('data', 3);
            $json->whereContains('data.0.id', $transactionIds[2]);
            $json->whereContains('data.1.id', $transactionIds[1]);
            $json->whereContains('data.2.id', $transactionIds[0]);
            $json->etc();
        });
    }
}
