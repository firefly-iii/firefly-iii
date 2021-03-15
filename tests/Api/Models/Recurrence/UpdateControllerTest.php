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

namespace Tests\Api\Models\Recurrence;


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
        $route  = route('api.v1.recurrences.update', [$submission['id']]);

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
        $types = [
            ['daily', ''],
            ['weekly', (string)$faker->numberBetween(1, 7)],
            ['ndom', (string)$faker->numberBetween(1, 4) . ',' . $faker->numberBetween(1, 7)],
            ['monthly', (string)$faker->numberBetween(1, 31)],
            ['yearly', $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d')],
        ];

        $set = [
            'title'             => [
                'id'           => 1,
                'fields'       => [
                    'title' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
            'description'       => [
                'id'           => 1,
                'fields'       => [
                    'description' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
            'first_date'        => [
                'id'           => 1,
                'fields'       => [
                    'first_date' => ['test_value' => $faker->date()],
                ],
                'extra_ignore' => [],
            ],
            'repeat_until'      => [
                'id'           => 1,
                'fields'       => [
                    'repeat_until' => ['test_value' => $faker->dateTimeBetween('1 year', '2 year')->format('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
            'nr_of_repetitions' => [
                'id'           => 1,
                'fields'       => [
                    'nr_of_repetitions' => ['test_value' => $faker->numberBetween(1, 5)],
                ],
                'extra_ignore' => ['repeat_until'],
            ],
            'apply_rules'       => [
                'id'           => 1,
                'fields'       => [
                    'apply_rules' => ['test_value' => $faker->boolean],
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
                    'notes' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
        ];
        // repetitions. Will submit 0,1 2 3 repetitions:
        for ($i = 0; $i < 4; $i++) {
            if (0 === $i) {
                $set[] = [
                    'id'           => 1,
                    'fields'       => [
                        'repetitions' => [
                            'test_value' => [],
                        ],
                    ],
                    'extra_ignore' => [],
                ];
                continue;
            }
            $extraRepetitions = [];
            // do $i repetitions
            for ($ii = 0; $ii < $i; $ii++) {
                //echo 'Now at ' . $i . ':' . $ii . ' <br>' . "\n";
                // now loop fields, enough to create sets I guess?
                $thisType = $types[$faker->numberBetween(0, 4)];
                // TODO maybe do some permutation stuff here?
                $extraRepetition = [
                    'type'    => $thisType[0],
                    'moment'  => $thisType[1],
                    'skip'    => $faker->numberBetween(1, 3),
                    'weekend' => $faker->numberBetween(1, 4),
                ];

                $extraRepetitions[] = $extraRepetition;
            }
            $set[] = [
                'id'           => 1,
                'fields'       => [
                    'repetitions' => [
                        'test_value' => $extraRepetitions,
                    ],
                ],
                'extra_ignore' => [],
            ];
        }

        // transactions. Will submit 0,1 2 3 transactions:
        for ($i = 0; $i < 4; $i++) {
            if (0 === $i) {
                $set[] = [
                    'id'           => 1,
                    'fields'       => [
                        'transactions' => [
                            'test_value' => [],
                        ],
                    ],
                    'extra_ignore' => [],
                ];
                continue;
            }
            $extraTransactions = [];
            // do $i repetitions
            for ($ii = 0; $ii < $i; $ii++) {
                //echo 'Now at ' . $i . ':' . $ii . ' <br>' . "\n";
                // now loop fields, enough to create sets I guess?
                // TODO maybe do some permutation stuff here?
                $extraTransaction = [
                    'currency_id'         => (string)$faker->numberBetween(1, 4),
                    'foreign_currency_id' => (string)$faker->numberBetween(4, 6),
                    'source_id'           => $faker->numberBetween(1, 3),
                    'destination_id'      => $faker->numberBetween(8, 8),
                    'amount'              => number_format($faker->randomFloat(2, 10, 100), 2),
                    'foreign_amount'      => number_format($faker->randomFloat(2, 10, 100), 2),
                    'description'         => $faker->uuid,
                ];

                $extraTransactions[] = $extraTransaction;
            }
            $set[] = [
                'id'           => 1,
                'fields'       => [
                    'transactions' => [
                        'test_value' => $extraTransactions,
                    ],
                ],
                'extra_ignore' => [],
            ];
        }

        return $set;
    }


}