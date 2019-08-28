<?php
/**
 * ReportControllerTest.php
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

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Generator\Report\Account\YearReportGenerator as AcYRG;
use FireflyIII\Generator\Report\Audit\YearReportGenerator as AYRG;
use FireflyIII\Generator\Report\Budget\YearReportGenerator as BYRG;
use FireflyIII\Generator\Report\Category\YearReportGenerator as CYRG;
use FireflyIII\Generator\Report\Standard\YearReportGenerator as SYRG;
use FireflyIII\Generator\Report\Tag\YearReportGenerator as TYRG;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
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
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testAccountReport(): void
    {
        $this->mockDefaultSession();
        $this->mock(ReportHelperInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $generator        = $this->mock(AcYRG::class);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();
        $account          = $this->getRandomAsset();
        $expense          = $this->getRandomExpense();

        $budgetRepository->shouldReceive('cleanupBudgets');
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setExpense')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.account', [$account->id, $expense->id, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testAuditReport(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_reports_report_audit');
        $this->mock(ReportHelperInterface::class);

        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $generator        = $this->mock(AYRG::class);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();
        $account          = $this->getRandomAsset();

        $budgetRepository->shouldReceive('cleanupBudgets');

        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.audit', [$account->id, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testBudgetReport(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_reports_report_budget');
        $this->mock(ReportHelperInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $generator        = $this->mock(BYRG::class);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();

        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);

        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setBudgets')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.budget', [1, 1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testCategoryReport(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_reports_report_category');
        $this->mock(ReportHelperInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $generator        = $this->mock(CYRG::class);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();

        $budgetRepository->shouldReceive('cleanupBudgets');

        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);


        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setCategories')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.category', [1, 1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testDefaultReport(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_reports_report_default');
        $this->mock(ReportHelperInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $generator        = $this->mock(SYRG::class);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();

        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);

        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testDefaultReportBadDate(): void
    {
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_reports_report_default');
        $this->mock(ReportHelperInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();

        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);
        $budgetRepository->shouldReceive('cleanupBudgets');


        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20150131']));
        $response->assertStatus(200);
        $response->assertSee('End date of report must be after start date.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testIndex(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_reports_index');
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $userRepos        = $this->mock(UserRepositoryInterface::class);
        $helper           = $this->mock(ReportHelperInterface::class);
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);

        $budgetRepository->shouldReceive('cleanupBudgets');
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $helper->shouldReceive('listOfMonths')->andReturn([]);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();

        // get some preferences:
        $false       = new Preference;
        $false->data = false;
        Preferences::shouldReceive('get')->withArgs(['customFiscalYear', false])->andReturn($false);


        $this->be($this->user());
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testOptions(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['default']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testOptionsAccount(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $repository = $this->mock(AccountRepositoryInterface::class);

        $account       = new Account();
        $account->name = 'Something';
        $account->id   = 3;
        $collection    = new Collection([$account]);


        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::EXPENSE]])->once()->andReturn($collection);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::REVENUE]])->once()->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['account']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testOptionsBudget(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(ReportHelperInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budget      = $this->getRandomBudget();


        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection([$budget]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['budget']));
        $response->assertStatus(200);
        $response->assertSee($budget->name);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testOptionsCategory(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $category = $this->getRandomCategory();


        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['category']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testOptionsTag(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tag      = $this->getRandomTag();


        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['tag']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexAccountError(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $asset        = $this->getRandomAsset();
        // find the user's asset account
        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn($asset)->atLeast()->once();

        // do not find the exp_rev things.
        $accountRepos->shouldReceive('findNull')->withArgs([4])->andReturnNull()->atLeast()->once();


        $data = [
            'accounts'    => ['1'],
            'exp_rev'     => ['4'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'account',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexAccountOK(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $this->mockDefaultSession();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->times(4);


        $data = [
            'accounts'    => ['1'],
            'exp_rev'     => ['4'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'account',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.account', ['1', '1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexAuditOK(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'audit',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.audit', ['1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexBudgetError(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'budget'      => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'budget',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexBudgetOK(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);

        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();
        $budgetRepository->shouldReceive('findNull')->andReturn($this->user()->budgets()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'budget'      => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'budget',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.budget', ['1', '1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexCategoryError(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'category'    => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'category',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexCategoryOK(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);

        $categoryRepos->shouldReceive('findNull')->andReturn($this->user()->categories()->find(1))->twice();
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'category'    => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'category',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.category', ['1', '1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexDefaultOK(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'default',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.default', ['1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexDefaultStartEnd(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'daterange'   => '2016-01-01 - 2015-01-31',
            'report_type' => 'default',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(200);
        $response->assertSee('End date of report must be after start date.');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexTagError(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'tag'         => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'tag',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexTagOK(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);

        /** @var Tag $tag */
        $tag  = $this->user()->tags()->find(1);
        $tag2 = $this->user()->tags()->find(3);


        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $tagRepos->shouldReceive('findByTag')->andReturn($tag, null)->times(4);
        $tagRepos->shouldReceive('findNull')->andReturn($tag2)->times(3);

        $data = [
            'accounts'    => ['1'],
            'tag'         => ['housing', '3'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'tag',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.tag', ['1', $tag->id . ',' . $tag2->id, '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexTagOKNoID(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        Log::debug(sprintf('Now in test %s', __METHOD__));
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $tagRepos     = $this->mock(TagRepositoryInterface::class);


        $tag2 = $this->user()->tags()->find(3);


        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $tagRepos->shouldReceive('findByTag')->andReturn(null)->times(4);
        $tagRepos->shouldReceive('findNull')->andReturn($tag2)->times(4);

        $data = [
            'accounts'    => ['1'],
            'tag'         => ['housing', '3'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'tag',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.tag', ['1', $tag2->id . ',' . $tag2->id, '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexZeroAccounts(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);
        $data = [
            'accounts'    => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'default',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController
     */
    public function testTagReport(): void
    {
        $this->mockDefaultSession();
        $this->mock(BudgetRepositoryInterface::class);
        $this->mock(ReportHelperInterface::class);
        $this->mock(CategoryRepositoryInterface::class);
        $this->mock(TagRepositoryInterface::class);
        $this->mock(AccountRepositoryInterface::class);

        $this->mockIntroPreference('shown_demo_reports_report_tag');
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $generator        = $this->mock(TYRG::class);
        $fiscalHelper     = $this->mock(FiscalHelperInterface::class);
        $tag              = $this->user()->tags()->find(1);
        $start            = Carbon::now()->startOfYear();
        $end              = Carbon::now()->endOfYear();

        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($start);
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($end);
        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setTags')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.tag', [1, $tag->tag, '20160101', '20161231']));
        $response->assertStatus(200);
    }
}
