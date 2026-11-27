<?php

declare(strict_types=1);

/*
 * UnifyUserGroupData.php
 * Copyright (c) 2026 james@firefly-iii.org
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

namespace FireflyIII\Console\Commands\Integrity;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\Support\Authentication\HandlesUserRegistrationTrait;
use FireflyIII\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('integrity:unify-user-group-data')]
#[Description('Make sure all users have one group and owner in that group. Data is moved to that group.')]
class UnifyUserGroupData extends Command
{
    use HandlesUserRegistrationTrait;
    use ShowsFriendlyMessages;

    private array $tables = [
        'accounts',
        'attachments',
        'available_budgets',
        'bills',
        'budgets',
        'categories',
        'currency_exchange_rates',
        'object_groups',
        'recurrences',
        'rule_groups',
        'rules',
        'tags',
        'transaction_groups',
        'transaction_journals',
        'webhooks',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (false === config('firefly.feature_flags.single_user_group')) {
            $this->friendlyWarning('This command is disabled');

            return Command::SUCCESS;
        }
        $users = User::query()->get();

        /** @var User $user */
        foreach ($users as $user) {
            $this->prepUser($user);
            $this->unifyGroupData($user);
            $this->removeOtherMemberships($user);
        }
        $this->deleteEmptyUserGroups();

        /** @var User $user */
        foreach ($users as $user) {
            $this->prepUser($user);
            $this->renamePrimaryUserGroup($user);
        }

        return Command::SUCCESS;
    }

    private function deleteEmptyUserGroup(UserGroup $group): void
    {
        $totalCount = 0;
        foreach ($this->tables as $table) {
            $count = DB::table($table)
                ->where('user_group_id', (int) $group->id)
                ->count()
            ;
            $totalCount += $count;
        }
        if (0 === $totalCount && 0 === $group->groupMemberships()->count()) {
            Log::debug(sprintf('Deleting empty user group "%s" (#%d)', $group->title, $group->id));
            $group->delete();
        }
    }

    private function deleteEmptyUserGroups(): void
    {
        $groups = UserGroup::query()->get();

        /** @var UserGroup $group */
        foreach ($groups as $group) {
            $this->deleteEmptyUserGroup($group);
        }
    }

    private function prepUser(User $user): void
    {
        $userGroup = $user->userGroup;
        if (null === $userGroup) {
            Log::debug(sprintf('User "%s" has no group, creating one.', $user->email));
            // needs a new user group.
            $this->createGroupMembership($user);
        }
    }

    private function removeOtherMemberships(User $user): void
    {
        Log::debug(sprintf('Removing other memberships for user "%s"', $user->email));
        $userGroup   = $user->userGroup;
        $userGroupId = (int) $userGroup->id;
        $fullOwner   = UserRole::whereTitle(UserRoleEnum::OWNER->value)->first();
        if (null === $fullOwner) {
            throw new FireflyException('The user role is unexpectedly empty. Did you run all migrations?');
        }
        $count       = DB::table('group_memberships')
            ->where('user_id', (int) $user->id)
            ->where('user_group_id', '!=', $userGroupId)
            ->where('user_role_id', '!=', $fullOwner->id)
            ->delete()
        ;
        if ($count > 0) {
            Log::debug(sprintf('Removed %d other memberships for user "%s"', $count, $user->email));
        }
    }

    private function renamePrimaryUserGroup(User $user): void
    {
        $userGroup   = $user->userGroup;
        $userGroupId = (int) $userGroup->id;
        // find other groups with the same title:
        $otherGroups = UserGroup::query()->where('id', '!=', $userGroupId)->where('title', $user->email)->get();

        /**
         * @var int       $index
         * @var UserGroup $otherGroup
         */
        foreach ($otherGroups as $index => $otherGroup) {
            $otherGroup->title = sprintf('%s-%d', $user->email, $index);
            $otherGroup->save();
            Log::debug(sprintf('Temporarily renaming group #%d to "%s"', $otherGroup->id, $otherGroup->title));
        }
        // rename the user's group to their email address, if not already so.
        if ($userGroup->title !== $user->email) {
            $userGroup->title = $user->email;
            $userGroup->save();
            Log::debug(sprintf('Renaming group #%d to "%s"', $userGroup->id, $userGroup->title));
        }
    }

    private function unifyGroupData(User $user): void
    {
        Log::debug(sprintf('Unifying group data for user "%s"', $user->email));
        $userGroup   = $user->userGroup;
        $userGroupId = (int) $userGroup->id;

        // update everything owned by this user to this group.
        foreach ($this->tables as $table) {
            $count = DB::table($table)
                ->where('user_id', (int) $user->id)
                ->where(function (Builder $q) use ($userGroupId): void {
                    $q->where('user_group_id', '!=', $userGroupId);
                    $q->orWhereNull('user_group_id');
                })
                ->update(['user_group_id' => $userGroupId])
            ;
            if ($count > 0) {
                Log::debug(sprintf('Updated %d items in "%s" for user "%s" to group "%s"', $count, $table, $user->email, $userGroup->title));
            }
        }
    }
}
