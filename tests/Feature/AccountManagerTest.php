<?php

namespace dnj\Account\Tests\Feature;

use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Models\Account;
use dnj\Account\Tests\TestCase;
use dnj\Currency\Models\Currency;
use dnj\Number\Number;

class AccountManagerTest extends TestCase
{
    public function testCreate()
    {
        $now = time();
        $USD = Currency::factory()->asUSD()->create();
        $account = $this->getAccountManager()->create('USD Reserve', $USD->getID());
        $this->assertSame($USD->getID(), $account->getCurrencyID());
        $this->assertSame($USD->getID(), $account->getCurrency()->getID());
        $this->assertSame(0, $account->getBalance()->getValue());
        $this->assertSame($now, $account->getCreateTime());
        $this->assertSame($now, $account->getUpdateTime());
    }

    public function testGetByID()
    {
        $account = Account::factory()->withUSD()->withoutUser()->create();
        $accountCopy = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame($account->getID(), $accountCopy->getID());
    }

    public function testFinds()
    {
        $USD = Currency::factory()
            ->asUSD()
            ->create();
        $systemAccount = Account::factory()
            ->withCurrency($USD)
            ->withoutUser()
            ->create();;
        $userAccount = Account::factory()
            ->withCurrency($USD)
            ->withUserId(2)
            ->create();

        $accounts = $this->getAccountManager()->findByUser(2);
        $this->assertSame(1, $accounts->count());
        $this->assertSame($userAccount->getID(), $accounts[0]->getID());

        $accounts = $this->getAccountManager()->findByUser(null);
        $this->assertSame(1, $accounts->count());
        $this->assertSame($systemAccount->getID(), $accounts[0]->getID());

        $accounts = $this->getAccountManager()->findAll();
        $this->assertSame(2, $accounts->count(2));
    }

    public function testUpdate()
    {
        $account = Account::factory()->withUSD()->withoutUser()->create();

        $account = $this->getAccountManager()->update($account->getID(), [
            'title' => 'Test Account',
            'userId' => 1,
            'status' => AccountStatus::DEACTIVE,
            'canSend' => false,
            'canReceive' => false,
            'meta' => [
                'dummy' => 'change',
            ],
        ]);

        $this->assertSame('Test Account', $account->getTitle());
        $this->assertSame(1, $account->getUserID());
        $this->assertSame(AccountStatus::DEACTIVE, $account->getStatus());
        $this->assertFalse($account->getCanSend());
        $this->assertFalse($account->getCanReceive());
        $this->assertSame(['dummy' => 'change'], $account->getMeta());
    }

    public function testDelete()
    {
        $account = Account::factory()->create();
        $this->getAccountManager()->delete($account->getID());
        $this->assertTrue(true);
    }

    public function testRecalucateBalance()
    {
        $USD = Currency::factory()
            ->asUSD()
            ->create();
        $account1 = Account::factory()
            ->withCurrency($USD)
            ->withUserId(1)
            ->withBalance(0)
            ->create();
        $account2 = Account::factory()
            ->withCurrency($USD)
            ->withUserId(1)
            ->withBalance(0)
            ->create();

        $this->getTransactionManager()->transfer($account1->getID(), $account2->getID(), Number::fromInput('1.02'), null, true);
        $this->getTransactionManager()->transfer($account1->getID(), $account2->getID(), Number::fromInput('2.05'), null, true);

        $account1 = $this->getAccountManager()->getByID($account1->getID());
        $account2 = $this->getAccountManager()->getByID($account2->getID());

        $this->assertSame(-3.07, $account1->getBalance()->getValue());
        $this->assertSame(3.07, $account2->getBalance()->getValue());

        $account1->balance = Number::fromInt(0);
        $account1->save();

        $account2->balance = Number::fromInt(0);
        $account2->save();

        $account1 = $this->getAccountManager()->recalucateBalance($account1->getID());
        $account2 = $this->getAccountManager()->recalucateBalance($account2->getID());

        $this->assertSame(-3.07, $account1->getBalance()->getValue());
        $this->assertSame(3.07, $account2->getBalance()->getValue());
    }
}
