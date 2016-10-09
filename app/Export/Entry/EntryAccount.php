<?php
/**
 * EntryAccount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Export\Entry;

use FireflyIII\Models\Account;

/**
 * Class EntryAccount
 *
 * @package FireflyIII\Export\Entry
 */
class EntryAccount
{
    /** @var  int */
    public $accountId;
    /** @var  string */
    public $iban;
    /** @var  string */
    public $name;
    /** @var  string */
    public $number;
    /** @var  string */
    public $type;

    /**
     * EntryAccount constructor.
     *
     * @param Account $account
     */
    public function __construct(Account $account)
    {
        $this->accountId = $account->id;
        $this->name      = $account->name;
        $this->iban      = $account->iban;
        $this->type      = $account->accountType->type;
        $this->number    = $account->getMeta('accountNumber');
    }
}
