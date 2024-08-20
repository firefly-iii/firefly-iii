<?php
/*
 * AccountControllerTest.php
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

namespace Tests\integration\Api\Autocomplete;

use FireflyIII\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;
use FireflyIII\User;

/**
 * Class CategoryControllerTest
 *
 * @internal
 *
 * @coversNothing
 */
final class CategoryControllerTest extends TestCase {
    /**
     * @covers \FireflyIII\Api\V1\Controllers\Autocomplete\CategoryController
     */
    use RefreshDatabase;

    private function createAuthenticatedUser(): User {
        return User::create([
            'email' => 'test@email.com',
            'password' => 'password',
            ]);
        }

    private function createTestCategories(int $count, User $user): void {
        for ($i = 1; $i <= $count; $i++) {
            $category = Category::create([
                'user_id' => $user->id,
                'name' => 'Category ' . $i,
                'user_group_id' => $user->user_group_id,
            ]);
        }
    }
    

    public function testGivenAnUnauthenticatedRequestWhenCallingTheCategoriesEndpointThenReturns401HttpCode(): void {
        // test API
        $response = $this->get(route('api.v1.autocomplete.categories'), ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertContent('{"message":"Unauthenticated","exception":"AuthenticationException"}');
    }
    
    public function testGivenAuthenticatedRequestWhenCallingTheCategoriesEndpointThenReturns200HttpCode(): void
    {
        // act as a user
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $response = $this->get(route('api.v1.autocomplete.categories'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

    }

    public function testGivenAuthenticatedRequestWhenCallingTheCategoriesEndpointThenReturnsCategories(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestCategories(5, $user);
        $response = $this->get(route('api.v1.autocomplete.categories'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(5);
        $response->assertJsonFragment(['name' => 'Category 1']);
          $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
            ],
        ]);
    }

    public function testGivenAuthenticatedRequestWhenCallingTheCategoriesEndpointWithQueryThenReturnsCategoriesWithLimit(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestCategories(5, $user);
        $response = $this->get(route('api.v1.autocomplete.categories', [
            'query' => 'Category',
            'limit' => 3
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(3);
    }

    public function testGivenAuthenticatedRequestWhenCallingTheCategoriesEndpointWithQueryThenReturnsCategoriesThatMatchQuery(): void
    {
        $user = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestCategories(20, $user);
        $response = $this->get(route('api.v1.autocomplete.categories', [
            'query' => 'Category 1',
            'limit' => 20,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        // Category 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (11)
        $response->assertJsonCount(11);
        $response->assertJsonMissing(['name' => 'Category 2']);
    }
    
}