<?php

namespace Firefly\Helper\Controllers;

use Carbon\Carbon;
use Exception;
use Firefly\Exception\FireflyException;
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
     * @param CRI $categories
     * @param BRI $budgets
     * @param PRI $piggybanks
     * @param ARI $accounts
     */
    public function __construct(TJRI $journals, CRI $categories, BRI $budgets, PRI $piggybanks, ARI $accounts)
    {
        $this->_journals = $journals;
        $this->_categories = $categories;
        $this->_budgets = $budgets;
        $this->_piggybanks = $piggybanks;
        $this->_accounts = $accounts;
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
     * @param array $data
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
            $to = $this->_accounts->findAssetAccountById($data['account_id']);
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
        $catids = is_null($category) ? [] : [$category->id];
        $components = array_merge($budgetids,$catids);
        $journal->components()->sync($components);
        $journal->save();

        if (isset($data['return_journal']) && $data['return_journal'] == true) {
            return $journal;
        }
        return $journal->errors();

    }

    /**
     * Returns messages about the validation.
     *
     * @param array $data
     * @return array
     * @throws FireflyException
     */
    public function validate(array $data)
    {
        $errors = new MessageBag;
        $warnings = new MessageBag;
        $successes = new MessageBag;

        /*
         * Description:
         */
        if (strlen($data['description']) == 0) {
            $errors->add('description', 'The description should not be this short.');
        }
        if (strlen($data['description']) > 250) {
            $errors->add('description', 'The description should not be this long.');
        }

        /*
         * Amount
         */
        if (floatval($data['amount']) <= 0) {
            $errors->add('amount', 'The amount cannot be zero or less than zero.');
        }
        if (floatval($data['amount']) > 10000) {
            $warnings->add('amount', 'OK, but that\'s a lot of money dude.');
        }

        /*
         * Date
         */
        try {
            $date = new Carbon($data['date']);
        } catch (Exception $e) {
            $errors->add('date', 'The date entered was invalid');
        }
        if (strlen($data['date']) == 0) {
            $errors->add('date', 'The date entered was invalid');
        }
        if (!$errors->has('date')) {
            $successes->add('date', 'OK!');
        }

        /*
         * Category
         */
        $category = $this->_categories->findByName($data['category']);
        if (strlen($data['category']) == 0) {
            $warnings->add('category', 'No category will be created.');
        } else {
            if (is_null($category)) {
                $warnings->add('category', 'Will have to be created.');
            } else {
                $successes->add('category', 'OK!');
            }
        }

        switch ($data['what']) {
            default:
                throw new FireflyException('Cannot validate a ' . $data['what']);
                break;
            case 'deposit':
                /*
                 * Tests for deposit
                 */
                // asset account
                $accountId = isset($data['account_id']) ? intval($data['account_id']) : 0;
                $account = $this->_accounts->find($accountId);
                if (is_null($account)) {
                    $errors->add('account_id', 'Cannot find this asset account.');
                } else {
                    $successes->add('account_id', 'OK!');
                }

                // revenue account:
                if (strlen($data['revenue_account']) == 0) {
                    $warnings->add('revenue_account', 'Revenue account will be "cash".');
                } else {
                    $exp = $this->_accounts->findRevenueAccountByName($data['revenue_account'], false);
                    if (is_null($exp)) {
                        $warnings->add('revenue_account', 'Expense account will be created.');
                    } else {
                        $successes->add('revenue_account', 'OK!');
                    }
                }

                break;
            case 'transfer':
                // account from
                $accountId = isset($data['account_from_id']) ? intval($data['account_from_id']) : 0;
                $account = $this->_accounts->find($accountId);
                if (is_null($account)) {
                    $errors->add('account_from_id', 'Cannot find this asset account.');
                } else {
                    $successes->add('account_from_id', 'OK!');
                }
                unset($accountId);
                // account to
                $accountId = isset($data['account_to_id']) ? intval($data['account_to_id']) : 0;
                $account = $this->_accounts->find($accountId);
                if (is_null($account)) {
                    $errors->add('account_to_id', 'Cannot find this asset account.');
                } else {
                    $successes->add('account_to_id', 'OK!');
                }
                unset($accountId);

                // piggy bank
                $piggybankId = isset($data['piggybank_id']) ? intval($data['piggybank_id']) : 0;
                $piggybank = $this->_piggybanks->find($piggybankId);
                if (is_null($piggybank)) {
                    $warnings->add('piggybank_id', 'No piggy bank will be modified.');
                } else {
                    $successes->add('piggybank_id', 'OK!');
                }

                break;
            case 'withdrawal':
                /*
                 * Tests for withdrawal
                 */
                // asset account
                $accountId = isset($data['account_id']) ? intval($data['account_id']) : 0;
                $account = $this->_accounts->find($accountId);
                if (is_null($account)) {
                    $errors->add('account_id', 'Cannot find this asset account.');
                } else {
                    $successes->add('account_id', 'OK!');
                }

                // expense account
                if (strlen($data['expense_account']) == 0) {
                    $warnings->add('expense_account', 'Expense account will be "cash".');
                } else {
                    $exp = $this->_accounts->findExpenseAccountByName($data['expense_account'], false);
                    if (is_null($exp)) {
                        $warnings->add('expense_account', 'Expense account will be created.');
                    } else {
                        $successes->add('expense_account', 'OK!');
                    }
                }

                // budget
                if (!isset($data['budget_id']) || (isset($data['budget_id']) && intval($data['budget_id']) == 0)) {
                    $warnings->add('budget_id', 'No budget selected.');
                } else {
                    $budget = $this->_budgets->find(intval($data['budget_id']));
                    if (is_null($budget)) {
                        $errors->add('budget_id', 'This budget does not exist');
                    } else {
                        $successes->add('budget_id', 'OK!');
                    }
                }

                break;
        }

        if (count($errors->get('description')) == 0) {
            $successes->add('description', 'OK!');
        }

        if (count($errors->get('amount')) == 0) {
            $successes->add('amount', 'OK!');
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
        /*
         * Tests for deposit
         */
        /*
         * Tests for transfer
         */

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
            $to = $this->_accounts->findAssetAccountById($data['account_id']);
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

        /*
         * Trigger recurring transaction event.
         */
        \Event::fire('journals.store',[$journal]);

        if (isset($data['return_journal']) && $data['return_journal'] == true) {
            return ['journal' => $journal, 'messagebag' => $journal->errors()];
        }
        return $journal->errors();
    }

} 