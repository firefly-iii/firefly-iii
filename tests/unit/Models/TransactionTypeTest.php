<?php
/**
 * TransactionTypeTest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

use FireflyIII\Models\TransactionType;


/**
 * Class TransactionTypeTest
 */
class TransactionTypeTest extends TestCase
{
    public function testIsDeposit()
    {
        $transactionType = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $this->assertTrue($transactionType->isDeposit());
    }

    public function testIsOpeningBalance()
    {
        $transactionType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        $this->assertTrue($transactionType->isOpeningBalance());
    }

    public function testIsTransfer()
    {
        $transactionType = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $this->assertTrue($transactionType->isTransfer());
    }

    public function testIsWithdrawal()
    {
        $transactionType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $this->assertTrue($transactionType->isWithdrawal());
    }
}
