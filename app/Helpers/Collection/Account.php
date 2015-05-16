<?php

namespace FireflyIII\Helpers\Collection;

use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
 * Class Account
 *
 * @package FireflyIII\Helpers\Collection
 */
class Account
{

    /** @var Collection */
    protected $accounts;
    /** @var float */
    protected $difference;
    /** @var float */
    protected $end;
    /** @var float */
    protected $start;

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @param \Illuminate\Support\Collection $accounts
     */
    public function setAccounts($accounts)
    {
        $this->accounts = $accounts;
    }

    /**
     * @return float
     */
    public function getDifference()
    {
        return $this->difference;
    }

    /**
     * @param float $difference
     */
    public function setDifference($difference)
    {
        $this->difference = $difference;
    }

    /**
     * @return float
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param float $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * @return float
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param float $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }


}