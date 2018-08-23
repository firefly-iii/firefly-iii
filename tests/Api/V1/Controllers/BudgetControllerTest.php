<?php
/**
 * BudgetControllerTest.php
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

namespace Tests\Api\V1\Controllers;


use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class BudgetControllerTest
 */
class BudgetControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Delete a budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get budget:
        $budget = $this->user()->budgets()->first();

        // call API
        $response = $this->delete('/api/v1/budgets/' . $budget->id);
        $response->assertStatus(204);
    }

    /**
     * Show all budgets
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testIndex(): void
    {
        $budgets = $this->user()->budgets()->get();
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getBudgets')->once()->andReturn($budgets);

        // call API
        $response = $this->get('/api/v1/budgets');
        $response->assertStatus(200);
        $response->assertSee($budgets->first()->name);
    }

    /**
     * Show a single budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testShow(): void
    {
        $budget = $this->user()->budgets()->first();
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->get('/api/v1/budgets/' . $budget->id);
        $response->assertStatus(200);
        $response->assertSee($budget->name);
    }

    /**
     * Store a new budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     * @covers \FireflyIII\Api\V1\Requests\BudgetRequest
     */
    public function testStore(): void
    {
        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();

        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->once()->andReturn($budget);

        // data to submit
        $data = [
            'name'   => 'Some budget',
            'active' => '1',
        ];

        // test API
        $response = $this->post('/api/v1/budgets', $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'budgets', 'links' => true],]);
        $response->assertSee($budget->name); // the amount
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     * @covers \FireflyIII\Api\V1\Requests\BudgetRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository = $this->mock(BudgetRepositoryInterface::class);

        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once()->andReturn($budget);

        // data to submit
        $data = [
            'name'   => 'Some new budget',
            'active' => '1',
        ];

        // test API
        $response = $this->put('/api/v1/budgets/' . $budget->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'budgets', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($budget->name);
    }


}
