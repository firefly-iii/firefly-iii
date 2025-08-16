<?php

/**
 * FrontpageChartGenerator.php
 * Copyright (c) 2020 james@firefly-iii.org
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
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class FrontpageChartGenerator
 */
class FrontpageChartGenerator
{
    use AugumentData;

    public bool                           $convertToPrimary = false;
    public TransactionCurrency            $primaryCurrency;
    private AccountRepositoryInterface    $accountRepos;
    private array                         $currencies;
    private NoCategoryRepositoryInterface $noCatRepos;
    private OperationsRepositoryInterface $opsRepos;
    private CategoryRepositoryInterface   $repository;

    /**
     * FrontpageChartGenerator constructor.
     */
    public function __construct(private Carbon $start, private Carbon $end)
    {
        $this->currencies   = [];
        $this->repository   = app(CategoryRepositoryInterface::class);
        $this->accountRepos = app(AccountRepositoryInterface::class);
        $this->opsRepos     = app(OperationsRepositoryInterface::class);
        $this->noCatRepos   = app(NoCategoryRepositoryInterface::class);
    }

    public function generate(): array
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        $categories   = $this->repository->getCategories();
        $accounts     = $this->accountRepos->getAccountsByType([AccountTypeEnum::DEBT->value, AccountTypeEnum::LOAN->value, AccountTypeEnum::MORTGAGE->value, AccountTypeEnum::ASSET->value, AccountTypeEnum::DEFAULT->value]);
        $collection   = $this->collectExpensesAll($categories, $accounts);

        // collect for no-category:
        $noCategory   = $this->collectNoCatExpenses($accounts);
        $collection   = array_merge($collection, $noCategory);

        // sort temp array by amount.
        $amounts      = array_column($collection, 'sum_float');
        array_multisort($amounts, SORT_ASC, $collection);

        $currencyData = $this->createCurrencyGroups($collection);

        return $this->insertValues($currencyData, $collection);
    }

    private function addCurrency(array $currency): void
    {
        $currencyId = (int)$currency['currency_id'];

        $this->currencies[$currencyId] ??= [
            'currency_id'             => $currencyId,
            'currency_name'           => $currency['currency_name'],
            'currency_symbol'         => $currency['currency_symbol'],
            'currency_code'           => $currency['currency_code'],
            'currency_decimal_places' => $currency['currency_decimal_places'],
        ];
    }

    private function collectNoCatExpenses(Collection $accounts): array
    {
        $noCatExp = $this->noCatRepos->sumExpenses($this->start, $this->end, $accounts);
        $tempData = [];
        foreach ($noCatExp as $currency) {
            $this->addCurrency($currency);
            $tempData[] = [
                'name'        => trans('firefly.no_category'),
                'sum'         => $currency['sum'],
                'sum_float'   => round((float)$currency['sum'], $currency['currency_decimal_places'] ?? 2), // intentional float
                'currency_id' => (int)$currency['currency_id'],
            ];
        }

        return $tempData;
    }

    private function createCurrencyGroups(array $data): array
    {
        $return = [];
        $names  = $this->expandNames($data);

        /**
         * @var array $currency
         */
        foreach ($this->currencies as $currencyId => $currency) {
            $key          = sprintf('spent-%d', $currencyId);
            $return[$key] = [
                'label'           => sprintf('%s (%s)', (string)trans('firefly.spent'), $currency['currency_name']),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'entries'         => $names,
            ];
        }

        return $return;
    }

    private function insertValues(array $currencyData, array $monetaryData): array
    {
        /** @var array $array */
        foreach ($monetaryData as $array) {
            $direction                                = $array['sum_float'] < 0 ? 'spent' : 'earned';
            $key                                      = sprintf('%s-%d', $direction, $array['currency_id']);
            $category                                 = $array['name'];
            $amount                                   = $array['sum_float'] < 0 ? $array['sum_float'] * -1 : $array['sum_float'];
            $currencyData[$key]['entries'][$category] = $amount;
        }

        return $currencyData;
    }

    private function collectExpensesAll(Collection $categories, Collection $accounts): array
    {
        Log::debug(sprintf('Collect expenses for %d category(ies).', count($categories)));
        $spent    = $this->opsRepos->collectExpenses($this->start, $this->end, $accounts, $categories);
        $tempData = [];
        foreach ($categories as $category) {
            $sums = $this->opsRepos->sumCollectedTransactionsByCategory($spent, $category, 'negative', $this->convertToPrimary);
            if (0 === count($sums)) {
                continue;
            }
            foreach ($sums as $currency) {
                $this->addCurrency($currency);
                $tempData[] = [
                    'name'        => $category->name,
                    'sum'         => $currency['sum'],
                    'sum_float'   => round((float)$currency['sum'], $currency['currency_decimal_places']),
                    'currency_id' => (int)$currency['currency_id'],
                ];
            }
        }

        return $tempData;
    }
}
