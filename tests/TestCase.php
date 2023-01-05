<?php

namespace dnj\Account\Tests;

use dnj\Account\AccountManager;
use dnj\Account\AccountServiceProvider;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\IHoldingManager;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\HoldingManager;
use dnj\Account\Models\Account;
use dnj\Account\Models\User;
use dnj\Account\TransactionManager;
use dnj\Currency\Contracts\ICurrency;
use dnj\Currency\Contracts\ICurrencyManager;
use dnj\Currency\Contracts\RoundingBehaviour;
use dnj\Currency\CurrencyServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends \Orchestra\Testbench\TestCase {
	use RefreshDatabase;
	
	public function setUp (): void {
		parent::setUp();
		$this->withFactories(__DIR__ . '/../database/factories');
	}
	
	protected function defineDatabaseMigrations () {
		$this->loadLaravelMigrations([ '--database' => 'testing' ]);
		$this->artisan('migrate' , [ '--database' => 'testing' ])
			 ->run();
	}
	
	protected function getPackageProviders ( $app ) {
		return [
			CurrencyServiceProvider::class ,
			AccountServiceProvider::class ,
		];
	}
	
	public function getAccountManager (): AccountManager {
		return $this->app->make(IAccountManager::class);
	}
	
	public function getTransactionManager (): TransactionManager {
		return $this->app->make(ITransactionManager::class);
	}
	
	public function getCurrencyManager (): ICurrencyManager {
		return $this->app->make(ICurrencyManager::class);
	}
	
	public function getHoldingManager (): HoldingManager {
		return $this->app->make(IHoldingManager::class);
	}
	
	public function createUSD (): ICurrency {
		return $this->getCurrencyManager()
					->create('USD' , 'US Dollar' , '$' , '' , RoundingBehaviour::CEIL , 2);
	}
	
	public function createEUR (): ICurrency {
		return $this->getCurrencyManager()
					->create('USD' , 'US Dollar' , '$' , '' , RoundingBehaviour::CEIL , 2);
	}
	
	public function createUSDAccount ( ICurrency $USD , ?int $userId = null ): Account {
		return $this->getAccountManager()
					->create('USD Reserve' , $USD->getID() , $userId);
	}
	
	public function createEURAccount ( ICurrency $EUR , ?int $userId = null ): Account {
		return $this->getAccountManager()
					->create('EUR Reserve' , $EUR->getID() , $userId);
	}
	
	public function getRoute ( string $route ) {
		if ( config('account.route_prefix') != null ) {
			$route = config('account.route_prefix') . '/' . $route;
		}
		
		return $route;
	}
	
	public function createNewUser () {
		$user = factory(User::class)->create();
		
		return $user;
	}
	
	public function createAccount ($count = 1) {
		$USD = $this->createUSD();
		$account = factory(Account::class,$count)->create([
													   'currency_id' => $USD->id ,
												   ]);
		
		return $account;
	}
}
