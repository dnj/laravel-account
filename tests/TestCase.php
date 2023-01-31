<?php

namespace dnj\Account\Tests;

use dnj\Account\AccountManager;
use dnj\Account\AccountServiceProvider;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\IHoldingManager;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\HoldingManager;
use dnj\Account\Tests\Models\User;
use dnj\Account\TransactionManager;
use dnj\Currency\Contracts\ICurrencyManager;
use dnj\Currency\CurrencyServiceProvider;
use dnj\UserLogger\ServiceProvider as UserLoggerServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        config()->set('account.user_model', User::class);
    }

    public function getAccountManager(): AccountManager
    {
        return $this->app->make(IAccountManager::class);
    }

    public function getTransactionManager(): TransactionManager
    {
        return $this->app->make(ITransactionManager::class);
    }

    public function getCurrencyManager(): ICurrencyManager
    {
        return $this->app->make(ICurrencyManager::class);
    }

    public function getHoldingManager(): HoldingManager
    {
        return $this->app->make(IHoldingManager::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            CurrencyServiceProvider::class,
            UserLoggerServiceProvider::class,
            AccountServiceProvider::class,
        ];
    }
}
