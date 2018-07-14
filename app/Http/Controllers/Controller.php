<?php
/**
 * Controller.php
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

use FireflyConfig;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Log;
use Route;
use URL;

/**
 * Class Controller.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** @var string */
    protected $dateTimeFormat;
    /** @var string */
    protected $monthAndDayFormat;
    /** @var string */
    protected $monthFormat;
    /** @var string */
    protected $redirectUri = '/';

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        // for transaction lists:
        app('view')->share('hideBudgets', false);
        app('view')->share('hideCategories', false);
        app('view')->share('hideBills', false);
        app('view')->share('hideTags', false);

        // is site a demo site?
        $isDemoSite = FireflyConfig::get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        app('view')->share('IS_DEMO_SITE', $isDemoSite);
        app('view')->share('DEMO_USERNAME', env('DEMO_USERNAME', ''));
        app('view')->share('DEMO_PASSWORD', env('DEMO_PASSWORD', ''));
        app('view')->share('FF_VERSION', config('firefly.version'));

        $this->middleware(
            function ($request, $next) {
                // translations for specific strings:
                $this->monthFormat       = (string)trans('config.month');
                $this->monthAndDayFormat = (string)trans('config.month_and_day');
                $this->dateTimeFormat    = (string)trans('config.date_time');

                // get shown-intro-preference:
                if (auth()->check()) {
                    // some routes have a "what" parameter, which indicates a special page:
                    $specificPage = null === Route::current()->parameter('what') ? '' : '_' . Route::current()->parameter('what');
                    $page         = str_replace('.', '_', Route::currentRouteName());

                    // indicator if user has seen the help for this page ( + special page):
                    $key = 'shown_demo_' . $page . $specificPage;
                    // is there an intro for this route?
                    $intro        = config('intro.' . $page);
                    $specialIntro = config('intro.' . $page . $specificPage);
                    $shownDemo    = true;

                    // either must be array and either must be > 0
                    if ((\is_array($intro) || \is_array($specialIntro)) && (\count($intro) > 0 || \count($specialIntro) > 0)) {
                        $shownDemo = app('preferences')->get($key, false)->data;
                        Log::debug(sprintf('Check if user has already seen intro with key "%s". Result is %d', $key, $shownDemo));
                    }

                    // share language
                    $language = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;

                    app('view')->share('language', $language);
                    app('view')->share('shownDemo', $shownDemo);
                    app('view')->share('current_route_name', $page);
                    app('view')->share('original_route_name', Route::currentRouteName());
                }

                return $next($request);
            }
        );
    }

    /**
     * Functionality:.
     *
     * - If the $identifier contains the word "delete" then a remembered uri with the text "/show/" in it will not be returned but instead the index (/)
     *   will be returned.
     * - If the remembered uri contains "javascript/" the remembered uri will not be returned but instead the index (/) will be returned.
     *
     * @param string $identifier
     *
     * @return string
     */
    protected function getPreviousUri(string $identifier): string
    {
        $uri = (string)session($identifier);
        if (!(false === strpos($identifier, 'delete')) && !(false === strpos($uri, '/show/'))) {
            $uri = $this->redirectUri;
        }
        if (!(false === strpos($uri, 'jscript'))) {
            $uri = $this->redirectUri; // @codeCoverageIgnore
        }

        return $uri;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function isOpeningBalance(TransactionJournal $journal): bool
    {
        return TransactionType::OPENING_BALANCE === $journal->transactionType->type;
    }


    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectToAccount(TransactionJournal $journal)
    {
        $valid        = [AccountType::DEFAULT, AccountType::ASSET];
        $transactions = $journal->transactions;
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $account = $transaction->account;
            if (\in_array($account->accountType->type, $valid, true)) {
                return redirect(route('accounts.show', [$account->id]));
            }
        }
        // @codeCoverageIgnoreStart
        session()->flash('error', (string)trans('firefly.cannot_redirect_to_account'));

        return redirect(route('index'));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $identifier
     */
    protected function rememberPreviousUri(string $identifier): void
    {
        session()->put($identifier, URL::previous());
    }
}
