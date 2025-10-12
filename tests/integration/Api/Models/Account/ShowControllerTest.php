<?php


/*
 * AccountControllerTest.php
 * Copyright (c) 2025 james@firefly-iii.org
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

namespace Tests\integration\Api\Models\Account;

use FireflyIII\Enums\AccountTypeEnum;
use FireflyIII\Models\Account;
use FireflyIII\User;
use Override;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @covers \FireflyIII\Api\V1\Controllers\Models\Account\ShowController
 */
final class ShowControllerTest extends TestCase
{
    use RefreshDatabase;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        $this->actingAs($this->user);

        Account::factory()->for($this->user)->withType(AccountTypeEnum::ASSET)->create();
        Account::factory()->for($this->user)->withType(AccountTypeEnum::REVENUE)->create();
        Account::factory()->for($this->user)->withType(AccountTypeEnum::EXPENSE)->create();
        Account::factory()->for($this->user)->withType(AccountTypeEnum::DEBT)->create();
        Account::factory()->for($this->user)->withType(AccountTypeEnum::ASSET)->create();
    }

    public function testIndex(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.index'));
        $response->assertStatus(200);
        $response->assertJson([
            'meta' => ['pagination' => ['total' => 5]],
        ]);
    }

    public function testIndexFailsOnUnknownAccountType(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.index').'?type=foobar');
        $response->assertStatus(422);
        $response->assertJson(['errors' => ['type' => ['The selected type is invalid.']]]);
    }

    public function testIndexCanFilterOnAccountType(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.index').'?type=asset');
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                ['attributes' => ['type' => 'asset']],
                ['attributes' => ['type' => 'asset']],
            ],
            'meta' => ['pagination' => ['total' => 2]],
        ]);
    }
}
