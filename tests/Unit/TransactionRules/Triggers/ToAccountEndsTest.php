<?php
/**
 * ToAccountEndsTest.php
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
use FireflyIII\TransactionRules\Triggers\ToAccountEnds;
use Tests\TestCase;

/**
 * Class ToAccountEndsTest
 */
class ToAccountEndsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::triggered
     */
    public function testTriggered(): void
    {
        $count = 0;
        while ($count === 0) {
            $journal     = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count       = $journal->transactions()->where('amount', '>', 0)->count();
            $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        }

        $account = $transaction->account;
        $trigger = ToAccountEnds::makeFromStrings(substr($account->name, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::triggered
     */
    public function testTriggeredLonger(): void
    {
        $count = 0;
        while ($count === 0) {
            $journal     = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count       = $journal->transactions()->where('amount', '>', 0)->count();
            $transaction = $journal->transactions()->where('amount', '>', 0)->first();
        }
        $account     = $transaction->account;

        $trigger = ToAccountEnds::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::triggered
     */
    public function testTriggeredNot(): void
    {
        $count = 0;
        while ($count === 0) {
            $journal     = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count       = $journal->transactions()->where('amount', '>', 0)->count();
        }

        $trigger = ToAccountEnds::makeFromStrings((string)random_int(1, 1234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $value  = '';
        $result = ToAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = ToAccountEnds::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\ToAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = ToAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
