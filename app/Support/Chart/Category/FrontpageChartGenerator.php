<?php
declare(strict_types=1);
/**
 * FrontpageChartGenerator.php
 * Copyright (c) 2020 thegrumpydictator@gmail.com
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

namespace FireflyIII\Support\Chart\Category;

use Carbon\Carbon;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Category\NoCategoryRepositoryInterface;
use FireflyIII\Repositories\Category\OperationsRepositoryInterface;
use FireflyIII\Support\Http\Controllers\AugumentData;
use Illuminate\Support\Collection;

/**
 * Class FrontpageChartGenerator
 */
class FrontpageChartGenerator
{
    use AugumentData;
    /** @var AccountRepositoryInterface */
    private $accountRepos;
    /** @var array */
    private $currencies;
    /** @var Carbon */
    private $end;
    /** @var NoCategoryRepositoryInterface */
    private $noCatRepos;
    /** @var OperationsRepositoryInterface */
    private $opsRepos;
    /** @var CategoryRepositoryInterface */
    private $repository;
    /** @var Carbon */
    private $start;

    /**
     * FrontpageChartGenerator constructor.
     *
     * @param Carbon $start
     * @param Carbon $end
     */
    public function __construct(Carbon $start, Carbon $end)
    {
        $this->currencies   = [];
        $this->start        = $start;
        $this->end          = $end;
        $this->repository   = app(CategoryRepositoryInterface::class);
        $this->accountRepos = app(AccountRepositoryInterface::class);
        $this->opsRepos     = app(OperationsRepositoryInterface::class);
        $this->noCatRepos   = app(NoCategoryRepositoryInterface::class);

    }

    /**
     * @return array
     */
    public function generate(): array
    {
        $categories = $this->repository->getCategories();
        $accounts   = $this->accountRepos->getAccountsByType(
            [AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::ASSET, AccountType::DEFAULT]
        );

        // get expenses + income per category:
        $collection = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            // get expenses
            $collection[] = $this->collectExpenses($category, $accounts);
            $collection[] = $this->collectIncome($category, $accounts);
        }

        // collect for no-category:
        $collection[] = $this->collectNoCatExpenses($accounts);
        $collection[] = $this->collectNoCatIncome($accounts);

        $tempData = array_merge(...$collection);

        // sort temp array by amount.
        $amounts = array_column($tempData, 'sum_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        $currencyData = $this->createCurrencyGroups($tempData);

        return $this->insertValues($currencyData, $tempData);
    }

    /**
     * @param array $currency
     */
    private function addCurrency(array $currency): void
    {
        $currencyId = (int) $currency['currency_id'];

        $this->currencies[$currencyId] = $this->currencies[$currencyId] ?? [
                'currency_id'             => $currencyId,
                'currency_name'           => $currency['currency_name'],
                'currency_symbol'         => $currency['currency_symbol'],
                'currency_code'           => $currency['currency_code'],
                'currency_decimal_places' => $currency['currency_decimal_places'],
            ];
    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return array
     */
    private function collectExpenses(Category $category, Collection $accounts): array
    {
        $spent    = $this->opsRepos->sumExpenses($this->start, $this->end, $accounts, new Collection([$category]));
        $tempData = [];
        foreach ($spent as $currency) {
            $this->addCurrency($currency);
            $tempData[] = [
                'name'        => $category->name,
                'sum'         => $currency['sum'],
                'sum_float'   => round($currency['sum'], $currency['currency_decimal_places']),
                'currency_id' => (int) $currency['currency_id'],
            ];
        }

        return $tempData;
    }

    /**
     * @param Category   $category
     * @param Collection $accounts
     *
     * @return array
     */
    private function collectIncome(Category $category, Collection $accounts): array
    {
        $spent    = $this->opsRepos->sumIncome($this->start, $this->end, $accounts, new Collection([$category]));
        $tempData = [];
        foreach ($spent as $currency) {
            $this->addCurrency($currency);
            $tempData[] = [
                'name'        => $category->name,
                'sum'         => $currency['sum'],
                'sum_float'   => round($currency['sum'], $currency['currency_decimal_places']),
                'currency_id' => (int) $currency['currency_id'],
            ];
        }

        return $tempData;
    }

    /**
     * @param Collection $accounts
     *
     * @return array
     */
    private function collectNoCatExpenses(Collection $accounts): array
    {
        $noCatExp = $this->noCatRepos->sumExpenses($this->start, $this->end, $accounts);
        $tempData = [];
        foreach ($noCatExp as $currency) {
            $this->addCurrency($currency);
            $tempData[] = [
                'name'        => trans('firefly.no_category'),
                'sum'         => $currency['sum'],
                'sum_float'   => round($currency['sum'], $currency['currency_decimal_places'] ?? 2),
                'currency_id' => (int) $currency['currency_id'],];
        }

        return $tempData;
    }

    /**
     * @param Collection $accounts
     *
     * @return array
     */
    private function collectNoCatIncome(Collection $accounts): array
    {
        $noCatExp = $this->noCatRepos->sumIncome($this->start, $this->end, $accounts);
        $tempData = [];
        foreach ($noCatExp as $currency) {
            $this->addCurrency($currency);
            $tempData[] = [
                'name'        => trans('firefly.no_category'),
                'sum'         => $currency['sum'],
                'sum_float'   => round($currency['sum'], $currency['currency_decimal_places'] ?? 2),
                'currency_id' => (int) $currency['currency_id'],];
        }

        return $tempData;
    }

    /**
     * @param array $data
     *
     * @return array
     */
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
                'label'           => sprintf('%s (%s)', (string) trans('firefly.spent'), $currency['currency_name']),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'entries'         => $names,
            ];
            $key          = sprintf('earned-%d', $currencyId);
            $return[$key] = [
                'label'           => sprintf('%s (%s)', (string) trans('firefly.earned'), $currency['currency_name']),
                'type'            => 'bar',
                'currency_symbol' => $currency['currency_symbol'],
                'data_type'       => 'earned',
                'entries'         => $names,
            ];
        }

        return $return;
    }


    /**
     * @param array $currencyData
     * @param array $monetaryData
     *
     * @return array
     */
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

}
