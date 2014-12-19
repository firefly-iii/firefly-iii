<?php

namespace FireflyIII\Database\RecurringTransaction;


use Carbon\Carbon;
use FireflyIII\Database\CommonDatabaseCalls;
use FireflyIII\Database\CUD;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class Recurring
 *
 * @package FireflyIII\Database
 */
class RecurringTransaction implements CUD, CommonDatabaseCalls, RecurringTransactionInterface
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
     * @param \Eloquent $model
     *
     * @return bool
     */
    public function destroy(\Eloquent $model)
    {
        $model->delete();

        return true;
    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     */
    public function store(array $data)
    {
        $recurring = new \RecurringTransaction;
        $recurring->user()->associate($this->getUser());
        $recurring->name       = $data['name'];
        $recurring->match      = $data['match'];
        $recurring->amount_max = floatval($data['amount_max']);
        $recurring->amount_min = floatval($data['amount_min']);

        $date = new Carbon($data['date']);


        $recurring->active      = intval($data['active']);
        $recurring->automatch   = intval($data['automatch']);
        $recurring->repeat_freq = $data['repeat_freq'];

        /*
         * Jump to the start of the period.
         */
        $date            = \DateKit::startOfPeriod($date, $data['repeat_freq']);
        $recurring->date = $date;
        $recurring->skip = intval($data['skip']);

        $recurring->save();

        return $recurring;
    }

    /**
     * @param \Eloquent $model
     * @param array     $data
     *
     * @return bool
     */
    public function update(\Eloquent $model, array $data)
    {
        $model->name       = $data['name'];
        $model->match      = $data['match'];
        $model->amount_max = floatval($data['amount_max']);
        $model->amount_min = floatval($data['amount_min']);

        $date = new Carbon($data['date']);

        $model->date        = $date;
        $model->active      = intval($data['active']);
        $model->automatch   = intval($data['automatch']);
        $model->repeat_freq = $data['repeat_freq'];
        $model->skip        = intval($data['skip']);
        $model->save();

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
     */
    public function validate(array $model)
    {
        $warnings  = new MessageBag;
        $successes = new MessageBag;
        $errors    = new MessageBag;

        if (isset($model['name']) && strlen($model['name']) == 0) {
            $errors->add('name', 'Name must be longer.');
        }
        if (isset($model['name']) && strlen($model['name']) > 200) {
            $errors->add('name', 'Name must be shorter.');
        }

        if (isset($model['match']) && strlen(trim($model['match'])) <= 2) {
            $errors->add('match', 'Needs more matches.');
        }

        if (isset($model['amount_min']) && floatval($model['amount_min']) < 0.01) {
            $errors->add('amount_min', 'Minimum amount must be higher.');
        }
        if (isset($model['amount_max']) && floatval($model['amount_max']) < 0.02) {
            $errors->add('amount_max', 'Maximum amount must be higher.');
        }
        if (isset($model['amount_min']) && isset($model['amount_max']) && floatval($model['amount_min']) > floatval($model['amount_max'])) {
            $errors->add('amount_max', 'Maximum amount can not be less than minimum amount.');
            $errors->add('amount_min', 'Minimum amount can not be more than maximum amount.');
        }

        if ($model['date'] != '') {
            try {
                new Carbon($model['date']);
            } catch (\Exception $e) {
                $errors->add('date', 'Invalid date.');
            }
        }

        $reminders = \Config::get('firefly.budget_periods');
        if (!isset($model['repeat_freq']) || (isset($model['repeat_freq']) && !in_array($model['repeat_freq'], $reminders))) {
            $errors->add('repeat_freq', 'Invalid reminder period');
        }

        if (isset($model['skip']) && intval($model['skip']) < 0) {
            $errors->add('skip', 'Invalid skip.');
        }

        $set = ['name', 'match', 'amount_min', 'amount_max', 'date', 'repeat_freq', 'skip', 'automatch', 'active'];
        foreach ($set as $entry) {
            if (!$errors->has($entry)) {
                $successes->add($entry, 'OK');
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
    }

    /**
     * Returns an object with id $id.
     *
     * @param int $objectId
     *
     * @return \Eloquent
     * @throws NotImplementedException
     */
    public function find($objectId)
    {
        // TODO: Implement find() method.
        throw new NotImplementedException;
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
        return $this->getUser()->recurringtransactions()->get();
    }

    /**
     * @param array $ids
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function getByIds(array $ids)
    {
        // TODO: Implement getByIds() method.
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function getActive()
    {
        return $this->getUser()->recurringtransactions()->where('active', 1)->get();
    }

    /**
     * @param \RecurringTransaction $recurring
     * @param Carbon                $start
     * @param Carbon                $end
     *
     * @return \TransactionJournal|null
     */
    public function getJournalForRecurringInRange(\RecurringTransaction $recurring, Carbon $start, Carbon $end)
    {
        return $this->getUser()->transactionjournals()->where('recurring_transaction_id', $recurring->id)->after($start)->before($end)->first();

    }

    /**
     * @param \RecurringTransaction $recurring
     * @param \TransactionJournal   $journal
     *
     * @return bool
     */
    public function scan(\RecurringTransaction $recurring, \TransactionJournal $journal)
    {
        /*
         * Match words.
         */
        $wordMatch   = false;
        $matches     = explode(',', $recurring->match);
        $description = strtolower($journal->description);

        /*
         * Attach expense account to description for more narrow matching.
         */
        if (count($journal->transactions) < 2) {
            $transactions = $journal->transactions()->get();
        } else {
            $transactions = $journal->transactions;
        }
        /** @var \Transaction $transaction */
        foreach ($transactions as $transaction) {
            /** @var \Account $account */
            $account = $transaction->account()->first();
            /** @var \AccountType $type */
            $type = $account->accountType()->first();
            if ($type->type == 'Expense account' || $type->type == 'Beneficiary account') {
                $description .= ' ' . strtolower($account->name);
            }
        }
        \Log::debug('Final description: ' . $description);
        \Log::debug('Matches searched: ' . join(':', $matches));

        $count = 0;
        foreach ($matches as $word) {
            if (!(strpos($description, strtolower($word)) === false)) {
                $count++;
            }
        }
        if ($count >= count($matches)) {
            $wordMatch = true;
            \Log::debug('word match is true');
        }


        /*
         * Match amount.
         */

        $amountMatch = false;
        if (count($transactions) > 1) {

            $amount = max(floatval($transactions[0]->amount), floatval($transactions[1]->amount));
            $min    = floatval($recurring->amount_min);
            $max    = floatval($recurring->amount_max);
            if ($amount >= $min && $amount <= $max) {
                $amountMatch = true;
                \Log::debug('Amount match is true!');
            }
        }

        /*
         * If both, update!
         */
        if ($wordMatch && $amountMatch) {
            $journal->recurringTransaction()->associate($recurring);
            $journal->save();
        }
    }

    /**
     * @param \RecurringTransaction $recurring
     *
     * @return bool
     */
    public function scanEverything(\RecurringTransaction $recurring)
    {
        // get all journals that (may) be relevant.
        // this is usually almost all of them.

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $journalRepository */
        $journalRepository = \App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        $set = \DB::table('transactions')->where('amount', '>', 0)->where('amount', '>=', $recurring->amount_min)->where('amount', '<=', $recurring->amount_max)
                  ->get(['transaction_journal_id']);
        $ids = [];

        /** @var \Transaction $entry */
        foreach ($set as $entry) {
            $ids[] = intval($entry->transaction_journal_id);
        }
        if (count($ids) > 0) {
            $journals = $journalRepository->getByIds($ids);
            /** @var \TransactionJournal $journal */
            foreach ($journals as $journal) {
                $this->scan($recurring, $journal);
            }
        }

        return true;
    }
}