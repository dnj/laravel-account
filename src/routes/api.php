<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::controller(\dnj\Account\Http\Controllers\AccountController::class)
	 ->group(function () {
		 Route::post('create' , 'create');
		 Route::post('update' , 'update');
		 Route::post('destroy' , 'destroy');
		 Route::post('filter' , 'filter');
	 });
Route::controller(\dnj\Account\Http\Controllers\TransactionController::class)
	 ->prefix('transaction')
	 ->group(function () {
		 Route::post('transfer' , 'transfer');
		 Route::post('update' , 'update');
		 Route::post('rollback' , 'transactionRollBack');
	 });