<?php
/**
 * CreateCSVImportTest.php
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

namespace Tests\Unit\Console\Commands\Import;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Import\Storage\ImportArrayStorage;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class CreateCSVImportTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateCSVImportTest extends TestCase
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
     * Covers a default run with perfect arguments.
     *
     * @covers \FireflyIII\Console\Commands\Import\CreateCSVImport
     */
    public function testHandle(): void
    {
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $jobRepos    = $this->mock(ImportJobRepositoryInterface::class);
        $fileRoutine = $this->mock(FileRoutine::class);
        $storage     = $this->mock(ImportArrayStorage::class);
        $user        = $this->user();
        $token       = new Preference;
        $importJob   = $this->user()->importJobs()->first();
        $file        = storage_path('build/test-upload.csv');
        $config      = storage_path('build/configuration.json');

        // set preferences:
        $token->data = 'token';

        // mock calls to repository:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($user);
        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('create')->atLeast()->once()->andReturn($importJob);
        $jobRepos->shouldReceive('storeCLIupload')->atLeast()->once()->andReturn(new MessageBag);
        $jobRepos->shouldReceive('setConfiguration')->atLeast()->once();

        // job is ready to run.
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storing_data'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storage_finished'])->atLeast()->once();

        // file routine gets called.
        $fileRoutine->shouldReceive('setImportJob')->atLeast()->once();
        $fileRoutine->shouldReceive('run')->atLeast()->once();

        // store data thing gets called.
        $storage->shouldReceive('setImportJob')->atLeast()->once();
        $storage->shouldReceive('store')->atLeast()->once();

        // mock Preferences.
        Preferences::shouldReceive('setForUser')->atLeast()->once()->withArgs([Mockery::any(), 'lastActivity', Mockery::any()]);
        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'access_token', null])->andReturn($token);


        $parameters = [
            $file,
            $config,
            '--user=1',
            '--token=token',
        ];

        $this->artisan('firefly-iii:csv-import ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Import file        : %s', $file))
             ->expectsOutput(sprintf('Configuration file : %s', $config))
             ->expectsOutput('User               : #1 (thegrumpydictator@gmail.com)')
             ->expectsOutput(sprintf('Job                : %s', $importJob->key))
             ->assertExitCode(0);

        // this method imports nothing so there is nothing to verify.

    }

    /**
     * Covers a default run with perfect arguments, but no import tag
     *
     * @covers \FireflyIII\Console\Commands\Import\CreateCSVImport
     */
    public function testHandleNoTag(): void
    {
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $jobRepos    = $this->mock(ImportJobRepositoryInterface::class);
        $fileRoutine = $this->mock(FileRoutine::class);
        $storage     = $this->mock(ImportArrayStorage::class);
        $user        = $this->user();
        $token       = new Preference;

        $importJob = ImportJob::create(
            [
                'key'       => 'key-' . $this->randomInt(),
                'user_id'   => 1,
                'file_type' => 'csv',
                'status'    => 'new',
                'errors'    => [],
            ]
        );

        $file   = storage_path('build/test-upload.csv');
        $config = storage_path('build/configuration.json');

        // set preferences:
        $token->data = 'token';

        // mock calls to repository:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($user);
        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('create')->atLeast()->once()->andReturn($importJob);
        $jobRepos->shouldReceive('storeCLIupload')->atLeast()->once()->andReturn(new MessageBag);
        $jobRepos->shouldReceive('setConfiguration')->atLeast()->once();

        // job is ready to run.
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storing_data'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storage_finished'])->atLeast()->once();

        // file routine gets called.
        $fileRoutine->shouldReceive('setImportJob')->atLeast()->once();
        $fileRoutine->shouldReceive('run')->atLeast()->once();

        // store data thing gets called.
        $storage->shouldReceive('setImportJob')->atLeast()->once();
        $storage->shouldReceive('store')->atLeast()->once();

        // mock Preferences.
        Preferences::shouldReceive('setForUser')->atLeast()->once()->withArgs([Mockery::any(), 'lastActivity', Mockery::any()]);
        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'access_token', null])->andReturn($token);


        $parameters = [
            $file,
            $config,
            '--user=1',
            '--token=token',
        ];

        $this->artisan('firefly-iii:csv-import ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Import file        : %s', $file))
             ->expectsOutput(sprintf('Configuration file : %s', $config))
             ->expectsOutput('User               : #1 (thegrumpydictator@gmail.com)')
             ->expectsOutput(sprintf('Job                : %s', $importJob->key))
             ->expectsOutput('No transactions have been imported :(.')
             ->assertExitCode(0);

        // this method imports nothing so there is nothing to verify.
    }

    /**
     * Covers a default run with perfect arguments, but errors after importing.
     *
     * @covers \FireflyIII\Console\Commands\Import\CreateCSVImport
     */
    public function testHandleErrors(): void
    {
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $jobRepos    = $this->mock(ImportJobRepositoryInterface::class);
        $fileRoutine = $this->mock(FileRoutine::class);
        $storage     = $this->mock(ImportArrayStorage::class);
        $user        = $this->user();
        $token       = new Preference;

        $importJob = ImportJob::create(
            [
                'key'       => 'key-' . $this->randomInt(),
                'user_id'   => 1,
                'file_type' => 'csv',
                'status'    => 'new',
                'errors'    => ['I am an error'],
            ]
        );

        $file   = storage_path('build/test-upload.csv');
        $config = storage_path('build/configuration.json');

        // set preferences:
        $token->data = 'token';

        // mock calls to repository:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($user);
        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('create')->atLeast()->once()->andReturn($importJob);
        $jobRepos->shouldReceive('storeCLIupload')->atLeast()->once()->andReturn(new MessageBag);
        $jobRepos->shouldReceive('setConfiguration')->atLeast()->once();

        // job is ready to run.
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storing_data'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storage_finished'])->atLeast()->once();

        // file routine gets called.
        $fileRoutine->shouldReceive('setImportJob')->atLeast()->once();
        $fileRoutine->shouldReceive('run')->atLeast()->once();

        // store data thing gets called.
        $storage->shouldReceive('setImportJob')->atLeast()->once();
        $storage->shouldReceive('store')->atLeast()->once();

        // mock Preferences.
        Preferences::shouldReceive('setForUser')->atLeast()->once()->withArgs([Mockery::any(), 'lastActivity', Mockery::any()]);
        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'access_token', null])->andReturn($token);


        $parameters = [
            $file,
            $config,
            '--user=1',
            '--token=token',
        ];

        $this->artisan('firefly-iii:csv-import ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Import file        : %s', $file))
             ->expectsOutput(sprintf('Configuration file : %s', $config))
             ->expectsOutput('User               : #1 (thegrumpydictator@gmail.com)')
             ->expectsOutput(sprintf('Job                : %s', $importJob->key))
             ->expectsOutput('- I am an error')
             ->assertExitCode(0);

        // this method imports nothing so there is nothing to verify.
    }

    /**
     * Crash while storing data.
     *
     * @covers \FireflyIII\Console\Commands\Import\CreateCSVImport
     */
    public function testHandleCrashStorage(): void
    {
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $jobRepos    = $this->mock(ImportJobRepositoryInterface::class);
        $fileRoutine = $this->mock(FileRoutine::class);
        $storage     = $this->mock(ImportArrayStorage::class);
        $user        = $this->user();
        $token       = new Preference;
        $importJob   = $this->user()->importJobs()->first();
        $file        = storage_path('build/test-upload.csv');
        $config      = storage_path('build/configuration.json');

        // set preferences:
        $token->data = 'token';

        // mock calls to repository:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($user);
        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('create')->atLeast()->once()->andReturn($importJob);
        $jobRepos->shouldReceive('storeCLIupload')->atLeast()->once()->andReturn(new MessageBag);
        $jobRepos->shouldReceive('setConfiguration')->atLeast()->once();

        // job is ready to run.
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'storing_data'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'error'])->atLeast()->once();

        // file routine gets called.
        $fileRoutine->shouldReceive('setImportJob')->atLeast()->once();
        $fileRoutine->shouldReceive('run')->atLeast()->once();

        // store data thing gets called.
        $storage->shouldReceive('setImportJob')->atLeast()->once();
        $storage->shouldReceive('store')->atLeast()->once()->andThrow(new FireflyException('I am storage error.'));

        // mock Preferences.
        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'access_token', null])->andReturn($token);


        $parameters = [
            $file,
            $config,
            '--user=1',
            '--token=token',
        ];
        Log::warning('The following error is part of a test.');
        $this->artisan('firefly-iii:csv-import ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Import file        : %s', $file))
             ->expectsOutput(sprintf('Configuration file : %s', $config))
             ->expectsOutput('User               : #1 (thegrumpydictator@gmail.com)')
             ->expectsOutput(sprintf('Job                : %s', $importJob->key))
             ->expectsOutput('The import routine crashed: I am storage error.')
             ->assertExitCode(1);

        // this method imports nothing so there is nothing to verify.

    }

    /**
     * The file processor crashes for some reason.
     *
     * @covers \FireflyIII\Console\Commands\Import\CreateCSVImport
     */
    public function testHandleCrashProcess(): void
    {
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $jobRepos    = $this->mock(ImportJobRepositoryInterface::class);
        $fileRoutine = $this->mock(FileRoutine::class);
        $storage     = $this->mock(ImportArrayStorage::class);
        $user        = $this->user();
        $token       = new Preference;
        $importJob   = $this->user()->importJobs()->first();
        $file        = storage_path('build/test-upload.csv');
        $config      = storage_path('build/configuration.json');

        // set preferences:
        $token->data = 'token';

        // mock calls to repository:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($user);
        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('create')->atLeast()->once()->andReturn($importJob);
        $jobRepos->shouldReceive('storeCLIupload')->atLeast()->once()->andReturn(new MessageBag);
        $jobRepos->shouldReceive('setConfiguration')->atLeast()->once();

        // job is ready to run.
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->atLeast()->once();
        $jobRepos->shouldReceive('setStatus')->withArgs([Mockery::any(), 'error'])->atLeast()->once();

        // file routine gets called.
        $fileRoutine->shouldReceive('setImportJob')->atLeast()->once();
        $fileRoutine->shouldReceive('run')->atLeast()->once()->andThrows(new FireflyException('I am big bad exception.'));

        // mock Preferences.
        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'access_token', null])->andReturn($token);

        $parameters = [
            $file,
            $config,
            '--user=1',
            '--token=token',
        ];
        Log::warning('The following error is part of a test.');
        $this->artisan('firefly-iii:csv-import ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Import file        : %s', $file))
             ->expectsOutput(sprintf('Configuration file : %s', $config))
             ->expectsOutput('User               : #1 (thegrumpydictator@gmail.com)')
             ->expectsOutput(sprintf('Job                : %s', $importJob->key))
             ->expectsOutput('The import routine crashed: I am big bad exception.')
             ->assertExitCode(1);

        // this method imports nothing so there is nothing to verify.

    }

    /**
     * Throw error when storing data.
     *
     * @covers \FireflyIII\Console\Commands\Import\CreateCSVImport
     */
    public function testHandleFileStoreError(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $this->mock(FileRoutine::class);
        $this->mock(ImportArrayStorage::class);
        $user      = $this->user();
        $token     = new Preference;
        $importJob = $this->user()->importJobs()->first();
        $file      = storage_path('build/test-upload.csv');
        $config    = storage_path('build/configuration.json');
        $messages  = new MessageBag;
        $messages->add('file', 'Some file error.');

        // set preferences:
        $token->data = 'token';

        // mock calls to repository:
        $userRepos->shouldReceive('findNull')->atLeast()->once()->andReturn($user);
        $jobRepos->shouldReceive('setUser')->atLeast()->once();
        $jobRepos->shouldReceive('create')->atLeast()->once()->andReturn($importJob);
        $jobRepos->shouldReceive('storeCLIupload')->atLeast()->once()->andReturn($messages);

        // mock Preferences.
        Preferences::shouldReceive('getForUser')->atLeast()->once()->withArgs([Mockery::any(), 'access_token', null])->andReturn($token);


        $parameters = [
            $file,
            $config,
            '--user=1',
            '--token=token',
        ];
        Log::warning('The following error is part of a test.');
        $this->artisan('firefly-iii:csv-import ' . implode(' ', $parameters))
             ->expectsOutput(sprintf('Import file        : %s', $file))
             ->expectsOutput(sprintf('Configuration file : %s', $config))
             ->expectsOutput('User               : #1 (thegrumpydictator@gmail.com)')
             ->expectsOutput(sprintf('Job                : %s', $importJob->key))
             ->expectsOutput('Some file error.')
             ->assertExitCode(1);

        // this method imports nothing so there is nothing to verify.
    }
}
