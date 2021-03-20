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

namespace Tests\Api\Models\Attachment;


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

        $route = route('api.v1.attachments.update', $submission['parameters']);
        $this->assertPUT($route, $submission);
    }


    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $configuration = new TestConfiguration;

        // optional field sets (for all test configs)
        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('filename', 'uuid'));
        $configuration->addOptionalFieldSet('filename', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('title', 'uuid'));
        $configuration->addOptionalFieldSet('title', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('notes', 'uuid'));
        $configuration->addOptionalFieldSet('notes', $fieldSet);

        $fieldSet             = new FieldSet;
        $fieldSet->parameters = [1];
        $fieldSet->addField(Field::createBasic('attachable_type', 'static-journal-type'));
        $fieldSet->addField(Field::createBasic('attachable_id', 'random-journal-id'));
        $configuration->addOptionalFieldSet('attachable_type', $fieldSet);
        return $configuration->generateAll();
    }
}