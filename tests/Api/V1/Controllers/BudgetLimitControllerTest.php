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



use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\BudgetLimitTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class BudgetLimitControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Store new budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testStore(): void
    {
        $budget      = $this->user()->budgets()->first();
        $transformer = $this->mock(BudgetLimitTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
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
            'budget_id' => $budget->id,
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
            'amount'    => 1,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('findNull')->andReturn($budget)->once();
        $repository->shouldReceive('storeBudgetLimit')->andReturn($budgetLimit)->once();


        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->post(route('api.v1.budget_limits.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new budget limit, but give error
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testStoreBadBudget(): void
    {
        $data
            = [
            'budget_id' => '1',
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
            'amount'    => 1,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('findNull')->andReturn(null)->once();
        $transformer = $this->mock(BudgetLimitTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->post(route('api.v1.budget_limits.store'), $data);
        $response->assertStatus(500);
        $response->assertSee('Unknown budget.');
    }

    /**
     * Test update of budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testUpdate(): void
    {
        $transformer = $this->mock(BudgetLimitTransformer::class);
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
            'budget_id' => $budget->id,
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
            'amount'    => 2,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('updateBudgetLimit')->andReturn($budgetLimit)->once();
        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->put(route('api.v1.budget_limits.update', [$budgetLimit->id]), $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }
}
