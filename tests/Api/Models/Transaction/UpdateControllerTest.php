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

namespace Tests\Api\Models\Transaction;
use Carbon\Carbon;
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
     *
     * @param array $submission
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

        $route = route('api.v1.transactions.update', $submission['parameters']);
        $this->assertPUT($route, $submission);
    }
    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $configuration = new TestConfiguration;

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('apply_rules', 'boolean'));
        $configuration->addOptionalFieldSet('apply_rules', $fieldSet);

        // add date
        $fieldSet              = new FieldSet();
        $fieldSet->parameters  = [1];
        $field                 = Field::createBasic('transactions/0/date', 'random-past-date');
        $field->expectedReturn = function ($value) {
            $date = new Carbon($value, 'Europe/Amsterdam');

            return $date->toIso8601String();
        };
        $fieldSet->addField($field);

        $configuration->addOptionalFieldSet('date', $fieldSet);

        // category
        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('transactions/0/category_id', 'random-category-id'));
        $configuration->addOptionalFieldSet('category_id', $fieldSet);

        // amount
        $fieldSet              = new FieldSet;
        $fieldSet->parameters  = [1];
        $field                 = Field::createBasic('transactions/0/amount', 'random-amount');
        $field->expectedReturn = function ($value) {
            return number_format((float)$value, 12);
        };
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('amount', $fieldSet);

        // descr
        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('transactions/0/description', 'uuid'));
        $configuration->addOptionalFieldSet('descr', $fieldSet);

        // source
        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('transactions/0/source_id', 'random-asset-id');
        $field->ignorableFields = ['transactions/0/source_name', 'transactions/0/source_iban'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('src', $fieldSet);

        // dest
        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('transactions/0/destination_id', 'random-expense-id');
        $field->ignorableFields = ['transactions/0/destination_name', 'transactions/0/destination_iban'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('dest', $fieldSet);
        // optional fields
        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('transactions/0/category_id', 'random-category-id');
        $field->ignorableFields = ['transactions/0/category_name'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('category_id', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('transactions/0/reconciled', 'boolean'));
        $configuration->addOptionalFieldSet('reconciled', $fieldSet);

        $fieldSet              = new FieldSet;
        $fieldSet->parameters  = [1];
        $field                 = Field::createBasic('transactions/0/tags', 'random-tags');
        $field->expectedReturn = function ($value) {
            if (is_array($value)) {
                asort($value);

                return array_values($value);
            }

            return $value;
        };
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('tags', $fieldSet);
        $array = ['notes', 'internal_reference', 'bunq_payment_id', 'sepa_cc', 'sepa_ct_op', 'sepa_ct_id',
                  'sepa_db', 'sepa_country', 'sepa_ep', 'sepa_ci', 'sepa_batch_id'];

        foreach ($array as $value) {
            $fieldSet             = new FieldSet;
            $fieldSet->parameters = [1];
            $fieldSet->addField(Field::createBasic('transactions/0/' . $value, 'uuid'));
            $configuration->addOptionalFieldSet($value, $fieldSet);
        }
        $result = $configuration->generateAll();

        return $result;
    }
}
