<?php
/**
 * AccountControllerTest.php
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

use Exception;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\Transformers\PiggyBankTransformer;
use FireflyIII\Transformers\TransactionGroupTransformer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
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
     * Destroy account over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('destroy')->atLeast()->once()->andReturn(true);

        // get account:
        $account = $this->getRandomAsset();

        // call API
        $response = $this->delete(route('api.v1.accounts.delete', [$account->id]));
        $response->assertStatus(204);

    }

    /**
     * Test the list of accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testIndex(): void
    {
        // create stuff
        $accounts = factory(Account::class, 10)->create();

        // mock stuff:
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getAccountsByType')->withAnyArgs()->andReturn($accounts)->once();

        // test API
        $response = $this->get('/api/v1/accounts');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertSee('type=all'); // default returns this.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Test the list of piggy banks.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testPiggyBanks(): void
    {
        // mock stuff:
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $piggyRepos  = $this->mock(PiggyBankRepositoryInterface::class);
        $transformer = $this->mock(PiggyBankTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // get piggies for this user.
        $piggies = factory(PiggyBank::class, 10)->create();
        $asset   = $this->getRandomAsset();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        $repository->shouldReceive('getPiggyBanks')->andReturn($piggies)->atLeast()->once();

        // test API
        $response = $this->get(route('api.v1.accounts.piggy_banks', [$asset->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(
            ['meta' => ['pagination' => ['total'       => $piggies->count(), 'count' => $piggies->count(), 'per_page' => true, 'current_page' => 1,
                                         'total_pages' => 1]],]
        );
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertSee('page=1'); // default returns this.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show an account.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */

    public function testShow(): void
    {
        // mock stuff:
        $account    = $this->getRandomAsset();
        $repository = $this->mock(AccountRepositoryInterface::class);

        $transformer = $this->mock(AccountTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();


        // test API
        $response = $this->get(route('api.v1.accounts.show', [$account->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Opening balance without date.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @throws Exception
     */
    public function testStoreInvalidBalance(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);


        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();


        // data to submit
        $data = [
            'name'            => 'Some new asset account #' . random_int(1, 10000),
            'type'            => 'asset',
            'account_role'    => 'defaultAsset',
            'opening_balance' => '123.45',
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'opening_balance_date' => ['The opening balance date field is required when opening balance is present.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @throws Exception
     */
    public function testStoreLiability(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);

        $transformer = $this->mock(AccountTransformer::class);
        $account     = $this->getRandomAsset();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->atLeast()->once()->andReturn($account);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // data to submit
        $data = [
            'name'                 => 'Some new liability account #' . random_int(1, 10000),
            'type'                 => 'liability',
            'liability_amount'     => '10000',
            'liability_start_date' => '2016-01-01',
            'liability_type'       => 'mortgage',
            'active'               => true,
            'interest'             => '1',
            'interest_period'      => 'daily',
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);

        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * CC type present when account is a credit card.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     */
    public function testStoreNoCreditCardData(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();


        // data to submit
        $data = [
            'name'         => 'Some new asset account #' . random_int(1, 10000),
            'type'         => 'asset',
            'account_role' => 'ccAsset',
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'credit_card_type'     => ['The credit card type field is required when account role is ccAsset.'],
                    'monthly_payment_date' => ['The monthly payment date field is required when account role is ccAsset.'],

                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * No currency information (is allowed).
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @throws Exception
     */
    public function testStoreNoCurrencyInfo(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->once()->andReturn(new Account);

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'type'              => 'asset',
            'account_role'      => 'defaultAsset',
            'include_net_worth' => false,
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Name already in use.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     */
    public function testStoreNotUnique(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();


        $account = $this->getRandomAsset();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'name' => ['This account name is already in use.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @throws Exception
     */
    public function testStoreValid(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);
        $account     = $this->getRandomAsset();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->atLeast()->once()->andReturn($account);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // data to submit
        $data = [
            'name'         => 'Some new asset account #' . random_int(1, 10000),
            'type'         => 'asset',
            'account_role' => 'defaultAsset',
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @throws Exception
     */
    public function testStoreWithCurrencyCode(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);
        $account     = $this->getRandomAsset();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        $repository->shouldReceive('store')->atLeast()->once()->andReturn($account);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // data to submit
        $data = [
            'name'          => 'Some new asset account #' . random_int(1, 10000),
            'currency_code' => 'EUR',
            'type'          => 'asset',
            'account_role'  => 'defaultAsset',
        ];

        // test API
        $response = $this->post(route('api.v1.accounts.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show transactions.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testTransactionsBasic(): void
    {
        // default mocks
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $transformer  = $this->mock(TransactionGroupTransformer::class);

        // objects
        $paginator = new LengthAwarePaginator(new Collection, 0, 50);

        // calls to account repos.
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock collector:
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('withAPIInformation')->andReturnSelf();
        $collector->shouldReceive('setLimit')->withArgs([50])->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->andReturn($paginator);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $asset = $this->getRandomAsset();

        // test API
        $response = $this->get(route('api.v1.accounts.transactions', [$asset->id]));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show transactions but submit a limit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testTransactionsLimit(): void
    {
        // default mocks
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $transformer  = $this->mock(TransactionGroupTransformer::class);

        // objects
        $paginator = new LengthAwarePaginator(new Collection, 0, 50);

        // calls to account repos.
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock collector:
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('withAPIInformation')->andReturnSelf();
        $collector->shouldReceive('setLimit')->withArgs([10])->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->andReturn($paginator);

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $asset = $this->getRandomAsset();

        // test API
        $response = $this->get(route('api.v1.accounts.transactions', [$asset->id]) . '?limit=10');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testTransactionsRange(): void
    {
        // default mocks
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(GroupCollectorInterface::class);
        $transformer  = $this->mock(TransactionGroupTransformer::class);

        // objects
        $paginator = new LengthAwarePaginator(new Collection, 0, 50);

        // calls to account repos.
        $accountRepos->shouldReceive('setUser')->atLeast()->once();

        // mock collector:
        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('withAPIInformation')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedGroups')->andReturn($paginator);
        $collector->shouldReceive('setRange')->andReturnSelf();

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $asset = $this->getRandomAsset();

        // test API
        $response = $this->get(route('api.v1.accounts.transactions', [$asset->id]) . '?' . http_build_query(['start' => '2018-01-01', 'end' => '2018-01-31']));
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 0, 'count' => 0, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountUpdateRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);

        $transformer = $this->mock(AccountTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        $repository->shouldReceive('update')->atLeast()->once();

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        $account = $this->getRandomAsset();
        // data to submit
        $data = [
            'active'            => true,
            'include_net_worth' => true,
            'name'              => $account->name,
            'type'              => 'asset',
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->put(route('api.v1.accounts.update', [$account->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountUpdateRequest
     */
    public function testUpdateCurrencyCode(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);

        $transformer = $this->mock(AccountTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        $repository->shouldReceive('update')->atLeast()->once();

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        $account = $this->getRandomAsset();
        // data to submit
        $data = [
            'name'          => $account->name,
            'type'          => 'asset',
            'currency_code' => 'EUR',
            'account_role'  => 'defaultAsset',
        ];

        // test API
        $response = $this->put(route('api.v1.accounts.update', [$account->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a liability
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountUpdateRequest
     */
    public function testUpdateLiability(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);

        $transformer = $this->mock(AccountTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();

        $repository->shouldReceive('update')->atLeast()->once();

        // mock calls to transformer:
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);


        $account = $this->getRandomAsset();
        // data to submit
        $data = [
            'active'               => true,
            'include_net_worth'    => true,
            'name'                 => $account->name,
            'type'                 => 'liability',
            'liability_type'       => 'loan',
            'liability_amount'     => '100',
            'interest'             => '1',
            'interest_period'      => 'yearly',
            'liability_start_date' => '2019-01-01',
            'account_role'         => 'defaultAsset',
        ];

        // test API
        $response = $this->put(route('api.v1.accounts.update', [$account->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }


}
