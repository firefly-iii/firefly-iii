<?php
/**
 * ReportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;


use FireflyIII\Generator\Report\Audit\YearReportGenerator as AYRG;
use FireflyIII\Generator\Report\Budget\YearReportGenerator as BYRG;
use FireflyIII\Generator\Report\Category\YearReportGenerator as CYRG;
use FireflyIII\Generator\Report\Standard\YearReportGenerator as SYRG;
use FireflyIII\Generator\Report\Tag\YearReportGenerator as TYRG;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @package Tests\Feature\Controllers
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
        $generator->shouldReceive('generate')->andReturn('here-be-report');


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
        $generator->shouldReceive('generate')->andReturn('here-be-report');

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
        $generator->shouldReceive('generate')->andReturn('here-be-report');

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
        $generator->shouldReceive('generate')->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20160131']));
        $response->assertStatus(200);
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
     * @covers \FireflyIII\Http\Controllers\ReportController::postIndex
     */
    public function testPostIndex()
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::categoryReport
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
