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

namespace Tests\Api\Models\Rule;


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
        $defaultSet->title = 'default_rule_id';
        $defaultSet->addField(Field::createBasic('title', 'uuid'));
        $defaultSet->addField(Field::createBasic('rule_group_id', 'random-rule-group-id'));
        $defaultSet->addField(Field::createBasic('trigger', 'random-trigger'));
        $defaultSet->addField(Field::createBasic('triggers/0/type', 'random-trigger-type'));
        $defaultSet->addField(Field::createBasic('triggers/0/value', 'uuid'));
        $defaultSet->addField(Field::createBasic('actions/0/type', 'random-action-type'));
        $defaultSet->addField(Field::createBasic('actions/0/value', 'uuid'));
        $configuration->addMandatoryFieldSet($defaultSet);

        $defaultSet        = new FieldSet();
        $defaultSet->title = 'default_rule_name';
        $defaultSet->addField(Field::createBasic('title', 'uuid'));
        $defaultSet->addField(Field::createBasic('rule_group_title', 'random-rule-group-title'));
        $defaultSet->addField(Field::createBasic('trigger', 'random-trigger'));
        $defaultSet->addField(Field::createBasic('triggers/0/type', 'random-trigger-type'));
        $defaultSet->addField(Field::createBasic('triggers/0/value', 'uuid'));
        $defaultSet->addField(Field::createBasic('actions/0/type', 'random-action-type'));
        $defaultSet->addField(Field::createBasic('actions/0/value', 'uuid'));
        $configuration->addMandatoryFieldSet($defaultSet);

        // add optional set
        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('order', 'low-order'));
        $configuration->addOptionalFieldSet('order', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('active', 'boolean'));
        $configuration->addOptionalFieldSet('active', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('strict', 'boolean'));
        $configuration->addOptionalFieldSet('strict', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('stop_processing', 'boolean'));
        $configuration->addOptionalFieldSet('stop_processing', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('triggers/0/stop_processing', 'boolean'));
        $configuration->addOptionalFieldSet('stop_processingX', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('triggers/0/active', 'boolean'));
        $configuration->addOptionalFieldSet('activeX', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('actions/0/active', 'boolean'));
        $configuration->addOptionalFieldSet('activeXX', $fieldSet);


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
        $address = route('api.v1.rules.store');
        $this->assertPOST($address, $submission);
    }

}