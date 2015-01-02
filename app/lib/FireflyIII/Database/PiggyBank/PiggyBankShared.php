<?php

namespace FireflyIII\Database\PiggyBank;

use FireflyIII\Database\SwitchUser;
use FireflyIII\Exception\NotImplementedException;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Class PiggyBankShared
 *
 * @package FireflyIII\Database\PiggyBank
 */
class PiggyBankShared
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
        $reminders = \Reminder::where('remindersable_id', $model->id)->get();
        /** @var \Reminder $reminder */
        foreach ($reminders as $reminder) {
            $reminder->delete();
        }
        $model->delete();

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
        return \PiggyBank::
        leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('piggy_banks.id', '=', $objectId)->where(
            'accounts.user_id', $this->getUser()->id
        )
                         ->first(['piggy_banks.*']);
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
     * @param array $ids
     *
     * @return Collection
     * @throws NotImplementedException
     */
    public function getByIds(array $ids)
    {
        return \PiggyBank::
        leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->whereIn('piggy_banks.id', [$ids])->where(
            'accounts.user_id', $this->getUser()->id
        )
                         ->first(['piggy_banks.*']);
    }

    /**
     * @param \Account $account
     *
     * @return float
     */
    public function leftOnAccount(\Account $account)
    {
        \Log::debug('Now in leftOnAccount() for account #' . $account->id . ' (' . $account->name . ')');
        $balance = \Steam::balance($account);
        \Log::debug('Steam says: ' . $balance);
        /** @var \PiggyBank $p */
        foreach ($account->piggyBanks()->get() as $p) {
            $balance -= $p->currentRelevantRep()->currentamount;
        }

        return $balance;

    }

    /**
     * @param array $data
     *
     * @return \Eloquent
     * @throws FireflyException
     */
    public function store(array $data)
    {
        if (!isset($data['remind_me']) || (isset($data['remind_me']) && $data['remind_me'] == 0)) {
            $data['reminder'] = null;
        }
        $piggyBank = new \PiggyBank($data);
        $piggyBank->save();

        return $piggyBank;
    }


    /**
     * @param Eloquent $model
     * @param array    $data
     *
     * @return bool
     */
    public function update(Eloquent $model, array $data)
    {
        /** @var \PiggyBank $model */
        $model->name          = $data['name'];
        $model->account_id    = intval($data['account_id']);
        $model->targetamount  = floatval($data['targetamount']);
        $model->targetdate    = isset($data['targetdate']) && $data['targetdate'] != '' ? $data['targetdate'] : null;
        $model->rep_every     = intval($data['rep_every']);
        $model->reminder_skip = intval($data['reminder_skip']);
        $model->order         = intval($data['order']);
        $model->remind_me     = intval($data['remind_me']);
        $model->reminder      = isset($data['reminder']) ? $data['reminder'] : 'month';

        if ($model->remind_me == 0) {
            $model->reminder = null;
        }

        $model->save();

        return true;
    }

    /**
     * Validates an array. Returns an array containing MessageBags
     * errors/warnings/successes.
     *
     * Ignore PHPMD rules because Laravel 5.0 will make this method superfluous anyway.
     *
     * @param array $model
     *
     * @return array
     */
    public function validate(array $model)
    {
        $warnings  = new MessageBag;
        $successes = new MessageBag;
        $model     = new \PiggyBank($model);
        $model->isValid();
        $errors = $model->getErrors();

        // add ok messages.
        $list = ['name', 'account_id', 'targetamount', 'targetdate', 'remind_me', 'reminder'];
        foreach ($list as $entry) {
            if (!$errors->has($entry) && !$warnings->has($entry)) {
                $successes->add($entry, 'OK');
            }
        }


        return ['errors' => $errors, 'warnings' => $warnings, 'successes' => $successes];
    }

}
