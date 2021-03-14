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

namespace Tests\Api\Models\Bill;


use Faker\Factory;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;
use Tests\Traits\CollectsValues;
use Tests\Traits\RandomValues;
use Tests\Traits\TestHelpers;

/**
 * Class StoreControllerTest
 */
class StoreControllerTest extends TestCase
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
     * @param array $submission
     *
     * emptyDataProvider / storeDataProvider
     * @dataProvider storeDataProvider
     */
    public function testStore(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty data provider');
        }
        // run account store with a minimal data set:
        $route = 'api.v1.bills.store';
        $this->storeAndCompare($route, $submission);
    }

    /**
     * @return array
     */
    public function emptyDataProvider(): array
    {
        return [[[]]];

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

                return join(' ', $faker->words(5));
            },
        ];

        return $this->genericDataProvider($minimalSets, $optionalSets, $regenConfig);
    }


    /**
     * @return array
     */
    private function minimalSets(): array
    {
        $faker       = Factory::create();
        $repeatFreqs = ['yearly', 'weekly', 'monthly'];
        $repeatFreq  = $repeatFreqs[rand(0, count($repeatFreqs) - 1)];

        return [
            'default_bill' => [
                'fields' => [
                    'name'        => $faker->uuid,
                    'amount_min'  => number_format($faker->randomFloat(2, 10, 50), 2),
                    'amount_max'  => number_format($faker->randomFloat(2, 60, 90), 2),
                    'date'        => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                    'repeat_freq' => $repeatFreq,
                ],
            ],
        ];
    }


    /**
     * @return \array[][]
     */
    private function optionalSets(): array
    {
        $faker           = Factory::create();
        $repeatFreqs     = ['weekly', 'monthly', 'yearly'];
        $repeatFreq      = $repeatFreqs[rand(0, count($repeatFreqs) - 1)];
        $currencies      = [
            1 => 'EUR',
            2 => 'HUF',
            3 => 'GBP',
            4 => 'UAH',
        ];
        $rand            = rand(1, 4);
        $objectGroupId   = $faker->numberBetween(1, 2);
        $objectGroupName = sprintf('Object group %d', $objectGroupId);

        return [
            'currency_by_id'     => [
                'fields' => [
                    'currency_id' => $rand,
                ],
            ],
            'currency_by_code'   => [
                'fields' => [
                    'currency_code' => $currencies[$rand],
                ],
            ],
            'name'               => [
                'fields' => [
                    'name' => $faker->uuid,
                ],
            ],
            'amount_min'         => [
                'fields' => [
                    'amount_min' => number_format($faker->randomFloat(2, 10, 50), 2),
                ],
            ],
            'amount_max'         => [
                'fields' => [
                    'amount_max' => number_format($faker->randomFloat(2, 60, 590), 2),
                ],
            ],
            'date'               => [
                'fields' => [
                    'date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                ],
            ],
            'repeat_freq'        => [
                'fields' => [
                    'repeat_freq' => $repeatFreq,
                ],
            ],
            'skip'               => [
                'fields' => [
                    'skip' => $faker->numberBetween(0, 5),
                ],
            ],
            'active'             => [
                'fields' => [
                    'active' => $faker->boolean,
                ],
            ],
            'notes'              => [
                'fields' => [
                    'notes' => join(' ', $faker->words(5)),
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
        ];
    }

}