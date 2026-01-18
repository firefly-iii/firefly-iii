<?php

/*
 * PiggyBankEventHandler.php
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

namespace FireflyIII\Handlers\Events\Model;

use FireflyIII\Events\Model\PiggyBank\PiggyBankAmountIsChanged;
use FireflyIII\Events\Model\PiggyBank\ChangedName;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionGroup;
use Illuminate\Support\Facades\Log;

/**
 * Class PiggyBankEventHandler
 */
class PiggyBankEventHandler
{
    public function changedPiggyBankName(ChangedName $event): void
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
