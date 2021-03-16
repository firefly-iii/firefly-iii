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
use Tests\Objects\TestConfiguration;
use Tests\Objects\TestMandatoryField;
use Tests\Objects\TestMandatoryFieldSet;
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
     * emptyDataProvider / storeDataProvider
     *
     * @dataProvider storeDataProvider
     */
    public function testStore(array $submission): void
    {
        $this->someTestData();
        exit;
        if ([] === $submission) {
            $this->markTestSkipped('Empty data provider');
        }
        // run account store with a minimal data set:
        $route = 'api.v1.accounts.store';
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
            'name'           => function () {
                $faker = Factory::create();

                return $faker->uuid;
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
            'active'            => [
                'fields' => [
                    'active' => $faker->boolean,
                ],
            ],
            'iban'              => [
                'fields' => [
                    'iban' => $faker->iban(),
                ],
            ],
            'bic'               => [
                'fields' => [
                    'bic' => $faker->swiftBicNumber,
                ],
            ],
            'account_number'    => [
                'fields' => [
                    'account_number' => $faker->iban(),
                ],
            ],
            'ob'                => [
                'fields' => [
                    'opening_balance'      => $this->getRandomAmount(),
                    'opening_balance_date' => $this->getRandomDateString(),
                ],
            ],
            'virtual_balance'   => [
                'fields' => [
                    'virtual_balance' => $this->getRandomAmount(),
                ],
            ],
            'currency_id'       => [
                'fields' => [
                    'currency_id' => $rand,
                ],
            ],
            'currency_code'     => [
                'fields' => [
                    'currency_code' => $currencies[$rand],
                ],
            ],
            'order'             => [
                'fields' => [
                    'order' => $faker->numberBetween(1, 5),
                ],
            ],
            'include_net_worth' => [
                'fields' => [
                    'include_net_worth' => $faker->boolean,
                ],
            ],
            'notes'             => [
                'fields' => [
                    'notes' => join(' ', $faker->words(5)),
                ],
            ],
            'location'          => [
                'fields' => [
                    'latitude'   => $faker->latitude,
                    'longitude'  => $faker->longitude,
                    'zoom_level' => $faker->numberBetween(1, 10),
                ],
            ],
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
                'parameters' => [],
                'fields'     => [
                    'name'         => $faker->uuid,
                    'type'         => 'asset',
                    'account_role' => $this->randomAccountRole(),
                ],
            ],
            'expense'   => [
                'parameters' => [],
                'fields'     => [
                    'name' => $faker->uuid,
                    'type' => 'expense',
                ],
            ],
            'liability' => [
                'parameters' => [],
                'fields'     => [
                    'name'                 => $faker->uuid,
                    'type'                 => 'liabilities',
                    'liability_type'       => $this->randomLiabilityType(),
                    'liability_amount'     => $this->getRandomAmount(),
                    'liability_start_date' => $this->getRandomDateString(),
                    'interest'             => $this->getRandomPercentage(),
                    'interest_period'      => $this->getRandomInterestPeriod(),
                ],
                'ignore'     => [
                    'opening_balance', 'opening_balance_date',
                ],
            ],
            'cc'        => [
                'fields' => [
                    'name'                 => $faker->uuid,
                    'type'                 => 'asset',
                    'account_role'         => 'ccAsset',
                    'credit_card_type'     => 'monthlyFull',
                    'monthly_payment_date' => $this->getRandomDateString(),

                ],
            ],
        ];
    }

    public function someTestData(): void
    {
        // a basic test config set contains
        // mandatory fields and X optional fields
        // the optional fields will be rotated automatically.
        $config = new TestConfiguration;

        // add a set of mandatory fields:
        $mandatoryFieldSet        = new TestMandatoryFieldSet();
        $mandatoryFieldSet->title = 'default_asset_account';

        // name
        $mandatoryField                     = new TestMandatoryField;
        $mandatoryField->title              = 'name';
        $mandatoryField->fieldTitle         = 'name';
        $mandatoryField->fieldPosition      = ''; // root
        $mandatoryField->fieldType          = 'uuid'; // refers to a generator or something?
        $mandatoryField->expectedReturnType = 'equal'; // or 'callback'
        $mandatoryField->expectedReturn     = null; // or the callback
        $mandatoryField->ignorableFields    = [];
        $mandatoryFieldSet->addMandatoryField($mandatoryField);

        // type
        $mandatoryField                     = new TestMandatoryField;
        $mandatoryField->title              = 'type';
        $mandatoryField->fieldTitle         = 'type';
        $mandatoryField->fieldPosition      = ''; // root
        $mandatoryField->fieldType          = 'static-asset'; // refers to a generator or something?
        $mandatoryField->expectedReturnType = 'equal'; // or 'callback'
        $mandatoryField->expectedReturn     = null; // or the callback
        $mandatoryField->ignorableFields    = []; // something like transactions/0/currency_code
        $mandatoryFieldSet->addMandatoryField($mandatoryField);

        // role
        $mandatoryField                     = new TestMandatoryField;
        $mandatoryField->title              = 'role';
        $mandatoryField->fieldTitle         = 'account_role';
        $mandatoryField->fieldPosition      = ''; // root
        $mandatoryField->fieldType          = 'random-asset-accountRole'; // refers to a generator or something?
        $mandatoryField->expectedReturnType = 'equal'; // or 'callback'
        $mandatoryField->expectedReturn     = null; // or the callback
        $mandatoryField->ignorableFields    = []; // something like transactions/0/currency_code
        $mandatoryFieldSet->addMandatoryField($mandatoryField);

//        $mandatoryField                     = new TestMandatoryField;
//        $mandatoryField->title              = 'transaction_type';
//        $mandatoryField->fieldTitle         = 'type';
//        $mandatoryField->fieldPosition      = 'transactions/0'; // not root!
//        $mandatoryField->fieldType          = 'random-transactionType'; // refers to a generator or something?
//        $mandatoryField->expectedReturnType = 'equal'; // or 'callback'
//        $mandatoryField->expectedReturn     = null; // or the callback
//        $mandatoryField->ignorableFields    = [];
//        $mandatoryFieldSet->addMandatoryField($mandatoryField);

        $config->mandatoryFieldSet = $mandatoryFieldSet;

        // generate submissions
        $arr = $config->generateSubmission();
        var_dump($arr);
        exit;
        // generate expected returns.

        $set                                = [
            // set for withdrawal, copy this for
            // other transaction types etc.
            // make a CLASS!!
            'identifier' => [
                'mandatory_fields' => [
                    'name_of_set' => [
                        'fields' => [
                            'basic_text_field'                      => [
                                'test_value'            => function () {
                                    return 'callback';
                                },
                                'expected_return_value' => function ($input) {
                                    // the same?
                                    return $input;

                                    // a conversion?
                                    return (string)$input;

                                    // something else entirely?
                                    return 'something else entirely.';
                                },
                                'ignore_other_fields'   => [
                                    'key_to_ignore',
                                    'sub_array_like_transactions' => [0 => 'field_to_ignore'],
                                ],
                            ],
                            'another_basic_text_field'              => [
                                // see above for 'test_value', 'expected_return_value' and 'ignore_other_fields'
                            ],
                            'complex_array_field_like_transactions' => [
                                'transactions' => [
                                    0 => [
                                        'field_is_here' => [
                                            'test_value'            => null, // see above
                                            'expected_return_value' => null,  // see above
                                            'ignore_other_fields'   => [], // see above
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // these will be permutated
                'optional_fields'  => [],
            ],
        ];
    }
}