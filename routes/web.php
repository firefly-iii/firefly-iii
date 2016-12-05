<?php
/**
 * web.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
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
    Route::get('password/reset/{token}', ['uses' => 'Auth\ResetPasswordController@showResetForm', 'as' => 'password.reset']);
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
    Route::get('/error', ['uses' => 'HomeController@displayError', 'as' => 'displayError']);
    Route::any('logout', ['uses' => 'Auth\LoginController@logout', 'as' => 'logout']);
    Route::get('/flush', ['uses' => 'HomeController@flush', 'as' => 'flush']);
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
    Route::get('/confirm-your-account', ['uses' => 'Auth\ConfirmationController@confirmationError', 'as' => 'confirmation_error']);
    Route::get('/resend-confirmation', ['uses' => 'Auth\ConfirmationController@resendConfirmation', 'as' => 'resend_confirmation']);
    Route::get('/confirmation/{code}', ['uses' => 'Auth\ConfirmationController@doConfirmation', 'as' => 'do_confirm_account']);

}
);

/**
 * For all other routes, the user must be fully authenticated and have an activated account.
 */

/**
 * Home Controller
 */
Route::group(
    ['middleware' => ['user-full-auth']], function () {
    Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::get('/flash', ['uses' => 'HomeController@testFlash', 'as' => 'testFlash']);
    Route::get('/home', ['uses' => 'HomeController@index', 'as' => 'home']);
    Route::post('/daterange', ['uses' => 'HomeController@dateRange', 'as' => 'daterange']);
    Route::get('/routes', ['uses' => 'HomeController@routes', 'as' => 'allRoutes']);
}
);

/**
 * Account Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'accounts', 'as' => 'accounts.'], function () {
    Route::get('{what}', ['uses' => 'AccountController@index', 'as' => 'index'])->where('what', 'revenue|asset|expense');
    Route::get('create/{what}', ['uses' => 'AccountController@create', 'as' => 'create'])->where('what', 'revenue|asset|expense');
    Route::get('edit/{account}', ['uses' => 'AccountController@edit', 'as' => 'edit']);
    Route::get('delete/{account}', ['uses' => 'AccountController@delete', 'as' => 'delete']);
    Route::get('show/{account}', ['uses' => 'AccountController@show', 'as' => 'show']);
    Route::get('show/{account}/all', ['uses' => 'AccountController@showAll', 'as' => 'show.all']);
    Route::get('show/{account}/{date}', ['uses' => 'AccountController@showWithDate', 'as' => 'show.date']);

    Route::post('store', ['uses' => 'AccountController@store', 'as' => 'store']);
    Route::post('update/{account}', ['uses' => 'AccountController@update', 'as' => 'update']);
    Route::post('destroy/{account}', ['uses' => 'AccountController@destroy', 'as' => 'destroy']);

}
);

/**
 * Attachment Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'attachments', 'as' => 'attachments.'], function () {
    Route::get('edit/{attachment}', ['uses' => 'AttachmentController@edit', 'as' => 'edit']);
    Route::get('delete/{attachment}', ['uses' => 'AttachmentController@delete', 'as' => 'delete']);
    Route::get('preview/{attachment}', ['uses' => 'AttachmentController@preview', 'as' => 'preview']);
    Route::get('download/{attachment}', ['uses' => 'AttachmentController@download', 'as' => 'download']);

    Route::post('update/{attachment}', ['uses' => 'AttachmentController@update', 'as' => 'update']);
    Route::post('destroy/{attachment}', ['uses' => 'AttachmentController@destroy', 'as' => 'destroy']);

}
);

/**
 * Bills Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'bills', 'as' => 'bills.'], function () {
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
    ['middleware' => 'user-full-auth', 'prefix' => 'budgets', 'as' => 'budgets.'], function () {
    Route::get('', ['uses' => 'BudgetController@index', 'as' => 'index']);
    Route::get('income', ['uses' => 'BudgetController@updateIncome', 'as' => 'income']);
    Route::get('create', ['uses' => 'BudgetController@create', 'as' => 'create']);
    Route::get('edit/{budget}', ['uses' => 'BudgetController@edit', 'as' => 'edit']);
    Route::get('delete/{budget}', ['uses' => 'BudgetController@delete', 'as' => 'delete']);
    Route::get('show/{budget}', ['uses' => 'BudgetController@show', 'as' => 'show']);
    Route::get('show/{budget}/{limitrepetition}', ['uses' => 'BudgetController@showWithRepetition', 'as' => 'showWithRepetition']);
    Route::get('list/noBudget', ['uses' => 'BudgetController@noBudget', 'as' => 'noBudget']);

    Route::post('income', ['uses' => 'BudgetController@postUpdateIncome', 'as' => 'postIncome']);
    Route::post('store', ['uses' => 'BudgetController@store', 'as' => 'store']);
    Route::post('update/{budget}', ['uses' => 'BudgetController@update', 'as' => 'update']);
    Route::post('destroy/{budget}', ['uses' => 'BudgetController@destroy', 'as' => 'destroy']);
    Route::post('amount/{budget}', ['uses' => 'BudgetController@amount']);
}
);

/**
 * Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'categories', 'as' => 'categories.'], function () {
    Route::get('', ['uses' => 'CategoryController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'CategoryController@create', 'as' => 'create']);
    Route::get('edit/{category}', ['uses' => 'CategoryController@edit', 'as' => 'edit']);
    Route::get('delete/{category}', ['uses' => 'CategoryController@delete', 'as' => 'delete']);

    Route::get('show/{category}', ['uses' => 'CategoryController@show', 'as' => 'show']);
    Route::get('show/{category}/{date}', ['uses' => 'CategoryController@showWithDate', 'as' => 'show.date']);
    Route::get('list/noCategory', ['uses' => 'CategoryController@noCategory', 'as' => 'noCategory']);

    Route::post('store', ['uses' => 'CategoryController@store', 'as' => 'store']);
    Route::post('update/{category}', ['uses' => 'CategoryController@update', 'as' => 'update']);
    Route::post('destroy/{category}', ['uses' => 'CategoryController@destroy', 'as' => 'destroy']);
}
);


/**
 * Currency Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'currencies', 'as' => 'currencies.'], function () {
    Route::get('', ['uses' => 'CurrencyController@index', 'as' => 'index']);
    Route::get('create', ['uses' => 'CurrencyController@create', 'as' => 'create']);
    Route::get('edit/{currency}', ['uses' => 'CurrencyController@edit', 'as' => 'edit']);
    Route::get('delete/{currency}', ['uses' => 'CurrencyController@delete', 'as' => 'delete']);
    Route::get('default/{currency}', ['uses' => 'CurrencyController@defaultCurrency', 'as' => 'default']);

    Route::post('store', ['uses' => 'CurrencyController@store', 'as' => 'store']);
    Route::post('update/{currency}', ['uses' => 'CurrencyController@update', 'as' => 'update']);
    Route::post('destroy/{currency}', ['uses' => 'CurrencyController@destroy', 'as' => 'destroy']);

}
);

/**
 * Export Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'export', 'as' => 'export.'], function () {
    Route::get('', ['uses' => 'ExportController@index', 'as' => 'index']);
    Route::get('status/{jobKey}', ['uses' => 'ExportController@getStatus', 'as' => 'status']);
    Route::get('download/{jobKey}', ['uses' => 'ExportController@download', 'as' => 'download']);

    Route::post('submit', ['uses' => 'ExportController@postIndex', 'as' => 'export']);

}
);

/**
 * Chart\Account Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Chart', 'prefix' => 'chart/account', 'as' => 'chart.account.'], function () {
    Route::get('frontpage', ['uses' => 'AccountController@frontpage', 'as' => 'frontpage']);
    Route::get('expense', ['uses' => 'AccountController@expenseAccounts', 'as' => 'expense']);
    Route::get('revenue', ['uses' => 'AccountController@revenueAccounts', 'as' => 'revenue']);
    Route::get('report/default/{start_date}/{end_date}/{accountList}', ['uses' => 'AccountController@report', 'as' => 'report']);
    Route::get('{account}', ['uses' => 'AccountController@single', 'as' => 'single']);
    Route::get('{account}/{date}', ['uses' => 'AccountController@specificPeriod', 'as' => 'specific-period']);

    Route::get('income-by-category/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@incomeByCategory', 'as' => 'incomeByCategory']);
    Route::get('expense-by-category/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@expenseByCategory', 'as' => 'expenseByCategory']);
    Route::get('expense-by-budget/{account}/{start_date}/{end_date}', ['uses' => 'AccountController@expenseByBudget', 'as' => 'expenseByBudget']);

}
);

/**
 * Chart\Bill Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Chart', 'prefix' => 'chart/bill'], function () {
    Route::get('frontpage', ['uses' => 'BillController@frontpage']);
    Route::get('{bill}', ['uses' => 'BillController@single']);

}
);

/**
 * Chart\Budget Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Chart', 'prefix' => 'chart/budget'], function () {
    Route::get('frontpage', ['uses' => 'BudgetController@frontpage']);
    Route::get('period/0/default/{start_date}/{end_date}/{accountList}', ['uses' => 'BudgetController@periodNoBudget']);
    Route::get('period/{budget}/default/{start_date}/{end_date}/{accountList}', ['uses' => 'BudgetController@period']);
    Route::get('{budget}/{limitrepetition}', ['uses' => 'BudgetController@budgetLimit']);
    Route::get('{budget}', ['uses' => 'BudgetController@budget']);

}
);

/**
 * Chart\Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Chart', 'prefix' => 'chart/category'], function () {
    Route::get('frontpage', ['uses' => 'CategoryController@frontpage']);
    Route::get('{category}/period', ['uses' => 'CategoryController@currentPeriod']);
    Route::get('{category}/period/{date}', ['uses' => 'CategoryController@specificPeriod']);
    Route::get('{category}/all', ['uses' => 'CategoryController@all']);
    Route::get('{category}/report-period/{start_date}/{end_date}/{accountList}', ['uses' => 'CategoryController@reportPeriod']);

    // these charts are used in reports (category reports):
    Route::get('{accountList}/{categoryList}/{start_date}/{end_date}/{others}/income', ['uses' => 'CategoryReportController@categoryIncome']);
    Route::get('{accountList}/{categoryList}/{start_date}/{end_date}/{others}/expense', ['uses' => 'CategoryReportController@categoryExpense']);
    Route::get('{accountList}/{categoryList}/{start_date}/{end_date}/{others}/income', ['uses' => 'CategoryReportController@accountIncome']);
    Route::get('{accountList}/{categoryList}/{start_date}/{end_date}/{others}/expense', ['uses' => 'CategoryReportController@accountExpense']);
    Route::get('report-in-out/{accountList}/{categoryList}/{start_date}/{end_date}', ['uses' => 'CategoryReportController@mainChart']);

}
);

/**
 * Chart\PiggyBank Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Chart', 'prefix' => 'chart/piggy-bank'], function () {
    Route::get('{piggyBank}', ['uses' => 'PiggyBankController@history']);
}
);

/**
 * Chart\Report Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Chart', 'prefix' => 'chart/report'], function () {
    Route::get('in-out/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@yearInOut']);
    Route::get('in-out-sum/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@yearInOutSummarized']);
    Route::get('net-worth/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@netWorth']);

}
);

/**
 * Import Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'import', 'as' => 'import.'], function () {
    Route::get('', ['uses' => 'ImportController@index', 'as' => 'index']);
    Route::get('configure/{importJob}', ['uses' => 'ImportController@configure', 'as' => 'configure']);
    Route::get('settings/{importJob}', ['uses' => 'ImportController@settings', 'as' => 'settings']);
    Route::get('complete/{importJob}', ['uses' => 'ImportController@complete', 'as' => 'complete']);
    Route::get('download/{importJob}', ['uses' => 'ImportController@download', 'as' => 'download']);
    Route::get('status/{importJob}', ['uses' => 'ImportController@status', 'as' => 'status']);
    Route::get('json/{importJob}', ['uses' => 'ImportController@json', 'as' => 'json']);
    Route::get('finished/{importJob}', ['uses' => 'ImportController@finished', 'as' => 'finished']);

    Route::post('upload', ['uses' => 'ImportController@upload', 'as' => 'upload']);
    Route::post('configure/{importJob}', ['uses' => 'ImportController@postConfigure', 'as' => 'process_configuration']);
    Route::post('settings/{importJob}', ['uses' => 'ImportController@postSettings', 'as' => 'postSettings']);
    Route::post('start/{importJob}', ['uses' => 'ImportController@start', 'as' => 'start']);


}
);

/**
 * Help Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'help', 'as' => 'help.'], function () {
    Route::get('{route}', ['uses' => 'HelpController@show', 'as' => 'show']);

}
);

/**
 * JSON Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'json', 'as' => 'admin.'], function () {
    Route::get('expense-accounts', ['uses' => 'JsonController@expenseAccounts', 'as' => 'expense-accounts']);
    Route::get('revenue-accounts', ['uses' => 'JsonController@revenueAccounts', 'as' => 'revenue-accounts']);
    Route::get('categories', ['uses' => 'JsonController@categories', 'as' => 'categories']);
    Route::get('tags', ['uses' => 'JsonController@tags', 'as' => 'tags']);
    Route::get('tour', ['uses' => 'JsonController@tour', 'as' => 'tour']);
    Route::get('box/in', ['uses' => 'JsonController@boxIn', 'as' => 'box.in']);
    Route::get('box/out', ['uses' => 'JsonController@boxOut', 'as' => 'box.out']);
    Route::get('box/bills-unpaid', ['uses' => 'JsonController@boxBillsUnpaid', 'as' => 'box.paid']);
    Route::get('box/bills-paid', ['uses' => 'JsonController@boxBillsPaid', 'as' => 'box.unpaid']);
    Route::get('transaction-journals/{what}', 'JsonController@transactionJournals');
    Route::get('trigger', ['uses' => 'JsonController@trigger', 'as' => 'trigger']);
    Route::get('action', ['uses' => 'JsonController@action', 'as' => 'action']);

    Route::post('end-tour', ['uses' => 'JsonController@endTour']);

}
);


/**
 * NewUser Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'new-user', 'as' => 'new-user.'], function () {
    Route::get('', ['uses' => 'NewUserController@index', 'as' => 'index']);
    Route::post('submit', ['uses' => 'NewUserController@submit', 'as' => 'submit']);
}
);

/**
 * Piggy Bank Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'piggy-banks', 'as' => 'piggy-banks.'], function () {
    Route::get('', ['uses' => 'PiggyBankController@index', 'as' => 'index']);
    Route::get('add/{piggyBank}', ['uses' => 'PiggyBankController@add', 'as' => 'addMoney']);
    Route::get('remove/{piggyBank}', ['uses' => 'PiggyBankController@remove', 'as' => 'removeMoney']);
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
    Route::post('sort', ['uses' => 'PiggyBankController@order', 'as' => 'order']);


}
);


/**
 * Name Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'preferences', 'as' => 'preferences.'], function () {
    Route::get('', ['uses' => 'PreferencesController@index', 'as' => 'index']);
    Route::get('/code', ['uses' => 'PreferencesController@code', 'as' => 'code']);
    Route::get('/delete-code', ['uses' => 'PreferencesController@deleteCode', 'as' => 'delete-code']);
    Route::post('', ['uses' => 'PreferencesController@postIndex', 'as' => 'update']);
    Route::post('/code', ['uses' => 'PreferencesController@postCode', 'as' => 'code.store']);

}
);

/**
 * Profile Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'profile', 'as' => 'profile.'], function () {

    Route::get('', ['uses' => 'ProfileController@index', 'as' => 'index']);
    Route::get('change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);
    Route::get('delete-account', ['uses' => 'ProfileController@deleteAccount', 'as' => 'delete-account']);
    Route::post('delete-account', ['uses' => 'ProfileController@postDeleteAccount', 'as' => 'delete-account.post']);
    Route::post('change-password', ['uses' => 'ProfileController@postChangePassword', 'as' => 'change-password.store']);
}
);

/**
 * Name Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'prefix' => 'reports', 'as' => 'reports.'], function () {

    Route::get('', ['uses' => 'ReportController@index', 'as' => 'index']);


    Route::get('options/{reportType}', ['uses' => 'ReportController@options', 'as' => 'options']);
    Route::get('default/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@default', 'as' => 'report.default']);
    Route::get('audit/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@audit', 'as' => 'report.audit']);
    Route::get('category/{start_date}/{end_date}/{accountList}/{categoryList}', ['uses' => 'ReportController@category', 'as' => 'report.category']);

    Route::post('', ['uses' => 'ReportController@postIndex', 'as' => 'index.post']);
}
);

/**
 * Report Data AccountController
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Report', 'prefix' => 'report-data/account', 'as' => 'report-data.account'], function () {
    Route::get('general/{start_date}/{end_date}/{accountList}', ['uses' => 'AccountController@general', 'as' => 'general']);
}
);

/**
 * Report Data Income/Expenses Controller (called financial operations)
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Report', 'prefix' => 'report-data/operations', 'as' => 'report-data.operations'], function () {
    Route::get('operations/{start_date}/{end_date}/{accountList}', ['uses' => 'OperationsController@operations', 'as' => 'operations']);
    Route::get('income/{start_date}/{end_date}/{accountList}', ['uses' => 'OperationsController@income', 'as' => 'income']);
    Route::get('expenses/{start_date}/{end_date}/{accountList}', ['uses' => 'OperationsController@expenses', 'as' => 'expenses']);

}
);

/**
 * Report Data Category Controller
 */
Route::group(
    ['middleware' => 'user-full-auth', 'namespace' => 'Report', 'prefix' => 'report-data/category', 'as' => 'report-data.category.'], function () {
    Route::get('operations/{start_date}/{end_date}/{accountList}', ['uses' => 'CategoryController@operations', 'as' => 'operations']);
    Route::get('income/{start_date}/{end_date}/{accountList}', ['uses' => 'CategoryController@income', 'as' => 'income']);
    Route::get('expenses/{start_date}/{end_date}/{accountList}', ['uses' => 'CategoryController@expenses', 'as' => 'expense']);

}
);


Route::group(
    ['middleware' => ['user-full-auth']], function () {


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

    // budget period overview
    Route::get(
        '/reports/data/budget-period/{start_date}/{end_date}/{accountList}',
        ['uses' => 'Report\BudgetController@budgetPeriodReport', 'as' => 'reports.data.budgetPeriodReport']
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

    // normal controller: index for session range
    Route::get('/transactions/{what}', ['uses' => 'TransactionController@index', 'as' => 'transactions.index'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );

    // normal controller: index showing ALL:
    Route::get('/transactions/{what}/all', ['uses' => 'TransactionController@indexAll', 'as' => 'transactions.index.all'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );

    // normal controller: index for specific date range:
    Route::get('/transactions/{what}/{date}', ['uses' => 'TransactionController@indexDate', 'as' => 'transactions.index.date'])->where(
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
    ['middleware' => 'admin', 'namespace' => 'Admin', 'prefix' => 'admin', 'as' => 'admin.'], function () {

    // admin home
    Route::get('', ['uses' => 'HomeController@index', 'as' => 'index']);

    // user manager
    Route::get('users', ['uses' => 'UserController@index', 'as' => 'users']);
    Route::get('users/edit/{user}', ['uses' => 'UserController@edit', 'as' => 'users.edit']);
    Route::get('users/show/{user}', ['uses' => 'UserController@show', 'as' => 'users.show']);

    // user domain manager
    Route::get('domains', ['uses' => 'DomainController@domains', 'as' => 'users.domains']);
    Route::get('domains/toggle/{domain}', ['uses' => 'DomainController@toggleDomain', 'as' => 'users.domains.block-toggle']);
    Route::post('domains/manual', ['uses' => 'DomainController@manual', 'as' => 'users.domains.manual']);

    // FF configuration:
    Route::get('configuration', ['uses' => 'ConfigurationController@index', 'as' => 'configuration.index']);
    Route::post('configuration', ['uses' => 'ConfigurationController@store', 'as' => 'configuration.store']);

}
);
