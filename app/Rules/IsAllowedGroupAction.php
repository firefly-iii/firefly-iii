<?php
/*
 * IsAllowedGroupAction.php
 * Copyright (c) 2024 james@firefly-iii.org.
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
 * along with this program.  If not, see https://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace FireflyIII\Rules;

use Closure;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\UserGroup\UserGroupRepositoryInterface;
use FireflyIII\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;

class IsAllowedGroupAction implements ValidationRule
{

    private string $className;
    private string $methodName;

    private array $acceptedRoles;
    private UserGroupRepositoryInterface $repository;

    public function __construct(string $className, string $methodName)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        // you need these roles to do anything with any endpoint.
        $this->acceptedRoles = [UserRoleEnum::OWNER, UserRoleEnum::FULL];
        $this->repository = app(UserGroupRepositoryInterface::class);
    }

    /**
     * @inheritDoc
     * @throws AuthorizationException
     */
    #[\Override] public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if('GET' === $this->methodName) {
            // need at least "read only rights".
            $this->acceptedRoles[] = UserRoleEnum::READ_ONLY;
        }
        if('GET' !== $this->methodName) {
            // either post, put or delete or something else.. you need more access rights.
            switch ($this->className) {
                default:
                    throw new AuthorizationException(sprintf('Cannot handle class "%s"', $this->className));
                case Account::class:
                    $this->acceptedRoles[] = UserRoleEnum::MANAGE_TRANSACTIONS;
                    break;
            }
        }
        $this->validateUserGroup((int)$value, $fail);
    }

    private function validateUserGroup(int $userGroupId, Closure $fail): void {
        Log::debug(sprintf('validateUserGroup: %s', static::class));
        if (!auth()->check()) {
            Log::debug('validateUserGroup: user is not logged in, return NULL.');
            $fail('validation.no_auth_user_group')->translate();
            return;
        }

        /** @var User $user */
        $user        = auth()->user();
        if(0 !== $userGroupId) {
            Log::debug(sprintf('validateUserGroup: user group submitted, search for memberships in group #%d.', $userGroupId));
        }
        if (0 === $userGroupId) {
            $userGroupId = $user->user_group_id;
            Log::debug(sprintf('validateUserGroup: no user group submitted, use default group #%d.', $userGroupId));
        }

        $this->repository->setUser($user);
        $memberships = $this->repository->getMembershipsFromGroupId($userGroupId);

        if (0 === $memberships->count()) {
            Log::debug(sprintf('validateUserGroup: user has no access to group #%d.', $userGroupId));
            $fail('validation.no_access_user_group')->translate();
            return;
        }

        // need to get the group from the membership:
        $userGroup       = $this->repository->getById($userGroupId);
        if (null === $userGroup) {
            Log::debug(sprintf('validateUserGroup: group #%d does not exist.', $userGroupId));
            $fail('validation.belongs_user_or_user_group')->translate();
            return;
        }
        Log::debug(sprintf('validateUserGroup: validate access of user to group #%d ("%s").', $userGroupId, $userGroup->title));
        Log::debug(sprintf('validateUserGroup: have %d roles to check.', count($this->acceptedRoles)), $this->acceptedRoles);

        /** @var UserRoleEnum $role */
        foreach ($this->acceptedRoles as $role) {
            if ($user->hasRoleInGroupOrOwner($userGroup, $role)) {
                Log::debug(sprintf('validateUserGroup: User has role "%s" in group #%d, return.', $role->value, $userGroupId));

                return;
            }
            Log::debug(sprintf('validateUserGroup: User does NOT have role "%s" in group #%d, continue searching.', $role->value, $userGroupId));
        }

        Log::debug('validateUserGroup: User does NOT have enough rights to access endpoint.');
        $fail('validation.belongs_user_or_user_group')->translate();
    }
}
