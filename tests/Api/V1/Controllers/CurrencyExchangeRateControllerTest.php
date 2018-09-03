<?php
/**
 * CurrencyExchangeRateControllerTest.php
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


use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Currency\ExchangeRateInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 *
 * Class CurrencyExchangeRateControllerTest
 */
class CurrencyExchangeRateControllerTest extends TestCase
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
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyExchangeRateController
     */
    public function testIndex(): void
    {
        // mock repository
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $service    = $this->mock(ExchangeRateInterface::class);

        $rate                   = new CurrencyExchangeRate();
        $rate->date             = new Carbon();
        $rate->updated_at       = new Carbon();
        $rate->created_at       = new Carbon();
        $rate->rate             = '0.5';
        $rate->to_currency_id   = 1;
        $rate->from_currency_id = 2;

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByCodeNull')->withArgs(['EUR'])->andReturn(TransactionCurrency::whereCode('EUR')->first())->once();
        $repository->shouldReceive('findByCodeNull')->withArgs(['USD'])->andReturn(TransactionCurrency::whereCode('USD')->first())->once();
        $repository->shouldReceive('getExchangeRate')->andReturn(null)->once();
        $service->shouldReceive('setUser')->once();
        $service->shouldReceive('getRate')->once()->andReturn($rate);

        // test API
        $params   = [
            'from' => 'EUR',
            'to'   => 'USD',
            'date' => '2018-01-01',
        ];
        $response = $this->get('/api/v1/cer?' . http_build_query($params));
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type'       => 'currency_exchange_rates',
                'id'         => '0',
                'attributes' => [
                    'rate' => 0.5,
                ],
                'links'      => [

                    [
                        'rel' => 'self',
                        'uri' => '/currency_exchange_rates/',
                    ],
                ],
            ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyExchangeRateController
     */
    public function testIndexBadDestination(): void
    {
        // mock repository
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $service    = $this->mock(ExchangeRateInterface::class);

        $rate                   = new CurrencyExchangeRate();
        $rate->date             = new Carbon();
        $rate->updated_at       = new Carbon();
        $rate->created_at       = new Carbon();
        $rate->rate             = '0.5';
        $rate->to_currency_id   = 1;
        $rate->from_currency_id = 2;

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByCodeNull')->withArgs(['EUR'])->andReturn(TransactionCurrency::whereCode('USD')->first())->once();
        $repository->shouldReceive('findByCodeNull')->withArgs(['USD'])->andReturn(null)->once();

        // test API
        $params   = [
            'from' => 'EUR',
            'to'   => 'USD',
            'date' => '2018-01-01',
        ];
        $response = $this->get('/api/v1/cer?' . http_build_query($params), ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('Unknown destination currency.');
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\CurrencyExchangeRateController
     */
    public function testIndexBadSource(): void
    {
        // mock repository
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $service    = $this->mock(ExchangeRateInterface::class);

        $rate                   = new CurrencyExchangeRate();
        $rate->date             = new Carbon();
        $rate->updated_at       = new Carbon();
        $rate->created_at       = new Carbon();
        $rate->rate             = '0.5';
        $rate->to_currency_id   = 1;
        $rate->from_currency_id = 2;

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByCodeNull')->withArgs(['EUR'])->andReturn(null)->once();
        $repository->shouldReceive('findByCodeNull')->withArgs(['USD'])->andReturn(TransactionCurrency::whereCode('USD')->first())->once();

        // test API
        $params   = [
            'from' => 'EUR',
            'to'   => 'USD',
            'date' => '2018-01-01',
        ];
        $response = $this->get('/api/v1/cer?' . http_build_query($params), ['Accept' => 'application/json']);
        $response->assertStatus(500);
        $response->assertSee('Unknown source currency.');
        $response->assertHeader('Content-Type', 'application/json');
    }
}
