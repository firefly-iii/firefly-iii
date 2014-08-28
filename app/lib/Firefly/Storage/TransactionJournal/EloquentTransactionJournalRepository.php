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
        $journal = new \TransactionJournal;

        $amountFrom = $amount * -1;
        $amountTo = $amount;

        if (round(floatval($amount), 2) == 0.00) {
            $journal->errors()->add('amount', 'Amount must not be zero.');

            return $journal;
        }
        // same account:
        if ($from->id == $toAccount->id) {
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

        if (is_null($journalType)) {
            throw new FireflyException('Could not figure out transaction type.');
        }

        // always the same currency:
        $currency = \TransactionCurrency::where('code', 'EUR')->first();
        if (is_null($currency)) {
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
            throw new FireflyException('Cannot create valid transaction (from): ' . $fromTransaction->errors()->first(
                ));
        }
        $fromTransaction->save();

        $toTransaction = new \Transaction;
        $toTransaction->account()->associate($toAccount);
        $toTransaction->transactionJournal()->associate($journal);
        $toTransaction->description = null;
        $toTransaction->amount = $amountTo;
        if (!$toTransaction->validate()) {
            throw new FireflyException('Cannot create valid transaction (to): ' . $toTransaction->errors()->first());
        }
        $toTransaction->save();

        $journal->completed = true;
        $journal->save();

        return $journal;
    }

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
     * @param int $count
     *
     * @return mixed
     */
    public function paginate($count = 25, Carbon $start = null, Carbon $end = null)
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
            ->orderBy('transaction_journals.id', 'DESC');
        if (!is_null($start)) {
            $query->where('transaction_journals.date', '>=', $start->format('Y-m-d'));
        }
        if (!is_null($end)) {
            $query->where('transaction_journals.date', '<=', $end->format('Y-m-d'));
        }

        $result = $query->paginate($count);

        return $result;
    }

    /**
     * @param $what
     * @param $data
     *
     * @return mixed|\TransactionJournal
     */
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
        // find amount & description:
        $description = trim($data['description']);
        $amount = floatval($data['amount']);
        $date = new Carbon($data['date']);

        // try to create a journal:
        $transactionJournal = $this->createSimpleJournal($fromAccount, $toAccount, $description, $amount, $date);
        if (!$transactionJournal->id || $transactionJournal->completed == 0) {
            return $transactionJournal;
        }

        // here we're done and we have transactions in the journal:
        // do something with the piggy bank:
        if ($what == 'transfer') {
            /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggyRepository */
            $piggyRepository = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

            if (isset($data['piggybank_id'])) {
                /** @var \Piggybank $piggyBank */
                $piggyBank = $piggyRepository->find(intval($data['piggybank_id']));

                if ($piggyBank) {
                    // one of the two transactions may be connected to this piggy bank.
                    $connected = false;
                    foreach ($transactionJournal->transactions()->get() as $transaction) {
                        if ($transaction->account_id == $piggyBank->account_id) {
                            $connected = true;
                            $transaction->piggybank()->associate($piggyBank);
                            $transaction->save();
                            \Event::fire(
                                'piggybanks.createRelatedTransfer', [$piggyBank, $transactionJournal, $transaction]
                            );
                            break;
                        }
                    }
                    if ($connected === false) {
                        \Session::flash(
                            'warning', 'Piggy bank "' . e($piggyBank->name)
                            . '" is not set to draw money from any of the accounts in this transfer'
                        );
                    }
                }
            }
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

    /**
     * @param \TransactionJournal $journal
     * @param                     $data
     *
     * @return mixed|\TransactionJournal
     * @throws \Firefly\Exception\FireflyException
     */
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
        // remove previous piggy bank, if any:


        $category = isset($data['category']) ? $catRepository->findByName($data['category']) : null;
        if (!is_null($category)) {
            $journal->categories()->attach($category);
        }
        // update the amounts:
        $transactions = $journal->transactions()->orderBy('amount', 'ASC')->get();

        // remove previous piggy bank, if any:
        /** @var \Transaction $transaction */
        foreach ($transactions as $transaction) {
            if (!is_null($transaction->piggybank()->first())) {
                $transaction->piggybank_id = null;
                $transaction->save();
            }
        }
        unset($transaction);

        $transactions[0]->amount = $amount * -1;
        $transactions[1]->amount = $amount;

        // switch on type to properly change things:
        $fireEvent = false;
        switch ($journal->transactiontype->type) {
            case 'Withdrawal':
                // means transaction[0] is the users account.
                $account = $accountRepository->find($data['account_id']);
                $beneficiary = $accountRepository->createOrFindBeneficiary($data['beneficiary']);
                $transactions[0]->account()->associate($account);
                $transactions[1]->account()->associate($beneficiary);

                // do budget:
                $budget = $budRepository->find($data['budget_id']);
                if (!is_null($budget)) {
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
                /** @var \Account $fromAccount */
                $fromAccount = $accountRepository->find($data['account_from_id']);
                /** @var \Account $toAccount */
                $toAccount = $accountRepository->find($data['account_to_id']);
                $journal->transactions[0]->account()->associate($fromAccount);
                $journal->transactions[1]->account()->associate($toAccount);

                // attach the new piggy bank, if valid:
                /** @var \Firefly\Storage\Piggybank\PiggybankRepositoryInterface $piggyRepository */
                $piggyRepository = \App::make('Firefly\Storage\Piggybank\PiggybankRepositoryInterface');

                if (isset($data['piggybank_id'])) {
                    /** @var \Piggybank $piggyBank */
                    $piggyBank = $piggyRepository->find(intval($data['piggybank_id']));

                    // loop transactions and re-attach the piggy bank:

                    if ($piggyBank) {

                        $connected = false;
                        foreach ($journal->transactions()->get() as $transaction) {
                            if ($transaction->account_id == $piggyBank->account_id) {
                                $connected = true;
                                $transaction->piggybank()->associate($piggyBank);
                                $transaction->save();
                                $fireEvent = true;
                                break;
                            }
                        }
                        if ($connected === false) {
                            \Session::flash(
                                'warning', 'Piggy bank "' . e($piggyBank->name)
                                . '" is not set to draw money from any of the accounts in this transfer'
                            );
                        }
                    }
                }


                break;
            default:
                throw new FireflyException('Cannot edit this!');
                break;
        }

        $transactions[0]->save();
        $transactions[1]->save();
        if ($journal->validate()) {
            $journal->save();
        }
        if ($fireEvent) {
            \Event::fire('piggybanks.updateRelatedTransfer', [$piggyBank]);
        }

        return $journal;


    }


}