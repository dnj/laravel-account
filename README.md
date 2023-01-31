# Management user account and transaction inside laravel app

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]][link-license]
[![Testing status][ico-workflow-test]][link-workflow-test]
[![Open API][ico-openapi]][link-openapi]

## Introduction

The dnj/laravel-account package provides easy way to manage accounts and transactions of the users in your app. The Package stores all data in the accounts and transactions table.
* Latest versions of PHP and PHPUnit and PHPCsFixer
* Best practices applied:
    * [`README.md`][link-readme] (badges included)
    * [`LICENSE`][link-license]
    * [`composer.json`][link-composer-json]
    * [`phpunit.xml`][link-phpunit]
    * [`.gitignore`][link-gitignore]
    * [`.php-cs-fixer.php`][link-phpcsfixer]
    * [`Open Api 3`][link-openapi]
* Some useful resources to start coding


## Here's a demo of how you can use it:
```php
use dnj\Account\Contracts\IAccount;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\AccountStatus;
use dnj\Currency\Contracts\ICurrencyManager;

$currencyManager = app(ICurrencyManager::class);
$currency = $currencyManager->firstByCode("USD");

$accountManager = app(IAccountManager::class);

/**
 * @var IAccount $account
 */
$account = $accountManager->create(
    title: 'Profits',
    userId: Auth::user()->id,
    currencyId: $currency->getId(),
    status: AccountStatus::ACTIVE,
    canSend: true,
    canReceive: true,
    meta: ["key" => "value"],
    userActivityLog: true
);
```
## Installation
You can install the package via composer:
```bash
composer require dnj/laravel-account
```

The package will automatically register itself.


After this you can create required tables by running the migrations:

```bash
php artisan migrate
```

You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="dnj\Account\AccountServiceProvider" --tag="config"
```

Config file:
```php
return [
    // Define your user model class for connect accounts to users. Example: App\User:class
    'user_model' => null,
    
    // Enable http restful routes.
    'route_enable' => true,
    
    // Prefix of routes. By default routes register with /api/{prefix}/{accounts|transactions} pattern.
    'route_prefix' => null,
];
```

## Working With Accounts

Create new account:
```php
use dnj\Account\Contracts\IAccount;
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\AccountStatus;
use dnj\Currency\Contracts\ICurrencyManager;

$currencyManager = app(ICurrencyManager::class);
$currency = $currencyManager->firstByCode("USD");

$accountManager = app(IAccountManager::class);

/**
 * @var IAccount $account
 */
$account = $accountManager->create(
    title: 'Profits',
    userId: Auth::user()->id,
    currencyId: $currency->getId(),
    status: AccountStatus::ACTIVE,
    canSend: true,
    canReceive: true,
    meta: ["key" => "value"],
    userActivityLog: true
);

```

Update account:
```php
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\AccountStatus;

$accountManager = app(IAccountManager::class);
$account = $accountManager->update(
    accountId: 2,
    changes: array(
        'status' => AccountStatus::DEACTIVE,
    ),
    userActivityLog: true
);
```

Destroy account:
```php
use dnj\Account\Contracts\IAccountManager;

$accountManager = app(IAccountManager::class);
$accountManager->delete(
    accountId: $account->getId(),
    userActivityLog: true
);
```

***

## Working With Transactions

Create transaction:
```php
use dnj\Account\Contracts\IAccountManager;
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Contracts\ITransaction;
use dnj\Number\Number;

$accountManager = app(IAccountManager::class);

$profits = $accountManager->getByID(1);
$salary = $accountManager->getByID(2);

$transactionManager = app(ITransactionManager::class);

/**
 * @var ITransaction $transaction
 */
$transaction = $transactionManager->transfer(
    fromAccountId: $profits->getId(),
    toAccountId: $salary->getId(),
    amount: Number::fromInput(2501.55),
    meta: [
        'month' => '2023-01'
    ],
    force: false,
    userActivityLog: true
);

```


Update transaction:
```php
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Contracts\ITransaction;

$transactionManager = app(ITransactionManager::class);

/**
 * @var ITransaction $transaction
 */
$transaction = $transactionManager->update(
    transactionId: 55,
    meta: [
        'month' => '2023-01',
        'over-time' => 21
    ]
);
```

Rollback transaction:
```php
use dnj\Account\Contracts\ITransactionManager;
use dnj\Account\Contracts\ITransaction;

$transactionManager = app(ITransactionManager::class);

/**
 * @var ITransaction $rollbackTransaction new transaction that just made for rollback
 */
$rollbackTransaction = $transactionManager->rollback(55);
```

## How to use package API

A document in YAML format has been prepared for better familiarization and use of package web services. which is placed in the [`openapi.json`][link-openapi] file.

To use this file, you can import it on the [Swagger](link-swagger) site and see all available methods.


## Contribution

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are greatly appreciated.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement". Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request


## Testing
You can run unit tests with PHP Unit:

```bash
./vendor/bin/phpunit 
```
## About
We'll try to maintain this project as simple as possible, but Pull Requests are welcomed!

## License

The MIT License (MIT). Please see [License File][link-license] for more information.

[ico-version]: https://img.shields.io/packagist/v/dnj/laravel-account.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/dnj/laravel-account.svg?style=flat-square
[ico-workflow-test]: https://github.com/dnj/local-filesystem/actions/workflows/test.yaml/badge.svg
[ico-openapi]: https://img.shields.io/endpoint?color=blue&label=openAPI&logo=%22%236BA539%22&logoColor=blue&style=for-the-badge&url=https%3A%2F%2Fimg.shields.io%2Fendpoint%3Furl%3Dhttps%3A%2F%2Fgithub.com%2Fdnj%2Flaravel-account%2Fblob%2Fmaster%2FapiDocs%2Faccount.json

[link-openapi]: https://github.com/dnj/laravel-account/blob/master/openapi.json
[link-swagger]: https://petstore.swagger.io/?url=https://raw.githubusercontent.com/dnj/laravel-account/master/openapi.json
[link-workflow-test]: https://github.com/dnj/laravel-account/actions/workflows/test.yaml
[link-packagist]: https://packagist.org/packages/dnj/laravel-account
[link-license]: https://github.com/dnj/laravel-account/blob/master/LICENSE
[link-downloads]: https://packagist.org/packages/dnj/laravel-account
[link-readme]: https://github.com/dnj/laravel-account/blob/master/README.md
[link-composer-json]: https://github.com/dnj/laravel-account/blob/master/composer.json
[link-phpunit]: https://github.com/dnj/laravel-account/blob/master/phpunit.xml
[link-gitignore]: https://github.com/dnj/laravel-account/blob/master/.gitignore
[link-phpcsfixer]: https://github.com/dnj/laravel-account/blob/master/.php-cs-fixer.php
[link-author]: https://github.com/dnj
