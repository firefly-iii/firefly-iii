<?php

/*
 * ObjectGroupControllerTest.php
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

use FireflyIII\Models\ObjectGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;
use FireflyIII\User;
use FireflyIII\Models\UserGroup;

/**
 * Class ObjectGroupControllerTest
 *
 * @internal
 *
 * @coversNothing
 */
final class ObjectGroupControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Api\V1\Controllers\Autocomplete\ObjectGroupController
     */
    use RefreshDatabase;

    protected function createAuthenticatedUser(): User
    {
        $userGroup           = UserGroup::create(['title' => 'Test Group']);


        $user                = User::create([
            'email'         => 'test@email.com',
            'password'      => 'password',
        ]);
        $user->user_group_id = $userGroup->id;
        $user->save();

        return $user;
    }

    private function createTestObjectGroups(int $count, User $user): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $objectGroup = ObjectGroup::create([
                'title'         => 'Object Group '.$i,
                'order'         => $i,
                'user_group_id' => $user->user_group_id,
                'user_id'       => $user->id,
            ]);
        }
    }

    public function testGivenAnUnauthenticatedRequestWhenCallingTheObjectGroupEndpointThenReturn401HttpCode(): void
    {
        $response = $this->get(route('api.v1.autocomplete.object-groups'), ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertContent('{"message":"Unauthenticated","exception":"AuthenticationException"}');
    }

    public function testGivenAuthenticatedRequestWhenCallingTheObjectGroupsEndpointThenReturns200HttpCode(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        // test API
        $response = $this->get(route('api.v1.autocomplete.object-groups'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function testGivenAuthenticatedRequestWhenCallingTheObjectGroupsEndpointThenReturnsObjectGroups(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestObjectGroups(5, $user);
        $response = $this->get(route('api.v1.autocomplete.object-groups'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(5);
        $response->assertJsonFragment(['title' => 'Object Group 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'title',
            ],
        ]);
    }

    public function testGivenAuthenticatedRequestWhenCallingTheObjectGroupsEndpointWithQueryThenReturnsObjectGroupsWithLimit(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestObjectGroups(5, $user);
        $response = $this->get(route('api.v1.autocomplete.object-groups', [
            'query' => 'Object Group',
            'limit' => 3,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(3);
        $response->assertJsonFragment(['name' => 'Object Group 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'title',
            ],
        ]);

    }

    public function testGivenAuthenticatedRequestWhenCallingTheObjectGroupsEndpointWithQueryThenReturnsObjectGroupsThatMatchQuery(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestObjectGroups(20, $user);
        $response = $this->get(route('api.v1.autocomplete.object-groups', [
            'query' => 'Object Group 1',
            'limit' => 20,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        // Object Group 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (11)
        $response->assertJsonCount(11);
        $response->assertJsonMissing(['name' => 'Object Group 2']);
    }
}
