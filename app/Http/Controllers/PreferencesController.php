<?php
/**
 * PreferencesController.php
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

use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Http\Request;
use Preferences;

/**
 * Class PreferencesController.
 */
class PreferencesController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.preferences'));
                app('view')->share('mainTitleIcon', 'fa-gear');

                return $next($request);
            }
        );
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $accounts           = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $viewRangePref      = Preferences::get('viewRange', '1M');
        $viewRange          = $viewRangePref->data;
        $frontPageAccounts  = Preferences::get('frontPageAccounts', []);
        $language           = Preferences::get('language', config('firefly.default_language', 'en_US'))->data;
        $listPageSize       = Preferences::get('listPageSize', 50)->data;
        $customFiscalYear   = Preferences::get('customFiscalYear', 0)->data;
        $fiscalYearStartStr = Preferences::get('fiscalYearStart', '01-01')->data;
        $fiscalYearStart    = date('Y') . '-' . $fiscalYearStartStr;
        $tjOptionalFields   = Preferences::get('transaction_journal_optional_fields', [])->data;

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
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postIndex(Request $request)
    {
        // front page accounts
        $frontPageAccounts = [];
        if (\is_array($request->get('frontPageAccounts'))) {
            foreach ($request->get('frontPageAccounts') as $id) {
                $frontPageAccounts[] = (int)$id;
            }
            Preferences::set('frontPageAccounts', $frontPageAccounts);
        }

        // view range:
        Preferences::set('viewRange', $request->get('viewRange'));
        // forget session values:
        session()->forget('start');
        session()->forget('end');
        session()->forget('range');

        // custom fiscal year
        $customFiscalYear = 1 === (int)$request->get('customFiscalYear');
        $fiscalYearStart  = date('m-d', strtotime((string)$request->get('fiscalYearStart')));
        Preferences::set('customFiscalYear', $customFiscalYear);
        Preferences::set('fiscalYearStart', $fiscalYearStart);

        // save page size:
        Preferences::set('listPageSize', 50);
        $listPageSize = (int)$request->get('listPageSize');
        if ($listPageSize > 0 && $listPageSize < 1337) {
            Preferences::set('listPageSize', $listPageSize);
        }

        // language:
        $lang = $request->get('language');
        if (array_key_exists($lang, config('firefly.languages'))) {
            Preferences::set('language', $lang);
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
        Preferences::set('transaction_journal_optional_fields', $optionalTj);

        session()->flash('success', (string)trans('firefly.saved_preferences'));
        app('preferences')->mark();

        return redirect(route('preferences.index'));
    }
}
