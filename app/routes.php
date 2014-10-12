<?php

//use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;



// models:
Route::bind('account', function($value, $route)
    {
        if(Auth::check()) {
            $account = Account::
                leftJoin('account_types','account_types.id','=','accounts.account_type_id')->
                where('account_types.editable',1)->
                where('accounts.id', $value)->
                where('user_id',Auth::user()->id)->
                first(['accounts.*']);
            if($account) {
                return $account;
            }
        }
        App::abort(404);
    });

Route::bind('accountname', function($value, $route)
    {
        if(Auth::check()) {
            return Account::
                leftJoin('account_types','account_types.id','=','accounts.account_type_id')->
                where('account_types.editable',1)->
                where('name', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });


Route::bind('recurring', function($value, $route)
    {
        if(Auth::check()) {
            return RecurringTransaction::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });
Route::bind('budget', function($value, $route)
    {
        if(Auth::check()) {
            return Budget::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });

Route::bind('reminder', function($value, $route)
    {
        if(Auth::check()) {
            return Reminder::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });

Route::bind('category', function($value, $route)
{
    if(Auth::check()) {
        return Category::
            where('id', $value)->
            where('user_id',Auth::user()->id)->first();
    }
    return null;
});

Route::bind('tj', function($value, $route)
    {
        if(Auth::check()) {
            return TransactionJournal::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });

Route::bind('limit', function($value, $route)
    {
        if(Auth::check()) {
            return Limit::
                where('limits.id', $value)->
                leftJoin('components','components.id','=','limits.component_id')->
                where('components.class','Budget')->
                where('components.user_id',Auth::user()->id)->first(['limits.*']);
        }
        return null;
    });

Route::bind('limitrepetition', function($value, $route)
    {
        if(Auth::check()) {
            return LimitRepetition::
                where('limit_repetitions.id', $value)->
                leftjoin('limits','limits.id','=','limit_repetitions.limit_id')->
                leftJoin('components','components.id','=','limits.component_id')->
                where('components.class','Budget')->
                where('components.user_id',Auth::user()->id)->first(['limit_repetitions.*']);
        }
        return null;
    });

Route::bind('piggybank', function($value, $route)
    {
        if(Auth::check()) {
            return Piggybank::
                where('piggybanks.id', $value)->
                leftJoin('accounts','accounts.id','=','piggybanks.account_id')->
                where('accounts.user_id',Auth::user()->id)->first(['piggybanks.*']);
        }
        return null;
    });



// a development route:
Route::get('/dev', ['uses' => 'HomeController@jobDev']);

// protected routes:
Route::group(['before' => 'auth'], function () {




        // some date routes:
        Route::get('/prev',['uses' => 'HomeController@sessionPrev', 'as' => 'sessionPrev']);
        Route::get('/next',['uses' => 'HomeController@sessionNext', 'as' => 'sessionNext']);
        Route::get('/jump/{range}',['uses' => 'HomeController@rangeJump','as' => 'rangeJump']);

        // account controller:
        Route::get('/accounts', ['uses' => 'AccountController@index', 'as' => 'accounts.index']);
        Route::get('/accounts/asset',  ['uses' => 'AccountController@asset', 'as' => 'accounts.asset']);
        Route::get('/accounts/expense', ['uses' => 'AccountController@expense', 'as' => 'accounts.expense']);
        Route::get('/accounts/revenue', ['uses' => 'AccountController@revenue', 'as' => 'accounts.revenue']);

        Route::get('/accounts/create/{what}', ['uses' => 'AccountController@create', 'as' => 'accounts.create'])->where('what','revenue|asset|expense');
        Route::get('/accounts/{account}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);
        Route::get('/accounts/{account}/edit', ['uses' => 'AccountController@edit', 'as' => 'accounts.edit']);
        Route::get('/accounts/{account}/delete', ['uses' => 'AccountController@delete', 'as' => 'accounts.delete']);

        // budget controller:
        Route::get('/budgets/date',['uses' => 'BudgetController@indexByDate','as' => 'budgets.index.date']);
        Route::get('/budgets/budget',['uses' => 'BudgetController@indexByBudget','as' => 'budgets.index.budget']);
        Route::get('/budgets/create',['uses' => 'BudgetController@create', 'as' => 'budgets.create']);

        Route::get('/budgets/nobudget/{period}',['uses' => 'BudgetController@nobudget', 'as' => 'budgets.nobudget']);

        Route::get('/budgets/show/{budget}/{limitrepetition?}',['uses' => 'BudgetController@show', 'as' => 'budgets.show']);
        Route::get('/budgets/edit/{budget}',['uses' => 'BudgetController@edit', 'as' => 'budgets.edit']);
        Route::get('/budgets/delete/{budget}',['uses' => 'BudgetController@delete', 'as' => 'budgets.delete']);

        // category controller:
        Route::get('/categories',['uses' => 'CategoryController@index','as' => 'categories.index']);
        Route::get('/categories/create',['uses' => 'CategoryController@create','as' => 'categories.create']);
        Route::get('/categories/show/{category}',['uses' => 'CategoryController@show','as' => 'categories.show']);
        Route::get('/categories/edit/{category}',['uses' => 'CategoryController@edit','as' => 'categories.edit']);
        Route::get('/categories/delete/{category}',['uses' => 'CategoryController@delete','as' => 'categories.delete']);

        // chart controller
        Route::get('/chart/home/account/{account?}', ['uses' => 'ChartController@homeAccount', 'as' => 'chart.home']);
        Route::get('/chart/home/categories', ['uses' => 'ChartController@homeCategories', 'as' => 'chart.categories']);
        Route::get('/chart/home/budgets', ['uses' => 'ChartController@homeBudgets', 'as' => 'chart.budgets']);
        Route::get('/chart/home/info/{accountnameA}/{day}/{month}/{year}', ['uses' => 'ChartController@homeAccountInfo', 'as' => 'chart.info']);
        Route::get('/chart/categories/show/{category}', ['uses' => 'ChartController@categoryShowChart','as' => 'chart.showcategory']);
        // (new charts for budgets)
        Route::get('/chart/budget/{budget}/default', ['uses' => 'ChartController@budgetDefault', 'as' => 'chart.budget.default']);
        Route::get('chart/budget/{budget}/no_envelope', ['uses' => 'ChartController@budgetNoLimits', 'as' => 'chart.budget.nolimit']);
        Route::get('chart/budget/{budget}/session', ['uses' => 'ChartController@budgetSession', 'as' => 'chart.budget.session']);
        Route::get('chart/budget/envelope/{limitrepetition}', ['uses' => 'ChartController@budgetLimit', 'as' => 'chart.budget.limit']);

        // home controller
        Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
        Route::get('/flush', ['uses' => 'HomeController@flush', 'as' => 'flush']);

        // JSON controller:
        Route::get('/json/expense-accounts', ['uses' => 'JsonController@expenseAccounts', 'as' => 'json.expense-accounts']);
        Route::get('/json/revenue-accounts', ['uses' => 'JsonController@revenueAccounts', 'as' => 'json.revenue-accounts']);
        Route::get('/json/categories', ['uses' => 'JsonController@categories', 'as' => 'json.categories']);
        Route::get('/json/expenses', ['uses' => 'JsonController@expenses', 'as' => 'json.expenses']);
        Route::get('/json/revenue', ['uses' => 'JsonController@revenue', 'as' => 'json.revenue']);
        Route::get('/json/transfers', ['uses' => 'JsonController@transfers', 'as' => 'json.transfers']);
        Route::get('/json/recurring', ['uses' => 'JsonController@recurring', 'as' => 'json.recurring']);
        Route::get('/json/recurringjournals/{recurring}', ['uses' => 'JsonController@recurringjournals', 'as' => 'json.recurringjournals']);

        // limit controller:
        Route::get('/budgets/limits/create/{budget?}',['uses' => 'LimitController@create','as' => 'budgets.limits.create']);
        Route::get('/budgets/limits/delete/{limit}',['uses' => 'LimitController@delete','as' => 'budgets.limits.delete']);
        Route::get('/budgets/limits/edit/{limit}',['uses' => 'LimitController@edit','as' => 'budgets.limits.edit']);

        Route::get('/migrate',['uses' => 'MigrateController@index', 'as' => 'migrate.index']);

        // piggy bank controller
        Route::get('/piggybanks',['uses' => 'PiggybankController@piggybanks','as' => 'piggybanks.index.piggybanks']);
        Route::get('/repeated',['uses' => 'PiggybankController@repeated','as' => 'piggybanks.index.repeated']);
        Route::get('/piggybanks/create/piggybank', ['uses' => 'PiggybankController@createPiggybank','as' => 'piggybanks.create.piggybank']);
        Route::get('/piggybanks/create/repeated', ['uses' => 'PiggybankController@createRepeated','as' => 'piggybanks.create.repeated']);
        Route::get('/piggybanks/addMoney/{piggybank}', ['uses' => 'PiggybankController@addMoney','as' => 'piggybanks.amount.add']);
        Route::get('/piggybanks/removeMoney/{piggybank}', ['uses' => 'PiggybankController@removeMoney','as' => 'piggybanks.amount.remove']);
        Route::get('/piggybanks/show/{piggybank}', ['uses' => 'PiggybankController@show','as' => 'piggybanks.show']);
        Route::get('/piggybanks/edit/{piggybank}', ['uses' => 'PiggybankController@edit','as' => 'piggybanks.edit']);
        Route::get('/piggybanks/delete/{piggybank}', ['uses' => 'PiggybankController@delete','as' => 'piggybanks.delete']);
        Route::post('/piggybanks/updateAmount/{piggybank}',['uses' => 'PiggybankController@updateAmount','as' => 'piggybanks.updateAmount']);

        // preferences controller
        Route::get('/preferences', ['uses' => 'PreferencesController@index', 'as' => 'preferences']);

        //profile controller
        Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
        Route::get('/profile/change-password',['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);

        // recurring transactions controller
        Route::get('/recurring',['uses' => 'RecurringController@index', 'as' => 'recurring.index']);
        Route::get('/recurring/show/{recurring}',['uses' => 'RecurringController@show', 'as' => 'recurring.show']);
        Route::get('/recurring/rescan/{recurring}',['uses' => 'RecurringController@rescan', 'as' => 'recurring.rescan']);
        Route::get('/recurring/create',['uses' => 'RecurringController@create', 'as' => 'recurring.create']);
        Route::get('/recurring/edit/{recurring}',['uses' => 'RecurringController@edit','as' => 'recurring.edit']);
        Route::get('/recurring/delete/{recurring}',['uses' => 'RecurringController@delete','as' => 'recurring.delete']);

        // reminder controller
        Route::get('/reminders/dialog',['uses' => 'ReminderController@modalDialog']);
        Route::post('/reminders/postpone/{reminder}',['uses' => 'ReminderController@postpone']);
        Route::post('/reminders/dismiss/{reminder}',['uses' => 'ReminderController@dismiss']);
        Route::get('/reminders/redirect/{reminder}',['uses' => 'ReminderController@redirect']);

        // report controller:
        Route::get('/reports',['uses' => 'ReportController@index','as' => 'reports.index']);

        // search controller:
        Route::get('/search',['uses' => 'SearchController@index','as' => 'search']);

        // transaction controller:
        Route::get('/transactions/create/{what}', ['uses' => 'TransactionController@create', 'as' => 'transactions.create'])->where(['what' => 'withdrawal|deposit|transfer']);
        Route::get('/transaction/show/{tj}',['uses' => 'TransactionController@show','as' => 'transactions.show']);
        Route::get('/transaction/edit/{tj}',['uses' => 'TransactionController@edit','as' => 'transactions.edit']);
        Route::get('/transaction/delete/{tj}',['uses' => 'TransactionController@delete','as' => 'transactions.delete']);
        Route::get('/transactions/index',['uses' => 'TransactionController@index','as' => 'transactions.index']);
        Route::get('/transactions/expenses',['uses' => 'TransactionController@expenses','as' => 'transactions.expenses']);
        Route::get('/transactions/revenue',['uses' => 'TransactionController@revenue','as' => 'transactions.revenue']);
        Route::get('/transactions/transfers',['uses' => 'TransactionController@transfers','as' => 'transactions.transfers']);

        Route::get('/transactions/expenses',['uses' => 'TransactionController@expenses','as' => 'transactions.index.withdrawal']);
        Route::get('/transactions/revenue',['uses' => 'TransactionController@revenue','as' => 'transactions.index.deposit']);
        Route::get('/transactions/transfers',['uses' => 'TransactionController@transfers','as' => 'transactions.index.transfer']);

        // user controller
        Route::get('/logout', ['uses' => 'UserController@logout', 'as' => 'logout']);

    }
);

// protected + csrf routes (POST)
Route::group(['before' => 'csrf|auth'], function () {
        // account controller:
        Route::post('/accounts/store', ['uses' => 'AccountController@store', 'as' => 'accounts.store']);
        Route::post('/accounts/update/{account}', ['uses' => 'AccountController@update', 'as' => 'accounts.update']);
        Route::post('/accounts/destroy/{account}', ['uses' => 'AccountController@destroy', 'as' => 'accounts.destroy']);

        // budget controller:
        Route::post('/budgets/store',['uses' => 'BudgetController@store', 'as' => 'budgets.store']);
        Route::post('/budgets/update/{budget}', ['uses' => 'BudgetController@update', 'as' => 'budgets.update']);
        Route::post('/budgets/destroy/{budget}', ['uses' => 'BudgetController@destroy', 'as' => 'budgets.destroy']);

        // category controller
        Route::post('/categories/store',['uses' => 'CategoryController@store', 'as' => 'categories.store']);
        Route::post('/categories/update/{category}', ['uses' => 'CategoryController@update', 'as' => 'categories.update']);
        Route::post('/categories/destroy/{category}', ['uses' => 'CategoryController@destroy', 'as' => 'categories.destroy']);

        // limit controller:
        Route::post('/budgets/limits/store/{budget?}', ['uses' => 'LimitController@store', 'as' => 'budgets.limits.store']);
        Route::post('/budgets/limits/destroy/{limit}',['uses' => 'LimitController@destroy','as' => 'budgets.limits.destroy']);
        Route::post('/budgets/limits/update/{limit}',['uses' => 'LimitController@update','as' => 'budgets.limits.update']);

        Route::post('/migrate/upload',['uses' => 'MigrateController@upload', 'as' => 'migrate.upload']);


        // piggy bank controller
        Route::post('/piggybanks/store/piggybank',['uses' => 'PiggybankController@storePiggybank','as' => 'piggybanks.store.piggybank']);
        Route::post('/piggybanks/store/repeated',['uses' => 'PiggybankController@storeRepeated','as' => 'piggybanks.store.repeated']);
        Route::post('/piggybanks/update/{piggybank}', ['uses' => 'PiggybankController@update','as' => 'piggybanks.update']);
        Route::post('/piggybanks/destroy/{piggybank}', ['uses' => 'PiggybankController@destroy','as' => 'piggybanks.destroy']);
        Route::post('/piggybanks/mod/{piggybank}', ['uses' => 'PiggybankController@modMoney','as' => 'piggybanks.modMoney']);


        // preferences controller
        Route::post('/preferences', ['uses' => 'PreferencesController@postIndex']);

        // profile controller
        Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword']);

        // recurring controller
        Route::post('/recurring/store',['uses' => 'RecurringController@store', 'as' => 'recurring.store']);
        Route::post('/recurring/update/{recurring}',['uses' => 'RecurringController@update','as' => 'recurring.update']);
        Route::post('/recurring/destroy/{recurring}',['uses' => 'RecurringController@destroy','as' => 'recurring.destroy']);

        // transaction controller:
        Route::post('/transactions/store/{what}', ['uses' => 'TransactionController@store', 'as' => 'transactions.store'])->where(['what' => 'withdrawal|deposit|transfer']);
        Route::post('/transaction/update/{tj}',['uses' => 'TransactionController@update','as' => 'transactions.update']);
        Route::post('/transaction/destroy/{tj}',['uses' => 'TransactionController@destroy','as' => 'transactions.destroy']);

    }
);

// guest routes:
Route::group(['before' => 'guest'], function () {
        // user controller
        Route::get('/login', ['uses' => 'UserController@login', 'as' => 'login']);
        Route::get('/register', ['uses' => 'UserController@register', 'as' => 'register']);
        Route::get('/verify/{verification}', ['uses' => 'UserController@verify', 'as' => 'verify']);
        Route::get('/reset/{reset}', ['uses' => 'UserController@reset', 'as' => 'reset']);
        Route::get('/remindme', ['uses' => 'UserController@remindme', 'as' => 'remindme']);


    }
);

// guest + csrf routes:
Route::group(['before' => 'csrf|guest'], function () {

        // user controller
        Route::post('/login', ['uses' => 'UserController@postLogin']);
        Route::post('/register', ['uses' => 'UserController@postRegister']);
        Route::post('/remindme', ['uses' => 'UserController@postRemindme']);
    }
);