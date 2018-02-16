<?php
/**
 * api.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'about', 'as' => 'api.v1.about.'],
    function () {

        // Accounts API routes:
        Route::get('', ['uses' => 'AboutController@about', 'as' => 'index']);
        Route::get('user', ['uses' => 'AboutController@user', 'as' => 'user']);
    }
);


Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'accounts', 'as' => 'api.v1.accounts.'],
    function () {

        // Accounts API routes:
        Route::get('', ['uses' => 'AccountController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'AccountController@store', 'as' => 'store']);
        Route::get('{account}', ['uses' => 'AccountController@show', 'as' => 'show']);
        Route::put('{account}', ['uses' => 'AccountController@update', 'as' => 'update']);
        Route::delete('{account}', ['uses' => 'AccountController@delete', 'as' => 'delete']);
    }
);


Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'bills', 'as' => 'api.v1.bills.'], function () {

    // Bills API routes:
    Route::get('', ['uses' => 'BillController@index', 'as' => 'index']);
    Route::post('', ['uses' => 'BillController@store', 'as' => 'store']);
    Route::get('{bill}', ['uses' => 'BillController@show', 'as' => 'show']);
    Route::put('{bill}', ['uses' => 'BillController@update', 'as' => 'update']);
    Route::delete('{bill}', ['uses' => 'BillController@delete', 'as' => 'delete']);
}
);


Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'transactions', 'as' => 'api.v1.transactions.'],
    function () {

        // Users API routes:
        Route::get('', ['uses' => 'TransactionController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'TransactionController@store', 'as' => 'store']);
        Route::get('{transaction}', ['uses' => 'TransactionController@show', 'as' => 'show']);
        Route::put('{transaction}', ['uses' => 'TransactionController@update', 'as' => 'update']);
        Route::delete('{transaction}', ['uses' => 'TransactionController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings', \FireflyIII\Http\Middleware\IsAdmin::class], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'users', 'as' => 'api.v1.users.'],
    function () {

        // Users API routes:
        Route::get('', ['uses' => 'UserController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'UserController@store', 'as' => 'store']);
        Route::get('{user}', ['uses' => 'UserController@show', 'as' => 'show']);
        Route::put('{user}', ['uses' => 'UserController@update', 'as' => 'update']);
        Route::delete('{user}', ['uses' => 'UserController@delete', 'as' => 'delete']);
    }
);