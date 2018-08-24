<?php
/**
 * UpdatedJournalEventHandlerTest.php
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

use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Handlers\Events\UpdatedJournalEventHandler;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Log;
use Tests\TestCase;

/**
 * Class UpdatedJournalEventHandlerTest
 */
class UpdatedJournalEventHandlerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Handlers\Events\UpdatedJournalEventHandler
     * @covers \FireflyIII\Events\StoredTransactionJournal
     */
    public function testProcessRules(): void
    {
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $processor      = $this->mock(Processor::class);

        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $event      = new UpdatedTransactionJournal($journal);
        $ruleGroups = $this->user()->ruleGroups()->take(1)->get();
        $rules      = $this->user()->rules()->take(1)->get();

        // mock calls:
        $ruleGroupRepos->shouldReceive('setUser')->once();
        $ruleGroupRepos->shouldReceive('getActiveGroups')->andReturn($ruleGroups)->once();
        $ruleGroupRepos->shouldReceive('getActiveUpdateRules')->andReturn($rules)->once();
        $processor->shouldReceive('make')->once();
        $processor->shouldReceive('handleTransactionJournal')->once();


        $handler = new UpdatedJournalEventHandler;
        try {
            $handler->processRules($event);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

}