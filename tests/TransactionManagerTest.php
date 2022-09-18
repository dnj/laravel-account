<?php

namespace dnj\Account\Tests;

use dnj\Account\AccountManager;
use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Exceptions\CurrencyMismatchException;
use dnj\Account\Exceptions\DisabledAccountException;
use dnj\Account\Exceptions\InvalidAccountOperationException;
use dnj\Account\Models\Account;
use dnj\Account\TransactionManager;
use dnj\Currency\Contracts\ICurrencyManager;
use dnj\Currency\Contracts\RoundingBehaviour;
use dnj\Currency\CurrencyManager;
use dnj\Currency\Models\Currency;
use dnj\Number\Number;

class TransactionManagerTest extends TestCase
{
    public function getAccountManager(): AccountManager
    {
        return $this->app->make(IAccountManager::class);
    }

    public function getTransactionManager(): TransactionManager
    {
        return $this->app->make(ITransactionManager::class);
    }

    public function getCurrencyManager(): CurrencyManager
    {
        return $this->app->make(ICurrencyManager::class);
    }

    public function createUSD(): Currency
    {
        return $this->getCurrencyManager()->create('USD', 'US Dollar', '$', '', RoundingBehaviour::CEIL, 2);
    }

    public function createEUR(): Currency
    {
        return $this->getCurrencyManager()->create('USD', 'US Dollar', '$', '', RoundingBehaviour::CEIL, 2);
    }

    public function createUSDAccount(Currency $USD, ?int $userId = null): Account
    {
        return $this->getAccountManager()->create('USD Reserve', $USD->getID(), $userId);
    }

    public function createEURAccount(Currency $EUR, ?int $userId = null): Account
    {
        return $this->getAccountManager()->create('EUR Reserve', $EUR->getID(), $userId);
    }

    public function testTransfer()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            ['key1' => 'value1']
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());
    }

    public function testTransferFromAccountDeactived()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account1->status = AccountStatus::DEACTIVE;
        $account1->save();

        $account2 = $this->createUSDAccount($USD);

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
    }

    public function testTransferToAccountDeactived()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $account2->status = AccountStatus::DEACTIVE;
        $account2->save();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
    }

    public function testTransferFromAccountCantSend()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account1->can_send = false;
        $account1->save();
        $account2 = $this->createUSDAccount($USD);

        $this->expectException(InvalidAccountOperationException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
    }

    public function testTransferToAccountCantReceive()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $account2->can_receive = false;
        $account2->save();

        $this->expectException(InvalidAccountOperationException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
    }

    public function testTransferDiffrentCurrencies()
    {
        $USD = $this->createUSD();
        $EUR = $this->createEUR();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($EUR);

        $this->expectException(CurrencyMismatchException::class);
        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
    }

    public function testRollback()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

        $rollBackTransaction = $this->getTransactionManager()->rollback($transaction->getID());
        $this->assertSame($account2->getID(), $rollBackTransaction->getFromAccountID());
        $this->assertSame($account1->getID(), $rollBackTransaction->getToAccountID());
        $this->assertSame(1.02, $rollBackTransaction->getAmount()->getValue());
    }

    public function testRollbackFromAccountDeactived()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

        $account1->status = AccountStatus::DEACTIVE;
        $account1->save();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->rollback($transaction->getID());
    }

    public function testRollbackToAccountDeactived()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
        $this->assertSame(1.02, $transaction->getAmount()->getValue());

        $account2->status = AccountStatus::DEACTIVE;
        $account2->save();

        $this->expectException(DisabledAccountException::class);
        $this->getTransactionManager()->rollback($transaction->getID());
    }

    public function testUpdate()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);

        $transaction = $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02'),
            ['key1' => 'value1']
        );
        $this->assertSame(['key1' => 'value1'], $transaction->getMeta());

        $updatedTransaction = $this->getTransactionManager()->update($transaction->getID(), ['key2' => 'value2']);
        $this->assertSame(['key2' => 'value2'], $updatedTransaction->getMeta());
    }

    public function testFindByAccount()
    {
        $USD = $this->createUSD();
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $account3 = $this->createUSDAccount($USD);

        $this->getTransactionManager()->transfer(
            $account1->getID(),
            $account2->getID(),
            Number::formString('1.02')
        );
        $this->getTransactionManager()->transfer(
            $account2->getID(),
            $account1->getID(),
            Number::formString('2.05')
        );
        $this->getTransactionManager()->transfer(
            $account2->getID(),
            $account3->getID(),
            Number::formString('4.00')
        );

        $transactions = $this->getTransactionManager()->findByAccount($account1->getID());
        $this->assertSame(2, $transactions->count());
        $this->assertSame(1.02, $transactions[0]->getAmount()->getValue());
        $this->assertSame(2.05, $transactions[1]->getAmount()->getValue());
    }
}
