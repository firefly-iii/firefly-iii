<?php

namespace Firefly\Helper\Controllers;

use Illuminate\Support\MessageBag;

/**
 * Class Transaction
 *
 * @package Firefly\Helper\Controllers
 */
class Transaction implements TransactionInterface
{

    /**
     * Store a full transaction journal and associated stuff
     *
     * @param array $data
     *
     * @return MessageBag
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function store(array $data)
    {
        /*
         * save journal using repository
         */
        /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $journals */
        $journals = \App::make('Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface');
        $journal  = $journals->store($data);

        /*
         * If invalid, return the message bag:
         */
        if (!$journal->validate()) {
            return $journal->errors();
        }

        /*
         * save budget using repository
         */
        if (isset($data['budget_id'])) {
            /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgets */
            $budgets = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');
            $budget  = $budgets->find($data['budget_id']);
        }

        /*
         * save category using repository
         */
        /** @var \Firefly\Storage\Category\CategoryRepositoryInterface $categories */
        $categories = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');
        $category   = $categories->firstOrCreate($data['category']);

        /*
         * save accounts using repositories
         * this depends on the kind of transaction and i've yet to fix this.
         */
        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accounts */
        $accounts = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        if (isset($data['account_id'])) {
            $from = $accounts->findAssetAccountById($data['account_id']);
        }
        if (isset($data['expense_account'])) {
            $to = $accounts->findExpenseAccountByName($data['expense_account']);
        }
        if (isset($data['revenue_account'])) {
            $from = $accounts->findRevenueAccountByName($data['revenue_account']);
            $to   = $accounts->findAssetAccountById($data['account_id']);
        }
        if (isset($data['account_from_id'])) {
            $from = $accounts->findAssetAccountById($data['account_from_id']);
        }
        if (isset($data['account_to_id'])) {
            $to = $accounts->findAssetAccountById($data['account_to_id']);
        }
        /*
         * Add a custom error when they are the same.
         */
        if($to->id == $from->id) {
            $bag = new MessageBag;
            $bag->add('account_from_id','The account from cannot be the same as the account to.');
            return $bag;
        }

        /*
         * save transactions using repository.
         */
        $one = $journals->saveTransaction($journal, $from, floatval($data['amount']) * -1);
        $two = $journals->saveTransaction($journal, $to, floatval($data['amount']));
        /*
         * Count for $journal is zero? Then there were errors!
         */
        if ($journal->transactions()->count() < 2) {
            /*
             * Join message bags and return them:
             */
            $bag = $one->errors();
            $bag->merge($two->errors());
            return $bag;
        }

        /*
         * Connect budget and category:
         */
        if (isset($budget) && !is_null($budget)) {
            $journal->budgets()->save($budget);
        }
        if (!is_null($category)) {
            $journal->categories()->save($category);
        }
        $journal->completed = true;
        $journal->save();
        return $journal->errors();
    }

} 