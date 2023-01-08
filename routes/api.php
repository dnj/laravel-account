<?php

use dnj\Account\Http\Controllers\AccountController;
use dnj\Account\Http\Controllers\TransactionController;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'api', SubstituteBindings::class])->group(function () {
    Route::apiResources([
        'accounts' => AccountController::class,
        'transactions' => TransactionController::class,
    ]);
});
