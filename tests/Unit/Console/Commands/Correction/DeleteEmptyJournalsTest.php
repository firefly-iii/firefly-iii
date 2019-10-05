<?php
/**
 * DeleteEmptyJournalsTest.php
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

/**
 * Class DeleteEmptyJournalsTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DeleteEmptyJournalsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyJournals
     */
    public function testHandle(): void
    {
        // assume there are no empty journals or uneven journals
        $this->artisan('firefly-iii:delete-empty-journals')
             ->expectsOutput('No uneven transaction journals.')
             ->expectsOutput('No empty transaction journals.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyJournals
     */
    public function testHandleEmptyJournals(): void
    {
        // create empty journal:
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => 1,
                'description'             => 'Hello',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $this->artisan('firefly-iii:delete-empty-journals')
             ->expectsOutput('No uneven transaction journals.')
             ->expectsOutput(sprintf('Deleted empty transaction journal #%d', $journal->id))
             ->assertExitCode(0);

        // verify its indeed gone
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyJournals
     */
    public function testHandleUnevenJournals(): void
    {
        // create empty journal:
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => 1,
                'description'             => 'Hello',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );

        // link empty transaction
        $transaction = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => 1,
                'amount'                 => '5',
            ]
        );


        $this->artisan('firefly-iii:delete-empty-journals')
             ->expectsOutput(sprintf('Deleted transaction journal #%d because it had an uneven number of transactions.', $journal->id))
             ->expectsOutput('No empty transaction journals.')
             ->assertExitCode(0);

        // verify both are gone
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
        $this->assertCount(0, Transaction::where('id', $transaction->id)->whereNull('deleted_at')->get());
    }


}
