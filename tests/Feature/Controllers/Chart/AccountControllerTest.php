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


use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseAccounts
     * @covers       \FireflyIII\Generator\Chart\Basic\GeneratorInterface::singleSet
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseAccounts(string $range)
    {
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseBudget
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseBudget(string $range)
    {
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
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.period', [1, '2012-01-01']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\AccountController::report
     */
    public function testReport()
    {
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
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.single', [1]));
        $response->assertStatus(200);
    }

}
