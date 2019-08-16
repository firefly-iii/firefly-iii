<?php
/**
 * JavascriptController.php
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

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Http\Controllers\GetConfigurationData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class JavascriptController.
 */
class JavascriptController extends Controller
{
    use GetConfigurationData;

    /**
     * Show info about accounts.
     *
     * @param AccountRepositoryInterface  $repository
     * @param CurrencyRepositoryInterface $currencyRepository
     *
     * @return \Illuminate\Http\Response
     */
    public function accounts(AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository): Response
    {
        $accounts   = $repository->getAccountsByType(
            [AccountType::DEFAULT, AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD]
        );
        $preference = app('preferences')->get('currencyPreference', config('firefly.default_currency', 'EUR'));
        /** @noinspection NullPointerExceptionInspection */
        $default = $currencyRepository->findByCodeNull($preference->data);

        $data = ['accounts' => []];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId = $account->id;
            $currency  = (int)$repository->getMetaValue($account, 'currency_id');
            /** @noinspection NullPointerExceptionInspection */
            $currency                     = 0 === $currency ? $default->id : $currency;
            $entry                        = ['preferredCurrency' => $currency, 'name' => $account->name];
            $data['accounts'][$accountId] = $entry;
        }

        return response()
            ->view('javascript.accounts', $data)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * Get info about currencies.
     *
     * @param CurrencyRepositoryInterface $repository
     *
     * @return Response
     */
    public function currencies(CurrencyRepositoryInterface $repository): Response
    {
        $currencies = $repository->get();
        $data       = ['currencies' => []];
        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $currencyId                      = $currency->id;
            $entry                           = ['name' => $currency->name, 'code' => $currency->code, 'symbol' => $currency->symbol];
            $data['currencies'][$currencyId] = $entry;
        }

        return response()
            ->view('javascript.currencies', $data)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * Show some common variables to be used in scripts.
     *
     * @param Request                     $request
     * @param AccountRepositoryInterface  $repository
     * @param CurrencyRepositoryInterface $currencyRepository
     *
     * @return \Illuminate\Http\Response
     */
    public function variables(Request $request, AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository): Response
    {
        $account    = $repository->findNull((int)$request->get('account'));
        $currencyId = 0;
        if (null !== $account) {
            // TODO we can use getAccountCurrency() instead
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
        }
        /** @var TransactionCurrency $currency */
        $currency = $currencyRepository->findNull($currencyId);
        if (null === $currency) {
            /** @var TransactionCurrency $currency */
            $currency = app('amount')->getDefaultCurrency();
        }

        $localeconv                = localeconv();
        $accounting                = app('amount')->getJsConfig($localeconv);
        $localeconv                = localeconv();
        $localeconv['frac_digits'] = $currency->decimal_places;
        $pref                      = app('preferences')->get('language', config('firefly.default_language', 'en_US'));
        /** @noinspection NullPointerExceptionInspection */
        $lang      = $pref->data;
        $dateRange = $this->getDateRangeConfig();
        $uid       = substr(hash('sha256', auth()->user()->id . auth()->user()->email), 0, 12);

        $data = [
            'currencyCode'    => $currency->code,
            'currencySymbol'  => $currency->symbol,
            'accounting'      => $accounting,
            'localeconv'      => $localeconv,
            'language'        => $lang,
            'dateRangeTitle'  => $dateRange['title'],
            'dateRangeConfig' => $dateRange['configuration'],
            'uid'             => $uid,
        ];
        $request->session()->keep(['two-factor-secret']);

        return response()
            ->view('javascript.variables', $data)
            ->header('Content-Type', 'text/javascript');
    }

}
