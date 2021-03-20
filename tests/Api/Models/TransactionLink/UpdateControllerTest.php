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

namespace Tests\Api\Models\TransactionLink;


use Faker\Factory;
use Laravel\Passport\Passport;
use Log;
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
        $ignore = [
            'created_at',
            'updated_at',
        ];
        $route  = route('api.v1.transaction_links.update', [$submission['id']]);

        $this->updateAndCompare($route, $submission, $ignore);
    }


    /**
     * @return array
     */
    public function updateDataProvider(): array
    {
        $submissions = [];
        $all         = $this->updateDataSet();
        foreach ($all as $name => $data) {
            $submissions[] = [$data];
        }

        return $submissions;
    }


    /**
     * @return array
     */
    public function updateDataSet(): array
    {
        $faker = Factory::create();
        $set   = [
            'link_type_id'   => [
                'id'           => 1,
                'fields'       => [
                    'link_type_id' => ['test_value' => (string)$faker->numberBetween(1, 3)],
                ],
                'extra_ignore' => ['link_type_name'],
            ],
            'link_type_name' => [
                'id'           => 1,
                'fields'       => [
                    'link_type_name' => ['test_value' => 'Refund'],
                ],
                'extra_ignore' => ['link_type_id'],
            ],
            'inward_id'      => [
                'id'           => 1,
                'fields'       => [
                    'inward_id' => ['test_value' => (string)$faker->numberBetween(11, 20)],
                ],
                'extra_ignore' => [],
            ],
            'outward_id'     => [
                'id'           => 1,
                'fields'       => [
                    'outward_id' => ['test_value' => (string)$faker->numberBetween(11, 30)],
                ],
                'extra_ignore' => [],
            ],
            'notes'          => [
                'id'           => 1,
                'fields'       => [
                    'notes' => ['test_value' => join(' ', $faker->words(5))],
                ],
                'extra_ignore' => [],
            ],
        ];

        return $set;
    }


}