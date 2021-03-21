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

namespace Tests\Api\Models\BudgetLimit;


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

        $route = route('api.v1.budgets.limits.update', $submission['parameters']);
        $this->assertPUT($route, $submission);
    }


    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $configuration = new TestConfiguration;

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1, 1];
        $field                  = new Field;
        $field->fieldTitle      = 'currency_id';
        $field->fieldType       = 'random-currency-id';
        $field->ignorableFields = ['currency_code', 'currency_name', 'currency_symbol', 'spent'];
        $field->title           = 'currency_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_id', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1, 1];
        $field                  = new Field;
        $field->fieldTitle      = 'currency_code';
        $field->fieldType       = 'random-currency-code';
        $field->ignorableFields = ['currency_id', 'currency_name', 'currency_symbol', 'spent'];
        $field->title           = 'currency_code';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_code', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1, 1];
        $fieldSet->addField(Field::createBasic('start', 'random-date-two-year'));
        $configuration->addOptionalFieldSet('start', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1, 1];
        $fieldSet->addField(Field::createBasic('end', 'random-date-one-year'));
        $configuration->addOptionalFieldSet('end', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1, 1];
        $fieldSet->addField(Field::createBasic('amount', 'random-amount'));
        $configuration->addOptionalFieldSet('amount', $fieldSet);

        return $configuration->generateAll();
    }

}