<?php
/**
 * PrerequisitesControllerTest.php
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

use FireflyIII\Import\Prerequisites\BunqPrerequisites;
use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Import\Prerequisites\SpectrePrerequisites;
use FireflyIII\Import\Prerequisites\YnabPrerequisites;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrerequisitesControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $prereq    = $this->mock(BunqPrerequisites::class);
        $this->mock(ImportJobRepositoryInterface::class);

        // mock some prerequisites:
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'A_pre_job_' . $this->randomInt();
        $job->status       = 'new';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $prereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $prereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();
        $spectrePrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();
        $ynabPrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();


        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);


        $prereq->shouldReceive('getView')->once()->andReturn('import.fake.prerequisites');
        $prereq->shouldReceive('getViewParameters')->once()->andReturn(['api_key' => '']);


        $this->be($this->user());
        $response = $this->get(route('import.prerequisites.index', ['bunq', $job->key]));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testIndexBadState(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(ImportJobRepositoryInterface::class);

        // mock some prerequisites:
        $bunqPrereq    = $this->mock(BunqPrerequisites::class);
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'B_pre_job_' . $this->randomInt();
        $job->status       = 'some_Bad_state';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        // fake calls to prereq classes
        $bunqPrereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $bunqPrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();
        $spectrePrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();
        $ynabPrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);


        $this->be($this->user());
        $response = $this->get(route('import.prerequisites.index', ['bunq', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testIndexComplete(): void
    {
        $this->mockDefaultSession();
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock some prerequisites:
        $bunqPrereq    = $this->mock(BunqPrerequisites::class);
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        // fake calls to prereq classes
        $bunqPrereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $bunqPrereq->shouldReceive('isComplete')->andReturn(true)->atLeast()->once();
        $spectrePrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();
        $ynabPrereq->shouldReceive('isComplete')->andReturn(false)->atLeast()->once();

        //Preferences::shouldReceive('setForUser')->withArgs([Mockery::any(),'x','x'])->atLeast()->once();


        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'C_pre_job_' . $this->randomInt();
        $job->status       = 'new';
        $job->provider     = 'bunq';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'has_prereq']);

        $this->be($this->user());
        $response = $this->get(route('import.prerequisites.index', ['bunq', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.job.configuration.index', [$job->key]));

    }

    /**
     * Redirects to configuration.
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPost(): void
    {
        $this->mockDefaultSession();
        $userRepos  = $this->mock(UserRepositoryInterface::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock some prerequisites:
        $bunqPrereq    = $this->mock(BunqPrerequisites::class);
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        // fake calls to prereq classes
        $bunqPrereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $bunqPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $spectrePrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $ynabPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);

        $bunqPrereq->shouldReceive('storePrerequisites')->atLeast()->once()->andReturn(new MessageBag);


        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'D_pre_job_' . $this->randomInt();
        $job->status       = 'new';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);
        //$prereq->shouldReceive('setUser')->times(2);
        //$prereq->shouldReceive('storePrerequisites')->once()->andReturn(new MessageBag);

        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'has_prereq']);

        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['bunq', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.job.configuration.index', [$job->key]));
    }

    /**
     * Bad state gives errors.
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPostBadState(): void
    {
        $this->mockDefaultSession();
        $userRepos         = $this->mock(UserRepositoryInterface::class);
        $prereq            = $this->mock(FakePrerequisites::class);
        $this->mock(ImportJobRepositoryInterface::class);

        // mock some prerequisites:
        $bunqPrereq    = $this->mock(BunqPrerequisites::class);
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        // fake calls to prereq classes
        $bunqPrereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $bunqPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $spectrePrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $ynabPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);


        //Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'bunq_api_key', null])->andReturnNull();
        //Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'spectre_app_id', null])->andReturnNull();
        //Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'ynab_client_id', null])->andReturnNull();

        $job          = new ImportJob;
        $job->user_id = $this->user()->id;
        $job->key          = 'D_pre_job_' . $this->randomInt();
        $job->status       = 'badstate';
        $job->provider     = 'bunq';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);
        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['bunq', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.index'));
        $response->assertSessionHas('error', 'To access this page, your import job cannot have status "badstate".');
    }

    /**
     * Redirects to index.
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPostNoJob(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $this->mock(ImportJobRepositoryInterface::class);

        // mock some prerequisites:
        $bunqPrereq    = $this->mock(BunqPrerequisites::class);
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        // fake calls to prereq classes
        $bunqPrereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $bunqPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $spectrePrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $ynabPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);

        $bunqPrereq->shouldReceive('storePrerequisites')->atLeast()->once()->andReturn(new MessageBag);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['bunq']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.index'));
    }

    /**
     * Error messages? Redirect back
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPostWithMessages(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $prereq    = $this->mock(FakePrerequisites::class);
        $this->mock(ImportJobRepositoryInterface::class);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'D_pre_job_' . $this->randomInt();
        $job->status       = 'new';
        $job->provider     = 'bunq';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $messages = new MessageBag;
        $messages->add('some', 'message');
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);


        // mock some prerequisites:
        $bunqPrereq    = $this->mock(BunqPrerequisites::class);
        $spectrePrereq = $this->mock(SpectrePrerequisites::class);
        $ynabPrereq    = $this->mock(YnabPrerequisites::class);

        // fake calls to prereq classes
        $bunqPrereq->shouldReceive('setUser')->atLeast()->once();
        $spectrePrereq->shouldReceive('setUser')->atLeast()->once();
        $ynabPrereq->shouldReceive('setUser')->atLeast()->once();

        $bunqPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $spectrePrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);
        $ynabPrereq->shouldReceive('isComplete')->atLeast()->once()->andReturn(false);

        $bunqPrereq->shouldReceive('storePrerequisites')->atLeast()->once()->andReturn($messages);




        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['bunq', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.prerequisites.index', ['bunq', $job->key]));
        $response->assertSessionHas('error', 'message');
    }
}
