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
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'attachments', 'as' => 'api.v1.attachments.'],
    function () {

        // Attachment API routes:
        Route::get('', ['uses' => 'AttachmentController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'AttachmentController@store', 'as' => 'store']);
        Route::get('{attachment}', ['uses' => 'AttachmentController@show', 'as' => 'show']);
        Route::get('{attachment}/download', ['uses' => 'AttachmentController@download', 'as' => 'download']);
        Route::post('{attachment}/upload', ['uses' => 'AttachmentController@upload', 'as' => 'upload']);
        Route::put('{attachment}', ['uses' => 'AttachmentController@update', 'as' => 'update']);
        Route::delete('{attachment}', ['uses' => 'AttachmentController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'available_budgets',
     'as'         => 'api.v1.available_budgets.'],
    function () {

        // Available Budget API routes:
        Route::get('', ['uses' => 'AvailableBudgetController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'AvailableBudgetController@store', 'as' => 'store']);
        Route::get('{availableBudget}', ['uses' => 'AvailableBudgetController@show', 'as' => 'show']);
        Route::put('{availableBudget}', ['uses' => 'AvailableBudgetController@update', 'as' => 'update']);
        Route::delete('{availableBudget}', ['uses' => 'AvailableBudgetController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'budget_limits', 'as' => 'api.v1.budget_limits.'],
    function () {

        // Budget Limit API routes:
        Route::get('', ['uses' => 'BudgetLimitController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'BudgetLimitController@store', 'as' => 'store']);
        Route::get('{budgetLimit}', ['uses' => 'BudgetLimitController@show', 'as' => 'show']);
        Route::put('{budgetLimit}', ['uses' => 'BudgetLimitController@update', 'as' => 'update']);
        Route::delete('{budgetLimit}', ['uses' => 'BudgetLimitController@delete', 'as' => 'delete']);
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
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'budgets', 'as' => 'api.v1.budgets.'],
    function () {

        // Budget API routes:
        Route::get('', ['uses' => 'BudgetController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'BudgetController@store', 'as' => 'store']);
        Route::get('{budget}', ['uses' => 'BudgetController@show', 'as' => 'show']);
        Route::put('{budget}', ['uses' => 'BudgetController@update', 'as' => 'update']);
        Route::delete('{budget}', ['uses' => 'BudgetController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'categories', 'as' => 'api.v1.categories.'],
    function () {

        // Category API routes:
        Route::get('', ['uses' => 'CategoryController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'CategoryController@store', 'as' => 'store']);
        Route::get('{category}', ['uses' => 'CategoryController@show', 'as' => 'show']);
        Route::put('{category}', ['uses' => 'CategoryController@update', 'as' => 'update']);
        Route::delete('{category}', ['uses' => 'CategoryController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'configuration', 'as' => 'api.v1.configuration.'],
    function () {

        // Configuration API routes:
        Route::get('', ['uses' => 'ConfigurationController@index', 'as' => 'index']);
        Route::put('', ['uses' => 'ConfigurationController@update', 'as' => 'update']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'cer', 'as' => 'api.v1.cer.'],
    function () {

        // Currency Exchange Rate API routes:
        Route::get('', ['uses' => 'CurrencyExchangeRateController@index', 'as' => 'index']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'currencies', 'as' => 'api.v1.currencies.'],
    function () {

        // Transaction currency API routes:
        Route::get('', ['uses' => 'CurrencyController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'CurrencyController@store', 'as' => 'store']);
        Route::get('{currency}', ['uses' => 'CurrencyController@show', 'as' => 'show']);
        Route::put('{currency}', ['uses' => 'CurrencyController@update', 'as' => 'update']);
        Route::delete('{currency}', ['uses' => 'CurrencyController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['middleware' => ['auth:api', 'bindings'], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'transactions', 'as' => 'api.v1.transactions.'],
    function () {

        // Transaction API routes:
        Route::get('', ['uses' => 'TransactionController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'TransactionController@store', 'as' => 'store']);
        Route::get('{transaction}', ['uses' => 'TransactionController@show', 'as' => 'show']);
        Route::put('{transaction}', ['uses' => 'TransactionController@update', 'as' => 'update']);
        Route::delete('{transaction}', ['uses' => 'TransactionController@delete', 'as' => 'delete']);
    }
);


Route::group(
    ['middleware' => ['auth:api', 'bindings', \FireflyIII\Http\Middleware\IsAdmin::class], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'users',
     'as'         => 'api.v1.users.'],
    function () {

        // Users API routes:
        Route::get('', ['uses' => 'UserController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'UserController@store', 'as' => 'store']);
        Route::get('{user}', ['uses' => 'UserController@show', 'as' => 'show']);
        Route::put('{user}', ['uses' => 'UserController@update', 'as' => 'update']);
        Route::delete('{user}', ['uses' => 'UserController@delete', 'as' => 'delete']);
    }
);