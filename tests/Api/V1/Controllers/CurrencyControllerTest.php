<?php
/**
 * CurrencyControllerTest.php
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


use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class CurrencyControllerTest
 */
class CurrencyControllerTest extends TestCase
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
     * Send delete
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        $userRepos->shouldReceive('hasRole')->once()->withArgs([Mockery::any(), 'owner'])->andReturn(true);
        $repository->shouldReceive('canDeleteCurrency')->once()->andReturn(true);

        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get a currency
        $currency = TransactionCurrency::first();

        // call API
        $response = $this->delete('/api/v1/currencies/' . $currency->id);
        $response->assertStatus(204);
    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testIndex(): void
    {
        $collection = TransactionCurrency::get();
        // mock stuff:
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('get')->withNoArgs()->andReturn($collection)->once();

        // test API
        $response = $this->get('/api/v1/currencies');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(
            [
                'meta' => [
                    'pagination' => [
                        'total'        => $collection->count(),
                        'count'        => $collection->count(),
                        'per_page'     => true, // depends on user preference.
                        'current_page' => 1,
                        'total_pages'  => 1,
                    ],
                ],
            ]
        );
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Test show of a currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testShow(): void
    {
        // create stuff
        $currency   = TransactionCurrency::first();
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // test API
        $response = $this->get('/api/v1/currencies/' . $currency->id);
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'currencies',
                'id'   => $currency->id,
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testStore(): void
    {

        $currency   = TransactionCurrency::first();
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($currency);

        // data to submit:
        $data = [
            'name'           => 'New currency',
            'code'           => 'ABC',
            'symbol'         => 'A',
            'decimal_places' => 2,
            'default'        => '0',
        ];

        // test API
        $response = $this->post('/api/v1/currencies', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($currency->name);
    }

    /**
     * Store new currency and make it default.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testStoreWithDefault(): void
    {
        $currency   = TransactionCurrency::first();
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        $preference       = new Preference;
        $preference->data = 'EUR';
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($currency);
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->once();
        Preferences::shouldReceive('mark')->once();
        Preferences::shouldReceive('lastActivity')->once();
        Preferences::shouldReceive('getForUser')->once()->andReturn($preference);

        // data to submit:
        $data = [
            'name'           => 'New currency',
            'code'           => 'ABC',
            'symbol'         => 'A',
            'decimal_places' => 2,
            'default'        => '1',
        ];

        // test API
        $response = $this->post('/api/v1/currencies', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($currency->name);
    }

    /**
     * Update currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testUpdate(): void
    {
        $currency   = TransactionCurrency::first();
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos  = $this->mock(UserRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('update')->andReturn($currency);

        // data to submit:
        $data = [
            'name'           => 'Updated currency',
            'code'           => 'ABC',
            'symbol'         => '$E',
            'decimal_places' => '2',
            'default'        => '0',
        ];

        // test API
        $response = $this->put('/api/v1/currencies/' . $currency->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($currency->name);
    }

    /**
     * Update currency and make default.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     * @covers \FireflyIII\Api\V1\Requests\CurrencyRequest
     */
    public function testUpdateWithDefault(): void
    {
        $currency         = TransactionCurrency::first();
        $repository       = $this->mock(CurrencyRepositoryInterface::class);
        $userRepos        = $this->mock(UserRepositoryInterface::class);
        $preference       = new Preference;
        $preference->data = 'EUR';

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('update')->andReturn($currency);
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->once();
        Preferences::shouldReceive('mark')->once();
        Preferences::shouldReceive('lastActivity')->once();
        Preferences::shouldReceive('getForUser')->once()->andReturn($preference);

        // data to submit:
        $data = [
            'name'           => 'Updated currency',
            'code'           => 'ABC',
            'symbol'         => '$E',
            'decimal_places' => '2',
            'default'        => '1',
        ];

        // test API
        $response = $this->put('/api/v1/currencies/' . $currency->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($currency->name);
    }
}
