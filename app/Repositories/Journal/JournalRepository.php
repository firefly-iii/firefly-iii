<?php
/**
 * JournalRepository.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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
use Illuminate\Support\Collection;
use Log;

/**
 * Class JournalRepository.
 */
class JournalRepository implements JournalRepositoryInterface
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
        }
    }

    /**
     * Search in journal descriptions.
     *
     * @param string $search
     * @return Collection
     */
    public function searchJournalDescriptions(string $search): Collection
    {
        $query = $this->user->transactionJournals()
                            ->orderBy('date', 'DESC');
        if ('' !== $query) {
            $query->where('description', 'LIKE', sprintf('%%%s%%', $search));
        }

        return $query->get();
    }

    /**
     * @param TransactionGroup $transactionGroup
     *
     */
    public function destroyGroup(TransactionGroup $transactionGroup): void
    {
        /** @var TransactionGroupDestroyService $service */
        $service = app(TransactionGroupDestroyService::class);
        $service->destroy($transactionGroup);
    }

    /**
     * @param TransactionJournal $journal
     *
     */
    public function destroyJournal(TransactionJournal $journal): void
    {
        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        $service->destroy($journal);
    }

    /**
     * Find a journal by its hash.
     *
     * @param string $hash
     *
     * @return TransactionJournalMeta|null
     */
    public function findByHash(string $hash): ?TransactionJournalMeta
    {
        $jsonEncode = json_encode($hash);
        $hashOfHash = hash('sha256', $jsonEncode);
        Log::debug(sprintf('JSON encoded hash is: %s', $jsonEncode));
        Log::debug(sprintf('Hash of hash is: %s', $hashOfHash));

        $result = TransactionJournalMeta::withTrashed()
                                        ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
                                        ->where('hash', $hashOfHash)
                                        ->where('name', 'import_hash_v2')
                                        ->first(['journal_meta.*']);
        if (null === $result) {
            Log::debug('Result is null');
        }

        return $result;
    }

    /**
     * Find a specific journal.
     *
     * @param int $journalId
     *
     * @return TransactionJournal|null
     */
    public function findNull(int $journalId): ?TransactionJournal
    {
        return $this->user->transactionJournals()->where('id', $journalId)->first();
    }

    /**
     * Get users first transaction journal or NULL.
     *
     * @return TransactionJournal|null
     */
    public function firstNull(): ?TransactionJournal
    {
        /** @var TransactionJournal $entry */
        $entry  = $this->user->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);
        $result = null;
        if (null !== $entry) {
            $result = $entry;
        }

        return $result;
    }

    /**
     * Return a list of all destination accounts related to journal.
     *
     * @param TransactionJournal $journal
     * @param bool $useCache
     *
     * @return Collection
     */
    public function getJournalDestinationAccounts(TransactionJournal $journal, bool $useCache = true): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('destination-account-list');
        if ($useCache && $cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $journal->transactions()->where('amount', '>', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $list = $list->unique('id');
        $cache->store($list);

        return $list;
    }

    /**
     * Return a list of all source accounts related to journal.
     *
     * @param TransactionJournal $journal
     * @param bool $useCache
     *
     * @return Collection
     */
    public function getJournalSourceAccounts(TransactionJournal $journal, bool $useCache = true): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('source-account-list');
        if ($useCache && $cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $journal->transactions()->where('amount', '<', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $list = $list->unique('id');
        $cache->store($list);

        return $list;
    }

    /**
     * Return total amount of journal. Is always positive.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getJournalTotal(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('amount-positive');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        // saves on queries:
        $amount = $journal->transactions()->where('amount', '>', 0)->get()->sum('amount');
        $amount = (string)$amount;
        $cache->store($amount);

        return $amount;
    }

    /**
     * @param TransactionJournalLink $link
     *
     * @return string
     */
    public function getLinkNoteText(TransactionJournalLink $link): string
    {
        $notes = null;
        /** @var Note $note */
        $note = $link->notes()->first();
        if (null !== $note) {
            return $note->text ?? '';
        }

        return '';
    }









    /**
     * @param int $transactionId
     */
    public function reconcileById(int $journalId): void
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->find($journalId);
        if (null !== $journal) {
            $journal->transactions()->update(['reconciled' => true]);
        }
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Update budget for a journal.
     *
     * @param TransactionJournal $journal
     * @param int $budgetId
     *
     * @return TransactionJournal
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
     *
     * @param TransactionJournal $journal
     * @param string $category
     *
     * @return TransactionJournal
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
     *
     * @param TransactionJournal $journal
     * @param array $tags
     *
     * @return TransactionJournal
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

    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param int    $journalId
     * @param string $field
     *
     * @return null|Carbon
     */
    public function getMetaDateById(int $journalId, string $field): ?Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journalId);
        $cache->addProperty($field);

        if ($cache->has()) {
            return new Carbon($cache->get()); // @codeCoverageIgnore
        }
        $entry = TransactionJournalMeta::where('transaction_journal_id', $journalId)
                                       ->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }
        $value = new Carbon($entry->data);
        $cache->store($entry->data);

        return $value;
    }
}
