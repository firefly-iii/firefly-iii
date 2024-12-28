<?php

/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Account;

use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\ModelInformation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Class EditController
 */
class EditController extends Controller
{
    use ModelInformation;

    private AttachmentHelperInterface  $attachments;
    private AccountRepositoryInterface $repository;

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
                app('view')->share('title', (string) trans('firefly.accounts'));

                $this->repository  = app(AccountRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit account overview. It's complex, but it just has a lot of if/then/else.
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function edit(Request $request, Account $account, AccountRepositoryInterface $repository)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        $objectType           = config('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle             = (string) trans(sprintf('firefly.edit_%s_account', $objectType), ['name' => $account->name]);
        $subTitleIcon         = config(sprintf('firefly.subIconsByIdentifier.%s', $objectType));
        $roles                = $this->getRoles();
        $liabilityTypes       = $this->getLiabilityTypes();
        $location             = $repository->getLocation($account);
        $latitude             = null !== $location ? $location->latitude : config('firefly.default_location.latitude');
        $longitude            = null !== $location ? $location->longitude : config('firefly.default_location.longitude');
        $zoomLevel            = null !== $location ? $location->zoom_level : config('firefly.default_location.zoom_level');
        $canEditCurrency = $account->piggyBanks()->count() === 0;
        $hasLocation          = null !== $location;
        $locations            = [
            'location' => [
                'latitude'     => null !== old('location_latitude') ? old('location_latitude') : $latitude,
                'longitude'    => null !== old('location_longitude') ? old('location_longitude') : $longitude,
                'zoom_level'   => null !== old('location_zoom_level') ? old('location_zoom_level') : $zoomLevel,
                'has_location' => $hasLocation || 'true' === old('location_has_location'),
            ],
        ];

        $liabilityDirections  = [
            'debit'  => trans('firefly.liability_direction_debit'),
            'credit' => trans('firefly.liability_direction_credit'),
        ];

        // interest calculation periods:
        $interestPeriods      = [
            'daily'   => (string) trans('firefly.interest_calc_daily'),
            'monthly' => (string) trans('firefly.interest_calc_monthly'),
            'yearly'  => (string) trans('firefly.interest_calc_yearly'),
        ];

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('accounts.edit.fromUpdate')) {
            $this->rememberPreviousUrl('accounts.edit.url');
        }
        $request->session()->forget('accounts.edit.fromUpdate');

        $openingBalanceAmount = (string) $repository->getOpeningBalanceAmount($account);
        if ('0' === $openingBalanceAmount) {
            $openingBalanceAmount = '';
        }
        $openingBalanceDate   = $repository->getOpeningBalanceDate($account);
        $currency             = $this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency();

        // include this account in net-worth charts?
        $includeNetWorth      = $repository->getMetaValue($account, 'include_net_worth');
        $includeNetWorth      = null === $includeNetWorth ? true : '1' === $includeNetWorth;

        // issue #8321
        $showNetWorth         = true;
        if ('liabilities' !== $objectType && 'asset' !== $objectType) {
            $showNetWorth = false;
        }

        // code to handle active-checkboxes
        $hasOldInput          = null !== $request->old('_token');
        $virtualBalance       = null === $account->virtual_balance ? '0' : $account->virtual_balance;
        $preFilled            = [
            'account_number'          => $repository->getMetaValue($account, 'account_number'),
            'account_role'            => $repository->getMetaValue($account, 'account_role'),
            'cc_type'                 => $repository->getMetaValue($account, 'cc_type'),
            'cc_monthly_payment_date' => $repository->getMetaValue($account, 'cc_monthly_payment_date'),
            'BIC'                     => $repository->getMetaValue($account, 'BIC'),
            'opening_balance_date'    => substr((string) $openingBalanceDate, 0, 10),
            'liability_type_id'       => $account->account_type_id,
            'opening_balance'         => app('steam')->bcround($openingBalanceAmount, $currency->decimal_places),
            'liability_direction'     => $this->repository->getMetaValue($account, 'liability_direction'),
            'virtual_balance'         => app('steam')->bcround($virtualBalance, $currency->decimal_places),
            'currency_id'             => $currency->id,
            'include_net_worth'       => $hasOldInput ? (bool) $request->old('include_net_worth') : $includeNetWorth,
            'interest'                => $repository->getMetaValue($account, 'interest'),
            'interest_period'         => $repository->getMetaValue($account, 'interest_period'),
            'notes'                   => $this->repository->getNoteText($account),
            'active'                  => $hasOldInput ? (bool) $request->old('active') : $account->active,
        ];
        if ('' === $openingBalanceAmount) {
            $preFilled['opening_balance'] = '';
        }

        $request->session()->flash('preFilled', $preFilled);

        return view('accounts.edit', compact('account', 'currency','canEditCurrency', 'showNetWorth', 'subTitle', 'subTitleIcon', 'locations', 'liabilityDirections', 'objectType', 'roles', 'preFilled', 'liabilityTypes', 'interestPeriods'));
    }

    /**
     * Update the account.
     *
     * @return $this|Redirector|RedirectResponse
     */
    public function update(AccountFormRequest $request, Account $account)
    {
        if (!$this->isEditableAccount($account)) {
            return $this->redirectAccountToAccount($account);
        }

        $data     = $request->getAccountData();
        $this->repository->update($account, $data);
        Log::channel('audit')->info(sprintf('Updated account #%d.', $account->id), $data);
        $request->session()->flash('success', (string) trans('firefly.updated_account', ['name' => $account->name]));

        // store new attachment(s):
        /** @var null|array $files */
        $files    = $request->hasFile('attachments') ? $request->file('attachments') : null;
        if (null !== $files && !auth()->user()->hasRole('demo')) {
            $this->attachments->saveAttachmentsForModel($account, $files);
        }
        if (null !== $files && auth()->user()->hasRole('demo')) {
            Log::channel('audit')->warning(sprintf('The demo user is trying to upload attachments in %s.', __METHOD__));
            session()->flash('info', (string) trans('firefly.no_att_demo_user'));
        }

        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            $request->session()->flash('info', $this->attachments->getMessages()->get('attachments'));
        }

        // redirect
        $redirect = redirect($this->getPreviousUrl('accounts.edit.url'));
        if (1 === (int) $request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('accounts.edit.fromUpdate', true);

            $redirect = redirect(route('accounts.edit', [$account->id]))->withInput(['return_to_edit' => 1]);
        }
        app('preferences')->mark();

        return $redirect;
    }
}
