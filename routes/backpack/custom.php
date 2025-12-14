<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('user', 'UserCrudController');
    Route::crud('building', 'BuildingCrudController');
    Route::crud('bakong-account', 'BakongAccountCrudController');
    Route::crud('setting', 'SettingCrudController');
    Route::crud('role', 'RoleCrudController');
    Route::crud('room-type', 'RoomTypeCrudController');
    Route::crud('room', 'RoomCrudController');
    Route::crud('contract', 'ContractCrudController');
    Route::crud('service', 'ServiceCrudController');
    Route::crud('consumption', 'ConsumptionCrudController');
    Route::crud('transaction', 'TransactionCrudController');
    Route::crud('payment', 'PaymentCrudController');
    Route::crud('payment-item', 'PaymentItemCrudController');
    Route::crud('notification', 'NotificationCrudController');
    Route::crud('telegrambot', 'TelegrambotCrudController');
    Route::crud('tenant', 'TenantCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
