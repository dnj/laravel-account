<?php

namespace dnj\Account\Tests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Exceptions\DisabledAccountException;
use dnj\Number\Number;
use Illuminate\Testing\Fluent\AssertableJson;

class TransactionTest extends TestCase
{
    /**
     * Testing validation for transfer.
     *
     * @return void
     */
    public function testValidationForTransfer()
    {
        $data = [
            'from_id' => '',
            'to_id' => '',
            'amount' => '',
        ];
        $route = $this->getRoute('transaction/transfer');
        $this->postJson($route, $data)
             ->assertStatus(422)
             ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                                                                          'errors.from_id',
                                                                          'errors.to_id',
                                                                          // 'errors.amount' ,
                                                                      ])
                                                             ->etc());
    }

    /**
     * Testing transfer.
     */
    public function testTransfer()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $data = [
            'from_id' => $account1->getID(),
            'to_id' => $account2->getID(),
            'amount' => 1.02,
            'meta' => ['transfer_key' => 'transfer_value'],
            'force' => true,
        ];
        $route = $this->getRoute('transaction/transfer');
        $this->postJson($route, $data)
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($data) {
                 $json->where('transaction.meta', $data['meta']);
             });
    }

    /**
     * Testing validation update transfer.
     */
    public function testValidationUpdateTransfer()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), ['key1' => 'value1'], true);
        $data = [
            'transaction_id' => '',
            'meta' => ['transaction_key_1' => 'transaction_value_1'],
        ];
        $route = $this->getRoute('transaction/update');
        $this->postJson($route, $data)
             ->assertStatus(422)
             ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                                                                          'errors.transaction_id',
                                                                      ])
                                                             ->etc());
    }

    /**
     * Testing update transfer.
     */
    public function testUpdateTransfer()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), ['key1' => 'value1'], true);
        $data = [
            'transaction_id' => $transaction->id,
            'meta' => ['transaction_key_1' => 'transaction_value_1'],
        ];
        $route = $this->getRoute('transaction/update');
        $this->postJson($route, $data)
             ->assertStatus(200)
             ->assertJson(function (AssertableJson $json) use ($data) {
                 $json->where('transaction.meta', $data['meta']);
             });
    }

    /**
     * Testing validation rollback.
     */
    public function testValidationRollbackTransfer()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), null, true);
        $data = [
            'transaction_id' => '',
        ];
        $route = $this->getRoute('transaction/rollback');
        $this->postJson($route, $data)
             ->assertStatus(422)
             ->assertJson(fn (AssertableJson $json) => $json->hasAll([
                                                                          'errors.transaction_id',
                                                                      ])
                                                             ->etc());
    }

    /**
     * Testing rollback.
     */
    public function testRollbackTransfer()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), null, true);
        $data = [
            'transaction_id' => $transaction->id,
        ];
        $route = $this->getRoute('transaction/rollback');
        $response = $this->postJson($route, $data)
                         ->assertStatus(200);
        $this->assertSame($response['transaction']['meta']['type'], 'rollback-transaction');
    }

    /**
     * Testing rollback from account deactived.
     */
    public function testRollbackFromAccountDeactived()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), null, true);
        $data = [
            'transaction_id' => $transaction->id,
        ];
        $account1->status = AccountStatus::DEACTIVE;
        $account1->save();
        $this->expectException(DisabledAccountException::class);
        $route = $this->getRoute('transaction/rollback');
        $this->postJson($route, $data);
    }

    /**
     * Testing rollback to account deactived.
     */
    public function testRollbackToAccountDeactived()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $transaction = $this->getTransactionManager()
                            ->transfer($account1->getID(), $account2->getID(), Number::formString('1.02'), null, true);
        $data = [
            'transaction_id' => $transaction->id,
        ];
        $account2->status = AccountStatus::DEACTIVE;
        $account2->save();
        $this->expectException(DisabledAccountException::class);
        $route = $this->getRoute('transaction/rollback');
        $this->postJson($route, $data);
    }

    /**
     * Testing transfer without user authenticate.
     */
    public function tes_transfer_without_user_authenticate()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $data = [
            'from_id' => $account1->id,
            'to_id' => $account2->id,
            'amount' => Number::formString('1.02')
                              ->getValue(),
            'meta' => ['transfer_key' => 'transfer_value'],
            'force' => true,
        ];
        $this->postJson('/api/transaction/transfer', $data)
             ->assertStatus(401);
    }

    /**
     * Testing update transfer without authenticated.
     */
    public function tes_update_transfer_without_authenticate()
    {
        $transaction = $this->createTransaction();
        $this->postJson('/api/transaction/update', [
            'transaction_id' => $transaction->id,
            'meta' => ['transaction_key_1' => 'transaction_value_1'],
        ])
             ->assertStatus(401);
    }

    /**
     * Testing rollback transfer without authenticated.
     */
    public function tes_rollback_transfer_without_authenticated()
    {
        $transaction = $this->createTransaction();
        $data = [
            'transaction_id' => $transaction->id,
        ];
        $this->postJson('/api/transaction/rollback', $data)
             ->assertStatus(401);
    }
}
