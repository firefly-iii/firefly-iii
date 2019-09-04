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
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetLimitRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\BudgetTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class BudgetControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * Store a new budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testStore(): void
    {
        /** @var Budget $budget */
        $budget = new Budget;

        // mock stuff:
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetTransformer::class);
        $blRepository = $this->mock(BudgetLimitRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $blRepository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn($budget);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // data to submit
        $data = [
            'name' => 'Some budget',
        ];

        // test API
        $response = $this->post(route('api.v1.budgets.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'budgets', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testStoreBudgetLimit(): void
    {
        $budget      = $this->user()->budgets()->first();
        $budgetLimit = new BudgetLimit;
        $data
                     = [
            'budget_id' => $budget->id,
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
            'amount'    => 1,
        ];
        // mock stuff:
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $blRepository = $this->mock(BudgetLimitRepositoryInterface::class);
        $transformer  = $this->mock(BudgetLimitTransformer::class);

        $blRepository->shouldReceive('storeBudgetLimit')->andReturn($budgetLimit)->once();
        $blRepository->shouldReceive('setUser')->atLeast()->once();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('storeBudgetLimit')->andReturn($budgetLimit);

        // call API
        $response = $this->post(route('api.v1.budgets.store_budget_limit', [$budget->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertSee('budget_limits');
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetTransformer::class);
        $blRepository = $this->mock(BudgetLimitRepositoryInterface::class);
        /** @var Budget $budget */
        $budget = $this->user()->budgets()->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $blRepository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once()->andReturn(new Budget);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // data to submit
        $data = [
            'name'   => 'Some new budget',
            'active' => '1',
        ];

        // test API
        $response = $this->put(route('api.v1.budgets.update', $budget->id), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'budgets', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

}
