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

use FireflyIII\Factory\UserGroupFactory;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

/**
 * Class UserGroupRepository
 */
class UserGroupRepository implements UserGroupRepositoryInterface
{
    private User $user;

    /**
     * @inheritDoc
     */
    public function destroy(UserGroup $userGroup): void
    {
        app('log')->debug(sprintf('Going to destroy user group #%d ("%s").', $userGroup->id, $userGroup->title));
        $memberships = $userGroup->groupMemberships()->get();
        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            /** @var User $user */
            $user = $membership->user()->first();
            if (null === $user) {
                continue;
            }
            app('log')->debug(sprintf('Processing membership #%d (user #%d "%s")', $membership->id, $user->id, $user->email));
            // user has memberships of other groups?
            $count = $user->groupMemberships()->where('user_group_id', '!=', $userGroup->id)->count();
            if (0 === $count) {
                app('log')->debug('User has no other memberships and needs a new administration.');
                // makeNewAdmin()
                // assignToUser().
            }
            // user has other memberships, select one at random and assign it to the user.
            if ($count > 0) {
                // findAndAssign()
            }
            // deleteMembership()
        }
        // all users are now moved away from user group.
        // time to DESTROY all objects.
        // TODO piggy banks linked to accounts were deleting.
        $userGroup->piggyBanks()->delete();
        $userGroup->accounts()->delete();
        $userGroup->availableBudgets()->delete();
        $userGroup->attachments()->delete();
        $userGroup->bills()->delete();
        $userGroup->budgets()->delete();
        $userGroup->categories()->delete();
        $userGroup->currencyExchangeRates()->delete();
        $userGroup->objectGroups()->delete();
        $userGroup->recurrences()->delete();
        $userGroup->rules()->delete();
        $userGroup->ruleGroups()->delete();
        $userGroup->tags()->delete();
        $userGroup->transactionJournals()->delete(); // TODO needs delete service probably.
        $userGroup->transactionGroups()->delete();   // TODO needs delete service probably.
        $userGroup->webhooks()->delete();

        // user group deletion should also delete everything else.
        // for all users, if this is the primary user group switch to the first alternative.
        // if they have no other memberships, create a new user group for them.
        $userGroup->delete();

    }

    /**
     * Returns all groups the user is member in.
     *
     * @inheritDoc
     */
    public function get(): Collection
    {
        $collection  = new Collection();
        $memberships = $this->user->groupMemberships()->get();
        /** @var GroupMembership $membership */
        foreach ($memberships as $membership) {
            /** @var UserGroup $group */
            $group = $membership->userGroup()->first();
            if (null !== $group) {
                $collection->push($group);
            }
        }
        return $collection;
    }

    /**
     * Returns all groups.
     *
     * @inheritDoc
     */
    public function getAll(): Collection
    {
        return UserGroup::all();
    }

    /**
     * @inheritDoc
     */
    public function setUser(Authenticatable | User | null $user): void
    {
        app('log')->debug(sprintf('Now in %s', __METHOD__));
        if (null !== $user) {
            $this->user = $user;
        }
    }

    /**
     * @param array $data
     *
     * @return UserGroup
     */
    public function store(array $data): UserGroup
    {
        $data['user'] = $this->user;
        /** @var UserGroupFactory $factory */
        $factory = app(UserGroupFactory::class);
        return $factory->create($data);
    }
}
