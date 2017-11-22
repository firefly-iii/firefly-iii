<?php
/**
 * ExchangeControllerTest.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Tests\Feature\Controllers\Json;

use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Tests\TestCase;

/**
 * Class ExchangeControllerTest
 *
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
