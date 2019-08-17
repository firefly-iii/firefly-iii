<?php
/**
 * BillTransformerTest.php
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

namespace Tests\Unit\Transformers;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class BillTransformerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BillTransformerTest extends TestCase
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
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\BillTransformer
     */
    public function testBasic(): void
    {
        $repository = $this->mock(BillRepositoryInterface::class);
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getNoteText')->atLeast()->once()->andReturn('');


        /** @var Bill $bill */
        $bill        = Bill::first();
        $transformer = app(BillTransformer::class);
        $transformer->setParameters(new ParameterBag);
        $result = $transformer->transform($bill);

        // assert fields.
        $this->assertEquals($bill->name, $result['name']);
        $this->assertEquals($bill->transactionCurrency->decimal_places, $result['currency_decimal_places']);
        $this->assertEquals($bill->active, $result['active']);
        $this->assertNull($result['notes']);
    }

    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\BillTransformer
     */
    public function testWithDates(): void
    {
        $repository = $this->mock(BillRepositoryInterface::class);
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getNoteText')->atLeast()->once()->andReturn('');

        // repos should also receive call for dates:
        $list = new Collection(
            [new Carbon('2018-01-02'), new Carbon('2018-01-09'), new Carbon('2018-01-16'),
             new Carbon('2018-01-21'), new Carbon('2018-01-30'),
            ]
        );
        $repository->shouldReceive('getPaidDatesInRange')->atLeast()->once()->andReturn($list);

        $parameters = new ParameterBag;
        $parameters->set('start', new Carbon('2018-01-01'));
        $parameters->set('end', new Carbon('2018-01-31'));

        /** @var Bill $bill */
        $bill        = Bill::first();
        $transformer = app(BillTransformer::class);
        $transformer->setParameters($parameters);
        $result = $transformer->transform($bill);

        // assert fields.
        $this->assertEquals($bill->name, $result['name']);
        $this->assertEquals($bill->transactionCurrency->decimal_places, $result['currency_decimal_places']);
        $this->assertEquals($bill->active, $result['active']);
        $this->assertNull($result['notes']);

        $this->assertEquals('2018-03-01', $result['next_expected_match']);
        $this->assertEquals(['2018-01-01'], $result['pay_dates']);
        $this->assertEquals(['2018-01-02', '2018-01-09', '2018-01-16', '2018-01-21', '2018-01-30',], $result['paid_dates']);
    }

}
