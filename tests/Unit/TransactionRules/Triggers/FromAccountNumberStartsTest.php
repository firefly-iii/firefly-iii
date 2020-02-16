<?php
/**
 * FromAccountNumberStartsTest.php
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

use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts;
use Log;
use Tests\TestCase;

/**
 * Class FromAccountNumberStartsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FromAccountNumberStartsTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testTriggeredBoth(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();

        $account->iban = 'FR7620041010053537027625181';
        $account->save();
        $meta             = new AccountMeta;
        $meta->account_id = $account->id;
        $meta->name       = 'account_number';
        $meta->data       = 'FR7620041010053537027625181';
        $meta->save();

        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberStarts::makeFromStrings('FR76', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testTriggeredIban(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();

        $account->iban = 'FR7620041010053537027625181';
        $account->save();

        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberStarts::makeFromStrings('FR76', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testTriggeredNot(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();

        $account->iban = 'FR7620041010053537027625181';
        $account->save();
        $meta             = new AccountMeta;
        $meta->account_id = $account->id;
        $meta->name       = 'account_number';
        $meta->data       = 'FR7620041010053537027625181';
        $meta->save();

        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberStarts::makeFromStrings('FR76x', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testTriggeredNumber(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account = $this->user()->accounts()->inRandomOrder()->first();

        $meta             = new AccountMeta;
        $meta->account_id = $account->id;
        $meta->name       = 'account_number';
        $meta->data       = 'FR7620041010053537027625181';
        $meta->save();

        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountNumberStarts::makeFromStrings('FR76', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $value  = '';
        $result = FromAccountNumberStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $value  = 'x';
        $result = FromAccountNumberStarts::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountNumberStarts
     */
    public function testWillMatchEverythingNull(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $value  = null;
        $result = FromAccountNumberStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
