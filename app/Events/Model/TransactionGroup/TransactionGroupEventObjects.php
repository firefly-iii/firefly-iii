<?php

namespace FireflyIII\Events\Model\TransactionGroup;

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

    public function __construct() {
        $this->accounts = new Collection();
        $this->budgets =  new Collection();
        $this->categories = new Collection();
        $this->tags =new Collection();
        $this->transactionJournals = new Collection();
    }
}
