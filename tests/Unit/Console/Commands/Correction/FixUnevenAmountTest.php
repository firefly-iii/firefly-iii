<?php
/**
 * FixUnevenAmountTest.php
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
/**
 * FixUnevenAmountTest.php
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
use FireflyIII\Models\TransactionType;
use Log;
use Tests\TestCase;

/**
 * Class FixUnevenAmountTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FixUnevenAmountTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\FixUnevenAmount
     */
    public function testHandle(): void
    {
        // assume there's nothing to fix.
        $this->artisan('firefly-iii:fix-uneven-amount')
             ->expectsOutput('Amount integrity OK!')
             ->assertExitCode(0);

        // dont verify anything
    }

    /**
     * Create uneven journal
     * @covers \FireflyIII\Console\Commands\Correction\FixUnevenAmount
     */
    public function testHandleUneven(): void
    {
        $asset      = $this->getRandomAsset();
        $expense    = $this->getRandomExpense();
        $withdrawal = TransactionType::where('type', TransactionType::WITHDRAWAL)->first();
        $journal    = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => $withdrawal->id,
                'description'             => 'Test',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $one        = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $asset->id,
                'amount'                 => '-10',
            ]
        );
        $two        = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => $expense->id,
                'amount'                 => '12',
            ]
        );

        $this->artisan('firefly-iii:fix-uneven-amount')
             ->expectsOutput(sprintf('Corrected amount in transaction journal #%d', $journal->id))
             ->assertExitCode(0);

        // verify change.
        $this->assertCount(1, Transaction::where('id', $one->id)->where('amount', '-10')->get());
        $this->assertCount(1, Transaction::where('id', $two->id)->where('amount', '10')->get());
    }

}
