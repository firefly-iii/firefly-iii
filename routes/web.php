<?php
/**
 * routes.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);


/**
 * These routes only work when the user is NOT logged in.
 */
Route::group(
    ['middleware' => 'user-not-logged-in'], function () {

    // Authentication Routes...
    Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Auth\LoginController@login');

    // Registration Routes...
    Route::get('/register', ['uses' => 'Auth\RegisterController@showRegistrationForm', 'as' => 'register']);
    Route::post('/register', 'Auth\RegisterController@register');

    // Password Reset Routes...
    Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm');
    Route::post('/password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('/password/reset', 'Auth\ResetPasswordController@reset');
    Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm');


}
);

/**
 * For some other routes, it is only relevant that the user is authenticated.
 */
Route::group(
    ['middleware' => 'user-simple-auth'], function () {
    Route::get('/error', 'HomeController@displayError');
    Route::any('logout', ['uses' => 'Auth\LoginController@logout', 'as' => 'logout']);
    Route::get('/flush', ['uses' => 'HomeController@flush']);
}
);

/**
 * For the two factor routes, the user must be logged in, but NOT 2FA. Account confirmation does not matter here.
 */
Route::group(
    ['middleware' => 'user-logged-in-no-2fa'], function () {
    Route::get('/two-factor', ['uses' => 'Auth\TwoFactorController@index', 'as' => 'two-factor']);
    Route::get('/lost-two-factor', ['uses' => 'Auth\TwoFactorController@lostTwoFactor', 'as' => 'lost-two-factor']);
    Route::post('/two-factor', ['uses' => 'Auth\TwoFactorController@postIndex', 'as' => 'two-factor-post']);

}
);

/**
 * For the confirmation routes, the user must be logged in, also 2FA, but his account must not be confirmed.
 */
Route::group(
    ['middleware' => 'user-logged-in-2fa-no-activation'], function () {
    //
    Route::get('/confirm-your-account', ['uses' => 'Auth\ConfirmationController@confirmationError', 'as' => 'confirmation_error']);
    Route::get('/resend-confirmation', ['uses' => 'Auth\ConfirmationController@resendConfirmation', 'as' => 'resend_confirmation']);
    Route::get('/confirmation/{code}', ['uses' => 'Auth\ConfirmationController@doConfirmation', 'as' => 'do_confirm_account']);

}
);

/**
 * For all other routes, the user must be fully authenticated and have an activated account.
 */
Route::group(
    ['middleware' => ['user-full-auth']], function () {

    /**
     * Home Controller
     */
    Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::get('/flash', ['uses' => 'HomeController@testFlash', 'as' => 'testFlash']);
    Route::get('/home', ['uses' => 'HomeController@index', 'as' => 'home']);
    Route::post('/daterange', ['uses' => 'HomeController@dateRange', 'as' => 'daterange']);
    Route::get('/routes', ['uses' => 'HomeController@routes']);
    /**
     * Account Controller
     */
    Route::get('/accounts/{what}', ['uses' => 'AccountController@index', 'as' => 'accounts.index'])->where('what', 'revenue|asset|expense');
    Route::get('/accounts/create/{what}', ['uses' => 'AccountController@create', 'as' => 'accounts.create'])->where('what', 'revenue|asset|expense');
    Route::get('/accounts/edit/{account}', ['uses' => 'AccountController@edit', 'as' => 'accounts.edit']);
    Route::get('/accounts/delete/{account}', ['uses' => 'AccountController@delete', 'as' => 'accounts.delete']);
    Route::get('/accounts/show/{account}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);
    Route::get('/accounts/show/{account}/{date}', ['uses' => 'AccountController@showWithDate', 'as' => 'accounts.show.date']);


    Route::post('/accounts/store', ['uses' => 'AccountController@store', 'as' => 'accounts.store']);
    Route::post('/accounts/update/{account}', ['uses' => 'AccountController@update', 'as' => 'accounts.update']);
    Route::post('/accounts/destroy/{account}', ['uses' => 'AccountController@destroy', 'as' => 'accounts.destroy']);


    /**
     * Attachment Controller
     */

    Route::post('/attachment/update/{attachment}', ['uses' => 'AttachmentController@update', 'as' => 'attachments.update']);
    Route::post('/attachment/destroy/{attachment}', ['uses' => 'AttachmentController@destroy', 'as' => 'attachments.destroy']);

    Route::get('/attachment/edit/{attachment}', ['uses' => 'AttachmentController@edit', 'as' => 'attachments.edit']);
    Route::get('/attachment/delete/{attachment}', ['uses' => 'AttachmentController@delete', 'as' => 'attachments.delete']);
    Route::get('/attachment/preview/{attachment}', ['uses' => 'AttachmentController@preview', 'as' => 'attachments.preview']);
    Route::get('/attachment/download/{attachment}', ['uses' => 'AttachmentController@download', 'as' => 'attachments.download']);


    /**
     * Bills Controller
     */
    Route::get('/bills', ['uses' => 'BillController@index', 'as' => 'bills.index']);
    Route::get('/bills/rescan/{bill}', ['uses' => 'BillController@rescan', 'as' => 'bills.rescan']);
    Route::get('/bills/create', ['uses' => 'BillController@create', 'as' => 'bills.create']);
    Route::get('/bills/edit/{bill}', ['uses' => 'BillController@edit', 'as' => 'bills.edit']);
    Route::get('/bills/delete/{bill}', ['uses' => 'BillController@delete', 'as' => 'bills.delete']);
    Route::get('/bills/show/{bill}', ['uses' => 'BillController@show', 'as' => 'bills.show']);
    Route::post('/bills/store', ['uses' => 'BillController@store', 'as' => 'bills.store']);
    Route::post('/bills/update/{bill}', ['uses' => 'BillController@update', 'as' => 'bills.update']);
    Route::post('/bills/destroy/{bill}', ['uses' => 'BillController@destroy', 'as' => 'bills.destroy']);

    /**
     * Budget Controller
     */
    Route::get('/budgets', ['uses' => 'BudgetController@index', 'as' => 'budgets.index']);
    Route::get('/budgets/income', ['uses' => 'BudgetController@updateIncome', 'as' => 'budgets.income']);
    Route::get('/budgets/create', ['uses' => 'BudgetController@create', 'as' => 'budgets.create']);
    Route::get('/budgets/edit/{budget}', ['uses' => 'BudgetController@edit', 'as' => 'budgets.edit']);
    Route::get('/budgets/delete/{budget}', ['uses' => 'BudgetController@delete', 'as' => 'budgets.delete']);
    Route::get('/budgets/show/{budget}', ['uses' => 'BudgetController@show', 'as' => 'budgets.show']);
    Route::get('/budgets/show/{budget}/{limitrepetition}', ['uses' => 'BudgetController@showWithRepetition', 'as' => 'budgets.showWithRepetition']);
    Route::get('/budgets/list/noBudget', ['uses' => 'BudgetController@noBudget', 'as' => 'budgets.noBudget']);
    Route::post('/budgets/income', ['uses' => 'BudgetController@postUpdateIncome', 'as' => 'budgets.postIncome']);
    Route::post('/budgets/store', ['uses' => 'BudgetController@store', 'as' => 'budgets.store']);
    Route::post('/budgets/update/{budget}', ['uses' => 'BudgetController@update', 'as' => 'budgets.update']);
    Route::post('/budgets/destroy/{budget}', ['uses' => 'BudgetController@destroy', 'as' => 'budgets.destroy']);
    Route::post('budgets/amount/{budget}', ['uses' => 'BudgetController@amount']);

    /**
     * Category Controller
     */
    Route::get('/categories', ['uses' => 'CategoryController@index', 'as' => 'categories.index']);
    Route::get('/categories/create', ['uses' => 'CategoryController@create', 'as' => 'categories.create']);
    Route::get('/categories/edit/{category}', ['uses' => 'CategoryController@edit', 'as' => 'categories.edit']);
    Route::get('/categories/delete/{category}', ['uses' => 'CategoryController@delete', 'as' => 'categories.delete']);
    Route::get('/categories/show/{category}', ['uses' => 'CategoryController@show', 'as' => 'categories.show']);
    Route::get('/categories/show/{category}/{date}', ['uses' => 'CategoryController@showWithDate', 'as' => 'categories.show.date']);
    Route::get('/categories/list/noCategory', ['uses' => 'CategoryController@noCategory', 'as' => 'categories.noCategory']);
    Route::post('/categories/store', ['uses' => 'CategoryController@store', 'as' => 'categories.store']);
    Route::post('/categories/update/{category}', ['uses' => 'CategoryController@update', 'as' => 'categories.update']);
    Route::post('/categories/destroy/{category}', ['uses' => 'CategoryController@destroy', 'as' => 'categories.destroy']);

    /**
     * Currency Controller
     */
    Route::get('/currency', ['uses' => 'CurrencyController@index', 'as' => 'currency.index']);
    Route::get('/currency/create', ['uses' => 'CurrencyController@create', 'as' => 'currency.create']);
    Route::get('/currency/edit/{currency}', ['uses' => 'CurrencyController@edit', 'as' => 'currency.edit']);
    Route::get('/currency/delete/{currency}', ['uses' => 'CurrencyController@delete', 'as' => 'currency.delete']);
    Route::get('/currency/default/{currency}', ['uses' => 'CurrencyController@defaultCurrency', 'as' => 'currency.default']);
    Route::post('/currency/store', ['uses' => 'CurrencyController@store', 'as' => 'currency.store']);
    Route::post('/currency/update/{currency}', ['uses' => 'CurrencyController@update', 'as' => 'currency.update']);
    Route::post('/currency/destroy/{currency}', ['uses' => 'CurrencyController@destroy', 'as' => 'currency.destroy']);

    /**
     * Export Controller
     */
    Route::get('/export', ['uses' => 'ExportController@index', 'as' => 'export.index']);
    Route::post('/export/submit', ['uses' => 'ExportController@postIndex', 'as' => 'export.export']);
    Route::get('/export/status/{jobKey}', ['uses' => 'ExportController@getStatus', 'as' => 'export.status']);
    Route::get('/export/download/{jobKey}', ['uses' => 'ExportController@download', 'as' => 'export.download']);


    /**
     * ALL CHART Controllers
     */
    // accounts:
    Route::get('/chart/account/frontpage', ['uses' => 'Chart\AccountController@frontpage']);
    Route::get('/chart/account/expense', ['uses' => 'Chart\AccountController@expenseAccounts']);
    Route::get('/chart/account/revenue', ['uses' => 'Chart\AccountController@revenueAccounts']);
    Route::get('/chart/account/report/default/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\AccountController@report']);
    Route::get('/chart/account/{account}', ['uses' => 'Chart\AccountController@single']);
    Route::get('/chart/account/{account}/{date}', ['uses' => 'Chart\AccountController@specificPeriod']);


    // bills:
    Route::get('/chart/bill/frontpage', ['uses' => 'Chart\BillController@frontpage']);
    Route::get('/chart/bill/{bill}', ['uses' => 'Chart\BillController@single']);

    // budgets:
    Route::get('/chart/budget/frontpage', ['uses' => 'Chart\BudgetController@frontpage']);
    Route::get('/chart/budget/period/{budget}/default/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\BudgetController@period']);

    // this chart is used in reports:
    Route::get('/chart/budget/{budget}/{limitrepetition}', ['uses' => 'Chart\BudgetController@budgetLimit']);
    Route::get('/chart/budget/{budget}', ['uses' => 'Chart\BudgetController@budget']);

    // categories:
    Route::get('/chart/category/frontpage', ['uses' => 'Chart\CategoryController@frontpage']);

    Route::get('/chart/category/{category}/period', ['uses' => 'Chart\CategoryController@currentPeriod']);
    Route::get('/chart/category/{category}/period/{date}', ['uses' => 'Chart\CategoryController@specificPeriod']);
    Route::get('/chart/category/{category}/all', ['uses' => 'Chart\CategoryController@all']);

    // piggy banks:
    Route::get('/chart/piggy-bank/{piggyBank}', ['uses' => 'Chart\PiggyBankController@history']);

    // reports:
    Route::get('/chart/report/in-out/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\ReportController@yearInOut']);
    Route::get('/chart/report/in-out-sum/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\ReportController@yearInOutSummarized']);
    Route::get('/chart/report/net-worth/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\ReportController@netWorth']);


    /**
     * IMPORT CONTROLLER
     */
    Route::get('/import', ['uses' => 'ImportController@index', 'as' => 'import.index']);
    Route::post('/import/upload', ['uses' => 'ImportController@upload', 'as' => 'import.upload']);
    Route::get('/import/configure/{importJob}', ['uses' => 'ImportController@configure', 'as' => 'import.configure']);
    Route::post('/import/configure/{importJob}', ['uses' => 'ImportController@postConfigure', 'as' => 'import.process_configuration']);
    Route::get('/import/settings/{importJob}', ['uses' => 'ImportController@settings', 'as' => 'import.settings']);
    Route::post('/import/settings/{importJob}', ['uses' => 'ImportController@postSettings', 'as' => 'import.postSettings']);
    Route::get('/import/complete/{importJob}', ['uses' => 'ImportController@complete', 'as' => 'import.complete']);
    Route::get('/import/download/{importJob}', ['uses' => 'ImportController@download', 'as' => 'import.download']);

    Route::get('/import/status/{importJob}', ['uses' => 'ImportController@status', 'as' => 'import.status']);
    Route::get('/import/json/{importJob}', ['uses' => 'ImportController@json', 'as' => 'import.json']);

    Route::post('/import/start/{importJob}', ['uses' => 'ImportController@start', 'as' => 'import.start']);
    Route::get('/import/finished/{importJob}', ['uses' => 'ImportController@finished', 'as' => 'import.finished']);

    /**
     * Help Controller
     */
    Route::get('/help/{route}', ['uses' => 'HelpController@show', 'as' => 'help.show']);

    /**
     * JSON Controller
     */
    Route::get('/json/expense-accounts', ['uses' => 'JsonController@expenseAccounts', 'as' => 'json.expense-accounts']);
    Route::get('/json/revenue-accounts', ['uses' => 'JsonController@revenueAccounts', 'as' => 'json.revenue-accounts']);
    Route::get('/json/categories', ['uses' => 'JsonController@categories', 'as' => 'json.categories']);
    Route::get('/json/tags', ['uses' => 'JsonController@tags', 'as' => 'json.tags']);
    Route::get('/json/tour', ['uses' => 'JsonController@tour', 'as' => 'json.tour']);
    Route::post('/json/end-tour', ['uses' => 'JsonController@endTour']);

    Route::get('/json/box/in', ['uses' => 'JsonController@boxIn', 'as' => 'json.box.in']);
    Route::get('/json/box/out', ['uses' => 'JsonController@boxOut', 'as' => 'json.box.out']);
    Route::get('/json/box/bills-unpaid', ['uses' => 'JsonController@boxBillsUnpaid', 'as' => 'json.box.paid']);
    Route::get('/json/box/bills-paid', ['uses' => 'JsonController@boxBillsPaid', 'as' => 'json.box.unpaid']);
    Route::get('/json/transaction-journals/{what}', 'JsonController@transactionJournals');

    Route::get('/json/trigger', ['uses' => 'JsonController@trigger', 'as' => 'json.trigger']);
    Route::get('/json/action', ['uses' => 'JsonController@action', 'as' => 'json.action']);

    /**
     * New user Controller
     */
    Route::get('/new-user', ['uses' => 'NewUserController@index', 'as' => 'new-user.index']);
    Route::post('/new-user/submit', ['uses' => 'NewUserController@submit', 'as' => 'new-user.submit']);

    /**
     * Piggy Bank Controller
     */
    Route::get('/piggy-banks', ['uses' => 'PiggyBankController@index', 'as' => 'piggy-banks.index']);
    Route::get('/piggy-banks/add/{piggyBank}', ['uses' => 'PiggyBankController@add', 'as' => 'piggy-banks.addMoney']);
    Route::get('/piggy-banks/remove/{piggyBank}', ['uses' => 'PiggyBankController@remove', 'as' => 'piggy-banks.removeMoney']);
    Route::get('/piggy-banks/add-money/{piggyBank}', ['uses' => 'PiggyBankController@addMobile', 'as' => 'piggy-banks.add-money-mobile']);
    Route::get('/piggy-banks/remove-money/{piggyBank}', ['uses' => 'PiggyBankController@removeMobile', 'as' => 'piggy-banks.remove-money-mobile']);
    Route::get('/piggy-banks/create', ['uses' => 'PiggyBankController@create', 'as' => 'piggy-banks.create']);
    Route::get('/piggy-banks/edit/{piggyBank}', ['uses' => 'PiggyBankController@edit', 'as' => 'piggy-banks.edit']);
    Route::get('/piggy-banks/delete/{piggyBank}', ['uses' => 'PiggyBankController@delete', 'as' => 'piggy-banks.delete']);
    Route::get('/piggy-banks/show/{piggyBank}', ['uses' => 'PiggyBankController@show', 'as' => 'piggy-banks.show']);
    Route::post('/piggy-banks/store', ['uses' => 'PiggyBankController@store', 'as' => 'piggy-banks.store']);
    Route::post('/piggy-banks/update/{piggyBank}', ['uses' => 'PiggyBankController@update', 'as' => 'piggy-banks.update']);
    Route::post('/piggy-banks/destroy/{piggyBank}', ['uses' => 'PiggyBankController@destroy', 'as' => 'piggy-banks.destroy']);
    Route::post('/piggy-banks/add/{piggyBank}', ['uses' => 'PiggyBankController@postAdd', 'as' => 'piggy-banks.add']);
    Route::post('/piggy-banks/remove/{piggyBank}', ['uses' => 'PiggyBankController@postRemove', 'as' => 'piggy-banks.remove']);
    Route::post('/piggy-banks/sort', ['uses' => 'PiggyBankController@order', 'as' => 'piggy-banks.order']);

    /**
     * Preferences Controller
     */
    Route::get('/preferences', ['uses' => 'PreferencesController@index', 'as' => 'preferences']);
    Route::post('/preferences', ['uses' => 'PreferencesController@postIndex', 'as' => 'preferences.update']);
    Route::get('/preferences/code', ['uses' => 'PreferencesController@code', 'as' => 'preferences.code']);
    Route::get('/preferences/delete-code', ['uses' => 'PreferencesController@deleteCode', 'as' => 'preferences.delete-code']);
    Route::post('/preferences/code', ['uses' => 'PreferencesController@postCode', 'as' => 'preferences.code.store']);

    /**
     * Profile Controller
     */
    Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
    Route::get('/profile/change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'profile.change-password']);
    Route::get('/profile/delete-account', ['uses' => 'ProfileController@deleteAccount', 'as' => 'profile.delete-account']);
    Route::post('/profile/delete-account', ['uses' => 'ProfileController@postDeleteAccount', 'as' => 'profile.delete-account.post']);
    Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword', 'as' => 'profile.change-password.store']);

    /**
     * Report Controller
     */
    Route::get('/reports', ['uses' => 'ReportController@index', 'as' => 'reports.index']);
    Route::get('/reports/report/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@report', 'as' => 'reports.report']);
    Route::get('/reports/options/{reportType}', ['uses' => 'ReportController@options', 'as' => 'reports.options']);

    /**
     * Report AJAX data Controller:
     */
    // account report
    Route::get(
        '/reports/data/account-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\AccountController@accountReport', 'as' => 'reports.data.accountReport']
    );

    // income and expenses report
    Route::get(
        '/reports/data/inc-exp-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\InOutController@incExpReport', 'as' => 'reports.data.incExpReport']
    );
    // (income report):
    Route::get(
        '/reports/data/income-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\InOutController@incomeReport', 'as' => 'reports.data.incomeReport']
    );
    // (expense report):
    Route::get(
        '/reports/data/expense-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\InOutController@expenseReport', 'as' => 'reports.data.expenseReport']
    );

    // category report:
    Route::get(
        '/reports/data/category-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\CategoryController@categoryReport', 'as' => 'reports.data.categoryReport']
    );

    // balance report:
    Route::get(
        '/reports/data/balance-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\BalanceController@balanceReport', 'as' => 'reports.data.balanceReport']
    );

    // budget report:
    Route::get(
        '/reports/data/budget-report/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\BudgetController@budgetReport', 'as' => 'reports.data.budgetReport']
    );
    // budget year overview
    Route::get(
        '/reports/data/budget-year-overview/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\BudgetController@budgetYearOverview', 'as' => 'reports.data.budgetYearOverview']
    );

    // budget multi year overview
    Route::get(
        '/reports/data/budget-multi-year/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\BudgetController@budgetMultiYear', 'as' => 'reports.data.budgetMultiYear']
    );


    /**
     * Rules Controller
     */
    // index
    Route::get('/rules', ['uses' => 'RuleController@index', 'as' => 'rules.index']);

    // rules GET:
    Route::get('/rules/create/{ruleGroup}', ['uses' => 'RuleController@create', 'as' => 'rules.rule.create']);
    Route::get('/rules/up/{rule}', ['uses' => 'RuleController@up', 'as' => 'rules.rule.up']);
    Route::get('/rules/down/{rule}', ['uses' => 'RuleController@down', 'as' => 'rules.rule.down']);
    Route::get('/rules/edit/{rule}', ['uses' => 'RuleController@edit', 'as' => 'rules.rule.edit']);
    Route::get('/rules/delete/{rule}', ['uses' => 'RuleController@delete', 'as' => 'rules.rule.delete']);
    Route::get('/rules/test', ['uses' => 'RuleController@testTriggers', 'as' => 'rules.rule.test_triggers']);

    // rules POST:
    Route::post('/rules/trigger/order/{rule}', ['uses' => 'RuleController@reorderRuleTriggers']);
    Route::post('/rules/action/order/{rule}', ['uses' => 'RuleController@reorderRuleActions']);
    Route::post('/rules/store/{ruleGroup}', ['uses' => 'RuleController@store', 'as' => 'rules.rule.store']);
    Route::post('/rules/update/{rule}', ['uses' => 'RuleController@update', 'as' => 'rules.rule.update']);
    Route::post('/rules/destroy/{rule}', ['uses' => 'RuleController@destroy', 'as' => 'rules.rule.destroy']);


    // rule groups GET
    Route::get('/rule-groups/create', ['uses' => 'RuleGroupController@create', 'as' => 'rules.rule-group.create']);
    Route::get('/rule-groups/edit/{ruleGroup}', ['uses' => 'RuleGroupController@edit', 'as' => 'rules.rule-group.edit']);
    Route::get('/rule-groups/delete/{ruleGroup}', ['uses' => 'RuleGroupController@delete', 'as' => 'rules.rule-group.delete']);
    Route::get('/rule-groups/up/{ruleGroup}', ['uses' => 'RuleGroupController@up', 'as' => 'rules.rule-group.up']);
    Route::get('/rule-groups/down/{ruleGroup}', ['uses' => 'RuleGroupController@down', 'as' => 'rules.rule-group.down']);
    Route::get('/rule-groups/select/{ruleGroup}', ['uses' => 'RuleGroupController@selectTransactions', 'as' => 'rules.rule-group.select_transactions']);

    // rule groups POST
    Route::post('/rule-groups/store', ['uses' => 'RuleGroupController@store', 'as' => 'rules.rule-group.store']);
    Route::post('/rule-groups/update/{ruleGroup}', ['uses' => 'RuleGroupController@update', 'as' => 'rules.rule-group.update']);
    Route::post('/rule-groups/destroy/{ruleGroup}', ['uses' => 'RuleGroupController@destroy', 'as' => 'rules.rule-group.destroy']);
    Route::post('/rule-groups/execute/{ruleGroup}', ['uses' => 'RuleGroupController@execute', 'as' => 'rules.rule-group.execute']);

    /**
     * Search Controller
     */
    Route::get('/search', ['uses' => 'SearchController@index', 'as' => 'search']);

    /**
     * Tag Controller
     */
    Route::get('/tags', ['uses' => 'TagController@index', 'as' => 'tags.index']);
    Route::get('/tags/create', ['uses' => 'TagController@create', 'as' => 'tags.create']);
    Route::get('/tags/show/{tag}', ['uses' => 'TagController@show', 'as' => 'tags.show']);
    Route::get('/tags/edit/{tag}', ['uses' => 'TagController@edit', 'as' => 'tags.edit']);
    Route::get('/tags/delete/{tag}', ['uses' => 'TagController@delete', 'as' => 'tags.delete']);

    Route::post('/tags/store', ['uses' => 'TagController@store', 'as' => 'tags.store']);
    Route::post('/tags/update/{tag}', ['uses' => 'TagController@update', 'as' => 'tags.update']);
    Route::post('/tags/destroy/{tag}', ['uses' => 'TagController@destroy', 'as' => 'tags.destroy']);

    Route::post('/tags/hideTagHelp/{state}', ['uses' => 'TagController@hideTagHelp', 'as' => 'tags.hideTagHelp']);


    /**
     * Transaction Controller
     */

    // normal controller
    Route::get('/transactions/{what}', ['uses' => 'TransactionController@index', 'as' => 'transactions.index'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::get('/transaction/show/{tj}', ['uses' => 'TransactionController@show', 'as' => 'transactions.show']);
    Route::post('/transaction/reorder', ['uses' => 'TransactionController@reorder', 'as' => 'transactions.reorder']);

    // single controller
    Route::get('/transactions/create/{what}', ['uses' => 'Transaction\SingleController@create', 'as' => 'transactions.create'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::get('/transaction/edit/{tj}', ['uses' => 'Transaction\SingleController@edit', 'as' => 'transactions.edit']);
    Route::get('/transaction/delete/{tj}', ['uses' => 'Transaction\SingleController@delete', 'as' => 'transactions.delete']);
    Route::post('/transactions/store/{what}', ['uses' => 'Transaction\SingleController@store', 'as' => 'transactions.store'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::post('/transaction/update/{tj}', ['uses' => 'Transaction\SingleController@update', 'as' => 'transactions.update']);
    Route::post('/transaction/destroy/{tj}', ['uses' => 'Transaction\SingleController@destroy', 'as' => 'transactions.destroy']);

    // mass controller:
    Route::get('/transactions/mass-edit/{journalList}', ['uses' => 'Transaction\MassController@massEdit', 'as' => 'transactions.mass-edit']);
    Route::get('/transactions/mass-delete/{journalList}', ['uses' => 'Transaction\MassController@massDelete', 'as' => 'transactions.mass-delete']);
    Route::post('/transactions/mass-update', ['uses' => 'Transaction\MassController@massUpdate', 'as' => 'transactions.mass-update']);
    Route::post('/transactions/mass-destroy', ['uses' => 'Transaction\MassController@massDestroy', 'as' => 'transactions.mass-destroy']);

    // split (will be here):
    Route::get('/transaction/split/edit/{tj}', ['uses' => 'Transaction\SplitController@edit', 'as' => 'transactions.edit-split']);
    Route::post('/transaction/split/update/{tj}', ['uses' => 'Transaction\SplitController@update', 'as' => 'split.journal.update']);

    // convert controller:
    Route::get('transactions/convert/{transaction_type}/{tj}', ['uses' => 'Transaction\ConvertController@convert', 'as' => 'transactions.convert']);
    Route::post('transactions/convert/{transaction_type}/{tj}', ['uses' => 'Transaction\ConvertController@submit', 'as' => 'transactions.convert.post']);

    /**
     * POPUP Controllers
     */
    /**
     * Report popup
     */
    Route::get('/popup/report', ['uses' => 'Popup\ReportController@info', 'as' => 'popup.report']);

}
);

/**
 * For the admin routes, the user must be logged in and have the role of 'owner'
 */
Route::group(
    ['middleware' => 'admin'], function () {

    // admin home
    Route::get('/admin', ['uses' => 'Admin\HomeController@index', 'as' => 'admin.index']);

    // user manager
    Route::get('/admin/users', ['uses' => 'Admin\UserController@index', 'as' => 'admin.users']);
    Route::get('/admin/users/edit/{user}', ['uses' => 'Admin\UserController@edit', 'as' => 'admin.users.edit']);
    Route::get('/admin/users/show/{user}', ['uses' => 'Admin\UserController@show', 'as' => 'admin.users.show']);

    // user domains:
    Route::get('/admin/domains', ['uses' => 'Admin\DomainController@domains', 'as' => 'admin.users.domains']);
    Route::get('/admin/domains/toggle/{domain}', ['uses' => 'Admin\DomainController@toggleDomain', 'as' => 'admin.users.domains.block-toggle']);
    Route::post('/admin/domains/manual', ['uses' => 'Admin\DomainController@manual', 'as' => 'admin.users.domains.manual']);

    // FF configuration:
    Route::get('/admin/configuration', ['uses' => 'Admin\ConfigurationController@index', 'as' => 'admin.configuration.index']);
    Route::post('/admin/configuration', ['uses' => 'Admin\ConfigurationController@store', 'as' => 'admin.configuration.store']);

}
);
