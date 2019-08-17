<?php
/**
 * CreateRecurringTransactionsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Jobs;


use Carbon\Carbon;
use FireflyIII\Events\StoredTransactionGroup;
use FireflyIII\Factory\PiggyBankEventFactory;
use FireflyIII\Factory\PiggyBankFactory;
use FireflyIII\Jobs\CreateRecurringTransactions;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\TransactionGroup\TransactionGroupRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Log;
use Preferences;
use Tests\TestCase;

/**
 * Class CreateRecurringTransactionsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateRecurringTransactionsTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Submit nothing.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testBasic(): void
    {
        // mock classes
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $date = new Carbon();
        $job  = new CreateRecurringTransactions($date);
        $job->setForce(false);
        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(0, $job->executed);
        $this->assertEquals(0, $job->submitted);

    }

    /**
     * Submit one, but offer no occurrences.
     *
     * TODO there is a random element in this test that breaks the test.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingle(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        // mock classes
        $date = new Carbon;
        $date->subDays(4);
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->latest_date = null;
        $recurrence->first_date  = $date;
        $recurrence->save();

        Log::debug(sprintf('Test is going to use Recurrence #%d', $recurrence->id), $recurrence->toArray());

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $groupRepos     = $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([]);
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        Preferences::shouldReceive('mark')->atLeast()->once();


        $date = new Carbon();
        $job  = new CreateRecurringTransactions($date);

        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(1, $job->executed);
        $this->assertEquals(1, $job->submitted);
    }



    /**
     * Submit one, but has already fired today
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleFiredToday(): void
    {
        // mock classes
        $recurrence     = $this->getRandomRecurrence();
        $recurrence->latest_date = new Carbon;
        $recurrence->save();
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        Preferences::shouldReceive('mark')->atLeast()->once();


        $date = new Carbon();
        $job  = new CreateRecurringTransactions($date);

        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(0, $job->executed);
        $this->assertEquals(1, $job->submitted);
        $recurrence->latest_date =null;
        $recurrence->save();
    }


    /**
     * Submit one, but offer no occurrences.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleFuture(): void
    {
        // mock classes
        $future = new Carbon;
        $future->addDays(4);
        $recurrence     = $this->getRandomRecurrence();
        $recurrence->first_date =$future;
        $recurrence->save();


        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        Preferences::shouldReceive('mark')->atLeast()->once();


        $date = new Carbon();
        $job  = new CreateRecurringTransactions($date);

        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(0, $job->executed);
        $this->assertEquals(1, $job->submitted);

        $recurrence->first_date =$date;
        $recurrence->save();
    }


    /**
     * Submit one, but should no longer run.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleOverDue(): void
    {
        // mock classes
        $date           = new Carbon();
        $yesterday      = clone $date;
        $yesterday->subDays(3);
        $recurrence     = $this->getRandomRecurrence();

        $recurrence->repeat_until =$yesterday;
        $recurrence->save();


        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        Preferences::shouldReceive('mark')->atLeast()->once();


        $job = new CreateRecurringTransactions($date);

        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(0, $job->executed);
        $this->assertEquals(1, $job->submitted);

        $recurrence->repeat_until =null;
        $recurrence->save();
    }


    /**
     * Submit one, but it has fired enough times already.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleOccurrences(): void
    {
        // mock classes
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->repetitions = 1;
        $recurrence->save();

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(1);
        Preferences::shouldReceive('mark')->atLeast()->once();


        $date = new Carbon();
        $job  = new CreateRecurringTransactions($date);

        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(0, $job->executed);
        $this->assertEquals(1, $job->submitted);

        $recurrence->repetitions = 0;
        $recurrence->save();
    }

    /**
     * Submit one, but it's inactive.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleInactive(): void
    {

        // mock classes
        $recurrence = $this->getRandomRecurrence();

        $recurrence->active = false;
        $recurrence->save();

        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $this->mock(JournalRepositoryInterface::class);
        $this->mock(TransactionGroupRepositoryInterface::class);

        // mocks:
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        Preferences::shouldReceive('mark')->atLeast()->once();


        $date = new Carbon();
        $job  = new CreateRecurringTransactions($date);

        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(0, $job->executed);
        $this->assertEquals(1, $job->submitted);

        $recurrence->active = true;
        $recurrence->save();
    }


    /**
     * Submit one, offer occurence for today.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleToday(): void
    {
        Event::fake();
        $date = new Carbon();
        $this->expectsEvents([StoredTransactionGroup::class]);

        // mock classes
        $carbon = new Carbon;
        $carbon->subDays(4);
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->latest_date = null;
        $recurrence->first_date  = $carbon;
        $recurrence->save();

        $group      = $this->getRandomWithdrawalGroup();

        // overrule some fields in the recurrence to make it seem it hasnt fired yet.
        $recurrence->latest_date = null;
        $recurrence->save();

        // mock classes
        $recurringRepos    = $this->mock(RecurringRepositoryInterface::class);
        $journalRepos      = $this->mock(JournalRepositoryInterface::class);
        $groupRepos        = $this->mock(TransactionGroupRepositoryInterface::class);
        $piggyFactory      = $this->mock(PiggyBankFactory::class);
        $piggyEventFactory = $this->mock(PiggyBankEventFactory::class);

        // mocks:
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('setUser')->atLeast()->once();

        $recurringRepos->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([$date]);
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        $recurringRepos->shouldReceive('getPiggyBank')->atLeast()->once()->andReturnNull();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // return data:
        $recurringRepos->shouldReceive('getBudget')->atLeast()->once()->andReturnNull();
        $recurringRepos->shouldReceive('getCategory')->atLeast()->once()->andReturnNull();
        $recurringRepos->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        // store journal
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($group);

        //Event::assertDispatched(StoredTransactionGroup::class);

        $job = new CreateRecurringTransactions($date);
        $job->handle();

        $this->assertEquals(1, $job->created);
        $this->assertEquals(1, $job->executed);
        $this->assertEquals(1, $job->submitted);
    }


    /**
     * Submit one, offer occurence for today.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testForced(): void
    {
        Log::info(sprintf('Now in test %s.', __METHOD__));
        Event::fake();
        $date = new Carbon();
        $this->expectsEvents([StoredTransactionGroup::class]);

        // overrule some fields in the recurrence.
        $carbon = new Carbon;
        $carbon->subDays(4);
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->latest_date = null;
        $recurrence->first_date  = $carbon;
        $recurrence->save();



        $group      = $this->getRandomWithdrawalGroup();

        // overrule some fields in the recurrence to make it seem it hasnt fired yet.
        $recurrence->latest_date = null;
        $recurrence->save();

        // mock classes
        $recurringRepos    = $this->mock(RecurringRepositoryInterface::class);
        $journalRepos      = $this->mock(JournalRepositoryInterface::class);
        $groupRepos        = $this->mock(TransactionGroupRepositoryInterface::class);
        $piggyFactory      = $this->mock(PiggyBankFactory::class);
        $piggyEventFactory = $this->mock(PiggyBankEventFactory::class);

        // mocks:
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('setUser')->atLeast()->once();

        $recurringRepos->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([$date]);
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(3);
        $recurringRepos->shouldReceive('getPiggyBank')->atLeast()->once()->andReturnNull();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // return data:
        $recurringRepos->shouldReceive('getBudget')->atLeast()->once()->andReturnNull();
        $recurringRepos->shouldReceive('getCategory')->atLeast()->once()->andReturnNull();
        $recurringRepos->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        // store journal
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($group);

        //Event::assertDispatched(StoredTransactionGroup::class);

        $job = new CreateRecurringTransactions($date);
        $job->setForce(true);
        $job->handle();

        $this->assertEquals(1, $job->created);
        $this->assertEquals(1, $job->executed);
        $this->assertEquals(1, $job->submitted);
    }


    /**
     * Submit one, offer occurence for today.
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testBadJournalCount(): void
    {
        Event::fake();
        $date = new Carbon();

        // overrule some fields in the recurrence to make it seem it hasnt fired yet.
        $carbon = new Carbon;
        $carbon->subDays(4);
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->latest_date = null;
        $recurrence->first_date  = $carbon;
        $recurrence->save();

        // mock classes
        $recurringRepos    = $this->mock(RecurringRepositoryInterface::class);
        $journalRepos      = $this->mock(JournalRepositoryInterface::class);
        $groupRepos        = $this->mock(TransactionGroupRepositoryInterface::class);
        $piggyFactory      = $this->mock(PiggyBankFactory::class);
        $piggyEventFactory = $this->mock(PiggyBankEventFactory::class);

        // mocks:
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('setUser')->atLeast()->once();

        $recurringRepos->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([$date]);
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(3);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $job = new CreateRecurringTransactions($date);
        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(1, $job->executed);
        $this->assertEquals(1, $job->submitted);
    }

    /**
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleNotToday(): void
    {
        $date = new Carbon();
        $tomorrow = new Carbon();
        $tomorrow->addDays(2);

        // overrule some fields in the recurrence to make it seem it hasnt fired yet.
        $carbon = new Carbon;
        $carbon->subDays(4);
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->latest_date = null;
        $recurrence->first_date  = $carbon;
        $recurrence->save();

        // mock classes
        $recurringRepos    = $this->mock(RecurringRepositoryInterface::class);
        $journalRepos      = $this->mock(JournalRepositoryInterface::class);
        $groupRepos        = $this->mock(TransactionGroupRepositoryInterface::class);
        $piggyFactory      = $this->mock(PiggyBankFactory::class);
        $piggyEventFactory = $this->mock(PiggyBankEventFactory::class);

        // mocks:
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('setUser')->atLeast()->once();

        $recurringRepos->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([$tomorrow]);
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $job = new CreateRecurringTransactions($date);
        $job->handle();

        $this->assertEquals(0, $job->created);
        $this->assertEquals(1, $job->executed);
        $this->assertEquals(1, $job->submitted);

    }

    /**
     * Submit one, offer occurence for today, with piggy
     *
     * @covers \FireflyIII\Jobs\CreateRecurringTransactions
     */
    public function testSingleTodayPiggy(): void
    {
        Event::fake();
        $date = new Carbon();
        $this->expectsEvents([StoredTransactionGroup::class]);


        $group      = $this->getRandomWithdrawalGroup();
        $piggy = $this->getRandomPiggyBank();

        // overrule some fields in the recurrence to make it seem it hasnt fired yet.
        $carbon = new Carbon;
        $carbon->subDays(4);
        $recurrence              = $this->getRandomRecurrence();
        $recurrence->latest_date = null;
        $recurrence->first_date  = $carbon;
        $recurrence->save();

        // mock classes
        $recurringRepos    = $this->mock(RecurringRepositoryInterface::class);
        $journalRepos      = $this->mock(JournalRepositoryInterface::class);
        $groupRepos        = $this->mock(TransactionGroupRepositoryInterface::class);
        $piggyEventFactory = $this->mock(PiggyBankEventFactory::class);

        // mocks:
        $groupRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $recurringRepos->shouldReceive('setUser')->atLeast()->once();

        $recurringRepos->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([$date]);
        $recurringRepos->shouldReceive('getAll')->atLeast()->once()->andReturn(new Collection([$recurrence]));
        $recurringRepos->shouldReceive('getJournalCount')->atLeast()->once()->andReturn(0);
        $recurringRepos->shouldReceive('getPiggyBank')->atLeast()->once()->andReturn($piggy);
        $piggyEventFactory->shouldReceive('create')->once();
        Preferences::shouldReceive('mark')->atLeast()->once();

        // return data:
        $recurringRepos->shouldReceive('getBudget')->atLeast()->once()->andReturnNull();
        $recurringRepos->shouldReceive('getCategory')->atLeast()->once()->andReturnNull();
        $recurringRepos->shouldReceive('getTags')->atLeast()->once()->andReturn([]);

        // store journal
        $groupRepos->shouldReceive('store')->atLeast()->once()->andReturn($group);

        //Event::assertDispatched(StoredTransactionGroup::class);

        $job = new CreateRecurringTransactions($date);
        $job->handle();

        $this->assertEquals(1, $job->created);
        $this->assertEquals(1, $job->executed);
        $this->assertEquals(1, $job->submitted);

    }
}