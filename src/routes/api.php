<?php

use dnj\Account\Http\Controllers\AccountController;
use dnj\Account\Http\Controllers\TransactionController;

Route::resource('accounts', AccountController::class);
Route::resource('transactions', TransactionController::class);
