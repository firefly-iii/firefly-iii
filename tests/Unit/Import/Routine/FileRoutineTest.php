<?php
/**
 * FileRoutineTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Unit\Import\Routine;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\Bunq\StageImportDataHandler;
use FireflyIII\Support\Import\Routine\File\CSVProcessor;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class FileRoutineTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileRoutineTest extends TestCase
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
     * @covers \FireflyIII\Import\Routine\FileRoutine
     */
    public function testRunDefault(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'brY_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock stuff:
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $handler    = $this->mock(StageImportDataHandler::class);
        $this->mock(AttachmentHelperInterface::class);
        $csv = $this->mock(CSVProcessor::class);

        $csv->shouldReceive('setImportJob')->atLeast()->once();
        $csv->shouldReceive('run')->atLeast()->once();


        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running']);
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished']);
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final']);
        $repository->shouldReceive('getConfiguration')->atLeast()->once()->andReturn([]);
        //$repository->shouldReceive('getAttachments')->atLeast()->once()->andReturn(new Collection);
        $repository->shouldReceive('setTransactions')->atLeast()->once();
        //$repository->shouldReceive('appendTransactions')->withArgs([Mockery::any(), ['a' => 'c']])->once();

        //$handler->shouldReceive('setImportJob')->once();
        //$handler->shouldReceive('run')->once();
        //$handler->shouldReceive('getTransactions')->once()->andReturn(['a' => 'c']);
        $handler->shouldReceive('isStillRunning')->andReturn(false);
        $routine = new FileRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }
}
