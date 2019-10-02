<?php
/**
 * AvailableBudgetControllerTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Api\V1\Controllers\Chart;


use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Budget\OperationsRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class AvailableBudgetControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
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
        $opsRepository   = $this->mock(OperationsRepositoryInterface::class);

        // get data:
        $budget = $this->getBudget();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $opsRepository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));
        $opsRepository->shouldReceive('spentInPeriodMc')->atLeast()->once()->andReturn(
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
        $response   = $this->get(
            route('api.v1.chart.ab.overview', [$availableBudget->id]) . '?'
            . http_build_query($parameters), ['Accept' => 'application/json']
        );
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\Chart\AvailableBudgetController
     */
    public function testOverviewNothingLeft(): void
    {
        $availableBudget = $this->user()->availableBudgets()->first();
        $repository      = $this->mock(BudgetRepositoryInterface::class);
        $opsRepository   = $this->mock(OperationsRepositoryInterface::class);
        // get data:
        $budget = $this->getBudget();

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $opsRepository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('getActiveBudgets')->atLeast()->once()->andReturn(new Collection([$budget]));
        $opsRepository->shouldReceive('spentInPeriodMc')->atLeast()->once()->andReturn(
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
        $response   = $this->get(
            route('api.v1.chart.ab.overview', [$availableBudget->id]) . '?'
            . http_build_query($parameters), ['Accept' => 'application/json']
        );
        $response->assertStatus(200);
    }

}
