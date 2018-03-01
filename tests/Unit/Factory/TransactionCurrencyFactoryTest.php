<?php
/**
 * TransactionCurrencyFactoryTest.php
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

namespace Tests\Unit\Factory;


use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Models\TransactionCurrency;
use Tests\TestCase;

/**
 * Class TransactionCurrencyFactoryTest
 */
class TransactionCurrencyFactoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByBadCode()
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $this->assertNull($factory->find(null, 'BAD CODE'));

    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByCode()
    {
        $currency = TransactionCurrency::find(1);
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->find(null, $currency->code);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindByID()
    {
        $currency = TransactionCurrency::find(1);
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $result  = $factory->find($currency->id, null);
        $this->assertEquals($currency->id, $result->id);
    }

    /**
     * @covers \FireflyIII\Factory\TransactionCurrencyFactory
     */
    public function testFindNull()
    {
        /** @var TransactionCurrencyFactory $factory */
        $factory = app(TransactionCurrencyFactory::class);
        $this->assertNull($factory->find(null, null));
    }

}