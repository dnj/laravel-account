<?php

namespace dnj\Account\Tests;

use dnj\Account\AccountManager;
use dnj\Account\Contracts\AccountStatus;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Models\Account;
use dnj\Currency\Contracts\ICurrencyManager;
use dnj\Currency\Contracts\RoundingBehaviour;
use dnj\Currency\CurrencyManager;
use dnj\Currency\Models\Currency;

class AccountManagerTest extends TestCase
{
    public function getManager(): AccountManager
    {
        return $this->app->make(IAccountManager::class);
    }

    public function getCurrencyManager(): CurrencyManager
    {
        return $this->app->make(ICurrencyManager::class);
    }

    public function createUSD(): Currency
    {
        return $this->getCurrencyManager()->create('USD', 'US Dollar', '$', '', RoundingBehaviour::CEIL, 2);
    }

    public function createUSDAccount(Currency $USD, ?int $userId = null): Account
    {
        return $this->getManager()->create('USD Reserve', $USD->getID(), $userId);
    }

    public function testCreate()
    {
        $now = time();
        $USD = $this->createUSD();
        $account = $this->createUSDAccount($USD);
        $this->assertSame($USD->getID(), $account->getCurrencyID());
        $this->assertSame($USD->getID(), $account->getCurrency()->getID());
        $this->assertSame(0, $account->getBalance()->getValue());
        $this->assertSame($now, $account->getCreateTime());
        $this->assertSame($now, $account->getUpdateTime());
    }

    public function testGetByID()
    {
        $USD = $this->createUSD();
        $account = $this->createUSDAccount($USD);
        $accountCopy = $this->getManager()->getByID($account->getID());
        $this->assertSame($account->getID(), $accountCopy->getID());
    }

    public function testFinds()
    {
        $USD = $this->createUSD();
        $systemAccount = $this->createUSDAccount($USD, null);
        $userAccount = $this->createUSDAccount($USD, 2);

        $accounts = $this->getManager()->findByUser(2);
        $this->assertSame(1, $accounts->count());
        $this->assertSame($userAccount->getID(), $accounts[0]->getID());

        $accounts = $this->getManager()->findByUser(null);
        $this->assertSame(1, $accounts->count());
        $this->assertSame($systemAccount->getID(), $accounts[0]->getID());

        $accounts = $this->getManager()->findAll();
        $this->assertSame(2, $accounts->count(2));
    }

    public function testUpdate()
    {
        $USD = $this->createUSD();
        $account = $this->createUSDAccount($USD);
        $this->assertSame('USD Reserve', $account->getTitle());
        $this->assertNull($account->getUserID());
        $this->assertSame(AccountStatus::ACTIVE, $account->getStatus());
        $this->assertTrue($account->getCanSend());
        $this->assertTrue($account->getCanReceive());
        $this->assertNull($account->getMeta());

        $account = $this->getManager()->update($account->getID(), [
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
        $USD = $this->createUSD();
        $account = $this->createUSDAccount($USD);
        $this->getManager()->delete($account->getID());
        $this->assertTrue(true);
    }
}
