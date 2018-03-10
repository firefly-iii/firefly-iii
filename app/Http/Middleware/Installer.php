<?php

namespace FireflyIII\Http\Middleware;

use Closure;
use DB;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class Installer
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
     */
    public function handle($request, Closure $next)
    {
        if(env('APP_ENV') === 'testing') {
            return $next($request);
        }
        $url = $request->url();
        $strpos = stripos($url, '/install');
        if (!($strpos === false)) {
            Log::debug(sprintf('URL is %s, will NOT run installer middleware', $url));
            return $next($request);
        }
        Log::debug(sprintf('URL is %s, will run installer middleware', $url));

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
        $configVersion = intval(config('firefly.db_version'));
        $dbVersion     = intval(FireflyConfig::getFresh('db_version', 1)->data);
        if ($configVersion > $dbVersion) {
            Log::warning(sprintf(
                'The current installed version (%d) is older than the required version (%d). Redirect to migrate routine.', $dbVersion, $configVersion
            ));

            // redirect to migrate routine:
            return response()->redirectTo(route('installer.index'));
        }
        return $next($request);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    protected function isAccessDenied(string $message): bool
    {
        return !(stripos($message, 'Access denied') === false);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    protected function noTablesExist(string $message): bool
    {
        return !(stripos($message, 'Base table or view not found') === false);
    }
}
