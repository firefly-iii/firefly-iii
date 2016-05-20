<?php
/**
 * BalanceHeader.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Account as AccountModel;
use Illuminate\Support\Collection;

/**
 *
 * Class BalanceHeader
 *
 * @package FireflyIII\Helpers\Collection
 */
class BalanceHeader
{

    /** @var  Collection */
    protected $accounts;

    /**
     *
     */
    public function __construct()
    {
        $this->accounts = new Collection;
    }

    /**
     * @param AccountModel $account
     */
    public function addAccount(AccountModel $account)
    {
        $this->accounts->push($account);
    }

    /**
     * @return Collection
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }


}
