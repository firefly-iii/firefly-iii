<?php

/*
 * CorrectsGroupInformation.php
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
use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\ObjectGroup;
use FireflyIII\Models\Preference;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\UserGroup;
use FireflyIII\Models\Webhook;
use FireflyIII\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

class CorrectsGroupInformation extends Command
{
    use ShowsFriendlyMessages;

    protected $description = 'Makes sure that every object is linked to a group';
    protected $signature   = 'correction:group-information';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // objects: accounts, attachments, available budgets, bills, budgets, categories, currency_exchange_rates
        // recurrences, rule groups, rules, tags, transaction groups, transaction journals, webhooks

        $users = User::get();

        /** @var User $user */
        foreach ($users as $user) {
            $this->updateGroupInfo($user);
        }

        return 0;
    }

    private function updateGroupInfo(User $user): void
    {
        $group = $user->userGroup;
        if (null === $group) {
            $this->friendlyWarning(sprintf('User "%s" has no group. Please run "php artisan firefly-iii:create-group-memberships"', $user->email));

            return;
        }
        $set   = [
            Account::class,
            Attachment::class,
            AvailableBudget::class,
            Bill::class,
            Budget::class,
            Category::class,
            ObjectGroup::class,
            CurrencyExchangeRate::class,
            Preference::class,
            Recurrence::class,
            RuleGroup::class,
            Rule::class,
            Tag::class,
            TransactionGroup::class,
            TransactionJournal::class,
            Webhook::class,
        ];
        foreach ($set as $className) {
            $this->updateGroupInfoForObject($user, $group, $className);
        }
    }

    private function updateGroupInfoForObject(User $user, UserGroup $group, string $className): void
    {
        try {
            $result = $className::where('user_id', $user->id)->where('user_group_id', null)->update(['user_group_id' => $group->id]);
        } catch (QueryException $e) {
            $this->friendlyError(sprintf('Could not update group information for "%s" because of error "%s"', $className, $e->getMessage()));

            return;
        }
        if (0 !== $result) {
            $this->friendlyPositive(sprintf('User #%d: Moved %d %s objects to the correct group.', $user->id, $result, str_replace('FireflyIII\Models\\', '', $className)));
        }
    }
}
