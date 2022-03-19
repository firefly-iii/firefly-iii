<?php
/**
 * DebugController.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace FireflyIII\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use DB;
use Exception;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\View\View;
use Log;
use Monolog\Handler\RotatingFileHandler;
use Route as RouteFacade;

/**
 * Class DebugController
 *
 */
class DebugController extends Controller
{
    use GetConfigurationData;

    /**
     * DebugController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(IsDemoUser::class)->except(['displayError']);
    }

    /**
     * Show all possible errors.
     *
     * @throws FireflyException
     */
    public function displayError(): void
    {
        Log::debug('This is a test message at the DEBUG level.');
        Log::info('This is a test message at the INFO level.');
        Log::notice('This is a test message at the NOTICE level.');
        Log::warning('This is a test message at the WARNING level.');
        Log::error('This is a test message at the ERROR level.');
        Log::critical('This is a test message at the CRITICAL level.');
        Log::alert('This is a test message at the ALERT level.');
        Log::emergency('This is a test message at the EMERGENCY level.');
        throw new FireflyException('A very simple test error.');
    }

    /**
     * Clear log and session.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Redirector
     */
    public function flush(Request $request)
    {
        app('preferences')->mark();
        $request->session()->forget(['start', 'end', '_previous', 'viewRange', 'range', 'is_custom_range']);
        Log::debug('Call cache:clear...');
        Artisan::call('cache:clear');
        Log::debug('Call config:clear...');
        Artisan::call('config:clear');
        Log::debug('Call route:clear...');
        Artisan::call('route:clear');
        Log::debug('Call twig:clean...');
        try {
            Artisan::call('twig:clean');

        } catch (Exception $e) { // @phpstan-ignore-line
            // @ignoreException
        }

        Log::debug('Call view:clear...');
        Artisan::call('view:clear');
        Log::debug('Done! Redirecting...');

        return redirect(route('index'));
    }

    /**
     * Show debug info.
     *
     * @param Request $request
     *
     * @return Factory|View
     * @throws FireflyException
     */
    public function index(Request $request)
    {
        // basic scope information:
        $now               = Carbon::now()->format('Y-m-d H:i:s e');
        $buildNr           = '(unknown)';
        $buildDate         = '(unknown)';
        $expectedDBversion = config('firefly.db_version');
        $foundDBversion    = FireflyConfig::get('db_version', 1)->data;
        if (file_exists('/var/www/counter-main.txt')) {
            $buildNr = trim(file_get_contents('/var/www/counter-main.txt'));
        }
        if (file_exists('/var/www/build-date-main.txt')) {
            $buildDate = trim(file_get_contents('/var/www/build-date-main.txt'));
        }
        $phpVersion = PHP_VERSION;
        $phpOs      = PHP_OS;

        // system information
        $tz              = env('TZ');
        $appEnv          = config('app.env');
        $appDebug        = var_export(config('app.debug'), true);
        $cacheDriver     = config('cache.default');
        $logChannel      = config('logging.default');
        $appLogLevel     = config('logging.level');
        $displayErrors   = ini_get('display_errors');
        $errorReporting  = $this->errorReporting((int) ini_get('error_reporting'));
        $interface       = PHP_SAPI;
        $defaultLanguage = (string) config('firefly.default_language');
        $defaultLocale   = (string) config('firefly.default_locale');
        $bcscale         = bcscale();
        $drivers         = implode(', ', DB::availableDrivers());
        $currentDriver   = DB::getDriverName();
        $trustedProxies  = config('firefly.trusted_proxies');

        // user info
        $loginProvider    = config('auth.providers.users.driver');
        $userGuard        = config('auth.defaults.guard');
        $remoteHeader     = $userGuard === 'remote_user_guard' ? config('auth.guard_header') : 'N/A';
        $remoteMailHeader = $userGuard === 'remote_user_guard' ? config('auth.guard_email') : 'N/A';
        $userLanguage     = app('steam')->getLanguage();
        $userLocale       = app('steam')->getLocale();
        $userAgent        = $request->header('user-agent');
        $stateful         = join(', ', config('sanctum.stateful'));


        // expected + found DB version:

        // some new vars.
        $isDocker = env('IS_DOCKER', false);

        // set languages, see what happens:
        $original       = setlocale(LC_ALL, 0);
        $localeAttempts = [];
        $parts          = app('steam')->getLocaleArray(app('steam')->getLocale());
        foreach ($parts as $code) {
            $code = trim($code);
            Log::debug(sprintf('Trying to set %s', $code));
            $localeAttempts[$code] = var_export(setlocale(LC_ALL, $code), true);
        }
        setlocale(LC_ALL, $original);

        // get latest log file:
        $logger     = Log::driver();
        $handlers   = $logger->getHandlers();
        $logContent = '';
        foreach ($handlers as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $logFile = $handler->getUrl();
                if (null !== $logFile) {
                    try {
                        $logContent = file_get_contents($logFile);

                    } catch (Exception $e) { // @phpstan-ignore-line
                        // @ignoreException
                    }

                }
            }
        }
        if ('' !== $logContent) {
            // last few lines
            $logContent = 'Truncated from this point <----|' . substr($logContent, -8192);
        }

        return view(
            'debug',
            compact(
                'phpVersion',
                'localeAttempts',
                'expectedDBversion',
                'foundDBversion',
                'appEnv',
                'appDebug',
                'logChannel',
                'stateful',
                'tz',
                'appLogLevel',
                'remoteHeader',
                'remoteMailHeader',
                'now',
                'drivers',
                'currentDriver',
                'userGuard',
                'loginProvider',
                'buildNr',
                'buildDate',
                'bcscale',
                'userAgent',
                'displayErrors',
                'errorReporting',
                'phpOs',
                'interface',
                'logContent',
                'cacheDriver',
                'trustedProxies',
                'userLanguage',
                'userLocale',
                'defaultLanguage',
                'defaultLocale',
                'isDocker'

            )
        );
    }

    /**
     * Return all possible routes.
     *
     * @return string
     */
    public function routes(): string
    {
        $set    = RouteFacade::getRoutes();
        $ignore = ['chart.', 'javascript.', 'json.', 'report-data.', 'popup.', 'debugbar.', 'attachments.download', 'attachments.preview',
                   'bills.rescan', 'budgets.income', 'currencies.def', 'error', 'flush', 'help.show',
                   'login', 'logout', 'password.reset', 'profile.confirm-email-change', 'profile.undo-email-change',
                   'register', 'report.options', 'routes', 'rule-groups.down', 'rule-groups.up', 'rules.up', 'rules.down',
                   'rules.select', 'search.search', 'test-flash', 'transactions.link.delete', 'transactions.link.switch',
                   'two-factor.lost', 'reports.options', 'debug',
                   'preferences.delete-code', 'rules.test-triggers', 'piggy-banks.remove-money', 'piggy-banks.add-money',
                   'accounts.reconcile.transactions', 'accounts.reconcile.overview',
                   'transactions.clone', 'two-factor.index', 'api.v1', 'installer.', 'attachments.view', 'recurring.events',
                   'recurring.suggest',
        ];
        $return = '&nbsp;';
        /** @var Route $route */
        foreach ($set as $route) {
            $name = (string) $route->getName();
            if (in_array('GET', $route->methods(), true)) {
                $found = false;
                foreach ($ignore as $string) {
                    if (false !== stripos($name, $string)) {
                        $found = true;
                        break;
                    }
                }
                if (false === $found) {
                    $return .= 'touch ' . $route->getName() . '.md;';
                }
            }
        }

        return $return;
    }

    /**
     * Flash all types of messages.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Redirector
     */
    public function testFlash(Request $request)
    {
        $request->session()->flash('success', 'This is a success message.');
        $request->session()->flash('info', 'This is an info message.');
        $request->session()->flash('warning', 'This is a warning.');
        $request->session()->flash('error', 'This is an error!');

        return redirect(route('home'));
    }
}
