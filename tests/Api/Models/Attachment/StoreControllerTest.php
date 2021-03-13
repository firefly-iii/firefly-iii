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


use Faker\Factory;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;
use Tests\Traits\CollectsValues;
use Tests\Traits\RandomValues;
use Tests\Traits\TestHelpers;

/**
 * Class StoreControllerTest
 */
class StoreControllerTest extends TestCase
{
    use RandomValues, TestHelpers, CollectsValues;

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
     * @dataProvider storeDataProvider
     * @ data Provider emptyDataProvider
     */
    public function testStore(array $submission): void
    {
        if ([] === $submission) {
            $this->markTestSkipped('Empty data provider');
        }
        // run account store with a minimal data set:
        $route = 'api.v1.attachments.store';
        $this->submitAndCompare($route, $submission);
    }

    /**
     * @return array
     */
    public function emptyDataProvider(): array
    {
        return [[[]]];

    }


    /**
     * @return array
     */
    public function storeDataProvider(): array
    {
        $minimalSets  = $this->minimalSets();
        $optionalSets = $this->optionalSets();
        $regenConfig  = [
            //            'name'           => function () {
            //                $faker = Factory::create();
            //
            //                return $faker->name;
            //            },
            //            'iban'           => function () {
            //                $faker = Factory::create();
            //
            //                return $faker->iban();
            //            },
            //            'account_number' => function () {
            //                $faker = Factory::create();
            //
            //                return $faker->iban();
            //            },
        ];

        return $this->genericDataProvider($minimalSets, $optionalSets, $regenConfig);
    }


    /**
     * @return array
     */
    private function minimalSets(): array
    {
        $faker = Factory::create();
        $types = [
            'Account',
            'Budget',
            'Bill',
            'TransactionJournal',
            'PiggyBank',
            'Tag',
        ];
        $type  = $types[rand(0, count($types) - 1)];

        return [
            'default_file' => [
                'fields' => [
                    'filename'        => $faker->randomAscii,
                    'attachable_type' => $type,
                    'attachable_id'   => '1',
                ],
            ],
        ];
    }

    /**
     * @return \array[][]
     */
    private function optionalSets(): array
    {
        $faker      = Factory::create();
        return [
            'title' => [
                'fields' => [
                    'title' => $faker->randomAscii,
                ],
            ],
            'notes' => [
                'fields' => [
                    'notes' => join(' ', $faker->words(5)),
                ],
            ],
        ];
    }
}