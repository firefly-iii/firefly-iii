<?php
/**
 * DescriptionStartsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
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
