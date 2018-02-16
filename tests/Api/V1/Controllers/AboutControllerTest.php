<?php
/**
 * AboutControllerTest.php
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

use FireflyIII\Transformers\UserTransformer;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Class AboutControllerTest
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AboutControllerTest extends TestCase
{
    /** @var array */
    protected $transformed
        = [
            'id'    => 1,
            'email' => 'some@user',
            'links' => [
                'rel' => 'self',
                'uri' => '/users/1',
            ],
        ];

    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\AboutController::about
     */
    public function testAbout()
    {
        // test API
        $response = $this->get('/api/v1/about');
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'version'     => true,
                'api_version' => true,
                'php_version' => true,
            ]]
        );
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\AboutController::user
     */
    public function testUser()
    {
        // mock stuff:
        $transformer = $this->overload(UserTransformer::class);
        $transformer->shouldReceive('setCurrentScope')->andReturnSelf();
        $transformer->shouldReceive('transform')->andReturn($this->transformed);

        // test API
        $response = $this->get('/api/v1/about/user');
        $response->assertStatus(200);
        $response->assertJson(['data' => ['attributes' => true, 'links' => true]]);
    }


}