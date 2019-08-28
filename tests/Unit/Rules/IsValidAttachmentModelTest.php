<?php
declare(strict_types=1);
/**
 * IsValidAttachmentModelTest.php
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

namespace Tests\Unit\Rules;


use FireflyIII\Models\Bill;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalAPIRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Rules\IsValidAttachmentModel;
use Log;
use Tests\TestCase;

/**
 * Class IsValidAttachmentModelTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IsValidAttachmentModelTest extends TestCase
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
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testBillFull(): void
    {
        $bill      = $this->getRandomBill();
        $billRepos = $this->mock(BillRepositoryInterface::class);

        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('find')->atLeast()->once()->withArgs([$bill->id])->andReturn($bill);

        $value     = $bill->id;
        $attribute = 'not-important';
        $this->be($this->user());
        $engine    = new IsValidAttachmentModel(Bill::class);
        $this->assertTrue($engine->passes($attribute, $value));
    }

    /**
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testImportJob(): void
    {
        $job      = $this->getRandomImportJob();
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);

        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('find')->atLeast()->once()->withArgs([$job->id])->andReturn($job);

        $value     = $job->id;
        $attribute = 'not-important';
        $this->be($this->user());
        $engine    = new IsValidAttachmentModel(ImportJob::class);
        $this->assertTrue($engine->passes($attribute, $value));
    }

    /**
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testTransaction(): void
    {
        $transaction     = $this->getRandomWithdrawal()->transactions()->first();
        $apiJournalRepos = $this->mock(JournalAPIRepositoryInterface::class);

        $apiJournalRepos->shouldReceive('setUser')->atLeast()->once();
        $apiJournalRepos->shouldReceive('findTransaction')->atLeast()->once()->withArgs([$transaction->id])->andReturn($transaction);

        $value     = $transaction->id;
        $attribute = 'not-important';
        $this->be($this->user());
        $engine    = new IsValidAttachmentModel(Transaction::class);
        $this->assertTrue($engine->passes($attribute, $value));
    }

    /**
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testTransactionJournal(): void
    {
        $journal      = $this->getRandomWithdrawal();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->atLeast()->once();
        $journalRepos->shouldReceive('findNull')->atLeast()->once()->withArgs([$journal->id])->andReturn($journal);

        $value     = $journal->id;
        $attribute = 'not-important';
        $this->be($this->user());
        $engine    = new IsValidAttachmentModel(TransactionJournal::class);
        $this->assertTrue($engine->passes($attribute, $value));
    }


    /**
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testBadModel(): void
    {
        $value     = '123';
        $attribute = 'not-important';
        $this->be($this->user());
        $engine    = new IsValidAttachmentModel('False');
        $this->assertFalse($engine->passes($attribute, $value));
    }

    /**
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testBillPartial(): void
    {
        $bill      = $this->getRandomBill();
        $billRepos = $this->mock(BillRepositoryInterface::class);

        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('find')->atLeast()->once()->withAnyArgs([$bill->id])->andReturn($bill);

        $value     = $bill->id;
        $attribute = 'not-important';
        $this->be($this->user());
        $engine    = new IsValidAttachmentModel('Bill');
        $this->assertTrue($engine->passes($attribute, $value));
    }

    /**
     * @covers \FireflyIII\Rules\IsValidAttachmentModel
     */
    public function testNotLoggedIn(): void
    {
        $value     = '1';
        $attribute = 'not-important';
        $engine    = new IsValidAttachmentModel(Bill::class);
        $this->assertFalse($engine->passes($attribute, $value));
    }

}
