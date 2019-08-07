<?php
/**
 * web.php
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

declare(strict_types=1);

Route::group(
    ['namespace' => 'FireflyIII\Http\Controllers\System',
     'as'        => 'installer.', 'prefix' => 'install'], function () {
    Route::get('', ['uses' => 'InstallController@index', 'as' => 'index']);
    Route::post('runCommand', ['uses' => 'InstallController@runCommand', 'as' => 'runCommand']);
}
);

Route::group(
    ['middleware' => 'binders-only', 'namespace' => 'FireflyIII\Http\Controllers\System', 'as' => 'cron.', 'prefix' => 'cron'], static function () {
    Route::get('run/{cliToken}', ['uses' => 'CronController@cron', 'as' => 'cron']);
}
);

/**
 * These routes only work when the user is NOT logged in.
 */
Route::group(
    ['middleware' => 'user-not-logged-in', 'namespace' => 'FireflyIII\Http\Controllers'], static function () {

    // Authentication Routes...
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Auth\LoginController@login');


    // Registration Routes...
    Route::get('register', ['uses' => 'Auth\RegisterController@showRegistrationForm', 'as' => 'register']);
    Route::post('register', 'Auth\RegisterController@register');

    // Password Reset Routes...
    Route::get('password/reset/{token}', ['uses' => 'Auth\ResetPasswordController@showResetForm', 'as' => 'password.reset']);
    Route::post('password/email', ['uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail', 'as' => 'password.email']);
    Route::post('password/reset', ['uses' => 'Auth\ResetPasswordController@reset']);
    Route::get('password/reset', ['uses' => 'Auth\ForgotPasswordController@showLinkRequestForm', 'as' => 'password.reset.request']);

    // Change email routes:
    Route::get('profile/confirm-email-change/{token}', ['uses' => 'ProfileController@confirmEmailChange', 'as' => 'profile.confirm-email-change']);
    Route::get('profile/undo-email-change/{token}/{oldAddressHash}', ['uses' => 'ProfileController@undoEmailChange', 'as' => 'profile.undo-email-change']);

}
);

/**
 * For some other routes, it is only relevant that the user is authenticated.
 */
Route::group(
    ['middleware' => 'user-simple-auth', 'namespace' => 'FireflyIII\Http\Controllers'], function () {
    Route::get('error', ['uses' => 'DebugController@displayError', 'as' => 'error']);
    Route::any('logout', ['uses' => 'Auth\LoginController@logout', 'as' => 'logout']);
    Route::get('flush', ['uses' => 'DebugController@flush', 'as' => 'flush']);
    Route::get('routes', ['uses' => 'DebugController@routes', 'as' => 'routes']);
    Route::get('debug', 'DebugController@index')->name('debug');
}
);


///**
// * For the two factor routes, the user must be logged in, but NOT 2FA. Account confirmation does not matter here.
// *
// */
Route::group(
    ['middleware' => 'user-logged-in-no-2fa', 'prefix' => 'two-factor', 'as' => 'two-factor.', 'namespace' => 'FireflyIII\Http\Controllers\Auth'], function () {
    Route::post('submit', ['uses' => 'TwoFactorController@submitMFA', 'as' => 'submit']);
    Route::get('lost', ['uses' => 'TwoFactorController@lostTwoFactor', 'as' => 'lost']);
    //    Route::post('', ['uses' => 'TwoFactorController@postIndex', 'as' => 'post']);
    //
}
);

/**
 * For all other routes, the user must be fully authenticated and have an activated account.
 */

/**
 * Home Controller
 */
Route::group(
    ['middleware' => ['user-full-auth'], 'namespace' => 'FireflyIII\Http\Controllers'], function () {
    Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::get('/flash', ['uses' => 'DebugController@testFlash', 'as' => 'test-flash']);
    Route::get('/home', ['uses' => 'HomeController@index', 'as' => 'home']);
    Route::post('/daterange', ['uses' => 'HomeController@dateRange', 'as' => 'daterange']);
}
);


/**
 * Account Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'accounts', 'as' => 'accounts.'], function () {

    // show:
    Route::get('{objectType}', ['uses' => 'Account\IndexController@index', 'as' => 'index'])->where('objectType', 'revenue|asset|expense|liabilities');

    // create
    Route::get('create/{objectType}', ['uses' => 'Account\CreateController@create', 'as' => 'create'])->where('objectType', 'revenue|asset|expense|liabilities');
    Route::post('store', ['uses' => 'Account\CreateController@store', 'as' => 'store']);


    // edit
    Route::get('edit/{account}', ['uses' => 'Account\EditController@edit', 'as' => 'edit']);
    Route::post('update/{account}', ['uses' => 'Account\EditController@update', 'as' => 'update']);

    // delete
    Route::get('delete/{account}', ['uses' => 'Account\DeleteController@delete', 'as' => 'delete']);
    Route::post('destroy/{account}', ['uses' => 'Account\DeleteController@destroy', 'as' => 'destroy']);

    // show
    Route::get('show/{account}/all', ['uses' => 'Account\ShowController@showAll', 'as' => 'show.all']);
    Route::get('show/{account}/{start_date?}/{end_date?}', ['uses' => 'Account\ShowController@show', 'as' => 'show']);

    // reconcile routes:
    Route::get('reconcile/{account}/index/{start_date?}/{end_date?}', ['uses' => 'Account\ReconcileController@reconcile', 'as' => 'reconcile']);
    Route::post('reconcile/{account}/submit/{start_date?}/{end_date?}', ['uses' => 'Account\ReconcileController@submit', 'as' => 'reconcile.submit']);

    // reconcile JSON routes
    Route::get('reconcile/{account}/overview/{start_date?}/{end_date?}', ['uses' => 'Json\ReconcileController@overview', 'as' => 'reconcile.overview']);
    Route::get(
        'reconcile/{account}/transactions/{start_date?}/{end_date?}', ['uses' => 'Json\ReconcileController@transactions', 'as' => 'reconcile.transactions']
    );

    // show reconciliation
    // TODO improve me
    //Route::get('reconcile/show/{transactionGroup}', ['uses' => 'Account\ReconcileController@show', 'as' => 'reconcile.show']);
    //Route::get('reconcile/edit/{transactionGroup}', ['uses' => 'Account\ReconcileController@edit', 'as' => 'reconcile.edit']);
    //Route::post('reconcile/update/{transactionGroup}', ['uses' => 'Account\ReconcileController@update', 'as' => 'reconcile.update']);


}
);

/**
 * Attachment Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'attachments', 'as' => 'attachments.'], function () {
    Route::get('', ['uses' => 'AttachmentController@index', 'as' => 'index']);
    Route::get('edit/{attachment}', ['uses' => 'AttachmentController@edit', 'as' => 'edit']);
    Route::get('delete/{attachment}', ['uses' => 'AttachmentController@delete', 'as' => 'delete']);
    Route::get('download/{attachment}', ['uses' => 'AttachmentController@download', 'as' => 'download']);
    Route::get('view/{attachment}', ['uses' => 'AttachmentController@view', 'as' => 'view']);

    Route::post('update/{attachment}', ['uses' => 'AttachmentController@update', 'as' => 'update']);
    Route::post('destroy/{attachment}', ['uses' => 'AttachmentController@destroy', 'as' => 'destroy']);

}
);

/**
 * Bills Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'bills', 'as' => 'bills.'], function () {
    Route::get('', ['uses' => 'BillController@index', 'as' => 'index']);
    Route::get('rescan/{bill}', ['uses' => 'BillController@rescan', 'as' => 'rescan']);
    Route::get('create', ['uses' => 'BillController@create', 'as' => 'create']);
    Route::get('edit/{bill}', ['uses' => 'BillController@edit', 'as' => 'edit']);
    Route::get('delete/{bill}', ['uses' => 'BillController@delete', 'as' => 'delete']);
    Route::get('show/{bill}', ['uses' => 'BillController@show', 'as' => 'show']);

    Route::post('store', ['uses' => 'BillController@store', 'as' => 'store']);
    Route::post('update/{bill}', ['uses' => 'BillController@update', 'as' => 'update']);
    Route::post('destroy/{bill}', ['uses' => 'BillController@destroy', 'as' => 'destroy']);
}
);


/**
 * Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'budgets', 'as' => 'budgets.'], function () {

    // delete
    Route::get('delete/{budget}', ['uses' => 'Budget\DeleteController@delete', 'as' => 'delete']);
    Route::post('destroy/{budget}', ['uses' => 'Budget\DeleteController@destroy', 'as' => 'destroy']);

    // create
    Route::get('create', ['uses' => 'Budget\CreateController@create', 'as' => 'create']);
    Route::post('store', ['uses' => 'Budget\CreateController@store', 'as' => 'store']);

    // edit
    Route::get('edit/{budget}', ['uses' => 'Budget\EditController@edit', 'as' => 'edit']);
    Route::post('update/{budget}', ['uses' => 'Budget\EditController@update', 'as' => 'update']);

    // show
    Route::get('show/{budget}', ['uses' => 'Budget\ShowController@show', 'as' => 'show']);
    Route::get('show/{budget}/{budgetLimit}', ['uses' => 'Budget\ShowController@showByBudgetLimit', 'as' => 'show.limit']);
    Route::get('list/no-budget/all', ['uses' => 'Budget\ShowController@noBudgetAll', 'as' => 'no-budget-all']);
    Route::get('list/no-budget/{start_date?}/{end_date?}', ['uses' => 'Budget\ShowController@noBudget', 'as' => 'no-budget']);

    // reorder budgets
    Route::post('reorder', ['uses' => 'Budget\IndexController@reorder', 'as' => 'reorder']);

    // index
    Route::get('{start_date?}/{end_date?}', ['uses' => 'Budget\IndexController@index', 'as' => 'index']);

    // update budget amount and income amount
    Route::get('income/{start_date}/{end_date}', ['uses' => 'Budget\AmountController@updateIncome', 'as' => 'income']);
    Route::post('income', ['uses' => 'Budget\AmountController@postUpdateIncome', 'as' => 'income.post']);
    Route::post('amount/{budget}', ['uses' => 'Budget\AmountController@amount', 'as' => 'amount']);


}
);

/**
 * Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'categories', 'as' => 'categories.'], function () {

    // index:
    Route::get('', ['uses' => 'Category\IndexController@index', 'as' => 'index']);

    // create
    Route::get('create', ['uses' => 'Category\CreateController@create', 'as' => 'create']);
    Route::post('store', ['uses' => 'Category\CreateController@store', 'as' => 'store']);

    // edit
    Route::get('edit/{category}', ['uses' => 'Category\EditController@edit', 'as' => 'edit']);
    Route::post('update/{category}', ['uses' => 'Category\EditController@update', 'as' => 'update']);

    // delete
    Route::get('delete/{category}', ['uses' => 'Category\DeleteController@delete', 'as' => 'delete']);
    Route::post('destroy/{category}', ['uses' => 'Category\DeleteController@destroy', 'as' => 'destroy']);

    // show category:
    Route::get('show/{category}/all', ['uses' => 'Category\ShowController@showAll', 'as' => 'show.all']);
    Route::get('show/{category}/{start_date?}/{end_date?}', ['uses' => 'Category\ShowController@show', 'as' => 'show']);

    // no category controller:
    Route::get('list/no-category/all', ['uses' => 'Category\NoCategoryController@showAll', 'as' => 'no-category.all']);
    Route::get('list/no-category/{start_date?}/{end_date?}', ['uses' => 'Category\NoCategoryController@show', 'as' => 'no-category']);

}
);


/**
 * Currency Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'currencies', 'as' => 'currencies.'], function () {
    Route::get('', ['uses' => 'CurrencyController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'CurrencyController@create', 'as' => 'create']);
    Route::get('edit/{currency}', ['uses' => 'CurrencyController@edit', 'as' => 'edit']);
    Route::get('delete/{currency}', ['uses' => 'CurrencyController@delete', 'as' => 'delete']);
    Route::get('default/{currency}', ['uses' => 'CurrencyController@defaultCurrency', 'as' => 'default']);
    Route::get('enable/{currency}', ['uses' => 'CurrencyController@enableCurrency', 'as' => 'enable']);
    Route::get('disable/{currency}', ['uses' => 'CurrencyController@disableCurrency', 'as' => 'disable']);

    Route::post('store', ['uses' => 'CurrencyController@store', 'as' => 'store']);
    Route::post('update/{currency}', ['uses' => 'CurrencyController@update', 'as' => 'update']);
    Route::post('destroy/{currency}', ['uses' => 'CurrencyController@destroy', 'as' => 'destroy']);

}
);

/**
 * Chart\Account Controller (default report)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/account', 'as' => 'chart.account.'], function () {
    Route::get('frontpage', ['uses' => 'AccountController@frontpage', 'as' => 'frontpage']);
    Route::get('expense', ['uses' => 'AccountController@expenseAccounts', 'as' => 'expense']);
    Route::get('revenue', ['uses' => 'AccountController@revenueAccounts', 'as' => 'revenue']);
    Route::get('report/{accountList}/{start_date}/{end_date}', ['uses' => 'AccountController@report', 'as' => 'report']);
    Route::get('period/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@period', 'as' => 'period']);

    Route::get('income-category/{account}/all/all', ['uses' => 'AccountController@incomeCategoryAll', 'as' => 'income-category-all']);
    Route::get('expense-category/{account}/all/all', ['uses' => 'AccountController@expenseCategoryAll', 'as' => 'expense-category-all']);
    Route::get('expense-budget/{account}/all/all', ['uses' => 'AccountController@expenseBudgetAll', 'as' => 'expense-budget-all']);

    Route::get('income-category/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@incomeCategory', 'as' => 'income-category']);
    Route::get('expense-category/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@expenseCategory', 'as' => 'expense-category']);
    Route::get('expense-budget/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@expenseBudget', 'as' => 'expense-budget']);
}
);


/**
 * Chart\Bill Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/bill', 'as' => 'chart.bill.'], function () {
    Route::get('frontpage', ['uses' => 'BillController@frontpage', 'as' => 'frontpage']);
    Route::get('single/{bill}', ['uses' => 'BillController@single', 'as' => 'single']);

}
);

/**
 * Chart\Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/budget', 'as' => 'chart.budget.'], function () {

    Route::get('frontpage', ['uses' => 'BudgetController@frontpage', 'as' => 'frontpage']);
    Route::get('period/0/{accountList}/{start_date}/{end_date}', ['uses' => 'BudgetController@periodNoBudget', 'as' => 'period.no-budget']);
    Route::get('period/{budget}/{accountList}/{start_date}/{end_date}', ['uses' => 'BudgetController@period', 'as' => 'period']);
    Route::get('budget/{budget}/{budgetLimit}', ['uses' => 'BudgetController@budgetLimit', 'as' => 'budget-limit']);
    Route::get('budget/{budget}', ['uses' => 'BudgetController@budget', 'as' => 'budget']);

    // these charts are used in budget/show:
    Route::get('expense-category/{budget}/{budgetLimit?}', ['uses' => 'BudgetController@expenseCategory', 'as' => 'expense-category']);
    Route::get('expense-asset/{budget}/{budgetLimit?}', ['uses' => 'BudgetController@expenseAsset', 'as' => 'expense-asset']);
    Route::get('expense-expense/{budget}/{budgetLimit?}', ['uses' => 'BudgetController@expenseExpense', 'as' => 'expense-expense']);

    // these charts are used in reports (category reports):
    Route::get(
        'budget/expense/{accountList}/{budgetList}/{start_date}/{end_date}/{others}',
        ['uses' => 'BudgetReportController@budgetExpense', 'as' => 'budget-expense']
    );
    Route::get(
        'account/expense/{accountList}/{budgetList}/{start_date}/{end_date}/{others}',
        ['uses' => 'BudgetReportController@accountExpense', 'as' => 'account-expense']
    );

    Route::get(
        'operations/{accountList}/{budgetList}/{start_date}/{end_date}',
        ['uses' => 'BudgetReportController@mainChart', 'as' => 'main']
    );
}
);

/**
 * Chart\Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/category', 'as' => 'chart.category.'],
    function () {

        Route::get('frontpage', ['uses' => 'CategoryController@frontPage', 'as' => 'frontpage']);
        Route::get('period/{category}', ['uses' => 'CategoryController@currentPeriod', 'as' => 'current']);
        Route::get('period/{category}/{date}', ['uses' => 'CategoryController@specificPeriod', 'as' => 'specific']);
        Route::get('all/{category}', ['uses' => 'CategoryController@all', 'as' => 'all']);
        Route::get(
            'report-period/0/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@reportPeriodNoCategory', 'as' => 'period.no-category']
        );
        Route::get('report-period/{category}/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@reportPeriod', 'as' => 'period']);

        // these charts are used in reports (category reports):
        Route::get(
            'category/income/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@categoryIncome', 'as' => 'category-income']
        );
        Route::get(
            'category/expense/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@categoryExpense', 'as' => 'category-expense']
        );
        Route::get(
            'account/income/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@accountIncome', 'as' => 'account-income']
        );
        Route::get(
            'account/expense/{accountList}/{categoryList}/{start_date}/{end_date}/{others}',
            ['uses' => 'CategoryReportController@accountExpense', 'as' => 'account-expense']
        );

        Route::get(
            'operations/{accountList}/{categoryList}/{start_date}/{end_date}',
            ['uses' => 'CategoryReportController@mainChart', 'as' => 'main']
        );

    }
);

/**
 * Chart\Tag Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/tag', 'as' => 'chart.tag.'], function () {

    // these charts are used in reports (tag reports):
    Route::get(
        'tag/income/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@tagIncome', 'as' => 'tag-income']
    );
    Route::get(
        'tag/expense/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@tagExpense', 'as' => 'tag-expense']
    );
    Route::get(
        'account/income/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@accountIncome', 'as' => 'account-income']
    );
    Route::get(
        'account/expense/{accountList}/{tagList}/{start_date}/{end_date}/{others}',
        ['uses' => 'TagReportController@accountExpense', 'as' => 'account-expense']
    );

    // new routes
    Route::get(
        'budget/expense/{accountList}/{tagList}/{start_date}/{end_date}',
        ['uses' => 'TagReportController@budgetExpense', 'as' => 'budget-expense']
    );
    Route::get(
        'category/expense/{accountList}/{tagList}/{start_date}/{end_date}',
        ['uses' => 'TagReportController@categoryExpense', 'as' => 'category-expense']

    );


    Route::get(
        'operations/{accountList}/{tagList}/{start_date}/{end_date}',
        ['uses' => 'TagReportController@mainChart', 'as' => 'main']
    );

}
);

/**
 * Chart\Expense Controller (for expense/revenue report).
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/expense', 'as' => 'chart.expense.'], function () {
    Route::get(
        'operations/{accountList}/{expenseList}/{start_date}/{end_date}',
        ['uses' => 'ExpenseReportController@mainChart', 'as' => 'main']
    );
}
);


/**
 * Chart\PiggyBank Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/piggy-bank', 'as' => 'chart.piggy-bank.'],
    function () {
        Route::get('{piggyBank}', ['uses' => 'PiggyBankController@history', 'as' => 'history']);
    }
);

/**
 * Chart\Report Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Chart', 'prefix' => 'chart/report', 'as' => 'chart.report.'], function () {
    Route::get('operations/{accountList}/{start_date}/{end_date}', ['uses' => 'ReportController@operations', 'as' => 'operations']);
    Route::get('operations-sum/{accountList}/{start_date}/{end_date}/', ['uses' => 'ReportController@sum', 'as' => 'sum']);
    Route::get('net-worth/{accountList}/{start_date}/{end_date}/', ['uses' => 'ReportController@netWorth', 'as' => 'net-worth']);

}
);

/**
 * Import Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'import', 'as' => 'import.'], function () {

    // index
    Route::get('', ['uses' => 'Import\IndexController@index', 'as' => 'index']);

    // create new job
    Route::get('create/{import_provider}', ['uses' => 'Import\IndexController@create', 'as' => 'create']);

    // set global prerequisites for an import source, possible with a job already attached.
    Route::get('prerequisites/{import_provider}/{importJob?}', ['uses' => 'Import\PrerequisitesController@index', 'as' => 'prerequisites.index']);
    Route::post('prerequisites/{import_provider}/{importJob?}', ['uses' => 'Import\PrerequisitesController@post', 'as' => 'prerequisites.post']);

    // configure a job:
    Route::get('job/configuration/{importJob}', ['uses' => 'Import\JobConfigurationController@index', 'as' => 'job.configuration.index']);
    Route::post('job/configuration/{importJob}', ['uses' => 'Import\JobConfigurationController@post', 'as' => 'job.configuration.post']);

    // get status of a job. This is also the landing page of a job after job config is complete.
    Route::get('job/status/{importJob}', ['uses' => 'Import\JobStatusController@index', 'as' => 'job.status.index']);
    Route::get('job/json/{importJob}', ['uses' => 'Import\JobStatusController@json', 'as' => 'job.status.json']);

    // start the job!
    Route::any('job/start/{importJob}', ['uses' => 'Import\JobStatusController@start', 'as' => 'job.start']);
    Route::any('job/store/{importJob}', ['uses' => 'Import\JobStatusController@store', 'as' => 'job.store']);

    // download config:
    Route::get('download/{importJob}', ['uses' => 'Import\IndexController@download', 'as' => 'job.download']);

    // callback URI for YNAB OAuth. Sadly, needs a custom solution.
    Route::get('ynab-callback', ['uses' => 'Import\CallbackController@ynab', 'as' => 'callback.ynab']);
}
);

/**
 * Help Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'help', 'as' => 'help.'], function () {
    Route::get('{route}', ['uses' => 'HelpController@show', 'as' => 'show']);

}
);

/**
 * Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'v1/jscript', 'as' => 'javascript.'], function () {
    Route::get('variables', ['uses' => 'JavascriptController@variables', 'as' => 'variables']);
    Route::get('accounts', ['uses' => 'JavascriptController@accounts', 'as' => 'accounts']);
    Route::get('currencies', ['uses' => 'JavascriptController@currencies', 'as' => 'currencies']);
}
);

/**
 * JSON Controller(s)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'json', 'as' => 'json.'], function () {

    // for auto complete
    Route::get('accounts', ['uses' => 'Json\AutoCompleteController@accounts', 'as' => 'autocomplete.accounts']);
    Route::get('revenue-accounts', ['uses' => 'Json\AutoCompleteController@revenueAccounts', 'as' => 'autocomplete.revenue-accounts']);
    Route::get('expense-accounts', ['uses' => 'Json\AutoCompleteController@expenseAccounts', 'as' => 'autocomplete.expense-accounts']);
    Route::get('budgets', ['uses' => 'Json\AutoCompleteController@budgets', 'as' => 'autocomplete.budgets']);
    Route::get('categories', ['uses' => 'Json\AutoCompleteController@categories', 'as' => 'autocomplete.categories']);
    Route::get('currencies', ['uses' => 'Json\AutoCompleteController@currencies', 'as' => 'autocomplete.currencies']);
    Route::get('piggy-banks', ['uses' => 'Json\AutoCompleteController@piggyBanks', 'as' => 'autocomplete.piggy-banks']);
    Route::get('tags', ['uses' => 'Json\AutoCompleteController@tags', 'as' => 'autocomplete.tags']);
    Route::get('transaction-journals/all', ['uses' => 'Json\AutoCompleteController@allJournals', 'as' => 'autocomplete.all-journals']);
    Route::get('transaction-journals/with-id', ['uses' => 'Json\AutoCompleteController@allJournalsWithID', 'as' => 'autocomplete.all-journals-with-id']);
    Route::get('currency-names', ['uses' => 'Json\AutoCompleteController@currencyNames', 'as' => 'autocomplete.currency-names']);



    Route::get('transaction-types', ['uses' => 'Json\AutoCompleteController@transactionTypes', 'as' => 'transaction-types']);

    // boxes
    Route::get('box/balance', ['uses' => 'Json\BoxController@balance', 'as' => 'box.balance']);
    Route::get('box/bills', ['uses' => 'Json\BoxController@bills', 'as' => 'box.bills']);
    Route::get('box/available', ['uses' => 'Json\BoxController@available', 'as' => 'box.available']);
    Route::get('box/net-worth', ['uses' => 'Json\BoxController@netWorth', 'as' => 'box.net-worth']);

    // rules
    Route::get('trigger', ['uses' => 'Json\RuleController@trigger', 'as' => 'trigger']);
    Route::get('action', ['uses' => 'Json\RuleController@action', 'as' => 'action']);

    // front page
    Route::get('frontpage/piggy-banks', ['uses' => 'Json\FrontpageController@piggyBanks', 'as' => 'fp.piggy-banks']);

    // currency conversion:
    Route::get('rate/{fromCurrencyCode}/{toCurrencyCode}/{date}', ['uses' => 'Json\ExchangeController@getRate', 'as' => 'rate']);

    // intro things:
    Route::any('intro/finished/{route}/{specificPage?}', ['uses' => 'Json\IntroController@postFinished', 'as' => 'intro.finished']);
    Route::post('intro/enable/{route}/{specificPage?}', ['uses' => 'Json\IntroController@postEnable', 'as' => 'intro.enable']);
    Route::get('intro/{route}/{specificPage?}', ['uses' => 'Json\IntroController@getIntroSteps', 'as' => 'intro']);

}
);


/**
 * NewUser Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'new-user', 'as' => 'new-user.'], function () {
    Route::get('', ['uses' => 'NewUserController@index', 'as' => 'index']);
    Route::post('submit', ['uses' => 'NewUserController@submit', 'as' => 'submit']);
}
);

/**
 * Piggy Bank Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'piggy-banks', 'as' => 'piggy-banks.'], function () {
    Route::get('', ['uses' => 'PiggyBankController@index', 'as' => 'index']);
    Route::get('add/{piggyBank}', ['uses' => 'PiggyBankController@add', 'as' => 'add-money']);
    Route::get('remove/{piggyBank}', ['uses' => 'PiggyBankController@remove', 'as' => 'remove-money']);
    Route::get('add-money/{piggyBank}', ['uses' => 'PiggyBankController@addMobile', 'as' => 'add-money-mobile']);
    Route::get('remove-money/{piggyBank}', ['uses' => 'PiggyBankController@removeMobile', 'as' => 'remove-money-mobile']);
    Route::get('create', ['uses' => 'PiggyBankController@create', 'as' => 'create']);
    Route::get('edit/{piggyBank}', ['uses' => 'PiggyBankController@edit', 'as' => 'edit']);
    Route::get('delete/{piggyBank}', ['uses' => 'PiggyBankController@delete', 'as' => 'delete']);
    Route::get('show/{piggyBank}', ['uses' => 'PiggyBankController@show', 'as' => 'show']);
    Route::post('store', ['uses' => 'PiggyBankController@store', 'as' => 'store']);
    Route::post('update/{piggyBank}', ['uses' => 'PiggyBankController@update', 'as' => 'update']);
    Route::post('destroy/{piggyBank}', ['uses' => 'PiggyBankController@destroy', 'as' => 'destroy']);
    Route::post('add/{piggyBank}', ['uses' => 'PiggyBankController@postAdd', 'as' => 'add']);
    Route::post('remove/{piggyBank}', ['uses' => 'PiggyBankController@postRemove', 'as' => 'remove']);

    Route::post('set-order/{piggyBank}', ['uses' => 'PiggyBankController@setOrder', 'as' => 'set-order']);


}
);


/**
 * Preferences Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'preferences', 'as' => 'preferences.'], function () {
    Route::get('', ['uses' => 'PreferencesController@index', 'as' => 'index']);
    Route::post('', ['uses' => 'PreferencesController@postIndex', 'as' => 'update']);


}
);

/**
 * Profile Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'profile', 'as' => 'profile.'], function () {

    Route::get('', ['uses' => 'ProfileController@index', 'as' => 'index']);
    Route::get('change-email', ['uses' => 'ProfileController@changeEmail', 'as' => 'change-email']);
    Route::get('change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);
    Route::get('delete-account', ['uses' => 'ProfileController@deleteAccount', 'as' => 'delete-account']);

    Route::post('delete-account', ['uses' => 'ProfileController@postDeleteAccount', 'as' => 'delete-account.post']);
    Route::post('change-password', ['uses' => 'ProfileController@postChangePassword', 'as' => 'change-password.post']);
    Route::post('change-email', ['uses' => 'ProfileController@postChangeEmail', 'as' => 'change-email.post']);
    Route::post('regenerate', ['uses' => 'ProfileController@regenerate', 'as' => 'regenerate']);

    // new 2FA routes
    Route::post('enable2FA', ['uses' => 'ProfileController@enable2FA', 'as' => 'enable2FA']);
    Route::get('2fa/code', ['uses' => 'ProfileController@code', 'as' => 'code']);
    Route::post('2fa/code', ['uses' => 'ProfileController@postCode', 'as' => 'code.store']);
    Route::get('/delete-code', ['uses' => 'ProfileController@deleteCode', 'as' => 'delete-code']);
    Route::get('2fa/new-codes', ['uses' => 'ProfileController@newBackupCodes', 'as' => 'new-backup-codes']);

}
);

/**
 * Recurring Transactions Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'recurring', 'as' => 'recurring.'], function () {

    Route::get('', ['uses' => 'Recurring\IndexController@index', 'as' => 'index']);

    Route::get('show/{recurrence}', ['uses' => 'Recurring\IndexController@show', 'as' => 'show']);
    Route::get('create', ['uses' => 'Recurring\CreateController@create', 'as' => 'create']);
    Route::get('edit/{recurrence}', ['uses' => 'Recurring\EditController@edit', 'as' => 'edit']);
    Route::get('delete/{recurrence}', ['uses' => 'Recurring\DeleteController@delete', 'as' => 'delete']);

    Route::post('store', ['uses' => 'Recurring\CreateController@store', 'as' => 'store']);
    Route::post('update/{recurrence}', ['uses' => 'Recurring\EditController@update', 'as' => 'update']);
    Route::post('destroy/{recurrence}', ['uses' => 'Recurring\DeleteController@destroy', 'as' => 'destroy']);

    // JSON routes:
    Route::get('events', ['uses' => 'Json\RecurrenceController@events', 'as' => 'events']);
    Route::get('suggest', ['uses' => 'Json\RecurrenceController@suggest', 'as' => 'suggest']);
}
);

/**
 * Report Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'reports', 'as' => 'reports.'], function () {

    Route::get('', ['uses' => 'ReportController@index', 'as' => 'index']);
    Route::get('options/{reportType}', ['uses' => 'ReportController@options', 'as' => 'options']);
    Route::get('default/{accountList}/{start_date}/{end_date}', ['uses' => 'ReportController@defaultReport', 'as' => 'report.default']);
    Route::get('audit/{accountList}/{start_date}/{end_date}', ['uses' => 'ReportController@auditReport', 'as' => 'report.audit']);
    Route::get('category/{accountList}/{categoryList}/{start_date}/{end_date}', ['uses' => 'ReportController@categoryReport', 'as' => 'report.category']);
    Route::get('budget/{accountList}/{budgetList}/{start_date}/{end_date}', ['uses' => 'ReportController@budgetReport', 'as' => 'report.budget']);
    Route::get('tag/{accountList}/{tagList}/{start_date}/{end_date}', ['uses' => 'ReportController@tagReport', 'as' => 'report.tag']);
    Route::get('account/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ReportController@accountReport', 'as' => 'report.account']);

    Route::post('', ['uses' => 'ReportController@postIndex', 'as' => 'index.post']);
}
);

/**
 * Report Data AccountController
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/account', 'as' => 'report-data.account.'],
    function () {
        Route::get('general/{accountList}/{start_date}/{end_date}', ['uses' => 'AccountController@general', 'as' => 'general']);
    }
);

/**
 * Report Data Expense / Revenue Account Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/expense', 'as' => 'report-data.expense.'],
    function () {

        // spent per period
        Route::get('spent/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@spent', 'as' => 'spent']);

        // per category && per budget
        Route::get('category/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@category', 'as' => 'category']);
        Route::get('budget/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@budget', 'as' => 'budget']);

        //expense earned top X
        Route::get('expenses/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@topExpense', 'as' => 'expenses']);
        Route::get('income/{accountList}/{expenseList}/{start_date}/{end_date}', ['uses' => 'ExpenseController@topIncome', 'as' => 'income']);

    }
);

/**
 * Report Data Income/Expenses Controller (called financial operations)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/operations',
     'as'         => 'report-data.operations.'], function () {
    Route::get('operations/{accountList}/{start_date}/{end_date}', ['uses' => 'OperationsController@operations', 'as' => 'operations']);
    Route::get('income/{accountList}/{start_date}/{end_date}', ['uses' => 'OperationsController@income', 'as' => 'income']);
    Route::get('expenses/{accountList}/{start_date}/{end_date}', ['uses' => 'OperationsController@expenses', 'as' => 'expenses']);

}
);

/**
 * Report Data Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/category',
     'as'         => 'report-data.category.'], function () {
    Route::get('operations/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@operations', 'as' => 'operations']);
    Route::get('income/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@income', 'as' => 'income']);
    Route::get('expenses/{accountList}/{start_date}/{end_date}', ['uses' => 'CategoryController@expenses', 'as' => 'expenses']);

}
);

/**
 * Report Data Balance Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/balance', 'as' => 'report-data.balance.'],
    function () {

        Route::get('general/{accountList}/{start_date}/{end_date}', ['uses' => 'BalanceController@general', 'as' => 'general']);
    }
);

/**
 * Report Data Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Report', 'prefix' => 'report-data/budget', 'as' => 'report-data.budget.'],
    function () {

        Route::get('general/{accountList}/{start_date}/{end_date}/', ['uses' => 'BudgetController@general', 'as' => 'general']);
        Route::get('period/{accountList}/{start_date}/{end_date}', ['uses' => 'BudgetController@period', 'as' => 'period']);

    }
);

/**
 * Rules Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'rules', 'as' => 'rules.'], function () {

    // create controller
    Route::get('create/{ruleGroup?}', ['uses' => 'Rule\CreateController@create', 'as' => 'create']);
    Route::get('create-from-bill/{bill}', ['uses' => 'Rule\CreateController@createFromBill', 'as' => 'create-from-bill']);
    Route::post('store', ['uses' => 'Rule\CreateController@store', 'as' => 'store']);

    // delete controller
    Route::get('delete/{rule}', ['uses' => 'Rule\DeleteController@delete', 'as' => 'delete']);
    Route::post('destroy/{rule}', ['uses' => 'Rule\DeleteController@destroy', 'as' => 'destroy']);

    // index controller
    Route::get('', ['uses' => 'Rule\IndexController@index', 'as' => 'index']);
    Route::get('up/{rule}', ['uses' => 'Rule\IndexController@up', 'as' => 'up']);
    Route::get('down/{rule}', ['uses' => 'Rule\IndexController@down', 'as' => 'down']);
    Route::post('trigger/order/{rule}', ['uses' => 'Rule\IndexController@reorderRuleTriggers', 'as' => 'reorder-triggers']);
    Route::post('action/order/{rule}', ['uses' => 'Rule\IndexController@reorderRuleActions', 'as' => 'reorder-actions']);

    // select controller
    Route::get('test', ['uses' => 'Rule\SelectController@testTriggers', 'as' => 'test-triggers']);
    Route::get('test-rule/{rule}', ['uses' => 'Rule\SelectController@testTriggersByRule', 'as' => 'test-triggers-rule']);
    Route::get('select/{rule}', ['uses' => 'Rule\SelectController@selectTransactions', 'as' => 'select-transactions']);
    Route::post('execute/{rule}', ['uses' => 'Rule\SelectController@execute', 'as' => 'execute']);

    // edit controller
    Route::get('edit/{rule}', ['uses' => 'Rule\EditController@edit', 'as' => 'edit']);
    Route::post('update/{rule}', ['uses' => 'Rule\EditController@update', 'as' => 'update']);


}
);

/**
 * Rule Groups Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'rule-groups', 'as' => 'rule-groups.'], function () {
    Route::get('create', ['uses' => 'RuleGroup\CreateController@create', 'as' => 'create']);
    Route::get('edit/{ruleGroup}', ['uses' => 'RuleGroup\EditController@edit', 'as' => 'edit']);
    Route::get('delete/{ruleGroup}', ['uses' => 'RuleGroup\DeleteController@delete', 'as' => 'delete']);
    Route::get('up/{ruleGroup}', ['uses' => 'RuleGroup\EditController@up', 'as' => 'up']);
    Route::get('down/{ruleGroup}', ['uses' => 'RuleGroup\EditController@down', 'as' => 'down']);
    Route::get('select/{ruleGroup}', ['uses' => 'RuleGroup\ExecutionController@selectTransactions', 'as' => 'select-transactions']);

    Route::post('store', ['uses' => 'RuleGroup\CreateController@store', 'as' => 'store']);
    Route::post('update/{ruleGroup}', ['uses' => 'RuleGroup\EditController@update', 'as' => 'update']);
    Route::post('destroy/{ruleGroup}', ['uses' => 'RuleGroup\DeleteController@destroy', 'as' => 'destroy']);
    Route::post('execute/{ruleGroup}', ['uses' => 'RuleGroup\ExecutionController@execute', 'as' => 'execute']);
}
);

/**
 * Search Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'search', 'as' => 'search.'], function () {
    Route::get('', ['uses' => 'SearchController@index', 'as' => 'index']);
    Route::any('search', ['uses' => 'SearchController@search', 'as' => 'search']);
}
);


/**
 * Tag Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'tags', 'as' => 'tags.'], function () {

    Route::get('', ['uses' => 'TagController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'TagController@create', 'as' => 'create']);

    Route::get('show/{tag}/all', ['uses' => 'TagController@showAll', 'as' => 'show.all']);
    Route::get('show/{tag}/{start_date?}/{end_date?}', ['uses' => 'TagController@show', 'as' => 'show']);

    Route::get('edit/{tag}', ['uses' => 'TagController@edit', 'as' => 'edit']);
    Route::get('delete/{tag}', ['uses' => 'TagController@delete', 'as' => 'delete']);

    Route::post('store', ['uses' => 'TagController@store', 'as' => 'store']);
    Route::post('update/{tag}', ['uses' => 'TagController@update', 'as' => 'update']);
    Route::post('destroy/{tag}', ['uses' => 'TagController@destroy', 'as' => 'destroy']);
}
);

/**
 * Transaction Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers', 'prefix' => 'transactions', 'as' => 'transactions.'], function () {

    // show groups:
    // TODO improve these routes
    Route::get('{what}/all', ['uses' => 'Transaction\IndexController@indexAll', 'as' => 'index.all'])->where(['what' => 'withdrawal|deposit|transfers|transfer']);

    Route::get('{what}/{start_date?}/{end_date?}', ['uses' => 'Transaction\IndexController@index', 'as' => 'index'])->where(
        ['what' => 'withdrawal|deposit|transfers|transfer']
    );

    // create group:
    Route::get('create/{objectType}', ['uses' => 'Transaction\CreateController@create', 'as' => 'create']);
    Route::post('store', ['uses' => 'Transaction\CreateController@store', 'as' => 'store']);

    // edit group
    Route::get('edit/{transactionGroup}', ['uses' => 'Transaction\EditController@edit', 'as' => 'edit']);
    Route::post('update', ['uses' => 'Transaction\EditController@update', 'as' => 'update']);

    // delete group
    Route::get('delete/{transactionGroup}', ['uses' => 'Transaction\DeleteController@delete', 'as' => 'delete']);
    Route::post('destroy/{transactionGroup}', ['uses' => 'Transaction\DeleteController@destroy', 'as' => 'destroy']);

    // clone group:
    Route::get('clone/{transactionGroup}', ['uses' => 'Transaction\CloneController@clone', 'as' => 'clone']);

    //Route::get('debug/{tj}', ['uses' => 'Transaction\SingleController@debugShow', 'as' => 'debug']);
    //Route::get('debug/{tj}', ['uses' => 'Transaction\SingleController@debugShow', 'as' => 'debug']);

    Route::post('reorder', ['uses' => 'TransactionController@reorder', 'as' => 'reorder']);
    Route::post('reconcile', ['uses' => 'TransactionController@reconcile', 'as' => 'reconcile']);
    // TODO end of improvement.


    Route::get('show/{transactionGroup}', ['uses' => 'Transaction\ShowController@show', 'as' => 'show']);
}
);

/**
 * Transaction Single Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions', 'as' => 'transactions.'],
    function () {
        // TODO improve these routes

        //Route::get('edit/{tj}', ['uses' => 'SingleController@edit', 'as' => 'edit']);
        //
        //Route::post('store', ['uses' => 'SingleController@store', 'as' => 'store'])->where(['what' => 'withdrawal|deposit|transfer']);
        //Route::post('update/{tj}', ['uses' => 'SingleController@update', 'as' => 'update']);
        //
        //Route::get('clone/{tj}', ['uses' => 'SingleController@cloneTransaction', 'as' => 'clone']);
        //Route::get('{tj}/{type}', ['uses' => 'ConvertController@index', 'as' => 'convert']);
        // TODO end of improvement.
    }
);

/**
 * Transaction Mass Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/mass', 'as' => 'transactions.mass.'],
    function () {
        Route::get('edit/{journalList}', ['uses' => 'MassController@edit', 'as' => 'edit']);
        Route::get('delete/{journalList}', ['uses' => 'MassController@delete', 'as' => 'delete']);
        Route::post('update', ['uses' => 'MassController@update', 'as' => 'update']);
        Route::post('destroy', ['uses' => 'MassController@destroy', 'as' => 'destroy']);
    }
);

/**
 * Transaction Bulk Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/bulk', 'as' => 'transactions.bulk.'],
    function () {
        Route::get('edit/{journalList}', ['uses' => 'BulkController@edit', 'as' => 'edit']);
        Route::post('update', ['uses' => 'BulkController@update', 'as' => 'update']);
    }
);

/**
 * Transaction Split Controller
 */
//Route::group(
//    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/split',
//     'as'         => 'transactions.split.'], function () {
//    // TODO improve these routes
//    Route::get('edit/{tj}', ['uses' => 'SplitController@edit', 'as' => 'edit']);
//    Route::post('update/{tj}', ['uses' => 'SplitController@update', 'as' => 'update']);
//    // TODO end of todo.
//
//}
//);

/**
 * Transaction Convert Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/convert',
     'as'         => 'transactions.convert.'], static function () {
    Route::get('{transactionType}/{transactionGroup}', ['uses' => 'ConvertController@index', 'as' => 'index']);
    Route::post('{transactionType}/{transactionGroup}', ['uses' => 'ConvertController@postIndex', 'as' => 'index.post']);
}
);

/**
 * Transaction Link Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Transaction', 'prefix' => 'transactions/link', 'as' => 'transactions.link.'],
    function () {

        Route::get('modal/{tj}', ['uses' => 'LinkController@modal', 'as' => 'modal']);

        // TODO improve this route:
        Route::post('store/{tj}', ['uses' => 'LinkController@store', 'as' => 'store']);
        Route::get('delete/{journalLink}', ['uses' => 'LinkController@delete', 'as' => 'delete']);
        Route::get('switch/{journalLink}', ['uses' => 'LinkController@switchLink', 'as' => 'switch']);

        Route::post('destroy/{journalLink}', ['uses' => 'LinkController@destroy', 'as' => 'destroy']);
    }
);

/**
 * Report Popup Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'FireflyIII\Http\Controllers\Popup', 'prefix' => 'popup', 'as' => 'popup.'], function () {
    Route::get('general', ['uses' => 'ReportController@general', 'as' => 'general']);

}
);

/**
 * For the admin routes, the user must be logged in and have the role of 'owner'
 */
Route::group(
    ['middleware' => 'admin', 'namespace' => 'FireflyIII\Http\Controllers\Admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {

    // admin home
    Route::get('', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::post('test-message', ['uses' => 'HomeController@testMessage', 'as' => 'test-message']);

    // check for updates?
    Route::get('update-check', ['uses' => 'UpdateController@index', 'as' => 'update-check']);
    Route::post('update-check/manual', ['uses' => 'UpdateController@updateCheck', 'as' => 'update-check.manual']);
    Route::post('update-check', ['uses' => 'UpdateController@post', 'as' => 'update-check.post']);

    // user manager
    Route::get('users', ['uses' => 'UserController@index', 'as' => 'users']);
    Route::get('users/edit/{user}', ['uses' => 'UserController@edit', 'as' => 'users.edit']);
    Route::get('users/delete/{user}', ['uses' => 'UserController@delete', 'as' => 'users.delete']);
    Route::get('users/show/{user}', ['uses' => 'UserController@show', 'as' => 'users.show']);

    Route::post('users/update/{user}', ['uses' => 'UserController@update', 'as' => 'users.update']);
    Route::post('users/destroy/{user}', ['uses' => 'UserController@destroy', 'as' => 'users.destroy']);

    // journal links manager
    Route::get('links', ['uses' => 'LinkController@index', 'as' => 'links.index']);
    Route::get('links/create', ['uses' => 'LinkController@create', 'as' => 'links.create']);
    Route::get('links/show/{linkType}', ['uses' => 'LinkController@show', 'as' => 'links.show']);
    Route::get('links/edit/{linkType}', ['uses' => 'LinkController@edit', 'as' => 'links.edit']);
    Route::get('links/delete/{linkType}', ['uses' => 'LinkController@delete', 'as' => 'links.delete']);


    Route::post('links/store', ['uses' => 'LinkController@store', 'as' => 'links.store']);
    Route::post('links/update/{linkType}', ['uses' => 'LinkController@update', 'as' => 'links.update']);
    Route::post('links/destroy/{linkType}', ['uses' => 'LinkController@destroy', 'as' => 'links.destroy']);

    // FF configuration:
    Route::get('configuration', ['uses' => 'ConfigurationController@index', 'as' => 'configuration.index']);
    Route::post('configuration', ['uses' => 'ConfigurationController@postIndex', 'as' => 'configuration.index.post']);

}
);
