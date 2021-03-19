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

namespace Tests\Api\Models\Rule;


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
        //    - title
        //    - rule_group_id
        //    - trigger
        //    - triggers
        //    - actions
        $set = [
            'default_by_id'    => [
                'parameters' => [],
                'fields'     => [
                    'title'         => $faker->uuid,
                    'rule_group_id' => (string)$faker->randomElement([1, 2]),
                    'trigger'       => $faker->randomElement(['store-journal', 'update-journal']),
                    'triggers'      => [
                        [
                            'type'  => $faker->randomElement(['from_account_starts', 'from_account_is', 'description_ends', 'description_is']),
                            'value' => $faker->uuid,
                        ],
                    ],
                    'actions'       => [
                        [
                            'type'  => $faker->randomElement(['set_category', 'add_tag', 'set_description']),
                            'value' => $faker->uuid,
                        ],
                    ],
                ],
            ],
            'default_by_title' => [
                'parameters' => [],
                'fields'     => [
                    'title'            => $faker->uuid,
                    'rule_group_title' => sprintf('Rule group %d', $faker->randomElement([1, 2])),
                    'trigger'          => $faker->randomElement(['store-journal', 'update-journal']),
                    'triggers'         => [
                        [
                            'type'  => $faker->randomElement(['from_account_starts', 'from_account_is', 'description_ends', 'description_is']),
                            'value' => $faker->uuid,
                        ],
                    ],
                    'actions'          => [
                        [
                            'type'  => $faker->randomElement(['set_category', 'add_tag', 'set_description']),
                            'value' => $faker->uuid,
                        ],
                    ],
                ],
            ],
        ];

        // leave it like this for now.

        return $set;


    }

    /**
     * @return \array[][]
     */
    private function optionalSets(): array
    {
        $faker = Factory::create();

        return [
            'order'                   => [
                'fields' => [
                    'order' => $faker->numberBetween(1, 2),
                ],
            ],
            'active'                  => [
                'fields' => [
                    'active' => $faker->boolean,
                ],
            ],
            'strict'                  => [
                'fields' => [
                    'strict' => $faker->boolean,
                ],
            ],
            'stop_processing'         => [
                'fields' => [
                    'stop_processing' => $faker->boolean,
                ],
            ],
            'triggers_order'          => [
                'fields' => [
                    'triggers' => [
                        // first entry, set field:
                        [
                            'order' => 1,
                        ],
                    ],
                ],
            ],
            'triggers_active'         => [
                'fields' => [
                    'triggers' => [
                        // first entry, set field:
                        [
                            'active' => false,
                        ],
                    ],
                ],
            ],
            'triggers_not_active'     => [
                'fields' => [
                    'triggers' => [
                        // first entry, set field:
                        [
                            'active' => true,
                        ],
                    ],
                ],
            ],
            'triggers_processing'     => [
                'fields' => [
                    'triggers' => [
                        // first entry, set field:
                        [
                            'stop_processing' => true,
                        ],
                    ],
                ],
            ],
            'triggers_not_processing' => [
                'fields' => [
                    'triggers' => [
                        // first entry, set field:
                        [
                            'stop_processing' => false,
                        ],
                    ],
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
        $route = 'api.v1.rules.store';
        $this->storeAndCompare($route, $submission);
    }

}