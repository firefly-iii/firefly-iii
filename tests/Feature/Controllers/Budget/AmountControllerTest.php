<?php
/**
 * AmountControllerTest.php
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

namespace Tests\Feature\Controllers\Budget;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 *
 * Class AmountControllerTest
 */
class AmountControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testAmount(): void
    {
        Log::debug('Now in testAmount()');
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');


        $data = ['amount' => 200, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testAmountLargeDiff(): void
    {
        Log::debug('Now in testAmount()');
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');


        $data = ['amount' => 20000, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
        $response->assertSee('Normally you budget about \u20ac10.00 per day.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testAmountOutOfRange(): void
    {
        Log::debug('Now in testAmountOutOfRange()');
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');

        $today = new Carbon;
        $start = $today->startOfMonth()->format('Y-m-d');
        $end   = $today->endOfMonth()->format('Y-m-d');
        $data  = ['amount' => 200, 'start' => $start, 'end' => $end];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testAmountZero(): void
    {
        Log::debug('Now in testAmountZero()');
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');

        $data = ['amount' => 0, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testInfoIncome(): void
    {
        Log::debug('Now in testInfoIncome()');
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->times(2);
        $collector->shouldReceive('setRange')->andReturnSelf()->times(2);
        $collector->shouldReceive('setTypes')->andReturnSelf()->times(2);
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->times(2);

        // collect transactions to return. First an expense, then income.
        $income                     = new Transaction;
        $income->transaction_amount = '150';
        $incomeCollection           = new Collection([$income]);
        $expense                     = new Transaction;
        $expense->transaction_amount = '100';
        $expenseCollection           = new Collection([$expense]);

        $collector->shouldReceive('getTransactions')->andReturn($incomeCollection, $expenseCollection)->times(2);



        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $repository->shouldReceive('getAverageAvailable')->andReturn('100.123')->once();

        $this->be($this->user());
        $response = $this->get(route('budgets.income.info', ['20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testInfoIncomeInversed(): void
    {
        Log::debug('Now in testInfoIncome()');
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->times(2);
        $collector->shouldReceive('setRange')->andReturnSelf()->times(2);
        $collector->shouldReceive('setTypes')->andReturnSelf()->times(2);
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->times(2);

        // collect transactions to return. First an expense, then income.
        $income                     = new Transaction;
        $income->transaction_amount = '100';
        $incomeCollection           = new Collection([$income]);
        $expense                     = new Transaction;
        $expense->transaction_amount = '150';
        $expenseCollection           = new Collection([$expense]);

        $collector->shouldReceive('getTransactions')->andReturn($incomeCollection, $expenseCollection)->times(2);



        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $repository->shouldReceive('getAverageAvailable')->andReturn('100.123')->once();

        $this->be($this->user());
        $response = $this->get(route('budgets.income.info', ['20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\AmountController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testInfoIncomeExpanded(string $range): void
    {
        Log::debug(sprintf('Now in testInfoIncomeExpanded(%s)', $range));
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $repository->shouldReceive('getAverageAvailable')->andReturn('100.123')->once();

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->times(2);
        $collector->shouldReceive('setRange')->andReturnSelf()->times(2);
        $collector->shouldReceive('setTypes')->andReturnSelf()->times(2);
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->times(2);

        // collect transactions to return. First an expense, then income.
        $income                     = new Transaction;
        $income->transaction_amount = '150';
        $incomeCollection           = new Collection([$income]);
        $expense                     = new Transaction;
        $expense->transaction_amount = '100';
        $expenseCollection           = new Collection([$expense]);

        $collector->shouldReceive('getTransactions')->andReturn($incomeCollection, $expenseCollection)->times(2);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.income.info', ['20170301', '20170430']));
        $response->assertStatus(200);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testPostUpdateIncome(): void
    {
        Log::debug('Now in testPostUpdateIncome()');
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('setAvailableBudget');
        $repository->shouldReceive('cleanupBudgets');

        $data = ['amount' => '200', 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.income.post'), $data);
        $response->assertStatus(302);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\AmountController
     */
    public function testUpdateIncome(): void
    {
        Log::debug('Now in testUpdateIncome()');
        // must be in list
        $this->be($this->user());

        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getAvailableBudget')->andReturn('1');
        $repository->shouldReceive('cleanupBudgets');

        $response = $this->get(route('budgets.income', ['2017-01-01', '2017-01-31']));
        $response->assertStatus(200);
    }
}
