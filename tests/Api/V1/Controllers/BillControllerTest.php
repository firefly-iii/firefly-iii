<?php
/**
 * BillControllerTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tests\Api\V1\Controllers;


use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Class BillControllerTest
 */
class BillControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());

    }

    /**
     * Send delete
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController::delete
     * @covers \FireflyIII\Api\V1\Controllers\BillController::__construct
     */
    public function testDelete()
    {
        // mock stuff:
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get account:
        $bill = $this->user()->bills()->first();

        // call API
        $response = $this->delete('/api/v1/bills/' . $bill->id);
        $response->assertStatus(204);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BillController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\BillController::index
     */
    public function testIndex()
    {
        // create stuff
        $bills     = factory(Bill::class, 10)->create();
        $paginator = new LengthAwarePaginator($bills, 10, 50, 1);
        // mock stuff:
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getPaginator')->withAnyArgs()->andReturn($paginator)->once();

        // test API
        $response = $this->get('/api/v1/bills');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'per_page' => 50, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BillController::show
     */
    public function testShow()
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();

        // test API
        $response = $this->get('/api/v1/bills/' . $bill->id);
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'type' => 'bills',
                'id'   => $bill->id,
            ],]
        );
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BillController::store
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::rules
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::authorize
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::getAll
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::withValidator
     */
    public function testStoreMinOverMax()
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . rand(1, 1000),
            'match'       => 'some,words,' . rand(1, 1000),
            'amount_min'  => '66.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,

        ];

        // test API
        $response = $this->post('/api/v1/bills', $data, ['Accept' => 'application/json']);
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
     * @covers \FireflyIII\Api\V1\Controllers\BillController::store
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::rules
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::authorize
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::getAll
     */
    public function testStoreValid()
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . rand(1, 1000),
            'match'       => 'some,words,' . rand(1, 1000),
            'amount_min'  => '12.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,

        ];

        // test API
        $response = $this->post('/api/v1/bills', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($bill->name);
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\BillController::update
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::rules
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::authorize
     * @covers \FireflyIII\Api\V1\Requests\BillRequest::getAll
     */
    public function testUpdateValid()
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('update')->andReturn($bill);

        // data to submit:
        $data = [
            'name'        => 'New bill #' . rand(1, 1000),
            'match'       => 'some,words,' . rand(1, 1000),
            'amount_min'  => '12.34',
            'amount_max'  => '45.67',
            'date'        => '2018-01-01',
            'repeat_freq' => 'monthly',
            'skip'        => 0,
            'automatch'   => 1,
            'active'      => 1,

        ];

        // test API
        $response = $this->put('/api/v1/bills/' . $bill->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($bill->name);
    }

}