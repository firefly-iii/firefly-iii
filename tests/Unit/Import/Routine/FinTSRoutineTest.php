<?php
declare(strict_types=1);
/**
 * FinTSRoutineTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Import\Routine;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\FinTSRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\FinTS\StageImportDataHandler;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class FinTSRoutineTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FinTSRoutineTest extends TestCase
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
     * @covers \FireflyIII\Import\Routine\FinTSRoutine
     */
    public function testRunDefault(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'a_fin_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'fints';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock
        $handler    = $this->mock(StageImportDataHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // calls
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final'])->once();
        $repository->shouldReceive('setTransactions')->withArgs([Mockery::any(), ['a' => 'b']])->once();

        $handler->shouldReceive('setImportJob')->atLeast()->once();
        $handler->shouldReceive('run')->once()->atLeast()->once();
        $handler->shouldReceive('getTransactions')->atLeast()->once()->andReturn(['a' => 'b']);


        $routine = new FinTSRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}
