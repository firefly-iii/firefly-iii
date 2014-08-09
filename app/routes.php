<?php

// models:
Route::bind('account', function($value, $route)
    {
        if(Auth::check()) {
            return Account::
                where('id', $value)->
                where('user_id',Auth::user()->id)->first();
        }
        return null;
    });

Route::bind('accountname', function($value, $route)
    {
        if(Auth::check()) {
            $type = AccountType::where('description','Default account')->first();
            return Account::
                where('name', $value)->
                where('account_type_id',$type->id)->
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


// protected routes:
Route::group(['before' => 'auth'], function () {

        // account controller:
        Route::get('/accounts', ['uses' => 'AccountController@index', 'as' => 'accounts.index']);
        Route::get('/accounts/create', ['uses' => 'AccountController@create', 'as' => 'accounts.create']);
        Route::get('/accounts/{account}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);
        Route::get('/accounts/{account}/edit', ['uses' => 'AccountController@edit', 'as' => 'accounts.edit']);
        Route::get('/accounts/{account}/delete', ['uses' => 'AccountController@delete', 'as' => 'accounts.delete']);

        // budget controller:
        Route::get('/budgets',['uses' => 'BudgetController@indexByDate','as' => 'budgets.index']);
        Route::get('/budgets/create',['uses' => 'BudgetController@create', 'as' => 'budgets.create']);
        Route::get('/budgets/budget',['uses' => 'BudgetController@indexByBudget','as' => 'budgets.index.budget']);
        Route::get('/budgets/show/{budget}',['uses' => 'BudgetController@show', 'as' => 'budgets.show']);
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
        Route::get('/chart/home/info/{accountnameA}/{day}/{month}/{year}',
            ['uses' => 'ChartController@homeAccountInfo', 'as' => 'chart.info']);
        Route::get('/chart/categories/show/{category}', ['uses' => 'ChartController@categoryShowChart','as' => 'chart.showcategory']);

        // home controller
        Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);
        Route::get('/flush', ['uses' => 'HomeController@flush', 'as' => 'flush']);

        // JSON controller:
        Route::get('/json/beneficiaries', ['uses' => 'JsonController@beneficiaries', 'as' => 'json.beneficiaries']);
        Route::get('/json/categories', ['uses' => 'JsonController@categories', 'as' => 'json.categories']);

        // limit controller:
        Route::get('/budgets/limits/create/{budget?}',['uses' => 'LimitController@create','as' => 'budgets.limits.create']);
        Route::get('/budgets/limits/delete/{limit}',['uses' => 'LimitController@delete','as' => 'budgets.limits.delete']);
        Route::get('/budgets/limits/edit/{limit}',['uses' => 'LimitController@edit','as' => 'budgets.limits.edit']);

        // piggy bank controller
        Route::get('/piggybanks',['uses' => 'PiggybankController@index','as' => 'piggybanks.index']);
        Route::get('/piggybanks/create', ['uses' => 'PiggybankController@create','as' => 'piggybanks.create']);
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
        Route::get('/recurring/create',['uses' => 'RecurringController@create', 'as' => 'recurring.create']);
        Route::get('/recurring/edit/{recurring}',['uses' => 'RecurringController@edit','as' => 'recurring.edit']);
        Route::get('/recurring/delete/{recurring}',['uses' => 'RecurringController@delete','as' => 'recurring.delete']);

        // transaction controller:
        Route::get('/transactions/create/{what}', ['uses' => 'TransactionController@create', 'as' => 'transactions.create'])->where(['what' => 'withdrawal|deposit|transfer']);
        Route::get('/transaction/show/{tj}',['uses' => 'TransactionController@show','as' => 'transactions.show']);
        Route::get('/transaction/edit/{tj}',['uses' => 'TransactionController@edit','as' => 'transactions.edit']);
        Route::get('/transaction/delete/{tj}',['uses' => 'TransactionController@delete','as' => 'transactions.delete']);
        Route::get('/transactions/index',['uses' => 'TransactionController@index','as' => 'transactions.index']);

        // user controller
        Route::get('/logout', ['uses' => 'UserController@logout', 'as' => 'logout']);

        // migration controller
        Route::get('/migrate', ['uses' => 'MigrationController@index', 'as' => 'migrate']);

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
        Route::post('/budgets/limits/destroy/{id?}',['uses' => 'LimitController@destroy','as' => 'budgets.limits.destroy']);
        Route::post('/budgets/limits/update/{id?}',['uses' => 'LimitController@update','as' => 'budgets.limits.update']);


        // piggy bank controller
        Route::post('/piggybanks/store',['uses' => 'PiggybankController@store','as' => 'piggybanks.store']);
        Route::post('/piggybanks/update', ['uses' => 'PiggybankController@update','as' => 'piggybanks.update']);
        Route::post('/piggybanks/destroy/{piggybank}', ['uses' => 'PiggybankController@destroy','as' => 'piggybanks.destroy']);

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

        // migration controller
        Route::post('/migrate', ['uses' => 'MigrationController@postIndex']);

    }
);

// guest routes:
Route::group(['before' => 'guest'], function () {
        // dev import route:
        Route::get('/dev',['uses' => 'MigrationController@dev']);

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