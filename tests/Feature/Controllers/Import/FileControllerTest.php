<?php
/**
 * FileControllerTest.php
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

namespace Tests\Feature\Controllers\Import;

use FireflyIII\Import\Configurator\CsvConfigurator;
use FireflyIII\Import\Routine\ImportRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Class FileControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\FileController::configure
     * @covers \FireflyIII\Http\Controllers\Import\FileController::makeConfigurator
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
        $response = $this->get(route('import.file.configure', ['configure']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\FileController::configure
     * @covers \FireflyIII\Http\Controllers\Import\FileController::makeConfigurator
     */
    public function testConfigured()
    {
        // mock stuff.
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('import.file.configure', ['configure']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.file.status', ['configure']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::download
     */
    public function testDownload()
    {
        $this->be($this->user());
        $response = $this->get(route('import.file.download', ['configure']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::initialize
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
        $response = $this->post(route('import.file.initialize'), ['import_file_type' => 'csv', 'import_file' => $file, 'configuration_file' => $configFile]);

        $response->assertStatus(302);
        $response->assertRedirect(route('import.file.configure', ['hello']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::json
     */
    public function testJson()
    {
        $this->be($this->user());
        $response = $this->get(route('import.file.json', ['configure']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::json
     */
    public function testJsonFinished()
    {
        $this->be($this->user());
        $response = $this->get(route('import.file.json', ['finished']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::json
     */
    public function testJsonRunning()
    {
        $this->be($this->user());
        $response = $this->get(route('import.file.json', ['running']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::postConfigure
     */
    public function testPostConfigure()
    {
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(false);
        $configurator->shouldReceive('configureJob')->once()->andReturn(false);
        $configurator->shouldReceive('getWarningMessage')->once()->andReturn('');

        $this->be($this->user());
        $response = $this->post(route('import.file.process-configuration', ['running']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.file.configure', ['running']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::postConfigure
     */
    public function testPostConfigured()
    {
        $configurator = $this->mock(CsvConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('import.file.process-configuration', ['running']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.file.status', ['running']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::start
     */
    public function testStart()
    {
        $importer = $this->mock(ImportRoutine::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('run')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('import.file.start', ['running']));
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Import\FileController::start
     * @expectedExceptionMessage Job did not complete succesfully.
     */
    public function testStartFailed()
    {
        $importer = $this->mock(ImportRoutine::class);
        $importer->shouldReceive('setJob')->once();
        $importer->shouldReceive('run')->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('import.file.start', ['running']));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::status
     */
    public function testStatus()
    {
        $this->be($this->user());
        $response = $this->get(route('import.file.status', ['running']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\FileController::status
     */
    public function testStatusNew()
    {
        $this->be($this->user());
        $response = $this->get(route('import.file.status', ['new']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.file.configure', ['new']));
    }
}
