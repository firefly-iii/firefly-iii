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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;


use FireflyIII\Generator\Report\Audit\YearReportGenerator as AYRG;
use FireflyIII\Generator\Report\Budget\YearReportGenerator as BYRG;
use FireflyIII\Generator\Report\Category\YearReportGenerator as CYRG;
use FireflyIII\Generator\Report\Standard\YearReportGenerator as SYRG;
use FireflyIII\Generator\Report\Tag\YearReportGenerator as TYRG;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::auditReport
     */
    public function testAuditReport()
    {
        $generator    = $this->mock(AYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('generate')->andReturn('here-be-report')->once();


        $this->be($this->user());
        $response = $this->get(route('reports.report.audit', [1, '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::budgetReport
     */
    public function testBudgetReport()
    {
        $generator    = $this->mock(BYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setBudgets')->once();
        $generator->shouldReceive('generate')->andReturn('here-be-report')->once();

        $this->be($this->user());
        $response = $this->get(route('reports.report.budget', [1, 1, '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::categoryReport
     */
    public function testCategoryReport()
    {
        $generator    = $this->mock(CYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setCategories')->once();
        $generator->shouldReceive('generate')->andReturn('here-be-report')->once();

        $this->be($this->user());
        $response = $this->get(route('reports.report.category', [1, 1, '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::defaultReport
     */
    public function testDefaultReport()
    {
        $generator    = $this->mock(SYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('generate')->andReturn('here-be-report')->once();

        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20160131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::defaultReport
     */
    public function testDefaultReportBadDate()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20150131']));
        $response->assertStatus(200);
        $response->assertSee('End date of report must be after start date.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::index
     * @covers \FireflyIII\Http\Controllers\ReportController::__construct
     */
    public function testIndex()
    {
        $helper       = $this->mock(ReportHelperInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $helper->shouldReceive('listOfMonths')->andReturn([]);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::noReportOptions
     */
    public function testOptions()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['default']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::budgetReportOptions
     */
    public function testOptionsBudget()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budget      = factory(Budget::class)->make();
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection([$budget]));


        $this->be($this->user());
        $response = $this->get(route('reports.options', ['budget']));
        $response->assertStatus(200);
        $response->assertSee($budget->name);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::categoryReportOptions
     */
    public function testOptionsCategory()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $category      = factory(Category::class)->make();
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['category']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::tagReportOptions
     */
    public function testOptionsTag()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $tag      = factory(Tag::class)->make();
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['tag']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexAuditOK()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexBudgetError()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexBudgetOK()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexCategoryError()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexCategoryOK()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexDefaultOK()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexDefaultStartEnd()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexTagError()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexTagOK()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [
            'accounts'    => ['1'],
            'tag'         => ['housing'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'tag',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.tag', ['1', 'housing', '20160101', '20160131']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndexZeroAccounts()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

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
     * @covers \FireflyIII\Http\Controllers\ReportController::tagReport
     */
    public function testTagReport()
    {
        $generator    = $this->mock(TYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setTags')->once();
        $generator->shouldReceive('generate')->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.tag', [1, 'TagJanuary', '20160101', '20160131']));
        $response->assertStatus(200);
    }

}
