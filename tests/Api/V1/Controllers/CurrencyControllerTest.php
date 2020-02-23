<?php
/**
 * CurrencyControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Api\V1\Controllers;



use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\CurrencyTransformer;
use Laravel\Passport\Passport;
use Log;
use Preferences;
use Tests\TestCase;
use Amount;
/**
 * Class CurrencyControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        Log::info(sprintf('Now in %s.', get_class($this)));

    }

    /**
     * Store new currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testStore(): void
    {

        $currency       = $this->getEuro();
        $repository     = $this->mock(CurrencyRepositoryInterface::class);
        $transformer    = $this->mock(CurrencyTransformer::class);
        $this->mock(UserRepositoryInterface::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock facades.
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($currency);
        Preferences::shouldReceive('mark');

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
            'enabled'        => '1',
        ];

        // test API
        $response = $this->post(route('api.v1.currencies.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store new currency and make it default.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testStoreWithDefault(): void
    {
        $currency       = $this->getEuro();
        $repository     = $this->mock(CurrencyRepositoryInterface::class);
        $transformer    = $this->mock(CurrencyTransformer::class);
        $userRepository = $this->mock(UserRepositoryInterface::class);

        // mock facades.
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($currency);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        $preference       = new Preference;
        $preference->data = 'EUR';
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($currency);
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->once();
        Preferences::shouldReceive('mark')->once();
        //Preferences::shouldReceive('lastActivity')->once();
        //Preferences::shouldReceive('getForUser')->once()->andReturn($preference);

        // data to submit:
        $data = [
            'name'           => 'New currency',
            'code'           => 'ABC',
            'symbol'         => 'A',
            'decimal_places' => 2,
            'default'        => '1',
            'enabled'        => '1',
        ];

        // test API
        $response = $this->post(route('api.v1.currencies.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update currency.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testUpdate(): void
    {
        $currency    = $this->getEuro();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);
        $this->mock(UserRepositoryInterface::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);
        Preferences::shouldReceive('mark');
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($currency);

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
            'enabled'        => '1',
        ];

        // test API
        $response = $this->put(route('api.v1.currencies.update', [$currency->code]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update currency and make default.
     *
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyController
     */
    public function testUpdateWithDefault(): void
    {
        $currency    = $this->getEuro();
        $repository  = $this->mock(CurrencyRepositoryInterface::class);
        $transformer = $this->mock(CurrencyTransformer::class);
        $this->mock(UserRepositoryInterface::class);
        $preference       = new Preference;
        $preference->data = 'EUR';

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('update')->andReturn($currency);
        Preferences::shouldReceive('set')->withArgs(['currencyPreference', 'EUR'])->once();
        Preferences::shouldReceive('mark')->once();
        //Preferences::shouldReceive('lastActivity')->once();
        Amount::shouldReceive('getDefaultCurrencyByUser')->atLeast()->once()->andReturn($currency);
        //Preferences::shouldReceive('getForUser')->once()->andReturn($preference);

        // data to submit:
        $data = [
            'name'           => 'Updated currency',
            'code'           => 'ABC',
            'symbol'         => '$E',
            'decimal_places' => '2',
            'default'        => '1',
            'enabled'        => '1',
        ];

        // test API
        $response = $this->put(route('api.v1.currencies.update', [$currency->code]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'currencies', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }
}
