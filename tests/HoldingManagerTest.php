<?php

namespace dnj\Account\Tests;

use dnj\Account\Exceptions\BalanceInsufficientException;
use dnj\Account\Exceptions\MultipleAccountOperationException;
use dnj\Account\Models\Holding;
use dnj\Number\Number;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class HoldingManagerTest extends TestCase
{
    public function testAcquire()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);

        $now = time();
        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(3), null, true);
        $holding1 = $this->getHoldingManager()->acquire($account->getID(), Number::fromInput('1.05'), ['foo' => 'bar']);
        $holding1 = $this->getHoldingManager()->getByID($holding1->getID());
        $this->assertSame($account->getID(), $holding1->getAccountID());
        $this->assertSame(['foo' => 'bar'], $holding1->getMeta());
        $this->assertSame($now, $holding1->getCreateTime());
        $this->assertSame($now, $holding1->getUpdateTime());
        $this->assertSame(1.05, $holding1->getAmount()->getValue());

        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(1.05, $account->getHoldingBalance()->getValue());
        $this->assertSame(1.95, $account->getAvailableBalance()->getValue());

        $holding2 = $this->getHoldingManager()->acquire($account->getID(), Number::fromInput('0.95'));
        $this->assertSame(0.95, $holding2->getAmount()->getValue());

        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(2, $account->getHoldingBalance()->getValue());
        $this->assertSame(1, $account->getAvailableBalance()->getValue());

        $holdings = $this->getHoldingManager()->findByAccount($account->getID());
        $this->assertCount(2, $holdings);
        $this->assertContainsOnlyInstancesOf(Holding::class, $holdings);
    }

    public function testAcquireBalanceInsufficient()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);

        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(3), null, true);

        $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(4), null, true);
        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(4, $account->getHoldingBalance()->getValue());
        $this->assertSame(-1, $account->getAvailableBalance()->getValue());

        $this->expectException(BalanceInsufficientException::class);
        $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(1));
    }

    public function testRelease()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);
        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(3), null, true);

        $holding = $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(2), null);
        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(2, $account->getHoldingBalance()->getValue());
        $this->assertSame(1, $account->getAvailableBalance()->getValue());

        $account = $this->getHoldingManager()->release($holding->getID());
        $this->assertSame(0, $account->getHoldingBalance()->getValue());
        $this->assertSame(3, $account->getAvailableBalance()->getValue());
    }

    public function testReleaseMultiple()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);
        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(4), null, true);

        $holding1 = $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(1), null);
        $holding2 = $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(2), null);

        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(3, $account->getHoldingBalance()->getValue());
        $this->assertSame(1, $account->getAvailableBalance()->getValue());

        $account = $this->getHoldingManager()->releaseMultiple([$holding1->getID(), $holding2->getID()]);

        $this->assertSame(0, $account->getHoldingBalance()->getValue());
        $this->assertSame(4, $account->getAvailableBalance()->getValue());
    }

    public function testReleaseMultipleWithInvalidID()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);
        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(4), null, true);

        $holding1 = $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(1), null);
        $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(2), null);

        $this->expectException(ModelNotFoundException::class);
        $this->getHoldingManager()->releaseMultiple([$holding1->getID(), 5000]);
    }

    public function testReleaseMultipleWithMultipleAccount()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account1 = $this->createUSDAccount($USD);
        $account2 = $this->createUSDAccount($USD);
        $this->getTransactionManager()->transfer($system->getID(), $account1->getID(), Number::fromInt(4), null, true);
        $this->getTransactionManager()->transfer($system->getID(), $account2->getID(), Number::fromInt(4), null, true);

        $holding1 = $this->getHoldingManager()->acquire($account1->getID(), Number::fromInt(1), null);
        $holding2 = $this->getHoldingManager()->acquire($account1->getID(), Number::fromInt(1), null);
        $holding3 = $this->getHoldingManager()->acquire($account2->getID(), Number::fromInt(1), null);

        $this->expectException(MultipleAccountOperationException::class);
        $this->getHoldingManager()->releaseMultiple([$holding1->getID(), $holding2->getID(), $holding3->getID()]);
    }

    public function testReleaseAll()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);
        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(4), null, true);

        $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(1), null);
        $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(2), null);

        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(3, $account->getHoldingBalance()->getValue());
        $this->assertSame(1, $account->getAvailableBalance()->getValue());

        $account = $this->getHoldingManager()->releaseAll($account->getID());

        $this->assertSame(0, $account->getHoldingBalance()->getValue());
        $this->assertSame(4, $account->getAvailableBalance()->getValue());

        $holdings = $this->getHoldingManager()->findByAccount($account->getID());
        $this->assertCount(0, $holdings);
    }

    public function testUpdate()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD);
        $account = $this->createUSDAccount($USD);
        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(3), null, true);
        $holding = $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(2), null);
        $this->assertNull($holding->getMeta());

        $holding = $this->getHoldingManager()->update($holding->getID(), [
            'meta' => ['foo' => 'bar'],
            'amount' => Number::fromInt(1),
        ]);
        $this->assertSame(['foo' => 'bar'], $holding->getMeta());
        $this->assertSame(1, $holding->getAmount()->getValue());

        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(1, $account->getHoldingBalance()->getValue());

        $holding = $this->getHoldingManager()->update($holding->getID(), [
            'amount' => Number::fromInt(1),
        ]);
        $this->assertSame(1, $holding->getAmount()->getValue());

        $this->expectException(InvalidArgumentException::class);
        $this->getHoldingManager()->update($holding->getID(), [
            'amount' => Number::fromInt(-1),
        ]);
    }

    public function testRecalucateHoldingBalance()
    {
        $USD = $this->createUSD();
        $system = $this->createUSDAccount($USD, 1);
        $account = $this->createUSDAccount($USD, 1);
        $this->assertSame(0, $account->getHoldingBalance()->getValue());

        $this->getTransactionManager()->transfer($system->getID(), $account->getID(), Number::fromInt(5), null, true);
        $this->getHoldingManager()->acquire($account->getID(), Number::fromInt(3), null);

        $account = $this->getAccountManager()->getByID($account->getID());
        $this->assertSame(3, $account->getHoldingBalance()->getValue());
        $this->assertSame(2, $account->getAvailableBalance()->getValue());

        $account->holding = Number::fromInt(0);
        $account->save();

        $account = $this->getHoldingManager()->recalucateHoldingBalance($account->getID());
        $this->assertSame(3, $account->getHoldingBalance()->getValue());
        $this->assertSame(2, $account->getAvailableBalance()->getValue());
    }
}
