<?php

namespace FireflyIII\Database\PiggyBank;

/**
 * Interface PiggyBankInterface
 *
 * @package FireflyIII\Database\Ifaces
 */
interface PiggyBankInterface
{

    /**
     * @param \Account $account
     *
     * @return float
     */
    public function leftOnAccount(\Account $account);
} 