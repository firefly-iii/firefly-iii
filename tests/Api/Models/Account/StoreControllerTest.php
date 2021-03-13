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

namespace Tests\Api\Models\Account;


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
     * @dataProvider storeDataProvider
     *
     * @ data Provider emptyDataProvider
     */
    public function testStore(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty data provider');
        }
        // run account store with a minimal data set:
        $route = 'api.v1.accounts.store';
        $this->submitAndCompare($route, $submission);
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
            'name'           => function () {
                $faker = Factory::create();

                return $faker->name;
            },
            'iban'           => function () {
                $faker = Factory::create();

                return $faker->iban();
            },
            'account_number' => function () {
                $faker = Factory::create();

                return $faker->iban();
            },
        ];

        return $this->genericDataProvider($minimalSets, $optionalSets, $regenConfig);
    }

    /**
     * @return \array[][]
     */
    private function optionalSets(): array
    {
        $faker      = Factory::create();
        $currencies = [
            1 => 'EUR',
            2 => 'HUF',
            3 => 'GBP',
            4 => 'UAH',
        ];
        $rand       = rand(1, 4);

        return [
            'active' => [
                'fields' => [
                    'active' => $faker->boolean,
                ],
            ],
            //            'iban'              => [
            //                'fields' => [
            //                    'iban' => $faker->iban(),
            //                ],
            //            ],
            //            'bic'               => [
            //                'fields' => [
            //                    'bic' => $faker->swiftBicNumber,
            //                ],
            //            ],
            //            'account_number'    => [
            //                'fields' => [
            //                    'account_number' => $faker->iban(),
            //                ],
            //            ],
            //            'ob'                => [
            //                'fields' => [
            //                    'opening_balance'      => $this->getRandomAmount(),
            //                    'opening_balance_date' => $this->getRandomDateString(),
            //                ],
            //            ],
            //            'virtual_balance'   => [
            //                'fields' => [
            //                    'virtual_balance' => $this->getRandomAmount(),
            //                ],
            //            ],
            //            'currency_id'       => [
            //                'fields' => [
            //                    'currency_id' => $rand,
            //                ],
            //            ],
            //            'currency_code'     => [
            //                'fields' => [
            //                    'currency_code' => $currencies[$rand],
            //                ],
            //            ],
            //            'order'             => [
            //                'fields' => [
            //                    'order' => $faker->numberBetween(1, 5),
            //                ],
            //            ],
            //            'include_net_worth' => [
            //                'fields' => [
            //                    'include_net_worth' => $faker->boolean,
            //                ],
            //            ],
            //            'notes'             => [
            //                'fields' => [
            //                    'notes' => join(' ', $faker->words(5)),
            //                ],
            //            ],
            //            'location'          => [
            //                'fields' => [
            //                    'latitude'   => $faker->latitude,
            //                    'longitude'  => $faker->longitude,
            //                    'zoom_level' => $faker->numberBetween(1, 10),
            //                ],
            //            ],
        ];
    }

    /**
     * @return array
     */
    private function minimalSets(): array
    {
        $faker = Factory::create();

        return [
            'asset'     => [
                'fields' => [
                    'name'         => $faker->name . join(' ', $faker->words(2)),
                    'type'         => 'asset',
                    'account_role' => $this->randomAccountRole(),
                ],
            ],
            'expense'   => [
                'fields' => [
                    'name' => $faker->name,
                    'type' => 'expense',
                ],
            ],
            'liability' => [
                'fields' => [
                    'name'                 => $faker->name,
                    'type'                 => 'liabilities',
                    'liability_type'       => $this->randomLiabilityType(),
                    'liability_amount'     => $this->getRandomAmount(),
                    'liability_start_date' => $this->getRandomDateString(),
                    'interest'             => $this->getRandomPercentage(),
                    'interest_period'      => $this->getRandomInterestPeriod(),
                ],
            ],
            'cc'        => [
                'fields' => [
                    'name'                 => $faker->name,
                    'type'                 => 'asset',
                    'account_role'         => 'ccAsset',
                    'credit_card_type'     => 'monthlyFull',
                    'monthly_payment_date' => $this->getRandomDateString(),

                ],
            ],
        ];
    }
}