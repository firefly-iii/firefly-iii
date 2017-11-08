<?php
/**
 * DescriptionContainsTest.php
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
use FireflyIII\TransactionRules\Triggers\DescriptionContains;
use Tests\TestCase;

/**
 * Class DescriptionContains
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class DescriptionContainsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::triggered
     */
    public function testTriggeredCase()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUM bla bla ';
        $trigger              = DescriptionContains::makeFromStrings('ipsum', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::triggered
     */
    public function testTriggeredNot()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUM bla bla ';
        $trigger              = DescriptionContains::makeFromStrings('blurb', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }


    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::triggered
     */
    public function testTriggeredDefault()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Should contain test string';
        $trigger              = DescriptionContains::makeFromStrings('cont', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::triggered
     */
    public function testTriggeredEnd()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something is going to happen';
        $trigger              = DescriptionContains::makeFromStrings('pen', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::triggered
     */
    public function testTriggeredStart()
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something is going to happen';
        $trigger              = DescriptionContains::makeFromStrings('somet', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = DescriptionContains::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = DescriptionContains::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = DescriptionContains::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
