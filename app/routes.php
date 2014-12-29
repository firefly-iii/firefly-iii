<?php

// models:
Route::bind(
    'account',
    function ($value, $route) {
        if (Auth::check()) {
            $account = Account::
            leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')
                              ->where('account_types.editable', 1)
                              ->where('accounts.id', $value)
                              ->where('user_id', Auth::user()->id)
                              ->first(['accounts.*']);
            if ($account) {
                return $account;
            }
        }
        App::abort(404);
    }
);

Route::bind(
    'accountname', function ($value, $route) {
    if (Auth::check()) {
        return Account::
        leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')->where('account_types.editable', 1)->where('name', $value)->where(
            'user_id', Auth::user()->id
        )->first();
    }

    return null;
}
);


Route::bind(
    'bill', function ($value, $route) {
    if (Auth::check()) {
        return Bill::
        where('id', $value)->where('user_id', Auth::user()->id)->first();
    }

    return null;
}
);
Route::bind(
    'budget', function ($value, $route) {
    if (Auth::check()) {
        return Budget::
        where('id', $value)->where('user_id', Auth::user()->id)->first();
    }

    return null;
}
);

Route::bind(
    'component', function ($value, $route) {
    if (Auth::check()) {
        return Component::
        where('id', $value)->where('user_id', Auth::user()->id)->first();
    }

    return null;
}
);

Route::bind(
    'reminder', function ($value, $route) {
    if (Auth::check()) {
        return Reminder::
        where('id', $value)->where('user_id', Auth::user()->id)->first();
    }

    return null;
}
);

Route::bind(
    'category', function ($value, $route) {
    if (Auth::check()) {
        return Category::
        where('id', $value)->where('user_id', Auth::user()->id)->first();
    }

    return null;
}
);

Route::bind(
    'tj', function ($value, $route) {
    if (Auth::check()) {
        return TransactionJournal::
        where('id', $value)->where('user_id', Auth::user()->id)->first();
    }

    return null;
}
);

Route::bind(
    'currency', function ($value, $route) {
    return TransactionCurrency::find($value);
}
);

Route::bind(
    'limitrepetition', function ($value, $route) {
    if (Auth::check()) {
        return LimitRepetition::where('limit_repetitions.id', $value)
                              ->leftjoin('budget_limits', 'budget_limits.id', '=', 'limit_repetitions.budget_limit_id')
                              ->leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                              ->where('budgets.user_id', Auth::user()->id)
                              ->first(['limit_repetitions.*']);
    }

    return null;
}
);

Route::bind(
    'piggy_bank', function ($value, $route) {
    if (Auth::check()) {
        return PiggyBank::
        where('piggy_banks.id', $value)
                        ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                        ->where('accounts.user_id', Auth::user()->id)
                        ->where('repeats', 0)->first(['piggy_banks.*']);
    }

    return null;
}
);

Route::bind(
    'repeated', function ($value, $route) {
    if (Auth::check()) {
        return PiggyBank::
        where('piggy_banks.id', $value)
                        ->leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')
                        ->where('accounts.user_id', Auth::user()->id)
                        ->where('repeats', 1)->first(['piggy_banks.*']);
    }

    return null;
}
);

// protected routes:
Route::group(
    ['before' => 'auth'], function () {


    // some date routes used for (well duh) date-based navigation.
    Route::get('/prev', ['uses' => 'HomeController@sessionPrev', 'as' => 'sessionPrev']);
    //Route::get('/repair', ['uses' => 'HomeController@repair']);
    Route::get('/next', ['uses' => 'HomeController@sessionNext', 'as' => 'sessionNext']);
    Route::get('/jump/{range}', ['uses' => 'HomeController@rangeJump', 'as' => 'rangeJump']);


    // account controller:
    Route::get('/accounts/{what}', ['uses' => 'AccountController@index', 'as' => 'accounts.index'])->where('what', 'revenue|asset|expense');
    Route::get('/accounts/create/{what}', ['uses' => 'AccountController@create', 'as' => 'accounts.create'])->where('what', 'revenue|asset|expense');
    Route::get('/accounts/edit/{account}', ['uses' => 'AccountController@edit', 'as' => 'accounts.edit']);
    Route::get('/accounts/delete/{account}', ['uses' => 'AccountController@delete', 'as' => 'accounts.delete']);
    Route::get('/accounts/show/{account}/{view?}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);


    // budget controller:
    Route::get('/budgets', ['uses' => 'BudgetController@index', 'as' => 'budgets.index']);
    Route::get('/budgets/income', ['uses' => 'BudgetController@updateIncome', 'as' => 'budgets.income']); # extra.
    Route::get('/budgets/create', ['uses' => 'BudgetController@create', 'as' => 'budgets.create']);
    Route::get('/budgets/edit/{budget}', ['uses' => 'BudgetController@edit', 'as' => 'budgets.edit']);
    Route::get('/budgets/delete/{budget}', ['uses' => 'BudgetController@delete', 'as' => 'budgets.delete']);
    Route::get('/budgets/show/{budget}/{limitrepetition?}', ['uses' => 'BudgetController@show', 'as' => 'budgets.show']);

    // category controller:
    Route::get('/categories', ['uses' => 'CategoryController@index', 'as' => 'categories.index']);
    Route::get('/categories/create', ['uses' => 'CategoryController@create', 'as' => 'categories.create']);
    Route::get('/categories/edit/{category}', ['uses' => 'CategoryController@edit', 'as' => 'categories.edit']);
    Route::get('/categories/delete/{category}', ['uses' => 'CategoryController@delete', 'as' => 'categories.delete']);
    Route::get('/categories/show/{category}', ['uses' => 'CategoryController@show', 'as' => 'categories.show']);

    // currency controller
    Route::get('/currency', ['uses' => 'CurrencyController@index', 'as' => 'currency.index']);
    Route::get('/currency/create', ['uses' => 'CurrencyController@create', 'as' => 'currency.create']);
    Route::get('/currency/edit/{currency}', ['uses' => 'CurrencyController@edit', 'as' => 'currency.edit']);
    Route::get('/currency/delete/{currency}', ['uses' => 'CurrencyController@delete', 'as' => 'currency.delete']);
    Route::get('/currency/default/{currency}', ['uses' => 'CurrencyController@defaultCurrency', 'as' => 'currency.default']);

    // google chart controller
    Route::get('/chart/home/account', ['uses' => 'GoogleChartController@allAccountsBalanceChart']);
    Route::get('/chart/home/budgets', ['uses' => 'GoogleChartController@allBudgetsHomeChart']);
    Route::get('/chart/home/categories', ['uses' => 'GoogleChartController@allCategoriesHomeChart']);
    Route::get('/chart/home/bills', ['uses' => 'GoogleChartController@billsOverview']);
    Route::get('/chart/account/{account}/{view?}', ['uses' => 'GoogleChartController@accountBalanceChart']);
    Route::get('/chart/reports/income-expenses/{year}', ['uses' => 'GoogleChartController@yearInExp']);
    Route::get('/chart/reports/income-expenses-sum/{year}', ['uses' => 'GoogleChartController@yearInExpSum']);
    Route::get('/chart/bills/{bill}', ['uses' => 'GoogleChartController@billOverview']);
    Route::get('/chart/budget/{budget}/{limitrepetition}', ['uses' => 'GoogleChartController@budgetLimitSpending']);
    Route::get('/chart/piggy_history/{piggy_bank}', ['uses' => 'GoogleChartController@piggyBankHistory']);

    // google chart for components (categories + budgets combined)
    Route::get('/chart/budget/{budget}/spending/{year}', ['uses' => 'GoogleChartController@budgetsAndSpending']);
    Route::get('/chart/category/{category}/spending/{year}', ['uses' => 'GoogleChartController@categoriesAndSpending']);

    // help controller
    Route::get('/help/{route}', ['uses' => 'HelpController@show', 'as' => 'help.show']);

    // home controller
    Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
    Route::get('/flush', ['uses' => 'HomeController@flush', 'as' => 'flush']); # even though nothing is cached.

    // JSON controller
    Route::get('/json/expense-accounts', ['uses' => 'JsonController@expenseAccounts', 'as' => 'json.expense-accounts']);
    Route::get('/json/revenue-accounts', ['uses' => 'JsonController@revenueAccounts', 'as' => 'json.revenue-accounts']);
    Route::get('/json/categories', ['uses' => 'JsonController@categories', 'as' => 'json.categories']);


    // piggy bank controller
    Route::get('/piggy_banks', ['uses' => 'PiggyBankController@index', 'as' => 'piggy_banks.index']);
    Route::get('/piggy_banks/add/{piggy_bank}', ['uses' => 'PiggyBankController@add']); # add money
    Route::get('/piggy_banks/remove/{piggy_bank}', ['uses' => 'PiggyBankController@remove']); #remove money

    Route::get('/piggy_banks/create', ['uses' => 'PiggyBankController@create', 'as' => 'piggy_banks.create']);
    Route::get('/piggy_banks/edit/{piggy_bank}', ['uses' => 'PiggyBankController@edit', 'as' => 'piggy_banks.edit']);
    Route::get('/piggy_banks/delete/{piggy_bank}', ['uses' => 'PiggyBankController@delete', 'as' => 'piggy_banks.delete']);
    Route::get('/piggy_banks/show/{piggy_bank}', ['uses' => 'PiggyBankController@show', 'as' => 'piggy_banks.show']);

    // preferences controller
    Route::get('/preferences', ['uses' => 'PreferencesController@index', 'as' => 'preferences']);

    //profile controller
    Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
    Route::get('/profile/change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);

    // bills controller
    Route::get('/bills', ['uses' => 'BillController@index', 'as' => 'bills.index']);
    Route::get('/bills/rescan/{bill}', ['uses' => 'BillController@rescan', 'as' => 'bills.rescan']); # rescan for matching.
    Route::get('/bills/create', ['uses' => 'BillController@create', 'as' => 'bills.create']);
    Route::get('/bills/edit/{bill}', ['uses' => 'BillController@edit', 'as' => 'bills.edit']);
    Route::get('/bills/delete/{bill}', ['uses' => 'BillController@delete', 'as' => 'bills.delete']);
    Route::get('/bills/show/{bill}', ['uses' => 'BillController@show', 'as' => 'bills.show']);

    // repeated expenses controller:
    Route::get('/repeatedexpenses', ['uses' => 'RepeatedExpenseController@index', 'as' => 'repeated.index']);
    Route::get('/repeatedexpenses/create', ['uses' => 'RepeatedExpenseController@create', 'as' => 'repeated.create']);
    Route::get('/repeatedexpenses/show/{repeated}', ['uses' => 'RepeatedExpenseController@show', 'as' => 'repeated.show']);

    // report controller:
    Route::get('/reports', ['uses' => 'ReportController@index', 'as' => 'reports.index']);
    Route::get('/reports/{year}', ['uses' => 'ReportController@year', 'as' => 'reports.year']);
    Route::get('/reports/{year}/{month}', ['uses' => 'ReportController@month', 'as' => 'reports.month']);
    Route::get('/reports/budget/{year}/{month}', ['uses' => 'ReportController@budget', 'as' => 'reports.budget']);
    #Route::get('/reports/unbalanced/{year}/{month}', ['uses' => 'ReportController@unbalanced', 'as' => 'reports.unbalanced']);

    // reminder controller
    Route::get('/reminders/{reminder}', ['uses' => 'ReminderController@show', 'as' => 'reminders.show']);
    Route::get('/reminders/{reminder}/dismiss', ['uses' => 'ReminderController@dismiss', 'as' => 'reminders.dismiss']);
    Route::get('/reminders/{reminder}/notNow', ['uses' => 'ReminderController@notNow', 'as' => 'reminders.notNow']);
    Route::get('/reminders/{reminder}/act', ['uses' => 'ReminderController@act', 'as' => 'reminders.act']);

    // search controller:
    Route::get('/search', ['uses' => 'SearchController@index', 'as' => 'search']);

    // transaction controller:
    Route::get('/transactions/{what}', ['uses' => 'TransactionController@index', 'as' => 'transactions.index'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::get('/transactions/create/{what}', ['uses' => 'TransactionController@create', 'as' => 'transactions.create'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::get('/transaction/edit/{tj}', ['uses' => 'TransactionController@edit', 'as' => 'transactions.edit']);
    Route::get('/transaction/delete/{tj}', ['uses' => 'TransactionController@delete', 'as' => 'transactions.delete']);
    Route::get('/transaction/show/{tj}', ['uses' => 'TransactionController@show', 'as' => 'transactions.show']);
    Route::get('/transaction/relate/{tj}', ['uses' => 'TransactionController@relate', 'as' => 'transactions.relate']);
    Route::post('/transactions/relatedSearch/{tj}', ['uses' => 'TransactionController@relatedSearch', 'as' => 'transactions.relatedSearch']);
    Route::post('/transactions/alreadyRelated/{tj}', ['uses' => 'TransactionController@alreadyRelated', 'as' => 'transactions.alreadyRelated']);
    Route::post('/transactions/doRelate', ['uses' => 'TransactionController@doRelate', 'as' => 'transactions.doRelate']);
    Route::any('/transactions/unrelate/{tj}', ['uses' => 'TransactionController@unrelate', 'as' => 'transactions.unrelate']);

    // user controller
    Route::get('/logout', ['uses' => 'UserController@logout', 'as' => 'logout']);

    Route::post('budgets/amount/{budget}', ['uses' => 'BudgetController@amount']);


}
);

// protected + csrf routes (POST)
Route::group(
    ['before' => 'csrf|auth'], function () {
    // account controller:
    Route::post('/accounts/store', ['uses' => 'AccountController@store', 'as' => 'accounts.store']);
    Route::post('/accounts/update/{account}', ['uses' => 'AccountController@update', 'as' => 'accounts.update']);
    Route::post('/accounts/destroy/{account}', ['uses' => 'AccountController@destroy', 'as' => 'accounts.destroy']);

    // budget controller:
    Route::post('/budgets/income', ['uses' => 'BudgetController@postUpdateIncome', 'as' => 'budgets.postIncome']);
    Route::post('/budgets/store', ['uses' => 'BudgetController@store', 'as' => 'budgets.store']);
    Route::post('/budgets/update/{budget}', ['uses' => 'BudgetController@update', 'as' => 'budgets.update']);
    Route::post('/budgets/destroy/{budget}', ['uses' => 'BudgetController@destroy', 'as' => 'budgets.destroy']);

    // category controller
    Route::post('/categories/store', ['uses' => 'CategoryController@store', 'as' => 'categories.store']);
    Route::post('/categories/update/{category}', ['uses' => 'CategoryController@update', 'as' => 'categories.update']);
    Route::post('/categories/destroy/{category}', ['uses' => 'CategoryController@destroy', 'as' => 'categories.destroy']);

    // currency controller
    Route::post('/currency/store', ['uses' => 'CurrencyController@store', 'as' => 'currency.store']);
    Route::post('/currency/update/{currency}', ['uses' => 'CurrencyController@update', 'as' => 'currency.update']);
    Route::post('/currency/destroy/{currency}', ['uses' => 'CurrencyController@destroy', 'as' => 'currency.destroy']);

    // piggy bank controller
    Route::post('/piggy_banks/store', ['uses' => 'PiggyBankController@store', 'as' => 'piggy_banks.store']);
    Route::post('/piggy_banks/update/{piggy_bank}', ['uses' => 'PiggyBankController@update', 'as' => 'piggy_banks.update']);
    Route::post('/piggy_banks/destroy/{piggy_bank}', ['uses' => 'PiggyBankController@destroy', 'as' => 'piggy_banks.destroy']);
    Route::post('/piggy_banks/add/{piggy_bank}', ['uses' => 'PiggyBankController@postAdd', 'as' => 'piggy_banks.add']); # add money
    Route::post('/piggy_banks/remove/{piggy_bank}', ['uses' => 'PiggyBankController@postRemove', 'as' => 'piggy_banks.remove']); # remove money.

    // repeated expense controller
    Route::post('/repeatedexpense/store', ['uses' => 'RepeatedExpenseController@store', 'as' => 'repeated.store']);

    // preferences controller
    Route::post('/preferences', ['uses' => 'PreferencesController@postIndex']);

    // profile controller
    Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword']);

    // bills controller
    Route::post('/bills/store', ['uses' => 'BillController@store', 'as' => 'bills.store']);
    Route::post('/bills/update/{bill}', ['uses' => 'BillController@update', 'as' => 'bills.update']);
    Route::post('/bills/destroy/{bill}', ['uses' => 'BillController@destroy', 'as' => 'bills.destroy']);

    // transaction controller:
    Route::post('/transactions/store/{what}', ['uses' => 'TransactionController@store', 'as' => 'transactions.store'])->where(
        ['what' => 'expenses|revenue|withdrawal|deposit|transfer|transfers']
    );
    Route::post('/transaction/update/{tj}', ['uses' => 'TransactionController@update', 'as' => 'transactions.update']);
    Route::post('/transaction/destroy/{tj}', ['uses' => 'TransactionController@destroy', 'as' => 'transactions.destroy']);

}
);

// guest routes:
Route::group(
    ['before' => 'guest'], function () {
    // user controller
    Route::get('/login', ['uses' => 'UserController@login', 'as' => 'login']);
    Route::get('/register', ['uses' => 'UserController@register', 'as' => 'register']);
    Route::get('/reset/{reset}', ['uses' => 'UserController@reset', 'as' => 'reset']);
    Route::get('/remindme', ['uses' => 'UserController@remindme', 'as' => 'remindme']);


}
);

// guest + csrf routes:
Route::group(
    ['before' => 'csrf|guest'], function () {

    // user controller
    Route::post('/login', ['uses' => 'UserController@postLogin', 'as' => 'login.post']);
    Route::post('/register', ['uses' => 'UserController@postRegister', 'as' => 'register.post']);
    Route::post('/remindme', ['uses' => 'UserController@postRemindme', 'as' => 'remindme.post']);
}
);