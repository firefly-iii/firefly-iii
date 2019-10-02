<?php
/**
 * EditControllerTest.php
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
 * Class EditControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EditControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Budget\EditController
     */
    public function testEdit(): void
    {
        Log::debug('Now in testEdit()');
        // mock stuff
        $this->mock(BudgetRepositoryInterface::class);
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $this->mockDefaultSession();
        $this->be($this->user());
        $response = $this->get(route('budgets.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Budget\EditController
     */
    public function testUpdate(): void
    {
        Log::debug('Now in testUpdate()');
        // mock stuff
        $budget     = $this->getRandomBudget();
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);

        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('update');
        $repository->shouldReceive('cleanupBudgets');

        $this->mockDefaultSession();
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->session(['budgets.edit.uri' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Budget ' . $this->randomInt(),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
