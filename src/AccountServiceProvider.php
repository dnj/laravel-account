<?php

namespace dnj\Account;

use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\IHoldingManager;
use dnj\Account\Contracts\ITransactionManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AccountServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/account.php', 'account');
        $this->app->singleton(IAccountManager::class, AccountManager::class);
        $this->app->singleton(ITransactionManager::class, TransactionManager::class);
        $this->app->singleton(IHoldingManager::class, HoldingManager::class);
    }

    public function boot()
    {
        $this->loadRoutes();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/account.php' => config_path('account.php'),
            ], 'config');
        }
    }

    private function loadRoutes()
    {
        if (!config('account.route_enable')) {
            return;
        }
        Route::prefix(config('account.route_prefix'))->group(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
