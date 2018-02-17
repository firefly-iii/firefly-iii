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
use FireflyIII\Models\Note;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Transformers\BillTransformer;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Tests\TestCase;

/**
 * Class BillTransformerTest
 */
class BillTransformerTest extends TestCase
{
    /**
     * Basic coverage
     *
     * @covers \FireflyIII\Transformers\BillTransformer::transform
     * @covers \FireflyIII\Transformers\BillTransformer::paidData
     * @covers \FireflyIII\Transformers\BillTransformer::payDates
     */
    public function testBasic()
    {

        $bill        = Bill::create(
            [
                'user_id'     => $this->user()->id,
                'name'        => 'Some bill ' . rand(1, 10000),
                'match'       => 'word,' . rand(1, 10000),
                'amount_min'  => 12.34,
                'amount_max'  => 45.67,
                'date'        => '2018-01-02',
                'repeat_freq' => 'weekly',
                'skip'        => 0,
                'active'      => 1,
            ]
        );
        $transformer = new BillTransformer(new ParameterBag);
        $result      = $transformer->transform($bill);

        $this->assertEquals($bill->name, $result['name']);
        $this->assertTrue($result['active']);
    }

    /**
     * Basic coverage with a note.
     *
     * @covers \FireflyIII\Transformers\BillTransformer::transform
     */
    public function testNote()
    {

        $bill     = Bill::create(
            [
                'user_id'     => $this->user()->id,
                'name'        => 'Some bill ' . rand(1, 10000),
                'match'       => 'word,' . rand(1, 10000),
                'amount_min'  => 12.34,
                'amount_max'  => 45.67,
                'date'        => '2018-01-02',
                'repeat_freq' => 'weekly',
                'skip'        => 0,
                'active'      => 1,
            ]
        );
        $noteText = 'I are a note ' . rand(1, 10000);
        Note::create(
            [
                'noteable_id'   => $bill->id,
                'noteable_type' => Bill::class,
                'text'          => $noteText,
            ]
        );
        $transformer = new BillTransformer(new ParameterBag);
        $result      = $transformer->transform($bill);

        $this->assertEquals($bill->name, $result['name']);
        $this->assertEquals($noteText, $result['notes']);
        $this->assertTrue($result['active']);
    }

    /**
     * Coverage for dates.
     *
     * @covers \FireflyIII\Transformers\BillTransformer::transform
     * @covers \FireflyIII\Transformers\BillTransformer::paidData
     * @covers \FireflyIII\Transformers\BillTransformer::payDates
     * @covers \FireflyIII\Transformers\BillTransformer::lastPaidDate
     * @covers \FireflyIII\Transformers\BillTransformer::nextDateMatch
     */
    public function testWithDates()
    {
        // mock stuff
        $repository = $this->mock(BillRepositoryInterface::class);
        $repository->shouldReceive('setUser')->andReturnSelf();
        $repository->shouldReceive('getPaidDatesInRange')->andReturn(new Collection([new Carbon('2018-01-02')]));
        $bill       = Bill::create(
            [
                'user_id'     => $this->user()->id,
                'name'        => 'Some bill ' . rand(1, 10000),
                'match'       => 'word,' . rand(1, 10000),
                'amount_min'  => 12.34,
                'amount_max'  => 45.67,
                'date'        => '2018-01-02',
                'repeat_freq' => 'monthly',
                'skip'        => 0,
                'active'      => 1,
            ]
        );
        $parameters = new ParameterBag();
        $parameters->set('start', new Carbon('2018-01-01'));
        $parameters->set('end', new Carbon('2018-01-31'));
        $transformer = new BillTransformer($parameters);
        $result      = $transformer->transform($bill);

        $this->assertEquals($bill->name, $result['name']);
        $this->assertTrue($result['active']);
        $this->assertCount(1, $result['pay_dates']);
        $this->assertEquals('2018-01-02', $result['pay_dates'][0]);
        $this->assertCount(1, $result['paid_dates']);
        $this->assertEquals('2018-01-02', $result['paid_dates'][0]);
    }

}