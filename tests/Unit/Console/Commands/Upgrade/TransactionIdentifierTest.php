<?php
declare(strict_types=1);
/**
 * TransactionIdentifierTest.php
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

namespace Tests\Unit\Console\Commands\Upgrade;


use FireflyConfig;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalCLIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class TransactionIdentifierTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionIdentifierTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Upgrade\TransactionIdentifier
     */
    public function testHandle(): void
    {
        // mock classes:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $cliRepos     = $this->mock(JournalCLIRepositoryInterface::class);
        // commands:
        $cliRepos->shouldReceive('getSplitJournals')->andReturn(new Collection)
                     ->atLeast()->once();

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_transaction_identifier', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_transaction_identifier', true]);

        // assume all is well.
        $this->artisan('firefly-iii:transaction-identifiers')
             ->expectsOutput('All split journal transaction identifiers are correct.')
             ->assertExitCode(0);

    }

    /**
     * Basic test. Assume nothing is wrong.
     *
     * @covers \FireflyIII\Console\Commands\Upgrade\TransactionIdentifier
     */
    public function testHandleSplit(): void
    {
        // create a split journal:
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
                'identifier'             => 0,
            ]
        );
        $two     = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $expense->id,
                'amount'                 => '10',
                'identifier'             => 0,
            ]
        );
        $three   = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $asset->id,
                'amount'                 => '-12',
                'identifier'             => 0,
            ]
        );
        $four    = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $expense->id,
                'amount'                 => '12',
                'identifier'             => 0,
            ]
        );

        // mock classes:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $cliRepos     = $this->mock(JournalCLIRepositoryInterface::class);
        // commands:
        $cliRepos->shouldReceive('getSplitJournals')->andReturn(new Collection([$journal]))
                     ->atLeast()->once();

        // configuration
        $false       = new Configuration;
        $false->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['480_transaction_identifier', false])->andReturn($false);
        FireflyConfig::shouldReceive('set')->withArgs(['480_transaction_identifier', true]);

        // assume all is well.
        $this->artisan('firefly-iii:transaction-identifiers')
             ->expectsOutput('Fixed 2 split journal transaction identifier(s).')
             ->assertExitCode(0);

        // see results:
        $this->assertCount(1, Transaction::where('id', $one->id)->where('identifier', 0)->get());
        $this->assertCount(1, Transaction::where('id', $three->id)->where('identifier', 1)->get());

    }

}
