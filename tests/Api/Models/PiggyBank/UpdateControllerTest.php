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

namespace Tests\Api\Models\PiggyBank;


use Faker\Factory;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;
use Tests\Traits\CollectsValues;

use Tests\Traits\TestHelpers;

/**
 * Class UpdateControllerTest
 */
class UpdateControllerTest extends TestCase
{
    use TestHelpers, CollectsValues;

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
        $route  = route('api.v1.piggy_banks.update', [$submission['id']]);

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
        $objectGroupId   = $faker->numberBetween(1, 2);
        $objectGroupName = sprintf('Object group %d', $objectGroupId);
        $set             = [
            'name'               => [
                'id'           => 1,
                'fields'       => [
                    'name' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
            'account_id'         => [
                'id'           => 1,
                'fields'       => [
                    'account_id' => ['test_value' => (string)$faker->numberBetween(1, 3)],
                ],
                'extra_ignore' => ['account_name', 'currency_id', 'currency_code'],
            ],
            'target_amount'      => [
                'id'           => 1,
                'fields'       => [
                    'target_amount' => ['test_value' => number_format($faker->randomFloat(2, 50, 100), 2)],
                ],
                'extra_ignore' => ['percentage', 'current_amount', 'left_to_save'],
            ],
            'current_amount'     => [
                'id'           => 1,
                'fields'       => [
                    'current_amount' => ['test_value' => number_format($faker->randomFloat(2, 5, 10), 2)],
                ],
                'extra_ignore' => ['percentage', 'left_to_save'],
            ],
            'start_date'         => [
                'id'           => 1,
                'fields'       => [
                    'start_date' => ['test_value' => $faker->dateTimeBetween('-2 year', '-1 year')->format('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
            'target_date'        => [
                'id'           => 1,
                'fields'       => [
                    'target_date' => ['test_value' => $faker->dateTimeBetween('+1 year', '+2 year')->format('Y-m-d')],
                ],
                'extra_ignore' => ['save_per_month'],
            ],
            'order'              => [
                'id'           => 1,
                'fields'       => [
                    'order' => ['test_value' => $faker->numberBetween(1, 5)],
                ],
                'extra_ignore' => [],
            ],
            'notes'              => [
                'id'           => 1,
                'fields'       => [
                    'notes' => ['test_value' => join(' ', $faker->words(5))],
                ],
                'extra_ignore' => [],
            ],
            'object_group_id'    => [
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
        ];

        return $set;
    }


}