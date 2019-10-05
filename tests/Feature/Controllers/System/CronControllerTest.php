<?php
/**
 * CronControllerTest.php
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

namespace Tests\Feature\Controllers\System;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Support\Cronjobs\RecurringCronjob;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class CronControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CronControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\System\CronController
     * @covers \FireflyIII\Support\Binder\CLIToken
     */
    public function testCron(): void
    {
        $users            = new Collection([$this->user()]);
        $job              = $this->mock(RecurringCronjob::class);
        $preference       = new Preference();
        $preference->data = 'token';
        $job->shouldReceive('fire')->once()->andReturn(true);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('all')->andReturn($users);
        Preferences::shouldReceive('getForUser')
                    ->withArgs([Mockery::any(), 'access_token', null])
                    ->andReturn($preference)->once();
        $response = $this->get(route('cron.cron', ['token']));
        $response->assertStatus(200);
        $response->assertSee('The recurring transaction cron job fired successfully.');

    }

    /**
     * @covers \FireflyIII\Http\Controllers\System\CronController
     * @covers \FireflyIII\Support\Binder\CLIToken
     */
    public function testCronException(): void
    {
        $users            = new Collection([$this->user()]);
        $job              = $this->mock(RecurringCronjob::class);
        $preference       = new Preference();
        $preference->data = 'token';
        $job->shouldReceive('fire')->once()->andThrow(new FireflyException('Exception noted.'));
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('all')->andReturn($users);
        Preferences::shouldReceive('getForUser')
                    ->withArgs([Mockery::any(), 'access_token', null])
                    ->andReturn($preference)->once();
        $response = $this->get(route('cron.cron', ['token']));
        $response->assertStatus(200);
        $response->assertSee('Exception noted.');

    }

    /**
     * @covers \FireflyIII\Http\Controllers\System\CronController
     * @covers \FireflyIII\Support\Binder\CLIToken
     */
    public function testCronFalse(): void
    {
        $users            = new Collection([$this->user()]);
        $job              = $this->mock(RecurringCronjob::class);
        $preference       = new Preference();
        $preference->data = 'token';
        $job->shouldReceive('fire')->once()->andReturn(false);
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('all')->andReturn($users);
        Preferences::shouldReceive('getForUser')
                    ->withArgs([Mockery::any(), 'access_token', null])
                    ->andReturn($preference)->once();
        $response = $this->get(route('cron.cron', ['token']));
        $response->assertStatus(200);
        $response->assertSee('The recurring transaction cron job did not fire.');

    }

}
