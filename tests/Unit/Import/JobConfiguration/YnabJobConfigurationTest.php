<?php
/**
 * YnabJobConfigurationTest.php
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
use FireflyIII\Import\JobConfiguration\YnabJobConfiguration;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\JobConfiguration\Ynab\NewYnabJobHandler;
use FireflyIII\Support\Import\JobConfiguration\Ynab\SelectAccountsHandler;
use FireflyIII\Support\Import\JobConfiguration\Ynab\SelectBudgetHandler;
use Illuminate\Support\MessageBag;
use Log;
use Tests\TestCase;

/**
 * Class YnabJobConfigurationTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class YnabJobConfigurationTest extends TestCase
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
     * @covers \FireflyIII\Import\JobConfiguration\YnabJobConfiguration
     */
    public function testConfigurationComplete(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_jc_A' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "NewYnabJobHandler" to be created because job is new.
        $handler = $this->mock(NewYnabJobHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('configurationComplete')->once()->andReturn(true);

        $config = new YnabJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($config->configurationComplete());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\YnabJobConfiguration
     */
    public function testConfigureJob(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_jc_B' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'select_budgets';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $configData = ['ssome' => 'values'];
        $return     = new MessageBag();
        $return->add('some', 'return message');

        // expect "SelectBudgetHandler" to be created because job is in "select_budgets".
        $handler = $this->mock(SelectBudgetHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('configureJob')->once()->withArgs([$configData])->andReturn($return);

        $config = new YnabJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($return, $config->configureJob($configData));
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\YnabJobConfiguration
     */
    public function testGetNextData(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_jc_C' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'select_accounts';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();
        $data = ['ssome' => 'values'];

        // Expect "SelectAccountsHandler" because state is "select_accounts"
        $handler = $this->mock(SelectAccountsHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextData')->once()->andReturn($data);

        $config = new YnabJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals($data, $config->getNextData());
    }

    /**
     * @covers \FireflyIII\Import\JobConfiguration\YnabJobConfiguration
     */
    public function testGetNextView(): void
    {
        $jobRepos = $this->mock(ImportJobRepositoryInterface::class);
        $jobRepos->shouldReceive('setUser')->once();
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_jc_E' . $this->randomInt();
        $job->status        = 'new';
        $job->stage         = 'new';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // expect "NewYnabJobHandler" because of state.
        $handler = $this->mock(NewYnabJobHandler::class);
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getNextView')->once()->andReturn('import.fake.view2');

        $config = new YnabJobConfiguration;
        try {
            $config->setImportJob($job);
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertEquals('import.fake.view2', $config->getNextView());
    }


}
