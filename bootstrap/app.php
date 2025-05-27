<?php
/**
 * app.php
 * Copyright (c) 2019 james@firefly-iii.org.
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use FireflyIII\Exceptions\Handler;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all the various parts.
|
*/

bcscale(12);

if (!function_exists('envNonEmpty')) {
    /**
     * @param string               $key
     * @param string|int|bool|null $default
     *
     * @return mixed|null
     */
    function envNonEmpty(string $key, string | int | bool | null $default = null)
    {
        $result = env($key, $default); // @phpstan-ignore-line
        if ('' === $result) {
            return $default;
        }

        return $result;
    }
}

if (!function_exists('stringIsEqual')) {
    /**
     * @param string $left
     * @param string $right
     *
     * @return bool
     */
    function stringIsEqual(string $left, string $right): bool
    {
        return $left === $right;
    }
}

$app = new Application(
    (string)realpath(__DIR__ . '/../')
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
    Kernel::class,
    FireflyIII\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    FireflyIII\Console\Kernel::class
);

$app->singleton(
    ExceptionHandler::class,
    Handler::class
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
