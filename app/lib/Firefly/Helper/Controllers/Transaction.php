<?php

namespace Firefly\Helper\Controllers;

use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as BRI;
use Firefly\Storage\Category\CategoryRepositoryInterface as CRI;
use Firefly\Storage\Piggybank\PiggybankRepositoryInterface as PRI;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;
use Illuminate\Support\MessageBag;

/**
 * Class Transaction
 *
 * @package Firefly\Helper\Controllers
 */
class Transaction implements TransactionInterface
{
    protected $_user = null;

    /** @var \Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface $_journals */
    protected $_journals;

    /** @var \Firefly\Storage\Category\CategoryRepositoryInterface $_categories */
    protected $_categories;

    /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $_budgets */
    protected $_budgets;

    /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $_piggybanks */
    protected $_piggybanks;

    /** @var \Firefly\Storage\Account\AccountRepositoryInterface $_accounts */
    protected $_accounts;


    /**
     * @param TJRI $journals
     * @param CRI  $categories
     * @param BRI  $budgets
     * @param PRI  $piggybanks
     * @param ARI  $accounts
     */
    public function __construct(TJRI $journals, CRI $categories, BRI $budgets, PRI $piggybanks, ARI $accounts)
    {
        $this->_journals   = $journals;
        $this->_categories = $categories;
        $this->_budgets    = $budgets;
        $this->_piggybanks = $piggybanks;
        $this->_accounts   = $accounts;
        $this->overruleUser(\Auth::user());
    }

    /**
     * @param \User $user
     *
     * @return mixed|void
     */
    public function overruleUser(\User $user)
    {
        $this->_user = $user;
        $this->_journals->overruleUser($user);
        $this->_categories->overruleUser($user);
        $this->_budgets->overruleUser($user);
        $this->_piggybanks->overruleUser($user);
        $this->_accounts->overruleUser($user);
        return true;
    }

    /**
     * @param \TransactionJournal $journal
     * @param array               $data
     *
     * @return MessageBag|\TransactionJournal
     */
    public function update(\TransactionJournal $journal, array $data)
    {
        /*
         * Update the journal using the repository.
         */
        $journal = $this->_journals->update($journal, $data);

        /*
         * If invalid, return the message bag:
         */
        if (!$journal->validate()) {
            return $journal->errors();
        }

        /*
         * find budget using repository
         */
        if (isset($data['budget_id'])) {
            $budget = $this->_budgets->find($data['budget_id']);
        }

        /*
         * find category using repository
         */
        $category = $this->_categories->firstOrCreate($data['category']);

        /*
         * Find piggy bank using repository:
         */
        $piggybank = null;
        if (isset($data['piggybank_id'])) {
            $piggybank = $this->_piggybanks->find($data['piggybank_id']);
        }

        /*
         * save accounts using repositories
         * this depends on the kind of transaction and i've yet to fix this.
         */

        if (isset($data['account_id'])) {
            $from = $this->_accounts->findAssetAccountById($data['account_id']);
        }
        if (isset($data['expense_account'])) {
            $to = $this->_accounts->findExpenseAccountByName($data['expense_account']);
        }
        if (isset($data['revenue_account'])) {
            $from = $this->_accounts->findRevenueAccountByName($data['revenue_account']);
            $to   = $this->_accounts->findAssetAccountById($data['account_id']);
        }
        if (isset($data['account_from_id'])) {
            $from = $this->_accounts->findAssetAccountById($data['account_from_id']);
        }
        if (isset($data['account_to_id'])) {
            $to = $this->_accounts->findAssetAccountById($data['account_to_id']);
        }


        /*
         * Add a custom error when they are the same.
         */
        if ($to->id == $from->id) {
            $bag = new MessageBag;
            $bag->add('account_from_id', 'The account from cannot be the same as the account to.');
            return $bag;
        }

        /*
         * Check if the transactions need new data:
         */
        $transactions = $journal->transactions()->orderBy('amount', 'ASC')->get();
        /** @var \Transaction $transaction */
        foreach ($transactions as $index => $transaction) {
            switch (true) {
                case ($index == 0): // FROM account
                    $transaction->account()->associate($from);
                    $transaction->amount = floatval($data['amount']) * -1;
                    break;
                case ($index == 1): // TO account.
                    $transaction->account()->associate($to);
                    $transaction->amount = floatval($data['amount']);
                    break;
            }
            $transaction->save();
            // either way, try to attach the piggy bank:
            if (!is_null($piggybank)) {
                if ($piggybank->account_id == $transaction->account_id) {
                    $transaction->piggybank()->associate($piggybank);
                }
            }
        }

        /*
         * Connect budget and category:
         */
        $budgetids = !isset($budget) || (isset($budget) && is_null($budget)) ? [] : [$budget->id];
        $catids    = is_null($category) ? [] : [$category->id];
        $journal->budgets()->sync($budgetids);
        $journal->categories()->sync($catids);
        $journal->save();
        if (isset($data['return_journal']) && $data['return_journal'] == true) {
            return $journal;
        }
        return $journal->errors();

    }

    /**
     * Store a full transaction journal and associated stuff
     *
     * @param array $data
     *
     * @return MessageBag|\TransactionJournal
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     */
    public function store(array $data)
    {
        /*
         * save journal using repository
         */
        $journal = $this->_journals->store($data);

        /*
         * If invalid, return the message bag:
         */
        if (!$journal->validate()) {
            return $journal->errors();
        }

        /*
         * find budget using repository
         */
        if (isset($data['budget_id'])) {
            $budget = $this->_budgets->find($data['budget_id']);
        }

        /*
         * find category using repository
         */
        $category = $this->_categories->firstOrCreate($data['category']);

        /*
         * Find piggy bank using repository:
         */
        $piggybank = null;
        if (isset($data['piggybank_id'])) {
            $piggybank = $this->_piggybanks->find($data['piggybank_id']);
        }

        /*
         * save accounts using repositories
         * this depends on the kind of transaction and i've yet to fix this.
         */
        if (isset($data['account_id'])) {
            $from = $this->_accounts->findAssetAccountById($data['account_id']);
        }
        if (isset($data['expense_account'])) {
            $to = $this->_accounts->findExpenseAccountByName($data['expense_account']);

        }
        if (isset($data['revenue_account'])) {
            $from = $this->_accounts->findRevenueAccountByName($data['revenue_account']);
            $to   = $this->_accounts->findAssetAccountById($data['account_id']);
        }
        if (isset($data['account_from_id'])) {
            $from = $this->_accounts->findAssetAccountById($data['account_from_id']);
        }
        if (isset($data['account_to_id'])) {
            $to = $this->_accounts->findAssetAccountById($data['account_to_id']);
        }

        /*
         * Add a custom error when they are the same.
         */
        if ($to->id ==
            $from->id) {
            $bag = new MessageBag;
            $bag->add('account_from_id', 'The account from cannot be the same as the account to.');
            return $bag;
        }

        /*
         * Save transactions using repository. We try to connect the (possibly existing)
         * piggy bank to either transaction, knowing it will only work with one of them.
         */
        /** @var \Transaction $one */
        $one = $this->_journals->saveTransaction($journal, $from, floatval($data['amount']) * -1);
        $one->connectPiggybank($piggybank);
        $two = $this->_journals->saveTransaction($journal, $to, floatval($data['amount']));
        $two->connectPiggybank($piggybank);
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
         * Connect budget, category and piggy bank:
         */
        if (isset($budget) && !is_null($budget)) {
            $journal->budgets()->save($budget);
        }
        if (!is_null($category)) {
            $journal->categories()->save($category);
        }
        $journal->completed = true;
        $journal->save();
        if (isset($data['return_journal']) && $data['return_journal'] == true) {
            return $journal;
        }
        return $journal->errors();
    }

} 