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

use FireflyIII\Models\Bill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;
use FireflyIII\User;
use FireflyIII\Models\UserGroup;

/**
 * Class BillControllerTest
 *
 * @internal
 *
 * @coversNothing
 */
final class BillControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Api\V1\Controllers\Autocomplete\BillController
     */
    use RefreshDatabase;

    protected function createAuthenticatedUser(): User
    {
        $userGroup = UserGroup::create(['title' => 'Test Group']);

        return User::create([
            'email'         => 'test@email.com',
            'password'      => 'password',
            'user_group_id' => $userGroup->id,
        ]);
    }

    private function createTestBills(int $count, User $user): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $bill = Bill::create([
                'user_id'       => $user->id,
                'name'          => 'Bill '.$i,
                'user_group_id' => $user->user_group_id,
                'amount_min'    => rand(1, 100), // random amount
                'amount_max'    => rand(101, 200), // random amount
                'match'         => 'MIGRATED_TO_RULES',
                'date'          => '2024-01-01',
                'repeat_freq'   => 'monthly',
                'automatch'     => 1,

            ]);
        }
    }

    public function testGivenAnUnauthenticatedRequestWhenCallingTheBillsEndpointThenReturns401HttpCode(): void
    {
        // test API
        $response = $this->get(route('api.v1.autocomplete.bills'), ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertContent('{"message":"Unauthenticated","exception":"AuthenticationException"}');
    }

    public function testGivenAuthenticatedRequestWhenCallingTheBillsEndpointThenReturns200HttpCode(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $response = $this->get(route('api.v1.autocomplete.bills'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');

    }

    public function testGivenAuthenticatedRequestWhenCallingTheBillsEndpointThenReturnsBills(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestBills(5, $user);
        $response = $this->get(route('api.v1.autocomplete.bills'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(5);
        $response->assertJsonFragment(['name' => 'Bill 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'active',
            ],
        ]);

    }

    public function testGivenAuthenticatedRequestWhenCallingTheBillsEndpointWithQueryThenReturnsBillsWithLimit(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestBills(5, $user);
        $response = $this->get(route('api.v1.autocomplete.bills', [
            'query' => 'Bill',
            'limit' => 3,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(3);
        $response->assertJsonFragment(['name' => 'Bill 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'active',
            ],
        ]);

    }

    public function testGivenAuthenticatedRequestWhenCallingTheBillsEndpointWithQueryThenReturnsBillsThatMatchQuery(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestBills(20, $user);
        $response = $this->get(route('api.v1.autocomplete.bills', [
            'query' => 'Bill 1',
            'limit' => 20,
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        // Bill 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (11)
        $response->assertJsonCount(11);
        $response->assertJsonMissing(['name' => 'Bill 2']);
    }
}
