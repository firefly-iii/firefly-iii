<?php

/**
 * TestCase.php
 * Copyright (c) 2020 james@firefly-iii.org
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

namespace Tests\integration;

use FireflyIII\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\integration\Traits\CollectsValues;

/**
 * Class TestCase
 */
abstract class TestCase extends BaseTestCase
{
    use CollectsValues;
    use CreatesApplication;

    protected const MAX_ITERATIONS = 2;

    public function dateRangeProvider(): array
    {
        return [
            'one day'      => ['1D'],
            'one week'     => ['1W'],
            'one month'    => ['1M'],
            'three months' => ['3M'],
            'six months'   => ['6M'],
            'one year'     => ['1Y'],
            'custom range' => ['custom'],
        ];
    }

    protected function createAuthenticatedUser(): User
    {
        $group = UserGroup::create(['title' => 'test@email.com']);
        return User::create([
            'email'    => 'test@email.com',
            'password' => 'password',
            'user_group_id' => $group->id,
        ]);
    }
}
