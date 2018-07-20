<?php
/**
 * FromAccountStartsTest.php
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
use FireflyIII\TransactionRules\Triggers\FromAccountStarts;
use Log;
use Tests\TestCase;

/**
 * Class FromAccountStartsTest
 */
class FromAccountStartsTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts::triggered
     */
    public function testTriggered(): void
    {
        Log::debug('In testTriggered()');
        $loops = 0; // FINAL LOOP METHOD.
        do {
            /** @var TransactionJournal $journal */
            $journal     = $this->user()->transactionJournals()->inRandomOrder()->whereNull('deleted_at')->first();
            $transaction = $journal->transactions()->where('amount', '<', 0)->first();
            $account     = null === $transaction ? null : $transaction->account;
            $count       = $journal->transactions()->count();
            $name        = $account->name ?? '';

            Log::debug(sprintf('Loop: %d, transaction count: %d, account is null: %d, name = "%s"', $loops, $count, (int)null === $account, $name));

            $loops++;

            // do this while the following is untrue:
            // 1) account is not null,
            // 2) journal has two transactions
            // 3) loops is less than 30
            // 4) $name is longer than 3
        } while (!(null !== $account && 2 === $count && $loops < 30 && \strlen($name) > 3));

        $trigger = FromAccountStarts::makeFromStrings(substr($account->name, 0, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts::triggered
     */
    public function testTriggeredLonger(): void
    {
        Log::debug('In testTriggeredLonger()');
        $loops = 0; // FINAL LOOP METHOD.
        do {
            /** @var TransactionJournal $journal */
            $journal     = $this->user()->transactionJournals()->inRandomOrder()->whereNull('deleted_at')->first();
            $transaction = $journal->transactions()->where('amount', '<', 0)->first();
            $account     = null === $transaction ? null : $transaction->account;
            $count       = $journal->transactions()->count();
            $name        = $account->name ?? '';

            Log::debug(sprintf('Loop: %d, transaction count: %d, account is null: %d, name = "%s"', $loops, $count, (int)null === $account, $name));

            $loops++;

            // do this while the following is untrue:
            // 1) account is not null,
            // 2) journal has two transactions
            // 3) loops is less than 30
            // 4) $name is longer than 3
        } while (!(null !== $account && 2 === $count && $loops < 30 && \strlen($name) > 3));

        $trigger = FromAccountStarts::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts::triggered
     */
    public function testTriggeredNot(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();

        $trigger = FromAccountStarts::makeFromStrings('some name' . random_int(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts::willMatchEverything
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $value  = '';
        $result = FromAccountStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts::willMatchEverything
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = FromAccountStarts::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts::willMatchEverything
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = FromAccountStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
