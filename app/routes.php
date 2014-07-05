<?php

// protected routes:
Route::group(['before' => 'auth'], function () {

        // home controller
        Route::get('/', ['uses' => 'HomeController@index', 'as' => 'index']);

        // user controller
        Route::get('/logout', ['uses' => 'UserController@logout', 'as' => 'logout']);

        //profile controller
        Route::get('/profile', ['uses' => 'ProfileController@index', 'as' => 'profile']);
        Route::get('/profile/change-password',['uses' => 'ProfileController@changePassword', 'as' => 'change-password']);

        // migration controller
        Route::get('/migrate', ['uses' => 'MigrationController@index', 'as' => 'migrate']);
    }
);

// protected + csrf routes
Route::group(['before' => 'csrf|auth'], function () {
        // profile controller
        Route::post('/profile/change-password', ['uses' => 'ProfileController@postChangePassword']);

        // migration controller
        Route::post('/migrate', ['uses' => 'MigrationController@postIndex']);

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