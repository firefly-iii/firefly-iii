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


use FireflyIII\Http\Middleware\IsAdmin;

/**
 * Autocomplete controllers
 */
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Autocomplete', 'prefix' => 'autocomplete',
     'as'        => 'api.v1.autocomplete.',],
    static function () {
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

/**
 * CHART ROUTES.
 */
// Accounts
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Chart', 'prefix' => 'chart/account',
     'as'        => 'api.v1.chart.account.',],
    static function () {
        Route::get('overview', ['uses' => 'AccountController@overview', 'as' => 'overview']);
    }
);

/**
 * DATA ROUTES
 */
// EXPORT
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Data\Export', 'prefix' => 'data/export',
     'as'        => 'api.v1.data.export.',],
    static function () {
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

/**
 * INSIGHTS ROUTES
 */

// Insight in expenses:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Insight\Expense', 'prefix' => 'insight/expense',
     'as'        => 'api.v1.insight.expense.',],
    static function () {
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


        // TODO per budget limit?
        // TODO per object group?
        // TODO per recurrence?
        // TODO per object group
        // TODO transfers voor piggies
        // TODO transfers per piggy?
        // TODO currency?
        // TODO net worth?
    }
);
// insight in income
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Insight\Income', 'prefix' => 'insight/income',
     'as'        => 'api.v1.insight.income.',],
    static function () {
        // Insight in expenses per account:
        Route::get('revenue', ['uses' => 'AccountController@revenue', 'as' => 'revenue']);
        Route::get('asset', ['uses' => 'AccountController@asset', 'as' => 'asset']);
        Route::get('total', ['uses' => 'PeriodController@total', 'as' => 'total']);
        Route::get('category', ['uses' => 'CategoryController@category', 'as' => 'category']);
        Route::get('no-category', ['uses' => 'CategoryController@noCategory', 'as' => 'no-category']);

        Route::get('tag', ['uses' => 'TagController@tag', 'as' => 'tag']);
        Route::get('no-tag', ['uses' => 'TagController@noTag', 'as' => 'no-tag']);

        // TODO per budget limit?
        // TODO per object group?
        // TODO per recurrence?
        // TODO per object group
        // TODO transfers voor piggies
        // TODO transfers per piggy?
        // TODO currency?
        // TODO net worth?
    }
);


// Insight in transfers
// TODO



/**
 * SUMMARY CONTROLLER
 */
// BASIC
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Summary', 'prefix' => 'summary',
     'as'        => 'api.v1.summary.',],
    static function () {
        Route::get('basic', ['uses' => 'BasicController@basic', 'as' => 'basic']);
    }
);

/**
 * MODELS
 */
// Accounts API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Account', 'prefix' => 'accounts',
     'as'        => 'api.v1.accounts.',],
    static function () {

        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{account}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{account}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{account}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{account}/piggy_banks', ['uses' => 'ListController@piggyBanks', 'as' => 'piggy_banks']);
        Route::get('{account}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
        Route::get('{account}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
    }
);

// Attachment API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Attachment', 'prefix' => 'attachments',
     'as'        => 'api.v1.attachments.',],
    static function () {

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
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Bill', 'prefix' => 'bills',
     'as'        => 'api.v1.bills.',],
    static function () {

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
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\AvailableBudget', 'prefix' => 'available_budgets',
     'as'        => 'api.v1.available_budgets.',],
    static function () {

        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{availableBudget}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{availableBudget}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{availableBudget}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);
    }
);

// Budget and Budget Limit API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models', 'prefix' => 'budgets',
     'as'        => 'api.v1.budgets.',],
    static function () {
        Route::get('', ['uses' => 'Budget\ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'Budget\StoreController@store', 'as' => 'store']);
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

// Category API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Category', 'prefix' => 'categories',
     'as'        => 'api.v1.categories.',],
    static function () {
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
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\ObjectGroup', 'prefix' => 'object_groups',
     'as'        => 'api.v1.object-groups.',],
    static function () {

        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::get('{objectGroup}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{objectGroup}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{objectGroup}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{objectGroup}/piggy_banks', ['uses' => 'ListController@piggyBanks', 'as' => 'piggy_banks']);
        Route::get('{objectGroup}/bills', ['uses' => 'ListController@bills', 'as' => 'bills']);
    }
);

// Piggy Bank API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\PiggyBank', 'prefix' => 'piggy_banks',
     'as'        => 'api.v1.piggy_banks.',],
    static function () {

        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{piggyBank}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{piggyBank}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{piggyBank}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{piggyBank}/events', ['uses' => 'ListController@piggyBankEvents', 'as' => 'events']);
        Route::get('{piggyBank}/attachments', ['uses' => 'ListController@attachments', 'as' => 'attachments']);
    }
);

// Recurrence API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Recurrence', 'prefix' => 'recurrences',
     'as'        => 'api.v1.recurrences.',],
    static function () {

        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{recurrence}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{recurrence}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{recurrence}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{recurrence}/transactions', ['uses' => 'ListController@transactions', 'as' => 'transactions']);
        // TODO
        Route::post('trigger', ['uses' => 'RecurrenceController@trigger', 'as' => 'trigger']);
    }
);

// Rules API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Rule', 'prefix' => 'rules',
     'as'        => 'api.v1.rules.',],
    static function () {

        Route::get('', ['uses' => 'ShowController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'StoreController@store', 'as' => 'store']);
        Route::get('{rule}', ['uses' => 'ShowController@show', 'as' => 'show']);
        Route::put('{rule}', ['uses' => 'UpdateController@update', 'as' => 'update']);
        Route::delete('{rule}', ['uses' => 'DestroyController@destroy', 'as' => 'delete']);

        Route::get('{rule}/test', ['uses' => 'TriggerController@testRule', 'as' => 'test']);
        // TODO give results back.
        Route::post('{rule}/trigger', ['uses' => 'TriggerController@triggerRule', 'as' => 'trigger']);
        // TODO rule transactions, rule bills?
    }
);

// Rules API routes:
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\RuleGroup', 'prefix' => 'rule_groups',
     'as'        => 'api.v1.rule_groups.',],
    static function () {

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






























/**
 * DATA CONTROLLERS
 *
 */

//// EXPORT
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers\Data\Export', 'prefix' => 'data/export',
//     'as'        => 'api.v1.data.export.',],
//    static function () {
//        Route::get('transactions', ['uses' => 'TransactionController@export', 'as' => 'transactions']);
//    }
//);

/**
 * INSIGHT CONTROLLERS
 */


/**
 * System and configuration controllers
 */

// TODO get rid of underscores.

// ABOUT FIREFLY III
// TODO VERIFY API DOCS
//Route::group(
//    [
//        'namespace' => 'FireflyIII\Api\V1\Controllers\System', 'prefix' => 'about',
//        'as'        => 'api.v1.about.'],
//    static function () {
//
//        // Accounts API routes:
//        Route::get('', ['uses' => 'AboutController@about', 'as' => 'index']);
//        Route::get('user', ['uses' => 'AboutController@user', 'as' => 'user']);
//    }
//);


// DYNAMIC CONFIGURATION (CHANGEABLE)
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers\System', 'prefix' => 'configuration/dynamic',
//     'as'        => 'api.v1.configuration.dynamic.',],
//    static function () {
//
//        // Configuration API routes:
//        Route::get('', ['uses' => 'DynamicConfigController@index', 'as' => 'index']);
//        Route::get('{dynamicConfigKey}', ['uses' => 'DynamicConfigController@show', 'as' => 'show']);
//        Route::post('{dynamicConfigKey}', ['uses' => 'DynamicConfigController@update', 'as' => 'update']);
//    }
//);

// STATIC CONFIGURATION (NOT CHANGEABLE)
// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers\System', 'prefix' => 'configuration/static',
//     'as'        => 'api.v1.configuration.static.',],
//    static function () {
//
//        // Configuration API routes:
//        Route::get('', ['uses' => 'StaticConfigController@index', 'as' => 'index']);
//        Route::get('{staticConfigKey}', ['uses' => 'StaticConfigController@show', 'as' => 'show']);
//    }
//);





//// TODO VERIFY API DOCS









//
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'cer',
//     'as'        => 'api.v1.cer.',],
//    static function () {
//
//        // Currency Exchange Rate API routes:
//        Route::get('', ['uses' => 'CurrencyExchangeRateController@index', 'as' => 'index']);
//    }
//);

//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'link_types',
//     'as'        => 'api.v1.link_types.',],
//    static function () {
//
//        // Link Type API routes:
//        Route::get('', ['uses' => 'LinkTypeController@index', 'as' => 'index']);
//        Route::post('', ['uses' => 'LinkTypeController@store', 'as' => 'store']);
//        Route::get('{linkType}', ['uses' => 'LinkTypeController@show', 'as' => 'show']);
//        Route::put('{linkType}', ['uses' => 'LinkTypeController@update', 'as' => 'update']);
//        Route::delete('{linkType}', ['uses' => 'LinkTypeController@delete', 'as' => 'delete']);
//        Route::get('{linkType}/transactions', ['uses' => 'LinkTypeController@transactions', 'as' => 'transactions']);
//    }
//);
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'transaction_links',
//     'as'        => 'api.v1.transaction_links.',],
//    static function () {
//
//        // Transaction Links API routes:
//        Route::get('', ['uses' => 'TransactionLinkController@index', 'as' => 'index']);
//        Route::post('', ['uses' => 'TransactionLinkController@store', 'as' => 'store']);
//        Route::get('{journalLink}', ['uses' => 'TransactionLinkController@show', 'as' => 'show']);
//        Route::put('{journalLink}', ['uses' => 'TransactionLinkController@update', 'as' => 'update']);
//        Route::delete('{journalLink}', ['uses' => 'TransactionLinkController@delete', 'as' => 'delete']);
//    }
//);

//
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'preferences',
//     'as'        => 'api.v1.preferences.',],
//    static function () {
//
//        // Preference API routes:
//        Route::get('', ['uses' => 'PreferenceController@index', 'as' => 'index']);
//        Route::get('date-ranges', ['uses' => 'Preferences\IndexController@dateRanges', 'as' => 'date-ranges']);
//        Route::get('{preference}', ['uses' => 'PreferenceController@show', 'as' => 'show']);
//        Route::put('{preference}', ['uses' => 'PreferenceController@update', 'as' => 'update']);
//    }
//);




//
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers\Search', 'prefix' => 'search',
//     'as'        => 'api.v1.search.',],
//    static function () {
//
//        // Attachment API routes:
//        Route::get('transactions', ['uses' => 'TransactionController@search', 'as' => 'transactions']);
//        Route::get('accounts', ['uses' => 'AccountController@search', 'as' => 'accounts']);
//    }
//);
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers\Webhook', 'prefix' => 'webhooks',
//     'as'        => 'api.v1.webhooks.',],
//    static function () {
//
//        // Webhook API routes:
//        Route::get('', ['uses' => 'IndexController@index', 'as' => 'index']);
//
//        // create new one.
//        Route::post('', ['uses' => 'CreateController@store', 'as' => 'store']);
//
//        // update
//        Route::put('{webhook}', ['uses' => 'EditController@update', 'as' => 'update']);
//        Route::delete('{webhook}', ['uses' => 'DeleteController@destroy', 'as' => 'destroy']);
//    }
//);
//
//
//// destroy data route.
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'data',
//     'as'        => 'api.v1.data.',],
//    static function () {
//
//        // Overview API routes:
//        Route::delete('destroy', ['uses' => 'Data\DestroyController@destroy', 'as' => 'destroy']);
//    }
//);
//
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'currencies',
//     'as'        => 'api.v1.currencies.',],
//    static function () {
//
//        // Transaction currency API routes:
//        Route::get('', ['uses' => 'CurrencyController@index', 'as' => 'index']);
//        Route::post('', ['uses' => 'CurrencyController@store', 'as' => 'store']);
//        Route::get('default', ['uses' => 'CurrencyController@showDefault', 'as' => 'show.default']);
//        Route::get('{currency_code}', ['uses' => 'CurrencyController@show', 'as' => 'show']);
//        Route::put('{currency_code}', ['uses' => 'CurrencyController@update', 'as' => 'update']);
//        Route::delete('{currency_code}', ['uses' => 'CurrencyController@delete', 'as' => 'delete']);
//
//        Route::post('{currency_code}/enable', ['uses' => 'CurrencyController@enable', 'as' => 'enable']);
//        Route::post('{currency_code}/disable', ['uses' => 'CurrencyController@disable', 'as' => 'disable']);
//        Route::post('{currency_code}/default', ['uses' => 'CurrencyController@makeDefault', 'as' => 'default']);
//
//        Route::get('{currency_code}/accounts', ['uses' => 'CurrencyController@accounts', 'as' => 'accounts']);
//        Route::get('{currency_code}/available_budgets', ['uses' => 'CurrencyController@availableBudgets', 'as' => 'available_budgets']);
//        Route::get('{currency_code}/bills', ['uses' => 'CurrencyController@bills', 'as' => 'bills']);
//        Route::get('{currency_code}/budget_limits', ['uses' => 'CurrencyController@budgetLimits', 'as' => 'budget_limits']);
//        Route::get('{currency_code}/cer', ['uses' => 'CurrencyController@cer', 'as' => 'cer']);
//        Route::get('{currency_code}/recurrences', ['uses' => 'CurrencyController@recurrences', 'as' => 'recurrences']);
//        Route::get('{currency_code}/rules', ['uses' => 'CurrencyController@rules', 'as' => 'rules']);
//        Route::get('{currency_code}/transactions', ['uses' => 'CurrencyController@transactions', 'as' => 'transactions']);
//    }
//);
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'tags',
//     'as'        => 'api.v1.tags.',],
//    static function () {
//        // Tag API routes:
//        Route::get('', ['uses' => 'TagController@index', 'as' => 'index']);
//        Route::post('', ['uses' => 'TagController@store', 'as' => 'store']);
//        Route::get('{tagOrId}', ['uses' => 'TagController@show', 'as' => 'show']);
//        Route::put('{tagOrId}', ['uses' => 'TagController@update', 'as' => 'update']);
//        Route::delete('{tagOrId}', ['uses' => 'TagController@delete', 'as' => 'delete']);
//
//        Route::get('{tagOrId}/transactions', ['uses' => 'TagController@transactions', 'as' => 'transactions']);
//        Route::get('{tagOrId}/attachments', ['uses' => 'TagController@attachments', 'as' => 'attachments']);
//    }
//);
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'tag-cloud',
//     'as'        => 'api.v1.tag-cloud.',],
//    static function () {
//        // Tag cloud API routes (to prevent collisions)
//        Route::get('', ['uses' => 'TagController@cloud', 'as' => 'cloud']);
//    }
//);
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'transactions',
//     'as'        => 'api.v1.transactions.',],
//    static function () {
//
//        // Transaction API routes:
//        Route::get('', ['uses' => 'Models\Transaction\ShowController@index', 'as' => 'index']);
//        Route::post('', ['uses' => 'Models\Transaction\StoreController@store', 'as' => 'store']);
//        Route::get('{transactionGroup}', ['uses' => 'Models\Transaction\ShowController@show', 'as' => 'show']);
//        Route::get('{transactionGroup}/attachments', ['uses' => 'Models\Transaction\ListController@attachments', 'as' => 'attachments']);
//        Route::get('{transactionGroup}/piggy_bank_events', ['uses' => 'Models\Transaction\ListController@piggyBankEvents', 'as' => 'piggy_bank_events']);
//        Route::get('{tj}/links', ['uses' => 'Models\Transaction\ListController@transactionLinks', 'as' => 'transaction_links']);
//        Route::put('{transactionGroup}', ['uses' => 'Models\Transaction\UpdateController@update', 'as' => 'update']);
//        Route::delete('{transactionGroup}/{tj}', ['uses' => 'Models\Transaction\DestroyController@destroyJournal', 'as' => 'delete-journal']);
//        Route::delete('{transactionGroup}', ['uses' => 'Models\Transaction\DestroyController@destroy', 'as' => 'delete']);
//    }
//);
//
//// special group for transaction journals
//// TODO VERIFY API DOCS
//Route::group(
//    ['namespace' => 'FireflyIII\Api\V1\Controllers\Models\Transaction', 'prefix' => 'transaction-journals',
//     'as'        => 'api.v1.journals.',],
//    static function () {
//
//        // Transaction API routes:
//        Route::get('{tj}', ['uses' => 'ShowController@showByJournal', 'as' => 'showByJournal']);
//    }
//);
//
//// TODO VERIFY API DOCS
//Route::group(
//    ['middleware' => ['auth:api', 'bindings', IsAdmin::class], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'users',
//     'as'         => 'api.v1.users.',],
//    static function () {
//
//        // Users API routes:
//        Route::get('', ['uses' => 'UserController@index', 'as' => 'index']);
//        Route::post('', ['uses' => 'UserController@store', 'as' => 'store']);
//        Route::get('{user}', ['uses' => 'UserController@show', 'as' => 'show']);
//        Route::put('{user}', ['uses' => 'UserController@update', 'as' => 'update']);
//        Route::delete('{user}', ['uses' => 'UserController@delete', 'as' => 'delete']);
//    }
//);
