<?php
/**
 * NewUserController.php
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

use Carbon\Carbon;
use FireflyIII\Http\Requests\NewUserFormRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Preferences;
use Session;
use View;

/**
 * Class NewUserController.
 */
class NewUserController extends Controller
{
    /**
     * NewUserController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                return $next($request);
            }
        );
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        View::share('title', trans('firefly.welcome'));
        View::share('mainTitleIcon', 'fa-fire');

        $types = config('firefly.accountTypesByIdentifier.asset');
        $count = $repository->count($types);

        if ($count > 0) {
            return redirect(route('index'));
        }

        return view('new-user.index');
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submit(NewUserFormRequest $request, AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository)
    {
        // create normal asset account:
        $this->createAssetAccount($request, $repository);

        // create savings account
        $this->createSavingsAccount($request, $repository);

        // also store currency preference from input:
        $currency = $currencyRepository->find(intval($request->input('amount_currency_id_bank_balance')));

        if (null !== $currency->id) {
            // store currency preference:
            Preferences::set('currencyPreference', $currency->code);
            Preferences::mark();
        }

        Session::flash('success', strval(trans('firefly.stored_new_accounts_new_user')));
        Preferences::mark();

        return redirect(route('index'));
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return bool
     */
    private function createAssetAccount(NewUserFormRequest $request, AccountRepositoryInterface $repository): bool
    {
        $assetAccount = [
            'name'               => $request->get('bank_name'),
            'iban'               => null,
            'accountType'        => 'asset',
            'virtualBalance'     => 0,
            'active'             => true,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => round($request->input('bank_balance'), 12),
            'openingBalanceDate' => new Carbon,
            'currency_id'        => intval($request->input('amount_currency_id_bank_balance')),
        ];

        $repository->store($assetAccount);

        return true;
    }

    /**
     * @param NewUserFormRequest         $request
     * @param AccountRepositoryInterface $repository
     *
     * @return bool
     */
    private function createSavingsAccount(NewUserFormRequest $request, AccountRepositoryInterface $repository): bool
    {
        $savingsAccount = [
            'name'               => $request->get('bank_name') . ' savings account',
            'iban'               => null,
            'accountType'        => 'asset',
            'virtualBalance'     => 0,
            'active'             => true,
            'accountRole'        => 'savingAsset',
            'openingBalance'     => round($request->input('savings_balance'), 12),
            'openingBalanceDate' => new Carbon,
            'currency_id'        => intval($request->input('amount_currency_id_bank_balance')),
        ];
        $repository->store($savingsAccount);

        return true;
    }
}
