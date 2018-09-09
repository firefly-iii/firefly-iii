<?php
/**
 * FileRoutineTest.php
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

namespace Tests\Unit\Import\Routine;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\CSVProcessor;
use Mockery;
use Tests\TestCase;
use Log;

/**
 * Class FileRoutineTest
 */
class FileRoutineTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Import\Routine\FileRoutine
     */
    public function testRunDefault(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'a_fr_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'ready_to_run';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock
        $processor  = $this->mock(CSVProcessor::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // calls
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final'])->once();
        $repository->shouldReceive('setTransactions')->withArgs([Mockery::any(), ['a' => 'b']])->once();
        $repository->shouldReceive('getConfiguration')->withArgs([Mockery::any()])->once()->andReturn([]);
        $processor->shouldReceive('setImportJob')->once();
        $processor->shouldReceive('run')->once()->andReturn(['a' => 'b']);


        $routine = new FileRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}
