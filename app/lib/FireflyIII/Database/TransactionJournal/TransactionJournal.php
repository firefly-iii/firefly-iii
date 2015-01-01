<?php

namespace FireflyIII\Database\TransactionJournal;


use Carbon\Carbon;
use FireflyIII\Database\CommonDatabaseCalls;
use FireflyIII\Database\CUD;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\FireflyException;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

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
     * @param Eloquent $model
     *
     * @return bool
     */
    public function destroy(Eloquent $model)
    {
        /*
         * Trigger deletion.
         */
        \Event::fire('transactionJournal.destroy', [$model]); // new and used.
        /*
         * Since this event will also destroy both transactions, trigger on those as
         * well because we might want to update some caches and what-not.
         */
        /** @var Transaction $transaction */
        foreach ($model->transactions as $transaction) {
            \Event::fire('transaction.destroy', [$transaction]);
        }

        $model->delete();

        return true;
    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     * @throws FireflyException
     */
    public function store(array $data)
    {
        $currency = $this->getJournalCurrency($data['currency']);
        $journal  = new \TransactionJournal(
            [
                'transaction_type_id'     => $data['transaction_type_id'],
                'transaction_currency_id' => $currency->id, 'user_id' => $this->getUser()->id,
                'description'             => $data['description'], 'date' => $data['date'], 'completed' => 0]
        );
        $journal->save();

        list($fromAccount, $toAccount) = $this->storeAccounts($data);

        $this->storeTransaction(['account' => $fromAccount, 'transaction_journal' => $journal, 'amount' => floatval($data['amount'] * -1)]);
        $this->storeTransaction(['account' => $toAccount, 'transaction_journal' => $journal, 'amount' => floatval($data['amount'])]);
        $this->storeBudget($data, $journal);
        $this->storeCategory($data, $journal);

        $journal->completed = 1;
        $journal->save();

        return $journal;
    }

    /**
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     * @throws FireflyException
     */
    public function update(Eloquent $model, array $data)
    {
        $journalType        = $this->getJournalType($data['what']);
        $currency           = $this->getJournalCurrency($data['currency']);
        $model->description = $data['description'];
        $model->date        = $data['date'];

        $model->transactionType()->associate($journalType);
        $model->transactionCurrency()->associate($currency);
        $model->user()->associate($this->getUser());
        $model->save();

        list($fromAccount, $toAccount) = $this->storeAccounts($data);

        $this->storeBudget($data, $model);
        $this->storeCategory($data, $model);

        /*
         * Now we can update the transactions related to this journal.
         */
        $amount = floatval($data['amount']);
        /** @var \Transaction $transaction */
        foreach ($model->transactions()->get() as $transaction) {
            if (floatval($transaction->amount) > 0) {
                // the TO transaction.
                $transaction->account()->associate($toAccount);
                $transaction->amount = $amount;
            } else {
                $transaction->account()->associate($fromAccount);
                $transaction->amount = $amount * -1;
            }
            if (!$transaction->isValid()) {
                throw new FireflyException('Could not validate transaction while saving.');
            }
            $transaction->save();
        }

        return true;
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * ignored because this method will be gone soon.
     *
     * @param array $model
     *
     * @return array
     * @throws FireflyException
     */
    public function validate(array $model)
    {

        $warnings  = new MessageBag;
        $successes = new MessageBag;

        $journal = new \TransactionJournal($model);
        $journal->isValid();
        $errors = $journal->getErrors();

        if (!isset($model['what'])) {
            $errors->add('description', 'Internal error: need to know type of transaction!');
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
     * @param $currency
     *
     * @return null|\TransactionCurrency
     */
    public function getJournalCurrency($currency)
    {
        /** @var \FireflyIII\Database\TransactionCurrency\TransactionCurrency $currencyRepository */
        $currencyRepository = \App::make('FireflyIII\Database\TransactionCurrency\TransactionCurrency');

        return $currencyRepository->findByCode($currency);
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws FireflyException
     */
    public function storeAccounts(array $data)
    {
        /** @var \FireflyIII\Database\Account\Account $accountRepository */
        $accountRepository = \App::make('FireflyIII\Database\Account\Account');
        $fromAccount       = null;
        $toAccount         = null;

        switch ($data['what']) {
            case 'withdrawal':
                $fromAccount = $accountRepository->find($data['account_id']);
                $toAccount   = $accountRepository->firstExpenseAccountOrCreate($data['expense_account']);
                break;
            case 'opening':
                break;
            case 'deposit':
                $fromAccount = $accountRepository->firstRevenueAccountOrCreate($data['revenue_account']);
                $toAccount   = $accountRepository->find($data['account_id']);
                break;
            case 'transfer':
                $fromAccount = $accountRepository->find($data['account_from_id']);
                $toAccount   = $accountRepository->find($data['account_to_id']);
                break;

            default:
                throw new FireflyException('Cannot save transaction journal with accounts based on $what "' . $data['what'] . '".');
                break;
        }

        return [$fromAccount, $toAccount];
    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     * @throws FireflyException
     */
    public function storeTransaction($data)
    {

        /** @var \FireflyIII\Database\Transaction\Transaction $repository */
        $repository = \App::make('FireflyIII\Database\Transaction\Transaction');

        $errors = $repository->validate($data);
        if ($errors->count() > 0) {
            \Log::error('Could not store transaction: ' . $errors->toJson());
            throw new FireflyException('store() transaction failed, but it should not!');
        }

        return $repository->store($data);
    }

    /**
     * @param array                         $data
     * @param \TransactionJournal|\Eloquent $journal
     */
    public function storeBudget($data, \TransactionJournal $journal)
    {
        if (isset($data['budget_id']) && intval($data['budget_id']) > 0) {
            /** @var \FireflyIII\Database\Budget\Budget $budgetRepository */
            $budgetRepository = \App::make('FireflyIII\Database\Budget\Budget');
            $budget           = $budgetRepository->find(intval($data['budget_id']));
            if ($budget) {
                $journal->budgets()->sync([$budget->id]);
            }
        }
    }

    /**
     * @param array                         $data
     * @param \TransactionJournal|\Eloquent $journal
     */
    public function storeCategory(array $data, \TransactionJournal $journal)
    {
        if (isset($data['category']) && strlen($data['category']) > 0) {
            /** @var \FireflyIII\Database\Category\Category $categoryRepository */
            $categoryRepository = \App::make('FireflyIII\Database\Category\Category');
            $category           = $categoryRepository->firstOrCreate($data['category']);
            if ($category) {
                $journal->categories()->sync([$category->id]);
            }

            return;
        }
        $journal->categories()->sync([]);

        return;
    }

    /**
     * @param $type
     *
     * @return \TransactionType|null
     * @throws FireflyException
     */
    public function getJournalType($type)
    {
        /** @var \FireflyIII\Database\TransactionType\TransactionType $typeRepository */
        $typeRepository = \App::make('FireflyIII\Database\TransactionType\TransactionType');

        return $typeRepository->findByWhat($type);
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $objectId
     *
     * @return \Eloquent
     */
    public function find($objectId)
    {
        return $this->getUser()->transactionjournals()->find($objectId);
    }

    /**
     * Finds an account type using one of the "$what"'s: expense, asset, revenue, opening, etc.
     *
     * @param $what
     *
     * @return \AccountType|null
     * @throws NotImplementedException
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
        return $this->getUser()->transactionjournals()->with(['TransactionType', 'transactions', 'transactions.account', 'transactions.account.accountType'])
                    ->get();
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
     * @return Carbon
     */
    public function firstDate()
    {
        $journal = $this->first();
        if ($journal) {
            return $journal->date;
        }

        return Carbon::now();
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
        /** @var \FireflyIII\Report\ReportInterface $reportRepository */
        $reportRepository = \App::make('FireflyIII\Report\ReportInterface');

        $set = $reportRepository->getExpenseGroupedForMonth($date, 200);
        $sum = 0;
        foreach ($set as $entry) {
            $sum += $entry['amount'];
        }
        

        return $sum;
    }

    /**
     * TODO This query includes incomes to "shared" accounts which arent income.
     *
     * @param Carbon $date
     *
     * @return float
     */
    public function getSumOfIncomesByMonth(Carbon $date)
    {
        /** @var \FireflyIII\Report\ReportInterface $reportRepository */
        $reportRepository = \App::make('FireflyIII\Report\ReportInterface');

        $incomes = $reportRepository->getIncomeForMonth($date);
        $totalIn = 0;
        /** @var \TransactionJournal $entry */
        foreach ($incomes as $entry) {
            $totalIn += $entry->getAmount();
        }

        return $totalIn;
    }

    /**
     * @param int $limit
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getDepositsPaginated($limit = 50)
    {
        $offset = intval(\Input::get('page')) > 0 ? (intval(\Input::get('page')) - 1) * $limit : 0;

        $set   = $this->getUser()->transactionJournals()->transactionTypes(['Deposit'])->withRelevantData()->take($limit)->offset($offset)->orderBy(
            'date', 'DESC'
        )->get(['transaction_journals.*']);
        $count = $this->getUser()->transactionJournals()->transactionTypes(['Deposit'])->count();
        $items = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }

    /**
     * @param \Account $account
     * @param Carbon   $start
     * @param Carbon   $end
     * @param int      $count
     *
     * @return Collection
     */
    public function getInDateRangeAccount(\Account $account, Carbon $start, Carbon $end, $count = 20)
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

    /**
     * @param int $limit
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getTransfersPaginated($limit = 50)
    {
        $offset = intval(\Input::get('page')) > 0 ? (intval(\Input::get('page')) - 1) * $limit : 0;

        $set   = $this->getUser()->transactionJournals()->transactionTypes(['Transfer'])->withRelevantData()->take($limit)->offset($offset)->orderBy(
            'date', 'DESC'
        )->get(['transaction_journals.*']);
        $count = $this->getUser()->transactionJournals()->transactionTypes(['Transfer'])->count();
        $items = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }

    /**
     * @param int $limit
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function getWithdrawalsPaginated($limit = 50)
    {
        $offset = intval(\Input::get('page')) > 0 ? (intval(\Input::get('page')) - 1) * $limit : 0;

        $set   = $this->getUser()->transactionJournals()->transactionTypes(['Withdrawal'])->withRelevantData()->take($limit)->offset($offset)->orderBy(
            'date', 'DESC'
        )->get(['transaction_journals.*']);
        $count = $this->getUser()->transactionJournals()->transactionTypes(['Withdrawal'])->count();
        $items = [];
        foreach ($set as $entry) {
            $items[] = $entry;
        }

        return \Paginator::make($items, $count, $limit);
    }

    /**
     * @param string              $query
     * @param \TransactionJournal $journal
     *
     * @return Collection
     */
    public function searchRelated($query, \TransactionJournal $journal)
    {
        $start = clone $journal->date;
        $end   = clone $journal->date;
        $start->startOfMonth();
        $end->endOfMonth();

        // get already related transactions:
        $exclude = [$journal->id];
        foreach ($journal->transactiongroups()->get() as $group) {
            foreach ($group->transactionjournals() as $current) {
                $exclude[] = $current->id;
            }
        }
        $exclude = array_unique($exclude);

        $query = $this->getUser()->transactionjournals()
                      ->withRelevantData()
                      ->before($end)
                      ->after($start)
                      ->whereNotIn('id', $exclude)
                      ->where('description', 'LIKE', '%' . $query . '%')
                      ->get();

        return $query;
    }
}