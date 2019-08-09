<?php
/**
 * AvailableBudgetControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Api\V1\Controllers\Chart;


use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class AvailableBudgetControllerTest
 */
class AvailableBudgetControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\Chart\AvailableBudgetController
     */
    public function testOverview(): void
    {
        $availableBudget = $this->user()->availableBudgets()->first();
        $repository      = $this->mock(BudgetRepositoryInterface::class);

        // get data:
        $budget = $this->getBudget();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));
        $repository->shouldReceive('spentInPeriodMc')->atLeast()->once()->
        andReturn(
            [
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'EUR',
                    'currency_symbol'         => 'x',
                    'currency_decimal_places' => 2,
                    'amount'                  => 321.21,
                ],
            ]
        );

        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.ab.overview', [$availableBudget->id]) . '?'
                                 . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\Chart\AvailableBudgetController
     */
    public function testOverviewNothingLeft(): void
    {
        $availableBudget = $this->user()->availableBudgets()->first();
        $repository      = $this->mock(BudgetRepositoryInterface::class);

        // get data:
        $budget = $this->getBudget();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));
        $repository->shouldReceive('spentInPeriodMc')->atLeast()->once()->
        andReturn(
            [
                [
                    'currency_id'             => 1,
                    'currency_code'           => 'EUR',
                    'currency_symbol'         => 'x',
                    'currency_decimal_places' => 2,
                    'amount'                  => -3321.21,
                ],
            ]
        );

        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.ab.overview', [$availableBudget->id]) . '?'
                                 . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

}