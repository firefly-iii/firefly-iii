<?php
use Carbon\Carbon;

/**
 * Class GoogleTableController
 */
class GoogleTableController extends BaseController
{
    /**
     * @param Account $account
     */
    public function transactionsByAccount(Account $account)
    {
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

        /*
         * Find transactions:
         */
        $accountID    = $account->id;
        $transactions = $account->transactions()->with(
            ['transactionjournal', 'transactionjournal.transactions' => function ($q) use ($accountID) {
                $q->where('account_id', '!=', $accountID);
            }, 'transactionjournal.budgets', 'transactionjournal.transactiontype',
             'transactionjournal.categories']
        )->before(Session::get('end'))->after(
            Session::get('start')
        )->orderBy('date', 'DESC')->get();

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
                $budgetURL = '';
                $budget    = '';
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

            $budcat = 'Budcat';
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
} 