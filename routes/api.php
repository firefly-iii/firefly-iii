<?php

/**
 * api.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
    [
        'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'about',
        'as'        => 'api.v1.about.'],
    static function () {

        // Accounts API routes:
        Route::get('', ['uses' => 'AboutController@about', 'as' => 'index']);
        Route::get('user', ['uses' => 'AboutController@user', 'as' => 'user']);
    }
);


Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'accounts',
     'as'         => 'api.v1.accounts.'],
    static function () {

        // Accounts API routes:
        Route::get('', ['uses' => 'AccountController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'AccountController@store', 'as' => 'store']);
        Route::get('{account}', ['uses' => 'AccountController@show', 'as' => 'show']);
        Route::put('{account}', ['uses' => 'AccountController@update', 'as' => 'update']);
        Route::delete('{account}', ['uses' => 'AccountController@delete', 'as' => 'delete']);

        Route::get('{account}/piggy_banks', ['uses' => 'AccountController@piggyBanks', 'as' => 'piggy_banks']);
        Route::get('{account}/transactions', ['uses' => 'AccountController@transactions', 'as' => 'transactions']);

    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'attachments',
     'as'         => 'api.v1.attachments.'],
    static function () {

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
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'available_budgets',
     'as'         => 'api.v1.available_budgets.'],
    static function () {

        // Available Budget API routes:
        Route::get('', ['uses' => 'AvailableBudgetController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'AvailableBudgetController@store', 'as' => 'store']);
        Route::get('{availableBudget}', ['uses' => 'AvailableBudgetController@show', 'as' => 'show']);
        Route::put('{availableBudget}', ['uses' => 'AvailableBudgetController@update', 'as' => 'update']);
        Route::delete('{availableBudget}', ['uses' => 'AvailableBudgetController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'bills',
     'as'         => 'api.v1.bills.'], static function () {

    // Bills API routes:
    Route::get('', ['uses' => 'BillController@index', 'as' => 'index']);
    Route::post('', ['uses' => 'BillController@store', 'as' => 'store']);
    Route::get('{bill}', ['uses' => 'BillController@show', 'as' => 'show']);
    Route::put('{bill}', ['uses' => 'BillController@update', 'as' => 'update']);
    Route::delete('{bill}', ['uses' => 'BillController@delete', 'as' => 'delete']);

    Route::get('{bill}/attachments', ['uses' => 'BillController@attachments', 'as' => 'attachments']);
    Route::get('{bill}/rules', ['uses' => 'BillController@rules', 'as' => 'rules']);
    Route::get('{bill}/transactions', ['uses' => 'BillController@transactions', 'as' => 'transactions']);
}
);


Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'budgets/limits',
     'as'         => 'api.v1.budget_limits.'],
    static function () {

        // Budget Limit API routes:
        Route::get('', ['uses' => 'BudgetLimitController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'BudgetLimitController@store', 'as' => 'store']);
        Route::get('{budgetLimit}', ['uses' => 'BudgetLimitController@show', 'as' => 'show']);
        Route::put('{budgetLimit}', ['uses' => 'BudgetLimitController@update', 'as' => 'update']);
        Route::delete('{budgetLimit}', ['uses' => 'BudgetLimitController@delete', 'as' => 'delete']);
        Route::get('{budgetLimit}/transactions', ['uses' => 'BudgetLimitController@transactions', 'as' => 'transactions']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'budgets',
     'as'         => 'api.v1.budgets.'],
    static function () {

        // Budget API routes:
        Route::get('', ['uses' => 'BudgetController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'BudgetController@store', 'as' => 'store']);
        Route::get('{budget}', ['uses' => 'BudgetController@show', 'as' => 'show']);
        Route::put('{budget}', ['uses' => 'BudgetController@update', 'as' => 'update']);
        Route::delete('{budget}', ['uses' => 'BudgetController@delete', 'as' => 'delete']);
        Route::get('{budget}/transactions', ['uses' => 'BudgetController@transactions', 'as' => 'transactions']);
        Route::get('{budget}/limits', ['uses' => 'BudgetController@budgetLimits', 'as' => 'budget_limits']);
        Route::post('{budget}/limits', ['uses' => 'BudgetController@storeBudgetLimit', 'as' => 'store_budget_limit']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'categories',
     'as'         => 'api.v1.categories.'],
    static function () {

        // Category API routes:
        Route::get('', ['uses' => 'CategoryController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'CategoryController@store', 'as' => 'store']);
        Route::get('{category}', ['uses' => 'CategoryController@show', 'as' => 'show']);
        Route::put('{category}', ['uses' => 'CategoryController@update', 'as' => 'update']);
        Route::delete('{category}', ['uses' => 'CategoryController@delete', 'as' => 'delete']);
        Route::get('{category}/transactions', ['uses' => 'CategoryController@transactions', 'as' => 'transactions']);
    }
);

/**
 * CHART ROUTES
 */

// Accounts
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Chart', 'prefix' => 'chart/account',
     'as'         => 'api.v1.chart.account.'],
    static function () {
        Route::get('overview', ['uses' => 'AccountController@overview', 'as' => 'overview']);
        Route::get('expense', ['uses' => 'AccountController@expenseOverview', 'as' => 'expense']);
        Route::get('revenue', ['uses' => 'AccountController@revenueOverview', 'as' => 'revenue']);

    }
);

// Available budgets
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Chart', 'prefix' => 'chart/ab',
     'as'         => 'api.v1.chart.ab.'],
    static function () {

        // Overview API routes:
        Route::get('overview/{availableBudget}', ['uses' => 'AvailableBudgetController@overview', 'as' => 'overview']);
    }
);

// Categories
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers\Chart', 'prefix' => 'chart/category',
     'as'         => 'api.v1.chart.category.'],
    static function () {

        // Overview API routes:
        Route::get('overview', ['uses' => 'CategoryController@overview', 'as' => 'overview']);
    }
);





Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'configuration',
     'as'         => 'api.v1.configuration.'],
    static function () {

        // Configuration API routes:
        Route::get('', ['uses' => 'ConfigurationController@index', 'as' => 'index']);
        Route::post('{configName}', ['uses' => 'ConfigurationController@update', 'as' => 'update']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'cer',
     'as'         => 'api.v1.cer.'],
    static function () {

        // Currency Exchange Rate API routes:
        Route::get('', ['uses' => 'CurrencyExchangeRateController@index', 'as' => 'index']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'import',
     'as'         => 'api.v1.import.'],
    static function () {

        // Transaction Links API routes:
        Route::get('list', ['uses' => 'ImportController@listAll', 'as' => 'list']);
        Route::get('{importJob}', ['uses' => 'ImportController@show', 'as' => 'show']);
        Route::get('{importJob}/transactions', ['uses' => 'ImportController@transactions', 'as' => 'transactions']);
    }
);
Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'link_types',
     'as'         => 'api.v1.link_types.'],
    static function () {

        // Link Type API routes:
        Route::get('', ['uses' => 'LinkTypeController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'LinkTypeController@store', 'as' => 'store']);
        Route::get('{linkType}', ['uses' => 'LinkTypeController@show', 'as' => 'show']);
        Route::put('{linkType}', ['uses' => 'LinkTypeController@update', 'as' => 'update']);
        Route::delete('{linkType}', ['uses' => 'LinkTypeController@delete', 'as' => 'delete']);
        Route::get('{linkType}/transactions', ['uses' => 'LinkTypeController@transactions', 'as' => 'transactions']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'transaction_links',
     'as'         => 'api.v1.transaction_links.'],
    static function () {

        // Transaction Links API routes:
        Route::get('', ['uses' => 'TransactionLinkController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'TransactionLinkController@store', 'as' => 'store']);
        Route::get('{journalLink}', ['uses' => 'TransactionLinkController@show', 'as' => 'show']);
        Route::put('{journalLink}', ['uses' => 'TransactionLinkController@update', 'as' => 'update']);
        Route::delete('{journalLink}', ['uses' => 'TransactionLinkController@delete', 'as' => 'delete']);

    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'piggy_banks',
     'as'         => 'api.v1.piggy_banks.'],
    static function () {

        // Piggy Bank API routes:
        Route::get('', ['uses' => 'PiggyBankController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'PiggyBankController@store', 'as' => 'store']);
        Route::get('{piggyBank}', ['uses' => 'PiggyBankController@show', 'as' => 'show']);
        Route::get('{piggyBank}/events', ['uses' => 'PiggyBankController@piggyBankEvents', 'as' => 'events']);
        Route::put('{piggyBank}', ['uses' => 'PiggyBankController@update', 'as' => 'update']);
        Route::delete('{piggyBank}', ['uses' => 'PiggyBankController@delete', 'as' => 'delete']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'preferences',
     'as'         => 'api.v1.preferences.'],
    static function () {

        // Preference API routes:
        Route::get('', ['uses' => 'PreferenceController@index', 'as' => 'index']);
        Route::get('{preference}', ['uses' => 'PreferenceController@show', 'as' => 'show']);
        Route::put('{preference}', ['uses' => 'PreferenceController@update', 'as' => 'update']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'recurrences',
     'as'         => 'api.v1.recurrences.'],
    static function () {

        // Recurrence API routes:
        Route::get('', ['uses' => 'RecurrenceController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'RecurrenceController@store', 'as' => 'store']);
        Route::post('trigger', ['uses' => 'RecurrenceController@trigger', 'as' => 'trigger']);
        Route::get('{recurrence}', ['uses' => 'RecurrenceController@show', 'as' => 'show']);
        Route::put('{recurrence}', ['uses' => 'RecurrenceController@update', 'as' => 'update']);
        Route::delete('{recurrence}', ['uses' => 'RecurrenceController@delete', 'as' => 'delete']);
        Route::get('{recurrence}/transactions', ['uses' => 'RecurrenceController@transactions', 'as' => 'transactions']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'rules',
     'as'         => 'api.v1.rules.'],
    static function () {

        // Rules API routes:
        Route::get('', ['uses' => 'RuleController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'RuleController@store', 'as' => 'store']);
        Route::get('{rule}', ['uses' => 'RuleController@show', 'as' => 'show']);
        Route::put('{rule}', ['uses' => 'RuleController@update', 'as' => 'update']);
        Route::delete('{rule}', ['uses' => 'RuleController@delete', 'as' => 'delete']);
        Route::get('{rule}/test', ['uses' => 'RuleController@testRule', 'as' => 'test']);
        Route::post('{rule}/trigger', ['uses' => 'RuleController@triggerRule', 'as' => 'trigger']);
        Route::post('{rule}/up', ['uses' => 'RuleController@moveUp', 'as' => 'up']);
        Route::post('{rule}/down', ['uses' => 'RuleController@moveDown', 'as' => 'down']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'rule_groups',
     'as'         => 'api.v1.rule_groups.'],
    static function () {

        // Rules API routes:
        Route::get('', ['uses' => 'RuleGroupController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'RuleGroupController@store', 'as' => 'store']);
        Route::get('{ruleGroup}', ['uses' => 'RuleGroupController@show', 'as' => 'show']);
        Route::put('{ruleGroup}', ['uses' => 'RuleGroupController@update', 'as' => 'update']);
        Route::delete('{ruleGroup}', ['uses' => 'RuleGroupController@delete', 'as' => 'delete']);
        Route::get('{ruleGroup}/test', ['uses' => 'RuleGroupController@testGroup', 'as' => 'test']);
        Route::get('{ruleGroup}/rules', ['uses' => 'RuleGroupController@rules', 'as' => 'rules']);
        Route::post('{ruleGroup}/trigger', ['uses' => 'RuleGroupController@triggerGroup', 'as' => 'trigger']);

        Route::post('{ruleGroup}/up', ['uses' => 'RuleGroupController@moveUp', 'as' => 'up']);
        Route::post('{ruleGroup}/down', ['uses' => 'RuleGroupController@moveDown', 'as' => 'down']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'summary',
     'as'         => 'api.v1.summary.'],
    static function () {

        // Overview API routes:
        Route::get('basic', ['uses' => 'SummaryController@basic', 'as' => 'basic']);

    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'currencies',
     'as'         => 'api.v1.currencies.'],
    static function () {

        // Transaction currency API routes:
        Route::get('', ['uses' => 'CurrencyController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'CurrencyController@store', 'as' => 'store']);
        Route::get('{currency_code}', ['uses' => 'CurrencyController@show', 'as' => 'show']);
        Route::put('{currency_code}', ['uses' => 'CurrencyController@update', 'as' => 'update']);
        Route::delete('{currency_code}', ['uses' => 'CurrencyController@delete', 'as' => 'delete']);

        Route::post('{currency_code}/enable', ['uses' => 'CurrencyController@enable', 'as' => 'enable']);
        Route::post('{currency_code}/disable', ['uses' => 'CurrencyController@disable', 'as' => 'disable']);
        Route::post('{currency_code}/default', ['uses' => 'CurrencyController@makeDefault', 'as' => 'default']);

        Route::get('{currency_code}/accounts', ['uses' => 'CurrencyController@accounts', 'as' => 'accounts']);
        Route::get('{currency_code}/available_budgets', ['uses' => 'CurrencyController@availableBudgets', 'as' => 'available_budgets']);
        Route::get('{currency_code}/bills', ['uses' => 'CurrencyController@bills', 'as' => 'bills']);
        Route::get('{currency_code}/budget_limits', ['uses' => 'CurrencyController@budgetLimits', 'as' => 'budget_limits']);
        Route::get('{currency_code}/cer', ['uses' => 'CurrencyController@cer', 'as' => 'cer']);
        Route::get('{currency_code}/recurrences', ['uses' => 'CurrencyController@recurrences', 'as' => 'recurrences']);
        Route::get('{currency_code}/rules', ['uses' => 'CurrencyController@rules', 'as' => 'rules']);
        Route::get('{currency_code}/transactions', ['uses' => 'CurrencyController@transactions', 'as' => 'transactions']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'tags',
     'as'         => 'api.v1.tags.'],
    static function () {
        // Tag API routes:
        Route::get('', ['uses' => 'TagController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'TagController@store', 'as' => 'store']);
        Route::get('{tagOrId}', ['uses' => 'TagController@show', 'as' => 'show']);
        Route::put('{tagOrId}', ['uses' => 'TagController@update', 'as' => 'update']);
        Route::delete('{tagOrId}', ['uses' => 'TagController@delete', 'as' => 'delete']);
        Route::get('{tagOrId}/transactions', ['uses' => 'TagController@transactions', 'as' => 'transactions']);
    }
);

Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'tag-cloud',
     'as'         => 'api.v1.tag-cloud.'],
    static function () {
        // Tag cloud API routes (to prevent collisions)
        Route::get('', ['uses' => 'TagController@cloud', 'as' => 'cloud']);
    }
);


Route::group(
    ['namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'transactions',
     'as'         => 'api.v1.transactions.'],
    static function () {

        // Transaction API routes:
        Route::get('', ['uses' => 'TransactionController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'TransactionController@store', 'as' => 'store']);
        Route::get('{transactionGroup}', ['uses' => 'TransactionController@show', 'as' => 'show']);
        Route::get('{transactionGroup}/attachments', ['uses' => 'TransactionController@attachments', 'as' => 'attachments']);
        Route::get('{transactionGroup}/piggy_bank_events', ['uses' => 'TransactionController@piggyBankEvents', 'as' => 'piggy_bank_events']);
        Route::put('{transactionGroup}', ['uses' => 'TransactionController@update', 'as' => 'update']);
        Route::delete('{transactionGroup}/{transactionJournal}', ['uses' => 'TransactionController@deleteJournal', 'as' => 'delete-journal']);
        Route::delete('{transactionGroup}', ['uses' => 'TransactionController@delete', 'as' => 'delete']);
    }
);


Route::group(
    ['middleware' => ['auth:api', 'bindings', \FireflyIII\Http\Middleware\IsAdmin::class], 'namespace' => 'FireflyIII\Api\V1\Controllers', 'prefix' => 'users',
     'as'         => 'api.v1.users.'],
    static function () {

        // Users API routes:
        Route::get('', ['uses' => 'UserController@index', 'as' => 'index']);
        Route::post('', ['uses' => 'UserController@store', 'as' => 'store']);
        Route::get('{user}', ['uses' => 'UserController@show', 'as' => 'show']);
        Route::put('{user}', ['uses' => 'UserController@update', 'as' => 'update']);
        Route::delete('{user}', ['uses' => 'UserController@delete', 'as' => 'delete']);
    }
);
