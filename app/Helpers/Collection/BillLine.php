<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Bill as BillModel;

/**
 * @codeCoverageIgnore
 *
 * Class BillLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BillLine
{

    /** @var  bool */
    protected $active;
    /** @var  float */
    protected $amount;
    /** @var  BillModel */
    protected $bill;
    /** @var  bool */
    protected $hit;
    /** @var  float */
    protected $max;
    /** @var  float */
    protected $min;

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return BillModel
     */
    public function getBill()
    {
        return $this->bill;
    }

    /**
     * @param BillModel $bill
     */
    public function setBill($bill)
    {
        $this->bill = $bill;
    }

    /**
     * @return float
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param float $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @return float
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param float $min
     */
    public function setMin($min)
    {
        $this->min = $min;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return boolean
     */
    public function isHit()
    {
        return $this->hit;
    }

    /**
     * @param boolean $hit
     */
    public function setHit($hit)
    {
        $this->hit = $hit;
    }


}
