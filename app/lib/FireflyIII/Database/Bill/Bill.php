<?php

namespace FireflyIII\Database\Bill;


use Carbon\Carbon;
use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class Bill
 *
 * @package FireflyIII\Database
 */
class Bill implements CUDInterface, CommonDatabaseCallsInterface, BillInterface
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
        $bill = new \Bill;
        $bill->user()->associate($this->getUser());
        $bill->name       = $data['name'];
        $bill->match      = $data['match'];
        $bill->amount_max = floatval($data['amount_max']);
        $bill->amount_min = floatval($data['amount_min']);

        $date = new Carbon($data['date']);


        $bill->active      = intval($data['active']);
        $bill->automatch   = intval($data['automatch']);
        $bill->repeat_freq = $data['repeat_freq'];

        /*
         * Jump to the start of the period.
         */
        $date       = \DateKit::startOfPeriod($date, $data['repeat_freq']);
        $bill->date = $date;
        $bill->skip = intval($data['skip']);

        $bill->save();

        return $bill;
    }

    /**
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     */
    public function update(Eloquent $model, array $data)
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
        if (isset($model['amount_min']) && isset($model['amount_max']) && floatval($model['amount_min']) > floatval($model['amount_max'])) {
            $errors->add('amount_max', 'Maximum amount can not be less than minimum amount.');
            $errors->add('amount_min', 'Minimum amount can not be more than maximum amount.');
        }
        $object = new \Bill($model);
        $object->isValid();
        $errors->merge($object->getErrors());

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
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return $this->getUser()->bills()->get();
    }

    /**
     * @param array $ids
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function getByIds(array $ids)
    {
        throw new NotImplementedException;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function getActive()
    {
        return $this->getUser()->bills()->where('active', 1)->get();
    }

    /**
     * @param \Bill  $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return \TransactionJournal|null
     */
    public function getJournalForBillInRange(\Bill $bill, Carbon $start, Carbon $end)
    {
        return $this->getUser()->transactionjournals()->where('bill_id', $bill->id)->after($start)->before($end)->first();

    }

    /**
     * @param \Bill               $bill
     * @param \TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(\Bill $bill, \TransactionJournal $journal)
    {
        /*
         * Match words.
         */
        $wordMatch   = false;
        $matches     = explode(',', $bill->match);
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
            $min    = floatval($bill->amount_min);
            $max    = floatval($bill->amount_max);
            if ($amount >= $min && $amount <= $max) {
                $amountMatch = true;
                \Log::debug('Amount match is true!');
            }
        }

        /*
         * If both, update!
         */
        if ($wordMatch && $amountMatch) {
            $journal->bill()->associate($bill);
            $journal->save();
        }
    }

    /**
     * @param \Bill $bill
     *
     * @return bool
     */
    public function scanEverything(\Bill $bill)
    {
        // get all journals that (may) be relevant.
        // this is usually almost all of them.

        /** @var \FireflyIII\Database\TransactionJournal\TransactionJournal $journalRepository */
        $journalRepository = \App::make('FireflyIII\Database\TransactionJournal\TransactionJournal');

        $set = \DB::table('transactions')->where('amount', '>', 0)->where('amount', '>=', $bill->amount_min)->where('amount', '<=', $bill->amount_max)
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
                $this->scan($bill, $journal);
            }
        }

        return true;
    }
}
