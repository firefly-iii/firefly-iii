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
    /** @var  string */
    protected $amount;
    /** @var  BillModel */
    protected $bill;
    /** @var  bool */
    protected $hit;
    /** @var  string */
    protected $max;
    /** @var  string */
    protected $min;

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param string $amount
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
     * @return string
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @param string $max
     */
    public function setMax($max)
    {
        $this->max = $max;
    }

    /**
     * @return string
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @param string $min
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
