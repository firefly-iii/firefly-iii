<?php
/**
 * DeleteOrphanedTransactionsTest.php
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

/**
 * Class DeleteOrphanedTransactionsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DeleteOrphanedTransactionsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\DeleteOrphanedTransactions
     */
    public function testHandle(): void
    {
        // assume there are no orphaned transactions.
        $this->artisan('firefly-iii:delete-orphaned-transactions')
             ->expectsOutput('No orphaned transactions.')
             ->expectsOutput('No orphaned accounts.')
             ->assertExitCode(0);
    }

    /**
     *
     */
    public function testHandleOrphanedAccounts(): void
    {

        // create deleted account:
        $account = Account::create(
            [
                'user_id'         => 1,
                'name'            => 'Some account',
                'account_type_id' => 1,

            ]
        );
        $account->delete();

        // create NOT deleted journal + transaction.
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

        $transaction = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $account->id,
                'amount'                 => '5',
            ]
        );

        $this->artisan('firefly-iii:delete-orphaned-transactions')
             ->expectsOutput('No orphaned transactions.')
             ->expectsOutput(sprintf('Deleted transaction journal #%d because account #%d was already deleted.',
                                     $journal->id, $account->id))
             ->assertExitCode(0);

        // verify bad objects are gone.
        $this->assertCount(0, Transaction::where('id', $transaction->id)->whereNull('deleted_at')->get());
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
        $this->assertCount(0, Account::where('id', $account->id)->whereNull('deleted_at')->get());

    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteOrphanedTransactions
     */
    public function testHandleOrphanedTransactions(): void
    {
        // create deleted journal:
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
        $journal->delete();

        // create NOT deleted transaction.
        $transaction = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => 1,
                'amount'                 => '5',
            ]
        );

        $this->artisan('firefly-iii:delete-orphaned-transactions')
             ->expectsOutput(sprintf('Transaction #%d (part of deleted transaction journal #%d) has been deleted as well.',
                                     $transaction->id, $journal->id))
             ->expectsOutput('No orphaned accounts.')
             ->assertExitCode(0);

        // verify objects are gone.
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
        $this->assertCount(0, Transaction::where('id', $transaction->id)->whereNull('deleted_at')->get());
    }
}
