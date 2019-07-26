<?php
/**
 * BunqRoutineTest.php
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
use FireflyIII\Import\Routine\BunqRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler;
use FireflyIII\Support\Import\Routine\Bunq\StageNewHandler;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class BunqRoutineTest
 */
class BunqRoutineTest extends TestCase
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
     * @covers \FireflyIII\Import\Routine\BunqRoutine
     */
    public function testRunImport(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'brY_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock stuff:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $handler    = $this->mock(StageImportDataHandler::class);


        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running']);
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished']);
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final']);
        $repository->shouldReceive('appendTransactions')->withArgs([Mockery::any(), ['a' => 'c']])->once();

        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();
        $handler->shouldReceive('getTransactions')->once()->andReturn(['a' => 'c']);
        $handler->shouldReceive('isStillRunning')->andReturn(false);
        $routine = new BunqRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }

    }

    /**
     * @covers \FireflyIII\Import\Routine\BunqRoutine
     */
    public function testRunImportStillRunning(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'brY_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock stuff:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $handler    = $this->mock(StageImportDataHandler::class);


        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();
        $handler->shouldReceive('getTransactions')->once()->andReturn(['a' => 'c']);
        $handler->shouldReceive('isStillRunning')->andReturn(true);

        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run']);
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'go-for-import']);

        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running']);
        $repository->shouldReceive('appendTransactions')->withArgs([Mockery::any(), ['a' => 'c']])->once();


        $routine = new BunqRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }

    }


    /**
     * @covers \FireflyIII\Import\Routine\BunqRoutine
     */
    public function testRunNew(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'brX_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock stuff:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $handler    = $this->mock(StageNewHandler::class);


        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running']);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'choose-accounts'])->once();

        $routine = new BunqRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }

    }

}
