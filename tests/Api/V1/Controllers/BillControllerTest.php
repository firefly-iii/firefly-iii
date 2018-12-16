<?php
/**
 * BillControllerTest.php
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
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\AttachmentTransformer;
use FireflyIII\Transformers\BillTransformer;
use FireflyIII\Transformers\RuleTransformer;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class BillControllerTest
 */
class BillControllerTest extends TestCase
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
     * List all attachments.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testAttachments(): void
    {
        // create stuff
        $bill = $this->user()->bills()->first();

        // mock stuff:
        $repository    = $this->mock(AttachmentRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $billRepos     = $this->mock(BillRepositoryInterface::class);
        $transformer   = $this->mock(AttachmentTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // mock calls:
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('getAttachments')->once()->andReturn(new Collection);

        // test API
        $response = $this->get(route('api.v1.bills.attachments', [$bill->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Send delete
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get bill:
        $bill = $this->user()->bills()->first();

        // call API
        $response = $this->delete(route('api.v1.bills.delete', [$bill->id]));
        $response->assertStatus(204);
    }

    /**
     * Show all bills
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testIndex(): void
    {
        // create stuff
        $paginator   = new LengthAwarePaginator(new Collection, 0, 50, 1);
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getPaginator')->withAnyArgs()->andReturn($paginator)->once();

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();

        // test API
        $response = $this->get(route('api.v1.bills.index'));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testRules(): void
    {
        $bill        = $this->user()->bills()->first();
        $billRepos   = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(RuleTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $billRepos->shouldReceive('setUser')->atLeast()->once();
        $billRepos->shouldReceive('getRulesForBill')->atLeast()->once()->andReturn(new Collection);

        // call API
        $response = $this->get(route('api.v1.bills.rules', [$bill->id]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show one bill
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testShow(): void
    {
        // create stuff
        $bill        = $this->user()->bills()->first();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser');
        // test API
        $response = $this->get(route('api.v1.bills.show', [$bill->id]));
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'bills',
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store with minimum amount more than maximum amount
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @covers \FireflyIII\Api\V1\Requests\BillRequest
     */
    public function testStoreMinOverMax(): void
    {
        // create stuff
        $bill        = $this->user()->bills()->first();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . random_int(1, 10000),
            'match'       => 'some,words,' . random_int(1, 10000),
            'amount_min'  => '66.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'currency_id' => 1,
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,

        ];

        // test API
        $response = $this->post(route('api.v1.bills.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'amount_min' => ['The minimum amount cannot be larger than the maximum amount.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Store a valid bill
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @covers \FireflyIII\Api\V1\Requests\BillRequest
     */
    public function testStoreValid(): void
    {
        // create stuff
        $bill        = $this->user()->bills()->first();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . random_int(1, 10000),
            'match'       => 'some,words,' . random_int(1, 10000),
            'amount_min'  => '12.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,
            'currency_id' => 1,

        ];

        // test API
        $response = $this->post(route('api.v1.bills.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testTransactionsBasic(): void
    {
        $bill               = $this->user()->bills()->first();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos       = $this->mock(AccountRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $transformer        = $this->mock(TransactionTransformer::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $billRepos->shouldReceive('setUser');
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setBills')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();


        // mock some calls:

        // test API
        $response = $this->get(route('api.v1.bills.transactions', [$bill->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testTransactionsRange(): void
    {
        $bill               = $this->user()->bills()->first();
        $repository         = $this->mock(JournalRepositoryInterface::class);
        $collector          = $this->mock(TransactionCollectorInterface::class);
        $currencyRepository = $this->mock(CurrencyRepositoryInterface::class);
        $billRepos          = $this->mock(BillRepositoryInterface::class);
        $transformer        = $this->mock(TransactionTransformer::class);
        $paginator          = new LengthAwarePaginator(new Collection, 0, 50);
        $billRepos->shouldReceive('setUser');
        $repository->shouldReceive('setUser');
        $currencyRepository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setBills')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();


        $collector->shouldReceive('getPaginatedTransactions')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get(route('api.v1.bills.transactions', [$bill->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31']));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a valid bill.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @covers \FireflyIII\Api\V1\Requests\BillRequest
     */
    public function testUpdateValid(): void
    {
        // create stuff
        $bill        = $this->user()->bills()->first();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('update')->andReturn($bill);
        // data to submit:
        $data = [
            'name'        => 'New bill #' . random_int(1, 10000),
            'match'       => 'some,words,' . random_int(1, 10000),
            'amount_min'  => '12.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,
            'currency_id' => 1,
        ];

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // test API
        $response = $this->put(route('api.v1.bills.update', [$bill->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

}
