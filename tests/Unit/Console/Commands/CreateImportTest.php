<?php
/**
 * CreateImportTest.php
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

namespace Tests\Unit\Console\Commands;


use FireflyIII\Import\Routine\FileRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Internal\File\EncryptService;
use Illuminate\Support\Collection;
use Storage;
use Tests\TestCase;


/**
 * Class CreateImportTest
 */
class CreateImportTest extends TestCase
{
    /**
     * @covers \FireflyIII\Console\Commands\CreateImport
     * @covers \FireflyIII\Console\Commands\EncryptFile
     * @covers \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testBasic()
    {
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $encrypter = $this->mock(EncryptService::class);
        $routine   = $this->mock(FileRoutine::class);

        $collection = new Collection();
        $collection->push('I am error');

        $job            = new ImportJob;
        $job->key       = 'import-' . random_int(1, 1000);
        $job->file_type = 'csv';
        $job->user      = $this->user();

        $userRepos->shouldReceive('findNull')->andReturn($this->user())->times(2);
        $jobRepos->shouldReceive('setUser')->once();
        $jobRepos->shouldReceive('create')->once()->andReturn($job);
        $jobRepos->shouldReceive('setConfiguration')->once();
        $jobRepos->shouldReceive('updateStatus')->once();
        $encrypter->shouldReceive('encrypt')->once();
        $routine->shouldReceive('setJob')->once();
        $routine->shouldReceive('run')->once();
        $routine->shouldReceive('getErrors')->once()->andReturn($collection);
        $routine->shouldReceive('getJournals')->once()->andReturn(new Collection());
        $routine->shouldReceive('getLines')->once()->andReturn(7);

        Storage::fake('upload');

        $output = $this->artisan(
            'firefly:create-import',
            [
                '--user'        => 1,
                '--token'       => 'token',
                'file'          => 'storage/build/test-upload.csv',
                'configuration' => 'storage/build/test-upload.json',
                '--start'       => true,

            ]
        );

        $this->assertEquals(0, $output);
    }

    /**
     * @covers \FireflyIII\Console\Commands\CreateImport
     * @covers \FireflyIII\Console\Commands\EncryptFile
     * @covers \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testInvalidToken()
    {
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $encrypter = $this->mock(EncryptService::class);
        $routine   = $this->mock(FileRoutine::class);

        $collection = new Collection();
        $collection->push('I am error');

        $job            = new ImportJob;
        $job->key       = 'import-' . random_int(1, 1000);
        $job->file_type = 'csv';
        $job->user      = $this->user();

        $userRepos->shouldReceive('findNull')->andReturn($this->user())->times(1);

        Storage::fake('upload');

        $output = $this->artisan(
            'firefly:create-import',
            [
                '--user'        => 1,
                '--token'       => 'tokenX',
                'file'          => 'storage/build/test-upload.csv',
                'configuration' => 'storage/build/test-upload.json',
                '--start'       => true,

            ]
        );

        $this->assertEquals(1, $output);
    }

    /**
     * @covers                   \FireflyIII\Console\Commands\CreateImport
     * @covers                   \FireflyIII\Console\Commands\EncryptFile
     * @covers                   \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testInvalidType()
    {
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $encrypter = $this->mock(EncryptService::class);
        $routine   = $this->mock(FileRoutine::class);

        $collection = new Collection();
        $collection->push('I am error');

        $job            = new ImportJob;
        $job->key       = 'import-' . random_int(1, 1000);
        $job->file_type = 'csv';
        $job->user      = $this->user();

        $userRepos->shouldReceive('findNull')->andReturn($this->user())->times(2);

        $output = $this->artisan(
            'firefly:create-import',
            [
                '--user'        => 1,
                '--token'       => 'token',
                '--type'        => 'csvX',
                'file'          => 'storage/build/test-upload.csv',
                'configuration' => 'storage/build/test-upload.json',
                '--start'       => true,

            ]
        );

        $this->assertEquals(1, $output);
    }


    /**
     * @covers                   \FireflyIII\Console\Commands\CreateImport
     * @covers                   \FireflyIII\Console\Commands\EncryptFile
     * @covers                   \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testInvalidJSON()
    {
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $encrypter = $this->mock(EncryptService::class);
        $routine   = $this->mock(FileRoutine::class);

        $collection = new Collection();
        $collection->push('I am error');

        $job            = new ImportJob;
        $job->key       = 'import-' . random_int(1, 1000);
        $job->file_type = 'csv';
        $job->user      = $this->user();

        $userRepos->shouldReceive('findNull')->andReturn($this->user())->times(2);

        $output = $this->artisan(
            'firefly:create-import',
            [
                '--user'        => 1,
                '--token'       => 'token',
                '--type'        => 'csv',
                'file'          => 'storage/build/test-upload.csv',
                'configuration' => 'storage/build/test-upload.csv',
                '--start'       => true,

            ]
        );

        $this->assertEquals(1, $output);
    }

    /**
     * @covers                   \FireflyIII\Console\Commands\CreateImport
     * @covers                   \FireflyIII\Console\Commands\EncryptFile
     * @covers                   \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testFileNotFound()
    {
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $encrypter = $this->mock(EncryptService::class);
        $routine   = $this->mock(FileRoutine::class);

        $collection = new Collection();
        $collection->push('I am error');

        $job            = new ImportJob;
        $job->key       = 'import-' . random_int(1, 1000);
        $job->file_type = 'csv';
        $job->user      = $this->user();

        $userRepos->shouldReceive('findNull')->andReturn($this->user())->times(2);

        $output = $this->artisan(
            'firefly:create-import',
            [
                '--user'        => 1,
                '--token'       => 'token',
                '--type'        => 'csv',
                'file'          => 'storage/build/test-uploadX.csv',
                'configuration' => 'storage/build/test-upload.json',
                '--start'       => true,

            ]
        );

        $this->assertEquals(1, $output);
    }

    /**
     * @covers                   \FireflyIII\Console\Commands\CreateImport
     * @covers                   \FireflyIII\Console\Commands\EncryptFile
     * @covers                   \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testConfigNotFound()
    {
        $jobRepos  = $this->mock(ImportJobRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $encrypter = $this->mock(EncryptService::class);
        $routine   = $this->mock(FileRoutine::class);

        $collection = new Collection();
        $collection->push('I am error');

        $job            = new ImportJob;
        $job->key       = 'import-' . random_int(1, 1000);
        $job->file_type = 'csv';
        $job->user      = $this->user();

        $userRepos->shouldReceive('findNull')->andReturn($this->user())->times(2);

        $output = $this->artisan(
            'firefly:create-import',
            [
                '--user'        => 1,
                '--token'       => 'token',
                '--type'        => 'csv',
                'file'          => 'storage/build/test-upload.csv',
                'configuration' => 'storage/build/test-uploadX.json',
                '--start'       => true,

            ]
        );

        $this->assertEquals(1, $output);
    }

}
