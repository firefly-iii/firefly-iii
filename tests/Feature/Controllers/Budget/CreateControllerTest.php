<?php
/**
 * CreateControllerTest.php
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

namespace Tests\Feature\Controllers\Budget;


use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class CreateControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CreateControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Budget\CreateController
     */
    public function testCreate(): void
    {
        $this->mock(BudgetRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $this->mockDefaultSession();


        $this->be($this->user());
        $response = $this->get(route('budgets.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\CreateController
     */
    public function testStore(): void
    {
        Log::debug('Now in testStore()');
        // mock stuff
        $budget     = $this->getRandomBudget();
        $repository = $this->mock(BudgetRepositoryInterface::class);

        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('store')->andReturn($budget);
        $repository->shouldReceive('cleanupBudgets');

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['budgets.create.uri' => 'http://localhost']);

        $data = [
            'name' => sprintf('New Budget %s', $this->randomInt()),
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

}
