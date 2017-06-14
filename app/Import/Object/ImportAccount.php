<?php
/**
 * ImportAccount.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Object;


class ImportAccount
{

    /** @var  array */
    private $accountId = [];

    /** @var array */
    private $accountIban = [];
    /** @var array */
    private $accountName = [];

    /** @var array */
    private $accountNumber = [];

    /**
     * @param array $accountNumber
     */
    public function setAccountNumber(array $accountNumber)
    {
        $this->accountNumber = $accountNumber;
    }

    /**
     * @param array $accountName
     */
    public function setAccountName(array $accountName)
    {
        $this->accountName = $accountName;
    }


    /**
     * @param array $value
     */
    public function setAccountId(array $value)
    {
        $this->accountId = $value;
    }

    /**
     * @param array $accountIban
     */
    public function setAccountIban(array $accountIban)
    {
        $this->accountIban = $accountIban;
    }


}