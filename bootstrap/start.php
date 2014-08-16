<?php

use Carbon\Carbon;
use Firefly\Exception\FireflyException;

if (!function_exists('mf')) {
    function mf($n, $coloured = true)
    {

        $n = floatval($n);
        $n = round($n, 2);
        $string = number_format($n, 2, ',', '.');

        if ($coloured === true && $n === 0.0) {
            return '<span style="color:#999">&#128; ' . $string . '</span>';
        }
        if ($coloured === true && $n > 0) {
            return '<span class="text-success">&#128; ' . $string . '</span>';
        }
        if ($coloured === true && $n < 0) {
            return '<span class="text-danger">&#128; ' . $string . '</span>';
        }

        return '&#128; ' . $string;
    }
}


$app = new Illuminate\Foundation\Application;


/*
|--------------------------------------------------------------------------
| Detect The Application Environment
|--------------------------------------------------------------------------
|
| Laravel takes a dead simple approach to your application environments
| so you can just specify a machine name for the host that matches a
| given environment, then we will automatically detect it for you.
|
*/

$env = $app->detectEnvironment(
    function () {
        // Default to production if LARAVEL_ENV is not set
        return getenv('LARAVEL_ENV') ? : 'production';
    }
);


/*
|--------------------------------------------------------------------------
| Bind Paths
|--------------------------------------------------------------------------
|
| Here we are binding the paths configured in paths.php to the app. You
| should not be changing these here. If you need to change these you
| may do so within the paths.php file and they will be bound here.
|
*/

$app->bindInstallPaths(require __DIR__ . '/paths.php');

/*
|--------------------------------------------------------------------------
| Load The Application
|--------------------------------------------------------------------------
|
| Here we will load this Illuminate application. We will keep this in a
| separate location so we can isolate the creation of an application
| from the actual running of the application with a given request.
|
*/

$framework = $app['path.base'] .
    '/vendor/laravel/framework/src';

require $framework . '/Illuminate/Foundation/start.php';

Event::subscribe('Firefly\Helper\Form\FormTrigger');

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

// do something with events:
Event::subscribe('Firefly\Trigger\Limits\EloquentLimitTrigger');
Event::subscribe('Firefly\Trigger\Piggybanks\EloquentPiggybankTrigger');

//App::booted(
//    function () {
//        $forms = new \Firefly\Helper\Form\FormTrigger;
//        $forms->registerFormExtensions();
//    }
//);




return $app;
