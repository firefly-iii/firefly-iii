<?php

/**
 * PreferencesController.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Notifications\UrlValidator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class PreferencesController.
 */
class PreferencesController extends Controller
{
    /**
     * PreferencesController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            static function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.preferences'));
                app('view')->share('mainTitleIcon', 'fa-gear');

                return $next($request);
            }
        );
    }

    /**
     * Show overview of preferences.
     *
     * @return Factory|View
     *
     * @throws FireflyException
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $accounts              = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE]);
        $isDocker              = env('IS_DOCKER', false);
        $groupedAccounts       = [];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $type                                                                       = $account->accountType->type;
            $role                                                                       = sprintf('opt_group_%s', $repository->getMetaValue($account, 'account_role'));

            if (in_array($type, [AccountType::MORTGAGE, AccountType::DEBT, AccountType::LOAN], true)) {
                $role = sprintf('opt_group_l_%s', $type);
            }

            if ('opt_group_' === $role) {
                $role = 'opt_group_defaultAsset';
            }
            $groupedAccounts[(string)trans(sprintf('firefly.%s', $role))][$account->id] = $account->name;
        }
        ksort($groupedAccounts);

        /** @var array<int, int> $accountIds */
        $accountIds            = $accounts->pluck('id')->toArray();
        $viewRange             = app('navigation')->getViewRange(false);
        $frontpageAccountsPref = app('preferences')->get('frontpageAccounts', $accountIds);
        $frontpageAccounts     = $frontpageAccountsPref->data;
        if (!is_array($frontpageAccounts)) {
            $frontpageAccounts = $accountIds;
        }
        $language              = app('steam')->getLanguage();
        $languages             = config('firefly.languages');
        $locale                = app('preferences')->get('locale', config('firefly.default_locale', 'equal'))->data;
        $listPageSize          = app('preferences')->get('listPageSize', 50)->data;
        $darkMode              = app('preferences')->get('darkMode', 'browser')->data;
        $slackUrl              = app('preferences')->get('slack_webhook_url', '')->data;
        $customFiscalYear      = app('preferences')->get('customFiscalYear', 0)->data;
        $fiscalYearStartStr    = app('preferences')->get('fiscalYearStart', '01-01')->data;
        if (is_array($fiscalYearStartStr)) {
            $fiscalYearStartStr = '01-01';
        }
        $fiscalYearStart       = sprintf('%s-%s', date('Y'), (string)$fiscalYearStartStr);
        $tjOptionalFields      = app('preferences')->get('transaction_journal_optional_fields', [])->data;
        $availableDarkModes    = config('firefly.available_dark_modes');

        // notification preferences (single value for each):
        $notifications         = [];
        die('fix the reference to the available notifications.');
        foreach (config('firefly.available_notifications') as $notification) {
            $notifications[$notification] = app('preferences')->get(sprintf('notification_%s', $notification), true)->data;
        }

        ksort($languages);

        // list of locales also has "equal" which makes it equal to whatever the language is.

        try {
            $locales = json_decode((string)file_get_contents(resource_path(sprintf('locales/%s/locales.json', $language))), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            app('log')->error($e->getMessage());
            $locales = [];
        }
        $locales               = ['equal' => (string)trans('firefly.equal_to_language')] + $locales;
        // an important fallback is that the frontPageAccount array gets refilled automatically
        // when it turns up empty.
        if (0 === count($frontpageAccounts)) {
            $frontpageAccounts = $accountIds;
        }

        // for the demo user, the slackUrl is automatically emptied.
        // this isn't really secure, but it means that the demo site has a semi-secret
        // slackUrl.
        if (auth()->user()->hasRole('demo')) {
            $slackUrl = '';
        }

        return view('preferences.index', compact('language', 'groupedAccounts', 'isDocker', 'frontpageAccounts', 'languages', 'darkMode', 'availableDarkModes', 'notifications', 'slackUrl', 'locales', 'locale', 'tjOptionalFields', 'viewRange', 'customFiscalYear', 'listPageSize', 'fiscalYearStart'));
    }

    /**
     * Store new preferences.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function postIndex(Request $request)
    {
        // front page accounts
        $frontpageAccounts = [];
        if (is_array($request->get('frontpageAccounts')) && count($request->get('frontpageAccounts')) > 0) {
            foreach ($request->get('frontpageAccounts') as $id) {
                $frontpageAccounts[] = (int)$id;
            }
            app('preferences')->set('frontpageAccounts', $frontpageAccounts);
        }

        // extract notifications:
        $all               = $request->all();
        die('fix the reference to the available notifications.');
        foreach (config('firefly.available_notifications') as $option) {
            $key = sprintf('notification_%s', $option);
            if (array_key_exists($key, $all)) {
                app('preferences')->set($key, true);
            }
            if (!array_key_exists($key, $all)) {
                app('preferences')->set($key, false);
            }
        }

        // view range:
        app('preferences')->set('viewRange', $request->get('viewRange'));
        // forget session values:
        session()->forget('start');
        session()->forget('end');
        session()->forget('range');

        // slack URL:
        if (!auth()->user()->hasRole('demo')) {
            $url = (string)$request->get('slackUrl');
            if (UrlValidator::isValidWebhookURL($url)) {
                app('preferences')->set('slack_webhook_url', $url);
            }
            if ('' === $url) {
                app('preferences')->delete('slack_webhook_url');
            }
        }

        // custom fiscal year
        $customFiscalYear  = 1 === (int)$request->get('customFiscalYear');
        $string            = strtotime((string)$request->get('fiscalYearStart'));
        if (false !== $string) {
            $fiscalYearStart = date('m-d', $string);
            app('preferences')->set('customFiscalYear', $customFiscalYear);
            app('preferences')->set('fiscalYearStart', $fiscalYearStart);
        }

        // save page size:
        app('preferences')->set('listPageSize', 50);
        $listPageSize      = (int)$request->get('listPageSize');
        if ($listPageSize > 0 && $listPageSize < 1337) {
            app('preferences')->set('listPageSize', $listPageSize);
        }

        // language:
        /** @var Preference $currentLang */
        $currentLang       = app('preferences')->get('language', 'en_US');
        $lang              = $request->get('language');
        if (array_key_exists($lang, config('firefly.languages'))) {
            app('preferences')->set('language', $lang);
        }
        if ($currentLang->data !== $lang) {
            // this string is untranslated on purpose.
            session()->flash('info', 'All translations are supplied by volunteers. There might be errors and mistakes. I appreciate your feedback.');
        }

        // same for locale:
        if (!auth()->user()->hasRole('demo')) {
            $locale = (string) $request->get('locale');
            $locale = '' === $locale ? null : $locale;
            app('preferences')->set('locale', $locale);
        }

        // optional fields for transactions:
        $setOptions        = $request->get('tj') ?? [];
        $optionalTj        = [
            'interest_date'      => array_key_exists('interest_date', $setOptions),
            'book_date'          => array_key_exists('book_date', $setOptions),
            'process_date'       => array_key_exists('process_date', $setOptions),
            'due_date'           => array_key_exists('due_date', $setOptions),
            'payment_date'       => array_key_exists('payment_date', $setOptions),
            'invoice_date'       => array_key_exists('invoice_date', $setOptions),
            'internal_reference' => array_key_exists('internal_reference', $setOptions),
            'notes'              => array_key_exists('notes', $setOptions),
            'attachments'        => array_key_exists('attachments', $setOptions),
            'external_url'       => array_key_exists('external_url', $setOptions),
            'location'           => array_key_exists('location', $setOptions),
            'links'              => array_key_exists('links', $setOptions),
        ];
        app('preferences')->set('transaction_journal_optional_fields', $optionalTj);

        // dark mode
        $darkMode          = $request->get('darkMode') ?? 'browser';
        if (in_array($darkMode, config('firefly.available_dark_modes'), true)) {
            app('preferences')->set('darkMode', $darkMode);
        }

        session()->flash('success', (string)trans('firefly.saved_preferences'));
        app('preferences')->mark();

        return redirect(route('preferences.index'));
    }
}
