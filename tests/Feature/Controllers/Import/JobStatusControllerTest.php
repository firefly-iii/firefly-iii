<?php
/**
 * JobStatusControllerTest.php
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

namespace Tests\Feature\Controllers\Import;

use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\FakeRoutine;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class JobStatusControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JobStatusControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testIndex(): void
    {
        $importRepos    = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $job            = new ImportJob;
        $job->user_id   = $this->user()->id;
        $job->key       = 'Afake_job_' . $this->randomInt();
        $job->status    = 'ready_to_run';
        $job->provider  = 'fake';
        $job->file_type = '';
        $job->save();

        $this->mockDefaultSession();

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        // call thing.
        $this->be($this->user());
        $response = $this->get(route('import.job.status.index', [$job->key]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testJson(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Bfake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'file';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        $importRepos->shouldReceive('countTransactions')->once()->andReturn(0);

        // call thing.
        $this->be($this->user());
        $response = $this->get(route('import.job.status.json', [$job->key]));
        $response->assertStatus(200);
        $response->assertSee(
            'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.'
        );
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testJsonWithTag(): void
    {
        $importRepos       = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos         = $this->mock(UserRepositoryInterface::class);
        $tag               = $this->getRandomTag();
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Cfake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->tag()->associate($tag);
        $job->save();

        $this->mockDefaultSession();
        $importRepos->shouldReceive('countTransactions')->once()->andReturn(0);
        $importRepos->shouldReceive('countByTag')->atLeast()->once()->andReturn(0);

        // call thing.
        $this->be($this->user());
        $response = $this->get(route('import.job.status.json', [$job->key]));
        $response->assertStatus(200);
        $response->assertSee(
            'No transactions have been imported. Perhaps they were all duplicates is simply no transactions where present to be imported. Perhaps the log files can tell you what happened. If you import data regularly, this is normal.'
        );
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testJsonWithTagManyJournals(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);

        /** @var Tag $tag */
        $tag     = $this->user()->tags()->first();
        $journal = $this->user()->transactionJournals()->first();
        $second  = $this->user()->transactionJournals()->where('id', '!=', $journal->id)->first();
        $tag->transactionJournals()->sync([$journal->id, $second->id]);

        $this->mockDefaultSession();

        $importRepos->shouldReceive('countTransactions')->once()->andReturn(2);
        $importRepos->shouldReceive('countByTag')->atLeast()->once()->andReturn(2);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Dfake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->tag()->associate($tag);
        $job->save();

        // call thing.
        $this->be($this->user());
        $response = $this->get(route('import.job.status.json', [$job->key]));
        $response->assertStatus(200);
        $response->assertSee(
            'Firefly III has imported 2 transactions. They are stored under tag <a href=\"http:\/\/localhost\/tags\/show\/' . $tag->id
        );
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testJsonWithTagOneJournal(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);

        /** @var Tag $tag */
        $tag     = $this->user()->tags()->first();
        $journal = $this->user()->transactionJournals()->first();
        $tag->transactionJournals()->sync([$journal->id]);

        $this->mockDefaultSession();

        $importRepos->shouldReceive('countTransactions')->once()->andReturn(1);
        $importRepos->shouldReceive('countByTag')->atLeast()->once()->andReturn(1);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Efake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->tag()->associate($tag);
        $job->save();

        // call thing.
        $this->be($this->user());
        $response = $this->get(route('import.job.status.json', [$job->key]));
        $response->assertStatus(200);
        $response->assertSee(
            'Exactly one transaction has been imported. It is stored under tag <a href=\"http:\/\/localhost\/tags\/show\/' . $tag->id
        );
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStart(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Ffake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $routine    = $this->mock(FakeRoutine::class);

        // mock calls:
        $routine->shouldReceive('setImportJob')->once();
        $routine->shouldReceive('run')->once();

        // call thing.
        $this->be($this->user());
        $response = $this->post(route('import.job.start', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'OK', 'message' => 'stage_finished']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStartException(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Gfake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $routine    = $this->mock(FakeRoutine::class);

        // mock calls:
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'error']);
        $routine->shouldReceive('setImportJob')->once();
        $routine->shouldReceive('run')->andThrow(new Exception('Unknown exception'));

        // call thing.
        Log::warning('The following error is part of a test.');
        $this->be($this->user());
        $response = $this->post(route('import.job.start', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'NOK', 'message' => 'The import routine crashed: Unknown exception']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStartFireflyException(): void
    {
        $userRepos         = $this->mock(UserRepositoryInterface::class);
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Hfake_job_' . $this->randomInt();
        $job->status       = 'ready_to_run';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $routine    = $this->mock(FakeRoutine::class);

        // mock calls:
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'error']);
        $routine->shouldReceive('setImportJob')->once();
        $routine->shouldReceive('run')->andThrow(new FireflyException('Unknown exception'));

        // call thing.
        Log::warning('The following error is part of a test.');
        $this->be($this->user());
        $response = $this->post(route('import.job.start', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'NOK', 'message' => 'The import routine crashed: Unknown exception']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStartInvalidState(): void
    {
        $importRepos = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        // mock calls:
        $importRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'error'])
                    ->atLeast()->once();

        $this->mockDefaultSession();

        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Ifake_job_' . $this->randomInt();
        $job->status       = 'bad_state';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        // call thing.
        $this->be($this->user());
        $response = $this->post(route('import.job.start', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'NOK', 'message' => 'JobStatusController::start expects status "ready_to_run" instead of "bad_state".']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStore(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Jfake_job_' . $this->randomInt();
        $job->status       = 'provider_finished';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $storage    = $this->mock(ImportArrayStorage::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'storage_finished']);
        $storage->shouldReceive('setImportJob')->once();
        $storage->shouldReceive('store')->once();


        $this->be($this->user());
        $response = $this->post(route('import.job.store', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'OK', 'message' => 'storage_finished']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStoreException(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Lfake_job_' . $this->randomInt();
        $job->status       = 'provider_finished';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        // mock stuff
        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $storage    = $this->mock(ImportArrayStorage::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'storing_data']);
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'error']);
        $storage->shouldReceive('setImportJob')->once();
        $storage->shouldReceive('store')->once()->andThrow(new FireflyException('Some storage exception.'));


        $this->be($this->user());
        $response = $this->post(route('import.job.store', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(['status' => 'NOK', 'message' => 'The import storage routine crashed: Some storage exception.']);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\JobStatusController
     */
    public function testStoreInvalidState(): void
    {
        $importRepos       = $this->mock(ImportJobRepositoryInterface::class);
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'Kfake_job_' . $this->randomInt();
        $job->status       = 'some_bad_state';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->mockDefaultSession();

        $this->be($this->user());
        $response = $this->post(route('import.job.store', [$job->key]));
        $response->assertStatus(200);
        $response->assertExactJson(
            ['status' => 'NOK', 'message' => 'JobStatusController::start expects status "provider_finished" instead of "' . $job->status . '".']
        );
    }
}
