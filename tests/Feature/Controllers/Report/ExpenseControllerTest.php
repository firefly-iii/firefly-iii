<?php
/**
 * ExpenseControllerTest.php
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

namespace Tests\Feature\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class ExpenseControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpenseControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testBudget(): void
    {
        $expense    = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue    = $this->user()->accounts()->where('account_type_id', 5)->first();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        // fake collection:
        $transA                                  = new Transaction;
        $transA->transaction_currency_id         = 1;
        $transA->transaction_budget_name         = 'Budget';
        $transA->transaction_budget_id           = 1;
        $transA->transaction_currency_symbol     = 'A';
        $transA->transaction_currency_dp         = 2;
        $transA->transaction_amount              = '100';
        $transB                                  = new Transaction;
        $transB->transaction_currency_id         = 2;
        $transB->transaction_budget_name         = null;
        $transB->transaction_budget_id           = 0;
        $transB->transaction_journal_budget_name = 'Budget2';
        $transB->transaction_journal_budget_id   = 2;
        $transB->transaction_currency_symbol     = 'A';
        $transB->transaction_currency_dp         = 2;
        $transB->transaction_amount              = '100';
        $collection                              = new Collection([$transA, $transB]);

        // mock collector for spentByBudget (complex)
        $collector = $this->mock(TransactionCollectorInterface::class);
        // dont care about any calls, just return a default set of fake transactions:
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);


        $this->be($this->user());
        $response = $this->get(route('report-data.expense.budget', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testCategory(): void
    {
        $expense    = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue    = $this->user()->accounts()->where('account_type_id', 5)->first();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        // fake collection:
        $transA                                    = new Transaction;
        $transA->transaction_currency_id           = 1;
        $transA->transaction_category_name         = 'Category';
        $transA->transaction_category_id           = 1;
        $transA->transaction_currency_symbol       = 'A';
        $transA->transaction_currency_dp           = 2;
        $transA->transaction_amount                = '100';
        $transB                                    = new Transaction;
        $transB->transaction_currency_id           = 2;
        $transB->transaction_category_name         = null;
        $transB->transaction_category_id           = 0;
        $transB->transaction_journal_category_name = 'Category2';
        $transB->transaction_journal_category_id   = 2;
        $transB->transaction_currency_symbol       = 'A';
        $transB->transaction_currency_dp           = 2;
        $transB->transaction_amount                = '100';
        $collection                                = new Collection([$transA, $transB]);
        $transC                                    = new Transaction;
        $transC->transaction_currency_id           = 3;
        $transC->transaction_category_name         = null;
        $transC->transaction_category_id           = 0;
        $transC->transaction_journal_category_name = 'Category3';
        $transC->transaction_journal_category_id   = 3;
        $transC->transaction_currency_symbol       = 'A';
        $transC->transaction_currency_dp           = 2;
        $transC->transaction_amount                = '100';
        $secondCollection                          = new Collection([$transC]);

        // mock collector for spentByCategory and earnedByCategory (complex)
        $collector = $this->mock(TransactionCollectorInterface::class);
        // dont care about any calls, just return a default set of fake transactions:
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection, $secondCollection);
        //$collector->shouldReceive('')->andReturnSelf();

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.category', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testSpent(): void
    {
        $expense    = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue    = $this->user()->accounts()->where('account_type_id', 5)->first();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        // fake collection:
        $transA                                  = new Transaction;
        $transA->transaction_currency_id         = 1;
        $transA->transaction_category_name       = 'Category';
        $transA->transaction_category_id         = 1;
        $transA->transaction_currency_symbol     = 'A';
        $transA->transaction_currency_dp         = 2;
        $transA->transaction_amount              = '100';
        $transB                                  = new Transaction;
        $transB->transaction_currency_id         = 2;
        $transB->transaction_category_name       = null;
        $transB->transaction_category_id         = 0;
        $transB->transaction_journal_budget_name = 'Category2';
        $transB->transaction_journal_budget_id   = 2;
        $transB->transaction_currency_symbol     = 'A';
        $transB->transaction_currency_dp         = 2;
        $transB->transaction_amount              = '100';
        $collection                              = new Collection([$transA, $transB]);

        // mock collector for spentInPeriod and earnedInPeriod (complex)
        $collector = $this->mock(TransactionCollectorInterface::class);
        // dont care about any calls, just return a default set of fake transactions:
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.spent', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testTopExpense(): void
    {
        $expense    = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue    = $this->user()->accounts()->where('account_type_id', 5)->first();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        // fake collection:
        $transA                                  = new Transaction;
        $transA->transaction_currency_id         = 1;
        $transA->transaction_category_name       = 'Category';
        $transA->transaction_category_id         = 1;
        $transA->transaction_currency_symbol     = 'A';
        $transA->transaction_currency_dp         = 2;
        $transA->transaction_amount              = '100';
        $transA->opposing_account_id             = $expense->id;
        $transB                                  = new Transaction;
        $transB->transaction_currency_id         = 2;
        $transB->transaction_category_name       = null;
        $transB->transaction_category_id         = 0;
        $transB->transaction_journal_budget_name = 'Category2';
        $transB->transaction_journal_budget_id   = 2;
        $transB->transaction_currency_symbol     = 'A';
        $transB->transaction_currency_dp         = 2;
        $transB->transaction_amount              = '100';
        $transB->opposing_account_id             = $expense->id;
        $collection                              = new Collection([$transA, $transB]);

        // mock collector for topExpense (complex)
        $collector = $this->mock(TransactionCollectorInterface::class);
        // dont care about any calls, just return a default set of fake transactions:
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('report-data.expense.expenses', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\ExpenseController
     */
    public function testTopIncome(): void
    {
        $expense    = $this->user()->accounts()->where('account_type_id', 4)->first();
        $revenue    = $this->user()->accounts()->where('account_type_id', 5)->first();
        $repository = $this->mock(AccountRepositoryInterface::class);
        $fiscalHelper  = $this->mock(FiscalHelperInterface::class);
        $date          = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('findByName')->once()->withArgs([$expense->name, [AccountType::REVENUE]])->andReturn($revenue);

        // fake collection:
        $transA                                  = new Transaction;
        $transA->transaction_currency_id         = 1;
        $transA->transaction_category_name       = 'Category';
        $transA->transaction_category_id         = 1;
        $transA->transaction_currency_symbol     = 'A';
        $transA->transaction_currency_dp         = 2;
        $transA->transaction_amount              = '100';
        $transA->opposing_account_id             = $expense->id;
        $transB                                  = new Transaction;
        $transB->transaction_currency_id         = 2;
        $transB->transaction_category_name       = null;
        $transB->transaction_category_id         = 0;
        $transB->transaction_journal_budget_name = 'Category2';
        $transB->transaction_journal_budget_id   = 2;
        $transB->transaction_currency_symbol     = 'A';
        $transB->transaction_currency_dp         = 2;
        $transB->transaction_amount              = '100';
        $transB->opposing_account_id             = $expense->id;
        $collection                              = new Collection([$transA, $transB]);

        $collector = $this->mock(TransactionCollectorInterface::class);
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setOpposingAccounts')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($collection);
        //$collector->shouldReceive('')->andReturnSelf();


        $this->be($this->user());
        $response = $this->get(route('report-data.expense.income', ['1', $expense->id, '20170101', '20170131']));
        $response->assertStatus(200);
    }

}
