<?php
/**
 * TransactionCurrenciesTest.php
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

namespace Tests\Unit\Import\Mapper;

use FireflyIII\Import\Mapper\TransactionCurrencies;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class TransactionCurrenciesTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransactionCurrenciesTest extends TestCase
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
     * @covers \FireflyIII\Import\Mapper\TransactionCurrencies
     */
    public function testGetMapBasic(): void
    {
        $one        = new TransactionCurrency;
        $one->id    = 9;
        $one->name  = 'Something';
        $one->code  = 'ABC';
        $two        = new TransactionCurrency;
        $two->id    = 11;
        $two->name  = 'Else';
        $two->code  = 'DEF';
        $collection = new Collection([$one, $two]);

        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturn($collection)->once();

        $mapper  = new TransactionCurrencies();
        $mapping = $mapper->getMap();
        $this->assertCount(3, $mapping);
        // assert this is what the result looks like:
        $result = [
            0  => (string)trans('import.map_do_not_map'),
            11 => 'Else (DEF)',
            9  => 'Something (ABC)',

        ];
        $this->assertEquals($result, $mapping);
    }

}
