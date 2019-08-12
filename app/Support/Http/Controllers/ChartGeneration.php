<?php
/**
 * ChartGeneration.php
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

namespace FireflyIII\Support\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;

/**
 * Trait ChartGeneration
 */
trait ChartGeneration
{
    /**
     * Shows an overview of the account balances for a set of accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function accountBalanceChart(Collection $accounts, Carbon $start, Carbon $end): array // chart helper method.
    {

        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.account-balance-chart');
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        Log::debug('Regenerate chart.account.account-balance-chart from scratch.');
        /** @var GeneratorInterface $generator */
        $generator = app(GeneratorInterface::class);

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);

        $default   = app('amount')->getDefaultCurrency();
        $chartData = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $repository->findNull((int)$accountRepos->getMetaValue($account, 'currency_id'));
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet = [
                'label'           => $account->name,
                'currency_symbol' => $currency->symbol,
                'entries'         => [],
            ];

            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = array_values($range)[0];
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->formatLocalized((string)trans('config.month_and_day'));
                $balance  = isset($range[$format]) ? round($range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[] = $currentSet;
        }
        $data = $generator->multiSet($chartData);
        $cache->store($data);

        return $data;
    }

    /**
     * Collects the incomes and expenses for the given periods, grouped per month. Will cache its results.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function getChartData(Collection $accounts, Carbon $start, Carbon $end): array // chart helper function
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.get-chart-data');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $currentStart = clone $start;
        $spentArray   = [];
        $earnedArray  = [];

        /** @var AccountTaskerInterface $tasker */
        $tasker = app(AccountTaskerInterface::class);

        while ($currentStart <= $end) {
            $currentEnd = app('navigation')->endOfPeriod($currentStart, '1M');
            $earned     = (string)array_sum(
                array_map(
                    function ($item) {
                        return $item['sum'];
                    },
                    $tasker->getIncomeReport($currentStart, $currentEnd, $accounts)
                )
            );

            $spent = (string)array_sum(
                array_map(
                    function ($item) {
                        return $item['sum'];
                    },
                    $tasker->getExpenseReport($currentStart, $currentEnd, $accounts)
                )
            );

            $label               = $currentStart->format('Y-m') . '-01';
            $spentArray[$label]  = bcmul($spent, '-1');
            $earnedArray[$label] = $earned;
            $currentStart        = app('navigation')->addPeriod($currentStart, '1M', 0);
        }
        $result = [
            'spent'  => $spentArray,
            'earned' => $earnedArray,
        ];
        $cache->store($result);

        return $result;
    }
}
