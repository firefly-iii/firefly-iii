<?php

/*
 * BudgetControllerTest.php
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

use FireflyIII\Models\Budget;
use FireflyIII\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;
use FireflyIII\User;

/**
 * Class BudgetControllerTest
 *
 * @internal
 *
 * @coversNothing
 */
final class BudgetControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Api\V1\Controllers\Autocomplete\BudgetController
     */
    use RefreshDatabase;

    #[\Override]
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

    private function createTestBudgets(int $count, User $user): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $budget = Budget::create([
                'user_id'       => $user->id,
                'name'          => 'Budget '.$i,
                'user_group_id' => $user->user_group_id,
                'active'        => 1,
            ]);
        }
    }

    public function testGivenAnUnauthenticatedRequestWhenCallingTheBudgetsEndpointThenReturns401HttpCode(): void
    {
        // test API
        $response = $this->get(route('api.v1.autocomplete.budgets'), ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertContent('{"message":"Unauthenticated","exception":"AuthenticationException"}');
    }

    public function testGivenAuthenticatedRequestWhenCallingTheBudgetsEndpointThenReturns200HttpCode(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $response = $this->get(route('api.v1.autocomplete.budgets'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

    }

    public function testGivenAuthenticatedRequestWhenCallingTheBudgetsEndpointThenReturnsBudgets(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestBudgets(5, $user);
        $response = $this->get(route('api.v1.autocomplete.budgets'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(5);
        $response->assertJsonFragment(['name' => 'Budget 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
            ],
        ]);
    }

    public function testGivenAuthenticatedRequestWhenCallingTheBudgetsEndpointWithQueryThenReturnsBudgetsWithLimit(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestBudgets(5, $user);
        $response = $this->get(route('api.v1.autocomplete.budgets', [
            'query' => 'Budget',
            'limit' => 3,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(3);
    }

    public function testGivenAuthenticatedRequestWhenCallingTheBudgetsEndpointWithQueryThenReturnsBudgetsThatMatchQuery(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestBudgets(20, $user);
        $response = $this->get(route('api.v1.autocomplete.budgets', [
            'query' => 'Budget 1',
            'limit' => 20,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        // Budget 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (11)
        $response->assertJsonCount(11);
        $response->assertJsonMissing(['name' => 'Budget 2']);
    }
}
