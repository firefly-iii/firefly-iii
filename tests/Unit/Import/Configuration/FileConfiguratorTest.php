<?php
/**
 * FileConfiguratorTest.php
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

namespace Tests\Unit\Import\Configuration;

use FireflyIII\Import\Configuration\FileConfigurator;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Configuration\File\Initial;
use FireflyIII\Support\Import\Configuration\File\Map;
use FireflyIII\Support\Import\Configuration\File\Roles;
use FireflyIII\Support\Import\Configuration\File\UploadConfig;
use Mockery;
use Tests\TestCase;

/**
 * Class FileConfiguratorTest
 */
class FileConfiguratorTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::configureJob
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::setJob
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getConfig
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getConfigurationClass
     */
    public function testConfigureJobInitial()
    {
        // data
        $config   = ['stage' => 'initial'];
        $data     = ['some' => 'array'];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);

        // mock repository
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();


        // assert that new initial is created:
        $processor = $this->mock(Initial::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('storeConfiguration')->withArgs([$data])->once()->andReturn(true);
        $processor->shouldReceive('getWarningMessage')->andReturn('')->once();

        // config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->configureJob($data);
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::configureJob
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getConfigurationClass
     */
    public function testConfigureJobMap()
    {
        // data
        $config   = ['stage' => 'map'];
        $data     = ['some' => 'array'];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);

        // mock repository
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new Roles is created:
        $processor = $this->mock(Map::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('storeConfiguration')->withArgs([$data])->once()->andReturn(true);
        $processor->shouldReceive('getWarningMessage')->andReturn('')->once();

        // config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->configureJob($data);
    }

    /**
     * Should throw a FireflyException when $job is null.
     *
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::__construct
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::configureJob
     * @expectedException \FireflyIII\Exceptions\FireflyException
     */
    public function testConfigureJobNoJob()
    {
        // config
        $configurator = new FileConfigurator();
        $configurator->configureJob([]);
    }

    /**
     * @covers                   \FireflyIII\Import\Configuration\FileConfigurator::configureJob
     * @covers                   \FireflyIII\Import\Configuration\FileConfigurator::getConfigurationClass
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot handle job stage "ready" in getConfigurationClass().
     */
    public function testConfigureJobReady()
    {
        // data
        $config   = ['stage' => 'ready'];
        $data     = ['some' => 'array'];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->configureJob($data);
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::configureJob
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getConfigurationClass
     */
    public function testConfigureJobRoles()
    {
        $config   = ['stage' => 'roles'];
        $data     = ['some' => 'array'];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new Roles is created:
        $processor = $this->mock(Roles::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('storeConfiguration')->withArgs([$data])->once()->andReturn(true);
        $processor->shouldReceive('getWarningMessage')->andReturn('')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->configureJob($data);
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::configureJob
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getConfigurationClass
     */
    public function testConfigureJobUploadConfig()
    {
        // data
        $config   = ['stage' => 'upload-config'];
        $data     = ['some' => 'array'];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new UploadConfig is created:
        $processor = $this->mock(UploadConfig::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('storeConfiguration')->withArgs([$data])->once()->andReturn(true);
        $processor->shouldReceive('getWarningMessage')->andReturn('')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->configureJob($data);
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     */
    public function testGetNextDataInitial()
    {
        // data
        $config   = ['stage' => 'initial'];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new Initial is created:
        $processor = $this->mock(Initial::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('getData')->once();


        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextData();
    }

    /**
     * Should throw a FireflyException when $job is null.
     *
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     * @expectedException \FireflyIII\Exceptions\FireflyException
     */
    public function testGetNextDataNoJob()
    {
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $configurator = new FileConfigurator();
        $configurator->getNextData();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     */
    public function testGetNextDataUploadConfig()
    {
        // data
        $config = ['stage' => 'upload-config'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new Initial is created:
        $processor = $this->mock(UploadConfig::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('getData')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextData();
    }

    /**
     * @covers                   \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot handle job stage "ksksjje" in getConfigurationClass().
     */
    public function testGetNextDataUploadInvalid()
    {
        // data
        $config = ['stage' => 'ksksjje'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // should throw error
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextData();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     */
    public function testGetNextDataUploadMap()
    {
        // data:
        $config = ['stage' => 'map'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new Initial is created:
        $processor = $this->mock(Map::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('getData')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextData();
    }

    /**
     * @covers                   \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot handle job stage "ready" in getConfigurationClass().
     */
    public function testGetNextDataUploadReady()
    {
        // data
        $config = ['stage' => 'ready'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextData();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextData
     */
    public function testGetNextDataUploadRoles()
    {
        // data
        $config = ['stage' => 'roles'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // assert that new Initial is created:
        $processor = $this->mock(Roles::class);
        $processor->shouldReceive('setJob')->withArgs([$job])->once();
        $processor->shouldReceive('getData')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextData();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     */
    public function testGetNextViewInitial()
    {
        // data
        $config = ['stage' => 'initial'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $view = $configurator->getNextView();

        // test
        $this->assertEquals('import.file.initial', $view);
    }

    /**
     * @covers                   \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage No view for stage "slkds903ms90k"
     */
    public function testGetNextViewInvalid()
    {
        // data
        $config = ['stage' => 'slkds903ms90k'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextView();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     */
    public function testGetNextViewMap()
    {
        // data
        $config = ['stage' => 'map'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $view = $configurator->getNextView();

        // test
        $this->assertEquals('import.file.map', $view);
    }

    /**
     * Should throw a FireflyException when $job is null.
     *
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     * @expectedException \FireflyIII\Exceptions\FireflyException
     */
    public function testGetNextViewNoJob()
    {
        $configurator = new FileConfigurator();
        $configurator->getNextView();
    }

    /**
     * @covers                   \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage No view for stage "ready"
     */
    public function testGetNextViewReady()
    {
        // data
        $config = ['stage' => 'ready'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run configx§
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $configurator->getNextView();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     */
    public function testGetNextViewRoles()
    {
        // data
        $config = ['stage' => 'roles'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $view = $configurator->getNextView();

        // test
        $this->assertEquals('import.file.roles', $view);
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getNextView
     */
    public function testGetNextViewUploadConfig()
    {
        // data
        $config = ['stage' => 'upload-config'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $view = $configurator->getNextView();

        // test
        $this->assertEquals('import.file.upload-config', $view);
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getWarningMessage
     */
    public function testGetWarningMessage()
    {
        // data
        $config = ['stage' => 'upload-config'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->once();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $warning = $configurator->getWarningMessage();

        // test
        $this->assertEquals('', $warning);

    }

    /**
     * Should throw a FireflyException when $job is null.
     *
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::getWarningMessage
     * @expectedException \FireflyIII\Exceptions\FireflyException
     */
    public function testGetWarningMessageNoJob()
    {
        $configurator = new FileConfigurator();
        $configurator->getWarningMessage();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::isJobConfigured
     */
    public function testIsJobConfiguredFalse()
    {
        // data
        $config = ['stage' => 'upload-config'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $result = $configurator->isJobConfigured();

        // test
        $this->assertFalse($result);
    }

    /**
     * Should throw a FireflyException when $job is null.
     *
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::isJobConfigured
     * @expectedException \FireflyIII\Exceptions\FireflyException
     */
    public function testIsJobConfiguredNoJob()
    {
        $configurator = new FileConfigurator();
        $configurator->isJobConfigured();
    }

    /**
     * @covers \FireflyIII\Import\Configuration\FileConfigurator::isJobConfigured
     */
    public function testIsJobConfiguredTrue()
    {
        // data
        $config = ['stage' => 'ready'];
        $extended = ['steps' => 0, 'done' => 0];
        $job    = $this->getJob($config);

        // mock repos
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config)->twice();
        $repository->shouldReceive('setConfiguration')->once();
        $repository->shouldReceive('getExtendedStatus')->andReturn($extended)->once();
        $repository->shouldReceive('setExtendedStatus')->once();

        // run config
        $configurator = new FileConfigurator();
        $configurator->setJob($job);
        $result = $configurator->isJobConfigured();

        // test
        $this->assertTrue($result);
    }

    /**
     * @param array $config
     *
     * @return ImportJob
     */
    protected function getJob(array $config): ImportJob
    {
        $job            = new ImportJob;
        $job->file_type = 'file';
        $job->status    = 'new';
        $job->key       = 'x' . rand(1, 100000);
        $job->user()->associate($this->user());
        $job->configuration = $config;

        return $job;
    }
}