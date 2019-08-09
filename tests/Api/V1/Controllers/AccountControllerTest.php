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
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
     * Opening balance without date.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
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
            'name'            => 'Some new asset account #' . $this->randomInt(),
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
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testShow(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->mock(AccountTransformer::class);
        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $transformer->shouldReceive('setParameters')->atLeast()->once();
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(
            [
                'id'         => 1,
                'attributes' => [
                    'name' => 'Account',
                ],
            ]);
        $transformer->shouldReceive('setCurrentScope')->atLeast()->once();
        $transformer->shouldReceive('getDefaultIncludes')->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->atLeast()->once()->andReturn([]);


        // getAccountType

        $account  = $this->getRandomAsset();
        $response = $this->get(route('api.v1.accounts.show', [$account->id]));
        $response->assertStatus(200);
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountStoreRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
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
            'name'                 => 'Some new liability account #' . $this->randomInt(),
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
     * @covers \FireflyIII\Api\V1\Requests\Request
     * @throws Exception
     */
    public function testStoreNoCreditCardData(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);
        $this->mock(AccountTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();


        // data to submit
        $data = [
            'name'         => 'Some new asset account #' . $this->randomInt(),
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
     * @covers \FireflyIII\Api\V1\Requests\Request
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
            'name'              => 'Some new asset account #' . $this->randomInt(),
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
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testStoreNotUnique(): void
    {
        // mock repositories
        $repository = $this->mock(AccountRepositoryInterface::class);

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
     * @covers \FireflyIII\Api\V1\Requests\Request
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
            'name'         => 'Some new asset account #' . $this->randomInt(),
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
     * @covers \FireflyIII\Api\V1\Requests\Request
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
            'name'          => 'Some new asset account #' . $this->randomInt(),
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
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountUpdateRequest
     * @covers \FireflyIII\Api\V1\Requests\Request
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository  = $this->mock(AccountRepositoryInterface::class);
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
     * @covers \FireflyIII\Api\V1\Requests\Request
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
     * @covers \FireflyIII\Api\V1\Requests\Request
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
