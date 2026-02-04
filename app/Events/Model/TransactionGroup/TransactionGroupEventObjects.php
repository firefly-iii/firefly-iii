<?php

declare(strict_types=1);

namespace FireflyIII\Events\Model\TransactionGroup;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;

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
    public Collection $transactionJournals;

    public function __construct()
    {
        $this->accounts            = new Collection();
        $this->budgets             = new Collection();
        $this->categories          = new Collection();
        $this->tags                = new Collection();
        $this->transactionJournals = new Collection();
    }

    public static function collectFromTransactionGroup(TransactionGroup $transactionGroup): self
    {
        $object = new self();

        /** @var TransactionJournal $journal */
        foreach ($transactionGroup->transactionJournals as $journal) {
            $object->transactionJournals->push($journal);
            $object->budgets    = $object->tags->merge($journal->budgets);
            $object->categories = $object->tags->merge($journal->categories);
            $object->tags       = $object->tags->merge($journal->tags);

            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                $object->accounts->push($transaction->account);
            }
        }

        return $object;
    }
}
