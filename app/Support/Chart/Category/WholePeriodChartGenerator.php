<?php
declare(strict_types=1);
/**
 * WholePeriodChartGenerator.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Chart\Category;

use Carbon\Carbon;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;

/**
 * Class WholePeriodChartGenerator
 */
class WholePeriodChartGenerator
{
    /**
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     */
    public function generate(Category $category, Carbon $start, Carbon $end): array
    {
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        $types     = [AccountType::DEFAULT, AccountType::ASSET, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE];
        $accounts  = $accountRepository->getAccountsByType($types);
        $step      = $this->calculateStep($start, $end);
        $chartData = [];
        $spent     = [];
        $earned    = [];

        /** @var Carbon $current */
        $current = clone $start;

        while ($current <= $end) {
            $key          = $current->format('Y-m-d');
            $currentEnd   = app('navigation')->endOfPeriod($current, $step);
            $spent[$key]  = $repository->spentInPeriod($category, $accounts, $current, $currentEnd);
            $earned[$key] = $repository->earnedInPeriod($category, $accounts, $current, $currentEnd);
            $current      = app('navigation')->addPeriod($current, $step, 0);
        }
        $currencies = $this->extractCurrencies($spent) + $this->extractCurrencies($earned);

        // generate chart data (for each currency)
        /** @var array $currency */
        foreach ($currencies as $currency) {
            $code                                     = $currency['currency_code'];
            $name                                     = $currency['currency_name'];
            $chartData[sprintf('spent-in-%s', $code)] = [
                'label'           => (string)trans('firefly.box_spent_in_currency', ['currency' => $name]),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ];

            $chartData[sprintf('earned-in-%s', $code)] = [
                'label'           => (string)trans('firefly.box_earned_in_currency', ['currency' => $name]),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ];
        }

        /** @var Carbon $current */
        $current = clone $start;

        while ($current <= $end) {
            $key        = $current->format('Y-m-d');
            $label      = app('navigation')->periodShow($current, $step);

            /** @var array $currency */
            foreach ($currencies as $currency) {
                $code                                         = $currency['currency_code'];
                $spentInfoKey                                 = sprintf('spent-in-%s', $code);
                $earnedInfoKey                                = sprintf('earned-in-%s', $code);
                $spentAmount                                  = $spent[$key][$code]['spent'] ?? '0';
                $earnedAmount                                 = $earned[$key][$code]['earned'] ?? '0';
                $chartData[$spentInfoKey]['entries'][$label]  = round($spentAmount, $currency['currency_decimal_places']);
                $chartData[$earnedInfoKey]['entries'][$label] = round($earnedAmount, $currency['currency_decimal_places']);
            }
            $current = app('navigation')->addPeriod($current, $step, 0);
        }
        return $chartData;
    }

    /**
     * TODO is a duplicate function.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    protected function calculateStep(Carbon $start, Carbon $end): string
    {

        $step   = '1D';
        $months = $start->diffInMonths($end);
        if ($months > 3) {
            $step = '1W'; // @codeCoverageIgnore
        }
        if ($months > 24) {
            $step = '1M'; // @codeCoverageIgnore
        }
        if ($months > 100) {
            $step = '1Y'; // @codeCoverageIgnore
        }

        return $step;
    }

    /**
     * Loop array of spent/earned info, and extract which currencies are present.
     * Key is the currency ID.
     *
     * @param array $array
     *
     * @return array
     */
    private function extractCurrencies(array $array): array
    {
        $return = [];
        foreach ($array as $info) {
            foreach ($info as $block) {
                $currencyId = $block['currency_id'];
                if (!isset($return[$currencyId])) {
                    $return[$currencyId] = [
                        'currency_id'             => $block['currency_id'],
                        'currency_code'           => $block['currency_code'],
                        'currency_name'           => $block['currency_name'],
                        'currency_symbol'         => $block['currency_symbol'],
                        'currency_decimal_places' => $block['currency_decimal_places'],
                    ];
                }
            }
        }

        return $return;
    }

}
