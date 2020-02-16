<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Popup;

use Amount;
use Carbon\Carbon;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBadEndDate(): void
    {
        $this->mock(PopupReportInterface::class);

        $this->mockDefaultSession();


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
        $response->assertStatus(200);
        $response->assertSee('Firefly III cannot handle');
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBadStartDate(): void
    {
        $this->mock(PopupReportInterface::class);

        $this->be($this->user());
        $this->mockDefaultSession();

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
        $response->assertStatus(200);
        $response->assertSee('Firefly III cannot handle');
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBudgetSpentAmount(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $budget        = $this->getRandomBudget();

        $this->mockDefaultSession();
        $budgetRepos->shouldReceive('findNull')->andReturn($budget)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byBudget')->andReturn([]);

        //Amount::shouldReceive('formatAnything')->andReturn('-100')->atLeast()->once();

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'budget-spent-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'currencyId' => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBudgetSpentAmountNoBudget(): void
    {
        $this->mock(AccountRepositoryInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper = $this->mock(PopupReportInterface::class);

        //Amount::shouldReceive('formatAnything')->andReturn('-100')->atLeast()->once();

        $this->mockDefaultSession();
        $budgetRepos->shouldReceive('findNull')->andReturnNull()->once()->withArgs([1]);
        $popupHelper->shouldReceive('byBudget')->andReturn([]);

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testCategoryEntry(): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $category      = $this->getRandomCategory();

        $this->mockDefaultSession();
        $categoryRepos->shouldReceive('findNull')->andReturn($category)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byCategory')->andReturn([]);

        //Amount::shouldReceive('formatAnything')->andReturn('-100')->atLeast()->once();

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testCategoryEntryUnknown(): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);

        $this->mockDefaultSession();
        $categoryRepos->shouldReceive('findNull')->andReturn(null)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byCategory')->andReturn([]);

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
        $response->assertSee('This is an unknown category. Apologies.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testExpenseEntry(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $account       = $this->getRandomAsset();

        //Amount::shouldReceive('formatAnything')->andReturn('-100')->atLeast()->once();

        $this->mockDefaultSession();
        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn($account)->once();
        $popupHelper->shouldReceive('byExpenses')->andReturn([]);

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testExpenseEntryUnknown(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $this->mockDefaultSession();
        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn(null)->once();
        $popupHelper->shouldReceive('byExpenses')->andReturn([]);

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
        $response->assertSee('This is an unknown account. Apologies.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testIncomeEntry(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $account       = $this->getRandomAsset();

        $this->mockDefaultSession();
        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn($account)->once();
        $popupHelper->shouldReceive('byIncome')->andReturn([]);

        //Amount::shouldReceive('formatAnything')->andReturn('-100')->atLeast()->once();

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testIncomeEntryUnknown(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $this->mockDefaultSession();
        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn(null)->once();
        $popupHelper->shouldReceive('byIncome')->andReturn([]);

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
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController
     * @expectedExceptionMessage Firefly cannot handle
     */
    public function testWrongLocation(): void
    {
        $popupReport = $this->mock(PopupReportInterface::class);

        $this->mockDefaultSession();
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
        $response->assertStatus(200);
        $response->assertDontSee('Firefly III could not render the view. Please see the log files.');
    }
}
