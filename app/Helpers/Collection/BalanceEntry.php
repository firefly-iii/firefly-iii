<?php
declare(strict_types = 1);
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
    /** @var string */
    protected $left = '0';
    /** @var string */
    protected $spent = '0';

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
    public function setAccount(AccountModel $account)
    {
        $this->account = $account;
    }

    /**
     * @return string
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @param string $left
     */
    public function setLeft(string $left)
    {
        $this->left = $left;
    }

    /**
     * @return string
     */
    public function getSpent()
    {
        return $this->spent;
    }

    /**
     * @param string $spent
     */
    public function setSpent(string $spent)
    {
        $this->spent = $spent;
    }


}
