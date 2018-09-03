<?php
/**
 * AmountFilterTest.php
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

namespace Tests\Unit\Helpers\Filter;


use FireflyIII\Helpers\Filter\AmountFilter;
use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 *
 * Class AmountFilterTest
 */
class AmountFilterTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Helpers\Filter\AmountFilter
     */
    public function testBasicPositive(): void
    {
        $count      = 0;
        $collection = new Collection;
        for ($i = 0; $i < 10; $i++) {
            $amount                          = random_int(-10, 10);
            $transaction                     = new Transaction;
            $transaction->transaction_amount = (string)$amount;
            if ($amount <= 0) {
                $count++;
            }
            $collection->push($transaction);
        }

        $filter = new AmountFilter(1);
        $result = $filter->filter($collection);
        $this->assertCount($count, $result);
    }
}