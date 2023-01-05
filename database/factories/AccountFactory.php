<?php

use dnj\Account\Models\Account;
use dnj\Account\Models\User;
use Faker\Generator;


$factory->define(Account::class, function (Generator $faker, $currencyId) {
	return [
		'title' => $faker->sentence(3),
		'user_id' =>User::all()->random()->first()->id,
		'balance' => $faker->numberBetween($min = 1500, $max = 6000),
		'currency_id' => $currencyId,
		'can_send' => true,
		'can_receive' => true,
		'status' => \dnj\Account\Contracts\AccountStatus::ACTIVE
	];
});