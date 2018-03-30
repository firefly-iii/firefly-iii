<?php
/**
 * CreateExportTest.php
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

use FireflyIII\Export\ProcessorInterface;
use FireflyIII\Models\ExportJob;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ExportJob\ExportJobRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Mockery;
use Preferences;
use Storage;
use Tests\TestCase;

/**
 * Class CreateExportTest
 */
class CreateExportTest extends TestCase
{

    /**
     * @covers \FireflyIII\Console\Commands\CreateExport
     * @covers \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testBasic()
    {
        $journal           = $this->user()->transactionJournals()->inRandomOrder()->first();
        $preference        = new Preference;
        $preference->data  = 'token';
        $job               = new ExportJob;
        $job->key          = 'export-' . random_int(1, 1000);
        $journalRepository = $this->mock(JournalRepositoryInterface::class);
        $userRepository    = $this->mock(UserRepositoryInterface::class);
        $jobRepository     = $this->mock(ExportJobRepositoryInterface::class);
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $processor         = $this->mock(ProcessorInterface::class);

        $journalRepository->shouldReceive('setUser')->once();
        $journalRepository->shouldReceive('first')->once()->andReturn($journal);

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])->andReturn($preference)->once();

        $accountRepository->shouldReceive('setUser')->once();
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection)->once();

        $jobRepository->shouldReceive('setUser')->once();
        $jobRepository->shouldReceive('create')->once()->andReturn($job);

        $userRepository->shouldReceive('findNull')->andReturn($this->user())->twice();

        $processor->shouldReceive('setSettings')->once();
        $processor->shouldReceive('collectJournals')->once();
        $processor->shouldReceive('exportJournals')->once();
        $processor->shouldReceive('convertJournals')->once();
        $processor->shouldReceive('collectAttachments')->once();
        $processor->shouldReceive('collectOldUploads')->once();
        $processor->shouldReceive('createZipFile')->once();

        $fakeNews = 'I am a zipfile';
        Storage::fake('export');
        Storage::disk('export')->put(sprintf('%s.zip', $job->key), $fakeNews);
        $output = $this->artisan(
            'firefly:create-export',
            [
                '--user'             => 1,
                '--token'            => 'token',
                '--with_attachments' => true,
                '--with_uploads'     => true,

            ]
        );

        Storage::disk('export')->delete(sprintf('%s.zip', $job->key));
        $this->assertEquals(0, $output);
    }

    /**
     * @covers \FireflyIII\Console\Commands\CreateExport
     * @covers \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testInvalidToken()
    {
        $preference       = new Preference;
        $preference->data = 'token';
        $job              = new ExportJob;
        $job->key         = 'export-' . random_int(1, 1000);
        $userRepository   = $this->mock(UserRepositoryInterface::class);

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])->andReturn($preference)->once();
        $userRepository->shouldReceive('findNull')->andReturn($this->user())->once();

        $output = $this->artisan(
            'firefly:create-export',
            [
                '--user'             => 1,
                '--token'            => 'wrong_token',
                '--with_attachments' => true,
                '--with_uploads'     => true,

            ]
        );
        $this->assertEquals(1, $output);
    }

    /**
     * @covers \FireflyIII\Console\Commands\CreateExport
     * @covers \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testNoSuchUser()
    {
        $preference       = new Preference;
        $preference->data = 'token';
        $job              = new ExportJob;
        $job->key         = 'export-' . random_int(1, 1000);
        $userRepository   = $this->mock(UserRepositoryInterface::class);

        $userRepository->shouldReceive('findNull')->andReturn(null)->once();

        $output = $this->artisan(
            'firefly:create-export',
            [
                '--user'             => 0,
                '--token'            => 'wrong_token',
                '--with_attachments' => true,
                '--with_uploads'     => true,

            ]
        );
        $this->assertEquals(1, $output);
    }

    /**
     * @covers \FireflyIII\Console\Commands\CreateExport
     * @covers \FireflyIII\Console\Commands\VerifiesAccessToken
     */
    public function testNoToken()
    {
        $preference       = new Preference;
        $preference->data = 'token';
        $job              = new ExportJob;
        $job->key         = 'export-' . random_int(1, 1000);
        $userRepository   = $this->mock(UserRepositoryInterface::class);

        Preferences::shouldReceive('getForUser')->withArgs([Mockery::any(), 'access_token', null])->andReturn(null)->once();
        $userRepository->shouldReceive('findNull')->andReturn($this->user())->once();

        $output = $this->artisan(
            'firefly:create-export',
            [
                '--user'             => 1,
                '--token'            => 'wrong_token',
                '--with_attachments' => true,
                '--with_uploads'     => true,

            ]
        );
        $this->assertEquals(1, $output);
    }

}