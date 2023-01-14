<?php

namespace dnj\Account\Tests;

use dnj\Account\Models\Account;
use dnj\Account\Tests\Models\User;
use dnj\Currency\Models\Currency;
use dnj\Number\Number;
use Illuminate\Testing\Fluent\AssertableJson;

class TransactionControllerTest extends TestCase
{
    /**
     * Testing validation for transfer.
     *
     * @return void
     */
    public function testValidation()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $data = [
            'from_id' => '',
            'to_id' => '',
            'amount' => '',
        ];
        $this->postJson(route('transactions.store'), $data)
             ->assertStatus(422)
             ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                                                                          'errors.from_id',
                                                                          'errors.to_id',
                                                                          'errors.amount',
                                                                      ])
                                                             ->etc());
    }

    /**
     * Testing store transaction.
     */
    public function testStore()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $USD = Currency::factory()
                       ->asUSD()
                       ->create();
        $account1 = Account::factory()
                           ->withCurrency($USD)
                           ->create();
        $account2 = Account::factory()
                           ->withCurrency($USD)
                           ->create();
        $data = [
            'from_id' => $account1->getID(),
            'to_id' => $account2->getID(),
            'amount' => '1.25',
            'meta' => ['transfer_key' => 'transfer_value'],
            'force' => true,
        ];
        $this->postJson(route('transactions.store'), $data)
             ->assertStatus(201)
             ->assertJson(function (AssertableJson $json) use ($data) {
                 $json->where('data.meta', $data['meta']);
                 $json->where('data.from_id', $data['from_id']);
                 $json->where('data.to_id', $data['to_id']);
                 $json->where('data.amount', $data['amount']);
             });
    }

    /**
     * Testing update transaction.
     */
    public function testUpdate(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
        $USD = Currency::factory()
                       ->asUSD()
                       ->create();
        $account1 = Account::factory()
                           ->withCurrency($USD)
                           ->create();
        $account2 = Account::factory()
                           ->withCurrency($USD)
                           ->create();
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), ['key1' => 'value1'], true);
        $this->assertSame(1.02, $transaction->getAmount()->getValue());
        $data = [
            'meta' => ['transaction_key_1' => 'transaction_value_1'],
        ];
        $this->putJson(route('transactions.update', ['transaction' => $transaction->getID()]), $data)
             ->assertStatus(200)
            ->assertJson(function (AssertableJson $json) use ($data) {
                $json->where('data.meta', $data['meta']);
            });
    }

    /**
     * Testing rollback.
     */
    public function testRollback()
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);
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
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

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
}
