<?php
/**
 * Controller.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyConfig;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\Facades\Preferences;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Log;
use Route;
use Session;
use URL;
use View;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** @var  string */
    protected $dateTimeFormat;
    /** @var string */
    protected $monthAndDayFormat;
    /** @var string */
    protected $monthFormat;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        // for transaction lists:
        View::share('hideBudgets', false);
        View::share('hideCategories', false);
        View::share('hideBills', false);
        View::share('hideTags', false);

        // is site a demo site?
        $isDemoSite = FireflyConfig::get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        View::share('IS_DEMO_SITE', $isDemoSite);
        View::share('DEMO_USERNAME', env('DEMO_USERNAME', ''));
        View::share('DEMO_PASSWORD', env('DEMO_PASSWORD', ''));


        $this->middleware(
            function ($request, $next) {
                // translations for specific strings:
                $this->monthFormat       = (string)trans('config.month');
                $this->monthAndDayFormat = (string)trans('config.month_and_day');
                $this->dateTimeFormat    = (string)trans('config.date_time');

                // get shown-intro-preference:
                if (auth()->check()) {
                    // some routes have a "what" parameter, which indicates a special page:
                    $specificPage = is_null(Route::current()->parameter('what')) ? '' : '_' . Route::current()->parameter('what');
                    $page         = str_replace('.', '_', Route::currentRouteName());

                    // indicator if user has seen the help for this page ( + special page):
                    $key = 'shown_demo_' . $page . $specificPage;
                    // is there an intro for this route?
                    $intro        = config('intro.' . $page);
                    $specialIntro = config('intro.' . $page . $specificPage);
                    $shownDemo    = true;

                    // either must be array and either must be > 0
                    if ((is_array($intro) || is_array($specialIntro)) && (count($intro) > 0 || count($specialIntro) > 0)) {
                        $shownDemo = Preferences::get($key, false)->data;
                        Log::debug(sprintf('Check if user has already seen intro with key "%s". Result is %d', $key, $shownDemo));
                    }

                    View::share('shownDemo', $shownDemo);
                    View::share('current_route_name', $page);
                    View::share('original_route_name', Route::currentRouteName());
                }

                return $next($request);
            }
        );

    }

    /**
     * Functionality:
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
        $uri = strval(session($identifier));
        if (!(strpos($identifier, 'delete') === false) && !(strpos($uri, '/show/') === false)) {
            $uri = route('index');
        }
        if (!(strpos($uri, 'javascript') === false)) {
            $uri = route('index');
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
        return $journal->transactionTypeStr() === TransactionType::OPENING_BALANCE;
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
            if (in_array($account->accountType->type, $valid)) {
                return redirect(route('accounts.show', [$account->id]));
            }

        }
        // @codeCoverageIgnoreStart
        Session::flash('error', strval(trans('firefly.cannot_redirect_to_account')));

        return redirect(route('index'));
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $identifier
     */
    protected function rememberPreviousUri(string $identifier)
    {
        Session::put($identifier, URL::previous());
    }

}
