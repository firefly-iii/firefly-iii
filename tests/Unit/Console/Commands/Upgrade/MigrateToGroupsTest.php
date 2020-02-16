<?php
/**
 * MigrateToGroupsTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Unit\Console\Commands\Upgrade;

use FireflyConfig;
use FireflyIII\Factory\TransactionGroupFactory;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class MigrateToGroupsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MigrateToGroupsTest extends TestCase
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
     * Basic test. Assume nothing is wrong.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testHandle(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $service      = $this->mock(JournalDestroyService::class);
        $groupFactory = $this->mock(TransactionGroupFactory::class);
        $cliRepos     = $this->mock(JournalCLIRepositoryInterface::class);

        // mock calls:
        $cliRepos->shouldReceive('getSplitJournals')
                     ->atLeast()->once()
                     ->andReturn(new Collection);
        $cliRepos->shouldReceive('getJournalsWithoutGroup')
                     ->atLeast()->once()
                     ->andReturn([]);

        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_migrated_to_groups', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_migrated_to_groups', true]);

        // assume all is well.
        $this->artisan('firefly-iii:migrate-to-groups')
             ->expectsOutput('No journals to migrate to groups.')
             ->assertExitCode(0);
    }

    /**
     * Return a journal without a group.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testHandleNoGroup(): void
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $service      = $this->mock(JournalDestroyService::class);
        $groupFactory = $this->mock(TransactionGroupFactory::class);
        $cliRepos     = $this->mock(JournalCLIRepositoryInterface::class);

        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => 1,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $asset->id,
                'amount'                 => '-10',
                'identifier'             => 1,
            ]
        );
        $two     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $expense->id,
                'amount'                 => '10',
                'identifier'             => 1,
            ]
        );
        $array   = $journal->toArray();

        // mock calls:
        $cliRepos->shouldReceive('getSplitJournals')
                     ->atLeast()->once()
                     ->andReturn(new Collection);
        $cliRepos->shouldReceive('getJournalsWithoutGroup')
                     ->atLeast()->once()
                     ->andReturn([$array]);

        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_migrated_to_groups', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_migrated_to_groups', true]);

        // assume all is well.
        $this->artisan('firefly-iii:migrate-to-groups')
             ->expectsOutput('Migrated 1 transaction journal(s).')
             ->assertExitCode(0);

        // no longer without a group.
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('transaction_group_id')->get());
        $journal->transactionGroup()->forceDelete();
        $one->forceDelete();
        $two->forceDelete();
        $journal->forceDelete();
    }

    /**
     * Create split withdrawal and see what the system will do.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\MigrateToGroups
     */
    public function testHandleSplitJournal(): void
    {
        $asset   = $this->getRandomAsset();
        $expense = $this->getRandomExpense();
        $group   = $this->getRandomWithdrawalGroup();
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => 1,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $asset->id,
                'amount'                 => '-10',
                'identifier'             => 1,
            ]
        );
        $two     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $expense->id,
                'amount'                 => '10',
                'identifier'             => 1,
            ]
        );
        $three   = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $asset->id,
                'amount'                 => '-12',
                'identifier'             => 2,
            ]
        );
        $four    = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $expense->id,
                'amount'                 => '12',
                'identifier'             => 2,
            ]
        );


        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $service      = $this->mock(JournalDestroyService::class);
        $factory      = $this->mock(TransactionGroupFactory::class);
        $cliRepos     = $this->mock(JournalCLIRepositoryInterface::class);

        // mock calls:
        $cliRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('setUser')->atLeast()->once();

        // mock journal things:
        $cliRepos->shouldReceive('getJournalBudgetId')->atLeast()->once()->andReturn(0);
        $cliRepos->shouldReceive('getJournalCategoryId')->atLeast()->once()->andReturn(0);
        $cliRepos->shouldReceive('getNoteText')->atLeast()->once()->andReturn('Some note.');
        $cliRepos->shouldReceive('getTags')->atLeast()->once()->andReturn(['A', 'B']);
        $cliRepos->shouldReceive('getMetaField')->atLeast()
                     ->withArgs([Mockery::any(), Mockery::any()])
                     ->once()->andReturn(null);
        $cliRepos->shouldReceive('getMetaDate')->atLeast()
                     ->withArgs([Mockery::any(), Mockery::any()])
                     ->once()->andReturn(null);

        // create a group
        $factory->shouldReceive('create')->atLeast()->once()->andReturn($group);
        $service->shouldReceive('destroy')->atLeast()->once();

        $factory->shouldReceive('setUser')->atLeast()->once();

        $cliRepos->shouldReceive('getSplitJournals')
                     ->atLeast()->once()
                     ->andReturn(new Collection([$journal]));
        $cliRepos->shouldReceive('getJournalsWithoutGroup')
                     ->atLeast()->once()
                     ->andReturn([]);

        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_migrated_to_groups', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_migrated_to_groups', true]);

        $this->artisan('firefly-iii:migrate-to-groups')
             ->expectsOutput('Migrated 1 transaction journal(s).')
             ->assertExitCode(0);

        // delete the created stuff:
        $one->forceDelete();
        $two->forceDelete();
        $three->forceDelete();
        $four->forceDelete();
        $journal->forceDelete();

        // the calls above let me know it's OK.
    }
}
