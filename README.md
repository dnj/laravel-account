# Management user account and transaction inside laravel app

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]][link-license]
[![Testing status][ico-workflow-test]][link-workflow-test]
[![Open API][ico-open-api]][link-open-api]

## Introduction

The  dnj/laravel-account Public package provides easy way to manage  account and transaction of the users of your app. The Package stores all data in the accounts and transactions table.
* Latest versions of PHP and PHPUnit and PHPCsFixer
* Best practices applied:
    * [`README.md`][link-readme] (badges included)
    * [`LICENSE`][link-license]
    * [`composer.json`][link-composer-json]
    * [`phpunit.xml`][link-phpunit]
    * [`.gitignore`][link-gitignore]
    * [`.php-cs-fixer.php`][link-phpcsfixer]
    * [`openAPI`][link-phpcsfixer]
* Some useful resources to start coding


## Here's a demo of how you can use it:
```php
$account = new AccountManager();
$account->create(
    'this is a firest account',
    $currency_id,
    Auth::user()->id,
    AccountStatus::ACTIVE,
    false,
    true,
    ["key" => "value"]
);
```
## Installation
You can install the package via composer:
```bash
composer require dnj/laravel-account
```

The package will automatically register itself.


After this you can create the `accounts and transactions` table by running the migrations:

```bash
php artisan migrate
```

You can optionally publish the config file with:

```bash
php artisan vendor:publish --provider="dnj\laravel-account\AccountServiceProvider" --tag="config"
```

### Account usage:

Create new account:
```php
<?php
use dnj\Account\AccountManager;
$account = new AccountManager();
$account->create(
    'this is a firest account',
    $currency_id,
    Auth::user()->id,
    AccountStatus::ACTIVE,
    false,
    true,
    ["key" => "value"]
);
```

Update account:
```php
<?php
use dnj\Account\AccountManager;
$account = new AccountManager();
$data = $request->validated();
$changes = [];
foreach ($data as $key => $value) {
    $changes[Str::camel($key)] = $value;
}
accountManager->update($account->id, $changes);
```

Destroy account:
```php
<?php
use dnj\Account\AccountManager;
$account = new AccountManager();
accountManager->delete($account->id);
```
### Transaction usage:
Create transaction:
```php
<?php
use dnj\Account\TransactionManager;
$transactionManager = new TransactionManager();
$data['amount'] = Number::formString($data['amount']);
$transaction = $transactionManager->transfer(
    $data['from_id'],
    $data['to_id'],
    $data['amount'],
    $data['meta'] ?? null,
    $data['force'] ?? false,
);
```
Update transaction:
```php
<?php
use dnj\Account\TransactionManager;
$transactionManager = new TransactionManager();
$meta = [
    [
        "key" => 'value'		
    ]
];
$transaction = $transactionManager->update(
$transaction->id,
$meta);
```

Rollback transaction:
```php
<?php
use dnj\Account\TransactionManager;
$transactionManager = new TransactionManager();
transactionManager->rollback($transaction->id);
```

## About
We'll try to maintain this project as simple as possible, but Pull Requests are welcomed!

## License

The MIT License (MIT). Please see [License File][link-license] for more information.

[ico-version]: https://img.shields.io/packagist/v/dnj/laravel-account.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/dnj/laravel-account.svg?style=flat-square
[ico-workflow-test]: https://github.com/dnj/local-filesystem/actions/workflows/test.yaml/badge.svg
[ico-open-api]: https://img.shields.io/endpoint?color=blue&label=openAPI&logo=%22%236BA539%22&logoColor=blue&style=for-the-badge&url=https%3A%2F%2Fimg.shields.io%2Fendpoint%3Furl%3Dhttps%3A%2F%2Fgithub.com%2Fdnj%2Flaravel-account%2Fblob%2Fmaster%2FapiDocs%2Faccount.json

[link-open-api]: https://github.com/dnj/laravel-account/blob/master/apiDocs/account.json
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
