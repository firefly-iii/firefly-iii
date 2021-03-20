<?php
/*
 * StoreControllerTest.php
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
 * Class StoreControllerTest
 */
class StoreControllerTest extends TestCase
{
    use TestHelpers, CollectsValues;

    /**
     * @return array
     */
    public function emptyDataProvider(): array
    {
        return [[[]]];

    }

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
     * @return array
     */
    public function storeDataProvider(): array
    {
        $minimalSets  = $this->minimalSets();
        $optionalSets = $this->optionalSets();
        $regenConfig  = [
            'name' => function () {
                $faker = Factory::create();

                return $faker->uuid;
            },
        ];

        return $this->genericDataProvider($minimalSets, $optionalSets, $regenConfig);
    }

    /**
     * @return array
     */
    private function minimalSets(): array
    {
        $faker = Factory::create();

        return [
            'default_piggy' => [
                'parameters' => [],
                'fields'     => [
                    'name'          => $faker->uuid,
                    'account_id'    => $faker->numberBetween(1, 3),
                    'target_amount' => number_format($faker->randomFloat(2, 50, 100), 2),
                ],
            ],
        ];
    }

    /**
     * @return \array[][]
     */
    private function optionalSets(): array
    {
        $faker = Factory::create();

        $objectGroupId   = $faker->numberBetween(1, 2);
        $objectGroupName = sprintf('Object group %d', $objectGroupId);

        return [
            'current_amount'     => [
                'fields' => [
                    'current_amount' => number_format($faker->randomFloat(2, 10, 50), 2),
                ],
            ],
            'start_date'         => [
                'fields' => [
                    'start_date' => $faker->dateTimeBetween('-2 year', '-1 year')->format('Y-m-d'),
                ],
            ],
            'target_date'        => [
                'fields' => [
                    'target_date' => $faker->dateTimeBetween('+1 year', '+2 year')->format('Y-m-d'),
                ],
            ],
            'order'              => [
                'fields' => [
                    'order' => $faker->numberBetween(1, 5),
                ],
            ],
            'object_group_id'    => [
                'fields' => [
                    'object_group_id' => $objectGroupId,
                ],
            ],
            'object_group_title' => [
                'fields' => [
                    'object_group_title' => $objectGroupName,
                ],
            ],
            'notes'              => [
                'fields' => [
                    'notes' => join(' ', $faker->words(5)),
                ],
            ],
        ];
    }

    /**
     * @param array $submission
     *
     * emptyDataProvider / storeDataProvider
     *
     * @dataProvider storeDataProvider
     */
    public function testStore(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty data provider');
        }
        $route = 'api.v1.piggy_banks.store';
        $this->storeAndCompare($route, $submission);
    }

}