<?php
/**
 * ConfigureUploadHandlerTest.php
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

namespace Tests\Unit\Support\Import\JobConfiguration\File;


use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class ConfigureUploadHandlerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigureUploadHandlerTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler
     */
    public function testConfigureJobAccount(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'upload-B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();


        $data           = [
            'csv_import_account' => '1',
            'csv_delimiter'      => ',',
            'has_headers'        => '1',
            'date_format'        => 'Y-m-d',
            'apply_rules'        => '1',
            'specifics'          => ['IngDescription'],
        ];
        $expectedConfig = [
            'has-headers'    => true,
            'date-format'    => 'Y-m-d',
            'delimiter'      => ',',
            'apply-rules'    => true,
            'specifics'      => [
                'IngDescription' => 1,
            ],
            'import-account' => 1,
        ];

        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([1])->andReturn($this->user()->accounts()->first());
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expectedConfig]);
        $repository->shouldReceive('setStage')->once()->withArgs([Mockery::any(), 'roles']);

        $handler = new ConfigureUploadHandler;
        $handler->setImportJob($job);
        $result = $handler->configureJob($data);
        $this->assertCount(0, $result);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler
     */
    public function testConfigureJobNoAccount(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'upload-B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();


        $data           = [
            'csv_import_account' => '1',
            'csv_delimiter'      => ',',
            'has_headers'        => '1',
            'date_format'        => 'Y-m-d',
            'apply_rules'        => '1',
            'specifics'          => ['IngDescription'],
        ];
        $expectedConfig = [
            'has-headers' => true,
            'date-format' => 'Y-m-d',
            'delimiter'   => ',',
            'apply-rules' => true,
            'specifics'   => [
                'IngDescription' => 1,
            ],
        ];

        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findNull')->once()->withArgs([1])->andReturn(null);
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), $expectedConfig]);

        $handler = new ConfigureUploadHandler;
        $handler->setImportJob($job);
        $result = $handler->configureJob($data);
        $this->assertCount(1, $result);
        $this->assertEquals('You have selected an invalid account to import into.', $result->get('account')[0]);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler
     */
    public function testGetNextData(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'upload-A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fake';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $repository = $this->mock(ImportJobRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('setUser');
        $accountRepos->shouldReceive('setUser');
        $repository->shouldReceive('setConfiguration')->once()->withArgs([Mockery::any(), ['date-format' => 'Ymd']]);

        $handler = new ConfigureUploadHandler;
        $handler->setImportJob($job);
        $result   = $handler->getNextData();
        $expected = [
            'accounts'   => [],
            'delimiters' => [],
        ];
        // not much to compare, really.
        $this->assertEquals($expected['accounts'], $result['accounts']);
    }

    /**
     * @covers \FireflyIII\Support\Import\JobConfiguration\File\ConfigureUploadHandler
     */
    public function testGetSpecifics(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $array    = [
            'specifics' => [
                'IngDescription', 'BadFakeNewsThing',
            ],
        ];
        $expected = [
            'IngDescription' => 1,
        ];

        $handler = new ConfigureUploadHandler;
        $result  = $handler->getSpecifics($array);
        $this->assertEquals($expected, $result);
    }

}
