<?php


/*
 * ValidatesAdministrationAccess.php
 * Copyright (c) 2023 james@firefly-iii.org
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

namespace FireflyIII\Validation\Administration;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\UserRole;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Validator;

/**
 * Trait ValidatesAdministrationAccess
 */
trait ValidatesAdministrationAccess
{
    /**
     * @param Validator $validator
     * @param array     $allowedRoles
     *
     * @return void
     * @throws AuthenticationException
     * @throws FireflyException
     */
    protected function validateAdministration(Validator $validator, array $allowedRoles): void
    {
        Log::debug('Now in validateAdministration()');
        if (!auth()->check()) {
            Log::error('User is not authenticated.');
            throw new AuthenticationException('No access to validateAdministration() method.');
        }
        /** @var User $user */
        $user = auth()->user();
        // get data from request:
        $data = $validator->getData();
        // check if user is part of this administration
        $administrationId = (int)($data['administration_id'] ?? $user->getAdministrationId());
        // safety catch:
        if (0 === $administrationId) {
            Log::error('validateAdministration ran into empty administration ID.');
            throw new AuthenticationException('Cannot validate administration.');
        }
        // grab the group:
        $repository = app(UserRepositoryInterface::class);

        // collect the user's roles in this group:
        $array = $repository->getRolesInGroup($user, $administrationId);
        if (0 === count($array)) {
            Log::error(sprintf('User #%d ("%s") has no membership in group #%d.', $user->id, $user->email, $administrationId));
            $validator->errors()->add('administration', (string)trans('validation.no_access_user_group'));
            return;
        }
        if (in_array(UserRole::OWNER, $array, true)) {
            Log::debug('User is owner of this administration.');
            return;
        }
        if (in_array(UserRole::FULL, $array, true)) {
            Log::debug('User has full access to this administration.');
            return;
        }
        $access = true;
        foreach ($allowedRoles as $allowedRole) {
            if (!in_array($allowedRole, $array, true)) {
                $access = false;
            }
        }
        if (false === $access) {
            Log::error(
                sprintf(
                    'User #%d has memberships [%s] to group #%d but needs [%s].',
                    $user->id,
                    join(', ', $array),
                    $administrationId,
                    join(', ', $allowedRoles)
                )
            );
            $validator->errors()->add('administration', (string)trans('validation.no_access_user_group'));
        }
    }
}
