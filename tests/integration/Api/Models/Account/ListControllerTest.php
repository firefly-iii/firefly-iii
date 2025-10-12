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
use FireflyIII\Factory\AttachmentFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\Category;
use FireflyIII\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\integration\TestCase;

/**
 * @internal
 *
 * @covers \FireflyIII\Api\V1\Controllers\Models\Account\ListController
 */
final class ListControllerTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAuthenticatedUser();
        $this->actingAs($this->user);

        $this->account = Account::factory()->for($this->user)->withType(AccountTypeEnum::ASSET)->create();
        app(AttachmentFactory::class)->setUser($this->user)->create([
            'filename'        => 'test 1',
            'title'           => 'test 1',
            'attachable_type' => Account::class,
            'attachable_id'   => $this->account->id,
        ]);
        app(AttachmentFactory::class)->setUser($this->user)->create([
            'filename'        => 'test 2',
            'title'           => 'test 2',
            'attachable_type' => Account::class,
            'attachable_id'   => $this->account->id,
        ]);
    }

    public function testIndex(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.attachments', ['account' => $this->account->id]));
        $response->assertStatus(200);
        $response->assertJson([
            'meta' => ['pagination' => ['total' => 2, 'total_pages' => 1]],
        ]);
    }

    public function testIndexCanChangePageSize(): void
    {
        $this->actingAs($this->user);
        $response = $this->getJson(route('api.v1.accounts.attachments', ['account' => $this->account->id, 'limit' => 1]));
        $response->assertStatus(200);
        $response->assertJson([
            'meta' => ['pagination' => ['total' => 2, 'total_pages' => 2]],
        ]);
    }
}
