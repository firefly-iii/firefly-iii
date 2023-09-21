<?php
/*
 * ChecksLogin.php
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

namespace FireflyIII\Support\Request;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Support\Facades\Log;
use ValueError;

/**
 * Trait ChecksLogin
 */
trait ChecksLogin
{
    /**
     * Verify the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        Log::debug(sprintf('Now in %s', __METHOD__));
        // Only allow logged-in users
        $check = auth()->check();
        if (!$check) {
            return false;
        }
        if (!property_exists($this, 'acceptedRoles')) {
            app('log')->debug('Request class has no acceptedRoles array');
            return true; // check for false already took place.
        }
        /** @var UserGroup $userGroup */
        $userGroup = $this->route()->parameter('userGroup');
        if (null === $userGroup) {
            app('log')->debug('Request class has no userGroup parameter.');
            return true;
        }
        /** @var User $user */
        $user = auth()->user();
        /** @var UserRoleEnum $role */
        foreach ($this->acceptedRoles as $role) {
            if ($user->hasRoleInGroup($userGroup, $role, true, true)) {
                return true;
            }
        }
        return false;
    }
}
