<?php
declare(strict_types = 1);

/**
 * EntryBill.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace FireflyIII\Export\Entry;

use FireflyIII\Models\Bill;

/**
 * Class EntryBill
 *
 * @package FireflyIII\Export\Entry
 */
class EntryBill
{
    /** @var  int */
    public $billId = '';
    /** @var  string */
    public $name = '';

    /**
     * EntryBill constructor.
     *
     * @param Bill $bill
     */
    public function __construct(Bill $bill = null)
    {
        if (!is_null($bill)) {
            $this->billId = $bill->id;
            $this->name   = $bill->name;
        }
    }

}