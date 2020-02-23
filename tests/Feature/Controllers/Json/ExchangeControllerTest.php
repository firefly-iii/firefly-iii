<?php
/**
 * ExchangeControllerTest.php
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

namespace Tests\Feature\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Services\Currency\ExchangeRateInterface;
use Log;
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
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\ExchangeController
     */
    public function testGetRate(): void
    {
        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);

        $this->mockDefaultSession();

        $date = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $rate = $this->getRandomCer();
        $repository->shouldReceive('getExchangeRate')->andReturn($rate);

        $this->be($this->user());
        $response = $this->get(route('json.rate', ['EUR', 'USD', '20170101']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\ExchangeController
     */
    public function testGetRateAmount(): void
    {
        $this->mockDefaultSession();

        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $rate         = $this->getRandomCer();
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $repository->shouldReceive('getExchangeRate')->andReturn($rate);

        $this->be($this->user());
        $response = $this->get(route('json.rate', ['EUR', 'USD', '20170101']) . '?amount=10');
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\ExchangeController
     */
    public function testGetRateNull(): void
    {
        $this->mockDefaultSession();

        $repository   = $this->mock(CurrencyRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $rate = $this->getRandomCer();
        $repository->shouldReceive('getExchangeRate')->andReturnNull();
        $interface = $this->mock(ExchangeRateInterface::class);
        $interface->shouldReceive('setUser')->once();
        $interface->shouldReceive('getRate')->andReturn($rate);

        $this->be($this->user());
        $response = $this->get(route('json.rate', ['EUR', 'USD', '20170101']));
        $response->assertStatus(200);
    }
}
