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

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use FireflyIII\Support\Models\AccountBalanceCalculator;
use FireflyIII\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Monolog\Handler\RotatingFileHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DebugController
 */
class DebugController extends Controller
{
    use GetConfigurationData;

    /**
     * DebugController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(IsDemoUser::class)->except(['displayError']);
    }

    public function routes(Request $request): never
    {
        if (!auth()->user()->hasRole('owner')) {
            throw new NotFoundHttpException();
        }

        /** @var iterable $routes */
        $routes = Route::getRoutes();

        if ('true' === $request->get('api')) {
            $collection = [];
            $i          = 0;

            echo 'PATHS="';

            /** @var \Illuminate\Routing\Route $route */
            foreach ($routes as $route) {
                ++$i;
                // skip API and other routes.
                if (!str_starts_with($route->uri(), 'api/v1')
                ) {
                    continue;
                }
                // skip non GET routes
                if (!in_array('GET', $route->methods(), true)) {
                    continue;
                }
                // no name route:
                if (null === $route->getName()) {
                    var_dump($route);

                    exit;
                }

                echo substr($route->uri(), 3);
                if (0 === $i % 5) {
                    echo '"<br>PATHS="${PATHS},';
                }
                if (0 !== $i % 5) {
                    echo ',';
                }
            }

            exit;
        }



        $return = [];

        /** @var \Illuminate\Routing\Route $route */
        foreach ($routes as $route) {
            // skip API and other routes.
            if (
                str_starts_with($route->uri(), 'api')
                || str_starts_with($route->uri(), '_debugbar')
                || str_starts_with($route->uri(), '_ignition')
                || str_starts_with($route->uri(), 'oauth')
                || str_starts_with($route->uri(), 'chart')
                || str_starts_with($route->uri(), 'v1/jscript')
                || str_starts_with($route->uri(), 'v2/jscript')
                || str_starts_with($route->uri(), 'json')
                || str_starts_with($route->uri(), 'sanctum')
            ) {
                continue;
            }
            // skip non GET routes
            if (!in_array('GET', $route->methods(), true)) {
                continue;
            }
            // no name route:
            if (null === $route->getName()) {
                var_dump($route);

                exit;
            }
            if (!str_contains($route->uri(), '{')) {

                $return[$route->getName()] = route($route->getName());

                continue;
            }
            $params                    = [];
            foreach ($route->parameterNames() as $name) {
                $params[] = $this->getParameter($name);
            }
            $return[$route->getName()] = route($route->getName(), $params);
        }
        $count  = 0;
        echo '<hr>';
        echo '<h1>Routes</h1>';
        echo sprintf('<h2>%s</h2>', $count);
        foreach ($return as $name => $path) {
            echo sprintf('<a href="%1$s">%2$s</a><br>', $path, $name).PHP_EOL;
            ++$count;
            if (0 === $count % 10) {
                echo '<hr>';
                echo sprintf('<h2>%s</h2>', $count);
            }
        }

        exit;
    }

    /**
     * Show all possible errors.
     *
     * @throws FireflyException
     */
    public function displayError(): void
    {
        app('log')->debug('This is a test message at the DEBUG level.');
        app('log')->info('This is a test message at the INFO level.');
        Log::notice('This is a test message at the NOTICE level.');
        app('log')->warning('This is a test message at the WARNING level.');
        app('log')->error('This is a test message at the ERROR level.');
        Log::critical('This is a test message at the CRITICAL level.');
        Log::alert('This is a test message at the ALERT level.');
        Log::emergency('This is a test message at the EMERGENCY level.');

        throw new FireflyException('A very simple test error.');
    }

    /**
     * Clear log and session.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function flush(Request $request)
    {
        Preferences::mark();
        $request->session()->forget(['start', 'end', '_previous', 'viewRange', 'range', 'is_custom_range', 'temp-mfa-secret', 'temp-mfa-codes']);

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // also do some recalculations.
        Artisan::call('correction:recalculates-liabilities');
        AccountBalanceCalculator::recalculateAll(false);

        try {
            Artisan::call('twig:clean');
        } catch (\Exception $e) {  // intentional generic exception
            throw new FireflyException($e->getMessage(), 0, $e);
        }

        Artisan::call('view:clear');

        return redirect(route('index'));
    }

    /**
     * Show debug info.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function index()
    {
        $table      = $this->generateTable();
        $table      = str_replace(["\n", "\t", '  '], '', $table);
        $now        = now(config('app.timezone'))->format('Y-m-d H:i:s');

        // get latest log file:
        $logger     = Log::driver();
        // PHPstan doesn't recognize the method because of its polymorphic nature.
        $handlers   = $logger->getHandlers(); // @phpstan-ignore-line
        $logContent = '';
        foreach ($handlers as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $logFile = $handler->getUrl();
                if (null !== $logFile && file_exists($logFile)) {
                    $logContent = \Safe\file_get_contents($logFile);
                }
            }
        }
        if ('' !== $logContent) {
            // last few lines
            $logContent = 'Truncated from this point <----|'.substr((string) $logContent, -16384);
        }

        return view('debug', compact('table', 'now', 'logContent'));
    }

    private function generateTable(): string
    {
        // system information:
        $system = $this->getSystemInformation();
        $docker = $this->getBuildInfo();
        $app    = $this->getAppInfo();
        $user   = $this->getUserInfo();

        return (string) view('partials.debug-table', compact('system', 'docker', 'app', 'user'));
    }

    private function getSystemInformation(): array
    {
        $maxFileSize   = Steam::phpBytes((string) \Safe\ini_get('upload_max_filesize'));
        $maxPostSize   = Steam::phpBytes((string) \Safe\ini_get('post_max_size'));
        $drivers       = DB::availableDrivers();
        $currentDriver = DB::getDriverName();

        return [
            'db_version'      => app('fireflyconfig')->get('db_version', 1)->data,
            'php_version'     => PHP_VERSION,
            'php_os'          => PHP_OS,
            'uname'           => php_uname('m'),
            'interface'       => \PHP_SAPI,
            'bits'            => \PHP_INT_SIZE * 8,
            'bcscale'         => bcscale(),
            'display_errors'  => \Safe\ini_get('display_errors'),
            'error_reporting' => $this->errorReporting((int) \Safe\ini_get('error_reporting')),
            'upload_size'     => min($maxFileSize, $maxPostSize),
            'all_drivers'     => $drivers,
            'current_driver'  => $currentDriver,
        ];
    }

    private function getBuildInfo(): array
    {
        $return = [
            'is_docker'       => env('IS_DOCKER', false), // @phpstan-ignore-line
            'build'           => '(unknown)',
            'build_date'      => '(unknown)',
            'base_build'      => '(unknown)',
            'base_build_date' => '(unknown)',
        ];

        try {
            if (file_exists('/var/www/counter-main.txt')) {
                $return['build'] = trim((string) \Safe\file_get_contents('/var/www/counter-main.txt'));
                app('log')->debug(sprintf('build is now "%s"', $return['build']));
            }
        } catch (\Exception $e) {
            app('log')->debug('Could not check build counter, but thats ok.');
            app('log')->warning($e->getMessage());
        }

        try {
            if (file_exists('/var/www/build-date-main.txt')) {
                $return['build_date'] = trim((string) \Safe\file_get_contents('/var/www/build-date-main.txt'));
            }
        } catch (\Exception $e) {
            app('log')->debug('Could not check build date, but thats ok.');
            app('log')->warning($e->getMessage());
        }
        if ('' !== (string) env('BASE_IMAGE_BUILD')) { // @phpstan-ignore-line
            $return['base_build'] = env('BASE_IMAGE_BUILD'); // @phpstan-ignore-line
        }
        if ('' !== (string) env('BASE_IMAGE_DATE')) { // @phpstan-ignore-line
            $return['base_build_date'] = env('BASE_IMAGE_DATE'); // @phpstan-ignore-line
        }

        return $return;
    }

    private function getAppInfo(): array
    {
        $userGuard      = config('auth.defaults.guard');

        $config         = app('fireflyconfig')->get('last_rt_job', 0);
        $lastTime       = (int) $config->data;
        $lastCronjob    = 'never';
        $lastCronjobAgo = 'never';
        if ($lastTime > 0) {
            $carbon         = Carbon::createFromTimestamp($lastTime);
            $lastCronjob    = $carbon->format('Y-m-d H:i:s');
            $lastCronjobAgo = $carbon->locale('en')->diffForHumans(); // @phpstan-ignore-line
        }

        return [
            'debug'              => var_export(config('app.debug'), true),
            'audit_log_channel'  => envNonEmpty('AUDIT_LOG_CHANNEL', '(empty)'),
            'default_language'   => (string) config('firefly.default_language'),
            'default_locale'     => (string) config('firefly.default_locale'),
            'remote_header'      => 'remote_user_guard' === $userGuard ? config('auth.guard_header') : 'N/A',
            'remote_mail_header' => 'remote_user_guard' === $userGuard ? config('auth.guard_email') : 'N/A',
            'stateful_domains'   => implode(', ', config('sanctum.stateful')),

            // the dates for the cron job are based on the recurring cron job's times.
            // any of the cron jobs will do, they always run at the same time.
            // but this job is the oldest, so the biggest chance it ran once

            'last_cronjob'       => $lastCronjob,
            'last_cronjob_ago'   => $lastCronjobAgo,
        ];
    }

    private function getUserInfo(): array
    {
        $userFlags      = $this->getUserFlags();

        // user info
        $userAgent      = request()->header('user-agent');

        // set languages, see what happens:
        $original       = setlocale(LC_ALL, '0');
        $localeAttempts = [];
        $parts          = Steam::getLocaleArray(Steam::getLocale());
        foreach ($parts as $code) {
            $code                  = trim($code);
            app('log')->debug(sprintf('Trying to set %s', $code));
            $result                = setlocale(LC_ALL, $code);
            $localeAttempts[$code] = $result === $code;
        }
        setlocale(LC_ALL, (string) $original);

        return [
            'user_id'           => auth()->user()->id,
            'user_count'        => User::count(),
            'user_flags'        => $userFlags,
            'user_agent'        => $userAgent,
            'native'            => Amount::getNativeCurrency(),
            'convert_to_native' => Amount::convertToNative(),
            'locale_attempts'   => $localeAttempts,
            'locale'            => Steam::getLocale(),
            'language'          => Steam::getLanguage(),
            'view_range'        => Preferences::get('viewRange', '1M')->data,
        ];
    }

    private function getUserFlags(): string
    {
        $flags      = [];

        /** @var User $user */
        $user       = auth()->user();

        // has liabilities
        if ($user->accounts()->accountTypeIn([AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value])->count() > 0) {
            $flags[] = '<span title="Has liabilities">:credit_card:</span>';
        }

        // has piggies
        $repository = app(PiggyBankRepositoryInterface::class);
        $repository->setUser($user);

        if ($repository->getPiggyBanks()->count() > 0) {
            $flags[] = '<span title="Has piggy banks">:pig:</span>';
        }

        // has stored reconciliations
        $type       = TransactionType::whereType(TransactionTypeEnum::RECONCILIATION->value)->first();
        if ($user->transactionJournals()->where('transaction_type_id', $type->id)->count() > 0) {
            $flags[] = '<span title="Has reconciled">:ledger:</span>';
        }

        // has used importer?

        // has rules
        if ($user->rules()->count() > 0) {
            $flags[] = '<span title="Has rules">:wrench:</span>';
        }

        // has recurring transactions
        if ($user->recurrences()->count() > 0) {
            $flags[] = '<span title="Has recurring transactions">:clock130:</span>';
        }

        // has groups
        if ($user->objectGroups()->count() > 0) {
            $flags[] = '<span title="Has object groups">:bookmark_tabs:</span>';
        }

        // uses bills
        if ($user->bills()->count() > 0) {
            $flags[] = '<span title="Has subscriptions">:email:</span>';
        }

        return implode(' ', $flags);
    }

    /**
     * Flash all types of messages.
     *
     * @return Redirector|RedirectResponse
     */
    public function testFlash(Request $request)
    {
        $request->session()->flash('success', 'This is a success message.');
        $request->session()->flash('info', 'This is an info message.');
        $request->session()->flash('warning', 'This is a warning.');
        $request->session()->flash('error', 'This is an error!');

        return redirect(route('home'));
    }

    private function getParameter(string $name): string
    {
        switch ($name) {
            default:
                throw new FireflyException(sprintf('Unknown parameter "%s"', $name));

            case 'cliToken':
            case 'token':
            case 'code':
            case 'oldAddressHash':
                return 'fake-token';

            case 'objectType':
                return 'asset';

            case 'account':
                return '1';

            case 'start_date':
                return '20241201';

            case 'end_date':
                return '20241231';

            case 'attachment':
                return '1';

            case 'bill':
                return '1';

            case 'budget':
                return '1';

            case 'budgetLimit':
                return '1';

            case 'category':
                return '1';

            case 'currency':
                return '1';

            case 'fromCurrencyCode':
                return 'EUR';

            case 'toCurrencyCode':
                return 'USD';

            case 'accountList':
                return '1,6';

            case 'budgetList':
                return '1,2';

            case 'categoryList':
                return '1,2';

            case 'doubleList':
                return '1,2';

            case 'tagList':
                return '1,2';

            case 'tag':
                return '1';

            case 'piggyBank':
                return '1';

            case 'objectGroup':
                return '1';

            case 'route':
                return 'accounts';

            case 'specificPage':
                return 'show';

            case 'recurrence':
                return '1';

            case 'tj':
                return '1';

            case 'reportType':
                return 'default';

            case 'ruleGroup':
                return '1';

            case 'rule':
                return '1';

            case 'tagOrId':
                return '1';

            case 'transactionGroup':
                return '1';

            case 'journalList':
                return '1,2';

            case 'transactionType':
                return 'withdrawal';

            case 'journalLink':
                return '1';

            case 'webhook':
                return '1';

            case 'user':
                return '1';

            case 'linkType':
                return '1';

            case 'userGroup':
                return '1';

            case 'date':
                return '20241201';

        }
    }
}
