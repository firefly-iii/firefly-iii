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
        $defaultSet->addField(Field::createBasic('code', 'random-new-currency-code'));
        $defaultSet->addField(Field::createBasic('name', 'uuid'));
        $defaultSet->addField(Field::createBasic('symbol', 'random-new-currency-symbol'));

        //                     'code'   => substr($faker->uuid, 0, 3),
        //                    'name'   => $faker->uuid,
        //                    'symbol' => $faker->randomAscii . $faker->randomAscii,

        $configuration->addMandatoryFieldSet($defaultSet);

        // optionals
        $fieldSet = new FieldSet;
        $field    = Field::createBasic('enabled', 'boolean');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('enabled', $fieldSet);

        $fieldSet = new FieldSet;
        $field    = Field::createBasic('default', 'boolean');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('default', $fieldSet);

        $fieldSet = new FieldSet;
        $field    = Field::createBasic('decimal_places', 'currency-dp');
        $fieldSet->addField($field);
        $configuration->addOptionalFieldSet('decimal_places', $fieldSet);


        return $configuration->generateAll();

    }

    /**
     * @param array $submission
     *
     * emptyDataProvider / storeDataProvider
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
        $address = route('api.v1.currencies.store');
        $this->assertPOST($address, $submission);
    }

}