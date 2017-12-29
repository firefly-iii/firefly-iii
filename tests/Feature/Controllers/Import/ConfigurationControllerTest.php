<?php
/**
 * ConfigurationControllerTest.php
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

use FireflyIII\Import\Configuration\FileConfigurator;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigurationControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::index
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::makeConfigurator
     */
    public function testIndex()
    {
        /** @var ImportJob $job */
        $job          = $this->user()->importJobs()->where('key', 'configuring')->first();
        $configurator = $this->mock(FileConfigurator::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(false);
        $configurator->shouldReceive('getNextView')->once()->andReturn('error'); // does not matter which view is returned.
        $configurator->shouldReceive('getNextData')->once()->andReturn([]);
        $repository->shouldReceive('updateStatus')->once();

        $this->be($this->user());
        $response = $this->get(route('import.configure', [$job->key]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::__construct
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::index
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::makeConfigurator
     */
    public function testIndexConfigured()
    {
        /** @var ImportJob $job */
        $job          = $this->user()->importJobs()->where('key', 'configured')->first();
        $configurator = $this->mock(FileConfigurator::class);
        $repository   = $this->mock(ImportJobRepositoryInterface::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(true);
        $repository->shouldReceive('updateStatus')->once();

        $this->be($this->user());
        $response = $this->get(route('import.configure', [$job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.status', [$job->key]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::post
     */
    public function testPost()
    {
        /** @var ImportJob $job */
        $job          = $this->user()->importJobs()->where('key', 'configuring')->first();
        $data         = ['some' => 'config'];
        $configurator = $this->mock(FileConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(false);
        $configurator->shouldReceive('configureJob')->once()->withArgs([$data]);
        $configurator->shouldReceive('getWarningMessage')->once()->andReturn('Some warning');

        $this->be($this->user());
        $response = $this->post(route('import.configure.post', [$job->key]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.configure', [$job->key]));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\ConfigurationController::post
     */
    public function testPostConfigured()
    {
        /** @var ImportJob $job */
        $job          = $this->user()->importJobs()->where('key', 'configuring')->first();
        $data         = ['some' => 'config'];
        $configurator = $this->mock(FileConfigurator::class);
        $configurator->shouldReceive('setJob')->once();
        $configurator->shouldReceive('isJobConfigured')->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->post(route('import.configure.post', [$job->key]), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('import.status', [$job->key]));
    }
}
