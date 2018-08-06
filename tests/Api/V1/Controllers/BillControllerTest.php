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
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
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
        Log::debug(sprintf('Now in %s.', \get_class($this)));

    }

    /**
     * Send delete
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);

        // get bill:
        $bill = $this->user()->bills()->first();

        // call API
        $response = $this->delete('/api/v1/bills/' . $bill->id);
        $response->assertStatus(204);
    }

    /**
     * Show all bills
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testIndex(): void
    {
        // create stuff
        $bills     = factory(Bill::class, 10)->create();
        $paginator = new LengthAwarePaginator($bills, 10, 50, 1);
        // mock stuff:
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getPaginator')->withAnyArgs()->andReturn($paginator)->once();
        $repository->shouldReceive('getRulesForBill')->withAnyArgs()->andReturn(new Collection());

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
     * Show one bill
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     */
    public function testShow(): void
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getRulesForBill')->withAnyArgs()->andReturn(new Collection());
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
     * Store with minimum amount more than maximum amount
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @covers \FireflyIII\Api\V1\Requests\BillRequest
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
            'name'        => 'New bill #' . random_int(1, 10000),
            'match'       => 'some,words,' . random_int(1, 10000),
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
     * Store a valid bill
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @covers \FireflyIII\Api\V1\Requests\BillRequest
     */
    public function testStoreValid(): void
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->times(2);
        $repository->shouldReceive('store')->andReturn($bill);
        $repository->shouldReceive('getRulesForBill')->withAnyArgs()->andReturn(new Collection());
        // data to submit:
        $data = [
            'name'        => 'New bill #' . random_int(1, 10000),
            'match'       => 'some,words,' . random_int(1, 10000),
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
        $response = $this->post('/api/v1/bills', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($bill->name);
    }

    /**
     * Update a valid bill.
     *
     * @covers \FireflyIII\Api\V1\Controllers\BillController
     * @covers \FireflyIII\Api\V1\Requests\BillRequest
     */
    public function testUpdateValid(): void
    {
        // create stuff
        $bill       = $this->user()->bills()->first();
        $repository = $this->mock(BillRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->times(2);
        $repository->shouldReceive('update')->andReturn($bill);
        $repository->shouldReceive('getRulesForBill')->withAnyArgs()->andReturn(new Collection());
        // data to submit:
        $data = [
            'name'        => 'New bill #' . random_int(1, 10000),
            'match'       => 'some,words,' . random_int(1, 10000),
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
        $response = $this->put('/api/v1/bills/' . $bill->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'bills', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($bill->name);
    }

}
