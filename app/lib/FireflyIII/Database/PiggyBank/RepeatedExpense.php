<?php

namespace FireflyIII\Database\PiggyBank;


use FireflyIII\Collection\PiggyBankPart;
use FireflyIII\Database\CommonDatabaseCallsInterface;
use FireflyIII\Database\CUDInterface;
use Illuminate\Support\Collection;

/**
 * Class RepeatedExpense
 *
 * @package FireflyIII\Database
 */
class RepeatedExpense extends PiggyBankShared implements CUDInterface, CommonDatabaseCallsInterface, PiggyBankInterface
{

    /**
     * Based on the piggy bank, the reminder-setting and
     * other variables this method tries to divide the piggy bank into equal parts. Each is
     * accommodated by a reminder (if everything goes to plan).
     *
     * @param \PiggyBankRepetition $repetition
     *
     * @return Collection
     */
    public function calculateParts(\PiggyBankRepetition $repetition)
    {
        /** @var \PiggyBank $piggyBank */
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
            $currentTarget = \DateKit::endOfX($currentStart, $piggyBank->reminder, $repetition->targetdate);
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
     * @param array $data
     *
     * @return \Eloquent
     */
    public function store(array $data)
    {

        $data['rep_every']     = intval($data['rep_every']);
        $data['reminder_skip'] = intval($data['reminder_skip']);
        $data['order']         = intval($data['order']);
        $data['remind_me']     = intval($data['remind_me']);
        $data['account_id']    = intval($data['account_id']);


        if ($data['remind_me'] == 0) {
            $data['reminder'] = null;
        }

        $repeated = new \PiggyBank($data);
        $repeated->save();

        return $repeated;
    }

    /**
     * Returns all objects.
     *
     * @return Collection
     */
    public function get()
    {
        return $this->getUser()->piggyBanks()->where('repeats', 1)->get();
    }

}
