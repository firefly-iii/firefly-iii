<?php


namespace Firefly\Storage\TransactionJournal;


class EloquentTransactionJournalRepository implements TransactionJournalRepositoryInterface
{

    public function createSimpleJournal(\Account $from, \Account $to, $description, $amount, \Carbon\Carbon $date)
    {

        \Log::debug('Creating tranaction "' . $description . '".');
        /*
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
         */

        // amounts:
        $amountFrom = $amount * -1;
        $amountTo = $amount;

        // account types for both:
        $toAT = $to->accountType->description;
        $fromAT = $from->accountType->description;

        $journalType = null;

        switch (true) {
            case ($from->transactions()->count() == 0 && $to->transactions()->count() == 0):
                $journalType = \TransactionType::where('type', 'Opening balance')->first();
                break;

            // both are yours:
            case ($fromAT == 'Default account' && $toAT == 'Default account'):
                // determin transaction type. If both accounts are new, it's an initial
                // balance transfer.
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
        $journal->completed = false;
        $journal->description = $description;
        $journal->date = $date;
        if (!$journal->save()) {
            \Log::error('Cannot create valid journal.');
            \Log::error('Errors: ' . print_r($journal->errors()->all(), true));
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

    public function homeBudgetChart(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return $this->homeComponentChart($start, $end, 'Budget');
    }

    public function homeComponentChart(\Carbon\Carbon $start, \Carbon\Carbon $end, $chartType)
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
                ['components'         => function ($q) use ($chartType) {
                        $q->where('class', $chartType);
                    }, 'transactions' => function ($q) {
                        $q->where('amount', '>', 0);
                    }]
            )
            ->after($start)->before($end)
            ->whereIn('transaction_type_id', $types)
            ->get(['transaction_journals.*']);
        unset($types);
        $result = [];


        foreach ($journals as $journal) {
            // has to be one:
            $transaction = $journal->transactions[0];
            $amount = floatval($transaction->amount);


            // MIGHT be one:
            $budget = isset($journal->components[0]) ? $journal->components[0] : null;
            if (!is_null($budget)) {
                $name = $budget->name;
            } else {
                $name = '(no budget)';
            }
            $result[$name] = isset($result[$name]) ? $result[$name] + $amount : $amount;

        }
        unset($journal, $transaction, $budget, $name, $amount);
        return $result;
    }

    public function homeCategoryChart(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return $this->homeComponentChart($start, $end, 'Category');
    }

    public function homeBeneficiaryChart(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        $result = [];

        // lets make this simple.
        $types = [];
        foreach (\TransactionType::whereIn('type', ['Withdrawal'])->get() as $t) {
            $types[] = $t->id;
        }
        unset($t);

        // account type we want to see:
        $accountType = \AccountType::where('description', 'Beneficiary account')->first();
        $accountTypeID = $accountType->id;

        // get all journals, partly filtered:
        $journals = \TransactionJournal::
            with(
                ['transactions', 'transactions.account' => function ($q) use ($accountTypeID) {
                        $q->where('account_type_id', $accountTypeID);
                    }]
            )
            ->after($start)->before($end)
            ->whereIn('transaction_type_id', $types)
            ->get(['transaction_journals.*']);
        foreach ($journals as $journal) {
            foreach ($journal->transactions as $t) {
                if (!is_null($t->account)) {
                    $name = $t->account->name;
                    $amount = floatval($t->amount) < 0 ? floatval($t->amount) * -1 : floatval($t->amount);

                    $result[$name] = isset($result[$name]) ? $result[$name] + $amount : $amount;
                }
            }
        }
        return $result;
    }

    public function getByAccount(\Account $account, $count = 25)
    {
        $accountID = $account->id;
        $query = \TransactionJournal::
            with(
                [
                    'transactions',
                    'transactioncurrency',
                    'transactiontype'
                ]
            )
            ->take($count)
            ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->where('accounts.id', $accountID)
            ->orderBy('transaction_journals.date', 'DESC')
            ->orderBy('transaction_journals.id', 'DESC')
            ->take($count)
            ->get(['transaction_journals.*']);
        return $query;
    }


}