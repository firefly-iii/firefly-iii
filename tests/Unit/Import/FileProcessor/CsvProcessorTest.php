<?php
/**
 * CsvProcessorTest.php
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

namespace Tests\Unit\Import\FileProcessor;

use FireflyIII\Import\FileProcessor\CsvProcessor;
use FireflyIII\Import\Specifics\AbnAmroDescription;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Mockery;
use Tests\TestCase;

/**
 * Class CsvProcessorTest
 */
class CsvProcessorTest extends TestCase
{
    /**
     * @covers                   \FireflyIII\Import\FileProcessor\CsvProcessor::__construct
     * @covers                   \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot call getObjects() without a job.
     */
    public function testGetObjectsNoJob()
    {
        $processor = new CsvProcessor();
        $processor->getObjects();
    }

    /**
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getImportArray
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::setJob
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::importRow
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::specifics
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getRowHash
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::annotateValue
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage "bad-role" is not a valid role.
     */
    public function testRunBadRole()
    {
        // data
        $config   = [
            'column-roles' => [
                0 => 'bad-role',
            ],
        ];
        $extended = ['steps' => 0, 'done' => 0];
        $job      = $this->getJob($config);
        $csvFile  = '20170101,-12.34,"Some description"';

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config);
        $repository->shouldReceive('uploadFileContents')->withArgs([Mockery::any()])->andReturn($csvFile)->once();
        //$repository->shouldReceive('getExtendedStatus')->once()->andReturn([]);
        //$repository->shouldReceive('setExtendedStatus')->once()->andReturn($job);
        $repository->shouldReceive('addStepsDone')->twice();

        // mock stuff for this single row:
        $repository->shouldReceive('countByHash')->once()->withArgs([Mockery::any()])->andReturn(0);
        $processor = new CsvProcessor();
        $processor->setJob($job);
        $processor->run();
    }

    /**
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getImportArray
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::setJob
     */
    public function testRunBasic()
    {
        // data
        $config   = [];
        $job      = $this->getJob($config);
        $csvFile  = '';
        $extended = ['steps' => 0, 'done' => 0];

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config);
        $repository->shouldReceive('uploadFileContents')->withArgs([Mockery::any()])->andReturn($csvFile)->once();
        //        $repository->shouldReceive('getExtendedStatus')->once()->andReturn([]);
        //        $repository->shouldReceive('setExtendedStatus')->once()->andReturn($job);
        $repository->shouldReceive('addStepsDone')->times(3);

        $processor = new CsvProcessor();
        $processor->setJob($job);
        $processor->run();

        $objects = $processor->getObjects();
        $this->assertCount(0, $objects);
    }

    /**
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getImportArray
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::setJob
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::importRow
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::specifics
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getRowHash
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::annotateValue
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::rowAlreadyImported
     */
    public function testRunExisting()
    {
        // data
        $config   = [];
        $job      = $this->getJob($config);
        $csvFile  = '20170101,-12.34,"Some description"';
        $extended = ['steps' => 0, 'done' => 0];

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config);
        $repository->shouldReceive('uploadFileContents')->withArgs([Mockery::any()])->andReturn($csvFile)->once();
        //        $repository->shouldReceive('getExtendedStatus')->once()->andReturn([]); // twice for update errors.
        //        $repository->shouldReceive('setExtendedStatus')->once()->andReturn($job);
        //$repository->shouldReceive('addStepsDone')->times(3);

        // mock stuff for this single row:
        $repository->shouldReceive('countByHash')->once()->withArgs([Mockery::any()])->andReturn(1);
        $repository->shouldReceive('addStepsDone')->times(3)->withArgs([Mockery::any(), 1]);
        $repository->shouldReceive('addError')->once()->withArgs([Mockery::any(), 0, 'Row #0 has already been imported.']);
        $processor = new CsvProcessor();
        $processor->setJob($job);
        $processor->run();

        $objects = $processor->getObjects();
        $this->assertCount(0, $objects);
    }

    /**
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getImportArray
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::setJob
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::importRow
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::specifics
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getRowHash
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::annotateValue
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::rowAlreadyImported
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage "GoodBankDescription" is not a valid class name
     */
    public function testRunInvalidSpecific()
    {
        // data
        $config   = [
            'specifics' => ['GoodBankDescription' => 1],
        ];
        $job      = $this->getJob($config);
        $csvFile  = '20170101,-12.34,descr';
        $extended = ['steps' => 0, 'done' => 0];

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config);
        $repository->shouldReceive('uploadFileContents')->withArgs([Mockery::any()])->andReturn($csvFile)->once();
//        $repository->shouldReceive('getExtendedStatus')->once()->andReturn([]);
//        $repository->shouldReceive('setExtendedStatus')->once()->andReturn($job);
        // mock stuff for this single row:
        $repository->shouldReceive('countByHash')->once()->withArgs([Mockery::any()])->andReturn(0);
        $repository->shouldReceive('addStepsDone')->times(2)->withArgs([Mockery::any(), 1]);

        // mock specific:
        $processor = new CsvProcessor();
        $processor->setJob($job);
        $processor->run();
    }

    /**
     * @covers                   \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @expectedException \FireflyIII\Exceptions\FireflyException
     * @expectedExceptionMessage Cannot call run() without a job.
     */
    public function testRunNoJob()
    {

        $processor = new CsvProcessor();
        $processor->run();
    }

    /**
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getImportArray
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::setJob
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::importRow
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::specifics
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getRowHash
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::annotateValue
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::rowAlreadyImported
     */
    public function testRunSingle()
    {
        // data
        $config   = [];
        $job      = $this->getJob($config);
        $csvFile  = '20170101,-12.34,"Some description"';
        $extended = ['steps' => 0, 'done' => 0];

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config);
        $repository->shouldReceive('uploadFileContents')->withArgs([Mockery::any()])->andReturn($csvFile)->once();
//        $repository->shouldReceive('getExtendedStatus')->once()->andReturn([]);
//        $repository->shouldReceive('setExtendedStatus')->once()->andReturn($job);
        // mock stuff for this single row:
        $repository->shouldReceive('countByHash')->once()->withArgs([Mockery::any()])->andReturn(0);
        $repository->shouldReceive('addStepsDone')->times(3)->withArgs([Mockery::any(), 1]);
        $processor = new CsvProcessor();
        $processor->setJob($job);
        $processor->run();

        $objects = $processor->getObjects();
        $this->assertCount(1, $objects);
    }

    /**
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::run
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getImportArray
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getObjects
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::setJob
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::importRow
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::specifics
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::getRowHash
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::annotateValue
     * @covers \FireflyIII\Import\FileProcessor\CsvProcessor::rowAlreadyImported
     */
    public function testRunSpecific()
    {
        // data
        $config   = [
            'specifics' => ['AbnAmroDescription' => 1],
        ];
        $job      = $this->getJob($config);
        $csvFile  = '20170101,-12.34,descr';
        $row      = explode(',', $csvFile);
        $extended = ['steps' => 0, 'done' => 0];

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('setUser')->withArgs([Mockery::any()])->once();
        $repository->shouldReceive('getConfiguration')->andReturn($config);
        $repository->shouldReceive('uploadFileContents')->withArgs([Mockery::any()])->andReturn($csvFile)->once();
//        $repository->shouldReceive('getExtendedStatus')->once()->andReturn([]);
//        $repository->shouldReceive('setExtendedStatus')->once()->andReturn($job);
        // mock stuff for this single row:
        $repository->shouldReceive('countByHash')->once()->withArgs([Mockery::any()])->andReturn(0);
        $repository->shouldReceive('addStepsDone')->times(3)->withArgs([Mockery::any(), 1]);

        // mock specific:
        $specific = $this->mock(AbnAmroDescription::class);
        $specific->shouldReceive('run')->once()->andReturn($row);

        $processor = new CsvProcessor();
        $processor->setJob($job);
        $processor->run();

        $objects = $processor->getObjects();
        $this->assertCount(1, $objects);
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