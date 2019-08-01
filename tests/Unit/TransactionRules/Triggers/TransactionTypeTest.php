<?php
/**
 * TransactionTypeTest.php
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
use FireflyIII\TransactionRules\Triggers\TransactionType;
use Tests\TestCase;

/**
 * Class TransactionTypeTest
 */
class TransactionTypeTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType
     */
    public function testTriggered(): void
    {
        $journal = $this->getRandomWithdrawal();
        $type    = $journal->transactionType->type;
        $trigger = TransactionType::makeFromStrings($type, false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType
     */
    public function testTriggeredFalse(): void
    {
        $journal = $this->getRandomWithdrawal();
        $trigger = TransactionType::makeFromStrings('NonExisting', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = TransactionType::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\TransactionType
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = TransactionType::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
