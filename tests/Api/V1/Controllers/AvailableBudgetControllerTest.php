<?php
/**
 * AvailableBudgetControllerTest.php
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


use Amount;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Transformers\AvailableBudgetTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class AvailableBudgetControllerTest
 */
class AvailableBudgetControllerTest extends TestCase
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
     * Delete an available budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $transformer   = $this->mock(AvailableBudgetTransformer::class);
        $factory       = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('destroyAvailableBudget')->once()->andReturn(true);

        // get available budget:
        $availableBudget = $this->user()->availableBudgets()->first();

        // call API
        $response = $this->delete(route('api.v1.available_budgets.delete', [$availableBudget->id]));
        $response->assertStatus(204);
    }

    /**
     * Show all available budgets.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     */
    public function testIndex(): void
    {
        $availableBudgets = $this->user()->availableBudgets()->get();
        // mock stuff:
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $transformer   = $this->mock(AvailableBudgetTransformer::class);
        $factory       = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getAvailableBudgets')->once()->andReturn($availableBudgets);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // call API
        $response = $this->get(route('api.v1.available_budgets.index'));
        $response->assertStatus(200);
        $response->assertSee($availableBudgets->first()->id);
    }

    /**
     * Show one available budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     */
    public function testShow(): void
    {
        $availableBudget = $this->user()->availableBudgets()->first();
        // mock stuff:
        $repository    = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $transformer   = $this->mock(AvailableBudgetTransformer::class);
        $factory       = $this->mock(TransactionCurrencyFactory::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // call API
        $response = $this->get(route('api.v1.available_budgets.show', [$availableBudget->id]));
        $response->assertStatus(200);
        $response->assertSee($availableBudget->id);
    }

    /**
     * Store new available budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     * @covers \FireflyIII\Api\V1\Requests\AvailableBudgetRequest
     */
    public function testStore(): void
    {
        $repository         = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $transformer        = $this->mock(AvailableBudgetTransformer::class);
        $factory            = $this->mock(TransactionCurrencyFactory::class);
        $availableBudget    = new AvailableBudget;

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        $factory->shouldReceive('find')->withArgs([2, ''])->once()->andReturn(TransactionCurrency::find(2));

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setAvailableBudget')->once()->andReturn($availableBudget);

        // data to submit
        $data = [
            'currency_id' => '2',
            'amount'      => '100',
            'start'       => '2018-01-01',
            'end'         => '2018-01-31',
        ];


        // test API
        $response = $this->post(route('api.v1.available_budgets.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'available_budgets', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new available budget without a valid currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     * @covers \FireflyIII\Api\V1\Requests\AvailableBudgetRequest
     */
    public function testStoreNoCurrencyAtAll(): void
    {
        // mock stuff:
        $repository         = $this->mock(BudgetRepositoryInterface::class);
        $transformer        = $this->mock(AvailableBudgetTransformer::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $factory            = $this->mock(TransactionCurrencyFactory::class);
        $availableBudget    = new AvailableBudget;

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        $factory->shouldReceive('find')->withArgs([0, ''])->once()->andReturnNull();

        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn(TransactionCurrency::find(5));

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('setAvailableBudget')->once()->andReturn($availableBudget);

        // data to submit
        $data = [
            'amount' => '100',
            'start'  => '2018-01-01',
            'end'    => '2018-01-31',
        ];


        // test API
        $response = $this->post(route('api.v1.available_budgets.store'), $data);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new available budget without a valid currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     * @covers \FireflyIII\Api\V1\Requests\AvailableBudgetRequest
     */
    public function testStoreNoCurrencyId(): void
    {
        /** @var AvailableBudget $availableBudget */
        $availableBudget = $this->user()->availableBudgets()->first();

        // mock stuff:
        $repository  = $this->mock(BudgetRepositoryInterface::class);
        $transformer = $this->mock(AvailableBudgetTransformer::class);
        $factory     = $this->mock(TransactionCurrencyFactory::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $factory->shouldReceive('find')->withArgs([0, 'EUR'])->once()->andReturnNull();
        Amount::shouldReceive('getDefaultCurrency')->once()->andReturn(TransactionCurrency::find(5));

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setAvailableBudget')->once()->andReturn($availableBudget);

        // data to submit
        $data = [
            'currency_code' => 'EUR',
            'amount'        => '100',
            'start'         => '2018-01-01',
            'end'           => '2018-01-31',
        ];


        // test API
        $response = $this->post(route('api.v1.available_budgets.store'), $data);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'available_budgets', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update available budget.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AvailableBudgetController
     * @covers \FireflyIII\Api\V1\Requests\AvailableBudgetRequest
     *
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository         = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $transformer        = $this->mock(AvailableBudgetTransformer::class);
        $factory            = $this->mock(TransactionCurrencyFactory::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $factory->shouldReceive('find')->withArgs([1, ''])->once()->andReturnNull();

        /** @var AvailableBudget $availableBudget */
        $availableBudget = $this->user()->availableBudgets()->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('updateAvailableBudget')->once()->andReturn($availableBudget);
        $currencyRepository->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));

        // data to submit
        $data = [
            'currency_id' => '1',
            'amount'      => '100',
            'start'       => '2018-01-01',
            'end'         => '2018-01-31',
        ];

        // test API
        $response = $this->put(route('api.v1.available_budgets.update', $availableBudget->id), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'available_budgets', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


}
