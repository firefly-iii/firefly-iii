<?php

/*
 * CurrencyControllerTest.php
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

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;
use FireflyIII\User;
use FireflyIII\Models\UserGroup;

/**
 * Class CurrencyControllerTest
 *
 * @internal
 *
 * @coversNothing
 */
final class CurrencyControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Api\V1\Controllers\Autocomplete\CurrencyController
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

    private function createTestCurrencies(int $count, bool $enabled): void
    {
        for ($i = 1; $i <= $count; ++$i) {
            $currency = TransactionCurrency::create([
                'name'           => 'Currency '.$i,
                'code'           => 'CUR'.$i,
                'symbol'         => 'C'.$i,
                'decimal_places' => $i,
                'enabled'        => $enabled,
            ]);
        }
    }

    public function testGivenAnUnauthenticatedRequestWhenCallingTheCurrenciesEndpointThenReturns401HttpCode(): void
    {
        // test API
        $response = $this->get(route('api.v1.autocomplete.currencies'), ['Accept' => 'application/json']);
        $response->assertStatus(401);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertContent('{"message":"Unauthenticated","exception":"AuthenticationException"}');
    }

    public function testGivenAuthenticatedRequestWhenCallingTheCurrenciesEndpointThenReturns200HttpCode(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        // test API
        $response = $this->get(route('api.v1.autocomplete.currencies'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function testGivenAuthenticatedRequestWhenCallingTheCurrenciesEndpointThenReturnsACollectionOfEnabledCurrencies(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        // create test data
        $this->createTestCurrencies(9, true);

        // test API
        $response = $this->get(route('api.v1.autocomplete.currencies'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonFragment(['name' => 'Currency 1']);
        $response->assertJsonFragment(['code' => 'CUR1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'code',
                'symbol',
                'decimal_places',
            ],
        ]);

        $response->assertJsonCount(10);
    }

    public function testGivenAuthenticatedRequestWhenCallingTheCurrenciesEndpointDoesNotReturnDisabledCurrencies(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        // create test data
        $this->createTestCurrencies(10, false);

        // test API
        $response = $this->get(route('api.v1.autocomplete.currencies'), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonCount(1); // always connects to EUR.
    }

    public function testGivenAuthenticatedRequestWhenCallingTheCurrenciesEndpointWithQueryThenReturnsCurrenciesWithLimit(): void
    {
        // act as a user
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        // create test data
        $this->createTestCurrencies(5, true);

        // test API
        $response = $this->get(route('api.v1.autocomplete.currencies', ['query' => 'Currency 1']), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonFragment(['name' => 'Currency 1']);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'code',
                'symbol',
                'decimal_places',
            ],
        ]);

        $response->assertJsonCount(1);

    }

    public function testGivenAuthenticatedRequestWhenCallingTheCurrenciesEndpointWithQueryThenReturnsCurrenciesThatMatchQuery(): void
    {
        $user     = $this->createAuthenticatedUser();
        $this->actingAs($user);

        $this->createTestCurrencies(20, true);
        $response = $this->get(route('api.v1.autocomplete.currencies', [
            'query' => 'Currency 1',
            'limit' => 20,
        ]), ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        // Currency 1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (11)
        $response->assertJsonCount(11);
    }
}
