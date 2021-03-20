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

namespace Tests\Api\Models\AvailableBudget;


use Faker\Factory;
use Laravel\Passport\Passport;
use Log;
use Tests\Objects\Field;
use Tests\Objects\FieldSet;
use Tests\Objects\TestConfiguration;
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
     * @param array $submission
     *
     * @dataProvider updateDataProvider
     */
    public function testUpdate(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty provider.');
        }
        Log::debug('testStoreUpdated()');
        Log::debug('submission       :', $submission['submission']);
        Log::debug('expected         :', $submission['expected']);
        Log::debug('ignore           :', $submission['ignore']);
        Log::debug('parameters       :', $submission['parameters']);

        $route = route('api.v1.available_budgets.update', $submission['parameters']);
        $this->assertPUT($route, $submission);
    }


    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $configuration = new TestConfiguration;

        // optional field sets (for all test configs)
        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'currency_id';
        $field->fieldType       = 'random-currency-id';
        $field->ignorableFields = ['currency_code','currency_symbol'];
        $field->title           = 'currency_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_id', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'currency_code';
        $field->fieldType       = 'random-currency-code';
        $field->ignorableFields = ['currency_id','currency_symbol'];
        $field->title           = 'currency_code';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_id', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('amount', 'random-amount'));
        $configuration->addOptionalFieldSet('amount', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('start', 'random-date-two-year'));
        $configuration->addOptionalFieldSet('start', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('end', 'random-date-one-year'));
        $configuration->addOptionalFieldSet('end', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('start', 'random-date-two-year'));
        $fieldSet->addField(Field::createBasic('end', 'random-date-one-year'));
        $configuration->addOptionalFieldSet('both', $fieldSet);
        return $configuration->generateAll();
    }


    /**
     * @return array
     */
    public function updateDataSet(): array
    {
        $faker        = Factory::create();
        $currencies   = ['EUR', 'GBP', 'USD', 'HUF'];
        $currencyCode = $currencies[rand(0, count($currencies) - 1)];
        $set          = [
            'currency_id'   => [
                'id'           => 1,
                'fields'       => [
                    'currency_id' => ['test_value' => (string)$faker->numberBetween(1, 10)],
                ],
                'extra_ignore' => ['currency_code', 'currency_symbol'],
            ],
            'currency_code' => [
                'id'           => 1,
                'fields'       => [
                    'currency_code' => ['test_value' => $currencyCode],
                ],
                'extra_ignore' => ['currency_id', 'currency_symbol'],
            ],
            'amount'        => [
                'id'           => 1,
                'fields'       => [
                    'amount' => ['test_value' => number_format($faker->randomFloat(2, 10, 100), 2)],
                ],
                'extra_ignore' => [],
            ],
            'start'         => [
                'id'           => 1,
                'fields'       => [
                    'start' => ['test_value' => $faker->dateTimeBetween('-2 year', '-1 year')->format('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
            'end'           => [
                'id'           => 1,
                'fields'       => [
                    'end' => ['test_value' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
            'both'          => [
                'id'           => 1,
                'fields'       => [
                    'start' => ['test_value' => $faker->dateTimeBetween('-2 year', '-1 year')->format('Y-m-d')],
                    'end'   => ['test_value' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d')],
                ],
                'extra_ignore' => [],
            ],
        ];

        return $set;
    }


}