<?php

/**
 * JournalRepository.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class JournalRepository.
 */
class JournalRepository implements JournalRepositoryInterface
{
    private User $user;

    public function destroyGroup(TransactionGroup $transactionGroup): void
    {
        /** @var TransactionGroupDestroyService $service */
        $service = app(TransactionGroupDestroyService::class);
        $service->destroy($transactionGroup);
    }

    public function destroyJournal(TransactionJournal $journal): void
    {
        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        $service->destroy($journal);
    }

    public function findByType(array $types): Collection
    {
        return $this->user
            ->transactionJournals()
            ->leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereIn('transaction_types.type', $types)
            ->get(['transaction_journals.*'])
        ;
    }

    /**
     * Get users first transaction journal or NULL.
     */
    public function firstNull(): ?TransactionJournal
    {
        /** @var null|TransactionJournal $entry */
        $entry  = $this->user->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);
        $result = null;
        if (null !== $entry) {
            $result = $entry;
        }

        return $result;
    }

    public function getDestinationAccount(TransactionJournal $journal): Account
    {
        /** @var null|Transaction $transaction */
        $transaction = $journal->transactions()->with('account')->where('amount', '>', 0)->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Your administration is broken. Transaction journal #%d has no destination transaction.', $journal->id));
        }

        return $transaction->account;
    }

    /**
     * Return total amount of journal. Is always positive.
     */
    public function getJournalTotal(TransactionJournal $journal): string
    {
        $cache  = new CacheProperties();
        $cache->addProperty($journal->id);
        $cache->addProperty('amount-positive');
        if ($cache->has()) {
            return $cache->get();
        }

        // saves on queries:
        $amount = $journal->transactions()->where('amount', '>', 0)->get()->sum('amount');
        $amount = (string) $amount;
        $cache->store($amount);

        return $amount;
    }

    public function getLast(): ?TransactionJournal
    {
        /** @var null|TransactionJournal $entry */
        $entry  = $this->user->transactionJournals()->orderBy('date', 'DESC')->first(['transaction_journals.*']);
        $result = null;
        if (null !== $entry) {
            $result = $entry;
        }

        return $result;
    }

    public function getLinkNoteText(TransactionJournalLink $link): string
    {
        /** @var null|Note $note */
        $note = $link->notes()->first();

        return (string) $note?->text;
    }

    /**
     * Return Carbon value of a meta field (or NULL).
     */
    public function getMetaDateById(int $journalId, string $field): ?Carbon
    {
        $cache = new CacheProperties();
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journalId);
        $cache->addProperty($field);

        if ($cache->has()) {
            return new Carbon($cache->get());
        }
        $entry = TransactionJournalMeta::where('transaction_journal_id', $journalId)
            ->where('name', $field)->first()
        ;
        if (null === $entry) {
            return null;
        }
        $value = new Carbon($entry->data);
        $cache->store($entry->data);

        return $value;
    }

    public function getSourceAccount(TransactionJournal $journal): Account
    {
        /** @var null|Transaction $transaction */
        $transaction = $journal->transactions()->with('account')->where('amount', '<', 0)->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Your administration is broken. Transaction journal #%d has no source transaction.', $journal->id));
        }

        return $transaction->account;
    }

    public function reconcileById(int $journalId): void
    {
        /** @var null|TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->find($journalId);
        $journal?->transactions()->update(['reconciled' => true]);
    }

    /**
     * Find a specific journal.
     */
    public function find(int $journalId): ?TransactionJournal
    {
        /** @var TransactionJournal|null */
        return $this->user->transactionJournals()->find($journalId);
    }

    /**
     * Search in journal descriptions.
     */
    public function searchJournalDescriptions(string $search, int $limit): Collection
    {
        $query = $this->user->transactionJournals()
            ->orderBy('date', 'DESC')
        ;
        if ('' !== $search) {
            $query->whereLike('description', sprintf('%%%s%%', $search));
        }

        return $query->take($limit)->get();
    }

    public function setUser(null|Authenticatable|User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    public function unreconcileById(int $journalId): void
    {
        /** @var null|TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->find($journalId);
        $journal?->transactions()->update(['reconciled' => false]);
    }

    /**
     * Update budget for a journal.
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);

        $service->setTransactionJournal($journal);
        $service->setData(
            [
                'budget_id' => $budgetId,
            ]
        );
        $service->update();
        $journal->refresh();

        return $journal;
    }

    /**
     * Update category for a journal.
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->setTransactionJournal($journal);
        $service->setData(
            [
                'category_name' => $category,
            ]
        );
        $service->update();
        $journal->refresh();

        return $journal;
    }

    /**
     * Update tag(s) for a journal.
     */
    public function updateTags(TransactionJournal $journal, array $tags): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->setTransactionJournal($journal);
        $service->setData(
            [
                'tags' => $tags,
            ]
        );
        $service->update();
        $journal->refresh();

        return $journal;
    }
}
