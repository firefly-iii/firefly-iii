<?php
/**
 * StoredJournalEventHandlerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Handlers\Events;


use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Handlers\Events\StoredJournalEventHandler;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Log;
use Tests\TestCase;

/**
 *
 * Class StoredJournalEventHandlerTest
 */
class StoredJournalEventHandlerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Handlers\Events\StoredJournalEventHandler
     * @covers \FireflyIII\Events\StoredTransactionJournal
     */
    public function testProcessRules(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $processor      = $this->mock(Processor::class);

        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $piggy      = $this->user()->piggyBanks()->inRandomOrder()->first();
        $event      = new StoredTransactionJournal($journal, $piggy->id);
        $ruleGroups = $this->user()->ruleGroups()->take(1)->get();
        $rules      = $this->user()->rules()->take(1)->get();

        // mock calls:
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('getActiveGroups')->andReturn($ruleGroups)->once();
        $ruleGroupRepos->shouldReceive('getActiveStoreRules')->andReturn($rules)->once();
        $processor->shouldReceive('make')->once();
        $processor->shouldReceive('handleTransactionJournal')->once();


        $handler = new StoredJournalEventHandler;
        try {
            $handler->processRules($event);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}