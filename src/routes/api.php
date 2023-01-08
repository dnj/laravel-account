<?php
use dnj\Account\Http\Controllers\AccountController;
use dnj\Account\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth')->group(function () {
	Route::apiResources([
		'accounts' => AccountController::class,
		'transactions' => TransactionController::class,
	]);
});