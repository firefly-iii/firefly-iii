<?php
/*
 * UpdateControllerTest.php
 * Copyright (c) 2021 james@firefly-iii.org
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

namespace Tests\Api\Models\Bill;


use Faker\Factory;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;
use Tests\Traits\CollectsValues;
use Tests\Traits\RandomValues;
use Tests\Traits\TestHelpers;

/**
 * Class UpdateControllerTest
 */
class UpdateControllerTest extends TestCase
{
    use RandomValues, TestHelpers, CollectsValues;

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
     * @dataProvider updateDataProvider
     */
    public function testUpdate(array $submission): void
    {
        $ignore = [
            'created_at',
            'updated_at',
        ];
        $route  = route('api.v1.bills.update', [$submission['id']]);

        $this->updateAndCompare($route, $submission, $ignore);
    }


    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $submissions = [];
        $all         = $this->updateDataSet();
        foreach ($all as $name => $data) {
            $submissions[] = [$data];
        }

        return $submissions;
    }


    /**
     * @return array
     */
    public function updateDataSet(): array
    {
        $faker           = Factory::create();
        $currencies      = [
            1 => 'EUR',
            2 => 'HUF',
            3 => 'GBP',
            4 => 'UAH',
        ];
        $repeatFreqs     = ['yearly', 'weekly', 'monthly'];
        $repeatFreq      = $repeatFreqs[rand(0, count($repeatFreqs) - 1)];
        $objectGroupId   = $faker->numberBetween(1, 2);
        $objectGroupName = sprintf('Object group %d', $objectGroupId);
        $rand            = rand(1, 4);
        $set             = [
            'name'       => [
                'id'           => 1,
                'fields'       => [
                    'name' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
            'amount_min' => [
                'id'           => 1,
                'fields'       => [
                    'amount_min' => ['test_value' => number_format($faker->randomFloat(2, 10, 50), 2)],
                ],
                'extra_ignore' => [],
            ],
            'amount_max' => [
                'id'           => 1,
                'fields'       => [
                    'amount_max' => ['test_value' => number_format($faker->randomFloat(2, 60, 90), 2)],
                ],
                'extra_ignore' => [],
            ],
            'date'       => [
                'id'           => 1,
                'fields'       => [
                    'date' => ['test_value' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],

            'repeat_freq' => [
                'id'           => 1,
                'fields'       => [
                    'repeat_freq' => ['test_value' => $repeatFreq],
                ],
                'extra_ignore' => [],
            ],
            'skip'        => [
                'id'           => 1,
                'fields'       => [
                    'skip' => ['test_value' => $faker->numberBetween(1, 10)],
                ],
                'extra_ignore' => [],
            ],

            'active'            => [
                'id'           => 1,
                'fields'       => [
                    'active' => ['test_value' => $faker->boolean],
                ],
                'extra_ignore' => [],
            ],
            'notes'             => [
                'id'           => 1,
                'fields'       => [
                    'notes' => ['test_value' => join(' ', $faker->words(5))],
                ],
                'extra_ignore' => [],
            ],
            'object_group_id'   => [
                'id'           => 1,
                'fields'       => [
                    'object_group_id' => ['test_value' => (string)$objectGroupId],
                ],
                'extra_ignore' => ['object_group_order', 'object_group_title'],
            ],
            'object_group_title' => [
                'id'           => 1,
                'fields'       => [
                    'object_group_title' => ['test_value' => $objectGroupName],
                ],
                'extra_ignore' => ['object_group_order', 'object_group_id'],
            ],
            'currency_id'       => [
                'id'           => 1,
                'fields'       => [
                    'currency_id' => ['test_value' => (string)$rand],
                ],
                'extra_ignore' => ['currency_code', 'currency_symbol'],
            ],
            'currency_code'     => [
                'id'           => 1,
                'fields'       => [
                    'currency_code' => ['test_value' => $currencies[$rand]],
                ],
                'extra_ignore' => ['currency_id', 'currency_symbol'],
            ],

        ];

        return $set;
    }


}