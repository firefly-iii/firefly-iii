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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\Request;
use Log;
use Navigation;
use Preferences;

/**
 * Class JavascriptController.
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

        $data = ['accounts' => []];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId                    = $account->id;
            $currency                     = intval($account->getMeta('currency_id'));
            $currency                     = 0 === $currency ? $default->id : $currency;
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
        $data       = ['currencies' => []];
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
    public function variables(Request $request, AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository)
    {
        $account    = $repository->find(intval($request->get('account')));
        $currencyId = 0;
        if (null !== $account) {
            $currencyId = intval($account->getMeta('currency_id'));
        }
        /** @var TransactionCurrency $currency */
        $currency = $currencyRepository->find($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency();
        }

        $localeconv                = localeconv();
        $accounting                = app('amount')->getJsConfig($localeconv);
        $localeconv                = localeconv();
        $localeconv['frac_digits'] = $currency->decimal_places;
        $pref                      = Preferences::get('language', config('firefly.default_language', 'en_US'));
        $lang                      = $pref->data;
        $dateRange                 = $this->getDateRangeConfig();

        $data = [
            'currencyCode'    => $currency->code,
            'currencySymbol'  => $currency->symbol,
            'accounting'      => $accounting,
            'localeconv'      => $localeconv,
            'language'        => $lang,
            'dateRangeTitle'  => $dateRange['title'],
            'dateRangeConfig' => $dateRange['configuration'],
        ];
        $request->session()->keep(['two-factor-secret']);

        return response()
            ->view('javascript.variables', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * @return array
     */
    private function getDateRangeConfig(): array
    {
        $viewRange = Preferences::get('viewRange', '1M')->data;
        $start     = session('start');
        $end       = session('end');
        $first     = session('first');
        $title     = sprintf('%s - %s', $start->formatLocalized($this->monthAndDayFormat), $end->formatLocalized($this->monthAndDayFormat));
        $isCustom  = session('is_custom_range');
        $ranges    = [
            // first range is the current range:
            $title => [$start, $end],
        ];
        Log::debug(sprintf('viewRange is %s', $viewRange));

        // when current range is a custom range, add the current period as the next range.
        if ($isCustom) {
            Log::debug('Custom is true.');
            $index             = Navigation::periodShow($start, $viewRange);
            $customPeriodStart = Navigation::startOfPeriod($start, $viewRange);
            $customPeriodEnd   = Navigation::endOfPeriod($customPeriodStart, $viewRange);
            $ranges[$index]    = [$customPeriodStart, $customPeriodEnd];
        }
        // then add previous range and next range
        $previousDate   = Navigation::subtractPeriod($start, $viewRange);
        $index          = Navigation::periodShow($previousDate, $viewRange);
        $previousStart  = Navigation::startOfPeriod($previousDate, $viewRange);
        $previousEnd    = Navigation::endOfPeriod($previousStart, $viewRange);
        $ranges[$index] = [$previousStart, $previousEnd];

        $nextDate       = Navigation::addPeriod($start, $viewRange, 0);
        $index          = Navigation::periodShow($nextDate, $viewRange);
        $nextStart      = Navigation::startOfPeriod($nextDate, $viewRange);
        $nextEnd        = Navigation::endOfPeriod($nextStart, $viewRange);
        $ranges[$index] = [$nextStart, $nextEnd];

        // everything
        $index          = strval(trans('firefly.everything'));
        $ranges[$index] = [$first, new Carbon];

        $return = [
            'title'         => $title,
            'configuration' => [
                'apply'       => strval(trans('firefly.apply')),
                'cancel'      => strval(trans('firefly.cancel')),
                'from'        => strval(trans('firefly.from')),
                'to'          => strval(trans('firefly.to')),
                'customRange' => strval(trans('firefly.customRange')),
                'start'       => $start->format('Y-m-d'),
                'end'         => $end->format('Y-m-d'),
                'ranges'      => $ranges,
            ],
        ];

        return $return;
    }
}
