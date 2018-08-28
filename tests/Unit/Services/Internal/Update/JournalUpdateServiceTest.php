<?php
/**
 * JournalUpdateServiceTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Services\Internal\Update;


use Carbon\Carbon;
use FireflyIII\Factory\BillFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Services\Internal\Update\TransactionUpdateService;
use Mockery;
use Tests\TestCase;

/**
 * Class JournalUpdateServiceTest
 */
class JournalUpdateServiceTest extends TestCase
{
    /**
     * @covers \FireflyIII\Services\Internal\Update\JournalUpdateService
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testUpdateBasic(): void
    {
        // mock other stuff:
        $transactionFactory = $this->mock(TransactionFactory::class);
        $transactionService = $this->mock(TransactionUpdateService::class);
        $billFactory        = $this->mock(BillFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);

        // mock calls
        $billFactory->shouldReceive('setUser');
        $billFactory->shouldReceive('find')->andReturn(null);
        $transactionService->shouldReceive('setUser');
        $transactionFactory->shouldReceive('setUser');
        $tagFactory->shouldReceive('setUser');
        $metaFactory->shouldReceive('setUser');

        $metaFactory->shouldReceive('updateOrCreate');

        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $data    = [
            'description'  => 'Updated journal #' . random_int(1, 10000),
            'date'         => new Carbon('2018-01-01'),
            'bill_id'      => null,
            'bill_name'    => null,
            'tags'         => [],
            'notes'        => 'Hello',
            'transactions' => [],
        ];

        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $result  = $service->update($journal, $data);

        $this->assertEquals($data['description'], $result->description);
        $this->assertEquals(0, $result->transactions()->count());
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\JournalUpdateService
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testUpdateBasicEmptyNote(): void
    {
        // mock other stuff:
        $transactionFactory = $this->mock(TransactionFactory::class);
        $transactionService = $this->mock(TransactionUpdateService::class);
        $billFactory        = $this->mock(BillFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);

        // mock calls
        $billFactory->shouldReceive('setUser');
        $billFactory->shouldReceive('find')->andReturn(null);
        $transactionService->shouldReceive('setUser');
        $transactionFactory->shouldReceive('setUser');
        $tagFactory->shouldReceive('setUser');
        $metaFactory->shouldReceive('setUser');
        $metaFactory->shouldReceive('updateOrCreate');


        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 2)->first();
        $data    = [
            'description'  => 'Updated journal #' . random_int(1, 10000),
            'date'         => new Carbon('2018-01-01'),
            'bill_id'      => null,
            'bill_name'    => null,
            'tags'         => [],
            'notes'        => '',
            'transactions' => [],
        ];

        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $result  = $service->update($journal, $data);

        $this->assertEquals($data['description'], $result->description);
        $this->assertEquals(0, $result->transactions()->count());
        $this->assertEquals(0, $result->notes()->count());
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\JournalUpdateService
     */
    public function testUpdateBudget(): void
    {
        $budget  = $this->user()->budgets()->first();
        $service = $this->mock(TransactionUpdateService::class);
        $service->shouldReceive('setUser');
        $service->shouldReceive('updateBudget')->withArgs([Mockery::any(), $budget->id])->twice();


        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 1)->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        // call update service to update budget. Should call transaction service twice.
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->updateBudget($journal, $budget->id);
    }

    /**
     * @covers \FireflyIII\Services\Internal\Update\JournalUpdateService
     */
    public function testUpdateCategory(): void
    {
        $service = $this->mock(TransactionUpdateService::class);
        $service->shouldReceive('setUser');
        $service->shouldReceive('updateCategory')->withArgs([Mockery::any(), 'New category'])->twice();


        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 1)->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        // call update service to update budget. Should call transaction service twice.
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->updateCategory($journal, 'New category');
    }


    /**
     * @covers \FireflyIII\Services\Internal\Update\JournalUpdateService
     * @covers \FireflyIII\Services\Internal\Support\JournalServiceTrait
     */
    public function testUpdateLotsOfTransactions(): void
    {
        // mock other stuff:
        $transactionFactory = $this->mock(TransactionFactory::class);
        $transactionService = $this->mock(TransactionUpdateService::class);
        $billFactory        = $this->mock(BillFactory::class);
        $tagFactory         = $this->mock(TagFactory::class);
        $metaFactory        = $this->mock(TransactionJournalMetaFactory::class);


        // mock calls
        $billFactory->shouldReceive('setUser');
        $billFactory->shouldReceive('find')->andReturn(null);
        $transactionService->shouldReceive('setUser');
        $transactionFactory->shouldReceive('setUser');
        $transactionService->shouldReceive('update')->times(2);
        $transactionFactory->shouldReceive('createPair')->times(2);
        $tagFactory->shouldReceive('setUser');
        $metaFactory->shouldReceive('setUser');
        $metaFactory->shouldReceive('updateOrCreate');


        /** @var TransactionJournal $journal */
        $journal = $this->user()->transactionJournals()->skip(4)->where('transaction_type_id', 1)->first();
        $data    = [
            'description'  => 'Updated journal #' . random_int(1, 10000),
            'date'         => new Carbon('2018-01-01'),
            'bill_id'      => null,
            'bill_name'    => null,
            'tags'         => [],
            'notes'        => 'Hello',
            'transactions' => [
                ['identifier' => 0],
                ['identifier' => 1],
                ['identifier' => 2],
            ],
        ];

        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $result  = $service->update($journal, $data);

        $this->assertEquals($data['description'], $result->description);
        $this->assertEquals(2, $result->transactions()->count());
    }

}
