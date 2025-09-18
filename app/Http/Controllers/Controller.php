<?php

/**
 * Controller.php
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

use FireflyIII\Events\RequestedSendWebhookMessages;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Facades\Preferences;
use FireflyIII\Support\Facades\Steam;
use FireflyIII\Support\Http\Controllers\RequestInformation;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

use function Safe\realpath;
use function Safe\ini_get;

/**
 * Class Controller.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.NumberOfChildren")
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use RequestInformation;
    use UserNavigation;
    use ValidatesRequests;

    // fails on PHP < 8.4
    public protected(set) string $name;
    protected bool                 $convertToPrimary = false;
    protected string               $dateTimeFormat;
    protected ?TransactionCurrency $primaryCurrency;
    protected string               $monthAndDayFormat;
    protected string               $monthFormat;
    protected string               $redirectUrl      = '/';

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        // is site a demo site?
        $isDemoSiteConfig = app('fireflyconfig')->get('is_demo_site', config('firefly.configuration.is_demo_site', false));
        $isDemoSite       = (bool) $isDemoSiteConfig->data;
        View::share('IS_DEMO_SITE', $isDemoSite);
        View::share('DEMO_USERNAME', config('firefly.demo_username'));
        View::share('DEMO_PASSWORD', config('firefly.demo_password'));
        View::share('FF_VERSION', config('firefly.version'));

        // is webhooks enabled?
        View::share('featuringWebhooks', true === config('firefly.feature_flags.webhooks') && true === config('firefly.allow_webhooks'));

        // share custom auth guard info.
        $authGuard        = config('firefly.authentication_guard');
        $logoutUrl        = config('firefly.custom_logout_url');

        // overrule v2 layout back to v1.
        if ('true' === request()->get('force_default_layout') && 'v2' === config('view.layout')) {
            // config('view.layout','v1');
            Config::set('view.layout', 'v1');
            View::getFinder()->setPaths([realpath(base_path('resources/views'))]); // @phpstan-ignore-line
        }

        View::share('authGuard', $authGuard);
        View::share('logoutUrl', $logoutUrl);

        // upload size
        $maxFileSize      = Steam::phpBytes(ini_get('upload_max_filesize'));
        $maxPostSize      = Steam::phpBytes(ini_get('post_max_size'));
        $uploadSize       = min($maxFileSize, $maxPostSize);
        View::share('uploadSize', $uploadSize);

        // share is alpha, is beta
        $isAlpha          = false;
        $isBeta           = false;
        $isDevelop        = false;
        if (str_contains((string) config('firefly.version'), 'alpha')) {
            $isAlpha = true;
        }
        if (str_contains((string) config('firefly.version'), 'develop') || str_contains((string) config('firefly.version'), 'branch')) {
            $isDevelop = true;
        }

        if (str_contains((string) config('firefly.version'), 'beta')) {
            $isBeta = true;
        }

        View::share('FF_IS_ALPHA', $isAlpha);
        View::share('FF_IS_BETA', $isBeta);
        View::share('FF_IS_DEVELOP', $isDevelop);

        $this->middleware(
            function ($request, $next): mixed {
                $locale                  = Steam::getLocale();
                // translations for specific strings:
                $this->monthFormat       = (string) trans('config.month_js', [], $locale);
                $this->monthAndDayFormat = (string) trans('config.month_and_day_js', [], $locale);
                $this->dateTimeFormat    = (string) trans('config.date_time_js', [], $locale);
                $darkMode                = 'browser';
                $this->primaryCurrency   = null;
                // get shown-intro-preference:
                if (auth()->check()) {
                    $this->primaryCurrency  = Amount::getPrimaryCurrency();
                    $language               = Steam::getLanguage();
                    $locale                 = Steam::getLocale();
                    $darkMode               = Preferences::get('darkMode', 'browser')->data;
                    $this->convertToPrimary = Amount::convertToPrimary();
                    $page                   = $this->getPageName();
                    $shownDemo              = $this->hasSeenDemo();
                    View::share('language', $language);
                    View::share('locale', $locale);
                    View::share('convertToPrimary', $this->convertToPrimary);
                    View::share('primaryCurrency', $this->primaryCurrency);
                    View::share('shownDemo', $shownDemo);
                    View::share('current_route_name', $page);
                    View::share('original_route_name', Route::currentRouteName());

                    // lottery to send any remaining webhooks:
                    if (7 === random_int(1, 10)) {
                        // trigger event to send them:
                        Log::debug('send event RequestedSendWebhookMessages through lottery');
                        event(new RequestedSendWebhookMessages());
                    }

                }
                View::share('darkMode', $darkMode);

                return $next($request);
            }
        );
    }
}
