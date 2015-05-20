<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Account as AccountModel;

/**
 * @codeCoverageIgnore
 *
 * Class BalanceEntry
 *
 * @package FireflyIII\Helpers\Collection
 */
class BalanceEntry
{


    /** @var  AccountModel */
    protected $account;
    /** @var float */
    protected $left = 0.0;
    /** @var float */
    protected $spent = 0.0;

    /**
     * @return AccountModel
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param AccountModel $account
     */
    public function setAccount($account)
    {
        $this->account = $account;
    }

    /**
     * @return float
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param float $left
     */
    public function setLeft($left)
    {
        $this->left = $left;
    }

    /**
     * @return float
     */
    public function getSpent()
    {
        return $this->spent;
    }

    /**
     * @param float $spent
     */
    public function setSpent($spent)
    {
        $this->spent = $spent;
    }


}