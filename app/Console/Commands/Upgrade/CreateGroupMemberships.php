<?php

/*
 * CreateGroupMemberships.php
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

namespace FireflyIII\Console\Commands\Upgrade;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\GroupMembership;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\UserRole;
use FireflyIII\User;
use Illuminate\Console\Command;
use Log;

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
    protected $description = 'SOME DESCRIPTION';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:create-group-memberships {--F|force : Force the execution of this command.}';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FireflyException
     */
    public function handle(): int
    {
        $start = microtime(true);
        if ($this->isExecuted() && true !== $this->option('force')) {
            $this->warn('This command has already been executed.');

            return 0;
        }
        $this->createGroupMemberships();
        $this->markAsExecuted();

        $end = round(microtime(true) - $start, 2);
        $this->info(sprintf('in %s seconds.', $end));

        return 0;
    }

    /**
     * @return bool
     * @throws FireflyException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function isExecuted(): bool
    {
        $configVar = app('fireflyconfig')->get(self::CONFIG_NAME, false);
        if (null !== $configVar) {
            return (bool) $configVar->data;
        }

        return false;
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
            if (!$this->hasGroupMembership($user)) {
                Log::debug(sprintf('User #%d has no main group.', $user->id));
                $this->createGroupMembership($user);
            }
            Log::debug(sprintf('Done with user #%d', $user->id));
        }
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function hasGroupMembership(User $user): bool
    {
        return $user->groupMemberships()->count() > 0;
    }

    /**
     * @param User $user
     *
     * @throws FireflyException
     */
    private function createGroupMembership(User $user): void
    {
        $userGroup = UserGroup::create(['title' => $user->email]);
        $userRole  = UserRole::where('title', UserRole::OWNER)->first();

        if (null === $userRole) {
            throw new FireflyException('Firefly III could not find a user role. Please make sure all validations have run.');
        }

        $membership = GroupMembership::create(
            [
                'user_id'       => $user->id,
                'user_role_id'  => $userRole->id,
                'user_group_id' => $userGroup->id,
            ]
        );
        if (null === $membership) {
            throw new FireflyException('Firefly III could not create user group management object. Please make sure all validations have run.');
        }
        $user->user_group_id = $userGroup->id;
        $user->save();

        Log::debug(sprintf('User #%d now has main group.', $user->id));
    }

    /**
     *
     */
    private function markAsExecuted(): void
    {
        app('fireflyconfig')->set(self::CONFIG_NAME, true);
    }
}
