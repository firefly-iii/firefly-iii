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

namespace Tests\Api\Models\Recurrence;


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
        $defaultSet->title = 'default_withdrawal';
        $defaultSet->addField(Field::createBasic('type', 'static-withdrawal'));
        $defaultSet->addField(Field::createBasic('title', 'uuid'));
        $defaultSet->addField(Field::createBasic('first_date', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('repeat_until', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('repetitions/0/type', 'static-type-weekly'));
        $defaultSet->addField(Field::createBasic('repetitions/0/moment', 'static-one'));
        $defaultSet->addField(Field::createBasic('transactions/0/description', 'uuid'));
        $defaultSet->addField(Field::createBasic('transactions/0/amount', 'random-amount'));
        $defaultSet->addField(Field::createBasic('transactions/0/source_id', 'random-asset-id'));
        $defaultSet->addField(Field::createBasic('transactions/0/destination_id', 'random-expense-id'));
        $configuration->addMandatoryFieldSet($defaultSet);

        $defaultSet        = new FieldSet();
        $defaultSet->title = 'default_withdrawal_2';
        $defaultSet->addField(Field::createBasic('type', 'static-withdrawal'));
        $defaultSet->addField(Field::createBasic('title', 'uuid'));
        $defaultSet->addField(Field::createBasic('first_date', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('nr_of_repetitions', 'random-nr-of-reps'));
        $defaultSet->addField(Field::createBasic('repetitions/0/type', 'static-type-weekly'));
        $defaultSet->addField(Field::createBasic('repetitions/0/moment', 'static-one'));
        $defaultSet->addField(Field::createBasic('transactions/0/description', 'uuid'));
        $defaultSet->addField(Field::createBasic('transactions/0/amount', 'random-amount'));
        $defaultSet->addField(Field::createBasic('transactions/0/source_id', 'random-asset-id'));
        $defaultSet->addField(Field::createBasic('transactions/0/destination_id', 'random-expense-id'));
        $configuration->addMandatoryFieldSet($defaultSet);


        $defaultSet        = new FieldSet();
        $defaultSet->title = 'default_deposit';
        $defaultSet->addField(Field::createBasic('type', 'static-deposit'));
        $defaultSet->addField(Field::createBasic('title', 'uuid'));
        $defaultSet->addField(Field::createBasic('first_date', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('repeat_until', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('repetitions/0/type', 'static-type-weekly'));
        $defaultSet->addField(Field::createBasic('repetitions/0/moment', 'static-one'));
        $defaultSet->addField(Field::createBasic('transactions/0/description', 'uuid'));
        $defaultSet->addField(Field::createBasic('transactions/0/amount', 'random-amount'));
        $defaultSet->addField(Field::createBasic('transactions/0/source_id', 'random-revenue-id'));
        $defaultSet->addField(Field::createBasic('transactions/0/destination_id', 'random-asset-id'));
        $configuration->addMandatoryFieldSet($defaultSet);

        $defaultSet        = new FieldSet();
        $defaultSet->title = 'default_transfer';
        $defaultSet->addField(Field::createBasic('type', 'static-transfer'));
        $defaultSet->addField(Field::createBasic('title', 'uuid'));
        $defaultSet->addField(Field::createBasic('first_date', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('repeat_until', 'random-future-date'));
        $defaultSet->addField(Field::createBasic('repetitions/0/type', 'static-type-weekly'));
        $defaultSet->addField(Field::createBasic('repetitions/0/moment', 'static-one'));
        $defaultSet->addField(Field::createBasic('transactions/0/description', 'uuid'));
        $defaultSet->addField(Field::createBasic('transactions/0/amount', 'random-amount'));
        $defaultSet->addField(Field::createBasic('transactions/0/source_id', 'random-other-asset-id'));
        $defaultSet->addField(Field::createBasic('transactions/0/destination_id', 'random-asset-id'));
        $configuration->addMandatoryFieldSet($defaultSet);

        // add optional set
        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('description', 'uuid'));
        $configuration->addOptionalFieldSet('description', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('apply_rules', 'boolean'));
        $configuration->addOptionalFieldSet('apply_rules', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('active', 'boolean'));
        $configuration->addOptionalFieldSet('active', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('repetitions/0/skip', 'random-skip'));
        $configuration->addOptionalFieldSet('skip', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('transactions/0/foreign_amount', 'random-amount'));
        $fieldSet->addField(Field::createBasic('transactions/0/foreign_currency_id', 'random-currency-id'));
        $configuration->addOptionalFieldSet('foreign1', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('transactions/0/budget_id', 'random-budget-id'));
        $configuration->addOptionalFieldSet('budget', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('transactions/0/category_id', 'random-category-id'));
        $configuration->addOptionalFieldSet('category', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('transactions/0/tags', 'random-tags'));
        $configuration->addOptionalFieldSet('tags', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->addField(Field::createBasic('transactions/0/piggy_bank_id', 'random-piggy-id'));
        $configuration->addOptionalFieldSet('piggy', $fieldSet);

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
        $address = route('api.v1.recurrences.store');
        $this->assertPOST($address, $submission);

    }

}