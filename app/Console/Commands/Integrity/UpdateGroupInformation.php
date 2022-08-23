<?php
/*
 * UpdateGroupInformation.php
 * Copyright (c) 2022 james@firefly-iii.org
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

use FireflyIII\Models\Account;
use FireflyIII\Models\Attachment;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\CurrencyExchangeRate;
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

/**
 * Class UpdateGroupInformation
 */
class UpdateGroupInformation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly-iii:upgrade-group-information';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes sure that every object is linked to a group';

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
            $this->warn(sprintf('User "%s" has no group.', $user->email));
            return;
        }
        $set = [Account::class, Attachment::class, AvailableBudget::class,
                Bill::class, Budget::class, Category::class, CurrencyExchangeRate::class,
                Recurrence::class, RuleGroup::class, Rule::class, Tag::class, TransactionGroup::class,
                TransactionJournal::class, Webhook::class];
        foreach ($set as $className) {
            $this->updateGroupInfoForObject($user, $group, $className);
        }
    }

    /**
     * @param User      $user
     * @param UserGroup $group
     * @param string    $className
     * @return void
     */
    private function updateGroupInfoForObject(User $user, UserGroup $group, string $className): void
    {
        $result = $className::where('user_id', $user->id)->where('user_group_id', null)->update(['user_group_id' => $group->id]);
        if (0 !== $result) {
            $this->line(sprintf('Moved %d %s objects to the correct group.', $result, str_replace('FireflyIII\\Models\\', '', $className)));
        }
    }
}
