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

namespace Tests\Api\Models\Account;


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
            'currency_code',
            'currency_symbol',
            'currency_decimal_places',
            'current_balance',
        ];
        $route  = route('api.v1.accounts.update', [$submission['id']]);

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
        $faker        = Factory::create();
        $currencies   = ['EUR', 'GBP', 'USD', 'HUF'];
        $currencyCode = $currencies[rand(0, count($currencies) - 1)];

        $accountRoles = ['defaultAsset', 'sharedAsset', 'savingAsset'];
        $accountRole  = $accountRoles[rand(0, count($accountRoles) - 1)];

        $liabilityRoles = ['loan', 'debt', 'asset'];
        $liabilityRole  = $liabilityRoles[rand(0, count($liabilityRoles) - 1)];

        $interestPeriods = ['daily', 'monthly', 'yearly'];
        $interestPeriod  = $interestPeriods[rand(0, count($interestPeriods) - 1)];

        $set = [
            'name'              => [
                'id'           => 1,
                'fields'       => [
                    'name' => ['test_value' => $faker->text(64)],
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
            'iban'              => [
                'id'           => 1,
                'fields'       => [
                    'iban' => ['test_value' => $faker->iban()],
                ],
                'extra_ignore' => [],
            ],
            'bic'               => [
                'id'           => 1,
                'fields'       => [
                    'bic' => ['test_value' => $faker->swiftBicNumber],
                ],
                'extra_ignore' => [],
            ],
            'account_number'    => [
                'id'           => 1,
                'fields'       => [
                    'account_number' => ['test_value' => $faker->iban()],
                ],
                'extra_ignore' => [],
            ],
            'order'             => [
                'id'           => 1,
                'fields'       => [
                    'order' => ['test_value' => $faker->numberBetween(1, 10)],
                ],
                'extra_ignore' => [],
            ],
            'include_net_worth' => [
                'id'           => 1,
                'fields'       => [
                    'include_net_worth' => ['test_value' => $faker->boolean],
                ],
                'extra_ignore' => [],
            ],
            'virtual_balance'   => [
                'id'           => 1,
                'fields'       => [
                    'virtual_balance' => ['test_value' => number_format($faker->randomFloat(2,10,100), 2)],
                ],
                'extra_ignore' => [],
            ],
            'currency_id'       => [
                'id'           => 1,
                'fields'       => [
                    'currency_id' => ['test_value' => (string)$faker->numberBetween(1, 10)],
                ],
                'extra_ignore' => ['currency_code'],
            ],
            'currency_code'     => [
                'id'           => 1,
                'fields'       => [
                    'currency_code' => ['test_value' => $currencyCode],
                ],
                'extra_ignore' => ['currency_id'],
            ],
            'account_role'      => [
                'id'           => 1,
                'fields'       => [
                    'account_role' => ['test_value' => $accountRole],
                ],
                'extra_ignore' => [],
            ],
            'notes'             => [
                'id'           => 1,
                'fields'       => [
                    'notes' => ['test_value' => $faker->randomAscii],
                ],
                'extra_ignore' => [],
            ],
            'location'          => [
                'id'           => 1,
                'fields'       => [
                    'longitude'  => ['test_value' => $faker->longitude],
                    'latitude'   => ['test_value' => $faker->latitude],
                    'zoom_level' => ['test_value' => $faker->numberBetween(1, 10)],
                ],
                'extra_ignore' => [],
            ],
            'ob'                => [
                'id'           => 1,
                'fields'       => [
                    'opening_balance'      => ['test_value' => number_format($faker->randomFloat(2,10,100), 2)],
                    'opening_balance_date' => ['test_value' => $faker->date('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
            'cc2'               => [
                'id'           => 7,
                'fields'       => [
                    'monthly_payment_date' => ['test_value' => $faker->date('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
            'cc3'               => [
                'id'           => 7,
                'fields'       => [
                    'monthly_payment_date' => ['test_value' => $faker->date('Y-m-d')],
                    'credit_card_type'     => ['test_value' => 'monthlyFull'],
                ],
                'extra_ignore' => [],
            ],
            'liabilityA'        => [
                'id'           => 13,
                'fields'       => [
                    'liability_type' => ['test_value' => $liabilityRole],
                ],
                'extra_ignore' => [],
            ],
            'liabilityB'        => [
                'id'           => 13,
                'fields'       => [
                    'interest'        => ['test_value' => $faker->randomFloat(2, 1, 99)],
                    'interest_period' => ['test_value' => $interestPeriod],
                ],
                'extra_ignore' => [],
            ],
        ];

        return $set;
    }


}