<?php
/**
 * MigrateToGroupsTest.php
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

declare(strict_types=1);

namespace Tests\Unit\Console\Commands\Upgrade;

use Carbon\Carbon;
use FireflyConfig;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class MigrateToGroupsTest
 */
class MigrateToGroupsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testAlreadyExecuted(): void
    {
        $this->mock(TransactionJournalFactory::class);
        $this->mock(JournalRepositoryInterface::class);

        $configObject       = new Configuration;
        $configObject->data = true;
        FireflyConfig::shouldReceive('get')->withArgs(['migrated_to_groups_4780', false])->andReturn($configObject)->once();

        $this->artisan('firefly:migrate-to-groups')
             ->expectsOutput('Database already seems to be migrated.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testBasic(): void
    {
        $journalFactory       = $this->mock(TransactionJournalFactory::class);
        $journalRepos         = $this->mock(JournalRepositoryInterface::class);
        $withdrawal           = $this->getRandomSplitWithdrawal();
        $collection           = new Collection([$withdrawal]);
        $date                 = new Carbon;
        $opposing             = new Transaction;
        $opposing->account_id = 13;

        // not yet executed:
        $configObject       = new Configuration;
        $configObject->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['migrated_to_groups_4780', false])->andReturn($configObject)->once();
        FireflyConfig::shouldReceive('set')->withArgs(['migrated_to_groups_4780', true])->once();


        // calls to repository:
        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('getJournalBudgetId')->atLeast()->once()->andReturn(1);
        $journalRepos->shouldReceive('getJournalCategoryId')->atLeast()->once()->andReturn(2);
        $journalRepos->shouldReceive('findOpposingTransaction')->atLeast()->once()->andReturn($opposing);
        $journalRepos->shouldReceive('getNoteText')->atLeast()->once()->andReturn('I am some notes.');
        $journalRepos->shouldReceive('getTags')->atLeast()->once()->andReturn(['a', 'b']);
        $journalRepos->shouldReceive('getSplitJournals')->once()->andReturn($collection);

        // all meta field calls.
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'internal-reference'])->andReturn('ABC');
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-cc'])->andReturnNull();

        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-ct-op'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-ct-id'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-db'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-country'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-ep'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-ci'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'sepa-batch-id'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'external-id'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'original-source'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'recurrence_id'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'bunq_payment_id'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'importHash'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaField')->atLeast()->once()->withArgs([Mockery::any(), 'importHashV2'])->andReturnNull();

        $journalRepos->shouldReceive('getMetaDate')->atLeast()->once()->withArgs([Mockery::any(), 'interest_date'])->andReturn($date);
        $journalRepos->shouldReceive('getMetaDate')->atLeast()->once()->withArgs([Mockery::any(), 'book_date'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaDate')->atLeast()->once()->withArgs([Mockery::any(), 'process_date'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaDate')->atLeast()->once()->withArgs([Mockery::any(), 'due_date'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaDate')->atLeast()->once()->withArgs([Mockery::any(), 'payment_date'])->andReturnNull();
        $journalRepos->shouldReceive('getMetaDate')->atLeast()->once()->withArgs([Mockery::any(), 'invoice_date'])->andReturnNull();

        // calls to factory
        $journalFactory->shouldReceive('setUser')->atLeast()->once();
        $journalFactory->shouldReceive('create')->atLeast()->once()->withAnyArgs()->andReturn(new Collection());


        $this->artisan('firefly:migrate-to-groups')
             ->expectsOutput('Going to un-split 1 transaction(s). This could take some time.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testForced(): void
    {
        $this->mock(TransactionJournalFactory::class);
        $repository = $this->mock(JournalRepositoryInterface::class);

        $repository->shouldReceive('getSplitJournals')->andReturn(new Collection);


        $configObject       = new Configuration;
        $configObject->data = true;
        FireflyConfig::shouldReceive('get')->withArgs(['migrated_to_groups_4780', false])->andReturn($configObject)->once();
        FireflyConfig::shouldReceive('set')->withArgs(['migrated_to_groups_4780', true])->once();

        $this->artisan('firefly:migrate-to-groups --force')
             ->expectsOutput('Forcing the migration.')
             ->expectsOutput('Found no split journals. Nothing to do.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testNotSplit(): void
    {
        $this->mock(TransactionJournalFactory::class);
        $repository = $this->mock(JournalRepositoryInterface::class);
        $withdrawal = $this->getRandomWithdrawal();

        $repository->shouldReceive('getSplitJournals')->andReturn(new Collection([$withdrawal]));


        $configObject       = new Configuration;
        $configObject->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['migrated_to_groups_4780', false])->andReturn($configObject)->once();
        FireflyConfig::shouldReceive('set')->withArgs(['migrated_to_groups_4780', true])->once();

        $this->artisan('firefly:migrate-to-groups')
             ->expectsOutput('Going to un-split 1 transaction(s). This could take some time.')
             ->assertExitCode(0);
    }

}