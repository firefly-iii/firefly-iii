<?php
/**
 * SpectreJobConfigurationTest.php
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
use FireflyIII\Import\JobConfiguration\SpectreJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\Spectre\AuthenticatedHandler;
use FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseAccountsHandler;
use FireflyIII\Support\Import\JobConfiguration\Spectre\ChooseLoginHandler;
use FireflyIII\Support\Import\JobConfiguration\Spectre\DoAuthenticateHandler;
use FireflyIII\Support\Import\JobConfiguration\Spectre\NewSpectreJobHandler;
use Illuminate\Support\MessageBag;
use Log;
use Tests\TestCase;

/**
 * Class SpectreJobConfigurationTest
 */
class SpectreJobConfigurationTest extends TestCase
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
     * @covers \FireflyIII\Import\JobConfiguration\SpectreJobConfiguration
     */
    public function testConfigurationComplete(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'spectre_jc_A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "NewSpectreJobHandler" to be created because job is new.
        $handler = $this->mock(NewSpectreJobHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('configurationComplete')->once()->andReturn(true);

        $config = new SpectreJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($config->configurationComplete());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\SpectreJobConfiguration
     */
    public function testConfigureJob(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'spectre_jc_B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'do-authenticate';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $configData = ['ssome' => 'values'];
        $return     = new MessageBag();
        $return->add('some', 'return message');

        // expect "DoAuthenticateHandler" to be created because job is in "do-authenticate".
        $handler = $this->mock(DoAuthenticateHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('configureJob')->once()->withArgs([$configData])->andReturn($return);

        $config = new SpectreJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($return, $config->configureJob($configData));
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\SpectreJobConfiguration
     */
    public function testGetNextData(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'spectre_jc_C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'choose-login';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $data = ['ssome' => 'values'];

        // Expect choose-login handler because of state.
        $handler = $this->mock(ChooseLoginHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextData')->once()->andReturn($data);

        $config = new SpectreJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data, $config->getNextData());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\SpectreJobConfiguration
     */
    public function testGetNextView(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'spectre_jc_D' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'authenticated';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "AuthenticatedHandler" because of state.
        $handler = $this->mock(AuthenticatedHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextView')->once()->andReturn('import.fake.view');

        $config = new SpectreJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.fake.view', $config->getNextView());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\SpectreJobConfiguration
     */
    public function testGetNextViewAccount(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'spectre_jc_E' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'choose-accounts';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "ChooseAccountsHandler" because of state.
        $handler = $this->mock(ChooseAccountsHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextView')->once()->andReturn('import.fake.view2');

        $config = new SpectreJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.fake.view2', $config->getNextView());
    }


}
