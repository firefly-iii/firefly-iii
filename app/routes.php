<?php

// basic home views:
Route::get('/', ['uses' => 'HomeController@index','as' => 'index','before' => 'auth']);

// login, register, logout:
Route::get('/login',['uses' => 'UserController@login','as' => 'login','before' => 'guest']);
Route::get('/register',['uses' => 'UserController@register','as' => 'register','before' => 'guest']);
Route::get('/verify/{verification}',['uses' => 'UserController@verify','as' => 'verify','before' => 'guest']);
Route::get('/reset/{reset}',['uses' => 'UserController@reset','as' => 'reset','before' => 'guest']);
Route::get('/logout',['uses' => 'UserController@logout','as' => 'logout','before' => 'auth']);
Route::get('/remindme',['uses' => 'UserController@remindme','as' => 'remindme','before' => 'guest']);
Route::post('/login',['uses' => 'UserController@postLogin','before' => 'csrf|guest']);
Route::post('/register',['uses' => 'UserController@postRegister','before' => 'csrf|guest']);
Route::post('/remindme',['uses' => 'UserController@postRemindme','before' => 'csrf|guest']);

// profile (after login / logout)
Route::get('/profile',['uses' => 'ProfileController@index','as' => 'profile','before' => 'auth']);
Route::get('/profile/change-password',['uses' => 'ProfileController@changePassword','as' => 'change-password','before' => 'auth']);
Route::post('/profile/change-password',['uses' => 'ProfileController@postChangePassword','before' => 'csrf|auth']);

// migrate controller:
Route::get('/migrate',['uses' => 'MigrationController@index','as' => 'migrate','before' => 'auth']);
Route::post('/migrate',['uses' => 'MigrationController@postIndex','before' => 'csrf|auth']);