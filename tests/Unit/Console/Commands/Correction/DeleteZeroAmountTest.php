<?php
/**
 * DeleteZeroAmountTest.php
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

/**
 * Class DeleteZeroAmountTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DeleteZeroAmountTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\DeleteZeroAmount
     */
    public function testHandle(): void
    {
        // assume there are no transactions with a zero amount.
        $this->artisan('firefly-iii:delete-zero-amount')
             ->expectsOutput('No zero-amount transaction journals.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteZeroAmount
     */
    public function testHandleTransactions(): void
    {
        $account = $this->getRandomAsset();
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
                'amount'                 => '0',
            ]
        );


        // assume there are no transactions with a zero amount.
        $this->artisan('firefly-iii:delete-zero-amount')
             ->expectsOutput(sprintf('Deleted transaction journal #%d because the amount is zero (0.00).', $journal->id))
             ->assertExitCode(0);

        // verify objects are gone.
        $this->assertCount(0, Transaction::where('id', $transaction->id)->whereNull('deleted_at')->get());
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
    }
}