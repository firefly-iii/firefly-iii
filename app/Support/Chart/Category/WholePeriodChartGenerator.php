<?php

/**
 * WholePeriodChartGenerator.php
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

namespace FireflyIII\Support\Chart\Category;

use Carbon\Carbon;
use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class WholePeriodChartGenerator
 */
class WholePeriodChartGenerator
{
    public bool $convertToNative;

    public function generate(Category $category, Carbon $start, Carbon $end): array
    {
        $collection        = new Collection([$category]);

        /** @var OperationsRepositoryInterface $opsRepository */
        $opsRepository     = app(OperationsRepositoryInterface::class);

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);

        $types             = [AccountTypeEnum::DEFAULT->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::DEBT->value, AccountTypeEnum::MORTGAGE->value];
        $accounts          = $accountRepository->getAccountsByType($types);
        $step              = $this->calculateStep($start, $end);
        $chartData         = [];
        $spent             = [];
        $earned            = [];

        $current           = clone $start;

        while ($current <= $end) {
            $key          = $current->format('Y-m-d');
            $currentEnd   = app('navigation')->endOfPeriod($current, $step);
            $spent[$key]  = $opsRepository->sumExpenses($current, $currentEnd, $accounts, $collection);
            $earned[$key] = $opsRepository->sumIncome($current, $currentEnd, $accounts, $collection);
            $current      = app('navigation')->addPeriod($current, $step, 0);
        }

        $currencies        = $this->extractCurrencies($spent) + $this->extractCurrencies($earned);

        // generate chart data (for each currency)
        /** @var array $currency */
        foreach ($currencies as $currency) {
            $code                                      = $currency['currency_code'];
            $name                                      = $currency['currency_name'];
            $chartData[sprintf('spent-in-%s', $code)]  = [
                'label'           => (string) trans('firefly.box_spent_in_currency', ['currency' => $name]),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ];

            $chartData[sprintf('earned-in-%s', $code)] = [
                'label'           => (string) trans('firefly.box_earned_in_currency', ['currency' => $name]),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ];
        }

        $current           = clone $start;

        while ($current <= $end) {
            $key     = $current->format('Y-m-d');
            $label   = app('navigation')->periodShow($current, $step);

            /** @var array $currency */
            foreach ($currencies as $currency) {
                $code                                         = $currency['currency_code'];
                $currencyId                                   = $currency['currency_id'];
                $spentInfoKey                                 = sprintf('spent-in-%s', $code);
                $earnedInfoKey                                = sprintf('earned-in-%s', $code);
                $spentAmount                                  = $spent[$key][$currencyId]['sum'] ?? '0';
                $earnedAmount                                 = $earned[$key][$currencyId]['sum'] ?? '0';
                $chartData[$spentInfoKey]['entries'][$label]  = app('steam')->bcround($spentAmount, $currency['currency_decimal_places']);
                $chartData[$earnedInfoKey]['entries'][$label] = app('steam')->bcround($earnedAmount, $currency['currency_decimal_places']);
            }
            $current = app('navigation')->addPeriod($current, $step, 0);
        }

        return $chartData;
    }

    /**
     * TODO this method is duplicated
     */
    protected function calculateStep(Carbon $start, Carbon $end): string
    {
        $step   = '1D';
        $months = $start->diffInMonths($end, true);
        if ($months > 3) {
            $step = '1W';
        }
        if ($months > 24) {
            $step = '1M';
        }
        if ($months > 100) {
            $step = '1Y';
        }

        return $step;
    }

    /**
     * Loop array of spent/earned info, and extract which currencies are present.
     * Key is the currency ID.
     */
    private function extractCurrencies(array $array): array
    {
        $return = [];
        foreach ($array as $block) {
            foreach ($block as $currencyId => $currencyRow) {
                $return[$currencyId] ??= [
                    'currency_id'             => $currencyId,
                    'currency_name'           => $currencyRow['currency_name'],
                    'currency_symbol'         => $currencyRow['currency_symbol'],
                    'currency_code'           => $currencyRow['currency_code'],
                    'currency_decimal_places' => $currencyRow['currency_decimal_places'],
                ];
            }
        }

        return $return;
    }
}
