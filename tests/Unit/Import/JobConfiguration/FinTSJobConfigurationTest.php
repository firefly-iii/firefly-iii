<?php
/**
 * FinTSJobConfigurationTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Import\JobConfiguration;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\JobConfiguration\FinTSJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\FinTS\ChooseAccountHandler;
use FireflyIII\Support\Import\JobConfiguration\FinTS\NewFinTSJobHandler;
use Log;
use Tests\TestCase;

/**
 * Class FinTSJobConfigurationTest
 */
class FinTSJobConfigurationTest extends TestCase
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
     * @covers \FireflyIII\Import\JobConfiguration\FinTSJobConfiguration
     */
    public function testConfigurationComplete(): void
    {
        $this->mock(ImportJobRepositoryInterface::class);
        $this->mock(NewFinTSJobHandler::class);

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'fints_jc_A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'go-for-import';
        $job->provider      = 'fints';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $config = new FinTSJobConfiguration;
        $config->setImportJob($job);
        $this->assertTrue($config->configurationComplete());
    }


    /**
     * @covers \FireflyIII\Import\JobConfiguration\FinTSJobConfiguration
     */
    public function testConfigureJob(): void
    {
        $this->mock(ImportJobRepositoryInterface::class);
        $handler = $this->mock(NewFinTSJobHandler::class);

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'fints_jc_B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'fints';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $handler->shouldReceive('setImportJob')->atLeast()->once();
        $handler->shouldReceive('configureJob')->atLeast()->once()->withArgs([[123]]);


        $config = new FinTSJobConfiguration;
        $config->setImportJob($job);
        try {
            $config->configureJob([123]);
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\FinTSJobConfiguration
     */
    public function testGetNextData(): void
    {
        $this->mock(ImportJobRepositoryInterface::class);
        $handler = $this->mock(ChooseAccountHandler::class);

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'fints_jc_C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'choose_account';
        $job->provider      = 'fints';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $handler->shouldReceive('setImportJob')->atLeast()->once();
        $handler->shouldReceive('getNextData')->atLeast()->once()->withNoArgs()->andReturn([456]);


        $res    = [];
        $config = new FinTSJobConfiguration;
        $config->setImportJob($job);
        try {
            $res = $config->getNextData();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
        $this->assertEquals([456], $res);
    }


    /**
     * @covers \FireflyIII\Import\JobConfiguration\FinTSJobConfiguration
     */
    public function testGetNextView(): void
    {
        $this->mock(ImportJobRepositoryInterface::class);
        $this->mock(ChooseAccountHandler::class);

        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'fints_jc_D' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'choose_account';
        $job->provider      = 'fints';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        $res    = [];
        $config = new FinTSJobConfiguration;
        $config->setImportJob($job);
        try {
            $res = $config->getNextView();
        } catch (FireflyException $e) {
            $this->assertFalse(true, $e->getMessage());
        }
        $this->assertEquals('import.fints.choose_account', $res);
    }
}