<?php
/**
 * EditController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Http\Controllers\Account;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\Request;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
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
     * @param Request                    $request
     * @param Account                    $account
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function edit(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        $what         = config('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle     = (string)trans('firefly.edit_' . $what . '_account', ['name' => $account->name]);
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        $roles        = [];
        foreach (config('firefly.accountRoles') as $role) {
            $roles[$role] = (string)trans('firefly.account_role_' . $role);
        }

        // types of liability:
        $debt           = $this->repository->getAccountTypeByType(AccountType::DEBT);
        $loan           = $this->repository->getAccountTypeByType(AccountType::LOAN);
        $mortgage       = $this->repository->getAccountTypeByType(AccountType::MORTGAGE);
        $liabilityTypes = [
            $debt->id     => (string)trans('firefly.account_type_' . AccountType::DEBT),
            $loan->id     => (string)trans('firefly.account_type_' . AccountType::LOAN),
            $mortgage->id => (string)trans('firefly.account_type_' . AccountType::MORTGAGE),
        ];
        asort($liabilityTypes);

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
        $default              = app('amount')->getDefaultCurrency();
        $currency             = $this->currencyRepos->findNull((int)$repository->getMetaValue($account, 'currency_id'));

        // include this account in net-worth charts?
        $includeNetWorth = $repository->getMetaValue($account, 'include_net_worth');
        $includeNetWorth = null === $includeNetWorth ? true : '1' === $includeNetWorth;

        if (null === $currency) {
            $currency = $default;
        }

        // code to handle active-checkboxes
        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'accountNumber'        => $repository->getMetaValue($account, 'accountNumber'),
            'accountRole'          => $repository->getMetaValue($account, 'accountRole'),
            'ccType'               => $repository->getMetaValue($account, 'ccType'),
            'ccMonthlyPaymentDate' => $repository->getMetaValue($account, 'ccMonthlyPaymentDate'),
            'BIC'                  => $repository->getMetaValue($account, 'BIC'),
            'openingBalanceDate'   => $openingBalanceDate,
            'liability_type_id'    => $account->account_type_id,
            'openingBalance'       => $openingBalanceAmount,
            'virtualBalance'       => $account->virtual_balance,
            'currency_id'          => $currency->id,
            'include_net_worth'    => $includeNetWorth,
            'interest'             => $repository->getMetaValue($account, 'interest'),
            'interest_period'      => $repository->getMetaValue($account, 'interest_period'),
            'notes'                => $this->repository->getNoteText($account),
            'active'               => $hasOldInput ? (bool)$request->old('active') : $account->active,
        ];
        if ('liabilities' === $what) {
            $preFilled['openingBalance'] = bcmul($preFilled['openingBalance'], '-1');
        }

        $request->session()->flash('preFilled', $preFilled);

        return view(
            'accounts.edit', compact('account', 'currency', 'subTitle', 'subTitleIcon', 'what', 'roles', 'preFilled', 'liabilityTypes', 'interestPeriods')
        );
    }


    /**
     * Update the account.
     *
     * @param AccountFormRequest $request
     * @param Account            $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(AccountFormRequest $request, Account $account)
    {
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
