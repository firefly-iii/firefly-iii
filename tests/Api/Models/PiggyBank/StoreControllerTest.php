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

declare(strict_types=1);

namespace Tests\Api\Models\PiggyBank;
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

        // default test set:
        $defaultSet        = new FieldSet();
        $defaultSet->title = 'default_object';
        $defaultSet->addField(Field::createBasic('name', 'uuid'));
        $defaultSet->addField(Field::createBasic('account_id', 'random-piggy-account'));
        $defaultSet->addField(Field::createBasic('target_amount', 'random-amount-max'));
        $configuration->addMandatoryFieldSet($defaultSet);

        // add optional set
        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('current_amount', 'random-amount-min'));
        $configuration->addOptionalFieldSet('current_amount', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('start_date', 'random-past-date'));
        $configuration->addOptionalFieldSet('start_date', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('target_date', 'random-future-date'));
        $configuration->addOptionalFieldSet('target_date', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('order', 'order'));
        $configuration->addOptionalFieldSet('order', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'object_group_id';
        $field->fieldType       = 'random-og-id';
        $field->ignorableFields = ['object_group_title'];
        $field->title           = 'object_group_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('object_group_id', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'object_group_title';
        $field->fieldType       = 'uuid';
        $field->ignorableFields = ['object_group_id'];
        $field->title           = 'object_group_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('object_group_title', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

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
        $address = route('api.v1.piggy_banks.store');
        $this->assertPOST($address, $submission);
    }

}
