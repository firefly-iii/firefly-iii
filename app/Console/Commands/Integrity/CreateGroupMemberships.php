<?php

/*
 * CreateGroupMemberships.php
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateGroupMemberships
 */
class CreateGroupMemberships extends Command
{
    use ShowsFriendlyMessages;

    public const string CONFIG_NAME = '560_create_group_memberships';
    protected $description          = 'Update group memberships';
    protected $signature            = 'firefly-iii:create-group-memberships';

    /**
     * Execute the console command.
     *
     * @throws FireflyException
     */
    public function handle(): int
    {
        $this->createGroupMemberships();
        $this->setDefaultGroups();
        $this->friendlyPositive('Validated group memberships');

        return 0;
    }

    /**
     * @throws FireflyException
     */
    private function createGroupMemberships(): void
    {
        $users = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            self::createGroupMembership($user);
        }
    }

    /**
     * TODO move to helper.
     *
     * @throws FireflyException
     */
    public static function createGroupMembership(User $user): void
    {
        // check if membership exists
        $userGroup  = UserGroup::where('title', $user->email)->first();
        if (null === $userGroup) {
            $userGroup = UserGroup::create(['title' => $user->email, 'default_administration' => true]);
        }

        $userRole   = UserRole::where('title', UserRoleEnum::OWNER->value)->first();

        if (null === $userRole) {
            throw new FireflyException('Firefly III could not find a user role. Please make sure all migrations have run.');
        }
        $membership = GroupMembership::where('user_id', $user->id)
            ->where('user_group_id', $userGroup->id)
            ->where('user_role_id', $userRole->id)->first()
        ;
        if (null === $membership) {
            GroupMembership::create(
                [
                    'user_id'       => $user->id,
                    'user_role_id'  => $userRole->id,
                    'user_group_id' => $userGroup->id,
                ]
            );
        }
        if (null === $user->user_group_id) {
            $user->user_group_id = $userGroup->id;
            $user->save();
        }
    }

    private function setDefaultGroups(): void
    {
        $users = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            $this->setDefaultGroup($user);
        }
    }

    /**
     * @throws FireflyException
     */
    private function setDefaultGroup(User $user): void
    {
        Log::debug(sprintf('setDefaultGroup() for #%d "%s"', $user->id, $user->email));

        /** @var UserRepositoryInterface $repository */
        $repository = app(UserRepositoryInterface::class);
        $groups     = $repository->getUserGroups($user);
        if (1 === $groups->count()) {
            /** @var UserGroup $first */
            $first                         = $groups->first();
            $first->default_administration = true;
            $first->save();
            Log::debug(sprintf('User has only one group (#%d, "%s"), make it the default (owner or not).', $first->id, $first->title));

            return;
        }
        Log::debug(sprintf('User has %d groups.', $groups->count()));
        /*
         * Loop all the groups, expect to find at least ONE
         * where you're owner, and it has your name. In that case, it's yours.
         * Then we can safely return and stop.
         */

        /** @var UserGroup $group */
        foreach ($groups as $group) {
            $group->default_administration = false;
            $group->save();
            if ($group->title === $user->email) {
                $roles   = $repository->getRolesInGroup($user, $group->id);
                Log::debug(sprintf('Group #%d ("%s")', $group->id, $group->title), $roles);
                $isOwner = false;
                foreach ($roles as $role) {
                    if ($role === UserRoleEnum::OWNER->value) {
                        $isOwner = true;
                    }
                }
                if (true === $isOwner) {
                    // make this group the default, set the rest NOT to be the default:
                    $group->default_administration = true;
                    $group->save();
                    Log::debug(sprintf('Make group #%d ("%s") the default (is owner + name matches).', $group->id, $group->title));

                    return;
                }
                if (false === $isOwner) {
                    $this->friendlyWarning(sprintf('User "%s" has a group with matching name (#%d), but is not the owner. User will be given the owner role.', $user->email, $group->id));
                    self::createGroupMembership($user);

                    return;
                }
            }
        }
        // if there is no group at all, create it.
        $this->friendlyWarning(sprintf('User "%s" has no group with matching name. Will be created.', $user->email));
        self::createGroupMembership($user);
    }
}
