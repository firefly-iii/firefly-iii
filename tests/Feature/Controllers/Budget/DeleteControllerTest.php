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

namespace Tests\Feature\Controllers\Budget;

use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
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
     * @covers \FireflyIII\Http\Controllers\Budget\DeleteController
     */
    public function testDelete(): void
    {
        $this->mockDefaultSession();
        $budget = $this->getRandomBudget();
        Log::debug('Now in testDelete()');
        // mock stuff
        $this->mock(BudgetRepositoryInterface::class);

        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();


        $this->be($this->user());
        $response = $this->get(route('budgets.delete', [$budget->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Budget\DeleteController
     */
    public function testDestroy(): void
    {
        $this->mockDefaultSession();
        $budget = $this->getRandomBudget();
        Log::debug('Now in testDestroy()');
        // mock stuff
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);


        Preferences::shouldReceive('mark')->atLeast()->once();

        $repository->shouldReceive('destroy')->andReturn(true);

        $this->session(['budgets.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('budgets.destroy', [$budget->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
