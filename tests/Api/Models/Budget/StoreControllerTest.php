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

namespace Tests\Api\Models\Budget;


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
        $faker = Factory::create();

        return [
            'default_budget' => [
                'fields' => [
                    'name' => $faker->uuid,
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

        $autoBudgetTypes = ['reset', 'rollover'];
        $autoBudgetType  = $autoBudgetTypes[rand(0, count($autoBudgetTypes) - 1)];

        return [
            'active'           => [
                'fields' => [
                    'active' => $faker->boolean,
                ],
            ],
            'auto_budget_id'   => [
                'fields' => [
                    'auto_budget_type'        => $autoBudgetType,
                    'auto_budget_currency_id' => $rand,
                    'auto_budget_amount'      => number_format($faker->randomFloat(2, 10, 100), 2),
                    'auto_budget_period'      => $repeatFreq,
                ],
            ],
            'auto_budget_code' => [
                'fields' => [
                    'auto_budget_type'          => $autoBudgetType,
                    'auto_budget_currency_code' => $currencies[$rand],
                    'auto_budget_amount'        => number_format($faker->randomFloat(2, 10, 100), 2),
                    'auto_budget_period'        => $repeatFreq,
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
        // run account store with a minimal data set:
        $route = 'api.v1.budgets.store';
        $this->storeAndCompare($route, $submission);
    }

}