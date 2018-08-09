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
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Log;
use Route;
use URL;

/**
 * Class Controller.
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, UserNavigation;

    /** @var string Format for date and time. */
    protected $dateTimeFormat;
    /** @var string Format for "23 Feb, 2016". */
    protected $monthAndDayFormat;
    /** @var string Format for "March 2018" */
    protected $monthFormat;
    /** @var string Redirect user */
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
                    $language  = $this->getLanguage();
                    $page      = $this->getPageName();
                    $shownDemo = $this->hasSeenDemo();
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
     * Is transaction opening balance?
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function isOpeningBalance(TransactionJournal $journal): bool // get object info / validate input
    {
        return TransactionType::OPENING_BALANCE === $journal->transactionType->type;
    }




    /**
     * Get user's language.
     *
     * @return string
     */
    protected function getLanguage(): string // get preference
    {
        /** @var string $language */
        $language = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;

        return $language;
    }

    /**
     * @return string
     */
    protected function getPageName(): string // get request info
    {
        return str_replace('.', '_', Route::currentRouteName());
    }

    /**
     * Get the specific name of a page for intro.
     *
     * @return string
     */
    protected function getSpecificPageName(): string // get request info
    {
        return null === Route::current()->parameter('what') ? '' : '_' . Route::current()->parameter('what');
    }

    /**
     * Returns if user has seen demo.
     *
     * @return bool
     */
    protected function hasSeenDemo(): bool // get request info + get preference
    {
        $page         = $this->getPageName();
        $specificPage = $this->getSpecificPageName();

        // indicator if user has seen the help for this page ( + special page):
        $key = 'shown_demo_' . $page . $specificPage;
        // is there an intro for this route?
        $intro        = config('intro.' . $page) ?? [];
        $specialIntro = config('intro.' . $page . $specificPage) ?? [];
        // some routes have a "what" parameter, which indicates a special page:

        $shownDemo = true;
        // both must be array and either must be > 0
        if (\count($intro) > 0 || \count($specialIntro) > 0) {
            $shownDemo = app('preferences')->get($key, false)->data;
            Log::debug(sprintf('Check if user has already seen intro with key "%s". Result is %d', $key, $shownDemo));
        }

        return $shownDemo;
    }
}
