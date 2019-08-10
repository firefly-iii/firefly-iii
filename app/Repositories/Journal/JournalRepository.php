<?php
/**
 * JournalRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Journal;

use Carbon\Carbon;
use DB;
use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Destroy\TransactionGroupDestroyService;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use stdClass;

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
     * Return the ID of the budget linked to the journal (if any) or the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getJournalBudgetId(TransactionJournal $journal): int
    {
        $budget = $journal->budgets()->first();
        if (null !== $budget) {
            return $budget->id;
        }
        /** @noinspection NullPointerExceptionInspection */
        $budget = $journal->transactions()->first()->budgets()->first();
        if (null !== $budget) {
            return $budget->id;
        }

        return 0;
    }

    /**
     * Return the ID of the category linked to the journal (if any) or to the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getJournalCategoryId(TransactionJournal $journal): int
    {
        $category = $journal->categories()->first();
        if (null !== $category) {
            return $category->id;
        }
        /** @noinspection NullPointerExceptionInspection */
        $category = $journal->transactions()->first()->categories()->first();
        if (null !== $category) {
            return $category->id;
        }

        return 0;
    }

    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param TransactionJournal $journal
     * @param string $field
     *
     * @return null|Carbon
     */
    public function getMetaDate(TransactionJournal $journal, string $field): ?Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journal->id);
        $cache->addProperty($field);

        if ($cache->has()) {
            return new Carbon($cache->get()); // @codeCoverageIgnore
        }

        $entry = $journal->transactionJournalMeta()->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }
        $value = new Carbon($entry->data);
        $cache->store($entry->data);

        return $value;
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
     * Return all journals without a group, used in an upgrade routine.
     *
     * @return array
     */
    public function getJournalsWithoutGroup(): array
    {
        return TransactionJournal::whereNull('transaction_group_id')->get(['id', 'user_id'])->toArray();
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
     * Return value of a meta field (or NULL) as a string.
     *
     * @param TransactionJournal $journal
     * @param string $field
     *
     * @return null|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getMetaField(TransactionJournal $journal, string $field): ?string
    {
        $cache = new CacheProperties;
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journal->id);
        $cache->addProperty($field);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $entry = $journal->transactionJournalMeta()->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }

        $value = $entry->data;

        if (is_array($value)) {
            $return = implode(',', $value);
            $cache->store($return);

            return $return;
        }

        // return when something else:
        try {
            $return = (string)$value;
            $cache->store($return);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return '';
        }

        return $return;
    }

    /**
     * Return text of a note attached to journal, or NULL
     *
     * @param TransactionJournal $journal
     *
     * @return string|null
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
     * in Firefly III > v4.8.0.
     *
     * @return Collection
     */
    public function getSplitJournals(): Collection
    {
        $query      = TransactionJournal
            ::leftJoin('transactions', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
            ->groupBy('transaction_journals.id');
        $result     = $query->get(['transaction_journals.id as id', DB::raw('count(transactions.id) as transaction_count')]);
        $journalIds = [];
        /** @var stdClass $row */
        foreach ($result as $row) {
            if ((int)$row->transaction_count > 2) {
                $journalIds[] = (int)$row->id;
            }
        }
        $journalIds = array_unique($journalIds);

        return TransactionJournal
            ::with(['transactions'])
            ->whereIn('id', $journalIds)->get();
    }

    /**
     * Return all tags as strings in an array.
     *
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function getTags(TransactionJournal $journal): array
    {
        return $journal->tags()->get()->pluck('tag')->toArray();
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
     * Get all transaction journals with a specific type, regardless of user.
     *
     * @param array $types
     * @return Collection
     */
    public function getAllJournals(array $types): Collection
    {
        return TransactionJournal
            ::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
            ->whereIn('transaction_types.type', $types)
            ->with(['user', 'transactionType', 'transactionCurrency', 'transactions', 'transactions.account'])
            ->get(['transaction_journals.*']);
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
