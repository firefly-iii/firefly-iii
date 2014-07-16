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

    public function create($what)
    {
        // get accounts with names and id's.
        $accounts = $this->_accounts->getActiveDefaultAsSelectList();

        $budgets = $this->_budgets->getAsSelectList();

        $budgets[0] = '(no budget)';

        return View::make('transactions.create')->with('accounts', $accounts)->with('budgets', $budgets)->with(
            'what', $what
        );
    }

    public function store($what)
    {
        // $fromAccount and $toAccount are found
        // depending on the $what

        $fromAccount = null;
        $toAccount = null;

        switch ($what) {
            case 'withdrawal':
                $fromAccount = $this->_accounts->find(intval(Input::get('account_id')));
                $toAccount = $this->_accounts->createOrFindBeneficiary(Input::get('beneficiary'));
                break;
            case 'deposit':
                $fromAccount = $this->_accounts->createOrFindBeneficiary(Input::get('beneficiary'));
                $toAccount = $this->_accounts->find(intval(Input::get('account_id')));
                break;
            case 'transfer':
                $fromAccount = $this->_accounts->find(intval(Input::get('account_from_id')));
                $toAccount = $this->_accounts->find(intval(Input::get('account_to_id')));
                break;
        }
        // fall back to cash if necessary:
        if (is_null($fromAccount)) {
            $fromAccount = $this->_accounts->getCashAccount();
        }
        if (is_null($toAccount)) {
            $toAccount = $this->_accounts->getCashAccount();
        }

        // create or find category:
        $category = $this->_categories->createOrFind(Input::get('category'));

        // find budget:
        $budget = $this->_budgets->find(intval(Input::get('budget_id')));

        // find amount & description:
        $description = trim(Input::get('description'));
        $amount = floatval(Input::get('amount'));
        $date = new \Carbon\Carbon(Input::get('date'));

        // create journal
        /** @var \TransactionJournal $journal */
        try {
            $journal = $this->_journal->createSimpleJournal($fromAccount, $toAccount, $description, $amount, $date);
        } catch (\Firefly\Exception\FireflyException $e) {
            return Redirect::route('transactions.create', $what)->withInput();
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

    public function show($journalId)
    {
        $journal = $this->_journal->find($journalId);
        if ($journal) {
            return View::make('transactions.show')->with('journal', $journal);
        }
        return View::make('error')->with('message', 'Invalid journal');
    }

    public function edit($journalId)
    {
        $journal = $this->_journal->find($journalId);
        if ($journal) {
            $accounts = $this->_accounts->getActiveDefaultAsSelectList();
            return View::make('transactions.edit')->with('journal', $journal)->with('accounts', $accounts);
        }
        return View::make('error')->with('message', 'Invalid journal');
    }

} 