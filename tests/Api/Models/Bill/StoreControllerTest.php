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

namespace Tests\Api\Models\Bill;


use Laravel\Passport\Passport;
use Log;
use Tests\Objects\Field;
use Tests\Objects\FieldSet;
use Tests\Objects\TestConfiguration;
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
        // some test configs:
        $configuration = new TestConfiguration;

        // default asset account test set:
        $defaultAssetSet        = new FieldSet();
        $defaultAssetSet->title = 'default_file';
        $defaultAssetSet->addField(Field::createBasic('name', 'uuid'));
        $defaultAssetSet->addField(Field::createBasic('amount_min', 'random-amount-min'));
        $defaultAssetSet->addField(Field::createBasic('amount_max', 'random-amount-max'));
        $defaultAssetSet->addField(Field::createBasic('date', 'random-past-date'));
        $defaultAssetSet->addField(Field::createBasic('repeat_freq', 'random-bill-repeat-freq'));
        $configuration->addMandatoryFieldSet($defaultAssetSet);

        // optional field sets
        $fieldSet               = new FieldSet;
        $field                  = new Field;
        $field->fieldTitle      = 'currency_id';
        $field->fieldType       = 'random-currency-id';
        $field->ignorableFields = ['currency_code'];
        $field->title           = 'currency_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_id', $fieldSet);

        $fieldSet               = new FieldSet;
        $field                  = new Field;
        $field->fieldTitle      = 'currency_code';
        $field->fieldType       = 'random-currency-code';
        $field->ignorableFields = ['currency_id'];
        $field->title           = 'currency_code';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_code', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('name', 'uuid'));
        $configuration->addOptionalFieldSet('name', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('amount_min', 'random-amount-min'));
        $configuration->addOptionalFieldSet('amount_min', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('amount_max', 'random-amount-max'));
        $configuration->addOptionalFieldSet('amount_max', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('date', 'random-past-date'));
        $configuration->addOptionalFieldSet('date', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('repeat_freq', 'random-bill-repeat-freq'));
        $configuration->addOptionalFieldSet('repeat_freq', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('skip', 'random-skip'));
        $configuration->addOptionalFieldSet('skip', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('active', 'boolean'));
        $configuration->addOptionalFieldSet('active', $fieldSet);


        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'object_group_id';
        $field->fieldType       = 'random-og-id';
        $field->ignorableFields = ['object_group_title', 'object_group_order'];
        $field->title           = 'object_group_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('object_group_id', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'object_group_title';
        $field->fieldType       = 'uuid';
        $field->ignorableFields = ['object_group_id', 'object_group_order'];
        $field->title           = 'object_group_title';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('object_group_title', $fieldSet);

        return $configuration->generateAll();
    }


    /**
     * @param array $submission
     *
     * emptyDataProvider / storeDataProvider
     *
     * @dataProvider emptyDataProvider
     */
    public function testStore(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty provider.');
        }
        Log::debug('testStoreUpdated()');
        Log::debug('submission       :', $submission['submission']);
        Log::debug('expected         :', $submission['expected']);
        Log::debug('ignore           :', $submission['ignore']);
        // run account store with a minimal data set:
        $address = route('api.v1.bills.store');
        $this->assertPOST($address, $submission);
    }

}