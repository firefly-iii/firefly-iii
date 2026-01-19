<?php

declare(strict_types=1);
/*
 * UpdatesRulesForChangedPiggyBankName.php
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

namespace FireflyIII\Listeners\Model\PiggyBank;

use FireflyIII\Events\Model\PiggyBank\PiggyBankNameIsChanged;
use FireflyIII\Models\Account;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdatesRulesForChangedPiggyBankName implements ShouldQueue
{
    public function handle(PiggyBankNameIsChanged $event): void
    {
        // loop all accounts, collect all user's rules.
        /** @var Account $account */
        foreach ($event->piggyBank->accounts as $account) {
            /** @var Rule $rule */
            foreach ($account->user->rules as $rule) {
                /** @var RuleAction $ruleAction */
                foreach ($rule->ruleActions()->where('action_type', 'update_piggy')->get() as $ruleAction) {
                    if ($event->oldName === $ruleAction->action_value) {
                        $ruleAction->action_value = $event->newName;
                        $ruleAction->save();
                    }
                }
            }
        }
    }
}
