<?php
/**
 * AmountMoreTest.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\TransactionRules\Triggers\AmountMore;
use Tests\TestCase;

/**
 * Class AmountMoreTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AmountMoreTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore
     */
    public function testTriggeredExact(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('setUser');
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('12.35');

        $journal                     = new TransactionJournal;
        $journal->user               = $this->user();
        $journal->destination_amount = '12.35';
        $trigger                     = AmountMore::makeFromStrings('12.35', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore
     */
    public function testTriggeredMore(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('setUser');
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('12.34');

        $journal                     = new TransactionJournal;
        $journal->user               = $this->user();
        $journal->destination_amount = '12.34';
        $trigger                     = AmountMore::makeFromStrings('12.10', false);
        $result                      = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore
     */
    public function testTriggeredNotMore(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('setUser');
        $journalRepos->shouldReceive('getJournalTotal')->andReturn('12.35');

        $journal                     = new TransactionJournal;
        $journal->user               = $this->user();
        $journal->destination_amount = '12.35';
        $trigger                     = AmountMore::makeFromStrings('12.50', false);
        $result                      = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = '1';
        $result = AmountMore::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = AmountMore::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\AmountMore
     */
    public function testWillMatchEverythingZero(): void
    {
        $value  = '0';
        $result = AmountMore::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
