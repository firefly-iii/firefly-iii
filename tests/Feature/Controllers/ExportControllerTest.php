<?php
/**
 * ExportControllerTest.php
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

use Carbon\Carbon;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class ExportControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ExportController::download
     */
    public function testDownload()
    {
        // mock stuff
        $repository   = $this->mock(ExportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('Some content beep boop');

        $this->be($this->user());
        $response = $this->get(route('export.download', ['testExport']));
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\ExportController::download
     * @expectedExceptionMessage Against all expectations
     */
    public function testDownloadFailed()
    {
        // mock stuff
        $repository   = $this->mock(ExportJobRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('exists')->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('export.download', ['testExport']));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ExportController::getStatus
     */
    public function testGetStatus()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('export.status', ['testExport']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ExportController::index
     * @covers \FireflyIII\Http\Controllers\ExportController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $repository   = $this->mock(ExportJobRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('create')->andReturn(new ExportJob);
        $repository->shouldReceive('cleanup');
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('export.index'));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ExportController::postIndex
     */
    public function testPostIndex()
    {
        // mock stuff
        $repository   = $this->mock(ExportJobRepositoryInterface::class);
        $processor    = $this->mock(ProcessorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $this->session(
            ['first' => new Carbon('2014-01-01')]
        );


        $data = [
            'export_start_range'  => '2015-01-01',
            'export_end_range'    => '2015-01-21',
            'exportFormat'        => 'csv',
            'accounts'            => [1],
            'include_attachments' => '1',
            'include_old_uploads' => '1',
            'job'                 => 'testExport',
        ];

        $accountRepos->shouldReceive('getAccountsById')->withArgs([$data['accounts']])->andReturn(new Collection);

        $processor->shouldReceive('setSettings')->once();
        $processor->shouldReceive('collectJournals')->once();
        $processor->shouldReceive('convertJournals')->once();
        $processor->shouldReceive('exportJournals')->once();
        $processor->shouldReceive('createZipFile')->once();
        $processor->shouldReceive('collectOldUploads')->once();
        $processor->shouldReceive('collectAttachments')->once();

        $job       = new ExportJob;
        $job->user = $this->user();

        $repository->shouldReceive('changeStatus')->andReturn(true);
        $repository->shouldReceive('findByKey')->andReturn($job);

        $this->be($this->user());

        $response = $this->post(route('export.submit'), $data);
        $response->assertStatus(200);
        $response->assertSee('ok');
    }

}
