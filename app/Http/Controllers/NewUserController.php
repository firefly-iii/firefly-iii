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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Http\Requests\NewUserFormRequest;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use View;

/**
 * Class NewUserController.
 */
class NewUserController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $repository;

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
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function index()
    {
        app('view')->share('title', trans('firefly.welcome'));
        app('view')->share('mainTitleIcon', 'fa-fire');

        $types = config('firefly.accountTypesByIdentifier.asset');
        $count = $this->repository->count($types);

        $languages = [];

        if ($count > 0) {
            return redirect(route('index'));
        }

        return view('new-user.index', compact('languages'));
    }

    /**
     * @param NewUserFormRequest          $request
     * @param CurrencyRepositoryInterface $currencyRepository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submit(NewUserFormRequest $request, CurrencyRepositoryInterface $currencyRepository)
    {
        $language = $request->string('language');
        if (!array_key_exists($language, config('firefly.languages'))) {
            $language = 'en_US';

        }

        // set language preference:
        app('preferences')->set('language', $language);
        // Store currency preference from input:
        $currency = $currencyRepository->findNull((int)$request->input('amount_currency_id_bank_balance'));

        // if is null, set to EUR:
        if (null === $currency) {
            $currency = $currencyRepository->findByCodeNull('EUR');
        }

        // create normal asset account:
        $this->createAssetAccount($request, $currency);

        // create savings account
        $this->createSavingsAccount($request, $currency, $language);

        // create cash wallet account
        $this->createCashWalletAccount($currency, $language);

        // store currency preference:
        app('preferences')->set('currencyPreference', $currency->code);
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

        session()->flash('success', (string)trans('firefly.stored_new_accounts_new_user'));
        app('preferences')->mark();

        return redirect(route('index'));
    }

    /**
     * @param NewUserFormRequest  $request
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    private function createAssetAccount(NewUserFormRequest $request, TransactionCurrency $currency): bool
    {
        $assetAccount = [
            'name'               => $request->get('bank_name'),
            'iban'               => null,
            'accountType'        => 'asset',
            'virtualBalance'     => 0,
            'account_type_id'    => null,
            'active'             => true,
            'accountRole'        => 'defaultAsset',
            'openingBalance'     => $request->input('bank_balance'),
            'openingBalanceDate' => new Carbon,
            'currency_id'        => $currency->id,
        ];

        $this->repository->store($assetAccount);

        return true;
    }

    /**
     * @param TransactionCurrency $currency
     * @param string              $language
     *
     * @return bool
     */
    private function createCashWalletAccount(TransactionCurrency $currency, string $language): bool
    {
        $assetAccount = [
            'name'               => (string)trans('firefly.cash_wallet', [], $language),
            'iban'               => null,
            'accountType'        => 'asset',
            'virtualBalance'     => 0,
            'account_type_id'    => null,
            'active'             => true,
            'accountRole'        => 'cashWalletAsset',
            'openingBalance'     => null,
            'openingBalanceDate' => null,
            'currency_id'        => $currency->id,
        ];

        $this->repository->store($assetAccount);

        return true;
    }

    /**
     * @param NewUserFormRequest  $request
     * @param TransactionCurrency $currency
     * @param string              $language
     *
     * @return bool
     */
    private function createSavingsAccount(NewUserFormRequest $request, TransactionCurrency $currency, string $language): bool
    {
        $savingsAccount = [
            'name'               => (string)trans('firefly.new_savings_account', ['bank_name' => $request->get('bank_name')], $language),
            'iban'               => null,
            'accountType'        => 'asset',
            'account_type_id'    => null,
            'virtualBalance'     => 0,
            'active'             => true,
            'accountRole'        => 'savingAsset',
            'openingBalance'     => $request->input('savings_balance'),
            'openingBalanceDate' => new Carbon,
            'currency_id'        => $currency->id,
        ];
        $this->repository->store($savingsAccount);

        return true;
    }
}
