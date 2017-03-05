<?php
/**
 * ExportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Models\ExportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ExportController::download
     */
    public function testDownload()
    {
        $repository = $this->mock(ExportJobRepositoryInterface::class);
        $repository->shouldReceive('exists')->once()->andReturn(true);
        $repository->shouldReceive('getContent')->once()->andReturn('Some content beep boop');

        $this->be($this->user());
        $response = $this->get(route('export.download', ['testExport']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ExportController::getStatus
     */
    public function testGetStatus()
    {
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
        $this->session(
            ['first' => new Carbon('2014-01-01')]
        );


        $data = [

            'export_start_range' => '2015-01-01',
            'export_end_range'   => '2015-01-21',
            'exportFormat'       => 'csv',
            'accounts'           => [1],
            'job'                => 'testExport',
        ];

        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('getAccountsById')->withArgs([$data['accounts']])->andReturn(new Collection);

        $processor = $this->mock(ProcessorInterface::class);
        $processor->shouldReceive('setSettings')->once();
        $processor->shouldReceive('collectJournals')->once();
        $processor->shouldReceive('convertJournals')->once();
        $processor->shouldReceive('exportJournals')->once();
        $processor->shouldReceive('createZipFile')->once();

        $repository = $this->mock(ExportJobRepositoryInterface::class);
        $repository->shouldReceive('changeStatus')->andReturn(true);
        $repository->shouldReceive('findByKey')->andReturn(new ExportJob);

        $this->be($this->user());

        $response = $this->post(route('export.export'), $data);
        $response->assertStatus(200);
        $response->assertSee('ok');
    }

}
