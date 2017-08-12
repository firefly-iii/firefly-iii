<?php
/**
 * ExchangeControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Tests\Feature\Controllers\Json;


use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Tests\TestCase;

/**
 * Class ExchangeControllerTest
 *
 * @package Tests\Feature\Controllers
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExchangeControllerTest extends TestCase
{

    /**
     * @covers \FireflyIII\Http\Controllers\Json\ExchangeController::getRate
     */
    public function testGetRate()
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);

        $rate = factory(CurrencyExchangeRate::class)->make();
        $repository->shouldReceive('getExchangeRate')->andReturn($rate);

        $this->be($this->user());
        $response = $this->get(route('json.rate', ['EUR', 'USD', '20170101']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\ExchangeController::getRate
     */
    public function testGetRateAmount()
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $rate       = factory(CurrencyExchangeRate::class)->make();
        $repository->shouldReceive('getExchangeRate')->andReturn($rate);

        $this->be($this->user());
        $response = $this->get(route('json.rate', ['EUR', 'USD', '20170101']) . '?amount=10');
        $response->assertStatus(200);
    }
}
