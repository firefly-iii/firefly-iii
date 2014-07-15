<?php


use Firefly\Storage\Account\AccountRepositoryInterface as ARI;
use Firefly\Storage\Budget\BudgetRepositoryInterface as Bud;
use Firefly\Storage\Category\CategoryRepositoryInterface as Cat;
use Firefly\Storage\TransactionJournal\TransactionJournalRepositoryInterface as TJRI;

class TransactionController extends BaseController
{

    protected $accounts;
    protected $budgets;
    protected $categories;
    protected $tj;

    public function __construct(ARI $accounts, Bud $budgets, Cat $categories, TJRI $tj)
    {
        $this->accounts = $accounts;
        $this->budgets = $budgets;
        $this->categories = $categories;
        $this->tj = $tj;


        View::share('menu', 'home');
    }

    public function createWithdrawal()
    {

        // get accounts with names and id's.
        $accounts = $this->accounts->getActiveDefaultAsSelectList();

        $budgets = $this->budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.withdrawal')->with('accounts', $accounts)->with('budgets', $budgets);
    }

    public function createDeposit()
    {
        // get accounts with names and id's.
        $accounts = $this->accounts->getActiveDefaultAsSelectList();

        $budgets = $this->budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.deposit')->with('accounts', $accounts)->with('budgets', $budgets);

    }

    public function createTransfer()
    {
        // get accounts with names and id's.
        $accounts = $this->accounts->getActiveDefaultAsSelectList();

        $budgets = $this->budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.transfer')->with('accounts', $accounts)->with('budgets', $budgets);

    }

    public function postCreateWithdrawal()
    {

        // create or find beneficiary:
        $beneficiary = $this->accounts->createOrFindBeneficiary(Input::get('beneficiary'));

        // fall back to cash account if empty:
        if (is_null($beneficiary)) {
            $beneficiary = $this->accounts->getCashAccount();
        }

        // create or find category:
        $category = $this->categories->createOrFind(Input::get('category'));

        // find budget:
        $budget = $this->budgets->find(intval(Input::get('budget_id')));

        // find account:
        $account = $this->accounts->find(intval(Input::get('account_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->tj->createSimpleJournal($account, $beneficiary, $description, $amount, $date);
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

    public function postCreateDeposit()
    {
        // create or find beneficiary:
        $beneficiary = $this->accounts->createOrFindBeneficiary(Input::get('beneficiary'));

        // fall back to cash account if empty:
        if (is_null($beneficiary)) {
            $beneficiary = $this->accounts->getCashAccount();
        }

        // create or find category:
        $category = $this->categories->createOrFind(Input::get('category'));

        // find account:
        $account = $this->accounts->find(intval(Input::get('account_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->tj->createSimpleJournal($beneficiary, $account, $description, $amount, $date);
        } catch (\Firefly\Exception\FireflyException $e) {
            return Redirect::route('transactions.deposit')->withInput();
        }

        if (!is_null($category)) {
            $journal->categories()->save($category);
        }

        Session::flash('success', 'Transaction saved');
        return Redirect::route('index');
    }

    public function postCreateTransfer()
    {
        // create or find category:
        $category = $this->categories->createOrFind(Input::get('category'));

        // find account to:
        $to = $this->accounts->find(intval(Input::get('account_to_id')));

        // find account from
        $from = $this->accounts->find(intval(Input::get('account_from_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->tj->createSimpleJournal($from, $to, $description, $amount, $date);
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