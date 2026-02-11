<?php
/*
 * UpdatesRulesForChangedBill.php
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

namespace FireflyIII\Listeners\Model\Bill;

use FireflyIII\Events\Model\Bill\UpdatedExistingBill;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdatesRulesForChangedBill implements ShouldQueue
{
    public function handle(UpdatedExistingBill $event): void
    {

        // update rule actions.
        if ($event->bill->name !== $event->oldData['name']) {
            $this->updateBillTriggersAndActions($event->bill, $event->oldData);
        }
    }


    private function updateBillTriggersAndActions(Bill $bill, array $oldData): void
    {
        Log::debug(sprintf('Now in updateBillTriggersAndActions(#%d)', $bill->id));
        $repository = app(RuleRepositoryInterface::class);
        $repository->setUser($bill->user);
        $rules = $repository->getAll();

        /** @var Rule $rule */
        foreach ($rules as $rule) {
            $this->updateRule($bill, $rule, $oldData);
        }
    }

    private function updateRule(Bill $bill, Rule $rule, array $oldData): void
    {
        $triggers = ['bill_is', 'bill_ends', 'bill_starts', 'bill_contains'];
        /** @var RuleTrigger $trigger */
        foreach ($rule->ruleTriggers as $trigger) {
            if (in_array($trigger->trigger_type, $triggers, true) && $trigger->trigger_value === $oldData['name']) {
                Log::debug(sprintf('Updated trigger #%d in rule #%d to new subscription name', $trigger->id, $rule->id));
                $trigger->trigger_value = $bill->name;
                $trigger->save();
            }
        }
        /** @var RuleAction $action */
        foreach ($rule->ruleActions as $action) {
            if ($action->action_type === 'link_to_bill' && $action->action_value === $oldData['name']) {
                Log::debug(sprintf('Updated action #%d in rule #%d to new subscription name', $action->id, $rule->id));
                $action->action_value = $bill->name;
                $action->save();
            }
        }
    }

}
