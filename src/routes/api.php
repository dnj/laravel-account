<?php
use dnj\Account\Http\Controllers\AccountController;
use dnj\Account\Http\Controllers\TransactionController;

Route::resource('accounts',AccountController::class);
Route::post('transaction/rollback/{transaction}',[TransactionController::class,'transactionRollBack'])->name('transaction.rollback');
Route::resource('transaction',TransactionController::class);