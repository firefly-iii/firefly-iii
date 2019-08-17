<?php
/**
 * BunqJobConfigurationTest.php
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

namespace Tests\Unit\Import\JobConfiguration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\JobConfiguration\BunqJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\Bunq\ChooseAccountsHandler;
use FireflyIII\Support\Import\JobConfiguration\Bunq\NewBunqJobHandler;
use Illuminate\Support\MessageBag;
use Log;
use Tests\TestCase;

/**
 * Class BunqJobConfigurationTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BunqJobConfigurationTest extends TestCase
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
     * @covers \FireflyIII\Import\JobConfiguration\BunqJobConfiguration
     */
    public function testConfigurationComplete(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'bunq_jc_A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "NewBunqJobHandler" to be created because job is new.
        $handler = $this->mock(NewBunqJobHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('configurationComplete')->once()->andReturn(true);

        $config = new BunqJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($config->configurationComplete());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\BunqJobConfiguration
     */
    public function testConfigureJob(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'bunq_jc_B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $configData = ['ssome' => 'values'];
        $return     = new MessageBag();
        $return->add('some', 'return message');

        // expect "NewBunqJobHandler" to be created because job is in "do-authenticate".
        $handler = $this->mock(NewBunqJobHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('configureJob')->once()->withArgs([$configData])->andReturn($return);

        $config = new BunqJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($return, $config->configureJob($configData));
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\BunqJobConfiguration
     */
    public function testGetNextData(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'bunq_jc_C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $data = ['ssome' => 'values'];

        // Expect "NewBunqJobHandler" because of state.
        $handler = $this->mock(NewBunqJobHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextData')->once()->andReturn($data);

        $config = new BunqJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data, $config->getNextData());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\BunqJobConfiguration
     */
    public function testGetNextViewAccount(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'bunq_jc_E' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'choose-accounts';
        $job->provider      = 'bunq';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "ChooseAccountsHandler" because of state.
        $handler = $this->mock(ChooseAccountsHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextView')->once()->andReturn('import.fake.view2');

        $config = new BunqJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.fake.view2', $config->getNextView());
    }


}
