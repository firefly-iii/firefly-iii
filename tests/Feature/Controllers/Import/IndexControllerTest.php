<?php
/**
 * IndexControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Feature\Controllers\Import;

use FireflyIII\Import\Prerequisites\BunqPrerequisites;
use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Import\Prerequisites\SpectrePrerequisites;
use FireflyIII\Import\Prerequisites\YnabPrerequisites;
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
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testCreateBadJob(): void
    {
        $this->mockDefaultSession();

        // mock stuff:
        $this->mock(ImportJobRepositoryInterface::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $bunqPrerequisites    = $this->mock(BunqPrerequisites::class);
        $spectrePrerequisites = $this->mock(SpectrePrerequisites::class);
        $ynabPrerequisites    = $this->mock(YnabPrerequisites::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'fake';
        $importJob->key      = 'fake_job_1';


        // mock calls:
        $ynabPrerequisites->shouldReceive('setUser')->once();
        //$fakePrerequisites->shouldReceive('setUser')->once();
        $bunqPrerequisites->shouldReceive('setUser')->once();
        $spectrePrerequisites->shouldReceive('setUser')->once();
        //$fakePrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $bunqPrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $spectrePrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $ynabPrerequisites->shouldReceive('isComplete')->once()->andReturn(true);

        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->once();

        $this->be($this->user());
        $response = $this->get(route('import.create', ['bad']));
        $response->assertStatus(404);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testCreateDemoUser(): void
    {
        Log::debug(sprintf('Now in test %s', __METHOD__));
        // mock stuff:
        $this->mock(ImportJobRepositoryInterface::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $fakePrerequisites    = $this->mock(FakePrerequisites::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'spectre';
        $importJob->key      = 'fake_job_1';

        $this->mockDefaultSession();

        // mock calls:
        $fakePrerequisites->shouldReceive('setUser')->atLeast()->once();
        $fakePrerequisites->shouldReceive('isComplete')->atLeast()->once()->andReturn(true);

        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(true)->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('import.create', ['spectre']));
        $response->assertStatus(404);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testCreateFake(): void
    {
        // mock stuff:
        $repository           = $this->mock(ImportJobRepositoryInterface::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $fakePrerequisites    = $this->mock(FakePrerequisites::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'fake';
        $importJob->key      = 'fake_job_1';
        $importJob->user_id  = 1;

        $this->mockDefaultSession();

        // mock calls
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(true)->times(3);
        $repository->shouldReceive('create')->withArgs(['fake'])->andReturn($importJob);

        $fakePrerequisites->shouldReceive('isComplete')->times(3)->andReturn(false);
        $fakePrerequisites->shouldReceive('setUser')->times(3);


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
        $repository           = $this->mock(ImportJobRepositoryInterface::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $fakePrerequisites    = $this->mock(FakePrerequisites::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'fake';
        $importJob->key      = 'fake_job_2';
        $importJob->user_id  = 1;

        $this->mockDefaultSession();

        // mock call:

        $fakePrerequisites->shouldReceive('isComplete')->times(3)->andReturn(true);
        $fakePrerequisites->shouldReceive('setUser')->times(3);



        $repository->shouldReceive('create')->withArgs(['fake'])->andReturn($importJob);
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'has_prereq'])->andReturn($importJob)->once();

        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(true)->times(3);

        $this->be($this->user());
        $response = $this->get(route('import.create', ['fake']));
        $response->assertStatus(302);
        // expect a redirect to prerequisites
        $response->assertRedirect(route('import.job.configuration.index', ['fake_job_2']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testCreateFileHasNoPrereq(): void
    {
        // mock stuff:
        $repository           = $this->mock(ImportJobRepositoryInterface::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $bunqPrerequisites    = $this->mock(BunqPrerequisites::class);
        $spectrePrerequisites = $this->mock(SpectrePrerequisites::class);
        $ynabPrerequisites    = $this->mock(YnabPrerequisites::class);

        // fake job:
        $importJob           = new ImportJob;
        $importJob->provider = 'file';
        $importJob->key      = 'file_job_1';
        $importJob->user_id  = 1;

        $this->mockDefaultSession();

        // mock calls
        //$fakePrerequisites->shouldReceive('setUser')->times(2);
        $bunqPrerequisites->shouldReceive('setUser')->times(2);
        $spectrePrerequisites->shouldReceive('setUser')->times(2);
        $ynabPrerequisites->shouldReceive('setUser')->times(2);

        //$fakePrerequisites->shouldReceive('isComplete')->times(2)->andReturn(true);
        $bunqPrerequisites->shouldReceive('isComplete')->times(2)->andReturn(true);
        $spectrePrerequisites->shouldReceive('isComplete')->times(2)->andReturn(true);
        $ynabPrerequisites->shouldReceive('isComplete')->times(2)->andReturn(true);

        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->times(3);
        $repository->shouldReceive('create')->withArgs(['file'])->andReturn($importJob);
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'has_prereq'])->andReturn($importJob)->once();


        $this->be($this->user());
        $response = $this->get(route('import.create', ['file']));
        $response->assertStatus(302);
        // expect a redirect to prerequisites
        $response->assertRedirect(route('import.job.configuration.index', ['file_job_1']));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testDownload(): void
    {
        // mock stuff:
        $repository           = $this->mock(ImportJobRepositoryInterface::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $bunqPrerequisites    = $this->mock(BunqPrerequisites::class);
        $spectrePrerequisites = $this->mock(SpectrePrerequisites::class);
        $ynabPrerequisites    = $this->mock(YnabPrerequisites::class);

        $this->mockDefaultSession();

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'dc_' . $this->randomInt();
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'file';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $fakeConfig = [
            'hi'                    => 'there',
            1                       => true,
            'column-mapping-config' => ['a', 'b', 'c'],
        ];

        $repository->shouldReceive('getConfiguration')->andReturn($fakeConfig)->once();
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->once()->andReturn(false);

        //$fakePrerequisites->shouldReceive('setUser')->times(1);
        $bunqPrerequisites->shouldReceive('setUser')->times(1);
        $spectrePrerequisites->shouldReceive('setUser')->times(1);
        $ynabPrerequisites->shouldReceive('setUser')->times(1);
        //$filePrerequisites->shouldReceive('setUser')->times(1);

        //$fakePrerequisites->shouldReceive('isComplete')->times(1)->andReturn(true);
        $bunqPrerequisites->shouldReceive('isComplete')->times(1)->andReturn(true);
        $spectrePrerequisites->shouldReceive('isComplete')->times(1)->andReturn(true);
        $ynabPrerequisites->shouldReceive('isComplete')->times(1)->andReturn(true);
        //$filePrerequisites->shouldReceive('isComplete')->times(1)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('import.job.download', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['column-mapping-config' => ['a', 'b', 'c'], 'delimiter' => ',', 'hi' => 'there', 1 => true]);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\IndexController
     */
    public function testIndex(): void
    {
        $this->be($this->user());

        // fake stuff:
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $bunqPrerequisites    = $this->mock(BunqPrerequisites::class);
        $spectrePrerequisites = $this->mock(SpectrePrerequisites::class);
        $ynabPrerequisites    = $this->mock(YnabPrerequisites::class);
        $this->mock(ImportJobRepositoryInterface::class);

        $this->mockDefaultSession();

        // call methods:
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false);
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(false);

        //$fakePrerequisites->shouldReceive('setUser')->once();
        $bunqPrerequisites->shouldReceive('setUser')->once();
        $spectrePrerequisites->shouldReceive('setUser')->once();
        $ynabPrerequisites->shouldReceive('setUser')->once();

        //$fakePrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $bunqPrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $spectrePrerequisites->shouldReceive('isComplete')->once()->andReturn(true);
        $ynabPrerequisites->shouldReceive('isComplete')->once()->andReturn(true);

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
        $fakePrerequisites    = $this->mock(FakePrerequisites::class);
        $userRepository       = $this->mock(UserRepositoryInterface::class);
        $this->mock(ImportJobRepositoryInterface::class);

        $this->mockDefaultSession();

        // call methods:
        $fakePrerequisites->shouldReceive('setUser')->once();
        $fakePrerequisites->shouldReceive('isComplete')->once()->andReturn(true);

        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(true);
        $userRepository->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(false);

        $response = $this->get(route('import.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }
}
