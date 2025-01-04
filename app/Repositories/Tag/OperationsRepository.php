<?php

/**
 * OperationsRepository.php
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

namespace FireflyIII\Repositories\Tag;

use Carbon\Carbon;
use FireflyIII\Enums\TransactionTypeEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class OperationsRepository
 */
class OperationsRepository implements OperationsRepositoryInterface
{
    private User $user;

    /**
     * This method returns a list of all the withdrawal transaction journals (as arrays) set in that period
     * which have the specified tag(s) set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always negative.
     */
    public function listExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $tags = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector      = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionTypeEnum::WITHDRAWAL->value]);
        $tagIds         = [];
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $tags && $tags->count() > 0) {
            $collector->setTags($tags);
            $tagIds = $tags->pluck('id')->toArray();
        }
        if (null === $tags || 0 === $tags->count()) {
            $collector->setTags($this->getTags());
            $tagIds = $this->getTags()->pluck('id')->toArray();
        }
        $collector->withCategoryInformation()->withAccountInformation()->withBudgetInformation();
        $journals       = $collector->getExtractedJournals();
        $array          = [];
        $listedJournals = [];
        foreach ($journals as $journal) {
            $currencyId = (int) $journal['currency_id'];
            $array[$currencyId] ??= [
                'tags'                    => [],
                'currency_id'             => $currencyId,
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
            ];

            // may have multiple tags:
            foreach ($journal['tags'] as $tag) {
                $tagId                                                                  = (int) $tag['id'];
                $tagName                                                                = (string) $tag['name'];
                $journalId                                                              = (int) $journal['transaction_journal_id'];
                if (!in_array($tagId, $tagIds, true)) {
                    continue;
                }

                // TODO not sure what this check does.
                if (in_array($journalId, $listedJournals, true)) {
                    continue;
                }
                $listedJournals[]                                                       = $journalId;
                $array[$currencyId]['tags'][$tagId] ??= [
                    'id'                   => $tagId,
                    'name'                 => $tagName,
                    'transaction_journals' => [],
                ];

                $array[$currencyId]['tags'][$tagId]['transaction_journals'][$journalId] = [
                    'amount'                   => app('steam')->negative($journal['amount']),
                    'date'                     => $journal['date'],
                    'source_account_id'        => $journal['source_account_id'],
                    'budget_name'              => $journal['budget_name'],
                    'category_name'            => $journal['category_name'],
                    'source_account_name'      => $journal['source_account_name'],
                    'destination_account_id'   => $journal['destination_account_id'],
                    'destination_account_name' => $journal['destination_account_name'],
                    'description'              => $journal['description'],
                    'transaction_group_id'     => $journal['transaction_group_id'],
                ];
            }
        }

        return $array;
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    private function getTags(): Collection
    {
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);

        return $repository->get();
    }

    /**
     * This method returns a list of all the deposit transaction journals (as arrays) set in that period
     * which have the specified tag(s) set to them. It's grouped per currency, with as few details in the array
     * as possible. Amounts are always positive.
     */
    public function listIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $tags = null): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector      = app(GroupCollectorInterface::class);
        $collector->setUser($this->user)->setRange($start, $end)->setTypes([TransactionTypeEnum::DEPOSIT->value]);
        $tagIds         = [];
        if (null !== $accounts && $accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (null !== $tags && $tags->count() > 0) {
            $collector->setTags($tags);
            $tagIds = $tags->pluck('id')->toArray();
        }
        if (null === $tags || 0 === $tags->count()) {
            $collector->setTags($this->getTags());
            $tagIds = $this->getTags()->pluck('id')->toArray();
        }
        $collector->withCategoryInformation()->withAccountInformation()->withBudgetInformation()->withTagInformation();
        $journals       = $collector->getExtractedJournals();
        $array          = [];
        $listedJournals = [];

        foreach ($journals as $journal) {
            $currencyId = (int) $journal['currency_id'];
            $array[$currencyId] ??= [
                'tags'                    => [],
                'currency_id'             => $currencyId,
                'currency_name'           => $journal['currency_name'],
                'currency_symbol'         => $journal['currency_symbol'],
                'currency_code'           => $journal['currency_code'],
                'currency_decimal_places' => $journal['currency_decimal_places'],
            ];

            // may have multiple tags:
            foreach ($journal['tags'] as $tag) {
                $tagId                                                                  = (int) $tag['id'];
                $tagName                                                                = (string) $tag['name'];
                $journalId                                                              = (int) $journal['transaction_journal_id'];

                if (!in_array($tagId, $tagIds, true)) {
                    continue;
                }

                if (in_array($journalId, $listedJournals, true)) {
                    continue;
                }
                $listedJournals[]                                                       = $journalId;

                $array[$currencyId]['tags'][$tagId] ??= [
                    'id'                   => $tagId,
                    'name'                 => $tagName,
                    'transaction_journals' => [],
                ];
                $journalId                                                              = (int) $journal['transaction_journal_id'];
                $array[$currencyId]['tags'][$tagId]['transaction_journals'][$journalId] = [
                    'amount'                   => app('steam')->positive($journal['amount']),
                    'date'                     => $journal['date'],
                    'source_account_id'        => $journal['source_account_id'],
                    'budget_name'              => $journal['budget_name'],
                    'source_account_name'      => $journal['source_account_name'],
                    'destination_account_id'   => $journal['destination_account_id'],
                    'destination_account_name' => $journal['destination_account_name'],
                    'description'              => $journal['description'],
                    'transaction_group_id'     => $journal['transaction_group_id'],
                ];
            }
        }

        return $array;
    }

    /**
     * Sum of withdrawal journals in period for a set of tags, grouped per currency. Amounts are always negative.
     *
     * @throws FireflyException
     */
    public function sumExpenses(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $tags = null): array
    {
        throw new FireflyException(sprintf('%s is not yet implemented.', __METHOD__));
    }

    /**
     * Sum of income journals in period for a set of tags, grouped per currency. Amounts are always positive.
     *
     * @throws FireflyException
     */
    public function sumIncome(Carbon $start, Carbon $end, ?Collection $accounts = null, ?Collection $tags = null): array
    {
        throw new FireflyException(sprintf('%s is not yet implemented.', __METHOD__));
    }
}
