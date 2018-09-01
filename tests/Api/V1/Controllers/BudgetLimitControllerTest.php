<?php
/**
 * BudgetLimitControllerTest.php
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
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class BudgetLimitControllerTest
 */
class BudgetLimitControllerTest extends TestCase
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
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroyBudgetLimit')->once()->andReturn(true);

        // Create a budget limit (just in case).
        /** @var Budget $budget */
        $budget      = $this->user()->budgets()->first();
        $budgetLimit = BudgetLimit::create(
            [
                'budget_id'  => $budget->id,
                'start_date' => '2018-01-01',
                'end_date'   => '2018-01-31',
                'amount'     => 1,
            ]
        );

        // call API
        $response = $this->delete('/api/v1/budget_limits/' . $budgetLimit->id);
        $response->assertStatus(204);
    }

    /**
     * Show budget limits by budget, include no dates.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testIndex(): void
    {
        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn($budget->budgetlimits()->get());

        // call API
        $params   = [
            'budget_id' => $budget->id,
        ];
        $response = $this->get('/api/v1/budget_limits?' . http_build_query($params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show budget limits by budget, include dates.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testIndexNoBudget(): void
    {
        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn(null);
        $repository->shouldReceive('getAllBudgetLimits')->once()->andReturn($budget->budgetlimits()->get());

        // call API
        $params   = [
            'start' => '2018-01-01',
            'end'   => '2018-01-31',
        ];
        $uri      = '/api/v1/budget_limits?' . http_build_query($params);
        $response = $this->get($uri);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show budget limits by budget, include dates.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testIndexWithDates(): void
    {
        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn($budget->budgetlimits()->get());

        // call API
        $params   = [
            'budget_id' => $budget->id,
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
        ];
        $response = $this->get('/api/v1/budget_limits?' . http_build_query($params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testShow(): void
    {
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // Create a budget limit (just in case).
        /** @var Budget $budget */
        $budget      = $this->user()->budgets()->first();
        $budgetLimit = BudgetLimit::create(
            [
                'budget_id'  => $budget->id,
                'start_date' => '2018-01-01',
                'end_date'   => '2018-01-31',
                'amount'     => 1,
            ]
        );


        $response = $this->get('/api/v1/budget_limits/' . $budgetLimit->id);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
     */
    public function testStore(): void
    {
        $budget      = $this->user()->budgets()->first();
        $budgetLimit = BudgetLimit::create(
            [
                'budget_id'  => $budget->id,
                'start_date' => '2018-01-01',
                'end_date'   => '2018-01-31',
                'amount'     => 1,
            ]
        );
        $data
                     = [
            'budget_id'  => $budget->id,
            'start_date' => '2018-01-01',
            'end_date'   => '2018-01-31',
            'amount'     => 1,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('findNull')->andReturn($budget)->once();
        $repository->shouldReceive('storeBudgetLimit')->andReturn($budgetLimit)->once();


        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->post('/api/v1/budget_limits', $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new budget limit, but give error
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
     */
    public function testStoreBadBudget(): void
    {
        $data
            = [
            'budget_id'  => '1',
            'start_date' => '2018-01-01',
            'end_date'   => '2018-01-31',
            'amount'     => 1,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('findNull')->andReturn(null)->once();


        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->post('/api/v1/budget_limits', $data);
        $response->assertStatus(500);
        $response->assertSee('Unknown budget.');
    }

    /**
     * Test update of budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
     */
    public function testUpdate(): void
    {
        $budget      = $this->user()->budgets()->first();
        $budgetLimit = BudgetLimit::create(
            [
                'budget_id'  => $budget->id,
                'start_date' => '2018-01-01',
                'end_date'   => '2018-01-31',
                'amount'     => 1,
            ]
        );
        $data
                     = [
            'budget_id'  => $budget->id,
            'start_date' => '2018-01-01',
            'end_date'   => '2018-01-31',
            'amount'     => 2,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('findNull')->andReturn($budget)->once();
        $repository->shouldReceive('updateBudgetLimit')->andReturn($budgetLimit)->once();


        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->put('/api/v1/budget_limits/' . $budgetLimit->id, $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Test update of budget limit but submit bad budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
     */
    public function testUpdateBadBudget(): void
    {
        $budget      = $this->user()->budgets()->first();
        $budgetLimit = BudgetLimit::create(
            [
                'budget_id'  => $budget->id,
                'start_date' => '2018-01-01',
                'end_date'   => '2018-01-31',
                'amount'     => 1,
            ]
        );
        $data
                     = [
            'budget_id'  => $budget->id,
            'start_date' => '2018-01-01',
            'end_date'   => '2018-01-31',
            'amount'     => 2,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('findNull')->andReturn(null)->once();


        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->put('/api/v1/budget_limits/' . $budgetLimit->id, $data);
        $response->assertStatus(500);
        $response->assertSee('Unknown budget.');
    }

}
