<?php
/**
 * AccountControllerTest.php
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

use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
{
    /** @var array */
    protected $transformed
        = [
            'id'    => 1,
            'name'  => 'Some account',
            'links' => [
                'rel' => 'self',
                'uri' => '/accounts/1',
            ],
        ];

    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\AccountController::index
     */
    public function testIndex()
    {
        // mock stuff:
        $repository  = $this->mock(AccountRepositoryInterface::class);
        $transformer = $this->overload(AccountTransformer::class);
        $transformer->shouldReceive('setCurrentScope')->andReturnSelf();
        $transformer->shouldReceive('transform')->andReturn($this->transformed);

        $accounts = factory(Account::class, 10)->create();
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('getAccountsByType')->withAnyArgs()->andReturn($accounts)->once();

        $response = $this->get('/api/v1/accounts');
        $response->assertStatus(200);
        $response->assertJson(['data' => []]);
        $response->assertJson(['meta' => []]);
        $response->assertJson(['links' => []]);
        $response->assertSee('type=all'); // default returns this.
    }


}