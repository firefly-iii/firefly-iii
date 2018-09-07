<?php

/**
 * Installer.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Middleware;

use Closure;
use DB;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class Installer
 * @codeCoverageIgnore
 *
 */
class Installer
{
    /**
     * Handle an incoming request.
     *
     * @throws FireflyException
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    public function handle($request, Closure $next)
    {
        if ('testing' === env('APP_ENV')) {
            return $next($request);
        }
        $url    = $request->url();
        $strpos = stripos($url, '/install');
        if (!(false === $strpos)) {
            Log::debug(sprintf('URL is %s, will NOT run installer middleware', $url));

            return $next($request);
        }
        // no tables present?
        try {
            DB::table('users')->count();
        } catch (QueryException $e) {
            $message = $e->getMessage();
            Log::error('Access denied: ' . $message);
            if ($this->isAccessDenied($message)) {
                throw new FireflyException('It seems your database configuration is not correct. Please verify the username and password in your .env file.');
            }
            if ($this->noTablesExist($message)) {
                // redirect to UpdateController
                Log::warning('There are no Firefly III tables present. Redirect to migrate routine.');

                return response()->redirectTo(route('installer.index'));
            }
            throw new FireflyException(sprintf('Could not access the database: %s', $message));
        }

        // older version in config than database?
        $configVersion = (int)config('firefly.db_version');
        $dbVersion     = (int)FireflyConfig::getFresh('db_version', 1)->data;
        if ($configVersion > $dbVersion) {
            Log::warning(
                sprintf(
                    'The current installed version (%d) is older than the required version (%d). Redirect to migrate routine.', $dbVersion, $configVersion
                )
            );

            // redirect to migrate routine:
            return response()->redirectTo(route('installer.index'));
        }

        return $next($request);
    }

    /**
     * Is access denied error.
     *
     * @param string $message
     *
     * @return bool
     */
    protected function isAccessDenied(string $message): bool
    {
        return !(false === stripos($message, 'Access denied'));
    }

    /**
     * Is no tables exist error.
     *
     * @param string $message
     *
     * @return bool
     */
    protected function noTablesExist(string $message): bool
    {
        return !(false === stripos($message, 'Base table or view not found'));
    }
}
