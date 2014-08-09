<?php


namespace Firefly\Storage\TransactionJournal;

use Carbon\Carbon;
use Firefly\Exception\FireflyException;

/**
 * Class EloquentTransactionJournalRepository
 *
 * @package Firefly\Storage\TransactionJournal
 */
class EloquentTransactionJournalRepository implements TransactionJournalRepositoryInterface
{

    /**
     *
     * We're building this thinking the money goes from A to B.
     * If the amount is negative however, the money still goes
     * from A to B but the balances are reversed.
     *
     * Aka:
     *
     * Amount = 200
     * A loses 200 (-200).  * -1
     * B gains 200 (200).    * 1
     *
     * Final balance: -200 for A, 200 for B.
     *
     * When the amount is negative:
     *
     * Amount = -200
     * A gains 200 (200). * -1
     * B loses 200 (-200). * 1
     *
     * @param \Account       $from
     * @param \Account       $toAccount
     * @param                $description
     * @param                $amount
     * @param \Carbon\Carbon $date
     *
     * @return \TransactionJournal
     * @throws \Firefly\Exception\FireflyException
     */
    public function createSimpleJournal(\Account $from, \Account $toAccount, $description, $amount, Carbon $date)
    {
        \Log::debug('Creating tranaction "' . $description . '".');
        $journal = new \TransactionJournal;

        $amountFrom = $amount * -1;
        $amountTo = $amount;

        if (round(floatval($amount), 2) == 0.00) {
            \Log::error('Transaction will never save: amount = 0');
            $journal->errors()->add('amount', 'Amount must not be zero.');

            return $journal;
        }
        // same account:
        if ($from->id == $toAccount->id) {
            \Log::error('Accounts cannot be equal');
            $journal->errors()->add('account_id', 'Must be different accounts.');
            $journal->errors()->add('account_from_id', 'Must be different accounts.');

            return $journal;
        }

        // account types for both:
        $toAT = $toAccount->accountType->description;
        $fromAT = $from->accountType->description;

        $journalType = null;

        switch (true) {
            case ($from->transactions()->count() == 0 && $toAccount->transactions()->count() == 0):
                $journalType = \TransactionType::where('type', 'Opening balance')->first();
                break;

            case ($fromAT == 'Default account' && $toAT == 'Default account'): // both are yours:
                // determin transaction type. If both accounts are new, it's an initial balance transfer.
                $journalType = \TransactionType::where('type', 'Transfer')->first();
                break;
            case ($amount < 0):
                $journalType = \TransactionType::where('type', 'Deposit')->first();
                break;
            // is deposit into one of your own accounts:
            case ($toAT == 'Default account'):
                $journalType = \TransactionType::where('type', 'Deposit')->first();
                break;
            // is withdrawal from one of your own accounts:
            case ($fromAT == 'Default account'):
                $journalType = \TransactionType::where('type', 'Withdrawal')->first();
                break;
        }

        // some debug information:
        \Log::debug(
            $journalType->type . ': AccountFrom "' . $from->name . '" will gain/lose ' . $amountFrom
            . ' and AccountTo "' . $toAccount->name . '" will gain/lose ' . $amountTo
        );

        if (is_null($journalType)) {
            \Log::error('Could not figure out transacion type!');
            throw new FireflyException('Could not figure out transaction type.');
        }

        // always the same currency:
        $currency = \TransactionCurrency::where('code', 'EUR')->first();
        if (is_null($currency)) {
            \Log::error('No currency for journal!');
            throw new FireflyException('No currency for journal!');
        }

        // new journal:

        $journal->transactionType()->associate($journalType);
        $journal->transactionCurrency()->associate($currency);
        $journal->user()->associate(\Auth::user());
        $journal->completed = false;
        $journal->description = $description;
        $journal->date = $date;
        if (!$journal->validate()) {
            return $journal;
        }
        $journal->save();

        // create transactions:
        $fromTransaction = new \Transaction;
        $fromTransaction->account()->associate($from);
        $fromTransaction->transactionJournal()->associate($journal);
        $fromTransaction->description = null;
        $fromTransaction->amount = $amountFrom;
        if (!$fromTransaction->validate()) {
            \Log::error('Cannot create valid transaction (from) for journal #' . $journal->id);
            \Log::error('Errors: ' . print_r($fromTransaction->errors()->all(), true));
            throw new FireflyException('Cannot create valid transaction (from).');
        }
        $fromTransaction->save();

        $toTransaction = new \Transaction;
        $toTransaction->account()->associate($toAccount);
        $toTransaction->transactionJournal()->associate($journal);
        $toTransaction->description = null;
        $toTransaction->amount = $amountTo;
        if (!$toTransaction->validate()) {
            \Log::error('Cannot create valid transaction (to) for journal #' . $journal->id);
            \Log::error('Errors: ' . print_r($toTransaction->errors()->all(), true));
            throw new FireflyException('Cannot create valid transaction (to).');
        }
        $toTransaction->save();

        $journal->completed = true;
        $journal->save();

        return $journal;
    }
    /*

             *
             */

    /**
     * @param $journalId
     *
     * @return mixed
     */
    public function find($journalId)
    {
        return \Auth::user()->transactionjournals()->with(
            ['transactions' => function ($q) {
                    return $q->orderBy('amount', 'ASC');
                }, 'transactioncurrency', 'transactiontype', 'components', 'transactions.account',
             'transactions.account.accounttype']
        )
            ->where('id', $journalId)->first();
    }

    /**
     *
     */
    public function get()
    {

    }

    /**
     * @param \Account $account
     * @param Carbon   $date
     *
     * @return mixed
     */
    public function getByAccountAndDate(\Account $account, Carbon $date)
    {
        $accountID = $account->id;
        $query = \Auth::user()->transactionjournals()->with(
            [
                'transactions',
                'transactions.account',
                'transactioncurrency',
                'transactiontype'
            ]
        )
            ->distinct()
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('transactions.account_id', $accountID)
            ->where('transaction_journals.date', $date->format('Y-m-d'))
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->get(['transaction_journals.*']);

        return $query;
    }

    /**
     * @param \Account $account
     * @param int      $count
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return mixed
     */
    public function getByAccountInDateRange(\Account $account, $count = 25, Carbon $start, Carbon $end)
    {
        $accountID = $account->id;
        $query = \Auth::user()->transactionjournals()->with(
            [
                'transactions',
                'transactioncurrency',
                'transactiontype'
            ]
        )
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.id', $accountID)
            ->where('date', '>=', $start->format('Y-m-d'))
            ->where('date', '<=', $end->format('Y-m-d'))
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->take($count)
            ->get(['transaction_journals.*']);

        return $query;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return mixed
     */
    public function getByDateRange(Carbon $start, Carbon $end)
    {
        die('no impl');
    }

    /**
     * @param int $count
     *
     * @return mixed
     */
    public function paginate($count = 25)
    {
        $query = \Auth::user()->transactionjournals()->with(
            [
                'transactions' => function ($q) {
                        return $q->orderBy('amount', 'ASC');
                    },
                'transactions.account',
                'transactions.account.accounttype',
                'transactioncurrency',
                'transactiontype'
            ]
        )
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->paginate($count);

        return $query;
    }

    public function store($what, $data)
    {
        // $fromAccount and $toAccount are found
        // depending on the $what

        $fromAccount = null;
        $toAccount = null;

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');

        /** @var \Firefly\Storage\Category\CategoryRepositoryInterface $catRepository */
        $catRepository = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budRepository */
        $budRepository = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');


        switch ($what) {
            case 'withdrawal':
                $fromAccount = $accountRepository->find(intval($data['account_id']));
                $toAccount = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                break;
            case 'deposit':
                $fromAccount = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                $toAccount = $accountRepository->find(intval($data['account_id']));
                break;
            case 'transfer':
                $fromAccount = $accountRepository->find(intval($data['account_from_id']));
                $toAccount = $accountRepository->find(intval($data['account_to_id']));
                break;
        }
        // fall back to cash if necessary:
        $fromAccount = is_null($fromAccount) ? $fromAccount = $accountRepository->getCashAccount() : $fromAccount;
        $toAccount = is_null($toAccount) ? $toAccount = $accountRepository->getCashAccount() : $toAccount;

        // create or find category:
        $category = isset($data['category']) ? $catRepository->createOrFind($data['category']) : null;

        // find budget:
        $budget = isset($data['budget_id']) ? $budRepository->find(intval($data['budget_id'])) : null;
//
//        // find amount & description:
        $description = trim($data['description']);
        $amount = floatval($data['amount']);
        $date = new \Carbon\Carbon($data['date']);

        // try to create a journal:
        $transactionJournal = $this->createSimpleJournal($fromAccount, $toAccount, $description, $amount, $date);
        if (!$transactionJournal->id || $transactionJournal->completed == 0) {
            return $transactionJournal;
        }

        // attach:
        if (!is_null($budget)) {
            $transactionJournal->budgets()->save($budget);
        }
        if (!is_null($category)) {
            $transactionJournal->categories()->save($category);
        }

        return $transactionJournal;
    }

    public function update(\TransactionJournal $journal, $data)
    {
        /** @var \Firefly\Storage\Category\CategoryRepositoryInterface $catRepository */
        $catRepository = \App::make('Firefly\Storage\Category\CategoryRepositoryInterface');

        /** @var \Firefly\Storage\Budget\BudgetRepositoryInterface $budgetRepository */
        $budRepository = \App::make('Firefly\Storage\Budget\BudgetRepositoryInterface');

        /** @var \Firefly\Storage\Account\AccountRepositoryInterface $accountRepository */
        $accountRepository = \App::make('Firefly\Storage\Account\AccountRepositoryInterface');


        // update basics first:
        $journal->description = $data['description'];
        $journal->date = $data['date'];
        $amount = floatval($data['amount']);

        // remove previous category, if any:
        if (!is_null($journal->categories()->first())) {
            $journal->categories()->detach($journal->categories()->first()->id);
        }
        // remove previous budget, if any:
        if (!is_null($journal->budgets()->first())) {
            $journal->budgets()->detach($journal->budgets()->first()->id);
        }


        $category = isset($data['category']) ? $catRepository->findByName($data['category']) : null;
        if (!is_null($category)) {
            $journal->categories()->attach($category);
        }
        // update the amounts:
        /** @var \Transaction $transaction */
        $transactions = $journal->transactions()->orderBy('amount', 'ASC')->get();
        $transactions[0]->amount = $amount * -1;
        $transactions[1]->amount = $amount;

        // switch on type to properly change things:
        switch ($journal->transactiontype->type) {
            case 'Withdrawal':
                // means transaction[0] is the users account.
                $account = $accountRepository->find($data['account_id']);
                $beneficiary = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                $transactions[0]->account()->associate($account);
                $transactions[1]->account()->associate($beneficiary);

                // do budget:
                $budget = $budRepository->find($data['budget_id']);
                if(!is_null($budget)) {
                    $journal->budgets()->attach($budget);
                }

                break;
            case 'Deposit':
                // means transaction[0] is the beneficiary.
                $account = $accountRepository->find($data['account_id']);
                $beneficiary = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                $journal->transactions[0]->account()->associate($beneficiary);
                $journal->transactions[1]->account()->associate($account);
                break;
            case 'Transfer':
                // means transaction[0] is account that sent the money (from).
                $fromAccount = $accountRepository->find($data['account_from_id']);
                $toAccount = $accountRepository->find($data['account_to_id']);
                $journal->transactions[0]->account()->associate($fromAccount);
                $journal->transactions[1]->account()->associate($toAccount);
                break;
            default:
                throw new \Firefly\Exception\FireflyException('Cannot edit this!');
                break;
        }

        $transactions[0]->save();
        $transactions[1]->save();
        if ($journal->validate()) {
            $journal->save();
        }

        return $journal;


    }


}