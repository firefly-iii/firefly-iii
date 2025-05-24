<?php

/**
 * NewUserController.php
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

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Requests\NewUserFormRequest;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

/**
 * Class NewUserController.
 */
class NewUserController extends Controller
{
    use CreateStuff;

    private AccountRepositoryInterface $repository;

    /**
     * NewUserController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Form the user gets when he has no data in the system.
     *
     * @return Factory|Redirector|RedirectResponse|View
     */
    public function index()
    {
        app('view')->share('title', (string) trans('firefly.welcome'));
        app('view')->share('mainTitleIcon', 'fa-fire');

        $types     = config('firefly.accountTypesByIdentifier.asset');
        $count     = $this->repository->count($types);

        $languages = [];

        if ($count > 0) {
            return redirect(route('index'));
        }

        return view('new-user.index', compact('languages'));
    }

    /**
     * Store his new settings.
     *
     * @return Redirector|RedirectResponse
     *
     * @throws FireflyException
     */
    public function submit(NewUserFormRequest $request, CurrencyRepositoryInterface $currencyRepository)
    {
        $language      = $request->convertString('language');
        if (!array_key_exists($language, config('firefly.languages'))) {
            $language = 'en_US';
        }

        // set language preference:
        app('preferences')->set('language', $language);
        // Store currency preference from input:
        $currency      = $currencyRepository->find((int) $request->input('amount_currency_id_bank_balance'));

        // if is null, set to EUR:
        if (!$currency instanceof TransactionCurrency) {
            $currency = $currencyRepository->findByCode('EUR');
        }
        $currencyRepository->enable($currency);

        $this->createAssetAccount($request, $currency);              // create normal asset account
        $this->createSavingsAccount($request, $currency, $language); // create savings account
        $this->createCashWalletAccount($currency, $language);        // create cash wallet account

        // store currency preference:
        $currencyRepository->makeDefault($currency);

        // store frontpage preferences:
        $accounts      = $this->repository->getAccountsByType([AccountTypeEnum::ASSET->value])->pluck('id')->toArray();
        app('preferences')->set('frontpageAccounts', $accounts);

        // mark.
        app('preferences')->mark();

        // set default optional fields:
        $visibleFields = [
            'interest_date'      => true,
            'book_date'          => false,
            'process_date'       => false,
            'due_date'           => false,
            'payment_date'       => false,
            'invoice_date'       => false,
            'internal_reference' => false,
            'notes'              => true,
            'attachments'        => true,
        ];
        app('preferences')->set('transaction_journal_optional_fields', $visibleFields);

        session()->flash('success', (string) trans('firefly.stored_new_accounts_new_user'));
        app('preferences')->mark();

        return redirect(route('index'));
    }
}
