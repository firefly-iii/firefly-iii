<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Popup;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;


/**
 * Class ReportControllerTest
 *
 * @package Tests\Feature\Controllers\Popup
 */
class ReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::balanceAmount
     */
    public function testBalanceAmount()
    {
        $collector    = $this->mock(JournalCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $budget       = factory(Budget::class)->make();
        $account      = factory(Account::class)->make();

        $budgetRepos->shouldReceive('find')->andReturn($budget)->once()->withArgs([1]);
        $accountRepos->shouldReceive('find')->andReturn($account)->once()->withArgs([1]);
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->once()->andReturnSelf();
        $collector->shouldReceive('getJournals')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'balance-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
                'role'       => 3, // diff role, is complicated.
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::budgetSpentAmount()
     */
    public function testBudgetSpentAmount()
    {
        $collector   = $this->mock(JournalCollectorInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budget      = factory(Budget::class)->make();

        $budgetRepos->shouldReceive('find')->andReturn($budget)->once()->withArgs([1]);
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('setBudget')->once()->andReturnSelf();
        $collector->shouldReceive('getJournals')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'budget-spent-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::categoryEntry()
     */
    public function testCategoryEntry()
    {
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $category      = factory(Category::class)->make();

        $categoryRepos->shouldReceive('find')->andReturn($category)->once()->withArgs([1]);
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->once()->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]]);
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('setCategory')->once()->andReturnSelf();
        $collector->shouldReceive('getJournals')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'category-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::expenseEntry()
     */
    public function testExpenseEntry()
    {
        $collector    = $this->mock(JournalCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = factory(Account::class)->make();

        $accountRepos->shouldReceive('find')->withArgs([1])->andReturn($account)->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->once()->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]]);
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('getJournals')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'expense-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::incomeEntry()
     */
    public function testIncomeEntry()
    {
        $collector    = $this->mock(JournalCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = factory(Account::class)->make();

        $accountRepos->shouldReceive('find')->withArgs([1])->andReturn($account)->once();
        $collector->shouldReceive('setAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setTypes')->andReturnSelf()->once()->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]]);
        $collector->shouldReceive('setRange')->once()->andReturnSelf();
        $collector->shouldReceive('getJournals')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'income-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }


}
