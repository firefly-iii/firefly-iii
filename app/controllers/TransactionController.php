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

    public function postCreateWithdrawal()
    {

        // create or find beneficiary:
        $beneficiary = $this->accounts->createOrFindBeneficiary(Input::get('beneficiary'));

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
        $journal = $this->tj->createSimpleJournal($account, $beneficiary, $description, $amount, $date);

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

} 