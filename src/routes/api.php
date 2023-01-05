<?php
use dnj\Account\Http\Controllers\AccountController;

Route::resource('accounts',AccountController::class);
Route::controller(\dnj\Account\Http\Controllers\TransactionController::class)
	 ->prefix('transaction')
	 ->group(function () {
		 Route::post('transfer' , 'transfer');
		 Route::put('transfer/{transactionId}' , 'update');
		 Route::put('transfer/rollback/{transactionId}' , 'transactionRollBack');
	 });