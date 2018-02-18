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

use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Class AboutControllerTest
 */
class AboutControllerTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());
    }

    /**
     * Test the about endpoint
     *
     * @covers \FireflyIII\Api\V1\Controllers\AboutController::__construct
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
     * Test user end point
     *
     * @covers \FireflyIII\Api\V1\Controllers\AboutController::user
     */
    public function testUser()
    {
        // test API
        $response = $this->get('/api/v1/about/user');
        $response->assertStatus(200);
        $response->assertJson(['data' => ['attributes' => true, 'links' => true]]);
        $this->assertEquals($this->user()->id, $response->json()['data']['id']);
    }


}