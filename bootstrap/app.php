<?php
declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

bcscale(12);


$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    FireflyIII\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    FireflyIII\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    FireflyIII\Exceptions\Handler::class
);

/* Overrule logging */
$app->configureMonologUsing(
    function (Logger $monolog) use ($app) {
        $interface = php_sapi_name();
        $path      = $app->storagePath() . '/logs/ff3-' . $interface . '.log';
        $level     = 'debug';
        if ($app->bound('config')) {
            $level = $app->make('config')->get('app.log_level', 'debug');
        }
        $levels = [
            'debug'     => Logger::DEBUG,
            'info'      => Logger::INFO,
            'notice'    => Logger::NOTICE,
            'warning'   => Logger::WARNING,
            'error'     => Logger::ERROR,
            'critical'  => Logger::CRITICAL,
            'alert'     => Logger::ALERT,
            'emergency' => Logger::EMERGENCY,
        ];

        $useLevel = $levels[$level];

        $formatter = new LineFormatter(null, null, true, true);
        $handler   = new RotatingFileHandler($path, 5, $useLevel);
        $handler->setFormatter($formatter);
        $monolog->pushHandler($handler);
    }
);

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

return $app;
