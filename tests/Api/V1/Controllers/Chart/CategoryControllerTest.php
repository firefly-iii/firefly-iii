<?php
/**
 * CategoryControllerTest.php
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


use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 */
class CategoryControllerTest extends TestCase
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
     * @covers \FireflyIII\Api\V1\Controllers\Chart\CategoryController
     */
    public function testOverview(): void
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);

        $spent = [
            2 => [
                'name'  => 'Some other category',
                'spent' => [
                    // earned in this currency.
                    1 => [
                        'currency_decimal_places' => 2,
                        'currency_symbol'         => 'x',
                        'currency_code'           => 'EUR',
                        'currency_id'             => 1,
                        'spent'                   => '-522',
                    ],
                ],
            ],
        ];

        $earned = [
            1 => [
                'name'   => 'Some category',
                'earned' => [
                    // earned in this currency.
                    2 => [
                        'currency_decimal_places' => 2,
                        'currency_id'             => 1,
                        'currency_symbol'         => 'x',
                        'currency_code'           => 'EUR',
                        'earned'                  => '123',
                    ],
                ],
            ],
        ];

        $earnedNoCategory = [
            1 => [
                'currency_decimal_places' => 2,
                'currency_id'             => 3,
                'currency_symbol'         => 'x',
                'currency_code'           => 'EUR',
                'earned'                  => '123',
            ],
        ];
        $spentNoCategory  = [
            5 => [
                'currency_decimal_places' => 2,
                'currency_symbol'         => 'x',
                'currency_code'           => 'EUR',
                'currency_id'             => 4,
                'spent'                   => '-345',
            ],
        ];

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('spentInPeriodPerCurrency')->atLeast()->once()->andReturn($spent);
        $repository->shouldReceive('earnedInPeriodPerCurrency')->atLeast()->once()->andReturn($earned);

        $repository->shouldReceive('earnedInPeriodPcWoCategory')->atLeast()->once()->andReturn($earnedNoCategory);
        $repository->shouldReceive('spentInPeriodPcWoCategory')->atLeast()->once()->andReturn($spentNoCategory);

        $parameters = [
            'start' => '2019-01-01',
            'end'   => '2019-01-31',
        ];
        $response   = $this->get(route('api.v1.chart.category.overview') . '?' . http_build_query($parameters), ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

}