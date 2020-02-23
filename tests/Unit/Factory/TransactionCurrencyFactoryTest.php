<?php
/**
 * TransactionCurrencyFactoryTest.php
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

namespace Tests\Unit\Factory;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\TransactionCurrency;
use Log;
use Tests\TestCase;

/**
 * Class TransactionCurrencyFactoryTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionCurrencyFactoryTest extends TestCase
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
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testCreate(): void
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->create(['name' => 'OK', 'code' => 'XXA', 'symbol' => 'Z', 'decimal_places' => 2, 'enabled' => true]);
        $this->assertNotNull($result);
        $this->assertEquals('XXA', $result->code);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testCreateEmpty(): void
    {
        $this->expectException(FireflyException::class);
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        Log::warning('The following error is part of a test.');
        $result  = $factory->create(['name' => null, 'code' => null, 'symbol' => null, 'decimal_places' => null, 'enabled' => true]);
        $this->assertNull($result);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByBadCode(): void
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $this->assertNull($factory->find(null, 'BAD CODE'));

    }

    /**
     * submit ID = 1000
     *
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByBadID(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->whereNull('deleted_at')->first();
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->find(1000, $currency->code);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByCode(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->whereNull('deleted_at')->first();
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->find(null, $currency->code);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByID(): void
    {
        $currency = TransactionCurrency::inRandomOrder()->whereNull('deleted_at')->first();
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->find($currency->id, null);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindNull(): void
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $this->assertNull($factory->find(null, null));
    }

}
