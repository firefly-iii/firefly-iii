<?php
/**
 * FileJobConfigurationTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Import\JobConfiguration;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\JobConfiguration\FileJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureMappingHandler;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureRolesHandler;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler;
use FireflyIII\Support\Import\JobConfiguration\File\NewFileJobHandler;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class FileJobConfigurationTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileJobConfigurationTest extends TestCase
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
     * No config, job is new.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testCCFalse(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'File_A_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // should be false:
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertFalse($configurator->configurationComplete());
    }

    /**
     * Job is ready to run.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testCCTrue(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'File_B_unit_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'ready_to_run';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // should be false:
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        $this->assertTrue($configurator->configurationComplete());
    }

    /**
     * Configure the job when the stage is "map". Won't test other combo's because they're covered by other tests.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testConfigureJob(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'I-Cfile_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'map';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $bag    = new MessageBag;
        $result = null;

        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureMappingHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('configureJob')->withArgs([['c' => 'd']])->andReturn($bag)->once();

        try {
            $result = $configurator->configureJob(['c' => 'd']);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($bag, $result);
    }

    /**
     * Get next data when stage is "configure-upload". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataCU(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'G-Dfile_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'configure-upload';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureUploadHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get next data when stage is "map". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataMap(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'H-Efile_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'map';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureMappingHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get next data when stage is "new". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataNew(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'F-fFile_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(NewFileJobHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get next data when stage is "roles". Expect a certain class to be called.
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextDataRoles(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'H-fiGle_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'roles';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);

        $handler = $this->mock(ConfigureRolesHandler::class);
        $handler->shouldReceive('setImportJob')->once()->withArgs([Mockery::any()]);
        $handler->shouldReceive('getNextData')->andReturn(['a' => 'b'])->withNoArgs()->once();

        try {
            $result = $configurator->getNextData();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals(['a' => 'b'], $result);
    }

    /**
     * Get view when stage is "configure-upload".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewCU(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'DfiHle_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'configure-upload';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.configure-upload', $result);
    }

    /**
     * Get view when stage is "map".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewMap(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'FfilIe_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'map';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.map', $result);
    }

    /**
     * Get view when stage is "new".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewNew(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'CfJile_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.new', $result);
    }

    /**
     * Get view when stage is "roles".
     *
     * @covers \FireflyIII\Import\JobConfiguration\FileJobConfiguration
     */
    public function testGetNextViewRoles(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once()->atLeast();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'EfiKle_' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'roles';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $result       = 'x';
        $configurator = new FileJobConfiguration;
        $configurator->setImportJob($job);
        try {
            $result = $configurator->getNextView();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.file.roles', $result);
    }
}
