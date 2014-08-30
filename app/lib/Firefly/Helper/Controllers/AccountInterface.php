<?php

namespace Firefly\Helper\Controllers;

use Illuminate\Database\Eloquent\Collection;

/**
 * Interface AccountInterface
 *
 * @package Firefly\Helper\Controllers
 */
interface AccountInterface
{

    /**
     * @param \Account $account
     *
     * @return mixed
     */
    public function openingBalanceTransaction(\Account $account);

    /**
     * @param \Account $account
     * @param          $perPage
     *
     * @return mixed
     */
    public function show(\Account $account, $perPage);

} 