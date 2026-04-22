<?php

declare(strict_types=1);

namespace FireflyIII\Events\Model\TransactionGroup;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * This class collects all objects before and after the creation, removal or updating
 * of a transaction group. The idea is that this class contains all relevant objects.
 * Right now, that means journals, tags, accounts, budgets and categories.
 *
 * By collecting these objects (in case of an update: before AND after update) there
 * is a unified set of objects to manage: update balances, recalculate credits, etc.
 */
class TransactionGroupEventObjects
{
    public Collection $accounts;
    public Collection $budgets;
    public Collection $categories;
    public Collection $tags;
    public Collection $transactionGroups;
    public Collection $transactionJournals;

    public function __construct()
    {
        $this->accounts            = new Collection();
        $this->budgets             = new Collection();
        $this->categories          = new Collection();
        $this->tags                = new Collection();
        $this->transactionGroups   = new Collection();
        $this->transactionJournals = new Collection();
    }

    public static function collectFromTransactionGroup(TransactionGroup $transactionGroup): self
    {
        Log::debug(sprintf('collectFromTransactionGroup(#%d)', $transactionGroup->id));
        $object = new self();
        $object->appendFromTransactionGroup($transactionGroup);

        return $object;
    }

    public function collectFromCollection(Collection $collection): void
    {
        Log::debug('Will now collect info from collection.');
        /** @var TransactionGroup|array $object */
        foreach ($collection as $object) {
            if ($object instanceof TransactionGroup) {
                Log::debug(sprintf('Added group #%d', $object->id));
                $this->appendFromTransactionGroup($object);
            }
            if (is_array($object) && array_key_exists('id', $object)) {
                // FIXME technically speaking not sure of this is the user's transaction group.
                $group = TransactionGroup::find((int)$object['id']);
                if (null !== $group) {
                    Log::debug(sprintf('Added group #%d', $group->id));
                    $this->appendFromTransactionGroup($group);
                }
            }
        }
    }

    public function appendFromTransactionGroup(TransactionGroup $transactionGroup): void
    {
        Log::debug(sprintf('Appended transaction group #%d', $transactionGroup->id));
        $this->transactionGroups->push($transactionGroup);

        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            Log::debug(sprintf('Appended transaction journal #%d', $journal->id));
            $this->transactionJournals->push($journal);
            $this->budgets    = $this->budgets->merge($journal->budgets);
            $this->categories = $this->categories->merge($journal->categories);
            $this->tags       = $this->tags->merge($journal->tags);

            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                Log::debug(sprintf('Appended account #%d', $transaction->account->id));
                $this->accounts->push($transaction->account);
            }
        }
        $this->transactionGroups   = $this->transactionGroups->unique('id');
        $this->transactionJournals = $this->transactionJournals->unique('id');
        $this->budgets             = $this->budgets->unique('id');
        $this->categories          = $this->categories->unique('id');
        $this->tags                = $this->tags->unique('id');
        $this->accounts            = $this->accounts->unique('id');
    }
}
