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
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class Installer
 *
 * @codeCoverageIgnore
 *
 */
class Installer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     *
     *
     * @throws FireflyException
     *
     */
    public function handle($request, Closure $next)
    {
        Log::debug(sprintf('Installer middleware for URI %s', $request->url()));
        // ignore installer in test environment.
        if ('testing' === config('app.env')) {
            return $next($request);
        }
        // don't run installer when already in installer.
        $url    = $request->url();
        $strpos = stripos($url, '/install');
        if (!(false === $strpos)) {
            Log::debug(sprintf('URL is %s, will NOT run installer middleware', $url));

            return $next($request);
        }

        // run installer when no tables are present,
        // or when old scheme version
        // or when old firefly version
        if ($this->hasNoTables() || $this->oldDBVersion() || $this->oldVersion()) {
            return response()->redirectTo(route('installer.index'));
        }
        // update scheme version
        // update firefly version


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

    /**
     * Check if the tables are created and accounted for.
     *
     * @return bool
     * @throws FireflyException
     */
    private function hasNoTables(): bool
    {
        Log::debug('Now in routine hasNoTables()');

        try {
            DB::table('users')->count();
        } catch (QueryException $e) {
            $message = $e->getMessage();
            Log::error(sprintf('Error message trying to access users-table: %s', $message));
            if ($this->isAccessDenied($message)) {
                throw new FireflyException('It seems your database configuration is not correct. Please verify the username and password in your .env file.');
            }
            if ($this->noTablesExist($message)) {
                // redirect to UpdateController
                Log::warning('There are no Firefly III tables present. Redirect to migrate routine.');

                return true;

            }
            throw new FireflyException(sprintf('Could not access the database: %s', $message));
        }
        Log::debug('Everything seems OK with the tables.');

        return false;

    }

    /**
     * Check if the "db_version" variable is correct.
     *
     * @return bool
     */
    private function oldDBVersion(): bool
    {
        // older version in config than database?
        $configVersion = (int)config('firefly.db_version');
        $dbVersion     = (int)app('fireflyconfig')->getFresh('db_version', 1)->data;
        if ($configVersion > $dbVersion) {
            Log::warning(
                sprintf(
                    'The current configured version (%d) is older than the required version (%d). Redirect to migrate routine.', $dbVersion, $configVersion
                )
            );

            return true;
        }
        Log::info(sprintf('Configured DB version (%d) equals expected DB version (%d)', $dbVersion, $configVersion));

        return false;
    }

    /**
     * Check if the "firefly_version" variable is correct.
     *
     * @return bool
     */
    private function oldVersion(): bool
    {
        // version compare thing.
        $configVersion = (string)config('firefly.version');
        $dbVersion     = (string)app('fireflyconfig')->getFresh('ff3_version', '1.0')->data;
        if (1 === version_compare($configVersion, $dbVersion)) {
            Log::warning(
                sprintf(
                    'The current configured Firefly III version (%s) is older than the required version (%s). Redirect to migrate routine.', $dbVersion, $configVersion
                )
            );

            return true;
        }
        Log::info(sprintf('Installed Firefly III version (%s) equals expected Firefly III version (%s)', $dbVersion, $configVersion));

        return false;
    }
}
