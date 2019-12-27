<?php
/**
 * FromAccountNumberContainsTest.php
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

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\TransactionRules\Triggers\FromAccountNumberContains;
use Tests\TestCase;

/**
 * Class FromAccountNumberContainsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FromAccountNumberContainsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testTriggeredBoth(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();

        $account->iban = 'FR7620041010053537027625181';
        $account->save();
        $meta = new AccountMeta;
        $meta->account_id = $account->id;
        $meta->name = 'account_number';
        $meta->data= '7027625181';
        $meta->save();

        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberContains::makeFromStrings('7027', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);

        $meta->forceDelete();

    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testTriggeredIban(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $account->iban = 'FR7620041010053537027625181';
        $account->save();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberContains::makeFromStrings('7027', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);

    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testTriggeredNot(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberContains::makeFromStrings('some name' . random_int(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testTriggeredNumber(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $meta = new AccountMeta;
        $meta->account_id = $account->id;
        $meta->name = 'account_number';
        $meta->data= '7027625181';
        $meta->save();

        $trigger = FromAccountNumberContains::makeFromStrings('276251', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
        $meta->forceDelete();
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $value      = '';
        $result     = FromAccountNumberContains::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $value      = 'x';
        $result     = FromAccountNumberContains::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberContains
     */
    public function testWillMatchEverythingNull(): void
    {
        $repository = $this->mock(JournalRepositoryInterface::class);
        $value      = null;
        $result     = FromAccountNumberContains::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
