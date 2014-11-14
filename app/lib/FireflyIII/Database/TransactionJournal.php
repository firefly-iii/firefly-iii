<?php

namespace FireflyIII\Database;


use Carbon\Carbon;
use Firefly\Exception\FireflyException;
use FireflyIII\Database\Ifaces\CommonDatabaseCalls;
use FireflyIII\Database\Ifaces\CUD;
use FireflyIII\Database\Ifaces\TransactionJournalInterface;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use LaravelBook\Ardent\Ardent;

/**
 * Class TransactionJournal
 *
 * @package FireflyIII\Database
 */
class TransactionJournal implements TransactionJournalInterface, CUD, CommonDatabaseCalls
{
    use SwitchUser;

    /**
     *
     */
    public function __construct()
    {
        $this->setUser(\Auth::user());
    }

    /**
     * @param Ardent $model
     *
     * @return bool
     */
    public function destroy(Ardent $model)
    {
        $model->delete();
        return true;
    }

    /**
     * @param array $data
     *
     * @return Ardent
     */
    public function store(array $data)
    {
        /** @var \FireflyIII\Database\TransactionType $typeRepository */
        $typeRepository = \App::make('FireflyIII\Database\TransactionType');

        /** @var \FireflyIII\Database\Account $accountRepository */
        $accountRepository = \App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\TransactionCurrency $currencyRepository */
        $currencyRepository = \App::make('FireflyIII\Database\TransactionCurrency');

        /** @var \FireflyIII\Database\Transaction $transactionRepository */
        $transactionRepository = \App::make('FireflyIII\Database\Transaction');

        $journalType = $typeRepository->findByWhat($data['what']);
        $currency    = $currencyRepository->findByCode($data['currency']);

        $journal = new \TransactionJournal;
        $journal->transactionType()->associate($journalType);
        $journal->transactionCurrency()->associate($currency);
        $journal->user()->associate($this->getUser());
        $journal->description = $data['description'];
        $journal->date        = $data['date'];
        $journal->completed   = 0;

        /*
         * This must be enough to store the journal:
         */
        if (!$journal->validate()) {
            \Log::error($journal->errors()->all());
            throw new FireflyException('store() transaction journal failed, but it should not!');
        }
        $journal->save();

        /*
         * Still need to find the accounts related to the transactions.
         * This depends on the type of transaction.
         */
        switch ($data['what']) {
            case 'withdrawal':
                $data['from'] = $accountRepository->find($data['account_id']);
                $data['to']   = $accountRepository->firstExpenseAccountOrCreate($data['expense_account']);
                break;
            case 'opening':
                break;
            case 'deposit':
                $data['to']   = $accountRepository->find($data['account_id']);
                $data['from'] = $accountRepository->firstRevenueAccountOrCreate($data['revenue_account']);
                break;
            case 'transfer':
                $data['from'] = $accountRepository->find($data['account_from_id']);
                $data['to']   = $accountRepository->find($data['account_to_id']);
                break;

            default:
                throw new FireflyException('Cannot save transaction journal with accounts based on $what "' . $data['what'] . '".');
                break;
        }

        /*
         *  Then store both transactions.
         */
        $first    = ['account' => $data['from'], 'transaction_journal' => $journal, 'amount' => ($data['amount'] * -1),];
        $validate = $transactionRepository->validate($first);
        if ($validate['errors']->count() == 0) {
            $transactionRepository->store($first);
        } else {
            throw new FireflyException($validate['errors']->first());
        }

        $second = ['account' => $data['to'], 'transaction_journal' => $journal, 'amount' => floatval($data['amount']),];

        $validate = $transactionRepository->validate($second);
        if ($validate['errors']->count() == 0) {
            $transactionRepository->store($second);
        } else {
            throw new FireflyException($validate['errors']->first());
        }

        /*
         * Store the budget.
         */
        if(isset($data['budget_id']) && intval($data['budget_id']) > 0) {
            /** @var \FireflyIII\Database\Budget $budgetRepository */
            $budgetRepository = \App::make('FireflyIII\Database\Budget');
            $budget = $budgetRepository->find(intval($data['budget_id']));
            if($budget) {
                $journal->budgets()->save($budget);
            }
        }
        if(strlen($data['category']) > 0) {
            /** @var \FireflyIII\Database\Category $categoryRepository */
            $categoryRepository = \App::make('FireflyIII\Database\Category');
            $category = $categoryRepository->firstOrCreate($data['category']);
            if($category) {
                $journal->categories()->save($category);
            }
        }

        $journal->completed = 1;
        $journal->save();

        /*
         * Trigger a search for a relevant recurring transaction.
         */


        return $journal;
    }

    /**
     * @param Ardent $model
     * @param array  $data
     *
     * @return bool
     */
    public function update(Ardent $model, array $data)
    {
        var_dump($data);
        /** @var \FireflyIII\Database\TransactionType $typeRepository */
        $typeRepository = \App::make('FireflyIII\Database\TransactionType');

        /** @var \FireflyIII\Database\Account $accountRepository */
        $accountRepository = \App::make('FireflyIII\Database\Account');

        /** @var \FireflyIII\Database\TransactionCurrency $currencyRepository */
        $currencyRepository = \App::make('FireflyIII\Database\TransactionCurrency');

        /** @var \FireflyIII\Database\Transaction $transactionRepository */
        $transactionRepository = \App::make('FireflyIII\Database\Transaction');

        $journalType = $typeRepository->findByWhat($data['what']);
        $currency    = $currencyRepository->findByCode($data['currency']);

        $model->transactionType()->associate($journalType);
        $model->transactionCurrency()->associate($currency);
        $model->user()->associate($this->getUser());
        $model->description = $data['description'];
        $model->date        = $data['date'];

        /*
         * This must be enough to store the journal:
         */
        if (!$model->validate()) {
            \Log::error($model->errors()->all());
            throw new FireflyException('store() transaction journal failed, but it should not!');
        }
        $model->save();

        /*
         * Still need to find the accounts related to the transactions.
         * This depends on the type of transaction.
         */
        switch ($data['what']) {
            case 'withdrawal':
                $data['from'] = $accountRepository->find($data['account_id']);
                $data['to']   = $accountRepository->firstExpenseAccountOrCreate($data['expense_account']);
                break;
            case 'opening':
                break;
            case 'deposit':
                $data['to']   = $accountRepository->find($data['account_id']);
                $data['from'] = $accountRepository->firstRevenueAccountOrCreate($data['revenue_account']);
                break;
            case 'transfer':
                $data['from'] = $accountRepository->find($data['account_from_id']);
                $data['to']   = $accountRepository->find($data['account_to_id']);
                break;

            default:
                throw new FireflyException('Cannot save transaction journal with accounts based on $what "' . $data['what'] . '".');
                break;
        }

        /*
         * Store the budget.
         */
        if(isset($data['budget_id']) && intval($data['budget_id']) > 0) {
            /** @var \FireflyIII\Database\Budget $budgetRepository */
            $budgetRepository = \App::make('FireflyIII\Database\Budget');
            $budget = $budgetRepository->find(intval($data['budget_id']));
            if($budget) {
                $model->budgets()->sync([$budget->id]);
            }
        }
        if(strlen($data['category']) > 0) {
            /** @var \FireflyIII\Database\Category $categoryRepository */
            $categoryRepository = \App::make('FireflyIII\Database\Category');
            $category = $categoryRepository->firstOrCreate($data['category']);
            if($category) {
                $model->categories()->sync([$category->id]);
            }
        }

        /*
         * Now we can update the transactions related to this journal.
         */
        $amount = floatval($data['amount']);
        /** @var \Transaction $transaction */
        foreach ($model->transactions()->get() as $transaction) {
            if (floatval($transaction->amount) > 0) {
                // the TO transaction.
                $transaction->account()->associate($data['to']);
                $transaction->amount = $amount;
            } else {
                $transaction->account()->associate($data['from']);
                $transaction->amount = $amount * -1;
            }
            if (!$transaction->validate()) {
                throw new FireflyException('Could not validate transaction while saving.');
            }
            $transaction->save();
        }

        return new MessageBag;
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model)
    {

        $warnings  = new MessageBag;
        $successes = new MessageBag;
        $errors    = new MessageBag;


        if (!isset($model['what'])) {
            $errors->add('description', 'Internal error: need to know type of transaction!');
        }
        if (isset($model['recurring_transaction_id']) && intval($model['recurring_transaction_id']) < 0) {
            $errors->add('recurring_transaction_id', 'Recurring transaction is invalid.');
        }
        if (!isset($model['description'])) {
            $errors->add('description', 'This field is mandatory.');
        }
        if (isset($model['description']) && strlen($model['description']) == 0) {
            $errors->add('description', 'This field is mandatory.');
        }
        if (isset($model['description']) && strlen($model['description']) > 255) {
            $errors->add('description', 'Description is too long.');
        }

        if (!isset($model['currency'])) {
            $errors->add('description', 'Internal error: currency is mandatory!');
        }
        if (isset($model['date']) && !($model['date'] instanceof Carbon) && strlen($model['date']) > 0) {
            try {
                new Carbon($model['date']);
            } catch (\Exception $e) {
                $errors->add('date', 'This date is invalid.');
            }
        }
        if (!isset($model['date'])) {
            $errors->add('date', 'This date is invalid.');
        }

        /*
         * Amount:
         */
        if (isset($model['amount']) && floatval($model['amount']) < 0.01) {
            $errors->add('amount', 'Amount must be > 0.01');
        } else {
            if (!isset($model['amount'])) {
                $errors->add('amount', 'Amount must be set!');
            } else {
                $successes->add('amount', 'OK');
            }
        }

        /*
         * Budget:
         */
        if (isset($model['budget_id']) && !ctype_digit($model['budget_id'])) {
            $errors->add('budget_id', 'Invalid budget');
        } else {
            $successes->add('budget_id', 'OK');
        }

        $successes->add('category', 'OK');

        /*
         * Many checks to catch invalid or not-existing accounts.
         */
        $accountError = false;
        switch (true) {
            // this combination is often seen in withdrawals.
            case (isset($model['account_id']) && isset($model['expense_account'])):
                if (intval($model['account_id']) < 1) {
                    $errors->add('account_id', 'Invalid account.');
                } else {
                    $successes->add('account_id', 'OK');
                }
                $successes->add('expense_account', 'OK');
                break;
            case (isset($model['account_id']) && isset($model['revenue_account'])):
                if (intval($model['account_id']) < 1) {
                    $errors->add('account_id', 'Invalid account.');
                } else {
                    $successes->add('account_id', 'OK');
                }
                $successes->add('revenue_account', 'OK');
                break;
            case (isset($model['account_from_id']) && isset($model['account_to_id'])):
                if (intval($model['account_from_id']) < 1 || intval($model['account_from_id']) < 1) {
                    $errors->add('account_from_id', 'Invalid account selected.');
                    $errors->add('account_to_id', 'Invalid account selected.');

                } else {
                    if (intval($model['account_from_id']) == intval($model['account_to_id'])) {
                        $errors->add('account_to_id', 'Cannot be the same as "from" account.');
                        $errors->add('account_from_id', 'Cannot be the same as "to" account.');
                    } else {
                        $successes->add('account_from_id', 'OK');
                        $successes->add('account_to_id', 'OK');
                    }
                }
                break;

            case (isset($model['to']) && isset($model['from'])):
                if (is_object($model['to']) && is_object($model['from'])) {
                    $successes->add('from', 'OK');
                    $successes->add('to', 'OK');
                }
                break;

            default:
                throw new FireflyException('Cannot validate accounts for transaction journal.');
                break;
        }

        //        if (isset($model['to_id']) && intval($model['to_id']) < 1) {
        //            $errors->add('account_to', 'Invalid to-account');
        //        }
        //
        //        if (isset($model['from_id']) && intval($model['from_id']) < 1) {
        //            $errors->add('account_from', 'Invalid from-account');
        //
        //        }
        //        if (isset($model['account_id']) && intval($model['account_id']) < 1) {
        //            $errors->add('account_id', 'Invalid account!');
        //        }
        //        if (isset($model['to']) && !($model['to'] instanceof \Account)) {
        //            $errors->add('account_to', 'Invalid to-account');
        //        }
        //        if (isset($model['from']) && !($model['from'] instanceof \Account)) {
        //            $errors->add('account_from', 'Invalid from-account');
        //        }
        //        if (!isset($model['amount']) || (isset($model['amount']) && floatval($model['amount']) < 0)) {
        //            $errors->add('amount', 'Invalid amount');
        //        }


        $validator = \Validator::make([$model], \Transaction::$rules);
        if ($validator->invalid()) {
            $errors->merge($errors);
        }


        /*
         * Add "OK"
         */
        if (!$errors->has('description')) {
            $successes->add('description', 'OK');
        }
        if (!$errors->has('date')) {
            $successes->add('date', 'OK');
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];


    }

    /**
     * Validates a model. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * @param Ardent $model
     *
     * @return array
     */
    public function validateObject(Ardent $model)
    {
        // TODO: Implement validateObject() method.
        throw new NotImplementedException;
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $id
     *
     * @return Ardent
     */
    public function find($id)
    {
        return $this->getUser()->transactionjournals()->find($id);
    }

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     */
    public function findByWhat($what)
    {
        // TODO: Implement findByWhat() method.
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return $this->getUser()->transactionjournals()->get();
    }

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids)
    {
        return $this->getUser()->transactionjournals()->with('transactions')->whereIn('id', $ids)->orderBy('date', 'ASC')->get();
    }

    /**
     * @return TransactionJournal
     */
    public function first()
    {
        return $this->getUser()->transactionjournals()->orderBy('date', 'ASC')->first();
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getInDateRange(Carbon $start, Carbon $end)
    {
        return $this->getuser()->transactionjournals()->withRelevantData()->before($end)->after($start)->get();
    }

    /**
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfExpensesByMonth(Carbon $date)
    {
        $end = clone $date;
        $date->startOfMonth();
        $end->endOfMonth();

        $sum = \DB::table('transactions')->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')->leftJoin(
            'transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id'
        )->where('amount', '>', 0)->where('transaction_types.type', '=', 'Withdrawal')->where('transaction_journals.date', '>=', $date->format('Y-m-d'))->where(
            'transaction_journals.date', '<=', $end->format('Y-m-d')
        )->sum('transactions.amount');
        $sum = floatval($sum);

        return $sum;
    }

    /**
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfIncomesByMonth(Carbon $date)
    {
        $end = clone $date;
        $date->startOfMonth();
        $end->endOfMonth();

        $sum = \DB::table('transactions')->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')->leftJoin(
            'transaction_types', 'transaction_journals.transaction_type_id', '=', 'transaction_types.id'
        )->where('amount', '>', 0)->where('transaction_types.type', '=', 'Deposit')->where('transaction_journals.date', '>=', $date->format('Y-m-d'))->where(
            'transaction_journals.date', '<=', $end->format('Y-m-d')
        )->sum('transactions.amount');
        $sum = floatval($sum);

        return $sum;
    }

    /**
     * @param \Account $account
     * @param int      $count
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return Collection
     */
    public function getInDateRangeAccount(\Account $account, $count = 20, Carbon $start, Carbon $end)
    {

        $accountID = $account->id;
        $query     = $this->_user->transactionjournals()->with(['transactions', 'transactioncurrency', 'transactiontype'])->leftJoin(
            'transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id'
        )->leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')->where('accounts.id', $accountID)->where(
            'date', '>=', $start->format('Y-m-d')
        )->where('date', '<=', $end->format('Y-m-d'))->orderBy('transaction_journals.date', 'DESC')->orderBy('transaction_journals.id', 'DESC')->take(
            $count
        )->get(['transaction_journals.*']);

        return $query;
    }

    public function getWithdrawalsPaginated($limit = 50) {
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;

        $set    = $this->getUser()->transactionJournals()->transactionTypes(['Withdrawal'])->withRelevantData()->take($limit)->offset($offset)->orderBy('date', 'DESC')->get(['transaction_journals.*']);
        $count  = $this->getUser()->transactionJournals()->transactionTypes(['Withdrawal'])->count();
        $items  = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }

    public function getDepositsPaginated($limit = 50) {
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;

        $set    = $this->getUser()->transactionJournals()->transactionTypes(['Deposit'])->withRelevantData()->take($limit)->offset($offset)->orderBy('date', 'DESC')->get(['transaction_journals.*']);
        $count  = $this->getUser()->transactionJournals()->transactionTypes(['Deposit'])->count();
        $items  = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }

    public function getTransfersPaginated($limit = 50) {
        $offset = intval(\Input::get('page')) > 0 ? intval(\Input::get('page')) * $limit : 0;

        $set    = $this->getUser()->transactionJournals()->transactionTypes(['Transfer'])->withRelevantData()->take($limit)->offset($offset)->orderBy('date', 'DESC')->get(['transaction_journals.*']);
        $count  = $this->getUser()->transactionJournals()->transactionTypes(['Transfer'])->count();
        $items  = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }
}