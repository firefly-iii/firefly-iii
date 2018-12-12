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


use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Show all budgets
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testBudgetLimits(): void
    {
        $budget       = $this->user()->budgets()->first();
        $budgetLimits = BudgetLimit::get();
        $repository   = $this->mock(BudgetRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getBudgetLimits')->once()->andReturn($budgetLimits);

        // call API
        $response = $this->get(route('api.v1.budgets.budget_limits', [$budget->id]));
        $response->assertStatus(200);
        $response->assertSee('budget_limits');
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
     * Store new budget limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     * @covers \FireflyIII\Api\V1\Requests\BudgetLimitRequest
     */
    public function testStoreBudgetLimit(): void
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
            'budget_id' => $budget->id,
            'start'     => '2018-01-01',
            'end'       => '2018-01-31',
            'amount'    => 1,
        ];
        // mock stuff:
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('storeBudgetLimit')->andReturn($budgetLimit)->once();


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
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testTransactionsBasic(): void
    {
        $budget             = $this->user()->budgets()->first();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
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
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);

        // test API
        $response = $this->get(route('api.v1.budgets.transactions', [$budget->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BudgetController
     */
    public function testTransactionsRange(): void
    {
        $budget             = $this->user()->budgets()->first();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $budgetRepos        = $this->mock(BudgetRepositoryInterface::class);
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
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // test API
        $response = $this->get(route('api.v1.budgets.transactions', [$budget->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31']));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
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
