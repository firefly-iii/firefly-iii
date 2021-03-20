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

namespace Tests\Api\Models\TransactionCurrency;


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
        $route  = route('api.v1.currencies.update', [$submission['id']]);

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
        $faker = Factory::create();
        $set   = [
            'name'           => [
                'id'           => 'INR',
                'fields'       => [
                    'name' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
            'code'           => [
                'id'           => 'INR',
                'fields'       => [
                    'code' => ['test_value' => substr($faker->uuid, 0, 3)],
                ],
                'extra_ignore' => [],
            ],
            'symbol'         => [
                'id'           => 'RUB',
                'fields'       => [
                    'description' => ['test_value' => $faker->randomAscii . $faker->randomAscii],
                ],
                'extra_ignore' => [],
            ],
            'decimal_places' => [
                'id'           => 'ETH',
                'fields'       => [
                    'decimal_places' => ['test_value' => $faker->numberBetween(1, 6)],
                ],
                'extra_ignore' => [],
            ],
            'enabled'        => [
                'id'           => 'ETH',
                'fields'       => [
                    'enabled' => ['test_value' => $faker->boolean],
                ],
                'extra_ignore' => [],
            ],
            'default'        => [
                'id'           => 'XBT',
                'fields'       => [
                    'default' => ['test_value' => $faker->boolean],
                ],
                'extra_ignore' => [],
            ],
        ];

        return $set;
    }


}