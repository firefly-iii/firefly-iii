<?php
/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Account;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use FireflyIII\Support\Http\Controllers\UserNavigation;
use Illuminate\Http\Request;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    use ModelInformation, UserNavigation;
    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepos;
    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * EditController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));

                $this->repository    = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit account overview.
     *
     * @param Request $request
     * @param Account $account
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     */
    public function edit(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        $objectType     = config('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle       = (string)trans(sprintf('firefly.edit_%s_account', $objectType), ['name' => $account->name]);
        $subTitleIcon   = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $roles          = $this->getRoles();
        $liabilityTypes = $this->getLiabilityTypes();
        $location       = $repository->getLocation($account);
        $latitude       = $location ? $location->latitude : config('firefly.default_location.latitude');
        $longitude      = $location ? $location->longitude : config('firefly.default_location.longitude');
        $zoomLevel      = $location ? $location->zoom_level : config('firefly.default_location.zoom_level');
        $hasLocation    = null !== $location;
        $locations      = [
            'location' => [
                'latitude'     => old('location_latitude') ?? $latitude,
                'longitude'    => old('location_longitude') ?? $longitude,
                'zoom_level'   => old('location_zoom_level') ?? $zoomLevel,
                'has_location' => $hasLocation || 'true' === old('location_has_location'),
            ],
        ];

        // interest calculation periods:
        $interestPeriods = [
            'daily'   => (string)trans('firefly.interest_calc_daily'),
            'monthly' => (string)trans('firefly.interest_calc_monthly'),
            'yearly'  => (string)trans('firefly.interest_calc_yearly'),
        ];

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('accounts.edit.fromUpdate')) {
            $this->rememberPreviousUri('accounts.edit.uri');
        }
        $request->session()->forget('accounts.edit.fromUpdate');

        $openingBalanceAmount = (string)$repository->getOpeningBalanceAmount($account);
        $openingBalanceDate   = $repository->getOpeningBalanceDate($account);
        $currency             = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();

        // include this account in net-worth charts?
        $includeNetWorth = $repository->getMetaValue($account, 'include_net_worth');
        $includeNetWorth = null === $includeNetWorth ? true : '1' === $includeNetWorth;

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'account_number'          => $repository->getMetaValue($account, 'account_number'),
            'account_role'            => $repository->getMetaValue($account, 'account_role'),
            'cc_type'                 => $repository->getMetaValue($account, 'cc_type'),
            'cc_monthly_payment_date' => $repository->getMetaValue($account, 'cc_monthly_payment_date'),
            'BIC'                     => $repository->getMetaValue($account, 'BIC'),
            'opening_balance_date'    => $openingBalanceDate,
            'liability_type_id'       => $account->account_type_id,
            'opening_balance'         => $openingBalanceAmount,
            'virtual_balance'         => $account->virtual_balance,
            'currency_id'             => $currency->id,
            'include_net_worth'       => $includeNetWorth,
            'interest'                => $repository->getMetaValue($account, 'interest'),
            'interest_period'         => $repository->getMetaValue($account, 'interest_period'),
            'notes'                   => $this->repository->getNoteText($account),
            'active'                  => $hasOldInput ? (bool)$request->old('active') : $account->active,
        ];

        $request->session()->flash('preFilled', $preFilled);

        return view(
            'accounts.edit', compact(
            'account', 'currency', 'subTitle', 'subTitleIcon', 'locations', 'objectType', 'roles', 'preFilled', 'liabilityTypes', 'interestPeriods'
        )
        );
    }


    /**
     * Update the account.
     *
     * @param AccountFormRequest $request
     * @param Account $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(AccountFormRequest $request, Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account); // @codeCoverageIgnore
        }

        $data = $request->getAccountData();
        $this->repository->update($account, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_account', ['name' => $account->name]));
        app('preferences')->mark();

        $redirect = redirect($this->getPreviousUri('accounts.edit.uri'));
        if (1 === (int)$request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('accounts.edit.fromUpdate', true);

            $redirect = redirect(route('accounts.edit', [$account->id]))->withInput(['return_to_edit' => 1]);
        }

        return $redirect;
    }

}
