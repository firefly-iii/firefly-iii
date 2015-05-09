<?php

namespace FireflyIII\Repositories\PiggyBank;

use Auth;
use Carbon\Carbon;
use DB;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use Illuminate\Support\Collection;
use Navigation;

/**
 * Class PiggyBankRepository
 *
 * @package FireflyIII\Repositories\PiggyBank
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{


    /**
     *
     * Based on the piggy bank, the reminder-setting and
     * other variables this method tries to divide the piggy bank into equal parts. Each is
     * accommodated by a reminder (if everything goes to plan).
     *
     * @param PiggyBankRepetition $repetition
     *
     * @return Collection
     */
    public function calculateParts(PiggyBankRepetition $repetition)
    {
        /** @var PiggyBank $piggyBank */
        $piggyBank    = $repetition->piggyBank()->first();
        $bars         = new Collection;
        $currentStart = clone $repetition->startdate;

        if (is_null($piggyBank->reminder)) {
            $entry = ['repetition'    => $repetition, 'amountPerBar' => floatval($piggyBank->targetamount),
                      'currentAmount' => floatval($repetition->currentamount), 'cumulativeAmount' => floatval($piggyBank->targetamount),
                      'startDate'     => clone $repetition->startdate, 'targetDate' => clone $repetition->targetdate];
            $bars->push($this->createPiggyBankPart($entry));

            return $bars;
        }

        while ($currentStart < $repetition->targetdate) {
            $currentTarget = Navigation::endOfX($currentStart, $piggyBank->reminder, $repetition->targetdate);
            $entry         = ['repetition'       => $repetition, 'amountPerBar' => null, 'currentAmount' => floatval($repetition->currentamount),
                              'cumulativeAmount' => null, 'startDate' => $currentStart, 'targetDate' => $currentTarget];
            $bars->push($this->createPiggyBankPart($entry));
            $currentStart = clone $currentTarget;
            $currentStart->addDay();

        }
        $amountPerBar = floatval($piggyBank->targetamount) / $bars->count();
        $cumulative   = $amountPerBar;
        /** @var PiggyBankPart $bar */
        foreach ($bars as $index => $bar) {
            $bar->setAmountPerBar($amountPerBar);
            $bar->setCumulativeAmount($cumulative);
            if ($bars->count() - 1 == $index) {
                $bar->setCumulativeAmount($piggyBank->targetamount);
            }
            $cumulative += $amountPerBar;
        }

        return $bars;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param           $amount
     *
     * @return bool
     */
    public function createEvent(PiggyBank $piggyBank, $amount)
    {
        PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

        return true;
    }

    /**
     * @param array $data
     *
     * @return PiggyBankPart
     */
    public function createPiggyBankPart(array $data)
    {
        $part = new PiggyBankPart;
        $part->setRepetition($data['repetition']);
        $part->setAmountPerBar($data['amountPerBar']);
        $part->setCurrentamount($data['currentAmount']);
        $part->setCumulativeAmount($data['cumulativeAmount']);
        $part->setStartdate($data['startDate']);
        $part->setTargetdate($data['targetDate']);

        return $part;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return bool
     */
    public function destroy(PiggyBank $piggyBank)
    {
        return $piggyBank->delete();
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEventSummarySet(PiggyBank $piggyBank)
    {
        return DB::table('piggy_bank_events')->where('piggy_bank_id', $piggyBank->id)->groupBy('date')->get(['date', DB::Raw('SUM(`amount`) AS `sum`')]);
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank)
    {
        return $piggyBank->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /**
     * @return Collection
     */
    public function getPiggyBanks()
    {
        /** @var Collection $set */
        $set = Auth::user()->piggyBanks()->orderBy('order', 'ASC')->get();

        return $set;
    }

    /**
     * Set all piggy banks to order 0.
     *
     * @return boolean
     */
    public function reset()
    {
        // split query to make it work in sqlite:
        $set = PiggyBank::
                 leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.id')
                 ->where('accounts.user_id', Auth::user()->id)->get(['piggy_banks.*']);
        foreach ($set as $e) {
            $e->order = 0;
            $e->save();
        }

        return true;
    }

    /**
     *
     * set id of piggy bank.
     *
     * @param int $id
     * @param int $order
     *
     * @return void
     */
    public function setOrder($id, $order)
    {
        $piggyBank = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('accounts.user_id', Auth::user()->id)
                              ->where('piggy_banks.id', $id)->first(['piggy_banks.*']);
        if ($piggyBank) {
            $piggyBank->order = $order;
            $piggyBank->save();
        }
    }

    /**
     * @param array $data
     *
     * @return PiggyBank
     */
    public function store(array $data)
    {
        $data['remind_me'] = isset($data['remind_me']) && $data['remind_me'] == '1' ? true : false;
        $piggyBank         = PiggyBank::create($data);

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param array     $data
     *
     * @return PiggyBank
     * @internal param PiggyBank $account
     */
    public function update(PiggyBank $piggyBank, array $data)
    {
        /**
         * 'rep_length'   => $request->get('rep_length'),
         * 'rep_every'    => intval($request->get('rep_every')),
         * 'rep_times'    => intval($request->get('rep_times')),
         * 'remind_me'    => intval($request->get('remind_me')) == 1 ? true : false ,
         * 'reminder'     => $request->get('reminder'),
         */

        $piggyBank->name         = $data['name'];
        $piggyBank->account_id   = intval($data['account_id']);
        $piggyBank->targetamount = floatval($data['targetamount']);
        $piggyBank->targetdate   = $data['targetdate'];
        $piggyBank->reminder     = $data['reminder'];
        $piggyBank->startdate    = $data['startdate'];
        $piggyBank->remind_me    = isset($data['remind_me']) && $data['remind_me'] == '1' ? 1 : 0;

        $piggyBank->save();

        return $piggyBank;
    }
}
