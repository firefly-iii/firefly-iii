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
     * @return $this
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
     * @return $this
     */
    public function currencies(CurrencyRepositoryInterface $repository)
    {
        $currencies = $repository->get();
        $data       = ['currencies' => [],];
        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $currencyId                    = $currency->id;
            $entry                         = ['name' => $currency->name, 'code' => $currency->code, 'symbol' => $currency->symbol];
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
        $picker                    = $this->getDateRangePicker();
        $start                     = Session::get('start');
        $end                       = Session::get('end');
        $linkTitle                 = sprintf('%s - %s', $start->formatLocalized($this->monthAndDayFormat), $end->formatLocalized($this->monthAndDayFormat));
        $firstDate                 = session('first')->format('Y-m-d');
        $localeconv                = localeconv();
        $accounting                = Amount::getJsConfig($localeconv);
        $localeconv                = localeconv();
        $defaultCurrency           = Amount::getDefaultCurrency();
        $localeconv['frac_digits'] = $defaultCurrency->decimal_places;
        $pref                      = Preferences::get('language', config('firefly.default_language', 'en_US'));
        $lang                      = $pref->data;
        $data                      = [
            'picker'         => $picker,
            'linkTitle'      => $linkTitle,
            'firstDate'      => $firstDate,
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

    /**
     * @return array
     * @throws FireflyException
     */
    private function getDateRangePicker(): array
    {
        $viewRange = Preferences::get('viewRange', '1M')->data;
        $start     = Session::get('start');
        $end       = Session::get('end');

        $prevStart = clone $start;
        $prevEnd   = clone $start;
        $nextStart = clone $end;
        $nextEnd   = clone $end;
        if ($viewRange === 'custom') {
            $days = $start->diffInDays($end);
            $prevStart->subDays($days);
            $nextEnd->addDays($days);
            unset($days);
        }

        if ($viewRange !== 'custom') {
            $prevStart = Navigation::subtractPeriod($start, $viewRange);// subtract for previous period
            $prevEnd   = Navigation::endOfPeriod($prevStart, $viewRange);
            $nextStart = Navigation::addPeriod($start, $viewRange, 0); // add for previous period
            $nextEnd   = Navigation::endOfPeriod($nextStart, $viewRange);
        }

        $ranges             = [];
        $ranges['current']  = [$start->format('Y-m-d'), $end->format('Y-m-d')];
        $ranges['previous'] = [$prevStart->format('Y-m-d'), $prevEnd->format('Y-m-d')];
        $ranges['next']     = [$nextStart->format('Y-m-d'), $nextEnd->format('Y-m-d')];

        switch ($viewRange) {
            default:
                throw new FireflyException('The date picker does not yet support "' . $viewRange . '".'); // @codeCoverageIgnore
            case '1D':
            case 'custom':
                $format = (string)trans('config.month_and_day');
                break;
            case '3M':
                $format = (string)trans('config.quarter_in_year');
                break;
            case '6M':
                $format = (string)trans('config.half_year');
                break;
            case '1Y':
                $format = (string)trans('config.year');
                break;
            case '1M':
                $format = (string)trans('config.month');
                break;
            case '1W':
                $format = (string)trans('config.week_in_year');
                break;
        }

        $current = $start->formatLocalized($format);
        $next    = $nextStart->formatLocalized($format);
        $prev    = $prevStart->formatLocalized($format);

        return [
            'start'    => $start->format('Y-m-d'),
            'end'      => $end->format('Y-m-d'),
            'current'  => $current,
            'previous' => $prev,
            'next'     => $next,
            'ranges'   => $ranges,
        ];
    }

}
