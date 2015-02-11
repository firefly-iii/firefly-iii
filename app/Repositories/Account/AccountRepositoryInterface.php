<?php

namespace FireflyIII\Repositories\Account;


/**
 * Interface AccountRepositoryInterface
 *
 * @package FireflyIII\Repositories\Account
 */
interface AccountRepositoryInterface
{
    /**
     * @param array $data
     *
     * @return mixed
     */
    public function store(array $data);
}