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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class CreateGroupMemberships
 */
class CreateGroupMemberships extends Command
{
    public const CONFIG_NAME = '560_create_group_memberships';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update group memberships';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:create-group-memberships';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        $start = microtime(true);

        $this->createGroupMemberships();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('Validated group memberships in %s seconds.', $end));

        return 0;
    }

    /**
     *
     * @throws FireflyException
     */
    private function createGroupMemberships(): void
    {
        $users = User::get();
        /** @var User $user */
        foreach ($users as $user) {
            Log::debug(sprintf('Manage group memberships for user #%d', $user->id));
            self::createGroupMembership($user);
            Log::debug(sprintf('Done with user #%d', $user->id));
        }
    }

    /**
     * TODO move to helper.
     * @param  User  $user
     *
     * @throws FireflyException
     */
    public static function createGroupMembership(User $user): void
    {
        // check if membership exists
        $userGroup = UserGroup::where('title', $user->email)->first();
        if (null === $userGroup) {
            $userGroup = UserGroup::create(['title' => $user->email]);
            Log::debug(sprintf('Created new user group #%d ("%s")', $userGroup->id, $userGroup->title));
        }

        $userRole = UserRole::where('title', UserRole::OWNER)->first();

        if (null === $userRole) {
            throw new FireflyException('Firefly III could not find a user role. Please make sure all migrations have run.');
        }
        $membership = GroupMembership::where('user_id', $user->id)
                                     ->where('user_group_id', $userGroup->id)
                                     ->where('user_role_id', $userRole->id)->first();
        if (null === $membership) {
            GroupMembership::create(
                [
                    'user_id'       => $user->id,
                    'user_role_id'  => $userRole->id,
                    'user_group_id' => $userGroup->id,
                ]
            );
            Log::debug('Created new membership.');
        }
        if (null === $user->user_group_id) {
            $user->user_group_id = $userGroup->id;
            $user->save();
            Log::debug('Put user in default group.');
        }

        Log::debug(sprintf('User #%d now has main group.', $user->id));
    }
}
