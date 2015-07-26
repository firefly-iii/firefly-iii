<?php

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Models\PiggyBankRepetition;

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

    /** @var  PiggyBankRepetition */
    public $repetition;

    /** @var  Carbon */
    public $startdate;

    /** @var  Carbon */
    public $targetdate;

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
     * @return float|int
     */
    public function percentage()
    {
        bcscale(2);
        if ($this->getCurrentamount() < $this->getCumulativeAmount()) {
            $pct = 0;
            // calculate halfway point?
            if (bcsub($this->getCumulativeAmount(), $this->getCurrentamount()) < $this->getAmountPerBar()) {
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
