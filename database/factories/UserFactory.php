<?php

use dnj\Account\Models\User;

$factory->define(User::class, function (Faker\Generator $faker) {
	return [
		'name' => $faker->name(),
		'email' => $faker->unique()->safeEmail(),
		'email_verified_at' => now(),
		'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
		'remember_token' => 'null'
	];
});