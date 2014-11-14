<?php
use FireflyIII\Exception\FireflyException;
use Illuminate\Support\Collection;

/**
 * Class GoogleTableController
 */
class GoogleTableController extends BaseController
{

    /**
     * @param $what
     *
     * @throws FireflyException
     */
    public function accountList($what)
    {

        /** @var \FireflyIII\Database\Account $acct */
        $acct = App::make('FireflyIII\Database\Account');

        switch ($what) {
            default:
                throw new FireflyException('Cannot handle "' . e($what) . '" in accountList.');
                break;
            case 'asset':
                $list = $acct->getAssetAccounts();
                break;
            case 'expense':
                $list = $acct->getExpenseAccounts();
                break;
            case 'revenue':
                $list = $acct->getRevenueAccounts();
                break;
        }


        $chart = App::make('gchart');
        $chart->addColumn('ID', 'number');
        $chart->addColumn('ID_Edit', 'string');
        $chart->addColumn('ID_Delete', 'string');
        $chart->addColumn('Name_URL', 'string');
        $chart->addColumn('Name', 'string');
        $chart->addColumn('Balance', 'number');

        /** @var \Account $entry */
        foreach ($list as $entry) {
            $edit   = route('accounts.edit', $entry->id);
            $delete = route('accounts.delete', $entry->id);
            $show   = route('accounts.show', $entry->id);
            $chart->addRow($entry->id, $edit, $delete, $show, $entry->name, $entry->balance());
        }

        $chart->generate();

        return Response::json($chart->getData());


    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryList()
    {

        /** @var \FireflyIII\Database\Category $repos */
        $repos = App::make('FireflyIII\Database\Category');

        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('ID', 'number');
        $chart->addColumn('ID_Edit', 'string');
        $chart->addColumn('ID_Delete', 'string');
        $chart->addColumn('Name_URL', 'string');
        $chart->addColumn('Name', 'string');

        $list = $repos->get();

        /** @var Category $entry */
        foreach ($list as $entry) {
            $chart->addRow(
                $entry->id, route('categories.edit', $entry->id), route('categories.delete', $entry->id), route('categories.show', $entry->id), $entry->name
            );
        }


        $chart->generate();

        return Response::json($chart->getData());

    }

    public function recurringList()
    {
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('ID', 'number');
        $chart->addColumn('ID_Edit', 'string');
        $chart->addColumn('ID_Delete', 'string');
        $chart->addColumn('Name_URL', 'string');
        $chart->addColumn('Name', 'string');
        $chart->addColumn('Matches', 'string');
        $chart->addColumn('Minimum amount', 'number');
        $chart->addColumn('Maximum amount', 'number');

        /** @var \FireflyIII\Database\Recurring $repository */
        $repository = App::make('FireflyIII\Database\Recurring');

        $set = $repository->get();

        /** @var \RecurringTransaction $entry */
        foreach ($set as $entry) {
            $row = [$entry->id, route('recurring.edit', $entry->id), route('recurring.delete', $entry->id), route('recurring.show', $entry->id), $entry->name,
                    $entry->match, $entry->amount_min, $entry->amount_max

            ];
            $chart->addRowArray($row);

        }


        /*
         *                     <th>name</th>
        <th>match</th>
        <th>amount_min</th>
        <th>amount_max</th>
        <th>date</th>
        <th>active</th>
        <th>automatch</th>
        <th>repeat_freq</th>
        <th>id</th>

         */
        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param Account $account
     */
    public function transactionsByAccount(Account $account)
    {
        $table    = new \FireflyIII\Shared\Google\Table\Transactions;
        /*
         * Find transactions:
         */
        $accountID    = $account->id;
        $transactions = $account->transactions()->with(
            ['transactionjournal', 'transactionjournal.transactions' => function ($q) use ($accountID) {
                $q->where('account_id', '!=', $accountID);
            }, 'transactionjournal.budgets', 'transactionjournal.transactiontype', 'transactionjournal.categories']
        )->before(Session::get('end'))->after(
            Session::get('start')
        )->orderBy('date', 'DESC')->get();

        $collection = new Collection;
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {

            $date           = $transaction->transactionJournal->date;
            $descriptionURL = route('transactions.show', $transaction->transaction_journal_id);
            $description    = $transaction->transactionJournal->description;
            $amount         = floatval($transaction->amount);

            if ($transaction->transactionJournal->transactions[0]->account->id == $account->id) {
                $opposingAccountURI  = route('accounts.show', $transaction->transactionJournal->transactions[1]->account->id);
                $opposingAccountName = $transaction->transactionJournal->transactions[1]->account->name;
            } else {
                $opposingAccountURI  = route('accounts.show', $transaction->transactionJournal->transactions[0]->account->id);
                $opposingAccountName = $transaction->transactionJournal->transactions[0]->account->name;
            }
            if (isset($transaction->transactionJournal->budgets[0])) {
                $budgetURL = route('budgets.show', $transaction->transactionJournal->budgets[0]->id);
                $budget    = $transaction->transactionJournal->budgets[0]->name;
            } else {
                $budgetURL = '';
                $budget    = '';
            }

            if (isset($transaction->transactionJournal->categories[0])) {
                $categoryURL = route('categories.show', $transaction->transactionJournal->categories[0]->id);
                $category    = $transaction->transactionJournal->categories[0]->name;
            } else {
                $categoryURL = '';
                $category    = '';
            }


            if ($amount < 0) {
                $from    = $account->name;
                $fromURL = route('accounts.show', $account->id);

                $to    = $opposingAccountName;
                $toURL = $opposingAccountURI;
            } else {
                $to    = $account->name;
                $toURL = route('accounts.show', $account->id);

                $from    = $opposingAccountName;
                $fromURL = $opposingAccountURI;
            }

            $id     = $transaction->transactionJournal->id;
            $edit   = route('transactions.edit', $transaction->transactionJournal->id);
            $delete = route('transactions.delete', $transaction->transactionJournal->id);
            $chart->addRow(
                $id, $edit, $delete, $date, $descriptionURL, $description, $amount, $fromURL, $from, $toURL, $to, $budgetURL, $budget, $categoryURL, $category
            );
        }

        //        <th>Date</th>
        //        <th>Description</th>
        //        <th>Amount (&euro;)</th>
        //        <th>From</th>
        //        <th>To</th>
        //        <th>Budget / category</th>
        //        <th>ID</th>


        $chart->generate();

        return Response::json($chart->getData());
    }

    /**
     * @param Component       $component
     * @param LimitRepetition $repetition
     */
    public function transactionsByComponent(Component $component, LimitRepetition $repetition = null)
    {
        /** @var \Grumpydictator\Gchart\GChart $chart */
        $chart = App::make('gchart');
        $chart->addColumn('ID', 'number');
        $chart->addColumn('ID_Edit', 'string');
        $chart->addColumn('ID_Delete', 'string');
        $chart->addColumn('Date', 'date');
        $chart->addColumn('Description_URL', 'string');
        $chart->addColumn('Description', 'string');
        $chart->addColumn('Amount', 'number');
        $chart->addColumn('From_URL', 'string');
        $chart->addColumn('From', 'string');
        $chart->addColumn('To_URL', 'string');
        $chart->addColumn('To', 'string');
        $chart->addColumn('Budget_URL', 'string');
        $chart->addColumn('Budget', 'string');
        $chart->addColumn('Category_URL', 'string');
        $chart->addColumn('Category', 'string');

        if (is_null($repetition)) {
            $journals = $component->transactionjournals()->with(['budgets', 'categories', 'transactions', 'transactions.account'])->orderBy('date', 'DESC')
                                  ->get();
        } else {
            $journals = $component->transactionjournals()->with(['budgets', 'categories', 'transactions', 'transactions.account'])->after(
                $repetition->startdate
            )->before($repetition->enddate)->orderBy('date', 'DESC')->get();
        }
        /** @var TransactionJournal $transaction */
        foreach ($journals as $journal) {
            $date           = $journal->date;
            $descriptionURL = route('transactions.show', $journal->id);
            $description    = $journal->description;
            /** @var Transaction $transaction */
            foreach ($journal->transactions as $transaction) {
                if (floatval($transaction->amount) > 0) {
                    $amount = floatval($transaction->amount);
                    $to     = $transaction->account->name;
                    $toURL  = route('accounts.show', $transaction->account->id);
                } else {
                    $from    = $transaction->account->name;
                    $fromURL = route('accounts.show', $transaction->account->id);
                }

            }
            if (isset($journal->budgets[0])) {
                $budgetURL = route('budgets.show', $journal->budgets[0]->id);
                $component = $journal->budgets[0]->name;
            } else {
                $budgetURL = '';
                $component = '';
            }

            if (isset($journal->categories[0])) {
                $categoryURL = route('categories.show', $journal->categories[0]->id);
                $category    = $journal->categories[0]->name;
            } else {
                $categoryURL = '';
                $category    = '';
            }


            $id     = $journal->id;
            $edit   = route('transactions.edit', $journal->id);
            $delete = route('transactions.delete', $journal->id);
            $chart->addRow(
                $id, $edit, $delete, $date, $descriptionURL, $description, $amount, $fromURL, $from, $toURL, $to, $budgetURL, $component, $categoryURL,
                $category
            );
        }


        $chart->generate();

        return Response::json($chart->getData());

    }

    public function transactionsByRecurring(RecurringTransaction $recurring)
    {

        /** @var \FireflyIII\Shared\Google\Table\Transactions $table */
        $table    = new \FireflyIII\Shared\Google\Table\Transactions;
        $journals = $recurring->transactionjournals()->get();

        $table->addData($journals);

        return $table->generate();
    }

    /**
     * @param $what
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionsList($what)
    {
        /*
         * Process some google stuff:
         */
        $parameters = explode(' ',trim(Input::get('tq')));
        $limit = intval($parameters[1]);
        $offset = intval($parameters[3]);
        $request = explode(':',Input::get('tqx'));
        $reqID = $request[1];

        /** @var \FireflyIII\Shared\Google\Table\Transactions $table */
        $table    = new \FireflyIII\Shared\Google\Table\Transactions;
        $table->setPaging(true);
        $table->setLimit($limit);
        $table->setOffset($offset);
        $table->setReqID($reqID);

        /** @var \FireflyIII\Database\TransactionJournal $repository */
        $repository = App::make('FireflyIII\Database\TransactionJournal');

        switch ($what) {
            case 'expenses':
            case 'withdrawal':
                $list = $repository->getWithdrawals($limit, $offset);
                break;
            case 'revenue':
            case 'deposit':
                $list = $repository->getDeposits($limit, $offset);
                break;
            case 'transfer':
            case 'transfers':
                $list = $repository->getTransfers($limit, $offset);
                break;
        }

        $table->addData($list);

        echo $table->generate();
        exit;
    }
} 