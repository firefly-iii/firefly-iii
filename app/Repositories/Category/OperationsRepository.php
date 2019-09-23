<?php
/**
 * OperationsRepository.php
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

declare(strict_types=1);

namespace FireflyIII\Repositories\Category;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 *
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
            die(__METHOD__);
        }
    }

    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified category set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     *
     * First currency, then categories.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $categories
     *
     * @return array
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $categories = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL]);
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $categories && $categories->count() > 0) {
            $collector->setCategories($categories);
        }
        if (null === $categories || (null !== $categories && 0 === $categories->count())) {
            $collector->setCategories($this->getCategories());
        }
        $collector->withCategoryInformation()->withAccountInformation()->withBudgetInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $categoryId   = (int)$journal['category_id'];
            $categoryName = (string)$journal['category_name'];

            // catch "no category" entries.
            if (0 === $categoryId) {
                continue;
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'categories'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the categories:
            $array[$currencyId]['categories'][$categoryId] = $array[$currencyId]['categories'][$categoryId] ?? [
                    'id'                   => $categoryId,
                    'name'                 => $categoryName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId = (int)$journal['transaction_journal_id'];


            $array[$currencyId]['categories'][$categoryId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->negative($journal['amount']),
                'date'                     => $journal['date'],
                'source_account_id'        => $journal['source_account_id'],
                'budget_name'              => $journal['budget_name'],
                'source_account_name'      => $journal['source_account_name'],
                'destination_account_id' => $journal['destination_account_id'],
                'destination_account_name' => $journal['destination_account_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
            ];

        }

        return $array;
    }

    /**
     * This method returns a list of all the deposit transaction journals (as arrays) set in that period
     * which have the specified category set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $categories
     *
     * @return array
     */
    public function listIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $categories = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionType::DEPOSIT]);
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $categories && $categories->count() > 0) {
            $collector->setCategories($categories);
        }
        if (null === $categories || (null !== $categories && 0 === $categories->count())) {
            $collector->setCategories($this->getCategories());
        }
        $collector->withCategoryInformation()->withAccountInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId   = (int)$journal['currency_id'];
            $categoryId   = (int)$journal['category_id'];
            $categoryName = (string)$journal['category_name'];

            // catch "no category" entries.
            if (0 === $categoryId) {
                $categoryName = (string)trans('firefly.no_category');
            }

            // info about the currency:
            $array[$currencyId] = $array[$currencyId] ?? [
                    'categories'              => [],
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];

            // info about the categories:
            $array[$currencyId]['categories'][$categoryId] = $array[$currencyId]['categories'][$categoryId] ?? [
                    'id'                   => $categoryId,
                    'name'                 => $categoryName,
                    'transaction_journals' => [],
                ];

            // add journal to array:
            // only a subset of the fields.
            $journalId = (int)$journal['transaction_journal_id'];


            $array[$currencyId]['categories'][$categoryId]['transaction_journals'][$journalId] = [
                'amount'                   => app('steam')->positive($journal['amount']),
                'date'                     => $journal['date'],
                'source_account_id'        => $journal['source_account_id'],
                'destination_account_id'   => $journal['destination_account_id'],
                'source_account_name'      => $journal['source_account_name'],
                'destination_account_name' => $journal['destination_account_name'],
                'description'              => $journal['description'],
                'transaction_group_id'     => $journal['transaction_group_id'],
            ];

        }

        return $array;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Sum of withdrawal journals in period for a set of categories, grouped per currency. Amounts are always negative.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $categories
     *
     * @return array
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $categories = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)
                  ->setTypes([TransactionType::WITHDRAWAL]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null === $categories || (null !== $categories && 0 === $categories->count())) {
            $categories = $this->getCategories();
        }
        $collector->setCategories($categories);
        $collector->withCategoryInformation();
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId                = (int)$journal['currency_id'];
            $array[$currencyId]        = $array[$currencyId] ?? [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => (int)$journal['currency_decimal_places'],
                ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->negative($journal['amount']));
        }

        return $array;
    }

    /**
     * Sum of income journals in period for a set of categories, grouped per currency. Amounts are always positive.
     *
     * @param Carbon          $start
     * @param Carbon          $end
     * @param Collection|null $accounts
     * @param Collection|null $categories
     *
     * @return array
     */
    public function sumIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $categories = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)
                  ->setTypes([TransactionType::DEPOSIT]);

        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null === $categories || (null !== $categories && 0 === $categories->count())) {
            $categories = $this->getCategories();
        }
        $collector->setCategories($categories);
        $journals = $collector->getExtractedJournals();
        $array    = [];

        foreach ($journals as $journal) {
            $currencyId                = (int)$journal['currency_id'];
            $array[$currencyId]        = $array[$currencyId] ?? [
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_code'           => $journal['currency_code'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                ];
            $array[$currencyId]['sum'] = bcadd($array[$currencyId]['sum'], app('steam')->positive($journal['amount']));
        }

        return $array;
    }

    /**
     * Returns a list of all the categories belonging to a user.
     *
     * @return Collection
     */
    private function getCategories(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->categories()->get();

        return $set;
    }
}