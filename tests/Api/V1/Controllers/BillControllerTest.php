<?php
/**
 * BillControllerTest.php
 * Copyright (c) 2019 james@firefly-iii.org
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

namespace Tests\Api\V1\Controllers;


use Exception;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Transformers\BillTransformer;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * Class BillControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BillControllerTest extends TestCase
{
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
     * Store with minimum amount more than maximum amount
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @throws Exception
     */
    public function testStoreMinOverMax(): void
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . $this->randomInt(),
            'match'       => 'some,words,' . $this->randomInt(),
            'amount_min'  => '66.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'currency_id' => 1,
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,

        ];

        // test API
        $response = $this->post(route('api.v1.bills.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'amount_min' => ['The minimum amount cannot be larger than the maximum amount.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Store a valid bill
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @throws Exception
     */
    public function testStoreValid(): void
    {
        // create stuff
        $bill        = $this->user()->bills()->first();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('store')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . $this->randomInt(),
            'match'       => 'some,words,' . $this->randomInt(),
            'amount_min'  => '12.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,
            'currency_id' => 1,

        ];

        // test API
        $response = $this->post(route('api.v1.bills.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }


    /**
     * Update a valid bill.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @throws Exception
     */
    public function testUpdateValid(): void
    {
        // create stuff
        $bill        = $this->user()->bills()->first();
        $repository  = $this->mock(BillRepositoryInterface::class);
        $transformer = $this->mock(BillTransformer::class);

        // mock calls:
        $repository->shouldReceive('setUser')->atLeast()->once();
        $repository->shouldReceive('update')->andReturn($bill);
        // data to submit:
        $data = [
            'name'        => 'New bill #' . $this->randomInt(),
            'match'       => 'some,words,' . $this->randomInt(),
            'amount_min'  => '12.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,
            'currency_id' => 1,
        ];

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // test API
        $response = $this->put(route('api.v1.bills.update', [$bill->id]), $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

}
