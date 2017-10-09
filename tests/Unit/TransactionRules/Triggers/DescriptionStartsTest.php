<?php
/**
 * DescriptionStartsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\DescriptionStarts;
use Tests\TestCase;

/**
 * Class DescriptionStarts
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class DescriptionStartsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::triggered
     */
    public function testTriggeredCase()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUMbla';
        $trigger              = DescriptionStarts::makeFromStrings('lorem', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::triggered
     */
    public function testTriggeredNot()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUM blabla';
        $trigger              = DescriptionStarts::makeFromStrings('blabla', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::triggered
     */
    public function testTriggeredDefault()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Should contain test string';
        $trigger              = DescriptionStarts::makeFromStrings('Should', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::triggered
     */
    public function testTriggeredClose()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something is going to happen';
        $trigger              = DescriptionStarts::makeFromStrings('omething', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::triggered
     */
    public function testTriggeredLongSearch()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something';
        $trigger              = DescriptionStarts::makeFromStrings('Something is', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = DescriptionStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = DescriptionStarts::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionStarts::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = DescriptionStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }
}