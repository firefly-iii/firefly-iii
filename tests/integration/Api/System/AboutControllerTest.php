<?php

/*
 * AboutControllerTest.php
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

declare(strict_types=1);

namespace Tests\integration\Api\About;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\integration\TestCase;
use Override;

/**
 * Class AboutControllerTest
 *
 * @internal
 *
 * @coversNothing
 */
final class AboutControllerTest extends TestCase
{
    use RefreshDatabase;
    private $user;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (!isset($this->user)) {
            $this->user = $this->createAuthenticatedUser();
        }
        $this->actingAs($this->user);
    }

    public function testGivenAuthenticatedRequestReturnsSystemInformation(): void
    {
        $response = $this->getJson(route('api.v1.about.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'version',
                'api_version',
                'php_version',
                'os',
                'driver',
            ],
        ]);
    }

    public function testGivenAuthenticatedRequestReturnsUserInformation(): void
    {
        $response = $this->getJson(route('api.v1.about.user'));

        $response->assertOk();
        $response->assertJson(
            fn (AssertableJson $json) => $json
                ->where('data.attributes.email', $this->user->email)
                ->where('data.attributes.role', $this->user->role)
        );
    }
}
