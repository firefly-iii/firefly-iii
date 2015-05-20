<?php

namespace FireflyIII\Helpers\Collection;

use FireflyIII\Models\Account as AccountModel;
use Illuminate\Support\Collection;

/**
 * @codeCoverageIgnore
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
    public function getAccounts()
    {
        return $this->accounts;
    }


}
