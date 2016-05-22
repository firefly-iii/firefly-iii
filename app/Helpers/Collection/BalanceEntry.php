<?php
/**
 * BalanceEntry.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Account as AccountModel;

/**
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
    public function getAccount(): AccountModel
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
    public function getLeft(): string
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
    public function getSpent(): string
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
