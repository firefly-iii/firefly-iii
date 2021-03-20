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

namespace Tests\Api\Models\Attachment;


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
        $address = route('api.v1.attachments.store');
        $this->assertPOST($address, $submission);
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
        $defaultAssetSet->addField(Field::createBasic('filename', 'uuid'));
        $defaultAssetSet->addField(Field::createBasic('attachable_type', 'random-attachment-type'));
        $defaultAssetSet->addField(Field::createBasic('attachable_id', 'static-one'));
        $configuration->addMandatoryFieldSet($defaultAssetSet);

        // optional field sets
        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('title', 'uuid'));
        $configuration->addOptionalFieldSet('title', $fieldSet);

        $fieldSet = new FieldSet;
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);


        // generate submissions
        $array    = $configuration->generateSubmissions();
        $expected = $configuration->generateExpected($array);
        $ignored  = $configuration->ignores;

        // now create a combination for each submission and associated data:
        $final = [];
        foreach ($array as $index => $submission) {
            $final[] = [[
                            'submission' => $submission,
                            'expected'   => $expected[$index],
                            'ignore'     => $ignored[$index],
                        ]];
        }

        return $final;
    }
}