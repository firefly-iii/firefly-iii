<?php
/**
 * AutoCompleteCollector.php
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

use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Trait AutoCompleteCollector
 */
trait AutoCompleteCollector
{

    /**
     * @param array  $unfiltered
     * @param string $query
     *
     * @return array|null
     */
    protected function filterResult(?array $unfiltered, string $query): ?array
    {
        if (null === $unfiltered) {
            return null; // @codeCoverageIgnore
        }
        if ('' === $query) {
            sort($unfiltered);

            return $unfiltered;
        }
        $return = [];
        if ('' !== $query) {
            $return = array_values(
                array_filter(
                    $unfiltered, function (string $value) use ($query) {
                    return !(false === stripos($value, $query));
                }, ARRAY_FILTER_USE_BOTH
                )
            );
        }
        sort($return);


        return $return;
    }

    /**
     * @param array $types
     *
     * @return array
     */
    protected function getAccounts(array $types): array
    {
        $repository = app(AccountRepositoryInterface::class);
        // find everything:
        /** @var Collection $collection */
        $collection = $repository->getAccountsByType($types);
        $filtered   = $collection->filter(
            function (Account $account) {
                return true === $account->active;
            }
        );

        return array_values(array_unique($filtered->pluck('name')->toArray()));
    }

    /**
     * @return array
     */
    protected function getBills(): array
    {
        $repository = app(BillRepositoryInterface::class);

        return array_unique($repository->getActiveBills()->pluck('name')->toArray());
    }

    /**
     * @return array
     */
    protected function getBudgets(): array
    {
        $repository = app(BudgetRepositoryInterface::class);

        return array_unique($repository->getBudgets()->pluck('name')->toArray());
    }

    /**
     * @return array
     */
    protected function getCategories(): array
    {
        $repository = app(CategoryRepositoryInterface::class);

        return array_unique($repository->getCategories()->pluck('name')->toArray());
    }

    /**
     * @return array
     */
    protected function getCurrencyNames(): array
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);

        return $repository->get()->pluck('name')->toArray();
    }

    /**
     * @return array
     */
    protected function getTags(): array
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);

        return array_unique($repository->get()->pluck('tag')->toArray());
    }

    /**
     * @return array
     */
    protected function getTransactionTypes(): array
    {
        $repository = app(JournalRepositoryInterface::class);

        return array_unique($repository->getTransactionTypes()->pluck('type')->toArray());
    }
}
