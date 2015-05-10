<?php

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\Reminder;

/**
 * Class PiggyBankPart
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Collection
 */
class PiggyBankPart
{
    /** @var  float */
    public $amountPerBar;
    /** @var  float */
    public $cumulativeAmount;
    /** @var  float */
    public $currentamount;
    /** @var  Reminder */
    public $reminder;

    /** @var  PiggyBankRepetition */
    public $repetition;

    /** @var  Carbon */
    public $startdate;

    /** @var  Carbon */
    public $targetdate;

    /**
     * @return Reminder
     */
    public function getReminder()
    {
        if (is_null($this->reminder)) {
            $this->reminder = $this->repetition->piggyBank->reminders()->where('startdate', $this->getStartdate()->format('Y-m-d'))->where(
                'enddate', $this->getTargetdate()->format('Y-m-d')
            )->first();
        }

        return $this->reminder;
    }

    /**
     * @param Reminder $reminder
     */
    public function setReminder($reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * @return Carbon
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * @param Carbon $startdate
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;
    }

    /**
     * @return Carbon
     */
    public function getTargetdate()
    {
        return $this->targetdate;
    }

    /**
     * @param Carbon $targetdate
     */
    public function setTargetdate($targetdate)
    {
        $this->targetdate = $targetdate;
    }

    /**
     * @return PiggyBankRepetition
     */
    public function getRepetition()
    {
        return $this->repetition;
    }

    /**
     * @param PiggyBankRepetition $repetition
     */
    public function setRepetition($repetition)
    {
        $this->repetition = $repetition;
    }

    /**
     * @return bool
     */
    public function hasReminder()
    {
        return !is_null($this->reminder);
    }

    /**
     * @return float|int
     */
    public function percentage()
    {
        if ($this->getCurrentamount() < $this->getCumulativeAmount()) {
            $pct = 0;
            // calculate halfway point?
            if ($this->getCumulativeAmount() - $this->getCurrentamount() < $this->getAmountPerBar()) {
                $left = $this->getCurrentamount() % $this->getAmountPerBar();
                $pct  = round($left / $this->getAmountPerBar() * 100);
            }

            return $pct;
        } else {
            return 100;
        }
    }

    /**
     * @return float
     */
    public function getCurrentamount()
    {
        return $this->currentamount;
    }

    /**
     * @param float $currentamount
     */
    public function setCurrentamount($currentamount)
    {
        $this->currentamount = $currentamount;
    }

    /**
     * @return float
     */
    public function getCumulativeAmount()
    {
        return $this->cumulativeAmount;
    }

    /**
     * @param float $cumulativeAmount
     */
    public function setCumulativeAmount($cumulativeAmount)
    {
        $this->cumulativeAmount = $cumulativeAmount;
    }

    /**
     * @return float
     */
    public function getAmountPerBar()
    {
        return $this->amountPerBar;
    }

    /**
     * @param float $amountPerBar
     */
    public function setAmountPerBar($amountPerBar)
    {
        $this->amountPerBar = $amountPerBar;
    }


}
