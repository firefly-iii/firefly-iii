<?php
/**
 * DebugController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use DB;
use Exception;
use FireflyIII\Http\Middleware\IsDemoUser;
use Illuminate\Http\Request;
use Log;
use Monolog\Handler\RotatingFileHandler;

/**
 * Class DebugController
 */
class DebugController extends Controller
{
    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(IsDemoUser::class);
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search  = ['~', '#'];
        $replace = ['\~', '# '];

        $phpVersion     = str_replace($search, $replace, PHP_VERSION);
        $phpOs          = str_replace($search, $replace, php_uname());
        $interface      = PHP_SAPI;
        $now            = Carbon::create()->format('Y-m-d H:i:s e');
        $extensions     = join(', ', get_loaded_extensions());
        $drivers        = join(', ', DB::availableDrivers());
        $currentDriver  = DB::getDriverName();
        $userAgent      = $request->header('user-agent');
        $isSandstorm    = var_export(env('IS_SANDSTORM', 'unknown'), true);
        $isDocker       = var_export(env('IS_DOCKER', 'unknown'), true);
        $trustedProxies = env('TRUSTED_PROXIES', '(none)');
        $displayErrors  = ini_get('display_errors');
        $errorReporting = $this->errorReporting(intval(ini_get('error_reporting')));
        $appEnv         = env('APP_ENV', '');
        $appDebug       = var_export(env('APP_DEBUG', false), true);
        $appLog         = env('APP_LOG', '');
        $appLogLevel    = env('APP_LOG_LEVEL', '');
        $packages       = $this->collectPackages();
        $cacheDriver    = env('CACHE_DRIVER', 'unknown');


        // get latest log file:
        $logger     = Log::getMonolog();
        $handlers   = $logger->getHandlers();
        $logContent = '';
        foreach ($handlers as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $logFile = $handler->getUrl();
                if (null !== $logFile) {
                    try {
                        $logContent = file_get_contents($logFile);
                    } catch (Exception $e) {
                        // don't care
                    }
                }
            }
        }
        // last few lines
        $logContent = 'Truncated from this point <----|' . substr($logContent, -4096);

        return view(
            'debug',
            compact(
                'phpVersion',
                'extensions',
                'carbon',
                'appEnv',
                'appDebug',
                'appLog',
                'appLogLevel',
                'now',
                'packages',
                'drivers',
                'currentDriver',
                'userAgent',
                'displayErrors',
                'errorReporting',
                'phpOs',
                'interface',
                'logContent',
                'cacheDriver',
                'isDocker',
                'isSandstorm',
                'trustedProxies'
            )
        );
    }

    /**
     * Some common combinations.
     *
     * @param int $value
     *
     * @return string
     */
    protected function errorReporting(int $value): string
    {
        $array = [
            -1                                                             => 'ALL errors',
            E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED                  => 'E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED',
            E_ALL                                                          => 'E_ALL',
            E_ALL & ~E_DEPRECATED & ~E_STRICT                              => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
            E_ALL & ~E_NOTICE                                              => 'E_ALL & ~E_NOTICE',
            E_ALL & ~E_NOTICE & ~E_STRICT                                  => 'E_ALL & ~E_NOTICE & ~E_STRICT',
            E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR => 'E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR',
        ];
        if (isset($array[$value])) {
            return $array[$value];
        }

        return strval($value); // @codeCoverageIgnore
    }

    /**
     * @return array
     */
    private function collectPackages(): array
    {
        $packages = [];
        $file     = realpath(__DIR__ . '/../../../vendor/composer/installed.json');
        if (!($file === false) && file_exists($file)) {
            // file exists!
            $content = file_get_contents($file);
            $json    = json_decode($content, true);
            foreach ($json as $package) {
                $packages[]
                    = [
                    'name'    => $package['name'],
                    'version' => $package['version'],
                ];
            }
        }

        return $packages;
    }
}