<?php
/**
 * PreferencesController.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Class PreferencesController.
 */
class PreferencesController extends Controller
{
    /**
     * PreferencesController constructor.
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.preferences'));
                app('view')->share('mainTitleIcon', 'fa-gear');

                return $next($request);
            }
        );
    }

    /**
     * Show overview of preferences.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $accounts      = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $accountIds    = $accounts->pluck('id')->toArray();
        $viewRangePref = app('preferences')->get('viewRange', '1M');
        /** @noinspection NullPointerExceptionInspection */
        $viewRange          = $viewRangePref->data;
        $frontPageAccounts  = app('preferences')->get('frontPageAccounts', $accountIds);
        $language           = app('preferences')->get('language', config('firefly.default_language', 'en_US'))->data;
        $listPageSize       = app('preferences')->get('listPageSize', 50)->data;
        $customFiscalYear   = app('preferences')->get('customFiscalYear', 0)->data;
        $fiscalYearStartStr = app('preferences')->get('fiscalYearStart', '01-01')->data;
        $fiscalYearStart    = date('Y') . '-' . $fiscalYearStartStr;
        $tjOptionalFields   = app('preferences')->get('transaction_journal_optional_fields', [])->data;

        // an important fallback is that the frontPageAccount array gets refilled automatically
        // when it turns up empty.
        if (0 === count($frontPageAccounts->data)) {
            $frontPageAccounts = $accountIds;
        }

        return view(
            'preferences.index',
            compact(
                'language',
                'accounts',
                'frontPageAccounts',
                'tjOptionalFields',
                'viewRange',
                'customFiscalYear',
                'listPageSize',
                'fiscalYearStart'
            )
        );
    }

    /**
     * Store new preferences.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     */
    public function postIndex(Request $request)
    {
        // front page accounts
        $frontPageAccounts = [];
        if (is_array($request->get('frontPageAccounts')) && count($request->get('frontPageAccounts')) > 0) {
            foreach ($request->get('frontPageAccounts') as $id) {
                $frontPageAccounts[] = (int)$id;
            }
            app('preferences')->set('frontPageAccounts', $frontPageAccounts);
        }

        // view range:
        app('preferences')->set('viewRange', $request->get('viewRange'));
        // forget session values:
        session()->forget('start');
        session()->forget('end');
        session()->forget('range');

        // custom fiscal year
        $customFiscalYear = 1 === (int)$request->get('customFiscalYear');
        $fiscalYearStart  = date('m-d', strtotime((string)$request->get('fiscalYearStart')));
        app('preferences')->set('customFiscalYear', $customFiscalYear);
        app('preferences')->set('fiscalYearStart', $fiscalYearStart);

        // save page size:
        app('preferences')->set('listPageSize', 50);
        $listPageSize = (int)$request->get('listPageSize');
        if ($listPageSize > 0 && $listPageSize < 1337) {
            app('preferences')->set('listPageSize', $listPageSize);
        }

        // language:
        /** @var Preference $currentLang */
        $currentLang = app('preferences')->get('language', 'en_US');
        $lang = $request->get('language');
        if (array_key_exists($lang, config('firefly.languages'))) {
            app('preferences')->set('language', $lang);
        }
        if ($currentLang->data !== $lang) {
            session()->flash('info', 'All translations are supplied by volunteers. There might be errors and mistakes. I appreciate your feedback.');
        }

        // optional fields for transactions:
        $setOptions = $request->get('tj');
        $optionalTj = [
            'interest_date'      => isset($setOptions['interest_date']),
            'book_date'          => isset($setOptions['book_date']),
            'process_date'       => isset($setOptions['process_date']),
            'due_date'           => isset($setOptions['due_date']),
            'payment_date'       => isset($setOptions['payment_date']),
            'invoice_date'       => isset($setOptions['invoice_date']),
            'internal_reference' => isset($setOptions['internal_reference']),
            'notes'              => isset($setOptions['notes']),
            'attachments'        => isset($setOptions['attachments']),
        ];
        app('preferences')->set('transaction_journal_optional_fields', $optionalTj);

        session()->flash('success', (string)trans('firefly.saved_preferences'));
        app('preferences')->mark();

        return redirect(route('preferences.index'));
    }
}
