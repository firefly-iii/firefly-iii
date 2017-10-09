<?php
/**
 * DescriptionIsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;


use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\DescriptionIs;
use Tests\TestCase;

/**
 * Class DescriptionIs
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class DescriptionIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionIs::triggered
     */
    public function testTriggeredCase()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUMbla';
        $trigger              = DescriptionIs::makeFromStrings('lorem ipsumbla', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionIs::triggered
     */
    public function testTriggeredNot()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUM blabla';
        $trigger              = DescriptionIs::makeFromStrings('lorem', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionIs::triggered
     */
    public function testTriggeredDefault()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Should be test string';
        $trigger              = DescriptionIs::makeFromStrings('Should be test string', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionIs::triggered
     */
    public function testTriggeredClose()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something is going to happen';
        $trigger              = DescriptionIs::makeFromStrings('Something is going to happe', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionIs::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = DescriptionIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionIs::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = DescriptionIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}