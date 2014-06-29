<?php
Route::get('/', ['uses' => 'HomeController@index','as' => 'index','before' => 'auth']);

// login, register, logout:
Route::get('/login',['uses' => 'UserController@login','as' => 'login','before' => 'guest']);
Route::get('/register',['uses' => 'UserController@register','as' => 'register','before' => 'guest']);
Route::get('/verify/{verification}',['uses' => 'UserController@verify','as' => 'verify','before' => 'guest']);

Route::post('/login',['uses' => 'UserController@postLogin','before' => 'csrf|guest']);
Route::post('/register',['uses' => 'UserController@postRegister','before' => 'csrf|guest']);
