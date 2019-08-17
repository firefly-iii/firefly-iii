<?php
/**
 * FakeRoutineTest.php
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
use FireflyIII\Import\Routine\FakeRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\Fake\StageAhoyHandler;
use FireflyIII\Support\Import\Routine\Fake\StageFinalHandler;
use FireflyIII\Support\Import\Routine\Fake\StageNewHandler;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class FakeRoutineTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FakeRoutineTest extends TestCase
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
     * @covers \FireflyIII\Import\Routine\FakeRoutine
     */
    public function testRunAhoy(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'a_route_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'ahoy';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock
        $handler    = $this->mock(StageAhoyHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // calls
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final'])->once();
        $handler->shouldReceive('run')->once();


        $routine = new FakeRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\FakeRoutine
     */
    public function testRunFinal(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'a_route_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'final';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock
        $handler    = $this->mock(StageFinalHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // calls
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final'])->once();
        $repository->shouldReceive('setTransactions')->withArgs([Mockery::any(), []])->once();
        $handler->shouldReceive('getTransactions')->once()->andReturn([]);
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $handler->shouldReceive('setImportJob')->once();

        $routine = new FakeRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\FakeRoutine
     */
    public function testRunNew(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'a_route_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock
        $handler    = $this->mock(StageNewHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // calls
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'ahoy'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $handler->shouldReceive('run')->once();


        $routine = new FakeRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

}
