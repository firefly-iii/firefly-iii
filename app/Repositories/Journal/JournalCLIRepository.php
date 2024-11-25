<?php

/**
 * JournalCLIRepository.php
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

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class JournalCLIRepository
 */
class JournalCLIRepository implements JournalCLIRepositoryInterface
{
    /**
     * Get all transaction journals with a specific type, regardless of user.
     */
    public function getAllJournals(array $types): Collection
    {
        return TransactionJournal::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereIn('transaction_types.type', $types)
            ->with(['user', 'transactionType', 'transactionCurrency', 'transactions', 'transactions.account'])
            ->get(['transaction_journals.*'])
        ;
    }

    /**
     * Return the ID of the budget linked to the journal (if any) or the transactions (if any).
     */
    public function getJournalBudgetId(TransactionJournal $journal): int
    {
        $budget = $journal->budgets()->first();
        if (null !== $budget) {
            return $budget->id;
        }
        $budget = $journal->transactions()->first()->budgets()->first();
        if (null !== $budget) {
            return $budget->id;
        }

        return 0;
    }

    /**
     * Return the ID of the category linked to the journal (if any) or to the transactions (if any).
     */
    public function getJournalCategoryId(TransactionJournal $journal): int
    {
        $category = $journal->categories()->first();
        if (null !== $category) {
            return $category->id;
        }
        $category = $journal->transactions()->first()->categories()->first();
        if (null !== $category) {
            return $category->id;
        }

        return 0;
    }

    /**
     * Return all journals without a group, used in an upgrade routine.
     */
    public function getJournalsWithoutGroup(): array
    {
        return TransactionJournal::whereNull('transaction_group_id')->get(['id', 'user_id'])->toArray();
    }

    /**
     * Return Carbon value of a meta field (or NULL).
     */
    public function getMetaDate(TransactionJournal $journal, string $field): ?Carbon
    {
        $cache = new CacheProperties();
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journal->id);
        $cache->addProperty($field);

        if ($cache->has()) {
            return new Carbon($cache->get());
        }

        $entry = $journal->transactionJournalMeta()->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }
        $value = new Carbon($entry->data);
        $cache->store($value);

        return $value;
    }

    /**
     * Return value of a meta field (or NULL) as a string.
     */
    public function getMetaField(TransactionJournal $journal, string $field): ?string
    {
        $cache  = new CacheProperties();
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journal->id);
        $cache->addProperty($field);

        if ($cache->has()) {
            return $cache->get();
        }

        $entry  = $journal->transactionJournalMeta()->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }

        $value  = $entry->data;

        if (is_array($value)) {
            $return = implode(',', $value);
            $cache->store($return);

            return $return;
        }

        // return when something else:
        $return = (string)$value;
        $cache->store($return);

        return $return;
    }

    /**
     * Return text of a note attached to journal, or NULL
     */
    public function getNoteText(TransactionJournal $journal): ?string
    {
        $note = $journal->notes()->first();
        if (null === $note) {
            return null;
        }

        return $note->text;
    }

    /**
     * Returns all journals with more than 2 transactions. Should only return empty collections
     * in Firefly III > v4.8,0.
     */
    public function getSplitJournals(): Collection
    {
        $query      = TransactionJournal::leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->groupBy('transaction_journals.id')
        ;
        $result     = $query->get(['transaction_journals.id as id', \DB::raw('count(transactions.id) as transaction_count')]); // @phpstan-ignore-line
        $journalIds = [];

        /** @var \stdClass $row */
        foreach ($result as $row) {
            if ((int)$row->transaction_count > 2) {
                $journalIds[] = (int)$row->id;
            }
        }
        $journalIds = array_unique($journalIds);

        return TransactionJournal::with(['transactions'])
            ->whereIn('id', $journalIds)->get()
        ;
    }

    /**
     * Return all tags as strings in an array.
     */
    public function getTags(TransactionJournal $journal): array
    {
        return $journal->tags()->get()->pluck('tag')->toArray();
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        // empty
    }
}
