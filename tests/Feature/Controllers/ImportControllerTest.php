<?php
/**
 * ImportControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use FireflyIII\Import\Configurator\CsvConfigurator;
use FireflyIII\Import\Routine\ImportRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Class ImportControllerTest
 *
 * @package Tests\Feature\Controllers
 */
class ImportControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::__construct
     * @covers \FireflyIII\Http\Controllers\ImportController::configure
     * @covers \FireflyIII\Http\Controllers\ImportController::makeConfigurator
     */
    public function testConfigure()
    {
        // mock stuff.
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(false);
        $configurator->shouldReceive('getNextView')->once()->andReturn('import.csv.initial');
        $configurator->shouldReceive('getNextData')->andReturn(['specifics' => [], 'delimiters' => [], 'accounts' => []])->once();


        $this->be($this->user());
        $response = $this->get(route('import.configure', ['configure']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::__construct
     * @covers \FireflyIII\Http\Controllers\ImportController::configure
     * @covers \FireflyIII\Http\Controllers\ImportController::makeConfigurator
     */
    public function testConfigured()
    {
        // mock stuff.
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('import.configure', ['configure']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.status', ['configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::download
     */
    public function testDownload()
    {
        $this->be($this->user());
        $response = $this->get(route('import.download', ['configure']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::index
     */
    public function testIndex()
    {
        $this->be($this->user());
        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::initialize
     */
    public function testInitialize()
    {
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $path       = resource_path('stubs/csv.csv');
        $file       = new UploadedFile($path, 'upload.csv', filesize($path), 'text/csv', null, true);
        $configPath = resource_path('stubs/demo-configuration.json');
        $configFile = new UploadedFile($path, 'configuration.json', filesize($configPath), 'application/json', null, true);
        $job        = new ImportJob;
        $job->key   = 'hello';

        $repository->shouldReceive('create')->once()->andReturn($job);
        $repository->shouldReceive('processFile')->once();
        $repository->shouldReceive('processConfiguration')->once();
        $repository->shouldReceive('updateStatus')->once();

        $this->be($this->user());
        $response = $this->post(route('import.initialize'), ['import_file_type' => 'csv', 'import_file' => $file, 'configuration_file' => $configFile]);

        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['hello']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::json
     */
    public function testJson()
    {
        $this->be($this->user());
        $response = $this->get(route('import.json', ['configure']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::json
     */
    public function testJsonFinished()
    {
        $this->be($this->user());
        $response = $this->get(route('import.json', ['finished']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::json
     */
    public function testJsonRunning()
    {
        $this->be($this->user());
        $response = $this->get(route('import.json', ['running']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postConfigure
     */
    public function testPostConfigure()
    {
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(false);
        $configurator->shouldReceive('configureJob')->once()->andReturn(false);


        $this->be($this->user());
        $response = $this->post(route('import.process-configuration', ['running']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['running']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::postConfigure
     */
    public function testPostConfigured()
    {
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(true);


        $this->be($this->user());
        $response = $this->post(route('import.process-configuration', ['running']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.status', ['running']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::start
     */
    public function testStart()
    {
        $importer = $this->mock(ImportRoutine::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('run')->once()->andReturn(true);


        $this->be($this->user());
        $response = $this->post(route('import.start', ['running']));
        $response->assertStatus(200);
    }
    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::start
     * @expectedExceptionMessage Job did not complete succesfully.
     */
    public function testStartFailed()
    {
        $importer = $this->mock(ImportRoutine::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('run')->once()->andReturn(false);


        $this->be($this->user());
        $response = $this->post(route('import.start', ['running']));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::status
     */
    public function testStatus()
    {
        $this->be($this->user());
        $response = $this->get(route('import.status', ['running']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ImportController::status
     */
    public function testStatusNew()
    {
        $this->be($this->user());
        $response = $this->get(route('import.status', ['new']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['new']));
    }


}
