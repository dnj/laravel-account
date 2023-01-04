<?php
Route::controller(\dnj\Account\Http\Controllers\AccountController::class)
	 ->group(function () {
		 Route::post('accounts' , 'create');
		 Route::get('accounts' , 'filter');
		 Route::put('accounts/{accountId}' , 'update');
		 Route::delete('accounts/{accountId}' , 'destroy');
	 });
Route::controller(\dnj\Account\Http\Controllers\TransactionController::class)
	 ->prefix('transaction')
	 ->group(function () {
		 Route::post('transfer' , 'transfer');
		 Route::post('update' , 'update');
		 Route::post('rollback' , 'transactionRollBack');
	 });