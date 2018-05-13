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

use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class IndexControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testCreateFake(): void
    {
        // mock stuff:
        $repository        = $this->mock(ImportJobRepositoryInterface::class);
        $userRepository    = $this->mock(UserRepositoryInterface::class);
        $fakePrerequisites = $this->mock(FakePrerequisites::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'fake';
        $importJob->key      = 'fake_job_1';

        // mock call:
        $repository->shouldReceive('create')->withArgs(['fake'])->andReturn($importJob);
        $fakePrerequisites->shouldReceive('isComplete')->once()->andReturn(false);
        $fakePrerequisites->shouldReceive('setUser')->once();


        $this->be($this->user());
        $response = $this->get(route('import.create', ['fake']));
        $response->assertStatus(302);
        // expect a redirect to prerequisites
        $response->assertRedirect(route('import.prerequisites.index', ['fake', 'fake_job_1']));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testCreateFakeNoPrereq(): void
    {
        // mock stuff:
        $repository        = $this->mock(ImportJobRepositoryInterface::class);
        $fakePrerequisites = $this->mock(FakePrerequisites::class);
        $userRepository    = $this->mock(UserRepositoryInterface::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'fake';
        $importJob->key      = 'fake_job_2';

        // mock call:
        $repository->shouldReceive('create')->withArgs(['fake'])->andReturn($importJob);
        $fakePrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $fakePrerequisites->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'has_prereq'])->andReturn($importJob)->once();


        $this->be($this->user());
        $response = $this->get(route('import.create', ['fake']));
        $response->assertStatus(302);
        // expect a redirect to prerequisites
        $response->assertRedirect(route('import.job.configuration.index', ['fake_job_2']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testIndex(): void
    {
        $this->be($this->user());

        // fake stuff:
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // call methods:
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false);


        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testIndexDemo(): void
    {
        $this->be($this->user());

        // fake stuff:
        $fake           = $this->mock(FakePrerequisites::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // call methods:
        $fake->shouldReceive('setUser')->once();
        $fake->shouldReceive('isComplete')->once()->andReturn(true);
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(true);


        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }
}
