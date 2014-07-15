<?php


use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as Bud;
use Firefly\Storage\Category\CategoryRepositoryInterface as Cat;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

/**
 * Class TransactionController
 */
class TransactionController extends BaseController
{

    protected $_accounts;
    protected $_budgets;
    protected $_categories;
    protected $_journal;

    /**
     * @param ARI  $accounts
     * @param Bud  $budgets
     * @param Cat  $categories
     * @param TJRI $journal
     */
    public function __construct(ARI $accounts, Bud $budgets, Cat $categories, TJRI $journal)
    {
        $this->_accounts = $accounts;
        $this->_budgets = $budgets;
        $this->_categories = $categories;
        $this->_journal = $journal;


        View::share('menu', 'home');
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function createWithdrawal()
    {

        // get accounts with names and id's.
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        $budgets = $this->_budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.withdrawal')->with('accounts', $accounts)->with('budgets', $budgets);
    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function createDeposit()
    {
        // get accounts with names and id's.
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        $budgets = $this->_budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.deposit')->with('accounts', $accounts)->with('budgets', $budgets);

    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function createTransfer()
    {
        // get accounts with names and id's.
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        $budgets = $this->_budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.transfer')->with('accounts', $accounts)->with('budgets', $budgets);

    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCreateWithdrawal()
    {

        // create or find beneficiary:
        $beneficiary = $this->_accounts->createOrFindBeneficiary(Input::get('beneficiary'));

        // fall back to cash account if empty:
        if (is_null($beneficiary)) {
            $beneficiary = $this->_accounts->getCashAccount();
        }

        // create or find category:
        $category = $this->_categories->createOrFind(Input::get('category'));

        // find budget:
        $budget = $this->_budgets->find(intval(Input::get('budget_id')));

        // find account:
        $account = $this->_accounts->find(intval(Input::get('account_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->_journal->createSimpleJournal($account, $beneficiary, $description, $amount, $date);
        } catch (\Firefly\Exception\FireflyException $e) {
            return Redirect::route('transactions.withdrawal')->withInput();
        }

        // attach bud/cat (?)
        if (!is_null($budget)) {
            $journal->budgets()->save($budget);
        }
        if (!is_null($category)) {
            $journal->categories()->save($category);
        }

        Session::flash('success', 'Transaction saved');
        return Redirect::route('index');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCreateDeposit()
    {
        // create or find beneficiary:
        $beneficiary = $this->_accounts->createOrFindBeneficiary(Input::get('beneficiary'));

        // fall back to cash account if empty:
        if (is_null($beneficiary)) {
            $beneficiary = $this->_accounts->getCashAccount();
        }

        // create or find category:
        $category = $this->_categories->createOrFind(Input::get('category'));

        // find account:
        $account = $this->_accounts->find(intval(Input::get('account_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->_journal->createSimpleJournal($beneficiary, $account, $description, $amount, $date);
        } catch (\Firefly\Exception\FireflyException $e) {
            return Redirect::route('transactions.deposit')->withInput();
        }

        if (!is_null($category)) {
            $journal->categories()->save($category);
        }

        Session::flash('success', 'Transaction saved');
        return Redirect::route('index');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postCreateTransfer()
    {
        // create or find category:
        $category = $this->_categories->createOrFind(Input::get('category'));

        // find account to:
        $toAccount = $this->_accounts->find(intval(Input::get('account_to_id')));

        // find account from
        $from = $this->_accounts->find(intval(Input::get('account_from_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->_journal->createSimpleJournal($from, $toAccount, $description, $amount, $date);
        } catch (\Firefly\Exception\FireflyException $e) {
            return Redirect::route('transactions.transfer')->withInput();
        }
        if (!is_null($category)) {
            $journal->categories()->save($category);
        }

        Session::flash('success', 'Transaction saved');
        return Redirect::route('index');
    }

} 