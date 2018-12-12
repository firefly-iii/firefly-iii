<?php
/**
 * CategoryControllerTest.php
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
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class CategoryControllerTest
 */
class CategoryControllerTest extends TestCase
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
     * Delete a category.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(CategoryRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get category:
        $category = $this->user()->categories()->first();

        // call API
        $response = $this->delete('/api/v1/categories/' . $category->id);
        $response->assertStatus(204);
    }

    /**
     * Show all categories
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     */
    public function testIndex(): void
    {
        $categories = $this->user()->categories()->get();
        // mock stuff:
        $repository = $this->mock(CategoryRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getCategories')->once()->andReturn($categories);

        // call API
        $response = $this->get('/api/v1/categories');
        $response->assertStatus(200);
        $response->assertSee($categories->first()->name);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show a single category.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     */
    public function testShow(): void
    {
        $category = $this->user()->categories()->first();
        // mock stuff:
        $repository = $this->mock(CategoryRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // call API
        $response = $this->get('/api/v1/categories/' . $category->id);
        $response->assertStatus(200);
        $response->assertSee($category->name);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a new category.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     * @covers \FireflyIII\Api\V1\Requests\CategoryRequest
     */
    public function testStore(): void
    {
        /** @var Category $category */
        $category = $this->user()->categories()->first();

        // mock stuff:
        $repository = $this->mock(CategoryRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->once()->andReturn($category);

        // data to submit
        $data = [
            'name'   => 'Some category',
            'active' => '1',
        ];

        // test API
        $response = $this->post('/api/v1/categories', $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'categories', 'links' => true],]);
        $response->assertSee($category->name); // the amount
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     */
    public function testTransactionsBasic(): void
    {
        $category           = $this->user()->categories()->first();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos      = $this->mock(CategoryRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);

        $categoryRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser');
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setCategory')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get(route('api.v1.categories.transactions', [$category->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     */
    public function testTransactionsRange(): void
    {
        $category           = $this->user()->categories()->first();
        $categoryRepos      = $this->mock(CategoryRepositoryInterface::class);
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);

        $categoryRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('setUser');
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setCategory')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);

        $response = $this->get(
            route('api.v1.categories.transactions', [$category->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31'])
        );
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a category.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CategoryController
     * @covers \FireflyIII\Api\V1\Requests\CategoryRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository = $this->mock(CategoryRepositoryInterface::class);

        /** @var Category $category */
        $category = $this->user()->categories()->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once()->andReturn($category);

        // data to submit
        $data = [
            'name'   => 'Some new category',
            'active' => '1',
        ];

        // test API
        $response = $this->put('/api/v1/categories/' . $category->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'categories', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($category->name);
    }

}
