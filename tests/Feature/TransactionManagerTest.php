<?php

namespace dnj\Account\Tests;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Exceptions\CurrencyMismatchException;
use dnj\Account\Exceptions\DisabledAccountException;
use dnj\Account\Exceptions\InvalidAccountOperationException;
use dnj\Account\Models\Account;
use dnj\Currency\Models\Currency;
use dnj\Number\Number;

class TransactionManagerTest extends TestCase
{
    public function testTransfer()
    {
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
        $this->assertSame(1.02, $transaction->getAmount()->getValue());
    }

    public function testTransferFromAccountDeactived()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->deactived()->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
    }

    public function testTransferToAccountDeactived()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->deactived()->create();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
    }

    public function testTransferFromAccountCantSend()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->cantSend()->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $this->expectException(InvalidAccountOperationException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
    }

    public function testTransferToAccountCantReceive()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->cantReceive()->create();

        $this->expectException(InvalidAccountOperationException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
    }

    public function testTransferDiffrentCurrencies()
    {
        $account1 = Account::factory()->withUSD()->create();
        $account2 = Account::factory()->withEUR()->create();

        $this->expectException(CurrencyMismatchException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
    }

    public function testRollback()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

        $rollBackTransaction = $this->getTransactionManager()->rollback($transaction->getID());
        $this->assertSame($account2->getID(), $rollBackTransaction->getFromAccountID());
        $this->assertSame($account1->getID(), $rollBackTransaction->getToAccountID());
        $this->assertSame(1.02, $rollBackTransaction->getAmount()->getValue());
    }

    public function testRollbackFromAccountDeactived()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

        $account1->status = AccountStatus::DEACTIVE;
        $account1->save();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->rollback($transaction->getID());
    }

    public function testRollbackToAccountDeactived()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true,
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

        $account2->status = AccountStatus::DEACTIVE;
        $account2->save();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->rollback($transaction->getID());
    }

    public function testUpdate()
    {
        $USD = Currency::factory()->asUSD()->create();
        $account1 = Account::factory()->withCurrency($USD)->create();
        $account2 = Account::factory()->withCurrency($USD)->create();

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            ['key1' => 'value1'],
            true,
        );
        $this->assertSame(['key1' => 'value1'], $transaction->getMeta());

        $updatedTransaction = $this->getTransactionManager()->update($transaction->getID(), ['key2' => 'value2']);
        $this->assertSame(['key2' => 'value2'], $updatedTransaction->getMeta());
    }

    public function testFindByAccount()
    {
        $USD = Currency::factory()->asUSD()->create();
        [$account1, $account2, $account3] = Account::factory(3)->withCurrency($USD)->create();

        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            null,
            true,
        );
        $this->getTransactionManager()->transfer(
            $account2->getID(),
            $account1->getID(),
            Number::formString('2.05'),
            null,
            true,
        );
        $this->getTransactionManager()->transfer(
            $account2->getID(),
            $account3->getID(),
            Number::formString('4.00'),
            null,
            true,
        );

        $transactions = $this->getTransactionManager()->findByAccount($account1->getID());
        $this->assertSame(2, $transactions->count());
        $this->assertSame(1.02, $transactions[0]->getAmount()->getValue());
        $this->assertSame(2.05, $transactions[1]->getAmount()->getValue());
    }
}
