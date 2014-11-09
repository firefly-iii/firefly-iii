<?php

namespace FireflyIII\Database\Ifaces;

/**
 * Interface PiggybankInterface
 *
 * @package FireflyIII\Database\Ifaces
 */
interface PiggybankInterface
{

    /**
     * @param \Account $account
     *
     * @return float
     */
    public function leftOnAccount(\Account $account);
} 