<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Popup;


use Carbon\Carbon;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;


/**
 * Class ReportControllerTest
 *
 * @package Tests\Feature\Controllers\Popup
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportControllerTest extends TestCase
{

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::__construct
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @expectedExceptionMessage Could not parse end date
     */
    public function testBadEndDate()
    {
        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'bla-bla',
                'startDate'  => Carbon::now()->endOfMonth()->format('Ymd'),
                'endDate'    => 'bla-bla',
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(500);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @expectedExceptionMessage Could not parse start date
     */
    public function testBadStartDate()
    {
        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'bla-bla',
                'startDate'  => 'bla-bla',
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::balanceAmount
     */
    public function testBalanceAmountDefaultNoBudget()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper  = $this->mock(PopupReportInterface::class);
        $account      = factory(Account::class)->make();

        $popupHelper->shouldReceive('balanceForNoBudget')->once()->andReturn(new Collection);
        $budgetRepos->shouldReceive('find')->andReturn(new Budget)->once()->withArgs([0]);
        $accountRepos->shouldReceive('find')->andReturn($account)->once()->withArgs([1]);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'balance-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 0,
                'role'       => 1, // ROLE_DEFAULTROLE
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::balanceAmount
     */
    public function testBalanceAmountDefaultRole()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper  = $this->mock(PopupReportInterface::class);
        $budget       = factory(Budget::class)->make();
        $account      = factory(Account::class)->make();

        $budgetRepos->shouldReceive('find')->andReturn($budget)->once()->withArgs([1]);
        $accountRepos->shouldReceive('find')->andReturn($account)->once()->withArgs([1]);
        $popupHelper->shouldReceive('balanceForBudget')->once()->andReturn(new Collection);

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
                'role'       => 1, // ROLE_DEFAULTROLE
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::balanceAmount
     */
    public function testBalanceAmountDiffRole()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper  = $this->mock(PopupReportInterface::class);


        $budget  = factory(Budget::class)->make();
        $account = factory(Account::class)->make();

        $budgetRepos->shouldReceive('find')->andReturn($budget)->once()->withArgs([1]);
        $accountRepos->shouldReceive('find')->andReturn($account)->once()->withArgs([1]);
        $popupHelper->shouldReceive('balanceDifference')->once()->andReturn(new Collection);

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
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::balanceAmount
     * @expectedExceptionMessage Firefly cannot handle this type of info-button
     */
    public function testBalanceAmountTagRole()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $budget       = factory(Budget::class)->make();
        $account      = factory(Account::class)->make();

        $budgetRepos->shouldReceive('find')->andReturn($budget)->once()->withArgs([1]);
        $accountRepos->shouldReceive('find')->andReturn($account)->once()->withArgs([1]);

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
                'role'       => 2, // ROLE_TAGROLE
            ],
        ];


        $uri      = route('popup.general') . '?' . http_build_query($arguments);
        $response = $this->get($uri);
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController::budgetSpentAmount()
     */
    public function testBudgetSpentAmount()
    {
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper = $this->mock(PopupReportInterface::class);
        $budget      = factory(Budget::class)->make();

        $budgetRepos->shouldReceive('find')->andReturn($budget)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byBudget')->andReturn(new Collection);

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
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $category      = factory(Category::class)->make();

        $categoryRepos->shouldReceive('find')->andReturn($category)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byCategory')->andReturn(new Collection);

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
        $popupHelper  = $this->mock(PopupReportInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = factory(Account::class)->make();

        $accountRepos->shouldReceive('find')->withArgs([1])->andReturn($account)->once();
        $popupHelper->shouldReceive('byExpenses')->andReturn(new Collection);

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
        $popupHelper  = $this->mock(PopupReportInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = factory(Account::class)->make();

        $accountRepos->shouldReceive('find')->withArgs([1])->andReturn($account)->once();
        $popupHelper->shouldReceive('byIncome')->andReturn(new Collection);

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

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::general
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController::parseAttributes
     * @expectedExceptionMessage Firefly cannot handle
     */
    public function testWrongLocation()
    {
        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'bla-bla',
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
        $response->assertStatus(500);
    }


}
