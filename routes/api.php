<?php

/**
 * api.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use FireflyIII\Api\V2\Controllers\JsonApi\AccountController;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\Relationships;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/*
 *
 * ____    ____  ___      .______        ______    __    __  .___________. _______     _______.
 * \   \  /   / |__ \     |   _  \      /  __  \  |  |  |  | |           ||   ____|   /       |
 *  \   \/   /     ) |    |  |_)  |    |  |  |  | |  |  |  | `---|  |----`|  |__     |   (----`
 *   \      /     / /     |      /     |  |  |  | |  |  |  |     |  |     |   __|     \   \
 *    \    /     / /_     |  |\  \----.|  `--'  | |  `--'  |     |  |     |  |____.----)   |
 *     \__/     |____|    | _| `._____| \______/   \______/      |__|     |_______|_______/
 */

// AUTOCOMPLETE ROUTES
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V2\Controllers\Autocomplete',
        'prefix'    => 'v2/autocomplete',
        'as'        => 'api.v2.autocomplete.',
    ],
    static function (): void {
        Route::get('accounts', ['uses' => 'AccountController@accounts', 'as' => 'accounts']);
        // Route::get('categories', ['uses' => 'CategoryController@categories', 'as' => 'categories']);
        // Route::get('tags', ['uses' => 'TagController@tags', 'as' => 'tags']);
        // Route::get('transaction-descriptions', ['uses' => 'TransactionController@transactionDescriptions', 'as' => 'transaction-descriptions']);
    }
);

// USER GROUP ROUTES
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V2\Controllers\UserGroup',
        'prefix'    => 'v2/user-groups',
        'as'        => 'api.v2.user-groups.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{userGroup}', ['uses' => 'ShowController@show', 'as' => 'show']);
        //        Route::put('{userGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        //        Route::post('{userGroup}/use', ['uses' => 'UpdateController@useUserGroup', 'as' => 'use']);
        //        Route::put('{userGroup}/update-membership', ['uses' => 'UpdateController@updateMembership', 'as' => 'updateMembership']);
        //        Route::delete('{userGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'destroy']);
    }
);

// CHART ROUTES
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V2\Controllers\Chart',
        'prefix'    => 'v2/chart',
        'as'        => 'api.v2.chart.',
    ],
    static function (): void {
        // Route::get('account/dashboard', ['uses' => 'AccountController@dashboard', 'as' => 'account.dashboard']);
        // Route::get('budget/dashboard', ['uses' => 'BudgetController@dashboard', 'as' => 'budget.dashboard']);
        // Route::get('category/dashboard', ['uses' => 'CategoryController@dashboard', 'as' => 'category.dashboard']);
        Route::get('balance/balance', ['uses' => 'BalanceController@balance', 'as' => 'balance.balance']);
    }
);

// CURRENCY ROUTES
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\TransactionCurrency',
        'prefix'    => 'v2/currencies',
        'as'        => 'api.v2.currencies.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
        Route::get('{currency_code}', ['uses' => 'ShowController@show', 'as' => 'show']);
//        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
//
        //        Route::put('{userGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        //        Route::post('{userGroup}/use', ['uses' => 'UpdateController@useUserGroup', 'as' => 'use']);
        //        Route::put('{userGroup}/update-membership', ['uses' => 'UpdateController@updateMembership', 'as' => 'updateMembership']);
        //        Route::delete('{userGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'destroy']);
    }
);

// exchange rates
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\ExchangeRate',
        'prefix'    => 'v2/exchange-rates',
        'as'        => 'api.v2.exchange-rates.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
        Route::get('{fromCurrencyCode}/{toCurrencyCode}', ['uses' => 'ShowController@show', 'as' => 'show']);
//        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
//
        //        Route::put('{userGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        //        Route::post('{userGroup}/use', ['uses' => 'UpdateController@useUserGroup', 'as' => 'use']);
        //        Route::put('{userGroup}/update-membership', ['uses' => 'UpdateController@updateMembership', 'as' => 'updateMembership']);
        //        Route::delete('{userGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'destroy']);
    }
);


// V2 API route for Summary boxes
// BASIC
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Summary',
//        'prefix'    => 'v2/summary',
//        'as'        => 'api.v2.summary.',
//    ],
//    static function (): void {
//        // Route::get('basic', ['uses' => 'BasicController@basic', 'as' => 'basic']);
//    }
//);
//// V2 API route for all kinds of Transaction lists.
//// A lot of endpoints involve transactions. So any time Firefly III needs to list transactions
//// it's coming from these endpoints.
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Transaction\List',
//        'prefix'    => 'v2',
//        'as'        => 'api.v2.',
//    ],
//    static function (): void {
//        // basic list
//        // Route::get('transactions', ['uses' => 'TransactionController@list', 'as' => 'transactions.list']);
//
//        // list by parent or related object.
//        // note how the check is done on the user group, not the user itself.
//        // Route::get('accounts/{userGroupAccount}/transactions', ['uses' => 'AccountController@list', 'as' => 'accounts.transactions']);
//    }
//);

// V2 API routes for auto complete
//
//// V2 API route for net worth endpoint(s);
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Summary',
//        'prefix'    => 'v2/net-worth',
//        'as'        => 'api.v2.net-worth.',
//    ],
//    static function (): void {
//        // Route::get('', ['uses' => 'NetWorthController@get', 'as' => 'index']);
//    }
//);
//
//// // V2 API route for accounts.
//// Route::group(
////    [
////        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\Account',
////        'prefix'    => 'v2/accounts',
////        'as'        => 'api.v2.accounts.',
////    ],
////    static function (): void {
////        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
////        Route::get('{account}', ['uses' => 'ShowController@show', 'as' => 'show']);
////        Route::put('{account}', ['uses' => 'UpdateController@update', 'as' => 'update']);
////    }
//// );
//
//// V2 API route for subscriptions.
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\Bill',
//        'prefix'    => 'v2/subscriptions',
//        'as'        => 'api.v2.subscriptions.',
//    ],
//    static function (): void {
//        //        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
//        //        Route::get('{userGroupBill}', ['uses' => 'ShowController@show', 'as' => 'show']);
//        //        Route::get('sum/paid', ['uses' => 'SumController@paid', 'as' => 'sum.paid']);
//        //        Route::get('sum/unpaid', ['uses' => 'SumController@unpaid', 'as' => 'sum.unpaid']);
//    }
//);
//
//// V2 API route for piggy banks.
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\PiggyBank',
//        'prefix'    => 'v2/piggy-banks',
//        'as'        => 'api.v2.piggy-banks.',
//    ],
//    static function (): void {
//        //        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
//    }
//);
//
//// V2 API route for transaction currencies
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\Currency',
//        'prefix'    => 'v2/currencies',
//        'as'        => 'api.v2.currencies.',
//    ],
//    static function (): void {
//        //        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
//    }
//);
//
//// V2 API route for transactions
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Model\Transaction',
//        'prefix'    => 'v2/transactions',
//        'as'        => 'api.v2.transactions.',
//    ],
//    static function (): void {
//        //        Route::post('', ['uses' => 'StoreController@post', 'as' => 'store']);
//        //        Route::get('{userGroupTransaction}', ['uses' => 'ShowController@show', 'as' => 'show']);
//        //        Route::put('{userGroupTransaction}', ['uses' => 'UpdateController@update', 'as' => 'update']);
//    }
//);
//// infinite (transactions) list:
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Transaction\List',
//        'prefix'    => 'v2/infinite/transactions',
//        'as'        => 'api.v2.infinite.transactions.',
//    ],
//    static function (): void {
//        //        Route::get('', ['uses' => 'TransactionController@infiniteList', 'as' => 'list']);
//    }
//);
//
//// V2 API route for budgets and budget limits:
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\Model',
//        'prefix'    => 'v2/budgets',
//        'as'        => 'api.v2.budgets',
//    ],
//    static function (): void {
//        //        Route::get('', ['uses' => 'Budget\IndexController@index', 'as' => 'index']);
//        //        Route::get('{budget}', ['uses' => 'Budget\ShowController@show', 'as' => 'show']);
//        //        Route::get('{budget}/limits', ['uses' => 'BudgetLimit\IndexController@index', 'as' => 'budget-limits.index']);
//        //        Route::get('sum/budgeted', ['uses' => 'Budget\SumController@budgeted', 'as' => 'sum.budgeted']);
//        //        Route::get('sum/spent', ['uses' => 'Budget\SumController@spent', 'as' => 'sum.spent']);
//        // Route::get('{budget}/budgeted', ['uses' => 'Budget\ShowController@budgeted', 'as' => 'budget.budgeted']);
//        // Route::get('{budget}/spent', ['uses' => 'Budget\ShowController@spent', 'as' => 'budget.spent']);
//    }
//);
//
//// V2 API route for system
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V2\Controllers\System',
//        'prefix'    => 'v2',
//        'as'        => 'api.v2.system.',
//    ],
//    static function (): void {
//        //        Route::get('preferences/{preference}', ['uses' => 'PreferencesController@get', 'as' => 'preferences.get']);
//    }
//);
//


// V2 JSON API ROUTES
// JsonApiRoute::server('v2')->prefix('v2')
//    ->resources(function (ResourceRegistrar $server): void {
//        // ACCOUNTS
//        $server->resource('accounts', AccountController::class)
//            ->relationships(function (Relationships $relations): void {
//                $relations->hasOne('user')->readOnly();
//            })
//        ;
//
//        // USERS
//        $server->resource('users', JsonApiController::class)->readOnly()->relationships(function (Relationships $relations): void {
//            $relations->hasMany('accounts')->readOnly();
//        });
//    })
// ;

/*
 * ____    ____  __     .______        ______    __    __  .___________. _______     _______.
 * \   \  /   / /_ |    |   _  \      /  __  \  |  |  |  | |           ||   ____|   /       |
 *  \   \/   /   | |    |  |_)  |    |  |  |  | |  |  |  | `---|  |----`|  |__     |   (----`
 *   \      /    | |    |      /     |  |  |  | |  |  |  |     |  |     |   __|     \   \
 *    \    /     | |    |  |\  \----.|  `--'  | |  `--'  |     |  |     |  |____.----)   |
 *     \__/      |_|    | _| `._____| \______/   \______/      |__|     |_______|_______/
 */

// Autocomplete controllers
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Autocomplete',
        'prefix'    => 'v1/autocomplete',
        'as'        => 'api.v1.autocomplete.',
    ],
    static function (): void {
        // Auto complete routes
        Route::get('accounts', ['uses' => 'AccountController@accounts', 'as' => 'accounts']);
        Route::get('bills', ['uses' => 'BillController@bills', 'as' => 'bills']);
        Route::get('budgets', ['uses' => 'BudgetController@budgets', 'as' => 'budgets']);
        Route::get('categories', ['uses' => 'CategoryController@categories', 'as' => 'categories']);
        Route::get('currencies', ['uses' => 'CurrencyController@currencies', 'as' => 'currencies']);
        Route::get('currencies-with-code', ['uses' => 'CurrencyController@currenciesWithCode', 'as' => 'currencies-with-code']);
        Route::get('object-groups', ['uses' => 'ObjectGroupController@objectGroups', 'as' => 'object-groups']);
        Route::get('piggy-banks', ['uses' => 'PiggyBankController@piggyBanks', 'as' => 'piggy-banks']);
        Route::get('piggy-banks-with-balance', ['uses' => 'PiggyBankController@piggyBanksWithBalance', 'as' => 'piggy-banks-with-balance']);
        Route::get('recurring', ['uses' => 'RecurrenceController@recurring', 'as' => 'recurring']);
        Route::get('rules', ['uses' => 'RuleController@rules', 'as' => 'rules']);
        Route::get('rule-groups', ['uses' => 'RuleGroupController@ruleGroups', 'as' => 'rule-groups']);
        Route::get('tags', ['uses' => 'TagController@tags', 'as' => 'tags']);
        Route::get('transactions', ['uses' => 'TransactionController@transactions', 'as' => 'transactions']);
        Route::get('transactions-with-id', ['uses' => 'TransactionController@transactionsWithID', 'as' => 'transactions-with-id']);
        Route::get('transaction-types', ['uses' => 'TransactionTypeController@transactionTypes', 'as' => 'transaction-types']);
    }
);

// CHART ROUTES.
// Chart accounts
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Chart',
        'prefix'    => 'v1/chart/account',
        'as'        => 'api.v1.chart.account.',
    ],
    static function (): void {
        Route::get('overview', ['uses' => 'AccountController@overview', 'as' => 'overview']);
    }
);

// DATA ROUTES
// Export data API routes
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Data\Export',
        'prefix'    => 'v1/data/export',
        'as'        => 'api.v1.data.export.',
    ],
    static function (): void {
        Route::get('accounts', ['uses' => 'ExportController@accounts', 'as' => 'accounts']);
        Route::get('bills', ['uses' => 'ExportController@bills', 'as' => 'bills']);
        Route::get('budgets', ['uses' => 'ExportController@budgets', 'as' => 'budgets']);
        Route::get('categories', ['uses' => 'ExportController@categories', 'as' => 'categories']);
        Route::get('piggy-banks', ['uses' => 'ExportController@piggyBanks', 'as' => 'piggy-banks']);
        Route::get('recurring', ['uses' => 'ExportController@recurring', 'as' => 'recurring']);
        Route::get('rules', ['uses' => 'ExportController@rules', 'as' => 'rules']);
        Route::get('tags', ['uses' => 'ExportController@tags', 'as' => 'tags']);
        Route::get('transactions', ['uses' => 'ExportController@transactions', 'as' => 'transactions']);
    }
);
// Destroy data API route
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Data',
        'prefix'    => 'v1/data/destroy',
        'as'        => 'api.v1.data.',
    ],
    static function (): void {
        Route::delete('', ['uses' => 'DestroyController@destroy', 'as' => 'destroy']);
    }
);
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Data',
        'prefix'    => 'v1/data/purge',
        'as'        => 'api.v1.data.',
    ],
    static function (): void {
        Route::delete('', ['uses' => 'PurgeController@purge', 'as' => 'purge']);
    }
);

// Bulk update API routes
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Data\Bulk',
        'prefix'    => 'v1/data/bulk',
        'as'        => 'api.v1.data.bulk.',
    ],
    static function (): void {
        Route::post('transactions', ['uses' => 'TransactionController@update', 'as' => 'transactions']);
    }
);

// INSIGHTS ROUTES

// Insight in expenses:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Insight\Expense',
        'prefix'    => 'v1/insight/expense',
        'as'        => 'api.v1.insight.expense.',
    ],
    static function (): void {
        // Insight in expenses per account:
        Route::get('expense', ['uses' => 'AccountController@expense', 'as' => 'expense']);
        Route::get('asset', ['uses' => 'AccountController@asset', 'as' => 'asset']);
        Route::get('total', ['uses' => 'PeriodController@total', 'as' => 'total']);
        Route::get('bill', ['uses' => 'BillController@bill', 'as' => 'bill']);
        Route::get('no-bill', ['uses' => 'BillController@noBill', 'as' => 'no-bill']);
        Route::get('budget', ['uses' => 'BudgetController@budget', 'as' => 'budget']);
        Route::get('no-budget', ['uses' => 'BudgetController@noBudget', 'as' => 'no-budget']);
        Route::get('category', ['uses' => 'CategoryController@category', 'as' => 'category']);
        Route::get('no-category', ['uses' => 'CategoryController@noCategory', 'as' => 'no-category']);
        Route::get('tag', ['uses' => 'TagController@tag', 'as' => 'tag']);
        Route::get('no-tag', ['uses' => 'TagController@noTag', 'as' => 'no-tag']);

        // TODO per object group, perhaps in the future.
        // TODO per recurrence, all transaction created under it.
        // TODO Per currency or as filter?
        // TODO Show user net worth
    }
);
// insight in income
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Insight\Income',
        'prefix'    => 'v1/insight/income',
        'as'        => 'api.v1.insight.income.',
    ],
    static function (): void {
        // Insight in expenses per account:
        Route::get('revenue', ['uses' => 'AccountController@revenue', 'as' => 'revenue']);
        Route::get('asset', ['uses' => 'AccountController@asset', 'as' => 'asset']);
        Route::get('total', ['uses' => 'PeriodController@total', 'as' => 'total']);
        Route::get('category', ['uses' => 'CategoryController@category', 'as' => 'category']);
        Route::get('no-category', ['uses' => 'CategoryController@noCategory', 'as' => 'no-category']);

        Route::get('tag', ['uses' => 'TagController@tag', 'as' => 'tag']);
        Route::get('no-tag', ['uses' => 'TagController@noTag', 'as' => 'no-tag']);

        // TODO per object group, maybe in the future
        // TODO Per recurrence, all transactions created under it.
        // TODO per currency or as a filter?
        // TODO show user net worth?
    }
);

// Insight in transfers
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Insight\Transfer',
        'prefix'    => 'v1/insight/transfer',
        'as'        => 'api.v1.insight.transfer.',
    ],
    static function (): void {
        // Insight in expenses per account:
        Route::get('asset', ['uses' => 'AccountController@asset', 'as' => 'asset']);
        Route::get('category', ['uses' => 'CategoryController@category', 'as' => 'category']);
        Route::get('no-category', ['uses' => 'CategoryController@noCategory', 'as' => 'no-category']);
        Route::get('tag', ['uses' => 'TagController@tag', 'as' => 'tag']);
        Route::get('no-tag', ['uses' => 'TagController@noTag', 'as' => 'no-tag']);
        Route::get('total', ['uses' => 'PeriodController@total', 'as' => 'total']);
        // TODO Transfers for piggies
    }
);
// SUMMARY CONTROLLER
// BASIC
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Summary',
        'prefix'    => 'v1/summary',
        'as'        => 'api.v1.summary.',
    ],
    static function (): void {
        Route::get('basic', ['uses' => 'BasicController@basic', 'as' => 'basic']);
    }
);

// MODELS
// Accounts API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Account',
        'prefix'    => 'v1/accounts',
        'as'        => 'api.v1.accounts.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{account}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{account}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{account}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{account}/piggy-banks', ['uses' => 'ListController@piggyBanks', 'as' => 'piggy-banks']);
        Route::get('{account}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
        Route::get('{account}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
    }
);

// Attachment API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Attachment',
        'prefix'    => 'v1/attachments',
        'as'        => 'api.v1.attachments.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{attachment}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::get('{attachment}/download', ['uses' => 'ShowController@download', 'as' => 'download']);
        Route::post('{attachment}/upload', ['uses' => 'StoreController@upload', 'as' => 'upload']);
        Route::put('{attachment}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{attachment}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);
    }
);

// Bills API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Bill',
        'prefix'    => 'v1/bills',
        'as'        => 'api.v1.bills.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{bill}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{bill}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{bill}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{bill}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
        Route::get('{bill}/rules', ['uses' => 'ListController@rules', 'as' => 'rules']);
        Route::get('{bill}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
    }
);

// Available Budget API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\AvailableBudget',
        'prefix'    => 'v1/available-budgets',
        'as'        => 'api.v1.available-budgets.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        // Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{availableBudget}', ['uses' => 'ShowController@show', 'as' => 'show']);
        // Route::put('{availableBudget}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        // Route::delete('{availableBudget}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);
    }
);

// Budget and Budget Limit API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models',
        'prefix'    => 'v1/budgets',
        'as'        => 'api.v1.budgets.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'Budget\ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'Budget\StoreController@store', 'as' => 'store']);
        Route::get('transactions-without-budget', ['uses' => 'Budget\ListController@withoutBudget', 'as' => 'without-budget']);
        Route::get('{budget}', ['uses' => 'Budget\ShowController@show', 'as' => 'show']);
        Route::put('{budget}', ['uses' => 'Budget\UpdateController@update', 'as' => 'update']);
        Route::delete('{budget}', ['uses' => 'Budget\DestroyController@destroy', 'as' => 'delete']);

        Route::get('{budget}/transactions', ['uses' => 'Budget\ListController@transactions', 'as' => 'transactions']);
        Route::get('{budget}/attachments', ['uses' => 'Budget\ListController@attachments', 'as' => 'attachments']);

        // limits:
        Route::get('{budget}/limits', ['uses' => 'BudgetLimit\ShowController@index', 'as' => 'limits.index']);
        Route::post('{budget}/limits', ['uses' => 'BudgetLimit\StoreController@store', 'as' => 'limits.store']);
        Route::get('{budget}/limits/{budgetLimit}', ['uses' => 'BudgetLimit\ShowController@show', 'as' => 'limits.show']);
        Route::put('{budget}/limits/{budgetLimit}', ['uses' => 'BudgetLimit\UpdateController@update', 'as' => 'limits.update']);
        Route::delete('{budget}/limits/{budgetLimit}', ['uses' => 'BudgetLimit\DestroyController@destroy', 'as' => 'limits.delete']);
        Route::get('{budget}/limits/{budgetLimit}/transactions', ['uses' => 'BudgetLimit\ListController@transactions', 'as' => 'limits.transactions']);
    }
);

// separate route for budget limits without referring to the budget.
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\BudgetLimit',
        'prefix'    => 'v1/budget-limits',
        'as'        => 'api.v1.budget-limits.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@indexAll', 'as' => 'index']);
    }
);

// Category API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Category',
        'prefix'    => 'v1/categories',
        'as'        => 'api.v1.categories.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{category}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{category}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{category}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{category}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
        Route::get('{category}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
    }
);

// Object Group API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\ObjectGroup',
        'prefix'    => 'v1/object-groups',
        'as'        => 'api.v1.object-groups.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::get('{objectGroup}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{objectGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{objectGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{objectGroup}/piggy-banks', ['uses' => 'ListController@piggyBanks', 'as' => 'piggy-banks']);
        Route::get('{objectGroup}/bills', ['uses' => 'ListController@bills', 'as' => 'bills']);
    }
);

// Piggy Bank API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\PiggyBank',
        'prefix'    => 'v1/piggy-banks',
        'as'        => 'api.v1.piggy-banks.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{piggyBank}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{piggyBank}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{piggyBank}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{piggyBank}/events', ['uses' => 'ListController@piggyBankEvents', 'as' => 'events']);
        Route::get('{piggyBank}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
        Route::get('{piggyBank}/accounts', ['uses' => 'ListController@accounts', 'as' => 'accounts']);
    }
);

// Recurrence API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Recurrence',
        'prefix'    => 'v1/recurrences',
        'as'        => 'api.v1.recurrences.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{recurrence}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{recurrence}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{recurrence}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{recurrence}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);

        // controller does not exist:
        // Route::post('trigger', ['uses' => 'RecurrenceController@trigger', 'as' => 'trigger']);
    }
);

// Rules API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Rule',
        'prefix'    => 'v1/rules',
        'as'        => 'api.v1.rules.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('validate-expression', ['uses' => 'ExpressionController@validateExpression', 'as' => 'validate']);
        Route::get('{rule}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{rule}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{rule}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{rule}/test', ['uses' => 'TriggerController@testRule', 'as' => 'test']);
        // TODO give results back
        Route::post('{rule}/trigger', ['uses' => 'TriggerController@triggerRule', 'as' => 'trigger']);
        // TODO rule transactions, rule bills?
    }
);

// Rules API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\RuleGroup',
        'prefix'    => 'v1/rule-groups',
        'as'        => 'api.v1.rule-groups.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{ruleGroup}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{ruleGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{ruleGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);
        Route::get('{ruleGroup}/test', ['uses' => 'TriggerController@testGroup', 'as' => 'test']);
        Route::post('{ruleGroup}/trigger', ['uses' => 'TriggerController@triggerGroup', 'as' => 'trigger']);

        Route::get('{ruleGroup}/rules', ['uses' => 'ListController@rules', 'as' => 'rules']);
    }
);

// Tag API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Tag',
        'prefix'    => 'v1/tags',
        'as'        => 'api.v1.tags.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{tagOrId}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{tagOrId}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{tagOrId}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{tagOrId}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
        Route::get('{tagOrId}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
    }
);
// Transaction API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Transaction',
        'prefix'    => 'v1/transactions',
        'as'        => 'api.v1.transactions.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{transactionGroup}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{transactionGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{transactionGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{transactionGroup}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
        Route::get('{transactionGroup}/piggy-bank-events', ['uses' => 'ListController@piggyBankEvents', 'as' => 'piggy-bank-events']);
    }
);

Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\Transaction',
        'prefix'    => 'v1/transaction-journals',
        'as'        => 'api.v1.transaction-journals.',
    ],
    static function (): void {
        Route::get('{tj}', ['uses' => 'ShowController@showJournal', 'as' => 'show']);
        Route::delete('{tj}', ['uses' => 'DestroyController@destroyJournal', 'as' => 'delete']);

        Route::get('{tj}/links', ['uses' => 'ListController@transactionLinks', 'as' => 'transaction-links']);
    }
);

// Transaction currency API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\TransactionCurrency',
        'prefix'    => 'v1/currencies',
        'as'        => 'api.v1.currencies.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('default', ['uses' => 'ShowController@showDefault', 'as' => 'show.default']);
        Route::get('{currency_code}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{currency_code}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{currency_code}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::post('{currency_code}/enable', ['uses' => 'UpdateController@enable', 'as' => 'enable']);
        Route::post('{currency_code}/disable', ['uses' => 'UpdateController@disable', 'as' => 'disable']);
        Route::post('{currency_code}/default', ['uses' => 'UpdateController@makeDefault', 'as' => 'default']);

        Route::get('{currency_code}/accounts', ['uses' => 'ListController@accounts', 'as' => 'accounts']);
        Route::get('{currency_code}/available-budgets', ['uses' => 'ListController@availableBudgets', 'as' => 'available-budgets']);
        Route::get('{currency_code}/bills', ['uses' => 'ListController@bills', 'as' => 'bills']);
        Route::get('{currency_code}/budget-limits', ['uses' => 'ListController@budgetLimits', 'as' => 'budget-limits']);
        Route::get('{currency_code}/cer', ['uses' => 'ListController@cer', 'as' => 'cer']);
        Route::get('{currency_code}/recurrences', ['uses' => 'ListController@recurrences', 'as' => 'recurrences']);
        Route::get('{currency_code}/rules', ['uses' => 'ListController@rules', 'as' => 'rules']);
        Route::get('{currency_code}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
    }
);

// Transaction Links API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\TransactionLink',
        'prefix'    => 'v1/transaction-links',
        'as'        => 'api.v1.transaction-links.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{journalLink}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{journalLink}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{journalLink}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);
    }
);

// Transaction Link Type API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Models\TransactionLinkType',
        'prefix'    => 'v1/link-types',
        'as'        => 'api.v1.link-types.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{linkType}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{linkType}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{linkType}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);
        Route::get('{linkType}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
    }
);

// SEARCH ENDPOINTS
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Search',
        'prefix'    => 'v1/search',
        'as'        => 'api.v1.search.',
    ],
    static function (): void {
        Route::get('transactions', ['uses' => 'TransactionController@search', 'as' => 'transactions']);
        Route::get('accounts', ['uses' => 'AccountController@search', 'as' => 'accounts']);
    }
);

// SYSTEM END POINTS
// About Firefly III API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\System',
        'prefix'    => 'v1/about',
        'as'        => 'api.v1.about.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'AboutController@about', 'as' => 'index']);
        Route::get('user', ['uses' => 'AboutController@user', 'as' => 'user']);
    }
);
// Configuration API routes
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\System',
        'prefix'    => 'v1/configuration',
        'as'        => 'api.v1.configuration.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ConfigurationController@index', 'as' => 'index']);
        Route::get('{eitherConfigKey}', ['uses' => 'ConfigurationController@show', 'as' => 'show']);
        Route::put('{dynamicConfigKey}', ['uses' => 'ConfigurationController@update', 'as' => 'update']);
    }
);
// Users API routes:
Route::group(
    [
        'middleware' => ['auth:api,sanctum', 'bindings'],
        'namespace'  => 'FireflyIII\Api\V1\Controllers\System',
        'prefix'     => 'v1/users',
        'as'         => 'api.v1.users.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'UserController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'UserController@store', 'as' => 'store']);
        Route::get('{user}', ['uses' => 'UserController@show', 'as' => 'show']);
        Route::put('{user}', ['uses' => 'UserController@update', 'as' => 'update']);
        Route::delete('{user}', ['uses' => 'UserController@destroy', 'as' => 'delete']);
    }
);

// USER

// Preference API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\User',
        'prefix'    => 'v1/preferences',
        'as'        => 'api.v1.preferences.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'PreferencesController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'PreferencesController@store', 'as' => 'store']);
        // Route::get('{preferenceList}', ['uses' => 'PreferencesController@showList', 'as' => 'show-list'])->where('preferenceList', ',+');
        Route::get('{preference}', ['uses' => 'PreferencesController@show', 'as' => 'show']);
        Route::put('{preference}', ['uses' => 'PreferencesController@update', 'as' => 'update']);
    }
);

// Webhook API routes:
Route::group(
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers\Webhook',
        'prefix'    => 'v1/webhooks',
        'as'        => 'api.v1.webhooks.',
    ],
    static function (): void {
        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{webhook}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{webhook}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::post('{webhook}/submit', ['uses' => 'SubmitController@submit', 'as' => 'submit']);
        Route::post('{webhook}/trigger-transaction/{transactionGroup}', ['uses' => 'ShowController@triggerTransaction', 'as' => 'trigger-transaction']);
        Route::delete('{webhook}', ['uses' => 'DestroyController@destroy', 'as' => 'destroy']);

        // webhook messages
        Route::get('{webhook}/messages', ['uses' => 'MessageController@index', 'as' => 'messages.index']);
        Route::get('{webhook}/messages/{webhookMessage}', ['uses' => 'MessageController@show', 'as' => 'messages.show']);
        Route::delete('{webhook}/messages/{webhookMessage}', ['uses' => 'DestroyController@destroyMessage', 'as' => 'messages.destroy']);

        // webhook message attempts
        Route::get('{webhook}/messages/{webhookMessage}/attempts', ['uses' => 'AttemptController@index', 'as' => 'attempts.index']);
        Route::get('{webhook}/messages/{webhookMessage}/attempts/{webhookAttempt}', ['uses' => 'AttemptController@show', 'as' => 'attempts.show']);
        Route::delete(
            '{webhook}/messages/{webhookMessage}/attempts/{webhookAttempt}',
            ['uses' => 'DestroyController@destroyAttempt', 'as' => 'attempts.destroy']
        );
    }
);
