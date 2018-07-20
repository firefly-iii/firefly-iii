<?php
/**
 * FromAccountIsTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\FromAccountIs;
use Tests\TestCase;
use Log;
/**
 * Class FromAccountIsTest
 */
class FromAccountIsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountIs::triggered
     */
    public function testTriggered(): void
    {
        $loops = 0; // FINAL LOOP METHOD.
        do {
            /** @var TransactionJournal $journal */
            $journal     = $this->user()->transactionJournals()->inRandomOrder()->whereNull('deleted_at')->first();
            $transaction = $journal->transactions()->where('amount', '<', 0)->first();
            $account     = null === $transaction ? null : $transaction->account;
            $count       = $journal->transactions()->count();

            Log::debug(sprintf('Loop: %d, transaction count: %d, account is null: %d', $loops, $count, (int)null===$account));

            $loops++;

            // do this until:  account is not null, journal has two transactions, loops is below 30
        } while (!(null !== $account && 2 === $count && $loops < 30));


        $trigger = FromAccountIs::makeFromStrings($account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountIs::triggered
     */
    public function testTriggeredNot(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();

        $trigger = FromAccountIs::makeFromStrings('some name' . random_int(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountIs::willMatchEverything
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $value  = '';
        $result = FromAccountIs::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountIs::willMatchEverything
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = FromAccountIs::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountIs::willMatchEverything
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = FromAccountIs::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
