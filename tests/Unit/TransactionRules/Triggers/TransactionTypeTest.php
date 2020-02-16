<?php
/**
 * TransactionTypeTest.php
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

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\TransactionType;
use Tests\TestCase;

/**
 * Class TransactionTypeTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
