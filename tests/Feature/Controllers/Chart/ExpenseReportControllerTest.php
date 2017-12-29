<?php
/**
 * ExpenseReportControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class ExpenseReportControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpenseReportControllerTest extends TestCase
{


    /**
     * @covers \FireflyIII\Http\Controllers\Chart\ExpenseReportController::__construct
     * @covers \FireflyIII\Http\Controllers\Chart\ExpenseReportController::groupByName
     * @covers \FireflyIII\Http\Controllers\Chart\ExpenseReportController::mainChart()
     * @covers \FireflyIII\Http\Controllers\Chart\ExpenseReportController::getExpenses
     * @covers \FireflyIII\Http\Controllers\Chart\ExpenseReportController::getIncome
     * @covers \FireflyIII\Http\Controllers\Chart\ExpenseReportController::combineAccounts
     */
    public function testMainChart()
    {
        $expense           = $this->user()->accounts()->where('account_type_id', 4)->first();
        $generator         = $this->mock(GeneratorInterface::class);
        $collector         = $this->mock(JournalCollectorInterface::class);
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('findByName')->once()->andReturn($expense);

        $set                                = new Collection;
        $transaction                        = new Transaction();
        $transaction->opposing_account_name = 'Somebody';
        $transaction->transaction_amount    = '5';
        $set->push($transaction);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn($set);
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.expense.main', ['1', $expense->id, '20120101', '20120131']));
        $response->assertStatus(200);
    }

}
