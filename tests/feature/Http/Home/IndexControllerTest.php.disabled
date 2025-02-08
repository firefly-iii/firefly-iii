<?php
/*
 * IndexControllerTest.php
 * Copyright (c) 2025 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tests\feature\Http\Home;


use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\feature\TestCase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(302);
    }

    public function test_debug(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->get('/debug');

        $response->assertStatus(200);
    }
}
