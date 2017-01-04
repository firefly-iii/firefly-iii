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
    /**
     * @covers \FireflyIII\Models\TransactionType::isDeposit
     */
    public function testIsDeposit()
    {

        $transactionType = TransactionType::whereType(TransactionType::DEPOSIT)->first();
        $this->assertTrue($transactionType->isDeposit());
    }

    /**
     * @covers \FireflyIII\Models\TransactionType::isOpeningBalance
     */
    public function testIsOpeningBalance()
    {
        $transactionType = TransactionType::whereType(TransactionType::OPENING_BALANCE)->first();
        $this->assertTrue($transactionType->isOpeningBalance());
    }

    /**
     * @covers \FireflyIII\Models\TransactionType::isTransfer
     */
    public function testIsTransfer()
    {
        $transactionType = TransactionType::whereType(TransactionType::TRANSFER)->first();
        $this->assertTrue($transactionType->isTransfer());
    }

    /**
     * @covers \FireflyIII\Models\TransactionType::isWithdrawal
     */
    public function testIsWithdrawal()
    {
        $transactionType = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $this->assertTrue($transactionType->isWithdrawal());
    }
}
