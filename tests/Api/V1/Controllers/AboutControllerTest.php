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
use Log;
use Tests\TestCase;

/**
 * Class AboutControllerTest
 */
class AboutControllerTest extends TestCase
{
    /**
     * Set up test
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Test the about endpoint
     *
     * @covers \FireflyIII\Api\V1\Controllers\AboutController
     */
    public function testAbout(): void
    {

        $search     = ['~', '#'];
        $replace    = ['\~', '# '];
        $phpVersion = str_replace($search, $replace, PHP_VERSION);
        $phpOs      = str_replace($search, $replace, PHP_OS);
        $response   = $this->get(route('api.v1.about.index'));
        $response->assertStatus(200);
        $response->assertJson(
            ['data' => [
                'version'     => config('firefly.version'),
                'api_version' => config('firefly.api_version'),
                'php_version' => $phpVersion,
                'os'          => $phpOs,
                'driver'      => 'sqlite',
            ]]
        );
    }

    /**
     * Test user end point
     *
     * @covers \FireflyIII\Api\V1\Controllers\AboutController
     */
    public function testUser(): void
    {
        $response = $this->get(route('api.v1.about.user'));
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'attributes' => true,
                    'links'      => true,
                ],
            ]
        );
    }


}
