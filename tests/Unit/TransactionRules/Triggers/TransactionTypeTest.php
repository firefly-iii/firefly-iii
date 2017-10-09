<?php
/**
 * TransactionTypeTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\TransactionType;
use Tests\TestCase;

/**
 * Class TransactionTypeTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class TransactionTypeTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType::triggered
     */
    public function testTriggered()
    {
        $journal = TransactionJournal::find(69);
        $type    = $journal->transactionType->type;
        $trigger = TransactionType::makeFromStrings($type, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType::triggered
     */
    public function testTriggeredFalse()
    {
        $journal = TransactionJournal::find(70);
        $trigger = TransactionType::makeFromStrings('NonExisting', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = TransactionType::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = TransactionType::willMatchEverything($value);
        $this->assertTrue($result);
    }


}