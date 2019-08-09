<?php
/**
 * DeleteControllerTest.php
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

namespace Tests\Feature\Controllers\Recurring;

use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class DeleteControllerTest
 */
class DeleteControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Recurring\DeleteController
     */
    public function testDelete(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();

        $recurringRepos->shouldReceive('getTransactions')->andReturn(new Collection)->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('recurring.delete', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Recurring\DeleteController
     */
    public function testDestroy(): void
    {
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $recurringRepos->shouldReceive('destroy')->once();


        $this->be($this->user());
        $response = $this->post(route('recurring.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }


}
