<?php

/*
 * BillControllerTest.php
 * Copyright (c) 2024 tasnim0tantawi
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

namespace Tests\integration\Api\Autocomplete;

use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class TagControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Api\V1\Controllers\Autocomplete\RecurrenceController
     */
    use RefreshDatabase;

    private function createTestTags(int $count, User $user): void
    {

        for ($i = 1; $i <= $count; ++$i) {
            $tag = Tag::create(
                [
                    'user_id'             => $user->id,
                    'user_group_id'       => $user->user_group_id,
                    'tag'                 => 'Tag '.$i,
                    'tag_mode'            => 'nothing',
                ]
            );
        }
    }

    public function testUnauthenticatedCall(): void
    {
        // test API
        $response = $this->get(route('api.v1.autocomplete.tags'), ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertContent('{"message":"Unauthenticated.","exception":"AuthenticationException"}');
    }

    public function testAuthenticatedCall(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $response = $this->get(route('api.v1.autocomplete.tags'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function testGivenAuthenticatedRequestWithItems(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestTags(5, $user);
        $response = $this->get(route('api.v1.autocomplete.tags'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(5);
        $response->assertJsonFragment(['name' => 'Tag 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'tag',
            ],
        ]);

    }

    public function testGivenAuthenticatedRequestWithItemsLimited(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestTags(5, $user);
        $response = $this->get(route('api.v1.autocomplete.tags', [
            'query' => 'Tag',
            'limit' => 3,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(3);
        $response->assertJsonFragment(['name' => 'Tag 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'tag',
            ],
        ]);

    }

    public function testGivenAuthenticatedRequestWithItemsLots(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestTags(20, $user);
        $response = $this->get(route('api.v1.autocomplete.tags', [
            'query' => 'Tag 1',
            'limit' => 20,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        // Bill 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (11)
        $response->assertJsonCount(11);
        $response->assertJsonMissing(['name' => 'Tag 2']);
    }
}
