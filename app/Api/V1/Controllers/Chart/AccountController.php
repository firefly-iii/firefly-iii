<?php

/**
 * AccountController.php
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

namespace FireflyIII\Api\V1\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Api\V1\Controllers\Controller;
use FireflyIII\Api\V1\Requests\Data\DateRequest;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\ChartGeneration;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    use DateCalculation, AugumentData, ChartGeneration;

    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    private CurrencyRepositoryInterface $currencyRepository;
    private AccountRepositoryInterface  $repository;

    /**
     * AccountController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user             = auth()->user();
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);
                $this->generator          = app(GeneratorInterface::class);

                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->currencyRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * This endpoint is documented at:
     * https://api-docs.firefly-iii.org/#/charts/getChartAccountOverview
     *
     * @param DateRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function overview(DateRequest $request): JsonResponse
    {
        // parameters for chart:
        $dates = $request->getAll();
        /** @var Carbon $start */
        $start = $dates['start'];
        /** @var Carbon $end */
        $end = $dates['end'];

        // user's preferences
        $defaultSet = $this->repository->getAccountsByType([AccountType::ASSET])->pluck('id')->toArray();
        $frontPage  = app('preferences')->get('frontPageAccounts', $defaultSet);
        $default    = app('amount')->getDefaultCurrency();

        if (empty($frontPage->data)) {
            $frontPage->data = $defaultSet;
            $frontPage->save();
        }


        // get accounts:
        $accounts  = $this->repository->getAccountsById($frontPage->data);
        $chartData = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $this->repository->getAccountCurrency($account);
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet   = [
                'label'                   => $account->name,
                'currency_id'             => (string)$currency->id,
                'currency_code'           => $currency->code,
                'currency_symbol'         => $currency->symbol,
                'currency_decimal_places' => $currency->decimal_places,
                'start_date'              => $start->toAtomString(),
                'end_date'                => $end->toAtomString(),
                'type'                    => 'line', // line, area or bar
                'yAxisID'                 => 0, // 0, 1, 2
                'entries'                 => [],
            ];
            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = round((float)array_values($range)[0], 12);
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->toAtomString();
                $balance  = array_key_exists($format, $range) ? round((float)$range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[] = $currentSet;
        }

        return response()->json($chartData);
    }

     /**
     * Shows overview of account during a single period.
     *
     * @param Account $account
     *
     * @return JsonResponse
     * @throws FireflyException
     * @throws JsonException
     */
    public function period(Account $account): JsonResponse
    {
        $dateParam = $this->parameters->get('date');
        $date = $dateParam ? new Carbon($dateParam) : Carbon::now();
        $start = clone $date;
        $end   = clone $date;
        $start->startOfMonth();
        $end->endOfMonth();
        $chartData = [];
        $cache     = new CacheProperties;
        $cache->addProperty('chart.account.period');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return response()->json($cache->get());
        }
        $currencies = $this->repository->getUsedCurrencies($account);

        // if the account is not expense or revenue, just use the account's default currency.
        if (!in_array($account->accountType->type, [AccountType::REVENUE, AccountType::EXPENSE], true)) {
            $currencies = [$this->repository->getAccountCurrency($account) ?? app('amount')->getDefaultCurrency()];
        }

        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $chartData[] = $this->periodByCurrency($start, $end, $account, $currency);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * @param Carbon              $start
     * @param Carbon              $end
     * @param Account             $account
     * @param TransactionCurrency $currency
     *
     * @return array
     * @throws FireflyException
     * @throws JsonException
     */
    private function periodByCurrency(Carbon $start, Carbon $end, Account $account, TransactionCurrency $currency): array
    {
        $locale  = app('steam')->getLocale();
        $step    = $this->calculateStep($start, $end);
        $result  = [
            'label'           => sprintf('%s (%s)', $account->name, $currency->symbol),
            'currency_symbol' => $currency->symbol,
            'currency_code'   => $currency->code,
            'entries'         => [],
        ];
        $entries = [];
        $current = clone $start;
        switch ($step) {
            default:
                break;
            case '1D':
                // per day the entire period, balance for every day.
                $format   = (string)trans('config.month_and_day', [], $locale);
                $range    = app('steam')->balanceInRange($account, $start, $end, $currency);
                $previous = array_values($range)[0];
                while ($end >= $current) {
                    $theDate         = $current->format('Y-m-d');
                    $balance         = $range[$theDate] ?? $previous;
                    $label           = $current->formatLocalized($format);
                    $entries[$label] = (float)$balance;
                    $previous        = $balance;
                    $current->addDay();
                }
                break;

            case '1W':
            case '1M':
            case '1Y':
                while ($end >= $current) {
                    $balance         = (float)app('steam')->balance($account, $current, $currency);
                    $label           = app('navigation')->periodShow($current, $step);
                    $entries[$label] = $balance;
                    $current         = app('navigation')->addPeriod($current, $step, 0);
                }
                break;

        }
        $result['entries'] = $entries;

        return $result;
    }
}
