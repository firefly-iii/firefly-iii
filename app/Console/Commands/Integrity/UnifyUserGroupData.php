<?php
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
    use ShowsFriendlyMessages;
    use HandlesUserRegistrationTrait;

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
            $this->unifyGroupData($user);
        }

        return Command::SUCCESS;
    }

    private function unifyGroupData(User $user): void
    {
        Log::debug(sprintf('Unifying group data for user "%s"', $user->email));
        $userGroup = $user->userGroup;
        if (null === $userGroup) {
            Log::debug(sprintf('User "%s" has no group, creating one.', $user->email));
            // needs a new user group.
            $this->createGroupMembership($user);
            $userGroup = $user->userGroup()->first();
        }
        $userGroupId = (int)$userGroup->id;

        // update everything owned by this user to this group.
        $tables = ['accounts', 'attachments', 'available_budgets', 'bills', 'budgets', 'categories', 'currency_exchange_rates', 'object_groups', 'recurrences', 'rule_groups', 'rules', 'tags', 'transaction_groups', 'transaction_journals', 'webhooks'];

        foreach ($tables as $table) {
            $count = DB::table($table)
                       ->where('user_id', (int)$user->id)
                       ->where(function (Builder $q) use ($userGroupId) {
                           $q->where('user_group_id', '!=', $userGroupId);
                           $q->orWhereNull('user_group_id');
                       })->update(['user_group_id' => $userGroupId]);
            if ($count > 0) {
                Log::debug(sprintf('Updated %d items in "%s" for user "%s" to group "%s"', $count, $table, $user->email, $userGroup->title));
            }
        }


    }

}
