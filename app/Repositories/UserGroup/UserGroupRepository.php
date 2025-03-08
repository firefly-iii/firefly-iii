<?php

/*
 * UserGroupRepository.php
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

namespace FireflyIII\Repositories\UserGroup;

use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\UserGroupFactory;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupInterface;
use FireflyIII\Support\Repositories\UserGroup\UserGroupTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Override;
use ValueError;

/**
 * Class UserGroupRepository
 */
class UserGroupRepository implements UserGroupRepositoryInterface, UserGroupInterface
{
    use UserGroupTrait;

    public function destroy(UserGroup $userGroup): void
    {
        app('log')->debug(sprintf('Going to destroy user group #%d ("%s").', $userGroup->id, $userGroup->title));
        $memberships = $userGroup->groupMemberships()->get();

        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            /** @var null|User $user */
            $user = $membership->user()->first();
            if (null === $user) {
                continue;
            }
            app('log')->debug(sprintf('Processing membership #%d (user #%d "%s")', $membership->id, $user->id, $user->email));
            // user has memberships of other groups?
            $count = $user->groupMemberships()->where('user_group_id', '!=', $userGroup->id)->count();
            if (0 === $count) {
                app('log')->debug('User has no other memberships and needs a new user group.');
                $newUserGroup        = $this->createNewUserGroup($user);
                $user->user_group_id = $newUserGroup->id;
                $user->save();
                app('log')->debug(sprintf('Make new group #%d ("%s")', $newUserGroup->id, $newUserGroup->title));
            }
            // user has other memberships, select one at random and assign it to the user.
            if ($count > 0) {
                app('log')->debug('User has other memberships and will be assigned a new administration.');

                /** @var GroupMembership $first */
                $first               = $user->groupMemberships()->where('user_group_id', '!=', $userGroup->id)->inRandomOrder()->first();
                $user->user_group_id = $first->id;
                $user->save();
            }
            // delete membership so group is empty after this for-loop.
            $membership->delete();
        }
        // all users are now moved away from user group.
        // time to DESTROY all objects.
        // we have to do this one by one to trigger the necessary observers :(
        $objects = ['availableBudgets', 'bills', 'budgets', 'categories', 'currencyExchangeRates', 'objectGroups',
                    'recurrences', 'rules', 'ruleGroups', 'tags', 'transactionGroups', 'transactionJournals', 'piggyBanks', 'accounts', 'webhooks',
        ];
        foreach ($objects as $object) {
            foreach ($userGroup->{$object}()->get() as $item) { // @phpstan-ignore-line
                $item->delete();
            }
        }
        $userGroup->delete();
        app('log')->debug('Done!');
    }

    /**
     * Returns all groups the user is member in.
     *
     * {@inheritDoc}
     */
    public function get(): Collection
    {
        $collection  = new Collection();
        $set         = [];
        $memberships = $this->user->groupMemberships()->get();

        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            /** @var null|UserGroup $group */
            $group = $membership->userGroup()->first();
            if (null !== $group) {
                $groupId = $group->id;
                if (in_array($groupId, array_keys($set), true)) {
                    continue;
                }
                $set[$groupId] = $group;
            }
        }
        $collection->push(...$set);

        return $collection;
    }

    /**
     * Because there is the chance that a group with this name already exists,
     * Firefly III runs a little loop of combinations to make sure the group name is unique.
     */
    private function createNewUserGroup(User $user): UserGroup
    {
        $loop          = 0;
        $groupName     = $user->email;
        $exists        = true;
        $existingGroup = null;
        while ($exists && $loop < 10) {
            $existingGroup = $this->findByName($groupName);
            if (null === $existingGroup) {
                $exists = false;

                /** @var null|UserGroup $existingGroup */
                $existingGroup = $this->store(['user' => $user, 'title' => $groupName]);
            }
            if (null !== $existingGroup) {
                // group already exists
                $groupName = sprintf('%s-%s', $user->email, substr(sha1(rand(1000, 9999) . microtime()), 0, 4));
            }
            ++$loop;
        }

        return $existingGroup;
    }

    public function findByName(string $title): ?UserGroup
    {
        return UserGroup::whereTitle($title)->first();
    }

    /**
     * @throws FireflyException
     */
    public function store(array $data): UserGroup
    {
        $data['user'] = $this->user;

        /** @var UserGroupFactory $factory */
        $factory = app(UserGroupFactory::class);

        return $factory->create($data);
    }

    /**
     * Returns all groups.
     *
     * {@inheritDoc}
     */
    public function getAll(): Collection
    {
        return UserGroup::all();
    }

    #[Override]
    public function getById(int $id): ?UserGroup
    {
        return UserGroup::find($id);
    }

    #[Override]
    public function getMembershipsFromGroupId(int $groupId): Collection
    {
        return $this->user->groupMemberships()->where('user_group_id', $groupId)->get();
    }

    public function update(UserGroup $userGroup, array $data): UserGroup
    {
        $userGroup->title = $data['title'];
        $userGroup->save();
        $currency = null;

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);

        if (array_key_exists('native_currency_code', $data)) {
            $repository->setUser($this->user);
            $currency = $repository->findByCode($data['native_currency_code']);
        }

        if (array_key_exists('native_currency_id', $data) && null === $currency) {
            $repository->setUser($this->user);
            $currency = $repository->find((int) $data['native_currency_id']);
        }
        if (null !== $currency) {
            $repository->makeDefault($currency);
        }


        return $userGroup;
    }

    /**
     * @SuppressWarnings("PHPMD.NPathComplexity")
     *
     * @throws FireflyException
     */
    public function updateMembership(UserGroup $userGroup, array $data): UserGroup
    {
        $owner = UserRole::whereTitle(UserRoleEnum::OWNER)->first();
        app('log')->debug('in update membership');

        /** @var null|User $user */
        $user = null;
        if (array_key_exists('id', $data)) {
            /** @var null|User $user */
            $user = User::find($data['id']);
            app('log')->debug('Found user by ID');
        }
        if (array_key_exists('email', $data) && '' !== (string) $data['email']) {
            /** @var null|User $user */
            $user = User::whereEmail($data['email'])->first();
            app('log')->debug('Found user by email');
        }
        if (null === $user) {
            // should throw error, but validator already catches this.
            app('log')->debug('No user found');

            return $userGroup;
        }
        // count the number of members in the group right now:
        $membershipCount = $userGroup->groupMemberships()->distinct()->count('group_memberships.user_id');

        // if it's 1:
        if (1 === $membershipCount) {
            $lastUserId = $userGroup->groupMemberships()->distinct()->first(['group_memberships.user_id'])->user_id;
            // if this is also the user we're editing right now, and we remove all of their roles:
            if ($lastUserId === (int) $user->id && 0 === count($data['roles'])) {
                app('log')->debug('User is last in this group, refuse to act');

                throw new FireflyException('You cannot remove the last member from this user group. Delete the user group instead.');
            }
            // if this is also the user we're editing right now, and do not grant them the owner role:
            if ($lastUserId === (int) $user->id && count($data['roles']) > 0 && !in_array(UserRoleEnum::OWNER->value, $data['roles'], true)) {
                app('log')->debug('User needs to have owner role in this group, refuse to act');

                throw new FireflyException('The last member in this user group must get or keep the "owner" role.');
            }
        }
        if ($membershipCount > 1) {
            // group has multiple members. How many are owner, except the user we're editing now?
            $ownerCount = $userGroup->groupMemberships()
                                    ->where('user_role_id', $owner->id)
                                    ->where('user_id', '!=', $user->id)->count();
            // if there are no other owners and the current users does not get or keep the owner role, refuse.
            if (
                0 === $ownerCount
                && (0 === count($data['roles'])
                    || (count($data['roles']) > 0 // @phpstan-ignore-line
                        && !in_array(UserRoleEnum::OWNER->value, $data['roles'], true)))) {
                app('log')->debug('User needs to keep owner role in this group, refuse to act');

                throw new FireflyException('The last owner in this user group must keep the "owner" role.');
            }
        }
        // simplify the list of roles:
        $rolesSimplified = $this->simplifyListByName($data['roles']);

        // delete all existing roles for user:
        $user->groupMemberships()->where('user_group_id', $userGroup->id)->delete();
        foreach ($rolesSimplified as $role) {
            try {
                $enum = UserRoleEnum::from($role);
            } catch (ValueError $e) {
                // TODO error message
                continue;
            }
            $userRole = UserRole::whereTitle($enum->value)->first();
            $user->groupMemberships()->create(['user_group_id' => $userGroup->id, 'user_role_id' => $userRole->id]);
        }

        return $userGroup;
    }

    private function simplifyListByName(array $roles): array
    {
        if (in_array(UserRoleEnum::OWNER->value, $roles, true)) {
            app('log')->debug(sprintf('List of roles is [%1$s] but this includes "%2$s", so return [%2$s]', implode(',', $roles), UserRoleEnum::OWNER->value));

            return [UserRoleEnum::OWNER->value];
        }
        if (in_array(UserRoleEnum::FULL->value, $roles, true)) {
            app('log')->debug(sprintf('List of roles is [%1$s] but this includes "%2$s", so return [%2$s]', implode(',', $roles), UserRoleEnum::FULL->value));

            return [UserRoleEnum::FULL->value];
        }

        return $roles;
    }

    #[Override]
    public function useUserGroup(UserGroup $userGroup): void
    {
        $this->user->user_group_id = $userGroup->id;
        $this->user->save();
    }
}
