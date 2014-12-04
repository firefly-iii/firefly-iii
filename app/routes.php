<?php

// models:
Route::bind(
    'account', function ($value, $route) {
        if (Auth::check()) {
            $account = Account::
            leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id')->where('account_types.editable', 1)->where('accounts.id', $value)
                              ->where('user_id', Auth::user()->id)->first(['accounts.*']);
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
    'recurring', function ($value, $route) {
        if (Auth::check()) {
            return RecurringTransaction::
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
    'limitrepetition', function ($value, $route) {
        if (Auth::check()) {
            return LimitRepetition::
            where('limit_repetitions.id', $value)->leftjoin('limits', 'limits.id', '=', 'limit_repetitions.limit_id')->leftJoin(
                'components', 'components.id', '=', 'limits.component_id'
            )->where('components.class', 'Budget')->where('components.user_id', Auth::user()->id)->first(['limit_repetitions.*']);
        }

        return null;
    }
);

Route::bind(
    'piggybank', function ($value, $route) {
        if (Auth::check()) {
            return Piggybank::
            where('piggybanks.id', $value)
                            ->leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')
                            ->where('accounts.user_id', Auth::user()->id)
                            ->where('repeats', 0)->first(['piggybanks.*']);
        }

        return null;
    }
);

Route::bind(
    'repeated', function ($value, $route) {
        if (Auth::check()) {
            return Piggybank::
            where('piggybanks.id', $value)
                            ->leftJoin('accounts', 'accounts.id', '=', 'piggybanks.account_id')
                            ->where('accounts.user_id', Auth::user()->id)
                            ->where('repeats', 1)->first(['piggybanks.*']);
        }

        return null;
    }
);

// protected routes:
Route::group(
    ['before' => 'auth'], function () {


        // some date routes used for (well duh) date-based navigation.
        Route::get('/prev', ['uses' => 'HomeController@sessionPrev', 'as' => 'sessionPrev']);
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

        // google chart controller
        Route::get('/chart/home/account', ['uses' => 'GoogleChartController@allAccountsBalanceChart']);
        Route::get('/chart/home/budgets', ['uses' => 'GoogleChartController@allBudgetsHomeChart']);
        Route::get('/chart/home/categories', ['uses' => 'GoogleChartController@allCategoriesHomeChart']);
        Route::get('/chart/home/recurring', ['uses' => 'GoogleChartController@recurringTransactionsOverview']);
        Route::get('/chart/account/{account}/{view?}', ['uses' => 'GoogleChartController@accountBalanceChart']);
        Route::get('/chart/sankey/{account}/out/{view?}', ['uses' => 'GoogleChartController@accountSankeyOutChart']);
        Route::get('/chart/sankey/{account}/in/{view?}', ['uses' => 'GoogleChartController@accountSankeyInChart']);
        Route::get('/chart/reports/income-expenses/{year}', ['uses' => 'GoogleChartController@yearInExp']);
        Route::get('/chart/reports/income-expenses-sum/{year}', ['uses' => 'GoogleChartController@yearInExpSum']);
        Route::get('/chart/recurring/{recurring}', ['uses' => 'GoogleChartController@recurringOverview']);
        Route::get('/chart/reports/budgets/{year}', ['uses' => 'GoogleChartController@budgetsReportChart']);
        Route::get('/chart/budget/{budget}/{limitrepetition}', ['uses' => 'GoogleChartController@budgetLimitSpending']);
        Route::get('/chart/piggyhistory/{piggybank}', ['uses' => 'GoogleChartController@piggyBankHistory']);

        // google chart for components (categories + budgets combined)
        Route::get('/chart/component/{component}/spending/{year}', ['uses' => 'GoogleChartController@componentsAndSpending']);

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
        Route::get('/piggybanks', ['uses' => 'PiggybankController@index', 'as' => 'piggybanks.index']);
        Route::get('/piggybanks/add/{piggybank}', ['uses' => 'PiggybankController@add']); # add money
        Route::get('/piggybanks/remove/{piggybank}', ['uses' => 'PiggybankController@remove']); #remove money

        Route::get('/piggybanks/create', ['uses' => 'PiggybankController@create', 'as' => 'piggybanks.create']);
        Route::get('/piggybanks/edit/{piggybank}', ['uses' => 'PiggybankController@edit', 'as' => 'piggybanks.edit']);
        Route::get('/piggybanks/delete/{piggybank}', ['uses' => 'PiggybankController@delete', 'as' => 'piggybanks.delete']);
        Route::get('/piggybanks/show/{piggybank}', ['uses' => 'PiggybankController@show', 'as' => 'piggybanks.show']);

        // preferences controller
        Route::get('/preferences', ['uses' => 'PreferencesController@index', 'as' => 'preferences']);

        //profile controller
        Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
        Route::get('/profile/change-password', ['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);

        // recurring transactions controller
        Route::get('/recurring', ['uses' => 'RecurringController@index', 'as' => 'recurring.index']);
        Route::get('/recurring/rescan/{recurring}', ['uses' => 'RecurringController@rescan', 'as' => 'recurring.rescan']); # rescan for matching.
        Route::get('/recurring/create', ['uses' => 'RecurringController@create', 'as' => 'recurring.create']);
        Route::get('/recurring/edit/{recurring}', ['uses' => 'RecurringController@edit', 'as' => 'recurring.edit']);
        Route::get('/recurring/delete/{recurring}', ['uses' => 'RecurringController@delete', 'as' => 'recurring.delete']);
        Route::get('/recurring/show/{recurring}', ['uses' => 'RecurringController@show', 'as' => 'recurring.show']);

        // repeated expenses controller:
        Route::get('/repeatedexpenses', ['uses' => 'RepeatedExpenseController@index', 'as' => 'repeated.index']);
        Route::get('/repeatedexpenses/create', ['uses' => 'RepeatedExpenseController@create', 'as' => 'repeated.create']);
        Route::get('/repeatedexpenses/show/{repeated}', ['uses' => 'RepeatedExpenseController@show', 'as' => 'repeated.show']);

        // report controller:
        Route::get('/reports', ['uses' => 'ReportController@index', 'as' => 'reports.index']);
        Route::get('/reports/{year}', ['uses' => 'ReportController@year', 'as' => 'reports.year']);
        Route::get('/reports/budgets/{year}/{month}', ['uses' => 'ReportController@budgets', 'as' => 'reports.budgets']);
        Route::get('/reports/unbalanced/{year}/{month}', ['uses' => 'ReportController@unbalanced', 'as' => 'reports.unbalanced']);

        // reminder controller
        Route::get('/reminders/{reminder}', ['uses' => 'ReminderController@show', 'as' => 'reminders.show']);
        Route::get('/reminders/{reminder}/dismiss', ['uses' => 'ReminderController@dismiss', 'as' => 'reminders.dismiss']);
        Route::get('/reminders/{reminder}/notnow', ['uses' => 'ReminderController@notnow', 'as' => 'reminders.notnow']);
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

        // piggy bank controller
        Route::post('/piggybanks/store', ['uses' => 'PiggybankController@store', 'as' => 'piggybanks.store']);
        Route::post('/piggybanks/update/{piggybank}', ['uses' => 'PiggybankController@update', 'as' => 'piggybanks.update']);
        Route::post('/piggybanks/destroy/{piggybank}', ['uses' => 'PiggybankController@destroy', 'as' => 'piggybanks.destroy']);
        Route::post('/piggybanks/add/{piggybank}', ['uses' => 'PiggybankController@postAdd', 'as' => 'piggybanks.add']); # add money
        Route::post('/piggybanks/remove/{piggybank}', ['uses' => 'PiggybankController@postRemove', 'as' => 'piggybanks.remove']); # remove money.

        // repeated expense controller
        Route::post('/repeatedexpense/store', ['uses' => 'RepeatedExpenseController@store', 'as' => 'repeated.store']);

        // preferences controller
        Route::post('/preferences', ['uses' => 'PreferencesController@postIndex']);

        // profile controller
        Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword']);

        // recurring controller
        Route::post('/recurring/store', ['uses' => 'RecurringController@store', 'as' => 'recurring.store']);
        Route::post('/recurring/update/{recurring}', ['uses' => 'RecurringController@update', 'as' => 'recurring.update']);
        Route::post('/recurring/destroy/{recurring}', ['uses' => 'RecurringController@destroy', 'as' => 'recurring.destroy']);

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
        Route::get('/verify/{verification}', ['uses' => 'UserController@verify', 'as' => 'verify']);
        Route::get('/reset/{reset}', ['uses' => 'UserController@reset', 'as' => 'reset']);
        Route::get('/remindme', ['uses' => 'UserController@remindme', 'as' => 'remindme']);


    }
);

// guest + csrf routes:
Route::group(
    ['before' => 'csrf|guest'], function () {

        // user controller
        Route::post('/login', ['uses' => 'UserController@postLogin']);
        Route::post('/register', ['uses' => 'UserController@postRegister']);
        Route::post('/remindme', ['uses' => 'UserController@postRemindme']);
    }
);