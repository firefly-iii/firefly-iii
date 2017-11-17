<?php
/**
 * HomeController.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use DB;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Log;
use Monolog\Handler\RotatingFileHandler;
use Preferences;
use Route as RouteFacade;
use Session;
use View;

/**
 * Class HomeController.
 */
class HomeController extends Controller
{
    /**
     * HomeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        View::share('title', 'Firefly III');
        View::share('mainTitleIcon', 'fa-fire');
    }

    /**
     * @param Request $request
     */
    public function dateRange(Request $request)
    {
        $start         = new Carbon($request->get('start'));
        $end           = new Carbon($request->get('end'));
        $label         = $request->get('label');
        $isCustomRange = false;

        Log::debug('Received dateRange', ['start' => $request->get('start'), 'end' => $request->get('end'), 'label' => $request->get('label')]);

        // check if the label is "everything" or "Custom range" which will betray
        // a possible problem with the budgets.
        if ($label === strval(trans('firefly.everything')) || $label === strval(trans('firefly.customRange'))) {
            $isCustomRange = true;
            Log::debug('Range is now marked as "custom".');
        }

        $diff = $start->diffInDays($end);

        if ($diff > 50) {
            Session::flash('warning', strval(trans('firefly.warning_much_data', ['days' => $diff])));
        }

        Session::put('is_custom_range', $isCustomRange);
        Session::put('start', $start);
        Session::put('end', $end);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function displayDebug(Request $request)
    {
        $phpVersion     = PHP_VERSION;
        $phpOs          = php_uname();
        $interface      = php_sapi_name();
        $now            = Carbon::create()->format('Y-m-d H:i:s e');
        $extensions     = join(', ', get_loaded_extensions());
        $drivers        = join(', ', DB::availableDrivers());
        $currentDriver  = DB::getDriverName();
        $userAgent      = $request->header('user-agent');
        $isSandstorm    = var_export(env('IS_SANDSTORM', 'unknown'), true);
        $isDocker       = var_export(env('IS_DOCKER', 'unknown'), true);
        $trustedProxies = env('TRUSTED_PROXIES', '(none)');

        // get latest log file:
        $logger     = Log::getMonolog();
        $handlers   = $logger->getHandlers();
        $logContent = '';
        foreach ($handlers as $handler) {
            if ($handler instanceof RotatingFileHandler) {
                $logFile = $handler->getUrl();
                if (null !== $logFile) {
                    $logContent = file_get_contents($logFile);
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
                'now',
                'drivers',
                'currentDriver',
                'userAgent',
                'phpOs',
                'interface',
                'logContent',
                'isDocker',
                'isSandstorm',
                'trustedProxies'
            )
        );
    }

    /**
     * @throws FireflyException
     */
    public function displayError()
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
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function flush(Request $request)
    {
        Preferences::mark();
        $request->session()->forget(['start', 'end', '_previous', 'viewRange', 'range', 'is_custom_range']);
        Artisan::call('cache:clear');

        return redirect(route('index'));
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $types = config('firefly.accountTypesByIdentifier.asset');
        $count = $repository->count($types);

        if (0 === $count) {
            return redirect(route('new-user.index'));
        }

        $subTitle     = trans('firefly.welcomeBack');
        $transactions = [];
        $frontPage    = Preferences::get(
            'frontPageAccounts',
            $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray()
        );
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end      = session('end', Carbon::now()->endOfMonth());
        $accounts = $repository->getAccountsById($frontPage->data);
        $showDeps = Preferences::get('showDepositsFrontpage', false)->data;
        $today    = new Carbon;

        // zero bills? Hide some elements from view.
        /** @var BillRepositoryInterface $billRepository */
        $billRepository = app(BillRepositoryInterface::class);
        $billCount      = $billRepository->getBills()->count();

        foreach ($accounts as $account) {
            $collector = app(JournalCollectorInterface::class);
            $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->setLimit(10)->setPage(1);
            $set            = $collector->getJournals();
            $transactions[] = [$set, $account];
        }

        return view(
            'index',
            compact('count', 'subTitle', 'transactions', 'showDeps', 'billCount','start','end','today')
        );
    }

    public function routes()
    {
        $set    = RouteFacade::getRoutes();
        $ignore = ['chart.', 'javascript.', 'json.', 'report-data.', 'popup.', 'debugbar.', 'attachments.download', 'attachments.preview',
                   'bills.rescan', 'budgets.income', 'currencies.def', 'error', 'flush', 'help.show', 'import.file',
                   'login', 'logout', 'password.reset', 'profile.confirm-email-change', 'profile.undo-email-change',
                   'register', 'report.options', 'routes', 'rule-groups.down', 'rule-groups.up', 'rules.up', 'rules.down',
                   'rules.select', 'search.search', 'test-flash', 'transactions.link.delete', 'transactions.link.switch',
                   'two-factor.lost', 'report.options',
        ];

        /** @var Route $route */
        foreach ($set as $route) {
            $name = $route->getName();
            if (null !== $name && in_array('GET', $route->methods()) && strlen($name) > 0) {
                $found = false;
                foreach ($ignore as $string) {
                    if (false !== strpos($name, $string)) {
                        $found = true;
                    }
                }
                if (!$found) {
                    echo 'touch ' . $route->getName() . '.md;';
                }
            }
        }

        return '&nbsp;';
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function testFlash()
    {
        Session::flash('success', 'This is a success message.');
        Session::flash('info', 'This is an info message.');
        Session::flash('warning', 'This is a warning.');
        Session::flash('error', 'This is an error!');

        return redirect(route('home'));
    }
}
