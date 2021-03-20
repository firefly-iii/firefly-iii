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

namespace Tests\Api\Models\Account;


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
     * Only create optional sets.
     *
     * @return array
     */
    public function newUpdateDataProvider(): array
    {
        $configuration = new TestConfiguration;

        // optional field sets (for all test configs)
        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('name', 'uuid'));
        $configuration->addOptionalFieldSet('name', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('active', 'boolean'));
        $configuration->addOptionalFieldSet('active', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('iban', 'iban'));
        $configuration->addOptionalFieldSet('iban', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('bic', 'bic'));
        $configuration->addOptionalFieldSet('bic', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('account_number', 'iban'));
        $configuration->addOptionalFieldSet('account_number', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('order', 'order'));
        $configuration->addOptionalFieldSet('order', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('include_net_worth', 'boolean'));
        $configuration->addOptionalFieldSet('include_net_worth', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('virtual_balance', 'random-amount');
        $field->ignorableFields = ['current_balance'];
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('virtual_balance', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'currency_id';
        $field->fieldType       = 'random-currency-id';
        $field->ignorableFields = ['currency_code', 'currency_symbol', 'current_balance'];
        $field->title           = 'currency_id';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_id', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = new Field;
        $field->fieldTitle      = 'currency_code';
        $field->fieldType       = 'random-currency-code';
        $field->ignorableFields = ['currency_id', 'currency_symbol', 'current_balance'];
        $field->title           = 'currency_code';
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('currency_code', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('account_role', 'random-asset-accountRole'));
        $configuration->addOptionalFieldSet('account_role', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('latitude', 'latitude'));
        $fieldSet->addField(Field::createBasic('longitude', 'longitude'));
        $fieldSet->addField(Field::createBasic('zoom_level', 'random-zoom_level'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [1];
        $field                  = Field::createBasic('opening_balance', 'random-amount');
        $field->ignorableFields = ['current_balance'];
        $fieldSet->addField($field);
        $fieldSet->addField(Field::createBasic('opening_balance_date', 'random-past-date'));
        $configuration->addOptionalFieldSet('ob', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [7];
        $fieldSet->addField(Field::createBasic('account_role', 'static-ccAsset'));
        $fieldSet->addField(Field::createBasic('credit_card_type', 'static-monthlyFull'));
        $fieldSet->addField(Field::createBasic('monthly_payment_date', 'random-past-date'));
        $configuration->addOptionalFieldSet('cc1', $fieldSet);

        $fieldSet               = new FieldSet;
        $fieldSet->parameters   = [13];
        $field                  = new Field;
        $field->fieldTitle      = 'liability_type';
        $field->fieldType       = 'random-liability-type';
        $field->ignorableFields = ['account_role'];
        $field->title           = 'liability_type';
        $fieldSet->addField($field);
        $fieldSet->addField(Field::createBasic('account_role', 'null'));
        $fieldSet->addField(Field::createBasic('credit_card_type', 'null'));
        $fieldSet->addField(Field::createBasic('monthly_payment_date', 'null'));
        $configuration->addOptionalFieldSet('liability-1', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [13];
        $fieldSet->addField(Field::createBasic('interest', 'random-percentage'));
        $field                  = new Field;
        $field->fieldTitle      = 'interest_period';
        $field->fieldType       = 'random-interest-period';
        $field->ignorableFields = ['account_role'];
        $field->title           = 'interest_period';
        $fieldSet->addField($field);
        $fieldSet->addField(Field::createBasic('account_role', 'null'));
        $fieldSet->addField(Field::createBasic('credit_card_type', 'null'));
        $fieldSet->addField(Field::createBasic('monthly_payment_date', 'null'));
        $configuration->addOptionalFieldSet('liability-2', $fieldSet);

        return $configuration->generateAll();
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
     * @param array $submission
     *
     * newStoreDataProvider / emptyDataProvider
     *
     * @dataProvider newUpdateDataProvider
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

        $route = route('api.v1.accounts.update', $submission['parameters']);
        $this->assertPUT($route, $submission);
    }
}