<?php
/**
 * IndexControllerTest.php
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
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Import;

use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController::create
     */
    public function testCreate()
    {
        $job        = $this->user()->importJobs()->where('key', 'new')->first();
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $repository->shouldReceive('create')->withArgs(['file'])->andReturn($job);
        $this->be($this->user());
        $response = $this->get(route('import.create-job', ['file']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', ['new']));

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController::download
     */
    public function testDownload()
    {
        //$job = $this->user()->importJobs()->where('key', 'testImport')->first();
        $this->be($this->user());
        $response = $this->get(route('import.download', ['testImport']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\IndexController::index
     */
    public function testIndex()
    {

        $this->be($this->user());
        $response = $this->get(route('import.index'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController::start
     */
    public function testStart()
    {
        $routine = $this->mock(FileRoutine::class);
        $routine->shouldReceive('setJob')->once();
        $routine->shouldReceive('run')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('import.start', ['configured']));
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Import\IndexController::start
     * @expectedExceptionMessage Job did not complete successfully.
     */
    public function testStartFailed()
    {
        $routine = $this->mock(FileRoutine::class);
        $routine->shouldReceive('setJob')->once();
        $routine->shouldReceive('run')->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('import.start', ['configured']));
        $response->assertStatus(500);
    }
}
