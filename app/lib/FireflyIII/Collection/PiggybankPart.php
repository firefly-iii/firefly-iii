<?php

namespace FireflyIII\Collection;


use Carbon\Carbon;

class PiggybankPart
{
    /** @var  int */
    public $amount;
    public $amountPerBar;
    /** @var  int */
    public $currentamount;
    /** @var  \PiggybankRepetition */
    public $repetition;
    /** @var  Carbon */
    public $startdate;
    /** @var  Carbon */
    public $targetdate;

    /**
     * @return \PiggybankRepetition
     */
    public function getRepetition()
    {
        return $this->repetition;
    }

    /**
     * @param \PiggybankRepetition $repetition
     */
    public function setRepetition($repetition)
    {
        $this->repetition = $repetition;
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

    public function percentage()
    {
        if ($this->getCurrentamount() < $this->getAmount()) {
            $pct = 0;
            // calculate halway point?
            if ($this->getAmount() - $this->getCurrentamount() < $this->getAmountPerBar()) {
                $left = $this->getCurrentamount() % $this->getAmountPerBar();
                $pct  = round($left / $this->getAmountPerBar() * 100);
            }

            return $pct;
        } else {
            return 100;
        }
    }

    /**
     * @return int
     */
    public function getCurrentamount()
    {
        return $this->currentamount;
    }

    /**
     * @param int $currentamount
     */
    public function setCurrentamount($currentamount)
    {
        $this->currentamount = $currentamount;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getAmountPerBar()
    {
        return $this->amountPerBar;
    }

    /**
     * @param mixed $amountPerBar
     */
    public function setAmountPerBar($amountPerBar)
    {
        $this->amountPerBar = $amountPerBar;
    }


}