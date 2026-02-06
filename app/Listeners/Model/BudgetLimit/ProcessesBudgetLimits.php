<?php
/*
 * RecalculatesAvailableBudgets.php
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

namespace FireflyIII\Listeners\Model\BudgetLimit;

use FireflyIII\Enums\WebhookTrigger;
use FireflyIII\Events\Model\BudgetLimit\CreatedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\DestroyedBudgetLimit;
use FireflyIII\Events\Model\BudgetLimit\UpdatedBudgetLimit;
use FireflyIII\Generator\Webhook\MessageGeneratorInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Support\Facades\Amount;
use FireflyIII\Support\Models\AvailableBudgetCalculator;
use FireflyIII\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

class ProcessesBudgetLimits implements ShouldQueue
{

    public function handle(DestroyedBudgetLimit | CreatedBudgetLimit | UpdatedBudgetLimit $event): void
    {

        if ($event instanceof DestroyedBudgetLimit && null !== $event->user) {
            // need to recalculate all available budgets for this user.
            $calculator = new AvailableBudgetCalculator();
            $calculator->setUser($event->user);
            $calculator->setStart($event->start->clone());
            $calculator->setEnd($event->end->clone());
            $calculator->setCreate(false);
            $calculator->setCurrency(Amount::getPrimaryCurrencyByUserGroup($event->user->userGroup));
            $calculator->recalculateByRange();

            // do webhooks
            if ($event->createWebhookMessages) {
                $this->createWebhookMessages($event->user, $event->budget, WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT);
            }

            return;
        }

        $calculator = new AvailableBudgetCalculator();
        $calculator->setUser($event->budgetLimit->budget->user);
        $calculator->setStart($event->budgetLimit->start_date->clone());
        $calculator->setEnd($event->budgetLimit->end_date->clone());
        $calculator->setCreate(true);
        $calculator->setCurrency($event->budgetLimit->transactionCurrency);
        $calculator->recalculateByRange();

        // do webhooks:
        if ($event->createWebhookMessages) {
            $this->createWebhookMessages($event->budgetLimit->budget->user, $event->budgetLimit->budget, WebhookTrigger::STORE_UPDATE_BUDGET_LIMIT);
        }
    }

    private function createWebhookMessages(User $user, Budget $budget, WebhookTrigger $trigger): void
    {
        /** @var MessageGeneratorInterface $engine */
        $engine = app(MessageGeneratorInterface::class);
        $engine->setUser($user);
        $engine->setObjects(new Collection()->push($budget));
        $engine->setTrigger($trigger);
        $engine->generateMessages();
    }
}
