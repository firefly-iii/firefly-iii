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
     * @param $what
     *
     * @return $this|\Illuminate\View\View
     */
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

    /**
     * @param $what
     *
     * @return \Illuminate\Http\RedirectResponse
     */
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
        $fromAccount = is_null($fromAccount) ? $fromAccount = $this->_accounts->getCashAccount() : $fromAccount;
        $toAccount = is_null($toAccount) ? $toAccount = $this->_accounts->getCashAccount() : $toAccount;

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

        Session::flash('success', 'Transaction "' . $description . '" saved');

        if (Input::get('create') == '1') {
            return Redirect::route('transactions.create', $what)->withInput();
        } else {
            return Redirect::route('index');
        }


    }

    /**
     * @return $this|\Illuminate\View\View
     */
    public function index()
    {
        $transactions = $this->_journal->paginate(25);

        return View::make('transactions.index')->with('transactions', $transactions);
    }

    /**
     * @param $journalId
     *
     * @return $this|\Illuminate\View\View
     */
    public function show($journalId)
    {
        $journal = $this->_journal->find($journalId);
        if ($journal) {
            return View::make('transactions.show')->with('journal', $journal);
        }

        return View::make('error')->with('message', 'Invalid journal');
    }

    /**
     * @param $journalId
     *
     * @return $this|\Illuminate\View\View
     */
    public function edit($journalId)
    {
        // get journal:
        $journal = $this->_journal->find($journalId);

        if ($journal) {
            // type is useful for display:
            $what = strtolower($journal->transactiontype->type);

            // some lists prefilled:
            $budgets = $this->_budgets->getAsSelectList();
            $budgets[0] = '(no budget)';
            $accounts = $this->_accounts->getActiveDefaultAsSelectList();

            // data to properly display form:
            $data = [
                'date'      => $journal->date->format('Y-m-d'),
                'category'  => '',
                'budget_id' => 0
            ];
            $category = $journal->categories()->first();
            if (!is_null($category)) {
                $data['category'] = $category->name;
            }
            switch ($journal->transactiontype->type) {
                case 'Withdrawal':
                    $data['account_id'] = $journal->transactions[0]->account->id;
                    $data['beneficiary'] = $journal->transactions[1]->account->name;
                    $data['amount'] = floatval($journal->transactions[1]->amount);
                    $budget = $journal->budgets()->first();
                    if (!is_null($budget)) {
                        $data['budget_id'] = $budget->id;
                    }
                    break;
                case 'Deposit':
                    $data['account_id'] = $journal->transactions[1]->account->id;
                    $data['beneficiary'] = $journal->transactions[0]->account->name;
                    $data['amount'] = floatval($journal->transactions[1]->amount);
                    break;
                case 'Transfer':
                    $data['account_from_id'] = $journal->transactions[1]->account->id;
                    $data['account_to_id'] = $journal->transactions[0]->account->id;
                    $data['amount'] = floatval($journal->transactions[1]->amount);
                    break;
            }

            return View::make('transactions.edit')->with('journal', $journal)->with('accounts', $accounts)->with(
                'what', $what
            )->with('budgets', $budgets)->with('data', $data);
        }

        return View::make('error')->with('message', 'Invalid journal');
    }

    public function update($journalId)
    {

        // get journal:
        $journal = $this->_journal->find($journalId);

        if ($journal) {
            // update basics first:
            $journal->description = Input::get('description');
            $journal->date = Input::get('date');
            $amount = floatval(Input::get('amount'));

            // remove previous category, if any:
            if (!is_null($journal->categories()->first())) {
                $journal->categories()->detach($journal->categories()->first()->id);
            }
            // remove previous budget, if any:
            if (!is_null($journal->budgets()->first())) {
                $journal->budgets()->detach($journal->budgets()->first()->id);
            }

            // get the category:
            $category = $this->_categories->findByName(Input::get('category'));
            if (!is_null($category)) {
                $journal->categories()->attach($category);
            }
            // update the amounts:
            $journal->transactions[0]->amount = $amount * -1;
            $journal->transactions[1]->amount = $amount;

            // switch on type to properly change things:
            switch ($journal->transactiontype->type) {
                case 'Withdrawal':
                    // means transaction[0] is the users account.
                    $account = $this->_accounts->find(Input::get('account_id'));
                    $beneficiary = $this->_accounts->findByName(Input::get('beneficiary'));
                    $journal->transactions[0]->account()->associate($account);
                    $journal->transactions[1]->account()->associate($beneficiary);


                    // do budget:
                    $budget = $this->_budgets->find(Input::get('budget_id'));
                    $journal->budgets()->attach($budget);

                    break;
                case 'Deposit':
                    // means transaction[0] is the beneficiary.
                    $account = $this->_accounts->find(Input::get('account_id'));
                    $beneficiary = $this->_accounts->findByName(Input::get('beneficiary'));
                    $journal->transactions[0]->account()->associate($beneficiary);
                    $journal->transactions[1]->account()->associate($account);
                    break;
                case 'Transfer':
                    // means transaction[0] is account that sent the money (from).
                    $fromAccount = $this->_accounts->find(Input::get('account_from_id'));
                    $toAccount = $this->_accounts->find(Input::get('account_to_id'));
                    $journal->transactions[0]->account()->associate($fromAccount);
                    $journal->transactions[1]->account()->associate($toAccount);
                    break;
                default:
                    throw new \Firefly\Exception\FireflyException('Cannot edit this!');
                    break;
            }

            $journal->transactions[0]->save();
            $journal->transactions[1]->save();
            $journal->save();

            return Redirect::route('transactions.edit', $journal->id);
        }


    }

} 