<?php
/**
 * FromAccountContainsTest.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\FromAccountContains;
use Tests\TestCase;

/**
 * Class FromAccountContainsTest
 *
 * @package Tests\Unit\TransactionRules\Triggers
 */
class FromAccountContainsTest extends TestCase
{

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountContains::triggered
     */
    public function testTriggered()
    {
        $journal     = TransactionJournal::find(20);
        $transaction = $journal->transactions()->where('amount', '<', 0)->first();
        $account     = $transaction->account;

        $trigger = FromAccountContains::makeFromStrings($account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountContains::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::find(21);

        $trigger = FromAccountContains::makeFromStrings('some name' . rand(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountContains::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = FromAccountContains::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountContains::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = FromAccountContains::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountContains::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = FromAccountContains::willMatchEverything($value);
        $this->assertTrue($result);
    }


}
