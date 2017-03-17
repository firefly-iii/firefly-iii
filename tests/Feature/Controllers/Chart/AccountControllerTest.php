<?php
/**
 * AccountControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers\Chart;


use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Steam;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @package Tests\Feature\Controllers\Chart
 */
class AccountControllerTest extends TestCase
{

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::all
     */
    public function testAll()
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('oldestJournalDate')->once()->andReturn(Carbon::now()->subMonth());
        Steam::shouldReceive('balanceInRange')->andReturn(['2012-01-01' => '0']);
        $generator->shouldReceive('singleSet')->andReturn([]);

        $this->be($this->user());
        $response = $this->get(route('chart.account.all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseAccounts
     * @covers       \FireflyIII\Generator\Chart\Basic\GeneratorInterface::singleSet
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseAccounts(string $range)
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::EXPENSE, AccountType::BENEFICIARY]])->andReturn(new Collection);
        $generator->shouldReceive('singleSet')->andReturn([]);
        Steam::shouldReceive('balancesById')->twice()->andReturn([]);


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseBudget
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::getBudgetNames
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseBudget(string $range)
    {
        $generator   = $this->mock(GeneratorInterface::class);
        $collector   = $this->mock(JournalCollectorInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $generator->shouldReceive('pieChart')->andReturn([]);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection);


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense-budget', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseCategory
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseCategory(string $range)
    {
        $generator     = $this->mock(GeneratorInterface::class);
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $generator->shouldReceive('pieChart')->andReturn([]);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense-category', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::frontpage
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::__construct
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::accountBalanceChart
     * @covers       \FireflyIII\Generator\Chart\Basic\GeneratorInterface::multiSet
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range)
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection);
        Steam::shouldReceive('balanceInRange')->andReturn([]);
        $generator->shouldReceive('multiSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::incomeCategory
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIncomeCategory(string $range)
    {
        $generator     = $this->mock(GeneratorInterface::class);
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $generator->shouldReceive('pieChart')->andReturn([]);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.income-category', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::period
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testPeriod(string $range)
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('oldestJournalDate')->andReturn(new Carbon);
        Steam::shouldReceive('balanceInRange')->andReturn(['2012-01-01' => '0']);
        $generator->shouldReceive('singleSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.period', [1, '2012-01-01']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\AccountController::report
     * @covers \FireflyIII\Http\Controllers\Chart\AccountController::accountBalanceChart
     */
    public function testReport()
    {
        $generator = $this->mock(GeneratorInterface::class);
        $generator->shouldReceive('multiSet')->andreturn([]);
        Steam::shouldReceive('balanceInRange')->andReturn(['2012-01-01' => '0']);

        $this->be($this->user());
        $response = $this->get(route('chart.account.report', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::revenueAccounts
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testRevenueAccounts(string $range)
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::REVENUE]])->andReturn(new Collection);
        $generator->shouldReceive('singleSet')->andReturn([]);
        Steam::shouldReceive('balancesById')->twice()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.revenue'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::single
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testSingle(string $range)
    {
        $generator = $this->mock(GeneratorInterface::class);

        Steam::shouldReceive('balanceInRange')->andReturn(['2012-01-01' => '0']);
        $generator->shouldReceive('singleSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.single', [1]));
        $response->assertStatus(200);
    }

}
