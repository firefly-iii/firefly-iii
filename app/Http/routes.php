<?php

// auth routes, i think
Route::group(
    ['middleware' => 'web'], function () {

    // Authentication Routes...
    Route::get('/login', 'Auth\AuthController@showLoginForm');
    Route::post('/login', 'Auth\AuthController@login');
    Route::get('/logout', 'Auth\AuthController@logout');

    // Registration Routes...
    Route::get('/register', ['uses' => 'Auth\AuthController@showRegistrationForm', 'as' => 'register']);
    Route::post('/register', 'Auth\AuthController@register');

    Route::get('/password/reset', 'Auth\PasswordController@getReset');

    // Password Reset Routes...
    Route::get('/password/reset/{token?}', 'Auth\PasswordController@showResetForm');
    Route::post('/password/email', 'Auth\PasswordController@sendResetLinkEmail');
    Route::post('/password/reset', 'Auth\PasswordController@reset');


}
);


Route::group(
    ['middleware' => ['web-auth-range']], function () {

    /**
     * Home Controller
     */
    Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::get('/home', ['uses' => 'HomeController@index', 'as' => 'home']);
    Route::post('/daterange', ['uses' => 'HomeController@dateRange', 'as' => 'daterange']);
    Route::get('/flush', ['uses' => 'HomeController@flush', 'as' => 'flush']);
    /**
     * Account Controller
     */
    Route::get('/accounts/{what}', ['uses' => 'AccountController@index', 'as' => 'accounts.index'])->where('what', 'revenue|asset|expense');
    Route::get('/accounts/create/{what}', ['uses' => 'AccountController@create', 'as' => 'accounts.create'])->where('what', 'revenue|asset|expense');
    Route::get('/accounts/edit/{account}', ['uses' => 'AccountController@edit', 'as' => 'accounts.edit']);
    Route::get('/accounts/delete/{account}', ['uses' => 'AccountController@delete', 'as' => 'accounts.delete']);
    Route::get('/accounts/show/{account}/{view?}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);

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
    Route::get('/attachment/show/{attachment}', ['uses' => 'AttachmentController@show', 'as' => 'attachments.show']);
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
    Route::get('/budgets/show/{budget}/{limitrepetition?}', ['uses' => 'BudgetController@show', 'as' => 'budgets.show']);
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
     * CSV controller
     */
    Route::get('/csv', ['uses' => 'CsvController@index', 'as' => 'csv.index']);
    Route::post('/csv/upload', ['uses' => 'CsvController@upload', 'as' => 'csv.upload']);
    Route::get('/csv/column_roles', ['uses' => 'CsvController@columnRoles', 'as' => 'csv.column-roles']);
    Route::post('/csv/initial_parse', ['uses' => 'CsvController@initialParse', 'as' => 'csv.initial_parse']);
    Route::get('/csv/map', ['uses' => 'CsvController@map', 'as' => 'csv.map']);
    Route::get('/csv/download-config', ['uses' => 'CsvController@downloadConfig', 'as' => 'csv.download-config']);
    Route::get('/csv/download', ['uses' => 'CsvController@downloadConfigPage', 'as' => 'csv.download-config-page']);
    Route::post('/csv/save_mapping', ['uses' => 'CsvController@saveMapping', 'as' => 'csv.save_mapping']);

    Route::get('/csv/process', ['uses' => 'CsvController@process', 'as' => 'csv.process']);

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
     * ALL CHART Controllers
     */
    // accounts:
    Route::get('/chart/account/frontpage', ['uses' => 'Chart\AccountController@frontpage']);
    Route::get('/chart/account/expense', ['uses' => 'Chart\AccountController@expenseAccounts']);
    Route::get('/chart/account/report/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\AccountController@report']);
    Route::get('/chart/account/{account}', ['uses' => 'Chart\AccountController@single']);


    // bills:
    Route::get('/chart/bill/frontpage', ['uses' => 'Chart\BillController@frontpage']);
    Route::get('/chart/bill/{bill}', ['uses' => 'Chart\BillController@single']);

    // budgets:
    Route::get('/chart/budget/frontpage', ['uses' => 'Chart\BudgetController@frontpage']);

    // this chart is used in reports:
    Route::get('/chart/budget/year/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\BudgetController@year']);
    Route::get('/chart/budget/multi-year/{reportType}/{start_date}/{end_date}/{accountList}/{budgetList}', ['uses' => 'Chart\BudgetController@multiYear']);

    Route::get('/chart/budget/{budget}/{limitrepetition}', ['uses' => 'Chart\BudgetController@budgetLimit']);
    Route::get('/chart/budget/{budget}', ['uses' => 'Chart\BudgetController@budget']);

    // categories:
    Route::get('/chart/category/frontpage', ['uses' => 'Chart\CategoryController@frontpage']);

    // these three charts are for reports:
    Route::get('/chart/category/earned-in-period/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\CategoryController@earnedInPeriod']);
    Route::get('/chart/category/spent-in-period/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\CategoryController@spentInPeriod']);
    Route::get(
        '/chart/category/multi-year/{reportType}/{start_date}/{end_date}/{accountList}/{categoryList}', ['uses' => 'Chart\CategoryController@multiYear']
    );

    Route::get('/chart/category/{category}/period', ['uses' => 'Chart\CategoryController@currentPeriod']);
    Route::get('/chart/category/{category}/period/{date}', ['uses' => 'Chart\CategoryController@specificPeriod']);
    Route::get('/chart/category/{category}/all', ['uses' => 'Chart\CategoryController@all']);

    // piggy banks:
    Route::get('/chart/piggy-bank/{piggyBank}', ['uses' => 'Chart\PiggyBankController@history']);

    // reports:
    Route::get('/chart/report/in-out/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\ReportController@yearInOut']);
    Route::get('/chart/report/in-out-sum/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'Chart\ReportController@yearInOutSummarized']);


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
    Route::post('/preferences', ['uses' => 'PreferencesController@postIndex']);

    /**
     * Profile Controller
     */
    Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
    Route::get('/profile/change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'profile.change-password']);
    Route::get('/profile/delete-account', ['uses' => 'ProfileController@deleteAccount', 'as' => 'profile.delete-account']);
    Route::post('/profile/delete-account', ['uses' => 'ProfileController@postDeleteAccount', 'as' => 'delete-account-post']);
    Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword', 'as' => 'change-password-post']);

    /**
     * Report Controller
     */
    Route::get('/reports', ['uses' => 'ReportController@index', 'as' => 'reports.index']);
    Route::get('/reports/report/{reportType}/{start_date}/{end_date}/{accountList}', ['uses' => 'ReportController@report', 'as' => 'reports.report']);

    /**
     * Rules Controller
     */
    // index
    Route::get('/rules', ['uses' => 'RuleController@index', 'as' => 'rules.index']);

    // rules GET:
    Route::get('/rules/create/{ruleGroup}', ['uses' => 'RuleController@create', 'as' => 'rules.rule.create']);
    Route::get('/rules/rules/up/{rule}', ['uses' => 'RuleController@up', 'as' => 'rules.rule.up']);
    Route::get('/rules/rules/down/{rule}', ['uses' => 'RuleController@down', 'as' => 'rules.rule.down']);
    Route::get('/rules/rules/edit/{rule}', ['uses' => 'RuleController@edit', 'as' => 'rules.rule.edit']);
    Route::get('/rules/rules/delete/{rule}', ['uses' => 'RuleController@delete', 'as' => 'rules.rule.delete']);

    // rules POST:
    Route::post('/rules/rules/trigger/reorder/{rule}', ['uses' => 'RuleController@reorderRuleTriggers']);
    Route::post('/rules/rules/action/reorder/{rule}', ['uses' => 'RuleController@reorderRuleActions']);
    Route::post('/rules/store/{ruleGroup}', ['uses' => 'RuleController@store', 'as' => 'rules.rule.store']);
    Route::post('/rules/update/{rule}', ['uses' => 'RuleController@update', 'as' => 'rules.rule.update']);
    Route::post('/rules/destroy/{rule}', ['uses' => 'RuleController@destroy', 'as' => 'rules.rule.destroy']);


    // rule groups GET
    Route::get('/rules/groups/create', ['uses' => 'RuleGroupController@create', 'as' => 'rules.rule-group.create']);
    Route::get('/rules/groups/edit/{ruleGroup}', ['uses' => 'RuleGroupController@edit', 'as' => 'rules.rule-group.edit']);
    Route::get('/rules/groups/delete/{ruleGroup}', ['uses' => 'RuleGroupController@delete', 'as' => 'rules.rule-group.delete']);
    Route::get('/rules/groups/up/{ruleGroup}', ['uses' => 'RuleGroupController@up', 'as' => 'rules.rule-group.up']);
    Route::get('/rules/groups/down/{ruleGroup}', ['uses' => 'RuleGroupController@down', 'as' => 'rules.rule-group.down']);

    // rule groups POST
    Route::post('/rules/groups/store', ['uses' => 'RuleGroupController@store', 'as' => 'rules.rule-group.store']);
    Route::post('/rules/groups/update/{ruleGroup}', ['uses' => 'RuleGroupController@update', 'as' => 'rules.rule-group.update']);
    Route::post('/rules/groups/destroy/{ruleGroup}', ['uses' => 'RuleGroupController@destroy', 'as' => 'rules.rule-group.destroy']);

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
    Route::get('/transactions/{what}', ['uses' => 'TransactionController@index', 'as' => 'transactions.index'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::get('/transactions/create/{what}', ['uses' => 'TransactionController@create', 'as' => 'transactions.create'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::get('/transaction/edit/{tj}', ['uses' => 'TransactionController@edit', 'as' => 'transactions.edit']);
    Route::get('/transaction/delete/{tj}', ['uses' => 'TransactionController@delete', 'as' => 'transactions.delete']);
    Route::get('/transaction/show/{tj}', ['uses' => 'TransactionController@show', 'as' => 'transactions.show']);
    // transaction controller:
    Route::post('/transactions/store/{what}', ['uses' => 'TransactionController@store', 'as' => 'transactions.store'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::post('/transaction/update/{tj}', ['uses' => 'TransactionController@update', 'as' => 'transactions.update']);
    Route::post('/transaction/destroy/{tj}', ['uses' => 'TransactionController@destroy', 'as' => 'transactions.destroy']);
    Route::post('/transaction/reorder', ['uses' => 'TransactionController@reorder', 'as' => 'transactions.reorder']);

    /**
     * Auth\Auth Controller
     */
    Route::get('/logout', ['uses' => 'Auth\AuthController@logout', 'as' => 'logout']);


}
);

