<?php


namespace Firefly\Storage\TransactionJournal;


use Firefly\Exception\FireflyException;

class EloquentTransactionJournalRepository implements TransactionJournalRepositoryInterface
{

    public function find($journalId)
    {
        return \Auth::user()->transactionjournals()->with(
            ['transactions', 'transactioncurrency', 'transactiontype', 'components', 'transactions.account',
             'transactions.account.accounttype']
        )
            ->where('id', $journalId)->first();
    }
    /*

             *
             */

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
     * @param \Account       $to
     * @param                $description
     * @param                $amount
     * @param \Carbon\Carbon $date
     *
     * @return \TransactionJournal
     * @throws \Firefly\Exception\FireflyException
     */
    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date)
    {
        \Log::debug('Creating tranaction "' . $description . '".');

        $amountFrom = $amount * -1;
        $amountTo = $amount;

        if (round(floatval($amount), 2) == 0.00) {
            \Log::error('Transaction will never save: amount = 0');
            \Session::flash('error', 'The amount should not be empty or zero.');
            throw new \Firefly\Exception\FireflyException('Could not figure out transaction type.');
        }
        // same account:
        if ($from->id == $to->id) {
            \Log::error('Accounts cannot be equal');
            \Session::flash('error', 'Select two different accounts.');
            throw new \Firefly\Exception\FireflyException('Select two different accounts.');
        }

        // account types for both:
        $toAT = $to->accountType->description;
        $fromAT = $from->accountType->description;

        $journalType = null;

        switch (true) {
            case ($from->transactions()->count() == 0 && $to->transactions()->count() == 0):
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
            . ' and AccountTo "' . $to->name . '" will gain/lose ' . $amountTo
        );

        if (is_null($journalType)) {
            \Log::error('Could not figure out transacion type!');
            throw new \Firefly\Exception\FireflyException('Could not figure out transaction type.');
        }

        // always the same currency:
        $currency = \TransactionCurrency::where('code', 'EUR')->first();
        if (is_null($currency)) {
            \Log::error('No currency for journal!');
            throw new \Firefly\Exception\FireflyException('No currency for journal!');
        }

        // new journal:
        $journal = new \TransactionJournal();
        $journal->transactionType()->associate($journalType);
        $journal->transactionCurrency()->associate($currency);
        $journal->user()->associate(\Auth::user());
        $journal->completed = false;
        $journal->description = $description;
        $journal->date = $date;
        if (!$journal->save()) {
            \Log::error('Cannot create valid journal.');
            \Log::error('Errors: ' . print_r($journal->errors()->all(), true));
            \Session::flash('error', 'Could not create journal: ' . $journal->errors()->first());
            throw new \Firefly\Exception\FireflyException('Cannot create valid journal.');
        }
        $journal->save();

        // create transactions:
        $fromTransaction = new \Transaction;
        $fromTransaction->account()->associate($from);
        $fromTransaction->transactionJournal()->associate($journal);
        $fromTransaction->description = null;
        $fromTransaction->amount = $amountFrom;
        if (!$fromTransaction->save()) {
            \Log::error('Cannot create valid transaction (from) for journal #' . $journal->id);
            \Log::error('Errors: ' . print_r($fromTransaction->errors()->all(), true));
            throw new \Firefly\Exception\FireflyException('Cannot create valid transaction (from).');
        }
        $fromTransaction->save();

        $toTransaction = new \Transaction;
        $toTransaction->account()->associate($to);
        $toTransaction->transactionJournal()->associate($journal);
        $toTransaction->description = null;
        $toTransaction->amount = $amountTo;
        if (!$toTransaction->save()) {
            \Log::error('Cannot create valid transaction (to) for journal #' . $journal->id);
            \Log::error('Errors: ' . print_r($toTransaction->errors()->all(), true));
            throw new \Firefly\Exception\FireflyException('Cannot create valid transaction (to).');
        }
        $toTransaction->save();

        $journal->completed = true;
        $journal->save();
        return $journal;
    }

    public function get()
    {

    }

    public function getByAccount(\Account $account, $count = 25)
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
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->take($count)
            ->get(['transaction_journals.*']);
        return $query;
    }

    public function getByDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        // lets make this simple.
        $types = [];
        foreach (\TransactionType::whereIn('type', ['Withdrawal'])->get() as $t) {
            $types[] = $t->id;
        }
        unset($t);

        // get all journals, partly filtered:
        $journals = \TransactionJournal::
            with(
                ['components', 'transactions' => function ($q) {
                        $q->where('amount', '>', 0);
                    }]
            )
            ->after($start)->before($end)
            ->where('completed', 1)
            ->whereIn('transaction_type_id', $types)
            ->get(['transaction_journals.*']);
        unset($types);
        return $journals;
    }

    public function getByAccountAndDate(\Account $account, \Carbon\Carbon $date)
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


}