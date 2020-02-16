<?php
/**
 * FromAccountStartsTest.php
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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\TransactionRules\Triggers\FromAccountStarts;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class FromAccountStartsTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FromAccountStartsTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts
     */
    public function testTriggered(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        /** @var TransactionJournal $journal */
        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account    = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountStarts::makeFromStrings(substr($account->name, 0, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts
     */
    public function testTriggeredLonger(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account    = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountStarts::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts
     */
    public function testTriggeredNot(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        /** @var TransactionJournal $journal */
        $journal    = $this->user()->transactionJournals()->inRandomOrder()->first();
        $account    = $this->user()->accounts()->inRandomOrder()->first();
        $repository->shouldReceive('getSourceAccount')->once()->andReturn($account);

        $trigger = FromAccountStarts::makeFromStrings('some name' . random_int(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $value  = '';
        $result = FromAccountStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $value  = 'x';
        $result = FromAccountStarts::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountStarts
     */
    public function testWillMatchEverythingNull(): void
    {
        $repository   = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $value  = null;
        $result = FromAccountStarts::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
