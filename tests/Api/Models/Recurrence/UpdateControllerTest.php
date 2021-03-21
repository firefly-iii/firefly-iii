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

        $route = route('api.v1.recurrences.update', $submission['parameters']);
        $this->assertPUT($route, $submission);

    }


    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $configuration = new TestConfiguration;
        // optional fields
        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $field                = Field::createBasic('title', 'uuid');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('title', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $field                = Field::createBasic('description', 'uuid');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('description', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('first_date', 'random-past-date');
        $field->ignorableFields = ['repetitions'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('first_date', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $field                = Field::createBasic('apply_rules', 'boolean');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('apply_rules', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $field                = Field::createBasic('active', 'boolean');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('active', $fieldSet);


        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('repetitions/0/type', 'static-ndom');
        $field->ignorableFields = ['repetitions/0/description', 'repetitions/0/occurrences'];
        $fieldSet->addField($field);
        $fieldSet->addField(Field::createBasic('repetitions/0/moment', 'moment-ndom'));
        $configuration->addOptionalFieldSet('ndom', $fieldSet);


        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('repetitions/0/type', 'static-monthly');
        $field->ignorableFields = ['repetitions/0/description', 'repetitions/0/occurrences'];
        $fieldSet->addField($field);
        $fieldSet->addField(Field::createBasic('repetitions/0/moment', 'moment-monthly'));
        $configuration->addOptionalFieldSet('monthly', $fieldSet);


        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('repetitions/0/type', 'static-yearly');
        $field->ignorableFields = ['repetitions/0/description', 'repetitions/0/occurrences'];
        $fieldSet->addField($field);
        $fieldSet->addField(Field::createBasic('repetitions/0/moment', 'random-past-date'));
        $configuration->addOptionalFieldSet('yearly', $fieldSet);


        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('repetitions/0/skip', 'random-skip');
        $field->ignorableFields = ['repetitions/0/description', 'repetitions/0/occurrences'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('skip', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('repetitions/0/weekend', 'weekend');
        $field->ignorableFields = ['repetitions/0/description', 'repetitions/0/occurrences'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('weekend', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('transactions/0/foreign_amount', 'random-amount'));
        $field                  = Field::createBasic('transactions/0/foreign_currency_id', 'random-currency-id');
        $field->ignorableFields = ['transactions/0/foreign_currency_code', 'transactions/0/foreign_currency_symbol'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('foreign1', $fieldSet);


        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('transactions/0/budget_id', 'random-budget-id');
        $field->ignorableFields = ['transactions/0/budget_name'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('budget', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('transactions/0/category_id', 'random-category-id');
        $field->ignorableFields = ['transactions/0/category_name'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('category', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('transactions/0/tags', 'random-tags'));
        $configuration->addOptionalFieldSet('tags', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('transactions/0/piggy_bank_id', 'random-piggy-id');
        $field->ignorableFields = ['transactions/0/piggy_bank_name'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('piggy', $fieldSet);

        return $configuration->generateAll();
    }
}