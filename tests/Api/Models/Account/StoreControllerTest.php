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
        $defaultAssetSet->title = 'default_asset_account';
        $defaultAssetSet->addField(Field::createBasic('name', 'uuid'));
        $defaultAssetSet->addField(Field::createBasic('type', 'static-asset'));
        $defaultAssetSet->addField(Field::createBasic('account_role', 'random-asset-accountRole'));
        $configuration->addMandatoryFieldSet($defaultAssetSet);

        // expense test set:
        $expenseSet        = new FieldSet();
        $expenseSet->title = 'expense_account';
        $expenseSet->addField(Field::createBasic('name', 'uuid'));

        // to make sure expense set ignores the opening balance fields:
        $field                  = new Field;
        $field->title           = 'type';
        $field->fieldTitle      = 'type';
        $field->fieldType       = 'static-expense';
        $field->ignorableFields = ['opening_balance', 'opening_balance_date', 'virtual_balance', 'order'];
        $expenseSet->addField($field);
        $configuration->addMandatoryFieldSet($expenseSet);

        // liability test set:
        $fieldSet        = new FieldSet();
        $fieldSet->title = 'liabilities_account';
        $fieldSet->addField(Field::createBasic('name', 'uuid'));
        $fieldSet->addField(Field::createBasic('type', 'static-liabilities'));
        $fieldSet->addField(Field::createBasic('liability_type', 'random-liability-type'));
        $fieldSet->addField(Field::createBasic('liability_amount', 'random-amount'));
        $fieldSet->addField(Field::createBasic('interest', 'random-percentage'));
        $fieldSet->addField(Field::createBasic('interest_period', 'random-interest-period'));
        $field                  = new Field;
        $field->fieldTitle      = 'liability_start_date';
        $field->fieldType       = 'random-past-date';
        $field->ignorableFields = ['opening_balance', 'opening_balance_date'];
        $field->title           = 'liability_start_date';
        $fieldSet->addField($field);
        $configuration->addMandatoryFieldSet($fieldSet);

        // credit card set:
        $fieldSet        = new FieldSet();
        $fieldSet->title = 'cc_account';
        $fieldSet->addField(Field::createBasic('name', 'uuid'));
        $fieldSet->addField(Field::createBasic('type', 'static-asset'));
        $fieldSet->addField(Field::createBasic('account_role', 'static-ccAsset'));
        $fieldSet->addField(Field::createBasic('credit_card_type', 'static-monthlyFull'));
        $fieldSet->addField(Field::createBasic('monthly_payment_date', 'random-past-date'));
        $configuration->addMandatoryFieldSet($fieldSet);

        // optional field sets (for all test configs)
        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('active', 'boolean'));
        $configuration->addOptionalFieldSet('active', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('iban', 'iban'));
        $configuration->addOptionalFieldSet('iban', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('bic', 'bic'));
        $configuration->addOptionalFieldSet('bic', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('account_number', 'account_number'));
        $configuration->addOptionalFieldSet('account_number', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('opening_balance', 'random-amount'));
        $fieldSet->addField(Field::createBasic('opening_balance_date', 'random-past-date'));
        $configuration->addOptionalFieldSet('ob', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('virtual_balance', 'random-amount'));
        $configuration->addOptionalFieldSet('virtual_balance', $fieldSet);

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

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('order', 'order'));
        $configuration->addOptionalFieldSet('order', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('include_net_worth', 'boolean'));
        $configuration->addOptionalFieldSet('include_net_worth', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('latitude', 'latitude'));
        $fieldSet->addField(Field::createBasic('longitude', 'longitude'));
        $fieldSet->addField(Field::createBasic('zoom_level', 'random-zoom_level'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        return $configuration->generateAll();
    }

    /**
     * @param array $submission
     *
     * storeDataProvider / emptyDataProvider
     *
     * @dataProvider storeDataProvider
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
        $address = route('api.v1.accounts.store');
        $this->assertPOST($address, $submission);
    }

}