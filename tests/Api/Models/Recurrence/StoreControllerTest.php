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

namespace Tests\Api\Models\Recurrence;


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
     *
     * @dataProvider storeDataProvider
     */
    public function testStore(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty data provider');
        }
        $route = 'api.v1.recurrences.store';
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
            'title' => function () {
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
        // three sets:
        $combis = [
            ['withdrawal', 1, 8],
            ['deposit', 9, 1],
            ['transfer', 1, 2],
        ];

        $types = [
            ['daily', ''],
            ['weekly', (string)$faker->numberBetween(1, 7)],
            ['ndom', (string)$faker->numberBetween(1, 4) . ',' . $faker->numberBetween(1, 7)],
            ['monthly', (string)$faker->numberBetween(1, 31)],
            ['yearly', $faker->dateTimeBetween('-1 year','now')->format('Y-m-d')],
        ];
        $set   = [];

        foreach ($combis as $combi) {
            foreach ($types as $type) {
                $set[] = [
                    'parameters' => [],
                    'fields'     => [
                        'type'         => $combi[0],
                        'title'        => $faker->uuid,
                        'first_date'   => $faker->date(),
                        'repeat_until' => $faker->date(),
                        'repetitions'  => [
                            [
                                'type'   => $type[0],
                                'moment' => $type[1],
                            ],
                        ],
                        'transactions' => [
                            [
                                'description'    => $faker->uuid,
                                'amount'         => number_format($faker->randomFloat(2, 10, 100), 2),
                                'source_id'      => $combi[1],
                                'destination_id' => $combi[2],
                            ],
                        ],
                    ],
                ];
            }
        }

        return $set;
    }


    /**
     * @return \array[][]
     */
    private function optionalSets(): array
    {
        $faker = Factory::create();

        return [
            'description'       => [
                'fields' => [
                    'description' => $faker->uuid,
                ],
            ],
            'nr_of_repetitions' => [
                'fields'        => [
                    'nr_of_repetitions' => $faker->numberBetween(1, 2),
                ],
                'remove_fields' => ['repeat_until'],
            ],
            'apply_rules'       => [
                'fields' => [
                    'apply_rules' => $faker->boolean,
                ],
            ],
            'active'            => [
                'fields' => [
                    'active' => $faker->boolean,
                ],
            ],
            'notes'             => [
                'fields' => [
                    'notes' => $faker->uuid,
                ],
            ],
            'repetitions_skip' => [
                'fields' => [
                    'repetitions' => [
                        // first entry, set field:
                        [
                            'skip' => $faker->numberBetween(1,3),
                        ],
                    ],
                ],
            ],
            'repetitions_weekend' => [
                'fields' => [
                    'repetitions' => [
                        // first entry, set field:
                        [
                            'weekend' => $faker->numberBetween(1,4),
                        ],
                    ],
                ],
            ]
        ];
    }

}