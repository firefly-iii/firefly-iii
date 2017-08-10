<?php
/**
 * JavascriptController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Amount;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\Request;
use Navigation;
use Preferences;
use Session;

/**
 * Class JavascriptController
 *
 * @package FireflyIII\Http\Controllers
 */
class JavascriptController extends Controller
{
    /**
     * @param AccountRepositoryInterface  $repository
     * @param CurrencyRepositoryInterface $currencyRepository
     *
     * @return \Illuminate\Http\Response
     */
    public function accounts(AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository)
    {
        $accounts   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $preference = Preferences::get('currencyPreference', config('firefly.default_currency', 'EUR'));
        $default    = $currencyRepository->findByCode($preference->data);

        $data = ['accounts' => [],];


        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId                    = $account->id;
            $currency                     = intval($account->getMeta('currency_id'));
            $currency                     = $currency === 0 ? $default->id : $currency;
            $entry                        = ['preferredCurrency' => $currency, 'name' => $account->name];
            $data['accounts'][$accountId] = $entry;
        }


        return response()
            ->view('javascript.accounts', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * @param CurrencyRepositoryInterface $repository
     *
     * @return \Illuminate\Http\Response
     */
    public function currencies(CurrencyRepositoryInterface $repository)
    {
        $currencies = $repository->get();
        $data       = ['currencies' => [],];
        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $currencyId                      = $currency->id;
            $entry                           = ['name' => $currency->name, 'code' => $currency->code, 'symbol' => $currency->symbol];
            $data['currencies'][$currencyId] = $entry;
        }

        return response()
            ->view('javascript.currencies', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function variables(Request $request)
    {
        $localeconv                = localeconv();
        $accounting                = Amount::getJsConfig($localeconv);
        $localeconv                = localeconv();
        $defaultCurrency           = Amount::getDefaultCurrency();
        $localeconv['frac_digits'] = $defaultCurrency->decimal_places;
        $pref                      = Preferences::get('language', config('firefly.default_language', 'en_US'));
        $lang                      = $pref->data;
        $data                      = [
            'currencyCode'   => Amount::getCurrencyCode(),
            'currencySymbol' => Amount::getCurrencySymbol(),
            'accounting'     => $accounting,
            'localeconv'     => $localeconv,
            'language'       => $lang,
        ];
        $request->session()->keep(['two-factor-secret']);

        return response()
            ->view('javascript.variables', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }
}
