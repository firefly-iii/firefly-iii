<?php
/**
 * FromAccountEndsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\TransactionRules\Triggers\FromAccountEnds;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class FromAccountEndsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FromAccountEndsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds
     */
    public function testTriggered(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        /** @var TransactionJournal $journal */
        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account    = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountEnds::makeFromStrings(substr($account->name, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds
     */
    public function testTriggeredLonger(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account    = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountEnds::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds
     */
    public function testTriggeredNot(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account    = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountEnds::makeFromStrings('some name' . random_int(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $value      = '';
        $result     = FromAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $value      = 'x';
        $result     = FromAccountEnds::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds
     */
    public function testWillMatchEverythingNull(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $value      = null;
        $result     = FromAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
