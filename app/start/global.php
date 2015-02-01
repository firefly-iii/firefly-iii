<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(
    [

        app_path() . '/commands', app_path() . '/controllers', app_path() . '/models', app_path() . '/database/seeds',

    ]
);

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

Log::useDailyFiles(storage_path('logs') . DIRECTORY_SEPARATOR . 'laravel.log', 3, Config::get('app.log_level'));

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(
    function (Exception $exception, $code) {
        Log::error($code . ': ' . $exception);
    }
);

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(
    function () {
        return Response::make("Be right back!", 503);
    }
);

// forms
\Form::macro(
    'ffText', function ($name, $value = null, array $options = []) {
    return \FireflyIII\Form\Form::ffText($name, $value, $options);
}
);
\Form::macro(
    'ffSelect', function ($name, array $list = [], $selected = null, array $options = []) {
    return \FireflyIII\Form\Form::ffSelect($name, $list, $selected, $options);
}
);
\Form::macro(
    'ffInteger', function ($name, $value = null, array $options = []) {
    return \FireflyIII\Form\Form::ffInteger($name, $value, $options);
}
);
\Form::macro(
    'ffAmount', function ($name, $value = null, array $options = []) {
    return \FireflyIII\Form\Form::ffAmount($name, $value, $options);
}
);
\Form::macro(
    'ffBalance', function ($name, $value = null, array $options = []) {
    return \FireflyIII\Form\Form::ffBalance($name, $value, $options);
}
);
\Form::macro(
    'ffDate', function ($name, $value = null, array $options = []) {
    return \FireflyIII\Form\Form::ffDate($name, $value, $options);
}
);
\Form::macro(
    'ffTags', function ($name, $value = null, array $options = []) {
    return \FireflyIII\Form\Form::ffTags($name, $value, $options);
}
);
\Form::macro(
    'ffCheckbox', function ($name, $value = 1, $checked = null, $options = []) {
    return \FireflyIII\Form\Form::ffCheckbox($name, $value, $checked, $options);
}
);
\Form::macro(
    'ffOptionsList', function ($type, $name) {
    return \FireflyIII\Form\Form::ffOptionsList($type, $name);
}
);


/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

/** @noinspection PhpIncludeInspection */
require app_path() . '/filters.php';
