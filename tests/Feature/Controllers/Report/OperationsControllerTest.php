<?php
/**
 * OperationsControllerTest.php
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

namespace Tests\Feature\Controllers\Report;


use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Tests\TestCase;

/**
 * Class OperationsControllerTest
 *
 * @package Tests\Feature\Controllers\Report
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OperationsControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController::expenses
     */
    public function testExpenses()
    {
        $transactions = factory(Transaction::class, 10)->make();
        $collector    = $this->mock(JournalCollectorInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn($transactions);


        $this->be($this->user());
        $response = $this->get(route('report-data.operations.expenses', ['1', '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController::income
     */
    public function testIncome()
    {
        $transactions = factory(Transaction::class, 10)->make();
        $collector    = $this->mock(JournalCollectorInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn($transactions);

        $this->be($this->user());
        $response = $this->get(route('report-data.operations.income', ['1', '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\OperationsController::operations
     */
    public function testOperations()
    {
        $collector    = $this->mock(JournalCollectorInterface::class);
        $transactions = factory(Transaction::class, 10)->make();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn($transactions);

        $this->be($this->user());
        $response = $this->get(route('report-data.operations.operations', ['1', '20160101', '20160131']));
        $response->assertStatus(200);
    }

}
