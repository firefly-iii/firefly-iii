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

use FireflyIII\Support\Http\Controllers\RequestInformation;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Route;

/**
 * Class Controller.
 *
 *
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, UserNavigation, RequestInformation;

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
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        // for transaction lists:
        app('view')->share('hideBudgets', false,); // Firefly III will break here if you don't have PHP 7.3up
        app('view')->share('hideCategories', false,);
        app('view')->share('hideBills', false,);
        app('view')->share('hideTags', false,);

        // is site a demo site?
        $isDemoSite = app('fireflyconfig')->get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        app('view')->share('IS_DEMO_SITE', $isDemoSite);
        app('view')->share('DEMO_USERNAME', config('firefly.demo_username'));
        app('view')->share('DEMO_PASSWORD', config('firefly.demo_password'));
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

}
