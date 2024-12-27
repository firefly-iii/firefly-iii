<?php

/*
 * CreatesGroupMemberships.php
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

namespace FireflyIII\Console\Commands\Correction;

use FireflyIII\Console\Commands\ShowsFriendlyMessages;
use FireflyIII\Enums\UserRoleEnum;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\User;
use Illuminate\Console\Command;

class CreatesGroupMemberships extends Command
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
            $userGroup = UserGroup::create(['title' => $user->email]);
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
}
