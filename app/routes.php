<?php
Route::bind('account', function ($value, $route) {
        if(Auth::user()) {
            return Auth::user()->accounts()->find($value);
        } else {
            return null;
        }
    });



// protected routes:
Route::group(['before' => 'auth'], function () {

        // home controller
        Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);

        // chart controller
        Route::get('/chart/home/{account?}', ['uses' => 'ChartController@home', 'as' => 'chart.home']);

        // preferences controller
        Route::get('/preferences', ['uses' => 'PreferencesController@index', 'as' => 'preferences']);

        // user controller
        Route::get('/logout', ['uses' => 'UserController@logout', 'as' => 'logout']);

        //profile controller
        Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
        Route::get('/profile/change-password',['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);

        // account controller:
        Route::get('/accounts', ['uses' => 'AccountController@index', 'as' => 'accounts.index']);
        Route::get('/accounts/create', ['uses' => 'AccountController@create', 'as' => 'accounts.create']);
        Route::get('/accounts/{account}', ['uses' => 'AccountController@show', 'as' => 'accounts.show']);

        // migration controller
        Route::get('/migrate', ['uses' => 'MigrationController@index', 'as' => 'migrate']);

    }
);

// protected + csrf routes (POST)
Route::group(['before' => 'csrf|auth'], function () {
        // profile controller
        Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword']);

        // migration controller
        Route::post('/migrate', ['uses' => 'MigrationController@postIndex']);

        // preferences controller
        Route::post('/preferences', ['uses' => 'PreferencesController@postIndex']);

        // account controller:
        Route::get('/accounts/store', ['uses' => 'AccountController@store', 'as' => 'accounts.store']);

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