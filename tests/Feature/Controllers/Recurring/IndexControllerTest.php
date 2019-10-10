<?php
/**
 * IndexControllerTest.php
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

namespace Tests\Feature\Controllers\Recurring;

use FireflyConfig;
use FireflyIII\Factory\CategoryFactory;
use FireflyIII\Models\Configuration;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class IndexControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class IndexControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Recurring\IndexController
     */
    public function testIndex(): void
    {

        $repository      = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $userRepos       = $this->mock(UserRepositoryInterface::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $transformer     = $this->mock(RecurrenceTransformer::class);

        // mock calls
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $repository->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([]);

        $this->mockDefaultSession();

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            [
                'id'           => 5,
                'first_date'   => '2018-01-01',
                'repeat_until' => null,
                'latest_date'  => null,
            ]
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $config       = new Configuration;
        $config->data = 0;

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        $collection = $this->user()->recurrences()->take(2)->get();

        // mock cron job config:
        FireflyConfig::shouldReceive('get')->withArgs(['last_rt_job', 0])->once()->andReturn($config);

        $repository->shouldReceive('get')->andReturn($collection)->once();


        $this->be($this->user());
        $response = $this->get(route('recurring.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * The last time the recurring job fired it was a long time ago.
     *
     * @covers \FireflyIII\Http\Controllers\Recurring\IndexController
     */
    public function testIndexLongAgo(): void
    {

        $repository      = $this->mock(RecurringRepositoryInterface::class);
        $budgetRepos     = $this->mock(BudgetRepositoryInterface::class);
        $userRepos       = $this->mock(UserRepositoryInterface::class);
        $categoryFactory = $this->mock(CategoryFactory::class);
        $transformer     = $this->mock(RecurrenceTransformer::class);

        // mock calls
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $repository->shouldReceive('getOccurrencesInRange')->atLeast()->once()->andReturn([]);

        $this->mockDefaultSession();

        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            [
                'id'           => 5,
                'first_date'   => '2018-01-01',
                'repeat_until' => null,
                'latest_date'  => null,
            ]
        );

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $config       = new Configuration;
        $config->data = 1;

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        $collection = $this->user()->recurrences()->take(2)->get();

        // mock cron job config:
        FireflyConfig::shouldReceive('get')->withArgs(['last_rt_job', 0])->once()->andReturn($config);

        $repository->shouldReceive('get')->andReturn($collection)->once();


        $this->be($this->user());
        $response = $this->get(route('recurring.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSessionHas('warning');
    }



}
