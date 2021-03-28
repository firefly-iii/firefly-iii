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

declare(strict_types=1);

namespace Tests\Api\Models\TransactionCurrency;
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

        $route = route('api.v1.currencies.update', $submission['parameters']);
        $this->assertPUT($route, $submission);
    }
    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $configuration = new TestConfiguration;

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = ['RMB'];
        $fieldSet->addField(Field::createBasic('name', 'uuid'));
        $configuration->addOptionalFieldSet('name', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = ['RMB'];
        $fieldSet->addField(Field::createBasic('symbol', 'random-new-currency-symbol'));
        $configuration->addOptionalFieldSet('symbol', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = ['RMB'];
        $field                = Field::createBasic('enabled', 'boolean');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('enabled', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = ['RMB'];
        $field                = Field::createBasic('default', 'boolean-true');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('default', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = ['RMB'];
        $field                = Field::createBasic('decimal_places', 'currency-dp');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('decimal_places', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = ['RMB'];
        $fieldSet->addField(Field::createBasic('symbol', 'random-new-currency-code'));
        $configuration->addOptionalFieldSet('code', $fieldSet);
        return $configuration->generateAll();
    }
    /**
     * @return array
     */
    public function updateDataSet(): array
    {
        $faker = Factory::create();
        $set   = [
            'name'           => [
                'id'           => 'INR',
                'fields'       => [
                    'name' => ['test_value' => $faker->uuid],
                ],
                'extra_ignore' => [],
            ],
            'code'           => [
                'id'           => 'INR',
                'fields'       => [
                    'code' => ['test_value' => substr($faker->uuid, 0, 3)],
                ],
                'extra_ignore' => [],
            ],
            'symbol'         => [
                'id'           => 'RUB',
                'fields'       => [
                    'description' => ['test_value' => $faker->randomAscii . $faker->randomAscii],
                ],
                'extra_ignore' => [],
            ],
            'decimal_places' => [
                'id'           => 'ETH',
                'fields'       => [
                    'decimal_places' => ['test_value' => $faker->numberBetween(1, 6)],
                ],
                'extra_ignore' => [],
            ],
            'enabled'        => [
                'id'           => 'ETH',
                'fields'       => [
                    'enabled' => ['test_value' => $faker->boolean],
                ],
                'extra_ignore' => [],
            ],
            'default'        => [
                'id'           => 'XBT',
                'fields'       => [
                    'default' => ['test_value' => $faker->boolean],
                ],
                'extra_ignore' => [],
            ],
        ];

        return $set;
    }
}
