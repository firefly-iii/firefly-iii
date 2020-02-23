<?php
/**
 * CurrencyMapperTest.php
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

namespace Tests\Unit\Support\Import\Routine\File;


use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Import\Routine\File\CurrencyMapper;
use Log;
use Tests\TestCase;

/**
 * Class CurrencyMapperTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CurrencyMapperTest extends TestCase
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
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testBasic(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->first();
        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->once()->withArgs([$currency->id])->andReturn($currency);
        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map($currency->id, []);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testBasicNotFound(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->first();
        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findNull')->once()->withArgs([$currency->id])->andReturn(null);

        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map($currency->id, []);
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testEmpty(): void
    {

        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();

        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map(null, []);
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testFindAndCreate(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->first();
        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findBySymbolNull')->withArgs([$currency->symbol])->andReturn(null)->once();
        $repository->shouldReceive('findByCodeNull')->withArgs([$currency->code])->andReturn(null)->once();
        $repository->shouldReceive('findByNameNull')->withArgs([$currency->name])->andReturn(null)->once();

        // nothing found, mapper will try to create it.
        $repository->shouldReceive('store')
                   ->withArgs([['code' => $currency->code, 'name' => $currency->name, 'symbol' => $currency->symbol, 'enabled' => true, 'decimal_places' => 2]])
                   ->once()->andReturn($currency);

        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map(null, ['name' => $currency->name, 'code' => $currency->code, 'enabled' => true, 'symbol' => $currency->symbol]);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testFindByCode(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->first();
        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByCodeNull')->withArgs([$currency->code])
                   ->andReturn($currency)->once();

        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map(null, ['code' => $currency->code]);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testFindByName(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->first();
        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findByNameNull')->withArgs([$currency->name])
                   ->andReturn($currency)->once();

        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map(null, ['name' => $currency->name]);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Support\Import\Routine\File\CurrencyMapper
     */
    public function testFindBySymbol(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->first();
        // mock data
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('findBySymbolNull')->withArgs([$currency->symbol])
                   ->andReturn($currency)->once();

        $mapper = new CurrencyMapper();
        $mapper->setUser($this->user());

        $result = $mapper->map(null, ['symbol' => $currency->symbol]);
        $this->assertEquals($currency->id, $result->id);
    }

}
