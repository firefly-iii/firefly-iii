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


use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\BudgetLimitTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetLimitTransformer::class);


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
        $response = $this->delete(route('api.v1.budget_limits.delete', $budgetLimit->id));
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
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetLimitTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection);

        // call API
        $params   = ['budget_id' => $budget->id,];
        $response = $this->get(route('api.v1.budget_limits.index') . '?' . http_build_query($params));
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
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetLimitTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn(null);
        $repository->shouldReceive('getAllBudgetLimits')->once()->andReturn(new Collection);

        // call API
        $params   = [
            'start' => '2018-01-01',
            'end'   => '2018-01-31',
        ];
        $uri      = route('api.v1.budget_limits.index') . '?' . http_build_query($params);
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
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetLimitTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn(new Collection);

        // call API
        $params   = [
            'budget_id' => $budget->id,
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
        ];
        $response = $this->get(route('api.v1.budget_limits.index') . '?' . http_build_query($params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testShow(): void
    {
        // mock stuff:
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(BudgetLimitTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

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


        $response = $this->get(route('api.v1.budget_limits.show', $budgetLimit->id));
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
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
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
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     */
    public function testTransactionsBasic(): void
    {
        $budgetLimit        = BudgetLimit::first();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(TransactionTransformer::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $billRepos->shouldReceive('setUser');
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $budgetRepos->shouldReceive('setUser');
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        // mock some calls:

        // test API
        $response = $this->get(route('api.v1.budget_limits.transactions', [$budgetLimit->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Test update of budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetLimitController
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
     */
    public function testUpdate(): void
    {
        $transformer= $this->mock(BudgetLimitTransformer::class);
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
        $response = $this->put('/api/v1/budgets/limits/' . $budgetLimit->id, $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }
}
